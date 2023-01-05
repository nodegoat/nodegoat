<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECTS', DB::$database_home.'.def_nodegoat_custom_projects');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES', DB::$database_home.'.def_nodegoat_custom_project_types');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_DATE_TYPES', DB::$database_home.'.def_nodegoat_custom_project_date_types');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES', DB::$database_home.'.def_nodegoat_custom_project_location_types');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES', DB::$database_home.'.def_nodegoat_custom_project_source_types');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_USE_PROJECTS', DB::$database_home.'.def_nodegoat_custom_project_use_projects');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION', DB::$database_home.'.def_nodegoat_custom_project_type_configuration');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES', DB::$database_home.'.def_nodegoat_custom_project_type_include_referenced_types');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS', DB::$database_home.'.def_nodegoat_custom_project_type_filters');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCOPES', DB::$database_home.'.def_nodegoat_custom_project_type_scopes');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONTEXTS', DB::$database_home.'.def_nodegoat_custom_project_type_contexts');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES', DB::$database_home.'.def_nodegoat_custom_project_type_frames');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIOS', DB::$database_home.'.def_nodegoat_custom_project_type_scenarios');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS', DB::$database_home.'.def_nodegoat_custom_project_type_conditions');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES', DB::$database_home.'.def_nodegoat_custom_project_type_analyses');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES_CONTEXTS', DB::$database_home.'.def_nodegoat_custom_project_type_analyses_contexts');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_EXPORT_SETTINGS', DB::$database_home.'.def_nodegoat_custom_project_type_export_settings');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_VISUAL_SETTINGS', DB::$database_home.'.def_nodegoat_custom_project_visual_settings');

DB::setTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE', DB::$database_home.'.data_nodegoat_custom_project_type_scenario_cache');

DB::setTable('USER_LINK_NODEGOAT_CUSTOM_PROJECTS', DB::$database_home.'.user_link_nodegoat_custom_projects');
DB::setTable('USER_LINK_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS', DB::$database_home.'.user_link_nodegoat_custom_project_type_filters');

define('DIR_CUSTOM_PROJECT_WORKSPACE', DIR_UPLOAD.'workspace/');
define('DIR_HOME_CUSTOM_PROJECT_WORKSPACE', DIR_ROOT_STORAGE.DIR_HOME.DIR_CUSTOM_PROJECT_WORKSPACE);

class cms_nodegoat_custom_projects extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function jobProperties() {
		return [
			'runUserProjectTypeFilterUpdates' => [
				'label' => 'nodegoat '.getLabel('lbl_filter_notify'),
				'options' => false,
			]
		];
	}
	
	// Project Type Filters
	
	public static function handleProjectTypeFilter($project_id, $user_id, $filter_id, $type_id, $arr, $arr_type_filter, $is_domain = false) {
		
		$str_object = value2JSON($arr_type_filter);
		
		if ($is_domain) {
			$project_id = 0;
			$user_id = 0;
		}
		
		if ($filter_id) {
		
			$arr_cur = self::getProjectTypeFilters($project_id, false, false, $filter_id, $is_domain);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$filter_id = false;
			}
			if (!$arr_cur['project_id'] && !$is_domain) {
				error(getLabel('msg_not_allowed'));
			}
		}
		
		
		$sql_new_id = "JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')." pf ON (pf.project_id = 0 OR pf.project_id = p.id)";
		
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')."
			(project_id".", user_id, id, name, type_id, description, object)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".($filter_id ? (int)$filter_id : "(SELECT * FROM (SELECT COALESCE(MAX(pf.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							".$sql_new_id."
					) AS pid
				)").",
				'".DBFunctions::strEscape($arr['name'])."',
				".(int)$type_id.",
				'".DBFunctions::strEscape($arr['description'])."',
				'".DBFunctions::strEscape($str_object)."'
			)
			".DBFunctions::onConflict('project_id'.', id', ['user_id', 'name', 'type_id', 'description', 'object'])."
		");
						
		if (!$filter_id) {
			
			$arr_cur = self::getProjectTypeFilters($project_id, $user_id, $type_id, false, $is_domain);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$filter_id = $arr_cur['id'];
		}
		
		return $filter_id;
	}
	
	public static function delProjectTypeFilter($project_id, $filter_id, $is_domain = false) {
				
		$arr_cur = self::getProjectTypeFilters($project_id, false, false, $filter_id, $is_domain);
		if (!$arr_cur['project_id'] && !$is_domain) {
			error(getLabel('msg_not_allowed'));
		}
		
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')."
			WHERE (
					".($project_id ? "project_id = ".(int)$project_id : "")."
					".($is_domain ? ($project_id ? "OR " : "")."project_id = 0" : '')."
				)
				AND id = ".(int)$filter_id."
		");
	}
	
	public static function getProjectTypeFilters($project_id, $user_id = false, $type_id = false, $filter_id = false, $is_domain = false, $arr_use_project_ids = []) {
	
		$arr = [];

		$res = DB::query("SELECT pf.*, p.name AS project_name
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')." pf
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = pf.project_id)
			WHERE (
					".($project_id ? "pf.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id) : "")."
					".($is_domain ? ($project_id ? "OR " : "")."pf.project_id = 0" : '')."
				)
				AND (p.id != 0 OR pf.project_id = 0)
				".($filter_id && !is_array($filter_id) ? "AND pf.id = ".(int)$filter_id : "")."
				".($filter_id && is_array($filter_id) ? "AND pf.id IN (".implode(',', arrParseRecursive($filter_id, 'int')).")" : "")."
				".($user_id ? "AND (pf.user_id = 0 OR pf.user_id = ".(int)$user_id.")" : "")."
				".(!$user_id && !$filter_id ? "AND pf.user_id = 0" : "")."
				".($type_id ? "AND pf.type_id = ".(int)$type_id : "")."
			ORDER BY pf.name, pf.user_id
		");		
		
		if ($filter_id && !is_array($filter_id)) {
			
			if ($res->getRowCount()) {
				
				$arr = $res->fetchAssoc();
				$arr['label'] = ($arr['user_id'] ? '• ' : '').$arr['name'];
				$arr['object'] = json_decode($arr['object'], true);
			}

			return $arr;
		} else {
		
			while ($arr_row = $res->fetchAssoc()) {
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id) { // Do grouping
					if ($is_domain && !$arr_row['project_id']) {
						$arr_row['label'] = getLabel('lbl_clearance_admin').cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
					} else if ($project_id != $arr_row['project_id']) {
						$arr_row['label'] = $arr_row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
					}
				}
				
				$arr_row['object'] = json_decode($arr_row['object'], true);
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// Project Type Scopes
	
	public static function handleProjectTypeScope($project_id, $user_id, $scope_id, $type_id, $arr, $arr_scope) {

		$str_object = value2JSON($arr_scope);

		$is_user_default = ($project_id && $user_id && $scope_id === 0);
		
		if ($scope_id) {
			
			$arr_cur = self::getProjectTypeScopes($project_id, false, false, $scope_id);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$scope_id = false;
			}
		}
		
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCOPES')."
			(project_id, user_id, id, type_id, name, description, object)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".($scope_id || $is_user_default ? (int)$scope_id : "(SELECT * FROM (SELECT COALESCE(MAX(ps.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p2 ON (p2.user_id = p.user_id)
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCOPES')." ps ON (ps.project_id = p2.id)
						WHERE p.id = ".(int)$project_id."
					) AS pid
				)").",
				".(int)$type_id.",
				'".DBFunctions::strEscape($arr['name'])."',
				'".DBFunctions::strEscape($arr['description'])."',
				'".DBFunctions::strEscape($str_object)."'
			)
			".DBFunctions::onConflict('project_id, user_id, id, type_id', ['name', 'description', 'object'])."
		");
		
		if ($is_user_default) {
			
			return $res->getAffectedRowCount();
		}
		
		if (!$scope_id && !$is_user_default) {
			
			$arr_cur = self::getProjectTypeScopes($project_id, $user_id, $type_id, false);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$scope_id = $arr_cur['id'];
		}
		
		return $scope_id;
	}
	
	public static function delProjectTypeScope($project_id, $scope_id) {
		
		$arr_cur = self::getProjectTypeScopes($project_id, false, false, $scope_id);
			
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCOPES')."
			WHERE project_id = ".(int)$project_id."
				AND id = ".(int)$scope_id."
		");
	}
	
	public static function getProjectTypeScopes($project_id, $user_id = false, $type_id = false, $scope_id = false, $arr_use_project_ids = []) {
		
		// $scope_id = false (all stored features) / 0 (default user only) / array (any)
		
		$is_user_default = ($project_id && $user_id && $scope_id === 0);

		$arr = [];

		$res = DB::query("SELECT ps.*, p.name AS project_name
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCOPES')." ps
				JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = ps.project_id)
			WHERE ps.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id)."
				".(($scope_id || $is_user_default) && !is_array($scope_id) ? "AND ps.id = ".(int)$scope_id : "")."
				".($scope_id && is_array($scope_id) ? "AND ps.id IN (".implode(',', arrParseRecursive($scope_id, 'int')).")" : "")."
				".($user_id ? "AND (ps.user_id = 0 OR ps.user_id = ".(int)$user_id.")" : "")."
				".(!$user_id && !$scope_id ? "AND ps.user_id = 0" : "")."
				".($type_id ? "AND ps.type_id = ".(int)$type_id : "")."
			ORDER BY ps.name, ps.user_id
		");		
		
		if (($scope_id || $is_user_default) && !is_array($scope_id)) {
			
			if ($res->getRowCount()) {
				
				$arr = $res->fetchAssoc();
				$arr['label'] = ($arr['user_id'] ? '• ' : '').$arr['name'];
				$arr['object'] = json_decode($arr['object'], true);
			}

			return $arr;
		} else {
		
			while($arr_row = $res->fetchAssoc()) {
				
				if ($arr_row['id'] == 0 && ($scope_id === false || $scope_id === 0)) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id != $arr_row['project_id']) {
					$arr_row['label'] = $arr_row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
				}
				
				$arr_row['object'] = json_decode($arr_row['object'], true);
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// Project Type Contexts
	
	public static function handleProjectTypeContext($project_id, $user_id, $context_id, $type_id, $arr, $arr_context) {
		
		$str_object = value2JSON($arr_context);
		
		$is_user_default = ($project_id && $user_id && $context_id === 0);
		
		if ($context_id) {
			
			$arr_cur = self::getProjectTypeContexts($project_id, false, false, $context_id);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$context_id = false;
			}
		}
				
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONTEXTS')."
			(project_id, user_id, id, type_id, name, description, object)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".($context_id || $is_user_default ? (int)$context_id : "(SELECT * FROM (SELECT COALESCE(MAX(pcx.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p2 ON (p2.user_id = p.user_id)
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONTEXTS')." pcx ON (pcx.project_id = p2.id)
						WHERE p.id = ".(int)$project_id."
					) AS pid
				)").",
				".(int)$type_id.",
				'".DBFunctions::strEscape($arr['name'])."',
				'".DBFunctions::strEscape($arr['description'])."',
				'".DBFunctions::strEscape($str_object)."'
			)
			".DBFunctions::onConflict('project_id, user_id, id, type_id', ['name', 'description', 'object'])."
		");
		
		if ($is_user_default) {
			
			return $res->getAffectedRowCount();
		}
		
		if (!$context_id && !$is_user_default) {
			
			$arr_cur = self::getProjectTypeContexts($project_id, $user_id, $type_id, false);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$context_id = $arr_cur['id'];
		}
		
		return $context_id;
	}
	
	public static function delProjectTypeContext($project_id, $context_id) {
		
		$arr_cur = self::getProjectTypeContexts($project_id, false, false, $context_id);
			
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONTEXTS')."
			WHERE project_id = ".(int)$project_id."
				AND id = ".(int)$context_id."
		");
	}
	
	public static function getProjectTypeContexts($project_id, $user_id = false, $type_id = false, $context_id = false, $arr_use_project_ids = []) {
		
		// $context_id = false (all stored features) / 0 (default user only) / array (any)
		
		$is_user_default = ($project_id && $user_id && $context_id === 0);

		$arr = [];

		$res = DB::query("SELECT pcx.*, p.name AS project_name
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONTEXTS')." pcx
				JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = pcx.project_id)
			WHERE pcx.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id)."
				".(($context_id || $is_user_default) && !is_array($context_id) ? "AND pcx.id = ".(int)$context_id : "")."
				".($context_id && is_array($context_id) ? "AND pcx.id IN (".implode(',', arrParseRecursive($context_id, 'int')).")" : "")."
				".($user_id ? "AND (pcx.user_id = 0 OR pcx.user_id = ".(int)$user_id.")" : "")."
				".(!$user_id && !$context_id ? "AND pcx.user_id = 0" : "")."
				".($type_id ? "AND pcx.type_id = ".(int)$type_id : "")."
			ORDER BY pcx.name, pcx.user_id
		");		
		
		if (($context_id || $is_user_default) && !is_array($context_id)) {
			
			if ($res->getRowCount()) {
				$arr = $res->fetchAssoc();
				$arr['label'] = ($arr['user_id'] ? '• ' : '').$arr['name'];
				$arr['object'] = json_decode($arr['object'], true);
			}

			return $arr;
		} else {
		
			while($arr_row = $res->fetchAssoc()) {
				
				if ($arr_row['id'] == 0 && ($context_id === false || $context_id === 0)) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id != $arr_row['project_id']) {
					$arr_row['label'] = $arr_row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
				}
				
				$arr_row['object'] = json_decode($arr_row['object'], true);
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// Project Type Frames
	
	public static function handleProjectTypeFrame($project_id, $user_id, $frame_id, $type_id, $arr, $arr_frame) {
		
		$is_user_default = ($project_id && $user_id && $frame_id === 0);
		
		if ($frame_id) {
			
			$arr_cur = self::getProjectTypeFrames($project_id, false, false, $frame_id);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$frame_id = false;
			}
		}
				
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES')."
			(project_id, user_id, id, type_id, name, description,
				area_geo_latitude, area_geo_longitude, area_geo_zoom_scale, area_geo_zoom_min, area_geo_zoom_max, area_social_object_id, area_social_zoom_level, area_social_zoom_min, area_social_zoom_max, time_bounds_date_start, time_bounds_date_end, time_selection_date_start, time_selection_date_end, object_subs_unknown_date, object_subs_unknown_location
			)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".($frame_id || $is_user_default ? (int)$frame_id : "(SELECT * FROM (SELECT COALESCE(MAX(pfr.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p2 ON (p2.user_id = p.user_id)
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES')." pfr ON (pfr.project_id = p2.id)
						WHERE p.id = ".(int)$project_id."
					) AS pid
				)").",
				".(int)$type_id.",
				'".DBFunctions::strEscape($arr['name'])."',
				'".DBFunctions::strEscape($arr['description'])."',
				".((string)$arr_frame['area']['geo']['latitude'] !== '' ? (float)$arr_frame['area']['geo']['latitude'] : 'NULL').",
				".((string)$arr_frame['area']['geo']['longitude'] !== '' ? (float)$arr_frame['area']['geo']['longitude'] : 'NULL').",
				".(float)$arr_frame['area']['geo']['zoom']['scale'].",
				".(int)$arr_frame['area']['geo']['zoom']['min'].",
				".(int)$arr_frame['area']['geo']['zoom']['max'].",
				".(int)$arr_frame['area']['social']['object_id'].",
				".(float)$arr_frame['area']['social']['zoom']['level'].",
				".(int)$arr_frame['area']['social']['zoom']['min'].",
				".(int)$arr_frame['area']['social']['zoom']['max'].",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['bounds']['date_start']).",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['bounds']['date_end']).",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['selection']['date_start']).",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['selection']['date_end']).",
				".($arr_frame['object_subs']['unknown']['date'] && $arr_frame['object_subs']['unknown']['date'] != 'span' ? "'".DBFunctions::strEscape($arr_frame['object_subs']['unknown']['date'])."'" : 'NULL').",
				".($arr_frame['object_subs']['unknown']['location'] && $arr_frame['object_subs']['unknown']['location'] != 'ignore' ? "'".DBFunctions::strEscape($arr_frame['object_subs']['unknown']['location'])."'" : 'NULL')."
			)
			".DBFunctions::onConflict('project_id, user_id, id, type_id', ['name', 'description',
				'area_geo_latitude', 'area_geo_longitude', 'area_geo_zoom_scale', 'area_geo_zoom_min', 'area_geo_zoom_max', 'area_social_object_id', 'area_social_zoom_level', 'area_social_zoom_min', 'area_social_zoom_max', 'time_bounds_date_start', 'time_bounds_date_end', 'time_selection_date_start', 'time_selection_date_end', 'object_subs_unknown_date', 'object_subs_unknown_location'
			])."
		");
		
		if ($is_user_default) {
			
			return $res->getAffectedRowCount();
		}
		
		if (!$frame_id && !$is_user_default) {
			
			$arr_cur = self::getProjectTypeFrames($project_id, $user_id, $type_id, false);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$frame_id = $arr_cur['id'];
		}
		
		return $frame_id;
	}
	
	public static function delProjectTypeFrame($project_id, $frame_id) {
		
		$arr_cur = self::getProjectTypeFrames($project_id, false, false, $frame_id);
			
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES')."
			WHERE project_id = ".(int)$project_id."
				AND id = ".(int)$frame_id."
		");
	}
	
	public static function getProjectTypeFrames($project_id, $user_id = false, $type_id = false, $frame_id = false, $arr_use_project_ids = []) {
		
		// $frame_id = false (all stored features) / 0 (default user only) / array (any)
		
		$is_user_default = ($project_id && $user_id && $frame_id === 0);

		$arr = [];

		$res = DB::query("SELECT pfr.*, p.name AS project_name
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES')." pfr
				JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = pfr.project_id)
			WHERE pfr.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id)."
				".(($frame_id || $is_user_default) && !is_array($frame_id) ? "AND pfr.id = ".(int)$frame_id : "")."
				".($frame_id && is_array($frame_id) ? "AND pfr.id IN (".implode(',', arrParseRecursive($frame_id, 'int')).")" : "")."
				".($user_id ? "AND (pfr.user_id = 0 OR pfr.user_id = ".(int)$user_id.")" : "")."
				".(!$user_id && !$frame_id ? "AND pfr.user_id = 0" : "")."
				".($type_id ? "AND pfr.type_id = ".(int)$type_id : "")."
			ORDER BY pfr.name, pfr.user_id
		");		
		
		if (($frame_id || $is_user_default) && !is_array($frame_id)) {
			
			if ($res->getRowCount()) {
				
				$arr_row = $res->fetchAssoc();
				
				$arr_row['time_bounds_date_start'] = (int)$arr_row['time_bounds_date_start'];
				$arr_row['time_bounds_date_end'] = (int)$arr_row['time_bounds_date_end'];
				$arr_row['time_selection_date_start'] = (int)$arr_row['time_selection_date_start'];
				$arr_row['time_selection_date_end'] = (int)$arr_row['time_selection_date_end'];
				
				$arr_settings = array_slice($arr_row, 5, -1, true);
				array_splice($arr_row, 6, -1);
				$arr_row['settings'] = $arr_settings;
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				
				$arr = $arr_row;
			}

			return $arr;
		} else {
		
			while($arr_row = $res->fetchAssoc()) {
				
				if ($arr_row['id'] == 0 && ($frame_id === false || $frame_id === 0)) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['time_bounds_date_start'] = (int)$arr_row['time_bounds_date_start'];
				$arr_row['time_bounds_date_end'] = (int)$arr_row['time_bounds_date_end'];
				$arr_row['time_selection_date_start'] = (int)$arr_row['time_selection_date_start'];
				$arr_row['time_selection_date_end'] = (int)$arr_row['time_selection_date_end'];
				
				$arr_settings = array_slice($arr_row, 5, -1, true);
				array_splice($arr_row, 6, -1);
				$arr_row['settings'] = $arr_settings;
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id != $arr_row['project_id']) {
					$arr_row['label'] = $arr_row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
				}
				
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	public static function parseFrame($arr_settings) {
		
		if (!$arr_settings) {
			$arr_settings = [];
		}
		
		if (array_key_exists('area', $arr_settings)) {
			
			$arr_settings_use = $arr_settings;
			
			$arr_settings = [
				'area_geo_latitude' => $arr_settings_use['area']['geo']['latitude'],
				'area_geo_longitude' => $arr_settings_use['area']['geo']['longitude'],
				'area_geo_zoom_scale' => $arr_settings_use['area']['geo']['zoom']['scale'],
				'area_geo_zoom_min' => $arr_settings_use['area']['geo']['zoom']['min'],
				'area_geo_zoom_max' => $arr_settings_use['area']['geo']['zoom']['max'],
				'area_social_object_id' => $arr_settings_use['area']['social']['object_id'],
				'area_social_zoom_level' => $arr_settings_use['area']['social']['zoom']['level'],
				'area_social_zoom_min' => $arr_settings_use['area']['social']['zoom']['min'],
				'area_social_zoom_max' => $arr_settings_use['area']['social']['zoom']['max'],
				'time_bounds_date_start' => $arr_settings_use['time']['bounds']['date_start'],
				'time_bounds_date_end' => $arr_settings_use['time']['bounds']['date_end'],
				'time_selection_date_start' => $arr_settings_use['time']['selection']['date_start'],
				'time_selection_date_end' => $arr_settings_use['time']['selection']['date_end'],
				'object_subs_unknown_date' => $arr_settings_use['object_subs']['unknown']['date'],
				'object_subs_unknown_location' => $arr_settings_use['object_subs']['unknown']['location']
			];
		}
			
		return [
			'area' => [
				'geo' => [
					'latitude' => ((string)$arr_settings['area_geo_latitude'] !== '' ? (float)$arr_settings['area_geo_latitude'] : ''),
					'longitude' => ((string)$arr_settings['area_geo_longitude'] !== '' ? (float)$arr_settings['area_geo_longitude'] : ''),
					'zoom' => [
						'scale' => ($arr_settings['area_geo_zoom_scale'] ? (float)$arr_settings['area_geo_zoom_scale'] : ''),
						'min' => ($arr_settings['area_geo_zoom_min'] ? (int)$arr_settings['area_geo_zoom_min'] : ''),
						'max' => ($arr_settings['area_geo_zoom_max'] ? (int)$arr_settings['area_geo_zoom_max'] : '')
					]
				],
				'social' => [
					'object_id' => ($arr_settings['area_social_object_id'] ? (int)$arr_settings['area_social_object_id'] : ''),
					'zoom' => [
						'level' => ($arr_settings['area_social_zoom_level'] ? (float)$arr_settings['area_social_zoom_level'] : ''),
						'min' => ($arr_settings['area_social_zoom_min'] ? (int)$arr_settings['area_social_zoom_min'] : ''),
						'max' => ($arr_settings['area_social_zoom_max'] ? (int)$arr_settings['area_social_zoom_max'] : '')
					]
				]
			],
			'time' => [
				'bounds' => [
					'date_start' => (int)(StoreTypeObjects::formatToSQLValue('date', $arr_settings['time_bounds_date_start']) ?: ''),
					'date_end' => (int)(StoreTypeObjects::formatToSQLValue('date', $arr_settings['time_bounds_date_end']) ?: '')
				],
				'selection' => [
					'date_start' => (int)(StoreTypeObjects::formatToSQLValue('date', $arr_settings['time_selection_date_start']) ?: ''),
					'date_end' => (int)(StoreTypeObjects::formatToSQLValue('date', $arr_settings['time_selection_date_end']) ?: '')
				]
			],
			'object_subs' => [
				'unknown' => [
					'date' => ($arr_settings['object_subs_unknown_date'] ? $arr_settings['object_subs_unknown_date'] : 'span'),
					'location' => ($arr_settings['object_subs_unknown_location'] ? $arr_settings['object_subs_unknown_location'] : 'ignore')
				]
			]
		];
	}
	
	// Project Visual Settings
	
	public static function handleProjectVisualSettings($project_id, $user_id, $visual_settings_id, $arr, $arr_visual_settings) {
		
		$is_user_default = ($project_id && $user_id && $visual_settings_id === 0);
		
		if ($visual_settings_id) {
			
			$arr_cur = self::getProjectVisualSettings($project_id, false, $visual_settings_id);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$visual_settings_id = false;
			}
		}
		
		$arr_default = self::parseVisualSettings();
				
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_VISUAL_SETTINGS')."
			(project_id, user_id, id, name, description,
				capture_enable, capture_settings, dot_show, dot_color, dot_opacity, dot_color_condition, dot_size_min, dot_size_max, dot_size_start, dot_size_stop, dot_stroke_color, dot_stroke_opacity, dot_stroke_width, location_show, location_color, location_opacity, location_size, location_threshold, location_offset, location_position, location_condition, line_show, line_color, line_opacity, line_width_min, line_width_max, line_offset, visual_hints_show, visual_hints_color, visual_hints_opacity, visual_hints_size, visual_hints_stroke_color, visual_hints_stroke_opacity, visual_hints_stroke_width, visual_hints_duration, visual_hints_delay, geometry_show, geometry_color, geometry_opacity, geometry_stroke_color, geometry_stroke_opacity, geometry_stroke_width, map_show, map_url, map_attribution, geo_info_show, geo_background_color, geo_mode, geo_display, geo_advanced, social_dot_color, social_dot_size_min, social_dot_size_max, social_dot_size_start, social_dot_size_stop, social_dot_stroke_color, social_dot_stroke_width, social_line_show, social_line_arrowhead_show, social_force, social_forceatlas2, social_disconnected_dot_show, social_include_location_references, social_background_color, social_display, social_static_layout, social_static_layout_interval, social_advanced, time_bar_color, time_bar_opacity, time_background_color, time_relative_graph, time_cumulative_graph
			)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".(($visual_settings_id || $is_user_default) ? (int)$visual_settings_id : "(SELECT * FROM (SELECT COALESCE(MAX(pvs.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p2 ON (p2.user_id = p.user_id)
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_VISUAL_SETTINGS')." pvs ON (pvs.project_id = p2.id)
						WHERE p.id = ".(int)$project_id."
					) AS pid
				)").",
				'".DBFunctions::strEscape($arr['name'])."',
				'".DBFunctions::strEscape($arr['description'])."',
					".((string)$arr_visual_settings['capture']['enable'] !== '' && (int)$arr_visual_settings['capture']['enable'] != $arr_default['capture']['enable'] ? (int)$arr_visual_settings['capture']['enable'] : 'NULL').",
					'".($arr_visual_settings['capture']['settings'] && $arr_visual_settings['capture']['settings'] !== $arr_default['capture']['settings'] ? DBFunctions::strEscape(value2JSON($arr_visual_settings['capture']['settings'])) : '')."',
					".((string)$arr_visual_settings['dot']['show'] !== '' && (int)$arr_visual_settings['dot']['show'] != $arr_default['dot']['show'] ? (int)$arr_visual_settings['dot']['show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['dot']['color']) != $arr_default['dot']['color'] ? str2Color($arr_visual_settings['dot']['color']) : '')."',
					".((string)$arr_visual_settings['dot']['opacity'] !== '' && (float)$arr_visual_settings['dot']['opacity'] != $arr_default['dot']['opacity'] ? (float)$arr_visual_settings['dot']['opacity'] : 'NULL').",
					'".($arr_visual_settings['dot']['color_condition'] != $arr_default['dot']['color_condition'] ? DBFunctions::strEscape($arr_visual_settings['dot']['color_condition']) : '')."',
					".(float)((float)$arr_visual_settings['dot']['size']['min'] != $arr_default['dot']['size']['min'] ? $arr_visual_settings['dot']['size']['min'] : '').",
					".(float)((float)$arr_visual_settings['dot']['size']['max'] != $arr_default['dot']['size']['max'] ? $arr_visual_settings['dot']['size']['max'] : '').",
					".(int)((int)$arr_visual_settings['dot']['size']['start'] != $arr_default['dot']['size']['start'] ? $arr_visual_settings['dot']['size']['start'] : '').",
					".(int)((int)$arr_visual_settings['dot']['size']['stop'] != $arr_default['dot']['size']['stop'] ? $arr_visual_settings['dot']['size']['stop'] : '').",
					'".(str2Color($arr_visual_settings['dot']['stroke_color']) != $arr_default['dot']['stroke_color'] ? str2Color($arr_visual_settings['dot']['stroke_color']) : '')."',
					".(float)((float)$arr_visual_settings['dot']['stroke_opacity'] != $arr_default['dot']['stroke_opacity'] ? $arr_visual_settings['dot']['stroke_opacity'] : '').",
					".((string)$arr_visual_settings['dot']['stroke_width'] !== '' && (float)$arr_visual_settings['dot']['stroke_width'] != $arr_default['dot']['stroke_width'] ? (float)$arr_visual_settings['dot']['stroke_width'] : 'NULL').",
					".((string)$arr_visual_settings['location']['show'] !== '' && (int)$arr_visual_settings['location']['show'] != $arr_default['location']['show'] ? (int)$arr_visual_settings['location']['show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['location']['color']) != $arr_default['location']['color'] ? str2Color($arr_visual_settings['location']['color']) : '')."',
					".(float)((float)$arr_visual_settings['location']['opacity'] != $arr_default['location']['opacity'] ? $arr_visual_settings['location']['opacity'] : '').",
					".(float)((float)$arr_visual_settings['location']['size'] != $arr_default['location']['size'] ? $arr_visual_settings['location']['size'] : '').",
					".(int)((int)$arr_visual_settings['location']['threshold'] != $arr_default['location']['threshold'] ? $arr_visual_settings['location']['threshold'] : '').",
					".((string)$arr_visual_settings['location']['offset'] !== '' && (int)$arr_visual_settings['location']['offset'] != $arr_default['location']['offset'] ? (int)$arr_visual_settings['location']['offset'] : 'NULL').",
					'".($arr_visual_settings['location']['position'] && $arr_visual_settings['location']['position'] !== $arr_default['location']['position'] ? DBFunctions::strEscape(value2JSON($arr_visual_settings['location']['position'])) : '')."',
					'".($arr_visual_settings['location']['condition'] != $arr_default['location']['condition'] ? DBFunctions::strEscape($arr_visual_settings['location']['condition']) : '')."',
					".((string)$arr_visual_settings['line']['show'] !== '' && (int)$arr_visual_settings['line']['show'] != $arr_default['line']['show'] ? (int)$arr_visual_settings['line']['show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['line']['color']) != $arr_default['line']['color'] ? str2Color($arr_visual_settings['line']['color']) : '')."',
					".(float)((float)$arr_visual_settings['line']['opacity'] != $arr_default['line']['opacity'] ? $arr_visual_settings['line']['opacity'] : '').",
					".(float)((float)$arr_visual_settings['line']['width']['min'] != $arr_default['line']['width']['min'] ? $arr_visual_settings['line']['width']['min'] : '').",
					".(float)((float)$arr_visual_settings['line']['width']['max'] != $arr_default['line']['width']['max'] ? $arr_visual_settings['line']['width']['max'] : '').",
					".((string)$arr_visual_settings['line']['offset'] !== '' && (int)$arr_visual_settings['line']['offset'] != $arr_default['line']['offset'] ? (int)$arr_visual_settings['line']['offset'] : 'NULL').",
					".((string)$arr_visual_settings['hint']['show'] !== '' && (int)$arr_visual_settings['hint']['show'] != $arr_default['hint']['show'] ? (int)$arr_visual_settings['hint']['show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['hint']['color']) != $arr_default['hint']['color'] ? str2Color($arr_visual_settings['hint']['color']) : '')."',
					".((string)$arr_visual_settings['hint']['opacity'] !== '' && (float)$arr_visual_settings['hint']['opacity'] != $arr_default['hint']['opacity'] ? (float)$arr_visual_settings['hint']['opacity'] : 'NULL').",
					".(float)((float)$arr_visual_settings['hint']['size'] != $arr_default['hint']['size'] ? $arr_visual_settings['hint']['size'] : '').",
					'".(str2Color($arr_visual_settings['hint']['stroke_color']) != $arr_default['hint']['stroke_color'] ? str2Color($arr_visual_settings['hint']['stroke_color']) : '')."',
					".(float)((float)$arr_visual_settings['hint']['stroke_opacity'] != $arr_default['hint']['stroke_opacity'] ? $arr_visual_settings['hint']['stroke_opacity'] : '').",
					".((string)$arr_visual_settings['hint']['stroke_width'] !== '' && (float)$arr_visual_settings['hint']['stroke_width'] != $arr_default['hint']['stroke_width'] ? (float)$arr_visual_settings['hint']['stroke_width'] : 'NULL').",
					".(float)((float)$arr_visual_settings['hint']['duration'] != $arr_default['hint']['duration'] ? $arr_visual_settings['hint']['duration'] : '').",
					".(float)((float)$arr_visual_settings['hint']['delay'] != $arr_default['hint']['delay'] ? $arr_visual_settings['hint']['delay'] : '').",
					".((string)$arr_visual_settings['geometry']['show'] !== '' && (int)$arr_visual_settings['geometry']['show'] != $arr_default['geometry']['show'] ? (int)$arr_visual_settings['geometry']['show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['geometry']['color']) != $arr_default['geometry']['color'] ? str2Color($arr_visual_settings['geometry']['color']) : '')."',
					".((string)$arr_visual_settings['geometry']['opacity'] !== '' && (float)$arr_visual_settings['geometry']['opacity'] != $arr_default['geometry']['opacity'] ? (float)$arr_visual_settings['geometry']['opacity'] : 'NULL').",
					'".(str2Color($arr_visual_settings['geometry']['stroke_color']) != $arr_default['geometry']['stroke_color'] ? str2Color($arr_visual_settings['geometry']['stroke_color']) : '')."',
					".(float)((float)$arr_visual_settings['geometry']['stroke_opacity'] != $arr_default['geometry']['stroke_opacity'] ? $arr_visual_settings['geometry']['stroke_opacity'] : '').",
					".((string)$arr_visual_settings['geometry']['stroke_width'] !== '' && (float)$arr_visual_settings['geometry']['stroke_width'] != $arr_default['geometry']['stroke_width'] ? (float)$arr_visual_settings['geometry']['stroke_width'] : 'NULL').",
					".((string)$arr_visual_settings['settings']['map_show'] !== '' && (int)$arr_visual_settings['settings']['map_show'] != $arr_default['settings']['map_show'] ? (int)$arr_visual_settings['settings']['map_show'] : 'NULL').",
					'".($arr_visual_settings['settings']['map_url'] != $arr_default['settings']['map_url'] ? DBFunctions::strEscape($arr_visual_settings['settings']['map_url']) : '')."',
					'".($arr_visual_settings['settings']['map_attribution'] != $arr_default['settings']['map_attribution'] ? DBFunctions::strEscape($arr_visual_settings['settings']['map_attribution']) : '')."',
					".((string)$arr_visual_settings['settings']['geo_info_show'] !== '' && (int)$arr_visual_settings['settings']['geo_info_show'] != $arr_default['settings']['geo_info_show'] ? (int)$arr_visual_settings['settings']['geo_info_show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['settings']['geo_background_color']) != $arr_default['settings']['geo_background_color'] ? str2Color($arr_visual_settings['settings']['geo_background_color']) : '')."',
					".((string)$arr_visual_settings['settings']['geo_mode'] !== '' && (int)$arr_visual_settings['settings']['geo_mode'] != $arr_default['settings']['geo_mode'] ? (int)$arr_visual_settings['settings']['geo_mode'] : 'NULL').",
					".((string)$arr_visual_settings['settings']['geo_display'] !== '' && (int)$arr_visual_settings['settings']['geo_display'] != $arr_default['settings']['geo_display'] ? (int)$arr_visual_settings['settings']['geo_display'] : 'NULL').",
					'".($arr_visual_settings['settings']['geo_advanced'] && $arr_visual_settings['settings']['geo_advanced'] !== $arr_default['settings']['geo_advanced'] ? DBFunctions::strEscape(value2JSON($arr_visual_settings['settings']['geo_advanced'])) : '')."',
					'".(str2Color($arr_visual_settings['social']['dot']['color']) != $arr_default['social']['dot']['color'] ? str2Color($arr_visual_settings['social']['dot']['color']) : '')."',
					".(float)((float)$arr_visual_settings['social']['dot']['size']['min'] != $arr_default['social']['dot']['size']['min'] ? $arr_visual_settings['social']['dot']['size']['min'] : '').",
					".(float)((float)$arr_visual_settings['social']['dot']['size']['max'] != $arr_default['social']['dot']['size']['max'] ? $arr_visual_settings['social']['dot']['size']['max'] : '').",
					".(int)((int)$arr_visual_settings['social']['dot']['size']['start'] != $arr_default['social']['dot']['size']['start'] ? $arr_visual_settings['social']['dot']['size']['start'] : '').",
					".(int)((int)$arr_visual_settings['social']['dot']['size']['stop'] != $arr_default['social']['dot']['size']['stop'] ? $arr_visual_settings['social']['dot']['size']['stop'] : '').",
					'".(str2Color($arr_visual_settings['social']['dot']['stroke_color']) != $arr_default['social']['dot']['stroke_color'] ? str2Color($arr_visual_settings['social']['dot']['stroke_color']) : '')."',
					".((string)$arr_visual_settings['social']['dot']['stroke_width'] !== '' && (float)$arr_visual_settings['social']['dot']['stroke_width'] != $arr_default['social']['dot']['stroke_width'] ? (float)$arr_visual_settings['social']['dot']['stroke_width'] : 'NULL').",
					".((string)$arr_visual_settings['social']['line']['show'] !== '' && (int)$arr_visual_settings['social']['line']['show'] != $arr_default['social']['line']['show'] ? (int)$arr_visual_settings['social']['line']['show'] : 'NULL').",
					".((string)$arr_visual_settings['social']['line']['arrowhead_show'] !== '' && (int)$arr_visual_settings['social']['line']['arrowhead_show'] != $arr_default['social']['line']['arrowhead_show'] ? (int)$arr_visual_settings['social']['line']['arrowhead_show'] : 'NULL').",
					'".($arr_visual_settings['social']['force'] && $arr_visual_settings['social']['force'] !== $arr_default['social']['force'] ? DBFunctions::strEscape(value2JSON($arr_visual_settings['social']['force'])) : '')."',
					'".($arr_visual_settings['social']['forceatlas2'] && $arr_visual_settings['social']['forceatlas2'] !== $arr_default['social']['forceatlas2'] ? DBFunctions::strEscape(value2JSON($arr_visual_settings['social']['forceatlas2'])) : '')."',
					".((string)$arr_visual_settings['social']['settings']['disconnected_dot_show'] !== '' && (int)$arr_visual_settings['social']['settings']['disconnected_dot_show'] != $arr_default['social']['settings']['disconnected_dot_show'] ? (int)$arr_visual_settings['social']['settings']['disconnected_dot_show'] : 'NULL').",
					".((string)$arr_visual_settings['social']['settings']['include_location_references'] !== '' && (int)$arr_visual_settings['social']['settings']['include_location_references'] != $arr_default['social']['settings']['include_location_references'] ? (int)$arr_visual_settings['social']['settings']['include_location_references'] : 'NULL').",
					'".(str2Color($arr_visual_settings['social']['settings']['background_color']) != $arr_default['social']['settings']['background_color'] ? str2Color($arr_visual_settings['social']['settings']['background_color']) : '')."',
					".((string)$arr_visual_settings['social']['settings']['display'] !== '' && (int)$arr_visual_settings['social']['settings']['display'] != $arr_default['social']['settings']['display'] ? (int)$arr_visual_settings['social']['settings']['display'] : 'NULL').",
					".((string)$arr_visual_settings['social']['settings']['static_layout'] !== '' && (int)$arr_visual_settings['social']['settings']['static_layout'] != $arr_default['social']['settings']['static_layout'] ? (int)$arr_visual_settings['social']['settings']['static_layout'] : 'NULL').",
					".((string)$arr_visual_settings['social']['settings']['static_layout_interval'] !== '' && (float)$arr_visual_settings['social']['settings']['static_layout_interval'] != $arr_default['social']['settings']['static_layout_interval'] ? (float)$arr_visual_settings['social']['settings']['static_layout_interval'] : 'NULL').",
					'".($arr_visual_settings['social']['settings']['social_advanced'] && $arr_visual_settings['social']['settings']['social_advanced'] !== $arr_default['social']['settings']['social_advanced'] ? DBFunctions::strEscape(value2JSON($arr_visual_settings['social']['settings']['social_advanced'])) : '')."',
					'".(str2Color($arr_visual_settings['time']['bar']['color']) != $arr_default['time']['bar']['color'] ? str2Color($arr_visual_settings['time']['bar']['color']) : '')."',
					".(float)((float)$arr_visual_settings['time']['bar']['opacity'] != $arr_default['time']['bar']['opacity'] ? $arr_visual_settings['time']['bar']['opacity'] : '').",
					'".(str2Color($arr_visual_settings['time']['settings']['background_color']) != $arr_default['time']['settings']['background_color'] ? str2Color($arr_visual_settings['time']['settings']['background_color']) : '')."',
					".((string)$arr_visual_settings['time']['settings']['relative_graph'] !== '' && (int)$arr_visual_settings['time']['settings']['relative_graph'] != $arr_default['time']['settings']['relative_graph'] ? (int)$arr_visual_settings['time']['settings']['relative_graph'] : 'NULL').",
					".((string)$arr_visual_settings['time']['settings']['cumulative_graph'] !== '' && (int)$arr_visual_settings['time']['settings']['cumulative_graph'] != $arr_default['time']['settings']['cumulative_graph'] ? (int)$arr_visual_settings['time']['settings']['cumulative_graph'] : 'NULL')."
			)
			".DBFunctions::onConflict('project_id, user_id, id', ['name', 'description',
				'capture_enable', 'capture_settings', 'dot_show', 'dot_color', 'dot_opacity', 'dot_color_condition', 'dot_size_min', 'dot_size_max', 'dot_size_start', 'dot_size_stop', 'dot_stroke_color', 'dot_stroke_opacity', 'dot_stroke_width', 'location_show', 'location_color', 'location_opacity', 'location_size', 'location_threshold', 'location_offset', 'location_position', 'location_condition', 'line_show', 'line_color', 'line_opacity', 'line_width_min', 'line_width_max', 'line_offset', 'visual_hints_show', 'visual_hints_color', 'visual_hints_opacity', 'visual_hints_size', 'visual_hints_stroke_color', 'visual_hints_stroke_opacity', 'visual_hints_stroke_width', 'visual_hints_duration', 'visual_hints_delay', 'geometry_show', 'geometry_color', 'geometry_opacity', 'geometry_stroke_color', 'geometry_stroke_opacity', 'geometry_stroke_width', 'map_show', 'map_url', 'map_attribution', 'geo_info_show', 'geo_background_color', 'geo_mode', 'geo_display', 'geo_advanced', 'social_dot_color', 'social_dot_size_min', 'social_dot_size_max', 'social_dot_size_start', 'social_dot_size_stop', 'social_dot_stroke_color', 'social_dot_stroke_width', 'social_line_show', 'social_line_arrowhead_show', 'social_force', 'social_forceatlas2', 'social_disconnected_dot_show', 'social_include_location_references', 'social_background_color', 'social_display', 'social_static_layout', 'social_static_layout_interval', 'social_advanced', 'time_bar_color', 'time_bar_opacity', 'time_background_color', 'time_relative_graph', 'time_cumulative_graph'
			])."
		");
		
		if ($is_user_default) {
			
			return $res->getAffectedRowCount();
		}
		
		if (!$visual_settings_id && !$is_user_default) {
			
			$arr_cur = self::getProjectVisualSettings($project_id, $user_id, false);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$visual_settings_id = $arr_cur['id'];
		}
		
		return $visual_settings_id;
	}
	
	public static function parseVisualSettings($arr_settings = false) {
		
		if (!$arr_settings) {
			$arr_settings = [];
		}
		
		if (array_key_exists('dot', $arr_settings)) {
			
			$arr_settings_use = $arr_settings;
			
			$arr_settings = [
				'capture_enable' => $arr_settings_use['capture']['enable'],
				'capture_settings' => $arr_settings_use['capture']['settings'],
				'dot_show' => $arr_settings_use['dot']['show'],
				'dot_color' => $arr_settings_use['dot']['color'],
				'dot_opacity' => $arr_settings_use['dot']['opacity'],
				'dot_color_condition' => $arr_settings_use['dot']['color_condition'],
				'dot_size_min' => $arr_settings_use['dot']['size']['min'],
				'dot_size_max' => $arr_settings_use['dot']['size']['max'],
				'dot_size_start' => $arr_settings_use['dot']['size']['start'],
				'dot_size_stop' => $arr_settings_use['dot']['size']['stop'],
				'dot_stroke_color' => $arr_settings_use['dot']['stroke_color'],
				'dot_stroke_opacity' => $arr_settings_use['dot']['stroke_opacity'],
				'dot_stroke_width' => $arr_settings_use['dot']['stroke_width'],
				'location_show' => $arr_settings_use['location']['show'],
				'location_color' => $arr_settings_use['location']['color'],
				'location_opacity' => $arr_settings_use['location']['opacity'],
				'location_size' => $arr_settings_use['location']['size'],
				'location_threshold' => $arr_settings_use['location']['threshold'],
				'location_offset' => $arr_settings_use['location']['offset'],
				'location_position' => $arr_settings_use['location']['position'],
				'location_condition' => $arr_settings_use['location']['condition'],
				'line_show' => $arr_settings_use['line']['show'],
				'line_color' => $arr_settings_use['line']['color'],
				'line_opacity' => $arr_settings_use['line']['opacity'],
				'line_width_min' => $arr_settings_use['line']['width']['min'],
				'line_width_max' => $arr_settings_use['line']['width']['max'],
				'line_offset' => $arr_settings_use['line']['offset'],
				'visual_hints_show' => $arr_settings_use['hint']['show'],
				'visual_hints_color' => $arr_settings_use['hint']['color'],
				'visual_hints_opacity' => $arr_settings_use['hint']['opacity'],
				'visual_hints_size' => $arr_settings_use['hint']['size'],
				'visual_hints_stroke_color' => $arr_settings_use['hint']['stroke_color'],
				'visual_hints_stroke_opacity' => $arr_settings_use['hint']['stroke_opacity'],
				'visual_hints_stroke_width' => $arr_settings_use['hint']['stroke_width'],
				'visual_hints_duration' => $arr_settings_use['hint']['duration'],
				'visual_hints_delay' => $arr_settings_use['hint']['delay'],
				'geometry_show' => $arr_settings_use['geometry']['show'],
				'geometry_color' => $arr_settings_use['geometry']['color'],
				'geometry_opacity' => $arr_settings_use['geometry']['opacity'],
				'geometry_stroke_color' => $arr_settings_use['geometry']['stroke_color'],
				'geometry_stroke_opacity' => $arr_settings_use['geometry']['stroke_opacity'],
				'geometry_stroke_width' => $arr_settings_use['geometry']['stroke_width'],
				'map_show' => $arr_settings_use['settings']['map_show'],
				'map_url' => $arr_settings_use['settings']['map_url'],
				'map_attribution' => $arr_settings_use['settings']['map_attribution'],
				'geo_info_show' => $arr_settings_use['settings']['geo_info_show'],
				'geo_background_color' => $arr_settings_use['settings']['geo_background_color'],
				'geo_mode' => $arr_settings_use['settings']['geo_mode'],
				'geo_display' => $arr_settings_use['settings']['geo_display'],
				'geo_advanced' => $arr_settings_use['settings']['geo_advanced'],
				'social_dot_color' => $arr_settings_use['social']['dot']['color'],
				'social_dot_size_min' => $arr_settings_use['social']['dot']['size']['min'],
				'social_dot_size_max' => $arr_settings_use['social']['dot']['size']['max'],
				'social_dot_size_start' => $arr_settings_use['social']['dot']['size']['start'],
				'social_dot_size_stop' => $arr_settings_use['social']['dot']['size']['stop'],
				'social_dot_stroke_color' => $arr_settings_use['social']['dot']['stroke_color'],
				'social_dot_stroke_width' => $arr_settings_use['social']['dot']['stroke_width'],
				'social_line_show' => $arr_settings_use['social']['line']['show'],
				'social_line_arrowhead_show' => $arr_settings_use['social']['line']['arrowhead_show'],
				'social_force' => $arr_settings_use['social']['force'],
				'social_forceatlas2' => $arr_settings_use['social']['forceatlas2'],
				'social_disconnected_dot_show' => $arr_settings_use['social']['settings']['disconnected_dot_show'],
				'social_include_location_references' => $arr_settings_use['social']['settings']['include_location_references'],
				'social_background_color' => $arr_settings_use['social']['settings']['background_color'],
				'social_display' => $arr_settings_use['social']['settings']['display'],
				'social_static_layout' => $arr_settings_use['social']['settings']['static_layout'],
				'social_static_layout_interval' => $arr_settings_use['social']['settings']['static_layout_interval'],
				'social_advanced' => $arr_settings_use['social']['settings']['social_advanced'],
				'time_bar_color' => $arr_settings_use['time']['bar']['color'],
				'time_bar_opacity' => $arr_settings_use['time']['bar']['opacity'],
				'time_background_color' => $arr_settings_use['time']['settings']['background_color'],
				'time_relative_graph' => $arr_settings_use['time']['settings']['relative_graph'],
				'time_cumulative_graph' => $arr_settings_use['time']['settings']['cumulative_graph']
			];
		}
		
		$arr_settings['capture_settings'] = ($arr_settings['capture_settings'] ? (!is_array($arr_settings['capture_settings']) ? (array)json_decode($arr_settings['capture_settings'], true) : $arr_settings['capture_settings']) : []);
		$arr_settings['location_position'] = ($arr_settings['location_position'] ? (!is_array($arr_settings['location_position']) ? (array)json_decode($arr_settings['location_position'], true) : $arr_settings['location_position']) : []);
		$arr_settings['geo_advanced'] = ($arr_settings['geo_advanced'] ? (!is_array($arr_settings['geo_advanced']) ? (array)json_decode($arr_settings['geo_advanced'], true) : $arr_settings['geo_advanced']) : []);
		$arr_settings['social_force'] = ($arr_settings['social_force'] ? (!is_array($arr_settings['social_force']) ? (array)json_decode($arr_settings['social_force'], true) : $arr_settings['social_force']) : []);
		$arr_settings['social_forceatlas2'] = ($arr_settings['social_forceatlas2'] ? (!is_array($arr_settings['social_forceatlas2']) ? (array)json_decode($arr_settings['social_forceatlas2'], true) : $arr_settings['social_forceatlas2']) : []);
		$arr_settings['social_advanced'] = ($arr_settings['social_advanced'] ? (!is_array($arr_settings['social_advanced']) ? (array)json_decode($arr_settings['social_advanced'], true) : $arr_settings['social_advanced']) : []);
		
		$arr = [
			'capture' => [
				'enable' => (int)((string)$arr_settings['capture_enable'] !== '' ? (bool)$arr_settings['capture_enable'] : false),
				'settings' => [
					'size' => ['width' => (float)($arr_settings['capture_settings']['size']['width'] ?? null ?: 30), 'height' => (float)($arr_settings['capture_settings']['size']['height'] ?? null ?: 20)],
					'resolution' => (int)((string)($arr_settings['capture_settings']['resolution'] ?? '') !== '' ? $arr_settings['capture_settings']['resolution'] : 300)
				]
			],
			'dot' => [
				'show' => (int)((string)$arr_settings['dot_show'] !== '' ? (bool)$arr_settings['dot_show'] : true),
				'color' => ($arr_settings['dot_color'] ?: ''),
				'opacity' => (float)((string)$arr_settings['dot_opacity'] !== '' ? $arr_settings['dot_opacity'] : 1),
				'color_condition' => ($arr_settings['dot_color_condition'] ?: ''),
				'size' => ['min' => (float)($arr_settings['dot_size_min'] ?: 8), 'max' => (float)($arr_settings['dot_size_max'] ?: 20), 'start' => ((int)$arr_settings['dot_size_start'] ?: ''), 'stop' => ((int)$arr_settings['dot_size_stop'] ?: '')],
				'stroke_color' => ($arr_settings['dot_stroke_color'] ?: '#f0f0f0'),
				'stroke_opacity' => (float)($arr_settings['dot_stroke_opacity'] ?: 1),
				'stroke_width' => ((string)$arr_settings['dot_stroke_width'] !== '' ? (float)$arr_settings['dot_stroke_width'] : 1.5)
			],
			'location' => [
				'show' => (int)((string)$arr_settings['location_show'] !== '' ? (bool)$arr_settings['location_show'] : false),
				'color' => ($arr_settings['location_color'] ?: '#000000'),
				'opacity' => (float)($arr_settings['location_opacity'] ?: 1),
				'size' => (float)($arr_settings['location_size'] ?: 8),
				'threshold' => (int)($arr_settings['location_threshold'] ?: 1),
				'offset' => ((string)$arr_settings['location_offset'] !== '' ? (int)$arr_settings['location_offset'] : -5),
				'position' => ['mode' => (int)($arr_settings['location_position']['mode'] ?: 0), 'manual' => (bool)$arr_settings['location_position']['manual']],
				'condition' => ($arr_settings['location_condition'] ?: '')
			],
			'line' => [
				'show' => (int)((string)$arr_settings['line_show'] !== '' ? (bool)$arr_settings['line_show'] : true),
				'color' => ($arr_settings['line_color'] ?: ''),
				'opacity' => (float)($arr_settings['line_opacity'] ?: 1),
				'width' =>  ['min' => (float)($arr_settings['line_width_min'] ?: 2), 'max' => (float)($arr_settings['line_width_max'] ?: 10)],
				'offset' => (int)((string)$arr_settings['line_offset'] !== '' ? $arr_settings['line_offset'] : 6)
			],
			'hint' => [
				'show' => (int)((string)$arr_settings['visual_hints_show'] !== '' ? (bool)$arr_settings['visual_hints_show'] : true),
				'color' => ($arr_settings['visual_hints_color'] ?: '#0092d9'),
				'opacity' => (float)((string)$arr_settings['visual_hints_opacity'] !== '' ? $arr_settings['visual_hints_opacity'] : 1),
				'size' => (float)($arr_settings['visual_hints_size'] ?: 20),
				'stroke_color' => ($arr_settings['visual_hints_stroke_color'] ?: '#ffffff'),
				'stroke_opacity' => (float)($arr_settings['visual_hints_stroke_opacity'] ?: 1),
				'stroke_width' => (float)((string)$arr_settings['visual_hints_stroke_width'] !== '' ? $arr_settings['visual_hints_stroke_width'] : 2),
				'duration' => (float)($arr_settings['visual_hints_duration'] ?: 0.5),
				'delay' => (float)($arr_settings['visual_hints_delay'] ?: 0)
			],
			'geometry' => [
				'show' => (int)((string)$arr_settings['geometry_show'] !== '' ? (bool)$arr_settings['geometry_show'] : true),
				'color' => ($arr_settings['geometry_color'] ?: '#666666'),
				'opacity' => (float)((string)$arr_settings['geometry_opacity'] !== '' ? $arr_settings['geometry_opacity'] : 0.4),
				'stroke_color' => ($arr_settings['geometry_stroke_color'] ?: '#444444'),
				'stroke_opacity' => (float)($arr_settings['geometry_stroke_opacity'] ?: 0.6),
				'stroke_width' => (float)((string)$arr_settings['geometry_stroke_width'] !== '' ? $arr_settings['geometry_stroke_width'] : 1)
			],
			'settings' => [
				'map_show' => (int)((string)$arr_settings['map_show'] !== '' ? (bool)$arr_settings['map_show'] : true),
				'map_url' => ($arr_settings['map_url'] ?: '//mt{s}.googleapis.com/vt?pb=!1m4!1m3!1i{z}!2i{x}!3i{y}!2m3!1e0!2sm!3i278000000!3m14!2sen-US!3sUS!5e18!12m1!1e47!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjE3fHAudjpvZmYscy50OjE4fHAudjpvZmYscy50OjIwfHMuZTpsfHAudjpvZmYscy50OjgxfHAudjpvZmYscy50OjJ8cC52Om9mZixzLnQ6NDl8cC52Om9mZixzLnQ6NTB8cy5lOmx8cC52Om9mZixzLnQ6NHxwLnY6b2ZmLHMudDo2fHMuZTpsfHAudjpvZmY!4e0!20m1!1b1'), // //mt{s}.googleapis.com/vt?lyrs=m@205000000&src=apiv3&hl=en-US&x={x}&y={y}&z={z}&s=Galil&apistyle=p.v%3Aoff%2Cs.t%3A6%7Cp.v%3Aon%7Cp.c%3A%23ffc7d7e4%2Cs.t%3A82%7Cp.v%3Aon%2Cs.t%3A19%7Cp.v%3Aon&style=api%7Csmartmaps
				'map_attribution' => ($arr_settings['map_attribution'] ?: 'Map data ©'.date('Y').' Google'),
				'geo_info_show' => (int)((string)$arr_settings['geo_info_show'] !== '' ? (bool)$arr_settings['geo_info_show'] : false),
				'geo_background_color' => ($arr_settings['geo_background_color'] ?: ''),
				'geo_mode' => (int)((string)$arr_settings['geo_mode'] !== '' ? $arr_settings['geo_mode'] : 1),
				'geo_display' => (int)((string)$arr_settings['geo_display'] !== '' ? $arr_settings['geo_display'] : 1),
				'geo_advanced' => $arr_settings['geo_advanced']
			],
			'social' => [
				'dot' => [
					'color' => ($arr_settings['social_dot_color'] ?: '#ffffff'),
					'size' => ['min' => (float)($arr_settings['social_dot_size_min'] ?: 3), 'max' => (float)($arr_settings['social_dot_size_max'] ?: 20), 'start' => ((int)$arr_settings['social_dot_size_start'] ?: ''), 'stop' => ((int)$arr_settings['social_dot_size_stop'] ?: '')],
					'stroke_color' => ($arr_settings['social_dot_stroke_color'] ?: '#aaaaaa'),
					'stroke_width' => (float)((string)$arr_settings['social_dot_stroke_width'] !== '' ? $arr_settings['social_dot_stroke_width'] : 1)
				],
				'line' => [
					'show' => (int)((string)$arr_settings['social_line_show'] !== '' ? (bool)$arr_settings['social_line_show'] : true),
					'arrowhead_show' => (int)((string)$arr_settings['social_line_arrowhead_show'] !== '' ? (bool)$arr_settings['social_line_arrowhead_show'] : false)
				],
				'force' => [
					'charge' => (int)((string)$arr_settings['social_force']['charge'] !== '' ? $arr_settings['social_force']['charge'] : -40),
					'theta' => (float)((string)$arr_settings['social_force']['theta'] !== '' ? $arr_settings['social_force']['theta'] : 0.8),
					'friction' => (float)((string)$arr_settings['social_force']['friction'] !== '' ? $arr_settings['social_force']['friction'] : 0.2),
					'gravity' => (float)((string)$arr_settings['social_force']['gravity'] !== '' ? $arr_settings['social_force']['gravity'] : 0.08)
				],
				'forceatlas2' => [
					'lin_log_mode' => (bool)(isset($arr_settings['social_forceatlas2']['lin_log_mode']) ? $arr_settings['social_forceatlas2']['lin_log_mode'] : false),
					'outbound_attraction_distribution' => (bool)(isset($arr_settings['social_forceatlas2']['outbound_attraction_distribution']) ? $arr_settings['social_forceatlas2']['outbound_attraction_distribution'] : true),
					'adjust_sizes' => (bool)(isset($arr_settings['social_forceatlas2']['adjust_sizes']) ? $arr_settings['social_forceatlas2']['adjust_sizes'] : false),
					'edge_weight_influence' => (float)((string)$arr_settings['social_forceatlas2']['edge_weight_influence'] !== '' ? $arr_settings['social_forceatlas2']['edge_weight_influence'] : 0),
					'scaling_ratio' => (float)($arr_settings['social_forceatlas2']['scaling_ratio'] ?: 1),
					'strong_gravity_mode' => (bool)(isset($arr_settings['social_forceatlas2']['strong_gravity_mode']) ? $arr_settings['social_forceatlas2']['strong_gravity_mode'] : false),
					'gravity' => (float)((string)$arr_settings['social_forceatlas2']['gravity'] !== '' ? $arr_settings['social_forceatlas2']['gravity'] : 1),
					'slow_down' => (float)((string)$arr_settings['social_forceatlas2']['slow_down'] !== '' ? $arr_settings['social_forceatlas2']['slow_down'] : 1),
					'optimize_theta' => (float)((string)$arr_settings['social_forceatlas2']['optimize_theta'] !== '' ? $arr_settings['social_forceatlas2']['optimize_theta'] : 0.5)
				],
				'settings' => [
					'disconnected_dot_show' => (int)((string)$arr_settings['social_disconnected_dot_show'] !== '' ? (bool)$arr_settings['social_disconnected_dot_show'] : true),
					'include_location_references' => (int)((string)$arr_settings['social_include_location_references'] !== '' ? (bool)$arr_settings['social_include_location_references'] : false),
					'background_color' => ($arr_settings['social_background_color'] ?: ''),
					'display' => (int)((string)$arr_settings['social_display'] !== '' ? $arr_settings['social_display'] : 1),
					'static_layout' => (int)((string)$arr_settings['social_static_layout'] !== '' ? (bool)$arr_settings['social_static_layout'] : false),
					'static_layout_interval' => ((string)$arr_settings['social_static_layout_interval'] !== '' ? (float)$arr_settings['social_static_layout_interval'] : ''),
					'social_advanced' => $arr_settings['social_advanced']
				]
			],
			'time' => [
				'bar' => [
					'color' => ($arr_settings['time_bar_color'] ?: ''),
					'opacity' => (float)($arr_settings['time_bar_opacity'] ?: 0.5)
				],
				'settings' => [
					'background_color' => ($arr_settings['time_background_color'] ?: ''),
					'relative_graph' => (int)((string)$arr_settings['time_relative_graph'] !== '' ? (bool)$arr_settings['time_relative_graph'] : false),
					'cumulative_graph' => (int)((string)$arr_settings['time_cumulative_graph'] !== '' ? (bool)$arr_settings['time_cumulative_graph'] : false)
				]
			]
		];
		
		$arr['dot']['size']['min'] = min($arr['dot']['size']['min'], $arr['dot']['size']['max']);
		$arr['dot']['size']['start'] = min($arr['dot']['size']['start'], $arr['dot']['size']['stop']);
		$arr['line']['width']['min'] = min($arr['line']['width']['min'], $arr['line']['width']['max']);
		$arr['social']['dot']['size']['min'] = min($arr['social']['dot']['size']['min'], $arr['social']['dot']['size']['max']);
		$arr['social']['dot']['size']['start'] = min($arr['social']['dot']['size']['start'], $arr['social']['dot']['size']['stop']);
		
		return $arr;
	}
	
	public static function parseVisualSettingsInputAdvanced($value) {
		
		$arr = [];
		
		if (!$value) {
			return $arr;
		}
		
		$arr_settings = explode(PHP_EOL, $value);
			
		foreach ($arr_settings as $value) {
			
			$num_pos = strpos($value, ':');
			
			if (!$num_pos) {
				continue;
			}
			
			$key_setting = trim(substr($value, 0, $num_pos));
			$value_setting = trim(substr($value, $num_pos + 1));
			
			if ($key_setting && $value_setting != '') {
				$arr[$key_setting] = $value_setting;
			}
		}
		
		return $arr;
	}
	
	public static function parseVisualSettingsOutputAdvanced($arr) {
		
		$str = '';
		
		foreach ($arr as $key => $value) {
			$str .= $key.':'.$value.PHP_EOL;
		}
		
		return $str;
	}
	
	public static function delProjectVisualSettings($project_id, $visual_settings_id) {
		
		$arr_cur = self::getProjectVisualSettings($project_id, false, $visual_settings_id);
			
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_VISUAL_SETTINGS')."
			WHERE project_id = ".(int)$project_id."
				AND id = ".(int)$visual_settings_id."
		");
	}
	
	public static function getProjectVisualSettings($project_id, $user_id = false, $visual_settings_id = false, $arr_use_project_ids = []) {
		
		$is_user_default = ($project_id && $user_id && $visual_settings_id === 0);
	
		$arr = [];

		$res = DB::query("SELECT pvs.*, p.name AS project_name
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_VISUAL_SETTINGS')." pvs
				JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = pvs.project_id)
			WHERE pvs.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id)."
				".(($visual_settings_id || $is_user_default) && !is_array($visual_settings_id) ? "AND pvs.id = ".(int)$visual_settings_id : "")."
				".($visual_settings_id && is_array($visual_settings_id) ? "AND pvs.id IN (".implode(',', arrParseRecursive($visual_settings_id, 'int')).")" : "")."
				".($user_id ? "AND (pvs.user_id = 0 OR pvs.user_id = ".(int)$user_id.")" : "")."
				".(!$user_id && !$visual_settings_id ? "AND pvs.user_id = 0" : "")."
			ORDER BY pvs.name, pvs.user_id
		");		
		
		if (($visual_settings_id || $is_user_default) && !is_array($visual_settings_id)) {
			
			if ($res->getRowCount()) {
				
				$row = $res->fetchAssoc();
				
				$arr_settings = array_slice($row, 5, -1, true);
				array_splice($row, 6, -1);
				$row['settings'] = $arr_settings;
				
				$row['label'] = ($row['user_id'] ? '• ' : '').$row['name'];
				
				$arr = $row;
			}

			return $arr;
		} else {
		
			while($row = $res->fetchAssoc()) {
				
				if ($row['id'] == 0 && !$is_user_default) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_settings = array_slice($row, 5, -1, true);
				array_splice($row, 6, -1);
				$row['settings'] = $arr_settings;
				
				$row['label'] = ($row['user_id'] ? '• ' : '').$row['name'];
				if ($project_id != $row['project_id']) {
					$row['label'] = $row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$row['label'];
				}
				
				$arr[$row['id']] = $row;
			}
			
			return $arr;
		}
	}
	
	// Project Type Conditions
	
	public static function handleProjectTypeCondition($project_id, $user_id, $condition_id, $type_id, $arr, $arr_condition, $arr_model_conditions, $is_domain = false) {
		
		$str_object = ($arr_condition ? value2JSON($arr_condition) : '');
		$str_model_object = ($arr_model_conditions ? value2JSON($arr_model_conditions) : '');
		
		if ($is_domain) {
			$project_id = 0;
			$user_id = 0;
		}
		$is_user_default = ($project_id && $user_id && $condition_id === 0);
		
		if ($condition_id) {
			
			$arr_cur = self::getProjectTypeConditions($project_id, false, false, $condition_id, $is_domain);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$condition_id = false;
			}
			if (!$arr_cur['project_id'] && !$is_domain) {
				error(getLabel('msg_not_allowed'));
			}
		}
		
		
		$sql_new_id = "JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')." pc ON (pc.project_id = 0 OR pc.project_id = p.id)";
		
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')."
			(project_id".", user_id, id, type_id, name, description, object, model_object)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".($condition_id || $is_user_default ? (int)$condition_id : "(SELECT * FROM (SELECT COALESCE(MAX(pc.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							".$sql_new_id."
					) AS pid
				)").",
				".(int)$type_id.",
				'".DBFunctions::strEscape($arr['name'])."',
				'".DBFunctions::strEscape($arr['description'])."',
				'".DBFunctions::strEscape($str_object)."',
				'".DBFunctions::strEscape($str_model_object)."'
			)
			".DBFunctions::onConflict('project_id'.', user_id, id, type_id', ['name', 'description', 'object', 'model_object'])."
		");
		
		if ($is_user_default) {
			
			return $res->getAffectedRowCount();
		}
		
		if (!$condition_id && !$is_user_default) {
			
			$arr_cur = self::getProjectTypeConditions($project_id, $user_id, $type_id, false, $is_domain);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$condition_id = $arr_cur['id'];
		}
		
		return $condition_id;
	}
	
	public static function delProjectTypeCondition($project_id, $user_id, $condition_id, $is_domain = false, $arr_use_project_ids = []) {
		
		$arr_cur = self::getProjectTypeConditions($project_id, $user_id, false, $condition_id, $is_domain);
		if (!$arr_cur['project_id'] && !$is_domain) {
			error(getLabel('msg_not_allowed'));
		}
			
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')."
			WHERE (
					".($project_id ? "project_id = ".(int)$project_id : "")."
					".($is_domain ? ($project_id ? "OR " : "")."project_id = 0" : '')."
				)
				AND id = ".(int)$condition_id."
				".($user_id ? "AND user_id = ".(int)$user_id : "")."
		");
	}
	
	public static function getProjectTypeConditions($project_id, $user_id = false, $type_id = false, $condition_id = false, $is_domain = false, $arr_use_project_ids = []) {
		
		// $condition_id = false (all stored features) / 0 (default user only) / array (any)
		
		$is_user_default = ($project_id && $user_id && $condition_id === 0);
	
		$arr = [];
		
		$sql_condition_id = '';
		$sql_use_project_id = '';
		$sql_user_id = '';
		$sql_type_id = '';
		
		if (($condition_id || $is_user_default) && !is_array($condition_id)) {
			$sql_condition_id = "AND pc.id = ".(int)$condition_id;
		} else if ($condition_id && is_array($condition_id)) {
			$sql_condition_id = "AND pc.id IN (".implode(',', arrParseRecursive($condition_id, 'int')).")";
		}
		if ($arr_use_project_ids) {
			$sql_use_project_id = "AND (pc.id != 0 OR pc.project_id = ".(int)$project_id.")"; // User default conditions (id 0) are only valid within the specified project
		}
		if ($user_id) {
			$sql_user_id = "AND (pc.user_id = 0 OR pc.user_id = ".(int)$user_id.")";
		} else if (!$condition_id) {
			$sql_user_id = "AND pc.user_id = 0";
		}
		if ($type_id) {
			$sql_type_id = "AND pc.type_id = ".(int)$type_id;
		}

		$res = DB::query("SELECT pc.*, p.name AS project_name
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')." pc
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = pc.project_id)
			WHERE (
					".($project_id ? "pc.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id) : "")."
					".($is_domain ? ($project_id ? "OR " : "")."pc.project_id = 0" : '')."
				)
				AND (p.id != 0 OR pc.project_id = 0)
				".$sql_condition_id."
				".$sql_use_project_id."
				".$sql_user_id."
				".$sql_type_id."
			".(!$condition_id ? "ORDER BY pc.name, pc.user_id" : "")."
		");		
		
		if (($condition_id || $is_user_default) && !is_array($condition_id)) {
			
			if ($res->getRowCount()) {
				
				$arr = $res->fetchAssoc();
				$arr['label'] = ($arr['user_id'] ? '• ' : '').$arr['name'];
				$arr['object'] = (array)json_decode($arr['object'], true);
				$arr['model_object'] = (array)json_decode($arr['model_object'], true);
			}

			return $arr;
		} else {
		
			while ($arr_row = $res->fetchAssoc()) {
				
				if ($arr_row['id'] == 0 && ($condition_id === false || $condition_id === 0)) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id) { // Do grouping
					if ($is_domain && !$arr_row['project_id']) {
						$arr_row['label'] = getLabel('lbl_clearance_admin').cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
					} else if ($project_id != $arr_row['project_id']) {
						$arr_row['label'] = $arr_row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
					}
				}
				
				$arr_row['object'] = (array)json_decode($arr_row['object'], true);
				$arr_row['model_object'] = (array)json_decode($arr_row['model_object'], true);
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// Project Type Analyses
	
	public static function handleProjectTypeAnalysis($project_id, $user_id, $analysis_id, $type_id, $arr, $arr_analysis) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES';
		
		$str_object = ($arr_analysis['settings'] ? value2JSON($arr_analysis['settings']) : '');
		$str_scope_object = ($arr_analysis['scope'] ? value2JSON($arr_analysis['scope']) : '');
		
		$arr_sql_store = [
			'algorithm' => "'".DBFunctions::strEscape($arr_analysis['algorithm'])."'",
			'object' => "'".DBFunctions::strEscape($str_object)."'",
			'scope_object' => "'".DBFunctions::strEscape($str_scope_object)."'"
		];
		
		return self::handleProjectTypeFeature($table_name, $project_id, $user_id, $analysis_id, $type_id, $arr, $arr_sql_store);
	}
	
	public static function delProjectTypeAnalysis($project_id, $analysis_id) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES';
		
		self::delProjectTypeFeature($table_name, $project_id, $analysis_id);
	}
	
	public static function getProjectTypeAnalyses($project_id, $user_id = false, $type_id = false, $analysis_id = false, $arr_use_project_ids = []) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES';
		
		$func_parse = function(&$arr) {
			
			$arr['settings'] = (array)json_decode($arr['object'], true);
			$arr['scope'] = (array)json_decode($arr['scope_object'], true);
		};
		
		return self::getProjectTypeFeatures($table_name, $func_parse, $project_id, $user_id, $type_id, $analysis_id, $arr_use_project_ids);
	}
	
	// Project Type Analyses Contexts
	
	public static function handleProjectTypeAnalysisContext($project_id, $user_id, $analysis_context_id, $type_id, $arr, $arr_analysis_context) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES_CONTEXTS';
		
		$str_object = value2JSON($arr_analysis_context);
		
		$arr_sql_store = [
			'object' => "'".DBFunctions::strEscape($str_object)."'"
		];
		
		return self::handleProjectTypeFeature($table_name, $project_id, $user_id, $analysis_context_id, $type_id, $arr, $arr_sql_store);
	}
	
	public static function delProjectTypeAnalysisContext($project_id, $analysis_context_id) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES_CONTEXTS';
		
		self::delProjectTypeFeature($table_name, $project_id, $analysis_context_id);
	}
	
	public static function getProjectTypeAnalysesContexts($project_id, $user_id = false, $type_id = false, $analysis_context_id = false, $arr_use_project_ids = []) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_ANALYSES_CONTEXTS';
		
		$func_parse = function(&$arr) {
			
			$arr['object'] = json_decode($arr['object'], true);
		};
		
		return self::getProjectTypeFeatures($table_name, $func_parse, $project_id, $user_id, $type_id, $analysis_context_id, $arr_use_project_ids);
	}
	
	// Project Type Export Settings
	
	public static function handleProjectTypeExportSettings($project_id, $user_id, $export_settings_id, $type_id, $arr, $arr_export_settings) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_EXPORT_SETTINGS';
		
		$str_format_object = ($arr_export_settings['format']['settings'] ? value2JSON($arr_export_settings['format']['settings']) : '');
		$str_scope_object = ($arr_export_settings['scope'] ? value2JSON($arr_export_settings['scope']) : '');
		
		$arr_sql_store = [
			'format_type' => "'".DBFunctions::strEscape($arr_export_settings['format']['type'])."'",
			'format_include_description_name' => ($arr_export_settings['format']['include_description_name'] !== null ? DBFunctions::escapeAs($arr_export_settings['format']['include_description_name'], DBFunctions::TYPE_BOOLEAN) : 'NULL'),
			'format_object' => "'".DBFunctions::strEscape($str_format_object)."'",
			'scope_object' => "'".DBFunctions::strEscape($str_scope_object)."'"
		];
		
		return self::handleProjectTypeFeature($table_name, $project_id, $user_id, $export_settings_id, $type_id, $arr, $arr_sql_store);
	}
	
	public static function delProjectTypeExportSettings($project_id, $export_settings_id) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_EXPORT_SETTINGS';
		
		self::delProjectTypeFeature($table_name, $project_id, $export_settings_id);
	}
	
	public static function getProjectTypeExportSettings($project_id, $user_id = false, $type_id = false, $export_settings_id = false, $arr_use_project_ids = []) {
		
		$table_name = 'DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_EXPORT_SETTINGS';
		
		$func_parse = function(&$arr) {
			
			$arr['format_settings'] = (array)json_decode($arr['format_object'], true);
			$arr['scope'] = (array)json_decode($arr['scope_object'], true);
		};
		
		return self::getProjectTypeFeatures($table_name, $func_parse, $project_id, $user_id, $type_id, $export_settings_id, $arr_use_project_ids);
	}
	
	// Features handlers
	
	protected static function handleProjectTypeFeature($table_name, $project_id, $user_id, $feature_id, $type_id, $arr, $arr_sql_store) {
				
		$is_user_default = ($project_id && $user_id && $feature_id === 0);
		
		if ($feature_id) {
			
			$arr_cur = self::getProjectTypeFeatures($table_name, false, $project_id, false, false, $feature_id);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$feature_id = false;
			}
		}
		
		$arr_columns_conflict = ['name', 'description'];
		$arr_columns_conflict = array_merge($arr_columns_conflict, array_keys($arr_sql_store));

		$res = DB::query("INSERT INTO ".DB::getTable($table_name)."
			(project_id, user_id, id, type_id, name, description,
				".implode(',', array_keys($arr_sql_store))."
			)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".($feature_id || $is_user_default ? (int)$feature_id : "(SELECT * FROM (SELECT COALESCE(MAX(pfeat.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p2 ON (p2.user_id = p.user_id)
							JOIN ".DB::getTable($table_name)." pfeat ON (pfeat.project_id = p2.id)
						WHERE p.id = ".(int)$project_id."
					) AS pid
				)").",
				".(int)$type_id.",
				'".DBFunctions::strEscape($arr['name'])."',
				'".DBFunctions::strEscape($arr['description'])."',
				".implode(',', $arr_sql_store)."
			)
			".DBFunctions::onConflict('project_id, user_id, id, type_id', $arr_columns_conflict)."
		");
		
		if ($is_user_default) {
			
			return $res->getAffectedRowCount();
		}
		
		if (!$feature_id && !$is_user_default) {
			
			$arr_cur = self::getProjectTypeFeatures($table_name, false, $project_id, $user_id, $type_id, false);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$feature_id = $arr_cur['id'];
		}
		
		return $feature_id;
	}
	
	public static function delProjectTypeFeature($table_name, $project_id, $feature_id) {
		
		$arr_cur = self::getProjectTypeFeatures($table_name, false, $project_id, false, false, $feature_id);
			
		$res = DB::query("DELETE FROM ".DB::getTable($table_name)."
			WHERE project_id = ".(int)$project_id."
				AND id = ".(int)$feature_id."
		");
	}
	
	public static function getProjectTypeFeatures($table_name, $func_parse, $project_id, $user_id = false, $type_id = false, $feature_id = false, $arr_use_project_ids = []) {
		
		// $feature_id = false (all stored features) / 0 (default user only) / array (any)
		
		$is_user_default = ($project_id && $user_id && $feature_id === 0);

		$arr = [];

		$res = DB::query("SELECT pfeat.*, p.name AS project_name
				FROM ".DB::getTable($table_name)." pfeat
				JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = pfeat.project_id)
			WHERE pfeat.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id)."
				".(($feature_id || $is_user_default) && !is_array($feature_id) ? "AND pfeat.id = ".(int)$feature_id : "")."
				".($feature_id && is_array($feature_id) ? "AND pfeat.id IN (".implode(',', arrParseRecursive($feature_id, 'int')).")" : "")."
				".($user_id ? "AND (pfeat.user_id = 0 OR pfeat.user_id = ".(int)$user_id.")" : "")."
				".(!$user_id && !$feature_id ? "AND pfeat.user_id = 0" : "")."
				".($type_id ? "AND pfeat.type_id = ".(int)$type_id : "")."
			ORDER BY pfeat.name, pfeat.user_id
		");
		
		if (($feature_id || $is_user_default) && !is_array($feature_id)) {
			
			if ($res->getRowCount()) {
				
				$arr = $res->fetchAssoc();
				
				$arr['label'] = ($arr['user_id'] ? '• ' : '').$arr['name'];
				
				if ($func_parse) {
					$func_parse($arr);
				}
			}

			return $arr;
		} else {
		
			while ($arr_row = $res->fetchAssoc()) {
				
				if ($arr_row['id'] == 0 && ($feature_id === false || $feature_id === 0)) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id) { // Do grouping
					if ($project_id != $arr_row['project_id']) {
						$arr_row['label'] = $arr_row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$arr_row['label'];
					}
				}
				
				if ($func_parse) {
					$func_parse($arr_row);
				}
				
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// Project Type Scenarios
	
	public static function handleProjectTypeScenario($project_id, $user_id, $scenario_id, $type_id, $arr, $arr_options) {
		
		if ($scenario_id) {
			
			$arr_cur = self::getProjectTypeScenarios($project_id, false, false, $scenario_id);
			
			if (!$arr_cur || $arr_cur['user_id'] != $user_id) { // Create new when not found (i.e. changed user scope)
				$scenario_id = false;
			}
		}
				
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIOS')."
			(project_id, user_id, id, name, type_id, description, attribution, filter_id, filter_use_current, scope_id, scope_use_current, condition_id, condition_use_current, context_id, context_use_current, frame_id, frame_use_current, visual_settings_id, visual_settings_use_current, analysis_id, analysis_use_current, analysis_context_id, analysis_context_use_current, cache_retain)
				VALUES 
			(
				".(int)$project_id.",
				".(int)$user_id.",
				".($scenario_id ? (int)$scenario_id : "(SELECT * FROM (SELECT COALESCE(MAX(psc.id), 0)+1
							FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p2 ON (p2.user_id = p.user_id)
							JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIOS')." psc ON (psc.project_id = p2.id)
						WHERE p.id = ".(int)$project_id."
					) AS pid
				)").",
				'".DBFunctions::strEscape($arr['name'])."',
				".(int)$type_id.",
				'".DBFunctions::strEscape($arr['description'])."',
				'".DBFunctions::strEscape($arr['attribution'])."',
				".(int)($arr_options['filter_use_current'] ? 0 : $arr_options['filter_id']).",
				".(int)$arr_options['filter_use_current'].",
				".(int)($arr_options['scope_use_current'] ? 0 : $arr_options['scope_id']).",
				".(int)$arr_options['scope_use_current'].",
				".(int)($arr_options['condition_use_current'] ? 0 : $arr_options['condition_id']).",
				".(int)$arr_options['condition_use_current'].",
				".(int)($arr_options['context_use_current'] ? 0 : $arr_options['context_id']).",
				".(int)$arr_options['context_use_current'].",
				".(int)($arr_options['frame_use_current'] ? 0 : $arr_options['frame_id']).",
				".(int)$arr_options['frame_use_current'].",
				".(int)($arr_options['visual_settings_use_current'] ? 0 : $arr_options['visual_settings_id']).",
				".(int)$arr_options['visual_settings_use_current'].",
				".(int)($arr_options['analysis_use_current'] ? 0 : $arr_options['analysis_id']).",
				".(int)$arr_options['analysis_use_current'].",
				".(int)($arr_options['analysis_context_use_current'] ? 0 : $arr_options['analysis_context_id']).",
				".(int)$arr_options['analysis_context_use_current'].",
				".(int)$arr_options['cache_retain']."
			)
			".DBFunctions::onConflict('project_id, id', ['user_id', 'name', 'type_id', 'description',
				'attribution', 'filter_id', 'filter_use_current', 'scope_id', 'scope_use_current', 'condition_id', 'condition_use_current', 'context_id', 'context_use_current', 'frame_id', 'frame_use_current', 'visual_settings_id', 'visual_settings_use_current', 'analysis_id', 'analysis_use_current', 'analysis_context_id', 'analysis_context_use_current', 'cache_retain'
			])."
		");
		
		if (!$scenario_id) {
			
			$arr_cur = self::getProjectTypeScenarios($project_id, $user_id, $type_id, false);
			ksort($arr_cur);
			$arr_cur = end($arr_cur);
			
			$scenario_id = $arr_cur['id'];
		}
		
		return $scenario_id;
	}
		
	public static function delProjectTypeScenario($project_id, $scenario_id) {
		
		$arr_cur = self::getProjectTypeScenarios($project_id, false, false, $scenario_id);
			
		$res = DB::queryMulti("
			DELETE FROM ".DB::getTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE')."
				WHERE project_id = ".(int)$project_id."
					AND scenario_id = ".(int)$scenario_id."
			;
			
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIOS')."
				WHERE project_id = ".(int)$project_id."
					AND id = ".(int)$scenario_id."
			;
		");
	}
	
	public static function getProjectTypeScenarios($project_id, $user_id = false, $type_id = false, $scenario_id = false, $arr_use_project_ids = []) {
		
		if ($scenario_id && !is_array($scenario_id)) {
			
			$str_identifier = $project_id.'_'.(int)$scenario_id;
			
			$cache = self::getCache($str_identifier);
			if ($cache) {
				return $cache;
			}
		}
	
		$arr = [];

		$res = DB::query("SELECT psc.*, p.name AS project_name
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIOS')." psc
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = psc.project_id)
				WHERE psc.project_id ".($arr_use_project_ids ? "IN (".(int)$project_id.", ".implode(',', arrParseRecursive($arr_use_project_ids, 'int')).")" : "= ".(int)$project_id)."
					".($scenario_id && !is_array($scenario_id) ? "AND psc.id = ".(int)$scenario_id : "")."
					".($scenario_id && is_array($scenario_id) ? "AND psc.id IN (".implode(',', arrParseRecursive($scenario_id, 'int')).")" : "")."
					".($user_id ? "AND (psc.user_id = 0 OR psc.user_id = ".(int)$user_id.")" : "")."
					".(!$user_id && !$scenario_id ? "AND psc.user_id = 0" : "")."
					".($type_id ? "AND psc.type_id = ".(int)$type_id : "")."
				ORDER BY psc.name, psc.user_id
		");		
		
		if ($str_identifier) {
			
			if ($res->getRowCount()) {
				$arr = $res->fetchAssoc();
				$arr['label'] = ($arr['user_id'] ? '• ' : '').$arr['name'];
			}
			
			self::setCache($str_identifier, $arr);

			return $arr;
		} else {
		
			while($row = $res->fetchAssoc()) {
				
				$row['label'] = ($row['user_id'] ? '• ' : '').$row['name'];
				if ($project_id != $row['project_id']) {
					$row['label'] = $row['project_name'].cms_general::OPTION_GROUP_SEPARATOR.$row['label'];
				}
				
				$arr[$row['type_id']][$row['id']] = $row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// User related (i.e. following) project type filters
	
	public static function getUserProjectTypeFilters($user_id, $type_id = false) {
		
		$arr = [];
		
		$res = DB::query("SELECT upf.*, pf.type_id
				FROM ".DB::getTable('USER_LINK_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')." upf
				JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')." pf ON (pf.project_id = upf.project_id AND pf.id = upf.filter_id)
			WHERE TRUE
				".($user_id ? "AND upf.user_id = ".(int)$user_id : "")."
				".($type_id ? "AND pf.type_id = ".(int)$type_id : "")."
		");		

		while($row = $res->fetchAssoc()) {
			$row['source'] = ($row['source'] ?: 3);
			if ($type_id && !$user_id) {
				$arr[$row['project_id']][$row['filter_id']][$row['user_id']] = $row;
			} else if ($type_id) {
				$arr[$row['project_id']][$row['filter_id']] = $row;
			} else if ($user_id) {
				$arr[$row['project_id']][$row['type_id']][$row['filter_id']] = $row;
			}
		}
		
		return $arr;
	}
	
	public static function updateUserProjectTypeFilters($user_id, $arr_project_type_filters) {

		$arr_sql = [];
		
		foreach ((array)$arr_project_type_filters as $project_id => $arr_type_filters) {
			foreach ($arr_type_filters as $arr_type_filter) {
				
				if (!$arr_type_filter['filter_id']) {
					continue;
				}
				
				foreach ($arr_type_filter['filter_id'] as $value) {
					$arr_sql[(int)$project_id.'_'.(int)$value] = "(".(int)$user_id.", ".(int)$project_id.", ".(int)$value.", ".(int)$arr_type_filter['source'].")";
				}
			}
		}
		
		$res = DB::query("DELETE FROM ".DB::getTable('USER_LINK_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')."
			WHERE user_id = ".(int)$user_id."
		");
		
		if ($arr_sql) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('USER_LINK_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')."
				(user_id, project_id, filter_id, source)
					VALUES
				".implode(',', $arr_sql)."
			");
		}
	}

	public static function runUserProjectTypeFilterUpdates($arr_options = []) {
		
		$arr_filter_object_date = ['start' => $arr_options['date_executed']['previous'], 'end' => $arr_options['date_executed']['now']];
		
		$arr_types_changed = FilterTypeObjects::getTypesUpdatedAfter($arr_options['date_executed']['previous']);
		$arr_updated_types_projects_filters_users = [];
				
		foreach ($arr_types_changed as $type_id => $arr_type) {
				
			$arr_user_project_type_filtering = self::getUserProjectTypeFilters(false, $type_id);
			
			if (!$arr_user_project_type_filtering) {
				continue;
			}
			
			$filter_version = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
			$filter_version->setFilter([
				'object_dating' => ['date' => $arr_filter_object_date]
			]);
			$arr_count = $filter_version->getResultInfo();
			
			if (!$arr_count['total_filtered']) {
				continue;
			}
			
			$table_name_to_temp = $filter_version->storeResultTemporarily(false, true);
			
			foreach ($arr_user_project_type_filtering as $project_id => $arr_project_type_filtering) {
				
				$arr_project = StoreCustomProject::getProjects($project_id);
				$arr_type_filter_default = ($arr_project['types'][$type_id]['type_filter_id'] ? self::getProjectTypeFilters($project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true) : false);
				
				$arr_project_type_filters = self::getProjectTypeFilters($project_id, false, $type_id, array_unique(array_keys($arr_project_type_filtering)));
				
				foreach ($arr_project_type_filters as $filter_id => $arr_project_type_filter) {
					
					try {
							
						$arr_source_user_ids = [];
						foreach ($arr_project_type_filtering[$filter_id] as $user_id => $value) {
							$arr_source_user_ids[$value['source']][$user_id] = $user_id;
						}
						
						foreach ([1, 2] as $source) {
							
							$arr_user_ids = ($arr_source_user_ids[$source] ?: []);
							if ($arr_source_user_ids[3]) { // Include users that match any (source = 3)
								$arr_user_ids = $arr_user_ids + $arr_source_user_ids[3];
							}
								
							$cur_sql_filter = '';
							$match = false;
							
							foreach ($arr_user_ids as $user_id) {
								
								$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
								$filter->setScope(['users' => $user_id, 'types' => StoreCustomProject::getScopeTypes($project_id), 'project_id' => $project_id]);
								if ($arr_type_filter_default) {
									$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter_default['object']), $arr_project['types'][$type_id]['type_filter_object_subs']);
								}
								$arr_filter = FilterTypeObjects::convertFilterInput($arr_project_type_filter['object']); // Prefilter
								
								$arr_filter['table'] = $table_name_to_temp;
								if ($source == 1) {
									$arr_filter['object_versioning']['date'] = $arr_filter_object_date;
								}
								if ($source == 2) {
									$arr_filter['object_discussion']['date'] = $arr_filter_object_date;
								}
								$filter->setFilter($arr_filter);
								$sql_filter = $filter->sqlQuery('object_ids');
								
								if ($sql_filter != $cur_sql_filter) { // Reuse the existing filter result when there is no user specific filtering taking place
		
									$match = $filter->init();
									$cur_sql_filter = $sql_filter;
								}
								
								if ($match) {
									
									unset($arr_source_user_ids[3][$user_id]); // Any match (source = 3) has been matched!
									
									Labels::setVariable('name', $arr_project_type_filter['label']);
									Labels::setVariable('link', pages::getModUrl(pages::getClosestMod('data_entry'), false, ['project.v', $project_id]).'filter.v/'.$filter_id);
									cms_messaging::sendMessage('filter_'.$project_id.'_'.$filter_id, 0, getLabel('msg_filter_notify_title', 'L', true), getLabel('msg_filter_notify', 'L', true), $user_id, false, ['individual' => true, 'limit' => 15]);
								
									$arr_updated_types_projects_filters_users[$type_id][$project_id][$filter_id][$user_id] = true;
								}
							}
						}
					} catch (Exception $e) {
						
						error(__METHOD__.' ERROR:'.PHP_EOL
							.'	Type = '.$type_id.' Custom Project = '.$project_id.' Filter = '.$filter_id
						, TROUBLE_NOTICE, LOG_BOTH, false, $e); // Make notice
					}
				}
			}
		}
		
		if ($arr_updated_types_projects_filters_users) {
			
			$count_types = 0;
			$count_projects = 0;
			$count_filters = 0;
			$count_users = 0;
			
			foreach ($arr_updated_types_projects_filters_users as $type_id => $arr_projects_filters_users) {
				
				$count_types++;
				
				foreach ($arr_projects_filters_users as $project_id => $arr_filters_users) {
					
					$count_projects++;
					
					foreach ($arr_filters_users as $filter_id => $users) {
						
						$count_filters++;
						$count_users += count($users);
					}
				}
			}
			
			msg(__METHOD__.' SUCCESS:'.PHP_EOL.
					'Types = '.$count_types.' Custom Projects = '.$count_projects.' Filters = '.$count_filters.' Users = '.$count_users
			);
		}
	}
}
