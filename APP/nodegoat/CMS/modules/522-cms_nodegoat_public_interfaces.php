<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACES', 'def_nodegoat_public_interfaces');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS', 'def_nodegoat_public_interface_projects');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS', 'def_nodegoat_public_interface_texts');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES', 'def_nodegoat_public_interface_project_types');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS', 'def_nodegoat_public_interface_project_scenarios');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS', 'data_nodegoat_public_interface_selections');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS', 'data_nodegoat_public_interface_selection_elements');

class cms_nodegoat_public_interfaces extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function jobProperties() {
		return [
			'cleanupPublicInterfaceSelections' => [
				'label' => 'nodegoat '.getLabel('lbl_public_interface_cleanup_selections'),
				'options' => function($options) {
					$arr_units = [
						['id' => 1440, 'name' => getLabel('unit_day')],
						['id' => 10080, 'name' => getLabel('unit_week')],
						['id' => 40320, 'name' => getLabel('unit_month')]
					];
					return '<label>'.getLabel('lbl_age').'</label><input type="text" name="options[age_amount]" value="'.$options['age_amount'].'" /><select name="options[age_unit]">'.cms_general::createDropdown($arr_units, $options['age_unit']).'</select>';
				}
			]
		];
	}
		
	public static function listProjectTypesScenarios($arr_public_interface, $arr_project) {
		
		$project_id = $arr_project['project']['id'];
		$arr_types = StoreType::getTypes();
		$arr_type_scenarios = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id); 
		$arr_data_options = self::getPublicInterfaceDataOptions();
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_project').' '.getLabel('lbl_settings').'</a></li>
				<li><a href="#">'.getLabel('lbl_types').'</a></li>
				<li><a href="#">'.getLabel('lbl_scenarios').'</a></li>
				<li><a href="#">'.getLabel('lbl_type').' '.getLabel('lbl_settings').'</a></li>
				<li><a href="#">'.getLabel('lbl_object').' '.getLabel('lbl_settings').'</a></li>
				<li><a href="#">'.getLabel('lbl_start').'</a></li>
			</ul>
			<div>
				<div class="options">
					<fieldset><ul>
						<li><label>'.getLabel('lbl_project').' '.getLabel('lbl_name').'</label><input type="text" name="settings[projects][project-'.$project_id.'][name]" value="'.htmlspecialchars($arr_public_interface['interface']['settings']['projects'][$project_id]['name']).'" /></li>
						<li><label>'.getLabel('lbl_search').' '.getLabel('lbl_info').'</label><input type="text" name="settings[projects][project-'.$project_id.'][info_search]" value="'.htmlspecialchars($arr_public_interface['interface']['settings']['projects'][$project_id]['info_search']).'" /></li>
						<li><label>'.getLabel('lbl_info').' '.getLabel('lbl_title').'</label><input type="text" name="settings[projects][project-'.$project_id.'][info_title]" value="'.htmlspecialchars($arr_public_interface['interface']['settings']['projects'][$project_id]['info_title']).'" /></li>
						<li><label>'.getLabel('lbl_info').'</label>'.cms_general::editBody($arr_public_interface['interface']['settings']['projects'][$project_id]['info'], 'settings[projects][project-'.$project_id.'][info]', ['inline' => true]).'</li>
						<li><label>'.getLabel('lbl_no').' '.getLabel('lbl_search').'</label><input name="settings[projects][project-'.$project_id.'][no_quicksearch]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['no_quicksearch'] ? 'checked="checked"' : '').' /></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_type').' '.getLabel('lbl_button').'s</label><input name="settings[projects][project-'.$project_id.'][show_type_buttons]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['show_type_buttons'] ? 'checked="checked"' : '').' /></li>
						<li><label>'.getLabel('lbl_filter').' '.getLabel('lbl_object_descriptions').'</label><input name="settings[projects][project-'.$project_id.'][use_filter]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['use_filter'] ? 'checked="checked"' : '').' /></li>
					</ul></fieldset>
				</div>
			</div>
			<div>	
				<div class="options"><fieldset>';
				
					// Loop trough saved, unset not used in project arr, show rest of project arr
					
					foreach ((array)$arr_public_interface['project_types'][$project_id] as $type_id => $value) {
						unset($arr_project['types'][$type_id]);
						$sorter_types .= '<li><span></span><ul>'
							.'<li><label><input name="public_interface_project_types['.$project_id.'][type-'.$type_id.'][include]" type="checkbox" value="1" checked="checked" /><span>'.$arr_types[$type_id]["name"].'</span></label></li>'
							.'<li><fieldset><ul><li><div>'.self::getVisualisationOptions('type', $type_id, $project_id, $value).'</div></li></ul></fieldset>'
						.'</ul></li>';
					}
					
					foreach ((array)$arr_project['types'] as $type_id => $value) {
						$sorter_types .= '<li><span></span><ul>'
							.'<li><label><input name="public_interface_project_types['.$project_id.'][type-'.$type_id.'][include]" type="checkbox" value="1" /><span>'.$arr_types[$type_id]["name"].'</span></label></li>'
							.'<li><fieldset><ul><li><div>'.self::getVisualisationOptions('type', $type_id, $project_id).'</div></li></ul></fieldset>'
						.'</ul></li>';
					}
						
					if ($sorter_types) {
						$return .= '<ul class="sorter">'.$sorter_types.'</ul>';
					}
						
				$return .= '</fieldset></div>
				
			</div>
			<div>	
				<div class="options"><fieldset>';

					// loop trough saved, unset not used in scenario arr, show rest of scenario arr
					foreach ((array)$arr_public_interface['project_scenarios'][$project_id] as $scenario_id => $value) {
						$arr_type_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id, false, false, $scenario_id); 
						unset($arr_type_scenarios[$arr_type_scenario['type_id']][$scenario_id]);
						$sorter_scenarios .= '<li><span></span><ul>'
							.'<li><label><input name="public_interface_project_scenarios['.$project_id.'][scenario-'.$scenario_id.'][include]" type="checkbox" value="1" checked="checked" /><span>'.$arr_type_scenario['name'].'</span></label></li>'
							.'<li><fieldset><ul><li><div>'.self::getVisualisationOptions('scenario', $scenario_id, $project_id, $value).'</div></li></ul></fieldset>'
						.'</ul></li>';	
					}
					
					foreach ($arr_type_scenarios as $type_id => $arr_type_scenario) {
						foreach ($arr_type_scenario as $scenario_id => $scenario) {
							$sorter_scenarios .= '<li><span></span><ul>'
								.'<li><label><input name="public_interface_project_scenarios['.$project_id.'][scenario-'.$scenario_id.'][include]" type="checkbox" value="1" /><span>'.$scenario['name'].'</span></label></li>'
								.'<li><fieldset><ul><li><div>'.self::getVisualisationOptions('scenario', $scenario_id, $project_id).'</div></li></ul></fieldset>'
							.'</ul></li>';						
						}					
					}

					if ($sorter_scenarios) {
						$return .= '<ul class="sorter">'.$sorter_scenarios.'</ul>';
					}
					
				$return .= '</fieldset></div>
				
			</div>
			<div>
				<div class="options"><fieldset>';

					if (!empty($arr_public_interface['project_types'][$project_id])) {
						$return .= '<ul>';
							foreach ((array)$arr_public_interface['project_types'][$project_id] as $type_id => $value) {
								if (!$value['type_is_filter']) {
									$return .= '<li>
													<label>'.Labels::parseTextVariables($arr_types[$type_id]["name"]).'</label>
													<div class="fieldsets"><div>';
													
													$arr_object_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($project_id, $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids);

													$return .= '<fieldset><ul>
																	<li>
																		<label>Primary Project</label>
																		<input name="settings[projects][project-'.$project_id.'][primary_project]['.$type_id.']" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['primary_project'][$type_id] ? 'checked="checked"' : '').' />
																	</li>
																	<li>
																		<label>'.getLabel("lbl_order_by").'</label>
																		<select name="settings[projects][project-'.$project_id.'][sort]['.$type_id.']">'.cms_general::createDropdown($arr_object_analyses, $arr_public_interface['interface']['settings']['projects'][$project_id]['sort'][$type_id], true).'</select>
																	</li>
																</ul></fieldset>';

													foreach (['browse', 'explore'] as $scope_option) {
														$return .= '<fieldset><legend>'.getLabel("lbl_".$scope_option).' '.getLabel("lbl_scope").'</legend><ul>';
														foreach ($arr_data_options as $arr_data_option) {
															if ($arr_data_option['visualisation_type']) {
																$return .= '<li>
																				<label>'.$arr_data_option['name'].'</label>
																				<select name="settings[projects][project-'.$project_id.'][scope]['.$type_id.']['.$scope_option.']['.$arr_data_option['id'].']">'. cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeScopes($project_id, $_SESSION['USER_ID'], $type_id, false), (is_array($arr_public_interface['interface']['settings']['projects'][$project_id]['scope'][$type_id][$scope_option]) ? $arr_public_interface['interface']['settings']['projects'][$project_id]['scope'][$type_id][$scope_option][$arr_data_option['id']] : 0), true, 'label').'</select>
																			</li>';
															}
														}
														$return .= '</ul></fieldset>';
													}
									$return .= '</div></div></li>';
								}
							}
						$return .= '</ul>';
					}				
				
				$return .= '</fieldset></div>
			
			</div>
			<div>
				<div class="options">
					<fieldset><ul>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_object').' fullscreen</label><input name="settings[projects][project-'.$project_id.'][show_object_fullscreen]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['show_object_fullscreen'] ? 'checked="checked"' : '').' /></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_object_subs').'</label><input name="settings[projects][project-'.$project_id.'][show_object_subs]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['show_object_subs'] ? 'checked="checked"' : '').' /></li>
					</ul></fieldset>
				</div>
			</div>
			<div>
				<div class="options"><fieldset>';

					$return .= '<ul>
									<li>
										<input name="settings[projects][project-'.$project_id.'][start][set]" value="none" '.(!$arr_public_interface['interface']['settings']['projects'][$project_id]['start']['set'] || $arr_public_interface['interface']['settings']['projects'][$project_id]['start']['set'] == 'none' ? 'checked="checked"' : '').' type="radio">
										<label>'.getLabel("lbl_none").'</label>
									<li>
										<input name="settings[projects][project-'.$project_id.'][start][set]" value="random" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['start']['set'] == 'random' ? 'checked="checked"' : '').' type="radio">
										<label>'.getLabel("lbl_public_interface_random_objects").'</label>';

										if (!empty($arr_public_interface['project_types'][$project_id])) {
											$return .= '<fieldset><ul>
													<li>
														<input type="number" name="settings[projects][project-'.$project_id.'][start][random][amount]" value="'.$arr_public_interface['interface']['settings']['projects'][$project_id]['start']['random']['amount'].'" title="'.getLabel("lbl_amount").'" />
													</li>';
																	
												foreach ((array)$arr_public_interface['project_types'][$project_id] as $type_id => $value) {
													
													if (!$value['type_is_filter']) {
														
														$return .= '<li>
															<input name="settings[projects][project-'.$project_id.'][start][random][types]['.$type_id.'][set]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['start']['random']['types'][$type_id]['set'] ? 'checked="checked"' : '').' />
															<label>'.Labels::parseTextVariables($arr_types[$type_id]['name']).'</label>
															<select name="settings[projects][project-'.$project_id.'][start][random][types]['.$type_id.'][filter_id]" title="'.getLabel("lbl_filter").'">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false)), $arr_public_interface['interface']['settings']['projects'][$project_id]['start']['random']['types'][$type_id]['filter_id']).'</select>
														</li>';
													}
												}
											$return .= '</ul></fieldset>';
										}
																
						$return .= '</li>
									<li>
										<input name="settings[projects][project-'.$project_id.'][start][set]" value="scenario" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['start']['set'] == 'scenario' ? 'checked="checked"' : '').' type="radio">
										<label>'.getLabel("lbl_scenario").'</label>';
										
										$arr_type_scenarios = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id); 
										$arr_scenarios = [];
										foreach ((array)$arr_type_scenarios as $type_id => $arr_type_scenario) {
											foreach ((array)$arr_type_scenario as $scenario_id => $scenario) {
												$arr_scenarios[$scenario_id] = $scenario;
											}
										}
										
										if (count($arr_scenarios)) {
											$return .= '<fieldset><ul>
														<li>
															<select name="settings[projects][project-'.$project_id.'][start][scenario][id]" title="'.getLabel("lbl_start").' '.getLabel("lbl_scenario").'">'. cms_general::createDropdown($arr_scenarios, $arr_public_interface['interface']['settings']['projects'][$project_id]['start']['scenario']['id'], true, 'label').'</select>
															<select name="settings[projects][project-'.$project_id.'][start][scenario][display_mode]">'. cms_general::createDropdown(self::getPublicInterfaceDataOptions(), $arr_public_interface['interface']['settings']['projects'][$project_id]['start']['scenario']['display_mode'], true, 'name').'</select>
														</li>
													</ul></fieldset>';
										}
										
						$return .= '</li>
									<li>
										<input name="settings[projects][project-'.$project_id.'][start][set]" value="type" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['start']['set'] == 'type' ? 'checked="checked"' : '').' type="radio">
										<label>'.getLabel("lbl_type").'</label>';
					
										$return .= '<fieldset><ul>
													<li>
														<select name="settings[projects][project-'.$project_id.'][start][type][id]" title="'.getLabel("lbl_start").' '.getLabel("lbl_type").'">'.cms_general::createDropdown($arr_types, $arr_public_interface['interface']['settings']['projects'][$project_id]['start']['type']['id'], true, 'name').'</select>
														<select name="settings[projects][project-'.$project_id.'][start][type][display_mode]">'. cms_general::createDropdown(self::getPublicInterfaceDataOptions(), $arr_public_interface['interface']['settings']['projects'][$project_id]['start']['type']['display_mode'], true, 'name').'</select>
													</li>
												</ul></fieldset>';
										
						$return .= '</li>
									</ul>';				
				
				$return .= '</fieldset></div>
			
			</div>
		</div>';
			
		return $return;
	}
	
	private static function getVisualisationOptions($set, $id, $project_id, $value = false) {
		
		$arr_data_options = self::getPublicInterfaceDataOptions();
		
		if ($set == 'type') {
		
			$return = '<label>
				<input name="public_interface_project_'.$set.'s['.$project_id.']['.$set.'-'.$id.']['.$set.'_is_filter]" type="checkbox" value="1" '.($value[$set.'_is_filter'] ? 'checked="checked"' : '').' title="'.getLabel('inf_project_type_in_user_interface_filter').'"/>
				<span class="icon">'.getIcon('filter').'</span>
			</label>
			<span class="split"></span>';
		}
		
		foreach ($arr_data_options as $key => $arr_data_option) {
		
			$return .= '<label>
				<input name="public_interface_project_'.$set.'s['.$project_id.']['.$set.'-'.$id.']['.$set.'_'.$key.']" type="checkbox" value="1" '.($value[$set.'_is_filter'] ? 'disabled' : ($value[$set.'_'.$key.''] ? 'checked="checked"' : '')).' title="'.$arr_data_option['name'].'"/>
				<span class="icon">'.getIcon($arr_data_option['icon']).'</span>
			</label>';
		
		}
		
		return $return;
	}	
	
	public static function createCiteAsSettings($type_id, $arr = [[], []]) {
		
		if (!$type_id) {
			return false;
		}
		
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id);

		$arr_options = [
			'string' => ['id' => 'string', 'name' => getLabel('lbl_text')],
			'object_name' => ['id' => 'object_name', 'name' => getLabel('lbl_name')],
			'value' => ['id' => 'value', 'name' => getLabel('lbl_value')],
			'access_date' => ['id' => 'access_date', 'name' => getLabel('lbl_access').' '.getLabel('lbl_date')],
			'access_day' => ['id' => 'access_day', 'name' => getLabel('lbl_access').' '.getLabel('unit_day')],
			'access_month' => ['id' => 'access_month', 'name' => getLabel('lbl_access').' '.getLabel('unit_month')],
			'access_year' => ['id' => 'access_year', 'name' => getLabel('lbl_access').' '.getLabel('unit_year')],
			'modify_date' => ['id' => 'modify_date', 'name' => getLabel('lbl_modify').' '.getLabel('lbl_date')],
			'modify_day' => ['id' => 'modify_day', 'name' => getLabel('lbl_modify').' '.getLabel('unit_day')],
			'modify_month' => ['id' => 'modify_month', 'name' => getLabel('lbl_modify').' '.getLabel('unit_month')],
			'modify_year' => ['id' => 'modify_year', 'name' => getLabel('lbl_modify').' '.getLabel('unit_year')],
			'object_id' => ['id' => 'object_id', 'name' => 'Object ID'],
			'type_id' => ['id' => 'type_id', 'name' => 'Type ID'],
			'nodegoat_id' => ['id' => 'nodegoat_id', 'name' => 'nodegoat ID'],
			'url' => ['id' => 'url', 'name' => getLabel('lbl_url')],
			'line_break' => ['id' => 'line_break', 'name' => 'Line break']
		];

		$return = '<fieldset><ul>
			<li>
				<label></label>
				<span><input type="button" class="data del" value="del" title="'.getLabel("inf_remove_empty_fields").'" /><input type="button" class="data add" value="add" /></span>
			</li>
			<li>
				<label></label>
				<div>';
					$arr_sorter = [];
					foreach ($arr as $arr_value) {
						$arr_sorter[] = ['value' => [
							'<select name="settings[cite_as]['.$type_id.'][citation_elm][]">'.cms_general::createDropdown($arr_options, $arr_value['citation_elm'], true).'</select>
							<select name="settings[cite_as]['.$type_id.'][value][]" '.($arr_value['citation_elm'] == 'value' ? '' : 'class="hide"').'>'.cms_general::createDropdown($arr_type_set_flat, $arr_value['value']).'</select>
							<input name="settings[cite_as]['.$type_id.'][string][]" type="text" value="'.$arr_value['string'].'" '.(!$arr_value['citation_elm'] || $arr_value['citation_elm'] == 'string' ? '' : 'class="hide"').'/>'
						]];
					}
					$return .= cms_general::createSorter($arr_sorter, true);
				$return .= '</div>
			</li>
		</ul></fieldset>';
		
		return $return;
	}

	public static function getSettingsTypesSorter($arr_public_interface, $sorter_type) {

		$arr_types = StoreType::getTypes();
		
		foreach ((array)$arr_public_interface['interface']['settings']['types'][$sorter_type] as $type_id) {
			$sorter .= '<li><span></span><div><label><input name="settings[types]['.$sorter_type.'][]" type="checkbox" value="'.$type_id.'" checked="checked" /><span>'.Labels::parseTextVariables($arr_types[$type_id]["name"]).'</span></label></div></li>';
			unset($arr_types[$type_id]);
		}
		foreach ((array)$arr_types as $type_id => $value) {
			$sorter .= '<li><span></span><div><label><input name="settings[types]['.$sorter_type.'][]" type="checkbox" value="'.$type_id.'" /><span>'.Labels::parseTextVariables($value["name"]).'</span></label></div></li>';
		}
		
		return $sorter;
	}
	
	public static function handlePublicInterface($public_interface_id, $arr, $arr_texts) {

		if (!empty($arr['settings'])) {
			
			$arr_projects = [];
			
			foreach ((array)$arr['settings']['projects'] as $str_project_id => $arr_project) {
				
				$project_id = explode('-', $str_project_id);
				$project_id = (int)$project_id[1];
				$arr_projects[$project_id] = $arr_project;
			}

			$arr_types_cite_as = [];

			foreach ((array)$arr['settings']['cite_as'] as $type_id => $arr_type_cite_as) {
				
				foreach ((array)$arr_type_cite_as as $option => $arr_values) {

					foreach ($arr_values as $key => $value) {
						
						if ($option == 'string' && $value) {
							$value = htmlspecialchars($value);
						}
						
						$arr_types_cite_as[$type_id][$key][$option] = $value;
					}
				}

			}
			
			$arr['settings']['projects'] = $arr_projects;
			$arr['settings']['cite_as'] = $arr_types_cite_as;
			
			$public_interface_settings = (is_array($arr['settings']) ? json_encode($arr['settings']) : $arr['settings']);
		}
	
		if (!$public_interface_id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." 
				(name, mode, settings, description, information, css, script, is_default) 
					VALUES 
				(
					'".DBFunctions::strEscape($arr['name'])."',
					'".DBFunctions::strEscape($arr['mode'])."',
					'".DBFunctions::strEscape($public_interface_settings)."',
					'".DBFunctions::strEscape($arr['description'])."',
					'".DBFunctions::strEscape($arr['information'])."',
					'".DBFunctions::strEscape($arr['css'])."',
					'".DBFunctions::strEscape($arr['script'])."',
					".(int)$arr['is_default']."
				)"
			);
			
			
			$public_interface_id = DB::lastInsertID();
		
		} else {
		
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." SET
					name = '".DBFunctions::strEscape($arr['name'])."',
					mode = '".DBFunctions::strEscape($arr['mode'])."',
					settings = '".DBFunctions::strEscape($public_interface_settings)."',
					description = '".DBFunctions::strEscape($arr['description'])."',
					information = '".DBFunctions::strEscape($arr['information'])."',
					css = '".DBFunctions::strEscape($arr['css'])."',
					script = '".DBFunctions::strEscape($arr['script'])."',
					is_default = ".(int)$arr['is_default']."
				WHERE id = ".(int)$public_interface_id."");
		}
						
		$res = DB::query("DELETE ip, it, ipt, ips
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it ON (it.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt ON (ipt.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')." ips ON (ips.public_interface_id = i.id)
			WHERE i.id = ".(int)$public_interface_id."");

		if ($arr['projects']) {

			$arr_sql_insert = [];
			
			foreach ((array)$arr['projects'] as $value) {
				
				if (!$value) {
					continue;
				}
				
				$arr_sql_insert[] = "(".(int)$public_interface_id.", ".(int)$value.")";
			}

			if ($arr_sql_insert) {
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')."
					(public_interface_id, project_id)
						VALUES
					".implode(",", $arr_sql_insert)."
				");
			}
			
			if ($arr['settings']['projects']) {
				
				$i = 0;
				foreach ($arr['settings']['projects'] as $project_id => $arr_project) {
					
					$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." SET
							sort = ".(int)$i."
						WHERE public_interface_id = ".(int)$public_interface_id." 
							AND project_id = ".(int)$project_id."");
					
					$i++;
				}
			}
		}
		
		if ($arr['public_interface_project_types']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['public_interface_project_types'] as $project_id => $arr_project_types) {
				
				$sort = 0; 
				
				foreach ($arr_project_types as $str_type_id => $value) {

					if (!$value['include']) {
						continue;
					}
						
					$type_id = explode('-', $str_type_id);
					$type_id = (int)$type_id[1];
					
					$arr_sql_insert[] = "(".(int)$public_interface_id.", ".(int)$project_id.", ".(int)$type_id.", ".(int)$value['type_is_filter'].", ".(int)$value['type_list'].", ".(int)$value['type_grid'].", ".(int)$value['type_geo'].", ".(int)$value['type_soc'].", ".(int)$value['type_time'].", ".$sort.")";
					$sort++;
				}
			}
			
			if ($arr_sql_insert) {
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')."
					(public_interface_id, project_id, type_id, is_filter, list, browse, geographic_visualisation, social_visualisation, time_visualisation, sort)
						VALUES
					".implode(",", $arr_sql_insert)."
				");
			}

		}
		
		if ($arr['public_interface_project_scenarios']) {
			
			$arr_sql_insert = [];
			
			foreach ($arr['public_interface_project_scenarios'] as $project_id => $arr_project_scenarios) {
				
				$sort = 0; 
				
				foreach ($arr_project_scenarios as $str_scenario_id => $value) {
					
					if (!$value['include']) {
						continue;
					}
					
					$scenario_id = explode('-', $str_scenario_id);
					$scenario_id = (int)$scenario_id[1];
	
					$arr_sql_insert[] = "(".(int)$public_interface_id.", ".(int)$project_id.", ".(int)$scenario_id.", ".(int)$value['scenario_list'].", ".(int)$value['scenario_grid'].", ".(int)$value['scenario_geo'].", ".(int)$value['scenario_soc'].", ".(int)$value['scenario_time'].", ".$sort.")";
					$sort++;
				}
			}
			
			if (!empty($arr_sql_insert)) {
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')."
					(public_interface_id, project_id, scenario_id, list, browse, geographic_visualisation, social_visualisation, time_visualisation, sort)
						VALUES
					".implode(",", $arr_sql_insert)."
				");
			}
		}
		
		if ($arr_texts) {
			
			$sort = 0; 
			
			foreach ($arr_texts as $value) {	
			
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')."
					(public_interface_id, name, text, sort)
						VALUES 
					( ".(int)$public_interface_id.", '".DBFunctions::strEscape($value['name'])."', '".DBFunctions::strEscape($value['text'])."', ".$sort.")
				");
				
				$sort++;
			}
		}

		return $public_interface_id;
	}
	
	public static function delPublicInterface($public_interface_id) {
		
		$res = DB::query("DELETE i, ip, it, ipt, ips
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it ON (it.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt ON (ipt.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')." ips ON (ips.public_interface_id = i.id)
			WHERE i.id = ".(int)$public_interface_id."");
	}
	
	public static function getPublicInterfaces($public_interface_id = 0, $project_id = 0) {

		$str_identifier = (int)$public_interface_id.'_'.(int)$project_id;
		
		$cache = self::getCache($str_identifier);
		if ($cache) {
			return $cache;
		}

		$arr = [];

		$arr_res = DB::queryMulti("
			SELECT i.*, 
					it.id AS text_id, it.name AS text_name, it.text, it.sort
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it ON (it.public_interface_id = i.id)
				WHERE TRUE
				".($public_interface_id ? "AND i.id = ".(int)$public_interface_id."" : "")."
				ORDER BY it.sort;
				
			SELECT i.id, 
					ip.project_id, ip.sort,
					ipt.project_id AS type_project_id, ipt.type_id, ipt.is_filter AS type_is_filter, ipt.browse AS type_grid, ipt.list AS type_list, ipt.geographic_visualisation AS type_geo, ipt.social_visualisation AS type_soc, ipt.time_visualisation AS type_time
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
					JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt ON (ipt.public_interface_id = i.id AND ipt.project_id = ip.project_id)
				WHERE TRUE
				".($public_interface_id ? "AND i.id = ".(int)$public_interface_id."" : "")."
				".($project_id ? "AND ip.project_id = ".(int)$project_id."" : "")."
				ORDER BY ip.sort, ipt.sort;
				
			SELECT i.id,
					ips.project_id AS scenario_project_id, ips.scenario_id, ips.browse AS scenario_grid, ips.list AS scenario_list, ips.geographic_visualisation AS scenario_geo, ips.social_visualisation AS scenario_soc, ips.time_visualisation AS scenario_time
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
					JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
					JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')." ips ON (ips.public_interface_id = i.id AND ips.project_id = ip.project_id)
				WHERE TRUE
				".($public_interface_id ? "AND i.id = ".(int)$public_interface_id."" : "")."
				".($project_id ? "AND ip.project_id = ".(int)$project_id."" : "")."
				ORDER BY ips.sort;
		");

		while($row = $arr_res[0]->fetchAssoc()) {

			if (!$arr[$row['id']]) {
				
				$row['settings'] = json_decode($row['settings'], true);
				
				$arr[$row['id']] = ['interface' => $row, 'projects' => [], 'texts' => []];
			}
			if ($row['text_id']) {
				$arr[$row['id']]['texts'][$row['text_id']] = $row;
			}
		}
		
		while($row = $arr_res[1]->fetchAssoc()) {

			$arr[$row['id']]['projects'][$row['project_id']] = $row;
			
			if ($row['type_id']) {
				$arr[$row['id']]['project_types'][$row['type_project_id']][$row['type_id']] = $row;
			}
		}
		
		while($row = $arr_res[2]->fetchAssoc()) {

			$arr[$row['id']]['project_scenarios'][$row['scenario_project_id']][$row['scenario_id']] = $row;
		}
		
		$arr = ((int)$public_interface_id ? current($arr) : $arr);
		
		self::setCache($str_identifier, $arr);

		return $arr;
	}
	
	public static function getDefaultPublicInterfaceId() {
		
		$id = false;
		
		$res = DB::query("SELECT i.id
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
			WHERE i.is_default = 1
		");

		while($row = $res->fetchAssoc()) {
			if ($row['id']) {
				$id = $row['id'];
			}
		}
		
		return (int)$id;
	}

	public static function getPublicInterfaceProjectIds($public_interface_id, $limit = false) {
		
		$arr = [];

		$res = DB::query("SELECT i.id, 
				ip.project_id, ip.sort
			FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
			WHERE i.id = ".(int)$public_interface_id."
			ORDER BY ip.sort
			".($limit ? "LIMIT ".(int)$limit."" : "")."
		");

		while($row = $res->fetchAssoc()) {
			
			if ($row['project_id']) {
				$arr[$row['project_id']] = $row['project_id']; 
			}
		}
		
		return (count($arr) > 1 ? $arr : key($arr));
	}	

	public static function getPublicInterfaceTypeIds($public_interface_id, $project_id = false, $filter = false) {

		$arr = [];
 	
		$res = DB::query("SELECT ipt.public_interface_id, ipt.type_id, ipt.project_id, ipt.is_filter, ipt.sort  
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.project_id = ipt.project_id)
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i ON (i.id = ip.public_interface_id)
			WHERE ipt.public_interface_id = ".(int)$public_interface_id."
				".($project_id ? "AND ipt.project_id = ".(int)$project_id."" : "")."
				".($filter ? "AND ipt.is_filter = 1" : "AND ipt.is_filter = 0")."
			ORDER BY ipt.sort
		");

		while($row = $res->fetchAssoc()) {
			if ($row['type_id']) {
				$arr[$row['type_id']] = $row['type_id']; 
			}
		}

		return $arr;
	}
	
	public static function getPublicInterfaceTypeProjectId($public_interface_id, $type_id) {

		$project_id = false;
 	
		$res = DB::query("SELECT ipt.project_id 
			FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.project_id = ipt.project_id)
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i ON (i.id = ip.public_interface_id)
			WHERE ipt.public_interface_id = ".(int)$public_interface_id."
				AND ip.public_interface_id = ".(int)$public_interface_id."
				AND ipt.type_id = ".(int)$type_id."
			ORDER BY ip.sort
		");

		while($row = $res->fetchAssoc()) {
			
			if ($row['project_id']) {
				$project_id = $row['project_id']; 
				break;
			}
		}

		return $project_id;
	}

	public static function getPublicInterfaceStyle($public_interface_id) {
		
		$arr = [];
		
		$res = DB::query("SELECT i.id, i.css, i.script
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
			WHERE i.id = ".(int)$public_interface_id."");

		while($row = $res->fetchAssoc()) {
			$arr = $row;
		}
		
		return $arr;
	}
	
	public static function getPublicInterfaceSettings($public_interface_id) {
		
		$arr = [];
		
		$res = DB::query("SELECT i.id, i.settings
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
			WHERE i.id = ".(int)$public_interface_id."");

		while($row = $res->fetchAssoc()) {
			
			$row['settings'] = json_decode($row['settings'], true);
			
			$arr = $row;
		}
		
		return $arr['settings'];
	}
	
	public static function getPublicInterfaceTexts($public_interface_id) {
	
		$arr = [];
		
		$res = DB::query("SELECT it.*
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i ON (i.id = it.public_interface_id)
			WHERE i.id = ".(int)$public_interface_id."
			ORDER BY it.sort
		");

		while ($row = $res->fetchAssoc()) {
			$arr[] = $row;
		}

		return $arr;
	}
	
	public static function getPublicInterfaceDataOptions() {
					
		$arr = [
			'grid' => ['id' => 'grid', 'name' => getLabel('lbl_grid'), 'icon' => 'tiles-many', 'visualisation_type' => false],
			'list' => ['id' => 'list', 'name' => getLabel('lbl_list'), 'icon' => 'tags', 'visualisation_type' => false],
			'geo' => ['id' => 'geo', 'name' => getLabel('lbl_map'), 'icon' => 'globe', 'visualisation_type' => 'plot'],
			'soc' => ['id' => 'soc', 'name' => getLabel('lbl_network'), 'icon' => 'graph', 'visualisation_type' => 'soc'],
			'time' => ['id' => 'time', 'name' => getLabel('lbl_chart'), 'icon' => 'chart-bar', 'visualisation_type' => 'line']
		];

		return $arr;
	}
	
	public static function getPublicInterfaceShareOptions($title) {
					
		$arr = [
			['share_name' => 'E-Mail', 'share_url' => 'mailto:?subject='.$title.' - &amp;body='.$title.' - ', 'icon' => 'email'],
			['share_name' => 'Facebook', 'share_url' => 'https://www.facebook.com/sharer/sharer.php?u=', 'icon_class' => 'icon-fontawesome-facebook'],
			['share_name' => 'Twitter', 'share_url' => 'https://twitter.com/home?status='.$title.' - ', 'icon_class' => 'icon-fontawesome-twitter'],
			['share_name' => 'Linkedin', 'share_url' => 'https://www.linkedin.com/shareArticle?mini=true&title='.$title.'&url=', 'icon_class' => 'icon-fontawesome-linkedin']
		];

		return $arr;
	}
	
	public static function getPublicInterfaceTypeSettings() {
											
		$arr = [
			['id' => 'central_types', 'name' => getLabel('lbl_public_interface_central_types')],
			['id' => 'media_types', 'name' => getLabel('lbl_public_interface_media_types')],
			['id' => 'exploration_types', 'name' => getLabel('lbl_public_interface_exploration_types')]
		];

		return $arr;
	}
	
	public static function getPublicInterfaceSelection($selection_id) {

		$arr = [];

		$res = DB::query("SELECT p.id, p.date_modified, p.title as selection_title, p.notes as selection_notes, p.editor as selection_editor, 
				pe.selection_id, pe.elm_id, pe.type as elm_type, pe.heading as elm_heading, pe.notes as elm_notes, pe.sort 
			FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." p
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe ON (pe.selection_id = p.id)
			WHERE p.id = '".DBFunctions::strEscape($selection_id)."'
			ORDER BY pe.sort
		");

		while($row = $res->fetchAssoc()) {

			if (!$arr['id']) {
				$arr = $row;
				$arr['elements'] = [];
			}
			if ($row['elm_id']) {
				$arr['elements'][$row['elm_id']] = $row;
			}
		}

		return $arr;
	}
	
	public static function storePublicInterfaceSelection($arr_selection) {

		$selection_url_id = $arr_selection['url_id'];

		if (!$selection_url_id) {
			
			$selection_url_id = generateRandomString(20);
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." 
				(id, date_modified, title, notes, editor) 
					VALUES 
				('".DBFunctions::strEscape($selection_url_id)."',
					NOW(),
					'".DBFunctions::strEscape($arr_selection['selection_title'])."',
					'".DBFunctions::strEscape($arr_selection['selection_notes'])."',
					'".DBFunctions::strEscape($arr_selection['selection_editor'])."'
				)");
								
		} else {
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." SET
					date_modified = NOW(),
					title = '".DBFunctions::strEscape($arr_selection['selection_title'])."',
					notes = '".DBFunctions::strEscape($arr_selection['selection_notes'])."',
					editor = '".DBFunctions::strEscape($arr_selection['selection_editor'])."'
				WHERE id = '".DBFunctions::strEscape($selection_url_id)."'");
						
		}

		$res = DB::query("DELETE pe
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe
			WHERE pe.selection_id = '".DBFunctions::strEscape($selection_url_id)."'");
		
		$arr_elements = $arr_selection['elements'];
		usort($arr_elements, function($a, $b) { return $a['sort'] > $b['sort']; }); 

		$sort = 0;
		
		foreach ((array)$arr_elements as $arr_element) {	
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')."
				(selection_id, elm_id, type, heading, notes, sort)
					VALUES 
				('".DBFunctions::strEscape($selection_url_id)."', '".DBFunctions::strEscape($arr_element['elm_id'])."', '".DBFunctions::strEscape($arr_element['elm_type'])."', '".DBFunctions::strEscape($arr_element['elm_heading'])."', '".DBFunctions::strEscape($arr_element['elm_notes'])."', ".(int)$sort.")
			");
		
			$sort++;
		}

		
		return $selection_url_id;
	}
	
	public static function delPublicInterfaceSelection($selection_url_id) {

		$res = DB::query("DELETE p, pe
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." p
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe ON (pe.selection_id = p.id)
			WHERE p.id = '".DBFunctions::strEscape($selection_url_id)."'
		");
	}
	
	public static function cleanupPublicInterfaceSelections($arr_options = []) {

		if ($arr_options['age_amount'] && $arr_options['age_unit']) {
			
			$minutes = $arr_options['age_amount'] * $arr_options['age_unit'];
			
			$res = DB::query("DELETE p, pe
					FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." p
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe ON (pe.selection_id = p.id)
				WHERE p.date_modified < (NOW() - ".DBFunctions::interval($minutes, 'MINUTE').")
			");
		} else {
			error(getLabel('msg_missing_information'));
		}
	}
}
