<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ChangeTypeObjects extends StoreTypeObjects {
	
	const ACTION_APPEND = 'append';
	const ACTION_CHANGE = 'change';
	const ACTION_REPLACE = 'replace';
	const ACTION_REMOVE = 'remove';
	const ACTION_ADD = 'add';
		
	protected $arr_find_change = [];
	protected $arr_find_change_parsed = [];
	
	protected $arr_selection = [];
	protected $is_filtering = false;
	
    public function __construct($type_id, $object_id, $user_id) {
		
		parent::__construct($type_id, $object_id, $user_id);
    }
    
    public function setChange($arr_find_change, $func_parse_input, $arr_selection, $is_filtering, $project_id, $num_nodegoat_clearance = 0) {
		
		$this->arr_find_change = $arr_find_change;
		$this->arr_find_change_parsed = [];
		$this->arr_selection = $arr_selection;
		$this->is_filtering = (bool)$is_filtering;
		
		$arr_project = StoreCustomProject::getProjects($project_id);
		
		if (isset($this->arr_find_change['object']['name']) && !$this->arr_type_set['type']['use_object_name']) {
			unset($this->arr_find_change['object']['name']);
		}

		foreach ((array)$this->arr_find_change['object_descriptions'] as $object_description_id => $arr_selected) {
			
			if (!StoreType::checkTypeConfigurationUserClearance($this->arr_type_set, $num_nodegoat_clearance, $object_description_id, false, false, StoreType::CLEARANCE_PURPOSE_EDIT) || !StoreCustomProject::checkTypeConfigurationAccess($arr_project['types'], $this->arr_type_set, $object_description_id, false, false, StoreCustomProject::ACCESS_PURPOSE_EDIT)) {
				unset($this->arr_find_change['object_descriptions'][$object_description_id]);
			}
		}
		
		foreach ((array)$this->arr_find_change['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			if (!StoreType::checkTypeConfigurationUserClearance($this->arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, false, StoreType::CLEARANCE_PURPOSE_EDIT) || !StoreCustomProject::checkTypeConfigurationAccess($arr_project['types'], $this->arr_type_set, false, $object_sub_details_id, false, StoreCustomProject::ACCESS_PURPOSE_EDIT)) {
				unset($this->arr_find_change['object_sub_details'][$object_sub_details_id]);
				continue;
			}
			
			foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_selected) {
									
				if (!StoreType::checkTypeConfigurationUserClearance($this->arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, $object_sub_description_id, StoreType::CLEARANCE_PURPOSE_EDIT) || !StoreCustomProject::checkTypeConfigurationAccess($arr_project['types'], $this->arr_type_set, false, $object_sub_details_id, $object_sub_description_id, StoreCustomProject::ACCESS_PURPOSE_EDIT)) {
					unset($this->arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]);
				}
			}
			
			// Parse input
			
			if (!isset($arr_object_sub_details['object_sub_details']['object_sub'])) {
				continue;
			}
			
			$arr_input = ['object_sub' => []];
			
			foreach ($arr_object_sub_details['object_sub_details']['object_sub'] as $arr_selected) {
				
				if ($arr_selected['action'] == static::ACTION_ADD) {
					$arr_input['object_sub'][] = $arr_selected['values'];
				}
			}
			
			if ($arr_input['object_sub']) {
				
				$arr_input = $func_parse_input($arr_input);
				$this->arr_find_change_parsed['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_subs'] = $arr_input['object_subs'];
			}
			
			unset($this->arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub']);
		}
	}
    
	public function change($arr_object) {
		
		if ($this->is_filtering) {
			
			$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_STORAGE);
			$filter->setVersioning();
			$filter->setSelection($this->arr_selection);
			$filter->setFilter(['objects' => $this->object_id]);
			
			$arr_object_full = $filter->init();
			$arr_object_full = $arr_object_full[$this->object_id];
		} else {
						
			$arr_object_full = $arr_object;
		}
		
		$arr_object_store = ['object' => [], 'object_definitions' => [], 'object_subs' => []];
		
		if ($this->arr_find_change['object']['name']) {
			
			$arr_selected = $this->arr_find_change['object']['name'];
			
			$arr_object_store['object'] = $arr_object_full['object'];
			$arr_object_self_store =& $arr_object_store['object'];
			
			if ($arr_selected['action'] == static::ACTION_REMOVE) {
				
				$arr_object_self_store['object_name_plain'] = '';
			} else if ($arr_selected['action'] == static::ACTION_CHANGE) {
				
				$arr_object_self_store['object_name_plain'] = $arr_selected['values']['object_name_plain'];
			} else if ($arr_selected['action'] == static::ACTION_REPLACE) {
				
				$arr_regex = $arr_selected['values']['regex'];
				
				if ($arr_regex['enable']) {
					$arr_object_self_store['object_name_plain'] = strRegularExpression((string)$arr_object_self_store['object_name_plain'], $arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template']);
				} else {
					$arr_object_self_store['object_name_plain'] = str_replace($arr_regex['pattern'], $arr_regex['template'], (string)$arr_object_self_store['object_name_plain']);
				}
			}
		}
					
		foreach ((array)$this->arr_find_change['object_descriptions'] as $object_description_id => $arr_selected) {
			
			$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
						
			$arr_object_store['object_definitions'][$object_description_id] = $arr_object_full['object_definitions'][$object_description_id];
			$arr_object_definition_store =& $arr_object_store['object_definitions'][$object_description_id];

			if ($arr_object_description['object_description_has_multi']) {
				
				$key_object_definition = ($arr_object_description['object_description_ref_type_id'] ? 'object_definition_ref_object_id' : 'object_definition_value');
				
				if (variableHasValue($arr_selected['action'], static::ACTION_CHANGE, static::ACTION_REPLACE, static::ACTION_REMOVE)) {
					
					if ($this->is_filtering) {
						$arr_object_definition_store[$key_object_definition] = array_diff($arr_object_definition_store[$key_object_definition], $arr_object['object_definitions'][$object_description_id][$key_object_definition]);
					} else {
						$arr_object_definition_store[$key_object_definition] = [];
					}
				}
				
				if ($arr_selected['action'] == static::ACTION_CHANGE) {
					
					$arr_object_definition_store[$key_object_definition] = array_merge($arr_object_definition_store[$key_object_definition], (array)$arr_selected['values'][$key_object_definition]);
				} else if ($arr_selected['action'] == static::ACTION_REPLACE) {
					
					$arr_object_definition_values = $arr_object['object_definitions'][$object_description_id][$key_object_definition];
					$arr_regex = $arr_selected['values']['regex'];
					
					foreach ($arr_object_definition_values as &$str_value) {
						
						if ($arr_regex['enable']) {
							$str_value = strRegularExpression($str_value, $arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template']);
						} else {
							$str_value = str_replace($arr_regex['pattern'], $arr_regex['template'], $str_value);
						}
					}
					unset($str_value);
					
					$arr_object_definition_store[$key_object_definition] = array_merge($arr_object_definition_store[$key_object_definition], $arr_object_definition_values);
				} else if ($arr_selected['action'] == static::ACTION_APPEND) {
					
					$arr_object_definition_store[$key_object_definition] = (array)$arr_selected['values'][$key_object_definition];
				}
			} else {
				
				if ($arr_object_description['object_description_ref_type_id']) {
					
					if ($arr_selected['action'] == static::ACTION_REMOVE) {
						$arr_object_definition_store['object_definition_ref_object_id'] = '';
					} else if ($arr_selected['action'] == static::ACTION_CHANGE) {
						$arr_object_definition_store['object_definition_ref_object_id'] = $arr_selected['values']['object_definition_ref_object_id'];
					}
				} else {
					
					if ($arr_selected['action'] == static::ACTION_REMOVE) {
						
						$arr_object_definition_store['object_definition_value'] = '';
					} else if ($arr_selected['action'] == static::ACTION_CHANGE || $arr_selected['action'] == static::ACTION_APPEND) {
						
						$arr_object_definition_store['object_definition_value'] = $arr_selected['values']['object_definition_value'];
					} else if ($arr_selected['action'] == static::ACTION_REPLACE) {
						
						$arr_regex = $arr_selected['values']['regex'];
						
						if ($arr_regex['enable']) {
							$arr_object_definition_store['object_definition_value'] = strRegularExpression($arr_object_definition_store['object_definition_value'], $arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template']);
						} else {
							$arr_object_definition_store['object_definition_value'] = str_replace($arr_regex['pattern'], $arr_regex['template'], $arr_object_definition_store['object_definition_value']);
						}
					}
				}
			}
			
			if ($arr_selected['action'] == static::ACTION_REMOVE) {
				$arr_object_definition_store['object_definition_sources'] = false;
			} else if ($arr_selected['action'] == static::ACTION_CHANGE || $arr_selected['action'] == static::ACTION_APPEND) {
				$arr_object_definition_store['object_definition_sources'] = $arr_selected['values']['object_definition_sources'];
			}
		}
		
		foreach ((array)$this->arr_find_change['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
										
			if ($this->arr_find_change_parsed['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_subs']) { // Add sub-objects

				$arr_object_store['object_subs'] = array_merge($arr_object_store['object_subs'], $this->arr_find_change_parsed['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_subs']);
			}
			
			foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) { // Change or remove sub-objects
				
				if ($arr_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
					continue;
				}
				
				$arr_object_store['object_subs'][$object_sub_id] = $arr_object_full['object_subs'][$object_sub_id];
				$arr_object_sub_store =& $arr_object_store['object_subs'][$object_sub_id];
				unset($arr_object_sub_store['object_sub_sources']);
				unset($arr_object_sub_store['object_sub']['object_sub_date_type'], $arr_object_sub_store['object_sub']['object_sub_date_start'], $arr_object_sub_store['object_sub']['object_sub_date_end'], $arr_object_sub_store['object_sub']['object_sub_date_chronology']);
				unset($arr_object_sub_store['object_sub']['object_sub_location_type'], $arr_object_sub_store['object_sub']['object_sub_location_ref_object_id']);
				
				foreach ((array)$arr_object_sub_details['object_sub_details'] as $str_id => $arr_selected) {
					
					if ($str_id == 'object_sub_id') {
						
						if ($arr_selected['action'] == static::ACTION_REMOVE) {
							$arr_object_sub_store['object_sub']['object_sub_version'] = 'deleted';
						}
					}
					
					if ($str_id == 'object_sub_details_date_start' || $str_id == 'object_sub_details_date_end') {

						if ($str_id == 'object_sub_details_date_start') {

							if ($arr_selected['action'] == static::ACTION_REMOVE) {
								
								$arr_object_sub_store['object_sub']['object_sub_date_start']['chronology'] = '';
							} else if ($arr_selected['action'] == static::ACTION_CHANGE) {
								
								if ($arr_selected['values']['object_sub_date_start_infinite']) {
									$arr_selected['values']['object_sub_date_start'] = '-∞';
								}

								$arr_object_sub_store['object_sub']['object_sub_date_type'] = $arr_selected['values']['object_sub_date_type'];
								
								$arr_object_sub_store['object_sub']['object_sub_date_start'] = [
									'value' => $arr_selected['values']['object_sub_date_start'],
									'object_sub_id' => $arr_selected['values']['object_sub_date_object_sub_id'],
									'chronology' => $arr_selected['values']['object_sub_date_chronology']
								];
							}	
						} else {
							
							if ($arr_selected['action'] == static::ACTION_REMOVE) {
								
								$arr_object_sub_store['object_sub']['object_sub_date_end']['chronology'] = '';
							} else if ($arr_selected['action'] == static::ACTION_CHANGE) {
								
								if ($arr_selected['values']['object_sub_date_end_infinite']) {
									$arr_selected['values']['object_sub_date_end'] = '∞';
								}
								
								$arr_object_sub_store['object_sub']['object_sub_date_type'] = $arr_selected['values']['object_sub_date_type'];
								
								$arr_object_sub_store['object_sub']['object_sub_date_end'] = [
									'value' => $arr_selected['values']['object_sub_date_end'],
									'object_sub_id' => $arr_selected['values']['object_sub_date_object_sub_id'],
									'chronology' => $arr_selected['values']['object_sub_date_chronology']
								];
							}
						}		
					}
					
					if ($str_id == 'object_sub_details_date') {
						
						if ($arr_selected['action'] == static::ACTION_REMOVE) {
							
							$arr_object_sub_store['object_sub']['object_sub_date_chronology'] = '';
						} else if ($arr_selected['action'] == static::ACTION_CHANGE) {
							
							if ($arr_selected['values']['object_sub_date_start_infinite']) {
								$arr_selected['values']['object_sub_date_start'] = '-∞';
							}
							if ($arr_selected['values']['object_sub_date_end_infinite']) {
								$arr_selected['values']['object_sub_date_end'] = '∞';
							}
							
							$arr_object_sub_store['object_sub'] = $arr_selected['values'] + $arr_object_sub_store['object_sub'];
						}
					}
					
					if ($str_id == 'object_sub_details_location') {
						
						if ($arr_selected['action'] == static::ACTION_REMOVE) {
							
							$arr_object_sub_store['object_sub']['object_sub_location_type'] = 'reference';
							$arr_object_sub_store['object_sub']['object_sub_location_ref_object_id'] = false;
						} else if ($arr_selected['action'] == static::ACTION_CHANGE) {
							
							$arr_object_sub_store['object_sub'] = $arr_selected['values'] + $arr_object_sub_store['object_sub'];
						}
					}
				}
				
				foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_selected) {
					
					$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
										
					$arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id] = $arr_object_full['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id];
					$arr_object_sub_definition_store =& $arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id];

					if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
						
						if ($arr_selected['action'] == static::ACTION_REMOVE) {
							$arr_object_sub_definition_store['object_sub_definition_ref_object_id'] = '';
						} else if ($arr_selected['action'] == static::ACTION_CHANGE) {
							$arr_object_sub_definition_store['object_sub_definition_ref_object_id'] = $arr_selected['values']['object_sub_definition_ref_object_id'];
						}
					} else {
						
						if ($arr_selected['action'] == static::ACTION_REMOVE) {
							
							$arr_object_sub_definition_store['object_sub_definition_value'] = '';
						} else if ($arr_selected['action'] == static::ACTION_CHANGE || $arr_selected['action'] == static::ACTION_APPEND) {
							
							$arr_object_sub_definition_store['object_sub_definition_value'] = $arr_selected['values']['object_sub_definition_value'];
						} else if ($arr_selected['action'] == static::ACTION_REPLACE) {
							
							$arr_regex = $arr_selected['values']['regex'];
							
							if ($arr_regex['enable']) {
								$arr_object_sub_definition_store['object_sub_definition_value'] = strRegularExpression($arr_object_sub_definition_store['object_sub_definition_value'], $arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template']);
							} else {
								$arr_object_sub_definition_store['object_sub_definition_value'] = str_replace($arr_regex['pattern'], $arr_regex['template'], $arr_object_sub_definition_store['object_sub_definition_value']);
							}
						}
					}
					
					if ($arr_selected['action'] == static::ACTION_REMOVE) {
						$arr_object_sub_definition_store['object_sub_definition_sources'] = false;
					} else if ($arr_selected['action'] == static::ACTION_CHANGE || $arr_selected['action'] == static::ACTION_APPEND) {
						$arr_object_sub_definition_store['object_sub_definition_sources'] = $arr_selected['values']['object_sub_definition_sources'];
					}
				}
			}
		}
		
		$this->store($arr_object_store['object'], $arr_object_store['object_definitions'], $arr_object_store['object_subs']);
	}
	
	public static function parseTypeChange($type_id, $arr) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$arr_find_change = [];
		$arr_selection = ['object' => [], 'object_descriptions' => [], 'object_sub_details' => []];
		$arr_append = [];
		$arr_selection_user = [];
		
		foreach ($arr as $arr_selected) {
				
			if (!$arr_selected['id']) {
				continue;
			}

			$arr_selected['values'] = JSON2Value($arr_selected['values']);
			if ($arr_selected['values']) {
				$arr_selected['values'] = current(current($arr_selected['values']));
			}
			
			if ($arr_selected['id'] == 'name') {
				
				$arr_selection['object']['object_name_plain'] = true;
									
				if ($arr_selected['action'] == static::ACTION_REPLACE) {
					
					$arr_regex = $arr_selected['values']['regex'];
					if ($arr_regex['enable']) {
						$arr_regex = parseRegularExpression($arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template'], true);
						if ($arr_regex) {
							$arr_regex['enable'] = true;
						}
						$arr_selected['values']['regex'] = $arr_regex;
					} else {
						$arr_selected['values']['regex'] = ($arr_regex['pattern'] ? $arr_regex : false);
					}
				}
				
				$arr_find_change['object']['name'] = $arr_selected;
				$arr_selection_user[] = $arr_selected;
				
				continue;
			}
			
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
									
				$str_id = 'object_description_'.$object_description_id;
				
				if ($str_id != $arr_selected['id']) {
					continue;
				}
				
				$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
				
				if ($arr_selected['action'] == static::ACTION_APPEND) {
					$arr_append['object_definitions'][$object_description_id] = $object_description_id;
				} else {
					unset($arr_append['object_definitions'][$object_description_id]);
				}
				
				if ($arr_selected['action'] == static::ACTION_REPLACE) {
					
					$arr_regex = $arr_selected['values']['regex'];
					if ($arr_regex['enable']) {
						$arr_regex = parseRegularExpression($arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template'], true);
						if ($arr_regex) {
							$arr_regex['enable'] = true;
						}
						$arr_selected['values']['regex'] = $arr_regex;
					} else {
						$arr_selected['values']['regex'] = ($arr_regex['pattern'] ? $arr_regex : false);
					}
				}
				
				$arr_selected['values']['object_definition_sources'] = JSON2Value($arr_selected['values']['object_definition_sources']);
				
				$arr_find_change['object_descriptions'][$object_description_id] = $arr_selected;
				$arr_selection_user[] = $arr_selected;
				
				continue 2;
			}
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$str_id = 'object_sub_details_'.$object_sub_details_id;
				$has_match = $has_match_select = false;
				
				if ($str_id.'_id' == $arr_selected['id']) {
					
					if ($arr_selected['action'] == static::ACTION_ADD) {
						
						$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub'][] = $arr_selected;
						$has_match = true;
					} else {
						
						$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_id'] = $arr_selected;
						$has_match = $has_match_select = true;
					}
				} else if ($str_id.'_date_start' == $arr_selected['id']) {
					
					$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_date_start'] = $arr_selected;
					$has_match = $has_match_select = true;
				} else if ($str_id.'_date_end' == $arr_selected['id']) {
					
					$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_date_end'] = $arr_selected;
					$has_match = $has_match_select = true;
				} else if ($str_id.'_date' == $arr_selected['id']) {
					
					$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_date'] = $arr_selected;
					$has_match = $has_match_select = true;
				} else if ($str_id.'_location' == $arr_selected['id']) {
					
					$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_location'] = $arr_selected;
					$has_match = $has_match_select = true;
				}
				
				if ($has_match_select) {
					
					if (!isset($arr_selection['object_sub_details'][$object_sub_details_id])) {
						$arr_selection['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => true, 'object_sub_descriptions' => []];
					} else {
						$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = true;
					}
				}
				if ($has_match) {
					
					$arr_selection_user[] = $arr_selected;
					
					continue 2;
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$str_id = 'object_sub_description_'.$object_sub_description_id;
					
					if ($str_id != $arr_selected['id']) {
						continue;
					}
						
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
					
					if ($arr_selected['action'] == static::ACTION_APPEND) {
						$arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id] = $object_sub_description_id;
					} else {
						unset($arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]);
					}
					
					if ($arr_selected['action'] == static::ACTION_REPLACE) {
					
						$arr_regex = $arr_selected['values']['regex'];
						if ($arr_regex['enable']) {
							$arr_regex = parseRegularExpression($arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template'], true);
							if ($arr_regex) {
								$arr_regex['enable'] = true;
							}
							$arr_selected['values']['regex'] = $arr_regex;
						} else {
							$arr_selected['values']['regex'] = ($arr_regex['pattern'] ? $arr_regex : false);
						}
					}
					
					$arr_selected['values']['object_sub_definition_sources'] = JSON2Value($arr_selected['values']['object_sub_definition_sources']);
					
					$arr_find_change['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $arr_selected;
					$arr_selection_user[] = $arr_selected;
					
					continue 3;
				}
			}
		}
		
		return ['find_change' => $arr_find_change, 'selection' => $arr_selection, 'append' => $arr_append, 'selection_user' => $arr_selection_user];
	}
}
