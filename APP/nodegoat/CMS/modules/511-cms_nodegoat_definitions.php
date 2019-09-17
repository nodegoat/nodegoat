<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
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
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_subs');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_date');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_date_chronology');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_location_geometry');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_sub_definitions');
DB::setTable('DATA_NODEGOAT_TYPE_OBJECT_FILTERS', DATABASE_NODEGOAT_CONTENT.'.data_type_object_filters');

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
				'options' => function($options) {
					return '<label>'.getLabel('lbl_descriptions').'</label><input type="checkbox" name="options[descriptions]" value="1"'.($options['descriptions'] ? ' checked="checked"' :'').' />'
						.'<label>'.getLabel('lbl_data').'</label><input type="checkbox" name="options[data]" value="1"'.($options['data'] ? ' checked="checked"' :'').' />';
				}
			],
			'buildTypeObjectCaching' => [
				'label' => 'nodegoat '.getLabel('lbl_cache_objects').' ('.getLabel('lbl_reset').')',
				'options' => false,
			],
			'runTypeObjectCaching' => [
				'label' => 'nodegoat '.getLabel('lbl_cache_objects'),
				'options' => function($options) {
					return '<label>'.getLabel('lbl_reset').'</label><input type="checkbox" name="options[reset]" value="1"'.($options['reset'] ? ' checked="checked"' :'').' />';
				}
			],
			'runReversals' => [
				'label' => 'nodegoat '.getLabel('lbl_reversals'),
				'options' => false
			],
			'runReversalsSelection' => [
				'label' => 'nodegoat '.getLabel('lbl_reversals').' ('.getLabel('lbl_select').')',
				'options' => function($options) {
					
					$arr_values = [];
					
					if ($options['type_ids']) {
						
						$arr_types = StoreType::getTypes($options['type_ids'], 'reversal');
						
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
				'options' => function($options) {
					return '<fieldset><ul>
						<li><label>'.getLabel('lbl_server_host_port').'</label><input type="text" name="options[port]" value="'.$options['port'].'" /></li>
					</ul></fieldset>';
				}
			],
			'runInformationRetrievalService' => [
				'label' => 'nodegoat '.getLabel('lbl_information_retrieval').' '.getLabel('lbl_service'),
				'service' => true,
				'options' => function($options) {
					return '<fieldset><ul>
						<li><label>'.getLabel('lbl_server_host_port').'</label><input type="text" name="options[port]" value="'.$options['port'].'" /></li>
					</ul></fieldset>';
				}
			],
			'buildInformationRetrievalIndex' => [
				'label' => 'nodegoat '.getLabel('lbl_information_retrieval').' Index (Reset)',
				'options' => false,
			]
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

	public static function cleanupOrphans($what = 'data') {
		
		 if ($what == 'descriptions') {
			
			$arr = [
				['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id']],
				['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id']],
				['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id']],
				['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'object_sub_details_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS'), 'id']],
				
				['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_NAME_PATH'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id']],
				['delete' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SEARCH_PATH'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id']],
			];
		} else if ($what == 'data') {
			
			$arr = [
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'type_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPES'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DISCUSSION'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_FILTERS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS'), 'object_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').'_references', 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').'_references', 'object_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'object_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'object_sub_details_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS'), 'object_sub_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').'_references', 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').'_references', 'object_sub_description_id'], 'test' => [DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES'), 'object_sub_id'], 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'id']],
				
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES'), 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').'_references', 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS'), 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES'), 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES'), 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').'_references', 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS'), 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']],
				['delete' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES'), 'ref_object_id'], 'value' => true, 'test' => [DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'id']]
			];
		}
		
		DBFunctions::cleanupTables($arr);
	}
	
	// Caching
	
	public static function runTypeObjectCaching($arr_options = []) {
		
		if (!$arr_options['reset']) {
			
			$is_updated = FilterTypeObjects::getTypesUpdatedAfter($arr_options['date_executed']['previous'], [], true);
			
			if (!$is_updated) {
				return;
			}
			
			StoreTypeObjectsProcessing::cacheTypeObjectSubs($arr_options['date_executed']['previous'], $arr_options['date_executed']['now']);
			
			return;
		}
			
		StoreTypeObjectsProcessing::cacheTypeObjectSubs();
	}
	
	public static function buildTypeObjectCaching($arr_options = []) {
		
		$arr_job = cms_jobs::getJob('cms_nodegoat_definitions', 'runTypeObjectCaching');
		
		$arr_job['reset'] = true;
				
		cms_jobs::runJob('cms_nodegoat_definitions', 'runTypeObjectCaching', false, $arr_job);
	}

	// Reversal
	
	public static function runReversals($arr_options = []) {
		
		$is_updated = FilterTypeObjects::getTypesUpdatedAfter($arr_options['date_executed']['previous'], [], true);
		
		if (!$is_updated) {
			return;
		}
		
		if ($arr_options['type_ids']) {
			$arr_types = StoreType::getTypes($arr_options['type_ids'], 'reversal');
		} else {
			$arr_types = StoreType::getTypes(false, 'reversal');
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
		
		StoreTypeObjects::cleanupObjects();
	}
	
	public static function runCleanupOrphans($arr_options = []) {
		
		if ($arr_options['descriptions']) {
			
			self::cleanupOrphans('descriptions');
		}
		
		if ($arr_options['data']) {
			
			self::cleanupOrphans('data');
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
		
		$timeout_status = (60 * 60 * 2);
		$time_status = 0;

		while (true) {
			
			Mediator::checkState(); // Check state of this service
			
			$process->checkOutput(true, true); // Check state of the process
			
			$str_error = $process->getError();
			
			if ($str_error !== '') {
				
				error('Graph Analysis Service ERROR:'.PHP_EOL
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
						
						$time = time();
						
						if (($time - $time_status) > $timeout_status) {
							
							if ($arr_result['statistics']) {
								
								msg('Status:'.PHP_EOL
									.'	Jobs: Total = '.nr2String($arr_result['statistics']['jobs']).' Timeouts = '.nr2String($arr_result['statistics']['timeouts']),
								'GRAPH ANALYSIS'); // Provide status update and keep database connection alive
							}
				
							$time_status = $time;
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
		
		$timeout_status = (60 * 60 * 2);
		$time_status = 0;

		while (true) {
			
			Mediator::checkState(); // Check state of this service
			
			$process->checkOutput(true, true); // Check state of the process
			
			$str_error = $process->getError();
			
			if ($str_error !== '') {
				
				error('Information Retrieval Service ERROR:'.PHP_EOL
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
						
						$time = time();
						
						if (($time - $time_status) > $timeout_status) {
							
							if ($arr_result['statistics']) {
								
								msg('Status:'.PHP_EOL
									.'	Jobs: Total = '.nr2String($arr_result['statistics']['jobs']),
								'INFO RETRIEVAL'); // Provide status update and keep database connection alive
							}
				
							$time_status = $time;
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
	
	public static function buildInformationRetrievalIndex($arr_options = []) {
		
		$str_host = self::getInformationRetrievalHost();

		$ir = new IndexTypeObjectsInformationRetrieval($str_host);
		
		$ir->build();
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
