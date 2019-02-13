<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class register_by_user extends register_by {

	public static function moduleProperties() {
		parent::moduleProperties();
		static::$label = getLabel('ttl_register_users');
	}
	
	protected $main_table = false;
	protected $columns = [];

	protected function contentsForm() {
				
		$arr_projects = cms_nodegoat_custom_projects::getProjects();
		$return .= '<fieldset><legend>'.getLabel('lbl_projects').'</legend><ul>
			<li>'.Labels::parseTextVariables(cms_general::createSelectorList(arrParseRecursive(arrValuesRecursive('project', $arr_projects), 'htmlspecialchars'), 'projects', ($this->arr_user[DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')] ? array_keys($this->arr_user[DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')]) : []))).'</li>
		</ul></fieldset>';
			
		$return .= '<fieldset><legend>'.getLabel('lbl_clearance').'</legend><ul>
			<li><label>'.getLabel('lbl_clearance_level').'</label><select name="clearance">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $this->arr_user[DB::getTableName('TABLE_USER_DETAILS')]['clearance'], false, 'label').'</select></li>
		</ul></fieldset>';

		return $return;
	}
	
	public static function js() {
	
		$return = parent::js();
	
		$return .= "";
		
		return $return;
	}
	
	protected function doubleCheckValidUserId($user_id = false) {
				
		if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
			error(getLabel('msg_not_allowed'));
			return;
		}
	}
	
	protected function processForm() {

		$user_data = [
			DB::getTableName('TABLE_USER_DETAILS').'.clearance' => $_POST['clearance'],
			DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS').'.project_id' => ($_POST['projects'] ? array_intersect($_POST['projects'], array_keys(cms_nodegoat_custom_projects::getProjects())) : [])
		];
				
		return $user_data;
	}
}
