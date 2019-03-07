<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ui_data extends base_module {
	
	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
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
		'ui' => [],
		'ui_selection' => []
	];
		
	public function getDynamicProjectDataTools($data_display_mode = false, $explore_object = false) {
	
		$arr_data_options = cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions();
		
		if ($explore_object) {
			return  '<div data-method="handle_dynamic_project_data" class="explore-object"><div id="y:ui_data:get_visulisation_data-'.$explore_object.'_'.$data_display_mode.'" data-visualisation_type="'.$arr_data_options[$data_display_mode]['visualisation_type'].'"></div></div>';
		}
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	

		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');

		$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);		
			
		$scenario_id = SiteStartVars::getFeedback('scenario_id');

		if ($scenario_id) {
				
			$arr_scenario = $arr_public_user_interface['project_scenarios'][$public_user_interface_active_custom_project_id][$scenario_id];
			
			if ($arr_scenario) {
			
				toolbar::setScenario($scenario_id);
				
				if (!$data_display_mode && !$arr_public_user_interface_module_vars['display_mode']) {
	
					foreach ((array)$arr_scenario as $key => $value) {
						
						$arr_key = explode('_', $key);
						$data_option = $arr_key[1];
						
						if ($arr_data_options[$data_option] && $value) {

							$data_display_mode = $data_option;
							
							break;
						}
					}
				}
				
				$arr_type_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($public_user_interface_active_custom_project_id, false, false, $scenario_id); 
				
				$elm_active_set = '<span '.($arr_type_scenario['description'] ? 'class="set a quick" id="y:ui:view_text-scenario_'.$scenario_id.'"' : 'class="set"').'>
										'.($arr_type_scenario['description'] ? ($arr_public_interface_settings['labels']['info'] ? '<span>('.Labels::parseLanguage($arr_public_interface_settings['labels']['info']).')</span>' : '<span class="icon" id="y:ui:view_text-scenario_'.$scenario_id.'">'.getIcon('info-point').'</span>') : '').'
										<span class="name">'.htmlspecialchars(Labels::parseTextVariables($arr_type_scenario['name'])).'</span>
									</span>';
			} else {
				
				toolbar::setScenario();
				
			}

		} else {
		
			$type_id = SiteStartVars::getFeedback('type_id');
			
			if ($type_id) {
			
				$arr_type_set = StoreType::getTypeSet($type_id);
				
				$elm_active_set = '<span class="set">
										<span class="name">'.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])).'</span>
									</span>';
								
				$arr_type = $arr_public_user_interface['project_types'][$public_user_interface_active_custom_project_id][$type_id];
			
			} else {
				
				$arr_type = $arr_public_user_interface['project_types'][$public_user_interface_active_custom_project_id][current($arr_public_interface_project_types)];
			}
			

			if (!$data_display_mode && !$arr_public_user_interface_module_vars['display_mode']) {

				foreach ((array)$arr_type as $key => $value) {
					
					$arr_key = explode('_', $key);
					$data_option = $arr_key[1];
					
					if ($arr_data_options[$data_option] && $value) {

						$data_display_mode = $data_option;
						
						break;
					}
				}
			}
			
		}
			
		$filter_is_active = ui::isFilterActive(true);

		$options = [];
	
		// if no filter is set, show 'start elements'
		if ($filter_is_active === false) {
			
			$start = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['set'];
			
			if ($start == 'random') {
				
				$data_display_mode = 'grid';
				$options['random'] = 1;

			} else if ($start == 'scenario') {
			
				$start_scenario_id = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['scenario']['id'];
			
				if ($start_scenario_id) {
					
					$start_scenario_display_mode = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['scenario']['display_mode'];
					
					toolbar::setScenario($start_scenario_id);	
					SiteEndVars::setFeedback('scenario_id', $start_scenario_id, true);
			
					if ($start_scenario_display_mode) {
						$data_display_mode = $start_scenario_display_mode;
					}
					
					ui::setPublicUserInterfaceModuleVars(['set' => 'scenario', 'id' => $start_scenario_id, 'display_mode' => $start_scenario_display_mode]);
					
				}
				
			} else if ($start == 'type') {
				
				$start_type_id = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['type']['id'];
				
				if ($start_type_id) {
					
					$start_type_display_mode = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['type']['display_mode'];
					
					SiteEndVars::setFeedback('type_id', $start_type_id, true);
			
					if ($start_type_display_mode) {
						$data_display_mode = $start_type_display_mode;
					}
					
					ui::setPublicUserInterfaceModuleVars(['set' => 'type', 'id' => $start_type_id, 'display_mode' => $start_type_display_mode]);
					
				}
			}
			
		}
		
		if (!$data_display_mode && $arr_public_user_interface_module_vars['display_mode']) {
			
			$data_display_mode = $arr_public_user_interface_module_vars['display_mode'];

		} 
	
		if (!$data_display_mode) {
			
			$data_display_mode = 'grid';
		}
		
		$return = '<div data-method="handle_dynamic_project_data" '.($start ? 'class="hide"' : '').'>';
		
		if ($elm_active_set) {
			$result_info_elm = $elm_active_set;
		}
		
		if ($filter_is_active > 1) {
			$result_info_elm .= '<span class="amount">
						'.($elm_active_set ? '(' : '').nr2String($filter_is_active).' '.getLabel('lbl_objects').($elm_active_set ? ')' : '').'
					</span>';
		}
		
		$return .= '<div class="result-info">'.$result_info_elm.'</div>';
		
		$visualisation_buttons = [];
		
		foreach ($arr_data_options as $key => $arr_data_option) {

			if ($arr_scenario && !$arr_scenario['scenario_'.$key]) {
				
				continue;
			}
			
			if ($arr_type && !$arr_type['type_'.$key]) {
				
				continue;
			}
			
			$visualisation_buttons[] = '<span class="a '.($data_display_mode == $key ? 'active' : '').'" data-display_mode="'.$key.'" data-visualisation_type="'.$arr_data_option['visualisation_type'].'" id="y:ui_data:set_data_display_mode-'. $key.'" >
							<span>'.$arr_data_option['name'].'</span>
							<span class="icon">'.getIcon($arr_data_option['icon']).'</span>
						</span>';
		}
		
		if (count((array)$visualisation_buttons) > 1) {
			$return .= '<div class="visualisation-buttons">'.implode('', $visualisation_buttons).'</div>';
		}
		
		$return .= '<div class="controls">
						<span class="a clear" id="y:ui:set_project-'.$public_user_interface_active_custom_project_id.'">
							<span>'.getLabel('lbl_start_over').'</span>
							<span class="icon">'.getIcon('reload').'</span>
						</span>
					</div>';

		if ($arr_public_user_interface_module_vars['set'] != 'object') {

			$arr_data = $this->createViewTypeObjects($data_display_mode, false, $options);
			$elm_data = $arr_data['html'];
			unset($arr_data['html']);
			
			if ($start) {
				unset($arr_data['set']);
			}

			$arr_data['filtered'] = ($filter_is_active === false ? false : true);
		
			$return .= '<div class="new-data" data-options="'.htmlspecialchars(json_encode($arr_data)).'">'.$elm_data.'</div>';		
		}
			
		$return .= '</div>';

		return $return;

	}
	
	private function createViewTypeObjects($data_display_mode = false, $arr_filter = false, $options = []) {

		$arr = [];
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
		
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
	
		if (SiteStartVars::getFeedback('scenario_id') || SiteStartVars::getFeedback('type_id')) {
			
			$arr['set'] = true;
		}
	
		$scenario_id = SiteStartVars::getFeedback('scenario_id');

		if ($scenario_id) {
			
			toolbar::setScenario($scenario_id);	
			$arr_type_filters = toolbar::getFilter();
			$arr_public_interface_project_types = [key($arr_type_filters) => key($arr_type_filters)];
				
		}
		
		$arr_visualisation_options = cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions();
		
		$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		
		if (!$data_display_mode && $arr_public_user_interface_module_vars['display_mode']) {
			
			$data_display_mode = $arr_public_user_interface_module_vars['display_mode'];
		}
		
		if (!$data_display_mode) {
			
			$data_display_mode = 'grid';
		}
			
		if ($arr_public_user_interface_module_vars['set'] && !$arr_public_user_interface_module_vars['display_mode']) {
		
			ui::setPublicUserInterfaceModuleVars(['display_mode' => $data_display_mode]);
		}
		
		$arr['display_mode'] = $data_display_mode;
		
		if ($data_display_mode == 'grid') {

			$arr['html'] = '<button class="a quick" id="y:ui_data:load_grid_data-0" data-min="0" data-max="50" data-random="'.($options['random'] ? 1 : 0).'">'.getLabel('lbl_load_more').'</button>';

		} else if ($data_display_mode == 'list') {
			
			$arr_active_filters = toolbar::getFilter();
			
			foreach ((array)$arr_public_interface_project_types as $type_id => $value) {
				
				if (SiteStartVars::getFeedback('type_id') && $type_id != SiteStartVars::getFeedback('type_id')) {
					
					continue;
				}

				if ($arr_active_filters[$type_id]) {
					
					$arr_type_filter = $arr_active_filters[$type_id];
					
				} else if ($type_id != SiteStartVars::getFeedback('type_id')) {
					
					continue;
				}
			
				$filter = new FilterTypeObjects($type_id, 'id');
				$filter->setFilter($arr_type_filter);
				$arr_info = $filter->getResultInfo();
					
				if ($arr_info['total_filtered']) {
					
					$arr_type_set = StoreType::getTypeSet($type_id);
					
					$arr_tag_tabs['links'][$type_id] = '<li><a href="#">'.Labels::parseTextVariables($arr_type_set['type']['name']).' ('.$arr_info['total_filtered'].')</a></li>';
		
					$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
				
					$arr_tag_tabs['content'][$type_id] = '<div>'.$this->createViewTypeObjectsList($type_id, $arr_type_filter, false).'</div>';
				}
			}
			
			if (count((array)$arr_tag_tabs['links'])) {
				
				if (count((array)$arr_tag_tabs['links']) > 1) {
					
					$arr['html'] .= '<div class="tabs list-view">
						<ul>
							'.implode('', $arr_tag_tabs['links']).'
						</ul>
						'.implode('', $arr_tag_tabs['content']).'
					</div>';
					
				} else {
					
					$arr['html'] .= '<div class="tabs list-view"><ul></ul>'.implode('', $arr_tag_tabs['content']).'</div>';
				}
			}
			
		} else { // visualise

			$arr['html'] .= '<div data-visualisation_type="'.$arr_visualisation_options[$data_display_mode]['visualisation_type'].'" id="y:ui_data:get_visulisation_data-0"></div>'; 

		}

		return $arr;
	}

	private function createVisualisation($explore_object_id) {

		$arr = [];
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		$data_display_mode = $arr_public_user_interface_module_vars['display_mode'];
				
		if ($explore_object_id) {
			
			$arr_id = explode('_', $explore_object_id);
			$type_id = $arr_id[0];
			$object_id = $arr_id[1];
			$data_display_mode = $arr_id[2];
			
			$arr_filters = ['objects' => $object_id];
			
			$use_custom_project_id = self::checkCustomProjectProjectId($type_id);
			
			if ($use_custom_project_id) {
				
				$public_user_interface_active_custom_project_id = $use_custom_project_id;
				$_SESSION['custom_projects']['project_id'] = $use_custom_project_id;
				$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
			}
	
			$explore_scope_id = (is_array($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope'][$type_id]['explore']) ? $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope'][$type_id]['explore'][$data_display_mode] : false);

			if ($explore_scope_id) {
				
				SiteEndVars::setFeedback('scope_id', $explore_scope_id, true);
			}

			
		} else {


			if (SiteStartVars::getFeedback('scenario_id')) {
				
				$scenario_id = SiteStartVars::getFeedback('scenario_id');
				toolbar::setScenario($scenario_id);	
				
			} else if (SiteStartVars::getFeedback('type_id')) {
				
				$type_id = SiteStartVars::getFeedback('type_id');
				
			}

			$arr_type_filters = toolbar::getFilter();

			if (!$type_id) {
				
				$type_id = key($arr_type_filters);
			}
			
			$arr_filters = current($arr_type_filters);
			$browse_scope_id = (is_array($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope'][$type_id]['browse']) ? $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope'][$type_id]['browse'][$data_display_mode] : false);
			
			if (!$scenario_id && $browse_scope_id) {

				SiteEndVars::setFeedback('scope_id', $browse_scope_id, true);
			}
		}
		
		$arr_scope = data_visualise::getTypeScope($type_id);	
		$arr_context = data_visualise::getTypeContext($type_id);
		$arr_conditions = toolbar::getTypeConditions($type_id);
		$arr_frame = data_visualise::getTypeFrame($type_id);
		$arr_visual_settings = data_visualise::getVisualSettings();
	
		$scenario_hash = toolbar::checkActiveScenario('visualise', $arr_filters, $arr_scope, $arr_conditions);

		$identifier_data = $type_id.'_'.md5(serialize($arr_filters).'_'.serialize($arr_scope).'_'.serialize($arr_context).'_'.serialize($arr_conditions));
		$identifier_date = time();
		
		$has_data = ($value['identifier'] && $value['identifier']['data'] == $identifier_data);
		
		if ($has_data) {
			
			$is_updated = FilterTypeObjects::getTypesUpdatedSince($value['identifier']['date'], cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id']), true);
			
			if ($is_updated) {
				
				$has_data = false;
				
			} else {
				
				$identifier_date = $value['identifier']['date'];
			}
		}
		
		$arr_types_all = StoreType::getTypes();
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		$create_visualisation_package = new createVisualisationPackage($arr_project, $arr_types_all, $arr_frame, $arr_visual_settings);
		$create_visualisation_package->setOutput($arr);

		if (!$has_data) {

			$collect = data_visualise::getVisualisationCollector($type_id, $arr_filters, $arr_scope, $arr_conditions);
			
			if ($scenario_hash) {
					
				$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $scenario_id, $arr_use_project_ids);
				
				if ($arr_scenario['cache_retain']) {
					
					memoryBoost(2048);
					timeLimit(120);
				}
			}

			$create_visualisation_package->addType($type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $scenario_id, $scenario_hash);

			if ((!$arr_frame['object_subs']['unknown']['date'] && !$arr['data']['pack'][0]['object_subs']) || !$arr['data']['pack'][0]['objects']) { // No usable data
				
				msg(getLabel('msg_visualisation_not_set'));
				
				return;
			}

			if ($arr_context['include']) {

				foreach ($arr_context['include'] as $arr_include) {

					$context_type_id = $arr_include['type_id'];
					$context_scenario_id = $arr_include['scenario_id'];
					$arr_context_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $context_scenario_id, $arr_use_project_ids);
					
					if (!$arr_context_scenario) {
						continue;
					}
					
					SiteEndVars::setFeedback('context', ['type_id' => $context_type_id], true);
					
					$arr_filters = toolbar::getScenarioFilters($context_scenario_id);
					
					$cur_scope_id = SiteStartVars::getFeedback('scope_id');
					SiteEndVars::setFeedback('scope_id', ($arr_context_scenario['scope_id'] ?: false), true);
					$arr_scope = data_visualise::getTypeScope($context_type_id);
					SiteEndVars::setFeedback('scope_id', $cur_scope_id, true);
					
					$cur_condition_id = SiteStartVars::getFeedback('condition_id');
					SiteEndVars::setFeedback('condition_id', ($arr_context_scenario['condition_id'] ?: false), true);
					$arr_conditions = toolbar::getTypeConditions($context_type_id);
					SiteEndVars::setFeedback('condition_id', $cur_condition_id, true);
					
					$collect = data_visualise::getVisualisationCollector($context_type_id, $arr_filters, $arr_scope, $arr_conditions);
					
					$context_scenario_hash = toolbar::getScenarioHash($context_scenario_id, $arr_filters, $arr_scope, $arr_conditions);
					
					$create_visualisation_package->addType($context_type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $context_scenario_id, $context_scenario_hash);
				}
				
				SiteEndVars::setFeedback('context', null, true);
			}
		}
		
		$create_visualisation_package->getPackage();
		
		$arr['identifier'] = ['data' => $identifier_data, 'date' => $identifier_date];

		$_SESSION['custom_projects']['project_id'] = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		return $arr;
					
	}
	
	private function handleTypeObjectIds($id = false, $arr_visualisation_data = false) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');		
		$arr_public_interface_projects_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id);
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true);
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
	
		$show_header = true;
		
		if (is_array($id)) { // body text tags with multiple linked objects send an array of objects per type
			
			$arr_type_objects = $id;
			
		} else {
			
			$arr_type_objects = [];
			$arr_types_objects = [];
			
			// show data sent from visualisation
			if ($arr_visualisation_data) {

				$count = 0;
				$arr_type_object_ids = $arr_visualisation_data['arr_type_object_ids'];
				$explore_visualisation_object = $arr_visualisation_data['explore'];
				
				$project_type_is_present = false;
				
				foreach ((array)$arr_type_object_ids as $type_id => $arr_objects) {
					
					if (isset($arr_public_interface_projects_types[$type_id]) && count((array)$arr_objects)) {
						
						$project_type_is_present = true;
					}
				}
				
				foreach ((array)$arr_type_object_ids as $type_id => $arr_objects) {
					
					if (!$arr_objects) {
						continue;
					}
														
					// only show objects of types in any PUI project (perhaps offer as option?)
					if (!$arr_public_interface_projects_types[$type_id]) {
						
						if ($explore_visualisation_object || $project_type_is_present) {
							continue;
						}
					
						foreach ($arr_public_interface_projects_types as $public_interface_project_type) {
							
							$arr_objects_filter = ui::createReferencedObjectFilter($public_interface_project_type, [$type_id => $arr_objects], true);
				
							if (!count((array)$arr_objects_filter)) {
								
								continue;
							}
							
							$arr_filtered_objects = self::getTypeObjectIds($public_interface_project_type, $arr_objects_filter);

							foreach ((array)$arr_filtered_objects as $arr_filtered_object_id => $arr_filtered_object) {
					
								$arr_type_objects[$arr_filtered_object_id]['object'] = ['type_id' => $public_interface_project_type, 'object_id' => $arr_filtered_object_id];
								$arr_types_objects[$public_interface_project_type][$arr_filtered_object_id] = $arr_filtered_object_id;
								
								$count++;
							}
						}
						
					} else {
					
						foreach ($arr_objects as $object_id) {
				
							$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
							$arr_types_objects[$type_id][$object_id] = $object_id;
							
							$count++;
						}
					}
				}
			
				if ($count == 1) {
				
					$object_id = key($arr_type_objects);
					$type_id = $arr_type_objects[$object_id]['object']['type_id'];
					
					return self::createViewTypeObject($type_id, $object_id);
				}
				
			// show data based on view_object click	
			} else if ($id) {
		
				$arr_id = explode("_", $id);
				$type_id = $arr_id[0];
				$object_id = $arr_id[1];
		
				// if reference has nog been given, show object
				if (!$arr_id[2]) {
					
					// Check if it is a type that has been included in the interace, or if it is a media type
					if (($arr_public_interface_projects_types[$type_id] && !$arr_public_interface_project_filter_types[$type_id]) || in_array($type_id, (array)$arr_public_interface_settings['types']['media_types']) || in_array($type_id, (array)$arr_public_interface_settings['types']['central_types'])) {
				
						return self::createViewTypeObject($type_id, $object_id);
					}
				}
				
				$arr_options = ['set' => $arr_id[2], 'referenced_type_id' => $arr_id[3]];
				
				if ($arr_options['set'] == 'referenced' && !$arr_options['referenced_type_id']) { // list all incoming and outgoing references
					
					$show_header = false;
					
					$arr_ref_objects = self::getObjectReferences($type_id, $object_id, 'both', (count((array)$arr_public_interface_settings['types']['central_types']) ? $arr_public_interface_settings['types']['central_types'] : false), true);
					
					foreach ((array)$arr_ref_objects as $arr_referenced_object_id => $arr_referenced_object) {
					
						$arr_type_objects[$arr_referenced_object_id] = $arr_referenced_object;
						$arr_types_objects[$arr_referenced_object['object']['type_id']][$arr_referenced_object_id] = $arr_referenced_object;
					}
					
				} else if ($arr_options['set'] == 'referenced' && $arr_options['referenced_type_id']) {
					
					if ($arr_public_interface_settings['use_combined_references_as_filters']) { // explore based on classifications
						
						return $this->createCombinedReferencesFilters($arr_options['referenced_type_id'], $type_id, $object_id);
					}

					$show_header = false;		

					$arr_ref_objects = self::getObjectReferences($type_id, $object_id, 'both', [$arr_options['referenced_type_id'] => $arr_options['referenced_type_id']], true);
					
					foreach ((array)$arr_ref_objects as $arr_referenced_object_id => $arr_referenced_object) {
					
						$arr_type_objects[$arr_referenced_object_id] = $arr_referenced_object;
						$arr_types_objects[$arr_referenced_object['object']['type_id']][$arr_referenced_object_id] = $arr_referenced_object;
					}
				
				} else if ($arr_public_interface_project_filter_types[$type_id] || !$arr_public_interface_projects_types[$type_id]) {
				
					$arr_ref_objects = self::getObjectReferences($type_id, $object_id, 'both', false, true);
					
					foreach ((array)$arr_ref_objects as $arr_referenced_object_id => $arr_referenced_object) {
					
						$arr_type_objects[$arr_referenced_object_id] = $arr_referenced_object;
						$arr_types_objects[$arr_referenced_object['object']['type_id']][$arr_referenced_object_id] = $arr_referenced_object;
					}				
				} 
			}
			
			if (!count((array)$arr_type_objects)) {
				
				return false;
			}
		}

		if ($show_header) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$type_name = ($arr_public_interface_settings['labels']['type'][$exploration_type_id]['singular'] ? $arr_public_interface_settings['labels']['type'][$exploration_type_id]['singular'] : Labels::parseTextVariables($arr_type_set['type']['name']));
			$arr_name = FilterTypeObjects::getTypeObjectNames($type_id, $object_id);
			$object_name = $arr_name[$object_id];			
			
			$amount = ($count_filtered_type_objects ? $count_filtered_type_objects : count($arr_type_objects));
			$return = '<div class="head">
						<h1>'.$type_name.' "'.$object_name.'" ('.$amount.' '.($amount == 1 ? getLabel('lbl_reference') : getLabel('lbl_references')).')</h1>
						<div class="navigation-buttons">
							<button class="close" type="button">
								<span class="icon">'.getIcon('close').'</span>
							</button>
						</div>
					</div>';
		}

		if (count((array)$arr_type_objects)) {

			if ($arr_public_interface_settings['show_objects_list'] && $arr_types_objects) {
				
				$arr_tag_tabs = [];
				
				$i = 2;
				
				foreach ((array)$arr_types_objects as $ref_type_id => $arr_objects) {

					$arr_type_set = StoreType::getTypeSet($ref_type_id);
					
					$key = $i;
					
					if ($arr_public_interface_project_types[$ref_type_id]) {
						
						$key = 0;	
						
					} else if (in_array($ref_type_id, (array)$arr_public_interface_settings['types']['central_types'])) {
						
						$key = 1;
						
					}
					
					$arr_tag_tabs['links'][$key.'-'.$ref_type_id] = '<li><a href="#">'.Labels::parseTextVariables($arr_type_set['type']['name']).' ('.count((array)$arr_objects).')</a></li>';
					$arr_tag_tabs['content'][$key.'-'.$ref_type_id] = '<div>'.$this->createViewTypeObjectsList($ref_type_id, ['objects' => array_keys((array)$arr_objects)]).'</div>';
					
					$i++;
				}
				
				ksort($arr_tag_tabs['links']);
				ksort($arr_tag_tabs['content']);
				
				if (count((array)$arr_tag_tabs['links'])) {
					$return .= '<div class="tabs list-view">
						<ul>
							'.implode('', $arr_tag_tabs['links']).'
						</ul>
						'.implode('', $arr_tag_tabs['content']).'
					</div>';
				}

				
			} else {
				
				$i = 0;
				foreach ($arr_type_objects as $arr_object) {

					 $return .= self::createViewTypeObjectThumbnail($arr_object); 
					 $i++;
					 
					 if ($i > 100) {
						 break;
					 }
				}
			
			}
			
		}
		
		return '<div data-method="view_object">'.$return.'</div>';
		
	}
				
	public function createViewTypeObject($type_id = false, $object_id = false, $print = false) {
			
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);
		
		if (!$arr_public_interface_project_types[$type_id] && !in_array($type_id, (array)$arr_public_interface_settings['types']['media_types'])) {

			return self::handleTypeObjectIds($type_id.'_'.$object_id);
		}
		
		$use_custom_project_id = self::checkCustomProjectProjectId($type_id);
	
		if ($use_custom_project_id) {
			
			$public_user_interface_active_custom_project_id = $use_custom_project_id;
			
		} else {
			
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');	
		}
		
		$arr_object = self::getPublicInterfaceObject($type_id, $object_id);		
		$location_id = $type_id.'-'.$object_id;	
		
		$object_name = Response::addParseDelay('', function($foo) use ($arr_object) {
			$name = $arr_object['object']['object_name'];
			$name = GenerateTypeObjects::printSharedTypeObjectNames($name);
			$name = htmlspecialchars(strip_tags($name));
			return $name;
		});
		
		$arr_object['object']['object_name'] = $object_name;
		
		$elm_object = $this->createViewTypeObjectElm($arr_object, $print, $use_custom_project_id);

		if ($print) {
			
			$return = '<div>
						<h1>'.$arr_object['object']['object_name'].'</h1>
						'.$elm_object.'
					</div>';
			
		} else {

			// show tabbed interface, or only object data
			if ($arr_public_interface_settings['object_references'] || $arr_public_interface_settings['object_geo'] || $arr_public_interface_settings['object_soc'] || $arr_public_interface_settings['types']['exploration_types']) {
				
				$arr_object_view_tabs = ['links' => [], 'content' => []];
				
				$arr_object_view_tabs['links'][] = '<li><a href="#"><span>'.($arr_public_interface_settings['labels']['type'][$type_id]['singular'] ? Labels::parseLanguage($arr_public_interface_settings['labels']['type'][$type_id]['singular']) : getLabel('lbl_object')).'</span></a></li>';
				$arr_object_view_tabs['content'][] = '<div>'.$elm_object.'</div>';
				
				if ($arr_public_interface_settings['object_geo']) {
					
					$arr_object_view_tabs['links'][] = '<li><a href="#">'.($arr_public_interface_settings['labels']['explore_geo'] ? '<span>'.Labels::parseLanguage($arr_public_interface_settings['labels']['explore_geo']).'</span>' : '<span class="icon">'.getIcon('globe').'</span>').'</a></li>';
					$arr_object_view_tabs['content'][] = '<div id="y:ui_data:visualize_explore_object_geo-'.$type_id.'_'.$object_id.'" class="explore-object"></div>';
				}
				
				if ($arr_public_interface_settings['object_soc']) {
					
					$arr_object_view_tabs['links'][] = '<li><a href="#">'.($arr_public_interface_settings['labels']['explore_soc'] ? '<span>'.Labels::parseLanguage($arr_public_interface_settings['labels']['explore_soc']).'</span>' : '<span class="icon">'.getIcon('graph').'</span>').'</a></li>';
					$arr_object_view_tabs['content'][] = '<div id="y:ui_data:visualize_explore_object_soc-'.$type_id.'_'.$object_id.'" class="explore-object"></div>';
				}
				
				if ($arr_public_interface_settings['object_time']) {
					
					$arr_object_view_tabs['links'][] = '<li><a href="#">'.($arr_public_interface_settings['labels']['explore_time'] ? '<span>'.Labels::parseLanguage($arr_public_interface_settings['labels']['explore_time']).'</span>' : '<span class="icon">'.getIcon('chart-bar').'</span>').'</a></li>';
					$arr_object_view_tabs['content'][] = '<div id="y:ui_data:visualize_explore_object_time-'.$type_id.'_'.$object_id.'" class="explore-object"></div>';
				}
				
				if ($arr_public_interface_settings['object_references']) {
					
					$arr_type_objects = [];

		
					foreach ((array)$arr_object['object_referenced'] as $referenced_type_id => $arr_referneced_objects) {

						if (count((array)$arr_public_interface_settings['types']['central_types']) && !in_array($referenced_type_id, $arr_public_interface_settings['types']['central_types'])) { // only list types that are set as central types

							continue;
						}
												
						foreach ((array)$arr_referneced_objects as $arr_referenced_object_id => $arr_referenced_object) {
					
							$arr_type_objects[$arr_referenced_object_id] = $arr_referenced_object;
						}							
					}
					
					foreach ((array)$arr_object['object_references'] as $reference_type_id => $arr_reference_objects) {
												
						if (!isset($arr_public_interface_project_types[$reference_type_id])) { // only list types that are used in the PUI
			 
							continue;
						}
						
						if (count((array)$arr_public_interface_settings['types']['central_types']) && !in_array($reference_type_id, $arr_public_interface_settings['types']['central_types'])) { // only list types that are set as central types

							continue;
						}
						
						foreach ((array)$arr_reference_objects as $arr_reference_object_id => $arr_reference_object) {
					
							$arr_type_objects[$arr_reference_object_id] = $arr_reference_object;
						}							
					}
					
					$count_references = count((array)$arr_type_objects);
					
					$arr_object_view_tabs['links'][] = '<li class="'.($count_references ? '' : 'no-data').'">
															<a href="#">
																<span>'.getLabel('lbl_references').'</span>
																'.($count_references ? '<span class="amount">'.$count_references.'</span>' : '').'
															</a>
														</li>';
					$arr_object_view_tabs['content'][] = '<div id="y:ui_data:show_project_type_object-'.$type_id.'_'.$object_id.'_referenced"></div>';
				}
			
				foreach ((array)$arr_public_interface_settings['types']['exploration_types'] as $key => $exploration_type_id) {
					
					if ($exploration_type_id == $type_id) {
						
						continue;
					}
					
					$arr_type_explore_set = StoreType::getTypeSet($exploration_type_id);
					$arr_object_view_tabs['links'][] = '<li class="'.($arr_public_interface_settings['use_combined_references_as_filters'] || $arr_object['object_explore_referenced_references'][$exploration_type_id] ? '' : 'no-data').'">
															<a href="#" class="type-'.$exploration_type_id.'">
																<span>'.($arr_public_interface_settings['labels']['type'][$exploration_type_id]['plural'] ? Labels::parseLanguage($arr_public_interface_settings['labels']['type'][$exploration_type_id]['plural']) : Labels::parseTextVariables($arr_type_explore_set['type']['name'])).'</span>
																'.($arr_object['object_explore_referenced_references'][$exploration_type_id] ? '<span class="amount">'.count((array)$arr_object['object_explore_referenced_references'][$exploration_type_id]).'</span>' : '').'
															</a>
														</li>';
					$arr_object_view_tabs['content'][] = '<div id="y:ui_data:show_project_type_object-'.$type_id.'_'.$object_id.'_referenced_'.$exploration_type_id.'"></div>';
				}
									
				$elm_object_tabs .= '<div class="tabs object-view">
					<ul>
						'.($arr_object_view_tabs ? implode('', $arr_object_view_tabs['links']) : '').'
					</ul>';
				
					$elm_object_tabs .= ($arr_object_view_tabs ? implode('', $arr_object_view_tabs['content']) : '');
				
				$elm_object_tabs .= '</div>';
				
				$elm_object = $elm_object_tabs;
				
			}

			$return = '<div data-method="view_object" data-location="'.SiteEndVars::getModLocation(0, [$public_user_interface_id, $public_user_interface_active_custom_project_id, 'object', $location_id]).'" data-type_id="'.$type_id.'" data-object_id="'.$object_id.'">
						<div class="head">
							'.($arr_object['object_thumbnail'] ? '<div class="object-thumbnail-image" style="background-image: url('.$arr_object['object_thumbnail'].');"></div>' : '').'
							<h1>'.$arr_object['object']['object_name'].'</h1>
							<div class="navigation-buttons">
								<button class="prev" type="button">
									<span class="icon">'.getIcon('prev').'</span>
								</button><button class="next" type="button">
									<span class="icon">'.getIcon('next').'</span>
								</button><button class="close" type="button">
									<span class="icon">'.getIcon('close').'</span>
								</button>
							</div>
						</div>
						'.$elm_object.'
					</div>';
		}
		
		$_SESSION['custom_projects']['project_id'] = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		return ($print == 'pdf' ? $elm_object : $return);
	}

	private function createViewTypeObjectElm($arr_object, $print = false, $use_custom_project_id = false) {
	
		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		
		$public_user_interface_active_custom_project_id = ($use_custom_project_id ? $use_custom_project_id : SiteStartVars::getFeedback('public_user_interface_active_custom_project_id'));
		
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true);
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);		
		
		$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'view');
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);
		$arr_source_types = $arr_object['object']['object_sources'];
		
		$elm_keyword_object_descriptions = $elm_related_media_object_descriptions = $elm_media_object_descriptions = $elm_type_object_descriptions = $elm_classification_object_descriptions = $elm_value_object_descriptions = false;
		$arr_cite_as_values = [];
		$arr_pdf_values = [];
		
		$arr_pdf_values['name'] = $arr_object['object']['object_name'];
		
		foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$arr_object_definition = $arr_object['object_definitions'][$object_description_id];
			
			if ((!$arr_object_definition['object_definition_value'] && !$arr_object_definition['object_definition_ref_object_id']) || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id) || $arr_object_definition['object_definition_style'] == 'hide') {
				continue;
			}
			
			$arr_object_definition_style = $arr_object_definition['object_definition_style'];
			$str_name = htmlspecialchars(Labels::parseTextVariables($arr_object_description['object_description_name']));
			
			if ($arr_object_description['object_description_ref_type_id']) {
				
				$arr_ref_type_objects = [];
				
				if ($arr_object_description['object_description_is_dynamic']) {
					
					foreach ($arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
					
						foreach($arr_ref_objects as $cur_object_id => $arr_reference) {
							
							$arr_ref_type_objects[] = ['type_id' => $ref_type_id, 'object_id' => $cur_object_id, 'value' => $arr_reference['object_definition_ref_object_name']];
						}
					}
					
				} else if ($arr_object_description['object_description_has_multi']) {

					foreach ($arr_object_definition['object_definition_ref_object_id'] as $key => $value) {

						$arr_ref_type_objects[] = ['type_id' => $arr_object_description['object_description_ref_type_id'], 'object_id' => $value, 'value' => $arr_object_definition['object_definition_value'][$key]];
					}

				} else {
					
					$arr_ref_type_objects[] = ['type_id' => $arr_object_description['object_description_ref_type_id'], 'object_id' => $arr_object_definition['object_definition_ref_object_id'], 'value' => $arr_object_definition['object_definition_value']];
				}
				
				if (in_array($arr_object_description['object_description_ref_type_id'], (array)$arr_public_interface_settings['types']['media_types'])) {
					
					foreach ($arr_ref_type_objects as $key => $arr_ref_type_object) {

						$arr_media_object = current(self::getPublicInterfaceObjects($arr_ref_type_object['type_id'], ['objects' => $arr_ref_type_object['object_id']], true, 1));
						$media_object_thumbnail = $arr_media_object['object_thumbnail'];
						
						if ($media_object_thumbnail) {

							$elm_related_media_object_descriptions .= '<div class="a" style="background-image: url('.$media_object_thumbnail.');" data-type_id="'.$arr_ref_type_object['type_id'].'" data-object_id="'.$arr_ref_type_object['object_id'].'" id="y:ui_data:show_project_type_object-'.$arr_ref_type_object['type_id'].'_'.$arr_ref_type_object['object_id'].'" title="'.htmlspecialchars(GenerateTypeObjects::printSharedTypeObjectNames($arr_ref_type_object['value'])).'"></div>';
							$arr_pdf_values['images'][] = ['cache_url' => $media_object_thumbnail, 'caption' => $arr_ref_type_object['value']];							
							
						} else {

							$icon = ($arr_public_interface_settings['icons']['type'][$arr_ref_type_object['type_id']] ? $arr_public_interface_settings['icons']['type'][$arr_ref_type_object['type_id']] : 'image');
							$elm_related_media_object_descriptions .= '<div class="a" data-type_id="'.$arr_ref_type_object['type_id'].'" data-object_id="'.$arr_ref_type_object['object_id'].'" id="y:ui_data:show_project_type_object-'.$arr_ref_type_object['type_id'].'_'.$arr_ref_type_object['object_id'].'" title="'.htmlspecialchars(GenerateTypeObjects::printSharedTypeObjectNames($arr_ref_type_object['value'])).'"><span class="icon" data-category="full">'.getIcon($icon).'</span></div>';

						}
					}

				} else {
					
					$elms = false;

					$elms .= Response::addParseDelay('', function($foo) use ($arr_ref_type_objects) {
				
						foreach ($arr_ref_type_objects as $key => $value) {
							$arr_ref_type_objects[$key]['value'] = GenerateTypeObjects::printSharedTypeObjectNames($value['value']);
						}
						
						usort($arr_ref_type_objects, function($a, $b) { return strcmp($a['value'], $b['value']); });
							
						foreach ($arr_ref_type_objects as $key => $arr_ref_type_object) {
							
							$return .= '<span class="a type-'.$arr_ref_type_object['type_id'].'" data-type_id="'.$arr_ref_type_object['type_id'].'" data-object_id="'.$arr_ref_type_object['object_id'].'" id="y:ui_data:show_project_type_object-'.$arr_ref_type_object['type_id'].'_'.$arr_ref_type_object['object_id'].'">'.$arr_ref_type_object['value'].'</span>';
						}				
						
						return $return;
					});
							
					foreach ($arr_ref_type_objects as $key => $arr_ref_type_object) {
						
						$arr_cite_as_values['object_description_'.$object_description_id][] = $arr_ref_type_object['value'];
						$arr_pdf_values['types'][$object_description_id][] = $arr_ref_type_object['value'];
					}

					if ($arr_public_interface_project_filter_types[$arr_object_description['object_description_ref_type_id']]) {
						
						$elm_keyword_object_descriptions .= $elms;
						
					} else if ($arr_object_description['object_description_value_type_base'] == 'type') {
						
						$elm_type_object_descriptions .= '<li data-object_description_id="'.$object_description_id.'"  class="'.$arr_object_description['object_description_value_type_base'].'">
									<dt>'.$str_name.':</dt>
									<dd>'.$elms.'</dd>
								</li>';
								
					} else if ($arr_object_description['object_description_value_type_base'] == 'classification') {
						
						$elm_classification_object_descriptions .= '<li data-object_description_id="'.$object_description_id.'"  class="'.$arr_object_description['object_description_value_type_base'].'">
									<dt>'.$str_name.':</dt>
									<dd>'.$elms.'</dd>
								</li>';					
					}
					
				}
				
			} else {
				
				$html_value = arrParseRecursive($arr_object_definition['object_definition_value'], ['Labels', 'parseLanguage']);
				
				$html_value = StoreTypeObjects::formatToPresentationValue($arr_object_description['object_description_value_type'], $html_value, $arr_object_description['object_description_value_type_options'], $arr_object_definition['object_definition_ref_object_id']);
			
				$arr_cite_as_values['object_description_'.$object_description_id] .= $html_value;
				
				if ($arr_object_description['object_description_value_type_base'] == 'media' || $arr_object_description['object_description_value_type_base'] == 'media_external') {
					
					$show_full = false;
					
					if (!count((array)$arr_public_interface_settings['types']['media_types']) || (count((array)$arr_public_interface_settings['types']['media_types']) && in_array($type_id, (array)$arr_public_interface_settings['types']['media_types']))) {
						
						$elm_media_object_descriptions .= '<span>'.$html_value.'</span>';
						
						$show_full = true;
					}							

					foreach ((array)$arr_object_definition['object_definition_value'] as $media_value) {
						
						$media = new EnucleateMedia($media_value);
						$url = $media->enucleate(true, false);
						$type = $media->enucleate(false, true);	
									
						if ($type == 'image') {
							
							if ($show_full) {
							
								$arr_pdf_values['images_full'][] = ['url' => $url];
							
							} else {
							
								$elm_related_media_object_descriptions .= '<div style="background-image: url('.$url.');" data-type_id="'.$type_id.'" data-object_id="'.$object_id.'" ></div>';
								$arr_pdf_values['images'][] = ['cache_url' => $url, 'caption' => ''];
							}
						}													
					}
					

					
				} else {
					
					$arr_pdf_values['values'][$object_description_id][] = $html_value;
					
					$elm_value_object_descriptions .= '<li data-object_description_id="'.$object_description_id.'" class="'.$arr_object_description['object_description_value_type_base'].'">
						<dt>'.$str_name.':</dt>
						<dd>'.$html_value.'</dd>
					</li>';	
				}
			}
			
		}

		if ($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['show_object_subs'] && $arr_object['object_subs_info']) {
			
			$arr_object_sub_tabs = ['links' => [], 'content' => []];
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if (!$arr_object['object_subs_info'][$object_sub_details_id] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
					continue;
				}
				
				$arr_object_sub_tabs['links'][] = '<li><a href="#"><span>'.($arr_object_sub_details['object_sub_details']['object_sub_details_type_id'] ? '<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details']['object_sub_details_type_id']]['name']).'</span> ' : '').'<span class="sub-name">'.htmlspecialchars(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span></span></a></li>';
				
				$arr_columns = [];
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					if (!$arr_object_sub_description['object_sub_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
						continue;
					}
					
					$str_name = Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']);
							
					$arr_columns[] = '<th class="limit'.($arr_object_sub_description['object_sub_description_value_type'] == 'date' ? ' date' : '').'">'.($arr_object_sub_description['object_sub_description_is_referenced'] ? '<span><span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.$str_name.'</span></span>' : '<span>'.$str_name.'</span>').'</th>';
				}
				
				$return_content = '<div>
					<table class="display" id="d:data_view:data_object_sub_details-'.$type_id.'_'.$object_id.'_'.$object_sub_details_id.'_1" data-pause="1" data-filter="0" data-search="0">
						<thead><tr><th class="date" data-sort="asc-0"><span>'.getLabel('lbl_date_start').'</span></th><th class="date"><span>'.getLabel('lbl_date_end').'</span></th><th class="limit disable-sort"></th><th class="max limit disable-sort"><span>'.getLabel('lbl_location').'</span></th>'
							.implode('', $arr_columns)
						.'</tr></thead>
						<tbody>
							<tr>
								<td colspan="'.(5+count($arr_columns)).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
							</tr>
						</tbody>
					</table>
				</div>';
				
				$arr_object_sub_tabs['content'][] = $return_content;
			}
			
			if (count((array)$arr_object['object_subs_info']) > 1) { // Show combined only if there are multiple subobjects to be shown
				
				array_unshift($arr_object_sub_tabs['links'], '<li><a href="#">'.getLabel('lbl_object_subs').': '.getLabel('lbl_overview').'</a></li>');
				
				$return_content = '<div>
					<table class="display" id="d:data_view:data_object_sub_details-'.$type_id.'_'.$object_id.'_all_1" data-pause="0" data-filter="0" data-search="0">
						<thead><tr><th class="limit"></th><th class="date" data-sort="asc-0"><span>'.getLabel('lbl_date_start').'</span></th><th class="date"><span>'.getLabel('lbl_date_end').'</span></th><th class="limit disable-sort"></th><th class="max limit disable-sort"><span>'.getLabel('lbl_location').'</span></th></tr></thead>
						<tbody>
							<tr>
								<td colspan="5" class="empty">'.getLabel('msg_loading_server_data').'</td>
							</tr>
						</tbody>
					</table>
				</div>';

				array_unshift($arr_object_sub_tabs['content'], $return_content);
			}
			
			$elm_object_subs .= '<div class="tabs object-subs">
				<ul>
					'.($arr_object_sub_tabs ? implode('', $arr_object_sub_tabs['links']) : '').'
				</ul>';
			
				$elm_object_subs .= ($arr_object_sub_tabs ? implode('', $arr_object_sub_tabs['content']) : '');
			
			$elm_object_subs .= '</div>';
		}

		if ($arr_source_types) {
		
			$arr_collect_type_object_names = array();
			
			foreach ((array)$arr_source_types as $ref_type_id => $arr_source_objects) {
				
				$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($ref_type_id, arrValuesRecursive('object_source_ref_object_id', $arr_source_objects), 'style_include');
				
				foreach ($arr_source_objects as $arr_source_object) {
					$arr_collect_type_object_names[] = str_replace("&", "&amp;", $arr_type_object_names[$arr_source_object['object_source_ref_object_id']]);
				}
				
			}
			
			sort($arr_collect_type_object_names);
			
			$arr_pdf_values['sources'] = $arr_collect_type_object_names;
			$elm_object_sources .= '<div><p>'.implode('</p><p>', $arr_collect_type_object_names).'</p></div>';

		}
		
		if ($arr_public_interface_settings['cite_as'][$type_id]) {
	
			$citation_elm = $this->createCitationElm($arr_object, $arr_cite_as_values, $arr_public_interface_settings['cite_as'][$type_id]); 
		}
		
		if (!$print) {
			
			$elm_object = '<menu class="buttons">
					<button class="'.($arr_public_interface_settings['selection'] ? '' : 'hide').' selection-add-elm" value="" type="button" data-elm_id="'.$type_id.'_'.$object_id.'" data-elm_type="object" data-elm_name="'.$arr_object['object']['object_name'].'" data-elm_thumbnail="'.$arr_object['object_thumbnail'].'">
						<span class="icon">'.getIcon('download').'</span>
					</button><button class="print '.($arr_public_interface_settings['print_object'] ? '' : 'hide').'" value="" title="'.getLabel('lbl_print').' '.$arr_object['object']['object_name'].'"  type="button" data-href="'.SiteStartVars::getBasePath(0, false).SiteStartVars::$page['name'].'.p/'.$public_user_interface_id.'/'.$public_user_interface_active_custom_project_id.'/object-print/'.$type_id.'-'.$object_id.'">
						<span class="icon">'.getIcon('print').'</span>
					</button><button class="url quick '.($arr_public_interface_settings['show_object_url'] ? '' : 'hide').'" id="y:ui_data:object_url-get_'.$type_id.'_'.$object_id.'" value="" title="'.getLabel('lbl_show').' '.getLabel('lbl_URL').'" type="button">
						<span class="icon">'.getIcon('link').'</span>
					</button><button class="share quick '.($arr_public_interface_settings['share_object_url'] ? '' : 'hide').'" id="y:ui_data:object_url-share_'.$type_id.'_'.$object_id.'" value="" title="'.getLabel('lbl_share').' '.$arr_object['object']['object_name'].'" type="button">
						<span class="icon">'.getIcon('users').'</span>
					</button>
				</menu>';
		}
			
		$elm_object .= '<ul>
				<li class="media">
					'.$elm_media_object_descriptions.'
				</li>
				<li class="related-media">
					'.$elm_related_media_object_descriptions.'
				</li>
				<li class="keywords">
					'.$elm_keyword_object_descriptions.'
				</li>
				<li class="object-descriptions">
					<dl>'.
							$elm_type_object_descriptions.
							$elm_classification_object_descriptions.
							$elm_value_object_descriptions.
					'</dl>
				</li>
				<li class="object-subs">
					'.$elm_object_subs.'
				</li>
				<li class="sources">
					'.$elm_object_sources.'
				</li>
				<li class="cite-as">
					'.$citation_elm.'
				</li>
			</ul>';

		return ($print === 'pdf' ? $arr_pdf_values : $elm_object);
	}
	
	private function createCitationElm($arr_object, $arr_cite_as_values, $arr_citation_parts) {
		
		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];
		$date_modified = strtotime($arr_object['object']['object_dating']);
		
		foreach ($arr_citation_parts as $arr_cite_as) {
							
			switch ($arr_cite_as['citation_elm']) {
				case 'value':
				
					if (is_array($arr_cite_as_values[$arr_cite_as['value']])) {
						
						$arr_values = $arr_cite_as_values[$arr_cite_as['value']];
						$value = Response::addParseDelay('', function($foo) use ($arr_values) {
					
							foreach ($arr_values as $key => $value) {
								$arr_values[$key] = GenerateTypeObjects::printSharedTypeObjectNames($value);
							}
							
							usort($arr_values, function($a, $b) { return strcmp($a, $b); });
							
							$return = false;
							
							foreach ($arr_values as $key => $value) {
								
								$return .= ($return ? ', ' : '') . trim($value);
							}				
							
							return $return;
						});	
											
					} else {
						
						$value = $arr_cite_as_values[$arr_cite_as['value']];
					}
				
					$citation_elm .= $value;
					break;
				case 'string':
					$citation_elm .= htmlspecialchars_decode(Labels::parseTextVariables($arr_cite_as['string']));
					break;
				case 'object_name':
					$citation_elm .= $arr_object['object']['object_name'];
					break;
				case 'access_date':
					$citation_elm .= date('d-m-Y');
					break;
				case 'access_day':
					$citation_elm .= date('d');
					break;
				case 'access_month':
					$citation_elm .= date('m');
					break;
				case 'access_year':
					$citation_elm .= date('Y');
					break;
				case 'modify_date':
					$citation_elm .= date('d-m-Y', $date_modified);
					break;
				case 'modify_day':
					$citation_elm .= date('d', $date_modified);
					break;
				case 'modify_month':
					$citation_elm .= date('m', $date_modified);
					break;
				case 'modify_year':
					$citation_elm .= date('Y', $date_modified);
					break;
				case 'url':
					$citation_elm .= $this->getObjectURL($type_id, $object_id);
					break;
				case 'object_id':
					$citation_elm .= $object_id;
					break;
				case 'type_id':
					$citation_elm .= $type_id;
					break;
				case 'nodegoat_id':
					$citation_elm .= GenerateTypeObjects::encodeTypeObjectId($type_id, $object_id);
					break;
				case 'line_break':
					$citation_elm .= '<br />';
					break;
			}
		}
		
		return $citation_elm;
	}
	
	public static function createViewKeywords($filter_type_id = false, $value, $filter_id = false) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');		
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_project_filter_types = ($filter_type_id ? [$filter_type_id] : cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true));

		$arr_keywords = [];
		$amount_of_filter_types = 0;
		
		$arr_filter = [];
		$arr_filter['search_name'] = $value;
		
		if ($filter_id) {
			
			$arr_filter_id = explode('_', $filter_id);		
			
			$target_type_id = $arr_filter_id[0];
			$object_description_id = $arr_filter_id[2];
			$object_description_reference_type_id = $arr_filter_id[3];		
			

			$arr_filter['object_filter'] = [['referenced_types' => [$target_type_id => ['object_definitions' => [$object_description_id => ['objects' => ['relationality' => ['equality' => '≥', 'value' => 1, 'range' => '']]]]]]]];
			
		}
		
		foreach ((array)$arr_public_interface_project_filter_types as $type_id) {

			$arr_objects = self::getPublicInterfaceObjects($type_id, $arr_filter, true, 75, false, true, ['no_thumbnails'=> true]);
			
			if (!count((array)$arr_objects)) {
				continue;
			}
			
			foreach ((array)$arr_objects as $arr_object) {
				$arr_keywords[$type_id][] =  $arr_object;
			}
			$amount_of_filter_types++;
		}
		
		foreach ((array)$arr_keywords as $arr_type_keywords) {
			
			$result .= '<li>
				<ul>';
				foreach ((array)$arr_type_keywords as $arr_keyword) {
					$result .= '<li data-type_id="'.$arr_keyword['object']['type_id'].'" data-object_id="'.$arr_keyword['object']['object_id'].'" class="keyword type-'.$arr_keyword['object']['type_id'].'">'.$arr_keyword['object']['object_name'].'</li><li class="separator"></li>';
				}
				$result .= '</ul>
				</li>';
				
		}

		return '<ul class="keywords">'.$result.'</ul>';
	}
	
	public static function createViewTypeObjectThumbnail($arr_object, $show_ref_counts = false) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];

		if (!$arr_object['object']['object_name']) {
		
			$arr_object = current(self::getPublicInterfaceObjects($type_id, ['objects' => $object_id], true, 1));

			if (!$arr_object) {
				
				$arr_object['object']['object_name'] = getLabel('msg_not_available');
				$no_name_parse = true;
			}
		}
		
		if ($show_ref_counts) {
			
			$arr_ref = self::getObjectReferences($type_id, $object_id, 'both', (count((array)$arr_public_interface_settings['types']['central_types']) ? $arr_public_interface_settings['types']['central_types'] : false));
			
			$elm_ref_count = '<div class="ref-count">
								<div>
									<span class="arrow">→</span>
									<span class="count">'.$arr_ref['count']['referenced'].'</span>
								</div>
								<div>
									<span class="count">'.$arr_ref['count']['references'].'</span>
									<span class="arrow">→</span>
								</div>
							</div>';
		}
		
		$first_char = Response::addParsePost(trim($arr_object['object']['object_name']), array('limit' => 1));
	
		if (!$first_char) {
			$first_char = '·';
		}
		
		$object_name = $arr_object['object']['object_name'];

		$return = '<div class="object-thumbnail a" id="y:ui_data:show_project_type_object-'.$type_id.'_'.$object_id.'">'
					.'<div class="image" '.($arr_object['object_thumbnail'] ? 'style="background-image: url('.$arr_object['object_thumbnail'].');"' : '').'>'.($arr_object['object_thumbnail'] ? '' : '<span>'.$first_char.'</span>').'</div>'
					.'<div class="name"><span>'.$object_name.'</span></div>'
					.$elm_ref_count
				.'</div>'; 
		
		return $return;
		
	}
	
	public static function handleFeedbackFilter($type_id, $arr_filter) {
		
		$arr_feedback_filters = SiteStartVars::getFeedback('arr_feedback_filters');
		$feedback_filter_key = false;
		
		foreach ((array)$arr_feedback_filters as $key => $arr_feedback_filter) {

			if ($arr_feedback_filter === $arr_filter) {
		
				$feedback_filter_key = $key;
			} 
		}
		
		if ($feedback_filter_key === false) {
			
			$arr_feedback_filters[] = $arr_filter;
			$feedback_filter_key = count((array)$arr_feedback_filters) - 1;
				
		}
		
		SiteEndVars::setFeedback('arr_feedback_filters', $arr_feedback_filters, true);
		
		$_SESSION['data_view']['filter'][$type_id] = $arr_filter;
		
		return $feedback_filter_key;
	}
	
	private function createViewTypeObjectsList($type_id, $arr_filter, $pause = true) {
		
		$feedback_filter_key = false;
		
		if (count((array)$arr_filter)) {

			$feedback_filter_key = self::handleFeedbackFilter($type_id, $arr_filter);
		}

		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$filter_reset = new FilterTypeObjects($type_id);
		$filter_reset->resetResultInfo();
		
		$return = '<table class="display" id="d:ui_data:data-'.$type_id.($feedback_filter_key !== false ? '_'.($feedback_filter_key + 1) : '').'" '.($pause ? 'data-pause="1"' : '').'>
			<thead><tr>';

				if ($arr_type_set['type']['object_name_in_overview']) {
					$return .= '<th class="max limit"><span>'.getLabel('lbl_name').'</span></th>';
				}
					
				$nr_column = 0;
				
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
					if (!$arr_object_description['object_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
						continue;
					}
					
					$return .= '<th class="limit'.(!$arr_type_set['type']['object_name_in_overview'] && $nr_column == 0 ? ' max' : '').'"><span>'.Labels::parseTextVariables($arr_object_description['object_description_name']).'</span></th>';
					
					$nr_column++;

				}

			$return .= '</tr></thead>
			<tbody>
				<tr>
					<td colspan="'.(count((array)$arr_type_set['object_descriptions'])).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
				</tr>
			</tbody>
		</table>';
		
		return $return;
	}
	
	public static function css() {
		
		$return = '';
	
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('[data-method=view_object]', function(elm_scripter) {

					var elm_ui = elm_scripter.closest('.ui');

					if (!elm_scripter.is('[data-method=view_object]')) {
						
						var elm_scripter = elm_ui.find('[data-method=view_object]');
						elm_scripter.data({module: 'ui_data'});
					}
					
					if (!elm_scripter.children().length) {
						return;
					}

					elm_ui.find('.project-dynamic-nav, .tools').attr('data-object_active', true);
					
					var elm_container = elm_scripter.parent();
											
					if (elm_container.children().length) {
						elm_scripter.siblings().addClass('hide');
					}
					
					LOCATION.attach(elm_scripter[0], elm_scripter.attr('data-location'), true);	
					window.scrollTo(0,0);
					
					elm_scripter.on('click', 'button.close', function() {
					
						if (elm_container.children().length > 1) {
						
							// Show previous object view
							var elm_previous_object = elm_scripter.prev();
							
							elm_previous_object.removeClass('hide');
							
							LOCATION.attach(elm_previous_object[0], elm_previous_object.attr('data-location'), true);	
							window.scrollTo(0,0);
							
						} else {

							if (!elm_ui.find('.data .objects').children().length) {
								
								elm_ui.find('[id^=y\\\:ui\\\:set_project-].active').trigger('click');
								elm_ui.find('.project-dynamic-nav, .tools').attr('data-object_active', false);
								
							} else if (!elm_scripter.siblings().length) {
							
								elm_ui.find('.project-dynamic-nav, .tools').attr('data-object_active', false);
							}
							
							LOCATION.attach(elm_ui[0], false, true);
							window.scrollTo(0,0);
							
						}
												
						elm_scripter.remove();
					}).on('click', '[id^=y\\\:ui_data\\\:show_project_type_object-]', function() {
					
						var elm_object = elm_ui.find('.object');	
						$(this).quickCommand(elm_object, {'html': 'append'});
						
						elm_object[0].elm_prevnext = false;
					}).on('open', '.tabs.object-view > div', function(e) {
			
						if (e.target != e.currentTarget) {
							return;
						}
						
						var elm_tab = $(this);
						
						if (elm_tab.attr('id')) {
						
							elm_tab.quickCommand(elm_tab).removeAttr('id');
						}
					}).on('click', '[id^=y\\\:ui_data\\\:visualize_explore_object]', function() {

						$(this).quickCommand(elm_ui.find('.explore-object'));
						window.scrollTo(0,0);
					}).on('command', '.datatable [id^=x\\\:ui_data\\\:show_project_type_object-]', function() {
					
						var elm_object = elm_ui.find('.object');		
						$(this).data({target: elm_object, options: {'html': 'append'}});
						elm_object[0].elm_prevnext = $(this);
					}).on('command', '.body [id^=y\\\:data_view\\\:view_type_object-]', function() {
						
						$(this).data({target: elm_ui.find('.object'), method: 'handle_tags', module: 'ui_data', options: {'html': 'append'}});
					}).on('open', '.tabs > div', function(e) {
			
						if (e.target != e.currentTarget) {
							return;
						}
						
						var elm_table = $(this).find('[id^=d\\\:]').first();

						if (!elm_table.length) {
							return;
						}
						
						COMMANDS.dataTableContinue(elm_table);
					});
					
					elm_scripter.find('[id^=y\\\:ui_data\\\:object_url-]').each(function() {
						COMMANDS.setTarget($(this), elm_ui.find('div.fixed-view-container'));
					});
								
					var elm_object = elm_scripter.closest('[id=y\\\:ui_data\\\:show_project_type_object-0]');
					var elm_object_thumbnail = elm_ui.find('[id=y\\\:ui_data\\\:show_project_type_object_thumbnail-0]');
					var touch = (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0));
					
					elm_prevnext = elm_object[0].elm_prevnext;
					
					if (elm_prevnext) {
						
						elm_scripter[0].cur_elm_prevnext = elm_prevnext;
						
						elm_scripter.on('click', '.navigation-buttons > button.next, .navigation-buttons > button.prev', function() {
						
							var cur_elm_prevnext = elm_scripter[0].cur_elm_prevnext;
							var elm = $(this);
							
							if (elm.hasClass('prev')) {
								var target = cur_elm_prevnext.closest('tr, li').prev();
								var next_prev = 'prev';
							} else if (elm.hasClass('next')) {
								var target = cur_elm_prevnext.closest('tr, li').next();
								var next_prev = 'next';
							} else {
								return;
							}
							
							var call_view = function() {
								target.trigger('click');
							};
							
							if (target.length) {
								call_view();
							} else {
								
								var table = cur_elm_prevnext.closest('[id^=d:], .datatable');
								
								if (table.length) {
									
									table.trigger(next_prev);
									
									if (table.is('[id^=d:]')) {
										
										table.one('commandfinished', function() {
											target = table.find('[data-method]');
											target = (next_prev == 'prev' ? target.last() : target.first());
											call_view();
										});
									} else {
										
										target = table.find('[data-method]');
										target = (next_prev == 'prev' ? target.last() : target.first());
										call_view();
									}
								}
							}							
						});
						
						elm_scripter.on('keyup', function(e) {
							
							if (e.which == 37) {
								elm_scripter.find('.navigation-buttons > button.prev').trigger('click');
							} else if (e.which == 39) {
								elm_scripter.find('.navigation-buttons > button.next').trigger('click');
							} else {
								return;
							}
						});
					} else {
						elm_scripter.find('.navigation-buttons > button.next, .navigation-buttons > button.prev').addClass('hide');
					}

					elm_scripter.find('[id^=d\\\:].display').each(function() {

						var elm_table = $(this);
						elm_table.on('commandfinished', function() {
						
							elm_table.find('.popup').each(function() {
							
								if (!$(this).is('tr') && $(this).closest('.object-subs').length) {
								
									$(this).removeClass('popup a');
								} else if (!$(this).is('tr') || $(this).attr('data-method') == 'view_type_object') {
								
									$(this).removeClass('popup').addClass('a quick');
								}
							});
						});
					});
						
										
					elm_scripter.find('.body .tag').each(function() {
						
						var elm_tag = $(this);
						elm_tag.removeClass('popup');
						
						if (touch) {
							
							elm_tag.on('touchend', function() {
							
								var arr_type_object_ids = {};
								var ids = elm_tag.attr('data-ids');
								var arr_ids = ids.split('|');
								
								for (var i = 0; i < arr_ids.length; i++) {
							
									var arr_tag_type_object_ids = arr_ids[i].split('_');

									if (!arr_type_object_ids[arr_tag_type_object_ids[0]]) {
										arr_type_object_ids[arr_tag_type_object_ids[0]] = {};
									}
									
									arr_type_object_ids[arr_tag_type_object_ids[0]][arr_tag_type_object_ids[1]] = arr_tag_type_object_ids[1];
								}
			
								COMMANDS.setData(elm_object[0], {arr_type_object_ids: arr_type_object_ids}, true);								
								COMMANDS.setData(elm_object_thumbnail[0], arr_type_object_ids, true);
						
								elm_object_thumbnail.quickCommand(elm_object_thumbnail);
								
								elm_object_thumbnail.on('click', 'div.a', function() {
								
									elm_object.quickCommand(elm_object, {'html': 'append'});
									elm_object_thumbnail.html('');
								}).on('click', 'button', function() {
								
									elm_object_thumbnail.html('');
								});
							});
						} else {
						
							elm_tag.addClass('quick');
							
							elm_tag.on('mouseenter', function() { 
								
								elm_tag.addHover({hover_action: 'y:ui_data:hover_object-'});
								
							});
						}						
					});
					
					var add_to_selection_elm = elm_scripter.find('.selection-add-elm');
					
					if (add_to_selection_elm.length) {
					
						UISELECTION.handleElement(add_to_selection_elm);
					}
				});
				
				SCRIPTER.dynamic('[data-method=set_project]', '[data-method=view_object]');

				SCRIPTER.dynamic('[data-method=handle_dynamic_project_data]', function(elm_scripter) {

					var elm_ui = elm_scripter.closest('.ui');
					var cur = elm_scripter;

					if (cur.is('.explore-object')) {

						var elm_previous_data = cur.parent();
						var elm_new_data = cur.children().detach();
						var explore_object = true;
							
					} else {

						if (!cur.is('[data-method=handle_dynamic_project_data]')) {

							var cur = elm_ui.find('[data-method=handle_dynamic_project_data]');
						}

						var elm_previous_data = cur.parent().next('.data').children('.objects');
						
						elm_ui.find('.project-dynamic-nav').attr('data-set', false);
						elm_ui.find('.project-filters').attr('data-active', false);
						cur.parent().attr('data-active', false);
						
						if (!cur.hasClass('hide')) {
							cur.parent().attr('data-active', true);
						}
												
						if (!cur.find('.new-data').length) {
						
							elm_previous_data.empty();
							return;
						}

						var elm_new_data_container = cur.children('.new-data').detach();
						var elm_new_data = elm_new_data_container.children();
						var arr_options = JSON.parse(elm_new_data_container.attr('data-options'));
						
						var display_mode = arr_options['display_mode'];
						
						elm_previous_data.attr('data-display_mode', display_mode);
						
						if (arr_options['set']) {
							elm_ui.find('.project-dynamic-nav').attr('data-set', true);
						}				
						
						if (arr_options['filtered']) {	
							elm_ui.find('.project-filters').attr('data-active', true);
						} 

					}
					
					var func_handle_click = function(elm_map) {
					
						elm_map.on('click.review', '[id=y\\\:data_visualise\\\:review_data-date]', function() {

							var cur_elm = $(this);
							var obj_labmap = cur_elm.closest('.labmap')[0].labmap;
							
							var dateint_range = obj_labmap.getDateRange();
							var arr_data = obj_labmap.getData();

							var arr_type_object_ids = {};
							
							for (var type_id in arr_data.info.types) { // Prepare and order the Types list
								arr_type_object_ids[type_id] = {};
							}
							
							// Single date sub-objects
							for (var i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
								
								var date = arr_data.date.arr_loop[i];
								var in_range = (date >= dateint_range.min && date <= dateint_range.max);
								
								if (!in_range) {
									continue;
								}

								var arr_object_subs = arr_data.date[date];
								
								for (var j = 0, len_j = arr_object_subs.length; j < len_j; j++) {
								
									var object_sub_id = arr_object_subs[j];
									var arr_object_sub = arr_data.object_subs[object_sub_id];
						
									if (arr_object_sub.object_sub_details_id === 'object') { // Dummy sub-object
										continue;
									}
															
									var object_id = arr_object_sub.object_id;
									var type_id = arr_data.objects[object_id].type_id;
									arr_type_object_ids[type_id][object_id] = object_id;
									
									// Full objects and possible partake in scopes
									for (var i_connected = 0, len_i_connected = arr_object_sub.connected_object_ids.length; i_connected < len_i_connected; i_connected++) {
										
										var connected_object_id = arr_object_sub.connected_object_ids[i_connected];	
										var in_scope = (connected_object_id != object_id);
							
										if (in_scope) {			
											var type_id = arr_data.objects[connected_object_id].type_id;
											arr_type_object_ids[type_id][connected_object_id] = connected_object_id;
										}
									}						
								}
							}
							
							// Sub-objects with a date range
							for (var i = 0, len = arr_data.range.length; i < len; i++) {
								
								var object_sub_id = arr_data.range[i];
								var arr_object_sub = arr_data.object_subs[object_sub_id];
								
								if (arr_object_sub.object_sub_details_id === 'object') { // Dummy sub-object
									continue;
								}		
														
								var in_range = ((arr_object_sub.date_start >= dateint_range.min && arr_object_sub.date_start <= dateint_range.max) || (arr_object_sub.date_end >= dateint_range.min && arr_object_sub.date_end <= dateint_range.max) || (arr_object_sub.date_start < dateint_range.min && arr_object_sub.date_end > dateint_range.max));
								
								if (!in_range) {
									continue;
								}
								
								var object_id = arr_object_sub.object_id;
								var type_id = arr_data.objects[object_id].type_id;
								arr_type_object_ids[type_id][object_id] = object_id;
								
								// Full objects and possible partake in scopes
								for (var i_connected = 0, len_i_connected = arr_object_sub.connected_object_ids.length; i_connected < len_i_connected; i_connected++) {
									
									var connected_object_id = arr_object_sub.connected_object_ids[i_connected];	
									var in_scope = (connected_object_id != object_id);
							
									if (in_scope) {		
										var type_id = arr_data.objects[connected_object_id].type_id;
										arr_type_object_ids[type_id][connected_object_id] = connected_object_id;
									}
								}		
							}	
							
							var elm_object = elm_ui.find('[id=y\\\:ui_data\\\:show_project_type_object-0]');
							COMMANDS.setData(elm_object[0], {arr_type_object_ids: arr_type_object_ids}, true);
							elm_object.quickCommand(elm_object, {'html': 'append'});

						}).on('touch click', '.paint', function(e) {
						
							var cur_elm = $(this);
							var arr_link = cur_elm[0].arr_link;
				
							if (!arr_link) {
								return;
							}
							
							var touch = (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0));
							var elm_object_thumbnail = elm_ui.find('[id=y\\\:ui_data\\\:show_project_type_object_thumbnail-0]');
							
							if (touch) {
							
								elm_object_thumbnail.html('<div><div class=image></div><div class=name><span>...</span></div></div><button></button>');
							}
							
							var obj_labmap = cur_elm.closest('.labmap')[0].labmap;
							var arr_data = obj_labmap.getData();

							var arr_type_object_ids = {};
							
							if (arr_link.object_id && arr_link.type_id) {
							
								arr_type_object_ids[arr_link.type_id] = {};
								arr_type_object_ids[arr_link.type_id][arr_link.object_id] = arr_link.object_id;
								
							} else {
							
								for (var type_id in arr_data.info.types) { // Prepare and order the Types list
									arr_type_object_ids[type_id] = {};
								}
								
								if (arr_link.is_line) {
									arr_link.object_sub_ids = arrUnique(arr_link.object_sub_ids.concat(arr_link.connect_object_sub_ids));
								}
								if (!arr_link.object_ids) {
									arr_link.object_ids = [];
								}
							
								if (arr_link.object_sub_ids) { // Sub-objects
								
									for (var i = 0; i < arr_link.object_sub_ids.length; i++) {
									
										var object_sub_id = arr_link.object_sub_ids[i];
										var arr_object_sub = arr_data.object_subs[object_sub_id];
										var object_id = (arr_object_sub.original_object_id ? arr_object_sub.original_object_id : arr_object_sub.object_id);
										var type_id = arr_data.objects[object_id].type_id;

										arr_type_object_ids[type_id][object_id] = object_id;
									}
								}
								if (arr_link.connect_object_ids) { // Object descriptions
								
									for (var i = 0; i < arr_link.connect_object_ids.length; i++) {
									
										var arr_object_link = arr_link.connect_object_ids[i];
										var object_id = arr_object_link.object_id;
										var type_id = arr_data.objects[arr_object_link.object_id].type_id;
										
										arr_type_object_ids[type_id][object_id] = object_id;
									}
								}
								if (arr_link.object_ids) { // Objects
								
									for (var i = 0; i < arr_link.object_ids.length; i++) {
									
										var object_id = arr_link.object_ids[i];
										var arr_object = arr_data.objects[object_id];
										var type_id = arr_object.type_id;

										arr_type_object_ids[type_id][object_id] = object_id;
									}
								}
							}
							
							if (cur_elm.closest('.explore-object').length) {
								var explore = true;
							}
							
							var elm_object = elm_ui.find('[id=y\\\:ui_data\\\:show_project_type_object-0]');
							COMMANDS.setData(elm_object[0], {arr_type_object_ids: arr_type_object_ids, explore: explore}, true);
							
							if (touch) {
							
								COMMANDS.setData(elm_object_thumbnail[0], arr_type_object_ids, true);
								
								elm_object_thumbnail.quickCommand(elm_object_thumbnail);
								
								elm_object_thumbnail.on('click', 'div.a', function() {
								
									elm_object.quickCommand(elm_object, {'html': 'append'});
									elm_object_thumbnail.html('');
								}).on('click', 'button', function() {
								
									elm_object_thumbnail.html('');
									
								})
								
							} else {
							
								elm_object.quickCommand(elm_object, {'html': 'append'});
							}

						}).on('click.review', 'figure.types li, figure.object-sub-details li, figure.conditions li', function() {
				
							var cur = $(this);
							var elm_source = cur.closest('figure');
							
							var str_target = (elm_source.hasClass('conditions') ? 'condition' : (elm_source.hasClass('object-sub-details') ? 'object-sub-details' : 'type'));
							var str_identifier = this.dataset.identifier;
							
							var obj_labmap = cur.closest('.labmap')[0].labmap;
							
							var state = (this.dataset.state == '1' || this.dataset.state === undefined ? false : true);
							this.dataset.state = (state ? '1' : '0');			
							
							obj_labmap.setDataState(str_target, str_identifier, state);
							obj_labmap.doDraw();
						});
					}
					
					var visualisation_type = false;
					var new_visualisation_type = false;
					
					elm_new_data.on('run-visualisation', function() {
                       
						var func_visualise = function() {
						
							if (visualisation_type == 'plot') {
							
								if (is_same) {
								
									var obj_options = {};
									
									if (obj_data.data.center) {
										obj_options.default_center = obj_data.data.center.coordinates;
									}
									if (obj_data.data.zoom) {
										obj_options.default_zoom = obj_data.data.zoom;
									}
								} else {
								
									var arr_levels = [];
									
									for (var i = 1; i <= 18; i++) {
										arr_levels.push({width: 256 * Math.pow(2,i), height: 256 * Math.pow(2,i), tile_width: 256, tile_height: 256});
									}
									
									var attribution = obj_data.data.attribution;
									attribution = (attribution.source ? attribution.source+' - ' : '')+(obj_data.visual.settings.map_attribution ? obj_data.visual.settings.map_attribution+' - ' : '')+attribution.base;
									
									var obj_options = {
										call_class_paint: MapGeo,
										arr_class_paint_settings: {arr_visual: obj_data.visual},
										arr_class_data_settings: obj_data.data.options,
										arr_levels: arr_levels,
										tile_path: (!obj_data.visual.settings.geo_background_color ? obj_data.visual.settings.map_url : false),
										tile_subdomain_range: [1,2,3],
										attribution: attribution,
										background_color: obj_data.visual.settings.geo_background_color,
										allow_sizing: true,
										center_pointer: true
									};
									
									if (obj_data.data.center) {
										obj_options.default_center = obj_data.data.center.coordinates;
									}
									if (obj_data.data.zoom) {
										obj_options.default_zoom = obj_data.data.zoom;
									}
								}
							} else if (visualisation_type == 'soc') {
							
								if (is_same) {
									
									var obj_options = {};
								} else {
								
									var arr_levels = [];
								
									if (obj_data.visual.social.settings.display == 2) {
										
										for (var i = 1; i <= 14; i++) {
											arr_levels.push({auto: true});
										}
									} else {
									
										for (var i = 1; i <= 14; i++) {
											arr_levels.push({width: 6000 * Math.pow(1.5,i), height: 3000 * Math.pow(1.5,i)});
										}
									}
								
									var attribution = obj_data.data.attribution;
									attribution = (attribution.source ? attribution.source+' - ' : '')+attribution.base;
																
									var obj_options = {
										call_class_paint: MapSocial,
										arr_class_paint_settings: {arr_visual: obj_data.visual},
										arr_class_data_settings: obj_data.data.options,
										arr_levels: arr_levels,
										tile_path: false,
										attribution: attribution,
										background_color: obj_data.visual.social.settings.background_color,
										allow_sizing: false,
										default_center: {x: 0.5, y: 0.5},
										default_zoom: 7,
										center_pointer: false
									};
								}
							} else if (visualisation_type == 'line') {
							
								if (is_same) {
									
									var obj_options = {};
								} else {
									
									var arr_levels = [{width: 4000, height: 2000}];
									
									var attribution = obj_data.data.attribution;
									attribution = (attribution.source ? attribution.source+' - ' : '')+attribution.base;
									
									var obj_options = {
										call_class_paint: MapTimeline,
										arr_class_paint_settings: {arr_visual: obj_data.visual},
										arr_class_data_settings: obj_data.data.options,
										arr_levels: arr_levels,
										tile_path: false,
										attribution: attribution,
										background_color: false,
										allow_sizing: false,
										default_center: {x: 0.5, y: 0.5},
										default_zoom: 1,
										center_pointer: true
									};
								}
							}
                        
							if (is_new) {
								obj_options.call_class_data = MapData;
							}
							if (is_new || has_new_data) {
								obj_options.arr_data = obj_data.data;
							}
							if (is_new) {
								obj_options.default_time = obj_data.data.time;
							}
														
							var obj_labmap = elm_map[0].labmap;
						
							if (!obj_labmap) {
							
								obj_labmap = new Map(elm_map);
								elm_map[0].labmap = obj_labmap;
							}

							obj_labmap.init(obj_options);		
							
							if (visualisation_type == 'plot') {
							
								var elm_toolbox = getContainerToolbox(elm_ui);
								var obj_device_location = elm_toolbox[0].device_location;
								
								if (obj_device_location) {
								
									obj_device_location.addLabMapListener(obj_data.identifier, elm_map);
								}
							}							
						};
						
						if (!explore_object) {
							var obj_data = cur.parent()[0].obj_data;
						}
						
						var elm_map = elm_new_data.find('.labmap');
						var is_new = true;
						var is_same = false;
						var has_new_data = false;
				
						if (elm_map.length) {

							is_new = false;
							is_same = (visualisation_type == new_visualisation_type ? true : false);
							elm_map.children('.controls').children('.geo, .soc').addClass('hide');
							
							visualisation_type = new_visualisation_type;
							
							elm_map.removeClass('plot soc line').addClass(visualisation_type);
						}
						
						if (is_new) {
						
							if (obj_data) {
								COMMANDS.setData(elm_new_data[0], {identifier: obj_data.identifier});
							}
							
							COMMANDS.checkCacher(elm_new_data, 'quick', function(data) {

								if (!data) {
									return;
								}
								
								if (!obj_data || (obj_data.identifier.data != data.identifier.data || obj_data.identifier.date != data.identifier.date)) {
									
									obj_data = data;
									has_new_data = true;
										
									if (!explore_object) {
										cur.parent()[0].obj_data = obj_data;
									}
								} else {
									for (var key in data) {
										$.extend(obj_data[key], data[key]);
									}
								}
								
								if (is_new) {
								
									elm_map = $(obj_data.html).addClass(visualisation_type);
									elm_new_data.html(elm_map);
									func_handle_click(elm_map);
									new ToolExtras(elm_map, {fullscreen: true, maximize: 'fixed', tools: true});
								}
								
								func_visualise();
							});
						} else {
							func_visualise();
						}
					});

					cur.on('click', '[id^=y\\\:ui_data\\\:set_data_display_mode-]', function() {
					
						var elm_button = $(this);
						
						if (elm_button.attr('data-visualisation_type').length) {

							if (elm_new_data.is('[id=y\\\:ui_data\\\:get_visulisation_data-0]')) {

								new_visualisation_type = elm_button.attr('data-visualisation_type');
								elm_new_data.trigger('run-visualisation');
								
								elm_button.quickCommand();
								
								elm_button.siblings().removeClass('active');
								elm_button.addClass('active');
								
							} else {

								elm_button.data({value: 'html'}).quickCommand(cur.parent());	
							}
						} else {

							elm_button.data({value: 'html'}).quickCommand(cur.parent());
						}	
					});

					cur.find('[id^=y\\\:ui\\\:view_text-]').each(function() {
						COMMANDS.setTarget($(this), elm_ui.find('div.fixed-view-container'));
					});
					
					var func_load_grid_data = function(elm_load_more_button, elm_data) {
						
						if (elm_data) {
						
							var elm_data = $(elm_data);
							
							elm_data.on('click', function() {

								$(this).quickCommand(cur.closest('.project-dynamic-data').find('.object'), {'html': 'append'});
							});
							
							elm_data.appendTo(elm_previous_data);
						}
						
						if (elm_load_more_button) {
						
							var elm_load_more_button = $(elm_load_more_button);
							
							COMMANDS.setData(elm_load_more_button[0], {min: elm_load_more_button.attr('data-min'), max: elm_load_more_button.attr('data-max'), random: elm_load_more_button.attr('data-random')});
							
							elm_load_more_button.on('click', function() {
	
								$(this).quickCommand(function(arr_data) {
								
									func_load_grid_data(arr_data[0], arr_data[1]);
									elm_load_more_button.remove();
								});
							
							});	
							
							elm_load_more_button.addClass('hide').appendTo(elm_previous_data);	
							
							if (elm_load_more_button.attr('data-min') == 0) {
								
								elm_load_more_button.trigger('click');
								
							} else {
							
								elm_load_more_button.removeClass('hide');
							}
						}
					}
			
					if (elm_new_data.is('[id^=y\\\:ui_data\\\:get_visulisation_data-]')) {

						elm_previous_data.html(elm_new_data);
	
						visualisation_type = elm_new_data.attr('data-visualisation_type');

						elm_new_data.trigger('run-visualisation');
						
						return;
										
					} else if (elm_new_data.is('.list-view')) {

						elm_previous_data.html(elm_new_data);
				
						elm_new_data.navTabs();
						elm_new_data.find('[id^=d\\\:].display').data({module: 'ui_data'}).dataTable();

						elm_new_data.on('command', '[id^=x\\\:ui_data\\\:show_project_type_object-]', function() {
						
							var elm_object = elm_ui.find('.object');
							$(this).data({target: elm_object, options: {'html': 'append'}});
							elm_object[0].elm_prevnext = $(this);
						});	
					} else if (elm_new_data.length) { // GRID
					
						elm_previous_data.empty();
						func_load_grid_data(elm_new_data, false);
					} else if (!elm_new_data.length) {
					
						elm_previous_data.empty();
					}
				});
				
				SCRIPTER.dynamic('[data-method=set_project]', '[data-method=handle_dynamic_project_data]');
				
				SCRIPTER.dynamic('[data-method=combined_references_filter]', function(elm_scripter) {

					var func_filter = function() {
					
						var target = elm_scripter.find('[id^=y\\\:ui_data\\\:set_combined_references_filter-]');
						var arr_filters = [];
						
						elm_scripter.find('.combined-filters input:checked').each(function() {
						
							var arr_filter = JSON.parse($(this).attr('data-filter'));
							arr_filters.push(arr_filter);
						});
						
						COMMANDS.setData(target[0], arr_filters, true);
						target.quickCommand(target);
					};
					
					func_filter();
					
					elm_scripter.on('click', '.combined-filters input', function() {
					
						func_filter();
					});
				});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 

		if ($method == "load_grid_data") {
		
			$max = $value['max'];
			$min = $value['min'];
			$random = $value['random'];
			$arr_objects = [];
			
			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
			$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
			$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true);
			$arr_public_interface_project_types = $arr_public_interface_project_filter_types + cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
			
			if ($random) {
			
				foreach ((array)$arr_public_interface_project_types as $type_id => $random_value) {

					if ($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['random']['types'][$type_id]['set']) {
				
						$arr_type_random = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['random']['types'][$type_id];			
						$arr_type_random_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_type_random['filter_id'], true);
						$arr_type_random_filter = FilterTypeObjects::convertFilterInput($arr_type_random_filter['object']);
		
						$arr_objects += self::getPublicInterfaceObjects($type_id, $arr_type_random_filter, true, $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['start']['random']['amount'], false, false, ['random' => true]);
				
					}
				}
				
			} else {

				$arr_active_filters = toolbar::getFilter();
				$arr_types = [];
				
				foreach ((array)$arr_public_interface_project_types as $type_id => $arr) {
					
					if (SiteStartVars::getFeedback('type_id') && $type_id == SiteStartVars::getFeedback('type_id')) {
					
						$arr_types[$type_id] = $type_id;
					
					} else if ($arr_active_filters[$type_id]) {
						
						$arr_types[$type_id] = $type_id;
					} 
				}
			
				if (count((array)$arr_types)) {
					$arr_objects = self::getPublicInterfaceObjects($arr_types, false, true, $max, $min, true);	
				}
				
			}
			
			foreach ((array)$arr_objects as $arr_object) {
				
				$elm_data .= self::createViewTypeObjectThumbnail($arr_object); 
			}
			
			$filter_is_active = ui::isFilterActive(true);
			if (!$random && $filter_is_active > $max) {
				
				$max = $max + 50;
				$min = $min + 50;

				$elm_button = '<button class="a quick" id="y:ui_data:load_grid_data-0" data-min="'.$min.'" data-max="'.$max.'">'.getLabel('lbl_load_more').'</button>';
			
			} else {
				
				$elm_button = false;
			}
			
			$this->html = [$elm_button, $elm_data];
		}
				
		if ($method == "set_data_display_mode") {

			if ($id) {
				
				$arr_public_user_interface_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		
				if ($arr_public_user_interface_module_vars['display_mode']) {
					
					ui::setPublicUserInterfaceModuleVars(['display_mode' => $id]);
				}
				
				if ($value == 'html') {
					$this->html = $this->getDynamicProjectDataTools($id);
				}

			}
			
		}
		
		if ($method == "show_project_type_object") {

			$this->html = $this->handleTypeObjectIds($id, $value);
			
		}
		
		if ($method == "handle_tags") {
			
			$arr_objects = self::parsTag($id);
			
			if (count((array)$arr_objects) == 1) {
				
				$arr_object = current($arr_objects);
				
				$return = $this->createViewTypeObject($arr_object['object']['type_id'], $arr_object['object']['object_id']);
				
			} else {
				
				$return = $this->handleTypeObjectIds($arr_objects);
			}
			
			$this->html = $return;
			
		}
		
		if ($method == "hover_object") {
			
			$arr_objects = self::parsTag($id);

			if (count((array)$arr_objects) == 1) {
				
				$arr_object = current($arr_objects);
				
				$return = self::createViewTypeObjectThumbnail($arr_object, true); 
				
			} else {
				
				$return = '<div><div class="image"></div><div class="name">'.count((array)$arr_objects).' '.getLabel('lbl_objects').'</div></div>';
			}

			$this->html = $return;
			
		}
		
		if ($method == "show_project_type_object_thumbnail") {

			$arr_type_objects = [];
			$count = 0;
			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
			$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);

			if ($value) {

				foreach ($value as $type_id => $arr_objects) {
					
					// only show objects of types in any PUI project (perhaps offer as option?)
					if (!$arr_public_interface_project_types[$type_id]) {
						
						continue;
					}						
						
					foreach ($arr_objects as $object_id) {
						
						$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
						
						$count++;
					}
					
				}
				
			} else {

				$arr_id = explode("_", $id);
				$arr_type_objects[$arr_id[1]]['object'] = ['type_id' => $arr_id[0], 'object_id' => $arr_id[1]];
			}
			
			if ($count == 0) {
				
				$return = '<div class="a"><div class="image"><span>N</span></div><div class="name"><span>'.getLabel('msg_not_available').'</span></div></div>';
				
			} else if ($count > 1) {
				
				$return = '<div class="a"><div class="name"><span>';
				
				foreach ($value as $type_id => $arr_objects) {

					$arr_type_set = StoreType::getTypeSet($type_id);
					
					if (count((array)$arr_objects) > 1) {
					
						$return .= count((array)$arr_objects).' '.($arr_public_interface_settings['labels']['type'][$type_id]['plural'] ? Labels::parseTextVariables($arr_public_interface_settings['labels']['type'][$type_id]['plural']) : Labels::parseTextVariables($arr_type_set['type']['name'])).'. ';
					
					} else {
					
						$return .= count((array)$arr_objects).' '.($arr_public_interface_settings['labels']['type'][$type_id]['singular'] ? Labels::parseTextVariables($arr_public_interface_settings['labels']['type'][$type_id]['singular']) : Labels::parseTextVariables($arr_type_set['type']['name'])).'. ';
					
					}
				}
				
				$return .= '</span></div></div>';
				
			} else {
				
				$return = self::createViewTypeObjectThumbnail(current($arr_type_objects));
			}
			
			$return .= '<button class="a"><span class="icon">'.getIcon('close').'</span></button>';
			
			$this->html = $return;
		}
		
		if ($method == "get_visulisation_data") {

			$this->html = ui_data::createVisualisation($id);
		}
		
		if ($method == "visualize_explore_object_geo" || $method == "visualize_explore_object_soc" || $method == "visualize_explore_object_time") {

			$this->html = $this->getDynamicProjectDataTools(($method == "visualize_explore_object_geo" ? 'geo' : ($method == "visualize_explore_object_soc" ? 'soc' : 'time')), $id);
		}
		
		if ($method == "object_url") {
			
			$arr_id = explode("_", $id);
			$action = $arr_id[0];
			$type_id = $arr_id[1];
			$object_id = $arr_id[2];
			
			$url = $this->getObjectURL($type_id, $object_id);
			
			if ($action == 'get') {

				$return = '<h1>'.getLabel('lbl_url').'</h1><input type="text" value="'.$url.'">';	
						
			} else if ($action == 'share') {
				
				$arr_public_user_interface = SiteStartVars::getFeedback('arr_public_user_interface');
				$public_interface_name = Labels::parseTextVariables($arr_public_user_interface['interface']['name']);
				$arr_name = FilterTypeObjects::getTypeObjectNames($type_id, $object_id);
				$object_name = $arr_name[$object_id];

				$arr_shares = cms_nodegoat_public_interfaces::getPublicInterfaceShareOptions($public_interface_name.' - '.$object_name);
				
				$return = '<h1>'.getLabel('lbl_share').' '.$object_name.':</h1>';
				
				foreach ($arr_shares as $arr_share) {
					$return .= '<button class="share" type="button" data-href="'.$arr_share['share_url'].$url.'" title="'.$arr_share['share_name'].'">
						'.($arr_share['icon_class'] ? '<span class="'.$arr_share['icon_class'].'"></span>' : '<span class="icon">'.getIcon($arr_share['icon']).'</span>').'						
					</button>';
				}
							
			}
			
			$this->html = ui::createViewElm('<div class="url">'.$return.'</div>');
			
		}
		
		if ($method == "set_combined_references_filter") {
			
			$arr_objects = [];
			$arr_filters = $value;
			$target_type_id = $id;
			
			foreach ((array)$arr_filters as $arr_filter) {
				
				if (isset($arr_filter['active_object'])) { 
					
					$arr_ref_objects = self::getObjectReferences($arr_filter['active_object']['type_id'], $arr_filter['active_object']['object_id'], 'both', [$target_type_id => $target_type_id], true);
					
					if (count((array)$arr_ref_objects)) {
						
						$arr_objects = $arr_objects + $arr_ref_objects;
											
					}
										
				} else {
				
					$arr_filtered_objects = self::getTypeObjectIds($target_type_id, $arr_filter);
					$arr_objects = $arr_objects + $arr_filtered_objects;
				}
				
				
			}
			
			if (count((array)$arr_objects)) {
				
				$return = $this->createViewTypeObjectsList($target_type_id, ['objects' => array_keys((array)$arr_objects)], false);
				
			} else {
				
				$return = '<div>'.getLabel('msg_no_results').'</div>';
			}
			
			$this->html = $return;
			
		}
					
		if ($method == "data") {
	
			$arr_id = explode("_", $id);
			$type_id = (int)$arr_id[0];
			$feedback_filter_key = (int)$arr_id[1];

			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$use_custom_project_id = self::checkCustomProjectProjectId($type_id);
		
			if ($use_custom_project_id) {
				
				$public_user_interface_active_custom_project_id = $use_custom_project_id;
				
			} else {
				
				$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');	
			}
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
			$arr_type_set = StoreType::getTypeSet($type_id);
				
			$filter = new FilterTypeObjects($type_id, 'overview', true);
			$filter->setConditions('style', toolbar::getTypeConditions($type_id));
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($public_user_interface_active_custom_project_id)]);

			$arr_selection = [['object' => true, 'object_descriptions' => [], 'object_sub_details' => []]];
			
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if (!$arr_object_description['object_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
					continue;
				}
				
				$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
			}
			
			$filter->setSelection($arr_selection);

			if ($arr_project['types'][$type_id]['type_filter_id']) {

				$arr_use_project_ids = array_keys($arr_project['use_projects']);
					
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
				
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
			}


			if ($feedback_filter_key !== 0) {
				
				$arr_feedback_filters = SiteStartVars::getFeedback('arr_feedback_filters');
				$arr_feedback_filter = $arr_feedback_filters[$feedback_filter_key - 1];
			
				if (!$arr_feedback_filter) {
					
					$arr_feedback_filter = $_SESSION['data_view']['filter'][$type_id];
					
					$_SESSION['data_view']['filter'][$type_id] = false;
				}
			
				if ($arr_feedback_filter) {

					$arr_filter = $arr_feedback_filter;
					
				}
			}
			
			if ($_POST['search']) {
				$arr_filter['search'] .= ' '.$_POST['search'];
			}
			
			if ($arr_filter['object_versioning']['version']) {
				$filter->setVersioning('full');
			}
			if ($arr_filter) {
				$filter->setFilter($arr_filter);
			}
			
			if (isset($_POST['sorting_column_0'])) {
				
				$nr_column = $_POST['sorting_column_0'];
				$sorting_direction = $_POST['sorting_direction_0'];
				
			} else {
				
				$nr_column = 0;
				$sorting_direction = 'asc';
			}
		
			if ($nr_column == 0 && $arr_type_set['type']['object_name_in_overview']) { // Object name
				
				$filter->setOrder(['object_name' => $sorting_direction]);
			} else {
				
				$count_column = ($arr_type_set['type']['object_name_in_overview'] ? 1 : 0);
				
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
					if (!$arr_selection['object_descriptions'][$object_description_id] || ($arr_option_dynamic_object_descriptions && !$arr_option_dynamic_object_descriptions[$object_description_id])) {
						continue;
					}
					
					if ($nr_column == $count_column) {
						$filter->setOrder([$object_description_id => $sorting_direction]);
					}
					$count_column++;
				}
			}
			
			if (isset($_POST['nr_records_start']) && $_POST['nr_records_length'] != '-1') {
				$filter->setLimit([$_POST['nr_records_start'], $_POST['nr_records_length']]);
			}
			
			$arr = $filter->init();
			$arr_info = $filter->getResultInfo();

			$arr_output = [
				'echo' => intval($_POST['echo']),
				'total_records' => $arr_info['total'],
				'total_records_filtered' => $arr_info['total_filtered'],
				'data' => []
			];
			
			foreach ($arr as $arr_object) {
				
				$count = 0;
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:ui_data:show_project_type_object-'.$type_id.'_'.$arr_object['object']['object_id'].'';
				$arr_data['class'] = 'a quick';
				$arr_data['attr']['data-method'] = 'show_project_type_object';
				
				if ($arr_type_set['type']['object_name_in_overview']) {
					
					$arr_data['cell'][$count]['attr']['style'] = $arr_object['object']['object_name_style'];
					$arr_data[] = $arr_object['object']['object_name'];
					$count++;
				}
				
				foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
					
					$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
						
					if ($arr_object_definition['object_definition_style']) {
					
						if ($arr_object_definition['object_definition_style'] == 'hide') {
							
							$arr_data[] = '';
							$count++;
							
							continue;
						}
						
						$arr_data['cell'][$count]['attr']['style'] = $arr_object_definition['object_definition_style'];
					}
					
					if ($arr_object_description['object_description_ref_type_id']) {
						
						if ($arr_object_description['object_description_is_dynamic']) {
						
							$arr_html = [];
							
							foreach ($arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
							
								foreach($arr_ref_objects as $cur_object_id => $arr_reference) {
									
									$arr_html[] = $arr_reference['object_definition_ref_object_name'];
								}
							}
							
							$arr_data[] = implode(', ', $arr_html);
						} else if ($arr_object_description['object_description_has_multi']) {
							
							$arr_html = [];
							
							foreach ($arr_object_definition['object_definition_ref_object_id'] as $key => $value) {
								
								$arr_html[] = $arr_object_definition['object_definition_value'][$key];
							}
							
							$arr_data[] = implode(', ', $arr_html);
						} else {
							
							$arr_data[] = $arr_object_definition['object_definition_value'];
						}
					} else {
						
						$str_value = arrParseRecursive($arr_object_definition['object_definition_value'], ['Labels', 'parseLanguage']);
						
						$arr_data[] = StoreTypeObjects::formatToPreviewValue($arr_object_description['object_description_value_type'], $str_value);
					}

					
					$count++;
				}
				$arr_output['data'][] = $arr_data;
			}
			
			$this->data = $arr_output;
		}
		
	}
	
	private static function parsTag($tag_ids) {
	
		$arr_id = explode("|", $tag_ids);
		$arr_type_objects = [];
		
		foreach ($arr_id as $type_object_tag) {
			
			$arr_type_object_tag = explode("_", $type_object_tag);
			$type_id = $arr_type_object_tag[0];
			$object_id = $arr_type_object_tag[1];
			
			$arr_type_objects[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
		}
	
		return $arr_type_objects;
	}
	
	private static function getPublicInterfaceObject($type_id, $object_id) {

		if (!(int)$type_id || !(int)$object_id) {
			return false;
		}

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		
		$use_custom_project_id = self::checkCustomProjectProjectId($type_id);
	
		if ($use_custom_project_id) {
			
			$public_user_interface_active_custom_project_id = $use_custom_project_id;
			
		} else {
			
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');	
		}

		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		
		$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'view');
		$arr_ref_type_ids = cms_nodegoat_custom_projects::getProjectScopeTypes($public_user_interface_active_custom_project_id);

		$filter = new FilterTypeObjects($type_id, 'all', false, $arr_type_set);			
		$filter->setVersioning('added');
		$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => $arr_ref_type_ids]);
		$filter->setSelection(['object_sub_details' => []]);
		
		$filter->setFilter(['objects' => $object_id]);
		
		if ($arr_project['types'][$type_id]['type_filter_id']) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_UNDER_REVIEW) {
				
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
			}
		}

		$filter->setConditions('style_include', toolbar::getTypeConditions($type_id));

		$arr_object = current($filter->init());
		
		if ($arr_object) {
			
			$object_available = true;
			
		} else {
			
			$arr_name = FilterTypeObjects::getTypeObjectNames($type_id, $object_id);
			$object_name = $arr_name[$object_id];
			
			$arr_object['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id, 'object_name' => $object_name];			
		}
		
		// all in en out refs and store them!
		$arr_filter = ['referenced_object' => ['object_id' => [$object_id]]];
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);		
		$arr_reference_type_ids = array_unique(array_merge($arr_public_interface_project_types, $arr_public_interface_settings['types']['central_types'], $arr_public_interface_settings['types']['exploration_types']));
		
		foreach ((array)$arr_reference_type_ids as $ref_type_id) {
			
			$arr_object['object_referenced'][$ref_type_id] = self::getTypeObjectIds($ref_type_id, $arr_filter, false);
			
			if (in_array($ref_type_id, $arr_public_interface_settings['types']['exploration_types'])) {
				
				$arr_object['object_explore_referenced_references'][$ref_type_id] = $arr_object['object_referenced'][$ref_type_id];
			}
		}
			
		if ($object_available) {
						
			$arr_object['object']['type_id'] = $type_id;
			
			if ($arr_type_set['object_sub_details']) {
				$arr_object_subs_info = $filter->getInfoObjectSubs();			
				$arr_object['object_subs_info'] = $arr_object_subs_info[$object_id];
			}
			
			$object_thumbnail = self::getObjectsThumbnail([$object_id => $arr_object]);
			$arr_object['object_thumbnail'] = $object_thumbnail[$object_id]['object_thumbnail'];
			
			foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$arr_object_definition = $arr_object['object_definitions'][$object_description_id];
				
				if ((!$arr_object_definition['object_definition_value'] && !$arr_object_definition['object_definition_ref_object_id']) || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id) || $arr_object_definition['object_definition_style'] == 'hide') {
					continue;
				}
				
				if ($arr_object_definition['object_definition_ref_object_id']) {
					
					if ($arr_object_description['object_description_ref_type_id']) {
						
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_object_id) {
							
							if (is_array($ref_object_id)) {

								continue;
							}
							
							$ref_type_id = $arr_object_description['object_description_ref_type_id'];
							
							$arr_object['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
						}
							
					} else { 
				
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
							
							foreach ((array)$arr_ref_objects as $arr_ref_object) {
								
								$ref_object_id = $arr_ref_object['object_definition_ref_object_id'];
								
								$arr_object['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
							}	
						}
					}
					
					foreach ((array)$arr_object['object_references'] as $ref_type_id => $arr_object_references) {
						
						if (in_array($ref_type_id, $arr_public_interface_settings['types']['exploration_types'])) {
							
							foreach ((array)$arr_object_references as $ref_object_id => $arr_object_reference) {
								
								if (!$arr_object['object_explore_referenced_references'][$ref_type_id][$ref_object_id]) {
									
									$arr_object['object_explore_referenced_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
								
								}
							}
						}
					}
				}	
			}
		
		}
		
		return $arr_object;
	}
	
	private static function getObjectReferences($type_id, $object_id, $direction = 'both', $arr_reference_type_ids = false, $merge = false) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
				
		if (!$arr_reference_type_ids) {
						
			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);		
			$arr_reference_type_ids = array_unique(array_merge($arr_public_interface_project_types, $arr_public_interface_settings['types']['central_types'], $arr_public_interface_settings['types']['exploration_types']));		
		}
		
		if ($merge) {
			
			$arr = [];
			
		} else {
			
			$arr = ['object_referenced' => [], 'object_references' => [], 'count' => ['referenced' => 0, 'references' => 0]];
			
		}
		
		if ($direction == 'in' || $direction == 'both') {
			
			$arr_filter = ['referenced_object' => ['object_id' => [$object_id]]];
			
			foreach ((array)$arr_reference_type_ids as $ref_type_id) {
				
				if ($merge) {
					
					$arr = $arr + self::getTypeObjectIds($ref_type_id, $arr_filter, false);
					
				} else {
				
					$arr['object_referenced'][$ref_type_id] = self::getTypeObjectIds($ref_type_id, $arr_filter, false);
					$arr['count']['referenced'] += count((array)$arr['object_referenced'][$ref_type_id]);
				}
			}			
		}
		
		if ($direction == 'out' || $direction == 'both') {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$arr_selection = self::getTypeSelection($type_id, ['referencing' => true]);
			$filter = new FilterTypeObjects($type_id, 'all');			
			$filter->setVersioning('added');
			$filter->setSelection($arr_selection);
			$filter->setFilter(['objects' => $object_id]);	
			$arr_object = current($filter->init());
			
			foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$arr_object_definition = $arr_object['object_definitions'][$object_description_id];
				
				if ((!$arr_object_definition['object_definition_value'] && !$arr_object_definition['object_definition_ref_object_id']) || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id) || $arr_object_definition['object_definition_style'] == 'hide') {
					continue;
				}
				
				if ($arr_object_definition['object_definition_ref_object_id']) {
					
					if ($arr_object_description['object_description_ref_type_id']) {
						
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_object_id) {
							
							if (is_array($ref_object_id)) {

								continue;
							}
							
							$ref_type_id = $arr_object_description['object_description_ref_type_id'];
							
							if (in_array($ref_type_id, $arr_reference_type_ids)) {
								
								if ($merge) {
									
									$arr[$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
									
								} else {
									
									$arr['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
									$arr['count']['references']++;
								}
							}
						}
							
					} else { 
				
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
							
							foreach ((array)$arr_ref_objects as $arr_ref_object) {
								
								$ref_object_id = $arr_ref_object['object_definition_ref_object_id'];
								
								if (in_array($ref_type_id, $arr_reference_type_ids)) {
									
									if ($merge) {
										
										$arr[$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
										
									} else {
										
										$arr['object_references'][$ref_type_id][$ref_object_id]['object'] = ['type_id' => $ref_type_id, 'object_id' => $ref_object_id];
										$arr['count']['references']++;
									}
								}
							}	
						}
					}
				}	
			}
		}
		
		return $arr;
		
	}
	
	public static function getPublicInterfaceObjects($arr_type_ids = false, $arr_filter = false, $mix_types = true, $max = false, $min = false, $sort = true, $options = []) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');			
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);		
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		
		if (!$arr_type_ids) {
			$arr_type_ids = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		}
	
		$arr_objects = [];
		$arr_type_info = [];	
		
		$arr_active_filter = toolbar::getFilter();
	
		$sort_on_analysis = false;

		foreach ((array)$arr_type_ids as $type_id) {
			
			$arr_selection = self::getTypeSelection($type_id, ['media' => true]);
			
			$analysis_order_id = $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['sort'][$type_id];
	
			if ($analysis_order_id) {
				
				$sort_on_analysis = true;

				$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($public_user_interface_active_custom_project_id, 0, $type_id, $analysis_order_id, false);
				$arr_selection['object']['analysis'][] = ['analysis_id' => $arr_analysis['id'], 'user_id' => $arr_analysis['user_id']];
				
			}
	
			if ($arr_selection) {

				$filter = new FilterTypeObjects($type_id, 'all');
				$filter->setSelection($arr_selection);
				
			} else {
				
				$filter = new FilterTypeObjects($type_id, 'name');
			}
			
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($public_user_interface_active_custom_project_id)]);
			
			//Check if a project filter is present and if so set it
			if ($arr_project['types'][$type_id]['type_filter_id']) {

				$arr_use_project_ids = array_keys($arr_project['use_projects']);			
				$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
		
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
			}
				
			//Check if a current filter is present and if so set it
			if ($arr_filter) {

				$filter->setFilter($arr_filter);

			} else if (count((array)$arr_active_filter[$type_id])) { // Check if a pui filter is present and if so set it

				$filter->setFilter($arr_active_filter[$type_id]);
			}
			
			if ($sort_on_analysis) {
				
				$filter->setOrder(['object_analysis' => "desc"]);
				
			} else if ($sort) {
				
				$filter->setOrder(['object_name' => "asc"]);
				
			}

			if ($max && !$min) {
				if ($options['random']) {
					$filter->limitRandom($max);
				} else {
					$filter->setLimit($max);
				}
			} else if ($max && $min) {
				$filter->setLimit([$min, ($max-$min)]);
			}
			
			$arr_filtered_objects = $filter->init();
			$arr_type_info[$type_id] = $filter->getResultInfo();

			foreach ($arr_filtered_objects as $object_id => $arr_object) {

				if ($arr_object['object']['object_name']) {
										
					$arr_object['object']['type_id'] = $type_id;

					$arr_objects[$object_id] = $arr_object;	
				}
			}
		}
		
		if (!$options['no_thumbnails']) { // get a single image for  each object, either via it's own ODs, or via related media types.
			
			$arr_objects = self::getObjectsThumbnail($arr_objects);
		}
		
		if ($mix_types && $sort && count((array)$arr_objects) > 1) {
			
			if ($sort_on_analysis) {
				
				usort($arr_objects, function($a, $b) { return $a['object']['object_analysis'] < $b['object']['object_analysis']; }); 
				
			} else {
			
				usort($arr_objects, function($a, $b) { return strcmp($a['object']['object_name'], $b['object']['object_name']); }); 
			}
		}
	
		if (!$mix_types) {
			
			foreach ($arr_objects as $object_id => $arr_object) {
				
				$type_id = $arr_object['object']['type_id'];
				$arr_type_objects[$type_id][$object_id] = $arr_object;
			}
			
			$arr_objects = $arr_type_objects;
		}

		return ($options['info'] ? array('objects' => $arr_objects, 'info' => $arr_type_info) : $arr_objects);
	}
	
	private static function getObjectsThumbnail($arr_objects) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');			
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);		
		$arr_type_media_objects = [];
		
		foreach ($arr_objects as $object_id => $arr_object) {
			
			$type_id = $arr_object['object']['type_id']; 
			$arr_type_set = StoreType::getTypeSet($type_id);
			$image_filename = self::getObjectImage($arr_type_set, $arr_object);
						
			if ($image_filename) { // Object has image
				
				$arr_objects[$object_id]['object_thumbnail'] = SiteStartVars::getCacheUrl('img', [false, 200], $image_filename);
			
			} else { // Object has no image, check related objects

				foreach ((array)$arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {

					if ($arr_object_definition['object_definition_ref_object_id'] && in_array($arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id'], (array)$arr_public_interface_settings['types']['media_types'])) {
					
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $key => $object_definition_ref_object_id) {

							$ref_media_type_id = $arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id'];
							$ref_media_object_id = $object_definition_ref_object_id;
							
							if ($object_id != $ref_media_object_id) {
							
								$arr_type_media_objects[$ref_media_type_id][$ref_media_object_id][] = $object_id;
							}
						}
					}
				}
			}
		}
		
		// Get all images of related objects and assign them as thumbnails
		foreach ((array)$arr_type_media_objects as $media_type_id => $arr_media_objects) {
			
			$arr_thumbnail_objects = self::getPublicInterfaceObjects($media_type_id, ['objects' => array_keys($arr_media_objects)], true, false, false, false, ['no_thumbnails' => true]); // don't generate thumbnails for images!
			
			foreach ($arr_thumbnail_objects as $thumbnail_object_id => $arr_thumbnail_object) {
				
				$arr_type_set = StoreType::getTypeSet($media_type_id);
				$image_filename = self::getObjectImage($arr_type_set, $arr_thumbnail_object);
		
				if ($image_filename) {
					
					foreach ($arr_media_objects[$thumbnail_object_id] as $thumbnail_target_object_id) {
						$arr_objects[$thumbnail_target_object_id]['object_thumbnail'] = SiteStartVars::getCacheUrl('img', [false, 200], $image_filename);
					}
				}
			}
		}
		
		return $arr_objects;
	}
	
	private static function getObjectImage($arr_type_set, $arr_object) {
		
		$image_filename = false;
	
		foreach ((array)$arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {

			if ($arr_object_definition['object_definition_value'] && ($arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'] == 'media' || $arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'] == 'media_external')) {

				$value = (is_array($arr_object_definition['object_definition_value']) ? $arr_object_definition['object_definition_value'][0] : $arr_object_definition['object_definition_value']);
	
				$media = new EnucleateMedia($value);
		
				// check if media is image
				if ($media->enucleate(false, true) == 'image') {
				
					$image_filename = $media->enucleate(true);
				}
			
				if ($image_filename) {
					break;
				}
			}
		}
		
		return $image_filename;
	}
	
	private static function getTypeSelection($type_id, $arr_types) {
	
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
	
		$arr_type_set = StoreType::getTypeSet($type_id);
	
		$arr_selection = ['object' => ['all' => true], 'object_descriptions' => [], 'object_sub_details' => []];
		
		foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if ($arr_object_description['object_description_is_identifier']) {
				
				if ($arr_types['identifier']) {
					
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
				
			}
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view']) {
				continue;
			}
			
			if ($arr_object_description['object_description_ref_type_id']) {
				
				if ($arr_types['media'] && in_array($arr_object_description['object_description_ref_type_id'], (array)$arr_public_interface_settings['types']['media_types'])) {
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
				
				if ($arr_types['referencing']) {
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
				
			} else {
				
				if ($arr_types['media'] && ($arr_object_description['object_description_value_type'] == 'media' || $arr_object_description['object_description_value_type'] == 'media_external')) {
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
				
				if ($arr_types['referencing'] && $arr_object_description['object_description_value_type'] == 'text_tags') {
					$arr_selection['object_descriptions'][$object_description_id] = true;
					continue;
				}
			}
		}

		return $arr_selection;
	}
	
	private function getObjectURL($type_id, $object_id) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		
		$arr_selection = self::getTypeSelection($type_id, ['identifier' => true]);
		
		$identifier = false;
			
		if ($arr_selection) {
			
			$filter = new FilterTypeObjects($type_id, 'all');			
			$filter->setVersioning('added');
			$filter->setSelection($arr_selection);
			$filter->setFilter(['objects' => $object_id]);
			$arr_object = current($filter->init());

			foreach ((array)$arr_object['object_definitions'] as $arr_object_definition) {
			
				$identifier = $arr_object_definition['object_definition_value'];
				break;
			}
		}
		
		if (!$identifier) {
			
			$identifier = GenerateTypeObjects::encodeTypeObjectId($type_id, $object_id);
			
		}

		if ($identifier && $arr_public_interface_settings['short_url_host']) {

				$url = $arr_public_interface_settings['short_url_host'].'/'.$identifier;
				
		}
		
		if (!$url) {	
			
			$url = SiteStartVars::getBasePath(0, false).SiteStartVars::$page['name'].'.p/'.$public_user_interface_id.'/'.$public_user_interface_active_custom_project_id.'/object/'.$type_id.'-'.$object_id;
				
		}
		
		return $url;
	}
	
	private function getObjectCombinedReferencesFilters($arr_object, $target_type_id) {
		
		$type_id = $arr_object['object']['type_id'];
		$object_id = $arr_object['object']['object_id'];
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');		
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, true);

		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_target_type_set = StoreType::getTypeSet($target_type_id);
		
		$arr_matched_filter_types = [];
		
		// get all the related types of start type
		foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view']) {
				continue;
			}
						
			if ($arr_public_interface_project_filter_types[$arr_object_description['object_description_ref_type_id']]) {
				
				$arr_matched_filter_types[$arr_object_description['object_description_ref_type_id']][$type_id][] = $object_description_id;
			}
		}
		
		// check if related types are present in target type
		foreach ((array)$arr_target_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {

			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view']) {
				continue;
			}
						
			if ($arr_matched_filter_types[$arr_object_description['object_description_ref_type_id']] && $arr_public_interface_project_filter_types[$arr_object_description['object_description_ref_type_id']]) {
		
				$arr_matched_filter_types[$arr_object_description['object_description_ref_type_id']][$target_type_id][] = $object_description_id;
			}
		}
		
		// create filters based on common related types
		$arr_filter_objects = [];
		$arr_object_description_object_ids = [];
		
		foreach ((array)$arr_matched_filter_types as $filter_type_id => $arr_matched_filter_type) {
			
			$arr_type_object_descriptions = $arr_matched_filter_type[$type_id];
			$arr_target_object_descriptions = $arr_matched_filter_type[$target_type_id];
			
			if (!$arr_type_object_descriptions || !$arr_target_object_descriptions) {
				
				continue;
			}
			
			foreach ((array)$arr_type_object_descriptions as $type_object_description_key => $type_object_description_id) { 

				foreach ((array)$arr_object['object_definitions'][$type_object_description_id]['object_definition_ref_object_id'] as $key => $value) {

					foreach ((array)$arr_target_object_descriptions as $type_object_description_key => $target_type_object_description_id) { 
				
						// check if filter yields any results at all, if not it can be discarded as it will not allow for any valid single or combinational filters
						$arr_object_definitions_filter = ['object_filter' => ['object_definitions' => [$target_type_object_description_id => [$value]]]];
						
						$arr_test_result = self::getTypeObjectIds($target_type_id, $arr_object_definitions_filter, true);
						
						if ($arr_test_result['total_filtered'] == 0) {

							continue;
						}
						
						$arr_object_description_object_ids[] = $target_type_object_description_id.'-'.$value;		
						$arr_filter_objects[$target_type_object_description_id][$value] = ['type_id' => $filter_type_id, 'object_id' => $value, 'value' => (is_array($arr_object['object_definitions'][$type_object_description_id]['object_definition_value']) ? $arr_object['object_definitions'][$type_object_description_id]['object_definition_value'][$key] : $arr_object['object_definitions'][$type_object_description_id]['object_definition_value'])];
						
					}
				}
			}
		}

		$i = 1;
		$arr_object_description_ids = array_keys($arr_filter_objects);
		$arr_object_description_ids_sets = [];
		
		// create unique sets
		while ($i <= count((array)$arr_filter_objects)) {
			
			$arr_object_description_ids_sets[] = $this->createObjectDescriptionsObjectsSets($arr_object_description_object_ids, $i);
			$i++;	
		}
		
		$arr_combined_filters = [];
		
		foreach ($arr_object_description_ids_sets as $level => $arr_object_description_ids_set) {

			foreach ($arr_object_description_ids_set as $arr_object_description_object_ids) {				
				
				$arr_filter = [];
				$arr_elements = [];
				
				foreach ($arr_object_description_object_ids as $object_description_object_id) {
					
					$arr_object_description_object_id = explode('-', $object_description_object_id);
					
					$object_description_id = $arr_object_description_object_id[0];
					$object_id = $arr_object_description_object_id[1];
					
					$arr_elements[$object_id] = $arr_filter_objects[$object_description_id][$object_id];
					$arr_filter['object_filter']['object_definitions'][$object_description_id] = $object_id;
				}
				
				$arr_test_result = self::getTypeObjectIds($target_type_id, $arr_filter, true);
				
				if ($arr_test_result['total_filtered'] > 0) {
							
					$arr_combined_filters[$level + 1][] = ['arr_elms' => $arr_elements, 'filter' => $arr_filter, 'result' => $arr_test_result['total_filtered']];
				}
			}
		}
	
		return $arr_combined_filters;	
	}
	
	private function createObjectDescriptionsObjectsSets($arr_ids, $size) {  
		
		sort($arr_ids);
		
		$arr_object_description_object_sets = [];
		
		if ($size == 1) {
			
			return array_map(function ($v) { return [$v]; }, $arr_ids);
		}
		
		foreach ($this->createObjectDescriptionsObjectsSets($arr_ids, $size - 1) as $subset) {
			
			foreach ($arr_ids as $element) {
				
				if (!in_array($element, $subset)) {
					
					$new_arr = array_merge($subset, [$element]);
					
					$arr_object_description_ids = [];
					$valid_combination = true;
					
					foreach ($new_arr as $new_element) {
						
						$arr_object_description_object_ids = explode('-', $new_element);
						
						if (!isset($arr_object_description_ids[$arr_object_description_object_ids[0]])) {
							
							$arr_object_description_ids[$arr_object_description_object_ids[0]] = true;
							
						} else {
							
							$valid_combination = false;
							
						}
					}
					
					if ($valid_combination) {
						
						sort($new_arr);
						
						if (!in_array($new_arr, $arr_object_description_object_sets)) {
							
							$arr_object_description_object_sets[] = $new_arr;
							
						}
					}
				}
			}
		}
		
		return $arr_object_description_object_sets;
		
	}
	
	private function createCombinedReferencesFilters($target_type_id, $type_id, $object_id) {
	
		$arr_object = self::getPublicInterfaceObject($type_id, $object_id, true);

		if ($arr_object['object_explore_referenced_references'][$target_type_id]) {	
		
			$elm_filters = '<div>
						<input type="checkbox" data-filter="'.htmlspecialchars(json_encode(['active_object' => ['type_id' => $type_id, 'object_id' => $object_id]])).'" checked>
						<div>'.$arr_object['object']['object_name'].'</div>
						<span>'.count((array)$arr_object['object_explore_referenced_references'][$target_type_id]).'</span>
					</div>';	
		
		}
		
		// get all possible filters
		$arr_combined_filters = $this->getObjectCombinedReferencesFilters($arr_object, $target_type_id);	
		
		//start with most relevant (highest number of elements in set)
		krsort($arr_combined_filters);
		
		$i = 0;
		
		foreach ($arr_combined_filters as $level => $arr_filters) {

			foreach ($arr_filters as $arr_filter) {
	
				$elm_filters .= '<div>
							<input type="checkbox" data-filter="'.htmlspecialchars(json_encode($arr_filter['filter'])).'" '.($i < 3 && !count((array)$arr_object['object_explore_referenced_references'][$target_type_id]) ? ' checked ' : '').'>';

							foreach ($arr_filter['arr_elms'] as $arr_elm) {
								$elm_filters .= '<div class="keyword type-'.$arr_elm['type_id'].'">'.$arr_elm['value'].'</div>';
							}
				
				$elm_filters .= '<span>'.$arr_filter['result'].'</span>
						</div>';
				
				$i++;
			}
			
		}
		
		$return = '<div class="combined-filters">'.$elm_filters.'</div>
					<div class="list-results" id="y:ui_data:set_combined_references_filter-'.$target_type_id.'"></div>';
		
		return '<div data-method="combined_references_filter">'.$return.'</div>';
	}
	
	private static function getTypeObjectIds($type_id, $arr_filter, $results_only = false) {
		
		$use_custom_project_id = self::checkCustomProjectProjectId($type_id);
	
		if ($use_custom_project_id) {
			
			$public_user_interface_active_custom_project_id = $use_custom_project_id;
			
		} else {
			
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');	
		}
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($public_user_interface_active_custom_project_id);
		
		$filter = new FilterTypeObjects($type_id, 'id');
		$filter->setVersioning('added');
		$filter->setFilter($arr_filter);
		$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($public_user_interface_active_custom_project_id)]);	
				
		//Check if a project filter is present and if so set it
		if ($arr_project['types'][$type_id]['type_filter_id']) {

			$arr_use_project_ids = array_keys($arr_project['use_projects']);			
			$arr_type_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($public_user_interface_active_custom_project_id, false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
		
			$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_type_filter['object']));
		}
		
		$arr = [];
		
		if ($results_only) {
			
			$arr = $filter->getResultInfo();
			
		} else {
			
			$arr_objects = $filter->init();
			
			foreach ((array)$arr_objects as $object_id => $arr_object) {
				$arr[$object_id]['object'] = ['type_id' => $type_id, 'object_id' => $object_id];
			}
		}
		
		return $arr;
	}
	
	private static function checkCustomProjectProjectId($type_id) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');		
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		
		$use_custom_project_id = false;
		
		if (!$arr_public_interface_project_types[$type_id]) {
			
			$arr_public_user_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_user_interface_id);	
			
			foreach ((array)$arr_public_user_interface['project_types'] as $custom_project_id => $arr_project_types) {
				
				if ($arr_project_types[$type_id]) {
					
					if ($arr_public_interface_settings['projects'][$custom_project_id]['primary_project'][$type_id]) {
					
						$use_custom_project_id = $custom_project_id;
						
						break;
					}
				}				
			}
		}
		
		return $use_custom_project_id;
	}
	
	
}
