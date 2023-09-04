<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CreateVisualisationPackage {
	
	
	protected $arr_data = [];
	protected $arr_project = [];
	protected $arr_types_all = [];
	protected $arr_frame = [];
	protected $arr_visual_settings = [];
	
	protected $arr_collect_info = [];
	protected $attribution = '';
	protected $arr_pack_data = [];
	
	protected $arr_package_html = [];
	protected $arr_package_data = [];
	
	protected $arr_type_sets = [];

    public function __construct($arr_project, $arr_types_all, $arr_frame, $arr_visual_settings) {
		
		
		$this->arr_project = $arr_project;
		$this->arr_types_all = $arr_types_all;
		
		$this->arr_frame = $arr_frame;
		$this->arr_visual_settings = $arr_visual_settings;
		
		$this->setOutput($this->arr_data);
    }
    
    public function setOutput(&$arr) {
		
		$this->arr_data =& $arr;
		
		$this->arr_data['data'] =& $this->arr_package_data;
		$this->arr_data['visual'] =& $this->arr_visual_settings;
		$this->arr_data['html'] =& $this->arr_package_html;
	}
	
	public function addType($type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $scenario_id = false, $scenario_hash = false) {
		
		$this->arr_pack_data = [];
		$has_cache = null;
		
		if ($scenario_hash) {
			
			$arr_use_project_ids = array_keys($this->arr_project['use_projects']);
			$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($this->arr_project['project']['id'], false, false, $scenario_id, $arr_use_project_ids);
			
			if ($arr_scenario['attribution']) {
				$this->attribution = $arr_scenario['attribution'];
			}
			
			$cache_scenario = new CacheProjectTypeScenario($this->arr_project['project']['id'], $scenario_id);						
			$has_cache = $cache_scenario->checkCacheVisualise($scenario_hash);
			
			if ($has_cache === true) {
						
				$arr_scenario_storage = $cache_scenario->getCache();

				$this->arr_pack_data = $arr_scenario_storage['arr_pack'];
				$arr_collect_info = $arr_scenario_storage['arr_collect_info'];

				foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
					
					if ($this->arr_type_sets[$cur_type_id]) {
						continue;
					}
					
					$this->arr_type_sets[$cur_type_id] = StoreType::getTypeSet($cur_type_id);
				}
				
				$this->arr_collect_info = ($this->arr_collect_info ? array_merge_recursive($this->arr_collect_info, $arr_collect_info) : $arr_collect_info);
			} else {
				
				status(getLabel('msg_building_cache_scenario_visualisation'), false, getLabel('msg_wait'), ['identifier' => SiteStartVars::getSessionId(true).'cache_scenario_visualisation', 'duration' => 1000, 'persist' => true]);
			}
		}
		
		if (!$this->arr_pack_data) {
			
			$collect->init($arr_filters);
			$arr_collect_info = $collect->getResultInfo();

			$arr_check = []; // Have arrays with unique values while preserving an array (not object) for fast javascript iteration
			$arr_post_process_date = [];
			$num_sort = 1;
			
			foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
				
				if ($this->arr_type_sets[$cur_type_id]) {
					continue;
				}
				
				$this->arr_type_sets[$cur_type_id] = StoreType::getTypeSet($cur_type_id);
			}
			
			$this->arr_collect_info = ($this->arr_collect_info ? array_merge_recursive($this->arr_collect_info, $arr_collect_info) : $arr_collect_info);
							
			$arr_objects = $collect->getPathObjects('0');
			
			foreach ($arr_objects as $object_id => $arr_object) {
								
				$collect->getWalkedObject($object_id, [], function &($cur_target_object_id, $cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) use ($object_id, &$arr_check, &$arr_post_process_date, &$num_sort) {
					
					$arr_object_descriptions = $this->arr_type_sets[$cur_target_type_id]['object_descriptions'];
					$arr_object_subs_details = $this->arr_type_sets[$cur_target_type_id]['object_sub_details'];
					
					$arr_object = $collect->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id']);
					
					$do_collapse = ($arr_info['arr_collapse_source'] ? true : false);
					$is_collapsed = ($arr_info['arr_collapsed_source'] ? true : false);
					$arr_collapsing_source = ($is_collapsed ? $arr_info['arr_collapsed_source'] : $arr_info['arr_collapse_source']);
				
					$num_depth = ($source_path == '0' ? 0 : 1 + substr_count($source_path, '-'));
					
					$s_arr_object =& $this->arr_pack_data['objects'][$cur_target_object_id];
					
					if (!$do_collapse) { // Object is not needed when it is collapsed
						
						if (!$s_arr_object || !isset($s_arr_object['name'])) { // Object can exist in multiple paths
							
							$s_arr_object = [
								'name' => $arr_object['object']['object_name'],
								'style' => [],
								'type_id' => $cur_target_type_id,
								'sort' => $num_sort
							];
														
							$num_sort++;
						}
						
						static::parseStyle($s_arr_object['style'], $arr_object['object']['object_style'], $num_depth); // Add to the object style on every encounter
						
						if (!isset($arr_check[$cur_path.$arr_info['in_out'].'_objects_'.$cur_target_object_id])) { // Objects can exist in multiple paths, update object and its descriptions (in case of filtering) once in every path

							$s_arr_object_definitions =& $s_arr_object['object_definitions'];
							
							foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
								
								$arr_object_description = $arr_object_descriptions[$object_description_id];
								
								if (!$arr_object_definition['object_definition_value'] && !$arr_object_definition['object_definition_ref_object_id']) {
									continue;
								}

								if ($arr_object_description['object_description_is_dynamic']) {
									
									if ($arr_object_definition['object_definition_value']) {
										
										$s_arr =& $s_arr_object_definitions[$object_description_id];
																
										if (!$s_arr) {
											
											$s_arr = [
												'description_id' => $object_description_id,
												'value' => [$arr_object_definition['object_definition_value']],
												'ref_object_id' => [],
												'style' => []
											];
										}
									}
										
									foreach ($arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
										
										$s_arr =& $s_arr_object_definitions[$object_description_id.'_'.$ref_type_id];
										
										if (!$s_arr) {
											
											$s_arr = [
												'description_id' => $object_description_id.'_'.$ref_type_id,
												'value' => [],
												'ref_object_id' => [],
												'style' => []
											];
										}
										
										foreach ($arr_ref_objects as $arr_ref_object) {
											
											$s_arr['value'][] = $arr_ref_object['object_definition_ref_object_name'];
											$s_arr['ref_object_id'][] = $arr_ref_object['object_definition_ref_object_id'];
										}
									}
								} else {
									
									$s_arr =& $s_arr_object_definitions[$object_description_id];
									
									if (!$s_arr) {
										
										$s_arr = [
											'description_id' => $object_description_id,
											'value' => (array)$arr_object_definition['object_definition_value'],
											'ref_object_id' => (array)$arr_object_definition['object_definition_ref_object_id'],
											'style' => []
										];
									} else if ($s_arr['ref_object_id'] !== (array)$arr_object_definition['object_definition_ref_object_id']) {
										
										$s_arr['value'] = arrMergeValues([$s_arr['value'], (array)$arr_object_definition['object_definition_value']]);
										$s_arr['ref_object_id'] = arrMergeValues([$s_arr['ref_object_id'], (array)$arr_object_definition['object_definition_ref_object_id']]);
									}
									
									static::parseStyle($s_arr['style'], $arr_object_definition['object_definition_style'], $num_depth);
								}
							}
							
							$arr_check[$cur_path.$arr_info['in_out'].'_objects_'.$cur_target_object_id] = true;
						}
					} else { // Though some information may still be needed
						
						if (!$s_arr_object) {
							
							$s_arr_object = [
								'style' => [],
								'type_id' => $cur_target_type_id
							];
						}
							
						$s_arr_object =& $this->arr_pack_data['objects'][$arr_collapsing_source['object_id']];
						
						static::parseStyle($s_arr_object['style'], $arr_object['object']['object_style'], $num_depth); // Add to the object style on every encounter, and collapse
					}
					
					$s_arr_object_connect_object_sub_ids =& $this->arr_pack_data['objects'][$object_id]['connect_object_sub_ids'];
					
					if (!$s_arr_object_connect_object_sub_ids) {
						$s_arr_object_connect_object_sub_ids = [];
					}
										
					foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
						
						if (isset($arr_info['filtered']) && isset($arr_info['object_sub_details_id']) && $arr_object_sub['object_sub']['object_sub_details_id'] == $arr_info['object_sub_details_id'] && $object_sub_id != $arr_info['object_sub_id']) { // Subobjects can be skipped/dropped in the collection, make sure subobjects only add themselves
							continue;
						}
						
						if ($do_collapse && $arr_collapsing_source['object_sub_id'] != $object_sub_id) { // Sub-object is not needed when it is collapsed and not needed as source of the collapse (i.e. has a referenced sub-object description) 
							continue;
						}
									
						if (!$this->arr_frame['object_subs']['unknown']['date'] && !$arr_object_sub['object_sub']['object_sub_date_start']) {
							continue;
						}
						
						$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$object_sub_id];
						
						if (!isset($s_arr_object_sub)) { // Sub-objects can exist in multiple paths

							// Return *ymmdd
							$date_raw_start = ($arr_object_sub['object_sub']['object_sub_date_start'] ?: $arr_object_sub['object_sub']['object_sub_date_end']);
							$date_raw_end = ($arr_object_sub['object_sub']['object_sub_date_end'] ?: $date_raw_start);
							$date_start = self::parseDate($date_raw_start);
							$date_end = self::parseDate($date_raw_end);
							
							$location_geometry = ($arr_object_sub['object_sub']['object_sub_location_geometry'] ?? '');

							$s_arr_object_sub = [
								'object_id' => $cur_target_object_id,
								'object_sub_details_id' => $arr_object_sub['object_sub']['object_sub_details_id'],
								'location_geometry' => $location_geometry,
								'location_name' => $arr_object_sub['object_sub']['object_sub_location_ref_object_name'],
								'location_object_id' => $arr_object_sub['object_sub']['object_sub_location_ref_object_id'],
								'location_type_id' => $arr_object_sub['object_sub']['object_sub_location_ref_type_id'],
								'date_start' => $date_start,
								'date_end' => $date_end,
								'style' => []
							];
							
							$this->arr_pack_data['legend']['object_subs'][$arr_object_sub['object_sub']['object_sub_details_id']] = [];
							
							if ($date_start) {
								
								if (($date_raw_start == StoreTypeObjects::DATE_INT_MIN && $date_raw_end == StoreTypeObjects::DATE_INT_MIN) || ($date_raw_start == StoreTypeObjects::DATE_INT_MAX && $date_raw_end == StoreTypeObjects::DATE_INT_MAX)) {
									
									$arr_post_process_date[$object_sub_id] = $date_raw_start;
									
									$this->arr_pack_data['range'][] = $object_sub_id;
								} else {
									
									if ($date_start != $date_end) {
										$this->arr_pack_data['range'][] = $object_sub_id;
									} else {
										$this->arr_pack_data['date'][$date_start][] = $object_sub_id;
									}
									
									$date_start = ($date_raw_start == StoreTypeObjects::DATE_INT_MIN || $date_raw_start == StoreTypeObjects::DATE_INT_MAX ? ($date_raw_end == StoreTypeObjects::DATE_INT_MIN || $date_raw_end == StoreTypeObjects::DATE_INT_MAX ? false : $date_end) : $date_start);
									if ($date_start && (empty($this->arr_pack_data['date_range']['min']) || StoreTypeObjects::dateInt2Absolute($date_start) < StoreTypeObjects::dateInt2Absolute($this->arr_pack_data['date_range']['min']))) {
										$this->arr_pack_data['date_range']['min'] = $date_start;
									}
									$date_end = ($date_raw_end == StoreTypeObjects::DATE_INT_MIN || $date_raw_end == StoreTypeObjects::DATE_INT_MAX ? ($date_raw_start == StoreTypeObjects::DATE_INT_MIN || $date_raw_start == StoreTypeObjects::DATE_INT_MAX ? false : $date_start) : $date_end);
									if ($date_end && (empty($this->arr_pack_data['date_range']['max']) || StoreTypeObjects::dateInt2Absolute($date_end) > StoreTypeObjects::dateInt2Absolute($this->arr_pack_data['date_range']['max']))) {
										$this->arr_pack_data['date_range']['max'] = $date_end;
									}
								}
							}
						}
						
						static::parseStyle($s_arr_object_sub['style'], $arr_object_sub['object_sub']['object_sub_style'], $num_depth); // Add to the sub-object style on every encounter,
						
						$s_arr_object_connect_object_sub_ids['_'.$object_sub_id] = $object_sub_id; // Make sure the key is not numeric to prevent potential sorting by client
						
						if (!isset($arr_check[$cur_path.$arr_info['in_out'].'_object_subs_'.$object_sub_id])) { // Subobjects can exist in multiple paths, update descriptions (in case of filtering) once in every path

							if (!$do_collapse) { // Sub-object description is not needed when it is collapsed
								
								$arr_object_sub_details = $arr_object_subs_details[$arr_object_sub['object_sub']['object_sub_details_id']];
								
								$s_arr_object_sub_definitions =& $s_arr_object_sub['object_sub_definitions'];
								
								foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
									
									$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
									
									if (!$arr_object_sub_definition['object_sub_definition_value'] && !$arr_object_sub_definition['object_sub_definition_ref_object_id']) {
										continue;
									}
									
									if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
										
										if ($arr_object_sub_definition['object_sub_definition_value']) {
											
											$s_arr =& $s_arr_object_sub_definitions[$object_sub_description_id];
																	
											if (!$s_arr) {
												
												$s_arr = [
													'description_id' => $object_sub_description_id,
													'value' => [$arr_object_sub_definition['object_sub_definition_value']],
													'ref_object_id' => [],
													'style' => []
												];
											}
										}
										
										foreach ($arr_object_sub_definition['object_sub_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
											
											$s_arr =& $s_arr_object_sub_definitions[$object_sub_description_id.'_'.$ref_type_id];
											
											if (!$s_arr) {
												
												$s_arr = [
													'description_id' => $object_sub_description_id.'_'.$ref_type_id,
													'value' => [],
													'ref_object_id' => [],
													'style' => []
												];
											}
											
											foreach ($arr_ref_objects as $arr_ref_object) {
												
												$s_arr['value'][] = $arr_ref_object['object_sub_definition_ref_object_name'];
												$s_arr['ref_object_id'][] = $arr_ref_object['object_sub_definition_ref_object_id'];
											}
										}
									} else {
										
										$s_arr =& $s_arr_object_sub_definitions[$object_sub_description_id];
									
										if (!$s_arr) {
											
											$s_arr = [
												'description_id' => $object_sub_description_id,
												'value' => (array)$arr_object_sub_definition['object_sub_definition_value'],
												'ref_object_id' => (array)$arr_object_sub_definition['object_sub_definition_ref_object_id'],
												'style' => []
											];
										} else if ($s_arr['ref_object_id'] !== (array)$arr_object_sub_definition['object_sub_definition_ref_object_id']) {
											
											$s_arr['value'] = arrMergeValues([$s_arr['value'], (array)$arr_object_sub_definition['object_sub_definition_value']]);
											$s_arr['ref_object_id'] = arrMergeValues([$s_arr['ref_object_id'], (array)$arr_object_sub_definition['object_sub_definition_ref_object_id']]);
										}
										
										static::parseStyle($s_arr['style'], $arr_object_sub_definition['object_sub_definition_style'], $num_depth);
									}
								}
							}
							
							$arr_check[$cur_path.$arr_info['in_out'].'_object_subs_'.$object_sub_id] = true;
						}
					}

					if ($do_collapse) {

						$source_object_id = $arr_collapsing_source['object_id'];
						$source_object_sub_id = $arr_collapsing_source['object_sub_id'];
						
						$has_data = ($arr_object['object_definitions'] || $arr_object['object_subs']); // Not 'object only'
						
						if ($source_object_sub_id && $has_data) { // Relocate incomming sub-objects (if applicable) to the collapse source sub-object
							
							$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$source_object_sub_id];
							
							if (!isset($s_arr_object_sub)) { // Sub-object could be missing when the sub-object itself is collapsed and not part of the selection
								
								$s_arr_object_sub['object_sub_details_id'] = false;
								$s_arr_object_sub['original_object_id'] = ($arr_info['in_out'] == 'in' ? $cur_target_object_id : $arr_info['object_id']);
								$s_arr_object_sub['object_id'] = $source_object_id;
							} else if (!isset($s_arr_object_sub['original_object_id'])) {
							
								$s_arr_object_sub['original_object_id'] = $s_arr_object_sub['object_id'];
								$s_arr_object_sub['object_id'] = $source_object_id;
							}
						}
						
						if ($arr_object['object_definitions']) {
								
							foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
								
								$arr_object_description = $arr_object_descriptions[$object_description_id];
								
								if ($arr_info['arr_collapse_targets']['object_descriptions'][$object_description_id] || (!$arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic'])) {
									continue;
								}
								
								if ($arr_object_description['object_description_is_dynamic']) {
									
									foreach ($arr_object_definition['object_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
										
										$arr_value = [];
										$arr_ref_object_id = [];
										
										foreach ($arr_ref_objects as $arr_ref_object) {
											
											$arr_value[] = $arr_ref_object['object_definition_ref_object_name'];
											$arr_ref_object_id[] = $arr_ref_object['object_definition_ref_object_id'];
										}
										
										$this->collapseObjectDescription($object_description_id.'_'.$ref_type_id, $arr_value, $arr_ref_object_id, $arr_collapsing_source, $ref_type_id);
									}
								} else {
									
									$this->collapseObjectDescription($object_description_id, (array)$arr_object_definition['object_definition_value'], (array)$arr_object_definition['object_definition_ref_object_id'], $arr_collapsing_source, $arr_object_description['object_description_ref_type_id']);
								}
							}
						}
						
						if ($arr_object['object_subs']) {
							
							foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
								
								if (!$arr_object_sub['object_sub_definitions']) {
									continue;
								}
																
								$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
								$arr_object_sub_details = $arr_object_subs_details[$object_sub_details_id];
								
								foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
									
									$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
									
									if ($arr_info['arr_collapse_targets']['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] || !$arr_object_sub_definition['object_sub_definition_ref_object_id'] || (!$arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic'])) {
										continue;
									}
									
									
									if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
										
										foreach ($arr_object_sub_definition['object_sub_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
											
											$arr_value = [];
											$arr_ref_object_id = [];
											
											foreach ($arr_ref_objects as $arr_ref_object) {
												
												$arr_value[] = $arr_ref_object['object_sub_definition_ref_object_name'];
												$arr_ref_object_id[] = $arr_ref_object['object_sub_definition_ref_object_id'];
											}
											
											$this->collapseObjectDescription($object_sub_description_id.'_'.$ref_type_id, $arr_value, $arr_ref_object_id, $arr_collapsing_source, $ref_type_id);
										}
									} else {
										
										$this->collapseObjectDescription($object_sub_description_id, (array)$arr_object_sub_definition['object_sub_definition_value'], (array)$arr_object_sub_definition['object_sub_definition_ref_object_id'], $arr_collapsing_source, $arr_object_sub_description['object_sub_description_ref_type_id']);
									}
								}
							}
						}
						
						if ($arr_info['collapse_start'] && $arr_info['in_out'] == 'out') { // If collapse source is the starting point, remove
							
							if ($arr_collapsing_source['object_description_id']) {

								unset($this->arr_pack_data['objects'][$source_object_id]['object_definitions'][$arr_collapsing_source['object_description_id'].($arr_collapsing_source['dynamic'] ? '_'.$cur_target_type_id : '')]);
							} else if ($arr_collapsing_source['object_sub_description_id']) {

								unset($this->arr_pack_data['object_subs'][$source_object_sub_id]['object_sub_definitions'][$arr_collapsing_source['object_sub_description_id'].($arr_collapsing_source['dynamic'] ? '_'.$cur_target_type_id : '')]);
							} else if ($arr_collapsing_source['object_sub_location']) {
							
							}
						}
					} else if ($is_collapsed && $arr_info['in_out'] == 'in') { // If source was part of a collapse, but the current object is not, reconfigure the reference to that source
						
						$source_object_id = $arr_collapsing_source['object_id'];
						$source_type_id = $arr_collapsing_source['type_id'];
						
						$arr_value = (array)$this->arr_pack_data['objects'][$source_object_id]['name'];
						$arr_ref_object_id = (array)$source_object_id;
						
						if ($arr_collapsing_source['object_description_id']) {
							$org_object_description_id = $arr_collapsing_source['object_description_id'];
						} else if ($arr_collapsing_source['object_sub_description_id']) {
							$org_object_description_id = $arr_collapsing_source['object_sub_description_id'];
						} else if ($arr_collapsing_source['object_sub_location']) {
							$org_object_description_id = $arr_collapsing_source['object_sub_location'];
						}
						$arr_info['object_id'] = $cur_target_object_id;
						$this->collapseObjectDescription($org_object_description_id, $arr_value, $arr_ref_object_id, $arr_info, $source_type_id);
						
						if ($arr_info['object_description_id'] || $arr_info['object_sub_description_id']) {
							
							if ($arr_info['object_description_id']) {
								
								$s_arr_object_definitions =& $this->arr_pack_data['objects'][$cur_target_object_id]['object_definitions'];
								$object_description_identifier = $arr_info['object_description_id'].($arr_collapsing_source['dynamic'] ? '_'.$arr_info['type_id'] : '');
							} else {
								
								$s_arr_object_definitions =& $this->arr_pack_data['object_subs'][$arr_info['object_sub_id']]['object_sub_definitions'];
								$object_description_identifier = $arr_info['object_sub_description_id'].($arr_collapsing_source['dynamic'] ? '_'.$arr_info['type_id'] : '');
							}

							if (isset($s_arr_object_definitions[$object_description_identifier])) {
								
								$s_arr =& $s_arr_object_definitions[$object_description_identifier];
								
								if ($s_arr['ref_object_id']) { // If collapsed description is part of the selection, remove
								
									$key = array_search($arr_info['object_id'], $s_arr['ref_object_id']);

									unset(
										$s_arr['value'][$key],
										$s_arr['ref_object_id'][$key]
									);
									
									// Make sure the arrays do not have nonsequential keys
									$s_arr['value'] = array_values($s_arr['value']);
									$s_arr['ref_object_id'] = array_values($s_arr['ref_object_id']);
								}
							}
						} else if ($arr_info['object_sub_location']) {
							
						}
					}
				});
			}
			
			foreach ($arr_post_process_date as $object_sub_id => $date_start_raw) {
						
				$s_arr_object_sub =& $this->arr_pack_data['object_subs'][$object_sub_id];
				
				if ($date_start_raw == StoreTypeObjects::DATE_INT_MIN) {
					$s_arr_object_sub['date_end'] = ($this->arr_pack_data['date_range']['min'] ?? null);
				} else {
					$s_arr_object_sub['date_start'] = ($this->arr_pack_data['date_range']['max'] ?? null);
				}
			}
		}
		
		if ($has_cache === false) {
							
			$arr_store = ['arr_pack' => $this->arr_pack_data, 'arr_collect_info' => $arr_collect_info];
			
			// Parse package
			
			GenerateTypeObjects::setClearSharedTypeObjectNames(false);
			
			Response::holdFormat(true);
			Response::setFormat(Response::OUTPUT_JSON);
			
			$str = Response::parse($arr_store);
			
			Response::holdFormat();
			
			unset($arr_store);
			GenerateTypeObjects::setClearSharedTypeObjectNames(true);
			
			// Store package
			
			$cache_scenario->updateCache($str);
			
			clearStatus(SiteStartVars::getSessionId(true).'cache_scenario_visualisation');
		}
		
		if ($this->arr_pack_data) {
			
			$this->arr_package_data['pack'][] = $this->arr_pack_data;
		}
	}
	
	protected function collapseObjectDescription($org_object_description_id, $arr_value, $arr_ref_object_id, $arr_collapse_to, $ref_type_id) {
		
		if ($arr_collapse_to['object_description_id']) {
			$new_object_description_id = $arr_collapse_to['object_description_id'].'_'.$org_object_description_id;
		} else if ($arr_collapse_to['object_sub_description_id']) {
			$new_object_description_id = $arr_collapse_to['object_sub_description_id'].'_'.$org_object_description_id;
		} else if ($arr_collapse_to['object_sub_location']) {
			$new_object_description_id = 'object_sub_location_'.$org_object_description_id;
		}
		
		if ($arr_collapse_to['object_description_id']) {
		
			$s_arr_new =& $this->arr_pack_data['objects'][$arr_collapse_to['object_id']]['object_definitions'][$new_object_description_id];
			
			if (!isset($this->arr_pack_data['info']['object_descriptions'][$new_object_description_id])) {
				$this->arr_pack_data['info']['object_descriptions'][$new_object_description_id] = ['object_description_ref_type_id' => $ref_type_id, 'object_description_name' => ''];
			}
		} else if ($arr_collapse_to['object_sub_description_id']) {
		
			$s_arr_new =& $this->arr_pack_data['object_subs'][$arr_collapse_to['object_sub_id']]['object_sub_definitions'][$new_object_description_id];
			
			if (!isset($this->arr_pack_data['info']['object_sub_descriptions'][$new_object_description_id])) {
				$this->arr_pack_data['info']['object_sub_descriptions'][$new_object_description_id] = ['object_sub_description_ref_type_id' => $ref_type_id, 'object_sub_description_name' => ''];
			}
		} else if ($arr_collapse_to['object_sub_location']) {
		
			$s_arr_new =& $this->arr_pack_data['object_subs'][$arr_collapse_to['object_sub_id']]['object_sub_definitions'][$new_object_description_id];
			
			if (!isset($this->arr_pack_data['info']['object_sub_descriptions'][$new_object_description_id])) {
				$this->arr_pack_data['info']['object_sub_descriptions'][$new_object_description_id] = ['object_sub_description_ref_type_id' => $ref_type_id, 'object_sub_description_name' => ''];
			}
		}
		
		if (!$s_arr_new) {
			
			$s_arr_new['description_id'] = $new_object_description_id;
			$s_arr_new['value'] = $arr_value;
			$s_arr_new['ref_object_id'] = $arr_ref_object_id;
			$s_arr_new['style'] = [];
			
		} else if ($s_arr_new['ref_object_id'] !== $arr_ref_object_id) {
		
			$s_arr_new['value'] = arrMergeValues([$s_arr_new['value'], $arr_value]);
			$s_arr_new['ref_object_id'] = arrMergeValues([$s_arr_new['ref_object_id'], $arr_ref_object_id]);
		}
	}
	
	public function getPackage() {
		
		if ($this->arr_package_data['pack']) {
			
			$this->arr_package_data['info'] = [];
			$this->arr_package_data['legend'] = [];
			$count_objects = 0;
			
			foreach ($this->arr_package_data['pack'] as &$arr_pack_data) {
					
				if (!$arr_pack_data['objects']) {
					continue;
				}
				
				if ($arr_pack_data['info']) {
					$this->arr_package_data['info'] = array_replace_recursive($this->arr_package_data['info'], $arr_pack_data['info']);
				}
				
				if ($arr_pack_data['legend']) {
					$this->arr_package_data['legend'] = array_replace_recursive($this->arr_package_data['legend'], $arr_pack_data['legend']);
				}
				
				$date_min = $arr_pack_data['date_range']['min'];
				$date_max = $arr_pack_data['date_range']['max'];
				$s_date_package_min =& $this->arr_package_data['date_range']['min'];
				$s_date_package_max =& $this->arr_package_data['date_range']['max'];
				
				if ($date_min && (!$s_date_package_min || $date_min < $s_date_package_min)) {
					$s_date_package_min = $date_min;
				}
				if ($date_max && (!$s_date_package_max || $date_max > $s_date_package_max)) {
					$s_date_package_max = $date_max;
				}
				unset($arr_pack_data['info'], $arr_pack_data['legend'], $arr_pack_data['date_range']);
				
				$count_objects += count($arr_pack_data['objects']);
			}
			
			if ($count_objects) {
									
				$date_now = (int)(date('Ymd').StoreTypeObjects::DATE_INT_SEQUENCE_NULL);
				if (!$this->arr_package_data['date_range']['min']) {
					$this->arr_package_data['date_range']['min'] = $date_now;
				}
				if (!$this->arr_package_data['date_range']['max']) {
					$this->arr_package_data['date_range']['max'] = $date_now;
				}

				// Gather information on types and subobjects for (quick) external access and legends
				
				$arr_legend = [];
				$arr_html_legend = [];
				$arr_types_found = [];
				
				foreach ($this->arr_type_sets as $type_id => $arr_type_set) {
					
					$this->arr_package_data['info']['types'][$arr_type_set['type']['id']] = ['name' => Labels::parseTextVariables($arr_type_set['type']['name'])];
					$arr_types_found[$type_id] = $type_id;
					
					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						if (!data_model::checkClearanceTypeConfiguration(StoreType::CLEARANCE_PURPOSE_VIEW, $arr_type_set, $object_description_id)) {
							continue;
						}
						
						$this->arr_package_data['info']['object_descriptions'][$object_description_id] = ['object_description_ref_type_id' => $arr_object_description['object_description_ref_type_id'], 'object_description_name' => Labels::parseTextVariables($arr_object_description['object_description_name'])];
						
						if ($arr_object_description['object_description_ref_type_id']) {
							$arr_types_found[$arr_object_description['object_description_ref_type_id']] = $arr_object_description['object_description_ref_type_id'];
						}
						
						if ($arr_object_description['object_description_is_dynamic']) {
							
							foreach ((array)$this->arr_collect_info['types_found']['object_definition_'.$object_description_id] as $found_type_id) {
								
								$this->arr_package_data['info']['object_descriptions'][$object_description_id.'_'.$found_type_id] = ['object_description_ref_type_id' => $found_type_id, 'object_description_name' => Labels::parseTextVariables($arr_object_description['object_description_name'].' ('.$this->arr_types_all[$found_type_id]['name'].')')];
								$arr_types_found[$found_type_id] = $found_type_id;
							}
						}
					}
					
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view']) {
							continue;
						}
						
						$this->arr_package_data['info']['object_sub_details'][$arr_object_sub_details['object_sub_details']['object_sub_details_id']] = ['object_sub_details_name' => Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])];
						$arr_legend['object_sub_details'][$object_sub_details_id] = '<span>'.Labels::parseTextVariables($arr_type_set['type']['name']).' </span><span class="sub-name">'.$this->arr_package_data['info']['object_sub_details'][$object_sub_details_id]['object_sub_details_name'].'</span>';
						
						foreach ((array)$arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view']) {
								continue;
							}

							$this->arr_package_data['info']['object_sub_descriptions'][$object_sub_description_id] = ['object_sub_description_ref_type_id' => $arr_object_sub_description['object_sub_description_ref_type_id'], 'object_sub_description_name' => Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'])];
							
							if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
								$arr_types_found[$arr_object_sub_description['object_sub_description_ref_type_id']] = $arr_object_sub_description['object_sub_description_ref_type_id'];
							}
							
							if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
								
								foreach ((array)$this->arr_collect_info['types_found']['object_sub_definition_'.$object_sub_description_id] as $found_type_id) {
									
									$this->arr_package_data['info']['object_sub_descriptions'][$object_sub_description_id.'_'.$found_type_id] = ['object_sub_description_ref_type_id' => $found_type_id, 'object_sub_description_name' => Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'].' ('.$this->arr_types_all[$found_type_id]['name'].')')];
									$arr_types_found[$found_type_id] = $found_type_id;
								}
							}
						}
					}
				}
						
				// Coloring
				$arr_colors = [
					['start' => 237/360, 'stop' => 208/360, 'sat' => .75, 'val' => .95, 'name' => 'blue'],
					['start' => 0, 'stop' => 22/360, 'sat' => .75, 'val' => .95, 'name' => 'red'],
					['start' => 284/360, 'stop' => 310/360, 'sat' => .75, 'val' => .95, 'name' => 'purple'],
					['start' => 202/360, 'stop' => 181/360, 'sat' => .75, 'val' => .95, 'name' => 'turquoise'],
					['start' => 28/360, 'stop' => 38/360, 'sat' => .75, 'val' => .95, 'name' => 'orange'],
					['start' => 120/360, 'stop' => 80/360, 'sat' => .75, 'val' => .95, 'name' => 'green'],
					['start' => 360/360, 'stop' => 360/360, 'sat' => .0, 'val' => .85, 'name' => 'other']
				];
				$arr_color_full = ['start' => 0, 'stop' => 1, 'sat' => .75, 'val' => .95, 'name' => 'full'];
				
				$total = (isset($this->arr_package_data['legend']['object_subs']) ? count($this->arr_package_data['legend']['object_subs']) : 0);
				$total_colors = count($arr_colors);
				$i = 0;
				foreach (($this->arr_package_data['legend']['object_subs'] ?? []) as $object_sub_details_id => $value) {
					
					if ($i >= $total_colors) {
						
						$cur_color = $arr_color_full;
						
						$range = ($cur_color['stop'] - $cur_color['start']) / ($total - $total_colors);
						$cur_color['start'] = $range * ($total - 1 - $i);
						$cur_color['stop'] = $range * (($total - 1 - $i) + 1);
						
					} else {
						
						$cur_color = ($arr_colors[$i] ?: end($arr_colors));
					}

					$this->arr_package_data['legend']['object_subs'][$object_sub_details_id] = [
						'color' => self::HSV2RGB($cur_color['start'], $cur_color['sat'], $cur_color['val'])
					];
					
					$arr_info = $this->arr_package_data['legend']['object_subs'][$object_sub_details_id];
					$arr_html_legend['object_sub_details'][$object_sub_details_id] = '<li data-identifier="'.$object_sub_details_id.'"><dt>'.$arr_legend['object_sub_details'][$object_sub_details_id].'</dt><dd><span style="background-color: rgb('.$arr_info['color']['r'].','.$arr_info['color']['g'].','.$arr_info['color']['b'].');"></span></dd></li>';
					
					$i++;
				}
				
				if (isset($this->arr_collect_info['types_found']['locations'])) {
					
					$arr_types_found += $this->arr_collect_info['types_found']['locations'];
				}
				foreach ($arr_types_found as $type_id => $value) {
					
					if ($this->arr_types_all[$type_id]['color'] || !empty($this->arr_project['types'][$type_id]['color'])) {
						
						$str_color = ($this->arr_project['types'][$type_id]['color'] ?? null ?: $this->arr_types_all[$type_id]['color']);
						
						$this->arr_package_data['legend']['types'][$type_id] = [
							'color' => $str_color
						];
						$arr_html_legend['types'][$type_id] = '<li data-identifier="'.$type_id.'"><dt>'.Labels::parseTextVariables($this->arr_types_all[$type_id]['name']).'</dt><dd><span style="background-color: '.$str_color.';"></span></dd></li>';
					}
				}
				
				$arr_condition_settings = [];
				
				foreach ($this->arr_collect_info['conditions'] as $arr_conditions) {
					
					foreach ((array)$arr_conditions['object'] as $arr_condition_setting) {
									
						$arr_condition_settings[] = $arr_condition_setting;
					}
					
					foreach ((array)$arr_conditions['object_descriptions'] as $object_description_id => $arr_conditions_object_description) {
						foreach ($arr_conditions_object_description as $arr_condition_setting) {
							
							$arr_condition_settings[] = $arr_condition_setting;
						}
					}
					
					foreach ((array)$arr_conditions['object_sub_details'] as $object_sub_details_id => $arr_conditions_object_sub_details) {
						
						foreach ((array)$arr_conditions_object_sub_details['object_sub_details'] as $arr_condition_setting) {
						
							$arr_condition_settings[] = $arr_condition_setting;
						}
						
						foreach ((array)$arr_conditions_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_conditions_object_sub_description) {
							foreach ($arr_conditions_object_sub_description as $arr_condition_setting) {
							
								$arr_condition_settings[] = $arr_condition_setting;
							}
						}
					}
				}
				
				foreach ($arr_condition_settings as $key => $arr_condition_setting) {

					if (!$arr_condition_setting['condition_in_object_nodes_referencing'] && !$arr_condition_setting['condition_in_object_nodes_object']) {
						continue;
					}
					
					$str_label = Labels::parseTextVariables($arr_condition_setting['condition_label']);
					$str_color = ($arr_condition_setting['condition_actions']['color']['color'] ?? null);
					$str_weight = ($arr_condition_setting['condition_actions']['weight']['number'] ?? null);
					$str_icon = ($arr_condition_setting['condition_actions']['icon']['image'] ?? null);
					
					if ($str_icon) {
						
						$str_icon = '/'.DIR_CUSTOM_PROJECT_WORKSPACE.$str_icon;
					}
					
					$str_identifier = $arr_condition_setting['condition_identifier'];
					
					$this->arr_package_data['legend']['conditions'][$str_identifier] = [
						'label' => $str_label,
						'color' => $str_color,
						'weight' => $str_weight,
						'icon' => $str_icon
					];
					
					if (!$arr_condition_setting['condition_label'] || !$str_color) {
						continue;
					}
				
					$arr_html_legend['conditions'][] = '<li data-identifier="'.strEscapeHTML($str_identifier).'"><dt>'.$str_label.'</dt><dd><span style="background-color: '.$str_color.';"></span></dd></li>';
				}
				
				$this->arr_package_html = '<div class="labmap">
					<div class="map"></div>
					<div class="controls">
						<div class="geo hide"></div>
						<div class="soc hide"></div>
						<div class="time hide"></div>
						<div class="timeline">
							<div>
								<div class="slider"></div>
								<div class="buttons">
									<input type="button" id="y:data_visualise:review_data-date" value="'.getLabel('lbl_view_selection').'" />
								</div>
							</div>
						</div>
						<div class="legends">
							'.($arr_html_legend['types'] ? '<figure class="types">
								<dl>'.implode('', $arr_html_legend['types']).'</dl>
							</figure>' : '').'
							'.($arr_html_legend['object_sub_details'] ? '<figure class="object-sub-details">
								<dl>'.implode('', $arr_html_legend['object_sub_details']).'</dl>
							</figure>' : '').'
							'.($arr_html_legend['conditions'] ? '<figure class="conditions">
								<dl>'.implode('', $arr_html_legend['conditions']).'</dl>
							</figure>' : '').'
						</div>
					</div>
				</div>';
			}
			
			status(getLabel('msg_transferring'), false, false, ['persist' => true, 'duration' => 0]);
		}
		
		$this->arr_package_data['time'] = ['bounds' => [], 'selection' => []];
		if ($this->arr_frame['time']['bounds']['date_start'] && $this->arr_frame['time']['bounds']['date_end']) {
			$this->arr_package_data['time']['bounds']['min'] = static::parseDate($this->arr_frame['time']['bounds']['date_start']);
			$this->arr_package_data['time']['bounds']['max'] = static::parseDate($this->arr_frame['time']['bounds']['date_end']);
		}
		if ($this->arr_frame['time']['selection']['date_start'] && $this->arr_frame['time']['selection']['date_end']) {
			$this->arr_package_data['time']['selection']['min'] = static::parseDate($this->arr_frame['time']['selection']['date_start']);
			$this->arr_package_data['time']['selection']['max'] = static::parseDate($this->arr_frame['time']['selection']['date_end']);
		}
		
		$this->arr_package_data['center'] = false;
		if ($this->arr_frame['area']['geo']['latitude'] || $this->arr_frame['area']['geo']['longitude']) {
			$this->arr_package_data['center']['coordinates']['latitude'] = $this->arr_frame['area']['geo']['latitude'];
			$this->arr_package_data['center']['coordinates']['longitude'] = $this->arr_frame['area']['geo']['longitude'];
		}
		$this->arr_package_data['focus'] = false;
		if ($this->arr_frame['area']['social']['object_id']) {
			$this->arr_package_data['focus'] = ['object_id' => $this->arr_frame['area']['social']['object_id']];
		}
		$this->arr_package_data['zoom'] = [];
		if ($this->arr_frame['area']['geo']['zoom']['scale']) {
			$this->arr_package_data['zoom']['scale'] = $this->arr_frame['area']['geo']['zoom']['scale'];
		}
		$this->arr_package_data['zoom']['geo']['min'] = ($this->arr_frame['area']['geo']['zoom']['min'] ?: 1);
		$this->arr_package_data['zoom']['geo']['max'] = ($this->arr_frame['area']['geo']['zoom']['max'] ?: 18);
		if ($this->arr_frame['area']['social']['zoom']['level']) {
			$this->arr_package_data['zoom']['level'] = $this->arr_frame['area']['social']['zoom']['level'];
		}
		$this->arr_package_data['zoom']['social']['min'] = ($this->arr_frame['area']['social']['zoom']['min'] ?: -7);
		$this->arr_package_data['zoom']['social']['max'] = ($this->arr_frame['area']['social']['zoom']['max'] ?: 7);
		$this->arr_package_data['options'] = [];
		if ($this->arr_frame['object_subs']['unknown']) {
			$this->arr_package_data['options']['object_subs']['unknown'] = $this->arr_frame['object_subs']['unknown'];
		}
		
		$this->arr_package_data['attribution'] = ['base' => getLabel('lbl_site_attribution')];
		if ($this->attribution) {
			$this->arr_package_data['attribution']['source'] = $this->attribution;
		}
		
		return $this->arr_data;
	}
	
	protected static function parseDate($date) {
		
		if (!$date) {
			return 0;
		}
		
		return (int)preg_replace(['/0000(.{4})$/', '/00(.{4})$/'], ['0101$1', '01$1'], $date);
	}
	
	protected static function parseStyle(&$arr_style_target, $arr_style_add, $num_depth) {
		
		if (!$arr_style_add || ($arr_style_target == $arr_style_add)) {
			return;
		}
		
		$is_deeper = ($num_depth >= ($arr_style_target['depth'] ?? 0));

		foreach ($arr_style_add as $key => $value) {
			
			if ($key === 'conditions') {
								
				foreach ($value as $str_identifier => $num_condition) {
					$arr_style_target['conditions'][$str_identifier] += $num_condition;
				};
			} else {
				
				if (is_array($value)) {
					if (!isset($arr_style_target[$key])) {
						$arr_style_target[$key] = $value;
					} else if (is_array($arr_style_target[$key])) {
						array_push($arr_style_target[$key], ...$value);
					} else if ($is_deeper) {
						$arr_style_target[$key] = [$arr_style_target[$key]];
						array_push($arr_style_target[$key], ...$value);
					}
				} else {
					if ($is_deeper || !isset($arr_style_target[$key])) {
						$arr_style_target[$key] = $value;
					}
				}
			}
		}
		
		if ($is_deeper) {
			$arr_style_target['depth'] = $num_depth;
		}
		
		return $arr_style_target;
	}
	
	protected static function HSV2RGB($h, $s, $v) {
	
		//1
		$h *= 6;
		//2
		$i = floor($h);
		$f = $h - $i;
		//3
		$m = $v * (1 - $s);
		$n = $v * (1 - $s * $f);
		$k = $v * (1 - $s * (1 - $f));
		//4
		switch ($i) {
			case 0:
				list($r,$g,$b) = [$v,$k,$m];
				break;
			case 1:
				list($r,$g,$b) = [$n,$v,$m];
				break;
			case 2:
				list($r,$g,$b) = [$m,$v,$k];
				break;
			case 3:
				list($r,$g,$b) = [$m,$n,$v];
				break;
			case 4:
				list($r,$g,$b) = [$k,$m,$v];
				break;
			case 5:
			case 6: // For when $h=1 is given
				list($r,$g,$b) = [$v,$m,$n];
				break;
		}
		$r = round($r * 255);
		$g = round($g * 255);
		$b = round($b * 255);
		
		return ['r' => $r, 'g' => $g, 'b' => $b];
	}
}
