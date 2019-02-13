<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_model extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Model';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'general' => [],
		'data_filter' => [],
		'data_view' => []
	];
	
	private $arr_object_descriptions_numbers = [];
	private $arr_object_analyses = [];
	private $show_user_settings = false;
	private $show_api_settings = false;
	
	function __construct() {
		
		parent::__construct();
		
		$arr_users_link = pages::getClosestMod('register_by_user');
		$this->show_user_settings = ($arr_users_link && pages::filterClearance([$arr_users_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]));
		
		$arr_api_link = pages::getClosestMod('api_configuration');
		$this->show_api_settings = ($arr_api_link && pages::filterClearance([$arr_api_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]));
	}
		
	public function contents() {
		
		if (Settings::get('domain_administrator_mode')) {
				
			$arr_projects = custom_projects::getUserProjects();
			foreach ($arr_projects as $project_id => &$arr_project) {
				
				$arr_project = ['id' => $project_id, 'name' => $arr_project['project']['name']];
			}
			unset($arr_project);
			
			$arr_labels = StoreType::getTypesLabels();
			foreach ($arr_labels as &$arr_label) {
				
				$arr_label['id'] = $arr_label['label'];
				
				if (!$arr_label['label']) {
					$arr_label['label'] = getLabel('lbl_none');
					$arr_label['id'] = 'null';
				}
			}
			unset($arr_label);
		
			$return = '<form class="options" id="y:data_model:set_filter-0">
				<label>'.getLabel('lbl_model_filter_project').':</label><select name="project_id">'.Labels::parseTextVariables(cms_general::createDropdown($arr_projects, false, true)).'</select>
				<label>'.getLabel('lbl_model_filter_label').':</label><select name="label">'.Labels::parseTextVariables(cms_general::createDropdown($arr_labels, false, true, 'label')).'</select>
			</form>';
		}
	
		$return .= '<div class="dynamic"></div>';
					
		$return .= '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_types').'</a></li>
				<li><a href="#">'.getLabel('lbl_classifications').'</a></li>
				<li><a href="#">'.getLabel('lbl_reversals').'</a></li>
			</ul>
			<div>
				
				'.$this->createAddType('is_type').'
				
				<table class="display" id="d:data_model:data-0">
					<thead>
						<tr>			
							<th class="max" data-sort="asc-0">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_definitions').'</th>
							<th>'.getLabel('lbl_object_descriptions').'</th>
							<th>'.getLabel('lbl_object_subs').'</th>
							<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="5" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div>
			
				'.$this->createAddType('is_classification').'
				
				<table class="display" id="d:data_model:data-classifications">
					<thead>
						<tr>			
							<th class="max" data-sort="asc-0">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_definitions').'</th>
							<th>'.getLabel('lbl_category_descriptions').'</th>
							<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="4" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div>
			
				'.$this->createAddType('is_reversal').'
				
				<table class="display" id="d:data_model:data-reversals">
					<thead>
						<tr>			
							<th class="max" data-sort="asc-0">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_definitions').'</th>
							<th>'.getLabel('lbl_referencing').'</th>
							<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="4" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>';
		
		return $return;
	}
	
	private function createAddType($what) {
		
		if ($what == 'is_classification') {
			$str_what = getLabel('lbl_classification');
		} else if ($what == 'is_reversal') {
			$str_what = getLabel('lbl_reversal');
		} else {
			$str_what = getLabel('lbl_type');
		}
	
		$return .= '<form id="f:data_model:add-'.$what.'" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.$str_what.'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	private function createType($type_id = false) {
	
		if (is_numeric($type_id)) {
		
			//$this->checkValidUserId($type_id);
		
			$arr_type_set = StoreType::getTypeSet($type_id);
		}
		if ($type_id === 'is_classification' || $arr_type_set['type']['is_classification']) {
			$is_classification = true;
			$str_what = getLabel('lbl_classification');
		} else if ($type_id === 'is_reversal' || $arr_type_set['type']['is_reversal']) {
			$is_reversal = true;
			$str_what = getLabel('lbl_reversal');
		} else {
			$is_type = true;
			$str_what = getLabel('lbl_type');
		}
		$type_id = (int)$type_id;
		
		$arr_types = ['types' => [], 'classifications' => [], 'reversals' => []];
		foreach (StoreType::getTypes() as $key => $value) {
			$arr_types[($value['is_reversal'] ? 'reversals' : ($value['is_classification'] ? 'classifications' : 'types'))][$key] = $value;
		}
		
		$arr_external_resources = ExternalResource::getReferenceTypes();
		foreach (data_linked_data::getLinkedDataResources() as $id => $arr_resource) {
			$arr_external_resources[] = ['id' => $id, 'name' => $arr_resource['name']];
		};
		
		if (Settings::get('domain_administrator_mode') && $type_id) {
			
			$arr_type_set['type']['name'] = $arr_type_set['type']['name_raw'];
		}
		
		$return = '<h1>'.($type_id ? $str_what.': '.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])) : $str_what).'</h1>
		
		<div class="definition options">
			
			<fieldset>
				<input type="hidden" name="is_classification" value="'.(int)$is_classification.'" />
				<input type="hidden" name="is_reversal" value="'.(int)$is_reversal.'" />
				<ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<input type="text" name="name" value="'.htmlspecialchars($arr_type_set['type']['name']).'" />
					</li>
					'.(Settings::get('domain_administrator_mode') ? '<li>
						<label>'.getLabel('lbl_label').'</label>
						<input type="text" name="label" value="'.htmlspecialchars($arr_type_set['type']['label']).'" title="'.getLabel('inf_administrator_visible').'" />
					</li>' : '').'
					<li>
						<label>'.getLabel('lbl_color').'</label>
						<input name="color" type="text" value="'.$arr_type_set['type']['color'].'" class="colorpicker" />
					</li>
					'.(!$is_reversal ? '<li>
						<label>'.getLabel('lbl_conditions').'</label>
						<select name="condition_id">'.Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeConditions(false, false, $type_id, false, true), $arr_type_set['type']['condition_id'], true, 'label')).'</select>
					</li>' : '').'
					<li>
						<label></label>
						<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
					</li>
					<li>
						<label>'.getLabel('lbl_definitions').'</label>
						<div>';
							$arr_sorter = [];
							foreach (($arr_type_set['definitions'] ?: [[]]) as $arr_definition) {
								$arr_sorter[] = ['value' => [
									'<input type="text" name="definition_name[]" value="'.htmlspecialchars($arr_definition['definition_name']).'" />',
									'<textarea name="definition_text[]">'.$arr_definition['definition_text'].'</textarea><input type="hidden" name="definition_id[]" value="'.$arr_definition['definition_id'].'" />'
								]];
							}
							$return .= cms_general::createSorter($arr_sorter, true);
						$return .= '</div>
					</li>
				</ul>
			</fieldset>

			<div class="tabs">
				<ul>
					'.(($is_type || $is_classification) ? '<li><a href="#">'.getLabel(($is_classification ? 'lbl_category' : 'lbl_object')).'</a></li>' : '').'
					'.($is_reversal ? '<li><a href="#">'.getLabel('lbl_category').'</a></li>' : '').'
					'.($is_type ? '<li><a href="#">'.getLabel('lbl_object_sub').'</a></li>' : '').'
				</ul>';
				
				if ($is_type || $is_classification) {
					
					$arr_selector = [
						['id' => 'use_object_name', 'name' => getLabel('lbl_use_object_name')],
						['id' => 'object_name_in_overview', 'name' => getLabel('lbl_object_name_in_overview')]
					];
									
					$arr_selected = [
						(!$type_id || $arr_type_set['type']['use_object_name'] ? 'use_object_name' : false),
						(!$type_id || $arr_type_set['type']['object_name_in_overview'] ? 'object_name_in_overview' : false)
					];

					$return .= '<div>
						<div class="options">
							<fieldset><ul>
								<li>
									<label>'.getLabel('lbl_name').'</label>
									<span>'.cms_general::createSelector($arr_selector, '', $arr_selected).'</span>
								</li>
								<li>
									<label></label>
									<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
								</li>
								<li><label>'.getLabel('lbl_descriptions').'</label>
									<div class="object-descriptions">';
										
										$arr_value_types = StoreType::getValueTypesBase();
										unset($arr_value_types['object_description']);
										
										$arr_sorter = [];
										
										$arr_object_descriptions = $arr_type_set['object_descriptions'];
										if (!$arr_object_descriptions) {
											$arr_object_descriptions[] = [];
										}
										array_unshift($arr_object_descriptions, []); // Empty run for sorter source
										
										foreach ($arr_object_descriptions as $key => $arr_object_description) {
											
											$unique = uniqid('array_');
											
											$has_default_value = (bool)$arr_object_description['object_description_value_type_options']['default']['value'];
											
											$arr_sorter[] = ['source' => ($key == 0 ? true : false), 'value' => [
												'<input type="text" name="object_descriptions['.$unique.'][object_description_name]" value="'.htmlspecialchars($arr_object_description['object_description_name']).'" />'
												.'<select name="object_descriptions['.$unique.'][object_description_value_type_base]" id="y:data_model:selector_value_type-0">'.cms_general::createDropdown($arr_value_types, $arr_object_description['object_description_value_type_base']).'</select>'
												.'<select name="object_descriptions['.$unique.'][object_description_ref_type_id][type]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['types'], $arr_object_description['object_description_ref_type_id'])).'</select>'
												.'<select name="object_descriptions['.$unique.'][object_description_ref_type_id][classification]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['classifications'], $arr_object_description['object_description_ref_type_id'])).'</select>'
												.'<select name="object_descriptions['.$unique.'][object_description_ref_type_id][reversal]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['reversals'], $arr_object_description['object_description_ref_type_id'])).'</select>'
												.'<input type="hidden" name="object_descriptions['.$unique.'][object_description_id]" value="'.$arr_object_description['object_description_id'].'" />'
												,'<fieldset><ul>
													<li class="description-options">'
														.'<div><label><span class="input">'.getLabel('lbl_source').'</span></label><select name="object_descriptions['.$unique.'][object_description_value_type_options][external][id]">'.cms_general::createDropdown($arr_external_resources, ($arr_object_description['object_description_value_type_base'] == 'external' && $arr_object_description['object_description_value_type_options'] ? $arr_object_description['object_description_value_type_options']['id'] : '')).'</select></div>'
														.'<div><label><input type="checkbox" name=object_descriptions['.$unique.'][object_description_value_type_options][text_tags][marginalia]" value="1" title="'.getLabel('lbl_marginalia').'"'.($arr_object_description['object_description_value_type_base'] == 'text_tags' && $arr_object_description['object_description_value_type_options']['marginalia'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_marginalia').'</span></label></div>'
													.'</li><li>'
														.'<div>'
															.'<label title="'.getLabel('inf_object_description_has_multi').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_has_multi]" value="1"'.($arr_object_description['object_description_has_multi'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_multiple').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_is_required').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_is_required]" value="1"'.($arr_object_description['object_description_is_required'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_required').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_has_default_value').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_has_default_value]" value="1"'.($has_default_value ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_default').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_is_unique').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_is_unique]" value="1"'.($arr_object_description['object_description_is_unique'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_unique').'</span></label>'
															.'<span class="split"></span>'
															.'<label title="'.getLabel('inf_object_description_in_name').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_in_name]" value="1"'.($arr_object_description['object_description_in_name'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_name').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_in_search').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_in_search]" value="1"'.($arr_object_description['object_description_in_search'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_quick_search').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_in_overview').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_in_overview]" value="1"'.(!$arr_object_description || $arr_object_description['object_description_in_overview'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_overview').'</span></label>'
															.($this->show_api_settings ?
																'<label title="'.getLabel('inf_object_description_is_identifier').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_is_identifier]" value="1"'.($arr_object_description['object_description_is_identifier'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_identifier').'</span></label>'
															: '')
														.'</div>'
													.'</li><li class="default-value" title="'.getLabel('inf_object_description_default_value').'">'
														.$this->createTypeObjectDefaultValue('object_descriptions['.$unique.'][object_description_value_type_options_default_value]', $arr_object_description['object_description_value_type_base'], $arr_object_description['object_description_ref_type_id'], $arr_object_description['object_description_has_multi'], $arr_object_description['object_description_value_type_options'])
													.($this->show_user_settings ? 
														'</li><li>'
															.'<select name="object_descriptions['.$unique.'][object_description_clearance_edit]" title="'.getLabel('inf_object_description_clearance_edit').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_description['object_description_clearance_edit'], true, 'label').'</select>'
															.'<select name="object_descriptions['.$unique.'][object_description_clearance_view]" title="'.getLabel('inf_object_description_clearance_view').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_description['object_description_clearance_view'], true, 'label').'</select>'
													: '')
													.'</li>
												</ul></fieldset>'
											]];
										}
										$return .= cms_general::createSorter($arr_sorter, true);
										
									$return .= '</div>
								</li>
							</ul></fieldset>
						</div>
					</div>';
				}
				
				if ($is_reversal) {
					
					$ref_type_id_object_description_id = $arr_type_set['object_description_ids']['rc_ref_type_id'];
					$arr_object_description = $arr_type_set['object_descriptions'][$ref_type_id_object_description_id];
					
					if ($arr_object_description['object_description_ref_type_id'] || !$type_id) {
						$reference = ($arr_types['types'][$arr_object_description['object_description_ref_type_id']] ? 'type' : 'classification');
					} else {
						$reference = '0';
					}
					
					$return .= '<div>
						<div class="options">
							<fieldset><ul>
								<li>
									<label>'.getLabel('lbl_mode').'</label>
									<span>'.cms_general::createSelectorRadio([['id' => 0, 'name' => getLabel('lbl_reversed_classification')], ['id' => 1, 'name' => getLabel('lbl_reversed_collection')]], 'reversal_mode', ($arr_type_set['type']['mode'] ?: 0)).'</span>
								</li>
								<li>
									<label>'.getLabel('lbl_reversed_classification_reference').'</label>
									<span>'.cms_general::createSelectorRadio([['id' => '0', 'name' => getLabel('lbl_none')], ['id' => 'type', 'name' => getLabel('lbl_type')], ['id' => 'classification', 'name' => getLabel('lbl_classification')]], 'reversal_ref', $reference).'</span>
								</li>
								<li>
									<label></label>
									<span>'
										.'<select name="reversal_ref_type_id[type]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['types'], $arr_object_description['object_description_ref_type_id'])).'</select>'
										.'<select name="reversal_ref_type_id[classification]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['classifications'], $arr_object_description['object_description_ref_type_id'])).'</select>
									</span>
								</li>
							</ul></fieldset>
						</div>
					</div>';
				}
				
				if ($is_type) {
					$return .= '<div>
						<div class="options">
						
							<fieldset><ul>
								<li>
									<label></label>
									<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
								</li>
								<li><label>'.getLabel('lbl_object_subs').'</label>
									<div class="object-sub-details">';
										$arr_sorter = [];
										$arr_sorter[] = ['source' => true, 'value' => $this->createTypeObjectSubDetails($type_id)];
										
										foreach (($arr_type_set['object_sub_details'] ?: [[]]) as $arr_object_sub) {
											$arr_sorter[] = ['value' => $this->createTypeObjectSubDetails($type_id, ($arr_object_sub['object_sub_details'] ?: []), ($arr_object_sub['object_sub_descriptions'] ?: []))];
										}
										
										$return .= cms_general::createSorter($arr_sorter, false);
									$return .= '</div>
								</li>
							</ul></fieldset>
							
						</div>
					</div>';
				}
				
			$return .= '</div>
		</div>
	
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel((($is_reversal ? 'lbl_reversal' : ($is_classification ? 'lbl_classification' : 'lbl_type')))).'" /><input type="submit" name="discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
			
		$this->validate = ['name' => 'required'];
		
		return $return;
	}

	private function createTypeObjectSubDetails($type_id = false, $arr_object_sub_details = [], $arr_object_sub_descriptions = []) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
	
		$unique = uniqid('array_');
		
		$object_sub_details_date_type = ($arr_object_sub_details['object_sub_details_id'] && !$arr_object_sub_details['object_sub_details_has_date'] ? 'none' : ($arr_object_sub_details['object_sub_details_is_date_range'] ? 'period' : 'date'));
		$object_sub_details_location_type = ($arr_object_sub_details['object_sub_details_id'] && !$arr_object_sub_details['object_sub_details_has_location'] ? 'none' : 'default');
		
		$date_usage = ($arr_object_sub_details['object_sub_details_date_use_object_sub_details_id'] ? 'object_sub_details' : ($arr_object_sub_details['object_sub_details_date_use_object_sub_description_id'] ? 'object_sub_description' : ($arr_object_sub_details['object_sub_details_date_use_object_description_id'] ? 'object_description' : '')));
		$location_usage = ($arr_object_sub_details['object_sub_details_location_use_object_id'] ? 'object' : ($arr_object_sub_details['object_sub_details_location_use_object_description_id'] ? 'object_description' : ($arr_object_sub_details['object_sub_details_location_use_object_sub_details_id'] ? 'object_sub_details' : ($arr_object_sub_details['object_sub_details_location_use_object_sub_description_id'] ? 'object_sub_description' : ''))));
		$arr_date_object_sub_details = $arr_location_object_sub_details = [];
		foreach (StoreType::getTypeObjectSubsDetails($type_id) as $object_sub_details_id => $value) {
			if ($value['object_sub_details_location_ref_type_id'] == $arr_object_sub_details['object_sub_details_location_ref_type_id'] && $value['object_sub_details_location_ref_type_id_locked'] && $value['object_sub_details_is_unique'] && $object_sub_details_id != $arr_object_sub_details['object_sub_details_id']) {
				$arr_location_object_sub_details[] = $value;
			}
			if ($object_sub_details_id != $arr_object_sub_details['object_sub_details_id'] && (int)$value['object_sub_details_is_date_range'] == (int)$arr_object_sub_details['object_sub_details_is_date_range'] && $value['object_sub_details_is_unique']) {
				$arr_date_object_sub_details[] = $value;
			}
		}
		$arr_date_object_sub_descriptions = $arr_location_object_sub_descriptions = [];
		foreach ($arr_object_sub_descriptions as $object_sub_description_id => $value) {
			if ($value['object_sub_description_ref_type_id'] == $arr_object_sub_details['object_sub_details_location_ref_type_id'] && !$value['object_sub_description_use_object_description_id']) {
				$arr_location_object_sub_descriptions[] = $value;
			}
			if ($value['object_sub_description_value_type_base'] == 'date' && !$arr_object_sub_details['object_sub_details_is_date_range']) {
				$arr_date_object_sub_descriptions[] = $value;
			}
		}
		$arr_date_object_descriptions = $arr_location_object_descriptions = [];
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $value) {
			if ($value['object_description_ref_type_id'] == $arr_object_sub_details['object_sub_details_location_ref_type_id']) {
				$arr_location_object_descriptions[] = $value;
			}
			if ($value['object_description_value_type_base'] == 'date' && !$arr_object_sub_details['object_sub_details_is_date_range']) {
				$arr_date_object_descriptions[] = $value;
			}
		}
		
		$arr_types = ['types' => [], 'classifications' => [], 'reversals' => []];
		$arr_sub_types = [];
		foreach (StoreType::getTypes() as $key => $value) {
			$arr_types[($value['is_reversal'] ? 'reversals' : ($value['is_classification'] ? 'classifications' : 'types'))][$key] = $value;
			if (!$value['is_classification']) {
				$arr_sub_types[$key] = $value;
			}
		}
		
		$arr_external_resources = ExternalResource::getReferenceTypes();
		foreach (data_linked_data::getLinkedDataResources() as $id => $arr_resource) {
			$arr_external_resources[] = ['id' => $id, 'name' => $arr_resource['name']];
		};

		$return = '<fieldset>
			<legend><span class="handle"><span class="icon">'.getIcon('updown').'</span></span><span>'.getLabel('lbl_object_sub').'</span></legend>
			<ul>
				<li>
					<label>'.getLabel('lbl_name').'</label><input type="text" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_name]" value="'.htmlspecialchars($arr_object_sub_details['object_sub_details_name']).'" />'.($arr_object_sub_details['object_sub_details_id'] ? '<input type="hidden" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_id]" value="'.$arr_object_sub_details['object_sub_details_id'].'" />' : '').'
				</li>
				<li>
					<label>'.getLabel('lbl_options').'</label>
					<span>'
						.'<label><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_is_unique]" value="1"'.($arr_object_sub_details['object_sub_details_is_unique'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_unique').'</span></label>'
						.'<label><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_is_required]" value="1"'.($arr_object_sub_details['object_sub_details_is_required'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_required').'</span></label>'
					.'</span>
				</li>'
				.($this->show_user_settings ?
					'<li>
						<label></label>
						<span>'
							.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_clearance_edit]" title="'.getLabel('inf_object_sub_details_clearance_edit').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_details['object_sub_details_clearance_edit'], true, 'label').'</select>'
							.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_clearance_view]" title="'.getLabel('inf_object_sub_details_clearance_view').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_details['object_sub_details_clearance_view'], true, 'label').'</select>'
						.'</span>
					</li>'
				: '')
			.'</ul>
			
			<div class="tabs">
				<ul>
					<li><a href="#">'.getLabel('lbl_date').'</a></li>
					<li><a href="#">'.getLabel('lbl_location').'</a></li>
					<li><a href="#">'.getLabel('lbl_descriptions').'</a></li>
				</ul>
				
				<div>
					<div class="options">
						
						<fieldset><ul>
							<li>
								<label>'.getLabel('lbl_date').'</label>								
								<div>'
									.cms_general::createSelectorRadio([['id' => 'date', 'name' => getLabel('lbl_date')], ['id' => 'period', 'name' => getLabel('lbl_period')], ['id' => 'none', 'name' => getLabel('lbl_none')]], 'object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_type]', $object_sub_details_date_type)
								.'</div>
							</li><li>
								<label>'.getLabel('lbl_source').'</label>
								<div>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_useage]" title="'.getLabel('inf_date_source').'">'.cms_general::createDropdown(cms_nodegoat_definitions::getReferenceUsage(), $date_usage, true).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_use_object_sub_details_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_sub_details, $arr_object_sub_details['object_sub_details_date_use_object_sub_details_id'], false, 'object_sub_details_name', 'object_sub_details_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_use_object_sub_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_sub_descriptions, $arr_object_sub_details['object_sub_details_date_use_object_sub_description_id'], false, 'object_sub_description_name', 'object_sub_description_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_use_object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_descriptions, $arr_object_sub_details['object_sub_details_date_use_object_description_id'], false, 'object_description_name', 'object_description_id')).'</select>'
								.'</div>
							</li>
						</ul></fieldset>
						
					</div>
				</div>
				
				<div>
					<div class="options">
						
						<fieldset><ul>
							<li>
								<label>'.getLabel('lbl_location').'</label>
								<div>'
									.cms_general::createSelectorRadio([['id' => 'default', 'name' => getLabel('lbl_yes')], ['id' => 'none', 'name' => getLabel('lbl_none')]], 'object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_type]', $object_sub_details_location_type)
								.'</div>
							<li>
								<label>'.getLabel('lbl_reference').'</label><span>'
								.'<input type="checkbox" value="1" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_only]" title="'.getLabel('inf_location_reference_only').'"'.($arr_object_sub_details['object_sub_details_location_ref_only'] ? ' checked="checked"' : '').' />'
									.'<select id="y:data_model:selector_object_sub_details-0" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_sub_types, $arr_object_sub_details['object_sub_details_location_ref_type_id'], true)).'</select>'
									.'<input type="checkbox" value="1" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_type_id_locked]" title="'.getLabel('inf_location_reference_lock').'"'.($arr_object_sub_details['object_sub_details_location_ref_type_id_locked'] ? ' checked="checked"' : '').' />'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_object_sub_details_id]">'.Labels::parseTextVariables(cms_general::createDropdown(StoreType::getTypeObjectSubsDetails($arr_object_sub_details['object_sub_details_location_ref_type_id']), $arr_object_sub_details['object_sub_details_location_ref_object_sub_details_id'], true, 'object_sub_details_name', 'object_sub_details_id')).'</select>'
									.'<input type="checkbox" value="1" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_object_sub_details_id_locked]" title="'.getLabel('inf_location_reference_lock').'"'.($arr_object_sub_details['object_sub_details_location_ref_object_sub_details_id_locked'] ? ' checked="checked"' : '').' />'
								.'</span>
							</li>
							<li>
								<label>'.getLabel('lbl_source').'</label><span>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_useage]" title="'.getLabel('inf_location_source').'">'.cms_general::createDropdown(cms_nodegoat_definitions::getReferenceUsage(), $location_usage, true).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_use_object_sub_details_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_location_object_sub_details, $arr_object_sub_details['object_sub_details_location_use_object_sub_details_id'], false, 'object_sub_details_name', 'object_sub_details_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_use_object_sub_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_location_object_sub_descriptions, $arr_object_sub_details['object_sub_details_location_use_object_sub_description_id'], false, 'object_sub_description_name', 'object_sub_description_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_use_object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_location_object_descriptions, $arr_object_sub_details['object_sub_details_location_use_object_description_id'], false, 'object_description_name', 'object_description_id')).'</select>'
								.'</span>
							</li>
						</ul></fieldset>
								
					</div>
				</div>
				
				<div>
					<div class="options">
						
						<fieldset><ul><legend>'.getLabel('lbl_descriptions').'</legend>
							<li>
								<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
							</li>
							<li>
								<div class="object-sub-descriptions">';
									
									$arr_value_types = StoreType::getValueTypesBase();
									
									if (!$arr_object_sub_descriptions) {
										$arr_object_sub_descriptions[] = [];
									}
									array_unshift($arr_object_sub_descriptions, []); // Empty run for sorter source
								
									foreach ($arr_object_sub_descriptions as $key => $arr_object_sub_description) {
										
										$has_default_value = (bool)$arr_object_sub_description['object_sub_description_value_type_options']['default']['value'];
										
										$unique2 = uniqid('array_');
										
										$arr_sorter[] = ['source' => ($key == 0 ? true : false), 'value' => [
											'<input type="text" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_name]" value="'.htmlspecialchars($arr_object_sub_description['object_sub_description_name']).'" />'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_value_type_base]" id="y:data_model:selector_value_type-0">'.cms_general::createDropdown($arr_value_types, $arr_object_sub_description['object_sub_description_value_type_base']).'</select>'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_ref_type_id][type]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['types'], $arr_object_sub_description['object_sub_description_ref_type_id'])).'</select>'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_ref_type_id][classification]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['classifications'], $arr_object_sub_description['object_sub_description_ref_type_id'])).'</select>'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_ref_type_id][reversal]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types['reversals'], $arr_object_sub_description['object_sub_description_ref_type_id'])).'</select>'
											.'<input type="hidden" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_id]" value="'.$arr_object_sub_description['object_sub_description_id'].'" />'
											,'<fieldset><ul>
												<li class="description-options">'
													.'<div><label><span class="input">'.getLabel('lbl_source').'</span></label><select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_value_type_options][external][id]">'.cms_general::createDropdown($arr_external_resources, ($arr_object_sub_description['object_sub_description_value_type_base'] == 'external' && $arr_object_sub_description['object_sub_description_value_type_options'] ? $arr_object_sub_description['object_sub_description_value_type_options']['id'] : '')).'</select></div>'
													.'<div><label><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_value_type_options][text_tags][marginalia]" value="1" title="'.getLabel('lbl_marginalia').'"'.($arr_object_sub_description['object_sub_description_value_type_base'] == 'text_tags' && $arr_object_sub_description['object_sub_description_value_type_options']['marginalia'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_marginalia').'</span></label></div>'
													.'<div><label><span class="input">'.getLabel('lbl_object_description').'</span></label><select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_use_object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set['object_descriptions'], $arr_object_sub_description['object_sub_description_use_object_description_id'], false, 'object_description_name', 'object_description_id')).'</select></div>'
												.'</li><li>'
													.'<div>'
														.'<label title="'.getLabel('inf_object_description_is_required').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_is_required]" value="1"'.($arr_object_sub_description['object_sub_description_is_required'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_required').'</span></label>'
														.'<label title="'.getLabel('inf_object_description_has_default_value').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_has_default_value]" value="1"'.($has_default_value ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_default').'</span></label>'
														.'<span class="split"></span>'
														.'<label title="'.getLabel('inf_object_description_in_name').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_in_name]" value="1"'.($arr_object_sub_description['object_sub_description_in_name'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_name').'</span></label>'
														.'<label title="'.getLabel('inf_object_description_in_search').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_in_search]" value="1"'.($arr_object_sub_description['object_sub_description_in_search'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_quick_search').'</span></label>'
														.'<label title="'.getLabel('inf_object_description_in_overview').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_in_overview]" value="1"'.(!$arr_object_sub_description || $arr_object_sub_description['object_sub_description_in_overview'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_overview').'</span></label>'
													.'</div>'
												.'</li><li class="default-value" title="'.getLabel('inf_object_description_default_value').'">'
													.$this->createTypeObjectDefaultValue('object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_value_type_options_default_value]', $arr_object_sub_description['object_sub_description_value_type_base'], $arr_object_sub_description['object_sub_description_ref_type_id'], false, $arr_object_sub_description['object_sub_description_value_type_options'])
												.($this->show_user_settings ?
													'</li><li>'
														.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_clearance_edit]" title="'.getLabel('inf_object_description_clearance_edit').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_description['object_sub_description_clearance_edit'], true, 'label').'</select>'
														.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_clearance_view]" title="'.getLabel('inf_object_description_clearance_view').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_description['object_sub_description_clearance_view'], true, 'label').'</select>'
													: '')
												.'</li>
											</ul></fieldset>'
										]];
									}
									$return .= cms_general::createSorter($arr_sorter, true);
									
								$return .= '</div>
							</li>
						</ul></fieldset>
						
					</div>
				</div>
				
			</div>
		
		</fieldset>';
		
		return $return;
	}
	
	private function createTypeObjectDefaultValue($str_name, $value_type, $ref_type_id, $has_multi, $arr_value_type_options = []) {
		
		$arr_values = ($arr_value_type_options['default']['value'] ?: '');
		
		if ($value_type == 'type' || $value_type == 'classification') {
			
			$arr_type_object_names = ($arr_values ? FilterTypeObjects::getTypeObjectNames($ref_type_id, $arr_values) : []);
			
			if ($has_multi) {
				$return_value = '<div class="input">'.cms_general::createMultiSelect($str_name, 'y:data_filter:lookup_type_object-'.$ref_type_id, $arr_type_object_names, false, ['list' => true]).'</div>';
			} else {
				$return_value = '<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$ref_type_id.'" name="'.$str_name.'" value="'.$arr_values.'" /><input type="search" id="y:data_filter:lookup_type_object-'.$ref_type_id.'" class="autocomplete" value="'.$arr_type_object_names[$arr_values].'" />';
			}
		} else {
			
			if ($value_type == 'text' || $value_type == 'text_layout' || $value_type == 'text_tags') {
				$value_type = '';
			}
			
			if ($has_multi) {
				$arr_values = ($arr_values ?: []);
			}
			
			$return_value = StoreTypeObjects::formatToFormValue($value_type, StoreTypeObjects::formatToSQLValue($value_type, $arr_values), $str_name, $arr_value_type_options);
		}
		
		$return = '<div class="entry object-various">
			<fieldset><ul>
				<li>
					<label><span>'.getLabel('lbl_default').'</label>
					<div>'.$return_value.'</div>
				</li>
			</ul></fieldset>
		</div>';
		
		return $return;
	}
	
	public function createTypeCondition($type_id, $arr_condition, $arr_model_conditions) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['object' => true, 'object_sub_details_date' => false, 'object_sub_details_location' => false, 'purpose' => 'filter']);
		
		$arr_type_set_flat_separated = [];
		$arr_type_set_flat_separated['object_name']['id'] = $arr_type_set_flat['id'];
		$arr_type_set_flat_separated['object_values'] = [];
		$arr_type_set_flat_separated['object_nodes_object']['id'] = $arr_type_set_flat['id'];
		$arr_type_set_flat_separated['object_nodes_referencing'] = [];

		$arr_condition_actions_separated['object_name'] = cms_nodegoat_definitions::getSetConditionActions('object_name');
		$arr_condition_actions_separated['object_values'] = cms_nodegoat_definitions::getSetConditionActions('object_values');
		$arr_condition_actions_separated['object_nodes_object'] = cms_nodegoat_definitions::getSetConditionActions('object_nodes');
		$arr_condition_actions_separated['object_nodes_referencing'] = cms_nodegoat_definitions::getSetConditionActions('object_nodes_referencing');
		
		$this->arr_object_analyses = data_analysis::createTypeAnalysesSelection($type_id);
		
		$this->arr_object_descriptions_numbers = [];
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$str_id = 'object_description_'.$object_description_id;

			if ($arr_object_description['object_description_in_name']) {
				
				$arr_type_set_flat_separated['object_name'][$str_id] = $arr_type_set_flat[$str_id];
			}
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
				continue;
			}

			$arr_type_set_flat_separated['object_values'][$str_id] = $arr_type_set_flat[$str_id];
			
			if ($arr_object_description['object_description_ref_type_id']) {
					
				$arr_type_set_flat_separated['object_nodes_referencing'][$str_id] = $arr_type_set_flat[$str_id];
			}
			
			if ($arr_object_description['object_description_value_type'] == 'int') {
				
				$this->arr_object_descriptions_numbers[$str_id] = $arr_type_set_flat[$str_id];
			}
		}
			
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			$has_object_sub_details_clearance = !($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id));
				
			if ($has_object_sub_details_clearance) {
				
				$str_id_object_sub_details = 'object_sub_details_'.$object_sub_details_id.'_id';
				$arr_type_set_flat_separated['object_nodes_object'][$str_id_object_sub_details] = $arr_type_set_flat[$str_id_object_sub_details];
			}
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				$str_id = 'object_sub_description_'.$object_sub_description_id;
				
				if ($arr_object_sub_description['object_sub_description_in_name']) {
				
					$arr_type_set_flat_separated['object_name'][$str_id] = $arr_type_set_flat[$str_id];
				}
				
				if (!$has_object_sub_details_clearance || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
					continue;
				}

				$arr_type_set_flat_separated['object_values'][$str_id] = $arr_type_set_flat[$str_id];
				
				if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
					
					$arr_type_set_flat_separated['object_nodes_referencing'][$str_id] = $arr_type_set_flat[$str_id];
				}
				
				if ($arr_object_sub_description['object_sub_description_value_type'] == 'int') {
					
					$this->arr_object_descriptions_numbers[$str_id] = $arr_type_set_flat[$str_id];
					$this->arr_object_descriptions_numbers[$str_id]['attr']['data-group_identifier'] = $str_id_object_sub_details;
				}
			}
		}
		
		$arr_condition_separated = [];
		
		if ($arr_condition['object']) {
			
			foreach ($arr_condition['object'] as $arr_condition_setting) {
				
				if ($arr_condition_setting['condition_in_object_name']) {
					
					$arr_condition_separated['object_name']['id'][] = $arr_condition_setting;
				} else {
					
					$arr_condition_separated['object_nodes_object']['id'][] = $arr_condition_setting;
				}
			}
		}

		foreach ((array)$arr_condition['object_descriptions'] as $object_description_id => $arr_condition_settings) {
			
			$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
			$has_object_description_clearance = !($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id));
			
			$str_id = 'object_description_'.$object_description_id;
			
			foreach ($arr_condition_settings as $arr_condition_setting) {
				
				if ($arr_condition_setting['condition_in_object_name']) {
					
					$arr_condition_separated['object_name'][$str_id][] = $arr_condition_setting;
				} else {
					
					if (!$has_object_description_clearance) {
						continue;
					}
					
					if ($arr_condition_setting['condition_in_object_nodes_referencing']) {
						
						$arr_condition_separated['object_nodes_referencing'][$str_id][] = $arr_condition_setting;
					} else {
						
						$arr_condition_separated['object_values'][$str_id][] = $arr_condition_setting;
					}
				}
			}
		}
		
		foreach ((array)$arr_condition['object_sub_details'] as $object_sub_details_id => $arr_condition_settings) {
			
			$has_object_sub_details_clearance = !($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id));
			
			if ($has_object_sub_details_clearance) {
					
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
				
				foreach ((array)$arr_condition_settings['object_sub_details'] as $arr_condition_setting) {
					
					$arr_condition_separated['object_nodes_object'][$str_id][] = $arr_condition_setting;
				}
			}
			
			foreach ((array)$arr_condition_settings['object_sub_descriptions'] as $object_sub_description_id => $arr_condition_settings) {
				
				$arr_object_sub_description = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
				$has_object_sub_description_clearance = ($has_object_sub_details_clearance && !($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)));
				
				$str_id = 'object_sub_description_'.$object_sub_description_id;
				
				foreach ($arr_condition_settings as $arr_condition_setting) {
					
					if ($arr_condition_setting['condition_in_object_name']) {
					
						$arr_condition_separated['object_name'][$str_id][] = $arr_condition_setting;
					} else {
						
						if (!$has_object_sub_description_clearance) {
							continue;
						}
						
						if ($arr_condition_setting['condition_in_object_nodes_referencing']) {
							
							$arr_condition_separated['object_nodes_referencing'][$str_id][] = $arr_condition_setting;
						} else {
							
							$arr_condition_separated['object_values'][$str_id][] = $arr_condition_setting;
						}
					}
				}
			}
		}
		
		$arr_sorter = [];
		
		foreach ($arr_type_set_flat_separated as $object_or_object_sub_details => $arr_type_set_flat_separate) {
			
			if (!$arr_condition_separated[$object_or_object_sub_details]) {
				$arr_condition_separated[$object_or_object_sub_details] = [[[]]];
			}
			array_unshift($arr_condition_separated[$object_or_object_sub_details], [[[]]]); // Empty run for sorter source
			$is_source = true;
			
			foreach ($arr_condition_separated[$object_or_object_sub_details] as $id => $arr_condition_settings) {
				foreach ($arr_condition_settings as $arr_condition_setting) {
				
					$unique = uniqid('array_');
					
					$return_actions = '<fieldset><ul>';
					foreach ($arr_condition_actions_separated[$object_or_object_sub_details] as $arr_action) {
						$return_actions .= '<li><label>'.$arr_action['name'].'</label><div>'.$this->createConditionAction($type_id, $arr_action['id'], $arr_condition_setting['condition_actions'][$arr_action['id']], ['name' => 'condition['.$unique.'][condition_actions]']).'</div></li>';
					}
					$return_actions .= '</ul></fieldset>';
					
					Labels::setVariable('application', 'condition');
					
					$arr_sorter[$object_or_object_sub_details][] = ['source' => $is_source, 'value' => [
						'<select name="condition['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set_flat_separate, $id, true)).'</select>'
						.'<input type="hidden" name="condition['.$unique.'][condition_filter]" value="'.htmlspecialchars(json_encode($arr_condition_setting['condition_filter'])).'" />'
						.'<button type="button" id="y:data_filter:configure_application_filter-'.$type_id.'" value="filter" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>'
						.'<input type="hidden" name="condition['.$unique.'][condition_in_'.$object_or_object_sub_details.']" value="1" />'
						.($object_or_object_sub_details == 'object_nodes_object' || $object_or_object_sub_details == 'object_nodes_referencing' ? '<input type="text" name="condition['.$unique.'][condition_label]" title="'.getLabel('inf_condition_label').'" value="'.$arr_condition_setting['condition_label'].'" />' : '')
						,$return_actions
					]];
					
					if ($is_source) {
						$is_source = false;
					}
				}
			}
		}
		
		$return .= '<div class="condition tabs">
			<ul>
				<li class="no-tab"><span>'
					.'<input type="button" id="y:data_model:store_condition-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_conditions_store').'" />'
					.'<input type="button" id="y:data_model:open_condition-'.$type_id.'" class="data add popup" value="open" title="'.getLabel('inf_conditions_open').'" />'
				.'</span></li>
				<li><a href="#">'.getLabel('lbl_condition').': '.Labels::parseTextVariables($arr_type_set['type']['name']).'</a></li>
				<li><a href="#">'.getLabel('lbl_model').'</a></li>
			</ul>
			
			<div class="condition-self">
			
				<div class="tabs">
					<ul>
						<li><a href="#">'.getLabel('lbl_name').'</a></li>
						<li><a href="#">'.getLabel('lbl_descriptions').'</a></li>
						<li><a href="#">'.getLabel('lbl_nodes').'</a></li>
					</ul>
						
					<div class="name">
						<div class="options">
						
							<fieldset><ul>
								<li>
									<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
								</li>
								<li>
									<label>'.getLabel('lbl_condition').'</label>'.cms_general::createSorter($arr_sorter['object_name'], true).'
								</li>
							</ul></fieldset>
							
						</div>
					</div>
					
					<div class="values">
						<div class="options">
						
							<fieldset><ul>
								<li>
									<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
								</li>
								<li>
									<label>'.getLabel('lbl_condition').'</label>'.cms_general::createSorter($arr_sorter['object_values'], true).'
								</li>
							</ul></fieldset>
							
						</div>
					</div>
					
					<div class="identifiers">

						<div class="tabs">
							<ul>
								<li><a href="#">'.getLabel('lbl_object').'</a></li>
								<li><a href="#">'.getLabel('lbl_referencing').'</a></li>
							</ul>
							
							<div>
								<div class="options">
								
									<fieldset><ul>
										<li>
											<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
										</li>
										<li>
											<label>'.getLabel('lbl_condition').'</label>'.cms_general::createSorter($arr_sorter['object_nodes_object'], true).'
										</li>
									</ul></fieldset>
									
								</div>
							</div>
							
							<div>
								<div class="options">
								
									<fieldset><ul>
										<li>
											<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
										</li>
										<li>
											<label>'.getLabel('lbl_condition').'</label>'.cms_general::createSorter($arr_sorter['object_nodes_referencing'], true).'
										</li>
									</ul></fieldset>
									
								</div>
							</div>
						</div>
						
					</div>
					
				</div>
				
			</div>
			
			<div class="condition-model-conditions">
				<div class="options">';
					
					$arr_types = ($arr_project['types'] ? StoreType::getTypes(array_keys($arr_project['types'])) : []);
					$arr_conditions_all = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], false, false, false, $arr_use_project_ids);
					
					$arr_types_classifications = [];
					foreach ($arr_types as $cur_type_id => $arr_type) {
						
						if ($cur_type_id == $type_id || !$arr_conditions_all[$cur_type_id]) {
							continue;
						}
						
						$arr_types_classifications[(($arr_type['is_reversal'] ? 'reversals' : ($arr_type['is_classification'] ? 'classifications' : 'types')))][$cur_type_id] = $arr_type;
					}
					
					foreach ($arr_types_classifications as $type_or_classification => $arr_types) {
							
						$return .= '<fieldset><legend>'.getLabel('lbl_'.$type_or_classification).'</legend><ul>';
							
							foreach ($arr_types as $cur_type_id => $arr_type) {
								
								$return .= '<li>
									<label>'.Labels::parseTextVariables($arr_type['name']).'</label><div>'
										.'<select name="model_conditions['.$cur_type_id.'][condition_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_conditions_all[$cur_type_id], $arr_model_conditions[$cur_type_id]['condition_id'], true, 'label')).'</select>'
										.'<input type="checkbox" value="1" name="model_conditions['.$cur_type_id.'][condition_use_current]" title="'.getLabel('inf_condition_model_conditions_use_current').'"'.($arr_model_conditions[$cur_type_id]['condition_use_current'] ? ' checked="checked"' : '').' />'
									.'</div>
								</li>';
							}
							
						$return .= '</ul></fieldset>';
						
					}
					
				$return .= '</div>
			</div>
		
		</div>';
		
		return $return;
	}
	
	private function createConditionAction($type_id, $action, $arr_selected = [], $arr_options = []) {
		
		$arr_condition_actions = cms_nodegoat_definitions::getSetConditionActions();
		
		$unique = uniqid('array_');
	
		foreach ($arr_condition_actions[$action]['value'] as $value) {
			
			if (is_array($value)) {
				$type = $value['type'];
				$info = $value['info'];
			} else {
				$type = $value;
				$info = false;
			}
			
			$name = ($arr_options['name'] ? $arr_options['name'].'['.$action.']['.$type.']' : $action.'['.$unique.']['.$type.']');
			
			switch ($type) {
				case 'value':
					$return .= '<input type="text" name="'.$name.'" value="'.htmlspecialchars($arr_selected['value']).'"'.($info ? ' title="'.htmlspecialchars($info).'"' : '').' />';
					break;
				case 'number':
					$return .= '<input type="number" name="'.$name.'" value="'.htmlspecialchars($arr_selected['number']).'"'.($info ? ' title="'.htmlspecialchars($info).'"' : '').' />';
					break;
				case 'emphasis':
					$return .= cms_general::createSelector([['id' => 'bold', 'name' => getLabel('lbl_text_bold')], ['id' => 'italic', 'name' => getLabel('lbl_text_italic')], ['id' => 'strikethrough', 'name' => getLabel('lbl_text_strikethrough')]], $name, (array)$arr_selected['emphasis']);
					break;
				case 'color':
					$return .= '<input name="'.$name.'" type="text" value="'.$arr_selected['color'].'" class="colorpicker"'.($info ? ' title="'.htmlspecialchars($info).'"' : '').' />';
					break;
				case 'opacity':
					$return .= '<input type="number" name="'.$name.'" step="0.01" min="0" max="1" value="'.htmlspecialchars($arr_selected['opacity']).'" title="'.getLabel('lbl_opacity').'" />';
					break;
				case 'check':
					$return .= '<input type="checkbox" name="'.$name.'" value="1"'.($arr_selected['check'] ? ' checked="checked"' : '').($info ? ' title="'.htmlspecialchars($info).'"' : '').' />';
					break;
				case 'number_use_object_description_id':
					$return .= '<select name="'.$name.'"'.($info ? ' title="'.htmlspecialchars($info).'"' : '').'>'.Labels::parseTextVariables(cms_general::createDropdown($this->arr_object_descriptions_numbers, $arr_selected['number_use_object_description_id'], true)).'</select>';
					break;
				case 'number_use_object_analysis_id':
					$return .= '<select name="'.$name.'"'.($info ? ' title="'.htmlspecialchars($info).'"' : '').'>'.Labels::parseTextVariables(cms_general::createDropdown($this->arr_object_analyses, $arr_selected['number_use_object_analysis_id'], true, 'label')).'</select>';
					break;
				case 'image':
					$has_file = ($arr_selected['image'] && isPath(DIR_HOME_CUSTOM_PROJECT_WORKSPACE.$arr_selected['image']));
					
					if ($has_file) {
						$return = '<input type="hidden" name="'.$name.'[url]" value="'.htmlspecialchars($arr_selected['image']).'" />'
						.'<img src="/'.htmlspecialchars(DIR_CUSTOM_PROJECT_WORKSPACE.$arr_selected['image']).'" />'
						.'<input type="button" class="data del" value="del" />';
					}
					
					$return .= '<div class="hide-edit hide">'
						.cms_general::createFileBrowser(false, $name.'[file]')
					.'</div>'
					.'<input type="button" class="data neutral'.($has_file ? ' hide' : '').'" value="file" />';
					
					break;
			}
		}

		return $return;
	}
	
	private function createSelectCondition($type_id, $store = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<fieldset><legend>'.getLabel(($store ? 'lbl_save' : 'lbl_select')).'</legend>
			<ul>
				<li><label>'.getLabel('lbl_conditions').'</label><span id="x:custom_projects:condition_storage-'.(int)$type_id.'">'
					.'<select name="condition_id">'.Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids), false, true, 'label')).'</select>'
					.($store ?
						'<input type="button" class="data add popup add_condition_storage" value="store" />'
						.'<input type="button" class="data del msg del_condition_storage" value="del" />'
					: '')
				.'</span></li>
			</ul>
		</fieldset>';

		return $return;
	}
	
	public static function createTypeNetwork($from_type_id, $to_type_id, $steps, $arr_options = []) {
		
		// $arr_options = array('references' => 'referencing/referenced/both', 'descriptions' => bool/'pick'/'combine', 'functions' => ['filter' => bool, 'collapse' => bool], 'network' => ['dynamic' => bool, 'object_sub_locations' => bool], 'name' => string, 'source_path' => string);
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_options['name'] = ($arr_options['name'] ?: 'type_network');
		$arr_options['references'] = ($arr_options['references'] ?: 'both');
		
		$arr_types = StoreType::getTypes();
		$arr_ref_type_ids = array_keys($arr_types);
		
		$trace = new TraceTypeNetwork(array_keys($arr_project['types']), $arr_options['network']['dynamic'], $arr_options['network']['object_sub_locations']);
		$trace->run($from_type_id, $to_type_id, ($steps === false ? 1 : $steps), $arr_options['references']);

		$arr_type_network_paths = $trace->getTypeNetworkPaths();

		$func_connection = function($arr_network_connections, $type_id, $source_path) use (&$func_connection, $arr_project, $trace, $steps, $arr_ref_type_ids, $arr_options) {
							
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			if ($arr_options['descriptions'] == 'pick') {
				$arr_type_set_flat = StoreType::getTypeSetFlat($type_id);
			} else if ($arr_options['descriptions'] == 'combine') {
				$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['object_sub_details_date' => false, 'object_sub_details_location' => false]);
			}
			
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$str_id = 'object_description_'.$object_description_id;
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
					
					unset($arr_type_set['object_descriptions'][$object_description_id], $arr_type_set_flat[$str_id]);
				} else {
					
					if ($arr_options['descriptions'] == 'pick') {
						
						if ($arr_object_description['object_description_ref_type_id'] || $arr_object_description['object_description_value_type'] == 'text_tags') {
							arrInsert($arr_type_set_flat, $str_id, [$str_id.'_id' => ['id' => $str_id.'_id', 'name' => $arr_type_set_flat[$str_id]['name'].' ID']]);
						}
					}
				}
			}
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_';
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
					
					unset($arr_type_set['object_sub_details'][$object_sub_details_id]);

					foreach ($arr_type_set_flat as $key => $value) {
						
						if (strpos($key, $str_id) !== false) {
							unset($arr_type_set_flat[$key]);
						}
					}
				} else {
					
					$arr_type_set_flat[$str_id.'id']['name'] = $arr_type_set_flat[$str_id.'id']['name'].' ID';
					
					if ($arr_options['descriptions'] == 'pick') {
						
						$str_id = $str_id.'location_ref_type_id';
						
						if ($arr_type_set_flat[$str_id]) {
							arrInsert($arr_type_set_flat, $str_id, [$str_id.'_id' => ['id' => $str_id.'_id', 'name' => $arr_type_set_flat[$str_id]['name'].' ID']]);
						}
					}
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$str_id = 'object_sub_description_'.$object_sub_description_id;
					
					if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {

						unset($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id], $arr_type_set_flat[$str_id]);
					} else {
						
						if ($arr_options['descriptions'] == 'pick') {
							
							if ($arr_object_sub_description['object_sub_description_ref_type_id'] || $arr_object_sub_description['object_sub_description_value_type'] == 'text_tags') {
								arrInsert($arr_type_set_flat, $str_id, [$str_id.'_id' => ['id' => $str_id.'_id', 'name' => $arr_type_set_flat[$str_id]['name'].' ID']]);
							}
						}
					}
				}
			}
			
			// Network
			
			$arr_type_set_dynamic_referencing = [];
			$arr_return_paths = [];
			
			$arr_in_out_type_object_connections = [];
			$arr_in_out_type_object_connections['in'] = (array)$arr_network_connections['in'];
			$arr_in_out_type_object_connections['out'] = (array)$arr_network_connections['out'];
			
			foreach ($arr_in_out_type_object_connections as $in_out => $arr_type_object_connections) {
				
				foreach ($arr_type_object_connections as $ref_type_id => $arr_object_connections) {
					
					$use_type_id = ($in_out == 'out' ? $type_id : $ref_type_id);						
					$arr_use_type_set = StoreType::getTypeSet($use_type_id); // Use the type set matching the reference direction (in or out)
											
					foreach ((array)$arr_object_connections['object_sub_details'] as $object_sub_details_id => $arr_object_sub_descriptions) {
						
						$arr_object_sub_details = $arr_use_type_set['object_sub_details'][$object_sub_details_id];
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$use_type_id], $arr_use_type_set, false, $object_sub_details_id)) {
							continue;
						}
						
						foreach ($arr_object_sub_descriptions as $object_sub_description_id => $arr_connection) {
							
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$use_type_id], $arr_use_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
							
							if ($arr_connection['use_object_description_id']) {
								//continue;
							}

							$path = implode('-', $arr_connection['path']);
							if ($steps === false && $source_path) {
								$path = $source_path.'-'.$path;
							}
							if ($object_sub_description_id == 'object_sub_location') {
								
								if ($arr_connection['dynamic']) {
									
									$checked = $arr_options['value']['paths'][$path]['object_sub_locations'][$object_sub_details_id][$in_out][$ref_type_id];
									$name = $arr_options['name'].'[paths]['.$path.'][object_sub_locations]['.$object_sub_details_id.']['.$in_out.']['.$ref_type_id.']';
									
									if ($in_out == 'out') {
										$arr_type_set_dynamic_referencing['object_sub_locations'][$object_sub_details_id][$ref_type_id] = $ref_type_id;
									}
								} else {
									
									$checked = $arr_options['value']['paths'][$path]['object_sub_locations'][$object_sub_details_id];
									$checked = (is_array($checked) ? $checked[$in_out] : $checked);
									$name = $arr_options['name'].'[paths]['.$path.'][object_sub_locations]['.$object_sub_details_id.']['.$in_out.']';
								}
								$str_name = getLabel('lbl_location');
							} else {
								
								if ($arr_connection['dynamic']) {
									
									$checked = $arr_options['value']['paths'][$path]['object_sub_descriptions'][$object_sub_description_id][$in_out][$ref_type_id];
									$name = $arr_options['name'].'[paths]['.$path.'][object_sub_descriptions]['.$object_sub_description_id.']['.$in_out.']['.$ref_type_id.']';
									
									if ($in_out == 'out') {
										$arr_type_set_dynamic_referencing['object_sub_descriptions'][$object_sub_description_id][$ref_type_id] = $ref_type_id;
									}
								} else {
									
									$checked = $arr_options['value']['paths'][$path]['object_sub_descriptions'][$object_sub_description_id];
									$checked = (is_array($checked) ? $checked[$in_out] : $checked);
									$name = $arr_options['name'].'[paths]['.$path.'][object_sub_descriptions]['.$object_sub_description_id.']['.$in_out.']';
								}
								$str_name = $arr_use_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_name'];
							}
							$arr_return_paths[$ref_type_id]['path'] = $path;
							$arr_return_paths[$ref_type_id]['checked'] = ($arr_return_paths[$ref_type_id]['checked'] ?: $checked);
							$arr_return_paths[$ref_type_id]['html'] .= '<label>'
								.'<span>'
									.'<span class="sub-name">'.Labels::parseTextVariables($arr_use_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_name']).'</span> <span'.($arr_connection['dynamic'] ? ' class="dynamic-references-name"' : '').'>'.Labels::parseTextVariables($str_name).'</span>'
								.'</span>'
								.'<input type="checkbox" name="'.$name.'" value="1"'.($checked ? ' checked="checked"' : '').' />'
								.'<span class="icon" data-category="direction">'.getIcon(($in_out == 'in' ? 'updown-up' : 'updown-down')).'</span>'
							.'</label>';
						}
					}
					foreach ((array)$arr_object_connections['object_descriptions'] as $object_description_id => $arr_connection) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_use_type_set['object_descriptions'][$object_description_id]['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$use_type_id], $arr_use_type_set, $object_description_id)) {
							continue;
						}
						
						$path = implode('-', $arr_connection['path']);
						if ($steps === false && $source_path) {
							$path = $source_path.'-'.$path;
						}
						if ($arr_connection['dynamic']) {
							
							$checked = $arr_options['value']['paths'][$path]['object_descriptions'][$object_description_id][$in_out][$ref_type_id];
							$name = $arr_options['name'].'[paths]['.$path.'][object_descriptions]['.$object_description_id.']['.$in_out.']['.$ref_type_id.']';
							
							if ($in_out == 'out') {
								$arr_type_set_dynamic_referencing['object_descriptions'][$object_description_id][$ref_type_id] = $ref_type_id;
							}
						} else {
							
							$checked = $arr_options['value']['paths'][$path]['object_descriptions'][$object_description_id];
							$checked = (is_array($checked) ? $checked[$in_out] : $checked);
							$name = $arr_options['name'].'[paths]['.$path.'][object_descriptions]['.$object_description_id.']['.$in_out.']';
						}
						$arr_return_paths[$ref_type_id]['path'] = $path;
						$arr_return_paths[$ref_type_id]['checked'] = ($arr_return_paths[$ref_type_id]['checked'] ?: $checked);
						$arr_return_paths[$ref_type_id]['html'] .= '<label>'
							.'<span'.($arr_connection['dynamic'] ? ' class="dynamic-references-name"' : '').'>'.Labels::parseTextVariables($arr_use_type_set['object_descriptions'][$object_description_id]['object_description_name']).'</span>'
							.'<input type="checkbox" name="'.$name.'" value="1"'.($checked ? ' checked="checked"' : '').' />'
							.'<span class="icon" data-category="direction">'.getIcon(($in_out == 'in' ? 'updown-up' : 'updown-down')).'</span>'
						.'</label>';
					}
				}
			}
			
			// Output
			
			$return .= '<div class="node">';
				$return .= '<h4>'.Labels::parseTextVariables($arr_type_set['type']['name']).'</h4>';
				
				$arr_options_values = $arr_options['value']['types'][$source_path][$type_id];
				$name = $arr_options['name'].'[types]['.$source_path.']['.$type_id.']';
				
				if (!$arr_options['descriptions']) {
					
					$arr_selector = [];
					if (keyIsUncontested('filter', $arr_options['functions'])) {
						$arr_selector[] = ['id' => 'filter', 'name' => getLabel('lbl_filter'), 'title' => getLabel('inf_path_filter')];
					}
					if ($source_path && keyIsUncontested('collapse', $arr_options['functions'])) {
						$arr_selector[] = ['id' => 'collapse', 'name' => getLabel('lbl_collapse'), 'title' => getLabel('inf_path_collapse')];
					}
					$arr_selected = [
						($arr_options_values['filter'] ? 'filter' : false),
						($arr_options_values['collapse'] ? 'collapse' : false)
					];

					$return .= '<fieldset><ul>
						<li>
							<label></label><span>'.cms_general::createSelector($arr_selector, $name, $arr_selected).'</span>
						</li>
					</ul></fieldset>';
				} else if ($arr_options['descriptions'] == 'pick') {
					
					$arr_sorter = [];
					
					foreach (($arr_options_values['selection'] ?: [[]]) as $id => $value) {
						
						$unique = uniqid('array_');
						
						$arr_sorter[] = ['value' => [
								'<select name="'.$name.'[selection]['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set_flat, $id, true)).'</select>'
							]
						];
					}
					
					$arr_selector = [['id' => 'filter', 'name' => getLabel('lbl_filter'), 'title' => getLabel('inf_path_filter')]];

					$arr_selected = [
						($arr_options_values['filter'] ? 'filter' : false)
					];

					$return .= '<fieldset><ul>
						<li>
							<label></label><span>'.cms_general::createSelector($arr_selector, $name, $arr_selected).'</span>
						</li>';
						
						$arr_selected = [
							($arr_options_values['nodegoat_id'] ? 'nodegoat_id' : false),
							($arr_options_values['id'] ? 'id' : false),
							($arr_options_values['name'] ? 'name' : false),
							($arr_options_values['sources'] ? 'sources' : false),
							($arr_options_values['analysis'] ? 'analysis' : false)
						];
					
						$return .= '<li>
							<label>'.getLabel('lbl_object').'</label>
							<span>'.cms_general::createSelectorList([
								['id' => 'nodegoat_id', 'name' => 'nodegoat ID'],
								['id' => 'id', 'name' => getLabel('lbl_object_id')],
								['id' => 'name', 'name' => getLabel('lbl_name')],
								['id' => 'sources', 'name' => getLabel('lbl_sources')],
								['id' => 'analysis', 'name' => getLabel('lbl_analysis')]
							], $name, $arr_selected).'</span>
						</li>
						<li>
							<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
						</li>
						<li>
							<label>'.getLabel('lbl_export').'</label>'.cms_general::createSorter($arr_sorter, true).'
						</li>
					</ul></fieldset>';
					
				} else if ($arr_options['descriptions'] == 'combine') {

					$arr_type_set_flat_separated = [];
					$arr_sorter = [];
					
					$arr_select_object_sub_details = $arr_selected_object_sub_ids = [];
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
						$arr_select_object_sub_details[] = ['id' => $str_id, 'name' => '<span class="sub-name">'.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'</span>'];
						
						if ($arr_options_values['selection'][$str_id]) {
							$arr_selected_object_sub_ids[$str_id] = $str_id;
							unset($arr_options_values['selection'][$str_id]);
						}
					}
					
					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						$str_id = 'object_description_'.$object_description_id;
						$is_dynamic = $arr_object_description['object_description_is_dynamic'];
						
						if ($arr_object_description['object_description_ref_type_id'] || $is_dynamic) {
						
							if ($is_dynamic) {
								
								$arr_type_set_flat_separated['referencing'][$str_id.'_reference'] = $arr_type_set_flat[$str_id];
								$arr_type_set_flat_separated['referencing'][$str_id.'_reference']['id'] = $str_id.'_reference';
								
								foreach ((array)$arr_type_set_dynamic_referencing['object_descriptions'][$object_description_id] as $ref_type_id) {
									
									$arr_type_set_flat_separated['referencing'][$str_id.'_reference_'.$ref_type_id] = $arr_type_set_flat[$str_id];
									$arr_type_set_flat_separated['referencing'][$str_id.'_reference_'.$ref_type_id]['id'] = $str_id.'_reference_'.$ref_type_id;
									
									$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);
									$arr_type_set_flat_separated['referencing'][$str_id.'_reference_'.$ref_type_id]['name'] .= ' - '.$arr_ref_type_set['type']['name'];
								}
							} else {
								
								$arr_type_set_flat_separated['referencing'][$str_id] = $arr_type_set_flat[$str_id];
							}
						}
						if (!$arr_object_description['object_description_ref_type_id']) {
							
							if ($is_dynamic) {
								$arr_type_set_flat_separated['values'][$str_id.'_value'] = $arr_type_set_flat[$str_id];
								$arr_type_set_flat_separated['values'][$str_id.'_value']['id'] = $str_id.'_value';
							} else {
								$arr_type_set_flat_separated['values'][$str_id] = $arr_type_set_flat[$str_id];
							}
						}
					}
					
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							$str_id = 'object_sub_description_'.$object_sub_description_id;
							$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
							
							if ($arr_object_sub_description['object_sub_description_ref_type_id'] || $is_dynamic) {
								
								if ($is_dynamic) {
									
									$arr_type_set_flat_separated['referencing'][$str_id.'_reference'] = $arr_type_set_flat[$str_id];
									$arr_type_set_flat_separated['referencing'][$str_id.'_reference']['id'] = $str_id.'_reference';
									
									foreach ((array)$arr_type_set_dynamic_referencing['object_sub_descriptions'][$object_sub_description_id] as $ref_type_id) {
									
										$arr_type_set_flat_separated['referencing'][$str_id.'_reference_'.$ref_type_id] = $arr_type_set_flat[$str_id];
										$arr_type_set_flat_separated['referencing'][$str_id.'_reference_'.$ref_type_id]['id'] = $str_id.'_reference_'.$ref_type_id;
										
										$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);
										$arr_type_set_flat_separated['referencing'][$str_id.'_reference_'.$ref_type_id]['name'] .= ' - '.$arr_ref_type_set['type']['name'];
									}
								} else {
									
									$arr_type_set_flat_separated['referencing'][$str_id] = $arr_type_set_flat[$str_id];
								}
							}
							if (!$arr_object_sub_description['object_sub_description_ref_type_id']) {
								
								if ($is_dynamic) {
									
									$arr_type_set_flat_separated['values'][$str_id.'_value'] = $arr_type_set_flat[$str_id];
									$arr_type_set_flat_separated['values'][$str_id.'_value']['id'] = $str_id.'_value';
								} else {
									
									$arr_type_set_flat_separated['values'][$str_id] = $arr_type_set_flat[$str_id];
								}
							}
						}
					}
					
					$arr_selection_referencing_or_values = [];
					
					foreach ((array)$arr_options_values['selection'] as $id => $arr_selected) {
						
						if (strpos($id, '_reference') !== false) { // Check if dynamic references are targeting specific type IDs and separate them
							
							$arr_ref_type_ids = ($arr_selected['object_description_reference'] ?: $arr_selected['object_sub_description_reference']);
							
							if ($arr_ref_type_ids && is_array($arr_ref_type_ids)) {
								
								foreach ($arr_ref_type_ids as $ref_type_id) {
									
									$arr_selection_referencing_or_values['referencing'][$id.'_'.$ref_type_id] = $arr_selected;
								}
								continue;
							}
						}
						
						$referencing_or_values = ($arr_type_set_flat_separated['referencing'][$id] || $arr_type_set_flat_separated['referencing'][$id.'_reference'] ? 'referencing' : 'values'); // Referencing has precedence over values in case of dynamic object descriptions
						
						$arr_selection_referencing_or_values[$referencing_or_values][$id] = $arr_selected;
					}
					
					foreach ($arr_type_set_flat_separated as $referencing_or_values => $arr_type_set_flat_separate) {		
								
						foreach (($arr_selection_referencing_or_values[$referencing_or_values] ?: [[]]) as $id => $arr_selected) {
							
							$unique = uniqid('array_');
							
							$arr_sorter[$referencing_or_values][] = ['value' => [
									'<select name="'.$name.'[selection]['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set_flat_separate, $id, true)).'</select>'
								]
							];
						}
					}
					
					$arr_selector = [
						['id' => 'filter', 'name' => getLabel('lbl_filter'), 'title' => getLabel('inf_path_filter')]
					];
					if ($source_path) {
						$arr_selector[] = ['id' => 'collapse', 'name' => getLabel('lbl_collapse'), 'title' => getLabel('inf_path_collapse')];
					}
					$arr_selected = [
						($arr_options_values['filter'] ? 'filter' : false),
						($arr_options_values['collapse'] ? 'collapse' : false)
					];
					
					$arr_selector_object = [
						['id' => 'object_only', 'name' => getLabel('lbl_object_only'), 'title' => getLabel('inf_path_object_only')]
					];
					$arr_selected_object = [
						($arr_options_values['object_only'] ? 'object_only' : false)
					];

					$return .= '<fieldset><ul>
						<li>
							<label></label><span>'.cms_general::createSelector($arr_selector, $name, $arr_selected).'</span>
						</li>
						<li>
							<label>'.getLabel('lbl_object').'</label><span>'.cms_general::createSelector($arr_selector_object, $name, $arr_selected_object).'</span>
						</li>';
					if ($arr_select_object_sub_details) {
						$return .= '<li>
							<label>'.getLabel('lbl_object_subs').'</label><span>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_select_object_sub_details, $name.'[selection][][id]', $arr_selected_object_sub_ids)).'</span>
						</li>';
					}
					if ($arr_sorter['referencing']) {
						$return .= '<li>
							<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
						</li>
						<li>
							<label>'.getLabel('lbl_referencing').'</label>'.cms_general::createSorter($arr_sorter['referencing'], true).'
						</li>';
					}
					if ($arr_sorter['values']) {
						$return .= '<li>
							<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
						</li>
						<li>
							<label>'.getLabel('lbl_values').'</label>'.cms_general::createSorter($arr_sorter['values'], true).'
						</li>';
					}
					$return .= '</ul></fieldset>';
				}
				
				$return_paths = '';
				
				foreach ($arr_return_paths as $ref_type_id => $arr_return_path) {
					
					if ($steps) {
						$return_deep = $func_connection($arr_network_connections['connections'][$ref_type_id], $ref_type_id, $arr_return_path['path']);
					} else {
						if ($arr_return_path['checked']) {
							$trace->run($ref_type_id, false, 1, $arr_options['references']);
							$arr_type_network_paths = $trace->getTypeNetworkPaths();
							$return_deep = $func_connection($arr_type_network_paths['connections'][$ref_type_id], $ref_type_id, $arr_return_path['path']);
						} else {
							$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);
							$return_deep = '<div class="node" data-type_id="'.$ref_type_id.'" data-source_path="'.$arr_return_path['path'].'"><h4>'.Labels::parseTextVariables($arr_ref_type_set['type']['name']).'</h4></div>';
						}
					}
					
					$return_paths .= '<div>'.$arr_return_path['html'].$return_deep.'</div>';
				}
				
				$return .= ($return_paths ? '<div>'.$return_paths.'</div>' : '');
				
			$return .= '</div>';
			
			return $return;
		};
		
		if ($to_type_id && $trace->getTotalSteps() <= 1) {
			return;
		}
		
		$arr_options_hop = [
			'name' => $arr_options['name'],
			'descriptions' => $arr_options['descriptions'],
			'functions' => $arr_options['functions'],
			'references' => $arr_options['references'],
			'network' => $arr_options['network']
		];

		$return = '<div class="network type"'.($steps === false ? ' id="y:data_model:get_type_network_hop-0"' : '').' data-options="'.htmlspecialchars(json_encode($arr_options_hop)).'"><div class="node">
			'.$func_connection($arr_type_network_paths['connections'][$from_type_id], $from_type_id, ($arr_options['source_path'] ?: 0)).'
		</div></div>';

		return $return;
	}
	
	public static function css() {
	
	
		$return = '.data_model .definition > fieldset .sorter textarea { width: 450px; height: 100px; }
					.data_model .object-descriptions > ul.sorter > li + li,
					.data_model .object-sub-descriptions > ul.sorter > li + li { margin-top: 25px; }
					.data_model .object-sub-details > .sorter > li > div > fieldset { margin: 0px; }
					.data_model .object-sub-details > .sorter > li { margin-top: 10px; }
					.data_model .object-sub-details > .sorter > li:first-child { margin-top: 0px; }
					
					.data_model .object-sub-details fieldset .tabs > div > div > fieldset { margin-left: 0px; } 
					
					.data_model .definition > fieldset > ul > li > label:first-child + [name=label] { width: 120px; }
					
					.condition fieldset input[name$="[image][url]"] + img { max-height: 20px; }
					
					.network.type { margin: 0px auto; }
					.network.type .node > fieldset { margin: 0px; margin-bottom: 10px; display: inline-block; }
					.network.type .node > fieldset select { max-width: 230px; }
					.network.type .node > fieldset:not(.hide) + div { margin-left: 10px; }
					.network.type .node > div > div > label { display: inline-block; margin: 5px 5px; }
					.network.type .node > div > div > label > input[type=checkbox] { display: block; margin: 1px auto 0px auto; }
					.network.type .node > div > div > label > .icon,
					.network.type .node > div > div > label > .icon { display: block; margin: 1px auto -3px auto; }';

		return $return;
	}
	
	public static function js() {

		$return = "
		SCRIPTER.static('.data_model', function(elm_scripter) {
		
			elm_scripter.on('click', '[id^=d\\\:data_model\\\:data-] .edit', function() {
				var cur = $(this);
				cur.quickCommand(cur.closest('.datatable').prev('form'), {html: 'replace'});
			}).on('click', '.definition .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', '.definition .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('change', '[id=y\\\:data_model\\\:set_filter-0] [name]', function() {
				var elm_command = this.closest('form');
				COMMANDS.setData(elm_command, serializeArrayByName(elm_command));
				COMMANDS.quickCommand(elm_command);
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_model\\\:]', function(elm_scripter) {
		
			var func_update_value_type = function(elm_value_type) {
			
				var elm_parent = elm_value_type.closest('ul');
				var elm_default_value = elm_parent.find('.default-value');
				elm_default_value.addClass('hide');
				
				var str_value_type = elm_value_type.val();
				var str_name = elm_value_type.attr('name');
				var ref_type_id = elm_parent.find('select[name*=description_ref_type_id]:not(.hide)').val();
				var has_multi = elm_parent.find('input[name*=description_has_multi]').is(':enabled:checked');
				var elm_value_type_options = elm_parent.find('div:not(.hide) [name*=\"description_value_type_options]\"]');
				var has_default_value = elm_parent.find('[name*=description_has_default_value]').is(':enabled:checked');
				
				var arr_value = {value_type: str_value_type, name: str_name, ref_type_id: (ref_type_id ? ref_type_id : false), has_multi: has_multi, value_type_options: (elm_value_type_options.length ? serializeArrayByName(elm_value_type_options) : false)};
				var identifier = JSON.stringify(arr_value);
				var identifier_check = elm_value_type[0].identifier_value_type;
				
				elm_value_type[0].identifier_value_type = identifier;
				
				if (!identifier_check || identifier_check == identifier) {
					
					if (has_default_value) {
						elm_default_value.removeClass('hide');
					}
					
					return;
				}

				COMMANDS.setData(elm_value_type, arr_value);
				
				elm_value_type.quickCommand(function(arr_data) {
					
					var elm_default = $(arr_data.default_value);
					
					elm_default_value.html(elm_default);

					if (has_default_value) {
						elm_default_value.removeClass('hide');
					}
					
					return elm_default;
				});
			};
			
			elm_scripter.on('ajaxloaded scripter', function(e) {
			
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				runElementSelectorFunction(e.detail.elm, '[name*=\"description_value_type_base]\"], input[name=reversal_mode]:checked, input[name=reversal_ref]:checked', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_data_model');
				});
			}).on('change update_data_model', '[name*=\"description_value_type_base]\"]', function(e) {
				
				var elm_value_type = $(this);
				var elm_parent = elm_value_type.closest('ul');
				var str_value_type = elm_value_type.val();
				
				var elm_targets = elm_parent.find('input[name*=description_has_multi], input[name*=description_is_required], input[name*=description_has_default_value], input[name*=object_description_is_unique], input[name*=object_description_is_identifier]');
				elm_targets.prop('disabled', false);
				
				if (str_value_type == 'type' || str_value_type == 'classification' || str_value_type == 'reversal') {
					
					var elm_target = elm_parent.find('select[name*=description_ref_type_id]').addClass('hide').filter('[name*=\"['+str_value_type+']\"]');
					elm_target.removeClass('hide');
				
					if (str_value_type == 'reversal') {
						elm_parent.find('input[name*=description_has_multi], input[name*=description_is_required], input[name*=description_has_default_value], input[name*=object_description_is_unique], input[name*=object_description_is_identifier]').prop('disabled', true);
					} else {
						elm_parent.find('input[name*=object_description_is_identifier]').prop('disabled', true);
					}
				} else {
				
					elm_parent.find('select[name*=description_ref_type_id]').addClass('hide')
					
					if (str_value_type == '' || str_value_type == 'int' || str_value_type == 'media' || str_value_type == 'media_external' || str_value_type == 'external') { // Multi

					} else {
						elm_parent.find('input[name*=description_has_multi]').prop('disabled', true);
					}
					if (str_value_type == '' || str_value_type == 'external') { // API identifier
					
					} else {
						elm_parent.find('input[name*=object_description_is_identifier]').prop('disabled', true);
					}
				}
				
				if (str_value_type == 'object_description') {
					var elm_target = elm_parent.find('select[name*=object_sub_description_use_object_description_id]');
					elm_target.closest('div').removeClass('hide');
				} else {
					var elm_target = elm_parent.find('select[name*=object_sub_description_use_object_description_id]');
					elm_target.closest('div').addClass('hide');
				}
				
				if (str_value_type == 'external' || str_value_type == 'text_tags' || str_value_type == 'reversal') {
					var elm_target = elm_parent.find('[name*=\"description_value_type_options]\"]');
					elm_target.closest('div').addClass('hide');
					elm_target = elm_target.filter('[name*=\"['+str_value_type+']\"]');
					elm_target.closest('div').removeClass('hide');
				} else {
					var elm_target = elm_parent.find('[name*=\"description_value_type_options]\"]');
					elm_target.closest('div').addClass('hide');
				}
				
				var elm_target = elm_parent.find('.description-options');
				if (elm_target.children('div:not(.hide)').length) {
					elm_target.removeClass('hide');
				} else {
					elm_target.addClass('hide');
				}

				func_update_value_type(elm_value_type);
			}).on('change', 'select[name*=description_ref_type_id]', function(e) {
			
				var elm_value_type = $(this).closest('ul').find('[name*=\"description_value_type_base]\"]');
				
				func_update_value_type(elm_value_type);
			}).on('change', '[name*=description_has_default_value], input[name*=description_has_multi], [name*=\"description_value_type_options]\"]', function(e) {
			
				var elm_value_type = $(this).closest('fieldset').closest('ul').find('[name*=\"description_value_type_base]\"]');
				
				func_update_value_type(elm_value_type);
			}).on('change update_data_model', '[name=reversal_mode]', function(e) {
				
				var cur = $(this);
				var value = cur.val();
				var elm_parent = cur.closest('li');
				var elm_target = elm_parent.nextAll('li');
				
				if (value == 1) {
					elm_target.addClass('hide');
				} else {
					elm_target.removeClass('hide');
				}
			}).on('change update_data_model', '[name=reversal_ref]', function(e) {
				
				var cur = $(this);
				var value = cur.val();
				var elm_parent = cur.closest('li');
				var elm_target = elm_parent.next('li');
				
				var elm_select = elm_target.find('select[name^=reversal_ref_type_id]').addClass('hide').filter('[name*=\"['+value+']\"]');
				if (elm_select.length) {
					elm_target.removeClass('hide');
					elm_select.removeClass('hide');
				} else {
					elm_target.addClass('hide');
				}
			});
			
			// SUB OBJECT DETAILS
			
			elm_scripter.on('ajaxloaded scripter', function(e) {
				
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				runElementSelectorFunction(e.detail.elm, '[name*=object_sub_details_date_type]:checked, [name*=object_sub_details_location_type]:checked, [name*=\"[object_sub_details_date_useage]\"], [name*=\"[object_sub_details_location_ref_type_id_locked]\"]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_data_model');
				});
			}).on('change', '[name*=object_sub_details_is_date_range]', function() {
				var cur = $(this);
				cur.closest('li').next('li').find('[name*=object_sub_details_date_use_object_sub_description_id], [name*=object_sub_details_date_use_object_description_id]').empty();
			}).on('change', '[id=y\\\:data_model\\\:selector_object_sub_details-0]', function() {
				var cur = $(this);
				cur.quickCommand(cur.siblings('[name*=\"[object_sub_details_location_ref_object_sub_details_id]\"]'));
				cur.closest('li').next('li').find('[name*=object_sub_details_location_use_object_sub_details_id], [name*=object_sub_details_location_use_object_sub_description_id], [name*=object_sub_details_location_use_object_description_id]').empty();
			}).on('change update_data_model', '[name*=object_sub_details_date_type], [name*=object_sub_details_location_type]', function() {
				
				var cur = $(this);
				var elms_target = cur.closest('li').nextAll('li');
				
				if (cur.val() == 'none') {
					elms_target.addClass('hide');
				} else {
					elms_target.removeClass('hide');
				}			
			}).on('change update_data_model', '[name*=object_sub_details_date_useage]', function() {
				
				var cur = $(this);
				var elm_target = cur.siblings('[name*=object_sub_details_date_use_object_sub_details_id], [name*=object_sub_details_date_use_object_sub_description_id], [name*=object_sub_details_date_use_object_description_id]');
				elm_target.hide();
				
				if (cur.val() == 'object_sub_details') {
					elm_target.filter('[name*=object_sub_details_date_use_object_sub_details_id]').show();
				} else if (cur.val() == 'object_sub_description') {
					elm_target.filter('[name*=object_sub_details_date_use_object_sub_description_id]').show();
				} else if (cur.val() == 'object_description') {
					elm_target.filter('[name*=object_sub_details_date_use_object_description_id]').show();
				}
			}).on('change update_data_model', '[name*=object_sub_details_location_ref_type_id_locked]', function() {
				var cur = $(this);
				var elm_target = cur.closest('li').next('li').find('[name*=object_sub_details_location_useage]');
				if (cur.prop('checked')) {
					elm_target.closest('li').show();
				} else {
					elm_target.val('').closest('li').hide();
				}
				elm_target.trigger('update_data_model');
			}).on('change update_data_model', '[name*=object_sub_details_location_useage]', function() {
				var cur = $(this);
				var elm_target = cur.siblings('[name*=object_sub_details_location_use_object_sub_details_id], [name*=object_sub_details_location_use_object_sub_description_id], [name*=object_sub_details_location_use_object_description_id]');
				elm_target.hide();
				if (cur.val() == 'object_sub_details') {
					elm_target.filter('[name*=object_sub_details_location_use_object_sub_details_id]').show();
				} else if (cur.val() == 'object_sub_description') {
					elm_target.filter('[name*=object_sub_details_location_use_object_sub_description_id]').show();
				} else if (cur.val() == 'object_description') {
					elm_target.filter('[name*=object_sub_details_location_use_object_description_id]').show();
				}
			}).on('change', '[id^=y\\\:data_model\\\:selector_object_sub_descriptions-]', function() {
				$(this).quickCommand($(this).siblings('[name*=object_sub_description_id]'));
			});
		});
		
		// CONDITIONS
		
		SCRIPTER.dynamic('[data-method=update_condition]', function(elm_scripter) {
		
			SCRIPTER.runDynamic(elm_scripter.find('.condition'));
		
			elm_scripter.on('command', '[id^=y\\\:data_model\\\:open_condition-]', function() {
									
				var cur = $(this);
				var elm_condition = elm_scripter.find('.condition');
					
				cur.data('target', function(html) {
				
					elm_condition.replaceWith(html);
					SCRIPTER.runDynamic(html);
				});
			});
		});
		
		SCRIPTER.dynamic('.condition', function(elm_scripter) {
		
			var func_update_condition_object_nodes_values = function(elm) {
			
				elm.find('select[name$=\"[id]\"]').each(function() {
				
					var cur = $(this);
				
					var value = cur.val();
					var elm_select = cur.closest('ul').find('select[name$=\"[number_use_object_description_id]\"]');
					var elm_target = elm_select.children('[data-group_identifier]');
					
					if (value) {
						elm_target.filter('[data-group_identifier=\"'+value+'\"]').show();
						elm_target.not('[data-group_identifier=\"'+value+'\"]').prop('selected', false).hide();
					} else {
						elm_target.prop('selected', false).hide();
					}
				});
			};

			func_update_condition_object_nodes_values(elm_scripter.find('div.identifiers'));
			
			elm_scripter.on('ajaxloaded scripter', function() {
				elm_scripter.find('.condition-model-conditions [name$=\"[condition_use_current]\"]').trigger('update_condition_model_conditions');
			}).on('change update_condition_model_conditions', '.condition-model-conditions [name$=\"[condition_use_current]\"]', function() {
				$(this).prev('select').prop('disabled', $(this).is(':checked'));
			}).on('change', 'div.identifiers select[name$=\"[id]\"]', function() {
				
				func_update_condition_object_nodes_values($(this).parent());
			}).on('click', 'fieldset > ul > li > span > .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', 'fieldset > ul > li > span > .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('click', 'fieldset > ul > li > div > img + .del', function() {
				var cur = $(this);
				cur.nextAll('input[type=button].hide').removeClass('hide');
				cur.add(cur.prevAll('img, input[type=hidden]')).remove();
			});
		});
		
		SCRIPTER.dynamic('.condition', 'application_filter');
		
		// NETWORK
		
		SCRIPTER.dynamic('.network.type', function(elm_scripter) {
			
			var func_update_type_network = function(elm) {
				
				elm.each(function() {
				
					var cur = $(this);
					var check = cur.parent('div').children('label').children('input:checked');
					var elm_target = cur.children('fieldset, div');
					if (!check.length) {
						elm_target.addClass('hide');
					} else {
						elm_target.removeClass('hide');
						func_update_type_network_object_only(elm_target.filter('fieldset').find('input[name$=\"[object_only]\"]'));
					}
				});
			};
			var func_update_type_network_object_only = function(elm) {
			
				var elm_target = elm.closest('li').nextAll('li').find('select, input');
				
				elm_target.prop('disabled', elm.is(':checked'));
			};
			
			elm_scripter.on('ajaxloaded scripter', function(e) {
			
				var cur = elm_scripter.find('> .node > .node').find('.node');
				
				if (cur.length) {
					func_update_type_network(cur);
				}
				
				if (e.type == 'scripter') {
					
					var cur = elm_scripter.find('> .node > .node > fieldset input[name$=\"[object_only]\"]');
				
					if (cur.length) {
						func_update_type_network_object_only(cur);
					}
				}
			}).on('change', '.node > div > div > label > input[type=checkbox]', function() {
				
				cur = $(this);
				var elm_target = $(this).closest('div').children('.node');
				var elm_target_children = elm_target.children('fieldset, div');
				var elm_source = cur.closest(elm_scripter);
				
				var func_scroll = function() {
										
					moveScroll(cur, {elm_con: elm_source});
					new Pulse(elm_target.children('h4'));
				};
				
				if (elm_target_children.length) {
					
					func_update_type_network(elm_target);
					func_scroll();
				} else if (elm_source.is('[id=y\\\:data_model\\\:get_type_network_hop-0]')) {
					
					var value = {type_id: elm_target.attr('data-type_id'), source_path: elm_target.attr('data-source_path'), options: elm_source.attr('data-options')};
					
					elm_source.data('value', value).quickCommand(function(elm) {
						
						elm_target.html(elm.find('> .node > .node').children());
						func_scroll();
						
						return elm_target;
					});
				}
			}).on('change', 'input[name$=\"[object_only]\"]', function() {
				
				func_update_type_network_object_only($(this));
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
	
		if ($method == "add") {
			
			$this->html = '<form id="f:data_model:insert-'.$id.'">'.$this->createType(($id == 'is_reversal' ? 'is_reversal' : ($id == 'is_classification' ? 'is_classification' : false))).'</form>';
		}
		if ($method == "edit") {
			
			$this->html = '<form id="f:data_model:update-'.$id.'">'.$this->createType($id).'</form>';
		}
		
		if ($method == "selector_value_type") {
			
			$str_name = str_replace('_value_type_base]', '_value_type_options_default_value]', $value['name']);
			
			$html_default_value = $this->createTypeObjectDefaultValue($str_name, $value['value_type'], $value['ref_type_id'], $value['has_multi']);
			
			$this->html = ['default_value' => $html_default_value];
		}
		
		if ($method == "selector_object_description") {
				
			$arr_type_set = StoreType::getTypeSet($id);
			$this->html = cms_general::createDropdown($arr_type_set['object_descriptions'], false, true, 'object_description_name', 'object_description_id');
		}
		if ($method == "selector_object_sub_details") {
			
			$this->html = cms_general::createDropdown(StoreType::getTypeObjectSubsDetails($value), 0, true, 'object_sub_details_name', 'object_sub_details_id');
		}
		if ($method == "selector_object_sub_descriptions") {
			
			$arr_type_set = StoreType::getTypeSet($id);
			$this->html = cms_general::createDropdown((array)$arr_type_set['object_sub_details'][$value]['object_sub_descriptions'], 0, true, 'object_sub_description_name', 'object_sub_description_id');
		}
		
		if ($method == "set_filter") {
			
			if (!Settings::get('domain_administrator_mode')) {
				error(getLabel('msg_not_allowed'));
			}
				
			SiteEndVars::setFeedback('filter_model', $value, true);
				
			$this->refresh_table = true;
		}
		
		if ($method == "edit_condition") {
			
			$type_id = toolbar::getFilterTypeId();
			
			$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, 0);
			$arr_condition_self = self::parseTypeCondition($type_id, $arr_condition['object']);
			$arr_model_conditions = self::parseTypeModelConditions($type_id, $arr_condition['model_object']);
			
			$this->html = '<form data-method="update_condition">
				'.$this->createTypeCondition($type_id, $arr_condition_self, $arr_model_conditions)
				.'<input type="submit" data-tab="condition" name="default_condition" value="'.getLabel('lbl_remove').' '.getLabel('lbl_conditions').'" />'
				.'<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_settings').'" />'
			.'</form>';
		}
		
		if ($method == "update_condition") {
			
			$type_id = toolbar::getFilterTypeId();
			
			if ($_POST['default_condition']) {
				
				$arr_condition_self = [];
				$arr_model_conditions = [];
				
				SiteEndVars::setFeedback('condition_id', false, true);
			} else {
				
				$arr_files = ($_FILES['condition'] ? arrRearrangeParams($_FILES['condition']) : []);
				
				$arr_condition_self = self::parseTypeCondition($type_id, $_POST['condition'], $arr_files);
				$arr_model_conditions = self::parseTypeModelConditions($type_id, $_POST['model_conditions']);
				
				SiteEndVars::setFeedback('condition_id', 0, true);
			}
			
			cms_nodegoat_custom_projects::handleProjectTypeCondition($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_condition_self, $arr_model_conditions);

			SiteEndVars::setFeedback('condition', ($arr_condition_self ? true : false));
			SiteEndVars::setFeedback('condition_model_conditions', ($arr_model_conditions ? true : false));
			$this->msg = true;
		}
		
		if ($method == "store_condition") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage">
				'.$this->createSelectCondition($type_id, true).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_condition") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage" data-method="return_condition">
				'.$this->createSelectCondition($type_id).'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "return_condition") {
			
			$type_id = $id;
			
			if ($_POST['condition_id']) {
				
				$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], false, $_POST['condition_id'], ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false));
				$arr_condition_self = self::parseTypeCondition($type_id, $arr_condition['object']);
				$arr_model_conditions = self::parseTypeModelConditions($type_id, $arr_condition['model_object']);
			} else {
				
				$arr_condition_self = $arr_model_conditions = [];
			}

			$this->html = $this->createTypeCondition($type_id, $arr_condition_self, $arr_model_conditions);
		}

		if ($method == "get_type_network_hop") {
			
			$arr_options = json_decode($value['options'], true);
			$arr_options['source_path'] = $value['source_path'];
			
			$this->html = self::createTypeNetwork((int)$value['type_id'], false, false, $arr_options);
		}
		
		if ($method == "data") {
	
			$sql_type_definitions = "(SELECT
					".DBFunctions::sqlImplode("CONCAT('<strong>', nodegoat_t_des.name, '</strong><br />', nodegoat_t_des.text)", '<br />', 'ORDER BY nodegoat_t_des.sort')."
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')." nodegoat_t_des
				WHERE nodegoat_t_des.type_id = nodegoat_t.id
			)";
			$sql_type_definitions_count = "(SELECT
					COUNT(nodegoat_t_des.id)
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')." nodegoat_t_des
				WHERE nodegoat_t_des.type_id = nodegoat_t.id
			)";
			
			if ($id == 'reversals') {
				
				$arr_sql_columns = ['nodegoat_t.name', $sql_type_definitions_count, 'nodegoat_t_ref.name'];
				$arr_sql_columns_search = ['nodegoat_t.name', $sql_type_definitions, 'nodegoat_t_ref.name'];
				$arr_sql_columns_as = ['nodegoat_t.id', 'nodegoat_t.name', $sql_type_definitions.' AS definitions', $sql_type_definitions_count.' AS definitions_count', 'nodegoat_to_des.ref_type_id AS reversal_ref_type_id', 'nodegoat_t_ref.name AS reversal_ref_type_name'];

				if (Settings::get('domain_administrator_mode')) {
					$arr_sql_columns[0] = 'CASE WHEN nodegoat_t.label != \'\' THEN CONCAT(nodegoat_t.label, \' \', nodegoat_t.name) ELSE nodegoat_t.name END';
					$arr_sql_columns_search[0] = 'CONCAT(nodegoat_t.label, \' \', nodegoat_t.name)';
					$arr_sql_columns_as[] = 'nodegoat_t.label';
				}
			} else {
				
				$sql_object_descriptions = "(SELECT
					".DBFunctions::sqlImplode('nodegoat_to_des.name', '<br />', 'ORDER BY nodegoat_to_des.sort')."
						FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
					WHERE nodegoat_to_des.type_id = nodegoat_t.id
				)";
				$sql_object_descriptions_count = "(SELECT
					COUNT(nodegoat_to_des.id)
						FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
					WHERE nodegoat_to_des.type_id = nodegoat_t.id
				)";
				$sql_object_sub_details = "(SELECT
					".DBFunctions::sqlImplode("CONCAT(nodegoat_tos_det.name, ' (', COALESCE((SELECT
						".DBFunctions::sqlImplode('nodegoat_tos_des.name', ', ', 'ORDER BY nodegoat_tos_des.sort')."
							FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des
						WHERE nodegoat_tos_des.object_sub_details_id = nodegoat_tos_det.id
					), '".getLabel('inf_none')."'), ')')", '<br />', 'ORDER BY nodegoat_tos_det.sort')."
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
					WHERE nodegoat_tos_det.type_id = nodegoat_t.id
				)";
				$sql_object_sub_details_count = "(SELECT 
						COUNT(nodegoat_tos_det.id)
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
					WHERE nodegoat_tos_det.type_id = nodegoat_t.id
				)";

				$arr_sql_columns = ['nodegoat_t.name', $sql_type_definitions_count, $sql_object_descriptions_count, $sql_object_sub_details_count];
				$arr_sql_columns_search = ['nodegoat_t.name', $sql_type_definitions, $sql_object_descriptions, $sql_object_sub_details];
				$arr_sql_columns_as = ['nodegoat_t.id', 'nodegoat_t.name', $sql_type_definitions.' AS definitions', $sql_type_definitions_count.' AS definitions_count', $sql_object_descriptions.' AS object_descriptions', $sql_object_descriptions_count.' AS object_descriptions_count', $sql_object_sub_details.' AS object_sub_details', $sql_object_sub_details_count.' AS object_sub_details_count'];
			
				if (Settings::get('domain_administrator_mode')) {
					$arr_sql_columns[0] = 'CASE WHEN nodegoat_t.label != \'\' THEN CONCAT(nodegoat_t.label, \' \', nodegoat_t.name) ELSE nodegoat_t.name END';
					$arr_sql_columns_search[0] = 'CONCAT(nodegoat_t.label, \' \', nodegoat_t.name)';
					$arr_sql_columns_as[] = 'nodegoat_t.label';
				}
			}
				
			if ($id =='reversals') {

				$sql_table = DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des ON (nodegoat_to_des.type_id = nodegoat_t.id AND nodegoat_to_des.id_id = 'rc_ref_type_id')
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t_ref ON (nodegoat_t_ref.id = nodegoat_to_des.ref_type_id)
				";

				$sql_index = 'nodegoat_t.id, nodegoat_to_des.ref_type_id';
			} else {
				
				$sql_table = DB::getTable('DEF_NODEGOAT_TYPES').' nodegoat_t';

				$sql_index = 'nodegoat_t.id';
			}
						
			$sql_where = "nodegoat_t.is_classification = ".($id == 'classifications' ? 'TRUE' : 'FALSE')." AND nodegoat_t.is_reversal = ".($id == 'reversals' ? 'TRUE' : 'FALSE');
			
			if (Settings::get('domain_administrator_mode')) {
				
				$arr_filter_model = (SiteStartVars::getFeedback('filter_model') ?: []);
				
				if ($arr_filter_model['project_id']) {
					
					$sql_table .= " JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." pt ON (pt.type_id = nodegoat_t.id AND pt.project_id = ".(int)$arr_filter_model['project_id'].")";
				}
				
				if ($arr_filter_model['label']) {
					
					if ($arr_filter_model['label'] == 'null') {
						$arr_filter_model['label'] = '';
					}
					
					$sql_where .= " AND nodegoat_t.label = '".DBFunctions::strEscape($arr_filter_model['label'])."'";
				}
			}
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];

				$arr_data['id'] = 'x:data_model:id-'.$arr_row['id'];
				
				$str_name = ($arr_row['label'] ? $arr_row['label'].' '.$arr_row['name'] : $arr_row['name']);
				$arr_data[] = Labels::parseTextVariables($str_name);
				$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_row['definitions'] ? htmlspecialchars(Labels::parseTextVariables($arr_row['definitions'])) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['definitions_count'].'</span></span>';
				if ($id != 'reversals') {
					$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_row['object_descriptions'] ? htmlspecialchars(Labels::parseTextVariables($arr_row['object_descriptions'])) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['object_descriptions_count'].'</span></span>';
					if ($id != 'classifications') {
						$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_row['object_sub_details'] ? htmlspecialchars(Labels::parseTextVariables($arr_row['object_sub_details'])) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['object_sub_details_count'].'</span></span>';
					}
				} else {
					$arr_data[] = ($arr_row['reversal_ref_type_id'] ? $arr_row['reversal_ref_type_name'] : '<span class="icon" data-category="status">'.getIcon('min').'</span>');
				}
				$arr_data[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data del msg empty" value="empty" /><input type="button" class="data del msg del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		// QUERY
		
		if (($method == "insert" || $method == "update") && $_POST['discard']) {
			
			if ($method == "update") {
				$arr_type_set = StoreType::getTypeSet($id);
				$what = ($arr_type_set['type']['is_reversal'] ? 'is_reversal' : ($arr_type_set['type']['is_classification'] ? 'is_classification' : false));
			} else {
				$what = ($id == 'is_reversal' ? 'is_reversal' : ($id == 'is_classification' ? 'is_classification' : false));
			}
							
			$this->html = $this->createAddType($what);
			return;
		}
		
		if ($method == "insert" || ($method == "update" && (int)$id)) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
		
			if (!$_POST['name']) {
				error(getLabel('msg_missing_information'));
			}

			$arr_details = [
				'name' => $_POST['name'],
				'label' => $_POST['label'],
				'color' => $_POST['color'],
				'condition_id' => (int)$_POST['condition_id'],
				'use_object_name' => (bool)$_POST['use_object_name'],
				'object_name_in_overview' => (bool)$_POST['object_name_in_overview'],
				'is_classification' => (bool)$_POST['is_classification'],
				'is_reversal' => (bool)$_POST['is_reversal']
			];
			
			$arr_definitions = [];
			
			foreach ($_POST['definition_name'] as $key => $value) {
				$arr_definitions[] = [
					'definition_name' => $_POST['definition_name'][$key],
					'definition_text' => $_POST['definition_text'][$key],
					'definition_id' => (int)$_POST['definition_id'][$key]
				];
			}
			
			if ($_POST['is_reversal']) {
				
				$reversal_ref_type_id = $_POST['reversal_ref_type_id'][$_POST['reversal_ref']];
				$arr_details['reversal_ref_type_id'] = (int)$reversal_ref_type_id;
				$arr_details['reversal_mode'] = (int)$_POST['reversal_mode'];
				
				$arr_object_descriptions = false;
			} else {
				
				$arr_object_descriptions = [];
				
				foreach ($_POST['object_descriptions'] as $value) {
					
					$object_description_value_type_base = $value['object_description_value_type_base'];
					
					switch ($object_description_value_type_base) {
						case 'external':
						case 'text_tags':
							$object_description_value_type_options = $value['object_description_value_type_options'][$object_description_value_type_base];
							break;
						default:
							$object_description_value_type_options = '';
					}
					
					if ($value['object_description_has_default_value']) {
						
						$object_description_value_type_options = ($object_description_value_type_options ?: []);
						$object_description_value_type_options['default']['value'] = $value['object_description_value_type_options_default_value'];
					}
					
					$arr_object_descriptions[] = [
						'object_description_name' => $value['object_description_name'],
						'object_description_value_type_base' => $object_description_value_type_base,
						'object_description_value_type_options' => $object_description_value_type_options,
						'object_description_is_required' => (int)$value['object_description_is_required'],
						'object_description_is_unique' => (int)$value['object_description_is_unique'],
						'object_description_has_multi' => (int)$value['object_description_has_multi'],
						'object_description_ref_type_id' => (int)($object_description_value_type_base == 'reversal' ? $value['object_description_ref_type_id']['reversal'] : ($object_description_value_type_base == 'classification' ? $value['object_description_ref_type_id']['classification'] : $value['object_description_ref_type_id']['type'])),
						'object_description_in_name' => (int)$value['object_description_in_name'],
						'object_description_in_search' => (int)$value['object_description_in_search'],
						'object_description_in_overview' => (int)$value['object_description_in_overview'],
						'object_description_is_identifier' => ($this->show_api_settings ? (int)$value['object_description_is_identifier'] : null),
						'object_description_clearance_edit' => ($this->show_user_settings ? (int)$value['object_description_clearance_edit'] : null),
						'object_description_clearance_view' => ($this->show_user_settings ? (int)$value['object_description_clearance_view'] : null),
						'object_description_id' => (int)$value['object_description_id']
					];
				}
				
				$arr_object_sub_details = [];
				
				foreach ((array)$_POST['object_sub_details'] as $arr_object_sub) {
					
					if (!$this->show_user_settings) {
						unset($arr_object_sub['object_sub_details']['object_sub_details_clearance_edit'], $arr_object_sub['object_sub_details']['object_sub_details_clearance_view']);
					}
					
					$arr_object_sub_descriptions = [];
					
					if ($arr_object_sub['object_sub_descriptions']) {
						
						foreach ($arr_object_sub['object_sub_descriptions'] as $value) {
							
							$object_sub_description_value_type_base = $value['object_sub_description_value_type_base'];
							
							switch ($object_sub_description_value_type_base) {
								case 'external':
								case 'text_tags':
									$object_sub_description_value_type_options = $value['object_sub_description_value_type_options'][$object_sub_description_value_type_base];
									break;
								default:
									$object_sub_description_value_type_options = '';
							}
							
							if ($value['object_sub_description_has_default_value']) {
						
								$object_sub_description_value_type_options = ($object_sub_description_value_type_options ?: []);
								$object_sub_description_value_type_options['default']['value'] = $value['object_sub_description_value_type_options_default_value'];
							}
							
							$arr_object_sub_descriptions[] = [
								'object_sub_description_name' => $value['object_sub_description_name'],
								'object_sub_description_value_type_base' => $object_sub_description_value_type_base,
								'object_sub_description_value_type_options' => $object_sub_description_value_type_options,
								'object_sub_description_use_object_description_id' => $value['object_sub_description_use_object_description_id'],
								'object_sub_description_ref_type_id' => (int)($object_sub_description_value_type_base == 'reversal' ? $value['object_sub_description_ref_type_id']['reversal'] : ($object_sub_description_value_type_base == 'classification' ? $value['object_sub_description_ref_type_id']['classification'] : $value['object_sub_description_ref_type_id']['type'])),
								'object_sub_description_is_required' => (int)$value['object_sub_description_is_required'],
								'object_sub_description_in_name' => (int)$value['object_sub_description_in_name'],
								'object_sub_description_in_search' => (int)$value['object_sub_description_in_search'],
								'object_sub_description_in_overview' => (int)$value['object_sub_description_in_overview'],
								'object_sub_description_clearance_edit' => ($this->show_user_settings ? (int)$value['object_sub_description_clearance_edit'] : null),
								'object_sub_description_clearance_view' => ($this->show_user_settings ? (int)$value['object_sub_description_clearance_view'] : null),
								'object_sub_description_id' => (int)$value['object_sub_description_id']
							];
						}
					}
					$arr_object_sub_details[] = [
						'object_sub_details' => $arr_object_sub['object_sub_details'],
						'object_sub_descriptions' => $arr_object_sub_descriptions
					];
				}
			}
			
			$store_type = new StoreType($id);
			
			$type_id = $store_type->store($arr_details, $arr_definitions, $arr_object_descriptions, $arr_object_sub_details);
			
			StoreType::setTypesObjectPaths();
			
			$arr_filter_model = (SiteStartVars::getFeedback('filter_model') ?: []);
			
			if ($method == 'insert' && $arr_filter_model['project_id']) {
				
				if ($_SESSION['CUR_USER'][DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')][$arr_filter_model['project_id']]) {
					cms_nodegoat_custom_projects::addProjectTypes($arr_filter_model['project_id'], $type_id);
				}
			}
			
			$this->html = $this->createAddType(($_POST['is_reversal'] ? 'is_reversal' : ($_POST['is_classification'] ? 'is_classification' : 'is_type')));
			$this->refresh_table = true;
			$this->msg = true;
		}
	
		if ($method == "empty" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
		
			StoreTypeObjects::clearTypeObjects($id);
								
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			$store_type = new StoreType($id);
			
			$store_type->delType();
								
			$this->msg = true;
		}
	}
	
	public static function parseTypeNetwork($arr) {
		
		$arr = ['paths' => ($arr['paths'] ?: []), 'types' => ($arr['types'] ?: [])];
		
		foreach ($arr['paths'] as $source_path => $value) {
			if (!$value) {
				unset($arr['paths'][$source_path]);
			}
		}
	
		foreach ($arr['types'] as $source_path => $arr_source_type) {
			
			foreach ($arr_source_type as $type_id => $arr_type) {
				
				$arr_type_set = StoreType::getTypeSet($type_id);
				$s_arr = &$arr['types'][$source_path][$type_id];

				$arr_selection = [];
				
				// Pre-parse selection
				
				foreach ((array)$s_arr['selection'] as $id => $arr_selected) {
					
					if (isset($arr_selected['id'])) { // Form
						
						if (is_array($arr_selected['id'])) {
							$str_id = current($arr_selected['id']);
						} else {
							$str_id = $arr_selected['id'];
						}
						
						if (!$str_id) {
							continue;
						}
						
						$pos_dynamic_reference = strpos($str_id, '_reference_');
					
						if ($pos_dynamic_reference !== false) {
							
							$ref_type_id = (int)substr($str_id, $pos_dynamic_reference + 11);
							$str_id = substr($str_id, 0, $pos_dynamic_reference + 10);
							
							if ($ref_type_id) {
								
								$arr_selection[$str_id]['ref_type_ids'][$ref_type_id] = $ref_type_id;
								continue;
							}
						}
					} else { // Already parsed previously
						
						$str_id = $id;
						
						if (!$str_id) {
							continue;
						}
						
						$arr_ref_type_ids = ($arr_selected['object_description_reference'] ?: $arr_selected['object_sub_description_reference']);
						
						if ($arr_ref_type_ids) {
							
							$arr_selection[$str_id]['ref_type_ids'] = $arr_ref_type_ids;
							continue;
						}
					}

					if ($arr_selection[$str_id] === null) {
						$arr_selection[$str_id] = [];
					}
				}
				
				// Amend selection
				
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
					
					$str_id = 'object_description_'.$object_description_id;
					$is_dynamic = $arr_object_description['object_description_is_dynamic'];
					$in_selection = ($arr_selection[$str_id] !== null || ($is_dynamic && ($arr_selection[$str_id.'_reference'] !== null || $arr_selection[$str_id.'_value'] !== null)));
					
					if ($in_selection && $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view']) {
					
						unset($arr_selection[$str_id]);
						if ($is_dynamic) {
							unset($arr_selection[$str_id.'_reference'], $arr_selection[$str_id.'_value']);
						}
					} else if ($in_selection) {
						
						if ($arr_selection[$str_id] !== null) {
							$arr_selection[$str_id]['object_description_id'] = $object_description_id;
						}
						
						if ($is_dynamic) {
							
							if ($arr_selection[$str_id.'_reference'] !== null) {
								
								$arr_selection[$str_id.'_reference']['object_description_id'] = $object_description_id;
								$arr_selection[$str_id.'_reference']['object_description_reference'] = ($arr_selection[$str_id.'_reference']['ref_type_ids'] ?: true);
								
								unset($arr_selection[$str_id.'_reference']['ref_type_ids']);
							}
							if ($arr_selection[$str_id.'_value'] !== null) {
								
								$arr_selection[$str_id.'_value']['object_description_id'] = $object_description_id;
								$arr_selection[$str_id.'_value']['object_description_value'] = true;
							}
						}
					}
				}
				
				foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
					
					if ($arr_selection[$str_id] !== null) {
						$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						$str_id = 'object_sub_description_'.$object_sub_description_id;
						$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
						$in_selection = ($arr_selection[$str_id] !== null || ($is_dynamic && ($arr_selection[$str_id.'_reference'] !== null || $arr_selection[$str_id.'_value'] !== null)));
						
						if ($in_selection && $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view']) {
							
							unset($arr_selection[$str_id]);
							if ($is_dynamic) {
								unset($arr_selection[$str_id.'_reference'], $arr_selection[$str_id.'_value']);
							}
						} else if ($in_selection) {
							
							if ($arr_selection[$str_id] !== null) {
								
								$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
								$arr_selection[$str_id]['object_sub_description_id'] = $object_sub_description_id;
							}
							
							if ($is_dynamic) {
								
								if ($arr_selection[$str_id.'_reference'] !== null) {
									
									$arr_selection[$str_id.'_reference']['object_sub_details_id'] = $object_sub_details_id;
									$arr_selection[$str_id.'_reference']['object_sub_description_id'] = $object_sub_description_id;
									$arr_selection[$str_id.'_reference']['object_sub_description_reference'] = ($arr_selection[$str_id.'_reference']['ref_type_ids'] ?: true);
									
									unset($arr_selection[$str_id.'_reference']['ref_type_ids']);
								}
								if ($arr_selection[$str_id.'_value'] !== null) {
									
									$arr_selection[$str_id.'_value']['object_sub_details_id'] = $object_sub_details_id;
									$arr_selection[$str_id.'_value']['object_sub_description_id'] = $object_sub_description_id;
									$arr_selection[$str_id.'_value']['object_sub_description_value'] = true;
								}
							}
						}			
					}				
				}
				
				$s_arr = [
					'filter' => ($s_arr ? (bool)$s_arr['filter'] : false),
					'collapse' => ($s_arr ? (bool)$s_arr['collapse'] : false),
					'object_only' => ($s_arr ? (bool)$s_arr['object_only'] : false)
				];
				$s_arr['selection'] = ($s_arr['object_only'] ? [] : $arr_selection);
				
				if (!$s_arr['selection'] && !$s_arr['filter'] && !$s_arr['collapse'] && !$s_arr['object_only']) {
					unset($arr['types'][$source_path][$type_id]);
				}
			}
			
			if (!$arr['types'][$source_path]) {
				unset($arr['types'][$source_path]);
			}
		}
		
		return $arr;
	}
	
	public static function parseTypeCondition($type_id, $arr, $arr_files = []) {
		
		if ($arr && !$arr['object'] && !$arr['object_descriptions'] && !$arr['object_sub_details']) { // Form
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$arr_condition_actions = cms_nodegoat_definitions::getSetConditionActions();
			
			$arr_condition = [];
			
			foreach ($arr as $key => $arr_condition_setting) {
				
				if (!$arr_condition_setting['id']) {
					continue;
				}
				
				$condition_filter = json_decode($arr_condition_setting['condition_filter'], true);
				$condition_actions = [];
				
				foreach ($arr_condition_actions as $action => $arr_action) {
					
					if (!$arr_condition_setting['condition_actions'][$action]) {
						continue;
					}
					
					foreach ($arr_action['value'] as $value) {
						
						$type = (is_array($value) ? $value['type'] : $value);
						
						switch ($type) {
							case 'emphasis':
								$return = array_filter(array_values($arr_condition_setting['condition_actions'][$action][$type]));
								break;
							case 'color':
								$return = str2Color($arr_condition_setting['condition_actions'][$action][$type]);
								break;
							case 'image':
							
								$return = '';
								$url = $arr_condition_setting['condition_actions'][$action][$type]['url'];
								
								if ($url) {
									
									if (isPath(DIR_HOME_CUSTOM_PROJECT_WORKSPACE.$url)) {
										
										$return = $url;
									}
								} else if ($arr_files[$key]['name']['condition_actions'][$action][$type]['file']) {
									
									$arr_file = $arr_files[$key];
									
									foreach ($arr_file as $key_file => $value_file) {
										
										$arr_file[$key_file] = $value_file['condition_actions'][$action][$type]['file'];
									}
									
									$file = $arr_file['tmp_name'];
									
									$extension = FileStore::getFileExtension($file);
									$extension = ($extension ?: FileStore::getFilenameExtension($arr_file['name']));
									
									if ($extension != 'svg') {
										
										Labels::setVariable('type', 'svg');
										error(getLabel('msg_invalid_file_type_specific'));
									}
									
									$filename = hash_file('md5', $file);
									$filename = $filename.'.'.$extension;
									
									if (!isPath(DIR_HOME_CUSTOM_PROJECT_WORKSPACE.$filename)) {
										$store_file = new FileStore($arr_file, ['dir' => DIR_HOME_CUSTOM_PROJECT_WORKSPACE, 'filename' => $filename]);
									}

									$return = $filename;
								}
								break;
							default:
								$return = $arr_condition_setting['condition_actions'][$action][$type];
						}
						
						if ((string)$return !== '' && $return !== []) {
							$condition_actions[$action][$type] = $return;
						}
					}
				}
				
				if (!$condition_actions && !$arr_condition_setting['condition_label']) {
					continue;
				}

				$arr_condition_setting_clean = [
					'condition_filter' => $condition_filter,
					'condition_actions' => $condition_actions,
					'condition_in_object_name' => (int)$arr_condition_setting['condition_in_object_name'],
					'condition_in_object_values' => (int)$arr_condition_setting['condition_in_object_values'],
					'condition_in_object_nodes_object' => (int)$arr_condition_setting['condition_in_object_nodes_object'],
					'condition_in_object_nodes_referencing' => (int)$arr_condition_setting['condition_in_object_nodes_referencing'],
					'condition_label' => $arr_condition_setting['condition_label']
				];

				if ($arr_condition_setting['id'] == 'id') {
					$arr_condition['object'][] = $arr_condition_setting_clean;
				}				
			
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $value) {
					
					$str_id = 'object_description_'.$object_description_id;
					
					if ($arr_condition_setting['id'] == $str_id && $_SESSION['NODEGOAT_CLEARANCE'] >= $value['object_description_clearance_view']) {
						$arr_condition['object_descriptions'][$object_description_id][] = $arr_condition_setting_clean;
						break;
					}				
				}
				
				foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
					
					if ($arr_condition_setting['id'] == $str_id) {
						$arr_condition['object_sub_details'][$object_sub_details_id]['object_sub_details'][] = $arr_condition_setting_clean;
						break;
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $value) {
						
						$str_id = 'object_sub_description_'.$object_sub_description_id;
						
						if ($arr_condition_setting['id'] == $str_id) {
							$arr_condition['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id][] = $arr_condition_setting_clean;
							break;
						}					
					}				
				}
				
			}
		} else {
			
			$arr_condition = $arr;
		}
		
		return $arr_condition;
	}
	
	public static function parseTypeModelConditions($type_id, $arr) {
		
		$arr_collect = [];
		
		foreach ((array)$arr as $cur_type_id => $arr_type_condition) {
			
			if ($arr_type_condition['condition_id']) {
				$arr_collect[$cur_type_id] = ['condition_id' => $arr_type_condition['condition_id']];
			} else if ($arr_type_condition['condition_use_current']) {
				$arr_collect[$cur_type_id] = ['condition_use_current' => true];
			}
		}
		
		return $arr_collect;
	}
	
	public static function parseTypeContext($type_id, $arr) {
		
		$arr_collect = [];
		
		foreach ((array)$arr['include'] as $arr_context_include) {
			
			if (!$arr_context_include['type_id'] || !$arr_context_include['scenario_id'] ) {
				continue;
			}
			
			$arr_collect['include'][$arr_context_include['type_id'].'_'.$arr_context_include['scenario_id']] = ['type_id' => $arr_context_include['type_id'], 'scenario_id' => $arr_context_include['scenario_id']];
		}
		
		return $arr_collect;
	}

	private function checkValidUserId($id) {
	
		$check = DB::query("SELECT user_id
				FROM ".$this->main_table."
			WHERE user_id = ".(int)$id." AND parent_user_id = ".$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['id']."
		");
		
		if (!$check->getRowCount()) {
			error('Unauthorized ID! Actions have been noted.');
		}
	}
}
