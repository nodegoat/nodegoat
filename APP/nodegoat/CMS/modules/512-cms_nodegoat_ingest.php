<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS', DB::$database_home.'.def_nodegoat_pattern_type_object_pairs');

DB::setTable('DEF_NODEGOAT_IMPORT_TEMPLATES', DB::$database_home.'.def_nodegoat_import_templates');
DB::setTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS', DB::$database_home.'.def_nodegoat_import_template_columns');
DB::setTable('DEF_NODEGOAT_IMPORT_FILES', DB::$database_home.'.def_nodegoat_import_files');
DB::setTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG', DB::$database_home.'.data_nodegoat_import_template_log');

DB::setTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES', DB::$database_home.'.def_nodegoat_linked_data_resources');
DB::setTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES', DB::$database_home.'.def_nodegoat_linked_data_resource_values');
DB::setTable('DEF_NODEGOAT_LINKED_DATA_CONVERSIONS', DB::$database_home.'.def_nodegoat_linked_data_conversions');

define('DIR_TYPE_INGEST', DIR_UPLOAD.'ingest/');
define('DIR_HOME_TYPE_INGEST', DIR_ROOT_STORAGE.DIR_HOME.DIR_TYPE_INGEST);
define('DIR_TYPE_IMPORT', DIR_UPLOAD.'import/');
define('DIR_HOME_TYPE_IMPORT', DIR_ROOT_STORAGE.DIR_HOME.DIR_TYPE_IMPORT);

class cms_nodegoat_ingest extends base_module {
	
	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function jobProperties() {
		return [
			'cleanupImportTemplateLogs' => [
				'label' => 'nodegoat '.getLabel('lbl_import_cleanup_import_template_logs'),
				'options' => function($arr_options) {
					
					$arr_units = StoreIngestFile::getTemplateLogOptions();
					
					return '<label>'.getLabel('lbl_age').'</label><input type="text" name="options[age_amount]" value="'.$arr_options['age_amount'].'" />'
						.'<select name="options[age_unit]">'.cms_general::createDropdown($arr_units, $arr_options['age_unit']).'</select>';
				}
			]
		];
	}
	
	public static function webServiceProperties() {
		return [
			'WebServiceTaskIngestSource' => [
				'passkey' => true
			]
		];
	}

	public static function cleanupImportTemplateLogs($arr_options = []) {

		if ($arr_options['age_amount'] && $arr_options['age_unit']) {
			
			$num_minutes = $arr_options['age_amount'] * $arr_options['age_unit'];
			
			StoreIngestFile::cleanupTemplateLogs($num_minutes);
		} else {
			
			error(getLabel('msg_missing_information'));
		}
	}
}
