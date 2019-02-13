<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class FilterTypeObjects extends GenerateTypeObjects {

	private $arr_nr_object_tables = [];
	private $arr_translate_filter_codes = [];
	
	private static $arr_system_filter_keys = ['query' => true, 'queries' => true, 'query_dependent' => true, 'table' => true, 'object_filter_parsed' => true];
		
	public function setFilter($arr_filters, $filter_object_subs = false) {
		
		$arr_filters = ($arr_filters ?: []);
		if (!is_array($arr_filters)) {
			$arr_filters = [$arr_filters];
		}
		
		$arr_collect_filter = [];
		
		foreach ($arr_filters as $key => $arr_filter) {
			
			if (is_int($key) || strpos($key, 'filter_') === 0) { // If filter key is numeric or has a 'filter_' prefix, process as seperate filter
				$this->parseFilter($arr_filter, $filter_object_subs);
			} else {
				$arr_collect_filter[$key] = $arr_filter;
			}
		}
		
		if ($arr_collect_filter) {
			$this->parseFilter($arr_collect_filter, $filter_object_subs);
		}
	}
	
	private function parseFilter($arr_filter_parse, $filter_object_subs = false) {
		
		// $arr_filter_parse = search value or array("search" => string/array, "query" => array => string, "objects" => array/int, "object_subs" => array/int, "date" => array("start", "end"), "object_filter" => object definition array, "object_versioning" => array('version' => array, 'source' => object/object_sub/both, 'audited' => no/yes/both, 'users' => array), "referenced_object" => array => object_id/array("object_id", "object_descriptions", "object_sub_descriptions", "object_sub_locations"))
				
		// Prepare
				
		$arr_filter_parse = ($arr_filter_parse ?: []);
		$arr_filter_parse = (is_array($arr_filter_parse) ? $arr_filter_parse : ['search' => $arr_filter_parse]);

		$arr_filter = [];
		
		if ($arr_filter_parse['objects']) {
			
			$arr_objects = arrParseRecursive((array)$arr_filter_parse['objects']);
			
			$arr_filter['objects'] = $arr_objects;
			$this->arr_sql_filter['objects'] = ($this->arr_sql_filter['objects'] ? array_merge_recursive($this->arr_sql_filter['objects'], $arr_objects) : $arr_objects);
		}
		
		if ($arr_filter_parse['query']) {
			
			if (!is_array($arr_filter_parse['query'])) {
				$arr_filter_parse['query'] = [$arr_filter_parse['query']];
			}
			
			$arr_filter['query'] = $arr_filter_parse['query'];
		}
		
		if ($arr_filter_parse['query_dependent']) {
			
			if (!is_array($arr_filter_parse['query_dependent'])) {
				$arr_filter_parse['query_dependent'] = [$arr_filter_parse['query_dependent']];
			}
			
			$arr_filter['query_dependent'] = $arr_filter_parse['query_dependent'];
		}
				
		// Search
				
		if ($arr_filter_parse['search'] || $arr_filter_parse['search_name']) {
			
			$this->parseFilterObjectName($arr_filter_parse, $arr_filter);
		}

		// Date
		if ($arr_filter_parse['date'] || $arr_filter_parse['date_int']) {
			
			$this->parseFilterObjectDate($arr_filter_parse, $arr_filter, $filter_object_subs);
		}
		
		if ($arr_filter_parse['object_filter_form']) {
						
			$arr_filter_parse['object_filter'] = $this->parseFilterObjectForm($arr_filter_parse['object_filter_form']);
		}
		
		// Object Filter
		if ($arr_filter_parse['object_filter']) {
			
			$arr_filter['object_filter'] = $arr_filter_parse['object_filter'];
		
			if (arrIsAssociative($arr_filter['object_filter'])) {
				
				$arr_filter['object_filter'] = [$arr_filter['object_filter']];
			}
			
			$arr_filter_parse['object_filter_parsed'] = $this->parseFilterObject($arr_filter['object_filter'], false, $filter_object_subs);
		}
		
		if ($arr_filter_parse['object_filter_parsed']) {
			
			// Set all object filters, matching ANY or INCLUDING filter => OR/AND
			if ($arr_filter_parse['object_filter_parsed']['filter']) {
				
				$this->arr_sql_filter['filter'][] = "(".implode('', $arr_filter_parse['object_filter_parsed']['filter']).")";
			}
			if ($filter_object_subs) {
				
				foreach ((array)$arr_filter_parse['object_filter_parsed']['object_subs'] as $object_sub_details_id => $value) {
					
					if ($value['filter']) {
						
						$this->arr_sql_filter_subs[$object_sub_details_id]['filter'][] = "(".implode('', $value['filter']).")";
						$this->arr_filter_object_sub_details_ids[$object_sub_details_id] = true;
					}
				}
			}
		}
		
		// Object Versioning
		if ($arr_filter_parse['object_versioning']) {
			
			$this->parseFilterObjectVersioning($arr_filter_parse, $arr_filter);
		}
		
		if ($arr_filter_parse['object_discussion']) {
			
			$this->parseFilterObjectVersioningDiscussion($arr_filter_parse, $arr_filter);
		}
		
		if ($arr_filter_parse['object_dating']) {
			
			$this->parseFilterObjectVersioningDating($arr_filter_parse, $arr_filter);
		}
		
		// Referenced Object
		if ($arr_filter_parse['referenced_object']) {
			
			$this->parseFilterObjectReferenced($arr_filter_parse, $arr_filter);
		}
		
		// Sub-object-details
		
		if ($arr_filter_parse['object_sub_details']) {
			
			$this->parseFilterObjectSubDetails($arr_filter_parse, $arr_filter, $filter_object_subs);
		}
		
		// Sub-objects
		
		if ($arr_filter_parse['object_subs']) {
				
			$arr_object_subs = arrParseRecursive((array)$arr_filter_parse['object_subs']);
				
			$arr_filter['object_subs'][] = $arr_object_subs;
			
			if ($filter_object_subs) {
				$this->arr_sql_filter['object_subs'] = ($this->arr_sql_filter['object_subs'] ? array_merge_recursive($this->arr_sql_filter['object_subs'], $arr_object_subs) : $arr_object_subs);
			}
		}
		
		// When querying for sub-objects, also look up the corresponding object ids for efficiency/performance
		
		if ($arr_object_subs) {

			if (!$arr_filter_parse['objects']) {
				
				if (count($arr_object_subs) > 50) {
					
					$table_name = $this->storeIdsTemporarily($arr_object_subs);
								
					$sql_where = "JOIN ".$table_name." temp_object_sub_ids ON (temp_object_sub_ids.id = nodegoat_tos.id)";
					
				} else {
					
					$sql_where = "WHERE nodegoat_tos.id IN (".implode(',', $arr_object_subs).")";
				}
				
				$arr_filter_parse['queries'][] = "SELECT object_id AS id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
					".$sql_where."
				";
			}
		}
		
		// Queries

		if ($arr_filter_parse['queries']) {
			
			$arr_filter_parse['query'][] = "SELECT DISTINCT id
				FROM (
					(".implode(") UNION ALL (", $arr_filter_parse['queries']).")
				) AS foo
			";
		}
		
		// Query
		
		if ($arr_filter_parse['query']) {
			
			foreach ($arr_filter_parse['query'] as $sql) {
				
				$table_name = $this->generateTemporaryTableName('temp_query_'.$this->type_id, $sql);
				
				$this->addPre($table_name,
					[
						'table_name' => $table_name,
						'settings' => "
							id INT,
							PRIMARY KEY (id)",
						'query' => $sql
					]
				);
				
				$this->arr_sql_filter['table'][] = ['table_name' => DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name)];
			}
		}
		
		// Query Dependent (reinit-able)
		
		if ($arr_filter_parse['query_dependent']) {
			
			foreach ($arr_filter_parse['query_dependent'] as $sql) {
				
				$table_name = $this->generateTemporaryTableName('temp_query_'.$this->type_id, $sql);
				
				$this->addPre($table_name,
					[
						'table_name' => $table_name,
						'settings' => "
							id INT,
							PRIMARY KEY (id)",
						'query' => $sql
					],
					false,
					false,
					true
				);
				
				$this->arr_sql_filter['table'][] = ['table_name' => DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name)];
			}
		}
		
		// Table
		
		if ($arr_filter_parse['table']) {
			
			if (!is_array($arr_filter_parse['table'])) {
				$arr_filter_parse['table'] = [$arr_filter_parse['table']];
			}
			
			foreach ($arr_filter_parse['table'] as $value) {
				
				if (!is_array($value)) {
					$value = ['table_name' => $value];
				}
				
				$this->arr_sql_filter['table'][] = $value;
				$arr_filter['table'][] = $value;
			}
		}
		
		if ($arr_filter) {
			
			$this->addDepth($arr_filter);
		
			$this->arr_combine_filters = ($this->arr_combine_filters ? array_merge_recursive($this->arr_combine_filters, $arr_filter) : $arr_filter);
		}
	}
	
	// Filter Functions
	
	public function parseFilterObjectName(&$arr_filter_parse, &$arr_filter_collect) {
		
		if ($arr_filter_parse['search_name']) {
			
			$arr_filter_collect['search_name'] = $arr_filter_parse['search_name'];
			$arr_filter_search = $arr_filter_parse['search_name'];
			$search_type = 'name';
		} else {
			
			$arr_filter_collect['search'] = $arr_filter_parse['search'];
			$arr_filter_search = $arr_filter_collect['search'];
			$search_type = 'search';
		}
		
		if (!is_array($arr_filter_search)) {
			
			$str_filter_search = trim($arr_filter_search);
			$arr_filter_search = [];
				
			$pos = strpos($str_filter_search, '[');
			
			if ($pos !== false) {

				while ($pos !== false) {
					
					$pos_end = strpos($str_filter_search, ']');
					
					if (!$pos_end) {
						break;
					}
					
					$str_query = substr($str_filter_search, $pos, ($pos_end + 1) - ($pos));
					$arr_filter_search[] = $str_query;
					
					$str_filter_search = substr($str_filter_search, 0, $pos).substr($str_filter_search, $pos_end + 1);
					
					$pos = strpos($str_filter_search, '[');
				}
				
				$str_filter_search = trim($str_filter_search);
			}
			
			if ($str_filter_search) {
				
				$arr_filter_search = array_merge($arr_filter_search, explode(' ', $str_filter_search));
			}
		}
		$arr_filter_search = arrParseRecursive($arr_filter_search, 'trim');
		
		$search_object_id = false;
		$search_object_sub_id = false;
		
		$arr_object_id = self::decodeTypeObjectId($arr_filter_search[0]);
		if ($arr_object_id && $arr_object_id['type_id'] == $this->type_id) {
			$search_object_id = $arr_object_id['object_id'];
		} else if (substr($arr_filter_search[0], 0, 7) == 'object:') {
			$search_object_id = substr($arr_filter_search[0], 7);
		} else if (substr($arr_filter_search[0], 0, 10) == 'subobject:') {
			$search_object_sub_id = substr($arr_filter_search[0], 10);
		}
		
		if ($search_object_id) {
			
			$this->arr_sql_filter['search'] = "SELECT id FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." WHERE id = ".(int)$search_object_id."";
			
		} else if ($search_object_sub_id) {
			
			$this->arr_sql_filter['search'] = "SELECT object_id AS id FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." WHERE id = ".(int)$search_object_sub_id."";
			
		} else if ($arr_filter_search && (arrHasKeysRecursive('object_description_in_search', $this->arr_type_set['object_descriptions'], true) || arrHasKeysRecursive('object_sub_description_in_search', $this->arr_type_set['object_sub_details'], true))) {
			
			if (substr($arr_filter_search[0], 0, 5) == 'name:') {
				
				$arr_filter_search[0] = substr($arr_filter_search[0], 5);
				$search_type = 'name';
			}
			
			$arr_sql_search_union = [];
			$arr_sql_connect = [];
			
			$use_information_retrieval = true;
			
			if ($use_information_retrieval) {
				
				try {
					$str_host_ir = cms_nodegoat_definitions::getInformationRetrievalHost();
				} catch (Exception $e) { }
				
				if (!$str_host_ir) {
					$use_information_retrieval = false;
				}
			}
			
			if ($use_information_retrieval) {
				
				$table_name_ir = $this->generateTemporaryTableName('temp_search_ir_'.$this->type_id);
				$table_name_ir_full = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name_ir);

				$ir = new QueryTypeObjectsInformationRetrieval($str_host_ir);
				
				$ir->setQuery($arr_filter_search);
				$ir->setSQLTableName($table_name_ir_full);
				
				$arr_type_sets_selection = [];
			}
			
			foreach (StoreType::getTypeObjectPath($search_type, $this->type_id) as $arr_type_object_path_value) {
				
				$org_object_description_id = $arr_type_object_path_value['org_object_description_id'];
				$org_object_sub_details_id = $arr_type_object_path_value['org_object_sub_details_id'];
				$ref_object_description_id = $arr_type_object_path_value['ref_object_description_id'];
				$ref_object_sub_details_id = $arr_type_object_path_value['ref_object_sub_details_id'];
				
				if ($arr_sql_connect[$org_object_description_id.'_'.$org_object_sub_details_id]) { // Reset old nested paths
					
					$pos = array_search($org_object_description_id.'_'.$org_object_sub_details_id, array_keys($arr_sql_connect));
					$arr_sql_connect = ($pos ? array_slice($arr_sql_connect, 0, $pos, true) : []);
				}
										
				if (!$arr_type_object_path_value['is_reference'] || $arr_type_object_path_value['is_dynamic']) {
					
					$arr_sql_tables_connect = [];
					
					if ($use_information_retrieval) {
						$sql_table_source = 's.id';
					} else {
						$sql_table_source = ($ref_object_sub_details_id ? 's_tos.object_id' : ($ref_object_description_id ? 's.object_id' : 's.id'));
					}
										
					foreach (array_reverse($arr_sql_connect, true) as $connect_table_id => $arr_connect_from_table_ids) { // Reversed order (start from the end); 'org' connects based on 'ref' instead of 'ref' from 'org'
						
						$table_name = "nodegoat_to_def_con_".$connect_table_id;
						$version_select = $this->generateVersion('record', $table_name);
						
						if ($arr_connect_from_table_ids[1]) {
							
							$version_select_tos = $this->generateVersion('object_sub', $table_name.'_tos');
							
							$arr_sql_tables_connect[] = "
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." AS ".$table_name." ON (".$table_name.".object_sub_description_id = ".$arr_connect_from_table_ids[0]." AND ".$table_name.".ref_object_id = ".$sql_table_source." AND ".$version_select.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_tos ON (".$table_name."_tos.object_sub_details_id = ".$arr_connect_from_table_ids[1]." AND ".$table_name."_tos.id = ".$table_name.".object_sub_id AND ".$version_select_tos.")
							";
						
							$sql_table_source = $table_name."_tos.object_id";
						} else {
							
							$arr_sql_tables_connect[] = " JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." AS ".$table_name." ON (".$table_name.".object_description_id = ".$arr_connect_from_table_ids[0]." AND ".$table_name.".ref_object_id = ".$sql_table_source." AND ".$version_select.")";
						
							$sql_table_source = $table_name.".object_id";
						}
					}
					
					if ($use_information_retrieval) {

						$arr_sql_search_match = [];
				
						foreach ($arr_filter_search as $key => $value) {
							
							$arr_sql_search_match[] = "CASE
								WHEN nodegoat_ir_results.query_id_".$key." = TRUE THEN ".($arr_type_object_path_value['sort']+1)."
								ELSE NULL
							END AS match_".$key;
						}
						
						$version_select = $this->generateVersion('search', 's');
						
						if ($ref_object_sub_details_id) {
							$collection_id = QueryTypeObjectsInformationRetrieval::getCollectionID('object_sub_description');
							$field_id = $ref_object_description_id;
							$arr_type_sets_selection[$arr_type_object_path_value['ref_type_id']]['object_sub_descriptions'][$field_id] = $field_id;
						} else if ($ref_object_description_id) {
							$collection_id = QueryTypeObjectsInformationRetrieval::getCollectionID('object_description');
							$field_id = $ref_object_description_id;
							$arr_type_sets_selection[$arr_type_object_path_value['ref_type_id']]['object_descriptions'][$field_id] = $field_id;
						} else {
							$collection_id = QueryTypeObjectsInformationRetrieval::getCollectionID('object_value');
							$field_id = 0;
							$arr_type_sets_selection[$arr_type_object_path_value['ref_type_id']]['object_value'] = true;
						}

						$arr_sql_search_union[] = "SELECT
							".$sql_table_source." AS id, ".implode(',', $arr_sql_search_match)."
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." AS s
								JOIN ".$table_name_ir_full." AS nodegoat_ir_results ON (nodegoat_ir_results.type_id = s.type_id AND nodegoat_ir_results.object_id = s.id AND nodegoat_ir_results.collection_id = ".(int)$collection_id." AND field_id = ".(int)$field_id.")
								".implode('', $arr_sql_tables_connect)."
							WHERE s.type_id = ".$arr_type_object_path_value['ref_type_id']." AND ".$version_select."
						";
					} else {
							
						$arr_sql_search_objects = [];
						$arr_sql_search_match = [];

						if (!$ref_object_description_id) { // In name
							
							$version_select = $this->generateVersion('search', 's');
													
							foreach ($arr_filter_search as $key => $value) {
								
								$str_value = DBFunctions::str2Search($value);
								
								$arr_sql_search_objects[] = "s.name LIKE '%".$str_value."%'";
								$arr_sql_search_match[] = "CASE
									WHEN s.name LIKE '%".$str_value."%' THEN ".($arr_type_object_path_value['sort']+1)."
									ELSE NULL
								END AS match_".$key;
							}
							
							$arr_sql_search_union[] = "SELECT
								".$sql_table_source." AS id, ".implode(',', $arr_sql_search_match)."
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." AS s
									".implode('', $arr_sql_tables_connect)."
								WHERE s.type_id = ".$arr_type_object_path_value['ref_type_id']." AND (".implode(' OR ', $arr_sql_search_objects).") AND ".$version_select."
							";
						} else { // In object description

							$value_type = $arr_type_object_path_value['value_type'];
							
							$sql_column = StoreTypeObjects::formatFromSQLValue($value_type, "s.".StoreType::getValueTypeValue($value_type, 'search'));
							$version_select = $this->generateVersion('search', 's', $value_type);		
							
							foreach ($arr_filter_search as $key => $value) {
								
								$str_value = DBFunctions::str2Search($value);
								
								$arr_sql_search_objects[] = $sql_column." LIKE '%".$str_value."%'";
								$arr_sql_search_match[] = "CASE
									WHEN ".$sql_column." LIKE '%".$str_value."%' THEN ".($arr_type_object_path_value['sort']+1)."
									ELSE NULL
								END AS match_".$key;
							}
							
							if ($ref_object_sub_details_id) {
								
								$version_select_tos = $this->generateVersion('object_sub', 's_tos');
								
								$arr_sql_search_union[] = "SELECT
									".$sql_table_source." AS id, ".implode(',', $arr_sql_search_match)."
										FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'search')." AS s
										JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS s_tos on (s_tos.id = s.object_sub_id AND s_tos.object_sub_details_id = ".$ref_object_sub_details_id." AND ".$version_select_tos.")
										".implode('', $arr_sql_tables_connect)."
									WHERE s.object_sub_description_id = ".$ref_object_description_id." AND (".implode(' OR ', $arr_sql_search_objects).") AND ".$version_select."
								";
							} else {
								
								$arr_sql_search_union[] = "SELECT
									".$sql_table_source." AS id, ".implode(',', $arr_sql_search_match)."
										FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'search')." AS s
										".implode('', $arr_sql_tables_connect)."
									WHERE s.object_description_id = ".$ref_object_description_id." AND (".implode(' OR ', $arr_sql_search_objects).") AND ".$version_select."
								";
							}
						}
					}
				} else {
					
					$arr_sql_connect[$org_object_description_id.'_'.$org_object_sub_details_id] = [$ref_object_description_id, $ref_object_sub_details_id]; // Store object_description_id path
				}
			}
			
			if (!$arr_sql_search_union) { // Mismatch between the data model and cached search path (could currently be building it)
				return false;
			}
			
			if ($use_information_retrieval) {
					
				$this->addPre($table_name_ir,
					[
						'table_name' => $table_name_ir,
						'settings' => [
							'columns' => $ir->getSQLColumns()
						],
						'function' => function() use ($ir, $arr_type_sets_selection) {
							
							$ir->setTypeSetsSelection($arr_type_sets_selection);
							$ir->query();
							
							return $ir->getSQLInsert();
						}
					],
					false,
					true
				);
			}
			
			$table_name = $this->generateTemporaryTableName('temp_search_'.$this->type_id, serialize($arr_sql_search_union));
			$table_name_full = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name);
			
			$arr_sql_select = [];
			$arr_sql_columns = [];
			$arr_sql_fields = [];
			$arr_sql_search_match_sum = [];
			
			foreach ($arr_filter_search as $key => $value) {
				
				$arr_sql_select[] = "MIN(object_name_search.match_".$key.")";
				$arr_sql_columns[] = "match_".$key." SMALLINT";
				$arr_sql_fields[] = "match_".$key."";
				$arr_sql_table_fields[] = "object_name_search.match_".$key."";
				$arr_sql_search_match_sum[] = "MIN(object_name_search.match_".$key.") IS NOT NULL";
			}
			
			$sql_query = "INSERT INTO ".$table_name_full."
					(id, ".implode(',', $arr_sql_fields).")
					SELECT
						object_name_search.id, ".implode(',', $arr_sql_select)."
							FROM (".implode(" UNION ALL ", $arr_sql_search_union).") AS object_name_search
					GROUP BY object_name_search.id
					HAVING ".implode(" AND ", $arr_sql_search_match_sum)."
			";
			
			$this->addPre($table_name,
				[
					'table_name' => $table_name,
					'settings' => [
						'columns' => "id INT,
						".implode(',', $arr_sql_columns).",
						PRIMARY KEY (id)",
						'indexes' => DBFunctions::createIndex($table_name_full, $arr_sql_fields)
					],
					'sql' => $sql_query
				],
				false,
				true
			);
			
			$this->arr_sql_filter['table']['object_name_search'] = ['table_name' => $table_name_full, 'table_alias' => 'object_name_search'];
			
			// Order results by first matched position in the path
			$this->arr_columns_object['order_object_name_search'] = implode(" ASC,", $arr_sql_table_fields)." ASC";
			$this->arr_columns_object_group['order_object_name_search'] = implode(',', $arr_sql_table_fields);
		} else if ($arr_filter_search) {
			
			$arr_sql_search_objects = [];
			
			foreach ($arr_filter_search as $value) {
				
				$arr_sql_search_objects[] = "name LIKE '%".DBFunctions::str2Search($value)."%'";
			}
			
			$sql_query = "SELECT DISTINCT id FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." WHERE type_id = ".$this->type_id." AND (".implode(' AND ', $arr_sql_search_objects).")";
			
			$table_name = $this->generateTemporaryTableName('temp_search_'.$this->type_id, $sql_query);
			
			$this->addPre($table_name,
				[
					'table_name' => $table_name,
					'settings' => "
						id INT,
						PRIMARY KEY (id)",
					'query' => $sql_query
				],
				false,
				true
			);
			
			$this->arr_sql_filter['table'][] = ['table_name' => DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name)];
		}
	}
	
	public function parseFilterObjectDate(&$arr_filter_parse, &$arr_filter_collect, $filter_object_subs) {
	
		if ($arr_filter_parse['date_int']) {
			$arr_filter_collect['date_int'] = $arr_filter_parse['date_int'];
		} else {
			$arr_filter_collect['date'] = $arr_filter_parse['date'];
		}
		
		if ($arr_filter_parse['date']) {
			
			$arr_filter_date['start'] = StoreTypeObjects::date2Int($arr_filter_parse['date']['start'], '>');
			$arr_filter_date['end'] = StoreTypeObjects::date2Int($arr_filter_parse['date']['end'], '<');
		} else {
			
			$arr_filter_date['start'] = (int)$arr_filter_parse['date_int']['start'];
			$arr_filter_date['end'] = (int)$arr_filter_parse['date_int']['end'];
		}
		
		$arr_sql_date = $this->generateTablesColumnsObjectSubDate('[X]', true);
		
		if ($arr_filter_date['start'] && $arr_filter_date['end']) {
			$sql_date = "((".$arr_sql_date['column_start']." BETWEEN ".(int)$arr_filter_date['start']." AND ".(int)$arr_filter_date['end'].") OR (".$arr_sql_date['column_end']." BETWEEN ".(int)$arr_filter_date['start']." AND ".(int)$arr_filter_date['end'].") OR (".$arr_sql_date['column_start']." < ".(int)$arr_filter_date['start']." AND ".$arr_sql_date['column_end']." > ".(int)$arr_filter_date['end']."))";
		} else if ($arr_filter_date['start']) {
			$sql_date = "(".$arr_sql_date['column_end']." >= ".(int)$arr_filter_date['start'].")";
		} else if ($arr_filter_date['end']) {
			$sql_date = "(".$arr_sql_date['column_start']." <= ".(int)$arr_filter_date['end'].")";
		}
		
		if (!$sql_date) {
			return;
		}
			
		$versioning = 'active';		
		$version_select_to = self::generateVersioning($versioning, 'object', 'nodegoat_to');
		$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
			
		$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
			JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to.id AND ".$version_select_tos.")
			".str_replace('[X]', 'nodegoat_tos', $arr_sql_date['tables'])."
			WHERE nodegoat_to.type_id = ".$this->type_id."
				AND ".$version_select_to."
				AND ".str_replace('[X]', 'nodegoat_tos', $sql_date)."
		";
		
		if ($filter_object_subs) {
			
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$this->arr_sql_filter_subs[$object_sub_details_id]['filter'][] = str_replace('[X]', 'nodegoat_tos_'.$object_sub_details_id, $sql_date);
				$this->arr_filter_object_sub_details_ids[$object_sub_details_id] = true;
			}
		}
	}
	
	public function parseFilterObjectReferenced(&$arr_filter_parse, &$arr_filter_collect) {
		
		$arr_filter_collect['referenced_object'] = $arr_filter_parse['referenced_object'];
			
		if (!is_array($arr_filter_collect['referenced_object']) || (is_array($arr_filter_collect['referenced_object']) && !$arr_filter_collect['referenced_object'][0])) {
			
			$arr_filter_collect['referenced_object'] = [$arr_filter_collect['referenced_object']];
		}
		
		foreach ($arr_filter_collect['referenced_object'] as $arr_filter_referenced_type_object) {
			
			if (!is_array($arr_filter_referenced_type_object)) {
				$arr_filter_referenced_type_object = ['object_id' => $arr_filter_referenced_type_object];
			}
			
			$str_object_ids = implode(',', (array)arrParseRecursive($arr_filter_referenced_type_object['object_id']));
			$has_filter = ($arr_filter_referenced_type_object['filter'] ? true : false);				
			
			$versioning = 'active';		
			$version_select_to = self::generateVersioning($versioning, 'object', 'nodegoat_to');
			$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
			
			if ($has_filter && !is_array($arr_filter_referenced_type_object['filter'])) {
				
				$arr_filter_parse['queries'][] = "SELECT FALSE AS id";
			} else {
					
				if (!$has_filter || $arr_filter_referenced_type_object['filter']['object_sources']) {
					
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')." nodegoat_to_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_src.object_id AND ".$version_select_to.")
					WHERE nodegoat_to.type_id = ".$this->type_id." AND nodegoat_to_src.ref_object_id IN (".$str_object_ids.")";
				}
				if (!$has_filter || $arr_filter_referenced_type_object['filter']['object_descriptions']) {
					
					$sql_filter_ids = ($arr_filter_referenced_type_object['filter']['object_descriptions'] ? "IN (".implode(',', (array)arrParseRecursive($arr_filter_referenced_type_object['filter']['object_descriptions'])).")" : "= 0");
					
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
						WHERE ".self::generateVersioning($versioning, 'record', 'nodegoat_to_def')."
							AND nodegoat_to.type_id = ".$this->type_id." AND nodegoat_to_def.ref_object_id IN (".$str_object_ids.")
							".($has_filter ? "AND nodegoat_to_def.object_description_id ".$sql_filter_ids : "")."
					";
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
						WHERE nodegoat_to.type_id = ".$this->type_id." AND nodegoat_to_def_ref.ref_object_id IN (".$str_object_ids.") AND nodegoat_to_def_ref.state = 1
							".($has_filter ? "AND nodegoat_to_def_ref.object_description_id ".$sql_filter_ids : "")."
					";
				}
				if (!$has_filter || $arr_filter_referenced_type_object['filter']['object_description_sources']) {
					
					$sql_filter_ids = ($arr_filter_referenced_type_object['filter']['object_description_sources'] ? "IN (".implode(',', (array)arrParseRecursive($arr_filter_referenced_type_object['filter']['object_description_sources'])).")" : "= 0");
					
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')." nodegoat_to_def_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to_def_src.object_id AND nodegoat_to_def.object_description_id = nodegoat_to_def_src.object_description_id AND ".self::generateVersioning($versioning, 'record', 'nodegoat_to_def').")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
					WHERE nodegoat_to.type_id = ".$this->type_id." AND nodegoat_to_def_src.ref_object_id IN (".$str_object_ids.")
						".($has_filter ? "AND nodegoat_to_def_src.object_description_id ".$sql_filter_ids : "")."
					";
				}
				if (!$has_filter || $arr_filter_referenced_type_object['filter']['object_sub_locations']) {
					
					$sql_filter_ids = ($arr_filter_referenced_type_object['filter']['object_sub_locations'] ? "IN (".implode(',', (array)arrParseRecursive($arr_filter_referenced_type_object['filter']['object_sub_locations'])).")" : "= 0");
					
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE ".$version_select_tos."
							AND nodegoat_to.type_id = ".$this->type_id." AND nodegoat_tos.location_ref_object_id IN (".$str_object_ids.")
							".($has_filter ? "AND nodegoat_tos.object_sub_details_id ".$sql_filter_ids : "")."
					";
				}
				if (!$has_filter || $arr_filter_referenced_type_object['filter']['object_sub_sources']) {
					
					$sql_filter_ids = ($arr_filter_referenced_type_object['filter']['object_sub_sources'] ? "IN (".implode(',', (array)arrParseRecursive($arr_filter_referenced_type_object['filter']['object_sub_sources'])).")" : "= 0");
					
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')." nodegoat_tos_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_src.object_sub_id AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_to.type_id = ".$this->type_id." AND nodegoat_tos_src.ref_object_id IN (".$str_object_ids.")
						".($has_filter ? "AND nodegoat_tos.object_sub_details_id ".$sql_filter_ids : "")."
					";
				}
				if (!$has_filter || $arr_filter_referenced_type_object['filter']['object_sub_descriptions']) {
					
					$sql_filter_ids = ($arr_filter_referenced_type_object['filter']['object_sub_descriptions'] ? "IN (".implode(',', (array)arrParseRecursive($arr_filter_referenced_type_object['filter']['object_sub_descriptions'])).")" : "= 0");
					
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE ".self::generateVersioning($versioning, 'record', 'nodegoat_tos_def')."
							AND nodegoat_to.type_id = ".$this->type_id." AND nodegoat_tos_def.ref_object_id IN (".$str_object_ids.")
							".($has_filter ? "AND nodegoat_tos_def.object_sub_description_id ".$sql_filter_ids : "")."
					";
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_to.type_id = ".$this->type_id." AND nodegoat_tos_def_ref.ref_object_id IN (".$str_object_ids.") AND nodegoat_tos_def_ref.state = 1
							".($has_filter ? "AND nodegoat_tos_def_ref.object_sub_description_id ".$sql_filter_ids : "")."
					";
				}
				if (!$has_filter || $arr_filter_referenced_type_object['filter']['object_sub_description_sources']) {
					
					$sql_filter_ids = ($arr_filter_referenced_type_object['filter']['object_sub_description_sources'] ? "IN (".implode(',', (array)arrParseRecursive($arr_filter_referenced_type_object['filter']['object_sub_description_sources'])).")" : "= 0");
					
					$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')." nodegoat_tos_def_src
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_src.object_sub_id AND ".$version_select_tos.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
					WHERE nodegoat_to.type_id = ".$this->type_id." AND nodegoat_tos_def_src.ref_object_id IN (".$str_object_ids.")
						".($has_filter ? "AND nodegoat_tos_def_src.object_sub_description_id ".$sql_filter_ids : "")."
					";
				}
			}
		}
	}
	
	public function parseFilterObjectVersioning(&$arr_filter_parse, &$arr_filter_collect) {
	
		$arr_filter_collect['object_versioning'] = $arr_filter_parse['object_versioning'];
			
		$sql_user_ids = '';
		
		if ($arr_filter_collect['object_versioning']['users']['selection'] || $arr_filter_collect['object_versioning']['users']['self']) {
			$arr_user_ids = (array)arrParseRecursive($arr_filter_collect['object_versioning']['users']['selection']);
			if ($arr_filter_collect['object_versioning']['users']['self']) {
				$arr_user_ids = array_merge($arr_user_ids, $this->arr_scope['users']);
			}
			$sql_user_ids = implode(',', $arr_user_ids);
			$sql_users_operator_not = ($arr_filter_collect['object_versioning']['users']['exclude'] ? 'NOT' : '');
		}
		
		$arr_sql_versions = [];
		
		if ($arr_filter_collect['object_versioning']['version']) {
			if (in_array(1, $arr_filter_collect['object_versioning']['version'])) { // Added
				$arr_sql_versions[] = "ver.version = -1";
			}
			if (in_array(2, $arr_filter_collect['object_versioning']['version'])) { // Changed
				$arr_sql_versions[] = "ver.version > 0";
			}
			if (in_array(3, $arr_filter_collect['object_versioning']['version'])) { // Deleted
				$arr_sql_versions[] = "ver.version = 0";
			}
			$sql_versions = implode(' OR ', $arr_sql_versions);
		}
		
		$sql_audited = '';
		
		if ($arr_filter_collect['object_versioning']['audited'] && $arr_filter_collect['object_versioning']['audited'] != 'all') {
			$sql_audited = ($arr_filter_collect['object_versioning']['audited'] == 'yes' ? 'IS NOT FALSE' : 'IS FALSE');
		}
		
		$sql_date = $sql_date_object = false;
		
		if ($arr_filter_collect['object_versioning']['date']) {
			$sql_date = self::format2SQLDateRange($arr_filter_collect['object_versioning']['date']['start'], $arr_filter_collect['object_versioning']['date']['end'], 'ver.date');
			$sql_date_object = self::format2SQLDateRange($arr_filter_collect['object_versioning']['date']['start'], $arr_filter_collect['object_versioning']['date']['end'], 'nodegoat_to_date.date_object');
		}
		
		if ($arr_filter_collect['object_versioning']['source'] && ($sql_user_ids || $sql_versions || $sql_date)) {

			if ($arr_filter_collect['object_versioning']['source'] == 'object' || $arr_filter_collect['object_versioning']['source'] == 'all') {
			
				$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_VERSION')." ver ON (ver.object_id = nodegoat_to.id)
					WHERE nodegoat_to.type_id = ".$this->type_id
						.($sql_versions ? " AND (".$sql_versions.")" : "")
						.($sql_user_ids ? " AND ver.user_id ".$sql_users_operator_not." IN (".$sql_user_ids.")" : "")
						.($sql_audited ? " AND COALESCE(ver.date_audited, 0) ".$sql_audited : "")
						.($sql_date ? " AND ".$sql_date."" : "");

				$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_VERSION')." ver ON (ver.object_id = nodegoat_to.id)
					WHERE nodegoat_to.type_id = ".$this->type_id
						.($sql_versions ? " AND (".$sql_versions.")" : "")
						.($sql_user_ids ? " AND ver.user_id ".$sql_users_operator_not." IN (".$sql_user_ids.")" : "")
						.($sql_audited ? " AND COALESCE(ver.date_audited, 0) ".$sql_audited : "")
						.($sql_date ? " AND ".$sql_date."" : "");
			}
			
			if ($arr_filter_collect['object_versioning']['source'] == 'object_sub' || $arr_filter_collect['object_versioning']['source'] == 'all') {
				
				$arr_filter_parse['queries'][] = "SELECT nodegoat_tos.object_id AS id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to.id)
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_VERSION')." ver ON (ver.object_sub_id = nodegoat_tos.id)
					WHERE nodegoat_to.type_id = ".$this->type_id
						.($sql_versions ? " AND (".$sql_versions.")" : "")
						.($sql_user_ids ? " AND ver.user_id ".$sql_users_operator_not." IN (".$sql_user_ids.")" : "")
						.($sql_audited ? " AND COALESCE(ver.date_audited, 0) ".$sql_audited : "")
						.($sql_date ? " AND ".$sql_date."" : "");
					
				$arr_filter_parse['queries'][] = "SELECT nodegoat_tos.object_id AS id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to.id)
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_VERSION')." ver ON (ver.object_sub_id = nodegoat_tos.id)
					WHERE nodegoat_to.type_id = ".$this->type_id
						.($sql_versions ? " AND (".$sql_versions.")" : "")
						.($sql_user_ids ? " AND ver.user_id ".$sql_users_operator_not." IN (".$sql_user_ids.")" : "")
						.($sql_audited ? " AND COALESCE(ver.date_audited, 0) ".$sql_audited : "")
						.($sql_date ? " AND ".$sql_date."" : "");
			}				
		} else if ($sql_date_object) { // Filter object by its last date of interaction
		
			$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DATING')." nodegoat_to_date ON (nodegoat_to_date.object_id = nodegoat_to.id)
						WHERE nodegoat_to.type_id = ".$this->type_id."
							AND ".$sql_date_object."
			";
		}
	}
	
	public function parseFilterObjectVersioningDiscussion(&$arr_filter_parse, &$arr_filter_collect) {
		
		$arr_filter_collect['object_discussion'] = $arr_filter_parse['object_discussion'];
		
		$sql_date = self::format2SQLDateRange($arr_filter_collect['object_discussion']['date']['start'], $arr_filter_collect['object_discussion']['date']['end'], 'nodegoat_to_date.date_discussion');
					
		if ($arr_filter_collect['object_versioning']['users']['selection'] || $arr_filter_collect['object_versioning']['users']['self']) {
			
			$arr_user_ids = (array)arrParseRecursive($arr_filter_collect['object_versioning']['users']['selection']);
		
			if ($arr_filter_collect['object_versioning']['users']['self']) {
				$arr_user_ids = array_merge($arr_user_ids, $this->arr_scope['users']);
			}
			
			$sql_user_ids = implode(',', $arr_user_ids);
			$sql_users_operator_not = ($arr_filter_collect['object_versioning']['users']['exclude'] ? 'NOT' : '');
		}
		
		$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DATING')." nodegoat_to_date ON (nodegoat_to_date.object_id = nodegoat_to.id)
					".($sql_user_ids ? " JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DISCUSSION')." nodegoat_to_disc ON (nodegoat_to_disc.object_id = nodegoat_to.id)" : "")."
					WHERE nodegoat_to.type_id = ".$this->type_id."
						AND ".$sql_date."
						".($sql_user_ids ? "AND nodegoat_to_disc.user_id_edited ".$sql_users_operator_not." IN (".$sql_user_ids.")" : "")."
		";
	}
	
	public function parseFilterObjectVersioningDating(&$arr_filter_parse, &$arr_filter_collect) {
		
		$arr_filter_collect['object_dating'] = $arr_filter_parse['object_dating'];
		
		$sql_date = self::format2SQLDateRange($arr_filter_collect['object_dating']['date']['start'], $arr_filter_collect['object_dating']['date']['end'], 'nodegoat_to_date.date');
		
		$arr_filter_parse['queries'][] = "SELECT nodegoat_to.id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DATING')." nodegoat_to_date ON (nodegoat_to_date.object_id = nodegoat_to.id)
					WHERE nodegoat_to.type_id = ".$this->type_id."
						AND ".$sql_date."
		";
	}
	
	public function parseFilterObjectSubDetails(&$arr_filter_parse, &$arr_filter_collect, $filter_object_subs) {
		
		$arr_filter_collect['object_sub_details'] = $arr_filter_parse['object_sub_details'];
		
		$arr_object_sub_details_ids = [];
		
		foreach ((is_array($arr_filter_collect['object_sub_details']) ? $arr_filter_collect['object_sub_details'] : [$arr_filter_collect['object_sub_details']]) as $object_sub_details_id) {
			
			$use_object_sub_details_id = (int)$this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id']; // In case the sub-object is referenced
			
			$arr_object_sub_details_ids[] = $use_object_sub_details_id;
			
			if ($filter_object_subs) {
				$this->arr_filter_object_sub_details_ids[$object_sub_details_id] = true;
			}
		}
		
		if (!$arr_filter_parse['objects']) { // When there is no selection for objects, look up all objects that have the specified object_sub_details_ids
			
			$versioning = 'active';		
			$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
									
			$arr_filter_parse['queries'][] = "SELECT object_id AS id
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
				WHERE nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_object_sub_details_ids).")
					AND ".$version_select_tos."
			";
		}
	}
	
	// Filter Form
	
	public function parseFilterObjectForm($arr_filter_object_form) {
		
		$arr_filter_sources = [];
		$arr_filter_beacons = [];
				
		$func_translate_filter_codes = function($filter_code, $unique = false) { // Translate filter codes to unique codes when they are already in use
			
			if (!$filter_code) {
				return '';
			}
			
			if ($unique) {
				
				if ($this->arr_translate_filter_codes[$filter_code]) {
					$filter_code_use = uniqid('filter_');
				} else {
					$filter_code_use = str2Label($filter_code); // Discard non-valid characters
				}
				
				$this->arr_translate_filter_codes[$filter_code] = $filter_code_use;
			}
						
			return $this->arr_translate_filter_codes[$filter_code];
		};
				
		foreach ($arr_filter_object_form as $filter_code => $arr_filter_form) {
			
			$filter_code = $func_translate_filter_codes($filter_code, true);
			
			if ($arr_filter_form['source'] && $arr_filter_form['source']['filter_code']) {
				
				$arr_filter_form['source']['filter_code'] = $func_translate_filter_codes($arr_filter_form['source']['filter_code']);
				
				if ($arr_filter_form['source']['object_sub_description_id']) {
					if (!is_numeric($arr_filter_form['source']['object_sub_description_id'])) { // Other i.e. location & date
						$arr_filter_sources[$arr_filter_form['source']['filter_code']][$arr_filter_form['source']['type_id']][$arr_filter_form['source']['direction']]['object_sub_details'][$arr_filter_form['source']['object_sub_details_id']][$arr_filter_form['source']['object_sub_description_id']][$arr_filter_form['source']['filter_beacon']][$filter_code] = $arr_filter_form;
						$arr_filter_beacons[$arr_filter_form['source']['filter_code']][$arr_filter_form['source']['filter_beacon']] = $arr_filter_form['source']['filter_beacon'];
					} else {
						$arr_filter_sources[$arr_filter_form['source']['filter_code']][$arr_filter_form['source']['type_id']][$arr_filter_form['source']['direction']]['object_sub_details'][$arr_filter_form['source']['object_sub_details_id']][$arr_filter_form['source']['object_sub_description_id']][$filter_code] = $arr_filter_form;
					}
				} else if ($arr_filter_form['source']['object_description_id']) {
					$arr_filter_sources[$arr_filter_form['source']['filter_code']][$arr_filter_form['source']['type_id']][$arr_filter_form['source']['direction']]['object_descriptions'][$arr_filter_form['source']['object_description_id']][$filter_code] = $arr_filter_form;
				} else if ($arr_filter_form['source']['type_id']) {
					$arr_filter_sources[$arr_filter_form['source']['filter_code']][$arr_filter_form['source']['type_id']][$arr_filter_form['source']['direction']]['type'][$filter_code] = $arr_filter_form;
				}
			} else {
				
				$arr_filter_sources[0][0][0][$filter_code] = $arr_filter_form;
			}
		}
			
		$func_filtering = function(&$arr_filter_forms, $is_source = false) use ($arr_filter_sources, $arr_filter_beacons, &$func_filtering) {
		
			$arr = [];
			$is_last_filter = true;

			foreach ($arr_filter_forms as $filter_code => &$arr_filter_form) {
				
				$type_id = $arr_filter_form['type_id'];
				$arr_type_set = StoreType::getTypeSet($type_id);
				
				$arr_filter_form = self::cleanupFilterForm($type_id, $arr_filter_form, ['beacons' => $arr_filter_beacons[$filter_code]]);
				$arr_filter_form['filter_code'] = $filter_code;

				foreach ((array)$arr_filter_sources[$filter_code] as $source_type_id => $arr_filter_source_directions) {
					
					foreach ($arr_filter_source_directions as $source_direction => $arr_filter_source) {
						
						$is_last_filter = false;
						
						if ($arr_filter_source['type']) {
							$arr_filter_form['referenced_types'][$source_type_id]['any'][] = $func_filtering($arr_filter_source['type']);
						}
						
						foreach ((array)$arr_filter_source['object_descriptions'] as $source_object_description_id => $arr_filters_object_description) {
								
							$arr_filter_form_deep = $func_filtering($arr_filters_object_description);
							
							if ($source_direction == 'out' || !$source_direction) {
								$arr_filter_form['object_definitions'][$source_object_description_id][] = $arr_filter_form_deep;
							} else {
								$arr_filter_form['referenced_types'][$source_type_id]['object_definitions'][$source_object_description_id][] = $arr_filter_form_deep;
							}
						}
						foreach ((array)$arr_filter_source['object_sub_details'] as $source_object_sub_details_id => $arr_source_object_sub_descriptions) {
							foreach ($arr_source_object_sub_descriptions as $source_object_sub_description_id => $arr_filters_object_sub_description) {
								
								if (is_numeric($source_object_sub_description_id)) {
																	
									$arr_filter_form_deep = $func_filtering($arr_filters_object_sub_description);
									
									if ($source_direction == 'out' || !$source_direction) {
										$arr_filter_form['object_subs'][$source_object_sub_details_id]['object_sub_definitions'][$source_object_sub_description_id][] = $arr_filter_form_deep;
									} else {
										$arr_filter_form['referenced_types'][$source_type_id]['object_subs'][$source_object_sub_details_id]['object_sub_definitions'][$source_object_sub_description_id][] = $arr_filter_form_deep;
									}
								} else {

									if ($source_object_sub_description_id == 'location') {
										
										foreach ((array)$arr_filters_object_sub_description as $filter_beacon => $arr_filters_values) {
											
											if ($source_direction == 'out' || !$source_direction) {

												$arr_filter_form_deep = $func_filtering($arr_filters_values);

												foreach ($arr_filter_form['object_subs'][$source_object_sub_details_id]['object_sub_locations'] as $key => &$arr_object_sub_location) {
											
													if ($arr_object_sub_location['object_sub_location_reference']) {
														if ($filter_beacon == $arr_object_sub_location['object_sub_location_reference']['beacon']) {
															$arr_object_sub_location['object_sub_location_reference'][] = $arr_filter_form_deep;
														}
													}
												}
												unset($arr_object_sub_location);
											} else {
												
												$arr_filter_form_deep = $func_filtering($arr_filters_values);
														
												$arr_filter_form['referenced_types'][$source_type_id]['object_subs'][$source_object_sub_details_id]['object_sub_location_reference'][] = $arr_filter_form_deep;
											}
										}
									}
								}
							}
						}
					}
				}
				
				$arr[] = $arr_filter_form;
			}
			
			if (!$is_source) {
				
				$arr = ['object_filter' => $arr];
			}
			
			return $arr;
		};
		
		$arr_filter_start = $arr_filter_sources[0][0][0];
		$arr_filter = [];
		
		if ($arr_filter_start) {
			$arr_filter = $func_filtering($arr_filter_start, true);
		}
		
		return $arr_filter; 
	}
	
	public function parseFilterObject($arr_filter, $purpose = false, $filter_object_subs = false) {
		
		$arr_sql_filter = [];
	
		$func_sql_sources = function($type, $arr_source, $table_name) {
			
			$arr_sql = [];
			
			if ($arr_source[$type.'_source_ref_type_id']) {
				$arr_sql[] = $table_name.".ref_type_id = ".$arr_source[$type.'_source_ref_type_id'];
			}
			if ($arr_source[$type.'_source_ref_object_id']) {
				$arr_sql[] = $this->format2SQLMatchObjects($arr_source[$type.'_source_ref_type_id'], $arr_source[$type.'_source_ref_object_id'], $table_name.".ref_object_id");
			}
			if ($arr_source[$type.'_source_link']) {
				
				$arr_sql_link = [];
				$arr_source_links = $arr_source[$type.'_source_link'];
				$arr_source_links = (is_array($arr_source_links) && !arrIsAssociative($arr_source_links) ? $arr_source_links : [$arr_source_links]);
				
				foreach ($arr_source_links as $value) {
					$arr_sql_link[] = StoreTypeObjects::formatToSQLValueFilter('text', $value, $table_name.".value");
				}
			
				$arr_sql[] = "(".implode(" OR ", $arr_sql_link).")";
			}
			
			if ($arr_sql) {
				$sql = "(".implode(" AND ", $arr_sql).")";
			}

			return $sql;
		};
		
		$count_filter = 0;
		$has_filter = false;
		$arr_sql_build = [];
		$arr_sql_build_object_subs = [];
		
		foreach ($arr_filter as $arr_object_filter) {
		
			$arr_sql = [];

			$arr_str = [];
			$table_name_to = 'nodegoat_to';
			$table_name_tos_all = 'nodegoat_tos_all';
			
			$sql_operator = ($arr_object_filter['options']['operator'] == 'and' || !$count_filter ? 'AND' : 'OR');
			$sql_operator_not = ($arr_object_filter['options']['exclude'] ? 'NOT' : '');
			
			$filter_pre = ($arr_object_filter['options']['exclude'] == 'hard' ? true : false);
			$arr_object_filter['filter_code'] = ($arr_object_filter['filter_code'] ?: uniqid());
									
			if ($this->isFilteringObject() && $arr_object_filter['source']['filter_code']) { // Path-aware filtering; match the correct Object in the path
				
				if ($arr_object_filter['source']['direction'] == 'in') {
					
					if ($arr_object_filter['source']['object_sub_description_id'] === 'location') {
						
						$arr_object_filter['object_subs'][$arr_object_filter['source']['object_sub_details_id']]['object_sub_locations']['filtering'] = true;
					} else {
						
						if ($arr_object_filter['source']['object_description_id']) {
							$arr_object_filter['object_definitions'][$arr_object_filter['source']['object_description_id']]['filtering'] = true;
						} else if ($arr_object_filter['source']['object_sub_description_id']) {
							$arr_object_filter['object_subs'][$arr_object_filter['source']['object_sub_details_id']]['object_sub_definitions'][$arr_object_filter['source']['object_sub_description_id']]['filtering'] = true;
						}
					}
				} else if ($arr_object_filter['source']['direction'] == 'out') {
					
					if ($arr_object_filter['source']['object_sub_description_id'] === 'location') {
						
						$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true, true);
						
						$arr_sql['filtering'] = "EXISTS (SELECT TRUE
								FROM ".$this->table_name_filtering." ".$arr_object_filter['source']['filter_code']."
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = ".$arr_object_filter['source']['filter_code'].".object_sub_".(int)$arr_object_filter['source']['object_sub_details_id']."_id AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
								".$arr_sql_location['tables']."
							WHERE ".$arr_object_filter['source']['filter_code'].".id_".$arr_object_filter['source']['filter_code']." = 1
								AND ".$arr_sql_location['column_ref_type_id']." = ".(int)$this->type_id."
								AND ".$arr_sql_location['column_object_sub_details_id']." = ".(int)$arr_object_filter['source']['object_sub_details_id']."
								AND ".$arr_sql_location['column_ref_object_id']." = nodegoat_to.id
						)";
					} else {
						
						if ($arr_object_filter['source']['object_description_id']) {
							$column_name_base = "object_definition_".(int)$arr_object_filter['source']['object_description_id'];
						} else if ($arr_object_filter['source']['object_sub_description_id']) {
							$column_name_base = "object_sub_definition_".(int)$arr_object_filter['source']['object_sub_description_id'];
						}
						
						$arr_sql['filtering'] = "EXISTS (SELECT TRUE
								FROM ".$this->table_name_filtering." ".$arr_object_filter['source']['filter_code']."
							WHERE ".$arr_object_filter['source']['filter_code'].".id_".$arr_object_filter['source']['filter_code']." = 1
								AND ".$arr_object_filter['source']['filter_code'].".".$column_name_base."_ref_object_id = nodegoat_to.id
						)";
					}
				}
			}
			
			// Objects
			if ($arr_object_filter['objects']) {
				
				$arr_object_filter['objects'] = array_filter(arrParseRecursive($arr_object_filter['objects'], 'int'));
				
				if ($arr_object_filter['objects']) {
					
					$arr_sql['filter'][] = $table_name_to.".id IN (".implode(',', $arr_object_filter['objects']).")";
				}
			}
			
			// Object name
			if ($arr_object_filter['object_name']) {
				
				$arr_str = [];
					
				foreach ($arr_object_filter['object_name'] as $arr_filter_object_name) {

					$arr_str[] = StoreTypeObjects::formatToSQLValueFilter('', $arr_filter_object_name, $table_name_to.".name");
				}
				
				if ($arr_str) {
					
					$arr_sql['filter'][] = "(".implode(" OR ", $arr_str).")";
				}
			}
			
			// Object source
			if ($arr_object_filter['object_sources']) {
				
				$arr_str = [];
				$table_name = 'nodegoat_to_src_'.count($this->query_object_sources);
				
				foreach ($arr_object_filter['object_sources'] as $arr_filter_object_source) {
					
					$arr_str[] = $func_sql_sources('object', $arr_filter_object_source, $table_name);
				}
				
				if ($arr_str) {
					
					$arr_sql['filter'][] = "(".implode(" OR ", $arr_str).")";
					$this->query_object_sources[] = $table_name;
				}
			}
			
			// Object analysis
			if ($arr_object_filter['object_analyses']) {
				
				$arr_str = [];
				
				$nr_object_analyses_table = count($this->query_object_analyses);
				$nr_object_analyses_table = (!$nr_object_analyses_table || $sql_operator == 'AND' ? $nr_object_analyses_table : $nr_object_analyses_table-1);
				
				$table_name = 'nodegoat_to_an_'.$nr_object_analyses_table;
					
				foreach ($arr_object_filter['object_analyses'] as $arr_filter_object_analysis) {

					$arr_object_analysis_id = explode('_', $arr_filter_object_analysis['object_analysis_id']);
					
					$analysis_id = (int)$arr_object_analysis_id[0];
					$analysis_user_id = (int)$arr_object_analysis_id[1];
					
					if ($analysis_id && !$analysis_user_id) {
						$analysis_user_id = 0;
					} else if ($analysis_user_id) {
						$analysis_user_id = ($this->arr_scope['users'] && in_array($analysis_user_id, $this->arr_scope['users']) ? $analysis_user_id : false);
					} else if (!$analysis_id) {
						$analysis_user_id = ($this->arr_scope['users'] ? current($this->arr_scope['users']) : false);
					}
					
					if (!$analysis_user_id) {
						continue;
					}
					
					$arr_str[] = "(
						".$table_name.".user_id = ".$analysis_user_id."
						AND ".$table_name.".analysis_id = ".$analysis_id."
						".($arr_filter_object_analysis['number']? "AND ".StoreTypeObjects::formatToSQLValueFilter('float', $arr_filter_object_analysis['number'], $table_name.".number") : "")."
						".($arr_filter_object_analysis['number_secondary'] ? "AND ".StoreTypeObjects::formatToSQLValueFilter('float', $arr_filter_object_analysis['number_secondary'], $table_name.".number_secondary") : "")."
					)";
				}
				
				if ($arr_str) {
					
					$sql_filter = "(".implode(" OR ", $arr_str).")";
					
					$arr_sql['filter'][] = $sql_filter;
					$this->query_object_analyses[$table_name][] = ['table_name' => $table_name, 'arr_sql' => ['sql_filter' => $sql_filter]];
				}
			}
			
			// Object definitions
			foreach ((array)$arr_object_filter['object_definitions'] as $object_description_id => $arr_definitions) {
				
				$arr_definitions = ($arr_definitions ? (array)$arr_definitions : []);
				$arr_options = ['object_description_id' => $object_description_id];
				$is_filtering = $this->isFilteringObjectDescription($object_description_id, false, true);
				if ($filter_pre) {
					$table_name = "nodegoat_to_def_".$object_description_id."_".$arr_object_filter['filter_code'];
				} else {
					$nr_object_description_table = count((array)$this->arr_nr_object_tables['object_descriptions'][$object_description_id]);
					$nr_object_description_table = (!$nr_object_description_table || $sql_operator == 'AND' ? $nr_object_description_table : $nr_object_description_table-1); // Use new tables when filtering on a previous filter (AND, taking into account the possibility for multiple values)
					$table_name = "nodegoat_to_def_".$object_description_id."_".$nr_object_description_table."_".($is_filtering ? $arr_object_filter['filter_code'] : 0);
				}
				
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
				if ($arr_definitions['filtering']) {
					
					if ($arr_object_description['object_description_is_dynamic']) {
						$table_name_filtering = $table_name."_objects";
					} else {
						$table_name_filtering = $table_name;
					}
					
					$sql_filter = "EXISTS (SELECT TRUE
							FROM ".$this->table_name_filtering." ".$arr_object_filter['source']['filter_code']."
						WHERE ".$arr_object_filter['source']['filter_code'].".id_".$arr_object_filter['source']['filter_code']." = 1
							AND ".$arr_object_filter['source']['filter_code'].".id = ".$table_name_filtering.".ref_object_id
					)";
					
					$this->query_object_descriptions['object_descriptions'][$object_description_id][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter]];
					$this->arr_nr_object_tables['object_descriptions'][$object_description_id][$table_name] = true;
					$arr_sql['filtering'] = $sql_filter;
					
					unset($arr_definitions['filtering']);
				}
				
				if ($arr_definitions) {
					
					if ($arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic']) {
						
						$arr_sql_format = $this->format2SQLReferencingObjects('object_definition', $arr_definitions, $table_name, $arr_options);
					} else {

						$arr_sql_format = $this->format2SQLValueType($arr_object_description['object_description_value_type'], $arr_definitions, $table_name, $arr_options);
					}
					
					if ($arr_sql_format['filter']) {
						
						$sql_filter = $arr_sql_format['filter'];
						if ($arr_sql_format['objects'] || $arr_sql_format['value']) {
							$sql_filter_object_description = ['objects' => $arr_sql_format['objects'], 'value' => $arr_sql_format['value']];
						} else {
							$sql_filter_object_description = ($arr_sql_format['filter_pre'] && $sql_operator_not ? "TRUE" : $arr_sql_format['filter_pre']);
						}
												
						if ($filter_pre || !$sql_filter_object_description) {
							
							$version_select = $this->generateVersion('record', $table_name, $arr_object_description['object_description_value_type']);

							$sql_filter = "EXISTS (SELECT TRUE
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2
									LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'], 'search')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to2.id AND ".$table_name.".object_description_id = ".$object_description_id." AND ".$version_select.")
								WHERE nodegoat_to2.id = nodegoat_to.id
									AND ".$sql_filter."
									
							)";
						}
						
						$arr_sql['filter'][] = $sql_filter;
						
						if (!($filter_pre || !$sql_filter_object_description)) {

							$this->query_object_descriptions['object_descriptions'][$object_description_id][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter_object_description, 'sql_operator' => $sql_operator, 'sql_operator_not' => $sql_operator_not]];
							$this->arr_nr_object_tables['object_descriptions'][$object_description_id][$table_name] = true;
						}
					}
				}
			}
			
			// Object definitions sources
			foreach ((array)$arr_object_filter['object_definitions_sources'] as $object_description_id => $arr_definition_sources) {
				
				$arr_str = [];
				$table_name = "nodegoat_to_def_src_".count((array)$this->arr_nr_object_tables['object_descriptions_sources'][$object_description_id]);
				
				foreach ((array)$arr_definition_sources as $value) {
					$arr_str[] = $func_sql_sources('object_definition', $value, $table_name);
				}
				
				if ($arr_str) {
					
					$arr_sql['filter'][] = "(".implode(" OR ", $arr_str).")";
					$this->query_object_descriptions['object_descriptions_sources'][$object_description_id][] = ['table_name' => $table_name];
					$this->arr_nr_object_tables['object_descriptions_sources'][$object_description_id][$table_name] = true;
				}
			}
			
			// Sub-objects
			$arr_sql_subs = [];
			$arr_sql_general = [];
			$arr_object_sub_general = $arr_object_filter['object_subs'][0];
			
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
				$arr_object_sub = $arr_object_filter['object_subs'][$object_sub_details_id];
				$is_filtering = $this->isFilteringObjectSubDetails($object_sub_details_id, false, true);
				$table_name_tos = 'nodegoat_tos_'.$object_sub_details_id;
				$table_name_query_object_sub_self = $table_name_tos.'_self';
				$nr_object_sub_details_table = count((array)$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id]);
				$nr_object_sub_details_table = (!$nr_object_sub_details_table || $sql_operator == 'AND' ? $nr_object_sub_details_table : $nr_object_sub_details_table-1); // Use new tables when filtering on a previous filter (AND, taking into account the possibility for multiple values)
				$table_name_query_object_sub = $table_name_tos."_".$nr_object_sub_details_table."_".($is_filtering ? $arr_object_filter['filter_code'] : 0);

				// Sub-object definitions
				foreach ((array)$arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_sub_definitions) {
					
					$arr_sub_definitions = ($arr_sub_definitions ? (array)$arr_sub_definitions : []);
					$arr_options = ['object_sub_details_id' => $object_sub_details_id, 'object_sub_description_id' => $object_sub_description_id];
					$is_filtering = $this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id, false, true);
					if ($filter_pre) {
						$table_name = "nodegoat_tos_def_".$object_sub_description_id."_".$arr_object_filter['filter_code'];
					} else {
						$table_name = "nodegoat_tos_def_".$object_sub_description_id."_".$nr_object_sub_details_table."_".($is_filtering ? $arr_object_filter['filter_code'] : 0);
					}
					
					if ($arr_sub_definitions['filtering']) {
						
						$sql_filter = "EXISTS (SELECT TRUE
								FROM ".$this->table_name_filtering." ".$arr_object_filter['source']['filter_code']."
							WHERE ".$arr_object_filter['source']['filter_code'].".id_".$arr_object_filter['source']['filter_code']." = 1
								AND ".$arr_object_filter['source']['filter_code'].".id = ".$table_name.".ref_object_id
						)";
						
						$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub]['object_sub_descriptions'][$object_sub_description_id][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter], 'filter_object_subs' => $filter_object_subs];
						$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub] = true;
						$arr_sql['filtering'] = $sql_filter;
						
						unset($arr_sub_definitions['filtering']);
					} 
					
					if ($arr_sub_definitions) {
						
						$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
												
						if ($arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic']) {
							
							$arr_sql_format = $this->format2SQLReferencingObjects('object_sub_definition', $arr_sub_definitions, $table_name, $arr_options);
						} else {
							
							$arr_sql_format = $this->format2SQLValueType($arr_object_sub_description['object_sub_description_value_type'], $arr_sub_definitions, $table_name, $arr_options);
						}

						if ($arr_sql_format['filter']) {
							
							$sql_filter = $arr_sql_format['filter'];
							$sql_filter_object_sub = ($arr_sql_format['filter_pre'] && $sql_operator_not ? "TRUE" : $arr_sql_format['filter_pre']);
														
							if ($filter_pre) {
								
								$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');
								$version_select = $this->generateVersion('record', $table_name, $arr_object_sub_description['object_sub_description_value_type']);
								
								if ($object_sub_description_use_object_description_id) {
									
									$sql_filter = "EXISTS (SELECT TRUE
											FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2
											LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'], 'search')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to2.id AND ".$table_name.".object_description_id = ".$object_sub_description_use_object_description_id." AND ".$version_select.")
										WHERE nodegoat_to2.id = nodegoat_to.id
											AND ".$sql_filter."
									)";
								} else {
									
									$sql_filter = "EXISTS (SELECT TRUE
											FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
											LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'], 'search')." ".$table_name." ON (".$table_name.".object_sub_id = nodegoat_tos.id AND ".$table_name.".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select.")
										WHERE nodegoat_tos.object_id = nodegoat_to.id
											AND nodegoat_tos.object_sub_details_id = ".$object_sub_details_id."
											AND ".$version_select_tos."
											AND ".$sql_filter."
									)";
								}
							}
							
							$arr_sql['filter'][] = $sql_filter;
							
							if (!$filter_pre) {
								
								$arr_sql_subs[$object_sub_details_id]['filter'][] = $sql_filter;
								$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub]['object_sub_descriptions'][$object_sub_description_id][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter_object_sub, 'sql_operator' => $sql_operator, 'sql_operator_not' => $sql_operator_not], 'filter_object_subs' => $filter_object_subs];
								$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub] = true;
							}
						}
					}
				}
				
				// Sub-object definitions sources
				foreach ((array)$arr_object_sub['object_sub_definitions_sources'] as $object_sub_description_id => $arr_sub_definition_sources) {
					
					$arr_str = [];
					$table_name = "nodegoat_tos_def_src_".$object_sub_description_id."_".$nr_object_sub_details_table;
					
					foreach ((array)$arr_sub_definition_sources as $value) {
						$arr_str[] = $func_sql_sources('object_sub_definition', $value, $table_name);
					}
					
					if ($arr_str) {
						
						$arr_sql['filter'][] = "(".implode(" OR ", $arr_str).")";
						$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub]['object_sub_descriptions_sources'][$object_sub_description_id][$table_name] = ['table_name' => $table_name, 'filter_object_subs' => $filter_object_subs];
						$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub] = true;
					}
				}
				
				// Sub-object date (*ymmdd OR *y-mm-dd)
				$arr_object_sub_dates = $arr_object_sub['object_sub_dates'];
				
				$is_filter_general = false;
				
				if (!$arr_object_sub_dates && $arr_object_sub_general['object_sub_dates']) {
					if ($object_sub_details_id == 'all' || (!$this->arr_type_set['object_sub_details']['all'] && !$this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])) {
						
						$arr_object_sub_dates = $arr_object_sub_general['object_sub_dates'];
						$is_filter_general = true;
					}
				}
					
				if ($arr_object_sub_dates) {

					$arr_str = [];
					$table_name = $table_name_query_object_sub_self;
					
					$table_name_date = 'nodegoat_tos';
					$arr_sql_date = $this->generateTablesColumnsObjectSubDate($table_name_date, true);
					
					$column_start = $arr_sql_date['column_start'];
					$column_end = $arr_sql_date['column_end'];
					
					foreach ((array)$arr_object_sub_dates as $arr_object_sub_date) {
						
						$arr_sql_date_filter = [];
						
						if ($arr_object_sub_date['object_sub_date_from'] || $arr_object_sub_date['object_sub_date_to']) {
							
							$date_start = StoreTypeObjects::date2Int($arr_object_sub_date['object_sub_date_from'], '>');
							$date_end = StoreTypeObjects::date2Int($arr_object_sub_date['object_sub_date_to'], '<');
							
							$arr_sql_date_filter[] = self::format2SQLDateIntMatch($date_start, $date_end, $column_start, $column_end);
						} else {
							
							$arr_date_start = $arr_object_sub_date['object_sub_date_start'];
							$arr_date_end = $arr_object_sub_date['object_sub_date_end'];
							
							$sql_date = '';
							
							if ($arr_date_start) {
								$sql_date = StoreTypeObjects::formatToSQLValueFilter('date', $arr_date_start, $column_start);
							}
							if ($arr_date_end) {
								$sql_date_end = StoreTypeObjects::formatToSQLValueFilter('date', $arr_date_end, $column_end);
								if ($sql_date) {
									$sql_date = "(".$sql_date." AND ".$sql_date_end.")";
								} else {
									$sql_date = $sql_date_end;
								}
							}
							
							if ($sql_date) {
								
								$arr_sql_date_filter[] = $sql_date;
							}
							
							if ($arr_object_sub_date['object_sub_date_value']['transcension']) {
						
								$arr_sql_date_filter[] = StoreTypeObjects::formatToSQLTranscension('null', $arr_object_sub_date['object_sub_date_value']['transcension'], [$column_start, $column_end]);
							}
						}

						/*
						
						$version_select_to = $this->generateVersion('object', 'nodegoat_to');
						$version_select_tos = $this->generateVersion('object_sub', $table_name_date);
						
						$column_select = ($filter_pre ? $table_name_date.".object_id AS id" : $table_name_date.".id");
						$column_target = ($filter_pre ? "nodegoat_to.id" : $table_name.".id");

						$sql_query = "SELECT DISTINCT ".$column_select."
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_date."
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = ".$table_name_date.".object_id AND ".$version_select_to.")
							".$arr_sql_date['tables']."
							WHERE ".$table_name_date.".object_sub_details_id ".($object_sub_details_id == 'all' ? "IN (".implode(',', $this->arr_type_set_object_sub_details_ids).")" : "= ".$object_sub_details_id)." AND ".$version_select_tos."
								AND ".implode(' AND ', $arr_sql_date_filter)."
						";
						
						$temp_table_name_date_match = $this->generateTemporaryTableName('temp_date_match', $sql_query);
						
						$this->addPre($temp_table_name_date_match, array(
							'table_name' => $temp_table_name_date_match,
							'settings' => "
								id INT,
								PRIMARY KEY (id)",
							'query' => $sql_query)
						);
						
						$arr_str[] = "EXISTS (SELECT TRUE FROM ".DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$temp_table_name_date_match)." WHERE ".$temp_table_name_date_match.".id = ".$column_target.")";
						*/
						
						$version_select_tos = $this->generateVersion('object_sub', $table_name_date);
						
						$column_select = ($filter_pre ? $table_name_date.".object_id" : $table_name_date.".id");
						$column_target = ($filter_pre ? "nodegoat_to.id" : $table_name.".id");

						$arr_str[] = "EXISTS (SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_date."
								".$arr_sql_date['tables']."
							WHERE ".$column_select." = ".$column_target."
								AND ".$table_name_date.".object_sub_details_id ".($object_sub_details_id == 'all' ? "IN (".implode(',', $this->arr_type_set_object_sub_details_ids).")" : "= ".$object_sub_details_id)."
								AND ".$version_select_tos."
								AND ".implode(' AND ', $arr_sql_date_filter)."
						)";
					}
					
					if ($arr_str) {
						
						$sql_filter = "(".implode(" OR ", $arr_str).")";

						$sql_filter_object_sub = ($sql_operator_not ? "TRUE" : $sql_filter);
						
						if ($object_sub_details_id != 'all') { // In case of 'all', object-level filtering is already handled
							if ($is_filter_general) {
								$arr_sql_general['filter']['date'][] = $sql_filter;
							} else {
								$arr_sql['filter'][] = $sql_filter;
							}
						}
						
						if (!$filter_pre) {
							
							$arr_sql_subs[$object_sub_details_id]['filter'][] = $sql_filter;
							$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub_self]['date'][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter_object_sub, 'sql_operator' => $sql_operator, 'sql_operator_not' => $sql_operator_not], 'filter_object_subs' => $filter_object_subs];
							$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub_self] = true;
						}
					}
				}
				
				// Sub-object location
				$arr_object_sub_locations = $arr_object_sub['object_sub_locations'];
				
				$is_filter_general = false;
				
				if (!$arr_object_sub_locations && $arr_object_sub_general['object_sub_locations']) {
					if ($object_sub_details_id == 'all' || (!$this->arr_type_set['object_sub_details']['all'] && !$this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])) {
						
						$arr_object_sub_locations = $arr_object_sub_general['object_sub_locations'];
						$is_filter_general = true;
					}
				}
					
				if ($arr_object_sub_locations) {

					$arr_str = [];
					$table_name = $table_name_query_object_sub_self;
					
					$table_name_location = 'nodegoat_tos';
					$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference($table_name_location, true);
					
					$column_geometry = $arr_sql_location['column_geometry'];
					$column_object_sub_id = $arr_sql_location['column_geometry_object_sub_id'];
					
					if ($arr_object_sub_locations['filtering']) {
						
						$sql_filter = "EXISTS (SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
								".$arr_sql_location['tables']."
								JOIN ".$this->table_name_filtering." ".$arr_object_filter['source']['filter_code']." ON (".$arr_object_filter['source']['filter_code'].".id_".$arr_object_filter['source']['filter_code']." = 1)
							WHERE nodegoat_tos.id = ".$table_name.".id AND ".$this->generateVersion('object_sub', 'nodegoat_tos')."
								AND ".$arr_sql_location['column_ref_type_id']." = ".(int)$arr_object_filter['source']['type_id']."
								AND ".$arr_sql_location['column_object_sub_details_id']." = ".(int)$object_sub_details_id."							
								AND ".$arr_sql_location['column_ref_object_id']." = ".$arr_object_filter['source']['filter_code'].".id
						)";
						
						$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub_self]['location'][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter], 'filter_object_subs' => $filter_object_subs];
						$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub_self] = true;
						$arr_sql['filtering'] = $sql_filter;
						
						unset($arr_object_sub_locations['filtering']);
					} 
					
					foreach ((array)$arr_object_sub_locations as $arr_object_sub_location) {

						$arr_sql_location_filter = [];
							
						if ($arr_object_sub_location['object_sub_location_reference']) {
							
							unset($arr_object_sub_location['object_sub_location_reference']['beacon']);
							
							$mode = $arr_object_sub_location['object_sub_location_reference']['mode']; // 'self' or 'default' (resolved) reference
							unset($arr_object_sub_location['object_sub_location_reference']['mode']);
														
							$arr_sql_filter_collect = [];
							$arr_sql_objects = [];
		
							foreach ($arr_object_sub_location['object_sub_location_reference'] as $value) {
								
								if (is_array($value)) { // Filter
									$arr_sql_filter_collect = ($arr_sql_filter_collect ? array_merge_recursive($arr_sql_filter_collect, $value) : $value);
								} else { // Object id
									$arr_sql_objects[] = $value;
								}
							}
							if ($arr_sql_objects) {
								
								$arr_sql_filter_collect['object_filter'][]['objects'] = $arr_sql_objects;
							}

							if ($mode == 'self') {
								
								$arr_options = ['object_sub_details_id' => $object_sub_details_id, 'ref_type_id' => $arr_object_sub_location['object_sub_location_ref_type_id']];
								$arr_sql_filter_collect = ($arr_sql_filter_collect ? [$arr_sql_filter_collect] : []);
								
								$arr_sql_format = $this->format2SQLReferencingObjects('object_sub_location', [$arr_sql_filter_collect], $arr_sql_location['table_name_location'], $arr_options);
								$sql_filter = "(".$arr_sql_format['filter']."
									".($arr_object_sub_location['object_sub_location_ref_object_sub_details_id'] ? " AND ".$arr_sql_location['column_ref_object_sub_details_id']." = ".(int)$arr_object_sub_location['object_sub_location_ref_object_sub_details_id'] : "")."
								)";
								
								$arr_sql_location_filter[] = $sql_filter;
							} else if ($arr_sql_filter_collect) {
								
								$radius = (int)$arr_object_sub_location['object_sub_location_reference']['radius'];
								unset($arr_object_sub_location['object_sub_location_reference']['radius']);
								
								// Select the resolved sub-object IDs and the object's own sub-object IDs that do not resolve (no location), but can be resolved to by other sub-objects
								
								$table_name_location_against = 'nodegoat_tos_against';
								$arr_sql_location_against = $this->generateTablesColumnsObjectSubLocationReference($table_name_location_against, true);
								
								$column_geometry_against = $arr_sql_location_against['column_geometry'];
								$column_object_sub_id_against = $arr_sql_location_against['column_geometry_object_sub_id'];
								
								$filter = new FilterTypeObjects($arr_object_sub_location['object_sub_location_ref_type_id'], 'id');
								$filter->setScope($this->arr_scope);
								$filter->setDepth($this->getDepth());
								$filter->setDifferentiationId($this->getDifferentiationId());
								$filter->setFilter($arr_sql_filter_collect);
								
								$sql_select = "CASE WHEN ".$column_object_sub_id_against." IS NOT NULL THEN ".$column_object_sub_id_against." ELSE ".$table_name_location_against.".id END";
								
								$arr_query_settings = [
									'columns' => [$sql_select." AS object_sub_id"],
									'group' => [$sql_select],
									'tables' => [
										"JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS ".$table_name_location_against." ON (".$table_name_location_against.".object_id = nodegoat_to.id AND ".$table_name_location_against.".object_sub_details_id = ".(int)$arr_object_sub_location['object_sub_location_ref_object_sub_details_id']." AND ".$table_name_location_against.".active = TRUE)",
										$arr_sql_location_against['tables']
									]
								];
								
								$sql_filter = $filter->sqlQuery($arr_query_settings);
								
								$arr_sql_pre_settings = $filter->getPre();
								$this->arr_sql_pre_settings += $arr_sql_pre_settings;
								
								$table_name_location_against = $this->generateTemporaryTableName('temp_'.$arr_object_sub_location['object_sub_location_ref_type_id'], $sql_filter);
									
								$this->addPre($table_name_location_against,
									[
										'table_name' => $table_name_location_against,
										'settings' => "
											object_sub_id INT,
											PRIMARY KEY (object_sub_id)",
										'query' => $sql_filter
									]
								);
								
								$arr_sql_location_filter[] = "(EXISTS (SELECT TRUE
										FROM ".DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name_location_against)."
									WHERE ".$column_object_sub_id." = ".$table_name_location_against.".object_sub_id
								) OR EXISTS (SELECT TRUE
										FROM ".DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name_location_against)."
									WHERE ".$table_name_location.".id = ".$table_name_location_against.".object_sub_id
								))";
							}
						} else {

							if ($arr_object_sub_location['object_sub_location_value']['transcension']) {
						
								$arr_sql_location_filter[] = StoreTypeObjects::formatToSQLTranscension('null', $arr_object_sub_location['object_sub_location_value']['transcension'], [$column_geometry]);
							}
							
							if ($arr_object_sub_location['object_sub_location_geometry']) {
							
								$sql_object_sub_location_geometry = "ST_GeomFromGeoJSON('".DBFunctions::strEscape($arr_object_sub_location['object_sub_location_geometry'])."', 1, 0)";

								$sql_location = "ST_Intersects(".$sql_object_sub_location_geometry.", ".$arr_sql_location['column_geometry'].")";
								
								$arr_sql_location_filter[] = $sql_location;
							} else if ($arr_object_sub_location['object_sub_location_latitude'] || $arr_object_sub_location['object_sub_location_longitude']) {

								$radius = (int)$arr_object_sub_location['object_sub_location_value']['radius'];

								if ($radius) {
									
									$sql_object_sub_location_geometry = "ST_MakeEnvelope(
										ST_GeomFromText(CONCAT(
											'POINT(',
												(".(float)$arr_object_sub_location['object_sub_location_longitude']." + (".$radius." / ABS(COS(RADIANS(".(float)$arr_object_sub_location['object_sub_location_latitude'].")) * 111))),
												' ',
												(".(float)$arr_object_sub_location['object_sub_location_latitude']." + (".$radius." / 111)),
											')'
										)),
										ST_GeomFromText(CONCAT(
											'POINT(',
												(".(float)$arr_object_sub_location['object_sub_location_longitude']." - (".$radius." / ABS(COS(RADIANS(".(float)$arr_object_sub_location['object_sub_location_latitude'].")) * 111))),
												' ',
												(".(float)$arr_object_sub_location['object_sub_location_latitude']." - (".$radius." / 111)),
											')'
										))
									)";
									
									/*	$sql_location = "ST_Intersects(".$sql_object_sub_location_geometry.", ".$column_geometry.")
										AND CASE
											WHEN 
											ST_Distance_Sphere(".$sql_object_sub_location_geometry.", ".$column_geometry.") <= ".($radius * 1000)
									;*/
									
									$sql_location = "ST_Intersects(".$sql_object_sub_location_geometry.", ".$column_geometry.")";
								} else {
									
									$sql_object_sub_location_geometry = "ST_GeomFromText('POINT(".(float)$arr_object_sub_location['object_sub_location_longitude']." ".(float)$arr_object_sub_location['object_sub_location_latitude'].")')";
									
									$sql_location = "ST_Intersects(".$sql_object_sub_location_geometry.", ".$column_geometry.")";
								}
								
								$arr_sql_location_filter[] = $sql_location;
							}
						}

						/*
						
						$version_select_to = $this->generateVersion('object', 'nodegoat_to');
						$version_select_tos = $this->generateVersion('object_sub', $table_name_location);
						
						$column_select = ($filter_pre ? $table_name_location.".object_id AS id" : $table_name_location.".id");
						$column_target = ($filter_pre ? "nodegoat_to.id" : $table_name.".id");

						$sql_query = "SELECT DISTINCT ".$column_select."
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_location."
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = ".$table_name_location.".object_id AND ".$version_select_to.")
							".$arr_sql_location['tables']."
							WHERE ".$table_name_location.".object_sub_details_id ".($object_sub_details_id == 'all' ? "IN (".implode(',', $this->arr_type_set_object_sub_details_ids).")" : "= ".$object_sub_details_id)." AND ".$version_select_tos."
								AND ".implode(' AND ', $arr_sql_location_filter)."
						";
						
						$temp_table_name_location_match = $this->generateTemporaryTableName('temp_location_'.$arr_object_sub_location['object_sub_location_ref_type_id'].'_match', $sql_query);
						
						$this->addPre($temp_table_name_location_match, array(
							'table_name' => $temp_table_name_location_match,
							'settings' => "
								id INT,
								PRIMARY KEY (id)",
							'query' => $sql_query)
						);
						
						$arr_str[] = "EXISTS (SELECT TRUE FROM ".DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$temp_table_name_location_match)." WHERE ".$temp_table_name_location_match.".id = ".$column_target.")";
						*/
						
						if ($arr_sql_location_filter) {
								
							$version_select_tos = $this->generateVersion('object_sub', $table_name_location);
							
							$column_select = ($filter_pre ? $table_name_location.".object_id" : $table_name_location.".id");
							$column_target = ($filter_pre ? "nodegoat_to.id" : $table_name.".id");

							$arr_str[] = "EXISTS (SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_location."
									".$arr_sql_location['tables']."
								WHERE ".$column_select." = ".$column_target."
									AND ".$table_name_location.".object_sub_details_id ".($object_sub_details_id == 'all' ? "IN (".implode(',', $this->arr_type_set_object_sub_details_ids).")" : "= ".$object_sub_details_id)."
									AND ".$version_select_tos."
									AND ".implode(' AND ', $arr_sql_location_filter)."
							)";
						}
					}
					
					if ($arr_str) {
						
						$sql_filter = "(".implode(" OR ", $arr_str).")";
						
						$sql_filter_object_sub = ($sql_operator_not ? "TRUE" : $sql_filter);
						
						if ($object_sub_details_id != 'all') { // In case of 'all', object-level filtering is already handled
							if ($is_filter_general) {
								$arr_sql_general['filter']['location'][] = $sql_filter;
							} else {
								$arr_sql['filter'][] = $sql_filter;
							}
						}
						
						if (!$filter_pre) {
							
							$arr_sql_subs[$object_sub_details_id]['filter'][] = $sql_filter;
							$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub_self]['location'][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter_object_sub, 'sql_operator' => $sql_operator,  'sql_operator_not' => $sql_operator_not], 'filter_object_subs' => $filter_object_subs];
							$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub_self] = true;
						}
					}
				}

				// Sub-object sources
				if ($arr_object_sub['object_sub_sources']) {
					
					$arr_str = [];
					$table_name = "nodegoat_tos_src_".$object_sub_details_id."_".$nr_object_sub_details_table;
					
					foreach ((array)$arr_object_sub['object_sub_sources'] as $value) {
						$arr_str[] = $func_sql_sources('object_sub', $value, $table_name);
					}
					
					if ($arr_str) {
						
						$arr_sql['filter'][] = "(".implode(" OR ", $arr_str).")";
						$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub]['object_sub_sources'][$table_name] = ['table_name' => $table_name, 'filter_object_subs' => $filter_object_subs];
						$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub] = true;
					}
				}
				
				// Sub-object referenced
				if ($arr_object_sub['object_sub_referenced']) {
					
					// Object-level filtering is handled through the intended cross-referenced functions

					$arr_options = ['object_sub_details_id' => $object_sub_details_id];
					$table_name = $table_name_query_object_sub_self;
										
					$arr_sql_format = $this->format2SQLReferencingObjects('object_sub_referenced', $arr_object_sub['object_sub_referenced'], $table_name, $arr_options);
					
					$sql_filter = $arr_sql_format['filter'];
					
					$arr_sql_subs[$object_sub_details_id]['filter'][] = $sql_filter;
					$this->query_object_subs[$object_sub_details_id][$table_name_query_object_sub_self]['referenced'][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter, 'sql_operator' => $sql_operator,  'sql_operator_not' => $sql_operator_not], 'filter_object_subs' => $filter_object_subs];
					$this->arr_nr_object_tables['object_sub_details'][$object_sub_details_id][$table_name_query_object_sub_self] = true;
					
					if ($this->arr_type_set['object_sub_details']['all']) {

						$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id'];
															
						$sql_filter_object_sub = $table_name_tos_all.".object_sub_details_id = ".$use_object_sub_details_id.($arr_sql_format['filter_pre'] ? " AND ".$arr_sql_format['filter_pre'] : '');
						
						$sql_filter = "CASE WHEN ".$table_name_tos_all.".object_sub_details_id = ".$use_object_sub_details_id." THEN ".$arr_sql_format['filter']." ELSE TRUE END";
						
						$arr_sql_subs['all']['filter'][] = $sql_filter;
						$this->query_object_subs['all']['nodegoat_tos_all_self']['referenced'][$arr_object_filter['filter_code']] = ['table_name' => $table_name, 'filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_filter_object_sub, 'sql_operator' => $sql_operator, 'sql_operator_not' => $sql_operator_not], 'filter_object_subs' => $filter_object_subs];
						$this->arr_nr_object_tables['object_sub_details']['all']['nodegoat_tos_all_self'] = true;
					}
				}

				// Relationality
				if (is_numeric($arr_object_sub['object_sub']['relationality']['value'])) {
					$arr_sql['filter'][] = $this->format2SQLHasSubObjects($arr_object_sub['object_sub']['relationality'], 'nodegoat_to', $object_sub_details_id);
				}
			}
			
			// Relationality
			if (is_numeric($arr_object_sub_general['object_sub']['relationality']['value'])) {
				$arr_sql['filter'][] = $this->format2SQLHasSubObjects($arr_object_sub_general['object_sub']['relationality'], 'nodegoat_to', false);
			}

			// Referenced
			if ($arr_object_filter['referenced_any']) {
				
				$arr_referenced_from = [];
				
				if ($arr_object_filter['referenced_any']['from']['object_definition'] || $arr_object_filter['referenced_any']['from']['any']) {
					
					$arr_referenced_from[] = 'object_definition';
				}
				
				if ($arr_object_filter['referenced_any']['from']['object_sub_definition'] || $arr_object_filter['referenced_any']['from']['any']) {
											
					$arr_referenced_from[] = 'object_sub_definition';
				}
				
				if ($arr_object_filter['referenced_any']['from']['object_sub_location_reference'] || $arr_object_filter['referenced_any']['from']['any']) {
					
					//$arr_referenced_from[] = 'object_sub_location_reference_cache';
					
					$arr_referenced_from[] = 'object_sub_location_reference_self';
					
					// Sub-object location reference is already covered by above
					/*
					$arr_referenced_from[] = 'object_sub_location_reference_use_object_sub_location_reference';
					*/
					
					if (!$arr_object_filter['referenced_any']['from']['object_sub_definition']) { // Sub-object definition references are already covered
						
						$arr_referenced_from[] = 'object_sub_location_reference_use_object_sub_definition';
					}
					
					if (!$arr_object_filter['referenced_any']['from']['object_definition']) { // Object definition references are already covered
						
						$arr_referenced_from[] = 'object_sub_location_reference_use_object_definition';
					}
				}
				
				$arr_relationality = ($arr_object_filter['referenced_any']['relationality'] ?: ['equality' => '≥', 'value' => 1]);
				
				$arr_sql['filter'][] = $this->format2SQLReferencedObjects($arr_referenced_from, ['filter' => ['objects' => ['relationality' => $arr_relationality]]]);
			}
			
			// Type referenced				
			foreach ((array)$arr_object_filter['referenced_types'] as $ref_type_id => $arr_reference_object_filter) {
				
				$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);
				
				if ($arr_reference_object_filter['any']) {
					
					$arr_from = ($arr_reference_object_filter['any']['from'] ?: ['any' => 'any']);
					unset($arr_reference_object_filter['any']['from']);
					
					$arr_referenced_from = [];
					
					if ($arr_from['object_definition'] || $arr_from['any']) {
						
						$arr_referenced_from[] = 'object_definition';
					}
					
					if ($arr_from['object_sub_definition'] || $arr_from['any']) {
												
						$arr_referenced_from[] = 'object_sub_definition';
					}
					
					if ($arr_from['object_sub_location_reference'] || $arr_from['any']) {
						
						//$arr_referenced_from[] = 'object_sub_location_reference_cache';
																		
						$arr_referenced_from[] = 'object_sub_location_reference_self';

						if (!$arr_from['object_sub_definition']) { // Subobject definition references are already covered
							
							$arr_referenced_from[] = 'object_sub_location_reference_use_object_sub_definition';
						}
						
						if (!$arr_from['object_definition']) { // Object definition references are already covered
							
							$arr_referenced_from[] = 'object_sub_location_reference_use_object_definition';
						}
					}
					
					$arr_sql['filter'][] = $this->format2SQLReferencedObjects($arr_referenced_from, ['ref_type_id' => $ref_type_id, 'filter' => $arr_reference_object_filter['any']]);
				}
				
				// Sub-objects
				foreach ((array)$arr_reference_object_filter['object_subs'] as $object_sub_details_id => $arr_object_sub) {
					
					if ($arr_object_sub['object_sub_location_reference']) {
						
						$arr_referenced_from = ['object_sub_location_reference_self', 'object_sub_location_reference_use_object_sub_definition', 'object_sub_location_reference_use_object_definition'];
						
						$arr_sql['filter'][] = $this->format2SQLReferencedObjects($arr_referenced_from, ['ref_type_id' => $ref_type_id, 'object_sub_details_id' => $object_sub_details_id, 'filter' => $arr_object_sub['object_sub_location_reference']]);
					}
									
					// Sub-object definitions
					foreach ((array)$arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_sub_definitions) {
						
						$arr_sub_definitions = ($arr_sub_definitions ? (array)$arr_sub_definitions : []);
						
						if (!$arr_sub_definitions) {
							continue;
						}
						
						$arr_object_sub_description = $arr_ref_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];

						if ($arr_sub_definitions) {				
												
							if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
								
								$s_arr = &$arr_reference_object_filter['object_definitions'][$arr_object_sub_description['object_sub_description_use_object_description_id']];
								$s_arr = ($s_arr ? array_merge($s_arr, $arr_sub_definitions) : $arr_sub_definitions);
							} else {
								
								$sql_add = $this->format2SQLReferencedObjects('object_sub_definition', ['ref_type_id' => $ref_type_id, 'object_sub_details_id' => $object_sub_details_id, 'object_sub_description_id' => $object_sub_description_id, 'filter' => $arr_sub_definitions]);
								
								if ($sql_add) {
									$arr_sql['filter'][] = $sql_add;
								}
							}
						}
					}
				}
				
				// Object definitions
				foreach ((array)$arr_reference_object_filter['object_definitions'] as $object_description_id => $arr_definitions) {
					
					$arr_definitions = ($arr_definitions ? (array)$arr_definitions : []);
					
					if (!$arr_definitions) {
						continue;
					}
	
					if ($arr_definitions) {
						
						$sql_add = $this->format2SQLReferencedObjects('object_definition', ['ref_type_id' => $ref_type_id, 'object_description_id' => $object_description_id, 'filter' => $arr_definitions]);
						
						if ($sql_add) {
							$arr_sql['filter'][] = $sql_add;
						}
					}
				}
			}
			
			if (!$arr_sql && $arr_sql_subs) { // Return positive when only sub-objects are being filtered
				$arr_sql['filter'][] = "TRUE";
			}
			
			// Collect object filter
			// Specific object and sub object queries, matching EVERYTHING in definitions and sub-objects => AND
			$arr_sql_collect = [];
			
			if ($arr_sql['filter']) {
				$arr_sql_collect['filter'][] = "(".implode(" AND ", $arr_sql['filter']).")";
			}
			
			// Collect object sub filter
			// Specific sub object queries, matching EVERYTHING in sub-objects => AND
			$arr_sql_collect_subs = [];
			
			foreach ($arr_sql_subs as $object_sub_details_id => $value) {

				if (!$value['filter']) {
					continue;
				}
				
				$sql_value = "(".implode(" AND ", $value['filter']).")";
				
				$arr_sql_collect_subs[$object_sub_details_id]['filter'][] = $sql_value;
				$this->arr_sql_filter_subs_purpose[$purpose][$object_sub_details_id][$arr_object_filter['filter_code']] = $sql_value;
			}
			
			// General sub object query, matching EVERYTHING in each sub object => AND and matching ANY between all sub-objects => OR
			if ($arr_sql_general['filter']) {
				
				$arr_sql_value = [];
				
				foreach ($arr_sql_general['filter'] as $value) {
					$arr_sql_value[] = "(".implode(" OR ", $value).")";
				}
				
				$sql_value = "(".implode(" AND ", $arr_sql_value).")";
				
				$this->arr_sql_filter_general_purpose[$purpose][$arr_object_filter['filter_code']] = $sql_value;
				$arr_sql_collect['filter'][] = $sql_value;
			}
	
			// Store object filter, either including or excluding, matching EVERYTHING in filter => AND (NOT)
			$arr_sql_build[$count_filter] = ['filter_code' => $arr_object_filter['filter_code'], 'sql_operator' => $sql_operator, 'sql_filter' => ''];
			
			if ($arr_sql_collect['filter']) {
			
				$has_filter = true;
				$sql_add = "(".implode(" AND ", $arr_sql_collect['filter']).")";
				$this->query_object[$arr_object_filter['filter_code']] = ['filter_code' => $arr_object_filter['filter_code'], 'arr_source' => $arr_object_filter['source'], 'purpose' => $purpose, 'arr_sql' => ['sql_filter' => $sql_add, 'sql_filtering' => $arr_sql['filtering'], 'sql_operator' => $sql_operator, 'sql_operator_not' => $sql_operator_not]];
				$arr_sql_build[$count_filter]['sql_filter'] = ($arr_sql['filtering'] ? $arr_sql['filtering']." AND" : "")." ".$sql_operator_not." ".$sql_add;
			}
			
			foreach ($arr_sql_collect_subs as $object_sub_details_id => $arr_value) {
				
				if ($arr_value['filter']) {
					
					$has_filter = true;
					$sql_add = "(".implode(" AND ", $arr_value['filter']).")";
					$sql_add = $sql_operator_not." ".$sql_add;
					$arr_sql_build_object_subs[$object_sub_details_id][$count_filter] = $sql_add;
				}
			}
			
			$count_filter++;
		}
		
		if ($has_filter) {
			
			$arr_sql_operator = ['open' => [], 'close' => []];
			$count_filter = 0;
			
			foreach ($arr_filter as $arr_object_filter) {

				$sql_operator = ($arr_object_filter['options']['operator'] == 'and' || !$count_filter ? 'AND' : 'OR');
				
				if ($count_filter && $sql_operator == 'AND') {
					
					$operator_position = ($arr_object_filter['options']['operator_extra'] > 1 ? $arr_object_filter['options']['operator_extra'] : 1);
					
					// Close before self
					$close = $count_filter-1;
					$arr_sql_operator['close'][$close] = 1;
					$arr_sql_operator['close_virtual'][$close+1] = 1;
					
					// Open before self
					$open = ($count_filter - $operator_position);
					$open = ($open >= 0 ? $open : 0);
					
					if ($open > 0) {
						
						$count_in_clause = 0;
						
						for ($i = $count_filter-1; $i >= 0; $i--) {
							
							if ($arr_sql_operator['close'][$i-1]) {
								$count_in_clause++;
							}
							if ($arr_sql_operator['open'][$i]) {
								$count_in_clause -= $arr_sql_operator['open'][$i];
							}
							if (!$count_in_clause && $i <= $open) {
								$open = $i;
								break;
							}
						}
					}
					
					$arr_sql_operator['open'][$open]++;
				}
				
				$count_filter++;
			}
			
			$total_filter = count($arr_filter)-1;
			$count_filter = $total_filter;
			
			foreach (array_reverse($arr_filter) as $arr_object_filter) {

				$sql_operator = ($arr_object_filter['options']['operator'] == 'and' || !$count_filter ? 'AND' : 'OR');
				
				if ($count_filter && $sql_operator == 'OR') {
					
					$operator_position = ($arr_object_filter['options']['operator_extra'] > 1 ? $arr_object_filter['options']['operator_extra'] : 1);
					
					// Close self
					$close = $count_filter;
					
					if ($close < $total_filter) {
						
						$count_in_clause = 0;
						
						for ($i = $count_filter; $i <= $total_filter; $i++) {
							
							if ($arr_sql_operator['open'][$i]) {
								$count_in_clause += $arr_sql_operator['open'][$i];
							}
							if ($arr_sql_operator['close_virtual'][$i]) {
								$count_in_clause -= $arr_sql_operator['close_virtual'][$i];
							}
							if (!$count_in_clause) {
								$close = $i;
								break;
							}
						}
					}
					
					$arr_sql_operator['close'][$close]++;
					$arr_sql_operator['close_virtual'][$close]++;

					// Open before self
					$open = ($count_filter - $operator_position);
					$open = ($open >= 0 ? $open : 0);
					
					if ($open > 0) {
						
						$count_in_clause = 0;
						
						for ($i = $count_filter-1; $i >= 0; $i--) {
							
							if ($arr_sql_operator['close'][$i]) {
								$count_in_clause++;
							}
							if ($arr_sql_operator['open'][$i]) {
								$count_in_clause -= $arr_sql_operator['open'][$i];
							}
							if (!$count_in_clause && $i <= $open) {
								$open = $i;
								break;
							}
						}
					}
					
					$arr_sql_operator['open'][$open]++;
				}
				
				$count_filter--;
			}
				
			foreach ($arr_sql_build as $count_filter => $arr_sql) {
				
				// Filter specific
				$sql_open = str_pad('', $arr_sql_operator['open'][$count_filter], '(', STR_PAD_LEFT);
				$sql_close = str_pad('', $arr_sql_operator['close'][$count_filter], ')', STR_PAD_RIGHT);
				$sql_operator = ($count_filter > 0 ? $arr_sql['sql_operator'] : '');
				$sql_ignore = ($count_filter > 0 && $arr_sql['sql_operator'] == 'AND' ? 'TRUE' : 'FALSE'); // Use operator to determine whether to use TRUE or FALSE to ignore empty filters
				
				$sql_add = ($arr_sql['sql_filter'] ?: $sql_ignore);
				$arr_sql_filter['filter'][] = $sql_operator." ".$sql_open." ".$sql_add." ".$sql_close;
				
				// Filter overall
				$sql_operator = ($this->arr_sql_filter_purpose[$purpose] ? $arr_sql['sql_operator'] : '');

				$this->arr_sql_filter_purpose[$purpose][$arr_sql['filter_code']] = $sql_operator." ".$sql_open." ".$sql_add." ".$sql_close;
			}
			
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if (!$arr_sql_build_object_subs[$object_sub_details_id]) {
					continue;
				}
				
				foreach ($arr_sql_build as $count_filter => $arr_sql) {
					
					$sql_open = str_pad('', $arr_sql_operator['open'][$count_filter], '(', STR_PAD_LEFT);
					$sql_close = str_pad('', $arr_sql_operator['close'][$count_filter], ')', STR_PAD_RIGHT);
					$sql_operator = ($count_filter > 0 ? $arr_sql['sql_operator'] : '');
					$sql_ignore = ($count_filter == 0 || ($count_filter > 0 && $arr_sql['sql_operator'] == 'AND') ? 'TRUE' : 'FALSE'); // Use operator to determine whether to use TRUE or FALSE to ignore empty filters
					
					$sql_add = ($arr_sql_build_object_subs[$object_sub_details_id][$count_filter] ?: $sql_ignore);
					$arr_sql_filter['object_subs'][$object_sub_details_id]['filter'][] = $sql_operator." ".$sql_open." ".$sql_add." ".$sql_close;
				}
			}
		}
		
		return $arr_sql_filter;
	}
		
	public function format2SQLValueType($type, $arr_values, $table_name, $arr_options = []) {
		
		$arr_values = ($arr_values ? (array)$arr_values : []);
		
		if ($arr_options['object_sub_description_id']) {
			$column_name = $table_name.'.'.StoreType::getValueTypeValue($this->arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']]['object_sub_description_value_type'], 'search');
		} else if ($arr_options['object_description_id']) {
			$column_name = $table_name.'.'.StoreType::getValueTypeValue($this->arr_type_set['object_descriptions'][$arr_options['object_description_id']]['object_description_value_type'], 'search');
		} else {
			$column_name = $table_name.'.value';
		}
		
		$arr_transcension = $arr_values['transcension'];
		unset($arr_values['transcension']);
		
		$arr_sql_filter_collect = [];
		$arr_sql_filter_collect_pre = [];
		$sql_filter_value = '';
		$sql_filter_objects = '';

		switch ($type) {
			case 'boolean':
				
				$sql_add = StoreTypeObjects::formatToSQLValueFilter($type, $arr_values[0], $column_name);
				
				$arr_sql_filter_collect[] = $sql_add;
				$arr_sql_filter_collect_pre[] = $sql_add;
				break;
			case 'text_tags':
			case 'reversed_collection':
				
				$arr_str = [];
				$table_name_objects = $table_name.'_objects';
				
				foreach ($arr_values as $value) { // Filter forms are added to root of the filter, add them to the object id filter
					if ($value['object_filter']) {
						foreach ($value['object_filter'] as $arr_object_filter) {
							$arr_values['type_tags'][$arr_object_filter['type_id']]['objects']['foo']['object_filter'][] = $arr_object_filter;
						}
					}
				}
				
				foreach ((array)$arr_values['type_tags'] as $type_id => $arr_tags) {
					
					$arr_str_str = [];
					
					$arr_tags['objects'] = ($arr_tags['objects'] ? (array)$arr_tags['objects'] : []);
					$arr_tags['values'] = ($arr_tags['values'] ? (array)$arr_tags['values'] : []);

					// Tag Objects
					
					$arr_options_extra = $arr_options + ['ref_type_id' => $type_id];
				
					if ($arr_options['object_sub_description_id']) {
						$arr_sql_format = $this->format2SQLReferencingObjects('object_sub_definition_objects', $arr_tags['objects'], $table_name_objects, $arr_options_extra);
					} else {
						$arr_sql_format = $this->format2SQLReferencingObjects('object_definition_objects', $arr_tags['objects'], $table_name_objects, $arr_options_extra);
					}
					if ($arr_sql_format['filter']) {
						$arr_str_str[] = $arr_sql_format['filter'];
					}
					
					// Tag Values
					
					if ($type_id) {
						$sql_type_ids = "= ".(int)$type_id;
					} else if ($this->arr_scope['types']) {
						$sql_type_ids = "IN (".implode(',', $this->arr_scope['types']).")";
					}
					
					$arr_sql_format = $this->format2SQLValueType('',  $arr_tags['values'], $table_name_objects);
					if ($arr_sql_format['filter']) {
						$arr_str_str[] = $table_name_objects.".ref_type_id ".$sql_type_ids;
						$arr_str_str[] = $arr_sql_format['filter'];
					}
					
					if ($arr_str_str) {
						$arr_str[] = "(".implode(" AND ", $arr_str_str).")";
					}
				}
				
				if ($arr_str) {
					$sql_add = "(".implode(" OR ", $arr_str).")";
					$arr_sql_filter_collect[] = $sql_add;
					$arr_sql_filter_collect_pre[] = $sql_add;
					$sql_filter_objects = $sql_add;
				}
								
				$arr_sql_format = $this->format2SQLValueType('text',  $arr_values['text'], $table_name, $arr_options);
				if ($arr_sql_format['filter']) {
					$arr_sql_filter_collect[] = $arr_sql_format['filter'];
					if ($arr_sql_format['filter_pre']) {
						$arr_sql_filter_collect_pre[] = $arr_sql_format['filter_pre'];
						$sql_filter_value = $arr_sql_format['filter_pre'];
					}
				}

				break;
			default:
			
				$arr_str = [];
				
				foreach ($arr_values as $value) {
					$arr_str[] = StoreTypeObjects::formatToSQLValueFilter($type, $value, $column_name);
				}
				
				if ($arr_str) {
					$sql_add = "(".implode(" OR ", $arr_str).")";
					$arr_sql_filter_collect[] = $sql_add;
					$arr_sql_filter_collect_pre[] = $sql_add;
				}
		}
		
		$arr_sql_filter = [];
		$arr_sql_filter_pre = [];
		
		if ($arr_transcension) {
			$arr_sql_filter[] = StoreTypeObjects::formatToSQLTranscension($type, $arr_transcension, $column_name);
		}
		
		if ($arr_sql_filter_collect) {
			$arr_sql_filter = array_merge($arr_sql_filter, $arr_sql_filter_collect);
		}
		if ($arr_sql_filter_collect_pre) {
			$arr_sql_filter_pre = array_merge($arr_sql_filter_pre, $arr_sql_filter_collect_pre);
		}
		
		$sql_filter = ($arr_sql_filter ? '('.implode(' AND ', $arr_sql_filter).')' : '');
		$sql_filter_pre = ($arr_sql_filter_pre ? '('.implode(' AND ', $arr_sql_filter_pre).')' : '');

		if ($sql_filter && ($sql_filter_objects || $sql_filter_value)) {
			return ['filter' => $sql_filter, 'filter_pre' => $sql_filter_pre, 'objects' => $sql_filter_objects, 'value' => $sql_filter_value];
		} else {
			return ['filter' => $sql_filter, 'filter_pre' => $sql_filter_pre];
		}
	}
	
	public function format2SQLHasSubObjects($arr_relationality, $table_name, $arr_object_sub_details_ids) {
		
		$version_select_tos = self::generateVersioning('active', 'object_sub', 'nodegoat_tos');
		$do_exists = ((
			(($arr_relationality['equality'] == '≥' || !$arr_relationality['equality']) && $arr_relationality['value'] == 1)
				||
			(($arr_relationality['equality'] == '=' || !$arr_relationality['equality']) && $arr_relationality['value'] == 0)
		) ? true : false);
		$sql_object_sub_details_ids = (is_array($arr_object_sub_details_ids) ? implode(',', $arr_object_sub_details_ids) : $arr_object_sub_details_ids);
		
		$column_name_relationality = "(SELECT ".($do_exists ? "1" : "COUNT(*)")."
				FROM ".DB::getTable("DATA_NODEGOAT_TYPE_OBJECT_SUBS")." nodegoat_tos
			WHERE nodegoat_tos.object_id = ".$table_name.".id
				".($arr_object_sub_details_ids ? "AND nodegoat_tos.object_sub_details_id IN (".$sql_object_sub_details_ids.")" : "")."
				AND ".$version_select_tos."
		)";
		
		if ($do_exists) {
			$arr_sql_filter = ($arr_relationality['value'] == 0 ? "NOT " : "")."EXISTS ".$column_name_relationality;
		} else {
			$arr_sql_filter = StoreTypeObjects::formatToSQLValueFilter('int', $arr_relationality, $column_name_relationality);
		}
		
		return $arr_sql_filter;
	}
	
	public function format2SQLReferencingObjects($from, $arr_values, $table_name, $arr_options = []) {
		
		$arr_values = ($arr_values ? (array)$arr_values : []);

		unset($arr_values['beacon']);
		$arr_relationality = $arr_values['relationality'];
		unset($arr_values['relationality']);
		$arr_transcension = $arr_values['transcension'];
		unset($arr_values['transcension']);
		
		if ($arr_options['ref_type_id']) {
			$sql_type_ids = "= ".(int)$arr_options['ref_type_id'];
		} else if ($this->arr_scope['types']) {
			$sql_type_ids = "IN (".implode(',', $this->arr_scope['types']).")";
		}
		
		$do_filter = ($arr_relationality && $arr_relationality['filter']);
		
		switch ($from) {
			case 'object_definition':
				$ref_type_id = $this->arr_type_set['object_descriptions'][$arr_options['object_description_id']]['object_description_ref_type_id'];
				break;
			case 'object_sub_definition':
				$ref_type_id = $this->arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']]['object_sub_description_ref_type_id'];
				break;
			case 'object_sub_location':
				$ref_type_id = $arr_options['ref_type_id'];
				break;
			case 'object_sub_referenced':
				$ref_type_id = $this->arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_details']['object_sub_details_type_id'];
				break;
			case 'object_definition_objects':
				$ref_type_id = $arr_options['ref_type_id'];
				break;
			case 'object_sub_definition_objects':
				$ref_type_id = $arr_options['ref_type_id'];
				break;
		}
		
		if ($arr_values) {
				
			$arr_sql_filter_objects = [];
			
			foreach ($arr_values as $value) {
							
				$sql = $this->format2SQLMatchObjects($ref_type_id, $value, '[X]');
					
				$arr_sql_filter_objects[] = $sql;
			}
				
			$sql_filter_objects = "(".implode(" OR ", $arr_sql_filter_objects).")";
		}
			
		$versioning = 'active';	
		$version_select_to = self::generateVersioning($versioning, 'object', 'nodegoat_to');
		$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
		
		switch ($from) {
			case 'object_definition':
			
				$column_name_value = $table_name.".ref_object_id";

				$column_name_relationality = "(SELECT COUNT(ref_object_id)
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
					WHERE nodegoat_to_def.object_id = ".$table_name.".object_id
						AND nodegoat_to_def.object_description_id = ".$table_name.".object_description_id
						AND nodegoat_to_def.version = ".$table_name.".version
						".($arr_values && $do_filter ? "AND ".str_replace('[X]', 'nodegoat_to_def.ref_object_id', $sql_filter_objects) : "")."
				)";
				
				break;
			case 'object_sub_definition':
			
				$column_name_value = $table_name.".ref_object_id";
				
				if ($this->arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']]['object_sub_description_use_object_description_id']) {
					
					$column_name_relationality = "(SELECT COUNT(ref_object_id)
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
						WHERE nodegoat_to_def.object_id = ".$table_name.".object_id
							AND nodegoat_to_def.object_description_id = ".$table_name.".object_description_id
							AND nodegoat_to_def.version = ".$table_name.".version
							".($arr_values && $do_filter ? "AND ".str_replace('[X]', 'nodegoat_to_def.ref_object_id', $sql_filter_objects) : "")."
					)";
				} else {
					
					$column_name_relationality = "(SELECT COUNT(ref_object_id)
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
						WHERE nodegoat_tos_def.object_sub_id = ".$table_name.".object_sub_id
							AND nodegoat_tos_def.object_sub_description_id = ".$table_name.".object_sub_description_id
							AND nodegoat_tos_def.version = ".$table_name.".version
							".($arr_values && $do_filter ? "AND ".str_replace('[X]', 'nodegoat_tos_def.ref_object_id', $sql_filter_objects) : "")."
					)";
				}
				break;
			case 'object_sub_location':
			
				$column_name_value = $table_name."_cache.ref_object_id";
				
				$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true, true);
				
				$column_name_relationality = "(SELECT COUNT(*)
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
						".$arr_sql_location['tables']."
					WHERE nodegoat_tos.id = ".$table_name.".id
						AND nodegoat_tos.version = ".$table_name.".version
						".($arr_values && $do_filter ? "AND ".str_replace('[X]', $arr_sql_location['column_ref_object_id'], $sql_filter_objects) : "")."
				)";
				break;
			case 'object_sub_referenced':
			
				$column_name_value = $table_name.".object_id";
				
				$column_name_relationality = "NULL"; // Relationality is handled through cross-referenced functions
				
				break;
			case 'object_definition_objects':
			
				$column_name_value = $table_name.".ref_object_id";
				
				$column_name_relationality = "(SELECT COUNT(ref_object_id)
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
					WHERE nodegoat_to_def_ref.object_id = ".$table_name.".object_id
						AND nodegoat_to_def_ref.object_description_id = ".$table_name.".object_description_id
						AND nodegoat_to_def_ref.state = 1
						".($arr_values && $do_filter ? "AND ".str_replace('[X]', 'nodegoat_to_def_ref.ref_object_id', $sql_filter_objects) : "")."
						".($sql_type_ids ? "AND nodegoat_to_def_ref.ref_type_id ".$sql_type_ids : "")."
				)";
				
				$sql_filter_type_ids = $table_name.".ref_type_id ".$sql_type_ids;
				
				break;
			case 'object_sub_definition_objects':
			
				$column_name_value = $table_name.".ref_object_id";
				
				$column_name_relationality = "(SELECT COUNT(ref_object_id)
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
					WHERE nodegoat_tos_def_ref.object_sub_id = ".$table_name.".object_sub_id
						AND nodegoat_tos_def_ref.object_sub_description_id = ".$table_name.".object_sub_description_id
						AND nodegoat_tos_def_ref.state = 1
						".($arr_values && $do_filter ? "AND ".str_replace('[X]', 'nodegoat_tos_def_ref.ref_object_id', $sql_filter_objects) : "")."
						".($sql_type_ids ? "AND nodegoat_tos_def_ref.ref_type_id ".$sql_type_ids : "")."
				)";
				
				$sql_filter_type_ids = $table_name.".ref_type_id ".$sql_type_ids;
				
				break;
		}
		
		$arr_sql_filter = [];
		$arr_sql_filter_pre = [];
		
		if ($arr_values) {
			$sql = str_replace('[X]', $column_name_value, $sql_filter_objects);
			$arr_sql_filter[] = $sql;
			$arr_sql_filter_pre[] = $sql;
		}
			
		if ($arr_relationality) {
			$arr_sql_filter[] = StoreTypeObjects::formatToSQLValueFilter('int', $arr_relationality, $column_name_relationality);
		}
		
		if ($arr_transcension) {
			
			$sql_condition = "EXISTS (SELECT TRUE
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				WHERE nodegoat_to.id = ".$column_name_value."
					AND ".$version_select_to."
			)";
			
			$arr_sql_filter[] = StoreTypeObjects::formatToSQLTranscension('condition', $arr_transcension, $sql_condition);
		}
		
		if ($arr_sql_filter && $sql_filter_type_ids) { // A description with extra objects can have various type ids, make sure to always limit the scope accordingly
			array_unshift($arr_sql_filter, $sql_filter_type_ids);
			if ($arr_sql_filter_pre) {
				array_unshift($arr_sql_filter_pre, $sql_filter_type_ids);
			}
		}
		
		$sql_filter = ($arr_sql_filter ? '('.implode(' AND ', $arr_sql_filter).')' : '');
		$sql_filter_pre = ($arr_sql_filter_pre ? '('.implode(' AND ', $arr_sql_filter_pre).')' : '');

		return ['filter' => $sql_filter, 'filter_pre' => $sql_filter_pre];
	}
		
	public function format2SQLReferencedObjects($arr_from, $arr_options = []) {
		
		$arr_from = (is_array($arr_from) ? array_combine($arr_from, $arr_from) : [$arr_from => $arr_from]);
		$arr_ref_type_ids = [];
		
		if ($arr_options['ref_type_id']) {
			$sql_type_ids = "AND nodegoat_to_referenced.type_id = ".(int)$arr_options['ref_type_id'];
			$arr_ref_type_ids[] = $arr_options['ref_type_id'];
		} else if ($this->arr_scope['types']) {
			$sql_type_ids = "AND nodegoat_to_referenced.type_id IN (".implode(',', $this->arr_scope['types']).")";
			$arr_ref_type_ids = $this->arr_scope['types'];
		}

		$arr_filter = [];
		if ($arr_options['filter']['values']) {
			$arr_filter['values'] = $arr_options['filter']['values'];
			unset($arr_options['filter']['values']);
		}
		if ($arr_options['filter']['objects']) {
			$arr_filter['objects'] = $arr_options['filter']['objects'];
			unset($arr_options['filter']['objects']);
		}
		foreach ((array)$arr_options['filter'] as $key => $value) { // Filter forms are added to root of the filter, add them to the object id filter
			$arr_filter['objects'][$key] = $value;
		}
		
		unset($arr_filter['objects']['beacon']);
		$arr_relationality = $arr_filter['objects']['relationality'];
		unset($arr_filter['objects']['relationality']);
		
		$do_filter = ($arr_relationality && $arr_relationality['filter']);
		$do_group = ($arr_relationality && $arr_relationality['group']);
		
		$arr_object_description_ids = [];
		$arr_object_sub_description_ids = [];
		$arr_object_sub_details_ids = [];
		$arr_filtering_object_sub_details_ids = [];
		
		$has_target = ($arr_options['object_description_id'] || $arr_options['object_sub_details_id']);
		
		foreach ($arr_ref_type_ids as $ref_type_id) {
			
			$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);

			foreach ($arr_ref_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if (!$arr_from['object_definition'] || ($has_target && $arr_options['object_description_id'] != $object_description_id) || ($arr_object_description['object_description_ref_type_id'] != $this->type_id && !$arr_object_description['object_description_is_dynamic'])) {
					continue;
				}
					
				if ($arr_object_description['object_description_is_dynamic']) {
					$arr_object_description_ids['values'][$object_description_id] = $object_description_id;
				} else {		
					$arr_object_description_ids['objects'][$object_description_id] = $object_description_id;
				}
			}
			
			foreach ($arr_ref_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if ($has_target && $arr_options['object_sub_details_id'] != $object_sub_details_id) {
					continue;
				}
				
				$arr_object_sub_details_ids[$object_sub_details_id] = $object_sub_details_id;
				
				$is_filtering_object_sub_details_id = ($arr_options['ref_type_id'] && $arr_relationality && $do_filter); // Use filtering on sub-objects to get filtered relationality
				
				if ($is_filtering_object_sub_details_id && ($arr_from['object_sub_location_reference_cache'] || $arr_from['object_sub_location_reference_self'] || $arr_from['object_sub_location_reference_use_object_sub_location_reference'] || $arr_from['object_sub_location_reference_use_object_sub_definition'] || $arr_from['object_sub_location_reference_use_object_definition'])) {
					
					$arr_filtering_object_sub_details_ids['object_sub_details'][$object_sub_details_id] = $object_sub_details_id;
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					if (!$arr_from['object_sub_definition'] || ($has_target && $arr_options['object_sub_description_id'] != $object_sub_description_id) || ($arr_object_sub_description['object_sub_description_ref_type_id'] != $this->type_id && !$arr_object_sub_description['object_sub_description_is_dynamic'])) {
						continue;
					}
					
					if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
						$arr_object_sub_description_ids['values'][$object_sub_description_id] = $object_sub_description_id;
					} else {		
						$arr_object_sub_description_ids['objects'][$object_sub_description_id] = $object_sub_description_id;
					}
					
					if ($is_filtering_object_sub_details_id) {
						
						$arr_filtering_object_sub_details_ids['object_sub_descriptions'][$object_sub_details_id] = $object_sub_details_id;
					}
				}
			}
		}
		
		if ($arr_options['ref_type_id']) {
						
			if ($arr_filter['objects']) {
				
				$arr_sql_filter_objects = [];
				
				foreach ($arr_filter['objects'] as $value) {
					
					if ($arr_filtering_object_sub_details_ids && is_array($value) && $value['object_filter']) { // Use filtering on sub-objects to get filtered relationality
						
						$arr_combine_object_sub_details_ids = (array)$arr_filtering_object_sub_details_ids['object_sub_details'] + (array)$arr_filtering_object_sub_details_ids['object_sub_descriptions'];
						
						$arr_sql = $this->format2SQLMatchObjectsSubs($arr_options['ref_type_id'], $arr_combine_object_sub_details_ids, $value, '[Y]');
						
						$arr_sql_filter_objects['object'][] = $arr_sql['id'];
						foreach ((array)$arr_filtering_object_sub_details_ids['object_sub_details'] as $object_sub_details_id) {
							$arr_sql_filter_objects['object_sub_details'][] = $arr_sql['object_sub_details'][$object_sub_details_id];
						}
						foreach ((array)$arr_filtering_object_sub_details_ids['object_sub_descriptions'] as $object_sub_details_id) {
							$arr_sql_filter_objects['object_sub_descriptions'][] = $arr_sql['object_sub_details'][$object_sub_details_id];
						}
					} else {
						
						$sql = $this->format2SQLMatchObjects($arr_options['ref_type_id'], $value, '[X]');
						
						$arr_sql_filter_objects['object'][] = $sql;
						$arr_sql_filter_objects['object_sub_details'][] = $sql;
						$arr_sql_filter_objects['object_sub_descriptions'][] = $sql;
					}
				}
				
				$arr_sql_filter_objects['object'] = "(".implode(" OR ", $arr_sql_filter_objects['object']).")";
				if ($arr_sql_filter_objects['object_sub_details']) {
					$arr_sql_filter_objects['object_sub_details'] = "(".implode(" OR ", $arr_sql_filter_objects['object_sub_details']).")";
				}
				if ($arr_sql_filter_objects['object_sub_descriptions']) {
					$arr_sql_filter_objects['object_sub_descriptions'] = "(".implode(" OR ", $arr_sql_filter_objects['object_sub_descriptions']).")";
				}
			}
			
			if ($arr_filter['values']) {
				
				$arr_sql_format = $this->format2SQLValueType('',  $arr_filter['values'], '[X]');
				$sql_filter_values = $arr_sql_format['filter'];
			}
		}
		
		$arr = [];
		
		$versioning = 'active';		
		$version_select_to = self::generateVersioning($versioning, 'object', 'nodegoat_to_referenced');
		$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
		
		foreach ($arr_from as $from) {
			
			switch ($from) {
				case 'object_definition':
					if ($arr_object_description_ids['objects']) {
						$arr['nodegoat_to_def.ref_object_id'] = [
							'sql' => "FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_referenced ON (nodegoat_to_referenced.id = nodegoat_to_def.object_id AND ".$version_select_to.")
								WHERE ".self::generateVersioning($versioning, 'record', 'nodegoat_to_def')."
									AND nodegoat_to_def.object_description_id IN (".implode(',', $arr_object_description_ids['objects']).")
									".$sql_type_ids."
							",
							'sql_filter' => ($arr_filter['objects'] ? "AND ".str_replace(['[X]', '[Y]'], 'nodegoat_to_def.object_id', $arr_sql_filter_objects['object']) : ""),
							'object_id' => 'nodegoat_to_def.object_id'
						];
					}
					if ($arr_object_description_ids['values']) {
						$arr['nodegoat_to_def_ref.ref_object_id'] = [
							'sql' => "FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_referenced ON (nodegoat_to_referenced.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
							WHERE nodegoat_to_def_ref.ref_type_id = ".$this->type_id."
								AND nodegoat_to_def_ref.object_description_id IN (".implode(',', $arr_object_description_ids['values']).")
								AND nodegoat_to_def_ref.state = 1
								".$sql_type_ids."
							",
							'sql_filter' => ($arr_filter['objects'] ? "AND ".str_replace(['[X]', '[Y]'], 'nodegoat_to_def_ref.object_id', $arr_sql_filter_objects['object']) : "")."
								".($arr_filter['values'] ? "AND ".str_replace('[X]', 'nodegoat_to_def_ref', $sql_filter_values) : ''),
							'object_id' => 'nodegoat_to_def_ref.object_id'
						];
					}
					break;
				case 'object_sub_definition':
				
					if (!$arr_object_sub_details_ids) {
						break;
					}
					
					if ($arr_object_sub_description_ids['objects']) {
						$arr['nodegoat_tos_def.ref_object_id'] = [
							'sql' => "FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_referenced ON (nodegoat_to_referenced.id = nodegoat_tos.object_id AND ".$version_select_to.")
								WHERE ".self::generateVersioning($versioning, 'record', 'nodegoat_tos_def')."
									AND nodegoat_tos_def.object_sub_description_id IN (".implode(',', $arr_object_sub_description_ids['objects']).")
									".$sql_type_ids."
							",
							'sql_filter' => ($arr_filter['objects'] ? "AND ".str_replace(['[X]', '[Y]'], ['nodegoat_tos.object_id', 'nodegoat_tos_def.object_sub_id'], $arr_sql_filter_objects['object_sub_descriptions']) : ""),
							'object_id' => 'nodegoat_tos.object_id'
						];
					}
					if ($arr_object_sub_description_ids['values']) {
						$arr['nodegoat_tos_def_ref.ref_object_id'] = [
							'sql' => "FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_referenced ON (nodegoat_to_referenced.id = nodegoat_tos.object_id AND ".$version_select_to.")
							WHERE nodegoat_tos_def_ref.ref_type_id = ".$this->type_id."
								AND nodegoat_tos_def_ref.object_sub_description_id IN (".implode(',', $arr_object_sub_description_ids['values']).")
								AND nodegoat_tos_def_ref.state = 1
								".$sql_type_ids."
							",
							'sql_filter' => ($arr_filter['objects'] ? "AND ".str_replace(['[X]', '[Y]'], ['nodegoat_tos.object_id', 'nodegoat_tos_def_ref.object_sub_id'], $arr_sql_filter_objects['object_sub_descriptions']) : "")."
								".($arr_filter['values'] ? "AND ".str_replace('[X]', 'nodegoat_tos_def_ref', $sql_filter_values) : ""),
							'object_id' => 'nodegoat_tos.object_id'
						];
					}
					break;
				case 'object_sub_location_reference_cache':
				
					if (!$arr_object_sub_details_ids) {
						break;
					}
					
					$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true, true);
					
					$arr[$arr_sql_location['column_ref_object_id']] = [
						'sql' => "FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
								".$arr_sql_location['tables']."
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_referenced ON (nodegoat_to_referenced.id = nodegoat_tos.object_id AND ".$version_select_to.")
							WHERE ".$version_select_tos."
								AND ".$arr_sql_location['column_ref_type_id']." = ".$this->type_id."
								AND nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_object_sub_details_ids).")
								".$sql_type_ids."
						",
						'sql_filter' => ($arr_filter['objects'] ? "AND ".str_replace(['[X]', '[Y]'], ['nodegoat_tos.object_id', 'nodegoat_tos.id'], $arr_sql_filter_objects['object_sub_details']) : ""),
						'object_id' => 'nodegoat_tos.object_id'
					];
					break;				
				case 'object_sub_location_reference_self':
				case 'object_sub_location_reference_use_object_sub_location_reference':
				case 'object_sub_location_reference_use_object_sub_definition':
				case 'object_sub_location_reference_use_object_definition':
				
					if (!$arr_object_sub_details_ids) {
						break;
					}
				
					if ($from == 'object_sub_location_reference_self') {
						
						$id = "nodegoat_tos.location_ref_object_id";
						$where = "nodegoat_tos_det.location_use_object_sub_details_id = 0 AND nodegoat_tos_det.location_use_object_sub_description_id = 0 AND nodegoat_tos_det.location_use_object_description_id = 0 AND nodegoat_tos.location_ref_type_id = ".$this->type_id;
					} else if ($from == 'object_sub_location_reference_use_object_sub_location_reference') {
					
						$join = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos_use_tos ON (nodegoat_tos_use_tos.object_id = nodegoat_tos.object_id AND nodegoat_tos_use_tos.object_sub_details_id = nodegoat_tos_det.location_use_object_sub_details_id AND nodegoat_tos_use_tos.location_ref_type_id = ".$this->type_id." AND ".self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos_use_tos').")";
						$id = "nodegoat_tos_use_tos.location_ref_object_id";
					} else if ($from == 'object_sub_location_reference_use_object_sub_definition') {
					
						$join = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_use_tos_def ON (nodegoat_tos_use_tos_def.object_sub_id = nodegoat_tos.id AND nodegoat_tos_use_tos_def.object_sub_description_id = nodegoat_tos_det.location_use_object_sub_description_id AND nodegoat_tos_det.location_ref_type_id = ".$this->type_id." AND ".self::generateVersioning($versioning, 'record', 'nodegoat_tos_use_tos_def').")";
						$id = "nodegoat_tos_use_tos_def.ref_object_id";
					} else if ($from == 'object_sub_location_reference_use_object_definition') {

						$join = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_use_to_def ON (nodegoat_tos_use_to_def.object_id = nodegoat_tos.object_id AND nodegoat_tos_use_to_def.object_description_id = nodegoat_tos_det.location_use_object_description_id AND nodegoat_tos_det.location_ref_type_id = ".$this->type_id." AND ".self::generateVersioning($versioning, 'record', 'nodegoat_tos_use_to_def').")";
						$id = "nodegoat_tos_use_to_def.ref_object_id";
					}
					
					$arr[$id] = [
						'sql' => "FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_referenced ON (nodegoat_to_referenced.id = nodegoat_tos.object_id AND ".$version_select_to.")
								JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos.object_sub_details_id)
								".$join."
							WHERE ".$version_select_tos."
								".($where ? "AND ".$where : "")."
								AND nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_object_sub_details_ids).")
								".$sql_type_ids."
						",
						'sql_filter' => ($arr_filter['objects'] ? "AND ".str_replace(['[X]', '[Y]'], ['nodegoat_tos.object_id', 'nodegoat_tos.id'], $arr_sql_filter_objects['object_sub_details']) : ""),
						'object_id' => 'nodegoat_tos.object_id'
					];
					break;
			}
		}
		
		if ($arr) {

			$arr_sql = [];
			
			foreach ($arr as $id => $sql) {
				
				$arr_sql[] = "SELECT ".$id." AS id, ".($do_group ? $sql['object_id'] : "COUNT(".$id.")")." AS count
					".$sql['sql'].$sql['sql_filter']."
				AND ".$id."
				GROUP BY ".($do_group ? $id.", ".$sql['object_id'] : $id);
			}
			
			$sql_filter = "SELECT id, ".($do_group ? "COUNT(DISTINCT count)" : "SUM(count)")." AS count
				FROM (
					 (".implode(") UNION ALL (", $arr_sql).")
				) AS foo
				GROUP BY id
			";
			
			$table_name = $this->generateTemporaryTableName('temp_referenced_'.$this->type_id, $sql_filter);
			
			$this->addPre($table_name,
				[
					'table_name' => $table_name,
					'settings' => "
						id INT,
						count INT,
						PRIMARY KEY (id)",
					'query' => $sql_filter
				]
			);
			
			$sql_filter = "(SELECT count FROM ".DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name)." WHERE id = nodegoat_to.id)";

			$sql_filter = "COALESCE(".$sql_filter.", 0)";
							
			if ($arr_relationality) {
				$sql_filter = StoreTypeObjects::formatToSQLValueFilter('int', $arr_relationality, $sql_filter);
			}
		} else {
			
			$sql_filter = "FALSE";
		}
				
		return $sql_filter;
	}
	
	public function format2SQLMatchObjects($ref_type_id, $value, $column_name, $overview = false) {
		
		if ($overview) {
			
			$arr_return = [];
		}
					
		if (is_array($value)) {
			
			if ($value['query']) {
				$sql = "EXISTS (SELECT TRUE FROM (".$value['query'].") AS foo WHERE foo.id = ".$column_name.")";
				//$sql = $column_name." IN (SELECT id FROM (".$value['query'].") AS foo)";
			} else if ($value['query_dependent']) {
				$sql = "EXISTS (SELECT TRUE FROM (".$value['query_dependent'].") AS foo WHERE foo.id = ".$column_name.")";
				//$sql = $column_name." IN (SELECT id FROM (".$value['query_dependent'].") AS foo)";
			} else if ($value['table']) {
				$sql = "EXISTS (SELECT TRUE FROM (".$value['table'].") AS foo WHERE foo.id = ".$column_name.")";
				//$sql = $column_name." IN (SELECT id FROM ".$value['table']." AS foo)";
			} else if ($value['objects']) {
				$sql = $column_name." IN (".(is_array($value['objects']) ? implode(",", $value['objects']) : $value['objects']).")";
			} else {

				$filter = new FilterTypeObjects($ref_type_id, 'id');
				$filter->setScope($this->arr_scope);
				$filter->setDepth($this->getDepth());
				$filter->setDifferentiationId($this->getDifferentiationId());
				$filter->setFilter($value);
				
				$sql_filter = $filter->sqlQuery('object_ids');
				
				$arr_sql_pre_settings = $filter->getPre();
				$this->arr_sql_pre_settings += $arr_sql_pre_settings;
				
				$table_name = $this->generateTemporaryTableName('temp_'.$ref_type_id, $sql_filter);
				
				$this->addPre($table_name,
					[
						'table_name' => $table_name,
						'settings' => "
							id INT,
							PRIMARY KEY (id)",
						'query' => $sql_filter
					]
				);
				
				if ($overview) {
					
					$arr_return['table_name'] = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name);
					$arr_return['sql_filter'] = $sql_filter;
				}
				
				$sql = "EXISTS (SELECT TRUE FROM ".DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name)." AS foo WHERE foo.id = ".$column_name.")";
			} 
		} else {
			
			$sql = $column_name." = ".(int)$value;
		}
		
		if ($overview) {
					
			$arr_return['sql'] = $sql;
			
			return $arr_return;
		}
		
		return $sql;
	}
	
	public function format2SQLMatchObjectsSubs($ref_type_id, $arr_object_sub_details_ids, $arr_filter, $column_name) {
		
		foreach ($arr_object_sub_details_ids as $object_sub_details_id) {
			$arr_object_sub_details_ids[$object_sub_details_id] = ['object_sub_descriptions' => []];
		}
					
		$filter = new FilterTypeObjects($ref_type_id, 'all');
		$filter->setScope($this->arr_scope);
		$filter->setDepth($this->getDepth());
		$filter->setDifferentiationId($this->getDifferentiationId());
		$filter->setSelection(['object' => [], 'object_descriptions' => [], 'object_sub_details' => $arr_object_sub_details_ids]);
		$filter->setFiltering([], ['object_sub_details' => $arr_object_sub_details_ids], true);
		$filter->setFilter($arr_filter);
		
		$sql_filter = $filter->sqlQuery('storage');
		
		$arr_sql_pre_settings = $filter->getPre();
		$this->arr_sql_pre_settings += $arr_sql_pre_settings;
		
		$table_name = $this->generateTemporaryTableName('temp_'.$ref_type_id, $sql_filter);
		$table_name_full = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name);
		
		$this->addPre($table_name,
			[
				'table_name' => $table_name,
				'settings' => $filter->sqlKeys('storage', $table_name_full),
				'query' => $sql_filter
			]
		);
		
		$arr_sql = ['id' => "EXISTS (SELECT TRUE FROM ".$table_name_full." WHERE id = ".$column_name.")"];
		
		foreach ($arr_object_sub_details_ids as $object_sub_details_id => $value) {
			$arr_sql['object_sub_details'][$object_sub_details_id] = "EXISTS (SELECT TRUE FROM ".$table_name_full." WHERE object_sub_".$object_sub_details_id."_id = ".$column_name.")";
		}

		return $arr_sql;
	}
	
	public function storeIdsTemporarily($arr_ids) {
		
		$table_name = $this->generateTemporaryTableName('temp_ids', serialize($arr_ids));
		$table_name_full = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$table_name);
		
		$this->addPre($table_name,
			[
				'table_name' => $table_name,
				'settings' => "
					id INT,
					PRIMARY KEY (id)",
				'sql' => "INSERT INTO ".$table_name_full."
					(id)
						VALUES
					(".implode("),(", $arr_ids).")
					".DBFunctions::onConflict('id', ['id'])."
				;"
			]
		);
					
		return $table_name_full;
	}
	
	public function cleanupFilterInput($arr_type_filter) { // Clean and check a user-generated filter
		
		if ($arr_type_filter['versioning']) {
			
			$filter_versioning = new FilterTypeObjects($this->type_id, 'id');
			$filter_versioning->setScope($this->arr_scope);
			$filter_versioning->setFilter(['object_versioning' => $arr_type_filter['versioning']]);
		}
		
		if ($arr_type_filter['form']) {
			
			$arr_filter_beacons = [];
			
			foreach ($arr_type_filter['form'] as $filter_code => $arr_filter_form) {
				
				if (!$arr_filter_form['source']['filter_beacon']) {
					continue;
				}
				
				$arr_filter_beacons[$arr_filter_form['source']['filter_code']][$arr_filter_form['source']['filter_beacon']] = $arr_filter_form['source']['filter_beacon'];
			}
			
			foreach ($arr_type_filter['form'] as $filter_code => &$arr_filter_form) {
				
				$arr_filter_form = FilterTypeObjects::cleanupFilterForm($arr_filter_form['type_id'], $arr_filter_form, ['beacons' => &$arr_filter_beacons[$filter_code]]);
			}
			unset($arr_filter_form);
			
			foreach ($arr_type_filter['form'] as $filter_code => $arr_filter_form) {
				
				if ($arr_filter_beacons[$arr_filter_form['source']['filter_code']][$arr_filter_form['source']['filter_beacon']]) { // Check if there are unused beacons, the form is obsolete
					
					unset($arr_type_filter['form'][$filter_code]);
				}
			}
			
			$arr_filter = FilterTypeObjects::convertFilterInput(['form' => $arr_type_filter['form']]);
			
			$filter_forms = new FilterTypeObjects($this->type_id, 'id');
			$filter_forms->setScope($this->arr_scope);
			$filter_forms->setFilter($arr_filter);
		}
		
		$arr = [];
		
		if ($arr_type_filter['versioning'] && $filter_versioning->isFiltered()) {
			$arr['versioning'] = $arr_type_filter['versioning'];
		}
		if ($arr_type_filter['form'] && $filter_forms->isFiltered()) {
			$arr['form'] = $arr_type_filter['form'];
		}
		
		return $arr;
	}
	
	public static function convertFilterInput($arr_type_filter) { // Convert a user-generated filter for operational purposes
		
		if (!is_array($arr_type_filter)) {
			error(getLabel('msg_filter_invalid'));
		}
		
		if (arrHasKeysRecursive(static::$arr_system_filter_keys, $arr_type_filter)) {
			error(getLabel('msg_illegal_action'));
		}
		
		$arr_filter = [];
		
		if ($arr_type_filter['versioning']) {
			
			$arr_filter['object_versioning'] = array_filter($arr_type_filter['versioning']);
			unset($arr_type_filter['versioning']);
		}
		
		if (!$arr_type_filter['form']) {
			$arr_type_filter['form'] = $arr_type_filter;
		}
		
		if ($arr_type_filter['form']) {
			
			foreach ($arr_type_filter['form'] as $filter_code => $value) {
			
				if (!$value || strpos($filter_code, 'filter_') !== 0) {
					continue;
				}
				
				$arr_filter['object_filter_form'][$filter_code] = $value;
			}
		}
		
		return $arr_filter;
	}
	
	public function limitRandom($amount = 1) {
		
		$this->storeResultTemporarily();
		
		$res = DB::query("SELECT COUNT(*) FROM ".$this->table_name."");
		
		$total = $res->fetchRow();
		$total = $total[0];
		
		if (!$total) {
			return;
		}
		
		$arr_sql = [];
		
		for ($i = 0; $i < $amount && $i < $total; $i++) {
			
			$nr = rand(0, $total-1);
			
			if ($arr_sql[$nr]) {
				
				$amount++;
			} else {
				
				$arr_sql[$nr] = "SELECT id
					FROM ".$this->table_name."
					LIMIT 1 OFFSET ".$nr."
				";
			}
		}
		
		$arr_res = DB::queryMulti(implode(';', $arr_sql));
		
		$arr_ids = [];
		
		foreach ($arr_res as $res) {
			
			$row = $res->fetchRow();
			$arr_ids[] = $row[0];
		}
		
		/*
		$res = DB::query("DELETE FROM ".$this->table_name."
			WHERE id NOT IN (".implode(',', $arr_ids).")
		");
		*/
		
		$res = DB::queryMulti("
			ALTER TABLE ".$this->table_name." RENAME TO ".$this->table_name."_old;
			CREATE".($this->is_temporary ? " TEMPORARY" : "")." TABLE ".$this->table_name." LIKE ".$this->table_name."_old;
			INSERT ".$this->table_name." SELECT * FROM ".$this->table_name."_old WHERE id IN (".implode(',', $arr_ids).");
			DROP TABLE ".$this->table_name."_old;
		");
	}
	
	// Direct calls
	
	public static function getTypesReferenced($type_ids, $ref_type_ids = false, $arr_options = ['model' => true, 'dynamic' => true, 'object_sub_locations' => true, 'model_is_used' => false, 'dynamic_is_used' => false]) {
				
		$sql_type_ids = (is_array($type_ids) ? implode(',', $type_ids) : $type_ids);
		$include_referencing = (is_array($type_ids) ? true : false);

		if ($ref_type_ids) {
			$sql_ref_type_ids = (is_array($ref_type_ids) ? implode(',', $ref_type_ids) : $ref_type_ids);
		}
		$include_referenced = (is_array($ref_type_ids) ? true : false);
		
		$model = keyIsUncontested('model', $arr_options);
		$dynamic = keyIsUncontested('dynamic', $arr_options);
		$object_sub_locations = keyIsUncontested('object_sub_locations', $arr_options);
		$model_is_used = $arr_options['model_is_used'];
		$dynamic_is_used = $arr_options['dynamic_is_used'];
		
		$versioning = (isset($arr_options['versioning']) ? $arr_options['versioning'] : 'active');
		
		$version_select_to = self::generateVersioning($versioning, 'object', 'nodegoat_to');
		$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
		
		$arr_sql = [];
		
		if ($model) {
			
			$version_select = self::generateVersioning($versioning, 'record', 'nodegoat_to_def');
				
			$arr_sql[] = "SELECT nodegoat_to_des.type_id, nodegoat_to_des.id AS object_description_id, nodegoat_to_des.in_name, nodegoat_to_des.in_search
					".($include_referencing ? ", nodegoat_to_des.ref_type_id" : "")."
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
				WHERE nodegoat_to_des.ref_type_id IN (".$sql_type_ids.")
					".($ref_type_ids ? "AND nodegoat_to_des.type_id IN (".$sql_ref_type_ids.")" : "")."
					".($model_is_used ? "AND EXISTS (
						SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
						WHERE nodegoat_to_def.object_description_id = nodegoat_to_des.id AND ".$version_select."
					)" : "")."
			";
		}
		
		if ($dynamic) {
			
			$arr_sql[] = "SELECT nodegoat_to_des.type_id, nodegoat_to_des.id AS object_description_id, nodegoat_to_des.in_name, nodegoat_to_des.in_search
					".($include_referencing ? ", nodegoat_t.id AS ref_type_id" : "")."
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des
					".($include_referencing ? "LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (
						nodegoat_t.id IN (".$sql_type_ids.")
						AND EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
							WHERE nodegoat_to_def_ref.object_description_id = nodegoat_to_des.id AND nodegoat_to_def_ref.ref_type_id = nodegoat_t.id AND nodegoat_to_def_ref.state = 1
						)
					)" : "")."
				WHERE (
						(nodegoat_to_des.value_type_base = 'text_tags')
						OR (nodegoat_to_des.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_to_des.ref_type_id AND mode = 1))
					)
					".($ref_type_ids ? "AND nodegoat_to_des.type_id IN (".$sql_ref_type_ids.")" : "")."
					".($dynamic_is_used ? "AND EXISTS (
						SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
						WHERE nodegoat_to_def_ref.object_description_id = nodegoat_to_des.id AND nodegoat_to_def_ref.ref_type_id IN (".$sql_type_ids.") AND nodegoat_to_def_ref.state = 1
					)" : "")."
			";
		}
		
		if ($model) {
			
			$version_select = self::generateVersioning($versioning, 'record', 'nodegoat_tos_def');
			
			$arr_sql[] = "SELECT nodegoat_tos_det.type_id, nodegoat_tos_des.object_sub_details_id, nodegoat_tos_des.id AS object_sub_description_id, nodegoat_tos_des.in_name, nodegoat_tos_des.in_search
					".($include_referencing ? ", nodegoat_tos_des.ref_type_id" : "")."
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos_des.object_sub_details_id)
				WHERE nodegoat_tos_des.ref_type_id IN (".$sql_type_ids.")
					".($ref_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_ref_type_ids.")" : "")."
					".($model_is_used ? "AND EXISTS (
						SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_tos_def.object_sub_description_id = nodegoat_tos_des.id AND ".$version_select."
					)" : "")."
			";
		}
		
		if ($dynamic) {
				
			$arr_sql[] = "SELECT nodegoat_tos_det.type_id, nodegoat_tos_des.object_sub_details_id, nodegoat_tos_des.id AS object_sub_description_id, nodegoat_tos_des.in_name, nodegoat_tos_des.in_search
					".($include_referencing ? ", nodegoat_t.id AS ref_type_id" : "")."
				FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DESCRIPTIONS')." nodegoat_tos_des
				JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos_des.object_sub_details_id)
				".($include_referencing ? "LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (
					nodegoat_t.id IN (".$sql_type_ids.")
					AND EXISTS (
						SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_tos_def_ref.object_sub_description_id = nodegoat_tos_des.id AND nodegoat_tos_def_ref.ref_type_id = nodegoat_t.id AND nodegoat_tos_def_ref.state = 1
					)
				)" : "")."
				WHERE (
						(nodegoat_tos_des.value_type_base = 'text_tags')
						OR (nodegoat_tos_des.value_type_base = 'reversal' AND (SELECT TRUE FROM ".DB::getTable('DEF_NODEGOAT_TYPES')." WHERE id = nodegoat_tos_des.ref_type_id AND mode = 1))
					)
					".($ref_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_ref_type_ids.")" : "")."
					".($dynamic_is_used ? "AND EXISTS (
						SELECT TRUE
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_tos_def_ref.object_sub_description_id = nodegoat_tos_des.id AND nodegoat_tos_def_ref.ref_type_id IN (".$sql_type_ids.") AND nodegoat_tos_def_ref.state = 1
					)" : "")."
			";
		}
		
		if ($object_sub_locations) {
			
			if ($model_is_used || $dynamic_is_used) {
				
				$filter = new FilterTypeObjects(0);
				$filter->setScope(['types'=> $ref_type_ids]);
				$arr_sql_location = $filter->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true, true);
				
				$version_select_to2 = self::generateVersioning($versioning, 'object', 'nodegoat_to2');
			}
			
			if ($model || $dynamic) {
				
				$arr_sql[] = "SELECT nodegoat_tos_det.type_id, nodegoat_tos_det.id AS object_sub_details_id,
						1 AS object_sub_location,
						location_ref_type_id_locked,
						CASE
							WHEN (location_use_object_sub_details_id != 0 OR location_use_object_sub_description_id != 0 OR location_use_object_description_id != 0 OR location_use_object_id = TRUE) THEN 1
							ELSE 0
						END AS location_use_other
						".($include_referencing ? ", nodegoat_tos_det.location_ref_type_id AS ref_type_id" : "")."
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
					WHERE nodegoat_tos_det.location_ref_type_id IN (".$sql_type_ids.")
						".($ref_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_ref_type_ids.")" : "")."
						".($model_is_used || $dynamic_is_used ? "AND EXISTS (
							SELECT TRUE
								FROM ".$arr_sql_location['table_name_cache']."
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = ".$arr_sql_location['table_name_cache'].".object_sub_id AND ".$version_select_tos.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
							WHERE ".$arr_sql_location['table_name_cache'].".ref_type_id IN (".$sql_type_ids.") AND ".$arr_sql_location['table_name_cache'].".object_sub_details_id = nodegoat_tos_det.id AND ".$arr_sql_location['table_cache_status']."
						)" : "")."
				";
			}
			
			if ($dynamic) {
				
				$arr_sql[] = "SELECT nodegoat_tos_det.type_id, nodegoat_tos_det.id AS object_sub_details_id,
						1 AS object_sub_location
						".($include_referencing ? ", nodegoat_t.id AS ref_type_id" : "")."
					FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." nodegoat_tos_det
					".($include_referencing ? "LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (
						nodegoat_t.id IN (".$sql_type_ids.")
						AND EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
								".($dynamic_is_used ? "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2 ON (nodegoat_to2.id = nodegoat_tos.location_ref_object_id AND ".$version_select_to2.")" : "")."
							WHERE nodegoat_tos.location_ref_type_id = nodegoat_t.id AND nodegoat_tos.object_sub_details_id = nodegoat_tos_det.id AND ".$version_select_tos."
						)
					)" : "")."
					WHERE nodegoat_tos_det.location_ref_type_id_locked = FALSE
						".($ref_type_ids ? "AND nodegoat_tos_det.type_id IN (".$sql_ref_type_ids.")" : "")."
						AND EXISTS (
							SELECT TRUE
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
								".($dynamic_is_used ? "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to2 ON (nodegoat_to2.id = nodegoat_tos.location_ref_object_id AND ".$version_select_to2.")" : "")."
							WHERE nodegoat_tos.location_ref_type_id IN (".$sql_type_ids.") AND nodegoat_tos.object_sub_details_id = nodegoat_tos_det.id AND ".$version_select_tos."
						)
				";
			}
		}
		
		$arr_res = DB::queryMulti(implode(';', $arr_sql));
		
		$arr = [];
		
		if ($include_referenced && $include_referencing) {
			
			foreach ($arr_res as $res) {
			
				while ($row = $res->fetchAssoc()) {
									
					if ($row['object_description_id']) {
						$arr[$row['type_id']][$row['ref_type_id']]['object_descriptions'][$row['object_description_id']] = $row;
					} else if ($row['object_sub_description_id']) {
						$arr[$row['type_id']][$row['ref_type_id']]['object_sub_details'][$row['object_sub_details_id']]['object_sub_descriptions'][$row['object_sub_description_id']] = $row;
					} else if ($row['object_sub_location']) {
						$arr[$row['type_id']][$row['ref_type_id']]['object_sub_details'][$row['object_sub_details_id']]['object_sub_location'] = $row;
					}
				}
			}			
		} else if ($include_referenced) {
			
			foreach ($arr_res as $res) {
			
				while ($row = $res->fetchAssoc()) {
									
					if ($row['object_description_id']) {
						$arr[$row['type_id']]['object_descriptions'][$row['object_description_id']] = $row;
					} else if ($row['object_sub_description_id']) {
						$arr[$row['type_id']]['object_sub_details'][$row['object_sub_details_id']]['object_sub_descriptions'][$row['object_sub_description_id']] = $row;
					} else if ($row['object_sub_location']) {
						$arr[$row['type_id']]['object_sub_details'][$row['object_sub_details_id']]['object_sub_location'] = $row;
					}
				}
			}			
		} else if ($include_referencing) {
			
			foreach ($arr_res as $res) {
			
				while ($row = $res->fetchAssoc()) {
									
					if ($row['object_description_id']) {
						$arr[$row['ref_type_id']]['object_descriptions'][$row['object_description_id']] = $row;
					} else if ($row['object_sub_description_id']) {
						$arr[$row['ref_type_id']]['object_sub_details'][$row['object_sub_details_id']]['object_sub_descriptions'][$row['object_sub_description_id']] = $row;
					} else if ($row['object_sub_location']) {
						$arr[$row['ref_type_id']]['object_sub_details'][$row['object_sub_details_id']]['object_sub_location'] = $row;
					}
				}
			}
		} else {
			
			foreach ($arr_res as $res) {
			
				while ($row = $res->fetchAssoc()) {
									
					if ($row['object_description_id']) {
						$arr['object_descriptions'][$row['object_description_id']] = $row;
					} else if ($row['object_sub_description_id']) {
						$arr['object_sub_details'][$row['object_sub_details_id']]['object_sub_descriptions'][$row['object_sub_description_id']] = $row;
					} else if ($row['object_sub_location']) {
						$arr['object_sub_details'][$row['object_sub_details_id']]['object_sub_location'] = $row;
					}
				}
			}
		}

		return $arr;
	}
	
	public static function getTypeObjectReferenced($object_id, $arr_ref_type_ids = [], $arr_options = []) {
				
		if ($arr_ref_type_ids) {
			$sql_type_ids = (is_array($arr_ref_type_ids) ? implode(',', $arr_ref_type_ids) : $arr_ref_type_ids);
		}
		$versioning = (isset($arr_options['versioning']) ? $arr_options['versioning'] : 'active');
		
		$arr = [];
		
		$version_select_to = self::generateVersioning($versioning, 'object', 'nodegoat_to');
		$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
		
		$version_select = self::generateVersioning($versioning, 'record', 'nodegoat_to_def');
		
		$res = DB::query("(SELECT nodegoat_to.type_id, nodegoat_to_def.object_description_id, COUNT(nodegoat_to_def.object_description_id) AS count
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
						WHERE nodegoat_to_def.ref_object_id = ".$object_id."
							".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
							AND ".$version_select."
						GROUP BY nodegoat_to.type_id, nodegoat_to_def.object_description_id
					) UNION (
						SELECT nodegoat_to.type_id, nodegoat_to_def_ref.object_description_id, COUNT(nodegoat_to_def_ref.object_description_id) AS count
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
						WHERE nodegoat_to_def_ref.ref_object_id = ".$object_id." AND nodegoat_to_def_ref.state = 1
							".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
						GROUP BY nodegoat_to.type_id, nodegoat_to_def_ref.object_description_id
					)");
					
		while ($row = $res->fetchAssoc()) {
			$arr[$row['type_id']]['object_definitions'][$row['object_description_id']] = $row;
		}
		
		$version_select = self::generateVersioning($versioning, 'record', 'nodegoat_tos_def');
		
		$res = DB::query("(SELECT nodegoat_to.type_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def.object_sub_description_id, COUNT(nodegoat_tos_def.object_sub_description_id) AS count
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_tos_def.ref_object_id = ".$object_id."
							".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
							AND ".$version_select."
						GROUP BY nodegoat_to.type_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def.object_sub_description_id
					) UNION (
						SELECT nodegoat_to.type_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def_ref.object_sub_description_id, COUNT(nodegoat_tos_def_ref.object_sub_description_id) AS count
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
						WHERE nodegoat_tos_def_ref.ref_object_id = ".$object_id." AND nodegoat_tos_def_ref.state = 1
							".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
						GROUP BY nodegoat_to.type_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def_ref.object_sub_description_id
					)");
					
		while ($row = $res->fetchAssoc()) {
			$arr[$row['type_id']]['object_subs'][$row['object_sub_details_id']]['object_sub_definitions'][$row['object_sub_description_id']] = $row;
		}
		
		$res = DB::query("SELECT nodegoat_to.type_id, nodegoat_tos.object_sub_details_id, COUNT(nodegoat_tos.id) AS count
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
			WHERE nodegoat_tos.location_ref_object_id = ".$object_id."
				".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
				AND ".$version_select_tos."
			GROUP BY nodegoat_to.type_id, nodegoat_tos.object_sub_details_id");
					
		while ($row = $res->fetchAssoc()) {
			$arr[$row['type_id']]['object_subs'][$row['object_sub_details_id']]['object_sub']['object_sub_location'] = $row;
		}
				
		$res = DB::query("
			(SELECT nodegoat_to.type_id, 0 AS object_description_id, 0 AS object_sub_details_id, 0 AS object_sub_description_id, COUNT(nodegoat_to.id) AS count
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')." nodegoat_to_src
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_src.object_id AND ".$version_select_to.")
				WHERE nodegoat_to_src.ref_object_id = ".$object_id."
					".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
				GROUP BY nodegoat_to.type_id
			) UNION (SELECT nodegoat_to.type_id, nodegoat_to_def_src.object_description_id, 0 AS object_sub_details_id, 0 AS object_sub_description_id, COUNT(nodegoat_to_def_src.object_description_id) AS count
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')." nodegoat_to_def_src
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_src.object_id AND ".$version_select_to.")
				WHERE nodegoat_to_def_src.ref_object_id = ".$object_id."
					".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
				GROUP BY nodegoat_to.type_id, nodegoat_to_def_src.object_description_id
			) UNION (SELECT nodegoat_to.type_id, 0 AS object_description_id, nodegoat_tos.object_sub_details_id, 0 AS object_sub_description_id, COUNT(nodegoat_tos.object_sub_details_id) AS count
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')." nodegoat_tos_src
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_src.object_sub_id AND ".$version_select_tos.")
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
				WHERE nodegoat_tos_src.ref_object_id = ".$object_id."
					".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
				GROUP BY nodegoat_to.type_id, nodegoat_tos.object_sub_details_id
			) UNION (SELECT nodegoat_to.type_id, 0 AS object_description_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def_src.object_sub_description_id, COUNT(nodegoat_tos_def_src.object_sub_description_id) AS count
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')." nodegoat_tos_def_src
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_src.object_sub_id AND ".$version_select_tos.")
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
				WHERE nodegoat_tos_def_src.ref_object_id = ".$object_id."
					".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
				GROUP BY nodegoat_to.type_id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def_src.object_sub_description_id
			)
		");
					
		while ($row = $res->fetchAssoc()) {
			if ($row['object_sub_description_id']) {
				$arr[$row['type_id']]['object_subs'][$row['object_sub_details_id']]['object_sub_definitions'][$row['object_sub_description_id']]['object_sub_definition_sources'] = $row;
			} else if ($row['object_sub_details_id']) {
				$arr[$row['type_id']]['object_subs'][$row['object_sub_details_id']]['object_sub']['object_sub_sources'] = $row;
			} else if ($row['object_description_id']) {
				$arr[$row['type_id']]['object_definitions'][$row['object_description_id']]['object_definition_sources'] = $row;
			} else {
				$arr[$row['type_id']]['object']['object_sources'] = $row;
			}
		}

		return $arr;
	}
	
	public static function getObjectsReferencedTypesObjects($object_id, $arr_ref_type_ids = [], $arr_options = ['model' => true, 'dynamic' => true, 'object_sub_locations' => true]) {
		
		if (is_numeric($object_id)) {
			$object_id = [$object_id];
		} else if (is_string($object_id)) {
			$table_name = $object_id;
			$object_id = false;
		}
		
		if (!$table_name && !$object_id) {
			return [];
		}
				
		if ($arr_ref_type_ids) {
			$sql_type_ids = (is_array($arr_ref_type_ids) ? implode(',', $arr_ref_type_ids) : $arr_ref_type_ids);
		}
		$model = keyIsUncontested('model', $arr_options);
		$dynamic = keyIsUncontested('dynamic', $arr_options);
		$object_sub_locations = keyIsUncontested('object_sub_locations', $arr_options);
		$versioning = (isset($arr_options['versioning']) ? $arr_options['versioning'] : 'active');
		
		$arr = [];
		
		$version_select_to = self::generateVersioning($versioning, 'object', 'nodegoat_to');
		$version_select_tos = self::generateVersioning($versioning, 'object_sub', 'nodegoat_tos');
				
		$arr_sql = [];

		if ($model) {
			
			$version_select = self::generateVersioning($versioning, 'record', 'nodegoat_to_def');
			
			$arr_sql[] = "(SELECT nodegoat_to_def.ref_object_id AS object_id, nodegoat_to.type_id, COUNT(nodegoat_to_def.object_id) AS count
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
								".($table_name ? "JOIN ".$table_name." nodegoat_to_store ON (nodegoat_to_store.id = nodegoat_to_def.ref_object_id)" : "")."
							WHERE ".$version_select."
								".($object_id ? "AND nodegoat_to_def.ref_object_id IN (".implode(',', $object_id).")" : "")."
								".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
							GROUP BY nodegoat_to_def.ref_object_id, nodegoat_to.type_id
			)";
		}
		
		if ($dynamic) {
			
			$arr_sql[] = "(SELECT nodegoat_to_def_ref.ref_object_id AS object_id, nodegoat_to.type_id, COUNT(nodegoat_to_def_ref.object_id) AS count
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def_ref.object_id AND ".$version_select_to.")
								".($table_name ? "JOIN ".$table_name." nodegoat_to_store ON (nodegoat_to_store.id = nodegoat_to_def_ref.ref_object_id)" : "")."
							WHERE nodegoat_to_def_ref.state = 1
								".($object_id ? "AND nodegoat_to_def_ref.ref_object_id IN (".implode(',', $object_id).")" : "")."
								".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
							GROUP BY nodegoat_to_def_ref.ref_object_id, nodegoat_to.type_id
			)";
		}
		
		if ($model) {
				
			$version_select = self::generateVersioning($versioning, 'record', 'nodegoat_tos_def');
			
			$arr_sql[] = "(SELECT nodegoat_tos_def.ref_object_id AS object_id, nodegoat_to.type_id, COUNT(nodegoat_tos.object_id) AS count
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
								".($table_name ? "JOIN ".$table_name." nodegoat_to_store ON (nodegoat_to_store.id = nodegoat_tos_def.ref_object_id)" : "")."
							WHERE ".$version_select."
								".($object_id ? "AND nodegoat_tos_def.ref_object_id IN (".implode(',', $object_id).")" : "")."
								".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
							GROUP BY nodegoat_tos_def.ref_object_id, nodegoat_to.type_id
			)";
		}
		
		if ($dynamic) {
			
			$arr_sql[] = "(SELECT nodegoat_tos_def_ref.ref_object_id AS object_id, nodegoat_to.type_id, COUNT(nodegoat_tos.object_id) AS count
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
							".($table_name ? "JOIN ".$table_name." nodegoat_to_store ON (nodegoat_to_store.id = nodegoat_tos_def_ref.ref_object_id)" : "")."
						WHERE nodegoat_tos_def_ref.state = 1
							".($object_id ? "AND nodegoat_tos_def_ref.ref_object_id IN (".implode(',', $object_id).")" : "")."
							".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
						GROUP BY nodegoat_tos_def_ref.ref_object_id, nodegoat_to.type_id
			)";
		}
		
		if ($object_sub_locations) {
			
			if ($model || $dynamic) {
					
				$arr_sql[] = "(SELECT nodegoat_tos.location_ref_object_id AS object_id, nodegoat_to.type_id, COUNT(nodegoat_tos.object_id) AS count
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id AND ".$version_select_to.")
									".($table_name ? "JOIN ".$table_name." nodegoat_to_store ON (nodegoat_to_store.id = nodegoat_tos.location_ref_object_id)" : "")."
								WHERE ".$version_select_tos."
									".($object_id ? "AND nodegoat_tos.location_ref_object_id IN (".implode(',', $object_id).")" : "")."
									".($arr_ref_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
								GROUP BY nodegoat_tos.location_ref_object_id, nodegoat_to.type_id
				)";
			}
		}
		
		$res = DB::query("SELECT object_id, type_id, SUM(count) AS count
							FROM (
								 ".implode(" UNION ALL ", $arr_sql)."
							) AS foo
						GROUP BY object_id, type_id
		");

		while ($row = $res->fetchAssoc()) {

			$arr[$row['object_id']][$row['type_id']] = $row;
		}

		return $arr;
	}
	
	public static function getTypesObjectsByObjectDescriptions($identifier, $arr_type_object_descriptions) {
		
		$arr_collect_object_description_ids = [];
		
		foreach ($arr_type_object_descriptions as $type_id => $arr_object_description_ids) {
			$arr_collect_object_description_ids += $arr_object_description_ids;
		}
		
		$sql_type_ids = implode(',', array_keys($arr_type_object_descriptions));
		$sql_object_description_ids = implode(',', $arr_collect_object_description_ids);
		
		$version_select = self::generateVersioning('active', 'record', 'nodegoat_to_def');
		$version_select_to = self::generateVersioning('active', 'object', 'nodegoat_to');
		
		$res = DB::query("SELECT
			DISTINCT nodegoat_to_def.object_id AS object_id, nodegoat_to.type_id
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS')." nodegoat_to_def
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_def.object_id AND ".$version_select_to.")
			WHERE nodegoat_to_def.object_description_id IN (".$sql_object_description_ids.")
				AND nodegoat_to.type_id IN (".$sql_type_ids.")
				AND nodegoat_to_def.value = '".DBFunctions::strEscape($identifier)."'
				AND ".$version_select."
		");
		
		while ($row = $res->fetchAssoc()) {

			$arr[$row['type_id']][$row['object_id']] = $row['object_id'];
		}

		return $arr;
	}
	
	public static function getTypesUpdatedSince($date, $arr_type_ids = [], $peek = false) {
		
		// Use $peek to have a quick peek if anything has been updated. Use $peek = 'date' to get last update date
		
		$date = str2SQlDate($date);
		if ($arr_type_ids) {
			$sql_type_ids = (is_array($arr_type_ids) ? implode(',', $arr_type_ids) : $arr_type_ids);
		}
		
		$arr = [];
		
		if ($peek) {
			
			$res = DB::query("SELECT
				".($peek === 'date' ? "MAX(nodegoat_to_date.date) AS date" : "TRUE")."
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DATING')." nodegoat_to_date ON (nodegoat_to_date.object_id = nodegoat_to.id)
				WHERE nodegoat_to_date.date >= '".$date."'
					".($arr_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
				LIMIT 1
			");
			
			if (!$res->getRowCount()) {
				
				return false;
			}
			
			if ($peek === 'date') {
				
				$row = $res->fetchAssoc();
				return $row['date'];
			} else {
				
				return true;
			}
		} else {
			
			$res = DB::query("SELECT
				nodegoat_to.type_id, MAX(nodegoat_to_date.date) AS date
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DATING')." nodegoat_to_date ON (nodegoat_to_date.object_id = nodegoat_to.id)
				WHERE nodegoat_to_date.date >= '".$date."'
					".($arr_type_ids ? "AND nodegoat_to.type_id IN (".$sql_type_ids.")" : "")."
				GROUP BY nodegoat_to.type_id
			");
			
			while ($row = $res->fetchAssoc()) {
				
				$arr[$row['type_id']] = $row;
			}
		}
		
		return $arr;
	}
	
	public static function getTypesAnalysesUpdatedSince($date, $arr_ids) {
				
		$date = str2SQlDate($date);
		
		$arr_sql_ids = [];
		
		foreach ($arr_ids as $arr_id) {
						
			$arr_sql_ids[] = "(nodegoat_to_an_stat.user_id = ".(int)($arr_id['user_id'] ?: 0)." AND nodegoat_to_an_stat.analysis_id = ".(int)$arr_id['analysis_id'].")";
		}
		
		if (!$arr_sql_ids) {
			
			return false;
		}
		
		$res = DB::query("SELECT
			TRUE
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSIS_STATUS')." nodegoat_to_an_stat
			WHERE nodegoat_to_an_stat.date >= '".$date."'
				AND (".implode(' OR ', $arr_sql_ids).")
			LIMIT 1
		");
		
		if (!$res->getRowCount()) {
			
			return false;
		}

		return true;
	}

	public static function cleanupFilterForm($type_id, $arr_filter, $arr_options) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$arr_filter['type_id'] = (int)$arr_filter['type_id'];
		
		if ($arr_filter['source'] && $arr_filter['source']['filter_code']) {
			
			$arr_filter['source'] = [
				'filter_code' => $arr_filter['source']['filter_code'],
				'filter_beacon' => $arr_filter['source']['filter_beacon'],
				'type_id' => (int)$arr_filter['source']['type_id'],
				'object_description_id' => (int)$arr_filter['source']['object_description_id'],
				'object_sub_details_id' => (int)$arr_filter['source']['object_sub_details_id'],
				'object_sub_description_id' => (!$arr_filter['source']['object_sub_description_id'] || is_numeric($arr_filter['source']['object_sub_description_id']) ? (int)$arr_filter['source']['object_sub_description_id'] : $arr_filter['source']['object_sub_description_id']),
				'direction' => $arr_filter['source']['direction'],
			];
		} else {
			
			$arr_filter['source'] = null;
		}
		
		// Options
		if ($arr_filter['options']['exclude'] && $arr_filter['options']['exclude'] == '1') {
			$arr_filter['options']['exclude'] = 'soft';
		}
		
		// Objects
		if ($arr_filter['objects']) {
			foreach ($arr_filter['objects'] as $key => $value) {

				if ((int)$value) {
					continue;
				}
				unset($arr_filter['objects'][$key]);
			}
			
			$arr_filter['objects'] = array_values($arr_filter['objects']); // Cleanup array keys
		}
		
		if (!$arr_filter['objects']) {
			unset($arr_filter['objects']);
		}
		
		// Object name
		if ($arr_filter['object_name']) {
			
			foreach ($arr_filter['object_name'] as $key => $value) {
				
				$value = (is_array($value) ? $value['value'] : $value); // Account for complex filter values (i.e. using equality)
				
				if ($value) {
					continue;
				}
				unset($arr_filter['object_name'][$key]);
			}
			
			$arr_filter['object_name'] = array_values($arr_filter['object_name']); // Cleanup array keys
		}
		
		if (!$arr_filter['object_name']) {
			unset($arr_filter['object_name']);
		}
		
		// Object analysis
		if ($arr_filter['object_analyses']) {
			
			foreach ($arr_filter['object_analyses'] as $key => &$arr_filter_object_analysis) {
				
				$arr_filter_object_analysis['number'] = current(self::cleanupFilterFormTypeValues('float', [$arr_filter_object_analysis['number']], false, $arr_options));
				
				if ($arr_filter_object_analysis['number_secondary']) {
					$arr_filter_object_analysis['number_secondary'] = current(self::cleanupFilterFormTypeValues('float', [$arr_filter_object_analysis['number_secondary']], false, $arr_options));
				}
				
				if (!$arr_filter_object_analysis['object_analysis_id'] || (!$arr_filter_object_analysis['number'] && !$arr_filter_object_analysis['number_secondary'])) {
					unset($arr_filter['object_analyses'][$key]);
				}
			}
			unset($arr_filter_object_analysis);
			
			$arr_filter['object_analyses'] = array_values($arr_filter['object_analyses']); // Cleanup array keys
		}
		
		if (!$arr_filter['object_analyses']) {
			unset($arr_filter['object_analyses']);
		}
						
		// Object definitions
		if ($arr_filter['object_definitions']) {
			foreach ($arr_filter['object_definitions'] as $object_description_id => &$arr_definitions) {
				
				if (!$arr_type_set['object_descriptions'][$object_description_id]) {
					unset($arr_filter['object_definitions'][$object_description_id]);
					continue;
				}
				
				$arr_definitions = self::cleanupFilterFormTypeValues($arr_type_set['object_descriptions'][$object_description_id]['object_description_value_type'], $arr_definitions, false, $arr_options);
				
				if (!$arr_filter['object_definitions'][$object_description_id]) {
					unset($arr_filter['object_definitions'][$object_description_id]);
				}
			}
			unset($arr_definitions);
		}
		
		if (!$arr_filter['object_definitions']) {
			unset($arr_filter['object_definitions']);
		}
		
		// Sub-object
		if ($arr_filter['object_subs']) {
			foreach ($arr_filter['object_subs'] as $object_sub_details_id => &$arr_object_sub) {
				
				$arr_object_sub_details = $arr_type_set['object_sub_details'][$object_sub_details_id];
				
				if ($object_sub_details_id && !$arr_object_sub_details) { // $object_sub_details_id = 0 when general sub-object filter
					unset($arr_filter['object_subs'][$object_sub_details_id]);
					continue;
				}
									
				// Sub-object definitions
				if ($arr_object_sub['object_sub_definitions']) {
					foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => &$arr_sub_definitions) {
						
						if (!$arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id]) {
							unset($arr_object_sub['object_sub_details'][$object_sub_details_id]['object_sub_definitions'][$object_sub_description_id]);
							continue;
						}
						
						$arr_sub_definitions = self::cleanupFilterFormTypeValues($arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_value_type'], $arr_sub_definitions, false, $arr_options);
						
						if (!$arr_object_sub['object_sub_definitions'][$object_sub_description_id]) {
							unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
						}
					}
				}
				
				if (!$arr_object_sub['object_sub_definitions']) {
					unset($arr_object_sub['object_sub_definitions']);
				}
				
				// Sub-object date
				$legacy_date_start = $arr_object_sub['object_sub']['object_sub_date_start'];
				$legacy_date_end = $arr_object_sub['object_sub']['object_sub_date_end'];
				if ($legacy_date_start || $legacy_date_end) { // Legacy
					
					/*if ($legacy_date_start) {
						$arr_date_start = array('equality' => '≥', 'value' => $legacy_date_start);
					}
					
					if ($legacy_date_end) {
						if ($arr_object_sub_details && !$arr_object_sub_details['object_sub_details']['object_sub_details_is_date_range']) {
							if ($arr_date_start) {
								$arr_date_start['equality'] = '≥≤';
								$arr_date_start['range'] = $legacy_date_end;
							} else {
								$arr_date_start = array('equality' => '≤', 'value' => $legacy_date_end);
							}
						} else {
							$arr_date_end = array('equality' => '≤', 'value' => $legacy_date_end);
						}
					}*/
					
					$arr_object_sub['object_sub_dates'][] = ['object_sub_date_from' => $legacy_date_start, 'object_sub_date_to' => $legacy_date_end];
					
					unset($arr_object_sub['object_sub']['object_sub_date_start'], $arr_object_sub['object_sub']['object_sub_date_end']);
				}
				
				if ($arr_object_sub['object_sub_dates']) {
					
					foreach ($arr_object_sub['object_sub_dates'] as $key => &$arr_object_sub_date) {
						
						if ($arr_object_sub_date['object_sub_date_start']) {
							$arr_object_sub_date['object_sub_date_start'] = current(self::cleanupFilterFormTypeValues('date', [$arr_object_sub_date['object_sub_date_start']], false, $arr_options));
						}
						if ($arr_object_sub_date['object_sub_date_end']) {
							$arr_object_sub_date['object_sub_date_end'] = current(self::cleanupFilterFormTypeValues('date', [$arr_object_sub_date['object_sub_date_end']], false, $arr_options));
						}
						
						if (!$arr_object_sub_date['object_sub_date_value']['transcension'] || $arr_object_sub_date['object_sub_date_value']['transcension']['value'] == 'any') {
							unset($arr_object_sub_date['object_sub_date_value']);
						}
						
						if ($arr_object_sub_date['object_sub_date_start'] || $arr_object_sub_date['object_sub_date_end'] || $arr_object_sub_date['object_sub_date_value']['transcension']) {
							unset($arr_object_sub_date['object_sub_date_from'], $arr_object_sub_date['object_sub_date_to']);
							if (!$arr_object_sub_date['object_sub_date_start']) {
								unset($arr_object_sub_date['object_sub_date_start']);
							}
							if (!$arr_object_sub_date['object_sub_date_end']) {
								unset($arr_object_sub_date['object_sub_date_end']);
							}
							$arr_object_sub_date['object_sub_date_type'] = 'point';
						} else {
							unset($arr_object_sub_date['object_sub_date_start'], $arr_object_sub_date['object_sub_date_end']);
							if (!StoreTypeObjects::date2Int($arr_object_sub_date['object_sub_date_from'])) {
								unset($arr_object_sub_date['object_sub_date_from']);
							}
							if (!StoreTypeObjects::date2Int($arr_object_sub_date['object_sub_date_to'])) {
								unset($arr_object_sub_date['object_sub_date_to']);
							}
							$arr_object_sub_date['object_sub_date_type'] = 'range';
						}
						
						if (!$arr_object_sub_date['object_sub_date_start'] && !$arr_object_sub_date['object_sub_date_end'] && !$arr_object_sub_date['object_sub_date_from'] && !$arr_object_sub_date['object_sub_date_to'] && !$arr_object_sub_date['object_sub_date_value']) {
							unset($arr_object_sub['object_sub_dates'][$key]);
						}
					}
					unset($arr_object_sub_date);
					
					$arr_object_sub['object_sub_dates'] = array_values($arr_object_sub['object_sub_dates']); // Cleanup array keys
				}
				
				if (!$arr_object_sub['object_sub_dates']) {
					unset($arr_object_sub['object_sub_dates']);
				}
										
				// Sub-object location
				if ($arr_object_sub['object_sub_locations']) {
					
					foreach ($arr_object_sub['object_sub_locations'] as $key => &$arr_object_sub_location) {
						
						if (!$arr_object_sub_location['object_sub_location_value']['transcension'] || $arr_object_sub_location['object_sub_location_value']['transcension']['value'] == 'any') {
							unset($arr_object_sub_location['object_sub_location_value']['transcension']);
						}
						
						if ($arr_object_sub_location['object_sub_location_value']['transcension']) {
							unset($arr_object_sub_location['object_sub_location_reference'], $arr_object_sub_location['object_sub_location_latitude'], $arr_object_sub_location['object_sub_location_longitude'], $arr_object_sub_location['object_sub_location_geometry'], $arr_object_sub_location['object_sub_location_ref_type_id'], $arr_object_sub_location['object_sub_location_ref_object_sub_details_id']);
						} else if ($arr_object_sub_location['object_sub_location_latitude'] || $arr_object_sub_location['object_sub_location_longitude']) {
							unset($arr_object_sub_location['object_sub_location_reference'], $arr_object_sub_location['object_sub_location_geometry'], $arr_object_sub_location['object_sub_location_ref_type_id'], $arr_object_sub_location['object_sub_location_ref_object_sub_details_id']);
						} else if ($arr_object_sub_location['object_sub_location_geometry']) {
							unset($arr_object_sub_location['object_sub_location_reference'], $arr_object_sub_location['object_sub_location_latitude'], $arr_object_sub_location['object_sub_location_longitude'], $arr_object_sub_location['object_sub_location_ref_type_id'], $arr_object_sub_location['object_sub_location_ref_object_sub_details_id']);
						} else {
							unset($arr_object_sub_location['object_sub_location_geometry'], $arr_object_sub_location['object_sub_location_latitude'], $arr_object_sub_location['object_sub_location_longitude'], $arr_object_sub_location['object_sub_location_value']);
						}
						
						if ($arr_object_sub_location['object_sub_location_reference']) {
							
							$arr_object_sub_location['object_sub_location_type'] = 'reference';
							
							$arr_object_sub_location['object_sub_location_ref_type_id'] = (int)$arr_object_sub_location['object_sub_location_ref_type_id'];
							$arr_object_sub_location['object_sub_location_ref_object_sub_details_id'] = (int)$arr_object_sub_location['object_sub_location_ref_object_sub_details_id'];
							
							$arr_object_sub_location['object_sub_location_reference'] = self::cleanupFilterFormTypeValues('type', $arr_object_sub_location['object_sub_location_reference'], false, $arr_options);
						} else if ($arr_object_sub_location['object_sub_location_geometry'] || $arr_object_sub_location['object_sub_location_value']['transcension']) {
							
							$arr_object_sub_location['object_sub_location_type'] = 'geometry';
							
							$arr_object_sub_location['object_sub_location_geometry'] = ($arr_object_sub_location['object_sub_location_geometry'] ? current(self::cleanupFilterFormTypeValues('geometry', [$arr_object_sub_location['object_sub_location_geometry']], false, $arr_options)) : '');
							
							if (!$arr_object_sub_location['object_sub_location_value']['transcension']) {
								unset($arr_object_sub_location['object_sub_location_value']);
							} else {
								unset($arr_object_sub_location['object_sub_location_value']['radius']);
							}
						} else if ($arr_object_sub_location['object_sub_location_latitude'] || $arr_object_sub_location['object_sub_location_longitude']) {
							
							$arr_object_sub_location['object_sub_location_type'] = 'point';
							
							$arr_object_sub_location['object_sub_location_latitude'] = (float)$arr_object_sub_location['object_sub_location_latitude'];
							$arr_object_sub_location['object_sub_location_longitude'] = (float)$arr_object_sub_location['object_sub_location_longitude'];
							
							if (!$arr_object_sub_location['object_sub_location_value']['radius'] || !$arr_object_sub_location['object_sub_location_latitude'] || !$arr_object_sub_location['object_sub_location_longitude']) {
								unset($arr_object_sub_location['object_sub_location_value']['radius']);
							} else {
								$arr_object_sub_location['object_sub_location_value']['radius'] = (int)$arr_object_sub_location['object_sub_location_value']['radius'];
							}

							if (!$arr_object_sub_location['object_sub_location_value']['radius']) {
								unset($arr_object_sub_location['object_sub_location_value']);
							} else {
								unset($arr_object_sub_location['object_sub_location_value']['transcension']);
							}
						}
						
						if (!$arr_object_sub_location['object_sub_location_latitude'] && !$arr_object_sub_location['object_sub_location_value'] && !$arr_object_sub_location['object_sub_location_geometry'] && !$arr_object_sub_location['object_sub_location_reference']) {
							unset($arr_object_sub['object_sub_locations'][$key]);
						}
					}
					unset($arr_object_sub_location);
					
					$arr_object_sub['object_sub_locations'] = array_values($arr_object_sub['object_sub_locations']); // Cleanup array keys
				}
				
				if (!$arr_object_sub['object_sub_locations']) {
					unset($arr_object_sub['object_sub_locations']);
				}
				
				// Sub-object relationality
				if (!is_numeric($arr_object_sub['object_sub']['relationality']['value'])) {
					unset($arr_object_sub['object_sub']['relationality']);
				} else {
					$arr_object_sub['object_sub']['relationality']['value'] = (int)$arr_object_sub['object_sub']['relationality']['value'];
				}
				
				if (!$arr_object_sub['object_sub']['relationality']) {
					unset($arr_object_sub['object_sub']);
				}

				if (!$arr_object_sub['object_sub'] && !$arr_object_sub['object_sub_definitions'] && !$arr_object_sub['object_sub_dates'] && !$arr_object_sub['object_sub_locations']) {
					unset($arr_filter['object_subs'][$object_sub_details_id]);
				}
			}
		}
		
		if (!$arr_filter['object_subs']) {
			unset($arr_filter['object_subs']);
		}
		
		// Referenced
		if ($arr_filter['referenced_any']) {
			
			if (!is_numeric($arr_filter['referenced_any']['relationality']['value'])) {
				unset($arr_filter['referenced_any']['relationality']);
			} else {
				$arr_filter['referenced_any']['relationality']['value'] = (int)$arr_filter['referenced_any']['relationality']['value'];
			}
		}
		
		if (!$arr_filter['referenced_any']['from']['any'] && !$arr_filter['referenced_any']['from']['object_definition'] && !$arr_filter['referenced_any']['from']['object_sub_definition'] && !$arr_filter['referenced_any']['from']['object_sub_location_reference']) {
			unset($arr_filter['referenced_any']);
		}
		
		// Type referenced
		if ($arr_filter['referenced_types']) {
			
			foreach ($arr_filter['referenced_types'] as $ref_type_id => &$arr_reference_filter) {
				
				if ($arr_reference_filter['any']) {
					
					foreach ($arr_reference_filter['any'] as $key => $value) {
						
						if ($key === 'relationality') {
							if (!is_numeric($value['value'])) {
								unset($arr_reference_filter['any'][$key]);
							} else {
								$arr_reference_filter['any'][$key]['value'] = (int)$value['value'];
							}
							continue;
						}
						if ($key === 'from') {
							if (!$value['any'] && !$value['object_definition'] && !$value['object_sub_definition'] && !$value['object_sub_location_reference']) {
								unset($arr_reference_filter['any'][$key]);
							}
							continue;
						}
					}
				}
				
				if (!$arr_reference_filter['any']['from']) {
					unset($arr_reference_filter['any']);
				}

				$arr_ref_type_set = StoreType::getTypeSet($ref_type_id);
			
				// Object definitions
				if ($arr_reference_filter['object_definitions']) {
					
					foreach ($arr_reference_filter['object_definitions'] as $object_description_id => &$arr_definitions) {
						
						if (!$arr_ref_type_set['object_descriptions'][$object_description_id]) {
							unset($arr_reference_filter['object_definitions'][$object_description_id]);
							continue;
						}
						
						$arr_definitions = self::cleanupFilterFormTypeValues($arr_ref_type_set['object_descriptions'][$object_description_id]['object_description_value_type'], $arr_definitions, true, $arr_options);
																
						if (!$arr_reference_filter['object_definitions'][$object_description_id]) {
							unset($arr_reference_filter['object_definitions'][$object_description_id]);
						}
					}
				}
				
				if (!$arr_reference_filter['object_definitions']) {
					unset($arr_reference_filter['object_definitions']);
				}
				
				// Sub-objects
				if ($arr_reference_filter['object_subs']) {
					
					foreach ($arr_reference_filter['object_subs'] as $object_sub_details_id => &$arr_object_sub) {
						
						if (!$arr_ref_type_set['object_sub_details'][$object_sub_details_id]) {
							unset($arr_reference_filter['object_subs'][$object_sub_details_id]);
							continue;
						}
						
						// Sub-object location reference
						if ($arr_object_sub['object_sub_location_reference']) {
							
							$arr_object_sub['object_sub_location_reference'] = self::cleanupFilterFormTypeValues('type', $arr_object_sub['object_sub_location_reference'], true, $arr_options);
									
							if (!$arr_object_sub['object_sub_location_reference']) {
								unset($arr_object_sub['object_sub_location_reference']);
							}
						}
											
						// Sub-object definitions
						if ($arr_object_sub['object_sub_definitions']) {
							
							foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => &$arr_sub_definitions) {
								
								if (!$arr_ref_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]) {
									unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
									continue;
								}
								
								$arr_sub_definitions = self::cleanupFilterFormTypeValues($arr_ref_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_value_type'], $arr_sub_definitions, true, $arr_options);
								
								if (!$arr_object_sub['object_sub_definitions'][$object_sub_description_id]) {
									unset($arr_object_sub['object_sub_definitions'][$object_sub_description_id]);
								}
							}
						}
						
						if (!$arr_object_sub['object_sub_definitions'] && !$arr_object_sub['object_sub_location_reference']) {
							unset($arr_reference_filter['object_subs'][$object_sub_details_id]);
						}
					}
				}
				
				if (!$arr_reference_filter['object_subs']) {
					unset($arr_reference_filter['object_subs']);
				}
				
				if (!$arr_filter['referenced_types'][$ref_type_id]) {
					unset($arr_filter['referenced_types'][$ref_type_id]);
				}
			}
		}
		
		if (!$arr_filter['referenced_types']) {
			unset($arr_filter['referenced_types']);
		}
			
		return $arr_filter;
	}
	
	private static function cleanupFilterFormTypeValues($type, $arr_values, $referenced = false, $arr_options) {
		
		switch ($type) {
			case 'text_tags':
			case 'reversed_collection':
			
				if ($referenced) {
					
					if ($arr_values['objects']) {
						$arr_values['objects'] = self::cleanupFilterFormTypeValuesArr('object_id', $arr_values['objects'], $arr_options);
					}
					if (!$arr_values['objects']) {
						unset($arr_values['objects']);
					}
					if ($arr_values['values']) {
						$arr_values['values'] = self::cleanupFilterFormTypeValuesArr('', $arr_values['values'], $arr_options);
					}
					if (!$arr_values['values']) {
						unset($arr_values['values']);
					}
				} else {
					
					if ($arr_values['text']) {
						$arr_values['text'] = self::cleanupFilterFormTypeValuesArr('text', $arr_values['text'], $arr_options);
					}
					if (!$arr_values['text']) {
						unset($arr_values['text']);
					}
					if ($arr_values['type_tags']) {
						
						$arr_type_tags = [];
						foreach ($arr_values['type_tags'] as $type_id => $value) {
							$type_id = (int)($value['type_id'] ?: $type_id);
							if (!$arr_type_tags[$type_id]['values']) {
								$arr_type_tags[$type_id]['values'] = [];
							}
							if (!$arr_type_tags[$type_id]['objects']) {
								$arr_type_tags[$type_id]['objects'] = [];
							}
							if ($value['values']) {
								$arr_type_tags[$type_id]['values'] += self::cleanupFilterFormTypeValuesArr('', $value['values'], $arr_options);
							}
							if ($value['objects']) {
								$arr_type_tags[$type_id]['objects'] += self::cleanupFilterFormTypeValuesArr('object_id', $value['objects'], $arr_options);
							}
						}
						
						$arr_values['type_tags'] = $arr_type_tags;
					}
					if (!$arr_values['type_tags']) {
						unset($arr_values['type_tags']);
					}
				}
				
				if (!$arr_values) {
					$arr_values = []; // Means: unset me
				}
			break;
			default:
				$arr_values = self::cleanupFilterFormTypeValuesArr($type, $arr_values, $arr_options);
		}
		
		return $arr_values;
	}
	
	private static function cleanupFilterFormTypeValuesArr($type, $arr_values, $arr_options) {
		
		$arr_clean = [];
		
		foreach ($arr_values as $key => $value) {
			
			$complex = false;
			
			if (is_array($value)) { // Account for complex filter values (i.e. using equality)
				$use_value = $value['value'];
				$complex = true;
			} else {
				$use_value = $value;
			}
			
			if ($key === 'relationality') {
				if (is_numeric($use_value)) {
					if ($complex) {
						$value['value'] = (int)$value['value'];
					} else {
						$value = (int)$value;
					}
					$arr_clean[$key] = $value;
				}
				continue;
			}
			if ($key === 'transcension') {
				if ($use_value != 'any') {
					$arr_clean[$key] = $value;
				}
				continue;
			}
			if ($key === 'mode') {
				if ($use_value != 'default') {
					$arr_clean[$key] = $value;
				}
				continue;
			}
			if ($key === 'radius') {
				if (is_numeric($use_value)) {
					$arr_clean[$key] = (int)$use_value;
				}
				continue;
			}
			if ($key === 'beacon') {
				if ($arr_options['beacons'][$use_value]) { // Keep beacon as value when there are more values than the beacon itself
					$arr_clean[$key] = $value;
					unset($arr_options['beacons'][$use_value]);
				}
				continue;
			}
			
			if ($type == 'boolean') {
				if ($value !== '' && $value !== null) {
					$arr_clean[] = $value;
				}
			} else if ($type == 'int') {
				if ((int)$use_value) {
					if ($complex) {
						$value['value'] = (int)$value['value'];
					} else {
						$value = (int)$value;
					}
					$arr_clean[] = $value;
				}
			} else if ($type == 'float') {
				if (is_numeric($use_value)) {
					if ($complex) {
						$value['value'] = (float)$value['value'];
					} else {
						$value = (float)$value;
					}
					$arr_clean[] = $value;
				}
			} else if ($type == 'date') {
				if ($value['value_now']) {
					$value['value'] = $use_value = 'now';
				}
				if ($value['range_now']) {
					$value['range'] = 'now';
				}
				unset($value['value_now'], $value['range_now']);
				if (StoreTypeObjects::date2Int($use_value)) {
					$arr_clean[] = $value;
				}
			} else if ($type == 'geometry') {
				$value = StoreTypeObjects::formatToSQLGeometry($value);
				if ($value) {
					$arr_clean[] = $value;
				}
			} else if ($type == 'object_id' || $type == 'type' || $type == 'classification' || $type == 'reversed_classification' || $type == 'reversed_collection') {
				if ((int)$value) {
					$arr_clean[] = (int)$value;
				}
			} else if ($use_value) {
				$arr_clean[] = $value;
			}
		}
		
		return $arr_clean;
	}
	
	public static function getEqualityValues() {
		
		return [
			'=' => ['id' => '=', 'name' => '='],
			'≠' => ['id' => '≠', 'name' => '≠'],
			'<' => ['id' => '<', 'name' => '<'],
			'>' => ['id' => '>', 'name' => '>'],
			'≤' => ['id' => '≤', 'name' => '≤'],
			'≥' => ['id' => '≥', 'name' => '≥'],
			'><' => ['id' => '><', 'name' => '> a < b'],
			'≥≤' => ['id' => '≥≤', 'name' => '≥ a ≤ b'],
		];
	}
	
	public static function getEqualityStringValues() {
		
		return [
			'*' => ['id' => '*', 'name' => '*'],
			'^' => ['id' => '^', 'name' => 'a*'],
			'$' => ['id' => '$', 'name' => '*a'],
			'=' => ['id' => '=', 'name' => '='],
			'≠' => ['id' => '≠', 'name' => '≠']
		];
	}

	public static function getTranscensionValues() {
		
		return [
			'any' => ['id' => 'any', 'name' => getLabel('lbl_any')],
			'not_empty' => ['id' => 'not_empty', 'name' => getLabel('lbl_not_empty')],
			'empty' => ['id' => 'empty', 'name' => getLabel('lbl_empty')]
		];
	}
	
	public static function getReferenceSourceValues($arr_select = []) {
		
		$arr = [];
		
		if (!$arr_select || count($arr_select) > 1 || $arr_select['any']) {
			$arr['any'] = ['id' => 'any', 'name' => getLabel('lbl_any')];
		}
		if (!$arr_select || $arr_select['object_definition']) {
			$arr['object_definition'] = ['id' => 'object_definition', 'name' => getLabel('lbl_object').' '.getLabel('lbl_descriptions')];
		}
		if (!$arr_select || $arr_select['object_sub_definition']) {
			$arr['object_sub_definition'] = ['id' => 'object_sub_definition', 'name' => getLabel('lbl_object_sub').' '.getLabel('lbl_descriptions')];
		}
		if (!$arr_select || $arr_select['object_sub_location_reference']) {
			$arr['object_sub_location_reference'] = ['id' => 'object_sub_location_reference', 'name' => getLabel('lbl_object_sub').' '.getLabel('lbl_locations')];
		}
		
		return $arr;
	}
	
	public static function format2SQLDateRange($date_start, $date_end, $column_name) {
		
		$date_start = str2SQlDate($date_start);
		$date_end = str2SQlDate($date_end);
		
		if ($date_start && $date_end) {
			$sql = $column_name." BETWEEN '".$date_start."' AND '".$date_end."'";
		} else if ($date_start) {
			$sql = $column_name." >= '".$date_start."'";
		} else if ($date_end) {
			$sql = $column_name." <= '".$date_end."'";
		}
		
		return $sql;
	}
	
	public static function format2SQLDateIntMatch($source_date_start, $source_date_end, $column_name_date_match_start, $column_name_date_match_end) {
		
		if (is_numeric($source_date_start) || is_numeric($source_date_end)) {
			if ($source_date_start && $source_date_end) {
				$sql = "(".$column_name_date_match_start." BETWEEN ".(int)$source_date_start." AND ".(int)$source_date_end.") OR (".$column_name_date_match_end." BETWEEN ".(int)$source_date_start." AND ".(int)$source_date_end.") OR (".$column_name_date_match_start." < ".(int)$source_date_start." AND ".$column_name_date_match_end." > ".(int)$source_date_end.")";
			} else if ($source_date_start) {
				$sql = $column_name_date_match_end." >= ".(int)$source_date_start."";
			} else if ($source_date_end) {
				$sql = $column_name_date_match_start." <= ".(int)$source_date_end."";
			}
		} else {
			if ($source_date_start && $source_date_end) {
				$sql = "(".$column_name_date_match_start." BETWEEN ".$source_date_start." AND ".$source_date_end.") OR (".$column_name_date_match_end." BETWEEN ".$source_date_start." AND ".$source_date_end.") OR (".$column_name_date_match_start." < ".$source_date_start." AND ".$column_name_date_match_end." > ".$source_date_end.")";
			} else if ($source_date_start) {
				$sql = $column_name_date_match_end." >= ".$source_date_start."";
			} else if ($source_date_end) {
				$sql = $column_name_date_match_start." <= ".$source_date_end."";
			}
		}
		
		return "(".$sql.")";
	}
}
