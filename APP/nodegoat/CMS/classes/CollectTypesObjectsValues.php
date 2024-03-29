<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CollectTypesObjectsValues extends CollectTypesObjects {
	
	protected $type_id = false;
	protected $value_type = '';
	protected $arr_value_type_settings = [];
	
	protected $arr_filters = [];
	
	protected static $num_store_objects_buffer = 2000;
	protected static $num_store_objects_stream = 50000;

	public function __construct($type_id, $view = GenerateTypeObjects::VIEW_SET, $value_type = '', $arr_value_type_settings = []) {
		
		$this->type_id = $type_id;
		$this->value_type = $value_type;
		$this->arr_value_type_settings = $arr_value_type_settings;
		
		$this->view = $view;
	}
    
	public function prepare($arr_type_network, $arr_filters) {
		
		if (!$arr_type_network) {
			return;
		}
				
		if ($arr_filters) {
			$this->arr_filters = FilterTypeObjects::convertFilterInput($arr_filters);
		} else {
			$this->arr_filters = [];
		}
		
		if ($arr_type_network['paths']) {
		
			$trace = new TraceTypesNetwork($this->arr_scope['types'], true, true);
			$trace->filterTypesNetwork($arr_type_network['paths']);
			$trace->run($this->type_id, false, cms_nodegoat_details::$num_network_trace_depth);
			$arr_type_network_paths = $trace->getTypeNetworkPaths(true);
		} else {
			
			$arr_type_network_paths = ['start' => [$this->type_id => ['path' => [0]]]];
		}
		
		parent::__construct($arr_type_network_paths, $this->view);
		$this->init($this->arr_filters, false);
			
		$arr_collect_info = $this->getResultInfo();
		
		foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
			
			if ($this->arr_scope['project_id']) {
				
				$arr_project = StoreCustomProject::getProjects($this->arr_scope['project_id']);
				
				if ($arr_project['types'][$cur_type_id]['type_filter_id']) {
				
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
					
					$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($arr_project['project']['id'], false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
					$this->addLimitTypeFilters($cur_type_id, FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project['types'][$cur_type_id]['type_filter_object_subs']);
				}
			}
			
			foreach ($arr_paths as $path) {
				
				$source_path = $path;
				if ($source_path) { // path includes the target type id, remove it
					$source_path = explode('-', $source_path);
					array_pop($source_path);
					$source_path = implode('-', $source_path);
				}
				
				$arr_settings = ($arr_type_network ? $arr_type_network['types'][$source_path][$cur_type_id] : []);
				
				$arr_filtering = [];
				if ($arr_settings['filter']) {
					$arr_filtering = ['all' => true];
				}
				
				$arr_in_selection = ($arr_settings['selection'] ?: []);

				$arr_selection = [
					'object' => [],
					'object_descriptions' => [],
					'object_sub_details' => []
				];
				
				if ($arr_in_selection) {
										
					foreach ($arr_in_selection as $id => $arr_selected) {
						
						$object_description_id = $arr_selected['object_description_id'];
						
						if ($object_description_id) {

							$s_arr =& $arr_selection['object_descriptions'][$object_description_id];
							$s_arr['object_description_id'] = true;
							$s_arr['object_description_value'] = true;
						}
						
						$object_sub_details_id = $arr_selected['object_sub_details_id'];
						
						if ($object_sub_details_id) {
							
							$str_attribute = $arr_selected['attribute'];
							
							if ($str_attribute == 'date_start' || $str_attribute == 'date_end']) {
								
								$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date'] = true;
								if ($str_attribute == 'date_start']) {
									$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date_start'] = true;
								}
								if ($str_attribute == 'date_end']) {
									$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date_end'] = true;
								}
							} else if (!isset($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'])) { // Set empty selection on sub object details if nothing is selected
								
								$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = [];
							}
							
							if (!isset($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'])) { // Set default empty selection on sub object descriptions as there could be none selected
								
								$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = [];
							}
							
							$object_sub_description_id = $arr_selected['object_sub_description_id'];
							
							if ($object_sub_description_id) {
								
								$s_arr =& $arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
								$s_arr['object_sub_description_id'] = true;
								$s_arr['object_sub_description_value'] = true;
							}
						}
					}
					unset($s_arr);
				}
		
				$this->setPathOptions([$path => [
					'arr_selection' => $arr_selection,
					'arr_filtering' => $arr_filtering
				]]);
			}
		}
	}
		
	public function collectToTable($sql_table_name, $source_object_sub_details_id) {

		$this->setInitLimit(static::$num_store_objects_stream);

		while ($this->init($this->arr_filters)) {

			$arr_objects = $this->getPathObjects('0');
			
			Mediator::checkState();
			
			$arr_sql_insert = [];
			
			foreach ($arr_objects as $object_id => $arr_object) {
				
				$source_object_sub_id = false;
			
				$arr_walked = $this->getWalkedObject($object_id, [], function &($cur_target_object_id, &$cur_arr, $source_path, $cur_path, $cur_target_type_id, &$arr_info) use ($source_object_sub_details_id, &$source_object_sub_id) {
					
					if ($source_path == (string)$this->type_id) { // Check for specific sub-object selection at the start
				
						if ($arr_info['in_out'] == 'out' && $arr_info['object_sub_details_id'] == $source_object_sub_details_id) {
							$source_object_sub_id = $arr_info['object_sub_id'];
						} else {
							$source_object_sub_id = false;
						}
					}
					
					$arr_selection = $this->arr_path_options[$cur_path]['arr_selection'];
										
					if ($arr_selection) {
						
						if ($source_object_sub_id) {
							$s_arr =& $cur_arr['object_sub_ids'][$source_object_sub_id];
						} else {
							$s_arr =& $cur_arr['value'];
						}
						
						$arr_object = $this->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id']);
						
						foreach ($arr_object['object_definitions'] as $object_description_id => $arr_object_definition) {
							
							$value = $arr_object_definition['object_definition_value'];
							
							if (!$value) {
								continue;
							}
							
							$s_arr[$value] = $value;
						}
						
						foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
							
							$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
						
							if ($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date_start']) {
								
								$value = $arr_object_sub['object_sub']['object_sub_date_start'];
								
								if ($value) {
									$s_arr[$value] = $value;
								}
							}
							
							if ($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date_end']) {
								
								$value = $arr_object_sub['object_sub']['object_sub_date_end'];
								
								if ($value) {
									$s_arr[$value] = $value;
								}
							}
							
							foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
								
								$value = $arr_object_sub_definition['object_sub_definition_value'];
								
								if (!$value) {
									continue;
								}
								
								$s_arr[$value] = $value;
							}
						}
					}
			
					return $cur_arr;
				});
	
				if ($arr_walked) {

					if ($arr_walked['value']) {
						
						if ($this->arr_value_type_settings['int_direction'] == StoreType::TIME_BEFORE_END || $this->arr_value_type_settings['int_direction'] == StoreType::TIME_AFTER_END) {
							$value_object = max($arr_walked['value']);
						} else {
							$value_object = min($arr_walked['value']);
						}
					} else {
						$value_object = false;
					}
					
					if ($arr_walked['object_sub_ids']) {
					
						foreach ($arr_walked['object_sub_ids'] as $object_sub_id => $value_object_sub) {
							
							if ($value_object_sub === null) {
								
								if ($value_object) {
									$value_object_sub = $value_object;
								} else {
									continue;
								}
							} else {
							
								if ($this->arr_value_type_settings['int_direction'] == StoreType::TIME_BEFORE_END || $this->arr_value_type_settings['int_direction'] == StoreType::TIME_AFTER_END) {
									$value_object_sub = max($value_object_sub);
									$value_object_sub = ($value_object && $value_object > $value_object_sub ? $value_object : $value_object_sub);
								} else {
									$value_object_sub = min($value_object_sub);
									$value_object_sub = ($value_object && $value_object < $value_object_sub ? $value_object : $value_object_sub);
								}
							}
							
							$arr_sql_insert[] = "(".(int)$this->arr_value_type_settings['int_identifier'].", ".$object_id.", ".$object_sub_id.", ".(int)$value_object_sub.")";
						}
					} else if ($value_object) {
						
						$arr_sql_insert[] = "(".(int)$this->arr_value_type_settings['int_identifier'].", ".$object_id.", 0, ".(int)$value_object.")";
					}
				}
			}

			unset($arr_walked, $arr_objects);
			
			if ($arr_sql_insert) {
								
				$count = 0;
				$arr_sql_chunk = array_slice($arr_sql_insert, $count, static::$num_store_objects_buffer);
				
				while ($arr_sql_chunk) {
					
					$res = DB::query("INSERT INTO ".DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$sql_table_name)."
						(identifier, object_id, object_sub_id, date_value)
							VALUES
						".implode(',', $arr_sql_chunk)."
						".DBFunctions::onConflict('identifier, object_id, object_sub_id', ['object_id'])."
					");
					
					$count += static::$num_store_objects_buffer;
					$arr_sql_chunk = array_slice($arr_sql_insert, $count, static::$num_store_objects_buffer);
				}
				
				unset($arr_sql_insert);
			}
		}
	}
}
