<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECTS', DB::$database_home.'.def_nodegoat_custom_projects');
DB::setTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES', DB::$database_home.'.def_nodegoat_custom_project_types');
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

	public static function handleProject($project_id, $arr) {
		
		if (!$project_id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')."
				(name, full_scope, source_referencing, discussion_provide, visual_settings_id)
					VALUES
				(
					'".DBFunctions::strEscape($arr['name'])."',
					".(int)$arr['full_scope'].",
					".(int)$arr['source_referencing'].",
					".(int)$arr['discussion_provide'].",
					".(int)$arr['visual_settings_id']."
				)
			");
			
			$project_id = DB::lastInsertID();
		} else {
						
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." SET
					name = '".DBFunctions::strEscape($arr['name'])."',
					full_scope = ".(int)$arr['full_scope'].",
					source_referencing = ".(int)$arr['source_referencing'].",
					discussion_provide = ".(int)$arr['discussion_provide'].",
					visual_settings_id = ".(int)$arr['visual_settings_id']."
				WHERE id = ".(int)$project_id."
			");
		}
													
		$arr_sql_keys = [];

		if ($arr['types']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['types'] as $type_id) {
				
				$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$type_id.")";
				$arr_sql_keys['types'][] = (int)$type_id;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')."
				(project_id, type_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('project_id, type_id', ['project_id'])."
			");

			$i = 0;
			
			foreach ((array)$arr['types_organise'] as $str_type_id => $arr_definition) {
				
				$type_id = explode('-', $str_type_id);
				$type_id = (int)$type_id[1];
				
				$has_configuration = ($arr_definition['configuration']['object_descriptions'] || $arr_definition['configuration']['object_sub_details']);
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." SET
						color = '".str2Color($arr_definition['color'])."',
						type_definition_id = ".(int)$arr_definition['type_definition_id'].",
						type_filter_id = ".(int)$arr_definition['type_filter_id'].",
						type_filter_object_subs = ".(int)$arr_definition['type_filter_object_subs'].",
						type_context_id = ".(int)$arr_definition['type_context_id'].",
						type_frame_id = ".(int)$arr_definition['type_frame_id'].",
						type_condition_id = ".(int)$arr_definition['type_condition_id'].",
						configuration_exclude = ".($has_configuration && $arr_definition['configuration_exclude'] ? 1 : 0).",
						sort = ".$i."
					WHERE project_id = ".(int)$project_id." AND type_id = ".(int)$type_id."
				");
				
				$i++;
				
				// Type - configuration
				
				if ($has_configuration) {
						
					$arr_sql_insert = [];
						
					foreach ((array)$arr_definition['configuration']['object_descriptions'] as $object_description_id => $arr_configuration_object_description) {
						
						if ($arr_definition['configuration_exclude']) {
							$view = ($arr_configuration_object_description['view'] ? 1 : 0);
							$edit = ($view || $arr_configuration_object_description['edit'] ? 1 : 0);
						} else {
							$edit = ($arr_configuration_object_description['edit'] ? 1 : 0);
							$view = ($edit || $arr_configuration_object_description['view'] ? 1 : 0);
						}
					
						$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$type_id.", ".(int)$object_description_id.", 0, 0, ".$edit.", ".$view.")";
						$arr_sql_keys['configuration'][] = "(type_id = ".(int)$type_id." AND object_description_id = ".(int)$object_description_id." AND object_sub_details_id = 0 AND object_sub_description_id = 0)";
					}
					
					foreach ((array)$arr_definition['configuration']['object_sub_details'] as $object_sub_details_id => $arr_configuration_object_sub_details) {
						
						if ($arr_configuration_object_sub_details['object_sub_details']) {
							
							if ($arr_definition['configuration_exclude']) {
								$view = ($arr_configuration_object_sub_details['object_sub_details']['view'] ? 1 : 0);
								$edit = ($view || $arr_configuration_object_sub_details['object_sub_details']['edit'] ? 1 : 0);
							} else {
								$edit = ($arr_configuration_object_sub_details['object_sub_details']['edit'] ? 1 : 0);
								$view = ($edit || $arr_configuration_object_sub_details['object_sub_details']['view'] ? 1 : 0);
							}
							
							$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$type_id.", 0, ".(int)$object_sub_details_id.", 0, ".$edit.", ".$view.")";
							$arr_sql_keys['configuration'][] = "(type_id = ".(int)$type_id." AND object_description_id = 0 AND object_sub_details_id = ".(int)$object_sub_details_id." AND object_sub_description_id = 0)";
						}
						
						foreach ((array)$arr_configuration_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_configuration_object_sub_description) {
							
							if ($arr_definition['configuration_exclude']) {
								$view = ($arr_configuration_object_sub_description['view'] ? 1 : 0);
								$edit = ($view || $arr_configuration_object_sub_description['edit'] ? 1 : 0);
							} else {
								$edit = ($arr_configuration_object_sub_description['edit'] ? 1 : 0);
								$view = ($edit || $arr_configuration_object_sub_description['view'] ? 1 : 0);
							}
							
							$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$type_id.", 0, ".(int)$object_sub_details_id.", ".(int)$object_sub_description_id.", ".$edit.", ".$view.")";
							$arr_sql_keys['configuration'][] = "(type_id = ".(int)$type_id." AND object_description_id = 0 AND object_sub_details_id = ".(int)$object_sub_details_id." AND object_sub_description_id = ".(int)$object_sub_description_id.")";
						}
					}
					
					$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION')."
						(project_id, type_id, object_description_id, object_sub_details_id, object_sub_description_id, edit, view)
							VALUES
						".implode(",", $arr_sql_insert)."
						".DBFunctions::onConflict('project_id, type_id, object_description_id, object_sub_details_id, object_sub_description_id', ['edit', 'view'])."
					");
				}	
				
				// Type - include referenced types
				
				foreach ((array)$arr_definition['include_referenced_types'] as $ref_type_id => $arr_referenced) {
					
					$arr_sql_insert = [];
					
					foreach ((array)$arr_referenced['object_descriptions'] as $object_description_id => $arr_referenced_object_description) {
						
						$edit = ($arr_referenced_object_description['edit'] ? 1 : 0);
						$view = ($arr_referenced_object_description['view'] ? 1 : 0);
				
						$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$type_id.", ".(int)$ref_type_id.", ".(int)$object_description_id.", 0, 0, ".$edit.", ".$view.")";
						$arr_sql_keys['include_referenced'][] = "(type_id = ".(int)$type_id." AND referenced_type_id = ".(int)$ref_type_id." AND object_description_id = ".(int)$object_description_id." AND object_sub_details_id = 0 AND object_sub_description_id = 0)";
					}
					
					foreach ((array)$arr_referenced['object_sub_details'] as $object_sub_details_id => $arr_referenced_object_sub_details) {
						foreach ($arr_referenced_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_referenced_object_sub_description) {
							
							$edit = ($arr_referenced_object_sub_description['edit'] ? 1 : 0);
							$view = ($arr_referenced_object_sub_description['view'] ? 1 : 0);
							
							$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$type_id.", ".(int)$ref_type_id.", 0, ".(int)$object_sub_details_id.", ".(int)$object_sub_description_id.", ".$edit.", ".$view.")";
							$arr_sql_keys['include_referenced'][] = "(type_id = ".(int)$type_id." AND referenced_type_id = ".(int)$ref_type_id." AND object_description_id = 0 AND object_sub_details_id = ".(int)$object_sub_details_id." AND object_sub_description_id = ".(int)$object_sub_description_id.")";
						}
					}
					
					$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')."
						(project_id, type_id, referenced_type_id, object_description_id, object_sub_details_id, object_sub_description_id, edit, view)
							VALUES
						".implode(",", $arr_sql_insert)."
						".DBFunctions::onConflict('project_id, type_id, referenced_type_id, object_description_id, object_sub_details_id, object_sub_description_id', ['edit', 'view'])."
					");
				}
			}
		}
		
		if ($arr['location_types']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['location_types'] as $value) {
				
				$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$value.")";
				$arr_sql_keys['location_types'][] = (int)$value;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES')."
				(project_id, type_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('project_id, type_id', ['project_id'])."
			");
		}
		
		if ($arr['source_types']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['source_types'] as $value) {
				
				$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$value.")";
				$arr_sql_keys['source_types'][] = (int)$value;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES')."
				(project_id, type_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('project_id, type_id', ['project_id'])."
			");
		}
		
		if ($arr['use_projects']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['use_projects'] as $value) {
				
				$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$value.")";
				$arr_sql_keys['use_projects'][] = (int)$value;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_USE_PROJECTS')." (project_id, use_project_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('project_id, use_project_id', ['project_id'])."
			");
		}
		
		// Cleanup
		
		$res = DB::queryMulti("
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')."
				WHERE project_id = ".(int)$project_id."
					".($arr_sql_keys['types'] ? "AND type_id NOT IN (".implode(",", $arr_sql_keys['types']).")" : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION')."
				WHERE project_id = ".(int)$project_id."
					".($arr_sql_keys['configuration'] ? "AND NOT ".implode(" AND NOT ", $arr_sql_keys['configuration']) : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')."
				WHERE project_id = ".(int)$project_id."
					".($arr_sql_keys['include_referenced'] ? "AND NOT ".implode(" AND NOT ", $arr_sql_keys['include_referenced']) : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES')."
				WHERE project_id = ".(int)$project_id."
					".($arr_sql_keys['location_types'] ? "AND type_id NOT IN (".implode(",", $arr_sql_keys['location_types']).")" : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES')."
				WHERE project_id = ".(int)$project_id."
					".($arr_sql_keys['source_types'] ? "AND type_id NOT IN (".implode(",", $arr_sql_keys['source_types']).")" : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_USE_PROJECTS')."
				WHERE project_id = ".(int)$project_id."
					".($arr_sql_keys['use_projects'] ? "AND use_project_id NOT IN (".implode(",", $arr_sql_keys['use_projects']).")" : "")."
			;
		");
		
		return $project_id;
	}
	
	public static function addProjectTypes($project_id, $arr_type_ids) {
		
		if (!is_array($arr_type_ids)) {
			$arr_type_ids = [$arr_type_ids];
		}
		
		$arr_sql_insert = [];
		
		foreach ($arr_type_ids as $type_id) {
			
			$arr_sql_insert[] = "(".(int)$project_id.", ".(int)$type_id.")";
		}
		
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')."
			(project_id, type_id)
				VALUES
			".implode(",", $arr_sql_insert)."
		");
	}
	
	public static function delProject($project_id) {
		
		$res = DB::queryMulti("
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." WHERE id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." WHERE project_id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')." WHERE project_id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIOS')." WHERE project_id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE')." WHERE project_id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')." WHERE project_id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES')." WHERE project_id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCOPES')." WHERE project_id = ".(int)$project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')." WHERE project_id = ".(int)$project_id.";
		");
	}
	
	public static function getProjects($project_id = 0) {
		
		$str_identifier = (int)$project_id;
		
		$cache = self::getCache($str_identifier);
		if ($cache) {
			return $cache;
		}
	
		$arr = [];

		$arr_res = DB::queryMulti("
			SELECT p.*,
				pt.type_id, pt.type_definition_id, pt.color, pt.type_filter_id, pt.type_filter_object_subs, pt.type_context_id, pt.type_frame_id, pt.type_condition_id, pt.configuration_exclude,
				ptc.object_description_id AS configuration_object_description_id, ptc.object_sub_details_id AS configuration_object_sub_details_id, ptc.object_sub_description_id AS configuration_object_sub_description_id, ptc.edit AS configuration_edit, ptc.view AS configuration_view
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." pt ON (pt.project_id = p.id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION')." ptc ON (ptc.project_id = p.id AND ptc.type_id = pt.type_id)
				WHERE TRUE
				".($project_id ? "AND p.id = ".(int)$project_id."" : "")."
				ORDER BY ".(!$project_id ? "p.name, " : "")."pt.sort;
				
			SELECT p.id,
				pt.type_id,
				ptrt.referenced_type_id, ptrt.object_description_id AS referenced_object_description_id, ptrt.object_sub_details_id AS referenced_object_sub_details_id, ptrt.object_sub_description_id AS referenced_object_sub_description_id, ptrt.edit AS referenced_edit, ptrt.view AS referenced_view
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." pt ON (pt.project_id = p.id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')." ptrt ON (ptrt.project_id = p.id AND ptrt.type_id = pt.type_id)
				WHERE TRUE
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
				
			SELECT p.id,
				plt.type_id AS location_type_id
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES')." plt ON (plt.project_id = p.id)
				WHERE TRUE
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
			
			SELECT p.id,
				pst.type_id AS source_type_id
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES')." pst ON (pst.project_id = p.id)
				WHERE TRUE
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
				
			SELECT p.id,
				pup.use_project_id
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_USE_PROJECTS')." pup ON (pup.project_id = p.id)
				WHERE TRUE
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
		");

		while ($row = $arr_res[0]->fetchAssoc()) {
			
			if (!$arr[$row['id']]) {
				$arr[$row['id']] = ['project' => $row, 'types' => [], 'location_types' => [], 'source_types' => [], 'use_projects' => []];
			}
			
			if ($row['type_id']) {
				
				$s_arr =& $arr[$row['id']]['types'][$row['type_id']];
				
				if (!$s_arr) {
					$s_arr = $row;
				}
				
				if ($row['configuration_edit'] || $row['configuration_view']) {
					
					$s_arr =& $s_arr['configuration'];
					
					if ($row['configuration_object_description_id']) {
						$s_arr['object_descriptions'][$row['configuration_object_description_id']] = ['edit' => $row['configuration_edit'], 'view' => $row['configuration_view']];
					} else if ($row['configuration_object_sub_description_id']) {
						$s_arr['object_sub_details'][$row['configuration_object_sub_details_id']]['object_sub_descriptions'][$row['configuration_object_sub_description_id']] = ['edit' => $row['configuration_edit'], 'view' => $row['configuration_view']];
					} else if ($row['configuration_object_sub_details_id']) {
						$s_arr['object_sub_details'][$row['configuration_object_sub_details_id']]['object_sub_details'] = ['edit' => $row['configuration_edit'], 'view' => $row['configuration_view']];
					}
				}
			}
		}
		
		while ($row = $arr_res[1]->fetchAssoc()) {
			
			$s_arr =& $arr[$row['id']]['types'][$row['type_id']]['include_referenced_types'][$row['referenced_type_id']];
			
			if ($row['referenced_object_description_id']) {
				$s_arr['object_descriptions'][$row['referenced_object_description_id']] = ['edit' => $row['referenced_edit'], 'view' => $row['referenced_view']];
			} else if ($row['referenced_object_sub_description_id']) {
				$s_arr['object_sub_details'][$row['referenced_object_sub_details_id']]['object_sub_descriptions'][$row['referenced_object_sub_description_id']] = ['edit' => $row['referenced_edit'], 'view' => $row['referenced_view']];
			}
		}
		while ($row = $arr_res[2]->fetchAssoc()) {
			
			$arr[$row['id']]['location_types'][$row['location_type_id']] = $row;
		}
		while ($row = $arr_res[3]->fetchAssoc()) {
			
			$arr[$row['id']]['source_types'][$row['source_type_id']] = $row;
		}
		while ($row = $arr_res[4]->fetchAssoc()) {
			
			$arr[$row['id']]['use_projects'][$row['use_project_id']] = $row;
		}
		unset($s_arr);
				
		$arr = ((int)$project_id ? current($arr) : $arr);
		
		self::setCache($str_identifier, $arr);
		
		return $arr;
	}
	
	public static function getTypeSetReferenced($type_id, $arr_project_type, $purpose = 'view') {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		foreach ((array)$arr_project_type['include_referenced_types'] as $referenced_type_id => $arr_referenced_type) {
			
			$arr_referenced_type_set = StoreType::getTypeSet($referenced_type_id);
			
			foreach ((array)$arr_referenced_type['object_descriptions'] as $object_description_id => $arr_options) {
				
				if (!$arr_options[$purpose]) {
					continue;
				}
				
				$use_object_description_id = $object_description_id.'is0referenced';
				
				$arr_type_set['type']['include_referenced']['object_descriptions'][$use_object_description_id] = $use_object_description_id;
				
				$arr_type_set['object_descriptions'][$use_object_description_id] = $arr_referenced_type_set['object_descriptions'][$object_description_id];
				
				$s_arr =& $arr_type_set['object_descriptions'][$use_object_description_id];
				$s_arr['object_description_is_referenced'] = true;
				$s_arr['object_description_ref_type_id'] = $referenced_type_id;
				$s_arr['object_description_has_multi'] = true;
			}
			
			foreach ((array)$arr_referenced_type['object_sub_details'] as $object_sub_details_id => $arr_referenced_object_sub_details) {
				
				foreach ($arr_referenced_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_options) {
				
					if (!$arr_options[$purpose]) {
						continue;
					}
					
					$use_object_sub_details_id = $object_sub_details_id.'is0referenced'.$object_sub_description_id;
					
					$arr_type_set['type']['include_referenced']['object_sub_details'][$use_object_sub_details_id][$object_sub_description_id] = $object_sub_description_id;
						
					$arr_type_set['object_sub_details'][$use_object_sub_details_id] = $arr_referenced_type_set['object_sub_details'][$object_sub_details_id];
					$arr_type_set['object_sub_details'][$use_object_sub_details_id]['object_sub_details']['object_sub_details_type_id'] = $referenced_type_id;

					$s_arr =& $arr_type_set['object_sub_details'][$use_object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
					$s_arr['object_sub_description_is_referenced'] = true;
					$s_arr['object_sub_description_ref_type_id'] = $referenced_type_id;
				}
			}
		}
		
		return $arr_type_set;
	}
	
	// Project Type Filters
	
	public static function handleProjectTypeFilter($project_id, $user_id, $filter_id, $type_id, $arr, $arr_type_filter, $is_domain = false) {
		
		$str_object = json_encode($arr_type_filter);
		
		if ($is_domain) {
			$project_id = 0;
			$user_id = 0;
		}
		
		if ($filter_id) {
			$arr_cur = self::getProjectTypeFilters($project_id, false, false, $filter_id, $is_domain);
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
				$filter_id = false;
			}
			if (!$arr_cur['project_id'] && !$is_domain) {
				error(getLabel('msg_not_allowed'));
			}
		}
		
		$sql_new_id = "JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')." pf ON (pf.project_id = 0 OR pf.project_id = p.id)";
				
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')."
			(project_id, user_id, id, name, type_id, description, object)
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
			".DBFunctions::onConflict('project_id, id', ['user_id', 'name', 'type_id', 'description', 'object'])."
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
					".($is_domain ? ($project_id ? "OR " : "")."pf.project_id = 0" : "")."
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
						$arr_row['label'] = getLabel('lbl_clearance_admin').' | '.$arr_row['label'];
					} else if ($project_id != $arr_row['project_id']) {
						$arr_row['label'] = $arr_row['project_name'].' | '.$arr_row['label'];
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

		$str_object = json_encode($arr_scope);
		
		if ($project_id && $user_id && $scope_id === 0) {
			$is_user_default = true;
		}
		
		if ($scope_id) {
			$arr_cur = self::getProjectTypeScopes($project_id, false, false, $scope_id);
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
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
		
		if ($project_id && $user_id && $scope_id === 0) {
			$is_user_default = true;
		}

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
				
				if ($arr_row['id'] == 0 && !$scope_id) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id != $arr_row['project_id']) {
					$arr_row['label'] = $arr_row['project_name'].' | '.$arr_row['label'];
				}
				
				$arr_row['object'] = json_decode($arr_row['object'], true);
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// Project Type Contexts
	
	public static function handleProjectTypeContext($project_id, $user_id, $context_id, $type_id, $arr, $arr_context) {
		
		$str_object = json_encode($arr_context);
		
		if ($project_id && $user_id && $context_id === 0) {
			$is_user_default = true;
		}
		
		if ($context_id) {
			$arr_cur = self::getProjectTypeContexts($project_id, false, false, $context_id);
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
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
		
		if ($project_id && $user_id && $context_id === 0) {
			$is_user_default = true;
		}

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
				
				if ($arr_row['id'] == 0 && !$context_id) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id != $arr_row['project_id']) {
					$arr_row['label'] = $arr_row['project_name'].' | '.$arr_row['label'];
				}
				
				$arr_row['object'] = json_decode($arr_row['object'], true);
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row;
			}
			
			return ($arr && $type_id ? current($arr) : $arr);
		}
	}
	
	// Project Type Frames
	
	public static function handleProjectTypeFrame($project_id, $user_id, $frame_id, $type_id, $arr, $arr_frame) {
		
		if ($project_id && $user_id && $frame_id === 0) {
			$is_user_default = true;
		}
		
		if ($frame_id) {
			$arr_cur = self::getProjectTypeFrames($project_id, false, false, $frame_id);
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
				$frame_id = false;
			}
		}
				
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES')."
			(project_id, user_id, id, type_id, name, description,
				area_geo_latitude, area_geo_longitude, area_geo_scale, area_social_object_id, area_social_zoom, time_bounds_date_start, time_bounds_date_end, time_selection_date_start, time_selection_date_end, object_subs_unknown_date, object_subs_unknown_location
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
				".(float)$arr_frame['area']['geo']['scale'].",
				".(int)$arr_frame['area']['social']['object_id'].",
				".(float)$arr_frame['area']['social']['zoom'].",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['bounds']['date_start']).",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['bounds']['date_end']).",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['selection']['date_start']).",
				".(int)StoreTypeObjects::formatToSQLValue('date', $arr_frame['time']['selection']['date_end']).",
				".($arr_frame['object_subs']['unknown']['date'] && $arr_frame['object_subs']['unknown']['date'] != 'span' ? "'".DBFunctions::strEscape($arr_frame['object_subs']['unknown']['date'])."'" : 'NULL').",
				".($arr_frame['object_subs']['unknown']['location'] && $arr_frame['object_subs']['unknown']['location'] != 'ignore' ? "'".DBFunctions::strEscape($arr_frame['object_subs']['unknown']['location'])."'" : 'NULL')."
			)
			".DBFunctions::onConflict('project_id, user_id, id, type_id', ['name', 'description',
				'area_geo_latitude', 'area_geo_longitude', 'area_geo_scale', 'area_social_object_id', 'area_social_zoom', 'time_bounds_date_start', 'time_bounds_date_end', 'time_selection_date_start', 'time_selection_date_end', 'object_subs_unknown_date', 'object_subs_unknown_location'
			])."
		");
		
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
		
		if ($project_id && $user_id && $frame_id === 0) {
			$is_user_default = true;
		}

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
				
				$arr_settings = array_slice($arr_row, 5, -1, true);
				array_splice($arr_row, 6, -1);
				$arr_row['settings'] = $arr_settings;
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				
				$arr = $arr_row;
			}

			return $arr;
		} else {
		
			while($arr_row = $res->fetchAssoc()) {
				
				if ($arr_row['id'] == 0 && !$frame_id) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_settings = array_slice($arr_row, 5, -1, true);
				array_splice($arr_row, 6, -1);
				$arr_row['settings'] = $arr_settings;
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id != $arr_row['project_id']) {
					$arr_row['label'] = $arr_row['project_name'].' | '.$arr_row['label'];
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
				'area_geo_scale' => $arr_settings_use['area']['geo']['scale'],
				'area_social_object_id' => $arr_settings_use['area']['social']['object_id'],
				'area_social_zoom' => $arr_settings_use['area']['social']['zoom'],
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
					'scale' => ($arr_settings['area_geo_scale'] ? (float)$arr_settings['area_geo_scale'] : '')
				],
				'social' => [
					'object_id' => ($arr_settings['area_social_object_id'] ? (int)$arr_settings['area_social_object_id'] : ''),
					'zoom' => ($arr_settings['area_social_zoom'] ? (float)$arr_settings['area_social_zoom'] : '')
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
		
		if ($project_id && $user_id && $visual_settings_id === 0) {
			$is_user_default = true;
		}
		
		if ($visual_settings_id) {
			$arr_cur = self::getProjectVisualSettings($project_id, false, $visual_settings_id);
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
				$visual_settings_id = false;
			}
		}
		
		$arr_default = self::parseVisualSettings();
				
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_VISUAL_SETTINGS')."
			(project_id, user_id, id, name, description,
				dot_show, dot_color, dot_opacity, dot_color_condition, dot_size_min, dot_size_max, dot_size_start, dot_size_stop, dot_stroke_color, dot_stroke_opacity, dot_stroke_width, location_show, location_color, location_size, location_threshold, location_condition, line_show, line_color, line_opacity, line_width_min, line_width_max, line_offset, visual_hints_show, visual_hints_color, visual_hints_opacity, visual_hints_size, visual_hints_stroke_color, visual_hints_stroke_opacity, visual_hints_stroke_width, visual_hints_duration, visual_hints_delay, geometry_show, geometry_color, geometry_opacity, geometry_stroke_color, geometry_stroke_opacity, geometry_stroke_width, map_url, map_attribution, geo_info_show, geo_background_color, geo_mode, geo_display, geo_advanced, social_dot_color, social_dot_size_min, social_dot_size_max, social_dot_stroke_color, social_dot_stroke_width, social_line_show, social_line_arrowhead_show, social_disconnected_dot_show, social_include_location_references, social_background_color, social_display, social_static_layout, social_static_layout_interval, social_advanced, time_conditions_relative, time_conditions_cumulative
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
					".(float)((float)$arr_visual_settings['location']['size'] != $arr_default['location']['size'] ? $arr_visual_settings['location']['size'] : '').",
					".(int)((int)$arr_visual_settings['location']['threshold'] != $arr_default['location']['threshold'] ? $arr_visual_settings['location']['threshold'] : '').",
					'".($arr_visual_settings['location']['condition'] != $arr_default['location']['condition'] ? DBFunctions::strEscape($arr_visual_settings['location']['condition']) : '')."',
					".((string)$arr_visual_settings['line']['show'] !== '' && (int)$arr_visual_settings['line']['show'] != $arr_default['line']['show'] ? (int)$arr_visual_settings['line']['show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['line']['color']) != $arr_default['line']['color'] ? str2Color($arr_visual_settings['line']['color']) : '')."',
					".(float)((float)$arr_visual_settings['line']['opacity'] != $arr_default['line']['opacity'] ? $arr_visual_settings['line']['opacity'] : '').",
					".(float)((float)$arr_visual_settings['line']['width']['min'] != $arr_default['line']['width']['min'] ? $arr_visual_settings['line']['width']['min'] : '').",
					".(float)((float)$arr_visual_settings['line']['width']['max'] != $arr_default['line']['width']['max'] ? $arr_visual_settings['line']['width']['max'] : '').",
					".((string)$arr_visual_settings['line']['offset'] !== '' && (float)$arr_visual_settings['line']['offset'] != $arr_default['line']['offset'] ? (int)$arr_visual_settings['line']['offset'] : 'NULL').",
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
					'".($arr_visual_settings['settings']['map_url'] != $arr_default['settings']['map_url'] ? DBFunctions::strEscape($arr_visual_settings['settings']['map_url']) : '')."',
					'".($arr_visual_settings['settings']['map_attribution'] != $arr_default['settings']['map_attribution'] ? DBFunctions::strEscape($arr_visual_settings['settings']['map_attribution']) : '')."',
					".((string)$arr_visual_settings['settings']['geo_info_show'] !== '' && (int)$arr_visual_settings['settings']['geo_info_show'] != $arr_default['settings']['geo_info_show'] ? (int)$arr_visual_settings['settings']['geo_info_show'] : 'NULL').",
					'".(str2Color($arr_visual_settings['settings']['geo_background_color']) != $arr_default['settings']['geo_background_color'] ? str2Color($arr_visual_settings['settings']['geo_background_color']) : '')."',
					".((string)$arr_visual_settings['settings']['geo_mode'] !== '' && (int)$arr_visual_settings['settings']['geo_mode'] != $arr_default['settings']['geo_mode'] ? (int)$arr_visual_settings['settings']['geo_mode'] : 'NULL').",
					".((string)$arr_visual_settings['settings']['geo_display'] !== '' && (int)$arr_visual_settings['settings']['geo_display'] != $arr_default['settings']['geo_display'] ? (int)$arr_visual_settings['settings']['geo_display'] : 'NULL').",
					'".($arr_visual_settings['settings']['geo_advanced'] && $arr_visual_settings['settings']['geo_advanced'] !== $arr_default['settings']['geo_advanced'] ? DBFunctions::strEscape(json_encode($arr_visual_settings['settings']['geo_advanced'])) : '')."',
					'".(str2Color($arr_visual_settings['social']['dot']['color']) != $arr_default['social']['dot']['color'] ? str2Color($arr_visual_settings['social']['dot']['color']) : '')."',
					".(float)((float)$arr_visual_settings['social']['dot']['size']['min'] != $arr_default['social']['dot']['size']['min'] ? $arr_visual_settings['social']['dot']['size']['min'] : '').",
					".(float)((float)$arr_visual_settings['social']['dot']['size']['max'] != $arr_default['social']['dot']['size']['max'] ? $arr_visual_settings['social']['dot']['size']['max'] : '').",
					".(int)((int)$arr_visual_settings['social']['dot']['size']['start'] != $arr_default['social']['dot']['size']['start'] ? $arr_visual_settings['social']['dot']['size']['start'] : '').",
					".(int)((int)$arr_visual_settings['social']['dot']['size']['stop'] != $arr_default['social']['dot']['size']['stop'] ? $arr_visual_settings['social']['dot']['size']['stop'] : '').",
					'".(str2Color($arr_visual_settings['social']['dot']['stroke_color']) != $arr_default['social']['dot']['stroke_color'] ? str2Color($arr_visual_settings['social']['dot']['stroke_color']) : '')."',
					".((string)$arr_visual_settings['social']['dot']['stroke_width'] !== '' && (float)$arr_visual_settings['social']['dot']['stroke_width'] != $arr_default['social']['dot']['stroke_width'] ? (float)$arr_visual_settings['social']['dot']['stroke_width'] : 'NULL').",
					".((string)$arr_visual_settings['social']['line']['show'] !== '' && (int)$arr_visual_settings['social']['line']['show'] != $arr_default['social']['line']['show'] ? (int)$arr_visual_settings['social']['line']['show'] : 'NULL').",
					".((string)$arr_visual_settings['social']['line']['arrowhead_show'] !== '' && (int)$arr_visual_settings['social']['line']['arrowhead_show'] != $arr_default['social']['line']['arrowhead_show'] ? (int)$arr_visual_settings['social']['line']['arrowhead_show'] : 'NULL').",
					".((string)$arr_visual_settings['social']['settings']['disconnected_dot_show'] !== '' && (int)$arr_visual_settings['social']['settings']['disconnected_dot_show'] != $arr_default['social']['settings']['disconnected_dot_show'] ? (int)$arr_visual_settings['social']['settings']['disconnected_dot_show'] : 'NULL').",
					".((string)$arr_visual_settings['social']['settings']['include_location_references'] !== '' && (int)$arr_visual_settings['social']['settings']['include_location_references'] != $arr_default['social']['settings']['include_location_references'] ? (int)$arr_visual_settings['social']['settings']['include_location_references'] : 'NULL').",
					'".(str2Color($arr_visual_settings['social']['settings']['background_color']) != $arr_default['social']['settings']['background_color'] ? str2Color($arr_visual_settings['social']['settings']['background_color']) : '')."',
					".((string)$arr_visual_settings['social']['settings']['display'] !== '' && (int)$arr_visual_settings['social']['settings']['display'] != $arr_default['social']['settings']['display'] ? (int)$arr_visual_settings['social']['settings']['display'] : 'NULL').",
					".((string)$arr_visual_settings['social']['settings']['static_layout'] !== '' && (int)$arr_visual_settings['social']['settings']['static_layout'] != $arr_default['social']['settings']['static_layout'] ? (int)$arr_visual_settings['social']['settings']['static_layout'] : 'NULL').",
					".((string)$arr_visual_settings['social']['settings']['static_layout_interval'] !== '' && (float)$arr_visual_settings['social']['settings']['static_layout_interval'] != $arr_default['social']['settings']['static_layout_interval'] ? (float)$arr_visual_settings['social']['settings']['static_layout_interval'] : 'NULL').",
					'".($arr_visual_settings['social']['settings']['social_advanced'] && $arr_visual_settings['social']['settings']['social_advanced'] !== $arr_default['social']['settings']['social_advanced'] ? DBFunctions::strEscape(json_encode($arr_visual_settings['social']['settings']['social_advanced'])) : '')."',
					".((string)$arr_visual_settings['time']['settings']['conditions_relative'] !== '' && (int)$arr_visual_settings['time']['settings']['conditions_relative'] != $arr_default['time']['settings']['conditions_relative'] ? (int)$arr_visual_settings['time']['settings']['conditions_relative'] : 'NULL').",
					".((string)$arr_visual_settings['time']['settings']['conditions_cumulative'] !== '' && (int)$arr_visual_settings['time']['settings']['conditions_cumulative'] != $arr_default['time']['settings']['conditions_cumulative'] ? (int)$arr_visual_settings['time']['settings']['conditions_cumulative'] : 'NULL')."
			)
			".DBFunctions::onConflict('project_id, user_id, id', ['name', 'description',
				'dot_show', 'dot_color', 'dot_opacity', 'dot_color_condition', 'dot_size_min', 'dot_size_max', 'dot_size_start', 'dot_size_stop', 'dot_stroke_color', 'dot_stroke_opacity', 'dot_stroke_width', 'location_show', 'location_color', 'location_size', 'location_threshold', 'location_condition', 'line_show', 'line_color', 'line_opacity', 'line_width_min', 'line_width_max', 'line_offset', 'visual_hints_show', 'visual_hints_color', 'visual_hints_opacity', 'visual_hints_size', 'visual_hints_stroke_color', 'visual_hints_stroke_opacity', 'visual_hints_stroke_width', 'visual_hints_duration', 'visual_hints_delay', 'geometry_show', 'geometry_color', 'geometry_opacity', 'geometry_stroke_color', 'geometry_stroke_opacity', 'geometry_stroke_width', 'map_url', 'map_attribution', 'geo_info_show', 'geo_background_color', 'geo_mode', 'geo_display', 'geo_advanced', 'social_dot_color', 'social_dot_size_min', 'social_dot_size_max', 'social_dot_size_start', 'social_dot_size_stop', 'social_dot_stroke_color', 'social_dot_stroke_width', 'social_line_show', 'social_line_arrowhead_show', 'social_disconnected_dot_show', 'social_include_location_references', 'social_background_color', 'social_display', 'social_static_layout', 'social_static_layout_interval', 'social_advanced', 'time_conditions_relative', 'time_conditions_cumulative'
			])."
		");
		
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
				'location_size' => $arr_settings_use['location']['size'],
				'location_threshold' => $arr_settings_use['location']['threshold'],
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
				'social_disconnected_dot_show' => $arr_settings_use['social']['settings']['disconnected_dot_show'],
				'social_include_location_references' => $arr_settings_use['social']['settings']['include_location_references'],
				'social_background_color' => $arr_settings_use['social']['settings']['background_color'],
				'social_display' => $arr_settings_use['social']['settings']['display'],
				'social_static_layout' => $arr_settings_use['social']['settings']['static_layout'],
				'social_static_layout_interval' => $arr_settings_use['social']['settings']['static_layout_interval'],
				'social_advanced' => $arr_settings_use['social']['settings']['social_advanced'],
				'time_conditions_relative' => $arr_settings_use['time']['settings']['conditions_relative'],
				'time_conditions_cumulative' => $arr_settings_use['time']['settings']['conditions_cumulative']
			];
		}
		
		return [
			'dot' => [
				'show' => (int)((string)$arr_settings['dot_show'] !== '' ? (bool)$arr_settings['dot_show'] : true),
				'color' => ($arr_settings['dot_color'] ?: ''),
				'opacity' => ((string)$arr_settings['dot_opacity'] !== '' ? (float)$arr_settings['dot_opacity'] : 1),
				'color_condition' => ($arr_settings['dot_color_condition'] ?: ''),
				'size' => ['min' => (float)($arr_settings['dot_size_min'] ?: 8), 'max' => (float)($arr_settings['dot_size_max'] ?: 20), 'start' => ((int)$arr_settings['dot_size_start'] ?: ''), 'stop' => ((int)$arr_settings['dot_size_stop'] ?: '')],
				'stroke_color' => ($arr_settings['dot_stroke_color'] ?: '#f0f0f0'),
				'stroke_opacity' => (float)($arr_settings['dot_stroke_opacity'] ?: 1),
				'stroke_width' => ((string)$arr_settings['dot_stroke_width'] !== '' ? (float)$arr_settings['dot_stroke_width'] : 1.5)
			],
			'location' => [
				'show' => (int)((string)$arr_settings['location_show'] !== '' ? (bool)$arr_settings['location_show'] : false),
				'color' => ($arr_settings['location_color'] ?: '#000000'),
				'size' => (float)($arr_settings['location_size'] ?: 8),
				'threshold' => ((int)$arr_settings['location_threshold'] ?: 1),
				'condition' => ($arr_settings['location_condition'] ?: '')
			],
			'line' => [
				'show' => (int)((string)$arr_settings['line_show'] !== '' ? (bool)$arr_settings['line_show'] : true),
				'color' => ($arr_settings['line_color'] ?: ''),
				'opacity' => (float)($arr_settings['line_opacity'] ?: 1),
				'width' =>  ['min' => (float)($arr_settings['line_width_min'] ?: 2), 'max' => (float)($arr_settings['line_width_max'] ?: 10)],
				'offset' => ((string)$arr_settings['line_offset'] !== '' ? (int)$arr_settings['line_offset'] : 6)
			],
			'hint' => [
				'show' => (int)((string)$arr_settings['visual_hints_show'] !== '' ? (bool)$arr_settings['visual_hints_show'] : true),
				'color' => ($arr_settings['visual_hints_color'] ?: '#0092d9'),
				'opacity' => ((string)$arr_settings['visual_hints_opacity'] !== '' ? (float)$arr_settings['visual_hints_opacity'] : 1),
				'size' => (float)($arr_settings['visual_hints_size'] ?: 20),
				'stroke_color' => ($arr_settings['visual_hints_stroke_color'] ?: '#ffffff'),
				'stroke_opacity' => (float)($arr_settings['visual_hints_stroke_opacity'] ?: 1),
				'stroke_width' => ((string)$arr_settings['visual_hints_stroke_width'] !== '' ? (float)$arr_settings['visual_hints_stroke_width'] : 2),
				'duration' => (float)($arr_settings['visual_hints_duration'] ?: 0.5),
				'delay' => (float)($arr_settings['visual_hints_delay'] ?: 0)
			],
			'geometry' => [
				'show' => (int)((string)$arr_settings['geometry_show'] !== '' ? (bool)$arr_settings['geometry_show'] : true),
				'color' => ($arr_settings['geometry_color'] ?: '#666666'),
				'opacity' => ((string)$arr_settings['geometry_opacity'] !== '' ? (float)$arr_settings['geometry_opacity'] : 0.4),
				'stroke_color' => ($arr_settings['geometry_stroke_color'] ?: '#444444'),
				'stroke_opacity' => (float)($arr_settings['geometry_stroke_opacity'] ?: 0.6),
				'stroke_width' => ((string)$arr_settings['geometry_stroke_width'] !== '' ? (float)$arr_settings['geometry_stroke_width'] : 1)
			],
			'settings' => [
				'map_url' => ($arr_settings['map_url'] ?: '//mt{s}.googleapis.com/vt?pb=!1m4!1m3!1i{z}!2i{x}!3i{y}!2m3!1e0!2sm!3i278000000!3m14!2sen-US!3sUS!5e18!12m1!1e47!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjE3fHAudjpvZmYscy50OjE4fHAudjpvZmYscy50OjIwfHMuZTpsfHAudjpvZmYscy50OjgxfHAudjpvZmYscy50OjJ8cC52Om9mZixzLnQ6NDl8cC52Om9mZixzLnQ6NTB8cy5lOmx8cC52Om9mZixzLnQ6NHxwLnY6b2ZmLHMudDo2fHMuZTpsfHAudjpvZmY!4e0!20m1!1b1'), // //mt{s}.googleapis.com/vt?lyrs=m@205000000&src=apiv3&hl=en-US&x={x}&y={y}&z={z}&s=Galil&apistyle=p.v%3Aoff%2Cs.t%3A6%7Cp.v%3Aon%7Cp.c%3A%23ffc7d7e4%2Cs.t%3A82%7Cp.v%3Aon%2Cs.t%3A19%7Cp.v%3Aon&style=api%7Csmartmaps
				'map_attribution' => ($arr_settings['map_attribution'] ?: 'Map data ©'.date('Y').' Google'),
				'geo_info_show' => (int)((string)$arr_settings['geo_info_show'] !== '' ? (bool)$arr_settings['geo_info_show'] : false),
				'geo_background_color' => ($arr_settings['geo_background_color'] ?: ''),
				'geo_mode' => ((string)$arr_settings['geo_mode'] !== '' ? (int)$arr_settings['geo_mode'] : 1),
				'geo_display' => ((string)$arr_settings['geo_display'] !== '' ? (int)$arr_settings['geo_display'] : 1),
				'geo_advanced' => ($arr_settings['geo_advanced'] ? (!is_array($arr_settings['geo_advanced']) ? (array)json_decode($arr_settings['geo_advanced'], true) : $arr_settings['geo_advanced']) : [])
			],
			'social' => [
				'dot' => [
					'color' => ($arr_settings['social_dot_color'] ?: '#ffffff'),
					'size' => ['min' => (float)($arr_settings['social_dot_size_min'] ?: 3), 'max' => (float)($arr_settings['social_dot_size_max'] ?: 20), 'start' => ((int)$arr_settings['social_dot_size_start'] ?: ''), 'stop' => ((int)$arr_settings['social_dot_size_stop'] ?: '')],
					'stroke_color' => ($arr_settings['social_dot_stroke_color'] ?: '#aaaaaa'),
					'stroke_width' => ((string)$arr_settings['social_dot_stroke_width'] !== '' ? (float)$arr_settings['social_dot_stroke_width'] : 1)
				],
				'line' => [
					'show' => (int)((string)$arr_settings['social_line_show'] !== '' ? (bool)$arr_settings['social_line_show'] : true),
					'arrowhead_show' => (int)((string)$arr_settings['social_line_arrowhead_show'] !== '' ? (bool)$arr_settings['social_line_arrowhead_show'] : false)
				],
				'settings' => [
					'disconnected_dot_show' => (int)((string)$arr_settings['social_disconnected_dot_show'] !== '' ? (bool)$arr_settings['social_disconnected_dot_show'] : true),
					'include_location_references' => (int)((string)$arr_settings['social_include_location_references'] !== '' ? (bool)$arr_settings['social_include_location_references'] : false),
					'background_color' => ($arr_settings['social_background_color'] ?: ''),
					'display' => ((string)$arr_settings['social_display'] !== '' ? (int)$arr_settings['social_display'] : 1),
					'static_layout' => (int)((string)$arr_settings['social_static_layout'] !== '' ? (bool)$arr_settings['social_static_layout'] : false),
					'static_layout_interval' => ((string)$arr_settings['social_static_layout_interval'] !== '' ? (float)$arr_settings['social_static_layout_interval'] : ''),
					'social_advanced' => ($arr_settings['social_advanced'] ? (!is_array($arr_settings['social_advanced']) ? (array)json_decode($arr_settings['social_advanced'], true) : $arr_settings['social_advanced']) : [])
				]
			],
			'time' => [
				'settings' => [
					'conditions_relative' => (int)((string)$arr_settings['time_conditions_relative'] !== '' ? (bool)$arr_settings['time_conditions_relative'] : false),
					'conditions_cumulative' => (int)((string)$arr_settings['time_conditions_cumulative'] !== '' ? (bool)$arr_settings['time_conditions_cumulative'] : false)
				]
			]
		];
	}
	
	public static function delProjectVisualSettings($project_id, $visual_settings_id) {
		
		$arr_cur = self::getProjectVisualSettings($project_id, false, $visual_settings_id);
			
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_VISUAL_SETTINGS')."
			WHERE project_id = ".(int)$project_id."
				AND id = ".(int)$visual_settings_id."
		");
	}
	
	public static function getProjectVisualSettings($project_id, $user_id = false, $visual_settings_id = false, $arr_use_project_ids = []) {
		
		if ($project_id && $user_id && $visual_settings_id === 0) {
			$is_user_default = true;
		}
	
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
					$row['label'] = $row['project_name'].' | '.$row['label'];
				}
				
				$arr[$row['id']] = $row;
			}
			
			return $arr;
		}
	}
	
	// Project Type Conditions
	
	public static function handleProjectTypeCondition($project_id, $user_id, $condition_id, $type_id, $arr, $arr_condition, $arr_model_conditions, $is_domain = false) {
		
		$str_object = ($arr_condition ? json_encode($arr_condition) : '');
		$str_model_object = ($arr_model_conditions ? json_encode($arr_model_conditions) : '');
		
		if ($is_domain) {
			$project_id = 0;
			$user_id = 0;
		}
		if ($project_id && $user_id && $condition_id === 0) {
			$is_user_default = true;
		}		
		
		if ($condition_id) {
			
			$arr_cur = self::getProjectTypeConditions($project_id, false, false, $condition_id, $is_domain);
			
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
				$condition_id = false;
			}
			if (!$arr_cur['project_id'] && !$is_domain) {
				error(getLabel('msg_not_allowed'));
			}
		}
		
		$sql_new_id = "JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')." pc ON (pc.project_id = 0 OR pc.project_id = p.id)";
						
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')."
			(project_id, user_id, id, type_id, name, description, object, model_object)
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
			".DBFunctions::onConflict('project_id, user_id, id, type_id', ['name', 'description', 'object', 'model_object'])."
		");
		
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
					".($is_domain ? ($project_id ? "OR " : "")."project_id = 0" : "")."
				)
				AND id = ".(int)$condition_id."
				".($user_id ? "AND user_id = ".(int)$user_id : "")."
		");
	}
	
	public static function getProjectTypeConditions($project_id, $user_id = false, $type_id = false, $condition_id = false, $is_domain = false, $arr_use_project_ids = []) {

		if ($project_id && $user_id && $condition_id === 0) {
			$is_user_default = true;
		}	
	
		$arr = [];
		
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
					".($is_domain ? ($project_id ? "OR " : "")."pc.project_id = 0" : "")."
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
		
			while($arr_row = $res->fetchAssoc()) {
				
				if ($arr_row['id'] == 0 && !$condition_id) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id) { // Do grouping
					if ($is_domain && !$arr_row['project_id']) {
						$arr_row['label'] = getLabel('lbl_clearance_admin').' | '.$arr_row['label'];
					} else if ($project_id != $arr_row['project_id']) {
						$arr_row['label'] = $arr_row['project_name'].' | '.$arr_row['label'];
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
		
		$str_object = ($arr_analysis['settings'] ? json_encode($arr_analysis['settings']) : '');
		$str_scope_object = ($arr_analysis['scope'] ? json_encode($arr_analysis['scope']) : '');
		
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
		
		$str_object = json_encode($arr_analysis_context);
		
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
	
	// Features handlers
	
	protected static function handleProjectTypeFeature($table_name, $project_id, $user_id, $feature_id, $type_id, $arr, $arr_sql_store) {
				
		if ($project_id && $user_id && $feature_id === 0) {
			$is_user_default = true;
		}
		
		if ($feature_id) {
			
			$arr_cur = self::getProjectTypeFeatures($table_name, false, $project_id, false, false, $feature_id);
			
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
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
		
		if ($project_id && $user_id && $feature_id === 0) {
			$is_user_default = true;
		}

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
				
				if ($arr_row['id'] == 0 && !$feature_id) { // Do not show the default user settings in lists
					continue;
				}
				
				$arr_row['label'] = ($arr_row['user_id'] ? '• ' : '').$arr_row['name'];
				if ($project_id) { // Do grouping
					if ($project_id != $arr_row['project_id']) {
						$arr_row['label'] = $arr_row['project_name'].' | '.$arr_row['label'];
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
			
			if ($arr_cur['name'] != $arr['name'] || $arr_cur['user_id'] != $user_id) {
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
	
	public static function getProjectTypeScenarioHash($project_id, $scenario_id, $use_project_id = false) {
		
		if ($use_project_id == $project_id) {
			$use_project_id = false;
		}
				
		$res = DB::query("SELECT *
				FROM ".DB::getTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE')." pscc
			WHERE pscc.project_id = ".(int)$project_id."
				AND pscc.scenario_id = ".(int)$scenario_id."
				AND pscc.use_project_id = ".(int)$use_project_id."
		");
		
		$arr = $res->fetchAssoc();
		
		return $arr;
	}
	
	public static function updateProjectTypeScenarioHash($project_id, $scenario_id, $hash, $hash_date, $use_project_id = false) {
		
		if ($use_project_id == $project_id) {
			$use_project_id = false;
		}
		
		$hash_date = str2SQlDate($hash_date);
		
		$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE')."
			(project_id, scenario_id, use_project_id, hash, hash_date)
				VALUES
			(".(int)$project_id.", ".(int)$scenario_id.", ".(int)$use_project_id.", '".DBFunctions::strEscape($hash)."', '".$hash_date."')
			".DBFunctions::onConflict('project_id, scenario_id, use_project_id', ['hash', 'hash_date'])."
		");
		
		return ($res->getAffectedRowCount() ? true : false);
	}
	
	public static function delProjectTypeScenarioHash($project_id, $scenario_id, $use_project_id = false) {
		
		if ($use_project_id == $project_id) {
			$use_project_id = false;
		}
		
		$res = DB::query("DELETE
				FROM ".DB::getTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE')."
			WHERE project_id = ".(int)$project_id."
				AND scenario_id = ".(int)$scenario_id."
				AND use_project_id = ".(int)$use_project_id."
		");
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
					$row['label'] = $row['project_name'].' | '.$row['label'];
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
	
	public static function getTypeRelatedProjectTypes($arr_type_ids) {
		
		$sql_type_ids = (is_array($arr_type_ids) ? implode(',', arrParseRecursive($arr_type_ids, 'int')) : (int)$arr_type_ids);
		$arr = [];
		
		$res = DB::query("SELECT prt.type_id
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." pt
				JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." prt ON (prt.project_id = pt.project_id)
			WHERE pt.type_id IN (".$sql_type_ids.")
			GROUP BY prt.type_id
		");
						
		while($row = $res->fetchAssoc()) {
			
			$arr[$row['type_id']] = $row;
		}
		
		return $arr;
	}
	
	public static function getProjectScopeTypes($project_id) {
		
		$arr_project = self::getProjects($project_id);
	
		if ($arr_project['project']['full_scope']) {
			
			$arr_types = StoreType::getTypes($arr_project['project']['user_id']);
			$arr_type_ids = array_keys($arr_types);
		} else {
			
			$arr_type_ids = array_keys($arr_project['types']);
		}

		return $arr_type_ids;
	}
	
	public static function checkProjectTypeAcces($project_id, $type_id) {
		
		$arr_project = self::getProjects($project_id);
		
		$found = ($arr_project['types'][$type_id] ? 'project' : false);
		
		if (!$found) {
			
			$arr_types = StoreType::getTypes($arr_project['project']['user_id']);
			
			if ($arr_types[$type_id]) {
				
				$found = ($arr_project['project']['full_scope'] ? 'scope' : 'domain');
			}
		}
		
		return $found;
	}
	
	public static function runUserProjectTypeFilterUpdates($arr_options = []) {
		
		$arr_filter_object_date = ['start' => $arr_options['date_executed']['previous'], 'end' => $arr_options['date_executed']['now']];
		Labels::setVariable('name', false);
		Labels::setVariable('link', false);
		$title = getLabel('msg_filter_notify_title', 'L', true);
		$msg = getLabel('msg_filter_notify', 'L', true);
		
		$arr_types_changed = FilterTypeObjects::getTypesUpdatedSince($arr_options['date_executed']['previous']);
		$arr_updated_types_projects_filters_users = [];
				
		foreach ($arr_types_changed as $type_id => $arr_type) {
				
			$arr_user_project_type_filtering = self::getUserProjectTypeFilters(false, $type_id);
			
			if (!$arr_user_project_type_filtering) {
				continue;
			}
			
			$filter_version = new FilterTypeObjects($type_id, 'id');
			$filter_version->setFilter([
				'object_dating' => ['date' => $arr_filter_object_date]
			]);
			$arr_count = $filter_version->getResultInfo();
			
			if (!$arr_count['total_filtered']) {
				continue;
			}
			
			$table_name_to_temp = $filter_version->storeResultTemporarily(false, true);
			
			foreach ($arr_user_project_type_filtering as $project_id => $arr_project_type_filtering) {
				
				$arr_project = self::getProjects($project_id);
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
								
								$filter = new FilterTypeObjects($type_id, 'id');
								$filter->setScope(['users' => $user_id, 'types' => self::getProjectScopeTypes($project_id)]);
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
									cms_messaging::sendMessage('filter_'.$project_id.'_'.$filter_id, 0, Labels::printLabels(Labels::parseTextVariables($title)), Labels::printLabels(Labels::parseTextVariables($msg)), $user_id, false, ['individual' => true, 'limit' => 15]);
								
									$arr_updated_types_projects_filters_users[$type_id][$project_id][$filter_id][$user_id] = true;
								}
							}
						}
					} catch (Exception $e) {
						
						error('cms_nodegoat_custom_projects::runUserProjectTypeFilterUpdates ERROR:'.PHP_EOL
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
			
			msg('cms_nodegoat_custom_projects::runUserProjectTypeFilterUpdates SUCCESS:'.PHP_EOL.
					'Types = '.$count_types.' Custom Projects = '.$count_projects.' Filters = '.$count_filters.' Users = '.$count_users
			);
		}
	}
}
