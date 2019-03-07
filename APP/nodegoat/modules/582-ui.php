<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ui extends base_module {

	public static function moduleProperties() {
		static::$label = 'Public User Interface';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'data_filter' => [],
		'data_view' => [],
		'data_visualise' => [
			'*' => false,
			'visualise' => true,
			'visualise_soc' => true,
			'visualise_time' => true,
			'review_data' => true
		],
		'ui_data' => [],
		'ui_selection' => []
	];
	
	public function contents() {
	
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		$do_print = (($arr_public_user_interface_module_vars['set'] == 'object-print' || $arr_public_user_interface_module_vars['set'] == 'selection-print') && (SiteStartVars::getFeedback('active_type_object_id') || SiteStartVars::getFeedback('active_selection_id')));
		
		if ($arr_public_user_interface['interface']['settings']['default_language']) {
			
			$arr_language_hosts = cms_language::getLanguageHosts();
			
			if (!$arr_language_hosts[SERVER_NAME_SITE_NAME]) {
				$_SESSION['LANGUAGE_SYSTEM'] = SiteStartVars::$language = $arr_public_user_interface['interface']['settings']['default_language'];
			}
		}
		
		if (!$arr_public_user_interface['interface']['settings']['disable_responsive_layout']) {
			
			if ($arr_public_user_interface_module_vars['start'] == 'fullscreen') {
				
				$this->style = 'fullscreen responsive-layout-enabled';
				
			} else {
				
				$this->style = 'responsive-layout-enabled';
			}
			
		} else if ($arr_public_user_interface_module_vars['start'] == 'fullscreen') {
			
			$this->style = 'fullscreen';
		}

		if ($do_print) {
			
			$this->style = 'print';
			
		}
			
		$return = '<div class="header-info" data-public_user_interface_id="'.$public_user_interface_id.'">
						<h1 class="a" id="y:ui:set_project-0">'.Labels::parseTextVariables($arr_public_user_interface['interface']['name']).'</h1>'.
						(!empty($arr_public_user_interface['texts']) ? '<span class="icon a quick" data-category="full" id="y:ui:view_text-0">'.getIcon('info-point').'</span>' : '').'
					</div>
					<div class="fixed-view-container"></div>
					<label for="nav-toggle">☰</label>
					<input id="nav-toggle" type="checkbox" />
					<nav>
						<ul>
							<li class="projects-nav">'.$this->getProjectsNavigationElm().'</li>
							<li class="project-dynamic-nav">'.$this->getProjectDynamicNavigationElm().'</li>
						</ul>
					</nav>
					<div class="project-dynamic-data" '.($arr_public_user_interface['interface']['settings']['show_device_location'] ? 'data-device_location="1"' : '').'>'.$this->getProjectDynamicDataElm($do_print).'</div>
					'.ui_selection::createViewSelectionsContainer($do_print);		
		
		return $return;
	}
	
	private function getProjectDynamicNavigationElm() {
	
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true);
	
		$return = '<ul data-method="set_project" data-public_user_interface_active_custom_project_id="'.$public_user_interface_active_custom_project_id.'">';
								
		if (!$arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['no_quicksearch'] || count((array)$arr_public_interface_project_filter_types)) {
						
			$return .='<li class="project-filters" id="y:ui:filter-0">
					<div class="filters-container">
						'.$this->getProjectFiltersElm().'
						'.($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['info'] ? '<button id="y:ui:view_text-project_'.$public_user_interface_active_custom_project_id.'" class="a quick"><span class="icon">'.getIcon('help').'</span></button>' : '').'
					</div>
				</li>';
				
		} else if ($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['info']) {
			
			$return .= '<li class="project-help">
							<div id="y:ui:view_text-project_'.$public_user_interface_active_custom_project_id.'" class="a quick">
								'.($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['info_title'] ? Labels::parseTextVariables($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['info_title']) : getLabel('lbl_information')).'
							</div>
						</li>';
		}
			
					
		if ($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['show_type_buttons']) {
			$return .= '<li class="project-types">'.$this->getProjectTypesElm().'</li>';
		}
		
		$elm_scenarios = $this->getProjectScenariosElm();
		
		if ($elm_scenarios) {
			$return .= '<li class="project-scenarios">'.$elm_scenarios.'</li>';
		}
		
		$return .= '</ul>';
		
		return $return;
	}
	
	private function getProjectDynamicDataElm($do_print = false) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');		
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		$create_data = new ui_data();
		
		if (SiteStartVars::getFeedback('active_type_object_id')) {

			$arr_id = explode("-", SiteStartVars::getFeedback('active_type_object_id'));
			$type_id = $arr_id[0];
			$object_id = $arr_id[1];

			SiteEndVars::setFeedback('active_type_object_id', false, true);

			if ($do_print) {
				
				$print_heading = '<div>
								<p>'.SiteStartVars::getBasePath(0, false).SiteStartVars::$page['name'].'.p/'.$public_user_interface_id.'/'.$public_user_interface_active_custom_project_id.'/object/'.$type_id.'-'.$object_id.'</p>
								<h3>'.Labels::parseTextVariables($arr_public_user_interface['interface']['name']).'</h3>
							</div>';
			}
									
			$elm = $create_data->createViewTypeObject($type_id, $object_id, $do_print);
			
		} else if (SiteStartVars::getFeedback('active_selection_id') && $do_print) {
			
			if ($do_print) {
				
				$print_heading = '<div>
								<h3>'.Labels::parseTextVariables($arr_public_user_interface['interface']['name']).'</h3>
							</div>';
			}
								
			$elm = ui_selection::createViewPrintSelection(SiteStartVars::getFeedback('active_selection_id'));
		}

		if ($do_print && $elm) {
			
			$elm .= '<script>window.print();</script>';
		}
		
		$return_elm = $print_heading.$elm;
		
		$return = '<div class="tools" id="y:ui:handle_dynamic_project_data-0">'.(!$do_print ? $create_data->getDynamicProjectDataTools() : '').'</div>
					<div class="data">
						<div class="objects"></div>
						<div class="object '.($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['show_object_fullscreen'] ? 'full' : '').'" id="y:ui_data:show_project_type_object-0">'.$return_elm.'</div>
						<div class="thumbnail" id="y:ui_data:show_project_type_object_thumbnail-0"></div>
					</div>';
		
		return $return;
	}
	
	private function getProjectsNavigationElm() {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		
		if (!$public_user_interface_active_custom_project_id) {
			error(getLabel('msg_missing_information'));
		}
		
		$arr_projects = cms_nodegoat_custom_projects::getProjects();	

		
		if (count((array)$arr_public_user_interface['interface']['settings']['projects']) > 1) {
			
			$arr_project_type_object_amounts = self::getProjectsTypeObjectAmounts($arr_public_user_interface, $arr_projects);
			$arr_types = StoreType::getTypes();

			$return = '<ul>';
			
			foreach ((array)$arr_public_user_interface['interface']['settings']['projects'] as $nav_project_id => $arr) {
				
				$project_name = Labels::parseTextVariables(($arr_public_user_interface['interface']['settings']['projects'][$nav_project_id]['name'] ? $arr_public_user_interface['interface']['settings']['projects'][$nav_project_id]['name'] : $arr_projects[$nav_project_id]['project']['name']));
				$elm_amounts = false;
			

				/*if (count($arr_project_type_object_amounts[$nav_project_id]['types']) > 1) {
					
					$elm_amounts = '<ul>';
					
					foreach ($arr_project_type_object_amounts[$nav_project_id]['types'] as $explore_project_type_id => $arr_amount) {
						
						$elm_amounts .= '<li>'.nr2String($arr_amount['total_filtered']).' '.$arr_types[$explore_project_type_id]['name'].($arr_amount['total_filtered'] > 1 && mb_substr($arr_types[$explore_project_type_id]['name'], -1) != 's' ? 's' : '').'</li>';
					}
					$elm_amounts .= '</ul>';	
				}*/
				
				$return .= '<li '.($nav_project_id == $public_user_interface_active_custom_project_id ? 'class="active"' : '').' id="y:ui:set_project-'.$nav_project_id.'" '.($elm_amounts ? 'title="'.htmlspecialchars($elm_amounts).'"' : '').'>
								<span class="project-name">'.htmlspecialchars($project_name).'</span>
								'.($arr_project_type_object_amounts[$nav_project_id]['project_total'] ? '<span class="project-amount">'.nr2String($arr_project_type_object_amounts[$nav_project_id]['project_total']).'</span>' : '').'
							</li>';			
			}
			
			$return .= '</ul>';
			
		}
				
		return $return;
	}
	
	private function getProjectsTypeObjectAmounts($arr_public_interface, $arr_projects) {
		
		$arr_amounts = [];
		
		foreach ((array)$arr_public_interface['projects'] as $public_interface_project_id => $arr_public_interface_project) {
			
			$arr_amounts[$public_interface_project_id]['project_total'] = 0;
				
			foreach ((array)$arr_public_interface['project_types'][$public_interface_project_id] as $public_interface_project_type_id => $arr_public_interface_project_types) {
				
				if (!$arr_public_interface_project_types['type_is_filter']) {
					
					$filter = new FilterTypeObjects($public_interface_project_type_id, 'name');			

					if ($arr_projects[$public_interface_project_id]['types'][$public_interface_project_type_id]['type_filter_id']) {

						$arr_use_project_ids = array_keys($arr_projects[$public_interface_project_id]['use_projects']);
						$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($public_interface_project_id, false, false, $arr_projects[$public_interface_project_id]['types'][$public_interface_project_type_id]['type_filter_id'], true, $arr_use_project_ids);
						$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
						
					}
					
					$arr_amounts[$public_interface_project_id]['types'][$public_interface_project_type_id] = $filter->getResultInfo();
					
					$arr_amounts[$public_interface_project_id]['project_total'] += $arr_amounts[$public_interface_project_id]['types'][$public_interface_project_type_id]['total_filtered'];
				}
			}
		}

		return $arr_amounts;
		
	}
	
	private function getProjectFiltersElm() {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');	
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);
		
		$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');

		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true);

		$public_user_interface_active_custom_project_settings = $arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id];

		$arr_filters = [];
		
		
		if ($public_user_interface_active_custom_project_settings['use_filter']) {

			foreach ((array)$arr_public_interface_project_types as $type_id) {

				$arr_type_network_paths = self::getTraceTypeNetworkPaths($type_id, $arr_public_interface_project_filter_types);
				$arr_type_set = StoreType::getTypeSet($type_id);

				foreach ((array)$arr_public_interface_project_filter_types as $filter_type_id) {
					
					$arr_filter_type_set = StoreType::getTypeSet($filter_type_id);
					
					foreach ((array)$arr_type_network_paths[$filter_type_id]['connections'][$type_id]['out'][$filter_type_id]['object_descriptions'] as $object_description_id => $arr_object_description) {
										
						$filter_id = $type_id.'_OD_'.$object_description_id.'_'.$filter_type_id;
						$arr_filters[$filter_id] = ['type_id' => $type_id, 'filter_type_id' => $filter_type_id, 'filter_id' => $filter_id, 'placeholder_text' => Labels::parseTextVariables($arr_type_set['object_descriptions'][$object_description_id]['object_description_name']), 'url_filter' => ''];
						
					}
				}
			}
		
		} else {
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
			$project_name = Labels::parseTextVariables(($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['name'] ? $arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['name'] : $arr_project['project']['name']));

			$arr_filters[0] = ['type_id' => 0, 'filter_type_id' => 0, 'filter_id' => 0, 'placeholder_text' => ($public_user_interface_active_custom_project_settings['info_search'] ? Labels::parseTextVariables($public_user_interface_active_custom_project_settings['info_search']) : getLabel('lbl_enter_search_terms').' '.$project_name), 'url_filter' => ''];
		}

		// Parse and set URL filter
		if ($arr_public_user_interface_module_vars['set'] == 'filter' && $arr_public_user_interface_module_vars['id']) {

			$arr_filter_parts = explode('+', $arr_public_user_interface_module_vars['id']);

			foreach ((array)$arr_filter_parts as $filter_part) {
	
				$arr_filter_part = explode(':', $filter_part);
			
				if (count((array)$arr_filter_part) > 1) {
					
					$arr_filter_part_id = $arr_filter_part[0];
					$arr_filter_part_elements = explode('|', $arr_filter_part[1]);		

				} else {
					
					$arr_filter_part_id = 0;
					$arr_filter_part_elements = explode('|', $arr_filter_part[0]);
				}

				$url_filter = '';

				foreach ((array)$arr_filter_part_elements as $filter_element) {
					
					if (preg_match("/\d+-\d+/", $filter_element)) {
						
						$arr_type_object_id = explode('-', $filter_element);
						
						if (is_numeric($arr_type_object_id[0]) && is_numeric($arr_type_object_id[1])) {
							
							if ($arr_public_interface_project_filter_types[$arr_type_object_id[0]]) {
								
								$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($arr_type_object_id[0], $arr_type_object_id[1], 'include');
								
								$url_filter .= '<div class="keyword type-'.$arr_type_object_id[0].'" data-object_id="'.$arr_type_object_id[1].'" data-type_id="'.$arr_type_object_id[0].'"><span>'.$arr_type_object_names[$arr_type_object_id[1]].'</span><span class="icon">'.getIcon('close').'</span></div>';
							}
						}
						
					} else {
						
						$url_filter .= '<div class="string"><span>'.htmlspecialchars($filter_element).'</span><span class="icon">'.getIcon('close').'</span></div>';
						
					}
				}
				
				$arr_filters[$arr_filter_part_id]['url_filter'] = $url_filter;
			}
		}
		
		$arr_filters = array_reverse($arr_filters, true);
	
		foreach ($arr_filters as $arr_filter) {				
		
			$return .= '<div class="filter-container">
				<div class="filter-search-bar">
					<div class="filter-active" data-filter_id="'.$arr_filter['filter_id'].'">'.$arr_filter['url_filter'].'</div>
					<div class="filter-input"><input type="text" id="y:ui:search-0" placeholder="'.htmlspecialchars($arr_filter['placeholder_text']).'" data-filter_type_id="'.$arr_filter['filter_type_id'].'"/></div>
				</div>
				<div class="results hide"></div>
			</div>';
		}

		return $return;		
	}
	
	private function getProjectTypesElm() {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');	
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);
		
		$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
				
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		
		foreach ((array)$arr_public_interface_project_types as $type_id) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			$return .= '<div class="a type" id="y:ui:set_type-'.$type_id.'"><span class="icon">'.getIcon('play').'</span>'.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])).'</div>';
		}
		
		return $return;	
	}
	
	private function getProjectScenariosElm() {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');	
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');

		foreach ((array)$arr_public_user_interface['project_scenarios'][$public_user_interface_active_custom_project_id] as $scenario_id => $value) {
			
			if (!$url_scenario_id || ($url_scenario_id && $url_scenario_id == $scenario_id)) {
				
				if ($value['scenario_grid'] || $value['scenario_list'] || $value['scenario_geo'] || $value['scenario_soc'] || $value['scenario_time']) {
					
					$arr_type_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($public_user_interface_active_custom_project_id, false, false, $scenario_id); 
					
					if (!count((array)$arr_type_scenario)) {
						continue;
					}
					
					$return .= '<div class="a scenario" id="y:ui:set_scenario-'.$scenario_id.'"><span class="icon">'.getIcon('play').'</span>'.htmlspecialchars(Labels::parseTextVariables($arr_type_scenario['name'])).'</div>';
				}	
			}
		}

		return $return;	
	}
	
	private function createViewText($id = false) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		if (!$id) {
				
			$arr_texts = cms_nodegoat_public_interfaces::getPublicInterfaceTexts($public_user_interface_id);
			
			foreach ((array)$arr_texts as $arr_text) {
				
				$arr_tag_tabs['links'][] = '<li><a href="#">'.Labels::parseTextVariables($arr_text['name']).'</a></li>';
				$arr_tag_tabs['content'][] = '<div class="body">'.parseBody($arr_text['text']).'</div>';
			}
			
			if (count($arr_tag_tabs['links']) > 1) {
				
				$return .= '<div class="tabs">
					<ul>
						'.implode('', $arr_tag_tabs['links']).'
					</ul>
					'.implode('', $arr_tag_tabs['content']).'
				</div>';
				
			} else {
				
				$return .= '<div class="tabs">
					<ul><li><a href="#">'.Labels::parseTextVariables($arr_text['name']).'</a></li></ul>
					<div class="body">'.parseBody($arr_text['text']).'</div>
				</div>';
				
			}
			
		} else {
				
			$arr_id = explode("_", $id);
			
			if ($arr_id[0] == 'scenario') {
		
				$arr_type_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($public_user_interface_active_custom_project_id, false, false, $arr_id[1]); 
				$title = $arr_type_scenario['name'];
				
				$return .= '<div class="tabs">
					<ul><li><a href="#">'.Labels::parseTextVariables($title).'</a></li></ul>
					<div class="body">'.parseBody($arr_type_scenario['description']).'</div>
				</div>';
				
			} else if ($arr_id[0] == 'project') {
				

				$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
				$project_name = Labels::parseTextVariables(($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['name'] ? $arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['name'] : $arr_project['project']['name']));

				$return .= '<div class="tabs">
					<ul><li><a href="#">'.$project_name.'</a></li></ul>
					<div class="body">'.parseBody($arr_public_user_interface['interface']['settings']['projects'][$public_user_interface_active_custom_project_id]['info']).'</div>
				</div>';
											
			}
		}
		
		return self::createViewElm('<div class="texts">'.$return.'</div>');

	}
	
	public static function createViewElm($elm, $location = false) {		
		
		return '<div data-method="view_elm" '.($location ? 'data-location="'.$location.'"' : '').'>
					<div class="head"><button class="close" type="button">
						<span class="icon">'.getIcon('close').'</span>
					</button></div>
					'.$elm.'
				</div>';

	}
	
	public static function css() {
		
		$return = '.ui { display: flex; flex-flow: column nowrap; align-content: flex-start; flex: 1 1 100%; position: relative; min-height: 100vh; background-color: #f3f3f3; }
					.ui menu.buttons { position: relative; width: 100%; margin: 0 0 5px 0; padding: 0; text-align: right; }				
					.ui menu.buttons button { display: inline-block; height: 40px; width: 40px; margin: 0px 5px 5px 0px; padding: 0; border: 0; border-radius: 0; background-color: #ccc; }
					.ui menu.buttons button:first-child { margin-top: 0px; }
					.ui menu.buttons button span { color: #fff; }
					.ui menu.buttons button.in-selection { color: #fff; background-color: #0096e4; }
					.ui menu.buttons button:hover { color: #fff; background-color: #0096e4; text-decoration: none; }
					
					.ui .labmap.soc .legends > figure.selected-node dl span { pointer-events: none; }
					
					.ui.print > *,
					.ui.fullscreen > * { display: none; }	
					.ui.print > .overlay,
					.ui.fullscreen > .overlay { display: block; }
					.ui.print > .project-dynamic-data,
					.ui.fullscreen > .project-dynamic-data { display: flex; }	
					.ui.print > .project-dynamic-data > .tools,
					.ui.fullscreen > .project-dynamic-data > .tools { display: none; }	

					.ui.print { background-color: #fff; margin: 0px; padding: 5px; }
					.ui.print .object > div > h1,
					.ui.print .object > div > h2,
					.ui.print .object > div > h3,
					.ui.print .object > div > p { margin-top: 20px; width: 100%; text-align: center; font-style: italic; }
					.ui.print .object > div > ul > li { margin: 5px; } 
					.ui.print .object > div > ul > li.keywords > span { margin: 10px; padding: 10px; background-color: #eee;  box-sizing: border-box; display: inline-block; } 
					.ui.print .object > div dl > li > dt { font-weight: bold; }
					.ui.print .object > div dl > li > dt,
					.ui.print .object > div dl > li > dd { margin: 10px; background-color: #fff; }
					.ui.print .object > div dl > li > dd .tabs > div { border: 0; }
					.ui.print .object > div dl > li > dd .body .a { color: #444444; text-decoration: none; }
					.ui.print .object > div dl > li.text_tags dd div + p { display: none;}
					.ui.print > .project-dynamic-data > .data > .object,
					.ui.print > .project-dynamic-data > .data > .object > div { background-color: #fff; margin: 0px; padding: 0px; border: 0; }
					.ui.print > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li dd {  background-color: #fff; }
					
					.ui > .header-info { position: relative; width: 100%; margin: 0; padding: 0; box-sizing: border-box; background-color: #555; }
					.ui > .header-info > h1 { max-width: 90vw; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 0px; line-height: 60px; padding: 0 95px 0 70px; font-size: 20px; font-weight: bold; color: #fff; box-sizing: border-box; } 
					.ui > .header-info > h1:hover { text-decoration: none; } 
					.ui > .header-info > span { position: absolute; right: 0px; top: 0px; width: 60px; height: 100%; background-color: #0096e4; color: #fff; text-align: center; }
					.ui > .header-info > span svg { height: 50%; }
					.ui > .header-info > span:hover { text-decoration: none; }

					.ui > label { display: none; }
					.ui > input { display: none; }
					
					.ui .datatable { background-color: #fdfdfd; }				
					.ui .datatable tr.a:hover { background-color: #eee; text-decoration: none; color: #000; }
					
					.ui button { border: 0; border-radius: 0; background-color: rgba(255,255,255,0.3); display: inline-block;}
					.ui .paginate button { background-color: #ccc; }
					.ui .paginate button.selected { background-color: rgba(255,255,255,0.1); color: #333; }
					.ui .dialog > nav > button,
					.ui .timeline button { background-color: #000; }

					.ui > .fixed-view-container:empty { display: none; }
					.ui > .fixed-view-container:not(:empty) ~ * { display: none; }
					.ui > .fixed-view-container { position: fixed; display: block; z-index: 15; top: 0; right: 0; left: 0; height: 100vh; background-color: #efefef; }
					.ui > .fixed-view-container > div { position: absolute; left: 0; top: 0; right: 0; height: 100vh;  overflow-y: scroll; }
										
					.ui > .fixed-view-container > div .head { position: absolute; left: 0; top: 0; right: 0; margin: 0; background-color: #efefef; height: 50px;}
					.ui > .fixed-view-container > div .head button.close { position: absolute; top: 0; right: 0; border: 0; margin: 0;  }
					.ui > .fixed-view-container > div .head + div { margin: 100px 60px 160px 60px; }
					.ui > .fixed-view-container > div .url { text-align: center; }
					
					.ui > .fixed-view-container > div button { width: 50px; height: 50px; border: 0; border-radius: 0; margin: 20px; padding: 0; background-color: rgba(0,0,0, 0.3); }
					.ui > .fixed-view-container > div button > span { width: 36px; color: #fff; font-size: 20px; }	
					.ui > .fixed-view-container > div input { height: 50px; line-height: 50px; font-size: 20px; width: 80vw; max-width: 1000px; }
					
					.ui > .fixed-view-container > div .tabs { }
					.ui > .fixed-view-container > div .tabs > ul > li { background-color: rgba(0,0,0,0.3); border: 0; border-radius: 0; padding: 10px; background-image: none; clip-path: none; -webkit-clip-path: none; }
					.ui > .fixed-view-container > div .tabs > ul li.selected { background-color: #fefefe; }
					.ui > .fixed-view-container > div .tabs > ul li > a { font-size: 14px; }
					.ui > .fixed-view-container > div .tabs > div { background-color: #fefefe; line-height: 20px; border: 0; }
					
					.ui > nav > ul li.projects-nav { background-color: #a3ce6c; }
					.ui > nav > ul li.projects-nav ul { width: 100%; height: 50px; background-color: #f3f3f3; color: #555; padding-left: 50px; box-sizing: border-box; }
					.ui > nav > ul li.projects-nav ul li { position: relative; display: inline-block; height: 50px; cursor: pointer; box-sizing: border-box; }
					.ui > nav > ul li.projects-nav ul li span.project-name { display: table-cell; height: 50px; padding: 8px 20px; vertical-align: bottom; text-align: center; font-weight: bold; font-size: 14px; box-sizing: border-box; }
					.ui > nav > ul li.projects-nav ul li span.project-amount { position: absolute; top: 10px; right: -5px; display: block; padding: 0 5px; height: 15px; min-width: 20px; border-radius: 8px; background-color: #eaeaea; text-align: center; font-size: 10px; line-height: 15px; color: #aaa; }
					.ui > nav > ul li.projects-nav ul li:hover,
					.ui > nav > ul li.projects-nav ul li.active { border-bottom: solid #0096e4 4px; }
					.ui > nav > ul li.projects-nav ul li:hover span.project-amount,
					.ui > nav > ul li.projects-nav ul li.active span.project-amount { background-color: #ddd; color: #444; }
										
					.ui > nav > ul > li.project-dynamic-nav[data-set="true"] { display: none; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters { position: relative; background-color: #a3ce6c; width: 100%; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters { height: 100px;  }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container { position: absolute; top: 25px; left: 0px; width: 100%; display: flex; z-index: 2; justify-content: center; padding: 0 5px; box-sizing: border-box; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container { flex: 1 1 10px; min-width: 10px; max-width: 80vw; margin: 0 5px;  }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar { box-sizing: border-box; display: inline-block; width: 100%; min-width: 200px; height: 50px; padding: 0 0 0 50px; background: url("/CMS/css/images/icons/search.svg") no-repeat scroll 20px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active { display: flex; overflow: hidden; max-width: 70%; box-sizing: border-box; margin: 0px; padding: 0px; height: 50px; float: left; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active > div { display: inline-block; min-width: 30px; box-sizing: border-box; white-space: nowrap; margin: 10px 3px; padding: 0px 5px; height: 30px; font-size: 16px; line-height: 30px; vertical-align: middle;}
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active > div.string { background-color: #ccc; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active > div span { display: inline-block; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active > div span:first-child { width: calc(100% - 24px); overflow: hidden; text-overflow: ellipsis; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active > div span.icon { cursor: pointer; border-left: 1px solid #444; margin-left: 5px; padding: 0 3px 0 6px; vertical-align: top; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active > div span.icon svg { height: 11px;  }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-input { overflow: auto; margin-right: 50px; height: 50px; min-width: 30%;}
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-input input { width: 100%; box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 16px; height: 50px; border: 0px; box-sizing: border-box; overflow: hidden; text-overflow: ellipsis; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container button { width: 50px; height: 50px; background-color: #0096e4; border: 0; text-align: center; vertical-align: middle;  }					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container button span { color: #fff; }					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container button span svg { height: 50%; }					

					.ui > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-help,
					.ui > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-scenarios,
					.ui > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-types { display: none; }
					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters[data-active="true"] ~ li { display: none; }
					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results { position: relative; width: 100%; min-width: 200px; margin-top: 10px; display: block; overflow-x: hidden; overflow-y: auto; background-color: rgba(238, 238, 238, 0.75);  }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results:empty { display: none; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results .object-thumbnail { position: relative; width: 100%; background-color: rgba(238, 238, 238, 0.5); margin: 3px 0 2px 10px; height: 50px;  white-space: nowrap; overflow: hidden;}
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results .object-thumbnail > div { position: relative; box-sizing: border-box; display: inline-block; vertical-align: middle; height: 50px; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results .object-thumbnail > div.image { width: 50px; background-color: #ddd; background-repeat: no-repeat; background-position: center 10%; background-size: cover; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results .object-thumbnail > div.image span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results .object-thumbnail > div.name { max-width: calc(100% - 80px); margin-left: 5px; line-height: 50px; font-size: 18px; overflow: hidden; text-overflow: ellipsis;}

					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results ul.keywords { display: flex; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results ul.keywords > li { overflow: hidden; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results ul.keywords > li > ul { display: flex; flex-flow: wrap; white-space: normal; vertical-align: top; padding: 5px; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results ul.keywords > li > ul > li { cursor: pointer; display: inline-block; padding: 4px 8px; margin: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results ul.keywords > li > ul > li:not(.separator) { max-width: 250px; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results ul.keywords .separator { display: none; cursor: default; font-variant: small-caps; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results > p { padding: 10px; cursor: pointer; }
					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help { display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help > div { background-color: #0096e4; padding: 10px 20px; margin: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 15px; line-height: 20px; font-weight: bold; color: #fff; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help > div:hover { background-color: #0096e4; color: #fff; text-decoration: none; }
					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters + .project-types,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help + .project-types,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters + .project-scenarios,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help + .project-scenarios { margin-top: 50px; }
					
					.ui > nav > ul > li.project-dynamic-nav ul li:last-child:not(.project-filters) { margin-bottom: 20px; }
					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-types,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-scenarios { display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start;  }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-types > div,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-scenarios > div { position: relative; display: flex; align-items: center; justify-content: center; margin: 15px 15px 0 0; line-height: 45px; padding: 0 30px 0 20px; background-color: #ddd; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 15px; font-weight: bold; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-types > div > span.icon,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-scenarios > div > span.icon { background-color: #0096e4; color: #fff; width: 25px; height: 25px; padding-left: 1px; border-radius: 25px; text-align: center; margin-right: 15px; line-height: 22px; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-types > div > span.icon svg,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-scenarios > div > span.icon svg { height: 10px; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-types > div:hover,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-scenarios > div:hover { background-color: #0096e4; color: #fff; text-decoration: none;}
					.ui > nav > ul > li.project-dynamic-nav ul li.project-types > div:hover > span.icon,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-scenarios > div:hover > span.icon { background-color: #fff; color: #0096e4; }
					
					.ui > .project-dynamic-data { position: relative; flex: 1; display: flex; flex-wrap: wrap; flex-direction: column; height: 100%; max-width: 100%; }
					.ui > .project-dynamic-data > .tools { width: 100%; margin: 0; background-color: #a3ce6c; box-sizing: border-box;  }	
					.ui > .project-dynamic-data > .tools.no-filter { display: none; }
					.ui > .project-dynamic-data > .data { flex: 2; position: relative; display: flex; flex-wrap: nowrap; flex-direction: row; justify-content: center; height: 100%; max-width: 100%; }							
					.ui > .project-dynamic-data > .data > .objects { position: relative; flex: 3 1 100%; max-width: 100%; }							
					.ui > .project-dynamic-data > .data > .object { position: relative; flex: 2 1 100%; box-sizing: border-box; max-width: 40vw; }	
					
					.ui > .project-dynamic-data > .tools[data-object_active="true"] + .data > .objects[data-display_mode="list"] { max-width: 60vw; }							
					
					.ui > .project-dynamic-data > .data > .object.full { position: absolute; top: 0; right: 0; left: 0; height: auto; min-height: 100%; z-index: 1; min-width: 100%; padding: 30px; background-color: #f3f3f3; }
						
					.ui > .project-dynamic-data > .data > .objects:empty,
					.ui > .project-dynamic-data > .data > .object:empty { flex: 0; display: none; }	
					
					.ui > .project-dynamic-data > .data > .objects:empty + .object { max-width: 90vw; border-top: 4px solid #f3f3f3;}
					
					.ui > .project-dynamic-data > .data > .thumbnail { display: none; }
											
					.ui > .project-dynamic-data > .tools > div { display: flex; justify-content: center; }			
					
					.ui > .project-dynamic-data > .tools > div > div { background-color: rgba(255,255,255, 0.8); padding: 0; margin: 10px 5px; white-space: nowrap; }				
					.ui > .project-dynamic-data > .tools > div > div > span { white-space: nowrap; text-align: center; font-weight: bold; display: inline-block; vertical-align: middle; color: #444; line-height: 36px; margin: 2px; margin-right: 0px; padding: 0 12px; box-sizing: border-box; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}				
					.ui > .project-dynamic-data > .tools > div > div > span:last-child { margin-right: 2px;  }			
					.ui > .project-dynamic-data > .tools > div > div > span > span:first-child { padding-right: 8px; }
					.ui > .project-dynamic-data > .tools > div > div > span:hover { text-decoration: none; }
					.ui > .project-dynamic-data > .tools > div > div.visualisation-buttons > span.active,
					.ui > .project-dynamic-data > .tools > div > div.visualisation-buttons > span:hover { background-color: #0096e4; color: #fff;  }
					
					.ui > .project-dynamic-data > .tools > div > div.result-info,
					.ui > .project-dynamic-data > .tools > div > div.controls { background-color: transparent;  }
					.ui > .project-dynamic-data > .tools > div > div.result-info > span:first-child:not(:last-child) { padding-right: 5px; }
					.ui > .project-dynamic-data > .tools > div > div.result-info > span.set + span.amount { padding-left: 0; }
					.ui > .project-dynamic-data > .tools > div > div.result-info > span > span:first-child { padding-right: 3px; }
					.ui > .project-dynamic-data > .tools > div > div.result-info > span,
					.ui > .project-dynamic-data > .tools > div > div.controls > span { background-color: rgba(255,255,255, 0.4); margin: 2px 0;}				
					.ui > .project-dynamic-data > .tools > div > div.controls > span:hover { color: #444; }				
					
					.ui > .project-dynamic-data > .tools .new-data { display: none }
					
					.ui > .project-dynamic-data > .data > .objects { display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; }
											
					.ui > .project-dynamic-data > .data > .objects[data-display_mode="grid"] { padding: 50px; }							
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail { position: relative; width: 140px; height: 170px; background-color: #ededed; margin: 0 55px 55px 0; border: 1px solid #d0d0d0; }
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail .image { margin: 4px 4px 0 4px; width: 132px; height: 131px; background-repeat: no-repeat; background-position: center 10%; background-size: cover; }
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail .image span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail .name { position: absolute; bottom: 0px; width: 100%; min-height: 35px; max-height: 100%; display: flex; overflow: hidden; justify-content: center; align-items: center; box-sizing: border-box; background-color: #ededed; padding: 5px; text-align: center; vertical-align: middle; color: #000; }
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail:hover { text-decoration: none; background-color: #0096e4; border-color: #0096e4; }
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail:hover .image span,
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail:hover .name { color: #fff; background-color: #0096e4; }
					.ui > .project-dynamic-data > .data > .objects > button { display: block; width: 20%; margin: 20px 40% 20px 40%; text-align: center; background-color: rgba(255,255,255, 0.3); padding: 20px; box-sizing: border-box; border: 0; font-weight: bold; color: #444; }
					.ui > .project-dynamic-data > .data > .objects > button:hover { text-decoration: none; background-color: #0096e4; color: #fff; }

					.ui > .project-dynamic-data > .data > .objects > [data-visualisation_type] { position: absolute; top: 0; right: 0; bottom: 0; left: 0; width: 100%; flex: 2; height: 100%; }
					
					.ui > .project-dynamic-data > .data > .objects > .tabs.list-view { position: relative; margin: 5px; width: 100%; max-width: 98vw; }	
					.ui > .project-dynamic-data .tabs.list-view > ul > li { background-color: #f5f5f5; border: 0; border-radius: 0; padding: 5px 10px; background-image: none; clip-path: none; -webkit-clip-path: none;  }
					.ui > .project-dynamic-data .tabs.list-view > ul > li.selected { background-color: #ddd; }
					.ui > .project-dynamic-data .tabs.list-view > ul > li a { color: #444; }
					.ui > .project-dynamic-data .tabs.list-view > div { position: relative; padding: 0; border: 0; }	
					.ui > .project-dynamic-data .tabs.list-view > div > div { position: relative; width: 100%; }	
					.ui > .project-dynamic-data .tabs.list-view > div > div div.options { background-color: #ddd; }	
					
					.ui > .project-dynamic-data > .data > .object { background-color: #eee; }
					.ui > .project-dynamic-data > .data > .object > div { position: relative; background-color: #eee; padding-bottom: 20px; height: auto; }
					
					.ui > .project-dynamic-data > .data > .object > div > .tabs { margin: 15px; }
					
					.ui > .project-dynamic-data > .data > .object .datatable > .options > div > input { display: none; }
					
					.ui > .project-dynamic-data > .data > .object > div > ul > li { margin: 10px; }
					
					.ui .head { margin: 0px; position: relative; background-color: #777; display: flex; justify-content: space-between; width: 100%; }	
					.ui .head > .object-thumbnail-image { display: block; margin: 0; padding: 0; height: 60px; width: 60px; min-width: 60px; background-repeat: no-repeat; background-position: center 10%; background-size: cover;  }	
					.ui .head > h1 { position: relative; flex-grow: 2; color: #efefef; margin: 0; min-height: 60px; line-height: 35px; font-size: 20px; padding: 12px 20px; box-sizing: border-box; word-break: break-all;}	
					.ui .head > .navigation-buttons { position: relative; white-space: nowrap; }
					.ui .head > .navigation-buttons > button { border: 0; width: 60px; height: 60px; border-radius: 0; margin: 0; padding: 0; background-color: #555; display: inline-block;}
					.ui .head > .navigation-buttons > button > span { color: #fff; }	
	
					.ui > .project-dynamic-data > .data > .object > div menu.buttons { width: auto; float: right; }	
					
					.ui > .project-dynamic-data > .data > .object .combined-filters { position: relative; display: flex; flex-wrap: wrap; align-content: flex-start; }	
					.ui > .project-dynamic-data > .data > .object .combined-filters > div { position: relative;  display: flex; flex-wrap: nowrap; padding: 5px; margin: 0 15px 10px 0; background-color: #fff; }	
					.ui > .project-dynamic-data > .data > .object .combined-filters > div > input { margin-right: 5px; }	
					.ui > .project-dynamic-data > .data > .object .combined-filters > div > * { padding: 5px; }	

					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul > li { background-color: transparent; border: 0; background-image: none; border-radius: 0; padding: 0; clip-path: none; -webkit-clip-path: none; }
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul > li.selected { border: 0; }
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul > li.selected a { background-color: rgba(255,255,255,0.7);}
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul > li.no-data a { background-color: rgba(255,255,255,0.35); pointer-events: none; }
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul > li > a { position: relative; font-size: 14px; padding: 10px; background-color: #aaa; margin-right: 10px; }
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul.big > li > a { margin-bottom: 8px; }
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul > li > a > span { line-height: 14px; }
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > ul > li > a > span.amount {  position: absolute; top: -10px; right: -15px; display: block; padding: 0 5px; height: 15px; min-width: 20px; border-radius: 8px; background-color: #0096e4; text-align: center; font-size: 10px; line-height: 15px; color: #fff; }
					.ui > .project-dynamic-data > .data > .object > div > .tabs.object-view > div { margin-top: 1px; background-color: rgba(255,255,255,0.7); border: 0; }

					.ui > .project-dynamic-data > .data > .object ul::after { content: " "; display: block; height: 0; clear: both; }
					.ui > .project-dynamic-data > .data > .object ul > li.media > span { margin: 0 10px 10px 0; box-sizing: border-box; display: inline-block; }
					.ui > .project-dynamic-data > .data > .object ul > li.media > span > img { max-height: 40vh; max-width: 100%; }
					.ui > .project-dynamic-data > .data > .object ul > li.related-media { display: flex; flex-wrap: wrap; }
					.ui > .project-dynamic-data > .data > .object.full ul > li.related-media { float: right; clear: both; margin-top: 50px; width: 340px; padding: 10px 0 0 10px; justify-content: flex-end;}
					.ui > .project-dynamic-data > .data > .object ul > li.related-media > div { width: 150px; height: 150px; display: inline-block; margin: 0 10px 10px 0; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > .project-dynamic-data > .data > .object ul > li.related-media > div > span { width: 100%; text-align: center; color: #fff; }
					.ui > .project-dynamic-data > .data > .object ul > li.related-media > div > span > svg { height: 25%; }
					.ui > .project-dynamic-data > .data > .object ul > li.keywords span { display: inline-block; padding: 10px; margin: 0 10px 10px 0;}
					.ui > .project-dynamic-data > .data > .object ul > li.keywords span:hover { color: #fff; background-color: #0096e4; text-decoration: none; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl { display: flex; flex-wrap: wrap; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li { margin-bottom: 10px; width: 100%;}
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li dt { display: inline-block; font-weight: bold; margin-bottom: 5px; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li dd { display: inline-block; padding: 10px; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li dd > span.a { display: inline-block; background-color: #fff; padding: 10px; margin-right: 5px; margin-top: 5px;}
					
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li dd > span.a:hover { text-decoration: none; background-color: #0096e4; color: #fff; }
					
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.type dt,
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.classification dt { margin-bottom: 0; }
					
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.type dd,
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.classification dd { display: inline-block; background-color: transparent; padding: 0px; }

					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.text_tags dd div + p { display: none;}
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.text_tags dd > div { background-color: #fff; padding: 10px; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.text_tags dd > div > ul { display: none;}
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.text_tags dd > div.tabs > div { padding: 0px; background-color: #fff; border: 0px;}
					
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.text_tags dd span.tag { white-space: nowrap; }

					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.external dt { display: block; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.external dd { max-width: 80%; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.external dd > a { display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; padding: 7px 40px 7px 12px; margin: 0 5px 5px 0; font-size: 1.4rem; color: #444444; background: url("/CMS/css/images/icons/linked.svg") no-repeat scroll right 15px center / 10px 10px #efefef;}
					.ui > .project-dynamic-data > .data > .object ul > li.object-descriptions > dl li.external dd > a:hover { color: #fff; text-decoration: none; background-color: #0096e4; }
					
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs .tabs { max-width: 35vw; }
					
					.ui > .project-dynamic-data > .data > .object.full ul > li.object-subs .tabs,
					.ui > .project-dynamic-data > .data > .objects:empty + .object ul > li.object-subs .tabs { max-width: 80vw; }

					.ui > .project-dynamic-data > .data .object .object-thumbnail { width: calc(100% - 30px); height: 50px; background-color: #fff; margin: 15px; box-sizing: border-box; display: flex; }
					.ui > .project-dynamic-data > .data .thumbnail .image,
					.ui > .project-dynamic-data > .data .object .object-thumbnail .image { display: inline-block; width: 50px; height: 100%; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > .project-dynamic-data > .data .thumbnail .image span,
					.ui > .project-dynamic-data > .data .object .object-thumbnail .image  span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui > .project-dynamic-data > .data .thumbnail .name,
					.ui > .project-dynamic-data > .data .object .object-thumbnail .name { width: calc(100% - 50px); max-width: 500px; height: 100%; color: #000; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 16px; vertical-align: middle; margin: 0; padding: 0; padding-left: 20px; box-sizing: border-box;}
					.ui > .project-dynamic-data > .data .thumbnail .name span,					
					.ui > .project-dynamic-data > .data .object .object-thumbnail .name span { line-height: 50px; }
					.ui > .project-dynamic-data > .data .object-thumbnail:hover,
					.ui > .project-dynamic-data > .data .object .object-thumbnail:hover { text-decoration: none; color: #fff; }
					
					.ui > .project-dynamic-data > .data > .object .explore-object { background-color: #efefef; padding: 20px; } 
					.ui > .project-dynamic-data > .data > .object .explore-object > div { height: calc(100vh - 450px);  } 
					.ui > .project-dynamic-data > .data > .object .explore-object > div .labmap > .controls .timeline .buttons { display: none; } 
					
					.ui > .selections-container.view,
					.ui > .selections-container.list > div { background-color: #0096e4; }
					
					.ui > .selections-container { z-index: 10; width: 20vw; min-width: 300px; }
					.ui > .selections-container.list { min-width: 100px; }
					.ui > .selections-container.view { position: absolute; left: 0; top: 0px; min-height: 100%;  }
					.ui > .selections-container.view .head { background-color: rgba(0,0,0,0.2); }
					.ui > .selections-container.view .head button { background-color: rgba(0,0,0,0.3); }
					
					.ui > .selections-container.view menu.buttons button { background-color: rgba(255,255,255,0.3); margin: 5px 5px 0 0; }
					.ui > .selections-container.view menu.buttons button:hover { background-color: #fff;  }
					.ui > .selections-container.view menu.buttons button:hover span { color: #0096e4; }
					.ui > .selections-container.view menu.buttons button.add-selection { position: absolute; left: 5px; }
					.ui > .selections-container.view menu.buttons button.add-selection span { font-size: 14px; }
					.ui > .selections-container.view menu.buttons > span { display: block; margin: 0; text-align: center; margin-top: 10px; }
					.ui > .selections-container.view menu.buttons > span > button { background-color: #fff;}
					.ui > .selections-container.view menu.buttons > span > button > span { color: #0096e4; font-size: 20px; }
					.ui > .selections-container.view menu.buttons > span > input {  width: calc(100% - 10px); margin: 5px; font-size: 20px; }
					
					.ui > .selections-container.view > h3 { margin-top: 25px; color: #fff; }
					.ui > .selections-container.view > p,
					.ui > .selections-container.view > ul.external > li > div > div > .name > span,
					.ui > .selections-container.view > ul.external > li > div > h4,
					.ui > .selections-container.view > ul.external > li > div > p { color: #fff; }
					.ui > .selections-container.view > h3,
					.ui > .selections-container.view > p,
					.ui > .selections-container.view > ul.external > li { padding: 10px 25px; }
					.ui > .selections-container.view > ul.external > li > div > div > .image { margin: 0; width: 50px; height: 50px; display: inline-block; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > .selections-container.view > ul.external > li > div > div > .name { display: inline-block; width: calc(100% - 50px); line-height: 50px; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff; font-size: 14px; vertical-align: top; margin: 0; box-sizing: border-box; background-color: rgba(0,0,0, 0.1); padding: 0 5px;  }
					.ui > .selections-container.view > ul.external > li > div > div.no-image > .name { width: 100%; }
					
					.ui > .selections-container.view .options { background-color: transparent; width: 100%; margin-top: 25px; padding: 0;  position: relative;} 
					.ui > .selections-container.view .options fieldset > ul { width: 100%; }
					.ui > .selections-container.view .options fieldset > ul > li { width: 100%; display: block; padding: 10px 25px; box-sizing: border-box;}
					.ui > .selections-container.view .options fieldset > ul > li:first-child > input { font-size: 20px; }
					.ui > .selections-container.view .options fieldset > ul > li > input[type="text"],
					.ui > .selections-container.view .options fieldset > ul > li > textarea { width: 100%; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li { width: 100%; position: relative; margin-bottom: 20px; display: table;}
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > span { color: #fff; display: table-cell;  }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div { display: table-cell; position: relative; height: 80px; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div { display: table; table-layout: fixed; position: relative; width: 100%; height: 80px; background-color: rgba(0,0,0, 0.1); padding: 5px; box-sizing: border-box; margin: 0; padding: 0; border-collapse: collapse; border: 0;}
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > div.object-thumbnail { width: calc(100% - 30px); height: 80px; display: inline-block; vertical-align: top; position: relative; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > div.object-thumbnail .image { width: 80px; height: 80px; display: inline-block; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > div.object-thumbnail .name { width: calc(100% - 100px); height: 30px; line-height: 30px;  color: #fff; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; vertical-align: top; margin: 5px 0 0 10px; box-sizing: border-box; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > div.object-thumbnail.no-image .name { width: calc(100% - 35px); margin-left: 5px;  }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > input { width: calc(100% - 45px); height: 30px; display: inline-block; margin: 5px 0 0 5px; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > button { width: 30px; height: 30px; position: absolute; top: 5px; right: 5px; background-color: rgba(255,255,255,0.3); border: 0; border-radius: 0; margin: 0; padding: 0;}
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > button:hover { background-color: #fff; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > button:hover span { color: #0096e4; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > textarea { width: calc(100% - 90px); height: 35px; position: absolute; bottom: 5px; right: 5px; resize: none; }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div > div.no-image + button + textarea { width: calc(100% - 10px); }
					.ui > .selections-container.view .options fieldset > ul > li > ul.sorter > li > div > div.heading > textarea { width: calc(100% - 10px); }
					
					.ui > .selections-container.list { position: fixed; bottom: 10px; left: 10px; }
					.ui > .selections-container.list > div { margin-top: 10px; cursor: pointer; padding: 0 10px; height: 30px; }
					.ui > .selections-container.list > div > span { display: inline-block; width: 30px; text-align: right; color: #fff; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; line-height: 30px; font-weight: bold; }
					.ui > .selections-container.list > div > span:first-child { width: calc(20vw - 50px); min-width: 50px; text-align: left; }
					
					.ui .selections-hover { display: block; position: absolute; width: 250px; background-color: #ccc; }
					.ui .selections-hover > div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; line-height: 30px; color: #fff; padding: 0 5px; font-weight: bold; cursor: pointer;}
					.ui .selections-hover > div.in-selection,
					.ui .selections-hover > div:hover { color: #fff; background-color: #0096e4; text-decoration: none; }

					.ui .hover { display: block; position: absolute; width: 150px; height: 150px; background-color: #a3ce6c; pointer-events: none;}
					.ui .hover:after, 
					.ui .hover:before { top: 100%; left: 50%; border: solid transparent; content: " "; height: 0; width: 0; position: absolute; pointer-events: none; } 
					.ui .hover:after { border-color: rgba(136, 183, 213, 0); border-top-color: #a3ce6c; border-width: 10px; margin-left: -10px; }
					.ui .hover .image { width: 100%; height: 95px; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: rgba(255,255,255, 0.3); }
					.ui .hover .image span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; color: #fff; }
					.ui .hover .name { width: 100%; height: 35px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; box-sizing: border-box; padding: 5px; line-height: 25px; text-align: center; vertical-align: middle; color: #fff; font-weight: bold;}
					.ui .hover .ref-count { position: relative; width: 100%; display: flex; background-color: rgba(255,255,255, 0.3); justify-content: center; } 
					.ui .hover .ref-count > div { position: relative; width: 100%; height: 15px; margin: 0; padding: 0; display: flex; justify-content: center;} 
					.ui .hover .ref-count > div span { color: #fff; line-height: 15px; padding: 0 5px; font-weight: bold; text-align: center; vertical-align: middle;} 
					.ui .hover .ref-count > div span.arrow { font-size: 20px; line-height: 12px; padding: 0;  } 
										
					@media all and (max-width : 1120px) {
					
						.ui.responsive-layout-enabled { padding-top: 60px; box-sizing: border-box; }

						.ui.responsive-layout-enabled > .fixed-view-container > div > .head + div { margin: 70px 20px 160px 20px; }
						
						.ui.responsive-layout-enabled > .header-info { position: fixed; top: 0; left: 0; right: 0; z-index: 2; } 
						
						.ui.responsive-layout-enabled > label { cursor: pointer; display: block; position: fixed; left: 15px; top: 0px; line-height: 60px; text-align: center; font-size: 20px; color: #fff; z-index: 4; }

						.ui.responsive-layout-enabled > nav { display: none; }
						.ui.responsive-layout-enabled > input:checked + nav { display: block; position: fixed; top: 60px; left: 0px; right: 0; bottom: 0; background-color: rgba(0,0,0,0.1); z-index: 4; } 					

						.ui.responsive-layout-enabled > nav > ul { width: 90%; max-width: 500px; height: 100%; background-color: #a3ce6c; display: block; overflow-y: auto;} 
						
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul { width: 100%; min-height: 50px; height: auto; background-color: rgba(255,255,255,0.5); padding: 10px 10px 0 10px; box-sizing: border-box; }
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul li { position: relative; display: inline-block; cursor: pointer; box-sizing: border-box; margin-right: 10px; margin-bottom: 8px;  height: 30px; background-color: #fff; color: #666; } 
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul li span.project-name { display: table-cell; height: 30px; padding: 0px 10px; line-height: 30px; text-align: center; font-weight: bold; font-size: 14px; box-sizing: border-box; }
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul li span.project-amount { display: none;}
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul li:hover,
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul li.active { border: 0; background-color: #0096e4; color: #fff; }
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul li:hover span.project-amount,
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul li.active span.project-amount { background-color: #444; color: #fff; }

						.ui.responsive-layout-enabled > nav > ul li.project-dynamic-nav[data-set="true"] { display: block; }
			
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters { position: relative; top: auto; left: auto; height: auto; display: inline-block; padding: 5px; box-sizing: border-box; }	

						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters[data-active="true"] ~ li,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters[data-object_active="true"] ~ li { display: block; }	

						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-help,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-scenarios,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-types { display: flex; }						

						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container { flex-wrap: wrap; position: relative; top: 0px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container { padding: 0; margin: 0; flex: 1 1 auto; width: calc(100% - 50px); max-width: 100%; display: block; box-sizing: border-box; height: auto;}
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container  + .filter-container { margin-top: 5px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results { margin-top: 5px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .results ul.keywords { display: block; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container button { margin: 0;  display: block; box-sizing: border-box; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container + .filter-container + button { margin-top: 5px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar { height: auto; white-space: normal; display: flex; flex-wrap: wrap; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active { display: inline-block; height: auto; white-space: normal; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-active > div { margin: 3px 3px 0 0; max-width: 100%; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-input { width: 100%;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container .filter-search-bar .filter-input input { white-space: normal; }
						
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul { margin: 0; padding: 0; } 
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-help,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-types,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-scenarios { margin: 30px 0; } 
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-help > div,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-types > div,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-scenarios > div { color: #fff; display: block; width: 100%; margin: 0; margin-bottom: 2px; background-color: rgba(255,255,255,0.2); box-sizing: border-box; } 
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-types > div > span.icon,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-scenarios > div > span.icon { display: none; }
				
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools { position: fixed; top: 60px; left: 0; right: 0; z-index: 2;  }				
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools > div { margin: 5px 0 5px 0; }				
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools > div > div > span { padding: 0 10px; line-height: 31px; }							
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools > div > div:not(:first-child) > span > span:first-child { display: none; }
						
						.ui.responsive-layout-enabled > .project-dynamic-data > .data { margin-top: 65px; }
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools[data-active="false"] + .data,
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools[data-object_active="true"] + .data { margin-top: 0px; }
						
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools[data-object_active="true"],
						.ui.responsive-layout-enabled > .project-dynamic-data > .tools[data-object_active="true"] + .data > .objects { display: none; }
						
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .objects[data-display_mode="list"] { max-width: 98vw; padding: 0; }
						.ui.responsive-layout-enabled > .project-dynamic-data > .data .tabs > div .datatable > .options > div > .count { display: none; }
						
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .objects[data-display_mode="grid"] { padding-top: 20px; }							
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .objects > .object-thumbnail { margin: 0 20px 20px 0; }
						
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .objects:empty + .object,
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .object { border-top: 4px solid #f3f3f3; position: absolute; top: 0; right: 0; left: 0; bottom: 0; z-index: 1; max-width: 100vw; padding: 0; }																																
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .thumbnail { display: flex; position: fixed; right: 0; left: 0; bottom: 0; height: 80px; z-index: 1; background-color: #eee; padding: 15px; box-sizing: border-box; }																
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .thumbnail:empty { display: none; }		

						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .object ul > li.object-subs .tabs { max-width: 90vw; }

						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .thumbnail > div { width: calc(100% - 60px); display: flex; box-sizing: border-box; height: 50px; background-color: #fff; }							
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .thumbnail button { margin-left: 10px; display: inline-block; width: 50px; height: 50px; background-color: #ddd; color: #444; line-height: 25px; text-align: center; vertical-align: middle;  border: 0;}		

						.ui.responsive-layout-enabled > .project-dynamic-data > .data div.head > h1 { font-size: 17px; }		

						.ui.responsive-layout-enabled > .project-dynamic-data > .data .explore-object > div { height: calc(100vh - 100px);  } 	
						
						.ui.responsive-layout-enabled > .project-dynamic-data > .data .objects .labmap > .controls .timeline { bottom: 0px } 
						.ui.responsive-layout-enabled > .project-dynamic-data > .data .objects .labmap > .controls .timeline > div { max-width: 100%; width: 100%; box-sizing: border-box; } 
						
						.ui.responsive-layout-enabled > input:checked + nav + div + .selections-container { display: none; }
						.ui.responsive-layout-enabled > .selections-container.view { position: absolute; left: 0; top: 0px; right: 0px; width: 100%; }													
						.ui.responsive-layout-enabled > .selections-container.list { bottom: 5px; left: 5px; }		
						.ui.responsive-layout-enabled > .selections-container.list > div { margin-top: 5px; cursor: pointer; padding: 0 5px; height: 25px; }
						.ui.responsive-layout-enabled > .selections-container.list > div > span { line-height: 25px; }
					}
					
					@media all and (max-width : 700px) {
					
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .objects > .tabs > div > div div.options > div:first-child > * { display: none; }
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .object > div menu.buttons { float: none; }
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .object.full ul > li.related-media { float: none; width: auto; margin: 0; padding: 0; justify-content: start; }
						
						.ui.responsive-layout-enabled > .selections-container.list { bottom: 5px; left: 0px; width: auto; min-width: auto; }
						.ui.responsive-layout-enabled > .selections-container.list > div > span { width: auto; }
						.ui.responsive-layout-enabled > .selections-container.list > div > span:first-child { display: none; }
						
						.ui.responsive-layout-enabled > .project-dynamic-data > .data .objects .labmap > .controls .timeline > div input.date,
						.ui.responsive-layout-enabled > .project-dynamic-data > .data .objects .labmap > .controls .timeline > div span.split { display: none; } 
					}
							
					@media print {
						body, 
						.site, 
						.container, 
						.full, 
						.ui,
						.ui > .project-dynamic-data, 
						.ui > .project-dynamic-data > .data, 
						.ui > .project-dynamic-data > .data > .object, 
						.ui > .project-dynamic-data > .data > .object > div { display: block !important; }
					}';
	
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.mod.ui', function(elm_scripter) {

					var elm_selections_container = elm_scripter.find('.selections-container');
					var public_user_interface_id = elm_scripter.find('[data-public_user_interface_id]').attr('data-public_user_interface_id');
					
					window.UISELECTION = new UISelection();
					UISELECTION.init(public_user_interface_id, elm_selections_container);
												
					elm_scripter.on('click', '[id^=y\\\:ui\\\:set_project-]', function() {
					
						var cur = $(this);
						
						if (cur.is('h1')) {
						
							elm_first_project = elm_scripter.find('.projects-nav > ul > li').first();
							
							if (elm_first_project.length) {
							
								cur = elm_first_project;
							}
						}
						
						cur.siblings().removeClass('active');
						
						cur.addClass('active').quickCommand(function(arr_data) {
						
							var elm_project_dynamic_nav = $(arr_data[0]);
							var elm_project_dynamic_data = $(arr_data[1]);
	
							elm_scripter.find('.project-dynamic-nav').html(elm_project_dynamic_nav);
							elm_scripter.find('.project-dynamic-data').html(elm_project_dynamic_data);

							return elm_project_dynamic_nav;
						});
					});
					
					elm_scripter.find('[id^=y\\\:ui\\\:view_text-]').each(function() {
						COMMANDS.setTarget($(this), elm_scripter.find('div.fixed-view-container'));
					});
					
					SCRIPTER.runDynamic($('[data-method=set_project]'));
					
					var check_device_location = elm_scripter.children('.project-dynamic-data').attr('data-device_location');
					
					if (check_device_location) {
					
						var obj_device_location = new DeviceLocation();
						var elm_toolbox = getContainerToolbox(elm_scripter);
						elm_toolbox[0].device_location = obj_device_location;
					}
					
					window.onpopstate = function(event) {

						var elm_current_focus = $(event.target.target);
						var elm_current_focus_location = elm_current_focus.attr('data-location');
						
						if (elm_current_focus_location) {

							var elm_close_button = elm_current_focus.find('button.close');
							elm_close_button.trigger('click');
	
						} else {
						
							LOCATION.attach(elm_scripter[0], false, true);
							LOCATION.reload();
						}
					};

				});				
				
				SCRIPTER.dynamic('[data-method=view_elm]', function(elm_scripter) {

					elm_scripter.on('click', 'button.close', function() {
						elm_scripter.remove();
					});
					
				});
				
				
				SCRIPTER.dynamic('[data-method=set_project]', function(elm_scripter) {

					var elm_ui = elm_scripter.closest('.ui');
					
					elm_ui.find('.project-dynamic-nav').attr('data-object_active', false);
					
					elm_ui.attr('data-public_user_interface_active_custom_project_id', elm_scripter.attr('data-public_user_interface_active_custom_project_id'));
		
					// DISPLAY
					var func_display_data = function() {

						var elm_handle_dynamic_project_data = elm_ui.find('[id=y\\\:ui\\\:handle_dynamic_project_data-0]');
						elm_handle_dynamic_project_data.data({target: elm_handle_dynamic_project_data, method: 'handle_dynamic_project_data', module: 'ui'});
						elm_handle_dynamic_project_data.quickCommand(elm_handle_dynamic_project_data);	
					}
					
					// SEARCH & FILTER
					if (elm_ui.find('.filter-container').length) {
						elm_ui.find('.projects-nav').attr('data-filter', true);
					} else {
						elm_ui.find('.projects-nav').attr('data-filter', false);
					}
					var func_filter = function(options) {

						var elm_filter_container = elm_ui.find('[id=y\\\:ui\\\:filter-0]');
						var value = {arr_filter: {}};
						
						elm_filter_container.find('.filter-container').each(function() {

							var elm_active_filter = $(this).find('.filter-active');
							var key = elm_active_filter.attr('data-filter_id');

							value.arr_filter[key] = [];
							
							elm_active_filter.children().each(function() {
							
								var elm_filter_value = $(this);
								
								if (elm_filter_value.data('object_id')) {
								
									value.arr_filter[key].push({object_id: elm_filter_value.data('object_id'), type_id: elm_filter_value.data('type_id')});
									
								} else {
									
									// get rid of remove icon
									var elm_filter_value_string = elm_filter_value.clone();
									elm_filter_value_string.find('.icon').remove();
									elm_filter_value_string = elm_filter_value_string.text();
									value.arr_filter[key].push({string: elm_filter_value_string});
								}
							});
						});
				

						elm_filter_container.data({value: value}).quickCommand(function() {
							func_display_data();
						});

						setTimeout(function () {
							elm_ui.children('input').prop('checked', false);
						}, 500);
						
					};
					
					var func_search = function(elm) {
					
						var value = elm.val().replace(/\W/g, '');
						
						var elm_search_active = elm.closest('.filter-container').find('.filter-search-bar .filter-active');
						
						if (value) {
						
							var elm_string = $('<div></div>').html('<span>'+value.trim()+'</span>').addClass('string').appendTo(elm_search_active);
							
							var elm_string_close_button = $('<span></span>').addClass('icon').appendTo(elm_string);
				
							ASSETS.getIcons(elm_string, ['close'], function(data) {
								
								elm_string_close_button[0].innerHTML = data.close;
							});
							
							func_filter({new: true});

						}
						
						elm_search_active.parent().find('input').val('').blur();
						
						elm.closest('.filter-container').find('.results').addClass('hide');
					}
					
					var elm_search_input = elm_scripter.find('[id^=y\\\:ui\\\:search-]');
					
					elm_search_input.on('keypress focus keyup', function(e) {

						var elm_search_input = $(this);
						
						FEEDBACK.stop(elm_search_input);
						
						var elm_filter = elm_search_input.closest('.filter-search-bar').find('.filter-active');
						var elm_results = elm_search_input.closest('.filter-container').find('.results');
						
						if (e.type == 'keypress' && e.key == 'Enter') {
							
							if (elm_filter.attr('data-filter_id') ==  0) {
								func_search(elm_search_input);
							}
							
						} else if (e.type == 'keypress' && e.key == 'Backspace') {
						
							if (!elm_search_input.val()) {
							
								elm_filter.children().last().remove();
								
								func_filter({new: true});
								
							}
						
						} else if (e.type == 'keyup' || e.type == 'focus') {
						
							var filter_id = elm_filter.attr('data-filter_id');
							var filter_type_id = elm_search_input.attr('data-filter_type_id');
							COMMANDS.setData(elm_search_input[0], {filter_type_id: filter_type_id, filter_id: filter_id}, true);
							
							elm_search_input.quickCommand(elm_results, {'html': 'replace'});
						
						}
						
					});
					
					elm_scripter.on('click', '.results > p ', function(e) {
					
						func_search(elm_search_input);
						
					}).on('click', '[id^=y\\\:ui\\\:set_scenario-], [id^=y\\\:ui\\\:set_type-]', function() {

						$(this).quickCommand(function() {

							func_display_data();
						});
						
					}).on('click', '.filter-search-bar .filter-active div span.icon', function() {
					
						var filter_elm = $(this).closest('.filter-active');
						$(this).parent().remove();
						func_filter({new: true});
						
					}).on('click', '.keywords .keyword', function() {
					
						var elm_keyword = $('<div></div>').html('<span>'+$(this).text()+'</span>').addClass($(this).attr('class')).attr('data-object_id', $(this).data('object_id')).attr('data-type_id', $(this).data('type_id'));
						var elm_remove = $('<span></span>').addClass('icon').appendTo(elm_keyword);
						
						ASSETS.getIcons(elm_keyword, ['close'], function(data) {
								
							elm_remove[0].innerHTML = data.close;
						});
						
						var elm_search_bar = $(this).closest('.filter-container').find('.filter-search-bar');
						var elm_results = $(this).closest('.filter-container').find('.results');	
										
						elm_keyword.appendTo(elm_search_bar.find('.filter-active'));
						
						elm_results.addClass('hide');
						
						elm_search_input.val('');
						
						func_filter({new: true});
						
					}).on('click', '[id^=y\\\:ui_data\\\:show_project_type_object-]', function() {
						
						if (elm_ui.find('.filter-container').length) {
							elm_search_input.val('');
							func_search(elm_search_input);
						}
						
						$(this).quickCommand(elm_ui.find('.object'), {'html': 'append'});
						
						elm_ui.children('input').prop('checked', false);
						
					});
					
					elm_ui.on('click', function(e) {

						if (!$(e.target).closest('.filter-search-bar').length && !$(e.target).closest('.results').length) {

							if (elm_ui.find('.filter-container').length) {

								func_search(elm_search_input);
							}
							
							if (!$(e.target).is('label, input, ul, span, span path, [class*=filter]')) {
														
								elm_ui.children('input').prop('checked', false);
							}
						}
						
					});

					elm_scripter.find('[id^=y\\\:ui\\\:view_text-]').each(function() {
						COMMANDS.setTarget($(this), elm_ui.find('div.fixed-view-container'));
					});
					
				});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 
		
		if ($method == "set_project") {

			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');

			toolbar::setScenario();
			
			SiteEndVars::setFeedback('type_id', false, true);
			SiteEndVars::setFeedback('scenario_id', false, true);
			SiteEndVars::setFeedback('active_type_object_id', false, true);
			SiteEndVars::setFeedback('arr_public_user_interface_module_vars', false, true);
			
			if ($id == 0) {

				$id = cms_nodegoat_public_interfaces::getPublicInterfaceProjectIds($public_user_interface_id, 1);
			}
			
			self::setPublicUserInterfaceActiveCustomProjectId($id);
			self::setPublicUserInterfaceModuleVars(false);
			
			$this->html = [self::getProjectDynamicNavigationElm(), self::getProjectDynamicDataElm()];

		}

		if ($method == "view_text") {

			$this->html = self::createViewText($id);
		}

		if ($method == "handle_dynamic_project_data") {

			$create_data = new ui_data();
	
			$this->html = $create_data->getDynamicProjectDataTools();
		}

		if ($method == "set_type" || $method == "set_scenario") {

			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
			
			if ($method == "set_type") {
				
				$set = 'type';
				
				if ((int)$id) {
					SiteEndVars::setFeedback('type_id', $id, true);
				}
				
			} else {
				
				$set = 'scenario';
			}
			
			if ((int)$id) {
					
				self::setPublicUserInterfaceModuleVars(['set' => $set, 'id' => $id, 'display_mode' => false]);
				
			} else {
				
				toolbar::setScenario();	
				SiteEndVars::setFeedback('type_id', false, true);
				self::setPublicUserInterfaceModuleVars(false);
			}
		}
				
		if ($method == "filter") {

			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');

			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $project_id);
			$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
			
			SiteEndVars::setFeedback('arr_public_user_interface_min_max', [$value['min'], $value['max']], true);
	
			$url_filter = false;

			foreach((array)$value['arr_filter'] as $filter_id => $arr_filter_set) {
			
				$url_filter_set = false;
				
				foreach((array)$arr_filter_set as $arr_filter) {
					
					if ($arr_filter['string']) {
						if ($url_filter_set) {
							$url_filter_set .= '|'.$arr_filter['string'];
						} else {
							$url_filter_set = $arr_filter['string'];
						}
					}
					if ($arr_filter['object_id']) {
						if ($url_filter_set) {
							$url_filter_set .= '|'.(int)$arr_filter['type_id'].'-'.(int)$arr_filter['object_id'];
						} else {
							$url_filter_set = (int)$arr_filter['type_id'].'-'.(int)$arr_filter['object_id'];
						}
					}
				}
				
				if ($filter_id) {
					
					if ($url_filter_set) {
						
						if ($url_filter) {
							
							$url_filter .= '+'.$filter_id.':'.$url_filter_set;
							
						} else {
							
							$url_filter = $filter_id.':'.$url_filter_set;
						}
					}
					
				} else {
					
					$url_filter = $url_filter_set;
				}
			}
	
			if ($url_filter) {
				
				self::setPublicUserInterfaceModuleVars(['set' => 'filter', 'id' => $url_filter]);
			
			} else {
				
				toolbar::setFilter([]);
				self::setPublicUserInterfaceModuleVars(false);
			}
		} 
		
		if ($method == "search") {
		
			if (is_array($value)) {
				
				$search_name = $value['value_element'];
				$filter_type_id = $value['filter_type_id'];
				$filter_id = $value['filter_id'];
				
			}

			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
	
			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id);
			$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);

			if ($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['use_filter'] != 1 && $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['no_quicksearch'] != 1) {				

				if ($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['name']) {
					
					$project_name = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['name'];
				} else {
					
		
					$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
					$project_name = $arr_project['project']['name'];
				}
	
				if ($search_name) {
					
					//Fix for O’Conor etc, remove all non-alphanumeric characters
					$value = preg_replace("/[^[:alnum:][:space:]]/u", ' ', $search_name);
					
					$arr_type_objects = ui_data::getPublicInterfaceObjects($arr_public_interface_project_types, ['search_name' => $value], false, 15, false, false);
				
					$elm_objects = '';
					
					foreach ((array)$arr_type_objects as $type_id => $arr_objects) {

						foreach ($arr_objects as $object_id => $arr_object) {
							$elm_objects .= ui_data::createViewTypeObjectThumbnail($arr_object); 
						}
					}
					
					$elm_objects .=  '<p><span class="icon">'.getIcon('search').'</span> Search '.Labels::parseTextVariables($project_name).' full text on "'.$value.'"</p>';
				}
			}
				
			$keywords = ui_data::createViewKeywords($filter_type_id, $search_name, $filter_id);

			$this->html = '<div class="results">'.$keywords.$elm_objects.'</div>';
		}
		
	}
	
	public static function parseFilterString($value) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true);

		$arr_filter_parts = explode('+', $value);

		foreach ((array)$arr_filter_parts as $filter_part) {

			$quicksearch_strings = '';
			$arr_type_object_ids = [];
					
			$arr_filter_part = explode(':', $filter_part);
		
			if (count((array)$arr_filter_part) > 1) {
				
				$arr_filter_part_id = explode('_', $arr_filter_part[0]);
				$arr_filter_part_elements = explode('|', $arr_filter_part[1]);
			
				$type_id = $arr_filter_part_id[0];
				$object_description_id = $arr_filter_part_id[2];
				$object_description_reference_type_id = $arr_filter_part_id[3];				

			} else {
				
				$arr_filter_part_elements = explode('|', $arr_filter_part[0]);
			}

			foreach ((array)$arr_filter_part_elements as $filter_element) {
				
				if (preg_match("/\d+-\d+/", $filter_element)) {
					
					$arr_type_object_id = explode('-', $filter_element);
					
					if (is_numeric($arr_type_object_id[0]) && is_numeric($arr_type_object_id[1]) && $arr_public_interface_project_filter_types[$arr_type_object_id[0]]) {

						$arr_type_object_ids[(int)$arr_type_object_id[0]][] = (int)$arr_type_object_id[1];
					}
				} else {
					
					$quicksearch_strings .= ' '.$filter_element;
				}
			}

			if ($arr_filter_part_id && count((array)$arr_type_object_ids[$object_description_reference_type_id])) {
				
				$arr_type_set = StoreType::getTypeSet($type_id);
		
				if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_is_dynamic']) {
					
					$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['object_definitions' => [$object_description_id => ['type_tags' => [$object_description_reference_type_id => ['objects' => [[['objects' => $arr_type_object_ids[$object_description_reference_type_id]]]]]]]]]];
				
				} else {
					
					$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['object_definitions' => [$object_description_id => [[['objects' => $arr_type_object_ids[$object_description_reference_type_id]]]]]]];		
				}			
			}			
			
		}

		$arr_temp_type_object_filters = [];
		
		if ($arr_type_object_filters) {
			
			foreach (current($arr_type_object_filters) as $arr_filter) {
		
				$arr_object_filter = $arr_filter['object_filter'];
				$arr_object_filter['options'] = ['operator' => 'and'];
				$arr_temp_type_object_filters[] = $arr_object_filter;
				
			}
		}

		return ['quicksearch_strings' => $quicksearch_strings, 'arr_type_object_ids' => $arr_type_object_ids, 'arr_type_object_filters' => $arr_temp_type_object_filters];
	}
		
	public static function createFilter($arr_filter) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');

		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);		

		$arr_type_filter = [];
		$quicksearch_strings = $arr_filter['quicksearch_strings'];
		$arr_type_object_ids = $arr_filter['arr_type_object_ids'];
		$arr_type_object_filters = $arr_filter['arr_type_object_filters'];

		foreach ((array)$arr_public_interface_project_types as $type_id) {
			
			if ($arr_type_object_ids) {
				
				$arr_object_filter = self::createReferencedObjectFilter($type_id, $arr_type_object_ids, true);
				
				if (count((array)$arr_object_filter)) {
					
					$arr_type_filter[$type_id] = self::createReferencedObjectFilter($type_id, $arr_type_object_ids, true);
				}
			}
			if ($quicksearch_strings) {
				$arr_type_filter[$type_id]['search'] = $quicksearch_strings;
			}
			if ($arr_type_object_filters) {
				$arr_type_filter[$type_id] = ['object_filter' => $arr_type_object_filters];
			}
			
			$arr_feedback_filters[] = $arr_type_filter[$type_id];
			
		}

		return $arr_type_filter;
	}
	
	private static function getTraceTypeNetworkPaths($type_id, $filter_type_ids) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id);
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		$arr_types = array_unique(array_merge($arr_public_interface_settings['types']['central_types'], $arr_public_interface_project_types, array_keys($arr_project['types'])));
		
		$arr_type_network_paths = [];
		
		foreach ($filter_type_ids as $filter_type_id) {
			
			$trace = new TraceTypeNetwork($arr_types, true);
			$trace->run($type_id, $filter_type_id, 3, 'referencing', ['shortest' => true]);
			
			$arr_type_network_paths[$filter_type_id] = $trace->getTypeNetworkPaths(true);
			
		}

		return $arr_type_network_paths;
		
	}
	
	public static function createReferencedObjectFilter($id, $arr_type_object_ids, $crossreferenced = false, $steps = 2) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, false, true);
				
		$arr_type_set = StoreType::getTypeSet($id);
		$arr_object_description_ref_type_ids = arrValuesRecursive('object_description_ref_type_id', $arr_type_set['object_descriptions']);
		$arr_object_sub_description_ref_type_ids = arrValuesRecursive('object_sub_description_ref_type_id', $arr_type_set['object_sub_details']);
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		
		$arr_types = array_unique(array_merge($arr_public_interface_settings['types']['central_types'], $arr_public_interface_project_types, $arr_public_interface_project_filter_types, array_keys($arr_type_object_ids)));

		$arr_type_filter = [];
		
		foreach ($arr_type_object_ids as $type_id => $arr_object_ids) {

			if (!$crossreferenced || ($crossreferenced && !in_array($type_id, $arr_object_description_ref_type_ids) && !in_array($type_id, $arr_object_sub_description_ref_type_ids))) {
				
				$trace = new TraceTypeNetwork($arr_types, true, true);
				$trace->run($id, $type_id, $steps, 'both', ['shortest' => true]);

				$arr_type_network_paths = $trace->getTypeNetworkPaths(true);

				$collect = new CollectTypeObjects($arr_type_network_paths);
				
				$collect->addFilterTypeFilters($type_id, ['objects' => $arr_object_ids]); // Filter object ids => OR
				$collect->setScope(['users' => false, 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($public_user_interface_active_custom_project_id)]);
				$collect->init([$id => []], false);
				$arr_collect_info = $collect->getResultInfo();
				

				foreach ($arr_collect_info['filters'][0] as $arr_collect_info_filter) {

					if ($arr_collect_info_filter['object_filter']) {

						$arr_type_filter[$type_id]['object_filter'] = array_merge((array)$arr_type_filter[$type_id]['object_filter'], $arr_collect_info_filter['object_filter']);
					}
				}
				
			} else {
				
				$arr_type_filter[$type_id]['referenced_object'] = ['object_id' => $arr_object_ids];
			}
			
		}

		return $arr_type_filter;
	}
		
	public static function isFilterActive($return_total_filtered = false) {
					
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		$filter_is_active = false;		
		
		$scenario_id = SiteStartVars::getFeedback('scenario_id');
		
		if ($scenario_id) {
			
			toolbar::setScenario($scenario_id);	
			$arr_type_filters = toolbar::getFilter();
			$type_id = key($arr_type_filters);
			$arr_active_filters = $arr_type_filters[$type_id];
		
			$filter_is_active = true;
			
			if ($return_total_filtered) {
				
				$filter = new FilterTypeObjects($type_id, 'id');
				$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($public_user_interface_active_custom_project_id)]);
				$filter->setFilter($arr_active_filters);
				
				if ($arr_project['types'][$type_id]['type_filter_id']) {

					$arr_use_project_ids = array_keys($arr_project['use_projects']);			
					$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
					$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
				}
				
				$arr_info = $filter->getResultInfo();
				
				$amount_total_filtered += $arr_info['total_filtered'];
			}
		
		} else {
				
			$arr_active_filters = toolbar::getFilter();
	
			$amount_total_filtered = 0;

			foreach ((array)$arr_public_interface_project_types as $type_id => $value) {
				
				if (count((array)$arr_active_filters[$type_id]) || SiteStartVars::getFeedback('type_id') == $type_id) {
		
					$filter_is_active = true;
			
					if ($return_total_filtered) {
				
						$filter = new FilterTypeObjects($type_id, 'id');
						$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($public_user_interface_active_custom_project_id)]);
						$filter->setFilter($arr_active_filters[$type_id]);

						if ($arr_project['types'][$type_id]['type_filter_id']) {

							$arr_use_project_ids = array_keys($arr_project['use_projects']);			
							$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
							$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
						}
						
						$arr_info = $filter->getResultInfo();
		
						$amount_total_filtered += $arr_info['total_filtered'];
					}
				}
			}
		}
		
		return ($return_total_filtered && $filter_is_active ? $amount_total_filtered : $filter_is_active);
		
	}
				
	public static function setPublicUserInterfaceModuleVars($arr = ['set' => false, 'id' => false, 'display_mode' => 'grid', 'start' => false]) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');	
		
		if ($arr) {
			
			$arr_request_vars = SiteStartVars::getModVariables(0);
			
			if ($arr['display_mode'] === false && $arr_request_vars[4]) {
				
				$display_mode = false;
				
			} else if ($arr['display_mode'] != false) {
				
				$display_mode = $arr['display_mode'];
				
			} else {
				
				$display_mode = $arr_request_vars[4];
				
			}

			$arr_public_user_interface_module_vars = [
														$public_user_interface_id, 
														$public_user_interface_active_custom_project_id,
														($arr['set'] ?: $arr_request_vars[2]),
														($arr['id'] ?: $arr_request_vars[3]),
														$display_mode,
														($arr['start'] ?: $arr_request_vars[5])
													];

		} else {

			$arr_public_user_interface_module_vars = [$public_user_interface_id, $public_user_interface_active_custom_project_id];
		}

		SiteEndVars::setModVariables(0, $arr_public_user_interface_module_vars);
	}
	
	public static function getPublicUserInterfaceModuleVars() {

		self::setPublicUserInterfaceId();
		self::setPublicUserInterfaceActiveCustomProjectId();
		
		$arr_request_vars = SiteStartVars::getModVariables(0);

		if ($arr_request_vars[2]) {

			$arr_public_user_interface_module_vars = ['set' => $arr_request_vars[2], 'id' => $arr_request_vars[3], 'display_mode' => (strpos($arr_request_vars[4], 'soc') !== false ? substr($arr_request_vars[4], 0, 3) : $arr_request_vars[4]), 'start' => $arr_request_vars[5]];
			SiteEndVars::setFeedback('arr_public_user_interface_module_vars', $arr_public_user_interface_module_vars, true);
			
		} else {
			
			SiteEndVars::setFeedback('arr_public_user_interface_module_vars', [], true);
		}
		
		if ($arr_public_user_interface_module_vars['set'] == 'scenario' && $arr_public_user_interface_module_vars['id']) {

			toolbar::setScenario($arr_public_user_interface_module_vars['id']);
			
		} else { 

			toolbar::setScenario();
		}
		
		if ($arr_public_user_interface_module_vars['set'] == 'type' && $arr_public_user_interface_module_vars['id']) {

			SiteEndVars::setFeedback('type_id', $arr_public_user_interface_module_vars['id'], true);
			
		} else { 

			SiteEndVars::setFeedback('type_id', false, true);
		}

		if ($arr_public_user_interface_module_vars['set'] == 'filter' && $arr_public_user_interface_module_vars['id']) {
			
			$arr_url_filter = self::parseFilterString($arr_public_user_interface_module_vars['id']);
			toolbar::setFilter(self::createFilter($arr_url_filter));
		}
		
		if (($arr_public_user_interface_module_vars['set'] == 'object' || $arr_public_user_interface_module_vars['set'] == 'object-print') && $arr_public_user_interface_module_vars['id']) {
			
			SiteEndVars::setFeedback('active_type_object_id', $arr_public_user_interface_module_vars['id'], true);
		}
		
		if (($arr_public_user_interface_module_vars['set'] == 'selection' || $arr_public_user_interface_module_vars['set'] == 'selection-print') && $arr_public_user_interface_module_vars['id']) {
			
			SiteEndVars::setFeedback('active_selection_id', $arr_public_user_interface_module_vars['id'], true);
		}
	
	}
		
	public static function setPublicUserInterfaceId() {
		
		if (!SiteStartVars::getFeedback('public_user_interface_id')) {
			
			if (SiteStartVars::getModVariables(0)[0]) {
				
				$url_public_user_interface_id = SiteStartVars::getModVariables(0)[0];
			
				if (cms_nodegoat_public_interfaces::getPublicInterfaces($url_public_user_interface_id)) {
					$public_user_interface_id = $url_public_user_interface_id;
				}
			}
			
			if (!$public_user_interface_id) {
				
				$public_user_interface_id = cms_nodegoat_public_interfaces::getDefaultPublicInterfaceId();
			}					

			SiteEndVars::setFeedback('public_user_interface_id', $public_user_interface_id, true);
		}
	}
	
	public static function setPublicUserInterfaceActiveCustomProjectId($id = false) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		$url_project_id = SiteStartVars::getModVariables(0)[1];
		
		if ((int)$id) {
			
			$public_user_interface_active_custom_project_id = $id;
			
		} else if ($url_project_id && $arr_public_user_interface['projects'][$url_project_id]) {
			
			$public_user_interface_active_custom_project_id = $url_project_id;
			
		} else if (SiteStartVars::getFeedback('public_user_interface_id')) {
			
			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = cms_nodegoat_public_interfaces::getPublicInterfaceProjectIds($public_user_interface_id, 1);
			
		}
		
		if (!$public_user_interface_active_custom_project_id) {
			error(getLabel('msg_missing_information'));
		}

		$_SESSION['custom_projects']['project_id'] = $public_user_interface_active_custom_project_id;

		SiteEndVars::setFeedback('public_user_interface_active_custom_project_id', $public_user_interface_active_custom_project_id, true);
		
	}
}
