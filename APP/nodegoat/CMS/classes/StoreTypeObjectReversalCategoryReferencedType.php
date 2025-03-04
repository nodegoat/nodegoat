<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreTypeObjectReversalCategoryReferencedType {
	
	const MODE_CLASSIFICATION = 0;
	const MODE_COLLECTION = 1;
	const MODE_COLLECTION_RESOURCE_PATH = 2;
	
	protected static $num_store_reversal_objects_buffer = 1000;
	protected static $num_store_reversal_objects_stream = 10000;
	
	protected $manager = false;
	protected $reversal_id = false;
	protected $category_id = false;
	protected $referenced_type_id = false;
	protected $num_mode = [];
	
	protected $arr_filters = [];
	protected $arr_type_network_paths = [];
	protected $arr_type_selection = [];
	protected $arr_scope_types = [];
	protected $str_resource_path = null;
	protected $arr_types_found = [];
	
	protected $collect = null;
	protected $arr_use_paths = null;
	protected $do_type_object_tag_print = true; // Tag Objects and print the Object name vs keeping the Object identifier (and parse when viewed). Applicable to Resource Paths.
	
	const RESOURCE_PATH_PARSE_JSON = 1;
	const RESOURCE_PATH_PARSE_YAML = 2;

	public function __construct($manager, $reversal_id, $category_id, $referenced_type_id, $num_mode) {
		
		$this->manager = $manager;
		$this->reversal_id = $reversal_id;
		$this->category_id = $category_id;
		$this->referenced_type_id = $referenced_type_id;
		$this->num_mode = $num_mode;
	}
	
	public function setConfiguration($arr_filters, $arr_type_network_paths, $arr_type_selection, $arr_scope_types, $str_resource_path, $arr_types_found) {
				
		$this->arr_filters = $arr_filters;
		$this->arr_type_network_paths = $arr_type_network_paths;
		$this->arr_type_selection = $arr_type_selection;
		$this->arr_scope_types = $arr_scope_types;
		$this->str_resource_path = $str_resource_path;
		$this->arr_types_found = $arr_types_found;
	}
	
	public function getTypesFound() {
		
		return $this->arr_types_found;
	}
	
	public function run() {
		
		$arr_reversal = $this->manager->getReversal($this->reversal_id);
		$arr_referenced_type = $arr_reversal['types'][$this->referenced_type_id];
		$arr_scope_type_ids = $arr_reversal['scope_type_ids'];

		$sql_table_name_references = StoreType::getValueTypeTable('reversed_classification');
		$sql_table_name_text = StoreType::getValueTypeTable('reversed_collection', 'name');
		$sql_value_text = StoreType::getValueTypeValue('reversed_collection', 'name');
		$sql_table_name_view = StoreType::getValueTypeTable('reversed_collection_resource_path', 'view');
		$sql_value_view = StoreType::getValueTypeValue('reversed_collection_resource_path', 'view');
		
		$this->arr_use_paths = ($this->num_mode == static::MODE_COLLECTION ? [] : null);
		
		$this->collect = static::getCollector($this->num_mode,
			$this->referenced_type_id,
			$this->arr_filters,
			['types' => $this->arr_scope_types],
			['types' => $arr_scope_type_ids, 'paths' => $this->arr_type_network_paths, 'selection' => $this->arr_type_selection],
			
			$this->arr_use_paths
		);

		$this->collect->setInitLimit(static::$num_store_reversal_objects_stream);

		while ($this->collect->init($this->arr_filters)) {
															
			$filter = $this->collect->getResultSource(0, 'start');
			$sql_table_name_source = $filter->storeResultTemporarily();
			$sql_table_name_source_objects = $filter->storeResultTemporarily(true, true);
			
			DB::startTransaction('store_reversal_type_objects');
				
			if ($arr_referenced_type['object_descriptions']) {

				foreach ($arr_referenced_type['object_descriptions'] as $object_description_id => $arr_object_description) {

					$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_references."
						(object_id, object_description_id, ref_object_id, identifier, version, active, status)
						SELECT nodegoat_to_source.id, ".$object_description_id.", ".$this->category_id.", 0, 1, TRUE, 10
							FROM ".$sql_table_name_source_objects." AS nodegoat_to_source
						".DBFunctions::onConflict('object_id, object_description_id, ref_object_id, identifier, version', false, 'active = TRUE, status = 11')."
					");
					
					$this->manager->triggerReversalCategoryObjectDefinitions($this->category_id, $object_description_id);
					
					if ($this->num_mode == static::MODE_COLLECTION || $this->num_mode == static::MODE_COLLECTION_RESOURCE_PATH) { // Add objects when has reversed classification is in summary mode
						
						$arr_sql_insert_objects = [];
						$arr_value_object_ids = [];
						$str_value_collect = '';
						
						if ($this->num_mode == static::MODE_COLLECTION_RESOURCE_PATH) {
							
							$traverse = new TraverseJSON($this->str_resource_path, false);
							$iterate_collection = $this->iterateCollectorCollectionResourcePath($traverse);

							foreach ($iterate_collection as list($object_id, $str_path, $arr_objects_type_id)) {
								
								foreach ($arr_objects_type_id as $cur_target_object_id => $cur_target_type_id) {
								
									$arr_sql_insert_objects[] = "(".$object_description_id.", ".$object_id.", ".$cur_target_object_id.", ".$cur_target_type_id.", ".$this->category_id.", 2)";
								}

								$arr_value_object_ids[] = $object_id;
								$str_value_collect .= $str_path.DBFunctions::SQL_VALUE_SEPERATOR;
							}
						} else {
							
							$iterate_collection = $this->iterateCollectorCollection();

							foreach ($iterate_collection as list($object_id, $cur_target_object_id, $cur_target_type_id)) {
								
								$arr_sql_insert_objects[] = "(".$object_description_id.", ".$object_id.", ".$cur_target_object_id.", ".$cur_target_type_id.", ".$this->category_id.", 2)";
							}
						}

						if ($arr_sql_insert_objects) {
							
							$num_count = 0;
							$arr_sql_chunk = array_slice($arr_sql_insert_objects, $num_count, static::$num_store_reversal_objects_buffer);
							
							while ($arr_sql_chunk) {
								
								$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')."
									(object_description_id, object_id, ref_object_id, ref_type_id, identifier, state)
										VALUES
									".implode(',', $arr_sql_chunk)."
									".DBFunctions::onConflict('object_description_id, object_id, ref_object_id, identifier', false, 'state = 3')."
								");
								
								$num_count += static::$num_store_reversal_objects_buffer;
								$arr_sql_chunk = array_slice($arr_sql_insert_objects, $num_count, static::$num_store_reversal_objects_buffer);
							}
							
							unset($arr_sql_insert_objects);
							
							if ($this->num_mode == static::MODE_COLLECTION) {
								
								foreach ($this->arr_use_paths as $path) {
									
									$arr_collect_filters = $this->collect->getResultSource($path);

									foreach ($arr_collect_filters as $in_out => $arr_path_filters) {
										
										foreach ($arr_path_filters as $path_filter) {
										
											$sql_table_name_names = $path_filter->storeResultTemporarily();
											$arr_dynamic_type_name_column = $path_filter->generateNameColumn('nodegoat_to_name.id');
											$sql_dynamic_type_name_clause = ($arr_dynamic_type_name_column['order'] ? 'ORDER BY '.$arr_dynamic_type_name_column['order'] : false); 
											
											$sql_query_name = "SELECT nodegoat_to_source.id, ".DBFunctions::group2String('DISTINCT '.$arr_dynamic_type_name_column['column'], ', ', $sql_dynamic_type_name_clause)." AS name, ".$object_description_id.", ".$this->category_id.", ".StoreTypeObjectsReversals::VERSION_OFFSET_ALTERNATE_ACTIVE.", FALSE, 10
													FROM ".$sql_table_name_names." nodegoat_to_name
													JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref ON (nodegoat_to_def_ref.ref_object_id = nodegoat_to_name.id AND nodegoat_to_def_ref.object_description_id = ".$object_description_id." AND nodegoat_to_def_ref.identifier = ".$this->category_id." AND nodegoat_to_def_ref.state >= 2)
													JOIN ".$sql_table_name_source_objects." AS nodegoat_to_source ON (nodegoat_to_source.id = nodegoat_to_def_ref.object_id)
													".$arr_dynamic_type_name_column['tables']."
												GROUP BY nodegoat_to_source.id
											";
											
											$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_text."
												(object_id, ".$sql_value_text.", object_description_id, identifier, version, active, status)
												".$sql_query_name."
												".DBFunctions::onConflict('object_id, object_description_id, identifier, version', [$sql_value_text], 'status = 11')."
											");
										}
									}
								}
							}
						}
						
						if ($arr_value_object_ids) {
							
							// Main value: processed/parsed for display without processed Object names
							
							$arr_sql_insert_values = [];
							
							Response::setFormat(Response::OUTPUT_TEXT);
							
							if ($this->do_type_object_tag_print) { // Process everything
								
								$str_value_collect = Response::parse($str_value_collect);
							} else { // Process everything except Object names
								
								Response::holdParse('print_type_object_names', true);
								$str_value_collect = Response::parse($str_value_collect);
								Response::holdParse('print_type_object_names', false);
							}
							
							$arr_value_collect = explode(DBFunctions::SQL_VALUE_SEPERATOR, DBFunctions::strEscape($str_value_collect));

							foreach ($arr_value_object_ids as $key => $object_id) {
								
								$str_path = $arr_value_collect[$key];
								
								$arr_sql_insert_values[] = "(".$object_id.", '".$str_path."', ".$object_description_id.", ".$this->category_id.", 1, TRUE, 10)";
							}
							
							$num_count = 0;
							$arr_sql_chunk = array_slice($arr_sql_insert_values, $num_count, static::$num_store_reversal_objects_buffer);
														
							while ($arr_sql_chunk) {
								
								$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_view."
									(object_id, ".$sql_value_view.", object_description_id, identifier, version, active, status)
										VALUES
									".implode(',', $arr_sql_chunk)."
									".DBFunctions::onConflict('object_id, object_description_id, identifier, version', [$sql_value_view], 'active = TRUE, status = 11')."
								");
								
								$num_count += static::$num_store_reversal_objects_buffer;
								$arr_sql_chunk = array_slice($arr_sql_insert_values, $num_count, static::$num_store_reversal_objects_buffer);
							}
							
							// Alternate value: fully processed/parsed for name/quicksearch
							
							$arr_sql_insert_values = [];
							
							if ($this->do_type_object_tag_print) { // Remove the added Object tags
								
								$str_value_collect = FormatTypeObjects::clearObjectDefinitionText($str_value_collect, FormatTypeObjects::TEXT_TAG_OBJECT);
							} else { // Process and print Object names
								
								$str_value_collect = Response::parse($str_value_collect);
							}
							
							$arr_value_collect = explode(DBFunctions::SQL_VALUE_SEPERATOR, DBFunctions::strEscape($str_value_collect));

							foreach ($arr_value_object_ids as $key => $object_id) {
								
								$str_path = $arr_value_collect[$key];
								
								$arr_sql_insert_values[] = "(".$object_id.", '".$str_path."', ".$object_description_id.", ".$this->category_id.", ".StoreTypeObjectsReversals::VERSION_OFFSET_ALTERNATE_ACTIVE.", FALSE, 10)";
							}
							
							$num_count = 0;
							$arr_sql_chunk = array_slice($arr_sql_insert_values, $num_count, static::$num_store_reversal_objects_buffer);
														
							while ($arr_sql_chunk) {
								
								$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_name_text."
									(object_id, ".$sql_value_text.", object_description_id, identifier, version, active, status)
										VALUES
									".implode(',', $arr_sql_chunk)."
									".DBFunctions::onConflict('object_id, object_description_id, identifier, version', [$sql_value_text], 'status = 11')."
								");
								
								$num_count += static::$num_store_reversal_objects_buffer;
								$arr_sql_chunk = array_slice($arr_sql_insert_values, $num_count, static::$num_store_reversal_objects_buffer);
							}
							
							unset($arr_sql_insert_values, $arr_value_object_ids, $str_value_collect, $arr_value_collect);
						}
						
						$this->manager->triggerReversalCategoryObjectDefinitionsObjects($this->category_id, $object_description_id);
						$this->manager->triggerReversalCategoryObjectDefinitionsTexts($this->category_id, $object_description_id);
					}
				}
			}
			
			if ($arr_referenced_type['object_sub_details']) {
					
				foreach ($arr_referenced_type['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					if ($arr_object_sub_details['object_sub_location'] && !$arr_object_sub_details['object_sub_location']['location_use_other']) {
						
						$is_filtering = $this->arr_type_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'];
						
						// Update status with 10 because sub-objects could already have a different status

						$res = DB::query("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos SET
								location_ref_object_id = ".$this->category_id.",
								location_ref_type_id = ".$this->reversal_id.",
								status = CASE WHEN location_ref_object_id = ".$this->category_id." AND status < 20 THEN status + 20 WHEN status < 10 THEN status + 10 ELSE status END
							WHERE nodegoat_tos.object_sub_details_id = ".$object_sub_details_id."
								".(!$arr_object_sub_details['object_sub_location']['location_ref_type_id_locked'] ? "AND nodegoat_tos.location_ref_type_id IN (0, ".$this->reversal_id.")" : "")."
								AND EXISTS (SELECT TRUE
										FROM ".($is_filtering ? $sql_table_name_source : $sql_table_name_source_objects)." AS nodegoat_to_source
									WHERE ".($is_filtering ? "nodegoat_tos.id = nodegoat_to_source.object_sub_".$object_sub_details_id."_id" : "nodegoat_tos.object_id = nodegoat_to_source.id")."
								)
								AND nodegoat_tos.active = TRUE
						");
						
						if ($arr_object_sub_details['object_sub_location']['location_ref_type_id_locked']) {
							$this->manager->triggerReversalObjectSubsLocationsLocked($this->reversal_id, $object_sub_details_id);
						} else {
							$this->manager->triggerReversalObjectSubsLocations($this->reversal_id, $object_sub_details_id);
						}
					}
					
					if ($arr_object_sub_details['object_sub_descriptions']) {
							
						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							$is_filtering = $this->arr_type_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];

							$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_name_references."
								(object_sub_id, object_sub_description_id, ref_object_id, version, active, status)
								SELECT nodegoat_tos.id, ".$object_sub_description_id.", ".$this->category_id.", 1, TRUE, 10
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
									JOIN ".($is_filtering ? $sql_table_name_source : $sql_table_name_source_objects)." AS nodegoat_to_source ON (".($is_filtering ? "nodegoat_to_source.object_sub_".$object_sub_details_id."_id = nodegoat_tos.id" : "nodegoat_tos.object_id = nodegoat_to_source.id").")
								WHERE nodegoat_tos.object_sub_details_id = ".$object_sub_details_id."
									AND nodegoat_tos.active = TRUE
								".DBFunctions::onConflict('object_sub_id, object_sub_description_id, ref_object_id, version', false, 'active = TRUE, status = 11')."
							");
							
							$this->manager->triggerReversalObjectSubDefinitions($this->reversal_id, $object_sub_description_id);
						}
					}
				}
			}
			
			DB::commitTransaction('store_reversal_type_objects');
		}
		
		return true;
	}
	
	protected function iterateCollectorCollection() {
		
		$arr_objects = $this->collect->getPathObjects('0');
		
		foreach ($arr_objects as $object_id => $arr_object) {
		
			$arr_objects_type_id = $this->collect->getWalkedObject($object_id, [], function &($cur_target_object_id, &$cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info) {
				
				if (!$this->arr_use_paths[$cur_path]) {
					return $cur_arr;
				}
				
				$cur_arr[$cur_target_object_id] = $cur_target_type_id;
				
				return $cur_arr;
			});
			
			if (!$arr_objects_type_id) {
				continue;
			}
					
			foreach ($arr_objects_type_id as $cur_target_object_id => $cur_target_type_id) {
					
				yield [$object_id, $cur_target_object_id, $cur_target_type_id];
			}
		}
	}
	
	protected function iterateCollectorCollectionResourcePath($traverse) {
		
		$this->collect->setWalkMode(false, true);
		
		$arr_objects = $this->collect->getPathObjects('0');
		
		foreach ($arr_objects as $object_id => $arr_object) {
			
			$arr_objects_type_id = [];
		
			$arr_walked = $this->collect->getWalkedObject($object_id, [], function &($cur_target_object_id, &$cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info) use (&$arr_objects_type_id) {
				
				$arr_settings = ($this->arr_scope_types[$source_path][$cur_target_type_id] ?? null);
				
				if (empty($arr_settings['selection']) && empty($arr_settings['name'])) {
					return $cur_arr;
				}
				
				$arr_type_set = StoreType::getTypeSet($cur_target_type_id);
								
				$arr_object = $this->collect->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id']);
				
				if (!empty($arr_settings['name'])) {
					
					$value = '';
					
					if ($this->do_type_object_tag_print) {
						$value = '[object='.$cur_target_type_id.'_'.$cur_target_object_id.']'.$arr_object['object']['object_name'].'[/object]';
					} else {
						$value = GenerateTypeObjects::NAME_REFERENCE_TYPE_OBJECT_OPEN.$cur_target_type_id.'_'.$cur_target_object_id.GenerateTypeObjects::NAME_REFERENCE_TYPE_OBJECT_CLOSE;
					}
					
					$cur_arr['name_'.$cur_target_type_id] = $value;
					
					$arr_objects_type_id[$cur_target_object_id] = $cur_target_type_id;
				}
				
				foreach ($arr_settings['selection'] as $str_id => $arr_selected) {
				
					if ($arr_selected['object_description_id']) {
						
						$object_description_id = $arr_selected['object_description_id'];
						$arr_object_definition = $arr_object['object_definitions'][$object_description_id];
						
						$value = $arr_object_definition['object_definition_value'];
						
						if (!$value) {
							continue;
						}
						
						$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];			
						$has_multi = $arr_object_description['object_description_has_multi'];
						$cur_type_id = $arr_object_description['object_description_ref_type_id'];
						$is_mutable = is_array($cur_type_id);

						$reference = $arr_object_definition['object_definition_ref_object_id'];
						
						if ($arr_object_description['object_description_ref_type_id'] && $reference) {
							
							if ($arr_object_description['object_description_is_dynamic']) {
								
								foreach ($reference as $key => $reference_object_ids) {
									
									foreach ($reference_object_ids as $cur_type_id => $arr_reference_objects) {
										
										foreach ($arr_reference_objects as $cur_object_id => $arr_reference_object) {
											
											$arr_objects_type_id[$cur_object_id] = $cur_type_id;
										}
									}
								}
							} else {

								if ($has_multi) {
									
									foreach ($reference as $key => $cur_object_id) {
										
										if ($is_mutable) {
											list($cur_type_id, $cur_object_id) = explode('_', $cur_object_id);
										}
																				
										$arr_objects_type_id[$cur_object_id] = $cur_type_id;
										
										if ($this->do_type_object_tag_print) {
											$value[$key] = '[object='.$cur_type_id.'_'.$cur_object_id.']'.$value[$key].'[/object]';
										}
									}
								} else {
									
									if ($is_mutable) {
										list($cur_type_id, $reference) = explode('_', $reference);
									}
									
									$arr_objects_type_id[$reference] = $cur_type_id;
									
									if ($this->do_type_object_tag_print) {
										$value = '[object='.$cur_type_id.'_'.$reference.']'.$value.'[/object]';
									}
								}
							}
						}
						
						if ($has_multi && isset($cur_arr['object_description_'.$object_description_id])) {
							$cur_arr['object_description_'.$object_description_id] = arrMergeValues($cur_arr['object_description_'.$object_description_id], $value);
						} else {
							$cur_arr['object_description_'.$object_description_id] = $value;
						}
					}
					
					if ($arr_selected['object_sub_details_id']) {
						
						foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
							
							$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
							
							if ($arr_selected['object_sub_details_id'] != $object_sub_details_id) {
								continue;
							}

							$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
							$is_single = $arr_object_sub_details['object_sub_details']['object_sub_details_is_single'];
							
							if ($arr_selected['attribute']) {
								
								$str_attribute = $arr_selected['attribute'];
								$arr_object_sub_self = $arr_object_sub['object_sub'];
								
								if ($str_attribute == 'date') {
									
									if ($arr_object_sub_self['object_sub_date_start'] != $arr_object_sub_self['object_sub_date_end']) {
										$value = FormatTypeObjects::formatToValue('date', $arr_object_sub_self['object_sub_date_start']).' / '.FormatTypeObjects::formatToValue('date', $arr_object_sub_self['object_sub_date_end']);
									} else {
										$value = FormatTypeObjects::formatToValue('date', $arr_object_sub_self['object_sub_date_start']);
									}
								} else if ($str_attribute == 'date_start' || $str_attribute == 'date_end') {
									
									$value = FormatTypeObjects::formatToValue('date', $arr_object_sub_self['object_sub_'.$str_attribute]);
								} else if ($str_attribute == 'location') {
									
									$value = $arr_object_sub_self['object_sub_location_ref_object_name'];
									
									if ($arr_object_sub_self['object_sub_location_ref_object_id']) {
										
										$arr_objects_type_id[$arr_object_sub_self['object_sub_location_ref_object_id']] = $arr_object_sub_self['object_sub_location_ref_type_id'];
										
										if ($this->do_type_object_tag_print) {
											$value = '[object='.$arr_object_sub_self['object_sub_location_ref_type_id'].'_'.$arr_object_sub_self['object_sub_location_ref_object_id'].']'.$value.'[/object]';
										}
									}
								} else {
									
									$value = $arr_object_sub_self['object_sub_'.$str_attribute];
								}
								
								if (!$value) {
									continue;
								}
								
								if ($is_single) {
									$cur_arr['object_sub_details_'.$object_sub_details_id.'_'.$str_attribute] = $value;
								} else {
									$cur_arr[$object_sub_details_id][$object_sub_id]['object_sub_details_'.$object_sub_details_id.'_'.$str_attribute] = $value;
								}
							}

							if ($arr_selected['object_sub_description_id']) {
								
								$object_sub_description_id = $arr_selected['object_sub_description_id'];
								$arr_object_sub_definition = $arr_object_sub['object_sub_definitions'][$object_sub_description_id];
								
								$value = $arr_object_sub_definition['object_sub_definition_value'];
								
								if (!$value) {
									continue;
								}
								
								$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];			
																
								$reference = $arr_object_sub_definition['object_sub_definition_ref_object_id'];
								
								if ($arr_sub_object_description['object_sub_description_ref_type_id'] && $reference) {
									
									$cur_type_id = $arr_sub_object_description['object_sub_description_ref_type_id'];
									$is_mutable = is_array($cur_type_id);

									if ($arr_sub_object_description['object_sub_description_is_dynamic']) {
										
										foreach ($reference as $key => $reference_object_ids) {
											
											foreach ($reference_object_ids as $cur_type_id => $arr_reference_objects) {
												
												foreach ($arr_reference_objects as $cur_object_id => $arr_reference_object) {
													
													$arr_objects_type_id[$cur_object_id] = $cur_type_id;
												}
											}
										}
									} else {

										if ($is_mutable) {
											list($cur_type_id, $reference) = explode('_', $reference);
										}
										
										$arr_objects_type_id[$reference] = $cur_type_id;
										
										if ($this->do_type_object_tag_print) {
											$value = '[object='.$cur_type_id.'_'.$reference.']'.$value.'[/object]';
										}
									}
								}
								
								if ($is_single) {
									$cur_arr['object_sub_description_'.$object_sub_description_id] = $value;
								} else {
									$cur_arr[$object_sub_details_id][$object_sub_id]['object_sub_description_'.$object_sub_description_id] = $value;
								}
							}
						}
					}
				}

				return $cur_arr;
			});
			
			$arr_walked = ($arr_walked[$object_id] ?? null); // No need to include the initial/starting Object iteration
			
			if (!isset($arr_walked)) {
				continue;
			}
			
			$traverse->set($arr_walked);
			
			if ($traverse->hasGroups()) {
				$str_path = $traverse->getString(true);
				$str_path = implode(EOL_1100CC, $str_path);
			} else {
				$str_path = $traverse->getString(false);
			}

			yield [$object_id, $str_path, $arr_objects_type_id];
		}
	}
		
	protected static function getTypeNetworkSelection($type_id, $arr_settings) {
				
		$arr_selection = [
			'object' => [],
			'object_descriptions' => [],
			'object_sub_details' => []
		];
				
		if (!$arr_settings['selection'] && !$arr_settings['name']) {
			return $arr_selection;
		}
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		if ($arr_settings['name']) {
			$arr_selection['object']['all'] = true;
		}

		foreach ($arr_settings['selection'] as $str_id => $value) {
			
			if ($str_id == 'name') {
				
				$arr_selection['object']['all'] = true;
				
				continue;
			}
			
			$object_description_id = $value['object_description_id'];
			
			if ($object_description_id) {
				
				$arr_selection['object_descriptions'][$object_description_id] = ['object_description_id' => true];
				
				continue;
			}
			
			$object_sub_details_id = $value['object_sub_details_id'];
			
			if ($object_sub_details_id) {

				if (!isset($arr_selection['object_sub_details'][$object_sub_details_id])) {
					
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['all' => true];
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = []; // Set default empty selection on sub object descriptions as there could be none selected
				}
				
				$object_sub_description_id = $value['object_sub_description_id'];
				
				if ($object_sub_description_id) {					
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = ['object_sub_description_id' => true];
				}
				
				continue;
			}
		}
		
		return $arr_selection;
	}
	
	public static function getCollector($num_mode, $type_id, $arr_filters, $arr_scope, $arr_type_network, &$arr_use_paths = null) {
		
		if (!$arr_type_network) {
			
			$arr_type_network = [];
			
			$arr_types_all = StoreType::getTypes();
			$arr_type_network['types'] = array_keys($arr_types_all);
			
			if ($arr_scope['paths']) {
				
				$trace = new TraceTypesNetwork($arr_type_network['types'], true, true);
				$trace->filterTypesNetwork($arr_scope['paths']);
				$trace->run($type_id, false, cms_nodegoat_details::$num_network_trace_depth);
				$arr_type_network['paths'] = $trace->getTypeNetworkPaths(true);
			} else {
				$arr_type_network['paths'] = ['start' => [$type_id => ['path' => [0]]]];
			}
		}
		
		$collect = new CollectTypesObjects($arr_type_network['paths'], GenerateTypeObjects::VIEW_ALL);
		$collect->setScope(['types' => $arr_type_network['types']]);
		$collect->setConditions(GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE, function($cur_type_id) {
			
			$arr_conditions = cms_nodegoat_publish::getTypeConditions($cur_type_id, false);
			
			return ParseTypeFeatures::parseTypeConditionNamespace($cur_type_id, $arr_conditions, fn($arr_condition_setting) => ParseTypeFeatures::checkTypeConditionNamespace($arr_condition_setting, false));
		});
		$collect->init($arr_filters, false);
		
		$arr_collect_info = $collect->getResultInfo();
		
		foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
			
			foreach ($arr_paths as $path) {
				
				$source_path = $path;
				if ($source_path) { // Path includes the target type id, remove it
					$source_path = explode('-', $source_path);
					array_pop($source_path);
					$source_path = implode('-', $source_path);
				}
				
				$arr_settings = [];
				if ($num_mode == static::MODE_COLLECTION || $num_mode == static::MODE_COLLECTION_RESOURCE_PATH) {
					$arr_settings = $arr_scope['types'][$source_path][$cur_type_id];
				}
				
				$arr_filtering = [];
				if ($arr_settings['filter']) {
					$arr_filtering = ['all' => true];
				}

				$arr_selection = static::getTypeNetworkSelection($cur_type_id, $arr_settings);
				
				if ($source_path == '0') { // Check for specific sub-object filtering at the start when applicable
					
					if (isset($arr_type_network['selection']['object_sub_details'])) {
																
						$arr_selection['object_sub_details'] = $arr_type_network['selection']['object_sub_details'];
						$arr_filtering = ['all' => true];
					}
				}
				
				if (isset($arr_use_paths) && $arr_collect_info['connections'][$path]['end']) { // End of a path, use it
					$arr_use_paths[$path] = $path;
				}
			
				$collect->setPathOptions([$path => [
					'arr_selection' => $arr_selection,
					'arr_filtering' => $arr_filtering
				]]);
			}
		}
		
		return $collect;
	}

	public static function getCollectorResourcePath($collect, $arr_type_network_types, $do_encode = false) {
		
		$collect->setWalkMode(true, true);
		
		$arr_comments = [];
							
		$arr_path = $collect->getWalkedObject(0, [], function &($cur_target_object_id, &$cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) use ($arr_type_network_types, &$arr_comments) {
			
			$arr_selection = $arr_type_network_types[$source_path][$cur_target_type_id];
			
			$arr_type_set = StoreType::getTypeSet($cur_target_type_id);
			$arr_type_set_flat = StoreType::getTypeSetFlat($cur_target_type_id, ['purpose' => 'select']);
			
			$arr_comments[$cur_target_type_id] = $arr_type_set['type']['name'];
			
			if (!$arr_selection) {
				return $cur_arr;
			}
			
			if ($arr_selection['name']) {
				
				$cur_arr['name_'.$cur_target_type_id] = '';
				
				$arr_comments['name_'.$cur_target_type_id] = ($arr_type_set['type']['class'] == StoreType::TYPE_CLASS_CLASSIFICATION ? getLabel('lbl_category') : getLabel('lbl_object')).' '.getLabel('lbl_name');
			}
			
			foreach ($arr_selection['selection'] as $str_id => $arr_description) {
				
				if ($arr_description['object_sub_details_id']) {
					
					if ($arr_type_set['object_sub_details'][$arr_description['object_sub_details_id']]['object_sub_details']['object_sub_details_is_single']) {
						$cur_arr[$str_id] = '';
					} else {
						$cur_arr[$arr_description['object_sub_details_id']][0][$str_id] = '';
					}
				} else {
					
					$cur_arr[$str_id] = '';
				}
				
				$arr_comments[$str_id] = $arr_type_set_flat[$str_id]['name'];
			}
			
			return $cur_arr;
		});
		
		$func_walk = function(&$arr) use (&$func_walk) {
			
			foreach ($arr as $k => &$v) {
				
				if (is_array($v)) {
					
					if ($k === 0) {
						
						$arr['[]'] = $v;
						unset($arr[$k]);
					}
					
					$func_walk($v);
				}
			}
			
		};
		
		$func_walk($arr_path);
		
		$arr_path = $arr_path['[]']; // No need to include the initial/starting Object iteration
		
		if (!$do_encode) {
			return $arr_path;
		}
		
		if ($do_encode == static::RESOURCE_PATH_PARSE_JSON) {
			
			return value2JSON($arr_path, JSON_PRETTY_PRINT);
		} else if ($do_encode == static::RESOURCE_PATH_PARSE_YAML) {
			
			$str_path = value2YAML($arr_path);
			$arr_path = str2Array($str_path, EOL_1100CC);
			
			$num_length = 0;
			
			foreach ($arr_path as $str_line) {
				
				$num_test = strlen($str_line);

				if ($num_test > $num_length) {
					$num_length = $num_test;
				}
			}
			
			foreach ($arr_path as &$str_line) {
				
				foreach ($arr_comments as $str_id => $str_name) {
					
					if (!preg_match('/^\s*'.$str_id.':/', $str_line)) {
						continue;
					}
					
					$str_line = str_pad($str_line, $num_length, ' ').'  # '.$str_name;
				}
			}
			
			$str_path = arr2String($arr_path, EOL_1100CC);
			
			return $str_path;
		}
	}
}
