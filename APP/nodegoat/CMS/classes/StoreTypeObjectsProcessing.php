<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreTypeObjectsProcessing extends StoreTypeObjects {
	
	protected static $nr_store_reversal_objects_buffer = 1000;
	protected static $nr_store_reversal_objects_stream = 10000;

	public static function cacheTypesObjectSubs($date_after = false, $date_to = false) {
		
		$table_name_tos_temp = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_tos_temp');
		$table_name_tos_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_tos_changed');
		$table_name_tos_changed_all = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_tos_changed_all');
		
		$table_name_tos = 'nodegoat_tos';
		$table_name_tos_details = $table_name_tos.'_det';
		$count = 4;
		
		if (!$date_after) { // Requires TRUNCATE/DROP
			
			DB::setConnection(DB::CONNECT_CMS);
			
			GenerateTypeObjects::setSQLFunctionObjectSubDate(); // Make sure the date routine is up-to-date
		}
		
		$sql = "
			".(!$date_after ? "
				TRUNCATE ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE_PATH').";
				TRUNCATE ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE').";
				TRUNCATE ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_PATH').";
				TRUNCATE ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION').";
			" : "")."
		";

		$sql_query = "
			SELECT DISTINCT
				nodegoat_tos.id AS object_sub_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					".($date_after ? "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status ON (nodegoat_to_status.object_id = nodegoat_to.id AND nodegoat_to_status.date > '".DBFunctions::str2Date($date_after)."' AND nodegoat_to_status.date <= '".DBFunctions::str2Date($date_to)."')" : "")."
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to.id AND ".GenerateTypeObjects::generateVersioning('any', 'object_sub', 'nodegoat_tos').")
				WHERE ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to')."
		";
		
		$sql .= "
			-- Select all sub-objects from updated sub-objects.
			
			CREATE TEMPORARY TABLE ".$table_name_tos_changed." (
				object_sub_id INT,
					PRIMARY KEY (object_sub_id)
			) ".DBFunctions::sqlTableOptions(($date_after ? DBFunctions::TABLE_OPTION_MEMORY : false))."
				".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query.")"
				:
				"; INSERT INTO ".$table_name_tos_changed."
					(".$sql_query.")
				")."
			;
		";
		
		$sql_collect = "
			-- Duplicate the nodegoat_tos_changed table to include other sub-objects in its path.
			
			CREATE TEMPORARY TABLE ".$table_name_tos_changed_all." (LIKE ".$table_name_tos_changed."".(DB::ENGINE_IS_POSTGRESQL ? " INCLUDING INDEXES" : "").");
				
			INSERT INTO ".$table_name_tos_changed_all." (
				SELECT * FROM ".$table_name_tos_changed."
			);
		";
		
		$func_collect_old = function($sql_table_name) use ($table_name_tos_changed, $table_name_tos_changed_all) {
			
			return "
				-- Update nodegoat_tos_changed to include all sub-object stakeholders in the sub-object's path.
				
				INSERT INTO ".$table_name_tos_changed_all."
					(object_sub_id)
					(SELECT
						DISTINCT nodegoat_tos_cache_path.object_sub_id
							FROM ".$table_name_tos_changed."
						JOIN ".$sql_table_name." nodegoat_tos_cache_path ON (nodegoat_tos_cache_path.path_object_sub_id = ".$table_name_tos_changed.".object_sub_id AND nodegoat_tos_cache_path.state = 0)
					)
					".DBFunctions::onConflict('object_sub_id', false)."
				;
								
				-- Update nodegoat_tos_cache_path to indicate upcoming changes and possible obsoletion.
				
				".DBFunctions::updateWith(
					$sql_table_name, 'nodegoat_tos_cache_path', 'object_sub_id',
					"JOIN ".$table_name_tos_changed_all." ON (".$table_name_tos_changed_all.".object_sub_id = nodegoat_tos_cache_path.object_sub_id)",
					['state' => '1']
				).";
			";
		};
		
		$func_collect_new = function($sql_table_name, $arr_source) use ($table_name_tos_temp, $count) {
				
			$arr_sql = [];

			for ($i = 1; $i <= $count; $i++) {
				
				$arr_sql['columns'][] = "path_".$i."_object_sub_id INT";
				
				$arr_sql['values'][] = $arr_source['column_path_'.$i.'_object_sub_id']." AS path_".$i."_object_sub_id";
				
				$arr_sql['insert'][] = "INSERT INTO ".$sql_table_name."
					(
						object_sub_id,
						path_object_sub_id,
						active,
						status
					)
					(
						SELECT object_sub_id, path_".$i."_object_sub_id AS path_object_sub_id, active, status
							FROM ".$table_name_tos_temp."
							WHERE path_".$i."_object_sub_id IS NOT NULL
					)
					".DBFunctions::onConflict('object_sub_id, path_object_sub_id, active, status', false, "state = 0")."
				;";
			}
			
			return $arr_sql;
		};
		
		// Initialise
		
		DB::startTransaction('cache_types_objects');
		DB::queryMulti($sql);
		DB::commitTransaction('cache_types_objects');
		
		// Date
		
		if ($date_after) {
			
			$sql_collect_related = "
				-- Update nodegoat_tos_loc_changed_all to include all sub-object stakeholders related to Cycles in the sub-object's path.
				
				INSERT INTO ".$table_name_tos_changed_all."
					(object_sub_id)
					(SELECT DISTINCT
						nodegoat_tos.id AS object_sub_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status ON (nodegoat_to_status.object_id = nodegoat_to.id AND nodegoat_to_status.date > '".DBFunctions::str2Date($date_after)."' AND nodegoat_to_status.date <= '".DBFunctions::str2Date($date_to)."')
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')." nodegoat_tos_date_chrono ON (nodegoat_tos_date_chrono.cycle_object_id = nodegoat_to.id AND nodegoat_tos_date_chrono.active = TRUE)
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_date_chrono.object_sub_id AND nodegoat_tos.date_version = nodegoat_tos_date_chrono.version AND ".GenerateTypeObjects::generateVersioning('any', 'object_sub', 'nodegoat_tos').")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2 ON (nodegoat_to2.id = nodegoat_tos.object_id AND ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to2').")
						WHERE nodegoat_to.type_id = ".StoreType::getSystemTypeID('cycle')." AND ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to')."
					)
					".DBFunctions::onConflict('object_sub_id', false)."
				;
			";
		} else {
			$sql_collect_related = '';
		}
		
		$arr_source = GenerateTypeObjects::format2SQLObjectSubDate($table_name_tos, StoreType::DATE_START_START, $count, false, $table_name_tos_details);
		
		$arr_sql_paths = $func_collect_new(DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE_PATH'), $arr_source);
		
		$func_update_date = function($sql_field_target, $arr_source, $is_null = true) use ($table_name_tos_temp, $table_name_tos_changed_all, $arr_sql_paths, $table_name_tos, $table_name_tos_details, $date_after, $date_to) {
			
			return "
				-- Create temporary table for new ".$sql_field_target." values.
				
				CREATE TEMPORARY TABLE ".$table_name_tos_temp." (
					object_sub_id INT,
					date_value BIGINT NULL,
					".implode(',', $arr_sql_paths['columns']).",
					active BOOLEAN,
					status SMALLINT,
						PRIMARY KEY (object_sub_id, active, status)
				) ".DBFunctions::sqlTableOptions(($date_after ? DBFunctions::TABLE_OPTION_MEMORY : false)).";
				
				-- Select and store all new ".$sql_field_target." values.
				
				INSERT INTO ".$table_name_tos_temp."
					(
						SELECT
							".$table_name_tos.".id AS object_sub_id,
							".$arr_source['column_date']." AS date_value,
							".implode(',', $arr_sql_paths['values']).",
							".$table_name_tos.".active,
							".$table_name_tos.".status
								FROM ".$table_name_tos_changed_all."
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_tos." ON (".$table_name_tos.".id = ".$table_name_tos_changed_all.".object_sub_id AND ".GenerateTypeObjects::generateVersioning('any', 'object_sub', $table_name_tos).")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = ".$table_name_tos.".object_id AND ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to').")
								JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$table_name_tos_details." ON (".$table_name_tos_details.".id = ".$table_name_tos.".object_sub_details_id AND ".$table_name_tos_details.".has_date = TRUE)
								".$arr_source['tables']."
					)
					".DBFunctions::onConflict('object_sub_id, active, status', false)."
				;
				
				-- Process! (".$sql_field_target.")
				
				INSERT INTO ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE')."
					(
						object_sub_id,
						".$sql_field_target.",
						active,
						status
					)
					(
						SELECT
							object_sub_id,
							".(!$is_null ? "COALESCE(date_value, 0)" : "date_value").",
							active,
							status
								FROM ".$table_name_tos_temp."
					)
					".DBFunctions::onConflict('object_sub_id, active, status', [$sql_field_target], "state = 0")."
				;
				
				-- Process paths! (".$sql_field_target.")
				
				".implode(" ", $arr_sql_paths['insert'])."
				
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_tos_temp.";
			";
		};
				
		$sql = "
			".$sql_collect."

			".$sql_collect_related."
		
			".$func_collect_old(DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE_PATH'))."
			
			".DBFunctions::updateWith(
				DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE'), 'nodegoat_tos_cache', 'object_sub_id',
				"JOIN ".$table_name_tos_changed_all." ON (".$table_name_tos_changed_all.".object_sub_id = nodegoat_tos_cache.object_sub_id)",
				['state' => '1']
			).";
			
			".$func_update_date('date_start_start', $arr_source, false)."
			".$func_update_date('date_start_end', GenerateTypeObjects::format2SQLObjectSubDate($table_name_tos, StoreType::DATE_START_END, $count, false, $table_name_tos_details))."
			".$func_update_date('date_end_start', GenerateTypeObjects::format2SQLObjectSubDate($table_name_tos, StoreType::DATE_END_START, $count, false, $table_name_tos_details))."
			".$func_update_date('date_end_end', GenerateTypeObjects::format2SQLObjectSubDate($table_name_tos, StoreType::DATE_END_END, $count, false, $table_name_tos_details), false)."
			
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_tos_changed_all.";
		";
		
		DB::startTransaction('cache_types_objects');
		DB::queryMulti($sql);
		DB::commitTransaction('cache_types_objects');

		// Location
		
		if ($date_after) {
		
			$sql_collect_related = "
				-- Include the Sub-Objects from Objects that reference Objects but do currently not have any actual resolved Sub-Objects.
				
				INSERT INTO ".$table_name_tos_changed_all."
					(object_sub_id)
					(SELECT DISTINCT
						nodegoat_tos.id AS object_sub_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status ON (nodegoat_to_status.object_id = nodegoat_to.id AND nodegoat_to_status.date > '".DBFunctions::str2Date($date_after)."' AND nodegoat_to_status.date <= '".DBFunctions::str2Date($date_to)."')
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.location_ref_object_id = nodegoat_to.id AND ".GenerateTypeObjects::generateVersioning('any', 'object_sub', 'nodegoat_tos')."
								AND NOT EXISTS (SELECT TRUE
										FROM ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_PATH')." nodegoat_tos_cache_path
									WHERE nodegoat_tos_cache_path.path_object_sub_id = nodegoat_tos.id AND nodegoat_tos_cache_path.state = 0
								)
							)
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2 ON (nodegoat_to2.id = nodegoat_tos.object_id AND ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to2').")
						WHERE ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to')."
					)
					".DBFunctions::onConflict('object_sub_id', false)."
				;
			";
		} else {
			$sql_collect_related = '';
		}
		
		$arr_source = GenerateTypeObjects::format2SQLObjectSubLocationReference($table_name_tos, $count, $table_name_tos_details);
		
		$arr_sql_paths = $func_collect_new(DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_PATH'), $arr_source);
				
		$sql = "
			".$sql_collect."
			
			".$sql_collect_related."
			
			".$func_collect_old(DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_PATH'))."
					
			-- Update nodegoat_tos_loc_cache to indicate upcoming changes and possible obsoletion.
			
			".DBFunctions::updateWith(
				DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION'), 'nodegoat_tos_loc_cache', 'object_sub_id',
				"JOIN ".$table_name_tos_changed_all." ON (".$table_name_tos_changed_all.".object_sub_id = nodegoat_tos_loc_cache.object_sub_id)",
				['state' => '1']
			).";
			
			-- Create temporary table for new location values.
			
			CREATE TEMPORARY TABLE ".$table_name_tos_temp." (
				object_sub_id INT,
				object_sub_details_id INT,
				geometry_object_sub_id INT,
				geometry_object_id INT,
				geometry_type_id INT,
				ref_object_id INT,
				ref_type_id INT,
				ref_object_sub_details_id INT,
				".implode(',', $arr_sql_paths['columns']).",
				active BOOLEAN,
				status SMALLINT,
					PRIMARY KEY (object_sub_id, geometry_object_sub_id, active, status)
			) ".DBFunctions::sqlTableOptions(($date_after ? DBFunctions::TABLE_OPTION_MEMORY : false)).";
			
			-- Select and store all new location values, use conflict clause in case of duplicate end/geometry sub-objects.
			
			INSERT INTO ".$table_name_tos_temp."
				(
					SELECT DISTINCT
						".$table_name_tos.".id AS object_sub_id,
						".$table_name_tos.".object_sub_details_id,
						COALESCE(".$arr_source['column_geometry_object_sub_id'].", 0) AS geometry_object_sub_id,
						COALESCE(".$arr_source['column_geometry_object_id'].", 0) AS geometry_object_id,
						COALESCE(".$arr_source['column_geometry_type_id'].", 0) AS geometry_type_id,
						".$arr_source['column_ref_show_object_id']." AS ref_object_id,
						".$arr_source['column_ref_show_type_id']." AS ref_type_id,
						".$arr_source['column_ref_show_object_sub_details_id']." AS ref_object_sub_details_id,
						".implode(',', $arr_sql_paths['values']).",
						".$table_name_tos.".active,
						".$table_name_tos.".status
							FROM ".$table_name_tos_changed_all."
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_tos." ON (".$table_name_tos.".id = ".$table_name_tos_changed_all.".object_sub_id AND ".GenerateTypeObjects::generateVersioning('any', 'object_sub', $table_name_tos).")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = ".$table_name_tos.".object_id AND ".GenerateTypeObjects::generateVersioning('any', 'object', 'nodegoat_to').")
							JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$table_name_tos_details." ON (".$table_name_tos_details.".id = ".$table_name_tos.".object_sub_details_id AND ".$table_name_tos_details.".has_location = TRUE)
							".$arr_source['tables']."
						WHERE ".$arr_source['has_geometry']." OR ".$arr_source['has_reference']."
				)
				".DBFunctions::onConflict('object_sub_id, geometry_object_sub_id, active, status', false)."
			;
			
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_tos_changed_all.";
			
			-- Process!
				
			INSERT INTO ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION')."
				(
					object_sub_id,
					object_sub_details_id,
					geometry_object_sub_id,
					geometry_object_id,
					geometry_type_id,
					ref_object_id,
					ref_type_id,
					ref_object_sub_details_id,
					active,
					status
				)
				(
					SELECT
						object_sub_id,
						object_sub_details_id,
						geometry_object_sub_id,
						geometry_object_id,
						geometry_type_id,
						ref_object_id,
						ref_type_id,
						ref_object_sub_details_id,
						active,
						status
							FROM ".$table_name_tos_temp."
				)
				".DBFunctions::onConflict('object_sub_id, geometry_object_sub_id, active, status', ['ref_object_id', 'ref_type_id', 'ref_object_sub_details_id'], "state = 0")."
			;
			
			-- Process paths!
					
			".implode(" ", $arr_sql_paths['insert'])."
			
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_tos_temp.";
		";
		
		DB::startTransaction('cache_types_objects');
		DB::queryMulti($sql);
		DB::commitTransaction('cache_types_objects');
		
		// Cleanup
		
		$sql = "
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_tos_changed.";
		";
		
		DB::startTransaction('cache_types_objects');
		DB::queryMulti($sql);
		DB::commitTransaction('cache_types_objects');
		
		DB::setConnection();
	}
    
    public static function setReversals($arr_types) {
		
		$arr_updated_types_categories_referenced_types = [];
		$arr_skipped_types_categories = [];
		
		foreach ($arr_types as $type_id => $arr_type) {
						
			$filter_categories = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ALL, false, false);
			$arr_types_all = StoreType::getTypes();
			$arr_scope_type_ids = array_keys($arr_types_all);
			
			$arr_categories = $filter_categories->init();
			
			$arr_types_referenced = [];
			$arr_sql_ids = ['object_descriptions' => [], 'object_sub_details_locations' => [], 'object_sub_details_locations_locked' => [], 'object_sub_descriptions' => []];
			
			$store_state = new StoreTypeObjects($type_id, false, false, $arr_type['user_id']);

			foreach ($arr_categories as $category_id => $arr_category) {
				
				$store_state->setObjectID($category_id);
				$bit_state = $store_state->getModuleState();
				
				if (bitHasMode($bit_state, StoreTypeObjects::MODULE_STATE_DISABLED)) {
					
					$arr_skipped_types_categories[$type_id][$category_id] = true;
					continue;
				}
				
				$arr_object_definition = $arr_category['object_definitions'][StoreType::getSystemTypeObjectDescriptionID(StoreType::getSystemTypeID('reversal'), 'module')];
				$arr_object_definition_value = ($arr_object_definition['object_definition_value'] ? json_decode($arr_object_definition['object_definition_value'], true) : []);
								
				foreach ($arr_object_definition_value as $ref_type_id => $arr_object_type_filter) {
					
					try {
						
						if (!is_array($arr_types_referenced[$ref_type_id])) {
							$arr_types_referenced[$ref_type_id] = FilterTypeObjects::getTypesReferenced($type_id, $ref_type_id, ['dynamic' => false]);
						}
						
						$arr_type_referenced = $arr_types_referenced[$ref_type_id];
						
						$arr_scope = ($arr_type['mode'] & StoreType::TYPE_MODE_REVERSAL_COLLECTION && $arr_object_type_filter['scope'] ? $arr_object_type_filter['scope'] : false);
						$arr_filters = $arr_object_type_filter['filter'];
						
						if ((!$arr_filters && !$arr_scope) || !$arr_type_referenced) {
							continue;
						}

						$arr_filters = FilterTypeObjects::convertFilterInput($arr_filters);
						
						$arr_selection_source = [];
						$arr_filtering_source = [];
						
						if ($arr_type_referenced['object_sub_details']) { // Use filtering to specifically target sub-objects when applicable (being filtered on)
							
							foreach ($arr_type_referenced['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
								
								$arr_selection_source['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => false, 'object_sub_descriptions' => []];
								
								if ($arr_object_sub_details['object_sub_location'] && !$arr_object_sub_details['object_sub_location']['location_use_other']) {
									
									$arr_selection_source['object_sub_details'][$object_sub_details_id]['object_sub_details'] = true;
								}
								
								foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $value) {
									
									$arr_selection_source['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
								}
							}
							
							$filter = new FilterTypeObjects($ref_type_id, GenerateTypeObjects::VIEW_ALL, false, false);
							$filter->setScope(['types' => $arr_scope_type_ids]);
							$filter->setFiltering(['all' => true], ['all' => true]);
							$filter->setFilter($arr_filters);
							
							foreach ($arr_selection_source['object_sub_details'] as $object_sub_details_id => $value) {
								
								if (!$filter->isFilteringObjectSubDetails($object_sub_details_id)) { // Check if the sub-object is really filtered on and therefore is needed
									unset($arr_selection_source['object_sub_details'][$object_sub_details_id]);
								}
							}

							$arr_filtering_source = ['all' => true];
						}
						
						if ($arr_scope) {
							
							$arr_use_paths = [];
							//$get_name = (arrValuesRecursive('in_name', $arr_type_referenced) || arrValuesRecursive('in_search', $arr_type_referenced)); // When in summary mode, cache object names for query and in-name usage
							$get_name = true;
						}
						
						if ($arr_scope && $arr_scope['paths']) {
			
							$trace = new TraceTypesNetwork($arr_scope_type_ids, true, true);
							$trace->filterTypesNetwork($arr_scope['paths']);
							$trace->run($ref_type_id, false, 3);
							$arr_type_network_paths = $trace->getTypeNetworkPaths(true);
						} else {
							
							$arr_type_network_paths = ['start' => [$ref_type_id => ['path' => [0]]]];
						}
						
						$collect = new CollectTypesObjects($arr_type_network_paths, GenerateTypeObjects::VIEW_ALL);
						$collect->setScope(['types' => $arr_scope_type_ids]);
						$collect->setConditions(false);
						$collect->init($arr_filters, false);
						
						$arr_collect_info = $collect->getResultInfo();
						
						foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
							
							foreach ($arr_paths as $path) {
								
								$source_path = $path;
								if ($source_path) { // Path includes the target type id, remove it
									$source_path = explode('-', $source_path);
									array_pop($source_path);
									$source_path = implode('-', $source_path);
								}
								
								$arr_settings = ($arr_scope ? $arr_scope['types'][$source_path][$cur_type_id] : []);
								
								$arr_filtering = [];
								if ($arr_settings['filter']) {
									$arr_filtering = ['all' => true];
								}

								$arr_selection = [
									'object' => [],
									'object_descriptions' => [],
									'object_sub_details' => []
								];
								
								if ($source_path == '0') { // Check for specific sub-object filtering at the start when applicable
									
									if ($arr_selection_source['object_sub_details']) {
																				
										$arr_selection['object_sub_details'] = $arr_selection_source['object_sub_details'];
										$arr_filtering = $arr_filtering_source;
									}
								}
							
								if ($arr_scope && $arr_collect_info['connections'][$path]['end']) { // End of a path, use it
									
									$arr_use_paths[$path] = $path;
								}
			
								$collect->setPathOptions([$path => [
									'arr_selection' => $arr_selection,
									'arr_filtering' => $arr_filtering
								]]);
							}
						}
						
						$collect->setInitLimit(static::$nr_store_reversal_objects_stream);

						while ($collect->init($arr_filters)) {
																			
							$filter = $collect->getResultSource(0, 'start');
							$sql_table_name_source = $filter->storeResultTemporarily();
							
							$sql_value_text = StoreType::getValueTypeValue('reversed_collection', 'name');
							$sql_table_text_affix = StoreType::getValueTypeTable('reversed_collection', 'name');
							
							DB::startTransaction('store_reversal_type_objects');
								
							if ($arr_type_referenced['object_descriptions']) {

								foreach ($arr_type_referenced['object_descriptions'] as $object_description_id => $arr_object_description) {

									$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')."
										(object_id, object_description_id, ref_object_id, identifier, version, active, status)
										SELECT nodegoat_to_source.id, ".$object_description_id.", ".$category_id.", 0, 1, TRUE, 10
											FROM ".$sql_table_name_source." AS nodegoat_to_source
										".DBFunctions::onConflict('object_id, object_description_id, ref_object_id, identifier, version', ['active', 'status'])."
									");
									
									$arr_sql_ids['object_descriptions'][$object_description_id] = $object_description_id;
									
									if ($arr_scope) { // Add objects when has reversed classification is in summary mode
										
										$arr_objects = $collect->getPathObjects('0');
										$arr_sql_insert = [];
										
										foreach ($arr_objects as $object_id => $arr_object) {
							
											$arr_walked = $collect->getWalkedObject($object_id, [], function &($cur_target_object_id, &$cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) use ($arr_use_paths, $get_name) {
												
												if ($arr_use_paths[$cur_path]) {
													
													$cur_arr[$cur_target_object_id] = $cur_target_type_id;
												}

												return $cur_arr;
											});
											
											if ($arr_walked) {
													
												foreach ($arr_walked as $cur_target_object_id => $cur_target_type_id) {
													
													$arr_sql_insert[] = "(".$object_description_id.", ".$object_id.", ".$cur_target_object_id.", ".$cur_target_type_id.", ".$category_id.", 2)";
												}
											}
										}
										
										unset($arr_walked, $arr_objects);
										
										if ($arr_sql_insert) {
											
											$count = 0;
											$arr_sql_chunk = array_slice($arr_sql_insert, $count, static::$nr_store_reversal_objects_buffer);
											
											while ($arr_sql_chunk) {
												
												$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')."
													(object_description_id, object_id, ref_object_id, ref_type_id, identifier, state)
														VALUES
													".implode(',', $arr_sql_chunk)."
													".DBFunctions::onConflict('object_description_id, object_id, ref_object_id, identifier', ['state'])."
												");
												
												$count += static::$nr_store_reversal_objects_buffer;
												$arr_sql_chunk = array_slice($arr_sql_insert, $count, static::$nr_store_reversal_objects_buffer);
											}
											
											unset($arr_sql_insert);
											
											if ($get_name) {

												foreach ($arr_use_paths as $path) {
													
													$arr_collect_filters = $collect->getResultSource($path);

													foreach ($arr_collect_filters as $in_out => $arr_path_filters) {
														
														foreach ($arr_path_filters as $path_filter) {
														
															$sql_table_name_names = $path_filter->storeResultTemporarily();
															$sql_dynamic_type_name_column = $path_filter->generateNameColumn('nodegoat_to_name.id');
															
															$sql_query_name = "SELECT nodegoat_to_source.id, ".DBFunctions::sqlImplode($sql_dynamic_type_name_column['column'], ', ')." AS name, ".$object_description_id.", 0, ".static::VERSION_OFFSET_ALTERNATE_ACTIVE.", FALSE, 10
																	FROM ".$sql_table_name_names." nodegoat_to_name
																	JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref ON (nodegoat_to_def_ref.ref_object_id = nodegoat_to_name.id AND nodegoat_to_def_ref.state = 2)
																	JOIN ".$sql_table_name_source." AS nodegoat_to_source ON (nodegoat_to_source.id = nodegoat_to_def_ref.object_id)
																	".$sql_dynamic_type_name_column['tables']."
																GROUP BY nodegoat_to_source.id
															";
															
															$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_text_affix."
																(object_id, ".$sql_value_text.", object_description_id, identifier, version, active, status)
																".$sql_query_name."
																".DBFunctions::onConflict('object_description_id, object_id, identifier, version', false, $sql_value_text." = CASE WHEN status = 10 THEN CONCAT(".$sql_value_text.", ', ', [".$sql_value_text."]) ELSE [".$sql_value_text."] END, status = 10")."
															");
														}
													}
												}
											}
										}
										
										$arr_sql_ids['object_description_objects'][$object_description_id] = $object_description_id;
										if ($get_name) {
											$arr_sql_ids['object_description_texts'][$object_description_id] = $object_description_id;
										}
									}
								}
							}
							
							if ($arr_type_referenced['object_sub_details']) {
									
								foreach ($arr_type_referenced['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
									
									if ($arr_object_sub_details['object_sub_location'] && !$arr_object_sub_details['object_sub_location']['location_use_other']) {
										
										$is_filtering = $arr_selection_source['object_sub_details'][$object_sub_details_id]['object_sub_details'];
										
										// Update status with 10 because sub-objects could already have a different status

										$res = DB::query("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos SET
												location_ref_object_id = ".$category_id.",
												location_ref_type_id = ".$type_id.",
												status = CASE WHEN status < 10 THEN status + 10 ELSE status END
											WHERE nodegoat_tos.object_sub_details_id = ".$object_sub_details_id."
												".(!$arr_object_sub_details['object_sub_location']['location_ref_type_id_locked'] ? "AND nodegoat_tos.location_ref_type_id IN (0, ".$type_id.")" : "")."
												AND EXISTS (SELECT TRUE
														FROM ".$sql_table_name_source." AS nodegoat_to_source
													WHERE ".($is_filtering ? "nodegoat_tos.id = nodegoat_to_source.object_sub_".$object_sub_details_id."_id" : "nodegoat_tos.object_id = nodegoat_to_source.id")."
												)
												AND nodegoat_tos.active = TRUE
										");
										
										if ($arr_object_sub_details['object_sub_location']['location_ref_type_id_locked']) {
											$arr_sql_ids['object_sub_details_locations_locked'][$object_sub_details_id] = $object_sub_details_id;
										} else {
											$arr_sql_ids['object_sub_details_locations'][$object_sub_details_id] = $object_sub_details_id;
										}
									}
									
									if ($arr_object_sub_details['object_sub_descriptions']) {
											
										foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
											
											$is_filtering = $arr_selection_source['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];

											$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')."
												(object_sub_id, object_sub_description_id, ref_object_id, version, active, status)
												SELECT nodegoat_tos.id, ".$object_sub_description_id.", ".$category_id.", 1, TRUE, 10
													FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
													JOIN ".$sql_table_name_source." AS nodegoat_to_source ON (".($is_filtering ? "nodegoat_to_source.object_sub_".$object_sub_details_id."_id = nodegoat_tos.id" : "nodegoat_tos.object_id = nodegoat_to_source.id").")
												WHERE nodegoat_tos.object_sub_details_id = ".$object_sub_details_id."
													AND nodegoat_tos.active = TRUE
												".DBFunctions::onConflict('object_sub_id, object_sub_description_id, ref_object_id, version', false, "active = TRUE, status = 10")."
											");
											
											$arr_sql_ids['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
										}
									}
								}
							}
							
							DB::commitTransaction('store_reversal_type_objects');
						}
						
						$arr_updated_types_categories_referenced_types[$type_id][$category_id][$ref_type_id] = true;
					} catch (Exception $e) {
						
						$store_state = new StoreTypeObjects($type_id, $category_id, false, $arr_type['user_id']);
						$store_state->updateModuleState(StoreTypeObjects::MODULE_STATE_DISABLED, BIT_MODE_ADD);
						
						error(__METHOD__.' ERROR:'.PHP_EOL
							.'	Type = '.$type_id.' Category = '.$category_id.' Referenced Type = '.$ref_type_id,
						TROUBLE_NOTICE, LOG_BOTH, false, $e); // Make notice
					}
					
					// Cleanup 
			
					GenerateTypeObjects::cleanupResults();
					unset($collect, $filter);
				}
			}
			
			$sql = '';
			
			if ($arr_sql_ids['object_descriptions']) {
				
				$sql .= "
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." SET
							active = FALSE
						WHERE object_description_id IN (".implode(',', $arr_sql_ids['object_descriptions']).")
							AND status = 0
					;
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." SET
							status = 0
						WHERE object_description_id IN (".implode(',', $arr_sql_ids['object_descriptions']).")
							AND status = 10
					;
				";
				
				if ($arr_sql_ids['object_description_objects']) {
					
					$sql .= "
						UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." SET
								state = 0
							WHERE object_description_id IN (".implode(',', $arr_sql_ids['object_description_objects']).")
								AND state = 1
						;
						UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." SET
								state = 1
							WHERE object_description_id IN (".implode(',', $arr_sql_ids['object_description_objects']).")
								AND state = 2
						;
					";
					
					if ($arr_sql_ids['object_description_texts']) {
												
						$sql .= "
							UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('text')." SET
									active = FALSE
								WHERE object_description_id IN (".implode(',', $arr_sql_ids['object_descriptions']).")
									AND status = 0
									AND version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE."
							;
							UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('text')." SET
									status = 0
								WHERE object_description_id IN (".implode(',', $arr_sql_ids['object_descriptions']).")
									AND status = 10
									AND version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE."
							;
						";
					}
				}
			}
			
			if ($arr_sql_ids['object_sub_descriptions']) {
				
				$sql .= "
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." SET
							active = FALSE
						WHERE object_sub_description_id IN (".implode(',', $arr_sql_ids['object_sub_descriptions']).")
							AND status = 0
					;	
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." SET
							status = 0
						WHERE object_sub_description_id IN (".implode(',', $arr_sql_ids['object_sub_descriptions']).")
							AND status = 10
					;
				";
			}
			
			if ($arr_sql_ids['object_sub_details_locations']) { // Only update sub-objects that are specifically selected for location reversal 
								
				$sql .= "
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
							location_ref_object_id = 0
						WHERE object_sub_details_id IN (".implode(',', $arr_sql_ids['object_sub_details_locations']).")
							AND status < 10
							AND active = TRUE
							AND location_ref_type_id IN (0, ".$type_id.")
					;
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
							status = status - 10
						WHERE object_sub_details_id IN (".implode(',', $arr_sql_ids['object_sub_details_locations']).")
							AND status >= 10
							AND active = TRUE
							AND location_ref_type_id IN (0, ".$type_id.")
					;
				";
			}
			
			if ($arr_sql_ids['object_sub_details_locations_locked']) { // Update all sub-objects for location reversal 

				$sql .= "
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
							location_ref_object_id = 0
						WHERE object_sub_details_id IN (".implode(',', $arr_sql_ids['object_sub_details_locations_locked']).")
							AND status < 10
							AND active = TRUE
					;
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
							status = status - 10
						WHERE object_sub_details_id IN (".implode(',', $arr_sql_ids['object_sub_details_locations_locked']).")
							AND status >= 10
							AND active = TRUE
					;
				";
			}
			
			if ($sql) {
				
				$res = DB::queryMulti($sql);
			}
		}
		
		if ($arr_updated_types_categories_referenced_types || $arr_skipped_types_categories) {
			
			$count_types = 0;
			$count_categories = 0;
			$count_referenced_types = 0;
			$count_skipped_categories = 0;
			
			foreach ($arr_updated_types_categories_referenced_types as $type_id => $arr_categories_referenced_types) {
				
				$count_types++;
				
				foreach ($arr_categories_referenced_types as $category_id => $arr_referenced_types) {
					
					$count_categories++;
					$count_referenced_types += count($arr_referenced_types);
				}
			}
			
			foreach ($arr_skipped_types_categories as $type_id => $arr_categories) {
				
				$count_skipped_categories += count($arr_categories);
			}
			
			msg('StoreTypeObjectsProcessing::setReversals SUCCESS:'.PHP_EOL
				.'	Types = '.$count_types
				.' Categories = '.$count_categories
				.' Referenced Types = '.$count_referenced_types
				.($count_skipped_categories ? ' Skipped Categories = '.$count_skipped_categories : '')
			);
		}
	}
	
	public static function revertTypeObjectsVersion($type_id, $date, $do_deleted_only = false, $sql_table_name = false) { // In development. TODO: also check/use status, check/use user ID.
		
		$sql = '';
		$sql_date = DBFunctions::str2Date($date);
		
		if (!$sql_table_name) {
			
			$sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp_revert');
			
			DB::queryMulti("
				DROP TEMPORARY TABLE IF EXISTS ".$sql_table_name.";
				
				CREATE TEMPORARY TABLE ".$sql_table_name." (
					PRIMARY KEY (id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)." AS (
					SELECT id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						WHERE nodegoat_to.type_id = ".(int)$type_id."
							AND EXISTS (
								SELECT TRUE
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
								WHERE nodegoat_to_ver.object_id = nodegoat_to.id
									AND nodegoat_to_ver.date > '".$sql_date."'
									".($do_deleted_only ? "AND nodegoat_to_ver.version = 0" : "")."
							)
						GROUP BY nodegoat_to.id
				);
			");
		}
		
		$sql .= "
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				"JOIN ".$sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to.id)",
				['active' => 'FALSE']
			)."
			;
			
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				"JOIN ".$sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to.id)",
				['active' => 'TRUE']
			)."
				AND nodegoat_to.version = (SELECT
					CASE
						WHEN version = -1 THEN 1
						ELSE version
					END AS version
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
					WHERE nodegoat_to_ver.object_id = nodegoat_to.id AND nodegoat_to_ver.date <= '".$sql_date."'
					ORDER BY nodegoat_to_ver.date DESC, nodegoat_to_ver.version DESC
					LIMIT 1
				)
			;
			
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION'), 'nodegoat_to_ver', 'object_id',
				"JOIN ".$sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_ver.object_id)"
			)."
				AND date > '".$sql_date."'
			;
		";
		
		$res = DB::queryMulti($sql);
		
		StoreTypeObjects::touchTypeObjects($type_id, $sql_table_name);
	}
	
	public static function revertTypeObjectsDescriptionVersion($type_id, $object_description_id, $date, $do_deleted_only = false, $sql_table_name = false) { // In development. TODO: also check/use status, check/use user ID.
				
		$sql = '';
		$sql_date = DBFunctions::str2Date($date);
		
		if (!$sql_table_name) {
			
			$sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp_revert');
			
			DB::queryMulti("
				DROP TEMPORARY TABLE IF EXISTS ".$sql_table_name.";
				
				CREATE TEMPORARY TABLE ".$sql_table_name." (
					PRIMARY KEY (id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)." AS (
					SELECT id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						WHERE nodegoat_to.type_id = ".(int)$type_id."
							AND EXISTS (
								SELECT TRUE
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver
								WHERE nodegoat_to_def_ver.object_description_id = ".(int)$object_description_id."
									AND nodegoat_to_def_ver.object_id = nodegoat_to.id
									AND nodegoat_to_def_ver.date > '".$sql_date."'
									".($do_deleted_only ? "AND nodegoat_to_def_ver.version = 0" : "")."
							)
						GROUP BY nodegoat_to.id
				);
			");
		}
									
		foreach (StoreType::getValueTypeTables() as $table) {
		
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table, 'nodegoat_to_def', 'object_id',
					"JOIN ".$sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_def.object_id)",
					['active' => 'FALSE']
				)."
					AND nodegoat_to_def.object_description_id = ".(int)$object_description_id."
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table, 'nodegoat_to_def', 'object_id',
					"JOIN ".$sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_def.object_id)",
					['active' => 'TRUE']
				)."
					AND nodegoat_to_def.object_description_id = ".(int)$object_description_id."
					AND nodegoat_to_def.version = (SELECT
						CASE
							WHEN version = -1 THEN 1
							ELSE version
						END AS version
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver
						WHERE nodegoat_to_def_ver.object_description_id = nodegoat_to_def.object_description_id AND nodegoat_to_def_ver.object_id = nodegoat_to_def.object_id AND nodegoat_to_def_ver.date <= '".$sql_date."'
						ORDER BY nodegoat_to_def_ver.date DESC, nodegoat_to_def_ver.version DESC
						LIMIT 1
					)
				;
			";
		}
		
		$sql .= "
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION'), 'nodegoat_to_def_ver', 'object_id',
				"JOIN ".$sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_def_ver.object_id)"
			)."
				AND object_description_id = ".(int)$object_description_id."
				AND date > '".$sql_date."'
			;
		";
		
		$res = DB::queryMulti($sql);
		
		StoreTypeObjects::touchTypeObjects($type_id, $sql_table_name);
	}
	
	public static function cleanupTypesObjects($table_name = false) {
		
		if (!$table_name) {
			
			$table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp_del');
			
			DB::queryMulti("
				DROP TEMPORARY TABLE IF EXISTS ".$table_name.";
				
				CREATE TEMPORARY TABLE ".$table_name." (
					PRIMARY KEY (id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)." AS (
					SELECT id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						WHERE NOT EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_check
							WHERE nodegoat_to_check.id = nodegoat_to.id
								AND (nodegoat_to_check.active = TRUE OR nodegoat_to_check.status > 0)
						)
						GROUP BY id
				);
			");
		}
		
		DB::startTransaction('cleanup_types_objects');
		
		DB::query("
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				"JOIN ".$table_name." nodegoat_to_del ON (nodegoat_to_del.id = nodegoat_to.id)"
			)."
		");
		
		DB::commitTransaction('cleanup_types_objects');
	}
}
