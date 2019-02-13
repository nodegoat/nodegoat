<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
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
		
		$filter = new FilterTypeObjects($this->type_id, 'set');
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
			
			$filter = new FilterTypeObjects($this->type_id, 'set');
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
				
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) {
					continue;
				}
				
				$object_sub_id = $this->arr_append_object_sub_details_ids[$object_sub_details_id];
				$arr_master_object_sub = $this->arr_master_object_set['object_subs'][$object_sub_id]['object_sub'];
				
				if ($this->is_master && ($arr_master_object_sub['object_sub_date_start'] || $arr_master_object_sub['object_sub_location_ref_object_id'] || $arr_master_object_sub['object_sub_location_geometry'])) { // Do not store: existing master values
					
					$arr_merge_object_set['object_subs'][$cur_object_sub_id]['object_sub'] = ['object_sub_id' => $cur_object_sub_id, 'object_sub_details_id' => $object_sub_details_id];
				}
				
				foreach ((array)$arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_sub_object_definition) {
				
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
	
	private function generateBody() {
		
		if (!$this->is_master) { // Create new object
		
			$this->addTypeObjectVersion(1);
			$this->addTypeObjectVersionUser(-1); // User that merges objects is owner of the new merged object
			
			// Bring new object into existence
			$this->save();
			$this->addObjectUpdate();
			$this->commit(true);
			
			$hash = self::hash('');
			$arr_collect_versions = [$hash => 1];
			$cur_version = 1;
		} else {
			
			$arr_collect_versions = [];
			$cur_version = 0;
		}

		if ($this->is_master) {
			
			foreach ($this->getTypeObjectVersions() as $arr_version) {
				
				$value = $arr_version['object_name_plain'];
				
				$hash = self::hash($value);

				$version = $arr_version['object_version'];
					
				$arr_collect_versions[$hash] = $version;
				$cur_version = ($version > $cur_version ? $version : $cur_version);
			}
		}
					
		foreach ($this->arr_storage_objects as $merge_object_id => $storage) {
			
			$arr_version_users = $storage->getTypeObjectVersionsUsers();
								
			foreach (array_reverse($storage->getTypeObjectVersions(), true) as $arr_version) {
				
				$value = $arr_version['object_name_plain'];
				
				$hash = self::hash($value);
				
				if (!$arr_collect_versions[$hash]) {
					
					$cur_version++;
					$version = $cur_version;

					$this->addTypeObjectVersion($version, $value);
					
					$arr_collect_versions[$hash] = $version;
				}
				
				foreach ((array)$arr_version_users[$arr_version['object_version']] as $arr_version_user) {
					
					$this->addTypeObjectVersionUser($arr_collect_versions[$hash], $arr_version_user['id'], $arr_version_user['date']);
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
					
					$hash = self::hash($value);

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
					
					$hash = self::hash($value);
					
					if (!$arr_collect_versions[$hash]) {
						
						$cur_version++;
						$version = $cur_version;

						$this->addTypeObjectDescriptionVersion($object_description_id, $version, $value);
						
						$arr_collect_versions[$hash] = $version;
					}
					
					foreach ((array)$arr_version_users[$arr_version['object_definition_version']] as $arr_version_user) {
						
						$this->addTypeObjectDescriptionVersionUser($object_description_id, $arr_collect_versions[$hash], $arr_version_user['id'], $arr_version_user['date']);
					}
				}
			}
		}
				
		foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			$arr_collect_versions_sub = [];
			$cur_version_sub = 0;
			$arr_collect_versions_sub_location_geometry = [];
			$cur_version_sub_location_geometry = 0;
			$arr_collect_versions_sub_descriptions = [];
			$arr_cur_version_sub_descriptions = [];
			
			if ($this->is_master && $arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) {
				
				foreach ($this->arr_master_object_set['object_subs'] as $object_sub_id => $arr_master_object_sub) {
					
					if ($arr_master_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
						continue;
					}

					foreach ($this->getTypeObjectSubVersions($object_sub_id) as $arr_version) {
						
						$arr_value = [
							'object_sub_date_start' => $arr_version['object_sub_date_start'],
							'object_sub_date_end' => $arr_version['object_sub_date_end'],
							'object_sub_location_geometry' => $arr_version['object_sub_location_geometry'],
							'object_sub_location_ref_object_id' => $arr_version['object_sub_location_ref_object_id'],
							'object_sub_location_ref_type_id' => $arr_version['object_sub_location_ref_type_id'],
							'object_sub_location_ref_object_sub_details_id' => $arr_version['object_sub_location_ref_object_sub_details_id']
						];
						
						$hash = self::hash($arr_value);

						$version = $arr_version['object_sub_version'];
							
						$arr_collect_versions_sub[$hash] = $version;
						$cur_version_sub = ($version > $cur_version_sub ? $version : $cur_version_sub);

						if ($arr_version['object_sub_location_geometry_version']) {
							
							$version_sub_location_geometry = $arr_version['object_sub_location_geometry_version'];
							
							$hash_geometry = self::hash($arr_version['object_sub_location_geometry']);

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
							
							$hash = self::hash($value);

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
					
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) {
						
						$object_sub_id = $this->arr_append_object_sub_details_ids[$object_sub_details_id];
						
					} else { // Every non-unique subobject starts their own collection
						
						$object_sub_id = false;
						
						$arr_collect_versions_sub = [];
						$cur_version_sub = 0;
						$arr_collect_versions_sub_location_geometry = [];
						$cur_version_sub_location_geometry = 0;
						$arr_collect_versions_sub_descriptions = [];
						$arr_cur_version_sub_descriptions = [];
					}

					$arr_version_users = $storage->getTypeObjectSubVersionsUsers($cur_object_sub_id);
										
					foreach (array_reverse($storage->getTypeObjectSubVersions($cur_object_sub_id), true) as $arr_version) {
						
						$arr_value = [
							'object_sub_date_start' => $arr_version['object_sub_date_start'],
							'object_sub_date_end' => $arr_version['object_sub_date_end'],
							'object_sub_location_geometry' => $arr_version['object_sub_location_geometry'],
							'object_sub_location_ref_object_id' => $arr_version['object_sub_location_ref_object_id'],
							'object_sub_location_ref_type_id' => $arr_version['object_sub_location_ref_type_id'],
							'object_sub_location_ref_object_sub_details_id' => $arr_version['object_sub_location_ref_object_sub_details_id']
						];
						
						$hash = self::hash($arr_value);
						
						if (!$arr_collect_versions_sub[$hash]) {
							
							$cur_version_sub++;
							$version = $cur_version_sub;
														
							if ($arr_version['object_sub_location_geometry_version']) {
								
								$hash_geometry = self::hash($arr_version['object_sub_location_geometry']);
								
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
														
							if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_unique']) {
								
								$this->arr_append_object_sub_details_ids[$object_sub_details_id] = $object_sub_id;
							} else {
								
								$this->arr_append_object_sub_ids[$cur_object_sub_id] = $object_sub_id;
							}
							
							$arr_collect_versions_sub[$hash] = $version;
						}
						
						foreach ((array)$arr_version_users[$arr_version['object_sub_version']] as $arr_version_user) {
							
							$this->addTypeObjectSubVersionUser($object_sub_id, $arr_collect_versions_sub[$hash], $arr_version_user['id'], $arr_version_user['date']);
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
							
							$hash = self::hash($value);
							
							if (!$arr_collect_versions_sub_descriptions[$object_sub_description_id][$hash]) {
								
								$arr_cur_version_sub_descriptions[$object_sub_description_id]++;
								$version = $arr_cur_version_sub_descriptions[$object_sub_description_id];

								$this->addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, $version, $value);
								
								$arr_collect_versions_sub_descriptions[$object_sub_description_id][$hash] = $version;
							}
							
							foreach ((array)$arr_version_users[$arr_version['object_definition_version']] as $arr_version_user) {
								
								$this->addTypeObjectSubDescriptionVersionUser($object_sub_id, $object_sub_description_id, $arr_collect_versions_sub_descriptions[$object_sub_description_id][$hash], $arr_version_user['id'], $arr_version_user['date']);
							}
						}
					}
				}
			}
		}
	}
	
	public function mergeDiscussions() {
		
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
		
		$sql_primary_key = 'id, object_sub_id, object_description_id, object_sub_details_id, object_sub_description_id, type';
		
		DB::queryMulti("
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$this->table_name_referenced_objects.";
			
			CREATE TEMPORARY TABLE ".$this->table_name_referenced_objects." (
				id INT,
				type_id INT,
				object_sub_id INT DEFAULT 0,
				object_description_id INT DEFAULT 0,
				object_sub_details_id INT DEFAULT 0,
				object_sub_description_id INT DEFAULT 0,
				type CHAR(30),
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
				
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_description_id, type)
				SELECT nodegoat_to_def.object_id AS id, nodegoat_to.type_id, nodegoat_to_def.object_description_id, 'object_definition' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
					WHERE nodegoat_to_def.ref_object_id IN (".$sql_merge_object_ids.")
						AND ".$version_select_to_def."
			;
			
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
			
			INSERT INTO ".$this->table_name_referenced_objects."
				(id, type_id, object_sub_id, object_sub_details_id, object_sub_description_id, type)
				SELECT nodegoat_tos.object_id AS id, nodegoat_to.type_id, nodegoat_tos_def.object_sub_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def.object_sub_description_id, 'object_sub_definition' AS type
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_tos_def.ref_object_id IN (".$sql_merge_object_ids.")
						AND ".$version_select_tos_def."
			;
			
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
		");
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
		
	public function linkReferencedObjects() {
		
		if (!$this->object_id) {
			error(getLabel('msg_missing_information'));
		}
		
		timeLimit(120);
		
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
				case 'object_sub_location':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_location'] = true;
					$arr_type_object_select[$arr['id']]['object_sub_details'][$arr['object_sub_details_id']]['object_sub_descriptions'] = [];
					break;
				case 'object_sub_sources':
					$arr_type_object_values[$arr['type_id']][$arr['id']]['object_subs'][$arr['object_sub_id']]['object_sub_sources'] = true;
					$arr_type_object_select[$arr['id']]['object_sub_details'][$arr['object_sub_details_id']]['object_sub_descriptions'] = [];
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
				
		$func_parse_sources = function($type, $arr_sources) {
			
			foreach ($arr_sources[$this->type_id] as &$arr_source) {
				
				if (in_array($arr_source[$type.'_source_ref_object_id'], $this->arr_merge_object_ids)) {
					$arr_source[$type.'_source_ref_object_id'] = $this->object_id;
				}
			}
			unset($arr_source);

			return $arr_sources;
		};
		
		foreach ($arr_type_object_values as $type_id => $arr_object_values) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$storage = new StoreTypeObjects($type_id, false, $this->user_id);
			$stored = false;
			
			foreach ($arr_object_values as $object_id => $arr_values) {
				
				$filter = new FilterTypeObjects($type_id, 'set');
				$filter->setFilter(['objects' => $object_id, 'object_subs' => ($arr_values['object_subs'] ? array_keys($arr_values['object_subs']) : false)], true);
				$filter->setSelection([
						'object' => ($arr_type_object_select[$object_id]['object'] ?: false),
						'object_descriptions' => ($arr_type_object_select[$object_id]['object_descriptions'] ?: []),
						'object_sub_details' => ($arr_type_object_select[$object_id]['object_sub_details'] ?: [])
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
					
					$value = $func_parse_sources('object', $arr_object['object']['object_sources']);

					$arr_object_store['object']['object_sources'] = $value;
				}
				
				foreach ((array)$arr_values['object_definitions'] as $object_description_id => $arr_object_definition) {
					
					$arr_object_description = $arr_type_set['object_descriptions'][$object_description_id];
					$arr_object_store['object_definitions'][$object_description_id]['object_description_id'] = $object_description_id;
					
					if ($arr_object_definition['object_definition']) {
						
						if ($arr_object_description['object_description_ref_type_id']) {

							if ($arr_object_description['object_description_has_multi']) {
								
								$value = $arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'];
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
						
						$value = $func_parse_sources('object_definition', $arr_object['object_definitions'][$object_description_id]['object_definition_sources']);

						$arr_object_store['object_definitions'][$object_description_id]['object_definition_sources'] = $value;
					}
				}
				
				foreach ((array)$arr_values['object_subs'] as $object_sub_id => $arr_object_sub) {
					
					$object_sub_details_id = $arr_object['object_subs'][$object_sub_id]['object_sub']['object_sub_details_id'];
					
					$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_id'] = $object_sub_id;
					$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_details_id'] = $object_sub_details_id;
					
					if ($arr_object_sub['object_sub_location']) {
						
						$value = $arr_object['object_subs'][$object_sub_id]['object_sub'];
						$value['object_sub_location_ref_object_id'] = $this->object_id;
						
						$arr_object_store['object_subs'][$object_sub_id]['object_sub'] = $value;
					}
					
					if ($arr_object_sub['object_sub_sources']) {
					
						$value = $func_parse_sources('object_sub', $arr_object['object_subs'][$object_sub_id]['object_sub']['object_sub_sources']);

						$arr_object_store['object_subs'][$object_sub_id]['object_sub']['object_sub_sources'] = $value;
					}
					
					foreach ((array)$arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
						
						$arr_object_sub_description = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
						$arr_object_store['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_description_id'] = $object_sub_description_id;
						
						if ($arr_object_sub_definition['object_sub_definition']) {
							
							if ($arr_object_sub_description['object_sub_description_ref_type_id']) {

								if ($arr_object_sub_description['object_sub_description_has_multi']) {
									
									$value = $arr_object['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id'];
									$value = array_diff($value, $this->arr_merge_object_ids);
									$value[] = $this->object_id;
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
						
							$value = $func_parse_sources('object_sub_definition', $arr_object['object_subs'][$object_sub_id]['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_sources']);

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
	
	public function delMergedTypeObjects($accept) {
		
		foreach ($this->arr_storage_objects as $storage) {
						
			$storage->delTypeObject($accept);
		}
	}
	
	private function hash($value) {
		
		$hash = hash('md5', serialize($value));
		
		return $hash;
	}
}
