<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ExportTypesObjectsNetworkCSV extends ExportTypesObjectsNetwork {
	
	protected $package = false;
	protected $str_escape = false;
		
	protected $arr_column_names = [];
	protected $arr_column_identifiers = [];
	protected $arr_column_identifier_keys = [];
	protected $arr_column_connection_identifiers = [];
	
	protected $arr_collector = [];
	protected $arr_collector_connections = [];
    
    protected $do_collect_columns = false;
    	
	public function &collect($cur_target_object_id, $cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) {
		
		$str_identifier_column_base = $arr_info['identifier'];
		$str_identifier_column_base_source = ($arr_info['identifier_source'] ?: $arr_info['identifier']);
		
		$arr_column_base = [$str_identifier_column_base, $str_identifier_column_base_source];
		
		$str_identifier_column_base_source_lineage = $str_identifier_column_base_source;
		
		$arr_selection = $this->arr_type_network_types[$source_path][$cur_target_type_id];
		
		if ($arr_info['in_out'] == 'in') {

			$this->arr_collector_connections[$str_identifier_column_base][$arr_info['object_id']][$cur_target_object_id] = $cur_target_object_id;
			
			if (!$arr_selection) {
				
				$str_identifier_connection_column = $this->addColumnConnection('object', $arr_column_base, $arr_info);

				$this->arr_collector[$str_identifier_connection_column][$cur_target_object_id] = $cur_target_object_id;
			}
		} else if ($arr_info['in_out'] == 'out') {
			
			if ($arr_info['object_description_id']) {
				
				$str_identifier_column_base_source_lineage .= '_od_'.$arr_info['object_description_id'];
				
				$str_identifier_connection_column = $this->addColumnConnection('object_description', $arr_column_base, $arr_info);

				if ($str_identifier_connection_column) {
					
					$this->arr_collector[$str_identifier_connection_column][$arr_info['object_id']][$cur_target_object_id] = $cur_target_object_id;
				}
			} else if ($arr_info['object_sub_details_id']) {
				
				$this->arr_collector_connections[$str_identifier_column_base][$arr_info['object_sub_id']] = $cur_target_object_id;
				
				$str_identifier_column_base_source_lineage .= '_os_'.$arr_info['object_sub_details_id'];
				
				$str_identifier_connection_column = $this->addColumnConnection('object_sub_details', $arr_column_base, $arr_info);

				if ($str_identifier_connection_column) {
					
					$this->arr_collector[$str_identifier_connection_column][$arr_info['object_id']][$arr_info['object_sub_id']][$cur_target_object_id] = $cur_target_object_id;
				}
			}
		} else {
			
			$this->arr_collector_connections[$str_identifier_column_base][null] = $cur_target_object_id; // The source/starting object
		}
		
		$arr_column_base[2] = $str_identifier_column_base_source_lineage;
	
		$arr_object = $collect->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id']);

		if ($arr_selection) {
						
			if ($arr_selection['nodegoat_id']) {
				
				$str_identifier_column = $str_identifier_column_base.'_nodegoat_id';
				$this->arr_collector[$str_identifier_column][$cur_target_object_id] = ($cur_target_object_id ? GenerateTypeObjects::encodeTypeObjectID($cur_target_type_id, $cur_target_object_id) : '');
				$this->addColumn('nodegoat_id', $cur_target_type_id, $str_identifier_column, $arr_column_base);
			}
			if ($arr_selection['id']) {
				
				$str_identifier_column = $str_identifier_column_base.'_id';
				$this->arr_collector[$str_identifier_column][$cur_target_object_id] = ($cur_target_object_id ?: '');
				$this->addColumn('id', $cur_target_type_id, $str_identifier_column, $arr_column_base);
			}
			if ($arr_selection['name']) {
				
				$str_identifier_column = $str_identifier_column_base.'_name';
				$this->arr_collector[$str_identifier_column][$cur_target_object_id] = $arr_object['object']['object_name'];
				$this->addColumn('name', $cur_target_type_id, $str_identifier_column, $arr_column_base);
			}
			if ($arr_selection['sources']) {
				
				$str_identifier_column = $str_identifier_column_base.'_sources_1';
				$this->arr_collector[$str_identifier_column][$cur_target_object_id] = [];
				$str_identifier_column = $str_identifier_column_base.'_sources_2';
				$this->arr_collector[$str_identifier_column][$cur_target_object_id] = [];
								
				foreach (($arr_object['object']['object_sources'] ?: [[]]) as $type_id => $arr_sources) {
					
					foreach (($arr_sources ?: [[]]) as $arr_source) {
						
						$this->arr_collector[$str_identifier_column_base.'_sources_1'][$cur_target_object_id][] = $arr_source['object_source_ref_object_id'];
						$this->arr_collector[$str_identifier_column_base.'_sources_2'][$cur_target_object_id][] = $arr_source['object_source_link'];
					}
				}
				
				$this->addColumn('sources', $cur_target_type_id, $str_identifier_column_base.'_sources', $arr_column_base);
			}
			if ($arr_selection['analysis']) {
				
				$str_identifier_column = $str_identifier_column_base.'_analysis';
				$this->arr_collector[$str_identifier_column][$cur_target_object_id] = $arr_object['object']['object_analysis'];
				$this->addColumn('analysis', $cur_target_type_id, $str_identifier_column, $arr_column_base);
			}
			
			foreach ($arr_selection['selection'] as $id => $arr_selected) {

				$arr_options = $arr_selected;
				
				if ($id == 'name') {
					
					$str_identifier_column = $str_identifier_column_base.'_name_plain';
					$this->arr_collector[$str_identifier_column][$cur_target_object_id] = $arr_object['object']['object_name_plain'];
					$this->addColumn('name', $cur_target_type_id, $str_identifier_column, $arr_column_base);
					
					continue;
				}
				
				if ($arr_selected['object_description_id']) {
					
					$arr_object_definition = $arr_object['object_definitions'][$arr_selected['object_description_id']];
				
					$value_object_definition = $this->formatColumnValue('object_description', $cur_target_type_id, $arr_object_definition, $arr_options);
					
					$str_identifier_column = $str_identifier_column_base.'_'.$id;
					$this->arr_collector[$str_identifier_column][$cur_target_object_id] = $value_object_definition;

					$this->addColumn('object_description', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
					
					continue;
				}
				
				if ($arr_selected['object_sub_details_id']) {
					
					$num_length_find = ($cur_target_object_id && $arr_object['object_subs'] ? count($arr_object['object_subs']) : 0);
					$num_count_find = 0;
					$is_found = false;

					foreach (($arr_object['object_subs'] ?: [[]]) as $object_sub_id => $arr_object_sub) {
						
						$num_count_find++;
						
						if ($arr_object_sub['object_sub']['object_sub_details_id'] != $arr_selected['object_sub_details_id']) {
							
							if (!$num_length_find || ($num_count_find == $num_length_find && !$is_found)) { // Force a run over each selected sub-object at least once
								$object_sub_id = '';
								$arr_object_sub = [];
							} else {
								continue;
							}
						}
						
						$is_found = true;
						
						$arr_options['object_sub_id'] = $object_sub_id; // Use for column identification
						
						$str_identifier_column = $str_identifier_column_base.'_'.$id;
						$s_cur_arr =& $this->arr_collector[$str_identifier_column][$cur_target_object_id];
												
						if ($arr_selected['object_sub_description_id']) {
							
							$arr_object_sub_definition = $arr_object_sub['object_sub_definitions'][$arr_selected['object_sub_description_id']];
						
							$value_object_sub_definition = $this->formatColumnValue('object_sub_description', $cur_target_type_id, $arr_object_sub_definition, $arr_options);
														
							if ($s_cur_arr) {
								$s_cur_arr += $value_object_sub_definition;
							} else {
								$s_cur_arr = $value_object_sub_definition;
							}
							
							$this->addColumn('object_sub_description', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
						} else {
							
							$str_attribute = $arr_selected['attribute'];
							$arr_object_sub_value = $arr_object_sub['object_sub'];
							
							if ($str_attribute == 'id') {
								$s_cur_arr[$object_sub_id] = $object_sub_id;
								$this->addColumn('object_sub_id', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
							}
							if ($str_attribute == 'date_start') {
								$s_cur_arr[$object_sub_id] = FormatTypeObjects::formatToValue('date', $arr_object_sub_value['object_sub_date_start']);
								$this->addColumn('object_sub_details_date_start', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
							}
							if ($str_attribute == 'date_end') {
								$s_cur_arr[$object_sub_id] = ($arr_object_sub_value['object_sub_date_start'] != $arr_object_sub_value['object_sub_date_end'] ? FormatTypeObjects::formatToValue('date', $arr_object_sub_value['object_sub_date_end']) : '');
								$this->addColumn('object_sub_details_date_end', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
							}
							if ($str_attribute == 'date_chronology') {
								if ($arr_object_sub_value['object_sub_date_chronology']) {
									$arr_object_sub_value['object_sub_date_chronology'] = FormatTypeObjects::formatToChronology($arr_object_sub_value['object_sub_date_chronology']);
									$arr_object_sub_value['object_sub_date_chronology'] = value2JSON($arr_object_sub_value['object_sub_date_chronology'], JSON_PRETTY_PRINT);
								}
								$s_cur_arr[$object_sub_id] = $arr_object_sub_value['object_sub_date_chronology'];
								$this->addColumn('object_sub_details_date_chronology', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
							}
							if ($str_attribute == 'location_ref_type_id') {
								if ($arr_options['use_id']) {
									$s_cur_arr[$object_sub_id] = $arr_object_sub_value['object_sub_location_ref_object_id'];
									$this->addColumn('object_sub_details_location_ref', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
								} else {
									$s_cur_arr[$object_sub_id] = $arr_object_sub_value['object_sub_location_ref_object_name'];
									$this->addColumn('object_sub_details_location_ref', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
								}
							}
							if ($str_attribute == 'location_geometry') {
								$s_cur_arr[$object_sub_id] = $arr_object_sub_value['object_sub_location_geometry'];
								$this->addColumn('object_sub_details_location_geometry', $cur_target_type_id, $str_identifier_column, $arr_column_base, $arr_options);
							}
						}
					}
					
					continue;
				}
			}
		}
		
		return $cur_arr;
	}
	
	protected function formatColumnValue($type, $type_id, $arr_definition, $arr_options) {
		
		if (!$arr_definition) {
			
			if ($type == 'object_sub_description') {
				return [$arr_options['object_sub_id'] => null];
			} else {
				return null;
			}
		}
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		if ($type == 'object_description') {
			
			$key_ref_object_id = 'object_definition_ref_object_id';
			$key_value = 'object_definition_value';
			
			$arr_object_description = $arr_type_set['object_descriptions'][$arr_options['object_description_id']];
			$has_multi = $arr_object_description['object_description_has_multi'];
			
			if ($arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic']) {
				$value_type = 'ref_object_id';
			} else {
				$value_type = $arr_object_description['object_description_value_type'];
			}
		} else {
			
			$key_ref_object_id = 'object_sub_definition_ref_object_id';
			$key_value = 'object_sub_definition_value';
			
			$arr_object_sub_description = $arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']];
			$has_multi = false;
			
			if ($arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic']) {
				$value_type = 'ref_object_id';
			} else {
				$value_type = $arr_object_sub_description['object_sub_description_value_type'];
			}
		}
	
		$arr_definition[$key_ref_object_id] = (array)$arr_definition[$key_ref_object_id];
		$arr_definition[$key_value] = (array)$arr_definition[$key_value];
		
		// References
		
		if ($value_type == 'ref_object_id') {
			
			$arr_value = [];

			foreach ($arr_definition[$key_ref_object_id] as $key => $ref_object_id) {
				
				$str_identifier_row = ($type == 'object_description' ? $ref_object_id : $arr_options['object_sub_id']);
				
				if ($arr_options['use_id']) {
					$arr_value[$str_identifier_row] = $ref_object_id;
				} else {
					$arr_value[$str_identifier_row] = $arr_definition[$key_value][$key];
				}
			}
			
			if (!$arr_value && $type == 'object_sub_description') {
				$arr_value[$arr_options['object_sub_id']] = null;
			}
			
			return $arr_value;
		}
		
		// Values
							
		if ($arr_options['use_id']) { // Dynamic values with IDs
			
			$arr_value = [];
			$arr_value_type_ref_object_ids = (!$has_multi ? [$arr_definition[$key_ref_object_id]] : $arr_definition[$key_ref_object_id]);
			
			foreach ($arr_value_type_ref_object_ids as $key => $arr_type_ref_object_ids) {
				
				if (!$arr_type_ref_object_ids) {
					continue;
				}
					
				foreach ($arr_type_ref_object_ids as $ref_type_id => $arr_ref_object_ids) {
					
					foreach ($arr_ref_object_ids as $arr_dynamic) {

						$ref_object_id = $arr_dynamic[$key_ref_object_id];
						
						$str_identifier_row = ($type == 'object_description' ? $ref_object_id : $arr_options['object_sub_id'].'_'.$ref_object_id);
						
						$arr_value[$str_identifier_row] = $ref_object_id;
					}
				}
			}
			
			if (!$arr_value && $type == 'object_sub_description') {
				$arr_value[$arr_options['object_sub_id'].'_0'] = null;
			}
			
			return $arr_value;
		}
		
		if ($arr_options['use_text']) { // Dynamic values with IDs
			
			$arr_value = [];
			
			foreach ($arr_definition[$key_value] as $key => $str_value) {
	
				$arr_value[$key] = FormatTypeObjects::clearObjectDefinitionText($str_value);
			}
			
			if ($type == 'object_sub_description') {
				return [$arr_value['object_sub_id'] => $arr_value[0]]; // Object definition value is casted to array earlier, select first element (0)
			}
			
			return $arr_value;
		}
		
		// Values plain

		if ($type == 'object_sub_description') {
			return [$arr_options['object_sub_id'] => $arr_definition[$key_value][0]]; // Object definition value is casted to array earlier, select first element (0)
		}
		
		return $arr_definition[$key_value];
	}
	
	protected function addColumn($type, $type_id, $str_identifier_column, $arr_column_base, $arr_options = []) {
		
		if (!$this->do_collect_columns) {
			return;
		}
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$str_identifier_column_base = $arr_column_base[0];
			
		switch ($type) {
			case 'nodegoat_id':
			
				$this->arr_column_names[] = 'nodegoat ID';
				$this->arr_column_identifiers[$str_identifier_column] = $arr_column_base;
				break;
			case 'id':
			
				$this->arr_column_names[] = 'Object ID';
				$this->arr_column_identifiers[$str_identifier_column] = $arr_column_base;
				break;
			case 'name':
			
				$this->arr_column_names[] = getLabel('lbl_name');
				$this->arr_column_identifiers[$str_identifier_column] = $arr_column_base;
				break;
			case 'sources':
			
				$this->arr_column_names[] = getLabel('lbl_source').' Object ID';
				$this->arr_column_names[] = getLabel('lbl_source').' '.getLabel('lbl_value');
				$this->arr_column_identifiers[$str_identifier_column.'_1'] = $arr_column_base;
				$this->arr_column_identifiers[$str_identifier_column.'_2'] = $arr_column_base;
				
				$str_identifier_column_base_lineage = $str_identifier_column_base.'_o_src';
				$this->arr_column_identifiers[$str_identifier_column.'_1'][3] = $str_identifier_column_base_lineage;
				$this->arr_column_identifiers[$str_identifier_column.'_2'][3] = $str_identifier_column_base_lineage;
				break;
			case 'analysis':
			
				$this->arr_column_names[] = getLabel('lbl_analysis');
				$this->arr_column_identifiers[$str_identifier_column] = $arr_column_base;
				break;
			case 'object_description':
				
				$arr_object_description = $arr_type_set['object_descriptions'][$arr_options['object_description_id']];
				
				$this->arr_column_names[] = Labels::parseTextVariables($arr_object_description['object_description_name']).($arr_options['use_text'] ? ' - Text' : ($arr_options['use_id'] ? ' - Object ID' : ''));
				$this->arr_column_identifiers[$str_identifier_column] = $arr_column_base;
				
				if ($arr_object_description['object_description_is_dynamic'] && $arr_object_description['object_description_ref_type_id'] && !$arr_options['use_id']) { // Not really connectable
					$str_identifier_column_base_lineage = $str_identifier_column_base.'_od_'.$arr_options['object_description_id'].'_value';
				} else {
					$str_identifier_column_base_lineage = $str_identifier_column_base.'_od_'.$arr_options['object_description_id'];
				}
				$this->arr_column_identifiers[$str_identifier_column][3] = $str_identifier_column_base_lineage;
				break;
			case 'object_sub_id':
			case 'object_sub_description':
			case 'object_sub_details_date_start':
			case 'object_sub_details_date_end':
			case 'object_sub_details_date_chronology':
			case 'object_sub_details_location_ref':
			case 'object_sub_details_location_geometry':
			
				switch ($type) {
					case 'object_sub_id':
						$name = 'Sub-Object ID';
						break;
					case 'object_sub_description':
						$name = Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']]['object_sub_description_name']).($arr_options['use_text'] ? ' - Text' : ($arr_options['use_id'] ? ' - Object ID' : ''));
						break;
					case 'object_sub_details_date_start':
						$name = getLabel('lbl_date_start');
						break;
					case 'object_sub_details_date_end':
						$name = getLabel('lbl_date_end');
						break;
					case 'object_sub_details_date_chronology':
						$name = getLabel('lbl_chronology');
						break;
					case 'object_sub_details_location_ref':
						$name = getLabel('lbl_location_reference').($arr_options['use_id'] ? ' - Object ID' : '');
						break;
					case 'object_sub_details_location_geometry':
						$name = getLabel('lbl_geometry');
						break;
				}
				
				$this->arr_column_names[] = '['.Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_details']['object_sub_details_name']).'] '.$name;
				$this->arr_column_identifiers[$str_identifier_column] = $arr_column_base;
				
				$str_identifier_column_base_lineage = $str_identifier_column_base.'_os_'.$arr_options['object_sub_details_id'];
				$this->arr_column_identifiers[$str_identifier_column][3] = $str_identifier_column_base_lineage;
				break;
		}
		
		$str_identifier_column_base_connection = ($str_identifier_column_base_lineage ?: $str_identifier_column_base);

		if (!$this->arr_column_connection_identifiers[$str_identifier_column_base_connection]) {
			$this->arr_column_connection_identifiers[$str_identifier_column_base_connection] = $str_identifier_column;
		}
	}
	
	protected function addColumnConnection($type, $arr_column_base, $arr_options = []) {

		switch ($type) {
			case 'object':
				$str_identifier_column_base = $arr_column_base[0];
				$str_identifier_column_base_source = $arr_column_base[1];
				$str_identifier_column_base_lineage = false;
				$str_identifier_column_base_connection = $str_identifier_column_base;
				break;
			case 'object_description':
				$str_identifier_column_base = $arr_column_base[1];
				$str_identifier_column_base_source = false;
				$str_identifier_column_base_lineage = $str_identifier_column_base.'_od_'.$arr_options['object_description_id'];
				$str_identifier_column_base_connection = $str_identifier_column_base_lineage;
				break;
			case 'object_sub_details':
				$str_identifier_column_base = $arr_column_base[1];
				$str_identifier_column_base_source = false;
				$str_identifier_column_base_lineage = $str_identifier_column_base.'_os_'.$arr_options['object_sub_details_id'];
				$str_identifier_column_base_connection = $str_identifier_column_base_lineage;
				break;
		}
		
		if (!$this->do_collect_columns) {
			
			if ($this->arr_column_connection_identifiers[$str_identifier_column_base_connection] == $str_identifier_column_base_connection) { // When a collection column equals to itself, it's a hidden connection column
				return $str_identifier_column_base_connection;
			}
			
			return false;
		}
		
		if ($this->arr_column_connection_identifiers[$str_identifier_column_base_connection]) {
			return false;
		}
		
		$this->arr_column_identifiers[$str_identifier_column_base_connection] = [$str_identifier_column_base, $str_identifier_column_base_source, $str_identifier_column_base_source, $str_identifier_column_base_lineage];
		$this->arr_column_identifiers[$str_identifier_column_base_connection][4] = true; // Hidden column
		
		$this->arr_column_connection_identifiers[$str_identifier_column_base_connection] = $str_identifier_column_base_connection;
	}
	
	protected function iterateCollectorColumn($num_column, $arr_state_position = [], $arr_state_row = []) {
		
		$str_identifier_column = $this->arr_column_identifier_keys[$num_column];
		$arr_collector_column = ($this->arr_collector[$str_identifier_column] ?? null);

		if ($arr_collector_column === null) { // End of chain, create an array to be passed down
			
			yield [];
			
			return;
		}
		
		$arr_column_base = $this->arr_column_identifiers[$str_identifier_column];
		
		$str_identifier_column_base = $arr_column_base[0];
		$str_identifier_column_base_source = $arr_column_base[1];
		$str_identifier_column_base_source_lineage = $arr_column_base[2];

		$str_identifier_state_position = ($arr_state_position[$str_identifier_column_base] ?? null);
		$str_identifier_state_row = $arr_state_row[$str_identifier_column_base_source_lineage];
				
		$is_state_owner_position = ($str_identifier_state_position === null ? true : false);
	
		if ($is_state_owner_position) {
			
			if (!isset($this->arr_collector_connections[$str_identifier_column_base])) {
				$arr_identifiers_state_position = $str_identifier_state_row;
			} else if ($str_identifier_column_base_source == $str_identifier_column_base_source_lineage) { // The source lineage identifier is the same as the source main identifier; connect the source position pointer to a new position pointer
				$arr_identifiers_state_position = $this->arr_collector_connections[$str_identifier_column_base][$arr_state_position[$str_identifier_column_base_source]];
			} else {
				$arr_identifiers_state_position = $this->arr_collector_connections[$str_identifier_column_base][$str_identifier_state_row]; // Connect the source lineage row pointer to a new position pointer
			}
			
			$arr_identifiers_state_position = ($arr_identifiers_state_position ? (array)$arr_identifiers_state_position : [false]); // Apply a false value to claim state ownership on empty columns
		} else {
			$arr_identifiers_state_position = [$str_identifier_state_position];
		}
		
		$str_identifier_column_base_lineage = ($arr_column_base[3] ?? null);
		
		if ($str_identifier_column_base_lineage) { // Check for lineage state
				
			$is_state_owner_row = (!isset($arr_state_row[$str_identifier_column_base_lineage]) ? true : false); // The first encounter with a possible relational column
			
			if (!$is_state_owner_row) { // Listen state
				$str_identifier_state_row = $arr_state_row[$str_identifier_column_base_lineage];
			}
		}
		
		$is_hidden_column = ($arr_column_base[4] ?? false);
		
		foreach ($arr_identifiers_state_position as $str_identifier_state_position) {
			
			if ($is_state_owner_position) {
				$arr_state_position[$str_identifier_column_base] = $str_identifier_state_position;
			}
			
			$arr_collector_column_position = $arr_collector_column[$str_identifier_state_position];
			
			if ($str_identifier_column_base_lineage && $is_state_owner_row && $arr_collector_column_position) {
				
				foreach ($arr_collector_column_position as $str_identifier_row => $value) {
					
					$arr_state_row[$str_identifier_column_base_lineage] = $str_identifier_row;
					
					$iterator = $this->iterateCollectorColumn($num_column + 1, $arr_state_position, $arr_state_row);
					
					foreach ($iterator as $arr_row) {
						
						// Append this column's value and pass it down the chain
						
						if (!$is_hidden_column) {
							$arr_row[] = $value;
						}
						
						yield $arr_row;
					}
				}
			} else {
				
				if ($arr_collector_column_position) {
					
					if ($str_identifier_column_base_lineage && !$is_state_owner_row) {
						$value = $arr_collector_column_position[$str_identifier_state_row];
					} else {
						$value = $arr_collector_column_position;
					}
				} else {
					$value = null;
				}
				
				$iterator = $this->iterateCollectorColumn($num_column + 1, $arr_state_position, $arr_state_row);
				
				foreach ($iterator as $arr_row) {
					
					// Append this column's value and pass it down the chain
					
					if (!$is_hidden_column) {
						$arr_row[] = $value;
					}
					
					yield $arr_row;
				}
			}
		}
	}
	
	protected function iterateCollector($object_id) {
			
		// Initiate iteration chain starting with the first column
		
		$iterator = $this->iterateCollectorColumn(0);
					
		// Collect all rows that are being returned
		
		foreach ($iterator as $arr_row) {
			
			$arr_row = array_reverse($arr_row);
			$arr_row = GenerateTypeObjects::printSharedTypeObjectNames($arr_row, false);

			yield $arr_row;
		}			
	}

	protected function collectColumns($start_stop = true) {
		
		$this->do_collect_columns = $start_stop;

		if ($start_stop) {
			
			$this->arr_column_names = [];
		} else {
			
			$this->arr_column_identifier_keys = array_keys($this->arr_column_identifiers);
		}
	}
			
	public function createPackage($arr_options) {
		
		$str_separator = $arr_options['separator'];
		$str_enclose = $arr_options['enclose'];
		$this->str_escape = $str_enclose; // Needed for output
		
		$this->package = getStreamMemory(false);
		
		$this->class_collect->setWalkMode(true);

		$this->collectColumns(true);
		$this->class_collect->getWalkedObject(0, [], [$this, 'collect']);
		$this->collectColumns(false);
		
		if ($this->arr_options['include_description_name']) {
			fputcsv($this->package, Labels::printLabels($this->arr_column_names), $str_separator, $str_enclose, CSV_ESCAPE);
		}
		
		$this->class_collect->setInitLimit(static::$num_objects_stream);

		while ($this->class_collect->init($this->arr_filters)) {
			
			$arr_objects = $this->class_collect->getPathObjects('0');
			
			GenerateTypeObjects::setClearSharedTypeObjectNames(false); // Disabled clearing name cache

			foreach ($arr_objects as $object_id => $arr_object) { // 0, source, as collection is path based
				
				$this->arr_collector_connections = [];
				$this->arr_collector = [];
				
				$this->class_collect->getWalkedObject($object_id, [], [$this, 'collect']);

				foreach ($this->iterateCollector($object_id) as $arr_row) {
					
					fputcsv($this->package, $arr_row, $str_separator, $str_enclose, CSV_ESCAPE);
				}
			}
			
			// Manual clearing name cache, being disabled for the iterator
			GenerateTypeObjects::setClearSharedTypeObjectNames(true);
			GenerateTypeObjects::printSharedTypeObjectNames('');
		}
		
		rewind($this->package);

		return ($this->package !== false ? true : false);
	}
	
	public function readPackage($str_filename) {
		
		$data = read($this->package);
		
		Response::setFormat(Response::OUTPUT_CSV);
		Response::setFormatSettings($this->str_escape);
		$data = Response::parse($data);
		
		// UTF-8 BOM
		$data = "\xEF\xBB\xBF".$data;

		Response::sendFileHeaders($data, $str_filename.'.csv');
		
		echo $data;
	}
	
	public function getPackage() {
		
		$data = read($this->package);
		
		Response::holdFormat(true);
		
		Response::setFormat(Response::OUTPUT_CSV);
		Response::setFormatSettings($this->str_escape);
		$data = Response::parse($data);
		
		Response::holdFormat();
		
		ftruncate($this->package, 0);
		fwrite($this->package, $data);
		
		rewind($this->package);
		
		return $this->package;
	}
	
	public static function getCollectorSettings() {
	
		return [
			'conditions' => GenerateTypeObjects::CONDITIONS_MODE_TEXT
		];
	}
}
