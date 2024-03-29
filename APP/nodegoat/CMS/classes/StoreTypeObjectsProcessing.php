<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreTypeObjectsProcessing extends StoreTypeObjects {

	public static function cacheTypesObjectSubs($date_after = false, $date_to = false) {
		
		$table_name_tos_cache_temporary = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_tos_cache_temp');
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
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to.id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object_sub', 'nodegoat_tos').")
				WHERE ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object', 'nodegoat_to')."
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
		
		$func_collect_new = function($sql_table_name, $arr_source) use ($table_name_tos_cache_temporary, $count) {
				
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
							FROM ".$table_name_tos_cache_temporary."
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
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_date_chrono.object_sub_id AND nodegoat_tos.date_version = nodegoat_tos_date_chrono.version AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object_sub', 'nodegoat_tos').")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2 ON (nodegoat_to2.id = nodegoat_tos.object_id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object', 'nodegoat_to2').")
						WHERE nodegoat_to.type_id = ".StoreType::getSystemTypeID('cycle')." AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object', 'nodegoat_to')."
					)
					".DBFunctions::onConflict('object_sub_id', false)."
				;
			";
		} else {
			$sql_collect_related = '';
		}
		
		$arr_source = GenerateTypeObjects::format2SQLObjectSubDate($table_name_tos, StoreType::DATE_START_START, $count, false, $table_name_tos_details);
		
		$arr_sql_paths = $func_collect_new(DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE_PATH'), $arr_source);
		
		$func_update_date = function($sql_field_target, $arr_source, $is_null = true) use ($table_name_tos_cache_temporary, $table_name_tos_changed_all, $arr_sql_paths, $table_name_tos, $table_name_tos_details, $date_after, $date_to) {
			
			return "
				-- Create temporary table for new ".$sql_field_target." values.
				
				CREATE TEMPORARY TABLE ".$table_name_tos_cache_temporary." (
					object_sub_id INT,
					date_value BIGINT NULL,
					".implode(',', $arr_sql_paths['columns']).",
					active BOOLEAN,
					status SMALLINT,
						PRIMARY KEY (object_sub_id, active, status)
				) ".DBFunctions::sqlTableOptions(($date_after ? DBFunctions::TABLE_OPTION_MEMORY : false)).";
				
				-- Select and store all new ".$sql_field_target." values.
				
				INSERT INTO ".$table_name_tos_cache_temporary."
					(
						SELECT
							".$table_name_tos.".id AS object_sub_id,
							".$arr_source['column_date']." AS date_value,
							".implode(',', $arr_sql_paths['values']).",
							".$table_name_tos.".active,
							".$table_name_tos.".status
								FROM ".$table_name_tos_changed_all."
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_tos." ON (".$table_name_tos.".id = ".$table_name_tos_changed_all.".object_sub_id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object_sub', $table_name_tos).")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = ".$table_name_tos.".object_id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object', 'nodegoat_to').")
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
								FROM ".$table_name_tos_cache_temporary."
					)
					".DBFunctions::onConflict('object_sub_id, active, status', [$sql_field_target], "state = 0")."
				;
				
				-- Process paths! (".$sql_field_target.")
				
				".implode(" ", $arr_sql_paths['insert'])."
				
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_tos_cache_temporary.";
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
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.location_ref_object_id = nodegoat_to_status.object_id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object_sub', 'nodegoat_tos')."
								AND NOT EXISTS (SELECT TRUE
										FROM ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_PATH')." nodegoat_tos_cache_path
									WHERE nodegoat_tos_cache_path.path_object_sub_id = nodegoat_tos.id AND nodegoat_tos_cache_path.state = 0
								)
							)
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object', 'nodegoat_to').")
						WHERE nodegoat_to_status.date > '".DBFunctions::str2Date($date_after)."' AND nodegoat_to_status.date <= '".DBFunctions::str2Date($date_to)."'
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
			
			CREATE TEMPORARY TABLE ".$table_name_tos_cache_temporary." (
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
			
			INSERT INTO ".$table_name_tos_cache_temporary."
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
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_tos." ON (".$table_name_tos.".id = ".$table_name_tos_changed_all.".object_sub_id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object_sub', $table_name_tos).")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = ".$table_name_tos.".object_id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ANY, 'object', 'nodegoat_to').")
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
							FROM ".$table_name_tos_cache_temporary."
				)
				".DBFunctions::onConflict('object_sub_id, geometry_object_sub_id, active, status', ['ref_object_id', 'ref_type_id', 'ref_object_sub_details_id'], "state = 0")."
			;
			
			-- Process paths!
					
			".implode(" ", $arr_sql_paths['insert'])."
			
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_tos_cache_temporary.";
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
	
	public static function revertTypeObjectsVersion($type_id, $date, $do_deleted_only = false, $str_sql_table_name = false) { // In development. TODO: also check/use status, check/use user ID.
		
		$str_sql = '';
		$str_sql_date = DBFunctions::str2Date($date);
		
		if (!$str_sql_table_name) {
			
			$str_sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp_revert');
			
			DB::queryMulti("
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name.";
				
				CREATE TEMPORARY TABLE ".$str_sql_table_name." (
					PRIMARY KEY (id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)." AS (
					SELECT id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						WHERE nodegoat_to.type_id = ".(int)$type_id."
							AND EXISTS (
								SELECT TRUE
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
								WHERE nodegoat_to_ver.object_id = nodegoat_to.id
									AND nodegoat_to_ver.date > '".$str_sql_date."'
									".($do_deleted_only ? "AND nodegoat_to_ver.version = 0" : "")."
							)
						GROUP BY nodegoat_to.id
				);
			");
		}
		
		$str_sql .= "
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				"JOIN ".$str_sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to.id)",
				['active' => 'FALSE']
			)."
			;
			
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				"JOIN ".$str_sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to.id)",
				['active' => 'TRUE']
			)."
				AND nodegoat_to.version = (SELECT
					CASE
						WHEN version = -1 THEN 1
						ELSE version
					END AS version
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
					WHERE nodegoat_to_ver.object_id = nodegoat_to.id AND nodegoat_to_ver.date <= '".$str_sql_date."'
					ORDER BY nodegoat_to_ver.date DESC, nodegoat_to_ver.version DESC
					LIMIT 1
				)
			;
			
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION'), 'nodegoat_to_ver', 'object_id',
				"JOIN ".$str_sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_ver.object_id)"
			)."
				AND date > '".$str_sql_date."'
			;
		";
		
		$res = DB::queryMulti($str_sql);
		
		static::touchTypeObjects($type_id, $str_sql_table_name);
	}
	
	public static function revertTypeObjectsDescriptionVersion($type_id, $object_description_id, $date, $do_deleted_only = false, $str_sql_table_name = false) { // In development. TODO: also check/use status, check/use user ID.
				
		$str_sql = '';
		$str_sql_date = DBFunctions::str2Date($date);
		
		if (!$str_sql_table_name) {
			
			$str_sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp_revert');
			
			DB::queryMulti("
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name.";
				
				CREATE TEMPORARY TABLE ".$str_sql_table_name." (
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
									AND nodegoat_to_def_ver.date > '".$str_sql_date."'
									".($do_deleted_only ? "AND nodegoat_to_def_ver.version = 0" : "")."
							)
						GROUP BY nodegoat_to.id
				);
			");
		}
									
		foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
		
			$str_sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_to_def', 'object_id',
					"JOIN ".$str_sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_def.object_id)",
					['active' => 'FALSE']
				)."
					AND nodegoat_to_def.object_description_id = ".(int)$object_description_id."
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_to_def', 'object_id',
					"JOIN ".$str_sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_def.object_id)",
					['active' => 'TRUE']
				)."
					AND nodegoat_to_def.object_description_id = ".(int)$object_description_id."
					AND nodegoat_to_def.version = (SELECT
						CASE
							WHEN version = -1 THEN 1
							ELSE version
						END AS version
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver
						WHERE nodegoat_to_def_ver.object_description_id = nodegoat_to_def.object_description_id AND nodegoat_to_def_ver.object_id = nodegoat_to_def.object_id AND nodegoat_to_def_ver.date <= '".$str_sql_date."'
						ORDER BY nodegoat_to_def_ver.date DESC, nodegoat_to_def_ver.version DESC
						LIMIT 1
					)
				;
			";
		}
		
		$str_sql .= "
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION'), 'nodegoat_to_def_ver', 'object_id',
				"JOIN ".$str_sql_table_name." nodegoat_to_revert ON (nodegoat_to_revert.id = nodegoat_to_def_ver.object_id)"
			)."
				AND object_description_id = ".(int)$object_description_id."
				AND date > '".$str_sql_date."'
			;
		";
		
		$res = DB::queryMulti($str_sql);
		
		static::touchTypeObjects($type_id, $str_sql_table_name);
	}
	
	public static function cleanupTypesObjects($str_sql_table_name = false) {
		
		if (!$str_sql_table_name) {
			
			$str_sql_table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp_del');
			
			DB::queryMulti("
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name.";
				
				CREATE TEMPORARY TABLE ".$str_sql_table_name." (
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
				"JOIN ".$str_sql_table_name." nodegoat_to_del ON (nodegoat_to_del.id = nodegoat_to.id)"
			)."
		");
		
		DB::commitTransaction('cleanup_types_objects');
	}
}
