<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreCustomProject {
	
	const ACCESS_PURPOSE_VIEW = 'view';
	const ACCESS_PURPOSE_EDIT = 'edit';
	const ACCESS_PURPOSE_CREATE = 'create';
	const ACCESS_PURPOSE_ANY = 'any';
	const ACCESS_PURPOSE_NONE = '';
	
	const MODE_UPDATE = 1;
	const MODE_OVERWRITE = 2;
	
	protected $project_id = false;
	
	protected $mode = self::MODE_OVERWRITE;
	
	public static $arr_projects_storage = [];
		
	public function __construct($project_id) {

		if ($project_id) {
			$this->project_id = (int)$project_id;
		}
		
	}

	public function setMode($mode = self::MODE_UPDATE) {
		
		// $mode = overwite OR update
		// $do_check = true OR false: perform object checks before update
		
		if ($mode !== null) {
			$this->mode = $mode;
		}
	}
	
	public function store($arr) {

		if (!$this->project_id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')."
				("."name, full_scope_enable, source_referencing_enable, discussion_enable, system_date_cycle_enable, system_ingestion_enable, system_reconciliation_enable, visual_settings_id)
					VALUES
				(
					"."
					'".DBFunctions::strEscape($arr['name'])."',
					".DBFunctions::escapeAs($arr['full_scope_enable'], DBFunctions::TYPE_BOOLEAN).",
					".DBFunctions::escapeAs($arr['source_referencing_enable'], DBFunctions::TYPE_BOOLEAN).",
					".DBFunctions::escapeAs($arr['discussion_enable'], DBFunctions::TYPE_BOOLEAN).",
					".DBFunctions::escapeAs($arr['system_date_cycle_enable'], DBFunctions::TYPE_BOOLEAN).",
					".DBFunctions::escapeAs($arr['system_ingestion_enable'], DBFunctions::TYPE_BOOLEAN).",
					".DBFunctions::escapeAs($arr['system_reconciliation_enable'], DBFunctions::TYPE_BOOLEAN).",
					".(int)$arr['visual_settings_id']."
				)
			");
			
			$this->project_id = DB::lastInsertID();
		} else {
						
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." SET
					"."
					name = '".DBFunctions::strEscape($arr['name'])."',
					full_scope_enable = ".DBFunctions::escapeAs($arr['full_scope_enable'], DBFunctions::TYPE_BOOLEAN).",
					source_referencing_enable = ".DBFunctions::escapeAs($arr['source_referencing_enable'], DBFunctions::TYPE_BOOLEAN).",
					discussion_enable = ".DBFunctions::escapeAs($arr['discussion_enable'], DBFunctions::TYPE_BOOLEAN).",
					system_date_cycle_enable = ".DBFunctions::escapeAs($arr['system_date_cycle_enable'], DBFunctions::TYPE_BOOLEAN).",
					system_ingestion_enable = ".DBFunctions::escapeAs($arr['system_ingestion_enable'], DBFunctions::TYPE_BOOLEAN).",
					system_reconciliation_enable = ".DBFunctions::escapeAs($arr['system_reconciliation_enable'], DBFunctions::TYPE_BOOLEAN).",
					visual_settings_id = ".(int)$arr['visual_settings_id']."
				WHERE id = ".$this->project_id."
			");
		}
													
		$arr_sql_keys = [];
		
		$arr_types = StoreType::getTypes();
		
		$func_check_type = function($type_id) use ($arr_types) {
			
			return ($type_id && isset($arr_types[$type_id]));
		};

		if ($arr['types']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['types'] as $type_id) {
				
				if (!$func_check_type($type_id)) {
					continue;
				}
				
				$arr_sql_insert[] = "(".$this->project_id.", ".(int)$type_id.")";
				$arr_sql_keys['types'][] = (int)$type_id;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')."
				(project_id, type_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('project_id, type_id', ['project_id'])."
			");

			$i = 0;

			$arr_ref_type_ids = array_keys($arr_types);

			foreach ((array)$arr['types_organise'] as $str_type_id => $arr_definition) {
				
				$type_id = explode('-', $str_type_id);
				$type_id = (int)$type_id[1];
				
				if (!$func_check_type($type_id)) {
					continue;
				}
				
				$arr_type_set = StoreType::getTypeSet($type_id);
				
				$has_configuration = ($arr_definition['configuration']['object_descriptions'] || $arr_definition['configuration']['object_sub_details']);
				
				$str_information = null;
				if (isset($arr_definition['type_information'])) {
					$str_information = trim($arr_definition['type_information']);
					$str_information = ($str_information ? "'".DBFunctions::strEscape($str_information)."'" : 'NULL');					
				}
				$has_information = ($str_information !== null);
				
				$num_type_edit = ($arr_definition['type_edit'] == static::ACCESS_PURPOSE_CREATE ? 2 : ($arr_definition['type_edit'] == static::ACCESS_PURPOSE_EDIT ? 1 : 0));
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." SET
						color = '".str2Color($arr_definition['color'])."',
						".($has_information ? "type_information = ".$str_information."," : "")."
						type_filter_id = ".(int)$arr_definition['type_filter_id'].",
						type_filter_object_subs = ".DBFunctions::escapeAs($arr_definition['type_filter_object_subs'], DBFunctions::TYPE_BOOLEAN).",
						type_context_id = ".(int)$arr_definition['type_context_id'].",
						type_frame_id = ".(int)$arr_definition['type_frame_id'].",
						type_condition_id = ".(int)$arr_definition['type_condition_id'].",
						type_edit = ".(int)$num_type_edit.",
						configuration_exclude = ".DBFunctions::escapeAs(($has_configuration && $arr_definition['configuration_exclude']), DBFunctions::TYPE_BOOLEAN).",
						sort = ".$i."
					WHERE project_id = ".$this->project_id." AND type_id = ".(int)$type_id."
				");
				
				$i++;

				// Type - configuration
					
				$arr_sql_insert = [];
					
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
					
					$arr_configuration_object_description = ($arr_definition['configuration']['object_descriptions'][$object_description_id] ?? []);
					
					if ($arr_definition['configuration_exclude']) {
						$view = ($arr_configuration_object_description['view'] ? true : false);
						$edit = ($view || $arr_configuration_object_description['edit'] ? true : false);
					} else {
						$edit = ($arr_configuration_object_description['edit'] ? true : false);
						$view = ($edit || $arr_configuration_object_description['view'] ? true : false);
					}
					$filter_id = (int)$arr_configuration_object_description['filter_id'];
					
					$str_information = null;
					if ($has_information) {
						$str_information = trim($arr_configuration_object_description['information']);
					}
					
					$has_data = ($edit || $view || $filter_id || $str_information);
					
					if ($has_data || !$has_information) { // When 'information' is not part of the data, there could still be valid data in the database
						
						$arr_sql_insert[] = "(
							".$this->project_id.", ".(int)$type_id.",
							".(int)$object_description_id.", 0, 0,
							".DBFunctions::escapeAs($edit, DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($view, DBFunctions::TYPE_BOOLEAN).", ".(int)$filter_id."
							".($has_information ? ", ".($str_information ? "'".DBFunctions::strEscape($str_information)."'" : 'NULL') : '')."
						)";

						$arr_sql_keys['configuration'][] = "(
							type_id = ".(int)$type_id."
							AND object_description_id = ".(int)$object_description_id." AND object_sub_details_id = 0 AND object_sub_description_id = 0
							".(!$has_information ? " AND information != NULL" : "")."
						)";
					}
				}
				
				foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					$arr_configuration_object_sub_details = ($arr_definition['configuration']['object_sub_details'][$object_sub_details_id]['object_sub_details'] ?? []);
					
					if ($arr_configuration_object_sub_details) {
						
						if ($arr_definition['configuration_exclude']) {
							$view = ($arr_configuration_object_sub_details['view'] ? true : false);
							$edit = ($view || $arr_configuration_object_sub_details['edit'] ? true : false);
						} else {
							$edit = ($arr_configuration_object_sub_details['edit'] ? true : false);
							$view = ($edit || $arr_configuration_object_sub_details['view'] ? true : false);
						}
						$filter_id = 0;
						
						$str_information = null;
						if ($has_information) {
							$str_information = trim($arr_configuration_object_sub_details['information']);
						}
						
						$has_data = ($edit || $view || $str_information);
						
						if ($has_data || !$has_information) { // When 'information' is not part of the data, there could still be valid data in the database
							
							$arr_sql_insert[] = "(
								".$this->project_id.", ".(int)$type_id.",
								0, ".(int)$object_sub_details_id.", 0,
								".DBFunctions::escapeAs($edit, DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($view, DBFunctions::TYPE_BOOLEAN).", ".(int)$filter_id."
								".($has_information ? ", ".($str_information ? "'".DBFunctions::strEscape($str_information)."'" : 'NULL') : '')."
							)";

							$arr_sql_keys['configuration'][] = "(
								type_id = ".(int)$type_id."
								AND object_description_id = 0 AND object_sub_details_id = ".(int)$object_sub_details_id." AND object_sub_description_id = 0
								".(!$has_information ? " AND information != NULL" : "")."
							)";
						}
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						$arr_configuration_object_sub_description = ($arr_definition['configuration']['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] ?? []);
						
						if ($arr_definition['configuration_exclude']) {
							$view = ($arr_configuration_object_sub_description['view'] ? true : false);
							$edit = ($view || $arr_configuration_object_sub_description['edit'] ? true : false);
						} else {
							$edit = ($arr_configuration_object_sub_description['edit'] ? true : false);
							$view = ($edit || $arr_configuration_object_sub_description['view'] ? true : false);
						}
						$filter_id = (int)$arr_configuration_object_sub_description['filter_id'];
						
						$str_information = null;
						if ($has_information) {
							$str_information = trim($arr_configuration_object_sub_description['information']);
						}
						
						$has_data = ($edit || $view || $filter_id || $str_information);
						
						if ($has_data || !$has_information) { // When 'information' is not part of the data, there could still be valid data in the database
							
							$arr_sql_insert[] = "(
								".$this->project_id.", ".(int)$type_id.",
								0, ".(int)$object_sub_details_id.", ".(int)$object_sub_description_id.",
								".DBFunctions::escapeAs($edit, DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($view, DBFunctions::TYPE_BOOLEAN).", ".(int)$filter_id."
								".($has_information ? ", ".($str_information ? "'".DBFunctions::strEscape($str_information)."'" : 'NULL') : '')."
							)";

							$arr_sql_keys['configuration'][] = "(
								type_id = ".(int)$type_id."
								AND object_description_id = 0 AND object_sub_details_id = ".(int)$object_sub_details_id." AND object_sub_description_id = ".(int)$object_sub_description_id."
								".(!$has_information ? " AND information != NULL" : "")."
							)";
						}
					}
				}
				
				if ($arr_sql_insert) {
					
					$arr_sql_update = ['edit', 'view', 'filter_id'];
					if ($has_information) {
						$arr_sql_update[] = 'information';
					}
	
					$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION')."
						(project_id, type_id, object_description_id, object_sub_details_id, object_sub_description_id, ".implode(',', $arr_sql_update).")
							VALUES
						".implode(",", $arr_sql_insert)."
						".DBFunctions::onConflict('project_id, type_id, object_description_id, object_sub_details_id, object_sub_description_id', $arr_sql_update)."
					");
				}
				
				// Type - include referenced types
				
				$arr_types_referenced = FilterTypeObjects::getTypesReferenced($type_id, $arr_ref_type_ids, ['dynamic' => false, 'object_sub_locations' => false]);
				
				foreach ($arr_types_referenced as $ref_type_id => $arr_type_referenced) {
					
					$arr_sql_insert = [];
				
					foreach ((array)$arr_type_referenced['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						$arr_referenced_object_description = ($arr_definition['include_referenced_types'][$ref_type_id]['object_descriptions'][$object_description_id] ?? []);
						
						$edit = ($arr_referenced_object_description['edit'] ? true : false);
						$view = ($arr_referenced_object_description['view'] ? true : false);
						$filter_id = (int)$arr_referenced_object_description['filter_id'];
						
						$str_information = null;
						if ($has_information) {
							$str_information = trim($arr_referenced_object_description['information']);
						}
						
						$has_data = ($edit || $view || $filter_id || $str_information);
						
						if ($has_data || !$has_information) { // When 'information' is not part of the data, there could still be valid data in the database
							
							$arr_sql_insert[] = "(
								".$this->project_id.", ".(int)$type_id.", ".(int)$ref_type_id.",
								".(int)$object_description_id.", 0, 0,
								".DBFunctions::escapeAs($edit, DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($view, DBFunctions::TYPE_BOOLEAN).", ".(int)$filter_id."
								".($has_information ? ", ".($str_information ? "'".DBFunctions::strEscape($str_information)."'" : 'NULL') : '')."
							)";

							$arr_sql_keys['include_referenced'][] = "(
								type_id = ".(int)$type_id." AND referenced_type_id = ".(int)$ref_type_id."
								AND object_description_id = ".(int)$object_description_id." AND object_sub_details_id = 0 AND object_sub_description_id = 0
								".(!$has_information ? " AND information != NULL" : "")."
							)";
						}
					}
					
					foreach ((array)$arr_type_referenced['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							$arr_referenced_object_sub_description = ($arr_definition['include_referenced_types'][$ref_type_id]['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] ?? []);
							
							$edit = ($arr_referenced_object_sub_description['edit'] ? true : false);
							$view = ($arr_referenced_object_sub_description['view'] ? true : false);
							$filter_id = (int)$arr_referenced_object_sub_description['filter_id'];
							
							$str_information = null;
							if ($has_information) {
								$str_information = trim($arr_referenced_object_sub_description['information']);
							}
							
							$has_data = ($edit || $view || $filter_id || $str_information);
							
							if ($has_data || !$has_information) { // When 'information' is not part of the data, there could still be valid data in the database
									
								$arr_sql_insert[] = "(
									".$this->project_id.", ".(int)$type_id.", ".(int)$ref_type_id.", 
									0, ".(int)$object_sub_details_id.", ".(int)$object_sub_description_id.",
									".DBFunctions::escapeAs($edit, DBFunctions::TYPE_BOOLEAN).", ".DBFunctions::escapeAs($view, DBFunctions::TYPE_BOOLEAN).", ".(int)$filter_id."
									".($has_information ? ", ".($str_information ? "'".DBFunctions::strEscape($str_information)."'" : 'NULL') : '')."
								)";

								$arr_sql_keys['include_referenced'][] = "(
									type_id = ".(int)$type_id." AND referenced_type_id = ".(int)$ref_type_id."
									AND object_description_id = 0 AND object_sub_details_id = ".(int)$object_sub_details_id." AND object_sub_description_id = ".(int)$object_sub_description_id."
									".(!$has_information ? " AND information != NULL" : "")."
								)";
							}
						}
					}
					
					if ($arr_sql_insert) {
							
						$arr_sql_update = ['edit', 'view', 'filter_id'];
						if ($has_information) {
							$arr_sql_update[] = 'information';
						}
						
						$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')."
							(project_id, type_id, referenced_type_id, object_description_id, object_sub_details_id, object_sub_description_id, ".implode(',', $arr_sql_update).")
								VALUES
							".implode(",", $arr_sql_insert)."
							".DBFunctions::onConflict('project_id, type_id, referenced_type_id, object_description_id, object_sub_details_id, object_sub_description_id', $arr_sql_update)."
						");
					}
				}
			}
		}
		
		$func_sql_link_types = function($arr, $sql_table_name) use ($func_check_type) {
			
			if (!$arr) {
				return;
			}
			
			$arr_sql_insert = [];
			
			foreach ($arr as $type_id) {
				
				if (!$func_check_type($type_id)) {
					continue;
				}
				
				$arr_sql_insert[] = "(".$this->project_id.", ".(int)$type_id.")";
				$arr_sql_keys[] = (int)$type_id;
			}
			
			$res = DB::query("INSERT INTO ".$sql_table_name."
				(project_id, type_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('project_id, type_id', ['project_id'])."
			");
			
			return $arr_sql_keys;
		};
		
		$arr_sql_keys['date_types'] = $func_sql_link_types($arr['date_types'], DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_DATE_TYPES'));
		$arr_sql_keys['location_types'] = $func_sql_link_types($arr['location_types'], DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES'));
		$arr_sql_keys['source_types'] = $func_sql_link_types($arr['source_types'], DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES'));
				
		if ($arr['use_projects']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['use_projects'] as $value) {
				
				$arr_sql_insert[] = "(".$this->project_id.", ".(int)$value.")";
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
				WHERE project_id = ".$this->project_id."
					".($arr_sql_keys['types'] ? "AND type_id NOT IN (".implode(",", $arr_sql_keys['types']).")" : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION')."
				WHERE project_id = ".$this->project_id."
					".($arr_sql_keys['configuration'] ? "AND NOT ".implode(" AND NOT ", $arr_sql_keys['configuration']) : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')."
				WHERE project_id = ".$this->project_id."
					".($arr_sql_keys['include_referenced'] ? "AND NOT ".implode(" AND NOT ", $arr_sql_keys['include_referenced']) : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_DATE_TYPES')."
				WHERE project_id = ".$this->project_id."
					".($arr_sql_keys['date_types'] ? "AND type_id NOT IN (".implode(",", $arr_sql_keys['date_types']).")" : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES')."
				WHERE project_id = ".$this->project_id."
					".($arr_sql_keys['location_types'] ? "AND type_id NOT IN (".implode(",", $arr_sql_keys['location_types']).")" : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES')."
				WHERE project_id = ".$this->project_id."
					".($arr_sql_keys['source_types'] ? "AND type_id NOT IN (".implode(",", $arr_sql_keys['source_types']).")" : "")."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_USE_PROJECTS')."
				WHERE project_id = ".$this->project_id."
					".($arr_sql_keys['use_projects'] ? "AND use_project_id NOT IN (".implode(",", $arr_sql_keys['use_projects']).")" : "")."
			;
		");
		
		
		self::$arr_projects_storage[$this->project_id] = false; // Invalidate Project configuration cache
		
		return $this->project_id;
	}
	
	public function addTypes($arr_type_ids) {
		
		if (!is_array($arr_type_ids)) {
			$arr_type_ids = [$arr_type_ids];
		}
		
		$arr_sql_insert = [];
		
		foreach ($arr_type_ids as $type_id) {
			
			$arr_sql_insert[] = "(".$this->project_id.", ".(int)$type_id.")";
		}
		
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')."
			(project_id, type_id)
				VALUES
			".implode(",", $arr_sql_insert)."
		");
	}
	
	public function delProject() {
		
		$res = DB::queryMulti("
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." WHERE id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_DATE_TYPES')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FILTERS')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIOS')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_FRAMES')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_SCOPES')." WHERE project_id = ".$this->project_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_USE_PROJECTS')." WHERE project_id = ".$this->project_id.";
		");
	}
	
	public static function getProjects($project_id = false) {
		
		$str_identifier = (int)$project_id;
		
		$cache = self::$arr_projects_storage[$str_identifier];
		if ($cache) {
			return $cache;
		}
	
		$arr = [];
		
		$arr_types = StoreType::getTypes();
		
		$func_check_type = function($type_id) use ($arr_types) {
			
			return ($type_id && $arr_types[$type_id] !== null);
		};

		$arr_res = DB::queryMulti("
			SELECT p.*,
				pt.type_id, pt.color, pt.type_information, pt.type_filter_id, pt.type_filter_object_subs, pt.type_context_id, pt.type_frame_id, pt.type_condition_id, pt.type_edit, pt.configuration_exclude,
				ptc.object_description_id AS configuration_object_description_id, ptc.object_sub_details_id AS configuration_object_sub_details_id, ptc.object_sub_description_id AS configuration_object_sub_description_id, ptc.edit AS configuration_edit, ptc.view AS configuration_view, ptc.filter_id AS configuration_filter_id, ptc.information AS configuration_information
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." pt ON (pt.project_id = p.id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONFIGURATION')." ptc ON (ptc.project_id = p.id AND ptc.type_id = pt.type_id)
				WHERE TRUE
				"."
				".($project_id ? "AND p.id = ".(int)$project_id."" : "")."
				ORDER BY ".(!$project_id ? "p.name, " : "")."pt.sort;
				
			SELECT p.id,
				pt.type_id,
				ptrt.referenced_type_id, ptrt.object_description_id AS referenced_object_description_id, ptrt.object_sub_details_id AS referenced_object_sub_details_id, ptrt.object_sub_description_id AS referenced_object_sub_description_id, ptrt.edit AS referenced_edit, ptrt.view AS referenced_view, ptrt.filter_id AS referenced_filter_id, ptrt.information AS referenced_information
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." pt ON (pt.project_id = p.id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_INCLUDE_REFERENCED_TYPES')." ptrt ON (ptrt.project_id = p.id AND ptrt.type_id = pt.type_id)
				WHERE TRUE
				"."
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
			
			SELECT p.id,
				pdt.type_id AS date_type_id
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_DATE_TYPES')." pdt ON (pdt.project_id = p.id)
				WHERE TRUE
				"."
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
				
			SELECT p.id,
				plt.type_id AS location_type_id
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_LOCATION_TYPES')." plt ON (plt.project_id = p.id)
				WHERE TRUE
				"."
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
			
			SELECT p.id,
				pst.type_id AS source_type_id
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_SOURCE_TYPES')." pst ON (pst.project_id = p.id)
				WHERE TRUE
				"."
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
				
			SELECT p.id,
				pup.use_project_id
					FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p
					JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_USE_PROJECTS')." pup ON (pup.project_id = p.id)
				WHERE TRUE
				"."
				".($project_id ? "AND p.id = ".(int)$project_id."" : "").";
		");

		while ($arr_row = $arr_res[0]->fetchAssoc()) {
			
			if (!isset($arr[$arr_row['id']])) {
				$arr[$arr_row['id']] = ['project' => $arr_row, 'types' => [], 'date_types' => [], 'location_types' => [], 'source_types' => [], 'use_projects' => []];
			}
			
			if ($func_check_type($arr_row['type_id'])) {
				
				$s_arr =& $arr[$arr_row['id']]['types'][$arr_row['type_id']];
				
				if (!$s_arr) {
					
					$arr_row['type_filter_object_subs'] = DBFunctions::unescapeAs($arr_row['type_filter_object_subs'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['configuration_exclude'] = DBFunctions::unescapeAs($arr_row['configuration_exclude'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['type_edit'] = ($arr_row['type_edit'] == 2 ? static::ACCESS_PURPOSE_CREATE : ($arr_row['type_edit'] == 1 ? static::ACCESS_PURPOSE_EDIT : static::ACCESS_PURPOSE_NONE));
					
					$s_arr = $arr_row;
				}
				
				if ($arr_row['configuration_edit'] || $arr_row['configuration_view'] || $arr_row['configuration_filter_id'] || $arr_row['configuration_information']) {
					
					$s_arr =& $s_arr['configuration'];
					
					if ($arr_row['configuration_object_description_id']) {
						$s_arr['object_descriptions'][$arr_row['configuration_object_description_id']] = ['edit' => DBFunctions::unescapeAs($arr_row['configuration_edit'], DBFunctions::TYPE_BOOLEAN), 'view' => DBFunctions::unescapeAs($arr_row['configuration_view'], DBFunctions::TYPE_BOOLEAN), 'filter_id' => $arr_row['configuration_filter_id'], 'information' => $arr_row['configuration_information']];
					} else if ($arr_row['configuration_object_sub_description_id']) {
						$s_arr['object_sub_details'][$arr_row['configuration_object_sub_details_id']]['object_sub_descriptions'][$arr_row['configuration_object_sub_description_id']] = ['edit' => DBFunctions::unescapeAs($arr_row['configuration_edit'], DBFunctions::TYPE_BOOLEAN), 'view' => DBFunctions::unescapeAs($arr_row['configuration_view'], DBFunctions::TYPE_BOOLEAN), 'filter_id' => $arr_row['configuration_filter_id'], 'information' => $arr_row['configuration_information']];
					} else if ($arr_row['configuration_object_sub_details_id']) {
						$s_arr['object_sub_details'][$arr_row['configuration_object_sub_details_id']]['object_sub_details'] = ['edit' => DBFunctions::unescapeAs($arr_row['configuration_edit'], DBFunctions::TYPE_BOOLEAN), 'view' => DBFunctions::unescapeAs($arr_row['configuration_view'], DBFunctions::TYPE_BOOLEAN), 'filter_id' => $arr_row['configuration_filter_id'], 'information' => $arr_row['configuration_information']];
					}
				}
			}
		}
		
		while ($arr_row = $arr_res[1]->fetchAssoc()) {
			
			if (!$func_check_type($arr_row['type_id']) || !$func_check_type($arr_row['referenced_type_id'])) {
				continue;
			}
			
			$s_arr =& $arr[$arr_row['id']]['types'][$arr_row['type_id']]['include_referenced_types'][$arr_row['referenced_type_id']];
			
			if ($arr_row['referenced_object_description_id']) {
				$s_arr['object_descriptions'][$arr_row['referenced_object_description_id']] = ['edit' => DBFunctions::unescapeAs($arr_row['referenced_edit'], DBFunctions::TYPE_BOOLEAN), 'view' => DBFunctions::unescapeAs($arr_row['referenced_view'], DBFunctions::TYPE_BOOLEAN), 'filter_id' => $arr_row['referenced_filter_id'], 'information' => $arr_row['referenced_information']];
			} else if ($arr_row['referenced_object_sub_description_id']) {
				$s_arr['object_sub_details'][$arr_row['referenced_object_sub_details_id']]['object_sub_descriptions'][$arr_row['referenced_object_sub_description_id']] = ['edit' => DBFunctions::unescapeAs($arr_row['referenced_edit'], DBFunctions::TYPE_BOOLEAN), 'view' => DBFunctions::unescapeAs($arr_row['referenced_view'], DBFunctions::TYPE_BOOLEAN), 'filter_id' => $arr_row['referenced_filter_id'], 'information' => $arr_row['referenced_information']];
			}
		}
		while ($arr_row = $arr_res[2]->fetchAssoc()) {
			
			if (!$func_check_type($arr_row['date_type_id'])) {
				continue;
			}
			
			$arr[$arr_row['id']]['date_types'][$arr_row['date_type_id']] = $arr_row;
		}
		while ($arr_row = $arr_res[3]->fetchAssoc()) {
			
			if (!$func_check_type($arr_row['location_type_id'])) {
				continue;
			}
			
			$arr[$arr_row['id']]['location_types'][$arr_row['location_type_id']] = $arr_row;
		}
		while ($arr_row = $arr_res[4]->fetchAssoc()) {
			
			if (!$func_check_type($arr_row['source_type_id'])) {
				continue;
			}
			
			$arr[$arr_row['id']]['source_types'][$arr_row['source_type_id']] = $arr_row;
		}
		while ($arr_row = $arr_res[5]->fetchAssoc()) {
			
			$arr[$arr_row['id']]['use_projects'][$arr_row['use_project_id']] = $arr_row;
		}
		unset($s_arr);
		
		foreach ($arr as &$arr_project) {

			if ($arr_project['project']['system_date_cycle_enable']) {
				
				$type_id = StoreType::getSystemTypeID('cycle');
				$arr_project['types'][$type_id] = ['type_id' => $type_id];
			}
			if ($arr_project['project']['system_ingestion_enable']) {
				
				$type_id = StoreType::getSystemTypeID('ingestion');
				$arr_project['types'][$type_id] = ['type_id' => $type_id];
			}
			if ($arr_project['project']['system_reconciliation_enable']) {
				
				$type_id = StoreType::getSystemTypeID('reconciliation');
				$arr_project['types'][$type_id] = ['type_id' => $type_id];
			}
		}
		unset($arr_project);
				
		$arr = ($project_id ? current($arr) : $arr);
		
		self::$arr_projects_storage[$str_identifier] = $arr;
		
		return $arr;
	}
	
	public static function getTypeSetReferenced($type_id, $arr_project_type, $purpose = self::ACCESS_PURPOSE_VIEW) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		foreach ((array)$arr_project_type['include_referenced_types'] as $referenced_type_id => $arr_referenced_type) {
			
			$arr_referenced_type_set = StoreType::getTypeSet($referenced_type_id);
			
			foreach ((array)$arr_referenced_type['object_descriptions'] as $object_description_id => $arr_options) {
				
				$arr_referenced_object_description = $arr_referenced_type_set['object_descriptions'][$object_description_id];
				
				if (($purpose != static::ACCESS_PURPOSE_ANY && !$arr_options[$purpose]) || !isset($arr_referenced_object_description) || !$arr_referenced_object_description['object_description_ref_type_id']) {
					continue;
				}
				
				$use_object_description_id = $object_description_id.GenerateTypeObjects::REFERENCED_ID_MODIFIER;
				
				$arr_type_set['type']['include_referenced']['object_descriptions'][$use_object_description_id] = $use_object_description_id;
				
				$arr_type_set['object_descriptions'][$use_object_description_id] = $arr_referenced_object_description;
				
				$s_arr =& $arr_type_set['object_descriptions'][$use_object_description_id];
				$s_arr['object_description_is_referenced'] = true;
				$s_arr['object_description_ref_type_id'] = $referenced_type_id;
				$s_arr['object_description_has_multi'] = true;
				
				if ($arr_referenced_object_description['object_description_is_dynamic']) { // In the referenced view the focus lies with the singular source type, not the dynamic values.
					$s_arr['object_description_is_dynamic'] = false;
					$s_arr['object_description_value_type'] = 'reference';
				} else if (is_array($arr_referenced_object_description['object_description_ref_type_id'])) { // Mutable references become singular plain references.
					$s_arr['object_description_value_type'] = 'reference';
				}
			}
			
			foreach ((array)$arr_referenced_type['object_sub_details'] as $object_sub_details_id => $arr_referenced_object_sub_details) {
				
				foreach ($arr_referenced_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_options) {
					
					$arr_referenced_object_sub_description = $arr_referenced_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
					
					if (($purpose != static::ACCESS_PURPOSE_ANY && !$arr_options[$purpose]) || !isset($arr_referenced_object_sub_description) || !$arr_referenced_object_sub_description['object_sub_description_ref_type_id'] || $arr_referenced_object_sub_description['object_sub_description_use_object_description_id']) {
						continue;
					}
					
					$use_object_sub_details_id = $object_sub_details_id.GenerateTypeObjects::REFERENCED_ID_MODIFIER.$object_sub_description_id;
					
					$arr_type_set['type']['include_referenced']['object_sub_details'][$use_object_sub_details_id][$object_sub_description_id] = $object_sub_description_id;
						
					$arr_type_set['object_sub_details'][$use_object_sub_details_id] = $arr_referenced_type_set['object_sub_details'][$object_sub_details_id];
					$arr_type_set['object_sub_details'][$use_object_sub_details_id]['object_sub_details']['object_sub_details_type_id'] = $referenced_type_id;

					$s_arr =& $arr_type_set['object_sub_details'][$use_object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
					$s_arr['object_sub_description_is_referenced'] = true;
					$s_arr['object_sub_description_ref_type_id'] = $referenced_type_id;
					
					if ($arr_referenced_object_sub_description['object_sub_description_is_dynamic']) { // In the referenced view the focus lies with the singular source type, not the dynamic values.
						$s_arr['object_sub_description_is_dynamic'] = false;
						$s_arr['object_sub_description_value_type'] = 'reference';
					} else if (is_array($arr_referenced_object_sub_description['object_sub_description_ref_type_id'])) { // Mutable references become singular plain references.
						$s_arr['object_sub_description_value_type'] = 'reference';
					}
					
					foreach ($arr_type_set['object_sub_details'][$use_object_sub_details_id]['object_sub_descriptions'] as $arr_check_object_sub_description) {
					
						if ($arr_check_object_sub_description['object_sub_description_use_object_description_id']) {
							
							$use_object_description_id = $arr_check_object_sub_description['object_sub_description_use_object_description_id'];
							
							$arr_type_set['type']['include_helpers']['object_descriptions'][$use_object_description_id] = $arr_referenced_type_set['object_descriptions'][$use_object_description_id];
						}
					}
					
					$arr_object_sub_details_self = $arr_type_set['object_sub_details'][$use_object_sub_details_id]['object_sub_details'];
					
					if ($arr_object_sub_details_self['object_sub_details_has_date']) {
					
						if ($arr_object_sub_details_self['object_sub_details_date_use_object_sub_details_id']) {
							
							$use_object_sub_details_id = $arr_object_sub_details_self['object_sub_details_date_use_object_sub_details_id'];
							
							$arr_type_set['type']['include_helpers']['object_sub_details'][$use_object_sub_details_id] = $arr_referenced_type_set['object_sub_details'][$use_object_sub_details_id];
						} else if ($arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id']) {
							
							$use_object_description_id = $arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id'];
							
							$arr_type_set['type']['include_helpers']['object_descriptions'][$use_object_description_id] = $arr_referenced_type_set['object_descriptions'][$use_object_description_id];
							
							if ($arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id']) {
								
								$use_object_description_id = $arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id'];
							
								$arr_type_set['type']['include_helpers']['object_descriptions'][$use_object_description_id] = $arr_referenced_type_set['object_descriptions'][$use_object_description_id];
							}
						}
					}
					if ($arr_object_sub_details_self['object_sub_details_has_location']) {
						
						if ($arr_object_sub_details_self['object_sub_details_location_use_object_sub_details_id']) {
							
							$use_object_sub_details_id = $arr_object_sub_details_self['object_sub_details_location_use_object_sub_details_id'];
							
							$arr_type_set['type']['include_helpers']['object_sub_details'][$use_object_sub_details_id] = $arr_referenced_type_set['object_sub_details'][$use_object_sub_details_id];
						} else if ($arr_object_sub_details_self['object_sub_details_location_use_object_description_id']) {
							
							$use_object_description_id = $arr_object_sub_details_self['object_sub_details_location_use_object_description_id'];
							
							$arr_type_set['type']['include_helpers']['object_descriptions'][$use_object_description_id] = $arr_referenced_type_set['object_descriptions'][$use_object_description_id];
						}
					}
				}
			}
		}
		
		return $arr_type_set;
	}
	
	public static function checkTypeAccess($project_id, $type_id, $type) {
		
		$arr_project = static::getProjects($project_id);
		
		if ($type_id < 0) {
			
			if ($type_id == StoreType::getSystemTypeID('cycle')) {
				$is_found = $arr_project['project']['system_date_cycle_enable'];
			} else if ($type_id == StoreType::getSystemTypeID('ingestion')) {
				$is_found = $arr_project['project']['system_ingestion_enable'];
			} else if ($type_id == StoreType::getSystemTypeID('reconciliation')) {
				$is_found = $arr_project['project']['system_reconciliation_enable'];
			}
		} else {
			
			$is_found = ($arr_project['types'][$type_id] ? 'project' : false);
		}
		
		if ($type == static::ACCESS_PURPOSE_EDIT || $type == static::ACCESS_PURPOSE_CREATE) {
			
			if ($is_found) {
				
				if ($type_id < 0) {
					
					$is_found = true;
				} else {
					
					$type_edit = $arr_project['types'][$type_id]['type_edit'];
					
					if ($type == static::ACCESS_PURPOSE_CREATE && $type_edit != static::ACCESS_PURPOSE_CREATE) {
						$is_found = false;
					} else if ($type == static::ACCESS_PURPOSE_EDIT && $type_edit != static::ACCESS_PURPOSE_CREATE && $type_edit != static::ACCESS_PURPOSE_EDIT) {
						$is_found = false;
					}
				}
			}
		} else {
			
			if (!$is_found) {
				
				$arr_types = StoreType::getTypes();
				
				if ($arr_types[$type_id]) {
					
					$is_found = ($arr_project['project']['full_scope_enable'] ? 'scope' : 'domain');
				}
			}
		}
		
		return $is_found;
	}
	
	public static function checkTypeConfigurationAccess($arr_project_types, $arr_type_set, $object_description_id, $object_sub_details_id, $object_sub_description_id, $type = self::ACCESS_PURPOSE_VIEW) {
		
		$type_id = $arr_type_set['type']['id'];
		
		if ($object_description_id) {
			
			if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_is_referenced']) {
				
				$type_id = $arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id'];
				$object_description_id = $arr_type_set['object_descriptions'][$object_description_id]['object_description_id'];
			}
		} else if ($object_sub_details_id) {
		
			$referenced_type_id = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_type_id'];
			
			if ($referenced_type_id) { // Referenced
				
				$type_id = $referenced_type_id;
				$object_sub_details_id = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id'];
			}
		}
		
		$arr_project_type = $arr_project_types[$type_id];
		
		$arr_type_configuration = ($arr_project_type['configuration'] ?? null);
		
		if (!$arr_type_configuration) {
			return true;
		}
		
		$has_view_or_edit = arrHasKeysRecursive(['view' => true, 'edit' => true], $arr_type_configuration, true);
		
		if (!$has_view_or_edit) {
			return true;
		}
		
		if ($object_description_id) {
						
			if (!empty($arr_type_configuration['object_descriptions'][$object_description_id][$type])) {
				return ($arr_project_type['configuration_exclude'] ? false : true);
			}
		}
		
		if ($object_sub_details_id) {
			
			if ($object_sub_description_id) {
				
				if (!empty($arr_type_configuration['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id][$type])) {
					return ($arr_project_type['configuration_exclude'] ? false : true);
				}
			} else {
				
				if (!empty($arr_type_configuration['object_sub_details'][$object_sub_details_id]['object_sub_details'][$type])) {
					return ($arr_project_type['configuration_exclude'] ? false : true);
				}
			}
		}
		
		return ($arr_project_type['configuration_exclude'] ? true : false);
	}
	
	public static function getTypeRelatedProjectTypes($arr_type_ids) {
		
		$sql_type_ids = (is_array($arr_type_ids) ? implode(',', arrParseRecursive($arr_type_ids, TYPE_INTEGER)) : (int)$arr_type_ids);
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
	
	public static function getScopeTypes($project_id) {
		
		$arr_project = StoreCustomProject::getProjects($project_id);
	
		if ($arr_project['project']['full_scope_enable']) {
			
			$arr_types = StoreType::getTypes();
			$arr_type_ids = array_keys($arr_types);
		} else {
			
			$arr_type_ids = array_keys($arr_project['types']);
		}

		return $arr_type_ids;
	}
	
	public static function getTypeScenarioHash($project_id, $scenario_id, $use_project_id = false) {
		
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
		
		if (!$arr['hash_date']) {
			
			$date_updated = DBFunctions::str2Date(time());
		} else {
			
			$date_updated = FilterTypeObjects::getTypesUpdatedAfter($arr['hash_date'], static::getScopeTypes(($use_project_id ?: $project_id)), 'last');
			if (!$date_updated) {
				$date_updated = $arr['hash_date'];
			}
		}

		$arr['is_expired'] = ($date_updated > $arr['hash_date']);
		$arr['hash_date'] = $date_updated;
		
		return $arr;
	}
	
	public static function updateTypeScenarioHash($project_id, $scenario_id, $hash, $hash_date, $use_project_id = false) {
		
		if ($use_project_id == $project_id) {
			$use_project_id = false;
		}
		
		$hash_date = DBFunctions::str2Date($hash_date);
		
		$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_CUSTOM_PROJECT_TYPE_SCENARIO_CACHE')."
			(project_id, scenario_id, use_project_id, hash, hash_date)
				VALUES
			(".(int)$project_id.", ".(int)$scenario_id.", ".(int)$use_project_id.", '".DBFunctions::strEscape($hash)."', '".$hash_date."')
			".DBFunctions::onConflict('project_id, scenario_id, use_project_id', ['hash', 'hash_date'])."
		");
		
		return ($res->getAffectedRowCount() ? true : false);
	}
	
	public static function delTypeScenarioHash($project_id, $scenario_id, $use_project_id = false) {
		
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
	
	public static function getWorkspaceMedia($project_id) {
		
		$sql_project_id = ($project_id && is_array($project_id) ? "IN (".implode(',', arrParseRecursive($project_id, TYPE_INTEGER)).")" : "= ".(int)$project_id);
		
		$arr = [];
		
		$arr_res = DB::queryMulti("SELECT pc.*
				FROM ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPE_CONDITIONS')." pc
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS')." p ON (p.id = pc.project_id)
			WHERE
				(
					p.id ".$sql_project_id."
					"."
				)
				AND pc.object LIKE '%".DBFunctions::strEscape('"image":')."%'
			;
		");
		
		while ($arr_row = $arr_res[0]->fetchAssoc()) {
			
			$arr_files = json_decode($arr_row['object'], true);
			$arr_files = arrValuesRecursive('image', $arr_files);
			
			foreach ($arr_files as $str_file) {
				
				$arr[$arr_row['project_id']][] = $str_file;
			}
		}
		
		return $arr;
	}
	
}
