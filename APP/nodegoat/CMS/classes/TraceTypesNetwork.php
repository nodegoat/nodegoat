<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class TraceTypesNetwork {
	
	protected $type_id = false;
	protected $to_type_id = false;
	protected $num_steps = false;
	protected $do_in = true;
	protected $do_out = true;
	
	protected $stmt_object = [];
	protected $stmt_object_sub = [];
	protected $stmt_object_dynamic = [];
	protected $stmt_object_sub_dynamic = [];
	protected $stmt_object_sub_location = [];
	protected $stmt_object_sub_location_dynamic = [];
	
	protected $arr_filter_object_connections = [];

    protected $arr_types_match = [];
	protected $arr_step = [];
	protected $num_count_step = false;
	protected $num_steps_total = false;
	protected $stmt = false;
	protected $arr_type_network_paths = [];
	
	const RUN_MODE_REFERENCING = 1;
	const RUN_MODE_REFERENCED = 2;
	const RUN_MODE_BOTH = (self::RUN_MODE_REFERENCING | self::RUN_MODE_REFERENCED);
	const RUN_MODE_SHORTEST = 4;

    public function __construct($arr_type_ids = [], $do_dynamic = false, $do_object_sub_locations = false) {
		
		$sql_type_ids = implode(',', arrParseRecursive($arr_type_ids));
		
		$version_select_to = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object', 'nodegoat_to');
		$version_select_tos = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object_sub', 'nodegoat_tos');
		$version_select_to2 = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object', 'nodegoat_to2');
		
		$this->stmt_object['in'] = DB::prepare("SELECT
			nodegoat_to_des.type_id, nodegoat_to_des.id AS object_description_id, nodegoat_to_des.has_multi
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
			WHERE nodegoat_to_des.ref_type_id = ".DBStatement::assign('id', 'i')."
				".($sql_type_ids ? "AND nodegoat_to_des.type_id IN (".$sql_type_ids.")" : "")."
				".($do_dynamic ? "AND NOT (nodegoat_to_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))" : "")."
			GROUP BY nodegoat_to_des.id
		");
					
		$this->stmt_object['out'] = DB::prepare("SELECT
			nodegoat_to_des.ref_type_id AS ref_type_id, nodegoat_to_des.id AS object_description_id, nodegoat_to_des.has_multi
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
			WHERE nodegoat_to_des.type_id = ".DBStatement::assign('id', 'i')." 
				AND nodegoat_to_des.ref_type_id ".($sql_type_ids ? "IN (".$sql_type_ids.")" : "!= 0")."
				".($do_dynamic ? "AND NOT (nodegoat_to_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))" : "")."
			GROUP BY nodegoat_to_des.id
		");
		
		$this->stmt_object_sub['in'] = DB::prepare("SELECT
			nodegoat_tos_det.type_id, nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_des.id AS object_sub_description_id, nodegoat_tos_des.use_object_description_id
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_tos_det.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des ON (nodegoat_to_des.id = nodegoat_tos_des.use_object_description_id)
			WHERE CASE
					WHEN nodegoat_tos_des.use_object_description_id != 0 THEN nodegoat_to_des.ref_type_id
					ELSE nodegoat_tos_des.ref_type_id
				END = ".DBStatement::assign('id', 'i')."
				".($sql_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_type_ids.")" : "")."
				".($do_dynamic ? "AND NOT (nodegoat_tos_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))" : "")."
			GROUP BY nodegoat_tos_des.id, nodegoat_tos_det.id
		");
					
		$this->stmt_object_sub['out'] = DB::prepare("SELECT
			CASE
				WHEN nodegoat_tos_des.use_object_description_id != 0 THEN nodegoat_to_des.ref_type_id
				ELSE nodegoat_tos_des.ref_type_id
			END AS ref_type_id,
			nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_des.id AS object_sub_description_id, nodegoat_tos_des.use_object_description_id
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des ON (nodegoat_tos_des.object_sub_details_id = nodegoat_tos_det.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des ON (nodegoat_to_des.id = nodegoat_tos_des.use_object_description_id)
			WHERE nodegoat_tos_det.type_id = ".DBStatement::assign('id', 'i')."
				AND CASE
					WHEN nodegoat_tos_des.use_object_description_id != 0 THEN nodegoat_to_des.ref_type_id
					ELSE nodegoat_tos_des.ref_type_id
				END ".($sql_type_ids ? "IN (".$sql_type_ids.")" : "!= 0")."
				".($do_dynamic ? "AND NOT (nodegoat_tos_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))" : "")."
			GROUP BY nodegoat_tos_des.id, nodegoat_tos_det.id, nodegoat_to_des.id
		");
					
		if ($do_dynamic) {
			
			$this->stmt_object_dynamic['in'] = DB::prepare("SELECT
				nodegoat_to_des.type_id, nodegoat_to_des.id AS object_description_id, nodegoat_to_des.has_multi
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
				WHERE (
						(nodegoat_to_des.value_type_base = 'text_tags')
						OR (nodegoat_to_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))
					)
					".($sql_type_ids ? "AND nodegoat_to_des.type_id IN (".$sql_type_ids.")" : "")."
					AND EXISTS (
						SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
						WHERE nodegoat_to_def_ref.object_description_id = nodegoat_to_des.id AND nodegoat_to_def_ref.ref_type_id = ".DBStatement::assign('id', 'i')." AND nodegoat_to_def_ref.state = 1
					)
				GROUP BY nodegoat_to_des.id
			");
			
			$this->stmt_object_dynamic['out'] = DB::prepare("SELECT
				nodegoat_t.id AS ref_type_id, nodegoat_to_des.id AS object_description_id, nodegoat_to_des.has_multi
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
					JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (
						".($sql_type_ids ? "nodegoat_t.id IN (".$sql_type_ids.")" : "TRUE")."
						AND EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
							WHERE nodegoat_to_def_ref.object_description_id = nodegoat_to_des.id AND nodegoat_to_def_ref.ref_type_id = nodegoat_t.id AND nodegoat_to_def_ref.state = 1
						)
					)
				WHERE (
						(nodegoat_to_des.value_type_base = 'text_tags')
						OR (nodegoat_to_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))
					)
					AND nodegoat_to_des.type_id = ".DBStatement::assign('id', 'i')."
				GROUP BY nodegoat_to_des.id, nodegoat_t.id
			");
												
			$this->stmt_object_sub_dynamic['in'] = DB::prepare("SELECT
				nodegoat_tos_det.type_id, nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_des.id AS object_sub_description_id
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des
					JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos_des.object_sub_details_id)
				WHERE (
						(nodegoat_tos_des.value_type_base = 'text_tags')
						OR (nodegoat_tos_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))
					)
					".($sql_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_type_ids.")" : "")."
					AND EXISTS (
						SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_tos_def_ref.object_sub_description_id = nodegoat_tos_des.id AND nodegoat_tos_def_ref.ref_type_id = ".DBStatement::assign('id', 'i')." AND nodegoat_tos_def_ref.state = 1
					)
				GROUP BY nodegoat_tos_des.id, nodegoat_tos_det.id
			");
			
			$this->stmt_object_sub_dynamic['out'] = DB::prepare("SELECT
				nodegoat_t.id AS ref_type_id, nodegoat_tos_det.id AS object_sub_details_id, nodegoat_tos_des.id AS object_sub_description_id
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des
					JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos_des.object_sub_details_id)
					JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (
						".($sql_type_ids ? "nodegoat_t.id IN (".$sql_type_ids.")" : "TRUE")."
						AND EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
							WHERE nodegoat_tos_def_ref.object_sub_description_id = nodegoat_tos_des.id AND nodegoat_tos_def_ref.ref_type_id = nodegoat_t.id AND nodegoat_tos_def_ref.state = 1
						)
					)
				WHERE (
						(nodegoat_tos_des.value_type_base = 'text_tags')
						OR (nodegoat_tos_des.value_type_base = 'reversal' AND EXISTS (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode & ".StoreType::TYPE_MODE_REVERSAL_COLLECTION." != 0))
					)
					AND nodegoat_tos_det.type_id = ".DBStatement::assign('id', 'i')."
				GROUP BY nodegoat_tos_des.id, nodegoat_tos_det.id, nodegoat_t.id
			");
		}

		if ($do_object_sub_locations) {
				
			$this->stmt_object_sub_location['in'] = DB::prepare("SELECT
				nodegoat_tos_det.type_id, nodegoat_tos_det.id AS object_sub_details_id,
				CASE
					WHEN (location_use_object_sub_details_id != 0 OR location_use_object_sub_description_id != 0 OR location_use_object_description_id != 0 OR location_use_object_id = TRUE) THEN 1
					ELSE 0
				END AS location_use_other
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
				WHERE nodegoat_tos_det.location_ref_type_id_locked = TRUE
					".($sql_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_type_ids.")" : "")."
					AND nodegoat_tos_det.location_ref_type_id = ".DBStatement::assign('id', 'i')."							
				GROUP BY nodegoat_tos_det.id
			");
			
			$this->stmt_object_sub_location['out'] = DB::prepare("SELECT
				nodegoat_tos_det.location_ref_type_id AS ref_type_id, nodegoat_tos_det.id AS object_sub_details_id,
				CASE
					WHEN (location_use_object_sub_details_id != 0 OR location_use_object_sub_description_id != 0 OR location_use_object_description_id != 0 OR location_use_object_id = TRUE) THEN 1
					ELSE 0
				END AS location_use_other
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
				WHERE nodegoat_tos_det.location_ref_type_id_locked = TRUE
					AND nodegoat_tos_det.type_id = ".DBStatement::assign('id', 'i')."
					AND ".($sql_type_ids ? "nodegoat_tos_det.location_ref_type_id IN (".$sql_type_ids.")" : "nodegoat_tos_det.location_ref_type_id")."					
				GROUP BY nodegoat_tos_det.id
			");
			
			if ($do_dynamic) {
				
				$this->stmt_object_sub_location_dynamic['in'] = DB::prepare("SELECT
					nodegoat_tos_det.type_id, nodegoat_tos_det.id AS object_sub_details_id
						FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
					WHERE nodegoat_tos_det.location_ref_type_id_locked = FALSE
						".($sql_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_type_ids.")" : "")."
						AND EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2 ON (nodegoat_to2.id = nodegoat_tos.location_ref_object_id AND ".$version_select_to2.")
							WHERE nodegoat_tos.object_sub_details_id = nodegoat_tos_det.id AND ".$version_select_tos."
								AND nodegoat_tos.location_ref_type_id = ".DBStatement::assign('id', 'i')."
						)
					GROUP BY nodegoat_tos_det.id
				");
					
				$this->stmt_object_sub_location_dynamic['out'] = DB::prepare("SELECT
					nodegoat_t.id AS ref_type_id, nodegoat_tos_det.id AS object_sub_details_id
						FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
						JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (
							".($sql_type_ids ? "nodegoat_t.id IN (".$sql_type_ids.")" : "TRUE")."
							AND EXISTS (
								SELECT TRUE
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2 ON (nodegoat_to2.id = nodegoat_tos.location_ref_object_id AND ".$version_select_to2.")
								WHERE nodegoat_tos.object_sub_details_id = nodegoat_tos_det.id AND ".$version_select_tos."
									AND nodegoat_tos.location_ref_type_id = nodegoat_t.id
							)
						)
					WHERE nodegoat_tos_det.location_ref_type_id_locked = FALSE
						AND nodegoat_tos_det.type_id = ".DBStatement::assign('id', 'i')."
					GROUP BY nodegoat_tos_det.id, nodegoat_t.id
				");
			}
		}
    }
	
	public function run($type_id, $to_type_id, $num_steps, $mode_run = self::RUN_MODE_BOTH) {
		
		$this->type_id = (int)$type_id;
		$this->to_type_id = (int)$to_type_id;
		$this->num_steps = (int)$num_steps;
		$this->do_out = bitHasMode($mode_run, static::RUN_MODE_REFERENCING);
		$this->do_in = bitHasMode($mode_run, static::RUN_MODE_REFERENCED);
		$do_shortest = bitHasMode($mode_run, static::RUN_MODE_SHORTEST);
		
		$this->arr_step[$this->type_id] = [[]];

		for ($this->num_count_step = 1; ($this->num_count_step <= $this->num_steps && $this->arr_step); $this->num_count_step++) {
					
			$arr_step = $this->arr_step;
			$this->arr_step = [];
			
			if ($do_shortest && $this->arr_types_match) {
				break;
			}
						
			foreach ($arr_step as $type_id => $arr_lists) {
				
				$this->doTraceTypeNetwork($type_id, $arr_lists);
			}
		}
		
		return $this->arr_types_match;
    }
		
	protected function doTraceTypeNetwork($type_id, $arr_lists) {
		
		$arr_type_network = $this->getTypeNetwork($type_id);
		
		foreach ($arr_lists as $arr_list) {
			
			if (!$this->to_type_id && !$arr_type_network && $arr_list['nodes']) { // The path ends on its own when there is no target type id
				$this->arr_types_match[] = $arr_list['nodes'];
			}
			
			foreach ($arr_type_network as $cur_type_id => $arr_object_connections) {
				
				$cur_list = $arr_list;
				
				$cur_list['path'][] = $type_id;
				
				foreach ($arr_object_connections as &$arr_object_connection) {
					
					$direction = $arr_object_connection['in_out']; // To get all possible connections in a path, incomming and outgoing connections have to be separated
					
					if ($arr_object_connection['object_description_id']) {
						
						if ($this->arr_filter_object_connections) {
							
							$path = implode('-', $cur_list['path']);
							$arr_prefilter = ($this->arr_filter_object_connections[$path]['object_descriptions'][$arr_object_connection['object_description_id']] ?? null);
							
							if (!$arr_prefilter || (is_array($arr_prefilter) && !$arr_prefilter[$arr_object_connection['in_out']]) || ($arr_object_connection['dynamic'] && !$arr_prefilter[$arr_object_connection['in_out']][$cur_type_id])) {
								$arr_object_connection = false;
								continue;
							}
						} else {
							
							$s = &$cur_list['passed']['object_descriptions'][$direction];
							
							if ($s && in_array($arr_object_connection['object_description_id'], $s)) {
								$arr_object_connection = false;
								continue;
							} else {
								$s[] = $arr_object_connection['object_description_id'];
							}
						}
					} else if ($arr_object_connection['object_sub_description_id']) {
						
						if ($this->arr_filter_object_connections) {
							
							$path = implode('-', $cur_list['path']);
							$arr_prefilter = ($this->arr_filter_object_connections[$path]['object_sub_descriptions'][$arr_object_connection['object_sub_description_id']] ?? null);
							
							if (!$arr_prefilter || (is_array($arr_prefilter) && !$arr_prefilter[$arr_object_connection['in_out']]) || ($arr_object_connection['dynamic'] && !$arr_prefilter[$arr_object_connection['in_out']][$cur_type_id])) {
								$arr_object_connection = false;
								continue;
							}
						} else {
							
							$s = &$cur_list['passed']['object_sub_descriptions'][$direction];
							
							if ($s && in_array($arr_object_connection['object_sub_description_id'], $s)) {
								$arr_object_connection = false;
								continue;
							} else {
								$s[] = $arr_object_connection['object_sub_description_id'];
							}
						}
					} else if ($arr_object_connection['object_sub_location']) {
					
						if ($this->arr_filter_object_connections) {
							
							$path = implode('-', $cur_list['path']);
							$arr_prefilter = ($this->arr_filter_object_connections[$path]['object_sub_locations'][$arr_object_connection['object_sub_details_id']] ?? null);
							
							if (!$arr_prefilter || (is_array($arr_prefilter) && !$arr_prefilter[$arr_object_connection['in_out']]) || ($arr_object_connection['dynamic'] && !$arr_prefilter[$arr_object_connection['in_out']][$cur_type_id])) {
								$arr_object_connection = false;
								continue;
							}
						} else {
							
							$s = &$cur_list['passed']['object_sub_locations'][$direction];
							
							if ($s && in_array($arr_object_connection['object_sub_details_id'], $s)) {
								$arr_object_connection = false;
								continue;
							} else {
								$s[] = $arr_object_connection['object_sub_details_id'];
							}
						}
					}
					
					$arr_object_connection['path'] = $cur_list['path'];
				}
				unset($arr_object_connection);
				
				$arr_object_connections = array_filter($arr_object_connections);
				
				$cur_list['nodes'][] = $arr_object_connections;
				
				if (!$arr_object_connections) { // The path ends here as it only reached already encountered descriptions, or the path is not part of the prefilter anymore.
					
					if (!$this->to_type_id) { // No target type id needed, store
						if ($arr_list['nodes']) {
							$this->arr_types_match[] = $arr_list['nodes'];
						}
						$this->num_steps_total = $this->num_count_step;
					}
				} else if ($this->to_type_id == $cur_type_id) { // Found a connection to the target type id, this path ends, store
					
					$this->arr_types_match[] = $cur_list['nodes'];
					$this->num_steps_total = $this->num_count_step;
				/*} else if ($this->type_id == $cur_type_id && $this->num_count_step > 1) { // Found a connection to the source type, this path ends. Allow for recursion at trace start
					
					if (!$this->to_type_id) { // No target type id needed, store
						if ($arr_list['nodes']) {
							$this->arr_types_match[] = $arr_list['nodes'];
						}
						$this->num_steps_total = $this->num_count_step;
					}*/
				} else if ($this->num_count_step == $this->num_steps) { // Reached the maximum steps, this path ends
					
					if (!$this->to_type_id) { // No target type id needed, store
						$this->arr_types_match[] = $cur_list['nodes'];				
						$this->num_steps_total = $this->num_count_step;
					}
				} else {
					
					$this->arr_step[$cur_type_id][] = $cur_list;
				}
			}
		}
	}
	
	protected function getTypeNetwork($type_id) {
	
		$arr = [];
		
		$type_id_in = ($this->do_in ? $type_id : 0);
		$type_id_out = ($this->do_out ? $type_id : 0);
		
		if ($type_id_in) {
			
			$this->stmt_object['in']->bindParameters(['id' => $type_id_in]);
					
			$res = $this->stmt_object['in']->execute();

			while ($arr_row = $res->fetchRow()) {
				
				$use_type_id = (int)$arr_row[0];
				
				$arr[$use_type_id][] = ['type_id' => $use_type_id, 'object_description_id' => (int)$arr_row[1], 'ref_type_id' => $type_id_in, 'in_out' => 'in', 'multi' => DBFunctions::unescapeAs($arr_row[2], DBFunctions::TYPE_BOOLEAN),
					'identifier' => $arr_row[0].'_'.$arr_row[1].'_'.$type_id_in.'_in'
				];
			}
		}
		if ($type_id_out) {
			
			$this->stmt_object['out']->bindParameters(['id' => $type_id_out]);
					
			$res = $this->stmt_object['out']->execute();
	
			while ($arr_row = $res->fetchRow()) {
				
				$use_type_id = (int)$arr_row[0];
				
				$arr[$use_type_id][] = ['type_id' => $type_id_out, 'object_description_id' => (int)$arr_row[1], 'ref_type_id' => $use_type_id, 'in_out' => 'out', 'multi' => DBFunctions::unescapeAs($arr_row[2], DBFunctions::TYPE_BOOLEAN),
					'identifier' => $type_id_out.'_'.$arr_row[1].'_'.$arr_row[0].'_out'
				];
			}
		}
		
		if ($type_id_in) {
			
			$this->stmt_object_sub['in']->bindParameters(['id' => $type_id_in]);
					
			$res = $this->stmt_object_sub['in']->execute();

			while ($arr_row = $res->fetchRow()) {
				
				$use_type_id = (int)$arr_row[0];
				
				$arr[$use_type_id][] = ['type_id' => $use_type_id, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_description_id' => (int)$arr_row[2], 'use_object_description_id' => (int)$arr_row[3], 'ref_type_id' => $type_id_in, 'in_out' => 'in',
					'identifier' => $arr_row[0].'_'.$arr_row[1].'_'.$arr_row[2].'_'.$arr_row[3].'_'.$type_id_in.'_in'
				];
			}
		}
		if ($type_id_out) {
			
			$this->stmt_object_sub['out']->bindParameters(['id' => $type_id_out]);
					
			$res = $this->stmt_object_sub['out']->execute();

			while ($arr_row = $res->fetchRow()) {
				
				$use_type_id = (int)$arr_row[0];
				
				$arr[$use_type_id][] = ['type_id' => $type_id_out, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_description_id' => (int)$arr_row[2], 'use_object_description_id' => (int)$arr_row[3], 'ref_type_id' => $use_type_id, 'in_out' => 'out',
					'identifier' => $type_id_out.'_'.$arr_row[1].'_'.$arr_row[2].'_'.$arr_row[3].'_'.$arr_row[0].'_out'
				];
			}
		}
		
		if ($this->stmt_object_dynamic) {
			
			if ($type_id_in) {	
						
				$this->stmt_object_dynamic['in']->bindParameters(['id' => $type_id_in]);
					
				$res = $this->stmt_object_dynamic['in']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $use_type_id, 'object_description_id' => (int)$arr_row[1], 'ref_type_id' => $type_id_in, 'in_out' => 'in', 'multi' => DBFunctions::unescapeAs($arr_row[2], DBFunctions::TYPE_BOOLEAN), 'dynamic' => true,
						'identifier' => $arr_row[0].'_'.$arr_row[1].'_'.$type_id_in.'_in_dynamic'
					];
				}
			}
			if ($type_id_out) {	
						
				$this->stmt_object_dynamic['out']->bindParameters(['id' => $type_id_out]);
					
				$res = $this->stmt_object_dynamic['out']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $type_id_out, 'object_description_id' => (int)$arr_row[1], 'ref_type_id' => $use_type_id, 'in_out' => 'out', 'multi' => DBFunctions::unescapeAs($arr_row[2], DBFunctions::TYPE_BOOLEAN), 'dynamic' => true,
						'identifier' => $type_id_out.'_'.$arr_row[1].'_'.$arr_row[0].'_out_dynamic'
					];
				}
			}
			
			if ($type_id_in) {	
						
				$this->stmt_object_sub_dynamic['in']->bindParameters(['id' => $type_id_in]);
					
				$res = $this->stmt_object_sub_dynamic['in']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $use_type_id, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_description_id' => (int)$arr_row[2], 'ref_type_id' => $type_id_in, 'in_out' => 'in', 'dynamic' => true,
						'identifier' => $arr_row[0].'_'.$arr_row[1].'_'.$arr_row[2].'_'.$type_id_in.'_in_dynamic'
					];
				}
			}
			if ($type_id_out) {	
						
				$this->stmt_object_sub_dynamic['out']->bindParameters(['id' => $type_id_out]);
					
				$res = $this->stmt_object_sub_dynamic['out']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $type_id_out, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_description_id' => (int)$arr_row[2], 'ref_type_id' => $use_type_id, 'in_out' => 'out', 'dynamic' => true,
						'identifier' => $type_id_out.'_'.$arr_row[1].'_'.$arr_row[2].'_'.$arr_row[0].'_out_dynamic'
					];
				}
			}
		}
		
		if ($this->stmt_object_sub_location) {
			
			if ($type_id_in) {
						
				$this->stmt_object_sub_location['in']->bindParameters(['id' => $type_id_in]);
					
				$res = $this->stmt_object_sub_location['in']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $use_type_id, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_location' => true, 'ref_type_id' => $type_id_in, 'in_out' => 'in',
						'identifier' => $arr_row[0].'_'.$arr_row[1].'_osl_'.$type_id_in.'_in'
					];
				}
			}
			if ($type_id_out) {	
						
				$this->stmt_object_sub_location['out']->bindParameters(['id' => $type_id_out]);
					
				$res = $this->stmt_object_sub_location['out']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $type_id_out, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_location' => true, 'ref_type_id' => $use_type_id, 'in_out' => 'out',
						'identifier' => $type_id_out.'_'.$arr_row[1].'_osl_'.$arr_row[0].'_out'
					];
				}
			}
		}
		
		if ($this->stmt_object_sub_location_dynamic) {
			
			if ($type_id_in) {	

				$this->stmt_object_sub_location_dynamic['in']->bindParameters(['id' => $type_id_in]);
					
				$res = $this->stmt_object_sub_location_dynamic['in']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $use_type_id, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_location' => true, 'ref_type_id' => $type_id_in, 'in_out' => 'in', 'dynamic' => true,
						'identifier' => $arr_row[0].'_'.$arr_row[1].'_osl_'.$type_id_in.'_in_dynamic'
					];
				}
			}
			if ($type_id_out) {
						
				$this->stmt_object_sub_location_dynamic['out']->bindParameters(['id' => $type_id_out]);
					
				$res = $this->stmt_object_sub_location_dynamic['out']->execute();

				while ($arr_row = $res->fetchRow()) {
					
					$use_type_id = (int)$arr_row[0];
					
					$arr[$use_type_id][] = ['type_id' => $type_id_out, 'object_sub_details_id' => (int)$arr_row[1], 'object_sub_location' => true, 'ref_type_id' => $use_type_id, 'in_out' => 'out', 'dynamic' => true,
						'identifier' => $type_id_out.'_'.$arr_row[1].'_osl_'.$arr_row[0].'_out_dynamic'
					];
				}
			}
		}
		
		return $arr;
	}
	
	public function filterTypesNetwork($arr_object_connections) {
		
		// $arr_object_connections = array("type_id-type_id-n" => array("object_descriptions" => object_description_ids) OR array("object_sub_descriptions" => object_sub_description_ids))
		
		$this->arr_filter_object_connections = $arr_object_connections;
		
		if ($this->type_id) { // Trace is already performed, filter after trace
	
			$arr_type_network_filtered = [];
			foreach ($this->arr_types_match as $arr_network_list) {
				
				$stop = false;
				foreach ($arr_network_list as $key => &$arr_network_list_step) {
					
					if ($stop) {
						unset($arr_network_list[$key]);
						continue;
					}
					
					$add = false;
					$arr_network_list_step_filtered = [];
					
					foreach ($arr_network_list_step as $value) {
						
						$path = implode('-', $value['path']);
						
						if ($value['object_description_id']) {
							if (!$arr_object_connections[$path]['object_descriptions'][$value['object_description_id']][$value['in_out']] || ($value['dynamic'] && !$arr_object_connections[$path]['object_descriptions'][$value['object_description_id']][$value['in_out']][($value['in_out'] == 'out' ? $value['ref_type_id'] : $value['type_id'])])) {
								continue;
							}
						} else if ($value['object_sub_description_id']) {
							if (!$arr_object_connections[$path]['object_sub_descriptions'][$value['object_sub_description_id']][$value['in_out']] || ($value['dynamic'] && !$arr_object_connections[$path]['object_sub_descriptions'][$value['object_sub_description_id']][$value['in_out']][($value['in_out'] == 'out' ? $value['ref_type_id'] : $value['type_id'])])) {
								continue;
							}
						} else if ($value['object_sub_location']) {
							if (!$arr_object_connections[$path]['object_sub_locations'][$value['object_sub_details_id']][$value['in_out']] || ($value['dynamic'] && !$arr_object_connections[$path]['object_sub_locations'][$value['object_sub_details_id']][$value['in_out']][($value['in_out'] == 'out' ? $value['ref_type_id'] : $value['type_id'])])) {
								continue;
							}
						}

						$add = true;
						$arr_network_list_step_filtered[] = $value;
					}
					$arr_network_list_step = $arr_network_list_step_filtered;
					
					if ($this->to_type_id && !$add) { // Every step has to be returned as true when there is a target type id
						break;
					} else if (!$this->to_type_id && !$add) { // Stop adding steps when one is missing, cleanup the rest
						unset($arr_network_list[$key]);
						$stop = true;
					}
				}
				if (($this->to_type_id && $add) || (!$this->to_type_id && $arr_network_list)) {
					$arr_type_network_filtered[] = $arr_network_list;
				}
			}
			
			$this->arr_types_match = $arr_type_network_filtered;

			return $this->arr_types_match;
		}
	}
	
	public function reverse() {
	
		foreach ($this->arr_types_match as &$arr_network_list) {
			foreach ($arr_network_list as &$arr_network_list_step) {
				foreach ($arr_network_list_step as &$value) {
					$value['in_out'] = ($value['in_out'] == 'out' ? 'in' : 'out');
				}
				$arr_network_list_step = array_reverse($arr_network_list_step);
			}
			$arr_network_list = array_reverse($arr_network_list);
		}
				
		return $this->arr_types_match;
	}
	
	public function getTypeNetworkPaths($self = false) {
		
		$this->arr_type_network_paths = [];
		
		foreach ($this->arr_types_match as $arr_network_list) {
			
			$cur_entry = &$this->arr_type_network_paths;
			
			foreach ($arr_network_list as $step => $arr_network_list_step) {
				
				$cur_entry = &$cur_entry['connections'];
				$source_type_id = false; // Does not change in this loop, it's the same in/out source
				
				foreach ($arr_network_list_step as $value) {
					
					$target_type_id = 0;
					
					if ($value['in_out'] == 'out') { // Switch type origin (in or out) and type reference (in or out) accordingly
						$source_type_id = $value['type_id'];
						$target_type_id = $value['ref_type_id'];
					} else {
						$source_type_id = $value['ref_type_id'];
						$target_type_id = $value['type_id'];
					}
					$path = implode('-', $value['path']);
					
					if ($value['object_description_id']) {

						if ($this->arr_filter_object_connections) { // Add-back additional optional connection information
							
							if ($value['dynamic']) {
								$arr_connection_settings = ($this->arr_filter_object_connections[$path]['object_descriptions'][$value['object_description_id']][$value['in_out']][$target_type_id] ?? false);
							} else {
								$arr_connection_settings = ($this->arr_filter_object_connections[$path]['object_descriptions'][$value['object_description_id']][$value['in_out']] ?? false);
							}
							$this->parseTypeNetworkPathConnection($value, $arr_connection_settings);
						}
						
						$cur_entry[$source_type_id][$value['in_out']][$target_type_id]['object_descriptions'][$value['object_description_id']] = $value;
					} else if ($value['object_sub_description_id']) {
						
						if ($this->arr_filter_object_connections) { // Add-back additional optional connection information
								
							if ($value['dynamic']) {
								$arr_connection_settings = ($this->arr_filter_object_connections[$path]['object_sub_descriptions'][$value['object_sub_description_id']][$value['in_out']][$target_type_id] ?? false);
							} else {
								$arr_connection_settings = ($this->arr_filter_object_connections[$path]['object_sub_descriptions'][$value['object_sub_description_id']][$value['in_out']] ?? false);
							}
							$this->parseTypeNetworkPathConnection($value, $arr_connection_settings);
						}
						
						$cur_entry[$source_type_id][$value['in_out']][$target_type_id]['object_sub_details'][$value['object_sub_details_id']][$value['object_sub_description_id']] = $value;
					} else if ($value['object_sub_location']) {
						
						if ($this->arr_filter_object_connections) { // Add-back additional optional connection information
							
							if ($value['dynamic']) {
								$arr_connection_settings = ($this->arr_filter_object_connections[$path]['object_sub_locations'][$value['object_sub_details_id']][$value['in_out']][$target_type_id] ?? false);
							} else {
								$arr_connection_settings = ($this->arr_filter_object_connections[$path]['object_sub_locations'][$value['object_sub_details_id']][$value['in_out']] ?? false);
							}
							$this->parseTypeNetworkPathConnection($value, $arr_connection_settings);
						}
						
						$cur_entry[$source_type_id][$value['in_out']][$target_type_id]['object_sub_details'][$value['object_sub_details_id']]['object_sub_location'] = $value;
					}
					
					$cur_entry[$source_type_id][$value['in_out']][$target_type_id]['path'] = $value['path'];
				}
								
				$cur_entry = &$cur_entry[$source_type_id];
			}
		}
		
		if ($self) {
			$this->arr_type_network_paths += ['start' => [$this->type_id => ['path' => [0]]]];
		}
		
		return $this->arr_type_network_paths;
	}
	
	protected function parseTypeNetworkPathConnection(&$arr_path, $arr_connection_settings) {
		
		if (!isset($arr_connection_settings['date'])) {
			return;
		}
		
		$arr_path['date'] = $arr_connection_settings['date'];
		
		foreach ($arr_path['date'] as $str_date_start_end => &$arr_date_statement) {
			
			$arr_date_statement['identifier'] = $str_date_start_end.'_'.arr2String($arr_date_statement, '_'); // Needed for grouping identical statements
			
			if ($arr_path['object_sub_details_id']) {
							
				if (strStartsWith($arr_date_statement['id'], 'object_sub_details_')) {

					$arr_date_statement['object_sub_details_id'] = $arr_path['object_sub_details_id'];
					
					if (
						strStartsWith($arr_date_statement['id'], 'object_sub_details_'.$arr_path['object_sub_details_id'])
							&&
						(($arr_path['in_out'] == 'out' && $arr_date_statement['source_target'] == 'source') || ($arr_path['in_out'] == 'in' && $arr_date_statement['source_target'] == 'target'))
					) {
					
						$arr_date_statement['filter_object_sub'] = true; // Indicate if the connection is sourcing its own sub-object date
					}
				}
			}
		}
	}
	
	public function getFoundTypeIDs($do_include_alternate = false) {
		
		$arr = [];
		
		foreach ($this->arr_types_match as $arr_network_list) {
						
			foreach ($arr_network_list as $step => $arr_network_list_step) {

				foreach ($arr_network_list_step as $value) {
					
					if ($value['in_out'] == 'out') { // Switch type origin (in or out) and type reference (in or out) accordingly
						$source_type_id = $value['type_id'];
						$target_type_id = $value['ref_type_id'];
					} else {
						$source_type_id = $value['ref_type_id'];
						$target_type_id = $value['type_id'];
					}
					
					$arr[$source_type_id] = $source_type_id;
					$arr[$target_type_id] = $target_type_id;
					
					if ($do_include_alternate) {
						
						if (!$value['dynamic']) {
							continue;
						}

						if ($value['object_description_id']) {

							$arr_type_set = StoreType::getTypeSet($source_type_id);
							$str_value_type = $arr_type_set['object_descriptions'][$value['object_description_id']]['object_description_value_type'];
							
							if ($str_value_type != 'reversed_collection' && $str_value_type != 'reversed_collection_resource_path') {
								continue;
							}
							
							$alternate_type_id = $arr_type_set['object_descriptions'][$value['object_description_id']]['object_description_ref_type_id'];
							$arr[$alternate_type_id] = $alternate_type_id;
						} else if ($value['object_sub_description_id']) {
							
							$arr_type_set = StoreType::getTypeSet($source_type_id);
							$str_value_type = $arr_type_set['object_sub_details'][$value['object_sub_details_id']]['object_sub_descriptions'][$value['object_sub_description_id']]['object_sub_description_value_type'];
							
							if ($str_value_type != 'reversed_collection' && $str_value_type != 'reversed_collection_resource_path') {
								continue;
							}
								
							$alternate_type_id = $arr_type_set['object_sub_details'][$value['object_sub_details_id']]['object_sub_descriptions'][$value['object_sub_description_id']]['object_sub_description_ref_type_id'];
							$arr[$alternate_type_id] = $alternate_type_id;
						}
					}
				}
			}
		}
		
		return $arr;
	}
	
	public function getTotalSteps() {
				
		return $this->num_steps_total;
	}
	
	/*
	$arr_type_end = [];
	
	$func_collect = function($arr_type_connections) use (&$func_collect, &$arr_type_end) {

		foreach ($arr_type_connections as $in_out => $arr_in_out) {
			
			if ($in_out == 'connections') {
				continue;
			}
			
			foreach ($arr_in_out as $target_type_id => $arr_type_object_connections) {
				
				if ($arr_type_connections['connections'][$target_type_id]) {
					
					$func_collect($arr_type_connections['connections'][$target_type_id]);
				} else { // End-point
					
					$arr_type_end[$target_type_id] = $target_type_id;
				}
			}
		}
	};
	
	$func_collect($arr_type_network_paths['connections'][$type_id]);
	*/
}
