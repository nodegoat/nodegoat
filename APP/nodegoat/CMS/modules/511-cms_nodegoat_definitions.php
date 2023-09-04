<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_TYPES', DATABASE_NODEGOAT_CONTENT.'.def_types');
DB::setTable('DEF_NODEGOAT_TYPE_DEFINITIONS', DATABASE_NODEGOAT_CONTENT.'.def_type_definitions');
DB::setTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS', DATABASE_NODEGOAT_CONTENT.'.def_type_object_descriptions');
DB::setTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS', DATABASE_NODEGOAT_CONTENT.'.def_type_object_sub_details');
DB::setTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS', DATABASE_NODEGOAT_CONTENT.'.def_type_object_sub_descriptions');
DB::setTable('DEF_NODEGOAT_TYPE_OBJECT_NAME_PATH', DATABASE_NODEGOAT_CONTENT.'.def_type_object_name_path');
DB::setTable('DEF_NODEGOAT_TYPE_OBJECT_SEARCH_PATH', DATABASE_NODEGOAT_CONTENT.'.def_type_object_search_path');

DB::setTable('DATA_NODEGOAT_TYPE_OBJECTS', DATABASE_NODEGOAT_CONTENT.'.data_type_objects');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_definitions');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_REFERENCES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_definitions_references');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_definitions_modules');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_subs');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_date');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_date_chronology');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_location_geometry');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_definitions');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS_REFERENCES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_definitions_references');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS_MODULES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_definitions_modules');

DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION', DATABASE_NODEGOAT_CONTENT.'.data_type_object_version');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION', DATABASE_NODEGOAT_CONTENT.'.data_type_object_definition_version');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_version');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_definition_version');

DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_definition_objects');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_definition_objects');

DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sources');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_definition_sources');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_sources');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_definition_sources');

DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULE_STATUS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_definitions_module_status');

DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES', DATABASE_NODEGOAT_CONTENT.'.data_type_object_analyses');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSIS_STATUS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_analysis_status');

DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_status');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_DISCUSSION', DATABASE_NODEGOAT_CONTENT.'.data_type_object_discussion');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK', DATABASE_NODEGOAT_CONTENT.'.data_type_object_lock');

DB::setTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE_PATH', DATABASE_NODEGOAT_CONTENT.'.cache_type_object_sub_date_path');
DB::setTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE', DATABASE_NODEGOAT_CONTENT.'.cache_type_object_sub_date');
DB::setTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_PATH', DATABASE_NODEGOAT_CONTENT.'.cache_type_object_sub_location_path');
DB::setTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION', DATABASE_NODEGOAT_CONTENT.'.cache_type_object_sub_location');

define('DIR_TYPE_OBJECT_MEDIA', DIR_UPLOAD.'media/');
define('DIR_HOME_TYPE_OBJECT_MEDIA', DIR_ROOT_STORAGE.DIR_HOME.DIR_TYPE_OBJECT_MEDIA);

class cms_nodegoat_definitions extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static $num_graph_analysis_timeout_status = (60 * 60 * 2);
	public static $num_information_retrieval_timeout_status = (60 * 60 * 2);
	
	public static function backupProperties() {
		return [
			'nodegoat' => [
				'label' => getLabel('lbl_backup_nodegoat'),
				'database' => DATABASE_NODEGOAT_CONTENT,
				'tables' => [],
				'download' => true
			]
		];
	}
	
	public static function jobProperties() {
		return [
			'runCleanupObjects' => [
				'label' => 'nodegoat '.getLabel('lbl_cleanup_objects'),
				'options' => false,
			],
			'runCleanupOrphans' => [
				'label' => 'nodegoat '.getLabel('lbl_cleanup_orphans'),
				'options' => function($arr_options) {
					return '<label>'.getLabel('lbl_model').'</label><input type="checkbox" name="options[model]" value="1"'.($arr_options['model'] ? ' checked="checked"' :'').' />'
						.'<label>'.getLabel('lbl_data').'</label><input type="checkbox" name="options[data]" value="1"'.($arr_options['data'] ? ' checked="checked"' :'').' />'
						.'<label>'.getLabel('lbl_media').'</label><input type="checkbox" name="options[media]" value="1"'.($arr_options['media'] ? ' checked="checked"' :'').' />';
				}
			],
			'buildTypeObjectCache' => [
				'label' => 'nodegoat '.getLabel('lbl_cache_objects').' ('.getLabel('lbl_reset').')',
				'options' => false,
			],
			'runTypeObjectCaching' => [
				'label' => 'nodegoat '.getLabel('lbl_cache_objects'),
				'options' => function($arr_options) {
					return '<label>'.getLabel('lbl_reset').'</label><input type="checkbox" name="options[reset]" value="1"'.($arr_options['reset'] ? ' checked="checked"' :'').' />';
				}
			],
			'runReversals' => [
				'label' => 'nodegoat '.getLabel('lbl_reversals'),
				'options' => false
			],
			'runReversalsSelection' => [
				'label' => 'nodegoat '.getLabel('lbl_reversals').' ('.getLabel('lbl_select').')',
				'options' => function($arr_options) {
					
					$arr_values = [];
					
					if ($arr_options['type_ids']) {
						
						$arr_types = StoreType::getTypes(false, $arr_options['type_ids'], StoreType::TYPE_CLASS_REVERSAL);
						
						$arr_values = [];
						
						foreach ($arr_types as $type_id => $arr_type) {
							$arr_values[$type_id] = Labels::parseTextVariables($arr_type['name']);
						}
					}

					return '<fieldset><ul>
						<li>
							<label>'.getLabel('lbl_reversals').'</label>
							<div>'.cms_general::createMultiSelect('options[type_ids]', 'y:cms_nodegoat_definitions:get_type-reversal', $arr_values, false, ['list' => true]).'</div>
						</li>
					</ul></fieldset>';
				}
			],
			'runGraphAnalysisService' => [
				'label' => 'nodegoat '.getLabel('lbl_analysis').' '.getLabel('lbl_service'),
				'service' => true,
				'options' => function($arr_options) {
					return '<fieldset><ul>
						<li><label>'.getLabel('lbl_server_host_port').'</label><input type="text" name="options[port]" value="'.$arr_options['port'].'" /></li>
					</ul></fieldset>';
				}
			],
			'runInformationRetrievalService' => [
				'label' => 'nodegoat '.getLabel('lbl_information_retrieval').' '.getLabel('lbl_service'),
				'service' => true,
				'options' => function($arr_options) {
					return '<fieldset><ul>
						<li><label>'.getLabel('lbl_server_host_port').'</label><input type="text" name="options[port]" value="'.$arr_options['port'].'" /></li>
					</ul></fieldset>';
				}
			],
			'buildInformationRetrievalIndex' => [
				'label' => 'nodegoat '.getLabel('lbl_information_retrieval').' Index ('.getLabel('lbl_reset').')',
				'options' => false,
			],
			'runInformationRetrievalIndexing' => [
				'label' => 'nodegoat '.getLabel('lbl_information_retrieval').' Index',
				'options' => function($arr_options) {
					return '<label>'.getLabel('lbl_reset').'</label><input type="checkbox" name="options[reset]" value="1"'.($arr_options['reset'] ? ' checked="checked"' :'').' />';
				}
			],
		];
	}
	
	public static function getSetConditionActions($type = false, $action = false) {
		
		$arr = [
			'background_color' => ['id' => 'background_color', 'name' => getLabel('lbl_background_color'), 'value' => ['color']],
			'text_emphasis' => ['id' => 'text_emphasis', 'name' => getLabel('lbl_text_emphasis'), 'value' => ['emphasis']],
			'text_color' => ['id' => 'text_color', 'name' => getLabel('lbl_text_color'), 'value' => ['color']],
			'limit_text' => ['id' => 'limit_text', 'name' => getLabel('lbl_limit').' '.getLabel('lbl_text'), 'value' => ['number', ['type' => 'value', 'info' => getLabel('inf_replace_text_value')]]],
			'add_text_prefix' => ['id' => 'add_text_prefix', 'name' => getLabel('lbl_prefix').' '.getLabel('lbl_text'), 'value' => ['value', ['type' => 'check', 'info' => getLabel('inf_override_default_or_previous')]]],
			'add_text_affix' => ['id' => 'add_text_affix', 'name' => getLabel('lbl_affix').' '.getLabel('lbl_text'), 'value' => ['value', ['type' => 'check', 'info' => getLabel('inf_override_default_or_previous')]]],
			'regex_replace' => ['id' => 'regex_replace', 'name' => getLabel('lbl_regular_expression'), 'value' => [['type' => 'regex', 'info' => getLabel('inf_regular_expression_replace')], ['type' => 'check', 'info' => getLabel('inf_override_default_or_previous')]]],
			'color' => ['id' => 'color', 'name' => getLabel('lbl_highlight_color'), 'value' => ['color', ['type' => 'check', 'info' => getLabel('inf_add_to_previous')]]],
			'weight' => ['id' => 'weight', 'name' => getLabel('lbl_weight').' ('.getLabel('lbl_multiply').')', 'value' => ['number', ['type' => 'number_use_object_description_id', 'info' => getLabel('lbl_multiply_with').' '.getLabel('lbl_object_description')], ['type' => 'number_use_object_analysis_id', 'info' => getLabel('lbl_multiply_with').' '.getLabel('lbl_analysis')], ['type' => 'check', 'info' => getLabel('inf_add_to_previous')]]],
			'remove' => ['id' => 'remove', 'name' => getLabel('lbl_remove').' '.getLabel('lbl_value'), 'value' => ['check']],
			'geometry_color' => ['id' => 'geometry_color', 'name' => getLabel('lbl_geometry').' '.getLabel('lbl_color'), 'value' => ['color', 'opacity']],
			'geometry_stroke_color' => ['id' => 'geometry_stroke_color', 'name' => getLabel('lbl_geometry').' '.getLabel('lbl_stroke_color'), 'value' => ['color', 'opacity']],
			'icon' => ['id' => 'icon', 'name' => getLabel('lbl_icon'), 'value' => ['image', ['type' => 'check', 'info' => getLabel('inf_add_to_previous')]]]
		];
		
		if ($type == 'object_name') {
			
			return ['background_color' => $arr['background_color'], 'text_emphasis' => $arr['text_emphasis'], 'text_color' => $arr['text_color'], 'limit_text' => $arr['limit_text'], 'add_text_prefix' => $arr['add_text_prefix'], 'add_text_affix' => $arr['add_text_affix'], 'regex_replace' => $arr['regex_replace']];
		} else if ($type == 'object_values') {
			
			return ['background_color' => $arr['background_color'], 'text_emphasis' => $arr['text_emphasis'], 'text_color' => $arr['text_color'], 'regex_replace' => $arr['regex_replace'], 'remove' => $arr['remove']];
		} else if ($type == 'object_nodes') {
			
			return ['color' => $arr['color'], 'weight' => $arr['weight'], 'geometry_color' => $arr['geometry_color'], 'geometry_stroke_color' => $arr['geometry_stroke_color'], 'icon' => $arr['icon']];
		} else if ($type == 'object_nodes_referencing') {
			
			return ['color' => $arr['color'], 'weight' => $arr['weight']];
		} else {
			
			return $arr;
		}
	}

	public static function cleanupOrphansModel() {
					
		$arr = [
			['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id'], 'clause' => 'type_id > 0'],
			['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id'], 'clause' => 'type_id > 0'],
			['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id'], 'clause' => 'type_id > 0'],
			['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'object_sub_details_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS'), 'id'], 'clause' => 'object_sub_details_id > 0'],
			
			['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_NAME_PATH'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id'], 'clause' => 'type_id > 0'],
			['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SEARCH_PATH'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id'], 'clause' => 'type_id > 0'],
		];
		
		DBFunctions::cleanupTables($arr);
	}
	
	public static function cleanupOrphansData() {
			
		$arr = [
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id'], 'clause' => 'type_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DISCUSSION'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']], // Would need additional check for Analysis ID and Project ID, same for DATA_NODEGOAT_TYPE_OBJECT_ANALYSIS_STATUS
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS'), 'object_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'id'], 'clause' => 'object_description_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_REFERENCES'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_REFERENCES'), 'object_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'id'], 'clause' => 'object_description_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULES'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULES'), 'object_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'id'], 'clause' => 'object_description_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULE_STATUS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULE_STATUS'), 'object_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'id'], 'clause' => 'object_description_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'object_sub_details_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS'), 'id'], 'clause' => 'object_sub_details_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS'), 'object_sub_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'id'], 'clause' => 'object_sub_description_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS_REFERENCES'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS_REFERENCES'), 'object_sub_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'id'], 'clause' => 'object_sub_description_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS_MODULES'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS_MODULES'), 'object_sub_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'id'], 'clause' => 'object_sub_description_id > 0'],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
			
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_REFERENCES'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS_REFERENCES'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true],
			['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES'), 'ref_object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id'], 'clause_not_empty' => true]
		];
		
		DBFunctions::cleanupTables($arr);
	}
	
	public static function cleanupOrphansMedia() {
		
		$arr_types = StoreType::getTypes();
		
		$arr_type_object_descriptions = StoreType::getTypesObjectValueTypeDescriptions('media', array_keys($arr_types));
		
		$arr_files_collect = [];
		$arr_object_description_ids = [];
		$arr_object_sub_description_ids = [];
		
		foreach ($arr_type_object_descriptions as $type_id => $arr_object_descriptions) {
			
			if ($arr_object_descriptions['object_descriptions']) {
				
				foreach ($arr_object_descriptions['object_descriptions'] as $object_description_id) {
					
					$arr_object_description_ids[] = $object_description_id;
				}
			}
			
			if ($arr_object_descriptions['object_sub_details']) {
				
				foreach ($arr_object_descriptions['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id) {
						
						$arr_object_sub_description_ids[] = $object_sub_description_id;
					}
				}
			}
		}
		
		if ($arr_object_description_ids) {
			
			$res = DB::query("SELECT ".StoreType::getValueTypeValue('media')."
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('media')." nodegoat_to_def
					WHERE
						nodegoat_to_def.object_description_id IN (".implode(',', $arr_object_description_ids).")
						AND nodegoat_to_def.".StoreType::getValueTypeValue('media')." != ''
						AND nodegoat_to_def.active = TRUE
			");
			
			while ($arr_row = $res->fetchRow()) {
				$arr_files_collect[$arr_row[0]] = $arr_row[0];
			}
		}
		
		if ($arr_object_sub_description_ids) {
			
			$res = DB::query("SELECT ".StoreType::getValueTypeValue('media')."
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('media')." nodegoat_tos_def
					WHERE
						nodegoat_tos_def.object_sub_description_id IN (".implode(',', $arr_object_sub_description_ids).")
						AND nodegoat_tos_def.".StoreType::getValueTypeValue('media')." != ''
						AND nodegoat_tos_def.active = TRUE
			");
			
			while ($arr_row = $res->fetchRow()) {
				$arr_files_collect[$arr_row[0]] = $arr_row[0];
			}
		}

		$iterator_files = new DirectoryIterator(DIR_HOME_TYPE_OBJECT_MEDIA);
		
		$arr_delete = [];
		
		foreach ($iterator_files as $file) {
				
			if (!$file->isFile()) {
				continue;
			}
			
			$str_file = $file->getFilename();
					
			if ($arr_files_collect[$str_file]) {
				continue;
			}
			
			$arr_delete[] = $str_file;
		}
				
		foreach ($arr_delete as $str_file) {
			
			FileStore::deleteFile(DIR_HOME_TYPE_OBJECT_MEDIA.$str_file);
		}
		
		msg('Cleaned up '.count($arr_delete).' files.', 'FILES', LOG_BOTH, arr2String($arr_delete, PHP_EOL));
	}
	
	// Caching
	
	public static function runTypeObjectCaching($arr_options = []) {
		
		$date_start = false;
		$date_end = false;
		
		if (!$arr_options['reset']) {
			
			$is_updated = FilterTypeObjects::getTypesUpdatedAfter($arr_options['date_executed']['previous'], [], true);
			
			if (!$is_updated) {
				return;
			}
			
			$date_start = $arr_options['date_executed']['previous'];
			$date_end = $arr_options['date_executed']['now'];
		}
		
		StoreTypeObjectsProcessing::cacheTypesObjectSubs($date_start, $date_end);
	}
	
	public static function buildTypeObjectCache($arr_options = []) {
		
		$arr_job = cms_jobs::getJob('cms_nodegoat_definitions', 'runTypeObjectCaching');
		
		$arr_job['reset'] = true;
				
		cms_jobs::runJob('cms_nodegoat_definitions', 'runTypeObjectCaching', 'reset', $arr_job);
	}

	// Reversal
	
	public static function runReversals($arr_options = []) {
		
		$is_updated = FilterTypeObjects::getTypesUpdatedAfter($arr_options['date_executed']['previous'], [], true);
		
		if (!$is_updated) {
			return;
		}
		
		if ($arr_options['type_ids']) {
			$arr_types = StoreType::getTypes(false, $arr_options['type_ids'], StoreType::TYPE_CLASS_REVERSAL);
		} else {
			$arr_types = StoreType::getTypes(false, false, StoreType::TYPE_CLASS_REVERSAL);
		}
		
		StoreTypeObjectsProcessing::setReversals($arr_types);
	}
	
	public static function runReversalsSelection($arr_options = []) {
		
		$arr_job = cms_jobs::getJob('cms_nodegoat_definitions', 'runReversals');
		
		$arr_job['type_ids'] = $arr_options['type_ids'];
				
		cms_jobs::runJob('cms_nodegoat_definitions', 'runReversals', false, $arr_job);
	}
	
	// Cleanup
	
	public static function runCleanupObjects($arr_options = []) {
		
		StoreTypeObjectsProcessing::cleanupTypesObjects();
	}
	
	public static function runCleanupOrphans($arr_options = []) {
		
		if ($arr_options['model']) {
			
			self::cleanupOrphansModel();
		}
		
		if ($arr_options['data']) {
			
			self::cleanupOrphansData();
		}
		
		if ($arr_options['media']) {
			
			self::cleanupOrphansMedia();
		}
	}
	
	// Graph analysis
	
	public static function runGraphAnalysisService($arr_options) {
				
		if (!$arr_options['port']) {
			error(getLabel('msg_missing_information'));
		}

		$process = new ProcessProgram(DIR_PROGRAMS_RUN.'graph_analysis --port '.(int)$arr_options['port']);
		
		$cleanup_id = Mediator::attach('cleanup', false, function() use ($process) {

			$process->close(true);
		});
		
		$num_time_status = 0;

		while (true) {
			
			Mediator::checkState(); // Check state of this service
			
			$process->checkOutput(true, true); // Check state of the process
			
			$str_error = $process->getError();
			
			if ($str_error !== '') {
				
				error(__METHOD__.' ERROR:'.PHP_EOL
					.strIndent($str_error),
				TROUBLE_NOTICE); // Make notice
			}
			
			$str_result = $process->getOutput();
			
			if ($str_result) {
				
				$str_separator = PHP_EOL;
				$str_line = strtok($str_result, $str_separator);

				while ($str_line !== false) {
					
					$arr_result = json_decode($str_line, true);
					
					if ($arr_result) { // JSON output
						
						$num_time = time();
						
						if (($num_time - $num_time_status) > static::$num_graph_analysis_timeout_status) {
							
							if ($arr_result['statistics']) {
								
								msg('Status:'.PHP_EOL
									.'	Jobs: total = '.num2String($arr_result['statistics']['jobs']).' timeouts = '.num2String($arr_result['statistics']['timeouts']),
								'GRAPH ANALYSIS'); // Provide status update and keep database connection alive
							}
				
							$num_time_status = $num_time;
						}
					} else {
					
						msg($str_line, 'GRAPH ANALYSIS');
					}
					
					$str_line = strtok($str_separator);
				}
			}
			
			if (!$process->isRunning(false)) {
				
				$process->close();
				
				Mediator::remove('cleanup', $cleanup_id);
				
				break;
			}
		}
	}
	
	// Information retrieval
	
	public static function runInformationRetrievalService($arr_options) {
				
		if (!$arr_options['port']) {
			error(getLabel('msg_missing_information'));
		}
		
		$path_store = DIR_ROOT_STORAGE.DIR_HOME.DIR_CMS.DIR_PRIVATE.'information_retrieval/';
		FileStore::makeDirectoryTree($path_store);
		
		$process = new ProcessProgram(DIR_PROGRAMS_RUN.'information_retrieval --port '.(int)$arr_options['port'].' --path "'.escapeshellcmd($path_store).'"');
		
		$cleanup_id = Mediator::attach('cleanup', false, function() use ($process) {

			$process->close(true);
		});
		
		$num_time_status = 0;

		while (true) {
			
			Mediator::checkState(); // Check state of this service
			
			$process->checkOutput(true, true); // Check state of the process
			
			$str_error = $process->getError();
			
			if ($str_error !== '') {
				
				error(__METHOD__.' ERROR:'.PHP_EOL
					.strIndent($str_error),
				TROUBLE_NOTICE); // Make notice
			}
			
			$str_result = $process->getOutput();
			
			if ($str_result) {
				
				$str_separator = PHP_EOL;
				$str_line = strtok($str_result, $str_separator);

				while ($str_line !== false) {
					
					$arr_result = json_decode($str_line, true);
					
					if ($arr_result) { // JSON output
						
						$num_time = time();
						
						if (($num_time - $num_time_status) > static::$num_information_retrieval_timeout_status) {
							
							if ($arr_result['statistics']) {
								
								msg('Status:'.PHP_EOL
									.'	Jobs: Total = '.num2String($arr_result['statistics']['jobs']),
								'INFO RETRIEVAL'); // Provide status update and keep database connection alive
							}
				
							$num_time_status = $num_time;
						}
					} else {
					
						msg($str_line, 'INFO RETRIEVAL');
					}
					
					$str_line = strtok($str_separator);
				}
			}
			
			if (!$process->isRunning(false)) {
				
				$process->close();
				
				Mediator::remove('cleanup', $cleanup_id);
				
				break;
			}
		}
	}
	
	public static function runInformationRetrievalIndexing($arr_options = []) {
		
		$date_start = false;
		$date_end = false;
		
		if (!$arr_options['reset']) {
			
			$is_updated = FilterTypeObjects::getTypesUpdatedAfter($arr_options['date_executed']['previous'], [], true);
			
			if (!$is_updated) {
				return;
			}
			
			$date_start = $arr_options['date_executed']['previous'];
			$date_end = $arr_options['date_executed']['now'];
		}
			
		$str_host = self::getInformationRetrievalHost();

		$ir = new IndexTypeObjectsInformationRetrieval($str_host);
		$ir->setMode(($arr_options['reset'] ? IndexTypeObjectsInformationRetrieval::MODE_OVERWRITE : IndexTypeObjectsInformationRetrieval::MODE_UPDATE));
		
		if ($date_end) {
			$ir->setObjectsByStatus($date_start, $date_end);
		}
		
		$ir->build();
	}
	
	public static function buildInformationRetrievalIndex($arr_options = []) {
		
		$arr_job = cms_jobs::getJob('cms_nodegoat_definitions', 'runInformationRetrievalIndexing');
		
		$arr_job['reset'] = true;
				
		cms_jobs::runJob('cms_nodegoat_definitions', 'runInformationRetrievalIndexing', false, $arr_job);
	}
		
	public static function getInformationRetrievalHost() {
		
		$str_host = Settings::get('information_retrieval_service', 'host');
		
		if ($str_host == 'service') {
			
			$arr_job = self::checkInformationRetrievalService();
			
			if (!$arr_job) {
				error(getLabel('msg_information_retrieval_no_service'));
			}
			
			$str_host = $arr_job['host'];
		}
		
		return $str_host;
	}
	
	public static function checkInformationRetrievalService() {
		
		$arr_job = cms_jobs::getJob('cms_nodegoat_definitions', 'runInformationRetrievalService');
		
		if ($arr_job && $arr_job['process_id']) {
			
			$arr_job['host'] = 'http://127.0.0.1:'.$arr_job['port'].'/';
			
			return $arr_job;
		} else {
			return false;
		}	
	}
}
