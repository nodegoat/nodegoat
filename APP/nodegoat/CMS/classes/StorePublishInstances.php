<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StorePublishInstances {

	
	const FILENAME_PARSE_ARCHIVE = '/(\d+)-(\d+)(\.zip)?/';
	
	protected static $arr_instances_storage = [];
	
	public function __construct() {
		
	}

	public function storeProjects($arr) {
		
		if (!is_array($arr)) {
			error(getLabel('msg_missing_information'));
		}
		
		$arr_sql_keys = [];

		if ($arr['projects']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['projects'] as $project_id) {
				
				$arr_sql_insert[] = "(".(int)$project_id.")";
				$arr_sql_keys['projects'][] = (int)$project_id;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLISH_CUSTOM_PROJECTS')."
				("."project_id)
					VALUES
				".implode(",", $arr_sql_insert)."
				".DBFunctions::onConflict('project_id', false)."
			");

			$i = 0;
			
			foreach ($arr['projects'] as $project_id) {
				
				$project_id = (int)$project_id;
				$arr_definition = $arr['projects_organise'][$project_id];
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLISH_CUSTOM_PROJECTS')." SET
						is_default = ".((int)$arr['default_project'] == $project_id ? 1 : 0).",
						description = '".DBFunctions::strEscape($arr_definition['description'])."',
						public_interface_id = ".(int)$arr_definition['public_interface_id']."
					WHERE project_id = ".(int)$project_id."
				");
			}
		}
		
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_PUBLISH_CUSTOM_PROJECTS')."
			WHERE TRUE"."
				".($arr_sql_keys['projects'] ? "AND project_id NOT IN (".implode(",", $arr_sql_keys['projects']).")" : "")."
		");
		
		self::$arr_instances_storage = [];
	}
	
	public function updateProjectDate($arr_instance_project, $str_date = false) {
		
		$project_id = $arr_instance_project['project_id'];
		$str_date = DBFunctions::str2Date(($str_date ?: time()));
		
		$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLISH_CUSTOM_PROJECTS')." SET
				date = '".$str_date."'
			WHERE project_id = ".(int)$project_id."
		");
		
		self::$arr_instances_storage = [];
	}
	
	public function publishProject($arr_instance_project) {
		
		$num_date = time();
		$arr_instance_project['date'] = $num_date;
		
		$publish = new PublishInstanceProject($arr_instance_project);
		$publish->generate(true);
		$str_path_temporary = $publish->store();
		
		$str_path = $this->getProjectPaths($arr_instance_project, $num_date);
		
		FileStore::makeDirectoryTree(dirname($str_path));
		FileStore::renameFile($str_path_temporary, $str_path);
		
		$this->updateProjectDate($arr_instance_project, $num_date);
	}

	public function getProjectPaths($arr_instance_project, $str_date = false) {
		
		$archive = static::getProjectArchives($arr_instance_project, $str_date);
		
		if (is_array($archive)) {
			
			foreach ($archive as &$str_filename) {
				
				$str_filename = DIR_HOME_PUBLISH_CUSTOM_PROJECT.$str_filename;
			}
			
			return $archive;
		}
		
		return DIR_HOME_PUBLISH_CUSTOM_PROJECT.$archive;
	}
	
	public function delProjectPaths($arr_instance_project, $str_date = false) {
		
		$str_path_archive = false;
		if ($str_date) {
			$str_path_archive = $this->getProjectPaths($arr_instance_project, $str_date);
		}
		
		$arr = $this->getProjectPaths($arr_instance_project);
		
		foreach ($arr as $str_path) {
			
			if ($str_path_archive && $str_path_archive != $str_path) {
				continue;
			}
			
			FileStore::deleteFile($str_path);
		}
	}
	
	public function getProject1100CC($arr_instance_project, $arr_settings = [], $do_live = false) {
		
		if ($do_live) {
			
			$publish = new PublishInstanceProject($arr_instance_project);
			$publish->generate();
		
			$arr = $publish->getDocument1100CC($arr_settings);
			
			return $arr;
		}
		
		$str_path = $this->getProjectPaths($arr_instance_project, $arr_instance_project['date']);
		
		if (!isPath($str_path)) {
			error(getLabel('msg_not_found'));
		}
	
		$publish = new PublishInstanceProject($arr_instance_project);
		$publish->setTarget($str_path, true);
		
		$arr = $publish->readDocument1100CC($arr_settings);
		
		return $arr;
	}
	
	public function readProject($arr_instance_project, $str_filename = true) {

		$str_path = $this->getProjectPaths($arr_instance_project, $arr_instance_project['date']);
		
		if (!isPath($str_path)) {
			error(getLabel('msg_not_found'));
		}
		
		FileStore::readFile($str_path, $str_filename);
	}
	
	public function readProjectFile($arr_instance_project, $str_path_internal, $str_filename = true) {
		
		$str_path = $this->getProjectPaths($arr_instance_project, $arr_instance_project['date']);
		
		if (!isPath($str_path)) {
			error(getLabel('msg_not_found'));
		}
	
		$publish = new PublishInstanceProject($arr_instance_project);
		$publish->setTarget($str_path, true);
				
		if ($str_filename === true) {
			$str_filename = basename($str_path_internal);
		}
		if (is_string($str_filename)) {
			$str_filename .= '.zip';
		}
		
		$file = $publish->readFileAsArchive($str_path_internal, false);

		FileStore::readFile($file, $str_filename);
	}
	
	public static function getProjectArchives($arr_instance_project, $str_date = false) {
				
		if (!$arr_instance_project) {
			return false;
		}
		
		if ($str_date) {

			$str_filename = $arr_instance_project['project_id'].'-'.date('YmdHis', (!is_integer($str_date) ? strtotime($str_date) : $str_date)).'.zip';
			
			return $str_filename;
		} else {
			
			$arr = [];
			
			$str_path = DIR_HOME_PUBLISH_CUSTOM_PROJECT;
			
			if (!isPath($str_path)) {
				return $arr;
			}
			
			$iterator_files = new DirectoryIterator($str_path);
						
			foreach ($iterator_files as $file) {
					
				if (!$file->isFile()) {
					continue;
				}
				
				$str_filename = $file->getFilename();
				
				if (!strStartsWith($str_filename, $arr_instance_project['project_id'].'-')) {
					continue;
				}
				
				$arr[] = $str_filename;
			}
			
			return $arr;
		}
	}

	public static function getInstanceProjects() {
		
		$str_identifier = 'publish';			

		if (isset(self::$arr_instances_storage[$str_identifier])) {
			return self::$arr_instances_storage[$str_identifier];
		}
		
		$arr = [];
		
		$res = DB::query("
			SELECT pp.*
					FROM ".DB::getTable('DEF_NODEGOAT_PUBLISH_CUSTOM_PROJECTS')." pp
				"."
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr['projects'][$arr_row['project_id']] = $arr_row;
		}
		
		self::$arr_instances_storage[$str_identifier] = $arr;

		return $arr;
	}
	
	public static function getInstanceProjectsArchives() {
		
		$arr_instance = static::getInstanceProjects();
		
		$arr = [];
		
		if (!$arr_instance) {
			return $arr;
		}
		
		foreach ($arr_instance['projects'] as $arr_instance_project) {
			
			$arr_files = static::getProjectArchives($arr_instance_project);
			
			if (!$arr_files) {
				continue;
			}
			
			foreach ($arr_files as $str_filename) {
				$arr[] = $str_filename;
			}
		}

		return $arr;
	}
	
}
