<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
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
	private $do_show_user_settings = false;
	private $do_show_api_settings = false;
	
	const TYPE_NETWORK_DESCRIPTIONS_FLAT = 'flat';
	const TYPE_NETWORK_DESCRIPTIONS_CONCEPT = 'concept';
	const TYPE_NETWORK_DESCRIPTIONS_DISPLAY = 'display';
	const TYPE_NETWORK_DESCRIPTIONS_DATE = 'date';
	const SYMBOL_IN = '🡩';
	const SYMBOL_OUT = '🡫';
	const SYMBOL_MUTABLE = '~';
	const SYMBOL_DYNAMIC = '*';
	
	function __construct() {
		
		parent::__construct();
		
		$arr_users_link = pages::getClosestModule('register_by_user');
		$this->do_show_user_settings = ($arr_users_link && pages::filterClearance([$arr_users_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]));
		
		$arr_api_link = pages::getClosestModule('api_configuration');
		$this->do_show_api_settings = ($arr_api_link && pages::filterClearance([$arr_api_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]));
	}
		
	public function contents() {
		
		$return = '';
		
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
						
		$return .= '<div class="tabs">
			<ul>
				<li><a href="#">'.StoreType::getTypeClassName(StoreType::TYPE_CLASS_TYPE, true).'</a></li>
				<li><a href="#">'.StoreType::getTypeClassName(StoreType::TYPE_CLASS_CLASSIFICATION, true).'</a></li>
				<li><a href="#">'.StoreType::getTypeClassName(StoreType::TYPE_CLASS_REVERSAL, true).'</a></li>
			</ul>
			<div>
				
				'.$this->createAddType(StoreType::TYPE_CLASS_TYPE).'
				
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
			
				'.$this->createAddType(StoreType::TYPE_CLASS_CLASSIFICATION).'
				
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
			
				'.$this->createAddType(StoreType::TYPE_CLASS_REVERSAL).'
				
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
	
	private function createAddType($num_class) {
		
		$str_class = StoreType::getTypeClassName($num_class);

		$return = '<form id="f:data_model:add-'.$num_class.'" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.$str_class.'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	private function createType($type_id = false) {
		
		$arr_type_set = [];
		$arr_type_conditions = [];
		
		if (is_numeric($type_id)) {
		
			$arr_type_set = StoreType::getTypeSet($type_id);
			$arr_type_conditions = cms_nodegoat_custom_projects::getProjectTypeConditions(false, false, $type_id, false, true);
			
			if (Settings::get('domain_administrator_mode')) {
			
				$arr_type_set['type']['name'] = $arr_type_set['type']['name_raw'];
			}
		}
		
		if ($type_id === 'reversal' || $arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL) {
			$str_class = StoreType::getTypeClassName(StoreType::TYPE_CLASS_REVERSAL);
			$num_type_class = StoreType::TYPE_CLASS_REVERSAL;
		} else if ($type_id === 'classification' || $arr_type_set['type']['class'] == StoreType::TYPE_CLASS_CLASSIFICATION) {
			$str_class = StoreType::getTypeClassName(StoreType::TYPE_CLASS_CLASSIFICATION);
			$num_type_class = StoreType::TYPE_CLASS_CLASSIFICATION;
		} else {
			$str_class = StoreType::getTypeClassName(StoreType::TYPE_CLASS_TYPE);
			$num_type_class = StoreType::TYPE_CLASS_TYPE;
		}
		$type_id = (int)$type_id;
		
		$num_type_mode = ($type_id ? (int)$arr_type_set['type']['mode'] : 0);
		
		$is_disabled = false;
		
		$arr_types = StoreType::getTypes();
		$arr_types_classifications = [StoreType::TYPE_CLASS_TYPE => [], StoreType::TYPE_CLASS_CLASSIFICATION => [], StoreType::TYPE_CLASS_REVERSAL => []];
		
		foreach ($arr_types as $cur_type_id => $arr_cur_type) {
			
			$arr_types_classifications[$arr_cur_type['class']][$cur_type_id] = $arr_cur_type;
		}
		
		$arr_mutable_references = [];
		
		foreach ([StoreType::TYPE_CLASS_TYPE, StoreType::TYPE_CLASS_CLASSIFICATION] as $num_cur_type_class) {
			
			$str_cur_class_name = StoreType::getTypeClassName($num_cur_type_class);
			
			foreach ($arr_types_classifications[$num_cur_type_class] as $cur_type_id => $arr_cur_type) {
				
				$arr_cur_type['name'] = $str_cur_class_name.cms_general::OPTION_GROUP_SEPARATOR.$arr_cur_type['name'];
				$arr_mutable_references[$cur_type_id] = $arr_cur_type;
			}
		}
		
		$return = '<h1>'.($type_id ? '<span>'.$str_class.': '.strEscapeHTML(Labels::parseTextVariables($arr_type_set['type']['name'])).'</span><small title="'.getLabel('lbl_type').' ID">'.$type_id.'</small>' : '<span>'.$str_class.'</span>').'</h1>';
		
		$return .= '<div class="definition options">
			
			<fieldset>
				<input type="hidden" name="class" value="'.$num_type_class.'" />
				<ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<input type="text" name="name" value="'.strEscapeHTML($arr_type_set['type']['name']).'" />
					</li>';
					
					if (Settings::get('domain_administrator_mode')) {
						
						$return .= '<li>
							<label>'.getLabel('lbl_label').'</label>
							<input type="text" name="label" value="'.strEscapeHTML($arr_type_set['type']['label']).'" title="'.getLabel('inf_administrator_visible').'" />
						</li>';
					}
					
					$return .= '<li>
						<label>'.getLabel('lbl_color').'</label>
						<input name="color" type="text" value="'.$arr_type_set['type']['color'].'" class="colorpicker" title="'.getLabel('inf_type_color').'" />
					</li>';
					
					if ($num_type_class != StoreType::TYPE_CLASS_REVERSAL) {
						
						$return .= '<li>
							<label>'.getLabel('lbl_conditions').'</label>
							<select name="condition_id" title="'.getLabel('inf_type_conditions').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_conditions, $arr_type_set['type']['condition_id'], true, 'label')).'</select>
						</li>';
					}
					
					if ($this->do_show_user_settings) {
						
						$return .= '<li>
							<label>'.getLabel('lbl_clearance').'</label>
							<select name="clearance_edit" title="'.getLabel('inf_type_clearance_edit').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_type_set['type']['clearance_edit'], true, 'label').'</select>
						</li>';
					}
					
					$return .= '<li>
						<label>'.getLabel('lbl_definitions').'</label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label></label>
						<div>';
							
							$arr_sorter = [];
							
							foreach (($arr_type_set['definitions'] ?: [[]]) as $arr_definition) {
								$arr_sorter[] = ['value' => [
									'<input type="text" name="definition_name[]" value="'.strEscapeHTML($arr_definition['definition_name']).'" />',
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
					'.(($num_type_class == StoreType::TYPE_CLASS_TYPE || $num_type_class == StoreType::TYPE_CLASS_CLASSIFICATION) ? '<li><a href="#">'.getLabel(($num_type_class == StoreType::TYPE_CLASS_CLASSIFICATION ? 'lbl_category' : 'lbl_object')).'</a></li>' : '').'
					'.($num_type_class == StoreType::TYPE_CLASS_REVERSAL ? '<li><a href="#">'.getLabel('lbl_process').'</a></li>' : '').'
					'.($num_type_class == StoreType::TYPE_CLASS_TYPE ? '<li><a href="#">'.getLabel('lbl_object_sub').'</a></li>' : '').'
				</ul>';
				
				if ($num_type_class == StoreType::TYPE_CLASS_TYPE || $num_type_class == StoreType::TYPE_CLASS_CLASSIFICATION) {
					
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
									<label>'.getLabel('lbl_descriptions').'</label>
									<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
								</li>
								<li><label></label>
									<div class="object-descriptions">';
										
										$arr_value_types = StoreType::getValueTypesBase();
										unset($arr_value_types['object_description']);
										Settings::get('hook_data_model_value_types', false, [&$arr_value_types, 'object_description']);
										
										foreach ($arr_value_types as &$arr_value_type) {
											
											if ($arr_value_type['has_support_multi']) {
												$arr_value_type['attr']['data-has_support_multi'] = '1';
											}
											if ($arr_value_type['has_support_identifier']) {
												$arr_value_type['attr']['data-has_support_identifier'] = '1';
											}
										}
										unset($arr_value_type);
										
										$arr_sorter = [];
										
										$arr_object_descriptions = $arr_type_set['object_descriptions'];
										if (!$arr_object_descriptions) {
											$arr_object_descriptions[] = [];
										}
										array_unshift($arr_object_descriptions, []); // Empty run for sorter source
										
										foreach ($arr_object_descriptions as $key => $arr_object_description) {
											
											$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
											
											$arr_referencing_type_ids = [];
											$arr_sorter_reference_mutable = [];
											
											if (is_array($arr_object_description['object_description_ref_type_id'])) {
												$arr_referencing_type_ids += $arr_object_description['object_description_ref_type_id'];
											}
											if (count($arr_referencing_type_ids) < StoreType::$num_mutable_references) {
												$arr_referencing_type_ids[] = '';
											}

											foreach ($arr_referencing_type_ids as $referencing_type_id) {
												
												$arr_sorter_reference_mutable[] = ['value' => [
													'<select name="object_descriptions['.$unique.'][object_description_ref_type_id][reference_mutable][]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_mutable_references, $referencing_type_id, true)).'</select>'
												]];
											}												
											
											$str_html_reference_mutable = cms_general::createSorter($arr_sorter_reference_mutable, 'append', false, ['auto_add' => true, 'auto_clean' => true, 'limit' => StoreType::$num_mutable_references]);
											
											$has_default_value = (bool)$arr_object_description['object_description_value_type_settings']['default']['value'];
											
											$arr_sorter[] = ['source' => ($key == 0 ? true : false), 'value' => [
												'<input type="text" name="object_descriptions['.$unique.'][object_description_name]" value="'.strEscapeHTML($arr_object_description['object_description_name']).'" />'
												.'<select name="object_descriptions['.$unique.'][object_description_value_type_base]" id="y:data_model:selector_value_type-0">'.cms_general::createDropdown($arr_value_types, $arr_object_description['object_description_value_type_base']).'</select>'
												.'<select name="object_descriptions['.$unique.'][object_description_ref_type_id][type]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_TYPE], $arr_object_description['object_description_ref_type_id'])).'</select>'
												.'<select name="object_descriptions['.$unique.'][object_description_ref_type_id][classification]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_CLASSIFICATION], $arr_object_description['object_description_ref_type_id'])).'</select>'
												.'<select name="object_descriptions['.$unique.'][object_description_ref_type_id][reversal]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_REVERSAL], $arr_object_description['object_description_ref_type_id'])).'</select>'
												.'<div class="reference_mutable input">'.$str_html_reference_mutable.'</div>'
												.'<input type="hidden" name="object_descriptions['.$unique.'][object_description_id]" value="'.$arr_object_description['object_description_id'].'" />'
												,'<fieldset><ul>
													<li>'
														.'<div>'
															.'<label title="'.getLabel('inf_object_description_has_multi').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_has_multi]" value="1"'.($arr_object_description['object_description_has_multi'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_multiple').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_is_required').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_is_required]" value="1"'.($arr_object_description['object_description_is_required'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_required').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_has_default_value').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_has_default_value]" value="1"'.($has_default_value ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_default').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_is_unique').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_is_unique]" value="1"'.($arr_object_description['object_description_is_unique'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_unique').'</span></label>'
															.'<span class="split"></span>'
															.'<label title="'.getLabel('inf_object_description_in_name').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_in_name]" value="1"'.($arr_object_description['object_description_in_name'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_name').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_in_search').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_in_search]" value="1"'.($arr_object_description['object_description_in_search'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_quick_search').'</span></label>'
															.'<label title="'.getLabel('inf_object_description_in_overview').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_in_overview]" value="1"'.(!$arr_object_description || $arr_object_description['object_description_in_overview'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_overview').'</span></label>'
															.($this->do_show_api_settings ?
																'<label title="'.getLabel('inf_object_description_is_identifier').'"><input type="checkbox" name="object_descriptions['.$unique.'][object_description_is_identifier]" value="1"'.($arr_object_description['object_description_is_identifier'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_identifier').'</span></label>'
															: '')
														.'</div>'
													.'</li>'
													.'<li class="object-description-options">'
														.$this->createTypeObjectDescriptionValueTypeOptions($type_id, 'object_descriptions['.$unique.']', $arr_object_description['object_description_value_type_base'], $arr_object_description['object_description_ref_type_id'], $arr_object_description['object_description_has_multi'], $has_default_value, $arr_object_description['object_description_in_name'], $arr_object_description)
													.'</li>'
													.($this->do_show_user_settings ? '<li>'
														.'<select name="object_descriptions['.$unique.'][object_description_clearance_edit]" title="'.getLabel('inf_object_description_clearance_edit').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_description['object_description_clearance_edit'], true, 'label').'</select>'
														.'<select name="object_descriptions['.$unique.'][object_description_clearance_view]" title="'.getLabel('inf_object_description_clearance_view').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_description['object_description_clearance_view'], true, 'label').'</select>'
													.'</li>' : '').'
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
				
				if ($num_type_class == StoreType::TYPE_CLASS_REVERSAL) {
					
					$reversal_ref_type_id = false;
					$arr_module_settings = [];
					
					if ($type_id) {
							
						foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
							
							if ($arr_object_description['object_description_value_type'] == 'reference') {
								$reversal_ref_type_id = $arr_object_description['object_description_ref_type_id'];
							}
							if ($arr_object_description['object_description_value_type'] == 'reversal_module') {
								$arr_module_settings = $arr_object_description['object_description_value_type_settings'];
							}
						}
					}
					
					$reference = '';
					if (!$type_id) {
						$reference = 'classification';
					} else if ($reversal_ref_type_id) {
						$reference = ($arr_types_classifications[StoreType::TYPE_CLASS_TYPE][$reversal_ref_type_id] ? 'type' : 'classification');
					}
					$has_reversal_resource_path = false;
					if ($arr_module_settings) {
						$has_reversal_resource_path = $arr_module_settings['resource_path'];
					}
					
					$return .= '<div>
						<div class="options">
							<fieldset><ul>
								<li>
									<label>'.getLabel('lbl_mode').'</label>
									<span>'.cms_general::createSelectorRadio([['id' => StoreType::TYPE_MODE_REVERSAL_CLASSIFICATION, 'name' => getLabel('lbl_reversed_classification')], ['id' => StoreType::TYPE_MODE_REVERSAL_COLLECTION, 'name' => getLabel('lbl_reversed_collection')]], 'reversal_mode', (bitHasMode($num_type_mode, StoreType::TYPE_MODE_REVERSAL_COLLECTION) ? StoreType::TYPE_MODE_REVERSAL_COLLECTION : StoreType::TYPE_MODE_REVERSAL_CLASSIFICATION)).'</span>
								</li>
								<li>
									<label>'.getLabel('lbl_reversed_classification_reference').'</label>
									<span>'.cms_general::createSelectorRadio([['id' => '', 'name' => getLabel('lbl_none')], ['id' => 'type', 'name' => StoreType::getTypeClassName(StoreType::TYPE_CLASS_TYPE)], ['id' => 'classification', 'name' => StoreType::getTypeClassName(StoreType::TYPE_CLASS_CLASSIFICATION)]], 'reversal_reference_class', $reference).'</span>
								</li>
								<li>
									<label></label>
									<span>'
										.'<select name="reversal_ref_type_id[type]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_TYPE], $reversal_ref_type_id)).'</select>'
										.'<select name="reversal_ref_type_id[classification]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_CLASSIFICATION], $reversal_ref_type_id)).'</select>
									</span>
								</li>
								<li>
									<label>'.getLabel('lbl_reversed_collection_resource_path').'</label>
									<span>'.cms_general::createSelectorRadio([['id' => 0, 'name' => getLabel('lbl_no')], ['id' => 1, 'name' => getLabel('lbl_yes')]], 'reversal_resource_path', $has_reversal_resource_path).'</span>
								</li>
							</ul></fieldset>
						</div>
					</div>';
				}
				
				if ($num_type_class == StoreType::TYPE_CLASS_TYPE) {
					
					$return .= '<div>
						<div class="options">
						
							<fieldset><ul>
								<li>
									<label>'.getLabel('lbl_object_subs').'</label>
									<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
								</li>
								<li><label></label>
									<div class="object-sub-details">';
										$arr_sorter = [];
										$arr_sorter[] = ['source' => true, 'value' => $this->createTypeObjectSubDetails($type_id)];
										
										foreach (($arr_type_set['object_sub_details'] ?: [[]]) as $arr_object_sub) {
											$arr_sorter[] = ['value' => $this->createTypeObjectSubDetails($type_id, ($arr_object_sub['object_sub_details'] ?: []), ($arr_object_sub['object_sub_descriptions'] ?: []))];
										}
										
										$return .= cms_general::createSorter($arr_sorter, false, false, ['diverse' => true]);
									$return .= '</div>
								</li>
							</ul></fieldset>
							
						</div>
					</div>';
				}
				
			$return .= '</div>
		</div>';

		$return .= '<menu class="options">'
			.'<input type="submit" value="'.getLabel('lbl_save').' '.$str_class.'"'.($is_disabled ? ' disabled="disabled"' : '').' />'
			.'<input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
			
		$this->validate = ['name' => 'required'];
		
		return $return;
	}

	private function createTypeObjectSubDetails($type_id = false, $arr_object_sub_details = [], $arr_object_sub_descriptions = []) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_object_sub_details = StoreType::getTypeObjectSubsDetails($type_id);
	
		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$object_sub_details_date_type = ($arr_object_sub_details['object_sub_details_id'] && !$arr_object_sub_details['object_sub_details_has_date'] ? 'none' : ($arr_object_sub_details['object_sub_details_is_date_period'] ? 'period' : 'date'));
		$object_sub_details_location_type = ($arr_object_sub_details['object_sub_details_id'] && !$arr_object_sub_details['object_sub_details_has_location'] ? 'none' : 'default');
		
		$date_usage = ($arr_object_sub_details['object_sub_details_date_use_object_sub_details_id'] ? 'object_sub_details' : ($arr_object_sub_details['object_sub_details_date_start_use_object_sub_description_id'] ? 'object_sub_description' : ($arr_object_sub_details['object_sub_details_date_start_use_object_description_id'] ? 'object_description' : '')));
		$location_usage = ($arr_object_sub_details['object_sub_details_location_use_object_id'] ? 'object' : ($arr_object_sub_details['object_sub_details_location_use_object_description_id'] ? 'object_description' : ($arr_object_sub_details['object_sub_details_location_use_object_sub_details_id'] ? 'object_sub_details' : ($arr_object_sub_details['object_sub_details_location_use_object_sub_description_id'] ? 'object_sub_description' : ''))));
		$arr_date_object_sub_details = $arr_location_object_sub_details = [];
		
		foreach ($arr_type_object_sub_details as $object_sub_details_id => $value) {
			
			if ($value['object_sub_details_location_ref_type_id'] == $arr_object_sub_details['object_sub_details_location_ref_type_id'] && $value['object_sub_details_location_ref_type_id_locked'] && $value['object_sub_details_is_single'] && $object_sub_details_id != $arr_object_sub_details['object_sub_details_id']) {
				$arr_location_object_sub_details[] = $value;
			}
			if ($object_sub_details_id != $arr_object_sub_details['object_sub_details_id'] && (int)$value['object_sub_details_is_date_period'] == (int)$arr_object_sub_details['object_sub_details_is_date_period'] && $value['object_sub_details_is_single']) {
				$arr_date_object_sub_details[] = $value;
			}
		}
		
		$arr_date_object_sub_descriptions = $arr_location_object_sub_descriptions = [];
		
		foreach ($arr_object_sub_descriptions as $object_sub_description_id => $value) {
			
			if ($value['object_sub_description_ref_type_id'] == $arr_object_sub_details['object_sub_details_location_ref_type_id'] && !$value['object_sub_description_use_object_description_id']) {
				$arr_location_object_sub_descriptions[] = $value;
			}
			if ($value['object_sub_description_value_type_base'] == 'date') {
				$arr_date_object_sub_descriptions[] = $value;
			}
		}
		
		$arr_date_object_descriptions = $arr_location_object_descriptions = [];
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $value) {
			
			if ($value['object_description_ref_type_id'] == $arr_object_sub_details['object_sub_details_location_ref_type_id']) {
				$arr_location_object_descriptions[] = $value;
			}
			if ($value['object_description_value_type_base'] == 'date') {
				$arr_date_object_descriptions[] = $value;
			}
		}
		
		$arr_types = StoreType::getTypes();
		
		$arr_types_classifications = [StoreType::TYPE_CLASS_TYPE => [], StoreType::TYPE_CLASS_CLASSIFICATION => [], StoreType::TYPE_CLASS_REVERSAL => []];

		foreach ($arr_types as $cur_type_id => $arr_cur_type) {
			
			$arr_types_classifications[$arr_cur_type['class']][$cur_type_id] = $arr_cur_type;
		}
		
		$arr_mutable_references = [];
		
		foreach ([StoreType::TYPE_CLASS_TYPE, StoreType::TYPE_CLASS_CLASSIFICATION] as $num_cur_type_class) {
			
			$str_cur_class_name = StoreType::getTypeClassName($num_cur_type_class);
			
			foreach ($arr_types_classifications[$num_cur_type_class] as $cur_type_id => $arr_cur_type) {
				
				$arr_cur_type['name'] = $str_cur_class_name.cms_general::OPTION_GROUP_SEPARATOR.$arr_cur_type['name'];
				$arr_mutable_references[$cur_type_id] = $arr_cur_type;
			}
		}
		
		$arr_object_sub_details_references = [];
		
		foreach ([StoreType::TYPE_CLASS_TYPE, StoreType::TYPE_CLASS_REVERSAL] as $num_cur_type_class) {
			
			$str_cur_class_name = StoreType::getTypeClassName($num_cur_type_class);
			
			foreach ($arr_types_classifications[$num_cur_type_class] as $cur_type_id => $arr_cur_type) {
				
				$arr_cur_type['name'] = $str_cur_class_name.cms_general::OPTION_GROUP_SEPARATOR.$arr_cur_type['name'];
				$arr_object_sub_details_references[$cur_type_id] = $arr_cur_type;
			}
		}
		
		$arr_value_usage = StoreType::getValueUsage();
		$arr_usage_date_value = $arr_value_usage;
		unset($arr_usage_date_value['object']);
		$arr_usage_location_reference = $arr_value_usage;
		
		$arr_date_options = StoreType::getDateOptions();
		$arr_date_options[] = ['id' => 'source', 'name' => getLabel('lbl_source')];
		$arr_location_options = StoreType::getLocationOptions();
		
		$object_sub_details_date_setting = 'point';
		$object_sub_details_location_setting = 'reference';
		
		if ($type_id) {

			$object_sub_details_date_setting = ($arr_date_options[$arr_object_sub_details['object_sub_details_date_setting']]['id'] ?? $object_sub_details_date_setting);
			if ($date_usage) {
				$object_sub_details_date_setting = 'source';
			}
			
			$object_sub_details_location_setting = ($arr_location_options[$arr_object_sub_details['object_sub_details_location_setting']]['id'] ?? $object_sub_details_location_setting);
		}

		$return = '<fieldset data-group_iterator="'.$unique.'">
			<legend><span class="handle"><span class="icon">'.getIcon('updown').'</span></span><span>'.getLabel('lbl_object_sub').'</span></legend>
			<ul>
				<li>
					<label>'.getLabel('lbl_name').'</label><input type="text" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_name]" value="'.strEscapeHTML($arr_object_sub_details['object_sub_details_name']).'" />'.($arr_object_sub_details['object_sub_details_id'] ? '<input type="hidden" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_id]" value="'.$arr_object_sub_details['object_sub_details_id'].'" />' : '').'
				</li>
				<li>
					<label>'.getLabel('lbl_options').'</label>
					<div>'
						.'<label><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_is_single]" value="1"'.($arr_object_sub_details['object_sub_details_is_single'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_single').'</span></label>'
						.'<label><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_is_required]" value="1"'.($arr_object_sub_details['object_sub_details_is_required'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_required').'</span></label>'
					.'</div>
				</li>'
				.($this->do_show_user_settings ?
					'<li>
						<label></label>
						<div>'
							.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_clearance_edit]" title="'.getLabel('inf_object_sub_details_clearance_edit').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_details['object_sub_details_clearance_edit'], true, 'label').'</select>'
							.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_clearance_view]" title="'.getLabel('inf_object_sub_details_clearance_view').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_details['object_sub_details_clearance_view'], true, 'label').'</select>'
						.'</div>
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
									.cms_general::createSelectorRadio([['id' => 'date', 'name' => getLabel('lbl_single')], ['id' => 'period', 'name' => getLabel('lbl_period')], ['id' => 'none', 'name' => getLabel('lbl_none')]], 'object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_type]', $object_sub_details_date_type)
								.'</div>
							</li><li>
								<label>'.getLabel('lbl_default').'</label>								
								<div>'
									.cms_general::createSelectorRadio($arr_date_options, 'object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_setting]', $object_sub_details_date_setting)
								.'</div>
							</li><li>
								<label>'.getLabel('lbl_source').'</label>
								<div>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_usage]" title="'.getLabel('inf_date_source').'">'.cms_general::createDropdown($arr_usage_date_value, $date_usage, true).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_use_object_sub_details_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_sub_details, $arr_object_sub_details['object_sub_details_date_use_object_sub_details_id'], false, 'object_sub_details_name', 'object_sub_details_id')).'</select>'
									.'<label>'.getLabel('lbl_start').'</label>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_start_use_object_sub_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_sub_descriptions, $arr_object_sub_details['object_sub_details_date_start_use_object_sub_description_id'], false, 'object_sub_description_name', 'object_sub_description_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_start_use_object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_descriptions, $arr_object_sub_details['object_sub_details_date_start_use_object_description_id'], false, 'object_description_name', 'object_description_id')).'</select>'
									.'<label>'.getLabel('lbl_end').'</label>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_end_use_object_sub_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_sub_descriptions, $arr_object_sub_details['object_sub_details_date_end_use_object_sub_description_id'], false, 'object_sub_description_name', 'object_sub_description_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_end_use_object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_object_descriptions, $arr_object_sub_details['object_sub_details_date_end_use_object_description_id'], false, 'object_description_name', 'object_description_id')).'</select>'
								.'</div>
							</li><li>
								<label>'.getLabel('lbl_reference').'</label>
								<div>'
									.'<select id="y:data_model:selector_object_sub_details-0" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_setting_type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_sub_details_references, $arr_object_sub_details['object_sub_details_date_setting_type_id'], true)).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_date_setting_object_sub_details_id]">'.Labels::parseTextVariables(cms_general::createDropdown(StoreType::getTypeObjectSubsDetails($arr_object_sub_details['object_sub_details_date_setting_type_id']), $arr_object_sub_details['object_sub_details_date_setting_object_sub_details_id'], true, 'object_sub_details_name', 'object_sub_details_id')).'</select>'
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
							</li><li>
								<label>'.getLabel('lbl_default').'</label>								
								<div>'
									.cms_general::createSelectorRadio($arr_location_options, 'object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_setting]', $object_sub_details_location_setting)
								.'</div>
							</li><li>
								<label>'.getLabel('lbl_reference').'</label>
								<div>'
									.'<select id="y:data_model:selector_object_sub_details-0" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_sub_details_references, $arr_object_sub_details['object_sub_details_location_ref_type_id'], true)).'</select>'
									.'<input type="checkbox" value="1" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_type_id_locked]" title="'.getLabel('inf_location_reference_lock').'"'.($arr_object_sub_details['object_sub_details_location_ref_type_id_locked'] ? ' checked="checked"' : '').' />'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_object_sub_details_id]">'.Labels::parseTextVariables(cms_general::createDropdown(StoreType::getTypeObjectSubsDetails($arr_object_sub_details['object_sub_details_location_ref_type_id']), $arr_object_sub_details['object_sub_details_location_ref_object_sub_details_id'], true, 'object_sub_details_name', 'object_sub_details_id')).'</select>'
									.'<input type="checkbox" value="1" name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_ref_object_sub_details_id_locked]" title="'.getLabel('inf_location_reference_lock').'"'.($arr_object_sub_details['object_sub_details_location_ref_object_sub_details_id_locked'] ? ' checked="checked"' : '').' />'
								.'</div>
							</li><li>
								<label>'.getLabel('lbl_source').'</label>
								<div>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_usage]" title="'.getLabel('inf_location_source').'">'.cms_general::createDropdown($arr_usage_location_reference, $location_usage, true).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_use_object_sub_details_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_location_object_sub_details, $arr_object_sub_details['object_sub_details_location_use_object_sub_details_id'], false, 'object_sub_details_name', 'object_sub_details_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_use_object_sub_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_location_object_sub_descriptions, $arr_object_sub_details['object_sub_details_location_use_object_sub_description_id'], false, 'object_sub_description_name', 'object_sub_description_id')).'</select>'
									.'<select name="object_sub_details['.$unique.'][object_sub_details][object_sub_details_location_use_object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_location_object_descriptions, $arr_object_sub_details['object_sub_details_location_use_object_description_id'], false, 'object_description_name', 'object_description_id')).'</select>'
								.'</div>
							</li>
						</ul></fieldset>
								
					</div>
				</div>
				
				<div>
					<div class="options">
						
						<fieldset><ul><legend>'.getLabel('lbl_descriptions').'</legend>
							<li>
								<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
							</li>
							<li>
								<div class="object-sub-descriptions">';
									
									$arr_value_types = StoreType::getValueTypesBase();
									Settings::get('hook_data_model_value_types', false, [&$arr_value_types, 'object_sub_description']);
																		
									if (!$arr_object_sub_descriptions) {
										$arr_object_sub_descriptions[] = [];
									}
									array_unshift($arr_object_sub_descriptions, []); // Empty run for sorter source
								
									foreach ($arr_object_sub_descriptions as $key => $arr_object_sub_description) {
										
										$unique2 = uniqid(cms_general::NAME_GROUP_ITERATOR);
										
										$arr_referencing_type_ids = [];
										$arr_sorter_reference_mutable = [];
										
										if (is_array($arr_object_sub_description['object_sub_description_ref_type_id'])) {
											$arr_referencing_type_ids += $arr_object_sub_description['object_sub_description_ref_type_id'];
										}
										if (count($arr_referencing_type_ids) < StoreType::$num_mutable_references) {
											$arr_referencing_type_ids[] = '';
										}

										foreach ($arr_referencing_type_ids as $referencing_type_id) {
											
											$arr_sorter_reference_mutable[] = ['value' => [
												'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_ref_type_id][reference_mutable][]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_mutable_references, $referencing_type_id, true)).'</select>'
											]];
										}												
										
										$str_html_reference_mutable = cms_general::createSorter($arr_sorter_reference_mutable, 'append', false, ['auto_add' => true, 'auto_clean' => true, 'limit' => StoreType::$num_mutable_references]);
										
										$has_default_value = (bool)$arr_object_sub_description['object_sub_description_value_type_settings']['default']['value'];
										
										$arr_sorter[] = ['source' => ($key == 0 ? true : false), 'attr' => ['data-group_iterator' => $unique2], 'value' => [
											'<input type="text" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_name]" value="'.strEscapeHTML($arr_object_sub_description['object_sub_description_name']).'" />'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_value_type_base]" id="y:data_model:selector_value_type-0">'.cms_general::createDropdown($arr_value_types, $arr_object_sub_description['object_sub_description_value_type_base']).'</select>'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_ref_type_id][type]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_TYPE], $arr_object_sub_description['object_sub_description_ref_type_id'])).'</select>'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_ref_type_id][classification]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_CLASSIFICATION], $arr_object_sub_description['object_sub_description_ref_type_id'])).'</select>'
											.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_ref_type_id][reversal]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_classifications[StoreType::TYPE_CLASS_REVERSAL], $arr_object_sub_description['object_sub_description_ref_type_id'])).'</select>'
											.'<div class="reference_mutable input">'.$str_html_reference_mutable.'</div>'
											.'<input type="hidden" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_id]" value="'.$arr_object_sub_description['object_sub_description_id'].'" />'
											,'<fieldset><ul>
												<li>'
													.'<div>'
														.'<label title="'.getLabel('inf_object_description_is_required').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_is_required]" value="1"'.($arr_object_sub_description['object_sub_description_is_required'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_required').'</span></label>'
														.'<label title="'.getLabel('inf_object_description_has_default_value').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_has_default_value]" value="1"'.($has_default_value ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_default').'</span></label>'
														.'<span class="split"></span>'
														.'<label title="'.getLabel('inf_object_description_in_name').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_in_name]" value="1"'.($arr_object_sub_description['object_sub_description_in_name'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_name').'</span></label>'
														.'<label title="'.getLabel('inf_object_description_in_search').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_in_search]" value="1"'.($arr_object_sub_description['object_sub_description_in_search'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_quick_search').'</span></label>'
														.'<label title="'.getLabel('inf_object_description_in_overview').'"><input type="checkbox" name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_in_overview]" value="1"'.(!$arr_object_sub_description || $arr_object_sub_description['object_sub_description_in_overview'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_overview').'</span></label>'
													.'</div>'
												.'</li>'
												.'<li class="object-description-options">'
													.$this->createTypeObjectDescriptionValueTypeOptions($type_id, 'object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.']', $arr_object_sub_description['object_sub_description_value_type_base'], $arr_object_sub_description['object_sub_description_ref_type_id'], false, $has_default_value, $arr_object_sub_description['object_sub_description_in_name'], $arr_object_sub_description)
												.'</li>'
												.($this->do_show_user_settings ? '<li>'
													.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_clearance_edit]" title="'.getLabel('inf_object_description_clearance_edit').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_description['object_sub_description_clearance_edit'], true, 'label').'</select>'
													.'<select name="object_sub_details['.$unique.'][object_sub_descriptions]['.$unique2.'][object_sub_description_clearance_view]" title="'.getLabel('inf_object_description_clearance_view').'">'.cms_general::createDropdown(cms_nodegoat_details::getClearanceLevels(), $arr_object_sub_description['object_sub_description_clearance_view'], true, 'label').'</select>'
												.'</li>' : '').'
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
	
	private function createTypeObjectDescriptionValueTypeOptions($type_id, $str_name, $value_type, $ref_type_id, $has_multi, $has_default_value, $in_name, $arr_object_description = []) {
		
		if (strpos($str_name, 'object_sub_details') === 0) {
			$arr_value_type_settings = current(arrValuesRecursive('object_sub_description_value_type_settings', $arr_object_description));
			$str_name_settings = $str_name.'[object_sub_description_value_type_settings]';
		} else {
			$arr_value_type_settings = current(arrValuesRecursive('object_description_value_type_settings', $arr_object_description));
			$str_name_settings = $str_name.'[object_description_value_type_settings]';
		}
		
		$html_option = false;

		switch ($value_type) {
			case 'external':
				
				$arr_external_resources = ResourceExternal::getReferenceTypes();
				foreach (StoreResourceExternal::getResources() as $id => $arr_resource) {
					$arr_external_resources[] = ['id' => $id, 'name' => $arr_resource['name']];
				};
				
				$html_option = '<li>
					<label><span class="input">'.getLabel('lbl_source').'</span></label>
					<div><select name="'.$str_name_settings.'[id]">'.cms_general::createDropdown($arr_external_resources, ($arr_value_type_settings ? $arr_value_type_settings['id'] : '')).'</select></div>
				</li>';
				
				break;
			case 'module':
				
				$arr_modules = EnucleateValueTypeModule::getModules();
				
				$html_option = '<li>
					<label><span class="input">'.getLabel('lbl_module').'</span></label>
					<div><select name="'.$str_name_settings.'[type]">'.cms_general::createDropdown($arr_modules, ($arr_value_type_settings ? $arr_value_type_settings['type'] : '')).'</select></div>
				</li>';
				
				break;
			case 'text_layout':
			case 'text_tags':
				
				$html_option = '';
				
				if ($value_type == 'text_tags') {
					
					$html_option .= '<li>
						<label><span>'.getLabel('lbl_marginalia').'</span></label>
						<div><input type="checkbox" name="'.$str_name_settings.'[marginalia]" value="1" title="'.getLabel('inf_marginalia_enable').'"'.($arr_value_type_settings['marginalia'] ? ' checked="checked"' : '').' /></div>
					</li>';
				}
				
				$html_option .= '<li>
					<label><span>'.getLabel('lbl_html').'</span></label>
					<div><input type="checkbox" name="'.$str_name_settings.'[html]" value="1" title="'.getLabel('inf_html_enable').'"'.($arr_value_type_settings['html'] ? ' checked="checked"' : '').' /></div>
				</li>';

				break;
			case 'media':
			case 'media_external':
								
				$html_option = '<li>
					<label><span>'.getLabel('lbl_display').'</span></label>
					<div>'.cms_general::createSelectorRadio([['id' => '', 'name' => getLabel('lbl_media')], ['id' => 'url', 'name' => getLabel('lbl_url')]], $str_name_settings.'[display]', $arr_value_type_settings['display']).'</div>
				</li>';

				break;
			case 'object_description':
			
				$arr_type_set = StoreType::getTypeSet($type_id);
				$arr_object_descriptions = [];
				
				foreach ($arr_type_set['object_descriptions'] as $collect_object_description_id => $arr_collect_object_description) {
			
					if (!$arr_collect_object_description['object_description_ref_type_id']) {
						continue;
					}
					
					$arr_object_descriptions[] = $arr_collect_object_description;
				}
				
				$use_object_description_id = current(arrValuesRecursive('object_sub_description_use_object_description_id', $arr_object_description));
			
				$html_option = '<li>
					<label><span class="input">'.getLabel('lbl_object_description').'</span></label>
					<div><select name="'.$str_name.'[object_sub_description_use_object_description_id]" title="'.getLabel('inf_save_and_edit_field').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_descriptions, $use_object_description_id, false, 'object_description_name', 'object_description_id')).'</select></div>
				</li>';
				
				break;
			case 'reversal':
				
				$has_multi = true;
				$has_default_value = false;
				
				break;
		}
		
		$html_multi = false;
		
		if ($has_multi) {
			
			$str_separator = ($arr_value_type_settings['separator'] ?: '');
			
			$html_multi = '<li>
				<label title="'.getLabel('inf_object_description_multi_separator').'"><span>'.getLabel('lbl_separator').'</label>
				<div><input type="text" name="'.$str_name_settings.'[separator]" value="'.$str_separator.'" placeholder="'.getLabel('lbl_default').'" /></div>
			</li>';
		}
		
		$html_default_value = false;
		
		if ($has_default_value) {
				
			$value_value = ($arr_value_type_settings['default']['value'] ?: '');
			$value_reference = false;
			$arr_format_extra = ['has_multi' => $has_multi, 'ref_type_id' => $ref_type_id];
			$str_name_default = $str_name_settings.'[default][value]';
			
			if ($value_type == 'type' || $value_type == 'classification' || $value_type == 'reference_mutable') {
				
				$value_reference = $value_value;
				
				if (!$has_multi) { // Could be switching from multi to non-multi
					$value_reference = (is_array($value_reference) ? current($value_reference) : $value_reference);
				}

				$value_value = ($value_reference ? FilterTypeObjects::getTypeObjectNames($ref_type_id, $value_reference) : []);
				
				if ($has_multi) {
					$value_reference = ($value_reference ?: []);
					$arr_format_extra['list'] = true;
				} else {
					$value_value = $value_value[$value_reference];
				}
			} else {
			
				if ($value_type == 'text' || $value_type == 'text_layout' || $value_type == 'text_tags') {
					$value_type = '';
				}
				
				if ($has_multi) {
					$value_value = ((array)$value_value ?: []);
				} else {
					$value_value = (is_array($arr_values) ? current($value_value) : $value_value);
				}
				
				$value_value = FormatTypeObjects::formatToSQLValue($value_type, $value_value);
			}
			
			$html_default_value = FormatTypeObjects::formatToFormValue($value_type, $value_value, $value_reference, $str_name_default, $arr_value_type_settings, $arr_format_extra);
			
			$html_default_value = '<li>
				<label title="'.getLabel('inf_object_description_default_value').'"><span>'.getLabel('lbl_default').'</label>
				<div>'.$html_default_value.'</div>
			</li>';
		}
		
		$html_name = false;
		
		if ($in_name) {
			
			if ($has_multi) {
				
				$str_separator = ($arr_value_type_settings['name']['separator'] ?: '');
				
				$html_name = '<li>
					<label title="'.getLabel('inf_object_description_name_separator').'"><span>'.getLabel('lbl_name').' '.getLabel('lbl_separator').'</label>
					<div><input type="text" name="'.$str_name_settings.'[name][separator]" value="'.$str_separator.'" placeholder="'.getLabel('lbl_default').'" /></div>
				</li>';
			}
		}
		
		if (!$html_option  && !$html_multi && !$html_default_value && !$html_name) {
			return '';
		}
		
		$html_base = $html_usage = false;
		
		if ($html_option  || $html_multi || $html_default_value) {
			
			$html_base = '<ul>
				'.($html_option ?: '').'
				'.($html_multi ?: '').'
				'.($html_default_value ?: '').'
			</ul>';
		}
		
		if ($html_name) {
			
			$html_usage = '<ul>
				'.($html_name ?: '').'
			</ul>';
		}
		
		$return = '<div class="entry object-various">
			<fieldset>
				'.($html_base ?: '').'
				'.($html_base && $html_usage ? '<hr/>' : '').'
				'.($html_usage ?: '').'
			</fieldset>
		</div>';
		
		return $return;
	}
	
	public function createTypeCondition($type_id, $arr_condition, $arr_model_conditions) {
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['object' => true, 'object_sub_details_date' => false, 'object_sub_details_location' => false, 'purpose' => 'filter']);
		
		$arr_type_set_flat_separated = [];
		$arr_type_set_flat_separated['object_name']['id'] = $arr_type_set_flat['id'];
		$arr_type_set_flat_separated['object_values'] = [];
		$arr_type_set_flat_separated['object_nodes_object']['id'] = $arr_type_set_flat['id'];
		$arr_type_set_flat_separated['object_nodes_referencing'] = [];

		$arr_condition_actions_separated['object_name'] = ParseTypeFeatures::getSetConditionActions('object_name');
		$arr_condition_actions_separated['object_values'] = ParseTypeFeatures::getSetConditionActions('object_values');
		$arr_condition_actions_separated['object_nodes_object'] = ParseTypeFeatures::getSetConditionActions('object_nodes');
		$arr_condition_actions_separated['object_nodes_referencing'] = ParseTypeFeatures::getSetConditionActions('object_nodes_referencing');
		
		$this->arr_object_analyses = data_analysis::createTypeAnalysesSelection($type_id);
		
		$this->arr_object_descriptions_numbers = [];
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$str_id = 'object_description_'.$object_description_id;

			if ($arr_object_description['object_description_in_name']) {
				
				$arr_type_set_flat_separated['object_name'][$str_id] = $arr_type_set_flat[$str_id];
			}
			
			if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id)) {
				continue;
			}

			$arr_type_set_flat_separated['object_values'][$str_id] = $arr_type_set_flat[$str_id];
			
			if ($arr_object_description['object_description_ref_type_id']) {
					
				$arr_type_set_flat_separated['object_nodes_referencing'][$str_id] = $arr_type_set_flat[$str_id];
			}
			
			if ($arr_object_description['object_description_value_type'] == 'int' || $arr_object_description['object_description_value_type'] == 'numeric') {
				
				$this->arr_object_descriptions_numbers[$str_id] = $arr_type_set_flat[$str_id];
			}
		}
			
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			$has_object_sub_details_clearance = !($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id));
				
			if ($has_object_sub_details_clearance) {
				
				$str_id_object_sub_details = 'object_sub_details_'.$object_sub_details_id.'_id';
				$arr_type_set_flat_separated['object_nodes_object'][$str_id_object_sub_details] = $arr_type_set_flat[$str_id_object_sub_details];
			}
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				$str_id = 'object_sub_description_'.$object_sub_description_id;
				
				if ($arr_object_sub_description['object_sub_description_in_name']) {
				
					$arr_type_set_flat_separated['object_name'][$str_id] = $arr_type_set_flat[$str_id];
				}
				
				if (!$has_object_sub_details_clearance || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
					continue;
				}

				$arr_type_set_flat_separated['object_values'][$str_id] = $arr_type_set_flat[$str_id];
				
				if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
					
					$arr_type_set_flat_separated['object_nodes_referencing'][$str_id] = $arr_type_set_flat[$str_id];
				}
				
				if ($arr_object_sub_description['object_sub_description_value_type'] == 'int' || $arr_object_sub_description['object_sub_description_value_type'] == 'numeric') {
					
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
			$has_object_description_clearance = !(!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id));
			
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
			
			$has_object_sub_details_clearance = !($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id));
			
			if ($has_object_sub_details_clearance) {
					
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
				
				foreach ((array)$arr_condition_settings['object_sub_details'] as $arr_condition_setting) {
					
					$arr_condition_separated['object_nodes_object'][$str_id][] = $arr_condition_setting;
				}
			}
			
			foreach ((array)$arr_condition_settings['object_sub_descriptions'] as $object_sub_description_id => $arr_condition_settings) {
				
				$arr_object_sub_description = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
				$has_object_sub_description_clearance = ($has_object_sub_details_clearance && !($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)));
				
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
				
				$is_node = ($object_or_object_sub_details == 'object_nodes_object' || $object_or_object_sub_details == 'object_nodes_referencing');
				
				foreach ($arr_condition_settings as $arr_condition_setting) {
				
					$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
					
					$return_actions = '<fieldset><ul>';
					foreach ($arr_condition_actions_separated[$object_or_object_sub_details] as $arr_action) {
						$return_actions .= '<li><label>'.$arr_action['name'].'</label><div>'.$this->createConditionAction($type_id, $arr_action['id'], $arr_condition_setting['condition_actions'][$arr_action['id']], ['name' => 'condition['.$unique.'][condition_actions]']).'</div></li>';
					}
					$return_actions .= '</ul></fieldset>';
					
					Labels::setVariable('application', 'Condition');
					
					$arr_sorter[$object_or_object_sub_details][] = ['source' => $is_source, 'value' => [
						'<select name="condition['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set_flat_separate, $id, true)).'</select>'
						.'<input type="hidden" name="condition['.$unique.'][condition_filter]" value="'.($arr_condition_setting['condition_filter'] ? strEscapeHTML(value2JSON($arr_condition_setting['condition_filter'])) : '').'" />'
						.'<button type="button" id="y:data_filter:configure_application_filter-'.$type_id.'" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>'
						.($is_node  ? '<input type="hidden" name="condition['.$unique.'][condition_scope]" value="'.($arr_condition_setting['condition_scope'] ? strEscapeHTML(value2JSON($arr_condition_setting['condition_scope'])) : '').'" />' : '')
						.'<input type="hidden" name="condition['.$unique.'][condition_in_'.$object_or_object_sub_details.']" value="1" />'
						.($is_node ? '<input type="text" name="condition['.$unique.'][condition_label]" title="'.getLabel('inf_condition_label').'" value="'.$arr_condition_setting['condition_label'].'" />' : '')
						, $return_actions
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
									<label>'.getLabel('lbl_condition').'</label>
									<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
								</li>
								<li>
									<label></label>'.cms_general::createSorter($arr_sorter['object_name'], true).'
								</li>
							</ul></fieldset>
							
						</div>
					</div>
					
					<div class="values">
						<div class="options">
						
							<fieldset><ul>
								<li>
									<label>'.getLabel('lbl_condition').'</label>
									<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
								</li>
								<li>
									<label></label>'.cms_general::createSorter($arr_sorter['object_values'], true).'
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
											<label>'.getLabel('lbl_condition').'</label>
											<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
										</li>
										<li>
											<label></label>'.cms_general::createSorter($arr_sorter['object_nodes_object'], true).'
										</li>
									</ul></fieldset>
									
								</div>
							</div>
							
							<div>
								<div class="options">
								
									<fieldset><ul>
										<li>
											<label>'.getLabel('lbl_condition').'</label>
											<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
										</li>
										<li>
											<label></label>'.cms_general::createSorter($arr_sorter['object_nodes_referencing'], true).'
										</li>
									</ul></fieldset>
									
								</div>
							</div>
						</div>
						
					</div>
					
				</div>
				
			</div>
			
			<div class="condition-model-conditions">
			
				<div class="options fieldsets"><div>';
					
					$arr_types = ($arr_project['types'] ? StoreType::getTypes(array_keys($arr_project['types'])) : []);
					$arr_conditions_all = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], false, false, false, $arr_use_project_ids);
					
					$arr_types_classifications = [];
					foreach ($arr_types as $cur_type_id => $arr_type) {
						
						if ($cur_type_id == $type_id || $arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
							continue;
						}
						
						$arr_types_classifications[$arr_type['class']][$cur_type_id] = $arr_type;
					}
					
					foreach ($arr_types_classifications as $type_or_classification => $arr_types) {
							
						$return .= '<fieldset><legend>'.StoreType::getTypeClassName($type_or_classification).'</legend><ul>';
							
							foreach ($arr_types as $cur_type_id => $arr_type) {
								
								if ($arr_conditions_all[$cur_type_id]) {
									$html_select = '<select name="model_conditions['.$cur_type_id.'][condition_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_conditions_all[$cur_type_id], $arr_model_conditions[$cur_type_id]['condition_id'], true, 'label')).'</select>';
								} else {
									$html_select = '';
								}
								
								$return .= '<li>
									<label>'.Labels::parseTextVariables($arr_type['name']).'</label><div>'
										.$html_select
										.'<input type="checkbox" value="1" name="model_conditions['.$cur_type_id.'][condition_use_current]" title="'.getLabel('inf_condition_model_conditions_use_current').'"'.($arr_model_conditions[$cur_type_id]['condition_use_current'] ? ' checked="checked"' : '').' />'
									.'</div>
								</li>';
							}
							
						$return .= '</ul></fieldset>';
						
					}
					
				$return .= '</div></div>
				
			</div>
		
		</div>';
		
		return $return;
	}
	
	private function createConditionAction($type_id, $action, $arr_selected = [], $arr_options = []) {
		
		$arr_condition_actions = ParseTypeFeatures::getSetConditionActions();
		
		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
	
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
					$return .= '<input type="text" name="'.$name.'" value="'.strEscapeHTML($arr_selected['value']).'"'.($info ? ' title="'.strEscapeHTML($info).'"' : '').' />';
					break;
				case 'number':
					$return .= '<input type="number" name="'.$name.'" value="'.strEscapeHTML($arr_selected['number']).'"'.($info ? ' title="'.strEscapeHTML($info).'"' : '').' />';
					break;
				case 'emphasis':
					$return .= cms_general::createSelector([['id' => 'bold', 'name' => getLabel('lbl_text_bold')], ['id' => 'italic', 'name' => getLabel('lbl_text_italic')], ['id' => 'strikethrough', 'name' => getLabel('lbl_text_strikethrough')]], $name, (array)$arr_selected['emphasis']);
					break;
				case 'color':
					$return .= '<input name="'.$name.'" type="text" value="'.$arr_selected['color'].'" class="colorpicker"'.($info ? ' title="'.strEscapeHTML($info).'"' : '').' />';
					break;
				case 'opacity':
					$return .= '<input type="number" name="'.$name.'" step="0.01" min="0" max="1" value="'.strEscapeHTML($arr_selected['opacity']).'" title="'.getLabel('lbl_opacity').'" />';
					break;
				case 'check':
					$return .= '<input type="checkbox" name="'.$name.'" value="1"'.($arr_selected['check'] ? ' checked="checked"' : '').($info ? ' title="'.strEscapeHTML($info).'"' : '').' />';
					break;
				case 'regex':
					
					$html = cms_general::createRegularExpressionReplaceEditor($arr_selected['regex'], $name, false, ['info' => $info]);

					if (!$arr_selected['regex']) {
						
						$return .= '<div class="hide-edit hide">'
							.$html
						.'</div>'
						.'<input type="button" class="data neutral" value="'.getLabel('lbl_regular_expression_abbr').'" />';
					} else {
						$return .= $html;
					}
					break;
				case 'number_use_object_description_id':
					$return .= '<select name="'.$name.'"'.($info ? ' title="'.strEscapeHTML($info).'"' : '').'>'.Labels::parseTextVariables(cms_general::createDropdown($this->arr_object_descriptions_numbers, $arr_selected['number_use_object_description_id'], true)).'</select>';
					break;
				case 'number_use_object_analysis_id':
					$return .= '<select name="'.$name.'"'.($info ? ' title="'.strEscapeHTML($info).'"' : '').'>'.Labels::parseTextVariables(cms_general::createDropdown($this->arr_object_analyses, $arr_selected['number_use_object_analysis_id'], true, 'label')).'</select>';
					break;
				case 'image':
					
					$has_file = ($arr_selected['image'] && isPath(DIR_HOME_CUSTOM_PROJECT_WORKSPACE.$arr_selected['image']));
					
					if ($has_file) {
						$return .= '<input type="hidden" name="'.$name.'[url]" value="'.strEscapeHTML($arr_selected['image']).'" />'
						.'<img src="/'.strEscapeHTML(DIR_CUSTOM_PROJECT_WORKSPACE.$arr_selected['image']).'" />'
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
	
	private function createSelectCondition($type_id, $is_store = false) {
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_options = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);		
		$command_id = 'x:custom_projects:condition_storage-'.(int)$type_id;

		$str_html = custom_projects::createStorageSelect('condition', $is_store, $arr_options, $command_id, getLabel('lbl_conditions'));

		return $str_html;
	}
	
	public static function createTypeNetwork($from_type_id, $to_type_id, $num_steps, $arr_options = []) {
		
		// $arr_options = array('references' => TraceTypesNetwork::RUN_MODE, 'descriptions' => false/'flat'/'concept'/'value'/'date', 'functions' => ['filter' => bool, 'collapse' => bool], 'network' => ['dynamic' => bool, 'object_sub_locations' => bool], 'name' => string, 'source_path' => string);
		
		$arr_options['name'] = ($arr_options['name'] ?: 'type_network');
		$arr_options['references'] = ($arr_options['references'] ?: TraceTypesNetwork::RUN_MODE_BOTH);
		$arr_options['compact'] = keyIsUncontested('compact', $arr_options);
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_ids = array_keys($arr_project['types']);
		
		$arr_types = StoreType::getTypes($arr_type_ids);

		$trace = new TraceTypesNetwork($arr_type_ids, $arr_options['network']['dynamic'], $arr_options['network']['object_sub_locations']);
		$trace->run($from_type_id, $to_type_id, ($num_steps === false ? 1 : $num_steps), $arr_options['references']);

		$arr_type_network_paths = $trace->getTypeNetworkPaths();

		$func_connection = function($arr_network_connections, $type_id, $source_path) use (&$func_connection, $arr_project, $trace, $num_steps, $arr_types, $arr_options) {
							
			$arr_type_set = StoreType::getTypeSet($type_id);
			$arr_type_set_date_flat = StoreType::getTypeSetFlat($type_id, ['object_sub_details_date' => true, 'object_sub_details_location' => false]);
			$arr_source_type_set_date_flat = [];
			
			$do_compact = $arr_options['compact'];
			$str_descriptions_type = ($arr_options['descriptions'] ?: '');
			
			if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_FLAT) {
				$arr_type_set_flat = StoreType::getTypeSetFlat($type_id);
			} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DISPLAY) {
				$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['purpose' => 'select']);
			} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_CONCEPT) {
				$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['object_sub_details_date' => false, 'object_sub_details_location' => false]);
			} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE) {
				$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['object_sub_details_date' => true, 'object_sub_details_location' => false]);
			}
			
			if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE) {
				unset($arr_type_set['name'], $arr_type_set_flat['name']);
			}
						
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$str_id = 'object_description_'.$object_description_id;
				
				if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id) || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, $object_description_id)) {
					
					unset($arr_type_set['object_descriptions'][$object_description_id]);
					
					if ($str_descriptions_type) {
						unset($arr_type_set_flat[$str_id]);
					}
				} else {
					
					if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_FLAT) {
						
						if ($arr_object_description['object_description_ref_type_id'] || $arr_object_description['object_description_value_type'] == 'text_tags') {
							
							arrInsert($arr_type_set_flat, $str_id, [$str_id.'_id' => ['id' => $str_id.'_id', 'name' => $arr_type_set_flat[$str_id]['name'].' - Object ID']]);
							
							if ($arr_object_description['object_description_value_type'] == 'text_tags') {
								arrInsert($arr_type_set_flat, $str_id, [$str_id.'_text' => ['id' => $str_id.'_text', 'name' => $arr_type_set_flat[$str_id]['name'].' - Text']]);
							}
						}
					} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE) {
						
						if ($arr_object_description['object_description_value_type'] != 'date') {
							unset($arr_type_set['object_descriptions'][$object_description_id], $arr_type_set_flat[$str_id]);
						}
					}
					
					if ($arr_object_description['object_description_value_type'] == 'date') {
						$arr_source_type_set_date_flat[$str_id] = $arr_type_set_date_flat[$str_id];
					}
				}
			}
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_';
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
					
					unset($arr_type_set['object_sub_details'][$object_sub_details_id]);
					
					if ($str_descriptions_type) {
						
						foreach ($arr_type_set_flat as $str_id_check => $value) {
							
							if (strpos($str_id_check, $str_id) === false) {
								continue;
							}
								
							unset($arr_type_set_flat[$str_id_check]);
						}
						
						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
							$str_id = 'object_sub_description_'.$object_sub_description_id;
							
							unset($arr_type_set_flat[$str_id]);
						}
					}
					
					continue;
				}
				
				if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_FLAT || $str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_CONCEPT) {
					
					$arr_type_set_flat[$str_id.'id']['name'] = $arr_type_set_flat[$str_id.'id']['name'].' Sub-Object ID';
					
					if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_FLAT) {
						
						$str_id = $str_id.'location_ref_type_id';
						
						if ($arr_type_set_flat[$str_id]) {
							arrInsert($arr_type_set_flat, $str_id, [$str_id.'_id' => ['id' => $str_id.'_id', 'name' => $arr_type_set_flat[$str_id]['name'].' - Object ID']]);
						}
					}
				} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DISPLAY) {
					
					unset($arr_type_set_flat[$str_id.'id']);
				} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE) {
					
					unset($arr_type_set_flat[$str_id.'id'], $arr_type_set_flat[$str_id.'date_chronology']);
				}
				
				if ($arr_type_set_date_flat[$str_id.'date_start']) {
					$arr_source_type_set_date_flat[$str_id.'date_start'] = $arr_type_set_date_flat[$str_id.'date_start'];
				}
				if ($arr_type_set_date_flat[$str_id.'date_end']) {
					$arr_source_type_set_date_flat[$str_id.'date_end'] = $arr_type_set_date_flat[$str_id.'date_end'];
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$str_id = 'object_sub_description_'.$object_sub_description_id;
					
					if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {

						unset($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]);
						
						if ($str_descriptions_type) {
							unset($arr_type_set_flat[$str_id]);
						}
					} else {
						
						if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_FLAT) {
							
							if ($arr_object_sub_description['object_sub_description_ref_type_id'] || $arr_object_sub_description['object_sub_description_value_type'] == 'text_tags') {
								
								arrInsert($arr_type_set_flat, $str_id, [$str_id.'_id' => ['id' => $str_id.'_id', 'name' => $arr_type_set_flat[$str_id]['name'].' - Object ID']]);
								
								if ($arr_object_sub_description['object_sub_description_value_type'] == 'text_tags') {
									arrInsert($arr_type_set_flat, $str_id, [$str_id.'_text' => ['id' => $str_id.'_text', 'name' => $arr_type_set_flat[$str_id]['name'].' - Text']]);
								}
							}
						} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE) {
						
							if ($arr_object_sub_description['object_sub_description_value_type'] != 'date') {
								unset($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id], $arr_type_set_flat[$str_id]);
							}
						}
						
						if ($arr_object_sub_description['object_sub_description_value_type'] == 'date') {
							$arr_source_type_set_date_flat[$str_id] = $arr_type_set_date_flat[$str_id];
						}
					}
				}
			}
			
			// Network
			
			$arr_type_set_mutable_referencing = [];
			$arr_return_paths = [];
			$arr_target_type_set_date_flat = [];
			
			$arr_in_out_type_object_connections = [];
			$arr_in_out_type_object_connections['in'] = (array)$arr_network_connections['in'];
			$arr_in_out_type_object_connections['out'] = (array)$arr_network_connections['out'];
						
			foreach ($arr_in_out_type_object_connections as $in_out => $arr_type_object_connections) {
				
				foreach ($arr_type_object_connections as $ref_type_id => $arr_object_connections) {
					
					$use_type_id = ($in_out == 'out' ? $type_id : $ref_type_id);						
					$arr_use_type_set = StoreType::getTypeSet($use_type_id); // Use the type set matching the reference direction (in or out)
				
					foreach ((array)$arr_object_connections['object_sub_details'] as $object_sub_details_id => $arr_object_sub_descriptions) {
						
						$arr_object_sub_details = $arr_use_type_set['object_sub_details'][$object_sub_details_id];
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_use_type_set, false, $object_sub_details_id)) {
							continue;
						}
												
						foreach ($arr_object_sub_descriptions as $object_sub_description_id => $arr_connection) {
							
							$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];

							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_use_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
							
							if ($arr_connection['use_object_description_id']) {
								//continue;
							}

							$path = implode('-', $arr_connection['path']);
							if ($num_steps === false && $source_path) {
								$path = $source_path.'-'.$path;
							}
							
							$str_name_path = $arr_options['name'].'[paths]['.$path.']';
							
							if ($object_sub_description_id == 'object_sub_location') {
								
								if ($arr_connection['mutable']) {
									
									$arr_checked = $arr_options['value']['paths'][$path]['object_sub_locations'][$object_sub_details_id][$in_out][$ref_type_id];
									$str_name_path_connection = ($do_compact ? 'object_sub_locations-'.$object_sub_details_id.'-'.$in_out.'-'.$ref_type_id : '[object_sub_locations]['.$object_sub_details_id.']['.$in_out.']['.$ref_type_id.']');
									
									if ($in_out == 'out') {
										$arr_type_set_mutable_referencing['object_sub_locations'][$object_sub_details_id][$ref_type_id] = $ref_type_id;
									}
								} else {
									
									$arr_checked = $arr_options['value']['paths'][$path]['object_sub_locations'][$object_sub_details_id];
									$arr_checked = (is_array($arr_checked) ? $arr_checked[$in_out] : $arr_checked);
									$str_name_path_connection = ($do_compact ? 'object_sub_locations-'.$object_sub_details_id.'-'.$in_out : '[object_sub_locations]['.$object_sub_details_id.']['.$in_out.']');
								}
								$str_name = getLabel('lbl_location');
							} else {
								
								if ($arr_connection['mutable']) {
									
									$arr_checked = $arr_options['value']['paths'][$path]['object_sub_descriptions'][$object_sub_description_id][$in_out][$ref_type_id];
									$str_name_path_connection = ($do_compact ? 'object_sub_descriptions-'.$object_sub_description_id.'-'.$in_out.'-'.$ref_type_id : '[object_sub_descriptions]['.$object_sub_description_id.']['.$in_out.']['.$ref_type_id.']');
									
									if ($in_out == 'out') {
										$arr_type_set_mutable_referencing['object_sub_descriptions'][$object_sub_description_id][$ref_type_id] = $ref_type_id;
									}
								} else {
									
									$arr_checked = $arr_options['value']['paths'][$path]['object_sub_descriptions'][$object_sub_description_id];
									$arr_checked = (is_array($arr_checked) ? $arr_checked[$in_out] : $arr_checked); // Legacy: not an in_out array
									$str_name_path_connection = ($do_compact ? 'object_sub_descriptions-'.$object_sub_description_id.'-'.$in_out : '[object_sub_descriptions]['.$object_sub_description_id.']['.$in_out.']');
								}
								$str_name = $arr_use_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_name'];
							}
							$arr_return_paths[$ref_type_id]['path'] = $path;
							$arr_return_paths[$ref_type_id]['checked'] = ($arr_return_paths[$ref_type_id]['checked'] ?: (bool)$arr_checked);
							
							if ($do_compact) {
								
								$str_name_reference = ($in_out == 'in' ? getLabel('lbl_referenced') : getLabel('lbl_referencing')).cms_general::OPTION_GROUP_SEPARATOR.' '.($in_out == 'in' ? static::SYMBOL_IN : static::SYMBOL_OUT).' ['.Labels::parseTextVariables($arr_use_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_name']).'] '.Labels::parseTextVariables($str_name).($arr_connection['dynamic'] ? static::SYMBOL_DYNAMIC : ($arr_connection['mutable'] ? static::SYMBOL_MUTABLE : ''));
								
								$arr_return_paths[$ref_type_id]['html']['options'][$in_out][] = [
									'id' => $str_name_path_connection,
									'name' => $str_name_reference
								];
								
								if ($arr_checked) {
									$arr_return_paths[$ref_type_id]['html']['checked'][] = ['id' => $str_name_path_connection, 'date' => ($arr_checked['date'] ?? [])];
								}
							} else {
								
								$arr_return_paths[$ref_type_id]['html'] .= '<label>'
									.'<span>'
										.'<span class="sub-name">'.Labels::parseTextVariables($arr_use_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_name']).'</span> <span'.($arr_connection['dynamic'] ? ' class="dynamic-references-name"' : '').'>'.Labels::parseTextVariables($str_name).'</span>'
									.'</span>'
									.'<input type="checkbox" name="'.$str_name_path.$str_name_path_connection.'" value="1"'.($arr_checked ? ' checked="checked"' : '').' />'
									.'<span class="icon" data-category="direction">'.getIcon(($in_out == 'in' ? 'updown-up' : 'updown-down')).'</span>'
								.'</label>';
							}
						}
					}
					foreach ((array)$arr_object_connections['object_descriptions'] as $object_description_id => $arr_connection) {
						
						$arr_object_description = $arr_use_type_set['object_descriptions'][$object_description_id];
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_descriptions'][$object_description_id]['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_use_type_set, $object_description_id)) {
							continue;
						}
						
						$path = implode('-', $arr_connection['path']);
						if ($num_steps === false && $source_path) {
							$path = $source_path.'-'.$path;
						}
						
						$str_name_path = $arr_options['name'].'[paths]['.$path.']';
						
						if ($arr_connection['mutable']) {
							
							$arr_checked = $arr_options['value']['paths'][$path]['object_descriptions'][$object_description_id][$in_out][$ref_type_id];
							$str_name_path_connection = ($do_compact ? 'object_descriptions-'.$object_description_id.'-'.$in_out.'-'.$ref_type_id : '[object_descriptions]['.$object_description_id.']['.$in_out.']['.$ref_type_id.']');
							
							if ($in_out == 'out') {
								$arr_type_set_mutable_referencing['object_descriptions'][$object_description_id][$ref_type_id] = $ref_type_id;
							}
						} else {
							
							$arr_checked = $arr_options['value']['paths'][$path]['object_descriptions'][$object_description_id];
							$arr_checked = (is_array($arr_checked) ? $arr_checked[$in_out] : $arr_checked); // Legacy: not an in_out array
							$str_name_path_connection = ($do_compact ? 'object_descriptions-'.$object_description_id.'-'.$in_out : '[object_descriptions]['.$object_description_id.']['.$in_out.']');
						}
						$arr_return_paths[$ref_type_id]['path'] = $path;
						$arr_return_paths[$ref_type_id]['checked'] = ($arr_return_paths[$ref_type_id]['checked'] ?: (bool)$arr_checked);
						
						if ($do_compact) {
							
							$str_name_reference = ($in_out == 'in' ? getLabel('lbl_referenced') : getLabel('lbl_referencing')).cms_general::OPTION_GROUP_SEPARATOR.' '.($in_out == 'in' ? static::SYMBOL_IN : static::SYMBOL_OUT).' '.Labels::parseTextVariables($arr_use_type_set['object_descriptions'][$object_description_id]['object_description_name']).($arr_connection['dynamic'] ? static::SYMBOL_DYNAMIC : ($arr_connection['mutable'] ? static::SYMBOL_MUTABLE : ''));
							
							$arr_return_paths[$ref_type_id]['html']['options'][$in_out][] = [
								'id' => $str_name_path_connection,
								'name' => $str_name_reference
							];
							
							if ($arr_checked) {
								$arr_return_paths[$ref_type_id]['html']['checked'][] = ['id' => $str_name_path_connection, 'date' => ($arr_checked['date'] ?? [])];
							}
						} else {
							
							$arr_return_paths[$ref_type_id]['html'] .= '<label>'
								.'<span'.($arr_connection['dynamic'] ? ' class="dynamic-references-name"' : '').'>'.Labels::parseTextVariables($arr_use_type_set['object_descriptions'][$object_description_id]['object_description_name']).'</span>'
								.'<input type="checkbox" name="'.$str_name_path.$str_name_path_connection.'" value="1"'.($arr_checked ? ' checked="checked"' : '').' />'
								.'<span class="icon" data-category="direction">'.getIcon(($in_out == 'in' ? 'updown-up' : 'updown-down')).'</span>'
							.'</label>';
						}
					}
					
					$arr_target_type_set = StoreType::getTypeSet($ref_type_id);
					$arr_type_set_date_flat = StoreType::getTypeSetFlat($ref_type_id, ['object_sub_details_date' => true, 'object_sub_details_location' => false]);
					$arr_target_type_set_date_flat[$ref_type_id] = [];
					
					foreach ($arr_target_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
												
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_descriptions'][$object_description_id]['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_use_type_set, $object_description_id)) {
							continue;
						}
						
						$str_id = 'object_description_'.$object_description_id;
						
						if ($arr_object_description['object_description_value_type'] == 'date') {
							$arr_target_type_set_date_flat[$ref_type_id][$str_id] = $arr_type_set_date_flat[$str_id];
						}
					}
					
					foreach ($arr_target_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {

						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_target_type_set, false, $object_sub_details_id)) {
							continue;
						}
						
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_';
						
						if ($arr_type_set_date_flat[$str_id.'date_start']) {
							$arr_target_type_set_date_flat[$ref_type_id][$str_id.'date_start'] = $arr_type_set_date_flat[$str_id.'date_start'];
						}
						if ($arr_type_set_date_flat[$str_id.'date_end']) {
							$arr_target_type_set_date_flat[$ref_type_id][$str_id.'date_end'] = $arr_type_set_date_flat[$str_id.'date_end'];
						}

						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {

							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration(StoreCustomProject::ACCESS_PURPOSE_VIEW, $arr_project['types'], $arr_target_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
							
							$str_id = 'object_sub_description_'.$object_sub_description_id;
							
							if ($arr_object_sub_description['object_sub_description_value_type'] == 'date') {
								$arr_target_type_set_date_flat[$use_type_id][$str_id] = $arr_type_set_date_flat[$str_id];
							}
						}
					}
				}
			}
			
			// Output
			
			$str_source_path_name = '';
			
			if ($source_path) {
				
				$arr_source_path = explode('-', $source_path);
				
				foreach ($arr_source_path as $source_type_id) {

					$str_source_path_name .= '<span>'.Labels::parseTextVariables($arr_types[$source_type_id]['name']).'</span>';
				}
			}
			
			$str_source_path_name .= '<span>'.Labels::parseTextVariables($arr_types[$type_id]['name']).'</span>';
			
			$return .= '<div class="node" data-type_id="'.$type_id.'">';
				$return .= '<h4><span></span><span>'.Labels::parseTextVariables($arr_type_set['type']['name']).'</span></h4>';
				
				$arr_options_values = $arr_options['value']['types'][$source_path][$type_id];
				$name = $arr_options['name'].'[types]['.$source_path.']['.$type_id.']';
									
				if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_FLAT) {
					
					$arr_sorter = [];
					
					foreach (($arr_options_values['selection'] ?: [[]]) as $id => $value) {
						
						$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
						
						$arr_sorter[] = ['value' => [
								'<select name="'.$name.'[selection]['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set_flat, $id, true)).'</select>'
							]
						];
					}
					
					$arr_selector = [['id' => 'filter', 'name' => getLabel('lbl_filter'), 'title' => getLabel('inf_path_filter')]];

					$arr_selected = [
						($arr_options_values['filter'] ? 'filter' : false)
					];

					$html_fields = '<fieldset><legend>'.$str_source_path_name.'</legend><ul>
						<li>
							<label>'.getLabel('lbl_path').'</label><span>'.cms_general::createSelector($arr_selector, $name, $arr_selected).'</span>
						</li>';
						
						$arr_selected = [
							($arr_options_values['nodegoat_id'] ? 'nodegoat_id' : false),
							($arr_options_values['id'] ? 'id' : false),
							($arr_options_values['name'] ? 'name' : false),
							($arr_options_values['sources'] ? 'sources' : false),
							($arr_options_values['analysis'] ? 'analysis' : false)
						];
					
						$html_fields .= '<li>
							<label>'.getLabel('lbl_object').'</label>
							<span>'.cms_general::createSelectorList([
								['id' => 'nodegoat_id', 'name' => getLabel('lbl_nodegoat_id')],
								['id' => 'id', 'name' => getLabel('lbl_object_id')],
								['id' => 'name', 'name' => getLabel('lbl_name')],
								['id' => 'sources', 'name' => getLabel('lbl_sources')],
								['id' => 'analysis', 'name' => getLabel('lbl_analysis')]
							], $name, $arr_selected).'</span>
						</li>
						<li>
							<label>'.getLabel('lbl_export').'</label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label></label>'.cms_general::createSorter($arr_sorter, true).'
						</li>
					</ul></fieldset>';
					
				} else if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_CONCEPT) {

					$arr_type_set_flat_separated = [];
					$arr_sorter = [];
					
					$arr_select_object_sub_details = $arr_selected_object_sub_ids = [];
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
						$arr_select_object_sub_details[] = ['id' => $str_id, 'name' => '<span class="sub-name">'.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'</span>'];
						
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
								
								$str_id_reference = $str_id.'_reference';
								$arr_type_set_flat_separated['referencing'][$str_id_reference] = $arr_type_set_flat[$str_id];
								$arr_type_set_flat_separated['referencing'][$str_id_reference]['id'] = $str_id_reference;
							} else {
								
								$str_id_reference = $str_id;
								$arr_type_set_flat_separated['referencing'][$str_id_reference] = $arr_type_set_flat[$str_id];
							}
							
							if ($is_dynamic || is_array($arr_object_description['object_description_ref_type_id'])) {
								
								foreach ((array)$arr_type_set_mutable_referencing['object_descriptions'][$object_description_id] as $ref_type_id) {
									
									$s_arr =& $arr_type_set_flat_separated['referencing'][$str_id_reference.'_mutable_'.$ref_type_id];
									$s_arr = $arr_type_set_flat[$str_id];
									
									$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);
									
									$s_arr['id'] = $str_id_reference.'_mutable_'.$ref_type_id;
									$s_arr['name'] = Labels::addContainer($s_arr['name']).($is_dynamic ? static::SYMBOL_DYNAMIC : static::SYMBOL_MUTABLE).' '.Labels::addContainer($arr_ref_type_set['type']['name']);
								}
								unset($s_arr);
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
									
									$str_id_reference = $str_id.'_reference';
									$arr_type_set_flat_separated['referencing'][$str_id_reference] = $arr_type_set_flat[$str_id];
									$arr_type_set_flat_separated['referencing'][$str_id_reference]['id'] = $str_id_reference;
								} else {
									
									$str_id_reference = $str_id;
									$arr_type_set_flat_separated['referencing'][$str_id_reference] = $arr_type_set_flat[$str_id];
								}
								
								if ($is_dynamic || is_array($arr_object_sub_description['object_sub_description_ref_type_id'])) {
									
									foreach ((array)$arr_type_set_mutable_referencing['object_sub_descriptions'][$object_sub_description_id] as $ref_type_id) {
									
										$s_arr =& $arr_type_set_flat_separated['referencing'][$str_id_reference.'_mutable_'.$ref_type_id];
										$s_arr = $arr_type_set_flat[$str_id];
										
										$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);

										$s_arr['id'] = $str_id_reference.'_mutable_'.$ref_type_id;
										$s_arr['name'] = Labels::addContainer($s_arr['name']).($is_dynamic ? static::SYMBOL_DYNAMIC : static::SYMBOL_MUTABLE).' '.Labels::addContainer($arr_ref_type_set['type']['name']);
									}
									unset($s_arr);
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
						
						if (isset($arr_selected['use_reference'])) { // Check if mutable references are targeting specific type IDs and separate them
							
							$arr_ref_type_ids = $arr_selected['use_reference'];
							
							if (is_array($arr_ref_type_ids)) {
																
								foreach ($arr_ref_type_ids as $ref_type_id) {
									
									$arr_selection_referencing_or_values['referencing'][$id.'_mutable_'.$ref_type_id] = $arr_selected;
								}
								continue;
							}
						}
						
						$referencing_or_values = ($arr_type_set_flat_separated['referencing'][$id] || $arr_type_set_flat_separated['referencing'][$id.'_reference'] ? 'referencing' : 'values'); // Referencing has precedence over values in case of dynamic object descriptions
						
						$arr_selection_referencing_or_values[$referencing_or_values][$id] = $arr_selected;
					}
					
					foreach ($arr_type_set_flat_separated as $referencing_or_values => $arr_type_set_flat_separate) {		
						
						foreach (($arr_selection_referencing_or_values[$referencing_or_values] ?: [[]]) as $id => $arr_selected) {
							
							$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
							
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

					$html_fields = '<fieldset><legend>'.$str_source_path_name.'</legend><ul>
						<li>
							<label>'.getLabel('lbl_path').'</label><span>'.cms_general::createSelector($arr_selector, $name, $arr_selected).'</span>
						</li>
						<li>
							<label>'.getLabel('lbl_object').'</label><span>'.cms_general::createSelector($arr_selector_object, $name, $arr_selected_object).'</span>
						</li>';
					if ($arr_select_object_sub_details) {
						$html_fields .= '<li>
							<label>'.getLabel('lbl_object_subs').'</label><span>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_select_object_sub_details, $name.'[selection][][id]', $arr_selected_object_sub_ids)).'</span>
						</li>';
					}
					if ($arr_sorter['referencing']) {
						$html_fields .= '<li>
							<label>'.getLabel('lbl_referencing').'</label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label></label>'.cms_general::createSorter($arr_sorter['referencing'], true).'
						</li>';
					}
					if ($arr_sorter['values']) {
						$html_fields .= '<li>
							<label>'.getLabel('lbl_values').'</label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label></label>'.cms_general::createSorter($arr_sorter['values'], true).'
						</li>';
					}
					$html_fields .= '</ul></fieldset>';
				} else {
					
					if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE) {
						$arr_options['functions']['collapse'] = false;
					}

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
					
					$html_fields_path = '<li>
						<label>'.getLabel('lbl_path').'</label><span>'.cms_general::createSelector($arr_selector, $name, $arr_selected).'</span>
					</li>';
					$html_fields_other = '';
					
					if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DISPLAY) {
						
						$arr_selected = [
							($arr_options_values['name'] ? 'name' : false)
						];
					
						$html_fields_other .= '<li>
							<label>'.getLabel('lbl_object').'</label>
							<span>'.cms_general::createSelectorList([
								['id' => 'name', 'name' => getLabel('lbl_name')]
							], $name, $arr_selected).'</span>
						</li>';
					}
					
					if ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE || $str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DISPLAY) {
						
						$arr_sorter = [];
					
						foreach (($arr_options_values['selection'] ?: [[]]) as $id => $value) {
							
							$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
							
							$arr_sorter[] = ['value' => [
									'<select name="'.$name.'[selection]['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set_flat, $id, true)).'</select>'
								]
							];
						}
						
						$str_label = ($str_descriptions_type == static::TYPE_NETWORK_DESCRIPTIONS_DATE ? 'lbl_date' : 'lbl_select');
											
						$html_fields_other .= '<li>
							<label>'.getLabel($str_label).'</label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label></label>'.cms_general::createSorter($arr_sorter, true).'
						</li>';
					}

					$html_fields = '<fieldset><legend>'.$str_source_path_name.'</legend><ul>
						'.$html_fields_path.$html_fields_other.'
					</ul></fieldset>';
				}
					
				$return .= '<div>'.$html_fields.'</div>';
				
				$arr_return_paths = arrSortByArray($arr_return_paths, array_keys($arr_types));
				$return_paths = '';
				
				foreach ($arr_return_paths as $ref_type_id => $arr_return_path) {
					
					if ($num_steps) {
						
						$return_deep = $func_connection($arr_network_connections['connections'][$ref_type_id], $ref_type_id, $arr_return_path['path']);
					} else {
						
						if ($arr_return_path['checked']) {
							
							$trace->run($ref_type_id, false, 1, $arr_options['references']);
							$arr_type_network_paths = $trace->getTypeNetworkPaths();
							$return_deep = $func_connection($arr_type_network_paths['connections'][$ref_type_id], $ref_type_id, $arr_return_path['path']);
						} else {
							
							$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);
							$return_deep = '<div class="node" data-type_id="'.$ref_type_id.'" data-source_path="'.$arr_return_path['path'].'"><h4><span></span><span>'.Labels::parseTextVariables($arr_ref_type_set['type']['name']).'</span></h4></div>';
						}
					}
					
					if ($do_compact) {
						
						$num_in = count($arr_return_path['html']['options']['in'] ?: []);
						$num_out = count($arr_return_path['html']['options']['out'] ?: []);
						$str_summary = ($num_in ? $num_in.' '.static::SYMBOL_IN : '').''.($num_out ? static::SYMBOL_OUT.' '.$num_out : '');
						
						$arr_date_rows = [];
						
						foreach ($arr_source_type_set_date_flat as $arr_value) {
							$arr_date_rows[] = ['id' => 'source-'.$arr_value['id'], 'name' => getLabel('lbl_source').cms_general::OPTION_GROUP_SEPARATOR.$arr_value['name']];
						}
						if (isset($arr_target_type_set_date_flat[$ref_type_id])) {
							
							foreach ($arr_target_type_set_date_flat[$ref_type_id] as $arr_value) {
								$arr_date_rows[] = ['id' => 'target-'.$arr_value['id'], 'name' => getLabel('lbl_target').cms_general::OPTION_GROUP_SEPARATOR.$arr_value['name']];
							}
						}
						
						$arr_rows = arrMerge(($arr_return_path['html']['options']['out'] ?? []), ($arr_return_path['html']['options']['in'] ?? []));
						$has_multiple = (count($arr_rows) > 1);				
						
						$arr_sorter_path = [];
						$arr_checked = $arr_return_path['html']['checked'];
						
						if ($has_multiple) {
							
							if ($arr_checked) { // Add one empty and source
								$arr_checked[] = [];
								$arr_checked['source'] = [];
							} else {
								$arr_checked = [[], 'source' => []]; // Only an empty and source
							}
						} else if (!$arr_checked) {
							$arr_checked = [[]]; // Only an empty
						}
						
						foreach ($arr_checked as $key => $arr_checked_connection) {
							
							$arr_date_selected = ($arr_checked_connection['date'] ?? []);
							$has_use_date = (bool)$arr_date_selected;
							
							$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
							$str_name_path = $arr_options['name'].'[paths]['.$arr_return_path['path'].']['.$unique.']';
							
							$arr_chronology_options = ['path' => false, 'reference' => false];
							
							$arr_chrolonogy_date_start = data_entry::createChronologyFields($str_name_path.'[date][start]', $arr_date_selected['start'], StoreType::DATE_START_START, $arr_chronology_options);
							$arr_chrolonogy_date_end = data_entry::createChronologyFields($str_name_path.'[date][end]', $arr_date_selected['end'], StoreType::DATE_END_END, $arr_chronology_options);
							$str_date_selected_start = (isset($arr_date_selected['start']['id']) ? $arr_date_selected['start']['source_target'].'-'.$arr_date_selected['start']['id'] : false);
							$str_date_selected_end =  (isset($arr_date_selected['end']['id']) ? $arr_date_selected['end']['source_target'].'-'.$arr_date_selected['end']['id'] : false);
							
							$str_html_special = '<fieldset><ul>'
								.'<li><label>'.getLabel('lbl_date_start').'</label><div>'.$arr_chrolonogy_date_start['offset'].$arr_chrolonogy_date_start['cycle'].$arr_chrolonogy_date_start['date'].'</div></li>'
								.'<li><label></label><div><select name="'.$str_name_path.'[date][start][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_rows, $str_date_selected_start, true)).'</select></div></li>'
								.'<li><label>'.getLabel('lbl_date_end').'</label><div>'.$arr_chrolonogy_date_end['offset'].$arr_chrolonogy_date_end['cycle'].$arr_chrolonogy_date_end['date'].'</div></li>'
								.'<li><label></label><div><select name="'.$str_name_path.'[date][end][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_date_rows, $str_date_selected_end, true)).'</select></div></li>'
							.'</ul></fieldset>';
							
							$arr_sorter_path[] = ['source' => ($key === 'source'), 'value' => [
									'<select name="'.$str_name_path.'[connection]" data-state_empty="1" placeholder="'.$str_summary.'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_rows, $arr_checked_connection['id'], true)).'</select>'
									.'<label title="'.getLabel((!$source_path ? 'inf_path_connection_temporal_origin' : 'inf_path_connection_temporal')).'"><input type="checkbox" name="'.$str_name_path.'[use_date]" value="1"'.($has_use_date ? ' checked="checked"' : '').' /><span>T</span></label>'
									, $str_html_special
								]
							];
						}
						
						$str_html = cms_general::createSorter($arr_sorter_path, true, false, ['auto_add' => true, 'auto_clean' => true, 'state_empty' => true, 'limit' => (!$has_multiple ? 1 : false)]);

						$return_paths .= '<div><fieldset><div>'.$str_html.'</div></fieldset>'.$return_deep.'</div>';
					} else {
						
						$return_paths .= '<div>'.$arr_return_path['html'].$return_deep.'</div>';
					}
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
			'network' => $arr_options['network'],
			'compact' => $arr_options['compact']
		];

		$return = '<div class="network type"'.($num_steps === false ? ' id="y:data_model:get_type_network_hop-0"' : '').' data-options="'.strEscapeHTML(value2JSON($arr_options_hop)).'"><div class="node">
			'.$func_connection($arr_type_network_paths['connections'][$from_type_id], $from_type_id, ($arr_options['source_path'] ?: 0)).'
		</div></div>';

		return $return;
	}
	
	public static function css() {
	
		$return = '
			.data_model .definition > fieldset .sorter textarea { width: 450px; height: 100px; }
			.data_model .object-descriptions > ul.sorter > li + li,
			.data_model .object-sub-descriptions > ul.sorter > li + li { margin-top: 25px; }
			.data_model .object-sub-details > .sorter > li > div > fieldset { margin: 0px; }
			
			.data_model .object-descriptions > ul.sorter > li > ul > li > div.reference_mutable,
			.data_model .object-sub-descriptions > ul.sorter > li > ul > li > div.reference_mutable { vertical-align: top; }
			
			.data_model .object-description-options:empty { display: none; }
			.data_model .object-description-options fieldset > ul > li > label:first-child + div > input[name$="[separator]"] { width: 75px; }
			
			.data_model .object-sub-details fieldset .tabs > div > div > fieldset { margin-left: 0px; } 
			
			.data_model .definition > fieldset > ul > li > label:first-child + [name=label] { width: 120px; }
			
			.condition fieldset > ul > li > label:first-child + * input[name$="[image][url]"] + img { max-height: 20px; }
			
			.network.type { margin: 0px auto; padding-bottom: 14px; }
			.network.type .node > h4 + div { display: inline-block; text-align: left; }
			.network.type .node > div > fieldset > legend { font-weight: normal; }
			.network.type .node > div > fieldset > legend > span:last-child { font-weight: bold; }
			.network.type .node > div > fieldset > legend > span::after { content: "-"; margin: 0px 4px; }
			.network.type .node > div > fieldset > legend > span:last-child::after { content: none; }
			.network.type .node > div > fieldset { margin: 0px; }
			.network.type .node > div > fieldset select { max-width: 250px; }
			.network.type .node:not(.inactive) > div + div { margin-left: 10px; }
			.network.type .node > div + div > div > label { display: inline-block; margin: 5px 5px; }
			.network.type .node > div + div > div > label > input[type=checkbox] { display: block; margin: 1px auto 0px auto; }
			.network.type .node > div + div > div > label > .icon,
			.network.type .node > div + div > div > label > .icon { display: block; margin: 1px auto -3px auto; }
			.network.type .node > div + div > div > fieldset { margin: 10px 10px; }
			.network.type .node > div + div > div > fieldset > div { display: inline-block; text-align: left; }
			.network.type .node > div + div > div > fieldset ul.inactive > li > select + label,
			.network.type .node > div + div > div > fieldset ul.inactive > li + li { display: none; }
			.network.type .node > div + div > div > fieldset select { text-align: left; font-family: var(--font-mono), var(--font-symbol); }
			.network.type .node > div + div > div > fieldset select.state-placeholder:not(:checked),
			.network.type .node > div + div > div > fieldset select.state-placeholder > option:last-child { text-align: center; }
			.network.type .node.inactive > div { display: none; }
		';
		
		$return .= EnucleateValueTypeModule::getModulesStyles();

		return $return;
	}
	
	public static function js() {

		$return = "
		SCRIPTER.static('.data_model', function(elm_scripter) {
		
			elm_scripter.on('click', '[id^=d\\\:data_model\\\:data-] .edit', function() {

				COMMANDS.quickCommand(this, $(this).closest('.datatable').prev('form'), {html: 'replace'});
			}).on('change', '[id=y\\\:data_model\\\:set_filter-0] [name]', function() {
				
				var elm_command = this.closest('form');
				
				COMMANDS.setData(elm_command, serializeArrayByName(elm_command));
				COMMANDS.quickCommand(elm_command);
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_model\\\:]', function(elm_scripter) {
		
			var func_update_value_type = function(elm_value_type) {
			
				const elm_parent = elm_value_type.closest('ul');
				const elm_options = elm_parent.find('.object-description-options');

				const type_id = COMMANDS.getID(elm_parent.closest('form'), true);
				const str_value_type = elm_value_type.val();
				const str_name = elm_value_type.attr('name');
				
				const elms_ref_type_id = elm_parent.find('select[name*=\"description_ref_type_id]['+str_value_type+']\"]');
				let elm_ref_type_id = elms_ref_type_id[0];
				let ref_type_id = false;
				if (elm_ref_type_id) {
					if (elm_ref_type_id.name.endsWith('[]')) {
						ref_type_id = [];
						for (elm_ref_type_id of elms_ref_type_id) {
							ref_type_id.push(elm_ref_type_id.value);
						}
					} else {
						ref_type_id = elm_ref_type_id.value;
					}
				}
				
				const has_multi = elm_parent.find('input[name*=description_has_multi]').is(':enabled:checked');
				const has_default_value = elm_parent.find('[name*=description_has_default_value]').is(':enabled:checked');
				const in_name = elm_parent.find('input[name*=description_in_name]').is(':enabled:checked');
				let elms_option_settings = elm_options.find('[name]:not([name*=\"[default]\"])'); // Do not include changes in default values for the identifier
				let value_settings = (elms_option_settings.length ? serializeArrayByName(elms_option_settings) : false);
				
				const arr_identifier = {type_id: type_id, value_type: str_value_type, name: str_name, ref_type_id: ref_type_id, has_multi: has_multi, has_default_value: has_default_value, in_name: in_name, value_settings: value_settings};
				let identifier = JSON.stringify(arr_identifier);
				let identifier_check = elm_value_type[0].identifier_value_type_settings;
				
				elm_value_type[0].identifier_value_type_settings = identifier;
				
				if (!identifier_check || identifier_check == identifier) { // Nothing changed after initialisation or the settings remained the same			
					return;
				}
				
				identifier = str_value_type+'_'+String(ref_type_id);
				identifier_check = elm_value_type[0].identifier_value_type;
				
				if (identifier_check && identifier != elm_value_type[0].identifier_value_type) { // Clear before loading new
					elm_options[0].innerHTML = '';
				}
				elm_value_type[0].identifier_value_type = identifier;
				
				elms_option_settings = elm_options;
				value_settings = (elms_option_settings.length ? serializeArrayByName(elms_option_settings) : false);
				var arr_value = {type_id: type_id, value_type: str_value_type, name: str_name, ref_type_id: ref_type_id, has_multi: has_multi, has_default_value: has_default_value, in_name: in_name, value_settings: value_settings};
				
				FEEDBACK.stop(elm_value_type);
				COMMANDS.setData(elm_value_type, arr_value);

				COMMANDS.quickCommand(elm_value_type, function(arr_data) {
					
					const elm_options_new = $(arr_data.object_description_options);
					elm_options.html(elm_options_new);
					
					return elm_options;
				});
			};
			
			elm_scripter.on('ajaxloaded scripter', function(e) {
			
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				runElementSelectorFunction(e.detail.elm, '[name*=\"description_value_type_base]\"], input[name=reversal_mode]:checked, input[name=reversal_reference_class]:checked', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_data_model');
				});
			}).on('change update_data_model', '[name*=\"description_value_type_base]\"]', function(e) {
				
				var elm_value_type = $(this);
				var elm_parent = elm_value_type.closest('ul');
				var str_value_type = elm_value_type.val();
				var elm_value_type_option = elm_value_type.children('option:selected');
				var has_support_multi = elm_value_type_option[0].dataset.has_support_multi;
				var has_support_identifier = elm_value_type_option[0].dataset.has_support_identifier;
				
				var elm_targets = elm_parent.find('input[name*=description_has_multi], input[name*=description_is_required], input[name*=description_has_default_value], input[name*=object_description_is_unique], input[name*=object_description_is_identifier]');
				elm_targets.prop('disabled', false);
				
				var elm_target = elm_parent.find('> li > select[name*=description_ref_type_id], > li > div.reference_mutable').addClass('hide');
				elm_target.addClass('hide');
				
				if (str_value_type == 'type' || str_value_type == 'classification' || str_value_type == 'reversal') {
				
					elm_target.filter('[name*=\"['+str_value_type+']\"]').removeClass('hide');

					if (str_value_type == 'reversal') {
						elm_parent.find('input[name*=description_has_multi], input[name*=description_is_required], input[name*=description_has_default_value], input[name*=object_description_is_unique], input[name*=object_description_is_identifier]').prop('disabled', true);
					} else {
						elm_parent.find('input[name*=object_description_is_identifier]').prop('disabled', true);
					}
				} else if (str_value_type == 'reference_mutable') {
					
					elm_target.filter('div.reference_mutable').removeClass('hide');
					
					elm_parent.find('input[name*=object_description_is_identifier]').prop('disabled', true);
				} else if (str_value_type == 'object_description') {
				
					elm_parent.find('input[name*=description_is_required], input[name*=description_has_default_value]').prop('disabled', true);
				} else {
					
					if (has_support_multi) { // Multi

					} else {
						elm_parent.find('input[name*=description_has_multi]').prop('disabled', true);
					}
					if (has_support_identifier) { // API identifier
					
					} else {
						elm_parent.find('input[name*=object_description_is_identifier]').prop('disabled', true);
					}
				}
				
				func_update_value_type(elm_value_type);
			}).on('change', 'select[name*=description_ref_type_id]', function(e) {
			
				var elm_value_type = $(this).closest('.object-descriptions > ul > li, .object-sub-descriptions > ul > li').find('[name*=\"description_value_type_base]\"]');
				
				func_update_value_type(elm_value_type);
			}).on('change', '[name*=description_has_default_value], input[name*=description_has_multi], input[name*=description_in_name], [name*=\"description_value_type_settings]\"]', function(e) {
			
				var elm_value_type = $(this).closest('.sorter > li > ul').find('[name*=\"description_value_type_base]\"]');
				
				func_update_value_type(elm_value_type);
			}).on('change update_data_model', '[name=reversal_mode]', function(e) {
				
				var cur = $(this);
				var value = cur.val();
				var elm_parent = cur.closest('ul');
				var elms_target_classification = elm_parent.find('[name^=reversal_ref]').closest('li');
				var elms_target_collection = elm_parent.find('[name=reversal_resource_path]').closest('li');
				
				if (value == ".StoreType::TYPE_MODE_REVERSAL_CLASSIFICATION.") {
					elms_target_classification.removeClass('hide');
					elms_target_collection.addClass('hide');
				} else {
					elms_target_classification.addClass('hide');
					elms_target_collection.removeClass('hide');
				}
			}).on('change update_data_model', '[name=reversal_reference_class]', function(e) {
				
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
				
				runElementSelectorFunction(e.detail.elm, '[name*=object_sub_details_date_type]:checked, [name*=object_sub_details_location_type]:checked', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_data_model');
				});
			}).on('change', '[id=y\\\:data_model\\\:selector_object_sub_details-0]', function() {
				
				var cur = $(this);
				var elm_target = cur.siblings('[name*=\"location_ref_object_sub_details_id]\"], [name*=\"date_setting_object_sub_details_id]\"]');
				
				COMMANDS.quickCommand(cur, elm_target);
				
				if (elm_target.is('[name*=\"location_ref_object_sub_details_id]\"]')) {
					cur.closest('li').next('li').find('[name*=object_sub_details_location_use_object_sub_details_id], [name*=object_sub_details_location_use_object_sub_description_id], [name*=object_sub_details_location_use_object_description_id]').empty();
				}
			}).on('change update_data_model', '[name*=object_sub_details_date_type], [name*=object_sub_details_location_type]', function() {
				
				var cur = $(this);
				var elms_target = cur.closest('li').nextAll('li');
				
				if (cur.val() == 'none') {
					elms_target.addClass('hide');
				} else {
				
					elms_target.removeClass('hide');
					
					var elm_target = false;
					
					if (cur.is('[name*=object_sub_details_location_type]')) {
						
						var elm_target = elms_target.find('[name*=object_sub_details_location_ref_type_id_locked]');
					} else {
						
						var elm_target = elms_target.find('[name*=object_sub_details_date_setting]:checked');
					}
					
					SCRIPTER.triggerEvent(elm_target, 'update_data_model');	
				}			
			}).on('change update_data_model', '[name*=object_sub_details_date_setting]', function() {
				
				var cur = $(this);
				var str_default = cur.val();
				var elms_target = cur.closest('li').nextAll('li');
				
				var elm_target_source = elms_target.find('[name*=object_sub_details_date_usage]');
				var elm_target_default_reference = elms_target.find('[name*=object_sub_details_date_setting_type_id]');

				if (str_default == 'source') {
					elm_target_source.closest('li').removeClass('hide');
					elm_target_default_reference.closest('li').addClass('hide');
					
					SCRIPTER.triggerEvent(elm_target_source, 'update_data_model');	
				} else {
					elm_target_source.closest('li').addClass('hide');
					elm_target_default_reference.closest('li').removeClass('hide');
				}			
			}).on('change update_data_model', '[name*=object_sub_details_date_usage]', function() {
				
				var cur = $(this);
				var elms_target = cur.siblings('[name*=object_sub_details_date_use], [name*=object_sub_details_date_start], [name*=object_sub_details_date_end], label');
				
				elms_target.addClass('hide');
				
				var is_date_period = (cur.closest('ul').find('[name*=object_sub_details_date_type]:checked').val() == 'period');
				
				if (!is_date_period) {
					elms_target = elms_target.filter('[name*=object_sub_details_date_use], [name*=object_sub_details_date_start]');
				}
				
				if (cur.val() == 'object_sub_details') {
					elms_target.filter('[name*=object_sub_details_date_use_object_sub_details_id]').removeClass('hide');
				} else if (cur.val() == 'object_sub_description') {
					elms_target.filter('[name*=use_object_sub_description_id], label').removeClass('hide');
				} else if (cur.val() == 'object_description') {
					elms_target.filter('[name*=use_object_description_id], label').removeClass('hide');
				}
			}).on('change update_data_model', '[name*=object_sub_details_location_ref_type_id_locked]', function() {
				
				var cur = $(this);
				var elm_target = cur.closest('li').next('li').find('[name*=object_sub_details_location_usage]');
				
				if (cur.prop('checked')) {
					elm_target.closest('li').removeClass('hide');
				} else {
					elm_target.val('').closest('li').addClass('hide');
				}
				
				SCRIPTER.triggerEvent(elm_target, 'update_data_model');
			}).on('change update_data_model', '[name*=object_sub_details_location_usage]', function() {
				
				var cur = $(this);
				var elms_target = cur.siblings('[name*=object_sub_details_location_use_object_sub_details_id], [name*=object_sub_details_location_use_object_sub_description_id], [name*=object_sub_details_location_use_object_description_id]');
				
				elms_target.addClass('hide');
				
				if (cur.val() == 'object_sub_details') {
					elms_target.filter('[name*=object_sub_details_location_use_object_sub_details_id]').removeClass('hide');
				} else if (cur.val() == 'object_sub_description') {
					elms_target.filter('[name*=object_sub_details_location_use_object_sub_description_id]').removeClass('hide');
				} else if (cur.val() == 'object_description') {
					elms_target.filter('[name*=object_sub_details_location_use_object_description_id]').removeClass('hide');
				}
			}).on('change', '[id^=y\\\:data_model\\\:selector_object_sub_descriptions-]', function() {
				COMMANDS.quickCommand(this, $(this).siblings('[name*=object_sub_description_id]'));
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_model\\\:]', 'filter_select');
		
		// CONDITIONS
		
		SCRIPTER.dynamic('[data-method=update_condition]', function(elm_scripter) {
		
			SCRIPTER.runDynamic(elm_scripter.find('.condition'));
		
			elm_scripter.on('command', '[id^=y\\\:data_model\\\:open_condition-]', function() {
									
				var cur = $(this);
				var elm_condition = elm_scripter.find('.condition');
					
				COMMANDS.setTarget(this, function(html) {
				
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
						elm_target.filter('[data-group_identifier=\"'+value+'\"]').prop('hidden', false);
						elm_target.not('[data-group_identifier=\"'+value+'\"]').prop('selected', false).prop('hidden', true);
					} else {
						elm_target.prop('selected', false).prop('hidden', true);
					}
				});
			};

			func_update_condition_object_nodes_values(elm_scripter.find('div.identifiers'));
			
			elm_scripter.on('ajaxloaded scripter', function() {
				
				runElementSelectorFunction(elm_scripter, '.condition-model-conditions [name$=\"[condition_use_current]\"]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_condition_model_conditions');
				});
			}).on('change update_condition_model_conditions', '.condition-model-conditions [name$=\"[condition_use_current]\"]', function() {
				$(this).prev('select').prop('disabled', $(this).is(':checked'));
			}).on('change', 'div.identifiers select[name$=\"[id]\"]', function() {
				
				func_update_condition_object_nodes_values($(this).parent());
			}).on('click', 'fieldset > ul > li > div > img + .del', function() {
				var cur = $(this);
				cur.nextAll('input[type=button].hide').removeClass('hide');
				cur.add(cur.prevAll('img, input[type=hidden]')).remove();
			});
		});
		
		SCRIPTER.dynamic('.condition', 'application_filter');
		
		// NETWORK
		
		SCRIPTER.dynamic('.network.type', function(elm_scripter) {
			
			var func_update_type_network = function(elms, is_init) {
				
				for (let i = 0, len = elms.length; i < len; i++) {
								
					const cur = $(elms[i]);
					const elm_parent = cur.parent('div');
					const elm_compact = elm_parent.children('fieldset');
					let has_selected = false;

					if (elm_compact.length) {
					
						const elms_target = getElementSelector(elm_compact, 'select[name$=\"[connection]\"]');
		
						for (let i = 0, len = elms_target.length; i < len; i++) {
							
							const elm_target = elms_target[i];
							has_selected = (has_selected || elm_target.value);
							
							let elm_check = getElementClosestSelector(elm_target, 'ul');
							
							if (!elm_target.value) {
								elm_check.classList.add('inactive');
								continue;
							}
							
							if (!elm_check.classList.contains('inactive') && !is_init) {
								continue;
							}
							
							elm_check.classList.remove('inactive');
							
							const elm_use_date = getElementSelector(elm_check, 'input[name$=\"[use_date]\"]');
							
							if (elm_use_date) {
								func_update_type_network_use_date(elm_use_date[0]);
							}
						}
					} else {
					
						has_selected = elm_parent.children('label').children('input:checked').length;
					}
				
					if (!has_selected) {
					
						cur.addClass('inactive');
					} else {
					
						cur.removeClass('inactive');
						
						const elm_target = getElementSelector(cur, ':scope > div:first-of-type > fieldset input[name$=\"[object_only]\"]');
						
						if (elm_target) {
							func_update_type_network_object_only(elm_target[0]);
						}
					}
				};
			};
			var func_update_type_network_use_date = function(elm) {
				
				let elm_target = getElementClosestSelector(elm, 'li');
				elm_target = elm_target.nextSibling;
				
				elm_target.classList.toggle('hide', !elm.checked);
			};
			var func_update_type_network_object_only = function(elm) {
				
				var elm = $(elm);
				const elm_target = elm.closest('li').nextAll('li').find('select, input');
				
				elm_target.prop('disabled', elm.is(':checked'));
			};
			
			elm_scripter.on('ajaxloaded scripter', function(e) {

				if (e.target != elm_scripter[0]) {
					return;
				}
			
				const elms_target = elm_scripter.find('> .node > .node').find('.node');
				
				if (elms_target.length) {
					func_update_type_network(elms_target, true);
				}
				
				if (e.type == 'scripter') {
					
					const elm_target = getElementSelector(elm_scripter, ':scope > .node > .node > div:first-of-type > fieldset input[name$=\"[object_only]\"]');
					
					if (elm_target) {
						func_update_type_network_object_only(elm_target[0]);
					}
				}
			}).on('change removed', '.node > div + div > div > label > input[type=checkbox], .node > div + div > div > fieldset .sorter, .node > div + div > div > fieldset select[name$=\"[connection]\"]', function(e) {
				
				if (e.currentTarget != e.target) {
					return;
				}
				
				const cur = $(this);
				const elm_target = $(this).closest('div > div').children('.node');
				const elm_target_children = elm_target.children('fieldset, div');
				const elm_source = cur.closest(elm_scripter);
				
				var func_scroll = function() {
										
					moveScroll(cur, {elm_container: elm_source});
					new Pulse(elm_target.children('h4'));
					new Pulse(elm_target.children('h4 + div'));
				};
				
				if (elm_target_children.length) {
					
					func_update_type_network(elm_target);
					func_scroll();
				} else if (elm_source.is('[id=y\\\:data_model\\\:get_type_network_hop-0]')) {
					
					const value = {type_id: elm_target.attr('data-type_id'), source_path: elm_target.attr('data-source_path'), options: elm_source.attr('data-options')};
					
					COMMANDS.setData(elm_source, value);
					COMMANDS.quickCommand(elm_source, function(elm) {
						
						elm_target.html(elm.find('> .node > .node').children());
						func_scroll();
						
						return elm_target;
					});
				}
			}).on('change', '.node > div + div > div > fieldset input[name$=\"[use_date]\"]', function(e) {
				
				func_update_type_network_use_date(this);
			}).on('change', 'input[name$=\"[object_only]\"]', function() {
				
				func_update_type_network_object_only(this);
			});
		});";
		
		$return .= EnucleateValueTypeModule::getModulesScripts();
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
	
		if ($method == "add") {
			
			$this->html = '<form id="f:data_model:insert-'.$id.'">'.$this->createType(($id == StoreType::TYPE_CLASS_REVERSAL ? 'reversal' : ($id == StoreType::TYPE_CLASS_CLASSIFICATION ? 'classification' : 'type'))).'</form>';
		}
		if ($method == "edit") {
			
			if (!static::checkClearanceType($id)) {
				return;
			}
			
			$this->html = '<form id="f:data_model:update-'.$id.'">'.$this->createType($id).'</form>';
		}
		
		if ($method == "selector_value_type") {
			
			$str_name = str_replace(['[object_description_value_type_base]', '[object_sub_description_value_type_base]'], '', $value['name']);
			$ref_type_id = arrParseRecursive($value['ref_type_id'], TYPE_INTEGER);
			
			$html_object_description_options = $this->createTypeObjectDescriptionValueTypeOptions((int)$value['type_id'], $str_name, $value['value_type'], $ref_type_id, $value['has_multi'], $value['has_default_value'], $value['in_name'], ($value['value_settings'] ?: []));
			
			$this->html = ['object_description_options' => $html_object_description_options];
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
				
			SiteEndEnvironment::setFeedback('filter_model', $value, true);
				
			$this->refresh_table = true;
		}
		
		if ($method == "edit_condition") {
			
			$type_id = toolbar::getFilterTypeID();
			
			$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, 0);
			$arr_condition_self = self::parseTypeCondition($type_id, $arr_condition['object']);
			$arr_model_conditions = ParseTypeFeatures::parseTypeModelConditions($type_id, $arr_condition['model_object']);
			
			$this->html = '<form data-method="update_condition">
				'.$this->createTypeCondition($type_id, $arr_condition_self, $arr_model_conditions)
				.'<input type="submit" data-tab="condition" name="default_condition" value="'.getLabel('lbl_remove').' '.getLabel('lbl_conditions').'" />'
				.'<input type="submit" value="'.getLabel('lbl_apply').' '.getLabel('lbl_settings').'" />'
			.'</form>';
		}
		
		if ($method == "update_condition") {
			
			$type_id = toolbar::getFilterTypeID();
			
			if ($_POST['default_condition']) {
				
				$arr_condition_self = [];
				$arr_model_conditions = [];
				
				SiteEndEnvironment::setFeedback('condition_id', false, true);
			} else {
				
				$arr_files = ($_FILES['condition'] ? arrRearrangeParams($_FILES['condition']) : []);
				
				$arr_condition_self = self::parseTypeCondition($type_id, $_POST['condition'], $arr_files);
				$arr_model_conditions = ParseTypeFeatures::parseTypeModelConditions($type_id, $_POST['model_conditions']);
				
				SiteEndEnvironment::setFeedback('condition_id', 0, true);
			}
			
			$has_changed = cms_nodegoat_custom_projects::handleProjectTypeCondition($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_condition_self, $arr_model_conditions);
			
			if ($has_changed) {
				toolbar::checkActiveScenario(false);
			}
			
			SiteEndEnvironment::setFeedback('condition', ($arr_condition_self ? true : false));
			SiteEndEnvironment::setFeedback('condition_model_conditions', ($arr_model_conditions ? true : false));
			$this->msg = true;
		}
		
		if ($method == "store_condition") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage">
				'.$this->createSelectCondition($type_id, true).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="do_discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_condition") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage open" data-method="return_condition">
				'.$this->createSelectCondition($type_id).'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "return_condition") {
			
			$type_id = $id;
			
			if ($_POST['condition_id']) {
				
				$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], false, $_POST['condition_id'], ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
				$arr_condition_self = self::parseTypeCondition($type_id, $arr_condition['object']);
				$arr_model_conditions = ParseTypeFeatures::parseTypeModelConditions($type_id, $arr_condition['model_object']);
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
					".DBFunctions::group2String("CONCAT('<strong>', nodegoat_t_des.name, '</strong><br />', nodegoat_t_des.text)", '<br />', 'ORDER BY nodegoat_t_des.sort')."
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
					".DBFunctions::group2String('nodegoat_to_des.name', '<br />', 'ORDER BY nodegoat_to_des.sort')."
						FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
					WHERE nodegoat_to_des.type_id = nodegoat_t.id
				)";
				$sql_object_descriptions_count = "(SELECT
					COUNT(nodegoat_to_des.id)
						FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
					WHERE nodegoat_to_des.type_id = nodegoat_t.id
				)";
				$sql_object_sub_details = "(SELECT
					".DBFunctions::group2String("CONCAT(nodegoat_tos_det.name, ' (', COALESCE((SELECT
						".DBFunctions::group2String('nodegoat_tos_des.name', ', ', 'ORDER BY nodegoat_tos_des.sort')."
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
			
			$sql_index = 'nodegoat_t.id';
			
			if ($id =='reversals') {

				$sql_table = DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des ON (nodegoat_to_des.type_id = nodegoat_t.id AND nodegoat_to_des.id_id = ".StoreType::getSystemTypeObjectDescriptionID(StoreType::getSystemTypeID('reversal'), 'reference').")
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t_ref ON (nodegoat_t_ref.id = nodegoat_to_des.ref_type_id)
				";
			} else {
				
				$sql_table = DB::getTable('DEF_NODEGOAT_TYPES').' nodegoat_t';
			}

			$sql_where = "nodegoat_t.class = ".($id == 'classifications' ? StoreType::TYPE_CLASS_CLASSIFICATION : ($id == 'reversals' ? StoreType::TYPE_CLASS_REVERSAL : StoreType::TYPE_CLASS_TYPE));
			
			if (Settings::get('domain_administrator_mode')) {
				
				$arr_filter_model = (SiteStartEnvironment::getFeedback('filter_model') ?: []);
				
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
				$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_row['definitions'] ? strEscapeHTML(Labels::parseTextVariables($arr_row['definitions'])) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['definitions_count'].'</span></span>';
				if ($id != 'reversals') {
					$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_row['object_descriptions'] ? strEscapeHTML(Labels::parseTextVariables($arr_row['object_descriptions'])) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['object_descriptions_count'].'</span></span>';
					if ($id != 'classifications') {
						$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_row['object_sub_details'] ? strEscapeHTML(Labels::parseTextVariables($arr_row['object_sub_details'])) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)$arr_row['object_sub_details_count'].'</span></span>';
					}
				} else {
					$arr_data[] = ($arr_row['reversal_ref_type_id'] ? $arr_row['reversal_ref_type_name'] : '<span class="icon" data-category="status">'.getIcon('min').'</span>');
				}
				$arr_data[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data del quick empty" value="empty" /><input type="button" class="data del quick del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		// QUERY
		
		if (($method == "insert" || $method == "update") && $this->is_discard) {
			
			if ($method == "update") {
				
				$arr_type_set = StoreType::getTypeSet($id);
				$what = $arr_type_set['type']['class'];
			} else {
				
				$what = $id;
			}
							
			$this->html = $this->createAddType($what);
			return;
		}
		
		if ($method == "insert" || ($method == "update" && (int)$id)) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN || ($method == 'update' && !static::checkClearanceType($id))) {
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
				'clearance_edit' => ($this->do_show_user_settings ? (int)$_POST['clearance_edit'] : null),
				'use_object_name' => (bool)$_POST['use_object_name'],
				'object_name_in_overview' => (bool)$_POST['object_name_in_overview'],
				'class' => (int)$_POST['class']
			];
			
			$arr_definitions = [];
			
			foreach ($_POST['definition_name'] as $key => $value) {
				$arr_definitions[] = [
					'definition_name' => $_POST['definition_name'][$key],
					'definition_text' => $_POST['definition_text'][$key],
					'definition_id' => (int)$_POST['definition_id'][$key]
				];
			}
			
			if ($_POST['class'] == StoreType::TYPE_CLASS_REVERSAL) {
				
				$arr_details['reversal_mode'] = (int)$_POST['reversal_mode'];
				$reversal_ref_type_id = $_POST['reversal_ref_type_id'][$_POST['reversal_reference_class']];
				$arr_details['reversal_ref_type_id'] = (int)$reversal_ref_type_id;
				$arr_details['reversal_resource_path'] = (bool)$_POST['reversal_resource_path'];
				
				$arr_object_descriptions = false;
			} else {
				
				$arr_object_descriptions = [];
				
				foreach ($_POST['object_descriptions'] as $value) {
					
					$object_description_value_type_base = $value['object_description_value_type_base'];
					
					$object_description_value_type_settings = false;
					if ($value['object_description_value_type_settings'] && is_array($value['object_description_value_type_settings'])) {
						$object_description_value_type_settings = $value['object_description_value_type_settings'];
					}
					
					if ($object_description_value_type_base == 'reference_mutable') {
						$object_description_ref_type_id = (array)$value['object_description_ref_type_id']['reference_mutable'];
					} else {
						$object_description_ref_type_id = (int)($object_description_value_type_base == 'reversal' ? $value['object_description_ref_type_id']['reversal'] : ($object_description_value_type_base == 'classification' ? $value['object_description_ref_type_id']['classification'] : $value['object_description_ref_type_id']['type']));
					}
					
					$arr_object_descriptions[] = [
						'object_description_name' => $value['object_description_name'],
						'object_description_value_type_base' => $object_description_value_type_base,
						'object_description_value_type_settings' => $object_description_value_type_settings,
						'object_description_is_required' => (int)$value['object_description_is_required'],
						'object_description_is_unique' => (int)$value['object_description_is_unique'],
						'object_description_has_multi' => (int)$value['object_description_has_multi'],
						'object_description_ref_type_id' => $object_description_ref_type_id,
						'object_description_in_name' => (int)$value['object_description_in_name'],
						'object_description_in_search' => (int)$value['object_description_in_search'],
						'object_description_in_overview' => (int)$value['object_description_in_overview'],
						'object_description_is_identifier' => ($this->do_show_api_settings ? (int)$value['object_description_is_identifier'] : null),
						'object_description_clearance_edit' => ($this->do_show_user_settings ? (int)$value['object_description_clearance_edit'] : null),
						'object_description_clearance_view' => ($this->do_show_user_settings ? (int)$value['object_description_clearance_view'] : null),
						'object_description_id' => (int)$value['object_description_id']
					];
				}
				
				$arr_object_sub_details = [];
				
				foreach ((array)$_POST['object_sub_details'] as $arr_object_sub) {
					
					if (!$this->do_show_user_settings) {
						unset($arr_object_sub['object_sub_details']['object_sub_details_clearance_edit'], $arr_object_sub['object_sub_details']['object_sub_details_clearance_view']);
					}
					
					$arr_object_sub_descriptions = [];
					
					if ($arr_object_sub['object_sub_descriptions']) {
						
						foreach ($arr_object_sub['object_sub_descriptions'] as $value) {
							
							$object_sub_description_value_type_base = $value['object_sub_description_value_type_base'];
							
							$object_sub_description_value_type_settings = false;
							if ($value['object_sub_description_value_type_settings'] && is_array($value['object_sub_description_value_type_settings'])) {
								$object_sub_description_value_type_settings = $value['object_sub_description_value_type_settings'];
							}
							
							if ($object_sub_description_value_type_base == 'reference_mutable') {
								$object_sub_description_ref_type_id = (array)$value['object_sub_description_ref_type_id']['reference_mutable'];
							} else {
								$object_sub_description_ref_type_id = (int)($object_sub_description_value_type_base == 'reversal' ? $value['object_sub_description_ref_type_id']['reversal'] : ($object_sub_description_value_type_base == 'classification' ? $value['object_sub_description_ref_type_id']['classification'] : $value['object_sub_description_ref_type_id']['type']));
							}
							
							$arr_object_sub_descriptions[] = [
								'object_sub_description_name' => $value['object_sub_description_name'],
								'object_sub_description_value_type_base' => $object_sub_description_value_type_base,
								'object_sub_description_value_type_settings' => $object_sub_description_value_type_settings,
								'object_sub_description_use_object_description_id' => (int)$value['object_sub_description_use_object_description_id'],
								'object_sub_description_ref_type_id' => $object_sub_description_ref_type_id,
								'object_sub_description_is_required' => (int)$value['object_sub_description_is_required'],
								'object_sub_description_in_name' => (int)$value['object_sub_description_in_name'],
								'object_sub_description_in_search' => (int)$value['object_sub_description_in_search'],
								'object_sub_description_in_overview' => (int)$value['object_sub_description_in_overview'],
								'object_sub_description_clearance_edit' => ($this->do_show_user_settings ? (int)$value['object_sub_description_clearance_edit'] : null),
								'object_sub_description_clearance_view' => ($this->do_show_user_settings ? (int)$value['object_sub_description_clearance_view'] : null),
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
			
			$store_type = new StoreType(($method == 'update' ? $id : false), $_SESSION['USER_ID']);
			
			if ($this->is_confirm === false) {
				return;
			} else if ($this->is_confirm) {
				
				$store_type->setMode(null, StoreType::CHECK_CONFIRMED);
			}
			
			try {
				
				$type_id = $store_type->store($arr_details, $arr_definitions, $arr_object_descriptions, $arr_object_sub_details);
			} catch (Exception $e) {
				
				if ($store_type->getStoreConfirm()) {
					
					$this->html = $store_type->getStoreConfirm();
					$this->do_confirm = true;
					return;
				}
					
				throw($e);
			}
			
			StoreType::setTypesObjectPaths();
			
			$arr_filter_model = (SiteStartEnvironment::getFeedback('filter_model') ?: []);
			
			if ($method == 'insert' && $arr_filter_model['project_id']) {
				
				if ($_SESSION['CUR_USER'][DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')][$arr_filter_model['project_id']]) {
					
					$custom_project = new StoreCustomProject($arr_filter_model['project_id']);
					$custom_project->addTypes($type_id);
				}
			}
			
			$this->html = $this->createAddType($_POST['class']);
			$this->refresh_table = true;
			$this->msg = true;
		}
	
		if ($method == "empty" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN || !static::checkClearanceType($id)) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			if ($this->is_confirm === null) {
				
				$arr_type_set = StoreType::getTypeSet($id);
				$str_what = StoreType::getTypeClassName($arr_type_set['type']['class']).' <strong>'.$arr_type_set['type']['name'].'</strong>';
				
				Labels::setVariable('what', $str_what);
				$this->html = getLabel('conf_empty');
				$this->do_confirm = true;
				return;
			} else if ($this->is_confirm) {

				$storage = new StoreTypeObjects($id, false, $_SESSION['USER_ID']);
				
				$storage->clearTypeObjects();
									
				$this->msg = true;
			}
		}
		
		if ($method == "del" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN || !static::checkClearanceType($id)) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			if ($this->is_confirm === null) {
				
				$arr_type_set = StoreType::getTypeSet($id);
				$str_what = StoreType::getTypeClassName($arr_type_set['type']['class']).' <strong>'.$arr_type_set['type']['name'].'</strong>';
				
				Labels::setVariable('what', $str_what);
				$this->html = getLabel('conf_delete');
				$this->do_confirm = true;
				return;
			} else if ($this->is_confirm) {
				
				$store_type = new StoreType($id, $_SESSION['USER_ID']);
				
				$store_type->delType();
				
				$this->refresh_table = true;			
				$this->msg = true;
			}
		}
	}
	
	public static function parseTypeNetwork($arr, $value_type = false) {
		
		return StoreType::parseTypeNetwork($arr, $value_type, ($_SESSION['NODEGOAT_CLEARANCE'] ?? 0));
	}
	
	public static function parseTypeNetworkModePick($arr) {
		
		return StoreType::parseTypeNetworkModePick($arr, ($_SESSION['NODEGOAT_CLEARANCE'] ?? 0));
	}
		
	public static function parseTypeCondition($type_id, $arr, $arr_files = []) {
		
		return ParseTypeFeatures::parseTypeCondition($type_id, $arr, $arr_files, ($_SESSION['NODEGOAT_CLEARANCE'] ?? 0));
	}
		
	public static function checkClearanceType($type_id, $do_error = true) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$has_clearance = ($arr_type_set && $arr_type_set['type']['class'] != StoreType::TYPE_CLASS_SYSTEM);
		
		if (!$has_clearance && $do_error) {
			error(getLabel('msg_not_allowed'));
		}
		
		return $has_clearance;
	}
	
	public static function checkClearanceTypeConfiguration($type, $arr_type_set, $object_description_id, $object_sub_details_id = false, $object_sub_description_id = false) {
		
		$has_clearance = StoreType::checkTypeConfigurationUserClearance($arr_type_set, ($_SESSION['NODEGOAT_CLEARANCE'] ?? 0), $object_description_id, $object_sub_details_id, $object_sub_description_id, $type);
		
		return $has_clearance;
	}
}
