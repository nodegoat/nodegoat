<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_PUBLISH_CUSTOM_PROJECTS', DB::$database_home.'.def_nodegoat_publish_custom_projects');

define('DIR_PUBLISH_CUSTOM_PROJECT', 'publish/');
define('DIR_HOME_PUBLISH_CUSTOM_PROJECT', DIR_ROOT_STORAGE.DIR_HOME.DIR_PUBLISH_CUSTOM_PROJECT);

class cms_nodegoat_publish extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function webLocations() {
		
		return [
			'name' => 'publish',
			'entries' => function() {
				
				$arr_link = pages::getClosestModule('publish');
				
				if (!$arr_link || $arr_link['require_login']) {
					return;
				}
				
				$str_location_base = pages::getModuleURL($arr_link, true);
				
				$arr_instance_projects = StorePublishInstances::getInstanceProjects();

				foreach (($arr_instance_projects['projects'] ?? []) as $project_id => $arr_instance_project) {

					$str_location = $str_location_base.$project_id;
					
					yield $str_location;
				}
				
			}
		];
	}
	
	const PUBLICATION_CREATE = 1;
	const PUBLICATION_DELETE = 2;
	
	public static function getTypeConditions($type_id, $project_id, $user_id = false, $do_object_name_only = false) {
		
		$arr_collect_conditions = [];
		$arr_condition_ids = [];
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		if ($arr_type_set['type']['condition_id']) { // Model settings
			
			
			$arr_condition_ids[] = $arr_type_set['type']['condition_id'];
			
		}
		
		$arr_use_project_ids = [];
		
		if ($project_id) { // Project settings
			
			$arr_project = StoreCustomProject::getProjects($project_id);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			if (!empty($arr_project['types'][$type_id]['type_condition_id'])) { // Project settings
				$arr_condition_ids[] = $arr_project['types'][$type_id]['type_condition_id'];
			}
		}
		
		if ($arr_condition_ids) {
			
			$arr_conditions = cms_nodegoat_custom_projects::getProjectTypeConditions($project_id, $user_id, $type_id, $arr_condition_ids, true, $arr_use_project_ids);

			foreach ($arr_condition_ids as $condition_id) { // Keep original sort
				
				$arr_condition = ($arr_conditions[$condition_id]['object'] ?? null);
				
				if (!$arr_condition) {
					continue;
				}
				
				$arr_collect_conditions[] = $arr_conditions[$condition_id]['object'];
			}
		}
		
		foreach ($arr_collect_conditions as &$arr_condition) {
			
			$arr_condition = ParseTypeFeatures::parseTypeCondition($type_id, $arr_condition);
		}
		unset($arr_condition);
		
		$arr_type_set_conditions = ParseTypeFeatures::mergeTypeConditions($type_id, $arr_collect_conditions, $do_object_name_only);
		
		return $arr_type_set_conditions;
	}
}
