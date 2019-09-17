<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

define('DATE_INT_MIN', '-9000000000000000000');
define('DATE_INT_MAX', '9000000000000000000');

class StoreTypeObjects {
	
	protected $type_id = false;
	protected $user_id = false;
	protected $object_id = false;
	
	protected $insert = true;
	protected $insert_sub = true;
	
	protected $arr_append = false;
	protected $versioning = true;
	
	protected $arr_type_set = [];
	protected $arr_types = [];
	protected $arr_object_set = [];
	
	protected $arr_sql_insert = [];
	protected $arr_sql_update = [];
	protected $arr_sql_delete = [];
	protected $arr_actions = [];
	
	protected $table_name_object_updates = '';
	protected $stmt_object_updates = false;
	
	protected $stmt_object_versions = false;
	protected $stmt_object_description_versions = [];
	protected $stmt_object_sub_versions = false;
	protected $stmt_object_sub_description_versions = [];
	protected $stmt_object_sub_details_id = false;
	protected $stmt_object_sub_ids = false;
	
	protected $stmt_object_id = false;
	
	protected $arr_append_object_sub_details_ids = [];
	protected $arr_append_object_sub_ids = [];
	
	protected $mode = 'update';
	protected $do_check = true;
	protected $is_new = false;
	protected $identifier = false;
	protected $lock_key = false;
	protected $is_trusted = false;

	public static $timeout_lock = 30; // Lock object, in seconds
	public static $dir_media = 'media/';
	
	public static $last_object_id = 0;
	
    public function __construct($type_id, $object_id, $user_id, $identifier = false, $arr_type_set = false) {
	
		$this->type_id = $type_id;
		$this->user_id = $user_id;
		
		$this->identifier = ($identifier ?: 'any'); // Use identifier to make table operations unique when applicable
		$this->table_name_object_updates = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_'.$this->identifier);
		$this->lock_key = uniqid();
		
		$this->setObjectID($object_id);
		
		if ($arr_type_set) {
			$this->arr_type_set = $arr_type_set;
		} else {
			$this->arr_type_set = StoreType::getTypeSet($this->type_id);
		}
			
		$this->arr_types = StoreType::getTypes();
    }
    
	public function setMode($mode = 'update', $do_check = true) {
		
		// $mode = overwite OR update
		// $do_check = true OR false: perform object checks before update
		
		if ($mode !== null) {
			$this->mode = $mode;
		}
		if ($do_check !== null) {
			$this->do_check = $do_check;
		}
	}
    
    public function setObjectID($object_id) {

		$this->object_id = ((int)$object_id ?: false);
		
		$this->is_new = ($object_id ? false : true);
		
		if (!$object_id) {
			return;
		}
		
		$this->addObjectUpdate();
	}
		
	public function setAppend($append = true) {
		
		if ($this->arr_append && is_array($append)) {
			
			$this->arr_append = array_replace_recursive($this->arr_append, $append);
		} else {
		
			if ($append === true) {
				$this->arr_append['all'] = true;
			} else if ($append === false) {
				$this->arr_append = [];
			} else {
				$this->arr_append = (array)$append;
			}
		}
	}
    
    /*
		Store:
		Objects are stored and updated according to the values served at their corresponding positions in the object array (same goes for filtering):
		- Want to insert/update something => present a new definition, sub-object, or source type
		- Want to delete something => present an empty definition, sub-object (object_sub_version = 'deleted'), or source type
		
		Versioning - version:
		-20+: Relates to alternate records of record versions
		-10+: Relates to an alternate record based on active record
		-2: Relates to records with no regard to versioning
		-1: Translates to version 1 in version log, indicates a new record
		1+: Relates to an existing record, record version is changed 
		0: Object is set to be deleted
		
		Status - status:
		-1: Record is deleted, and had its previous state as active (applicable only to forced deletion (no versioning) of objects)
		0: Record is either active or irrelevant
		1: Record matches version and no other record is active
		2: Record matches version and is not active, or record does not match the version but is currently active
		3: Record is the currently active record, or when there is no active record, the record with a status
	*/
		
	public function store($arr_object_self, $arr_object_definitions, $arr_object_subs) {
		
		$arr_object_self = arrParseRecursive($arr_object_self, 'trim');
		$arr_object_definitions = arrParseRecursive($arr_object_definitions, 'trim');
		$arr_object_subs = arrParseRecursive($arr_object_subs, 'trim');
		
		if ($this->object_id && $this->versioning) {
			
			// Relate changes to the latest version
			$filter = new FilterTypeObjects($this->type_id, 'storage');
			
			if ($this->mode == 'update') {
				
				$arr_selection = ['object_descriptions' => [], 'object_sub_details' => []];
				
				foreach ($arr_object_definitions as $arr_object_definition) {
					
					$arr_selection['object_descriptions'][$arr_object_definition['object_description_id']] = $arr_object_definition['object_description_id'];
				}
				foreach ($arr_object_subs as $arr_object_sub) {
					
					$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
					
					if (!$object_sub_details_id && $arr_object_sub['object_sub']['object_sub_id']) {
						
						$object_sub_details_id = $this->getTypeObjectSubObjectSubDetailsID($arr_object_sub['object_sub']['object_sub_id']);
					}
					
					if (!$object_sub_details_id) {
						continue;
					}
					
					$arr_selection['object_sub_details'][$object_sub_details_id] = true;
				}
				
				$filter->setSelection($arr_selection);
			}
			$filter->setVersioning();
			$filter->setFilter(['objects' => $this->object_id]);

			$this->arr_object_set = current($filter->init());
		} else {
			
			$this->arr_object_set = [];
		}
		
		$this->arr_actions = [];
		
		// Check & clean
		
		if ($arr_object_definitions) { // Object definitions (value OR ref_object_id OR array(ref_object_id))
			
			$arr_object_definitions_input = $arr_object_definitions;
			$arr_object_definitions = [];
			
			// Set key to object_description_id
			foreach ($arr_object_definitions_input as $value) {				
				$arr_object_definitions[$value['object_description_id']] = $value;
			}
			unset($arr_object_definitions_input);
		}
		
		if ($this->do_check) {
			
			$version_select = GenerateTypeObjects::generateVersioning('active', 'record', 'nodegoat_to_def');
			$version_select_to = GenerateTypeObjects::generateVersioning('active', 'object', 'nodegoat_to');

			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
				$arr_object_definition = $arr_object_definitions[$object_description_id];
	
				$is_ref_type_id = (bool)$arr_object_description['object_description_ref_type_id'];
				
				if (!$is_ref_type_id && $arr_object_description['object_description_is_unique']) {
					
					if (!$arr_object_definition['object_definition_value']) {
						continue;
					}
					
					$value_type = $arr_object_description['object_description_value_type'];
					$value_find = self::formatToSQLValue($value_type, $arr_object_definition['object_definition_value']);
					$sql_match = self::formatToSQLValueFilter($value_type, ['equality' => '=', 'value' => $value_find], StoreType::getValueTypeValue($value_type));
					
					$res = DB::query("SELECT object_description_id, object_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type)." nodegoat_to_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
						WHERE object_description_id = ".$object_description_id."
							AND ".$sql_match."
							AND ".$version_select."
							".($this->object_id ? "AND object_id != ".$this->object_id : "")."
					");
					
					if ($res->getRowCount()) {
						
						Labels::setVariable('value', $value_find);
						Labels::setVariable('field', Labels::parseTextVariables($arr_object_description['object_description_name']));
						error(getLabel('msg_object_definition_not_unique'));
					}
				}
				
				if ($arr_object_description['object_description_is_required']) {
					
					if ($arr_object_definition === null) {
						
						if ($this->object_id || (!$this->object_id && $arr_object_description['object_description_value_type_options']['default']['value'])) {
							continue;
						}
					} else if (($is_ref_type_id && $arr_object_definition['object_definition_ref_object_id']) || (!$is_ref_type_id && $arr_object_definition['object_definition_value'])) {
						continue;						
					}
					
					Labels::setVariable('field', Labels::parseTextVariables($arr_object_description['object_description_name']));
					error(getLabel('msg_object_definition_is_required'));
				}
			}
		}
		
		$arr_object_subs = ($arr_object_subs ?: []);
		
		foreach ($arr_object_subs as $key => &$arr_object_sub) {
			
			$object_sub_id = (int)$arr_object_sub['object_sub']['object_sub_id'];
			
			if (!$object_sub_id || $this->is_trusted) { // Trust object_sub_details_id when new sub-object or when doing internal operations (e.g. merging (object set doesn't contain old merging sub-object ids))
				
				$object_sub_details_id = (int)$arr_object_sub['object_sub']['object_sub_details_id'];
				$object_sub_details_id = ($this->arr_type_set['object_sub_details'][$object_sub_details_id] ? $object_sub_details_id : false);
			} else {
				
				if ($this->versioning) {
					
					$object_sub_details_id = (int)$this->arr_object_set['object_subs'][$object_sub_id]['object_sub']['object_sub_details_id'];
				} else {
					
					$object_sub_details_id = $this->getTypeObjectSubObjectSubDetailsID($object_sub_id);
				}
			}

			if (!$object_sub_details_id) {
				
				unset($arr_object_subs[$key]);
				continue;
			}
			
			// Subobject
		
			$arr_object_sub = $this->parseObjectSub($object_sub_details_id, $arr_object_sub);
			
			if (!$arr_object_sub) {
				
				unset($arr_object_subs[$key]);
			}
		}
		unset($arr_object_sub);
		
		// Start Storage
			
		$has_transaction = DB::startTransaction('store_type_objects');
	
		// Object (name)
		
		$action_object = false;
		
		if (!$this->object_id) {
			
			$action_object = 'insert';
			$this->insert = true;
		} else {
								
			if ($this->mode == 'overwrite' && $this->arr_type_set['type']['use_object_name'] && !isset($arr_object_self['object_name_plain'])) {
				$arr_object_self['object_name_plain'] = '';
			}
			
			if ($this->versioning) {
				
				if (
					($this->arr_type_set['type']['use_object_name'] && $arr_object_self['object_name_plain'] !== null && $arr_object_self['object_name_plain'] != $this->arr_object_set['object']['object_name_plain'])
						||
					$this->arr_object_set['object']['object_version'] == 'deleted'
				) { // Object version changes when object name is altered, or when the current object status was preset to deleted
					
					foreach ($this->getTypeObjectVersions() as $arr_version) {
						
						if ($arr_version['object_name_plain'] == $arr_object_self['object_name_plain'] && $arr_version['object_version'] > 0) {
						
							$version = $arr_version['object_version'];
							$action_object = 'version';
							
							break;
						}
					}
					
					$action_object = ($action_object ?: 'insert');
				}
			} else {
				
				if ($this->arr_type_set['type']['use_object_name'] && $arr_object_self['object_name_plain'] !== null) {
					
					$action_object = 'insert';
				}
			}

			$this->insert = false;
		}
		
		if ($action_object == 'insert') {
			
			if ($this->versioning && !$this->insert) {
				
				$arr_version = $this->getTypeObjectVersions();
				$arr_version = current($arr_version);					
				$version = ($arr_version['object_version'] > 0 ? $arr_version['object_version'] : 0) + 1;
			} else {
				
				$version = ($this->versioning ? 1 : -2);
			}
				
			$this->addTypeObjectVersion($version, $arr_object_self['object_name_plain']);
		}
		
		if ($action_object) {
			
			$version_log = ($this->versioning && $this->insert ? -1 : $version);
			
			$this->addTypeObjectVersionUser($version_log);
		}
					
		$this->handleSources('object', $arr_object_self['object_sources']);
								
		// Object definitions
		
		foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			$arr_object_definition = $arr_object_definitions[$object_description_id];
			
			$is_ref_type_id = (bool)$arr_object_description['object_description_ref_type_id'];
			$is_reversal = ($is_ref_type_id && $this->arr_types[$arr_object_description['object_description_ref_type_id']]['is_reversal']);
			
			if (!$is_reversal) {
					
				if (!$arr_object_definition) {
					
					if ($this->insert) {
						
						$value_default = $arr_object_description['object_description_value_type_options']['default']['value'];
						
						if ($value_default && $arr_object_definition === null) {

							if ($is_ref_type_id) {
								$arr_object_definition['object_definition_ref_object_id'] = $value_default;
							} else {
								$arr_object_definition['object_definition_value'] = $value_default;
							}
						} else {
							continue;
						}
					} else if ($this->mode == 'overwrite' && $arr_object_definition === null) {
						
						$arr_object_definition = ['object_definition_value' => '', 'object_definition_ref_object_id' => 0];
					} else {
						continue;
					}
				}
				
				$is_defined = (($is_ref_type_id && ($arr_object_definition['object_definition_ref_object_id'] !== null || array_key_exists('object_definition_ref_object_id', $arr_object_definition))) || (!$is_ref_type_id && ($arr_object_definition['object_definition_value'] !== null || array_key_exists('object_definition_value', $arr_object_definition))));

				$action_object_definition = false;

				if ($is_defined) {
					
					$arr_object_definition['object_definition_value'] = self::formatToSQLValue($arr_object_description['object_description_value_type'], $arr_object_definition['object_definition_value']);
					
					if ($arr_object_definition['object_definition_value'] === false || $arr_object_definition['object_definition_value'] === '') {
						$arr_object_definition['object_definition_value'] = null;
					}
					
					if ($is_ref_type_id) {
						
						if ($arr_object_description['object_description_has_multi']) {
							$arr_object_definition['object_definition_ref_object_id'] = array_values(array_unique(array_filter((array)$arr_object_definition['object_definition_ref_object_id'])));
						} else {
							$arr_object_definition['object_definition_ref_object_id'] = (is_array($arr_object_definition['object_definition_ref_object_id']) ? reset($arr_object_definition['object_definition_ref_object_id']) : $arr_object_definition['object_definition_ref_object_id']);
						}
					} else if ($arr_object_description['object_description_has_multi']) {
						
						$arr_object_definition['object_definition_value'] = (array)$arr_object_definition['object_definition_value'];
					}
					
					if ($this->insert) {
						
						if ($is_ref_type_id) {
							
							if ($arr_object_definition['object_definition_ref_object_id']) {
								
								// Insert new object definition record
								$action_object_definition = 'insert';
							} else if (!$this->insert) {
								
								$action_object_definition = 'delete';
							}
						} else {
							
							if (
								(!$arr_object_description['object_description_has_multi'] && $arr_object_definition['object_definition_value'] !== null)
								||
								($arr_object_description['object_description_has_multi'] && $arr_object_definition['object_definition_value'])
							) {
							
								// Insert new object definition record
								$action_object_definition = 'insert';
							} else if (!$this->insert) {
								
								$action_object_definition = 'delete';
							}
						}
					} else {
						
						$is_appendable = ($this->arr_append['all'] || (is_array($this->arr_append['object_definitions']) && $this->arr_append['object_definitions'][$object_description_id]));
						
						if ($is_ref_type_id) {
							
							if ($this->versioning) {
								
								$arr_compare_object_definition = $arr_object_definition['object_definition_ref_object_id'];
								
								if ($arr_object_description['object_description_has_multi']) {
									
									if ($is_appendable) {
										
										$arr_object_definition['object_definition_ref_object_id'] = array_combine($this->arr_object_set['object_definitions'][$object_description_id]['object_definition_ref_object_id'], $this->arr_object_set['object_definitions'][$object_description_id]['object_definition_ref_object_id']);
										
										foreach ($arr_compare_object_definition as $ref_object_id) {
											
											if ($arr_object_definition['object_definition_ref_object_id'][$ref_object_id]) {
												continue;
											}
											
											if ($ref_object_id < 0) { // Negative object id means: remove
												
												unset($arr_object_definition['object_definition_ref_object_id'][abs($ref_object_id)]);
												$is_appendable = false; // Allow deletion of empty object_definition_ref_object_id array
											} else {
												
												$arr_object_definition['object_definition_ref_object_id'][$ref_object_id] = $ref_object_id;
											}
										}
										
										$arr_compare_object_definition = $arr_object_definition['object_definition_ref_object_id'];
									}
								} else {
									
									if ($arr_compare_object_definition != 'last') {
										
										$arr_compare_object_definition = (int)$arr_compare_object_definition;
										
										if ($is_appendable) {
											
											if ($arr_compare_object_definition < 0 && $this->arr_object_set['object_definitions'][$object_description_id]['object_definition_ref_object_id'] == abs($arr_compare_object_definition)) { // Negative object id means: remove
												
												$arr_compare_object_definition = false;
												$is_appendable = false; // Allow deletion of empty object_definition_ref_object_id
											}
										}
									}
								}
								
								if ($arr_compare_object_definition != $this->arr_object_set['object_definitions'][$object_description_id]['object_definition_ref_object_id']) {
								
									if (!$arr_object_definition['object_definition_ref_object_id']) {
										
										if (!$is_appendable) {
											
											// Set object definition record deleted flag (version 0)
											$action_object_definition = 'delete';
										}
									} else {
																				
										// Update object definition record, find existing version or insert new version
										foreach ($this->getTypeObjectDescriptionVersions($object_description_id) as $arr_version) {
											
											if ($arr_compare_object_definition == $arr_version['object_definition_ref_object_id'] && $arr_version['object_definition_version'] > 0) {
												
												$version = $arr_version['object_definition_version'];
												$action_object_definition = 'version';
												
												break;
											}
										}
										
										$action_object_definition = ($action_object_definition ?: 'insert');
									}
								}
							} else {
								
								if (!$arr_object_definition['object_definition_ref_object_id']) {
								
									$action_object_definition = 'delete';
								} else {
									
									$action_object_definition = 'insert';
								}
							}
						} else {
							
							if ($this->versioning) {
								
								$arr_compare_object_definition = $arr_object_definition['object_definition_value'];
								$arr_cur_object_definition = $this->arr_object_set['object_definitions'][$object_description_id]['object_definition_value'];
								
								if ($arr_object_description['object_description_has_multi']) {
									
									if ($is_appendable) {
										
										$arr_object_definition['object_definition_value'] = arrMergeValues([$arr_compare_object_definition, $arr_cur_object_definition]);
										$arr_compare_object_definition = $arr_object_definition['object_definition_value'];
									}
								} else {
									
									if ($is_appendable) {
										
										$arr_object_definition['object_definition_value'] = self::appendToValue($arr_object_description['object_description_value_type'], $arr_cur_object_definition, $arr_object_definition['object_definition_value']);
									}
									
									$arr_compare_object_definition = $arr_object_definition['object_definition_value'];
								}
								
								if ($arr_compare_object_definition != $arr_cur_object_definition || (($arr_object_definition['object_definition_value'] !== '' && $arr_object_definition['object_definition_value'] !== null) && ($arr_cur_object_definition === '' || $arr_cur_object_definition === null))) {
									
									if ($arr_object_definition['object_definition_value'] === '' || $arr_object_definition['object_definition_value'] === null || ($arr_object_description['object_description_has_multi'] && !$arr_object_definition['object_definition_value'])) {
										
										if (!$is_appendable) {
											
											// Set object definition record deleted flag (version 0)
											$action_object_definition = 'delete';
										}
									} else {
											
										// Update object definition record, find existing version or insert new version
										foreach ($this->getTypeObjectDescriptionVersions($object_description_id) as $arr_version) {

											if ($arr_compare_object_definition == $arr_version['object_definition_value'] && $arr_version['object_definition_version'] > 0) {
												
												$version = $arr_version['object_definition_version'];
												$action_object_definition = 'version';
												
												break;
											}
										}

										$action_object_definition = ($action_object_definition ?: 'insert');
									}
								}
							} else {
								
								if ($arr_object_definition['object_definition_value'] === '' || $arr_object_definition['object_definition_value'] === null || ($arr_object_description['object_description_has_multi'] && !$arr_object_definition['object_definition_value'])) {
								
									$action_object_definition = 'delete';
								} else {
									
									$action_object_definition = 'insert';
								}
							}
						}
					}
					
					if ($action_object_definition == 'insert') {
						
						if ($this->versioning && !$this->insert) {
							
							$arr_version = $this->getTypeObjectDescriptionVersions($object_description_id);
							$arr_version = current($arr_version);
							$version = ($arr_version['object_definition_version'] > 0 ? $arr_version['object_definition_version'] : 0) + 1;
						} else {
							
							$version = ($this->versioning ? 1 : -2);
						}
						
						if ($is_ref_type_id) {
							$value = (!$arr_object_description['object_description_has_multi'] ? [$arr_object_definition['object_definition_ref_object_id']] : $arr_object_definition['object_definition_ref_object_id']);
						} else {
							$value = $arr_object_definition['object_definition_value'];
						}
						
						$this->addTypeObjectDescriptionVersion($object_description_id, $version, $value);
						
					} else if ($action_object_definition == 'delete') {
						
						if (!$this->versioning) {
							
							$this->arr_sql_delete['object_definition_version'][] = "UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'])."
								SET active = FALSE, status = 0
								WHERE object_description_id = ".$object_description_id."
									AND object_id = ".$this->object_id."
									AND version >= -2
							";
						}
						
						$arr_object_definition['object_definition_sources'] = []; // Also remove sources
						
						$version = 0;
					}
					
					if ($action_object_definition) {
						
						if ($arr_object_description['object_description_value_type'] == 'text' || $arr_object_description['object_description_value_type'] == 'text_layout' || $arr_object_description['object_description_value_type'] == 'text_tags') {
							
							if (!$this->insert) {

								$this->arr_sql_delete['object_definition_objects'][] = "UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')."
									SET state = 0
									WHERE object_description_id = ".$object_description_id."
										AND object_id = ".$this->object_id."
								";
							}
							
							if ($action_object_definition != 'delete') {
							
								$arr_sql_insert = [];
								$arr_object_definition_text = self::parseObjectDefinitionText($arr_object_definition['object_definition_value']);
								
								$this->addTypeObjectDescriptionVersion($object_description_id, -10, $arr_object_definition_text['text']);
								
								foreach($arr_object_definition_text['arr_tags'] as $value) {
									
									$arr_sql_insert[] = "(".$object_description_id.", ".$this->object_id.", ".$value['object_id'].", ".$value['type_id'].", '".DBFunctions::strEscape($value['text'])."', ".$value['group_id'].", 1)";
								}

								if ($arr_sql_insert) {
									
									$this->arr_sql_insert['object_definition_objects'][] = implode(',', $arr_sql_insert);
								}
							} else {
								
								$this->addTypeObjectDescriptionVersion($object_description_id, -10, '');
							}
						}
						
						$version_log = ($this->versioning && $this->insert ? -1 : $version);
						
						$this->addTypeObjectDescriptionVersionUser($object_description_id, $version_log);
					}
				}
			}
			
			$this->handleSources('object_definition', $arr_object_definition['object_definition_sources'], ['object_description_id' => $object_description_id]);
		}
				
		// Subobjects (object_sub AND object_sub_definitions)

		if (!$this->insert && $this->mode == 'overwrite') {
			
			$arr_object_subs_touched = [];
			
			foreach ($arr_object_subs as $arr_object_sub) {
				
				$object_sub_id = $arr_object_sub['object_sub']['object_sub_id'];
				
				if (!$object_sub_id) {
					continue;
				}
				
				$arr_object_subs_touched[$object_sub_id] = $object_sub_id;
			}
			
			if ($this->versioning) {
				
				foreach ($this->arr_object_set['object_subs'] as $object_sub_id => $arr_object_sub) {
					
					if ($arr_object_subs_touched[$object_sub_id]) {
						continue;
					}
						
					$arr_object_subs[] = ['object_sub' => [
						'object_sub_id' => $object_sub_id,
						'object_sub_details_id' => $arr_object_sub['object_sub']['object_sub_details_id'],
						'object_sub_version' => 'deleted'
					]];
				}
			} else {
				
				if (!$this->stmt_object_sub_ids) {
						
					$this->stmt_object_sub_ids = DB::prepare("SELECT nodegoat_tos.id, nodegoat_tos.object_sub_details_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
						WHERE nodegoat_tos.object_id = ".DBStatement::assign('object_id', 'i')."
							AND ".GenerateTypeObjects::generateVersioning('full', 'object_sub', 'nodegoat_tos')."
					");
				}
				
				$this->stmt_object_sub_ids->bindParameters(['object_id' => $this->object_id]);
				
				$res = $this->stmt_object_sub_ids->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$object_sub_id = $arr_row[0];
					$object_sub_details_id = $arr_row[1];
					
					if ($arr_object_subs_touched[$object_sub_id]) {
						continue;
					}
						
					$arr_object_subs[] = ['object_sub' => [
						'object_sub_id' => $object_sub_id,
						'object_sub_details_id' => $object_sub_details_id,
						'object_sub_version' => 'deleted'
					]];
				}
			}		
		}
						
		foreach ($arr_object_subs as $arr_object_sub) {
			
			$object_sub_details_id = $arr_object_sub['object_sub']['object_sub_details_id'];
			
			$parse_object_sub = ($arr_object_sub['object_sub']['object_sub_date_chronology'] !== null);
			$action_object_sub = false;
			$object_sub_id = false;
			
			$version = 0;
			$geometry_version = 0;
			$arr_object_sub_versions = false;
			
			if ($arr_object_sub['object_sub']['object_sub_id']) {
				
				$object_sub_id = $arr_object_sub['object_sub']['object_sub_id'];
			
				if ($arr_object_sub['object_sub']['object_sub_version'] == 'deleted') {
					
					if ($this->versioning) {
						
						$arr_cur_object_sub = $this->arr_object_set['object_subs'][$object_sub_id];
						
						if ($arr_cur_object_sub['object_sub']['object_sub_version'] != 'deleted') { // Already flagged as deleted
							// Set object sub record deleted flag (version 0)
							$action_object_sub = 'delete';
						}
					} else {
						
						$action_object_sub = 'delete';
					}
				} else if ($parse_object_sub) {
					
					if ($this->versioning) {
						
						$arr_cur_object_sub = $this->arr_object_set['object_subs'][$object_sub_id];
						
						$str_compare = $arr_object_sub['object_sub']['object_sub_date_chronology'].'-'.$arr_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id'].'-'.$arr_object_sub['object_sub']['object_sub_location_ref_object_id'].'-'.$arr_object_sub['object_sub']['object_sub_location_geometry'];
						$str_compare_cur = self::formatToSQLValue('chronology', $arr_cur_object_sub['object_sub']['object_sub_date_chronology']).'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] && $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] ? 0 : $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id']).'-'.$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'].'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] ? $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] : '');
						
						if ($str_compare != $str_compare_cur) {
											
							$arr_object_sub_versions = $this->getTypeObjectSubVersions($object_sub_id);
							
							foreach ($arr_object_sub_versions as $arr_object_sub_version) {
								
								$str_compare_version = self::formatToSQLValue('chronology', $arr_object_sub_version['object_sub_date_chronology']).'-'.$arr_object_sub_version['object_sub_location_ref_object_sub_details_id'].'-'.$arr_object_sub_version['object_sub_location_ref_object_id'].'-'.$arr_object_sub_version['object_sub_location_geometry'];
						
								if ($str_compare == $str_compare_version && $arr_object_sub_version['object_sub_version'] > 0) {
									
									$version = $arr_object_sub_version['object_sub_version'];
									$action_object_sub = 'version';
									
									$arr_object_sub['object_sub']['object_sub_date_version'] = $arr_object_sub_version['object_sub_date_version'];
									$arr_object_sub['object_sub']['object_sub_date_chronology'] = false;
									
									$arr_object_sub['object_sub']['object_sub_location_geometry_version'] = $arr_object_sub_version['object_sub_location_geometry_version'];
									$arr_object_sub['object_sub']['object_sub_location_geometry'] = false;
									
									break;
								}
							}
							
							$action_object_sub = ($action_object_sub ?: 'insert');
						}
					} else {
						
						$action_object_sub = 'insert';
					}
				}
				
				$this->insert_sub = false;
			} else if ($parse_object_sub || $arr_object_sub['object_sub_definitions']) {
				
				$action_object_sub = 'insert';
				$this->insert_sub = true;
			}
			
			if ($action_object_sub == 'insert') {
				
				if ($this->versioning && !$this->insert_sub) {
					
					$arr_object_sub_versions = ($arr_object_sub_versions ?: $this->getTypeObjectSubVersions($object_sub_id));
					$arr_object_sub_version = current($arr_object_sub_versions);					
					$version = ($arr_object_sub_version['object_sub_version'] > 0 ? $arr_object_sub_version['object_sub_version'] : 0) + 1;
					
					if ($arr_object_sub['object_sub']['object_sub_location_geometry']) {
						
						$arr_object_sub['object_sub']['object_sub_location_geometry_version'] = 1;
						
						foreach ($arr_object_sub_versions as $arr_object_sub_version) {
							
							if ($arr_object_sub_version['object_sub_version'] < 1) { // Disregard versionless sub-objects
								continue;
							}
							
							if ($arr_object_sub['object_sub']['object_sub_location_geometry'] == $arr_object_sub_version['object_sub_location_geometry']) {
								
								$arr_object_sub['object_sub']['object_sub_location_geometry'] = false;
								$arr_object_sub['object_sub']['object_sub_location_geometry_version'] = $arr_object_sub_version['object_sub_location_geometry_version'];
								
								break;
							} else if ($arr_object_sub_version['object_sub_location_geometry_version'] >= $arr_object_sub['object_sub']['object_sub_location_geometry_version']) {
								
								$arr_object_sub['object_sub']['object_sub_location_geometry_version'] = $arr_object_sub_version['object_sub_location_geometry_version'] + 1;
							}
						}
					}
					
					if ($arr_object_sub['object_sub']['object_sub_date_chronology']) {
						
						$arr_object_sub['object_sub']['object_sub_date_version'] = 1;
						
						foreach ($arr_object_sub_versions as $arr_object_sub_version) {
							
							if ($arr_object_sub_version['object_sub_version'] < 1) { // Disregard versionless sub-objects
								continue;
							}
							
							if ($arr_object_sub['object_sub']['object_sub_date_chronology'] == self::formatToSQLValue('chronology', $arr_object_sub_version['object_sub_date_chronology'])) {
								
								$arr_object_sub['object_sub']['object_sub_date_chronology'] = false;
								$arr_object_sub['object_sub']['object_sub_date_version'] = $arr_object_sub_version['object_sub_date_version'];
								
								break;
							} else if ($arr_object_sub_version['object_sub_date_version'] >= $arr_object_sub['object_sub']['object_sub_date_version']) {
								
								$arr_object_sub['object_sub']['object_sub_date_version'] = $arr_object_sub_version['object_sub_date_version'] + 1;
							}
						}
					}
				} else {
					
					$version = ($this->versioning ? 1 : -2);
					
					if ($arr_object_sub['object_sub']['object_sub_location_geometry']) {
						
						$arr_object_sub['object_sub']['object_sub_location_geometry_version'] = $version;
					}
					
					if ($arr_object_sub['object_sub']['object_sub_date_chronology']) {
						
						$arr_object_sub['object_sub']['object_sub_date_version'] = $version;
						
						if (!$this->versioning && !$this->insert_sub) {
							
							$this->arr_sql_delete['object_sub_date_chronology_version'][] = "UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')."
								SET active = FALSE
								WHERE object_sub_id = ".$object_sub_id."
									AND version = -2
							";
						}
					}
				}
				
				$object_sub_id = $this->addTypeObjectSubVersion($object_sub_id, $object_sub_details_id, $version, $arr_object_sub['object_sub']);

			} else if ($action_object_sub == 'delete') {
				
				if (!$this->versioning) {
				
					$this->arr_sql_delete['object_sub_version'][] = "UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')."
						SET active = FALSE, status = 0
						WHERE id = ".$object_sub_id."
							AND object_id = ".$this->object_id."
							AND version >= -2
					";
				}
				
				$arr_object_sub['object_sub']['object_sub_sources'] = []; // Also remove sources
				
				$version = 0;
			}
			
			if ($action_object_sub) {
				
				$version_log = ($this->versioning && $this->insert_sub ? -1 : $version);
			
				$this->addTypeObjectSubVersionUser($object_sub_id, $version_log);
			}
			
			if ($object_sub_id) {
				
				$this->handleSources('object_sub', $arr_object_sub['object_sub']['object_sub_sources'], ['object_sub_id' => $object_sub_id]);
			}
							
			// Object sub definitions (value OR ref_object_id)
			
			if (!$arr_object_sub['object_sub_definitions'] || !$object_sub_id) {
				continue;
			}

			foreach ($this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
					continue;
				}
				
				$arr_object_sub_definition = $arr_object_sub['object_sub_definitions'][$object_sub_description_id];
				
				$is_ref_type_id = (bool)$arr_object_sub_description['object_sub_description_ref_type_id'];
				$is_reversal = ($is_ref_type_id && $this->arr_types[$arr_object_sub_description['object_sub_description_ref_type_id']]['is_reversal']);
				
				if (!$is_reversal) {
						
					if (!$arr_object_sub_definition) {
						
						if ($this->insert_sub) {
							
							$value_default = $arr_object_sub_description['object_sub_description_value_type_options']['default']['value'];
							
							if ($value_default && $arr_object_sub_definition === null) {

								if ($is_ref_type_id) {
									$arr_object_sub_definition['object_sub_definition_ref_object_id'] = $value_default;
								} else {
									$arr_object_sub_definition['object_sub_definition_value'] = $value_default;
								}
							} else {
								continue;
							}
						} else if (!$this->insert && $this->mode == 'overwrite' && $arr_object_sub_definition === null) {
							
							$arr_object_sub_definition = ['object_sub_definition_value' => '', 'object_sub_definition_ref_object_id' => 0];
						} else {
							continue;
						}
					}
					
					$action_object_sub_definition = false;

					$is_defined = (($is_ref_type_id && ($arr_object_sub_definition['object_sub_definition_ref_object_id'] !== null || array_key_exists('object_sub_definition_ref_object_id', $arr_object_sub_definition))) || (!$is_ref_type_id && ($arr_object_sub_definition['object_sub_definition_value'] !== null || array_key_exists('object_sub_definition_value', $arr_object_sub_definition))));
					
					if ($is_defined) {
						
						$arr_object_sub_definition['object_sub_definition_value'] = self::formatToSQLValue($arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition['object_sub_definition_value']);
						
						if ($arr_object_sub_definition['object_sub_definition_value'] === false || $arr_object_sub_definition['object_sub_definition_value'] === '') {
							$arr_object_sub_definition['object_sub_definition_value'] = null;
						}
						
						/*if ($arr_object_sub_description['object_sub_description_has_multi'] && $arr_object_sub_definition['object_sub_definition_ref_object_id']) {
							$arr_object_sub_definition['object_sub_definition_ref_object_id'] = array_unique(array_filter($arr_object_sub_definition['object_sub_definition_ref_object_id']));
						} else if ($arr_object_sub_description['object_sub_description_has_multi'] && $arr_object_sub_definition['object_sub_definition_value']) {
							$arr_object_sub_definition['object_sub_definition_value'] = array_unique(array_filter($arr_object_sub_definition['object_sub_definition_value']));
						}*/
						
						if ($this->insert_sub) {
							
							if ($is_ref_type_id) {
								
								if ($arr_object_sub_definition['object_sub_definition_ref_object_id']) {
									
									// Insert new object sub definition record
									$action_object_sub_definition = 'insert';
								}
							} else {
								
								if (
									(!$arr_object_sub_description['object_sub_description_has_multi'] && $arr_object_sub_definition['object_sub_definition_value'] !== null)
									||
									($arr_object_sub_description['object_sub_description_has_multi'] && $arr_object_sub_definition['object_sub_definition_value'])
								) {
								
									// Insert new object sub definition record
									$action_object_sub_definition = 'insert';
								}
							}
						} else {
							
							$is_appendable = ($this->arr_append['all'] || (is_array($this->arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions']) && $this->arr_append['object_subs'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]));
							
							if ($is_ref_type_id) {
								
								if ($this->versioning) {
									
									$arr_compare_object_sub_definition = $arr_object_sub_definition['object_sub_definition_ref_object_id'];

									if ($arr_compare_object_sub_definition != 'last') {
										
										$arr_compare_object_sub_definition = (int)$arr_object_sub_definition['object_sub_definition_ref_object_id'];
										
										if ($is_appendable) {
											
											if ($arr_compare_object_sub_definition < 0 && $arr_cur_object_sub['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id'] == abs($arr_compare_object_sub_definition)) { // Negative object id means: remove
												
												$arr_compare_object_sub_definition = false;
												$is_appendable = false; // Allow deletion of empty object_sub_definition_ref_object_id
											}
										}
									}
									
									if ($arr_compare_object_sub_definition != $arr_cur_object_sub['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id']) {
									
										if (!$arr_object_sub_definition['object_sub_definition_ref_object_id']) {
											
											if (!$is_appendable) {
												
												// Set object sub definition record deleted flag (version 0)
												$action_object_sub_definition = 'delete';
											}
										} else {

											// Update object sub definition record, find existing version or insert new version
											foreach ($this->getTypeObjectSubDescriptionVersions($object_sub_details_id, $object_sub_id, $object_sub_description_id) as $arr_version) {
												
												if ($arr_object_sub_definition['object_sub_definition_ref_object_id'] == $arr_version['object_sub_definition_ref_object_id'] && $arr_version['object_sub_definition_version'] > 0) {
													
													$version = $arr_version['object_sub_definition_version'];
													$action_object_sub_definition = 'version';
													
													break;
												}
											}

											$action_object_sub_definition = ($action_object_sub_definition ?: 'insert');
										}
									}
								} else {
									
									if (!$arr_object_sub_definition['object_sub_definition_ref_object_id']) {
										
										$action_object_sub_definition = 'delete';
									} else {
										
										$action_object_sub_definition = 'insert';
									}
								}
							} else {
								
								if ($this->versioning) {
										
									$arr_compare_object_sub_definition = $arr_object_sub_definition['object_sub_definition_value'];
									$arr_cur_object_sub_definition = $arr_cur_object_sub['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value'];
									
									if ($arr_object_sub_description['object_sub_description_has_multi']) {	
									
									} else {
										
										if ($is_appendable) {
											$arr_object_sub_definition['object_sub_definition_value'] = self::appendToValue($arr_object_sub_description['object_sub_description_value_type'], $arr_cur_object_sub_definition, $arr_object_sub_definition['object_sub_definition_value']);
										}
										
										$arr_compare_object_sub_definition = $arr_object_sub_definition['object_sub_definition_value'];
									}	
									
									if ($arr_compare_object_sub_definition != $arr_cur_object_sub_definition || (($arr_object_sub_definition['object_sub_definition_value'] !== '' && $arr_object_sub_definition['object_sub_definition_value'] !== null) && ($arr_cur_object_sub_definition === '' || $arr_cur_object_sub_definition === null))) {

										if ($arr_object_sub_definition['object_sub_definition_value'] === '' || $arr_object_sub_definition['object_sub_definition_value'] === null || ($arr_object_sub_description['object_sub_description_has_multi'] && !$arr_object_sub_definition['object_sub_definition_value'])) {
											
											if (!$is_appendable) {
												
												// Set object definition record deleted flag (version 0)
												$action_object_sub_definition = 'delete';
											}
										} else {

											// Update object definition record, find existing version or insert new version
											foreach ($this->getTypeObjectSubDescriptionVersions($object_sub_details_id, $object_sub_id, $object_sub_description_id) as $arr_version) {
												
												if ($arr_compare_object_sub_definition == $arr_version['object_sub_definition_value'] && $arr_version['object_sub_definition_version'] > 0) {
													
													$version = $arr_version['object_sub_definition_version'];
													$action_object_sub_definition = 'version';
													
													break;
												}
											}
											
											$action_object_sub_definition = ($action_object_sub_definition ?: 'insert');
										}
									}
								} else {
									
									if ($arr_object_sub_definition['object_sub_definition_value'] === '' || $arr_object_sub_definition['object_sub_definition_value'] === null || ($arr_object_sub_description['object_sub_description_has_multi'] && !$arr_object_sub_definition['object_sub_definition_value'])) {
										
										$action_object_sub_definition = 'delete';
									} else {
										
										$action_object_sub_definition = 'insert';
									}
								}
							}
						}
						
						if ($action_object_sub_definition == 'insert') {
							
							if ($this->versioning && !$this->insert_sub) {
								
								$arr_version = $this->getTypeObjectSubDescriptionVersions($object_sub_details_id, $object_sub_id, $object_sub_description_id);
								$arr_version = current($arr_version);
								$version = ($arr_version['object_sub_definition_version'] > 0 ? $arr_version['object_sub_definition_version'] : 0) + 1;
							} else {
								
								$version = ($this->versioning ? 1 : -2);
							}
							
							if ($is_ref_type_id) {
								$value = (int)$arr_object_sub_definition['object_sub_definition_ref_object_id'];
							} else {
								$value = $arr_object_sub_definition['object_sub_definition_value'];
							}
							
							$this->addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, $version, $value);
							
						} else if ($action_object_sub_definition == 'delete') {
							
							if (!$this->versioning) {
								
								$this->arr_sql_delete['object_sub_definition_version'][] = "UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'])."
									SET active = FALSE, status = 0
									WHERE object_sub_description_id = ".$object_sub_description_id."
										AND object_sub_id = ".$object_sub_id."
										AND version >= -2
								";
							}
							
							$arr_object_sub_definition['object_sub_definition_sources'] = []; // Also remove sources
							
							$version = 0;
						}
						
						if ($action_object_sub_definition) {
							
							if ($arr_object_sub_description['object_sub_description_value_type'] == 'text' || $arr_object_sub_description['object_sub_description_value_type'] == 'text_layout' || $arr_object_sub_description['object_sub_description_value_type'] == 'text_tags') {
								
								if (!$this->insert) {
									
									$this->arr_sql_delete['object_sub_definition_objects'][] = "UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')."
											SET state = 0
										WHERE object_sub_description_id = ".$object_sub_description_id."
											AND object_sub_id = ".$object_sub_id."
									";
								}
								
								if ($action_object_sub_definition != 'delete') {
									
									$arr_object_sub_definition_text = self::parseObjectDefinitionText($arr_object_sub_definition['object_sub_definition_value']);
									
									$this->addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, -10, $arr_object_sub_definition_text['text']);
								
									$arr_sql_insert = [];
									
									foreach($arr_object_sub_definition_text['arr_tags'] as $value) {
										$arr_sql_insert[] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$value['object_id'].", ".$value['type_id'].", '".DBFunctions::strEscape($value['text'])."', ".$value['group_id'].", 1)";
									}
									
									if ($arr_sql_insert) {
										
										$this->arr_sql_insert['object_sub_definition_objects'][] = implode(',', $arr_sql_insert);
									}
								} else {
									
									$this->addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, -10, '');
								}
							}
							
							$version_log = ($this->versioning && $this->insert_sub ? -1 : $version);
							
							$this->addTypeObjectSubDescriptionVersionUser($object_sub_id, $object_sub_description_id, $version_log);
						}
					}
				}
				
				$this->handleSources('object_sub_definition', $arr_object_sub_definition['object_sub_definition_sources'], ['object_sub_id' => $object_sub_id, 'object_sub_description_id' => $object_sub_description_id, 'object_sub_details_id' => $object_sub_details_id]);
			}
		}
		
		if ($this->arr_actions) {
			
			if ($this->insert) {
				
				$this->addObjectUpdate();
			} else {
				
				$this->arr_sql_insert['object_dating'][] = "(".$this->object_id.", NOW(), NOW())"; // Update object dating with latest date
			}
		}
		
		if ($has_transaction) {
			$this->save();
		}
		
		DB::commitTransaction('store_type_objects');

		return $this->object_id;
	}
	
	protected function parseObjectSub($object_sub_details_id, $arr_object_sub) {

		$arr_object_sub_details = $this->arr_type_set['object_sub_details'][$object_sub_details_id];
		
		if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) { // Overwrite a possible existing sub-object when it's unique
			
			if ($this->object_id && !$arr_object_sub['object_sub']['object_sub_id'] && $this->arr_object_set['object_subs']) {
				
				foreach ($this->arr_object_set['object_subs'] as $cur_object_sub_id => $arr_cur_object_sub) {
					
					if ($arr_cur_object_sub['object_sub']['object_sub_details_id'] == $object_sub_details_id) {
						$arr_object_sub['object_sub']['object_sub_id'] = $cur_object_sub_id;
						break;
					}
				}
			}
		}
		$arr_object_sub['object_sub']['object_sub_id'] = (!$this->object_id ? 0 : (int)$arr_object_sub['object_sub']['object_sub_id']);
		$object_sub_id = $arr_object_sub['object_sub']['object_sub_id'];
		
		$parse_object_sub = false;
		$is_double = false;
		
		// Subobject
		
		$has_date = ($arr_object_sub['object_sub']['object_sub_date_type'] !== null || $arr_object_sub['object_sub']['object_sub_date_chronology'] !== null || $arr_object_sub['object_sub']['object_sub_date_start'] !== null || $arr_object_sub['object_sub']['object_sub_date_end'] !== null);
		$has_location = ($arr_object_sub['object_sub']['object_sub_location_type'] !== null || $arr_object_sub['object_sub']['object_sub_location_ref_object_id'] !== null);
		
		if (($arr_object_sub['object_sub']['object_sub_self'] || $has_date || $has_location) && $arr_object_sub['object_sub']['object_sub_version'] != 'deleted') { // Subobject wants to be inserted/updated
			
			$arr_cur_object_sub = $this->arr_object_set['object_subs'][$object_sub_id];
			$date_is_locked = ($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_sub_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_description_id']);
			$location_is_locked = ($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_id']);
			
			if ($date_is_locked || !$has_date) {
				
				$object_sub_date_chronology = self::formatToSQLValue('chronology', $arr_cur_object_sub['object_sub']['object_sub_date_chronology']);
			} else {
				
				$arr_chronology = [];

				if (!$arr_object_sub['object_sub']['object_sub_date_type']) {
					
					if ($arr_object_sub['object_sub']['object_sub_date_chronology']) {
						$arr_object_sub['object_sub']['object_sub_date_type'] = 'chronology';
					} else { // Make sure its point only, when applicable
						$arr_object_sub['object_sub']['object_sub_date_type'] = 'point';
					}
				}
				
				switch ($arr_object_sub['object_sub']['object_sub_date_type']) {
					case 'point':
					
						if ($arr_object_sub['object_sub']['object_sub_date_start'] === null) {
							$arr_object_sub['object_sub']['object_sub_date_start'] = '-∞';
						}
							
						$arr_chronology['start'] = [
							'start' => ['date_value' => $arr_object_sub['object_sub']['object_sub_date_start']]
						];
						
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
							
							if ($arr_object_sub['object_sub']['object_sub_date_end'] === null) {
								$arr_object_sub['object_sub']['object_sub_date_end'] = '∞';
							}
							
							$arr_chronology['end'] = [
								'end' => ['date_value' => $arr_object_sub['object_sub']['object_sub_date_end']]
							];
							
							$arr_chronology['type'] = 'period';
						} else {
							
							$arr_chronology['type'] = 'point';
						}

						break;
					case 'chronology':
						
						$arr_chronology = $arr_object_sub['object_sub']['object_sub_date_chronology'];
						$arr_chronology = ($arr_chronology && !is_array($arr_chronology) ? self::formatToChronology($arr_chronology) : $arr_chronology);
						
						if ($arr_chronology) {
							$arr_chronology['type'] = ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period'] ? 'period' : 'point');
						}
						
						break;
				}

				$object_sub_date_chronology = self::formatToSQLValue('chronology', $arr_chronology);
			}
			
			if (!$has_location) {
				
				$object_sub_location_geometry = $arr_cur_object_sub['object_sub']['object_sub_location_geometry'];
				$object_sub_location_ref_object_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'];
				$object_sub_location_ref_type_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_type_id'];
				$object_sub_location_ref_object_sub_details_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id'];
			} else {
				
				$object_sub_location_ref_object_id = $object_sub_location_ref_type_id = $object_sub_location_ref_object_sub_details_id = 0;
				$object_sub_location_geometry = '';
				if (!$arr_object_sub['object_sub']['object_sub_location_type'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_only']) { // Make sure its reference only, when applicable
					$arr_object_sub['object_sub']['object_sub_location_type'] = 'reference';
				}
				switch ($arr_object_sub['object_sub']['object_sub_location_type']) {
					case 'point':
					
						if ($arr_object_sub['object_sub']['object_sub_location_latitude'] || $arr_object_sub['object_sub']['object_sub_location_longitude']) {
							$arr_geometry = ['type' => 'Point', 'coordinates' => [(float)$arr_object_sub['object_sub']['object_sub_location_longitude'], (float)$arr_object_sub['object_sub']['object_sub_location_latitude']]];
							$object_sub_location_geometry = self::formatToSQLValue('geometry', $arr_geometry);
						} else {
							$object_sub_location_geometry = '';
						}
						
						break;
					case 'geometry':
					
						$object_sub_location_geometry = self::formatToSQLValue('geometry', $arr_object_sub['object_sub']['object_sub_location_geometry']);
						
						break;
					case 'reference':
					
						if ($location_is_locked) {
							$object_sub_location_ref_object_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'];
						} else {
							$object_sub_location_ref_object_id = $arr_object_sub['object_sub']['object_sub_location_ref_object_id'];
						}
						$object_sub_location_ref_type_id = ($arr_object_sub['object_sub']['object_sub_location_ref_type_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']);
						$object_sub_location_ref_object_sub_details_id = ($arr_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id'] ?: $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id']);
						
						break;
				}
			}

			$arr_object_sub['object_sub'] = [
				'object_sub_id' => $object_sub_id,
				'object_sub_details_id' => $object_sub_details_id,
				'object_sub_date_chronology' => $object_sub_date_chronology,
				'object_sub_location_geometry' => $object_sub_location_geometry,
				'object_sub_location_ref_object_id' => (int)$object_sub_location_ref_object_id,
				'object_sub_location_ref_type_id' => (int)$object_sub_location_ref_type_id,
				'object_sub_location_ref_object_sub_details_id' => (int)$object_sub_location_ref_object_sub_details_id,
				'object_sub_sources' => $arr_object_sub['object_sub']['object_sub_sources']
			];
			
			// Do not parse empty subobject on new insert
			if ($object_sub_id) {
				$parse_object_sub = true;
			} else if ((!$arr_object_sub_details['object_sub_details']['object_sub_details_has_date'] || $date_is_locked) && (!$arr_object_sub_details['object_sub_details']['object_sub_details_has_location'] || $location_is_locked)) {
				$parse_object_sub = true;
			} else if ($object_sub_date_chronology || $object_sub_location_geometry || $object_sub_location_ref_object_id) {
				$parse_object_sub = true;
			}
		}
		
		if ($arr_object_sub['object_sub']['object_sub_version'] == 'deleted') { // Subobject wants to be deleted
			
			$arr_object_sub = ['object_sub' => [ // Also remove any possible references to subobject definitions
				'object_sub_id' => $object_sub_id,
				'object_sub_details_id' => $object_sub_details_id,
				'object_sub_version' => 'deleted'
			]];
		} else if (!$parse_object_sub) { // Subobject itself has nothing to do, perhaps its definitions

			$arr_object_sub['object_sub'] = [
				'object_sub_id' => $object_sub_id,
				'object_sub_details_id' => $object_sub_details_id,
				'object_sub_sources' => $arr_object_sub['object_sub']['object_sub_sources']
			];
		}
		
		// Subobject definitions
		
		if ($arr_object_sub['object_sub_definitions']) {
			
			// Set key to object_sub_description_id
			$arr_object_sub['object_sub_definitions'] = array_combine(arrValuesRecursive('object_sub_description_id', $arr_object_sub['object_sub_definitions']), $arr_object_sub['object_sub_definitions']);
		} else {
			
			$arr_object_sub['object_sub_definitions'] = [];
		}
		
		$has_required = ($this->do_check && arrHasKeysRecursive('object_sub_description_is_required', $arr_object_sub_details['object_sub_descriptions'], true));
		
		if (!$object_sub_id || $has_required) { // Cleanup for empty subobject descriptions on new insert
			
			foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
				
				$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
				
				$is_ref_type_id = (bool)$arr_object_sub_description['object_sub_description_ref_type_id'];
				
				if (!$object_sub_id) {
					
					if ($is_ref_type_id) {
						if (!$arr_object_sub_definition['object_sub_definition_ref_object_id']) {
							unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
						}
					} else {
						if ($arr_object_sub_definition['object_sub_definition_value'] == '') {
							unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
						}
					}
				}
				
				if ($has_required && $arr_object_sub_description['object_sub_description_is_required']) {

					if ($arr_object_sub_definition === null) {
						
						if ($this->object_id || (!$this->object_id && $arr_object_sub_description['object_sub_description_value_type_options']['default']['value'])) {
							continue;
						}
					} else if (($is_ref_type_id && $arr_object_sub_definition['object_sub_definition_ref_object_id']) || (!$is_ref_type_id && $arr_object_sub_definition['object_sub_definition_value'])) {
						continue;						
					}
					
					Labels::setVariable('field', Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']));
					error(getLabel('msg_object_definition_is_required'));
				}
			}
		}

		// Append or discard subobjects
		
		if ($parse_object_sub || $arr_object_sub['object_sub_definitions']) {
			
			if (($this->arr_append['all'] || (is_array($this->arr_append['object_subs']) && $this->arr_append['object_subs'][$object_sub_details_id])) && $this->object_id) { // Appending and overwriting sub-objects to the existing set of sub-objects
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
				
					$arr_object_sub['object_sub']['object_sub_id'] = false;
					
					if ($this->arr_append_object_sub_details_ids[$object_sub_details_id]) {
						
						$arr_object_sub['object_sub']['object_sub_id'] = $this->arr_append_object_sub_details_ids[$object_sub_details_id];
					} else {
					
						foreach ((array)$this->arr_object_set['object_subs'] as $cur_object_sub_id => $arr_cur_object_sub) {
							if ($arr_cur_object_sub['object_sub']['object_sub_details_id'] == $object_sub_details_id) {
								$arr_object_sub['object_sub']['object_sub_id'] = $cur_object_sub_id;
								break;
							}
						}
					}
				} else if ($this->arr_append_object_sub_ids[$object_sub_id]) {
					
					$arr_object_sub['object_sub']['object_sub_id'] = $this->arr_append_object_sub_ids[$object_sub_id];
				
				} else if ($parse_object_sub) {
										
					foreach ((array)$this->arr_object_set['object_subs'] as $cur_object_sub_id => $arr_cur_object_sub) {
						
						if ($arr_cur_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
							continue;
						}
												
						$str_compare = $arr_object_sub['object_sub']['object_sub_date_chronology'].'-'.$arr_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id'].'-'.$arr_object_sub['object_sub']['object_sub_location_ref_object_id'].'-'.$arr_object_sub['object_sub']['object_sub_location_geometry'];
						$str_compare_cur = self::formatToSQLValue('chronology', $arr_cur_object_sub['object_sub']['object_sub_date_chronology']).'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] && $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] ? 0 : $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id']).'-'.$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'].'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] ? $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] : '');
							
						if ($str_compare == $str_compare_cur) {

							$is_double = true;
							
							foreach ($arr_cur_object_sub['object_sub_definitions'] as $cur_object_sub_description_id => $arr_cur_object_sub_definition) {
								
								if ($arr_object_sub_details['object_sub_descriptions'][$cur_object_sub_description_id]['object_sub_description_ref_type_id']) {
									
									if ($arr_cur_object_sub_definition['object_sub_definition_ref_object_id'] != $arr_object_sub['object_sub_definitions'][$cur_object_sub_description_id]['object_sub_definition_ref_object_id']) {
										$is_double = false;
										break;
									} 
								} else {
									
									$object_sub_definition_value = self::formatToSQLValue($arr_object_sub_details['object_sub_descriptions'][$cur_object_sub_description_id]['object_sub_description_value_type'], $arr_object_sub['object_sub_definitions'][$cur_object_sub_description_id]['object_sub_definition_value']);
									if ($arr_cur_object_sub_definition['object_sub_definition_value'] != $object_sub_definition_value) {
										$is_double = false;
										break;
									}
								}
							}
							
							if ($is_double) {
								$arr_object_sub = [];
								break;
							}
						}
					}
				}
			} else if (!$object_sub_id) {
				
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_is_single'] && $date_is_locked && $location_is_locked && !$arr_object_sub['object_sub_definitions']) { // Do not process sub-object when it's not unique and does not contain any information
					
					$arr_object_sub = [];
				}
			}
		}
		
		return $arr_object_sub;
	}
	
	public function getTypeObjectSubObjectSubDetailsID($object_sub_id) {
	
		if (!$this->stmt_object_sub_details_id) {
				
			$this->stmt_object_sub_details_id = DB::prepare("SELECT nodegoat_tos.object_sub_details_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
				WHERE nodegoat_tos.id = ".DBStatement::assign('object_sub_id', 'i')."
				LIMIT 1
			");
		}
		
		$this->stmt_object_sub_details_id->bindParameters(['object_sub_id' => $object_sub_id]);
		
		$res = $this->stmt_object_sub_details_id->execute();
		$arr_row = $res->fetchRow();

		$res->freeResult();
		
		return $arr_row[0];
	}
	
	private function handleSources($type, $arr_source_types, $arr_options = []) {
		
		if ($this->mode == 'update' && $arr_source_types === null) {
			return;
		}
		
		$arr_cur_source_types = false;
		$func_check_sources = false;
		$is_appendable = false;
		
		switch ($type) {
			case 'object':
				
				if (!$this->is_new) {
					$arr_cur_source_types = $this->arr_object_set['object']['object_sources'];
					$is_appendable = false;
				}
				
				$sql_table = DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES');
				$sql_insert = $this->object_id;
				break;
			case 'object_definition':
				
				if (!$this->is_new) {
					$arr_cur_source_types = $this->arr_object_set['object_definitions'][$arr_options['object_description_id']]['object_definition_sources'];
					$is_appendable = $this->arr_append['object_definitions'][$arr_options['object_description_id']];
				}
				
				$sql_table = DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES');
				$sql_insert = $arr_options['object_description_id'].", ".$this->object_id;
				break;
			case 'object_sub':
				
				if (!$this->is_new) {
					$arr_cur_source_types = $this->arr_object_set['object_subs'][$arr_options['object_sub_id']]['object_sub']['object_sub_sources'];
					$is_appendable = false;
				}
				
				$sql_table = DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES');
				$sql_insert = $arr_options['object_sub_id'];
				break;
			case 'object_sub_definition':

				if (!$this->is_new) {
					$arr_cur_source_types = $this->arr_object_set['object_subs'][$arr_options['object_sub_id']]['object_sub_definitions'][$arr_options['object_sub_description_id']]['object_sub_definition_sources'];
					$is_appendable = $this->arr_append['object_subs'][$arr_options['object_sub_details_id']]['object_sub_definitions'][$arr_options['object_sub_description_id']];
				}
				
				$sql_table = DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES');
				$sql_insert = $arr_options['object_sub_description_id'].", ".$arr_options['object_sub_id'];
				break;
		}
					
		if ($arr_cur_source_types) {
			
			$func_check_sources = function($ref_type_id, $ref_object_id, $link) use ($arr_cur_source_types, $type) { // Prevent duplications
				
				foreach ((array)$arr_cur_source_types[$ref_type_id] as $arr_ref_object) {

					if ($arr_ref_object[$type.'_source_ref_object_id'] == $ref_object_id && $arr_ref_object[$type.'_source_link'] == $link) {
						
						return true; // Found
					}
				}
				
				return false; // Not found
			};
			
			$is_appendable = ($is_appendable || $this->arr_append['all'] || $this->arr_append['sources']);
		}

		$arr_match = [];
		$arr_sql_insert = [];
		$arr_sql_delete = [];

		foreach ((array)$arr_source_types as $ref_type_id => $arr_source_objects) {
			
			if (!$ref_type_id) {
				continue;
			}
									
			foreach ((array)$arr_source_objects as $arr_ref_object) {
				
				$ref_object_id = $arr_ref_object[$type.'_source_ref_object_id'];
				$link = $arr_ref_object[$type.'_source_link'];
				$identifier = $ref_object_id.'_'.$link; // Prevent duplications
				
				if (!$ref_object_id || $arr_match[$identifier]) {
					continue;
				}

				if (!$arr_cur_source_types || !$func_check_sources($ref_type_id, $ref_object_id, $link)) {
					
					$arr_sql_insert[] = "(".$sql_insert.", ".(int)$ref_object_id.", ".(int)$ref_type_id.", '".DBFunctions::strEscape($link)."', ".($link ? "UNHEX('".hash('md5', $link)."')" : "''").")";
				}
				
				$arr_match[$identifier] = true;
			}
		}
		
		if ($arr_cur_source_types && !$is_appendable) {
						
			foreach ($arr_cur_source_types as $cur_ref_type_id => $arr_source_objects) {

				foreach ($arr_source_objects as $arr_ref_object) {
					
					$ref_object_id = $arr_ref_object[$type.'_source_ref_object_id'];
					$link = $arr_ref_object[$type.'_source_link'];
					$identifier = $ref_object_id.'_'.$link;
					
					if ($arr_match[$identifier]) {
						continue;
					}
					
					$arr_sql_delete[] = "(ref_object_id = ".(int)$ref_object_id." AND hash = ".($link ? "UNHEX('".hash('md5', $link)."')" : DBFunctions::castAs("''", DBFunctions::CAST_TYPE_BINARY)).")";
				}
			}
		}
		
		switch ($type) {
			case 'object':
				$sql_delete = "DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')."
								WHERE object_id = ".$this->object_id;
				break;
			case 'object_definition':
				$sql_delete = "DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')."
								WHERE object_description_id = ".$arr_options['object_description_id']."
									AND object_id = ".$this->object_id;
				break;
			case 'object_sub':
				$sql_delete = "DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')."
								WHERE object_sub_id = ".$arr_options['object_sub_id'];
				break;
			case 'object_sub_definition':
				$sql_delete = "DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')."
								WHERE object_sub_description_id = ".$arr_options['object_sub_description_id']."
									AND object_sub_id = ".$arr_options['object_sub_id'];
				break;
		}

		if ($arr_sql_insert) {
			
			$this->arr_sql_insert[$type.'_source'][] = implode(',', $arr_sql_insert);
		}
		
		if ($arr_sql_delete) {
			
			$this->arr_sql_delete[$type.'_source'][] = "
				".$sql_delete."
					AND (
						".implode(' OR ', $arr_sql_delete)."
					)
			";
		}
	}
	
	protected function addTypeObjectVersion($version, $value = null) {
		
		$sql_value = "(".($this->object_id ? $this->object_id.", " : "").$version.", ".$this->type_id.", ".($value !== null ? "'".DBFunctions::strEscape($value)."'" : 'NULL').", ".($this->versioning ? '0' : '1').")";
								
		if (!$this->object_id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
				(version, type_id, name, status)
					VALUES
				".$sql_value."
			");
			
			$this->object_id = DB::lastInsertID();
			self::$last_object_id = $this->object_id;
			
			$this->arr_sql_insert['object_dating'][] = "(".$this->object_id.", NOW(), NOW())"; // Ensure object has a dating record, even when making an object exist only (e.g. through merge)
		} else {
			
			$this->arr_sql_insert['object_version'][] = $sql_value;
		}
		
		return $this->object_id;
	}
	
	protected function addTypeObjectVersionUser($version, $user_id = false, $date = false) {
		
		$this->arr_actions['object'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_version_user'][] = "(".$this->object_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").")";
	}
	
	protected function addTypeObjectDescriptionVersion($object_description_id, $version, $value) {
		
		$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
		$is_alternate = ($version <= -10);
		$arr_sql_insert = [];
		
		if ($arr_object_description['object_description_ref_type_id']) {
			
			$count = 0;
			
			foreach ($value as $object_definition_ref_object_id) {
				
				$arr_sql_insert[] = "(".$object_description_id.", ".$this->object_id.", ".$version.", ".(int)($object_definition_ref_object_id == 'last' ? self::$last_object_id : $object_definition_ref_object_id).", ".$count.", ".($this->versioning ? '0' : '1').")";
				
				$count++;
			}
		} else {
			
			if ($arr_object_description['object_description_has_multi']) {
				
				$count = 0;
				
				foreach ($value as $object_definition_value) {
					
					$arr_sql_insert[] = "(".$object_description_id.", ".$this->object_id.", ".$version.", '".DBFunctions::strEscape($object_definition_value)."', ".$count.", ".($this->versioning || $is_alternate ? '0' : '1').")";
					
					$count++;
				}
			} else {
				
				$arr_sql_insert[] = "(".$object_description_id.", ".$this->object_id.", ".$version.", '".DBFunctions::strEscape($value)."', 0, ".($this->versioning || $is_alternate ? '0' : '1').")";
			}
		}
		
		$value_type = $arr_object_description['object_description_value_type'];
		$this->arr_sql_insert['object_definition_version'][$value_type][] = implode(',', $arr_sql_insert);
		
		if (!$this->versioning && !$this->insert) {
			
			if ($arr_object_description['object_description_ref_type_id']) {
			
				$this->arr_sql_delete['object_definition_version'][] = "DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')."
					WHERE object_description_id = ".$object_description_id." AND object_id = ".$this->object_id." AND version = ".$version."
						AND ref_object_id NOT IN (".implode(',', array_keys($arr_sql_insert)).")
				";
			} else if ($arr_object_description['object_description_has_multi']) {
				
				$this->arr_sql_delete['object_definition_version'][] = "DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type)."
					WHERE object_description_id = ".$object_description_id." AND object_id = ".$this->object_id." AND version = ".$version."
						AND ".StoreType::getValueTypeValue($value_type)." NOT IN ('".implode("','", array_keys($arr_sql_insert))."')
				";
			}
		}
	}
	
	protected function addTypeObjectDescriptionVersionUser($object_description_id, $version, $user_id = false, $date = false) {
		
		$this->arr_actions['object_definition'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_definition_version_user'][] = "(".$object_description_id.", ".$this->object_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").")";
	}
	
	protected function addTypeObjectSubVersion($object_sub_id, $object_sub_details_id, $version, $arr_value) {
		
		$sql_date_version = ($arr_value['object_sub_date_version'] ? (int)$arr_value['object_sub_date_version'] : "NULL");
		$sql_location_geometry_version = ($arr_value['object_sub_location_geometry_version'] ? (int)$arr_value['object_sub_location_geometry_version'] : "NULL");
		
		$sql_value = "(".($object_sub_id ? $object_sub_id.", " : "").$this->object_id.", ".$object_sub_details_id.", ".$version.", ".$sql_date_version.", ".$sql_location_geometry_version.", ".(int)$arr_value['object_sub_location_ref_object_id'].", ".(int)$arr_value['object_sub_location_ref_type_id'].", ".(int)$arr_value['object_sub_location_ref_object_sub_details_id'].", ".($this->versioning ? '0' : '1').")";
	
		if (!$object_sub_id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')."
				(object_id, object_sub_details_id, version, date_version, location_geometry_version, location_ref_object_id, location_ref_type_id, location_ref_object_sub_details_id, status)
					VALUES
				".$sql_value."
			");
			
			$object_sub_id = DB::lastInsertID();
		} else {
			
			$this->arr_sql_insert['object_sub_version'][] = $sql_value;
		}
		
		if ($arr_value['object_sub_date_chronology']) {
			
			$arr_sql_split = explode(';', $arr_value['object_sub_date_chronology']);

			$this->arr_sql_insert['object_sub_date_version'][] = "(".$object_sub_id.", ".$arr_sql_split[0].", ".(int)$arr_value['object_sub_date_version'].")";
			unset($arr_sql_split[0]);
			
			foreach ($arr_sql_split as $arr_sql_chronology) {
				$this->arr_sql_insert['object_sub_date_chronology_version'][] = "(".$object_sub_id.", ".$arr_sql_chronology.", TRUE, ".(int)$arr_value['object_sub_date_version'].")";
			}
		}
		
		if ($arr_value['object_sub_location_geometry']) {

			$this->arr_sql_insert['object_sub_location_geometry_version'][] = "(".$object_sub_id.", ST_GeomFromGeoJSON('".DBFunctions::strEscape($arr_value['object_sub_location_geometry'])."', 1, 0), ".(int)$arr_value['object_sub_location_geometry_version'].")";
		}
		
		return $object_sub_id;
	}
	
	protected function addTypeObjectSubVersionUser($object_sub_id, $version, $user_id = false, $date = false) {
		
		$this->arr_actions['object_sub'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_sub_version_user'][] = "(".$object_sub_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").")";
	}
	
	protected function addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, $version, $value) {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$is_alternate = ($version <= -10);
		
		$value_type = $arr_object_sub_description['object_sub_description_value_type'];
		$this->arr_sql_insert['object_sub_definition_version'][$value_type][] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$version.", ".($arr_object_sub_description['object_sub_description_ref_type_id'] ? (int)($value == 'last' ? self::$last_object_id : $value) : "'".DBFunctions::strEscape($value)."'").", ".($this->versioning || $is_alternate ? '0' : '1').")";
	}
	
	protected function addTypeObjectSubDescriptionVersionUser($object_sub_id, $object_sub_description_id, $version, $user_id = false, $date = false) {
		
		$this->arr_actions['object_sub_definition'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_sub_definition_version_user'][] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").")";
	}
	
	public function save() {

		$arr_sql_query = [];
		
		foreach ($this->arr_sql_delete as $task => $arr_sql_delete) {
			
			switch ($task) {
				case 'object_source':
				case 'object_definition_version':
				case 'object_definition_source':
				case 'object_definition_objects':
				case 'object_sub_version':
				case 'object_sub_source':
				case 'object_sub_date_chronology_version':
				case 'object_sub_definition_version':
				case 'object_sub_definition_source':
				case 'object_sub_definition_objects':
					$arr_sql_query[] = implode(';', $arr_sql_delete);
					break;
			}
		}
		
		foreach ($this->arr_sql_insert as $task => $arr_sql_insert) {
			
			switch ($task) {
				case 'object_version':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
						(id, version, type_id, name, status)
							VALUES
						".implode(',', $arr_sql_insert)."
						".(!$this->versioning ? DBFunctions::onConflict('id, version', ['name', 'status']) : "")."
					";
					break;
				case 'object_sub_version':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')."
						(id, object_id, object_sub_details_id, version, date_version, location_geometry_version, location_ref_object_id, location_ref_type_id, location_ref_object_sub_details_id, status)
							VALUES 
						".implode(',', $arr_sql_insert)."
						".(!$this->versioning ? DBFunctions::onConflict('id, version', ['date_version', 'location_geometry_version', 'location_ref_object_id', 'location_ref_type_id', 'location_ref_object_sub_details_id', 'status']) : "")."
					";
					break;
				case 'object_sub_date_version':
					
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')."
						(object_sub_id, span_period_amount, span_period_unit, span_cycle_object_id, version)
							VALUES 
						".implode(',', $arr_sql_insert)."
						".(!$this->versioning ? DBFunctions::onConflict('object_sub_id, version', ['span_period_amount', 'span_period_unit', 'span_cycle_object_id']) : '')."
					";
					break;
				case 'object_sub_date_chronology_version':
										
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')."
						(object_sub_id, offset_amount, offset_unit, cycle_object_id, cycle_direction, date_value, date_object_sub_id, date_direction, identifier, active, version)
							VALUES 
						".implode(',', $arr_sql_insert)."
						".(!$this->versioning ? DBFunctions::onConflict('object_sub_id, identifier, version', ['offset_amount', 'offset_unit', 'cycle_object_id', 'cycle_direction', 'date_value', 'date_object_sub_id', 'date_direction', 'active']) : '')."
					";
					break;
				case 'object_sub_location_geometry_version':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')."
						(object_sub_id, geometry, version)
							VALUES 
						".implode(',', $arr_sql_insert)."
						".(!$this->versioning ? DBFunctions::onConflict('object_sub_id, version', ['geometry']) : "")."
					";
					break;
				
				case 'object_version_user':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')."
						(object_id, version, user_id, date)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_id, version, user_id, date', ['object_id'])."
					";
					break;
				case 'object_definition_version':
				
					foreach ($arr_sql_insert as $value_type => $arr_sql) {
						
						$sql_value = StoreType::getValueTypeValue($value_type);
						$sql_table_affix = StoreType::getValueTypeTable($value_type);
						$sql_primary_key = ($sql_table_affix == '_references' ? 'object_description_id, object_id, ref_object_id, identifier, version' : 'object_description_id, object_id, identifier, version');
						
						$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$sql_table_affix."
							(object_description_id, object_id, version, ".$sql_value.", identifier, status)
								VALUES
							".implode(',', $arr_sql)."
							".(!$this->versioning ? DBFunctions::onConflict($sql_primary_key, [$sql_value, 'status']) : DBFunctions::onConflict($sql_primary_key, [$sql_value]))."
						";
					}
					break;
				case 'object_definition_version_user':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')."
						(object_description_id, object_id, version, user_id, date)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_description_id, object_id, version, user_id, date', ['object_id'])."
					";
					break;
				case 'object_sub_version_user':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')."
						(object_sub_id, version, user_id, date)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_id, version, user_id, date', ['object_sub_id'])."
					";
					break;
				case 'object_sub_definition_version':
				
					foreach ($arr_sql_insert as $value_type => $arr_sql) {
						
						$sql_value = StoreType::getValueTypeValue($value_type);
						$sql_table_affix = StoreType::getValueTypeTable($value_type);
						$sql_primary_key = ($sql_table_affix == '_references' ? 'object_sub_description_id, object_sub_id, ref_object_id, version' : 'object_sub_description_id, object_sub_id, version');
						
						$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$sql_table_affix."
							(object_sub_description_id, object_sub_id, version, ".$sql_value.", status)
								VALUES
							".implode(',', $arr_sql)."
							".(!$this->versioning ? DBFunctions::onConflict($sql_primary_key, [$sql_value, 'status']) : DBFunctions::onConflict($sql_primary_key, [$sql_value]))."
						";
					}
					break;
				case 'object_sub_definition_version_user':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')."
						(object_sub_description_id, object_sub_id, version, user_id, date)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_description_id, object_sub_id, version, user_id, date', ['object_sub_id'])."
					";
					break;
				
				case 'object_definition_objects':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')."
						(object_description_id, object_id, ref_object_id, ref_type_id, value, identifier, state)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_description_id, object_id, ref_object_id, identifier', false, "value = CASE state WHEN 0 THEN [value] ELSE CONCAT(value, ' ', [value]) END, state = [state]")."
					";
					break;
				case 'object_sub_definition_objects':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')."
						(object_sub_description_id, object_sub_id, ref_object_id, ref_type_id, value, identifier, state)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_description_id, object_sub_id, ref_object_id, identifier', false, "value = CASE state WHEN 0 THEN [value] ELSE CONCAT(value, ' ', [value]) END, state = [state]")."
					";
					break;
				
				case 'object_source':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')."
						(object_id, ref_object_id, ref_type_id, value, hash)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_id, ref_object_id, hash', ['object_id'])."
					";
					break;
				case 'object_definition_source':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')."
						(object_description_id, object_id, ref_object_id, ref_type_id, value, hash)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_description_id, object_id, ref_object_id, hash', ['object_id'])."
					";
					break;
				case 'object_sub_source':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')."
						(object_sub_id, ref_object_id, ref_type_id, value, hash)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_id, ref_object_id, hash', ['object_sub_id'])."
					";
					break;
				case 'object_sub_definition_source':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')."
						(object_sub_description_id, object_sub_id, ref_object_id, ref_type_id, value, hash)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_description_id, object_sub_id, ref_object_id, hash', ['object_sub_id'])."
					";
					break;
					
				case 'object_dating':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')."
						(object_id, date, date_object)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_id', ['date', 'date_object'])."
					";
					break;
			}
		}

		if ($arr_sql_query) {
			
			$res = DB::queryMulti(
				implode(';', $arr_sql_query)
			);
		}
		
		$this->arr_sql_insert = [];
		$this->arr_sql_delete = [];
	}
	
	public function commit($accept = false) {
				
		DB::startTransaction('store_type_objects');
		
		// Auto accept version if user has clearance
		if ($accept) {
						
			$this->updateTypeObjectVersion();
			$this->updateTypeObjectDescriptionVersion();
			$this->updateTypeObjectSubVersion();
			$this->updateTypeObjectSubDescriptionVersion();
		} else {
			
			$this->presetTypeObjectVersion();
			$this->presetTypeObjectDescriptionVersion();
			$this->presetTypeObjectSubVersion();
			$this->presetTypeObjectSubDescriptionVersion();
		}
		
		if (!$this->arr_actions) { // When this class has not made changes i.e. through save(), do make sure the Object is touched.
			self::touchTypeObjects($this->type_id, $this->table_name_object_updates);
		}
		
		DB::commitTransaction('store_type_objects');
	}
	
	public function discard($all = false) {
				
		DB::startTransaction('store_type_objects');
		
		$this->discardTypeObjectVersion($all);
		$this->discardTypeObjectDescriptionVersion($all);
		$this->discardTypeObjectSubVersion($all);
		$this->discardTypeObjectSubDescriptionVersion($all);
		
		if (!$this->arr_actions) { // When this class has not made changes i.e. through save(), do make sure the Object is touched.
			self::touchTypeObjects($this->type_id, $this->table_name_object_updates);
		}
		
		DB::commitTransaction('store_type_objects');
	}
	
	// Reversed Classification
	
	public function storeReversal($arr_object_self, $arr_object_definitions = [], $arr_object_filters = [], $arr_object_scopes = []) {
		
		$arr_object_self = arrParseRecursive($arr_object_self, 'trim');
		$arr_object_definitions = arrParseRecursive($arr_object_definitions, 'trim');
		$arr_object_filters = arrParseRecursive($arr_object_filters, 'trim');
		$arr_object_scopes = arrParseRecursive($arr_object_scopes, 'trim');
		
		DB::startTransaction('store_type_objects');
		
		if ($this->object_id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
				(id, type_id, name, version, active)
					VALUES
				(".$this->object_id.", ".$this->type_id.", '".DBFunctions::strEscape($arr_object_self['object_name_plain'])."', 1, TRUE)
				".DBFunctions::onConflict('id, version', ['name', 'active'])."
			");
		} else {
		
			$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
				(type_id, name, version, active)
					VALUES
				(".$this->type_id.", '".DBFunctions::strEscape($arr_object_self['object_name_plain'])."', 1, TRUE)
			");
						
			$this->object_id = DB::lastInsertID();
			$insert = true;
		}
		
		foreach ($arr_object_definitions as $value) {
			
			$res = DB::query("DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')."
					WHERE object_description_id = ".(int)$value['object_description_id']."
						AND object_id = ".$this->object_id."
			");
			
			if ($value['object_description_id']) {
			
				$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')."
					(object_description_id, object_id, ref_object_id, version, active)
						VALUES
					(".(int)$value['object_description_id'].", ".$this->object_id.", ".(int)$value['object_definition_ref_object_id'].", 1, TRUE)
				");
			}
		}
				
		foreach ($arr_object_filters as $type_id => $arr_type_filter) {
							
			$arr_type_scope = false;
			
			if ($this->arr_type_set['type']['mode'] == 1) {
				$arr_type_scope = $arr_object_scopes[$type_id];
			}
			
			$res = DB::query("DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_FILTERS')."
				WHERE ref_type_id = ".(int)$type_id."
					AND object_id = ".$this->object_id."
			");
			
			$filter = new FilterTypeObjects($type_id, 'id');
			$filter->setScope(['users' => $this->user_id]);
			$arr_type_filter = $filter->cleanupFilterInput($arr_type_filter);
				
			if ($arr_type_filter || $arr_type_scope) {
					
				$str_object = json_encode($arr_type_filter);
				$str_scope_object = json_encode($arr_type_scope);
				
				$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_FILTERS')."
					(object_id, ref_type_id, object, scope_object)
						VALUES
					(".$this->object_id.", ".(int)$type_id.", '".DBFunctions::strEscape($str_object)."', '".DBFunctions::strEscape($str_scope_object)."')
				");
			}
		}
		
		$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')."
			(object_id, date, date_object)
				VALUES
			(".$this->object_id.", NOW(), NOW())
			".DBFunctions::onConflict('object_id', ['date', 'date_object', 'status'])."
		");
		
		DB::commitTransaction('store_type_objects');

		if ($insert) {
			$this->addObjectUpdate();
		}
		
		return $this->object_id;
	}
	
	// Delete
	
	public function delTypeObject($accept) {
						
		if (!$this->versioning || ($this->arr_type_set['type']['is_reversal'] && $accept)) {
			
			$res = DB::query("
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
					"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)",
					['status' => "CASE
						WHEN active = TRUE THEN -1
						ELSE 0
					END",
					'active' => 'FALSE']
				)."
			");
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')."
				(object_id, version, user_id, date)
				SELECT nodegoat_to_updates.id, 0, ".$this->user_id.", NOW()
					FROM ".$this->table_name_object_updates." nodegoat_to_updates
					WHERE (SELECT
						CASE WHEN version = 0 THEN 1 ELSE NULL END
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
						WHERE nodegoat_to_ver.object_id = nodegoat_to_updates.id
							AND date_audited IS NULL
						ORDER BY date DESC
						LIMIT 1
					) IS NULL
			"); // Prepare for deletion when there is no deletion (0) already presetted
						
			// Auto accept version if user has clearance
			if ($accept) {
				
				$this->updateTypeObjectVersion();
			} else {
				
				$this->presetTypeObjectVersion();
			}
		}
	}
	
	// Lock
	
	public function handleLock($type, $key = false) {
		
		if (!$this->object_id) {
			return false;
		}
		
		$key = ($key ?: $this->lock_key);
		
		$table_name = DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK');
		$sql_expired = "(".$table_name.".identifier != '' AND (".$table_name.".date_updated + ".DBFunctions::interval(self::$timeout_lock, 'SECOND').") < NOW())";
		
		$sql_insert = "INSERT INTO ".$table_name."
			(object_id, type, user_id, date, date_updated, identifier)
				VALUES
			(".$this->object_id.", ".(int)$type.", ".(int)$this->user_id.", NOW(), NOW(), '".DBFunctions::strEscape($key)."')
			".DBFunctions::onConflict('object_id, type', false, "
				user_id = CASE WHEN ".$sql_expired." THEN [user_id] ELSE ".$table_name.".user_id END,
				date = CASE WHEN ".$sql_expired." THEN [date] ELSE ".$table_name.".date END,
				identifier = CASE WHEN ".$sql_expired." THEN [identifier] ELSE ".$table_name.".identifier END,
				date_updated = CASE WHEN [user_id] = ".$table_name.".user_id AND ".$table_name.".identifier = [identifier] THEN [date_updated] ELSE ".$table_name.".date_updated END
			")."					
		";
		
		$arr_res = DB::queryMulti("
			".(DB::ENGINE_IS_MYSQL ? "
				".$sql_insert.";
				
				SELECT ROW_COUNT();
			" : "
				WITH row_results AS (
					".$sql_insert."
						RETURNING CASE WHEN xmax = 0 THEN 1 ELSE 0 END AS inserted, CASE WHEN xmax::text::int > 0 THEN 1 ELSE 0 END AS updated
				)
				SELECT
					CASE
						WHEN SUM(updated) > 0 THEN 2
						WHEN SUM(inserted) > 0 THEN 1
						ELSE 0
					END AS nr_result
						FROM row_results
				;
			")."

			SELECT date, date_updated, user_id, identifier
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK')."
				WHERE object_id = ".$this->object_id."
					AND type = ".(int)$type."
			;
		");
		
		$nr_res = count($arr_res);
		$updated = $arr_res[$nr_res-2]->fetchRow()[0];

		if ($updated && $updated == 1) { // Got new lock
	
			return $key;
		} else {
			
			if ($updated == 2) { // Updated current lock
				
				return $key;
			}
			
			$arr_check = $arr_res[$nr_res-1]->fetchAssoc();
			
			if ($arr_check['user_id'] != $this->user_id) { // Locked by other user
				
				$arr_user = user_management::getUser($arr_check['user_id']);
				
				$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($this->type_id, $this->object_id, false);
				Labels::setVariable('object', current($arr_type_object_names));
				Labels::setVariable('user', $arr_user['name']);
				
				error(getLabel(($type == 2 ? 'msg_object_locked_discussion' : 'msg_object_locked'), 'L', true), TROUBLE_ERROR, LOG_CLIENT);
			} else if ($arr_check['identifier'] != $key) { // Locked by self
				
				$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($this->type_id, $this->object_id, false);
				Labels::setVariable('object', current($arr_type_object_names));
				
				error(getLabel(($type == 2 ? 'msg_object_locked_discussion_self' : 'msg_object_locked_self'), 'L', true), TROUBLE_ERROR, LOG_CLIENT);
			} else { // Update date is equal to the existing record
				
				return $key;
			}
		}
	}
	
	public function upgradeLock($type) {
		
		Mediator::attach('cleanup.program', 'remove_lock_'.$type.'_'.$this->identifier, function() use ($type) {
			
			$this->removeLock($type);
		});
		
		$key = $this->lock_key;
		$this->lock_key = '';
		
		$res = DB::query("
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK'), 'nodegoat_to_lock', 'object_id',
				"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_lock.object_id)",
				['identifier' => "''"]
			)."
				AND type = ".(int)$type."
				AND user_id = ".(int)$this->user_id."
				AND identifier = '".DBFunctions::strEscape($key)."'
		");
	}
	
	public function removeLock($type, $key = false) {
		
		$key = ($key ?: $this->lock_key);
				
		Mediator::remove('cleanup.program', 'remove_lock_'.$type.'_'.$this->identifier); // Applicable when lock was upgraded

		$res = DB::query("
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK'), 'nodegoat_to_lock', 'object_id',
				"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_lock.object_id)"
			)."
				AND nodegoat_to_lock.type = ".(int)$type."
				AND nodegoat_to_lock.user_id = ".(int)$this->user_id."
				AND nodegoat_to_lock.identifier = '".DBFunctions::strEscape($key)."'
		");
	}
	
	public function handleLockObject($key = false) {
		
		return $this->handleLock(1, $key);
	}
	
	public function upgradeLockObject() {
		
		return $this->upgradeLock(1);
	}
	
	public function removeLockObject($key = false) {
		
		return $this->removeLock(1, $key);
	}
	
	public function handleLockDiscussion($key = false) {
		
		return $this->handleLock(2, $key);
	}

	public function removeLockDiscussion($key = false) {
		
		return $this->removeLock(2, $key);
	}
	
	// Discussion
	
	public function handleDiscussion($arr_discussion) {
							
		$sql = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DISCUSSION')."
			(object_id, body, date_edited, user_id_edited)
				VALUES
			(".$this->object_id.", '".DBFunctions::strEscape($arr_discussion['object_discussion_body'])."', NOW(), ".(int)$this->user_id.")
			".DBFunctions::onConflict('object_id', ['body', 'date_edited', 'user_id_edited'])."
		;";
		
		$sql .= "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')."
			(object_id, date, date_object, date_discussion)
				VALUES
			(".$this->object_id.", NOW(), NOW(), NOW())
			".DBFunctions::onConflict('object_id', ['date', 'date_discussion'])."
		;";
		
		$res = DB::queryMulti($sql);
	}
	
	public function getDiscussion() {
		
		$arr = [];

		$res = DB::query("SELECT
			body AS object_discussion_body,
			date_edited AS object_discussion_date_edited,
			CASE
				WHEN (date_edited + ".DBFunctions::interval(6, 'SECOND').") > NOW() THEN user_id_edited
				ELSE NULL
			END AS object_discussion_user_id_edited
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DISCUSSION')."
			WHERE object_id = ".$this->object_id."
		");
						
		$arr_row = $res->fetchAssoc();
		
		return $arr_row;
	}
	
	// Versions
	
	public function setVersioning($versioning = true) {
		
		$this->versioning = ($versioning === true);
	}
	
	public function getTypeObjectVersions() {
		
		if (!$this->stmt_object_versions) {
			
			$this->stmt_object_versions = DB::prepare("SELECT
				nodegoat_to.name, nodegoat_to.version, nodegoat_to.active
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				WHERE nodegoat_to.id = ".DBStatement::assign('object_id', 'i')."
				ORDER BY nodegoat_to.version DESC
			");
		}
		
		$arr = [];
		
		$this->stmt_object_versions->bindParameters(['object_id' => $this->object_id]);
		
		$res = $this->stmt_object_versions->execute();
		
		while ($arr_row = $res->fetchRow()) {
			
			$arr[$arr_row[1]] = [
				'object_version' => $arr_row[1],
				'object_active' => $arr_row[2],
				'object_name_plain' => $arr_row[0]
			];
		}
		
		return $arr;
	}
    
	public function getTypeObjectDescriptionVersions($object_description_id, $full = false) {
		
		$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
		$is_multi = $arr_object_description['object_description_has_multi'];
		
		$stmt = $this->stmt_object_description_versions[$object_description_id];
		
		if (!$stmt) {
						
			$this->stmt_object_description_versions[$object_description_id] = DB::prepare("SELECT
				".($arr_object_description['object_description_ref_type_id'] ? "nodegoat_to_def.ref_object_id" : StoreType::getValueTypeValue($arr_object_description['object_description_value_type'])).",
				nodegoat_to_def.version, nodegoat_to_def.active
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'])." nodegoat_to_def
				WHERE nodegoat_to_def.object_id = ".DBStatement::assign('object_id', 'i')." AND nodegoat_to_def.object_description_id = ".$object_description_id."
				ORDER BY nodegoat_to_def.version DESC, nodegoat_to_def.identifier ASC
			");
			
			$stmt = $this->stmt_object_description_versions[$object_description_id];
		}
		
		$arr = [];
		
		$stmt->bindParameters(['object_id' => $this->object_id]);
		
		$res = $stmt->execute();
		
		if ($arr_object_description['object_description_ref_type_id']) {
		
			while ($arr_row = $res->fetchRow()) {
				
				$value = $arr_row[0];
				$version = $arr_row[1];
				$active = $arr_row[2];
				
				$s_arr =& $arr[$version];
				
				if (!$s_arr) {
					
					$s_arr = [
						'object_definition_version' => $version,
						'object_definition_active' => $active,
						'object_definition_ref_object_id' => ($is_multi ? [] : ''),
						'object_definition_value' => null
					];
				}
				
				if (!$value) {
					continue;
				}
				
				if ($is_multi) {
					$s_arr['object_definition_ref_object_id'][] = $value;
				} else {
					$s_arr['object_definition_ref_object_id'] = $value;
				}
			}
			
			if ($full && $arr) {
				
				$arr_objects = [];
				
				foreach ($arr as $arr_version) {
					
					if ($is_multi) {
						
						foreach ($arr_version['object_definition_ref_object_id'] as $ref_object_id) {
							$arr_objects[$ref_object_id] = $ref_object_id;
						}
					} else {
						
						$arr_objects[$arr_version['object_definition_ref_object_id']] = $arr_version['object_definition_ref_object_id'];
					}
				}
				
				$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($arr_object_description['object_description_ref_type_id'], $arr_objects);
				
				foreach ($arr as &$arr_version) {
					
					if ($is_multi) {
						
						$object_definition_value = [];
						
						foreach ($arr_version['object_definition_ref_object_id'] as $ref_object_id) {
							$object_definition_value[] = $arr_type_object_names[$ref_object_id];
						}
					} else {

						$object_definition_value = $arr_type_object_names[$arr_version['object_definition_ref_object_id']];
					}
					
					$arr_version['object_definition_value'] = $object_definition_value;
				}
			}

		} else {
			
			while ($arr_row = $res->fetchRow()) {
				
				$value = $arr_row[0];
				$version = $arr_row[1];
				$active = $arr_row[2];
				
				$s_arr =& $arr[$version];
				
				if (!$s_arr) {
					
					$s_arr = [
						'object_definition_version' => $version,
						'object_definition_active' => $active,
						'object_definition_value' => ($is_multi ? [] : '')
					];
				}
				
				if ($value === '' || $value === null) {
					continue;
				}
				
				if ($is_multi) {
					$s_arr['object_definition_value'][] = $value;
				} else {
					$s_arr['object_definition_value'] = $value;
				}
			}
		}
		
		return $arr;
	}
	
	public function getTypeObjectSubVersions($object_sub_id, $full = false) {
		
		if (!$this->stmt_object_sub_versions) {
			
			$this->stmt_object_sub_versions = DB::prepare("SELECT
				CASE WHEN nodegoat_tos_date.object_sub_id IS NOT NULL THEN ".StoreTypeObjects::formatFromSQLValue('chronology', 'nodegoat_tos_date')." ELSE '' END AS date_chronology, nodegoat_tos.date_version,
				nodegoat_tos.location_ref_object_id, nodegoat_tos.location_ref_type_id, nodegoat_tos.location_ref_object_sub_details_id, CASE WHEN nodegoat_tos_geo.object_sub_id IS NOT NULL THEN ".StoreTypeObjects::formatFromSQLValue('geometry', 'nodegoat_tos_geo.geometry')." ELSE '' END AS location_geometry, nodegoat_tos.location_geometry_version,
				nodegoat_tos.version, nodegoat_tos.active
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." nodegoat_tos_date ON (nodegoat_tos_date.object_sub_id = nodegoat_tos.id AND nodegoat_tos_date.version = nodegoat_tos.date_version)
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." nodegoat_tos_geo ON (nodegoat_tos_geo.object_sub_id = nodegoat_tos.id AND nodegoat_tos_geo.version = nodegoat_tos.location_geometry_version)
				WHERE nodegoat_tos.id = ".DBStatement::assign('object_sub_id', 'i')."
				ORDER BY nodegoat_tos.version DESC
			");
		}
		
		$arr = [];
		
		$this->stmt_object_sub_versions->bindParameters(['object_sub_id' => $object_sub_id]);
	
		$res = $this->stmt_object_sub_versions->execute();

		while ($arr_row = $res->fetchRow()) {
			
			$date_chronology = $arr_row[0];
			$date_version = $arr_row[1];
			$location_ref_object_id = $arr_row[2];
			$location_ref_type_id = $arr_row[3];
			$location_ref_object_sub_details_id = $arr_row[4];
			$location_geometry = $arr_row[5];
			$location_geometry_version = $arr_row[6];
			$version = $arr_row[7];
			$active = $arr_row[8];
			
			$arr[$version] = [
				'object_sub_version' => $version,
				'object_sub_active' => $active,
				'object_sub_date_chronology' => $date_chronology,
				'object_sub_date_version' => (int)$date_version,
				'object_sub_location_type' => ($location_ref_type_id ? 'reference' : 'geometry'),
				'object_sub_location_ref_object_id' => (int)$location_ref_object_id,
				'object_sub_location_ref_object_name' => '',
				'object_sub_location_ref_type_id' => (int)$location_ref_type_id,
				'object_sub_location_ref_object_sub_details_id' => (int)$location_ref_object_sub_details_id,
				'object_sub_location_geometry' => $location_geometry,
				'object_sub_location_geometry_version' => (int)$location_geometry_version
			];
		}
		
		if ($full && $arr) {
			
			$arr_type_object_names = [];
			
			foreach ($arr as $version => $arr_version) {
				
				$ref_object_id = $arr_version['object_sub_location_ref_object_id'];
				
				if (!$ref_object_id) {
					continue;
				}
					
				$arr_type_object_names[$arr_version['object_sub_location_ref_type_id']][$ref_object_id] = $ref_object_id;
			}
			
			foreach ($arr_type_object_names as $type_id => $value) {
				
				$arr_type_object_names[$type_id] = FilterTypeObjects::getTypeObjectNames($type_id, array_keys($value));
			}
			
			foreach ($arr as $version => &$arr_version) {
				
				$arr_version['object_sub_location_ref_object_name'] = $arr_type_object_names[$arr_version['object_sub_location_ref_type_id']][$arr_version['object_sub_location_ref_object_id']];
			}
		}
		
		return $arr;
	}
	
	public function getTypeObjectSubDescriptionVersions($object_sub_details_id, $object_sub_id, $object_sub_description_id, $full = false) {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$is_multi = $arr_object_sub_description['object_sub_description_has_multi'];
		
		$stmt = $this->stmt_object_sub_description_versions[$object_sub_description_id];
		
		if (!$stmt) {
						
			$this->stmt_object_sub_description_versions[$object_sub_description_id] = DB::prepare("SELECT
				".($arr_object_sub_description['object_sub_description_ref_type_id'] ? "nodegoat_tos_def.ref_object_id" : StoreType::getValueTypeValue($arr_object_sub_description['object_sub_description_value_type'])).",
				nodegoat_tos_def.version, nodegoat_tos_def.active
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'])." nodegoat_tos_def
				WHERE nodegoat_tos_def.object_sub_id = ".DBStatement::assign('object_sub_id', 'i')." AND nodegoat_tos_def.object_sub_description_id = ".$object_sub_description_id."
				ORDER BY nodegoat_tos_def.version DESC
			");
			
			$stmt = $this->stmt_object_sub_description_versions[$object_sub_description_id];
		}
		
		$arr = [];
		
		$stmt->bindParameters(['object_sub_id' => $object_sub_id]);
	
		$res = $stmt->execute();
		
		if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
		
			while ($arr_row = $res->fetchRow()) {
				
				$value = $arr_row[0];
				$version = $arr_row[1];
				$active = $arr_row[2];
				
				$s_arr =& $arr[$version];
				
				if (!$s_arr) {
					
					$s_arr = [
						'object_sub_definition_version' => $version,
						'object_sub_definition_active' => $active,
						'object_sub_definition_ref_object_id' => ($is_multi ? [] : ''),
						'object_sub_definition_value' => null
					];
				}
				
				if (!$value) {
					continue;
				}
				
				if ($is_multi) {
					$s_arr['object_sub_definition_ref_object_id'][] = $value;
				} else {
					$s_arr['object_sub_definition_ref_object_id'] = $value;
				}
			}
			
			if ($full && $arr) {
				
				$arr_objects = [];
				
				foreach ($arr as $arr_version) {
					
					if ($is_multi) {
						
						foreach ($arr_version['object_sub_definition_ref_object_id'] as $ref_object_id) {
							$arr_objects[$ref_object_id] = $ref_object_id;
						}
					} else {
						
						$arr_objects[$arr_version['object_sub_definition_ref_object_id']] = $arr_version['object_sub_definition_ref_object_id'];
					}
				}
				
				$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($arr_object_sub_description['object_sub_description_ref_type_id'], $arr_objects);
				
				foreach ($arr as &$arr_version) {
					
					if ($is_multi) {
						
						$object_sub_definition_value = [];
						
						foreach ($arr_version['object_sub_definition_ref_object_id'] as $ref_object_id) {
							$object_sub_definition_value[] = $arr_type_object_names[$ref_object_id];
						}
					} else {

						$object_sub_definition_value = $arr_type_object_names[$arr_version['object_sub_definition_ref_object_id']];
					}
					
					$arr_version['object_sub_definition_value'] = $object_sub_definition_value;
				}
			}

		} else {
			
			while ($arr_row = $res->fetchRow()) {
				
				$value = $arr_row[0];
				$version = $arr_row[1];
				$active = $arr_row[2];
				
				$s_arr =& $arr[$version];
				
				if (!$s_arr) {
					
					$s_arr = [
						'object_sub_definition_version' => $version,
						'object_sub_definition_active' => $active,
						'object_sub_definition_value' => ($is_multi ? [] : '')
					];
				}
				
				if ($value === '' || $value === null) {
					continue;
				}
				
				if ($is_multi) {
					$s_arr['object_sub_definition_value'][] = $value;
				} else {
					$s_arr['object_sub_definition_value'] = $value;
				}
			}
		}
		
		return $arr;
	}
	
	// Versions Users
	
	public function getTypeObjectVersionsUsers() {
				
		$arr = [];
						
		$res = DB::query("SELECT nodegoat_to_ver.version, nodegoat_to_ver.date, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_to_ver.user_id)
							WHERE nodegoat_to_ver.object_id = ".$this->object_id."
							ORDER BY CASE
								WHEN nodegoat_to_ver.version = -1 THEN 1
								ELSE nodegoat_to_ver.version
							END DESC, nodegoat_to_ver.date
		");
		
		while ($row = $res->fetchAssoc()) {
			if ($row['version'] == -1) {
				$arr[-1] = $row;
				$row['version'] = 1;
			}
			$row['name'] = ($row['id'] ? $row['name'] : 'System');
			$arr[$row['version']][] = $row;
		}
		
		return $arr;
	}
	
	public function getTypeObjectDescriptionVersionsUsers($object_description_id) {
				
		$arr = [];
						
		$res = DB::query("SELECT nodegoat_to_def_ver.version, nodegoat_to_def_ver.date, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_to_def_ver.user_id)
							WHERE nodegoat_to_def_ver.object_id = ".$this->object_id." AND nodegoat_to_def_ver.object_description_id = ".$object_description_id."
							ORDER BY CASE
								WHEN nodegoat_to_def_ver.version = -1 THEN 1
								ELSE nodegoat_to_def_ver.version
							END DESC, nodegoat_to_def_ver.date
		");
		
		while ($row = $res->fetchAssoc()) {
			if ($row['version'] == -1) {
				$arr[-1] = $row;
				$row['version'] = 1;
			}
			$row['name'] = ($row['id'] ? $row['name'] : 'System');
			$arr[$row['version']][] = $row;
		}
		
		return $arr;
	}
	
	public function getTypeObjectSubVersionsUsers($object_sub_id) {
				
		$arr = [];
						
		$res = DB::query("SELECT nodegoat_tos_ver.version, nodegoat_tos_ver.date, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')." nodegoat_tos_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_tos_ver.user_id)
							WHERE nodegoat_tos_ver.object_sub_id = ".$object_sub_id."
							ORDER BY CASE
								WHEN nodegoat_tos_ver.version = -1 THEN 1
								ELSE nodegoat_tos_ver.version
							END DESC, nodegoat_tos_ver.date
		");
		
		while ($row = $res->fetchAssoc()) {
			if ($row['version'] == -1) {
				$arr[-1] = $row;
				$row['version'] = 1;
			}
			$row['name'] = ($row['id'] ? $row['name'] : 'System');
			$arr[$row['version']][] = $row;
		}
		
		return $arr;
	}
	
	public function getTypeObjectSubDescriptionVersionsUsers($object_sub_id, $object_sub_description_id) {
				
		$arr = [];
						
		$res = DB::query("SELECT nodegoat_tos_def_ver.version, nodegoat_tos_def_ver.date, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')." nodegoat_tos_def_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_tos_def_ver.user_id)
							WHERE nodegoat_tos_def_ver.object_sub_id = ".$object_sub_id." AND nodegoat_tos_def_ver.object_sub_description_id = ".$object_sub_description_id."
							ORDER BY CASE
								WHEN nodegoat_tos_def_ver.version = -1 THEN 1
								ELSE nodegoat_tos_def_ver.version
							END DESC, nodegoat_tos_def_ver.date
		");
		
		while ($row = $res->fetchAssoc()) {
			
			if ($row['version'] == -1) {
				$arr[-1] = $row;
				$row['version'] = 1;
			}
			
			$row['name'] = ($row['id'] ? $row['name'] : 'System');
			$arr[$row['version']][] = $row;
		}
		
		return $arr;
	}
	
	// Version Update
	
	public function updateTypeObjectVersion() {
				
		if (!$this->versioning) {
			
			$table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			$res = DB::queryMulti("
				CREATE TEMPORARY TABLE IF NOT EXISTS ".$table_name_object_updates_changed." (
					id INT,
					description_id INT,
					PRIMARY KEY (id, description_id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
			
				TRUNCATE TABLE ".$table_name_object_updates_changed.";

				INSERT INTO ".$table_name_object_updates_changed."
					(SELECT nodegoat_to.id AS id, 0 AS description_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
							JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)
						WHERE nodegoat_to.version = -2
							AND nodegoat_to.status = 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
					"JOIN ".$table_name_object_updates_changed." ON (".$table_name_object_updates_changed.".id = nodegoat_to.id)",
					['active' => "CASE 
						WHEN nodegoat_to.version = -2 THEN TRUE
						ELSE FALSE
					END",
					'status' => '0']
				)."
					AND nodegoat_to.version >= -2
				;
			");
		} else {
		
			$res = DB::queryMulti("
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
					"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)",
					['active' => 'FALSE', 'status' => '0']
				)."
					AND EXISTS (SELECT TRUE
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')."
						WHERE object_id = nodegoat_to.id AND date_audited IS NULL
						ORDER BY date DESC
						LIMIT 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
					"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)",
					['active' => 'TRUE']
				)."
					AND nodegoat_to.version = (SELECT
						CASE
							WHEN version = -1 THEN 1
							ELSE version
						END AS version
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')."
						WHERE object_id = nodegoat_to.id AND date_audited IS NULL
						ORDER BY date DESC, version DESC
						LIMIT 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION'), 'nodegoat_to_ver', 'object_id',
					"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_ver.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
				)."
					AND nodegoat_to_ver.date_audited IS NULL
				;
			");
		}
	}
	
	public function updateTypeObjectDescriptionVersion() {
				
		$sql = '';
				
		if (!$this->versioning) {
			
			$table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			foreach (StoreType::getValueTypeTables() as $table) {
				
				$sql .= "
					TRUNCATE TABLE ".$table_name_object_updates_changed.";
					
					INSERT INTO ".$table_name_object_updates_changed."
						(SELECT nodegoat_to_def.object_id AS id, nodegoat_to_def.object_description_id AS description_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table." nodegoat_to_def
								JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def.object_id)
							WHERE nodegoat_to_def.version = -2
								AND nodegoat_to_def.status = 1
						)
						".DBFunctions::onConflict('id, description_id', ['id'])."
					; # Use conflict clause because ref_object_id could return multiple results for one object description
					
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table, 'nodegoat_to_def', ['object_id', 'object_description_id'],
						"JOIN ".$table_name_object_updates_changed." ON (".$table_name_object_updates_changed.".id = nodegoat_to_def.object_id AND ".$table_name_object_updates_changed.".description_id = nodegoat_to_def.object_description_id)",
						['active' => "CASE 
							WHEN nodegoat_to_def.version = -2 THEN TRUE
							ELSE FALSE
						END",
						'status' => '0']
					)."
						AND nodegoat_to_def.version >= -2
					;
				";
			}
		} else {
			
			foreach (StoreType::getValueTypeTables() as $table) {
		
				$sql .= "
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table, 'nodegoat_to_def', 'object_id',
						"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def.object_id)",
						['active' => 'FALSE', 'status' => '0']
					)."
						AND EXISTS (SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')."
							WHERE object_description_id = nodegoat_to_def.object_description_id AND object_id = nodegoat_to_def.object_id AND date_audited IS NULL
							ORDER BY date DESC
							LIMIT 1
						)
					;
					
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table, 'nodegoat_to_def', 'object_id',
						"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def.object_id)",
						['active' => 'TRUE']
					)."
						AND nodegoat_to_def.version = (SELECT
							CASE
								WHEN version = -1 THEN 1
								ELSE version
							END AS version
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')."
							WHERE object_description_id = nodegoat_to_def.object_description_id AND object_id = nodegoat_to_def.object_id AND date_audited IS NULL
							ORDER BY date DESC, version DESC
							LIMIT 1
						)
					;
				";
			}
			
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION'), 'nodegoat_to_def_ver', 'object_id',
					"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def_ver.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
				)."
					AND nodegoat_to_def_ver.date_audited IS NULL
				;
			";
		}
		
		$res = DB::queryMulti($sql);
	}
	
	public function updateTypeObjectSubVersion() {
		
		if (!$this->versioning) {
			
			$table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			$res = DB::queryMulti("
				TRUNCATE TABLE ".$table_name_object_updates_changed.";

				INSERT INTO ".$table_name_object_updates_changed."
					(SELECT nodegoat_tos.id AS id, 0 AS description_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
							JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)
						WHERE nodegoat_tos.version = -2
							AND nodegoat_tos.status = 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'nodegoat_tos', 'id',
					"JOIN ".$table_name_object_updates_changed." ON (".$table_name_object_updates_changed.".id = nodegoat_tos.id)",
					['active' => "CASE 
						WHEN nodegoat_tos.version = -2 THEN TRUE
						ELSE FALSE
					END",
					'status' => '0']
				)."
					AND nodegoat_tos.version >= -2
				;
			");
		} else {
		
			$res = DB::queryMulti("
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'nodegoat_tos', 'object_id',
					"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
					['active' => 'FALSE', 'status' => '0']
				)."
					AND EXISTS (SELECT TRUE
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')."
						WHERE object_sub_id = nodegoat_tos.id AND date_audited IS NULL
						ORDER BY date DESC
						LIMIT 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'nodegoat_tos', 'object_id',
					"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
					['active' => 'TRUE']
				)."
					AND nodegoat_tos.version = (SELECT
						CASE
							WHEN version = -1 THEN 1
							ELSE version
						END AS version
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')."
						WHERE object_sub_id = nodegoat_tos.id AND date_audited IS NULL
						ORDER BY date DESC, version DESC
						LIMIT 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION'), 'nodegoat_tos_ver', 'object_sub_id',
					"JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_ver.object_sub_id)
					JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
				)."
					AND nodegoat_tos_ver.date_audited IS NULL
				;
			");
		}
	}
	
	public function updateTypeObjectSubDescriptionVersion() {
				
		$sql = '';
				
		if (!$this->versioning) {
			
			$table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			foreach (StoreType::getValueTypeTables() as $table) {
				
				$sql .= "
					TRUNCATE TABLE ".$table_name_object_updates_changed.";
					
					INSERT INTO ".$table_name_object_updates_changed."
						(SELECT nodegoat_tos_def.object_sub_id AS id, nodegoat_tos_def.object_sub_description_id AS description_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table." nodegoat_tos_def
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
								JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)
							WHERE nodegoat_tos_def.version = -2
								AND nodegoat_tos_def.status = 1
						)
					;
					
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table, 'nodegoat_tos_def', ['object_sub_id', 'object_sub_description_id'],
						"JOIN ".$table_name_object_updates_changed." ON (".$table_name_object_updates_changed.".id = nodegoat_tos_def.object_sub_id AND ".$table_name_object_updates_changed.".description_id = nodegoat_tos_def.object_sub_description_id)",
						['active' => "CASE 
							WHEN nodegoat_tos_def.version = -2 THEN TRUE
							ELSE FALSE
						END",
						'status' => '0']
					)."
						AND nodegoat_tos_def.version >= -2
					;
				";
			}
		} else {

			foreach (StoreType::getValueTypeTables() as $table) {
		
				$sql .= "
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table, 'nodegoat_tos_def', 'object_sub_id',
						"JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
						JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
						['active' => 'FALSE', 'status' => '0']
					)."
						AND EXISTS (SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')."
							WHERE object_sub_description_id = nodegoat_tos_def.object_sub_description_id AND object_sub_id = nodegoat_tos_def.object_sub_id AND date_audited IS NULL
							ORDER BY date DESC
							LIMIT 1
						)
					;
					
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table, 'nodegoat_tos_def', 'object_sub_id',
						"JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
						JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
						['active' => 'TRUE']
					)."
						AND nodegoat_tos_def.version = (SELECT
							CASE
								WHEN version = -1 THEN 1
								ELSE version
							END AS version
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')."
							WHERE object_sub_description_id = nodegoat_tos_def.object_sub_description_id AND object_sub_id = nodegoat_tos_def.object_sub_id AND date_audited IS NULL
							ORDER BY date DESC, version DESC
							LIMIT 1
						)
					;
				";
			}
			
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION'), 'nodegoat_tos_def_ver', 'object_sub_id',
					"JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ver.object_sub_id)
					JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
				)."
					AND nodegoat_tos_def_ver.date_audited IS NULL
				;
			";
		}
		
		$res = DB::queryMulti($sql);
	}
	
	public function presetTypeObjectVersion() {
		
		$res = DB::query("
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				[
					"JOIN (SELECT
						nodegoat_to_updates.id,
						(SELECT
							CASE
								WHEN nodegoat_to_ver_last.version = -1 THEN 1
								ELSE nodegoat_to_ver_last.version
							END AS version
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver_last
							WHERE nodegoat_to_ver_last.object_id = nodegoat_to_updates.id
								AND date_audited IS NULL
							ORDER BY date DESC, nodegoat_to_ver_last.version DESC
							LIMIT 1
						) AS last_version,
						(SELECT nodegoat_to_active.active
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_active
							WHERE nodegoat_to_active.id = nodegoat_to_updates.id
								AND nodegoat_to_active.active = TRUE
						) AS has_active
						FROM ".$this->table_name_object_updates." nodegoat_to_updates 
					) nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)",
					'nodegoat_to_updates.last_version, nodegoat_to_updates.has_active'
				],
				['status' => "(CASE
					WHEN last_version = nodegoat_to.version AND has_active IS NULL THEN 1
					WHEN last_version = nodegoat_to.version AND nodegoat_to.active = FALSE OR (last_version != 0 AND last_version != nodegoat_to.version AND nodegoat_to.active = TRUE) THEN 2
					WHEN last_version = 0 AND (nodegoat_to.active = TRUE OR (has_active IS NULL AND nodegoat_to.status > 0)) THEN 3
					ELSE 0
				END)"]
			)."
		");
	}
	
	public function presetTypeObjectDescriptionVersion() {
				
		$sql = '';
		
		foreach (StoreType::getValueTypeTables() as $table) {
			
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table, 'nodegoat_to_def', ['object_id', 'object_description_id'],
					[
						"JOIN (SELECT
							nodegoat_to_updates.id,
							nodegoat_to_def.object_description_id,
							(SELECT
								CASE
									WHEN nodegoat_to_def_ver_last.version = -1 THEN 1
									ELSE nodegoat_to_def_ver_last.version
								END AS version
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver_last
								WHERE nodegoat_to_def_ver_last.object_id = nodegoat_to_def.object_id
									AND nodegoat_to_def_ver_last.object_description_id = nodegoat_to_def.object_description_id
									AND date_audited IS NULL
								ORDER BY date DESC, nodegoat_to_def_ver_last.version DESC
								LIMIT 1
							) AS last_version,
							(SELECT nodegoat_to_active.active
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_active
								WHERE nodegoat_to_active.id = nodegoat_to_updates.id
									AND nodegoat_to_active.active = TRUE
							) AS has_active
							FROM ".$this->table_name_object_updates." nodegoat_to_updates 
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to_updates.id)
						) nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def.object_id AND nodegoat_to_updates.object_description_id = nodegoat_to_def.object_description_id)",
						'nodegoat_to_updates.last_version, nodegoat_to_updates.has_active'
					],
					['status' => "(CASE
						WHEN last_version = nodegoat_to_def.version AND has_active IS NULL THEN 1
						WHEN last_version = nodegoat_to_def.version AND nodegoat_to_def.active = FALSE OR (last_version != 0 AND last_version != nodegoat_to_def.version AND nodegoat_to_def.active = TRUE) THEN 2
						WHEN last_version = 0 AND (nodegoat_to_def.active = TRUE OR (has_active IS NULL AND nodegoat_to_def.status > 0)) THEN 3
						ELSE 0
					END)"]
				)."
			;";
		}
		
		$res = DB::queryMulti($sql);
	}
	
	public function presetTypeObjectSubVersion() {
				
		$res = DB::query("
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'nodegoat_tos', ['object_id', 'id'],
				[
					"JOIN (SELECT
						nodegoat_to_updates.id,
						nodegoat_tos.id AS object_sub_id,
						(SELECT
							CASE
								WHEN nodegoat_tos_ver_last.version = -1 THEN 1
								ELSE nodegoat_tos_ver_last.version
							END AS version
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')." nodegoat_tos_ver_last
							WHERE nodegoat_tos_ver_last.object_sub_id = nodegoat_tos.id
								AND date_audited IS NULL
							ORDER BY date DESC, nodegoat_tos_ver_last.version DESC
							LIMIT 1
						) AS last_version,
						(SELECT nodegoat_tos_active.active
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos_active
							WHERE nodegoat_tos_active.id = nodegoat_tos.id
								AND nodegoat_tos_active.active = TRUE
						) AS has_active
						FROM ".$this->table_name_object_updates." nodegoat_to_updates 
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_updates.id)
					) nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id AND nodegoat_to_updates.object_sub_id = nodegoat_tos.id)",
					'nodegoat_to_updates.last_version, nodegoat_to_updates.has_active'
				],
				['status' => "(CASE
					WHEN last_version = nodegoat_tos.version AND has_active IS NULL THEN 1
					WHEN last_version = nodegoat_tos.version AND nodegoat_tos.active = FALSE OR (last_version != 0 AND last_version != nodegoat_tos.version AND nodegoat_tos.active = TRUE) THEN 2
					WHEN last_version = 0 AND (nodegoat_tos.active = TRUE OR (has_active IS NULL AND nodegoat_tos.status > 0)) THEN 3
					ELSE 0
				END)"]
			)."
		");
	}
	
	public function presetTypeObjectSubDescriptionVersion() {
				
		$sql = '';
		
		foreach (StoreType::getValueTypeTables() as $table) {
			
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table, 'nodegoat_tos_def', ['object_sub_id', 'object_sub_description_id'],
					[
						"JOIN (SELECT
							nodegoat_tos.id AS object_sub_id,
							nodegoat_tos_def.object_sub_description_id,
							(SELECT
								CASE
									WHEN nodegoat_tos_def_ver_last.version = -1 THEN 1
									ELSE nodegoat_tos_def_ver_last.version
								END AS version
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')." nodegoat_tos_def_ver_last
								WHERE nodegoat_tos_def_ver_last.object_sub_id = nodegoat_tos_def.object_sub_id
									AND nodegoat_tos_def_ver_last.object_sub_description_id = nodegoat_tos_def.object_sub_description_id
									AND date_audited IS NULL
								ORDER BY date DESC, nodegoat_tos_def_ver_last.version DESC
								LIMIT 1
							) AS last_version,
							(SELECT nodegoat_to_active.active
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_active
								WHERE nodegoat_to_active.id = nodegoat_to_updates.id
									AND nodegoat_to_active.active = TRUE
							) AS has_active
							FROM ".$this->table_name_object_updates." nodegoat_to_updates 
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_updates.id)
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id)
						) nodegoat_to_updates ON (nodegoat_to_updates.object_sub_id = nodegoat_tos_def.object_sub_id AND nodegoat_to_updates.object_sub_description_id = nodegoat_tos_def.object_sub_description_id)",
						'nodegoat_to_updates.last_version, nodegoat_to_updates.has_active'
					],
					['status' => "(CASE
						WHEN last_version = nodegoat_tos_def.version AND has_active IS NULL THEN 1
						WHEN last_version = nodegoat_tos_def.version AND nodegoat_tos_def.active = FALSE OR (last_version != 0 AND last_version != nodegoat_tos_def.version AND nodegoat_tos_def.active = TRUE) THEN 2
						WHEN last_version = 0 AND (nodegoat_tos_def.active = TRUE OR (has_active IS NULL AND nodegoat_tos_def.status > 0)) THEN 3
						ELSE 0
					END)"]
				)."
			;";
		}
		
		$res = DB::queryMulti($sql);
	}
	
	public function discardTypeObjectVersion($all = false) {
				
		$res = DB::query("
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION'), 'nodegoat_to_ver', 'object_id',
				[
					"JOIN (SELECT
						nodegoat_to_updates.id,
						(SELECT nodegoat_to_ver_last.version
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver_last
							WHERE nodegoat_to_ver_last.object_id = nodegoat_to_updates.id
								AND nodegoat_to_ver_last.date_audited IS NULL
							ORDER BY nodegoat_to_ver_last.date DESC, nodegoat_to_ver_last.version DESC
							LIMIT 1
						) AS last_version
						FROM ".$this->table_name_object_updates." nodegoat_to_updates 
					) nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_ver.object_id)",
					'nodegoat_to_updates.last_version'
				],
				['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
			)."
				AND nodegoat_to_ver.date_audited IS NULL
				".(!$all ? "AND nodegoat_to_ver.version = last_version" : "")."
		");
		
		$this->presetTypeObjectVersion();
	}
	
	public function discardTypeObjectDescriptionVersion($all = false) {
		
		$sql = '';
		
		foreach (StoreType::getValueTypeTables() as $table) {
				
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION'), 'nodegoat_to_def_ver', ['object_id', 'object_description_id'],
					[
						"JOIN (SELECT
							nodegoat_to_updates.id,
							nodegoat_to_def.object_description_id,
							(SELECT nodegoat_to_def_ver_last.version
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver_last
								WHERE nodegoat_to_def_ver_last.object_id = nodegoat_to_def.object_id
									AND nodegoat_to_def_ver_last.object_description_id = nodegoat_to_def.object_description_id
									AND nodegoat_to_def_ver_last.date_audited IS NULL
								ORDER BY nodegoat_to_def_ver_last.date DESC, nodegoat_to_def_ver_last.version DESC
								LIMIT 1
							) AS last_version
							FROM ".$this->table_name_object_updates." nodegoat_to_updates
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to_updates.id AND ".GenerateTypeObjects::generateVersioning('full', 'record', 'nodegoat_to_def').")
						) nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def_ver.object_id AND nodegoat_to_updates.object_description_id = nodegoat_to_def_ver.object_description_id)",
						'nodegoat_to_updates.last_version'
					],
					['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
				)."
					AND nodegoat_to_def_ver.date_audited IS NULL
					".(!$all ? "AND nodegoat_to_def_ver.version = last_version" : "")."
			;";
		}
		
		$res = DB::queryMulti($sql);
		
		$this->presetTypeObjectDescriptionVersion();
	}
	
	public function discardTypeObjectSubVersion($all = false) {
		
		$res = DB::query("
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION'), 'nodegoat_tos_ver', 'object_sub_id',
				[
					"JOIN (SELECT
						nodegoat_to_updates.id,
						nodegoat_tos.id AS object_sub_id,
						(SELECT nodegoat_tos_ver_last.version
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')." nodegoat_tos_ver_last
							WHERE nodegoat_tos_ver_last.object_sub_id = nodegoat_tos.id
								AND date_audited IS NULL
							ORDER BY date DESC, nodegoat_tos_ver_last.version DESC
							LIMIT 1
						) AS last_version
						FROM ".$this->table_name_object_updates." nodegoat_to_updates 
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_updates.id AND ".GenerateTypeObjects::generateVersioning('full', 'object_sub', 'nodegoat_tos').")
					) nodegoat_to_updates ON (nodegoat_to_updates.object_sub_id = nodegoat_tos_ver.object_sub_id)",
					'nodegoat_to_updates.last_version'
				],
				['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
			)."
				AND nodegoat_tos_ver.date_audited IS NULL
				".(!$all ? "AND nodegoat_tos_ver.version = last_version" : "")."
		");
		
		$this->presetTypeObjectSubVersion();
	}
	
	public function discardTypeObjectSubDescriptionVersion($all = false) {
				
		$sql = '';
		
		foreach (StoreType::getValueTypeTables() as $table) {
			
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION'), 'nodegoat_tos_def_ver', ['object_sub_id', 'object_sub_description_id'],
					[
						"JOIN (SELECT
							nodegoat_tos.id AS object_sub_id,
							nodegoat_tos_def.object_sub_description_id,
							(SELECT nodegoat_tos_def_ver_last.version
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')." nodegoat_tos_def_ver_last
								WHERE nodegoat_tos_def_ver_last.object_sub_id = nodegoat_tos_def.object_sub_id
									AND nodegoat_tos_def_ver_last.object_sub_description_id = nodegoat_tos_def.object_sub_description_id
									AND date_audited IS NULL
								ORDER BY date DESC, nodegoat_tos_def_ver_last.version DESC
								LIMIT 1
							) AS last_version
							FROM ".$this->table_name_object_updates." nodegoat_to_updates 
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_updates.id)
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND ".GenerateTypeObjects::generateVersioning('full', 'record', 'nodegoat_tos_def').")
						) nodegoat_to_updates ON (nodegoat_to_updates.object_sub_id = nodegoat_tos_def_ver.object_sub_id AND nodegoat_to_updates.object_sub_description_id = nodegoat_tos_def_ver.object_sub_description_id)",
						'nodegoat_to_updates.last_version'
					],
					['user_id_audited' => $this->user_id, 'date_audited' => 'NOW()']
				)."
					AND nodegoat_tos_def_ver.date_audited IS NULL
					".(!$all ? "AND nodegoat_tos_def_ver.version = last_version" : "")."
			;";
		}
		
		$res = DB::queryMulti($sql);
		
		$this->presetTypeObjectSubDescriptionVersion();
	}
	
	public function cleanupTypeObjects() {
				
		self::cleanupObjects($this->table_name_object_updates);
	}
	
	public static function clearTypeObjects($type_id) {
	
		$res = DB::queryMulti("
			UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
					SET status = 0
				WHERE type_id = ".(int)$type_id."
			;
				
			UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
					SET active = FALSE, status = -1
				WHERE type_id = ".(int)$type_id."
					AND active = TRUE
			;
		");
	}
	
	public static function cleanupObjects($table_name = false) {
		
		if (!$table_name) {
			
			$table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_to_temp_del');
			
			DB::queryMulti("
				DROP TEMPORARY TABLE IF EXISTS ".$table_name.";
				
				CREATE TEMPORARY TABLE ".$table_name." (
					PRIMARY KEY (id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)." AS (
					SELECT id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						WHERE NOT EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_check
							WHERE nodegoat_to_check.id = nodegoat_to.id
								AND (nodegoat_to_check.active = TRUE OR nodegoat_to_check.status > 0)
						)
						GROUP BY id
				);
			");
		}
		
		DB::query("
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				"JOIN ".$table_name." nodegoat_to_del ON (nodegoat_to_del.id = nodegoat_to.id)"
			)."
		");
	}
	
	protected function addObjectUpdate() {
				
		if (!$this->stmt_object_updates) {
			$this->generateObjectUpdatesTable();	
		}
				
		$this->stmt_object_updates->bindParameters(['object_id' => $this->object_id]);
		$this->stmt_object_updates->execute();
	}
	
	protected function generateObjectUpdatesTable() {
			
		DB::queryMulti("				
			CREATE TEMPORARY TABLE IF NOT EXISTS ".$this->table_name_object_updates." (
				id INT,
				PRIMARY KEY (id)
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
			
			TRUNCATE TABLE ".$this->table_name_object_updates.";
		");
		
		$this->stmt_object_updates = DB::prepare("INSERT INTO ".$this->table_name_object_updates." (id)
			SELECT id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
				WHERE type_id = ".$this->type_id." AND id = ".DBStatement::assign('object_id', 'i')."
			".DBFunctions::onConflict('id', false)."
		");
	}

	// Direct calls
	
	public static function touchTypeObjects($type_id, $sql_table) {
			
		$res = DB::query("
			INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')."
				(object_id, date, date_object)
				(SELECT
					id, NOW(), NOW()
						FROM ".$sql_table."
				)
				".DBFunctions::onConflict('object_id', ['date'])."
		");
	}
	
	public static function getTypeUsers($type_id, $arr_users) {
		
		$sql_users = ($arr_users ? "IN (".implode(',', $arr_users).")" : '');
				
		$arr = [];

		$arr_res = DB::queryMulti("
			SELECT DISTINCT nodegoat_to_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_ver.object_id)
				WHERE nodegoat_to.type_id = ".(int)$type_id."
					".($sql_users ? "AND nodegoat_to_ver.user_id ".$sql_users : "")."
			;
			
			SELECT DISTINCT nodegoat_to_def_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ver.object_id)
				WHERE nodegoat_to.type_id = ".(int)$type_id."
					".($sql_users ? "AND nodegoat_to_def_ver.user_id ".$sql_users : "")."
			;
			
			SELECT DISTINCT nodegoat_tos_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')." nodegoat_tos_ver
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_ver.object_sub_id)
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
				WHERE nodegoat_to.type_id = ".(int)$type_id."
					".($sql_users ? "AND nodegoat_tos_ver.user_id ".$sql_users : "")."
			;

			SELECT DISTINCT nodegoat_tos_def_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')." nodegoat_tos_def_ver
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ver.object_sub_id)
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
				WHERE nodegoat_to.type_id = ".(int)$type_id."
					".($sql_users ? "AND nodegoat_tos_def_ver.user_id ".$sql_users : "")."
			;
		");
		
		foreach ($arr_res as $res) {
			
			while ($row = $res->fetchRow()) {
				$arr[$row[0]] = $row[0];
			}
		}
		
		return $arr;
	}
	
	public static function getActiveUsers($arr_users) {
		
		$sql_users = "IN (".implode(',', $arr_users).")";
				
		$arr = [];

		$arr_res = DB::queryMulti("
			SELECT DISTINCT nodegoat_to_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
				WHERE nodegoat_to_ver.user_id ".$sql_users."
			;
			
			SELECT DISTINCT nodegoat_to_def_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver
				WHERE nodegoat_to_def_ver.user_id ".$sql_users."
			;
			
			SELECT DISTINCT nodegoat_tos_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')." nodegoat_tos_ver
				WHERE nodegoat_tos_ver.user_id ".$sql_users."
			;

			SELECT DISTINCT nodegoat_tos_def_ver.user_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')." nodegoat_tos_def_ver
				WHERE nodegoat_tos_def_ver.user_id ".$sql_users."
			;
		");
		
		foreach ($arr_res as $res) {
			
			while ($row = $res->fetchRow()) {
				$arr[$row[0]] = $row[0];
			}
		}
		
		return $arr;
	}

	public static function parseObjectDefinitionText($value) {

		$value = preg_replace_callback(
			'/\[object=([0-9_\|]+)\]/si',
			function ($matches) {
				return '<span class="tag" data-ids="'.$matches[1].'">'; 
			},
			$value
		);
		$value = preg_replace('/\[\/object\]/si', '</span>', $value);
		
		$text = strip_tags($value);
		
		$arr = [];
		$html = FormatHTML::openHTMLDocument($value);
		$spans = $html->getElementsByTagName('span');
		foreach ($spans as $span) {		
			$ids = $span->getAttribute('data-ids');
			if ($ids) {
				$arr_ids = explode('|', $ids);
				foreach ($arr_ids as $arr_type_object_id) {
					$arr_id = explode('_', $arr_type_object_id);
					$arr[] = ['type_id' => (int)$arr_id[0], 'object_id' => (int)$arr_id[1], 'group_id' => (int)$arr_id[2], 'text' => $span->nodeValue];
				}
			}
		}

		/*preg_replace_callback(
			'/\[object=([0-9_\|]+)\](.*?)\[\/object\]/si',
			function ($matches) use (&$arr) {
				$arr_ids = explode("|", $matches[1]);
				foreach ($arr_ids as $arr_type_object_id) {
					$arr_id = explode("_", $arr_type_object_id);
					$arr[] = array('type_id' => (int)$arr_id[0], 'object_id' => (int)$arr_id[1], 'group_id' => (int)$arr_id[2], 'text' => $matches[2]);
				}
			},
			$value
		);*/

		return ['text' => $text, 'arr_tags' => $arr];
	}
	
	
	public static function appendToValue($type, $value, $value_append) {
		
		switch ($type) {
			case 'text':
			case 'text_layout':
			case 'text_tags':
				if ($value) {
					$format = $value.($value_append ? PHP_EOL.$value_append : '');
				} else {
					$format = $value_append;
				}
				break;
			case '':
				if ($value) {
					$format = $value.($value_append ? ' '.$value_append : '');
				} else {
					$format = $value_append;
				}
				break;
			default:
				$format = $value_append;
		}
		
		return $format;
	}
	
	public static function formatToCleanValue($type, $value, $arr_type_options = []) { // From raw database to display
			
		switch ($type) {
			case 'date':
				$format = ($value ? self::int2Date($value) : '');
				break;
			case 'chronology':
				$format = self::formatToChronologyDetails($value);
				break;
			case 'geometry':
				$format = self::formatToGeometrySummary($value);
				break;
			case 'boolean':
				$format = ($value !== '' && $value !== null ? ($value == 1 || $value === 'yes' ? getLabel('lbl_yes') : getLabel('lbl_no')) : '');
				break;
			default:
				$format = $value;
		}
		
		return $format;
	}
	
	public static function formatToPreviewValue($type, $value, $arr_type_options = [], $extra = false) { // From formatted database to preview display
		
		switch ($type) {
			case 'int':
			case '':
			
				if (is_array($value)) {
					$format = implode(($arr_type_options['separator'] ?: ' '), $value);
				} else {
					$format = $value;
				}
				
				$format = htmlspecialchars($format);
				
				break;
			case 'media':
			case 'media_external':
				
				if ($value) {
					
					$show_url = ($arr_type_options['display'] && $arr_type_options['display'] == 'url');
					
					if (is_array($value)) {
						
						$arr_html = [];
							
						foreach ($value as $media) {
							
							$media = new EnucleateMedia($media, true);
							
							if ($show_url) {
								$str_url = $media->enucleate(true);
								$str_url = htmlspecialchars($str_url);
								$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
							} else {
								$arr_html[] = $media->enucleate();
							}
						}
						
						$format = implode(($show_url ? ($arr_type_options['separator'] ?: ', ') : ''), $arr_html);
					} else {
								
						$media = new EnucleateMedia($value, true);
						
						if ($show_url) {
							$str_url = $media->enucleate(true);
							$str_url = htmlspecialchars($str_url);
							$format = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
						} else {
							$format = $media->enucleate();
						}
					}
				}
				break;
			case 'external':
				
				if (is_array($value)) {
						
					$arr_html = [];
					
					foreach ($value as $ref_value) {
							
						$arr_html[] = $ref_value;
					}
					
					$format = implode(($arr_type_options['separator'] ?: ', '), $arr_html);
				} else {
					
					$format = $value;
				}
				
				break;
			case 'text':
			
				if (mb_strlen($value) > 500) {
					$value = mb_substr($value, 0, 500).' [...]';
				}
				
				$format = parseBody($value);
			case 'text_layout':
			case 'text_tags':
			
				if (mb_strlen($value) > 500) {
					$value = mb_substr($value, 0, 500).' [...]';
				}
				
				$format = parseBody($value);
				
				if ($format) {
					$format = Response::addParsePost($format, ['strip' => true]);
				}
				break;
			default:
			
				$format = self::formatToPresentationValue($type, $value, $arr_type_options, $extra);
		}
		
		return $format;
	}
	
	public static function formatToPresentationValue($type, $value, $arr_type_options = [], $extra = false) { // From formatted database to display
		
		switch ($type) {
			case 'int':
			case '':
				
				if (is_array($value)) {
					$format = '<span>'.implode('</span><span class="separator">'.($arr_type_options['separator'] ?: ' ').'</span><span>', arrParseRecursive($value, 'htmlspecialchars')).'</span>';
				} else {
					$format = htmlspecialchars($value);
				}
				
				break;
			case 'text':
			
				$format = parseBody($value);
				break;
			case 'text_layout':
			case 'text_tags':
				
				$value = parseBody($value);
				$html_word_count = Response::addParseDelay($value, function($value) {
					
					$count = str_word_count(strip_tags($value));
					
					if ($count > 1) {
						return '<p>'.getLabel('lbl_word_count', 'L', true).': '.$count.'</p>';
					} else {
						return '';
					}
				}, true);
			
				if ($extra) { // If there are tags
					
					$arr_types_all = StoreType::getTypes(); // Source can be any type
					$arr_html_tabs = [];
					
					$arr_collect_type_object_names = [];
					
					foreach ((array)$extra as $ref_type_id => $arr_ref_objects) {
					
						foreach($arr_ref_objects as $cur_object_id => $arr_reference) {
							
							$object_name = ($arr_reference['object_definition_ref_object_name'] ?: $arr_reference['object_sub_definition_ref_object_name']);
							
							if ($arr_collect_type_object_names[$ref_type_id][$object_name]) {
								continue;
							}
							
							$html = '<p class="'.$ref_type_id.'_'.$cur_object_id.'">'.data_view::createTypeObjectLink($ref_type_id, $cur_object_id, $object_name).'</p>';
							$arr_collect_type_object_names[$ref_type_id][$object_name] = $html;
						}
						
						if (!$arr_collect_type_object_names[$ref_type_id]) {
							continue;
						}

						ksort($arr_collect_type_object_names[$ref_type_id]);
						$arr_html_tabs['content'][] = '<div>
							'.implode('', $arr_collect_type_object_names[$ref_type_id]).'
						</div>';
						$arr_html_tabs['links'][] = '<li><a href="#">'.Labels::parseTextVariables($arr_types_all[$ref_type_id]['name']).'</a></li>';
					}
					
					if ($arr_html_tabs) {
					
						$format = '<div class="tabs '.$type.'">
							<ul>
								<li><a href="#">'.getLabel('lbl_text').'</a></li>
								<li><a href="#">'.getLabel('lbl_referencing').'</a></li>

							</ul>
							<div><div class="body'.($arr_type_options['marginalia'] ? ' marginalia-show' : '').'">'.$value.'</div>'.($arr_type_options['marginalia'] ? '<div class="marginalia"></div>' : '').$html_word_count.'</div>
							<div class="text-references">
								<div class="tabs">
									<ul>
										'.implode('', $arr_html_tabs['links']).'
									</ul>
									'.implode('', $arr_html_tabs['content']).'
								</div>
							</div>
						</div>';
						break;
					}
				}
				$format = '<div class="body '.$type.'">'.$value.'</div>'.$html_word_count;
				break;
			case 'media':
			case 'media_external':
			
				if ($value) {
					
					$show_url = ($arr_type_options['display'] && $arr_type_options['display'] == 'url');
					
					if (is_array($value)) {
						
						$arr_html = [];
							
						foreach ($value as $media) {
							
							$media = new EnucleateMedia($media);
							
							if ($show_url) {
								$str_url = $media->enucleate(true);
								$str_url = htmlspecialchars($str_url);
								$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
							} else {
								$arr_html[] = $media->enucleate();
							}
						}
						
						if (!$show_url && count($arr_html) > 1) {
							
							$format = '<div class="album">'.implode('', $arr_html).'</div>';
						} else {
							$format = implode('<span class="separator">'.($arr_type_options['separator'] ?: ' ').'</span>', $arr_html);
						}
					} else {
								
						$media = new EnucleateMedia($value);
						
						if ($show_url) {
							$str_url = $media->enucleate(true);
							$str_url = htmlspecialchars($str_url);
							$format = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
						} else {
							$format = $media->enucleate();
						}
					}
				}

				break;
			case 'external':
				
				if (is_array($value)) {
						
					$arr_html = [];
					
					foreach ($value as $ref_value) {
													
						$reference = new ExternalResource(data_linked_data::getLinkedDataResources($arr_type_options['id']), $ref_value);
						$arr_html[] = '<span>'.$reference->getURL().'</span>';
					}
					
					$format = implode('<span class="separator">'.($arr_type_options['separator'] ?: ' ').'</span>', $arr_html);
				} else {
					
					$reference = new ExternalResource(data_linked_data::getLinkedDataResources($arr_type_options['id']), $value);
					$format = $reference->getURL();
				}
				
				break;
			case 'date_cycle':
			
				$format = getLabel('unit_month').' '.(int)$value[0].'<br />'.getLabel('unit_day').' '.(int)$value[1];
				
				break;
			case 'date_compute':
			
				$format = getLabel('unit_year').' +'.(int)$value[0].'<br />'.getLabel('unit_month').' +'.(int)$value[1].'<br />'.getLabel('unit_day').' +'.(int)$value[2];
			
				break;
			default:
				$format = htmlspecialchars($value);
		}
		
		return $format;
	}
	
	public static function formatToInputValue($type, $value, $arr_type_options = []) {
			
		switch ($type) {
			case 'date':
				$format = ($value ? self::int2Date($value) : '');
				break;
			case 'boolean':
				$format = ($value !== '' && $value !== null ? ($value == 1 || $value === 'yes' ? 'yes' : 'no') : '');
				break;
			case 'date_cycle':
			
				if ($value) {
					$format = [
						'month' => $value[0],
						'day' => $value[1],
					];
				}
				break;
			case 'date_compute':
			
				if ($value) {
					$format = [
						'year' => $value[0],
						'month' => $value[1],
						'day' => $value[2],
					];
				}
				break;
			default:
				$format = $value;
		}
		
		return $format;
	}
	
	public static function formatToFormValue($type, $value, $name, $arr_type_options = [], $extra = false) {
	
		switch ($type) {
			case 'media_external':
			case '':
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
					
					foreach ($value as $str) {
						$format[] = ['value' => '<input type="text" class="default" name="'.$name.'[]" value="'.htmlspecialchars($str).'" />'];
					}
				} else {
					
					$format = '<input type="text" class="default" name="'.$name.'" value="'.htmlspecialchars($value).'" />';
				}
				
				break;
			case 'int':
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
				
					foreach ($value as $int) {
						$format[] = ['value' => '<input type="number" name="'.$name.'[]" value="'.htmlspecialchars($int).'" />'];
					}
				} else {
					
					$format = '<input type="number" name="'.$name.'" value="'.htmlspecialchars($value).'" />';
				}
				
				break;
			case 'media':
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
					
					foreach ($value as $str) {
						
						$unique = uniqid('array_');
						
						$format[] = ['value' => '<label>'.getLabel('lbl_url').':</label><input type="text" name="'.$name.'['.$unique.'][url]" value="'.htmlspecialchars($str).'" /><label>'.getLabel('lbl_file').':</label>'.cms_general::createFileBrowser(false, $name.'['.$unique.'][file]')];
					}
				} else {
					
					$format = '<label>'.getLabel('lbl_url').':</label><input type="text" name="'.$name.'[url]" value="'.htmlspecialchars($value).'" /><label>'.getLabel('lbl_file').':</label>'.cms_general::createFileBrowser(false, $name.'[file]');
				}
				
				break;
			case 'text':
				$format = '<textarea name="'.$name.'">'.htmlspecialchars($value).'</textarea>';
				break;
			case 'text_layout':
				$format = cms_general::editBody($value, $name, ['inline' => true]);
				break;
			case 'text_tags':
				$format = cms_general::editBody($value, $name, ['inline' => true, 'data' => ['tag_object' => true]]);
				break;
			case 'boolean':
				$format = '<span class="input">'.cms_general::createSelectorRadio([['id' => 'yes', 'name' => getLabel('lbl_yes')], ['id' => 'no', 'name' => getLabel('lbl_no')], ['id' => '', 'name' => getLabel('lbl_none')]], $name, ($value !== '' && $value !== null ? ($value == 1 || $value === 'yes' ? 'yes' : 'no') : '')).'</span>';
				break;
			case 'date':
				$format = '<input type="text" class="date" name="'.$name.'" value="'.($value ? self::int2Date($value) : '').'" />';
				break;
			case 'date_cycle':
				
				$day = $month = '';
				if ($value) {
					$month = $value[0];
					$day = $value[1];
				}

				$format = '<fieldset class="input"><ul>'
					.'<li><input type="number" class="date" placeholder="'.getLabel('unit_day').'" name="'.$name.'[day]" value="'.$day.'" /><label>'.getLabel('unit_day').'</label></li>'
					.'<li><input type="number" class="date" placeholder="'.getLabel('unit_month').'" name="'.$name.'[month]" value="'.$month.'" /><label>'.getLabel('unit_month').'</label></li>'
				.'</ul></fieldset>';
					
				break;
			case 'date_compute':
				
				$day = $month = $year = 0;
				if ($value) {
					$year = (int)$value[0];
					$month = (int)$value[1];
					$day = (int)$value[2];
				}
				
				$format = '<fieldset class="input"><ul>'
					.'<li><input type="number" name="'.$name.'[day]" value="'.$day.'" /><label>+ '.getLabel('unit_days').'</label></li>'
					.'<li><input type="number" name="'.$name.'[month]" value="'.$month.'" /><label>+ '.getLabel('unit_months').'</label></li>'
					.'<li><input type="number" name="'.$name.'[year]" value="'.$year.'" /><label>+ '.getLabel('unit_years').'</label></li>'
				.'</ul></fieldset>';
					
				break;
			case 'external':
				
				$is_multi = is_array($value);
				$is_static = true;
				
				if ($arr_type_options['id']) {
					
					$arr_resource = data_linked_data::getLinkedDataResources($arr_type_options['id']);
					$is_static = ($arr_resource && $arr_resource['protocol'] == 'static' ? true : false);
				}
				
				if (!$is_static) {
					
					if ($is_multi) {

						$format = cms_general::createMultiSelect($name, 'y:data_filter:lookup_external-'.$arr_type_options['id'], ($value ? array_combine($value, $value) : []), 'y:data_filter:lookup_external_pick-'.$arr_type_options['id'], ['delay' => 2]);
					} else {
						
						$format = '<input type="hidden" id="y:data_filter:lookup_external_pick-'.$arr_type_options['id'].'" name="'.$name.'" value="'.$value.'" /><input type="search" id="y:data_filter:lookup_external-'.$arr_type_options['id'].'" class="autocomplete external" data-delay="3" value="'.htmlspecialchars($value).'" />';
					}
				} else {
					
					if ($is_multi) {
						
						$format = [];
						
						if (!$value) {
							$value[] = '';
						}
						
						foreach ($value as $key => $ref_value) {

							$format[] = ['value' => '<input type="text" class="default" name="'.$name.'[]" value="'.htmlspecialchars($ref_value).'" />'];
						}
					} else {
						
						$format = '<input type="text" class="default" name="'.$name.'" value="'.htmlspecialchars($value).'" />';
					}
				}
				
				break;
			default:
				$format = '<input type="text" class="default" name="'.$name.'" value="'.htmlspecialchars($value).'" />';
		}
		
		if (is_array($format)) {
			
			$format = '<fieldset class="input"><ul>
				<li>
					<label></label><div><menu class="sorter">'
						.'<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'			
					.'</menu></div>
				</li><li>
					<label></label>
					'.cms_general::createSorter($format, true, true).'
				</li>
			</ul></fieldset>';
		}
		
		return $format;
	}
	
	public static function formatToFormValueFilter($type, $value, $name, $arr_type_options = []) {
	
		switch ($type) {
			case 'date':
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				$arr_equality = FilterTypeObjects::getEqualityValues();
				unset($arr_equality['≠']);
				$format = '<select name="'.$name.'[equality]">'.cms_general::createDropdown($arr_equality, $value['equality']).'</select>'
					.'<input type="text" class="date" name="'.$name.'[value]" value="'.($value['value'] !== 'now' ? $value['value'] : '').'" />'
					.'<input type="checkbox" name="'.$name.'[value_now]" value="1" title="'.getLabel('inf_date_now').'"'.($value['value'] === 'now' ? ' checked="checked"' : '').' />'
					.'<input type="text" class="date" name="'.$name.'[range]" value="'.($value['range'] !== 'now' ? $value['range'] : '').'" />'
					.'<input type="checkbox" name="'.$name.'[range_now]" value="1" title="'.getLabel('inf_date_now').'"'.($value['range'] === 'now' ? ' checked="checked"' : '').' />';
				break;
			case 'int':
			case 'float':
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				$arr_equality = FilterTypeObjects::getEqualityValues();
				unset($arr_equality['≠']);
				$format = '<select name="'.$name.'[equality]">'.cms_general::createDropdown($arr_equality, $value['equality']).'</select>'
					.'<input type="number" name="'.$name.'[value]" value="'.$value['value'].'" />'
					.'<input type="number" name="'.$name.'[range]" value="'.$value['range'].'" />';
				break;
			case 'text':
			case 'text_layout':
			case 'media_external':
			case 'external':
			case '':
				$value = (is_array($value) ? $value : ['equality' => '*', 'value' => $value]);
				$arr_equality = FilterTypeObjects::getEqualityStringValues();
				unset($arr_equality['≠']);
				$format = '<select name="'.$name.'[equality]">'.cms_general::createDropdown($arr_equality, $value['equality']).'</select>'
					.'<input type="text" name="'.$name.'[value]" value="'.$value['value'].'" />';
				break;
			default:
				$format = self::formatToFormValue($type, $value, $name, $arr_type_options);
		}
		
		return $format;
	}
		
	public static function formatFromSQLValue($type, $value, $arr_type_options = []) {
		
		switch ($type) {
			case 'boolean':
				$format = "CASE WHEN ".$value." = 1 THEN 'yes' WHEN ".$value." = 0 THEN 'no' ELSE '' END";
				break;
			case 'date':
				$value = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);
				$format = "CONCAT(
					CASE WHEN SUBSTRING(".$value." FROM (LENGTH(".$value.")-5) FOR 2) = '00' THEN '' ELSE CONCAT(SUBSTRING(".$value." FROM (LENGTH(".$value.")-5) FOR 2), '-') END,
					CASE WHEN SUBSTRING(".$value." FROM (LENGTH(".$value.")-7) FOR 2) = '00' THEN '' ELSE CONCAT(SUBSTRING(".$value." FROM (LENGTH(".$value.")-7) FOR 2), '-') END,
					SUBSTRING(".$value." FROM 1 FOR LENGTH(".$value.")-8),
					CASE WHEN RIGHT(".$value.", 4) = '0000' THEN '' ELSE CONCAT(' ', RIGHT(".$value.", 4)) END
				)";
				break;
			case 'chronology':
				$format = self::formatFromSQLChronology($value);
				break;
			case 'geometry':
				$format = "ST_AsGeoJSON(".$value.")";
				break;
			case 'int':
				$format = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);
				break;
			default:
				$format = $value;
		}

		return $format;
	}
	
	public static function formatToSQLValue($type, $value) {
		
		$do_unique = true;
	
		switch ($type) {
			case 'boolean':
				$format = ($value !== '' && $value !== null ? ($value === 'no' || !$value ? 0 : 1) : '');
				break;
			case 'date':
				$format = self::date2Int($value);
				break;
			case 'date_cycle':
				$do_unique = false;
				if (!$value || !$value['day'] || !$value['month']) {
					$format = [1, 1];
					break;
				}
				$format = [(int)$value['month'], (int)$value['day']];
				break;
			case 'date_compute':
				$do_unique = false;
				if (!$value || (!$value['day'] && !$value['month'] && !$value['year'])) {
					$format = [0, 0, 0];
					break;
				}
				$format = [(int)$value['year'], (int)$value['month'], (int)$value['day']];
				break;
			case 'chronology':
				$value = self::formatToSQLChronology($value);
				$format = ($value ? implode(',', $value['date']).';'.implode(';', $value['chronology']) : '');
				break;
			case 'geometry':
				$format = self::formatToSQLGeometry($value);
				break;
			case 'media':
			
				$is_multi = false;
				
				// Ensure the array is iterable by making it multi
				if (!is_array($value)) {
					
					$value = [$value];
				} else {
					
					if ($value['file'] !== null || $value['url'] !== null) {
						$value = [$value];
					} else {
						$is_multi = true;
					}
				}
				
				$format = [];
				
				foreach ($value as $arr_media) {
					
					if (!is_array($arr_media)) {
						$arr_media = ['url' => $arr_media];
					}
					
					$filename = false;
						
					if ($arr_media['file']) {
						
						$arr_file = (array)$arr_media['file'];
						
						if ($arr_file['size']) {
							
							$filename = hash_file('md5', $arr_file['tmp_name']);
							$extension = FileStore::getExtension($arr_file['tmp_name']);
							if ($extension == FileStore::EXTENSION_UNKNOWN) {
								$extension = FileStore::getExtension($arr_file['name']);
							}
							$filename = $filename.'.'.$extension;
							
							if (!isPath(DIR_HOME_TYPE_OBJECT_MEDIA.$filename)) {
								
								$store_file = new FileStore($arr_file, ['dir' => DIR_HOME_TYPE_OBJECT_MEDIA, 'filename' => $filename]);
							}
						}
					}
					
					if (!$filename && $arr_media['url']) {
						
						$file = new FileGet($arr_media['url']);
						
						if ($file->load()) {
							
							$path_file = $file->getPath();
							$filename = hash_file('md5', $path_file);
							$extension = FileStore::getExtension($path_file);
							if ($extension == FileStore::EXTENSION_UNKNOWN) {
								$extension = FileStore::getExtension($arr_media['url']);
							}
							
							$filename = $filename.'.'.$extension;
							
							if (!isPath(DIR_HOME_TYPE_OBJECT_MEDIA.$filename)) {
								
								$store_file = new FileStore($file, ['dir' => DIR_HOME_TYPE_OBJECT_MEDIA, 'filename' => $filename]);
							} else {
								
								$file->abort();
							}
						} else {
							
							$filename = $arr_media['url'];
						}
					}
					
					$format[] = $filename;
				}
				
				if (!$is_multi) {
					$format = current($format);
				}
				break;
			case 'int':
				$format = ($value !== '' && $value !== null ? arrParseRecursive($value, 'int') : '');
				break;
			case 'text':
			case 'text_layout':
			case 'text_tags':
			case 'media_external':
			case 'external':
			case '':
				$format = arrParseRecursive($value, 'trim');
				break;
			default:
				$format = $value;
		}
		
		if ($do_unique && is_array($format) && $format) {
			$format = array_values(array_unique(array_filter($format)));
		}
		
		return $format;
	}
	
	public static function formatToSQLValueFilter($type, $value, $name) {
		
		$value_plain = (is_array($value) ? $value['value'] : $value);
	
		switch ($type) {
			case 'date':
			case 'int':
			case 'float':
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				if ($type == 'date') {
					$value['value'] = (int)self::date2Int($value['value']);
					$value['range'] = (int)self::date2Int($value['range']);
				} else if ($type == 'float') {
					$value['value'] = (float)$value['value'];
					$value['range'] = (float)$value['range'];
				} else {
					$value['value'] = (int)$value['value'];
					$value['range'] = (int)$value['range'];
				}

				switch ($value['equality']) {
					case '=':
					case '>':
					case '<':
						$format = $name.' '.$value['equality'].' '.$value['value'];
						break;
					case '≥':
						$format = $name.' >= '.$value['value'];
						break;
					case '≤':
						$format = $name.' <= '.$value['value'];
						break;
					case '><':
						$format = '('.$name.' > '.$value['value'].' AND '.$name.' < '.$value['range'].')';
						break;
					case '≥≤':
						$format = '('.$name.' >= '.$value['value'].' AND '.$name.' <= '.$value['range'].')';
						break;
				}
				break;
			case 'text':
			case 'text_layout':
			case 'text_tags':
			case 'media_external':
			case 'external':
			case '':
				$value = (is_array($value) ? $value : ['equality' => '*', 'value' => $value]);
				$search = DBFunctions::str2Search(self::formatToSQLValue($type, $value['value']));

				switch ($value['equality']) {
					case '*':
						$format = $name." LIKE '%".$search."%'";
						break;
					case '^':
						$format = $name." LIKE '".$search."%'";
						break;
					case '$':
						$format = $name." LIKE '%".$search."'";
						break;
					case '=':
						$format = $name." LIKE '".$search."'";
						break;
				}
				break;
			case 'boolean':
				$format = self::formatToSQLValue($type, $value_plain);
				$format = ($format !== '' ? $name." = ".$format : 'TRUE');
				break;
			default:
				$format = $name." LIKE '%".DBFunctions::str2Search(self::formatToSQLValue($type, $value_plain))."%'";
				break;
		}
		
		return $format;
	}
	
	public static function formatToSQLTranscension($type, $value, $name) {
		
		if (!$value['value'] || $value['value'] == 'any') {
			return '';
		}
		
		if (is_array($name)) { // Check the combined result of multiple columns
			
			$arr_format = [];
			
			foreach ($name as $cur_name) {
				
				$arr_format[] = self::formatToSQLTranscension($type, $value, $cur_name);
			}
			
			if ($value['value'] == 'empty') {
				$format = "(".implode(' AND ', $arr_format).")";
			} else if ($value['value'] == 'not_empty') {
				$format = "(".implode(' OR ', $arr_format).")";
			}
		} else {
			
			switch ($type) {
				case 'text':
				case 'text_layout':
				case 'text_tags':
				case 'media':
				case 'media_external':
				case 'external':
				case '':
					if ($value['value'] == 'empty') {
						$format = "COALESCE(".$name.", '') = ''";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." != ''";
					}
					break;
				case 'boolean':
				case 'null':
					if ($value['value'] == 'empty') {
						$format = $name." IS NULL";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." IS NOT NULL";
					}
					break;
				case 'condition':
					if ($value['value'] == 'empty') {
						$format = $name." = FALSE";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." = TRUE";
					}
					break;
				default:
					if ($value['value'] == 'empty') {
						$format = "COALESCE(".$name.", 0) = 0";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." != 0";
					}
					break;
			}
		}
		
		return $format;
	}
	
	// Chronology
	
	public static function formatToSQLChronology($value) {
		
		if (!$value) {
			return '';
		}
		
		$arr_chronology = self::formatToChronology($value);
		
		$is_period = ($arr_chronology['type'] == 'period');
		
		$arr_sql = [
			'date' => [
				'span_period_amount' => 0, 'span_period_unit' => 0, 'span_cycle_object_id' => 0
			],
			'chronology' => []
		];
		
		$arr_time_directions = StoreType::getTimeDirectionsInternal();
		$arr_time_units = StoreType::getTimeUnitsInternal();
		
		$func_compute_date = function($arr) {
			
			if (!$arr) {
				return false;
			}
			
			if ($arr['date_object_sub_id'] || $arr['date_path']) {
				
				$date = 0;
			} else {
				
				$date = (int)self::formatToSQLValue('date', $arr['date_value']);
				
				if (!$date) {
					$date = false;
				}
			}
			
			return $date;
		};
		
		$date_start_start = $func_compute_date($arr_chronology['start']['start']);
		$date_start_end = $func_compute_date($arr_chronology['start']['end']);
		
		if ($is_period) {
			
			$date_end_start = $func_compute_date($arr_chronology['end']['start']);
			$date_end_end = $func_compute_date($arr_chronology['end']['end']);
			
			$date_end_end = ($date_end_end !== false ? $date_end_end : $date_start_start);

			$arr_sql['date']['span_period_amount'] = (int)$arr_chronology['span']['period_amount'];
			$arr_sql['date']['span_period_unit'] = (int)$arr_time_units[$arr_chronology['span']['period_unit']];
		} else {
			
			$date_end_start = false;
			
			$date_end_end = ($date_start_end !== false ? $date_start_end : $date_start_start);
		}
		
		if ($date_start_start === false) {
			return '';
		}
				
		$arr_sql['date']['span_cycle_object_id'] = (int)$arr_chronology['span']['cycle_object_id'];
		
		$func_sql_statement = function($arr, $identifier) use (&$arr_sql, $arr_time_directions, $arr_time_units) {
			
			if (!$arr) {
				return;
			}
			
			$arr_statement = [
				'offset_amount' => (int)$arr['offset_amount'],
				'offset_unit' => (int)$arr_time_units[$arr['offset_unit']],
				'cycle_object_id' => (int)$arr['cycle_object_id'],
				'cycle_direction' => (int)$arr_time_directions[$arr['cycle_direction']],
				'date_value' => ($arr['date_value'] ? (int)self::formatToSQLValue('date', $arr['date_value']) : 'NULL'),
				'date_object_sub_id' => ((int)$arr['date_object_sub_id'] ?: 'NULL'),
				'date_direction' => (int)$arr_time_directions[$arr['date_direction']]
			];
			
			$arr_sql['chronology'][] = implode(',', $arr_statement).', '.$identifier;
		};
		
		$func_sql_statement($arr_chronology['start']['start'], StoreType::DATE_START_START);
		$func_sql_statement($arr_chronology['start']['end'], StoreType::DATE_START_END);
		if ($is_period) {
			$func_sql_statement($arr_chronology['end']['start'], StoreType::DATE_END_START);
			$func_sql_statement($arr_chronology['end']['end'], StoreType::DATE_END_END);
		}
		
		return $arr_sql;
	}
	
	public static function formatFromSQLChronology($sql_table_name) {
				
		$sql = 'CONCAT(
			CONCAT('.$sql_table_name.'.span_period_amount, \',\', '.$sql_table_name.'.span_period_unit, \',\', '.$sql_table_name.'.span_cycle_object_id),
			\';\',
			COALESCE((SELECT
					'.DBFunctions::sqlImplode(
						'CONCAT(identifier, \',\', offset_amount, \',\', offset_unit, \',\', cycle_object_id, \',\', cycle_direction, \',\', COALESCE('.DBFunctions::castAs('date_value', DBFunctions::CAST_TYPE_STRING).', \'\'), \',\', COALESCE('.DBFunctions::castAs('date_object_sub_id', DBFunctions::CAST_TYPE_STRING).', \'\'), \',\', date_direction)',
						';'
					).'
				FROM '.DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY').' AS '.$sql_table_name.'_chrono
				WHERE '.$sql_table_name.'_chrono.object_sub_id = '.$sql_table_name.'.object_sub_id
					AND '.$sql_table_name.'_chrono.version = '.$sql_table_name.'.version
					AND '.$sql_table_name.'_chrono.active = TRUE
			), \'\')
		)';
	
		return $sql;
	}
	
	public static function formatToChronology($value) {
		
		if (!$value) {
			return false;
		}
		
		if (is_array($value) || substr($value, 0, 1) == '{') { // chronoJSON
			
			if (is_array($value)) {
				$arr_source = $value;
			} else {
				$arr_source = json_decode($value, true);
			}
			
			$is_period = ($arr_source['type'] == 'period');
			$date_start = $arr_source['date']['start'];
			$date_end = $arr_source['date']['end'];
			
			$arr_source_span = $arr_source['span'];
			
			$arr_chronology = [
				'type' => ($is_period ? 'period' : 'point'),
				'span' => [
					'period_amount' => $arr_source_span['period_amount'],
					'period_unit' => ($arr_source_span['period_amount'] ? $arr_source_span['period_unit'] : null),
					'cycle_object_id' => $arr_source_span['cycle_object_id']
				]
			];
			
			$func_parse_statement = function(&$arr, $arr_source) {
				
				if (!$arr_source) {
					return;
				}
				
				// date_value takes precedent (more specific)
				if ($arr_source['date_value_type']) {
					if ($arr_source['date_value_type'] == 'object_sub') {
						$arr_source['date_value'] = null;
						$arr_source['date_path'] = null;
					} else if ($arr_source['date_value_type'] == 'path') {
						$arr_source['date_value'] = null;
						$arr_source['date_object_sub_id'] = null;
					} else {
						$arr_source['date_object_sub_id'] = null;
						$arr_source['date_path'] = null;
					}
				} else if ($arr_source['date_value']) {
					
					$arr_source['date_object_sub_id'] = null;
					$arr_source['date_path'] = null;
											
					if ($arr_source['date_value'] == '∞' || $arr_source['date_value'] == '-∞') {
						
						$arr_source = [
							'date_value' => $arr_source['date_value']
						];
					}
				}
				
				if (!$arr_source['date_value'] && !$arr_source['date_object_sub_id'] && !$arr_source['date_path']) {
					return;
				}
				
				$arr = [
					'offset_amount' => $arr_source['offset_amount'],
					'offset_unit' => ($arr_source['offset_amount'] ? $arr_source['offset_unit'] : null),
					'cycle_object_id' => $arr_source['cycle_object_id'],
					'cycle_direction' => ($arr_source['cycle_object_id'] ? $arr_source['cycle_direction'] : null),
					'date_value' => $arr_source['date_value'],
					'date_object_sub_id' => $arr_source['date_object_sub_id'],
					'date_direction' => $arr_source['date_direction']
				];
				
				if ($arr_source['date_path']) {
					
					$arr['date_path'] = $arr_source['date_path'];
					
					if (!is_array($arr['date_path'])) {
						$arr['date_path'] = json_decode($arr['date_path'], true);
					}
				}
			};
			
			$func_parse_statement($arr_chronology['start']['start'], $arr_source['start']['start']);
			$func_parse_statement($arr_chronology['start']['end'], $arr_source['start']['end']);
			$func_parse_statement($arr_chronology['end']['start'], $arr_source['end']['start']);
			$func_parse_statement($arr_chronology['end']['end'], $arr_source['end']['end']);
			
		} else { // Internal
			
			$arr_time_directions = StoreType::getTimeDirectionsInternal(true);
			$arr_time_units = StoreType::getTimeUnitsInternal(true);
			
			$arr_parts = explode(';', $value);
			
			$arr_part = explode(',', $arr_parts[0]);
			unset($arr_parts[0]);
			
			$date_start = false;
			$date_end = false;

			$arr_chronology = [
				'type' => '',
				'span' => [
					'period_amount' => $arr_part[0],
					'period_unit' => $arr_time_units[$arr_part[1]],
					'cycle_object_id' => $arr_part[2]
				]
			];
			
			$func_parse_statement = function($str) use (&$arr_chronology, $arr_time_directions, $arr_time_units) {
				
				$arr_part = explode(',', $str);
				$int_identifier = (int)$arr_part[0];
				
				$arr = [
					'offset_amount' => $arr_part[1],
					'offset_unit' => $arr_time_units[$arr_part[2]],
					'cycle_object_id' => $arr_part[3],
					'cycle_direction' => $arr_time_directions[$arr_part[4]],
					'date_value' => self::formatToInputValue('date', $arr_part[5]),
					'date_object_sub_id' => $arr_part[6],
					'date_direction' => $arr_time_directions[$arr_part[7]]
				];
				
				switch ($int_identifier) {
					case StoreType::DATE_START_START:
						$arr_chronology['start']['start'] = $arr;
						break;
					case StoreType::DATE_START_END:
						$arr_chronology['start']['end'] = $arr;
						break;
					case StoreType::DATE_END_START:
						$arr_chronology['end']['start'] = $arr;
						break;
					case StoreType::DATE_END_END:
						$arr_chronology['end']['end'] = $arr;
						break;
				}
			};
			
			foreach ($arr_parts as $str) {
				$func_parse_statement($str);
			}
			
			$is_period = ($arr_chronology['end']['end']);
			$arr_chronology['type'] = ($is_period ? 'period' : 'point');
		}
		
		// Parse
		
		$is_single = (!$is_period && !$arr_chronology['start']['end'] && !$arr_chronology['start']['start']['offset_amount'] && $arr_chronology['start']['start']['date_value'] && (!$arr_chronology['start']['start']['date_direction'] || $arr_chronology['start']['start']['date_direction'] == '|>|')); // Chronology only involves a single date statement (and possibly a Cycle)
		
		if ($arr_chronology['start']['start'] === null) {
			
			if ($date_start) {
				$arr_chronology['start']['start']['date_value'] = $date_start;
			} else {
				return [];
			}
		} else {
			
			if ($is_single) {
				$arr_chronology['start']['start']['date_direction'] = '';
			}
		}
		
		if ($is_period) {
				
			if ($arr_chronology['end']['end'] === null) {
				
				if ($date_end) {
					$arr_chronology['end']['end']['date_value'] = $date_end;
				} else {
					$arr_chronology['end']['end'] = $arr_chronology['start']['start'];
					
					// Apply default direction if not further specified
					if ($arr_chronology['end']['end']['date_direction'] == '|>|' && !$arr_chronology['end']['end']['offset_amount'] && !$arr_chronology['end']['end']['cycle_object_id']) {
						$arr_chronology['end']['end']['date_direction'] = '|<|';
					}
				}
			}
		}
				
		return $arr_chronology;
	}
	
	public static function formatToChronologyDetails($value) {
		
		if (!$value) {
			return false;
		}
		
		if (!is_array($value)) {
			$arr_chronology = self::formatToChronology($value);
		} else {
			$arr_chronology = $value;
		}
			
		$arr_cycle_object_ids = arrValuesRecursive('cycle_object_id', $arr_chronology);
						
		if ($arr_cycle_object_ids) {
			
			$arr_object_names = FilterTypeObjects::getTypeObjectNames(StoreType::getSystemTypeID('cycle'), $arr_cycle_object_ids);
			
			$func_set_cycle_names = function(&$arr) use ($arr_object_names) {
				
				if (!$arr['cycle_object_id']) {
					return;
				}
				
				$arr['cycle_object_name'] = $arr_object_names[$arr['cycle_object_id']];
			};
			
			$func_set_cycle_names($arr_chronology['span']);
			$func_set_cycle_names($arr_chronology['start']['start']);
			$func_set_cycle_names($arr_chronology['start']['end']);
			$func_set_cycle_names($arr_chronology['end']['start']);
			$func_set_cycle_names($arr_chronology['end']['end']);
		}

		$arr_object_sub_ids = arrValuesRecursive('date_object_sub_id', $arr_chronology);
		
		if ($arr_object_sub_ids) {
		
			$arr_object_subs_info = FilterTypeObjects::getObjectSubsTypeObjects($arr_object_sub_ids);
			
			$arr_type_object_sub_ids = [];
			
			foreach ($arr_object_subs_info as $object_sub_id => $arr_info) {
				
				$arr_type_object_sub_ids[$arr_info['type_id']][$arr_info['object_id']][] = $object_sub_id;
			}
			
			$arr_type_object_names = [];
			$arr_object_subs_names = [];
			
			foreach ($arr_type_object_sub_ids as $type_id => $arr_objects_subs) {
				
				$arr_object_ids = array_keys($arr_objects_subs);
				$arr_object_sub_ids = arrMergeValues($arr_objects_subs);

				$arr_type_object_names[$type_id] = FilterTypeObjects::getTypeObjectNames($type_id, $arr_object_ids);
				$arr_object_subs_names += FilterTypeObjects::getTypeObjectSubsNames($type_id, $arr_object_ids, $arr_object_sub_ids);
			}
			
			$func_set_object_sub_names = function(&$arr) use ($arr_object_subs_info, $arr_type_object_names, $arr_object_subs_names) {
				
				if (!$arr['date_object_sub_id']) {
					return;
				}
				
				$arr_info = $arr_object_subs_info[$arr['date_object_sub_id']];
				
				$arr['date_type_id'] = $arr_info['type_id'];
				$arr['date_object_id'] = $arr_info['object_id'];
				$arr['date_object_sub_details_id'] = $arr_info['object_sub_details_id'];
				
				$arr['date_object_name'] = $arr_type_object_names[$arr_info['type_id']][$arr_info['object_id']];
				$arr['date_object_sub_name'] = $arr_object_subs_names[$arr['date_object_sub_id']];
			};
			
			$func_set_object_sub_names($arr_chronology['start']['start']);
			$func_set_object_sub_names($arr_chronology['start']['end']);
			$func_set_object_sub_names($arr_chronology['end']['start']);
			$func_set_object_sub_names($arr_chronology['end']['end']);
		}
				
		return $arr_chronology;
	}
	
	public static function FormatToChronologyPointOnly($value) {
		
		if (!is_array($value)) {
			$arr_chronology = self::formatToChronology($value);
		} else {
			$arr_chronology = $value;
		}
		
		$arr_chronology = StoreTypeObjects::formatToChronology([
			'type' => $arr_chronology['type'],
			'date' => ['start' => $arr_chronology['start']['start']['date_value'], 'end' => $arr_chronology['end']['end']['date_value']]
		]);
		
		$arr_chronology = arrFilterRecursive($arr_chronology);

		return $arr_chronology;
	}
	
	// Geometry
	
	public static function formatToSQLGeometry($value) {
		
		if (!$value) {
			return '';
		}
		
		if (is_array($value)) {
			$value = json_encode($value);
		}
		
		try {
			
			$value = DBFunctions::strEscape($value);
			
			// Check if it is valid GeoJSON and is queryable
			
			$sql = (
				DB::ENGINE_IS_MYSQL ? "
					SET @check = ST_GeomFromGeoJSON('".$value."', 1, 0);
					SELECT ST_Intersects(@check, @check);
				" :	"
					WITH vars AS (SELECT ST_GeomFromGeoJSON('".$value."', 1, 0) AS check);
					SELECT ST_Intersects(vars.check, vars.check) FROM vars;
				"
			);
			
			$sql .= "SELECT ST_AsGeoJSON(ST_GeomFromGeoJSON('".$value."', 1, 0));";
			
			$res = DB::queryMulti($sql);
		} catch (Exception $e) {
			
			error(getLabel('msg_malformed_geojson'), TROUBLE_ERROR, LOG_BOTH, false, $e);
		}
		
		$sql = $res[2]->fetchRow();
		$sql = $sql[0];
		
		return $sql;
	}
	
	public static function formatToGeometry($value) {
		
		if (!$value) {
			return false;
		}
		
		if (!is_array($value)) {
			$arr_geometry = json_decode($value, true);
		} else {
			$arr_geometry = $value;
		}
		
		return $arr_geometry;
	}
	
	public static function formatToGeometryPoint($value) {
		
		if (!$value) {
			return false;
		}
		
		$arr_geometry = self::formatToGeometry($value);
		
		if ($arr_geometry['type'] && $arr_geometry['type'] == 'Point') {
			return $arr_geometry['coordinates'];
		} else {
			return false;
		}
	}
	
	public static function formatToGeometrySummary($value) {
		
		if (!$value) {
			return '';
		}
		
		$arr_geometry = self::formatToGeometry($value);
			
		$arr_summary = [];
		$arr_values = arrValuesRecursive('type', $arr_geometry);
		
		foreach ($arr_values as $type) {
			
			if (!$arr_summary[$type]) {
				$arr_summary[$type] = 1;
			} else {
				$arr_summary[$type]++;
			}
		}
		
		if (count($arr_summary) == 1 && $arr_summary['Point']) {
			
			$arr_summary['Point'] = $type.': '.$arr_geometry['coordinates'][1].' <span title="Latitude">λ</span> '.$arr_geometry['coordinates'][0].' <span title="Longitude">φ</span>'; // Switch geometry around to latitude - longitude
		} else {
				
			foreach ($arr_summary as $type => &$value) {
				
				$value = $type.': '.$value;
			}
		}
		unset($value);
		
		$value = implode(', ', $arr_summary);
		
		return $value;
	}
	
	// Date

	public static function date2Int($date, $direction = false) {
		
		// *ymmdd OR *y OR mm-*y OR dd-mm-*y => *ymmdd
		// $direction false OR '>' OR '<'
		
		$date = ($date == '00-00-0000' ? false : $date);
		$arr_date = [];
		
		if ($date) {
			
			if ($date === 'now') {
				
				$date = date('Ymd').'0000';
			} else if (!is_numeric($date)) {
				
				if ($date == '∞') {
					
					$date = DATE_INT_MAX;
				} else if ($date == '-∞') {
					
					$date = DATE_INT_MIN;
				} else {

					$arr_date = self::date2Components($date);
					$date = false;
					
					if (!$arr_date[0]) {
						$arr_date = [];
					}
				}		
			} else if (($date > 0 && strlen((int)$date) <= 4) || $date < 0) {
				
				$arr_date = [$date, '00', '00', '0000'];
			}
		}
		
		if ($arr_date) {
			
			if ($direction) {
				
				if ($arr_date[1] == '00') {
					if ($arr_date[0] < 0) {
						$arr_date[1] = ($direction == '<' ? '00' : '12');
					} else {
						$arr_date[1] = ($direction == '<' ? '12' : '00');
					}
				}
				if ($arr_date[2] == '00') {
					if ($arr_date[0] < 0) {
						$arr_date[2] = ($direction == '<' ? '00' : '31');
					} else {
						$arr_date[2] = ($direction == '<' ? '31' : '00');
					}
				}
			}
			
			$date = implode('', $arr_date);
		}
		
		if ($date) {
			
			if ($date <= DATE_INT_MIN) {
				$date = DATE_INT_MIN;
			} else if ($date >= DATE_INT_MAX) {
				$date = DATE_INT_MAX;
			}
		}

		return $date;
	}
	
	public static function int2Date($date) {
		
		if ($date == DATE_INT_MAX) {
			
			$date = '∞';
		} else if ($date == DATE_INT_MIN) {
			
			$date = '-∞';
		} else {
			
			$sequence = substr($date, -4, 4);
			
			$date = substr($date, -6, 2).'-'.substr($date, -8, 2).'-'.substr($date, 0, -8);
			$date = str_replace('00-', '', $date);
			
			if ($sequence != '0000') {
				$date .= ' '.(int)$sequence;
			}
		}
		
		return $date;
	}
		
	public static function int2DateStandard($date) {
		
		$date = substr($date, 0, -8).'-'.substr($date, -8, 2).'-'.substr($date, -6, 2);
		$date = str_replace('-00', '', $date);
		
		return $date;
	}
	
	public static function intChronology2Date($date, $arr_chronology_statement, $int_identifier) {
		
		if (!$date) {
			return false;
		}
		
		$str_date = self::int2Date($date);
		$str_classifier = '';
		
		if ($arr_chronology_statement['offset_amount'] || $arr_chronology_statement['cycle_object_id']) {
			
			$str_classifier = '~';
		} else if ($arr_chronology_statement['date_direction']) {
			
			$arr_time_directions_internal = StoreType::getTimeDirectionsInternal(true);
			
			switch ($int_identifier) {
				case StoreType::DATE_START_START:
				case StoreType::DATE_END_START:
					if ($arr_chronology_statement['date_direction'] != $arr_time_directions_internal[StoreType::TIME_AFTER_BEGIN]) {
						$str_classifier = $arr_chronology_statement['date_direction'];
					}
					break;
				case StoreType::DATE_START_END:
				case StoreType::DATE_END_END:
					if ($arr_chronology_statement['date_direction'] != $arr_time_directions_internal[StoreType::TIME_BEFORE_END]) {
						$str_classifier = $arr_chronology_statement['date_direction'];
					}
					break;
			}
		}
		
		if ($str_classifier) {
			$str_date = $str_classifier.' '.$str_date;
		}
		
		return $str_date;
	}
	
	public static function date2Components($date) {
		
		$sequence = '0000';
		
		if (strpos($date, ' ') !== false) {
			$date = explode(' ', $date);
			$sequence = str_pad((int)$date[1], 4, '0', STR_PAD_LEFT);
			$date = $date[0];
		}
		
		$date = str_replace('/', '-', $date);
		$min = '';
		
		if (substr($date, 0, 1) == '-') { // -13-10-5456
			$date = substr($date, 1);
			$min = '-';
		} else if (strpos($date, '--') !== false) { // 13-10--5456
			$date = str_replace('--', '-', $date);
			$min = '-';
		}
		$arr_date = array_filter(explode('-', $date));
		$length = count($arr_date);
		
		$year = $arr_date[$length-1];
		$year = ($year === '∞' ? '∞' : (int)$year);
				
		$arr_date = [$min.$year, str_pad((int)$arr_date[$length-2], 2, '0', STR_PAD_LEFT), str_pad((int)$arr_date[$length-3], 2, '0', STR_PAD_LEFT), $sequence];
		
		return $arr_date;
	}
}
