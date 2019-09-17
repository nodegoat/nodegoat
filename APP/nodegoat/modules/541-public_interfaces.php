<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
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
						<th class="max limit">'.getLabel('lbl_name').'</th>
						<th class="disable-sort"></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="4" class="empty">'.getLabel('msg_loading_server_data').'</td>
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
		$arr_projects_full = cms_nodegoat_custom_projects::getProjects();
		
		foreach ($arr_projects_full as $key => $value) {
			$arr_projects[$key] = $value['project'];
		}
				
		if ((int)$public_interface_id) {		
			$arr_public_interface = cms_nodegoat_public_interfaces::getPublicInterfaces($public_interface_id);			
		}

		$return = '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_interface').'</a></li>
				<li><a href="#">'.getLabel('lbl_projects').'</a></li>
				<li><a href="#">'.getLabel('lbl_settings').'</a></li>
				<li><a href="#">'.getLabel('lbl_appearance').'</a></li>
				<li><a href="#">'.getLabel('lbl_texts').'</a></li>
			</ul>
			<div>
						
				<div class="options fieldsets"><div>
				
					<fieldset><legend>'.getLabel('lbl_public_interface').'<span>'. ($public_interface_id ? ' ['.$public_interface_id.']':'').'</span></legend><ul>
						<li><label>'.getLabel('lbl_name').'</label><input name="name" type="text" value="'.$arr_public_interface['interface']['name'].'" /></li>
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
							
							$arr_project = $arr_projects_full[$project_id];
							$tab_buttons .= '<li><a href="#tab-'.$project_id.'">'.Labels::parseTextVariables($arr_project['project']['name']).'</a></li>';
							$tab_divs .= '<div id="tab-'.$project_id.'"  class="interface-project">'.cms_nodegoat_public_interfaces::listProjectTypesScenarios($arr_public_interface, $arr_project).'</div>';
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
								<li><a href="#">'.getLabel('lbl_type').' '.getLabel('lbl_settings').'</a></li>
								<li><a href="#">'.getLabel('lbl_object').' '.getLabel('lbl_settings').'</a></li>
								<li><a href="#">'.getLabel('lbl_label').' '.getLabel('lbl_settings').'</a></li>
								<li><a href="#">'.getLabel('lbl_cite_as').'</a></li>
								<li><a href="#">PDF</a></li>
								<li><a href="#">'.getLabel('lbl_technical').'</a></li>
							</ul>
							<div>
								<div class="options fieldsets"><div>';

								$arr_public_interface_type_settings = cms_nodegoat_public_interfaces::getPublicInterfaceTypeSettings();
								
								foreach ($arr_public_interface_type_settings as $value) {
									$return .= '<fieldset><legend>'.$value['name'].'</legend>
											<ul class="sorter">'.cms_nodegoat_public_interfaces::getSettingsTypesSorter($arr_public_interface, $value['id']).'</ul>
									</fieldset>';					
								}
															
								$return .= '</div></div>
							</div>
							<div>
								<div class="options fieldsets"><div>';

								$return .= '<fieldset><legend>'.getLabel('lbl_object').' '.getLabel('lbl_settings').'</legend><ul>
											<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_device').' '.getLabel('lbl_location').'</label><input name="settings[show_device_location]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_device_location'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_objects').' '.getLabel('lbl_list').'</label><input name="settings[show_objects_list]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_objects_list'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_url').'</label><input name="settings[share_object_url]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['share_object_url'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_share').' '.getLabel('lbl_url').'</label><input name="settings[show_object_url]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['show_object_url'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_print').' '.getLabel('lbl_object').'</label><input name="settings[print_object]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['print_object'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_selection').'</label><input name="settings[selection]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['selection'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_references').'</label><input name="settings[object_references]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['object_references'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_public_interface_use_combined_references_as_filters').'</label><input name="settings[use_combined_references_as_filters]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['use_combined_references_as_filters'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_geo_visualisation').'</label><input name="settings[object_geo]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['object_geo'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_soc_visualisation').'</label><input name="settings[object_soc]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['object_soc'] ? 'checked="checked"' : '').' /></li>
											<li><label>'.getLabel('lbl_object').' '.getLabel('lbl_time_visualisation').'</label><input name="settings[object_time]" type="checkbox" value="1" '.($arr_public_interface['interface']['settings']['object_time'] ? 'checked="checked"' : '').' /></li>
										</ul></fieldset>';								
												
								$return .= '</div></div>
							</div>
							<div>
								<div class="options fieldsets"><div>';
		
								$return .= '<fieldset><legend>'.getLabel('lbl_default').' '.getLabel('lbl_language').'</legend><ul>
											<li><select name="settings[default_language]">'.cms_general::createDropdown((array)cms_language::getLanguage(), $arr_public_interface['interface']['settings']['default_language'], true, 'label', 'lang_code').'</select></li>
										</ul></fieldset>';		
															
								$return .= '<fieldset><legend>'.getLabel('lbl_public_interface_custom_labels').'</legend><ul>
									<li><label>'.getLabel('lbl_object').'</label><input name="settings[labels][object]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['object'].'" /></li>
									<li><label>'.getLabel('lbl_objects').'</label><input name="settings[labels][objects]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['objects'].'" /></li>
									<li><label>'.getLabel('lbl_list').' '.getLabel('lbl_relation').'s</label><input name="settings[labels][list_relations]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['list_relations'].'" /></li>
									<li><label>'.getLabel('lbl_explore').' '.getLabel('lbl_geo_visualisation').'</label><input name="settings[labels][explore_geo]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['explore_geo'].'" /></li>
									<li><label>'.getLabel('lbl_explore').' '.getLabel('lbl_soc_visualisation').'</label><input name="settings[labels][explore_soc]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['explore_soc'].'" /></li>
									<li><label>'.getLabel('lbl_explore').' '.getLabel('lbl_time_visualisation').'</label><input name="settings[labels][explore_time]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['explore_time'].'" /></li>
									<li><label>'.getLabel('lbl_information').'</label><input name="settings[labels][info]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['info'].'" /></li>';

									foreach ((array)$arr_types as $type_id => $value) {
										$return .= '<li><label>'.Labels::parseTextVariables($value['name']).'</label><input name="settings[labels][type]['.$type_id.'][singular]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['type'][$type_id]['singular'].'" title="Singular"/><input name="settings[labels][type]['.$type_id.'][plural]" type="text" value="'.$arr_public_interface['interface']['settings']['labels']['type'][$type_id]['plural'].'" title="Plural"/><input name="settings[icons][type]['.$type_id.']" type="text" value="'.$arr_public_interface['interface']['settings']['icons']['type'][$type_id].'" title="Icon"/></li>';
									}
																	
								$return .= '</ul></fieldset>';
																		
								$return .= '</div></div>
							</div>
							<div>
								<div class="options fieldsets"><div>';
								
								$return .= '<fieldset><ul>
												<li>
													<label></label>
													<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
												</li>
												<li>
													<label>'.getLabel('lbl_cite_as').'</label>
													<div>';
														$arr_sorter = [];
														foreach (($arr_public_interface['interface']['settings']['cite_as'] ?: [false => []]) as $type_id => $arr_value) {
															$arr_sorter[] = ['value' => [
																'<select name="cite_as_type_id">'.cms_general::createDropdown($arr_types, $type_id, true, 'name').'</select>',
																'<div id="y:public_interfaces:create_cite_as-0">'.cms_nodegoat_public_interfaces::createCiteAsSettings($type_id, $arr_value).'</div>',
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
		
								$return .= '<fieldset><legend>PDF</legend><ul>
											<li><label>'.getLabel('lbl_title').'</label><input name="settings[pdf][title]" type="text" value="'.$arr_public_interface['interface']['settings']['pdf']['title'].'" /></li>
											<li><label>'.getLabel('lbl_subtitle').'</label><input name="settings[pdf][subtitle]" type="text" value="'.$arr_public_interface['interface']['settings']['pdf']['subtitle'].'" /></li>
											<li><label>'.getLabel('lbl_public_interface_colophon').'</label><textarea name="settings[pdf][colofon]">'.$arr_public_interface['interface']['settings']['pdf']['colofon'].'</textarea></li>
											<li><label>'.getLabel('lbl_public_interface_license').'</label><textarea name="settings[pdf][license]">'.$arr_public_interface['interface']['settings']['pdf']['license'].'</textarea></li>
										</ul></fieldset>';		
																		
								$return .= '</div></div>
							</div>
							<div>
								<div class="options fieldsets"><div>';

								$return .= '<fieldset><legend>'.getLabel('lbl_url').' '.getLabel('lbl_server_host').'</legend><ul>
											<li><input name="settings[short_url_host]" type="text" value="'.$arr_public_interface['interface']['settings']['short_url_host'].'" /></li>
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
							<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
						</li>
						<li>
							<label>'.getLabel('lbl_texts').'</label>
							<div>';
								$arr_sorter = [];
								foreach (($arr_public_interface['texts'] ?: [[], []]) as $value) {
									$arr_sorter[] = ['value' => [
										'<input type="text" name="text_name[]" value="'.htmlspecialchars($value['text_name']).'" />',
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
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_public_interface').'" /><input type="submit" name="discard" value="'.getLabel("lbl_cancel").'" />
		</menu>';
		
		$this->validate = ['name' => 'required', 'user_id' => 'required'];
		
		return $return;
	}
	

	public static function css() {
	
		$return = '.public_interfaces textarea, .public_interfaces .body-content  { min-width: 600px !important;  min-height: 400px !important; } 
					.public_interfaces form fieldset legend span { font-weight: normal; }
					.public_interfaces .interface-project ul.sorter li fieldset > ul > li > div > label,
					.public_interfaces .interface-project ul.sorter li fieldset > ul > li > div > .split { margin-left: 15px; }
					.public_interfaces .interface-project ul.sorter li fieldset > ul > li > div > label:first-child { margin-left: 0px; }';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.public_interfaces', function(elm_scripter) {
		
			elm_scripter.on('click', '.edit', function() {
				$(this).quickCommand(elm_scripter.find('form'), {html: 'replace'});
			}).on('click', 'form .add', function() {
				$(this).closest('ul').find('.sorter').first().sorter('addRow');
			}).on('click', 'form .del', function() {
				$(this).closest('ul').find('.sorter').first().sorter('clean');
			}).on('change', '[name*=_is_filter]', function() {
				if($(this).is(':checked')){
					$(this).nextAll('input').removeAttr('checked').attr('disabled', true);
				} else {
					$(this).nextAll('input').removeAttr('disabled');
				}
			}).on('change', '.select_type', function() {
				var val = $(this).val();
				var target = $(this).parent().next('li').find('select');
				target.data('value', {'type_id': $(this).val()}).quickCommand(target);
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
				
		if ($method == "create_cite_as") {

			$this->html = cms_nodegoat_public_interfaces::createCiteAsSettings($value);
		}
		
		if ($method == "data") {
			
			$arr_sql_columns = ['nodegoat_pi.name'];
			$arr_sql_columns_search = ['nodegoat_pi.name'];
			$arr_sql_columns_as = ['nodegoat_pi.id', 'nodegoat_pi.name'];
			
			$sql_table = DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." nodegoat_pi";
			
			$sql_index = 'nodegoat_pi.id';
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$t_row = [];
				$t_row['id'] = 'x:public_interfaces:public_interface_id-'.$arr_row['id'];
				$t_row[] = Labels::parseTextVariables($arr_row['name']);
				$t_row[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data del msg del" value="del" />';
				$output['data'][] = $t_row;
			}
			
			$this->data = $output;
		}
	
		// QUERY
		
		if (($method == "insert" || $method == "update") && $_POST['discard']) {
			
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
