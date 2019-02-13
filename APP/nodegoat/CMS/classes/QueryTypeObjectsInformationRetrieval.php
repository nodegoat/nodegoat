<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class QueryTypeObjectsInformationRetrieval {
	
	protected $str_host = false;
	protected $arr_queries = [];
	protected $arr_type_sets_selection = false;
	
	protected $arr_results = false;
	protected $sql_table_name = false;
	
	static protected $arr_map_collection_id = [
		'object_value' => 1,
		'object_description' => 2,
		'object_sub_description' => 3
	];
	static protected $arr_map_result_collection_id = [
		'ov' => 1,
		'od' => 2,
		'osd' => 3
	];

    public function __construct($str_host) {
		
		$this->str_host = $str_host;
    }
	
	public function setQuery($arr_queries) {
		
		$arr_queries = (!is_array($arr_queries) ? [$arr_queries] : $arr_queries);
		
		foreach ($arr_queries as $str_query) {
			
			if (substr($str_query, 0, 1) == '[' && substr($str_query, -1) == ']') {
				$this->arr_queries[] = substr($str_query, 1, -1);
			} else {
				$this->arr_queries[] = trim($str_query, '*').'*';	
			}
		}
	}
	
	public function setTypeSetsSelection($arr_selection) {
	
		$this->arr_type_sets_selection = $arr_selection;
	}
    	
	public function query() {

		$arr_selection = [];
		
		foreach ($this->arr_type_sets_selection as $type_id => $arr_type_set_selection) {
			
			if ($arr_type_set_selection['object_value']) {
				$arr_selection[] = $type_id.':ov:';
			}
			foreach ((array)$arr_type_set_selection['object_descriptions'] as $object_description_id) {
				$arr_selection[] = $type_id.':od:'.$object_description_id;
			}
			foreach ((array)$arr_type_set_selection['object_sub_descriptions'] as $object_sub_description_id) {
				$arr_selection[] = $type_id.':osd:'.$object_sub_description_id;
			}
		}

		$command = 'curl --no-buffer --silent --show-error -X POST "'.$this->str_host.'query/term/" -H  "accept: application/json" -H  "Content-Type: application/json" --data-binary @-';
		
		$process = new ProcessProgram($command);

		$arr_settings = [
			'queries' => $this->arr_queries,
			'selection' => $arr_selection,
			'settings' => [
				'offset' => 0,
				'limit' => 10000,
				'include_value' => false
			]
		];
		
		$process->writeInput(json_encode($arr_settings));
		
		$process->closeInput();
		
		$process->checkOutput(true, true);

		$str_error = $process->getError();
		$str_result = $process->getOutput();
		
		$process->close();
		
		if ($str_error !== '') {

			error($str_error, TROUBLE_ERROR, LOG_BOTH, $str_result);
		}
		
		$this->arr_result = json_decode($str_result, true);
		
		if (!$this->arr_result) {
			return false;
		}
		
		return true;
	}
	
	public function setSQLTableName($sql_table_name) {
		
		$this->sql_table_name = $sql_table_name;
	}
	
	public function getSQLTableName($sql_table_name) {
		
		if (!$this->sql_table_name) {
			$this->sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_ir_result');
		}
		
		return $this->sql_table_name;
	}
	
	public function getSQLColumns() {
		
		$arr_sql_columns_query = [];
		
		foreach ($this->arr_queries as $query_id => $str_query) {
			
			$arr_sql_columns_query[] = 'query_id_'.$query_id.' BOOLEAN';
		}
		
		return "
			type_id INT,
			object_id INT,
			collection_id SMALLINT,
			field_id INT,
			rank INT,
			weight INT,
			".implode(',', $arr_sql_columns_query).",
			PRIMARY KEY (type_id, object_id, collection_id, field_id)
		";
	}
	
	public function getSQLInsert() {
		
		if (!$this->arr_result) {
			return '';
		}
		
		$arr_sql = [];
		
		foreach ($this->arr_result as $identifier => $arr_result) {
			
			$arr_identifier = explode(':', $identifier);
			
			$collection_id = self::$arr_map_result_collection_id[$arr_identifier[2]]; 
			
			$arr_sql_columns_match = [];
		
			foreach ($this->arr_queries as $query_id => $str_query) {
				
				$arr_sql_columns_match[] = (in_array($query_id, $arr_result['matches']) ? 'TRUE' : 'FALSE');
			}

			$arr_sql[] = '('.(int)$arr_identifier[0].', '.(int)$arr_identifier[1].', '.(int)$collection_id.', '.(int)$arr_identifier[3].', '.(int)$arr_result['rank'].', '.(int)$arr_result['weight'].', '.implode(',', $arr_sql_columns_match).')';
		}
		
		$arr_sql_columns_query = [];
		
		foreach ($this->arr_queries as $query_id => $str_query) {
			
			$arr_sql_columns_query[] = 'query_id_'.$query_id;
		}

		$sql = "
			INSERT INTO ".$this->sql_table_name."
				(type_id, object_id, collection_id, field_id, rank, weight, ".implode(',', $arr_sql_columns_query).")
					VALUES
				".implode(',', $arr_sql)."
		";
		
		return $sql;
	}
	
	protected function storeResults($arr) {

		$res = DB::query("
			CREATE TEMPORARY TABLE IF NOT EXISTS ".$this->getSQLTableName()." (
				".$this->getSQLColumns()."
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
		");
	
		$res = DB::query("
			".$this->getSQLInsert()."
		");
	}
	
	static public function getCollectionID($object_description) {
		
		return self::$arr_map_collection_id[$object_description];
	}
}
