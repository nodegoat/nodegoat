<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class publish extends base_module {

	public static function moduleProperties() {
		static::$label = 'Publish';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [];
		
	public function contents() {
				
		SiteEndEnvironment::setModuleVariables($this->mod_id);
		
		$project_id = false;
		$str_date = false;
		
		if ($this->arr_query[0]) {
			
			$project_id = (int)$this->arr_query[0];
			$str_date = (!empty($this->arr_query[1]) && is_numeric($this->arr_query[1]) ? $this->arr_query[1] : false);
		}
		
		$arr_instance_projects = StorePublishInstances::getInstanceProjects();
		
		if ($project_id) {
			
			$arr_instance_project = $arr_instance_projects['projects'][$project_id];
		} else {
			
			foreach ($arr_instance_projects as $arr_check) {
				
				if (!$arr_instance_project['is_default']) {
					continue;
				}
				
				$arr_instance_project = $arr_check;
				break;
			}
		}
		
		if (!$arr_instance_project) {
			return '';
		}
				
		SiteEndEnvironment::setModuleVariables($this->mod_id, [$arr_instance_project['project_id']]);
		
		if ($str_date) { // Set specific requested archive date
			
			$arr_instance_project['date'] = $str_date;
			SiteEndEnvironment::setModuleVariables($this->mod_id, [$str_date], false);
		}
		$str_date = date('YmdHis', (!is_integer($arr_instance_project['date']) ? strtotime($arr_instance_project['date']) : $arr_instance_project['date']));

		$publish = new StorePublishInstances();
		
		if (isset($this->arr_query['download'])) {

			try {
			
				if ($this->arr_query['download'][0] == 'archive') {
					
					$publish->readProject($arr_instance_project, 'publication-'.$arr_instance_project['project_id'].'-'.$str_date.'.zip');
				} else {
					
					$str_path_internal = str2Label(arr2String($this->arr_query['download'], '/'), '/.');
					$str_path_internal = str_replace('..', '', $str_path_internal);
					
					$publish->readProjectFile($arr_instance_project, $str_path_internal);
				}
				
				exit;
			} catch (Exception $e) {

				error(getLabel('msg_not_found'), TROUBLE_ERROR, LOG_CLIENT, false, $e); // Make notice
			}
		}
		
		if ($this->arr_mod['shortcut']) {
			SiteEndEnvironment::setShortcut($this->mod_id, $this->arr_mod['shortcut'], $this->arr_mod['shortcut_root']);
		}
		
		$arr_settings = ['url_file' => SiteStartEnvironment::getModuleURL($this->mod_id).$project_id.'/'.$str_date.'/download.v/'];
		
		$arr = $publish->getProject1100CC($arr_instance_project, $arr_settings);
		
		$str_title = $arr['title'].' ('.date('d-m-Y', strtotime($arr['date'])).')';
		
		SiteEndEnvironment::addTitle($str_title);
		SiteEndEnvironment::addStyle($arr['style']);

		$str_html = $arr['body'];
		
		return $str_html;
	}
	
	public static function css() {
			
		$return = '';
	
		return $return;
	}
	
	public static function js() {

		$return = "";
		
		return $return;
	}
	
	public function commands($method, $id, $value = '') {
	
	}
	
	public static function findMainPublish() {
		
		return pages::getClosestModule('publish', SiteStartEnvironment::getDirectory('id'), SiteStartEnvironment::getPage('id'), 0, false, false);
	}
}
