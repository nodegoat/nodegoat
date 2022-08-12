<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CollectTypesObjects {
	
	protected $arr_type_network_paths = [];
	
	protected $view = false;
	
	protected $nr_limit = 0;
	protected $is_generating = false;
	protected $arr_path_generate = [];
	
	protected $arr_path_objects = [];
	protected $arr_path_objects_referenced = [];
	protected $arr_path_selection_required = [];
	protected $arr_type_filters = [];
	protected $arr_type_conditions = [];
	protected $arr_path_object_connections = [];
	protected $arr_types = [];
	protected $arr_paths = [];
	
	protected $arr_limit_type_filters = [];
	protected $arr_filter_type_filters = [];
	protected $arr_type_options = [];
	protected $arr_path_options = [];
	protected $arr_scope = [];
	protected $conditions = null;
	protected $func_conditions = null;
	protected $func_generate = null;
	
	protected $arr_depth_filters = [];
	protected $arr_collapse_paths = [];	
	protected $arr_types_found = [];

	public $force_walk = false;

	public function __construct($arr_type_network_paths, $view = GenerateTypeObjects::VIEW_ALL) {
		
		$this->arr_type_network_paths = $arr_type_network_paths;
		$this->view = $view;
		
	}
	
	public function setInitLimit($limit) {
		
		$this->nr_limit = $limit;
	}
	
	public function init($arr_filter = [], $do_collect = true) {
		
		$this->arr_type_filters = $this->arr_types = [];
		
		$this->arr_path_objects = $this->arr_path_objects_referenced = [];
		
		$this->collectObjects($this->arr_type_network_paths, $arr_filter, false, $do_collect);
		
		if ($do_collect) {
			$this->is_generating = true;
		}
		
		if ($this->arr_path_objects[0]['start']) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getPathObjects($path, $in_out = false) {
		
		return $this->arr_path_objects[$path][($in_out ?: 'start')];
	}
	
	public function getPathObject($path, $in_out, $object_id, $ref_object_id) {
		
		$arr_object = $this->arr_path_objects[$path][($in_out ?: 'start')][$object_id];
		
		if (!$arr_object) {
			return [];
		}
	
		$arr_selection = ($this->arr_path_options[$path]['arr_selection'] ?: ($this->arr_type_options[$this->arr_paths[$path]]['arr_selection'] ?: []));
		$arr_selection_required = $this->arr_path_selection_required[$path]; // Remove values from objects originally not in selection but needed for collection
						
		if ($arr_selection_required['object_descriptions']) {
			
			$s_arr_object_definitions =& $arr_object['object_definitions'];
			
			foreach ($arr_selection_required['object_descriptions'] as $object_description_id => $arr_required) {
				unset($s_arr_object_definitions[$object_description_id]);
			}
		}
		
		$s_arr_object_subs =& $arr_object['object_subs'];
		
		foreach ($arr_object['object_subs'] as $object_sub_id => &$arr_object_sub) {
			
			$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
			$arr_selection_required_object_sub_details = $arr_selection_required['object_sub_details'][$object_sub_details_id];

			// If the sub-object is part of the object path, and the source of this object is in/referenced, do not include sub-objects that are not part of this path (other sub-objects of the same type could be part other objects' paths)
			if ($this->arr_path_object_connections[$path]['object_sub_details'][$object_sub_details_id]['use'] == 'solo' && !isset($this->arr_path_objects_referenced[$path][$ref_object_id]['object_subs'][$object_sub_details_id][$object_sub_id])) {
				
				unset($s_arr_object_subs[$object_sub_id]);
			} else if ($arr_selection_required_object_sub_details && !$arr_selection['object_sub_details'][$object_sub_details_id]) {
			
				unset($s_arr_object_subs[$object_sub_id]);
			} else {

				if ($arr_selection_required_object_sub_details['object_sub_descriptions']) {
					
					$s_arr_object_sub_definitions =& $arr_object_sub['object_sub_definitions'];
					
					foreach ($arr_selection_required_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_required) {
						unset($s_arr_object_sub_definitions[$object_sub_description_id]);
					}
				}
			}
		}
		
		return $arr_object;
	}
	
	public function getResultInfo() {
		
		foreach ($this->arr_types_found as $type => $value) { // Prevent double type ids from the collection merge
			$value = array_unique($value);
			$this->arr_types_found[$type] = array_combine($value, $value);
		}
		
		return [
			'types' => $this->arr_types,
			'connections' => $this->arr_path_object_connections,
			'filters' => $this->arr_type_filters,
			'conditions' => $this->arr_type_conditions,
			'types_found' => $this->arr_types_found
		];
	}
	
	public function getResultSource($path, $in_out = false) {
		
		$arr_filters = $this->arr_path_generate[$path];
		
		if ($in_out) {
			
			if ($in_out == 'start') {
				return $arr_filters[$in_out][0];
			} else {
				return $arr_filters[$in_out];
			}
		} else {
			return $arr_filters;
		}
	}
	
	public function getReferenced() {
		
		if (!$this->arr_path_objects_referenced) {
			$this->collectReferenced($this->arr_type_network_paths);
		}
		
		return $this->arr_path_objects_referenced;
	}
	
	public function getWalkedObject($object_id, $arr = [], $func_call = false) {
		
		if (!$this->arr_path_objects_referenced) {
			$this->collectReferenced($this->arr_type_network_paths);
		}
				
		return $this->walkObject($object_id, $arr, [], $func_call, $this->arr_type_network_paths);
	}
		
	private function collectReferenced($arr_type_connections) {
		
		foreach ($arr_type_connections as $in_out => $arr_in_out) {
			
			if ($in_out == 'connections') {
				continue;
			}
			
			foreach ($arr_in_out as $target_type_id => $arr_type_object_connections) {
				
				$path = implode('-', $arr_type_object_connections['path']);
				if ($in_out == 'start') {
					$path = '0';
				} else {
					$path = $path.'-'.$target_type_id;
				}
				
				if ($in_out == 'in') {
					
					foreach ($this->arr_path_objects[$path]['in'] as $object_id => $arr_object) {
						
						if ($arr_type_object_connections['object_descriptions']) {
								
							foreach ($arr_type_object_connections['object_descriptions'] as $object_description_id => $arr_connection) {
								
								if ($arr_connection['dynamic']) {
									$arr_ref_object_ids = $arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'][$arr_connection['ref_type_id']];
									$arr_ref_object_ids = ($arr_ref_object_ids ? array_keys($arr_ref_object_ids) : []);
								} else {
									$arr_ref_object_ids = (array)$arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'];
								}
								
								foreach ($arr_ref_object_ids as $ref_object_id) {
									
									if (!$ref_object_id) {
										continue;
									}
									
									$this->arr_path_objects_referenced[$path][$ref_object_id]['object_definitions'][$object_description_id][$object_id] = $object_id;
								}
							}
						}
						
						if ($arr_type_object_connections['object_sub_details']) {
							
							foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
								
								$arr_object_sub_details = $arr_type_object_connections['object_sub_details'][$arr_object_sub['object_sub']['object_sub_details_id']];
								
								if (!$arr_object_sub_details) {
									continue;
								}
								
								foreach ($arr_object_sub_details as $object_sub_connection_id => $arr_connection) {
									
									if ($object_sub_connection_id === 'object_sub_location') {
										
										$ref_object_id = $arr_object_sub['object_sub']['object_sub_location_ref_object_id'];
										
										if ($ref_object_id) {
											
											$this->arr_path_objects_referenced[$path][$ref_object_id]['object_subs'][$arr_object_sub['object_sub']['object_sub_details_id']][$object_sub_id]['object_sub_locations'][$object_id] = $object_id;
										}
									} else {
										
										if ($arr_connection['dynamic']) {
											$arr_ref_object_ids = $arr_object_sub['object_sub_definitions'][$object_sub_connection_id]['object_sub_definition_ref_object_id'][$arr_connection['ref_type_id']];
											$arr_ref_object_ids = ($arr_ref_object_ids ? array_keys($arr_ref_object_ids) : []);
										} else {
											$arr_ref_object_ids = (array)$arr_object_sub['object_sub_definitions'][$object_sub_connection_id]['object_sub_definition_ref_object_id'];
										}
										
										foreach ($arr_ref_object_ids as $ref_object_id) {
											
											if (!$ref_object_id) {
												continue;
											}
									
											$this->arr_path_objects_referenced[$path][$ref_object_id]['object_subs'][$arr_object_sub['object_sub']['object_sub_details_id']][$object_sub_id]['object_sub_definitions'][$object_sub_connection_id][$object_id] = $object_id;
										}
									}
								}
							}						
						}
					}
				}
								
				if ($arr_type_connections['connections'][$target_type_id]) {
					$this->collectReferenced($arr_type_connections['connections'][$target_type_id]);
				}
			}
		}
	}
	
	private function &walkObject($object_id, &$arr, $arr_cur_info, $func_call, $arr_type_connections) {
		
		if (!$arr_type_connections) {			
			return $arr;
		}
		
		$arr_new = $arr;

		foreach ($arr_type_connections as $in_out => $arr_in_out) {
			
			if ($in_out == 'connections') {
				continue;
			}
			
			foreach ($arr_in_out as $target_type_id => $arr_type_object_connections) {
				
				$arr_info = [];
				
				$path = implode('-', $arr_type_object_connections['path']);
				if ($in_out == 'start') {
					
					$path = '0';
					
					$arr_info['identifier'] = 'start';
				} else {
					
					$path_new = $path.'-'.$target_type_id; // Use objects found down the tree (referenced)
					$path_source = (count($arr_type_object_connections['path']) == 1 ? '0' : $path); // Objects found down the tree
					
					$collapse = ($this->arr_path_options[$path_new]['collapse'] ?: ($this->arr_type_options[$target_type_id]['collapse'] ?: false));
					$collapsed_source = ($path_source && ($this->arr_path_options[$path_source]['collapse'] ?: ($this->arr_type_options[$arr_cur_info['type_id']]['collapse'] ?: false)));
					
					if ($collapse && $arr_cur_info['arr_collapse_source']) {
						$arr_info['arr_collapse_source'] = $arr_cur_info['arr_collapse_source'];
						$collapse_start = false;
					} else if ($collapsed_source) {
						$arr_info['arr_collapsed_source'] = $arr_cur_info['arr_collapse_source'];
						$collapse_start = false;
					} else {
						$collapse_start = true;
					}
					
					$arr_info['identifier_source'] = $arr_cur_info['identifier'];
				}
			
				if ($in_out == 'start') {
					
					$arr_info['in_out'] = 'start';
										
					$arr_new =& $func_call($object_id, $arr, $path, $path, $target_type_id, $arr_info, $this);
					
					$arr_new =& $this->walkObject($object_id, $arr_new, $arr_info, $func_call, $arr_type_connections['connections'][$target_type_id]);
					
					return $arr; // Return the original referenced array to getWalkedObject()
				} else {
					
					if ($arr_type_object_connections['object_descriptions']) {
						
						foreach ($arr_type_object_connections['object_descriptions'] as $object_description_id => $arr_connection) {

							if ($in_out == 'out') {
								
								$arr_ref_object_ids = [];
																
								$do_trace_check = ($arr_cur_info['in_out'] == 'in' && $arr_cur_info['object_description_id'] == $object_description_id); // If the object description is tracing back to the same object's path, and the source of this object is in/referenced, do not include this object

								if ($arr_connection['dynamic']) {
									
									foreach ($this->arr_path_objects[$path_source] as $arr_objects) {
										
										$arr_object_ids = $arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_ref_object_id'][$arr_connection['ref_type_id']];
										
										if ($arr_object_ids) {
											foreach ($arr_object_ids as $ref_object_id => $value) {
												
												if ($do_trace_check && $arr_cur_info['object_id'] == $ref_object_id) {
													continue;
												}

												$arr_ref_object_ids[$ref_object_id] = $ref_object_id;
											}
										}
									}
								} else {
									
									foreach ($this->arr_path_objects[$path_source] as $arr_objects) {
									
										$arr_object_ids = $arr_objects[$object_id]['object_definitions'][$object_description_id]['object_definition_ref_object_id'];
										
										if ($arr_object_ids) {
											if (is_array($arr_object_ids)) {
												
												foreach ($arr_object_ids as $ref_object_id) {
													
													if ($do_trace_check && $arr_cur_info['object_id'] == $ref_object_id) {
														continue;
													}
												
													$arr_ref_object_ids[$ref_object_id] = $ref_object_id;
												}
											} else {
												
												$arr_ref_object_ids[$arr_object_ids] = $arr_object_ids;
											}
										}
									}
								}
								
								$type_id = $arr_connection['type_id'];
							} else {
								
								$arr_ref_object_ids = ($this->arr_path_objects_referenced[$path_new][$object_id]['object_definitions'][$object_description_id] ?? []);
								$type_id = $arr_connection['ref_type_id'];
							}
							
							$arr_info_use = ['type_id' => $type_id, 'object_id' => $object_id, 'object_description_id' => $object_description_id, 'dynamic' => $arr_connection['dynamic'], 'in_out' => $in_out, 'identifier' => $arr_cur_info['identifier'].'_'.$arr_connection['identifier']] + $arr_info;
							
							if ($collapse) {
								if ($collapse_start) {
									$arr_info_use['arr_collapse_source'] = $arr_info_use;
									$arr_info_use['collapse_start'] = true;
								}
							}
							if ($arr_info_use['arr_collapse_source']) {
								$arr_info_use['arr_collapse_targets'] = $this->arr_collapse_paths[$path_new];
							}
							
							if ($this->force_walk && !$arr_ref_object_ids) {
								$arr_ref_object_ids[] = 0;
							}
							
							foreach ($arr_ref_object_ids as $ref_object_id) {
								
								if ((!$ref_object_id || !$this->arr_path_objects[$path_new][$in_out][$ref_object_id]) && !$this->force_walk) {
									continue;
								}
								
								$arr_new =& $func_call($ref_object_id, $arr, $path, $path_new, $target_type_id, $arr_info_use, $this);

								$arr_new =& $this->walkObject($ref_object_id, $arr_new, $arr_info_use, $func_call, $arr_type_connections['connections'][$target_type_id]);
							}
						}
					}
				
					if ($arr_type_object_connections['object_sub_details']) {
						
						foreach ($arr_type_object_connections['object_sub_details'] as $object_sub_details_id => $arr_object_sub_connections) {
																					
							foreach ($arr_object_sub_connections as $object_sub_connection_id => $arr_connection) {
								
								$arr_object_subs_ref_object_ids = [];
								
								if ($object_sub_connection_id === 'object_sub_location') {
																	
									if ($in_out == 'out') {
										
										foreach ($this->arr_path_objects[$path_source] as $arr_objects) {
											
											$arr_object_subs = $arr_objects[$object_id]['object_subs'];
											
											if (!$arr_object_subs) {
												continue;
											}
											
											foreach ($arr_object_subs as $object_sub_id => $arr_object_sub) {
												
												if ($arr_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id || $arr_object_sub['object_sub']['object_sub_location_ref_type_id'] != $target_type_id) {
													continue;
												}
												
												// If the sub-object is part of the object path, and the source of this object is in/referenced, do not include sub-objects that are not part of this path (other sub-objects of the same type could be part other objects' paths)
												if ($arr_cur_info['in_out'] == 'in' && $arr_cur_info['object_sub_details_id'] == $object_sub_details_id && !$arr_cur_info['object_sub_location'] && $arr_cur_info['object_sub_id'] != $object_sub_id) {
													continue;
												}

												$ref_object_id = $arr_object_sub['object_sub']['object_sub_location_ref_object_id'];
												if ($ref_object_id) {
													$arr_object_subs_ref_object_ids[$object_sub_id][$ref_object_id] = $ref_object_id;
												}
											}
										}
										
										$type_id = $arr_connection['type_id'];
									} else {
										
										$arr_object_subs = ($this->arr_path_objects_referenced[$path_new][$object_id]['object_subs'][$object_sub_details_id] ?? null);
										
										if ($arr_object_subs) {
											foreach ($arr_object_subs as $object_sub_id => $arr_object_sub) {
												$arr_object_subs_ref_object_ids[$object_sub_id] = (array)$arr_object_sub['object_sub_locations'];
											}
										}
										
										$type_id = $arr_connection['ref_type_id'];
									}
									
									$arr_info_use = ['type_id' => $type_id, 'object_id' => $object_id, 'object_sub_details_id' => $object_sub_details_id, 'object_sub_location' => true, 'in_out' => $in_out, 'identifier' => $arr_cur_info['identifier'].'_'.$arr_connection['identifier']] + $arr_info;
								} else {
									
									if ($in_out == 'out') {
										
										foreach ($this->arr_path_objects[$path_source] as $arr_objects) {
											
											$arr_object_subs = $arr_objects[$object_id]['object_subs'];
											
											if (!$arr_object_subs) {
												continue;
											}
											
											foreach ($arr_object_subs as $object_sub_id => $arr_object_sub) {
												
												if ($arr_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
													continue;
												}
												
												// If the sub-object is part of the object path, and the source of this object is in/referenced, do not include sub-objects that are not part of this path (other sub-objects of the same type could be part other objects' paths)
												if ($arr_cur_info['in_out'] == 'in' && $arr_cur_info['object_sub_details_id'] == $object_sub_details_id && $arr_cur_info['object_sub_description_id'] != $object_sub_connection_id && $arr_cur_info['object_sub_id'] != $object_sub_id) {
													continue;
												}
												
												if ($arr_connection['dynamic']) {
													
													$arr_ref_object_ids = $arr_object_sub['object_sub_definitions'][$object_sub_connection_id]['object_sub_definition_ref_object_id'][$arr_connection['ref_type_id']];
													
													if ($arr_ref_object_ids) {
														
														foreach ($arr_ref_object_ids as $ref_object_id => $value) {
															$arr_object_subs_ref_object_ids[$object_sub_id][$ref_object_id] = $ref_object_id;
														}
													}
												} else {
													
													$ref_object_id = $arr_object_sub['object_sub_definitions'][$object_sub_connection_id]['object_sub_definition_ref_object_id'];
													
													if ($ref_object_id) {
														$arr_object_subs_ref_object_ids[$object_sub_id][$ref_object_id] = $ref_object_id;
													}
												}
											}
										}
										
										$type_id = $arr_connection['type_id'];
									} else {
										
										$arr_object_subs = ($this->arr_path_objects_referenced[$path_new][$object_id]['object_subs'][$object_sub_details_id] ?? null);
										
										if ($arr_object_subs) {
											foreach ($arr_object_subs as $object_sub_id => $arr_object_sub) {
												$arr_object_subs_ref_object_ids[$object_sub_id] = (array)$arr_object_sub['object_sub_definitions'][$object_sub_connection_id];
											}
										}
										
										$type_id = $arr_connection['ref_type_id'];
									}
									
									$arr_info_use = ['type_id' => $type_id, 'object_id' => $object_id, 'object_sub_details_id' => $object_sub_details_id, 'object_sub_description_id' => $object_sub_connection_id, 'dynamic' => $arr_connection['dynamic'], 'in_out' => $in_out, 'identifier' => $arr_cur_info['identifier'].'_'.$arr_connection['identifier']] + $arr_info;
								}
								
								if ($this->force_walk && !$arr_object_subs_ref_object_ids) {
									$arr_object_subs_ref_object_ids[] = 0;
								}
								
								foreach ($arr_object_subs_ref_object_ids as $object_sub_id => $arr_ref_object_ids) {
								
									$arr_info_use['object_sub_id'] = $object_sub_id;
									
									if ($collapse) {
										if ($collapse_start) {
											$arr_info_use['arr_collapse_source'] = $arr_info_use;
											$arr_info_use['collapse_start'] = true;
										}
									}
									if ($arr_info_use['arr_collapse_source']) {
										$arr_info_use['arr_collapse_targets'] = $this->arr_collapse_paths[$path_new];
									}
									
									if ($this->force_walk && !$arr_ref_object_ids) {
										$arr_ref_object_ids = [0];
									}

									foreach ($arr_ref_object_ids as $ref_object_id) {
										
										if ((!$ref_object_id || !$this->arr_path_objects[$path_new][$in_out][$ref_object_id]) && !$this->force_walk) {
											continue;
										}
										
										$arr_new =& $func_call($ref_object_id, $arr, $path, $path_new, $target_type_id, $arr_info_use, $this);

										$arr_new =& $this->walkObject($ref_object_id, $arr_new, $arr_info_use, $func_call, $arr_type_connections['connections'][$target_type_id]);
									}
								}					
							}
						}
					}
				}
			}
		}
		
		return $arr_new; // Return the reference of the created array by $func_call and ascendant walkObject()s to the descendant walkObject()
	}
				
	private function collectObjects($arr_type_connections, $arr_source_filters, $filter_source, $do_collect = true) {
		
		$arr = [];

		foreach ($arr_type_connections as $in_out => $arr_in_out) {
			
			if ($in_out == 'connections') {
				continue;
			}
			
			foreach ($arr_in_out as $target_type_id => $arr_type_object_connections) {

				$path = implode('-', $arr_type_object_connections['path']);
				if ($in_out == 'start') {
					$path_new = $path;
					$path_source = false;
				} else {
					$path_new = $path.'-'.$target_type_id;
					$path_source = (count($arr_type_object_connections['path']) == 1 ? '0' : $path);
				}
				
				if (!isset($this->arr_path_objects[$path_new][$in_out])) {
					$this->arr_path_objects[$path_new][$in_out] = [];
				}
				
				$filter = false;
				
				if ($do_collect && $this->is_generating) {
					
					$has_objects = false;
					
					foreach ($this->arr_path_generate[$path_new][$in_out] as $filter_generating) {
					
						$arr_objects = $filter_generating->init();
					
						$this->arr_path_objects[$path_new][$in_out] += $arr_objects;
					
						if ($arr_objects) {

							$arr_result = $filter_generating->getProcessedResultInfo();
							$this->arr_types_found = array_merge_recursive($this->arr_types_found, $arr_result['types_found']);
							
							$has_objects = true;
						}
					}
					
					if ($has_objects && isset($arr_type_connections['connections'][$target_type_id])) {
						$this->collectObjects($arr_type_connections['connections'][$target_type_id], false, false);
					}
				} else {
					
					$this->arr_types[$target_type_id][$path_new] = $path_new;
					$this->arr_paths[$path_new] = $target_type_id;
					
					$arr_collect_filters = ['all' => [], 'query' => [], 'filter' => [], 'filtering' => []];				
					
					$arr_selection = ($this->arr_path_options[$path_new]['arr_selection'] ?? ($this->arr_type_options[$target_type_id]['arr_selection'] ?? []));
					$arr_selection_required = [];
					
					$collapse = ($this->arr_path_options[$path_new]['collapse'] ?? null ?: ($this->arr_type_options[$target_type_id]['collapse'] ?? null ?: false));
					$collapsed_source = ($path_source && ($this->arr_path_options[$path_source]['collapse'] ?? null ?: ($this->arr_type_options[$arr_cur_info['type_id']]['collapse'] ?? null ?: false)));

					$arr_path_filter = [];
					
					if ($do_collect) {
						
						$filter = new FilterTypeObjects($target_type_id, $this->view, false, false);
						
						$this->arr_path_generate[$path_new][$in_out][] = $filter; // The source could itself be the result of both an incoming and outgoing path
						
						if ($in_out != 'start' && $filter_source) {
							
							if ($in_out == 'out') {
								$arr_path_filter['query_dependent'][] = $filter_source->format2SQLObjectReferencing($arr_type_object_connections, $target_type_id);
							} else {
								$arr_path_filter['query_dependent'][] = $filter->format2SQLObjectReferenced($arr_type_object_connections, $filter_source->storeResultTemporarily(true, true));
							}
						}
					}
					
					if ($in_out != 'start') {
					
						foreach (($arr_type_object_connections['object_descriptions'] ?? []) as $object_description_id => $arr_connection) {
							
							if ($in_out == 'out') {
								
								$s_arr_new = &$arr_path_filter['object_filter'][];

								if ($filter_source) {
									// Already in filter (as object query)
									$s_arr_new['any'] = true;
								} else {
									
									$s_arr = &$s_arr_new['referenced_types'][$arr_connection['type_id']]['object_definitions'][$object_description_id];

									if ($arr_source_filters) {
										$s_arr[] = $arr_source_filters;
									} else {
										$s_arr = ['objects' => ['relationality' => ['equality' => '>', 'value' => 0]]];
									}
								}
								
								if ($collapse) {
									$this->arr_collapse_paths[$path_source]['object_descriptions'][$object_description_id] = true;
								} else if ($collapsed_source) {
									$this->arr_collapse_paths[$path_source]['object_descriptions'][$object_description_id] = false;
								}
							} else {
								
								$s_arr_new = &$arr_path_filter['object_filter'][];

								if ($filter_source) {
									// Already in filter (as object query)
									$s_arr_new['any'] = true;
								} else {
									
									$s_arr = &$s_arr_new['object_definitions'][$object_description_id];

									if ($arr_source_filters) {
										$s_arr[] = $arr_source_filters;
									} else {
										$s_arr = ['transcension' => ['value' => 'not_empty']];
									}
								}
								
								if ($arr_selection && empty($arr_selection['object_descriptions'][$object_description_id])) {
									$arr_selection_required['object_descriptions'][$object_description_id]['object_description_reference'] = $object_description_id;
								}
								
								if ($collapse) {
									$this->arr_collapse_paths[$path_new]['object_descriptions'][$object_description_id] = true;
								}
							}	
												
							$this->arr_path_object_connections[$path_new]['object_descriptions'][$object_description_id] = $object_description_id;
						}
						
						foreach (($arr_type_object_connections['object_sub_details'] ?? []) as $object_sub_details_id => $arr_object_sub_connections) {
														
							foreach ($arr_object_sub_connections as $object_sub_connection_id => $arr_connection) {
								
								if ($object_sub_connection_id === 'object_sub_location') {
									
									if ($in_out == 'out') {
										
										$s_arr_new = &$arr_path_filter['object_filter'][];
										
										if ($filter_source) {
											// Already in filter (as object query)
											$s_arr_new['any'] = true;
										} else {
											
											$s_arr = &$s_arr_new['referenced_types'][$arr_connection['type_id']]['object_subs'][$object_sub_details_id]['object_sub_location_reference'];
											
											if ($arr_source_filters) {
												$s_arr[] = $arr_source_filters;
											} else {
												$s_arr = ['objects' => ['relationality' => ['equality' => '>', 'value' => 0]]];
											}
										}
										
										if ($collapse) {
											$this->arr_collapse_paths[$path_source]['object_sub_details'][$object_sub_details_id]['object_sub_location'] = true;
										} else if ($collapsed_source) {
											$this->arr_collapse_paths[$path_source]['object_sub_details'][$object_sub_details_id]['object_sub_location'] = false;
										}
									} else {
										
										$s_arr_new = &$arr_path_filter['object_filter'][];
										
										$s_arr = &$s_arr_new['object_subs'][$object_sub_details_id]['object_sub_locations'][];
										$s_arr['object_sub_location_ref_type_id'] = $arr_connection['ref_type_id'];
										$s_arr['object_sub_location_reference'] = ['mode' => 'self']; // Use Object's own reference, not the resolved reference (default)
										$s_arr = &$s_arr['object_sub_location_reference'];
										
										if ($filter_source) {
											// Already in filter (as object query), but do filter the sub-objects if they are not needed as a followup connection
											if ($arr_source_filters && !isset($arr_type_connections['connections'][$target_type_id]['out'][$arr_connection['ref_type_id']]['object_sub_details'][$object_sub_details_id]['object_sub_location'])) {
												$s_arr[]['query_dependent'] = $filter_source->format2SQLObject();
											} else {
												$s_arr_new['any'] = true;
											}
										} else {
											if ($arr_source_filters) {
												$s_arr[] = $arr_source_filters;
											} else {
												$s_arr = ['transcension' => ['value' => 'not_empty']];
											}
										}
										
										if ($arr_selection && empty($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'])) {
											
											$arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['object_sub_location_reference' => true];
											
											if (!isset($arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'])) {
												$arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = [];
											}
										}
										
										if ($collapse) {
											$this->arr_collapse_paths[$path_new]['object_sub_details'][$object_sub_details_id]['object_sub_location'] = true;
										}
									}
									
									$this->arr_path_object_connections[$path_new]['object_sub_details'][$object_sub_details_id]['object_sub_location'] = true;
								} else {
									
									if ($in_out == 'out') {
										
										$s_arr_new = &$arr_path_filter['object_filter'][];
										
										if ($filter_source) {
											// Already in filter (as object query)
											$s_arr_new['any'] = true;
										} else {
											
											$s_arr = &$s_arr_new['referenced_types'][$arr_connection['type_id']]['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_connection_id];
											
											if ($arr_source_filters) {
												$s_arr[] = $arr_source_filters;
											} else {
												$s_arr = ['objects' => ['relationality' => ['equality' => '>', 'value' => 0]]];
											}
										}
										
										if ($collapse) {
											$this->arr_collapse_paths[$path_source]['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id] = true;
										} else if ($collapsed_source) {
											$this->arr_collapse_paths[$path_source]['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id] = false;
										}
									} else {
										
										$s_arr_new = &$arr_path_filter['object_filter'][];
										
										$s_arr = &$s_arr_new['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_connection_id];
										
										if ($filter_source) {
											// Already in filter (as object query), but do filter the sub-objects if they are not needed as a followup connection
											if ($arr_source_filters && !isset($arr_type_connections['connections'][$target_type_id]['out'][$arr_connection['ref_type_id']]['object_sub_details'][$object_sub_details_id][$object_sub_connection_id])) {
												$s_arr[]['query_dependent'] = $filter_source->format2SQLObject();
											} else {
												$s_arr_new['any'] = true;
											}
										} else {
											if ($arr_source_filters) {
												$s_arr[] = $arr_source_filters;
											} else {
												$s_arr = ['transcension' => ['value' => 'not_empty']];
											}
										}
										
										if ($arr_selection && empty($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id])) {
											$arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id]['object_sub_description_reference'] = $object_sub_connection_id;
										}
										
										if ($collapse) {
											$this->arr_collapse_paths[$path_new]['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id] = true;
										}
									}
									
									$this->arr_path_object_connections[$path_new]['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id] = $object_sub_connection_id;
								}
								
								$cur_use = ($this->arr_path_object_connections[$path_new]['object_sub_details'][$object_sub_details_id]['use'] ?? null);
								
								if ($cur_use !== 'all') {
										
									$this->arr_path_object_connections[$path_new]['object_sub_details'][$object_sub_details_id]['use'] = ($in_out == 'in' ? 'solo' : 'all');
									
									// When the sub-object is used as both in/referenced and out in the same path, all sub-objects are available to generate the path
									if (isset($this->arr_path_object_connections[$path_source]['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id])) {
										$this->arr_path_object_connections[$path_source]['object_sub_details'][$object_sub_details_id]['use'] = 'all';
									}
								}
							}
						}
						
						if (!isset($arr_type_connections['connections'][$target_type_id])) {
							$this->arr_path_object_connections[$path_new]['end'] = true;
						}
					}
					
					if ($arr_selection) { // Collect values from referenced objects originally not in selection but needed for collection
						
						foreach (($arr_type_connections['connections'][$target_type_id]['out'] ?? []) as $user_type_id => $arr_target_type_object_connections) {
							
							foreach (($arr_target_type_object_connections['object_descriptions'] ?? []) as $object_description_id => $arr_connection) {
								
								if (empty($arr_selection['object_descriptions'][$object_description_id])) {
									$arr_selection_required['object_descriptions'][$object_description_id]['object_description_reference'] = $object_description_id;
								}
							}
							
							foreach (($arr_target_type_object_connections['object_sub_details'] ?? []) as $object_sub_details_id => $arr_object_sub_connections) {
														
								foreach ($arr_object_sub_connections as $object_sub_connection_id => $arr_connection) {
									
									if ($object_sub_connection_id === 'object_sub_location') {
										
										if (empty($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'])) {
											
											$arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['object_sub_location_reference' => true];
											
											if (!isset($arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'])) {
												$arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = [];
											}
										}
									} else {
									
										if (empty($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id])) {
											
											$arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id]['object_sub_description_reference'] = $object_sub_connection_id;
											
											if (empty($arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'])) {
												$arr_selection_required['object_sub_details'][$object_sub_details_id]['object_sub_details'] = [];
											}
										}
									}
								}
							}
						}
					}
					
					if ($in_out == 'start') {
						$arr_collect_filters['all'] = array_merge($arr_collect_filters['all'], $arr_source_filters);
					}
					
					if ($arr_path_filter) {
						
						$arr_collect_filters['all'][] = $arr_path_filter;
						
						if ($in_out == 'in') {
							$arr_collect_filters['filter'][] = $arr_path_filter;
						} else {
							$arr_collect_filters['query'][] = $arr_path_filter;
						}
					}
					
					if ($this->arr_filter_type_filters) {
						
						$arr_filter = $this->collectFilters($arr_type_connections);
						
						$arr_collect_filters['all'] = array_merge($arr_collect_filters['all'], $arr_filter);
						$arr_collect_filters['query'] = array_merge($arr_collect_filters['query'], $arr_filter);
					}

					$arr = array_merge($arr, $arr_collect_filters['all']);
					
					foreach (($this->arr_limit_type_filters[$target_type_id] ?? []) as $arr_limit_type_filter) {
						
						$arr_collect_filters['all'][] = $arr_limit_type_filter['filter'];
						if ($arr_limit_type_filter['filter_object_subs']) {
							$arr_collect_filters['filter'][] = $arr_limit_type_filter['filter'];
						} else {
							$arr_collect_filters['query'][] = $arr_limit_type_filter['filter'];
						}
					}
					
					$this->arr_type_filters[$path_new] = $arr_collect_filters['all'];
					
					if ($this->conditions !== null && $this->func_conditions) {
						
						if (!isset($this->arr_type_conditions[$target_type_id])) {
							
							$function = $this->func_conditions;
							$arr_conditions = $function($target_type_id);
													
							$this->arr_type_conditions[$target_type_id] = $arr_conditions;
						}
					}
					
					if ($filter) {

						if ($arr_selection_required) {
							
							$this->arr_path_selection_required[$path_new] = $arr_selection_required;
							$arr_selection = array_replace_recursive($arr_selection_required, $arr_selection);
						}
						
						$arr_filtering = ($this->arr_path_options[$path_new]['arr_filtering'] ?? ($this->arr_type_options[$target_type_id]['arr_filtering'] ?? []));
						$arr_filtering_enable = (array_filter(arrValuesRecursive('arr_filtering', array_merge($this->arr_path_options, $this->arr_type_options))) ? ['all' => true] : []);
						
						if ($in_out == 'start') {
							
							$filter_depth = new FilterTypeObjects($target_type_id, $this->view, false, false);
							$filter_depth->setScope($this->arr_scope);
							$filter_depth->setFilter($arr_source_filters);
							
							$this->arr_depth_filters = $filter_depth->getDepth(true);
							
							$arr_source_filters = $this->arr_depth_filters[$target_type_id]; // Also use the depth filter for the source filter to make sure it's filtering safe
							$arr_source_filters = (array)$arr_source_filters['arr_filters'];
							
							$arr_filtering_filters = [];
							
							if ($arr_filtering) {
								
								foreach ($arr_source_filters as $arr_source_filter) {
									
									if ($arr_source_filter['object_filter']) { // Select the object filter for filtering purposes

										$arr_filtering_filters = $arr_source_filter['object_filter'];
										
										if ($arr_source_filter['object_versioning']) { // Add the versioning section to the general query
											
											$arr_collect_filters['query'][] = ['object_versioning' => $arr_source_filter['object_versioning']];
										}
									} else {
										
										$arr_collect_filters['query'][] = $arr_source_filter;
									}
								}
							} else {
								
								// Apply the filter as a stand-alone filter when it's not used for filtering
								$arr_collect_filters['query'] = array_merge($arr_source_filters, $arr_collect_filters['query']);
								
								if ($arr_filtering_enable) { // Do use the filter for filtering purposes
									$arr_filtering_filters = true;
								}
							}
						} else {
							
							$arr_filtering_filters = [];
							
							if ($arr_filtering_enable) {
								
								$arr_depth_filter = $this->arr_depth_filters;
								foreach ($arr_type_object_connections['path'] as $cur_type_id) {
									$arr_depth_filter =& $arr_depth_filter[$cur_type_id];
								}
								
								$arr_depth_filter = ($arr_depth_filter[$target_type_id]['arr_filters'] ?? []);

								foreach ($arr_depth_filter as $arr_filter) {
								
									if (!$arr_filter['object_filter']) { // Also need a filter form at the beginning
										continue;
									}
									
									foreach ($arr_filter['object_filter'] as $value) { // Convert seperate filter collections to OR
										$arr_filtering_filters[] = $value;
									}
								}
							}
						}
						
						$filter->setScope($this->arr_scope, $path);
						$filter->setSelection($arr_selection);
						$filter->setFiltering($arr_filtering, $arr_filtering_enable, $arr_filtering_filters, ($filter_source ? $filter_source->storeResultTemporarily() : false));
						
						if ($arr_collect_filters['filter']) {
							$filter->setFilter($arr_collect_filters['filter'], true);
						}
						if ($arr_collect_filters['query']) {
							$filter->setFilter($arr_collect_filters['query']);
						}
						
						$limit = ($this->arr_path_options[$path_new]['limit'] ?? null ?: ($this->arr_type_options[$target_type_id]['limit'] ?? null ?: false));
						
						if ($limit) {
							$filter->setLimit($limit);
						}
						
						$order = ($this->arr_path_options[$path_new]['order'] ?? null ?: ($this->arr_type_options[$target_type_id]['order'] ?? null ?: false));
						
						if ($order) {
							$filter->setOrder($order);
						}
						
						if ($this->conditions !== null) {
							
							if ($this->func_conditions) {
								
								$filter->setConditions($this->conditions, $this->arr_type_conditions[$target_type_id]);
							} else {
								
								$filter->setConditions($this->conditions);
							}
						}
						
						if ($this->func_generate !== null) {
							
							$function = $this->func_generate;
							$function($filter, $target_type_id);
						}
						
						if ($in_out == 'start' && $this->nr_limit) {
							$filter->setInitLimit($this->nr_limit);
						}
						
						$table_name = $filter->storeResultTemporarily(uniqid(), true);
						
						$arr_objects = $filter->init();
						
						$this->arr_path_objects[$path_new][$in_out] += $arr_objects;
						
						$arr_result = $filter->getProcessedResultInfo();
						$this->arr_types_found = array_merge_recursive($this->arr_types_found, $arr_result['types_found']);
					}
					
					if (isset($arr_type_connections['connections'][$target_type_id])) {
						$this->collectObjects($arr_type_connections['connections'][$target_type_id], $arr_collect_filters['all'], $filter, $do_collect);
					}
				}
			}
		}
		
		return $arr;
	}
	
	private function collectFilters($arr_type_connections) {
		
		$arr = [];

		foreach (['start' => ($arr_type_connections['start'] ?? []), 'in' => ($arr_type_connections['in'] ?? []), 'out' => ($arr_type_connections['out'] ?? [])] as $in_out => $arr_in_out) {
			foreach ($arr_in_out as $target_type_id => $arr_type_object_connections) {
				
				$arr_source_filters = $this->collectFilters($arr_type_connections['connections'][$target_type_id]);
				
				foreach (($this->arr_filter_type_filters[$target_type_id] ?? []) as $arr_filter) {
					$arr_source_filters[] = $arr_filter;
				}
				
				$path = implode('-', $arr_type_object_connections['path']);
				if ($in_out == 'start') {
					$arr_filters = $arr_source_filters;
				} else {
					$arr_filters = [];
					$path = $path.'-'.$target_type_id;
				}
				
				$arr_filter = [];
				
				foreach (($arr_type_object_connections['object_descriptions'] ?? []) as $object_description_id => $arr_connection) {
					
					if ($in_out == 'out') {
						
						if ($arr_connection['dynamic']) {
							
							$arr_filter['object_filter'][]['object_definitions'][$object_description_id]['type_tags'][$arr_connection['ref_type_id']]['objects'][] = $arr_source_filters;
						} else {
							
							$arr_filter['object_filter'][]['object_definitions'][$object_description_id][] = $arr_source_filters;
						}
					} else {
						
						$arr_filter['object_filter'][]['referenced_types'][$arr_connection['type_id']]['object_definitions'][$object_description_id][] = $arr_source_filters;
					}
				}						
				foreach (($arr_type_object_connections['object_sub_details'] ?? []) as $object_sub_details_id => $arr_object_sub_connections) {
												
					foreach ($arr_object_sub_connections as $object_sub_connection_id => $arr_connection) {
						
						if ($object_sub_connection_id === 'object_sub_location') {
							
							if ($in_out == 'out') {
								
								$arr_source_filters['mode'] = 'self'; // Use Object's own reference, not the resolved reference (default)
								$arr_filter['object_filter'][]['object_subs'][$object_sub_details_id]['object_sub_locations'][] = ['object_sub_location_ref_type_id' => $arr_connection['ref_type_id'], 'object_sub_location_reference' => $arr_source_filters];
							} else {
								
								$arr_filter['object_filter'][]['referenced_types'][$arr_connection['type_id']]['object_subs'][$object_sub_details_id]['object_sub_location_reference'][] = $arr_source_filters;
							}
						} else {
							
							if ($in_out == 'out') {
								
								if ($arr_connection['dynamic']) {
									
									$arr_filter['object_filter'][]['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_connection_id]['type_tags'][$arr_connection['ref_type_id']]['objects'][] = $arr_source_filters;
								} else {
									
									$arr_filter['object_filter'][]['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_connection_id][] = $arr_source_filters;
								}
							} else {
								
								$arr_filter['object_filter'][]['referenced_types'][$arr_connection['type_id']]['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_connection_id][] = $arr_source_filters;
							}
						}
					}
				}
				if ($arr_filter) {
					$arr_filters[] = $arr_filter;
				}
				
				$arr = array_merge($arr, $arr_filters);
			}
		}
		
		return $arr;
	}
		
	public function addLimitTypeFilters($type_id, $arr_filter, $filter_object_subs = false) {
		
		$this->arr_limit_type_filters[$type_id][] = ['filter' => $arr_filter, 'filter_object_subs' => $filter_object_subs];
	}
	
	public function getLimitTypeFilters($type_id) {
		
		return $this->arr_limit_type_filters[$type_id];
	}
	
	public function addFilterTypeFilters($type_id, $arr_filter) { // Filter the collection upward (prefiltering) instead of downward
		
		$this->arr_filter_type_filters[$type_id][] = $arr_filter;
	}
		
	public function setTypeOptions($arr_type_options) {
		
		foreach ($arr_type_options as $type_id => $value) {
		
			$this->arr_type_options[$type_id] = array_merge((array)$this->arr_type_options[$type_id], $value);
		}
	}

	public function setPathOptions($arr_path_options) {
		
		foreach ($arr_path_options as $path => $value) {
		
			$this->arr_path_options[$path] = array_merge((array)$this->arr_path_options[$path], $value);
		}
	}
	
	public function getPathOptions($path = false) {
		
		if ($path) {
			return $this->arr_path_options[$path];
		} else {
			return $this->arr_path_options;
		}
	}
	
	public function setScope($arr_scope) {
		
		$this->arr_scope = $arr_scope;
	}
	
	public function getScope() {
		
		return $this->arr_scope;
	}
	
	public function setConditions($conditions, $function = false) {
		
		$this->conditions = $conditions;
		
		$this->func_conditions = $function;
	}
	
	public function setGenerateCallback($function) { // Apply any other stuff to a collecting GenerateTypeObjects
		
		$this->func_generate = $function;
	}
}
