<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class IngestTypeObjects {
	
	const MODE_UPDATE = 1;
	const MODE_OVERWRITE = 2; // And implicity ADD
	const MODE_OVERWRITE_IF_NOT_EXISTS = 3; // And implicity ADD
		
	
	protected $type_id = false;
	protected $arr_owner = false;
	
	protected $arr_template_pointers = [];
	protected $num_row_pointers = false;
	protected $arr_limit = [];
	
	protected $file_source = false;
	protected $stream_source = false;
	protected $count_stream = false;
	
	protected $count_row_pointer = false;
	protected $str_row_pointer = false;
	
	protected $arr_objects = [];
	protected $arr_filter_objects = [];
	
	protected $arr_type_sets = [];
	protected $store_pair = false;
	protected $is_ignorable_pairs = false;
	
	protected $mode = self::MODE_OVERWRITE;
	protected $arr_options = [];
	protected $arr_append = [];
	
	protected $template_id = false;
	protected $use_log = false;
	protected $arr_log = [];
	
	protected $stmt_log_add = null;
	protected $stmt_log_update = null;
	protected $stmt_log_error = null;
	
	protected $arr_object_id_row_identifier = [];
	
	protected static $num_store_objects_buffer = 1000;
	
	const TIMEOUT_STATUS = 5; // Seconds
			
    public function __construct($type_id, $arr_owner = false) {

		$this->type_id = (int)$type_id;
		
		$this->arr_owner = $arr_owner;
		
		$this->arr_type_sets[$this->type_id] = StoreType::getTypeSet($this->type_id);
		$this->store_pair = new StorePatternsTypeObjectPair();
	}
	
	public function setTemplate($arr_template_pointers, $mode) {
		
		$this->arr_template_pointers = [
			'filter_value' => ($arr_template_pointers['filter_value'] ?: []),
			'filter_object_identifier' => ($arr_template_pointers['filter_object_identifier'] ?: []),
			'filter_object_value' => ($arr_template_pointers['filter_object_value'] ?: []),
			'filter_object_sub_identifier' => ($arr_template_pointers['filter_object_sub_identifier'] ?: []),
			'map' => ($arr_template_pointers['map'] ?: [])
		];
		
		$this->mode = (int)$mode;
		
		if (!$this->mode) {
			$this->mode = (($arr_template_pointers['filter_object_identifier'] || $arr_template_pointers['filter_object_value'] || $arr_template_pointers['query_object_value']) ? self::MODE_UPDATE : self::MODE_OVERWRITE);
		}
	}
	
	public function getMode() {
		
		return $this->mode;
	}
	
	public function setFilter($arr_filter_objects) {
		
		$this->arr_filter_objects = $arr_filter_objects;
	}
		
	public function setSource($file_source) {
		
		if (!is_resource($file_source)) {
			$this->file_source = fopen($file_source, 'r');
		} else {
			$this->file_source = $file_source;
		}

		$this->stream_source = new StreamJSONInput($this->file_source);
		
		$this->count_stream = 0;
		$this->count_row_pointer = 0;
		
		$this->stream_source->init('[', function($str) {
			
			$this->str_row_pointer = $str;

			if ($this->count_stream >= $this->count_row_pointer) {
				$this->stream_source->stop();
			}
			
			$this->count_stream++;
		});
    }
    
    public function useLogIdentifier($template_id = false) {
		
		$this->use_log = (bool)$template_id;
		$this->template_id = (int)$template_id;	
		
		$this->arr_log = ($this->use_log ? ['rows' => []] : []);
	}
		
	public function getRowPointerCount() {
		
		if ($this->num_row_pointers !== false) {
		
			return $this->num_row_pointers;
		}
		
		$this->num_row_pointers = 0;
		
		$pos = ftell($this->file_source); // Store current file position to be able to restore it
		
		$stream = new StreamJSONInput($this->file_source);
		
		$stream->init('[', function() {
			
			$this->num_row_pointers++;
		});
		
		fseek($this->file_source, $pos);
				
		return $this->num_row_pointers;
	}
	
	public function setLimit($arr_limit) {
		
		// $arr_limit = 100, array(200, 100) (from 200 to 300)
		
		$arr_limit = (is_array($arr_limit) ? $arr_limit : [0, $arr_limit]);
		
		$this->arr_limit = $arr_limit;
	}
	
	public function getLimit() {
		
		$num_total = $this->getRowPointerCount();
		
		$num_start = ($this->arr_limit[0] ?: 0);
		$num_start = ($num_start > $num_total ? $num_total : $num_start);
		
		$num_offset = $this->arr_limit[1];
		$num_offset = ($num_offset ? $num_offset : $num_total);
		$num_offset = (($num_start + $num_offset) >= $num_total ? ($num_total - $num_start) : $num_offset);
		
		$num_end = ($num_start + $num_offset);

		return [$num_start, $num_offset, $num_end];
	}
    
	public function store() {

		$arr_return = ['locked' => null, 'count' => null, 'error' => null, 'mode' => $this->mode];
		
		if (!$this->arr_objects) {
			return $arr_return;
		}

		GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction
		
		$arr_object_ids = [];
		$arr_objects_buffers = [];
		$count = 0;
		$count_buffer = 0;
		$str_error = false;
	
		foreach ($this->arr_objects as $object_id => $arr_object) {
			
			$arr_objects_buffers[$count_buffer][] = $object_id;
			$count++;
			
			if ($count == static::$num_store_objects_buffer) {
				
				$count_buffer++;
				$count = 0;
			}
		}
		
		$num_row = 0;
		
		if ($this->mode == self::MODE_UPDATE) {
			
			$arr_locked = [];
			$arr_locked_objects = [];
			$storage_lock = new StoreTypeObjects($this->type_id, false, $this->arr_owner, 'lock');
			
			foreach ($this->arr_objects as $object_id => $arr_object) {
				
				$storage_lock->setObjectID($object_id);
				
				try {
					
					$storage_lock->handleLockObject();
					
				} catch (Exception $e) {
					
					$arr_locked[] = $e;
					$arr_locked_objects[] = $object_id;
				}
			}
			
			if ($arr_locked) {

				$storage_lock->removeLockObject();
				
				foreach ($arr_locked as &$e) {
								
					$e = Trouble::strMsg($e); // Convert to message only
				}
				unset($e);
				
				$arr_return['locked'] = $arr_locked;
				$str_error = 'msg_ingest_locked';
				
				if ($this->use_log) {
					
					foreach ($arr_locked_objects as $object_id) {
						
						$num_row = $this->arr_object_id_row_identifier[$object_id];
						$this->addSourcePointerLogError((int)$num_row, $str_error);
					}
				}
				
				return $arr_return;
			}
			
			$storage_lock->upgradeLockObject(); // Apply permanent lock
			
			Labels::setVariable('total', count($this->arr_objects));
			status(getLabel('msg_object_lock_success'));
			
			// Update objects
			
			$storage = new StoreTypeObjects($this->type_id, false, $this->arr_owner);
			$storage->setVersioning(true);
			$storage->setMode(StoreTypeObjects::MODE_UPDATE, false);
			$storage->setAppend($this->arr_append);
			
			$num_row = 0;
			
			DB::startTransaction('ingest_store');
			
			try {

				foreach ($arr_objects_buffers as $arr_buffer) {

					foreach ($arr_buffer as $object_id) {
							
						$arr_object = $this->arr_objects[$object_id];
						$num_row = $this->arr_object_id_row_identifier[$object_id];

						$storage->setObjectID($object_id);
						
						$storage->store((array)$arr_object['object'], (array)$arr_object['object_definitions'], (array)$arr_object['object_subs']);
						
						$arr_object_ids[] = $object_id;
					}
					
					$storage->save();
					
					$storage->commit(true);
					
					status(count($arr_object_ids).' objects have been updated.', 'IMPORT', false, ['persist' => false]);
				}
		
			} catch (Exception $e) {

				DB::rollbackTransaction('ingest_store');

				$num_row_error = $num_row + 1;
				Labels::setVariable('row_number', $num_row_error);
				msg(getLabel('msg_ingest_error_at_row_number'), false, LOG_CLIENT);

				if ($e instanceof RealTroubleDB) {
					
					$e_previous = $e->getPrevious(); // Get DBTrouble
					$str_error = DB::getErrorMessage($e_previous->getCode());
				}
				
				$arr_return['error'] = $num_row;
			}

			if ($str_error === false) {
				
				$storage->touch(); // Make sure the objects get a status update as late as possible
				
				DB::commitTransaction('ingest_store');
			}
			
			$storage_lock->removeLockObject();
		} else {
			
			$storage = new StoreTypeObjects($this->type_id, false, $this->arr_owner);
			$storage->setVersioning();
			$storage->setMode(null, false);
			
			$num_row = 0;
			
			DB::startTransaction('ingest_store');

			try {
				
				$object_id_processing = 0;
				
				foreach ($arr_objects_buffers as $arr_buffer) {

					$arr_buffer_stored_objects = [];
					
					foreach ($arr_buffer as $key) {
							
						$arr_object = $this->arr_objects[$key];
						
						$storage->setObjectID(false);
						
						$object_id_processing = $storage->store((array)$arr_object['object'], (array)$arr_object['object_definitions'], (array)$arr_object['object_subs']);
						
						$arr_object_ids[] = $object_id_processing;
						$arr_buffer_stored_objects[$num_row] = $object_id_processing;
						
						$num_row++;
					}
					
					$storage->save();
					
					$storage->commit(true);
					
					if ($this->use_log) {
						
						foreach ($arr_buffer_stored_objects as $num_row_stored => $object_id_new) {
							
							$this->addSourcePointerLog($num_row_stored, $object_id_new);
						}
					}

					status(count($arr_object_ids).' objects have been saved.', 'IMPORT', false, ['persist' => false]);
				}
			} catch (Exception $e) {

				DB::rollbackTransaction('ingest_store');
				
				$num_row_error = $num_row + 1;
				Labels::setVariable('row_number', $num_row_error);
				msg(getLabel('msg_ingest_error_at_row_number'), false, LOG_CLIENT);
				
				if ($e instanceof RealTroubleDB) {
					
					$e_previous = $e->getPrevious(); // Get DBTrouble
					$str_error = DB::getErrorMessage($e_previous->getCode());
				}
				
				$arr_return['error'] = $num_row;
			}

			if ($str_error === false) {
				
				$storage->touch(); // Make sure the objects get a status update as late as possible
				
				DB::commitTransaction('ingest_store');	
			}		
		}
		
		if ($str_error !== false && $this->use_log) {
			
			$this->addSourcePointerLogError((int)$num_row, substr($str_error, 4, -1));
		}
		
		$arr_return['count'] = count($arr_object_ids);
		
		return $arr_return;
	}
	
    public function resolveFilters($is_ignorable = false) {
		
		$this->is_ignorable_pairs = $is_ignorable;
		
		if (!$this->hasResolveFilters()) {
			return false;
		}
		
		$arr_unresolved_object_filters = $this->resolveObjectFilters();
		
		if (count($arr_unresolved_object_filters)) {
			
			return $arr_unresolved_object_filters;
		}
		
		$arr_unresolved_element_filters = $this->resolveElementFilters();
		
		if (count($arr_unresolved_element_filters)) {
			
			return $arr_unresolved_element_filters;
		}
		
		return false;
	}
	
	public function hasResolveFilters() {
		
		if ($this->arr_template_pointers['filter_object_value']) {
			return true;
		}
				
		foreach ($this->arr_template_pointers['map'] as $arr_pointer) {
			
			$type_id = $arr_pointer['element_type_id'];
			$element_type_element_id = $arr_pointer['element_type_element_id'];
			
			if (!$type_id || $element_type_element_id == 'object-id') {
				continue;
			}
			
			return true;
		}
		
		return false;
	}
	
    protected function resolveObjectFilters() {
		
		$arr = [];
		$arr_type_filters = [];
		
		list($num_start, $num_offset, $num_end) = $this->getLimit();
		
		for ($i = $num_start; $i < $num_end; $i++) {

			$arr_filter = [];
			$arr_pattern_value = [];
			
			foreach ($this->arr_template_pointers['filter_object_value'] as $arr_pointer) {
									
				$pointer_heading = $arr_pointer['pointer_heading'];
				$element_id = $arr_pointer['element_id'];
				$string = $this->getPointerData($i, $pointer_heading);
				
				$arr_pattern_value[$element_id] = $string;
				$arr_filter = $this->parseElementIdFilter($this->type_id, $element_id, $string, $arr_filter);
			}
			
			if (count($arr_filter)) {
				
				$arr_filter = [['object_filter' => $arr_filter]];
				$str_identifier = StorePatternsTypeObjectPair::getPatternIdentifier($arr_filter);
				
				$arr_type_filters[$this->type_id][$str_identifier] = ['pattern_value' => $arr_pattern_value, 'filter' => $arr_filter];
			}
		}
	
		if ($arr_type_filters[$this->type_id]) {
			
			$count_filters = count($arr_type_filters[$this->type_id]);
			Labels::setVariable('count', $count_filters);
			
			status(getLabel('msg_ingest_filters_found'), 'IMPORT', false, ['persist' => false]);
			
			$arr = $this->checkPatternsTypesObjects($arr_type_filters, $this->is_ignorable_pairs);
		}
		
		return $arr;
	}
    
    protected function resolveElementFilters() {
		
		$arr = [];
		$arr_type_filters = [];
		
		list($num_start, $num_offset, $num_end) = $this->getLimit();
		
		foreach ($this->arr_template_pointers['map'] as $arr_pointer) {
			
			$type_id = $arr_pointer['element_type_id'];
			$element_type_element_id = $arr_pointer['element_type_element_id'];
			$pointer_heading = $arr_pointer['pointer_heading'];
			$str_splitter = $arr_pointer['value_split'];
			$value_index = $arr_pointer['value_index'];
			
			if (!$type_id || $element_type_element_id == 'object-id') {
				continue;
			}
			
			$arr_str = [];

			for ($i = $num_start; $i < $num_end; $i++) {
				
				$value_pointer = $this->getPointerData($i, $pointer_heading);
				
				if (!$value_pointer) {
					continue;
				}
				
				if (is_array($value_pointer) || $str_splitter) {
					
					if (!is_array($value_pointer)) {
						$value_pointer = explode($str_splitter, $value_pointer);
					}
										
					if (!$value_index || $value_index === 'multiple') {
						
						foreach ($value_pointer as $str_value) {
							
							if (!$str_value) {
								continue;
							}
							
							$arr_str[] = $str_value;
						}
					} else {
						
						$str_value = $value_pointer[(int)$value_index-1];
						
						if (!$str_value) {
							continue;
						}
						
						$arr_str[] = $str_value;
					}
				} else {
					
					$arr_str[] = $value_pointer;	
				}
			}
			
			$arr_str = array_values(array_unique($arr_str));
			$num_str = count($arr_str);
			
			for ($i = 0; $i < $num_str; $i++) {
				
				$str_value = $arr_str[$i];
				
				if ($element_type_element_id) {
					
					$arr_filter = [['object_filter' => $this->parseElementIdFilter($type_id, $element_type_element_id, $str_value)]];
					$arr_pattern_value = [$element_type_element_id => $str_value];
				} else {
					
					$arr_filter = ['search' => $str_value];
					$arr_pattern_value = [$str_value];
				}
				
				$str_identifier = StorePatternsTypeObjectPair::getPatternIdentifier($arr_filter);

				$arr_type_filters[$type_id][$str_identifier] = ['pattern_value' => $arr_pattern_value, 'filter' => $arr_filter];
			}
		}
	
		if (count($arr_type_filters)) {
			
			$arr = $this->checkPatternsTypesObjects($arr_type_filters, $this->is_ignorable_pairs);
		}
		
		return $arr;
	}
	
	protected function checkPatternsTypesObjects($arr_type_filters, $is_ignorable = false) {
	
		$arr = [];
		$arr_types = StoreType::getTypes();
		
		foreach ((array)$arr_type_filters as $type_id => $arr_identifiers_filters) {
			
			$arr_identifiers = array_keys($arr_identifiers_filters);
			
			$arr_pair_identifiers = $this->store_pair->checkPatternsTypeObject($type_id, $arr_identifiers, $is_ignorable);

			if (!$arr_pair_identifiers['new']) {
				continue;
			}

			$time_process = microtime(true);
			$count_status = 0;
			$count_matched = 0;
			
			$num_new = count($arr_pair_identifiers['new']);
			
			Labels::setVariable('type', $arr_types[$type_id]['name']);
			Labels::setVariable('count', $num_new);
			
			status(getLabel('msg_ingest_strings_found'), 'IMPORT', false, ['persist' => false]);
						
			foreach ($arr_pair_identifiers['new'] as $str_identifier) {

				$arr_filter = $arr_identifiers_filters[$str_identifier]['filter'];
				$arr_pattern_value = $arr_identifiers_filters[$str_identifier]['pattern_value'];

				$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
				$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
				$filter->setFilter($arr_filter);

				$arr_filter_error = [];

				try {
					
					$arr_objects = $filter->init();
				} catch (Exception $e) {

					$arr_filter_error[] = $e;
				}
				
				if ($arr_filter_error) {
					
					foreach ($arr_filter_error as &$e) {
						
						$arr_code = Trouble::parseCode($e);
						
						if ($arr_code['code'] == TROUBLE_DATABASE) {
							
							$e_previous = $e->getPrevious(); // Get DBTrouble
							$e = DB::getErrorMessage($e_previous->getCode());
						} else if ($arr_code['suppress'] == LOG_BOTH || $arr_code['suppress'] == LOG_CLIENT) {
							
							$e = Trouble::strMsg($e); // Convert to message only
						} else {
							$e = false;
						}
						
						if (!$e) {
							$e = 'Error not visible in log.';
						}
					}
					
					return ['error' => ['message' => $arr_filter_error, 'values' => $arr_pattern_value]];
				}
				
				$num_count_objects = count($arr_objects);
				
				if ($num_count_objects == 1) { // Direct single match to the filter, store the string/object pair
					
					$object_id = (int)key($arr_objects);
					
					$this->store_pair->storeTypeObjectPair($type_id, $str_identifier, $object_id, $arr_pattern_value);
					
					$count_matched++;				
				} else {
					
					if (!$num_count_objects && $this->mode == self::MODE_OVERWRITE_IF_NOT_EXISTS) {
						
						// Is new
					} else {
						
						$arr[$type_id][] = ['identifier' => $str_identifier, 'pattern_value' => $arr_pattern_value, 'object_ids' => ($num_count_objects ? array_slice(array_keys($arr_objects), 0, 25) : []), 'is_ignored' => isset($arr_pair_identifiers['ignorable'][$str_identifier])];
					}
				}
				
				if (microtime(true) - $time_process > static::TIMEOUT_STATUS) {
					
					$count_new = count((array)$arr[$type_id]);
					$count_status = ($count_matched + $count_new);
					Labels::setVariable('count_status', $count_status);
					Labels::setVariable('count_matched', $count_matched);
					Labels::setVariable('count_new', $count_new);
					
					status(getLabel('msg_ingest_strings_processed'), 'IMPORT', false, ['persist' => false]);
					
					$time_process = microtime(true);
				}
			}
			
			$count_new = count((array)$arr[$type_id]);
			$count_status_final = ($count_matched + $count_new);
			
			$this->store_pair->commitPairs(); // Commit newly added pairs

			if ($count_status_final > $count_status) {
				
				Labels::setVariable('count_status', $count_status_final);
				Labels::setVariable('count_matched', $count_matched);
				Labels::setVariable('count_new', $count_new);
				
				status(getLabel('msg_ingest_strings_processed'), 'IMPORT', false, ['persist' => false]);
			}
		}
		
		return $arr;
	}
	
	protected function getFilterIdentifier($type_id, $element_type_element_id, $str) {
		
		if ($element_type_element_id) {
			
			$arr_filter = $this->parseElementIdFilter($type_id, $element_type_element_id, $str);
			$arr_filter = [['object_filter' => $arr_filter]];
		} else {
			
			$arr_filter = ['search' => $str];
		}
		
		return StorePatternsTypeObjectPair::getPatternIdentifier($arr_filter);
	}
	
	public function process() {
		
		$this->initLog();

		$stmt_check_object = false;
		$stmt_check_object_sub = false;
		
		if ($this->mode == self::MODE_UPDATE || $this->mode == self::MODE_OVERWRITE_IF_NOT_EXISTS) {
			
			if ($this->arr_filter_objects) {
			
				$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);			
				$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
				$filter->setFilter($this->arr_filter_objects);
				
				$table_name = $filter->storeResultTemporarily(uniqid(), true);
				
				$stmt_check_object = DB::prepare("SELECT TRUE
						FROM ".$table_name."
					WHERE id = ".DBStatement::assign('object_id', 'i')."
					LIMIT 1
				");
			}
			
			if (isset($this->arr_template_pointers['filter_object_sub_identifier'])) {
				
				$stmt_check_object_sub = DB::prepare("SELECT object_id, object_sub_details_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')."
					WHERE id = ".DBStatement::assign('object_sub_id', 'i')."
					LIMIT 1
				");
			}
		}
		
		$this->arr_objects = [];
		$time_process = microtime(true);
		$do_check_identical = false;
		
		list($num_start, $num_offset, $num_end) = $this->getLimit();
		
		for ($i = $num_start; $i < $num_end; $i++) {
			
			$arr_object = ['object' => [], 'object_definitions' => [], 'object_subs' => []];
			$arr_collected_filter = [];
			$object_id = false;
			$object_sub_id = false;
			$str_identifier = false;
			
			foreach ($this->arr_template_pointers as $type => $arr_type_pointers) {
				
				if (!$arr_type_pointers) {
					continue;
				}
				
				if ($type == 'filter_object_identifier') {
					
					if ($arr_type_pointers['object_description_id']) {
						
						$pointer_heading = 'uri';
						$value_pointer = $this->getPointerData($i, $pointer_heading);
						
						$object_id = $this->getTypeObjectIDByIdentifier($value_pointer, $arr_type_pointers['object_description_id']);
					} else {
						
						$pointer_heading = $arr_type_pointers['pointer_heading'];
						$value_pointer = $this->getPointerData($i, $pointer_heading);
						
						$object_id = GenerateTypeObjects::parseTypeObjectID($value_pointer);
					}
				} else if ($type == 'filter_object_sub_identifier') {
											
					$pointer_heading = $arr_type_pointers['pointer_heading'];
					$value_pointer = $this->getPointerData($i, $pointer_heading);
						
					$object_sub_id = (int)$value_pointer;
				} else {
									
					foreach ($arr_type_pointers as $arr_pointer) {

						$pointer_heading = $arr_pointer['pointer_heading'];
						$element_id = $arr_pointer['element_id'];
						$value_pointer = $this->getPointerData($i, $pointer_heading);

						if ($type == 'filter_object_value') {
							
							$arr_collected_filter[] = ['element_id' => $element_id, 'value' => $value_pointer];
						} else { // 'map'

							$str_splitter = $arr_pointer['value_split'];
							$value_index = $arr_pointer['value_index'];
							$ref_type_id = $arr_pointer['element_type_id'];
							$ref_type_object_sub_id = $arr_pointer['element_type_object_sub_id'];
							$ref_type_element_id = $arr_pointer['element_type_element_id'];
							
							$arr_options = ['overwrite' => ($arr_pointer['mode_write'] == 'overwrite'), 'ignore_empty' => $arr_pointer['ignore_empty'], 'ignore_identical' => $arr_pointer['ignore_identical'], 'row_identifier' => $i, 'pointer_heading' => $pointer_heading];
										
							if ($arr_options['ignore_identical']) {
								$do_check_identical = true;
							}
									
							$arr_values = [];
							
							$is_array = is_array($value_pointer);
							
							if ($is_array || $str_splitter) {
								
								if (!$is_array) {
									$value_pointer = explode($str_splitter, $value_pointer);
								}
								
								if (!$value_index || $value_index === 'multiple') {

									$arr_values = $value_pointer;
								} else {
									
									$arr_values[] = $value_pointer[(int)$value_index-1];
								}
							} else {
								
								$arr_values[] = $value_pointer;	
							}

							foreach ($arr_values as $key => $str_value) {
						
								$arr_object = $this->formatElementIdentifierValue($this->type_id, $element_id, $str_value, $key, $ref_type_id, $ref_type_object_sub_id, $ref_type_element_id, $arr_object, $arr_options);
							}	
						}
					}
				}
			}
			
			if ($this->mode == self::MODE_UPDATE || $this->mode == self::MODE_OVERWRITE_IF_NOT_EXISTS) {
	
				if (!$object_id) {
					
					$arr_filter = [];
					
					foreach ($arr_collected_filter as $arr_collected_filter_part) {
						
						$arr_filter = $this->parseElementIdFilter($this->type_id, $arr_collected_filter_part['element_id'], $arr_collected_filter_part['value'], $arr_filter);
					}
					
					$str_identifier = StorePatternsTypeObjectPair::getPatternIdentifier([['object_filter' => $arr_filter]]);
					$object_id = $this->store_pair->getPatternTypeObjectID($this->type_id, $str_identifier);
				}
			}
			
			if ($this->mode == self::MODE_UPDATE) {
				
				if (!$object_id) {
					continue;
				}
				
				if ($stmt_check_object !== false) {
					
					$stmt_check_object->bindParameters(['object_id' => $object_id]);
					$res = $stmt_check_object->execute();
					
					if (!$res->getRowCount()) {
						continue;
					}
				}

				if ($do_check_identical) {
					$arr_object = $this->checkTypeObjectIdenticalValues($object_id, $arr_object, $i);
				}
			
				// Merge data with data that has been assigned to this object in a previous run
				
				if (isset($this->arr_objects[$object_id])) {
					
					foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definitions) {
						
						if (isset($this->arr_objects[$object_id]['object_definitions'][$object_description_id])) {
							
							if ($arr_object_definitions['object_definition_ref_object_id'] && is_array($arr_object_definitions['object_definition_ref_object_id'])) {
								
								$this->arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_ref_object_id'] = array_merge($this->arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_ref_object_id'], $arr_object_definitions['object_definition_ref_object_id']);
							}
							
							if ($arr_object_definitions['object_definition_value'] && is_array($arr_object_definitions['object_definition_value'])) {
								
								$this->arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_value'] = array_merge($this->arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_value'], $arr_object_definitions['object_definition_value']);
							}
							
						} else {
							
							$this->arr_objects[$object_id]['object_definitions'][$object_description_id] = $arr_object['object_definitions'][$object_description_id];
						}
					}
					
				} else {
					
					$this->arr_objects[$object_id]['object_definitions'] = $arr_object['object_definitions'];
				}
				
				$has_match_object_sub_id = null;
				
				if ($stmt_check_object_sub !== false && $object_sub_id) {
					
					$stmt_check_object_sub->bindParameters(['object_sub_id' => $object_sub_id]);
					$res = $stmt_check_object_sub->execute();
					
					$has_match_object_sub_id = false;
					
					if ($res->getRowCount()) {
						
						$arr_object_sub_info = $res->fetchAssoc();
						
						if ($arr_object_sub_info['object_id'] == $object_id) {
							
							foreach ($arr_object['object_subs'] as $key => $arr_object_sub) {
								
								if ($arr_object_sub['object_sub']['object_sub_details_id'] != $arr_object_sub_info['object_sub_details_id']) {
									continue;
								}
								
								$has_match_object_sub_id = true;
							
								$arr_object_sub['object_sub']['object_sub_id'] = $object_sub_id;
								unset($arr_object['object_subs'][$key]);
							
								$this->arr_objects[$object_id]['object_subs'][$object_sub_id] = $arr_object_sub;
								
								break;
							}
						}
					}
					
					if (!$has_match_object_sub_id) {
						
						$this->arr_log['rows'][$i]['results']['filter_object_sub_identifier']['no_object_sub'] = true;
					}
				}
				
				if ($has_match_object_sub_id === null || $has_match_object_sub_id === true) { // Do not store sub-object when a wanted match has not succeeded
					
					foreach ($arr_object['object_subs'] as $key => $arr_object_sub) {
						
						$use_key = (isset($this->arr_objects[$object_id]['object_subs'][$key]) ? uniqid() : $key);
					
						$this->arr_objects[$object_id]['object_subs'][$use_key] = $arr_object_sub;
					}
				}
			} else if ($this->mode == self::MODE_OVERWRITE_IF_NOT_EXISTS) {
				
				if ($object_id) {
					
					if ($stmt_check_object !== false) {
						
						$stmt_check_object->bindParameters(['object_id' => $object_id]);
						$res = $stmt_check_object->execute();
						
						if ($res->getRowCount()) {
							continue;
						}
						
						$str_identifier = $object_id;
						$object_id = false;
					} else {
						
						continue;
					}
				}
				
				$this->arr_objects[$str_identifier] = $arr_object;
			} else {
				
				$this->arr_objects[] = $arr_object;
			}
			
			if ($object_id) {
				$this->arr_object_id_row_identifier[$object_id] = $i;
			}
			
			if ($this->use_log) {

				$str_data = value2JSON($this->getPointerData($i));
				$str_results = ($this->arr_log['rows'][$i] ? value2JSON($this->arr_log['rows'][$i]['results']) : null);
				$str_filter = ($arr_filter ? value2JSON($arr_filter) : null);
				
				$this->addSourcePointerLog($i, $object_id, $str_data, $str_filter, $str_results);
			}
			
			if (microtime(true) - $time_process > static::TIMEOUT_STATUS) {
			
				status($i.' of '.$this->num_row_pointers.' results have been processed.', 'IMPORT', false, ['persist' => false]);
				$time_process = microtime(true);
			}
		}
				
		return true;
	}
	
	public function getPointerData($pointer_row, $pointer_heading = false) {
	
		if ($pointer_row == $this->count_row_pointer) {
			// Already pointing
		} else {
			
			if ($pointer_row < $this->count_row_pointer) {
			
				$this->stream_source->reset();
				$this->count_stream = 0;
			}
			
			$this->count_row_pointer = $pointer_row;
			$this->stream_source->resume();
		}
	
		$arr_row = json_decode($this->str_row_pointer, true);
		$arr_row = $arr_row[0];
		
		if ($pointer_heading === false) {
			return $arr_row;
		} else {
			return $arr_row[$pointer_heading];
		}
	}
    	
	protected function parseElementIdFilter($type_id, $element_id, $value, $arr_filter = []) {
		
		if (!$this->arr_type_sets[$type_id]) {
			$this->arr_type_sets[$type_id] = StoreType::getTypeSet($type_id);
		}
		
		$arr_type_set = $this->arr_type_sets[$type_id];
		
		$arr_element = explode('-', $element_id);
		
		$trimmed_value = trim(str_replace('\n', PHP_EOL, $value));
		$decoded_value = html_entity_decode($trimmed_value, ENT_QUOTES, 'UTF-8');
		
		if ($arr_element[0] == 'object') {
			
			if ($arr_element[1] == 'id') {
				
				$arr_filter['objects'] = [GenerateTypeObjects::parseTypeObjectID($value)];
			} else if ($arr_element[1] == 'name') {
								
				$arr_filter['object_name'][] = ['equality' => '=', 'value' => $value];
			}
		} else if ($arr_element[0] == 'object_description') {
				
			$object_description_id = $arr_element[1];
			
			$arr_filter['object_definitions'][$object_description_id][] = ['equality' => '=', 'value' => $value]; 		
		} else if ($arr_element[0] == 'object_sub_details') {

			$object_sub_details_id = $arr_element[1];
			$str_element = $arr_element[2];

			$arr_filter['object_subs'][$object_sub_details_id]['object_sub'][$object_sub_details_id]['object_sub_details_id'] = $object_sub_details_id;
			
			switch ($str_element) {
				
				case 'date_chronology':

					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_dates'][$object_sub_details_id]['object_sub_date_type'] = 'range';
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_dates'][$object_sub_details_id]['object_sub_date_chronology'] = $value;

					break;
				case 'date_start':

					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_dates'][$object_sub_details_id]['object_sub_date_type'] = 'range';
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_dates'][$object_sub_details_id]['object_sub_date_from'] = $decoded_value;

					break;
				case 'date_end':
				
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_dates'][$object_sub_details_id]['object_sub_date_type'] = 'range';
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_dates'][$object_sub_details_id]['object_sub_date_to'] = $decoded_value;

					break;
				case 'location_geometry':
				
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_type'] = 'geometry';
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_geometry'] = $value;

					break;
				case 'location_latitude':

					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_type'] = 'point';
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_latitude'] = $decoded_value;
					
					break;
				case 'location_longitude':

					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_type'] = 'point';
					$arr_filter['object_subs'][$object_sub_details_id]['object_sub_locations'][$object_sub_details_id]['object_sub_location_longitude'] = $decoded_value;

					break;
				case 'object_sub_description':
				
					$object_sub_description_id = $arr_element[3];
					
					// NA
								
					break;
			}
		}
			
		return $arr_filter;	
	}
	
	protected function formatElementIdentifierValue($type_id, $element_id, $str_value, $str_key, $ref_type_id, $ref_type_object_sub_id, $ref_type_element_id, $arr_object, $arr_options) {
		
		if (!$this->arr_type_sets[$type_id]) {
			$this->arr_type_sets[$type_id] = StoreType::getTypeSet($type_id);
		}
		
		$arr_type_set = $this->arr_type_sets[$type_id];
		
		$arr_element = explode('-', $element_id);
		
		$str_value_trimmed = trim(str_replace('\n', PHP_EOL, $str_value));
		$str_value_decoded = html_entity_decode($str_value_trimmed, ENT_QUOTES, 'UTF-8');
		
		$num_row = $arr_options['row_identifier'];
		$pointer_heading = $arr_options['pointer_heading'];
		
		// Return if no value and option set to ignore empty values
		if ($str_value_decoded === '' && $arr_options['ignore_empty']) {
		
			if ($this->use_log && $num_row !== false && $pointer_heading) {
				$this->arr_log['rows'][$num_row]['results'][$pointer_heading]['options']['ignore_empty'] = true;
			}
			
			return $arr_object;
		}

		if ($arr_element[0] == 'object') {
			
			if ($arr_element[1] == 'name') {
				
				$this->arr_options[$type_id]['name'] = $arr_options;
				
				$arr_object['object']['object_name_plain'] = $str_value_trimmed;
			}
		} else if ($arr_element[0] == 'object_description') {
				
			$object_description_id = $arr_element[1];
			
			$this->arr_options[$type_id][$object_description_id] = $arr_options;

			if (!$arr_options['overwrite']) {
				
				$this->arr_append['object_definitions'][$object_description_id] = true;
			}
			
			if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id']) {

				if (!$ref_type_id) {
					
					$ref_type_id = $arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id'];
				}
				
				if ($ref_type_element_id == 'object-id') {
					
					$object_id = GenerateTypeObjects::parseTypeObjectID($str_value);
				} else {
					
					$str_identifier = $this->getFilterIdentifier($ref_type_id, $ref_type_element_id, $str_value);
					$object_id = $this->store_pair->getPatternTypeObjectID($ref_type_id, $str_identifier);
				}
				
				$arr_object['object_definitions'][$object_description_id]['object_description_id'] = $object_description_id;
				
				if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_has_multi']) {
					$arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'][] = $object_id;
				} else {
					$arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'] = $object_id;
				}
				
				if ($object_id) {
					
					if ($this->use_log && $num_row !== false && $pointer_heading) {
						$this->arr_log['rows'][$num_row]['results'][$pointer_heading]['objects'][$ref_type_id][$object_id] = true;
					}
				} else {
					
					if ($this->use_log && $str_value && $num_row !== false && $pointer_heading) {
						$this->arr_log['rows'][$num_row]['results'][$pointer_heading]['options']['unmatched_reference'] = true;
					}
				}
				
			} else {
				
				$arr_object['object_definitions'][$object_description_id]['object_description_id'] = $object_description_id;
					
				if ($arr_type_set['object_descriptions'][$object_description_id]['object_description_has_multi']) {
					$arr_object['object_definitions'][$object_description_id]['object_definition_value'][] = $str_value_decoded;
				} else {
					$arr_object['object_definitions'][$object_description_id]['object_definition_value'] = $str_value_decoded;
				}
			}			
		} else if ($arr_element[0] == 'object_sub_details') {

			$object_sub_details_id = $arr_element[1];
			$str_object_sub_identifier = $object_sub_details_id.'_'.$str_key; // Allow for multi-sub-object grouping
			
			$str_element = $arr_element[2];

			$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_details_id'] = $object_sub_details_id;
						
			switch ($str_element) {
				
				case 'date_chronology':

					$this->arr_options[$type_id][$object_sub_details_id]['date_chronology'] = $arr_options;
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_date_chronology'] = $str_value;

					break;
				case 'date_start':

					$this->arr_options[$type_id][$object_sub_details_id]['date_start'] = $arr_options;
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_date_start'] = $str_value_decoded;

					break;
				case 'date_end':
				
					$this->arr_options[$type_id][$object_sub_details_id]['date_end'] = $arr_options;
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_date_end'] = $str_value_decoded;

					break;
				case 'location_geometry':
				
					$this->arr_options[$type_id][$object_sub_details_id]['location_geometry'] = $arr_options;
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_type'] = 'geometry';
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_geometry'] = $str_value;

					break;
				case 'location_latitude':
				
					$this->arr_options[$type_id][$object_sub_details_id]['location_latitude'] = $arr_options;
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_type'] = 'point';
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_latitude'] = $str_value_decoded;

					break;
				case 'location_longitude':
				
					$this->arr_options[$type_id][$object_sub_details_id]['location_longitude'] = $arr_options;
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_type'] = 'point';
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_longitude'] = $str_value_decoded;

					break;
				case 'location_ref_type_id':
				
					$this->arr_options[$type_id][$object_sub_details_id]['location_ref_type_id'] = $arr_options;
				
					if ($ref_type_element_id == 'object-id') {
						
						$object_id = GenerateTypeObjects::parseTypeObjectID($str_value);
					} else {
												
						$str_identifier = $this->getFilterIdentifier($ref_type_id, $ref_type_element_id, $str_value);
						$object_id = $this->store_pair->getPatternTypeObjectID($ref_type_id, $str_identifier);
					}
					
					if ($object_id) {
					
						$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_type'] = 'reference';
						$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_ref_type_id'] = $ref_type_id;
						$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_ref_object_sub_details_id'] = $ref_type_object_sub_id;
						$arr_object['object_subs'][$str_object_sub_identifier]['object_sub']['object_sub_location_ref_object_id'] = $object_id;
						
						if ($this->use_log && $num_row !== false && $pointer_heading) {
							$this->arr_log['rows'][$num_row]['results'][$pointer_heading]['objects'][$ref_type_id][$object_id] = true;
						}
						
					} else {
						
						if ($this->use_log && $str_value && $num_row !== false && $pointer_heading) {
							$this->arr_log['rows'][$num_row]['results'][$pointer_heading]['options']['unmatched_reference'] = true;
						}
					}

					break;
				case 'object_sub_description':
				
					$object_sub_description_id = $arr_element[3];
			
					$this->arr_options[$type_id][$object_sub_details_id][$object_sub_description_id] = $arr_options;
					
					if (!$arr_options['overwrite'] && $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_is_single']) {
						
						$this->arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id] = true;
					}
					
					$arr_object['object_subs'][$str_object_sub_identifier]['object_sub_definitions'][$object_sub_description_id]['object_sub_description_id'] = $object_sub_description_id;
					
					if ($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_ref_type_id']) {

						if ($ref_type_element_id == 'object-id') {
							
							$object_id = GenerateTypeObjects::parseTypeObjectID($str_value);
						} else {
							
							$str_identifier = $this->getFilterIdentifier($ref_type_id, $ref_type_element_id, $str_value);
							$object_id = $this->store_pair->getPatternTypeObjectID($ref_type_id, $str_identifier);
						}
					
						if ($object_id) {
							
							$arr_object['object_subs'][$str_object_sub_identifier]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id'] = $object_id;
								
							if ($this->use_log && $num_row !== false && $pointer_heading) {
								$this->arr_log['rows'][$num_row]['results'][$pointer_heading]['objects'][$ref_type_id][$object_id] = true;
							}
						} else {
					
							if ($this->use_log && $str_value && $num_row !== false && $pointer_heading) {
								$this->arr_log['rows'][$num_row]['results'][$pointer_heading]['options']['unmatched_reference'] = true;
							}
						}
						
					} else {
						
						$arr_object['object_subs'][$str_object_sub_identifier]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value'] = $str_value_decoded;
					}
					
					break;
			}
		}
			
		return $arr_object;	
	}
	
	protected function checkTypeObjectIdenticalValues($object_id, $arr_object, $num_row) {
	
		$arr_selection = ['object_descriptions' => (array)$arr_object['object_definitions'], 'object_sub_details' => (array)$arr_object['object_subs']];
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_STORAGE);			
		$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
		$filter->setSelection($arr_selection);
		$filter->setFilter(['objects' => $object_id]);	
		$arr_stored_object = current($filter->init());
	
		foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definitions) {
			
			$arr_options = $this->arr_options[$this->type_id][$object_description_id];
			
			if (!$arr_options['ignore_identical']) {
				continue;
			}
		
			$is_identical = false;
		
			if ($arr_stored_object['object_definitions'][$object_description_id]) {
			
				if ($arr_object_definitions['object_definition_ref_object_id']) {

					if ($arr_stored_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'] == $arr_object_definitions['object_definition_ref_object_id']) {
						
						$is_identical = true;
						unset($arr_object['object_definitions'][$object_description_id]);
					}
					
				} else if ($arr_object_definitions['object_definition_value']) {
					
					if ($arr_stored_object['object_definitions'][$object_description_id]['object_definition_value'] == $arr_object_definitions['object_definition_value']) {
						
						$is_identical = true;
						unset($arr_object['object_definitions'][$object_description_id]);
					}
				}
			}
			
			if ($is_identical) {
				
				if ($this->use_log) {
					$this->arr_log['rows'][$num_row]['results'][$arr_options['pointer_heading']]['options']['ignore_identical'] = true;
				}
			}		
		}
						 				 
		foreach ($arr_object['object_subs'] as $object_sub_details_id => $arr_object_sub) {

			// Non-single sub-objects cannot be checked for identical values
			if (!$this->arr_type_sets[$this->type_id]['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_is_single']) {
				continue;
			}
			
			$arr_stored_object_sub = false;
			
			foreach ($arr_stored_object['object_subs'] as $check_object_sub_id => $arr_object_sub_loop) {

				if ($arr_object_sub_loop['object_sub']['object_sub_details_id'] == $object_sub_details_id) {
					$arr_stored_object_sub = $arr_object_sub_loop;
				}
			}

			if (!$arr_stored_object_sub) {
				continue;
			}
			
			if ($arr_object_sub['object_sub']['object_sub_date_chronology']) {
				
				$arr_options = $this->arr_options[$this->type_id][$object_sub_details_id]['date_chronology'];
				
				if ($arr_options['ignore_identical'] && $arr_stored_object_sub['object_sub']['object_sub_date_chronology'] == $arr_object_sub['object_sub']['object_sub_date_chronology']) {

					unset($arr_object_sub['object_sub']['object_sub_date_chronology']);
					
					if ($this->use_log) {
						$this->arr_log['rows'][$num_row]['results'][$arr_options['pointer_heading']]['options']['ignore_identical'] = true;
					}								
				}
			} else if ($arr_object_sub['object_sub']['object_sub_date_start'] || $arr_object_sub['object_sub']['object_sub_date_end']) {
				
				$arr_stored_date_chronology = FormatTypeObjects::formatToChronology($arr_stored_object_sub['object_sub']['object_sub_date_chronology']);
				
				if ($arr_object_sub['object_sub']['object_sub_date_start']) {
					
					$arr_options = $this->arr_options[$this->type_id][$object_sub_details_id]['date_start'];
					
					if ($arr_options['ignore_identical'] && $arr_stored_date_chronology['start']['start']['date_value'] == FormatTypeObjects::formatToInputValue('date', $arr_object_sub['object_sub']['object_sub_date_start'])) {

						unset($arr_object_sub['object_sub']['object_sub_date_start']);
						
						if ($this->use_log) {
							$this->arr_log['rows'][$num_row]['results'][$arr_options['pointer_heading']]['options']['ignore_identical'] = true;
						}								
					}
				}
				
				if ($arr_object_sub['object_sub']['object_sub_date_end']) {
					
					$arr_options = $this->arr_options[$this->type_id][$object_sub_details_id]['date_end'];
					
					if ($arr_options['ignore_identical'] && $arr_stored_date_chronology['end']['end']['date_value'] == FormatTypeObjects::formatToInputValue('date', $arr_object_sub['object_sub']['object_sub_date_end'])) {

						unset($arr_object_sub['object_sub']['object_sub_date_end']);
						
						if ($this->use_log) {
							$this->arr_log['rows'][$num_row]['results'][$arr_options['pointer_heading']]['options']['ignore_identical'] = true;
						}								
					}
				}
			}
			
			if ($arr_object_sub['object_sub']['object_sub_location_geometry']) {
				
				$arr_options = $this->arr_options[$this->type_id][$object_sub_details_id]['location_geometry'];
				
				if ($arr_options['ignore_identical'] && $arr_stored_object_sub['object_sub']['object_sub_location_geometry'] == $arr_object_sub['object_sub']['object_sub_location_geometry']) {

					unset($arr_object_sub['object_sub']['object_sub_location_geometry']);
					
					if ($this->use_log) {
						$this->arr_log['rows'][$num_row]['results'][$arr_options['pointer_heading']]['options']['ignore_identical'] = true;
					}								
				}
			}
			
			if ($arr_object_sub['object_sub']['object_sub_location_ref_object_id']) {
				
				$arr_options = $this->arr_options[$this->type_id][$object_sub_details_id]['location_ref_type_id'];
				
				if ($arr_options['ignore_identical'] && $arr_stored_object_sub['object_sub']['object_sub_location_ref_object_id'] == $arr_object_sub['object_sub']['object_sub_location_ref_object_id']) {

					unset($arr_object_sub['object_sub']['object_sub_location_ref_object_id']);
					
					if ($this->use_log) {
						$this->arr_log['rows'][$num_row]['results'][$arr_options['pointer_heading']]['options']['ignore_identical'] = true;
					}								
				}
			}
			
			foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definitions) {

				$arr_options = $this->arr_options[$this->type_id][$object_sub_details_id][$object_sub_description_id];
				
				if (!$arr_options['ignore_identical']) {
					continue;
				}
			
				$is_identical = false;
				
				$arr_stored_object_sub_definition = $arr_stored_object_sub['object_sub_definitions'][$object_sub_description_id];
				
				if ($arr_object_sub_definitions['object_sub_definition_ref_object_id']) {

					if ($arr_stored_object_sub_definition['object_sub_definition_ref_object_id'] == $arr_object_sub_definitions['object_sub_definition_ref_object_id']) {
						
						$is_identical = true;
					}
				} else if ($arr_object_sub_definitions['object_sub_definition_value']) {

					if ($arr_stored_object_sub_definition['object_sub_definition_value'] == $arr_object_sub_definitions['object_sub_definition_value']) {
	
						$is_identical = true;
					}
				}
					
				if ($is_identical) {
					
					unset($arr_object['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]);
					
					if ($this->use_log) {
						$this->arr_log['rows'][$num_row]['results'][$arr_options['pointer_heading']]['options']['ignore_identical'] = true;
					}
				}	
			}
		}
		
		return $arr_object;
	}
		
	protected function getTypeObjectIDByIdentifier($identifier, $object_description_id) {
		
		$arr_type_objects = FilterTypeObjects::getTypesObjectsByObjectDescriptions($identifier, [$this->type_id => [$object_description_id]]);
		
		if (!$arr_type_objects[$this->type_id]) {
			return false;
		}
		
		$object_id = current($arr_type_objects[$this->type_id]);
		
		return $object_id;
	}
	
	protected function initLog() {
		
		if (!$this->use_log || $this->stmt_log_add !== null) {
			return;
		}
		
		$this->stmt_log_add = DB::prepare("INSERT INTO ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')."
			(template_id, row_identifier, object_id, row_data, row_filter, row_results)
				VALUES
			(".(int)$this->template_id.", ".DBStatement::assign('num', 'i').", ".DBStatement::assign('object', 'i').", ".DBStatement::assign('data', 's').", ".DBStatement::assign('filter', 's').", ".DBStatement::assign('results', 's').")
		");
		
		$this->stmt_log_update = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')." SET 
				object_id = ".DBStatement::assign('object', 'i')."
			WHERE template_id = ".(int)$this->template_id."
				AND row_identifier = ".DBStatement::assign('num', 'i')."
		");
		
		$this->stmt_log_error = DB::prepare("UPDATE ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')." SET 
				row_results = ".DBStatement::assign('results', 's')."
			WHERE template_id = ".(int)$this->template_id."
				AND row_identifier = ".DBStatement::assign('num', 'i')."
		");
	}
	
	public function addSourcePointerLog($num_pointer, $object_id, $str_data = null, $str_filter = null, $str_results = null) {
	
		if ($str_data !== null) {
			
			$this->stmt_log_add->bindParameters(['num' => (int)$num_pointer, 'object' => ($object_id ? (int)$object_id : null), 'data' => $str_data, 'filter' => $str_filter, 'results' => $str_results]);
			$this->stmt_log_add->execute();
		} else {
			
			$this->stmt_log_update->bindParameters(['num' => (int)$num_pointer, 'object' => (int)$object_id]);
			$this->stmt_log_update->execute();
		}
	}
	
	public function addSourcePointerLogError($num_pointer, $msg) {
		
		$arr_log = [];

		$arr_cur = $this->getSourcePointerLog($num_pointer);
		
		if ($arr_cur['row_results']) {
			$arr_log = json_decode($arr_cur['row_results'], true);
		}
		
		$arr_log['error'] = $msg;
		$str_log = value2JSON($arr_log);
		
		$this->stmt_log_error->bindParameters(['num' => (int)$num_pointer, 'results' => $str_log]);
		$this->stmt_log_error->execute();
	}
	
	public function hasSourcePointerLog() {
		
		$res = DB::query("SELECT TRUE
				FROM ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')."
			WHERE template_id = ".(int)$this->template_id."
			LIMIT 1
		");
		
		$has_log = ($res->getRowCount() ? true : false);
				
		return $has_log;
	}
	
	public function getSourcePointerLog($num_pointer) {

		$res = DB::query("SELECT *
				FROM ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')."
			WHERE template_id = ".(int)$this->template_id."
				AND row_identifier = ".(int)$num_pointer."
		");
		
		$arr_row = $res->fetchAssoc();
		
		return $arr_row;
	}
	
	public function resetLog() {
		
		$res = DB::query("DELETE FROM ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')."
			WHERE template_id = ".(int)$this->template_id."
		");
		
		StoreIngestFile::setTemplateLastRun($this->template_id);
	}
		
	public static function getValueIndexOptions() {
	
		return [
			['id' => 'multiple', 'name' => 'multiple'],
			['id' => '1', 'name' => '1'],
			['id' => '2', 'name' => '2'],
			['id' => '3', 'name' => '3'],
			['id' => '4', 'name' => '4'],
			['id' => '5', 'name' => '5']
		];
	}

	public static function matchFilterSource($arr_filter, $arr_result) {
		
		$in_filter = false;
		
		foreach ($arr_filter as $identifier => $arr_values) { // Every filter has to match its result
			
			$str_result = $arr_result[$identifier];
			$str_result = (is_array($str_result) ? implode(' ', $str_result) : $str_result);
			
			$in_filter = false;
			
			foreach ($arr_values as $str_value) {
				
				if (stripos($str_result, $str_value) !== false) {
					$in_filter = true;
					break;
				}
			}
			
			if (!$in_filter) {
				break;
			}
		}
		
		return $in_filter;
	}
}
