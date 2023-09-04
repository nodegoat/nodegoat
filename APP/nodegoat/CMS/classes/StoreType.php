<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreType {
	
	const MODE_UPDATE = 1;
	const MODE_OVERWRITE = 2;
	const CHECK_ALL = 1;
	const CHECK_CONFIRMED = 2;
	
	const CLEARANCE_PURPOSE_VIEW = 'view';
	const CLEARANCE_PURPOSE_EDIT = 'edit';
	
	protected $type_id = false;
	protected $user_id = false;
	
	protected $mode = self::MODE_OVERWRITE;
	protected $do_check = self::CHECK_ALL;
	protected $arr_confirm = [];
	
	protected $arr_type_set = [];
	protected $arr_types_all = [];

	protected $has_unresolved_ids = false;
		
	protected static $arr_types_touched = [];
	
	// Static call caching
	protected static $arr_system_type_ids = [];
	protected static $arr_system_type_object_description_ids = [];
	public static $arr_types_storage = [];
	protected static $arr_value_types = [];
	protected static $arr_types_object_path_storage = [];
	
	protected static $arr_time_units = [];
	protected static $arr_time_units_internal = [];
	protected static $arr_time_directions = [];
	protected static $arr_time_directions_internal = [];
	
	const TYPE_CLASS_TYPE = 0;
	const TYPE_CLASS_CLASSIFICATION = 1;
	const TYPE_CLASS_REVERSAL = 2;
	const TYPE_CLASS_SYSTEM = 3;
	
	const TYPE_MODE_DEFAULT = 0; // Type model and Objects linked to Domain
	const TYPE_MODE_X = 1; // Reserved for Type-specific modes
	const TYPE_MODE_XX = 2; // Reserved for Type-specific modes
	const TYPE_MODE_REVERSAL_CLASSIFICATION = 1;
	const TYPE_MODE_REVERSAL_COLLECTION = 2;
	
	const TIME_UNIT_DAY = 1;
	const TIME_UNIT_MONTH = 2;
	const TIME_UNIT_YEAR = 3;
	const TIME_AFTER_BEGIN = 1;
	const TIME_BEFORE_BEGIN = 2;
	const TIME_AFTER_END = 3;
	const TIME_BEFORE_END = 4;
	
	const DATE_START_START = 1;
	const DATE_START_END = 2;
	const DATE_END_START = 3;
	const DATE_END_END = 4;
	
	const VALUE_TYPE_DATE_CHRONOLOGY = 1;
	const VALUE_TYPE_DATE_POINT = 2;
	const VALUE_TYPE_DATE_OBJECT_SUB = 3;
	
	const VALUE_TYPE_LOCATION_REFERENCE_LOCK = 1;
	const VALUE_TYPE_LOCATION_REFERENCE = 2;
	const VALUE_TYPE_LOCATION_GEOMETRY = 3;
	const VALUE_TYPE_LOCATION_POINT = 4;
	
    public function __construct($type_id, $user_id = false) {
		
		
		$this->type_id = ($type_id ? (int)$this->getTypeID($type_id) : false);
		
		if ($this->type_id) {
			$this->arr_type_set = self::getTypeSet($type_id);
		}
		
		$this->user_id = (int)$user_id;
    }
    
	public function setMode($mode = self::MODE_UPDATE, $do_check = self::CHECK_ALL) {
		
		// $mode = MODE_OVERWRITE OR MODE_UPDATE
		// $do_check = CHECK_ALL or CHECK_CONFIRMED OR false: perform type checks before update
		
		if ($mode !== null) {
			$this->mode = $mode;
		}
		if ($do_check !== null) {
			$this->do_check = $do_check;
		}
	}
    		
	public function store($arr_details, $arr_definitions, $arr_object_descriptions, $arr_object_subs_details) {
		
		$func_parse = function($value) {
			
			if ($value === null) {
				return null;
			}
			if (is_string($value)) {
				return parseValue($value, TYPE_TEXT);
			}
			
			return $value;
		};
		
		$arr_details = arrParseRecursive($arr_details, $func_parse);
		$arr_definitions = ($arr_definitions ? arrParseRecursive($arr_definitions, $func_parse) : []);
		$arr_object_descriptions = ($arr_object_descriptions ? arrParseRecursive($arr_object_descriptions, $func_parse, ['separator' => true], false) : []);
		$arr_object_subs_details = ($arr_object_subs_details ? arrParseRecursive($arr_object_subs_details, $func_parse, ['separator' => true], false) : []);
		
		$this->mode = (!$this->type_id ? self::MODE_OVERWRITE : $this->mode);
		
		if ($this->do_check) {
			
			$this->checkStore($arr_details, $arr_object_descriptions, $arr_object_subs_details);
		}
		
		$num_type_class = (int)(!$this->type_id ? $arr_details['class'] : $this->arr_type_set['type']['class']);
		$num_type_mode = $this->parseTypeMode($arr_details['mode']);

		if ($this->mode == self::MODE_OVERWRITE || ($this->mode == self::MODE_UPDATE && $arr_details !== null)) {
			
			$clearance_edit = ($arr_details['clearance_edit'] !== null ? (int)$arr_details['clearance_edit'] : null);
			
			$num_type_mode = ($this->type_id ? (int)$this->arr_type_set['type']['mode'] : 0);
			
			
			if ($this->type_id) {
				$num_type_mode = bitUpdateMode($num_type_mode, BIT_MODE_SUBTRACT, static::TYPE_MODE_X, static::TYPE_MODE_XX);
			}
			
			if ($num_type_class == self::TYPE_CLASS_REVERSAL) {
				
				$arr_system_type_set = static::getSystemTypeSet(static::getSystemTypeID('reversal'));
				
				$arr_details['use_object_name'] = $arr_system_type_set['type']['use_object_name'];
				$arr_details['object_name_in_overview'] = $arr_system_type_set['type']['object_name_in_overview'];
				
				$num_type_mode = bitUpdateMode($num_type_mode, BIT_MODE_ADD, ($arr_details['reversal_mode'] ? static::TYPE_MODE_REVERSAL_COLLECTION : static::TYPE_MODE_REVERSAL_CLASSIFICATION));
			}

			// Type
						
			if ($this->type_id) {
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPES')." SET
						name = '".DBFunctions::strEscape($arr_details['name'])."',
						".(Settings::get('domain_administrator_mode') ? "label = '".DBFunctions::strEscape($arr_details['label'])."'," : '')."
						color = '".str2Color($arr_details['color'])."',
						condition_id = ".(int)$arr_details['condition_id'].",
						"."
						use_object_name = ".DBFunctions::escapeAs($arr_details['use_object_name'], DBFunctions::TYPE_BOOLEAN).",
						object_name_in_overview = ".DBFunctions::escapeAs($arr_details['object_name_in_overview'], DBFunctions::TYPE_BOOLEAN).",
						mode = ".$num_type_mode."
						".($clearance_edit !== null ?
							", clearance_edit = ".$clearance_edit
						: "")."
					WHERE id = ".$this->type_id."
				");
			} else {

				if (!variableHasValue($num_type_class, self::TYPE_CLASS_REVERSAL, self::TYPE_CLASS_CLASSIFICATION, self::TYPE_CLASS_TYPE)) {
					$num_type_class = self::TYPE_CLASS_TYPE;
				}
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPES')."
					(class, mode, name, label, color, condition_id, clearance_edit".", use_object_name, object_name_in_overview, date)
						VALUES
					(
						".$num_type_class.",
						".$num_type_mode.",
						'".DBFunctions::strEscape($arr_details['name'])."',
						'".(Settings::get('domain_administrator_mode') ? DBFunctions::strEscape($arr_details['label']) : '')."',
						'".str2Color($arr_details['color'])."',
						0,
						".(int)$arr_details['clearance_edit'].",
						"."
						".DBFunctions::escapeAs($arr_details['use_object_name'], DBFunctions::TYPE_BOOLEAN).",
						".DBFunctions::escapeAs($arr_details['object_name_in_overview'], DBFunctions::TYPE_BOOLEAN).",
						NOW()
					)
				");
				
				$this->type_id = DB::lastInsertID();
			}
		}
								
		// Type definitions
			
		$arr_ids = [];

		if ($arr_definitions) {
			
			$num_sort = 0;
			
			foreach ($arr_definitions as $arr_definition) {
				
				if (!$arr_definition['definition_name']) {
					
					if ($this->mode == self::MODE_UPDATE && $arr_definition['definition_id']) {
						$arr_ids[] = (int)$arr_definition['definition_id'];
					}
					
					continue;
				}
				
				if ($arr_definition['definition_id']) {
					
					$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')." SET
							name = '".DBFunctions::strEscape($arr_definition['definition_name'])."',
							text = '".DBFunctions::strEscape($arr_definition['definition_text'])."'
							".($this->mode == self::MODE_OVERWRITE ? ", sort = ".$num_sort : "")."
						WHERE id = ".(int)$arr_definition['definition_id']."
							AND type_id = ".$this->type_id."
					");
					
					if ($this->mode == self::MODE_OVERWRITE) {
						$arr_ids[] = (int)$arr_definition['definition_id'];
					}
				} else {
					
					$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')."
						(type_id, name, text, sort)
							VALUES
						(".$this->type_id.", '".DBFunctions::strEscape($arr_definition['definition_name'])."', '".DBFunctions::strEscape($arr_definition['definition_text'])."', ".$num_sort.")
					");
					
					if ($this->mode == self::MODE_OVERWRITE) {
						$arr_ids[] = DB::lastInsertID();
					}
				}
				
				$num_sort++;
			}
		}
			
		$sql_select = ($this->mode == self::MODE_UPDATE ? ($arr_ids ? "id IN (".implode(",", $arr_ids).")" : "") : ($arr_ids ? "id NOT IN (".implode(",", $arr_ids).")" : "TRUE"));
		
		if ($sql_select) {
				
			$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')."
				WHERE type_id = ".$this->type_id."
					AND ".$sql_select."
			");
		}
		
		// Object descriptions

		if ($num_type_class == self::TYPE_CLASS_REVERSAL) {
			
			if ($this->mode == self::MODE_OVERWRITE || ($this->mode == self::MODE_UPDATE && $arr_details !== null)) {
				
				$reversal_ref_type_id = 0;
				if (bitHasMode($num_type_mode, static::TYPE_MODE_REVERSAL_CLASSIFICATION)) {
					$reversal_ref_type_id = $this->getTypeID($arr_details['reversal_ref_type_id']);
				}
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')."
						(id_id, type_id, ref_type_id, in_name)
							VALUES
						(".StoreType::getSystemTypeObjectDescriptionID(StoreType::getSystemTypeID('reversal'), 'reference').", ".$this->type_id.", ".(int)$reversal_ref_type_id.", ".DBFunctions::escapeAs($reversal_ref_type_id, DBFunctions::TYPE_BOOLEAN).")
					".DBFunctions::onConflict('id_id, type_id', ['ref_type_id', 'in_name'])."
				");
			}				
		} else {
				
			$arr_ids = [];
			
			if ($arr_object_descriptions) {
				
				$num_sort = 0;
				
				foreach ($arr_object_descriptions as $arr_object_description) {
					
					$arr_object_description['object_description_id'] = $this->getTypeObjectDescriptionID($this->type_id, $arr_object_description['object_description_id']);
					
					if (!$arr_object_description['object_description_name']) {
						
						if ($this->mode == self::MODE_UPDATE && $arr_object_description['object_description_id']) {
							$arr_ids[] = $arr_object_description['object_description_id'];
						}
						
						continue;
					}
					
					$arr_value_types = StoreType::getValueTypesBase();
					$arr_option_multi_value_types = [];
					$arr_option_identifier_value_types = [];
					
					foreach ($arr_value_types as $arr_value_type) {
						
						if ($arr_value_type['has_support_multi']) {
							$arr_option_multi_value_types[] = $arr_value_type['id'];
						}
						if ($arr_value_type['has_support_identifier']) {
							$arr_option_identifier_value_types[] = $arr_value_type['id'];
						}
					}
					
					$object_description_value_type_base = $arr_object_description['object_description_value_type_base'];
					$object_description_ref_type_id = $this->getTypeID($arr_object_description['object_description_ref_type_id']);
					
					if (in_array($object_description_value_type_base, ['type', 'classification', 'reversal'])) {
						if (!$object_description_ref_type_id) {
							$object_description_value_type_base = '';
						}
					} else {
						$object_description_ref_type_id = 0;
					}

					$object_description_is_required = ($arr_object_description['object_description_is_required'] && !in_array($object_description_value_type_base, ['reversal']) ? 1 : 0);
					
					$object_description_has_multi = (in_array($object_description_value_type_base, ['type', 'classification']) || in_array($object_description_value_type_base, $arr_option_multi_value_types) ? $arr_object_description['object_description_has_multi'] : 0);
					$object_description_has_multi = (($object_description_has_multi || $object_description_value_type_base == 'reversal') ? true : false);
					
					$object_description_is_identifier = $arr_object_description['object_description_is_identifier'];
					if ($object_description_is_identifier !== null) {
						$object_description_is_identifier = (in_array($object_description_value_type_base, $arr_option_identifier_value_types) ? $object_description_is_identifier : 0);
					}
					
					$object_description_has_default_value = true;
					$object_description_in_name = (bool)$arr_object_description['object_description_in_name'];
										
					$object_description_value_type_settings = $this->parseTypeObjectDescriptionValueTypeOptions($object_description_value_type_base, $object_description_ref_type_id, $object_description_has_multi, $object_description_has_default_value, $object_description_in_name, $arr_object_description['object_description_value_type_settings']);
					
					$clearance_view = ($arr_object_description['object_description_clearance_view'] !== null ? (int)$arr_object_description['object_description_clearance_view'] : null);
					$clearance_edit = ($arr_object_description['object_description_clearance_edit'] !== null ? (int)$arr_object_description['object_description_clearance_edit'] : null);
					$clearance_edit = ($clearance_view && $clearance_view > $clearance_edit ? $clearance_view : $clearance_edit);
					
					if ($arr_object_description['object_description_id']) {
						
						$arr_convert_objects = $this->getConvertTypeObjectDefinitions($arr_object_description['object_description_id'], $object_description_value_type_base);
												
						$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." SET
								name = '".DBFunctions::strEscape($arr_object_description['object_description_name'])."',
								value_type_base = '".DBFunctions::strEscape($object_description_value_type_base)."',
								value_type_settings = '".DBFunctions::strEscape($object_description_value_type_settings)."',
								is_required = ".DBFunctions::escapeAs($object_description_is_required, DBFunctions::TYPE_BOOLEAN).",
								is_unique = ".DBFunctions::escapeAs($arr_object_description['object_description_is_unique'], DBFunctions::TYPE_BOOLEAN).",
								has_multi = ".DBFunctions::escapeAs($object_description_has_multi, DBFunctions::TYPE_BOOLEAN).",
								ref_type_id = ".(int)$object_description_ref_type_id.",
								in_name = ".DBFunctions::escapeAs($object_description_in_name, DBFunctions::TYPE_BOOLEAN).",
								in_search = ".DBFunctions::escapeAs($arr_object_description['object_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
								in_overview = ".DBFunctions::escapeAs($arr_object_description['object_description_in_overview'], DBFunctions::TYPE_BOOLEAN)."
								".($object_description_is_identifier !== null ? 
									", is_identifier = ".DBFunctions::escapeAs($object_description_is_identifier, DBFunctions::TYPE_BOOLEAN)
								: "")."
								".($clearance_view !== null ? 
									", clearance_edit = ".$clearance_edit."
									, clearance_view = ".$clearance_view
								: "")."
								".($this->mode == self::MODE_OVERWRITE ? ", sort = ".$num_sort : "")."
							WHERE id = ".(int)$arr_object_description['object_description_id']."
								AND type_id = ".$this->type_id."
						");
						
						if ($this->mode == self::MODE_OVERWRITE) {
							
							$arr_ids[] = $arr_object_description['object_description_id'];
						}
						
						if ($arr_convert_objects) {
							
							timeLimit(300);
							self::$arr_types_storage[$this->type_id] = false; // Reload type set on next call
							
							$storage = new StoreTypeObjects($this->type_id, false, $this->user_id);
				
							foreach ($arr_convert_objects as $object_id => $arr_object) {
								
								$storage->setObjectID($object_id);
								$storage->store([], $arr_object['object_definitions'], []);
							}
							
							$storage->commit(true);
						}
					} else {
						
						$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')."
							(type_id, name, value_type_base, value_type_settings, is_required, is_unique, has_multi, ref_type_id, in_name, in_search, in_overview, is_identifier, clearance_edit, clearance_view, sort)
								VALUES
							(".$this->type_id.",
								'".DBFunctions::strEscape($arr_object_description['object_description_name'])."',
								'".DBFunctions::strEscape($object_description_value_type_base)."',
								'".DBFunctions::strEscape($object_description_value_type_settings)."',
								".DBFunctions::escapeAs($object_description_is_required, DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($arr_object_description['object_description_is_unique'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($object_description_has_multi, DBFunctions::TYPE_BOOLEAN).",
								".(int)$object_description_ref_type_id.",
								".DBFunctions::escapeAs($arr_object_description['object_description_in_name'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($arr_object_description['object_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($arr_object_description['object_description_in_overview'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($object_description_is_identifier, DBFunctions::TYPE_BOOLEAN).",
								".(int)$clearance_edit.",
								".(int)$clearance_view.",
								".$num_sort."
							)
						");
						
						if ($this->mode == self::MODE_OVERWRITE) {
							
							$arr_ids[] = DB::lastInsertID();
						}
					}
					
					$num_sort++;
				}
			}
			
			$sql_select = ($this->mode == self::MODE_UPDATE ? ($arr_ids ? "id IN (".implode(",", $arr_ids).")" : "") : ($arr_ids ? "id NOT IN (".implode(",", $arr_ids).")" : "TRUE"));
			
			if ($sql_select) {
					
				$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')."
					WHERE type_id = ".$this->type_id."
						AND ".$sql_select."
				");
			}
		}
		
		// Object sub details

		$arr_ids = [];
		$arr_touched_cache_object_sub_details_ids = [];
		
		if ($arr_object_subs_details) {
			
			$num_sort = 0;
			
			foreach ($arr_object_subs_details as $arr_object_sub_details) {
				
				$arr_object_sub_details['object_sub_details']['object_sub_details_id'] = $this->getTypeObjectSubDetailsID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_id']);
						
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_name']) {
					
					if ($this->mode == self::MODE_UPDATE && $arr_object_sub_details['object_sub_details']['object_sub_details_id']) {
						$arr_ids[] = $arr_object_sub_details['object_sub_details']['object_sub_details_id'];
					}
					
					continue;
				}
				
				$arr_object_sub_details_self = $arr_object_sub_details['object_sub_details'];
				
				$object_sub_details_date_type = ($arr_object_sub_details_self['object_sub_details_date_type'] ?? null ?: (!$arr_object_sub_details_self['object_sub_details_has_date'] ? 'none' : ($arr_object_sub_details_self['object_sub_details_is_date_period'] ? 'period' : 'date')));
				$object_sub_details_date_usage = ($arr_object_sub_details_self['object_sub_details_date_usage'] ?? null ?: ($arr_object_sub_details_self['object_sub_details_date_use_object_sub_details_id'] ? 'object_sub_details' : ($arr_object_sub_details_self['object_sub_details_date_start_use_object_sub_description_id'] ? 'object_sub_description' : ($arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id'] ? 'object_description' : ''))));
				
				$object_sub_details_has_date = true;
				$object_sub_details_is_date_period = ($object_sub_details_date_type == 'period');
				if ($object_sub_details_date_type == 'none') {
					$object_sub_details_has_date = false;
					$object_sub_details_is_date_period = false;
				}
				
				$object_sub_details_date_setting = ($arr_object_sub_details_self['object_sub_details_date_setting'] ?? '');
				
				if ($object_sub_details_date_setting && $object_sub_details_date_setting != 'source') {
					$object_sub_details_date_usage = '';
				}
				if ($object_sub_details_date_usage != 'object_sub_details') {
					unset($arr_object_sub_details_self['object_sub_details_date_use_object_sub_details_id']);
				}
				if ($object_sub_details_date_usage != 'object_sub_description') {
					unset($arr_object_sub_details_self['object_sub_details_date_start_use_object_sub_description_id'], $arr_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id']);
				} else {
					if (!$object_sub_details_is_date_period) {
						unset($arr_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id']);
					} else {
						$arr_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id'] = ($arr_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id'] ?: $arr_object_sub_details_self['object_sub_details_date_start_use_object_sub_description_id']);
					}
				}
				if ($object_sub_details_date_usage != 'object_description') {
					unset($arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id'], $arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id']);
				} else {
					if (!$object_sub_details_is_date_period) {
						unset($arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id']);
					} else {
						$arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id'] = ($arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id'] ?: $arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id']);
					}
				}
				if ($object_sub_details_date_usage) {
					$object_sub_details_date_setting = '';
				}
				
				$arr_date_options = static::getDateOptions(true);
				$object_sub_details_date_setting = ($arr_date_options[$object_sub_details_date_setting]['id'] ?? 0);
				
				$object_sub_details_location_type = ($arr_object_sub_details_self['object_sub_details_location_type'] ?? null ?: (!$arr_object_sub_details_self['object_sub_details_has_location'] ? 'none' : 'default'));
				$object_sub_details_location_usage = ($arr_object_sub_details_self['object_sub_details_location_usage'] ?? null ?: ($arr_object_sub_details_self['object_sub_details_location_use_object_id'] ? 'object' : ($arr_object_sub_details_self['object_sub_details_location_use_object_description_id'] ? 'object_description' : ($arr_object_sub_details_self['object_sub_details_location_use_object_sub_details_id'] ? 'object_sub_details' : ($arr_object_sub_details_self['object_sub_details_location_use_object_sub_description_id'] ? 'object_sub_description' : '')))));
		
				$object_sub_details_has_location = true;
				if ($object_sub_details_location_type == 'none') {
					$object_sub_details_has_location = false;
				}
				
				if (!$arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id'] || !$arr_object_sub_details_self['object_sub_details_location_ref_type_id_locked']) {
					unset($arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id_locked']);				
				}
				if (!$arr_object_sub_details_self['object_sub_details_location_ref_type_id'] || !$arr_object_sub_details_self['object_sub_details_location_ref_type_id_locked']) {
					unset($arr_object_sub_details_self['object_sub_details_location_ref_type_id_locked']);
					$object_sub_details_location_usage = '';				
				}
				if ($object_sub_details_location_usage != 'object_sub_details') {
					unset($arr_object_sub_details_self['object_sub_details_location_use_object_sub_details_id']);
				}
				if ($object_sub_details_location_usage != 'object_sub_description') {
					unset($arr_object_sub_details_self['object_sub_details_location_use_object_sub_description_id']);
				}
				if ($object_sub_details_location_usage != 'object_description') {
					unset($arr_object_sub_details_self['object_sub_details_location_use_object_description_id']);
				}
				if ($object_sub_details_location_usage == 'object' && $this->getTypeID($arr_object_sub_details_self['object_sub_details_location_ref_type_id']) != $this->type_id) {
					$object_sub_details_location_usage = '';
				}
				
				$arr_location_options = static::getLocationOptions(true);
				$object_sub_details_location_setting = ($arr_location_options[$arr_object_sub_details_self['object_sub_details_location_setting']]['id'] ?? 0);
				
				$clearance_view = ($arr_object_sub_details_self['object_sub_details_clearance_view'] !== null ? (int)$arr_object_sub_details_self['object_sub_details_clearance_view'] : null);
				$clearance_edit = ($arr_object_sub_details_self['object_sub_details_clearance_edit'] !== null ? (int)$arr_object_sub_details_self['object_sub_details_clearance_edit'] : null);
				$clearance_edit = ($clearance_view && $clearance_view > $clearance_edit ? $clearance_view : $clearance_edit);
				
				$object_sub_details_date_setting_type_id = $this->getTypeID($arr_object_sub_details_self['object_sub_details_date_setting_type_id']);
				$object_sub_details_date_setting_object_sub_details_id = $this->getTypeObjectSubDetailsID($object_sub_details_date_setting_type_id, $arr_object_sub_details_self['object_sub_details_date_setting_object_sub_details_id']);
				$object_sub_details_date_use_object_sub_details_id = $this->getTypeObjectSubDetailsID($this->type_id, $arr_object_sub_details_self['object_sub_details_date_use_object_sub_details_id']);
				$object_sub_details_date_start_use_object_sub_description_id = $this->getTypeObjectSubDescriptionID($this->type_id, $arr_object_sub_details_self['object_sub_details_id'], $arr_object_sub_details_self['object_sub_details_date_start_use_object_sub_description_id']);
				$object_sub_details_date_start_use_object_description_id = $this->getTypeObjectDescriptionID($this->type_id, $arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id']);
				$object_sub_details_date_end_use_object_sub_description_id = $this->getTypeObjectSubDescriptionID($this->type_id, $arr_object_sub_details_self['object_sub_details_id'], $arr_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id']);
				$object_sub_details_date_end_use_object_description_id = $this->getTypeObjectDescriptionID($this->type_id, $arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id']);
				$object_sub_details_location_ref_type_id = $this->getTypeID($arr_object_sub_details_self['object_sub_details_location_ref_type_id']);
				$object_sub_details_location_ref_object_sub_details_id = $this->getTypeObjectSubDetailsID($object_sub_details_location_ref_type_id, $arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id']);
				$object_sub_details_location_use_object_sub_details_id = $this->getTypeObjectSubDetailsID($this->type_id, $arr_object_sub_details_self['object_sub_details_location_use_object_sub_details_id']);
				$object_sub_details_location_use_object_sub_description_id = $this->getTypeObjectSubDescriptionID($this->type_id, $arr_object_sub_details_self['object_sub_details_id'], $arr_object_sub_details_self['object_sub_details_location_use_object_sub_description_id']);
				$object_sub_details_location_use_object_description_id = $this->getTypeObjectDescriptionID($this->type_id, $arr_object_sub_details_self['object_sub_details_location_use_object_description_id']);
				$object_sub_details_location_use_object_id = ($object_sub_details_location_usage == 'object');

				if ($arr_object_sub_details_self['object_sub_details_id']) {
					
					$object_sub_details_id = (int)$arr_object_sub_details_self['object_sub_details_id'];
					
					$arr_cur_object_sub_details_self = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details'];
					
					$str_compare = (int)$arr_object_sub_details_self['object_sub_details_is_single']
						.'-'.(int)$object_sub_details_date_use_object_sub_details_id
						.'-'.(int)$object_sub_details_date_start_use_object_sub_description_id.'-'.(int)$object_sub_details_date_end_use_object_sub_description_id
						.'-'.(int)$object_sub_details_date_start_use_object_description_id.'-'.(int)$object_sub_details_date_end_use_object_description_id
						.'-'.(int)$object_sub_details_location_use_object_sub_details_id
						.'-'.(int)$object_sub_details_location_use_object_sub_description_id.'-'.(int)$object_sub_details_location_use_object_description_id
						.'-'.(int)$object_sub_details_location_use_object_id;
						
					$str_compare_cur = (int)$arr_cur_object_sub_details_self['object_sub_details_is_single']
						.'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_date_use_object_sub_details_id']
						.'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_date_start_use_object_sub_description_id'].'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id']
						.'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_date_start_use_object_description_id'].'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_date_end_use_object_description_id']
						.'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_location_use_object_sub_details_id']
						.'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_location_use_object_sub_description_id'].'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_location_use_object_description_id']
						.'-'.(int)$arr_cur_object_sub_details_self['object_sub_details_location_use_object_id'];
					
					if ($str_compare != $str_compare_cur) {
						
						$arr_touched_cache_object_sub_details_ids[] = $object_sub_details_id;
					}
					
					$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." SET
							name = '".DBFunctions::strEscape($arr_object_sub_details_self['object_sub_details_name'])."',
							is_single = ".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_is_single'], DBFunctions::TYPE_BOOLEAN).",
							is_required = ".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN).",
							".($clearance_view !== null ? 
								"clearance_edit = ".$clearance_edit.",
								clearance_view = ".$clearance_view.","
							: "")."
							has_date = ".DBFunctions::escapeAs($object_sub_details_has_date, DBFunctions::TYPE_BOOLEAN).",
							is_date_period = ".DBFunctions::escapeAs($object_sub_details_is_date_period, DBFunctions::TYPE_BOOLEAN).",
							date_setting = ".(int)$object_sub_details_date_setting.",
							date_setting_type_id = ".(int)$object_sub_details_date_setting_type_id.",
							date_setting_object_sub_details_id = ".(int)$object_sub_details_date_setting_object_sub_details_id.",
							date_use_object_sub_details_id = ".(int)$object_sub_details_date_use_object_sub_details_id.",
							date_start_use_object_sub_description_id = ".(int)$object_sub_details_date_start_use_object_sub_description_id.",
							date_start_use_object_description_id = ".(int)$object_sub_details_date_start_use_object_description_id.",
							date_end_use_object_sub_description_id = ".(int)$object_sub_details_date_end_use_object_sub_description_id.",
							date_end_use_object_description_id = ".(int)$object_sub_details_date_end_use_object_description_id.",
							has_location = ".DBFunctions::escapeAs($object_sub_details_has_location, DBFunctions::TYPE_BOOLEAN).",
							location_setting = ".(int)$object_sub_details_location_setting.",
							location_ref_type_id = ".(int)$object_sub_details_location_ref_type_id.",
							location_ref_type_id_locked = ".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_location_ref_type_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							location_ref_object_sub_details_id = ".(int)$object_sub_details_location_ref_object_sub_details_id.",
							location_ref_object_sub_details_id_locked = ".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							location_use_object_sub_details_id = ".(int)$object_sub_details_location_use_object_sub_details_id.",
							location_use_object_sub_description_id = ".(int)$object_sub_details_location_use_object_sub_description_id.",
							location_use_object_description_id = ".(int)$object_sub_details_location_use_object_description_id.",
							location_use_object_id = ".DBFunctions::escapeAs($object_sub_details_location_use_object_id, DBFunctions::TYPE_BOOLEAN)."
							".($this->mode == self::MODE_OVERWRITE ? ", sort = ".$num_sort : "")."
						WHERE id = ".(int)$object_sub_details_id."
							AND type_id = ".$this->type_id."
					");
				} else {
					
					$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')."
						(type_id, name, is_single, is_required, clearance_edit, clearance_view,
							has_date, is_date_period, date_setting, date_setting_type_id, date_setting_object_sub_details_id, date_use_object_sub_details_id, date_start_use_object_sub_description_id, date_start_use_object_description_id, date_end_use_object_sub_description_id, date_end_use_object_description_id,
							has_location, location_setting, location_ref_type_id, location_ref_type_id_locked, location_ref_object_sub_details_id, location_ref_object_sub_details_id_locked, location_use_object_sub_details_id, location_use_object_sub_description_id, location_use_object_description_id, location_use_object_id,
							sort
						)
							VALUES
						(".$this->type_id.",
							'".DBFunctions::strEscape($arr_object_sub_details_self['object_sub_details_name'])."',
							".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_is_single'], DBFunctions::TYPE_BOOLEAN).",
							".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN).",
							".(int)$clearance_edit.",
							".(int)$clearance_view.",
							".DBFunctions::escapeAs($object_sub_details_has_date, DBFunctions::TYPE_BOOLEAN).",
							".DBFunctions::escapeAs($object_sub_details_is_date_period, DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_date_setting.",
							".(int)$object_sub_details_date_setting_type_id.",
							".(int)$object_sub_details_date_setting_object_sub_details_id.",
							".(int)$object_sub_details_date_use_object_sub_details_id.",
							".(int)$object_sub_details_date_start_use_object_sub_description_id.",
							".(int)$object_sub_details_date_start_use_object_description_id.",
							".(int)$object_sub_details_date_end_use_object_sub_description_id.",
							".(int)$object_sub_details_date_end_use_object_description_id.",
							".DBFunctions::escapeAs($object_sub_details_has_location, DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_location_setting.",
							".(int)$object_sub_details_location_ref_type_id.",
							".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_location_ref_type_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_location_ref_object_sub_details_id.",
							".DBFunctions::escapeAs($arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_location_use_object_sub_details_id.",
							".(int)$object_sub_details_location_use_object_sub_description_id.",
							".(int)$object_sub_details_location_use_object_description_id.",
							".DBFunctions::escapeAs($object_sub_details_location_use_object_id, DBFunctions::TYPE_BOOLEAN).",
							".$num_sort."
						)
					");
					
					$object_sub_details_id = DB::lastInsertID();
				}
				
				if ($this->mode == self::MODE_OVERWRITE) {
										
					$arr_ids[] = $object_sub_details_id;
				}
				
				// Object sub descriptions

				$arr_sub_description_ids = [];
				
				if ($arr_object_sub_details['object_sub_descriptions']) {
					
					$num_sort_sub = 0;
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $arr_object_sub_description) {
						
						$arr_object_sub_description['object_sub_description_id'] = $this->getTypeObjectSubDescriptionID($this->type_id, $object_sub_details_id, $arr_object_sub_description['object_sub_description_id']);
						
						if (!$arr_object_sub_description['object_sub_description_name']) {
							
							if ($this->mode == self::MODE_UPDATE && $arr_object_sub_description['object_sub_description_id']) {
								$arr_sub_description_ids[] = $arr_object_sub_description['object_sub_description_id'];
							}

							continue;
						}
						
						$object_sub_description_value_type_base = $arr_object_sub_description['object_sub_description_value_type_base'];
						$object_sub_description_ref_type_id = $this->getTypeID($arr_object_sub_description['object_sub_description_ref_type_id']);
						
						if (in_array($object_sub_description_value_type_base, ['type', 'classification', 'reversal'])) {
							if (!$object_sub_description_ref_type_id) {
								$object_sub_description_value_type_base = '';
							}
						} else {
							$object_sub_description_ref_type_id = 0;
						}
						
						$object_sub_description_is_required = ($arr_object_sub_description['object_sub_description_is_required'] && !in_array($object_sub_description_value_type_base, ['reversal']) ? true : false);
						
						$object_sub_description_has_multi = (($arr_object_sub_description['object_sub_description_has_multi'] || $object_sub_description_value_type_base == 'reversal') ? true : false);
							
						$object_sub_description_use_object_description_id = ($object_sub_description_value_type_base == 'object_description' ? $arr_object_sub_description['object_sub_description_use_object_description_id'] : 0);
						
						$object_description_has_default_value = true;
						$object_sub_description_in_name = (bool)$arr_object_sub_description['object_sub_description_in_name'];
						
						$object_sub_description_value_type_settings = $this->parseTypeObjectDescriptionValueTypeOptions($object_sub_description_value_type_base, $object_sub_description_ref_type_id, false, $object_description_has_default_value, $object_sub_description_in_name, $arr_object_sub_description['object_sub_description_value_type_settings']);
						
						$clearance_view = ($arr_object_sub_description['object_sub_description_clearance_view'] !== null ? (int)$arr_object_sub_description['object_sub_description_clearance_view'] : null);
						$clearance_edit = ($arr_object_sub_description['object_sub_description_clearance_edit'] !== null ? (int)$arr_object_sub_description['object_sub_description_clearance_edit'] : null);
						$clearance_edit = ($clearance_view && $clearance_view > $clearance_edit ? $clearance_view : $clearance_edit);
						
						if ($arr_object_sub_description['object_sub_description_id']) {
							
							$arr_convert_objects = $this->getConvertTypeObjectSubDefinitions($object_sub_details_id, $arr_object_sub_description['object_sub_description_id'], $object_sub_description_value_type_base);
						
							$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." SET
									name = '".DBFunctions::strEscape($arr_object_sub_description['object_sub_description_name'])."',
									value_type_base = '".DBFunctions::strEscape($object_sub_description_value_type_base)."',
									value_type_settings = '".DBFunctions::strEscape($object_sub_description_value_type_settings)."',
									is_required = ".DBFunctions::escapeAs($object_sub_description_is_required, DBFunctions::TYPE_BOOLEAN).",
									use_object_description_id = ".(int)$object_sub_description_use_object_description_id.",
									ref_type_id = ".(int)$object_sub_description_ref_type_id.",
									in_name = ".DBFunctions::escapeAs($object_sub_description_in_name, DBFunctions::TYPE_BOOLEAN).",
									in_search = ".DBFunctions::escapeAs($arr_object_sub_description['object_sub_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
									in_overview = ".DBFunctions::escapeAs($arr_object_sub_description['object_sub_description_in_overview'], DBFunctions::TYPE_BOOLEAN)."
									".($clearance_view !== null ? 
										", clearance_edit = ".$clearance_edit."
										, clearance_view = ".$clearance_view
									: "")."
									".($this->mode == self::MODE_OVERWRITE ? ", sort = ".$num_sort_sub : "")."
								WHERE id = ".(int)$arr_object_sub_description['object_sub_description_id']."
									AND object_sub_details_id = ".$object_sub_details_id."
							");
							
							if ($this->mode == self::MODE_OVERWRITE) {
								
								$arr_sub_description_ids[] = $arr_object_sub_description['object_sub_description_id'];
							}
							
							if ($arr_convert_objects) {
						
								timeLimit(300);
								self::$arr_types_storage[$this->type_id] = false; // Reload type set on next call
								
								$storage = new StoreTypeObjects($this->type_id, false, $this->user_id);
						
								foreach ($arr_convert_objects as $object_id => $arr_object) {
																		
									$storage->setObjectID($object_id);
									$storage->store([], [], $arr_object['object_subs']);
								}
								
								$storage->commit(true);
							}
						} else {
							
							$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')."
								(object_sub_details_id, name, value_type_base, value_type_settings, is_required, use_object_description_id, ref_type_id, in_name, in_search, in_overview, clearance_edit, clearance_view, sort)
									VALUES
								(".$object_sub_details_id.",
									'".DBFunctions::strEscape($arr_object_sub_description['object_sub_description_name'])."',
									'".DBFunctions::strEscape($object_sub_description_value_type_base)."',
									'".DBFunctions::strEscape($object_sub_description_value_type_settings)."',
									".DBFunctions::escapeAs($object_sub_description_is_required, DBFunctions::TYPE_BOOLEAN).",
									".(int)$object_sub_description_use_object_description_id.",
									".(int)$object_sub_description_ref_type_id.",
									".DBFunctions::escapeAs($arr_object_sub_description['object_sub_description_in_name'], DBFunctions::TYPE_BOOLEAN).",
									".DBFunctions::escapeAs($arr_object_sub_description['object_sub_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
									".DBFunctions::escapeAs($arr_object_sub_description['object_sub_description_in_overview'], DBFunctions::TYPE_BOOLEAN).",
									".(int)$clearance_edit.",
									".(int)$clearance_view.",
									".$num_sort_sub."
								)
							");
							
							if ($this->mode == self::MODE_OVERWRITE) {
								
								$arr_sub_description_ids[] = DB::lastInsertID();
							}
						}
						
						$num_sort_sub++;
					}
				}
				
				$sql_select = ($this->mode == self::MODE_UPDATE ? ($arr_sub_description_ids ? "id IN (".implode(",", $arr_sub_description_ids).")" : "") : ($arr_sub_description_ids ? "id NOT IN (".implode(",", $arr_sub_description_ids).")" : "TRUE"));
				
				if ($sql_select) {
					
					$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')."
						WHERE object_sub_details_id = ".$object_sub_details_id."
							AND ".$sql_select."
					");
				}
				
				$num_sort++;
			}
		}
		
		if ($this->mode == self::MODE_UPDATE) {
			$sql_select = ($arr_ids ? "IN (".implode(',', $arr_ids).")" : '');
		} else {
			$sql_select = ($arr_ids ? "NOT IN (".implode(',', $arr_ids).")" : '');
		}
		
		if ($this->mode == self::MODE_OVERWRITE || ($this->mode == self::MODE_UPDATE && $sql_select)) {

			$res = DB::queryMulti("
				".DBFunctions::deleteWith(
					DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'nodegoat_tos_des', 'object_sub_details_id',
					"JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (
						nodegoat_tos_det.id = nodegoat_tos_des.object_sub_details_id
							AND nodegoat_tos_det.type_id = ".$this->type_id."
							".($sql_select ? "AND nodegoat_tos_det.id ".$sql_select : "")."
					)"
				)."
				;
				DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')."
					WHERE type_id = ".$this->type_id."
						".($sql_select ? "AND id ".$sql_select : "")."
				;
			");
		}
		
		// Caching & processing
		
		if ($arr_touched_cache_object_sub_details_ids) { // Update the state of the objects when there could be cache-related changes to their sub-objects
			
			$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);
			$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
			$filter->setFilter(['object_sub_details' => $arr_touched_cache_object_sub_details_ids]);
			
			$sql_table_name = $filter->storeResultTemporarily();
			
			StoreTypeObjects::touchTypeObjects($this->type_id, $sql_table_name);
		}
		
		self::$arr_types_storage[$this->type_id] = false; // Invalidate Type configuration cache
		
		self::$arr_types_touched[$this->type_id] = $this->type_id;

		return $this->type_id;
	}
	
	public function checkStore($arr_details, $arr_object_descriptions, $arr_object_subs_details) {
		
		$num_type_class = (int)(!$this->type_id ? $arr_details['class'] : $this->arr_type_set['type']['class']);
		$num_type_mode = $this->parseTypeMode($arr_details['mode']);
		
		if ($num_type_class != self::TYPE_CLASS_REVERSAL && $this->mode == self::MODE_OVERWRITE) {

			$has_name = false;
			
			foreach ($arr_object_descriptions as $key => $arr_object_description) {
				
				if (!$arr_object_description['object_description_name']) {
					continue;
				}
				
				$arr_object_description['object_description_ref_type_id'] = (in_array($arr_object_description['object_description_value_type_base'], ['type', 'classification', 'reversal']) ? $arr_object_description['object_description_ref_type_id'] : 0);
				
				/*if ($this->type_id && $arr_object_description['object_description_ref_type_id'] == $this->type_id) { // Possible recursion in the name or search
					if ($arr_object_description['object_description_in_name']) {
						error(getLabel('msg_model_recursion_in_name'));
					}
					if ($arr_object_description['object_description_in_search']) {
						error(getLabel('msg_model_recursion_in_search'));
					}
				}*/
				
				if ($arr_object_description['object_description_in_name'] && !($this->type_id && $arr_object_description['object_description_ref_type_id'] == $this->type_id)) { // Self-reference does not count
					$has_name = true;
				}
			}
			
			if (!$arr_details['use_object_name'] && !$has_name) {
				
				error(getLabel('msg_model_no_object_name'));
			}
		}
		
		if ($this->arr_confirm) {
			
			error(arr2String($this->arr_confirm, EOL_1100CC.EOL_1100CC));
		}
	}
	
	public function getStoreConfirm() {
		
		return $this->arr_confirm;
	}
	
	public function parseTypeMode($arr_mode) {
		
		if ($arr_mode === null) {
			return null;
		}	
		
		$num_type_mode = 0;
			
		if (!is_array($arr_mode)) {
			
			$num_type_mode = (int)$arr_mode;
		} else {
			
		}

		return $num_type_mode;
	}
	
	public function updateTypeMode($num_mode) {
		
		$res = DB::query("
			UPDATE ".DB::getTable('DEF_NODEGOAT_TYPES')." SET
					mode = ".(int)$num_mode."
				WHERE id = ".$this->type_id."
					"."
		");
	}
		
	protected function parseTypeObjectDescriptionValueTypeOptions($value_type_base, $ref_type_id, $has_multi, $has_default_value, $in_name, $arr_value_type_settings) {
		
		$str_value = '';
			
		if ($arr_value_type_settings) {
			
			$arr_value = [];
			
			if (!is_array($arr_value_type_settings)) {
				$arr_value_type_settings = json_decode($arr_value_type_settings, true);
			}
			
			switch ($value_type_base) {
				case 'external':
					
					if ($arr_value_type_settings['id']) {
						$arr_value['id'] = (int)$arr_value_type_settings['id'];
					}	
					break;
				case 'text_tags':
				
					if ($arr_value_type_settings['marginalia']) {
						$arr_value['marginalia'] = true;
					}
					break;
				case 'media':
				case 'media_external':
				
					if ($arr_value_type_settings['display']) {
						$arr_value['display'] = $arr_value_type_settings['display'];
					}
					break;
				case 'module':
				
					if ($arr_value_type_settings['type']) {
						$arr_value['type'] = $arr_value_type_settings['type'];
					}
					break;
			}
			
			$value_default = ($arr_value_type_settings['default']['value'] ?? null);
			
			if ($has_default_value && $value_default) {
					
				if ($ref_type_id) {
					
					$value_default = arrParseRecursive($value_default, TYPE_INTEGER);
				} else {

					$default_value = StoreTypeObjects::formatToSQLValue($value_type_base, $value_default);
					
					if ($value_default === false || $value_default === '') {
						$value_default = null;
					}
				}
				
				if ($has_multi) {
					$value_default = array_unique(array_filter((array)$value_default));
				}
				
				$arr_value['default']['value'] = $value_default;
			}
			
			if ($has_multi) {
					
				if ($arr_value_type_settings['separator']) {
					$arr_value['separator'] = $arr_value_type_settings['separator'];
				}
				
				if ($in_name && $arr_value_type_settings['name']['separator']) {
					$arr_value['name']['separator'] = $arr_value_type_settings['name']['separator'];
				}
			}
			
			if ($arr_value) {
				$str_value = value2JSON($arr_value);
			}
		}
		
		return $str_value;
	}
	
	public function getConvertTypeObjectDefinitions($object_description_id, $to_value_type_base) {
		
		$value_type_base = $this->arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type_base'];
		
		if ($to_value_type_base == $value_type_base) {
			return;
		}
		
		$do_convert = false;
		
		switch ($to_value_type_base) {
			case 'media';
				if ($value_type_base == '') {
					$do_convert = true;
				}
				break;
			case 'text';
			case 'text_layout';
			case 'text_tags';
				if ($value_type_base == '' || $value_type_base == 'int' || $value_type_base == 'numeric') {
					$do_convert = true;
				}
				break;
			case '';
				if ($value_type_base == 'text' || $value_type_base == 'text_layout' || $value_type_base == 'text_tags' || $value_type_base == 'int' || $value_type_base == 'numeric') {
					$do_convert = true;
				}
				break;
			case 'int';
			case 'numeric';
				if ($value_type_base == 'int' || $value_type_base == 'numeric') {
					$do_convert = true;
				}
				break;
		}
		
		if (!$do_convert) {
			return;
		}
	
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ALL);
		$filter->setSelection(['object' => [], 'object_sub_details' => [], 'object_descriptions' => [$object_description_id => $object_description_id]]);
		$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
		$filter->setFilter(['object_filter' => [
			'object_definitions' => [
				$object_description_id => ['transcension' => ['value' => 'not_empty']]
			]
		]]);
		
		$arr_objects = $filter->init();
				
		return $arr_objects;
	}
	
	public function getConvertTypeObjectSubDefinitions($object_sub_details_id, $object_sub_description_id, $to_value_type_base) {
		
		$value_type_base = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_value_type_base'];
		
		if ($to_value_type_base == $value_type_base) {
			return;
		}
		
		$do_convert = false;
		
		switch ($to_value_type_base) {
			case 'media';
				if ($value_type_base == '') {
					$do_convert = true;
				}
				break;
		}
		
		if (!$do_convert) {
			return;
		}
	
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ALL);
		$filter->setSelection(['object' => [], 'object_sub_details' => [$object_sub_details_id => ['object_sub_details' => [], 'object_sub_descriptions' => [$object_sub_description_id => $object_sub_description_id]]], 'object_descriptions' => []]);
		$filter->setVersioning(GenerateTypeObjects::VERSIONING_ADDED);
		$filter->setFilter(['object_filter' => [
			'object_subs' => [
				$object_sub_details_id => [
					'object_sub_definitions' => [
						$object_sub_description_id => ['transcension' => ['value' => 'not_empty']]
					]
				]
			]
		]]);
		
		$arr_objects = $filter->init();
				
		return $arr_objects;
	}
	
	public static function getTypeObjectDescriptionSerialNext($type_id, $object_description_id) {
		
		$num_serial = false;
		
		if (DB::ENGINE_IS_MYSQL) {
			
			$arr_res = DB::queryMulti("				
				UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." SET
					value_type_serial = (@num_serial := COALESCE(value_type_serial, 0) + 1)
				WHERE id = ".(int)$object_description_id."
					AND type_id = ".(int)$type_id."
				;
					
				SELECT @num_serial;
			");
			
			$num_serial = $arr_res[1]->fetchRow();
			$num_serial = $num_serial[0];
		} else {
			
			$res = DB::query("
				UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." SET
					value_type_serial = COALESCE(value_type_serial, 0) + 1
				WHERE id = ".(int)$object_description_id."
					AND type_id = ".(int)$type_id."
				RETURNING value_type_serial
			");
			
			$num_serial = $res->fetchRow();
			$num_serial = $num_serial[0];
		}
		
		
		return $num_serial;
	}
	
	public static function getTypeObjectSubDescriptionSerialNext($type_id, $object_sub_details_id, $object_sub_description_id) {
		
		$num_serial = false;
		
		if (DB::ENGINE_IS_MYSQL) {
			
			$arr_res = DB::queryMulti("
				UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." SET
					value_type_serial = (@num_serial := COALESCE(value_type_serial, 0) + 1)
				WHERE id = ".(int)$object_sub_description_id."
					AND object_sub_details_id = ".(int)$object_sub_details_id."
				;
					
				SELECT @num_serial;
			");
			
			$num_serial = $arr_res[1]->fetchRow();
			$num_serial = $num_serial[0];
		} else {
			
			$res = DB::query("
				UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." SET
					value_type_serial = COALESCE(value_type_serial, 0) + 1
				WHERE id = ".(int)$object_sub_description_id."
					AND object_sub_details_id = ".(int)$object_sub_details_id."
				RETURNING value_type_serial
			");
			
			$num_serial = $res->fetchRow();
			$num_serial = $num_serial[0];
		}
		
		
		return $num_serial;
	}

	public static function setTypesObjectPaths() {
		
		if (!self::$arr_types_touched) {
			return;
		}
		
		self::setTypesObjectPath(self::$arr_types_touched, 'name');
		self::setTypesObjectPath(self::$arr_types_touched, 'search');
		
		self::$arr_types_touched = [];
	}
	
	public static function setTypesObjectPath($arr_type_ids, $path) {
		
		$arr_type_ids = (array)$arr_type_ids;
	
		$cur_type_id = 0;
		$num_sort = 0;
	
		$func_insert_object_description_name_ids = function($ref_type_id, $org_object_description_id, $org_object_sub_details_id, $arr_path_types) use (&$cur_type_id, &$num_sort, &$func_insert_object_description_name_ids, $path) {
			
			$arr_path_types[$ref_type_id]++; // Prevent recursion

			$res = DB::query("SELECT nodegoat_t.id
					FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
				WHERE id = ".$ref_type_id." AND use_object_name = TRUE
			");
			
			if ($res->getRowCount()) {
				
				// Store object's own name
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_'.($path == 'name' ? 'NAME' : 'SEARCH').'_PATH')."
					(type_id, ref_type_id, ref_object_description_id, ref_object_sub_details_id, org_object_description_id, org_object_sub_details_id, is_reference, sort)
						VALUES
					(
						".$cur_type_id.",
						".$ref_type_id.",
						0,
						0,
						".$org_object_description_id.",
						".$org_object_sub_details_id.",
						FALSE,
						".$num_sort."
					)
				");
				
				$num_sort++;
			}
			
			$res = DB::query("SELECT nodegoat_to_des.*
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
				WHERE nodegoat_to_des.type_id = ".$ref_type_id."
					AND nodegoat_to_des.in_".($path == 'name' ? 'name' : 'search')." = TRUE
				ORDER BY nodegoat_to_des.type_id ASC, nodegoat_to_des.sort ASC
			");
			
			while ($row = $res->fetchAssoc()) {
							
				if ($row['ref_type_id']) {
					
					if ($arr_path_types[$row['ref_type_id']] > 1) { // Prevent multi-recursion
					//if ($arr_path_types[$row['ref_type_id']]) { // Prevent recursion
						continue;
					}
					
					$is_reference = "TRUE"; // Store description when only link
				} else {
					
					$is_reference = "FALSE"; // Store description as name reference
				}
					
				$res_insert = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_'.($path == 'name' ? 'NAME' : 'SEARCH').'_PATH')."
					(type_id, ref_type_id, ref_object_description_id, ref_object_sub_details_id, org_object_description_id, org_object_sub_details_id, is_reference, sort)
						VALUES
					(
						".$cur_type_id.",
						".$row['type_id'].",
						".$row['id'].",
						0,
						".$org_object_description_id.",
						".$org_object_sub_details_id.",
						".$is_reference.",
						".$num_sort."
					)
				");
					
				$num_sort++;
					
				if ($row['ref_type_id']) {
					
					$func_insert_object_description_name_ids($row['ref_type_id'], $row['id'], 0, $arr_path_types);
				}
			}
			
			$res = DB::query("SELECT nodegoat_to_det.type_id, nodegoat_tos_des.*
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_to_det
					JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_to_det.id)
				WHERE nodegoat_to_det.type_id = ".$ref_type_id."
					AND nodegoat_tos_des.in_".($path == 'name' ? 'name' : 'search')." = TRUE
				ORDER BY nodegoat_to_det.type_id ASC, nodegoat_tos_des.sort ASC
			");
			
			while ($row = $res->fetchAssoc()) {
							
				if ($row['ref_type_id']) {
					
					if ($arr_path_types[$row['ref_type_id']] > 1) { // Prevent multi-recursion
					//if ($arr_path_types[$row['ref_type_id']]) { // Prevent recursion
						continue;
					}
					
					$is_reference = "TRUE"; // Store description when only link
				} else {
					
					$is_reference = "FALSE"; // Store description as name reference
				}

				// Store description when only link
				$res_insert = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_'.($path == 'name' ? 'NAME' : 'SEARCH').'_PATH')."
					(type_id, ref_type_id, ref_object_description_id, ref_object_sub_details_id, org_object_description_id, org_object_sub_details_id, is_reference, sort)
						VALUES
					(
						".$cur_type_id.",
						".$row['type_id'].",
						".$row['id'].",
						".$row['object_sub_details_id'].",
						".$org_object_description_id.",
						".$org_object_sub_details_id.",
						".$is_reference.",
						".$num_sort."
					)
				");
				
				$num_sort++;
				
				if ($row['ref_type_id']) {
					
					$func_insert_object_description_name_ids($row['ref_type_id'], $row['id'], $row['object_sub_details_id'], $arr_path_types);
				}
			}
		};
		
		$arr_collect_types_ids = [];
		
		foreach ($arr_type_ids as $type_id) {
				
			$res = DB::query("SELECT DISTINCT type_id
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_'.($path == 'name' ? 'NAME' : 'SEARCH').'_PATH')."
				WHERE ref_type_id = ".$type_id."
					AND type_id != ".$type_id
			);
									
			$arr_collect_types_ids[$type_id] = $type_id;
			
			while ($arr_row = $res->fetchRow()) {
			
				$arr_collect_types_ids[$arr_row[0]] = $arr_row[0];
			}
		}

		foreach ($arr_collect_types_ids as $cur_type_id) {
			
			$res_delete = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_'.($path == 'name' ? 'NAME' : 'SEARCH').'_PATH')."
				WHERE type_id = ".(int)$cur_type_id
			);

			$arr_path_types = [];
			$num_sort = 0;
			
			$func_insert_object_description_name_ids($cur_type_id, 0, 0, $arr_path_types);
		}
	}
	
	public function delType() {

		if (!$this->type_id) {
			return;
		}
		
		$storage = new StoreTypeObjects($this->type_id, false, $this->user_id);
		$storage->clearTypeObjects();
		
		DB::queryMulti("
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SEARCH_PATH')." WHERE type_id = ".$this->type_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_NAME_PATH')." WHERE type_id = ".$this->type_id.";
			
			".DBFunctions::deleteWith(
				DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS'), 'nodegoat_tos_des', 'object_sub_details_id',
				"JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (
					nodegoat_tos_det.id = nodegoat_tos_des.object_sub_details_id
					AND nodegoat_tos_det.type_id = ".$this->type_id."
				)"
			)."
			;
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." WHERE type_id = ".$this->type_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." WHERE type_id = ".$this->type_id.";
			
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')." WHERE type_id = ".$this->type_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = ".$this->type_id.";
		");
	}
	
	public function getTypeID($value) {
		
		if (!$value) {
			return false;
		}
		
		$is_name = (is_numeric($value) ? false : true);
		
		if (!$this->arr_types_all) {
			
			$this->arr_types_all = self::getTypes();
		}
				
		$type_id = false;
		
		if ($is_name) {
			
			foreach ($this->arr_types_all as $arr_type) {
				
				if ($arr_type['name'] != $value) {
					continue;
				}
				
				$type_id = $arr_type['id'];
				break;
			}
			
			if (!$type_id) {
				$this->has_unresolved_ids = true;
			}
		} else {
			
			$type_id = $this->arr_types_all[$value]['id'];
		}			
		
		return $type_id;
	}
	
	public function getTypeObjectDescriptionID($type_id, $value) {
		
		if (!$value) {
			return false;
		}
		
		$type_id = $this->getTypeID($type_id);
		$arr_type_set = self::getTypeSet($type_id);
		
		$is_name = (is_numeric($value) ? false : true);
		
		$object_description_id = false;
	
		if ($is_name) {
			
			if ($arr_type_set) {
				
				foreach ($arr_type_set['object_descriptions'] as $arr_object_description) {
					
					if ($arr_object_description['object_description_name'] != $value) {
						continue;
					}
					
					$object_description_id = $arr_object_description['object_description_id'];
					break;
				}
			}		
			
			if (!$object_description_id) {
				$this->has_unresolved_ids = true;
			}
		} else {
			
			$object_description_id = $arr_type_set['object_descriptions'][$value]['object_description_id'];
		}
		
		return $object_description_id;
	}
	
	public function getTypeObjectSubDetailsID($type_id, $value) {
		
		if (!$value) {
			return false;
		}
		
		$type_id = $this->getTypeID($type_id);
		$arr_type_set = self::getTypeSet($type_id);
		
		$is_name = (is_numeric($value) ? false : true);
		
		$object_sub_details_id = false;
	
		if ($is_name) {
			
			if ($arr_type_set) {
					
				foreach ($arr_type_set['object_sub_details'] as $arr_object_sub_details) {
					
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_name'] != $value) {
						continue;
					}
					
					$object_sub_details_id = $arr_object_sub_details['object_sub_details']['object_sub_details_id'];
					break;
				}
			}
			
			if (!$object_sub_details_id) {
				$this->has_unresolved_ids = true;
			}
		} else {
			
			$object_sub_details_id = $arr_type_set['object_sub_details'][$value]['object_sub_details']['object_sub_details_id'];
		}
		
		return $object_sub_details_id;
	}
	
	public function getTypeObjectSubDescriptionID($type_id, $object_sub_details_id, $value) {
		
		if (!$value) {
			return false;
		}
		
		$type_id = $this->getTypeID($type_id);
		$arr_type_set = self::getTypeSet($type_id);
		
		$object_sub_details_id = $this->getTypeObjectSubDetailsID($type_id, $object_sub_details_id);
		
		$is_name = (is_numeric($value) ? false : true);
		
		$object_sub_description_id = false;
	
		if ($is_name) {
			
			if ($arr_type_set['object_sub_details'][$object_sub_details_id]) {
					
				foreach ($arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] as $arr_object_sub_description) {
					
					if ($arr_object_sub_description['object_sub_description_name'] != $value) {
						continue;
					}
					
					$object_sub_description_id = $arr_object_sub_description['object_sub_description_id'];
					break;
				}
			}	
			
			if (!$object_sub_description_id) {
				$this->has_unresolved_ids = true;
			}
		} else {
			
			$object_sub_description_id = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$value]['object_sub_description_id'];
		}
		
		return $object_sub_description_id;
	}
	
	public function getTypeName($type_id) {
		
		if (!$type_id) {
			return false;
		}
				
		if (!$this->arr_types_all) {
			
			$this->arr_types_all = self::getTypes();
		}
				
		$value = $this->arr_types_all[$type_id]['name'];
				
		return $value;
	}
	
	public function getTypeObjectDescriptionName($type_id, $object_description_id) {
		
		if (!$object_description_id) {
			return false;
		}
		
		$arr_type_set = self::getTypeSet($type_id);
		
		$value = $arr_type_set['object_descriptions'][$object_description_id]['object_description_name'];

		return $value;
	}
	
	public function getTypeObjectSubDetailsName($type_id, $object_sub_details_id) {
		
		if (!$object_sub_details_id) {
			return false;
		}
		
		$arr_type_set = self::getTypeSet($type_id);
		
		$value = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_name'];

		return $value;
	}
	
	public function getTypeObjectSubDescriptionName($type_id, $object_sub_details_id, $object_sub_description_id) {
		
		if (!$object_sub_description_id) {
			return false;
		}
		
		$arr_type_set = self::getTypeSet($type_id);
		
		$value = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_name'];
		
		return $value;
	}
	
	public function hasUnresolvedIDs() {
		
		return $this->has_unresolved_ids;
	}
	
	public function getTypeMode($type_id) {
		
		if (!$type_id) {
			return false;
		}
				
		if (!$this->arr_types_all) {
			
			$this->arr_types_all = self::getTypes();
		}
				
		$value = $this->arr_types_all[$type_id]['mode'];
				
		return $value;
	}
	
	public function getReferencingTypesMode($arr_object_descriptions, $arr_object_subs_details) {
		
		$arr_type_ids = arrMergeValues(arrValuesRecursive('object_description_ref_type_id', $arr_object_descriptions), arrValuesRecursive(['object_sub_details_location_ref_type_id' => true, 'object_sub_description_ref_type_id' => true], $arr_object_subs_details));
		
		$arr_types_mode = [];
		$arr_types_mode[$this->type_id] = false; // Do not trace self
		
		$trace = new TraceTypesNetwork([], false, true);
		
		foreach ($arr_type_ids as $referencing_type_id) {
			
			if (isset($arr_types_mode[$referencing_type_id])) {
				continue;
			}
						
			$arr_types_mode[$referencing_type_id] = $this->getTypeMode($referencing_type_id);

			$trace->run($referencing_type_id, false, 100, TraceTypesNetwork::RUN_MODE_REFERENCING | TraceTypesNetwork::RUN_MODE_SHORTEST);

			$arr_type_network_paths = $trace->getTypeNetworkPaths();
			$arr_referencing_type_ids = arrValuesRecursive('ref_type_id', ($arr_type_network_paths['connections'][$referencing_type_id] ?? []));
			
			foreach ($arr_referencing_type_ids as $referencing_type_id) {
				
				if (isset($arr_types_mode[$referencing_type_id])) {
					continue;
				}

				$arr_types_mode[$referencing_type_id] = $this->getTypeMode($referencing_type_id);
			}
		}
		
		unset($arr_types_mode[$this->type_id]);
		
		return $arr_types_mode;
	}
	
	public function getReferencedTypesMode() {
		
		$arr_types_mode = [];
		
		$trace = new TraceTypesNetwork([], false, true);
		$trace->run($this->type_id, false, 100, TraceTypesNetwork::RUN_MODE_REFERENCED | TraceTypesNetwork::RUN_MODE_SHORTEST);

		$arr_type_network_paths = $trace->getTypeNetworkPaths();
		$arr_referenced_type_ids = arrValuesRecursive('type_id', ($arr_type_network_paths['connections'][$this->type_id] ?? []));
		
		foreach ($arr_referenced_type_ids as $referenced_type_id) {
			
			if (isset($arr_types_mode[$referenced_type_id])) {
				continue;
			}

			$arr_types_mode[$referenced_type_id] = $this->getTypeMode($referenced_type_id);
		}
		
		return $arr_types_mode;
	}
	
	// Static calls
	
	public static function getTypes($type_id = false, $class = false) {
	
		$arr = [];
		
		$sql_type_ids = false;
		
		if ($type_id) {
			
			if (is_array($type_id)) {
				$arr_type_ids = $type_id;
				$sql_type_ids = implode(',', $arr_type_ids);
			} else {
				$arr_type_ids = [(int)$type_id];
				$sql_type_ids = (int)$type_id;
			}
		}

		$res = DB::query("SELECT nodegoat_t.*
				FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
			WHERE TRUE
				"."
				".($class !== false ? "AND class = ".(int)$class : "")."
				".($sql_type_ids ? "
						AND nodegoat_t.id IN (".$sql_type_ids.")
					ORDER BY ".DBFunctions::fieldToPosition('id', $arr_type_ids)
				: "
					ORDER BY nodegoat_t.name ASC
				")."
		");
								 
		while ($arr_row = $res->fetchAssoc()) {
			
			if (Settings::get('domain_administrator_mode')) {
				
				$arr_row['name_raw'] = $arr_row['name'];
				if ($arr_row['label']) {
					$arr_row['name'] = $arr_row['label'].' '.$arr_row['name'];
				}
			}
				
			$arr[$arr_row['id']] = $arr_row;
		}
		
		if ($class === false || $class == self::TYPE_CLASS_SYSTEM) {
			
			if ($type_id) {
				
				foreach ($arr_type_ids as $cur_type_id) {
					
					if ($cur_type_id < 0) {
						
						$arr_type = self::getSystemTypes($cur_type_id);
						
						if ($arr_type) {
							$arr[$cur_type_id] = $arr_type;
						}
					}
				}
			} else {
				
				$arr += self::getSystemTypes();
			}
		}
		
		return ($type_id && is_numeric($type_id) ? current($arr) : $arr);
	}
	
	public static function getSystemTypes($type_id = false) {
			
		$arr = [
			-1 => [
				'id' => -1,
				'name' => getLabel('lbl_date_cycle'),
				'class' => static::TYPE_CLASS_SYSTEM,
				'mode' => static::TYPE_MODE_DEFAULT,
				'use_object_name' => true,
				'object_name_in_overview' => true
			],
			-2 => [
				'id' => -2,
				'name' => getLabel('lbl_system_ingestion'),
				'class' => static::TYPE_CLASS_SYSTEM,
				'mode' => static::TYPE_MODE_DEFAULT,
				'use_object_name' => true,
				'object_name_in_overview' => true
			],
			-3 => [
				'id' => -3,
				'name' => getLabel('lbl_system_reconciliation'),
				'class' => static::TYPE_CLASS_SYSTEM,
				'mode' => static::TYPE_MODE_DEFAULT,
				'use_object_name' => true,
				'object_name_in_overview' => true
			]
		];
		
		if ($type_id && $type_id == -4) { // Do not list, only return when requested
			
			$arr[-4] = [ // Reversal prototype
				'id' => -4,
				'name' => getLabel('lbl_reversal'),
				'class' => static::TYPE_CLASS_SYSTEM,
				'mode' => static::TYPE_MODE_DEFAULT,
				'use_object_name' => true,
				'object_name_in_overview' => true
			];
		}
		
		return ($type_id && is_numeric($type_id) ? $arr[$type_id] : $arr);
	}
	
	public static function getTypeSet($type_id) {

		$cache = (self::$arr_types_storage[$type_id] ?? null);
		if ($cache) {
			return $cache;
		}
		
		if ($type_id < 0) { // System Type
			return self::getSystemTypeSet($type_id);
		}
	
		$arr = ['definitions' => [], 'object_descriptions' => [], 'object_sub_details' => []];
		
		$arr_res = DB::queryMulti("
			SELECT nodegoat_t.*,
					nodegoat_t_des.id AS definition_id, nodegoat_t_des.name AS definition_name, nodegoat_t_des.text AS definition_text,
					nodegoat_to_des.id AS object_description_id, nodegoat_to_des.id_id AS object_description_id_id, nodegoat_to_des.name AS object_description_name,
						nodegoat_to_des.value_type_base AS object_description_value_type_base,
						nodegoat_to_des.value_type_settings AS object_description_value_type_settings,
						CASE
							WHEN nodegoat_to_des.value_type_base = 'reversal' THEN
								CASE
									WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN 'reversed_collection'
									ELSE 'reversed_classification'
								END
							WHEN nodegoat_to_des.value_type_base != '' THEN nodegoat_to_des.value_type_base
							WHEN nodegoat_to_des.id_id = ".static::getSystemTypeObjectDescriptionID(static::getSystemTypeID('reversal'), 'reference')." THEN 'reference'
							ELSE ''
						END AS object_description_value_type,
						CASE 
							WHEN nodegoat_to_des.value_type_base = 'text_tags' THEN TRUE
							WHEN nodegoat_to_des.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN TRUE
							ELSE FALSE
						END AS object_description_is_dynamic,
						nodegoat_to_des.is_required AS object_description_is_required, nodegoat_to_des.is_unique AS object_description_is_unique, nodegoat_to_des.has_multi AS object_description_has_multi, nodegoat_to_des.ref_type_id AS object_description_ref_type_id, nodegoat_to_des.in_name AS object_description_in_name, nodegoat_to_des.in_search AS object_description_in_search, nodegoat_to_des.in_overview AS object_description_in_overview, nodegoat_to_des.is_identifier AS object_description_is_identifier, nodegoat_to_des.clearance_edit AS object_description_clearance_edit, nodegoat_to_des.clearance_view AS object_description_clearance_view
				FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')." nodegoat_t_des ON (nodegoat_t_des.type_id = nodegoat_t.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des ON (nodegoat_to_des.type_id = nodegoat_t.id)
				WHERE nodegoat_t.id = ".(int)$type_id."
				ORDER BY nodegoat_t_des.sort ASC, nodegoat_to_des.sort ASC;
					
			SELECT nodegoat_t.id,
					nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_det.name AS object_sub_details_name, nodegoat_tos_det.is_single AS object_sub_details_is_single, nodegoat_tos_det.is_required AS object_sub_details_is_required,
						nodegoat_tos_det.clearance_edit AS object_sub_details_clearance_edit, nodegoat_tos_det.clearance_view AS object_sub_details_clearance_view,
						nodegoat_tos_det.has_date AS object_sub_details_has_date, nodegoat_tos_det.is_date_period AS object_sub_details_is_date_period,
							nodegoat_tos_det.date_setting AS object_sub_details_date_setting, nodegoat_tos_det.date_setting_type_id AS object_sub_details_date_setting_type_id, nodegoat_tos_det.date_setting_object_sub_details_id AS object_sub_details_date_setting_object_sub_details_id,
							nodegoat_tos_det.date_use_object_sub_details_id AS object_sub_details_date_use_object_sub_details_id, nodegoat_tos_det.date_start_use_object_sub_description_id AS object_sub_details_date_start_use_object_sub_description_id, nodegoat_tos_det.date_start_use_object_description_id AS object_sub_details_date_start_use_object_description_id, nodegoat_tos_det.date_end_use_object_sub_description_id AS object_sub_details_date_end_use_object_sub_description_id, nodegoat_tos_det.date_end_use_object_description_id AS object_sub_details_date_end_use_object_description_id,
						nodegoat_tos_det.has_location AS object_sub_details_has_location,
							nodegoat_tos_det.location_setting AS object_sub_details_location_setting, nodegoat_tos_det.location_ref_type_id AS object_sub_details_location_ref_type_id, nodegoat_tos_det.location_ref_type_id_locked AS object_sub_details_location_ref_type_id_locked, nodegoat_tos_det.location_ref_object_sub_details_id AS object_sub_details_location_ref_object_sub_details_id, nodegoat_tos_det.location_ref_object_sub_details_id_locked AS object_sub_details_location_ref_object_sub_details_id_locked,
								nodegoat_tos_det.location_use_object_sub_details_id AS object_sub_details_location_use_object_sub_details_id, nodegoat_tos_det.location_use_object_sub_description_id AS object_sub_details_location_use_object_sub_description_id, nodegoat_tos_det.location_use_object_description_id AS object_sub_details_location_use_object_description_id, nodegoat_tos_det.location_use_object_id AS object_sub_details_location_use_object_id,
						nodegoat_tos_det.sort AS object_sub_details_sort,
					nodegoat_tos_des.id AS object_sub_description_id, nodegoat_tos_des.name AS object_sub_description_name,
					nodegoat_tos_des.value_type_base AS object_sub_description_value_type_base,
					nodegoat_tos_des.value_type_settings AS object_sub_description_value_type_settings,
					CASE
						WHEN nodegoat_tos_des.value_type_base = 'reversal' THEN
							CASE
								WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN 'reversed_collection'
								ELSE 'reversed_classification'
							END
						ELSE nodegoat_tos_des.value_type_base
					END AS object_sub_description_value_type,
					CASE 
						WHEN nodegoat_tos_des.value_type_base = 'text_tags' THEN TRUE
						WHEN nodegoat_tos_des.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN TRUE
						ELSE FALSE
					END AS object_sub_description_is_dynamic,
					nodegoat_tos_des.is_required AS object_sub_description_is_required, nodegoat_tos_des.use_object_description_id AS object_sub_description_use_object_description_id, nodegoat_tos_des.ref_type_id AS object_sub_description_ref_type_id, nodegoat_tos_des.in_name AS object_sub_description_in_name, nodegoat_tos_des.in_search AS object_sub_description_in_search, nodegoat_tos_des.in_overview AS object_sub_description_in_overview, nodegoat_tos_des.clearance_edit AS object_sub_description_clearance_edit, nodegoat_tos_des.clearance_view AS object_sub_description_clearance_view
				FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.type_id = nodegoat_t.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_tos_det.id)
				WHERE nodegoat_t.id = ".(int)$type_id."
				ORDER BY nodegoat_tos_det.sort ASC, nodegoat_tos_des.sort ASC;
		");
		
		while ($arr_row = $arr_res[0]->fetchAssoc()) {
			
			if (!$arr['type']) {
				
				$arr_row['class'] = (int)$arr_row['class'];
				$arr_row['mode'] = (int)$arr_row['mode'];
				
				if (Settings::get('domain_administrator_mode')) {
					
					$arr_row['name_raw'] = $arr_row['name'];
					if ($arr_row['label']) {
						$arr_row['name'] = $arr_row['label'].' '.$arr_row['name'];
					}
				}
				
				$arr_row['use_object_name'] = DBFunctions::unescapeAs($arr_row['use_object_name'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_name_in_overview'] = DBFunctions::unescapeAs($arr_row['object_name_in_overview'], DBFunctions::TYPE_BOOLEAN);

				$arr['type'] = $arr_row;
			}
			
			if ($arr_row['definition_id']) {
				$arr['definitions'][$arr_row['definition_id']] = $arr_row;
			}

			if ($arr_row['object_description_id']) {
				
				if ($arr_row['class'] == static::TYPE_CLASS_REVERSAL) {
					
					if ($arr_row['mode'] & static::TYPE_MODE_REVERSAL_CLASSIFICATION) {
						
						$system_type_id = static::getSystemTypeID('reversal');
						$arr_system_type_set = static::getSystemTypeSet($system_type_id);
						
						if ($arr_row['object_description_id_id'] == static::getSystemTypeObjectDescriptionID($system_type_id, 'reference') && $arr_row['object_description_ref_type_id']) {
							
							$arr_system_object_description = $arr_system_type_set['object_descriptions'][static::getSystemTypeObjectDescriptionID($system_type_id, 'reference')];

							$arr_system_object_description['object_description_id'] = $arr_row['object_description_id'];
							$arr_system_object_description['object_description_ref_type_id'] = $arr_row['object_description_ref_type_id'];
							$arr_system_object_description['object_description_in_name'] = $arr_row['object_description_in_name'];
							
							$arr_row = $arr_system_object_description;
						} else {
							continue;
						}
					} else {
						continue;
					}
				} else {
					
					$arr_row['object_description_is_required'] = DBFunctions::unescapeAs($arr_row['object_description_is_required'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['object_description_is_unique'] = DBFunctions::unescapeAs($arr_row['object_description_is_unique'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['object_description_has_multi'] = DBFunctions::unescapeAs($arr_row['object_description_has_multi'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['object_description_in_name'] = DBFunctions::unescapeAs($arr_row['object_description_in_name'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['object_description_in_search'] = DBFunctions::unescapeAs($arr_row['object_description_in_search'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['object_description_in_overview'] = DBFunctions::unescapeAs($arr_row['object_description_in_overview'], DBFunctions::TYPE_BOOLEAN);
					$arr_row['object_description_is_identifier'] = DBFunctions::unescapeAs($arr_row['object_description_is_identifier'], DBFunctions::TYPE_BOOLEAN);
					
					$arr_row['object_description_value_type_settings'] = ($arr_row['object_description_value_type_settings'] ? json_decode($arr_row['object_description_value_type_settings'], true) : []);
					
					$arr_row['object_description_is_dynamic'] = DBFunctions::unescapeAs($arr_row['object_description_is_dynamic'], DBFunctions::TYPE_BOOLEAN);
				}
				
				$arr['object_descriptions'][$arr_row['object_description_id']] = $arr_row;
			}
		}
		
		if ($arr['type']['class'] == StoreType::TYPE_CLASS_REVERSAL) {
			
			$system_type_id = static::getSystemTypeID('reversal');
			$arr_system_object_description = static::getSystemTypeSet($system_type_id);
			$arr_system_object_description = $arr_system_object_description['object_descriptions'][static::getSystemTypeObjectDescriptionID($system_type_id, 'module')];
			
			$arr['object_descriptions'][static::getSystemTypeObjectDescriptionID($system_type_id, 'module')] = $arr_system_object_description;
		}
		
		while ($arr_row = $arr_res[1]->fetchAssoc()) {

			if (!isset($arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_details'])) {
				
				$arr_row['object_sub_details_is_single'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_single'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_is_required'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_has_date'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_date'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_is_date_period'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_date_period'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_has_location'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_location'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_location_ref_type_id_locked'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_type_id_locked'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_location_ref_object_sub_details_id_locked'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_object_sub_details_id_locked'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_location_use_object_id'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_use_object_id'], DBFunctions::TYPE_BOOLEAN);
				
				$arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_details'] = $arr_row;
				$arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_descriptions'] = [];
			}
							
			if ($arr_row['object_sub_description_id']) {
				
				$arr_row['object_sub_description_is_required'] = DBFunctions::unescapeAs($arr_row['object_sub_description_is_required'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_description_in_name'] = DBFunctions::unescapeAs($arr_row['object_sub_description_in_name'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_description_in_search'] = DBFunctions::unescapeAs($arr_row['object_sub_description_in_search'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_description_in_overview'] = DBFunctions::unescapeAs($arr_row['object_sub_description_in_overview'], DBFunctions::TYPE_BOOLEAN);
				
				$arr_row['object_sub_description_value_type_settings'] = ($arr_row['object_sub_description_value_type_settings'] ? json_decode($arr_row['object_sub_description_value_type_settings'], true) : []);

				$arr_row['object_sub_description_is_dynamic'] = DBFunctions::unescapeAs($arr_row['object_sub_description_is_dynamic'], DBFunctions::TYPE_BOOLEAN);
				
				$arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_descriptions'][$arr_row['object_sub_description_id']] = $arr_row;
			
				if ($arr_row['object_sub_description_use_object_description_id']) {
					
					$ref_type_id = ($arr['object_descriptions'][$arr_row['object_sub_description_use_object_description_id']]['object_description_ref_type_id'] ?? null);
					
					if ($ref_type_id) {
						$arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_descriptions'][$arr_row['object_sub_description_id']]['object_sub_description_ref_type_id'] = $ref_type_id;
					} else {
						$arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_descriptions'][$arr_row['object_sub_description_id']]['object_sub_description_use_object_description_id'] = false;
					}
				}
			}
		}

		self::$arr_types_storage[$type_id] = $arr;

		return $arr;
	}
	
	protected static function getSystemTypeSet($type_id) {
			
		$arr = [
			'type' => [],
			'definitions' => [], 'object_descriptions' => [], 'object_sub_details' => []
		];
		
		$arr['type'] = self::getSystemTypes($type_id);
		
		switch ($type_id) {
			case -1: // Cycle
				
				$arr['object_descriptions'] = [
					-1 => [
						'object_description_id' => -1,
						'object_description_name' => getLabel('lbl_start'),
						'object_description_value_type_base' => 'date',
						'object_description_value_type' => 'date_cycle',
						'object_description_has_multi' => true,
						'object_description_in_name' => true,
						'object_description_is_required' => true
					],
					-2 => [
						'object_description_id' => -2,
						'object_description_name' => getLabel('lbl_end'),
						'object_description_value_type_base' => 'date',
						'object_description_value_type' => 'date_compute',
						'object_description_has_multi' => true,
						'object_description_in_name' => true,
						'object_description_is_required' => true
					]
				];
				
				break;
			case -2: // Ingest
				
				$arr['object_descriptions'] = [
					-3 => [
						'object_description_id' => -3,
						'object_description_name' => getLabel('lbl_resource'),
						'object_description_value_type_base' => 'external',
						'object_description_value_type' => 'external_module',
						'object_description_has_multi' => false,
						'object_description_in_name' => false,
						'object_description_is_required' => true
					],
					-4 => [
						'object_description_id' => -4,
						'object_description_name' => getLabel('lbl_attribution'),
						'object_description_value_type_base' => 'text',
						'object_description_value_type' => 'text',
						'object_description_has_multi' => false,
						'object_description_in_name' => false,
						'object_description_is_required' => false
					],
					-5 => [ // Store processing state
						'object_description_id' => -5,
						'object_description_name' => false,
						'object_description_value_type_base' => 'int',
						'object_description_value_type' => 'int',
						'object_description_has_multi' => false,
						'object_description_in_name' => false,
						'object_description_is_required' => false,
						'object_description_clearance_edit' => NODEGOAT_CLEARANCE_SYSTEM,
						'object_description_clearance_view' => NODEGOAT_CLEARANCE_SYSTEM
					]
				];
				
				break;
			case -3: // Reconcile
				
				$arr['object_descriptions'] = [
					-6 => [
						'object_description_id' => -6,
						'object_description_name' => getLabel('lbl_reconcile'),
						'object_description_value_type_base' => 'external',
						'object_description_value_type' => 'reconcile_module',
						'object_description_has_multi' => false,
						'object_description_in_name' => false,
						'object_description_is_required' => true
					],
					-7 => [
						'object_description_id' => -7,
						'object_description_name' => getLabel('lbl_attribution'),
						'object_description_value_type_base' => 'text',
						'object_description_value_type' => 'text',
						'object_description_has_multi' => false,
						'object_description_in_name' => false,
						'object_description_is_required' => false
					],
					-8 => [ // Store processing state
						'object_description_id' => -8,
						'object_description_name' => false,
						'object_description_value_type_base' => 'int',
						'object_description_value_type' => 'int',
						'object_description_has_multi' => false,
						'object_description_in_name' => false,
						'object_description_is_required' => false,
						'object_description_clearance_edit' => NODEGOAT_CLEARANCE_SYSTEM,
						'object_description_clearance_view' => NODEGOAT_CLEARANCE_SYSTEM
					]
				];
				
				break;
			case -4: // Reversal prototype
				
				$arr['object_descriptions'] = [
					-9 => [
						'object_description_id' => -9,
						'object_description_name' => getLabel('lbl_reversed_classification_reference'),
						'object_description_value_type_base' => '',
						'object_description_value_type' => 'reference',
						'object_description_ref_type_id' => 0,
						'object_description_has_multi' => false,
						'object_description_in_name' => true,
						'object_description_is_required' => false
					],
					-10 => [
						'object_description_id' => -10,
						'object_description_name' => getLabel('lbl_reversal'),
						'object_description_value_type_base' => 'external',
						'object_description_value_type' => 'reversal_module',
						'object_description_has_multi' => false,
						'object_description_in_name' => false,
						'object_description_is_required' => true
					]
				];
				
				break;
		}
		
		self::$arr_types_storage[$type_id] = $arr;

		return $arr;
	}
	
	public static function getSystemTypeID($name, bool $flip = false) {
		
		if (static::$arr_system_type_ids[$flip]) {
			return static::$arr_system_type_ids[$flip][$name];
		}
		
		$arr_types = [
			'cycle' => -1,
			'ingestion' => -2,
			'reconciliation' => -3,
			'reversal' => -4
		];
		
		static::$arr_system_type_ids[$flip] = ($flip ? array_flip($arr_types) : $arr_types);
		
		return static::$arr_system_type_ids[$flip][$name];
	}
	
	public static function getSystemTypeObjectDescriptionID($type_id, $name, bool $flip = false) {
		
		if (static::$arr_system_type_object_description_ids[$flip]) {
			return static::$arr_system_type_object_description_ids[$flip][$type_id][$name];
		}
		
		static::$arr_system_type_object_description_ids[$flip] = [];
		
		$arr_type = [
			'date_cycle' => -1,
			'date_compute' => -2
		];
		
		static::$arr_system_type_object_description_ids[$flip][-1] = ($flip ? array_flip($arr_type) : $arr_type);
		
		$arr_type = [
			'module' => -3,
			'attribution' => -4,
			'state' => -5
		];
		
		static::$arr_system_type_object_description_ids[$flip][-2] = ($flip ? array_flip($arr_type) : $arr_type);
		
		$arr_type = [
			'module' => -6,
			'attribution' => -7,
			'state' => -8
		];
		
		static::$arr_system_type_object_description_ids[$flip][-3] = ($flip ? array_flip($arr_type) : $arr_type);
		
		$arr_type = [
			'reference' => -9,
			'module' => -10
		];
		
		static::$arr_system_type_object_description_ids[$flip][-4] = ($flip ? array_flip($arr_type) : $arr_type);
		
		return static::$arr_system_type_object_description_ids[$flip][$type_id][$name];
	}
	
	public static function getSystemTypeModuleClassEntry($type_id) {
						
		$arr_types = [
			-2 => 'data_ingest', // Ingestion
			-3 => 'data_reconcile' // Reconciliation
		];		
		
		return $arr_types[$type_id];
	}
	
	public static function getTypeSetFlat($type_id, $arr_options = ['object' => false, 'object_sub_details_date' => true, 'object_sub_details_location' => true, 'purpose' => '']) {
	
		$arr_type_set = self::getTypeSet($type_id);
		$arr = [];
		
		if ($arr_options['object']) {
			$arr['id'] = ['id' => 'id', 'name' => getLabel('lbl_object')]; 
		}
		
		if ($arr_type_set['type']['use_object_name']) {
			$arr['name'] = ['id' => 'name', 'name' => getLabel('lbl_name')]; 
		}
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			$arr['object_description_'.$object_description_id] = ['id' => 'object_description_'.$object_description_id, 'name' => $arr_object_description['object_description_name'], 'object_description_id' => $object_description_id]; 
		}
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			$arr['object_sub_details_'.$object_sub_details_id.'_id'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_id', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).']', 'object_sub_details_id' => $object_sub_details_id]; 
			
			if (keyIsUncontested('object_sub_details_date', $arr_options)) {
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_date'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_description_id']) {
					
					if ($arr_options['purpose'] == 'select' || $arr_options['purpose'] == 'filter') {
						$arr['object_sub_details_'.$object_sub_details_id.'_date'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_date', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_date'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'date'];
						if ($arr_options['purpose'] == 'select' && $arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
							$arr['object_sub_details_'.$object_sub_details_id.'_date_start'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_date_start', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_date_start'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'date_start']; 
							$arr['object_sub_details_'.$object_sub_details_id.'_date_end'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_date_end', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_date_end'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'date_end'];
						}
					} else {
						$arr['object_sub_details_'.$object_sub_details_id.'_date_start'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_date_start', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_date_start'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'date_start']; 
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
							$arr['object_sub_details_'.$object_sub_details_id.'_date_end'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_date_end', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_date_end'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'date_end'];
						}
						$arr['object_sub_details_'.$object_sub_details_id.'_date_chronology'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_date_chronology', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_date').' '.getLabel('lbl_chronology'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'date_chronology'];
					}
				}
			}
			if (keyIsUncontested('object_sub_details_location', $arr_options)) {
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_location'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']) {
					
					if ($arr_options['purpose'] == 'select') {
						$arr['object_sub_details_'.$object_sub_details_id.'_location'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_location', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_location'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'location']; 
					} else {
						if ($arr_options['purpose'] == 'filter') {
							$arr['object_sub_details_'.$object_sub_details_id.'_location_reference'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_location_reference', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_location_reference'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'location_reference']; 
						} else {
							$arr['object_sub_details_'.$object_sub_details_id.'_location_ref_type_id'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_location_ref_type_id', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_location_reference'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'location_ref_type_id']; 
						}
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_setting'] != StoreType::VALUE_TYPE_LOCATION_REFERENCE_LOCK) { // Not reference only
							$arr['object_sub_details_'.$object_sub_details_id.'_location_geometry'] = ['id' => 'object_sub_details_'.$object_sub_details_id.'_location_geometry', 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.getLabel('lbl_location').' '.getLabel('lbl_geometry'), 'object_sub_details_id' => $object_sub_details_id, 'identifier' => 'location_geometry'];
						}
					}
				}
			}
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				$arr['object_sub_description_'.$object_sub_description_id] = ['id' => 'object_sub_description_'.$object_sub_description_id, 'name' => '['.Labels::addContainer($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'] '.$arr_object_sub_description['object_sub_description_name'], 'object_sub_details_id' => $object_sub_details_id, 'object_sub_description_id' => $object_sub_description_id]; 
			}
		}

		return $arr;
	}
	
	public static function getTypeSetFlatMap($type_id, $arr_options = ['object' => false, 'references' => true]) {
	
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr = [];
		
		//$arr_ref['object-sources'] = ['id' => 'object-sources', 'name' => 'Object Sources']; 

		if ($arr_options['object']) {
			$arr['object-id'] = ['id' => 'object-id', 'name' => getLabel('lbl_nodegoat_id')]; 
		}
				
		if ($arr_type_set['type']['use_object_name']) {
			$arr['object-name'] = ['id' => 'object-name', 'name' => 'Object Name']; 
		}
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
		
			if ($arr_object_description['object_description_value_type_base'] == 'reversal') {
				continue;
			}
			
			if (!keyIsUncontested('references', $arr_options) && $arr_object_description['object_description_ref_type_id']) {
				continue;
			}
			
			$is_appendable = static::isAppendableTypeObjectDescription($type_id, $object_description_id);
			
			$arr['object_description-'.$object_description_id] = ['id' => 'object_description-'.$object_description_id, 'name' => $arr_object_description['object_description_name'], 'is_appendable' => $is_appendable, 'has_multi' => $arr_object_description['object_description_has_multi'], 'ref_type_id' => $arr_object_description['object_description_ref_type_id']]; 
		}
		
		if ($arr_type_set['object_sub_details']) {
	
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {

				if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
					$is_appendable = false;
				} else {
					$is_appendable = 'only_append';
				}
				
				$str_identifier = 'object_sub_details-'.$object_sub_details_id;
				
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_start_use_object_description_id']) {
					$arr[$str_identifier.'-date_start'] = ['id' => $str_identifier.'-date_start', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_date_start'), 'is_appendable' => $is_appendable]; 
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_period']) {
						$arr[$str_identifier.'-date_end'] = ['id' => $str_identifier.'-date_end', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_date_end'), 'is_appendable' => $is_appendable]; 
					}
					
					$arr[$str_identifier.'-date_chronology'] = ['id' => $str_identifier.'-date_chronology', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_date').' '.getLabel('lbl_chronology'), 'is_appendable' => $is_appendable]; 
				}
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']) {
				
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id_locked']) {
						
						if (keyIsUncontested('references', $arr_options)) {
							
							$arr[$str_identifier.'-location_ref_type_id-object_sub_details_lock'] = [
								'id' => $str_identifier.'-location_ref_type_id-object_sub_details_lock',
								'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location_reference'),
								'ref_type_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'],
								'object_sub_details_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'],
								'is_location_reference' => true,
								'is_appendable' => $is_appendable
							]; 
						}
					} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']) {
						
						if (keyIsUncontested('references', $arr_options)) {
							
							$arr[$str_identifier.'-location_ref_type_id-type_lock-'.$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']] = [
								'id' => $str_identifier.'-location_ref_type_id-type_lock-'.$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'],
								'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location_reference'),
								'ref_type_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'],
								'object_sub_details_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'],
								'is_changeable_object_sub_details_id' => true,
								'is_location_reference' => true,
								'is_appendable' => $is_appendable
							]; 
						}
					} else {
						
						if (keyIsUncontested('references', $arr_options)) {
							
							$arr[$str_identifier.'-location_ref_type_id'] = [
								'id' => $str_identifier.'-location_ref_type_id',
								'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location_reference'),
								'ref_type_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'],
								'object_sub_details_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'],
								'is_changeable_ref_type_id' => true,
								'is_changeable_object_sub_details_id' => true,
								'is_location_reference' => true,
								'is_appendable' => $is_appendable
							]; 
						}
						
						$arr[$str_identifier.'-location_latitude'] = ['id' => $str_identifier.'-location_latitude', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location').' '.getLabel('lbl_latitude'), 'is_appendable' => $is_appendable]; 
						$arr[$str_identifier.'-location_longitude'] = ['id' => $str_identifier.'-location_longitude', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location').' '.getLabel('lbl_longitude'), 'is_appendable' => $is_appendable]; 
						$arr[$str_identifier.'-location_geometry'] = ['id' => $str_identifier.'-location_geometry', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location').' '.getLabel('lbl_geometry'), 'is_appendable' => $is_appendable]; 
					}
				}
				if ($arr_object_sub_details['object_sub_descriptions']) {
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						if (!keyIsUncontested('references', $arr_options) && $arr_object_sub_description['object_sub_description_ref_type_id']) {
							continue;
						}
						
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_single']) {
							$is_appendable = static::isAppendableTypeObjectSubDescription($type_id, $object_sub_details_id, $object_sub_description_id);
						} else {
							$is_appendable = 'only_append';
						}
						
						$arr[$str_identifier.'-object_sub_description-'.$object_sub_description_id] = ['id' => $str_identifier.'-object_sub_description-'.$object_sub_description_id, 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.$arr_object_sub_description['object_sub_description_name'], 'is_appendable' => $is_appendable, 'ref_type_id' => $arr_object_sub_description['object_sub_description_ref_type_id']]; 
					}
				}
			}
		}
		
		return $arr;
	}

	public static function getTypeSelectionByFlatMap($type_id, $arr_map, $arr_options = []) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_selection = ['object' => [], 'object_descriptions' => [], 'object_sub_details' => []];
		
		foreach ($arr_map as $element_identifier => $arr_select) {
			
			$has_select = is_array($arr_select);
			$arr_element = explode('-', $element_identifier);
			
			if ($arr_element[0] == 'object') {
				
				if ($arr_element[1] == 'name') {
					
					$arr_selection['object']['all'] = true;
				}
			} else if ($arr_element[0] == 'object_description') {
					
				$object_description_id = $arr_element[1];
									
				$arr_collect = [];
				
				if ($has_select) {
					
					if ($arr_select['value'] !== null) {
						$arr_collect['object_description_value'] = $arr_select['value'];
					}
					if ($arr_select['reference'] !== null) {
						$arr_collect['object_description_reference'] = $arr_select['reference'];
					}
					if ($arr_select['reference_value'] !== null) {
						$arr_collect['object_description_reference_value'] = $arr_select['reference_value'];
					}
					
					$arr_collect = ['object_description_id' => $object_description_id];
				} else {
					if ($arr_select) {
						$arr_collect = ['object_description_id' => $object_description_id];
					} else {
						$arr_collect = false;
					}
				}

				$arr_selection['object_descriptions'][$object_description_id] = $arr_collect;
				
			} else if ($arr_element[0] == 'object_sub_details') {

				$object_sub_details_id = $arr_element[1];

				if (!$arr_selection['object_sub_details'][$object_sub_details_id]) {
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['object_sub_details_id' => $object_sub_details_id];
				}
				
				$str_element = $arr_element[2];
				
				switch ($str_element) {
					
					case 'date_chronology':
					case 'date_start':
					case 'date_end':

						$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date'] = (bool)$arr_select;

						break;
					case 'location_geometry':
					case 'location_latitude':
					case 'location_longitude':
					
						$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_geometry'] = (bool)$arr_select;

						break;
					case 'location_reference':
					case 'location_ref_type_id':
					
						$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_reference'] = (bool)$arr_select;

						break;
					case 'location_reference_value':
					
						$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_reference_value'] = (bool)$arr_select;

						break;
					case 'object_sub_description':
					
						$object_sub_description_id = $arr_element[3];
													
						$arr_collect = [];
						
						if ($has_select) {
							
							if ($arr_select['value'] !== null) {
								$arr_collect['object_sub_description_value'] = $arr_select['value'];
							}
							if ($arr_select['reference'] !== null) {
								$arr_collect['object_sub_description_reference'] = $arr_select['reference'];
							}
							if ($arr_select['reference_value'] !== null) {
								$arr_collect['object_sub_description_reference_value'] = $arr_select['reference_value'];
							}
							
							$arr_collect['object_sub_description_id'] = $object_sub_description_id;
						} else {
							if ($arr_select) {
								$arr_collect = ['object_sub_description_id' => $object_sub_description_id];
							} else {
								$arr_collect = false;
							}
						}
						
						$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $arr_collect;
						
						break;
				}
			}
		}
		
		return $arr_selection;
	}
	
	public static function getTypeSetByFlatMap($type_id, $arr_map, $arr_options = []) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_selection = ['type' => [], 'object_descriptions' => [], 'object_sub_details' => []];
		
		foreach ($arr_map as $element_identifier => $arr_select) {
			
			$has_select = is_array($arr_select);
			$arr_element = explode('-', $element_identifier);
			
			if ($arr_element[0] == 'object') {
				
				if ($arr_element[1] == 'name') {
					
					$arr_selection['type'] = $arr_type_set['type'];
				}
			} else if ($arr_element[0] == 'object_description') {
					
				$object_description_id = $arr_element[1];

				$arr_selection['object_descriptions'][$object_description_id] = $arr_type_set['object_descriptions'][$object_description_id];
				
			} else if ($arr_element[0] == 'object_sub_details') {

				$object_sub_details_id = $arr_element[1];
				
				$str_element = $arr_element[2];
				
				if ($str_element == 'object_sub_description') {
					
					$object_sub_description_id = $arr_element[3];
					
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
				} else {
					
					$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details'];
				}

			}
		}
		
		return $arr_selection;	
	}
	
	public static function getTypeObjectSubsDetails($type_id, $object_sub_details_id = 0) {
	
		$arr = [];

		$res = DB::query("SELECT
			nodegoat_tos_det.id,
			nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_det.name AS object_sub_details_name, nodegoat_tos_det.is_single AS object_sub_details_is_single, nodegoat_tos_det.is_required AS object_sub_details_is_required,
				nodegoat_tos_det.clearance_edit AS object_sub_details_clearance_edit, nodegoat_tos_det.clearance_view AS object_sub_details_clearance_view,
				nodegoat_tos_det.has_date AS object_sub_details_has_date, nodegoat_tos_det.is_date_period AS object_sub_details_is_date_period,
					nodegoat_tos_det.date_setting AS object_sub_details_date_setting, nodegoat_tos_det.date_setting_type_id AS object_sub_details_date_setting_type_id, nodegoat_tos_det.date_setting_object_sub_details_id AS object_sub_details_date_setting_object_sub_details_id,
					nodegoat_tos_det.date_use_object_sub_details_id AS object_sub_details_date_use_object_sub_details_id, nodegoat_tos_det.date_start_use_object_sub_description_id AS object_sub_details_date_start_use_object_sub_description_id, nodegoat_tos_det.date_start_use_object_description_id AS object_sub_details_date_start_use_object_description_id, nodegoat_tos_det.date_end_use_object_sub_description_id AS object_sub_details_date_end_use_object_sub_description_id, nodegoat_tos_det.date_end_use_object_description_id AS object_sub_details_date_end_use_object_description_id,
				nodegoat_tos_det.has_location AS object_sub_details_has_location,
					nodegoat_tos_det.location_setting AS object_sub_details_location_setting, nodegoat_tos_det.location_ref_type_id AS object_sub_details_location_ref_type_id, nodegoat_tos_det.location_ref_type_id_locked AS object_sub_details_location_ref_type_id_locked, nodegoat_tos_det.location_ref_object_sub_details_id AS object_sub_details_location_ref_object_sub_details_id, nodegoat_tos_det.location_ref_object_sub_details_id_locked AS object_sub_details_location_ref_object_sub_details_id_locked,
						nodegoat_tos_det.location_use_object_sub_details_id AS object_sub_details_location_use_object_sub_details_id, nodegoat_tos_det.location_use_object_sub_description_id AS object_sub_details_location_use_object_sub_description_id, nodegoat_tos_det.location_use_object_description_id AS object_sub_details_location_use_object_description_id, nodegoat_tos_det.location_use_object_id AS object_sub_details_location_use_object_id,
				nodegoat_tos_det.sort AS object_sub_details_sort
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
			WHERE type_id = COALESCE((SELECT ref_type_id FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." WHERE type_id = ".(int)$type_id." AND id_id = ".static::getSystemTypeObjectDescriptionID(static::getSystemTypeID('reversal'), 'reference')."), ".(int)$type_id.")
				".($object_sub_details_id ? "AND id = ".(int)$object_sub_details_id."" : "")."
			".(!$object_sub_details_id ? "ORDER BY nodegoat_tos_det.sort ASC" : "")."
		");
							
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['object_sub_details_is_single'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_single'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_is_required'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_has_date'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_date'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_is_date_period'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_date_period'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_has_location'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_location'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_location_ref_type_id_locked'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_type_id_locked'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_location_ref_object_sub_details_id_locked'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_object_sub_details_id_locked'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_location_use_object_id'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_use_object_id'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		}		

		return $arr;
	}
	
	public static function getTypesObjectIdentifierDescriptions($type_id) {
		
		if (is_array($type_id)) {
			$sql_type_ids = implode(',', arrParseRecursive($type_id, TYPE_INTEGER));
		} else {
			$sql_type_ids = (int)$type_id;
		}
		
		$arr = [];

		$res = DB::query("SELECT
			nodegoat_to_des.type_id, nodegoat_to_des.id
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
			WHERE nodegoat_to_des.type_id IN (".$sql_type_ids.")
				AND nodegoat_to_des.is_identifier
			ORDER BY nodegoat_to_des.sort ASC
		");
							
		while ($arr_row = $res->fetchAssoc()) {
			$arr[$arr_row['type_id']][$arr_row['id']] = $arr_row['id'];
		}		

		return $arr;
	}
	
	public static function getTypesObjectValueTypeDescriptions($value_type_base, $type_id) {
		
		if (is_array($type_id)) {
			$sql_type_ids = implode(',', arrParseRecursive($type_id, TYPE_INTEGER));
		} else {
			$sql_type_ids = (int)$type_id;
		}
		
		if (is_array($value_type_base)) {
			$sql_value_types = implode("','", DBFunctions::arrEscape($value_type_base));
		} else {
			$sql_value_types = "'".DBFunctions::strEscape($value_type_base)."'";
		}

		$arr = [];

		$arr_res = DB::queryMulti("
			SELECT nodegoat_to_des.type_id, nodegoat_to_des.id
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
				WHERE nodegoat_to_des.type_id IN (".$sql_type_ids.")
					AND nodegoat_to_des.value_type_base IN (".$sql_value_types.")
				ORDER BY nodegoat_to_des.sort ASC
			;
			
			SELECT nodegoat_tos_det.type_id, nodegoat_tos_des.object_sub_details_id, nodegoat_tos_des.id
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
					JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_tos_det.id)
				WHERE nodegoat_tos_det.type_id IN (".$sql_type_ids.")
					AND nodegoat_tos_des.value_type_base IN (".$sql_value_types.")
				ORDER BY nodegoat_tos_des.sort ASC
			;
		");
		
		while ($arr_row = $arr_res[0]->fetchAssoc()) {
			$arr[$arr_row['type_id']]['object_descriptions'][$arr_row['id']] = $arr_row['id'];
		}
		
		while ($arr_row = $arr_res[1]->fetchAssoc()) {
			$arr[$arr_row['type_id']]['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_descriptions'][$arr_row['id']] = $arr_row['id'];
		}

		return $arr;
	}
	
	public static function isAppendableTypeObjectDescription($type_id, $object_description_id) {
		
		if (!$object_description_id) {
			return false;
		}
		
		$arr_type_set = self::getTypeSet($type_id);
		$arr = $arr_type_set['object_descriptions'][$object_description_id];
		
		$base = $arr['object_description_value_type_base'];
		$has_multi = $arr['object_description_has_multi'];
			
		if ($has_multi) {
			return true;
		}
		
		if (!$base || $base == 'int' || strpos($base, 'text') !== false) {
			return true;
		}
		
		return false;
	}
	
	public static function isAppendableTypeObjectSubDescription($type_id, $object_sub_details_id, $object_sub_description_id) {
		
		if (!$object_sub_description_id) {
			return false;
		}
		
		$arr_type_set = self::getTypeSet($type_id);
		$arr = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];

		$base = $arr['object_sub_description_value_type_base'];
		$has_multi = false;
			
		if ($has_multi) {
			return true;
		}
		
		if (!$base || $base == 'int' || strpos($base, 'text') !== false) {
			return true;
		}
		
		return false;
	}
	
	public static function getTypeObjectPath($path, $type_id) {
		
		if (isset(self::$arr_types_object_path_storage[$path][$type_id])) {
			return self::$arr_types_object_path_storage[$path][$type_id];
		}
		
		$arr = [];

		$res = DB::query("SELECT
			nodegoat_to_path.*,
			CASE 
				WHEN nodegoat_tos_des_ref.id != 0 THEN
					CASE
						WHEN nodegoat_tos_des_ref.value_type_base = 'reversal' THEN
							CASE
								WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des_ref.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN 'reversed_collection'
								ELSE 'reversed_classification'
							END
						WHEN nodegoat_tos_des_ref.value_type_base != '' THEN nodegoat_tos_des_ref.value_type_base
						ELSE ''
					END					
				ELSE
					CASE
						WHEN nodegoat_to_des_ref.value_type_base = 'reversal' THEN
							CASE
								WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des_ref.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN 'reversed_collection'
								ELSE 'reversed_classification'
							END
						WHEN nodegoat_to_des_ref.value_type_base != '' THEN nodegoat_to_des_ref.value_type_base
						WHEN nodegoat_to_des_ref.id_id = ".static::getSystemTypeObjectDescriptionID(static::getSystemTypeID('reversal'), 'reference')." THEN 'reference'
						ELSE ''
					END	
			END AS value_type,
			CASE
				WHEN nodegoat_tos_des_ref.id != 0 THEN
					CASE 
						WHEN nodegoat_tos_des_ref.value_type_base = 'text_tags' THEN TRUE
						WHEN nodegoat_tos_des_ref.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des_ref.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN TRUE
						ELSE FALSE
					END
				ELSE
					CASE 
						WHEN nodegoat_to_des_ref.value_type_base = 'text_tags' THEN TRUE
						WHEN nodegoat_to_des_ref.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des_ref.ref_type_id AND mode & ".static::TYPE_MODE_REVERSAL_COLLECTION." != 0) THEN TRUE
						ELSE FALSE
					END
			END AS is_dynamic
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_'.($path == 'name' ? 'NAME' : 'SEARCH').'_PATH')." nodegoat_to_path
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des_ref ON (nodegoat_to_des_ref.id = nodegoat_to_path.ref_object_description_id AND nodegoat_to_path.ref_object_sub_details_id = 0)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des_ref ON (nodegoat_tos_des_ref.id = nodegoat_to_path.ref_object_description_id AND nodegoat_tos_des_ref.object_sub_details_id = nodegoat_to_path.ref_object_sub_details_id)
			WHERE nodegoat_to_path.type_id = ".(int)$type_id."
			ORDER BY sort ASC, is_reference DESC, org_object_description_id, org_object_sub_details_id
		");
								 
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['is_reference'] = DBFunctions::unescapeAs($arr_row['is_reference'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['is_dynamic'] = DBFunctions::unescapeAs($arr_row['is_dynamic'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[] = $arr_row;
		}
		
		self::$arr_types_object_path_storage[$path][$type_id] = $arr;	

		return $arr;
	}
	
	public static function getTypesLabels() {
	
		$arr = [];

		$res = DB::query("SELECT nodegoat_t.label
				FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
			WHERE TRUE
				"."
				GROUP BY nodegoat_t.label
				ORDER BY nodegoat_t.label ASC
		");
								 
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr[] = $arr_row;
		}		

		return $arr;
	}
	
	public static function getValueTypesBase() {

		$arr_value_types_base = [
			'type' => ['id' => 'type', 'name' => getLabel('lbl_reference').': '.getLabel('lbl_object').' '.getLabel('lbl_type'), 'name_base' => getLabel('lbl_type')],
			'classification' => ['id' => 'classification', 'name' => getLabel('lbl_reference').': '.getLabel('lbl_classification'), 'name_base' => getLabel('lbl_classification')],
			'reversal' => ['id' => 'reversal', 'name' => getLabel('lbl_reference').': '.getLabel('lbl_reversal'), 'name_base' => getLabel('lbl_reversal')],
			'' => ['id' => '', 'name' => getLabel('unit_data_string'), 'name_base' => getLabel('unit_data_string'), 'has_support_multi' => true, 'has_support_identifier' => true],
			'int' => ['id' => 'int', 'name' => getLabel('unit_data_number'), 'name_base' => getLabel('unit_data_number'), 'has_support_multi' => true],
			'numeric' => ['id' => 'numeric', 'name' => getLabel('unit_data_numeric_decimal'), 'name_base' => getLabel('unit_data_numeric'), 'has_support_multi' => true],
			'text' => ['id' => 'text', 'name' => getLabel('unit_data_text'), 'name_base' => getLabel('unit_data_text')],
			'text_layout' => ['id' => 'text_layout', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_layout').')', 'name_base' => getLabel('unit_data_text')],
			'text_tags' => ['id' => 'text_tags', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_tags').' & '.getLabel('lbl_layout').')', 'name_base' => getLabel('unit_data_text')],
			'boolean' => ['id' => 'boolean', 'name' => getLabel('unit_data_boolean_true_false'), 'name_base' => getLabel('unit_data_boolean')],
			'date' => ['id' => 'date', 'name' => getLabel('unit_data_date'), 'name_base' => getLabel('unit_data_date')],
			'serial_varchar' => ['id' => 'serial_varchar', 'name' => getLabel('unit_data_serial_varchar'), 'name_base' => getLabel('unit_data_serial'), 'has_support_multi' => true, 'has_support_identifier' => true],
			'media' => ['id' => 'media', 'name' => getLabel('lbl_media'), 'name_base' => getLabel('lbl_media'), 'has_support_multi' => true],
			'media_external' => ['id' => 'media_external', 'name' => getLabel('lbl_media').' ('.getLabel('lbl_external').')', 'name_base' => getLabel('lbl_media'), 'has_support_multi' => true],
			'module' => ['id' => 'module', 'name' => getLabel('lbl_application'), 'name_base' => getLabel('lbl_application')],
			'external' => ['id' => 'external', 'name' => getLabel('lbl_external'), 'name_base' => getLabel('lbl_external'), 'has_support_multi' => true, 'has_support_identifier' => true],
			'object_description' => ['id' => 'object_description', 'name' => getLabel('lbl_object_description'), 'name_base' => getLabel('lbl_object_description')]
		];
		
		return $arr_value_types_base;
	}
	
	public static function getValueTypes() {
		
		if (self::$arr_value_types) {
			return self::$arr_value_types;
		}
		
		self::$arr_value_types = [
			'' => ['id' => '', 'name' => getLabel('unit_data_string'), 'table' => '', 'value' => 'value'],
			'reference' => ['id' => 'reference', 'name' => getLabel('lbl_reference'), 'table' => '_references', 'value' => 'ref_object_id'],
			'type' => ['id' => 'type', 'name' => getLabel('lbl_type'), 'table' => '_references', 'value' => 'ref_object_id'],
			'classification' => ['id' => 'classification', 'name' => getLabel('lbl_classification'), 'table' => '_references', 'value' => 'ref_object_id'],
			'reversed_classification' => ['id' => 'reversed_classification', 'name' => getLabel('lbl_reversed_classification'), 'table' => '_references', 'value' => 'ref_object_id'],
			'reversed_collection' => ['id' => 'reversed_collection', 'name' => getLabel('lbl_reversed_collection'), 'table' => '_references', 'value' => 'ref_object_id', 'purpose' => ['name' => ['table' => '', 'value' => 'value_text'], 'search' => ['table' => '', 'value' => 'value_text']]],
			'int' => ['id' => 'int', 'name' => getLabel('unit_data_number'), 'table' => '', 'value' => 'value_int'],
			'numeric' => ['id' => 'numeric', 'name' => getLabel('unit_data_numeric_decimal'), 'table' => '', 'value' => 'value_int'],
			'text' => ['id' => 'text', 'name' => getLabel('unit_data_text'), 'table' => '', 'value' => 'value_text'],
			'text_layout' => ['id' => 'text_layout', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_layout').')', 'table' => '', 'value' => 'value_text'],
			'text_tags' => ['id' => 'text_tags', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_tags').' & '.getLabel('lbl_layout').')', 'table' => '', 'value' => 'value_text'],
			'boolean' => ['id' => 'boolean', 'name' => getLabel('unit_data_boolean_true_false'), 'table' => '', 'value' => 'value_int'],
			'date' => ['id' => 'date', 'name' => getLabel('unit_data_date'), 'table' => '', 'value' => 'value_int'],
			'date_cycle' => ['id' => 'date_cycle', 'name' => getLabel('unit_data_date').' '.getLabel('lbl_date_cycle'), 'table' => '', 'value' => 'value_int'],
			'date_compute' => ['id' => 'date_compute', 'name' => getLabel('unit_data_date').' '.getLabel('lbl_date_compute'), 'table' => '', 'value' => 'value_int'],
			'serial_varchar' => ['id' => 'serial_varchar', 'name' => getLabel('unit_data_serial_varchar'), 'table' => '', 'value' => 'value'],
			'media' => ['id' => 'media', 'name' => getLabel('lbl_media'), 'table' => '', 'value' => 'value'],
			'media_external' => ['id' => 'media_external', 'name' => getLabel('lbl_media').' ('.getLabel('lbl_external').')', 'table' => '', 'value' => 'value'],
			'module' => ['id' => 'module', 'name' => '', 'table' => '_modules', 'value' => 'object', 'is_module' => true],
			'external' => ['id' => 'external', 'name' => getLabel('lbl_external'), 'table' => '', 'value' => 'value'],
			'external_module' => ['id' => 'external_module', 'name' => '', 'table' => '_modules', 'value' => 'object', 'is_module' => true, 'module_class' => 'data_ingest'],
			'object_description' => ['id' => 'object_description', 'name' => getLabel('lbl_object_description'), 'table' => '', 'value' => 'value'],
			'reconcile_module' => ['id' => 'reconcile_module', 'name' => '', 'table' => '_modules', 'value' => 'object', 'is_module' => true, 'module_class' => 'data_reconcile'],
			'reversal_module' => ['id' => 'reversal_module', 'name' => '', 'table' => '_modules', 'value' => 'object', 'is_module' => true]
		];
		
		return self::$arr_value_types;
	}
	
	public static function getValueTypeValue($str_type, $str_purpose = false) {
		
		return static::getValueType($str_type, 'value', $str_purpose);
	}
	
	public static function getValueTypeTable($str_type, $str_purpose = false) {
		
		return static::getValueType($str_type, 'table', $str_purpose);
	}
	
	public static function isValueTypeModule($str_type, $str_purpose = false) {
		
		return static::getValueType($str_type, 'is_module', $str_purpose);
	}
	
	public static function getValueType($str_type, $str_property = false, $str_purpose = false) {
		
		if (!self::$arr_value_types) {
			self::getValueTypes();
		}
		
		$arr_value_type = self::$arr_value_types[$str_type];
		
		if ($str_purpose && isset($arr_value_type['purpose'][$str_purpose])) {

			$arr_value_type = $arr_value_type['purpose'][$str_purpose];
		}
				
		return ($str_property ? $arr_value_type[$str_property] : $arr_value_type);
	}
	
	public static function getValueTypeTables() {
		
		if (!self::$arr_value_types) {
			self::getValueTypes();
		}
		
		$arr_tables = [];
		
		foreach (self::$arr_value_types as $value_type => $arr_value_type) {
			
			$arr_tables[$arr_value_type['table']] = $arr_value_type['table'];
		}	
		
		return $arr_tables;
	}
	
	public static function getValueUsage() {
		
		return [
			'object' => ['id' => 'object', 'name' => getLabel('lbl_object')],
			'object_sub_details' => ['id' => 'object_sub_details', 'name' => getLabel('lbl_object_sub')],
			'object_sub_description' => ['id' => 'object_sub_description', 'name' => getLabel('lbl_object_sub').' '.getLabel('lbl_description')],
			'object_description' => ['id' => 'object_description', 'name' => getLabel('lbl_object_description')],
		];
	}
	
	public static function getTimeUnits() {
		
		if (self::$arr_time_units) {
			return self::$arr_time_units;
		}
		
		self::$arr_time_units = [
			'day' => ['id' => 'day', 'name' => getLabel('unit_day')],
			'month' => ['id' => 'month', 'name' => getLabel('unit_month')],
			'year' => ['id' => 'year', 'name' => getLabel('unit_year')]
		];
		
		return self::$arr_time_units;
	}
	
	public static function getTimeUnitsInternal(bool $flip = false) {
		
		if (self::$arr_time_units_internal[$flip]) {
			return self::$arr_time_units_internal[$flip];
		}
		
		self::$arr_time_units_internal[$flip] = ($flip ? [
			static::TIME_UNIT_DAY => 'day',
			static::TIME_UNIT_MONTH => 'month',
			static::TIME_UNIT_YEAR => 'year'
		] : [
			'day' => static::TIME_UNIT_DAY,
			'month' => static::TIME_UNIT_MONTH,
			'year' => static::TIME_UNIT_YEAR
		]);
		
		return self::$arr_time_units_internal[$flip];
	}
	
	public static function getTimeDirections() {
		
		if (self::$arr_time_directions) {
			return self::$arr_time_directions;
		}
		
		self::$arr_time_directions = [
			'|>|' => ['id' => '|>|', 'name' => getLabel('direction_after_begin')],
			'<||' => ['id' => '<||', 'name' => getLabel('direction_before_begin')],
			'||>' => ['id' => '||>', 'name' => getLabel('direction_after_end')],
			'|<|' => ['id' => '|<|', 'name' => getLabel('direction_before_end')]
		];
		
		return self::$arr_time_directions;
	}
	
	public static function getTimeDirectionsInternal(bool $flip = false) {
		
		if (self::$arr_time_directions_internal[$flip]) {
			return self::$arr_time_directions_internal[$flip];
		}
		
		self::$arr_time_directions_internal[$flip] = ($flip ? [
			static::TIME_AFTER_BEGIN => '|>|',
			static::TIME_BEFORE_BEGIN => '<||',
			static::TIME_AFTER_END => '||>',
			static::TIME_BEFORE_END => '|<|'
		] : [
			'|>|' => static::TIME_AFTER_BEGIN,
			'<||' => static::TIME_BEFORE_BEGIN,
			'||>' => static::TIME_AFTER_END,
			'|<|' => static::TIME_BEFORE_END
		]);
				
		return self::$arr_time_directions_internal[$flip];
	}
	
	public static function getDateOptions(bool $flip = false) {
		
		return ($flip ? [
			'point' => ['id' => static::VALUE_TYPE_DATE_POINT, 'name' => 'Point'],
			'object_sub' => ['id' => static::VALUE_TYPE_DATE_OBJECT_SUB, 'name' => 'Reference'],
			'chronology' => ['id' => static::VALUE_TYPE_DATE_CHRONOLOGY, 'name' => 'Chronology']
		] : [
			static::VALUE_TYPE_DATE_POINT => ['id' => 'point', 'name' => 'Point'],
			static::VALUE_TYPE_DATE_OBJECT_SUB => ['id' => 'object_sub', 'name' => 'Reference'],
			static::VALUE_TYPE_DATE_CHRONOLOGY => ['id' => 'chronology', 'name' => 'Chronology']
		]);
	}
	
	public static function getLocationOptions(bool $flip = false) {
		
		return ($flip ? [
			'reference' => ['id' => static::VALUE_TYPE_LOCATION_REFERENCE, 'name' => 'Reference'],
			'geometry' => ['id' => static::VALUE_TYPE_LOCATION_GEOMETRY, 'name' => 'Geometry'],
			'point' => ['id' => static::VALUE_TYPE_LOCATION_POINT, 'name' => 'Point'],
			'reference_lock' => ['id' => static::VALUE_TYPE_LOCATION_REFERENCE_LOCK, 'name' => 'Reference Only']
		] : [
			static::VALUE_TYPE_LOCATION_REFERENCE => ['id' => 'reference', 'name' => 'Reference'],
			static::VALUE_TYPE_LOCATION_GEOMETRY => ['id' => 'geometry', 'name' => 'Geometry'],
			static::VALUE_TYPE_LOCATION_POINT => ['id' => 'point', 'name' => 'Point'],
			static::VALUE_TYPE_LOCATION_REFERENCE_LOCK => ['id' => 'reference_lock', 'name' => 'Reference Only']
		]);	
	}
	
	public static function checkTypeConfigurationUserClearance($arr_type_set, $num_user_clearance, $object_description_id, $object_sub_details_id, $object_sub_description_id, $type = self::CLEARANCE_PURPOSE_VIEW) {
		
		if ($object_description_id) {
			
			$num_clearance = $arr_type_set['object_descriptions'][$object_description_id]['object_description_clearance_'.$type];
			
			if ($num_user_clearance < $num_clearance) {
				return false;
			}
		} else if ($object_sub_details_id) {
			
			$num_clearance = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_'.$type];
			
			if ($num_user_clearance < $num_clearance) {
				return false;
			}
		
			if ($object_sub_description_id) {
				
				$num_clearance = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_clearance_'.$type];
				
				if ($num_user_clearance < $num_clearance) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	public static function parseTypeNetwork($arr, $value_type = false, $num_user_clearance = 0) {
		
		$arr = ['paths' => ($arr['paths'] ?: []), 'types' => ($arr['types'] ?: [])];
		
		$arr['paths'] = static::parseTypeNetworkPaths($arr['paths']);
			
		foreach ($arr['types'] as $source_path => $arr_source_type) {
			
			foreach ($arr_source_type as $type_id => $arr_type) {

				$s_arr = &$arr['types'][$source_path][$type_id];
				
				$arr_selection = [];
				
				if ($s_arr['selection']) {
					
					$arr_type_set = static::getTypeSet($type_id);

					// Pre-parse selection
					
					foreach ($s_arr['selection'] as $id => $arr_selected) {
						
						if (isset($arr_selected['id'])) { // Form
							
							if (is_array($arr_selected['id'])) {
								$str_id = current($arr_selected['id']);
							} else {
								$str_id = $arr_selected['id'];
							}
							
							if (!$str_id) {
								continue;
							}
							
							$pos_dynamic_reference = strpos($str_id, '_reference_');
						
							if ($pos_dynamic_reference !== false) {
								
								$ref_type_id = (int)substr($str_id, $pos_dynamic_reference + 11);
								$str_id = substr($str_id, 0, $pos_dynamic_reference + 10);
								
								if ($ref_type_id) {
									
									$arr_selection[$str_id]['ref_type_ids'][$ref_type_id] = $ref_type_id;
									continue;
								}
							}
						} else { // Already parsed previously
							
							$str_id = $id;
							
							if (!$str_id) {
								continue;
							}
							
							$arr_ref_type_ids = ($arr_selected['object_description_reference'] ?: $arr_selected['object_sub_description_reference']);
							
							if ($arr_ref_type_ids) {
								
								$arr_selection[$str_id]['ref_type_ids'] = $arr_ref_type_ids;
								continue;
							}
						}

						if ($arr_selection[$str_id] === null) {
							$arr_selection[$str_id] = [];
						}
					}
					
					// Amend selection
					
					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						$str_id = 'object_description_'.$object_description_id;
						$is_dynamic = $arr_object_description['object_description_is_dynamic'];
						$in_selection = ($arr_selection[$str_id] !== null || ($is_dynamic && ($arr_selection[$str_id.'_reference'] !== null || $arr_selection[$str_id.'_value'] !== null)));
						
						if ($in_selection && !static::checkTypeConfigurationUserClearance($arr_type_set, $num_user_clearance, $object_description_id, false, false, self::CLEARANCE_PURPOSE_VIEW)) {
						
							unset($arr_selection[$str_id]);
							if ($is_dynamic) {
								unset($arr_selection[$str_id.'_reference'], $arr_selection[$str_id.'_value']);
							}
						} else if ($in_selection) {
							
							if ($arr_selection[$str_id] !== null) {
								$arr_selection[$str_id]['object_description_id'] = $object_description_id;
							}
							
							if ($is_dynamic) {
								
								if ($arr_selection[$str_id.'_reference'] !== null) {
									
									$arr_selection[$str_id.'_reference']['object_description_id'] = $object_description_id;
									$arr_selection[$str_id.'_reference']['object_description_reference'] = ($arr_selection[$str_id.'_reference']['ref_type_ids'] ?: true);
									$arr_selection[$str_id.'_reference']['object_description_reference_value'] = true;
									
									unset($arr_selection[$str_id.'_reference']['ref_type_ids']);
								}
								if ($arr_selection[$str_id.'_value'] !== null) {
									
									$arr_selection[$str_id.'_value']['object_description_id'] = $object_description_id;
									$arr_selection[$str_id.'_value']['object_description_value'] = true;
								}
							}
						}
					}
					
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
						if ($arr_selection[$str_id] !== null) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
						}
						
						// For specific date selection purposes
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_date_start';
						if ($arr_selection[$str_id] !== null) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
							$arr_selection[$str_id]['object_sub_details_date_start'] = true;
						}
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_date_end';
						if ($arr_selection[$str_id] !== null) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
							$arr_selection[$str_id]['object_sub_details_date_end'] = true;
						}
						
						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							$str_id = 'object_sub_description_'.$object_sub_description_id;
							$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
							$in_selection = ($arr_selection[$str_id] !== null || ($is_dynamic && ($arr_selection[$str_id.'_reference'] !== null || $arr_selection[$str_id.'_value'] !== null)));
							
							if ($in_selection && !static::checkTypeConfigurationUserClearance($arr_type_set, $num_user_clearance, false, $object_sub_details_id, $object_sub_description_id, self::CLEARANCE_PURPOSE_VIEW)) {
								
								unset($arr_selection[$str_id]);
								if ($is_dynamic) {
									unset($arr_selection[$str_id.'_reference'], $arr_selection[$str_id.'_value']);
								}
							} else if ($in_selection) {
								
								if ($arr_selection[$str_id] !== null) {
									
									$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
									$arr_selection[$str_id]['object_sub_description_id'] = $object_sub_description_id;
								}
								
								if ($is_dynamic) {
									
									if ($arr_selection[$str_id.'_reference'] !== null) {
										
										$arr_selection[$str_id.'_reference']['object_sub_details_id'] = $object_sub_details_id;
										$arr_selection[$str_id.'_reference']['object_sub_description_id'] = $object_sub_description_id;
										$arr_selection[$str_id.'_reference']['object_sub_description_reference'] = ($arr_selection[$str_id.'_reference']['ref_type_ids'] ?: true);
										$arr_selection[$str_id.'_reference']['object_sub_description_reference_value'] = true;
										
										unset($arr_selection[$str_id.'_reference']['ref_type_ids']);
									}
									if ($arr_selection[$str_id.'_value'] !== null) {
										
										$arr_selection[$str_id.'_value']['object_sub_details_id'] = $object_sub_details_id;
										$arr_selection[$str_id.'_value']['object_sub_description_id'] = $object_sub_description_id;
										$arr_selection[$str_id.'_value']['object_sub_description_value'] = true;
									}
								}
							}			
						}				
					}
				}
				
				$s_arr = [
					'filter' => ($s_arr ? (bool)$s_arr['filter'] : false),
					'collapse' => ($s_arr ? (bool)$s_arr['collapse'] : false),
					'object_only' => ($s_arr ? (bool)$s_arr['object_only'] : false)
				];
				$s_arr['selection'] = ($s_arr['object_only'] ? [] : $arr_selection);
				
				if (!$s_arr['selection'] && !$s_arr['filter'] && !$s_arr['collapse'] && !$s_arr['object_only']) {
					unset($arr['types'][$source_path][$type_id]);
				}
			}
			
			if (!$arr['types'][$source_path]) {
				unset($arr['types'][$source_path]);
			}
		}
		
		return $arr;
	}
	
	public static function parseTypeNetworkModePick($arr, $num_user_clearance = 0) {
		
		$arr = ['paths' => ($arr['paths'] ?: []), 'types' => ($arr['types'] ?: [])];
		
		$arr['paths'] = static::parseTypeNetworkPaths($arr['paths']);

		$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
		
		foreach ($arr['types'] as $source_path => $arr_source_type) {
			
			foreach ($arr_source_type as $type_id => $arr_type) {
				
				$s_arr = &$arr['types'][$source_path][$type_id];
				
				$arr_selection = [];
				
				if ($s_arr['selection']) {
					
					$arr_type_set = static::getTypeSet($type_id);

					foreach ($s_arr['selection'] as $key => $value) {
						
						if ($value['id']) { // Form
							$str_id = $value['id'];
						} else if ($value && $value['object_description_id'] !== null) { // Already parsed previously
							$str_id = $key;
						} else {
							continue;
						}
						
						$arr_selection[$str_id] = [
							'object_description_id' => 0,
							'object_sub_details_id' => 0,
							'object_sub_description_id' => 0
						];
					}
					
					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						$str_id = 'object_description_'.$object_description_id;
						
						if (($arr_selection[$str_id] || $arr_selection[$str_id.'_id'] || $arr_selection[$str_id.'_text']) && (!static::checkTypeConfigurationUserClearance($arr_type_set, $num_user_clearance, $object_description_id, false, false, self::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($arr_project['types'], $arr_type_set, $object_description_id, false, false, StoreCustomProject::ACCESS_PURPOSE_VIEW))) {
							unset($arr_selection[$str_id], $arr_selection[$str_id.'_id'], $arr_selection[$str_id.'_text']);
							continue;
						}
						
						if ($arr_selection[$str_id]) {
							$arr_selection[$str_id]['object_description_id'] = $object_description_id;
						}
						if ($arr_selection[$str_id.'_id']) {
							$arr_selection[$str_id.'_id']['object_description_id'] = $object_description_id;
							$arr_selection[$str_id.'_id']['use_id'] = true;
						}
						if ($arr_selection[$str_id.'_text']) {
							$arr_selection[$str_id.'_text']['object_description_id'] = $object_description_id;
							$arr_selection[$str_id.'_text']['use_text'] = true;
						}
					}
					
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						if (!static::checkTypeConfigurationUserClearance($arr_type_set, $num_user_clearance, false, $object_sub_details_id, false, self::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($arr_project['types'], $arr_type_set, false, $object_sub_details_id, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
							
							$str_id = 'object_sub_details_'.$object_sub_details_id.'_';
							
							foreach ($arr_selection as $key => $value) {
								if (strpos($key, $str_id) !== false) {
									unset($arr_selection[$key]);
								}
							}									
						}
						
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
						if ($arr_selection[$str_id]) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
						}
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_date_start';
						if ($arr_selection[$str_id]) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
						}
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_date_end';
						if ($arr_selection[$str_id]) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
						}
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_date_chronology';
						if ($arr_selection[$str_id]) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
						}
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_location_ref_type_id';
						if ($arr_selection[$str_id]) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
						}
						if ($arr_selection[$str_id.'_id']) {
							$arr_selection[$str_id.'_id']['object_sub_details_id'] = $object_sub_details_id;
							$arr_selection[$str_id.'_id']['use_id'] = true;
						}
						$str_id = 'object_sub_details_'.$object_sub_details_id.'_location_geometry';
						if ($arr_selection[$str_id]) {
							$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
						}	
						
						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
							
							$str_id = 'object_sub_description_'.$object_sub_description_id;
							if (($arr_selection[$str_id] || $arr_selection[$str_id.'_id'] || $arr_selection[$str_id.'_text']) && (!static::checkTypeConfigurationUserClearance($arr_type_set, $num_user_clearance, false, $object_sub_details_id, $object_sub_description_id, self::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id, StoreCustomProject::ACCESS_PURPOSE_VIEW))) {
								unset($arr_selection[$str_id], $arr_selection[$str_id.'_id'], $arr_selection[$str_id.'_text']);
								continue;
							}
							
							if ($arr_selection[$str_id]) {
								$arr_selection[$str_id]['object_sub_details_id'] = $object_sub_details_id;
								$arr_selection[$str_id]['object_sub_description_id'] = $object_sub_description_id;
							}
							if ($arr_selection[$str_id.'_id']) {
								$arr_selection[$str_id.'_id']['object_sub_details_id'] = $object_sub_details_id;
								$arr_selection[$str_id.'_id']['object_sub_description_id'] = $object_sub_description_id;
								$arr_selection[$str_id.'_id']['use_id'] = true;
							}
							if ($arr_selection[$str_id.'_text']) {
								$arr_selection[$str_id.'_text']['object_sub_details_id'] = $object_sub_details_id;
								$arr_selection[$str_id.'_text']['object_sub_description_id'] = $object_sub_description_id;
								$arr_selection[$str_id.'_text']['use_text'] = true;
							}
						}
					}
				}
				
				$s_arr = [
					'filter' => ($s_arr ? (bool)$s_arr['filter'] : false),
					'nodegoat_id' => ($s_arr ? (bool)$s_arr['nodegoat_id'] : false),
					'id' => ($s_arr ? (bool)$s_arr['id'] : false),
					'name' => ($s_arr ? (bool)$s_arr['name'] : false),
					'sources' => ($s_arr ? (bool)$s_arr['sources'] : false),
					'analysis' => ($s_arr ? (bool)$s_arr['analysis'] : false),
					'selection' => $arr_selection
				];
				
				if (!$s_arr['nodegoat_id'] && !$s_arr['id'] && !$s_arr['name'] && !$s_arr['sources'] && !$s_arr['selection']) {
					unset($arr['types'][$source_path][$type_id]);
				}
			}
			
			if (!$arr_source_type) {
				unset($arr['types'][$source_path]);
			}
		}
		
		return $arr;
	}

	public static function parseTypeNetworkPaths($arr_paths) {
		
		if (!$arr_paths) {
			return [];
		}
		
		if (arrHasKeysRecursive('connection', $arr_paths)) {
			
			foreach ($arr_paths as $source_path => &$arr_value) {

				$arr_parsed = [];

				foreach ($arr_value as $str_key => $arr_settings) {
									
					if (!$arr_settings['connection']) {
						continue;
					}
					
					$arr_connection = str2Array($arr_settings['connection'], '-');

					if (isset($arr_connection[3])) {
						$s_arr_parsed =& $arr_parsed[$arr_connection[0]][$arr_connection[1]][$arr_connection[2]][$arr_connection[3]];
					} else {
						$s_arr_parsed =& $arr_parsed[$arr_connection[0]][$arr_connection[1]][$arr_connection[2]];
					}
					$s_arr_parsed = true;
					
					if (!$arr_settings['use_date']) {
						continue;
					}
					
					$s_arr_parsed = ['date' => []];
					
					$func_parse_chronology_statement = function($arr_date_settings) {
						
						$arr_date_settings['date_value'] = 1; // Use a fictive date for a viable parse
						
						$arr_chronology_statement = [];
						StoreTypeObjects::parseChronologyStatementJSON($arr_chronology_statement, $arr_date_settings);
						
						if ($arr_chronology_statement) {
							
							unset($arr_chronology_statement['date_value']);
							$arr_chronology_statement['id'] = $arr_date_settings['id'];
						}
						
						$arr_chronology_statement['source_target'] = ($arr_date_settings['source_target'] ?? null);
						
						if (!$arr_chronology_statement['source_target']) {
							
							$arr_split = str2Array($arr_chronology_statement['id'], '-');
							$arr_chronology_statement['source_target'] = $arr_split[0];
							$arr_chronology_statement['id'] = $arr_split[1];
						}
						
						return $arr_chronology_statement;
					};
					
					if ($arr_settings['date']['start']['id']) {
						
						$s_arr_parsed['date']['start'] = $func_parse_chronology_statement($arr_settings['date']['start']);
					}
					if ($arr_settings['date']['end']['id']) {
						
						$s_arr_parsed['date']['end'] = $func_parse_chronology_statement($arr_settings['date']['end']);
					}
				}
				unset($s_arr_parsed);
				
				if (!$arr_parsed) {
					
					unset($arr_paths[$source_path]);
					continue;
				}
				
				$arr_value = $arr_parsed;
			}
			unset($arr_value);
		} else {
			
			foreach ($arr_paths as $source_path => $arr_value) {
			
				if (!$arr_value) {
					unset($arr_paths[$source_path]);
				}
			}
		}

		return $arr_paths;
	}
}
