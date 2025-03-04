<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreTypeObjectsReversals extends StoreTypeObjects {
		
	protected $arr_reversals = [];
	protected $date = false;
	protected $num_reversal_iterations_max = 5;
	protected $arr_updated_reversals_categories_referenced_types = [];
	protected $arr_passed_reversals_categories_referenced_types = [];
	protected $arr_skipped_reversals_categories = [];
	
	protected $num_reversal_iteration = 0;
	protected $arr_reversal_categories = [];
	protected $arr_changed_type_ids = []; // Initial changed Types
	protected $arr_check_type_ids = []; // Includes changed Types by Reversals
	protected $arr_push_reversal_category_ids = []; // Push dependent Reversal Categories to next iteration
	protected $arr_own_reversal_ids = []; // Track reciprocal dependency
	protected $arr_track_reversal_ids = []; // Track reciprocal dependency
	
	protected $arr_updateable_ids = [];
	protected $arr_updated_ids = [];
	protected $stmt_check_object_definitions = null;
	protected $stmt_update_object_definitions_old = null;
	protected $stmt_update_object_definitions_new = null;
	protected $stmt_check_object_definitions_objects = null;
	protected $stmt_update_object_definitions_objects_old = null;
	protected $stmt_update_object_definitions_objects_new = null;
	protected $stmt_update_object_definitions_texts_old = null;
	protected $stmt_update_object_definitions_texts_new = null;
	protected $stmt_check_object_sub_definitions = null;
	protected $stmt_update_object_sub_definitions_old = null;
	protected $stmt_update_object_sub_definitions_new = null;
	protected $stmt_check_object_subs_locations = null;
	protected $stmt_update_object_subs_locations_old = null;
	protected $stmt_update_object_subs_locations_new = null;
	protected $stmt_check_object_subs_locations_locked = null;
	protected $stmt_update_object_subs_locations_locked_old = null;
	protected $stmt_update_object_subs_locations_locked_new = null;
	
	protected $arr_cache_trace_type_network = [];
		
	public function __construct($arr_reversals, $date = false) {
		
		$this->arr_reversals = $arr_reversals;
		$this->date = $date;
	}

	public function run($num_iterations = null) {
		
		if (!$this->arr_reversals) {
			return;
		}
		
		if (isset($num_iterations)) {
			$this->num_reversal_iterations_max = (int)$num_iterations;
		}
		
		if ($this->date) {
			
			$this->arr_changed_type_ids = FilterTypeObjects::getTypesUpdatedAfter($this->date);
		} else {
			
			$this->arr_changed_type_ids = array_keys($this->arr_reversals);
			$this->arr_changed_type_ids = array_combine($this->arr_changed_type_ids, $this->arr_changed_type_ids);
		}
		
		if (!$this->arr_changed_type_ids) {
			return;
		}
		
		$this->arr_check_type_ids = $this->arr_changed_type_ids;
		
		$this->arr_updated_reversals_categories_referenced_types = [];
		$this->arr_passed_reversals_categories_referenced_types = [];
		$this->arr_skipped_reversals_categories = [];
		
		$this->num_reversal_iteration = 0;
		$this->arr_reversal_categories = [];
		
		// Get Categories
		
		foreach ($this->arr_reversals as $reversal_id => $arr_reversal) {
					
			$filter_categories = new FilterTypeObjects($reversal_id, GenerateTypeObjects::VIEW_ALL, false, false);
			$arr_categories = $filter_categories->init();
			
			if (!$arr_categories) {
				continue;
			}
			
			$this->arr_reversal_categories[$reversal_id] = $arr_categories;
		}
		
		if (!$this->arr_reversal_categories) {
			return;
		}
		
		// First run: prepare and apply
		
		$this->initReversalsUpdates();
		
		foreach ($this->arr_reversals as $reversal_id => $arr_reversal) {
			
			$this->setReversal($reversal_id);
			
			$this->updateReversal($reversal_id);
		}
		
		$this->arr_cache_trace_type_network = null; // Not needed anymore
		
		$this->checkReversalsChanges();

		//$this->updateReversalsChanges();
		
		// Iterate runs
		
		while ($this->arr_push_reversal_category_ids && $this->num_reversal_iteration < $this->num_reversal_iterations_max) {
			
			$this->num_reversal_iteration++;
			
			$arr_reversals = $this->arr_push_reversal_category_ids;
			$this->arr_push_reversal_category_ids = [];
			
			// Do apply
			
			foreach ($arr_reversals as $reversal_id => $arr_category_ids) {
			
				$this->setReversal($reversal_id);
				
				$this->updateReversal($reversal_id);
				//$this->updateReversalsChanges();
			}
		}
		
		// Done
		
		if ($this->num_reversal_iteration == $this->num_reversal_iterations_max) {
			
			$arr_debug = [];
			
			foreach ($this->arr_push_reversal_category_ids as $reversal_id => $arr_categories) {
				
				if (!$arr_categories) {
					continue;
				}
				
				$arr_debug[$reversal_id]['categories'] = array_keys($arr_categories);
			}
			
			msg(__METHOD__.' NOTICE: Needs more than '.$this->num_reversal_iterations_max.' iterations.' , false, LOG_BOTH, $arr_debug);
		}

		$count_types = 0;
		$count_passed_types = 0;
		$count_categories = 0;
		$count_referenced_types = 0;
		$count_skipped_categories = 0;
		
		foreach ($this->arr_updated_reversals_categories_referenced_types as $reversal_id => $arr_categories_referenced_types) {
			
			$count_types++;
			
			foreach ($arr_categories_referenced_types as $category_id => $arr_referenced_types) {
				
				$count_categories++;
				$count_referenced_types += count($arr_referenced_types);
			}
		}
		
		foreach ($this->arr_passed_reversals_categories_referenced_types as $reversal_id => $arr_categories_referenced_types) {
			
			$count_passed_types++;
		}
		
		foreach ($this->arr_skipped_reversals_categories as $reversal_id => $arr_categories) {
			
			$count_skipped_categories += count($arr_categories);
		}
		
		msg(__METHOD__.' SUCCESS:'
			.EOL_1100CC.'	Types = '.$count_types
				.' Categories = '.$count_categories
				.' Referenced Types = '.$count_referenced_types
			.EOL_1100CC.'	Ignored Types = '.$count_passed_types
			.($count_skipped_categories ? EOL_1100CC.'	Skipped Categories = '.$count_skipped_categories : '')
		);
	}
	
	protected function setReversal($reversal_id) {
		
		if (!isset($this->arr_reversal_categories[$reversal_id])) {
			return;
		}
		
		$arr_reversal = $this->arr_reversals[$reversal_id];
		
		$arr_types_all = StoreType::getTypes();
		$this->arr_reversals[$reversal_id]['scope_type_ids'] = array_keys($arr_types_all);

		$store_state = new StoreTypeObjects($reversal_id, false, false);

		foreach ($this->arr_reversal_categories[$reversal_id] as $category_id => &$arr_category) {
			
			$store_state->setObjectID($category_id);
			$bit_state = $store_state->getModuleState();
			
			if (bitHasMode($bit_state, StoreTypeObjects::MODULE_STATE_DISABLED)) {

				$this->arr_skipped_reversals_categories[$reversal_id][$category_id] = true;
				unset($this->arr_reversal_categories[$reversal_id][$category_id]);
				
				continue;
			}
			
			$referenced_type_id = 0;
			
			try {
				
				if ($this->num_reversal_iteration == 0) { // Prepare
					
					$arr_object_definition_value = $arr_category['object_definitions'][StoreType::getSystemTypeObjectDescriptionID(StoreType::getSystemTypeID('reversal'), 'module')];
					$arr_object_definition_value = ($arr_object_definition_value['object_definition_value'] ? json_decode($arr_object_definition_value['object_definition_value'], true) : []);
									
					foreach ($arr_object_definition_value as $referenced_type_id => $arr_object_type_filter) {
						
						$referenced_type_id = (int)$referenced_type_id;
						
						if (!isset($this->arr_reversals[$reversal_id]['types'][$referenced_type_id])) {
							$this->arr_reversals[$reversal_id]['types'][$referenced_type_id] = FilterTypeObjects::getTypesReferenced($reversal_id, $referenced_type_id, ['dynamic' => false]);
						}
						
						$this->prepareReversalCategoryReferencedType($reversal_id, $category_id, $referenced_type_id, $arr_object_type_filter);
					}
					
					if (!isset($arr_category['types'])) {
						
						unset($this->arr_reversal_categories[$reversal_id][$category_id]);
						continue;
					}
				}
				
				foreach ($arr_category['types'] as $referenced_type_id => $arr_referenced_type_settings) {
					
					$is_done = $this->setReversalCategoryReferencedType($reversal_id, $category_id, $referenced_type_id);
					
					if (!$is_done) {
						continue;
					}
					
					unset($arr_category['types'][$referenced_type_id]);
						
					// Cleanup
					GenerateTypeObjects::cleanResults();
				}
			} catch (Exception $e) {
				
				DB::rollbackTransaction(false);
				
				$store_state->updateModuleState(StoreTypeObjects::MODULE_STATE_DISABLED, BIT_MODE_ADD);
				
				error(__METHOD__.' ERROR:'.EOL_1100CC
					.'	Type = '.$reversal_id.' Category = '.$category_id.' Referenced Type = '.$referenced_type_id,
				TROUBLE_NOTICE, LOG_BOTH, false, $e); // Make notice
				
				// Cleanup
				GenerateTypeObjects::cleanResults();
			}
			
			if (empty($arr_category['types'])) {
				unset($this->arr_reversal_categories[$reversal_id][$category_id]);
			}
		}
		
		if (empty($this->arr_reversal_categories[$reversal_id])) {
			
			unset($this->arr_reversal_categories[$reversal_id]);
			
			if (isset($this->arr_track_reversal_ids[$reversal_id])) {
				
				foreach ($this->arr_track_reversal_ids[$reversal_id] as $related_reversal_id => $bool) {
					unset($this->arr_own_reversal_ids[$related_reversal_id][$reversal_id]);
				}
				unset($this->arr_track_reversal_ids[$reversal_id]);
			}
		}
	}
	
	public function getReversal($reversal_id) {
		
		return ($this->arr_reversals[$reversal_id] ?? null);
	}
	
	protected function prepareReversalCategoryReferencedType($reversal_id, $category_id, $referenced_type_id, $arr_object_type_filter) {
		
		$arr_reversal = $this->arr_reversals[$reversal_id];
		
		$num_mode = StoreTypeObjectReversalCategoryReferencedType::MODE_CLASSIFICATION;
		
		if ($arr_reversal['mode'] & StoreType::TYPE_MODE_REVERSAL_COLLECTION && $arr_object_type_filter['scope']) {
			
			$num_mode = StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION;
			if ($arr_object_type_filter['resource_path']) {
				$num_mode = StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION_RESOURCE_PATH;
			}
		}
		$has_filters = (bool)$arr_object_type_filter['filter'];
		
		$arr_referenced_type = $arr_reversal['types'][$referenced_type_id];

		if ((!$has_filters && $num_mode != StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION && $num_mode != StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION_RESOURCE_PATH) || !$arr_referenced_type) {
			return false;
		}
		
		$arr_scope_type_ids = $arr_reversal['scope_type_ids'];
		$arr_found_type_ids = [];
		
		$arr_filters = [];
		$filter = null;
		$do_check_filtering_object_sub = false;
		
		if ($has_filters) {
			
			$arr_filters = FilterTypeObjects::convertFilterInput($arr_object_type_filter['filter']);
			$do_check_filtering_object_sub = (bool)$arr_referenced_type['object_sub_details']; // Use filtering to specifically target sub-objects when applicable (being filtered on)

			$filter = new FilterTypeObjects($referenced_type_id, GenerateTypeObjects::VIEW_ALL, false, false);
			$filter->setScope(['types' => $arr_scope_type_ids]);
			if ($do_check_filtering_object_sub) {
				$filter->setFiltering(['all' => true]);
			}
			
			$filter->initFilteredTypes();
			$filter->setFilter($arr_filters);
			$arr_found_type_ids += $filter->getFilteredTypeIDs(true);
			$filter->initFilteredTypes(false);
		}
		
		$arr_scope = [];
		$num_clearance_type = static::getReferencedTypeClearance($referenced_type_id, $arr_referenced_type); // Get the required clearance of the referenced Type
		
		if ($num_mode == StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION) {
			$arr_scope = StoreType::parseTypeNetwork($arr_object_type_filter['scope'], false, $num_clearance_type);
		} else if ($num_mode == StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION_RESOURCE_PATH) {
			$arr_scope = StoreType::parseTypeNetworkModePick($arr_object_type_filter['scope'], $num_clearance_type);
		}

		if (($num_mode == StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION || $num_mode == StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION_RESOURCE_PATH) && $arr_scope['paths']) {
			
			sort($arr_scope_type_ids);
			$str_identifier = arr2String($arr_scope_type_ids, ',').'_'.value2JSON($arr_scope['paths']).'_'.$referenced_type_id;
			
			if (!isset($this->arr_cache_trace_type_network[$str_identifier])) {

				$trace = new TraceTypesNetwork($arr_scope_type_ids, true, true);
				$trace->filterTypesNetwork($arr_scope['paths']);
				$trace->run($referenced_type_id, false, cms_nodegoat_details::$num_network_trace_depth);
				
				$this->arr_cache_trace_type_network[$str_identifier] = [
					'network_paths' => $trace->getTypeNetworkPaths(true),
					'type_ids' => $trace->getFoundTypeIDs(true)
				];
			}
			
			$arr_type_network_paths = $this->arr_cache_trace_type_network[$str_identifier]['network_paths'];
			$arr_found_type_ids += $this->arr_cache_trace_type_network[$str_identifier]['type_ids'];
		} else {
			
			$arr_type_network_paths = ['start' => [$referenced_type_id => ['path' => [0]]]];
		}
		
		$str_resource_path = null;
		
		if ($num_mode == StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION_RESOURCE_PATH) {
			$str_resource_path = $arr_object_type_filter['resource_path'];
		}
		
		$arr_type_selection = [];
		
		if ($arr_referenced_type['object_descriptions']) {
			
			foreach ($arr_referenced_type['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$this->arr_updateable_ids['object_descriptions']['descriptions'][$object_description_id] = $object_description_id;
				$this->arr_updateable_ids['object_descriptions']['categories'][$category_id] = $category_id;
				
				if ($num_mode == StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION || $num_mode == StoreTypeObjectReversalCategoryReferencedType::MODE_COLLECTION_RESOURCE_PATH) {
					
					$this->arr_updateable_ids['object_description_objects']['descriptions'][$object_description_id] = $object_description_id;
					$this->arr_updateable_ids['object_description_objects']['categories'][$category_id] = $category_id;
					
					$this->arr_updateable_ids['object_description_texts']['descriptions'][$object_description_id] = $object_description_id;
					$this->arr_updateable_ids['object_description_texts']['categories'][$category_id] = $category_id;
				}
			}
		}
		
		if ($arr_referenced_type['object_sub_details']) {
			
			// No need to keep track of arr_updateable_ids as Sub-Object Descriptions can only be single reference
			
			if ($do_check_filtering_object_sub) {
				
				foreach ($arr_referenced_type['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					$arr_type_selection['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => false, 'object_sub_descriptions' => []];
					
					if ($arr_object_sub_details['object_sub_location'] && !$arr_object_sub_details['object_sub_location']['location_use_other']) {
						
						$arr_type_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = true;
					}
					
					foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $value) {
						
						$arr_type_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
					}
				}

				foreach ($arr_type_selection['object_sub_details'] as $object_sub_details_id => $value) {
					
					if (!$filter->isFilteringObjectSubDetails($object_sub_details_id)) { // Check if the sub-object is really filtered on and therefore is needed
						unset($arr_type_selection['object_sub_details'][$object_sub_details_id]);
					}
				}
			}
		}
		
		$category_referenced_type = new StoreTypeObjectReversalCategoryReferencedType($this, $reversal_id, $category_id, $referenced_type_id, $num_mode);
		$category_referenced_type->setConfiguration($arr_filters, $arr_type_network_paths, $arr_type_selection, $arr_scope['types'], $str_resource_path, $arr_found_type_ids);
		
		$this->arr_reversal_categories[$reversal_id][$category_id]['types'][$referenced_type_id] = $category_referenced_type;
		
		return true;
	}
	
	protected function setReversalCategoryReferencedType($reversal_id, $category_id, $referenced_type_id) {
		
		$category_referenced_type = $this->arr_reversal_categories[$reversal_id][$category_id]['types'][$referenced_type_id];

		$has_changed = false;
		$has_reversal = false;
		
		if (isset($this->arr_changed_type_ids[$reversal_id]) || isset($this->arr_changed_type_ids[$referenced_type_id])) { // If either have been changed excluding the Reversal changes
			$has_changed = true;
		}
		
		foreach ($category_referenced_type->getTypesFound() as $check_type_id) {
						
			if (isset($this->arr_reversals[$check_type_id]) && isset($this->arr_reversal_categories[$check_type_id])) { // If a used Type is a Reversal, and has still categories to be run, push current Reversal to next iteration
				
				if ($check_type_id == $reversal_id) { // The Reversal is making use of itself, run twice by keeping the instance once after it has run
					
					if ($this->num_reversal_iteration == 0 || count($this->arr_own_reversal_ids[$reversal_id]) > 1) {
						
						$this->arr_push_reversal_category_ids[$reversal_id][$category_id] = $category_id;
						$this->arr_own_reversal_ids[$reversal_id][$reversal_id] = true;
						
						$has_reversal = true;
					} else if (count($this->arr_own_reversal_ids[$reversal_id]) == 1) { // The Reversal has only references left pointing to itself
						
						$this->arr_push_reversal_category_ids[$reversal_id][$category_id] = $category_id;
					}
				} else {

					$this->arr_push_reversal_category_ids[$reversal_id][$category_id] = $category_id;
					$this->arr_own_reversal_ids[$reversal_id][$check_type_id] = true;
					$this->arr_track_reversal_ids[$check_type_id][$reversal_id] = true;
					
					if (!isset($this->arr_track_reversal_ids[$reversal_id][$check_type_id])) { // The Reversal is currently not used reciprocally
						$has_reversal = true;
					}
				}
			}
			
			if (isset($this->arr_check_type_ids[$check_type_id])) { // Check for any changes to the Type including Reversal changes
				$has_changed = true;
			}
		}
		
		if ($has_reversal) {
			
			if ($this->num_reversal_iteration < $this->num_reversal_iterations_max) {
				return false;
			}
		} else {
			unset($this->arr_push_reversal_category_ids[$reversal_id][$category_id]);
		}

		if (!$has_changed) {

			$this->arr_passed_reversals_categories_referenced_types[$reversal_id][$category_id][$referenced_type_id] = true;
			
			return true;
		}
		
		$category_referenced_type->run();
		
		$this->updateReversalCategoryReferencedType($category_id);

		$this->arr_updated_reversals_categories_referenced_types[$reversal_id][$category_id][$referenced_type_id] = true;
		
		return true;
	}
			
	public function triggerReversalCategoryObjectDefinitions($category_id, $object_description_id) {
		
		$this->arr_updated_ids['object_descriptions']['descriptions'][$object_description_id] = $object_description_id;
		$this->arr_updated_ids['object_descriptions']['categories'][$category_id] = $category_id;
	}
	
	public function triggerReversalCategoryObjectDefinitionsObjects($category_id, $object_description_id) {
		
		$this->arr_updated_ids['object_description_objects']['descriptions'][$object_description_id] = $object_description_id;
		$this->arr_updated_ids['object_description_objects']['categories'][$category_id] = $category_id;
	}
	
	public function triggerReversalCategoryObjectDefinitionsTexts($category_id, $object_description_id) {
		
		$this->arr_updated_ids['object_description_texts']['descriptions'][$object_description_id] = $object_description_id;
		$this->arr_updated_ids['object_description_texts']['categories'][$category_id] = $category_id;
	}
	
	public function triggerReversalObjectSubDefinitions($reversal_id, $object_sub_description_id) {
		
		$this->arr_updated_ids['object_sub_descriptions'][$reversal_id][$object_sub_description_id] = $object_sub_description_id;
	}
	
	public function triggerReversalObjectSubsLocations($reversal_id, $object_sub_details_id) {
		
		$this->arr_updated_ids['object_sub_details_locations'][$reversal_id][$object_sub_details_id] = $object_sub_details_id;
	}
	
	public function triggerReversalObjectSubsLocationsLocked($reversal_id, $object_sub_details_id) {
		
		$this->arr_updated_ids['object_sub_details_locations_locked'][$reversal_id][$object_sub_details_id] = $object_sub_details_id;
	}
	
	protected function updateReversalCategoryReferencedType($category_id) {
		
		DB::startTransaction('update_reversal_type_objects');
		
		if (isset($this->arr_updated_ids['object_descriptions']['descriptions'])) {
			
			foreach ($this->arr_updated_ids['object_descriptions']['descriptions'] as $object_description_id) {
				
				$this->stmt_check_object_definitions->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id]);
				$res = $this->stmt_check_object_definitions->execute();
				$arr_row = $res->fetchRow();
				
				if (isset($arr_row[0])) {
					$this->arr_check_type_ids[$arr_row[0]] = $arr_row[0];
				}
				
				$this->stmt_update_object_definitions_old->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id]);
				$this->stmt_update_object_definitions_old->execute();
				$this->stmt_update_object_definitions_new->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id]);
				$this->stmt_update_object_definitions_new->execute();
			}
			
			unset($this->arr_updated_ids['object_descriptions']['descriptions']);
		}
		
		if (isset($this->arr_updated_ids['object_description_objects']['descriptions'])) {
			
			foreach ($this->arr_updated_ids['object_description_objects']['descriptions'] as $object_description_id) {
				
				$this->stmt_check_object_definitions_objects->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id]);
				$res = $this->stmt_check_object_definitions_objects->execute();
				$arr_row = $res->fetchRow();
				
				if (isset($arr_row[0])) {
					$this->arr_check_type_ids[$arr_row[0]] = $arr_row[0];
				}
				
				$this->stmt_update_object_definitions_objects_old->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id]);
				$this->stmt_update_object_definitions_objects_old->execute();
				$this->stmt_update_object_definitions_objects_new->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id]);
				$this->stmt_update_object_definitions_objects_new->execute();
			}
			
			unset($this->arr_updated_ids['object_description_objects']['descriptions']);
		}
		
		if (isset($this->arr_updated_ids['object_description_texts']['descriptions'])) {
			
			foreach ($this->arr_updated_ids['object_description_texts']['descriptions'] as $object_description_id) {
				
				$this->stmt_update_object_definitions_texts_old->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id, 'category_id_alt' => $category_id, 'object_description_id_alt' => $object_description_id]);
				$this->stmt_update_object_definitions_texts_old->execute();
				$this->stmt_update_object_definitions_texts_new->bindParameters(['category_id' => $category_id, 'object_description_id' => $object_description_id, 'category_id_alt' => $category_id, 'object_description_id_alt' => $object_description_id]);
				$this->stmt_update_object_definitions_texts_new->execute();
			}
			
			unset($this->arr_updated_ids['object_description_texts']['descriptions']);
		}
		
		DB::commitTransaction('update_reversal_type_objects');
	}

	protected function updateReversal($reversal_id) {
		
		if (isset($this->arr_reversal_categories[$reversal_id])) { // Not finished yet
			return false;
		}
		
		DB::startTransaction('update_reversal_type_objects');
		
		if (isset($this->arr_updated_ids['object_sub_descriptions'][$reversal_id])) {
			
			foreach ($this->arr_updated_ids['object_sub_descriptions'][$reversal_id] as $object_sub_description_id) {
				
				$this->stmt_check_object_sub_definitions->bindParameters(['object_sub_description_id' => $object_sub_description_id]);
				$res = $this->stmt_check_object_sub_definitions->execute();
				$arr_row = $res->fetchRow();
				
				if (isset($arr_row[0])) {
					$this->arr_check_type_ids[$arr_row[0]] = $arr_row[0];
				}
				
				$this->stmt_update_object_sub_definitions_old->bindParameters(['object_sub_description_id' => $object_sub_description_id]);
				$this->stmt_update_object_sub_definitions_old->execute();
				$this->stmt_update_object_sub_definitions_new->bindParameters(['object_sub_description_id' => $object_sub_description_id]);
				$this->stmt_update_object_sub_definitions_new->execute();
			}
			
			unset($this->arr_updated_ids['object_sub_descriptions'][$reversal_id]);
		}
		
		if (isset($this->arr_updated_ids['object_sub_details_locations'][$reversal_id])) {
			
			foreach ($this->arr_updated_ids['object_sub_details_locations'][$reversal_id] as $object_sub_details_id) {
				
				$this->stmt_check_object_subs_locations->bindParameters(['reversal_id' => $reversal_id, 'object_sub_details_id' => $object_sub_details_id]);
				$res = $this->stmt_check_object_subs_locations->execute();
				$arr_row = $res->fetchRow();
				
				if (isset($arr_row[0])) {
					$this->arr_check_type_ids[$arr_row[0]] = $arr_row[0];
				}
				
				$this->stmt_update_object_subs_locations_old->bindParameters(['reversal_id' => $reversal_id, 'object_sub_details_id' => $object_sub_details_id]);
				$this->stmt_update_object_subs_locations_old->execute();
				$this->stmt_update_object_subs_locations_new->bindParameters(['reversal_id' => $reversal_id, 'object_sub_details_id' => $object_sub_details_id]);
				$this->stmt_update_object_subs_locations_new->execute();
			}
			
			unset($this->arr_updated_ids['object_sub_details_locations'][$reversal_id]);
		}
		
		if (isset($this->arr_updated_ids['object_sub_details_locations_locked'][$reversal_id])) {
			
			foreach ($this->arr_updated_ids['object_sub_details_locations_locked'][$reversal_id] as $object_sub_details_id) {
				
				$this->stmt_check_object_subs_locations_locked->bindParameters(['object_sub_details_id' => $object_sub_details_id]);
				$res = $this->stmt_check_object_subs_locations_locked->execute();
				$arr_row = $res->fetchRow();
				
				if (isset($arr_row[0])) {
					$this->arr_check_type_ids[$arr_row[0]] = $arr_row[0];
				}
				
				$this->stmt_update_object_subs_locations_locked_old->bindParameters(['object_sub_details_id' => $object_sub_details_id]);
				$this->stmt_update_object_subs_locations_locked_old->execute();
				$this->stmt_update_object_subs_locations_locked_new->bindParameters(['object_sub_details_id' => $object_sub_details_id]);
				$this->stmt_update_object_subs_locations_locked_new->execute();
			}
			
			unset($this->arr_updated_ids['object_sub_details_locations_locked'][$reversal_id]);
		}
		
		DB::commitTransaction('update_reversal_type_objects');
		
		return true;
	}
	
	protected function initReversalsUpdates() {
		
		$sql_table_name_references = StoreType::getValueTypeTable('reversed_classification');
		$sql_table_name_text = StoreType::getValueTypeTable('reversed_collection', 'name');
		$sql_value_text = StoreType::getValueTypeValue('reversed_collection', 'name');
		
		// Statements used to update Reversal Categories
		
		$this->stmt_check_object_definitions = DB::prepare("SELECT nodegoat_to.type_id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_references." nodegoat_to_def
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id)
			WHERE object_description_id = ".DBStatement::assign('object_description_id', 'i')."
				AND nodegoat_to_def.ref_object_id = ".DBStatement::assign('category_id', 'i')."
				AND (
					(nodegoat_to_def.active = TRUE AND nodegoat_to_def.status = 0)
						OR
					(nodegoat_to_def.active = TRUE AND nodegoat_to_def.status = 10)
				)
			LIMIT 1
		");
		$this->stmt_update_object_definitions_old = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_references." SET
				active = FALSE
			WHERE object_description_id = ".DBStatement::assign('object_description_id', 'i')."
				AND ref_object_id = ".DBStatement::assign('category_id', 'i')."
				AND active = TRUE
				AND status = 0
		");
		$this->stmt_update_object_definitions_new = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_references." SET
				status = 0
			WHERE object_description_id = ".DBStatement::assign('object_description_id', 'i')."
				AND ref_object_id = ".DBStatement::assign('category_id', 'i')."
				AND active = TRUE
				AND status >= 10
		");
		
		$this->stmt_check_object_definitions_objects = DB::prepare("SELECT nodegoat_to.type_id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id)
			WHERE nodegoat_to_def_ref.object_description_id = ".DBStatement::assign('object_description_id', 'i')."
				AND nodegoat_to_def_ref.identifier = ".DBStatement::assign('category_id', 'i')."
				AND (
					(nodegoat_to_def_ref.state = 1)
						OR
					(nodegoat_to_def_ref.state = 2)
				)
			LIMIT 1
		");
		$this->stmt_update_object_definitions_objects_old = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." SET
				state = 0
			WHERE object_description_id = ".DBStatement::assign('object_description_id', 'i')."
				AND identifier = ".DBStatement::assign('category_id', 'i')."
				AND state = 1
		");
		$this->stmt_update_object_definitions_objects_new = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." SET
				state = 1
			WHERE object_description_id = ".DBStatement::assign('object_description_id', 'i')."
				AND identifier = ".DBStatement::assign('category_id', 'i')."
				AND state >= 2
		");
				
		// Check already mainly (non-text based) handled by object_description_objects
		/*$this->stmt_check_object_definitions_texts = DB::prepare("SELECT nodegoat_to.type_id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_text." nodegoat_to_def
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id)
			WHERE object_description_id = ".DBStatement::assign('object_description_id', 'i')."
				AND nodegoat_to_def.identifier != ".DBStatement::assign('category_id', 'i')."
				AND (
					(nodegoat_to_def.active = FALSE AND nodegoat_to_def.status = 0 AND nodegoat_to_def.version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE." AND nodegoat_to_def.".$sql_table_name_text." IS NOT NULL)
						OR
					(nodegoat_to_def.active = FALSE AND nodegoat_to_def.status = 10 AND nodegoat_to_def.version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE.")
				)
			LIMIT 1
		");*/
		$this->stmt_update_object_definitions_texts_old = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_text." SET
				".$sql_value_text." = NULL
			WHERE (
					object_description_id = ".DBStatement::assign('object_description_id', 'i')."
					AND identifier = ".DBStatement::assign('category_id', 'i')."
					AND active = TRUE
					AND status = 0
					AND version = 1
				) OR (
					object_description_id = ".DBStatement::assign('object_description_id_alt', 'i')."
					AND identifier = ".DBStatement::assign('category_id_alt', 'i')."
					AND active = FALSE
					AND status = 0
					AND version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE."
				)
		");
		$this->stmt_update_object_definitions_texts_new = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_text." SET
				status = 0
			WHERE (
					object_description_id = ".DBStatement::assign('object_description_id', 'i')."
					AND identifier = ".DBStatement::assign('category_id', 'i')."
					AND active = TRUE
					AND status >= 10
					AND version = 1
				) OR (
					object_description_id = ".DBStatement::assign('object_description_id_alt', 'i')."
					AND identifier = ".DBStatement::assign('category_id_alt', 'i')."
					AND active = FALSE
					AND status >= 10
					AND version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE."
				)
		");
		
		// Statements used to update whole Reversals
		
		$this->stmt_check_object_sub_definitions = DB::prepare("SELECT nodegoat_to.type_id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_name_references." nodegoat_tos_def
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
			WHERE nodegoat_tos_def.object_sub_description_id = ".DBStatement::assign('object_sub_description_id', 'i')."
				AND (
					(nodegoat_tos_def.active = TRUE AND nodegoat_tos_def.status = 0)
						OR
					(nodegoat_tos_def.active = TRUE AND nodegoat_tos_def.status = 10)
				)
			LIMIT 1
		");
		$this->stmt_update_object_sub_definitions_old = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_name_references." SET
				active = FALSE
			WHERE object_sub_description_id = ".DBStatement::assign('object_sub_description_id', 'i')."
				AND active = TRUE
				AND status = 0
		");
		$this->stmt_update_object_sub_definitions_new = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_name_references." SET
				status = 0
			WHERE object_sub_description_id = ".DBStatement::assign('object_sub_description_id', 'i')."
				AND active = TRUE
				AND status >= 10
		");
		
		$this->stmt_check_object_subs_locations = DB::prepare("SELECT nodegoat_to.type_id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
			WHERE object_sub_details_id = ".DBStatement::assign('object_sub_details_id', 'i')."
				AND location_ref_type_id IN (0, ".DBStatement::assign('reversal_id', 'i').")
				AND (
					(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 10 AND location_ref_object_id != 0)
						OR
					(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 20)
				)
			LIMIT 1
		");
		$this->stmt_update_object_subs_locations_old = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
				location_ref_object_id = 0
			WHERE object_sub_details_id = ".DBStatement::assign('object_sub_details_id', 'i')."
				AND active = TRUE
				AND status < 10
				AND location_ref_type_id IN (0, ".DBStatement::assign('reversal_id', 'i').")
		");
		$this->stmt_update_object_subs_locations_new = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
				status = status % 10
			WHERE object_sub_details_id = ".DBStatement::assign('object_sub_details_id', 'i')."
				AND active = TRUE
				AND status >= 10
				AND location_ref_type_id IN (0, ".DBStatement::assign('reversal_id', 'i').")
		");
		
		$this->stmt_check_object_subs_locations_locked = DB::prepare("SELECT nodegoat_to.type_id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
			WHERE object_sub_details_id = ".DBStatement::assign('object_sub_details_id', 'i')."
				AND (
					(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 10 AND location_ref_object_id != 0)
						OR
					(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 20)
				)
			LIMIT 1
		");
		$this->stmt_update_object_subs_locations_locked_old = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
				location_ref_object_id = 0
			WHERE object_sub_details_id = ".DBStatement::assign('object_sub_details_id', 'i')."
				AND active = TRUE
				AND status < 10
		");
		$this->stmt_update_object_subs_locations_locked_new = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
				status = status % 10
			WHERE object_sub_details_id = ".DBStatement::assign('object_sub_details_id', 'i')."
				AND active = TRUE
				AND status >= 10
		");
	}
	
	protected function checkReversalsChanges() {
		
		// Find and cleanup any obsolete Reversals that target reiterable multi-values (i.e. Object Descriptions)
		
		$arr_sql_check = [];
		$sql_update = '';
		
		if (isset($this->arr_updateable_ids['object_descriptions'])) {
			
			$sql_description_ids = implode(',', $this->arr_updateable_ids['object_descriptions']['descriptions']);
			$sql_category_ids = implode(',', $this->arr_updateable_ids['object_descriptions']['categories']);
			
			$sql_table_name_affix = StoreType::getValueTypeTable('reversed_classification');
			
			$arr_sql_check[] = "SELECT nodegoat_to.type_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." nodegoat_to_def
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id)
				WHERE object_description_id IN (".$sql_description_ids.")
					AND nodegoat_to_def.ref_object_id NOT IN (".$sql_category_ids.")
					AND (nodegoat_to_def.active = TRUE AND nodegoat_to_def.status = 0)
				GROUP BY nodegoat_to.type_id
			";
			
			$sql_update .= "
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." SET
						active = FALSE
					WHERE object_description_id IN (".$sql_description_ids.")
						AND ref_object_id NOT IN (".$sql_category_ids.")
						AND active = TRUE
						AND status = 0
				;
			";
			
			if (isset($this->arr_updateable_ids['object_description_objects'])) {
				
				$sql_description_ids = implode(',', $this->arr_updateable_ids['object_description_objects']['descriptions']);
				$sql_category_ids = implode(',', $this->arr_updateable_ids['object_description_objects']['categories']);
				
				$arr_sql_check[] = "SELECT nodegoat_to.type_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id)
					WHERE nodegoat_to_def_ref.object_description_id IN (".$sql_description_ids.")
						AND nodegoat_to_def_ref.identifier NOT IN (".$sql_category_ids.")
						AND (nodegoat_to_def_ref.state = 1)
					GROUP BY nodegoat_to.type_id
				";
				
				$sql_update .= "
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." SET
							state = 0
						WHERE object_description_id IN (".$sql_description_ids.")
							AND identifier NOT IN (".$sql_category_ids.")
							AND state = 1
					;
				";
				
				if (isset($this->arr_updateable_ids['object_description_texts'])) {
					
					$sql_description_ids = implode(',', $this->arr_updateable_ids['object_description_texts']['descriptions']);
					$sql_category_ids = implode(',', $this->arr_updateable_ids['object_description_texts']['categories']);
					
					$sql_value_text = StoreType::getValueTypeValue('reversed_collection', 'name');
					$sql_table_name_affix = StoreType::getValueTypeTable('reversed_collection', 'name');
					
					// Check already mainly (non-text based) handled by object_description_objects
					
					/*$arr_sql_check[] = "SELECT nodegoat_to.type_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." nodegoat_to_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id)
						WHERE nodegoat_to_def.object_description_id IN (".$sql_description_ids.")
							AND nodegoat_to_def.identifier NOT IN (".$sql_category_ids.")
							AND (nodegoat_to_def.active = FALSE AND nodegoat_to_def.status = 0 AND nodegoat_to_def.version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE." AND nodegoat_to_def.".$sql_value_text." IS NOT NULL)
						GROUP BY nodegoat_to.type_id
					";*/
								
					$sql_update .= "
						UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." SET
								".$sql_value_text." = NULL
							WHERE (
								object_description_id IN (".$sql_description_ids.")
								AND identifier NOT IN (".$sql_category_ids.")
								AND active = TRUE
								AND status = 0
								AND version = 1
							) OR (
								object_description_id IN (".$sql_description_ids.")
								AND identifier NOT IN (".$sql_category_ids.")
								AND active = FALSE
								AND status = 0
								AND version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE."
							)
						;
					";
				}
			}
		}

		$arr_changed_type_ids = [];
		
		if ($arr_sql_check) {
			
			$res = DB::query("SELECT DISTINCT type_id
				FROM (
					(".implode(") UNION ALL (", $arr_sql_check).")
				) AS foo
			");
			
			while ($arr_row = $res->fetchRow()) {
				
				$arr_changed_type_ids[(int)$arr_row[0]] = (int)$arr_row[0];
			}
		}
		
		if ($sql_update) {
			
			$res = DB::queryMulti($sql_update);
		}
		

		$this->arr_updateable_ids = null; // Not needed anymore
		$this->arr_check_type_ids += $arr_changed_type_ids;
		
		return $arr_changed_type_ids;
	}
	
	protected function updateReversalsChanges() { // Legacy
		
		$arr_sql_check = [];
		$sql_update = '';
		
		if (isset($this->arr_updated_ids['object_descriptions'])) {
			
			$sql_description_ids = implode(',', $this->arr_updated_ids['object_descriptions']['descriptions']);
			$sql_category_ids = implode(',', $this->arr_updated_ids['object_descriptions']['categories']);
			
			$sql_table_name_affix = StoreType::getValueTypeTable('reversed_classification');
			
			$arr_sql_check[] = "SELECT nodegoat_to.type_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." nodegoat_to_def
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id)
				WHERE object_description_id IN (".$sql_description_ids.")
					AND nodegoat_to_def.ref_object_id IN (".$sql_category_ids.")
					AND (
						(nodegoat_to_def.active = TRUE AND nodegoat_to_def.status = 0)
							OR
						(nodegoat_to_def.active = TRUE AND nodegoat_to_def.status = 10)
					)
				GROUP BY nodegoat_to.type_id
			";
			
			$sql_update .= "
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." SET
						active = FALSE
					WHERE object_description_id IN (".$sql_description_ids.")
						AND ref_object_id IN (".$sql_category_ids.")
						AND active = TRUE
						AND status = 0
				;
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." SET
						status = 0
					WHERE object_description_id IN (".$sql_description_ids.")
						AND ref_object_id IN (".$sql_category_ids.")
						AND active = TRUE
						AND status >= 10
				;
			";
			
			if (isset($this->arr_updated_ids['object_description_objects'])) {
				
				$sql_description_ids = implode(',', $this->arr_updated_ids['object_description_objects']['descriptions']);
				$sql_category_ids = implode(',', $this->arr_updated_ids['object_description_objects']['categories']);
				
				$arr_sql_check[] = "SELECT nodegoat_to.type_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id)
					WHERE nodegoat_to_def_ref.object_description_id IN (".$sql_description_ids.")
						AND nodegoat_to_def_ref.identifier IN (".$sql_category_ids.")
						AND (
							(nodegoat_to_def_ref.state = 1)
								OR
							(nodegoat_to_def_ref.state = 2)
						)
					GROUP BY nodegoat_to.type_id
				";
				
				$sql_update .= "
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." SET
							state = 0
						WHERE object_description_id IN (".$sql_description_ids.")
							AND identifier IN (".$sql_category_ids.")
							AND state = 1
					;
					UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." SET
							state = 1
						WHERE object_description_id IN (".$sql_description_ids.")
							AND identifier IN (".$sql_category_ids.")
							AND state >= 2
					;
				";
				
				if (isset($this->arr_updated_ids['object_description_texts'])) {
					
					$sql_description_ids = implode(',', $this->arr_updated_ids['object_description_texts']['descriptions']);
					$sql_category_ids = implode(',', $this->arr_updated_ids['object_description_texts']['categories']);
					
					$sql_value_text = StoreType::getValueTypeValue('reversed_collection', 'name');
					$sql_table_name_affix = StoreType::getValueTypeTable('reversed_collection', 'name');
					
					// Check already mainly (non-text based) handled by object_description_objects
					
					/*$arr_sql_check[] = "SELECT nodegoat_to.type_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." nodegoat_to_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id)
						WHERE object_description_id IN (".$sql_description_ids.")
							AND nodegoat_to_def.identifier NOT IN (".$sql_category_ids.")
							AND (
								(nodegoat_to_def.active = FALSE AND nodegoat_to_def.status = 0 AND nodegoat_to_def.version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE." AND nodegoat_to_def.".$sql_value_text." IS NOT NULL)
									OR
								(nodegoat_to_def.active = FALSE AND nodegoat_to_def.status = 10 AND nodegoat_to_def.version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE.")
							)
						GROUP BY nodegoat_to.type_id
					";*/
											
					$sql_update .= "
						UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." SET
								".$sql_value_text." = NULL
							WHERE object_description_id IN (".$sql_description_ids.")
								AND identifier IN (".$sql_category_ids.")
								AND active = FALSE
								AND status = 0
								AND version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE."
						;
						UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_affix." SET
								status = 0
							WHERE object_description_id IN (".$sql_description_ids.")
								AND identifier IN (".$sql_category_ids.")
								AND active = FALSE
								AND status >= 10
								AND version = ".static::VERSION_OFFSET_ALTERNATE_ACTIVE."
						;
					";
				}
			}
		}
		
		if (isset($this->arr_updated_ids['object_sub_descriptions'])) {
			
			$arr_all_object_sub_description_ids = [];
			
			foreach ($this->arr_updated_ids['object_sub_descriptions'] as $reversal_id => $arr_object_sub_description_ids) {

				foreach ($arr_object_sub_description_ids as $object_sub_description_id) {
					$arr_all_object_sub_description_ids[$object_sub_description_id] = $object_sub_description_id;
				}
			}
			
			$sql_description_ids = implode(',', $arr_all_object_sub_description_ids);
			
			$sql_table_name_affix = StoreType::getValueTypeTable('reversed_classification');
			
			$arr_sql_check[] = "SELECT nodegoat_to.type_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_name_affix." nodegoat_tos_def
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
				WHERE nodegoat_tos_def.object_sub_description_id IN (".$sql_description_ids.")
					AND (
						(nodegoat_tos_def.active = TRUE AND nodegoat_tos_def.status = 0)
							OR
						(nodegoat_tos_def.active = TRUE AND nodegoat_tos_def.status = 10)
					)
				GROUP BY nodegoat_to.type_id
			";
			
			$sql_update .= "
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_name_affix." SET
						active = FALSE
					WHERE object_sub_description_id IN (".$sql_description_ids.")
						AND active = TRUE
						AND status = 0
				;	
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_name_affix." SET
						status = 0
					WHERE object_sub_description_id IN (".$sql_description_ids.")
						AND active = TRUE
						AND status >= 10
				;
			";
		}
		
		if (isset($this->arr_updated_ids['object_sub_details_locations'])) { // Only update sub-objects that are specifically selected for location reversal 
			
			$arr_reversal_ids = [];
			$arr_all_object_sub_details_ids = [];
			
			foreach ($this->arr_updated_ids['object_sub_details_locations'] as $reversal_id => $arr_object_sub_details_ids) {
			
				$arr_reversal_ids[] = $reversal_id;
				foreach ($arr_object_sub_details_ids as $object_sub_details_id) {
					$arr_all_object_sub_details_ids[$object_sub_details_id] = $object_sub_details_id;
				}
			}
			
			$sql_description_ids = implode(',', $arr_all_object_sub_details_ids);
			
			$arr_sql_check[] = "SELECT nodegoat_to.type_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
				WHERE object_sub_details_id IN (".$sql_description_ids.")
					AND location_ref_type_id IN (0, ".implode(',', $arr_reversal_ids).")
					AND (
						(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 10 AND location_ref_object_id != 0)
							OR
						(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 20)
					)
				GROUP BY nodegoat_to.type_id
			";
						
			$sql_update .= "
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
						location_ref_object_id = 0
					WHERE object_sub_details_id IN (".$sql_description_ids.")
						AND active = TRUE
						AND status < 10
						AND location_ref_type_id IN (0, ".implode(',', $arr_reversal_ids).")
				;
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
						status = status % 10
					WHERE object_sub_details_id IN (".$sql_description_ids.")
						AND active = TRUE
						AND status >= 10
						AND location_ref_type_id IN (0, ".implode(',', $arr_reversal_ids).")
				;
			";
		}
		
		if (isset($this->arr_updated_ids['object_sub_details_locations_locked'])) { // Update all sub-objects for location reversal 
			
			$arr_all_object_sub_details_ids = [];
			
			foreach ($this->arr_updated_ids['object_sub_details_locations_locked'] as $reversal_id => $arr_object_sub_details_ids) {

				foreach ($arr_object_sub_details_ids as $object_sub_details_id) {
					$arr_all_object_sub_details_ids[$object_sub_details_id] = $object_sub_details_id;
				}
			}
			
			$sql_description_ids = implode(',', $arr_all_object_sub_details_ids);
			
			$arr_sql_check[] = "SELECT nodegoat_to.type_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
				WHERE object_sub_details_id IN (".$sql_description_ids.")
					AND (
						(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 10 AND location_ref_object_id != 0)
							OR
						(nodegoat_tos.active = TRUE AND nodegoat_tos.status < 20)
					)
				GROUP BY nodegoat_to.type_id
			";
			
			$sql_update .= "
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
						location_ref_object_id = 0
					WHERE object_sub_details_id IN (".$sql_description_ids.")
						AND active = TRUE
						AND status < 10
				;
				UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." SET 
						status = status % 10
					WHERE object_sub_details_id IN (".$sql_description_ids.")
						AND active = TRUE
						AND status >= 10
				;
			";
		}
		
		$arr_changed_type_ids = [];
		
		if ($arr_sql_check) {
			
			$res = DB::query("SELECT DISTINCT type_id
				FROM (
					(".implode(") UNION ALL (", $arr_sql_check).")
				) AS foo
			");
			
			while ($arr_row = $res->fetchRow()) {
				
				$arr_changed_type_ids[(int)$arr_row[0]] = (int)$arr_row[0];
			}
		}
		
		if ($sql_update) {
			
			$res = DB::queryMulti($sql_update);
		}
		
		$this->arr_updated_ids = []; // Reset the tracker
		$this->arr_check_type_ids += $arr_changed_type_ids;
		
		return $arr_changed_type_ids;
	}
	
	public static function getReferencedTypeClearance($type_id, $arr_referenced_type) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$num_type_clearance = 0;
		
		if ($arr_referenced_type['object_descriptions']) {
			
			foreach ($arr_referenced_type['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$num_clearance = $arr_type_set['object_descriptions'][$object_description_id]['object_description_clearance_view'];
				
				if ($num_clearance > $num_type_clearance) {
					$num_type_clearance = $num_clearance;
				}
			}
		}
		if ($arr_referenced_type['object_sub_details']) {
					
			foreach ($arr_referenced_type['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$num_clearance = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_view'];
				
				if ($num_clearance > $num_type_clearance) {
					$num_type_clearance = $num_clearance;
				}
				
				if ($arr_object_sub_details['object_sub_descriptions']) {
								
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						$num_clearance = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_clearance_view'];
						
						if ($num_clearance > $num_type_clearance) {
							$num_type_clearance = $num_clearance;
						}
					}
				}
			}
		}
		
		return $num_type_clearance;
	}
}
