<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_entry extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Entry';
		static::$parent_label = 'nodegoat';
	}

	protected $arr_access = [
		'data_view' => [],
		'custom_projects' => [],
		'data_model' => [],
		'data_filter' => []
	];
	
	public $form_name = '';
		
	public function contents() {
		
		if (!$_SESSION['custom_projects']['project_id']) {
			
			return '<section class="info attention">
				'.getLabel('msg_no_custom_projects').'
			</section>';
		}
		
		SiteEndVars::setFeedback('project', true);
		
		// Reset filter
		toolbar::checkFilter();

		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_types = ($arr_project['types'] ? StoreType::getTypes(array_keys($arr_project['types'])) : []);
		
		if (!$arr_types) {

			return '<section class="info attention">
				'.getLabel('msg_no_types_custom_project').'
			</section>';
		}
		
		if ($this->arr_query['filter']) {
			
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $this->arr_query['filter'][0], ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
		}
		$type_id = (int)($arr_project_filters['type_id'] ?: ($this->arr_query['type'][0] ?: ($arr_project['types'] ? key($arr_project['types']) : 0)));
		
		if ($type_id) {
			if (!custom_projects::checkAccesType($type_id)) {
				$type_id = ($arr_project['types'] ? key($arr_project['types']) : 0);
			}
		}
		
		$arr_types_classifications = ['types' => [], 'classifications' => [], 'reversals' => []];
		foreach ((array)$arr_types as $cur_type_id => $value) {
			$arr_types_classifications[(($value['is_reversal'] ? 'reversals' : ($value['is_classification'] ? 'classifications' : 'types')))][$cur_type_id] = '<input type="button" data-type_id="'.$value['id'].'" value="'.htmlspecialchars(Labels::parseTextVariables($value['name'])).'"'.($value['id'] == $type_id ? ' class="selected"' : '').' />';
		}
		
		$return .= '<div class="tabs" id="y:data_entry:view-0">
			<ul>
				'.($arr_types_classifications['types'] ? '<li><a href="#">'.getLabel('lbl_types').'</a></li>' : '').'
				'.($arr_types_classifications['classifications'] ? '<li><a href="#"'.($arr_types_classifications['classifications'][$type_id] ? ' class="open"' : '').'>'.getLabel('lbl_classifications').'</a></li>' : '').'
				'.($arr_types_classifications['reversals'] ? '<li><a href="#"'.($arr_types_classifications['reversals'][$type_id] ? ' class="open"' : '').'>'.getLabel('lbl_reversals').'</a></li>' : '').'
			</ul>
			'.($arr_types_classifications['types'] ? '<div>
				<menu class="select options"><div>'.implode('', $arr_types_classifications['types']).'</div></menu>
			</div>' : '').'
			'.($arr_types_classifications['classifications'] ? '<div>
				<menu class="select options"><div>'.implode('', $arr_types_classifications['classifications']).'</div></menu>
			</div>' : '').'
			'.($arr_types_classifications['reversals'] ? '<div>
				<menu class="select options"><div>'.implode('', $arr_types_classifications['reversals']).'</div></menu>
			</div>' : '').'
		</div>';
		
		$return .= '<div class="dynamic">'.($type_id ? $this->createTypeOverview($type_id) : '').'</div>';
				
		return $return;
	}
	
	private function createTypeOverview($type_id) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$filter = new FilterTypeObjects($type_id);
		$filter->resetResultInfo();
		
		if ($this->arr_query['mode'][0] == 'add' || $this->arr_query['mode'][0] == 'edit') {

			$object_id = (int)$this->arr_query['object'][0];
			
			if ($object_id) {
				$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
				$storage->handleLockObject();
			}

			$return = $this->createAddEditTypeObject($type_id, $object_id);
		} else {
			$return = $this->createAddTypeObject($type_id, $arr_type_set['type']['name']);
		}
		
		toolbar::setFilter([(int)$type_id => []]);
		
		$arr_filter_settings = [];
		if ($this->arr_query['filter'][0]) {
			$arr_filter_settings['filter'] = '{"filter_id": '.(int)$this->arr_query['filter'][0].'}';
		} else if ($this->arr_query['object'][0]) {
			$arr_filter_settings['search'] = 'object:'.$this->arr_query['object'][0];
		}
		
		if ($this->arr_query) {
			SiteEndVars::setModVariables($this->mod_id, [], true); // Clear the settings in the url
		}
				
		$return .= cms_general::createDataTableHeading('d:data_entry:data-'.$type_id, ['filter' => 'y:data_filter:open_filter-'.$type_id, 'filter_settings' => $arr_filter_settings['filter'], 'filter_search' => $arr_filter_settings['search'], 'order' => true]).'
			<thead><tr>';
			
				$nr_column = 0;
				
				if ($arr_type_set['type']['object_name_in_overview']) {

					$return .= '<th class="max limit"><span>'.getLabel('lbl_name').'</span></th>';
					$nr_column++;
				}
			
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
					if (!$arr_object_description['object_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
						continue;
					}
					
					$return .= '<th class="limit'.($nr_column == 0 ? ' max' : '').'"><span>'.htmlspecialchars(Labels::parseTextVariables($arr_object_description['object_description_name'])).'</span></th>';
					$nr_column++;
				}
				
				$arr_analysis = data_analysis::getTypeAnalysis($type_id);
				$arr_analysis_context = data_analysis::getTypeAnalysisContext($type_id);
				
				if ($arr_analysis || $arr_analysis_context) {

					$return .= '<th class="analysis limit'.($nr_column == 0 ? ' max' : '').'" data-identifier="analysis"><span>'.data_analysis::createTypeAnalysisTableHeader($type_id, $arr_analysis, $arr_analysis_context).'</span></th>';
					$nr_column++;
				}
				
				$return .= ($_SESSION['NODEGOAT_CLEARANCE'] != NODEGOAT_CLEARANCE_INTERACT ? '<th title="'.getLabel('lbl_version').'" data-sort="desc-0" data-identifier="version"><span>V</span></th>' : '').'
				'.($_SESSION['NODEGOAT_CLEARANCE'] != NODEGOAT_CLEARANCE_INTERACT ? '<th class="disable-sort menu" id="x:data_entry:type_id-'.$type_id.'" title="'.getLabel('lbl_multi_select').'">'
					.'<div class="hide-edit hide">'
						.(!$arr_type_set['type']['is_reversal'] ? '<input type="button" class="data edit popup popup_find_change" value="c" title="'.getLabel('lbl_change').'" />' : '')
						.(!$arr_type_set['type']['is_reversal'] ? '<input type="button" class="data edit popup popup_merge" value="m" title="'.getLabel('lbl_merge').'" />' : '')
						.'<input type="button" class="data msg del" value="d" title="'.getLabel('lbl_delete').'" />'
					.'</div>'
					.'<input type="button" class="data neutral" value="multi" />'
					.'<input type="checkbox" class="multi all" value="" />'
				.'</th>' : '').'
			</tr></thead>
			<tbody>
				<tr>
					<td colspan="'.($nr_column+2).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
				</tr>
			</tbody>
		</table>';
		
		return $return;
	}
	
	private function createAddTypeObject($type_id, $name) {
		
		if ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_INTERACT) {
			return;
		}
	
		$return .= '<form id="f:data_entry:add-'.$type_id.'" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.htmlspecialchars(Labels::parseTextVariables($name)).'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	private function createAddEditTypeObject($type_id, $object_id = 0, $arr_options = []) {
		
		if ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_INTERACT) {
			return;
		}
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_project = $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']];
		$arr_options += ['source_referencing' => $arr_project['source_referencing'], 'discussion' => $arr_project['discussion_provide']];
	
		$return .= '<form id="f:data_entry:'.($object_id ? 'update' : 'insert').'-'.$type_id.'_'.(int)$object_id.'" data-lock="1">
			'.$this->createTypeObject($type_id, $object_id, $arr_options).'
			<menu class="options">
				<input type="submit" value="'.getLabel('lbl_save').' '.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])).'" /><input type="submit" name="discard" value="'.getLabel('lbl_cancel').'" />
			</menu>
		</form>';
		
		return $return;
	}

	private function createTypeObject($type_id, $object_id = 0, $arr_options = ['source_referencing' => false, 'discussion' => true]) {
		
		$project_id = $_SESSION['custom_projects']['project_id'];
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($project_id);
		
		$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
		$arr_types = StoreType::getTypes();

		if ((int)$object_id) {
			
			$arr_object_set = self::getTypeObjectSet($type_id, $object_id, false, $arr_type_set);
			
			if ($arr_type_set['type']['is_reversal']) {
				$arr_object_set_changes = $arr_object_set;
			} else {
				$arr_object_set_changes = self::getTypeObjectSet($type_id, $object_id, true, $arr_type_set);
			}
			
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
			$arr_version_user = $storage->getTypeObjectVersionsUsers();
			
			if ($arr_version_user[-1]) {
				Labels::setVariable('name', '['.$arr_version_user[-1]['name'].']');
				Labels::setVariable('date', date('d-m-Y', strtotime($arr_version_user[-1]['date'])));
				$str_version = getLabel('inf_added_by_on');
			}
			
			$str_object_id = GenerateTypeObjects::encodeTypeObjectId($type_id, $object_id);
			$str_object_name = $arr_object_set_changes['object']['object_name'];
		}
		
		$return = '<div class="tabs entry">
			<ul>
				<li><a href="#">'.getLabel(($arr_type_set['type']['is_reversal'] ? 'lbl_category' : ($arr_type_set['type']['is_classification'] ? 'lbl_category' : 'lbl_object'))).'</a></li>
				'.(!$arr_type_set['type']['is_reversal'] && $arr_options['source_referencing'] ? '<li><a href="#">'.getLabel('lbl_sources').'</a></li>' : '').'
				'.(!$arr_type_set['type']['is_reversal'] && $arr_options['discussion'] && $object_id ? '<li><a href="#">'.getLabel('lbl_discuss').'</a></li>' : '').'
			</ul>
				
			<div class="object">
				
				'.($object_id ? 
					'<h1>'
						.'<span'.($str_version ? ' title="'.$str_version.'"' : '').'>'.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])).': '.$str_object_name.'</span>'
						.'<small title="nodegoat ID">'.$str_object_id.'</small>'
					.'</h1>'
					:
					'<h1><span>'.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])).'</span></h1>'
				).'
				
				<div class="options">
									
					<fieldset class="object">
						'.($object_id ? '<input type="hidden" id="y:data_entry:set_lock-'.$type_id.'_'.(int)$object_id.'" value="'.$arr_options['lock_key'].'" />' : '').'
						<ul>';
						
						if ($arr_type_set['type']['is_reversal']) {
							
							$return .= '<li>
								<label>'.getLabel('lbl_name').'</label>
								<div><input type="text" name="object_name_plain" class="default" value="'.htmlspecialchars($arr_object_set['object']['object_name_plain']).'" /></div>
							</li>';
							
							$this->validate['object_name_plain'] = 'required';
							
							$arr_object_description = $arr_type_set['object_descriptions'][$arr_type_set['object_description_ids']['rc_ref_type_id']];
							
							if ($arr_type_set['type']['mode'] == 0 && $arr_object_description['object_description_ref_type_id']) {
								
								$return .= '<li>
									<label>'.htmlspecialchars(Labels::parseTextVariables($arr_object_description['object_description_name'])).'</label>
									<div class="definition">
										<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$arr_object_description['object_description_ref_type_id'].'" name="object_definition['.$arr_object_description['object_description_id'].'][object_definition_ref_object_id]" value="'.$arr_object_set['object_definitions'][$arr_object_description['object_description_id']]['object_definition_ref_object_id'].'" /><input type="search" id="y:data_filter:lookup_type_object-'.$arr_object_description['object_description_ref_type_id'].'" class="autocomplete" value="'.$arr_object_set['object_definitions'][$arr_object_description['object_description_id']]['object_definition_value'].'" />
									</div>
								</li>';
							}
							
							$arr_ref_type_ids = cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id']);
							$arr_type_referenced = FilterTypeObjects::getTypesReferenced($type_id, $arr_ref_type_ids, ['dynamic' => false]);
							
							$arr_html_tabs = [];
							
							foreach ($arr_type_referenced as $ref_type_id => $arr_ref_type) {
								
								$arr_reference_type_set = StoreType::getTypeSet($ref_type_id);
								
								$arr_html_tabs['links'][] = '<li><a href="#">'.Labels::parseTextVariables(htmlspecialchars($arr_reference_type_set['type']['name'])).'</a></li>';
								
								$create_filter = new data_filter();
								$create_filter->form_name = 'filter['.$ref_type_id.']';
								$html_filter = $create_filter->createFilter($ref_type_id, $arr_object_set['object_filters'][$ref_type_id]['object_filter_object']);
								
								if ($arr_type_set['type']['mode'] == 1) { // Summary mode
									
									$html_type_network = data_model::createTypeNetwork($ref_type_id, false, false, ['references' => 'both', 'network' => ['dynamic' => true, 'object_sub_locations' => true], 'value' => $arr_object_set['object_filters'][$ref_type_id]['object_filter_scope_object'], 'name' => 'scope['.$ref_type_id.']', 'descriptions' => false, 'functions' => ['collapse' => false]]);
								
									$html_tab_content = '<div class="tabs">
										<ul>
											<li><a href="#">'.getLabel('lbl_filter').'</a></li>
											<li><a href="#">'.getLabel('lbl_scope').'</a></li>
										</ul>
										<div>'.$html_filter.'</div>
										<div>
											<div class="options">'.$html_type_network.'</div>
										</div>
									</div>';
								} else { // Classification mode
									
									$html_tab_content = $html_filter;
								}
								
								$arr_html_tabs['content'][] = '<div>'.$html_tab_content.'</div>';					
							}
							
							if ($arr_html_tabs) {
								
								$return .= '<div class="tabs">
									<ul>
										'.implode('', $arr_html_tabs['links']).'
									</ul>';
									
									$return .= implode('', $arr_html_tabs['content']);
										
								$return .= '</div>';
							}
						} else {
						
							if ($arr_type_set['type']['use_object_name']) {
								
								$is_changed = ($arr_object_set_changes && $arr_object_set_changes['object']['object_version'] != 'added' && $arr_object_set_changes['object']['object_name_plain'] != $arr_object_set['object']['object_name_plain']);
								
								$return .= '<li>
									<label>'.getLabel('lbl_name').'</label>
									<div><input type="text" name="object_name_plain" class="default" value="'.htmlspecialchars(($arr_object_set_changes ? $arr_object_set_changes['object']['object_name_plain'] : $arr_object_set['object']['object_name_plain'])).'" />'.($object_id ? '<button type="button" id="y:data_entry:select_version-'.$type_id.'_'.$object_id.'" class="data '.($is_changed ? 'edit' : 'neutral').' popup" value="version"><span>'.getLabel('lbl_version_abbr').'</span></button>' : '').'</div>
								</li>';
								$this->validate['object_name_plain'] = 'required';
							}
							
							foreach ((array)$arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
								
								if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
									continue;
								}
								
								$is_reversal = ($arr_object_description['object_description_ref_type_id'] && $arr_types[$arr_object_description['object_description_ref_type_id']]['is_reversal'] ? true : false);
								$has_clearance_edit = ($_SESSION['NODEGOAT_CLEARANCE'] >= $arr_object_description['object_description_clearance_edit'] && custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, $object_description_id));
								$str_name = htmlspecialchars(Labels::parseTextVariables($arr_object_description['object_description_name']));
								
								$return .= '<li data-object_description_id="'.$object_description_id.'">'
									.'<label>'.($arr_object_description['object_description_is_referenced'] ? '<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.Labels::parseTextVariables($arr_types[$arr_object_description['object_description_ref_type_id']]['name']).' - '.$str_name.'</span>' : $str_name).
										($has_clearance_edit && !$is_reversal ? '<input type="hidden" name="object_definition['.$object_description_id.'][object_description_id]" value="'.$object_description_id.'" />' : '')
									.'</label>
									<div class="definition">';
									
										$definition_type = ($arr_object_description['object_description_ref_type_id'] ? 'object_definition_ref_object_id' : 'object_definition_value');
										$is_changed = ($arr_object_set_changes && $arr_object_set_changes['object']['object_version'] != 'added' && $arr_object_set_changes['object_definitions'][$object_description_id][$definition_type] != $arr_object_set['object_definitions'][$object_description_id][$definition_type]);
										$arr_object_definition = ($arr_object_set_changes ? $arr_object_set_changes['object_definitions'][$object_description_id] : $arr_object_set['object_definitions'][$object_description_id]);
										$has_multi = $arr_object_description['object_description_has_multi'];
										
										if ($is_reversal) {
											
											$return .= '<div class="show">'.$arr_types[$arr_object_description['object_description_ref_type_id']]['name'].'</div>';
										} else {
											
											$arr_object_definition_value = ($arr_object_definition ?: []);
											
											if ($arr_object_description['object_description_ref_type_id']) {
																								
												if (!$object_id) {
													
													$value_default = $arr_object_description['object_description_value_type_options']['default']['value'];
													
													if ($value_default) {
														
														$arr_object_definition_value = FilterTypeObjects::getTypeObjectNames($arr_object_description['object_description_ref_type_id'], $value_default);
														
														if (!$has_multi) {
															$arr_object_definition_value = ['object_definition_ref_object_id' => key($arr_object_definition_value), 'object_definition_value' => current($arr_object_definition_value)];
														}
													}
												} else {
	
													if ($has_multi) {
														
														if ($arr_object_definition_value['object_definition_ref_object_id']) {
															$arr_object_definition_value = array_combine($arr_object_definition_value['object_definition_ref_object_id'], $arr_object_definition_value['object_definition_value']);
														} else {
															$arr_object_definition_value = [];
														}
													}
												}												
											} else {
												
												if (!$object_id) {
													$arr_object_definition_value['object_definition_value'] = $arr_object_description['object_description_value_type_options']['default']['value'];
												}

												if ($has_multi) {
													
													if (!is_array($arr_object_definition_value['object_definition_value'])) {
														$arr_object_definition_value['object_definition_value'] = ($arr_object_definition_value['object_definition_value'] ? (array)$arr_object_definition_value['object_definition_value'] : []);
													}
												}
											}
											
											if (!$has_clearance_edit) {
												
												$return .= '<div class="show">';

												if ($arr_object_description['object_description_ref_type_id']) {
													
													if ($has_multi) {
														
														if (!$arr_object_definition_value) {
															$return .= getLabel('lbl_none');
														} else {
															$return .= implode(', ', $arr_object_definition_value);
														}
													} else {
														
														if (!$arr_object_definition_value['object_definition_value']) {
															$return .= getLabel('lbl_none');
														} else {
															$return .= $arr_object_definition_value['object_definition_value'];
														}
													}
												} else {
													
													$return .= StoreTypeObjects::formatToPresentationValue($arr_object_description['object_description_value_type'], StoreTypeObjects::formatToCleanValue($arr_object_description['object_description_value_type'], $arr_object_definition_value['object_definition_value'], $arr_object_description['object_description_value_type_options']), $arr_object_description['object_description_value_type_options'], $arr_object_definition_value['object_definition_ref_object_id']);
												}
												$return .= '</div>';
											} else if ($arr_object_description['object_description_ref_type_id']) {
												
												$str_name = 'object_definition['.$object_description_id.'][object_definition_ref_object_id]';

												if ($has_multi) {
													
													$return .= cms_general::createMultiSelect($str_name, 'y:data_filter:lookup_type_object-'.$arr_object_description['object_description_ref_type_id'], $arr_object_definition_value, 'y:data_filter:lookup_type_object_pick-'.$arr_object_description['object_description_ref_type_id'], ['list' => false]);
												} else {
													
													$return .= '<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$arr_object_description['object_description_ref_type_id'].'" name="'.$str_name.'" value="'.$arr_object_definition_value['object_definition_ref_object_id'].'" /><input type="search" id="y:data_filter:lookup_type_object-'.$arr_object_description['object_description_ref_type_id'].'" class="autocomplete" value="'.$arr_object_definition_value['object_definition_value'].'" />';
												}

												if ($arr_object_description['object_description_is_required']) {
													$this->validate[$str_name] = ($has_multi ? 'required_autocomplete' : 'required');
												}
											} else {
												
												$str_name = 'object_definition['.$object_description_id.'][object_definition_value]';

												$return .= StoreTypeObjects::formatToFormValue($arr_object_description['object_description_value_type'], $arr_object_definition_value['object_definition_value'], $str_name, $arr_object_description['object_description_value_type_options'], $arr_object_definition_value['object_definition_ref_object_id']);
												
												if ($arr_object_description['object_description_is_required']) {
													$this->validate[$str_name] = 'required';
												}
											}
										}
										
										if (!$is_reversal && !$arr_object_description['object_description_is_referenced'] && ($object_id || $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing'])) {
											
											$str_sources = ($arr_object_definition['object_definition_sources'] ? htmlspecialchars(json_encode($arr_object_definition['object_definition_sources'])) : '');
											
											$return .= '<input type="hidden" value="'.$str_sources.'"'.($has_clearance_edit ? ' name="object_definition['.$object_description_id.'][object_definition_sources]"' : '').' />'
												.'<button type="button" id="y:data_entry:select_version-'.$type_id.'_'.$object_id.'_'.$object_description_id.'" class="data popup '.($is_changed ? 'edit' : 'neutral').'" value="version"><span>'.getLabel('lbl_version_abbr').'</span></button>';
										}
									$return .= '</div>
								</li>';
							}
						}
					
						$return .= '</ul>
					</fieldset>';
					
					if ($arr_type_set['object_sub_details']) {
						
						$arr_collect_object_sub_details = [];
						$arr_object_sub_tabs = [];
						$has_direct_show = false;
						
						$html_handler = '<span class="icon">'.getIcon('plus').'</span>';
						$html_handler_multi = '<span class="icon">'.getIcon('plus').getIcon('plus').'</span>';
						
						$return_tab = '<div>
							<div class="entry object-subs options">
								<div class="tags"><ul>';
								
									$arr_direct_show = [];
								
									foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
										
										if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
											continue;
										}
										
										$arr_classes = [];
										$arr_classes[] = ($arr_object_sub_details['object_sub_details']['object_sub_details_is_unique'] ? 'unique' : '');
										$arr_classes[] = ($arr_object_sub_details['object_sub_details']['object_sub_details_is_required'] ? 'required' : '');
										$arr_classes = array_filter($arr_classes);
										
										$return_tab .= '<li><span>'
												.($arr_object_sub_details['object_sub_details']['object_sub_details_type_id'] ?
													'<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span>'
													.'<span>'.htmlspecialchars(Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details']['object_sub_details_type_id']]['name'])).'</span> '
												: '')
												.'<span class="sub-name">'.htmlspecialchars(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span>'
											.'</span>'
											.'<span class="handler'.($arr_classes ? ' '.implode(' ', $arr_classes) : '').'" id="y:data_entry:add_object_sub-'.$type_id.'_'.(int)$object_id.'_'.$object_sub_details_id.'">'.$html_handler.'</span>'
											.(!$arr_object_sub_details['object_sub_details']['object_sub_details_is_unique'] ? '<span class="handler'.($arr_classes ? ' '.implode(' ', $arr_classes) : '').'" id="y:data_entry:add_object_sub_multi-'.$type_id.'_'.(int)$object_id.'_'.$object_sub_details_id.'">'.$html_handler_multi.'</span>' : '')
										.'</li>';
										
										if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_required']) {
											$arr_direct_show[$object_sub_details_id] = $object_sub_details_id;
										}
									}
								
								$return_tab .= '</ul></div>';
									
								$return_tab .= '<div class="dynamic-object-subs fieldsets"><div>';
								
									if ($object_id) {
										
										foreach((array)$arr_object_set_changes['object_subs'] as $arr_object_sub) {
											
											$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
											
											$arr_collect_object_sub_details[$object_sub_details_id] = $object_sub_details_id;
											unset($arr_direct_show[$object_sub_details_id]);

											if (!$arr_object_sub['object_sub']['object_sub_version']) { // Only show changed subobjects in the initial view
												continue;
											}
										
											$return_tab .= $this->createTypeObjectSub($type_id, $object_sub_details_id, $object_id, $arr_object_set['object_subs'][$arr_object_sub['object_sub']['object_sub_id']], $arr_object_sub);
											$has_direct_show = true;
										}
									}
									
									foreach ($arr_direct_show as $object_sub_details_id) { // Show subobjects by default when they are not in use
										
										$return_tab .= $this->createTypeObjectSub($type_id, $object_sub_details_id);
										$has_direct_show = true;
									}
									
								$return_tab .= '</div></div>
							</div>
						</div>';
						
						if ($object_id) {
						
							foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
								
								if (!$arr_collect_object_sub_details[$object_sub_details_id] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
									continue;
								}
								
								$arr_object_sub_tabs['links'][] = '<li><a href="#"'.(!$has_direct_show && count($arr_collect_object_sub_details) == 2 ? ' class="open"' : '').'><span>'.($arr_object_sub_details['object_sub_details']['object_sub_details_type_id'] ? '<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details']['object_sub_details_type_id']]['name']).'</span> ' : '').'<span class="sub-name">'.htmlspecialchars(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span></span></a></li>';
								
								$arr_columns = [];
								$nr_column = 0;
								
								if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_date']) {
									
									$arr_columns[] = '<th class="date"><span>'.getLabel('lbl_date_start').'</span></th><th class="date"><span>'.getLabel('lbl_date_end').'</span></th>';
									$nr_column += 2;
								}
								if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_location']) {
									
									$arr_columns[] = '<th class="limit disable-sort"></th><th class="max limit disable-sort"><span>'.getLabel('lbl_location').'</span></th>';
									$nr_column += 2;
								}
								
								foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
									
									if (!$arr_object_sub_description['object_sub_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
										continue;
									}
									
									$str_name = htmlspecialchars(Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']));
									
									$arr_columns[] = '<th class="limit'.($nr_column == 0 ? ' max' : '').($arr_object_sub_description['object_sub_description_value_type'] == 'date' ? ' date' : '').'">'.($arr_object_sub_description['object_sub_description_is_referenced'] ? '<span><span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.$str_name.'</span></span>' : '</span>'.$str_name.'</span>').'</th>';
									$nr_column++;
								}
								
								$return_content = '<div>
									'.cms_general::createDataTableHeading('d:data_entry:data_object_sub_details-'.$type_id.'_'.$object_id.'_'.$object_sub_details_id, ['filter' => 'y:data_filter:open_filter-'.$type_id, 'pause' => true, 'search' => false, 'order' => true]).'
										<thead><tr>'
											.implode('', $arr_columns)
											.'<th class="disable-sort menu" id="x:data_entry:object_sub_id-0" title="'.getLabel('lbl_multi_select').'">'
												.'<input type="button" class="data edit edit_object_sub" value="e" title="'.getLabel('lbl_edit').'" />'
												.(!($arr_object_sub_details['object_sub_details']['object_sub_details_is_required'] && $arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) ? '<input type="button" class="data del msg del_object_sub" value="d" title="'.getLabel('lbl_delete').'" />' : '')
												.'<input type="checkbox" class="multi all" value="" />'
											.'</th>
										</tr></thead>
										<tbody>
											<tr>
												<td colspan="'.($nr_column+1).'" class="empty">'.getLabel('msg_loading_server_data').'</td>
											</tr>
										</tbody></table>
								</div>';
								
								$arr_object_sub_tabs['content'][] = $return_content;
							}
							
							if ($arr_object_sub_tabs && count($arr_collect_object_sub_details) > 2) { // Show combined only if there are multiple subobjects to be shown
								
								array_unshift($arr_object_sub_tabs['links'], '<li><a href="#"'.(!$has_direct_show ? ' class="open"' : '').'>'.getLabel('lbl_object_subs').': '.getLabel('lbl_overview').'</a></li>');
								
								$return_content = '<div>
									'.cms_general::createDataTableHeading('d:data_entry:data_object_sub_details-'.$type_id.'_'.$object_id.'_all', ['filter' => 'y:data_filter:open_filter-'.$type_id, 'pause' => true, 'search' => false, 'order' => true]).'
										<thead><tr>'
											.'<th class="limit" title="'.getLabel('lbl_object_sub').'"><span></span></th>'
											.'<th class="date"><span>'.getLabel('lbl_date_start').'</span></th><th class="date"><span>'.getLabel('lbl_date_end').'</span></th>'
											.'<th class="limit disable-sort"></th><th class="max limit disable-sort"><span>'.getLabel('lbl_location').'</span></th>'
											.'<th class="disable-sort menu" id="x:data_entry:object_sub_id-0" title="'.getLabel('lbl_multi_select').'">'
												.'<input type="button" class="data edit edit_object_sub" value="e" title="'.getLabel('lbl_edit').'" />'
												.'<input type="checkbox" class="multi all" value="" />'
											.'</th>
										</tr></thead>
										<tbody>
											<tr>
												<td colspan="6" class="empty">'.getLabel('msg_loading_server_data').'</td>
											</tr>
										</tbody></table>
								</div>';

								array_unshift($arr_object_sub_tabs['content'], $return_content);
							}
						}
						
						$return .= '<div class="tabs">
							<ul>
								<li><a href="#">'.getLabel('lbl_object_subs').': '.getLabel('lbl_editor').'</a></li>
								'.($arr_object_sub_tabs ? implode('', $arr_object_sub_tabs['links']) : '').'
							</ul>';
							
							$return .= $return_tab;
							$return .= ($arr_object_sub_tabs ? implode('', $arr_object_sub_tabs['content']) : '');
							
						$return .= '</div>';							
					}
					
				$return .= '</div>
			</div>';
			
			if (!$arr_type_set['type']['is_reversal'] && $arr_options['source_referencing']) {
			
				$return .= '<div>
					<div class="options">
						'.$this->createSelectSources('object', $type_id, $object_id, ['object_sources' => $arr_object_set_changes['object']['object_sources']]).'
					</div>
				</div>';
			}
			
			if (!$arr_type_set['type']['is_reversal'] && $arr_options['discussion'] && $object_id) {
				
				$return .= '<div class="discussion">		
					'.$this->createDiscussion($type_id, $object_id).'
				</div>';
			}
			
		$return .= '</div>';
		
		return $return;
	}
		
	private function createTypeObjectSub($type_id, $object_sub_details_id, $object_id = 0, $arr_object_subs = [], $arr_object_subs_changes = [], $arr_options = []) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_types = StoreType::getTypes();
		$arr_project_location_types = [];
		
		foreach ($arr_types as $cur_type_id => $value) {
			if ($arr_project['location_types'][$cur_type_id] || (!$arr_project['location_types'] && !$value['is_classification'])) {
				$arr_project_location_types[$cur_type_id] = $value;
			}
		}
		
		$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
		$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
		
		$is_multi = ($arr_object_subs && !arrIsAssociative($arr_object_subs));
		
		$arr_object_subs = ($is_multi ? $arr_object_subs : [$arr_object_subs]);
		$arr_object_subs_changes = ($is_multi ? $arr_object_subs_changes : [$arr_object_subs_changes]);
		
		if ($is_multi) {
			
			array_unshift($arr_object_subs, ($arr_options['multi_source'] ?: [])); // Empty run for sorter source
			array_unshift($arr_object_subs_changes, []);
			
			$arr_sorter = [];
	
			$return = '<fieldset class="full"><legend>
					'.($arr_object_sub_details['object_sub_details']['object_sub_details_type_id'] ? '<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.htmlspecialchars(Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details']['object_sub_details_type_id']]['name'])).'</span> ' : '').'<span class="sub-name">'.htmlspecialchars(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span>
				</legend>';
		}
		
		$cur_form_name = $this->form_name;
		
		foreach ($arr_object_subs as $key => $arr_object_sub) {
			
			$unique_sub = uniqid('array_');
			
			$form_name = ($cur_form_name ?: 'object_sub['.$unique_sub.']').'[object_sub]';
			$this->form_name = $form_name;
			
			$arr_object_sub_changes = $arr_object_subs_changes[$key];
			
			$is_changed = ($arr_object_sub_changes && (($arr_object_sub_changes['object_sub']['object_sub_version'] != 'added' && $arr_object_sub_changes['object_sub'] !== $arr_object_sub['object_sub']) || $arr_object_sub_changes['object_sub']['object_sub_version'] == 'deleted'));
			$arr_object_sub_value = ($arr_object_sub_changes ? $arr_object_sub_changes['object_sub'] : $arr_object_sub['object_sub']);
			$str_sources = ($arr_object_sub_value['object_sub_sources'] ? htmlspecialchars(json_encode($arr_object_sub_value['object_sub_sources'])) : '');
			
			$arr_html_fields = [
				'buttons' => (!($arr_object_sub_details['object_sub_details']['object_sub_details_is_required'] && $arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) && keyIsUncontested('button_delete', $arr_options) ? '<input type="button" class="data del" value="del" />' : '')
					.($arr_object_sub_value['object_sub_id'] || $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing'] ? '<input type="hidden" value="'.$str_sources.'" name="'.$form_name.'[object_sub_sources]" /><button type="button" id="y:data_entry:select_version-'.($arr_object_sub_details['object_sub_details']['object_sub_details_type_id'] ? $arr_object_sub_details['object_sub_details']['object_sub_details_type_id'].'_'.(int)$arr_object_sub_value['object_sub_object_id'] : $type_id.'_'.$object_id).'_'.$object_sub_details_id.'_'.(int)$arr_object_sub_value['object_sub_id'].'" class="data popup '.($is_changed ? 'edit' : 'neutral').'" value="version"><span>'.getLabel('lbl_version_abbr').'</span></button>' : ''),
				'hidden' => ($arr_object_sub_value['object_sub_id'] ? '<input type="hidden" name="'.$form_name.'[object_sub_id]" value="'.$arr_object_sub_value['object_sub_id'].'" /><input type="hidden" name="'.$form_name.'[object_sub_version]" value="'.$arr_object_sub_value['object_sub_version'].'" />' : '').'
					<input type="hidden" name="'.$form_name.'[object_sub_details_id]" value="'.$object_sub_details_id.'" />
					<input type="hidden" name="'.$form_name.'[object_sub_self]" value="1" />',
			];

			$arr_html_fields += $this->createTypeObjectSubFields($type_id, $object_sub_details_id, $object_id, $arr_object_sub_value, ['date' => true, 'location_geometry' => true, 'location_reference' => true]);
			
			$form_name = ($cur_form_name ?: 'object_sub['.$unique_sub.']').'[object_sub_definitions]';
			$this->form_name = $form_name;
						
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
					continue;
				}
				
				if ($arr_object_sub_description['object_sub_description_value_type'] == 'object_description') {
					
					$arr_html_fields['object_sub_descriptions'][$object_sub_description_id] = '<span>'.htmlspecialchars(($arr_object_sub_description['object_sub_description_use_object_description_id'] ? Labels::parseTextVariables($arr_type_set['object_descriptions'][$arr_object_sub_description['object_sub_description_use_object_description_id']]['object_description_name']) : getLabel('lbl_none'))).'</span>';
				} else {
					
					$is_reversal = ($arr_object_sub_description['object_sub_description_ref_type_id'] && $arr_types[$arr_object_sub_description['object_sub_description_ref_type_id']]['is_reversal'] ? true : false);
					$has_clearance_edit = ($_SESSION['NODEGOAT_CLEARANCE'] >= $arr_object_sub_description['object_sub_description_clearance_edit'] && custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id));
					$definition_type = ($arr_object_sub_description['object_sub_description_ref_type_id'] ? 'object_sub_definition_ref_object_id' : 'object_sub_definition_value');
					$is_changed = ($arr_object_sub_changes && $arr_object_sub_changes['object_sub']['object_sub_version'] != 'added' && $arr_object_sub_changes['object_sub_definitions'][$object_sub_description_id][$definition_type] != $arr_object_sub['object_sub_definitions'][$object_sub_description_id][$definition_type]);
					$arr_object_sub_definition = ($arr_object_sub_changes ? $arr_object_sub_changes['object_sub_definitions'][$object_sub_description_id] : $arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
					
					if ($is_reversal) {
						
						$arr_html_fields['object_sub_descriptions'][$object_sub_description_id] = '<div class="show">'.htmlspecialchars(Labels::parseTextVariables($arr_types[$arr_object_sub_description['object_sub_description_ref_type_id']]['name'])).'</div>';
					} else {
						
						$arr_object_sub_definition_value = ($arr_object_sub_definition ?: []);
						
						if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
							
							if (!$arr_object_sub_value['object_sub_id']) {
								
								$value_default = $arr_object_sub_description['object_sub_description_value_type_options']['default']['value'];
								
								if ($value_default) {
									
									$arr_object_sub_definition_value = FilterTypeObjects::getTypeObjectNames($arr_object_sub_description['object_sub_description_ref_type_id'], $value_default);
									$arr_object_sub_definition_value = ['object_sub_definition_ref_object_id' => key($arr_object_sub_definition_value), 'object_sub_definition_value' => current($arr_object_sub_definition_value)];
								}
							}
						} else {
							
							if (!$arr_object_sub_value['object_sub_id']) {
								$arr_object_sub_definition_value['object_sub_definition_value'] = $arr_object_sub_description['object_sub_description_value_type_options']['default']['value'];
							}
							
							if ($arr_object_sub_description['object_sub_description_has_multi']) {
								
								if (!is_array($arr_object_sub_definition_value['object_sub_definition_value'])) {
									$arr_object_sub_definition_value['object_sub_definition_value'] = ($arr_object_sub_definition_value['object_sub_definition_value'] ? (array)$arr_object_sub_definition_value['object_sub_definition_value'] : []);
								}
							}
						}
						
						if (!$has_clearance_edit) {

							$html = '<div class="show">';
							
							if (!$arr_object_sub_definition_value['object_sub_definition_value']) {
								$html .= getLabel('lbl_none');
							}

							if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
								
								$html .= $arr_object_sub_definition_value['object_sub_definition_value'];
							} else {
								
								$html .= StoreTypeObjects::formatToPresentationValue($arr_object_sub_description['object_sub_description_value_type'], StoreTypeObjects::formatToCleanValue($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition_value['object_sub_definition_value'], $arr_object_sub_description['object_sub_description_value_type_options']), $arr_object_sub_description['object_sub_description_value_type_options'], $arr_object_sub_definition_value['object_sub_definition_ref_object_id']);
							}
							
							$html .= '</div>';
							$arr_html_fields['object_sub_descriptions'][$object_sub_description_id] = $html;
						} else if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
							
							$str_name = $form_name.'['.$object_sub_description_id.'][object_sub_definition_ref_object_id]';

							$arr_html_fields['object_sub_descriptions'][$object_sub_description_id] = '<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$arr_object_sub_description['object_sub_description_ref_type_id'].'" name="'.$str_name.'" value="'.$arr_object_sub_definition_value['object_sub_definition_ref_object_id'].'" />'
								.'<input type="search" id="y:data_filter:lookup_type_object-'.$arr_object_sub_description['object_sub_description_ref_type_id'].'" class="autocomplete" value="'.$arr_object_sub_definition_value['object_sub_definition_value'].'" />';
						
							if ($arr_object_sub_description['object_sub_description_is_required']) {
								$this->validate[$str_name] = 'required';
							}
						} else {
							
							$str_name = $form_name.'['.$object_sub_description_id.'][object_sub_definition_value]';
				
							$arr_html_fields['object_sub_descriptions'][$object_sub_description_id] = StoreTypeObjects::formatToFormValue($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition_value['object_sub_definition_value'], $str_name, $arr_object_sub_description['object_sub_description_value_type_options'], $arr_object_sub_definition_value['object_sub_definition_ref_object_id']);
						
							if ($arr_object_sub_description['object_sub_description_is_required']) {
								$this->validate[$str_name] = 'required';
							}
						}
					}
					
					if (!$is_reversal && ($arr_object_sub_value['object_sub_id'] || $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing'])) {
						
						$str_sources = ($arr_object_sub_definition['object_sub_definition_sources'] ? htmlspecialchars(json_encode($arr_object_sub_definition['object_sub_definition_sources'])) : '');
						
						$arr_html_fields['object_sub_descriptions'][$object_sub_description_id] .= '<input type="hidden" value="'.$str_sources.'"'.($has_clearance_edit ? ' name="'.$form_name.'['.$object_sub_description_id.'][object_sub_definition_sources]"' : '').' />'
							.'<button type="button" id="y:data_entry:select_version-'.($arr_object_sub_description['object_sub_description_is_referenced'] ? $arr_object_sub_description['object_sub_description_ref_type_id'].'_'.$arr_object_sub_definition['object_sub_definition_ref_object_id'] : $type_id.'_'.$object_id).'_'.$object_sub_details_id.'_'.(int)$arr_object_sub_value['object_sub_id'].'_'.$object_sub_description_id.'" class="data popup '.($is_changed ? 'edit' : 'neutral').'" value="version"><span>'.getLabel('lbl_version_abbr').'</span></button>';
					}
				}
			}
			
			if ($is_multi) {
			
				$arr_html = [
					'<span class="hide">'.$arr_html_fields['hidden'].'</span>'.$arr_html_fields['buttons'],
					'<div class="date-section">'
						.$arr_html_fields['date'].$arr_html_fields['date_start'].$arr_html_fields['date_end']
					.'</div>',
					'<div class="location-section">'
						.($arr_html_fields['location_type'] ? $arr_html_fields['location_type'].$arr_html_fields['location_latitude'].$arr_html_fields['location_longitude'].$arr_html_fields['location_map'].$arr_html_fields['location_geometry'].$arr_html_fields['location_create'] : '')
						.$arr_html_fields['location_ref_type'].$arr_html_fields['location_ref_object_sub_details'].$arr_html_fields['location_object']
					.'</div>'
				];
				
				if ($arr_object_sub_details['object_sub_descriptions']) {
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
							continue;
						}
						
						$arr_html[] = '<div class="definition" title="'.htmlspecialchars(Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'])).'" data-object_sub_description_id="'.$object_sub_description_id.'">'
							.$arr_html_fields['object_sub_descriptions'][$object_sub_description_id]
						.'</div>';
					}
				}

				if ($is_multi && $key == 0) { // Add source row
					$arr_sorter[] = ['value' => $arr_html, 'source' => true];
				} else {
					$arr_sorter[] = ['value' => $arr_html, 'class' => ($arr_object_sub_value['object_sub_version'] ? $arr_object_sub_value['object_sub_version'] : '')];
				}
				
			} else {
				
				if ($arr_object_sub_value['object_sub_id']) {
					
					$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
				
					$arr_version_user = $storage->getTypeObjectSubVersionsUsers($arr_object_sub_value['object_sub_id']);
					
					if ($arr_version_user[-1]) {
						
						Labels::setVariable('name', '['.$arr_version_user[-1]['name'].']');
						Labels::setVariable('date', date('d-m-Y', strtotime($arr_version_user[-1]['date'])));
						$str_version = getLabel('inf_added_by_on');
					}
				}
				
				$return = '<fieldset'.($arr_object_sub_value['object_sub_version'] ? ' class="'.$arr_object_sub_value['object_sub_version'].'"' : '').'><legend>'
						.'<span'.($str_version ? ' title="'.$str_version.'"' : '').'>'.($arr_object_sub_details['object_sub_details']['object_sub_details_type_id'] ? '<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.htmlspecialchars(Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details']['object_sub_details_type_id']]['name'])).'</span> ' : '').'<span class="sub-name">'.Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'</span></span>'
						.$arr_html_fields['buttons'].'
					</legend>
					'.$arr_html_fields['hidden'].'
					<ul>';
					
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_date']) {
							
							if ($arr_html_fields['date']) {
								$return .= '<li class="date-section start">
									<label>'.getLabel('lbl_date').'</label>
									<div>'.$arr_html_fields['date'].'</div>
								</li>';
							} else {
								$return .= '<li class="date-section start">
									<label>'.getLabel(($arr_html_fields['date_end'] ? 'lbl_date_start' : 'lbl_date')).'</label>
									'.$arr_html_fields['date_start'].'
								</li>';
								if ($arr_html_fields['date_end']) {
									$return  .= '<li class="date-section">
										<label>'.getLabel('lbl_date_end').'</label>
										'.$arr_html_fields['date_end'].'
									</li>';
								}
							}
						}
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_location']) {
							
							if ($arr_html_fields['location_type']) {
								$return .= '<li class="location-section start">
									<label>'.getLabel('lbl_location').'</label>
									'.$arr_html_fields['location_type'].'
								</li>
								<li class="location-section">
									<label>'.getLabel('lbl_location').' '.getLabel('lbl_latitude').'</label>
									'.$arr_html_fields['location_latitude'].'
								</li>
								<li class="location-section">
									<label>'.getLabel('lbl_location').' '.getLabel('lbl_longitude').'</label>
									'.$arr_html_fields['location_longitude'].'
								</li>
								<li class="location-section">
									<label></label>
									<span>'.$arr_html_fields['location_map'].'</span>
								</li>
								<li class="location-section">
									<label>'.getLabel('lbl_location').' '.getLabel('lbl_geometry').'</label>
									'.$arr_html_fields['location_geometry'].'
								</li>
								<li class="location-section">
									<label></label>
									<span>'.$arr_html_fields['location_create'].'</span>
								</li>';
							}
							$return .= '<li class="location-section">
								<label>'.getLabel('lbl_location_reference').'</label>
								<div>'.$arr_html_fields['location_ref_type'].$arr_html_fields['location_ref_object_sub_details'].'</div>
							</li>
							<li class="location-section">
								<label></label>
								<div>'.$arr_html_fields['location_object'].'</div>
							</li>';
						}
						
						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
							
							$str_name = htmlspecialchars(Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']));
							
							$return .= '<li data-object_sub_description_id="'.$object_sub_description_id.'">
								<label>'.($arr_object_sub_description['object_sub_description_is_referenced'] ? '<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.htmlspecialchars(Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details']['object_sub_details_type_id']]['name'])).' - '.$str_name.'</span>' : $str_name).'</label>
								<div class="definition">'.$arr_html_fields['object_sub_descriptions'][$object_sub_description_id].'</div>
							</li>';
						}
						
					$return .= '</ul>
				</fieldset>';
			}
		}
		
		$this->form_name = $cur_form_name;
		
		if ($is_multi) {
			
				$return .= cms_general::createSorter($arr_sorter, false, false, ['auto_add' => true, 'full' => true])
			.'</fieldset>';
		}
	
		return $return;
	}
	
	private function createTypeObjectSubFields($type_id, $object_sub_details_id, $object_id, $arr_object_sub = [], $arr_options = ['date' => false, 'location_geometry' => false, 'location_reference' => false]) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_types = StoreType::getTypes();
		
		$arr_project_location_types = [];
		
		foreach ($arr_types as $cur_type_id => $value) {
			if ($arr_project['location_types'][$cur_type_id] || (!$arr_project['location_types'] && !$value['is_classification'])) {
				$arr_project_location_types[$cur_type_id] = $value;
			}
		}
		
		$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
		$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
		
		$arr_html_fields = [];
		
		$form_name = $this->form_name;
		
		if ($arr_options['date'] && $arr_object_sub_details['object_sub_details']['object_sub_details_has_date']) {
				
			if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_description_id']) {
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id']) {
					$arr_html_fields['date'] = '<span class="sub-name">'.Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id']]['object_sub_details']['object_sub_details_name']).'</span><span>'.getLabel('lbl_date').'</span>';
				} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_description_id']) {
					$arr_html_fields['date'] = '<span class="sub-name">'.Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'</span><span>'.Labels::parseTextVariables($arr_object_sub_details['object_sub_descriptions'][$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_description_id']]['object_sub_description_name']).'</span>';
				} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_description_id']) {
					$arr_html_fields['date'] = '<span>'.Labels::parseTextVariables($arr_type_set['object_descriptions'][$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_description_id']]['object_description_name']).'</span>';
				}
			} else {
				
				$arr_html_fields['date_start'] = '<input type="text" class="date" name="'.$form_name.'[object_sub_date_start]" value="'.StoreTypeObjects::formatToCleanValue('date', $arr_object_sub['object_sub_date_start']).'" />';
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_range']) {
					$arr_html_fields['date_end'] = '<input type="text" class="date" name="'.$form_name.'[object_sub_date_end]" value="'.StoreTypeObjects::formatToCleanValue('date', $arr_object_sub['object_sub_date_end']).'" />'
						.'<input type="checkbox" title="'.getLabel('inf_date_end_infinite').'" name="'.$form_name.'[object_sub_date_end_infinite]" value="1"'.($arr_object_sub['object_sub_date_end'] == DATE_INT_MAX ? ' checked="checked"' : '').' />';
				}
			}
		}
		
		if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_location']) {
			
			if ($arr_options['location_geometry'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_only']) {
				
				$arr_location_geometry = [];
				$arr_location_geometry_point = [];
				
				if ($arr_object_sub['object_sub_location_type'] && $arr_object_sub['object_sub_location_type'] == 'geometry') {
					
					if (!$arr_object_sub['object_sub_location_geometry']) {
						
						$arr_object_sub['object_sub_location_type'] = 'reference';
					} else {
						
						$arr_location_geometry = StoreTypeObjects::formatToGeometry($arr_object_sub['object_sub_location_geometry']);
						
						$arr_location_geometry_point = StoreTypeObjects::formatToGeometryPoint($arr_location_geometry);
						
						if ($arr_location_geometry_point) {
							$arr_object_sub['object_sub_location_type'] = 'point';
						}
					}
				}
				
				$arr_html_fields['location_geometry'] = '<textarea name="'.$form_name.'[object_sub_location_geometry]">'.($arr_location_geometry ? json_encode($arr_location_geometry, JSON_PRETTY_PRINT) : '').'</textarea>';
				$arr_html_fields['location_create'] = '<input type="button" class="data add select_geometry" value="create" />';
				
				$arr_html_fields['location_latitude'] = '<input type="text" name="'.$form_name.'[object_sub_location_latitude]" value="'.$arr_location_geometry_point[1].'" />';
				$arr_html_fields['location_longitude'] = '<input type="text" name="'.$form_name.'[object_sub_location_longitude]" value="'.$arr_location_geometry_point[0].'" />';
				$arr_html_fields['location_map'] = '<input type="button" class="data add" id="y:data_filter:select_geometry-0" value="map" />';
				
				$arr_location_options = data_filter::$arr_location_options;
				
				if (!$arr_options['location_reference']) {
					unset($arr_location_options['reference']);				
				}
				
				$arr_html_fields['location_type'] = '<select name="'.$form_name.'[object_sub_location_type]">'.cms_general::createDropdown(data_filter::$arr_location_options, $arr_object_sub['object_sub_location_type']).'</select>';
			}
			
			if ($arr_options['location_reference']) {
					
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']) {
					$ref_type_id = $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'];
					$arr_html_fields['location_ref_type'] = '<span data-type="reference">'.Labels::parseTextVariables($arr_types[$ref_type_id]['name']).'</span>';
				} else {
					$arr_html_fields['location_ref_type'] = '<select name="'.$form_name.'[object_sub_location_ref_type_id]" id="y:data_filter:selector_object_sub-0" class="update_object_type">'.Labels::parseTextVariables(cms_general::createDropdown($arr_project_location_types, ($arr_object_sub['object_sub_location_ref_type_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']))).'</select>';
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id_locked']) {
					$arr_location_object_sub_details = StoreType::getTypeObjectSubsDetails($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']);
					$arr_html_fields['location_ref_object_sub_details'] = '<span data-type="reference" class="sub-name">'.Labels::parseTextVariables($arr_location_object_sub_details[$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id']]['object_sub_details_name']).'</span>';
				} else {
					$arr_location_object_sub_details = StoreType::getTypeObjectSubsDetails(((($arr_object_sub['object_sub_location_ref_type_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']) ?: key($arr_project_location_types))));
					$arr_html_fields['location_ref_object_sub_details'] = '<select name="'.$form_name.'[object_sub_location_ref_object_sub_details_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_location_object_sub_details, ($arr_object_sub['object_sub_location_ref_object_sub_details_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id']), false, 'object_sub_details_name', 'object_sub_details_id')).'</select>';
				}
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id']) {
					$arr_html_fields['location_object'] = '<span data-type="reference" class="sub-name">'.Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id']]['object_sub_details']['object_sub_details_name']).'</span><span data-type="reference">'.getLabel('lbl_location').'</span>';
				} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id']) {
					$arr_html_fields['location_object'] = '<span data-type="reference" class="sub-name">'.Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'</span><span data-type="reference">'.Labels::parseTextVariables($arr_object_sub_details['object_sub_descriptions'][$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id']]['object_sub_description_name']).'</span>';
				} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']) {
					$arr_html_fields['location_object'] = '<span data-type="reference">'.Labels::parseTextVariables($arr_type_set['object_descriptions'][$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']]['object_description_name']).'</span>';
				} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_id']) {
					$arr_html_fields['location_object'] = '<span data-type="reference">'.getLabel('lbl_object_self').'</span>';
				} else {
					$ref_type_id = ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked'] ? $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'] : 0);
					$arr_html_fields['location_object'] = '<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$ref_type_id.'" name="'.$form_name.'[object_sub_location_ref_object_id]" value="'.$arr_object_sub['object_sub_location_ref_object_id'].'" />'
						.'<input type="search" id="y:data_filter:lookup_type_object-'.$ref_type_id.'" class="autocomplete" value="'.$arr_object_sub['object_sub_location_ref_object_name'].'" />';
				}
			}
		}
		
		return $arr_html_fields;
	}
	
	public function createDiscussion($type_id, $object_id) {

		$return = '<div>
			'.$this->createDiscussionContent($type_id, $object_id).'
		</div>';
	
		return $return;
	}
	
	public function createDiscussionContent($type_id, $object_id) {
		
		$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
		$arr_discussion = $storage->getDiscussion();
		
		$return = '<div class="discussion-content">';
		
			$return .= '<menu class="options">'
				.'<input type="button" class="quick" id="y:data_entry:edit_discussion-'.$type_id.'_'.(int)$object_id.'" value="'.getLabel('lbl_edit').' '.getLabel('lbl_discussion').'" />'
				.'<input type="hidden" value="'.($arr_discussion ? $arr_discussion['object_discussion_date_edited'] : '').'" id="y:data_entry:poll_discussion-'.$type_id.'_'.(int)$object_id.'" />'
			.'</menu>';
				
			if ($arr_discussion['object_discussion_body']) {
				
				$return .= '<div class="body">
					'.parseBody($arr_discussion['object_discussion_body']).'
				</div>';
			} else {
				
				$return .= '<section class="info attention">'.getLabel('msg_no_discussion').'</section>';
			}
			
		$return .= '</div>';
		
		return $return;
	}
	
	public function createDiscussionEditor($type_id, $object_id) {
		
		$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
		$arr_discussion = $storage->getDiscussion();
		
		$return = '<div class="options">'.cms_general::editBody($arr_discussion['object_discussion_body'], 'object_discussion_body').'</div>';
	
		return $return;
	}
		
	private function createSelectSources($type, $type_id, $object_id, $arr_options = []) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_types = StoreType::getTypes();
		$arr_project_source_types = [];
		foreach ($arr_types as $cur_type_id => $value) {
			if ($arr_project['source_types'][$cur_type_id]) {
				$arr_project_source_types[$cur_type_id] = $value;
			}
		}
		
		$arr_source_types = [];
		foreach ((array)$arr_options[$type.'_sources'] as $ref_type_id => $arr_source_objects) {
			
			if (!$arr_source_objects) {
				continue;
			}
			
			$arr_source_types[($arr_project_source_types[$ref_type_id] ? 'editable' : 'disabled')][$ref_type_id] = $arr_source_objects;
		}

		$return = '<fieldset class="entry sources"><ul>
			<li>
				<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
			</li>
			<li><label>'.getLabel('lbl_reference').'</label>';
				$arr_sorter = [];
				foreach (($arr_source_types['editable'] ?: [[]]) as $ref_type_id => $arr_source_objects) {
					
					$arr_sorter_select = [];
					$unique = uniqid('array_');
			
					$arr_type_object_names = [];
					if ($ref_type_id && $arr_source_objects) {
						$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($ref_type_id, arrValuesRecursive($type.'_source_ref_object_id', $arr_source_objects));
					}
					
					foreach (($arr_source_objects ?: [[]]) as $arr_source_object) {
						
						$unique_object = uniqid('array_');
						
						$arr_sorter_select[] = ['value' => 
							'<input type="hidden" id="y:data_filter:lookup_type_object_pick-0" name="'.$type.'_sources['.$unique.'][objects]['.$unique_object.']['.$type.'_source_ref_object_id]" value="'.$arr_source_object[$type.'_source_ref_object_id'].'" />'
							.'<input type="search" id="y:data_filter:lookup_type_object-0" class="autocomplete" value="'.$arr_type_object_names[$arr_source_object[$type.'_source_ref_object_id']].'" />'
							.'<input type="text" name="'.$type.'_sources['.$unique.'][objects]['.$unique_object.']['.$type.'_source_link]" value="'.htmlspecialchars($arr_source_object[$type.'_source_link']).'" />'
						];
					}
					
					$arr_sorter[] = ['value' => [
							'<select name="'.$type.'_sources['.$unique.']['.$type.'_source_ref_type_id]" class="update_object_type">'.Labels::parseTextVariables(cms_general::createDropdown($arr_project_source_types, $ref_type_id, true)).'</select>',
							'<fieldset><ul>
								<li>
									<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
								</li>
								<li><label></label>
									'.cms_general::createSorter($arr_sorter_select, false)
								.'</li>
							</ul></fieldset>'
						]
					];
				}
				$return .= cms_general::createSorter($arr_sorter, false).'
			</li>
		</ul></fieldset>';
		
		if ($arr_source_types['disabled']) {
			
			$return .= '<div class="fieldsets"><div>';
			
				foreach ($arr_source_types['disabled'] as $ref_type_id => $arr_source_objects) {
				
					$arr_type_object_names = [];
					if ($ref_type_id && $arr_source_objects) {
						$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($ref_type_id, arrValuesRecursive($type.'_source_ref_object_id', $arr_source_objects));
					}
					
					$return .= '<fieldset><legend>'.getLabel('lbl_reference').'</legend>
						<ul><li>
							<label>'.$arr_types[$ref_type_id]['name'].'</label>
							<ul>';
								foreach ($arr_source_objects as $arr_source_object) {
									$return .= '<li>'.$arr_type_object_names[$arr_source_object[$type.'_source_ref_object_id']].($arr_source_object[$type.'_source_link'] ? ' - '.$arr_source_object[$type.'_source_link'] : '').'</li>';
								}		
							$return .= '</ul>
						</li></ul>
					</fieldset>';
				}
			$return .= '</div></div>';
		}
		
		return $return;
	}
	
	private function createSelectHistory($type, $type_id, $object_id, $arr_options = []) {

		$return = '<fieldset class="entry history"><legend>'.getLabel('lbl_history').'</legend><ul>
			<li><label>'.getLabel('lbl_value').'</label>';
				
				if ($object_id) {
					$arr_type_set = StoreType::getTypeSet($type_id);
					$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
					
					$arr_versions = [];
					
					if ($type == 'object' && $object_id) {
						
						$arr_version_users = $storage->getTypeObjectVersionsUsers();
						
						foreach ($storage->getTypeObjectVersions() as $version => $value) {
							
							$str_users = ($arr_version_users[$version] ? implode(', ', arrValuesRecursive('name', $arr_version_users[$version])) : '');
							$str = '<span>'.($value['object_active'] ? '<strong>' : '').($value['object_name_plain'] ?: getLabel('lbl_none')).($value['object_active'] ? '</strong>' : '').'</span>'
								.($str_users ? '<span>['.$str_users.']</span>' : '');
							
							$arr_versions[] = ['id' => $value['object_version'], 'name' => $str];
						}
					} else if ($type == 'object_definition' && $object_id) {
						
						$arr_version_users = $storage->getTypeObjectDescriptionVersionsUsers($arr_options['object_description_id']);
						
						foreach ($storage->getTypeObjectDescriptionVersions($arr_options['object_description_id'], true) as $version => $value) {
							
							$str_users = ($arr_version_users[$version] ? implode(', ', arrValuesRecursive('name', $arr_version_users[$version])) : '');
							$str = ($arr_type_set['object_descriptions'][$arr_options['object_description_id']]['object_description_has_multi'] ? implode(', ', $value['object_definition_value']) : StoreTypeObjects::formatToCleanValue($arr_type_set['object_descriptions'][$arr_options['object_description_id']]['object_description_value_type'], $value['object_definition_value'], $arr_type_set['object_descriptions'][$arr_options['object_description_id']]['object_description_value_type_options']));
							$str = '<span>'.($value['object_definition_active'] ? '<strong>' : '').($str ?: getLabel('lbl_none')).($value['object_definition_active'] ? '</strong>' : '').'</span>'
								.($str_users ? '<span>['.$str_users.']</span>' : '');
							
							$arr_versions[] = ['id' => $value['object_definition_version'], 'name' => $str];
						}
					} else if ($type == 'object_sub' && $arr_options['object_sub_id']) {
						
						$arr_version_users = $storage->getTypeObjectSubVersionsUsers($arr_options['object_sub_id']);
						
						foreach ($storage->getTypeObjectSubVersions($arr_options['object_sub_id'], true) as $version => $value) {
							
							$str_users = ($arr_version_users[$version] ? implode(', ', arrValuesRecursive('name', $arr_version_users[$version])) : '');
							$str = '<span>'.($value['object_sub_active'] ? '<strong>' : '').($value['object_sub_date_start'] == $value['object_sub_date_end'] ? StoreTypeObjects::formatToCleanValue('date', $value['object_sub_date_start']) : StoreTypeObjects::formatToCleanValue('date', $value['object_sub_date_start']).' - '.StoreTypeObjects::formatToCleanValue('date', $value['object_sub_date_end'])).($value['object_sub_active'] ? '</strong>' : '').'</span>'
								.'<span>'.($value['object_sub_active'] ? '<strong>' : '').($value['object_sub_location_ref_object_id'] ? $value['object_sub_location_ref_object_name'] : StoreTypeObjects::formatToGeometrySummary($value['object_sub_location_geometry'])).($value['object_sub_active'] ? '</strong>' : '').'</span>'
								.($str_users ? '<span>['.$str_users.']</span>' : '');
							
							$arr_versions[] = ['id' => $value['object_sub_version'], 'name' => $str];
						}
					} else if ($type == 'object_sub_definition' && $arr_options['object_sub_id']) {
						
						$arr_version_users = $storage->getTypeObjectSubDescriptionVersionsUsers($arr_options['object_sub_id'], $arr_options['object_sub_description_id']);
						
						foreach ($storage->getTypeObjectSubDescriptionVersions($arr_options['object_sub_details_id'], $arr_options['object_sub_id'], $arr_options['object_sub_description_id'], true) as $version => $value) {
							
							$str_users = ($arr_version_users[$version] ? implode(', ', arrValuesRecursive('name', $arr_version_users[$version])) : '');
							$str = StoreTypeObjects::formatToCleanValue($arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']]['object_sub_description_value_type'], $value['object_sub_definition_value'], $arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']]['object_sub_description_value_type_options']);
							$str = '<span>'.($value['object_sub_definition_active'] ? '<strong>' : '').($str ?: getLabel('lbl_none')).($value['object_sub_definition_active'] ? '</strong>' : '').'</span>'
								.($str_users ? '<span>['.$str_users.']</span>' : '');
								
							$arr_versions[] = ['id' => $value['object_sub_definition_version'], 'name' => $str];
						}
					}
					$return .= cms_general::createSelectorRadioList($arr_versions, 'version');
				} else {
					$return .= '<div>'.getLabel('lbl_none').'</div>';
				}
			$return .= '</li>
		</ul></fieldset>';
		
		return $return;
	}
	
	private function createSelectVersioning($type, $type_id, $object_id, $arr_options = [], $tabs = []) {

		$return = '<div class="tabs versioning">
			<ul>
				'.($tabs['source_referencing'] ? '<li><a href="#">'.getLabel('lbl_source').'</a></li>' : '').'
				'.($tabs['history'] ? '<li><a href="#">'.getLabel('lbl_history').'</a></li>' : '').'
			</ul>';
						
			if ($tabs['source_referencing']) {
				
				$return .= '<div>
					<div class="options">
						'.$this->createSelectSources($type, $type_id, $object_id, $arr_options).'
					</div>
				</div>';
			}		
					
			if ($tabs['history']) {
				
				$return .= '<div>
					<div class="options">
						'.$this->createSelectHistory($type, $type_id, $object_id, $arr_options).'
					</div>
				</div>';
			}
			
			$return .= '<input type="hidden" name="type" value="'.$type.'" />
		</div>';
		
		return $return;
	}
	
	private static function createChangesTypeObject($type_id, $object_id, $arr_object, $arr_object_changes) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		if ($arr_object_changes['object']['object_locked']) {
			
			$return_locked = '<h1>'.Response::addParsePost(getLabel('lbl_locked'), ['case' => 'upper']).'</h1>';
		}
		
		if ($arr_object_changes['object']['object_dating']) {
			
			$return_dating = '<h1>'.getLabel('lbl_changed').':</h1><dl><li><dt></dt><dd>'.date('d-m-Y H:i', strtotime($arr_object_changes['object']['object_dating'])).'</dd></li></dl>';
		}
		
		if ($arr_object_changes['object']['object_version'] == 'added' || $arr_object_changes['object']['object_version'] == 'deleted') {
			
			$return_version = '<li>'.getLabel('lbl_'.$arr_object_changes['object']['object_version']).'</li>';
		} else {
				
			if ($arr_object['object']['object_name_plain'] != $arr_object_changes['object']['object_name_plain']) {
				$return_changes .= '<li><dt>'.getLabel('lbl_name').'</dt><dd>'.htmlspecialchars($arr_object_changes['object']['object_name_plain']).'</dd></li>';
			}
			
			if ($arr_object['object']['object_changes']) {
				$return_changes .= '<li>
					<dt>'.getLabel('lbl_changed').' '.getLabel('lbl_object_descriptions').'</dt>
					<dd>'.$arr_object['object']['object_changes'].'x</dd>
				</li>';
			}
			
			if ($arr_object['object']['object_sub_changes']) {
				$return_changes .= '<li>
					<dt>'.getLabel('lbl_changed').' '.getLabel('lbl_object_subs').'</dt>
					<dd>'.$arr_object['object']['object_sub_changes'].'x</dd>
				</li>';
			}
					
			if ($return_changes) {
				$return_version .= $return_changes;
			}
		}
		
		if (!$return_locked && !$return_version && !$return_dating) {
			
			return [];
		}
		
		if ($return_locked) {
			$return = ['title' => $return_locked.$return_dating, 'html' => '<span class="icon">'.getIcon('locked').'</span>'];
		} else if ($return_version) {
			$return = '<dl>'.$return_version.'</dl>'.$return_dating;
			$return = htmlspecialchars($return);
			$return = ['html' => '<input type="button" class="data add quick accept_version" value=" " title="<h1>'.Response::addParsePost(getLabel('lbl_accept'), ['case' => 'upper']).' '.getLabel('lbl_version').':</h1>'.$return.'" /><input type="button" class="data del quick discard_version" value=" " title="<h1>'.Response::addParsePost(getLabel('lbl_discard'), ['case' => 'upper']).' '.getLabel('lbl_version').':</h1>'.$return.'" />'];
		} else {
			$return = ['title' => $return_dating];
		}
		
		return $return;
	}
	
	public function createMergeTypeObjects($type_id, $arr_filter) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['object_sub_details_date' => false, 'object_sub_details_location' => false]);
		
		$arr_selection = $_SESSION['data_entry']['merge'][$type_id];
		$arr_append = [];
		
		// No futher user or project clearance restrictions because merge requires full objects
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$is_appendable = ($arr_object_description['object_description_has_multi'] || StoreTypeObjects::appendToValue($arr_object_description['object_description_value_type'], 'a', 'b')[0] == 'a');
			
			if (!$is_appendable) {
				continue;
			}
			
			$str_id = 'object_description_'.$object_description_id;
			$arr_append[$str_id] = $arr_type_set_flat[$str_id];
		}
				
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			if (!$arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) {
				continue;
			}
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				$is_appendable = ($arr_object_sub_description['object_sub_description_has_multi'] || StoreTypeObjects::appendToValue($arr_object_sub_description['object_sub_description_value_type'], 'a', 'b')[0] == 'a');
			
				if (!$is_appendable) {
					continue;
				}
				
				$str_id = 'object_sub_description_'.$object_sub_description_id;
				$arr_append[$str_id] = $arr_type_set_flat[$str_id];
			}
		}
		
		$arr_sorter = [];
		foreach (($arr_selection ?: [0]) as $value) {
			$arr_sorter[] = ['value' => [
					'<select name="append[]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_append, $value, true)).'</select>'
				]
			];
		}
		
		$arr_feedback = [$type_id => $arr_filter] + (SiteStartVars::getFeedback('data_view_filter') ?: []);
		SiteEndVars::setFeedback('data_view_filter', $arr_feedback, true);
		
		$return = '<div class="merge">
			<section class="info attention">
				'.getLabel('conf_object_merge').'
			</section>
			<div class="options">
				<fieldset><ul>
					<li>
						<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
					</li>
					<li>
						<label>'.getLabel('lbl_append').'</label>'.cms_general::createSorter($arr_sorter, true).'
					</li>
				</ul></fieldset>
			</div>
			'.data_view::createViewTypeObjects($type_id, ['session' => true, 'select' => true]).'
		</div>';
				
		return $return;
	}
	
	public function createFindChangeTypeObjectValues($type_id, $arr_filter) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id, ['purpose' => 'select']);
		
		$arr_feedback = [$type_id => $arr_filter] + (SiteStartVars::getFeedback('data_view_filter') ?: []);
		SiteEndVars::setFeedback('data_view_filter', $arr_feedback, true);

		$arr_find_change = ($_SESSION['data_entry']['find_change'][$type_id] ?: []);
		
		$return = '<div class="find_change">
			<section class="info attention">
				'.getLabel('conf_object_find_change').'
			</section>
			<div class="options">
				<fieldset><ul>
					<li>
						<label>'.getLabel('lbl_filtering_disable').'</label>
						<span><input type="checkbox" name="find_change[target_full]" value="1" title="'.getLabel('inf_find_change_disable_filtering').'"'.($arr_find_change['target_full'] ? ' checked="checked"' : '').' /></span>
					</li>
					<li>
						<label></label>
						<span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
					</li>
					<li><label>'.getLabel('lbl_change').'</label>
						<div>';
							
							foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
								
								if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
									
									$str_id = 'object_description_'.$object_description_id;
									unset($arr_type_set_flat[$str_id]);
								}
							}
									
							foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
								
								if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
									
									$str_id = 'object_sub_details_'.$object_sub_details_id.'_';
									
									foreach ($arr_type_set_flat as $key => $value) {
										if (strpos($key, $str_id) !== false) {
											unset($arr_type_set_flat[$key]);
										}
									}
								}
								
								foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
									
									if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
										
										$str_id = 'object_sub_description_'.$object_sub_description_id;
										unset($arr_type_set_flat[$str_id]);
									}
								}
							}
							
							$arr_sorter = [];
							
							$arr_selection = $arr_find_change['selection'];
							
							if (!$arr_selection) {
								$arr_selection[] = [];
							}
							array_unshift($arr_selection, []); // Empty run for sorter source
					
							foreach ($arr_selection as $arr_selected) {
								
								$unique = uniqid('array_');

								$arr_find_change_value = $this->createTypeObjectValue($type_id, (string)$arr_selected['id'], (array)$arr_selected['values']);
								
								$arr_sorter[] = ['value' => [
										'<select id="y:data_entry:select_find_change_id-'.$type_id.'" name="find_change[selection]['.$unique.'][id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_set_flat, $arr_selected['id'], true)).'</select>'
										.'<input type="hidden" name="find_change[selection]['.$unique.'][values]" value="" />'
										.'<input type="radio" name="find_change[selection]['.$unique.'][action]" value="change" title="'.getLabel('lbl_change').'"'.(($arr_selected['action'] == 'change' || !$arr_selected['action']) ? ' checked="checked"' : '').(!$arr_find_change_value['change'] ? ' class="hide"' : '').' />'
										.'<input type="radio" name="find_change[selection]['.$unique.'][action]" value="append" title="'.getLabel('lbl_append').'"'.($arr_selected['action'] == 'append' ? ' checked="checked"' : '').(!$arr_find_change_value['append'] ? ' class="hide"' : '').' />'
										.'<input type="radio" name="find_change[selection]['.$unique.'][action]" value="add" title="'.getLabel('lbl_add').'"'.($arr_selected['action'] == 'add' ? ' checked="checked"' : '').(!$arr_find_change_value['add'] ? ' class="hide"' : '').' />'
										.'<input type="radio" name="find_change[selection]['.$unique.'][action]" value="remove" title="'.getLabel('lbl_remove').'"'.($arr_selected['action'] == 'remove' ? ' checked="checked"' : '').' />',
										'<fieldset>'.$arr_find_change_value['html'].'</fieldset>'
									]
								];
							}
							
							$arr_sorter[0]['source'] = true;

							$return .= cms_general::createSorter($arr_sorter, true);
							
						$return .= '</div>
					</li>
				</ul></fieldset>
			</div>
			'.data_view::createViewTypeObjects($type_id, ['session' => true]).'
		</div>';
			
		return $return;
	}
	
	private function createTypeObjectValue($type_id, $id, $arr_values, $arr_options = []) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$unique = uniqid('array_');
		$this->form_name = ($arr_options['name'] ? $arr_options['name'] : 'find_change['.$unique.']');
		$form_name = $this->form_name;
		
		$change = true;
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$str_id = 'object_description_'.$object_description_id;
			
			if ($str_id == $id && $_SESSION['NODEGOAT_CLEARANCE'] >= $arr_object_description['object_description_clearance_view'] && custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
				
				$str_name = htmlspecialchars(Labels::parseTextVariables($arr_object_description['object_description_name']));
				
				if ($arr_object_description['object_description_ref_type_id']) {
					
					$arr_type_object_names = ($arr_values['object_definition_ref_object_id'] ? FilterTypeObjects::getTypeObjectNames($arr_object_description['object_description_ref_type_id'], $arr_values['object_definition_ref_object_id']) : []);
					
					if ($arr_object_description['object_description_has_multi']) {
						$return_value = '<div class="input">'.cms_general::createMultiSelect($form_name.'[object_definition_ref_object_id]', 'y:data_filter:lookup_type_object-'.$arr_object_description['object_description_ref_type_id'], $arr_type_object_names, false, ['list' => true]).'</div>';
						$append = true;
					} else {
						$return_value = '<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$arr_object_description['object_description_ref_type_id'].'" name="'.$form_name.'[object_definition_ref_object_id]" value="'.$arr_values['object_definition_ref_object_id'].'" /><input type="search" id="y:data_filter:lookup_type_object-'.$arr_object_description['object_description_ref_type_id'].'" class="autocomplete" value="'.$arr_type_object_names[$arr_values['object_definition_ref_object_id']].'" />';
					}
				} else {
					
					if ($arr_object_description['object_description_value_type'] == 'text' || $arr_object_description['object_description_value_type'] == 'text_layout' || $arr_object_description['object_description_value_type'] == 'text_tags') {
						$arr_object_description['object_description_value_type'] = false;
						$append = true;
					}
					
					if ($arr_object_description['object_description_has_multi']) {
						$arr_values['object_definition_value'] = ($arr_values['object_definition_value'] ?: []);
						$append = true;
					}
					
					$return_value = StoreTypeObjects::formatToFormValue($arr_object_description['object_description_value_type'], StoreTypeObjects::formatToSQLValue($arr_object_description['object_description_value_type'], $arr_values['object_definition_value']), $form_name.'[object_definition_value]', $arr_object_description['object_description_value_type_options']);
				}
				
				if ($_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing']) {
					
					$return_value .= '<input type="hidden" value="'.($arr_values['object_definition_sources'] ? htmlspecialchars(json_encode($arr_values['object_definition_sources'])) : '').'" name="'.$form_name.'[object_definition_sources]" />'
						.'<button type="button" id="y:data_entry:select_version-'.$type_id.'_0_'.$object_description_id.'" class="data popup neutral" value="version"><span>'.getLabel('lbl_version_abbr').'</span></button>';
				}
													
				$return = '<div class="entry object-various">
					<fieldset><ul>
						<li>
							<label><span>'.$str_name.'</label>
							<div>'.$return_value.'</div>
						</li>
					</ul></fieldset>
				</div>';
				
				break;
			}					
		}
		
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
				continue;
			}
			
			$str_id = 'object_sub_details_'.$object_sub_details_id.'_';
			
			if (strpos($id, $str_id) !== false) {
				
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
				if ($str_id == $id) {
					
					if ($arr_values) {
						$arr_object_sub = self::formatTypeObjectInput($type_id, self::parseTypeObjectInput($type_id, false, ['object_sub' => [$arr_values]]));
						$arr_object_sub = reset($arr_object_sub['object_subs']);
					} else {
						$arr_object_sub =  ['object_sub' => []];
					}

					$return = '<div class="entry object-subs">'.$this->createTypeObjectSub($type_id, $object_sub_details_id, 0, $arr_object_sub, $arr_object_sub, ['button_delete' => false]).'</div>';
					
					$change = false;
					$add = true;
				}
				
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_date_start';
				if ($str_id == $id) {
					$str_name = getLabel('lbl_date_start');
					$return_value = StoreTypeObjects::formatToFormValue('date', StoreTypeObjects::formatToSQLValue('date', $arr_values['object_sub_date_start']), $form_name.'[object_sub_date_start]');
				}
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_date_end';
				if ($str_id == $id) {
					$str_name = getLabel('lbl_date_end');
					$return_value = StoreTypeObjects::formatToFormValue('date', StoreTypeObjects::formatToSQLValue('date', $arr_values['object_sub_date_end']), $form_name.'[object_sub_date_end]');
				}
				$str_id = 'object_sub_details_'.$object_sub_details_id.'_location';
				if ($str_id == $id) {
									
					if ($arr_values['object_sub_location_ref_type_id'] && $arr_values['object_sub_location_ref_object_id']) {
						
						$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($arr_values['object_sub_location_ref_type_id'], [$arr_values['object_sub_location_ref_object_id']]);
						$arr_values['object_sub_location_ref_object_name'] = $arr_type_object_names[$arr_values['object_sub_location_ref_object_id']];
					}
					
					$arr_html_fields = $this->createTypeObjectSubFields($type_id, $object_sub_details_id, 0, $arr_values, ['location_reference' => true, 'location_geometry' => true]);
					
					$return = '<div class="entry object-various"><fieldset>
						<ul>';
							if ($arr_html_fields['location_type']) {
								$return .= '<li class="location-section start">
									<label>'.getLabel('lbl_location').'</label>
									'.$arr_html_fields['location_type'].'
								</li>
								<li class="location-section">
									<label>'.getLabel('lbl_location').' '.getLabel('lbl_latitude').'</label>
									'.$arr_html_fields['location_latitude'].'
								</li>
								<li class="location-section">
									<label>'.getLabel('lbl_location').' '.getLabel('lbl_longitude').'</label>
									'.$arr_html_fields['location_longitude'].'
								</li>
								<li class="location-section">
									<label></label>
									<span>'.$arr_html_fields['location_map'].'</span>
								</li>
								<li class="location-section">
									<label>'.getLabel('lbl_location').' '.getLabel('lbl_geometry').'</label>
									'.$arr_html_fields['location_geometry'].'
								</li>
								<li class="location-section">
									<label></label>
									<span>'.$arr_html_fields['location_create'].'</span>
								</li>';
							}
							$return .= '<li class="location-section">
								<label>'.getLabel('lbl_location_reference').'</label>
								<div>'.$arr_html_fields['location_ref_type'].$arr_html_fields['location_ref_object_sub_details'].'</div>
							</li>
							<li class="location-section">
								<label></label>
								<div>'.$arr_html_fields['location_object'].'</div>
							</li>
						</ul>
					</fieldset></div>';
				}	
				
				if ($return_value) {
					
					$return = '<div class="entry object-various">
						<fieldset><ul>
							<li>
								<label><span>'.$str_name.'</label>
								<div>'.$return_value.'</div>
							</li>
						</ul></fieldset>
					</div>';
				}
				
				break;
			}
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				$str_id = 'object_sub_description_'.$object_sub_description_id;
				
				if ($str_id == $id && $_SESSION['NODEGOAT_CLEARANCE'] >= $arr_object_sub_description['object_sub_description_clearance_view'] && custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
					
					$str_name = htmlspecialchars(Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']));
					
					if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
						
						$arr_type_object_names = ($arr_values['object_sub_definition_ref_object_id'] ? FilterTypeObjects::getTypeObjectNames($arr_object_sub_description['object_sub_description_ref_type_id'], $arr_values['object_sub_definition_ref_object_id']) : []);
						
						$return_value = '<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$arr_object_sub_description['object_sub_description_ref_type_id'].'" name="'.$form_name.'[object_sub_definition_ref_object_id]" value="'.$arr_values['object_sub_definition_ref_object_id'].'" /><input type="search" id="y:data_filter:lookup_type_object-'.$arr_object_sub_description['object_sub_description_ref_type_id'].'" class="autocomplete" value="'.$arr_type_object_names[$arr_values['object_sub_definition_ref_object_id']].'" />';
					} else {
						
						if ($arr_object_sub_description['object_sub_description_value_type'] == 'text' || $arr_object_sub_description['object_sub_description_value_type'] == 'text_layout' || $arr_object_sub_description['object_sub_description_value_type'] == 'text_tags') {
							$arr_object_sub_description['object_sub_description_value_type'] = false;
							$append = true;
						}
						
						$return_value = StoreTypeObjects::formatToFormValue($arr_object_sub_description['object_sub_description_value_type'], StoreTypeObjects::formatToSQLValue($arr_object_sub_description['object_sub_description_value_type'], $arr_values['object_sub_definition_value']), $form_name.'[object_sub_definition_value]', $arr_object_sub_description['object_sub_description_value_type_options']);
					}
					
					if ($_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing']) {
						
						$return_value .= '<input type="hidden" value="'.($arr_values['object_sub_definition_sources'] ? htmlspecialchars(json_encode($arr_values['object_sub_definition_sources'])) : '').'" name="'.$form_name.'[object_sub_definition_sources]" />'
							.'<button type="button" id="y:data_entry:select_version-'.$type_id.'_0_'.$object_sub_details_id.'_0_'.$object_sub_description_id.'" class="data popup neutral" value="version"><span>'.getLabel('lbl_version_abbr').'</span></button>';
					}
					
					$return = '<div class="entry object-various">
						<fieldset><ul>
							<li>
								<label><span>'.$str_name.'</label>
								<div>'.$return_value.'</div>
							</li>
						</ul></fieldset>
					</div>';
					
					break;
				}			
			}				
		}
		
		if (!$return) {
						
			$return = '<section class="info attention">'.getLabel('lbl_none').'</section>';
		}
			
		return ['html' => $return, 'change' => $change, 'append' => $append, 'add' => $add];
	}
	
	public static function createSelectTypeObject($arr_type_object_ids = [], $text = '', $string_stored_type_object_group_ids, $input_data) {

		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_types = StoreType::getTypes(array_keys($arr_project['types']));
		
		foreach ($arr_type_object_ids as $type_id => $arr_object_ids) {
			
			$filter = new FilterTypeObjects($type_id, 'name');
			$filter->setFilter(['objects' => $arr_object_ids]);
			$filter->setVersioning('added');
			$arr_objects = $filter->init();
			
			$arr_labels = [];
			
			foreach ($arr_objects as $key => $value) {
								
				$arr_labels[$key] = $value['object']['object_name']; 
			}
			
			$unique = uniqid('array_');
			
			$arr_sorter[] = ['value' => [
					'<select name="type_object_ids['.$unique.'][type_id]" class="update_object_type">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types, $type_id)).'</select>',
					'<fieldset><ul>
						<li><label></label><div>
							'.cms_general::createMultiSelect('type_object_ids['.$unique.'][object_ids]', 'y:data_filter:lookup_type_object-0', $arr_labels, 'y:data_filter:lookup_type_object_pick-0', ['list' => true])
						.'</div></li>
					</ul></fieldset>'
				]
			];
		}
		$return = '<div class="entry select-object options">
			<fieldset><ul>
				<li><label>'.getLabel('lbl_text').'</label><div><input type="text" name="text" data-customised="'.($text ? '1' : '').'" value="'.htmlspecialchars($text).'" /></div></li>
				<li><label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span></li>
				<li><label>'.getLabel('lbl_references').'</label>
					<fieldset><ul>
						<li><label></label>'.cms_general::createSorter($arr_sorter, false).'</li>
					</ul><fieldset>
					<input type="hidden" name="string_stored_type_object_group_ids" value="'.htmlspecialchars($string_stored_type_object_group_ids).'" />
					<input type="hidden" name="input_data" value="'.htmlspecialchars($input_data).'" />
				</li>
			</ul></fieldset>
		</div>';
					
		return $return;
	}
	
	public static function createSelectTypeObjectLabel($text = '', $input_data) {

		$labels = [];
		
		$input_data = preg_replace_callback(
			'/\[object=([0-9_\|]+)\]/si',
			function ($arr_matches) {
				return '<span class="tag" data-ids="'.$arr_matches[1].'">'; 
			},
			$input_data
		);
		
		$input_data = preg_replace('/\[\/object\]/si', '</span>', $input_data);
		
		$arr = [];
		$html = FormatHTML::openHTMLDocument($input_data);
		$spans = $html->getElementsByTagName('span');
		foreach ($spans as $span) {
			$ids = $span->getAttribute('data-ids');
			if ($ids) {
				if ($labels[$ids]) {
					$labels[$ids]['name'] = $labels[$ids]['name'].' '.$span->nodeValue;
				} else {
					$labels[$ids] = ['id' => $ids, 'name' => $span->nodeValue];
				}
			}
		}
		
		$return = '<div class="entry select-object options">
			<fieldset><ul>
				<li><label>'.getLabel('lbl_text').'</label><div><input type="text" name="text" data-customised="'.($text ? '1' : '').'" value="'.htmlspecialchars($text).'" /></div></li>
				<li><label>'.getLabel('lbl_references').'</label><div><select name="selected_label">'.cms_general::createDropdown($labels).'</select></div></li>
			</ul></fieldset>
		</div>';
					
		return $return;
	}
	
		
	public static function css() {
	
		$return = '
			.entry fieldset.object > ul { display: block; }
			.entry fieldset.object > ul > li > label:first-child + div > input[name=object_name].default { width: 450px; }
			.entry fieldset.object > ul > li > label:first-child + div.definition > input[type=text].default,
			.entry fieldset.object > ul > li > label:first-child + div.definition > fieldset ul.sorter > li > div > input[type=text].default { width: 350px; }
			.entry fieldset.object > ul > li > label:first-child + div.definition > input[type=number],
			.entry fieldset.object > ul > li > label:first-child + div.definition > fieldset ul.sorter > li > div > input[type=number] { width: 150px; }
			.entry fieldset.object > ul > li > label:first-child + div.definition > input.autocomplete { width: 350px; }
			.entry fieldset.object > ul > li > label:first-child + div.definition > textarea { width: 350px; height: 100px; }
			.entry fieldset.object > ul > li > label:first-child + div.definition > textarea.body-content,
			.entry fieldset.object > ul > li > label:first-child + div.definition > .editor-content > .body-content { width: 750px; height: 200px; }
			.entry fieldset > ul > li > label:first-child + div.definition > div.show { display: inline-block; vertical-align: middle; }
			.entry fieldset > ul > li > label:first-child + div.definition > div.show > span + span { margin-left: 4px; }
			.entry fieldset > ul > li > label:first-child + div.definition > div.show > .text_tags { margin: 0px; }
			.entry fieldset > ul > li > label:first-child + div.definition > fieldset { margin-left: 0px; }
			
			.entry.object-subs fieldset.full div.definition textarea,
			.entry.object-subs fieldset.full div.definition textarea.body-content,
			.entry.object-subs fieldset.full div.definition .editor-content .body-content { height: 3em; }
			.entry.object-subs fieldset div.location-section > span,
			.entry.object-subs fieldset li.location-section div > span + span,
			.entry.object-subs fieldset li.location-section div > span + select,
			.entry.object-subs fieldset div.location-section > span + select,
			.entry.object-various fieldset div.location-section > span,
			.entry.object-various fieldset li.location-section div > span + span,
			.entry.object-various fieldset li.location-section div > span + select,
			.entry.object-various fieldset div.location-section > span + select,
			.entry.object-subs fieldset div.date-section > span,
			.entry.object-subs fieldset li.date-section div > span + span,
			.entry.object-subs fieldset li.date-section div > span + select,
			.entry.object-subs fieldset div.date-section > span + select,
			.entry.object-various fieldset div.date-section > span,
			.entry.object-various fieldset li.date-section div > span + span,
			.entry.object-various fieldset li.date-section div > span + select,
			.entry.object-various fieldset div.date-section > span + select { margin-left: 4px; }

			.entry.object-subs fieldset > legend > span > span { vertical-align: middle; }
			.entry.object-subs fieldset.added > legend:before,
			.entry.object-subs fieldset li.added > ul:before { content:"+++"; margin-right: 4px; font-weight: bold; }
			.entry.object-subs fieldset.deleted > legend:before,
			.entry.object-subs fieldset li.deleted > ul:before { content:"---"; margin-right: 4px; font-weight: bold; }
			.entry.object-subs fieldset.deleted .del,
			.entry.object-subs fieldset li.deleted .del { display: none; }
			
			.entry.sources ul.sorter > li > div > input.autocomplete { width: 500px; }
			.entry.sources ul.sorter > li > div > input.autocomplete + input[type=text] { width: 200px; }
			
			.entry.history ul.select label > span > span { display: block; }
			
			.entry select option { max-width: 320px; overflow: hidden; text-overflow: ellipsis; }
			
			.entry.select-object select { max-width: 500px; }
			
			.discussion .discussion-content > .body { background-color: #f5f5f5; padding: 15px; }
			.discussion .editor-content { display: block; }
			.discussion .editor-content > .body-content { width: 100%; min-width: 800px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.data_entry', function(elm_scripter) {
		
			elm_scripter.on('command', '[id^=d\\\:data_entry\\\:data-], [id^=x\\\:data_entry\\\:type_object_id-], [id^=x\\\:data_view\\\:type_object_id-], [id^=y\\\:data_entry\\\:edit_quick-], [id^=y\\\:data_view\\\:view_type_object-]', function() {
				
				COMMANDS.setData(this, {dynamic_filtering: true});
			});
			
			$(document).on('documentloaded', function() {
		
				SCRIPTER.triggerEvent(elm_scripter.find('[id^=y\\\:data_entry\\\:view-0]'), 'commandfinished');
			});
						
			elm_scripter.on('click', '[id=y\\\:data_entry\\\:view-0] [type=button]', function() {
			
				var cur = $(this);
				var elm_target = elm_scripter.find('.dynamic');
				var elm_command = cur.closest('[id=y\\\:data_entry\\\:view-0]');
				
				LOCATION.checkLocked(elm_target[0], function() {
					
					var elm_table = elm_target.find('[id^=d\\\:data_entry\\\:data-]');
					if (elm_table.length) {
						FEEDBACK.stop(elm_table);
					}
					FEEDBACK.stop(elm_command);

					elm_command.children('div').find('.selected').removeClass('selected');
					cur.addClass('selected');
				
					COMMANDS.setID(elm_command, cur[0].dataset.type_id);
					COMMANDS.quickCommand(elm_command, elm_target);
				});
			}).on('commandfinished', '[id=y\\\:data_entry\\\:view-0]', function() {
			
				SCRIPTER.triggerEvent(elm_scripter.find('[id^=d\\\:data_entry\\\:data-]').prev('.options').find('input[type=search]'), 'focus');
			}).on('click', '[id^=d\\\:data_entry\\\:data-] td > .edit', function() {
			
				var cur = $(this);
				var elm_target = elm_scripter.find('.dynamic').find('form').last();
			
				LOCATION.checkLocked(elm_target[0], function() {
				
					cur.quickCommand(elm_target, {html: 'replace'});
				}, false, true);
			}).on('click', '.merge .del + .add, .find_change .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', '.merge .del, .find_change .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			});
			
			// Check for dynamic content when statically loaded (i.e. object editting via URL)
			
			var elm_form = elm_scripter.find('[id^=f\\\:data_entry\\\:insert], [id^=f\\\:data_entry\\\:update]');
			if (elm_form.length) {
				SCRIPTER.runDynamic(elm_form);
			}
			
			// MERGE & FIND CHANGE
			
			elm_scripter.on('command', '[id^=x\\\:data_entry\\\:type_id-] .popup_find_change, [id^=x\\\:data_entry\\\:type_id-] .popup_merge', function(e) {
				var cur = $(this);
				var arr_data = COMMANDS.getData(cur.closest('[id^=d\\\:data_entry\\\:data-]'));
				COMMANDS.setData(cur.parent(), {filter: arr_data});
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_entry\\\:insert-], [id^=f\\\:data_entry\\\:update-], .entry.object-subs, [data-method=insert_quick], [data-method=update_quick], [data-method=return_object_sub_multi], [data-method=return_version], [data-method=return_object], [data-method=find_change]', 'filtering');
		
		SCRIPTER.dynamic('[id^=f\\\:data_entry\\\:insert-], [id^=f\\\:data_entry\\\:update-], .entry.object-subs, [data-method=insert_quick], [data-method=update_quick], [data-method=return_object_sub_multi], [data-method=return_version], [data-method=find_change]', function(elm_scripter) {
			
			var elm_object_subs = elm_scripter.find('.object .object-subs');
			
			if (elm_object_subs.length) {
				new ToolExtras(elm_object_subs, {fullscreen: true, maximize: true});
			}
			
			var elm_lock = elm_scripter.find('[id^=y\\\:data_entry\\\:set_lock-]');
			
			if (elm_lock.length) {
			
				var lock_key = elm_lock.val();
				var locked_discussion = false;

				var interval = setInterval(function() {
				
					if (!onStage(elm_lock)) {
						clearInterval(interval);
						return false;
					}
					
					COMMANDS.setData(elm_lock, {lock_key: lock_key, locked_discussion: locked_discussion});
					elm_lock.quickCommand();
				}, 15000);

				var elm_form = elm_lock.closest('form'); // For save, leave and discard
				
				LOCATION.onLeave(elm_form[0], function(callback) {
					
					COMMANDS.setData(elm_lock, {close: true, lock_key: lock_key, locked_discussion: locked_discussion});
					elm_lock.quickCommand(callback);
				}, 'any');
				
				var elm_context = COMMANDS.getContext(elm_lock.parent()); // Provide the instance/element with the key
				COMMANDS.setData(elm_context, {lock_key: lock_key, locked_discussion: false});
				
				elm_lock[0].func_lock_discussion = function() {
				
					locked_discussion = true;
					COMMANDS.setData(elm_context, {lock_key: lock_key, locked_discussion: true});
				};
			}
			
			var elm_scope = elm_scripter.find('.network.type');
			
			if (elm_scope) {
				SCRIPTER.runDynamic(elm_scope);
			}
			
			elm_scripter.on('ajaxloaded scripter', function(e) {
				
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				runElementSelectorFunction(e.detail.elm, '[name*=date_end_infinite]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_data_entry');
				});
			}).on('open', '.object .tabs > div', function(e) {
				
				if (e.target != e.currentTarget) {
					return;
				}
				
				var elm_table = $(this).find('table[id^=d\\\:data_entry\\\:data_object_sub_details-]');

				if (!elm_table.length) {
					return;
				}

				COMMANDS.dataTableContinue(elm_table);
			}).on('editorloaded', function(e) {
				if (e.detail.source.dataset.tag_object) {
					new LabelOption(e.detail.source, {action: 'y:data_entry:select_object-0', tag: 'object', type: 'code', button: 'object', select_previous: true, select_using_content: true});
				}
			}).on('click', '[id^=y\\\:data_entry\\\:add_object_sub]', function() {
				var cur = $(this);
				var object_sub_details_id = cur.attr('id').split('_');
				object_sub_details_id = object_sub_details_id[object_sub_details_id.length-1];
				if (!cur.hasClass('unique') || (cur.hasClass('unique') && !cur.closest('form').find('.dynamic-object-subs [name*=\\\[object_sub\\\]\\\[object_sub_details_id\\\]][value='+object_sub_details_id+']').length)) {
					var elm_target = cur.closest('.object-subs').find('.dynamic-object-subs > div');
					if (cur.is('[id^=y\\\:data_entry\\\:add_object_sub_multi]')) {
						cur.data('target', elm_target).popupCommand({'html': 'prepend'});
					} else {
						cur.quickCommand(elm_target, {'html': 'prepend'});
					}
				}
			}).on('click', '[id^=x\\\:data_entry\\\:object_sub_id-] .edit_object_sub', function() {
				var elm_target = $(this).closest('form').find('.dynamic-object-subs > div');
				
				$(this).quickCommand(function(elm) {
					
					if (!elm) {
						return;
					}
					
					elm.each(function() {
						var cur = $(this);
						var object_sub_id = cur.children('[name$=\"[object_sub_id]\"]').val();

						var elm_check = elm_target.find('[name$=\"[object_sub_id]\"][value='+object_sub_id+']');
						if (elm_check.length) {
							cur = elm_check;
						}
						elm_target.prepend(cur);
					});
					
					SCRIPTER.triggerEvent(elm_target.closest('.tabs').find('li > a').first(), 'click');
				});
			}).on('command', '[id^=x\\\:data_entry\\\:object_sub_id-] .del_object_sub', function() {
				if (!elm_lock.length) {
					return;
				}
				COMMANDS.setData(COMMANDS.getContext(this), {lock_key: lock_key});
			}).on('click', 'fieldset > legend > .del, fieldset > ul > li > ul > li > .del', function() {
				var cur = $(this);
				if (cur.closest('.sorter').length) {
					var target = cur.closest('.sorter > li');
				} else {
					var target = cur.closest('fieldset');
				}
				if (target.find('[name*=object_sub_id]').length) {
					target.addClass('deleted').find('[name*=object_sub_version]').val('deleted');
				} else {
					target.remove();
				}
			}).on('change update_data_entry', '[name*=date_end_infinite]', function() {
				$(this).prev('input').prop('disabled', $(this).is(':checked'));
			}).on('command', '[id^=y\\\:data_entry\\\:select_version-]', function() {
			
				var cur = $(this);
				var func_update_value = function(elm, value) {
				
					if (elm.is('.autocomplete.multi')) { // Multi
						elm.autocomplete('clear');
						for (var key in value[0]) {
							elm.autocomplete('add', value[1][key], value[0][key]);
						}
					} else if (elm.hasClass('autocomplete')) {
						elm.autocomplete('add', value[1], value[0]);
					} else if (elm.next('.autocomplete').length) {
						elm.next().autocomplete('add', value[1], value[0]);
					} else if (elm.is('fieldset')) { // Multi
					
						var elm_sorter = elm.find('.sorter');
						elm_sorter.find('input[type=text], input[type=number]').val('');
						
						for (var key in value[1]) {
							elm_sorter.sorter('addRow', {focus: false});
							elm_sorter.find('input[type=text], input[type=number]').first().val(value[1][key]);
						}
						
						elm_sorter.sorter('clean');
					} else if (elm.is('.editor-content')) {
						var elm_value = elm.find('textarea');
						elm_value.val((value instanceof Object ? value[1] : value));
						SCRIPTER.triggerEvent(elm_value, 'change');
					} else if (elm.is('textarea')) {
						elm.val((value instanceof Object ? value[1] : value));
						SCRIPTER.triggerEvent(elm, 'change');
					} else {
						elm.val((value instanceof Object ? value[1] : value));
						SCRIPTER.triggerEvent(elm, 'change');
					}
				}
				
				var elm_sources = cur.prev('input[type=hidden]');
				var str_sources = false;
				if (elm_sources.length) {
					str_sources = elm_sources.val();
				}
				
				COMMANDS.setData(cur[0], {sources: (str_sources ? JSON.parse(str_sources) : false)});
				
				COMMANDS.setTarget(cur[0], function(data) {
					if (data.version) {
						if (data.type == 'object_sub') {
							if (cur.closest('.sorter').length) {
								var elm_con = cur.closest('.sorter > li');
							} else {
								var elm_con = cur.closest('fieldset');
							}
							for (var key in data.version) {
								var elm_target = elm_con.find('[name*=\"['+key+']\"]');
								if (elm_target.length) {
									func_update_value(elm_target, data.version[key]);
								}
							}
						} else {
							var elm_target = cur.prevAll('input:not([type=hidden]), textarea, .input');
							func_update_value(elm_target, data.version);
						}
					}
					if (elm_sources.length) {
						elm_sources.val(data.sources);
					}
				});
			}).on('click', '.sources .del + .add, .definition .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', '.sources .del, .definition .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			});
		});
				
		SCRIPTER.dynamic('[data-method=return_object]', function(elm_scripter) {
			
			elm_scripter.on('click', '.del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', '.del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('keyup', '[name=text]', function() {
				$(this).attr('data-customised', 1);
			}).on('change', '[id=y\\\:data_filter\\\:lookup_type_object_pick-0]', function() {
				var elm_target = $(this).closest('.select-object').find('[name=text]');
				if (!elm_target.attr('data-customised')) {
					elm_target.val($(this).next('input').val());
				}
			});
		});
		
		// FIND CHANGE
		
		SCRIPTER.dynamic('[data-method=find_change]', function(elm_scripter) {
		
			elm_scripter.on('ajaxloaded scripter', function(e) {
				
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				runElementSelectorFunction(e.detail.elm, '[id^=y\\\:data_entry\\\:select_find_change_id-]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_find_change');
				});
			}).on('change update_find_change', '[id^=y\\\:data_entry\\\:select_find_change_id-]', function(e) {
				var cur = $(this);
				var elm_settings = cur.closest('ul').find('> li > fieldset');
				var elm_action_remove = cur.nextAll('[name$=\"[action]\"][value=remove]:checked');
				if (!elm_action_remove.length) {
					elm_settings.removeClass('hide');
				} else {
					elm_settings.addClass('hide');
				}
				
				if (e.type != 'update_find_change') {
				
					COMMANDS.quickCommand(cur, function(data) {
					
						var elm_targets = cur.nextAll('[name$=\"[action]\"]');
						
						var elm_options_append = elm_targets.filter('[value=append]');
						if (!data.append) {
							elm_options_append.addClass('hide');
						} else {
							elm_options_append.removeClass('hide');
						}
						
						var elm_options_add = elm_targets.filter('[value=add]');
						if (!data.add) {
							elm_options_add.addClass('hide');
						} else {
							elm_options_add.removeClass('hide');
						}
						
						var elm_options_change = elm_targets.filter('[value=change]');
						if (!data.change) {
							elm_options_change.addClass('hide');
						} else {
							elm_options_change.removeClass('hide');
						}
						
						if (!elm_targets.not('.hide').filter(':checked').length) {
							elm_targets.not('.hide').first().prop('checked', true);
						}
						
						var elm_html = $(data.html);
						elm_settings.html(elm_html);
						
						return elm_html;
					});
				}
			}).on('change', '[name$=\"[action]\"]:checked', function(e) {
			
				$(this).prevAll('[id^=y\\\:data_entry\\\:select_find_change_id-]').each(function() {
					SCRIPTER.triggerEvent(this, 'update_find_change');
				});
			}).on('ajaxsubmit', function() {
			
				$(this).find('[id^=y\\\:data_entry\\\:select_find_change_id-]').each(function() {
					
					var cur = $(this);
					var elm_target = cur.next('input[type=hidden]');
					var elm_settings = cur.closest('ul').find('fieldset');
					var elm_values = elm_settings.find('[name]');
					
					if (elm_values.length) {
						var str = JSON.stringify(serializeArrayByName(elm_values));
					} else {
						var str = '';
					}
					
					elm_target.val(str);
				});
			});
		
		});

		// DISCUSSION
		
		SCRIPTER.dynamic('discussion', function(elm_scripter) {
		
			var interval_read = false;
			var elm_command_interval = false;
			
			elm_scripter.on('command', '[id^=y\\\:data_entry\\\:edit_discussion-]', function() {
				
				var cur = $(this);
				var elm_target = cur.closest('.discussion').find('.discussion-content');
				
				var elm_form = cur.closest('form');
				
				if (elm_form.length) {
				
					var elm_lock = elm_form.find('[id^=y\\\:data_entry\\\:set_lock-]');
					COMMANDS.setData(cur, {lock_key: elm_lock.val()});
				}
		
				COMMANDS.setTarget(cur, function(html) {
				
					elm_target.replaceWith(html);
					
					if (elm_lock) {
						elm_lock[0].func_lock_discussion();
					}
				});
			}).on('editorloaded', '.discussion', function(e) {
				var obj_editor = e.detail.editor.edit_content;
				obj_editor.addSeparator();
				var elm_button = obj_editor.addButton({title: 'Add Credentials', id: 'y:data_entry:add_credentials-0'}, '<span>Credentials</span>', function() {
					
					COMMANDS.setTarget(elm_button, function(arr_data) {
						CONTENT.input.setSelectionContent(e.detail.source, {after: \"\\n\"+arr_data.credentials});
						SCRIPTER.triggerEvent(e.detail.source, 'change');
					});
					
					elm_button.quickCommand();
				});
			}).on('open', '.discussion', function() {
			
				var cur = $(this);
				elm_command_interval = cur.find('[id^=y\\\:data_entry\\\:poll_discussion-]');
			
				if (elm_command_interval.length && !interval_read) {

					interval_read = setInterval(function() {
					
						if (!onStage(elm_command_interval) || isHidden(elm_command_interval.parent())) {
							
							clearInterval(interval_read);
							interval_read = false;
							
							return false;
						}
						
						elm_command_interval.quickCommand(function(data) {
						
							elm_command_interval[0].value = data.date_edited;
							
							if (data.discussion === false) {
								return;
							}
							
							var elm_target = elm_command_interval.parent('menu').next();
							
							if (elm_target.length) {
								elm_target.replaceWith(data.discussion);
							}
						});
					}, 5000);
				}
			}).on('closed', '.discussion *', function(e) {
				
				SCRIPTER.triggerEvent($(this).closest('.discussion'), 'open');
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_entry\\\:insert-], [id^=f\\\:data_entry\\\:update-], [data-method=insert_quick], [data-method=update_quick]', 'discussion');
		
		SCRIPTER.dynamic('[id^=f\\\:data_entry\\\:update_discussion]', function(elm_scripter) {
			
			var elm_lock = elm_scripter.find('[id^=y\\\:data_entry\\\:set_discussion_lock-]');
			var lock_key = elm_lock.val();

			var interval_lock = setInterval(function() {
			
				if (!onStage(elm_lock)) {
				
					clearInterval(interval_lock);
					return false;
				}
				
				COMMANDS.setData(elm_lock, {lock_key: lock_key});
				elm_lock.quickCommand();
			}, 5000);

			LOCATION.onLeave(elm_scripter[0], function(callback) {
				
				COMMANDS.setData(elm_lock, {close: true, lock_key: lock_key});
				elm_lock.quickCommand(callback);
			}, 'any');
			
			var elm_context = COMMANDS.getContext(elm_lock.parent()); // Provide the instance/element with the key
			COMMANDS.setData(elm_context, {lock_key: lock_key});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
	
		if ($method == "view") {
			
			if (!custom_projects::checkAccesType($id)) {
				return;
			}
			
			$this->html = $this->createTypeOverview($id);
		}
		
		if ($method == "add") {
			
			if (!custom_projects::checkAccesType($id)) {
				return;
			}
			
			$this->html = $this->createAddEditTypeObject($id);
		}
				
		if ($method == "edit") {
			
			if ($value && $value['dynamic_filtering']) {
				toolbar::enableDynamicFiltering();
			}
			
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}

			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
			$lock_key = $storage->handleLockObject();

			$this->html = $this->createAddEditTypeObject($type_id, $object_id, ['lock_key' => $lock_key]);
		}
		
		if ($method == "add_quick") {
			
			$type_id = (int)(is_array($value) && $value ? $value['type_id'] : $id);
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			$arr_project = $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']];
			
			$this->html = '<form data-method="insert_quick" data-lock="1">
				'.$this->createTypeObject($type_id, false, ['source_referencing' => $arr_project['source_referencing'], 'discussion' => $arr_project['discussion_provide']]).'
				<input type="submit" value="'.getLabel('lbl_save').' '.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])).'" />
			</form>';
		}
		
		if ($method == "edit_quick") {
			
			if ($value && $value['dynamic_filtering']) {
				toolbar::enableDynamicFiltering();
			}
			
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
			$lock_key = $storage->handleLockObject();
			
			$arr_project = $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']];
			
			$this->html = '<form data-method="update_quick" data-lock="1">
				'.$this->createTypeObject($type_id, $object_id, ['source_referencing' => $arr_project['source_referencing'], 'discussion' => $arr_project['discussion_provide'], 'lock_key' => $lock_key]).'
				<input type="submit" value="'.getLabel('lbl_save').' '.htmlspecialchars(Labels::parseTextVariables($arr_type_set['type']['name'])).'" />
			</form>';
		}
				
		if (($method == "insert" || $method == "update" || $method == "update_quick") && $_POST['discard']) {
		
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			if ($object_id) {
				
				$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
				$storage->removeLockObject($value['lock_key']);
				if ($value['locked_discussion']) {
					$storage->removeLockDiscussion($value['lock_key']);
				}
			}
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			$this->html = $this->createAddTypeObject($arr_type_set['type']['id'], $arr_type_set['type']['name']);
			return;
		}
		
		if ($method == "set_lock") {
			
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
			
			if ($value['close']) {
				$storage->removeLockObject($value['lock_key']);
				if ($value['locked_discussion']) {
					$storage->removeLockDiscussion($value['lock_key']);
				}
			} else {
				$storage->handleLockObject($value['lock_key']);
				if ($value['locked_discussion']) {
					$storage->handleLockDiscussion($value['lock_key']);
				}
			}
		}
		
		if ($method == "view_type_object") {
			
			if ($value && $value['dynamic_filtering']) {
				toolbar::enableDynamicFiltering();
			}
		
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
				
			$this->html = data_view::createViewTypeObject($type_id, $object_id);
		}
		
		if ($method == "accept_version" || $method == "discard_version") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER) {
				error(getLabel('msg_not_allowed'));
			}
			
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_object_set = self::getTypeObjectSet($type_id, $object_id, true);
			
			if ($method == 'accept_version') {
				
				if ($arr_object_set['object']['object_version'] == 'deleted') {
					$this->html = getLabel('conf_object_delete');
					$this->confirm = true;
					return;
				}
				
				$method_accept_version = true;
			} else {
				
				if ($arr_object_set['object']['object_version'] == 'added') {
					$this->html = getLabel('conf_object_delete');
					$this->confirm = true;
					return;
				}
				
				$method_discard_version = true;
			}
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ((is_array($method) && ($method['method'] == 'accept_version' || $method['method'] == 'discard_version') && $method['confirmed'])) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER) {
				error(getLabel('msg_not_allowed'));
			}
			
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
						
			if ($method['method'] == 'accept_version') {
				
				$method_accept_version = true;
			} else {
								
				$method_discard_version = true;
			}
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method_accept_version || $method_discard_version) {
						
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
			
			if ($method_accept_version) {
				
				$storage->commit(true);
			} else if ($method_discard_version) {
								
				$storage->discard();
			}
		}
		
		if ($method == "select_version") {
		
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_sources = ($value ? $value['sources'] : []);
			
			if ($arr_id[4]) {
				$return = $this->createSelectVersioning('object_sub_definition', $type_id, $arr_id[1],
					['object_sub_details_id' => $arr_id[2], 'object_sub_id' => $arr_id[3], 'object_sub_description_id' => $arr_id[4], 'object_sub_definition_sources' => $arr_sources],
					['history' => true, 'source_referencing' => $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing']]
				);
			} else if (isset($arr_id[3])) {
				$return = $this->createSelectVersioning('object_sub', $type_id, $arr_id[1],
					['object_sub_details_id' => $arr_id[2], 'object_sub_id' => $arr_id[3], 'object_sub_sources' => $arr_sources],
					['history' => true, 'source_referencing' => $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing']]
				);
			} else if ($arr_id[2]) {
				$return = $this->createSelectVersioning('object_definition', $type_id, $arr_id[1],
					['object_description_id' => $arr_id[2], 'object_definition_sources' => $arr_sources],
					['history' => true, 'source_referencing' => $_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['source_referencing']]
				);
			} else {
				$return = $this->createSelectVersioning('object', $type_id, $arr_id[1],
					[],
					['history' => true]
				);
			}
			
			$this->html = '<form data-method="return_version">'.$return.'</form>';
		}
		
		if ($method == "return_version") {
			
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$version = $_POST['version'];
			$type = $_POST['type'];
			
			$arr_sources_raw = $_POST[$type.'_sources'];
			$arr_source_types = [];
			foreach ((array)$arr_sources_raw as $key => $arr_sources) {
				if ($arr_sources[$type.'_source_ref_type_id'] && $arr_sources['objects']) {
					foreach ($arr_sources['objects'] as $arr_source_object) {
						$arr_source_types[$arr_sources[$type.'_source_ref_type_id']][] = $arr_source_object;
					}
				}
			}
			$str_sources = json_encode($arr_source_types);
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$storage = new StoreTypeObjects($type_id, $arr_id[1], $_SESSION['USER_ID']);
			
			if ($version && $type == 'object') {
				
				$arr = $storage->getTypeObjectVersions();
				$return_version = $arr[$version]['object_name_plain'];
			} else if ($version && $type == 'object_definition') {
				
				$arr_object_description = $arr_type_set['object_descriptions'][$arr_id[2]];
				$arr = $storage->getTypeObjectDescriptionVersions($arr_id[2], true);
				
				if ($arr_object_description['object_description_ref_type_id']) {
					$return_version = [$arr[$version]['object_definition_ref_object_id'], $arr[$version]['object_definition_value']];
				} else {
					$return_version = [($arr_object_description['object_description_has_multi'] ? $arr[$version]['object_definition_value'] : 0), StoreTypeObjects::formatToCleanValue($arr_object_description['object_description_value_type'], $arr[$version]['object_definition_value'], $arr_object_description['object_description_value_type_options'])];
				}
			} else if ($version && $type == 'object_sub') {
				
				$arr = $storage->getTypeObjectSubVersions($arr_id[3], true);
				$arr[$version]['object_sub_date_start'] = StoreTypeObjects::formatToCleanValue('date', $arr[$version]['object_sub_date_start']);
				$arr[$version]['object_sub_date_end'] = StoreTypeObjects::formatToCleanValue('date', $arr[$version]['object_sub_date_end']);
				$arr[$version]['object_sub_location_ref_object_id'] = [$arr[$version]['object_sub_location_ref_object_id'], $arr[$version]['object_sub_location_ref_object_name']];
				$arr_location_geometry = StoreTypeObjects::formatToGeometry($arr[$version]['object_sub_location_geometry']);
				$arr[$version]['object_sub_location_geometry'] = ($arr_location_geometry ? json_encode($arr_location_geometry, JSON_PRETTY_PRINT) : '');
				
				$return_version = $arr[$version];
			} else if ($version && $type == 'object_sub_definition') {
				
				$arr_object_sub_description = $arr_type_set['object_sub_details'][$arr_id[2]]['object_sub_descriptions'][$arr_id[4]];
				$arr = $storage->getTypeObjectSubDescriptionVersions($arr_id[2], $arr_id[3], $arr_id[4], true);
				
				if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
					$return_version = [$arr[$version]['object_sub_definition_ref_object_id'], $arr[$version]['object_sub_definition_value']];
				} else {
					$return_version = [($arr_object_sub_description['object_sub_description_has_multi'] ? $arr[$version]['object_sub_definition_value'] : 0), StoreTypeObjects::formatToCleanValue($arr_object_sub_description['object_sub_description_value_type'], $arr[$version]['object_sub_definition_value'], $arr_object_sub_description['object_sub_description_value_type_options'])];
				}
			}
			
			$this->html = ['type' => $type, 'sources' => $str_sources, 'version' => $return_version];
		}
		
		if ($method == "add_object_sub" || $method == "add_object_sub_multi" || $method == "return_object_sub_multi") {
		
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			$object_sub_details_id = $arr_id[2];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
				return;
			}
			
			if ($object_id) { // Check if a unique sub object already exists

				if ($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_is_unique']) { 
				
					$filter = new FilterTypeObjects($type_id, 'storage');
					$filter->setFilter(['objects' => $object_id]);
					$filter->setSelection(['object' => [], 'object_descriptions' => [], 'object_sub_details' => [$object_sub_details_id => ['object_sub_details' => true]]]);
					$arr_object = current($filter->init());
					
					if ($arr_object['object_subs']) {
						return;
					}
				}
			}
		}
		if ($method == "add_object_sub") {
			
			$arr_object_sub = [];
			
			$this->html = $this->createTypeObjectSub($type_id, $object_sub_details_id, 0, $arr_object_sub, $arr_object_sub);
		}
		if ($method == "add_object_sub_multi") {
			
			$arr_object_sub = [];
													
			$this->html = '<form class="options" data-method="return_object_sub_multi">
				<fieldset><legend>'.getLabel('lbl_predefine_object_subs').'</legend>
					<ul>
						<li><label>'.getLabel('lbl_amount').'</label><div><input type="number" name="amount" value="2" /></div></li>
					</ul>
				</fieldset>
				<div class="entry object-subs">'.$this->createTypeObjectSub($type_id, $object_sub_details_id, 0, $arr_object_sub, $arr_object_sub, ['button_delete' => false]).'</div>				
			</form>';
		}
		if ($method == "return_object_sub_multi") {
			
			$arr_object_subs = $arr_object_subs_changes = [];
			$amount = ($_POST['amount'] > 1 ? $_POST['amount'] : 2);
			
			$arr_object_sub = self::formatTypeObjectInput($type_id, self::parseTypeObjectInput($type_id, false, $_POST));
			$arr_object_sub = reset($arr_object_sub['object_subs']);
			
			for ($i = 0; $i < $amount; $i++) {
				$arr_object_subs[] = $arr_object_sub;
				$arr_object_subs_changes[] = [];
			}
																		
			$this->html = $this->createTypeObjectSub($type_id, $object_sub_details_id, 0, $arr_object_subs, $arr_object_subs_changes, ['multi_source' => $arr_object_sub]);
		}
		
		if ($method == "view_type_object_sub" && $id) {
			
			$data_view = new data_view();
			$data_view->commands($method, $id);
		
			$this->html = $data_view->html;
		}
		if ($method == "edit_object_sub" && $id) {
			
			$arr_collect_object_subs = [];
			
			if (is_array($id)) {
				
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$arr_collect_object_subs[$arr_id[2]][$arr_id[3]] = $arr_id;
				}
			} else {
				
				$arr_id = explode('_', $id);
				$arr_collect_object_subs[$arr_id[2]][$arr_id[3]] = $arr_id;
			}
			
			$type_id = $arr_id[0];
			$object_id = $arr_id[1];

			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');	
			
			$return = '';
			
			foreach ($arr_collect_object_subs as $object_sub_details_id => $arr_ids) {
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
					continue;
				}	
				
				$arr_id = reset($arr_ids);
				$arr_object_set = self::getTypeObjectSet($type_id, ['objects' => $object_id, 'object_subs' => array_keys($arr_ids)], true, $arr_type_set);
				
				$arr_object_subs = (count($arr_object_set['object_subs']) > 1 ? $arr_object_set['object_subs'] : reset($arr_object_set['object_subs'])); // Prepare selection for a single subobject or multiple subobjects
				
				$return .= $this->createTypeObjectSub($type_id, $object_sub_details_id, $object_id, $arr_object_subs);
			}
					
			$this->html = $return;
		}
		
		if ($method == "del_object_sub" && $id) {
			
			$arr_ids = [];
			
			if (is_array($id)) {
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$arr_ids[] = $arr_id;
				}
			} else {
				$arr_id = explode('_', $id);
				$arr_ids[] = $arr_id;
			}

			$arr_id = reset($arr_ids);
			$type_id = $arr_id[0];
			$object_id = $arr_id[1];
			$object_sub_details_id = $arr_id[2];
			$object_sub_id = $arr_id[3];
			$object_sub_object_id = $arr_id[4];

			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$lock_key = $value['lock_key'];
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
				return;
			}	
			
			$has_referenced = false;
			
			if ($object_sub_object_id) {

				if ($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_type_id']) { // Referenced
					
					$has_referenced = true;
					
					$type_id = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_type_id'];
				}
			}
			
			$arr_objects_object_subs = [];
			
			foreach ($arr_ids as $arr_id) {
				
				$object_id = ($has_referenced ? $arr_id[4] : $arr_id[1]);
				$object_sub_id = $arr_id[3];
				
				$arr_objects_object_subs[$object_id][]['object_sub'] = [
					'object_sub_id' => $object_sub_id,
					'object_sub_version' => 'deleted',
				];
			}
			
			foreach ($arr_objects_object_subs as $object_id => $arr_object_subs) {
					
				$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
				
				$storage->handleLockObject(($has_referenced ? false : $lock_key));
				
				$storage->store([], [], $arr_object_subs);
				$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
				
				if ($has_referenced) {
					$storage->removeLockObject();
				}
			}
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "poll_discussion" || $method == "edit_discussion" || $method == "set_discussion_lock" || $method == "update_discussion") {
			
			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id) || !$object_id) {
				return;
			}
		}
		
		if ($method == "poll_discussion") {
			
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
			
			$arr_discussion = $storage->getDiscussion();
			
			if ($arr_discussion && $arr_discussion['object_discussion_date_edited'] > $value) {
				
				$html_discussion = '<div class="body">
					'.parseBody($arr_discussion['object_discussion_body']).'
				</div>';
			} else {
				$html_discussion = false;
			}
			
			$this->html = ['discussion' => $html_discussion, 'date_edited' => $arr_discussion['object_discussion_date_edited']];
		}
		
		if ($method == "edit_discussion") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] <= NODEGOAT_CLEARANCE_DEMO) {
				error(getLabel('msg_not_allowed_demo'));
			}
			
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
			
			if ($value && $value['lock_key']) { // Already in Object editing, use the editing Object's key for locking
				
				$storage->handleLockDiscussion($value['lock_key']);
				
				$html = $this->createDiscussionEditor($type_id, $object_id);
			} else {

				$lock_key = $storage->handleLockDiscussion();
		
				$html = '<form id="f:data_entry:update_discussion-'.$type_id.'_'.(int)$object_id.'" data-lock="1">
					
					'.$this->createDiscussionEditor($type_id, $object_id).'
					
					<menu class="options">'
						.'<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_discussion').'" />'
						.'<input type="submit" name="discard" value="'.getLabel('lbl_cancel').'" />'
						.'<input type="hidden" id="y:data_entry:set_discussion_lock-'.$type_id.'_'.(int)$object_id.'" value="'.$lock_key.'" />'
					.'</menu>
				</form>';
			}
			
			$this->html = $html;
		}
		
		if ($method == "set_discussion_lock") {

			$lock_key = $value['lock_key'];
						
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
		
			if ($value['close']) {
				$storage->removeLockDiscussion($lock_key);
			} else {
				$storage->handleLockDiscussion($lock_key);
			}
		}
		
		if ($method == "update_discussion") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] <= NODEGOAT_CLEARANCE_DEMO) {
				error(getLabel('msg_not_allowed_demo'));
			}
			
			$lock_key = $value['lock_key'];
			
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID']);
	
			if ($_POST['discard']) {
				
				$storage->removeLockDiscussion($lock_key);
				
				$this->html = $this->createDiscussionContent($type_id, $object_id);
				return;
			}
			
			$storage->handleLockDiscussion($lock_key);
			
			$arr_discussion = ['object_discussion_body' => trim($_POST['object_discussion_body'])];
			$storage->handleDiscussion($arr_discussion);
			
			$storage->removeLockDiscussion($lock_key);
			
			$this->msg = true;
			$this->html = $this->createDiscussionContent($type_id, $object_id);
		}
			
		if ($method == "add_credentials") {
			
			$str_credential = '[ '.$_SESSION['CUR_USER'][DB::getTableName('TABLE_USERS')]['name'].' | '.date('d-m-Y H:i').' ]';
			
			$this->html = ['credentials' => $str_credential];
		}
		
		if ($method == "select_object") {

			$arr_type_object_ids = [];
			
			preg_match('/^\[object=([0-9_\|]+)\](.+)\[\/object\]$/si', $value['selected'], $arr_match);
			
			if ($arr_match[1]) {
				
				$string_stored_type_object_group_ids = $arr_match[1];
				$arr_ids = explode('|', $arr_match[1]);
				
				foreach ($arr_ids as $arr_type_object_id) {
					$arr_id = explode('_', $arr_type_object_id);
					$arr_type_object_ids[(int)$arr_id[0]][(int)$arr_id[1]] = (int)$arr_id[1];
				}
				
				$text = $arr_match[2];
			} else {
				
				$arr_type_object_ids[] = [];
				$text = $value['selected'];
			}
			
			if ($value['select_using_content']) {
				$return = '<form data-method="return_object">'.self::createSelectTypeObjectLabel($text, $value['content']).'</form>';
			} else {
				$return = '<form data-method="return_object">'.self::createSelectTypeObject($arr_type_object_ids, $text, $string_stored_type_object_group_ids, $value['content']).'</form>';
			}
			
			$this->html = $return;
		}
		
		if ($method == "return_object") {
			
			if ($_POST['selected_label']) {
				$type_object_ids = $_POST['selected_label'];
			} else {
				
				// Type Object Group IDs of original tag
				if ($_POST['string_stored_type_object_group_ids']) {
					$arr_ids = explode('|', $_POST['string_stored_type_object_group_ids']);
					foreach ($arr_ids as $arr_type_object_id) {
						$arr_id = explode('_', $arr_type_object_id);
						$arr_stored_type_object_group_ids[(int)$arr_id[0]][(int)$arr_id[1]] = (int)$arr_id[2];
					}
				}
				
				// Type Object Group IDs of all data
				$arr_object_definition_type_object_groups = [];
				
				if ($_POST['input_data']) {
					
					preg_replace_callback(
						'/\[object=([0-9_\|]+)\](.*?)\[\/object\]/si',
						function ($arr_matches) use (&$arr_object_definition_type_object_groups) {							
							$arr_ids = explode('|', $arr_matches[1]);
							foreach ($arr_ids as $arr_type_object_id) {
								$arr_id = explode('_', $arr_type_object_id);
								$arr_object_definition_type_object_groups[(int)$arr_id[0]][(int)$arr_id[1]][(int)$arr_id[2]] = $arr_matches[2];
							}	
						},
						$_POST['input_data']
					);
				}

				// Check if existing group or new group and assign group ID
				foreach ((array)$_POST['type_object_ids'] as $arr_type_object_ids) {
					
					foreach ((array)$arr_type_object_ids['object_ids'] as $object_id) {

						if ($arr_stored_type_object_group_ids[$arr_type_object_ids['type_id']][$object_id]) { // Check if edit of existing tag
							$type_object_ids .= ($type_object_ids ? '|' : '').$arr_type_object_ids['type_id'].'_'.($object_id ?:0).'_'.$arr_stored_type_object_group_ids[$arr_type_object_ids['type_id']][$object_id]; 
						} else if ($arr_object_definition_type_object_groups[$arr_type_object_ids['type_id']][$object_id]) { // Check if object exists elsewhere in text
							$highest_group_id = 1;
							foreach ($arr_object_definition_type_object_groups[$arr_type_object_ids['type_id']][$object_id] as $group_id => $text) {
								if ($group_id > $highest_group_id) {
									$highest_group_id = $group_id;
								}
							}
							$group_id = $highest_group_id + 1;
							$type_object_ids .= ($type_object_ids ? '|' : '').$arr_type_object_ids['type_id'].'_'.($object_id ?:0).'_'.$group_id; 
	
						} else { // Object not used before so group is 1
							$type_object_ids .= ($type_object_ids ? '|' : '').$arr_type_object_ids['type_id'].'_'.($object_id ?:0).'_1'; 
						}	
					}
				}
			}
			
			$this->html = '[object='.$type_object_ids.']'.$_POST['text'].'[/object]';
		}
		
		if ($method == "popup_merge") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER) {
				error(getLabel('msg_not_allowed'));
			}
			
			if (is_array($id)) {
				
				$arr_ids = [];
				
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$type_id = $arr_id[0];
					$arr_ids[] = $arr_id[1];
				}
				
				$arr_filter = ['objects' => $arr_ids];
			} else {
				
				$type_id = $id;
				$arr_filter = data_filter::parseUserFilterInput($value['filter']);
			}

			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
												
			$this->html = '<form data-method="merge">
				'.$this->createMergeTypeObjects($type_id, $arr_filter).'
				<input type="submit" value="'.getLabel('lbl_merge').'" />
			</form>';
		}
		
		if ($method == "popup_find_change") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER) {
				error(getLabel('msg_not_allowed'));
			}
						
			if (is_array($id)) {
				
				$arr_ids = [];
				
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$type_id = $arr_id[0];
					$arr_ids[] = $arr_id[1];
				}
				
				$arr_filter = ['objects' => $arr_ids];
			} else {
				
				$type_id = $id;
				$arr_filter = data_filter::parseUserFilterInput($value['filter']);
			}
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
									
			$this->html = '<form data-method="find_change">
				'.$this->createFindChangeTypeObjectValues($type_id, $arr_filter).'
				<input type="submit" value="'.getLabel('lbl_change').'" />
			</form>';
		}
		
		if ($method == "select_find_change_id") {
						
			if (!custom_projects::checkAccesType($id)) {
				return;
			}
			
			$this->html = $this->createTypeObjectValue($id, $value, []);
		}
				
		// INTERACT
				
		if ($method == "data") {
			
			if ($value && $value['dynamic_filtering']) {
				toolbar::enableDynamicFiltering();
			}
			
			$type_id = (int)$id;
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$arr_ref_type_ids = cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id']);
			$arr_type_set = StoreType::getTypeSet($type_id);

			//$filter = new FilterTypeObjects((int)$type_id, "overview");
			$filter = new FilterTypeObjects($type_id, 'overview', true);
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => $arr_ref_type_ids]);

			$arr_selection = ['object' => ['all' => true], 'object_descriptions' => [], 'object_sub_details' => []];
			
			$arr_analyses_active = data_analysis::getTypeAnalysesActive($type_id);
			
			if ($arr_analyses_active) {
				
				$arr_selection['object']['analysis'] = $arr_analyses_active;
			}
			
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if (!$arr_object_description['object_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
					continue;
				}
				
				$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
			}
			
			$filter->setSelection($arr_selection);
			
			if ($_POST['arr_order_column']) {
				
				foreach ($_POST['arr_order_column'] as $nr_order => list($nr_column, $str_direction)) {
					
					if ($nr_column == 0 && $arr_type_set['type']['object_name_in_overview']) { // Object name
						
						$filter->setOrder(['object_name' => $str_direction]);
					} else {
						
						$count_column = ($arr_type_set['type']['object_name_in_overview'] ? 1 : 0);
						
						foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
								
							if (!$arr_selection['object_descriptions'][$object_description_id]) {
								continue;
							}
							
							if ($nr_column == $count_column) {
								$filter->setOrder([$object_description_id => $str_direction]);
							}
							$count_column++;
						}
						
						if ($arr_selection['object']['analysis']) { 
							
							if ($nr_column == $count_column) {
								$filter->setOrder(['object_analysis' => $str_direction]); // Object analysis
							}
							$count_column++;
						}
						
						if ($nr_column == $count_column) {
							$filter->setOrder(['date' => $str_direction]); // Object dating
						}
					}
				}
			} else {
				
				$filter->setOrder(['date' => 'desc']); // Default ordering
			}
			if (isset($_POST['nr_records_start']) && $_POST['nr_records_length'] != '-1') {
				$filter->setLimit([$_POST['nr_records_start'], $_POST['nr_records_length']]);
			}
			
			if ($value && $arr_project['types'][$type_id]['type_filter_id'] && $value['filter_id'] == $arr_project['types'][$type_id]['type_filter_id']) {
				unset($value['filter_id']);
			}
			
			$arr_filter = data_filter::parseUserFilterInput($value);
			
			if ($_POST['search']) {
				$arr_filter['search'] = $_POST['search'];
			}
			if (!$arr_filter['object_versioning'] || $arr_filter['object_versioning']['version']) {
				$filter->setVersioning('full');
			}
			if ($arr_filter) {
				$filter->setFilter($arr_filter);
			}
			
			$arr_filters = $filter->getDepth();
			toolbar::setFilter([(int)$type_id => (array)$arr_filters['arr_filters']], true);
			
			$arr_conditions = toolbar::getTypeConditions($type_id);
			$filter->setConditions('style', $arr_conditions);
						
			if ($arr_project['types'][$type_id]['type_filter_id']) {
			
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
			}
			
			$arr_changes = $filter->init();
			$arr_info = $filter->getResultInfo();
			
			$arr_output = [
				'echo' => intval($_POST['echo']),
				'total_records' => $arr_info['total'],
				'total_records_filtered' => $arr_info['total_filtered'],
				'data' => []
			];
			
			if ($arr_changes) {
				
				$filter = new FilterTypeObjects($type_id, 'overview');
				$filter->setConditions('style', $arr_conditions);
				$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => $arr_ref_type_ids]);
				$filter->setSelection($arr_selection);
				$filter->setFilter(['objects' => array_keys($arr_changes)]);
				$arr_original = $filter->init();
			}
					
			foreach ($arr_changes as $object_id => $arr_object) {
				
				$arr_object_use = ($arr_original[$object_id] ?: $arr_object);
				$count = 0;
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:data_entry:type_object_id-'.$type_id.'_'.$arr_object_use['object']['object_id'];
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view_type_object';
				if ($arr_type_set['type']['object_name_in_overview']) {
					
					$arr_data['cell'][0]['attr']['style'] = $arr_object_use['object']['object_name_style'];
					$arr_data[] = $arr_object_use['object']['object_name'];
					$count++;
				}
				
				foreach ($arr_object_use['object_definitions'] as $object_description_id => $arr_object_definition) {
					
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
									
									$arr_html[] = data_view::createTypeObjectLink($ref_type_id, $cur_object_id, $arr_reference['object_definition_ref_object_name']);
								}
							}
							
							$arr_data[] = implode(', ', $arr_html);
						} else if ($arr_object_description['object_description_has_multi']) {
							
							$arr_html = [];
							
							foreach ($arr_object_definition['object_definition_ref_object_id'] as $key => $value) {
								
								$arr_html[] = data_view::createTypeObjectLink($arr_object_description['object_description_ref_type_id'], $value, $arr_object_definition['object_definition_value'][$key]);
							}
							
							$arr_data[] = implode(', ', $arr_html);
						} else {
							
							$arr_data[] = data_view::createTypeObjectLink($arr_object_description['object_description_ref_type_id'], $arr_object_definition['object_definition_ref_object_id'], $arr_object_definition['object_definition_value']);
						}
					} else {
						
						$str_value = arrParseRecursive($arr_object_definition['object_definition_value'], ['Labels', 'parseLanguage']);
						
						$arr_data[] = StoreTypeObjects::formatToPreviewValue($arr_object_description['object_description_value_type'], $str_value);
					}
					
					$count++;
				}
				if ($arr_selection['object']['analysis']) {
					
					$arr_data['cell'][$count]['attr']['class'] = 'analysis';
					$arr_data[] = $arr_object_use['object']['object_analysis'];
					
					$count++;
				}
				if ($_SESSION['NODEGOAT_CLEARANCE'] != NODEGOAT_CLEARANCE_INTERACT) {
					
					$arr_changes_info = $this->createChangesTypeObject($type_id, $arr_object_use['object']['object_id'], $arr_original[$object_id], $arr_object);
					
					if ($arr_changes_info['title']) {
						$arr_data['cell'][$count]['attr']['title'] = $arr_changes_info['title'];
					}
					$arr_data[] = $arr_changes_info['html'];
					$arr_data[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data msg del" value="del" /><input class="multi" value="'.$type_id.'_'.$arr_object_use['object']['object_id'].'" type="checkbox" />';
				}
				$arr_output['data'][] = $arr_data;
			}
			
			$this->data = $arr_output;
		}
				
		if ($method == "data_object_sub_details") {

			$arr_id = explode('_', $id);
			$type_id = (int)$arr_id[0];
			$object_id = $arr_id[1];
			$object_sub_details_id = $arr_id[2];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$arr_types = StoreType::getTypes(array_keys($arr_project['types']));
			
			$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
			$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
			
			if ($object_sub_details_id != 'all' && ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id))) {
				return;
			}
			
			$filter = new FilterTypeObjects($type_id, 'all', true, $arr_type_set);
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id'])]);
			$filter->setConditions('style', toolbar::getTypeConditions($type_id));
			
			$arr_selection = ['object' => [], 'object_descriptions' => [], 'object_sub_details' => [$object_sub_details_id => ['object_sub_details' => true, 'object_sub_descriptions' => []]]];
			$arr_filter_object_sub_details_ids = [];
			
			if ($object_sub_details_id != 'all') {
		
				foreach ($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					if (!$arr_object_sub_description['object_sub_description_in_overview'] || $_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
						continue;
					}
					
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
				}
				
				$filter->setSelection($arr_selection);
				
				$cur_type_id = $arr_object_sub_details['object_sub_details']['object_sub_details_type_id']; // Cross-referenced sub-object
					
				if ($cur_type_id) {

					if ($arr_project['types'][$cur_type_id]['type_filter_id']) {

						$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
						$arr_filter_form = FilterTypeObjects::convertFilterInput($arr_project_filters['object']);
						
						$arr_filter_referenced_object_sub_details = [];

						$arr_filter_referenced_object_sub_details['object_filter'][]['object_subs'][$object_sub_details_id]['object_sub_referenced'][] = $arr_filter_form;
						
						$filter->setFilter($arr_filter_referenced_object_sub_details, true);
					}
				}
			} else {
				
				$filter->setSelection($arr_selection);
				
				foreach ($arr_type_set['object_sub_details'] as $cur_object_sub_details_id => $arr_cur_object_sub_details) {
					
					if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_cur_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$type_id], $arr_type_set, false, $cur_object_sub_details_id)) {
						continue;
					}
					
					$cur_type_id = $arr_cur_object_sub_details['object_sub_details']['object_sub_details_type_id']; // Cross-referenced sub-object
					
					if ($cur_type_id) {

						if ($arr_project['types'][$cur_type_id]['type_filter_id']) {

							$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
							$arr_filter_form = FilterTypeObjects::convertFilterInput($arr_project_filters['object']);
							
							$arr_filter_referenced_object_sub_details = [];
							
							$arr_filter_referenced_object_sub_details['object_filter'][]['object_subs'][$cur_object_sub_details_id]['object_sub_referenced'][] = $arr_filter_form;
							
							$filter->setFilter($arr_filter_referenced_object_sub_details, true);
						}
					}
					
					$arr_filter_object_sub_details_ids[] = $cur_object_sub_details_id;
				}
			}

			if ($arr_filter_object_sub_details_ids) {
				$filter->setFilter(['objects' => $object_id, 'object_sub_details' => $arr_filter_object_sub_details_ids], true);
			} else {
				$filter->setFilter(['objects' => $object_id]);
			}
			
			if ($value && $arr_project['types'][$type_id]['type_filter_id'] && $value['filter_id'] == $arr_project['types'][$type_id]['type_filter_id']) {
				unset($value['filter_id']);
			}
						
			$arr_filter = data_filter::parseUserFilterInput($value);
			
			if ($_POST['search']) {
				$arr_filter['search'][] = $_POST['search'];
			}
			if (!$arr_filter['object_versioning'] || $arr_filter['object_versioning']['version']) {
				$filter->setVersioning('full');
			}
			if ($arr_filter) {
				$filter->setFilter($arr_filter, true);
			}
			if ($_POST['arr_order_column']) {
				
				foreach ($_POST['arr_order_column'] as $nr_order => list($nr_column, $str_direction)) {
					
					if ($object_sub_details_id == 'all') { // Object sub details combined
						
						$sort_id = ($nr_column == 0 ? 'object_sub_details_name' : ($nr_column == 1 ? 'object_sub_date_start' : ($nr_column == 2 ? 'object_sub_date_end' : '')));
						$filter->setOrderObjectSubs($object_sub_details_id, [$sort_id => $str_direction]);
					} else {
						
						if (!$arr_object_sub_details['object_sub_details']['object_sub_details_has_date']) {
							$nr_column += 2;
						}
						if (!$arr_object_sub_details['object_sub_details']['object_sub_details_has_location']) {
							$nr_column += 2;
						}
						
						if ($nr_column <= 1) {
							$sort_id = ($nr_column == 0 ? 'object_sub_date_start' : ($nr_column == 1 ? 'object_sub_date_end' : ''));
							$filter->setOrderObjectSubs($object_sub_details_id, [$sort_id => $str_direction]);
						} else {
							$count_column = 4;
							foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_sub_object_description) {
								
								if (!$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]) {
									continue;
								}
															
								if ($nr_column == $count_column) {
									$filter->setOrderObjectSubs($object_sub_details_id, [$object_sub_description_id => $str_direction]);
								}
								$count_column++;
							}
						}
					}
				}
			}
			if (isset($_POST['nr_records_start']) && $_POST['nr_records_length'] != '-1') {
				$filter->setLimitObjectSubs($object_sub_details_id, [$_POST['nr_records_start'], $_POST['nr_records_length']]);
			}
			
			if ($arr_project['types'][$type_id]['type_filter_id']) {

				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project['types'][$type_id]['type_filter_object_subs']);
			}

			$arr = $filter->init();
			$arr_info = $filter->getResultInfoObjectSubs($object_id, $object_sub_details_id);

			$arr_output = [
				'echo' => intval($_POST['echo']),
				'total_records' => $arr_info['total'],
				'total_records_filtered' => $arr_info['total_filtered'],
				'data' => []
			];
			
			$arr_object_subs = $arr[$object_id]['object_subs'];

			foreach ((array)$arr_object_subs as $arr_object_sub) {
				
				$count = 0;
				$cur_object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
				$arr_object_sub_details = $arr_type_set['object_sub_details'][$cur_object_sub_details_id]['object_sub_details'];
				$id = $type_id.'_'.$object_id.'_'.$cur_object_sub_details_id.'_'.$arr_object_sub['object_sub']['object_sub_id'].'_'.(int)$arr_object_sub['object_sub']['object_sub_object_id'];
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:data_entry:object_sub_id-'.$id;
				$arr_data['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view_type_object_sub';
				
				$arr_object_sub_values = data_view::formatToTypeObjectSubValues($type_id, $cur_object_sub_details_id, $object_id, $arr_object_sub);
				
				if ($object_sub_details_id == 'all') {
					
					$arr_data[] = ($arr_object_sub_details['object_sub_details_type_id'] ? '<span class="icon" data-category="direction" title="'.getLabel('lbl_referenced').'">'.getIcon('leftright-right').'</span><span>'.Labels::parseTextVariables($arr_types[$arr_object_sub_details['object_sub_details_type_id']]['name']).'</span> ' : '').'<span class="sub-name">'.Labels::parseTextVariables($arr_object_sub_details['object_sub_details_name']).'</span>';
					$count++;
					
					$count += 4;
				} else {
					
					if (!$arr_object_sub_details['object_sub_details_has_date']) {
						unset($arr_object_sub_values['object_sub_date_start'], $arr_object_sub_values['object_sub_date_end']);
					} else {
						$count += 2;
					}
					if (!$arr_object_sub_details['object_sub_details_has_location']) {
						unset($arr_object_sub_values['object_sub_location_reference_label'], $arr_object_sub_values['object_sub_location_reference_value']);
					} else {
						$count += 2;
					}
				}
			
				$arr_data = array_merge($arr_data, array_values($arr_object_sub_values));

				foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
					
					if (!isset($arr_object_sub_values['object_sub_definition_'.$object_sub_description_id])) {
						continue;
					}
					
					if ($arr_object_sub_definition['object_sub_definition_style']) { // Ignore object_sub_definition_style = 'hide'
						
						$arr_data['cell'][$count]['attr']['style'] = $arr_object_sub_definition['object_sub_definition_style'];
					}
					
					$count++;
				}
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $cur_object_sub_details_id)) {
					$arr_data[] = '';
				} else {
					$arr_data[] = '<input type="button" class="data edit edit_object_sub" value="edit" />'.(!($arr_object_sub_details['object_sub_details_is_required'] && $arr_object_sub_details['object_sub_details_is_unique']) ? '<input type="button" class="data del msg del_object_sub" value="del" />' : '').'<input class="multi" value="'.$id.'" type="checkbox" />';
				}
				$count++;
				
				$arr_output['data'][] = $arr_data;
			}
			
			$this->data = $arr_output;
		}
	
		// QUERY
				
		if ($method == "insert" || $method == "insert_quick" || (($method == "update" || $method == "update_quick") && $id)) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_UNDER_REVIEW) {
				error(getLabel('msg_not_allowed'));
			}
			
			if ($method == 'insert_quick') {
				$arr_id = [($id ?: $value['type_id']), 0];
			} else {
				$arr_id = explode('_', $id);
			}
			
			$type_id = $arr_id[0];
			$object_id = (int)$arr_id[1];
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
			
			$lock_key = ($object_id ? $value['lock_key'] : false);
			$locked_discussion = ($lock_key && $value['locked_discussion'] ? true : false);
			
			$storage = new StoreTypeObjects($type_id, $object_id, $_SESSION['USER_ID'], false, $arr_type_set);
			$storage->handleLockObject($lock_key);
			
			$arr_input = $_POST;
			
			if ($locked_discussion) {
				
				$storage->handleLockDiscussion($lock_key);
				
				$arr_discussion = ['object_discussion_body' => trim($arr_input['object_discussion_body'])];
				$storage->handleDiscussion($arr_discussion);
			}
			
			if ($arr_type_set['type']['is_reversal']) {
				
				$arr_object_self = [
					'object_name_plain' => $arr_input['object_name_plain'],
					'object_ref_object_id' => $arr_input['object_ref_object_id']
				];
				
				$arr_object_definitions = [];
				foreach ((array)$arr_input['object_definition'] as $key => $value) {
					$arr_object_definitions[] = [
						'object_description_id' => $key,
						'object_definition_ref_object_id' => $value['object_definition_ref_object_id']
					];
				}
				
				$arr_object_filters = ($arr_input['filter'] ?: []);
				$arr_object_scopes = ($arr_input['scope'] ?: []);
				
				$object_id = $storage->storeReversal($arr_object_self, $arr_object_definitions, $arr_object_filters, $arr_object_scopes);
				$storage->removeLockObject($lock_key);
				if ($locked_discussion) {
					$storage->removeLockDiscussion($lock_key);
				}
			} else {

				$arr = self::parseTypeObjectInput($type_id, $object_id, $arr_input, true);
				
				$object_id = $storage->store($arr['object_self'], $arr['object_definitions'], $arr['object_subs']);
				$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
				$storage->removeLockObject($lock_key);
				if ($locked_discussion) {
					$storage->removeLockDiscussion($lock_key);
				}
				
				if ($arr['referenced_type_objects']) {
					
					$arr_type_storage = [];
					
					// Let's try to update all referenced Objects, if not, rollback all changes.
					
					try {
						
						foreach ($arr['referenced_type_objects'] as $ref_type_id => $arr_referenced_objects) {
							
							$storage = new StoreTypeObjects($ref_type_id, false, $_SESSION['USER_ID'], 'type_'.$ref_type_id);
							
							$arr_type_storage[$ref_type_id] = $storage;
							
							foreach ($arr_referenced_objects as $ref_object_id => $arr_referenced_object) {
								
								$storage->setObjectID($ref_object_id);
							
								$storage->handleLockObject();
							}
						}
						
						DB::startTransaction('data_entry_insert_update');
					
						foreach ($arr['referenced_type_objects'] as $ref_type_id => $arr_referenced_objects) {
							
							$storage = $arr_type_storage[$ref_type_id];
							
							foreach ($arr_referenced_objects as $ref_object_id => $arr_referenced_object) {
								
								$storage->setObjectID($ref_object_id);
								
								if ($arr_referenced_object['object_definitions']) {
									$storage->setAppend(['object_definitions' => array_keys($arr_referenced_object['object_definitions'])]);
								}
								
								$storage->store([], (array)$arr_referenced_object['object_definitions'], (array)$arr_referenced_object['object_subs']);
							}
							
							$storage->save();
							$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
						}
					} catch (Exception $e) {
						
						DB::rollbackTransaction('data_entry_insert_update');
						
						foreach ($arr_type_storage as $storage) {
							$storage->removeLockObject();
						}
					
						throw($e);
					}
					
					DB::commitTransaction('data_entry_insert_update');
					
					foreach ($arr_type_storage as $storage) {
						$storage->removeLockObject();
					}
				}
			}
		}
		
		if ($method == "insert") {
		
			$this->reset_form = true;
			$this->refresh_table = true;
			$this->msg = true;
		}
		if ($method == "update" && $id) {
					
			$this->html = self::createAddTypeObject($type_id, $arr_type_set['type']['name']);
			$this->refresh_table = true;
			$this->msg = true;
		}
		if ($method == "insert_quick") {
			
			$filter = new FilterTypeObjects($type_id, 'name');
			$filter->setFilter(['objects' => $object_id]);
			$filter->setVersioning('added');
			$arr_object = $filter->init();
			$arr_object = current($arr_object);
			
			$str_object_name = $arr_object['object']['object_name'];
			
			$this->html = ['id' => $object_id, 'value' => $str_object_name];
			$this->msg = true;
		}
		if ($method == "update_quick" && $id) {
			
			if ($method == 'update_quick' && $arr_id[2] == 'view') {
				
				$this->html = data_view::createViewTypeObject($type_id, $arr_id[1]);
			}

			$this->msg = true;
		}
		
		if (($method == 'merge' || (is_array($method) && $method['method'] == 'merge' && $method['confirmed'])) && $id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER) {
				error(getLabel('msg_not_allowed'));
			}
			
			if (is_array($id)) {
				
				$arr_ids = [];
				
				foreach ($id as $cur_id) {
					$arr_id = explode('_', $cur_id);
					$type_id = $arr_id[0];
					$arr_ids[] = $arr_id[1];
				}
				
				$arr_filter = ['objects' => $arr_ids];
			} else {
				
				$type_id = $id;
				$arr_filter = data_filter::parseUserFilterInput($value['filter']);
			}
						
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			// No futher user or project clearance restrictions because merge requires full objects
			
			$object_id = ((int)$_POST['type_object_id'] ?: false); // Merge towards an existing object
			$_SESSION['data_entry']['merge'][$type_id] = [];
			$arr_append = [];
			
			if ($_POST['append']) {
				
				$arr_type_set = StoreType::getTypeSet($type_id);
				
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
										
					$str_id = 'object_description_'.$object_description_id;
					
					if (in_array($str_id, $_POST['append'])) {
						$arr_append['object_definitions'][$object_description_id] = true;
						$_SESSION['data_entry']['merge'][$type_id][] = $str_id;
					}
				}
						
				foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					if (!$arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) {
						continue;
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						$str_id = 'object_sub_description_'.$object_sub_description_id;
						if (in_array($str_id, $_POST['append'])) {
							$arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id] = true;
							$_SESSION['data_entry']['merge'][$type_id][] = $str_id;
						}
					}
				}
			}
			
			$filter = new FilterTypeObjects($type_id, 'id');
			$filter->setVersioning();
			$filter->setFilter($arr_filter);
			
			if ($method == 'merge') {
				
				$arr_result = $filter->getResultInfo();
				$this->confirm = true;
				Labels::setVariable('total', $arr_result['total_filtered']);
				$this->html = getLabel('conf_merge');
				return;
			}
			
			$arr_objects = $filter->init();
			$arr_object_ids = array_keys($arr_objects);
			
			if (count($arr_object_ids) <= 1) {
				error(getLabel('msg_missing_information'));
			}

			// Lock objects
			
			$arr_locked = [];
			$arr_storage_lock = [];
			
			$arr_storage_lock[$type_id] = new StoreTypeObjects($type_id, false, $_SESSION['USER_ID'], 'lock_'.$type_id);
			$storage_lock = $arr_storage_lock[$type_id];
			
			foreach ($arr_objects as $cur_object_id => $arr_object) {
				
				$storage_lock->setObjectID($cur_object_id);
				
				try {
					$storage_lock->handleLockObject();
				} catch (Exception $e) {
					$arr_locked[] = $e;
				}
			}
			
			$merge = new MergeTypeObjects($type_id, $object_id, $arr_object_ids, $_SESSION['USER_ID']);
			
			$arr_referenced_type_objects = $merge->getReferencedTypeObjects(true);
			
			foreach ($arr_referenced_type_objects as $cur_type_id => $arr_referenced_objects) {
				
				if ($cur_type_id != $type_id) {
					$arr_storage_lock[$cur_type_id] = new StoreTypeObjects($cur_type_id, false, $_SESSION['USER_ID'], 'lock_'.$cur_type_id);
				}
				$storage_lock = $arr_storage_lock[$cur_type_id];	
				
				foreach ($arr_referenced_objects as $cur_object_id => $arr_object) {
					
					$storage_lock->setObjectID($cur_object_id);
					
					try {
						$storage_lock->handleLockObject();
					} catch (Exception $e) {
						$arr_locked[] = $e;
					}
				}
			}
			
			if ($arr_locked) {
				
				foreach ($arr_storage_lock as $storage_lock) {
					$storage_lock->removeLockObject();
				}
				
				foreach ($arr_locked as &$e) {
					
					$e = Trouble::strMsg($e); // Convert to message only
				}
				
				Labels::setVariable('total', count($arr_locked));
				
				$str_locked = '<ul><li>'.implode('</li><li>', $arr_locked).'</li></ul>';
				
				error(getLabel('msg_object_locked_multi').PHP_EOL
					.$str_locked
				, TROUBLE_ERROR, LOG_CLIENT);
			}
			
			foreach ($arr_storage_lock as $storage_lock) {
				$storage_lock->upgradeLockObject(); // Apply permanent lock
			}
									
			// Merge objects
			
			GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction
			
			DB::startTransaction('data_entry_merge');
			
			try {

				if ($arr_append) {
					$merge->setAppend($arr_append);
				}
				
				$merge->merge();
				$merge->delMergedTypeObjects(false);
			} catch (Exception $e) {

				DB::rollbackTransaction('data_entry_merge');
				throw($e);
			}

			DB::commitTransaction('data_entry_merge');
			
			foreach ($arr_storage_lock as $storage_lock) {
				$storage_lock->removeLockObject();
			}
			
			$this->refresh_table = true;
			$this->msg = true;	
		}
		
		if (($method == 'find_change' || (is_array($method) && $method['method'] == 'find_change' && $method['confirmed'])) && $id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER) {
				error(getLabel('msg_not_allowed'));
			}
			
			$arr_filter = [];
			
			if (is_array($id)) {
				
				$arr_ids = [];
				
				foreach ($id as $cur_id) {
					$arr_id = explode("_", $cur_id);
					$type_id = $arr_id[0];
					$arr_ids[] = $arr_id[1];
				}
				
				$arr_filter[] = ['objects' => $arr_ids];
			} else {
				
				$type_id = $id;
			}
			
			$arr_filter[] = data_filter::parseUserFilterInput($value['filter']);
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			// No futher user or project clearance restrictions because find & change best utilises full objects
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_type_set = StoreType::getTypeSet($type_id);

			$is_filtering = (!$value['filter'] || $_POST['find_change']['target_full'] ? false : true);
			
			$_SESSION['data_entry']['find_change'][$type_id] = [];
			$arr_find_change = [];
			$arr_selection = ['object' => [], 'object_descriptions' => [], 'object_sub_details' => []];
			$arr_append = [];
			
			foreach ((array)$_POST['find_change']['selection'] as $arr_selected) {
				
				if (!$arr_selected['id']) {
					continue;
				}

				$arr_selected['values'] = json_decode($arr_selected['values'], true);
				if ($arr_selected['values']) {
					$arr_selected['values'] = current(current($arr_selected['values']));
				}
				
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
										
					$str_id = 'object_description_'.$object_description_id;
					
					if ($str_id == $arr_selected['id']) {
						
						$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
						
						if ($arr_selected['action'] == 'append') {
							$arr_append['object_definitions'][$object_description_id] = $object_description_id;
						} else {
							unset($arr_append['object_definitions'][$object_description_id]);
						}
						
						$arr_selected['values']['object_definition_sources'] = json_decode($arr_selected['values']['object_definition_sources'], true);
						
						$arr_find_change['object_descriptions'][$object_description_id] = $arr_selected;
						$_SESSION['data_entry']['find_change'][$type_id]['selection'][] = $arr_selected;
					}
				}
						
				foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					$str_id = 'object_sub_details_'.$object_sub_details_id;
					$match = $match_select = false;
					
					if ($str_id.'_id' == $arr_selected['id']) {
						if ($arr_selected['action'] == 'add') {
							$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub'][] = $arr_selected;
							$match = true;
						} else {
							$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_id'] = $arr_selected;
							$match = $match_select = true;
						}
					}
					if ($str_id.'_date_start' == $arr_selected['id']) {
						$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_date_start'] = $arr_selected;
						$match = $match_select = true;
					}
					if ($str_id.'_date_end' == $arr_selected['id']) {
						$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_date_end'] = $arr_selected;
						$match = $match_select = true;
					}
					if ($str_id.'_location' == $arr_selected['id']) {
						$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_location'] = $arr_selected;
						$match = $match_select = true;
					}	
					
					if ($match_select) {
						if (!isset($arr_selection['object_sub_details'][$object_sub_details_id])) {
							$arr_selection['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => true, 'object_sub_descriptions' => []];
						} else {
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = true;
						}
					}
					if ($match) {
						$_SESSION['data_entry']['find_change'][$type_id]['selection'][] = $arr_selected;
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						$str_id = 'object_sub_description_'.$object_sub_description_id;
						
						if ($str_id == $arr_selected['id']) {
							
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
							
							if ($arr_selected['action'] == 'append') {
								$arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id] = $object_sub_description_id;
							} else {
								unset($arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]);
							}
							
							$arr_selected['values']['object_sub_definition_sources'] = json_decode($arr_selected['values']['object_sub_definition_sources'], true);
							
							$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $arr_selected;
							$_SESSION['data_entry']['find_change'][$type_id]['selection'][] = $arr_selected;
						}
					}
				}
			}
			
			if (!$_SESSION['data_entry']['find_change'][$type_id]) {
				error(getLabel('msg_missing_information'));
			}
			
			$filter = new FilterTypeObjects($type_id, 'set');
			$filter->setVersioning();
			$filter->setSelection($arr_selection);
			if ($is_filtering) {
				$filter->setFiltering(['all' => true], ['all' => true], true);
			}
			$filter->setFilter($arr_filter);
			
			if ($method == 'find_change') {
				
				$arr_result = $filter->getResultInfo();
				
				$this->confirm = true;
				Labels::setVariable('total', $arr_result['total_filtered']);
				$this->html = getLabel('conf_find_change');
				
				return;
			}
			
			$arr_objects = $filter->init();
			
			// Lock objects
			
			$arr_locked = [];
			
			$storage_lock = new StoreTypeObjects($type_id, false, $_SESSION['USER_ID'], 'lock');
			
			foreach ($arr_objects as $object_id => $arr_object) {
				
				$storage_lock->setObjectID($object_id);
				
				try {
					$storage_lock->handleLockObject();
				} catch (Exception $e) {
					$arr_locked[] = $e;
				}
			}
			
			if ($arr_locked) {
				
				$storage_lock->removeLockObject();
				
				foreach ($arr_locked as &$e) {
					
					$e = Trouble::strMsg($e); // Convert to message only
				}
				
				Labels::setVariable('total', count($arr_locked));
				
				$str_locked = '<ul><li>'.implode('</li><li>', $arr_locked).'</li></ul>';
				
				error(getLabel('msg_object_locked_multi').PHP_EOL
					.$str_locked
				, TROUBLE_ERROR, LOG_CLIENT);
			}
			
			$storage_lock->upgradeLockObject(); // Apply permanent lock
			
			$arr_result = $filter->getResultInfo();
			Labels::setVariable('total', $arr_result['total_filtered']);
			status(getLabel('msg_object_lock_success'));
			
			// Update objects
			
			$storage = new StoreTypeObjects($type_id, false, $_SESSION['USER_ID']);
			$storage->setMode(null, false);
			$storage->setAppend($arr_append);
			
			$arr_arr_find_change_parsed = [];
			
			foreach ((array)$arr_find_change['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if (!$arr_object_sub_details['object_sub_details']['object_sub']) {
					continue;
				}
				
				$arr_input = ['object_sub' => []];
					
				foreach ($arr_object_sub_details['object_sub_details']['object_sub'] as $arr_selected) {
					
					if ($arr_selected['action'] == 'add') {
						$arr_input['object_sub'][] = $arr_selected['values'];
					}
				}
				
				if ($arr_input['object_sub']) {
					
					$arr_input = self::parseTypeObjectInput($type_id, $object_id, $arr_input, true);
					$arr_arr_find_change_parsed['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_subs'] = $arr_input['object_subs'];
				}
				
				unset($arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub']);
			}
			
			GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction
			
			DB::startTransaction('data_entry_find_change');
			
			try {
				
				foreach ($arr_objects as $object_id => $arr_object) {
									
					if ($is_filtering) {
						
						$filter_full = new FilterTypeObjects($type_id, 'storage');
						$filter_full->setVersioning();
						$filter_full->setSelection($arr_selection);
						$filter_full->setFilter(['objects' => $object_id]);
						
						$arr_object_full = $filter_full->init();
						$arr_object_full = $arr_object_full[$object_id];
					} else {
						
						$arr_object_full = $arr_object;
					}
					
					$arr_object_store = ['object_definitions' => [], 'object_subs' => []];
					
					foreach ((array)$arr_find_change['object_descriptions'] as $object_description_id => $arr_selected) {
						
						$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
							continue;
						}
						
						$arr_object_store['object_definitions'][$object_description_id] = $arr_object_full['object_definitions'][$object_description_id];
						$arr_object_definition_store =& $arr_object_store['object_definitions'][$object_description_id];

						if ($arr_object_description['object_description_has_multi']) {
							
							$key_object_definition = ($arr_object_description['object_description_ref_type_id'] ? 'object_definition_ref_object_id' : 'object_definition_value');
							
							if ($arr_selected['action'] == 'change' || $arr_selected['action'] == 'remove') {
								if ($is_filtering) {
									$arr_object_definition_store[$key_object_definition] = array_diff($arr_object_definition_store[$key_object_definition], $arr_object['object_definitions'][$object_description_id][$key_object_definition]);
								} else {
									$arr_object_definition_store[$key_object_definition] = [];
								}
							}
							
							if ($arr_selected['action'] == 'change') {
								$arr_object_definition_store[$key_object_definition] = array_merge($arr_object_definition_store[$key_object_definition], (array)$arr_selected['values'][$key_object_definition]);
							} else if ($arr_selected['action'] == 'append') {
								$arr_object_definition_store[$key_object_definition] = (array)$arr_selected['values'][$key_object_definition];
							}
						} else {
							
							if ($arr_object_description['object_description_ref_type_id']) {
								
								if ($arr_selected['action'] == 'remove') {
									$arr_object_definition_store['object_definition_ref_object_id'] = '';
								} else if ($arr_selected['action'] == 'change') {
									$arr_object_definition_store['object_definition_ref_object_id'] = $arr_selected['values']['object_definition_ref_object_id'];
								}
							} else {
								
								if ($arr_selected['action'] == 'remove') {
									$arr_object_definition_store['object_definition_value'] = '';
								} else if ($arr_selected['action'] == 'change' || $arr_selected['action'] == 'append') {
									$arr_object_definition_store['object_definition_value'] = $arr_selected['values']['object_definition_value'];
								}
							}
						}
						
						if ($arr_selected['action'] == 'remove') {
							$arr_object_definition_store['object_definition_sources'] = false;
						} else if ($arr_selected['action'] == 'change' || $arr_selected['action'] == 'append') {
							$arr_object_definition_store['object_definition_sources'] = $arr_selected['values']['object_definition_sources'];
						}
					}
					
					foreach ((array)$arr_find_change['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
							continue;
						}
										
						if ($arr_arr_find_change_parsed['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_subs']) { // Add sub-objects

							$arr_object_store['object_subs'] = array_merge($arr_object_store['object_subs'], $arr_arr_find_change_parsed['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_subs']);
						}
						
						foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) { // Change or remove sub-objects
							
							if ($arr_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
								continue;
							}
							
							$arr_object_store['object_subs'][$object_sub_id] = $arr_object_full['object_subs'][$object_sub_id];
							$arr_object_sub_store =& $arr_object_store['object_subs'][$object_sub_id];
							unset($arr_object_sub_store['object_sub_sources']);
							
							foreach ((array)$arr_object_sub_details['object_sub_details'] as $str_id => $arr_selected) {
								
								if ($str_id == 'object_sub_id') {
									if ($arr_selected['action'] == 'remove') {
										$arr_object_sub_store['object_sub']['object_sub_version'] = 'deleted';
									}
								}
								
								if ($str_id == 'object_sub_details_location') {
									
									if ($arr_selected['action'] == 'remove') {
										
										$arr_object_sub_store['object_sub']['object_sub_location_type'] = 'reference';
										$arr_object_sub_store['object_sub']['object_sub_location_ref_object_id'] = false;
									} else if ($arr_selected['action'] == 'change') {
										
										$arr_object_sub_store['object_sub'] = $arr_selected['values'] + $arr_object_sub_store['object_sub'];
									}
								}
								
								$str_id_store = '';
								
								if ($str_id == 'object_sub_details_date_start') {
									$str_id_store = 'object_sub_date_start';
								}
								if ($str_id == 'object_sub_details_date_end') {
									$str_id_store = 'object_sub_date_end';
								}
															
								if ($str_id_store) {
									if ($arr_selected['action'] == 'remove') {
										$arr_object_sub_store['object_sub'][$str_id_store] = '';
									} else if ($arr_selected['action'] == 'change') {
										$arr_object_sub_store['object_sub'][$str_id_store] = $arr_selected['values'][$str_id_store];
									}
								}
							}
							
							foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_selected) {
								
								$arr_object_sub_description = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
								
								if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
									continue;
								}
								
								$arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id] = $arr_object_full['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id];
								$arr_object_sub_definition_store =& $arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id];

								if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
									
									if ($arr_selected['action'] == 'remove') {
										$arr_object_sub_definition_store['object_sub_definition_ref_object_id'] = '';
									} else if ($arr_selected['action'] == 'change') {
										$arr_object_sub_definition_store['object_sub_definition_ref_object_id'] = $arr_selected['values']['object_sub_definition_ref_object_id'];
									}
								} else {
									
									if ($arr_selected['action'] == 'remove') {
										$arr_object_sub_definition_store['object_sub_definition_value'] = '';
									} else if ($arr_selected['action'] == 'change' || $arr_selected['action'] == 'append') {
										$arr_object_sub_definition_store['object_sub_definition_value'] = $arr_selected['values']['object_sub_definition_value'];
									}
								}
								
								if ($arr_selected['action'] == 'remove') {
									$arr_object_sub_definition_store['object_sub_definition_sources'] = false;
								} else if ($arr_selected['action'] == 'change' || $arr_selected['action'] == 'append') {
									$arr_object_sub_definition_store['object_sub_definition_sources'] = $arr_selected['values']['object_sub_definition_sources'];
								}
							}
						}
					}
					
					$storage->setObjectID($object_id);
					$storage->store([], $arr_object_store['object_definitions'], $arr_object_store['object_subs']);
				}
				
				$storage->save();
				
				$storage->commit(true);
			} catch (Exception $e) {

				DB::rollbackTransaction('data_entry_find_change');
				throw($e);
			}

			DB::commitTransaction('data_entry_find_change');
			
			$storage_lock->removeLockObject();
			
			$this->refresh_table = true;
			$this->msg = true;	
		}
		
		if ($method == "del" && $id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_UNDER_REVIEW) {
				error(getLabel('msg_not_allowed'));
			}
		
			$arr_ids = [];
			if (is_array($id)) {
				foreach ($id as $cur_id) {
					$arr_id = explode("_", $cur_id);
					$type_id = $arr_id[0];
					$arr_ids[] = $arr_id[1];
				}
			} else {
				$arr_id = explode("_", $id);
				$type_id = $arr_id[0];
				$arr_ids[] = $arr_id[1];
			}
			$arr_ids = array_filter($arr_ids);
			
			if (!$arr_ids) {
				error(getLabel('msg_missing_information'));
			}

			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			//Check whether user id is 0 to prevent shared object deletion - TEMPORARY UNTIL LOG SYSTEM IS IN PLACE
			$res = DB::query("SELECT user_id FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = ".(int)$type_id."");
			$row = $res->fetchAssoc();			
						
			$arr_locked = [];
			
			$storage = new StoreTypeObjects($type_id, 0, $_SESSION['USER_ID']);
			
			foreach ($arr_ids as $object_id) {
				
				$storage->setObjectID($object_id);
				
				try {
					$storage->handleLockObject();
				} catch (Exception $e) {
					$arr_locked[] = $e;
				}
			}
			
			if ($arr_locked) {
				
				$storage->removeLockObject();
				
				if (count($arr_ids) == 1) {
					
					throw($arr_locked[0]);
				} else {
				
					foreach ($arr_locked as &$e) {
						
						$e = Trouble::strMsg($e); // Convert to message only
					}
					
					Labels::setVariable('total', count($arr_locked));
					
					$str_locked = '<ul><li>'.implode('</li><li>', $arr_locked).'</li></ul>';
					
					error(getLabel('msg_object_locked_multi').PHP_EOL
						.$str_locked
					, TROUBLE_ERROR, LOG_CLIENT);
				}
			}
			
			$storage->delTypeObject(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
			
			$this->refresh_table = true;
			$this->msg = true;					
		}
	}
	
	public static function getTypeObjectSet($type_id, $arr_filter, $changes = false, $arr_type_set = false) {
		
		if (!is_array($arr_filter)) {
			$arr_filter = ['objects' => $arr_filter];
		}
		
		$filter = new FilterTypeObjects($type_id, 'set', false, $arr_type_set);
		$filter->setFilter($arr_filter, ($arr_filter['object_subs'] ? true : false));
		$filter->setConditions('text', toolbar::getTypeConditions($type_id));
		if ($changes) {
			$filter->setVersioning();
		}
		$arr = $filter->init();

		return current($arr);
	}
	
	public static function parseTypeObjectInput($type_id, $object_id, $arr_input, $store = false) {
	
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
		
		if ($object_id && $arr_type_set['type']['include_referenced']) {
			$arr_object_set = self::getTypeObjectSet($type_id, $object_id, false, $arr_type_set);
		}
		
		$func_parse_sources = function($type, $value) use ($arr_project) {
			
			if (is_string($value)) { // Values already processed, only needs a decode
				$arr_source_types = (array)json_decode($value, true);
			} else { // Values are not encoded, but need to be processed
				$arr_source_types = [];
				$arr_sources_raw = (array)$value;
				foreach ((array)$arr_sources_raw as $key => $arr_sources) {
					if ($arr_sources[$type.'_source_ref_type_id'] && $arr_sources['objects']) {
						foreach ($arr_sources['objects'] as $arr_source_object) {
							$arr_source_types[$arr_sources[$type.'_source_ref_type_id']][] = $arr_source_object;
						}
					}
				}
			}
			
			foreach ($arr_project['source_types'] as $ref_type_id => $value) { // Check and sort all project source types
				if (!$arr_source_types[$ref_type_id]) { // Make sure an empty source type is passed for necessary deletion
					$arr_source_types[$ref_type_id] = [];
				}
			}
			$arr_sources = $arr_source_types;
	
			return $arr_sources;
		};
		
		$arr_object_self = [
			'object_name_plain' => $arr_input['object_name_plain'],
			'object_sources' => ($arr_input['object_sources'] ? $func_parse_sources('object', $arr_input['object_sources']) : [])
		];
		
		$arr_object_definitions = [];
		$arr_object_definition_files = ($_FILES['object_definition'] ? arrRearrangeParams($_FILES['object_definition']) : []);
		$referenced_type_objects = [];
		
		foreach ((array)$arr_input['object_definition'] as $object_description_id => $value) {
			
			$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, $object_description_id)) {
				continue;
			}
			
			if ($arr_object_description['object_description_value_type'] == 'media' && $arr_object_definition_files[$object_description_id]) {
				
				$arr_object_definition_value = arrRearrangeParams($arr_object_definition_files[$object_description_id]);
				$arr_object_definition_value = arrRearrangeParams($arr_object_definition_value['object_definition_value']);
				
				if ($value['object_definition_value']['file'] !== null || $value['object_definition_value']['url'] !== null) {
				
					$value['object_definition_value'] = array_merge((array)$value['object_definition_value'], $arr_object_definition_value);
				} else { // Multi
					
					foreach ($value['object_definition_value'] as $key => $arr_value) {

						$arr_object_definition_value_key = arrRearrangeParams($arr_object_definition_value[$key]);
					
						$value['object_definition_value'][$key] = array_merge((array)$arr_value, $arr_object_definition_value_key);
					}
				}
			}
			
			$value['object_definition_value'] = ($arr_object_description['object_description_has_multi'] ? ($value['object_definition_value'] ?: []) : $value['object_definition_value']);
			$value['object_definition_ref_object_id'] = ($arr_object_description['object_description_has_multi'] ? ($value['object_definition_ref_object_id'] ?: []) : $value['object_definition_ref_object_id']);
			
			if ($arr_object_description['object_description_is_referenced'] && $store) { // Dealing with referenced descriptions is only needed when planning to store
				
				$use_object_description_id = $arr_object_description['object_description_id']; // Because the object description is referenced
				
				foreach ($value['object_definition_ref_object_id'] as $ref_object_id) {
					
					if ($object_id && in_array($ref_object_id, $arr_object_set['object_definitions'][$object_description_id]['object_definition_ref_object_id'])) { // Not changed
						continue;
					}
					
					$referenced_type_objects[$arr_object_description['object_description_ref_type_id']][$ref_object_id]['object_definitions'][] = [
						'object_description_id' => $use_object_description_id,
						'object_definition_ref_object_id' => ($object_id ?: 'last')
					];
				}
				
				if ($object_id) {
						
					foreach ((array)$arr_object_set['object_definitions'][$object_description_id]['object_definition_ref_object_id'] as $ref_object_id) {
						
						if (in_array($ref_object_id, $value['object_definition_ref_object_id'])) { // Not changed
							continue;
						}
						
						$referenced_type_objects[$arr_object_description['object_description_ref_type_id']][$ref_object_id]['object_definitions'][] = [
							'object_description_id' => $use_object_description_id,
							'object_definition_ref_object_id' => -$object_id
						];
					}
				}
			} else {
				
				$arr_object_definitions[] = [
					'object_description_id' => $object_description_id,
					'object_definition_value' => $value['object_definition_value'],
					'object_definition_ref_object_id' => $value['object_definition_ref_object_id'],
					'object_definition_sources' => ($value['object_definition_sources'] ? $func_parse_sources('object_definition', $value['object_definition_sources']) : [])
				];
			}
		}
		
		$arr_object_subs = [];
		
		foreach ((array)$arr_input['object_sub'] as $arr_object_sub) {
			
			if ($arr_object_sub['object_sub']['object_sub_id'] == 'deleted') {
				continue;
			}
			
			$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
			$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id)) {
				continue;
			}
			
			$object_sub_id = $arr_object_sub['object_sub']['object_sub_id'];
			$object_sub_object_id = $arr_object_set['object_subs'][$object_sub_id]['object_sub']['object_sub_object_id'];
			
			if ($arr_object_sub['object_sub']['object_sub_date_end_infinite'] && $store) {
				$arr_object_sub['object_sub']['object_sub_date_end'] = null;
			}
			
			$arr_object_sub['object_sub']['object_sub_sources'] = ($arr_object_sub['object_sub']['object_sub_sources'] ? $func_parse_sources('object_sub', $arr_object_sub['object_sub']['object_sub_sources']) : []);
			
			$arr_object_sub_definitions = [];
			$has_referenced = false;
			
			foreach ((array)$arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
				
				$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_edit'] || !custom_projects::checkClearanceTypeConfiguration('edit', $arr_project['types'][$type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
					continue;
				}
				
				$arr_object_sub_definition['object_sub_definition_value'] = ($arr_object_sub_description['object_sub_description_has_multi'] ? ($arr_object_sub_definition['object_sub_definition_value'] ?: []) : $arr_object_sub_definition['object_sub_definition_value']);
				$arr_object_sub_definition['object_sub_definition_ref_object_id'] = ($arr_object_sub_description['object_sub_description_has_multi'] ? ($arr_object_sub_definition['object_sub_definition_ref_object_id'] ?: []) : $arr_object_sub_definition['object_sub_definition_ref_object_id']);
				
				if ($arr_object_sub_description['object_sub_description_is_referenced'] && $store) { // Dealing with referenced descriptions is only needed when planning to store
					
					$has_referenced = true;
					$object_sub_object_id = ($object_sub_object_id ?: $arr_object_sub_definition['object_sub_definition_ref_object_id']);
					
					$arr_object_sub_definition['object_sub_definition_ref_object_id'] = ($arr_object_sub_definition['object_sub_definition_ref_object_id'] ? ($object_id ?: 'last') : false);
				}
				
				$arr_object_sub_definitions[] = [
					'object_sub_description_id' => $object_sub_description_id,
					'object_sub_definition_value' => $arr_object_sub_definition['object_sub_definition_value'],
					'object_sub_definition_ref_object_id' => $arr_object_sub_definition['object_sub_definition_ref_object_id'],
					'object_sub_definition_sources' => ($arr_object_sub_definition['object_sub_definition_sources'] ? $func_parse_sources('object_sub_definition', $arr_object_sub_definition['object_sub_definition_sources']) : [])
				];
			}
			
			$arr_object_sub = [
				'object_sub' => $arr_object_sub['object_sub'],
				'object_sub_definitions' => $arr_object_sub_definitions
			];
			
			if ($has_referenced) {
				
				if ($object_sub_object_id) {
					
					$arr_object_sub_details_self = $arr_object_sub_details['object_sub_details'];
					$arr_object_sub['object_sub']['object_sub_details_id'] = $arr_object_sub_details_self['object_sub_details_id']; // Because the sub-object is referenced
					
					$referenced_type_objects[$arr_object_sub_details_self['object_sub_details_type_id']][$object_sub_object_id]['object_subs'][] = $arr_object_sub;
				}
			} else {
				
				$arr_object_subs[] = $arr_object_sub;
			}
		}
		
		return ['object_self' => $arr_object_self, 'object_definitions' => $arr_object_definitions, 'object_subs' => $arr_object_subs, 'referenced_type_objects' => $referenced_type_objects];
	}
	
	public static function formatTypeObjectInput($type_id, $arr_input) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = cms_nodegoat_custom_projects::getTypeSetReferenced($type_id, $arr_project['types'][$type_id], 'edit');
		
		$arr_object_subs = [];
		
		foreach ((array)$arr_input['object_subs'] as $arr_object_sub) {
			
			$arr_object_sub['object_sub']['object_sub_date_start'] = StoreTypeObjects::formatToSQLValue('date', $arr_object_sub['object_sub']['object_sub_date_start']);
			if ($arr_object_sub['object_sub']['object_sub_date_end_infinite']) {
				$arr_object_sub['object_sub']['object_sub_date_end'] = DATE_INT_MAX;
			} else if ($arr_object_sub['object_sub']['object_sub_date_end']) {
				$arr_object_sub['object_sub']['object_sub_date_end'] = StoreTypeObjects::formatToSQLValue('date', $arr_object_sub['object_sub']['object_sub_date_end']);
			}
			
			if ($arr_object_sub['object_sub']['object_sub_location_ref_object_id']) {
				$arr_object_names = FilterTypeObjects::getTypeObjectNames($arr_object_sub['object_sub']['object_sub_location_ref_type_id'], $arr_object_sub['object_sub']['object_sub_location_ref_object_id'], false);
				$arr_object_sub['object_sub']['object_sub_location_ref_object_name'] = $arr_object_names[$arr_object_sub['object_sub']['object_sub_location_ref_object_id']];
			}
						
			$arr_object_sub_definitions = [];
			
			foreach ((array)$arr_object_sub['object_sub_definitions'] as $key => $arr_object_sub_definition) {
				
				$arr_object_sub_description = $arr_type_set['object_sub_details'][$arr_object_sub['object_sub']['object_sub_details_id']]['object_sub_descriptions'][$arr_object_sub_definition['object_sub_description_id']];
				
				if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
					if ($arr_object_sub_definition['object_sub_definition_ref_object_id']) {
						$arr_object_names = FilterTypeObjects::getTypeObjectNames($arr_object_sub_description['object_sub_description_ref_type_id'], $arr_object_sub_definition['object_sub_definition_ref_object_id'], false);
						$arr_object_sub_definition['object_sub_definition_value'] = $arr_object_names[$arr_object_sub_definition['object_sub_definition_ref_object_id']];
					}
				} else {
					$arr_object_sub_definition['object_sub_definition_value'] = StoreTypeObjects::formatToSQLValue($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition['object_sub_definition_value']);
				}
				
				$arr_object_sub_definitions[$arr_object_sub_definition['object_sub_description_id']] = $arr_object_sub_definition;
			}
			
			$arr_object_subs[] = [
				'object_sub' => $arr_object_sub['object_sub'],
				'object_sub_definitions' => $arr_object_sub_definitions
			];
		}
		
		return ['object_self' => $arr_object_self, 'object_definitions' => $arr_object_definitions, 'object_subs' => $arr_object_subs];
	}
}
