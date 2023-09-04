<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
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
		'ui_filter' => [],
		'ui_data' => [],
		'ui_selection' => []
	];
	
	public function contents() {
		
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		$do_print = (($arr_public_user_interface_module_vars['set'] == 'object-print' || $arr_public_user_interface_module_vars['set'] == 'selection-print') && (SiteStartVars::getFeedback('active_type_object_id') || SiteStartVars::getFeedback('active_selection_id')));
		
		if ($arr_public_user_interface['interface']['settings']['default_language']) {
			
			$arr_language_hosts = cms_language::getLanguageHosts();
			
			if (!$arr_language_hosts[SERVER_NAME_SITE_NAME]) {
				SiteStartVars::setContext(SiteStartVars::CONTEXT_LANGUAGE, $arr_public_user_interface['interface']['settings']['default_language']);
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
		
		if ($arr_public_user_interface['interface']['settings']['return_url']) {
			
			$return = '<a href="'.$arr_public_user_interface['interface']['settings']['return_url'].'" class="return" target="_parent">
							<span class="icon" data-category="full">'.getIcon('prev').'</span>
						</a>';	
			
		}
		
		$elm_info = false;
		
		if (!empty($arr_public_user_interface['texts'])) {
			
			if ($arr_public_user_interface['interface']['settings']['labels']['info']) {
			 
				$elm_info = '<span class="a quick" id="y:ui:view_text-0">'.Labels::parseLanguage($arr_public_user_interface['interface']['settings']['labels']['info']).'</span>';
				
			} else {
				
				$elm_info = '<span class="icon a quick" data-category="full" id="y:ui:view_text-0">'.getIcon('info-point').'</span>';
			}
		}
		
			
		$return .= '<div class="header-info" data-public_user_interface_id="'.$public_user_interface_id.'">
						<h1 class="a" id="y:ui:set_project-0">'.Labels::parseTextVariables($arr_public_user_interface['interface']['name']).'</h1>'.
						$elm_info.
					'</div>
					<div class="fixed-view-container"></div>
					<label for="nav-toggle">☰</label>
					<input id="nav-toggle" type="checkbox" />
					<nav class="'.$arr_public_user_interface['interface']['settings']['filter_form_position'].'">
						<ul>
							<li class="projects-nav">'.$this->createProjectsNavigation().'</li>
							<li class="project-dynamic-nav">'.$this->createProjectNavigation().'</li>
						</ul>
					</nav>
					<div class="project-dynamic-data" '.($arr_public_user_interface['interface']['settings']['show_device_location'] ? 'data-device_location="1"' : '').'>'.$this->getProjectDynamicDataElm($do_print).'</div>
					'.ui_selection::createViewSelectionsContainer($do_print);		
		
		return $return;
	}
	
	private function createProjectNavigation() {
	
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		$arr_settings =	$arr_public_user_interface['interface']['settings']['projects'][$project_id];
	
		if ($arr_settings['filter_mode'] == 'none' && $arr_settings['info_title']) {
			
			$elm_info = '<li class="project-help">
							<div id="y:ui:view_text-project_'.$project_id.'" class="a quick">
								'.($arr_settings['info_title'] ? Labels::parseTextVariables($arr_settings['info_title']) : getLabel('lbl_information')).'
							</div>
						</li>';	
		} else {
			
			$elm_filter = ui_filter::createFilter($project_id, $arr_public_user_interface);
		}
	
	
		foreach ((array)$arr_public_user_interface['project_scenarios'][$project_id] as $scenario_id => $value) {			
					
			$arr_type_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id, false, false, $scenario_id); 
			
			if (!count((array)$arr_type_scenario)) {
				continue;
			}
			
			$elm_scenarios .= '<div class="a scenario" id="y:ui:set_scenario-'.$scenario_id.'"><span class="icon">'.getIcon('play').'</span>'.strEscapeHTML(Labels::parseTextVariables($arr_type_scenario['name'])).'</div>';
	
		}
		
		if ($elm_scenarios) {
			
			$elm_scenarios = '<li class="project-scenarios">'.$elm_scenarios.'</li>';
		}
		
		$return = '<ul data-method="set_project" data-project_id="'.$project_id.'">'.
						$elm_info.
						$elm_filter.
						$elm_scenarios.
					'</ul>';
		
		return $return;
	}
	
	private function getProjectDynamicDataElm($do_print = false) {

		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');		
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_settings =	$arr_public_user_interface['interface']['settings']['projects'][$project_id];
		
		$create_data = new ui_data();
		
		if (SiteStartVars::getFeedback('active_type_object_id')) {

			$arr_id = explode('-', SiteStartVars::getFeedback('active_type_object_id'));
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];

			SiteEndVars::setFeedback('active_type_object_id', false, true);

			if ($do_print) {
				
				$print_heading = '<div>
								<p>'.SiteStartVars::getPageURL(false, 0, false).'.p/'.$public_user_interface_id.'/'.$project_id.'/object/'.$type_id.'-'.$object_id.'</p>
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
					<div class="data '.($arr_settings['show_object_fullscreen'] ? 'fullscreen-object' : '').' ">
						<div class="objects"></div>
						<div class="object '.($arr_settings['show_explore_visualisations'] ? 'show-explore-visualisations' : '').'" id="y:ui_data:show_project_type_object-0">'.$return_elm.'</div>
						<div class="object-thumbnail-container" id="y:ui_data:show_project_type_object_thumbnail-0"></div>
					</div>';
		
		return $return;
	}
	
	private function createProjectsNavigation() {
		
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		
		if (!$public_user_interface_active_custom_project_id) {
			error(getLabel('msg_missing_information'));
		}
		
		$arr_projects = StoreCustomProject::getProjects();	

		if (count((array)$arr_public_user_interface['interface']['settings']['projects']) > 1) {
			
			$arr_project_type_object_amounts = self::getProjectsTypeObjectAmounts($arr_public_user_interface, $arr_projects);
			$arr_types = StoreType::getTypes();

			$return = '<span class="icon prev" data-category="full">'.getIcon('prev').'</span><ul>';
			
			foreach ((array)$arr_public_user_interface['interface']['settings']['projects'] as $nav_project_id => $arr) {
				
				$project_name = Labels::parseTextVariables(($arr_public_user_interface['interface']['settings']['projects'][$nav_project_id]['name'] ? $arr_public_user_interface['interface']['settings']['projects'][$nav_project_id]['name'] : $arr_projects[$nav_project_id]['project']['name']));

				$return .= '<li '.($nav_project_id == $public_user_interface_active_custom_project_id ? 'class="active"' : '').' id="y:ui:set_project-'.$nav_project_id.'" >
								<span class="project-name">'.strEscapeHTML($project_name).'</span>
								'.($arr_project_type_object_amounts[$nav_project_id]['project_total'] ? '<span class="project-amount">'.num2String($arr_project_type_object_amounts[$nav_project_id]['project_total']).'</span>' : '').'
							</li>';			
			}
			
			$return .= '</ul><span class="icon next" data-category="full">'.getIcon('next').'</span>';
			
		}
				
		return $return;
	}
	
	private function getProjectsTypeObjectAmounts($arr_public_interface, $arr_projects) {
		
		$arr_amounts = [];
		
		foreach ((array)$arr_public_interface['projects'] as $public_interface_project_id => $arr_public_interface_project) {
			
			$arr_amounts[$public_interface_project_id]['project_total'] = 0;
				
			foreach ((array)$arr_public_interface['project_types'][$public_interface_project_id] as $public_interface_project_type_id => $arr_public_interface_project_types) {
				
				if (!$arr_public_interface_project_types['type_is_filter']) {
					
					$filter = new FilterTypeObjects($public_interface_project_type_id, GenerateTypeObjects::VIEW_NAME);			

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
	
	private function createViewText($id = false) {
		
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		
		$public_user_interface_active_custom_project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
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
				

				$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
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
		
		$return = '
					.ui { display: flex; flex-flow: column nowrap; align-content: flex-start; flex: 1 1 100%; position: relative; min-height: 100vh; background-color: #f3f3f3; }
					body.framed .ui { min-height: var(--view-height); }
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
					
					.ui > a.return { position: fixed; left: 0px; top: 0px; width: 60px; height: 60px; z-index: 1; }
					.ui > a.return > span { text-align: center; width: 100%;  }
					.ui > a.return > span > svg { height: 30%; }
					
					.ui > .header-info { position: relative; width: 100%; margin: 0; padding: 0; box-sizing: border-box; background-color: #555; }
					.ui > .header-info > h1 { max-width: 90vw; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0px; line-height: 60px; padding: 0 95px 0 70px; font-size: 20px; font-weight: bold; color: #fff; box-sizing: border-box; } 
					.ui > .header-info > h1:hover { text-decoration: none; } 
					.ui > .header-info > span { position: absolute; right: 0px; top: 0px; width: 60px; height: 100%; line-height: 60px; font-weight: bold; background-color: #0096e4; color: #fff; text-align: center; }
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
					.ui > .fixed-view-container { position: fixed; display: block; z-index: 15; top: 0; right: 0; left: 0; height: 100%; background-color: #efefef; }
					.ui > .fixed-view-container > div { position: absolute; left: 0; top: 0; right: 0; height: 100%;  overflow-y: scroll; }
										
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
					.ui > nav > ul li.projects-nav > span { display: none; }
					.ui > nav > ul li.projects-nav ul { width: 100%; height: 50px; background-color: #f3f3f3; color: #555; padding-left: 50px; box-sizing: border-box; white-space: nowrap; }
					.ui > nav > ul li.projects-nav ul li { position: relative; display: inline-block; height: 50px; cursor: pointer; box-sizing: border-box; }
					.ui > nav > ul li.projects-nav ul li span.project-name { display: table-cell; height: 50px; padding: 8px 20px; vertical-align: bottom; text-align: center; font-weight: bold; font-size: 14px; box-sizing: border-box; }
					.ui > nav > ul li.projects-nav ul li span.project-amount { position: absolute; top: 10px; right: -5px; display: block; padding: 0 5px; height: 15px; min-width: 20px; border-radius: 8px; background-color: #eaeaea; text-align: center; font-size: 10px; line-height: 15px; color: #aaa; }
					.ui > nav > ul li.projects-nav ul li:hover,
					.ui > nav > ul li.projects-nav ul li.active { border-bottom: solid #0096e4 4px; }
					.ui > nav > ul li.projects-nav ul li:hover span.project-amount,
					.ui > nav > ul li.projects-nav ul li.active span.project-amount { background-color: #ddd; color: #444; }
					
					.ui > nav > ul > li.project-dynamic-nav[data-set="true"] { display: none; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help { position: relative; background-color: #a3ce6c; width: 100%; }					
					.ui > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-help,
					.ui > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-scenarios,
					.ui > nav > ul > li.project-dynamic-nav[data-object_active="true"] ul li.project-types { display: none; }
					
					.ui > nav.left > ul li.projects-nav ul { padding-left: 5px; height: auto; white-space: normal; }
					.ui > nav.left > ul li.projects-nav ul li { height: auto; }
					
					
					@media all and (min-width : 1120px) {
						
						.ui > nav > ul.scroll { padding-top: 50px; }
						.ui > nav > ul.scroll li.projects-nav { position: absolute !important; top: 60px; left: 0px; right: 0px; height: 50px; }
						.ui > nav > ul.scroll li.projects-nav > span { display: block; position: absolute; top: 10px; bottom: 10px; z-index: 3; cursor: pointer; height: 30px; width: 30px; text-align: center; background-color: #aaa; color: #eaeaea; border-radius: 50px; }
						.ui > nav > ul.scroll li.projects-nav > span svg { height: 30%; }
						.ui > nav > ul.scroll li.projects-nav > span.prev { left: 10px; }
						.ui > nav > ul.scroll li.projects-nav > span.next { right: 10px; }
						.ui > nav > ul.scroll li.projects-nav ul { max-width: 100%; padding-left: 40px; overflow-x: scroll; scrollbar-width: none; -ms-overflow-style: none; }
						.ui > nav > ul.scroll li.projects-nav ul::-webkit-scrollbar { width: 0; height: 0; }
						.ui > nav > ul.scroll li.projects-nav ul li:last-child { margin-right: 40px; }
					
					}
					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help { display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help > div { background-color: #0096e4; padding: 10px 20px; margin: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 15px; line-height: 20px; font-weight: bold; color: #fff; }
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help > div:hover { background-color: #0096e4; color: #fff; text-decoration: none; }
					
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters + .project-types,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help + .project-types,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-filters + .project-scenarios,
					.ui > nav > ul > li.project-dynamic-nav ul li.project-help + .project-scenarios { margin-top: 50px; }
					
					.ui > nav > ul > li.project-dynamic-nav > ul > li:last-child:not(.project-filters) { margin-bottom: 20px; }
					
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
					
					.ui > nav.left { display: block; position: absolute; top: 60px; width: 20vw; overflow: visible; height: calc(100% - 60px); z-index: 2; }
					
					.ui > .project-dynamic-data { position: relative; flex: 1; display: flex; flex-wrap: nowrap; flex-direction: column; height: 100%; max-width: 100%; }
					.ui > nav.left + .project-dynamic-data {  padding-left: 20vw; box-sizing: border-box; }

					.ui > .project-dynamic-data > .tools { width: 100%; margin: 0; background-color: #a3ce6c; box-sizing: border-box;  }	
					.ui > .project-dynamic-data > .tools.no-filter { display: none; }
					.ui > .project-dynamic-data > .data { flex: 2; position: relative; display: flex; flex-wrap: nowrap; flex-direction: row; justify-content: center; height: 100%; max-width: 100%; }							
					.ui > .project-dynamic-data > .data > .objects { position: relative; flex: 3 1 100%; max-width: 100%; }							
					.ui > .project-dynamic-data > .data > .object { position: relative; flex: 2 1 100%; box-sizing: border-box; max-width: 40vw; }	
					
					.ui > .project-dynamic-data > .tools[data-object_active="true"] + .data > .objects[data-display_mode="list"] { max-width: 60vw; }							
					
					.ui > .project-dynamic-data > .tools[data-object_active="true"] + .data.fullscreen-object > .objects { display: none; }	
					
					.ui > .project-dynamic-data > .data.fullscreen-object > .object { position: relative;  min-width: 100%; padding: 30px; background-color: #f3f3f3; }
						
					.ui > .project-dynamic-data > .data > .objects:empty,
					.ui > .project-dynamic-data > .data > .object:empty { flex: 0; display: none; }	
					
					.ui > .project-dynamic-data > .data > .objects:empty + .object { max-width: 90vw; border-top: 4px solid #f3f3f3;}
					
					.ui > .project-dynamic-data > .data > .object-thumbnail-container { display: none; }
											
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
					
					.ui > .project-dynamic-data > .data > .objects > div.no-data ~ * { display: none !important; }
					.ui > .project-dynamic-data > .data > .objects > input { display: none; }
					.ui > .project-dynamic-data > .data > .objects > label { position: absolute; z-index: 1; cursor: pointer; }
					
					.ui > .project-dynamic-data > .data > .objects { display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; }
					
					.ui > .project-dynamic-data > .data > .objects > .overlay-grid { position: absolute; z-index: 1; }
					
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label { display: block; width: 160px; height: 30px; right: 20px; top: 260px; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label > span:first-child { display: block; width: 100%; line-height: 30px; text-align: center; background-color: #0096e4; color: #fff; letter-spacing: 2px; text-transform: uppercase; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label > span:last-child { display: none; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label + div.overlay-grid-next-prev { position: absolute; display: block; height: 30px; right: 20px; top: 20px; z-index: 1; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label + div.overlay-grid-next-prev > span { cursor: pointer; display: inline-block; text-align: center; height: 25px; width: 25px; color: #fff; background-color: #0096e4; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label + div.overlay-grid-next-prev > span:first-child { margin-right: 5px; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label + div + div.overlay-grid { width: 160px; height: 200px; top: 50px; right: 20px; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label + div + div.overlay-grid > .object-thumbnail { position: absolute; top: 20px; left: 10px; z-index: 1;}
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label + div + div.overlay-grid > .object-thumbnail:nth-of-type(2) { width: 150px; top: 10px; left: 5px; z-index: 2; }
					.ui > .project-dynamic-data > .data > .objects > input:not(:checked) + label + div + div.overlay-grid > .object-thumbnail:nth-of-type(1) { width: 160px; top: 0px; left: 0px; z-index: 3; }
					
					.ui > .project-dynamic-data > .data > .objects > input:checked + label { width: 60px; height: 60px; right: 20px; top: 20px; background-color: #fff; }
					.ui > .project-dynamic-data > .data > .objects > input:checked + label > span:first-child { display: none; }
					.ui > .project-dynamic-data > .data > .objects > input:checked + label > span:last-child {display: block; width: 100%; height: 100%; text-align: center; background-color: #0096e4; color: #fff;  }
					.ui > .project-dynamic-data > .data > .objects > input:checked + label + div { display: none; }
					.ui > .project-dynamic-data > .data > .objects > input:checked + label + div + div.overlay-grid {  left: 20px; top: 20px; right: 60px; display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; }

					.ui > .project-dynamic-data > .data > .objects[data-display_mode="grid"] { padding: 50px; }							
					.ui > .project-dynamic-data > .data > .objects .object-thumbnail { position: relative; display: inline-block; width: 140px; height: 170px; background-color: #ededed; margin: 0 55px 55px 0; border: 1px solid #d0d0d0; }
					.ui > .project-dynamic-data > .data > .objects .object-thumbnail .image { margin: 4px 4px 0 4px; width: calc(100% - 8px); height: 131px; background-repeat: no-repeat; background-position: center 10%; background-size: cover; }
					.ui > .project-dynamic-data > .data > .objects .object-thumbnail .image span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui > .project-dynamic-data > .data > .objects .object-thumbnail .name { position: absolute; bottom: 0px; width: 100%; min-height: 35px; max-height: 100%; display: flex; overflow: hidden; justify-content: center; align-items: center; box-sizing: border-box; background-color: #ededed; padding: 5px; text-align: center; vertical-align: middle; color: #000; }
					.ui > .project-dynamic-data > .data > .objects .object-thumbnail:hover { text-decoration: none; background-color: #0096e4; border-color: #0096e4; }
					.ui > .project-dynamic-data > .data > .objects .object-thumbnail:hover .image span,
					.ui > .project-dynamic-data > .data > .objects > .object-thumbnail:hover .name { color: #fff; background-color: #0096e4; }
					.ui > .project-dynamic-data > .data > .objects > button { display: block; width: 20%; margin: 20px 40% 20px 40%; text-align: center; background-color: rgba(255,255,255, 0.3); padding: 20px; box-sizing: border-box; border: 0; font-weight: bold; color: #444; }
					.ui > .project-dynamic-data > .data > .objects > button:hover { text-decoration: none; background-color: #0096e4; color: #fff; }

					.ui > .project-dynamic-data > .data > .objects > [data-visualisation_type] { position: absolute; top: 0; right: 0; bottom: 0; left: 0; width: 100%; flex: 2; height: 100%; }
					
					.ui > .project-dynamic-data > .data > .objects > .tabs.list-view { position: relative; margin: 5px; width: 100%; max-width: 98vw; }	
					.ui > nav.left + .project-dynamic-data  > .data > .objects > .tabs.list-view { max-width: 78vw; }	
					
					.ui > .project-dynamic-data .tabs.list-view > ul > li { background-color: #f5f5f5; border: 0; border-radius: 0; padding: 5px 10px; background-image: none; clip-path: none; -webkit-clip-path: none;  }
					.ui > .project-dynamic-data .tabs.list-view > ul > li.selected { background-color: #ddd; }
					.ui > .project-dynamic-data .tabs.list-view > ul > li a { color: #444; }
					.ui > .project-dynamic-data .tabs.list-view > div { position: relative; padding: 0; border: 0; }	
					.ui > .project-dynamic-data .tabs.list-view > div > div { position: relative; width: 100%; }	
					.ui > .project-dynamic-data .tabs.list-view > div > div div.options { background-color: #ddd; }	
					
					.ui > .project-dynamic-data > .data > .object { background-color: #eee; }
					.ui > .project-dynamic-data > .data > .object > div { position: relative; background-color: #eee; padding-bottom: 20px; height: auto; }
					
					.ui > .project-dynamic-data > .data.fullscreen-object > .object.show-explore-visualisations { padding: 0px; }
					.ui > .project-dynamic-data > .data > .object.show-explore-visualisations > div.has-explore-visualisations { display: flex; }
					.ui > .project-dynamic-data > .data > .object.show-explore-visualisations > div.has-explore-visualisations > div { position: relative; flex: 2 1 100%; box-sizing: border-box;  }
					.ui > .project-dynamic-data > .data > .object.show-explore-visualisations > div.has-explore-visualisations > div:first-child { margin: 0px; }
					.ui > .project-dynamic-data > .data > .objects:empty + .object.show-explore-visualisations { border: 0px; }
					
					.ui > .project-dynamic-data > .data > .object > div .tabs { margin: 15px; }
					.ui > .project-dynamic-data > .data > .object > div > div:not(.tabs) > ul { padding: 12px;  }
					
					.ui .head { margin: 0px; position: relative; background-color: #777; display: flex; justify-content: space-between; width: 100%; }	
					.ui .head > .object-thumbnail-image { display: block; margin: 0; padding: 0; height: 60px; width: 60px; min-width: 60px; background-repeat: no-repeat; background-position: center 10%; background-size: cover;  }	
					.ui .head > h1 { position: relative; flex-grow: 2; color: #efefef; margin: 0; min-height: 60px; line-height: 35px; font-size: 20px; padding: 12px 20px; box-sizing: border-box; }	
					.ui .head > .navigation-buttons { position: relative; white-space: nowrap; }
					.ui .head > .navigation-buttons > button { border: 0; width: 60px; height: 60px; border-radius: 0; margin: 0; padding: 0; background-color: #555; display: inline-block;}
					.ui .head > .navigation-buttons > button > span { color: #fff; }	
	
					.ui > .project-dynamic-data > .data > .object > div menu.buttons { width: auto; display: block; position: absolute; right: 20px; }	
					.ui > .project-dynamic-data > .data > .object > div > div > menu.buttons { top: 80px; }	
					
					.ui > .project-dynamic-data > .data > .object .combined-filters { position: relative; display: flex; flex-wrap: wrap; align-content: flex-start; }	
					.ui > .project-dynamic-data > .data > .object .combined-filters > div { position: relative;  display: flex; flex-wrap: nowrap; padding: 5px; margin: 0 15px 10px 0; background-color: #fff; }	
					.ui > .project-dynamic-data > .data > .object .combined-filters > div > input { margin-right: 5px; }	
					.ui > .project-dynamic-data > .data > .object .combined-filters > div > * { padding: 5px; }	

					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul > li { background-color: transparent; border: 0; background-image: none; border-radius: 0; padding: 0; clip-path: none; -webkit-clip-path: none; }
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul > li.selected { border: 0; }
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul > li.selected a { background-color: rgba(255,255,255,0.7);}
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul > li.no-data a { background-color: rgba(255,255,255,0.35); pointer-events: none; }
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul > li > a { position: relative; font-size: 14px; padding: 10px; background-color: #aaa; margin-right: 10px; }
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul.big > li > a { margin-bottom: 8px; }
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul > li > a > span { line-height: 14px; }
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > ul > li > a > span.amount {  position: absolute; top: -10px; right: -15px; display: block; padding: 0 5px; height: 15px; min-width: 20px; border-radius: 8px; background-color: #0096e4; text-align: center; font-size: 10px; line-height: 15px; color: #fff; }
					.ui > .project-dynamic-data > .data > .object .tabs.object-view > div { margin-top: 1px; background-color: rgba(255,255,255,0.7); border: 0; }

					.ui > .project-dynamic-data > .data > .object ul::after { content: " "; display: block; height: 0; clear: both; }
					
					.ui > .project-dynamic-data > .data > .object ul > li.media > span { margin: 0 10px 10px 0; box-sizing: border-box; display: inline-block; }
					.ui > .project-dynamic-data > .data > .object ul > li.media > span object,
					.ui > .project-dynamic-data > .data > .object ul > li.media > span iframe { width: 600px; height: 600px; }
					.ui > .project-dynamic-data > .data > .object ul > li.media > span img,
					.ui > .project-dynamic-data > .data > .object ul > li.media > span video,
					.ui > .project-dynamic-data > .data > .object ul > li.media > span object,
					.ui > .project-dynamic-data > .data > .object ul > li.media > span iframe { max-height: 40vh; max-width: 100%; }
					.ui > .project-dynamic-data > .data > .object ul > li.related-media { display: flex; flex-wrap: wrap; }
					.ui > .project-dynamic-data > .data.fullscreen-object > .object ul > li.related-media { float: right; clear: left; margin-top: 50px; width: 340px; padding: 10px 0 0 10px; justify-content: flex-end;}
					.ui > .project-dynamic-data > .data > .object ul > li.related-media > div { width: 150px; height: 150px; display: inline-block; margin: 0 10px 10px 0; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > .project-dynamic-data > .data > .object ul > li.related-media > div > span { width: 100%; text-align: center; color: #fff; }
					.ui > .project-dynamic-data > .data > .object ul > li.related-media > div > span > svg { height: 25%; }
					.ui > .project-dynamic-data > .data > .object ul > li.keywords span { display: inline-block; padding: 10px; margin: 0 10px 10px 0;}
					.ui > .project-dynamic-data > .data > .object ul > li.keywords span:hover { color: #fff; background-color: #0096e4; text-decoration: none; }
					
					.ui > .project-dynamic-data > .data > .object ul li dl { display: table; border-spacing: 0px 8px; }
					.ui > .project-dynamic-data > .data > .object ul li dl li { display: table-row;  }
					.ui > .project-dynamic-data > .data > .object ul li dt { display: table-cell; padding-right: 10px; font-family: var(--font-mono); vertical-align: middle; }
					.ui > .project-dynamic-data > .data > .object ul li dd { display: table-cell; }
					.ui > .project-dynamic-data > .data > .object ul li dd > span.a { display: inline-block; border-bottom: 1px #444 dashed; margin-right: 10px; padding: 2px 3px; margin-bottom: 3px; } 
					.ui > .project-dynamic-data > .data > .object ul li dd > span.a:hover { text-decoration: none; background-color: #0096e4; color: #fff; }

					.ui > .project-dynamic-data > .data > .object ul li.text_tags dd div + p { display: none;}
					.ui > .project-dynamic-data > .data > .object ul li.text_tags dd > div { background-color: #fff; padding: 10px; }
					.ui > .project-dynamic-data > .data > .object ul li.text_tags dd > div > ul { display: none;}
					.ui > .project-dynamic-data > .data > .object ul li.text_tags dd > div.tabs > div { padding: 0px; background-color: #fff; border: 0px;}
					.ui > .project-dynamic-data > .data > .object ul li.text_tags dd span.tag { white-space: nowrap; }
					.ui > .project-dynamic-data > .data > .object ul li.external dd { max-width: 80%; }
					.ui > .project-dynamic-data > .data > .object ul li.external dd > a { display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; padding: 4px 40px 4px 10px; margin: 0 3px 3px 0; font-size: 1.4rem; color: #444444; background: url("/CMS/css/images/icons/linked.svg") no-repeat scroll right 15px center / 10px 10px #efefef;}
					.ui > .project-dynamic-data > .data > .object ul li.external dd > a:hover { color: #fff; text-decoration: none; background-color: #0096e4; }
					
					.ui > .project-dynamic-data > .data > .object ul li dd .album {  }
					.ui > .project-dynamic-data > .data > .object ul li dd .album > figure { display: inline-block; margin: 16px 24px 0 0; padding: 0; width: 235px; height: 147px; }
					.ui > .project-dynamic-data > .data > .object ul li dd .album > figure div > img { width: 235px; height: 147px; object-fit: cover; }
					.ui > .project-dynamic-data > .data > .object ul li dd .album > figure > figurecaption { display: none; }
					
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs .tabs { max-width: 35vw; }
					
					.ui > .project-dynamic-data > .data.fullscreen-object > .object ul > li.object-subs .tabs,
					.ui > .project-dynamic-data > .data > .objects:empty + .object ul > li.object-subs .tabs { max-width: 80vw; }
					
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs > .tabs { max-width: 35vw; margin: 10px 0;}
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs > .tabs > ul > li,
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs > .tabs > ul > li.selected { clip-path: none; -webkit-clip-path: none; background-image: none; border: 0; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs > .tabs > ul > li { background-color: #aaa; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs > .tabs > ul > li.selected { background-color: #fff; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs > .tabs > div { border: 0; }
					.ui > .project-dynamic-data > .data > .object ul > li.object-subs > .tabs table.display td { /* white-space: normal; overflow: auto; text-overflow: unset; max-width: 35vw; */ }

					.ui > .project-dynamic-data > .data .object .object-thumbnail { width: calc(100% - 30px); height: 50px; background-color: #fff; margin: 15px; box-sizing: border-box; }
					.ui > .project-dynamic-data > .data .object-thumbnail-container .object-thumbnail > div,
					.ui > .project-dynamic-data > .data .object .object-thumbnail > div { display: flex; height: 100%; overflow: hidden; }
					.ui > .project-dynamic-data > .data .object-thumbnail-container .image,
					.ui > .project-dynamic-data > .data .object .object-thumbnail .image { display: inline-block; width: 50px; height: 100%; background-repeat: no-repeat; background-position: center 10%; background-size: cover; background-color: #bbb; }
					.ui > .project-dynamic-data > .data .object-thumbnail-container .image span,
					.ui > .project-dynamic-data > .data .object .object-thumbnail .image  span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui > .project-dynamic-data > .data .object-thumbnail-container .name,
					.ui > .project-dynamic-data > .data .object .object-thumbnail .name { width: calc(100% - 50px); max-width: 500px; height: 100%; color: #000; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 16px; vertical-align: middle; margin: 0; padding: 0; padding-left: 20px; box-sizing: border-box;}
					.ui > .project-dynamic-data > .data .object-thumbnail-container .name span,					
					.ui > .project-dynamic-data > .data .object .object-thumbnail .name span { line-height: 50px; }
					.ui > .project-dynamic-data > .data .object-thumbnail:hover,
					.ui > .project-dynamic-data > .data .object .object-thumbnail:hover { text-decoration: none; color: #fff; }
					.ui > .project-dynamic-data > .data .object-thumbnail .object-definitions,
					.ui > .project-dynamic-data > .data .object .object-thumbnail .object-definitions { display: none; }
					
					.ui > .project-dynamic-data > .data > .object .explore-object { background-color: #efefef; padding: 20px; } 
					.ui > .project-dynamic-data > .data > .object .explore-object > div { height: calc(var(--view-height) - 300px);  } 
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
					.ui .hover .object-definitions { display: none; }
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
						
						.ui.responsive-layout-enabled > nav > ul li.projects-nav ul { width: 100%; min-height: 50px; height: auto; background-color: rgba(255,255,255,0.5); padding: 10px 10px 0 10px; box-sizing: border-box; white-space: normal; }
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
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters .filters-container .filter-container { padding: 0; margin: 0; flex: 1 1 auto; width: 100%; max-width: 100%; display: block; box-sizing: border-box; height: auto;}
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
											
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > label,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > input { display: none; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container { box-sizing: border-box; flex-direction: column; margin: 0; padding: 0;  }
						
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date { flex: 0; background-color: #a3ce6c;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container:first-child { }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar { display: flex; background: none; padding: 0; margin: 0; height: auto; white-space: normal; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-active + label { display: none; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-input { order: 2; height: 40px; margin: 0; padding: 0 0 0 40px; background: url("/CMS/css/images/icons/search.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-input input { width: 100%; box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 14px; height: 40px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-active { order: 3; background: none; padding: 0; margin: 0; display: inline-block; height: auto; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-active:not(:empty) { margin: 5px 0 0 0; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-active > div { width: auto; margin: 0 5px 5px 0px; background-color: #eee; padding: 0 0 0 5px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-active > div span:first-child { width: auto; color: #333; font-size: 14px; padding: 0 4px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-active > div span.icon { background-color: #ddd; color: #444; padding: 0 6px; border: 0;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > input { display: none; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > .filter-active + label + input + label { display: inline-block; border: 2px solid #0096e4; order: 4; padding: 0; height: 23px; cursor: pointer; white-space: nowrap; margin-top: 5px; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > input + label > span { display: inline-block; font-weight: bold; font-size: 10px; padding: 5px; color: #333; box-sizing: border-box; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6); transition: background-color 100ms ease;   }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > input:not(:checked) + label > span:first-child {  background-color: #0096e4; color: #fff;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .filter-container .filter-search-bar > input:checked + label > span:last-child {  background-color: #0096e4; color: #fff; }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .results { margin-top: 0px; background-color: rgba(255, 255, 255, 0.6); }

						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date { display: flex; white-space: nowrap; flex-wrap: nowrap; align-content: flex-start; margin-top: 10px;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input { box-shadow: none; display: inline-block; width: auto; max-width: 150px; font-size: 14px; height: 40px; margin: 0; padding: 0 0 0 40px; border: 0; background: url("/CMS/css/images/icons/date.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(1) { order: 2;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(2) { order: 4;  }
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(1) + label { order: 1; margin-left: 0px; } 
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(1) + label,
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(2) + label { height: 40px; font-weight: bold; letter-spacing: 2px; font-size: 12px; padding: 0 0 0 10px; line-height: 40px; box-sizing: border-box; color: #333; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6);  } 
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(2) + label { order: 3;  } 
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(3) { display: none;  } 
						.ui.responsive-layout-enabled > nav > ul > li.project-dynamic-nav ul li.project-filters.form > .filters-container .date > input:nth-of-type(3) + label { display: none; } 
						
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
						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .object { border-top: 4px solid #f3f3f3; max-width: 100vw; padding: 0; }																																
						.ui > .project-dynamic-data > .data > .object-thumbnail-container { display: flex; position: fixed; right: 0; left: 0; bottom: 0; height: 80px; z-index: 3; background-color: #eee; padding: 15px; box-sizing: border-box; }																
						.ui > .project-dynamic-data > .data > .object-thumbnail-container:empty { display: none; }		

						.ui.responsive-layout-enabled > .project-dynamic-data > .data > .object ul > li.object-subs .tabs { max-width: 90vw; }

						.ui > .project-dynamic-data > .data > .object-thumbnail-container > div { width: calc(100% - 60px); display: flex; box-sizing: border-box; height: 50px; background-color: #fff; }							
						.ui > .project-dynamic-data > .data > .object-thumbnail-container button { margin-left: 10px; display: inline-block; width: 50px; height: 50px; background-color: #ddd; color: #444; line-height: 25px; text-align: center; vertical-align: middle;  border: 0;}		

						.ui.responsive-layout-enabled > .project-dynamic-data > .data div.head > h1 { font-size: 17px; }		

						.ui.responsive-layout-enabled > .project-dynamic-data > .data .explore-object > div { height: calc(var(--view-height) - 100px);  } 	
						
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
						.ui.responsive-layout-enabled > .project-dynamic-data > .data.fullscreen-object > .object ul > li.related-media { float: none; width: auto; margin: 0; padding: 0; justify-content: start; }
						
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

						elm_scripter.find('.project-dynamic-nav').attr('data-object_active', false);
					
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
						
						var str_title = document.title;
						var arr_title = str_title.split(' | ');
						
						if (arr_title.length > 2 || arr_title.length == 1) {
							str_title = arr_title[0];
						} else if (arr_title.length == 2) {
							str_title = arr_title[1];
						} 
						
						document.title = str_title;
						elm_scripter.closest('html').find('meta[property=og\\\:title]').attr('content', str_title);

						setTimeout(function(){
							elm_scripter.find('#nav-toggle').prop('checked', false);
						},1000); 
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
					
					if (elm_scripter.find('.projects-nav > ul').length) {
					
						var elm_projects_nav = elm_scripter.find('.projects-nav');
						var elm_projects_nav_container = elm_projects_nav.parent();
						var elm_projects_nav_ul = elm_projects_nav.find('ul');
						var projects_nav_ul_width = 100;
						var elm_prev = elm_projects_nav.find('span.prev').addClass('hide');
						var elm_next = elm_projects_nav.find('span.next');
						
						elm_projects_nav_ul.children().each(function() {
							projects_nav_ul_width += $(this).width();
						});

						elm_prev.on('click', function() {
							moveScroll(elm_prev, {elm_con: elm_projects_nav_ul});
							elm_prev.addClass('hide');
							elm_next.removeClass('hide');
						});
						elm_next.on('click', function() {
							moveScroll(elm_next, {elm_con: elm_projects_nav_ul});
							elm_next.addClass('hide');
							elm_prev.removeClass('hide');
						});
						var func_nav_width_check = function() {

							if (projects_nav_ul_width > window.innerWidth) {
								elm_projects_nav_container.addClass('scroll');
							} else {
								elm_projects_nav_container.removeClass('scroll');
							}
						}

						func_nav_width_check();
						new ResizeSensor(elm_projects_nav[0], func_nav_width_check);
						elm_projects_nav_ul[0].scrollLeft -= projects_nav_ul_width;
					}
				});				
				
				SCRIPTER.dynamic('[data-method=view_elm]', function(elm_scripter) {

					elm_scripter.on('click', 'button.close', function() {
						elm_scripter.remove();
					});
				});

				SCRIPTER.dynamic('[data-method=set_project]', function(elm_scripter) {
				
					var elm_ui = elm_scripter.closest('.ui');
					
					elm_scripter.find('[id^=y\\\:ui\\\:view_text-]').each(function() {
						COMMANDS.setTarget($(this), elm_ui.find('div.fixed-view-container'));
					});
					
				});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 
		
		if ($method == "set_project") {

			$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');

			toolbar::setScenario();
			toolbar::setFilter([]);
			
			SiteEndVars::setFeedback('selected_type_ids', false, true);
			SiteEndVars::setFeedback('type_id', false, true);
			SiteEndVars::setFeedback('scenario_id', false, true);
			SiteEndVars::setFeedback('active_type_object_id', false, true);
			SiteEndVars::setFeedback('arr_public_user_interface_module_vars', false, true);
			
			if ($id == 0) {

				$id = cms_nodegoat_public_interfaces::getPublicInterfaceProjectIDs($public_user_interface_id, 1);
			}
			
			self::setPublicUserInterfaceActiveCustomProjectId($id);
			self::setPublicUserInterfaceModuleVars(false);
			
			$this->html = [self::createProjectNavigation(), self::getProjectDynamicDataElm()];

		}

		if ($method == "view_text") {

			$this->html = self::createViewText($id);
		}

		if ($method == "handle_dynamic_project_data") {

			$create_data = new ui_data();
	
			$this->html = $create_data->getDynamicProjectDataTools();
		}

		if ($method == "set_type" || $method == "set_scenario") {

			$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
			
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
		
	}

	public static function isTypeEnabled($type_id, $mode) {
		
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$enabled = false;
		
		if ($mode == false) {
			
			$arr_types = StoreType::getTypes();
			
			if ($arr_types[$type_id]) {
				$enabled = true;
			}
			
		} else if ($mode == 'project') {
			
			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $project_id, false);
			
			if ($arr_public_interface_project_types[$type_id]) {
				$enabled = true;
			}
			
		} else if ($mode == 'filter') {
			
			$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIDs($public_user_interface_id, $project_id, true);
			
			if ($arr_public_interface_project_filter_types[$type_id]) {
				$enabled = true;
			}
		}
		
		return $enabled;
	}
				
	public static function setPublicUserInterfaceModuleVars($arr = ['set' => false, 'id' => false, 'display_mode' => 'grid', 'start' => false]) {

		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');	
		
		if ($arr) {
			
			$arr_request_vars = SiteStartVars::getModuleVariables(0);
			
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

		SiteEndVars::setModuleVariables(0, $arr_public_user_interface_module_vars);
	}
	
	public static function getPublicUserInterfaceModuleVars() {

		self::setPublicUserInterfaceId();
		self::setPublicUserInterfaceActiveCustomProjectId();
		
		$arr_request_vars = SiteStartVars::getModuleVariables(0);

		if ($arr_request_vars[2]) {

			$arr_vars = ['set' => $arr_request_vars[2], 'id' => $arr_request_vars[3], 'display_mode' => ($arr_request_vars[4] ? (strpos($arr_request_vars[4], 'soc') !== false ? substr($arr_request_vars[4], 0, 3) : $arr_request_vars[4]) : false), 'start' => $arr_request_vars[5]];
			SiteEndVars::setFeedback('arr_public_user_interface_module_vars', $arr_vars, true);
			
		} else {
			
			SiteEndVars::setFeedback('arr_public_user_interface_module_vars', [], true);
		}
		
		if ($arr_vars['set'] == 'scenario' && $arr_vars['id']) {

			toolbar::setScenario($arr_vars['id']);
			
		} else { 

			toolbar::setScenario();
		}
		
		if ($arr_vars['set'] == 'types') {

			SiteEndVars::setFeedback('types', 0, true);
			
		} else { 

			SiteEndVars::setFeedback('types', false, true);
		}

		if ($arr_vars['set'] == 'filter' && $arr_vars['id']) {
			
			$arr_url_filter = ui_filter::parseFilterString($arr_vars['id']);
			toolbar::setFilter(ui_filter::createFilterArray($arr_url_filter));
		}
		
		if (($arr_vars['set'] == 'object' || $arr_vars['set'] == 'object-print') && $arr_vars['id']) {
			
			SiteEndVars::setFeedback('active_type_object_id', $arr_vars['id'], true);
		}
		
		if (($arr_vars['set'] == 'selection' || $arr_vars['set'] == 'selection-print') && $arr_vars['id']) {
			
			SiteEndVars::setFeedback('active_selection_id', $arr_vars['id'], true);
		}
	}
		
	public static function setPublicUserInterfaceId() {
		
		if (!SiteStartVars::getFeedback('public_user_interface_id')) {
			
			if (SiteStartVars::getModuleVariables(0)[0]) {
				
				$url_public_user_interface_id = SiteStartVars::getModuleVariables(0)[0];
			
				if (cms_nodegoat_public_interfaces::getPublicInterfaces($url_public_user_interface_id)) {
					$public_user_interface_id = $url_public_user_interface_id;
				}
			}
			
			if (!$public_user_interface_id) {
				
				$public_user_interface_id = cms_nodegoat_public_interfaces::getDefaultPublicInterfaceID();
			}					

			SiteEndVars::setFeedback('public_user_interface_id', $public_user_interface_id, true);
		}
	}
	
	public static function setPublicUserInterfaceActiveCustomProjectId($id = false) {
		
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		$url_project_id = SiteStartVars::getModuleVariables(0)[1];
		
		if ((int)$id) {
			
			$public_user_interface_active_custom_project_id = $id;
			
		} else if ($url_project_id && $arr_public_user_interface['projects'][$url_project_id]) {
			
			$public_user_interface_active_custom_project_id = $url_project_id;
			
		} else if (SiteStartVars::getFeedback('public_user_interface_id')) {
			
			$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = cms_nodegoat_public_interfaces::getPublicInterfaceProjectIDs($public_user_interface_id, 1);
		}
		
		if ($public_user_interface_active_custom_project_id) {
			
			$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
			
			if (!$arr_project) {
				$public_user_interface_active_custom_project_id = false;
			}
		}
				
		if (!$public_user_interface_active_custom_project_id) {
			error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
		}

		$_SESSION['custom_projects']['project_id'] = $public_user_interface_active_custom_project_id;

		SiteEndVars::setFeedback('public_user_interface_active_custom_project_id', $public_user_interface_active_custom_project_id, true);
		
	}
}
