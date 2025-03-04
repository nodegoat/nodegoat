<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class TraceTypesObjectsNetwork {
	
	private $type_id = false;
	private $to_type_id = false;
	private $object_id = false;
	private $to_object_id = false;
	private $steps = false;

    private $arr_objects_match = [];
	private $arr_objects_passed = [];
	private $arr_step = [];
	private $cur_step = false;
	private $steps_total = false;
	private $sql_tables = false;
	private $stmt = false;
	private $sql_tables_reverse = false;
	private $stmt_reverse = false;
	private $arr_sql_type_objects = [];
	private $arr_type_object_ids = [];
	private $arr_type_object_network_paths = [];

    public function __construct($type_id, $to_type_id, $arr_type_network_paths, $arr_type_network_paths_reverse = false) {
	
		$this->type_id = $type_id;
		$this->to_type_id = $to_type_id;

		$arr_sql_tables = self::sqlTables($arr_type_network_paths);
		$this->sql_tables = $arr_sql_tables['sql'];
		$this->arr_sql_type_objects = $arr_sql_tables['arr_sql_type_objects'];

		$this->stmt = DB::prepare("SELECT to_to.id, to_to.type_id
				".$this->sql_tables."
			WHERE nodegoat_to.id = ".DBStatement::assign('id1', 'i')." AND to_to.id != ".DBStatement::assign('id2', 'i')."
			GROUP BY to_to.id
		");
		
		if ($arr_type_network_paths_reverse) {
				
			$arr_sql_tables = self::sqlTables($arr_type_network_paths_reverse);
			$this->sql_tables_reverse = $arr_sql_tables['sql'];

			$this->stmt_reverse = DB::prepare("SELECT to_to.id, to_to.type_id
					".$this->sql_tables_reverse."
				WHERE nodegoat_to.id = ".DBStatement::assign('id1', 'i')." AND to_to.id != ".DBStatement::assign('id2', 'i')."
				GROUP BY to_to.id
			");
		}							
    }
    
    public static function sqlTables($arr_type_network_paths) {
		
		if (!$arr_type_network_paths['connections']) {
			error(getLabel('msg_missing_information'));
		}
		
		$arr_sql_type_objects = [];
		$arr_sql_ref_tables = [];
		
		$func_connection = function($arr_network_connections, $source_table) use (&$func_connection, &$arr_sql_type_objects, &$arr_sql_ref_tables) {
			
			$sql_source_tables = ($source_table ? implode(',', $source_table) : "nodegoat_to.id");
											
			foreach (['in' => (array)$arr_network_connections['in'], 'out' => (array)$arr_network_connections['out']] as $in_out => $arr_in_out) {
				foreach ($arr_in_out as $ref_type_id => $arr_object_connections) {
					
					$arr_tables = [];
					
					foreach ((array)$arr_object_connections['object_sub_details'] as $object_sub_details_id => $arr_object_sub_descriptions) {
						foreach ($arr_object_sub_descriptions as $object_sub_description_id => $arr_connection) {
							
							$path = implode('-', $arr_connection['path']);
							$ref_unique_id = uniqid();
							
							if ($in_out == 'out') {
								if ($arr_connection['use_object_description_id']) {
									$sql .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable(StoreType::getTypeObjectDescriptionValueType($arr_connection['type_id'], $arr_connection['use_object_description_id']))." to_def_".$ref_unique_id." ON (to_def_".$ref_unique_id.".object_id IN (".$sql_source_tables.") AND to_def_".$ref_unique_id.".object_description_id = ".$arr_connection['use_object_description_id'].")";
									$ref_table = 'to_def_'.$ref_unique_id.'.ref_object_id';
								} else {
									$sql .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." tos_".$ref_unique_id." ON (tos_".$ref_unique_id.".object_id IN (".$sql_source_tables."))
										JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable(StoreType::getTypeObjectSubDescriptionValueType($arr_connection['type_id'], $object_sub_details_id, $object_sub_description_id))." tos_def_".$ref_unique_id." ON (tos_def_".$ref_unique_id.".object_sub_id = tos_".$ref_unique_id.".id AND tos_def_".$ref_unique_id.".object_sub_description_id = ".$object_sub_description_id.")";
									$ref_table = 'tos_def_'.$ref_unique_id.'.ref_object_id';
								}
							} else {
								if ($arr_connection['use_object_description_id']) {
									$sql .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable(StoreType::getTypeObjectDescriptionValueType($arr_connection['ref_type_id'], $arr_connection['use_object_description_id']))." to_def_".$ref_unique_id." ON (to_def_".$ref_unique_id.".ref_object_id IN (".$sql_source_tables.") AND to_def_".$ref_unique_id.".object_description_id = ".$arr_connection['use_object_description_id'].")";
									$ref_table = 'to_def_'.$ref_unique_id.'.object_id';
								} else {
									$sql .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable(StoreType::getTypeObjectSubDescriptionValueType($arr_connection['ref_type_id'], $object_sub_details_id, $object_sub_description_id))." tos_def_".$ref_unique_id." ON (tos_def_".$ref_unique_id.".ref_object_id IN (".$sql_source_tables.") AND tos_def_".$ref_unique_id.".object_sub_description_id = ".$object_sub_description_id.")
										JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." tos_".$ref_unique_id." ON (tos_".$ref_unique_id.".id = tos_def_".$ref_unique_id.".object_sub_id)";
									$ref_table = 'tos_'.$ref_unique_id.'.object_id';
								}
							}
							$arr_tables[] = $ref_table;
							$arr_sql_ref_tables[$path.'-'.($in_out == 'out' ? $arr_connection['ref_type_id'] : $arr_connection['type_id'])][] = $ref_table;
						}
					}
					
					foreach ((array)$arr_object_connections['object_descriptions'] as $object_description_id => $arr_connection) {
						
						$path = implode('-', $arr_connection['path']);
						$table_name = 'to_def_'.implode('_', $arr_connection['path']).'_'.$arr_connection['ref_type_id'].'_'.$object_description_id;
						
						if ($in_out == 'out') {
							$sql .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable(StoreType::getTypeObjectDescriptionValueType($arr_connection['type_id'], $object_description_id))." ".$table_name." ON (".$table_name.".object_id IN (".$sql_source_tables.") AND ".$table_name.".object_description_id = ".$object_description_id.")";
							$ref_table = $table_name.'.ref_object_id';
						} else {
							$sql .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable(StoreType::getTypeObjectDescriptionValueType($arr_connection['ref_type_id'], $object_description_id))." ".$table_name." ON (".$table_name.".ref_object_id IN (".$sql_source_tables.") AND ".$table_name.".object_description_id = ".$object_description_id.")";
							$ref_table = $table_name.'.object_id';
						}
						$arr_tables[] = $ref_table;
						$arr_sql_ref_tables[$path.'-'.($in_out == 'out' ? $arr_connection['ref_type_id'] : $arr_connection['type_id'])][] = $ref_table;
					}	
					
					if ($arr_network_connections['connections'][$ref_type_id]) {
						$arr_sql_type_objects[$ref_type_id] = array_merge((array)$arr_sql_type_objects[$ref_type_id], $arr_tables);
						$sql .= $func_connection($arr_network_connections['connections'][$ref_type_id], $arr_tables);
					} else {
						$arr_sql_type_objects['out'] = array_merge((array)$arr_sql_type_objects['out'], $arr_tables);
					}
				}
			}
			
			return $sql;
		};
		
		$arr_type_network_start = current($arr_type_network_paths['connections']);
		
		$sql = "FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					".$func_connection($arr_type_network_start, false, true)."
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." to_to ON (to_to.id IN (".implode(',', $arr_sql_type_objects['out'])."))";
				
		return ['sql' => $sql, 'arr_sql_type_objects' => $arr_sql_type_objects, 'arr_sql_ref_tables' => $arr_sql_ref_tables];
	}
	
	public function run($object_id, $to_object_id, $steps) {
		
		$this->object_id = $object_id;
		$this->to_object_id = $to_object_id;
		$this->steps = $steps;
		
		$this->arr_step[$this->object_id][] = [];

		for ($this->cur_step = 1; ($this->cur_step <= $this->steps && $this->arr_step); $this->cur_step++) {
		
			if ($this->to_object_id) {
				$this->arr_objects_passed += array_keys($this->arr_step);
			}
			
			$arr_step = $this->arr_step;
			$this->arr_step = [];
			
			foreach ($arr_step as $object_id => $arr_lists) {
				if ($this->to_object_id) {
					$this->doTraceToObjectNetwork($object_id, $arr_lists);
				} else {
					$this->doTraceObjectNetwork($object_id, $arr_lists);
				}
			}
		}
				
		$this->arr_type_object_ids = [$this->type_id => [$this->object_id => $this->object_id]]; // Start with the originating object id 
		foreach ($this->arr_objects_match as $arr_list) {
			foreach ($arr_list as $value) {
				$this->arr_type_object_ids[$value['type_id']][$value['id']] = $value['id'];
			}
		}
    }
    
    private function doTraceObjectNetwork($object_id, $arr_lists) {
		
		$arr_object_network = $this->findObjectNetwork($object_id);
		
		foreach ($arr_lists as $arr_list) {
			if ($arr_object_network) {
				foreach ($arr_object_network as $key => $value) {
					$cur_list = $arr_list;
					$cur_list[] = $value;
					if ($this->cur_step == $this->steps) {
						$this->arr_objects_match[] = $cur_list;
					} else {
						$this->arr_step[$key][] = $cur_list; // Use all possible connections for the next round
					}
				}
			} else {
				$this->arr_objects_match[] = $arr_list;
			}
		}
		
		$this->steps_total = $this->cur_step;
	}
		
	private function doTraceToObjectNetwork($object_id, $arr_lists) {
		
		$arr_object_network = $this->findObjectNetwork($object_id);
		
		foreach ($arr_lists as $arr_list) {
			foreach ($arr_object_network as $key => $value) {
				$cur_list = $arr_list;
				$cur_list[] = $value;
				if ($this->to_object_id == $key) {
					$this->arr_objects_match[] = $cur_list;
					$this->arr_objects_passed[] = $object_id; // Object found in direct connection to end object, no need to continue with this object
					$this->steps_total = $this->cur_step;
				} else {
					$this->arr_step[$key][] = $cur_list; // No connection found, use all possible connections so wait until next round to exclude object from next results
				}
			}
		}
	}
	
	private function findObjectNetwork($object_id) {
	
		$arr = [];
		
		// Weak
		if ($this->sql_tables_reverse) {
			
			$this->stmt_reverse->bindParameters(['id1' => $object_id, 'id2' => $object_id]);
					
			$res = $this->stmt_reverse->execute();

			while ($arr_row = $res->fetchRow()) {
				
				$id = $arr_row[0];
				
				if (!in_array($id, $this->arr_objects_passed) || $id == $this->to_object_id) {
					$arr[$id] = ['id' => $id, 'type_id' => $arr_row[1], 'weight' => 1];
				}
			}
		}
		
		// Strong
		$this->stmt->bindParameters(['id1' => $object_id, 'id2' => $object_id]);
				
		$res = $this->stmt->execute();

		while ($arr_row = $res->fetchRow()) {
			
			$id = $arr_row[0];
			
			if (!in_array($id, $this->arr_objects_passed) || $id == $this->to_object_id) {
				$arr[$id] = ['id' => $id, 'type_id' => $arr_row[1], 'weight' => ($arr[$id]['weight'] ? 3 : 2)];
			}
		}
		
		return $arr;
	}
	
	public function getLists() {
		
		return $this->arr_objects_match;
	}
	
	public function getTypeObjectIDs() {
		
		return $this->arr_type_object_ids;
	}
	
	public function getTypeObjectNetwork() {
		
		$this->arr_type_object_network_paths = [];
	
		foreach ($this->arr_objects_match as $arr_network_list) {
			
			$cur_entry = &$this->arr_type_object_network_paths;
			
			foreach ($arr_network_list as $step => $value) {
				
				$cur_entry = &$cur_entry['connections'];
				$cur_entry[$value['id']]['value'] = $value;
				$cur_entry = &$cur_entry[$value['id']];
			}
		}
		
		return $this->arr_type_object_network_paths;
	}
	
	public function getTypeObjectsSQL() {
		
		// Generate the queries to get the objects for each type between 'from' and the 'to' object
				
		$arr = [];
		
		if ($this->arr_type_object_ids[$this->to_type_id]) {
		
			$arr['run'] = "
				DROP TABLE IF EXISTS to_from_temp;
				CREATE TEMPORARY TABLE to_from_temp (
					id INT(11) NOT NULL,
					PRIMARY KEY (id)
				);
				DROP TABLE IF EXISTS to_to_temp;
				CREATE TEMPORARY TABLE to_to_temp (
					id INT(11) NOT NULL,
					PRIMARY KEY (id)
				);
				INSERT INTO to_from_temp (id) VALUES (".implode("),(", $this->arr_type_object_ids[$this->type_id]).");
				INSERT INTO to_to_temp (id) VALUES (".implode("),(", $this->arr_type_object_ids[$this->to_type_id]).");";
			
			foreach ($this->arr_sql_type_objects as $type_id => $arr_sql_columns) {
				
				if ($type_id == 'out') {
					continue;
				}
			
				$arr[$type_id] = "SELECT to_in_path.id
						".$this->sql_tables."
						JOIN to_from_temp ON (to_from_temp.id = nodegoat_to.id)
						JOIN to_to_temp ON (to_to_temp.id = to_to.id)
						JOIN ".DB::getTable("DATA_NODEGOAT_TYPE_OBJECTS")." to_in_path ON (to_in_path.id IN (".implode(',', $arr_sql_columns)."))
					GROUP BY to_in_path.id";
			}
		}
		
		return $arr;
	}
	
	public function getTotalSteps() {
				
		return $this->steps_total;
	}
}
