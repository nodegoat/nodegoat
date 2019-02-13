<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreType {
	
	protected $type_id = false;
	protected $mode = 'overwrite';
	
	protected $arr_type_set = [];
	protected $arr_types_all = [];
	
	protected $has_unresolved_ids = false;
	
	protected static $arr_types_touched = [];
	
	public static $arr_types_storage = []; // Static call caching
	protected static $arr_value_types = []; // Static call caching
	protected static $arr_types_object_path_storage = []; // Static call caching
	
    public function __construct($type_id = false) {

		$this->type_id = ($type_id ? (int)$this->getTypeID($type_id) : false);

		if ($this->type_id) {
			$this->arr_type_set = self::getTypeSet($type_id);
		}
    }
    
	public function setMode($mode = 'update') {
		
		// $mode = overwite OR update
		// $do_check = true OR false: perform object checks before update
		
		if ($mode !== null) {
			$this->mode = $mode;
		}
	}
    		
	public function store($arr_details, $arr_definitions, $arr_object_descriptions, $arr_object_subs_details) {
		
		$arr_details = arrParseRecursive($arr_details, 'trim');
		$arr_definitions = ($arr_definitions ? arrParseRecursive($arr_definitions, 'trim') : $arr_definitions);
		$arr_object_descriptions = ($arr_object_descriptions ? arrParseRecursive($arr_object_descriptions, 'trim') : $arr_object_descriptions);
		$arr_object_subs_details = ($arr_object_subs_details ? arrParseRecursive($arr_object_subs_details, 'trim') : $arr_object_subs_details);
		
		$is_new = ($this->type_id ? false : true);
		$is_reversal = ($is_new ? $arr_details['is_reversal'] : $this->arr_type_set['type']['is_reversal']);
		
		$this->mode = ($is_new ? 'overwrite' : $this->mode);
		
		// Check
		
		if (!$is_reversal && $this->mode == 'overwrite') {

			$has_name = false;
			
			foreach ($arr_object_descriptions as $key => &$value) {
				
				if (!$value['object_description_name']) {
					continue;
				}
				
				$value['object_description_ref_type_id'] = (in_array($value['object_description_value_type_base'], ['type', 'classification', 'reversal']) ? $value['object_description_ref_type_id'] : 0);
				
				/*if ($type_id && $value['object_description_ref_type_id'] == $type_id) { // Possible recursion in the name or search
					if ($value['object_description_in_name']) {
						error(getLabel('msg_model_recursion_in_name'));
					}
					if ($value['object_description_in_search']) {
						error(getLabel('msg_model_recursion_in_search'));
					}
				}*/
				
				if ($value['object_description_in_name'] && !($this->type_id && $value['object_description_ref_type_id'] == $this->type_id)) { // Self-reference does not count
					$has_name = true;
				}
			}
			unset($value);
			
			if (!$arr_details['use_object_name'] && !$has_name) {
				error(getLabel('msg_model_no_object_name'));
			}
		}
		
		if ($this->mode == 'overwrite' || ($this->mode == 'update' && $arr_details !== null)) {
			
			$type_mode = 0;
				
			if ($is_reversal) {
				
				$arr_details['use_object_name'] = true;
				$arr_details['object_name_in_overview'] = true;
				
				$type_mode = (int)$arr_details['reversal_mode'];
			}
		
			// Type
			
			if ($this->type_id) {
										
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPES')." SET
						name = '".DBFunctions::strEscape($arr_details['name'])."',
						".(Settings::get('domain_administrator_mode') ? "label = '".DBFunctions::strEscape($arr_details['label'])."'," : '')."
						color = '".str2Color($arr_details['color'])."',
						condition_id = ".(int)$arr_details['condition_id'].",
						use_object_name = ".DBFunctions::escapeAs($arr_details['use_object_name'], DBFunctions::TYPE_BOOLEAN).",
						object_name_in_overview = ".DBFunctions::escapeAs($arr_details['object_name_in_overview'], DBFunctions::TYPE_BOOLEAN).",
						mode = ".$type_mode."
					WHERE id = ".$this->type_id."
				");
			} else {
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPES')."
					(is_classification, is_reversal, mode, name, label, color, condition_id, use_object_name, object_name_in_overview, date)
						VALUES
					(
						".(int)$arr_details['is_classification'].",
						".(int)$is_reversal.",
						".$type_mode.",
						'".DBFunctions::strEscape($arr_details['name'])."',
						'".(Settings::get('domain_administrator_mode') ? DBFunctions::strEscape($arr_details['label']) : '')."',
						'".str2Color($arr_details['color'])."',
						0,
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
			
			$sort = 0;
			
			foreach ($arr_definitions as $value) {
				
				if (!$value['definition_name']) {
					
					if ($this->mode == 'update' && $value['definition_id']) {
						$arr_ids[] = $value['definition_id'];
					}
					
					continue;
				}
				
				if ($value['definition_id']) {
					
					$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')." SET
							name = '".DBFunctions::strEscape($value['definition_name'])."',
							text = '".DBFunctions::strEscape($value['definition_text'])."',
							".($this->mode == 'overwrite' ? "sort = ".$sort : "")."
						WHERE id = ".(int)$value['definition_id']."
					");
					
					if ($this->mode == 'overwrite') {
						$arr_ids[] = $value['definition_id'];
					}
				} else {
					
					$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')."
						(type_id, name, text, sort)
							VALUES
						(".$this->type_id.", '".DBFunctions::strEscape($value['definition_name'])."', '".DBFunctions::strEscape($value['definition_text'])."', ".$sort.")
					");
					
					if ($this->mode == 'overwrite') {
						$arr_ids[] = DB::lastInsertID();
					}
				}
				
				$sort++;
			}
		}
			
		$sql_select = ($this->mode == 'update' ? ($arr_ids ? "id IN (".implode(",", $arr_ids).")" : "") : ($arr_ids ? "id NOT IN (".implode(",", $arr_ids).")" : "TRUE"));
		
		if ($sql_select) {
				
			$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')."
				WHERE type_id = ".$this->type_id."
					AND ".$sql_select."
			");
		}
		
		// Object descriptions

		if ($is_reversal) {
			
			if ($this->mode == 'overwrite' || ($this->mode == 'update' && $arr_details !== null)) {
				
				$reversal_ref_type_id = 0;
				if ($type_mode == 0) {
					$reversal_ref_type_id = $this->getTypeID($arr_details['reversal_ref_type_id']);
				}
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')."
						(id_id, type_id, ref_type_id, in_name)
							VALUES
						('rc_ref_type_id', ".$this->type_id.", ".(int)$reversal_ref_type_id.", ".DBFunctions::escapeAs($reversal_ref_type_id, DBFunctions::TYPE_BOOLEAN).")
					".DBFunctions::onConflict('id_id, type_id', ['ref_type_id', 'in_name'])."
				");
			}				
		} else {
				
			$arr_ids = [];
			
			if ($arr_object_descriptions) {
				
				$sort = 0;
				
				foreach ($arr_object_descriptions as $value) {
					
					$value['object_description_id'] = $this->getTypeObjectDescriptionID($this->type_id, $value['object_description_id']);
					
					if (!$value['object_description_name']) {
						
						if ($this->mode == 'update' && $value['object_description_id']) {
							$arr_ids[] = $value['object_description_id'];
						}
						
						continue;
					}
					
					$object_description_value_type_base = $value['object_description_value_type_base'];
					$object_description_ref_type_id = $this->getTypeID($value['object_description_ref_type_id']);
					
					if (in_array($object_description_value_type_base, ['type', 'classification', 'reversal'])) {
						if (!$object_description_ref_type_id) {
							$object_description_value_type_base = '';
						}
					} else {
						$object_description_ref_type_id = 0;
					}

					$object_description_is_required = ($value['object_description_is_required'] && !in_array($object_description_value_type_base, ['reversal']) ? 1 : 0);
					
					$object_description_has_multi = (in_array($object_description_value_type_base, ['type', 'classification']) || in_array($object_description_value_type_base, ['', 'int', 'media', 'media_external', 'external']) ? $value['object_description_has_multi'] : 0);
					$object_description_has_multi = (($object_description_has_multi || $object_description_value_type_base == 'reversal') ? 1 : 0);
					
					$object_description_is_identifier = $value['object_description_is_identifier'];
					if ($object_description_is_identifier !== null) {
						$object_description_is_identifier = (in_array($object_description_value_type_base, ['', 'external']) ? $object_description_is_identifier : 0);
					}
					
					$object_description_value_type_options = $this->parseTypeObjectDescriptionValueTypeOptions($object_description_value_type_base, $object_description_ref_type_id, $value['object_description_value_type_options']);
					
					$clearance_view = ($value['object_description_clearance_view'] !== null ? (int)$value['object_description_clearance_view'] : null);
					$clearance_edit = ($value['object_description_clearance_edit'] !== null ? (int)$value['object_description_clearance_edit'] : null);
					$clearance_edit = ($clearance_view && $clearance_view > $clearance_edit ? $clearance_view : $clearance_edit);
					
					if ($value['object_description_id']) {
						
						$arr_convert_objects = [];
						
						if ($object_description_value_type_base != $this->arr_type_set['object_descriptions'][$value['object_description_id']]['object_description_value_type_base']) {
							
							$arr_convert_objects = $this->getConvertTypeObjectDefinitions($value['object_description_id'], $object_description_value_type_base);
						}
						
						$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." SET
								name = '".DBFunctions::strEscape($value['object_description_name'])."',
								value_type_base = '".DBFunctions::strEscape($object_description_value_type_base)."',
								value_type_options = '".DBFunctions::strEscape($object_description_value_type_options)."',
								is_required = ".DBFunctions::escapeAs($object_description_is_required, DBFunctions::TYPE_BOOLEAN).",
								is_unique = ".DBFunctions::escapeAs($value['object_description_is_unique'], DBFunctions::TYPE_BOOLEAN).",
								has_multi = ".DBFunctions::escapeAs($object_description_has_multi, DBFunctions::TYPE_BOOLEAN).",
								ref_type_id = ".(int)$object_description_ref_type_id.",
								in_name = ".DBFunctions::escapeAs($value['object_description_in_name'], DBFunctions::TYPE_BOOLEAN).",
								in_search = ".DBFunctions::escapeAs($value['object_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
								in_overview = ".DBFunctions::escapeAs($value['object_description_in_overview'], DBFunctions::TYPE_BOOLEAN)."
								".($object_description_is_identifier !== null ? 
									", is_identifier = ".DBFunctions::escapeAs($object_description_is_identifier, DBFunctions::TYPE_BOOLEAN)
								: "")."
								".($clearance_view !== null ? 
									", clearance_edit = ".$clearance_edit."
									, clearance_view = ".$clearance_view
								: "")."
								".($this->mode == 'overwrite' ? ", sort = ".$sort : "")."
							WHERE id = ".(int)$value['object_description_id']."
						");
						
						if ($this->mode == 'overwrite') {
							
							$arr_ids[] = $value['object_description_id'];
						}
						
						if ($arr_convert_objects) {
							
							timeLimit(300);
							self::$arr_types_storage[$this->type_id] = false; // Reload type set on next call
							
							$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
				
							foreach ($arr_convert_objects as $object_id => $arr_object) {
								
								$storage->setObjectID($object_id);
								$storage->store([], $arr_object['object_definitions'], []);
							}
							
							$storage->commit(true);
						}
					} else {
						
						$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')."
							(type_id, name, value_type_base, value_type_options, is_required, is_unique, has_multi, ref_type_id, in_name, in_search, in_overview, is_identifier, clearance_edit, clearance_view, sort)
								VALUES
							(".$this->type_id.",
								'".DBFunctions::strEscape($value['object_description_name'])."',
								'".DBFunctions::strEscape($object_description_value_type_base)."',
								'".DBFunctions::strEscape($object_description_value_type_options)."',
								".DBFunctions::escapeAs($object_description_is_required, DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($value['object_description_is_unique'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($object_description_has_multi, DBFunctions::TYPE_BOOLEAN).",
								".(int)$object_description_ref_type_id.",
								".DBFunctions::escapeAs($value['object_description_in_name'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($value['object_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($value['object_description_in_overview'], DBFunctions::TYPE_BOOLEAN).",
								".DBFunctions::escapeAs($object_description_is_identifier, DBFunctions::TYPE_BOOLEAN).",
								".(int)$clearance_edit.",
								".(int)$clearance_view.",
								".$sort."
							)
						");
						
						if ($this->mode == 'overwrite') {
							
							$arr_ids[] = DB::lastInsertID();
						}
					}
					
					$sort++;
				}
			}
			
			$sql_select = ($this->mode == 'update' ? ($arr_ids ? "id IN (".implode(",", $arr_ids).")" : "") : ($arr_ids ? "id NOT IN (".implode(",", $arr_ids).")" : "TRUE"));
			
			if ($sql_select) {
					
				$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')."
					WHERE type_id = ".$this->type_id."
						AND ".$sql_select."
				");
			}
		}
		
		// Object sub details

		$arr_ids = [];
		
		if ($arr_object_subs_details) {
			
			$sort = 0;
			
			foreach ($arr_object_subs_details as $arr_object_sub_details) {
				
				$arr_object_sub_details['object_sub_details']['object_sub_details_id'] = $this->getTypeObjectSubDetailsID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_id']);
						
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_name']) {
					
					if ($this->mode == 'update' && $arr_object_sub_details['object_sub_details']['object_sub_details_id']) {
						$arr_ids[] = $arr_object_sub_details['object_sub_details']['object_sub_details_id'];
					}
					
					continue;
				}
				
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'] || !$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']) {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id_locked']);				
				}
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'] || !$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']) {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']);
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage']);					
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage'] != 'object_sub_details') {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id']);
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage'] != 'object_sub_description') {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id']);
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage'] != 'object_description') {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']);
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage'] == 'object' && $this->getTypeID($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']) != $this->type_id) {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage']);
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_useage'] != 'object_sub_details') {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id']);
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_useage'] != 'object_sub_description') {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_description_id']);
				}
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_useage'] != 'object_description') {
					unset($arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_description_id']);
				}
				
				$clearance_view = ($arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] !== null ? (int)$arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] : null);
				$clearance_edit = ($arr_object_sub_details['object_sub_details']['object_sub_details_clearance_edit'] !== null ? (int)$arr_object_sub_details['object_sub_details']['object_sub_details_clearance_edit'] : null);
				$clearance_edit = ($clearance_view && $clearance_view > $clearance_edit ? $clearance_view : $clearance_edit);
				
				$object_sub_details_date_use_object_sub_details_id = $this->getTypeObjectSubDetailsID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id']);
				$object_sub_details_date_use_object_sub_description_id = $this->getTypeObjectSubDescriptionID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_id'], $arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_description_id']);
				$object_sub_details_date_use_object_description_id = $this->getTypeObjectDescriptionID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_description_id']);
				$object_sub_details_location_ref_type_id = $this->getTypeID($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']);
				$object_sub_details_location_ref_object_sub_details_id = $this->getTypeObjectSubDetailsID($object_sub_details_location_ref_type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id']);
				$object_sub_details_location_use_object_sub_details_id = $this->getTypeObjectSubDetailsID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id']);
				$object_sub_details_location_use_object_sub_description_id = $this->getTypeObjectSubDescriptionID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_id'], $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id']);
				$object_sub_details_location_use_object_description_id = $this->getTypeObjectDescriptionID($this->type_id, $arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']);

				$object_sub_details_has_date = true;
				$object_sub_details_is_date_range = ($arr_object_sub_details['object_sub_details']['object_sub_details_date_type'] == 'period' || $arr_object_sub_details['object_sub_details']['object_sub_details_is_date_range']);
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_date_type'] == 'none') {
					$object_sub_details_has_date = false;
					$object_sub_details_is_date_range = false;
				}
				
				$object_sub_details_has_location = true;
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_type'] == 'none') {
					$object_sub_details_has_location = false;
				}
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_id']) {
					
					$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." SET
							name = '".DBFunctions::strEscape($arr_object_sub_details['object_sub_details']['object_sub_details_name'])."',
							is_unique = ".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_is_unique'], DBFunctions::TYPE_BOOLEAN).",
							is_required = ".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN).",
							".($clearance_view !== null ? 
								"clearance_edit = ".$clearance_edit.",
								clearance_view = ".$clearance_view.","
							: "")."
							has_date = ".DBFunctions::escapeAs($object_sub_details_has_date, DBFunctions::TYPE_BOOLEAN).",
							is_date_range = ".DBFunctions::escapeAs($object_sub_details_is_date_range, DBFunctions::TYPE_BOOLEAN).",
							date_use_object_sub_details_id = ".(int)$object_sub_details_date_use_object_sub_details_id.",
							date_use_object_sub_description_id = ".(int)$object_sub_details_date_use_object_sub_description_id.",
							date_use_object_description_id = ".(int)$object_sub_details_date_use_object_description_id.",
							has_location = ".DBFunctions::escapeAs($object_sub_details_has_location, DBFunctions::TYPE_BOOLEAN).",
							location_ref_only = ".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_only'], DBFunctions::TYPE_BOOLEAN).",
							location_ref_type_id = ".(int)$object_sub_details_location_ref_type_id.",
							location_ref_type_id_locked = ".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							location_ref_object_sub_details_id = ".(int)$object_sub_details_location_ref_object_sub_details_id.",
							location_ref_object_sub_details_id_locked = ".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							location_use_object_sub_details_id = ".(int)$object_sub_details_location_use_object_sub_details_id.",
							location_use_object_sub_description_id = ".(int)$object_sub_details_location_use_object_sub_description_id.",
							location_use_object_description_id = ".(int)$object_sub_details_location_use_object_description_id.",
							location_use_object_id = ".($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage'] == 'object' ? "TRUE" : "FALSE")."
							".($this->mode == 'overwrite' ? ", sort = ".$sort : "")."
						WHERE id = ".(int)$arr_object_sub_details['object_sub_details']['object_sub_details_id']."
					");
					
					$object_sub_details_id = $arr_object_sub_details['object_sub_details']['object_sub_details_id'];
				} else {
					
					$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')."
						(type_id, name, is_unique, is_required, clearance_edit, clearance_view,
							has_date, is_date_range, date_use_object_sub_details_id, date_use_object_sub_description_id, date_use_object_description_id,
							has_location, location_ref_only, location_ref_type_id, location_ref_type_id_locked, location_ref_object_sub_details_id, location_ref_object_sub_details_id_locked, location_use_object_sub_details_id, location_use_object_sub_description_id, location_use_object_description_id, location_use_object_id,
							sort
						)
							VALUES
						(".$this->type_id.",
							'".DBFunctions::strEscape($arr_object_sub_details['object_sub_details']['object_sub_details_name'])."',
							".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_is_unique'], DBFunctions::TYPE_BOOLEAN).",
							".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN).",
							".(int)$clearance_edit.",
							".(int)$clearance_view.",
							".DBFunctions::escapeAs($object_sub_details_has_date, DBFunctions::TYPE_BOOLEAN).",
							".DBFunctions::escapeAs($object_sub_details_is_date_range, DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_date_use_object_sub_details_id.",
							".(int)$object_sub_details_date_use_object_sub_description_id.",
							".(int)$object_sub_details_date_use_object_description_id.",
							".DBFunctions::escapeAs($object_sub_details_has_location, DBFunctions::TYPE_BOOLEAN).",
							".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_only'], DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_location_ref_type_id.",
							".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_location_ref_object_sub_details_id.",
							".DBFunctions::escapeAs($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id_locked'], DBFunctions::TYPE_BOOLEAN).",
							".(int)$object_sub_details_location_use_object_sub_details_id.",
							".(int)$object_sub_details_location_use_object_sub_description_id.",
							".(int)$object_sub_details_location_use_object_description_id.",
							".($arr_object_sub_details['object_sub_details']['object_sub_details_location_useage'] == 'object' ? "TRUE" : "FALSE").",
							".$sort."
						)
					");
					
					$object_sub_details_id = DB::lastInsertID();
				}
				
				if ($this->mode == 'overwrite') {
										
					$arr_ids[] = $object_sub_details_id;
				}
				
				// Object sub descriptions

				$arr_sub_description_ids = [];
				
				if ($arr_object_sub_details['object_sub_descriptions']) {
					
					$sort_sub = 0;
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $value) {
						
						$value['object_sub_description_id'] = $this->getTypeObjectSubDescriptionID($this->type_id, $object_sub_details_id, $value['object_sub_description_id']);
						
						if (!$value['object_sub_description_name']) {
							
							if ($this->mode == 'update' && $value['object_sub_description_id']) {
								$arr_sub_description_ids[] = $value['object_sub_description_id'];
							}

							continue;
						}
						
						$object_sub_description_value_type_base = $value['object_sub_description_value_type_base'];
						$object_sub_description_ref_type_id = $this->getTypeID($value['object_sub_description_ref_type_id']);
						
						if (in_array($object_sub_description_value_type_base, ['type', 'classification', 'reversal'])) {
							if (!$object_sub_description_ref_type_id) {
								$object_sub_description_value_type_base = '';
							}
						} else {
							$object_sub_description_ref_type_id = 0;
						}
						
						$object_sub_description_is_required = ($value['object_sub_description_is_required'] && !in_array($object_sub_description_value_type_base, ['reversal']) ? 1 : 0);
						
						$object_sub_description_has_multi = (($value['object_sub_description_has_multi'] || $object_sub_description_value_type_base == 'reversal') ? 1 : 0);
							
						$object_sub_description_use_object_description_id = ($object_sub_description_value_type_base == 'object_description' ? $value['object_sub_description_use_object_description_id'] : '');
						
						$object_sub_description_value_type_options = $this->parseTypeObjectDescriptionValueTypeOptions($object_sub_description_value_type_base, $object_sub_description_ref_type_id, $value['object_sub_description_value_type_options']);
						
						$clearance_view = ($value['object_sub_description_clearance_view'] !== null ? (int)$value['object_sub_description_clearance_view'] : null);
						$clearance_edit = ($value['object_sub_description_clearance_edit'] !== null ? (int)$value['object_sub_description_clearance_edit'] : null);
						$clearance_edit = ($clearance_view && $clearance_view > $clearance_edit ? $clearance_view : $clearance_edit);
						
						if ($value['object_sub_description_id']) {
							
							$arr_convert_objects = [];
						
							if ($object_sub_description_value_type_base != $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$value['object_sub_description_id']]['object_sub_description_value_type_base']) {
								
								$arr_convert_objects = $this->getConvertTypeObjectSubDefinitions($object_sub_details_id, $value['object_sub_description_id'], $object_sub_description_value_type_base);
							}
							
							$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." SET
									name = '".DBFunctions::strEscape($value['object_sub_description_name'])."',
									value_type_base = '".DBFunctions::strEscape($object_sub_description_value_type_base)."',
									value_type_options = '".DBFunctions::strEscape($object_sub_description_value_type_options)."',
									is_required = ".DBFunctions::escapeAs($object_sub_description_is_required, DBFunctions::TYPE_BOOLEAN).",
									use_object_description_id = ".(int)$object_sub_description_use_object_description_id.",
									ref_type_id = ".(int)$object_sub_description_ref_type_id.",
									in_name = ".DBFunctions::escapeAs($value['object_sub_description_in_name'], DBFunctions::TYPE_BOOLEAN).",
									in_search = ".DBFunctions::escapeAs($value['object_sub_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
									in_overview = ".DBFunctions::escapeAs($value['object_sub_description_in_overview'], DBFunctions::TYPE_BOOLEAN)."
									".($clearance_view !== null ? 
										", clearance_edit = ".$clearance_edit."
										, clearance_view = ".$clearance_view
									: "")."
									".($this->mode == 'overwrite' ? ", sort = ".$sort_sub : "")."
								WHERE id = ".(int)$value['object_sub_description_id']."
							");
							
							if ($this->mode == 'overwrite') {
								
								$arr_sub_description_ids[] = $value['object_sub_description_id'];
							}
							
							if ($arr_convert_objects) {
						
								timeLimit(300);
								self::$arr_types_storage[$this->type_id] = false; // Reload type set on next call
								
								$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
						
								foreach ($arr_convert_objects as $object_id => $arr_object) {
																		
									$storage->setObjectID($object_id);
									$storage->store([], [], $arr_object['object_subs']);
								}
								
								$storage->commit(true);
							}
						} else {
							
							$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')."
								(object_sub_details_id, name, value_type_base, value_type_options, is_required, use_object_description_id, ref_type_id, in_name, in_search, in_overview, clearance_edit, clearance_view, sort)
									VALUES
								(".$object_sub_details_id.",
									'".DBFunctions::strEscape($value['object_sub_description_name'])."',
									'".DBFunctions::strEscape($object_sub_description_value_type_base)."',
									'".DBFunctions::strEscape($object_sub_description_value_type_options)."',
									".DBFunctions::escapeAs($object_sub_description_is_required, DBFunctions::TYPE_BOOLEAN).",
									".(int)$object_sub_description_use_object_description_id.",
									".(int)$object_sub_description_ref_type_id.",
									".DBFunctions::escapeAs($value['object_sub_description_in_name'], DBFunctions::TYPE_BOOLEAN).",
									".DBFunctions::escapeAs($value['object_sub_description_in_search'], DBFunctions::TYPE_BOOLEAN).",
									".DBFunctions::escapeAs($value['object_sub_description_in_overview'], DBFunctions::TYPE_BOOLEAN).",
									".(int)$clearance_edit.",
									".(int)$clearance_view.",
									".$sort_sub."
								)
							");
							
							if ($this->mode == 'overwrite') {
								
								$arr_sub_description_ids[] = DB::lastInsertID();
							}
						}
						
						$sort_sub++;
					}
				}
				
				$sql_select = ($this->mode == 'update' ? ($arr_sub_description_ids ? "id IN (".implode(",", $arr_sub_description_ids).")" : "") : ($arr_sub_description_ids ? "id NOT IN (".implode(",", $arr_sub_description_ids).")" : "TRUE"));
				
				if ($sql_select) {
					
					$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')."
						WHERE object_sub_details_id = ".$object_sub_details_id."
							AND ".$sql_select."
					");
				}
				
				$sort++;
			}
		}
		
		if ($this->mode == 'update') {
			$sql_select = ($arr_ids ? "IN (".implode(',', $arr_ids).")" : '');
		} else {
			$sql_select = ($arr_ids ? "NOT IN (".implode(',', $arr_ids).")" : '');
		}
		
		if ($this->mode == 'overwrite' || ($this->mode == 'update' && $sql_select)) {

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
		
		self::$arr_types_storage[$this->type_id] = false;	
		
		self::$arr_types_touched[$this->type_id] = $this->type_id;

		return $this->type_id;
	}
	
	protected function parseTypeObjectDescriptionValueTypeOptions($value_type_base, $ref_type_id, $arr_value_type_options) {
		
		$str_value_type_options = '';
			
		if ($arr_value_type_options) {
			
			if (!is_array($arr_value_type_options)) {
				$arr_value_type_options = json_decode($arr_value_type_options, true);
			}
			
			$value_default = $arr_value_type_options['default'];
			
			if ($value_default) {
					
				if ($ref_type_id) {
					
					$value_default = arrParseRecursive($value_default, 'int');
				} else {

					$default_value = StoreTypeObjects::formatToSQLValue($value_type_base, $value_default);
					
					if ($value_default === false || $value_default === '') {
						$value_default = null;
					}
				}
				
				if ($object_description_has_multi) {
					$value_default = array_unique(array_filter((array)$value_default));
				}
				
				$arr_value_type_options['default'] = $value_default;
			}
			
			if ($arr_value_type_options) {
				$str_value_type_options = json_encode($arr_value_type_options);
			}
		}
		
		return $str_value_type_options;
	}
	
	public function getConvertTypeObjectDefinitions($object_description_id, $to_value_type_base) {
		
		$value_type_base = $this->arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type_base'];
		
		switch ($to_value_type_base) {
			case 'media';
				$convert = true;
				break;
			case 'text';
			case 'text_layout';
			case 'text_tags';
				if ($value_type_base == '') {
					$convert = true;
				}
				break;
			case '';
				if ($value_type_base == 'text' || $value_type_base == 'text_layout' || $value_type_base == 'text_tags') {
					$convert = true;
				}
				break;
		}
		
		if (!$convert) {
			return;
		}
	
		$filter = new FilterTypeObjects($this->type_id, 'set');
		$filter->setSelection(['object' => [], 'object_sub_details' => [], 'object_descriptions' => [$object_description_id => $object_description_id]]);
		$filter->setVersioning('added');
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
		
		switch ($to_value_type_base) {
			case 'media';
				$convert = true;
				break;
		}
		
		if (!$convert) {
			return;
		}
	
		$filter = new FilterTypeObjects($this->type_id, 'set');
		$filter->setSelection(['object' => [], 'object_sub_details' => [$object_sub_details_id => ['object_sub_descriptions' => [$object_sub_description_id => $object_sub_description_id]]], 'object_descriptions' => []]);
		$filter->setVersioning('added');
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
		$sort = 0;
	
		$func_insert_object_description_name_ids = function($ref_type_id, $org_object_description_id, $org_object_sub_details_id, $arr_path_types) use (&$cur_type_id, &$sort, &$func_insert_object_description_name_ids, $path) {
			
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
						".$sort."
					)
				");
				
				$sort++;
			}
			
			$res = DB::query("SELECT nodegoat_to_des.*
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
				WHERE nodegoat_to_des.type_id = ".$ref_type_id."
					AND nodegoat_to_des.in_".($path == 'name' ? 'name' : 'search')." = TRUE
				ORDER BY nodegoat_to_des.type_id, sort
			");
			
			while($row = $res->fetchAssoc()) {
							
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
						".$sort."
					)
				");
					
				$sort++;
					
				if ($row['ref_type_id']) {
					
					$func_insert_object_description_name_ids($row['ref_type_id'], $row['id'], 0, $arr_path_types);
				}
			}
			
			$res = DB::query("SELECT nodegoat_to_det.type_id, nodegoat_tos_des.*
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_to_det
					JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_to_det.id)
				WHERE nodegoat_to_det.type_id = ".$ref_type_id."
					AND nodegoat_tos_des.in_".($path == 'name' ? 'name' : 'search')." = TRUE
				ORDER BY nodegoat_to_det.type_id, sort
			");
			
			while($row = $res->fetchAssoc()) {
							
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
						".$sort."
					)
				");
				
				$sort++;
				
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
			$sort = 0;
			
			$func_insert_object_description_name_ids($cur_type_id, 0, 0, $arr_path_types);
		}
	}
	
	public function delType() {

		if (!$this->type_id) {
			return;
		}
		
		StoreTypeObjects::clearTypeObjects($this->type_id);
		
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
	
	// Static calls
	
	public static function getTypes($type_id = false, $type = false) {
	
		$arr = [];
		
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
				".($type == 'reversal' ? "AND is_reversal = TRUE" : "")."
				".($type == 'classification' ? "AND is_classification = TRUE" : "")."
				".($type == 'type' ? "AND (is_reversal = FALSE AND is_classification = FALSE)" : "")."
				".($sql_type_ids ? "
					AND nodegoat_t.id IN (".$sql_type_ids.")
				ORDER BY ".DBFunctions::fieldToPosition('id', $arr_type_ids) : "")
		);
								 
		while ($arr_row = $res->fetchAssoc()) {
			
			if (Settings::get('domain_administrator_mode')) {
				
				$arr_row['name_raw'] = $arr_row['name'];
				if ($arr_row['label']) {
					$arr_row['name'] = $arr_row['label'].' '.$arr_row['name'];
				}
			}
			
			$arr_row['is_classification'] = DBFunctions::unescapeAs($arr_row['is_classification'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['is_reversal'] = DBFunctions::unescapeAs($arr_row['is_reversal'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		}		

		return ($type_id && is_numeric($type_id) ? current($arr) : $arr);
	}

	public static function getTypeSet($type_id) {
		
		if (self::$arr_types_storage[$type_id]) {
			return self::$arr_types_storage[$type_id];
		}
	
		$arr = ['definitions' => [], 'object_descriptions' => [], 'object_sub_details' => []];
		
		$arr_res = DB::queryMulti("
			SELECT nodegoat_t.*,
					nodegoat_t_des.id AS definition_id, nodegoat_t_des.name AS definition_name, nodegoat_t_des.text AS definition_text,
					nodegoat_to_des.id AS object_description_id, nodegoat_to_des.id_id AS object_description_id_id, nodegoat_to_des.name AS object_description_name,
						nodegoat_to_des.value_type_base AS object_description_value_type_base,
						nodegoat_to_des.value_type_options AS object_description_value_type_options,
						CASE
							WHEN nodegoat_to_des.value_type_base = 'reversal' THEN
								CASE
									WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode = 1) THEN 'reversed_collection'
									ELSE 'reversed_classification'
								END
							WHEN nodegoat_to_des.value_type_base != '' THEN nodegoat_to_des.value_type_base
							WHEN nodegoat_to_des.id_id = 'rc_ref_type_id' THEN 'id_id'
							ELSE ''
						END AS object_description_value_type,
						CASE 
							WHEN nodegoat_to_des.value_type_base = 'text_tags' THEN TRUE
							WHEN nodegoat_to_des.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode = 1) THEN TRUE
							ELSE FALSE
						END AS object_description_is_dynamic,
						nodegoat_to_des.is_required AS object_description_is_required, nodegoat_to_des.is_unique AS object_description_is_unique, nodegoat_to_des.has_multi AS object_description_has_multi, nodegoat_to_des.ref_type_id AS object_description_ref_type_id, nodegoat_to_des.in_name AS object_description_in_name, nodegoat_to_des.in_search AS object_description_in_search, nodegoat_to_des.in_overview AS object_description_in_overview, nodegoat_to_des.is_identifier AS object_description_is_identifier, nodegoat_to_des.clearance_edit AS object_description_clearance_edit, nodegoat_to_des.clearance_view AS object_description_clearance_view
				FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_DEFINITIONS')." nodegoat_t_des ON (nodegoat_t_des.type_id = nodegoat_t.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des ON (nodegoat_to_des.type_id = nodegoat_t.id)
				WHERE nodegoat_t.id = ".(int)$type_id."
				ORDER BY nodegoat_t_des.sort, nodegoat_to_des.sort;
					
			SELECT nodegoat_t.id,
					nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_det.name AS object_sub_details_name, nodegoat_tos_det.is_unique AS object_sub_details_is_unique, nodegoat_tos_det.is_required AS object_sub_details_is_required,
						nodegoat_tos_det.clearance_edit AS object_sub_details_clearance_edit, nodegoat_tos_det.clearance_view AS object_sub_details_clearance_view,
						nodegoat_tos_det.has_date AS object_sub_details_has_date, nodegoat_tos_det.is_date_range AS object_sub_details_is_date_range,
							nodegoat_tos_det.date_use_object_sub_details_id AS object_sub_details_date_use_object_sub_details_id, nodegoat_tos_det.date_use_object_sub_description_id AS object_sub_details_date_use_object_sub_description_id, nodegoat_tos_det.date_use_object_description_id AS object_sub_details_date_use_object_description_id,
						nodegoat_tos_det.has_location AS object_sub_details_has_location,
							nodegoat_tos_det.location_ref_only AS object_sub_details_location_ref_only, nodegoat_tos_det.location_ref_type_id AS object_sub_details_location_ref_type_id, nodegoat_tos_det.location_ref_type_id_locked AS object_sub_details_location_ref_type_id_locked, nodegoat_tos_det.location_ref_object_sub_details_id AS object_sub_details_location_ref_object_sub_details_id, nodegoat_tos_det.location_ref_object_sub_details_id_locked AS object_sub_details_location_ref_object_sub_details_id_locked,
								nodegoat_tos_det.location_use_object_sub_details_id AS object_sub_details_location_use_object_sub_details_id, nodegoat_tos_det.location_use_object_sub_description_id AS object_sub_details_location_use_object_sub_description_id, nodegoat_tos_det.location_use_object_description_id AS object_sub_details_location_use_object_description_id, nodegoat_tos_det.location_use_object_id AS object_sub_details_location_use_object_id,
						nodegoat_tos_det.sort AS object_sub_details_sort,
					nodegoat_tos_des.id AS object_sub_description_id, nodegoat_tos_des.name AS object_sub_description_name,
					nodegoat_tos_des.value_type_base AS object_sub_description_value_type_base,
					nodegoat_tos_des.value_type_options AS object_sub_description_value_type_options,
					CASE
						WHEN nodegoat_tos_des.value_type_base = 'reversal' THEN
							CASE
								WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode = 1) THEN 'reversed_collection'
								ELSE 'reversed_classification'
							END
						ELSE nodegoat_tos_des.value_type_base
					END AS object_sub_description_value_type,
					CASE 
						WHEN nodegoat_tos_des.value_type_base = 'text_tags' THEN TRUE
						WHEN nodegoat_tos_des.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode = 1) THEN TRUE
						ELSE FALSE
					END AS object_sub_description_is_dynamic,
					nodegoat_tos_des.is_required AS object_sub_description_is_required, nodegoat_tos_des.use_object_description_id AS object_sub_description_use_object_description_id, nodegoat_tos_des.ref_type_id AS object_sub_description_ref_type_id, nodegoat_tos_des.in_name AS object_sub_description_in_name, nodegoat_tos_des.in_search AS object_sub_description_in_search, nodegoat_tos_des.in_overview AS object_sub_description_in_overview, nodegoat_tos_des.clearance_edit AS object_sub_description_clearance_edit, nodegoat_tos_des.clearance_view AS object_sub_description_clearance_view
				FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.type_id = nodegoat_t.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_tos_det.id)
				WHERE nodegoat_t.id = ".(int)$type_id."
				ORDER BY nodegoat_tos_det.sort, nodegoat_tos_des.sort;
		");
		
		while ($arr_row = $arr_res[0]->fetchAssoc()) {
			
			if (!$arr['type']) {
				
				if (Settings::get('domain_administrator_mode')) {
					
					$arr_row['name_raw'] = $arr_row['name'];
					if ($arr_row['label']) {
						$arr_row['name'] = $arr_row['label'].' '.$arr_row['name'];
					}
				}
				
				$arr_row['is_classification'] = DBFunctions::unescapeAs($arr_row['is_classification'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['is_reversal'] = DBFunctions::unescapeAs($arr_row['is_reversal'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['use_object_name'] = DBFunctions::unescapeAs($arr_row['use_object_name'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_name_in_overview'] = DBFunctions::unescapeAs($arr_row['object_name_in_overview'], DBFunctions::TYPE_BOOLEAN);
				
				$arr['type'] = $arr_row;
			}
			
			if ($arr_row['definition_id']) {
				$arr['definitions'][$arr_row['definition_id']] = $arr_row;
			}

			if ($arr_row['object_description_id']) {
				
				$arr_row['object_description_is_required'] = DBFunctions::unescapeAs($arr_row['object_description_is_required'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_description_is_unique'] = DBFunctions::unescapeAs($arr_row['object_description_is_unique'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_description_has_multi'] = DBFunctions::unescapeAs($arr_row['object_description_has_multi'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_description_in_name'] = DBFunctions::unescapeAs($arr_row['object_description_in_name'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_description_in_search'] = DBFunctions::unescapeAs($arr_row['object_description_in_search'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_description_in_overview'] = DBFunctions::unescapeAs($arr_row['object_description_in_overview'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_description_is_identifier'] = DBFunctions::unescapeAs($arr_row['object_description_is_identifier'], DBFunctions::TYPE_BOOLEAN);
				
				$arr_row['object_description_value_type_options'] = ($arr_row['object_description_value_type_options'] ? json_decode($arr_row['object_description_value_type_options'], true) : []);
				
				$arr_row['object_description_is_dynamic'] = DBFunctions::unescapeAs($arr_row['object_description_is_dynamic'], DBFunctions::TYPE_BOOLEAN);
				
				$arr['object_descriptions'][$arr_row['object_description_id']] = $arr_row;
			}
			
			if ($arr_row['object_description_id_id']) {
				
				if ($arr_row['object_description_id_id'] == 'rc_ref_type_id') {
					$arr['object_descriptions'][$arr_row['object_description_id']]['object_description_name'] = getLabel('lbl_reversed_classification_reference');
				}
				
				$arr['object_description_ids'][$arr_row['object_description_id_id']] = $arr_row['object_description_id'];
			}
		}
		
		while ($arr_row = $arr_res[1]->fetchAssoc()) {

			if (!$arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_details']) {
				
				$arr_row['object_sub_details_is_unique'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_unique'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_is_required'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_has_date'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_date'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_is_date_range'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_date_range'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_has_location'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_location'], DBFunctions::TYPE_BOOLEAN);
				$arr_row['object_sub_details_location_ref_only'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_only'], DBFunctions::TYPE_BOOLEAN);
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
				
				$arr_row['object_sub_description_value_type_options'] = ($arr_row['object_sub_description_value_type_options'] ? json_decode($arr_row['object_sub_description_value_type_options'], true) : []);

				$arr_row['object_sub_description_is_dynamic'] = DBFunctions::unescapeAs($arr_row['object_sub_description_is_dynamic'], DBFunctions::TYPE_BOOLEAN);
				
				$arr['object_sub_details'][$arr_row['object_sub_details_id']]['object_sub_descriptions'][$arr_row['object_sub_description_id']] = $arr_row;
			
				if ($arr_row['object_sub_description_use_object_description_id']) {
					
					$ref_type_id = $arr['object_descriptions'][$arr_row['object_sub_description_use_object_description_id']]['object_description_ref_type_id'];
					
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
			$arr['object_description_'.$object_description_id] = ['id' => 'object_description_'.$object_description_id, 'name' => $arr_object_description['object_description_name']]; 
		}
		foreach ($arr_type_set['object_sub_details'] as $arr_object_sub_details_id => $arr_object_sub_details) {
			
			$arr['object_sub_details_'.$arr_object_sub_details_id.'_id'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_id', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].']']; 
			
			if (keyIsUncontested('object_sub_details_date', $arr_options)) {
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_date'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_description_id']) {
					
					if ($arr_options['purpose'] == 'filter') {
						$arr['object_sub_details_'.$arr_object_sub_details_id.'_date'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_date', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_date')];
					} else {
						$arr['object_sub_details_'.$arr_object_sub_details_id.'_date_start'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_date_start', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_date_start')]; 
						if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_range']) {
							$arr['object_sub_details_'.$arr_object_sub_details_id.'_date_end'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_date_end', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_date_end')];
						}
					}
				}
			}
			if (keyIsUncontested('object_sub_details_location', $arr_options)) {
				
				if ($arr_object_sub_details['object_sub_details']['object_sub_details_has_location'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']) {
					
					if ($arr_options['purpose'] == 'select') {
						$arr['object_sub_details_'.$arr_object_sub_details_id.'_location'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_location', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location')]; 
					} else {
						if ($arr_options['purpose'] == 'filter') {
							$arr['object_sub_details_'.$arr_object_sub_details_id.'_location_reference'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_location_reference', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location_reference')]; 
						} else {
							$arr['object_sub_details_'.$arr_object_sub_details_id.'_location_ref_type_id'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_location_ref_type_id', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location_reference')]; 
						}
						if (!$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_only']) {
							$arr['object_sub_details_'.$arr_object_sub_details_id.'_location_geometry'] = ['id' => 'object_sub_details_'.$arr_object_sub_details_id.'_location_geometry', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.getLabel('lbl_location').' '.getLabel('lbl_geometry')];
						}
					}
				}
			}
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
				$arr['object_sub_description_'.$object_sub_description_id] = ['id' => 'object_sub_description_'.$object_sub_description_id, 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] '.$arr_object_sub_description['object_sub_description_name']]; 
			}
		}

		return $arr;
	}
	
	public static function getTypeObjectSubsDetails($type_id, $object_sub_details_id = 0) {
	
		$arr = [];

		$res = DB::query("SELECT
			nodegoat_tos_det.id,
			nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_det.name AS object_sub_details_name, nodegoat_tos_det.is_unique AS object_sub_details_is_unique, nodegoat_tos_det.is_required AS object_sub_details_is_required,
				nodegoat_tos_det.clearance_edit AS object_sub_details_clearance_edit, nodegoat_tos_det.clearance_view AS object_sub_details_clearance_view,
				nodegoat_tos_det.has_date AS object_sub_details_has_date, nodegoat_tos_det.is_date_range AS object_sub_details_is_date_range,
					nodegoat_tos_det.date_use_object_sub_details_id AS object_sub_details_date_use_object_sub_details_id, nodegoat_tos_det.date_use_object_sub_description_id AS object_sub_details_date_use_object_sub_description_id, nodegoat_tos_det.date_use_object_description_id AS object_sub_details_date_use_object_description_id,
				nodegoat_tos_det.has_location AS object_sub_details_has_location,
					nodegoat_tos_det.location_ref_only AS object_sub_details_location_ref_only, nodegoat_tos_det.location_ref_type_id AS object_sub_details_location_ref_type_id, nodegoat_tos_det.location_ref_type_id_locked AS object_sub_details_location_ref_type_id_locked, nodegoat_tos_det.location_ref_object_sub_details_id AS object_sub_details_location_ref_object_sub_details_id, nodegoat_tos_det.location_ref_object_sub_details_id_locked AS object_sub_details_location_ref_object_sub_details_id_locked,
						nodegoat_tos_det.location_use_object_sub_details_id AS object_sub_details_location_use_object_sub_details_id, nodegoat_tos_det.location_use_object_sub_description_id AS object_sub_details_location_use_object_sub_description_id, nodegoat_tos_det.location_use_object_description_id AS object_sub_details_location_use_object_description_id, nodegoat_tos_det.location_use_object_id AS object_sub_details_location_use_object_id,
				nodegoat_tos_det.sort AS object_sub_details_sort
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
			WHERE type_id = COALESCE((SELECT ref_type_id FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." WHERE type_id = ".(int)$type_id." AND id_id = 'rc_ref_type_id'), ".(int)$type_id.")
				".($object_sub_details_id ? "AND id = ".(int)$object_sub_details_id."" : "")."
			ORDER BY id
		");
							
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['object_sub_details_is_unique'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_unique'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_is_required'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_required'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_has_date'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_date'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_is_date_range'] = DBFunctions::unescapeAs($arr_row['object_sub_details_is_date_range'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_has_location'] = DBFunctions::unescapeAs($arr_row['object_sub_details_has_location'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_location_ref_only'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_only'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_location_ref_type_id_locked'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_type_id_locked'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_location_ref_object_sub_details_id_locked'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_ref_object_sub_details_id_locked'], DBFunctions::TYPE_BOOLEAN);
			$arr_row['object_sub_details_location_use_object_id'] = DBFunctions::unescapeAs($arr_row['object_sub_details_location_use_object_id'], DBFunctions::TYPE_BOOLEAN);
			
			$arr[$arr_row['id']] = $arr_row;
		}		

		return $arr;
	}
	
	public static function getTypesObjectIdentifierDescriptions($type_id) {
		
		if ($type_id) {
			
			if (is_array($type_id)) {
				$sql_type_ids = implode(',', $type_id);
			} else {
				$sql_type_ids = (int)$type_id;
			}
		}
	
		$arr = [];

		$res = DB::query("SELECT nodegoat_to_des.type_id, nodegoat_to_des.id
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
			WHERE nodegoat_to_des.type_id IN (".$sql_type_ids.")
				AND nodegoat_to_des.is_identifier
		");
							
		while($row = $res->fetchAssoc()) {
			$arr[$row['type_id']][$row['id']] = $row['id'];
		}		

		return $arr;
	}
	
	public static function getTypeObjectPath($path, $type_id) {
		
		if (self::$arr_types_object_path_storage[$path][$type_id]) {
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
								WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des_ref.ref_type_id AND mode = 1) THEN 'reversed_collection'
								ELSE 'reversed_classification'
							END
						WHEN nodegoat_tos_des_ref.value_type_base != '' THEN nodegoat_tos_des_ref.value_type_base
						ELSE ''
					END					
				ELSE
					CASE
						WHEN nodegoat_to_des_ref.value_type_base = 'reversal' THEN
							CASE
								WHEN (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des_ref.ref_type_id AND mode = 1) THEN 'reversed_collection'
								ELSE 'reversed_classification'
							END
						WHEN nodegoat_to_des_ref.value_type_base != '' THEN nodegoat_to_des_ref.value_type_base
						WHEN nodegoat_to_des_ref.id_id = 'rc_ref_type_id' THEN 'id_id'
						ELSE ''
					END	
			END AS value_type,
			CASE
				WHEN nodegoat_tos_des_ref.id != 0 THEN
					CASE 
						WHEN nodegoat_tos_des_ref.value_type_base = 'text_tags' THEN TRUE
						WHEN nodegoat_tos_des_ref.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des_ref.ref_type_id AND mode = 1) THEN TRUE
						ELSE FALSE
					END
				ELSE
					CASE 
						WHEN nodegoat_to_des_ref.value_type_base = 'text_tags' THEN TRUE
						WHEN nodegoat_to_des_ref.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des_ref.ref_type_id AND mode = 1) THEN TRUE
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
			'' => ['id' => '', 'name' => getLabel('unit_data_varchar')],
			'type' => ['id' => 'type', 'name' => getLabel('lbl_type')],
			'classification' => ['id' => 'classification', 'name' => getLabel('lbl_classification')],
			'reversal' => ['id' => 'reversal', 'name' => getLabel('lbl_reversal')],
			'int' => ['id' => 'int', 'name' => getLabel('unit_data_int')],
			'text' => ['id' => 'text', 'name' => getLabel('unit_data_text')],
			'text_layout' => ['id' => 'text_layout', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_layout').')'],
			'text_tags' => ['id' => 'text_tags', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_tags').' & '.getLabel('lbl_layout').')'],
			'boolean' => ['id' => 'boolean', 'name' => getLabel('unit_data_boolean')],
			'date' => ['id' => 'date', 'name' => getLabel('unit_data_date')],
			'media' => ['id' => 'media', 'name' => getLabel('lbl_media')],
			'media_external' => ['id' => 'media_external', 'name' => getLabel('lbl_media').' ('.getLabel('lbl_external').')'],
			'external' => ['id' => 'external', 'name' => getLabel('lbl_external')],
			'object_description' => ['id' => 'object_description', 'name' => getLabel('lbl_object_description')]
		];
		
		return $arr_value_types_base;
	}
	
	public static function getValueTypes() {
		
		if (self::$arr_value_types) {
			return self::$arr_value_types;
		}
		
		self::$arr_value_types = [
			'' => ['id' => '', 'name' => getLabel('unit_data_varchar'), 'table' => '', 'value' => 'value'],
			'type' => ['id' => 'type', 'name' => getLabel('lbl_type'), 'table' => '_references', 'value' => 'ref_object_id'],
			'classification' => ['id' => 'classification', 'name' => getLabel('lbl_classification'), 'table' => '_references', 'value' => 'ref_object_id'],
			'reversed_classification' => ['id' => 'reversed_classification', 'name' => getLabel('lbl_reversed_classification'), 'table' => '_references', 'value' => 'ref_object_id'],
			'reversed_collection' => ['id' => 'reversed_collection', 'name' => getLabel('lbl_reversed_collection'), 'table' => '_references', 'value' => 'ref_object_id', 'purpose' => ['name' => ['table' => '', 'value' => 'value_text'], 'search' => ['table' => '', 'value' => 'value_text']]],
			'int' => ['id' => 'int', 'name' => getLabel('unit_data_int'), 'table' => '', 'value' => 'value_int'],
			'text' => ['id' => 'text', 'name' => getLabel('unit_data_text'), 'table' => '', 'value' => 'value_text'],
			'text_layout' => ['id' => 'text_layout', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_layout').')', 'table' => '', 'value' => 'value_text'],
			'text_tags' => ['id' => 'text_tags', 'name' => getLabel('unit_data_text').' ('.getLabel('lbl_tags').' & '.getLabel('lbl_layout').')', 'table' => '', 'value' => 'value_text'],
			'boolean' => ['id' => 'boolean', 'name' => getLabel('unit_data_boolean'), 'table' => '', 'value' => 'value_int'],
			'date' => ['id' => 'date', 'name' => getLabel('unit_data_date'), 'table' => '', 'value' => 'value_int'],
			'media' => ['id' => 'media', 'name' => getLabel('lbl_media'), 'table' => '', 'value' => 'value'],
			'media_external' => ['id' => 'media_external', 'name' => getLabel('lbl_media').' ('.getLabel('lbl_external').')', 'table' => '', 'value' => 'value'],
			'external' => ['id' => 'external', 'name' => getLabel('lbl_external'), 'table' => '', 'value' => 'value'],
			'object_description' => ['id' => 'object_description', 'name' => getLabel('lbl_object_description'), 'table' => '', 'value' => 'value'],
			'id_id' => ['id' => 'id_id', 'name' => '', 'table' => '_references', 'value' => 'ref_object_id']
		];
		
		return self::$arr_value_types;
	}
	
	public static function getValueTypeValue($type, $purpose = false) {
		
		if (!self::$arr_value_types) {
			self::getValueTypes();
		}
		
		$arr_value_type = self::$arr_value_types[$type];
		
		return ($purpose && $arr_value_type['purpose'] ? $arr_value_type['purpose'][$purpose]['value'] : $arr_value_type['value']);
	}
	
	public static function getValueTypeTable($type, $purpose = false) {
		
		if (!self::$arr_value_types) {
			self::getValueTypes();
		}
		
		$arr_value_type = self::$arr_value_types[$type];
		
		return ($purpose && $arr_value_type['purpose'] ? $arr_value_type['purpose'][$purpose]['table'] : $arr_value_type['table']);
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
}
