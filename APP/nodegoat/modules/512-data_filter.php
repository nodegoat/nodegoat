<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_filter extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Filter';
		static::$parent_label = 'nodegoat';
	}
	
	public static $arr_date_options = ['range' => ['id' => 'range', 'name' => 'Range'], 'chronology' => ['id' => 'chronology', 'name' => 'Chronology'], 'point' => ['id' => 'point', 'name' => 'Point']];
	public static $arr_location_options = ['reference' => ['id' => 'reference', 'name' => 'Reference'], 'geometry' => ['id' => 'geometry', 'name' => 'Geometry'], 'point' => ['id' => 'point', 'name' => 'Point']];
	public $form_name = 'filter';
		
	public function createFilter($type_id, $arr_type_filter = []) {
	
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		if (!$arr_type_filter['form']) {
			$arr_type_filter['form'] = $arr_type_filter;
		}
		if ($arr_type_filter['form']) {
			foreach ($arr_type_filter['form'] as $filter_code => $value) {
			
				if (!$value || strpos($filter_code, 'filter_') !== 0) {
					unset($arr_type_filter['form'][$filter_code]);
				}
			}
			$arr_filter_tabs = $this->getFilterTypeObjectTabs($arr_type_filter['form']);
		}
		if (!$arr_filter_tabs) {
			$arr_filter_tab = $this->createFilterTypeObjectTab($type_id);
		}
			
		$return = '<div class="filtering">
			<div class="filter tabs" data-sorting="1">
				<ul>
					<li class="no-tab" data-sortable="0"><span>'
						.'<input type="button" id="y:data_filter:store_filter-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_filter_store').'" />'
						.'<input type="button" id="y:data_filter:add_filter_extra-'.$type_id.'_0" class="data add popup" value="open" title="'.getLabel('inf_filter_add_extra').'"'.($this->form_name != 'filter' ? ' data-name="'.$this->form_name.'"' : '').' />'
					.'</span></li>
					'.($arr_filter_tab ? $arr_filter_tab['link'] : implode('', arrValuesRecursive('link', $arr_filter_tabs))).'
				</ul>
				'.($arr_filter_tab ? $arr_filter_tab['content'] : implode('', arrValuesRecursive('content', $arr_filter_tabs))).'
			</div>
			
			'.$this->createFilterVersioning($type_id, ($arr_type_filter['versioning'] ?: [])).'
			
		</div>';
		
		return $return;
	}
	
	private function getFilterTypeObjectTabs($arr_type_filter, $arr_source = []) {
		
		$arr_filter_codes_new = [];
		$arr_filter_tabs = [];
		
		foreach ((array)$arr_type_filter as $filter_code => $arr_filter) {
			
			if ($arr_source) { // Keep newly added filters unique, generate new filter codes
					
				if (!$arr_filter_codes_new[$filter_code]) {
					$arr_filter['filter_code'] = $arr_filter_codes_new[$filter_code] = uniqid('filter_');
				}
				
				if ($arr_filter['source'] && $arr_filter['source']['filter_code']) {
					
					if (!$arr_filter_codes_new[$arr_filter['source']['filter_code']]) {
						$arr_filter_codes_new[$arr_filter['source']['filter_code']] = uniqid('filter_');
					}
					
					$arr_filter['source']['filter_code'] = $arr_filter_codes_new[$arr_filter['source']['filter_code']];
				}
			} else {
				$arr_filter['filter_code'] = $filter_code;
			}
			
			$arr_filter_tabs[] = $this->createFilterTypeObjectTab($arr_filter['type_id'], ((!$arr_filter['source'] || !$arr_filter['source']['filter_code']) && $arr_source ? $arr_source : $arr_filter['source']), $arr_filter);
		}
		
		return $arr_filter_tabs;
	}
	
	private function createFilterTypeObjectTab($type_id, $arr_source = [], $arr_filter = []) {

		$arr_type_set = StoreType::getTypeSet($type_id);
		if ($arr_source['type_id']) {
			$arr_source_type_set = StoreType::getTypeSet($arr_source['type_id']);
		}
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$str_identifier = 'type_referenced_'.$type_id;
		$cache = self::getCache($str_identifier);
		
		if ($cache) {
			$arr_type_referenced = $cache;
		} else {
			$arr_ref_type_ids = StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id']);
			$arr_type_referenced = FilterTypeObjects::getTypesReferenced($type_id, $arr_ref_type_ids, ['object_sub_locations' => true, 'dynamic_is_used' => true]);
			self::setCache($str_identifier, $arr_type_referenced);
		}
				
		$filter_code = ($arr_filter['filter_code'] ?: uniqid('filter_'));
		$name = $this->form_name.'[form]['.$filter_code.']';
		$is_new = (!$arr_filter['filter_code']);
		
		if ($arr_source['type_id']) {
			if ($arr_source['object_description_id']) {
				$referenced = ($arr_source['direction'] == 'in' || (!$arr_source['direction'] && $arr_type_set['object_descriptions'][$arr_source['object_description_id']]) ? true : false); // If the source object description does not exist in the filtering type, it is a referenced one
				$filter_name = Labels::parseTextVariables($arr_source_type_set['object_descriptions'][$arr_source['object_description_id']]['object_description_name']);
			} else if ($arr_source['object_sub_description_id']) {
				$referenced = ($arr_source['direction'] == 'in' || (!$arr_source['direction'] && $arr_type_set['object_sub_details'][$arr_source['object_sub_details_id']]['object_sub_descriptions'][$arr_source['object_sub_description_id']]) ? true : false); // If the source object sub description does not exist in the filtering type, it is a referenced one
				$object_sub_details_name = Labels::parseTextVariables($arr_source_type_set['object_sub_details'][$arr_source['object_sub_details_id']]['object_sub_details']['object_sub_details_name']);
				$object_sub_description_name = Labels::parseTextVariables($arr_source_type_set['object_sub_details'][$arr_source['object_sub_details_id']]['object_sub_descriptions'][$arr_source['object_sub_description_id']]['object_sub_description_name']);
				if ($arr_source['object_sub_description_id'] == 'date' || $arr_source['object_sub_description_id'] == 'location') { // Filter on subobject date/location
					$object_sub_description_name = getLabel('lbl_'.$arr_source['object_sub_description_id']);
					if (!$arr_source['object_sub_details_id']) { // General subobject filtering
						$object_sub_details_name = getLabel('lbl_any');
					}
				}
				$filter_name = '<span class="sub-name">'.$object_sub_details_name.'</span> <span>'.$object_sub_description_name.'</span>';
			} else {
				$referenced = true; // If the source specification only contains a type id, it's referenced
			}
			if ($referenced) {
				if ($filter_name) {
					$filter_name = '<span><span class="icon" data-category="direction">'.getIcon('updown-up').'</span><span>'.$filter_name.'</span></span><span>'.Labels::parseTextVariables($arr_source_type_set['type']['name']).'</span>';
				} else {
					$filter_name = '<span><span class="icon" data-category="direction">'.getIcon('updown-up').'</span><span>'.Labels::parseTextVariables($arr_source_type_set['type']['name']).'</span></span>';
				}
			} else {
				$filter_name = '<span><span class="icon" data-category="direction">'.getIcon('updown-down').'</span><span>'.$filter_name.'</span></span><span>'.Labels::parseTextVariables($arr_type_set['type']['name']).'</span>';
			}
		} else {
			$filter_name = '<span><span class="source"></span>'.Labels::parseTextVariables($arr_type_set['type']['name']).'</span>';
		}
		
		$return_link = '<li'.($arr_source['filter_code'] ? ' data-parent_id="'.$arr_source['filter_code'].'"' : '').'><a href="#'.$filter_code.'">'.$filter_name.'</a><span><input type="button" class="data del" value="del" /></span></li>';
		
		$return = '<div id="'.$filter_code.'">
			<div class="options">
			
				<fieldset>
					<input type="hidden" name="'.$name.'[type_id]" value="'.$type_id.'" />
					<input type="hidden" name="'.$name.'[source][filter_code]" value="'.($arr_source['filter_code'] ?: '').'" />
					<input type="hidden" name="'.$name.'[source][filter_type_id]" value="'.($arr_source['filter_type_id'] ?: '').'" />
					<input type="hidden" name="'.$name.'[source][filter_beacon]" value="'.($arr_source['filter_beacon'] ?: '').'" />
					<input type="hidden" name="'.$name.'[source][type_id]" value="'.$arr_source['type_id'].'" />
					<input type="hidden" name="'.$name.'[source][object_description_id]" value="'.$arr_source['object_description_id'].'" />
					<input type="hidden" name="'.$name.'[source][object_sub_details_id]" value="'.$arr_source['object_sub_details_id'].'" />
					<input type="hidden" name="'.$name.'[source][object_sub_description_id]" value="'.$arr_source['object_sub_description_id'].'" />
					<input type="hidden" name="'.$name.'[source][direction]" value="'.($arr_source['direction'] ?: '').'" />
					<ul>
						<li><label>'.getLabel('lbl_mode').'</label><span>';
							
							$str_operator = $arr_filter['options']['operator'];
							if (!$str_operator || $str_operator == 'or') {
								$str_operator = 'object_or_sub_or';
							} else if ($str_operator == 'and') {
								$str_operator = 'object_and_sub_or';
							}
						
							$return .= cms_general::createSelectorRadio([
								['id' => 'object_or_sub_or', 'name' => '<span title="'.getLabel('inf_filter_object_or_sub_or').'">'.getLabel('lbl_filter_object_or_sub_or').'</span>'],
								['id' => 'object_and_sub_or', 'name' => '<span title="'.getLabel('inf_filter_object_and_sub_or').'">'.getLabel('lbl_filter_object_and_sub_or').'</span>'],
								['id' => 'object_and_sub_and', 'name' => '<span title="'.getLabel('inf_filter_object_and_sub_and').'">'.getLabel('lbl_filter_object_and_sub_and').'</span>'],
								['id' => 'object_optional_sub_optional', 'name' => '<span title="'.getLabel('inf_filter_object_optional_sub_optional').'">'.getLabel('lbl_filter_object_optional_sub_optional').'</span>']
							], $name.'[options][operator]', $str_operator)
							.'<span class="split"></span>'
							.'<label><em>n</em> <span>=</span><input type="number" name="'.$name.'[options][operator_extra]" value="'.((int)$arr_filter['options']['operator_extra'] ?: 1).'"/></label>'
						.'</span></li>
						<li><label>'.getLabel('lbl_exclude').'</label><span>'.cms_general::createSelectorRadio([
							['id' => '', 'name' => getLabel('lbl_no')],
							['id' => 'soft', 'name' => '<span title="'.getLabel('inf_filter_exclude').' '.getLabel('inf_filter_exclude_soft').'">'.getLabel('lbl_filter_exclude_soft').'</span>'],
							['id' => 'hard', 'name' => '<span title="'.getLabel('inf_filter_exclude').' '.getLabel('inf_filter_exclude_hard').'">'.getLabel('lbl_filter_exclude_hard').'</span>']
						], $name.'[options][exclude]', $arr_filter['options']['exclude']).'</span></li>
					</ul>
				</fieldset>
				
				<div class="tabs">
					<ul>
						<li><a href="#">'.Labels::parseTextVariables($arr_type_set['type']['name']).'</a></li>
						<li><a href="#"><span><span class="icon" data-category="direction">'.getIcon('updown-up').'</span><span>'.getLabel('lbl_referenced').'</span></span></a></li>
					</ul>
					
					<div>
						<div class="options">
						
							<fieldset><ul>';
								
								$return .= self::createFilterTypeReferences($type_id, $arr_filter['objects'], $name.'[objects]', getLabel('lbl_objects'), ['filter_deep' => false]);
								
								if ($arr_type_set['type']['use_object_name']) {
									
									$arr_sorter = [];
									
									foreach (($arr_filter['object_name'] ?: ['']) as $value) {
										
										$unique = uniqid('array_');
										
										$arr_sorter[] = ['value' => StoreTypeObjects::formatToFormValueFilter('', $value, $name.'[object_name]['.$unique.']')];
									}
									
									$return .= '<li>
										<label>'.getLabel('lbl_name').'</label><span>
											<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'
										.'</span>
									</li><li>
										<label></label>
										'.cms_general::createSorter($arr_sorter, false, true).'
									</li>';
								}

								$arr_object_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, [], $arr_use_project_ids);
								foreach ($arr_object_analyses as &$value){
									$value['analysis_id'] = $value['id'];
								}
								$has_analysis = ($arr_object_analyses && FilterTypeObjects::hasTypesAnalyses($arr_object_analyses));
								
								if ($has_analysis) {
									
									$arr_object_analyses_selection = data_analysis::createTypeAnalysesSelection($type_id);
		
									$arr_sorter = [];
										
									foreach (($arr_filter['object_analyses'] ?: [[]]) as $arr_filter_object_analysis) {
										
										$unique = uniqid('array_');
										
										$arr_sorter[] = ['value' => [
											'<select name="'.$name.'[object_analyses]['.$unique.'][object_analysis_id]"'.($info ? ' title="'.strEscapeHTML($info).'"' : '').'>'.Labels::parseTextVariables(cms_general::createDropdown($arr_object_analyses_selection, $arr_filter_object_analysis['object_analysis_id'], true, 'label')).'</select>',
											StoreTypeObjects::formatToFormValueFilter('float', $arr_filter_object_analysis['number'], $name.'[object_analyses]['.$unique.'][number]')
											.'<span class="input" title="'.getLabel('lbl_analysis_secondary_value').'">'.StoreTypeObjects::formatToFormValueFilter('float', $arr_filter_object_analysis['number_secondary'], $name.'[object_analyses]['.$unique.'][number_secondary]').'</span>'
										]];
									}
									
									$return .= '<li>
										<label>'.getLabel('lbl_analysis').'</label><span>
											<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'
										.'</span>
									</li><li>
										<label></label>
										'.cms_general::createSorter($arr_sorter, false, true).'
									</li>';
								}
										
							$return .= '</ul></fieldset>';
								
							if ($arr_type_set['object_descriptions']) {
									
								$return_html = $this->createFilterTypeObjectDefinitions($type_id, $filter_code, $arr_filter['object_definitions']);
								
								if ($return_html) {
									$return .= '<h3>'.getLabel('lbl_descriptions').'</h3>'.$return_html;
								}
							}
								
							if ($arr_type_set['object_sub_details']) {
								
								$return_html = $this->createFilterTypeObjectSubs($type_id, $filter_code, $arr_filter['object_subs']);
								
								if ($return_html) {
									$return .= '<h3>'.getLabel('lbl_object_subs').'</h3>'.$return_html;
								}
							}
						
						$return .= '</div>
					</div>
					
					<div>
						<div class="options">';
							
							if ($arr_type_referenced) {
								
								$name_referenced = '[referenced_any]';
								$arr_referenced = $arr_filter['referenced_any'];
								
								$arr_select_reference_sources = [];
								if (arrValuesRecursive('object_descriptions', $arr_type_referenced)) {
									$arr_select_reference_sources['object_definition'] = true;
								}
								if (arrValuesRecursive('object_sub_descriptions', $arr_type_referenced)) {
									$arr_select_reference_sources['object_sub_definition'] = true;
								}
								if (arrValuesRecursive('object_sub_location', $arr_type_referenced)) {
									$arr_select_reference_sources['object_sub_location_reference'] = true;
								}
														
								$return .= '<fieldset><ul>
									<li>
										<label>'.getLabel('lbl_referenced').'</label>
										<div>'.cms_general::createSelector(FilterTypeObjects::getReferenceSourceValues($arr_select_reference_sources), $name.$name_referenced.'[from]', ($arr_referenced['from'] ?: [])).'</div>
									</li>';

									$arr_html_extra = [];
									$has_extra = $arr_referenced['relationality'];
									$arr_html_extra[] = self::createFilterValueRelationality($arr_referenced['relationality'], $name.$name_referenced.'[relationality]');
									
									if (!$has_extra) {
										
										$return .= '<li class="extra"><label></label>
											<span><input type="button" class="data neutral extra" value="more" /></span>
										</li>';
									}
									
									foreach ($arr_html_extra as $value) {

										$return .= '<li class="extra '.(!$has_extra ? ' hide' : '').'"><label></label>
											<div>'.$value.'</div>
										</li>';
									}
								
								$return .= '</ul></fieldset>';

								$arr_html_type_referenced_tabs = [];
								
								foreach ($arr_type_referenced as $ref_type_id => $arr_ref_type) {
								
									$arr_reference_type_set = StoreType::getTypeSet($ref_type_id);
									$arr_select_reference_sources = [];
									
									$arr_html_type_referenced_tabs['links'][] = '<li><a href="#">'.Labels::parseTextVariables($arr_reference_type_set['type']['name']).'</a></li>';
									
									$return_content = '<div><div class="options">';
								
									if ($arr_ref_type['object_descriptions']) {
										
										$return_html = $this->createFilterTypeObjectDefinitions($ref_type_id, $filter_code, $arr_filter['referenced_types'][$ref_type_id]['object_definitions'], $arr_ref_type['object_descriptions']);
										
										if ($return_html) {
											
											$return_content .= '<h3>'.getLabel('lbl_descriptions').'</h3>'.$return_html;
											$arr_select_reference_sources['object_definition'] = true;
										}
									}
									
									if ($arr_ref_type['object_sub_details']) {
										
										$return_html = $this->createFilterTypeObjectSubs($ref_type_id, $filter_code, $arr_filter['referenced_types'][$ref_type_id]['object_subs'], $arr_ref_type['object_sub_details'], $type_id);
										
										if ($return_html) {
											$return_content .= '<h3>'.getLabel('lbl_object_subs').'</h3>'.$return_html;
										}
										
										if (arrValuesRecursive('object_sub_descriptions', $arr_ref_type['object_sub_details'])) {
											$arr_select_reference_sources['object_sub_definition'] = true;
										}
										if (arrValuesRecursive('object_sub_location', $arr_ref_type['object_sub_details'])) {
											$arr_select_reference_sources['object_sub_location_reference'] = true;
										}
									}
																
									$return_content .= '<fieldset><ul>';
																
										$name_referenced = '[referenced_types]['.$ref_type_id.'][any]';
										$arr_referenced = $arr_filter['referenced_types'][$ref_type_id]['any'];
										
										$return_content .= '<li>
											<label>'.getLabel('lbl_referencing').'</label>
											<div><input type="button" id="y:data_filter:add_filter_extra-'.$ref_type_id.'_'.$ref_type_id.'_0_0_0_1" class="data add popup" value="filter" title="'.getLabel('inf_filter_add_extra').'" /><span class="split"></span>'.cms_general::createSelector(FilterTypeObjects::getReferenceSourceValues($arr_select_reference_sources), $name.$name_referenced.'[from]', ($arr_referenced['from'] ?: [])).'</div>
										</li>';
									
										$arr_html_extra = [];
										$has_extra = $arr_referenced['relationality'];
										$arr_html_extra[] = self::createFilterValueRelationality($arr_referenced['relationality'], $name.$name_referenced.'[relationality]', ['filter' => true, 'group' => true]);
										
										if (!$has_extra) {
											
											$return_content .= '<li class="extra"><label></label>
												<span><input type="button" class="data neutral extra" value="more" /></span>
											</li>';
										}
									
										foreach ($arr_html_extra as $value) {

											$return_content .= '<li class="extra '.(!$has_extra ? ' hide' : '').'"><label></label>
												<div>'.$value.'</div>
											</li>';
										}
									
									$return_content .= '</ul></fieldset>';
									
									$return_content .= '</div></div>';
									
									$arr_html_type_referenced_tabs['content'][] = $return_content;
								}

								$return .= '<div class="tabs">
									<ul>
										'.implode('', $arr_html_type_referenced_tabs['links']).'
									</ul>
									'.implode('', $arr_html_type_referenced_tabs['content']).'
								</div>';
							}
							
						$return .= '</div>
					</div>
					
				</div>
			</div>
		</div>';
		
		return ['content' => $return, 'link' => $return_link];
	}
	
	private function createFilterTypeObjectDefinitions($type_id, $filter_code, $arr_object_definitions = [], $arr_referenced = []) {
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_object_descriptions = ($arr_referenced ?: $arr_type_set['object_descriptions']);
		
		$html_handler = '<span class="icon">'.getIcon('min').'</span><span class="icon">'.getIcon('plus').'</span>';
		
		$return .= '<div class="tags plus"><ul>';
		
			foreach ($arr_object_descriptions as $object_description_id => $value) {
				
				$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, $object_description_id)) {
					continue;
				}
				
				$has_object_descriptions = true;
				
				$return .= '<li'.($arr_object_definitions[$object_description_id] ? ' class="min"' : '').'>'
					.'<span>'.strEscapeHTML(Labels::parseTextVariables($arr_object_description['object_description_name'])).'</span>'
					.'<span class="handler" data-tag_identifier="object_description_id-'.$object_description_id.'" id="y:data_filter:add_filter_object_definition-'.$type_id.'_'.$object_description_id.'_'.($arr_referenced ? 1 : 0).'">'.$html_handler.'</span>'
				.'</li>';
			}
				
		$return .= '</ul></div>
		
		<div class="object-descriptions fieldsets"><div>';
		
			foreach ($arr_object_descriptions as $object_description_id => $value) {
				
				$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
					
				if (!($arr_object_definitions[$object_description_id]) || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, $object_description_id)) {
					continue;
				}
					
				$return .= '<fieldset data-tag_identifier="object_description_id-'.$object_description_id.'">
					<ul>'.$this->createFilterTypeObjectDefinition($type_id, $object_description_id, $filter_code, $arr_object_definitions[$object_description_id], ($arr_referenced ? true : false)).'</ul>
				</fieldset>';
			}
		
		$return .= '</div></div>';
		
		return ($has_object_descriptions ? $return : '');
	}
	
	private function createFilterTypeObjectSubs($type_id, $filter_code, $arr_object_subs = [], $arr_referenced = [], $referenced_type_id = false) {
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_object_subs_details = ($arr_referenced ?: $arr_type_set['object_sub_details']);
		
		$html_handler = '<span class="icon">'.getIcon('min').'</span><span class="icon">'.getIcon('plus').'</span>';
		
		$return .= '<div class="tags plus"><ul>';
			
			if (!$arr_referenced) {
				
				$return .= '<li class="general'.($arr_object_subs[0] ? ' min' : '').'">'
					.'<span>'.getLabel('lbl_object_subs').': '.getLabel('lbl_any').'</span>'
					.'<span class="handler" data-tag_identifier="object_sub_details_id-0" id="y:data_filter:add_filter_object_sub-'.$type_id.'_0_0">'.$html_handler.'</span>'
				.'</li>';
			}
			foreach ($arr_object_subs_details as $object_sub_details_id => $value) {
				
				$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
					continue;
				}
				
				if ($arr_referenced && $arr_referenced[$object_sub_details_id]['object_sub_descriptions'] && !$arr_referenced[$object_sub_details_id]['object_sub_location']) {
					
					$has_object_sub_descriptions = false;
					
					foreach ($arr_referenced[$object_sub_details_id]['object_sub_descriptions'] as $object_sub_description_id => $arr_referenced_object_sub_description) {
						
						$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
					
						if ($_SESSION['NODEGOAT_CLEARANCE'] >= $arr_object_sub_description['object_sub_description_clearance_view'] && custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
							
							$has_object_sub_descriptions = true;
							break;
						}
					}
					
					if (!$has_object_sub_descriptions) {
						continue;
					}
				}
				
				$has_object_subs_details = true;

				$return .= '<li'.($arr_object_subs[$object_sub_details_id] ? ' class="min"' : '').'>'
					.'<span><span class="sub-name">'.strEscapeHTML(Labels::parseTextVariables($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_name'])).'</span></span>'
					.'<span class="handler" data-tag_identifier="object_sub_details_id-'.$object_sub_details_id.'" id="y:data_filter:add_filter_object_sub-'.$type_id.'_'.$object_sub_details_id.'_'.(int)$referenced_type_id.'">'.$html_handler.'</span>'
				.'</li>';
			}
		
		$return .= '</ul></div>
		
		<div class="object-sub-details fieldsets"><div>';
		
			if (!$arr_referenced && $arr_object_subs[0]) {
			
				$return .= $this->createFilterTypeObjectSubGeneral($type_id, $filter_code, $arr_object_subs[0]);
				unset($arr_object_subs[0]);
			}
			
			foreach ($arr_object_subs_details as $object_sub_details_id => $value) {
				
				$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
								
				if (!$arr_object_subs[$object_sub_details_id] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
					continue;
				}

				$return .= $this->createFilterTypeObjectSub($type_id, $object_sub_details_id, $filter_code, $arr_object_subs[$object_sub_details_id], $arr_referenced[$object_sub_details_id], $referenced_type_id);
			}
								
		$return .= '</div></div>';
		
		return ($has_object_subs_details ? $return : '');
	}
	
	private function createFilterTypeObjectDefinition($type_id, $object_description_id, $filter_code, $arr_object_definition = [], $referenced = false) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
		if ($referenced) {
			$arr_object_description['object_description_ref_type_id'] = $type_id;
		}
		$arr_options = ['source_direction' => ($referenced ? 'in' : 'out'), 'source_type_id' => $type_id, 'source_object_description_id' => $object_description_id, 'type' => $arr_object_description['object_description_value_type']];
		
		$name = $this->form_name.'[form]['.$filter_code.']'.($referenced ? '[referenced_types]['.$type_id.']' : '').'[object_definitions]['.$arr_object_description['object_description_id'].']';
		
		if (!$referenced) {
			$arr_options['transcension'] = $arr_object_definition['transcension'];
			unset($arr_object_definition['transcension']);
		}
		
		if ($arr_object_description['object_description_ref_type_id'] && (!$arr_object_description['object_description_is_dynamic'] || $referenced)) {
			
			if ($arr_object_description['object_description_has_multi'] || $referenced) {
				$arr_options['relationality'] = $arr_object_definition['relationality'];
				if ($arr_object_description['object_description_has_multi'] && !$referenced) {
					$arr_options['relationality_options']['filter'] = true;
				}
			}
			unset($arr_object_definition['relationality']);
			
			$return .= self::createFilterTypeReferences($arr_object_description['object_description_ref_type_id'], $arr_object_definition, $name, Labels::parseTextVariables($arr_object_description['object_description_name']), $arr_options);
		} else {
			
			$return .= self::createFilterValueType($arr_object_description['object_description_value_type'], $arr_object_definition, $name, $arr_object_description['object_description_value_type_settings'], Labels::parseTextVariables($arr_object_description['object_description_name']), $arr_options);
		}
		
		return $return;
	}
	
	private function createFilterTypeObjectSubDefinition($type_id, $object_sub_details_id, $object_sub_description_id, $filter_code, $arr_object_sub_definition = [], $referenced = false) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_object_sub_description = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
							
		if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
			
			$return .= $this->createFilterTypeObjectDefinition($type_id, $arr_object_sub_description['object_sub_description_use_object_description_id'], $filter_code);
			
		} else {
		
			if ($referenced) {
				$arr_object_sub_description['object_sub_description_ref_type_id'] = $type_id;
			}
			
			$arr_options = ['source_direction' => ($referenced ? 'in' : 'out'), 'source_type_id' => $type_id, 'source_object_sub_details_id' => $object_sub_details_id, 'source_object_sub_description_id' => $object_sub_description_id, 'type' => $arr_object_sub_description['object_sub_description_value_type']];
			
			$name = $this->form_name.'[form]['.$filter_code.']'.($referenced ? '[referenced_types]['.$type_id.']' : '').'[object_subs]['.$object_sub_details_id.'][object_sub_definitions]['.$object_sub_description_id.']';
						
			if (!$referenced) {
				$arr_options['transcension'] = $arr_object_sub_definition['transcension'];
				unset($arr_object_sub_definition['transcension']);
			}
			
			if ($arr_object_sub_description['object_sub_description_ref_type_id'] && (!$arr_object_sub_description['object_sub_description_is_dynamic'] || $referenced)) {
				
				if ($arr_object_sub_description['object_sub_description_has_multi'] || !$arr_object_sub_details['object_sub_details_is_single'] || $referenced) {
					$arr_options['relationality'] = $arr_object_sub_definition['relationality'];
					$arr_options['relationality_options']['filter'] = true;
					if ($referenced) {
						$arr_options['relationality_options']['group'] = true;
					}
				}
				unset($arr_object_sub_definition['relationality']);
				
				$return .= self::createFilterTypeReferences($arr_object_sub_description['object_sub_description_ref_type_id'], $arr_object_sub_definition, $name, Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']), $arr_options);
			} else {
				
				$return .= self::createFilterValueType($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition, $name, $arr_object_sub_description['object_sub_description_value_type_settings'], Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']), $arr_options);
			}
		}

		return $return;
	}
	
	private function createFilterTypeObjectSubGeneral($type_id, $filter_code, $arr_object_sub = []) {
	
		$arr_type_set = StoreType::getTypeSet($type_id);
		$object_sub_details_id = false;
		$object_sub_details_id_first = false;
		
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			$object_sub_details_id_first = ($object_sub_details_id_first ?: $object_sub_details_id);
			if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_required']) {
				break;
			}
		}
		$object_sub_details_id = ($object_sub_details_id ?: $object_sub_details_id_first);
		
		return $this->createFilterTypeObjectSub($type_id, [
			'object_sub_details' => [
				'object_sub_details_id' => 0,
				'object_sub_details_name' => 'any',
				'object_sub_details_location_ref_type_id' => $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_location_ref_type_id'],
				'object_sub_location_ref_object_sub_details_id' => $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_ref_object_sub_details_id']
			]
		], $filter_code, $arr_object_sub);
	}
	
	private function createFilterTypeObjectSub($type_id, $object_sub_details_id, $filter_code, $arr_object_sub = [], $arr_referenced = [], $referenced_type_id = false) {
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		if (is_array($object_sub_details_id)) { // Custom object_sub_details filter
			$arr_object_sub_details = $object_sub_details_id;
			$object_sub_details_id = $arr_object_sub_details['object_sub_details']['object_sub_details_id'];
		} else {
			$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
		}
		
		$str_name = ($arr_object_sub_details['object_sub_details']['object_sub_details_name'] == 'any' ? '<span>'.getLabel('lbl_object_subs').': '.getLabel('lbl_any').'</span>' : '<span class="sub-name">'.strEscapeHTML(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span>');
		$html_handler = '<span class="icon">'.getIcon('min').'</span><span class="icon">'.getIcon('plus').'</span>';
		
		$return .= '<fieldset data-tag_identifier="object_sub_details_id-'.$object_sub_details_id.'"><legend>'.$str_name.'</legend>';
			
			if (!$arr_referenced) {
				
				$name = $this->form_name.'[form]['.$filter_code.'][object_subs]['.$object_sub_details_id.']';
				
				$return .= '<ul>';
					
					if (!$object_sub_details_id || !$arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
						
						$return .= '<li>
							<label>'.getLabel('lbl_amount').'</label>
							<span>'.self::createFilterValueRelationality($arr_object_sub['object_sub']['relationality'], $name.'[object_sub][relationality]', ['label' => 'lbl_relationality_object_subs']).'</span>
						</li>';
					} else {
						
						$return .= '<li>
							<label>'.getLabel('lbl_amount').'</label>
							<span>'.cms_general::createSelectorRadio([['id' => '', 'name' => getLabel('lbl_any')], ['id' => '1', 'name' => getLabel('lbl_yes')], ['id' => '0', 'name' => getLabel('lbl_no')]], $name.'[object_sub][relationality][value]', $arr_object_sub['object_sub']['relationality']['value']).'</span>
						</li>';
					}
					
					if (!$object_sub_details_id || $arr_object_sub_details['object_sub_details']['object_sub_details_has_date']) {
							
						$return .= '<li>
							<label>'.getLabel('lbl_date').'</label>
							<span><input type="button" class="data del" data-section="date" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" data-section="date" value="add" /></span>
						</li>';
						
						foreach (($arr_object_sub['object_sub_dates'] ?: [[]]) as $arr_object_sub_date) {
							
							$unique_date = uniqid('array_');
								
							$return .= '<li class="date-section start">
								<label></label>
								<div><select name="'.$name.'[object_sub_dates]['.$unique_date.'][object_sub_date_type]">'.cms_general::createDropdown(self::$arr_date_options, $arr_object_sub_date['object_sub_date_type']).'</select></div>
							</li>
							<li class="date-section">
								<label></label>
								<div><input type="text" class="date" name="'.$name.'[object_sub_dates]['.$unique_date.'][object_sub_date_from]" value="'.$arr_object_sub_date['object_sub_date_from'].'" /></div>
							</li>
							<li class="date-section">
								<label></label>
								<div><input type="text" class="date" name="'.$name.'[object_sub_dates]['.$unique_date.'][object_sub_date_to]" value="'.$arr_object_sub_date['object_sub_date_to'].'" /></div>
							</li>
							<li class="date-section">
								<label></label>
								<div>'.StoreTypeObjects::formatToFormValueFilter('date', $arr_object_sub_date['object_sub_date_start'], $name.'[object_sub_dates]['.$unique_date.'][object_sub_date_start]').'</div>
							</li>';
								
							if (!$object_sub_details_id || $arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
								
								$return .= '<li class="date-section">
									<label></label>
									<div>'.StoreTypeObjects::formatToFormValueFilter('date', $arr_object_sub_date['object_sub_date_end'], $name.'[object_sub_dates]['.$unique_date.'][object_sub_date_end]').'</div>
								</li>';
							}
							
							if ($arr_object_sub_date['object_sub_date_chronology']) {
								if (!is_array($arr_object_sub_date['object_sub_date_chronology'])) {
									$arr_object_sub_date['object_sub_date_chronology'] = json_decode($arr_object_sub_date['object_sub_date_chronology'], true);
								}
								$arr_object_sub_date['object_sub_date_chronology'] = value2JSON($arr_object_sub_date['object_sub_date_chronology'], JSON_PRETTY_PRINT);
							}
														
							$return .= '<li class="date-section">
								<label></label>
								<textarea name="'.$name.'[object_sub_dates]['.$unique_date.'][object_sub_date_chronology]" placeholder="ChronoJSON">'.($arr_object_sub_date['object_sub_date_chronology'] ?: '').'</textarea>
							</li>
							<li class="date-section">
								<label></label>
								<div><input type="button" class="data add popup" id="y:data_entry:select_chronology-'.$type_id.'_'.$object_sub_details_id.'_'.$object_id.'_1" value="create" /></div>
							</li>';
							
							$return .= '<li class="date-section">
								<label></label>
								<ul class="sorter">';
									
									$arr_html_extra = [];
									$has_extra = ($has_extra ?: $arr_object_sub_date['object_sub_date_value']);
									
									$arr_object_sub_date['object_sub_date_value']['transcension'] = ($arr_object_sub_date['object_sub_date_value']['transcension'] ?: ['value' => 'any']);
									$arr_transcension = FilterTypeObjects::getTranscensionValues();
									$arr_html_extra[] = cms_general::createSelectorRadio($arr_transcension, $name.'[object_sub_dates]['.$unique_date.'][object_sub_date_value][transcension][value]', $arr_object_sub_date['object_sub_date_value']['transcension']['value']);
									
									if (!$has_extra) {
										
										$return .= '<li class="extra">
											<div><input type="button" class="data neutral extra" value="more" /></div>
										</li>';
									}
		
									foreach ($arr_html_extra as $value) {
		
										$return .= '<li class="extra '.(!$has_extra ? ' hide' : '').'">
											<div>'.$value.'</div>
										</li>';
									}
								$return .= '</ul>
							</li>';
						}
					}
					
					if (!$object_sub_details_id || $arr_object_sub_details['object_sub_details']['object_sub_details_has_location']) {
							
						$return .= '<li>
							<label>'.getLabel('lbl_location').'</label>
							<span><input type="button" class="data del" data-section="location" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" data-section="location" value="add" /></span>
						</li>';
						
						$arr_types = StoreType::getTypes();
							
						foreach (($arr_object_sub['object_sub_locations'] ?: [[]]) as $arr_object_sub_location) {
								
								$unique_location = uniqid('array_');
								
								$return .= '<li class="location-section start">
									<label></label>
									<div><select name="'.$name.'[object_sub_locations]['.$unique_location.'][object_sub_location_type]">'.cms_general::createDropdown(self::$arr_location_options, $arr_object_sub_location['object_sub_location_type']).'</select></div>
								</li>
								<li class="location-section">
									<label></label>
									<div><input type="text" name="'.$name.'[object_sub_locations]['.$unique_location.'][object_sub_location_latitude]" value="'.$arr_object_sub_location['object_sub_location_latitude'].'" placeholder="'.getLabel('lbl_latitude').'" /><label title="'.getLabel('lbl_latitude').'">λ</label></div>
								</li>
								<li class="location-section">
									<label></label>
									<div><input type="text" name="'.$name.'[object_sub_locations]['.$unique_location.'][object_sub_location_longitude]" value="'.$arr_object_sub_location['object_sub_location_longitude'].'" placeholder="'.getLabel('lbl_longitude').'" /><label title="'.getLabel('lbl_longitude').'">φ</label></div>
								</li>
								<li class="location-section">
									<label></label>
									<span><input type="button" class="data add" id="y:data_filter:select_geometry-0" value="map" /></span>
								</li>
								<li class="location-section">
									<label></label>
									<ul class="sorter">';
										
										$arr_html_extra = [];
										$has_extra = ($arr_object_sub_location['object_sub_location_value']['radius']);
										
										$arr_html_extra[] = '<input type="number" title="'.getLabel('lbl_radius_km').'" name="'.$name.'[object_sub_locations]['.$unique_location.'][object_sub_location_value][radius]" value="'.$arr_object_sub_location['object_sub_location_value']['radius'].'" />';
										
										if (!$has_extra) {
											
											$return .= '<li class="extra">
												<div><input type="button" class="data neutral extra" value="more" /></div>
											</li>';
										}

										foreach ($arr_html_extra as $value) {

											$return .= '<li class="extra '.(!$has_extra ? ' hide' : '').'">
												<div>'.$value.'</div>
											</li>';
										}
									$return .= '</ul>
								</li>
								<li class="location-section">
									<label></label>
									<textarea name="'.$name.'[object_sub_locations]['.$unique_location.'][object_sub_location_geometry]" placeholder="GeoJSON">'.$arr_object_sub_location['object_sub_location_geometry'].'</textarea>
								</li>
								<li class="location-section">
									<label></label>
									<div><input type="button" class="data add select_geometry" value="create" /></div>
								</li>
								<li class="location-section">
									<label></label>
									<ul class="sorter">';
										
										$arr_html_extra = [];
										$has_extra = ($arr_object_sub_location['object_sub_location_value']['transcension']);
													
										$arr_object_sub_location['object_sub_location_value']['transcension'] = ($arr_object_sub_location['object_sub_location_value']['transcension'] ?: ['value' => 'any']);
										$arr_transcension = FilterTypeObjects::getTranscensionValues();
										$arr_html_extra[] = cms_general::createSelectorRadio($arr_transcension, $name.'[object_sub_locations]['.$unique_location.'][object_sub_location_value][transcension][value]', $arr_object_sub_location['object_sub_location_value']['transcension']['value']);
										
										if (!$has_extra) {
											
											$return .= '<li class="extra">
												<div><input type="button" class="data neutral extra" value="more" /></div>
											</li>';
										}

										foreach ($arr_html_extra as $value) {

											$return .= '<li class="extra '.(!$has_extra ? ' hide' : '').'">
												<div>'.$value.'</div>
											</li>';
										}
									$return .= '</ul>
								</li>
								<li class="location-section">
									<label></label>
									<ul class="sorter">
										<li>
											<select name="'.$name.'[object_sub_locations]['.$unique_location.'][object_sub_location_ref_type_id]" id="y:data_filter:selector_object_sub_details-0" class="update_object_type">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types, ($arr_object_sub_location['object_sub_location_ref_type_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']))).'</select>'
											.'<select name="'.$name.'[object_sub_locations]['.$unique_location.'][object_sub_location_ref_object_sub_details_id]">'.Labels::parseTextVariables(cms_general::createDropdown(StoreType::getTypeObjectSubsDetails((($arr_object_sub_location['object_sub_location_ref_type_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']) ?: key($arr_types))), ($arr_object_sub_location['object_sub_location_ref_object_sub_details_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id']), false, 'object_sub_details_name', 'object_sub_details_id')).'</select>
										</li>
										<li>
											<fieldset><ul>';
											
												$arr_options = ['source_direction' => 'out', 'source_type_id' => $type_id, 'source_object_sub_details_id' => $object_sub_details_id, 'source_object_sub_description_id' => 'location', 'type' => 'type', 'selectable_type_id' => true];
												
												$arr_options['beacon'] = $arr_object_sub_location['object_sub_location_reference']['beacon'];
												unset($arr_object_sub_location['object_sub_location_reference']['beacon']);
												
												$arr_options['mode'] = $arr_object_sub_location['object_sub_location_reference']['mode'];
												unset($arr_object_sub_location['object_sub_location_reference']['mode']);
												
												$return .= self::createFilterTypeReferences($arr_object_sub_location['object_sub_location_ref_type_id'], $arr_object_sub_location['object_sub_location_reference'], $name.'[object_sub_locations]['.$unique_location.'][object_sub_location_reference]', '', $arr_options);
											
											$return .= '</ul></fieldset>
										</li>
									</ul>
								</li>';
						}
					}
					
					$return .= '</ul>';
			} else if ($arr_referenced['object_sub_location']) {
				
				$name = $this->form_name.'[form]['.$filter_code.'][referenced_types]['.$type_id.'][object_subs]['.$object_sub_details_id.'][object_sub_location_reference]';
				
				$return .= '<ul>';
					
					$arr_options = ['source_direction' => 'in', 'source_type_id' => $type_id, 'source_object_sub_details_id' => $object_sub_details_id, 'source_object_sub_description_id' => 'location', 'type' => 'type'];
					
					$arr_options['relationality'] = $arr_object_sub['object_sub_location_reference']['relationality'];
					$arr_options['relationality_options']['filter'] = true;
					$arr_options['relationality_options']['group'] = true;
					unset($arr_object_sub['object_sub_location_reference']['relationality']);
										
					$return .= self::createFilterTypeReferences($arr_referenced['object_sub_location']['type_id'], $arr_object_sub['object_sub_location_reference'], $name, getLabel('lbl_location_reference'), $arr_options);
											
				$return .= '</ul>';
			}
			
			$arr_object_sub_descriptions = ($arr_referenced ? $arr_referenced['object_sub_descriptions'] : $arr_object_sub_details['object_sub_descriptions']);
			$arr_html_object_sub_descriptions = [];
			
			foreach ((array)$arr_object_sub_descriptions as $object_sub_description_id => $value) {
				
				$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
					continue;
				}
				
				$arr_html_object_sub_descriptions['html_tags'] .= '<li'.($arr_object_sub['object_sub_definitions'][$object_sub_description_id] || $referenced_type_id ? ' class="min"' : '').'>'
					.'<span>'.strEscapeHTML(Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'])).'</span>'
					.'<span class="handler" data-tag_identifier="object_sub_description_id-'.$object_sub_description_id.'" id="y:data_filter:add_filter_object_sub_definition-'.$type_id.'_'.$object_sub_details_id.'_'.$object_sub_description_id.'_'.(int)$referenced_type_id.'">'.$html_handler.'</span>'
				.'</li>';
			
				if (!($arr_object_sub['object_sub_definitions'][$object_sub_description_id] || $referenced_type_id)) {
					continue;
				}
					
				$arr_html_object_sub_descriptions['html'] .= '<fieldset data-tag_identifier="object_sub_description_id-'.$object_sub_description_id.'">
					<ul>'.$this->createFilterTypeObjectSubDefinition($type_id, $object_sub_details_id, $object_sub_description_id, $filter_code, $arr_object_sub['object_sub_definitions'][$object_sub_description_id], $referenced_type_id).'</ul>
				</fieldset>';
			}
			
			if ($arr_html_object_sub_descriptions) {
				
				$return .= '<div class="options">
					<div class="tags plus"><ul>
						'.$arr_html_object_sub_descriptions['html_tags'].'
					</ul></div>
					
					<div class="object-sub-descriptions fieldsets"><div>
						'.$arr_html_object_sub_descriptions['html'].'
					</div></div>
				</div>';
			}

		$return .= '</fieldset>';
		
		return $return;
	}
	
	public function createFilterExternal($resource_id, $arr_filter = []) {
	
		$arr_resource = StoreResourceExternal::getResources($resource_id);
		$external = new ResourceExternal($arr_resource);

		$arr_query_variables = $external->getQueryVariables();

		$html_handler = '<span class="icon">'.getIcon('min').'</span><span class="icon">'.getIcon('plus').'</span>';

		$return = '<div class="filter external options">';
		
			foreach ($arr_query_variables as $name_query => $arr_variables) {
			
				$return .= '<h3>'.strEscapeHTML(Labels::parseTextVariables($name_query)).'</h3>
				
				<div class="tags plus"><ul>';
				
					foreach ($arr_variables as $name_variable => $arr_variable) {
						
						$return .= '<li'.($arr_filter[$name_query][$name_variable] ? ' class="min"' : '').'>'
							.'<span>'.strEscapeHTML(Labels::parseTextVariables($name_variable)).'</span>'
							.'<span class="handler" data-tag_identifier="'.strEscapeHTML($name_query).'_'.strEscapeHTML($name_variable).'" id="y:data_filter:add_filter_external_variable-'.$resource_id.'_'.strEscapeHTML($name_query).'_'.strEscapeHTML($name_variable).'">'.$html_handler.'</span>'
						.'</li>';
					}
						
				$return .= '</ul></div>
				
				<div class="external-references fieldsets"><div>';
				
					foreach ($arr_variables as $name_variable => $arr_variable) {
						
						if (!$arr_filter[$name_query][$name_variable]) {
							continue;
						}
												
						$return .= '<fieldset data-tag_identifier="'.strEscapeHTML($name_query).'_'.strEscapeHTML($name_variable).'">
							<ul>'.$this->createFilterExternalVariable($resource_id, $name_query, $name_variable, $arr_filter[$name_query][$name_variable]).'</ul>
						</fieldset>';
					}
				
				$return .= '</div></div>';
			}
		$return .= '</div>';
		
		return $return;
	}
	
	private function createFilterExternalVariable($resource_id, $name_query, $name_variable, $arr_filter_variable = []) {
		
		$arr_resource = StoreResourceExternal::getResources($resource_id);
		$external = new ResourceExternal($arr_resource);
		
		$arr_query_variables = $external->getQueryVariables();
		
		$name = 'filter_external['.strEscapeHTML($name_query).']['.strEscapeHTML($name_variable).']';
		
		$arr_variable = $arr_query_variables[$name_query][$name_variable];
		
		$return = self::createFilterExternalValueType($arr_variable['type'], $arr_filter_variable, $name, false, Labels::parseTextVariables($name_variable));
		
		return $return;
	}
	
	public static function createFilterExternalValueType($type, $arr_values, $name, $arr_type_options = false, $label = '', $arr_options = []) {
		
		switch ($type) {
			default:
				$return = '<li>
					<label>'.strEscapeHTML($label).'</label>'.ResourceExternal::formatToFormValueFilter($type, $arr_values[0], $name.'[]', $arr_type_options).'
				</li>';
				break;
		}
				
		return $return;
	}
	
	public static function createFilterValueType($type, $arr_values, $name, $arr_type_options = false, $label = '', $arr_options = []) {
		
		switch ($type) {
			case 'boolean':
			
				$return = '<li>
					<label>'.strEscapeHTML($label).'</label>'.StoreTypeObjects::formatToFormValueFilter($type, $arr_values[0], $name.'[]', $arr_type_options).'
				</li>';

				break;
			case 'text_tags':
			case 'reversed_collection':
			
				unset($arr_options['transcension']);
				
				$arr_sorter = [];
				$arr_types = StoreType::getTypes(StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id']));
				
				foreach (($arr_values['type_tags'] ?: [[]]) as $type_id => $arr_tags) {
					
					$unique = uniqid('array_');
					$arr_options_extra = ['relationality' => $arr_tags['objects']['relationality'], 'transcension' => $arr_tags['objects']['transcension'], 'selectable_type_id' => true, 'type' => ''] + $arr_options;
					unset($arr_tags['objects']['relationality'], $arr_tags['objects']['transcension']);
					
					$arr_sorter[] = ['value' => [
							'<select name="'.$name.'[type_tags]['.$unique.'][type_id]" class="update_object_type">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types, $type_id, true)).'</select>',
							'<fieldset><ul>
								'.self::createFilterTypeReferences($type_id, $arr_tags['objects'], $name.'[type_tags]['.$unique.'][objects]', '', $arr_options_extra).'
								'.($type == 'text_tags' ? self::createFilterValueType('', $arr_tags['values'], $name.'[type_tags]['.$unique.'][values]') : '').'
							</ul></fieldset>'
						]
					];
				}
				
				$arr_options_extra = ['transcension' => $arr_values['text']['transcension']] + $arr_options;
				unset($arr_values['text']['transcension']);
				
				$return = self::createFilterValueType('text', $arr_values['text'], $name.'[text]', false, $label, $arr_options_extra).'
				<li>
					<label>'.getLabel('lbl_tags').'</label>
					<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
				</li><li>
					<label></label>
					'.cms_general::createSorter($arr_sorter, false, true).'
				</li>';

				break;
			case 'module':
			
				unset($arr_options['transcension']);
				
				$str_class_module = EnucleateValueTypeModule::getClassName($arr_type_options['type']);
				$arr_fields = $str_class_module::getValueFields();
				
				$return = '';
				
				foreach ($arr_fields as $str_identifier => $arr_field) {
					
					$arr_options_extra = ['transcension' => $arr_values[$str_identifier]['transcension']] + $arr_options;
					unset($arr_values[$str_identifier]['transcension']);
				
					$return .= self::createFilterValueType($arr_field['type'], $arr_values[$str_identifier], $name.'['.$str_identifier.']', false, $arr_field['name'], $arr_options_extra);
				}

				break;
			default:
			
				$arr_sorter = [];
				
				foreach (($arr_values ?: ['']) as $value) {
					$unique = uniqid('array_');
					$arr_sorter[] = ['value' => StoreTypeObjects::formatToFormValueFilter($type, $value, $name.'['.$unique.']', $arr_type_options)];
				}
				
				$return = '<li>
					<label>'.strEscapeHTML($label).'</label>
					<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
				</li><li>
					<label></label>
					'.cms_general::createSorter($arr_sorter, false, true).'
				</li>';
		}
		
		$arr_html_extra = [];
		
		if (array_key_exists('transcension', $arr_options)) {
			$has_extra = ($has_extra ?: $arr_options['transcension']);
			$arr_options['transcension'] = ($arr_options['transcension'] ?: ['value' => 'any']);
			$arr_transcension = FilterTypeObjects::getTranscensionValues();
			$arr_html_extra[] = cms_general::createSelectorRadio($arr_transcension, $name.'[transcension][value]', $arr_options['transcension']['value']);
		}
		
		if ($arr_html_extra) {
				
			if (!$has_extra) {
				
				$return .= '<li class="extra"><label></label>
					<span><input type="button" class="data neutral extra" value="more" /></span>
				</li>';
			}
		
			foreach ($arr_html_extra as $value) {

				$return .= '<li class="extra '.(!$has_extra ? ' hide' : '').'"><label></label>
					<div>'.$value.'</div>
				</li>';
			}
		}
		
		return $return;
	}
	
	public static function createFilterTypeReferences($type_id, $arr_values, $name, $label = '', $arr_options = []) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		switch ($arr_options['type']) {
			case 'text_tags':
			case 'reversed_collection':
				
				$arr_values['objects'] = $arr_values['objects'];
				
				if ($arr_options['type'] == 'text_tags') {
					$arr_values['values'] = $arr_values['values'];
				}
				
				$arr_options['relationality'] = $arr_values['objects']['relationality'];
				unset($arr_values['objects']['relationality']);
				
				$name_extra = '[objects]';
			
				break;
			default:

				$arr_values['objects'] = $arr_values;
				$name_extra = '';
		}
		
		$arr_sorter = [];
		
		if ($arr_values['objects']) {
			$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($type_id, $arr_values['objects']);
		}
		
		foreach (($arr_type_object_names ?: ['']) as $object_id => $value) {
			$unique = uniqid('array_');
			$arr_sorter[] = ['value' => '<span><input type="hidden" id="y:data_filter:lookup_type_object_pick-'.(int)($arr_options['selectable_type_id'] ? 0 : $type_id).'" name="'.$name.$name_extra.'['.$unique.']" value="'.$object_id.'" /><input type="search" id="y:data_filter:lookup_type_object-'.(int)($arr_options['selectable_type_id'] ? 0 : $type_id).'" class="autocomplete" value="'.$value.'" /></span>'];
		}
		
		$return = '<li>
			<label>'.strEscapeHTML($label).'</label>
			<span>'
				.'<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'
				.(keyIsUncontested('filter_deep', $arr_options) ? 
					'<span class="split"></span>'
					.'<input type="button" id="y:data_filter:add_filter_extra-'.(int)($arr_options['selectable_type_id'] ? 0 : $type_id).'_'.(int)$arr_options['source_type_id'].'_'.($arr_options['source_object_description_id'] ?: 0).'_'.($arr_options['source_object_sub_details_id'] ?: 0).'_'.($arr_options['source_object_sub_description_id'] ?: 0).'_'.($arr_options['source_direction'] == 'in' ? 1 : 0).'" class="data add popup" value="filter" title="'.getLabel('inf_filter_add_extra').'" />'
				: '')
				.(array_key_exists('beacon', $arr_options) ? '<input type="hidden" name="'.$name.$name_extra.'[beacon]" value="'.$arr_options['beacon'].'" />' : '')
			.'</span>
		</li><li>
			<label></label>
			'.cms_general::createSorter($arr_sorter, false, true).'
		</li>';
		
		$arr_html_extra = [];
		
		if (array_key_exists('radius', $arr_options)) {
			$has_extra = ($has_extra ?: $arr_options['radius']);
			$arr_html_extra[] = '<input type="number" title="'.getLabel('lbl_radius_km').'" name="'.$name.$name_extra.'[radius]" value="'.$arr_options['radius'].'" />';
		}
		
		if (array_key_exists('mode', $arr_options)) {
			$has_extra = ($has_extra ?: $arr_options['mode']);
			$arr_options['mode'] = ($arr_options['mode'] ?: 'default');
			$arr_mode = [['id' => 'default', 'name' => 'Resolved'], ['id' => 'self', 'name' => 'Self']];
			$arr_html_extra[] = cms_general::createSelectorRadio($arr_mode, $name.$name_extra.'[mode]', $arr_options['mode']);
		}		

		if (array_key_exists('transcension', $arr_options)) {
			$has_extra = ($has_extra ?: $arr_options['transcension']);
			$arr_options['transcension'] = ($arr_options['transcension'] ?: ['value' => 'any']);
			$arr_transcension = FilterTypeObjects::getTranscensionValues();
			$arr_html_extra[] = cms_general::createSelectorRadio($arr_transcension, $name.$name_extra.'[transcension][value]', $arr_options['transcension']['value']);
		}
		
		if (array_key_exists('relationality', $arr_options)) {
			$has_extra = ($has_extra ?: $arr_options['relationality']);
			$arr_html_extra[] = self::createFilterValueRelationality($arr_options['relationality'], $name.$name_extra.'[relationality]', $arr_options['relationality_options']);
		}
		
		if ($arr_html_extra) {
				
			if (!$has_extra) {
				
				$return .= '<li class="extra"><label></label>
					<span><input type="button" class="data neutral extra" value="more" /></span>
				</li>';
			}
		
			foreach ($arr_html_extra as $value) {

				$return .= '<li class="extra '.(!$has_extra ? ' hide' : '').'"><label></label>
					<div>'.$value.'</div>
				</li>';
			}
		}
		
		if (array_key_exists('values', $arr_values)) {
			
			$name_extra = '[values]';
			
			$return .= self::createFilterValueType('', $arr_values['values'], $name.$name_extra, false, $label);
		}
		
		return $return;
	}
	
	private static function createFilterValueRelationality($arr_relationality, $name, $arr_options = []) {
		
		$arr_relationality = ($arr_relationality ?: ['equality' => '=', 'value' => '']);
		$arr_equality = FilterTypeObjects::getEqualityValues();
		unset($arr_equality['≠']);
		
		$return = '<select name="'.$name.'[equality]">'.cms_general::createDropdown($arr_equality, $arr_relationality['equality']).'</select>'
			.'<input type="number" title="'.getLabel($arr_options['label'] ?: 'lbl_relationality').'" name="'.$name.'[value]" value="'.$arr_relationality['value'].'" placeholder="'.getLabel('lbl_any').'" />'
			.'<input type="number" name="'.$name.'[range]" value="'.$arr_relationality['range'].'" />'
			.($arr_options['filter'] ? cms_general::createSelectorRadio([['title' => getLabel('inf_relationality_filter_no'), 'id' => 0, 'name' => getLabel('lbl_relationality_filter_no')], ['title' => getLabel('inf_relationality_filter_yes'), 'id' => 1, 'name' => getLabel('lbl_relationality_filter_yes')]], $name.'[filter]', $arr_relationality['filter']) : '')
			.($arr_options['filter'] && $arr_options['group'] ? '<span class="split"></span>' : '')
			.($arr_options['group'] ? cms_general::createSelectorRadio([['title' => getLabel('inf_relationality_group_no'), 'id' => 0, 'name' => getLabel('lbl_relationality_group_no')], ['title' => getLabel('inf_relationality_group_yes'), 'id' => 1, 'name' => getLabel('lbl_relationality_group_yes')]], $name.'[group]', $arr_relationality['group']) : '');
		
		return $return;
	}
	
	private function createSelectFilterExtra($type_id, $arr_type_filter = false) {
		
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = [];

		if ($arr_type_filter !== false) {
			
			$filter = new FilterTypeObjects($type_id);
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id'])]);
			$arr_type_filter = $filter->cleanupFilterInput(($arr_type_filter ?: []));
		} else {

			$arr_use_project_ids = array_keys($arr_project['use_projects']);
		}
				
		$return = '<div class="tabs">
			<ul>
				<li><a href="#storage">'.getLabel(($arr_type_filter === false ? 'lbl_select' : 'lbl_save')).'</a></li>
				<li><a href="#advanced">'.getLabel('lbl_advanced').'</a></li>
			</ul>
			<div>
				<div class="options">
					<fieldset>
						<ul>
							<li><label>'.getLabel('lbl_filter').'</label><div id="x:custom_projects:filter_storage-'.(int)$type_id.'">'
								.'<select name="filter_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids), false, true, 'label').'</select>'
								.($arr_type_filter !== false ? '<input type="button" class="data add popup add_filter_storage" value="save" />'
								.'<input type="button" class="data del msg del_filter_storage" value="del" />' : '')
							.'</div></li>
						</ul>
					</fieldset>
				</div>
			</div>
			<div>
				<div class="options">
					<fieldset>
						<ul>
							<li><label>'.getLabel('lbl_form').'</label><div>'
								.'<textarea name="plain">'.($arr_type_filter ? value2JSON($arr_type_filter, JSON_PRETTY_PRINT) : '').'</textarea>'
							.'</div></li>
						</ul>
					</fieldset>
				</div>
			</div>
		</div>';
		
		// SiteStartVars::getModUrl($this->mod_id, false, false, true, array('project.v', $_SESSION['custom_projects']['project_id'])).'filter.v/';
		
		return $return;
	}
	
	private function createFilterVersioning($type_id, $arr_type_versioning = []) {
		
		if ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_INTERACT) {
			return;
		}
		
		$name = $this->form_name.'[versioning]';
			
		$return = '<div class="tabs versioning">
			<ul>
				<li><a href="#">'.getLabel('lbl_version').'</a></li>
				<li><a href="#">'.getLabel('lbl_users').'</a></li>
			</ul>
			
			<div>
				<div class="options fieldsets"><div>
					<fieldset><legend>'.getLabel('lbl_version').'</legend>
					<ul><li>
						'.cms_general::createSelectorList([
							['id' => '1', 'name' => getLabel('lbl_added')],
							['id' => '2', 'name' => getLabel('lbl_changed')],
							['id' => '3', 'name' => getLabel('lbl_deleted')]
						], $name.'[version]', ($arr_type_versioning['version'] ?: [])).'
					</li></ul>
					</fieldset>
					
					<fieldset><legend>'.getLabel('lbl_audited').'</legend>
					<ul><li>'.cms_general::createSelectorRadioList([
							['id' => 'no', 'name' => getLabel('lbl_no')],
							['id' => 'yes', 'name' => getLabel('lbl_yes')],
							['id' => 'all', 'name' => getLabel('lbl_both')]
						], $name.'[audited]', ($arr_type_versioning['audited'] ?: 'all')).'
					</li></ul>
					</fieldset>
					
					<fieldset><legend>'.getLabel('lbl_source').'</legend>
					<ul><li>'.cms_general::createSelectorRadioList([
							['id' => 'object', 'name' => getLabel('lbl_object')],
							['id' => 'object_sub', 'name' => getLabel('lbl_object_sub')],
							['id' => 'all', 'name' => getLabel('lbl_both')]
						], $name.'[source]', ($arr_type_versioning['source'] ?: 'all')).'
					</li></ul>
					</fieldset>
					
					<fieldset><legend>'.getLabel('lbl_date').'</legend>
					<ul><li>
						<label>'.getLabel('lbl_date_start').'</label>
						<input type="text" class="datepicker" name="'.$name.'[date][start]" value="'.$arr_type_versioning['date']['start'].'" />
					</li><li>
						<label>'.getLabel('lbl_date_end').'</label>
						<input type="text" class="datepicker" name="'.$name.'[date][end]" value="'.$arr_type_versioning['date']['end'].'" />
					</li></ul>
					</fieldset>
					
				</div></div>
			</div>
			
			<div>
				<div class="options fieldsets"><div>';
					
					if ($_SESSION['USER_ID']) {
						
						$arr_users = user_management::filterUsers(false, [], false);
						
						//$arr_user_found = StoreTypeObjects::getTypeUsers($type_id, array_keys($arr_users));
						$arr_user_found = StoreTypeObjects::getActiveUsers(array_keys($arr_users));
						
						$arr_users = array_intersect_key($arr_users, $arr_user_found);
					
						$return .= '<fieldset><legend>'.getLabel('lbl_options').'</legend>
							<ul>
								<li><label>'.getLabel('lbl_exclude').'</label><span><input type="checkbox" name="'.$name.'[users][exclude]" value="1" title="'.getLabel('inf_filter_user_exclude').'"'.($arr_type_versioning['users']['exclude'] ? ' checked="checked"' : '').' /></span></li>
								<li><label>'.getLabel('lbl_user_self').'</label><span><input type="checkbox" name="'.$name.'[users][self]" value="1" title="'.getLabel('inf_user_self').'"'.($arr_type_versioning['users']['self'] ? ' checked="checked"' : '').' /></span></li>
							</ul>
							</fieldset>
							<fieldset><legend>'.getLabel('lbl_users').'</legend>
							<ul>
								<li>'.cms_general::createSelectorList($arr_users, $name.'[users][selection]', ($arr_type_versioning['users']['selection'] ?: [])).'</li>
							</ul>
						</fieldset>';
					}
				$return .= '</div></div>
			</div>
			
		</div>';
		
		return $return;
	}
			
	public static function css() {
	
		$return = '
			.filter fieldset ul > li > div > input[name*=object_sub_location_radius],
			.filter fieldset ul > li.extra > div > input[type=number],
			.filter fieldset ul > li.extra > div > ul > li > input[type=number],
			.filter fieldset input[type=number][name*="[object_sub][relationality]"] { width: 50px; }
			.filter fieldset input[type=number][name*="[operator_extra]"] { margin-left: 6px; width: 35px; }
			.filter .tags li.general {  }
			.filter .object-descriptions > div:empty,
			.filter .object-sub-details > div:empty,
			.filter .object-sub-descriptions > div:empty,
			.filter .external-references > div:empty { display: none; }
			.point.labmap { height: 750px; }
			
			.filter-storage ul > li > label:first-child + div > textarea[name=plain] { width: 400px; height: 300px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.dynamic('filtering', function(elm_scripter) {
		
			var func_find_select_type = function(elm) {
				
				const str_selector = '.update_object_type';
				var elm_target = $(elm).closest('li');
				
				var elm_target_find = elm_target.find(str_selector);
				
				if (!elm_target_find.length) {
					elm_target_find = elm_target.prev('li').find(str_selector);
				}
				
				if (!elm_target_find.length) {
					
					var elm_in_list = elm_target.closest('ul.sorter > li');
					elm_target_find = elm_in_list.find(str_selector);
					
					if (!elm_target_find.length) {
					
						elm_in_list = elm_in_list.parent('ul').closest('ul.sorter > li');
						elm_target_find = elm_in_list.find(str_selector);
						
						if (!elm_target_find.length) {
							elm_target_find = elm_in_list.prev('li').find(str_selector);
						}
					}
				}
				
				return elm_target_find;
			};
			
			var func_find_select_type_object = function(elm, is_select_type_object_sub) {
			
				const str_selector = '[id^=y\\\:data_filter\\\:lookup_type_object-]';
				var elm_row = $(elm).closest('li');
				
				var elms_select_object = elm_row.find(str_selector);
									
				if (!elms_select_object.length) {
				
					if (is_select_type_object_sub) { // Look backwards from select_type_object_sub
						elms_select_object = elm_row.prev('li').find(str_selector);
					} else {
						elms_select_object = elm_row.next('li').find(str_selector);
					}
				}
				
				if (!elms_select_object.length) {
					return;
				}
				
				elms_select_object = elms_select_object.prev('input[type=hidden]');
				
				return elms_select_object;
			};
			
			var func_clear_select_type_object = function(elms_select_object) {
			
				if (!elms_select_object) {
					return;
				}
				
				var elms_select_object = $(elms_select_object).next('.autocomplete');
				
				for (var i = 0, len = elms_select_object.length; i < len; i++) {
				
					elms_select_object[i].autocompleter.clear();

					func_update_select_type_object_sub(func_find_select_type_object_sub(elms_select_object[i].previousSibling));
				}
			};
			
			var func_find_select_type_object_sub = function(elm) {
			
				const str_selector = 'input[name$=\"[object_sub_date_object_sub_id]\"], input[name$=\"[date_object_sub_id]\"]';
				var elm_row = $(elm).closest('li');
				
				var elm_select_object_sub = elm_row.find(str_selector);
									
				if (!elm_select_object_sub.length) {
					elm_select_object_sub = elm_row.next('li').find(str_selector);
				}
				
				if (!elm_select_object_sub.length) {
					return;
				}
				
				return elm_select_object_sub;
			};
			
			var func_update_select_type_object_sub = function(elm_select_object_sub, objecht_sub_id, objecht_sub_name) {

				if (!elm_select_object_sub) {
					return;
				}
				
				var elm_select_object_sub = $(elm_select_object_sub);
				
				elm_select_object_sub.val((objecht_sub_id ? objecht_sub_id : ''))
				elm_select_object_sub.next('input[type=search]').val((objecht_sub_name ? objecht_sub_name : ''));
			};
		
			elm_scripter.on('ajaxloaded scripter', function(e) {
			
				if (!getElement(e.detail.elm)) {
					return;
				}
			
				runElementsSelectorFunction(e.detail.elm, '[name*=object_sub_date_type], [name*=\"chronology[start][type]\"], [name*=\"chronology[end][type]\"], [name*=\"_infinite]\"]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_date_type');
				});
				runElementsSelectorFunction(e.detail.elm, '[name*=object_sub_location_type]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_location_type');
				});
			}).on('change update_date_type', '.filter [name*=\"[object_sub_date_type]\"]', function() { // Filter
			
				var cur = $(this);
				var elm_target = cur.closest('.date-section');
		
				elm_target = elm_target.add(elm_target.nextUntil('.start, :not(.date-section)'));
				var elm_target_range = elm_target.find('[name*=object_sub_date_from], [name*=object_sub_date_to]').closest('.date-section');
				var elm_target_point = elm_target.find('[name*=\"[object_sub_date_start][value]\"], [name*=\"[object_sub_date_end][value]\"]').closest('.date-section');
				var elm_target_chronology = elm_target.find('[name*=\"[object_sub_date_chronology]\"], [name*=object_sub_date_value], [id^=y\\\:data_entry\\\:select_chronology-]').closest('.date-section');
					
				if (this.value == 'range') {
					elm_target_range.removeClass('hide');
					elm_target_point.addClass('hide');
					elm_target_chronology.addClass('hide');
				} else if (this.value == 'point') {
					elm_target_range.addClass('hide');
					elm_target_point.removeClass('hide');
					elm_target_chronology.addClass('hide');
				} else if (this.value == 'chronology') {
					elm_target_range.addClass('hide');
					elm_target_point.addClass('hide');
					elm_target_chronology.removeClass('hide');
				}
			}).on('change update_date_type', '.entry [name*=\"[object_sub_date_type]\"]', function() { // Entry
			
				var cur = $(this);
				var elm_target = cur.closest('.date-section');
				
				if (elm_target.closest('ul.full').length) { // Multi data entry
					var elm_target_point = elm_target.find('[name*=\"[object_sub_date_start]\"], [name*=\"[object_sub_date_end]\"]').parent('span');
					var elm_target_reference = elm_target.find('[name*=\"[object_sub_date_type_id]\"], [name*=\"[object_sub_date_object_id]\"], [name*=\"[object_sub_date_object_sub_id]\"]').parent('span');
					var elm_target_chronology = elm_target.find('[name*=object_sub_date_chronology], [id^=y\\\:data_entry\\\:select_chronology-]').parent('span');
				} else { // Basic data entry
					elm_target = elm_target.add(elm_target.nextUntil('.start, :not(.date-section)'));	
					var elm_target_point = elm_target.find('[name*=\"[object_sub_date_start]\"], [name*=\"[object_sub_date_end]\"]').closest('.date-section');
					var elm_target_reference = elm_target.find('[name*=\"[object_sub_date_type_id]\"], [name*=\"[object_sub_date_object_id]\"], [name*=\"[object_sub_date_object_sub_id]\"]').closest('.date-section');
					var elm_target_chronology = elm_target.find('[name*=object_sub_date_chronology], [id^=y\\\:data_entry\\\:select_chronology-]').closest('.date-section');
				}
					
				if (this.value == 'chronology') {
					elm_target_chronology.removeClass('hide');
					elm_target_reference.addClass('hide');
					elm_target_point.addClass('hide');
				} else if (this.value == 'object_sub') {
					elm_target_chronology.addClass('hide');
					elm_target_reference.removeClass('hide');
					elm_target_point.addClass('hide');
				} else if (this.value == 'point') {
					elm_target_chronology.addClass('hide');
					elm_target_reference.addClass('hide');
					elm_target_point.removeClass('hide');
				}
			}).on('change update_date_type', '[name*=\"chronology[start][type]\"], [name*=\"chronology[end][type]\"]', function() {
			
				var cur = $(this);
				var elm_target = cur.closest('.date-section');
				
				elm_target = elm_target.add(elm_target.nextUntil('.start, :not(.date-section)'));
				var elm_target_point = elm_target.find('[name*=\"[date][start]\"], [name*=\"[date][end]\"]').closest('.date-section');
				var elm_target_statement = elm_target.find('[name*=\"[start][start]\"], [name*=\"[end][end]\"]').closest('.date-section');
				var elm_target_statement_between = elm_target_statement.add(elm_target.find('[name*=\"[start][end]\"], [name*=\"[end][start]\"]').closest('.date-section'));
				var elm_target_statement_inifite = elm_target_statement_between.find('[name*=\"[date_infinite]\"]');
					
				if (this.value == 'point') {
					elm_target_point.removeClass('hide');
					elm_target_statement_between.addClass('hide');
				} else if (this.value == 'statement') {
					elm_target_point.addClass('hide');
					elm_target_statement_between.addClass('hide');
					elm_target_statement.removeClass('hide');
					SCRIPTER.triggerEvent(elm_target_statement.find('[name$=\"[date_value_type]\"]'), 'update_date_value_type');
					elm_target_statement_inifite.addClass('hide');
				} else if (this.value == 'statement_between') {
					elm_target_point.addClass('hide');
					elm_target_statement_between.removeClass('hide');
					runElementsSelectorFunction(elm_target_statement_between, '[name$=\"[date_value_type]\"]', function(elm_found) {
						SCRIPTER.triggerEvent(elm_found, 'update_date_value_type');
					});
					elm_target_statement_inifite.removeClass('hide');
				}
			}).on('change update_date_type', '[name*=\"[start_infinite]\"], [name*=\"[end_infinite]\"]', function() {
				$(this).prevAll('input, select').prop('disabled', this.checked);
			}).on('change update_date_type', '[name*=\"[date_infinite]\"]', function() { // Date infinite in chronology
				$(this).closest('.date-section').find('input, select').not(this).prop('disabled', this.checked);
			}).on('change update_date_value_type', '[name$=\"[date_value_type]\"]', function() {
				
				var cur = $(this);
				var elm_target = cur.closest('ul');
				
				var elm_target_value = elm_target.find('[name$=\"[date_value]\"]').closest('li');
				var elm_target_object_sub = elm_target.find('[name$=\"[date_type_id]\"], [name$=\"[date_object_id]\"], [name$=\"[date_object_sub_id]\"]').closest('li');
				var elm_target_path = elm_target.find('[name*=\"[date_path]\"]').closest('li');
				
				if (this.value == 'path') {
					elm_target_value.addClass('hide');
					elm_target_object_sub.addClass('hide');
					elm_target_path.removeClass('hide');
				} else if (this.value == 'object_sub') {
					elm_target_value.addClass('hide');
					elm_target_object_sub.removeClass('hide');
					elm_target_path.addClass('hide');
				} else {
					elm_target_value.removeClass('hide');
					elm_target_object_sub.addClass('hide');
					elm_target_path.addClass('hide');
				}				
			}).on('change update_location_type', '[name*=object_sub_location_type]', function() {
			
				var cur = $(this);
				var elm_target = cur.closest('.location-section');
				
				if (elm_target.is('li')) { // Filter & basic data entry
					elm_target = elm_target.add(elm_target.nextUntil('.start, :not(.location-section)'));
					var elm_target_point = elm_target.find('[name*=object_sub_location_longitude], [name*=object_sub_location_latitude], [name*=\"[object_sub_location_value][radius]\"], [id=y\\\:data_filter\\\:select_geometry-0]').closest('.location-section');
					var elm_target_geometry = elm_target.find('[name*=object_sub_location_geometry], [name*=\"[object_sub_location_value][transcension]\"], *[type=button].select_geometry').closest('.location-section');
					var elm_target_reference = elm_target.find('[name*=object_sub_location_ref], span[data-type=reference]').closest('.location-section');
				} else { // Multi data entry
					var elm_target_point = elm_target.find('[name*=object_sub_location_longitude]').closest('span');
					var elm_target_geometry = elm_target.find('[name*=object_sub_location_geometry]').closest('span');
					var elm_target_reference = elm_target.find('[name*=object_sub_location_ref], [id^=y\\\:data_filter\\\:lookup_type_object-], span[data-type=reference]').closest('span');
				}
					
				if (this.value == 'point') {
					elm_target_point.removeClass('hide');
					elm_target_geometry.addClass('hide');
					elm_target_reference.addClass('hide');
				} else if (this.value == 'geometry') {
					elm_target_point.addClass('hide');
					elm_target_geometry.removeClass('hide');
					elm_target_reference.addClass('hide');
				} else if (this.value == 'reference') {
					elm_target_point.addClass('hide');
					elm_target_geometry.addClass('hide');
					elm_target_reference.removeClass('hide');
				}
			}).on('change update_type_object_pick', '[id^=y\\\:data_filter\\\:lookup_type_object_pick-]', function(e) {
			
				var elm_select_type_object_sub = func_find_select_type_object_sub(this);
				
				if (this.value) {
					
					if (elm_select_type_object_sub) {
						
						var elm_target = func_find_select_type(this);
						var object_sub_details_id = (elm_target.next().length ? elm_target.next()[0].value : false);
						
						COMMANDS.setData(this, {object_sub_details_id: object_sub_details_id});
						COMMANDS.quickCommand(this, function(data) {
						
							var data = (data ? data : []);
						
							func_update_select_type_object_sub(elm_select_type_object_sub, data.id, data.value);
						});
						
						return;
					} else {
						COMMANDS.quickCommand(this);
					}
				}
				
				if (elm_select_type_object_sub && SCRIPTER.isUserEvent(e)) {
					func_update_select_type_object_sub(elm_select_type_object_sub);
				}
			}).on('change', '[id=y\\\:data_filter\\\:selector_object_sub_details-0]', function(e) {
				
				var cur = $(this);
				var elm_target = cur.next('select');
				
				if (elm_target.length) {
				
					COMMANDS.quickCommand(this, elm_target);					
				}
				
				if (SCRIPTER.isUserEvent(e)) {
					func_clear_select_type_object(func_find_select_type_object(cur));
				}
			}).on('focus', '[id^=y\\\:data_filter\\\:lookup_type_object-]', function() {
				
				var cur = $(this);

				if (cur.closest('.entry, .select-object').length) {
					COMMANDS.setData(cur, {do_new: true});
				}

				if (cur.is('[id=y\\\:data_filter\\\:lookup_type_object-0]')) {
					
					var elm_target = func_find_select_type(cur);
					
					if (elm_target.length) {
						
						var type_id = elm_target[0].value;
						
						COMMANDS.setData(cur, {type_id: type_id});
						COMMANDS.setData(cur.prev('[id=y\\\:data_filter\\\:lookup_type_object_pick-0]'), {type_id: type_id});
					}
				}
			}).on('click', '[id^=y\\\:data_filter\\\:select_type_object_sub-]', function() {
			
				var cur = $(this);
				
				var elm_object_id = func_find_select_type_object(cur, true);
				
				var elm_target = func_find_select_type(elm_object_id);
				
				var type_id = elm_target[0].value;
				var object_sub_details_id = (elm_target.next().length ? elm_target.next()[0].value : false);
				var object_id = elm_object_id[0].value;
				var object_sub_id = cur.prev()[0].value;
				
				if (elm_target.length) {
				
					COMMANDS.setData(this, {type_id: type_id, object_id: object_id, object_sub_details_id: object_sub_details_id, object_sub_id: object_sub_id});
					COMMANDS.setTarget(this, function(data) {
					
						var data = (data ? data : []);
						
						func_update_select_type_object_sub(cur.prev(), data.id, data.value);
					});
					COMMANDS.popupCommand(this);
				}
			});
		
			// FILTER
								
			elm_scripter.on('ajaxloaded scripter', function(e) {
			
				if (!getElement(e.detail.elm)) {
					return;
				}
			
				e.detail.elm.find('input[name$=\"_now]\"]').trigger('update_input_now');
				e.detail.elm.find('select[name*=\"[equality]\"]').trigger('update_input_equality');
			}).on('click', '.filter .tags [id^=y\\\:data_filter\\\:add_filter_object_definition-], .filter .tags [id^=y\\\:data_filter\\\:add_filter_object_sub-], .filter .tags [id^=y\\\:data_filter\\\:add_filter_object_sub_definition-]', function() {
				var cur = $(this);
				var tag_identifier = cur.data('tag_identifier');
				var elm_filter_form_type = cur.closest('.tabs > div');
				var elm_filter_form = elm_filter_form_type.closest('.filter > div');
				var elm_filter = elm_filter_form.closest('.filter');
				var elm_target = cur.closest('.tags').next('.fieldsets');
				var target = elm_target.find('fieldset[data-tag_identifier='+tag_identifier+']');
				var filter_code = elm_filter_form.attr('id');
				var str_name = elm_filter.find('[data-name]').attr('data-name');
				if (!target.length) {
					COMMANDS.setData(cur, {filter_code: filter_code, name: str_name});
					cur.quickCommand(function(html) {
						elm_target.children().prepend(html);
						cur.parent('li').addClass('min');
					});
				} else {
					target.remove();
					cur.parent('li').removeClass('min');
				}
			}).on('command', '.filter [id^=y\\\:data_filter\\\:store_filter-]', function() {
				var cur = $(this);

				var elm_filter_form_all = cur.closest('.filtering');
				var str_filter = JSON.stringify(serializeArrayByName(elm_filter_form_all));
										
				COMMANDS.setData(cur, {filter: str_filter});
			}).on('command', '.filter [id^=y\\\:data_filter\\\:add_filter_extra-]', function() {
				var cur = $(this);
				var elm_parent = cur.closest('.filter');
				var elm_filter_form = elm_parent.children('div:visible');
				
				if (!$(this).closest('.tabs > ul').length) {
					var filter_code = elm_filter_form.attr('id');
					var filter_type_id = elm_filter_form.find('[name=\"filter[form]['+filter_code+'][type_id]\"]').val();
				} else {
					var filter_code = 0;
					var filter_type_id = 0;
				}
				var elm_filter_beacon = cur.next('[name$=\"[beacon]\"]');
				var filter_beacon = elm_filter_beacon.val();
				if (!filter_beacon && elm_filter_beacon.length) {
					filter_beacon = guid();
					elm_filter_beacon.val(filter_beacon);
				}
				var str_name = elm_parent.find('[data-name]').attr('data-name');
				var type_id = 0;
				
				if (cur.is('[id^=y\\\:data_filter\\\:add_filter_extra-0]')) {

					var target = cur.closest('li > fieldset').parent('li');
					if (!target.length) {
						target = cur.closest('li');
					}
					target = target.prev('li').find('.update_object_type');
					
					if (target.length) {
						var type_id = target.val();
					}
				}
										
				COMMANDS.setData(cur, {filter_code: filter_code, filter_type_id: filter_type_id, filter_beacon: filter_beacon, type_id: type_id, name: str_name});
				COMMANDS.setTarget(cur, function(data) {
				
					var elm_collect = $();
								
					for (var key in data.arr_filter_tabs) {
					
						var cur_filter = data.arr_filter_tabs[key];
						var elm_content = $(cur_filter.content);
						var elm_tab = cur_filter.link;
						
						elm_parent[0].navigationtabs.add({tab: elm_tab, content: elm_content});
						
						elm_collect = elm_collect.add(elm_content);
					}
					
					if (data.html_versioning) {
						
						var elm_content = $(data.html_versioning);
						elm_parent.next('.versioning').replaceWith(elm_content);
						elm_collect = elm_collect.add(elm_content);
					}
					
					return elm_collect;
				});
			}).on('click', '.filter > ul .del', function() {
				
				var elm_button = $(this);
				var elm_tabs = $(this).closest('.filter');
				
				elm_tabs[0].navigationtabs.del({id: elm_button.closest('li').children('a')[0].hash});
			}).on('click', '.filter > div .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', '.filter > div .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('click', '.filter > div .add[data-section]', function() {
				var cur = $(this);
				var elm_li = cur.closest('li');
				if (!cur[0].elm_source) {
					var target = elm_li.next('li');
						target = target.add(target.nextUntil('.start, :not(.'+cur.attr('data-section')+'-section)'));
					var clone = target.clone();
						clone.sorter('resetRow');
						clone.find('[name*=object_sub_'+cur.attr('data-section')+'_type]').removeAttr('data-update_'+cur.attr('data-section')+'_type');
					cur[0].elm_source = clone;
				}
				var elm = cur[0].elm_source.clone();
				replaceArrayInName(elm);
				elm.insertAfter(elm_li);
				SCRIPTER.triggerEvent(elm_scripter, 'ajaxloaded', {elm: elm});
			}).on('click', '.filter > div .del[data-section]', function() {
				var cur = $(this);
				var elm_ul = cur.closest('ul');
				
				var func_has_value = function() { return (this.value != '' && this.value != 0) };
				
				if (cur.attr('data-section') == 'date') {
				
					elm_ul.find('[name*=object_sub_date_type]').each(function(i) {
						var cur = $(this);
						var value = cur.val();
						var target = cur.closest('li');
						target = target.add(target.nextUntil('.start, :not(.date-section)'));

						if (elm_ul.find('[name*=object_sub_date_type]').length === 1) { // Keep last target
							return;
						}
						
						if (value == 'range') {
							if (!target.find('[name*=object_sub_date_from], [name*=object_sub_date_to]').filter(func_has_value).length) {
								target.remove();
							}
						} else if (value == 'point') {
							var elm_date = target.find('[name*=\"[object_sub_date_start][value]\"], [name*=\"[object_sub_date_end][value]\"]').filter(func_has_value);
							elm_date = elm_date.add(target.find('[name*=\"[object_sub_date_start][value_now]\"]:checked, [name*=\"[object_sub_date_end][value_now]\"]:checked'));
							if (!elm_date.length) {
								target.remove();
							}
						}
					});
				} else if (cur.attr('data-section') == 'location') {
				
					elm_ul.find('[name*=object_sub_location_type]').each(function(i) {
						
						var cur = $(this);
						var value = cur.val();
						var target = cur.closest('li');
						target = target.add(target.nextUntil('.start, :not(.location-section)'));

						if (elm_ul.find('[name*=object_sub_location_type]').length === 1) { // Keep last target
							return;
						}
							
						if (value == 'point') {
							if (!target.find('[name*=object_sub_location_longitude], [name*=object_sub_location_latitude]').filter(func_has_value).length) {
								target.remove();
							}
						} else if (value == 'geometry') {
							if (!target.find('[name*=object_sub_location_geometry]').filter(func_has_value).length) {
								target.remove();
							}
						} else if (value == 'reference') {
							if (!target.find('[id=y\\\:data_filter\\\:lookup_type_object_pick-0], [name*=\"[object_sub_location_reference][beacon]\"]').filter(func_has_value).length) { // Consider both references and beacons
								target.remove();
							}
						}
					});
				}
			}).on('click', '.filter > div *[type=button].extra', function() {
				var cur = $(this).closest('li');
				cur.nextUntil('li:not(.extra)').removeClass('hide');
				cur.remove();
			}).on('change update_input_now', '.filter input[name$=\"_now]\"]', function() {
				$(this).prev('input').prop('disabled', $(this).is(':checked'));
			}).on('change update_input_now', '.filter input[name$=\"_now]\"]', function() {
				$(this).prev('input').prop('disabled', $(this).is(':checked'));
			}).on('change update_input_equality', 'select[name*=\"[equality]\"]', function() {
				var cur = $(this);
				var value = cur.val();
				var target = cur.nextAll('input[name*=\"[range]\"], input[name*=\"[range_\"]');
				if (value == '><' || value == '≥≤') {
					target.show();
				} else {
					target.hide();
				} 
			});
		});
		
		SCRIPTER.dynamic('application_filter', function(elm_scripter) {

			elm_scripter.on('command', '[id^=y\\\:data_filter\\\:configure_application_filter-]', function() {
			
				var cur = $(this);
				var elm_target = cur.prev('input[type=hidden]');
				
				if (cur.is('[id$=configure_application_filter-0]')) {
				
					var type_id = cur.prevAll('select:last').val();
					COMMANDS.setID(cur, type_id);
				}
				
				COMMANDS.setData(cur, {filter: elm_target.val()});
				COMMANDS.setTarget(cur, function(data) {
					elm_target.val(data);
				});
			}).on('command', '[id^=y\\\:data_filter\\\:configure_application_path-]', function() {
			
				var cur = $(this);
				var elm_target = cur.prev('input[type=hidden]');
				
				if (cur.is('[id$=configure_application_path-0]')) {
				
					var type_id = cur.prevAll('select:last').val();
					COMMANDS.setID(cur, type_id);
				}
				
				COMMANDS.setData(cur, {path: elm_target.val(), options: cur.attr('data-options')});
				COMMANDS.setTarget(cur, function(data) {
					elm_target.val(data.path);
				});
			});
		});
		
		SCRIPTER.dynamic('.filter-storage.storage', function(elm_scripter) {
		
			elm_scripter.on('command', '[id^=x\\\:custom_projects\\\:filter_storage-] *[type=button]', function() {
				
				var cur = $(this);
				var elm_command = cur.parent();
				var elm_form = cur.closest('form');

				var elm_context = cur.closest('.overlay')[0].context;
				var str_filter = elm_context.data('value').filter;
				
				COMMANDS.setData(elm_command[0], {forms: [elm_form[0]], filter: str_filter});
				COMMANDS.setTarget(elm_command[0], cur.prevAll('select'));
				COMMANDS.setOptions(elm_command[0], {remove: false});
			});
		});
		
		SCRIPTER.dynamic('select_chronology', function(elm_scripter) {
		
			elm_scripter.on('command', '[id^=y\\\:data_entry\\\:select_chronology-]', function() {
				var cur = $(this);
				var elm_target = cur.prev('textarea');
				if (!elm_target.length) {
					elm_target = cur.closest('.date-section').prev().find('textarea');
				}
				COMMANDS.setData(this, {json: elm_target[0].value});
				COMMANDS.setTarget(this, elm_target);
			});
		});
		
		SCRIPTER.dynamic('filtering', 'select_chronology');

		SCRIPTER.dynamic('select_geometry', function(elm_scripter) {
		
			elm_scripter.on('click', '*[type=button].select_geometry', function() {
				
				LOCATION.open('http://geojson.io');
			}).on('click', '[id=y\\\:data_filter\\\:select_geometry-0]', function() {
			
				var cur = $(this);
			
				$(this).quickCommand(function(data) {
				
					var elm_map = $(data.html);
					var elm_con = cur.closest('.mod, body');
					
					new Overlay(elm_con, elm_map, {
						sizing: 'full-width'
					});
					
					var arr_levels = [];
					for (var i = data.frame.zoom.min; i <= data.frame.zoom.max; i++) {
						arr_levels.push({level: i, width: 256 * Math.pow(2,i), height: 256 * Math.pow(2,i), tile_width: 256, tile_height: 256});
					}
					var num_default_zoom = Math.floor((data.frame.zoom.max - data.frame.zoom.min) * 0.2);
					
					if (cur.closest('div.location-section').length) {
						var elm_latitude = cur.siblings('[name*=location_latitude]');
						var elm_longitude = cur.siblings('[name*=location_longitude]');
					} else {
						var elm_latitude = cur.closest('li').prev('li').prev('li').find('[name*=latitude]');
						var elm_longitude = cur.closest('li').prev('li').find('[name*=longitude]');
					}
					
					var arr_data = {points: []};
					var arr_latlong = {latitude: parseFloat(elm_latitude.val()), longitude: parseFloat(elm_longitude.val())};
					if (arr_latlong.latitude) {
						arr_data.points.push(arr_latlong);
					}
					
					var obj_options = {
						call_class_paint: MapDrawPoints,
						arr_levels: arr_levels,
						arr_class_paint_settings: {arr_visual: data.visual},
						arr_data: arr_data,
						tile_path: data.visual.settings.map_url,
						tile_subdomain_range: [1,2,3],
						allow_sizing: true,
						default_zoom: num_default_zoom
					};
					
					if (arr_latlong.latitude) {
						obj_options.default_center = arr_latlong;
						obj_options.default_zoom = false;
					} else if (data.frame.coordinates.latitude) {
						obj_options.default_center = data.frame.coordinates;
					}
					
					if (data.frame.zoom.scale) {
						obj_options.default_zoom = {scale: data.frame.zoom.scale};
					}
					
					var obj_labmap = new MapManager(elm_map);
					obj_labmap.init(obj_options);
					
					elm_map.on('click', function(e) {
						
						var arr_latlong_mouse = obj_labmap.getMousePosition();
						obj_labmap.prepareData({
							points: [arr_latlong_mouse]
						});
						elm_latitude.val(arr_latlong_mouse.latitude);
						elm_longitude.val(arr_latlong_mouse.longitude);
					});
					
					return elm_map;
				});
			});
		});
		
		SCRIPTER.dynamic('filtering', 'select_geometry');
		
		SCRIPTER.dynamic('[data-method=filter], .filtering, [data-method=return_application_filter]', 'filtering');
		
		SCRIPTER.dynamic('[data-method=return_application_path]', function(elm_scripter) {
			
			var elm_filtering = elm_scripter.find('.filtering');
			SCRIPTER.runDynamic(elm_filtering);
			
			var elm_scope = elm_scripter.find('.network.type');
			SCRIPTER.runDynamic(elm_scope);
		});
		
		// FILTER EXTERNAL
		
		SCRIPTER.dynamic('[id^=f\\\:data_entry\\\:]', function(elm_scripter) {
			
			elm_scripter.on('change', '[id^=y\\\:data_filter\\\:lookup_external_pick-]', function() {
				var cur = $(this);
				if (cur.val()) {
					cur.quickCommand();
				}
			});
		});
		SCRIPTER.dynamic('[data-method=return_external]', function(elm_scripter) {
			
			elm_scripter.on('change', '[id=y\\\:data_filter\\\:select_external-0]', function() {
				var cur = $(this);
				var elm_target = cur.closest('form');
				if (cur.val()) {
					cur.quickCommand(function(html) {
						elm_target.html(html);
					});
				}
			});
		});
		SCRIPTER.dynamic('[data-method=filter_external]', function(elm_scripter) {
		
			elm_scripter.on('click', '.filter.external .tags [id^=y\\\:data_filter\\\:add_filter_external_variable-]', function() {
				var cur = $(this);
				var tag_identifier = cur.data('tag_identifier');
				var elm_target = cur.closest('.tags').next('.fieldsets');
				var target = elm_target.find('fieldset[data-tag_identifier='+tag_identifier+']');
				if (!target.length) {
					cur.quickCommand(function(html) {
						elm_target.children().prepend(html);
						cur.parent('li').addClass('min');
					});
				} else {
					target.remove();
					cur.parent('li').removeClass('min');
				}
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == "open_filter") {
			
			$arr_type_filter = [];
			
			if (is_numeric($value)) { // Project filter id
				$value = ['filter_id' => $value];
			}
			if (is_array($value) && $value['filter_id']) {
				$arr_type_filter = self::getFilterSet($value['filter_id']);
			} else if ($value) {
				$arr_type_filter = $value;
			}
			
			$return = '<form data-method="filter">'.$this->createFilter($id, $arr_type_filter)
				.'<input type="submit" name="discard" value="'.getLabel('lbl_remove').' '.getLabel('lbl_filter').'" />'
				.'<input type="submit" value="'.getLabel('lbl_apply').' '.getLabel('lbl_filter').'" />
			</form>';
			
			$this->html = $return;
		}
		if ($method == "filter" && $_POST['discard']) {

			$this->html = ['active' => false, 'filter' => ['form' => [], 'versioning' => [], 'filter_id' => 0]];
			return;
		}
		if ($method == "filter") {
						
			$filter = new FilterTypeObjects($id);
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id'])]);
			$arr_type_filter = $filter->cleanupFilterInput(($_POST['filter'] ?: []));
			
			$is_active = ($arr_type_filter ? true : false);
			
			$arr_type_filter = [
				'form' => ($arr_type_filter['form'] ?: []),
				'versioning' => ($arr_type_filter['versioning'] ?: []),
				'filter_id' => 0 // Always reset a possible set filter_id
			];
								
			$this->html = ['active' => $is_active, 'filter' => $arr_type_filter];
		}
		
		if ($method == "store_filter") {
			
			$arr_id = explode('_', $id);
			$type_id = $arr_id[0];
			
			$arr_filter = json_decode($value['filter'], true);
			$arr_filter = $arr_filter['filter'];
			
			$this->html = '<form class="filter-storage storage">
				'.$this->createSelectFilterExtra($type_id, $arr_filter).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "add_filter_extra") {
			
			$arr_id = explode('_', $id);
			$type_id = ($value['type_id'] ?: $arr_id[0]);
			$is_deep_filter = ($arr_id[2] || $arr_id[3] || $arr_id[4]);

			$this->html = '<form class="filter-storage" data-method="return_filter_extra">
				'.$this->createSelectFilterExtra($type_id).'
				<input class="hide" type="submit" name="" value="" />
				<input data-tab="storage" type="submit" name="select" value="'.getLabel('lbl_select').'" />
				<input data-tab="advanced" type="submit" name="apply" value="'.getLabel('lbl_apply').'" />
			</form>';
		}
		
		if ($method == "return_filter_extra") {
			
			$arr_id = explode('_', $id);
			$type_id = ($value['type_id'] ?: $arr_id[0]);
			$arr_source = ['filter_code' => $value['filter_code'], 'filter_type_id' => $value['filter_type_id'], 'filter_beacon' => $value['filter_beacon'], 'type_id' => $arr_id[1], 'object_description_id' => $arr_id[2], 'object_sub_details_id' => $arr_id[3], 'object_sub_description_id' => $arr_id[4], 'direction' => ($arr_id[5] != '' ? ($arr_id[5] ? 'in' : 'out') : false)];
			if ($value['name']) {
				$this->form_name = $value['name'];
			}
			$arr_filter_tabs = [];
			$html_versioning = '';
			
			$arr_type_filter = [];
			
			if ($_POST['select']) {
				
				if ($_POST['filter_id']) {
					
					$arr_type_filter = self::getFilterSet($_POST['filter_id']);
				}
			} else {
				
				$arr_type_filter = json_decode($_POST['plain'], true);
			}
			
			if ($arr_type_filter) {
				
				if (!$arr_type_filter['form']) {
					
					if ($arr_type_filter['versioning']) {
						$arr_type_filter['form'] = [];
					} else {
						$arr_type_filter['form'] = $arr_type_filter;
					}
				}
								
				$filter = new FilterTypeObjects($type_id);
				$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id'])]);
				$arr_type_filter = $filter->cleanupFilterInput($arr_type_filter);
				
				$arr_filter_tabs = $this->getFilterTypeObjectTabs($arr_type_filter['form'], $arr_source);
				
				if ($arr_type_filter['versioning'] && !$arr_source['filter_code']) {
					$html_versioning = $this->createFilterVersioning($type_id, $arr_type_filter['versioning']);
				}
			} else {
				
				$arr_filter_tabs[] = $this->createFilterTypeObjectTab($type_id, $arr_source);
			}
			
			$this->html = ['arr_filter_tabs' => $arr_filter_tabs, 'html_versioning' => $html_versioning];
		}
		
		if ($method == "add_filter_object_definition") {
		
			$arr_id = explode('_', $id);
			
			if ($value['name']) {
				$this->form_name = $value['name'];
			}
			
			$this->html = '<fieldset data-tag_identifier="object_description_id-'.(int)$arr_id[1].'">
				<ul>'.$this->createFilterTypeObjectDefinition((int)$arr_id[0], (int)$arr_id[1], $value['filter_code'], [], (int)$arr_id[2]).'</ul>
			</fieldset>';
		}
		
		if ($method == "add_filter_object_sub") {
		
			$arr_id = explode('_', $id);
			
			if ($value['name']) {
				$this->form_name = $value['name'];
			}
		
			if ($arr_id[1]) {
				
				if ($arr_id[2]) {
					
					$arr_referenced = FilterTypeObjects::getTypesReferenced((int)$arr_id[2], (int)$arr_id[0], ['object_sub_locations' => true, 'dynamic_is_used' => true]);
					
					$this->html = $this->createFilterTypeObjectSub((int)$arr_id[0], (int)$arr_id[1], $value['filter_code'], [], $arr_referenced['object_sub_details'][$arr_id[1]], (int)$arr_id[2]);
				} else {
					
					$this->html = $this->createFilterTypeObjectSub((int)$arr_id[0], (int)$arr_id[1], $value['filter_code']);
				}
			} else {
				$this->html = $this->createFilterTypeObjectSubGeneral((int)$arr_id[0], $value['filter_code']);
			}
		}
		
		if ($method == "add_filter_object_sub_definition") {
		
			$arr_id = explode('_', $id);
			
			if ($value['name']) {
				$this->form_name = $value['name'];
			}
			
			$this->html = '<fieldset data-tag_identifier="object_sub_description_id-'.(int)$arr_id[2].'">
				<ul>'.$this->createFilterTypeObjectSubDefinition((int)$arr_id[0], (int)$arr_id[1], (int)$arr_id[2], $value['filter_code'], [], (int)$arr_id[3]).'</ul>
			</fieldset>';
		}
		
		if ($method == "open_filter_external") {
			
			$arr_filter = ($value['form'] ?: []);
			
			$return = '<form data-method="filter_external">'.$this->createFilterExternal($id, $arr_filter)
				.'<input type="submit" name="discard" value="'.getLabel('lbl_remove').' '.getLabel('lbl_filter').'" />'
				.'<input type="submit" value="'.getLabel('lbl_apply').' '.getLabel('lbl_filter').'" />
			</form>';
			
			$this->html = $return;
		}
		if ($method == "filter_external" && $_POST['discard']) {

			$this->html = ['active' => false, 'filter' => ['form' => []]];
			return;
		}
		if ($method == "filter_external") {
						
			$arr_resource = StoreResourceExternal::getResources($id);
			$external = new ResourceExternal($arr_resource);

			$arr_filter = $external->cleanupFilterForm(($_POST['filter_external'] ?: []));
			
			$is_active = ($arr_filter ? true : false);
								
			$this->html = ['active' => $is_active, 'filter' => ['form' => $arr_filter]];
		}
		
		if ($method == "add_filter_external_variable") {
		
			$arr_id = explode('_', $id);
						
			$this->html = '<fieldset data-tag_identifier="'.strEscapeHTML($arr_id[1]).'_'.strEscapeHTML($arr_id[2]).'">
				<ul>'.$this->createFilterExternalVariable($arr_id[0], $arr_id[1], $arr_id[2]).'</ul>
			</fieldset>';
		}
		
		if ($method == "configure_application_filter") {
		
			$arr_id = explode('_', $id);
			$arr_type_filter = ($value['filter'] ? json_decode($value['filter'], true) : []);
						
			$this->html = '<form data-method="return_application_filter">'.$this->createFilter($id, $arr_type_filter).'</form>';
		}
		if ($method == "return_application_filter") {
						
			$filter = new FilterTypeObjects($id);
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id'])]);
			$arr_type_filter = $filter->cleanupFilterInput(($_POST['filter'] ?: []));
						
			$return = value2JSON($arr_type_filter);
			
			$this->html = $return;
		}
		
		if ($method == "configure_application_path") {
			
			$arr_id = explode('_', $id);
			$type_id = $arr_id[0];
			
			$arr_path = ($value['path'] ? json_decode($value['path'], true) : []);
			$arr_type_filter = ($arr_path['filter'] ?: []);
			$arr_type_scope = ($arr_path['scope'] ?: []);
			
			$arr_options = ($value['options'] ? json_decode($value['options'], true) : []);
			
			$html_filter = $this->createFilter($type_id, $arr_type_filter);
			
			$html_type_network = data_model::createTypeNetwork($type_id, false, false, ['references' => 'both', 'network' => ['dynamic' => true, 'object_sub_locations' => true], 'name' => 'scope', 'value' => $arr_type_scope, 'descriptions' => $arr_options['descriptions'], 'functions' => ['filter' => true, 'collapse' => false]]);
			
			$return = '<div class="tabs">
				<ul>
					<li><a href="#">'.getLabel('lbl_filter').'</a></li>
					<li><a href="#">'.getLabel('lbl_scope').'</a></li>
				</ul>
				<div>'.$html_filter.'</div>
				<div>
					<div class="options">'.$html_type_network.'</div>
				</div>
			</div>';
			
			$this->html = '<form data-method="return_application_path">'.$return.'</form>';
		}
		if ($method == "return_application_path") {
						
			$filter = new FilterTypeObjects($id);
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id'])]);
			$arr_type_filter = $filter->cleanupFilterInput(($_POST['filter'] ?: []));
			
			$arr_type_scope = data_model::parseTypeNetwork(($_POST['scope'] ?: []));
			
			if (!$arr_type_scope['paths'] && !$arr_type_scope['types']) {
				$arr_type_scope = [];
			}
			
			$arr_path = ['filter' => $arr_type_filter, 'scope' => $arr_type_scope];
			
			$this->html = ['path' => value2JSON($arr_path)];
		}
		
		if ($method == "lookup_type_object") {
			
			if (is_array($value)) {
				$value_search = trim($value['value_element']);
			} else {
				$value_search = trim($value);
				$value = [];
			}
			
			$type_id = ($value['type_id'] ?: $id);
			if (!$type_id) {
				return;
			}
			
			if (!custom_projects::checkAccessType('view', $type_id)) {
				return;
			}
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_OVERVIEW, true);
			$filter->setLimit(20);
			$filter->setOrder(['object_name' => 'asc']);
			$filter->setVersioning('added');
			$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE, toolbar::getTypeConditions($type_id));
						
			if ($arr_project['types'][$type_id]['type_filter_id']) {
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_UNDER_REVIEW) {
					
					$arr_ref_type_ids = StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id']);
					$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => $arr_ref_type_ids]);
					
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
					
					$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
					$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
				}
			}
			
			if ($value_search) {
				
				$filter->setFilter($value_search);
				$arr_objects = $filter->init();
			} else {
				
				$arr_results = $filter->getResultInfo();
				
				if ($arr_results['total'] <= 30) {
					
					$filter->setLimit(30);
					$arr_objects = $filter->init();
				} else if ($_SESSION['data_filter']['lookup'][$type_id]) {
					
					$filter->setFilter(['objects' => $_SESSION['data_filter']['lookup'][$type_id]]);
					$arr_objects = $filter->init();
					
					$arr_objects_sort = [];
					foreach (array_reverse($_SESSION['data_filter']['lookup'][$type_id]) as $object_id) {
						
						if ($arr_objects[$object_id]) {
							$arr_objects_sort[] = $arr_objects[$object_id];
						}
					}
					
					$arr_objects = $arr_objects_sort;
				}
			}

			$arr = [];
			
			if ($value_search && !$arr_objects) {
				
				$arr[] = ['id' => '', 'label' => getLabel('msg_no_results'), 'value' => ''];
			} else if (!$arr_objects) {
				
				Labels::setVariable('what', ($arr_type_set['type']['name'] ?: getLabel('lbl_type_unknown')));
				$arr[] = ['id' => '', 'label' => getLabel('msg_search_by_typing'), 'value' => ''];
			}
			
			foreach ((array)$arr_objects as $arr_object) {
				
				$arr_title = [];
				
				foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
					
					$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
					
					if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, $object_description_id) || $arr_object_definition['object_definition_style'] == 'hide') {
						continue;
					}
					
					if ($arr_object_description['object_description_ref_type_id']) {
						
						if ($arr_object_description['object_description_is_dynamic']) {
							
							$arr_values = [];
							
							foreach ($arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
							
								foreach($arr_ref_objects as $cur_object_id => $arr_reference) {
									
									$arr_values[] = $arr_reference['object_definition_ref_object_name'];
								}
							}
							
							$title = implode(', ', $arr_values);
						} else if ($arr_object_description['object_description_has_multi']) {
							$title = implode(', ', $arr_object_definition['object_definition_value']);
						} else {
							$title = $arr_object_definition['object_definition_value'];
						}
					} else {
						
						$str_value = arrParseRecursive($arr_object_definition['object_definition_value'], ['Labels', 'parseLanguage']);
						
						$title = StoreTypeObjects::formatToHTMLPreviewValue($arr_object_description['object_description_value_type'], $str_value, $arr_object_description['object_description_value_type_settings']);
					}
					
					$title = ($title ?: '-');
					
					$arr_title[] = '<li><dt>'.strEscapeHTML(Labels::parseTextVariables($arr_object_description['object_description_name'])).'</dt><dd class="limit">'.($arr_object_definition['object_definition_style'] ? '<span style="'.$arr_object_definition['object_definition_style'].'">'.$title.'</span>' : $title).'</dd></li>';
				}
				
				$str_object_name = $arr_object['object']['object_name'];
				
				$arr[] = ['id' => $arr_object['object']['object_id'], 'label' => $str_object_name, 'value' => Response::addParsePost($str_object_name, ['strip' => true]), 'title' => ($arr_title ? '<dl>'.implode('', $arr_title).'</dl>' : '')];
			}
		
			if ($value['do_new'] && $_SESSION['NODEGOAT_CLEARANCE'] > NODEGOAT_CLEARANCE_INTERACT && data_entry::checkClearanceType($type_id, false) && custom_projects::checkAccessType('edit', $type_id, false)) {
				$arr[] = ['id' => '', 'label' => '<input type="button" id="y:data_entry:add_quick-'.$type_id.'" class="data add popup" value="new" />', 'value' => ''];
			}
			$arr[] = ['id' => '', 'label' => '<input type="button" id="y:data_filter:select_type_object-'.$type_id.'" class="data neutral popup" value="filter" />', 'value' => ''];
		
			$this->html = $arr;
		}
		
		if ($method == "lookup_external") {
			
			if (is_array($value)) {
				$value_search = trim($value['value_element']);
			} else {
				$value_search = trim($value);
				$value = [];
			}
			
			$resource_id = ($value['resource_id'] ?: $id);
			if (!$resource_id) {
				return;
			}
			
			$arr_resource = StoreResourceExternal::getResources($resource_id);
			$external = new ResourceExternal($arr_resource);
			
			if ($value_search) {
				
				$external->setLimit(20);
				$external->setFilter(['name' => $value_search]);
				$external->request();

				try {
					
					$arr_results = $external->getResultValues();
				} catch (RealTroubleThrown $e) {

					$arr_code = Trouble::parseCode($e);
					
					if ($arr_code['suppress'] != LOG_SYSTEM) {
						
						Labels::setVariable('name', $arr_resource['name']);

						error(getLabel('msg_external_resource_error_parse').' '.$e->getMessage(), TROUBLE_ERROR, LOG_CLIENT, false, $e); // Make notice
					}
					
					throw($e);
				}
			} else if ($_SESSION['data_filter']['lookup_external'][$resource_id]) {
				
				foreach (array_reverse($_SESSION['data_filter']['lookup_external'][$resource_id]) as $key => $value) {
					$arr_results[] = ['uri' => $value, 'label' => $value];
				}
			}

			$arr = [];
			
			if (!$arr_results) {
				
				if ($value_search) {
					
					$arr[] = ['id' => '', 'label' => getLabel('msg_no_results'), 'value' => ''];
				} else {
					
					Labels::setVariable('what', $arr_resource['name']);
					$arr[] = ['id' => '', 'label' => getLabel('msg_search_by_typing'), 'value' => ''];
				}
			} else {
			
				foreach ($arr_results as $arr_result) {
					
					$label = ($arr_result['label'] && $arr_result['label'] != $arr_result['uri'] ? $arr_result['label'].' ('.$arr_result['uri'].')' : $arr_result['uri']);
					$arr[] = ['id' => $arr_result['uri'], 'label' => (FormatHTML::test($label) ? $label : strEscapeHTML($label)), 'value' => $arr_result['uri']];
				}
			}
			
			if ($value_search) {
				$arr[] = ['id' => $value_search, 'label' => getLabel('msg_search_use_input').' <em>'.(FormatHTML::test($value_search) ? $value_search : strEscapeHTML($value_search)).'</em>', 'value' => $value_search];
			}
		
			$arr[] = ['id' => '', 'label' => '<input type="button" id="y:data_filter:select_external-'.$resource_id.'" class="data neutral popup" value="filter" />', 'value' => ''];
		
			$this->html = $arr;
		}
		
		if ($method == "select_type_object") {
			
			if (!custom_projects::checkAccessType('view', $id)) {
				return;
			}
			
			$this->html = '<form data-method="return_type_object">'.data_view::createViewTypeObjects($id, ['select' => true, 'filter' => true]).'<input type="submit" value="'.getLabel('lbl_select').'" /></form>';
		}
		
		if ($method == "select_type_object_sub") {
			
			if (!$value || !$value['type_id']) {
				return;
			}
			
			if (!custom_projects::checkAccessType('view', $value['type_id'])) {
				return;
			}
			
			if (!$value['object_sub_details_id'] || !$value['object_id']) {
				error(getLabel('msg_missing_information'), TROUBLE_ERROR, LOG_CLIENT);
			}
			
			$this->html = '<form data-method="return_type_object_sub">'.data_view::createViewTypeObjectSubs($value['type_id'], (int)$value['object_id'], (int)$value['object_sub_details_id'], ['select' => true, 'filter' => true]).'<input type="submit" value="'.getLabel('lbl_select').'" /></form>';
		}
		
		if ($method == "select_external") {
			
			$resource_id = (int)($id ?: $value);
			$arr_external_resources = StoreResourceExternal::getResources();
			foreach ($arr_external_resources as $cur_resource_id => $arr_external_resource) {
				if ($arr_external_resource['protocol'] == 'static') {
					unset($arr_external_resources[$cur_resource_id]);
				}
			}
			
			$return = '<div class="options">
				<fieldset>
					<ul>
						<li>
							<label>'.getLabel('lbl_linked_data').' '.getLabel('lbl_resource').'</label>
							<select name="resource_id" id="y:data_filter:select_external-0">'.cms_general::createDropdown($arr_external_resources, $resource_id).'</select>
						</li>
					</ul>
				</fieldset>
			</div>
			'.data_view::createViewExternal($resource_id, ['select' => true, 'filter' => true]);
			
			if ($id) {				
				$this->html = '<form data-method="return_external">
					'.$return.'
					<input type="submit" value="'.getLabel('lbl_select').'" />
				</form>';
			} else {
				$this->html = $return;
			}
		}
		
		if ($method == "return_type_object") {
		
			$object_id = (int)$_POST['type_object_id'];
			
			if (!$object_id) {
				$this->html = [];
				return;
			}
			
			$arr_objects = GenerateTypeObjects::getTypeObjectNames($id, $object_id, GenerateTypeObjects::CONDITIONS_MODE_TEXT);
			$str_object_name = $arr_objects[$object_id];
			
			$this->html = ['id' => $object_id, 'value' => $str_object_name];
		}
		
		if ($method == "return_type_object_sub") {
			
			$type_id = (int)$value['type_id'];
			$object_id = (int)$value['object_id'];
			$object_sub_details_id = (int)$value['object_sub_details_id'];
			$object_sub_id = (int)$_POST['type_object_sub_id'];
			
			if (!$object_sub_id || !$object_id || !$type_id) {
				return;
			}

			$arr_object_subs_names = GenerateTypeObjects::getTypeObjectSubsNames($type_id, $object_id, $object_sub_id, false, GenerateTypeObjects::CONDITIONS_MODE_TEXT);

			if (!$arr_object_subs_names) {
				return;
			}
			
			$str_object_sub_name = $arr_object_subs_names[$object_sub_id];
			
			$this->html = ['id' => $object_sub_id, 'value' => $str_object_sub_name];
		}
		
		if ($method == "return_external") {
		
			$uri = $_POST['uri'];
			if (!$uri) {
				$this->html = [];
				return;
			}
			
			$this->html = ['id' => $uri, 'label' => $uri, 'value' => $uri];
		}
				
		if ($method == "lookup_type_object_pick") {
			
			$type_id = (int)$id;
			$object_id = (int)$value;
			$object_sub_details_id = null;
			
			if (is_array($value)) {
				$type_id = ((int)$value['type_id'] ?: $type_id);
				$object_id = (int)$value['value_element'];
				$object_sub_details_id = (int)$value['object_sub_details_id'];
			}

			if (!$type_id || !$object_id) {
				return;
			}
			
			if (!$_SESSION['data_filter']['lookup'][$type_id]) {
				$_SESSION['data_filter']['lookup'][$type_id] = [];
			}
			
			unset($_SESSION['data_filter']['lookup'][$type_id][$object_id]);
			$_SESSION['data_filter']['lookup'][$type_id][$object_id] = $object_id;
			
			if (count($_SESSION['data_filter']['lookup'][$type_id]) > 20) {
				$_SESSION['data_filter']['lookup'][$type_id] = array_slice($_SESSION['data_filter']['lookup'][$type_id], 1, 20, true);
			}
			
			if ($object_sub_details_id) {
								
				if (!custom_projects::checkAccessType('view', $type_id)) {
					return;
				}
				
				$arr_object_sub_details = StoreType::getTypeObjectSubsDetails($type_id, $object_sub_details_id);
								
				if ($arr_object_sub_details[$object_sub_details_id]['object_sub_details_is_single']) {
				
					$arr_object_subs_names = GenerateTypeObjects::getTypeObjectSubsNames($type_id, $object_id, [], $object_sub_details_id, GenerateTypeObjects::CONDITIONS_MODE_TEXT);

					if (!$arr_object_subs_names) {
						return;
					}
					
					$object_sub_id = key($arr_object_subs_names);
					$str_object_sub_name = current($arr_object_subs_names);
					
					$this->html = ['id' => $object_sub_id, 'value' => $str_object_sub_name];
				}
			}
		}
		
		if ($method == "lookup_external_pick") {
			
			$resource_id = (is_array($value) ? $value['resource_id'] : $id);
			$identifier = (is_array($value) ? $value['value_element'] : $value);
			if (!$resource_id || !$identifier) {
				return;
			}
			
			if (!$_SESSION['data_filter']['lookup_external'][$resource_id]) {
				$_SESSION['data_filter']['lookup_external'][$resource_id] = [];
			}
			
			unset($_SESSION['data_filter']['lookup_external'][$resource_id][$identifier]);
			$_SESSION['data_filter']['lookup_external'][$resource_id][$identifier] = $identifier;
			
			if (count($_SESSION['data_filter']['lookup_external'][$resource_id]) > 20) {
				$_SESSION['data_filter']['lookup_external'][$resource_id] = array_slice($_SESSION['data_filter']['lookup_external'][$resource_id], 1, 20, true);
			}
		}
		
		if ($method == "selector_object_sub_details") {
			
			$this->html = Labels::parseTextVariables(cms_general::createDropdown(StoreType::getTypeObjectSubsDetails($value), false, false, 'object_sub_details_name', 'object_sub_details_id'));
		}
		
		if ($method == "select_geometry") {
			
			$type_id = toolbar::getFilterTypeId();
			
			$arr_visualisation_settings = data_visualise::getVisualSettings();
			$arr_type_frame = data_visualise::getTypeFrame($type_id);
			
			$arr_frame = ['coordinates' => [], 'zoom' => []];
			
			if ($arr_type_frame['area']['geo']['latitude'] || $arr_type_frame['area']['geo']['longitude']) {
				$arr_frame['coordinates']['latitude'] = $arr_type_frame['area']['geo']['latitude'];
				$arr_frame['coordinates']['longitude'] = $arr_type_frame['area']['geo']['longitude'];
			}
			if ($arr_type_frame['area']['geo']['zoom']['scale']) {
				$arr_frame['zoom']['scale'] = $arr_type_frame['area']['geo']['zoom']['scale'];
			}
			$arr_frame['zoom']['min'] = ($arr_type_frame['area']['geo']['zoom']['min'] ?: 2);
			$arr_frame['zoom']['max'] = ($arr_type_frame['area']['geo']['zoom']['max'] ?: 18);	
						
			$this->html = [
				'html' => '<div class="point labmap">
					<div class="map"></div>
				</div>',
				'visual' => $arr_visualisation_settings,
				'frame' => $arr_frame
			];
		}
		
		// QUERY
		
	}
	
	public static function parseUserFilterInput($value) {
		
		$arr_filter = [];
		
		$arr_value = ($value ?: []);
		if ($arr_value && !is_array($arr_value)) {
			$arr_value = json_decode($arr_value, true);
		}
		
		if (is_numeric($value)) { // Project filter id
			$arr_value = ['filter_id' => $value];
		}
		
		$arr_filter_set = false;
		
		if ($arr_value['filter_id']) {
			
			$arr_filter_set = self::getFilterSet($arr_value['filter_id']);
		} else if ($arr_value) {
			
			$arr_filter_set = $arr_value;
			
			unset($arr_filter_set['dynamic_filtering']);
		}
		
		if ($arr_filter_set) {

			$arr_filter = $arr_filter_set;
			$arr_filter += FilterTypeObjects::convertFilterInput($arr_filter_set);
			
			$arr_filter = array_filter($arr_filter);
		}
		
		return $arr_filter;
	}
	
	public static function getFilterSet($filter_id) {
				
		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_filter_set = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $filter_id, (($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN || toolbar::getActionSpace() !== 0) ? true : false), $arr_use_project_ids);
		$arr_filter_set = $arr_filter_set['object'];
		
		return $arr_filter_set;
	}
}
