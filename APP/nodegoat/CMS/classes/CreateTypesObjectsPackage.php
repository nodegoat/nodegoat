<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CreateTypesObjectsPackage {
	
	const MODE_DEFAULT = 0;
	const MODE_RAW = 1;

	protected $arr_type_sets = [];
	protected $mode = self::MODE_DEFAULT;
	
	public function __construct($arr_type_sets) {
		
		$this->arr_type_sets = $arr_type_sets;
	}
	
	public function setMode($mode = self::MODE_DEFAULT) {
		
		$this->mode = (int)$mode;
	}
    
	public function init($type_id, $arr_objects) { // Straightforward Object parsing
		
		if (Response::getFormat() & Response::RENDER_LINKED_DATA) {
			return $this->parseObjectsLD($type_id, $arr_objects);
		} else {
			return $this->parseObjects($type_id, $arr_objects);
		}
	}
	
	protected function parseObjects($type_id, $arr_objects) {
		
		$arr_type_set = $this->arr_type_sets[$type_id];
		
		foreach ($arr_objects as $object_id => &$arr_object) {
			
			$arr_object['object'] = ['nodegoat_id' => GenerateTypeObjects::encodeTypeObjectID($type_id, $object_id), 'type_id' => $type_id] + $arr_object['object'];
			
			unset($arr_object['object']['object_locked']);
				
			foreach ($arr_object['object_definitions'] as $object_description_id => &$arr_object_definition) {
									
				if ((($arr_object_definition['object_definition_value'] === null || $arr_object_definition['object_definition_value'] === '' || $arr_object_definition['object_definition_value'] === []) && !$arr_object_definition['object_definition_ref_object_id']) || $arr_object_definition['object_definition_style'] == 'hide') {
					
					unset($arr_object['object_definitions'][$object_description_id]);
					continue;
				}
				
				$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
				
				$arr_object_definition['object_definition_value'] = $this->formatToDataType($arr_object_description['object_description_value_type'], $arr_object_definition['object_definition_value'], $arr_object_description['object_description_value_type_settings']);
			}
			
			foreach ($arr_object['object_subs'] as $object_sub_id => &$arr_object_sub) {
				
				$s_arr_self = &$arr_object_sub['object_sub'];
				
				$arr_object_sub_details = $arr_type_set['object_sub_details'][$s_arr_self['object_sub_details_id']];
				
				if ($s_arr_self['object_sub_date_chronology']) {
					
					$s_arr_self['object_sub_date_chronology'] = $this->formatToDataType('chronology', $s_arr_self['object_sub_date_chronology']);
					$s_arr_self['object_sub_date_start'] = $this->formatToDataType('date', $s_arr_self['object_sub_date_start']);
					$s_arr_self['object_sub_date_end'] = $this->formatToDataType('date', $s_arr_self['object_sub_date_end']);
				}

				unset($s_arr_self['object_sub_date_all']);
				
				foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => &$arr_object_sub_definition) {
					
					if ((($arr_object_sub_definition['object_sub_definition_value'] === null || $arr_object_sub_definition['object_sub_definition_value'] === '') && !$arr_object_sub_definition['object_sub_definition_ref_object_id']) || $arr_object_sub_definition['object_sub_definition_style'] == 'hide') {
						
						unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
						continue;
					}
					
					$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
					
					$arr_object_sub_definition['object_sub_definition_value'] = $this->formatToDataType($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition['object_sub_definition_value'], $arr_object_sub_description['object_sub_description_value_type_settings']);
				}
			}
		}
		
		return $arr_objects;
	}
	
	protected function parseObjectsLD($type_id, $arr_objects) {

		$arr_objects_ld = [];
		
		$arr_type_set = $this->arr_type_sets[$type_id];
		$arr_types_schema = (Settings::get('nodegoat_api', 'schema') ?: []);
		
		$arr_type_schema = $arr_types_schema[$type_id];
		
		$str_base_path = SiteStartEnvironment::getBasePath(0, false);
		$str_context = 'nodegoat:';

		$type_name_ld = ($arr_type_schema['type']['name'] ?: $str_context.preg_replace('/[^\p{L}\p{N}]+/', '_', strtolower($arr_type_set['type']['name'])));
		
		$func_add_description_reference = function(&$arr_object_ld, $str_object_description_name_ld, $ref_object_id, $ref_type_id, $arr_schema_data_type) use ($str_base_path, $str_context, $arr_types_schema) {
			
			if (!isset($this->arr_type_sets[$ref_type_id])) {
				$this->arr_type_sets[$ref_type_id] = StoreType::getTypeSet($ref_type_id);
			}
			
			$arr_ref_type_set = $this->arr_type_sets[$ref_type_id];
			
			$str_ref_name_ld = ($arr_types_schema[$ref_type_id]['type']['name'] ?: $str_context.preg_replace('/[^\p{L}\p{N}]+/', '_', strtolower($arr_ref_type_set['type']['name'])));
				
			$arr_object_ld[$str_object_description_name_ld][] = [
				'@type' => $str_ref_name_ld,
				'@id' => $str_base_path.GenerateTypeObjects::encodeTypeObjectID($ref_type_id, $ref_object_id)
			];
		};
		$func_add_description_value = function(&$arr_object_ld, $str_object_description_name_ld, $str_value, $arr_object_description, $arr_schema_data_type) {
			
			if ($arr_schema_data_type['type']) {
				$arr_object_ld[$str_object_description_name_ld][] = $this->applySchemaDataType($arr_schema_data_type, $str_value, $arr_object_description['object_description_value_type_settings']);
			} else {
				$arr_object_ld[$str_object_description_name_ld][] = $this->formatToSchemaDataType($arr_object_description['object_description_value_type'], $str_value, $arr_object_description['object_description_value_type_settings']);
			}
		};
		$func_add_description_sub_reference = function(&$arr_object_sub_ld, $str_object_sub_description_name_ld, $ref_object_id, $ref_type_id, $arr_schema_data_type) use ($str_base_path, $str_context, $arr_types_schema) {
			
			if (!isset($this->arr_type_sets[$ref_type_id])) {
				$this->arr_type_sets[$ref_type_id] = StoreType::getTypeSet($ref_type_id);
			}
			
			$arr_ref_type_set = $this->arr_type_sets[$ref_type_id];
			
			$str_ref_name_ld = ($arr_types_schema[$ref_type_id]['type']['name'] ?: $str_context.preg_replace('/[^\p{L}\p{N}]+/', '_', strtolower($arr_ref_type_set['type']['name'])));
			
			$s_arr =& $arr_object_sub_ld[$str_object_sub_description_name_ld];
			
			if (isset($s_arr)) {
				if (!isset($s_arr[0])) {
					$s_arr = [$s_arr];
				}
				$s_arr =& $s_arr[];
			}
			
			$s_arr = [
				'@type' => $str_ref_name_ld,
				'@id' => $str_base_path.GenerateTypeObjects::encodeTypeObjectID($ref_type_id, $ref_object_id)
			];
		};
		$func_add_description_sub_value = function(&$arr_object_sub_ld, $str_object_sub_description_name_ld, $str_value, $arr_object_sub_description, $arr_schema_data_type) {
			
			$s_arr =& $arr_object_sub_ld[$str_object_sub_description_name_ld];
			
			if (isset($s_arr)) {
				if (!isset($s_arr[0])) {
					$s_arr = [$s_arr];
				}
				$s_arr =& $s_arr[];
			}
			
			if ($arr_schema_data_type['type']) {
				$s_arr = $this->applySchemaDataType($arr_schema_data_type, $str_value, $arr_object_sub_description['object_sub_description_value_type_settings']);
			} else {
				$s_arr = $this->formatToSchemaDataType($arr_object_sub_description['object_sub_description_value_type'], $str_value, $arr_object_sub_description['object_sub_description_value_type_settings']);
			}
		};

		foreach ($arr_objects as $object_id => $arr_object) {
			
			$arr_object_ld = [
				'@id' => $str_base_path.GenerateTypeObjects::encodeTypeObjectID($type_id, $object_id), 
				'@type' => ($arr_type_schema['type']['name'] ?: $type_name_ld), 
				'modified' => $arr_object['object']['object_dating'],
				'schema:name' => $arr_object['object']['object_name']
			];
			
			foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
									
				if ((($arr_object_definition['object_definition_value'] === null || $arr_object_definition['object_definition_value'] === '' || $arr_object_definition['object_definition_value'] === []) && !$arr_object_definition['object_definition_ref_object_id']) || $arr_object_definition['object_definition_style'] == 'hide') {
					continue;
				}
					
				$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
				$arr_schema_data_type = $arr_type_schema['object_descriptions'][$object_description_id];
				
				$object_description_name_ld = ($arr_schema_data_type['object_description_name'] ?: $type_name_ld.'/'.preg_replace('/[^\p{L}\p{N}]+/', '_', strtolower($arr_object_description['object_description_name'])));
				
				if (!isset($arr_object_ld[$object_description_name_ld])) {
					$arr_object_ld[$object_description_name_ld] = [];
				}

				if ($arr_object_description['object_description_is_dynamic']) {
					
					if ($arr_object_definition['object_definition_ref_object_id']) {
						
						$arr_references = $arr_object_definition['object_definition_ref_object_id'];
						if (!$arr_object_description['object_description_has_multi']) {
							$arr_references = [$arr_references];
						}
						
						foreach ($arr_references as $arr_ref_type_objects) {
							foreach ($arr_ref_type_objects as $ref_type_id => $arr_ref_objects) {
								foreach ($arr_ref_objects as $arr_ref_object) {
									$func_add_description_reference($arr_object_ld, $object_description_name_ld, $arr_ref_object['object_definition_ref_object_id'], $ref_type_id, $arr_schema_data_type);
								}
							}
						}
					} else {
						
						$arr_values = $arr_object_definition['object_definition_value'];
						if (!$arr_object_description['object_description_has_multi']) {
							$arr_values = [$arr_values];
						}
						
						foreach ($arr_values as $str_value) {
							$func_add_description_value($arr_object_ld, $object_description_name_ld, $str_value, $arr_object_description, $arr_schema_data_type);
						}
					}
				} else if (is_array($arr_object_description['object_description_ref_type_id'])) {

					$arr_references = (array)$arr_object_definition['object_definition_ref_object_id'];
					
					foreach ($arr_references as $key => $str_identifier) {
						
						list($ref_type_id, $ref_object_id) = explode('_', $str_identifier);
						
						$func_add_description_reference($arr_object_ld, $object_description_name_ld, $ref_object_id, $ref_type_id, $arr_schema_data_type);
					}
				} else {
					
					if ($arr_object_definition['object_definition_ref_object_id']) {
												
						foreach ((array)$arr_object_definition['object_definition_ref_object_id'] as $key => $object_definition_ref_object_id) {
							$func_add_description_reference($arr_object_ld, $object_description_name_ld, $object_definition_ref_object_id, $arr_object_description['object_description_ref_type_id'], $arr_schema_data_type);
						}
					} else {
						
						foreach ((array)$arr_object_definition['object_definition_value'] as $key => $object_definition_value) {
							$func_add_description_value($arr_object_ld, $object_description_name_ld, $object_definition_value, $arr_object_description, $arr_schema_data_type);
						}
					}
				}
			}
			
			foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
				
				$arr_object_sub_self = $arr_object_sub['object_sub'];
				
				$arr_object_sub_details = $arr_type_set['object_sub_details'][$arr_object_sub_self['object_sub_details_id']];
				$arr_object_sub_details_schema_data_type = $arr_type_schema['object_sub_details'][$arr_object_sub_self['object_sub_details_id']];
				
				$object_sub_name_ld = ($arr_object_sub_details_schema_data_type['object_sub_details']['object_sub_details_name'] ?: $type_name_ld.'/'.preg_replace('/[^\p{L}\p{N}]+/', '_', strtolower($arr_object_sub_details['object_sub_details']['object_sub_details_name'])));
				
				$arr_object_sub_ld = [];
					
				$arr_object_sub_ld['@type'] = $object_sub_name_ld;
									
				if ($arr_object_sub_self['object_sub_date_start']) {
					
					$str_name_ld = ($arr_object_sub_details_schema_data_type['object_sub_details']['object_sub_details_date_start'] ?: $object_sub_name_ld.'/date_start');
					$arr_object_sub_ld[$str_name_ld] = $this->formatToSchemaDataType('date', $arr_object_sub_self['object_sub_date_start']);
				}
				
				if ($arr_object_sub_self['object_sub_date_end']) {
					
					$str_name_ld = ($arr_object_sub_details_schema_data_type['object_sub_details']['object_sub_details_date_end'] ?: $object_sub_name_ld.'/date_end');
					$arr_object_sub_ld[$str_name_ld] = $this->formatToSchemaDataType('date', $arr_object_sub_self['object_sub_date_end']);
				}
				
				if ($arr_object_sub_self['object_sub_date_chronology']) {
					
					$str_name_ld = ($arr_object_sub_details_schema_data_type['object_sub_details']['object_sub_details_date_chronology'] ?: $object_sub_name_ld.'/chronology');
					$arr_object_sub_ld[$str_name_ld] = $this->formatToSchemaDataType('chronology', $arr_object_sub_self['object_sub_date_chronology']);
				}
				
				if ($arr_object_sub_self['object_sub_location_geometry']) {
					
					$str_name_ld = ($arr_object_sub_details_schema_data_type['object_sub_details']['object_sub_details_location_geometry'] ?: $object_sub_name_ld.'/geometry');
					$arr_object_sub_ld[$str_name_ld] = $this->formatToSchemaDataType('geometry', $arr_object_sub_self['object_sub_location_geometry']);
				}

				if ($arr_object_sub_self['object_sub_location_ref_object_id']) {
					
					$ref_type_id = $arr_object_sub_self['object_sub_location_ref_type_id'];
					
					if (!isset($this->arr_type_sets[$ref_type_id])) {
						$this->arr_type_sets[$ref_type_id] = StoreType::getTypeSet($ref_type_id);
					}
					
					$arr_location_ref_type_set = $this->arr_type_sets[$ref_type_id];
					
					$str_name_ld = ($arr_object_sub_details_schema_data_type['object_sub_details']['object_sub_details_location_reference'] ?: $object_sub_name_ld.'/location_reference');
					$str_ref_name_ld = ($arr_types_schema[$ref_type_id]['type']['name'] ?: $str_context.preg_replace('/[^\p{L}\p{N}]+/', '_', strtolower($arr_location_ref_type_set['type']['name'])));
					
					$arr_object_sub_ld[$str_name_ld] = [
						'@type' => $str_ref_name_ld, 
						'@id' => $str_base_path.GenerateTypeObjects::encodeTypeObjectID($ref_type_id, $arr_object_sub_self['object_sub_location_ref_object_id'])
					];
					
					$str_name_ld = ($arr_object_sub_details_schema_data_type['object_sub_details']['object_sub_details_location_reference_name'] ?: $object_sub_name_ld.'/location_reference_name');
					$arr_object_sub_ld[$str_name_ld] = $this->formatToSchemaDataType('', $arr_object_sub_self['object_sub_location_ref_object_name']);
				}
				
				foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
					
					if ((($arr_object_sub_definition['object_sub_definition_value'] === null || $arr_object_sub_definition['object_sub_definition_value'] === '') && !$arr_object_sub_definition['object_sub_definition_ref_object_id']) || $arr_object_sub_definition['object_sub_definition_style'] == 'hide') {
						continue;
					}
					
					$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
					$arr_schema_data_type = $arr_object_sub_details_schema_data_type['object_sub_descriptions'][$object_sub_description_id];
					
					$object_sub_description_name_ld = ($arr_schema_data_type['object_sub_description_name'] ?: $object_sub_name_ld.'/'.preg_replace('/[^\p{L}\p{N}]+/', '_', strtolower($arr_object_sub_description['object_sub_description_name'])));
					
					if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
						
						if ($arr_object_sub_definition['object_sub_definition_ref_object_id']) {
														
							foreach ($arr_object_sub_definition['object_sub_definition_ref_object_id'] as $ref_type_id => $arr_ref_objects) {
								foreach ($arr_ref_objects as $arr_ref_object) {
									$func_add_description_sub_reference($arr_object_sub_ld, $object_sub_description_name_ld, $arr_ref_object['object_sub_definition_ref_object_id'], $ref_type_id, $arr_schema_data_type);
								}
							}
						} else {
														
							foreach ($arr_object_sub_definition['object_sub_definition_value'] as $str_value) {
								$func_add_description_sub_value($arr_object_sub_ld, $object_sub_description_name_ld, $str_value, $arr_object_sub_description, $arr_schema_data_type);
							}
						}
					} else if (is_array($arr_object_sub_description['object_sub_description_ref_type_id'])) {
						
						$str_identifier = $arr_object_sub_definition['object_sub_definition_ref_object_id'];										
						list($ref_type_id, $ref_object_id) = explode('_', $str_identifier);
						
						$func_add_description_sub_reference($arr_object_sub_ld, $object_sub_description_name_ld, $ref_object_id, $ref_type_id, $arr_schema_data_type);
					} else {
						
						if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
							$func_add_description_sub_reference($arr_object_sub_ld, $object_sub_description_name_ld, $arr_object_sub_definition['object_sub_definition_ref_object_id'], $arr_object_sub_description['object_sub_description_ref_type_id'], $arr_schema_data_type);
						} else {
							$func_add_description_sub_value($arr_object_sub_ld, $object_sub_description_name_ld, $arr_object_sub_definition['object_sub_definition_value'], $arr_object_sub_description, $arr_schema_data_type);
						}
					}
				}
				
				$arr_object_ld[$object_sub_name_ld][] = $arr_object_sub_ld;
			}
			
			$arr_objects_ld[] = $arr_object_ld;
		}
		
		return $arr_objects_ld;
	}
	
	public function initPath($collect, $arr_objects) { // Path-based object parsing
			
		$arr_objects_walked = [];
		
		foreach ($arr_objects as $object_id => $arr_object) {
							
			$arr_objects_walked[$object_id] = $collect->getWalkedObject($object_id, [], function &($cur_target_object_id, &$cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) {
				
				$arr_object_descriptions = $this->arr_type_sets[$cur_target_type_id]['object_descriptions'];
				$arr_object_subs_details = $this->arr_type_sets[$cur_target_type_id]['object_sub_details'];
				
				$arr_object = $collect->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id']);
				
				$collapse = ($arr_info['arr_collapse_source'] ? true : false);
				$collapsed = ($arr_info['arr_collapsed_source'] ? true : false);
				$arr_collapsing_source = ($collapsed ? $arr_info['arr_collapsed_source'] : $arr_info['arr_collapse_source']);
				
				$arr_object['object'] = ['nodegoat_id' => GenerateTypeObjects::encodeTypeObjectID($cur_target_type_id, $cur_target_object_id), 'type_id' => $cur_target_type_id] + $arr_object['object'];
				
				foreach ($arr_object['object_definitions'] as $object_description_id => &$arr_object_definition) {
					
					if ((($arr_object_definition['object_definition_value'] === null || $arr_object_definition['object_definition_value'] === '' || $arr_object_definition['object_definition_value'] === []) && !$arr_object_definition['object_definition_ref_object_id']) || $arr_object_definition['object_definition_style'] == 'hide') {
						
						unset($arr_object['object_definitions'][$object_description_id]);
						continue;
					}
					
					$arr_object_description = $arr_object_descriptions[$object_description_id];
					
					$arr_object_definition['object_definition_value'] = $this->formatToDataType($arr_object_description['object_description_value_type'], $arr_object_definition['object_definition_value'], $arr_object_description['object_description_value_type_settings']);
				}
				
				foreach ($arr_object['object_subs'] as $object_sub_id => &$arr_object_sub) {
					
					$s_arr_object_sub_self = &$arr_object_sub['object_sub'];
					
					$arr_object_sub_details = $arr_object_subs_details[$s_arr_object_sub_self['object_sub_details_id']];
					
					if ($s_arr_object_sub_self['object_sub_date_chronology']) {
						
						$s_arr_object_sub_self['object_sub_date_chronology'] = $this->formatToDataType('chronology', $s_arr_object_sub_self['object_sub_date_chronology']);
						$s_arr_object_sub_self['object_sub_date_start'] = $this->formatToDataType('date', $s_arr_object_sub_self['object_sub_date_start']);
						$s_arr_object_sub_self['object_sub_date_end'] = $this->formatToDataType('date', $s_arr_object_sub_self['object_sub_date_end']);
					}
					
					unset($s_arr_object_sub_self['object_sub_date_all']);

					foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => &$arr_object_sub_definition) {

						if ((($arr_object_sub_definition['object_sub_definition_value'] === null || $arr_object_sub_definition['object_sub_definition_value'] === '') && !$arr_object_sub_definition['object_sub_definition_ref_object_id']) || $arr_object_sub_definition['object_sub_definition_style'] == 'hide') {
							
							unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
							continue;
						}
						
						$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
						
						$arr_object_sub_definition['object_sub_definition_value'] = $this->formatToDataType($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition['object_sub_definition_value'], $arr_object_sub_description['object_sub_description_value_type_settings']);
					}
				}
				unset($s_arr_object_sub_self);
			
				if ($arr_info['in_out'] == 'in' || $arr_info['in_out'] == 'out') {
					
					if ($arr_info['in_out'] == 'in') {
						$cur_arr['cross_referenced'][$cur_target_object_id] =& $arr_object;
					} else {
						$cur_arr['cross_referencing'][$cur_target_object_id] =& $arr_object;
					}
					
					return $arr_object;
				} else {
					
					$cur_arr['object'] =& $arr_object['object'];
					$cur_arr['object_definitions'] =& $arr_object['object_definitions'];
					$cur_arr['object_subs'] =& $arr_object['object_subs'];
					
					return $cur_arr;
				}
			});
		}
		
		return $arr_objects_walked;	
	}
	
	protected function formatToDataType($value_type, $value, $arr_type_settings = []) {
		
		if ($this->mode == static::MODE_RAW) {
			
			switch ($value_type) {
				case 'chronology':
					$value = value2JSON(FormatTypeObjects::formatToChronology($value));
					break;
			}
			
			return $value;
		}
		
		switch ($value_type) {
			case 'chronology':
				$value = value2JSON(FormatTypeObjects::formatToChronology($value));
				break;
			case 'geometry':
				$value = $value;
				break;
			default:
				$value = FormatTypeObjects::formatToValue($value_type, $value, $arr_type_settings);
		}
		
		return $value;
	}
	
	protected function formatToSchemaDataType($value_type, $value, $arr_type_settings = []) {
		
		$str_schema = 'schema';
		$cast_value = null;
		
		switch ($value_type) {
			case 'boolean':
				$str_cast_type = 'Boolean';
				break;
			case 'date':
				$str_cast_type = 'Date';
				$cast_value = FormatTypeObjects::dateInt2DateStandard($value);
				break;
			case 'chronology':
				$str_schema = 'dc';
				$str_cast_type = 'temporal';
				$cast_value = value2JSON(FormatTypeObjects::formatToChronology($value));
				break;
			case 'geometry':
				$str_cast_type = 'GeoCoordinates';
				$cast_value = $value;
				break;
			case 'media':
				$str_cast_type = 'MediaObject';
				break;
			case 'media_external':
				$str_cast_type = 'URL';
				break;
			case 'int':
				$str_cast_type = 'Integer';
				break;
			case 'text':
			case 'text_layout':
			case 'text_tags':
			case '':
				$str_cast_type = 'Text';
				break;
			case 'external':
				return ['@id' => $value];
			default:
				$str_cast_type = 'Text';
		}
		
		if ($cast_value === null) {
			$value = FormatTypeObjects::formatToValue($value_type, $value, $arr_type_settings);
		} else {
			$value = $cast_value;
		}
		
		return ['@type' => $str_schema.':'.$str_cast_type, '@value' => $value];
	}
	
	protected function applySchemaDataType($arr_schema_data_type, $value, $arr_type_settings = []) {
				
		$type = $arr_schema_data_type['type'];
		
		$func_cast_value = ($arr_schema_data_type['cast_value'] ?? null);
		
		if ($func_cast_value) {
			$value = $func_cast_value($value, $arr_type_settings);
		}
		
		return ['@type' => $type, '@value' => $value];
	}
}
