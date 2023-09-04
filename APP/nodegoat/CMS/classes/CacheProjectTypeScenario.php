<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CacheProjectTypeScenario {
	
	
	protected $project_id = false;
	protected $scenario_id = false;
	
	protected $arr_scenario = [];
	protected $arr_scenario_cache = [];
	protected $path_scenario = false;
	
	protected $str_hash_filter = '';
	protected $str_hash_visualise = '';
	
	const HASH_SEPERATOR = ':';
	
	public function __construct($project_id, $scenario_id) {
		
		
		$this->project_id = $project_id;
		$this->scenario_id = $scenario_id;
		
		$arr_project = StoreCustomProject::getProjects($project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$this->arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id, false, false, $scenario_id, $arr_use_project_ids);
		$this->arr_scenario_cache = StoreCustomProject::getTypeScenarioHash($this->arr_scenario['project_id'], $scenario_id, $project_id);
		
		$str_hash = ($this->arr_scenario_cache['hash'] ?: static::HASH_SEPERATOR);
		$arr_hash = str2Array($str_hash, static::HASH_SEPERATOR);
		
		$this->str_hash_filter = $arr_hash[0];
		$this->str_hash_visualise = $arr_hash[1];
	}

	public function checkCacheFilter($str_hash, $do_lock = true) { // Hash should start with the filter part

		$this->path_scenario = $this->getPath('data');
		$is_path = isPath($this->path_scenario);
		$store_scenario = false;
		
		if ($str_hash != $this->str_hash_filter || !$is_path || (!$this->arr_scenario['cache_retain'] && $this->arr_scenario_cache['is_expired'])) { // Scenario should be updated
			
			$this->str_hash_filter = $str_hash;
			
			if (!$do_lock) {
				return false;
			}
								
			$code = $str_hash.'_'.(!$this->arr_scenario['cache_retain'] ? $this->arr_scenario_cache['hash_date'] : '0');
			
			$store_scenario = Mediator::setLock($this->path_scenario, $code);
			
			if (!$store_scenario) {
				$is_path = true; // Should now be a path
			}
		}
		
		if (!$store_scenario && $is_path) {

			return true; // Has cache
		}
		
		return false; // Update cache
	}
	
	public function checkCacheVisualise($str_hash, $do_lock = true) {
		
		$this->path_scenario = $this->getPath('visualise');
		$is_path = isPath($this->path_scenario);
		$store_scenario = false;
		
		if ($str_hash != $this->str_hash_visualise || !$is_path || (!$this->arr_scenario['cache_retain'] && $this->arr_scenario_cache['is_expired'])) { // Scenario should be updated
			
			$this->str_hash_visualise = $str_hash;
			
			if (!$do_lock) {
				return false;
			}
			
			$code = $str_hash.'_'.(!$this->arr_scenario['cache_retain'] ? $this->arr_scenario_cache['hash_date'] : '0');
			
			$store_scenario = Mediator::setLock($this->path_scenario, $code);
			
			if (!$store_scenario) {
				$is_path = true; // Should now be a path
			}
		}
		
		if (!$store_scenario && $is_path) {

			return true; // Has cache
		}
		
		return false; // Update cache
	}
	
	public function updateCache($arr_data) {
		
		if (is_array($arr_data)) {
			$str = value2JSON($arr_data);
		} else {
			$str = $arr_data;
		}
		
		$str_hash = $this->str_hash_filter.static::HASH_SEPERATOR.$this->str_hash_visualise;
		
		FileStore::storeFile($this->path_scenario.'_temp', $str, $this->path_scenario);
		
		StoreCustomProject::updateTypeScenarioHash($this->arr_scenario['project_id'], $this->arr_scenario['id'], $str_hash, $this->arr_scenario_cache['hash_date'], $this->project_id);
		
		Mediator::removeLock($this->path_scenario);
	}
	
	public function getCache() {
		
		if (!$this->path_scenario || !isPath($this->path_scenario)) {
			return false;
		}
			
		$arr_cache = file_get_contents($this->path_scenario);
		$arr_cache = JSON2Value($arr_cache);
			
		return $arr_cache;
	}
	
	protected function getPath($type) {
				
		return DIR_CACHE_SCENARIOS.$type.'_'.$this->project_id.'_'.$this->scenario_id;
	}
	
	public static function generateHashVisualise($project_id, $scenario_id, $arr_package) {
		
		return value2Hash($arr_package);
	}
	
	public static function generateHashFilter($project_id, $scenario_id, $arr_filters) {
		
		$arr_project = StoreCustomProject::getProjects($project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id, false, false, $scenario_id, $arr_use_project_ids);
		$type_id = $arr_scenario['type_id'];
		
		if ($arr_project['types'][$type_id]['type_filter_id']) {
							
			$arr_project_type_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
			$arr_project_type_filters = FilterTypeObjects::convertFilterInput($arr_project_type_filters['object']);
		} else {
			
			$arr_project_type_filters = [];
		}
		
		return value2Hash($arr_filters + $arr_project_type_filters);
	}
}
