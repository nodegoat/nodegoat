<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_reconcile extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Reconcile';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'data_entry' => [],
		'data_view' => [],
		'data_filter' => []
	];
	
	public $form_name = 'template';
	
	protected $has_feedback_template_process = false;
	protected $is_done_template_process = false;
	
	protected static $num_limit = 5;
	protected static $num_pattern_threshold = 60;
	protected static $num_pattern_distance = 6;
	protected static $do_pattern_complete = false;
	protected static $num_score_threshold = 25;
	protected static $num_score_match_difference = 25;
	protected static $do_prioritise_match_pattern_pair = false;
	protected static $num_score_overlap_difference = 50;
	protected static $num_preview_characters = 1000;
	
	const WRITE_MODE_APPEND = 1;
	const WRITE_MODE_OVERWRITE = 2;
	const TARGET_SELF_MODE_FIRST = 1;
	const TARGET_SELF_MODE_ALL = 2;
	const AUTO_MODE_ALL = 1;
	const AUTO_MODE_CERTAIN = 2;
	const AUTO_MODE_DISCARD_NO_RESULT = 3;
	
	public function createTemplate($arr_template = []) {

		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);

		$arr_types_all_source = StoreType::getTypes(array_keys($arr_project['types']));
		$arr_types_all_test = $arr_types_all_source;
		
		foreach ($arr_types_all_source as $cur_type_id => $arr_type) {
			
			if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
				unset($arr_types_all_source[$cur_type_id], $arr_types_all_test[$cur_type_id]);
			}
			
		}
		
		$type_id = false;
		$test_type_id = false;
		
		if ($arr_template) {
			$type_id = $arr_template['type_id'];
			$test_type_id = $arr_template['test_type_id'];
		}
		
		$html = '<div class="reconcile template"'.($this->form_name != 'template' ? ' data-form_name="'.$this->form_name.'"' : '').'>
		
			<div class="options">
			
				<fieldset><legend>'.getLabel('lbl_reconcile_source').'</legend>
					<ul>
						<li>
							<label>'.getLabel('lbl_type').'</label>
							<select name="'.$this->form_name.'[type_id]" id="y:data_reconcile:get_source-0">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all_source, $type_id, true)).'</select>';
							
							Labels::setVariable('application', getLabel('lbl_type'));
									
							$html .= '<input type="hidden" name="'.$this->form_name.'[type_filter]" value="'.strEscapeHTML(value2JSON($arr_template['type_filter'])).'" />'
								.'<button type="button" id="y:data_filter:configure_application_filter-0" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>';
							
						$html .= '</li>
						<li>
							<label>'.getLabel('lbl_source').'</label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label></label>
							'.$this->createTemplateValues($this->form_name.'[values]', $type_id, ($arr_template['values'] ?: [])).'			
						</li>
					</ul>
				</fieldset>
				
			</div>
			
			<div class="options">
			
				<fieldset><legend>'.getLabel('lbl_reconcile_test').'</legend>
					<ul>
						<li>
							<label>'.getLabel('lbl_type').'</label>
							<select name="'.$this->form_name.'[test_type_id]" id="y:data_reconcile:get_test-0">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all_test, $test_type_id, true)).'</select>';
							
							Labels::setVariable('application', getLabel('lbl_type'));
									
							$html .= '<input type="hidden" name="'.$this->form_name.'[test_type_filter]" value="'.strEscapeHTML(value2JSON($arr_template['test_type_filter'])).'" />'
								.'<button type="button" id="y:data_filter:configure_application_filter-0" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>';
						
						$html .= '</li>
						<li>
							<label>'.getLabel('lbl_pattern_pairs').'</label>
							<div><input type="checkbox" name="'.$this->form_name.'[test_pattern_pairs]" value="1" title="'.getLabel('inf_reconcile_test_pattern_pairs').'" '.($arr_template['test_pattern_pairs'] ? ' checked="checked"' : '').' /></div>
						</li>
						<li>
							<label>'.getLabel('lbl_value').'</label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label></label>
							'.$this->createTemplateValues($this->form_name.'[test_values]', $test_type_id, ($arr_template['test_values'] ?: [])).'			
						</li>
					</ul>
				</fieldset>
			
			</div>
			
			<div class="options" id="y:data_reconcile:get_target-0">
			
				'.$this->createTemplateTarget($arr_template).'
				
			</div>
						
		</div>';
		
		return $html;
	}
	
	protected function createTemplateValues($str_name, $type_id, $arr_values = []) {
		
		$arr_sorter = [];

		if (!$arr_values) {
			$arr_values[] = [];
		}
		array_unshift($arr_values, []); // Empty run for sorter source
		
		foreach ($arr_values as $key => $arr_value) {
			
			$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
				
			$arr_sorter[] = [
				'source' => ($key === 0 ? true : false),
				'value' => '<select name="'.$str_name.'['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown(static::getTypeObjectDescriptionsText($type_id), $arr_value['id'], true)).'</select>'
			];
		}
		
		return cms_general::createSorter($arr_sorter, true);
	}

	protected function createTemplateTarget($arr_template = []) {
				
		if (!$arr_template['type_id'] || !$arr_template['values'] || !$arr_template['test_type_id']) {
			
			return '<section class="info attention">
				'.getLabel('inf_reconcile_select_source_test').'
			</section>';
		}
		
		$is_text_tags = static::isReconcileTextTagsSource($arr_template);
		
		$html = '<fieldset>
			<legend>'.getLabel('lbl_reconcile_target').'</legend>
			<ul>
				<li>
					<label>'.getLabel('lbl_save').'</label>
					<div>'
						.'<label><input type="radio" name="'.$this->form_name.'[mode_write]" value="'.static::WRITE_MODE_OVERWRITE.'"'.($arr_template['mode_write'] == static::WRITE_MODE_OVERWRITE ? ' checked="checked"' : '').'/><span>'.getLabel('lbl_overwrite').'</span></label>'
						.'<label><input type="radio" name="'.$this->form_name.'[mode_write]" value="'.static::WRITE_MODE_APPEND.'"'.(!$arr_template['mode_write'] || $arr_template['mode_write'] == static::WRITE_MODE_APPEND ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_append').'</span></label>'
					.'</div>
				</li>';
				
				if ($is_text_tags) {

					$html .= '<li>
						<label>'.getLabel('lbl_tags').'</label>
						<div title="'.getLabel('inf_reconcile_store_tags').'">'.cms_general::createSelectorRadio(static::getTemplateOptionsTargetSelf(), $this->form_name.'[target_self]', $arr_template['target_self']).'</div>
					</li>';
				}
			
				$html .= '<li>
					<label>'.getLabel('lbl_object_description').'</label>
					<select name="'.$this->form_name.'[target_id]">'.Labels::parseTextVariables(cms_general::createDropdown(static::getTypeObjectDescriptionsReference($arr_template['type_id'], $arr_template['test_type_id']), $arr_template['target_id'], true)).'</select>
				</li>
			</ul>
		</fieldset>';
		
		return $html;
	}
	
	public function createRunTemplate($arr_template) {
		
		$system_type_id = StoreType::getSystemTypeID('reconciliation');
		$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
		$system_object_id = $arr_template['identifier'];
			
		$return = '<div class="reconcile run options">
			
			<fieldset>
				<legend>'.getLabel('lbl_status').' <input type="button" id="y:data_reconcile:reset_process-'.$arr_template['identifier'].'" class="data del quick" value="reset" /></legend>
				<ul>
					<li>
						<label>'.getLabel('lbl_type_module_objects_processed').'</label>
						<div>'.FilterTypeObjects::getModuleObjectTypeCount($system_object_description_id, $system_object_id, $system_type_id).'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_reconcile_batch_amount').'</label>
						<div><input type="range" step="1" min="1" max="20" /><input type="number" name="reconcile[settings][batch]" step="1" min="1" max="20" value="'.static::$num_limit.'"/></div>
					</li>
					<li>
						<label>'.getLabel('lbl_reconcile_store_auto').'</label>
						<div>'.cms_general::createSelectorRadio(static::getProcessTemplateOptionsAuto(), 'reconcile[settings][auto]').'</div>
					</li>
				</ul>
			</fieldset>
			
			<fieldset>
				<legend>'.getLabel('lbl_scoring').'</legend>
				<ul>
					<li>
						<label>'.getLabel('lbl_reconcile_threshold').'</label>
						<div><label>'.getLabel('lbl_any').'</label><input type="range" step="1" min="1" max="100" /><label>'.getLabel('lbl_best').'</label><input type="number" name="reconcile[settings][score_threshold]" step="1" min="1" max="100" title="'.getLabel('inf_reconcile_threshold').'" value="'.static::$num_score_threshold.'"/></div>
					</li>
					<li>
						<label>'.getLabel('lbl_reconcile_match_difference').'</label>
						<div><label>'.getLabel('lbl_best').'</label><input type="range" step="1" min="0" max="99" /><label>'.getLabel('lbl_any').'</label><input type="number" name="reconcile[settings][score_match_difference]" step="1" min="0" max="99" title="'.getLabel('inf_reconcile_match_difference').'" value="'.static::$num_score_match_difference.'"/></div>
					</li>';
					
					if ($arr_template['test_pattern_pairs']) {
						
						$return .= '<li>
							<label>'.getLabel('lbl_reconcile_match_prioritise_pattern_pair').'</label>
							<div><input type="checkbox" name="reconcile[settings][prioritise_match_pattern_pair]" value="1" title="'.getLabel('inf_reconcile_match_prioritise_pattern_pair').'" '.(static::$do_prioritise_match_pattern_pair ? ' checked="checked"' : '').' /></div>
						</li>';
					}
					
					$return .= '<li>
						<label>'.getLabel('lbl_reconcile_overlap_difference').'</label>
						<div><label>'.getLabel('lbl_best').'</label><input type="range" step="1" min="0" max="99" /><label>'.getLabel('lbl_any').'</label><input type="number" name="reconcile[settings][score_overlap_difference]" step="1" min="0" max="99" title="'.getLabel('inf_reconcile_overlap_difference').'" value="'.static::$num_score_overlap_difference.'"/></div>
					</li>
				</ul>
			</fieldset>
			
			<fieldset>
				<legend>'.getLabel('lbl_pattern').'</legend>
				<ul>
					<li>
						<label>'.getLabel('lbl_reconcile_pattern_threshold').'</label>
						<div><label>Any</label><input type="range" step="1" min="0" max="100" /><label>Word</label><input type="number" name="reconcile[settings][pattern_threshold]" step="1" min="0" max="100" title="'.getLabel('inf_reconcile_pattern_threshold').'" value="'.static::$num_pattern_threshold.'"/></div>
					</li>
					<li>
						<label>'.getLabel('lbl_reconcile_pattern_distance').'</label>
						<div><input type="number" name="reconcile[settings][pattern_distance]" step="1" min="1" title="'.getLabel('inf_reconcile_pattern_distance').'" value="'.static::$num_pattern_distance.'"/></div>
					</li>';
					
					if ($arr_template['target_self']) {
						
						$return .= '<li>
							<label>'.getLabel('lbl_reconcile_pattern_complete').'</label>
							<div><input type="checkbox" name="reconcile[settings][pattern_complete]" value="1" title="'.getLabel('inf_reconcile_pattern_complete').'" '.(static::$do_pattern_complete ? ' checked="checked"' : '').' /></div>
						</li>';
					}
					
				$return .= '</ul>
			</fieldset>
		</div>';
						
		return $return;
	}
		
	public function createProcessTemplate($arr_template, $arr_reconcile, $arr_feedback) {
		
		$system_object_id = $arr_template['identifier'];
		
		$arr_reconcile = ($arr_feedback['reconcile'] ? data_reconcile::parseTemplateProcess($system_object_id, $arr_feedback['reconcile']) : []);
		
		$this->handleProcessTemplatePatternPairs($arr_template, $arr_reconcile['pattern_pairs']);
		unset($arr_reconcile['pattern_pairs']);
		
		$html = $this->createProcessTemplateStore($arr_template, $arr_reconcile);
		unset($arr_reconcile['results']);

		while (!$this->is_done_template_process && !$this->has_feedback_template_process) {
			
			$html = $this->doProcessTemplate($arr_template, $arr_reconcile);
			
			if ($this->is_done_template_process || $this->has_feedback_template_process) {
				break;
			} else {
				$arr_template = static::getTemplate($system_object_id);
			}
		}
		
		$html = '<div class="reconcile template-process">
			'.$html.'
		</div>';
		
		return [
			'is_done' => $this->is_done_template_process,
			'has_feedback' => $this->has_feedback_template_process,
			'html' => $html
		];
	}
	
	protected function doProcessTemplate($arr_template, $arr_reconcile = []) {
				
		$system_type_id = StoreType::getSystemTypeID('reconciliation');
		$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
		$system_object_id = $arr_template['identifier'];
		
		$type_id = $arr_template['type_id'];
		$test_type_id = $arr_template['test_type_id'];
		
		$arr_source_map = [];
		foreach ($arr_template['values'] as $arr_source_value) {
			$arr_source_map[$arr_source_value['id']] = true;
		}
		$arr_test_map = [];
		foreach ($arr_template['test_values'] as $arr_test_value) {
			$arr_test_map[$arr_test_value['id']] = true;
		}
		
		$arr_type_set = StoreType::getTypeSetByFlatMap($type_id, $arr_source_map);
		
		$is_text = static::isReconcileTextSource($arr_template);
		
		$arr_filter = [];
		$arr_filter_not_empty = [];

		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			$arr_filter_not_empty['object_definitions'][$object_description_id] = ['transcension' => ['value' => 'not_empty']];
		}
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_sub_object_description) {
				$arr_filter_not_empty['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id] = ['transcension' => ['value' => 'not_empty']];
			}
		}
	
		if ($arr_template['type_filter']) {
			$arr_filter[] = FilterTypeObjects::convertFilterInput($arr_template['type_filter']);
		}
		
		$arr_filter[]['object_filter'][] = $arr_filter_not_empty;
		
		$num_limit = ($arr_reconcile['settings']['batch'] ?: static::$num_limit);
		$arr_object_ids = static::getModuleObjectTypeObjects($system_object_id, $type_id, $arr_filter, 0, true, $num_limit);
		$arr_object_ids = array_keys($arr_object_ids);
		
		if (!$arr_object_ids) {
			
			$this->is_done_template_process = true;
			
			$count = (int)FilterTypeObjects::getModuleObjectTypeCount($system_object_description_id, $system_object_id, $system_type_id);
			$arr_result = ['count' => $count];
			
			return $this->createProcessTemplateStoreCheck($arr_result);
		}
		
		$arr_test_filter = false;
		if ($arr_template['test_type_filter']) {
			$arr_test_filter = FilterTypeObjects::convertFilterInput($arr_template['test_type_filter']);
		}
				
		$reconcile = new ReconcileTypeObjectsValues($test_type_id);
		$reconcile->addTest($arr_template['test_pattern_pairs'], $arr_test_map, $arr_test_filter, toolbar::getTypeConditions($test_type_id));
		$reconcile->setSourceTypeObjects($type_id, $arr_source_map, ['objects' => $arr_object_ids], toolbar::getTypeConditions($type_id));

		$reconcile->setPattern($arr_reconcile['settings']['pattern_threshold'], $arr_reconcile['settings']['pattern_distance'], $arr_reconcile['settings']['pattern_complete']);
		$reconcile->setThreshold($arr_reconcile['settings']['score_threshold'], $arr_reconcile['settings']['score_match_difference'], $arr_reconcile['settings']['score_overlap_difference'], ($arr_template['test_pattern_pairs'] ? $arr_reconcile['settings']['prioritise_match_pattern_pair'] : false));
		
		$reconcile->init();
		
		$mode_result = ReconcileTypeObjectsValues::RESULT_MODE_DEFAULT;
		if ($is_text) {
			
			$mode_result = ReconcileTypeObjectsValues::RESULT_MODE_TAG;
			
			if ($arr_template['mode_write'] == static::WRITE_MODE_OVERWRITE) {
				$mode_result = $mode_result | ReconcileTypeObjectsValues::RESULT_MODE_TAGS_OVERWRITE_TYPE;
			}
		}
		
		$arr_result = $reconcile->getResult($mode_result);
		
		if ($arr_result['error'] !== null) {
			
			$this->has_feedback_template_process = true;
			
			$html = $this->createProcessTemplateStoreCheck($arr_result);
		} else {
			
			$html = $this->createProcessTemplateResultCheck($arr_template, $arr_reconcile, $arr_result);
		}

		return $html;
	}

	protected function createProcessTemplateStore($arr_template, $arr_reconcile) {
		
		if (!$arr_reconcile['results']) {
			return;
		}
		
		$system_object_id = $arr_template['identifier'];
		$type_id = $arr_template['type_id'];
		$test_type_id = $arr_template['test_type_id'];
		
		$arr_target_self_type_set = false;
		$arr_target_type_set = false;
				
		if ($arr_template['target_self']) {
			
			$arr_target_map = [];
			foreach ($arr_template['values'] as $arr_source_value) {
				$arr_target_map[$arr_source_value['id']] = true;
			}
		
			$arr_target_self_type_set = StoreType::getTypeSetByFlatMap($type_id, $arr_target_map);
		}
		if ($arr_template['target_id']) {
			
			$arr_target_type_set = StoreType::getTypeSetByFlatMap($type_id, [$arr_template['target_id'] => true]);
		}
		
		$arr_objects = $arr_reconcile['results'];
		
		$has_updates = false;
		$storage_lock = new StoreTypeObjects($type_id, false, ['user_id' => $_SESSION['USER_ID'], 'system_object_id' => $system_object_id], 'lock');
		
		foreach ($arr_objects as $object_id => $arr_values) {
			
			if ($arr_values === false || ($arr_values === [] && $arr_template['mode_write'] == static::WRITE_MODE_APPEND)) {
				continue;
			}
			
			$storage_lock->setObjectID($object_id);
			
			try {
				$storage_lock->handleLockObject();
			} catch (Exception $e) {
				$arr_locked[] = $e;
			}
			
			$has_updates = true;
		}
		
		if ($arr_locked) {
			
			$storage_lock->removeLockObject(); // Remove locks from all possible successful ones
			
			foreach ($arr_locked as &$e) {
							
				$e = Trouble::strMsg($e); // Convert to message only
			}
			unset($e);
			
			$this->has_feedback_template_process = true;
			
			return $this->createProcessTemplateStoreCheck(['locked' => $arr_locked]);
		}
		
		if ($has_updates) {
				
			$storage_lock->upgradeLockObject(); // Apply permanent lock
			
			$storage = new StoreTypeObjects($type_id, false, ['user_id' => $_SESSION['USER_ID'], 'system_object_id' => $system_object_id]);
			$storage->setAppend(($arr_template['mode_write'] == static::WRITE_MODE_APPEND && !$arr_template['target_self'] ? true : false));
			$storage->setMode(StoreTypeObjects::MODE_UPDATE, false);

			GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction
			
			DB::startTransaction('data_reconcile_store');
			
			try {
				
				$do_store = false;
				$object_id_processing = 0;
				
				foreach ($arr_objects as $object_id => $arr_results) {
					
					if ($arr_results['select'] === [] && $arr_template['mode_write'] == static::WRITE_MODE_APPEND) {
						continue;
					}
					
					$object_id_processing = $object_id;
				
					$storage->setObjectID($object_id);
					
					$arr_object_definitions = [];
					$arr_object_subs = [];
					
					if ($arr_template['target_self']) {

						foreach ($arr_target_self_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
							
							$str_value = $arr_results['self']['values']['object_description-'.$object_description_id];
							$arr_select_object_ids = ($arr_results['self']['select']['object_description-'.$object_description_id] ?? []);
							$is_store = ($arr_results['self']['store'] ?? false);
							
							if ($arr_template['mode_write'] == static::WRITE_MODE_APPEND && !$is_store && !$arr_select_object_ids) {
								continue;
							}
							
							$arr_option_objects = ($arr_results['self']['options']['object_description-'.$object_description_id] ?? null);
							
							if ($arr_option_objects !== null && !$is_store) {
									
								if ($arr_template['target_self'] == static::TARGET_SELF_MODE_ALL && $arr_select_object_ids) {
									
									$reconcile = new ReconcileTypeObjectsValues($test_type_id);
								
									// Remove all reconciled tags
									
									$arr_remove_objects = [];
									
									foreach ($arr_option_objects as $result_object_id => $arr_pattern) {
										
										$arr_remove_objects[$result_object_id] = false;
									}

									$str_value = StoreTypeObjects::updateObjectDefinitionTextTagsObject($str_value, [$test_type_id => $arr_remove_objects]);
									
									// Get leftover tags and clear tags
									
									$arr_tags = $reconcile->getTextTags($str_value);
									
									$str_value = StoreTypeObjects::clearObjectDefinitionText($str_value, StoreTypeObjects::TEXT_TAG_OBJECT);
									
									// Add identical value tags for all selected reconciled tags
									
									foreach ($arr_select_object_ids as $select_object_id) {
										
										$str_tag_identifier = $test_type_id.'_'.$select_object_id;
										
										$arr_pattern = $arr_option_objects[$select_object_id];
										$pattern = new PatternEntity($arr_pattern);
										
										$arr_matches = $pattern->getPatternMatches($str_value);
										
										foreach ($arr_matches as $arr_match) {
											
											$arr_tags = $reconcile->addTextTag($arr_tags, $str_tag_identifier, $arr_match['position'], ($arr_match['position'] + strlen($arr_match['string'])));
										}
									}
									
									// Print result
									
									$arr_tags = $reconcile->getTextTagsContextualised($arr_tags);
									
									$str_value = $reconcile->printTextTags($str_value, $arr_tags);
								} else {
									
									// Remove or keep selected or deselected reconciled tags

									$arr_objects_select_state = [];
									
									foreach ($arr_option_objects as $result_object_id => $bool) {
										
										$arr_objects_select_state[$result_object_id] = in_array($result_object_id, $arr_select_object_ids);
									}
									
									$str_value = StoreTypeObjects::updateObjectDefinitionTextTagsObject($str_value, [$test_type_id => $arr_objects_select_state]);
								}
							}
							
							$arr_object_definitions[$object_description_id] = [
								'object_description_id' => $object_description_id,
								'object_definition_value' => $str_value
							];
						}
					}
					
					if ($arr_template['target_id']) {
						
						if ($arr_template['target_self'] && !empty($arr_results['self']['store'])) { // In raw/editor mode, but here we want all the tags as well
							
							$arr_results['select'] = [];
							
							foreach ($arr_target_self_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
								
								$str_value = $arr_results['self']['values']['object_description-'.$object_description_id];
								$arr_option_objects = $arr_results['self']['options']['object_description-'.$object_description_id];

								$arr_text_tags = StoreTypeObjects::parseObjectDefinitionText($str_value);
								
								foreach ($arr_text_tags['tags'] as $arr_type_object_id) {
									
									if ($arr_type_object_id['type_id'] != $test_type_id) {
										continue;
									}
									
									$check_object_id = $arr_type_object_id['object_id'];

									if (!isset($arr_option_objects[$check_object_id])) {
										continue;
									}
									
									$arr_results['select'][$check_object_id] = $check_object_id;			
								}
							}
						}
						
						if ($arr_target_type_set['object_sub_details']) {
							
							$object_sub_details_id = key($arr_target_type_set['object_sub_details']);
							$arr_object_sub_details = current($arr_target_type_set['object_sub_details']);
							
							if ($arr_object_sub_details['object_sub_descriptions']) {
							
								$arr_object_sub_description = current($arr_object_sub_details['object_sub_descriptions']);
								$object_sub_description_id = $arr_object_sub_description['object_sub_description_id'];
								
								foreach ($arr_results['select'] as $ref_object_id) {
									
									$arr_object_subs[] = [
										'object_sub' => ['object_sub_details_id' => $object_sub_details_id],
										'object_sub_definitions' => [
											$object_sub_description_id => [
												'object_sub_description_id' => $object_sub_description_id,
												'object_sub_definition_ref_object_id' => $ref_object_id
											]
										]
									];
								}
							} else if ($arr_object_sub_details['object_sub_details']) { // Location reference
								
								$location_ref_object_sub_details_id = $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'];
								
								foreach ($arr_results['select'] as $ref_object_id) {
									
									$arr_object_subs[] = [
										'object_sub' => [
											'object_sub_details_id' => $object_sub_details_id,
											'object_sub_date_chronology' => false,
											'object_sub_location_ref_type_id' => $test_type_id,
											'object_sub_location_ref_object_sub_details_id' => $location_ref_object_sub_details_id,
											'object_sub_location_ref_object_id' => $ref_object_id
										]
									];
								}
							}
						} else {
							
							$arr_object_description = current($arr_target_type_set['object_descriptions']);
							$object_description_id = $arr_object_description['object_description_id'];
							
							$arr_object_definitions[$object_description_id] = [
								'object_description_id' => $object_description_id,
								'object_definition_ref_object_id' => $arr_results['select']
							];
						}
					}
					
					$storage->store([], $arr_object_definitions, $arr_object_subs);
					$do_store = true;
				}
				
				if ($do_store) {
					
					$storage->save();
					
					$storage->commit(true);
				}
			} catch (Exception $e) {
				
				Labels::setVariable('object', $object_id_processing);
				msg(getLabel('msg_object_error'), false, LOG_CLIENT);

				DB::rollbackTransaction('data_reconcile_store');
				throw($e);
			}

			DB::commitTransaction('data_reconcile_store');
			
			$storage_lock->removeLockObject();
		}
		
		static::updateTemplateState($system_object_id, ['object_ids' => array_keys($arr_objects), 'status' => 'done']);
		
		Labels::setVariable('count', count($arr_objects));
		status(getLabel('msg_object_updated'), false, LOG_CLIENT);
		
		return;
	}
	
	protected function handleProcessTemplatePatternPairs($arr_template, $arr_pattern_pairs) {
		
		if (!$arr_pattern_pairs) {
			return;
		}
				
		$system_object_id = $arr_template['identifier'];
		$test_type_id = $arr_template['test_type_id'];
		
		$store_pair = new StorePatternsTypeObjectPair();
								
		foreach ($arr_pattern_pairs as $str_pattern_pair) {
			
			$arr_pattern_pair = JSON2Value($str_pattern_pair);
			
			$object_id = (int)$arr_pattern_pair[0];
			$arr_pattern = $arr_pattern_pair[1];
			$str_identifier = StorePatternsTypeObjectPair::getPatternIdentifier($arr_pattern);
			
			$store_pair->storeTypeObjectPair($test_type_id, $str_identifier, $object_id, $arr_pattern);
		}
		
		$store_pair->commitPairs();
	}
	
	protected function createProcessTemplateStoreCheck($arr_result) {
		
		if ($arr_result['locked'] !== null) {

			$str_locked = '<ul><li>'.implode('</li><li>', $arr_result['locked']).'</li></ul>';
			
			Labels::setVariable('total', count($arr_result['locked']));
			$str_message = parseBody(getLabel('msg_reconcile_stopped').' '.getLabel('msg_object_locked_multi')).parseBody($str_locked);
			
		} else if ($arr_result['error'] !== null) {
			
			Labels::setVariable('object', $arr_result['error']);
			$str_message = parseBody(getLabel('msg_reconcile_error_object'));
		} else {

			Labels::setVariable('count', (int)$arr_result['count']);
			$str_message = parseBody(getLabel('msg_reconcile_done'));
		}
		
		$html = '<section class="info attention body">'.$str_message.'</section>';
	
		return $html;
	}
	
	protected function createProcessTemplateResultCheckAutomation($mode_auto, $arr_template, &$arr_reconcile, &$arr_results) {

		if (!$mode_auto) {
			return;
		}
		
		$func_get_result_objects = function($arr_result) {
			
			$arr_result_option_objects = [];
			$arr_result_select_object_ids = [];
			
			foreach ($arr_result['values'] as $key => $str_value) {
				
				foreach ($arr_result['objects'] as $arr_result_object) {
					
					$test_object_id = $arr_result_object['object_id'];

					if (is_array($arr_result_object['value'])) {
						$arr_pattern = PatternEntity::createPattern($arr_result_object['value'][$key], $arr_result_object['pattern'][$key]);
					} else {
						$arr_pattern = PatternEntity::createPattern($arr_result_object['value'], $arr_result_object['pattern']);
					}
					
					$arr_result_option_objects[$key][$test_object_id] = $arr_pattern;
					$arr_result_select_object_ids[$key][] = $test_object_id;
				}
			}
			
			return ['options' => $arr_result_option_objects, 'select' => $arr_result_select_object_ids];
		};
		
		if ($mode_auto == static::AUTO_MODE_ALL) {
		
			foreach ($arr_results as $object_id => $arr_result) {
				
				$s_arr =& $arr_reconcile['results'][$object_id];
				
				$do_discard = (!$arr_result['objects']);
				
				if ($arr_template['target_self']) {
					
					if (!$do_discard && $arr_template['target_self'] == static::TARGET_SELF_MODE_ALL) {
						
						$arr_result_objects = $func_get_result_objects($arr_result);

						$s_arr['self'] = ['values' => $arr_result['values'], 'options' => $arr_result_objects['options'], 'select' => $arr_result_objects['select']];
					} else {
						
						$s_arr['self'] = ['values' => $arr_result['values']];
					}
				}
				if ($do_discard) {
					$s_arr['select'] = [];
				} else if ($arr_template['target_id']) {
					$s_arr['select'] = arrValuesRecursive('object_id', $arr_result['objects']);
				}
			}
			unset($s_arr);

			return ($this->createProcessTemplateStore($arr_template, $arr_reconcile) ?? true);
		} else if ($mode_auto == static::AUTO_MODE_CERTAIN || $mode_auto == static::AUTO_MODE_DISCARD_NO_RESULT) {

			foreach ($arr_results as $object_id => $arr_result) {
				
				$do_store = true;
				$do_discard = false;
				
				if ($mode_auto == static::AUTO_MODE_DISCARD_NO_RESULT) {
					
					// Do not auto-store (discard) a result that contains any matches
					
					if ($arr_result['objects']) {
						
						$do_store = false;
					} else {
						
						$do_discard = true;
					}
				} else {
					
					// Do not auto-store a result that contains an uncertain score
					
					if ($arr_result['objects']) {
						
						foreach ($arr_result['objects'] as $arr_result_object) {
							
							if (is_array($arr_result_object['score'])) {
								$num_score = min($arr_result_object['score']);
							} else {
								$num_score = $arr_result_object['score'];
							}
							
							if ($num_score < 100) {
								
								$do_store = false;
								break;
							}
						}
					} else {
						
						$do_discard = true;
					}
				}
					
				if ($do_store) {
				
					$s_arr =& $arr_reconcile['results'][$object_id];
					
					if ($arr_template['target_self']) {
						
						if (!$do_discard && $arr_template['target_self'] == static::TARGET_SELF_MODE_ALL) {
						
							$arr_result_objects = $func_get_result_objects($arr_result);

							$s_arr['self'] = ['values' => $arr_result['values'], 'options' => $arr_result_objects['options'], 'select' => $arr_result_objects['select']];
						} else {
							
							$s_arr['self'] = ['values' => $arr_result['values']];
						}
					}
					
					if ($do_discard) {
						$s_arr['select'] = [];
					} else if ($arr_template['target_id']) {
						$s_arr['select'] = arrValuesRecursive('object_id', $arr_result['objects']);
					}
					
					unset($arr_results[$object_id]);
				}
			}
			unset($s_arr);
			
			if ($arr_reconcile['results']) {
				
				$html = $this->createProcessTemplateStore($arr_template, $arr_reconcile);
				
				if ($this->has_feedback_template_process) {
					return $html;
				}
			}
			
			if (!$arr_results) {
				return true;
			}
		}
		
		return;
	}
	
	protected function createProcessTemplateResultCheck($arr_template, $arr_reconcile, $arr_results) {
		
		$mode_auto = $arr_reconcile['settings']['auto'];
		
		$has_return = $this->createProcessTemplateResultCheckAutomation($mode_auto, $arr_template, $arr_reconcile, $arr_results);
		
		if ($has_return !== null) {
			return $has_return;
		}
		
		$count_total = count($arr_results);
		
		$str_header = getLabel('msg_reconcile_resolve');
		
		$type_id = $arr_template['type_id'];
		$test_type_id = $arr_template['test_type_id'];
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlatMap($type_id);
	
		$is_text = static::isReconcileTextSource($arr_template);
		$has_multi = ($arr_type_set_flat[$arr_template['target_id']]['has_multi'] || $arr_type_set_flat[$arr_template['target_id']]['is_appendable'] === 'only_append' || $arr_template['target_self']);

		Labels::setVariable('count', $count_total);
		
		$html = '<div class="entry object-various options">
			
			<h3>'.getLabel('lbl_reconcile_match_results').': "'.Labels::parseTextVariables($arr_type_set['type']['name']).'"</h3>
			
			<section class="info attention body">'.parseBody(getLabel('inf_reconcile_match_results')).'</section>';
			
			$arr_object_ids = array_keys($arr_results);
			$arr_objects_name = GenerateTypeObjects::getTypeObjectNames($type_id, $arr_object_ids, GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE);
			$arr_test_object_ids = arrValuesRecursive('object_id', $arr_results);
			$arr_test_objects_name = GenerateTypeObjects::getTypeObjectNames($test_type_id, $arr_test_object_ids, false);
			$arr_objects_values = [];

			if ($is_text) {

				foreach ($arr_results as $object_id => $arr_result) {
					
					$str_name = 'reconcile[results]['.$object_id.']';
					$html_value = '';
										
					foreach ($arr_result['values'] as $key => $str_value) {
						
						$arr_objects_value_name = [];
						$arr_option_objects = [];
					
						foreach ($arr_result['objects'] as $arr_result_object) {
							
							$test_object_id = $arr_result_object['object_id'];
							$is_pattern_pair = (isset($arr_result_object['pattern_pair']) ? true : false);

							$str_name_input = $str_name.'[select]'.($arr_template['target_self'] ? '['.$key .']' : '').($has_multi ? '[]' : '');
							$html_input = '<input type="'.($has_multi ? 'checkbox' : 'radio').'" title="'.getLabel('inf_pattern_pair_assign_object').'" name="'.$str_name_input.'" value="'.$test_object_id.'"'.($mode_auto == static::AUTO_MODE_CERTAIN && $arr_result_object['score'] == 100 ? ' checked="checked"' : '').' />';
							
							$str_score = (is_array($arr_result_object['score']) ? $arr_result_object['score'][$key] : $arr_result_object['score']);
							$str_score = ($is_pattern_pair ? getLabel('lbl_pattern_pair') : 'score: '.$str_score);
							if (is_array($arr_result_object['value'])) {
								$arr_pattern = PatternEntity::createPattern($arr_result_object['value'][$key], $arr_result_object['pattern'][$key]);
							} else {
								$arr_pattern = PatternEntity::createPattern($arr_result_object['value'], $arr_result_object['pattern']);
							}
							
							if ($arr_template['test_pattern_pairs'] && !$is_pattern_pair) {
								
								$str_pattern_pair = value2JSON([$test_object_id, $arr_pattern]);
								
								$html_input .= '<input type="checkbox" title="'.getLabel('inf_pattern_pair_store').'" name="reconcile[pattern_pairs][]" value="'.strEscapeHTML($str_pattern_pair).'" />';
							}
							
							$arr_objects_value_name[$test_object_id] = ['name' => $arr_test_objects_name[$test_object_id].' ('.$str_score.')', 'input' => $html_input];
							
							if ($arr_template['target_self']) {
								
								$arr_option_objects[$test_object_id] = ($arr_template['target_self'] == static::TARGET_SELF_MODE_ALL ? $arr_pattern : true);
							}
						}
						
						$html_value .= '<fieldset>'
							.StoreTypeObjects::formatToHTMLValue('text_tags', $str_value, ['marginalia' => true], [$test_type_id => $arr_objects_value_name])
							.($arr_template['target_self'] ? '<input type="hidden" name="'.$str_name.'[values]['.$key.']" value="'.strEscapeHTML($str_value).'" />' : '')
							.($arr_template['target_self'] ? '<input type="hidden" name="'.$str_name.'[options]['.$key.']" value="'.strEscapeHTML(value2JSON($arr_option_objects)).'" />' : '')
						.'</fieldset>';
					}
					
					$arr_objects_values[$object_id] = $html_value;
				}
			} else {

				foreach ($arr_results as $object_id => $arr_result) {
					
					$str_value = '';
					
					$arr_group_values = $arr_result['values'];
				
					$str_value = implode(', ', $arr_group_values);
					$str_value = mb_strimwidth($str_value, 0, static::$num_preview_characters, '...');
					
					$arr_objects_values[$object_id] = '<blockquote>'.strEscapeHTML($str_value).'</blockquote>';
				}
			}
				
			$html .= '<ul class="results">';
				
				foreach ($arr_results as $object_id => $arr_result) {
					
					$str_name = 'reconcile[results]['.$object_id.']';
					
					$html .= '<li data-form_name="'.$str_name.'">
						<h3>'
							.'<span id="y:data_view:view_type_object-'.$type_id.'_'.$object_id.'" class="a popup">'.$arr_objects_name[$object_id].'</span>'
							.'<input type="hidden" name="'.$str_name.'[object_id]" value="'.$object_id.'" />'
						.'</h3>
					
						'.$arr_objects_values[$object_id];
						
						if ($arr_template['target_self']) {
							
							$html .= '<menu>'
								.'<input type="button" id="y:data_reconcile:edit-0" class="data edit" value="edit" />'
							.'</menu>
							
							<div class="fieldsets"><div>
								<fieldset>
									<label title="'.getLabel('inf_pattern_pair_assign_no_object').'">'
										.'<input type="'.($has_multi ? 'checkbox' : 'radio').'" name="'.$str_name.'[select]" value="none" />'
										.'<span>'.getLabel('lbl_discard').'</span>'
									.'</label>
								</fieldset>
							</div></div>';
						} else {
								
							$html .= '<div class="fieldsets"><div>';
																	
								if (!$is_text && $arr_result['objects']) {

									foreach ($arr_result['objects'] as $arr_result_object) {
										
										$test_object_id = $arr_result_object['object_id'];
										$is_pattern_pair = (isset($arr_result_object['pattern_pair']) ? true : false);
										
										$str_name_input = $str_name.'[select]'.($has_multi ? '[]' : '');
										$html_input = '<input type="'.($has_multi ? 'checkbox' : 'radio').'" title="'.getLabel('inf_pattern_pair_assign_object').'" name="'.$str_name_input.'" value="'.$test_object_id.'"'.($mode_auto == static::AUTO_MODE_CERTAIN && $arr_result_object['score'] == 100 ? ' checked="checked"' : '').' />';
										
										$str_score = (is_array($arr_result_object['score']) ? implode(' / ', $arr_result_object['score']) : $arr_result_object['score']);
										$str_score = ($is_pattern_pair ? getLabel('lbl_pattern_pair') : 'score: '.$str_score);
										
										if ($arr_template['test_pattern_pairs'] && !$is_pattern_pair) {
											
											if (is_array($arr_result_object['value'])) {
												$arr_pattern = PatternEntity::createPattern(implode(' ', $arr_result_object['value']));
											} else {
												$arr_pattern = PatternEntity::createPattern($arr_result_object['value']);
											}
											
											$str_pattern_pair = value2JSON([$test_object_id, $arr_pattern]);
											
											$html_input .= '<input type="checkbox" title="'.getLabel('inf_pattern_pair_store').'" name="reconcile[pattern_pairs][]" value="'.strEscapeHTML($str_pattern_pair).'" />';
										}
										
										$str_value = (is_array($arr_result_object['value']) ? implode(' / ', $arr_result_object['value']) : $arr_result_object['value']);
										
										$html .= '<fieldset>
											<blockquote>'.strEscapeHTML($str_value).'</blockquote>
											<label>'
												.$html_input
											.'</label>'
											.'<label><span id="y:data_view:view_type_object-'.$test_type_id.'_'.$test_object_id.'" class="a popup">'.$arr_test_objects_name[$test_object_id].'</span> ('.$str_score.')</label>'
										.'</fieldset>';
									}
								}
								
								if ($has_multi) {
									
									$html_select = '<div class="input">'
										.cms_general::createMultiSelect($str_name.'[pick]', 'y:data_filter:lookup_type_object-'.$test_type_id, [], 'y:data_filter:lookup_type_object_pick-'.$test_type_id, ['list' => true, 'order' => true])
									.'</div>';
								} else {
									
									$html_select = '<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$test_type_id.'" name="'.$str_name.'[pick]" value="" />'
										.'<input type="search" id="y:data_filter:lookup_type_object-'.$test_type_id.'" class="autocomplete" value="" />';
								}

								$html .= '<fieldset>
									<div>
										<label title="'.getLabel('inf_pattern_pair_assign_object').'">'
											.'<input type="'.($has_multi ? 'checkbox' : 'radio').'" name="'.$str_name.'[select]'.($has_multi ? '[]' : '').'" value="pick" />'
										.'</label>'
										.$html_select
									.'</div>
								</fieldset>
								<fieldset>
									<label title="'.getLabel('inf_pattern_pair_assign_no_object').'">'
										.'<input type="'.($has_multi ? 'checkbox' : 'radio').'" name="'.$str_name.'[select]" value="none" />'
										.'<span>'.getLabel('lbl_discard').'</span>'
									.'</label>
								</fieldset>
								
							</div></div>';
						}
						
					$html .= '</li>';
				}
		
			$html .= '</ul>
		</div>';
				
		$html = '<h2>'.$str_header.'</h2>'
			.$html
			.'<div class="options">
				<menu>'
					.'<label><input type="number" name="reconcile[settings][batch]" step="1" min="1" max="20" value="'.(int)$arr_reconcile['settings']['batch'].'"/><span>'.getLabel('lbl_reconcile_batch_amount').'</span></label>'
				.'</menu><menu>'
					.cms_general::createSelectorRadio(static::getProcessTemplateOptionsAuto(), 'reconcile[settings][auto]', $arr_reconcile['settings']['auto'])
				.'</menu><menu>'
					.'<label><input type="number" name="reconcile[settings][score_threshold]" step="1" min="1" max="100" value="'.(float)$arr_reconcile['settings']['score_threshold'].'"/><span>'.getLabel('lbl_reconcile_threshold').'</span></label>'
					.'<label><input type="number" name="reconcile[settings][score_match_difference]" step="1" min="0" max="99" value="'.(float)$arr_reconcile['settings']['score_match_difference'].'"/><span>'.getLabel('lbl_reconcile_match_difference').'</span></label>'
					.($arr_template['test_pattern_pairs'] ? '<label><input type="checkbox" name="reconcile[settings][prioritise_match_pattern_pair]" value="1"'.($arr_reconcile['settings']['prioritise_match_pattern_pair'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_reconcile_match_prioritise_pattern_pair').'</span></label>' : '')
					.'<label><input type="number" name="reconcile[settings][score_overlap_difference]" step="1" min="0" max="99" value="'.(float)$arr_reconcile['settings']['score_overlap_difference'].'"/><span>'.getLabel('lbl_reconcile_overlap_difference').'</span></label>'
				.'</menu><menu>'
					.'<label><input type="number" name="reconcile[settings][pattern_threshold]" step="1" min="0" max="100" value="'.(float)$arr_reconcile['settings']['pattern_threshold'].'"/><span>'.getLabel('lbl_reconcile_pattern_threshold').'</span></label>'
					.'<label><input type="number" name="reconcile[settings][pattern_distance]" step="1" min="1" value="'.(int)$arr_reconcile['settings']['pattern_distance'].'"/><span>'.getLabel('lbl_reconcile_pattern_distance').'</span></label>'
					.($arr_template['target_self'] ? '<label><input type="checkbox" name="reconcile[settings][pattern_complete]" value="1"'.($arr_reconcile['settings']['pattern_complete'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_reconcile_pattern_complete').'</span></label>' : '')
				.'</menu>
			</div>'
		;
		
		$this->has_feedback_template_process = true;
		
		return $html;
	}
	
	public static function css() {

		$return = '
			.reconcile.template-process ul.results > li > blockquote { quotes: none; display: block; width: max-content; margin: 12px 0px; padding: 0px; font-family: var(--font-mono); }
			.reconcile.template-process ul.results > li > fieldset,
			.reconcile.template-process ul.results > li > .fieldsets,
			.reconcile.template-process ul.results > li > menu { margin-left: 30px; }
			.reconcile.template-process ul.results > li > fieldset > .tabs { margin: 0px; }
			.reconcile.template-process ul.results > li > fieldset > .editor-content { display: block; }
			.reconcile.template-process ul.results > li > fieldset > .editor-content > .body-content { width: 100%; }
			.reconcile.template-process ul.results > li > fieldset > .text_tags .page > .body { flex: 0 0 70%; }
			.reconcile.template-process ul.results > li > fieldset > .text_tags .page > .marginalia { flex: 0 0 30%; max-width: none; }
			.reconcile.template-process ul.results > li > .fieldsets > div { margin: -4px !important; } 
			.reconcile.template-process ul.results > li > .fieldsets > div > fieldset { background-color: #e1e1e1; padding: 4px 8px; margin: 4px !important; } 
			.reconcile.template-process ul.results > li > .fieldsets > div > fieldset label,
			.reconcile.template-process ul.results > li > .fieldsets > div > fieldset span.a { line-height: 26px; }
			.reconcile.template-process ul.results > li > .fieldsets > div > fieldset > div > label,
			.reconcile.template-process ul.results > li > .fieldsets > div > fieldset > div > .input { display: inline-block; }
			.reconcile.template-process ul.results > li > .fieldsets > div > fieldset blockquote { quotes: none; display: block; margin: 0px 0px 4px 0px; padding: 4px 8px; font-family: var(--font-mono); background-color: #f5f5f5; }
		';

		return $return;
	}
	
	public static function js() {
		
		$return = "
		
		SCRIPTER.dynamic('.reconcile.template', function(elm_scripter) {
			
			elm_scripter.on('ajaxloaded scripter', function(e) {
				
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				runElementsSelectorFunction(e.detail.elm, '[id=y\\\:data_reconcile\\\:get_source-0], [id=y\\\:data_reconcile\\\:get_test-0]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'init_data_template');
				});
			}).on('init_data_template change', '[id=y\\\:data_reconcile\\\:get_source-0], [id=y\\\:data_reconcile\\\:get_test-0]', function(e) {
			
				var cur = $(this);
				var elm_target = cur.closest('ul').find('ul.sorter');
				var type_id = cur.val();
				var elm_filter_value = cur.next('[name$=\"type_filter]\"]');
				
				elm_filter_value.next('[type=button]').toggleClass('hide', (type_id ? false : true));
				
				if (e.type == 'init_data_template') {
					return;
				}
				
				elm_filter_value.val('');
				
				COMMANDS.setData(this, {form_name: FORMMANAGING.getElementNameBase(this)});
				COMMANDS.quickCommand(this, function(html) {
					
					elm_target.replaceWith(html);
					SCRIPTER.triggerEvent(elm_scripter.find('[id=y\\\:data_reconcile\\\:get_target-0]'), 'update_store');
				});
			}).on('change', '[name*=\"[values]\"]', function() {
				
				SCRIPTER.triggerEvent(elm_scripter.find('[id=y\\\:data_reconcile\\\:get_target-0]'), 'update_store');
			}).on('update_store', '[id=y\\\:data_reconcile\\\:get_target-0]', function() {
				
				var elms_target = elm_scripter.find('[name]');
				var arr_template = serializeArrayByName(elms_target);
				
				var str_name = FORMMANAGING.getElementNameBase(this);
				if (str_name) {
					arr_template = arrValueByKeyPath(str_name, arr_template);
				}
				
				arr_template.form_name = str_name;
																
				COMMANDS.setData(this, arr_template);
				COMMANDS.quickCommand(this, this);
			});
		});
		
		SCRIPTER.dynamic('.reconcile.template', 'application_filter');
		
		SCRIPTER.dynamic('.reconcile.run', function(elm_scripter) {
		
			var elm_reset = elm_scripter.find('[id^=y\\\:data_reconcile\\\:reset_process-]');
				
			COMMANDS.setTarget(elm_reset, elm_scripter);
			COMMANDS.setOptions(elm_reset, {html: 'replace'});
		});
		
		SCRIPTER.dynamic('reconcile_process_template', function(elm_scripter) {
							
			elm_scripter.on('change', 'input[type=checkbox][value=none]', function() {
			
				var elm = $(this);
				var elms_target = elm.closest('li').find('input[type=checkbox]').not(this);

				elms_target.prop('disabled', this.checked);
			}).on('click', '[id=y\\\:data_reconcile\\\:edit-0]', function() {
				
				var elm = $(this);
				var elms_source = elm.closest('li').find('input[name*=\"[values]\"]');
				var arr_values = [];
				
				for (var i = 0, len = elms_source.length; i < len; i++) {
					arr_values.push(elms_source[i].value);
				}
				
				COMMANDS.setData(this, {values: arr_values, form_name: FORMMANAGING.getElementNameBase(this)});
				
				COMMANDS.quickCommand(this, function(html) {
					
					var elm_target = elm.closest('li').children('h3');
					var elm_text = html;
					
					elm_target.nextAll().remove();
					elm_text.insertAfter(elm_target);
					
					var elms_target = elm_text.find('[name=\"\"]');
					
					for (var i = 0, len = elms_source.length; i < len; i++) {
						elms_target[i].setAttribute('name', elms_source[i].getAttribute('name'));
					}	
				});
			});
		});
		
		SCRIPTER.dynamic('.reconcile.template-process', 'reconcile_process_template');
		SCRIPTER.dynamic('.reconcile.template-process', 'view_type_object');
		SCRIPTER.dynamic('.reconcile.template-process', 'entry_type_object');
		
		SCRIPTER.dynamic('.reconcile.template-process', 'filtering');
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
			
		// INTERACT
		
		if ($method == "get_source" || $method == "get_test") {
				
			$type_id = (int)$value['value_element'];
			
			if ($type_id && (!data_entry::checkClearanceType($type_id) || !custom_projects::checkAccessType(StoreCustomProject::ACCESS_PURPOSE_VIEW, $type_id))) {
				return;
			}
			
			$this->form_name = ($value['form_name'] ?: $this->form_name);
			$str_name = $this->form_name.($method == 'get_test' ? '[test_values]' : '[values]');
			
			$this->html = $this->createTemplateValues($str_name, $type_id);
		}
		
		if ($method == "get_target") {

			if ($value['type_id'] && (!data_entry::checkClearanceType($value['type_id']) || !custom_projects::checkAccessType(StoreCustomProject::ACCESS_PURPOSE_VIEW, $value['type_id']))) {
				return;
			}
			if ($value['test_type_id'] && (!data_entry::checkClearanceType($value['test_type_id']) || !custom_projects::checkAccessType(StoreCustomProject::ACCESS_PURPOSE_VIEW, $value['test_type_id']))) {
				return;
			}
			
			$this->form_name = ($value['form_name'] ?: $this->form_name);
			
			$this->html = $this->createTemplateTarget($value);
		}
		
		if ($method == "reset_process") {

			$type_id = StoreType::getSystemTypeID('reconciliation');
			
			if (!data_entry::checkClearanceType($type_id) || !custom_projects::checkAccessType(StoreCustomProject::ACCESS_PURPOSE_EDIT, $type_id)) {
				return;
			}
			
			$arr_id = explode('_', $id);
			$object_id = $arr_id[0];
		
			static::clearTemplateState($object_id);
			
			$arr_template = static::getTemplate($object_id);
			
			$this->html = $this->createRunTemplate($arr_template);
			$this->msg = true;
		}
		
		if ($method == "edit") {
			
			$html = '';
			
			foreach ($value['values'] as $key => $str_value) {
				
				$html .= '<fieldset>'.StoreTypeObjects::formatToFormValue('text_tags', $str_value, '').'</fieldset>';
			}
			
			$str_name = $value['form_name'];
			
			$html .= '<div class="fieldsets"><div>
				<fieldset>
					<label>'
						.'<input type="radio" name="'.$str_name.'[store]" value="yes" />'
						.'<span>'.getLabel('lbl_accept').'</span>'
					.'</label>
				</fieldset>
				<fieldset>
					<label>'
						.'<input type="radio" name="'.$str_name.'[store]" value="no" />'
						.'<span>'.getLabel('lbl_discard').'</span>'
					.'</label>
				</fieldset>
			</div></div>';
			
			$this->html = $html;
		}
	}
	
	public static function getTypeObjectDescriptionsText($type_id) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlatMap($type_id, ['object' => true, 'references' => false]);
		
		$arr_type_set_filtered = [];
		
		if ($arr_type_set_flat['object-name']) {
			$arr_type_set_filtered['object-name'] = $arr_type_set_flat['object-name'];
		}
	
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$value_type = $arr_object_description['object_description_value_type'];
			
			if (!($value_type == '' || $value_type == 'text' || $value_type == 'text_layout' || $value_type == 'text_tags')) {
				continue;
			}
			
			$str_id = 'object_description-'.$object_description_id;
			
			$arr_type_set_filtered[$str_id] = $arr_type_set_flat[$str_id];
		}
		
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				$value_type = $arr_object_sub_description['object_sub_description_value_type'];
			
				if (!($value_type == '' || $value_type == 'text' || $value_type == 'text_layout' || $value_type == 'text_tags')) {
					continue;
				}
				
				$str_id = 'object_sub_details-'.$object_sub_details_id.'-object_sub_description-'.$object_sub_description_id;

				$arr_type_set_filtered[$str_id] = $arr_type_set_flat[$str_id];
			}
		}
		
		return $arr_type_set_filtered;
	}
	
	public static function getTypeObjectDescriptionsReference($type_id, $test_type_id) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlatMap($type_id, ['object' => false, 'references' => true]);
		
		$arr_type_set_filtered = [];
	
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if (!$arr_object_description['object_description_ref_type_id'] || $arr_object_description['object_description_ref_type_id'] != $test_type_id) {
				continue;
			}
			
			$str_id = 'object_description-'.$object_description_id;
			
			$arr_type_set_filtered[$str_id] = $arr_type_set_flat[$str_id];
		}
		
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			$str_identifier = 'object_sub_details-'.$object_sub_details_id;
			
			$str_id = false;
			
			if (!$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']) {
				
				$str_id = $str_identifier.'-location_ref_type_id';
			} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked'] && $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'] == $test_type_id) {
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id_locked']) {
					$str_id = $str_identifier.'-location_ref_type_id-object_sub_details_lock';
				} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']) {
					$str_id = $str_identifier.'-location_ref_type_id-type_lock-'.$test_type_id;
				}
			}
			
			if ($arr_type_set_flat[$str_id]) {
				$arr_type_set_filtered[$str_id] = $arr_type_set_flat[$str_id];
			}
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				if (!$arr_object_sub_description['object_sub_description_ref_type_id'] || $arr_object_sub_description['object_sub_description_ref_type_id'] != $test_type_id) {
					continue;
				}
				
				$str_id = $str_identifier.'-object_sub_description-'.$object_sub_description_id;

				$arr_type_set_filtered[$str_id] = $arr_type_set_flat[$str_id];
			}
		}
		
		return $arr_type_set_filtered;
	}
	
	public static function getTemplate($object_id) {
		
		$system_type_id = StoreType::getSystemTypeID('reconciliation');
		$arr_object_set = data_entry::getTypeObjectSet($system_type_id, $object_id);
		
		$arr_object_definition = $arr_object_set['object_definitions'][StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module')];
		$arr_template = $arr_object_definition['object_definition_value'];
		$arr_template = ($arr_template ? JSON2Value($arr_template) : []);
			
		$arr_template = static::parseTemplate($arr_template);
		
		$arr_template['identifier'] = $object_id;

		return $arr_template;
	}
	
	protected static function isReconcileTextSource($arr_template) {
		
		$arr_source_map = [];
		foreach ($arr_template['values'] as $arr_source_value) {
			$arr_source_map[$arr_source_value['id']] = true;
		}
		
		$arr_type_set = StoreType::getTypeSetByFlatMap($arr_template['type_id'], $arr_source_map);
		
		$is_text = (arrHasValuesRecursive('object_description_value_type', ['text' => true, 'text_layout' => true, 'text_tags' => true], $arr_type_set) || arrHasValuesRecursive('object_sub_description_value_type', ['text' => true, 'text_layout' => true, 'text_tags' => true], $arr_type_set));
	
		return $is_text;
	}
	
	
	protected static function isReconcileTextTagsSource($arr_template) {
		
		$arr_source_map = [];
		foreach ($arr_template['values'] as $arr_source_value) {
			$arr_source_map[$arr_source_value['id']] = true;
		}
		
		$arr_type_set = StoreType::getTypeSetByFlatMap($arr_template['type_id'], $arr_source_map);

		$is_text_tags = (arrHasValuesRecursive('object_description_value_type', 'text_tags', $arr_type_set) || arrHasValuesRecursive('object_sub_description_value_type', 'text_tags', $arr_type_set));
	
		return $is_text_tags;
	}
	
	public static function parseTemplate($arr_template_raw) {
				
		$arr_template = [
			'type_id' => (int)$arr_template_raw['type_id'],
			'test_type_id' => (int)$arr_template_raw['test_type_id'],
			'type_filter' => ($arr_template_raw['type_id'] ? (is_array($arr_template_raw['type_filter']) ? $arr_template_raw['type_filter'] : JSON2Value($arr_template_raw['type_filter'])) : ''),
			'test_type_filter' => ($arr_template_raw['test_type_id'] ? (is_array($arr_template_raw['test_type_filter']) ? $arr_template_raw['test_type_filter'] : JSON2Value($arr_template_raw['test_type_filter'])) : ''),
			'test_pattern_pairs' => (bool)$arr_template_raw['test_pattern_pairs'],
			'target_id' => $arr_template_raw['target_id'],
			'target_self' => (int)$arr_template_raw['target_self'],
			'mode_write' => ((int)$arr_template_raw['mode_write'] ?: static::WRITE_MODE_APPEND),
			'values' => [],
			'test_values' => []
		];
		
		$arr_types_check = StoreType::getTypes([$arr_template['type_id'], $arr_template['test_type_id']]);
		
		$arr_type = $arr_types_check[$arr_template['type_id']];
		if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
			unset($arr_template['type_id']);
		}
		
		$arr_type = $arr_types_check[$arr_template['test_type_id']];
		if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
			unset($arr_template['test_type_id']);
		}
		
		foreach ($arr_template_raw['values'] as $key => $arr_value) {
			
			if (!$arr_value['id']) {
				continue;
			}
			
			$arr_template['values'][] = $arr_value;
		}
		foreach ($arr_template_raw['test_values'] as $key => $arr_value) {
			
			if (!$arr_value['id']) {
				continue;
			}
			
			$arr_template['test_values'][] = $arr_value;
		}
		
		if ($arr_template['target_self']) {
			
			$arr_object_descriptions_text = static::getTypeObjectDescriptionsText($arr_template['type_id']);
			
			foreach ($arr_template['values'] as $key => $arr_source_value) {
				
				if (!$arr_object_descriptions_text[$arr_source_value['id']]) {
					unset($arr_template['values'][$key]);
				}
			}
		}
		
		return $arr_template;
	}
	
	public static function parseTemplateProcess($object_id, $arr_reconcile) {
		
		$arr_template = static::getTemplate($object_id);
		
		$num_limit = (int)$arr_reconcile['settings']['batch'];
		$num_limit = ($num_limit > 0 && $num_limit <= 20 ? $num_limit : static::$num_limit);
		$num_pattern_threshold = (float)$arr_reconcile['settings']['pattern_threshold'];
		$num_pattern_threshold = ($num_pattern_threshold >= 0 && $num_score_threshold <= 100 ? $num_pattern_threshold : static::$num_pattern_threshold);
		$num_pattern_distance = (int)$arr_reconcile['settings']['pattern_distance'];
		$num_pattern_distance = ($num_pattern_distance > 0 ? $num_pattern_distance : static::$num_pattern_distance);
		$do_pattern_complete = (bool)$arr_reconcile['settings']['pattern_complete'];
		$num_score_threshold = (float)$arr_reconcile['settings']['score_threshold'];
		$num_score_threshold = ($num_score_threshold > 0 && $num_score_threshold <= 100 ? $num_score_threshold : static::$num_score_threshold);
		$num_score_match_difference = (float)$arr_reconcile['settings']['score_match_difference'];
		$num_score_match_difference = ($num_score_match_difference >= 0 && $num_score_match_difference <= 99 ? $num_score_match_difference : static::$num_score_match_difference);
		$do_prioritise_match_pattern_pair = (bool)$arr_reconcile['settings']['prioritise_match_pattern_pair'];
		$num_score_overlap_difference = (float)$arr_reconcile['settings']['score_overlap_difference'];
		$num_score_overlap_difference = ($num_score_overlap_difference >= 0 && $num_score_overlap_difference <= 99 ? $num_score_overlap_difference : static::$num_score_overlap_difference);
		$num_auto = $arr_reconcile['settings']['auto'];
		$num_auto = ($num_auto == static::AUTO_MODE_ALL || $num_auto == static::AUTO_MODE_CERTAIN || $num_auto == static::AUTO_MODE_DISCARD_NO_RESULT ? $num_auto : false);
		
		$arr_settings = [
			'batch' => $num_limit,
			'pattern_threshold' => $num_pattern_threshold,
			'pattern_distance' => $num_pattern_distance,
			'pattern_complete' => $do_pattern_complete,
			'score_threshold' => $num_score_threshold,
			'score_match_difference' => $num_score_match_difference,
			'prioritise_match_pattern_pair' => $do_prioritise_match_pattern_pair,
			'score_overlap_difference' => $num_score_overlap_difference,
			'auto' => $num_auto
		];
	
		$arr_objects_results = [];
		
		if ($arr_reconcile['results']) {
				
			foreach ($arr_reconcile['results'] as $arr_input) {
				
				if ($arr_template['target_self']) {
					
					if (!$arr_input['store'] && !$arr_input['select']) { // Could be editor mode, or selection mode
						continue;
					}
					
					if ($arr_input['store'] === 'no') { // Ignored editor mode
						
						continue;
					} else {
						
						foreach ($arr_input['options'] as $key => &$arr_object_ids) {
							
							if (is_array($arr_object_ids)) {
								continue;
							}
							
							$arr_object_ids = JSON2Value($arr_object_ids);
						}
						unset($arr_object_ids);
							
						if ($arr_input['store'] === 'yes') { // Accepted editor mode
							
							$arr_objects_results[$arr_input['object_id']]['self'] = ['values' => $arr_input['values'], 'options' => $arr_input['options'], 'store' => true];
						} else if ($arr_input['select'] && $arr_input['select'] !== 'none') {
						
							$arr_objects_results[$arr_input['object_id']]['self'] = ['values' => $arr_input['values'], 'options' => $arr_input['options'], 'select' => $arr_input['select']];
						} else {

							$arr_objects_results[$arr_input['object_id']]['self'] = ['values' => $arr_input['values'], 'options' => $arr_input['options']];
							
							$arr_objects_results[$arr_input['object_id']]['select'] = [];
						}
					}
				}
				
				if ($arr_template['target_id']) {
					
					if (!$arr_input['select']) {
						continue;
					}
						
					if ($arr_input['select'] !== 'none') {
						
						$arr_data = [];
						
						if (is_array($arr_input['select'])) {

							foreach ($arr_input['select'] as $value) {

								if ($value === 'pick') {
									
									if ($arr_input['pick']) {
										$arr_data = array_merge($arr_data, $arr_input['pick']);
									}
								} else {
									
									if ($arr_template['target_self']) { // Includes additional key for the object description
										
										foreach ((array)$value as $ref_object_id) {
											$arr_data[] = $ref_object_id;
										}
									} else {
										$arr_data[] = $value;
									}
								}
							}
							
							$arr_data = array_unique($arr_data);
						} else {
							
							if ($arr_input['select'] === 'pick') {
								$arr_data = (array)$arr_input['pick'];
							} else {
								$arr_data = (array)$arr_input['select'];
							}
						}
						
						$arr_objects_results[$arr_input['object_id']]['select'] = $arr_data;
					} else {
						
						$arr_objects_results[$arr_input['object_id']]['select'] = [];
					}
				}
			}
		}
		
		$arr_pattern_pairs = null;
		
		if ($arr_template['test_pattern_pairs'] && $arr_reconcile['pattern_pairs']) {
			
			$arr_pattern_pairs = (array)$arr_reconcile['pattern_pairs'];
		}

		return ['results' => $arr_objects_results, 'pattern_pairs' => $arr_pattern_pairs, 'settings' => $arr_settings];
	}
	
	protected static function getTemplateOptionsTargetSelf() {
		
		$arr_target_self = [
			['id' => 0, 'name' => getLabel('lbl_no')],
			['id' => static::TARGET_SELF_MODE_FIRST, 'name' => getLabel('lbl_reconcile_store_tags_first')],
			['id' => static::TARGET_SELF_MODE_ALL, 'name' => getLabel('lbl_reconcile_store_tags_all')]
		];
		
		return $arr_target_self;
	}
	
	protected static function getProcessTemplateOptionsAuto() {
		
		$arr_auto = [
			['id' => static::AUTO_MODE_ALL, 'name' => getLabel('lbl_auto_save').': '.getLabel('lbl_reconcile_match_any')],
			['id' => static::AUTO_MODE_CERTAIN, 'name' => getLabel('lbl_auto_save').':  '.getLabel('lbl_reconcile_match_certain')],
			['id' => static::AUTO_MODE_DISCARD_NO_RESULT, 'name' => getLabel('lbl_auto_discard').': '.getLabel('lbl_no_result')],
			['id' => '', 'name' => getLabel('lbl_none')]
		];
		
		return $arr_auto;
	}
	
	protected static function updateTemplateState($object_id, $arr_update = []) {
				
		$system_type_id = StoreType::getSystemTypeID('reconciliation');
											
		if ($arr_update['object_ids'] !== null) {
			
			$num_status = 0; // Done
			switch ($arr_update['status']) {
				case 'pending':
					$num_status = 1;
					break;
				case 'clear':
					$num_status = 2;
					break;
			}
			
			$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
			StoreTypeObjects::updateModuleObjectTypeObjects($system_object_description_id, $object_id, $arr_update['object_ids'], $num_status);
		}
	}
	
	protected static function clearTemplateState($object_id) {
		
		$arr_template = static::getTemplate($object_id);
		
		static::updateTemplateState($object_id, ['object_ids' => false, 'status' => 'clear']);
	}
	
	protected static function getModuleObjectTypeObjects($object_id, $type_id, $arr_filter = false, $num_status = 0, $do_exclude = false, $num_limit = false) {
		
		$system_type_id = StoreType::getSystemTypeID('reconciliation');
		$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
	
		$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
		$filter->setFilter(['module_object_objects' => ['object_id' => $object_id, 'object_description_id' => $system_object_description_id, 'exclude' => $do_exclude, 'status' => $num_status]]);		
		if ($arr_filter) {
			$filter->setFilter($arr_filter);
		}
		if ($num_limit) {
			$filter->setLimit($num_limit);
		}
		
		$arr_object_ids = $filter->init();
		
		return $arr_object_ids;
	}
}
