<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class IndexTypeObjectsInformationRetrieval {
	
	const MODE_UPDATE = 1;
	const MODE_OVERWRITE = 2;
	
	protected $type_id = false;
	protected $str_host = false;
	
	protected $resource = false;
	protected $table_name_objects = false;
	protected $table_name_objects_deleted = false;
	
	protected $mode = self::MODE_UPDATE;
	protected $use_table_memory = false;
	
	protected static $num_buffer_size = 100000;
	
    public function __construct($str_host) {
		
		$this->str_host = $str_host;
    }
    
	protected function openInputResource() {
		
		$this->resource = fopen('php://temp/maxmemory:'.(100 * BYTE_MULTIPLIER * BYTE_MULTIPLIER), 'w'); // Keep resource in memory until it reaches 100MB, otherwise create a temporary file
	}
	
	protected function closeInputResource() {
		
		fclose($this->resource);
	}
	
	protected function getProcess($do, $arr_options) {
		
		$str_options = '';
		
		foreach ($arr_options as $key => $value) {
			
			$str_options .= '/'.$key.'/'.$value;
		}
		
		$command = 'curl --no-buffer --silent --show-error -X POST "'.$this->str_host.'index/'.$do.($str_options ?: '/').'/overwrite/'.($this->mode == static::MODE_OVERWRITE ? '1' : '0').'" -H  "accept: application/json" -H  "Content-Type: application/json" -H  "Authorization: bearer '.Settings::get('graph_database', 'token').'" --data-binary @-';

		$process = new ProcessProgram($command);
		
		return $process;
	}
	
	public function setMode($mode) {
		
		$this->mode = $mode;
	}

	public function setObjectsByStatus($date_after, $date_to) {
		
		$this->table_name_objects = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_changed');
		$this->table_name_objects_deleted = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_deleted');
		//$this->use_table_memory = true; Needs MySQL 8.0

		$sql_query = "
			SELECT DISTINCT
				nodegoat_to.id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status ON (nodegoat_to_status.object_id = nodegoat_to.id AND nodegoat_to_status.date > '".DBFunctions::str2Date($date_after)."' AND nodegoat_to_status.date <= '".DBFunctions::str2Date($date_to)."')
				WHERE ".GenerateTypeObjects::generateVersioning('active', 'object', 'nodegoat_to')."
		";
		
		$sql_query_deleted = "
			SELECT DISTINCT
				nodegoat_to_status.object_id AS id,
				(SELECT
					nodegoat_to.type_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					WHERE nodegoat_to.id = nodegoat_to_status.object_id
					LIMIT 1
				) AS type_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status
				WHERE nodegoat_to_status.date > '".DBFunctions::str2Date($date_after)."' AND nodegoat_to_status.date <= '".DBFunctions::str2Date($date_to)."'
					AND NOT EXISTS (SELECT TRUE
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						WHERE nodegoat_to.id = nodegoat_to_status.object_id
							AND ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to')."
					)
		";
		
		$sql = "
			-- Select all updated objects.
			
			CREATE TEMPORARY TABLE ".$this->table_name_objects." (
				id INT,
					PRIMARY KEY (id)
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
				".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query.")"
				:
				"; INSERT INTO ".$this->table_name_objects."
					(".$sql_query.")
				")."
			;
			
			-- Select all deleted objects.
			
			CREATE TEMPORARY TABLE ".$this->table_name_objects_deleted." (
				id INT,
				type_id INT,
					PRIMARY KEY (id)
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
				".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query_deleted.")"
				:
				"; INSERT INTO ".$this->table_name_objects_deleted."
					(".$sql_query_deleted.")
				")."
			;
		";
		
		$arr_res = DB::queryMulti($sql);
		
		$res = (DB::ENGINE_IS_MYSQL ? $arr_res[1] : $arr_res[3]);
		
		if (!$res->getAffectedRowCount()) {
			
			$this->table_name_objects_deleted = false;
		}
	}
	
	public function build() {
		
		$do_message = ($this->mode == static::MODE_OVERWRITE);
		
		if ($do_message) {
			status('Collecting data.', 'INFO RETRIEVAL', false, ['persist' => true]);
		}
		
		if (DB::ENGINE_IS_MYSQL) {
			
			$arr_res = DB::queryMulti("
				SELECT @@SESSION.group_concat_max_len;
				SET SESSION group_concat_max_len = 100000000;
			");
			
			$num_temp = $arr_res[0]->fetchRow()[0];
		}
		
		$process_index_object_values = $this->indexTypesObjectsValues();
		$process_index_object_descriptions = $this->indexTypesObjectsDescriptions();
		$process_index_object_sub_descriptions = $this->indexTypesObjectsSubsDescriptions();
		
		$process_delete_objects = $this->deleteTypesObjects();
		
		if (DB::ENGINE_IS_MYSQL) {
			
			DB::query("SET SESSION group_concat_max_len = ".$num_temp);
		}
		
		if ($do_message) {
			status('Creating index.', 'INFO RETRIEVAL', false, ['persist' => true]);
		}
		
		$arr_error = [];
		$arr_result = [];
		
		if ($process_index_object_values) {
				
			$process_index_object_values->checkOutput(true, true);

			$str_error = $process_index_object_values->getError();
			if ($str_error !== '') {
				$arr_error[] = $str_error;
			}
			$str_result = $process_index_object_values->getOutput();
			$arr_result['object_values'] = ($str_result ? json_decode($str_result, true) : false);
			
			$process_index_object_values->close();
		}

		if ($process_index_object_descriptions) {
			
			$process_index_object_descriptions->checkOutput(true, true);
			
			$str_error = $process_index_object_descriptions->getError();
			if ($str_error !== '') {
				$arr_error[] = $str_error;
			}
			$str_result = $process_index_object_descriptions->getOutput();
			$arr_result['object_descriptions'] = ($str_result ? json_decode($str_result, true) : false);
			
			$process_index_object_descriptions->close();
		}
		
		if ($process_index_object_sub_descriptions) {
			
			$process_index_object_sub_descriptions->checkOutput(true, true);

			$str_error = $process_index_object_sub_descriptions->getError();
			if ($str_error !== '') {
				$arr_error[] = $str_error;
			}
			$str_result = $process_index_object_sub_descriptions->getOutput();
			$arr_result['object_sub_descriptions'] = ($str_result ? json_decode($str_result, true) : false);
			
			$process_index_object_sub_descriptions->close();
		}
		
		if ($process_delete_objects) {
			
			$process_delete_objects->checkOutput(true, true);

			$str_error = $process_delete_objects->getError();
			if ($str_error !== '') {
				$arr_error[] = $str_error;
			}
			$str_result = $process_delete_objects->getOutput();
			$arr_result['delete'] = ($str_result ? json_decode($str_result, true) : false);
			
			$process_delete_objects->close();
		}
				
		if ($arr_error) {
			error($arr_error, TROUBLE_ERROR, LOG_BOTH, $arr_result);
		}
		
		if ($do_message) {
			msg($arr_result);
		}
	}
	
	public function indexTypesObjectsValues() {
		
		// Object values

		$table_name_temp = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp');
		
		$sql_query = "SELECT
			nodegoat_to.type_id, nodegoat_to.id, nodegoat_to.name
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				".($this->table_name_objects ? "JOIN ".$this->table_name_objects." nodegoat_to_changed ON (nodegoat_to_changed.id = nodegoat_to.id)" : "")."
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (nodegoat_t.id = nodegoat_to.type_id AND nodegoat_t.use_object_name = TRUE)
			WHERE ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to')."
			GROUP BY nodegoat_to.type_id, nodegoat_to.id, nodegoat_to.version
		";
		
		$sql_drop = "DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_temp;
		
		$arr_res = DB::queryMulti("	
			CREATE TEMPORARY TABLE ".$table_name_temp." (
				type_id INT,
				id INT,
				name VARCHAR(255)
			) ".DBFunctions::sqlTableOptions(($this->use_table_memory ? DBFunctions::TABLE_OPTION_MEMORY : false))."
				".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query.")"
				:
				"; INSERT INTO ".$table_name_temp."
					(".$sql_query.")
				")."
			;
		");
		
		$res = (DB::ENGINE_IS_MYSQL ? $arr_res[0] : $arr_res[1]);
		
		if (!$res->getAffectedRowCount()) {
			
			DB::query($sql_drop);
			return false;
		}
		
		$process = $this->getProcess('build', ['collection' => 'object_values']);
		
		$count = 0;
		$stmt = DB::prepare("SELECT * FROM ".$table_name_temp." LIMIT ".DBStatement::assign('offset', 'i').", ".static::$num_buffer_size);
		
		while (true) {
			
			$stmt->bindParameters(['offset' => $count]);
			$res = $stmt->execute();

			if (!$res->getRowCount()) {
				break;
			}
			
			while ($arr_row = $res->fetchRow()) {
				
				if (!$arr_row[2]) {
					continue;
				}

				$process->writeInput($arr_row[0].'$|$'.$arr_row[1].'$|$$|$'.$arr_row[2].PHP_EOL);
			}
			
			$count += static::$num_buffer_size;
		}

		$process->closeInput();
		
		DB::query($sql_drop);
		
		return $process;
	}
		
	public function indexTypesObjectsDescriptions() {

		// Object descriptions
		
		$table_name_temp = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp');
		$arr_sql_type_value = static::formatToSQLTypeValue('nodegoat_to_des');

		$sql_query = "SELECT
			nodegoat_to.type_id,
			nodegoat_to.id,
			nodegoat_to_def.object_description_id,
			".DBFunctions::sqlImplode($arr_sql_type_value['select'], ', ')." AS value
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				".($this->table_name_objects ? "JOIN ".$this->table_name_objects." nodegoat_to_changed ON (nodegoat_to_changed.id = nodegoat_to.id)" : "")."
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS')." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to.id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to_def').")
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des ON (nodegoat_to_des.id = nodegoat_to_def.object_description_id AND ".$arr_sql_type_value['where'].")
			WHERE ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to')."
			GROUP BY nodegoat_to.type_id, nodegoat_to.id, nodegoat_to_def.object_description_id
		";
		
		$sql_drop = "DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_temp;

		$arr_res = DB::queryMulti("	
			CREATE TEMPORARY TABLE ".$table_name_temp." (
				type_id INT,
				id INT,
				object_description_id INT,
				value LONGTEXT
			) ".DBFunctions::sqlTableOptions(($this->use_table_memory ? DBFunctions::TABLE_OPTION_MEMORY : false))."
				".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query.")"
				:
				"; INSERT INTO ".$table_name_temp."
					(".$sql_query.")
				")."
			;
		");
		
		$res = (DB::ENGINE_IS_MYSQL ? $arr_res[0] : $arr_res[1]);
		
		if (!$res->getAffectedRowCount()) {
			
			DB::query($sql_drop);
			return false;
		}
		
		$process = $this->getProcess('build', ['collection' => 'object_descriptions']);
		
		$count = 0;
		$stmt = DB::prepare("SELECT * FROM ".$table_name_temp." LIMIT ".DBStatement::assign('offset', 'i').", ".static::$num_buffer_size);
		
		while (true) {
			
			$stmt->bindParameters(['offset' => $count]);
			$res = $stmt->execute();

			if (!$res->getRowCount()) {
				break;
			}
			
			while ($arr_row = $res->fetchRow()) {
				
				if (!$arr_row[3]) {
					continue;
				}

				$process->writeInput($arr_row[0].'$|$'.$arr_row[1].'$|$'.$arr_row[2].'$|$'.str_replace(PHP_EOL, ' ', $arr_row[3]).PHP_EOL);
			}
			
			$count += static::$num_buffer_size;
		}
		
		$process->closeInput();
		
		DB::query($sql_drop);
		
		return $process;
	}
		
	public function indexTypesObjectsSubsDescriptions() {
		
		// Sub-Object descriptions

		$table_name_temp = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp');
		$arr_sql_type_value = static::formatToSQLTypeValue('nodegoat_tos_des');
		
		$sql_query = "SELECT
			nodegoat_to.type_id,
			nodegoat_to.id,
			nodegoat_tos_def.object_sub_id,
			nodegoat_tos_def.object_sub_description_id,
			".DBFunctions::sqlImplode($arr_sql_type_value['select'], ', ')." AS value
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				".($this->table_name_objects ? "JOIN ".$this->table_name_objects." nodegoat_to_changed ON (nodegoat_to_changed.id = nodegoat_to.id)" : "")."
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to.id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_tos').")
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS')." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_tos_def').")
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos.object_sub_details_id) 
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_tos_det.id AND nodegoat_tos_des.id = nodegoat_tos_def.object_sub_description_id AND ".$arr_sql_type_value['where'].")
			WHERE ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to')."
			GROUP BY nodegoat_to.type_id, nodegoat_to.id, nodegoat_tos_def.object_sub_id, nodegoat_tos_def.object_sub_description_id
		";
		
		$sql_drop = "DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_temp;

		$arr_res = DB::queryMulti("	
			CREATE TEMPORARY TABLE ".$table_name_temp." (
				type_id INT,
				id INT,
				object_sub_id INT,
				object_sub_description_id INT,
				value LONGTEXT
			) ".DBFunctions::sqlTableOptions(($this->use_table_memory ? DBFunctions::TABLE_OPTION_MEMORY : false))."
				".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query.")"
				:
				"; INSERT INTO ".$table_name_temp."
					(".$sql_query.")
				")."
			;
		");
		
		$res = (DB::ENGINE_IS_MYSQL ? $arr_res[0] : $arr_res[1]);
		
		if (!$res->getAffectedRowCount()) {
			
			DB::query($sql_drop);
			return false;
		}
		
		$process = $this->getProcess('build', ['collection' => 'object_sub_descriptions']);
		
		$count = 0;
		$stmt = DB::prepare("SELECT * FROM ".$table_name_temp." LIMIT ".DBStatement::assign('offset', 'i').", ".static::$num_buffer_size);
		
		while (true) {
			
			$stmt->bindParameters(['offset' => $count]);
			$res = $stmt->execute();

			if (!$res->getRowCount()) {
				break;
			}
			
			while ($arr_row = $res->fetchRow()) {
			
				if (!$arr_row[4]) {
					continue;
				}

				$process->writeInput($arr_row[0].'$|$'.$arr_row[1].'$|$'.$arr_row[2].'_'.$arr_row[3].'$|$'.str_replace(PHP_EOL, ' ', $arr_row[4]).PHP_EOL);
			}
			
			$count += static::$num_buffer_size;
		}

		$process->closeInput();
		
		DB::query($sql_drop);
		
		return $process;
	}
	
	public function deleteTypesObjects() {
		
		// Object values
		
		if (!$this->table_name_objects_deleted) {
			return false;
		}
		
		$process = $this->getProcess('delete', ['collection' => 'object_values+object_descriptions+object_sub_descriptions']);
		
		$count = 0;
		$stmt = DB::prepare("SELECT type_id, id FROM ".$this->table_name_objects_deleted." LIMIT ".DBStatement::assign('offset', 'i').", ".static::$num_buffer_size);
		
		while (true) {
			
			$stmt->bindParameters(['offset' => $count]);
			$res = $stmt->execute();

			if (!$res->getRowCount()) {
				break;
			}
			
			while ($arr_row = $res->fetchRow()) {
				
				$process->writeInput($arr_row[0].'$|$'.$arr_row[1].PHP_EOL);
			}
			
			$count += static::$num_buffer_size;
		}

		$process->closeInput();
		
		return $process;
	}
	
	protected static function formatToSQLTypeValue($sql_table) {
		
		$arr_value_types = StoreType::getValueTypes();
		$purpose = 'search';
		
		$arr_sql_select = [];
		$arr_sql_where = [];

		foreach ($arr_value_types as $value_type => $arr_value_type) {
			
			$arr_value_type_purpose = ($arr_value_type['purpose'] ? $arr_value_type['purpose'][$purpose] : $arr_value_type);
			
			if ($arr_value_type_purpose['table'] != '') { // Not a text value table
				continue;
			}
			
			if ($arr_value_type_purpose['value'] == 'object_description') { // Not applicable
				continue;
			}
			
			$arr_sql_select[] = "WHEN '".$value_type."' THEN ".$arr_value_type_purpose['value'];
			$arr_sql_where[] = "'".$value_type."'";
		}
		
		return [
			'select' => 'CASE '.$sql_table.'.value_type_base
				'.implode(' ', $arr_sql_select).'
			END',
			'where' => $sql_table.'.value_type_base IN (
				'.implode(',', $arr_sql_where).'
			)'
		];
	}
}
