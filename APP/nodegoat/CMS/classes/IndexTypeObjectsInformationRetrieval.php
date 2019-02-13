<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class IndexTypeObjectsInformationRetrieval {
	
	protected $type_id = false;
	protected $str_host = false;
	
	protected $resource = false;
	
    public function __construct($str_host) {
		
		$this->str_host = $str_host;
    }
    
	protected function openInputResource() {
		
		$this->resource = fopen('php://temp/maxmemory:'.(100 * 1024 * 1024), 'w'); // Keep resource in memory until it reaches 100MB, otherwise create a temporary file
	}
	
	protected function closeInputResource() {
		
		fclose($this->resource);
	}

	public function build() {
		
		status('Collecting data.', 'INFO RETRIEVAL', false, ['persist' => true]);
		
		$process_index_object_values = $this->indexObjectValues();
		$process_index_object_descriptions = $this->indexObjectDescriptions();
		$process_index_object_sub_descriptions = $this->indexObjectSubDescriptions();
		
		status('Creating index.', 'INFO RETRIEVAL', false, ['persist' => true]);
		
		$process_index_object_values->checkOutput(true, true);

		$str_error = $process_index_object_values->getError();
		$str_result = $process_index_object_values->getOutput();
		
		$process_index_object_values->close();

		$process_index_object_descriptions->checkOutput(true, true);

		$str_error .= $process_index_object_descriptions->getError();
		$str_result .= $process_index_object_descriptions->getOutput();
		
		$process_index_object_descriptions->close();
		
		$process_index_object_sub_descriptions->checkOutput(true, true);

		$str_error .= $process_index_object_sub_descriptions->getError();
		$str_result .= $process_index_object_sub_descriptions->getOutput();
		
		$process_index_object_sub_descriptions->close();
				
		if ($str_error !== '') {

			error($str_error, TROUBLE_ERROR, LOG_BOTH, $str_result);
		}
		
		msg($str_result);
	}
	
	public function indexObjectValues() {
		
		// Object values
			
		$command = 'curl --no-buffer --silent --show-error -X POST "'.$this->str_host.'index/build/collection/object_values" -H  "accept: application/json" -H  "Content-Type: application/json" --data-binary @-';
		
		$process = new ProcessProgram($command);
		
		$arr_select = DBFunctions::bulkSelect("SELECT
			nodegoat_to.type_id, nodegoat_to.id, nodegoat_to.name
				FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.type_id = nodegoat_t.id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to').")
			WHERE nodegoat_t.use_object_name = TRUE
			GROUP BY nodegoat_to.type_id, nodegoat_to.id, nodegoat_to.version
		");

		foreach ($arr_select as $arr_row) {
			
			if (!$arr_row[2]) {
				continue;
			}

			$process->writeInput($arr_row[0].'$|$'.$arr_row[1].'$|$$|$'.$arr_row[2].PHP_EOL);
		}

		$process->closeInput();
		
		return $process;
	}
		
	public function indexObjectDescriptions() {

		// Object descriptions
		
		$command = 'curl --no-buffer --silent --show-error -X POST "'.$this->str_host.'index/build/collection/object_descriptions" -H  "accept: application/json" -H  "Content-Type: application/json" --data-binary @-';
		
		$process = new ProcessProgram($command);

		$arr_select = DBFunctions::bulkSelect("SELECT
			nodegoat_to.type_id,
			nodegoat_to.id,
			nodegoat_to_def.object_description_id,
			".DBFunctions::sqlImplode(self::formatToSQLSelectTypeValue('nodegoat_to_des'), ', ')."
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.type_id = nodegoat_to_des.type_id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to').")
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS')." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to.id AND nodegoat_to_def.object_description_id = nodegoat_to_des.id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to_def').")
			GROUP BY nodegoat_to.type_id, nodegoat_to.id, nodegoat_to_def.object_description_id
		");

		foreach ($arr_select as $arr_row) {
			
			if (!$arr_row[3]) {
				continue;
			}

			$process->writeInput($arr_row[0].'$|$'.$arr_row[1].'$|$'.$arr_row[2].'$|$'.str_replace(PHP_EOL, ' ', $arr_row[3]).PHP_EOL);
		}
		
		$process->closeInput();
		
		return $process;
	}
		
	public function indexObjectSubDescriptions() {
		
		// Sub-Object descriptions
		
		$command = 'curl --no-buffer --silent --show-error -X POST "'.$this->str_host.'index/build/collection/object_sub_descriptions" -H  "accept: application/json" -H  "Content-Type: application/json" --data-binary @-';
		
		$process = new ProcessProgram($command);
		
		$arr_select = DBFunctions::bulkSelect("SELECT
			nodegoat_to.type_id,
			nodegoat_to.id,
			nodegoat_tos_def.object_sub_id,
			nodegoat_tos_def.object_sub_description_id,
			".DBFunctions::sqlImplode(self::formatToSQLSelectTypeValue('nodegoat_tos_des'), ', ')."
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos_des.object_sub_details_id)
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.type_id = nodegoat_tos_det.type_id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_to').")
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to.id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_tos').")
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS')." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def.object_sub_description_id = nodegoat_tos_des.id AND ".GenerateTypeObjects::generateVersioning(false, 'search', 'nodegoat_tos_def').")
			GROUP BY nodegoat_to.type_id, nodegoat_to.id, nodegoat_tos_def.object_sub_id, nodegoat_tos_def.object_sub_description_id
		");

		foreach ($arr_select as $arr_row) {
			
			if (!$arr_row[4]) {
				continue;
			}

			$process->writeInput($arr_row[0].'$|$'.$arr_row[1].'$|$'.$arr_row[2].'_'.$arr_row[3].'$|$'.str_replace(PHP_EOL, ' ', $arr_row[4]).PHP_EOL);
		}

		$process->closeInput();
		
		return $process;
	}
	
	protected static function formatToSQLSelectTypeValue($sql_table) {
		
		$arr_value_types = StoreType::getValueTypes();
		$purpose = 'search';
		
		$arr_sql = [];

		foreach ($arr_value_types as $value_type => $arr_value_type) {
			
			$arr_value_type_purpose = ($arr_value_type['purpose'] ? $arr_value_type['purpose'][$purpose] : $arr_value_type);
			
			if ($arr_value_type_purpose['table'] != '') { // Not a text value table
				continue;
			}
			
			if ($arr_value_type_purpose['value'] == 'object_description') { // Not applicable
				continue;
			}
			
			$arr_sql[] = "WHEN '".$value_type."' THEN ".$arr_value_type_purpose['value'];
		}
		
		return 'CASE '.$sql_table.'.value_type_base
			'.implode(' ', $arr_sql).'
		END';
	}
}
