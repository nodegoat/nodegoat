<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class public_interfaces extends base_module {

	public static function moduleProperties() {
		static::$label = 'Public Interfaces';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'general' => []
	];
	
	public function contents() {

		$return .= self::createAddPublicInterface();

		$return .= '<table class="display" id="d:public_interfaces:data-0">
				<thead>
					<tr>
						<th class="max limit" data-sort="asc-0">'.getLabel('lbl_name').'</th>
						<th class="disable-sort"></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="2" class="empty">'.getLabel('msg_loading_server_data').'</td>
					</tr>
				</tbody>
			</table>';

		return $return;
	}
	
	private static function createAddPublicInterface() {
	
		$return = '<form id="f:public_interfaces:add-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_public_interface').'" />
			</menu>
		</form>';
		
		return $return;
	}
											
	private function createPublicInterface($public_interface_id = false) {

		$arr_public_interface = [];
		
		$arr_projects = [];		
		$arr_projects_full = StoreCustomProject::getProjects();
		
		$arr_selected_types = [];
		$primary_option_enabled = false; 
		
		foreach ($arr_projects_full as $key => $value) {
			$arr_projects[$key] = $value['project'];
		}
				
		if ((int)$public_interface_id) {		
			$arr_public_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_interface_id);			
		}

		$return = '<h1>'.($public_interface_id ? '<span>'.getLabel('lbl_public_interface').': '.strEscapeHTML($arr_public_interface['interface']['name']).'</span><small title="'.getLabel('lbl_public_interface').' ID">'.$public_interface_id.'</small>' : '<span>'.getLabel('lbl_public_interface').'</span>').'</h1>
		
		<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_interface').'</a></li>
				<li><a href="#">'.getLabel('lbl_projects').'</a></li>
				<li><a href="#">'.getLabel('lbl_options').'</a></li>
				<li><a href="#">'.getLabel('lbl_appearance').'</a></li>
				<li><a href="#">'.getLabel('lbl_texts').'</a></li>
			</ul>
			<div>
						
				<div class="options fieldsets"><div>
				
					<fieldset><legend>'.getLabel('lbl_public_interface').'</legend><ul>
						<li><label>'.getLabel('lbl_name').'</label><input name="name" type="text" value="'.strEscapeHTML($arr_public_interface['interface']['name']).'" /></li>
						<li><label>'.getLabel('lbl_default').'</label><input name="is_default" type="checkbox" value="1"'.($arr_public_interface['interface']['is_default'] ? ' checked="checked"' : '').' /></li>
					</ul></fieldset>
				
					<fieldset><legend>'.getLabel('lbl_projects').'</legend><ul>
						<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_projects, 'projects', array_keys((array)$arr_public_interface['projects']))).'</li>
					</ul></fieldset>
					
				</div></div>
				
			</div>
			<div>
						
				<div class="options">
				
					<section class="info attention">'.getLabel('inf_save_and_edit_section').'</section>';
					
					if ($arr_public_interface['projects']) {
						
						foreach ($arr_public_interface['projects'] as $project_id => $project) {
							
							if (!$arr_projects_full[$project_id]) {
								continue;
							}
							
							$arr_project = $arr_projects_full[$project_id];
							$tab_buttons .= '<li><a href="#tab-'.$project_id.'">'.Labels::parseTextVariables($arr_project['project']['name']).'</a></li>';
							$tab_divs .= '<div id="tab-'.$project_id.'"  class="interface-project" data-project_id="'.$project_id.'">'.self::createProject($arr_public_interface, $arr_project).'</div>';
							
							$arr_selected_types = array_merge($arr_selected_types, array_keys((array)$arr_public_interface['project_types'][$project_id]));
						}
						
						$return .= '<div class="tabs" data-sorting="1">
						<ul>'.$tab_buttons.'
						</ul>'
						.$tab_divs.'
						</div>';
					}
				
				$return .= '</div>
				
			</div>
			<div>
						
				<div class="options">
				
					<section class="info attention">'.getLabel('inf_save_and_edit_section').'</section>';
					
					$arr_types = StoreType::getTypes();

					if ($arr_public_interface['projects']) {
						
						
						$return .= '<div class="tabs">
							<ul>
								<li><a href="#">'.getLabel('lbl_options').'</a></li>
								<li><a href="#">'.getLabel('ttl_labels').'</a></li>
								<li><a href="#">'.getLabel('lbl_cite_as').'</a></li>
								<li><a href="#">'.getLabel('lbl_selection').'</a></li>
								<li><a href="#">'.getLabel('lbl_technical').'</a></li>
							</ul>
							<div>';

								$arr_filter_form_positions = [['id' => 'button', 'name' => getLabel('lbl_public_interface_filter_position_button')], ['id' => 'top', 'name' => getLabel('lbl_public_interface_filter_position_top')], ['id' => 'left', 'name' => getLabel('lbl_public_interface_filter_position_left')]];
	
								$return .= '<div class="options"><fieldset><legend>'.getLabel('lbl_general').' '.getLabel('lbl_options').'</legend><ul>
												<li><label>'.getLabel('lbl_public_interface_filter_position').'</label><div>'.cms_general::createSelectorRadio($arr_filter_form_positions, 'settings[filter_form_position]', ($arr_public_interface['interface']['settings']['filter_form_position'] ?: 'button')).'</div></li>
												<li><label>'.getLabel('lbl_public_interface_filter_apply_button').'</label><input name="settings[filter_form_apply_button]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['filter_form_apply_button'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_device_location').'</label><input name="settings[show_device_location]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_device_location'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_show_objects_in_list').'</label><input name="settings[show_objects_list]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_objects_list'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_show_media_tiles').'</label><input name="settings[show_media_thumbnails]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_media_thumbnails'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_hide_object_subs_overview').'</label><input name="settings[hide_object_subs_overview]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['hide_object_subs_overview'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_object_descriptions_in_object_view').'</label><input name="settings[show_object_descriptions_in_object_view]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_object_descriptions_in_object_view'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_display_filter_objects_buttons').'</label><input name="settings[show_keyword_buttons]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_keyword_buttons'] ? 'checked="checked"' : '').' /></li>
											</ul></fieldset></div>
											<div class="options"><fieldset><legend>'.getLabel('lbl_public_interface_object_buttons').'</legend><ul>
												<li><label>'.getLabel('lbl_public_interface_share_object_button').'</label><input name="settings[share_object_url]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['share_object_url'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_URL_object_button').'</label><input name="settings[show_object_url]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_object_url'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_public_interface_PDF_object_button'). '</label><input name="settings[pdf_object]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['pdf_object'] ? 'checked="checked"' : '').' /></li>
											</ul></fieldset></div>
											<div class="options"><fieldset><legend>'.getLabel('lbl_public_interface_object_visualisations').'</legend><ul>
												<li><label>'.getLabel('lbl_display').' '.getLabel('lbl_object').' '.getLabel('lbl_geo_visualisation').'</label><input name="settings[object_geo]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['object_geo'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_display').' '.getLabel('lbl_object').' '.getLabel('lbl_soc_visualisation').'</label><input name="settings[object_soc]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['object_soc'] ? 'checked="checked"' : '').' /></li>
												<li><label>'.getLabel('lbl_display').' '.getLabel('lbl_object').' '.getLabel('lbl_time_visualisation').'</label><input name="settings[object_time]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['object_time'] ? 'checked="checked"' : '').' /></li>
											</ul></fieldset></div>';		
					
								$return .= '<div class="options"><fieldset><legend>'.getLabel('lbl_object').' '.getLabel('lbl_type').'</legend><ul>							
											<li>
												<label></label>
												<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
											</li>
											<li>
												<label></label>
												<div>';
													$arr_types_settings_sorter = [];
													foreach (($arr_public_interface['interface']['settings']['types'] ?: [[]]) as $type_id => $arr) {
														
														if ($arr['primary']) {
															$primary_option_enabled = true;
														}

														$arr_types_settings_sorter[] = ['value' => [
															'<select class="type-settings">'.
																Labels::parseTextVariables(cms_general::createDropdown($arr_types, $type_id, true)).
															'</select>',
															'<fieldset><ul id="y:public_interfaces:create_type_settings_options-'.$public_interface_id.'">'.
																($type_id ? self::createTypeSettingsOptions($type_id, $arr_selected_types, $arr, $arr_public_interface['projects']) : '').
															'</ul></fieldset>'
														]];
													}
													$return .= cms_general::createSorter($arr_types_settings_sorter, false);
												$return .= '</div>
											</li>
										</ul></fieldset></div>';								

								if ($primary_option_enabled) {
									
									$return .= '<div class="options"><fieldset><legend>'.getLabel('lbl_public_interface_primary_references').'</legend><ul>
													<li><label>'.getLabel('lbl_public_interface_use_combined_references_as_filters').'</label><input name="settings[use_combined_references_as_filters]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['use_combined_references_as_filters'] ? 'checked="checked"' : '').' /></li>
													<li><label>'.getLabel('lbl_public_interface_references_in_object_view').'</label><input name="settings[show_references_in_object_view]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_references_in_object_view'] ? 'checked="checked"' : '').' /></li>
												</ul></fieldset></div>';													
								}
							$return .= '</div>
							<div>
								<div class="options fieldsets"><div>';
		
								$return .= '<fieldset><legend>'.getLabel('lbl_default').' '.getLabel('lbl_language').'</legend><ul>
											<li><select name="settings[default_language]">'.cms_general::createDropdown(cms_language::getLanguage(), $arr_public_interface['interface']['settings']['default_language'], true, 'label', 'lang_code').'</select></li>
										</ul></fieldset>';		
															
								$return .= '<fieldset><legend>'.getLabel('lbl_public_interface_custom_labels').'</legend><ul>';
								
									foreach ((array)$arr_types as $type_id => $value) {
										$return .= '<li><label>'.Labels::parseTextVariables($value['name']).'</label><input name="settings[labels][type]['.$type_id.'][singular]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['type'][$type_id]['singular'].'" title="Singular"/><input name="settings[labels][type]['.$type_id.'][plural]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['type'][$type_id]['plural'].'" title="Plural"/><input name="settings[icons][type]['.$type_id.']" type="text" value="'.$arr_public_interface['interface']['settings']['icons']['type'][$type_id].'" title="Icon"/></li>';
									}
									
								$return .= '<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_geo_visualisation').'</label><input name="settings[labels][explore_geo]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['explore_geo'].'" /></li>
									<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_soc_visualisation').'</label><input name="settings[labels][explore_soc]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['explore_soc'].'" /></li>
									<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_time_visualisation').'</label><input name="settings[labels][explore_time]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['explore_time'].'" /></li>
									<li><label>'.getLabel('lbl_search').' '.getLabel('lbl_start').'</label><input name="settings[labels][search_start]" type="text" value="'.strEscapeHTML($arr_public_interface['interface']['settings']['labels']['search_start']).'" /></li>
									<li><label>'.getLabel('lbl_search').' '.getLabel('lbl_end').'</label><input name="settings[labels][search_end]" type="text" value="'.strEscapeHTML($arr_public_interface['interface']['settings']['labels']['search_end']).'" /></li>
									<li><label>'.getLabel('lbl_information').'</label><input name="settings[labels][info]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['info'].'" /></li>';

																	
								$return .= '</ul></fieldset>';
																		
								$return .= '</div></div>
							</div>
							<div>
								<div class="options fieldsets"><div>';
								
								$return .= '<fieldset><ul>
												<li>
													<label></label>
													<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
												</li>
												<li>
													<label>'.getLabel('lbl_cite_as').'</label>
													<div>';
														$arr_sorter = [];
														foreach (($arr_public_interface['interface']['settings']['cite_as'] ?: [false => []]) as $type_id => $arr_value) {
															$arr_sorter[] = ['value' => [
																'<select name="cite_as_type_id">'.cms_general::createDropdown($arr_types, $type_id, true, 'name').'</select>',
																'<div id="y:public_interfaces:create_cite_as-0">'.self::createCiteAsSettings($type_id, $arr_value).'</div>'
															]];
														}
														$return .= cms_general::createSorter($arr_sorter, true);
													$return .= '</div>
												</li>
											</ul></fieldset>';
																	
								$return .= '</div></div>
							</div>
							<div>
								<div class="options fieldsets"><div>';
		
								$return .= '<fieldset><legend>'.getLabel('lbl_object').' '.getLabel('lbl_selection').'</legend><ul>
											<li><label>'.getLabel('lbl_public_interface_selection_object_button').'</label><input name="settings[selection]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['selection'] ? 'checked="checked"' : '').' /></li>
											<li><label>PDF '.getLabel('lbl_title').'</label><input name="settings[pdf][title]" type="text" value="'.$arr_public_interface['interface']['settings']['pdf']['title'].'" /></li>
											<li><label>PDF '.getLabel('lbl_subtitle').'</label><input name="settings[pdf][subtitle]" type="text" value="'.$arr_public_interface['interface']['settings']['pdf']['subtitle'].'" /></li>
											<li><label>PDF '.getLabel('lbl_public_interface_colophon').'</label><textarea name="settings[pdf][colofon]">'.$arr_public_interface['interface']['settings']['pdf']['colofon'].'</textarea></li>
											<li><label>PDF '.getLabel('lbl_public_interface_license').'</label><textarea name="settings[pdf][license]">'.$arr_public_interface['interface']['settings']['pdf']['license'].'</textarea></li>
										</ul></fieldset>';		
																		
								$return .= '</div></div>
							</div>
							<div>
								<div class="options fieldsets"><div>';

								$return .= '<fieldset><ul>
											<li><label>'.getLabel('lbl_url').' '.getLabel('lbl_server_host').'</label><div><input name="settings[short_url_host]" type="text" value="'.$arr_public_interface['interface']['settings']['short_url_host'].'" /></div></li>
											<li><label>'.getLabel('lbl_use').' '.getLabel('lbl_nodegoat_id').' for URI</label><div><input name="settings[uri_nodegoat_id]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['uri_nodegoat_id'] ? 'checked="checked"' : '').' /></div></li>
											<li><label>'.getLabel('lbl_open_site').' '.getLabel('lbl_url').'</label><div><input name="settings[return_url]" type="text" value="'.$arr_public_interface['interface']['settings']['return_url'].'" /></div></li>
										</ul></fieldset>';
																				
								$return .= '</div></div>
							</div>
						</div>';				
					}
					
				$return .= '</div>
				
			</div>
			<div>
			
				<div class="options fieldsets"><div>
				
					<fieldset><ul>
						<li><label>'.getLabel('lbl_color').'</label><input name="settings[color]" type="text" value="'.$arr_public_interface['interface']['settings']['color'].'" class="colorpicker" /></li>
						<li><label>'.getLabel('lbl_disable_responsive_layout').'</label><input name="settings[disable_responsive_layout]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['disable_responsive_layout'] ? 'checked="checked"' : '').' /></li>
					</ul></fieldset>
									
					
					<fieldset><ul>
						<li><label>'.getLabel('lbl_css').'</label><textarea name="css">'.$arr_public_interface['interface']['css'].'</textarea></li>
					</ul></fieldset>
				
				</div></div>
				
			</div>
			<div>
				
				<div class="options fieldsets"><div>	
				
					<fieldset><ul>
						<li>
							<label></label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label>'.getLabel('lbl_texts').'</label>
							<div>';
								$arr_sorter = [];
								foreach (($arr_public_interface['texts'] ?: [[], []]) as $value) {
									$arr_sorter[] = ['value' => [
										'<input type="text" name="text_name[]" value="'.strEscapeHTML($value['text_name']).'" />',
										cms_general::editBody($value['text'], 'text[]', ['inline' => true])
									]];
								}
								$return .= cms_general::createSorter($arr_sorter, true);
							$return .= '</div>
						</li>
					</ul></fieldset>
				
				</div></div>
				
			</div>
		</div>
		
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_public_interface').'" /><input type="submit" name="do_discard" value="'.getLabel("lbl_cancel").'" />
		</menu>';
		
		$this->validate = ['name' => 'required', 'user_id' => 'required'];
		
		return $return;
	}
	
	private static function createProject($arr_public_interface, $arr_project) {
		
		$project_id = $arr_project['project']['id'];
		$arr_types = StoreType::getTypes();
		$arr_type_scenarios = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id); 
		$arr_data_options = cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions();
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		$arr_settings = $arr_public_interface['interface']['settings'];
		$arr_display_options = cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions();
		
		$arr_filter_modes = [['id' => 'none', 'name' => getLabel('lbl_none')], ['id' => 'search', 'name' => getLabel('lbl_search')], ['id' => 'form', 'name' => getLabel('lbl_filter').' '.getLabel('lbl_form')]];
		$arr_start_modes = [['id' => 'none', 'name' => getLabel('lbl_none')], ['id' => 'scenario', 'name' => getLabel('lbl_scenario')], ['id' => 'types', 'name' => getLabel('lbl_type').' / '.getLabel('lbl_types')], ['id' => 'random', 'name' => getLabel('lbl_public_interface_random_objects')]];
		
		$arr_project_types = [];
		
		foreach ((array)$arr_project['types'] as $type_id => $arr) {
			
			$arr_project_types[$type_id] = ['id' => $type_id, 'name' => $arr_types[$type_id]['name']];
		}
		
		$arr_project_scenarios = [];

		foreach ((array)$arr_type_scenarios as $type_id => $arr_type_scenario) {
			
			foreach ((array)$arr_type_scenario as $scenario_id => $arr_scenario) {
			
				$arr_project_scenarios[$scenario_id] = ['id' => $scenario_id, 'name' => $arr_scenario['name']];
			}
		}	
	
		$filter_mode = ($arr_settings['projects'][$project_id]['filter_mode'] ?: 'search');
		$arr_display = ($arr_public_interface['project_types'][$project_id] ? $arr_public_interface['project_types'][$project_id][key($arr_public_interface['project_types'][$project_id])] : []);
		
		$return = '<div class="options">
					<fieldset><ul>
						<li>
							<label>'.getLabel('lbl_public_interface_project_name_in_menu').'</label>
							<input type="text" name="settings[projects]['.$project_id.'][name]" value="'.strEscapeHTML($arr_public_interface['interface']['settings']['projects'][$project_id]['name']).'" /></li>
						<li>
							<label>'.getLabel('lbl_filter').' '.getLabel('lbl_mode').'</label>
							<div>'.cms_general::createSelectorRadio($arr_filter_modes, 'project_filter_mode['.$project_id.']', ($filter_mode ?: 'none')).'</div>
						</li>
					</ul></fieldset>
				</div>
				
				<div class="options" id="y:public_interfaces:create_filter_options-'.$arr_public_interface['interface']['id'].'_'.$project_id.'">'.
					self::createFilterOptions($filter_mode, $arr_public_interface, $arr_project).
				'</div>';
				
				$has_scope_options = ($arr_settings['projects'][$project_id]['scope'] ? true : false);
				
				$return .= '<div class="options display scope-options">
					<fieldset>
						<ul>
							<li>
								<label>'.getLabel('lbl_public_interface_display_modes').'</label>
								<div>
									'.self::getVisualisationOptions('type', 'display', $project_id, $arr_display).
									'<input type="button" class="data neutral show-scope-options '.($has_scope_options ? 'hide' : '').'" value="select scopes" />
								</div>
							</li>';
							
							foreach (['browse', 'explore'] as $scope) {
							
								$return .= '<li '.($has_scope_options ? '' : 'class="hide"').'>
										<label>'.($scope == 'browse' ? getLabel('lbl_public_interface_filter_scope') : getLabel('lbl_public_interface_object_visualisation_scope')).'</label>
										<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
									</li>
									<li '.($has_scope_options ? '' : 'class="hide"').'>
										<label></label>
										<div>';
											$arr_types_scopes = $arr_settings['projects'][$project_id]['scope'][$scope];

											if (!$arr_types_scopes) {
												$arr_types_scopes = [[[[]]], [[[]]]];
											} else {
												$arr_empty_source = [[[]]];
												$arr_types_scopes = $arr_empty_source + $arr_types_scopes; // Empty run for sorter source
											}
										
											$arr_scope_sorter = [];
											$is_source = true;
								
											foreach ($arr_types_scopes as $type_id => $arr_type_scopes) {
												
												foreach ($arr_type_scopes as $display_mode => $scope_id) {
													
													$unique_scope = uniqid(cms_general::NAME_GROUP_ITERATOR);
													$str_name = 'settings[projects]['.$project_id.'][scope]['.$scope.']['.$unique_scope.']';
		
													$arr_scope_sorter[] = ['source' => $is_source, 'value' => [
														'<select name="'.$str_name.'[type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_project_types, $type_id, true)).'</select>'.
														'<select name="'.$str_name.'[display_mode]">'.cms_general::createDropdown($arr_display_options, $display_mode, true).'</select>'.
														self::createScopeOptions($str_name, $project_id, $type_id, $scope_id)
													]];

													if ($is_source) {
														$is_source = false;
													}
												}
											}
											$return .= cms_general::createSorter($arr_scope_sorter, true);
										$return .= '</div>
									</li>';
							}
				$return .= '
						</ul>
					</fieldset>
				</div>';
				
				if (count($arr_project_scenarios)) {
				
					$return .= '<div class="options">
						<fieldset>
							<legend>'.getLabel('lbl_Select').' '.getLabel('lbl_Scenarios').'</legend>
							<ul>
								<li>
									<label></label>
									<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
								</li>
								<li>
									<label></label>
									<div>';
										$arr_scenarios = $arr_public_interface['project_scenarios'][$project_id];
										if (!$arr_scenarios) {
											$arr_scenarios[] = [];
										}
										array_unshift($arr_scenarios, []); // Empty run for sorter source
									
										$arr_filter_sorter = [];
									
										foreach ($arr_scenarios as $key => $arr_scenario) {
											
											$unique_scenario = uniqid(cms_general::NAME_GROUP_ITERATOR);
											
											$arr_filter_sorter[] = ['source' => ($key == 0 ? true : false), 'value' => [
												'<select name="project_scenarios['.$project_id.']['.$unique_scenario.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_project_scenarios, $arr_scenario['scenario_id'], true)).'</select>'.
												self::getVisualisationOptions('scenario', $unique_scenario, $project_id, $arr_scenario)
											]];
										}
										$return .= cms_general::createSorter($arr_filter_sorter, true);
									$return .= '</div>
								</li>
							</ul>
						</fieldset>
					</div>';
				}
				
				$start_mode = $arr_public_interface['interface']['settings']['projects'][$project_id]['start']['mode'];

				$return .= '<div class="options">
					<fieldset>
						<legend>'.getLabel('lbl_public_interface_start_configuration').'</legend>
						<ul>
							<li>
								<label></label>
								<select name="settings[projects]['.$project_id.'][start][mode]">'.
									Labels::parseTextVariables(cms_general::createDropdown($arr_start_modes, $arr_settings['projects'][$project_id]['start']['mode'], false)).
								'</select>'.
								'<select class="scenario '.($start_mode == 'scenario' ? '' : 'hide').'" name="settings[projects]['.$project_id.'][start][scenario][id]" title="'.getLabel('lbl_start').' '.getLabel('lbl_scenario').'">'.
									cms_general::createDropdown($arr_project_scenarios, $arr_settings['projects'][$project_id]['start']['scenario']['id'], false).
								'</select>'.
								'<select class="scenario '.($start_mode == 'scenario' ? '' : 'hide').'" name="settings[projects]['.$project_id.'][start][scenario][display_mode]">'.
									cms_general::createDropdown(cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions(), $arr_settings['projects'][$project_id]['start']['scenario']['display_mode'], false).
								'</select>'.
								'<select class="types '.($start_mode == 'types' ? '' : 'hide').'" name="settings[projects]['.$project_id.'][start][types][display_mode]">'.
									cms_general::createDropdown(cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions(), $arr_settings['projects'][$project_id]['start']['types']['display_mode'], false).
								'</select>'.
								'<input class="random '.($start_mode == 'random' ? '' : 'hide').'" type="number" name="settings[projects]['.$project_id.'][start][random][amount]" value="'.$arr_settings['projects'][$project_id]['start']['random']['amount'].'" title="'.getLabel('lbl_amount').'" />'.
								'<fieldset class="options random '.($start_mode == 'random' ? '' : 'hide').'">
									<ul>
										<li>
											<label></label>
											<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
										</li>
										<li>
											<label></label>
											<div>';
												$arr_sorter = [];
												foreach (($arr_settings['projects'][$project_id]['start']['random']['types'] ?: [[]]) as $random_type_id => $arr_value) {
													$arr_sorter[] = ['value' => [
														'<select name="settings[projects]['.$project_id.'][start][random][types]['.$random_type_id.'][set]">'.
															cms_general::createDropdown($arr_project_types, $random_type_id, true).
														'</select>'.
														self::createStartRandomTypeFilter($project_id, $random_type_id, $arr_value['filter_id'])
													]];
												}
												$return .= cms_general::createSorter($arr_sorter, true);
											$return .= '</div>
										</li>
									</ul>
								</fieldset>
							</li>
						</ul>
					</fieldset>
				</div>
				
				<div class="options">
					<fieldset>
						<legend>'.getLabel('lbl_advanced').'</legend>
						<ul>
							<li><label>'.getLabel('lbl_public_interface_display_grid_overlay').'</label><input name="settings[projects]['.$project_id.'][show_grid_overlay]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['show_grid_overlay'] ? 'checked="checked"' : '').' /></li>
							<li><label>'.getLabel('lbl_public_interface_display_object_fullscreen').'</label><input name="settings[projects]['.$project_id.'][show_object_fullscreen]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['show_object_fullscreen'] ? 'checked="checked"' : '').' /></li>
							<li><label>'.getLabel('lbl_public_interface_display_object_visualisations_first').'</label><input name="settings[projects]['.$project_id.'][show_explore_visualisations]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['projects'][$project_id]['show_explore_visualisations'] ? 'checked="checked"' : '').' /></li>
							<li>
								<label>'.getLabel('lbl_public_interface_order_objects_by').'</label>
								<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
							</li>
							<li>
								<label></label>
								<div>';
									$arr_types_sort_sorter = [];
									foreach (($arr_settings['projects'][$project_id]['sort'] ?: [[]]) as $type_id => $sort_id) {

										$arr_types_sort_sorter[] = ['value' => [
											'<select class="type-sort">'.
												Labels::parseTextVariables(cms_general::createDropdown($arr_project_types, $type_id, true)).
											'</select>'.
											self::createTypeSortOptions($project_id, $type_id, $sort_id)
										]];
									}
									$return .= cms_general::createSorter($arr_types_sort_sorter, true);
								$return .= '</div>
							</li>
						</ul>
					</fieldset>
				</div>';
			
		return $return;
	}	
	
	private static function createFilterOptions($filter_mode, $arr_public_interface, $arr_project) {
		
		$project_id = $arr_project['project']['id'];
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		$arr_settings = $arr_public_interface['interface']['settings']['projects'][$project_id];
		$arr_types = StoreType::getTypes();
		$arr_project_types = [];
		
		foreach ((array)$arr_project['types'] as $type_id => $arr) {
			
			$arr_project_types[$type_id] = ['id' => $type_id, 'name' => $arr_types[$type_id]['name']];
		}

		if ($filter_mode == 'search' || $filter_mode == 'none') { 	
						
			$return = '<fieldset>
						<ul>
							<li '.($filter_mode == 'none' ? 'class="hide"' : '').'>
								<label>'.getLabel('lbl_public_interface_search_bar_info_text').'</label>
								<input type="text" name="settings[projects]['.$project_id.'][info_search]" value="'.strEscapeHTML($arr_settings['info_search']).'" />
							</li>
							<li '.($filter_mode == 'search' ? 'class="hide"' : '').'>
								<label>'.getLabel('lbl_public_interface_project_description_button_text').'</label>
								<input type="text" name="settings[projects]['.$project_id.'][info_title]" value="'.strEscapeHTML($arr_public_interface['interface']['settings']['projects'][$project_id]['info_title']).'" /></li>
							<li>
								<label>'.getLabel('lbl_public_interface_project_description').'</label>
								<textarea name="settings[projects]['.$project_id.'][info]">'.strEscapeHTML($arr_public_interface['interface']['settings']['projects'][$project_id]['info']).'</textarea>
							</li>
							<li '.($filter_mode == 'none' ? 'class="hide"' : '').'>
								<label>'.getLabel('lbl_select').' '.getLabel('lbl_object').' '.getLabel('lbl_types').'</label>
								<input name="settings[projects]['.$project_id.'][select_types]" type="checkbox" value="1" '.($arr_settings['select_types'] ? 'checked="checked"' : '').' />
							</li>
							<li>
								<label>'.getLabel('lbl_object').' '.getLabel('lbl_types').'</label>
								<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
							</li>
							<li>
								<label></label>
								<div>';
									$arr_types_sorter = [];
									foreach (($arr_public_interface['project_types'][$project_id] ?: [[]]) as $type_id => $arr) {
										
										if ($arr['type_is_filter']) {
											continue;
										}
										
										$arr_html = [];
										
										$html_select_type = '<select name="project_types['.$project_id.'][types][]" class="search-type-id">'.
												Labels::parseTextVariables(cms_general::createDropdown($arr_project_types, $type_id, true)).
											'</select>';
											
										$arr_html[] = $html_select_type;
										
										$has_options = ($arr_settings['sort'][$type_id] || $arr_settings['export'][$type_id] || $arr_settings['primary_project'][$type_id] || $arr_settings['show_object_subs'][$type_id]);

										$html_options = '<span id="y:public_interfaces:create_type_options-'.$arr_public_interface['interface']['id'].'_'.$project_id.'" >'.
												self::createTypeOptions($project_id, $type_id, $arr_public_interface).
											'</span>';
											
										$html_options = '<fieldset>
											<ul><li>
												'.($has_options ? $html_options : '<div class="hide-edit hide">'.$html_options.'</div><input type="button" class="data neutral" value="more" />').'
											</li></ul>
										</fieldset>';
											
										$arr_html[] = $html_options;
										
										$arr_types_sorter[] = ['value' => $arr_html];
									}
									$return .= cms_general::createSorter($arr_types_sorter, true);
								$return .= '</div>
							</li>
							<li '.($filter_mode == 'none' ? 'class="hide"' : '').'>
								<label>'.getLabel('lbl_filter').' '.getLabel('lbl_object').' '.getLabel('lbl_types').'</label>
								<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
							</li>
							<li '.($filter_mode == 'none' ? 'class="hide"' : '').'>
								<label></label>
								<div>';
									$arr_filter_types_sorter = [];
									foreach (($arr_public_interface['project_types'][$project_id] ?: [[]]) as $type_id => $arr) {
										
										if (!$arr['type_is_filter']) {
											continue;
										}
										
										$arr_filter_types_sorter[] = ['value' => [
											'<select name="project_types['.$project_id.'][filters][]">'.
												Labels::parseTextVariables(cms_general::createDropdown($arr_project_types, $type_id, true)).
											'</select>'
										]];
									}
									
									if (!count($arr_filter_types_sorter)) {
										
										$arr_filter_types_sorter[] = ['value' => [
											'<select name="project_types['.$project_id.'][filters][]">'
												.Labels::parseTextVariables(cms_general::createDropdown($arr_project_types, false, true)).
											'</select>'
										]];
									}
									$return .= cms_general::createSorter($arr_filter_types_sorter, true);
								$return .= '</div>
							</li>
						</ul>
					</fieldset>';
					
		} else if ($filter_mode == 'form') {
			
			$type_id = ($arr_public_interface['project_types'][$project_id] ? key($arr_public_interface['project_types'][$project_id]) : false);
			$has_options = ($arr_settings['export'][$type_id] || $arr_settings['primary_project'][$type_id] || $arr_settings['show_object_subs'][$type_id]);
			$html_options = '<span id="y:public_interfaces:create_type_options-'.$arr_public_interface['interface']['id'].'_'.$project_id.'">'.self::createTypeOptions($project_id, $type_id, $arr_public_interface).'</span>';
			
			$return = '<fieldset><ul>
						<li>
							<label>'.getLabel('lbl_type').'</label>
							<ul class="sorter"><li>
								<ul><li>
									<select name="project_types['.$project_id.'][types][]" class="filter-form-type-id">'.
										Labels::parseTextVariables(cms_general::createDropdown($arr_project_types, $type_id, false)).
									'</select>
								</li>
								<li>
									<fieldset>
										<ul>
											<li>'.($has_options ? $html_options : '<div class="hide-edit hide">'.$html_options.'</div><input type="button" class="data neutral" value="more" />').'</li>
										</ul>
									</fieldset>									
								</li></ul>
							</li></ul>
						</li>
						<li>
							<label>'.getLabel('lbl_filter').' '.getLabel('lbl_form').'</label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label></label>
							<div id="y:public_interfaces:create_filter_form_options-0">'.
								self::createFilterFormOptions($project_id, ($type_id ?: key($arr_project_types)), ($arr_settings['filter_form'] ?: [[], []])).
							'</div>
						</li>
					</ul></fieldset>';
					
		}
					
		return $return;
	}
	
	private static function createTypeOptions($project_id, $type_id, $arr_public_interface) {
		
		$arr_settings = $arr_public_interface['interface']['settings']['projects'][$project_id];
		$arr_export_settings = ($type_id ? cms_nodegoat_custom_projects::getProjectTypeExportSettings($project_id, $_SESSION['USER_ID'], $type_id) : []);
		
		if (count($arr_export_settings)) {

			$return .= '<select name="settings[projects]['.$project_id.'][export]['.$type_id.']" title="'.getLabel('lbl_export').' '.getLabel('lbl_settings').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_export_settings, $arr_settings['export'][$type_id], true)).'</select>';			
		}
		
		if (count($arr_public_interface['projects']) > 1) {

			$return .= '<input name="settings[projects]['.$project_id.'][primary_project]['.$type_id.']" type="checkbox" value="1" '.($arr_settings['primary_project'][$type_id] ? 'checked="checked"' : '').' title="'.getLabel('lbl_primary_project').'" />';	
		}
		
		$return .= '<input name="settings[projects]['.$project_id.'][show_object_subs]['.$type_id.']" type="checkbox" value="1" '.($arr_settings['show_object_subs'][$type_id] ? 'checked="checked"' : '').' title="'.getLabel('lbl_show').' '.getLabel('lbl_object_subs').'" />';	
					
		return $return;
	}
	
	private static function createScopeOptions($str_name, $project_id, $type_id, $scope_id = false) {

		$arr_project_scopes = ($type_id ? cms_nodegoat_custom_projects::getProjectTypeScopes($project_id, $_SESSION['USER_ID'], $type_id, false) : []);

		$return = '<select id="y:public_interfaces:create_scope_options-'.$project_id.'" name="'.$str_name.'[scope_id]">'.
						cms_general::createDropdown($arr_project_scopes, $scope_id, true, 'label').
					'</select>';
					
		return $return;
	}	
	
	private static function createTypeSettingsOptions($type_id, $arr_selected_types = [], $arr = [], $arr_projects = []) {
		
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id);
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_descriptions = [];
		
		// Use textual values for meta descriptions
		foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$str_id = 'object_description_'.$object_description_id;
		
			if ($arr_object_description['object_description_value_type_base'] == '' || strpos($arr_object_description['object_description_value_type_base'], 'text') !== false) {
				$arr_descriptions[$str_id] = $arr_type_set_flat[$str_id];
			}
		}
		
		$elm_meta_description .= '<select name="settings[types]['.$type_id.'][meta_description]" title="'.getLabel('inf_public_interface_meta_description').'">'.
					Labels::parseTextVariables(cms_general::createDropdown($arr_descriptions, $arr['meta_description'], true)).
				'</select>';
		
		$arr_filters = [];
		foreach ((array)$arr_projects as $project_id => $arr_project) {
			
			$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false));
	
			foreach ($arr_project_filters as $filter_id => $arr_filter) {
				
				if ($arr_filter['project_id'] == 0) {
					
					$arr_filters[$filter_id] = $arr_filter;
				}
			}
		}

		$elm_override_filter .= '<select name="settings[types]['.$type_id.'][override_filter]" title="'.getLabel('inf_public_interface_override_filter').'">'.
					Labels::parseTextVariables(cms_general::createDropdown($arr_filters, $arr['override_filter'], true)).
				'</select>';
			

		$arr_type_settings_options = [['id' => 'view', 'name' => getLabel('lbl_public_interface_view_objects'), 'title' => getLabel('inf_public_interface_view_objects')], ['id' => 'media', 'name' => getLabel('lbl_public_interface_media'), 'title' => getLabel('inf_public_interface_media')], ['id' => 'explore', 'name' => getLabel('lbl_public_interface_explore'), 'title' => getLabel('inf_public_interface_explore')], ['id' => 'primary', 'name' => getLabel('lbl_public_interface_primary'), 'title' => getLabel('inf_public_interface_primary')]];

		foreach ($arr_type_settings_options as $arr_type_settings_option) {
			
			// Only object types that have been selected in a project can be set as 'primary'
			if ($arr_type_settings_option['id'] == 'primary' && !in_array($type_id, $arr_selected_types)) {
				continue;
			}
			
			// Selected object types are viewable by default
			if ($arr_type_settings_option['id'] == 'view' && in_array($type_id, $arr_selected_types)) {
				continue;
			}
			
			$elm_checkboxes .= '<label title="'.$arr_type_settings_option['title'].'"><input name="settings[types]['.$type_id.']['.$arr_type_settings_option['id'].']" type="checkbox" value="1" '.($arr[$arr_type_settings_option['id']] ? 'checked="checked"' : '').' /><span>'.$arr_type_settings_option['name'].'</span></label>';	
		}

		$return = '<li><div>'.$elm_checkboxes.'</div></li>
					<li><div>'.$elm_meta_description.$elm_override_filter.'</div></li>';
		
		return $return;
	}
	
	private static function createTypeSortOptions($project_id, $type_id, $sort_id = false) {

		$arr_object_analyses = ($type_id ? cms_nodegoat_custom_projects::getProjectTypeAnalyses($project_id, $_SESSION['USER_ID'], $type_id) : []);
		$return = '<select id="y:public_interfaces:create_type_sort_options-'.$project_id.'" name="settings[projects]['.$project_id.'][sort]['.$type_id.']">'.
						cms_general::createDropdown((array)$arr_object_analyses, $sort_id, true).
					'</select>';

		return $return;
	}
	
	private static function createStartRandomTypeFilter($project_id, $type_id, $filter_id = false) {
	
		$arr_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false));
		$return = '<select id="y:public_interfaces:create_start_random_type_filter-0" name="settings[projects]['.$project_id.'][start][random][types]['.$type_id.'][filter_id]" title="'.getLabel('lbl_filter').'">'.
						($type_id ? cms_general::createDropdown($arr_filters, $filter_id) : '').
					'</select>';
		
		return $return;
	}
	
	private static function getVisualisationOptions($set, $id, $project_id, $arr_values = false) {
		
		$arr_data_options = cms_nodegoat_public_interfaces::getPublicInterfaceDataOptions();
		
		foreach ($arr_data_options as $key => $arr_data_option) {
		
			$return .= '<label>
					<input name="project_'.$set.'s['.$project_id.']['.$id.']['.$key.']" type="checkbox" value="1" '.($arr_values[$set.'_'.$key] ? 'checked="checked"' : '').' title="'.$arr_data_option['name'].'"/>
					<span class="icon">'.getIcon($arr_data_option['icon']).'</span>
				</label>';
		}
		
		return $return;
	}	
	
	private static function createFilterFormOptions($project_id, $type_id, $arr = [[], []]) {
					
		if (!$type_id) {
			return false;
		}		
	
		$arr_options = [
			'description' => ['id' => 'description', 'name' => getLabel('lbl_description')],
			'object' => ['id' => 'object', 'name' => getLabel('lbl_object')],
			'search' => ['id' => 'search', 'name' => getLabel('lbl_search')],
			'date' => ['id' => 'date', 'name' => getLabel('lbl_date')]
		];
		

		$arr_types = StoreType::getTypes();
			
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id);
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_descriptions = [];
		$arr_reversals = [];

		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
		
			$str_id = 'object_description_'.$object_description_id;
		
			if ($arr_object_description['object_description_value_type_base'] == 'reversal' || 
				$arr_object_description['object_description_value_type_base'] == 'classification' || 
				$arr_object_description['object_description_value_type_base'] == 'reference_mutable' || 
				$arr_object_description['object_description_value_type_base'] == 'type' || 
				$arr_object_description['object_description_value_type_base'] == 'boolean' ||
				$arr_object_description['object_description_value_type_base'] == 'int') {
				$arr_descriptions[$str_id] = $arr_type_set_flat[$str_id];
			}
			
			if ($arr_object_description['object_description_value_type_base'] == 'reversal' || $arr_object_description['object_description_value_type_base'] == 'reference_mutable') {
				$arr_reversals[$str_id] = true;
			}
		}
		
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
	
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
			
				$str_id = 'object_sub_description_'.$object_sub_description_id;

				if ($arr_object_sub_description['object_sub_description_value_type_base'] == 'reversal' || 
					$arr_object_sub_description['object_sub_description_value_type_base'] == 'classification' || 
					$arr_object_sub_description['object_sub_description_value_type_base'] == 'type' || 
					$arr_object_sub_description['object_sub_description_value_type_base'] == 'boolean' ||
					$arr_object_sub_description['object_sub_description_value_type_base'] == 'int') {
					$arr_descriptions[$str_id] = $arr_type_set_flat[$str_id];
				}
				
				if ($arr_object_sub_description['object_sub_description_value_type_base'] == 'reversal') {
					$arr_reversals[$str_id] = true;
				}
			}
		}

		$arr_sorter = [];
		foreach ($arr as $arr_value) {
			
			$unique_form = uniqid(cms_general::NAME_GROUP_ITERATOR);
			
			$arr_sorter[] = ['value' => [
				'<select name="project_filter_form['.$project_id.']['.$unique_form.'][form_element]">'.
					Labels::parseTextVariables(cms_general::createDropdown($arr_options, $arr_value['form_element'], true)).
				'</select>'.
				'<select name="project_filter_form['.$project_id.']['.$unique_form.'][description]" class="description '.(!$arr_value['form_element'] || $arr_value['form_element'] == 'description' ? '' : 'hide').'" data-reversals="'.strEscapeHTML(value2JSON($arr_reversals)).'">'.
					Labels::parseTextVariables(cms_general::createDropdown($arr_descriptions, $arr_value['description'])).
				'</select>'.
				'<select name="project_filter_form['.$project_id.']['.$unique_form.'][description_ref_type_id]" class="description-ref-id '.($arr_value['description_ref_type_id'] ? '' : 'hide').'">'.
					Labels::parseTextVariables(cms_general::createDropdown($arr_types, $arr_value['description_ref_type_id'], true)).
				'</select>'.
				'<input type="text" class="date '.($arr_value['form_element'] == 'date' ? '' : 'hide').'" name="project_filter_form['.$project_id.']['.$unique_form.'][date_min]" value="'.($arr_value['date_min'] ?: '1800').'" placeholder="d-m-y">'.
				'<input type="text" class="date '.($arr_value['form_element'] == 'date' ? '' : 'hide').'" name="project_filter_form['.$project_id.']['.$unique_form.'][date_max]" value="'.($arr_value['date_max'] ?: '1900').'" placeholder="d-m-y">'.
				'<input type="text" name="project_filter_form['.$project_id.']['.$unique_form.'][placeholder]" value="'.$arr_value['placeholder'].'" placeholder="Placeholder Text">'
			]];
		}
		
		$return .= cms_general::createSorter($arr_sorter, true);

		
		return $return;
	}
	
	private static function createCiteAsSettings($type_id, $arr = [[], []]) {
		
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
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
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

	public static function css() {
		
		$return = '.options fieldset > ul > li > label:first-child + textarea[name="css"] { width: 1200px; height: 600px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.public_interfaces', function(elm_scripter) {
		
			elm_scripter.on('click', '.edit', function() {
				$(this).quickCommand(elm_scripter.find('form'), {html: 'replace'});
			}).on('change', '[name*=\"project_filter_mode\"]', function() {	
			
				var cur = $(this);
				var mode = this.value;
				var elm_project_form = cur.closest('.interface-project');
				var elm_filter = elm_project_form.find('[id^=y\\\:public_interfaces\\\:create_filter_options-]');

				elm_filter.data({value: {mode: mode}}).quickCommand(elm_filter);

			}).on('change', 'select.search-type-id', function() {	

				var cur = $(this);
				var type_id = this.value;
				
				var elm_target = cur.parent().next('li').find('div');
				elm_target.data({value: {type_id: type_id}}).quickCommand(elm_target);

			}).on('change', 'select.filter-form-type-id', function() {
			
				var type_id = $(this).val();
				var project_id = $(this).closest('.interface-project').attr('data-project_id');
				var elm_target = $(this).closest('fieldset').find('[id^=y\\\:public_interfaces\\\:create_filter_form_options-]');
				elm_target.data({value: {type_id: type_id, project_id: project_id}}).quickCommand(elm_target);
		
			}).on('change', '[id^=y\\\:public_interfaces\\\:create_filter_form_options-] [name*=\"[form_element]\"]', function() {
				
				var cur = $(this);
				var value = cur.val();
				cur.siblings().addClass('hide');
				
				if (value == 'description') {
					cur.nextAll('[name*=description], [name*=placeholder]').removeClass('hide').trigger('change');
				} else if (value == 'object' || value == 'search') {
					cur.nextAll('[name*=placeholder]').removeClass('hide');
				} else if (value == 'date') {
					cur.nextAll('[name*=date_]').removeClass('hide');
				}
			}).on('change', '[id^=y\\\:public_interfaces\\\:create_filter_form_options-] [name*=\"[description]\"]', function() {
				
				var cur = $(this);
				var value = cur.val();
				var arr_reversals = JSON.parse(cur.attr('data-reversals'));
				
				cur.nextAll('[name*=description_ref_type_id]').addClass('hide');
				
				if (arr_reversals[value]) {
					cur.nextAll('[name*=description_ref_type_id]').removeClass('hide');
				}
				
			}).on('click', '.show-scope-options', function() {	
			
				var cur = $(this);				
				var elm_target = cur.closest('li').nextAll('li');
				elm_target.removeClass('hide');
				cur.remove();

			}).on('change', 'div.scope-options select[name*=\"type_id\"]', function() {	
		
				var cur = $(this);
				var type_id = cur.val();
				
				var elm_target = cur.nextAll('select[name*=\"scope_id\"]');
				var name = cur.attr('name');
				
				elm_target.data({value: {type_id: type_id, name: name}}).quickCommand(elm_target, {html: 'replace'});

			}).on('change', '[name$=\"[start][mode]\"]', function() {
				
				var cur = $(this);
				var mode = this.value;
				
				cur.siblings().addClass('hide');
				
				if (mode == 'random') {
					cur.nextAll('.random').removeClass('hide');
				} else if (mode == 'scenario') {
					cur.nextAll('.scenario').removeClass('hide');
				} else if (mode == 'types') {
					cur.nextAll('.types').removeClass('hide');
				}
			}).on('change', '[name*=\"[start][random][types]\"]', function() {
				
				var type_id = $(this).val();
				var project_id = $(this).closest('.interface-project').attr('data-project_id');
				var elm_target = $(this).next('select');
				elm_target.data({value: {type_id: type_id, project_id: project_id}}).quickCommand(elm_target, {html: 'replace'});
				
			}).on('change', '.type-sort', function() {
	
				var type_id = $(this).val();
				var elm_target = $(this).next('select');
				elm_target.data({value: {type_id: type_id}}).quickCommand(elm_target, {html: 'replace'});
		
			}).on('change', '.type-settings', function() {
	
				var type_id = $(this).val();
				var elm_target = $(this).closest('ul').find('[id^=y\\\:public_interfaces\\\:create_type_settings_options-]');
				elm_target.data({value: {type_id: type_id}}).quickCommand(elm_target);

			}).on('change', '[name*=cite_as_type_id]', function() {
				var type_id = $(this).val();
				var target = $(this).parent().next('li').children('div');
				target.data({value: type_id}).quickCommand(target);
			}).on('change', '[name*=citation_elm]', function() {
				var elm = $(this);
				var value = elm.val();
				elm.siblings().addClass('hide');
				
				if (value == 'value') {
					elm.nextAll('[name*=value]').removeClass('hide');
				} else if (value == 'string') {
					elm.nextAll('[name*=string]').removeClass('hide');
				}
			});
		});";

		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		if ($method == "add") {
			$this->html = '<form id="f:public_interfaces:insert-0">'.self::createPublicInterface().'</form>';
		}
		
		if ($method == "edit") {
			$this->html = '<form id="f:public_interfaces:update-'.$id.'">'.self::createPublicInterface($id).'</form>';
		}
				
		if ($method == "create_filter_options") {
			
			$arr_id = explode('_', $id);
			$public_interface_id = $arr_id[0];
			$project_id = $arr_id[1];
			$arr_public_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_interface_id);
			$arr_project = StoreCustomProject::getProjects($project_id);
			$filter_mode = $value['mode'];
			
			$this->html = self::createFilterOptions($filter_mode, $arr_public_interface, $arr_project);
		}
				
		if ($method == "create_type_options") {

			$arr_id = explode('_', $id);
			$public_interface_id = $arr_id[0];
			$project_id = $arr_id[1];
			$type_id = $value['type_id'];
			$arr_public_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_interface_id);
			
			$this->html = self::createTypeOptions($project_id, $type_id, $arr_public_interface);
		}
		
		if ($method == "create_scope_options") {

			$project_id = $id;
			$type_id = $value['type_id'];
			$str_name = str_replace('[type_id]', '', $value['name']);
			
			$this->html = self::createScopeOptions($str_name, $project_id, $type_id);
		}
		
		if ($method == "create_start_random_type_filter") {

			$this->html = self::createStartRandomTypeFilter($value['project_id'], $value['type_id'], false);
		}
			
		if ($method == "create_filter_form_options") {

			$this->html = self::createFilterFormOptions($value['project_id'], $value['type_id']);
		}
		
		if ($method == "create_type_sort_options") {

			$this->html = self::createTypeSortOptions($id, $value['type_id']);
		}
		
		if ($method == "create_type_settings_options") {
			
			$public_interface_id = $id;
			$arr_selected_types = [];
			
			if ((int)$public_interface_id) {
						
				$arr_public_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_interface_id);
				
				if ($arr_public_interface['projects']) {
						
					foreach ($arr_public_interface['projects'] as $project_id => $project) {
						
						$arr_selected_types = array_merge($arr_selected_types, array_keys((array)$arr_public_interface['project_types'][$project_id]));
					}
				}			
			}
			
			$this->html = self::createTypeSettingsOptions($value['type_id'], $arr_selected_types, [], $arr_public_interface['projects']);
		}
				
		if ($method == "create_cite_as") {

			$this->html = self::createCiteAsSettings($value);
		}
		
		if ($method == "data") {
			
			$arr_sql_columns = ['nodegoat_pi.name'];
			$arr_sql_columns_search = ['nodegoat_pi.name'];
			$arr_sql_columns_as = ['nodegoat_pi.id', 'nodegoat_pi.name'];
			
			$sql_table = DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." nodegoat_pi";
			
			$sql_index = 'nodegoat_pi.id';
			$sql_where = '';
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				$arr_data['id'] = 'x:public_interfaces:public_interface_id-'.$arr_row['id'];
				$arr_data[] = Labels::parseTextVariables($arr_row['name']);
				$arr_data[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data del msg del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
	
		// QUERY
		
		if (($method == "insert" || $method == "update") && $this->is_discard) {
			
			$this->html = self::createAddPublicInterface();
			return;
		}
				
		if ($method == "insert" || $method == "update") {

			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
			}
			
			$arr_texts = [];
			foreach ($_POST['text_name'] as $key => $value) {
				if ($_POST['text_name'][$key]) {
					$arr_texts[] = ['name' => $_POST['text_name'][$key],
						'text' => $_POST['text'][$key],
					];
				}
			}
			
			foreach ((array)$_POST['settings']['types'] as $key => $arr) {
				if (is_array($arr)) {
					$_POST['settings']['types'][$key] = array_filter($arr);
				}
			}

			cms_nodegoat_public_interfaces::handlePublicInterface((int)$id, $_POST, $arr_texts);
			
			$this->html = self::createAddPublicInterface();						
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
			}
		
			cms_nodegoat_public_interfaces::delPublicInterface($id);
								
			$this->msg = true;
		}
	}	
}
