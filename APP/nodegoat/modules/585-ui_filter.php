<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ui_filter extends base_module {
	
	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	protected $arr_access = [
		'ui' => [],
		'ui_data' => []
	];
		
	public static function createFilter($project_id, $arr_public_user_interface) {

		$arr_settings = $arr_public_user_interface['interface']['settings']['projects'][$project_id];
		
		if (!$arr_settings['filter_mode']) {
			$arr_settings['filter_mode'] = 'search';
		}
		
		if ($arr_settings['filter_mode'] ==  'search') {
			$elm_filter = self::createFilterSearchBar($project_id, $arr_public_user_interface);
		} else if ($arr_settings['filter_mode'] ==  'form') {
			$elm_filter = self::createFilterForm($project_id, $arr_public_user_interface);
		} 

		$return = '<li class="project-filters '.$arr_settings['filter_mode'].'">
					<div id="y:ui_filter:filter-0">'.
						$elm_filter.
					'</div>
				</li>';
		
		return $return;
	}
	
	private static function createFilterSearchBar($project_id, $arr_public_user_interface) {
		
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$arr_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');			
		$arr_settings = $arr_public_user_interface['interface']['settings']['projects'][$project_id];

		$placeholder_text = ($arr_settings['info_search'] ?: getLabel('lbl_search'));

		$arr_filters[0] = [
			'type_id' => 0, 
			'filter_type_id' => 0, 
			'filter_id' => 0, 
			'operator' => 'OR', 
			'active_elements' => 0, 
			'placeholder_text' => $placeholder_text, 
			'url_filter' => ''
		];
						
		
		if ($arr_module_vars['set'] == 'filter' && $arr_module_vars['id']) {
						
			$arr_filters = self::parseUrlFilter($arr_filters, $arr_module_vars['id']);
		}
		
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $project_id, false);
		$arr_types = StoreType::getTypes();
		
		if ($arr_settings['select_types'] && count($arr_public_interface_project_types) > 1) {
			
			foreach ($arr_public_interface_project_types as $type_id) {
				$elm_select_types .= '<li>
										<input id="type-'.$type_id.'-toggle" type="checkbox" data-type_id="'.$type_id.'"/>
										<label for="type-'.$type_id.'-toggle">
											<span class="type-name">'.$arr_types[$type_id]['name'].'</span><span class="icon">'.getIcon('close').'</span>
										</label>
									</li>';
			}
			
			$elm_select_types = '<div class="select-types" id="y:ui_filter:select_types-0">
									<input id="types-toggle" type="checkbox" />
									<label for="types-toggle">
										<div>
											<span>Types</span>
											<span class="types-amount"></span>
										</div><div>
											<span class="icon">'.getIcon('down').'</span>
											<span class="icon">'.getIcon('up').'</span>
										</div>
									</label>
									<ul>'.
										$elm_select_types.
									'</ul>
								</div>';
		}	
		
		if ($arr_settings['info']) {
			
			$elm_button = '<button id="y:ui:view_text-project_'.$project_id.'" class="a quick"><span class="icon">'.getIcon('help').'</span></button>';
		}
		
		$return = $elm_select_types.
					'<div class="form-element" data-filter_id="0">
						<div class="input">'.
							'<div class="active-input">'.$arr_filters[0]['url_filter'].'</div>
							<div class="set-input">
								<input type="text" id="y:ui_filter:search-0" placeholder="'.strEscapeHTML($arr_filters[0]['placeholder_text']).'" data-filter_type_id="'.$arr_filters[0]['filter_type_id'].'"/>
							</div>
						</div>
						<div class="results hide"></div>
					</div>'.
					$elm_button;
		
		return $return;
	}
	
	private static function createFilterForm($project_id, $arr_public_user_interface) {

		$arr_module_vars = SiteStartVars::getFeedback('arr_public_user_interface_module_vars');	
		$arr_settings = $arr_public_user_interface['interface']['settings']['projects'][$project_id];
		$type_id = key($arr_public_user_interface['project_types'][$project_id]);
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_filters = [];
		
		foreach ((array)$arr_settings['filter_form'] as $arr_filter_from) {
			
			if ($arr_filter_from['form_element'] == 'description') {
	
				// Object Descriptions
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {

					$str_id = 'object_description_'.$object_description_id;
					
					if ($arr_filter_from['description'] == $str_id) {
						
						$placeholder_text = ($arr_filter_from['placeholder'] ?: Labels::parseTextVariables($arr_object_description['object_description_name']));
						$ref_type_id = ($arr_filter_from['description_ref_type_id'] ? : $arr_object_description['object_description_ref_type_id']);
						
						$filter_id = $type_id.'_OD_'.$object_description_id.'_'.$ref_type_id;
						$arr_filters[$filter_id] = [
													'type_id' => $type_id, 
													'filter_type_id' => $ref_type_id, 
													'filter_id' => $filter_id, 
													'value_type' => $arr_object_description['object_description_value_type_base'],
													'operator' => 'OR', 
													'active_elements' => 0, 
													'placeholder_text' => $placeholder_text, 
													'url_filter' => ''
												];
						
					}
				}
		
				// Object Sub Descriptions
				foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
						$str_id = 'object_sub_description_'.$object_sub_description_id;
		
						if ($arr_filter_from['description'] == $str_id) {
							
							$placeholder_text = ($arr_filter_from['placeholder'] ?: Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']));
							$ref_type_id = ($arr_filter_from['description_ref_type_id'] ? : $arr_object_sub_description['object_sub_description_ref_type_id']);
							
							$filter_id = $type_id.'_SOD_'.$object_sub_description_id.'_'.$ref_type_id;
							$arr_filters[$filter_id] = [
														'type_id' => $type_id, 
														'filter_type_id' => $ref_type_id, 
														'filter_id' => $filter_id, 
														'value_type' => $arr_object_sub_description['object_sub_description_value_type_base'],
														'operator' => 'OR', 
														'active_elements' => 0, 
														'placeholder_text' => $placeholder_text, 
														'url_filter' => ''
													];
							
						}
					}
				}				
						
				
			} else if ($arr_filter_from['form_element'] == 'object') {
				
				$placeholder_text = ($arr_filter_from['placeholder'] ?: Labels::parseTextVariables($arr_type_set['type']['name']));
						
				$filter_id = $type_id.'_O_0_'.$type_id;
				$arr_filters[$filter_id] = [
											'type_id' => $type_id, 
											'filter_type_id' => $type_id, 
											'filter_id' => $filter_id, 
											'operator' => 'OR', 
											'active_elements' => 0, 
											'placeholder_text' => $placeholder_text, 
											'url_filter' => ''
										];	
													
			} else if ($arr_filter_from['form_element'] == 'search') {
				
				$placeholder_text = ($arr_filter_from['placeholder'] ?: getLabel('lbl_search'));
				
				$filter_id = 0;
				$arr_filters[$filter_id] = [
											'type_id' => 0, 
											'filter_type_id' => 0, 
											'filter_id' => $filter_id, 
											'operator' => 'OR', 
											'active_elements' => 0, 
											'placeholder_text' => $placeholder_text,
											'url_filter' => ''
										];
													
			} else if ($arr_filter_from['form_element'] == 'date') {
				
				$filter_id = 'date';
				$arr_filters[$filter_id] = [
											'date_slider' => $arr_filter_from['date_slider'],
											'filter_id' => $filter_id, 
											'value_type' => 'date',
											'date_value_min' => false, 
											'date_value_max' => false, 
											'date_min' => $arr_filter_from['date_min'], 
											'date_max' => $arr_filter_from['date_max']
										];
			}
		
		}
		
		if ($arr_module_vars['set'] == 'filter' && $arr_module_vars['id']) {
			
			$arr_filters = self::parseUrlFilter($arr_filters, $arr_module_vars['id']);
		}

		foreach ($arr_filters as $arr_filter) {	
				
			$unique = uniqid();
			
			if ($arr_filter['filter_id'] === 'date') {
				
				$date_min_bounds = ($arr_filter['date_min'] ?: '1800');
				$date_max_bounds = ($arr_filter['date_max'] ?: '1900');
			
				$elm_form_elements .= '<div class="form-element" data-value-type="'.$arr_filter['value_type'].'">
							<div class="date" data-min_date="'.$date_min_bounds.'" data-max_date="'.$date_max_bounds.'">
								<input name="date-min" id="date-min" type="text" placeholder="'.$date_min_bounds.'" value="'.$arr_filter['date_value_min'].'" />
								<label for="date-min">'.getLabel('lbl_after').'</label>
								<input name="date-max" id="date-max" type="text" placeholder="'.$date_max_bounds.'" value="'.$arr_filter['date_value_max'].'" />
								<label for="date-max">'.getLabel('lbl_before').'</label>
							</div>
						</div>';	
							
			} else if ($arr_filter['value_type'] == 'int') {

				$elm_form_elements .= '<div class="form-element" data-value-type="'.$arr_filter['value_type'].'" data-filter_id="'.$arr_filter['filter_id'].'">
					<div class="input">'
						.'<input type="number" name="'.$arr_filter['filter_id'].'" value="'.($arr_filter['url_filter'] ?: 0).'" id="'.$arr_filter['filter_id'].'" />'
						.'<label>'.strEscapeHTML($arr_filter['placeholder_text']).'</label>'
					.'</div>
				</div>';
				
			} else if ($arr_filter['value_type'] == 'boolean') {

				$elm_form_elements .= '<div class="form-element" data-value-type="'.$arr_filter['value_type'].'" data-filter_id="'.$arr_filter['filter_id'].'">
					<div class="input">'
						.'<input type="radio" name="'.$arr_filter['filter_id'].'" value="1" id="'.$arr_filter['filter_id'].'1" '.($arr_filter['url_filter'] == 'yes' ? 'checked="checked" data-set="1"' : 'data-set="0"').'/>'
						.'<label for="'.$arr_filter['filter_id'].'1">Yes</label>'
						.'<input type="radio" name="'.$arr_filter['filter_id'].'" value="0" id="'.$arr_filter['filter_id'].'0" '.($arr_filter['url_filter'] == 'no' ? 'checked="checked" data-set="1"' : 'data-set="0"').'/>'
						.'<label for="'.$arr_filter['filter_id'].'0">No</label>'
						.'<label>'.strEscapeHTML($arr_filter['placeholder_text']).'</label>'
					.'</div>
				</div>';
				
			} else {

				$elm_form_elements .= '<div class="form-element" data-value-type="'.$arr_filter['value_type'].'" data-filter_id="'.$arr_filter['filter_id'].'">
					<div class="input">
						<div class="active-input">'
							.$arr_filter['url_filter'].
						'</div>
						<label>'.strEscapeHTML($arr_filter['placeholder_text']).'</label>
						<input id="operator-toggle-'.$unique.'" type="checkbox" class="operator" '.($arr_filter['operator'] == 'AND' ? 'checked="checked"' : '').' />
						<label for="operator-toggle-'.$unique.'" '.($arr_filter['active_elements'] > 1 ? '' : 'class="hide"').'>
							<span title="'.getLabel('lbl_public_interface_or_and_statement').'">OR</span>'.
							'<span title="'.getLabel('lbl_public_interface_and_or_statement').'">AND</span>
						</label>
						<div class="set-input">
							<input type="text" id="y:ui_filter:search-0" placeholder="'.strEscapeHTML($arr_filter['placeholder_text']).'" data-filter_type_id="'.$arr_filter['filter_type_id'].'"/>
						</div>
					</div>
					<div class="results hide"></div>
				</div>';
			}
		}

		if (!$arr_public_user_interface['interface']['settings']['filter_form_position'] || $arr_public_user_interface['interface']['settings']['filter_form_position'] == 'button') {
			
			$return = '<label for="form-toggle">Filter</label>
						<input id="form-toggle" type="checkbox" />
						<label for="form-toggle">
							<span class="icon">'.getIcon('down').'</span>
							<span class="icon">'.getIcon('up').'</span>
						</label>';
		}
		
		$return .= '<div>'.$elm_form_elements.'</div>';
					
					
					
		return $return;
		
	}
	
	private static function parseUrlFilter($arr_filters, $url_filter_string) {
	
		$arr_dates = [];
		if (preg_match_all("/\[([\d-]*)\]/", $url_filter_string, $arr_dates)) {
			
			$arr_filters['date']['date_value_min'] = $arr_dates[1][0];
			$arr_filters['date']['date_value_max'] = $arr_dates[1][1];
									
			$url_filter_string = preg_replace("/(\[[\d-]*\])/", '', $url_filter_string);
		}
		
		if ($url_filter_string) {
			
			$arr_filter_parts = explode('+', $url_filter_string);

			foreach ((array)$arr_filter_parts as $filter_part) {
		
				$arr_filter_part = explode(':', $filter_part);
			
				if (count((array)$arr_filter_part) > 1) {
					
					$filter_part_id_operator = $arr_filter_part[0];
					$arr_filter_part_id_operator = explode('-', $filter_part_id_operator);		
					
					$filter_part_id = $arr_filter_part_id_operator[0];
					$filter_part_operator = $arr_filter_part_id_operator[1];
					
					
					$arr_filter_part_elements = explode('|', $arr_filter_part[1]);		

				} else {
					
					$filter_part_id = 0;
					$arr_filter_part_elements = explode('|', $arr_filter_part[0]);
				}

				$url_filter = '';
				$active_elements = 0;
				
				foreach ((array)$arr_filter_part_elements as $filter_value) {
					
					$active_elements++;
					
					if (preg_match("/\d+-\d+/", $filter_value)) {
						
						$arr_type_object_id = explode('-', $filter_value);
						
						if (is_numeric($arr_type_object_id[0]) && is_numeric($arr_type_object_id[1])) {
							
							if (ui::isTypeEnabled($arr_type_object_id[0], false)) {
								
								$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($arr_type_object_id[0], $arr_type_object_id[1], GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE);
								
								$url_filter .= '<div class="keyword type-'.$arr_type_object_id[0].'" data-object_id="'.$arr_type_object_id[1].'" data-type_id="'.$arr_type_object_id[0].'"><span>'.$arr_type_object_names[$arr_type_object_id[1]].'</span><span class="icon">'.getIcon('close').'</span></div>';
							}
						}
						
					} else if (is_numeric($filter_value) || in_array($filter_value, ['yes', 'no'])) {
						
						$url_filter = $filter_value;
						
					} else {
						
						$url_filter .= '<div class="string"><span>'.strEscapeHTML(str_replace('~', ' ', $filter_value)).'</span><span class="icon">'.getIcon('close').'</span></div>';
					}
				}
				
				$arr_filters[$filter_part_id]['url_filter'] = $url_filter;
				$arr_filters[$filter_part_id]['operator'] = $filter_part_operator;
				$arr_filters[$filter_part_id]['active_elements'] = $active_elements;
			}
		}
		
		return $arr_filters;		
	}
	
	public static function css() {
		
		$return = ' 
					.ui li[class*="type-"] {  background-color: rgba(255, 255, 255, 0.75); } /* set default colour for elements expecting a type colour */ 
					
					.ui nav li.project-dynamic-nav ul li.project-filters[data-active="true"] ~ li,
					.ui nav li.project-dynamic-nav ul li.project-filters[data-object_active="true"] ~ li { display: none; }	
					
					.ui nav li.project-filters { position: relative; background-color: #a3ce6c; width: 100%; z-index: 2; }

					.ui nav li.project-filters.search { height: 100px; }
					.ui nav li.project-filters.search > div { position: absolute; top: 25px; left: 0px; width: 100%; display: flex; justify-content: center; padding: 0 5px; box-sizing: border-box; }
 
					.ui nav li.project-filters.search > div .select-types {  }
					.ui nav li.project-filters.search > div .select-types input { display: none; }
					.ui nav li.project-filters.search > div .select-types > input:not(:checked) + label + ul { display: none; }
					.ui nav li.project-filters.search > div .select-types > input:checked + label + ul { display: block; }
					.ui nav li.project-filters.search > div .select-types > label { cursor: pointer; width: 150px; height: 50px; background-color: #0096e4; box-sizing: border-box; margin: 0; vertical-align: top; line-height: 50px;}
					.ui nav li.project-filters.search > div .select-types > label > div:nth-of-type(1) { display: inline-block; width: calc(100% - 50px); height: 100%; box-sizing: border-box; }
					.ui nav li.project-filters.search > div .select-types > label > div:nth-of-type(1) > span { display: inline; color: #fff; font-size: 14px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
					.ui nav li.project-filters.search > div .select-types > label > div:nth-of-type(1) > span:nth-of-type(1) { padding-left: 10px; }
					.ui nav li.project-filters.search > div .select-types > label > div:nth-of-type(2) { display: inline-block; width: 50px; height: 100%; box-sizing: border-box; }
					.ui nav li.project-filters.search > div .select-types > label > div:nth-of-type(2) > span { display: none; color: #fff; width: 50px; height: 50px; box-sizing: border-box; text-align: center; }
					.ui nav li.project-filters.search > div .select-types > label > div:nth-of-type(2) > span > svg { height: 15%; }
					.ui nav li.project-filters.search > div .select-types > input:not(:checked) + label > div:nth-of-type(2) > span:nth-of-type(1) { display: inline-block;  }
					.ui nav li.project-filters.search > div .select-types > input:checked + label > div:nth-of-type(2) > span:nth-of-type(2) { display: inline-block;  }
					.ui nav li.project-filters.search > div .select-types > ul { position: relative; width: 150px; margin-top: 5px; display: block; overflow-x: hidden; overflow-y: auto; background-color: rgba(238, 238, 238, 0.75); padding: 10px 10px 0 10px; box-sizing: border-box; }
					.ui nav li.project-filters.search > div .select-types > ul > li { margin-bottom: 10px; box-sizing: border-box; height: 20px; }
					.ui nav li.project-filters.search > div .select-types > ul > li > label { cursor: pointer; display: inline-block; width: 100%; line-height: 20px; margin: 0; box-sizing: border-box;} 
					.ui nav li.project-filters.search > div .select-types > ul > li > label > span.type-name { display: inline-block; width: calc(100% - 20px); font-weight: normal; } 
					.ui nav li.project-filters.search > div .select-types > ul > li > label > span.icon { display: none; color: #333; width: 20px; height: 20px; text-align: center; box-sizing: border-box; border: 1px solid #333; border-radius: 20px;  } 
					.ui nav li.project-filters.search > div .select-types > ul > li > label > span.icon > svg { height: 35%; }
					.ui nav li.project-filters.search > div .select-types > ul > li > input:checked + label > span.type-name { font-weight: bold; } 
					.ui nav li.project-filters.search > div .select-types > ul > li > input:checked + label > span.icon { display: inline-block; } 
					.ui nav li.project-filters.search > div .select-types > ul > li > input:not(:checked) + label > span.icon { display: none; } 

					.ui nav li.project-filters.search > div .form-element { flex: 1 1 10px; min-width: 10px; max-width: 80vw; margin: 0 5px;  }
					.ui nav li.project-filters.search > div .form-element .input { box-sizing: border-box; display: inline-block; width: 100%; min-width: 200px; height: 50px; padding: 0 0 0 50px; background: url("/CMS/css/images/icons/search.svg") no-repeat scroll 20px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap; }
					.ui nav li.project-filters.search > div .form-element .input .active-input { display: flex; overflow: hidden; max-width: 70%; box-sizing: border-box; margin: 0px; padding: 0px; height: 50px; float: left; }
					.ui nav li.project-filters.search > div .form-element .input .active-input > div { display: inline-block; min-width: 30px; box-sizing: border-box; white-space: nowrap; margin: 10px 3px; padding: 0px 5px; height: 30px; font-size: 16px; line-height: 30px; vertical-align: middle;}
					.ui nav li.project-filters.search > div .form-element .input .active-input > div.string { background-color: #ccc; }
					.ui nav li.project-filters.search > div .form-element .input .active-input > div > span { display: inline-block; }
					.ui nav li.project-filters.search > div .form-element .input .active-input > div > span:first-child { width: calc(100% - 24px); overflow: hidden; text-overflow: ellipsis; }
					.ui nav li.project-filters.search > div .form-element .input .active-input > div > span.icon { cursor: pointer; border-left: 1px solid #444; margin-left: 5px; padding: 0 3px 0 6px; vertical-align: top; }
					.ui nav li.project-filters.search > div .form-element .input .active-input > div > span.icon svg { height: 11px;  }
					.ui nav li.project-filters.search > div .form-element .input .set-input { overflow: auto; margin-right: 50px; height: 50px; min-width: 30%;}
					.ui nav li.project-filters.search > div .form-element .input .set-input input { width: 100%; box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 16px; height: 50px; border: 0px; box-sizing: border-box; overflow: hidden; text-overflow: ellipsis; }
					.ui nav li.project-filters.search > div button { width: 50px; height: 50px; background-color: #0096e4; border: 0; text-align: center; vertical-align: middle;  }					
					.ui nav li.project-filters.search > div button span { color: #fff; }					
					.ui nav li.project-filters.search > div button span svg { height: 50%; }					

					.ui nav li.project-filters .results { position: relative; width: 100%; min-width: 200px; margin-top: 5px; display: block; overflow-x: hidden; overflow-y: auto; background-color: rgba(238, 238, 238, 0.75);  }
					.ui nav li.project-filters .results:empty { display: none; }
					.ui nav li.project-filters .results .object-thumbnail { position: relative; width: 100%; background-color: rgba(238, 238, 238, 0.5); margin: 3px 0 2px 10px; height: 50px; white-space: nowrap; overflow: hidden;}
					.ui nav li.project-filters .results .object-thumbnail > div { position: relative; width: 100%; vertical-align: middle; height: 50px; }
					.ui nav li.project-filters .results .object-thumbnail > div > div { position: relative; box-sizing: border-box; display: inline-block; vertical-align: middle; height: 50px; }
					.ui nav li.project-filters .results .object-thumbnail > div > div.image { width: 50px; background-color: #ddd; background-repeat: no-repeat; background-position: center 10%; background-size: cover; }
					.ui nav li.project-filters .results .object-thumbnail > div > div.image span { height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3em; font-family: serif; }
					.ui nav li.project-filters .results .object-thumbnail > div > div.name { max-width: calc(100% - 80px); margin-left: 5px; line-height: 50px; font-size: 18px; overflow: hidden; text-overflow: ellipsis;}
					.ui nav li.project-filters .results .object-thumbnail > div > div.object-definitions { display: none; }
					.ui nav li.project-filters .results ul.keywords { display: flex; }
					.ui nav li.project-filters .results ul.keywords > li { overflow: hidden; }
					.ui nav li.project-filters .results ul.keywords > li > ul { display: flex; flex-flow: wrap; white-space: normal; vertical-align: top; padding: 5px; }
					.ui nav li.project-filters .results ul.keywords > li > ul > li { cursor: pointer; display: inline-block; padding: 4px 8px; margin: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
					.ui nav li.project-filters .results ul.keywords > li > ul > li:not(.separator):not(.info) { max-width: 40%; }
					.ui nav li.project-filters .results ul.keywords li.info { cursor: default; }
					.ui nav li.project-filters .results ul.keywords .separator { display: none; cursor: default; font-variant: small-caps; }
					.ui nav li.project-filters .results > p { padding: 10px; cursor: pointer; }

					.ui nav > nav > ul > li.project-dynamic-nav ul li.project-filters[data-active="true"] ~ li { display: none; }
				
					.ui nav li.project-filters.form > div { display: flex; justify-content: flex-start; height: auto; min-height: 46px; font-family: var(--font-mono); }
														  
					.ui nav li.project-filters.form > div > input { display: none; }
					.ui nav li.project-filters.form > div > label:first-child { cursor: pointer; margin: 5px 0 0 5px; height: 36px; line-height: 36px; padding: 0 15px; font-weight: bold; letter-spacing: 2px; color: #333; text-transform: uppercase;  background-color: #fff;  }
					.ui nav li.project-filters.form > div > input + label { margin: 5px 0 0 0; cursor: pointer; background-color: #fff; height: 36px; }
					.ui nav li.project-filters.form > div > input + label > span { display: none; background-color: #0096e4; color: #fff; width: 36px; height: 36px; box-sizing: border-box; text-align: center; padding-top: 10px; }
					.ui nav li.project-filters.form > div > input + label > span > svg { width: 50%; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label > span:first-child { display: inline-block; }
					.ui nav li.project-filters.form > div > input:checked + label > span:last-child { display: inline-block; }

					.ui nav li.project-filters.form > div > input:not(:checked) + label + div {  display: flex; margin: 0 0 0 5px; padding: 0; width: auto; position: relative; top: 0; white-space: normal; flex-wrap: wrap; align-content: flex-start; min-height: 36px; } 
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element { flex: 0 1 auto; margin: 5px 0 0 0; min-width: 0px; }  
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input { display: flex; background: none; min-width: 0px; padding: 0px; width: auto; height: auto; white-space: normal; }  
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > .active-input { order: 2; display: inline-block; height: 36px; max-width: 100%; overflow: visible; white-space: normal; } 
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > .active-input:not(:empty) { background-color: rgba(255, 255, 255, 0.6); padding: 5px; margin-right: 5px; box-sizing: border-box; } 
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > .active-input > div { width: auto; display: inline-block; margin: 3px 3px 0 0; padding: 0; height: auto; line-height: 1; background-color: #fff; vertical-align: middle; } 
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > .active-input > div span:first-child { width: auto; height: 100%; vertical-align: middle; font-size: 12px; line-height: 18px; background-color: #fff; padding: 0 4px;  } 
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > .active-input > div span.icon { display: none; } 
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > .set-input { display: none; } 
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > input { display: none; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > label { order: 1;  display: inline-block; height: 36px; line-height: 36px; padding: 0 15px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; background-color: #0096e4; color: #fff;}
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > input + label { display: none; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element .input > .active-input:empty + label { display: none; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="int"],
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="boolean"] { margin-right: 5px; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="boolean"] .input > label { display: none; margin: 0px; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="boolean"] .input > input:checked + label { pointer-events: none; order: 2; display: inline-block; background-color: rgba(255, 255, 255, 0.6); color: #333; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="boolean"] .input > input:checked + label + input + label + label,
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="boolean"] .input > input:checked + label + label { display: inline-block; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="int"] .input > label { display: none; pointer-events: none; order: 1; margin: 0px; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .form-element[data-value-type="int"] .input > input { display: none; pointer-events: none; order: 2; height: 36px; width: 60px; padding: 10px; background-color: rgba(255, 255, 255, 0.6); color: #333; }
					
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date { display: inline-block; display: flex; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input { background-color: rgba(255, 255, 255, 0.6); width: 90px; min-width: 10px; border: 0; height: 36px; padding: 0 10px; margin: 0; pointer-events: none;  text-align: center; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input + label { display: inline-block; height: 36px; line-height: 36px; padding: 0 15px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; background-color: #0096e4; color: #fff; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:first-child { order: 2;  }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:nth-of-type(2) { order: 4;  }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:first-child + label { order: 1; margin-left: 0px; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:nth-of-type(2) + label { order: 3;  }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:placeholder-shown,
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:placeholder-shown + label { display: none; }
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:nth-of-type(3),
					.ui nav li.project-filters.form > div > input:not(:checked) + label + div .date > input:nth-of-type(3) + label { display: none; }

					.ui nav li.project-filters.form > div > input:checked + label + div { position: absolute; width: 30%; min-width: 800px; left: 5px; top: 41px; box-sizing: border-box; flex-direction: column; background-color: #fff; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element { flex: 0; background-color: #a3ce6c; padding: 10px; height: auto; margin: 0 10px 10px 10px; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element:first-child { margin-top: 10px; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input { display: flex; background: none; padding: 0; margin: 0; height: auto; white-space: normal; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .active-input + label { display: none; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .set-input { order: 2; height: 40px; margin: 0 10px 0 0; padding: 0 0 0 40px; background: url("/CMS/css/images/icons/search.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .set-input input { width: 100%;  box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 14px; height: 40px; border: 0; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .set-input input::placeholder {  }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .active-input { order: 3; background: none; padding: 0; margin: 0; display: inline-block; height: auto; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .active-input > div { display: inline-block; min-width: 30px; width: auto; box-sizing: border-box; white-space: nowrap; margin: 0 5px 5px 0px; background-color: #eee; padding: 0 0 0 5px; height: 30px; font-size: 16px; line-height: 30px; vertical-align: middle;}
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .active-input > div span:first-child { width: auto; max-width: 150px; color: #333; font-size: 12px; padding: 0 4px; display: inline-block; overflow-x: hidden; text-overflow: ellipsis;}
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .active-input > div span.icon { cursor: pointer; background-color: #ddd; color: #666; padding: 0 6px; border: 0; vertical-align: top; width: 22px; box-sizing: border-box; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .active-input > div span.icon svg { height: 11px; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > input { display: none; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > .active-input + label + input + label { display: inline-block; border: 2px solid #0096e4; order: 4; padding: 0; height: 23px; cursor: pointer; white-space: nowrap; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > input + label > span { display: inline-block; font-family: var(--font-mono); font-weight: bold; font-size: 10px; padding: 5px; color: #333; box-sizing: border-box; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6); transition: background-color 100ms ease;   }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > input:not(:checked) + label > span:first-child,
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element .input > input:checked + label > span:last-child {  background-color: #0096e4; color: #fff; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="int"] .input,
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="boolean"] .input { display: flex; height: 40px; padding: 0px; box-sizing: border-box; white-space: nowrap; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="int"] .input > label,
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="boolean"] .input > label { order: 2; font-family: var(--font-mono); display: inline-block; font-size: 14px; height: 40px; border: 0; margin: 0; padding: 10px; box-sizing: border-box; background-color: rgba(255, 255, 255, 0.6); }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="boolean"] .input > input + label {  cursor: pointer; height: 40px; width: 50px; margin: 0; border-left: 1px solid rgba(0, 0, 0, 0.1); line-height: 20px; text-align: center; font-weight: bold; font-size: 12px; color: #333; box-sizing: border-box; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6); transition: background-color 100ms ease;  }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="boolean"] .input > label:nth-of-type(2) { }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="boolean"] .input > label:nth-of-type(3) { color: rgba(0, 0, 0, 0.6);  order: 1; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="boolean"] .input > input:checked + label { background-color: #0096e4; color: #fff;  }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="int"] .input > label { color: rgba(0, 0, 0, 0.6);  order: 1;  }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="int"] .input > input { display: inline-block;  box-shadow: none; border: 0; border-radius: 0;  order: 3; height: 40px; width: 60px; background-color: rgba(255, 255, 255, 0.6); margin: 0; padding: 10px; box-sizing: border-box; font-size: 14px; }
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="int"] .input > input:hover,
					.ui nav li.project-filters.form > div > input:checked + label + div .form-element[data-value-type="int"] .input > input:focus { box-shadow: none; border: 0; border-radius: 0; }
					.ui nav li.project-filters.form > div > input:checked + label + div .results { margin-top: 0px; background-color: rgba(255, 255, 255, 0.6); }
					.ui nav li.project-filters.form > div > input:checked + label + div .results > p { display: none; }
					
					.ui nav li.project-filters.form > div > input:checked + label + div .date { display: flex; white-space: normal; flex-wrap: wrap; align-content: flex-start; }
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input { display: inline-block; box-shadow: none; display: inline-block; font-size: 14px; height: 40px; width: auto; margin: 0; padding: 0 0 0 40px; border: 0; background: url("/CMS/css/images/icons/date.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap;  }
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(1) { order: 2;  }
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(2) { order: 4;  }
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(1) + label { order: 1; margin-left: 0px; } 
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(1) + label,
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(2) + label { height: 40px; font-weight: bold; letter-spacing: 2px; font-size: 12px; padding: 0 0 0 10px; line-height: 40px; box-sizing: border-box; color: #333; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6);  } 
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(2) + label { order: 3;  } 
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(3) { display: none;  } 
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(3) + label { order: 5; position: relative; height: 40px; width: 40px; background-color: #0096e4; cursor: pointer; text-align: center; } 
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(3) + label > span.icon { color: #fff; height: 20px; margin-top: 10px;} 
					.ui nav li.project-filters.form > div > input:checked + label + div .date > input:nth-of-type(3) + label > span.icon > svg { height: 100%; } 

					.ui nav.top li.project-filters.form { height: 100px; }
					.ui nav.top li.project-filters.form > div { position: absolute; width: 100%; box-sizing: border-box; display: flex; flex-direction: row; }
					.ui nav.top li.project-filters.form > div > div { width: 100%; box-sizing: border-box; display: flex; flex-direction: row; }
					.ui nav.top li.project-filters.form > div > div .form-element { flex: 1; padding: 10px; height: auto; margin: 0 10px 0 0; }
					.ui nav.top li.project-filters.form > div > div .form-element:first-child { margin-top: 0px; }
					.ui nav.top li.project-filters.form > div > div .form-element .input { display: flex; background: none; padding: 0; margin: 0; height: auto; white-space: normal; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .active-input + label { display: none; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .set-input { order: 2; height: 40px; margin: 0 10px 0 0; padding: 0 0 0 40px; background: url("/CMS/css/images/icons/search.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .set-input input { width: 100%; box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 14px; height: 40px; border: 0; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .active-input { order: 3; background: none; padding: 0; margin: 0; display: inline-block; height: auto; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .active-input > div { display: inline-block; min-width: 30px; width: auto; box-sizing: border-box; white-space: nowrap; margin: 0 5px 5px 0px; background-color: #eee; padding: 0 0 0 5px; height: 30px; font-size: 16px; line-height: 30px; vertical-align: middle;}
					.ui nav.top li.project-filters.form > div > div .form-element .input > .active-input > div span:first-child { width: auto; color: #333; font-size: 14px; padding: 0 4px; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .active-input > div span.icon { cursor: pointer; background-color: #ddd; color: #444; padding: 0 6px; border: 0;  }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .active-input > div span.icon svg { height: 11px; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > input { display: none; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > .active-input + label + input + label { display: inline-block; border: 2px solid #0096e4; order: 4; padding: 0; height: 23px; cursor: pointer; white-space: nowrap; }
					.ui nav.top li.project-filters.form > div > div .form-element .input > input + label > span { display: inline-block; font-weight: bold; font-size: 10px; padding: 5px; color: #333; box-sizing: border-box; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6); transition: background-color 100ms ease;   }
					.ui nav.top li.project-filters.form > div > div .form-element .input > input:not(:checked) + label > span:first-child {  background-color: #0096e4; color: #fff;  }
					.ui nav.top li.project-filters.form > div > div .form-element .input > input:checked + label > span:last-child {  background-color: #0096e4; color: #fff; }
					
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input { display: flex; height: 40px; padding: 0px; box-sizing: border-box; white-space: nowrap; }
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > label { order: 2; font-family: var(--font-mono); display: inline-block; font-size: 14px; height: 40px; border: 0; margin: 0; padding: 10px; box-sizing: border-box; background-color: rgba(255, 255, 255, 0.6); }
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > input + label {  cursor: pointer; height: 40px; width: 50px; margin: 0; border-left: 1px solid rgba(0, 0, 0, 0.1); line-height: 20px; text-align: center; font-weight: bold; font-size: 12px; color: #333; box-sizing: border-box; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6); transition: background-color 100ms ease;  }
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > label:nth-of-type(3) { color: rgba(0, 0, 0, 0.6);  order: 1; }
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > input:checked + label { background-color: #0096e4; color: #fff;  }
					
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="int"] .input { height: 40px; margin: 0 10px 0 0; white-space: nowrap; }
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="int"] .input > label { order: 1; display: inline-block; font-size: 14px; height: 40px; border: 0; line-height: 40px; color: rgba(0, 0, 0, 0.6); padding: 0 10px; box-sizing: border-box; background-color: rgba(255, 255, 255, 0.6);  }
					.ui nav.top li.project-filters.form > div > div .form-element[data-value-type="int"] .input > input { order: 2; width: 60px; box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 14px; height: 40px; border: 0; padding: 10px; background-color: rgba(255, 255, 255, 0.6);  }
					
					.ui nav.top li.project-filters.form > div > div .results { margin-top: 0px; background-color: rgba(255, 255, 255, 0.6); }
					.ui nav.top li.project-filters.form > div > div .results > p { display: none; }
					
					.ui nav.top li.project-filters.form > div > div > .form-element > .date { display: flex; white-space: normal; flex-wrap: nowrap; align-content: flex-start; }
					.ui nav.top li.project-filters.form > div > div > .form-element > .date > input { display: inline-block; box-shadow: none; font-size: 14px; height: 40px; max-width: 200px; margin: 0 0 5px 0; padding: 0 0 0 40px; border: 0; background: url("/CMS/css/images/icons/date.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap;  }
					.ui nav.top li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(1) { order: 2;  }
					.ui nav.top li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(2) { order: 4;  }
					.ui nav.top li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(1) + label { order: 1; } 
					.ui nav.top li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(1) + label,
					.ui nav.top li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(2) + label { margin-left: 5px; width: 75px; height: 40px; font-weight: bold; letter-spacing: 2px; font-size: 12px; padding: 0 0 0 10px; line-height: 40px; box-sizing: border-box; color: #333; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6);  } 
					.ui nav.top li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(2) + label { order: 3;  } 
					
					.ui nav.left li.project-filters.form > div > div { position: relative; width: 100%; box-sizing: border-box; flex-direction: column; background-color: #a3ce6c;}
					.ui nav.left li.project-filters.form > div > div .form-element { flex: 0; background-color: rgba(255, 255, 255, 0.1); padding: 5px; height: auto; margin: 0 5px 10px 5px; }
					.ui nav.left li.project-filters.form > div > div .form-element:first-child { margin-top: 10px; }
					.ui nav.left li.project-filters.form > div > div .form-element .input { display: block; background: none; padding: 0; margin: 0; height: auto; white-space: normal; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .active-input + label { display: none; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .set-input { height: 40px; margin: 0; padding: 0 0 0 40px; background: url("/CMS/css/images/icons/search.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .set-input input { width: 100%; box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 14px; height: 40px; border: 0; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .active-input { background: none; padding: 0; margin: 0; display: inline; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .active-input > div { display: inline-block; min-width: 30px; width: auto; max-width: 100%; overflow: hidden; box-sizing: border-box; white-space: nowrap; margin: 0 5px 5px 0px; background-color: #eee; padding: 0 0 0 5px; height: 30px; font-size: 16px; line-height: 30px; vertical-align: middle;}
					.ui nav.left li.project-filters.form > div > div .form-element .input > .active-input > div span:first-child { display: inline-block; width: auto; max-width: calc(100% - 30px); color: #333; font-size: 14px; padding: 0 4px; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .active-input > div span.icon { cursor: pointer; background-color: #ddd; color: #444; padding: 0 6px; border: 0;  }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .active-input > div span.icon svg { height: 11px; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > input { display: none; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > .active-input + label + input + label { display: inline-block; border: 2px solid #0096e4; order: 4; padding: 0; cursor: pointer; white-space: nowrap; }
					.ui nav.left li.project-filters.form > div > div .form-element .input > input + label > span { display: inline-block; font-weight: bold; font-size: 10px; padding: 5px; color: #333; box-sizing: border-box; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6); transition: background-color 100ms ease;   }
					.ui nav.left li.project-filters.form > div > div .form-element .input > input:not(:checked) + label > span:first-child {  background-color: #0096e4; color: #fff;  }
					.ui nav.left li.project-filters.form > div > div .form-element .input > input:checked + label > span:last-child {  background-color: #0096e4; color: #fff; }
					
					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input { display: flex; height: 40px; padding: 0px; box-sizing: border-box; white-space: nowrap; }
					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > label { order: 2; font-family: var(--font-mono); display: inline-block; font-size: 14px; height: 40px; border: 0; margin: 0; padding: 10px; box-sizing: border-box; background-color: rgba(255, 255, 255, 0.6); }
					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > input + label {  cursor: pointer; height: 40px; width: 50px; margin: 0; border-left: 1px solid rgba(0, 0, 0, 0.1); line-height: 20px; text-align: center; font-weight: bold; font-size: 12px; color: #333; box-sizing: border-box; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6); transition: background-color 100ms ease;  }
					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > label:nth-of-type(3) { color: rgba(0, 0, 0, 0.6);  order: 1; }
					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="boolean"] .input > input:checked + label { background-color: #0096e4; color: #fff;  }

					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="int"] .input { display: flex; height: 40px; margin: 0 10px 0 0; white-space: nowrap; }
					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="int"] .input > label { order: 1; display: inline-block; font-size: 14px; height: 40px; border: 0; line-height: 40px; color: rgba(0, 0, 0, 0.6); padding: 0 10px; box-sizing: border-box; background-color: rgba(255, 255, 255, 0.6);  }
					.ui nav.left li.project-filters.form > div > div .form-element[data-value-type="int"] .input > input { order: 2; width: 60px; box-shadow: none; display: inline-block; padding: 0px; background-color: transparent; font-size: 14px; height: 40px; border: 0; padding: 10px; background-color: rgba(255, 255, 255, 0.6);  }
					
					.ui nav.left li.project-filters.form > div > div .results { margin-top: 0px; background-color: rgba(255, 255, 255, 0.6); width: 40vw; max-width: 750px; }
					.ui nav.left li.project-filters.form > div > div .results > p { display: none; }
					
					.ui nav.left li.project-filters.form > div > div > .form-element > .date { display: flex; white-space: normal; flex-wrap: wrap; align-content: flex-start; }
					.ui nav.left li.project-filters.form > div > div > .form-element > .date > input { display: inline-block; box-shadow: none; display: inline-block; font-size: 14px; height: 40px; width: calc(100% - 75px); margin: 0 0 5px 0; padding: 0 0 0 40px; border: 0; background: url("/CMS/css/images/icons/date.svg") no-repeat scroll 10px center / 20px 20px rgba(255, 255, 255, 0.6); white-space: nowrap;  }
					.ui nav.left li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(1) { order: 2;  }
					.ui nav.left li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(2) { order: 4;  }
					.ui nav.left li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(1) + label { order: 1; } 
					.ui nav.left li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(1) + label,
					.ui nav.left li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(2) + label { margin-left: 0px; width: 75px; height: 40px; font-weight: bold; letter-spacing: 2px; font-size: 12px; padding: 0 0 0 10px; line-height: 40px; box-sizing: border-box; color: #333; text-transform: uppercase; background-color: rgba(255, 255, 255, 0.6);  } 
					.ui nav.left li.project-filters.form > div > div > .form-element > .date > input:nth-of-type(2) + label { order: 3;  } 

					
					';
	
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('project-filter', function(elm_scripter) {
		
					var elm_filter = elm_scripter.find('[id=y\\\:ui_filter\\\:filter-0]');
					var elm_ui = elm_scripter.closest('.ui');
					var public_user_interface_id = elm_ui.find('[data-public_user_interface_id]').attr('data-public_user_interface_id');
					elm_body = elm_ui.closest('body');
					
					elm_ui.attr('data-project_id', elm_scripter.attr('data-project_id'));
					elm_body.attr('data-project_id', elm_scripter.attr('data-project_id'));
		
					// DISPLAY
					var func_display_data = function() {

						var elm_handle_dynamic_project_data = elm_ui.find('[id=y\\\:ui\\\:handle_dynamic_project_data-0]');
						elm_handle_dynamic_project_data.data({target: elm_handle_dynamic_project_data, method: 'handle_dynamic_project_data', module: 'ui'});
						elm_handle_dynamic_project_data.quickCommand(elm_handle_dynamic_project_data);
						
						var elm_object_container = elm_ui.find('.project-dynamic-data > .data > .object');
						elm_object_container.empty();
						
						elm_ui.find('.project-dynamic-nav, .tools').attr('data-object_active', false);
					}
					
					// SEARCH & FILTER
					if (elm_ui.find('.filter-container').length) {
						elm_ui.find('.projects-nav').attr('data-filter', true);
					} else {
						elm_ui.find('.projects-nav').attr('data-filter', false);
					}
					
					var func_get_input_dates = function(parse) {
					
						var elm_date_container = elm_filter.find('.form-element > .date');
						var has_input = false;
						
						if (!elm_date_container.length) {
							return false;
						}
						
						var elm_input_min = elm_date_container.find('input[name=date-min]');
						var elm_input_max = elm_date_container.find('input[name=date-max]');
						
						if (elm_input_min.val().length || elm_input_max.val().length) {
							has_input = true;
						}
						
						var input_min = (elm_input_min.val().length ? elm_input_min.val() : elm_date_container.attr('data-min_date'));
						var input_max = (elm_input_max.val().length ? elm_input_max.val() : elm_date_container.attr('data-max_date'));
						var bounds_min = elm_date_container.attr('data-min_date');
						var bounds_max = elm_date_container.attr('data-max_date');
						
						if (input_min.indexOf('-') <= 0) {
							input_min = '01-01-'+input_min
						}
						if (input_max.indexOf('-') <= 0) {
							input_max = '01-01-'+input_max
						}
						if (bounds_min.indexOf('-') <= 0) {
							bounds_min = '01-01-'+bounds_min
						}
						if (bounds_max.indexOf('-') <= 0) {
							bounds_max = '01-01-'+bounds_max
						}
						
						var arr_parsed_dates = {has_input: has_input, bounds_min: DATEPARSER.int2Date(DATEPARSER.strDate2Int(bounds_min)), bounds_max: DATEPARSER.int2Date(DATEPARSER.strDate2Int(bounds_max)), min: DATEPARSER.int2Date(DATEPARSER.strDate2Int(input_min)), max: DATEPARSER.int2Date(DATEPARSER.strDate2Int(input_max))};
						var arr_dates = {has_input: has_input, bounds_min: bounds_min, bounds_max: bounds_max, min: input_min, max: input_max};
						
						if (arr_parsed_dates.min < arr_parsed_dates.bounds_min) {
						
							arr_parsed_dates.min = arr_parsed_dates.bounds_min;
							arr_dates.min = arr_dates.bounds_min;
							
							elm_input_min.val(arr_dates.min);
						}
						
						if (arr_parsed_dates.max > arr_parsed_dates.bounds_max) {
						
							arr_parsed_dates.max = arr_parsed_dates.bounds_max;
							arr_dates.max = arr_dates.bounds_max;
							
							elm_input_max.val(arr_dates.max);
						}
						
						if (arr_parsed_dates.min > arr_parsed_dates.bounds_max) {
						
							arr_parsed_dates.min = arr_parsed_dates.bounds_max;
							arr_dates.min = arr_dates.bounds_max;
							
							elm_input_min.val(arr_dates.max);
						}
						
						if (arr_parsed_dates.max < arr_parsed_dates.bounds_min) {
						
							arr_parsed_dates.max = arr_parsed_dates.bounds_min;
							arr_dates.max = arr_dates.bounds_min;
							
							elm_input_max.val(arr_dates.min);
						}
						
						return (parse ? arr_parsed_dates : arr_dates);
					
					}
					
					var func_filter = function() {

						var value = {arr_filter: {}, date_range: {}};

						elm_filter.find('.form-element').each(function() {

							var elm_form = $(this);
							var elm_form_value_type = elm_form.attr('data-value-type');
							var elm_active_filter = elm_form.find('.active-input');
							var elm_operator = elm_form.find('.operator');
							var operator = 'OR';
							
							if (elm_operator.is(':checked')) {
								operator = 'AND';
							}
							
							var filter_id = elm_form.attr('data-filter_id');
							var filter_key = filter_id + '-' + operator;
														
							if (elm_form_value_type == 'boolean') {
								
								value.arr_filter[filter_id] = [{boolean_value: elm_form.find('input:checked').val()}];									
							
							} else if (elm_form_value_type == 'int') {
								
								value.arr_filter[filter_id] = [{int: elm_form.find('input').val()}];									
							
							} else {
							
								value.arr_filter[filter_key] = [];
								
								elm_active_filter.children().each(function() {
								
									var elm_filter_value = $(this);
									
									if (elm_filter_value.data('object_id')) { // objects IDs
									
										value.arr_filter[filter_key].push({object_id: elm_filter_value.data('object_id'), type_id: elm_filter_value.data('type_id')});
										
									} else {  // strings
										
										// get rid of remove icon
										var elm_filter_value_string = elm_filter_value.clone();
										elm_filter_value_string.find('.icon').remove();
										elm_filter_value_string = elm_filter_value_string.text();
										value.arr_filter[filter_key].push({string: elm_filter_value_string});
									}
								});
							}
						});
						
						var arr_dates = func_get_input_dates(false);	
						
						if (arr_dates.has_input) {					
							value.date_range = arr_dates;
						}

						elm_filter.data({value: value}).quickCommand(function() {
							func_display_data();
						});

						setTimeout(function () {
							elm_ui.children('input').prop('checked', false);
						}, 500);
					};
					
					var func_search = function(elm, run_filter) {
					
						var value = elm.val().replace(/[!@#$%^&*_|+\-'\"<>\{\}\[\]\\\/]/gi, ' ');
						
						var elm_search_active = elm.closest('.input').find('.active-input');
						var filter_id = elm_search_active.attr('data-filter_id');
						
						if (value) {
						
							var elm_string = $('<div></div>').html('<span>'+value.trim()+'</span>').addClass('string').appendTo(elm_search_active);
							
							var elm_string_close_button = $('<span></span>').addClass('icon').appendTo(elm_string);
				
							ASSETS.getIcons(elm_string, ['close'], function(data) {
								
								elm_string_close_button[0].innerHTML = data.close;
							});
							
							if (run_filter) {
								func_filter();
							}
						}
						
						elm_search_active.parent().find('input').val('').blur();
						
						var elm_project_filters = elm.closest('.project-filters');
						elm_project_filters.find('.results').addClass('hide');
					}
					
					var elm_search_input = elm_filter.find('[id^=y\\\:ui_filter\\\:search-]');			
					
					elm_search_input.on('keypress focus keyup', function(e) {

						var elm_search_input = $(this);
						var in_form = elm_search_input.closest('.form').length;
						var elm_active_filter = elm_search_input.closest('.input').find('.active-input');
						var elm_results = elm_search_input.closest('.form-element').find('.results');
						var filter_id = elm_active_filter.closest('.form-element').attr('data-filter_id');
						var filter_type_id = elm_search_input.attr('data-filter_type_id');
					
						FEEDBACK.stop(elm_search_input);
							
						if (e.type == 'keypress' && e.key == 'Enter') {
							
							//if (!in_form && elm_active_filter.attr('data-filter_id') ==  0) {
							//	func_search(elm_search_input);
							//}
						} else if (e.type == 'keypress' && e.key == 'Backspace') {
						
							if (!in_form && !elm_search_input.val()) {
							
								elm_active_filter.children().last().remove();
								
								func_filter();
							}
						} else if (e.type == 'keyup' || e.type == 'focus') {
					
							COMMANDS.setData(elm_search_input[0], {filter_type_id: filter_type_id, filter_id: filter_id}, true);
						
							elm_search_input.quickCommand(elm_results, {'html': 'replace'});
												
							/*localStorage[public_user_interface_id+'_pui_search_value_'+filter_id] = elm_search_input.val();*/
						}
					});
					
					/*if (elm_search_input.length == 1) {
					
						var elm_active_filter = elm_search_input.closest('.input').find('.active-input');
						var filter_id = elm_active_filter.closest('.form-element').attr('data-filter_id');
						var local_value = localStorage[public_user_interface_id+'_pui_search_value_'+filter_id];
						
						if (local_value) {
						
							elm_search_input.val(local_value);
							func_search(elm_search_input, false);
						}
					}*/	
					
					elm_scripter.on('change', '.select-types > ul input', function() {
						
						var elm_select_type = $(this).closest('[id=y\\\:ui_filter\\\:select_types-0]');
						var arr_type_ids = [];
						var elm_types_amount = elm_select_type.find('.types-amount');
						var num_selected_types = 0;
						
						elm_types_amount.empty();
						
						elm_select_type.find('li input:checked').each(function() {
							arr_type_ids.push($(this).data('type_id'));
							num_selected_types++;
						});
						
						if (num_selected_types > 0) {
						
							elm_types_amount.html('('+num_selected_types+')');
						}
						
						COMMANDS.setData(elm_select_type[0], {type_ids: arr_type_ids}, true);
						elm_select_type.quickCommand(function() {

							func_display_data();
						});
						
					}).on('click', '.results > p.run-quicksearch', function() {
						
						var elm = $(this);
						var elm_search_input = elm.closest('.form-element').find('[id^=y\\\:ui_filter\\\:search-]');
						
						func_search(elm_search_input, true);
						
					}).on('click', '[id^=y\\\:ui\\\:set_scenario-]', function() {

						$(this).quickCommand(function() {

							func_display_data();
						});
						
					}).on('click', '.form-element .active-input div span', function() {

						var elm_active_keyword = $(this);
						var elm_active_filter = elm_active_keyword.closest('.active-input');
						var elm_operator_toggle = elm_active_keyword.closest('.form-element').find('input.operator').next('label');
						
						if (!elm_active_keyword.hasClass('icon') && elm_active_keyword.parent().hasClass('string')) {
							var string_value = elm_active_keyword.html();
							var elm_search_input = elm_active_filter.parent().find('[id^=y\\\:ui_filter\\\:search-]');
							elm_search_input.val(string_value);
							elm_search_input.focus();
						}
						
						if (elm_active_keyword.hasClass('icon') || elm_active_keyword.parent().hasClass('string')) {
							elm_active_keyword.parent().remove();
						}
						
						if (elm_active_filter.children().length < 2) {
							
							elm_operator_toggle.addClass('hide');
						}
						
						if (elm_active_keyword.hasClass('icon')) {
						
							func_filter();
						}
						
					}).on('click', '.input > input', function() {
					
						var input = $(this);
					
						if (input.is(':radio')) {
						
							if (input.attr('data-set') == 1) {

								input.prop('checked', false);
								input.attr('data-set', 0);
								
							} else {
						
								input.parent().find('input').attr('data-set', 0);
								input.attr('data-set', 1);
							}
						}
					
						func_filter();
						
					}).on('click', '.keywords .keyword', function() {
					
						var elm_keyword = $('<div></div>').html('<span>'+$(this).text()+'</span>').addClass($(this).attr('class')).attr('data-object_id', $(this).data('object_id')).attr('data-type_id', $(this).data('type_id'));
						var elm_remove = $('<span></span>').addClass('icon').appendTo(elm_keyword);
						
						ASSETS.getIcons(elm_keyword, ['close'], function(data) {
								
							elm_remove[0].innerHTML = data.close;
						});
						
						var elm_active_filter = $(this).closest('.form-element').find('.active-input');
						var elm_operator_toggle = $(this).closest('.form-element').find('input.operator').next('label');
						var elm_results = $(this).closest('.form-element').find('.results');	
										
						elm_keyword.appendTo(elm_active_filter);
						
						if (elm_active_filter.children().length > 1) {
							
							elm_operator_toggle.removeClass('hide');
						}
						
						elm_results.addClass('hide');
						
						elm_search_input.val('');
						
						func_filter();
						
					}).on('click', '[id^=y\\\:ui_data\\\:show_project_type_object-]', function() {
						
						if (elm_ui.find('.form-element').length) {
							
							func_search($(this).closest('.form-element').find('[id^=y\\\:ui_filter\\\:search-]'), false);
						}
						
						$(this).quickCommand(elm_ui.find('.object'), {'html': 'append'});
						
						var elm_results = $(this).closest('.form-element').find('.results');	
						elm_results.addClass('hide');
						
						elm_ui.children('input').prop('checked', false);
						elm_ui.find('[id=y\\\:ui_filter\\\:filter-0]').children('input').prop('checked', false);
						elm_ui.find('#types-toggle').prop('checked', false);
						
					}).on('change', '.form-element > .date > input[name^=date-]', function() {
						
						FEEDBACK.stop(elm_ui.find('[id=y\\\:ui_filter\\\:filter-0]'));
						FEEDBACK.stop(elm_ui.find('[id=y\\\:ui\\\:handle_dynamic_project_data-0]'));
						
						var elm_input = $(this);
						var elm_date_container = elm_input.parent();
						
						func_filter();
						
					});
					
					elm_ui.on('click', function(e) {

						if (!$(e.target).parent('.string').length && !$(e.target).closest('.set-input').length && !$(e.target).closest('.results').length && !$(e.target).closest('.select-types').length) {

							// close search bar
							if (elm_search_input.length) {
							
								elm_search_input.each(function() {
									$(this).val('');
								});
							
								func_search(elm_search_input);
							}
							
							elm_ui.find('#types-toggle').prop('checked', false);
							
							// close responsive menu
							if (!$(e.target).is('label, input, ul, span, span path, [class*=filter]')) {
														
								elm_ui.find('#form-toggle').prop('checked', false);
								elm_ui.children('input').prop('checked', false);
							}
						}
					});
				});	
				
				SCRIPTER.dynamic('[data-method=set_project]', 'project-filter');
			";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 
		if ($method == "select_types") {
			
			$arr_input_selected_type_ids = $value['type_ids'];
			
			if (!count($arr_input_selected_type_ids)) {
				SiteEndVars::setFeedback('selected_type_ids', false, true);
			} else {
				
				$arr_selected_type_ids = [];
				
				foreach ($arr_input_selected_type_ids as $type_id) {
					
					if (ui::isTypeEnabled($type_id, 'project')) {
						
						$arr_selected_type_ids[$type_id] = $type_id;
					}
				}
				
				SiteEndVars::setFeedback('selected_type_ids', $arr_selected_type_ids, true);
			}
		}
		
		if ($method == "filter") {
			
			SiteEndVars::setFeedback('arr_public_user_interface_min_max', [$value['min'], $value['max']], true);
	
			$url_filter = false;

			foreach((array)$value['arr_filter'] as $filter_id => $arr_filter_set) {
			
				$url_filter_set = false;
				
				foreach((array)$arr_filter_set as $arr_filter) {
					
					if (isset($arr_filter['int'])) {

						$url_filter_set = (int)$arr_filter['int'];
					}
					
					if (isset($arr_filter['boolean_value'])) {
						
						$boolean_value = boolval($arr_filter['boolean_value']);
						$url_filter_set = ($boolean_value ? 'yes' : 'no');
					}
					
					if ($arr_filter['string']) {
						
						$string = str_replace(' ', '~', $arr_filter['string']);
						
						if ($url_filter_set) {
							$url_filter_set .= '|'.$string;
						} else {
							$url_filter_set = $string;
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
			
			if ($value['date_range']) {
				
				if ($value['date_range']['max'] || $value['date_range']['min']) {
					$url_filter = '['.$value['date_range']['min'].']['.$value['date_range']['max'].']'.$url_filter;
				}
			}
	
			if ($url_filter) {
				
				ui::setPublicUserInterfaceModuleVars(['set' => 'filter', 'id' => $url_filter]);
			
			} else {
				
				toolbar::setFilter([]);
				ui::setPublicUserInterfaceModuleVars(false);
			}
		} 
		
		if ($method == "search") {

			if (is_array($value)) {
				
				$search_name = $value['value_element'];
				$filter_type_id = $value['filter_type_id'];
				$filter_id = $value['filter_id'];
			}

			$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
			$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
	
			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $project_id);
			$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
			$arr_selected_types = SiteStartVars::getFeedback('selected_type_ids');

			if ($arr_selected_types) {
				$arr_public_interface_project_types = array_intersect($arr_public_interface_project_types, $arr_selected_types);
			}
			
			if ($filter_id == 0 && $arr_public_interface_settings['projects'][$project_id]['no_quicksearch'] != 1) {				

				if ($arr_public_interface_settings['projects'][$project_id]['name']) {
					
					$project_name = $arr_public_interface_settings['projects'][$project_id]['name'];
					
				} else {
					
					$arr_project = StoreCustomProject::getProjects($project_id);
					$project_name = $arr_project['project']['name'];
				}
	
				if ($search_name) {

					$arr_objects = ui_data::getPublicInterfaceObjects($arr_public_interface_project_types, ['search' => $search_name], true, 15, false, true, ['override_project_filter' => true]);

					$elm_objects = '';

					foreach ($arr_objects as $object_id => $arr_object) {
						
						$elm_objects .= ui_data::createViewTypeObjectThumbnail($arr_object); 
					}
					
					$elm_objects .=  '<p class="run-quicksearch"><span class="icon">'.getIcon('search').'</span><span>'.($arr_public_interface_settings['labels']['search_start'] ? strEscapeHTML($arr_public_interface_settings['labels']['search_start']): 'Click here to search through ').Labels::parseTextVariables($project_name).strEscapeHTML($arr_public_interface_settings['labels']['search_end']).'</span></p>';					
				}
			}

			if (($filter_id && $filter_type_id) || ($filter_id == 0 && $filter_type_id == 0 && !$arr_public_interface_settings['projects'][$project_id]['use_filter_form'])) {
				
				$keywords = ui_data::createViewKeywords($filter_type_id, $search_name, $filter_id);
			}

			$this->html = '<div class="results">'.$keywords.$elm_objects.'</div>';
		}	
		
	}
	
	public static function parseFilterString($value) {
		
		// Date Filter: [01-01-1800][01-01-1850]
		// Quick Search: 0-OR:string 
		// Object Search: 56_O_0_56-OR:56-730957
		// Object Description Reference Filter: 56_OD_237_78-OR:78-136935
		// Object Description Boolean Filter: 56_OD_15954_0:yes
		// Object Description Int Filter: 56_OD_15954_0:99
		// Sub-Object Description Reference Filter: 56_SOD_322_4-OR:4-4892850
		// All together: [01-01-1800][01-01-1850]0-OR:string+56_O_0_56-OR:56-730957+56_OD_237_78-OR:78-136935+56_OD_15954_0:yes+6_SOD_322_4-OR:4-4892850
		
		$arr_elements = ['O', 'OD', 'SOD'];
		$arr_dates = [];
		
		if (preg_match_all("/\[([\d-]*)\]/", $value, $arr_dates)) {
			
			$arr_filter_dates = ['min' => $arr_dates[1][0], 'max' => $arr_dates[1][1]];
			
			$value = preg_replace("/(\[[\d-]*\])/", '', $value);
		}
	
		$arr_filter_parts = explode('+', $value);

		foreach ((array)$arr_filter_parts as $filter_part) {

			$quicksearch_strings = '';
			$arr_type_object_ids = [];
					
			$arr_filter_part = explode(':', $filter_part);
			$arr_filter_part_id_operator = explode('-', $arr_filter_part[0]);
			
			// Set operator to OR / AND only if operator was specified
			$filter_part_operator = ($arr_filter_part_id_operator[1] ? ($arr_filter_part_id_operator[1] == 'OR' ? 'OR' : 'AND') : false);
			
			$filter_part_id = $arr_filter_part_id_operator[0];
			$arr_filter_part_id = explode('_', $filter_part_id);
			
			$type_id = (int)$arr_filter_part_id[0];
			$element = (in_array($arr_filter_part_id[1], $arr_elements) ? $arr_filter_part_id[1] : false);
			$object_description_id = (int)$arr_filter_part_id[2];
			$object_description_reference_type_id = (int)$arr_filter_part_id[3];	
						
			$arr_type_set = StoreType::getTypeSet($type_id);			

			$arr_filter_part_values = explode('|', $arr_filter_part[1]);
	
			foreach ((array)$arr_filter_part_values as $filter_value) {
					
				// References
				if (preg_match("/\d+-\d+/", $filter_value)) {
					
					$arr_type_object_id = explode('-', $filter_value);
					
					if (is_numeric($arr_type_object_id[0]) && is_numeric($arr_type_object_id[1]) && (ui::isTypeEnabled($arr_type_object_id[0], false))) {

						$arr_type_object_ids[(int)$arr_type_object_id[0]][] = (int)$arr_type_object_id[1];
					}
				
				// Int or Boolean
				} else if (is_numeric($filter_value) || in_array($filter_value, ['yes', 'no'])) {
					
					if ($element == 'OD') {
						
						$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['object_definitions' => [$object_description_id => [$filter_value]]]];	
					
					} else if ($element == 'SOD') {
								
						foreach ((array)$arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
							foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
								if ($object_sub_description_id != $object_description_id) {
									
									continue;
								}
								
								$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['object_subs' => [$object_sub_details_id => ['object_sub_definitions' => [$object_sub_description_id => [$filter_value]]]]]];
							}
						}					
					}
				
				// Strings
				} else {
					
					$quicksearch_strings .= ' '.str_replace('~', ' ', $filter_value);
				}
			}

			if ($arr_filter_part_id && count((array)$arr_type_object_ids[$object_description_reference_type_id])) {

				if ($element == 'O') {
					
					foreach ((array)$arr_type_object_ids[$object_description_reference_type_id] as $type_object_id) {
						
						$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['objects' => $arr_type_object_ids[$object_description_reference_type_id]]];	
					}
						
				} else if ($element == 'OD') {

					if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_is_dynamic']) {
						
						if ($filter_part_operator == 'AND') {
						
							foreach ((array)$arr_type_object_ids[$object_description_reference_type_id] as $type_object_id) {
						
								$arr_type_object_filters[$type_id][$arr_filter_part[0].uniqid()] = ['object_filter' => ['object_definitions' => [$object_description_id => ['type_tags' => [$object_description_reference_type_id => ['objects' => [[['objects' => [$type_object_id]]]]]]]]]];
							
							}
							
						} else {
							
							$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['object_definitions' => [$object_description_id => ['type_tags' => [$object_description_reference_type_id => ['objects' => [[['objects' => $arr_type_object_ids[$object_description_reference_type_id]]]]]]]]]];
						}
						
					} else {
						
						if ($filter_part_operator == 'AND') {
						
							foreach ((array)$arr_type_object_ids[$object_description_reference_type_id] as $type_object_id) {
								
								$arr_type_object_filters[$type_id][$arr_filter_part[0].uniqid()] = ['object_filter' => ['object_definitions' => [$object_description_id => [[['objects' => [$type_object_id]]]]]]];		
							}
							
						} else {
							
							$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['object_definitions' => [$object_description_id => [[['objects' => $arr_type_object_ids[$object_description_reference_type_id]]]]]]];		
						
						}
					}
					
				} else if ($element == 'SOD') {					
		
					foreach ((array)$arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
						foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
							if ($object_sub_description_id != $object_description_id) {
								
								continue;
							}
							
							if ($filter_part_operator == 'AND') {
							
								foreach ((array)$arr_type_object_ids[$object_description_reference_type_id] as $type_object_id) {
									
									$arr_type_object_filters[$type_id][$arr_filter_part[0].uniqid()] = ['object_filter' => ['object_subs' => [$object_sub_details_id => ['object_sub_definitions' => [$object_sub_description_id => [[['objects' => [$type_object_id]]]]]]]]];		
								}
								
							} else {
								
								$arr_type_object_filters[$type_id][$arr_filter_part[0]] = ['object_filter' => ['object_subs' => [$object_sub_details_id => ['object_sub_definitions' => [$object_sub_description_id => [[['objects' => $arr_type_object_ids[$object_description_reference_type_id]]]]]]]]];		
							
							}
						}
					}
				}	
			}		
		}
	
		if ($arr_filter_dates) {
			
			SiteEndVars::setFeedback('filter_date_start', $arr_filter_dates['min'], true);
			SiteEndVars::setFeedback('filter_date_end', $arr_filter_dates['max'], true);

			$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
			$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
			$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $project_id);
	
			foreach ((array)$arr_public_interface_project_types as $type_id) {
			
				$arr_type_set = StoreType::getTypeSet($type_id);
			
				if (count($arr_type_set['object_sub_details'])) {
			
					$arr_type_object_filters[$type_id][uniqid()] = [
						'object_filter' => [
							'object_subs' => [
								[
									'object_sub_dates' => [
										['object_sub_date_type' => 
											'point', 
											'object_sub_date_start' => [
												'equality' => '≥',
												'value' => $arr_filter_dates['min']
											],
											'object_sub_date_end' => [
												'equality' => '≤',
												'value' => $arr_filter_dates['max']
											]
										]
									]
								]
							]
						]
					];
				}
			}
			
		} else {
			
			SiteEndVars::setFeedback('filter_date_start', false, true);
			SiteEndVars::setFeedback('filter_date_end', false, true);
		}
		
		$arr_temp_type_object_filters = [];

		if ($arr_type_object_filters) {
			
			foreach (current($arr_type_object_filters) as $arr_filter) {
		
				$arr_object_filter = $arr_filter['object_filter'];
				$arr_object_filter['options'] = ['operator' => 'object_and_sub_and'];
				$arr_temp_type_object_filters[] = $arr_object_filter;
				
			}
		}

		return ['quicksearch_strings' => $quicksearch_strings, 'arr_type_object_ids' => $arr_type_object_ids, 'arr_type_object_filters' => $arr_temp_type_object_filters];
	}

	public static function createFilterArray($arr_filter) {

		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');

		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $project_id, false);		

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
			if ($arr_type_object_filters) {
				$arr_type_filter[$type_id] = ['object_filter' => $arr_type_object_filters];
			}		
			if ($quicksearch_strings) {
				$arr_type_filter[$type_id]['search'] = $quicksearch_strings;
			}	
		}

		return $arr_type_filter;
	}
			
	
	public static function createReferencedObjectFilter($type_id, $arr_type_object_ids, $crossreferenced = false, $steps = 2) {

		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id);
		$arr_public_interface_project_filter_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, false, true);
				
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_object_description_ref_type_ids = arrValuesRecursive('object_description_ref_type_id', $arr_type_set['object_descriptions']);
		$arr_object_sub_description_ref_type_ids = arrValuesRecursive('object_sub_description_ref_type_id', $arr_type_set['object_sub_details']);
		
		$arr_project = StoreCustomProject::getProjects($project_id);

		$arr_primary_types = [];
		
		foreach ($arr_public_interface_settings['types'] as $setting_type_id => $arr_type_settings) {
			
			if ($arr_type_settings['primary']) {
				
				$arr_primary_types[$setting_type_id] = $setting_type_id;
			}
		}	
		
		$arr_types = array_unique(array_merge($arr_primary_types, $arr_public_interface_project_types, $arr_public_interface_project_filter_types, array_keys($arr_type_object_ids)));

		$arr_type_filter = [];
		
		foreach ($arr_type_object_ids as $ref_type_id => $arr_object_ids) {

			if (!$crossreferenced || ($crossreferenced && !in_array($ref_type_id, $arr_object_description_ref_type_ids) && !in_array($ref_type_id, $arr_object_sub_description_ref_type_ids))) {
				
				$trace = new TraceTypesNetwork($arr_types, true, true);
				$trace->run($type_id, $type_id, $steps, TraceTypesNetwork::RUN_MODE_BOTH, ['shortest' => true]);

				$arr_type_network_paths = $trace->getTypeNetworkPaths(true);

				$collect = new CollectTypesObjects($arr_type_network_paths);
				
				$collect->addFilterTypeFilters($ref_type_id, ['objects' => $arr_object_ids]); // Filter object ids => OR
				$collect->setScope(['users' => false, 'types' => StoreCustomProject::getScopeTypes($project_id), 'project_id' => $project_id]);
				$collect->init([$type_id => []], false);
				$arr_collect_info = $collect->getResultInfo();
				

				foreach ($arr_collect_info['filters'][0] as $arr_collect_info_filter) {

					if ($arr_collect_info_filter['object_filter']) {

						$arr_type_filter[$ref_type_id]['object_filter'] = array_merge((array)$arr_type_filter[$ref_type_id]['object_filter'], $arr_collect_info_filter['object_filter']);
					}
				}
				
			} else {
				
				$arr_type_filter[$ref_type_id]['referenced_object'] = ['object_id' => $arr_object_ids];
			}
			
		}

		return $arr_type_filter;
	}
		
	public static function isFilterActive($return_total_filtered = false, $data_display_mode = false) {
		
		// SET BY FILTER ITSELF!!!
		
		$public_user_interface_id = (int)SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = (int)SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
		$arr_public_interface_project_types = cms_nodegoat_public_interfaces::getPublicInterfaceTypeIds($public_user_interface_id, $public_user_interface_active_custom_project_id, false);
		$arr_project = StoreCustomProject::getProjects($public_user_interface_active_custom_project_id);
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$filter_is_active = false;		
		$arr_selected_types = SiteStartVars::getFeedback('selected_type_ids');

		if ($arr_selected_types) {
			$arr_public_interface_project_types = array_intersect($arr_public_interface_project_types, $arr_selected_types);
		}
				
		$scenario_id = (int)SiteStartVars::getFeedback('scenario_id');
		
		if ($scenario_id) {
			
			toolbar::setScenario($scenario_id);	
			$arr_type_filters = toolbar::getFilter();
			$type_id = key($arr_type_filters);
			$arr_active_filters = $arr_type_filters[$type_id];
	
			$filter_is_active = true;
			
			if ($return_total_filtered) {
				
				$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
				$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($public_user_interface_active_custom_project_id), 'project_id' => $public_user_interface_active_custom_project_id]);
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
				
						$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
						$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($public_user_interface_active_custom_project_id), 'project_id' => $public_user_interface_active_custom_project_id]);
						
						if ($data_display_mode) {
						
							$browse_scope_id = (is_array($arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope'][$type_id]['browse']) ? $arr_public_interface_settings['projects'][$public_user_interface_active_custom_project_id]['scope'][$type_id]['browse'][$data_display_mode] : false);
						
							if ($browse_scope_id) {

								SiteEndVars::setFeedback('scope_id', $browse_scope_id, true);
								
								$arr_object_filter = ui_data::getScopeDateFilter($type_id, $browse_scope_id);
							
								if (count($arr_object_filter)) {
									
									$arr_active_filters[$type_id]['object_filter'][] = $arr_object_filter;
								}	
							}		
						}
					
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
	
}
