<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class QueryTypeObjectsInformationRetrieval {
	
	const QUERY_WILDCARD = 1;
	const QUERY_NEAR = 2;
	
	const COLLECTION_ID_OBJECT_VALUE = 1;
	const COLLECTION_ID_OBJECT_DESCRIPTION = 2;
	const COLLECTION_ID_OBJECT_SUB_DESCRIPTION = 3;
	
	protected $str_host = false;
	protected $arr_queries = [];
	protected $arr_selection_types_map = [];
	protected $arr_selection_types_objects = [];
	
	protected $arr_selection_weight = [];
	protected $arr_results = false;
	protected $sql_table_name = false;

	static protected $arr_map_result_collection_id = [
		'ov' => self::COLLECTION_ID_OBJECT_VALUE,
		'od' => self::COLLECTION_ID_OBJECT_DESCRIPTION,
		'osd' => self::COLLECTION_ID_OBJECT_SUB_DESCRIPTION
	];
	static protected $arr_map_result_collection_identifier = [
		'ov' => 'object_value',
		'od' => 'object_description',
		'osd' => 'object_sub_description'
	];

    public function __construct($str_host) {
		
		$this->str_host = $str_host;
    }
	
	public function setQuery($arr_queries, $mode = false) {
		
		$arr_queries = (!is_array($arr_queries) ? [$arr_queries] : $arr_queries);
		
		foreach ($arr_queries as $str_query) {
			
			if (substr($str_query, 0, 1) == '[' && substr($str_query, -1) == ']') {
				$this->arr_queries[] = substr($str_query, 1, -1);
			} else {
				$this->arr_queries[] = trim($str_query, '*').'*';	
			}
		}
	}
	
	public function setTypesSetSelection($arr_types_map) {
			
		$num_weight = 0;
		
		foreach (array_reverse($arr_types_map, true) as $type_id => $arr_type_map) {
			
			foreach (array_reverse($arr_type_map, true) as $element_identifier => $arr_select) {
				
				$num_weight++;
					
				$arr_element = explode('-', $element_identifier);
				
				if ($arr_element[0] == 'object') {
					
					if ($arr_element[1] == 'name') {
						
						$this->arr_selection_types_map[] = $type_id.':ov:';
						$this->arr_selection_weight[$type_id.':object_value:'] = $num_weight;
					}
				} else if ($arr_element[0] == 'object_description') {
						
					$object_description_id = $arr_element[1];
		
					$this->arr_selection_types_map[] = $type_id.':od:'.$object_description_id;
					$this->arr_selection_weight[$type_id.':object_descriptions:'.$object_description_id] = $num_weight;
					
				} else if ($arr_element[0] == 'object_sub_details') {

					$object_sub_details_id = $arr_element[1];
					
					$str_element = $arr_element[2];
					
					if ($str_element == 'object_sub_description') {
						
						$object_sub_description_id = $arr_element[3];
						
						$this->arr_selection_types_map[] = $type_id.':osd:'.$object_sub_description_id;
						$this->arr_selection_weight[$type_id.':object_sub_descriptions:'.$object_sub_description_id] = $num_weight;
					}
				}
			}
		}
	}
	
	public function setTypesObjectsSelection($arr_types_objects) {
		
		foreach ($arr_types_objects as $type_id => $arr_objects) {
			
			foreach ($arr_objects as $object_id) {
				
				$this->arr_selection_types_objects[] = $type_id.':'.$object_id.':';
			}
		}
	}
    	
	public function query() {

		$command = 'curl --no-buffer --silent --show-error -X POST "'.$this->str_host.'query/term/" -H  "accept: application/json" -H  "Content-Type: application/json" --data-binary @-';
		
		$process = new ProcessProgram($command);

		$arr_settings = [
			'queries' => $this->arr_queries,
			'selection_types_map' => $this->arr_selection_types_map,
			'selection_types_objects' => $this->arr_selection_types_objects,
			'settings' => [
				'offset' => 0,
				'limit' => 10000,
				'include_value' => false
			]
		];
		
		$process->writeInput(value2JSON($arr_settings));
		
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
	
	public function getResult() {
		
		if (!$this->arr_result) {
			return false;
		}
		
		$arr = [];
		
		foreach ($this->arr_result as $identifier => $arr_result) {
			
			$arr_identifier = explode(':', $identifier);
			
			$type_id = (int)$arr_identifier[0];
			$object_id = (int)$arr_identifier[1];

			$collection_identifier = self::$arr_map_result_collection_identifier[$arr_identifier[2]]; 
			$description_id = (int)$arr_identifier[3];
			
			$arr_details =& $arr[$type_id][$object_id];
			
			if (!$arr_details) {
				$arr_details = ['rank' => 0, 'score' => 0];
			}
			
			$num_weight_position = $this->arr_selection_weight[$type_id.':'.$collection_identifier.':'.$description_id];
			$num_weight_queries = 0;

			$count_queries = count($this->arr_queries) + 1;
								
			foreach ($this->arr_queries as $query_id => $str_query) {
				
				$count_queries--;
				
				if (!in_array($query_id, $arr_result['matches'])) {
					continue;
				}
				
				$num_weight_queries += $count_queries;
			}
			
			$num_score = (int)$arr_result['weight'] + $num_weight_position + $num_weight_queries;

			$arr_details['score'] += $num_score;
		}
		
		return $arr;
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
}
