<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ImportTypeObjects {
	
	private $type_id = false;
	private $import_template_id = false;
	private $arr_import_template = false;
	private $source_file_id = false;
	private $arr_source_file = false;
	private $arr_source_file_contents = false;
	
	private $arr_type_sets = false;
	private $arr_string_object_pairs = false;
			
    public function __construct($import_template_id, $source_file_id) {

		memoryBoost(1024);
		timeLimit(0);

		$this->import_template_id = $import_template_id;	
		$this->arr_import_template = cms_nodegoat_import::getImportTemplates($this->import_template_id);
		$this->type_id = $this->arr_import_template['import_template']['type_id'];
		$this->source_file_id = $source_file_id;
		$this->arr_source_file = cms_nodegoat_import::getSourceFiles($this->source_file_id);
		$this->arr_source_file_contents = json_decode(file_get_contents(DIR_HOME_TYPE_IMPORT.$this->arr_source_file['filename']), true);
    }
    
	public function store() {
		
		$number_of_rows = count($this->arr_source_file_contents);
		$update_objects = false;
		$arr_objects = [];
		$process_time = microtime(true);
		
		for ($i = 0; $i < $number_of_rows; $i++) {
			
			$arr_object = ['object' => [], 'object_definitions' => [], 'object_subs' => []];
			$arr_collected_filter = [];
			$object_id = false;
			
			foreach ($this->arr_import_template['columns'] as $arr_column) {

				$column_heading = $arr_column['column_heading'];
				$element_id = $arr_column['element_id'];
				$value = $this->arr_source_file_contents[$i][$column_heading];

				if ($arr_column['is_filter']) {
					
					$update_objects = true;
					
					if ($arr_column['use_object_id_as_filter']) {
					
						$object_id = $this->getObjectId($value);
					
					} else {
					
						$arr_collected_filter[] = ['element_id' => $element_id, 'value' => $value];
					}
				} else {

					$splitter = $arr_column['splitter'];
					$generate = $arr_column['generate'];
					$ref_type_id = $arr_column['element_type_id'];
					$ref_type_object_sub_id = $arr_column['element_type_object_sub_id'];
					$ref_type_element_id = $arr_column['element_type_element_id'];
					
					$arr_values = [];

					if ($splitter) {
						
						$arr_split_values = explode($splitter, $value);
					
						if ($generate == 'multiple') {

							$arr_values = $arr_split_values;

						} else {
							
							$arr_values[] = $arr_split_values[$generate-1];
						}
					} else {
						
						$arr_values[] = $value;	
					}

					foreach ($arr_values as $split_value) {
				
						$arr_object = $this->parseElementId($this->type_id, $element_id, $split_value, 'store', $ref_type_id, $ref_type_object_sub_id, $ref_type_element_id, $arr_object);
					}
				}
			}
			
			if ($update_objects) {
	
				if (!$object_id) {
					
					$arr_filter = [];
					
					foreach ($arr_collected_filter as $arr_collected_filter_part) {
						
						$arr_filter = $this->parseElementId($this->type_id, $arr_collected_filter_part['element_id'], $arr_collected_filter_part['value'], 'filter', false, false, false, $arr_filter);
					}
					
					$hashed_filter = md5(json_encode([['object_filter' => $arr_filter]]));
					$object_id = $this->getHashedFilterObjectId($hashed_filter, $this->type_id);
				}
				
				if (!$object_id) {
					continue;
				}
				
				if ($arr_objects[$object_id]) {
					
					foreach ((array)$arr_object['object_definitions'] as $object_description_id => $arr_object_definitions) {
						
						if ($arr_objects[$object_id]['object_definitions'][$object_description_id]) {
							
							if ($arr_object_definitions['object_definition_ref_object_id'] && is_array($arr_object_definitions['object_definition_ref_object_id'])) {
								
								$arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_ref_object_id'] = array_merge($arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_ref_object_id'], $arr_object_definitions['object_definition_ref_object_id']);
							}
							
							if ($arr_object_definitions['object_definition_value'] && is_array($arr_object_definitions['object_definition_value'])) {
								
								$arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_value'] = array_merge($arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_value'], $arr_object_definitions['object_definition_value']);
							}
							
						} else {
							
							$arr_objects[$object_id]['object_definitions'][$object_description_id] = $arr_object['object_definitions'][$object_description_id];
						}
					}
					
					foreach ((array)$arr_object['object_subs'] as $object_sub_details_id => $arr_object_sub) {
						
						$arr_objects[$object_id]['object_subs'][uniqid()] = $arr_object_sub;
					}
					
				} else {
					
					$arr_objects[$object_id] = $arr_object;
				}
				
			} else {
				
				$arr_objects[] = $arr_object;
			}
			
			if (microtime(true) - $process_time > 3) {
			
				status($i.' of '.$number_of_rows.' rows of the CSV file have been processed.', 'IMPORT', false, ['persist' => false]);
				$process_time = microtime(true);
			}
		}
		
		$arr_object_ids = [];
		$arr_objects_buffers = [];
		$count = 0;
		$buffer_count = 0;
		$max_buffer_size = 1000;
		
		foreach ($arr_objects as $key => $arr_object) {
			
			$arr_objects_buffers[$buffer_count][] = $key;
			$count++;
			
			if ($count == $max_buffer_size) {
				
				$buffer_count++;
				$count = 0;
			}
		}
			
		if ($update_objects && count($arr_objects)) {
			
			$arr_locked = [];
			$storage_lock = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID'], 'lock');
			
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
			
			Labels::setVariable('total', count($arr_objects));
			status(getLabel('msg_object_lock_success'));
			
			// Update objects
			DB::startTransaction('data_import_store');
			
			try {

				foreach ($arr_objects_buffers as $arr_buffer) {
					
					$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
					$storage->setMode(null, false);
					$storage->setAppend(true);			

					GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction
					
					foreach ($arr_buffer as $object_id) {
							
						$arr_object = $arr_objects[$object_id];
					
						$arr_object_ids[] = $object_id;
						$storage->setObjectID($object_id);
						$storage->store([], $arr_object['object_definitions'], $arr_object['object_subs']);
					}
				
					$storage->save();
					$storage->commit(true);
					status(count($arr_object_ids).' objects have been updated.', 'IMPORT', false, ['persist' => false]);
				}
		
			} catch (Exception $e) {

				DB::rollbackTransaction('data_import_store');
				throw($e);
			}

			DB::commitTransaction('data_import_store');
			
			$storage_lock->removeLockObject();
			
		} else {

			DB::startTransaction('data_import_store');

			try {
				
				$object_id_processing = 0;
				
				foreach ($arr_objects_buffers as $arr_buffer) {
					
					$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
					$storage->setVersioning();
					$storage->setMode(null, false);
					
					GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction

					foreach ($arr_buffer as $key) {
							
						$arr_object = $arr_objects[$key];
							
						$storage->setObjectID(false);
							
						$object_id_processing = $storage->store((array)$arr_object['object'], (array)$arr_object['object_definitions'], (array)$arr_object['object_subs']);
							
						$arr_object_ids[] = $object_id_processing;
					
					}
					
					$storage->save();
					$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
					status(count($arr_object_ids).' objects have been saved.', 'IMPORT', false, ['persist' => false]);
				}
				
				
			} catch (Exception $e) {
				
				msg('An error occured after processing a new Object with the Object ID '.$object_id_processing.'.', false, LOG_CLIENT);

				DB::rollbackTransaction('data_import_store');
				throw($e);
			}

			DB::commitTransaction('data_import_store');			
		}
		
		return $arr_object_ids;
	}
	
    public function resolveFilter() {
		
		$arr = [];
		$arr_type_filters = [];
		$number_of_rows = count($this->arr_source_file_contents);
		
		for ($i = 0; $i < $number_of_rows; $i++) {

			$arr_filter = [];
			$arr_filter_values = [];
			
			foreach ($this->arr_import_template['columns'] as $arr_column) {
				
				if ($arr_column['is_filter'] && !$arr_column['use_object_id_as_filter']) {
					
					$column_heading = $arr_column['column_heading'];
					$element_id = $arr_column['element_id'];
					$string = $this->arr_source_file_contents[$i][$column_heading];
					
					$arr_filter_values[$element_id] = $string;
					$arr_filter = $this->parseElementId($this->type_id, $element_id, $string, 'filter', false, false, false, $arr_filter);
				}
			}
			
			if (count($arr_filter)) {
				
				$arr_filter = [['object_filter' => $arr_filter]];
				$hashed_filter = md5(json_encode($arr_filter));
				$arr_type_filters[$this->type_id][$hashed_filter] = ['filter_values' => $arr_filter_values, 'filter' => $arr_filter];
			}
		}
	
		if ($arr_type_filters[$this->type_id]) {
			
			status(count($arr_type_filters[$this->type_id]).' filters.', 'IMPORT', false, ['persist' => false]);
			$arr = $this->checkObjectPairs($arr_type_filters);
		}
		
		return $arr;
	}
    
    public function resolveStringObjectPairs() {
		
		$number_of_rows = count($this->arr_source_file_contents);
		$arr = [];
		$arr_type_filters = [];
		
		foreach ($this->arr_import_template['columns'] as $arr_column) {
			
			$type_id = $arr_column['element_type_id'];
			$element_type_element_id = $arr_column['element_type_element_id'];
			$column_heading = $arr_column['column_heading'];
			$splitter = $arr_column['splitter'];
			$generate = $arr_column['generate'];
			
			if (!$type_id || $element_type_element_id == 'o_id') {
				
				continue;
			}
			
			$arr_strings = [];

			for ($i = 0; $i < $number_of_rows; $i++) {
				
				$string = $this->arr_source_file_contents[$i][$column_heading];
				
				if (!$string) {
					continue;
				}
				
				if ($splitter) {
					
					$arr_split_strings = explode($splitter, $string);
				
					if ($generate == 'multiple') {
						
						foreach ($arr_split_strings as $split_string) {
							
							if (!$split_string) {
								continue;
							}
							
							$arr_strings[] = $split_string;
						}
						
					} else {
						
						$split_string = $arr_split_string[$generate-1];
						
						if (!$split_string) {
							continue;
						}
							
						$arr_strings[] = $split_string;
					}
					
				} else {
					
					$arr_strings[] = $string;	
				}
			}
			
			$arr_strings = array_values(array_unique($arr_strings));
			$number_of_strings = count($arr_strings);
			
			for ($i = 0; $i < $number_of_strings; $i++) {
				
				$string = $arr_strings[$i];
			
				$arr_filter = ($element_type_element_id ? [['object_filter' => $this->parseElementId($type_id, $element_type_element_id, $string, 'filter')]] : ['search' => $string]);
				$hashed_filter = md5(json_encode($arr_filter));

				$arr_type_filters[$type_id][$hashed_filter] = ['filter_values' => [$string], 'filter' => $arr_filter];
			}
		}
	
		if (count($arr_type_filters)) {
			
			$arr = $this->checkObjectPairs($arr_type_filters);
		}
		
		return $arr;
	}
	
	private function checkObjectPairs($arr_type_filters) {
		
		$arr = [];
		$arr_types = StoreType::getTypes();
		
		foreach ((array)$arr_type_filters as $type_id => $arr_filters) {

			$arr_stored_hashed_filters = [];
			$arr_filters_hashes = array_keys($arr_filters);

			$res = DB::query("SELECT nodegoat_isop.string
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')." nodegoat_isop
				WHERE nodegoat_isop.type_id = ".(int)$type_id."
			");
					 
			while ($row = $res->fetchAssoc()) {
				$arr_stored_hashed_filters[] = $row['string'];
			}

			$arr_new_filters = array_values(array_diff($arr_filters_hashes, $arr_stored_hashed_filters));
			$number_of_new_filters = count($arr_new_filters);
			$arr_one_hit_filters = [];
			$process_time = microtime(true);
			
			status($number_of_new_filters.' new strings in "'.Labels::parseTextVariables($arr_types[$type_id]['name']).'".', 'IMPORT', false, ['persist' => false]);
						
			foreach ($arr_new_filters as $hash) {

				$arr_filter = $arr_filters[$hash]['filter'];
				$arr_filter_values = $arr_filters[$hash]['filter_values'];

				$filter = new FilterTypeObjects($type_id, 'id');
				$filter->setVersioning('added');
				$filter->setFilter($arr_filter);
				$arr_objects = $filter->init();				

				if (count($arr_objects) == 1) {
					
					$object_id = (int)key($arr_objects);
					$this->arr_string_object_pairs[$type_id][$hash] = $object_id;
					$json_filter_values = json_encode($arr_filter_values);
					$arr_one_hit_filters[] = "('".DBFunctions::strEscape($hash)."', '".DBFunctions::strEscape($json_filter_values)."', ".(int)$type_id.", ".(int)$object_id.")";		
										
				} else {
					
					$arr[$type_id][] = ['string' => $hash, 'filter_values' => $arr_filter_values, 'object_ids' => (count($arr_objects) ? array_slice(array_keys($arr_objects), 0, 25) : [])];
				}
				
				if (microtime(true) - $process_time > 3) {
					
					$current_count = count($arr_one_hit_filters) + count((array)$arr[$type_id]);
					status($current_count.' of '.$number_of_new_filters.' strings in "'.Labels::parseTextVariables($arr_types[$type_id]['name']).'" have been processed.', 'IMPORT', false, ['persist' => false]);
					
					$process_time = microtime(true);
				}
			}
			
		
			if (count($arr_one_hit_filters)) {
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')." 
					(string, filter_values, type_id, object_id) 
						VALUES
					".implode(',', $arr_one_hit_filters)
				);		
			}
			
			$current_count = count($arr_one_hit_filters)+count((array)$arr[$type_id]);
			if ($current_count) {
				status($current_count.' of '.$number_of_new_filters.' strings in "'.Labels::parseTextVariables($arr_types[$type_id]['name']).'" have been processed.', 'IMPORT', false, ['persist' => false]);
			}
		}
		
		return $arr;
	}
	
	private function getHashedFilter($type_id, $element_type_element_id, $string) {
		
		if ($element_type_element_id) {
			
			$arr_filter = $this->parseElementId($type_id, $element_type_element_id, $string, 'filter');
			$arr_filter = [['object_filter' => $arr_filter]];
			
		} else {
			
			$arr_filter = ['search' => $string];
		}
		
		return md5(json_encode($arr_filter));
		
	}
    
    private function parseElementId($type_id, $element_id, $value, $mode = 'store', $ref_type_id = false, $ref_type_object_sub_id = false, $ref_type_element_id = false, $arr = []) {
		
		if (!$this->arr_type_sets[$type_id]) {
			
			$this->arr_type_sets[$type_id] = StoreType::getTypeSet($type_id);
		}
		
		$arr_type_set = $this->arr_type_sets[$type_id];
		
		$arr_element = explode('_', $element_id);
		
		$value = trim($value);
		$decoded_value = html_entity_decode($value, ENT_NOQUOTES, 'UTF-8');
		
		if ($mode == 'filter') {
			$filter = true;
		}
		
		if ($arr_element[0] == 'o') {
			
			if ($arr_element[1] == 'id') {
				
				if ($filter) {
					$arr['objects'] = [$this->getObjectId($value)];
				}
				
			} else if ($arr_element[1] == 'name') {
				
				if ($filter) {
					
					$arr['object_name'] = ['equality' => '=', 'value' => $value];
					
                } else {
					
					$arr['object']['object_name_plain'] = $value;
				}
				
			} else {
				
				$object_description_id = $arr_element[1];
				
				if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id']) {

					if (!$ref_type_id) {
						
						$ref_type_id = $arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id'];
					}
					
					if ($ref_type_element_id == 'o_id') {
						
						$object_id = $this->getObjectId($value);
						
					} else {
						
						$hashed_filter = $this->getHashedFilter($ref_type_id, $ref_type_element_id, $value);
						$object_id = $this->getHashedFilterObjectId($hashed_filter, $ref_type_id);
					}

					if ($object_id) {
					
						$arr['object_definitions'][$object_description_id]['object_description_id'] = $object_description_id;
						$arr['object_definitions'][$object_description_id]['object_definition_ref_object_id'][] = $object_id;
					}
					
				} else {
					
					if ($filter) {

						$arr['object_definitions'][$object_description_id][] = ['equality' => '=', 'value' => $value]; 
						
					} else {
						
						$arr['object_definitions'][$object_description_id]['object_description_id'] = $object_description_id;
						
						if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_has_multi']) {
							
							$arr['object_definitions'][$object_description_id]['object_definition_value'][] = $decoded_value;
							
						} else {
							
							$arr['object_definitions'][$object_description_id]['object_definition_value'] = $decoded_value;
						}
					}
				}
			}
			
		} else if ($arr_element[0] == 'so') {

			$object_sub_details_id = $arr_element[1];
			$object_sub_element = $arr_element[2];
			$object_sub_description_id = $arr_element[3];
		
			$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_details_id'] = $object_sub_details_id;
			
			if (!$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_date_start'] && !$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_date_end']) {
				
				$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_date_start'] = false;
				$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_date_end'] = false;
			}
			
			switch ($object_sub_element) {
				
				case 'date-start':

						if ($filter) {
							
							$arr['object_subs'][$object_sub_details_id]['object_sub_dates']['object_sub_date_from'] = $decoded_value;
							
						} else if ($decoded_value) {
							
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_date_start'] = $decoded_value;
						}

					break;
				case 'date-end':
				
						if ($filter) {
							
							$arr['object_subs'][$object_sub_details_id]['object_sub_dates']['object_sub_date_to'] = $decoded_value;
							
						} else if ($decoded_value) {
							
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_date_end'] = $decoded_value;
						}

					break;
				case 'geometry':
				
						if ($filter) {
							
							$arr['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_type'] = 'geometry';
							$arr['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_geometry'] = $value;
														
						} else {
				
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_type'] = 'geometry';
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_geometry'] = $value;
						}
					
					break;
				case 'lat':
				
						if ($filter) {
							
							$arr['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_type'] = 'point';
							$arr['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_latitude'] = $decoded_value;
							
						} else {
				
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_type'] = 'point';
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_latitude'] = $decoded_value;
						}
					
					break;
				case 'lon':
				
						if ($filter) {
							
							$arr['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_type'] = 'point';
							$arr['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_longitude'] = $decoded_value;
							
						} else {

							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_type'] = 'point';
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_longitude'] = $decoded_value;
						}
						
					break;
				case 'location-ref-type-id':
				
						if ($ref_type_element_id == 'o_id') {
							
							$object_id = $this->getObjectId($value);
							
						} else {
													
							$hashed_filter = $this->getHashedFilter($ref_type_id, $ref_type_element_id, $value);
							$object_id = $this->getHashedFilterObjectId($hashed_filter, $ref_type_id);
						}
					
						if ($object_id) {
					
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_type'] = 'reference';
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_ref_type_id'] = $ref_type_id;
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_ref_object_sub_details_id'] = $ref_type_object_sub_id;
							$arr['object_subs'][$object_sub_details_id]['object_sub']['object_sub_location_ref_object_id'] = $object_id;

						}

					break;
				case 'osd':
			
						if ($filter) {
							
							
						} else {
						
							$arr['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_description_id'] = $object_sub_description_id;
							
							if ($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_ref_type_id']) {
								
								if ($ref_type_element_id == 'o_id') {
									
									$object_id = $this->getObjectId($value);
									
								} else {
									
									$hashed_filter = $this->getHashedFilter($ref_type_id, $ref_type_element_id, $value);
									$object_id = $this->getHashedFilterObjectId($hashed_filter, $ref_type_id);
								}
						
								$arr['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id'] = $object_id;
								
							} else {
								
								$arr['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value'] = $decoded_value;
							}
						}
					break;
			}
		}
			
		return $arr;	
		
	}	
	
	private function getHashedFilterObjectId($hashed_filter, $type_id) {

		if ($this->arr_string_object_pairs[$type_id][$hashed_filter]) {
			
			return $this->arr_string_object_pairs[$type_id][$hashed_filter];
		}
		
		$res = DB::query("SELECT nodegoat_isop.object_id
			FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')." nodegoat_isop
			WHERE nodegoat_isop.string = '".DBFunctions::strEscape($hashed_filter)."'
				AND nodegoat_isop.type_id = '".DBFunctions::strEscape($type_id)."'
		");
				 
		while ($row = $res->fetchAssoc()) {
			$object_id = $row['object_id'];
		}
		
		$this->arr_string_object_pairs[$type_id][$hashed_filter] = $object_id;

		return $object_id;
	}
	
	private static function getObjectId($string) {

		if ((int)$string) {
			
			// Object ID
			$object_id = (int)$string;
				
		} else {
			
			// nodegoat ID
			$arr_type_object_id = GenerateTypeObjects::decodeTypeObjectId($string);
			
			if ($arr_type_object_id) {
				$object_id = (int)$arr_type_object_id['object_id'];
			}
		}
		
		return $object_id;
	}
}
