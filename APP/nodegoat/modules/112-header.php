<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class header extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('ttl_header');
		static::$parent_label = getLabel('ttl_site');
	}
	
	function __construct() {
		
		if ($_SESSION['USER_ID']) {
			
			$this->arr_access = [
				'general' => [],
				'search_box' => [],
				'data_visualise' => [],
				'data_analysis' => [],
				'toolbar' => [],
				'data_view' => [],
				'data_entry' => [],
				'data_filter' => [],
				'data_model' => [],
				'custom_projects' => []
			];
		}
	}
	
	public static function modulePreload() {
		
		$_SESSION['NODEGOAT_CLEARANCE'] = (int)$_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_DETAILS')]['clearance'];
		
		if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
			Settings::override('messaging_allow_message_all', false);
		}

		Settings::set('domain_administrator_mode', ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN && Settings::get('domain_administrator_mode')));

		SiteEndVars::addTitle('nodegoat');
	}
	
	public function contents() {

		if (Settings::get('labeler_home')) {
			
			SiteEndVars::addScript("
				$(document).on('documentloaded ajaxloaded', function(e) {

					if (!getElement(e.detail.elm)) {
						return;
					}
					
					var elms_input = $('.data_model form textarea[name], .data_model form input[type=text][name], .custom_projects form input[type=text][name], form .scenario textarea[name], form .scenario input[type=text][name], form .condition input[type=text][name$=\"[condition_label]\"], .public_interfaces form textarea[name], .public_interfaces form input[type=text][name]').not('.editor');
					
					elms_input.each(function() {
						new LabelOption(this, {action: 'y:general:label_popup-0', tag: 'L'});
					});
				}).on('editorloaded', '.public_interfaces form .editor', function(e) {
					new LabelOption(e.detail.source, {action: 'y:general:label_popup-0', tag: 'L'});
				});
			");
		}
		
		$navigation = new navigation;
		$navigation = $navigation->contents();
		$search_box = new search_box;
		$search_box = $search_box->contents();
		$logout = new logout;
		$logout = $logout->contents();
		$toolbar = new toolbar;
		$toolbar = $toolbar->contents();

		$return .= '<a href="'.(SiteStartVars::$login_dir ? SiteStartVars::$login_dir['path'].'/' : '/').'" alt="'.getLabel('name', 'D').'"></a>';
		$return .= '<span></span><span></span>'; // Helpers
		$return .= '<div class="navigation">'.$navigation.'</div>'.($logout ? '<div class="logout">'.$logout.'</div>' : '').($toolbar ? '<div class="toolbar">'.$toolbar.'</div>' : '');
		
		return $return;
	}
	
	public static function css() {
	
		$return = '.header { position: relative; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {

	}	
}
