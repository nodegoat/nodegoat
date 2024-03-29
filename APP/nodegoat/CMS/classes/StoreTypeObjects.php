<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreTypeObjects {
	
	const VERSION_NEW = -1;
	const VERSION_DELETED = 0;
	const VERSION_NONE = -2;
	const VERSION_OFFSET_ALTERNATE_ACTIVE = -10;
	const VERSION_OFFSET_ALTERNATE = -20;

	const LAST_OBJECT_ID = 'last';
	
	const MODE_UPDATE = 1;
	const MODE_OVERWRITE = 2;
	
	const MODULE_STATE_NONE = 0;
	const MODULE_STATE_DISABLED = 1;
	/*const MODULE_MODE_X = 2;
	const MODULE_MODE_XX = 4;*/
	
	const TAGCODE_TEST_SERIAL_VARCHAR = '\[\[#(?:=[\d]*)?\]\]';
	const TAGCODE_PARSE_SERIAL_VARCHAR = '\[\[#(?:=([\d]*))?\]\]';
	
	protected $type_id = false;
	protected $user_id = false;
	protected $system_object_id = null;
	
	
	protected $object_id = false;
	
	protected $insert = true;
	protected $insert_sub = true;
	
	protected $arr_append = [];
	protected $versioning = true;
	
	protected $arr_type_set = [];
	protected $arr_types = [];
	protected $arr_object_set = [];
	
	protected $arr_sql_insert = [];
	protected $arr_sql_update = [];
	protected $arr_sql_delete = [];
	protected $arr_actions = false;
	
	protected $str_sql_table_name_object_updates = '';
	protected $stmt_object_updates = false;
	protected $has_object_updates = false;
	
	protected $stmt_object_versions = false;
	protected $stmt_object_description_versions = [];
	protected $stmt_object_sub_versions = false;
	protected $stmt_object_sub_description_versions = [];
	protected $stmt_object_sub_details_id = false;
	protected $stmt_object_sub_ids = false;
			
	protected $arr_append_object_sub_details_ids = [];
	protected $arr_append_object_sub_ids = [];
	
	protected $mode = self::MODE_UPDATE;
	protected $do_check = true;
	protected $is_new = false;
	protected $identifier = false;
	protected $lock_key = false;
	protected $is_trusted = false;

	public static $timeout_lock = 30; // Lock object, in seconds
	public static $last_object_id = 0;
		
    public function __construct($type_id, $object_id, $arr_owner, $identifier = false, $arr_type_set = false) {
	
		$this->type_id = $type_id;
		
		if (is_array($arr_owner)) {
			$this->user_id = (int)$arr_owner['user_id'];
			$this->system_object_id = ($arr_owner['system_object_id'] ? (int)$arr_owner['system_object_id'] : null);
		} else {
			$this->user_id = (int)$arr_owner;
		}
		
		
		$this->identifier = ($identifier ?: 'any'); // Use identifier to make table operations unique when applicable
		$this->str_sql_table_name_object_updates = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_'.$this->identifier);
		$this->lock_key = uniqid();
		
		if ($arr_type_set) {
			$this->arr_type_set = $arr_type_set;
		} else {
			$this->arr_type_set = StoreType::getTypeSet($this->type_id);
		}
		
		$this->arr_types = StoreType::getTypes();
		
		$this->setObjectID($object_id);
    }
    
	public function setMode($mode = self::MODE_UPDATE, $do_check = true) {
		
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
		
		$this->addTypeObjectUpdate();
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
		-20+: Relates to alternate records of record versions (VERSION_OFFSET_ALTERNATE - version)
		-19: Not used
		-18: Relates to alternate record of record with no regard to versioning (VERSION_OFFSET_ALTERNATE - VERSION_NONE)
		-10 / -17: Relates to an alternate record based on active record (VERSION_OFFSET_ALTERNATE_ACTIVE)
		-2: Relates to records with no regard to versioning (VERSION_NONE)
		-1: Translates to version 1 in version log, indicates a new record (VERSION_NEW)
		0: Object is set to be deleted (VERSION_DELETED)
		1+: Relates to an existing record, record version is changed
		
		Status - status:
		-1: Record is deleted, and had its previous state as active (applicable only to forced deletion (no versioning) of objects)
		0: Record is either active or irrelevant
		1: Record matches version and no other record is active
		2: Record matches version and is not active, or record does not match the version but is currently active
		3: Record is the currently active record, or when there is no active record, the record with a status
	*/
		
	public function store($arr_object_self, $arr_object_definitions, $arr_object_subs) {
		
		$func_parse = function($value) {
			
			if ($value === null) {
				return null;
			}
			if (is_string($value)) {
				return parseValue($value, TYPE_TEXT);
			}
			
			return $value;
		};
		
		$arr_object_self = arrParseRecursive($arr_object_self, $func_parse);
		$arr_object_definitions = arrParseRecursive($arr_object_definitions, $func_parse);
		$arr_object_subs = arrParseRecursive($arr_object_subs, $func_parse);
		
		if ($this->object_id && $this->versioning) {
			
			// Relate changes to the latest version
			$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_STORAGE);
			
			if ($this->mode == self::MODE_UPDATE) {
				
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
			
			$version_select = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'record', 'nodegoat_to_def');
			$version_select_to = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object', 'nodegoat_to');

			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if (isset($this->arr_type_set['type']['include_referenced']['object_descriptions'][$object_description_id])) {
					continue;
				}
			
				$arr_object_definition = ($arr_object_definitions[$object_description_id] ?? null);
	
				$is_ref_type_id = (bool)$arr_object_description['object_description_ref_type_id'];
				
				if (!$is_ref_type_id && $arr_object_description['object_description_is_unique']) {
					
					if (empty($arr_object_definition['object_definition_value'])) {
						continue;
					}
					
					$value_type = $arr_object_description['object_description_value_type'];
					$value_find = FormatTypeObjects::formatToSQLValue($value_type, $arr_object_definition['object_definition_value']);
					$sql_match = false;
					
					if ($arr_object_description['object_description_has_multi']) {
						
						$arr_str = [];
						
						foreach ((array)$value_find as $value) {
							$arr_str[] = FormatTypeObjects::formatToSQLValueFilter($value_type, ['equality' => '=', 'value' => $value], StoreType::getValueTypeValue($value_type));
						}
						
						if ($arr_str) {
							$sql_match = "(".implode(" OR ", $arr_str).")";
						}
					} else {
						$sql_match = FormatTypeObjects::formatToSQLValueFilter($value_type, ['equality' => '=', 'value' => $value_find], StoreType::getValueTypeValue($value_type));
					}
					
					if ($sql_match) {
							
						$res = DB::query("SELECT object_description_id, object_id
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type)." nodegoat_to_def
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
							WHERE object_description_id = ".$object_description_id."
								AND ".$sql_match."
								AND ".$version_select."
								".($this->object_id ? "AND object_id != ".$this->object_id : "")."
						");
						
						if ($res->getRowCount()) {
							
							Labels::setVariable('value', (is_array($value_find) ? implode(' / ', $value_find) : $value_find));
							Labels::setVariable('field', Labels::parseTextVariables($arr_object_description['object_description_name']));
							error(getLabel('msg_object_definition_not_unique'));
						}
					}
				}
				
				if ($arr_object_description['object_description_is_required']) {
					
					if ($arr_object_definition === null) {
						
						if ($this->object_id || (!$this->object_id && ($arr_object_description['object_description_value_type_settings']['default']['value'] ?? null))) {
							continue;
						}
					} else if (($is_ref_type_id && $arr_object_definition['object_definition_ref_object_id']) || (!$is_ref_type_id && isset($arr_object_definition['object_definition_value']) && $arr_object_definition['object_definition_value'] !== false && $arr_object_definition['object_definition_value'] !== '')) {
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

			if (!$object_sub_details_id || isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])) {
				
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
								
			if ($this->mode == self::MODE_OVERWRITE && $this->arr_type_set['type']['use_object_name'] && !isset($arr_object_self['object_name_plain'])) {
				$arr_object_self['object_name_plain'] = '';
			}
			
			if ($this->versioning) {
				
				if (
					($this->arr_type_set['type']['use_object_name'] && isset($arr_object_self['object_name_plain']) && $arr_object_self['object_name_plain'] != $this->arr_object_set['object']['object_name_plain'])
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
				
				if ($this->arr_type_set['type']['use_object_name'] && isset($arr_object_self['object_name_plain'])) {
					
					$action_object = 'insert';
				}
			}

			$this->insert = false;
		}
		
		if ($action_object == 'insert') {
			
			if ($this->versioning && !$this->insert) {
				
				$arr_version = $this->getTypeObjectVersions();
				$arr_version = current($arr_version);					
				$version = ($arr_version && $arr_version['object_version'] > 0 ? $arr_version['object_version'] : 0) + 1;
			} else {
				
				$version = ($this->versioning ? 1 : static::VERSION_NONE);
			}
				
			$this->addTypeObjectVersion($version, $arr_object_self['object_name_plain']);
		}
		
		if ($action_object) {
			
			$version_log = ($this->versioning && $this->insert ? static::VERSION_NEW : $version);
			
			$this->addTypeObjectVersionUser($version_log);
		}
					
		$this->handleSources('object', $arr_object_self['object_sources']);
								
		// Object definitions
		
		foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if (isset($this->arr_type_set['type']['include_referenced']['object_descriptions'][$object_description_id])) {
				continue;
			}
			
			$arr_object_definition = ($arr_object_definitions[$object_description_id] ?? null);
			
			$is_ref_type_id = (bool)$arr_object_description['object_description_ref_type_id'];
			$is_reversal = ($is_ref_type_id && $this->arr_types[$arr_object_description['object_description_ref_type_id']]['class'] == StoreType::TYPE_CLASS_REVERSAL);
			
			if (!$is_reversal) {
					
				if (!$arr_object_definition) {
					
					if ($this->insert) {
						
						$value_default = ($arr_object_description['object_description_value_type_settings']['default']['value'] ?? null);
						
						if ($value_default && $arr_object_definition === null) {

							if ($is_ref_type_id) {
								$arr_object_definition['object_definition_ref_object_id'] = $value_default;
							} else {
								$arr_object_definition['object_definition_value'] = $value_default;
							}
						} else {
							continue;
						}
					} else if ($this->mode == self::MODE_OVERWRITE && $arr_object_definition === null) {
						
						$arr_object_definition = ['object_definition_value' => '', 'object_definition_ref_object_id' => 0];
					} else {
						continue;
					}
				}
				
				$is_defined = (($is_ref_type_id && (isset($arr_object_definition['object_definition_ref_object_id']) || array_key_exists('object_definition_ref_object_id', $arr_object_definition))) || (!$is_ref_type_id && (isset($arr_object_definition['object_definition_value']) || array_key_exists('object_definition_value', $arr_object_definition))));

				$action_object_definition = false;

				if ($is_defined) {
					
					$arr_object_definition['object_definition_value'] = $this->parseObjectDefinition($object_description_id, $arr_object_description['object_description_value_type'], $arr_object_definition['object_definition_value']);
					
					if ($arr_object_definition['object_definition_value'] === false || $arr_object_definition['object_definition_value'] === '') {
						$arr_object_definition['object_definition_value'] = null;
					}
					
					if ($is_ref_type_id) {
						
						if ($arr_object_description['object_description_has_multi']) {
							$arr_object_definition['object_definition_ref_object_id'] = array_values(array_unique(array_filter((array)$arr_object_definition['object_definition_ref_object_id'])));
						} else {
							$arr_object_definition['object_definition_ref_object_id'] = (is_array($arr_object_definition['object_definition_ref_object_id']) ? reset($arr_object_definition['object_definition_ref_object_id']) : $arr_object_definition['object_definition_ref_object_id']);
						}
					} else {
						
						if ($arr_object_description['object_description_has_multi']) {
							$arr_object_definition['object_definition_value'] = (array)$arr_object_definition['object_definition_value'];
						} else {
							$arr_object_definition['object_definition_value'] = (is_array($arr_object_definition['object_definition_value']) ? reset($arr_object_definition['object_definition_value']) : $arr_object_definition['object_definition_value']);
						}
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
								(!$arr_object_description['object_description_has_multi'] && isset($arr_object_definition['object_definition_value']))
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
									
									if ($arr_compare_object_definition != static::LAST_OBJECT_ID) {
										
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
										
										$arr_object_definition['object_definition_value'] = static::appendToValue($arr_object_description['object_description_value_type'], $arr_cur_object_definition, $arr_object_definition['object_definition_value']);
									}
									
									$arr_compare_object_definition = $arr_object_definition['object_definition_value'];
								}
								
								if (!static::compareSQLValue($arr_object_description['object_description_value_type'], $arr_compare_object_definition, $arr_cur_object_definition) || ((isset($arr_object_definition['object_definition_value']) && $arr_object_definition['object_definition_value'] !== '') && ($arr_cur_object_definition === '' || $arr_cur_object_definition === null))) {
									
									if (!isset($arr_object_definition['object_definition_value']) || $arr_object_definition['object_definition_value'] === '' || ($arr_object_description['object_description_has_multi'] && !$arr_object_definition['object_definition_value'])) {
										
										if (!$is_appendable) {
											
											// Set object definition record deleted flag (version 0)
											$action_object_definition = 'delete';
										}
									} else {
											
										// Update object definition record, find existing version or insert new version
										foreach ($this->getTypeObjectDescriptionVersions($object_description_id) as $arr_version) {

											if (static::compareSQLValue($arr_object_description['object_description_value_type'], $arr_compare_object_definition, $arr_version['object_definition_value']) && $arr_version['object_definition_version'] > 0) {
												
												$version = $arr_version['object_definition_version'];
												$action_object_definition = 'version';
												
												break;
											}
										}

										$action_object_definition = ($action_object_definition ?: 'insert');
									}
								}
							} else {
								
								if (!isset($arr_object_definition['object_definition_value']) || $arr_object_definition['object_definition_value'] === '' || ($arr_object_description['object_description_has_multi'] && !$arr_object_definition['object_definition_value'])) {
								
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
							$version = ($arr_version && $arr_version['object_definition_version'] > 0 ? $arr_version['object_definition_version'] : 0) + 1;
						} else {
							
							$version = ($this->versioning ? 1 : static::VERSION_NONE);
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
									AND version >= ".static::VERSION_NONE."
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
								
								$this->addTypeObjectDescriptionVersion($object_description_id, static::VERSION_OFFSET_ALTERNATE_ACTIVE, $arr_object_definition_text['text']);
								
								foreach($arr_object_definition_text['tags'] as $value) {
									
									$arr_sql_insert[] = "(".$object_description_id.", ".$this->object_id.", ".$value['object_id'].", ".$value['type_id'].", '".DBFunctions::strEscape($value['text'])."', ".$value['group_id'].", 1)";
								}

								if ($arr_sql_insert) {
									
									$this->arr_sql_insert['object_definition_objects'][] = implode(',', $arr_sql_insert);
								}
							} else {
								
								$this->addTypeObjectDescriptionVersion($object_description_id, static::VERSION_OFFSET_ALTERNATE_ACTIVE, '');
							}
						}
						
						$version_log = ($this->versioning && $this->insert ? static::VERSION_NEW : $version);
						
						$this->addTypeObjectDescriptionVersionUser($object_description_id, $version_log);
					}
				}
			}
			
			$this->handleSources('object_definition', $arr_object_definition['object_definition_sources'], ['object_description_id' => $object_description_id]);
		}
				
		// Subobjects (object_sub AND object_sub_definitions)

		if (!$this->insert && $this->mode == self::MODE_OVERWRITE) {
			
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
							AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_FULL, 'object_sub', 'nodegoat_tos')."
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

			$parse_object_sub = isset($arr_object_sub['object_sub']['object_sub_date_chronology']);
			$action_object_sub = false;
			$object_sub_id = false;
			
			$arr_cur_object_sub = false;
			
			$version = 0;
			$geometry_version = 0;
			$arr_object_sub_versions = false;
			
			if ($arr_object_sub['object_sub']['object_sub_id']) {
				
				$object_sub_id = $arr_object_sub['object_sub']['object_sub_id'];
				if ($this->versioning) {
					$arr_cur_object_sub = $this->arr_object_set['object_subs'][$object_sub_id];
				}
				
				if ($arr_object_sub['object_sub']['object_sub_version'] == 'deleted') {
					
					if ($this->versioning) {
												
						if ($arr_cur_object_sub['object_sub']['object_sub_version'] != 'deleted') { // Already flagged as deleted
							// Set object sub record deleted flag (version 0)
							$action_object_sub = 'delete';
						}
					} else {
						
						$action_object_sub = 'delete';
					}
				} else if ($parse_object_sub) {
					
					if ($this->versioning) {
												
						$str_compare = $arr_object_sub['object_sub']['object_sub_date_chronology'].'-'.$arr_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id'].'-'.$arr_object_sub['object_sub']['object_sub_location_ref_object_id'].'-'.$arr_object_sub['object_sub']['object_sub_location_geometry'];
						$str_compare_cur = FormatTypeObjects::formatToSQLValue('chronology', $arr_cur_object_sub['object_sub']['object_sub_date_chronology']).'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] && $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] ? 0 : $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id']).'-'.$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'].'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] ? $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] : '');
						
						if ($str_compare != $str_compare_cur) {
											
							$arr_object_sub_versions = $this->getTypeObjectSubVersions($object_sub_id);
							
							foreach ($arr_object_sub_versions as $arr_object_sub_version) {
								
								$str_compare_version = FormatTypeObjects::formatToSQLValue('chronology', $arr_object_sub_version['object_sub_date_chronology']).'-'.$arr_object_sub_version['object_sub_location_ref_object_sub_details_id'].'-'.$arr_object_sub_version['object_sub_location_ref_object_id'].'-'.$arr_object_sub_version['object_sub_location_geometry'];
						
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
					
					if ($arr_object_sub_versions) {
						reset($arr_object_sub_versions);
					} else {
						$arr_object_sub_versions = $this->getTypeObjectSubVersions($object_sub_id);
					}
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
							
							if ($arr_object_sub['object_sub']['object_sub_date_chronology'] == FormatTypeObjects::formatToSQLValue('chronology', $arr_object_sub_version['object_sub_date_chronology'])) {
								
								$arr_object_sub['object_sub']['object_sub_date_chronology'] = false;
								$arr_object_sub['object_sub']['object_sub_date_version'] = $arr_object_sub_version['object_sub_date_version'];
								
								break;
							} else if ($arr_object_sub_version['object_sub_date_version'] >= $arr_object_sub['object_sub']['object_sub_date_version']) {
								
								$arr_object_sub['object_sub']['object_sub_date_version'] = $arr_object_sub_version['object_sub_date_version'] + 1;
							}
						}
					}
				} else {
					
					$version = ($this->versioning ? 1 : static::VERSION_NONE);
					
					if ($arr_object_sub['object_sub']['object_sub_location_geometry']) {
						
						$arr_object_sub['object_sub']['object_sub_location_geometry_version'] = $version;
					}
					
					if ($arr_object_sub['object_sub']['object_sub_date_chronology']) {
						
						$arr_object_sub['object_sub']['object_sub_date_version'] = $version;
						
						if (!$this->versioning && !$this->insert_sub) {
							
							$this->arr_sql_delete['object_sub_date_chronology_version'][] = "UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')."
								SET active = FALSE
								WHERE object_sub_id = ".$object_sub_id."
									AND version = ".static::VERSION_NONE."
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
							AND version >= ".static::VERSION_NONE."
					";
				}
				
				$arr_object_sub['object_sub']['object_sub_sources'] = []; // Also remove sources
				
				$version = 0;
			}
			
			if ($action_object_sub) {
				
				if ($action_object_sub != 'delete') {
				
					if (FormatTypeObjects::GEOMETRY_SRID && $arr_object_sub['object_sub']['object_sub_location_geometry_version']) {
						
						$str_location_geometry = $arr_object_sub['object_sub']['object_sub_location_geometry'];
						
						if (FormatTypeObjects::geometryToSRID($str_location_geometry) != FormatTypeObjects::GEOMETRY_SRID) {
							
							$str_location_geometry = FormatTypeObjects::translateToGeometry($str_location_geometry, FormatTypeObjects::GEOMETRY_SRID);
							
							$this->addTypeObjectSubGeometryVersion($object_sub_id, (static::VERSION_OFFSET_ALTERNATE - $arr_object_sub['object_sub']['object_sub_location_geometry_version']), $str_location_geometry, FormatTypeObjects::GEOMETRY_SRID);
						}
					}
				} else {
					
				}
				
				$version_log = ($this->versioning && $this->insert_sub ? static::VERSION_NEW : $version);
			
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
				
				$arr_object_sub_definition = ($arr_object_sub['object_sub_definitions'][$object_sub_description_id] ?? null);
				
				$is_ref_type_id = (bool)$arr_object_sub_description['object_sub_description_ref_type_id'];
				$is_reversal = ($is_ref_type_id && $this->arr_types[$arr_object_sub_description['object_sub_description_ref_type_id']]['class'] == StoreType::TYPE_CLASS_REVERSAL);
				
				if (!$is_reversal) {
						
					if (!$arr_object_sub_definition) {
						
						if ($this->insert_sub) {
							
							$value_default = ($arr_object_sub_description['object_sub_description_value_type_settings']['default']['value'] ?? null);
							
							if ($value_default && $arr_object_sub_definition === null) {

								if ($is_ref_type_id) {
									$arr_object_sub_definition['object_sub_definition_ref_object_id'] = $value_default;
								} else {
									$arr_object_sub_definition['object_sub_definition_value'] = $value_default;
								}
							} else {
								continue;
							}
						} else if (!$this->insert && $this->mode == self::MODE_OVERWRITE && $arr_object_sub_definition === null) {
							
							$arr_object_sub_definition = ['object_sub_definition_value' => '', 'object_sub_definition_ref_object_id' => 0];
						} else {
							continue;
						}
					}
					
					$action_object_sub_definition = false;

					$is_defined = (($is_ref_type_id && (isset($arr_object_sub_definition['object_sub_definition_ref_object_id']) || array_key_exists('object_sub_definition_ref_object_id', $arr_object_sub_definition))) || (!$is_ref_type_id && (isset($arr_object_sub_definition['object_sub_definition_value']) || array_key_exists('object_sub_definition_value', $arr_object_sub_definition))));
					
					if ($is_defined) {
						
						$arr_object_sub_definition['object_sub_definition_value'] = $this->parseObjectSubDefinition($object_sub_details_id, $object_sub_description_id, $arr_object_sub_description['object_sub_description_value_type'], $arr_object_sub_definition['object_sub_definition_value']);
						
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
									(!$arr_object_sub_description['object_sub_description_has_multi'] && isset($arr_object_sub_definition['object_sub_definition_value']))
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

									if ($arr_compare_object_sub_definition != static::LAST_OBJECT_ID) {
										
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
											$arr_object_sub_definition['object_sub_definition_value'] = static::appendToValue($arr_object_sub_description['object_sub_description_value_type'], $arr_cur_object_sub_definition, $arr_object_sub_definition['object_sub_definition_value']);
										}
										
										$arr_compare_object_sub_definition = $arr_object_sub_definition['object_sub_definition_value'];
									}	
									
									if (!static::compareSQLValue($arr_object_sub_description['object_sub_description_value_type'], $arr_compare_object_sub_definition, $arr_cur_object_sub_definition) || ((isset($arr_object_sub_definition['object_sub_definition_value']) && $arr_object_sub_definition['object_sub_definition_value'] !== '') && ($arr_cur_object_sub_definition === '' || $arr_cur_object_sub_definition === null))) {

										if (!isset($arr_object_sub_definition['object_sub_definition_value']) || $arr_object_sub_definition['object_sub_definition_value'] === '' || ($arr_object_sub_description['object_sub_description_has_multi'] && !$arr_object_sub_definition['object_sub_definition_value'])) {
											
											if (!$is_appendable) {
												
												// Set object definition record deleted flag (version 0)
												$action_object_sub_definition = 'delete';
											}
										} else {

											// Update object definition record, find existing version or insert new version
											foreach ($this->getTypeObjectSubDescriptionVersions($object_sub_details_id, $object_sub_id, $object_sub_description_id) as $arr_version) {
												
												if (static::compareSQLValue($arr_object_sub_description['object_sub_description_value_type'], $arr_compare_object_sub_definition, $arr_version['object_sub_definition_value']) && $arr_version['object_sub_definition_version'] > 0) {
													
													$version = $arr_version['object_sub_definition_version'];
													$action_object_sub_definition = 'version';
													
													break;
												}
											}
											
											$action_object_sub_definition = ($action_object_sub_definition ?: 'insert');
										}
									}
								} else {
									
									if (!isset($arr_object_sub_definition['object_sub_definition_value']) || $arr_object_sub_definition['object_sub_definition_value'] === '' || ($arr_object_sub_description['object_sub_description_has_multi'] && !$arr_object_sub_definition['object_sub_definition_value'])) {
										
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
								$version = ($arr_version && $arr_version['object_sub_definition_version'] > 0 ? $arr_version['object_sub_definition_version'] : 0) + 1;
							} else {
								
								$version = ($this->versioning ? 1 : static::VERSION_NONE);
							}
							
							if ($is_ref_type_id) {
								$value = $arr_object_sub_definition['object_sub_definition_ref_object_id'];
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
										AND version >= ".static::VERSION_NONE."
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
									
									$this->addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, static::VERSION_OFFSET_ALTERNATE_ACTIVE, $arr_object_sub_definition_text['text']);
								
									$arr_sql_insert = [];
									
									foreach($arr_object_sub_definition_text['tags'] as $value) {
										$arr_sql_insert[] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$value['object_id'].", ".$value['type_id'].", '".DBFunctions::strEscape($value['text'])."', ".$value['group_id'].", 1)";
									}
									
									if ($arr_sql_insert) {
										
										$this->arr_sql_insert['object_sub_definition_objects'][] = implode(',', $arr_sql_insert);
									}
								} else {
									
									$this->addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, static::VERSION_OFFSET_ALTERNATE_ACTIVE, '');
								}
							}
							
							$version_log = ($this->versioning && $this->insert_sub ? static::VERSION_NEW : $version);
							
							$this->addTypeObjectSubDescriptionVersionUser($object_sub_id, $object_sub_description_id, $version_log);
						}
					}
				}
				
				$this->handleSources('object_sub_definition', $arr_object_sub_definition['object_sub_definition_sources'], ['object_sub_id' => $object_sub_id, 'object_sub_description_id' => $object_sub_description_id, 'object_sub_details_id' => $object_sub_details_id]);
			}
		}
		
		if ($this->arr_actions) {
			
			if ($this->insert) {
				$this->addTypeObjectUpdate();
			} else {
				$this->arr_sql_insert['object_dating'][] = "(".$this->object_id.", ".DBFunctions::timeNow().", ".DBFunctions::timeNow().")"; // Update object dating with latest date
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
		
		$has_date = (isset($arr_object_sub['object_sub']['object_sub_date_type']) || isset($arr_object_sub['object_sub']['object_sub_date_chronology']) || isset($arr_object_sub['object_sub']['object_sub_date_object_sub_id']) || isset($arr_object_sub['object_sub']['object_sub_date_start']) || isset($arr_object_sub['object_sub']['object_sub_date_end']));
		$has_location = (isset($arr_object_sub['object_sub']['object_sub_location_type']) || isset($arr_object_sub['object_sub']['object_sub_location_ref_object_id']));
		
		if (($arr_object_sub['object_sub']['object_sub_self'] || $has_date || $has_location) && $arr_object_sub['object_sub']['object_sub_version'] != 'deleted') { // Subobject wants to be inserted/updated
			
			$arr_cur_object_sub = ($object_sub_id ? $this->arr_object_set['object_subs'][$object_sub_id] : false);
			$date_is_locked = ($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_sub_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_description_id']);
			$location_is_locked = ($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_id']);
			
			$object_sub_date_chronology = '';
			
			if ($date_is_locked || !$has_date) {
				
				if ($object_sub_id) {
					$object_sub_date_chronology = FormatTypeObjects::formatToSQLValue('chronology', $arr_cur_object_sub['object_sub']['object_sub_date_chronology']);
				}
			} else {
				
				$arr_chronology = [];
				
				if ($object_sub_id && $arr_cur_object_sub['object_sub']['object_sub_date_chronology'] && (is_array($arr_object_sub['object_sub']['object_sub_date_start']) || is_array($arr_object_sub['object_sub']['object_sub_date_end']))) { // Update existing Chronology
					
					$arr_chronology = FormatTypeObjects::formatToChronology($arr_cur_object_sub['object_sub']['object_sub_date_chronology']);
					
					if (is_array($arr_object_sub['object_sub']['object_sub_date_start'])) {
						unset($arr_chronology['start']);
					}
					if (is_array($arr_object_sub['object_sub']['object_sub_date_end'])) {
						unset($arr_chronology['end']);
					}
				}

				if (!$arr_object_sub['object_sub']['object_sub_date_type']) {
					
					if (isset($arr_object_sub['object_sub']['object_sub_date_chronology'])) {
						$arr_object_sub['object_sub']['object_sub_date_type'] = 'chronology';
					} else if (isset($arr_object_sub['object_sub']['object_sub_date_object_sub_id'])) {
						$arr_object_sub['object_sub']['object_sub_date_type'] = 'object_sub';
					} else { // Make sure its point only, when applicable
						$arr_object_sub['object_sub']['object_sub_date_type'] = 'point';
					}
				}
				
				switch ($arr_object_sub['object_sub']['object_sub_date_type']) {
					case 'point':
					
						if (isset($arr_object_sub['object_sub']['object_sub_date_start'])) {
							
							if (is_array($arr_object_sub['object_sub']['object_sub_date_start'])) {
								$arr_chronology['date']['start'] = $arr_object_sub['object_sub']['object_sub_date_start']['value'];
							} else {
								$arr_chronology['date']['start'] = $arr_object_sub['object_sub']['object_sub_date_start'];
							}
						} else {
							
							if (!$object_sub_id) {
								$arr_chronology['date']['start'] = '-∞';
							}
						}
						
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
							
							if (isset($arr_object_sub['object_sub']['object_sub_date_end'])) {
								
								if (is_array($arr_object_sub['object_sub']['object_sub_date_end'])) {
									$arr_chronology['date']['end'] = $arr_object_sub['object_sub']['object_sub_date_end']['value'];
								} else {
									$arr_chronology['date']['end'] = $arr_object_sub['object_sub']['object_sub_date_end'];
								}
							} else {
								
								if (!$object_sub_id) {
									$arr_chronology['date']['end'] = '∞';
								}
							}
						}

						break;
					case 'object_sub':
						
						if (isset($arr_object_sub['object_sub']['object_sub_date_object_sub_id'])) {
							
							$arr_chronology['reference']['start'] = $arr_object_sub['object_sub']['object_sub_date_object_sub_id'];
						} else if (isset($arr_object_sub['object_sub']['object_sub_date_start']) && is_array($arr_object_sub['object_sub']['object_sub_date_start'])) {
							
							$arr_chronology['reference']['start'] = $arr_object_sub['object_sub']['object_sub_date_start']['object_sub_id'];
						}
																		
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
							
							if (isset($arr_object_sub['object_sub']['object_sub_date_end']) && is_array($arr_object_sub['object_sub']['object_sub_date_end'])) {
								
								$arr_chronology['reference']['end'] = $arr_object_sub['object_sub']['object_sub_date_end']['object_sub_id'];
							}
						}

						break;
					case 'chronology':
					
						if (isset($arr_object_sub['object_sub']['object_sub_date_chronology'])) {
							
							$arr_chronology = $arr_object_sub['object_sub']['object_sub_date_chronology'];
							$arr_chronology = ($arr_chronology && !is_array($arr_chronology) ? FormatTypeObjects::formatToChronology($arr_chronology) : $arr_chronology);

						} else if (isset($arr_object_sub['object_sub']['object_sub_date_start']) && is_array($arr_object_sub['object_sub']['object_sub_date_start'])) {
							
							$arr_chronology_use = $arr_object_sub['object_sub']['object_sub_date_start']['chronology'];
							$arr_chronology_use = ($arr_chronology_use && !is_array($arr_chronology_use) ? FormatTypeObjects::formatToChronology($arr_chronology_use) : $arr_chronology_use);
							
							$arr_chronology['start'] = $arr_chronology_use['start'];
						}
						
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
							
							if (isset($arr_object_sub['object_sub']['object_sub_date_end']) && is_array($arr_object_sub['object_sub']['object_sub_date_end'])) {
							
								$arr_chronology_use = $arr_object_sub['object_sub']['object_sub_date_end']['chronology'];
								$arr_chronology_use = ($arr_chronology_use && !is_array($arr_chronology_use) ? FormatTypeObjects::formatToChronology($arr_chronology_use) : $arr_chronology_use);
							
								$arr_chronology['end'] = $arr_chronology_use['end'];
							}
						}
						
						break;
				}
				
				if ($arr_chronology) {
					$arr_chronology['type'] = ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period'] ? 'period' : 'point');
				}

				$object_sub_date_chronology = FormatTypeObjects::formatToSQLValue('chronology', $arr_chronology);
			}
			
			$object_sub_location_geometry = '';
			$object_sub_location_ref_object_id = $object_sub_location_ref_type_id = $object_sub_location_ref_object_sub_details_id = 0;
			
			if (!$has_location) {
				
				if ($object_sub_id) {
					$object_sub_location_geometry = $arr_cur_object_sub['object_sub']['object_sub_location_geometry'];
					$object_sub_location_ref_object_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'];
					$object_sub_location_ref_type_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_type_id'];
					$object_sub_location_ref_object_sub_details_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id'];
				}
			} else {
				
				if (!$arr_object_sub['object_sub']['object_sub_location_type'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_setting'] == StoreType::VALUE_TYPE_LOCATION_REFERENCE_LOCK) { // Make sure it's reference only, when applicable
					$arr_object_sub['object_sub']['object_sub_location_type'] = 'reference';
				}
				
				switch ($arr_object_sub['object_sub']['object_sub_location_type']) {
					case 'point':
					
						if ($arr_object_sub['object_sub']['object_sub_location_latitude'] || $arr_object_sub['object_sub']['object_sub_location_longitude']) {
							$arr_geometry = ['type' => 'Point', 'coordinates' => [(float)$arr_object_sub['object_sub']['object_sub_location_longitude'], (float)$arr_object_sub['object_sub']['object_sub_location_latitude']]];
							$object_sub_location_geometry = FormatTypeObjects::formatToSQLValue('geometry', $arr_geometry);
						} else {
							$object_sub_location_geometry = '';
						}
						
						break;
					case 'geometry':
					
						$object_sub_location_geometry = FormatTypeObjects::formatToSQLValue('geometry', $arr_object_sub['object_sub']['object_sub_location_geometry']);
						
						break;
					case 'reference':
					
						if ($location_is_locked) {
							
							if ($object_sub_id) {
								$object_sub_location_ref_object_id = $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'];
							}
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
						if (!isset($arr_object_sub_definition['object_sub_definition_value']) || $arr_object_sub_definition['object_sub_definition_value'] === false || $arr_object_sub_definition['object_sub_definition_value'] === '') {
							unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
						}
					}
				}
				
				if ($has_required && $arr_object_sub_description['object_sub_description_is_required']) {

					if ($arr_object_sub_definition === null) {
						
						if ($this->object_id || (!$this->object_id && ($arr_object_sub_description['object_sub_description_value_type_settings']['default']['value'] ?? null))) {
							continue;
						}
					} else if (($is_ref_type_id && $arr_object_sub_definition['object_sub_definition_ref_object_id']) || (!$is_ref_type_id && isset($arr_object_sub_definition['object_sub_definition_value']) && $arr_object_sub_definition['object_sub_definition_value'] !== false && $arr_object_sub_definition['object_sub_definition_value'] !== '')) {
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
						$str_compare_cur = FormatTypeObjects::formatToSQLValue('chronology', $arr_cur_object_sub['object_sub']['object_sub_date_chronology']).'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] && $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] ? 0 : $arr_cur_object_sub['object_sub']['object_sub_location_ref_object_sub_details_id']).'-'.$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'].'-'.(!$arr_cur_object_sub['object_sub']['object_sub_location_ref_object_id'] ? $arr_cur_object_sub['object_sub']['object_sub_location_geometry'] : '');
							
						if ($str_compare == $str_compare_cur) {

							$is_double = true;
							
							foreach ($arr_cur_object_sub['object_sub_definitions'] as $cur_object_sub_description_id => $arr_cur_object_sub_definition) {
								
								if ($arr_object_sub_details['object_sub_descriptions'][$cur_object_sub_description_id]['object_sub_description_ref_type_id']) {
									
									if ($arr_cur_object_sub_definition['object_sub_definition_ref_object_id'] != $arr_object_sub['object_sub_definitions'][$cur_object_sub_description_id]['object_sub_definition_ref_object_id']) {
										$is_double = false;
										break;
									} 
								} else {
									
									$object_sub_definition_value = FormatTypeObjects::formatToSQLValue($arr_object_sub_details['object_sub_descriptions'][$cur_object_sub_description_id]['object_sub_description_value_type'], $arr_object_sub['object_sub_definitions'][$cur_object_sub_description_id]['object_sub_definition_value']);
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
	
	public function parseObjectDefinition($object_description_id, $type, $value) {
		
		$value = FormatTypeObjects::formatToSQLValue($type, $value);
	
		if ($type == 'serial_varchar') {
			
			$has_serial = false;
			$is_array = is_array($value);
			
			if ($is_array) {
				
				foreach ($value as $value_check) {
					
					if (preg_match('/'.static::TAGCODE_TEST_SERIAL_VARCHAR.'/', $value_check)) {
						$has_serial = true;
						break;
					}
				}
			} else {
					
				if (preg_match('/'.static::TAGCODE_TEST_SERIAL_VARCHAR.'/', $value)) {
					$has_serial = true;
				}
			}
			
			if ($has_serial) {

				$num_serial = StoreType::getTypeObjectDescriptionSerialNext($this->type_id, $object_description_id);
				
				if ($is_array) {
					
					foreach ($value as &$value_update) {
						
						$value_update = preg_replace_callback(
							'/'.static::TAGCODE_PARSE_SERIAL_VARCHAR.'/',
							function ($arr_matches) use ($num_serial) {
								
								$num_pad = (int)$arr_matches[1];
								if ($num_pad) {
									$num_serial = str_pad($num_serial, $num_pad, '0', STR_PAD_LEFT);
								}
								
								return $num_serial;
							},
							$value_update
						);
					}
				} else {
					
					$value = preg_replace_callback(
						'/'.static::TAGCODE_PARSE_SERIAL_VARCHAR.'/',
						function ($arr_matches) use ($num_serial) {
							
							$num_pad = (int)$arr_matches[1];
							if ($num_pad) {
								$num_serial = str_pad($num_serial, $num_pad, '0', STR_PAD_LEFT);
							}
							
							return $num_serial;
						},
						$value
					);
				}
			}
		}
		
		return $value;
	}
	
	public function parseObjectSubDefinition($object_sub_details_id, $object_sub_description_id, $type, $value) {
		
		$value = FormatTypeObjects::formatToSQLValue($type, $value);
			
		if ($type == 'serial_varchar') {
			
			$has_serial = false;
			$is_array = is_array($value);
			
			if ($is_array) {
				
				foreach ($value as $value_check) {
					
					if (preg_match('/'.static::TAGCODE_TEST_SERIAL_VARCHAR.'/', $value_check)) {
						$has_serial = true;
						break;
					}
				}
			} else {
					
				if (preg_match('/'.static::TAGCODE_TEST_SERIAL_VARCHAR.'/', $value)) {
					$has_serial = true;
				}
			}
			
			if ($has_serial) {

				$num_serial = StoreType::getTypeObjectSubDescriptionSerialNext($this->type_id, $object_sub_details_id, $object_sub_description_id);
				
				if ($is_array) {
					
					foreach ($value as &$value_update) {
					
						$value_update = preg_replace_callback(
							static::TAGCODE_PARSE_SERIAL_VARCHAR,
							function ($arr_matches) use ($num_serial) {
								
								$num_pad = (int)$arr_matches[1];
								if ($num_pad) {
									$num_serial = str_pad($num_serial, $num_pad, '0', STR_PAD_LEFT);
								}
								
								return $num_serial;
							},
							$value_update
						);
					}
				} else {
					
					$value = preg_replace_callback(
						static::TAGCODE_PARSE_SERIAL_VARCHAR,
						function ($arr_matches) use ($num_serial) {
							
							$num_pad = (int)$arr_matches[1];
							if ($num_pad) {
								$num_serial = str_pad($num_serial, $num_pad, '0', STR_PAD_LEFT);
							}
							
							return $num_serial;
						},
						$value
					);
				}
			}
		}
		
		return $value;
	}
	
	public function getTypeObjectSubObjectSubDetailsID($object_sub_id) {
	
		if (!$this->stmt_object_sub_details_id) {
				
			$this->stmt_object_sub_details_id = DB::prepare("SELECT nodegoat_tos.object_sub_details_id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
				WHERE nodegoat_tos.id = ".DBStatement::assign('object_sub_id', 'i')."
				LIMIT 1
			");
		}
		
		$this->stmt_object_sub_details_id->bindParameters(['object_sub_id' => (int)$object_sub_id]);
		
		$res = $this->stmt_object_sub_details_id->execute();
		$arr_row = $res->fetchRow();

		$res->freeResult();
		
		return $arr_row[0];
	}
	
	private function handleSources($type, $arr_source_types, $arr_options = []) {
		
		if ($this->mode == self::MODE_UPDATE && !isset($arr_source_types)) {
			return;
		}
		
		$arr_source_types = ($arr_source_types ?: []);
		
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
		
		foreach ($arr_source_types as $ref_type_id => $arr_source_objects) {
			
			if (!$ref_type_id) {
				continue;
			}
									
			foreach ((array)$arr_source_objects as $arr_ref_object) {
				
				$ref_object_id = $arr_ref_object[$type.'_source_ref_object_id'];
				$link = $arr_ref_object[$type.'_source_link'];
				$identifier = $ref_object_id.'_'.$link; // Prevent duplications
				
				if (!$ref_object_id || isset($arr_match[$identifier])) {
					continue;
				}

				if (!$arr_cur_source_types || !$func_check_sources($ref_type_id, $ref_object_id, $link)) {
					
					$arr_sql_insert[] = "(".$sql_insert.", ".(int)$ref_object_id.", ".(int)$ref_type_id.", '".DBFunctions::strEscape($link)."', ".($link ? "UNHEX('".value2HashExchange($link)."')" : "''").")";
				}
				
				$arr_match[$identifier] = true;
			}
		}
		
		if ($arr_cur_source_types && !$is_appendable) {
						
			foreach ($arr_cur_source_types as $cur_ref_type_id => $arr_source_objects) {
				
				if (!isset($arr_source_types[$cur_ref_type_id])) { // Only delete sources from Types that are also defined in the new set
					continue;
				}

				foreach ($arr_source_objects as $arr_ref_object) {
					
					$ref_object_id = $arr_ref_object[$type.'_source_ref_object_id'];
					$link = $arr_ref_object[$type.'_source_link'];
					$identifier = $ref_object_id.'_'.$link;
					
					if (isset($arr_match[$identifier])) {
						continue;
					}
					
					$arr_sql_delete[] = "(ref_object_id = ".(int)$ref_object_id." AND hash = ".($link ? "UNHEX('".value2HashExchange($link)."')" : DBFunctions::castAs("''", DBFunctions::CAST_TYPE_BINARY, 16)).")";
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
				(version, type_id".", name, status)
					VALUES
				".$sql_value."
			");
			
			$this->object_id = DB::lastInsertID();
			self::$last_object_id = $this->object_id;
			
			$this->arr_sql_insert['object_dating'][] = "(".$this->object_id.", ".DBFunctions::timeNow().", ".DBFunctions::timeNow().")"; // Ensure object has a dating record, even when making an object exist only (e.g. through merge)
		} else {
			
			$this->arr_sql_insert['object_version'][] = $sql_value;
		}
		
		return $this->object_id;
	}
	
	protected function addTypeObjectVersionUser($version, $user_id = false, $date = false, $system_object_id = false) {
		
		$this->arr_actions['object'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_version_user'][] = "(".$this->object_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : DBFunctions::timeNow()).", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
	}
	
	protected function addTypeObjectDescriptionVersion($object_description_id, $version, $value) {
		
		$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
		$is_alternate = ($version <= static::VERSION_OFFSET_ALTERNATE_ACTIVE);
		$arr_sql_insert = [];
		
		if ($arr_object_description['object_description_ref_type_id']) {
			
			$count = 0;
			
			foreach ($value as $object_definition_ref_object_id) {
				
				$arr_sql_insert[] = "(".$object_description_id.", ".$this->object_id.", ".$version.", ".(int)($object_definition_ref_object_id === static::LAST_OBJECT_ID ? self::$last_object_id : $object_definition_ref_object_id).", ".$count.", ".($this->versioning ? '0' : '1').")";
				
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
			
			if ($arr_object_description['object_description_ref_type_id'] || $arr_object_description['object_description_has_multi']) {
				
				$sql_value = (StoreType::getValueTypeValue($value_type) == 'ref_object_id' ? implode(',', array_keys($arr_sql_insert)) : "'".implode("','", array_keys($arr_sql_insert))."'");
				
				$this->arr_sql_delete['object_definition_version'][] = "DELETE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type)."
					WHERE object_description_id = ".$object_description_id." AND object_id = ".$this->object_id." AND version = ".$version."
						AND ".StoreType::getValueTypeValue($value_type)." NOT IN (".$sql_value.")
				";
			}
		}
	}
	
	protected function addTypeObjectDescriptionVersionUser($object_description_id, $version, $user_id = false, $date = false, $system_object_id = false) {
		
		$this->arr_actions['object_definition'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_definition_version_user'][] = "(".$object_description_id.", ".$this->object_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : DBFunctions::timeNow()).", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
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
			
			$this->addTypeObjectSubChronologyVersion($object_sub_id, $arr_value['object_sub_date_version'], $arr_value['object_sub_date_chronology']);
		}
		
		if ($arr_value['object_sub_location_geometry']) {
			
			$this->addTypeObjectSubGeometryVersion($object_sub_id, $arr_value['object_sub_location_geometry_version'], $arr_value['object_sub_location_geometry']);
		}
		
		return $object_sub_id;
	}
	
	protected function addTypeObjectSubChronologyVersion($object_sub_id, $version, $value) {
		
		$arr_sql_split = explode(';', $value);

		$this->arr_sql_insert['object_sub_date_version'][] = "(".$object_sub_id.", ".$arr_sql_split[0].", ".(int)$version.")";
		unset($arr_sql_split[0]);
		
		foreach ($arr_sql_split as $arr_sql_chronology) {
			$this->arr_sql_insert['object_sub_date_chronology_version'][] = "(".$object_sub_id.", ".$arr_sql_chronology.", TRUE, ".(int)$version.")";
		}
	}
	
	protected function addTypeObjectSubGeometryVersion($object_sub_id, $version, $value, $num_srid = null) {
		
		if ($num_srid === null) {
			
			$num_srid = FormatTypeObjects::GEOMETRY_SRID;
			
			if (FormatTypeObjects::GEOMETRY_SRID) {
				
				$num_srid = FormatTypeObjects::geometryToSRID($value); // Get SRID from JSON
			}
		}
		
		if (DB::ENGINE_IS_MYSQL) {
			$sql_value = "ST_GeomFromGeoJSON('".DBFunctions::strEscape($value)."', 2, ".(int)$num_srid.")";
		} else {
			$sql_value = "ST_SetSRID(ST_GeomFromGeoJSON('".DBFunctions::strEscape($value)."'), ".(int)$num_srid.")";
		}
		
		$this->arr_sql_insert['object_sub_location_geometry_version'][] = "(".$object_sub_id.", ".$sql_value.", ".(int)$version.")";
	}
	
	protected function addTypeObjectSubVersionUser($object_sub_id, $version, $user_id = false, $date = false, $system_object_id = false) {
		
		$this->arr_actions['object_sub'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_sub_version_user'][] = "(".$object_sub_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : DBFunctions::timeNow()).", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
	}
	
	protected function addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, $version, $value) {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$is_alternate = ($version <= static::VERSION_OFFSET_ALTERNATE_ACTIVE);
		
		$value_type = $arr_object_sub_description['object_sub_description_value_type'];
		$this->arr_sql_insert['object_sub_definition_version'][$value_type][] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$version.", ".($arr_object_sub_description['object_sub_description_ref_type_id'] ? (int)($value === static::LAST_OBJECT_ID ? self::$last_object_id : $value) : "'".DBFunctions::strEscape($value)."'").", ".($this->versioning || $is_alternate ? '0' : '1').")";
	}
	
	protected function addTypeObjectSubDescriptionVersionUser($object_sub_id, $object_sub_description_id, $version, $user_id = false, $date = false, $system_object_id = false) {
		
		$this->arr_actions['object_sub_definition'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_sub_definition_version_user'][] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : DBFunctions::timeNow()).", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
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
						(id, version, type_id".", name, status)
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
						(object_id, version, user_id, date, system_object_id)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_id, version, user_id, date', ['object_id'])."
					";
					break;
				case 'object_definition_version':
				
					foreach ($arr_sql_insert as $value_type => $arr_sql) {
						
						$sql_value = StoreType::getValueTypeValue($value_type);
						$sql_table_affix = StoreType::getValueTypeTable($value_type);
						$sql_primary_key = ($sql_value == 'ref_object_id' ? 'object_id, object_description_id, ref_object_id, identifier, version' : 'object_id, object_description_id, identifier, version');
						
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
						(object_description_id, object_id, version, user_id, date, system_object_id)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_id, object_description_id, version, user_id, date', ['object_id'])."
					";
					break;
				case 'object_sub_version_user':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')."
						(object_sub_id, version, user_id, date, system_object_id)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_id, version, user_id, date', ['object_sub_id'])."
					";
					break;
				case 'object_sub_definition_version':
				
					foreach ($arr_sql_insert as $value_type => $arr_sql) {
						
						$sql_value = StoreType::getValueTypeValue($value_type);
						$sql_table_affix = StoreType::getValueTypeTable($value_type);
						$sql_primary_key = ($sql_value == 'ref_object_id' ? 'object_sub_description_id, object_sub_id, ref_object_id, version' : 'object_sub_description_id, object_sub_id, version');
						
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
						(object_sub_description_id, object_sub_id, version, user_id, date, system_object_id)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_id, object_sub_description_id, version, user_id, date', ['object_sub_id'])."
					";
					break;
				
				case 'object_definition_objects':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')."
						(object_description_id, object_id, ref_object_id, ref_type_id, value, identifier, state)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_id, object_description_id, ref_object_id, identifier', false, "value = CASE state WHEN 0 THEN [value] ELSE CONCAT(value, ' ', [value]) END, state = [state]")."
					";
					break;
				case 'object_sub_definition_objects':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')."
						(object_sub_description_id, object_sub_id, ref_object_id, ref_type_id, value, identifier, state)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_sub_id, object_sub_description_id, ref_object_id, identifier', false, "value = CASE state WHEN 0 THEN [value] ELSE CONCAT(value, ' ', [value]) END, state = [state]")."
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
						".DBFunctions::onConflict('object_id, object_description_id, ref_object_id, hash', ['object_id'])."
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
						".DBFunctions::onConflict('object_sub_id, object_sub_description_id, ref_object_id, hash', ['object_sub_id'])."
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
		
		if ($this->arr_actions === false) { // When this class has not made changes i.e. through save(), do make sure the Object is touched.
			$this->touch();
		}
		
		DB::commitTransaction('store_type_objects');
	}
	
	public function discard($all = false) {
				
		DB::startTransaction('store_type_objects');
		
		$this->discardTypeObjectVersion($all);
		$this->discardTypeObjectDescriptionVersion($all);
		$this->discardTypeObjectSubVersion($all);
		$this->discardTypeObjectSubDescriptionVersion($all);
		
		if ($this->arr_actions === false) { // When this class has not made changes i.e. through save(), do make sure the Object is touched.
			$this->touch();
		}
		
		DB::commitTransaction('store_type_objects');
	}
	
	public function touch($str_sql_table = false) {
		
		static::touchTypeObjects($this->type_id, ($str_sql_table ?: $this->str_sql_table_name_object_updates));
	}

	// Delete
	
	public function delTypeObject($accept) {
						
		if (!$this->versioning || ($this->arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL && $accept)) {
			
			$res = DB::query("
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
					"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)",
					['status' => "CASE
						WHEN active = TRUE THEN -1
						ELSE 0
					END",
					'active' => 'FALSE']
				)."
			");
			
			$this->touch();
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')."
				(object_id, version, user_id, date)
				SELECT nodegoat_to_updates.id, 0, ".$this->user_id.", ".DBFunctions::timeNow()."
					FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates
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
				$this->touch();
			} else {
				
				$this->presetTypeObjectVersion();
			}
		}
	}
	
	public function clearTypeObjects() {
		
		$table_name_objects_deleted = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_deleted');
		
		$sql_query = "SELECT DISTINCT id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
			WHERE type_id = ".$this->type_id."
				"."
		";

		DB::queryMulti("
			CREATE TEMPORARY TABLE ".$table_name_objects_deleted." (
				id INT,
					PRIMARY KEY (id)
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
				".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query.")"
				:
				"; INSERT INTO ".$table_name_objects_deleted."
					(".$sql_query.")
				")."
			;
			
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
				"JOIN ".$table_name_objects_deleted." AS nodegoat_to_all ON (nodegoat_to_all.id = nodegoat_to.id)",
				['status' => "CASE
					WHEN active = TRUE THEN -1
					ELSE 0
				END",
				'active' => 'FALSE']
			).";
		");
		
		$this->touch($table_name_objects_deleted);
		
		DB::query("DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_objects_deleted);
	}
	
	// Lock
	
	public function handleLock($type, $key = false) {
		
		if (!$this->object_id) {
			return false;
		}
		
		$key = ($key ?: $this->lock_key);
		
		$table_name = DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK');
		$sql_expired = "(".$table_name.".identifier != '' AND (".$table_name.".date_updated + ".DBFunctions::interval(self::$timeout_lock, 'SECOND').") < ".DBFunctions::timeNow().")";
		
		$sql_insert = "INSERT INTO ".$table_name."
			(object_id, type, user_id, date, date_updated, identifier)
				VALUES
			(".$this->object_id.", ".(int)$type.", ".(int)$this->user_id.", ".DBFunctions::timeNow().", ".DBFunctions::timeNow().", '".DBFunctions::strEscape($key)."')
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
				"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_lock.object_id)",
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
				"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_lock.object_id)"
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
	
	// Module/Process Mode
	
	public function getModuleState() {
		
		$type_id = ($this->arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL ? StoreType::getSystemTypeID('reversal') : $this->type_id);
		$object_description_id = StoreType::getSystemTypeObjectDescriptionID($type_id, 'module');
				
		$res = DB::query("SELECT nodegoat_to_def_modules.state
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULES')." AS nodegoat_to_def_modules
			WHERE nodegoat_to_def_modules.object_id = ".$this->object_id."
				AND nodegoat_to_def_modules.object_description_id = ".(int)$object_description_id."
				AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'record', 'nodegoat_to_def_modules')."
		");
		
		$arr = [];
		
		$arr_row = $res->fetchRow();
		$bit_state = (int)$arr_row[0];
		
		return $bit_state;
	}
	
	public function updateModuleState($bit_state, $mode = BIT_MODE_ADD) {
		
		$type_id = ($this->arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL ? StoreType::getSystemTypeID('reversal') : $this->type_id);
		$object_description_id = StoreType::getSystemTypeObjectDescriptionID($type_id, 'module');
		
		$sql_state = (int)$bit_state;
		if ($mode == BIT_MODE_ADD) {
			$sql_state = 'state | '.(int)$bit_state;
		} else if ($mode == BIT_MODE_SUBTRACT) {
			$sql_state = 'state & ~'.(int)$bit_state;
		}
		
		$res = DB::query("
			".DBFunctions::updateWith(
				DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULES'), 'nodegoat_to_def_modules', 'object_id',
				"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def_modules.object_id)",
				['state' => $sql_state]
			)."
				AND nodegoat_to_def_modules.object_description_id = ".(int)$object_description_id."
				AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'record', 'nodegoat_to_def_modules')."
		");
	}
		
	// Discussion
	
	public function handleDiscussion($arr_discussion) {
							
		$sql = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DISCUSSION')."
			(object_id, body, date_edited, user_id_edited)
				VALUES
			(".$this->object_id.", '".DBFunctions::strEscape($arr_discussion['object_discussion_body'])."', ".DBFunctions::timeNow().", ".(int)$this->user_id.")
			".DBFunctions::onConflict('object_id', ['body', 'date_edited', 'user_id_edited'])."
		;";
		
		$sql .= "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')."
			(object_id, date, date_object, date_discussion)
				VALUES
			(".$this->object_id.", ".DBFunctions::timeNow().", ".DBFunctions::timeNow().", ".DBFunctions::timeNow().")
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
				WHEN (date_edited + ".DBFunctions::interval(6, 'SECOND').") > ".DBFunctions::timeNow()." THEN user_id_edited
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
    
	public function getTypeObjectDescriptionVersions($object_description_id, $do_full = false) {
		
		$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
		$has_multi = $arr_object_description['object_description_has_multi'];
		
		$stmt = $this->stmt_object_description_versions[$object_description_id];
		
		if (!$stmt) {
						
			$this->stmt_object_description_versions[$object_description_id] = DB::prepare("SELECT
				nodegoat_to_def.".StoreType::getValueTypeValue($arr_object_description['object_description_value_type']).",
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
						'object_definition_ref_object_id' => ($has_multi ? [] : ''),
						'object_definition_value' => null
					];
				}
				
				if (!$value) {
					continue;
				}
				
				if ($has_multi) {
					$s_arr['object_definition_ref_object_id'][] = $value;
				} else {
					$s_arr['object_definition_ref_object_id'] = $value;
				}
			}
			
			if ($do_full && $arr) {
				
				$arr_objects = [];
				
				foreach ($arr as $arr_version) {
					
					if ($has_multi) {
						
						foreach ($arr_version['object_definition_ref_object_id'] as $ref_object_id) {
							$arr_objects[$ref_object_id] = $ref_object_id;
						}
					} else {
						
						$arr_objects[$arr_version['object_definition_ref_object_id']] = $arr_version['object_definition_ref_object_id'];
					}
				}
				
				$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($arr_object_description['object_description_ref_type_id'], $arr_objects);
				
				foreach ($arr as &$arr_version) {
					
					if ($has_multi) {
						
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
						'object_definition_value' => ($has_multi ? [] : '')
					];
				}
				
				if ($value === '' || $value === null) {
					continue;
				}
				
				if ($has_multi) {
					$s_arr['object_definition_value'][] = $value;
				} else {
					$s_arr['object_definition_value'] = $value;
				}
			}
		}
		
		return $arr;
	}
	
	public function getTypeObjectSubVersions($object_sub_id, $do_full = false) {
		
		if (!$this->stmt_object_sub_versions) {
			
			$this->stmt_object_sub_versions = DB::prepare("SELECT
				CASE WHEN nodegoat_tos_date.object_sub_id IS NOT NULL THEN ".FormatTypeObjects::formatFromSQLValue('chronology', 'nodegoat_tos_date')." ELSE '' END AS date_chronology, nodegoat_tos.date_version,
				nodegoat_tos.location_ref_object_id, nodegoat_tos.location_ref_type_id, nodegoat_tos.location_ref_object_sub_details_id, CASE WHEN nodegoat_tos_geo.object_sub_id IS NOT NULL THEN ".FormatTypeObjects::formatFromSQLValue('geometry', 'nodegoat_tos_geo.geometry')." ELSE '' END AS location_geometry, nodegoat_tos.location_geometry_version,
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
		
		if ($do_full && $arr) {
			
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
	
	public function getTypeObjectSubDescriptionVersions($object_sub_details_id, $object_sub_id, $object_sub_description_id, $do_full = false) {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$has_multi = $arr_object_sub_description['object_sub_description_has_multi'];
		
		$stmt = $this->stmt_object_sub_description_versions[$object_sub_description_id];
		
		if (!$stmt) {
						
			$this->stmt_object_sub_description_versions[$object_sub_description_id] = DB::prepare("SELECT
				nodegoat_tos_def.".StoreType::getValueTypeValue($arr_object_sub_description['object_sub_description_value_type']).",
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
						'object_sub_definition_ref_object_id' => ($has_multi ? [] : ''),
						'object_sub_definition_value' => null
					];
				}
				
				if (!$value) {
					continue;
				}
				
				if ($has_multi) {
					$s_arr['object_sub_definition_ref_object_id'][] = $value;
				} else {
					$s_arr['object_sub_definition_ref_object_id'] = $value;
				}
			}
			
			if ($do_full && $arr) {
				
				$arr_objects = [];
				
				foreach ($arr as $arr_version) {
					
					if ($has_multi) {
						
						foreach ($arr_version['object_sub_definition_ref_object_id'] as $ref_object_id) {
							$arr_objects[$ref_object_id] = $ref_object_id;
						}
					} else {
						
						$arr_objects[$arr_version['object_sub_definition_ref_object_id']] = $arr_version['object_sub_definition_ref_object_id'];
					}
				}
				
				$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($arr_object_sub_description['object_sub_description_ref_type_id'], $arr_objects);
				
				foreach ($arr as &$arr_version) {
					
					if ($has_multi) {
						
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
						'object_sub_definition_value' => ($has_multi ? [] : '')
					];
				}
				
				if ($value === '' || $value === null) {
					continue;
				}
				
				if ($has_multi) {
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
						
		$res = DB::query("SELECT nodegoat_to_ver.version, nodegoat_to_ver.date, nodegoat_to_ver.system_object_id, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." nodegoat_to_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_to_ver.user_id)
							WHERE nodegoat_to_ver.object_id = ".$this->object_id."
							ORDER BY CASE
								WHEN nodegoat_to_ver.version = ".static::VERSION_NEW." THEN 1
								ELSE nodegoat_to_ver.version
							END DESC, nodegoat_to_ver.date
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			if ($arr_row['version'] == static::VERSION_NEW) {
				$arr[static::VERSION_NEW] = $arr_row;
				$arr_row['version'] = 1;
			}
			
			$arr_row['name'] = ($arr_row['id'] ? $arr_row['name'] : 'System');
			$arr[$arr_row['version']][] = $arr_row;
		}
		
		return $arr;
	}
	
	public function getTypeObjectDescriptionVersionsUsers($object_description_id) {
				
		$arr = [];
						
		$res = DB::query("SELECT nodegoat_to_def_ver.version, nodegoat_to_def_ver.date, nodegoat_to_def_ver.system_object_id, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." nodegoat_to_def_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_to_def_ver.user_id)
							WHERE nodegoat_to_def_ver.object_id = ".$this->object_id." AND nodegoat_to_def_ver.object_description_id = ".$object_description_id."
							ORDER BY CASE
								WHEN nodegoat_to_def_ver.version = ".static::VERSION_NEW." THEN 1
								ELSE nodegoat_to_def_ver.version
							END DESC, nodegoat_to_def_ver.date
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			if ($arr_row['version'] == static::VERSION_NEW) {
				$arr[static::VERSION_NEW] = $arr_row;
				$arr_row['version'] = 1;
			}
			
			$arr_row['name'] = ($arr_row['id'] ? $arr_row['name'] : 'System');
			$arr[$arr_row['version']][] = $arr_row;
		}
		
		return $arr;
	}
	
	public function getTypeObjectSubVersionsUsers($object_sub_id) {
				
		$arr = [];
						
		$res = DB::query("SELECT nodegoat_tos_ver.version, nodegoat_tos_ver.date, nodegoat_tos_ver.system_object_id, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')." nodegoat_tos_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_tos_ver.user_id)
							WHERE nodegoat_tos_ver.object_sub_id = ".$object_sub_id."
							ORDER BY CASE
								WHEN nodegoat_tos_ver.version = ".static::VERSION_NEW." THEN 1
								ELSE nodegoat_tos_ver.version
							END DESC, nodegoat_tos_ver.date
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			if ($arr_row['version'] == static::VERSION_NEW) {
				$arr[static::VERSION_NEW] = $arr_row;
				$arr_row['version'] = 1;
			}
			
			$arr_row['name'] = ($arr_row['id'] ? $arr_row['name'] : 'System');
			$arr[$arr_row['version']][] = $arr_row;
		}
		
		return $arr;
	}
	
	public function getTypeObjectSubDescriptionVersionsUsers($object_sub_id, $object_sub_description_id) {
				
		$arr = [];
						
		$res = DB::query("SELECT nodegoat_tos_def_ver.version, nodegoat_tos_def_ver.date, nodegoat_tos_def_ver.system_object_id, u.id, u.name
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')." nodegoat_tos_def_ver
								LEFT JOIN ".DB::getTable('TABLE_USERS')." u ON (u.id = nodegoat_tos_def_ver.user_id)
							WHERE nodegoat_tos_def_ver.object_sub_id = ".$object_sub_id." AND nodegoat_tos_def_ver.object_sub_description_id = ".$object_sub_description_id."
							ORDER BY CASE
								WHEN nodegoat_tos_def_ver.version = ".static::VERSION_NEW." THEN 1
								ELSE nodegoat_tos_def_ver.version
							END DESC, nodegoat_tos_def_ver.date
		");
		
		while ($arr_row = $res->fetchAssoc()) {
			
			if ($arr_row['version'] == static::VERSION_NEW) {
				$arr[static::VERSION_NEW] = $arr_row;
				$arr_row['version'] = 1;
			}
			
			$arr_row['name'] = ($arr_row['id'] ? $arr_row['name'] : 'System');
			$arr[$arr_row['version']][] = $arr_row;
		}
		
		return $arr;
	}
	
	// Version Update
	
	public function updateTypeObjectVersion() {
				
		if (!$this->versioning) {
			
			$str_sql_table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			$res = DB::queryMulti("
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name_object_updates_changed.";
				
				CREATE TEMPORARY TABLE ".$str_sql_table_name_object_updates_changed." (
					id INT,
					description_id INT,
					PRIMARY KEY (id, description_id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";

				INSERT INTO ".$str_sql_table_name_object_updates_changed."
					(SELECT nodegoat_to.id AS id, 0 AS description_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
							JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)
						WHERE nodegoat_to.version = ".static::VERSION_NONE."
							AND nodegoat_to.status = 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
					"JOIN ".$str_sql_table_name_object_updates_changed." ON (".$str_sql_table_name_object_updates_changed.".id = nodegoat_to.id)",
					['active' => "CASE 
						WHEN nodegoat_to.version = ".static::VERSION_NONE." THEN TRUE
						ELSE FALSE
					END",
					'status' => '0']
				)."
					AND nodegoat_to.version >= ".static::VERSION_NONE."
				;
			");
		} else {
		
			$res = DB::queryMulti("
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS'), 'nodegoat_to', 'id',
					"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)",
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
					"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to.id)",
					['active' => 'TRUE']
				)."
					AND nodegoat_to.version = (SELECT
						CASE
							WHEN version = ".static::VERSION_NEW." THEN 1
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
					"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_ver.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
				)."
					AND nodegoat_to_ver.date_audited IS NULL
				;
			");
		}
	}
	
	public function updateTypeObjectDescriptionVersion() {
				
		$sql = '';
				
		if (!$this->versioning) {
			
			$str_sql_table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
				
				$sql .= "
					DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name_object_updates_changed.";
				
					CREATE TEMPORARY TABLE ".$str_sql_table_name_object_updates_changed." (
						id INT,
						description_id INT,
						PRIMARY KEY (id, description_id)
					) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
					
					INSERT INTO ".$str_sql_table_name_object_updates_changed."
						(SELECT nodegoat_to_def.object_id AS id, nodegoat_to_def.object_description_id AS description_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix." nodegoat_to_def
								JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def.object_id)
							WHERE nodegoat_to_def.version = ".static::VERSION_NONE."
								AND nodegoat_to_def.status = 1
						)
						".DBFunctions::onConflict('id, description_id', ['id'])."
					; # Use conflict clause because ref_object_id could return multiple results for one object description
					
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_to_def', ['object_id', 'object_description_id'],
						"JOIN ".$str_sql_table_name_object_updates_changed." ON (".$str_sql_table_name_object_updates_changed.".id = nodegoat_to_def.object_id AND ".$str_sql_table_name_object_updates_changed.".description_id = nodegoat_to_def.object_description_id)",
						['active' => "CASE 
							WHEN nodegoat_to_def.version = ".static::VERSION_NONE." THEN TRUE
							ELSE FALSE
						END",
						'status' => '0']
					)."
						AND nodegoat_to_def.version >= ".static::VERSION_NONE."
					;
				";
			}
		} else {
			
			foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
		
				$sql .= "
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_to_def', 'object_id',
						"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def.object_id)",
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
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_to_def', 'object_id',
						"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def.object_id)",
						['active' => 'TRUE']
					)."
						AND nodegoat_to_def.version = (SELECT
							CASE
								WHEN version = ".static::VERSION_NEW." THEN 1
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
					"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def_ver.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
				)."
					AND nodegoat_to_def_ver.date_audited IS NULL
				;
			";
		}
		
		$res = DB::queryMulti($sql);
	}
	
	public function updateTypeObjectSubVersion() {
		
		if (!$this->versioning) {
			
			$str_sql_table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			$res = DB::queryMulti("
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name_object_updates_changed.";
				
				CREATE TEMPORARY TABLE ".$str_sql_table_name_object_updates_changed." (
					id INT,
					description_id INT,
					PRIMARY KEY (id, description_id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";

				INSERT INTO ".$str_sql_table_name_object_updates_changed."
					(SELECT nodegoat_tos.id AS id, 0 AS description_id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
							JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)
						WHERE nodegoat_tos.version = ".static::VERSION_NONE."
							AND nodegoat_tos.status = 1
					)
				;
				
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'nodegoat_tos', 'id',
					"JOIN ".$str_sql_table_name_object_updates_changed." ON (".$str_sql_table_name_object_updates_changed.".id = nodegoat_tos.id)",
					['active' => "CASE 
						WHEN nodegoat_tos.version = ".static::VERSION_NONE." THEN TRUE
						ELSE FALSE
					END",
					'status' => '0']
				)."
					AND nodegoat_tos.version >= ".static::VERSION_NONE."
				;
			");
		} else {
		
			$res = DB::queryMulti("
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS'), 'nodegoat_tos', 'object_id',
					"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
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
					"JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
					['active' => 'TRUE']
				)."
					AND nodegoat_tos.version = (SELECT
						CASE
							WHEN version = ".static::VERSION_NEW." THEN 1
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
					JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
				)."
					AND nodegoat_tos_ver.date_audited IS NULL
				;
			");
		}
	}
	
	public function updateTypeObjectSubDescriptionVersion() {
				
		$sql = '';
				
		if (!$this->versioning) {
			
			$str_sql_table_name_object_updates_changed = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_changed');
			
			foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
				
				$sql .= "
					DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$str_sql_table_name_object_updates_changed.";
				
					CREATE TEMPORARY TABLE ".$str_sql_table_name_object_updates_changed." (
						id INT,
						description_id INT,
						PRIMARY KEY (id, description_id)
					) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
					
					INSERT INTO ".$str_sql_table_name_object_updates_changed."
						(SELECT nodegoat_tos_def.object_sub_id AS id, nodegoat_tos_def.object_sub_description_id AS description_id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name_affix." nodegoat_tos_def
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
								JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)
							WHERE nodegoat_tos_def.version = ".static::VERSION_NONE."
								AND nodegoat_tos_def.status = 1
						)
					;
					
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_tos_def', ['object_sub_id', 'object_sub_description_id'],
						"JOIN ".$str_sql_table_name_object_updates_changed." ON (".$str_sql_table_name_object_updates_changed.".id = nodegoat_tos_def.object_sub_id AND ".$str_sql_table_name_object_updates_changed.".description_id = nodegoat_tos_def.object_sub_description_id)",
						['active' => "CASE 
							WHEN nodegoat_tos_def.version = ".static::VERSION_NONE." THEN TRUE
							ELSE FALSE
						END",
						'status' => '0']
					)."
						AND nodegoat_tos_def.version >= ".static::VERSION_NONE."
					;
				";
			}
		} else {

			foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
		
				$sql .= "
					".DBFunctions::updateWith(
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_tos_def', 'object_sub_id',
						"JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
						JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
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
						DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_tos_def', 'object_sub_id',
						"JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id)
						JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
						['active' => 'TRUE']
					)."
						AND nodegoat_tos_def.version = (SELECT
							CASE
								WHEN version = ".static::VERSION_NEW." THEN 1
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
					JOIN ".$this->str_sql_table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_tos.object_id)",
					['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
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
								WHEN nodegoat_to_ver_last.version = ".static::VERSION_NEW." THEN 1
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
						FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates 
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
		
		foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
			
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_to_def', ['object_id', 'object_description_id'],
					[
						"JOIN (SELECT
							nodegoat_to_updates.id,
							nodegoat_to_def.object_description_id,
							(SELECT
								CASE
									WHEN nodegoat_to_def_ver_last.version = ".static::VERSION_NEW." THEN 1
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
							FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates 
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to_updates.id)
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
								WHEN nodegoat_tos_ver_last.version = ".static::VERSION_NEW." THEN 1
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
						FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates 
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
		
		foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
			
			$sql .= "
				".DBFunctions::updateWith(
					DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name_affix, 'nodegoat_tos_def', ['object_sub_id', 'object_sub_description_id'],
					[
						"JOIN (SELECT
							nodegoat_tos.id AS object_sub_id,
							nodegoat_tos_def.object_sub_description_id,
							(SELECT
								CASE
									WHEN nodegoat_tos_def_ver_last.version = ".static::VERSION_NEW." THEN 1
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
							FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates 
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_updates.id)
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name_affix." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id)
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
						FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates 
					) nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_ver.object_id)",
					'nodegoat_to_updates.last_version'
				],
				['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
			)."
				AND nodegoat_to_ver.date_audited IS NULL
				".(!$all ? "AND nodegoat_to_ver.version = last_version" : "")."
		");
		
		$this->presetTypeObjectVersion();
	}
	
	public function discardTypeObjectDescriptionVersion($all = false) {
		
		$sql = '';
		
		foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
				
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
							FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$str_sql_table_name_affix." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to_updates.id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_FULL, 'record', 'nodegoat_to_def').")
						) nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def_ver.object_id AND nodegoat_to_updates.object_description_id = nodegoat_to_def_ver.object_description_id)",
						'nodegoat_to_updates.last_version'
					],
					['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
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
						FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates 
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_updates.id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_FULL, 'object_sub', 'nodegoat_tos').")
					) nodegoat_to_updates ON (nodegoat_to_updates.object_sub_id = nodegoat_tos_ver.object_sub_id)",
					'nodegoat_to_updates.last_version'
				],
				['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
			)."
				AND nodegoat_tos_ver.date_audited IS NULL
				".(!$all ? "AND nodegoat_tos_ver.version = last_version" : "")."
		");
		
		$this->presetTypeObjectSubVersion();
	}
	
	public function discardTypeObjectSubDescriptionVersion($all = false) {
				
		$sql = '';
		
		foreach (StoreType::getValueTypeTables() as $str_sql_table_name_affix => $arr_value_type_table) {
			
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
							FROM ".$this->str_sql_table_name_object_updates." nodegoat_to_updates 
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_updates.id)
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$str_sql_table_name_affix." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND ".GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_FULL, 'record', 'nodegoat_tos_def').")
						) nodegoat_to_updates ON (nodegoat_to_updates.object_sub_id = nodegoat_tos_def_ver.object_sub_id AND nodegoat_to_updates.object_sub_description_id = nodegoat_tos_def_ver.object_sub_description_id)",
						'nodegoat_to_updates.last_version'
					],
					['user_id_audited' => $this->user_id, 'date_audited' => DBFunctions::timeNow()]
				)."
					AND nodegoat_tos_def_ver.date_audited IS NULL
					".(!$all ? "AND nodegoat_tos_def_ver.version = last_version" : "")."
			;";
		}
		
		$res = DB::queryMulti($sql);
		
		$this->presetTypeObjectSubDescriptionVersion();
	}
	
	public function cleanupTypeObjects() {
				
		StoreTypeObjectsProcessing::cleanupTypesObjects($this->str_sql_table_name_object_updates);
	}
		
	protected function addTypeObjectUpdate() {
				
		if (!$this->has_object_updates) {
			
			$this->generateTypeObjectsUpdateTable();
			$this->has_object_updates = true;
		}
				
		$this->stmt_object_updates->bindParameters(['object_id' => $this->object_id]);
		$res = $this->stmt_object_updates->execute();
				
		if (!$res->getAffectedRowCount()) {
			
			$res = DB::query("SELECT TRUE FROM ".$this->str_sql_table_name_object_updates."	WHERE id = ".$this->object_id); // Check if ID was already inserted (duplicate/no rows affected).
			
			if (!$res->getRowCount()) {
				error(getLabel('msg_object_missing'));
			}
		}
	}
	
	protected function generateTypeObjectsUpdateTable() {
			
		DB::queryMulti("
			DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$this->str_sql_table_name_object_updates.";
			
			CREATE TEMPORARY TABLE ".$this->str_sql_table_name_object_updates." (
				id INT,
				PRIMARY KEY (id)
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
		");
		
		$this->stmt_object_updates = DB::prepare("INSERT INTO ".$this->str_sql_table_name_object_updates." (id)
			SELECT id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')."
				WHERE id = ".DBStatement::assign('object_id', 'i')."
					AND type_id = ".$this->type_id."
					"."
				".DBFunctions::onConflict('id', false)."
		");
	}

	// Direct calls
	
	public static function touchTypeObjects($type_id, $sql_table) {
		
		$res = DB::query("
			INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')."
				(object_id, date, date_object)
				(SELECT
					id, ".DBFunctions::timeNow().", ".DBFunctions::timeNow()."
						FROM ".$sql_table."
				)
				".DBFunctions::onConflict('object_id', ['date'])."
		");
	}
	
	public static function updateModuleObjectTypeObjects($object_description_id, $object_id, $arr_ref_object_ids, $status = 0) {
		
		if ($arr_ref_object_ids) {
			
			if (!is_array($arr_ref_object_ids)) {
				$arr_ref_object_ids = (array)$arr_ref_object_ids;
			}
			
			$arr_sql_insert = [];
			
			foreach ($arr_ref_object_ids as $ref_object_id) {
				$arr_sql_insert[] = "(".(int)$object_id.", ".(int)$object_description_id.", ".(int)$ref_object_id.", ".(int)$status.")";
			}			
				
			$res = DB::query("INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULE_STATUS')."
				(object_id, object_description_id, ref_object_id, status)
					VALUES
				".implode(',', $arr_sql_insert)."
				".DBFunctions::onConflict('object_id, object_description_id, ref_object_id', ['status'])."
			");
		} else {
			
			$res = DB::query("UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULE_STATUS')." SET
					status = ".(int)$status."
				WHERE object_id = ".(int)$object_id."
					AND object_description_id = ".(int)$object_description_id."
			");
		}		
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
		
		$sql_users = "IN (".implode(',', arrParseRecursive($arr_users, TYPE_INTEGER)).")";
				
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

	public static function parseObjectDefinitionText($str_text) {

		$str_text = preg_replace_callback(
			'/'.FormatTypeObjects::TAGCODE_PARSE_TEXT_OBJECT_OPEN.'/s',
			function ($matches) {
				return '<span class="tag" data-ids="'.$matches[1].'">'; 
			},
			$str_text
		);
		$str_text = preg_replace('/'.FormatTypeObjects::TAGCODE_PARSE_TEXT_OBJECT_CLOSE.'/s', '</span>', $str_text);
		
		$str_text = preg_replace('/'.FormatTypeObjects::TAGCODE_PARSE_TEXT_ENTITY_OPEN.'/s', '', $str_text);
		$str_text = preg_replace('/'.FormatTypeObjects::TAGCODE_PARSE_TEXT_ENTITY_CLOSE.'/s', '', $str_text);
		
		$str_text_clear = strip_tags($str_text);
		
		$arr = [];
		$html = new HTMLDocument($str_text);
		$spans = $html->getElementsByTagName('span');
		
		foreach ($spans as $span) {		
			
			$str_ids = $span->getAttribute('data-ids');
			
			if (!$str_ids) {
				continue;
			}
				
			$arr_ids = explode('|', $str_ids);
				
			foreach ($arr_ids as $arr_type_object_id) {
					
				$arr_id = explode('_', $arr_type_object_id);
				$arr[] = ['type_id' => (int)$arr_id[0], 'object_id' => (int)$arr_id[1], 'group_id' => (int)$arr_id[2], 'text' => $span->nodeValue];
			}
		}

		/*preg_replace_callback(
			'/\[object=([0-9-_\|]+)\](.*?)\[\/object\]/si',
			function ($matches) use (&$arr) {
				$arr_ids = explode("|", $matches[1]);
				foreach ($arr_ids as $arr_type_object_id) {
					$arr_id = explode("_", $arr_type_object_id);
					$arr[] = array('type_id' => (int)$arr_id[0], 'object_id' => (int)$arr_id[1], 'group_id' => (int)$arr_id[2], 'text' => $matches[2]);
				}
			},
			$str_text
		);*/

		return ['text' => $str_text_clear, 'tags' => $arr];
	}
		
	public static function appendToValue($type, $value, $value_append) {
		
		$format = false;
		
		switch ($type) {
			case 'text':
			case 'text_layout':
			case 'text_tags':
				if ($value) {
					$format = $value.($value_append ? EOL_1100CC.$value_append : '');
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
	
	public static function compareSQLValue($type, $value_1, $value_2) { // Compare raw database values
		
		switch ($type) {
			case 'module':
			case 'external_module':
			case 'reconcile_module':
			case 'reversal_module':
			
				if ($value_1 == $value_2) {
					return true;
				}
				
				$value_1 = arrKsortRecursive(json_decode($value_1, true));
				$value_2 = arrKsortRecursive(json_decode($value_2, true));
				
				return ($value_1 === $value_2);
			default:
			
				return ($value_1 == $value_2); // Default loose comparison
		}
	}
}
