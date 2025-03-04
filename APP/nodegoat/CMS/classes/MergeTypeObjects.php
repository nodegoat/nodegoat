<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class MergeTypeObjects extends StoreTypeObjects {
	
	private $is_master = true;
	private $arr_master_object_set = [];
	private $arr_merge_object_ids = [];
	
	private $arr_storage_objects = [];
	private $arr_merge_object_sets = [];
	
	private $arr_merge_object_sub_ids = [];
	
	private $table_name_referenced_objects = '';

    public function __construct($type_id, $object_id, $arr_merge_object_ids, $user_id) {
		
		parent::__construct($type_id, $object_id, $user_id);
		
		$this->is_master = ($this->object_id ? true : false);
		
		$this->arr_merge_object_ids = $arr_merge_object_ids;
		
		foreach ($this->arr_merge_object_ids as $key => $merge_object_id) {
			
			if ($this->object_id && $this->object_id == $merge_object_id) {
				
				unset($this->arr_merge_object_ids[$key]);
				continue;
			}
						
			$this->arr_storage_objects[$merge_object_id] = new StoreTypeObjects($this->type_id, $merge_object_id, $this->user_id, 'merge_'.$merge_object_id);
		}
		
		$arr_append = [
			'sources' => true
		];
		foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			$arr_append['object_subs'][$object_sub_details_id] = true;
		}
		
		$this->is_trusted = true;
		
		$this->setAppend($arr_append);
    }
    	
	public function merge() {
		
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_STORAGE);
		$filter->setFilter(['objects' => $this->arr_merge_object_ids]);
		$filter->setOrder(['date' => 'asc']); // Store last version last
		
		$this->arr_merge_object_sets = $filter->init();
		
		// Preserve object order
		$this->arr_merge_object_ids = array_keys($this->arr_merge_object_sets);
		$arr_storage_objects = [];
		foreach ($this->arr_merge_object_ids as $merge_object_id) {
			$arr_storage_objects[$merge_object_id] = $this->arr_storage_objects[$merge_object_id];
		}
		$this->arr_storage_objects = $arr_storage_objects;
		
		// If master, use this set to track changes
		if ($this->is_master) {
			
			$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_STORAGE);
			$filter->setVersioning();
			$filter->setFilter(['objects' => $this->object_id]);

			$this->arr_master_object_set = current($filter->init());
		}
		
		DB::startTransaction('merge_type_objects');

		$this->generateBody(); // Create a new working body based on all versions
		$this->save();
		$this->discard(true); // Prepare object to be used/compared and auto-accept due to otherwise same version dates at version evaluation

		foreach ($this->arr_merge_object_sets as $merge_object_id => $arr_merge_object_set) {
			
			if ($this->is_master && $this->arr_master_object_set['object']['object_name_plain']) { // Do not store: existing master values
				
				unset($arr_merge_object_set['object']['object_name_plain']);
			}
			
			foreach ($arr_merge_object_set['object_definitions'] as $object_description_id => $arr_object_definition) {
				
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];

				if ($arr_object_description['object_description_is_unique']
					||
					($this->is_master && !$this->arr_append['object_definitions'][$object_description_id] && ($this->arr_master_object_set['object_definitions'][$object_description_id]['object_definition_ref_object_id'] || $this->arr_master_object_set['object_definitions'][$object_description_id]['object_definition_value']))
					||
					(!($arr_object_definition['object_definition_ref_object_id'] || $arr_object_definition['object_definition_value']) && !$this->arr_storage_objects[$merge_object_id]->getTypeObjectDescriptionVersions($object_description_id))
				) { // Do not store: unique / existing master values / undefined with no versions
					
					unset($arr_merge_object_set['object_definitions'][$object_description_id]);
				}
			}
			
			foreach ($arr_merge_object_set['object_subs'] as $cur_object_sub_id => $arr_object_sub) {
				
				$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
				$arr_object_sub_details = $this->arr_type_set['object_sub_details'][$object_sub_details_id];
				
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
					continue;
				}
				
				$object_sub_id = $this->arr_append_object_sub_details_ids[$object_sub_details_id];
				$arr_master_object_sub = $this->arr_master_object_set['object_subs'][$object_sub_id]['object_sub'];
				
				if ($this->is_master && ($arr_master_object_sub['object_sub_date_chronology'] || $arr_master_object_sub['object_sub_location_ref_object_id'] || $arr_master_object_sub['object_sub_location_geometry'])) { // Do not store: existing master values
					
					$arr_merge_object_set['object_subs'][$cur_object_sub_id]['object_sub'] = ['object_sub_id' => $cur_object_sub_id, 'object_sub_details_id' => $object_sub_details_id];
				}
				
				if (!$arr_object_sub['object_sub_definitions']) {
					continue;
				}
				
				foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_sub_object_definition) {
				
					$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];

					if (($this->is_master && !$this->arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id] && ($this->arr_master_object_set['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id'] || $this->arr_master_object_set['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value']))
						||
						(!($arr_sub_object_definition['object_sub_definition_ref_object_id'] || $arr_sub_object_definition['object_sub_definition_value']) && !$this->arr_storage_objects[$merge_object_id]->getTypeObjectSubDescriptionVersions($object_sub_details_id, $cur_object_sub_id, $object_sub_description_id))
					) { // Do not store: existing master values / undefined with no versions
						
						unset($arr_merge_object_set['object_subs'][$cur_object_sub_id]['object_sub_definitions'][$object_sub_description_id]);
					}
				}
			}
			
			$this->store($arr_merge_object_set['object'], $arr_merge_object_set['object_definitions'], $arr_merge_object_set['object_subs']);
			$this->save();
			$this->commit(true); // Prepare object to be used/compared and auto-accept due to otherwise same version dates at version evaluation
		}
		
		$this->mergeDiscussions();
		$this->linkReferencedObjects();
		
		DB::commitTransaction('merge_type_objects');
	}
	
	protected function generateBody() {
				
		if (!$this->is_master) { // Create new object
		
			$this->addTypeObjectVersion(1);
			$this->addTypeObjectVersionUser(-1); // User that merges objects is owner of the new merged object
			
			// Bring new object into existence
			$this->save();
			$this->addTypeObjectUpdate();
			$this->commit(true);
			
			$hash = value2Hash('');
			$arr_collect_versions = [$hash => 1];
			$cur_version = 1;
		} else {
			
			$arr_collect_versions = [];
			$cur_version = 0;
		}

		if ($this->is_master) {
			
			foreach ($this->getTypeObjectVersions() as $arr_version) {
				
				$value = $arr_version['object_name_plain'];
				
				$hash = value2Hash(($value ?? ''));

				$version = $arr_version['object_version'];
					
				$arr_collect_versions[$hash] = $version;
				$cur_version = ($version > $cur_version ? $version : $cur_version);
			}
		}
					
		foreach ($this->arr_storage_objects as $merge_object_id => $storage) {
			
			$arr_version_users = $storage->getTypeObjectVersionsUsers();
								
			foreach (array_reverse($storage->getTypeObjectVersions(), true) as $arr_version) {
				
				$value = $arr_version['object_name_plain'];
				
				$hash = value2Hash(($value ?? ''));
				
				if (!$arr_collect_versions[$hash]) {
					
					$cur_version++;
					$version = $cur_version;

					$this->addTypeObjectVersion($version, $value);
					
					$arr_collect_versions[$hash] = $version;
				}
				
				foreach ((array)$arr_version_users[$arr_version['object_version']] as $arr_version_user) {
					
					$this->addTypeObjectVersionUser($arr_collect_versions[$hash], $arr_version_user['id'], $arr_version_user['date'], $arr_version_user['system_object_id']);
				}
			}
		}

		foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$arr_collect_versions = [];
			$cur_version = 0;
			
			if ($this->is_master) {
				
				foreach ($this->getTypeObjectDescriptionVersions($object_description_id) as $arr_version) {
					
					if ($arr_object_description['object_description_ref_type_id']) {
						$value = (!$arr_object_description['object_description_has_multi'] ? [$arr_version['object_definition_ref_object_id']] : $arr_version['object_definition_ref_object_id']);
					} else {
						$value = $arr_version['object_definition_value'];
					}
					
					if (!$value) {
						continue;
					}
					
					$hash = value2Hash($value);

					$version = $arr_version['object_definition_version'];
						
					$arr_collect_versions[$hash] = $version;
					$cur_version = ($version > $cur_version ? $version : $cur_version);
				}
			}
				
			foreach ($this->arr_storage_objects as $merge_object_id => $storage) {
				
				$arr_version_users = $storage->getTypeObjectDescriptionVersionsUsers($object_description_id);
									
				foreach (array_reverse($storage->getTypeObjectDescriptionVersions($object_description_id), true) as $arr_version) {
					
					if ($arr_object_description['object_description_ref_type_id']) {
						$value = (!$arr_object_description['object_description_has_multi'] ? [$arr_version['object_definition_ref_object_id']] : $arr_version['object_definition_ref_object_id']);
					} else {
						$value = $arr_version['object_definition_value'];
					}
					
					if (!$value) {
						continue;
					}
					
					$hash = value2Hash($value);
					
					if (!$arr_collect_versions[$hash]) {
						
						$cur_version++;
						$version = $cur_version;

						$this->addTypeObjectDescriptionVersion($object_description_id, $version, $value);
						
						$arr_collect_versions[$hash] = $version;
					}
					
					foreach ((array)$arr_version_users[$arr_version['object_definition_version']] as $arr_version_user) {
						
						$this->addTypeObjectDescriptionVersionUser($object_description_id, $arr_collect_versions[$hash], $arr_version_user['id'], $arr_version_user['date'], $arr_version_user['system_object_id']);
					}
				}
			}
		}
				
		foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			$arr_collect_versions_sub = [];
			$cur_version_sub = 0;
			$arr_collect_versions_sub_date = [];
			$cur_version_sub_date = 0;
			$arr_collect_versions_sub_location_geometry = [];
			$cur_version_sub_location_geometry = 0;
			$arr_collect_versions_sub_descriptions = [];
			$arr_cur_version_sub_descriptions = [];
			
			if ($this->is_master && $arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
				
				foreach ($this->arr_master_object_set['object_subs'] as $object_sub_id => $arr_master_object_sub) {
					
					if ($arr_master_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
						continue;
					}

					foreach ($this->getTypeObjectSubVersions($object_sub_id) as $arr_version) {
						
						// Prepare values that need processing when storing internal values
						$arr_version['object_sub_date_chronology'] = FormatTypeObjects::formatToSQLValue('chronology', $arr_version['object_sub_date_chronology']);
						
						$arr_value = [
							'object_sub_date_chronology' => $arr_version['object_sub_date_chronology'],
							'object_sub_location_geometry' => $arr_version['object_sub_location_geometry'],
							'object_sub_location_ref_object_id' => $arr_version['object_sub_location_ref_object_id'],
							'object_sub_location_ref_type_id' => $arr_version['object_sub_location_ref_type_id'],
							'object_sub_location_ref_object_sub_details_id' => $arr_version['object_sub_location_ref_object_sub_details_id']
						];
						
						$hash = value2Hash($arr_value);

						$version = $arr_version['object_sub_version'];
							
						$arr_collect_versions_sub[$hash] = $version;
						$cur_version_sub = ($version > $cur_version_sub ? $version : $cur_version_sub);
						
						if ($arr_version['object_sub_date_version']) {
							
							$version_sub_date = $arr_version['object_sub_date_version'];
							
							$hash_chronology = value2Hash($arr_version['object_sub_date_chronology']);

							$arr_collect_versions_sub_date[$hash_chronology] = $version_sub_date;
							$cur_version_sub_date = ($version_sub_date > $cur_version_sub_date ? $version_sub_date : $cur_version_sub_date);
						}
						
						if ($arr_version['object_sub_location_geometry_version']) {
							
							$version_sub_location_geometry = $arr_version['object_sub_location_geometry_version'];
							
							$hash_geometry = value2Hash($arr_version['object_sub_location_geometry']);

							$arr_collect_versions_sub_location_geometry[$hash_geometry] = $version_sub_location_geometry;
							$cur_version_sub_location_geometry = ($version_sub_location_geometry > $cur_version_sub_location_geometry ? $version_sub_location_geometry : $cur_version_sub_location_geometry);
						}

						$this->arr_append_object_sub_details_ids[$object_sub_details_id] = $object_sub_id;
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
							continue;
						}
																									
						foreach ($this->getTypeObjectSubDescriptionVersions($object_sub_details_id, $object_sub_id, $object_sub_description_id) as $arr_version) {
							
							if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
								$value = $arr_version['object_sub_definition_ref_object_id'];
							} else {
								$value = $arr_version['object_sub_definition_value'];
							}
							
							if (!$value) {
								continue;
							}
							
							$hash = value2Hash($value);

							$version = $arr_version['object_sub_definition_version'];

							$arr_collect_versions_sub_descriptions[$object_sub_description_id][$hash] = $version;
							$arr_cur_version_sub_descriptions[$object_sub_description_id] = ($version > $arr_cur_version_sub_descriptions[$object_sub_description_id] ? $version : $arr_cur_version_sub_descriptions[$object_sub_description_id]);
						}
					}
				}
			}
				
			foreach ($this->arr_storage_objects as $merge_object_id => $storage) {
				
				foreach ($this->arr_merge_object_sets[$merge_object_id]['object_subs'] as $cur_object_sub_id => $arr_cur_object_sub) {
					
					if ($arr_cur_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
						continue;
					}
					
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
						
						$object_sub_id = $this->arr_append_object_sub_details_ids[$object_sub_details_id];
						
					} else { // Every non-unique subobject starts their own collection
						
						$object_sub_id = false;
						
						$arr_collect_versions_sub = [];
						$cur_version_sub = 0;
						$arr_collect_versions_sub_date = [];
						$cur_version_sub_date = 0;
						$arr_collect_versions_sub_location_geometry = [];
						$cur_version_sub_location_geometry = 0;
						$arr_collect_versions_sub_descriptions = [];
						$arr_cur_version_sub_descriptions = [];
					}

					$arr_version_users = $storage->getTypeObjectSubVersionsUsers($cur_object_sub_id);
										
					foreach (array_reverse($storage->getTypeObjectSubVersions($cur_object_sub_id), true) as $arr_version) {
						
						// Prepare values that need processing when storing internal values
						$arr_version['object_sub_date_chronology'] = FormatTypeObjects::formatToSQLValue('chronology', $arr_version['object_sub_date_chronology']);
						
						$arr_value = [
							'object_sub_date_chronology' => $arr_version['object_sub_date_chronology'],
							'object_sub_location_geometry' => $arr_version['object_sub_location_geometry'],
							'object_sub_location_ref_object_id' => $arr_version['object_sub_location_ref_object_id'],
							'object_sub_location_ref_type_id' => $arr_version['object_sub_location_ref_type_id'],
							'object_sub_location_ref_object_sub_details_id' => $arr_version['object_sub_location_ref_object_sub_details_id']
						];
						
						$hash = value2Hash($arr_value);
						
						if (!$arr_collect_versions_sub[$hash]) {
							
							$cur_version_sub++;
							$version = $cur_version_sub;
							
							if ($arr_version['object_sub_date_version']) {
								
								$hash_chronology = value2Hash($arr_version['object_sub_date_chronology']);
								
								if ($arr_collect_versions_sub_date[$hash_chronology]) {
									
									$arr_value['object_sub_date_chronology'] = false; // Do not store chronology itself; use the version
									$arr_value['object_sub_date_version'] = $arr_collect_versions_sub_date[$hash_chronology];
								} else {
									
									$cur_version_sub_date++;
									$arr_collect_versions_sub_date[$hash_chronology] = $cur_version_sub_date;
									$arr_value['object_sub_date_version'] = $cur_version_sub_date;
								}
							}
														
							if ($arr_version['object_sub_location_geometry_version']) {
								
								$hash_geometry = value2Hash($arr_version['object_sub_location_geometry']);
								
								if ($arr_collect_versions_sub_location_geometry[$hash_geometry]) {
									
									$arr_value['object_sub_location_geometry'] = false; // Do not store geometry itself; use the version
									$arr_value['object_sub_location_geometry_version'] = $arr_collect_versions_sub_location_geometry[$hash_geometry];
								} else {
									
									$cur_version_sub_location_geometry++;
									$arr_collect_versions_sub_location_geometry[$hash_geometry] = $cur_version_sub_location_geometry;
									$arr_value['object_sub_location_geometry_version'] = $cur_version_sub_location_geometry;
								}
							}
							
							$object_sub_id = $this->addTypeObjectSubVersion($object_sub_id, $object_sub_details_id, $version, $arr_value);
														
							if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
								
								$this->arr_append_object_sub_details_ids[$object_sub_details_id] = $object_sub_id;
							} else {
								
								$this->arr_append_object_sub_ids[$cur_object_sub_id] = $object_sub_id;
							}
							
							$this->arr_merge_object_sub_ids[$cur_object_sub_id] = $object_sub_id;
							
							$arr_collect_versions_sub[$hash] = $version;
						}
						
						foreach ((array)$arr_version_users[$arr_version['object_sub_version']] as $arr_version_user) {
							
							$this->addTypeObjectSubVersionUser($object_sub_id, $arr_collect_versions_sub[$hash], $arr_version_user['id'], $arr_version_user['date'], $arr_version_user['system_object_id']);
						}
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
							continue;
						}
														
						$arr_version_users = $storage->getTypeObjectSubDescriptionVersionsUsers($cur_object_sub_id, $object_sub_description_id);
											
						foreach (array_reverse($storage->getTypeObjectSubDescriptionVersions($object_sub_details_id, $cur_object_sub_id, $object_sub_description_id), true) as $arr_version) {

							if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
								$value = $arr_version['object_sub_definition_ref_object_id'];
							} else {
								$value = $arr_version['object_sub_definition_value'];
							}
							
							if (!$value) {
								continue;
							}
							
							$hash = value2Hash($value);
							
							if (!$arr_collect_versions_sub_descriptions[$object_sub_description_id][$hash]) {
								
								$arr_cur_version_sub_descriptions[$object_sub_description_id]++;
								$version = $arr_cur_version_sub_descriptions[$object_sub_description_id];

								$this->addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, $version, $value);
								
								$arr_collect_versions_sub_descriptions[$object_sub_description_id][$hash] = $version;
							}
							
							foreach ((array)$arr_version_users[$arr_version['object_definition_version']] as $arr_version_user) {
								
								$this->addTypeObjectSubDescriptionVersionUser($object_sub_id, $object_sub_description_id, $arr_collect_versions_sub_descriptions[$object_sub_description_id][$hash], $arr_version_user['id'], $arr_version_user['date'], $arr_version_user['system_object_id']);
							}
						}
					}
				}
			}
		}
	}
	
	protected function linkReferencedObjects() {
		
		if (!$this->object_id) {
			error(getLabel('msg_missing_information'));
		}
				
		$arr_str_replace = $arr_str_replace_old = [];
		foreach ($this->arr_merge_object_ids as $merge_object_id) {
			$arr_str_replace[] = '_'.$merge_object_id.'_';
			$arr_str_replace_old[] = '_'.$merge_object_id.']';
		}
		
		$arr_type_object_values = [];
		$arr_type_object_select = [];
				
		// Object sources
		
		$arr_referenced_objects = $this->getReferencedTypeObjects();
		
		foreach ($arr_referenced_objects as $arr) {
			
			if ($arr_type_object_select[$arr['id']]['object'] === null) {
				$arr_type_object_select[$arr['id']]['object'] = false;
			}
			
			if ($arr_type_object_select[$arr['id']]['object_descriptions'] === null) {
				$arr_type_object_select[$arr['id']]['object_descriptions'] = [];
			}
			
			if ($arr_type_object_select[$arr['id']]['object_sub_details'] === null) {
				$arr_type_object_select[$arr['id']]['object_sub_details'] = [];
			}
			
			if ($arr['object_sub_details_id']) {
				
				$s_arr =& $arr_type_object_select[$arr['id']]['object_sub_details'][$arr['object_sub_details_id']]['object_sub_descriptions']; // Do not select sub-object descriptions when they're not needed
				
				if ($s_arr === null) {
					$s_arr = [];
				}
				unset($s_arr);
			}
			
			switch ($arr['type']) {
				
				case 'object_sources':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_sources'] = true;
					$arr_type_object_select[$arr['id']]['object'] = true;
					break;
				case 'object_definition':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_definitions'][$arr['object_description_id']]['object_definition'] = true;
					$arr_type_object_select[$arr['id']]['object_descriptions'][$arr['object_description_id']] = $arr['object_description_id'];
					break;
				case 'object_definition_sources':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_definitions'][$arr['object_description_id']]['object_definition_sources'] = true;
					$arr_type_object_select[$arr['id']]['object_descriptions'][$arr['object_description_id']] = $arr['object_description_id'];
					break;
				case 'object_sub_date_chronology_object_sub':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_date_chronology_object_sub'] = true;
					break;
				case 'object_sub_date_chronology_cycle':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_date_chronology_cycle'] = true;
					break;
				case 'object_sub_date_span_cycle';
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_date_span_cycle'] = true;
					break;
				case 'object_sub_location':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_location'] = true;
					break;
				case 'object_sub_sources':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_sources'] = true;
					break;
				case 'object_sub_definition':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_definitions'][$arr['object_sub_description_id']]['object_sub_definition'] = true;
					$arr_type_object_select[$arr['id']]['object_sub_details'][$arr['object_sub_details_id']]['object_sub_descriptions'][$arr['object_sub_description_id']] = $arr['object_sub_description_id'];
					break;
				case 'object_sub_definition_sources':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_definitions'][$arr['object_sub_description_id']]['object_sub_definition_sources'] = true;
					$arr_type_object_select[$arr['id']]['object_sub_details'][$arr['object_sub_details_id']]['object_sub_descriptions'][$arr['object_sub_description_id']] = $arr['object_sub_description_id'];
					break;
			}
		}
				
		$func_parse_references = function($type, $arr_referencing, $has_multi = false) { // Works for both sources and dynamic references
			
			if ($has_multi) {
								
				foreach ($arr_referencing as $key => &$arr_referencing_group) {
					
					if (!isset($arr_referencing_group[$this->type_id])) {
						continue;
					}
				
					foreach ($arr_referencing_group[$this->type_id] as &$arr_reference) {
						
						if (!in_array($arr_reference[$type.'_ref_object_id'], $this->arr_merge_object_ids)) {
							continue;
						}
						
						$arr_reference[$type.'_ref_object_id'] = $this->object_id;
					}
				}
			} else {
				
				foreach ($arr_referencing[$this->type_id] as &$arr_reference) {
					
					if (!in_array($arr_reference[$type.'_ref_object_id'], $this->arr_merge_object_ids)) {
						continue;
					}
					
					$arr_reference[$type.'_ref_object_id'] = $this->object_id;
				}
			}
			
			return $arr_referencing;
		};
		
		foreach ($arr_type_object_values as $type_id => $arr_object_values) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			$storage = new StoreTypeObjects($type_id, false, $this->user_id);
			$storage->setMode(StoreTypeObjects::MODE_UPDATE, false); // Do not check Objects, only update them
			$stored = false;
			
			foreach ($arr_object_values as $object_id => $arr_values) {
				
				$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_STORAGE);
				$filter->setFilter(['objects' => $object_id, 'object_subs' => ($arr_values['object_subs'] ? array_keys($arr_values['object_subs']) : false)], true);
				$filter->setSelection([
						'object' => $arr_type_object_select[$object_id]['object'],
						'object_descriptions' => $arr_type_object_select[$object_id]['object_descriptions'],
						'object_sub_details' => $arr_type_object_select[$object_id]['object_sub_details']
					]
				);
				$filter->setVersioning();
				
				$arr_objects = $filter->init();
				$arr_object = $arr_objects[$object_id];
				
				if (!$arr_object) {
					continue;
				}
				
				$storage->setObjectID($object_id);
				
				$arr_object_store = [
					'object' => [],
					'object_definitions' => [],
					'object_subs' => []
				];
				
				if ($arr_values['object_sources']) {
					
					$value = $func_parse_references('object_source', $arr_object['object']['object_sources']);

					$arr_object_store['object']['object_sources'] = $value;
				}
				
				foreach ((array)$arr_values['object_definitions'] as $object_description_id => $arr_object_definition) {
					
					$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
					$arr_object_store['object_definitions'][$object_description_id]['object_description_id'] = $object_description_id;
					
					if ($arr_object_definition['object_definition']) {
						
						if ($arr_object_description['object_description_ref_type_id']) {
							
							if ($arr_object_description['object_description_is_dynamic']) { // Not necessarily updated in storage, i.e. Processes, Reversal
									
								$value = $func_parse_references('object_definition', $arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'], $arr_object_description['object_description_has_multi']);
							} else if ($arr_object_description['object_description_has_multi']) {
								
								$value = $arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'];
								$value = FormatTypeObjects::formatToSQLValue($arr_object_description['object_description_value_type'], $value);
								$value = array_diff($value, $this->arr_merge_object_ids);
								$value[] = $this->object_id;
							} else {
								
								$value = $this->object_id;
							}
							
							$arr_object_store['object_definitions'][$object_description_id]['object_definition_ref_object_id'] = $value;
						} else {
							
							$value = $arr_object['object_definitions'][$object_description_id]['object_definition_value'];
							$value = str_replace($arr_str_replace, '_'.$this->object_id.'_', $value);
							$value = str_replace($arr_str_replace_old, '_'.$this->object_id.']', $value);
							
							$arr_object_store['object_definitions'][$object_description_id]['object_definition_value'] = $value;
						}
					}
					
					if ($arr_object_definition['object_definition_sources']) {
						
						$value = $func_parse_references('object_definition_source', $arr_object['object_definitions'][$object_description_id]['object_definition_sources']);

						$arr_object_store['object_definitions'][$object_description_id]['object_definition_sources'] = $value;
					}
				}
				
				foreach ((array)$arr_values['object_subs'] as $object_sub_id => $arr_object_sub) {
					
					$object_sub_details_id = $arr_object['object_subs'][$object_sub_id]['object_sub']['object_sub_details_id'];
					
					$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_id'] = $object_sub_id;
					$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_details_id'] = $object_sub_details_id;
					
					if ($arr_object_sub['object_sub_date_chronology_object_sub'] || $arr_object_sub['object_sub_date_chronology_cycle'] || $arr_object_sub['object_sub_date_span_cycle']) {

						$arr_chronology = $arr_object['object_subs'][$object_sub_id]['object_sub']['object_sub_date_chronology'];
						$arr_chronology = FormatTypeObjects::formatToChronology($arr_chronology);
						
						if ($arr_object_sub['object_sub_date_chronology_object_sub']) {
								
							$func_sql_statement = function(&$arr) {
								
								if (!$arr || !$arr['date_object_sub_id']) {
									return;
								}

								$merge_object_sub_id = $this->arr_merge_object_sub_ids[$arr['date_object_sub_id']];
								
								if ($merge_object_sub_id) {
									$arr['date_object_sub_id'] = $merge_object_sub_id;
								}
							};

							$func_sql_statement($arr_chronology['start']['start']);
							$func_sql_statement($arr_chronology['start']['end']);
							$func_sql_statement($arr_chronology['end']['start']);
							$func_sql_statement($arr_chronology['end']['end']);
						}
						
						if ($arr_object_sub['object_sub_date_chronology_cycle']) {
								
							$func_sql_statement = function(&$arr) {
								
								if (!$arr || !$arr['cycle_object_id']) {
									return;
								}

								$do_merge = in_array($arr['cycle_object_id'], $this->arr_merge_object_ids);
								
								if ($do_merge) {
									$arr['cycle_object_id'] = $this->object_id;
								}
							};
								
							$func_sql_statement($arr_chronology['start']['start']);
							$func_sql_statement($arr_chronology['start']['end']);
							$func_sql_statement($arr_chronology['end']['start']);
							$func_sql_statement($arr_chronology['end']['end']);
						}
						
						if ($arr_object_sub['object_sub_date_span_cycle']) {
							
							$arr_chronology['span']['cycle_object_id'] = $this->object_id;
						}

						$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_date_chronology'] = $arr_chronology;
					}
					
					if ($arr_object_sub['object_sub_location']) {
						
						$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_location_ref_type_id'] = $arr_object['object_subs'][$object_sub_id]['object_sub']['object_sub_location_ref_type_id'];
						$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_location_ref_object_sub_details_id'] = $arr_object['object_subs'][$object_sub_id]['object_sub']['object_sub_location_ref_object_sub_details_id'];
						$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_location_ref_object_id'] = $this->object_id;
					}
					
					if ($arr_object_sub['object_sub_sources']) {
					
						$value = $func_parse_references('object_sub_source', $arr_object['object_subs'][$object_sub_id]['object_sub']['object_sub_sources']);

						$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_sources'] = $value;
					}
					
					foreach ((array)$arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
						
						$arr_object_sub_description = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
						$arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_description_id'] = $object_sub_description_id;
						
						if ($arr_object_sub_definition['object_sub_definition']) {
							
							if ($arr_object_sub_description['object_sub_description_ref_type_id']) {

								if ($arr_object_description['object_sub_description_is_dynamic']) {
									$value = $func_parse_references('object_sub_definition', $arr_object['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id']);
								} else {
									$value = $this->object_id;
								}
								
								$arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id'] = $value;
							} else {
								
								$value = $arr_object['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value'];
								$value = str_replace($arr_str_replace, '_'.$this->object_id.'_', $value);
								$value = str_replace($arr_str_replace_old, '_'.$this->object_id.']', $value);
								
								$arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value'] = $value;
							}
						}
						
						if ($arr_object_sub_definition['object_sub_definition_sources']) {
						
							$value = $func_parse_references('object_sub_definition_source', $arr_object['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_sources']);

							$arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_sources'] = $value;
						}
					}
				}

				$storage->store($arr_object_store['object'], $arr_object_store['object_definitions'], $arr_object_store['object_subs']);
				
				$stored = true;
			}
			
			if ($stored) {
				
				$storage->save();
				$storage->commit(true);
			}
		}
	}
	
	protected function mergeDiscussions() {
		
		if (!$this->object_id) {
			error(getLabel('msg_missing_information'));
		}
		
		$str_discussion = '';
		
		if ($this->is_master) {
			
			$arr_discussion = $this->getDiscussion();
			
			if ($arr_discussion['object_discussion_body']) {
				$str_discussion .= ($str_discussion ? PHP_EOL.PHP_EOL.'---'.PHP_EOL.PHP_EOL : '').trim($arr_discussion['object_discussion_body']);
			}
		}
		
		foreach ($this->arr_storage_objects as $storage) {
			
			$arr_discussion = $storage->getDiscussion();
			
			if ($arr_discussion['object_discussion_body']) {
				$str_discussion .= ($str_discussion ? PHP_EOL.PHP_EOL.'---'.PHP_EOL.PHP_EOL : '').trim($arr_discussion['object_discussion_body']);
			}
		}
		
		if ($str_discussion) {
			
			$arr_discussion = ['object_discussion_body' => $str_discussion];
			
			$this->handleDiscussion($arr_discussion);
		}
	}
	
	public function storeReferencedTypeObjects() {

		if ($this->table_name_referenced_objects) {
			return;
		}
			
		$this->table_name_referenced_objects = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_referenced_objects');
		
		$sql_merge_object_ids = implode(',', $this->arr_merge_object_ids);
		
		$versioning = 'full';
		$version_select_to = GenerateTypeObjects::generateVersioning($versioning, 'object', 'nodegoat_to');
		$version_select_tos = GenerateTypeObjects::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
		$version_select_to_def = GenerateTypeObjects::generateVersioning($versioning, 'record', 'nodegoat_to_def');
		$version_select_tos_def = GenerateTypeObjects::generateVersioning($versioning, 'record', 'nodegoat_tos_def');
		$version_select_tos_to = GenerateTypeObjects::generateVersioning($versioning, 'object_sub', 'nodegoat_tos_to');
		$arr_table_names_references = StoreType::getValueTypeTablesReferences();
		
		$sql_primary_key = 'id, object_sub_id, object_description_id, object_sub_details_id, object_sub_description_id, type';
		
		$sql = "
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$this->table_name_referenced_objects.";
			
			CREATE TEMPORARY TABLE ".$this->table_name_referenced_objects." (
				id INT,
				type_id INT,
				object_sub_id INT DEFAULT 0,
				object_description_id INT DEFAULT 0,
				object_sub_details_id INT DEFAULT 0,
				object_sub_description_id INT DEFAULT 0,
				type CHAR(40),
					PRIMARY KEY (".$sql_primary_key.")
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
			
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, type)
				SELECT nodegoat_to_src.object_id AS id, nodegoat_to.type_id, 'object_sources' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')." nodegoat_to_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_src.object_id AND ".$version_select_to.")
					WHERE nodegoat_to_src.ref_object_id IN (".$sql_merge_object_ids.")
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;
		";
			
		foreach ($arr_table_names_references as $str_sql_table_name => $arr_value_type_table) {
			
			$sql .= "
				INSERT INTO ".$this->table_name_referenced_objects."
					(id, type_id, object_description_id, type)
					SELECT nodegoat_to_def.object_id AS id, nodegoat_to.type_id, nodegoat_to_def.object_description_id, 'object_definition' AS type
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name." nodegoat_to_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
						WHERE nodegoat_to_def.ref_object_id IN (".$sql_merge_object_ids.")
							AND ".$version_select_to_def."
					".DBFunctions::onConflict($sql_primary_key, ['id'])."
				;
			";
		}
			
		$sql .= "
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_description_id, type)
				SELECT nodegoat_to_def_ref.object_id AS id, nodegoat_to.type_id, nodegoat_to_def_ref.object_description_id, 'object_definition' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
					WHERE nodegoat_to_def_ref.ref_object_id IN (".$sql_merge_object_ids.")
						AND nodegoat_to_def_ref.state = 1
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;
							
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_description_id, type)
				SELECT nodegoat_to_def_src.object_id AS id, nodegoat_to.type_id, nodegoat_to_def_src.object_description_id, 'object_definition_sources' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')." nodegoat_to_def_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_src.object_id AND ".$version_select_to.")
					WHERE nodegoat_to_def_src.ref_object_id IN (".$sql_merge_object_ids.")
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;

			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_date_chrono.object_sub_id, nodegoat_tos.object_sub_details_id, 'object_sub_date_chronology_object_sub' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')." nodegoat_tos_date_chrono
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos_to ON (nodegoat_tos_to.id = nodegoat_tos_date_chrono.date_object_sub_id
							AND nodegoat_tos_to.object_id IN (".$sql_merge_object_ids.")
							AND ".$version_select_tos_to."
						)
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_date_chrono.object_sub_id AND nodegoat_tos.date_version = nodegoat_tos_date_chrono.version AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos_date_chrono.active = TRUE
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;

			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_date_chrono.object_sub_id, nodegoat_tos.object_sub_details_id, 'object_sub_date_chronology_cycle' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')." nodegoat_tos_date_chrono
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_date_chrono.object_sub_id AND nodegoat_tos.date_version = nodegoat_tos_date_chrono.version AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos_date_chrono.cycle_object_id IN (".$sql_merge_object_ids.")
						AND nodegoat_tos_date_chrono.active = TRUE
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;
			
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_date.object_sub_id, nodegoat_tos.object_sub_details_id, 'object_sub_date_span_cycle' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." nodegoat_tos_date
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_date.object_sub_id AND nodegoat_tos.date_version = nodegoat_tos_date.version AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos_date.span_cycle_object_id IN (".$sql_merge_object_ids.")
			;
		
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos.id AS object_sub_id, nodegoat_tos.object_sub_details_id, 'object_sub_location' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos.location_ref_object_id IN (".$sql_merge_object_ids.")
						AND ".$version_select_tos."
			;
			
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_src.object_sub_id, nodegoat_tos.object_sub_details_id, 'object_sub_sources' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')." nodegoat_tos_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_src.object_sub_id AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos_src.ref_object_id IN (".$sql_merge_object_ids.")
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;
		";
		
		foreach ($arr_table_names_references as $str_sql_table_name => $arr_value_type_table) {
			
			$sql .= "
				INSERT INTO ".$this->table_name_referenced_objects."
					(id, type_id, object_sub_id, object_sub_details_id, object_sub_description_id, type)
					SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_def.object_sub_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def.object_sub_description_id, 'object_sub_definition' AS type
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name." nodegoat_tos_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_tos_def.ref_object_id IN (".$sql_merge_object_ids.")
							AND ".$version_select_tos_def."
				;
			";
		}
		
		$sql .= "
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, object_sub_description_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_def_ref.object_sub_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def_ref.object_sub_description_id, 'object_sub_definition' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos_def_ref.ref_object_id IN (".$sql_merge_object_ids.") AND nodegoat_tos_def_ref.state = 1
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;
			
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, object_sub_description_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_def_src.object_sub_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def_src.object_sub_description_id, 'object_sub_definition_sources' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')." nodegoat_tos_def_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_src.object_sub_id AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos_def_src.ref_object_id IN (".$sql_merge_object_ids.")
				".DBFunctions::onConflict($sql_primary_key, ['id'])."
			;			
		";
		
		DB::queryMulti($sql);
	}
	
	public function getReferencedTypeObjects($id_only = false) {
				
		$this->storeReferencedTypeObjects();
		
		$arr = [];
		
		if ($id_only) {
			
			$res = DB::query("SELECT DISTINCT type_id, id FROM ".$this->table_name_referenced_objects);
			
			while ($arr_row = $res->fetchAssoc()) {
				
				$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row['id'];
			}
		} else {
			
			$res = DB::query("SELECT * FROM ".$this->table_name_referenced_objects);
			
			while ($arr_row = $res->fetchAssoc()) {
				
				$arr[] = $arr_row;
			}
		}
		
		return $arr;
	}
	
	public function delMergedTypeObjects($accept) {
		
		foreach ($this->arr_storage_objects as $storage) {
						
			$storage->delTypeObject($accept);
		}
	}
	
	public static function parseTypeMerge($type_id, $arr) {

		$arr_append = [];
		$arr_selection_user = [];
		
		if ($arr['append']) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
							
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
									
				$str_id = 'object_description_'.$object_description_id;
				
				if (in_array($str_id, $arr['append'])) {
					
					$arr_append['object_definitions'][$object_description_id] = true;
					$arr_selection_user[] = $str_id;
				}
			}
					
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
					continue;
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$str_id = 'object_sub_description_'.$object_sub_description_id;
					
					if (in_array($str_id, $arr['append'])) {
						
						$arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id] = true;
						$arr_selection_user[] = $str_id;
					}
				}
			}
		}
		
		return ['append' => $arr_append, 'selection_user' => $arr_selection_user];
	}
}
