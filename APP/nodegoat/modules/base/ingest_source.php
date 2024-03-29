<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

abstract class ingest_source extends base_module {

	public static function moduleProperties() {
		static::$parent_label = 'nodegoat';
	}
	
	public $form_name = 'template';
	
	abstract protected function createTemplateSettingsExtra($arr_template);
	abstract protected function createCheckTemplate($arr_template, $source);
	abstract public function createProcessTemplate($arr_template, $source, $arr_feedback);
	abstract public static function getPointerDataHeadings($source_id, $pointer_heading = false);
	abstract public static function getPointerFilterHeadings($source_id, $pointer_heading = false);
	abstract public static function getSources();
	abstract public static function getTemplate($identifier);
	
	public static function getPointerQueryHeadings($source_id, $pointer_heading = false) { }
	
	protected static $use_project = false;
	protected static $use_log = false;
	protected static $use_type_filter = false;
	protected static $use_object_identifier_uri = false;
	protected static $use_filter_object_value = true;
	protected static $use_query_object_value = false;
	protected static $use_query_type_object_value = false;
	protected static $use_filter_value = true;
	protected static $use_query_value = false;
	
	protected $has_feedback_template_process = false;
	protected $is_done_template_process = false;
	protected $is_new_template_process = false;
	
	protected static $arr_labels = [
		'lbl_source' => 'Source',
		'lbl_pointer_data' => 'Pointer',
		'lbl_pointer_filter' => 'Pointer',
		'lbl_pointer_query' => 'Pointer',
		'inf_pointer_data' => 'Value in external source',
		'inf_pointer_filter' => 'Value in external source',
		'inf_pointer_query' => 'Value in external source',
		'inf_template' => false,
		'inf_pointer_filter_value' => false,
		'inf_pointer_filter_object_sub_identifier' => false,
		'inf_pointer_query_value' => false,
		'inf_pointer_link_object' => false,
		'inf_pointer_map' => false,
		'inf_template_process_select_source' => false
	];
	
	public function __construct() {
		
		parent::__construct();
		
		Labels::setVariable('source', getLabel(static::$arr_labels['lbl_source']));
	}
	
	// Create template
		
	public function createTemplate($arr_template = []) {
		
		if (static::$use_project) {
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_types_all = StoreType::getTypes(array_keys($arr_project['types']));
		} else {
			
			$arr_types_all = StoreType::getTypes();
		}
		
		$arr_sources = static::getSources();
		
		foreach ($arr_types_all as $cur_type_id => $arr_type) {
			
			if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
				unset($arr_types_all[$cur_type_id]);
			}
			
		}
		
		$template_mode = IngestTypeObjects::MODE_OVERWRITE;
		$type_id = false;
		$has_filter_object_value = false;
		$has_filter_object_identifier = false;
		$has_filter_object_sub_identifier = false;
		$has_query_object_value = false;
		$has_query_type_object_value = false;
		
		if ($arr_template) {
			
			$template_mode = (int)$arr_template['mode'];
			$type_id = $arr_template['type_id'];
			$source_id = $arr_template['source_id'];
			
			$has_filter_object_value = (bool)$arr_template['pointers']['filter_object_value'];
			$has_filter_object_identifier = (bool)$arr_template['pointers']['filter_object_identifier'];
			$has_filter_object_sub_identifier = ((bool)$arr_template['pointers']['filter_object_sub_identifier'] && $has_filter_object_identifier);
			$has_query_object_value = (bool)$arr_template['pointers']['query_object_value'];
			$has_query_type_object_value = (bool)$arr_template['pointers']['query_type_object_value'];
			
			if (!$template_mode) {
				$template_mode = (($has_filter_object_value || $has_filter_object_identifier || $has_query_object_value) ? IngestTypeObjects::MODE_UPDATE : IngestTypeObjects::MODE_OVERWRITE);
			}
			
			if (!$type_id || !$arr_types_all[$type_id]) {
				msg(getLabel('msg_type_does_not_exist'), false, LOG_CLIENT);
			}
		}

		$arr_link_object_modes = [['id' => 'filter_object_identifier', 'name' => (static::$use_object_identifier_uri ? getLabel('lbl_object').' URI' : getLabel('lbl_nodegoat_id'))]];
		if (static::$use_filter_object_value) {
			$arr_link_object_modes[] = ['id' => 'filter_object_value', 'name' => getLabel('lbl_filter')];
		}
		if (static::$use_query_object_value) {
			$arr_link_object_modes[] = ['id' => 'query_object_value', 'name' => getLabel('lbl_query')];
		}
		$arr_template_modes = [['id' => IngestTypeObjects::MODE_OVERWRITE, 'name' => getLabel('lbl_ingest_objects_add')], ['id' => IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS, 'name' => getLabel('lbl_ingest_objects_add_if_not_exists')], ['id' => IngestTypeObjects::MODE_UPDATE, 'name' => getLabel('lbl_ingest_objects_update')]];
	
		$return = '<div class="ingest-source template" data-form_name="'.$this->form_name.'">
		
			<div class="options">
				'.(static::$arr_labels['inf_template'] ? '<section class="info attention body">'.parseBody(getLabel(static::$arr_labels['inf_template'])).'</section>' : '').'
				<fieldset>
					<ul>
						<li>
							<label>'.getLabel('lbl_source').'</label>
							<div><select name="'.$this->form_name.'[source_id]">'.cms_general::createDropdown($arr_sources, $source_id, true).'</select></div>
						</li>
						<li>
							<label>'.getLabel('lbl_target').'</label>
							<div>';

								$return .= '<select name="'.$this->form_name.'[type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all, $type_id, true)).'</select>';
								
								if (static::$use_type_filter) {
									
									Labels::setVariable('application', getLabel('lbl_type'));
									
									$return .= '<input type="hidden" name="'.$this->form_name.'[type_filter]" value="'.($arr_template['type_filter'] ? strEscapeHTML(value2JSON($arr_template['type_filter'])) : '').'" />'
										.'<button type="button" id="y:data_filter:configure_application_filter-0" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>';
								}
								
							$return .= '</div>
						</li>
						<li>
							<label>'.getLabel('lbl_mode').'</label>
							<div>
								'.cms_general::createSelectorRadio($arr_template_modes, ''.$this->form_name.'[mode]', $template_mode);
								if (static::$use_log) {
									$return .= '<span class="split"></span>'
										.'<label><input type="checkbox" name="'.$this->form_name.'[use_log]" value="1" '.($arr_template['use_log'] ? 'checked="checked"' : '').'/><span>'.getLabel('lbl_ingest_show_log').'</span></label>';
								}
							$return .= '</div>
						</li>
						'.$this->createTemplateSettingsExtra($arr_template).'
					</ul>
				</fieldset>
			</div>';
			
			if (static::$use_query_value || static::$use_filter_value) {
				
				// Query by value
				
				if (static::$use_query_value) {
							
					$html_query_value = '<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label>'.getLabel('lbl_query').'</label>
						<div id="y:ingest_source:set_pointers-query_value">';
					
							if ($arr_template) {
								
								$arr_sorter = [];
								
								$arr_pointers = ($arr_template['pointers']['query_value'] ?: [[]]);
								array_unshift($arr_pointers, []); // Empty run for sorter source

								foreach ($arr_pointers as $key => $arr_pointer) {
									
									$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createPointerQueryValue($source_id, $arr_pointer)];
								}

								$html_query_value .= cms_general::createSorter($arr_sorter, true);
							}
						
						$html_query_value .= '</div>
					</li>';
					
					$html_query_value = (static::$arr_labels['inf_pointer_query_value'] ? '<li>
						<label></label>
						<div><section class="info attention">'.parseBody(getLabel(static::$arr_labels['inf_pointer_query_value'])).'</section></div>
					</li>' : '').'
					'.$html_query_value;
				}
				
				// Filter by value
				
				if (static::$use_filter_value) {
					
					$html_filter_value = '<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label>'.getLabel('lbl_filter').'</label>
						<div id="y:ingest_source:set_pointers-filter_value">';
					
							if ($arr_template) {
								
								$arr_sorter = [];
								
								$arr_pointers = ($arr_template['pointers']['filter_value'] ?: [[]]);
								array_unshift($arr_pointers, []); // Empty run for sorter source

								foreach ($arr_pointers as $key => $arr_pointer) {
									
									$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createPointerFilterValue($source_id, $arr_pointer)];
								}

								$html_filter_value .= cms_general::createSorter($arr_sorter, true);
							}
						
						$html_filter_value .= '</div>
					</li>';
					
					if (static::$arr_labels['inf_pointer_filter_value']) {
						
						$html_filter_value = '<li>
							<label></label>
							<div><section class="info attention">'.parseBody(getLabel(static::$arr_labels['inf_pointer_filter_value'])).'</section></div>
						</li>'
						.$html_filter_value;
					}
				}
				
				$return .= '<div class="options filter-value query-value">
					<fieldset>
						<legend>'.getLabel('lbl_ingest_template_query_filter_value').'</legend>
						<ul>
							'.$html_query_value.'
							'.$html_filter_value.'
						</ul>
					</fieldset>
				</div>';
			}
			
			// Link/match object

			$return .= '<div class="options link-object">';
				
				$str_selected = ($has_query_object_value ? 'query_object_value' : ($has_filter_object_identifier ? 'filter_object_identifier' : 'filter_object_value'));
				
				$html_link_object_modes = '<li>
					<label>'.getLabel('lbl_ingest_objects_identify').'</label>
					<div>'.cms_general::createSelectorRadio($arr_link_object_modes, $this->form_name.'[link_object_mode]', $str_selected).'</div>
				</li>';
				
				$html_filter_object_identifier = '<li>
					<label>'.(static::$use_object_identifier_uri ? getLabel('lbl_object').' URI' : getLabel('lbl_nodegoat_id')).'</label>
					<div id="y:ingest_source:set_pointers-filter_object_identifier">';
					
						if ($arr_template) {
							
							$arr_pointer = ($arr_template['pointers']['filter_object_identifier'] ?: []);
							
							if (static::$use_object_identifier_uri) {
								$html_filter_object_identifier .= $this->createPointerFilterTypeObjectURI($type_id, $arr_pointer);
							} else {
								$html_filter_object_identifier .= $this->createPointerFilterTypeObjectID($source_id, $arr_pointer);
							}
						}
					
					$html_filter_object_identifier .= '</div>
				</li>';
				
				if (!static::$use_object_identifier_uri) {

					$html_filter_object_sub_identifier .= '<li>
						<label>'.getLabel('lbl_object_sub_id').'</label>
						<div id="y:ingest_source:set_pointers-filter_object_sub_identifier">';
						
							if ($arr_template) {
								
								$arr_pointer = ($arr_template['pointers']['filter_object_sub_identifier'] ?: []);
								
								$html_filter_object_sub_identifier .= $this->createPointerFilterTypeObjectSubID($source_id, $arr_pointer);
							}
						
						$html_filter_object_sub_identifier .= '</div>
					</li>';
					
					if (static::$arr_labels['inf_pointer_filter_object_sub_identifier']) {
						
						$html_filter_object_sub_identifier = '<li>
							<label></label>
							<section class="info attention body">'.parseBody(getLabel(static::$arr_labels['inf_pointer_filter_object_sub_identifier'])).'</section>
						</li>'
						.$html_filter_object_sub_identifier;
					}
					
					$html_filter_object_identifier .= $html_filter_object_sub_identifier;
				}
				
				if (static::$use_filter_object_value) {
					
					$html_filter_object_value = '<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label>'.getLabel('lbl_filter').'</label>
						<div id="y:ingest_source:set_pointers-filter_object_value">';
					
							if ($arr_template) {
								
								$arr_sorter = [];
								
								$arr_pointers = ($arr_template['pointers']['filter_object_value'] ?: [[]]);
								array_unshift($arr_pointers, []); // Empty run for sorter source

								foreach ($arr_pointers as $key => $arr_pointer) {
									
									$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createPointerFilterTypeObjectValue($source_id, $type_id, $arr_pointer)];
								}

								$html_filter_object_value .= cms_general::createSorter($arr_sorter, true);
							}
						
						$html_filter_object_value .= '</div>
					</li>';
				}
			
				if (static::$use_query_object_value) {
					
					$html_query_object_value = '<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label>'.getLabel('lbl_query').'</label>
						<div id="y:ingest_source:set_pointers-query_object_value">';
					
							if ($arr_template) {
								
								$arr_sorter = [];
								
								$arr_pointers = ($arr_template['pointers']['query_object_value'] ?: [[]]);
								array_unshift($arr_pointers, []); // Empty run for sorter source

								foreach ($arr_pointers as $key => $arr_pointer) {
									
									$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createPointerQueryTypeObjectValue($source_id, $type_id, $arr_pointer, 'query_object_value')];
								}

								$html_query_object_value .= cms_general::createSorter($arr_sorter, true);
							}
						
						$html_query_object_value .= '</div>
					</li>';
				}
			
				$return .= '<fieldset>
					<legend>'.getLabel('lbl_ingest_template_link_object').'</legend>
					'.(static::$arr_labels['inf_pointer_link_object'] ? '<section class="info attention body">'.parseBody(getLabel(static::$arr_labels['inf_pointer_link_object'])).'</section>' : '').'
					<ul>
						'.$html_link_object_modes.'
						'.$html_filter_object_identifier.'
						'.$html_filter_object_value.'
						'.$html_query_object_value.'
					</ul>
				</fieldset>
				
			</div>';
			
			// Query objects in other type
			
			if (static::$use_query_type_object_value) {
				
				$return .= '<div class="options link-type-object">';
					
					Labels::setVariable('application', getLabel('lbl_type'));
					
					$html_query_type_object_value = '<li>
						<label>'.getLabel('lbl_use').'</label>
						<div>'
							.'<select name="'.$this->form_name.'[query_type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all, $arr_template['query_type_id'], true)).'</select>'
							.'<input type="hidden" name="'.$this->form_name.'[query_type_filter]" value="'.($arr_template['query_type_filter'] ? strEscapeHTML(value2JSON($arr_template['query_type_filter'])) : '').'" />'
							.'<button type="button" id="y:data_filter:configure_application_filter-0" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>'
						.'</div>
					</li>
					<li>
						<label></label>
						<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
					</li>
					<li>
						<label>'.getLabel('lbl_query').'</label>
						<div id="y:ingest_source:set_pointers-query_type_object_value">';
					
							if ($arr_template) {
								
								$arr_sorter = [];
								
								$arr_pointers = ($arr_template['pointers']['query_type_object_value'] ?: [[]]);
								array_unshift($arr_pointers, []); // Empty run for sorter source

								foreach ($arr_pointers as $key => $arr_pointer) {
									
									$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createPointerQueryTypeObjectValue($source_id, $arr_template['query_type_id'], $arr_pointer, 'query_type_object_value')];
								}

								$html_query_type_object_value .= cms_general::createSorter($arr_sorter, true);
							}
						
						$html_query_type_object_value .= '</div>
					</li>';
				
					$return .= '<fieldset>
						<legend>'.getLabel('lbl_ingest_template_link_type_object').'</legend>
						'.(static::$arr_labels['inf_pointer_link_type_object'] ? '<section class="info attention body">'.parseBody(getLabel(static::$arr_labels['inf_pointer_link_type_object'])).'</section>' : '').'
						<ul>
							'.$html_query_type_object_value.'
						</ul>
					</fieldset>
				</div>';
			}
						
			// Mapping
			
			$return .= '<div class="options map">
			
				<fieldset>
					<legend>'.getLabel('lbl_ingest_template_map').'</legend>
					'.(static::$arr_labels['inf_pointer_map'] ? '<section class="info attention body">'.parseBody(getLabel(static::$arr_labels['inf_pointer_map'])).'</section>' : '').'
					<ul>
						<li>
							<label></label>
							<div><menu class="sorter"><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></menu></div>
						</li>
						<li>
							<label>'.getLabel('lbl_link_to').'</label>
							<div id="y:ingest_source:set_pointers-map">';
							
								if ($arr_template) {
									
									$arr_sorter = [];
									
									$arr_pointers = ($arr_template['pointers']['map'] ?: [[]]);
									array_unshift($arr_pointers, []); // Empty run for sorter source

									foreach ($arr_pointers as $key => $arr_pointer) {
										
										$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createPointerMap($source_id, $type_id, $arr_pointer, $template_mode == IngestTypeObjects::MODE_UPDATE)];
									}

									$return .= cms_general::createSorter($arr_sorter, true);
								}
															
							$return .= '</div>
						</li>
					</ul>
				</fieldset>
			</div>
			
		</div>';
			
		$this->validate = [$this->form_name.'[source_id]' => 'required', $this->form_name.'[type_id]' => 'required'];
				
		return $return;
	}
	
	private function createPointerFilterValue($source_id, $arr_pointer = []) {

		$pointer_heading = $arr_pointer['pointer_heading'];
		
		$arr_pointer_headings = static::getPointerFilterHeadings($source_id, $pointer_heading);
		
		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers][filter_value]['.$unique.'][pointer_heading]" title="'.getLabel(static::$arr_labels['inf_pointer_filter']).'">'.cms_general::createDropdown($arr_pointer_headings, $pointer_heading, true).'</select>'
			.'<input type="text" name="'.$this->form_name.'[pointers][filter_value]['.$unique.'][value]" title="'.getLabel('inf_ingest_filter_value').'" value="'.strEscapeHTML($arr_pointer['value']).'" />'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers][filter_value]['.$unique.'][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;
		
		$arr_html = [];
	
		$html_pointer = '<div>'.$html_pointer.'</div>';
		
		$arr_html[] = $html_pointer;
			
		return $arr_html;
	}
	
	private function createPointerQueryValue($source_id, $arr_pointer = []) {

		$pointer_heading = $arr_pointer['pointer_heading'];
		
		$arr_pointer_headings = static::getPointerQueryHeadings($source_id, $pointer_heading);
		
		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers][query_value]['.$unique.'][pointer_heading]" title="'.getLabel(static::$arr_labels['inf_pointer_query']).'">'.cms_general::createDropdown($arr_pointer_headings, $pointer_heading, true).'</select>'
			.'<input type="text" name="'.$this->form_name.'[pointers][query_value]['.$unique.'][value]" title="'.getLabel('inf_ingest_query_value').'" value="'.strEscapeHTML($arr_pointer['value']).'" />'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers][query_value]['.$unique.'][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;
		
		$arr_html = [];
	
		$html_pointer = '<div>'.$html_pointer.'</div>';
		
		$arr_html[] = $html_pointer;
			
		return $arr_html;
	}
		
	private function createPointerFilterTypeObjectID($source_id, $arr_pointer = []) {

		$pointer_heading = $arr_pointer['pointer_heading'];

		$arr_pointer_headings = static::getPointerDataHeadings($source_id, $pointer_heading);

		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers][filter_object_identifier][pointer_heading]" title="'.getLabel(static::$arr_labels['inf_pointer_data']).'">'.cms_general::createDropdown($arr_pointer_headings, $pointer_heading, false).'</select>'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers][filter_object_identifier][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;
			
		return $html_pointer;
	}
	
	private function createPointerFilterTypeObjectSubID($source_id, $arr_pointer = []) {

		$pointer_heading = $arr_pointer['pointer_heading'];

		$arr_pointer_headings = static::getPointerDataHeadings($source_id, $pointer_heading);

		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers][filter_object_sub_identifier][pointer_heading]" title="'.getLabel(static::$arr_labels['inf_pointer_data']).'">'.cms_general::createDropdown($arr_pointer_headings, $pointer_heading, true).'</select>'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers][filter_object_sub_identifier][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;
			
		return $html_pointer;
	}
	
	private function createPointerFilterTypeObjectURI($type_id, $arr_pointer = []) {
		
		$arr_object_descriptions = StoreType::getTypesObjectIdentifierDescriptions($type_id);
		$arr_object_descriptions = ($arr_object_descriptions[$type_id] ?: []);
		
		if (!$arr_object_descriptions) {
			return '<section class="info attention">'.getLabel('msg_ingest_no_identifier_descriptions').'<input type="hidden" name="'.$this->form_name.'[pointers][filter_object_identifier]" value="" /></section>';
		}
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		foreach ($arr_object_descriptions as $object_description_id => &$arr_object_description) {
			$arr_object_description = ['id' => $object_description_id, 'name' => $arr_type_set['object_descriptions'][$object_description_id]['object_description_name']];
		}
		unset($arr_object_description);
		
		$object_description_id = $arr_pointer['object_description_id'];

		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers][filter_object_identifier][object_description_id]" title="'.getLabel('inf_ingest_target_element').'">'.cms_general::createDropdown($arr_object_descriptions, $object_description_id, false).'</select>'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers][filter_object_identifier][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;
			
		return $html_pointer;
	}
	
	private function createPointerFilterTypeObjectValue($source_id, $type_id, $arr_pointer = []) {

		$arr_type_set = StoreType::getTypeSetFlatMap($type_id, ['object' => true, 'references' => false]);
		$pointer_heading = $arr_pointer['pointer_heading'];
		
		$arr_pointer_headings = static::getPointerFilterHeadings($source_id, $pointer_heading);
		
		$element_id = $arr_pointer['element_id'];
		
		if (!$element_id) {
			
			$element_id = key($arr_type_set);
		}
		
		$arr_element_id = explode('_', $element_id);

		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers][filter_object_value]['.$unique.'][pointer_heading]" title="'.getLabel(static::$arr_labels['inf_pointer_filter']).'">'.cms_general::createDropdown($arr_pointer_headings, $pointer_heading, true).'</select>'
			.'<select name="'.$this->form_name.'[pointers][filter_object_value]['.$unique.'][element_id]" title="'.getLabel('inf_ingest_target_element').'">'.cms_general::createDropdown($arr_type_set, $element_id, false).'</select>'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers][filter_object_value]['.$unique.'][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;
		
		$arr_html = [];
	
		$html_pointer = '<div id="y:ingest_source:create_pointer-'.$source_id.'_'.$type_id.'_filter">'.$html_pointer.'</div>';
		
		$arr_html[] = $html_pointer;
			
		return $arr_html;
	}
	
	private function createPointerQueryTypeObjectValue($source_id, $type_id, $arr_pointer = [], $str_name = 'query_object_value') {

		$arr_type_set = StoreType::getTypeSetFlatMap($type_id, ['object' => true, 'references' => false]);
		$pointer_heading = $arr_pointer['pointer_heading'];
		
		$arr_pointer_headings = static::getPointerQueryHeadings($source_id, $pointer_heading);
		
		$element_id = $arr_pointer['element_id'];
		
		if (!$element_id) {
			
			$element_id = key($arr_type_set);
		}
		
		$arr_element_id = explode('_', $element_id);

		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers]['.$str_name.']['.$unique.'][pointer_heading]" title="'.getLabel(static::$arr_labels['inf_pointer_query']).'">'.cms_general::createDropdown($arr_pointer_headings, $pointer_heading, true).'</select>'
			.'<select name="'.$this->form_name.'[pointers]['.$str_name.']['.$unique.'][element_id]" title="'.getLabel('inf_ingest_target_element').'">'.cms_general::createDropdown($arr_type_set, $element_id, false).'</select>'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers]['.$str_name.']['.$unique.'][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;
		
		$arr_html = [];
	
		$html_pointer = '<div id="y:ingest_source:create_pointer-'.$source_id.'_'.$type_id.'_query">'.$html_pointer.'</div>';
		
		$arr_html[] = $html_pointer;
			
		return $arr_html;
	}
	
	private function createPointerMap($source_id, $type_id, $arr_pointer = [], $is_update = false) {

		$arr_type_set = StoreType::getTypeSetFlatMap($type_id, ['object' => false, 'references' => true]);
		$pointer_heading = $arr_pointer['pointer_heading'];
	
		$arr_pointer_headings = static::getPointerDataHeadings($source_id, $pointer_heading);
		
		$element_id = $arr_pointer['element_id'];
		
		if (!$element_id) {
			
			$element_id = key($arr_type_set);
		}
		
		$arr_element_id = explode('_', $element_id);

		$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
		
		$html_pointer = '<select name="'.$this->form_name.'[pointers][map]['.$unique.'][pointer_heading]" title="'.getLabel(static::$arr_labels['inf_pointer_data']).'">'.cms_general::createDropdown($arr_pointer_headings, $pointer_heading, true).'</select>'
			.'<input name="'.$this->form_name.'[pointers][map]['.$unique.'][value_split]" type="text" value="'.$arr_pointer['value_split'].'" title="'.getLabel('inf_ingest_pointer_value_split').'" />'
			.'<select name="'.$this->form_name.'[pointers][map]['.$unique.'][value_index]"'.($arr_pointer['value_index'] && $arr_pointer['value_split'] ? '' : ' class="hide"').' title="'.getLabel('inf_ingest_pointer_value_index').'">'.cms_general::createDropdown(IngestTypeObjects::getValueIndexOptions(), $arr_pointer['value_index']).'</select>'
			.'<select name="'.$this->form_name.'[pointers][map]['.$unique.'][element_id]" title="'.getLabel('inf_ingest_target_element').'">'.cms_general::createDropdown($arr_type_set, $element_id, false).'</select>'
			.($arr_pointer['pointer_id'] ? '<input name="'.$this->form_name.'[pointers][map]['.$unique.'][pointer_id]" type="hidden" value="'.$arr_pointer['pointer_id'].'" />' : '')
		;

		if ($arr_type_set[$element_id]['ref_type_id'] || $arr_type_set[$element_id]['is_location_reference']) {
			
			if ($arr_type_set[$element_id]['is_changeable_ref_type_id']) {
				
				$ref_type_id = ($arr_pointer['element_type_id'] ? $arr_pointer['element_type_id'] : $arr_type_set[$element_id]['ref_type_id']);
				
			} else {
				
				$disabled = true;
				$ref_type_id = $arr_type_set[$element_id]['ref_type_id'];
			}
		
			$arr_ref_type_set = StoreType::getTypeSetFlatMap($ref_type_id, ['object' => true, 'references' => false]);
			$arr_types = StoreType::getTypes();
			
			$html_pointer .= '<select name="'.$this->form_name.'[pointers][map]['.$unique.'][element_type_id]" title="'.getLabel('inf_import_type_of_target_element').'"'.($disabled ? ' disabled="disabled"' : '').'>'.Labels::parseTextVariables(cms_general::createDropdown($arr_types, $ref_type_id, false)).'</select>';
			
			if ($arr_type_set[$element_id]['is_location_reference']) {
				
				if ($arr_type_set[$element_id]['is_changeable_object_sub_details_id']) {
					
					$object_sub_details_id = ($arr_pointer['element_type_object_sub_id'] ? $arr_pointer['element_type_object_sub_id'] : $arr_type_set[$element_id]['object_sub_details_id']);
					
				} else {
					
					$sub_disabled = true;
					$object_sub_details_id = $arr_type_set[$element_id]['object_sub_details_id'];
				}
						
				$html_pointer .= '<select name="'.$this->form_name.'[pointers][map]['.$unique.'][element_type_object_sub_id]" title="'.getLabel('inf_import_sub_object_of_location_reference_type').'"'.($sub_disabled ? ' disabled="disabled"' : '').'>'.Labels::parseTextVariables(cms_general::createDropdown(StoreType::getTypeObjectSubsDetails($ref_type_id), $object_sub_details_id, false, 'object_sub_details_name', 'object_sub_details_id')).'</select>';
			
			} else {
				
				$html_pointer .= '<input name="'.$this->form_name.'[pointers][map]['.$unique.'][element_type_object_sub_id]" type="hidden"/>';
				
			}
			 
			$html_pointer .= '<select name="'.$this->form_name.'[pointers][map]['.$unique.'][element_type_element_id]" title="'.getLabel('inf_import_element_used_for_reference').'">'.cms_general::createDropdown((array)$arr_ref_type_set, $arr_pointer['element_type_element_id'], true).'</select>';
			
		} else {
			
			$html_pointer .= '<input name="'.$this->form_name.'[pointers][map]['.$unique.'][element_type_id]" type="hidden" value="0" />'
				.'<input name="'.$this->form_name.'[pointers][map]['.$unique.'][element_type_object_sub_id]" type="hidden" value="0" />'
				.'<input name="'.$this->form_name.'[pointers][map]['.$unique.'][element_type_element_id]" type="hidden" value="0" />';
		}
		
		$is_appendable = $arr_type_set[$element_id]['is_appendable'];
		$is_only_append = ($is_appendable === 'only_append' ? true : false);

		$arr_html = [];

		$html_pointer = '<div id="y:ingest_source:create_pointer-'.$source_id.'_'.$type_id.'">'.$html_pointer.'</div>';
		
		$arr_html[] = $html_pointer;
		
		$is_mode_overwrite = ($arr_pointer['mode_write'] == 'overwrite' || !$is_appendable);
		$is_mode_append = ($arr_pointer['mode_write'] == 'append' || $is_only_append || !$is_mode_overwrite);
	
		$html_options = '<div>'
			.'<label><input type="radio" name="'.$this->form_name.'[pointers][map]['.$unique.'][mode_write]" value="overwrite"'.($is_only_append ? ' disabled="disabled"' : ($is_mode_overwrite ? ' checked="checked"' : '')).'/><span>'.getLabel('lbl_overwrite').'</span></label>'
			.'<label><input type="radio" name="'.$this->form_name.'[pointers][map]['.$unique.'][mode_write]" value="append"'.(!$is_appendable ? ' disabled="disabled"' : ($is_mode_append ? ' checked="checked"' : '')).' /><span>'.getLabel('lbl_append').'</span></label>'
			.'<label><input type="checkbox" name="'.$this->form_name.'[pointers][map]['.$unique.'][ingore_empty]" value="1"'.($arr_pointer['ingore_empty'] ? ' checked="checked"' : '').'/><span>'.getLabel('lbl_import_ignore_empty').'</span></label>'
			.'<label><input type="checkbox" name="'.$this->form_name.'[pointers][map]['.$unique.'][ingore_identical]"'.($arr_type_set[$element_id]['has_multi'] || $is_only_append ? ' disabled="disabled"' : '').' value="1"'.($arr_pointer['ingore_identical'] ? ' checked="checked"' : '').'/><span>'.getLabel('lbl_import_ignore_identical').'</span></label>'
		.'</div>';
		
		$has_set_options = ($arr_pointer['mode_write'] == 'overwrite' || $arr_pointer['ingore_empty'] || $arr_pointer['ingore_identical']);
		
		$html_options = '<fieldset class="pointer-options">
			<ul><li>
				'.($is_update && $has_set_options ? $html_options : '<div class="hide-edit hide">'.$html_options.'</div><input type="button" class="data neutral" title="'.getLabel('lbl_import_specify_additional_options').'" value="more" />').'
			</li></ul>
		</fieldset>';
		
		$arr_html[] = $html_options;

		return $arr_html;
	}
	
	// Run template
	
	public function createRunTemplate($arr_template) {
		
		if (static::$use_project) {
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_types = StoreType::getTypes(array_keys($arr_project['types']));
		} else {
			
			$arr_types = StoreType::getTypes();
		}
		
		if (!$arr_template['pointers']) {
			return false;
		}
				
		$type_id = $arr_template['type_id'];
		$source_id = $arr_template['source_id'];
		
		if (!$arr_types[$type_id]) {
			
			error(getLabel('msg_type_does_not_exist'), TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$arr_sources = static::getSources();

		$return = '<div class="ingest-source run">
			
			<div class="options">
		
				'.(static::$arr_labels['inf_template_process_select_source'] ? '<section class="info attention body">'.parseBody(getLabel(static::$arr_labels['inf_template_process_select_source'])).'</section>' : '').'
				
				<fieldset>
					<ul>
						<li>
							<label>'.getLabel('lbl_source').'</label>
							<div>
								<select id="y:ingest_source:check_template-'.$arr_template['identifier'].'" name="source_id">'.cms_general::createDropdown($arr_sources, $source_id, true).'</select>
							</div>
						</li>
					</ul>
				</fieldset>
			</div>
					
			<div class="options">';
			
				if ($source_id) {
					$return .= $this->createCheckTemplate($arr_template, $source_id);
				}
			
			$return .= '</div>
			
		</div>';
				
		return $return;
	}
	
	protected function createProcessTemplateResultCheck($arr_type_results) {

		$html = '';
		
		if ($arr_type_results['error']) {

			$this->is_done_template_process = true;
			
			$str_header = getLabel('msg_ingest_error_resolving_filters').' '.implode(',', $arr_type_results['error']['message']).' '.getLabel('msg_ingest_inspect_template_source');
			$html = '<section class="info attention body"><strong>'.getLabel('msg_ingest_error_at_value').'</strong> <i>'.implode(',', $arr_type_results['error']['values']).'</i></section>';
		} else {

			$count_total = 0;
			foreach ((array)$arr_type_results as $type_id => $arr_results) {
				$count_total = $count_total + count($arr_results);
			}

			if ($count_total > 250) {
				
				$this->is_done_template_process = true;
				
				Labels::setVariable('count', 250);
				$str_header = getLabel('msg_ingest_unmatched_filters');
			} else {
				
				$str_header = getLabel('msg_ingest_unmatched_results_resolve');
				
				$arr_types = StoreType::getTypes();
				
				foreach ($arr_type_results as $type_id => $arr_results) {
					
					$html .= '<div class="options">
					
						<h3>'.count($arr_results).' '.getLabel('lbl_ingest_unmatched_results').': "'.Labels::parseTextVariables($arr_types[$type_id]['name']).'"</h3>
						
						<section class="info attention body">'.parseBody(getLabel('inf_ingest_unmatched_results')).'</section>
						
						<fieldset><ul class="results">';
					
						foreach ((array)$arr_results as $arr_result) {
							
							$str_pattern_value = value2JSON($arr_result['pattern_value']);
							$str_value = '';
								
							foreach ($arr_result['pattern_value'] as $value) {
								
								if (!$value) {
									$value = '[no value]';
								}
								
								$str_value .= ($str_value ? ', ' : '').'"'.mb_strimwidth($value, 0, 100, '...').'"';
							}
							
							$unique_id = uniqid();
							
							$html .= '<li>
								<label>'.$str_value.'</label>
								<div class="fieldsets"><div>';
							
									if ($arr_result['object_ids']) {
										
										$arr_object_names = GenerateTypeObjects::getTypeObjectNames($type_id, $arr_result['object_ids'], false);

										foreach ($arr_result['object_ids'] as $object_id) {
											
											$html .= '<fieldset>
												<label title="'.getLabel('inf_pattern_pair_assign_object').'">'
													.'<input type="radio" name="'.$unique_id.'" id="y:data_pattern_pairs:store-0" value="'.$arr_result['identifier'].'" />'
													.'<input type="hidden" name="pattern_value" value="'.strEscapeHTML($str_pattern_value).'" />'
													.'<input type="hidden" name="type_id" value="'.$type_id.'" />'
													.'<input type="hidden" name="object_id" value="'.$object_id.'" />'
												.'</label><label>'
													.'<span id="y:data_view:view_type_object-'.$type_id.'_'.$object_id.'" class="a popup">'.$arr_object_names[$object_id].'</span>'
												.'</label>'
											.'</fieldset>';
										}
									}
									
									$html .= '<fieldset>
										<label title="'.getLabel('inf_pattern_pair_assign_object').'">'
											.'<input type="radio" name="'.$unique_id.'" id="y:data_pattern_pairs:store-0" value="'.$arr_result['identifier'].'" />'
										.'</label>'											
										.'<input type="hidden" name="pattern_value" value="'.strEscapeHTML($str_pattern_value).'" />'
										.'<input type="hidden" name="type_id" value="'.$type_id.'" />'
										.'<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$type_id.'" name="object_id" value="" />'
										.'<input type="search" id="y:data_filter:lookup_type_object-'.$type_id.'" class="autocomplete" value="" />'
									.'</fieldset><fieldset>
										<label title="'.getLabel('inf_pattern_pair_assign_no_object').'">'
											.'<input type="hidden" name="pattern_value" value="'.strEscapeHTML($str_pattern_value).'" />'
											.'<input type="hidden" name="type_id" value="'.$type_id.'" />'
											.'<input type="hidden" name="object_id" value="'.StorePatternsTypeObjectPair::PATTERN_STR_NO_REFERENCE.'" />'
											.'<input type="radio" name="'.$unique_id.'" id="y:data_pattern_pairs:store-0" value="'.$arr_result['identifier'].'" />'
											.'<span>N/A</span>'
										.'</label>
									</fieldset><fieldset>
										<label title="'.getLabel('inf_pattern_pair_ignore').'">'
											.'<input type="hidden" name="pattern_value" value="'.strEscapeHTML($str_pattern_value).'" />'
											.'<input type="hidden" name="type_id" value="'.$type_id.'" />'
											.'<input type="hidden" name="object_id" value="'.StorePatternsTypeObjectPair::PATTERN_STR_IGNORE.'" />'
											.'<input type="radio" name="'.$unique_id.'" id="y:data_pattern_pairs:store-0" value="'.$arr_result['identifier'].'"'.($arr_result['is_ignored'] ? ' checked="checked"' : '').' />'
											.'<span>N/A '.getLabel('lbl_ignore').'</span>'
										.'</label>
									</fieldset>
								</div></div>
							</li>';
						}
					
						$html .= '</ul></fieldset>
					</div>';
				}
			}
		}
		
		$html = '<h2>'.$str_header.'</h2>'
			.$html;
		
		return $html;
	}
	
	protected function createProcessTemplateStoreCheck($arr_result) {
		
		if ($arr_result['locked'] !== null) {

			$str_locked = '<ul><li>'.implode('</li><li>', $arr_result['locked']).'</li></ul>';
			
			Labels::setVariable('total', count($arr_result['locked']));
			$str_message = parseBody(getLabel('msg_ingest_stopped').' '.getLabel('msg_object_locked_multi')).parseBody($str_locked);
			
		} else if ($arr_result['error'] !== null) {
				
			$error_pointer_row = $arr_result['error'] + 1;
			Labels::setVariable('row_number', $error_pointer_row);				
			$str_message = parseBody(getLabel('msg_ingest_error_at_row_number'));
		} else {

			Labels::setVariable('count', (int)$arr_result['count']);
			Labels::setVariable('verb', ($arr_result['mode'] == IngestTypeObjects::MODE_UPDATE ? getLabel('lbl_updated') : getLabel('lbl_added')));
			
			$str_message = parseBody(getLabel('msg_ingest_done'));
		}
		
		if ($arr_template['use_log']) {
			
			$str_message .= parseBody(getLabel('msg_import_log_created').' <span class="a popup" id="y:data_import:log_template-'.$arr_template['id'].'">'.getLabel('lbl_import_log_open').'</span>');
		} 
		
		$html = '<section class="info attention body">'.$str_message.'</section>';
	
		return $html;
	}
	
	public static function css() {
		
		static $added = false; // Only add once
		
		if ($added) {
			return '';
		}
		$added = true;
		
		$return = '
			.ingest-source.template > div:first-of-type input[name$="[name]"],
			.ingest-source.template > div:first-of-type select[name$="[source_id]"],
			.ingest-source.template > div:first-of-type select[name$="[type_id]"] { width: 400px; } 
			.ingest-source.template > div:not(:first-of-type) select:nth-of-type(1),
			.ingest-source.template > div:not(:first-of-type) select:nth-of-type(3) { width: 200px; }
			.ingest-source.template fieldset > ul > li > label:first-child + * input[name*="[query_value]"],
			.ingest-source.template fieldset > ul > li > label:first-child + * input[name*="[filter_value]"] { width: 400px; }
			.ingest-source.template fieldset > ul > li > label:first-child + * input[name*=value_split] { width: 20px; }
			.ingest-source.template fieldset > ul > li > label:first-child + div > section:first-child { margin-top: 0px; }
			.ingest-source.template fieldset > ul > li > label:first-child + div > section:last-child { margin-bottom: 0px; }
			
			.ingest-source.template-process .options > fieldset > ul.results > li:nth-child(even) { background-color: rgba(0, 0, 0, 0.03); } 
			.ingest-source.template-process .options > fieldset > ul.results > li > label:before { display: none; } 
			.ingest-source.template-process .options > fieldset > ul.results > li > label { padding: 0 8px; text-align: right; vertical-align: middle;} 
			.ingest-source.template-process .options > fieldset > ul.results > li > .fieldsets > div { margin: -4px !important; } 
			.ingest-source.template-process .options > fieldset > ul.results > li > .fieldsets > div > fieldset { background-color: var(--back-nested); padding: 4px 8px; margin: 4px !important;} 
			.ingest-source.template-process .options > fieldset > ul.results > li > .fieldsets > div > fieldset label { line-height: 26px; }
		';

		return $return;
	}
	
	public static function js() {
		
		static $added = false; // Only add once
		
		if ($added) {
			return '';
		}
		$added = true;
		
		$return = "
		function IngestSource() {
			
			const SELF = this;
			
			this.host = false;
			this.port = false;
			
			let is_active = false;
			
			this.listen = function(host, port) {
							
				SELF.host = host;
				SELF.port = port;
			
				SELF.send = WEBSERVICES.register(host, port, '".WebServiceTaskIngestSource::$name."', {
					receive: SELF.receive,
					opened: function() { is_active = true; },
					closed: function() { is_active = false; }
				});
			};
			
			this.stop = function() {
				
				WEBSERVICES.unregister(SELF.host, SELF.port, '".WebServiceTaskIngestSource::$name."');
			};
			
			this.isActive = function() {
				
				return is_active;
			};
			
			this.send = function(data) {};

			var arr_workers = {};
			var arr_conversion_process_callbacks = {};
			var count_conversion_processes = 0;
			
			this.receive = function(data) {
								
				var arr_output = [];
				
				var func_output = function(num_conversion, OUTPUT) {
					
					var arr_conversion = data[num_conversion];
					
					arr_conversion.output = OUTPUT;
					
					delete arr_conversion.input;
					delete arr_conversion.script;
					
					arr_output.push(arr_conversion);

					func_done();
				};
				
				var num_process = count_conversion_processes;
				count_conversion_processes++;
				
				arr_conversion_process_callbacks[num_process] = func_output;
				var count = 0;
				var i = 0;
				
				var func_done = function() {
					
					count--;
				
					if (count > 0) {
						
						return;
					}

					delete arr_conversion_process_callbacks[num_process];
					
					SELF.send({arr_data: arr_output});
				};
								
				for (var i = 0, len = data.length; i < len; i++) {

					var arr_conversion = data[i];
					
					if (!arr_conversion) {
						continue;
					}
					
					count++;
					
					doConversion(num_process, i, arr_conversion.script, arr_conversion.input);
				}
			};

			var doConversion = function(num_process, num_conversion, str_script, INPUT) {
						
				var worker = arr_workers[str_script];
				
				if (!worker) {
			
					var func = `function() {
				
						var func_convert = function(INPUT) {
							
							`+str_script+`
							
							return OUTPUT;
						};
						
						var num_process = false;
						var num_conversion = false;
						
						self.onmessage = function(event) {
						
							num_process = event.data.num_process;
							num_conversion = event.data.num_conversion;
														
							var OUTPUT = func_convert(event.data.INPUT);
							
							self.postMessage({OUTPUT: OUTPUT, num_process: num_process, num_conversion: num_conversion});
						};
						
						self.onerror = function(event) {
							
							self.postMessage({OUTPUT: false, num_process: num_process, num_conversion: num_conversion});
						};
					}`;

					var worker = ASSETS.createWorker(func);
					
					var func_message = function(event) {
						
						var OUTPUT = event.data.OUTPUT;
						
						var func_call = arr_conversion_process_callbacks[event.data.num_process];
						
						func_call(event.data.num_conversion, OUTPUT);
					};
										
					worker.addEventListener('message', func_message);
					
					arr_workers[str_script] = worker;
				}

				worker.postMessage({INPUT: INPUT, num_process: num_process, num_conversion: num_conversion});
			};
		}
		var INGEST_SOURCE = new IngestSource();

		FEEDBACK.listen(function(data) {
			
			if (data.ingest_source !== undefined) {
				
				if (data.ingest_source) {
					INGEST_SOURCE.listen(data.ingest_source.host, data.ingest_source.port);
				} else {
					INGEST_SOURCE.stop();
				}
			}	
		});
				
		SCRIPTER.dynamic('ingest_source_template', function(elm_scripter) {
			
			elm_scripter.on('scripter', function(e) {
			
				if (!getElement(e.detail.elm)) {
					return;
				}
			
				runElementsSelectorFunction(e.detail.elm, '[name$=\"[source_id]\"]', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'init_data_template');
				});
			}).on('ajaxloaded', '[id^=y\\\:ingest_source\\\:set_pointers-filter_value], [id^=y\\\:ingest_source\\\:set_pointers-query_value]', function() {
			
				var cur = $(this);
				cur.closest('.filter-value, .query-value').removeClass('hide');
			}).on('ajaxloaded', '[id=y\\\:ingest_source\\\:set_pointers-filter_object_identifier], [id=y\\\:ingest_source\\\:set_pointers-filter_object_value]', function() {
			
				var cur = $(this);
				var elm_form = cur.closest('form');
				
				var elm_link_object_mode = elm_form.find('[name$=\"[link_object_mode]\"]:checked');
				SCRIPTER.triggerEvent(elm_link_object_mode, 'update_link_object_mode');
			}).on('ajaxloaded', '[id^=y\\\:ingest_source\\\:set_pointers-map]', function() {
			
				var cur = $(this);
				var elm_form = cur.closest('form');
				
				cur.closest('.map').removeClass('hide');
					
				var elm_template_mode = elm_form.find('[name$=\"[mode]\"]:checked');
				SCRIPTER.triggerEvent(elm_template_mode, 'update_template_mode');
			}).on('change', '[id^=y\\\:ingest_source\\\:create_pointer-] select', function() {
			
				var elm_pointer = $(this).parent();
				var arr_data = serializeArrayByName(elm_pointer);
				
				var str_name = FORMMANAGING.getElementNameBase(this);
				if (str_name) {
					arr_data = arrValueByKeyPath(str_name, arr_data);
					arr_data = arr_data.pointers;
					arr_data = arr_data[Object.keys(arr_data)[0]]; // Select map/filter_object_value/query_object_value
					arr_data = arr_data[Object.keys(arr_data)[0]]; // Select first (this) pointer
					arr_data = {pointer: arr_data};
				}
				
				arr_data.template_mode = elm_pointer.closest('form').find('[name$=\"[mode]\"]:checked').val();
				arr_data.form_name = str_name;
				
				COMMANDS.setData(elm_pointer, arr_data);
				COMMANDS.quickCommand(elm_pointer, function(data) {
				
					if (!data) {
						return;
					}
		
					elm_pointer.parent().next('li').html(data[1]);	
					elm_pointer.parent().html(data[0]);
				});
				
			}).on('change init_data_template', '[name$=\"[source_id]\"], [name$=\"[type_id]\"]', function(e) {

				var cur = $(this);
				var elm_form = cur.closest('form');
				var elm_source_selector = elm_form.find('[name$=\"[source_id]\"]');
				var elm_type_selector = elm_form.find('[name$=\"[type_id]\"]');
				var elm_template_mode = elm_form.find('[name$=\"[mode]\"]:checked');
				var elm_link_object_mode = elm_form.find('[name$=\"[link_object_mode]\"]:checked');
				var type_id = elm_type_selector.val();
				
				var source_id = elm_source_selector.val();

				if (!type_id || !source_id) {
			
					elm_form.find('[name$=\"[type_filter]\"] + [type=button], .map, .filter-value, .query-value, .link-object, .link-type-object').addClass('hide');
					elm_form.find('[name$=\"[mode]\"]').attr('disabled', true);
					
					return;
				} else {

					elm_form.find('[name$=\"[mode]\"]').attr('disabled', false);
				}
				
				if (e.type == 'init_data_template') {
					
					SCRIPTER.triggerEvent(elm_template_mode, 'update_template_mode');
				} else {
					
					elm_type_selector.next('[name$=\"[type_filter]\"]').val('');
					
					var template_mode = elm_template_mode.val();
					
					FEEDBACK.mergeRequests(true);
					
					runElementSelectorFunction(elm_form, '[id^=y\\\:ingest_source\\\:set_pointers-]:not([id$=query_type_object_value])', function(elm_found) {
						
						COMMANDS.setData(elm_found, {source_id: source_id, type_id: type_id, template_mode: template_mode, form_name: FORMMANAGING.getElementNameBase(elm_found)});
						COMMANDS.quickCommand(elm_found, function(elm) {
							
							$(elm_found).html(elm);
						});
					});
					
					FEEDBACK.mergeRequests(false);
				}
			}).on('change init_data_template', '[name$=\"[source_id]\"], [name$=\"[query_type_id]\"]', function(e) {

				var cur = $(this);
				var elm_form = cur.closest('form');
				var elm_type_selector = elm_form.find('[name$=\"[query_type_id]\"]');
				if (!elm_type_selector.length) {
					return;
				}
				var elm_source_selector = elm_form.find('[name$=\"[source_id]\"]');
				var elms_target = elm_type_selector.closest('li').nextAll('li');
				var type_id = elm_type_selector.val();
				var source_id = elm_source_selector.val();

				if (!type_id || !source_id) {
					elms_target.addClass('hide');
				} else {
					elms_target.removeClass('hide');
				}
				
				if (e.type == 'init_data_template') {
					return;
				}

				elm_type_selector.next('[name$=\"[query_type_filter]\"]').val('');
				
				runElementSelectorFunction(elm_form, '[id=y\\\:ingest_source\\\:set_pointers-query_type_object_value]', function(elm_found) {
					COMMANDS.setData(elm_found, {source_id: source_id, type_id: type_id, form_name: FORMMANAGING.getElementNameBase(elm_found)});
					COMMANDS.quickCommand(elm_found, function(elm) {
						
						$(elm_found).html(elm);
					});
				});
			}).on('change update_template_mode', '[name$=\"[mode]\"]', function() {	
			
				var cur = $(this);
				var num_mode = this.value;
				var elm_form = cur.closest('form');
				
				var elm_type_filter = elm_form.find('[name$=\"[type_filter]\"] + [type=button]');
				var elm_link_object = elm_form.find('.link-object');
				var elm_link_type_object = elm_form.find('.link-type-object');
				var elms_pointer_options = elm_form.find('.map').find('.pointer-options').parent('li');
				
				if (num_mode == '".IngestTypeObjects::MODE_UPDATE."') {
					elm_type_filter.removeClass('hide');
					elm_link_object.removeClass('hide');
					elms_pointer_options.removeClass('hide');
					elm_link_type_object.addClass('hide');
				} else if (num_mode == '".IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS."') {
					elm_type_filter.removeClass('hide');
					elm_link_object.removeClass('hide');
					elms_pointer_options.addClass('hide');
					elm_link_type_object.removeClass('hide');
				} else {
					elm_type_filter.addClass('hide');
					elm_link_object.addClass('hide');
					elms_pointer_options.addClass('hide');
					elm_link_type_object.removeClass('hide');
				}
				
				var elm_link_object_mode = elm_form.find('[name$=\"[link_object_mode]\"]:checked');
				SCRIPTER.triggerEvent(elm_link_object_mode, 'update_link_object_mode');
			}).on('change update_link_object_mode', '[name$=\"[link_object_mode]\"]', function() {
				
				var cur = $(this);
				
				var elm_template_mode = cur.closest('form').find('[name$=\"[mode]\"]:checked');
				var num_mode = elm_template_mode.val();
				
				var elms_link_object_mode = cur.closest('li').find('[name$=\"[link_object_mode]\"]');
				var elm_link_object_mode_query = elms_link_object_mode.filter('[value=query_object_value]');
				
				if (elm_link_object_mode_query.length) {
					
					if (num_mode == '".IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS."') {

						elm_link_object_mode_query.parent('label').addClass('hide');
						
						if (elm_link_object_mode_query[0].checked) {
							
							elm_link_object_mode_query[0].checked = false;
							elms_link_object_mode.first()[0].checked = true;
						}
					} else {
					
						elm_link_object_mode_query.parent('label').removeClass('hide');
					}
				}
				
				var elm_selected = elms_link_object_mode.filter(':checked');
				var str_link_object_mode = elm_selected.val();
				
				elm_selected.closest('li').nextAll('li').addClass('hide');
				var elm_ul = elm_selected.closest('ul');
				
				if (str_link_object_mode == 'filter_object_identifier') {
					
					let elms_target = elm_ul.find('[name*=\"[filter_object_identifier]\"]').closest('li');
					elms_target.removeClass('hide');
					
					if (num_mode == '".IngestTypeObjects::MODE_UPDATE."') {
						
						elms_target = elm_ul.find('[name*=\"[filter_object_sub_identifier]\"]').closest('li');
						if (elms_target.length) {
							elms_target.removeClass('hide');
							elms_target = elms_target.prev('li').children('section');
							if (elms_target.length) {
								elms_target.parent().removeClass('hide');
							}
						}
					}
				} else {
					let elms_target = elm_ul.find('[name*=\"['+str_link_object_mode+']\"]').first().closest('.sorter').closest('li');
					elms_target = elms_target.add(elms_target.prev('li'));
					elms_target.removeClass('hide');
				}
			}).on('keyup', '[name*=value_split]', function() {
				if($(this).val()) {
					$(this).next('select').removeClass('hide');
				} else {
					$(this).next('select').addClass('hide');
				}
			});
		});
		
		SCRIPTER.dynamic('.ingest-source.template', 'ingest_source_template');
		
		SCRIPTER.dynamic('.ingest-source.template', 'application_filter');
		
		SCRIPTER.dynamic('ingest_source_run_template', function(elm_scripter) {
			
			var elm_template_check = false;
			runElementSelectorFunction(elm_scripter, '.ingest-source.run', function(elm_found) {
				elm_template_check = $(elm_found).children('.options').last();
			});
			
			SCRIPTER.runDynamic(elm_template_check.children());
		
			elm_scripter.on('change', '[id^=y\\\:ingest_source\\\:check_template-]', function() {
			
				COMMANDS.quickCommand(this, elm_template_check);
			});
		});
		
		SCRIPTER.dynamic('.ingest-source.run', 'ingest_source_run_template');
				
		SCRIPTER.dynamic('.ingest-source.template-process', 'pattern_object_pair_store');
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT

		if ($method == "create_pointer") {

			$arr_pointer = $value['pointer'];
			
			$arr_ids = explode('_', $id);
			$source_id = $arr_ids[0];
			$type_id = $arr_ids[1];
			$use_as_filter = ($arr_ids[2] == 'filter' || $arr_ids[2] == 'query');
			
			$this->form_name = ($value['form_name'] ?: $this->form_name);
						
			if ($use_as_filter) {
				
				// Nothing available
				// $this->html = $this->createPointerFilterTypeObjectValue($source_id, $type_id, $arr_pointer);
				return;
			} else {
				$this->html = $this->createPointerMap($source_id, $type_id, $arr_pointer, ($value['template_mode'] == IngestTypeObjects::MODE_UPDATE));
			}
		}
		
		if ($method == "set_pointers") {

			if (!$value['source_id']) {
			
				$this->html = '';
				return;
			}
			
			$this->form_name = ($value['form_name'] ?: $this->form_name);
			
			if ($id == 'filter_object_identifier') {
				
				if (static::$use_object_identifier_uri) {
					if ($value['type_id']) {
						$this->html = $this->createPointerFilterTypeObjectURI($value['type_id']);
					}
				} else {
					$this->html = $this->createPointerFilterTypeObjectID($value['source_id']);
				}
			} else if ($id == 'filter_object_sub_identifier') {
				
				$this->html = $this->createPointerFilterTypeObjectSubID($value['source_id']);
			} else {
				
				$arr_sorter = [];
				
				if ($value['type_id']) {
						
					if ($id == 'map') {
						
						$arr_pointers = static::getPointerDataHeadings($value['source_id']);
						array_unshift($arr_pointers, []); // Empty run for sorter source
						
						foreach ($arr_pointers as $key => $arr_pointer) {
							
							$arr_pointer['pointer_heading'] = $arr_pointer['id'];
							
							$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $this->createPointerMap($value['source_id'], $value['type_id'], $arr_pointer, ($value['template_mode'] == IngestTypeObjects::MODE_UPDATE))];
						}
					} else {
						
						$arr_pointers = [[], []]; // Empty run for sorter source

						foreach ($arr_pointers as $key => $arr_pointer) {
							
							$html = '';
							
							switch ($id) {
								case 'filter_value':
									$html = $this->createPointerFilterValue($value['source_id'], $arr_pointer);
									break;
								case 'query_value':
									$html = $this->createPointerQueryValue($value['source_id'], $arr_pointer);
									break;
								case 'filter_object_value':
									$html = $this->createPointerFilterTypeObjectValue($value['source_id'], $value['type_id'], $arr_pointer);
									break;
								case 'query_object_value':
									$html = $this->createPointerQueryTypeObjectValue($value['source_id'], $value['type_id'], $arr_pointer, 'query_object_value');
									break;
								case 'query_type_object_value':
									$html = $this->createPointerQueryTypeObjectValue($value['source_id'], $value['type_id'], $arr_pointer, 'query_type_object_value');
									break;
							}
							
							$arr_sorter[] = ['source' => ($key === 0 ? true : false), 'value' => $html];
						}
					}
				}
				
				$this->html = cms_general::createSorter($arr_sorter, true);
			}
		}
		
		if ($method == "check_template") {

			$source_id = $value;
			$arr_template = static::getTemplate($id);
			
			$this->html = $this->createCheckTemplate($arr_template, $source_id);
		}
	}
	
	public static function parseTemplate($arr_template_raw) {
		
		$template_mode = (int)$arr_template_raw['mode'];
		if (!$template_mode) {
			$template_mode = (((bool)$arr_template_raw['pointers']['filter_object_value'] || (bool)$arr_template_raw['pointers']['filter_object_identifier'] || (bool)$arr_template_raw['pointers']['query_object_value']) ? IngestTypeObjects::MODE_UPDATE : IngestTypeObjects::MODE_OVERWRITE);
		}
		$template_link_object_mode = $arr_template_raw['link_object_mode'];
		
		$arr_template = [
			'mode' => $template_mode,
			'source_id' => (int)$arr_template_raw['source_id'],
			'type_id' => (int)$arr_template_raw['type_id'],
			'type_filter' => (($template_mode == IngestTypeObjects::MODE_UPDATE || $template_mode == IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS) && $arr_template_raw['type_filter'] ? JSON2Value($arr_template_raw['type_filter']) : ''),
			'pointers' => ['filter_object_identifier' => [], 'filter_object_sub_identifier' => [], 'map' => [], 'filter_object_value' => [], 'query_object_value' => [], 'query_type_object_value' => [], 'filter_value' => [], 'query_value' => []]
		];
		
		if ($arr_template['type_id']) {
			
			$arr_type = StoreType::getTypes($arr_template['type_id']);
			
			if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
				unset($arr_template['type_id']);
			}
			
		}
		
		if (static::$use_log) {
			$arr_template['use_log'] = (int)$arr_template_raw['use_log'];
		}
				
		foreach ($arr_template_raw['pointers'] as $type => $arr_type_pointers) {
			
			if (!$arr_type_pointers) {
				continue;
			}
							
			if (($template_mode == IngestTypeObjects::MODE_UPDATE || $template_mode == IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS) && $type == 'filter_object_identifier' && $template_link_object_mode == 'filter_object_identifier') {
				
				$arr_template['pointers']['filter_object_identifier'] = $arr_type_pointers;
				continue;
			} else if ($template_mode == IngestTypeObjects::MODE_UPDATE && $type == 'filter_object_sub_identifier' && $template_link_object_mode == 'filter_object_identifier') {
				
				$arr_template['pointers']['filter_object_sub_identifier'] = $arr_type_pointers;
				continue;
			} else if ($template_mode == IngestTypeObjects::MODE_UPDATE && (($type == 'filter_object_value' && $template_link_object_mode == 'filter_object_value' && static::$use_filter_object_value) || ($type == 'query_object_value' && $template_link_object_mode == 'query_object_value' && static::$use_query_object_value))) {
				// pass
			} else if ($template_mode == IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS && (($type == 'filter_object_value' && $template_link_object_mode == 'filter_object_value' && static::$use_filter_object_value))) {
				// pass
			} else if (($template_mode == IngestTypeObjects::MODE_OVERWRITE || $template_mode == IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS) && ($type == 'query_type_object_value' && static::$use_query_type_object_value) && $arr_template_raw['query_type_id']) {
				// pass
				$arr_template['query_type_id'] = (int)$arr_template_raw['query_type_id'];
				$arr_template['query_type_filter'] = json_decode($arr_template_raw['query_type_filter'], true);
			} else if ($type == 'map' || ($type == 'filter_value' && static::$use_filter_value) || ($type == 'query_value' && static::$use_query_value)) {
				// pass
			} else {
				continue;
			}
			
			foreach ($arr_type_pointers as $arr_pointer) {
				
				if (!$arr_pointer['pointer_heading'] || $arr_pointer['value'] === '') {
					continue;
				}
				
				$arr_template['pointers'][$type][] = $arr_pointer;
			}
		}
		
		return $arr_template;
	}
}
