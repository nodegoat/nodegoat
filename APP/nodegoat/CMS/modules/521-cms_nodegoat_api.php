<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_APIS', DB::$database_home.'.def_nodegoat_apis');
DB::setTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS', DB::$database_home.'.def_nodegoat_api_custom_projects');

class cms_nodegoat_api extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
		
	public static function getConfiguration($api_id) {
		
		$str_identifier = $api_id;			
		
		$cache = self::getCache($str_identifier);
		if ($cache) {
			return $cache;
		}
		
		$arr = [];
		
		$res = DB::query("
			SELECT a.*,
				ap.project_id, ap.is_default, ap.require_authentication, ap.identifier_url
					FROM ".DB::getTable('DEF_NODEGOAT_APIS')." a
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')." ap ON (ap.api_id = a.api_id".")
				WHERE a.api_id = ".(int)$api_id."
				"."
		");
		
		while ($row = $res->fetchAssoc()) {
			
			if (!$arr) {
				$arr = ['api' => $row, 'projects' => []];
			}
			
			if ($row['project_id']) {
				$arr['projects'][$row['project_id']] = $row;
			}
		}
		
		self::setCache($str_identifier, $arr);

		return $arr;
	}
	
	public static function handleAPIConfiguration($api_id, $arr) {
		
		if (!$api_id) {
			error(getLabel('msg_missing_information'));
		}
		
		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_APIS')."
			(api_id".")
				VALUES
			(
				".(int)$api_id."
			)
			".DBFunctions::onConflict('api_id', ['api_id'])."
		");
		
		$arr_sql_keys = [];

		if ($arr['projects']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['projects'] as $project_id) {
				
				$arr_sql_insert[] = "(".(int)$api_id.", ".(int)$project_id.")";
				$arr_sql_keys['projects'][] = (int)$project_id;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')."
				(api_id".", project_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('api_id'.', project_id', ['api_id'])."
			");

			$i = 0;
			
			foreach ($arr['projects'] as $project_id) {
				
				$project_id = (int)$project_id;
				$arr_definition = $arr['projects_organise']['project_id-'.$project_id];
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')." SET
						is_default = ".((int)$arr['default_project'] == $project_id ? 1 : 0).",
						require_authentication = ".(int)$arr_definition['require_authentication'].",
						identifier_url = '".DBFunctions::strEscape($arr_definition['identifier_url'])."'
					WHERE api_id = ".(int)$api_id." AND project_id = ".(int)$project_id."
				");
			}
			
			$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_API_CUSTOM_PROJECTS')."
				WHERE api_id = ".(int)$api_id."
					".($arr_sql_keys['projects'] ? "AND project_id NOT IN (".implode(",", $arr_sql_keys['projects']).")" : "")."
			");
		}
	}
}
