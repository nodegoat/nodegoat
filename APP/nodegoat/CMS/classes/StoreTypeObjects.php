<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreTypeObjects {
	
	const VERSION_OFFSET_ALTERNATE_ACTIVE = -10;
	const VERSION_OFFSET_ALTERNATE = -20;
	const DATE_INT_MIN = -9000000000000000000;
	const DATE_INT_MAX = 9000000000000000000;
	const DATE_INT_CALC = 200000000000000000; // DATE_INT_MAX + DATE_INT_CALC is just within 64-bit margin.
	const DATE_INT_SEQUENCE_NULL = 5000;
	const VALUE_NUMERIC_DECIMALS = 10;
	const VALUE_NUMERIC_MULTIPLIER = 10 ** 10;
	const GEOMETRY_SRID = 4326; // 4326 for WGS84
	const GEOMETRY_COORDINATE_DECIMALS = 20;
	const TEXT_TAG_OBJECT = 1;
	const TEXT_TAG_ENTITY = 2;
	const TAGCODE_PARSE_TEXT_OBJECT_OPEN = '\[object=([0-9-_\|]+)\]';
	const TAGCODE_PARSE_TEXT_OBJECT_CLOSE = '\[\/object(?:=([0-9-_\|]+))?\]';
	const TAGCODE_PARSE_TEXT_ENTITY_OPEN = '\[entity(?:=([^\]]*))?\]';
	const TAGCODE_PARSE_TEXT_ENTITY_CLOSE = '\[\/entity\]';
	const TAGCODE_TEST_SERIAL_VARCHAR = '\[\[#(?:=[\d]*)?\]\]';
	const TAGCODE_PARSE_SERIAL_VARCHAR = '\[\[#(?:=([\d]*))?\]\]';
	const PIXEL_TO_CENTIMETER = 0.026458333;
	
	const FORMAT_DATE_YMD = 1;
	/*const FORMAT_X = 2;
	const FORMAT_XX = 4;*/
	
	const FORMAT_MULTI_SEPERATOR = ' '; // U+2002 - EN SPACE
	const FORMAT_MULTI_SEPERATOR_LIST = ', ';
	
	const MODE_UPDATE = 1;
	const MODE_OVERWRITE = 2;
	
	const MODULE_STATE_NONE = 0;
	const MODULE_STATE_DISABLED = 1;
	/*const MODULE_MODE_X = 2;
	const MODULE_MODE_XX = 4;*/
	
	protected $type_id = false;
	protected $user_id = false;
	protected $system_object_id = null;
	
	
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
	public static $num_media_preview_height = 50;
	
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
		$this->table_name_object_updates = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_object_updates_'.$this->identifier);
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
		-20+: Relates to alternate records of record versions (VERSION_OFFSET_ALTERNATE)
		-19: Not used
		-18: Relates to alternate record of record with no regard to versioning (VERSION_OFFSET_ALTERNATE)
		-10 / -17: Relates to an alternate record based on active record (VERSION_OFFSET_ALTERNATE_ACTIVE)
		-2: Relates to records with no regard to versioning
		-1: Translates to version 1 in version log, indicates a new record
		0: Object is set to be deleted
		1+: Relates to an existing record, record version is changed 
		
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
			
			$version_select = GenerateTypeObjects::generateVersioning('active', 'record', 'nodegoat_to_def');
			$version_select_to = GenerateTypeObjects::generateVersioning('active', 'object', 'nodegoat_to');

			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
				$arr_object_definition = ($arr_object_definitions[$object_description_id] ?? null);
	
				$is_ref_type_id = (bool)$arr_object_description['object_description_ref_type_id'];
				
				if (!$is_ref_type_id && $arr_object_description['object_description_is_unique']) {
					
					if (empty($arr_object_definition['object_definition_value'])) {
						continue;
					}
					
					$value_type = $arr_object_description['object_description_value_type'];
					$value_find = self::formatToSQLValue($value_type, $arr_object_definition['object_definition_value']);
					$sql_match = false;
					
					if ($arr_object_description['object_description_has_multi']) {
						
						$arr_str = [];
						
						foreach ((array)$value_find as $value) {
							$arr_str[] = self::formatToSQLValueFilter($value_type, ['equality' => '=', 'value' => $value], StoreType::getValueTypeValue($value_type));
						}
						
						if ($arr_str) {
							$sql_match = "(".implode(" OR ", $arr_str).")";
						}
					} else {
						$sql_match = self::formatToSQLValueFilter($value_type, ['equality' => '=', 'value' => $value_find], StoreType::getValueTypeValue($value_type));
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
						
						$version_log = ($this->versioning && $this->insert ? -1 : $version);
						
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
				
				if ($action_object_sub != 'delete') {
				
					if (static::GEOMETRY_SRID && $arr_object_sub['object_sub']['object_sub_location_geometry_version']) {
						
						$str_location_geometry = $arr_object_sub['object_sub']['object_sub_location_geometry'];
						
						if (static::geometryToSRID($str_location_geometry) != static::GEOMETRY_SRID) {
							
							$str_location_geometry = static::translateToGeometry($str_location_geometry, static::GEOMETRY_SRID);
							
							$this->addTypeObjectSubGeometryVersion($object_sub_id, (static::VERSION_OFFSET_ALTERNATE - $arr_object_sub['object_sub']['object_sub_location_geometry_version']), $str_location_geometry, static::GEOMETRY_SRID);
						}
					}
				} else {
					
				}
				
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
		
		$has_date = (isset($arr_object_sub['object_sub']['object_sub_date_type']) || isset($arr_object_sub['object_sub']['object_sub_date_chronology']) || isset($arr_object_sub['object_sub']['object_sub_date_object_sub_id']) || isset($arr_object_sub['object_sub']['object_sub_date_start']) || isset($arr_object_sub['object_sub']['object_sub_date_end']));
		$has_location = (isset($arr_object_sub['object_sub']['object_sub_location_type']) || isset($arr_object_sub['object_sub']['object_sub_location_ref_object_id']));
		
		if (($arr_object_sub['object_sub']['object_sub_self'] || $has_date || $has_location) && $arr_object_sub['object_sub']['object_sub_version'] != 'deleted') { // Subobject wants to be inserted/updated
			
			$arr_cur_object_sub = ($object_sub_id ? $this->arr_object_set['object_subs'][$object_sub_id] : false);
			$date_is_locked = ($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_sub_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_description_id']);
			$location_is_locked = ($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id'] || $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_id']);
			
			$object_sub_date_chronology = '';
			
			if ($date_is_locked || !$has_date) {
				
				if ($object_sub_id) {
					$object_sub_date_chronology = self::formatToSQLValue('chronology', $arr_cur_object_sub['object_sub']['object_sub_date_chronology']);
				}
			} else {
				
				$arr_chronology = [];
				
				if ($object_sub_id && (is_array($arr_object_sub['object_sub']['object_sub_date_start']) || is_array($arr_object_sub['object_sub']['object_sub_date_end']))) { // Update existing Chronology
					
					$arr_chronology = self::formatToChronology($arr_cur_object_sub['object_sub']['object_sub_date_chronology']);
					
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
							$arr_chronology = ($arr_chronology && !is_array($arr_chronology) ? self::formatToChronology($arr_chronology) : $arr_chronology);

						} else if (isset($arr_object_sub['object_sub']['object_sub_date_start']) && is_array($arr_object_sub['object_sub']['object_sub_date_start'])) {
							
							$arr_chronology_use = $arr_object_sub['object_sub']['object_sub_date_start']['chronology'];
							$arr_chronology_use = ($arr_chronology_use && !is_array($arr_chronology_use) ? self::formatToChronology($arr_chronology_use) : $arr_chronology_use);
							
							$arr_chronology['start'] = $arr_chronology_use['start'];
						}
						
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
							
							if (isset($arr_object_sub['object_sub']['object_sub_date_end']) && is_array($arr_object_sub['object_sub']['object_sub_date_end'])) {
							
								$arr_chronology_use = $arr_object_sub['object_sub']['object_sub_date_end']['chronology'];
								$arr_chronology_use = ($arr_chronology_use && !is_array($arr_chronology_use) ? self::formatToChronology($arr_chronology_use) : $arr_chronology_use);
							
								$arr_chronology['end'] = $arr_chronology_use['end'];
							}
						}
						
						break;
				}
				
				if ($arr_chronology) {
					$arr_chronology['type'] = ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period'] ? 'period' : 'point');
				}

				$object_sub_date_chronology = self::formatToSQLValue('chronology', $arr_chronology);
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
	
	public function parseObjectDefinition($object_description_id, $type, $value) {
		
		$value = static::formatToSQLValue($type, $value);
	
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
		
		$value = static::formatToSQLValue($type, $value);
			
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
		
		$this->stmt_object_sub_details_id->bindParameters(['object_sub_id' => $object_sub_id]);
		
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
			
			$this->arr_sql_insert['object_dating'][] = "(".$this->object_id.", NOW(), NOW())"; // Ensure object has a dating record, even when making an object exist only (e.g. through merge)
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
		
		$this->arr_sql_insert['object_version_user'][] = "(".$this->object_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
	}
	
	protected function addTypeObjectDescriptionVersion($object_description_id, $version, $value) {
		
		$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
		$is_alternate = ($version <= static::VERSION_OFFSET_ALTERNATE_ACTIVE);
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
	
	protected function addTypeObjectDescriptionVersionUser($object_description_id, $version, $user_id = false, $date = false, $system_object_id = false) {
		
		$this->arr_actions['object_definition'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_definition_version_user'][] = "(".$object_description_id.", ".$this->object_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
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
			
			$num_srid = static::GEOMETRY_SRID;
			
			if (static::GEOMETRY_SRID) {
				
				$num_srid = static::geometryToSRID($value); // Get SRID from JSON
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
		
		$this->arr_sql_insert['object_sub_version_user'][] = "(".$object_sub_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
	}
	
	protected function addTypeObjectSubDescriptionVersion($object_sub_id, $object_sub_details_id, $object_sub_description_id, $version, $value) {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$is_alternate = ($version <= static::VERSION_OFFSET_ALTERNATE_ACTIVE);
		
		$value_type = $arr_object_sub_description['object_sub_description_value_type'];
		$this->arr_sql_insert['object_sub_definition_version'][$value_type][] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$version.", ".($arr_object_sub_description['object_sub_description_ref_type_id'] ? (int)($value == 'last' ? self::$last_object_id : $value) : "'".DBFunctions::strEscape($value)."'").", ".($this->versioning || $is_alternate ? '0' : '1').")";
	}
	
	protected function addTypeObjectSubDescriptionVersionUser($object_sub_id, $object_sub_description_id, $version, $user_id = false, $date = false, $system_object_id = false) {
		
		$this->arr_actions['object_sub_definition'] = true;
		
		if (!$this->versioning) {
			return;
		}
		
		$this->arr_sql_insert['object_sub_definition_version_user'][] = "(".$object_sub_description_id.", ".$object_sub_id.", ".$version.", ".($user_id !== false ? (int)$user_id : $this->user_id).", ".($date ? "'".$date."'" : "NOW()").", ".($system_object_id !== false ? ((int)$system_object_id ?: 'NULL') : ($this->system_object_id ?: 'NULL')).")";
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
						(object_description_id, object_id, version, user_id, date, system_object_id)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('object_description_id, object_id, version, user_id, date', ['object_id'])."
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
						(object_sub_description_id, object_sub_id, version, user_id, date, system_object_id)
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
			static::touchTypeObjects($this->type_id, $this->table_name_object_updates);
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
			static::touchTypeObjects($this->type_id, $this->table_name_object_updates);
		}
		
		DB::commitTransaction('store_type_objects');
	}

	// Delete
	
	public function delTypeObject($accept) {
						
		if (!$this->versioning || ($this->arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL && $accept)) {
			
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
			
			static::touchTypeObjects($this->type_id, $this->table_name_object_updates);
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
				
				static::touchTypeObjects($this->type_id, $this->table_name_object_updates);
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
		
		static::touchTypeObjects($this->type_id, $table_name_objects_deleted);
		
		DB::query("DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE ".$table_name_objects_deleted);
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
	
	// Module/Process Mode
	
	public function getModuleState() {
		
		$type_id = ($this->arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL ? StoreType::getSystemTypeID('reversal') : $this->type_id);
		$object_description_id = StoreType::getSystemTypeObjectDescriptionID($type_id, 'module');
				
		$res = DB::query("SELECT nodegoat_to_def_modules.state
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULES')." AS nodegoat_to_def_modules
			WHERE nodegoat_to_def_modules.object_id = ".$this->object_id."
				AND nodegoat_to_def_modules.object_description_id = ".(int)$object_description_id."
				AND ".GenerateTypeObjects::generateVersioning('active', 'record', 'nodegoat_to_def_modules')."
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
				"JOIN ".$this->table_name_object_updates." nodegoat_to_updates ON (nodegoat_to_updates.id = nodegoat_to_def_modules.object_id)",
				['state' => $sql_state]
			)."
				AND nodegoat_to_def_modules.object_description_id = ".(int)$object_description_id."
				AND ".GenerateTypeObjects::generateVersioning('active', 'record', 'nodegoat_to_def_modules')."
		");
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
    
	public function getTypeObjectDescriptionVersions($object_description_id, $do_full = false) {
		
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
			
			if ($do_full && $arr) {
				
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
	
	public function getTypeObjectSubVersions($object_sub_id, $do_full = false) {
		
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
			
			if ($do_full && $arr) {
				
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
						
		$res = DB::query("SELECT nodegoat_to_ver.version, nodegoat_to_ver.date, nodegoat_to_ver.system_object_id, u.id, u.name
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
						
		$res = DB::query("SELECT nodegoat_to_def_ver.version, nodegoat_to_def_ver.date, nodegoat_to_def_ver.system_object_id, u.id, u.name
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
						
		$res = DB::query("SELECT nodegoat_tos_ver.version, nodegoat_tos_ver.date, nodegoat_tos_ver.system_object_id, u.id, u.name
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
						
		$res = DB::query("SELECT nodegoat_tos_def_ver.version, nodegoat_tos_def_ver.date, nodegoat_tos_def_ver.system_object_id, u.id, u.name
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
				
		StoreTypeObjectsProcessing::cleanupTypesObjects($this->table_name_object_updates);
	}
		
	protected function addObjectUpdate() {
				
		if (!$this->has_object_updates) {
			
			$this->generateObjectUpdatesTable();
			$this->has_object_updates = true;
		}
				
		$this->stmt_object_updates->bindParameters(['object_id' => $this->object_id]);
		$res = $this->stmt_object_updates->execute();
				
		if (!$res->getAffectedRowCount()) {
			
			$res = DB::query("SELECT TRUE FROM ".$this->table_name_object_updates."	WHERE id = ".$this->object_id); // Check if ID was already inserted (duplicate/no rows affected).
			
			if (!$res->getRowCount()) {
				error(getLabel('msg_object_missing'));
			}
		}
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
					id, NOW(), NOW()
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
		
		$sql_users = "IN (".implode(',', arrParseRecursive($arr_users, 'int')).")";
				
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
			'/'.static::TAGCODE_PARSE_TEXT_OBJECT_OPEN.'/s',
			function ($matches) {
				return '<span class="tag" data-ids="'.$matches[1].'">'; 
			},
			$str_text
		);
		$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_OBJECT_CLOSE.'/s', '</span>', $str_text);
		
		$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_ENTITY_OPEN.'/s', '', $str_text);
		$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_ENTITY_CLOSE.'/s', '', $str_text);
		
		$str_text_clear = strip_tags($str_text);
		
		$arr = [];
		$html = FormatHTML::openHTMLDocument($str_text);
		$spans = $html->getElementsByTagName('span');
		
		foreach ($spans as $span) {		
			
			$str_ids = $span->getAttribute('data-ids');
			
			if ($str_ids) {
				
				$arr_ids = explode('|', $str_ids);
				
				foreach ($arr_ids as $arr_type_object_id) {
					
					$arr_id = explode('_', $arr_type_object_id);
					$arr[] = ['type_id' => (int)$arr_id[0], 'object_id' => (int)$arr_id[1], 'group_id' => (int)$arr_id[2], 'text' => $span->nodeValue];
				}
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
	
	public static function clearObjectDefinitionText($str_text, $target_tag = false) {
		
		if (!$target_tag || $target_tag == static::TEXT_TAG_OBJECT) {
			
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_OBJECT_OPEN.'/s', '', $str_text);
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_OBJECT_CLOSE.'/s', '', $str_text);
		}
		
		if (!$target_tag || $target_tag == static::TEXT_TAG_ENTITY) {
			
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_ENTITY_OPEN.'/s', '', $str_text);
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_ENTITY_CLOSE.'/s', '', $str_text);
		}
		
		return $str_text;
	}
	
	public static function updateObjectDefinitionTextTagsObject($str_text, $arr_types_objects, $any_type = false) {
		
		$num_length_text = 0;
		$num_length_tag_open = 0;
		$num_length_tag_close = 0;
		
		$func_parse = function($num_pos_close, $num_pos_open, $str_tag_identifiers, $has_close_tag_identifiers = false) use (&$str_text, &$num_length_text, &$num_length_tag_open, &$num_length_tag_close, $arr_types_objects, $any_type) {
			
			$num_length_tag_identifiers = strlen($str_tag_identifiers);
			$arr_tag_identifiers = explode('|', $str_tag_identifiers);
			$has_change = false;
			
			foreach ($arr_tag_identifiers as $key => $str_tag_identifier) {
				
				$arr_tag = explode('_', $str_tag_identifier);
				
				$arr_type_objects = ($arr_types_objects[$arr_tag[0]] ?? null);
				
				if ($arr_type_objects === null) {
					
					if (!$any_type) { // Keep/ignore when type is not included
						continue;
					} else {
						unset($arr_tag_identifiers[$key]);
						$has_change = true;
					}
				} else if ($arr_type_objects === false) {
					
					unset($arr_tag_identifiers[$key]);
					$has_change = true;
				} else {
					
					$is_active = ($arr_type_objects[$arr_tag[1]] ?? null);
					
					if ($is_active === false) {
					
						unset($arr_tag_identifiers[$key]);
						$has_change = true;
					}
				}
			}
			
			if ($has_change) {
				
				if (!$arr_tag_identifiers) { // Remove whole tag
					
					$num_length = ($num_length_tag_open + $num_length_tag_identifiers + 1);
					$str_text = substr_replace($str_text, '', $num_pos_open, $num_length);
					
					$num_pos_close -= $num_length;
					$num_length_text -= $num_length;
					
					$num_length = ($num_length_tag_close + ($has_close_tag_identifiers ? $num_length_tag_identifiers : 0) + 1);
					$str_text = substr_replace($str_text, '', $num_pos_close, $num_length);

					$num_length_text -= $num_length;
				} else { // Update tag identifiers
					
					$str_tag_identifiers = implode('|', $arr_tag_identifiers);
					$num_length = strlen($str_tag_identifiers);
					
					$num_length_tag_identifiers_updated = ($num_length_tag_identifiers - $num_length);
					
					$str_text = substr_replace($str_text, $str_tag_identifiers, ($num_pos_open + $num_length_tag_open), $num_length_tag_identifiers);

					$num_pos_close -= $num_length_tag_identifiers_updated;
					$num_length_text -= $num_length_tag_identifiers_updated;
					
					if ($has_close_tag_identifiers) {
						
						$str_text = substr_replace($str_text, $str_tag_identifiers, ($num_pos_close + $num_length_tag_close), $num_length_tag_identifiers);

						$num_length_text -= $num_length_tag_identifiers_updated;
					}
															
					$num_pos_close += ($num_length_tag_close + ($has_close_tag_identifiers ? $num_length_tag_identifiers_updated : 0) + 1); // Set position after closing tag
				}				
			} else {
				
				$num_pos_close += ($num_length_tag_close + ($has_close_tag_identifiers ? $num_length_tag_identifiers : 0) + 1); // Set position after closing tag
			}
			
			return $num_pos_close;
		};
		
		$num_length_tag_open = 8;
		$num_length_tag_close = 9;
		
		$num_length_text = strlen($str_text);	
		$num_pos_close = strpos($str_text, '[/object=');

		while ($num_pos_close !== false) {
			
			$num_pos_close_end = strpos($str_text, ']', ($num_pos_close + $num_length_tag_close));
			
			$str_tag_identifiers = substr($str_text, ($num_pos_close + $num_length_tag_close), ($num_pos_close_end - ($num_pos_close + $num_length_tag_close)));

			$num_pos_open = strrpos($str_text, '[object='.$str_tag_identifiers.']', ($num_pos_close - $num_length_text));

			$num_pos_close = $func_parse($num_pos_close, $num_pos_open, $str_tag_identifiers, true);
			
			$num_pos_close = strpos($str_text, '[/object=', $num_pos_close);
		}
		
		$num_length_tag_close = 8;
		
		$num_length_text = strlen($str_text);	
		$num_pos_close = strpos($str_text, '[/object]');

		while ($num_pos_close !== false) {

			$num_pos_open = strrpos($str_text, '[object=', ($num_pos_close - $num_length_text));
			$num_pos_open_end = strpos($str_text, ']', ($num_pos_open + $num_length_tag_open));
			
			$str_tag_identifiers = substr($str_text, ($num_pos_open + $num_length_tag_open), ($num_pos_open_end - ($num_pos_open + $num_length_tag_open)));

			$num_pos_close = $func_parse($num_pos_close, $num_pos_open, $str_tag_identifiers, false);
			
			$num_pos_close = strpos($str_text, '[/object]', $num_pos_close);
		}
		
		return $str_text;
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
	
	public static function formatToCleanValue($type, $value, $arr_type_options = [], $mode_format = null) { // From raw database to display
		
		$format = false;
			
		switch ($type) {
			case 'numeric':
				$format = ($value ? self::int2Numeric($value) : '');
				break;
			case 'date':
			
				$do_mode_ymd = ($mode_format !== null && ($mode_format & StoreTypeObjects::FORMAT_DATE_YMD));
				
				$format = ($value ? self::int2Date($value, $do_mode_ymd) : '');
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
			case 'module':
				
				$module = EnucleateValueTypeModule::init($arr_type_options['type']);
				$module->setValue(json_decode($value, true));
				
				$format = $module->enucleate(EnucleateValueTypeModule::VIEW_TEXT);

				break;
			case 'external_module':
			case 'reversal_module':
				$format = $value;
				break;
			default:
				$format = $value;
		}
		
		return $format;
	}
	
	public static function formatToHTMLPreviewValue($type, $value, $arr_type_options = [], $extra = false) { // From formatted database to preview display
		
		$format = false;
		
		switch ($type) {
			case 'int':
			case 'numeric':
			case 'float':
			case 'serial_varchar':
			case '':
						
				if (is_array($value)) {
					$format = implode(($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR), $value);
				} else {
					$format = $value;
				}
				
				$format = strEscapeHTML($format);
				
				break;
			case 'media':
			case 'media_external':
			
				if ($value) {
					
					$show_url = ($arr_type_options['display'] && $arr_type_options['display'] == 'url');
					
					if (is_array($value)) {
						
						$arr_html = [];
							
						foreach ($value as $media) {
							
							$media = new EnucleateValueTypeMedia($media);
							$media->setSize(false, static::$num_media_preview_height);
							
							if ($show_url) {
								$str_url = $media->enucleate(true);
								$str_url = strEscapeHTML($str_url);
								$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
							} else {
								$arr_html[] = $media->enucleate();
							}
						}
						
						$format = implode(($show_url ? ($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR_LIST) : ''), $arr_html);
					} else {
								
						$media = new EnucleateValueTypeMedia($value, true);
						$media->setSize(false, static::$num_media_preview_height);
						
						if ($show_url) {
							$str_url = $media->enucleate(true);
							$str_url = strEscapeHTML($str_url);
							$format = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
						} else {
							$format = $media->enucleate();
						}
					}
				}
				break;
			case 'module':
				
				$module = EnucleateValueTypeModule::init($arr_type_options['type']);
				$module->setConfiguration(($extra ?: []) + ['size' => ['height' => static::$num_media_preview_height]]);
				$module->setValue(json_decode($value, true));
				
				$format = $module->enucleate();

				break;
			case 'external':
				
				if (is_array($value)) {
						
					$arr_html = [];
					
					foreach ($value as $ref_value) {
							
						$arr_html[] = $ref_value;
					}
					
					$format = implode(($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR_LIST), $arr_html);
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
			
				$format = self::formatToHTMLValue($type, $value, $arr_type_options, $extra);
		}
		
		return $format;
	}
	
	public static function formatToHTMLValue($type, $value, $arr_type_options = [], $extra = false) { // From formatted database to display
		
		$format = false;
		
		switch ($type) {
			case 'int':
			case 'numeric':
			case 'float':
			case 'serial_varchar':
			case '':
								
				if (is_array($value)) {
					$format = '<span>'.implode('</span><span class="separator">'.($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR).'</span><span>', arrParseRecursive($value, 'strEscapeHTML')).'</span>';
				} else {
					$format = strEscapeHTML($value);
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
				
				$str_command_hover = false;
				$arr_type_references = [];
			
				if ($extra) {

					if (isset($extra['references'])) {
						$arr_type_references = $extra['references'];
						$str_command_hover = $extra['command_hover'];
					} else {
						$arr_type_references = $extra;
					}
				}
				
				if ($arr_type_references && ($arr_type_options['marginalia'] || keyIsUncontested('list', $arr_type_options))) { // If there are tags
					
					$arr_types_all = StoreType::getTypes(); // Source can be any type
					$arr_html_tabs = [];
					
					$arr_collect_type_object_names = [];
					
					foreach ($arr_type_references as $ref_type_id => $arr_ref_objects) {
					
						foreach($arr_ref_objects as $cur_object_id => $arr_reference) {
							
							$arr_reference = (is_array($arr_reference) ? ($arr_reference['object_definition_ref_object_name'] ?: ($arr_reference['object_sub_definition_ref_object_name'] ?: $arr_reference)) : $arr_reference);
							
							$html_object_name = '';
							$html_object_input = false;
							
							if (is_array($arr_reference)) {
								$html_object_name = $arr_reference['name'];
								$html_object_input = $arr_reference['input'];
							} else {
								$html_object_name = $arr_reference;
							}
							
							if ($arr_collect_type_object_names[$ref_type_id][$cur_object_id.'_'.$html_object_name]) { // Check for duplicate ID-name/value combination
								continue;
							}
							
							$html = '<p class="'.$ref_type_id.'_'.$cur_object_id.'">'.($html_object_input ? $html_object_input : '').data_view::createTypeObjectLink($ref_type_id, $cur_object_id, $html_object_name).'</p>';
							$arr_collect_type_object_names[$ref_type_id][$cur_object_id.'_'.$html_object_name] = $html;
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
						
						if ($arr_type_options['marginalia']) {
							
							$html_page = '<div class="page">
								<div class="body">'.$value.'</div>
								<div class="marginalia"></div>
							</div>';
						} else {
							
							$html_page = '<div class="body">'.$value.'</div>';
						}
						
						$html_value = '<div>'
							.$html_page
							.$html_word_count
						.'</div>
						<div class="text-references">
							<div class="tabs">
								<ul>
									'.implode('', $arr_html_tabs['links']).'
								</ul>
								'.implode('', $arr_html_tabs['content']).'
							</div>
						</div>';
						
						if (keyIsUncontested('list', $arr_type_options)) {
							
							$format = '<div class="tabs '.$type.'"'.($str_command_hover ? ' data-command_hover="'.$str_command_hover.'"' : '').'>
								<ul>
									<li><a href="#">'.getLabel('lbl_text').'</a></li>
									<li><a href="#">'.getLabel('lbl_referencing').'</a></li>

								</ul>
								'.$html_value.'
							</div>';
						} else {
							
							$format = '<div class="'.$type.'"'.($str_command_hover ? ' data-command_hover="'.$str_command_hover.'"' : '').'>
								'.$html_value.'
							</div>';
						}
						
						break;
					}
				}
				
				$format = '<div class="body '.$type.'"'.($str_command_hover ? ' data-command_hover="'.$str_command_hover.'"' : '').'>'
					.$value
				.'</div>'.$html_word_count;
				
				break;
			case 'media':
			case 'media_external':
			
				if ($value) {
					
					$show_url = ($arr_type_options['display'] && $arr_type_options['display'] == 'url');
					
					if (is_array($value)) {
						
						$arr_html = [];
							
						foreach ($value as $media) {
							
							$media = new EnucleateValueTypeMedia($media);
							
							if ($show_url) {
								$str_url = $media->enucleate(true);
								$str_url = strEscapeHTML($str_url);
								$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
							} else {
								$arr_html[] = $media->enucleate();
							}
						}
						
						if (!$show_url && count($arr_html) > 1) {
							
							$format = '<div class="album">'.implode('', $arr_html).'</div>';
						} else {
							$format = implode('<span class="separator">'.($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
						}
					} else {
								
						$media = new EnucleateValueTypeMedia($value);
						
						if ($show_url) {
							$str_url = $media->enucleate(true);
							$str_url = strEscapeHTML($str_url);
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
													
						$reference = new ResourceExternal(StoreResourceExternal::getResources($arr_type_options['id']), $ref_value);
						$arr_html[] = '<span>'.$reference->getURL().'</span>';
					}
					
					$format = implode('<span class="separator">'.($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
				} else {
					
					$reference = new ResourceExternal(StoreResourceExternal::getResources($arr_type_options['id']), $value);
					$format = $reference->getURL();
				}
				
				break;
			case 'module':
				
				$module = EnucleateValueTypeModule::init($arr_type_options['type']);
				$module->setConfiguration($extra);
				$module->setValue(json_decode($value, true));
				
				$format = $module->enucleate();

				break;
			case 'external_module':
			case 'reversal_module':
			
				$format = strEscapeHTML($value);
				
				break;
			case 'date_cycle':
			
				$format = getLabel('unit_month').' '.(int)$value[0].'<br />'.getLabel('unit_day').' '.(int)$value[1];
				
				break;
			case 'date_compute':
			
				$format = getLabel('unit_year').' +'.(int)$value[0].'<br />'.getLabel('unit_month').' +'.(int)$value[1].'<br />'.getLabel('unit_day').' +'.(int)$value[2];
			
				break;
			default:
				$format = strEscapeHTML($value);
		}
		
		return $format;
	}
	
	public static function formatToHTMLPlainValue($type, $value, $arr_type_options = [], $extra = false) { // From formatted database to display
		
		$format = false;
		
		switch ($type) {
			case 'int':
			case 'numeric':
			case 'float':
			case 'serial_varchar':
			case '':
			
				if (is_array($value)) {
					$format = '<span>'.implode('</span><span class="separator">'.($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR).'</span><span>', $value).'</span>';
				} else {
					$format = $value;
				}
				
				break;
			case 'text':
			case 'text_layout':
			case 'text_tags':
										
				$value = parseBody($value);
				$format = '<section>'.$value.'</section>';

				break;
			case 'media':
			case 'media_external':
			
				$show_url = ($arr_type_options['display'] && $arr_type_options['display'] == 'url');
				
				$value = (array)$value;
				$arr_html = [];
				
				foreach ($value as $media) {
					
					$media = new EnucleateValueTypeMedia($media);

					if ($show_url) {
						
						$media->setPath(rtrim(URL_BASE_HOME, '/'));
						$str_url = $media->enucleate(true);
						$str_url = strEscapeHTML($str_url);
						
						$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
					} else {
						
						$media->setSize('15cm', '10cm', false);

						if (!$media->isExternal()) {
							
							$arr_size = $media->getSize();
							
							if ($arr_size) {
									
								$width = $arr_size['width'] * static::PIXEL_TO_CENTIMETER;
								$width = ($width > 15 ? 15 : $width);
								$height = ($width * ($arr_size['height'] / $arr_size['width']));
								$media->setSize($width.'cm', $height.'cm', false);
							}
							
							$str_url = $media->enucleate(true);
						
							$media->setPath('Pictures/', false);
							$str_url_new = $media->enucleate(true);
							
							if ($extra && is_array($extra['arr_path_media'])) {
								$extra['arr_path_media'][$str_url_new] = rtrim(DIR_ROOT_STORAGE.DIR_HOME, '/').$str_url;
							}
						}
						
						$arr_html[] = $media->enucleate();
					}
				}
				
				$format = implode('<span class="separator">'.($arr_type_options['separator'] ?: StoreTypeObjects::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);

				break;
			case 'module':
				
				$module = EnucleateValueTypeModule::init($arr_type_options['type']);
				$module->setConfiguration($extra);
				$module->setValue(json_decode($value, true));
				
				$format = $module->enucleate();

				break;
			default:
				$format = $value;
		}
		
		return $format;
	}
	
	public static function formatToInputValue($type, $value, $arr_type_options = []) { // From raw database to direct input (i.e in a field)
		
		$format = false;
			
		switch ($type) {
			case 'numeric':
				$format = ($value ? self::int2Numeric($value) : '');
				break;
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
	
	public static function formatToFormValue($type, $value, $name, $arr_type_options = [], $extra = false, $arr_options = []) { // From raw database to input field 
		
		$format = false;
		$html_menu = ($arr_options['menu'] ?? '');
	
		switch ($type) {
			case 'media_external':
			case 'serial_varchar':
			case '':
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
					
					foreach ($value as $str) {
						$format[] = ['value' => '<input type="text" class="default" name="'.$name.'[]" value="'.strEscapeHTML($str).'" />'];
					}
				} else {
					
					$format = '<input type="text" class="default" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
				}
				
				break;
			case 'int':
			case 'numeric':
			case 'float':
			
				if ($type == 'numeric' && $value) {
					$value = arrParseRecursive($value, __CLASS__.'::int2Numeric');
				}
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
				
					foreach ($value as $num) {
						$format[] = ['value' => '<input type="number" name="'.$name.'[]" value="'.strEscapeHTML($num).'" />'];
					}
				} else {
					
					$format = '<input type="number" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
				}
				
				break;
			case 'media':
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
					
					array_unshift($value, ''); // Empty run for sorter source
					
					foreach ($value as $key => $str_url) {
						
						$unique = uniqid('array_');
						
						$html_media = '';
						
						if ($str_url) {
							
							$media = new EnucleateValueTypeMedia($str_url, true);
							
							$html_media = '<div class="media-preview">'.$media->enucleate().'</div>';
						}
						
						$format[] = [
							'source' => ($key == 0 ? true : false),
							'value' => $html_media
								.'<label>'.getLabel('lbl_url').':</label>'
								.'<input type="text" name="'.$name.'['.$unique.'][url]" value="'.strEscapeHTML($str_url).'" />'
								.'<div class="hide-edit hide">'
									.'<label>'.getLabel('lbl_file').':</label>'
									.cms_general::createFileBrowser(false, $name.'['.$unique.'][file]')
								.'</div>'
								.'<input type="button" class="data neutral" value="'.getLabel('lbl_upload').'" />'
						];
					}
				} else {
					
					$html_media = '';
						
					if ($value) {
						
						$media = new EnucleateValueTypeMedia($value, true);
						
						$html_media = '<div class="media-preview">'.$media->enucleate().'</div>';
					}
					
					$format = $html_media
						.'<label>'.getLabel('lbl_url').':</label>'
						.'<input type="text" name="'.$name.'[url]" value="'.strEscapeHTML($value).'" />'
						.'<div class="hide-edit hide">'
							.'<label>'.getLabel('lbl_file').':</label>'
							.cms_general::createFileBrowser(false, $name.'[file]')
						.'</div>'
						.'<input type="button" class="data neutral" value="'.getLabel('lbl_upload').'" />';
				}
				
				break;
			case 'text':
				$format = '<textarea name="'.$name.'">'.strEscapeHTML($value).'</textarea>';
				break;
			case 'text_layout':
				$format = cms_general::editBody($value, $name, ['inline' => true, 'menu' => $html_menu]);
				$html_menu = '';
				break;
			case 'text_tags':
				$format = cms_general::editBody($value, $name, ['inline' => true, 'menu' => $html_menu, 'data' => ['tag_object' => true]]);
				$html_menu = '';
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
					
					$arr_resource = StoreResourceExternal::getResources($arr_type_options['id']);
					$is_static = ($arr_resource && $arr_resource['protocol'] == 'static' ? true : false);
				}
				
				if (!$is_static) {
					
					if ($is_multi) {

						$format = cms_general::createMultiSelect($name, 'y:data_filter:lookup_external-'.$arr_type_options['id'], ($value ? array_combine($value, $value) : []), 'y:data_filter:lookup_external_pick-'.$arr_type_options['id'], ['delay' => 2]);
					} else {
						
						$format = '<input type="hidden" id="y:data_filter:lookup_external_pick-'.$arr_type_options['id'].'" name="'.$name.'" value="'.$value.'" /><input type="search" id="y:data_filter:lookup_external-'.$arr_type_options['id'].'" class="autocomplete external" data-delay="3" value="'.strEscapeHTML($value).'" />';
					}
				} else {
					
					if ($is_multi) {
						
						$format = [];
						
						if (!$value) {
							$value[] = '';
						}
						
						foreach ($value as $key => $ref_value) {

							$format[] = ['value' => '<input type="text" class="default" name="'.$name.'[]" value="'.strEscapeHTML($ref_value).'" />'];
						}
					} else {
						
						$format = '<input type="text" class="default" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
					}
				}
				
				break;
			case 'module':
			case 'external_module':
			case 'reversal_module':
				// Handled by data entry module
			default:
				$format = '<input type="text" class="default" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
		}
		
		if (is_array($format)) {
			
			$format = '<fieldset class="input"><ul>
				<li>
					<label></label><div><menu class="sorter">'
						.'<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'.$html_menu			
					.'</menu></div>
				</li><li>
					<label></label>
					'.cms_general::createSorter($format, true, true).'
				</li>
			</ul></fieldset>';
		} else {
			
			$format .= $html_menu;
		}
		
		return $format;
	}
	
	public static function formatToFormValueFilter($type, $value, $name, $arr_type_options = []) { // No database involved
		
		$format = false;
	
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
			case 'numeric':
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
			case 'serial_varchar':
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
		
	public static function formatFromSQLValue($type, $value, $arr_type_options = [], $mode_format = null) {
		
		$format = false;
		
		switch ($type) {
			case 'boolean':
				$format = "CASE WHEN ".$value." = 1 THEN 'yes' WHEN ".$value." = 0 THEN 'no' ELSE '' END";
				break;
			case 'date':
				
				$do_mode_ymd = ($mode_format !== null && ($mode_format & StoreTypeObjects::FORMAT_DATE_YMD));
				
				$sql_string = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);

				$format = "CASE ".$value."
					WHEN ".static::DATE_INT_MAX." THEN '∞'
					WHEN ".static::DATE_INT_MIN." THEN '-∞'
					ELSE CONCAT(";
						
						$sql_year = "CASE WHEN LENGTH(".$sql_string.")-8 < 3 
							THEN SUBSTRING(CONCAT('00', ".$sql_string.") FROM -(8+3) FOR 3)
							ELSE SUBSTRING(".$sql_string." FROM 1 FOR LENGTH(".$sql_string.")-8)
						END";
						
						if ($do_mode_ymd) {
							
							$format .="
								".$sql_year.",
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2) = '00' THEN '' ELSE CONCAT('-', SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2)) END,
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2) = '00' THEN '' ELSE CONCAT('-', SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2)) END,
							";
						} else {
							
							$format .="
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2) = '00' THEN '' ELSE CONCAT(SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2), '-') END,
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2) = '00' THEN '' ELSE CONCAT(SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2), '-') END,
								".$sql_year.",
							";
						}
						$format .="
						CASE WHEN RIGHT(".$sql_string.", 4) = '".static::DATE_INT_SEQUENCE_NULL."' THEN '' ELSE CONCAT(' ', ".DBFunctions::castAs("(".DBFunctions::castAs("RIGHT(".$sql_string.", 4)", DBFunctions::CAST_TYPE_INTEGER)." - ".static::DATE_INT_SEQUENCE_NULL.")", DBFunctions::CAST_TYPE_STRING).") END
					)
				END";
				
				break;
			case 'chronology':
				$format = self::formatFromSQLChronology($value);
				break;
			case 'geometry':
			
				if (static::GEOMETRY_SRID && keyIsUncontested('srid', $arr_type_options)) {
					
					if (DB::ENGINE_IS_MYSQL) {
						
						//$sql_geojson_srid = "ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.", 2)"; // MariaDB does not support the option for adding the crs
						$sql_geojson_srid = 'INSERT(ST_AsGeoJSON('.$value.', '.static::GEOMETRY_COORDINATE_DECIMALS.'), 2, 0, CONCAT(\'"crs": {"type": "name", "properties": {"name": "EPSG:\', ST_SRID('.$value.'), \'"}}, \'))';
					} else {
						
						$sql_geojson_srid = "ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.", 2)";
					}

					$format = "CASE
						WHEN ST_SRID(".$value.") != ".static::GEOMETRY_SRID." THEN ".$sql_geojson_srid."
						ELSE ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.")
					END";
				} else {
					
					$format = "ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.")";
				}
				
				break;
			case 'int':
			case 'float':
				$format = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);
				break;
			case 'numeric':
				
				$value = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);
				$value = "LPAD(".$value.", GREATEST(LENGTH(".$value."), ".(static::VALUE_NUMERIC_DECIMALS + 1)."), '0')";
				
				if (DB::ENGINE_IS_MYSQL) {
					$format = "INSERT(".$value.", LENGTH(".$value.")+1-".static::VALUE_NUMERIC_DECIMALS.", 0, '.')";
					$format = "TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM ".$format."))";
				} else {
					$format = "OVERLAY(".$value." PLACING '.' FROM LENGTH(".$value.")+1-".static::VALUE_NUMERIC_DECIMALS." FOR 0)";
					$format = "RTRIM(RTRIM(".$format.", '0'), '.')";
				}

				break;
			default:
				$format = $value;
		}

		return $format;
	}
	
	public static function formatToSQLValue($type, $value) {
		
		$do_unique = true;
		$format = false;
	
		switch ($type) {
			case 'boolean':
				$format = ($value !== '' && $value !== null ? ($value === 'no' || !$value ? 0 : 1) : '');
				break;
			case 'date':
				$format = self::date2Integer($value);
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
					
					if (isset($value['file']) || isset($value['url'])) {
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
					
					$str_filename = false;
						
					if ($arr_media['file']) {
						
						$arr_file = (array)$arr_media['file'];
						
						if ($arr_file['size']) {
														
							$str_filename = hash_file('md5', $arr_file['tmp_name']);
							$str_extension = FileStore::getExtension($arr_file['name']);
							if ($str_extension == FileStore::EXTENSION_UNKNOWN) {
								$str_extension = FileStore::getExtension($arr_file['tmp_name']);
							}
							$str_filename = $str_filename.'.'.$str_extension;
							
							if (!isPath(DIR_HOME_TYPE_OBJECT_MEDIA.$str_filename)) {
	
								$store_file = new FileStore($arr_file, ['directory' => DIR_HOME_TYPE_OBJECT_MEDIA, 'filename' => $str_filename], FileStore::getSizeLimit(FileStore::STORE_FILE));
							}
						}
					}
					
					if (!$str_filename && $arr_media['url']) {
						
						$file = new FileGet($arr_media['url']);
						
						if ($file->load()) {
							
							$str_path_file = $file->getPath();
							$str_filename = hash_file('md5', $str_path_file);
							$str_extension = FileStore::getExtension($file->getSource());
							if ($str_extension == FileStore::EXTENSION_UNKNOWN) {
								$str_extension = FileStore::getExtension($str_path_file);
							}
							
							$str_filename = $str_filename.'.'.$str_extension;
							
							if (!isPath(DIR_HOME_TYPE_OBJECT_MEDIA.$str_filename)) {
								
								$store_file = new FileStore($file, ['directory' => DIR_HOME_TYPE_OBJECT_MEDIA, 'filename' => $str_filename], FileStore::getSizeLimit(FileStore::STORE_FILE));
							} else {
								
								$file->abort();
							}
						} else {
							
							$str_filename = $arr_media['url'];
						}
					}
					
					$format[] = $str_filename;
				}
				
				if (!$is_multi) {
					$format = current($format);
				}
				break;
			case 'int':
				$format = ($value !== '' && $value !== null ? arrParseRecursive($value, 'int') : '');
				break;
			case 'numeric':
				$format = ($value !== '' && $value !== null ? arrParseRecursive($value, __CLASS__.'::num2Integer') : '');
				break;
			case 'float':
				$format = ($value !== '' && $value !== null ? arrParseRecursive($value, 'float') : '');
				break;
			case 'text':
			case 'text_layout':
			case 'text_tags':
				$format = arrParseRecursive($value, 'text');
				break;
			case 'media_external':
			case 'external':
			case 'serial_varchar':
			case '':
				$format = arrParseRecursive($value, 'string');
				break;
			case 'module':
			case 'external_module':
			case 'reversal_module':
				$do_unique = false;
				$format = (!empty($value) ? (is_array($value) ? value2JSON($value) : $value) : '');
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
		
		if (is_array($name)) {
			
			$name = 'JSON_VALUE('.$name['name'].', \''.$name['path'].'\' RETURNING '.static::formatToSQLType($type).')';
		}
		
		$value_plain = (is_array($value) ? $value['value'] : $value);
		$format = false;
	
		switch ($type) {
			case 'date':
				
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				$arr_num_value = static::dateInt2Compontents(static::date2Integer($value['value']));
				$arr_num_range = static::dateInt2Compontents(static::date2Integer($value['range']));
				$arr_sql_name = static::dateSQL2Compontents($name);

				switch ($value['equality']) {
					case '=':
					case '>':
					case '<':
						$format = $arr_sql_name['absolute'].' '.$value['equality'].' '.$arr_num_value['absolute'];
						break;
					case '≥':
						$format = $arr_sql_name['absolute'].' >= '.$arr_num_value['absolute'];
						break;
					case '≤':
						$format = $arr_sql_name['absolute'].' <= '.$arr_num_value['absolute'];
						break;
					case '><':
						$format = '('.$arr_sql_name['absolute'].' > '.$arr_num_value['absolute'].' AND '.$arr_sql_name['absolute'].' < '.$arr_num_range['absolute'].')';
						break;
					case '≥≤':
						$format = '('.$arr_sql_name['absolute'].' >= '.$arr_num_value['absolute'].' AND '.$arr_sql_name['absolute'].' <= '.$arr_num_range['absolute'].')';
						break;
				}
				break;
			case 'int':
			case 'numeric':
			case 'float':
			
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				if ($type == 'float') {
					$value['value'] = (float)$value['value'];
					$value['range'] = (float)$value['range'];
				} else if ($type == 'numeric') {
					$value['value'] = self::num2Integer($value['value']);
					$value['range'] = self::num2Integer($value['range']);
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
			case 'serial_varchar':
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
				
		if (is_array($name) && isset($name['path'])) {
						
			$name = 'JSON_VALUE('.$name['name'].', \''.$name['path'].'\' RETURNING '.static::formatToSQLType($type).')';
		}
		
		$format = false;
		
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
				case 'serial_varchar':
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
	
	public static function formatToSQLType($type) {
		
		switch ($type) {
			case 'int':
			case 'numeric':
				$cast = DBFunctions::CAST_TYPE_INTEGER;
				break;
			case 'float':
				$cast = DBFunctions::CAST_TYPE_DECIMAL;
				break;
			default:
				$cast = DBFunctions::CAST_TYPE_STRING;
				break;
		}
		
		return $cast;
	}
	
	// Numeric
	
	public static function num2Integer($num) {
		
		if ((int)$num >= static::VALUE_NUMERIC_MULTIPLIER && strpos((string)$num, '.') === false) { // Already converted
			return $num;
		}
		
		return is_numeric($num) ? bcmul($num, static::VALUE_NUMERIC_MULTIPLIER) : '';
	}
	
	public static function int2Numeric($int) {
		
		return rtrim(rtrim(bcdiv($int, static::VALUE_NUMERIC_MULTIPLIER, static::VALUE_NUMERIC_DECIMALS), '0'), '.');
	}
	
	public static function sqlInt2SQLNumeric($sql_value) {
		
		return '('.$sql_value.' * '.(1 / static::VALUE_NUMERIC_MULTIPLIER).')'; // Convert multiplier to decimal for float precision purposes
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
			$date_start = ($arr_source['date']['start'] ?? false);
			$date_end = ($arr_source['date']['end'] ?? false);
			$reference_start = ($arr_source['reference']['start'] ?? false);
			$reference_end = ($arr_source['reference']['end'] ?? false);
			
			$arr_source_span = $arr_source['span'];
			
			$arr_chronology = [
				'type' => ($is_period ? 'period' : 'point'),
				'span' => [
					'period_amount' => (int)$arr_source_span['period_amount'],
					'period_unit' => ($arr_source_span['period_amount'] ? $arr_source_span['period_unit'] : null),
					'cycle_object_id' => (int)$arr_source_span['cycle_object_id']
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
						$arr_source['date_object_sub_id'] = (int)$arr_source['date_object_sub_id'];
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
					'offset_amount' => (int)$arr_source['offset_amount'],
					'offset_unit' => ($arr_source['offset_amount'] ? $arr_source['offset_unit'] : null),
					'cycle_object_id' => (int)$arr_source['cycle_object_id'],
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
			$reference_start = false;
			$reference_end = false;

			$arr_chronology = [
				'type' => '',
				'span' => [
					'period_amount' => (int)$arr_part[0],
					'period_unit' => $arr_time_units[$arr_part[1]],
					'cycle_object_id' => (int)$arr_part[2]
				]
			];
			
			$func_parse_statement = function($str) use (&$arr_chronology, $arr_time_directions, $arr_time_units) {
				
				$arr_part = explode(',', $str);
				$int_identifier = (int)$arr_part[0];
				
				$arr = [
					'offset_amount' => (int)$arr_part[1],
					'offset_unit' => $arr_time_units[$arr_part[2]],
					'cycle_object_id' => (int)$arr_part[3],
					'cycle_direction' => $arr_time_directions[$arr_part[4]],
					'date_value' => ($arr_part[5] ? self::formatToInputValue('date', $arr_part[5]) : null),
					'date_object_sub_id' => ($arr_part[6] ? (int)$arr_part[6] : null),
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
			
			$is_period = ($arr_chronology['end']['end'] ?? null);
			$arr_chronology['type'] = ($is_period ? 'period' : 'point');
		}
		
		// Parse
		
		$is_single = (!$is_period && empty($arr_chronology['start']['end']) && empty($arr_chronology['start']['start']['offset_amount']) && (!empty($arr_chronology['start']['start']['date_value']) || !empty($arr_chronology['start']['start']['date_object_sub_id'])) && (empty($arr_chronology['start']['start']['date_direction']) || $arr_chronology['start']['start']['date_direction'] == '|>|')); // Chronology only involves a single date statement (and possibly a Cycle)
		
		if (!isset($arr_chronology['start']['start'])) {
			
			if ($date_start) {
				$arr_chronology['start']['start']['date_value'] = $date_start;
			} else if ($reference_start) {
				$arr_chronology['start']['start']['date_object_sub_id'] = $reference_start;
			} else {
				return [];
			}
		} else {
			
			if ($is_single) {
				$arr_chronology['start']['start']['date_direction'] = '';
			}
		}
		
		if ($is_period) {
				
			if (!isset($arr_chronology['end']['end'])) {
				
				if ($date_end) {
					$arr_chronology['end']['end']['date_value'] = $date_end;
				}  else if ($reference_end) {
					$arr_chronology['end']['end']['date_object_sub_id'] = $reference_end;
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
	
	public static function formatToChronologyPointOnly($value) {
		
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
	
	public static function formatToChronologyReferenceOnly($value) {
		
		if (!is_array($value)) {
			$arr_chronology = self::formatToChronology($value);
		} else {
			$arr_chronology = $value;
		}
		
		$arr_chronology = StoreTypeObjects::formatToChronology([
			'type' => $arr_chronology['type'],
			'reference' => ['start' => $arr_chronology['start']['start']['date_object_sub_id'], 'end' => $arr_chronology['end']['end']['date_object_sub_id']]
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
			$value = value2JSON($value);
		}
		$value = ltrim($value);
					
		$is_json = (substr($value, 0, 1) == '{');
			
		// Check if a transformation is needed
		
		$num_srid = static::GEOMETRY_SRID;
		
		if (static::GEOMETRY_SRID) {
			
			$num_srid = static::geometryToSRID($value, true);
		}
		
		if (strStartsWith($value, 'SRID=')) { // Clean optional SRID prefix
			
			$value = preg_replace('/^SRID=\d+;?/', '', $value);
		}
		
		try {
			
			$value = static::formatToSQLGeometryValid($value, $num_srid, $is_json);
			
		} catch (Exception $e) {
			
			Labels::setVariable('type', ($is_json ? 'GeoJSON' : 'Geometry'));
			
			$e_previous = $e->getPrevious(); // Get DBTrouble
			$num_code = ($e_previous instanceof DBTrouble ? $e_previous->getCode() : false);
			
			$msg_client = '';
			if (DB::ENGINE_IS_MYSQL && ($num_code == 3731 || $num_code == 3732 || $num_code == 3616 || $num_code == 3617) && $is_json) { // Out of bounds
				
				try {
					
					$value = static::translateToGeometry($value, $num_srid); // Try to fix the bounds
					$value = static::formatToSQLGeometryValid($value, $num_srid, true);
				} catch (Exception $e2) {
					
					$msg_client = getLabel('msg_malformed_geometry_bounds');
				}
			} else {
				
				$msg_client = getLabel('msg_malformed_geometry');
			}
			
			if ($msg_client) {
				
				error($msg_client, TROUBLE_ERROR, LOG_BOTH, false, $e);
			}
		}
						
		return $value;
	}
	
	public static function formatToSQLGeometryValid($value, $num_srid, $is_json) {
		
		// Check if it is valid GeoJSON or geometry text (geometry WKT), and is queryable
		
		$value = DBFunctions::strEscape($value);
						
		if (DB::ENGINE_IS_MYSQL) {
			
			if ($is_json) {
				$sql_value = "ST_GeomFromGeoJSON('".$value."', 2, ".$num_srid.")";
			} else {
				$sql_value = "ST_SRID(ST_GeomFromText('".$value."'), ".$num_srid.")";
			}
			
			//$sql_geojson = "ST_AsGeoJSON(@check, ".static::GEOMETRY_COORDINATE_DECIMALS.", ".($num_srid != static::GEOMETRY_SRID ? '2' : '0').")"; // MariaDB does not support the option for adding the crs
			if ($num_srid != static::GEOMETRY_SRID) {
				$sql_geojson = 'INSERT(ST_AsGeoJSON(@check, '.static::GEOMETRY_COORDINATE_DECIMALS.'), 2, 0, CONCAT(\'"crs": {"type": "name", "properties": {"name": "EPSG:\', ST_SRID(@check), \'"}}, \'))';
			} else {
				$sql_geojson = 'ST_AsGeoJSON(@check, '.static::GEOMETRY_COORDINATE_DECIMALS.')';
			}
			
			$sql = "
				SET @check = ".$sql_value.";
				SELECT ST_Intersects(@check, @check), ST_IsEmpty(@check);
				SELECT ".$sql_geojson.";
			";
		} else {
			
			$sql_value = ($is_json ? "ST_GeomFromGeoJSON('".$value."')" : "ST_GeomFromText('".$value."', ".$num_srid.")");
			$sql_geojson = "ST_AsGeoJSON(vars.check, ".static::GEOMETRY_COORDINATE_DECIMALS.", ".($num_srid != static::GEOMETRY_SRID ? '2' : '0').")";
			
			$sql = "
				WITH vars AS (SELECT ".$sql_value." AS check);
				SELECT ST_Intersects(vars.check, vars.check), ST_IsEmpty(vars.check) FROM vars;
				SELECT ".$sql_geojson." FROM vars;
			";
		}

		$res = DB::queryMulti($sql);
		
		$is_empty = $res[1]->fetchRow();
		$is_empty = (bool)$is_empty[1];
		
		if ($is_empty) {
			
			Labels::setVariable('type', ($is_json ? 'GeoJSON' : 'Geometry'));
			
			error(getLabel('msg_malformed_geometry_empty'), TROUBLE_ERROR, LOG_BOTH, false, $e);
		}
		
		$value = $res[2]->fetchRow();
		$value = $value[0];
		
		return $value;
	}
	
	public static function translateToGeometry($value, $num_srid) {
			
		$process = new ProcessProgram('ogr2ogr'
			.' -t_srs EPSG:'.$num_srid
			.' -lco RFC7946=YES' // Output lastest GeoJSON standard, also automatically splits at the antimeridian
			//.' -lco COORDINATE_PRECISION='.static::GEOMETRY_COORDINATE_DECIMALS
			//.' -makevalid'
			.' -f GeoJSON'
			.' "/vsistdout/"'
			.' "/vsistdin/"'
		);
		
		$process->writeInput($value);
		$process->closeInput();
		
		$process->checkOutput(false, true);
		
		$str_error = $process->getError();
		
		if ($str_error !== '') {
			
			error(getLabel('msg_malformed_geometry_transform'), TROUBLE_ERROR, LOG_BOTH, $str_error, $e);
		}
		
		$value = $process->getOutput();
		
		return $value;
	}
	
	public static function geometryToSRID($value, $do_external = false) {
				
		if ($do_external) { // Check for SRID in non-parsed source
			
			if (strpos($value, 'EPSG:') !== false || strpos($value, 'epsg:') !== false) {
				
				preg_match('/(?:EPSG|epsg)::?(\d+)/', $value, $arr_match);
				$num_srid = (int)$arr_match[1];
				
				if ($num_srid) {
					return $num_srid;
				}
			} else if (strpos($value, 'urn:ogc:def:crs:') !== false) {
				
				if (strpos($value, 'OGC:1.3:CRS84') !== false || strpos($value, 'ogc:1.3:crs84') !== false) { // CRS84 for WGS84 for 4326
					return 4326;
				}
			} else if (strStartsWith($value, 'SRID=')) {
				
				preg_match('/^SRID=(\d+);?/', $value, $arr_match);
				$num_srid = (int)$arr_match[1];
				
				if ($num_srid) {
					return $num_srid;
				}
			}
		} else if (strpos($value, 'EPSG:') !== false || strpos($value, 'epsg:') !== false) {
			
			preg_match('/(?:EPSG|epsg):(\d+)/', $value, $arr_match);
			$num_srid = (int)$arr_match[1];
			
			if ($num_srid) {
				return $num_srid;
			}
		}
		
		return static::GEOMETRY_SRID;
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

	public static function date2Integer($date, $int_direction = false) {
		
		// *ymmdd OR *y OR *y-mm / mm-*y OR *y-mm-dd / dd-mm-*y => *ymmdds
		// $int_direction false OR StoreType::TIME_AFTER_BEGIN / StoreType::TIME_BEFORE_BEGIN OR StoreType::TIME_BEFORE_END / StoreType::TIME_AFTER_END
		
		$arr_date = [];
		
		if ($date) {
			
			if (is_int($date)) { // Real integer: should be the internal nodegoat date-value
				
				$date = $date;
			} else if ($date === 'now') {
				
				$date = date('Ymd').static::DATE_INT_SEQUENCE_NULL;
			} else if ($date !== (string)(int)$date) { // Check whether date equals a whole number (i.e. year only)
				
				if ($date == '∞') {
					
					$date = static::DATE_INT_MAX;
				} else if ($date == '-∞') {
					
					$date = static::DATE_INT_MIN;
				} else {

					$arr_date = static::date2Components($date);
					$date = false;
					
					if (!$arr_date[0]) {
						$arr_date = [];
					}
				}
			} else {
				
				$arr_date = [$date, '00', '00', static::DATE_INT_SEQUENCE_NULL];
			}
		}
		
		if ($arr_date) {
			
			if ($int_direction) {
				
				if ($arr_date[1] == '00') {
					$arr_date[1] = ($int_direction == StoreType::TIME_BEFORE_END || $int_direction == StoreType::TIME_AFTER_END ? '12' : '00');
				}
				if ($arr_date[2] == '00') {
					$arr_date[2] = ($int_direction == StoreType::TIME_BEFORE_END || $int_direction == StoreType::TIME_AFTER_END ? '31' : '00');
				}
				if ($arr_date[3] == static::DATE_INT_SEQUENCE_NULL) {
					$arr_date[3] = ($int_direction == StoreType::TIME_BEFORE_END || $int_direction == StoreType::TIME_AFTER_END ? '9999' : '0000');
				}
			}
			
			$date = implode('', $arr_date);
		}
		
		if ($date) {
			
			if ($date <= static::DATE_INT_MIN) {
				$date = static::DATE_INT_MIN;
			} else if ($date >= static::DATE_INT_MAX) {
				$date = static::DATE_INT_MAX;
			}
		}

		return ((int)$date ?: false);
	}
	
	public static function date2Components($date) {
		
		$num_sequence = 0;
		
		$str_sequence_separator = null;
		
		if (strpos($date, ' ') !== false) {
			$str_sequence_separator = ' ';
		} else if (strpos($date, 'T') !== false) {
			$str_sequence_separator = 'T';
		}
		
		if ($str_sequence_separator !== null) {
				
			$date = explode($str_sequence_separator, $date);

			if (strpos($date[1], ':') !== false) { // hours:minutes(:seconds+zone)
				
				$arr_time = explode(':', $date[1]);
				$num_sequence = (((int)$arr_time[0] * 60) + (int)$arr_time[1]);
			} else {
				
				$num_sequence = (int)$date[1];
			}
			
			$date = $date[0];
		}
		
		$num_sequence += static::DATE_INT_SEQUENCE_NULL;
		
		$date = str_replace(['/', '.'], '-', $date);
		$min = '';
		
		if (substr($date, 0, 1) == '-') { // -13-10-5456
			$date = substr($date, 1);
			$min = '-';
		} else if (strpos($date, '--') !== false) { // 13-10--5456
			$date = str_replace('--', '-', $date);
			$min = '-';
		}
		$arr_date = array_filter(explode('-', $date));
		
		$year = ($arr_date[0] ?? 0);
		
		if (strlen($year) > 2) { // y-m-d
			
			$year = ($year === '∞' ? '∞' : (int)$year);
				
			$arr_date = [$min.$year, str_pad((int)($arr_date[1] ?? 0), 2, '0', STR_PAD_LEFT), str_pad((int)($arr_date[2] ?? 0), 2, '0', STR_PAD_LEFT), str_pad($num_sequence, 4, '0', STR_PAD_LEFT)];
		} else { // d-m-y
			
			$num_length = count($arr_date);
		
			$year = $arr_date[$num_length-1];
			$year = ($year === '∞' ? '∞' : (int)$year);
				
			$arr_date = [$min.$year, str_pad((int)($arr_date[$num_length-2] ?? 0), 2, '0', STR_PAD_LEFT), str_pad((int)($arr_date[$num_length-3] ?? 0), 2, '0', STR_PAD_LEFT), str_pad($num_sequence, 4, '0', STR_PAD_LEFT)];
		}

		return $arr_date;
	}
	
	public static function int2Date($date, $do_ymd = false) {
		
		if ($date == static::DATE_INT_MAX) {
			
			$date = '∞';
		} else if ($date == static::DATE_INT_MIN) {
			
			$date = '-∞';
		} else {
			
			$str_year = substr($date, 0, -8);
			if (strlen($date) < 8 + 3) {
				$str_year = str_pad($str_year, 3, '0', STR_PAD_LEFT);
			}
			$num_sequence = (int)substr($date, -4, 4);
			
			if ($do_ymd) {
				$date = $str_year.'-'.substr($date, -8, 2).'-'.substr($date, -6, 2);
				$date = str_replace('-00', '', $date);
			} else {
				$date = substr($date, -6, 2).'-'.substr($date, -8, 2).'-'.$str_year;
				$date = str_replace('00-', '', $date);
			}

			if ($num_sequence != static::DATE_INT_SEQUENCE_NULL) {

				$date .= ' '.($num_sequence - static::DATE_INT_SEQUENCE_NULL);
			}
		}
		
		return $date;
	}
		
	public static function dateInt2DateStandard($date) {
		
		$date = substr($date, 0, -8).'-'.substr($date, -8, 2).'-'.substr($date, -6, 2);
		$date = str_replace('-00', '', $date);
		
		return $date;
	}
	
	public static function chronologyDateInt2Date($date, $arr_chronology_statement, $int_identifier) {
		
		if (!$date) {
			return false;
		}
		
		$str_date = self::int2Date($date);
		$str_classifier = '';
		
		if ($arr_chronology_statement) {
				
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
		}
		
		if ($str_classifier) {
			$str_date = $str_classifier.' '.$str_date;
		}
		
		return $str_date;
	}
	
	public static function dateInt2Compontents($date) {
		
		$date = (int)$date;
		$num_date = (abs($date) % 100000000);
		$num_year = ((abs($date) - $num_date) / 100000000) * (($date > 0) - ($date < 0));
		$num_absolute = (($num_year + (static::DATE_INT_CALC / 100000000)) * 100000000) + $num_date;
		
		$num_date = $num_date + 100000000; // Add 100000000 to make e.g. '00005000' a separate valid integer
		
		return ['year' => $num_year, 'date' => $num_date, 'absolute' => $num_absolute];
	}
	
	public static function dateInt2Absolute($date) {
		
		$date = (int)$date;
		$num_date = (abs($date) % 100000000);
		$num_year = ((abs($date) - $num_date) / 100000000) * (($date > 0) - ($date < 0));
		$num_absolute = (($num_year + (static::DATE_INT_CALC / 100000000)) * 100000000) + $num_date;

		return $num_absolute;
	}
	
	public static function dateSQL2Compontents($name) {
		
		$name_date = '(ABS('.$name.') % 100000000)'; // Add 100000000 to make e.g. '00005000' a valid integer
		$name_year = '((ABS('.$name.') - '.$name_date.') / 100000000) * SIGN('.$name.')';
		$name_absolute = '(('.$name_year.' + '.(static::DATE_INT_CALC / 100000000).') * 100000000) + '.$name_date;
		
		$name_date = '('.$name_date.' + 100000000)'; // Add 100000000 to make e.g. '00005000' a valid integer
		
		return ['year' => $name_year, 'date' => $name_date, 'absolute' => $name_absolute];
	}
	
	public static function dateSQL2Absolute($name) {
		
		$name_date = '(ABS('.$name.') % 100000000)'; // Add 100000000 to make e.g. '00005000' a valid integer
		$name_year = '((ABS('.$name.') - '.$name_date.') / 100000000) * SIGN('.$name.')';
		$name_absolute = '(('.$name_year.' + '.(static::DATE_INT_CALC / 100000000).') * 100000000) + '.$name_date;
			
		return $name_absolute;
	}
}
