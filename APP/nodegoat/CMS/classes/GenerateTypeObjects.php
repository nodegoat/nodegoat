<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class GenerateTypeObjects {
	
	protected static $static_settings_used = true;
	protected static $arr_storage_tables = [];

	protected $type_id = false;
	protected $view = false;
	protected $is_user = false;
	protected $arr_filter_object_sub_details_ids = [];
	protected $table_name_all = '';
	protected $table_name_all_conditions = '';
	protected $table_name = '';
	protected $table_name_objects = '';
	protected $is_stored = false;
	protected $is_temporary = false;
	protected $is_generating = false;
	protected $nr_limit_generate = 0;
	protected $nr_offset_generate = 0;
	
	protected $query_object = [];
	protected $query_object_sources = [];
	protected $query_object_analyses = [];
	protected $query_object_descriptions = [];
	protected $query_object_subs = [];
	
	protected $arr_type_set = [];
	protected $arr_type_set_object_sub_details_ids = [];
	protected $arr_type_set_conditions = [];
	
	protected $arr_columns_object = [];
	protected $arr_columns_object_as = [];
	protected $arr_columns_object_group = [];
	protected $arr_columns_object_conditions_values = [];
	protected $arr_columns_object_conditions = [];
	protected $arr_columns_subs = [];
	protected $arr_columns_subs_as = [];
	protected $arr_columns_subs_object_conditions_values = [];
	protected $arr_columns_object_descriptions = [];
	protected $arr_columns_subs_descriptions = [];
	protected $arr_columns_subs_group = [];
	protected $arr_column_purpose = [];
	protected $arr_tables = [];
	protected $count_object_descriptions = 0;
	protected $count_object_sub_descriptions = [];
	protected $arr_object_conditions = [];
	protected $arr_object_conditions_name = [];
	
	protected $arr_combine_filters = [];
	protected $arr_depth = [];
	protected $arr_order = [];
	protected $arr_order_object_subs = [];
	protected $arr_order_object_subs_use_object_description_id = [];
	protected $arr_limit = [];
	protected $arr_limit_object_subs = [];
	protected $arr_selection = [];
	protected $arr_selection_object_sub_details_ids = [];
	protected $arr_filtering = [];
	protected $arr_filtering_enable = [];
	protected $arr_filtering_filters = [];
	protected $table_name_filtering = false;
	protected $versioning = false;
	protected $conditions = false;
	protected $differentiation_id = false;
	
	protected $arr_sql_order = [];
	protected $arr_sql_group = [];
	protected $arr_sql_order_subs = [];
	protected $arr_sql_filter = [];
	protected $arr_sql_filter_purpose = [];
	protected $arr_sql_filter_general_purpose = [];
	protected $arr_sql_filter_subs = [];
	protected $arr_sql_filter_subs_purpose = [];
	protected $sql_limit = false;
	protected $arr_sql_pre_queries = [];
	protected $arr_sql_pre_settings = [];
	
	protected $arr_scope = [];
	protected $arr_types_found = [];
	
	protected $sql_tables_columns_generated = false;
	protected $settings_used = false;
	
	protected $arr_type_object_name_object_description_ids = [];
	protected $arr_type_object_name_object_sub_description_ids = [];
	protected $arr_type_object_names = [];
	
	public static $keep_tables = false;
	
	protected static $arr_pre_run = [];
	protected static $sql_separator_group = '$|$';
	protected static $sql_separator_group2 = '$||$';
	protected static $sql_no_value = 'X';
	protected static $sql_separator_analysis = ' | ';
	
	protected static $table_name_shared_type_object_names = '';
	protected static $func_shared_type_set_conditions_name = false;
	protected static $arr_shared_type_set_conditions_name = [];
	protected static $arr_shared_type_object_names = [];
	protected static $do_check_shared_type_object_names = false;
	protected static $is_active_shared_type_object_names = false;
	protected static $num_shared_type_object_name_depth = 5;
	protected static $num_shared_type_object_names_cache = 10000;
	protected static $do_clear_shared_type_object_names = true;
	
	public static function useStaticSettings() {
		
		if (!self::$static_settings_used) {
			return;
		}
		
		self::$static_settings_used = false;
		
		self::$table_name_shared_type_object_names = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_share_to_names');
		
		DB::query("
			CREATE TEMPORARY TABLE ".self::$table_name_shared_type_object_names." (
				type_id INT,
				id INT,
				state SMALLINT,
					PRIMARY KEY(type_id, id)
			) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
		"); // An additional index on 'state' will be created later in the process
		
		Response::addParse(function($str) {
			
			return self::printSharedTypeObjectNames($str, true);
		});
		
		Mediator::attach('cleanup.program', false, function() {
			
			if (self::$keep_tables) {
				return;
			}
			
			// Clean a possible leftover of the non-temporary tables at 1100CC shutdown.
			DB::setDatabase(DATABASE_NODEGOAT_TEMP); // Make sure the correct database is selected
			self::cleanupResults();
		});
	}
		
	public static function dropResult($table_name, $return = false) {
		
		$arr_table = self::$arr_storage_tables[$table_name];
		
		if (!$arr_table) {
			return false;
		}
			
		$sql_drop = "DROP ".($arr_table['temporary'] ? (DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '') : "")." TABLE IF EXISTS ".($arr_table['alias'] ?: $table_name);

		unset(self::$arr_storage_tables[$table_name]);
		
		if ($return) {
			return $sql_drop;
		}
		
		DB::query($sql_drop);
	}
	
	public static function dropResults($return = false) {
		
		$arr_sql_drop = [];
		
		foreach (self::$arr_storage_tables as $table_name => $arr_table) {
			
			$arr_sql_drop[] = "DROP ".($arr_table['temporary'] ? (DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '') : "")." TABLE IF EXISTS ".($arr_table['alias'] ?: $table_name);
		}
		
		if (!$arr_sql_drop) {
			return;
		}
		
		self::$arr_storage_tables = [];

		$sql_drop = implode(';', $arr_sql_drop);
		
		if ($return) {
			return $sql_drop;
		}
		
		DB::queryMulti($sql_drop);
	}
	
	public static function cleanupResults() {
		
		$arr_sql_drop = [];
		
		foreach (self::$arr_storage_tables as $table_name => $arr_table) {
			
			if (Mediator::$in_cleanup && $arr_table['temporary']) { // No need to drop temporary tables when closing down
				continue;
			}
			
			$arr_sql_drop[] = "DROP ".($arr_table['temporary'] ? (DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '') : "")." TABLE IF EXISTS ".($arr_table['alias'] ?: $table_name);
		}
				
		self::$arr_storage_tables = [];
		
		if ($arr_sql_drop) {

			$sql_drop = implode(';', $arr_sql_drop);
				
			DB::queryMulti($sql_drop);
		}
		
		// Cleanup other storages
		
		self::$arr_pre_run = [];
		
		self::clearSharedTypeObjectNames();
	}
	
	public static function printSharedTypeObjectNames($value, $encode = false) {

		if (self::$do_check_shared_type_object_names) {
			
			self::getSharedTypeObjectNames();
		}
		
		if (!self::$arr_shared_type_object_names) {
			
			return $value;
		}
		
		if (is_array($value)) {
			
			foreach ($value as &$str) {
				
				$str = self::doPrintSharedTypeObjectNames($str, $encode);
			}
		} else {
			
			$value = self::doPrintSharedTypeObjectNames($value, $encode);
		}
		
		self::clearSharedTypeObjectNames(true);
		
		return $value;
	}
	
	public static function doPrintSharedTypeObjectNames($str, $encode = false, $count_parse = 0) {
		
		if (strpos($str, '![[') === false) {
			return $str;
		}

		$str = preg_replace_callback('/\!\[\[(\d+)_(\d+)\]\]/', function($arr_match) use ($encode) {
			
			$str_object_name = self::$arr_shared_type_object_names[$arr_match[2]];
		
			return ($encode ? Response::encode($str_object_name) : $str_object_name);
		}, $str, -1, $count);
		
		if ($count && $count_parse < self::$num_shared_type_object_name_depth) { // Some parsing has been done, check for more, but not too much!
			
			$count_parse++;
			
			$str = self::doPrintSharedTypeObjectNames($str, $encode, $count_parse);
		}
		
		return $str;
	}
	
	private static function getSharedTypeObjectNames($count_names = 0) {
		
		self::$do_check_shared_type_object_names = false;
		
		if (!self::$is_active_shared_type_object_names) {
			
			DB::query(DBFunctions::createIndex(self::$table_name_shared_type_object_names, ['state', 'type_id'], 'state_index_nodegoat_share_to_names'));
			
			self::$is_active_shared_type_object_names = true;
		}
		
		$res = DB::query("SELECT DISTINCT type_id
					FROM ".self::$table_name_shared_type_object_names."
				WHERE state = 0
		");
		
		if (!$res->getRowCount()) {
			return;
		}
		
		DB::query("UPDATE ".self::$table_name_shared_type_object_names." SET state = 1 WHERE state = 0");

		while ($arr_row = $res->fetchRow()) {
			
			$type_id = $arr_row[0];
			
			if (self::$func_shared_type_set_conditions_name && !isset(self::$arr_shared_type_set_conditions_name[$type_id])) {
				
				$function = self::$func_shared_type_set_conditions_name;
				self::$arr_shared_type_set_conditions_name[$type_id] = $function($type_id);
			}
						
			$arr_table = ['table_name' => self::$table_name_shared_type_object_names, 'table_alias' => 'nodegoat_share_to_names', 'query' => "nodegoat_share_to_names.type_id = nodegoat_to.type_id AND nodegoat_share_to_names.state = 1"];
			
			$filter = new FilterTypeObjects($type_id, 'name');
			$filter->setVersioning('added');
			if (self::$func_shared_type_set_conditions_name) {
				$filter->setConditions('style_include', self::$arr_shared_type_set_conditions_name[$type_id]); // Use 'style_include' since the style will be stripped by the requesting object when applicable
			}
			$filter->setFilter(['table' => [$arr_table]]);

			foreach ($filter->init() as $object_id => $value) {

				self::$arr_shared_type_object_names[$object_id] = $value['object']['object_name'];
			}
		}
		
		DB::query("UPDATE ".self::$table_name_shared_type_object_names." SET state = 2 WHERE state = 1");
		
		if ($count_names < self::$num_shared_type_object_name_depth) {
			
			$count_names++;
			
			if (self::$do_check_shared_type_object_names) {
				
				self::getSharedTypeObjectNames($count_names);
			}
		}
	}
	
	public static function setClearSharedTypeObjectNames($state = true) {
		
		self::$do_clear_shared_type_object_names = (bool)$state;
	}
	
	private static function clearSharedTypeObjectNames($cache = false) {
		
		if (!self::$arr_shared_type_object_names || ($cache && (!self::$do_clear_shared_type_object_names || count(self::$arr_shared_type_object_names) < self::$num_shared_type_object_names_cache))) {
			return;
		}
		
		self::$arr_shared_type_object_names = [];
		
		DB::queryMulti("
			TRUNCATE TABLE ".self::$table_name_shared_type_object_names.";
			DROP INDEX ".(DB::ENGINE_IS_POSTGRESQL ? self::$table_name_shared_type_object_names.".state_index_nodegoat_share_to_names" : "state_index_nodegoat_share_to_names ON ".self::$table_name_shared_type_object_names).";
		");
		
		self::$is_active_shared_type_object_names = false;
	}
	
	public static function setConditionsResource($function) {
		
		self::$func_shared_type_set_conditions_name = $function;
	}

	public function __construct($type_id, $view = 'name', $is_user = false, $arr_type_set = false) {

		// $view = "id/name/overview/visualise/all/set/storage"

		$this->type_id = (int)$type_id;
		$this->view = $view;
		$this->is_user = $is_user;
		
		$this->setConditions(($this->view == 'id' || $this->view == 'storage' ? false : true));
		
		if ($arr_type_set) {
			$this->arr_type_set = $arr_type_set;
		} else {
			$this->arr_type_set = StoreType::getTypeSet($this->type_id);
		}
		
		$this->arr_type_set_object_sub_details_ids = $this->arr_type_set['object_sub_details'];

		foreach ((array)$this->arr_type_set['type']['include_referenced']['object_sub_details'] as $cur_object_sub_details_id => $arr_object_sub_description_ids) {
									
			unset($this->arr_type_set_object_sub_details_ids[$cur_object_sub_details_id]);
			
			$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$cur_object_sub_details_id]['object_sub_details']['object_sub_details_id'];
			$this->arr_type_set_object_sub_details_ids[$use_object_sub_details_id] = $use_object_sub_details_id;
		}
		
		$this->arr_type_set_object_sub_details_ids = array_keys($this->arr_type_set_object_sub_details_ids);
	}
	
	public function setInitLimit($limit) {
		
		$this->nr_limit_generate = $limit;
	}
	
	public function init() {

		if (!$this->is_generating) {
			
			$this->useSettings();
			$this->generateTablesColumns();
			
			if ($this->view == 'id') {
				
				return $this->initQuick();
			}
			
			$this->initGenerate();
			
			$this->is_generating = true;
		} else {
			
			if ($this->nr_limit_generate) {
			
				$this->nr_offset_generate += $this->nr_limit_generate;
			
				$this->storeResultTemporarilyGenerate();
			} else {
				
				$this->storeResultTemporarilyReload();
			}
		}
		
		$arr_objects = $this->generateObjects();

		return $arr_objects;
	}
	
	public function initGenerate() {

		$this->arr_sql_tables = [];
			
		// Order based on result set
		foreach ($this->arr_order as $column_name => $value) {
			
			if ($column_name == 'object_name') {
				
				if ($this->arr_combine_filters['search'] || $this->arr_combine_filters['search_name']) { // Apply additional advanced sorting based on filtering
				
					if ($this->arr_columns_object['order_object_name_search']) {
					
						$arr_table_info = $this->arr_sql_filter['table']['object_name_search'];
						
						$this->arr_sql_tables[] = "LEFT JOIN ".$arr_table_info['table_name']." AS ".$arr_table_info['table_alias']." ON (".$arr_table_info['table_alias'].".id = nodegoat_to_store.id)";
						
						$this->arr_sql_order[] = $this->arr_columns_object['order_object_name_search'];
						$this->arr_sql_group[] = $this->arr_columns_object_group['order_object_name_search'];
					}
				
					$this->arr_sql_order[] = "CHAR_LENGTH(".$this->arr_columns_object['object_name'].")";
				}
			}
			
			if ($this->arr_columns_object[$column_name]) {
				
				$sql_column = $this->arr_columns_object[$column_name];
				
				if (is_array($sql_column)) {
					foreach ($sql_column as $sql) {
						$this->arr_sql_order[] = $sql." ".$value;
					}
				} else {
					$this->arr_sql_order[] = $sql_column." ".$value;
				}
			}
		}
				
		$this->storeResultTemporarily();

		if ($this->view == 'visualise' || ($this->view == 'overview' && !$this->arr_limit)) {
			
			$arr_result = $this->getResultInfo();
			
			$arr_nodegoat_details = cms_nodegoat_details::getDetails();
			
			if ($arr_result['total_filtered'] > $arr_nodegoat_details['limit_view']) {
				error(getLabel('msg_data_range_too_wide'));
			}
		}

		if ($this->arr_columns_object_conditions) {
			
			$arr_sql_object_conditions = [];
				
			foreach ($this->arr_columns_object_conditions as $key => $arr_sql) {
				
				$sql = str_replace('[X]', $this->table_name_objects, $arr_sql['sql']);
				
				$arr_sql_object_conditions[] = "INSERT INTO ".$this->table_name_all_conditions."
					(id, condition_match, condition_key)
					".$sql."
				";
			}
			
			DB::queryMulti("
				DROP ".(self::$keep_tables ? "" : (DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : ''))." TABLE IF EXISTS ".$this->table_name_all_conditions.";
				
				CREATE ".(self::$keep_tables ? "" : "TEMPORARY")." TABLE ".$this->table_name_all_conditions." (
					id INT,
					condition_match INT,
					condition_key SMALLINT,
					".(DB::ENGINE_IS_MYSQL ? "PRIMARY KEY USING BTREE (id, condition_match, condition_key)" : "PRIMARY KEY (id, condition_match, condition_key)")."
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
				".implode(';', $arr_sql_object_conditions).";
			");
		}
	}
	
	private function generateObjects() {
				
		$sql_order = '';
		if ($this->arr_sql_order) {
			$sql_order = "ORDER BY ".implode(',', $this->arr_sql_order).", nodegoat_to.id DESC";
		} else if ($this->arr_limit) {
			$sql_order = "ORDER BY nodegoat_to.id DESC";
		}
		
		$sql_group = "GROUP BY nodegoat_to.id, nodegoat_to.version";
		
		if ($this->arr_sql_group) {
			$sql_group .= ",".implode(',', $this->arr_sql_group);
		}
		
		$sql_tables = ($this->arr_sql_tables ? implode(' ', $this->arr_sql_tables) : '');

		$version_select = $this->generateVersion('object', 'nodegoat_to');
		
		$sql_select_object_conditions = false;
		
		if ($this->arr_columns_object_conditions && ($this->arr_type_set_conditions['object'] || $this->arr_type_set_conditions['object_descriptions'])) {
			
			$arr_object_conditions_keys = [];

			foreach ($this->arr_columns_object_conditions as $key => $arr_sql) {
				
				if ($arr_sql['condition_group'] != 'object') {
					continue;
				}
				
				$arr_object_conditions_keys[] = $arr_sql['condition_key'];
			}
			
			$sql_select_object_conditions = "''";
			
			if ($arr_object_conditions_keys) {
				
				$sql_select_object_conditions = "(SELECT
					".DBFunctions::sqlImplode(DBFunctions::castAs('nodegoat_to_store_conditions.condition_key', DBFunctions::CAST_TYPE_STRING), ',')."
						FROM ".$this->table_name_all_conditions." AS nodegoat_to_store_conditions
					WHERE nodegoat_to_store_conditions.id = nodegoat_to.id
						AND nodegoat_to_store_conditions.condition_match = 1
						AND nodegoat_to_store_conditions.condition_key IN (".implode(',', $arr_object_conditions_keys).")
				) AS conditions";
			}
		}
		
		$arr_columns_object_conditions_values = [];
		
		foreach ($this->arr_columns_object_conditions_values as $key => $arr_sql) {
			
			$arr_columns_object_conditions_values[] = "(".$arr_sql['sql'].") AS ".$arr_sql['column_name'];
		}

		$res_objects = DB::query("
			SELECT ".implode(',', $this->arr_columns_object_as)
					.($sql_select_object_conditions ? ", ".$sql_select_object_conditions : "")
					.($arr_columns_object_conditions_values ? ", ".implode(',', $arr_columns_object_conditions_values) : "")."
				FROM ".$this->table_name." AS nodegoat_to_store
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_store.id AND ".$version_select.")
				".$sql_tables."
			".$sql_group."
			".$sql_order."
		");

		$arr = [];
		
		$func_parse_references = function($type, $value, $object_description_id) {
			
			$arr = [];
			
			foreach (array_filter(explode(self::$sql_separator_group, $value)) as $arr_ref) {
				
				$arr_ref = explode(self::$sql_separator_group2, $arr_ref);
				
				$ref_object_id = (int)$arr_ref[0];
				$ref_type_id = (int)$arr_ref[1];
				$ref_object_name = $arr_ref[2];
				
				$s_arr =& $arr[$ref_type_id][$ref_object_id];
				
				if (!$s_arr) {
					
					$s_arr = [$type.'_ref_object_id' => $ref_object_id, $type.'_ref_object_name' => $ref_object_name, $type.'_ref_value' => []];
					
					$this->addTypeFound($type.'_'.$object_description_id, $ref_type_id);
				}
				if ($arr_ref[3]) {
					
					$s_arr[$type.'_ref_value'][] = $arr_ref[3];
				}
			}
			
			return $arr;
		};
		
		$func_parse_sources = function($type, $value) {
			
			$arr = [];
			
			foreach (array_filter(explode(self::$sql_separator_group, $value)) as $arr_source) {
				
				$arr_source = explode(self::$sql_separator_group2, $arr_source);
				
				$type_id = (int)$arr_source[1];
				$ref_object_id = (int)$arr_source[0];
				
				$arr[$type_id][] = [$type.'_source_ref_object_id' => $ref_object_id, $type.'_source_link' => $arr_source[2]];
				
				$this->addTypeFound('sources', $type_id);
			}
			
			return $arr;
		};
		
		$func_parse_filters = function($value) {
			
			$arr = [];
			
			foreach (array_filter(explode(self::$sql_separator_group, $value)) as $arr_filter) {
				
				$arr_filter = explode(self::$sql_separator_group2, $arr_filter);
				
				$ref_type_id = (int)$arr_filter[0];
				
				$arr[$ref_type_id] = ['object_filter_ref_type_id' => $ref_type_id, 'object_filter_object' => ($arr_filter[1] ? json_decode($arr_filter[1], true) : []), 'object_filter_scope_object' => ($arr_filter[2] ? json_decode($arr_filter[2], true) : [])];
			}
			
			return $arr;
		};
		
		// Subobjects
		
		if ($this->arr_selection['object_sub_details']) {
			
			$table_name_nodegoat_tos = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_tos_temp');
			$contains_referenced = $this->arr_type_set['type']['include_referenced']['object_sub_details'];
			
			DB::queryMulti("
				DROP ".(DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : '')." TABLE IF EXISTS ".$table_name_nodegoat_tos.";
				
				CREATE ".(self::$keep_tables ? "" : "TEMPORARY")." TABLE ".$table_name_nodegoat_tos." (
					id INT,
					".($contains_referenced ? "
						object_id INT DEFAULT 0,
						referenced_object_sub_description_id INT DEFAULT 0,
					" : "")."
						PRIMARY KEY (".($contains_referenced ? "object_id, id" : "id").")
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
			");
			
			$sql_query_subs_preload = $sql_query_subs_select = '';
			$arr_object_sub_details_ids = [];
			
			foreach ($this->arr_columns_subs_as as $object_sub_details_id => $arr_columns) {
				
				$arr_object_sub_details_ids[] = $object_sub_details_id;
				
				if ($this->arr_order_object_subs[$object_sub_details_id]) {
					
					foreach ($this->arr_order_object_subs[$object_sub_details_id] as $key => $value) {
						
						$this->arr_sql_order_subs[$object_sub_details_id][] = $this->arr_columns_subs[$object_sub_details_id][$key]." ".$value;
					}
				}

				$is_filtering = $this->isFilteringObjectSubDetails($object_sub_details_id, true);
				$is_referenced = ($contains_referenced && $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]);
				$has_referenced = ($is_referenced || ($object_sub_details_id == 'all' && $contains_referenced));

				if (!$is_filtering) {
					
					if ($has_referenced) {
						$sql_query_subs_preload .= "
							INSERT INTO ".$table_name_nodegoat_tos." (id, object_id, referenced_object_sub_description_id)
							".$this->sqlQuerySub('object_sub_ids_object_ids_referenced_object_sub_description_ids', $object_sub_details_id, $this->table_name_objects).";
						";
					} else {
						$sql_query_subs_preload .= "
							INSERT INTO ".$table_name_nodegoat_tos." (id)
							".$this->sqlQuerySub('object_sub_ids', $object_sub_details_id, $this->table_name_objects).";
						";
					}
				}
				
				$table_name = "nodegoat_tos_".$object_sub_details_id;
				$table_name_store = ($is_filtering ? $this->table_name : $table_name_nodegoat_tos);
				$column_name_store = ($is_filtering ? "nodegoat_tos_store.object_sub_".$object_sub_details_id."_id" : "nodegoat_tos_store.id");
				$column_name_object = (!$has_referenced ? $table_name.".object_id" : "nodegoat_tos_store.object_id");
				$version_select = $this->generateVersion('object_sub', $table_name);
				$version_select_object = $this->generateVersion('object', 'nodegoat_to');
				
				$sql_select_object_conditions = false;
				
				if ($this->arr_columns_object_conditions && $this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id]) {
						
					$arr_object_conditions_keys = [];
					$arr_object_conditions_keys_filtering = [];

					foreach ($this->arr_columns_object_conditions as $key => $arr_sql) {
						
						if ($arr_sql['condition_group'] != 'object_sub_details') {
							continue;
						}
						
						if ($arr_sql['condition_is_filtering_sub']) {
							
							$arr_object_conditions_keys_filtering[] = $arr_sql['condition_key'];
						} else {
							$arr_object_conditions_keys[] = $arr_sql['condition_key'];
						}
					}
					
					$sql_select_object_conditions = "''";
					
					if ($arr_object_conditions_keys || $arr_object_conditions_keys_filtering) {
						
						$sql_select_object_conditions = "(SELECT ".DBFunctions::sqlImplode(DBFunctions::castAs('nodegoat_to_store_conditions.condition_key', DBFunctions::CAST_TYPE_STRING), ',')."
								FROM ".$this->table_name_all_conditions." AS nodegoat_to_store_conditions
							WHERE 
								".($arr_object_conditions_keys ? "
									(
										nodegoat_to_store_conditions.id = nodegoat_to.id
										AND nodegoat_to_store_conditions.condition_match = 1
										AND nodegoat_to_store_conditions.condition_key IN (".implode(',', $arr_object_conditions_keys).")
									)
								" : "")."
								".($arr_object_conditions_keys_filtering ? "
									".($arr_object_conditions_keys ? "OR" : "")." (
										nodegoat_to_store_conditions.id = nodegoat_to.id
										AND nodegoat_to_store_conditions.condition_match = ".$table_name.".id
										AND nodegoat_to_store_conditions.condition_key IN (".implode(',', $arr_object_conditions_keys_filtering).")
									)
								" : "")."
						) AS conditions";
					}
				}
				
				$arr_columns_object_conditions_values = [];
				foreach ((array)$this->arr_columns_subs_object_conditions_values[$object_sub_details_id] as $key => $arr_sql) {

					$arr_columns_object_conditions_values[] = "(".$arr_sql['sql'].") AS ".$arr_sql['column_name'];
				}

				$use_object_sub_details_id = $object_sub_details_id;
				$sql_select_referenced = '';
				
				// Select the correct referenced sub-object
				if ($contains_referenced) {
					
					if ($is_referenced) {
						$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id'];
						$sql_select_referenced = "nodegoat_tos_store.object_id != ".$table_name.".object_id";
					} else if (!$has_referenced) {
						$sql_select_referenced = "nodegoat_tos_store.object_id = 0";
					}
				}
				
				$sql_order = '';
				if ($this->arr_sql_order_subs[$object_sub_details_id]) {
					
					$sql_order_all = '';
					
					if ($object_sub_details_id == 'all') {
						
						$count = 0;
						$sql_order_all = DBFunctions::fieldToPosition($table_name.'.object_sub_details_id', $this->arr_selection_object_sub_details_ids);
					}
					
					$sql_order = "ORDER BY ".implode(',', $this->arr_sql_order_subs[$object_sub_details_id]).", ".($sql_order_all ? $sql_order_all.", " : "").$table_name.".id ASC";
				}
				
				$sql_query_subs_select .= "
					SELECT ".implode(',', $arr_columns)
							.($sql_select_object_conditions ? ", ".$sql_select_object_conditions : "")
							.($arr_columns_object_conditions_values ? ", ".implode(',', $arr_columns_object_conditions_values) : "")."
						FROM ".$table_name_store." AS nodegoat_tos_store
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".id = ".$column_name_store." AND ".$version_select.")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = ".$column_name_object." AND ".$version_select_object.")
						".$this->arr_tables['select_object_subs'][$object_sub_details_id]."
					WHERE ".$table_name.".object_sub_details_id ".($object_sub_details_id == 'all' ? 'IN ('.implode(',', $this->arr_selection_object_sub_details_ids).')' : "= ".$use_object_sub_details_id)."
						".($sql_select_referenced ? "AND ".$sql_select_referenced : "")."
					GROUP BY nodegoat_to.id, ".$table_name.".id, ".$table_name.".version".($this->arr_columns_subs_group[$object_sub_details_id] ? ", ".implode(',', $this->arr_columns_subs_group[$object_sub_details_id]) : "")."
					".$sql_order."
				;";
			}
			
			if ($this->arr_columns_subs) {
				
				if ($sql_query_subs_preload) {
					DB::queryMulti($sql_query_subs_preload);
				}
				$arr_res_objects_subs = DB::queryMulti($sql_query_subs_select);
			} else {
				
				$arr_res_objects_subs = [];
			}
			
			if ($this->view == 'visualise') {
				
				$count_objects = $res_objects->getRowCount();
				
				$count_object_subs = 0;
				foreach ($arr_res_objects_subs as $res) {
					$count_object_subs += $res->getRowCount();
				}
				
				Labels::setVariable('count_objects', false);
				Labels::setVariable('count_object_subs', false);
				Labels::setVariable('type', false);
				$msg = getLabel('msg_data_range_objects_found', 'L', true); // This message might occur more than once with varying 'count'
				
				Labels::setVariable('count_objects', nr2String($count_objects));
				Labels::setVariable('count_object_subs', nr2String($count_object_subs));
				Labels::setVariable('type', Labels::parseTextVariables($this->arr_type_set['type']['name']));
				status(Labels::parseTextVariables($msg));
				
				$arr_nodegoat_details = cms_nodegoat_details::getDetails();
				
				if ($count_object_subs > $arr_nodegoat_details['limit_view']) {
					error(getLabel('msg_data_range_too_wide'));
				}
			}
			
			foreach ($arr_res_objects_subs as $key => $res) {
				
				$object_sub_details_id = $arr_object_sub_details_ids[$key];
				$arr_object_sub_details = $this->arr_type_set['object_sub_details'][$object_sub_details_id];
				$arr_conditions_object_sub_details = $this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id];
				
				$arr_object_sub_description_ids = array_keys((array)$this->arr_columns_subs_descriptions[$object_sub_details_id]);
				$arr_object_sub_definitions_placeholder = [];
				
				$column_index_object_conditions_subs_values = $res->getFieldCount() - count((array)$this->arr_columns_subs_object_conditions_values[$object_sub_details_id]);
		
				foreach ($arr_object_sub_description_ids as $object_sub_description_id) {

					$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
					
					$object_sub_definition_ref = null;
					$object_sub_definition_value = null;
				
					if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
						$object_sub_definition_ref = [];
						$object_sub_definition_value = '';
					} else if ($arr_object_sub_description['object_sub_description_has_multi']) {
						$object_sub_definition_ref = [];
						$object_sub_definition_value = [];
					}
					
					$arr_object_sub_definitions_placeholder[$object_sub_description_id] = [
						'object_sub_description_id' => (int)$object_sub_description_id,
						'object_sub_definition_ref_object_id' => $object_sub_definition_ref,
						'object_sub_definition_value' => $object_sub_definition_value,
						'object_sub_definition_sources' => [],
						'object_sub_definition_style' => []
					];
				}
				
				$has_referenced = ($this->arr_type_set['type']['include_referenced']['object_sub_details'] && ($object_sub_details_id == 'all' || $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]));
				
				while ($arr_row = $res->fetchRow()) {
					
					$cur_object_id = $arr_row[0];
					$cur_row = 1;
					
					$cur_object_sub_id = (int)$arr_row[$cur_row+0];
					$cur_object_sub_details_id = (int)$arr_row[$cur_row+1];

					$object_sub_location_type = ($arr_row[$cur_row+7] || (!$arr_row[$cur_row+4] && $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']) ? 'reference' : 'geometry');
					$location_object_id = ($this->view != 'visualise' ? $arr_row[$cur_row+7] : $arr_row[$cur_row+5]);
					$location_type_id = ($this->view != 'visualise' ? $arr_row[$cur_row+8] : $arr_row[$cur_row+6]);
					$this->addTypeFound('locations', $arr_row[$cur_row+8]);
					
					$s_arr =& $arr[$cur_object_id]['object_subs'][$cur_object_sub_id];

					$s_arr['object_sub'] = [
						'object_sub_id' => $cur_object_sub_id,
						'object_sub_details_id' => $cur_object_sub_details_id,
						'object_sub_date_start' => (int)$arr_row[$cur_row+2],
						'object_sub_date_end' => (int)$arr_row[$cur_row+3],
						'object_sub_location_geometry' => (string)$arr_row[$cur_row+4],
						'object_sub_location_geometry_ref_object_id' => (int)$arr_row[$cur_row+5],
						'object_sub_location_geometry_ref_type_id' => (int)$arr_row[$cur_row+6],
						'object_sub_location_ref_object_id' => (int)$arr_row[$cur_row+7],
						'object_sub_location_ref_type_id' => (int)$arr_row[$cur_row+8],
						'object_sub_location_ref_object_sub_details_id' => (int)$arr_row[$cur_row+9],
						'object_sub_location_ref_object_name' => ($location_object_id && $this->view != 'storage' ? '![['.$location_type_id.'_'.$location_object_id.']]' : ''),
						'object_sub_location_type' => $object_sub_location_type,
						'object_sub_sources' => $func_parse_sources('object_sub', $arr_row[$cur_row+10]),
						'object_sub_version' => $arr_row[$cur_row+11],
						'object_sub_style' => []
					];
					
					$cur_row = $cur_row+12;

					if ($this->conditions == 'text') {
						
						$s_object_sub_location_ref_object_name = $s_arr['object_sub']['object_sub_location_ref_object_name'];
						
						if ($s_object_sub_location_ref_object_name) {
							$s_arr['object_sub']['object_sub_location_ref_object_name'] = Response::addParsePost($s_object_sub_location_ref_object_name, ['strip' => true]);
						}
					}
					
					if ($has_referenced) {
						$s_arr['object_sub']['object_sub_object_id'] = (int)$arr_row[$cur_row+0];
						if ($arr_row[$cur_row+1]) {
							$s_arr['object_sub']['object_sub_details_id'] = $cur_object_sub_details_id.'is0referenced'.$arr_row[$cur_row+1];
						}
						$cur_row = $cur_row+2;
					}
					
					$s_arr['object_sub_definitions'] = $arr_object_sub_definitions_placeholder;
					
					if ($arr_conditions_object_sub_details) {
						
						$this->resetObjectConditions();
						
						$arr_condition_labels = []; // Store the identified conditions with the sub objects
						
						if ($this->arr_columns_object_conditions) {
							
							$arr_match = $arr_row[$cur_row+0];
							
							if ($arr_match) {
								$arr_match = explode(',', $arr_row[$cur_row+0]);
								$arr_match = array_combine($arr_match, $arr_match);
							} else {
								$arr_match = [];
							}
							
							$cur_row = $cur_row+1;
						}
						
						if ($arr_conditions_object_sub_details['object_sub_details']) {
							
							foreach ($arr_conditions_object_sub_details['object_sub_details'] as $arr_condition_setting) {
								
								if ($arr_condition_setting['condition_filter']) {
									$apply = $arr_match[$arr_condition_setting['condition_key']];
								} else {
									$apply = true;
								}

								if ($apply) {
									
									$condition_label = $arr_condition_setting['condition_label'];
									
									if ($condition_label) {
										$arr_condition_labels[$condition_label] = $condition_label;
									}
									
									if ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id']) {
								
										$column_index = $column_index_object_conditions_subs_values + $arr_condition_setting['condition_value_column_index'];
										
										$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (int)$arr_row[$column_index];
									}
									
									$this->setObjectConditions('object_sub', $arr_condition_setting['condition_actions']);
								}
							}
						}
						
						if ($arr_conditions_object_sub_details['object_sub_descriptions']) {
							
							foreach ($arr_conditions_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_condition_settings) {
								
								$in_selection = (bool)$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id];

								foreach ($arr_condition_settings as $arr_condition_setting) {
									
									if ($arr_condition_setting['condition_filter']) {
										$apply = $arr_match[$arr_condition_setting['condition_key']];
									} else {
										$apply = true;
									}
									
									if ($apply && $in_selection) {
									
										if ($arr_condition_setting['condition_in_object_nodes_referencing']) {
										
											$condition_label = $arr_condition_setting['condition_label'];
											
											if ($condition_label) {
												$arr_condition_labels[$condition_label] = $condition_label;
											}
											
											if ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id']) {
										
												$column_index = $column_index_object_conditions_subs_values + $arr_condition_setting['condition_value_column_index'];
												
												$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (int)$arr_row[$column_index];
											}
										}
										
										$this->setObjectConditions($object_sub_description_id, $arr_condition_setting['condition_actions']);
									}
								}
							}
						}
						
						$this->applyObjectConditions($s_arr);
						
						if ($this->conditions == 'visual' && $arr_condition_labels && $s_arr['object_sub']['object_sub_style'] !== 'hide') {
							$s_arr['object_sub']['object_sub_style']['conditions'] = $arr_condition_labels;
						}
					}
				}
				
				unset($s_arr);
				$res->freeResult();
			}
			
			// Subobject definitions
		
			if ($this->arr_columns_subs_descriptions) {

				$arr_sql_object_subs_descriptions = [];
				$version_select = $this->generateVersion('record', 'nodegoat_tos_def');
				$version_select_object_definition = $this->generateVersion('record', 'nodegoat_to_def');
				$arr_object_sub_description_ids = [];
				
				foreach ($this->arr_columns_subs_descriptions as $object_sub_details_id => $arr_columns_sub_descriptions) {
					
					$is_filtering = $this->isFilteringObjectSubDetails($object_sub_details_id, true);
					$has_referenced = $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id];
					$arr_object_sub_details = $this->arr_type_set['object_sub_details'][$object_sub_details_id];
					$use_object_sub_details_id = $arr_object_sub_details['object_sub_details']['object_sub_details_id']; // In case the sub-object is referenced;
					
					$table_name = "nodegoat_tos_".$object_sub_details_id;
					$version_select_sub = $this->generateVersion('object_sub', $table_name);
					$column_name_store = ($is_filtering ? "nodegoat_to_store.object_sub_".$object_sub_details_id."_id" : "nodegoat_tos_store.id");
					$column_name_object = ($has_referenced ? "nodegoat_tos_store.object_id" : $table_name.".object_id");
					
					foreach ($arr_columns_sub_descriptions as $object_sub_description_id => $arr_columns) {
						
						$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];

						$arr_object_sub_description_ids[] = [$object_sub_details_id, $object_sub_description_id];
						
						if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
							
							$object_sub_description_use_object_description_id = $arr_object_sub_description['object_sub_description_use_object_description_id'];
							
							$is_filtering_object_definition = $this->isFilteringObjectDescription($object_sub_description_use_object_description_id, true);
							
							$sql_join = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_to_def ON (nodegoat_to_def.object_id = ".$table_name.".object_id AND nodegoat_to_def.object_description_id = ".$object_sub_description_use_object_description_id." AND ".$version_select_object_definition."
								".($is_filtering_object_definition && !$is_filtering ? "AND EXISTS (SELECT TRUE FROM ".$this->table_name." AS nodegoat_to_store WHERE nodegoat_to_store.object_definition_".$object_sub_description_use_object_description_id."_ref_object_id = nodegoat_to_def.ref_object_id)" : "")."
								".($is_filtering_object_definition && $is_filtering ? "AND nodegoat_to_store.object_definition_".$object_sub_description_use_object_description_id."_ref_object_id = nodegoat_to_def.ref_object_id" : "")."
							)";
							
							$sql_group = "nodegoat_to_def.object_id, nodegoat_to_def.object_description_id, nodegoat_to_def.identifier, nodegoat_to_def.version";
						} else {
							
							$is_filtering_object_sub_definition = $this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id, true);
							
							$sql_join = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'])." AS nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = ".$table_name.".id AND nodegoat_tos_def.object_sub_description_id = ".$object_sub_description_id." AND ".$version_select."
								".($is_filtering_object_sub_definition ? ($arr_object_sub_description['object_sub_description_ref_type_id'] ? "AND nodegoat_to_store.object_sub_definition_".$object_sub_description_id."_ref_object_id = nodegoat_tos_def.ref_object_id" : "AND nodegoat_to_store.object_sub_definition_".$object_sub_description_id."_value = nodegoat_tos_def.value") : "")."
							)";
							
							$sql_group = "nodegoat_tos_def.object_sub_id, nodegoat_tos_def.object_sub_description_id, nodegoat_tos_def.version";		
						}
						
						$arr_sql_object_subs_descriptions[] = "SELECT ".$column_name_object.",
									".$table_name.".id AS object_sub_id,
									".($arr_columns['select_id'] ? $arr_columns['select_id'] : "NULL")." AS select_id,
									".($arr_columns['select_value'] ? $arr_columns['select_value'] : "NULL")." AS select_value,
									".($arr_columns['sources'] ? DBFunctions::sqlImplode("CONCAT(nodegoat_tos_def_src.ref_object_id, '".self::$sql_separator_group2."', nodegoat_tos_def_src.ref_type_id, '".self::$sql_separator_group2."', nodegoat_tos_def_src.value)", self::$sql_separator_group) : "NULL")." AS sources
								FROM ".($is_filtering ? $this->table_name." AS nodegoat_to_store" : $table_name_nodegoat_tos." AS nodegoat_tos_store")."
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".object_sub_details_id = ".$use_object_sub_details_id." AND ".$table_name.".id = ".$column_name_store." AND ".$version_select_sub.")
									".$sql_join."
									".$arr_columns['tables']."
									".($arr_columns['sources'] ? "LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')." nodegoat_tos_def_src ON (nodegoat_tos_def_src.object_sub_description_id = ".$object_sub_description_id." AND nodegoat_tos_def_src.object_sub_id = ".$table_name.".id)" : "")."
							GROUP BY ".$column_name_object.", ".$table_name.".id, ".$table_name.".version, ".$sql_group.($arr_columns['group'] ? ", ".$arr_columns['group'] : "")."
							ORDER BY ".$table_name.".id
						";
					}
				}
				
				$arr_res_object_sub_descriptions = DB::queryMulti(implode(';', $arr_sql_object_subs_descriptions));
				
				foreach ($arr_res_object_sub_descriptions as $key => $res) {
					
					$object_sub_details_id = $arr_object_sub_description_ids[$key][0];
					$object_sub_description_id = $arr_object_sub_description_ids[$key][1];
					$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
					
					$is_ref_type_id = $arr_object_sub_description['object_sub_description_ref_type_id'];
					$has_multi = $arr_object_sub_description['object_sub_description_has_multi'];
					$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
					
					$arr_row = $res->fetchRow();
					
					$cur_object_id = (int)$arr_row[0];
					$cur_object_sub_id = (int)$arr_row[1];

					while ($arr_row) {

						$s_arr =& $arr[$cur_object_id]['object_subs'][$cur_object_sub_id]['object_sub_definitions'][$object_sub_description_id];
						
						$select_id = $arr_row[2];
						$select_value = $arr_row[3];
						
						if ($this->conditions == 'text' && $select_value && $is_ref_type_id) {
							$select_value = Response::addParsePost($select_value, ['strip' => true]);
						}
																	
						if ($is_dynamic) {
							$s_arr['object_sub_definition_ref_object_id'] = $func_parse_references('object_sub_definition', $select_id, $object_sub_description_id);
							$s_arr['object_sub_definition_value'] = $select_value;
						} else if ($has_multi) {
							if ($select_id || $select_value) {
								$s_arr['object_sub_definition_ref_object_id'][] = ($select_id === null ? null : (int)$select_id);
								$s_arr['object_sub_definition_value'][] = $select_value;
							}
						} else {
							if ($select_id || $select_value != '') {
								$s_arr['object_sub_definition_ref_object_id'] = ($select_id === null ? null : (int)$select_id);
								$s_arr['object_sub_definition_value'] = $select_value;
							}
						}
						
						if ($arr_row[4]) {
							$s_arr['object_sub_definition_sources'] = $func_parse_sources('object_sub_definition', $arr_row[4]);
						}
											
						$arr_row = $res->fetchRow();
						
						$object_sub_id = (int)$arr_row[1];
						
						if ($object_sub_id != $cur_object_sub_id) {
														
							$cur_object_id = (int)$arr_row[0];
							$cur_object_sub_id = $object_sub_id;
						}
					}
					
					$res->freeResult();
				}
				unset($s_arr);
			}
		}
		
		// Objects
		
		$has_analysis = ($this->arr_selection['object']['analysis'] ? true : false);
		$arr_object_description_ids = array_keys($this->arr_columns_object_descriptions);
		$arr_object_definitions_placeholder = [];
		
		if ($this->conditions) {
			
			$column_index_object_conditions_values = $res_objects->getFieldCount() - count($this->arr_columns_object_conditions_values);
			
			$arr_object_sub_details_condition_settings_in_name = [];
			
			if ($this->arr_type_set_conditions['object_sub_details']) {
					
				foreach ($this->arr_type_set_conditions['object_sub_details'] as $object_sub_details_id => $arr_conditions_object_sub_details) {
					
					if (!$arr_conditions_object_sub_details['object_sub_descriptions']) {
						continue;
					}
							
					foreach ($arr_conditions_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_condition_settings) {

						foreach ($arr_condition_settings as $arr_condition_setting) {
							
							if (!$arr_condition_setting['condition_in_object_name']) {
								continue;
							}
							
							$arr_object_sub_details_condition_settings_in_name[] = $arr_condition_setting;
						}
					}
				}
			}
		}
		
		foreach ($arr_object_description_ids as $object_description_id) {
			
			if (!$this->arr_column_purpose[$object_description_id]['view']) {
				continue;
			}
			
			$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
			
			$object_definition_ref = null;
			$object_definition_value = null;
				
			if ($arr_object_description['object_description_is_dynamic']) {
				$object_definition_ref = [];
				$object_definition_value = '';
			} else if ($arr_object_description['object_description_has_multi']) {
				$object_definition_ref = [];
				$object_definition_value = [];
			}
			
			$arr_object_definitions_placeholder[$object_description_id] = [
				'object_description_id' => (int)$object_description_id,
				'object_definition_ref_object_id' => $object_definition_ref,
				'object_definition_value' => $object_definition_value,
				'object_definition_sources' => [],
				'object_definition_style' => []
			];
		}

		while ($arr_row = $res_objects->fetchRow()) {
		
			$cur_object_id = (int)$arr_row[0];
			
			$arr[$cur_object_id]['object'] = [
				'object_id' => $cur_object_id,
				'object_name' => $arr_row[1],
				'object_name_plain' => $arr_row[2],
				'object_name_style' => [],
				'object_style' => [],
				'object_sources' => $func_parse_sources('object', $arr_row[3]),
				'object_version' => $arr_row[4],
				'object_dating' => $arr_row[5],
				'object_locked' => $arr_row[6]
			];
			$s_arr =& $arr[$cur_object_id]['object'];
			$cur_row = 7;
			if ($this->view == 'overview') {
				$s_arr['object_changes'] = $arr_row[$cur_row+0];
				$s_arr['object_sub_changes'] = $arr_row[$cur_row+1];
				$cur_row = $cur_row+2;
			}
			if ($has_analysis) {
				$s_arr['object_analysis'] = $arr_row[$cur_row+0];
				$cur_row = $cur_row+1;
			}
			
			if ($this->arr_type_set['type']['is_reversal']) {
				
				$arr[$cur_object_id]['object_filters'] = $func_parse_filters($arr_row[$cur_row+0]);
				$cur_row = $cur_row+1;
			}
			
			$arr[$cur_object_id]['object_definitions'] = $arr_object_definitions_placeholder;
			if (!$arr[$cur_object_id]['object_subs']) {
				$arr[$cur_object_id]['object_subs'] = [];
			}
			
			if ($this->conditions) {
					
				$this->resetObjectConditions();
				
				$arr_condition_labels = []; // Store the identified conditions with the objects
				
				if ($this->arr_columns_object_conditions) {
					
					$arr_match = $arr_row[$cur_row+0];
					
					if ($arr_match) {
						$arr_match = explode(',', $arr_row[$cur_row+0]);
						$arr_match = array_combine($arr_match, $arr_match);
					} else {
						$arr_match = [];
					}
					
					$cur_row = $cur_row+1;
				}
				
				if ($this->arr_type_set_conditions['object']) {

					foreach ($this->arr_type_set_conditions['object'] as $arr_condition_setting) {
						
						if ($arr_condition_setting['condition_filter']) {
							$apply = $arr_match[$arr_condition_setting['condition_key']];
						} else {
							$apply = true;
						}
						
						if ($arr_condition_setting['condition_in_object_name']) {
							
							$this->setObjectNameConditions('object', ($apply ? $arr_condition_setting['condition_actions'] : false));
						} else if ($apply) {
							
							$condition_label = $arr_condition_setting['condition_label'];
							
							if ($condition_label) {
								$arr_condition_labels[$condition_label] = $condition_label;
							}
							
							if ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id']) {
								
								$column_index = $column_index_object_conditions_values + $arr_condition_setting['condition_value_column_index'];
								
								$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (int)$arr_row[$column_index];
							}
							
							$this->setObjectConditions('object', $arr_condition_setting['condition_actions']);
						}
					}
				}
				
				if ($this->arr_type_set_conditions['object_descriptions']) {
					
					foreach ($this->arr_type_set_conditions['object_descriptions'] as $object_description_id => $arr_condition_settings) {
						
						$in_selection = (bool)$this->arr_columns_object_descriptions[$object_description_id];
						
						foreach ($arr_condition_settings as $arr_condition_setting) {
							
							if ($arr_condition_setting['condition_filter']) {
								$apply = $arr_match[$arr_condition_setting['condition_key']];
							} else {
								$apply = true;
							}
							
							if ($arr_condition_setting['condition_in_object_name']) {
								
								$this->setObjectNameConditions($object_description_id, ($apply ? $arr_condition_setting['condition_actions'] : false));
							} else if ($apply && $in_selection) {
								
								if ($arr_condition_setting['condition_in_object_nodes_referencing']) {
								
									$condition_label = $arr_condition_setting['condition_label'];
									
									if ($condition_label) {
										$arr_condition_labels[$condition_label] = $condition_label;
									}
									
									if ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id']) {
								
										$column_index = $column_index_object_conditions_values + $arr_condition_setting['condition_value_column_index'];
										
										$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (int)$arr_row[$column_index];
									}
								}
								
								$this->setObjectConditions($object_description_id, $arr_condition_setting['condition_actions']);
							}
						}
					}
				}
				
				if ($arr_object_sub_details_condition_settings_in_name) {
					
					foreach ($arr_object_sub_details_condition_settings_in_name as $arr_condition_setting) {
						
						if ($arr_condition_setting['condition_filter']) {
							$apply = $arr_match[$arr_condition_setting['condition_key']];
						} else {
							$apply = true;
						}
						
						$this->setObjectNameConditions($arr_condition_setting['object_sub_description_id'].'_'.$arr_condition_setting['object_sub_details_id'], ($apply ? $arr_condition_setting['condition_actions'] : false));
					}
				}
				
				$s_arr['object_name'] = Labels::parseLanguageTags($s_arr['object_name']);
				
				$this->applyObjectNameConditions($s_arr);
				$this->applyObjectConditions($arr[$cur_object_id]);
				
				if ($this->conditions == 'text' && $s_arr['object_name']) {
					$s_arr['object_name'] = Response::addParsePost($s_arr['object_name'], ['strip' => true]);
				}
			
				if ($this->conditions == 'visual' && $arr_condition_labels && $s_arr['object_style'] !== 'hide') {
					$s_arr['object_style']['conditions'] = $arr_condition_labels;
				}
			} else {
				
				$s_arr['object_name'] = Labels::parseLanguageTags($s_arr['object_name']);
			}
			
			if (!$s_arr['object_name']) {
				
				if ($this->conditions === 'style_include') {
					$s_arr['object_name'] = '<span style="font-style: italic; opacity: 0.8;">'.getLabel('lbl_no_name').'</span>';
				} else if ($this->conditions === 'style') {
					$s_arr['object_name'] = getLabel('lbl_no_name');
					$s_arr['object_name_style'] = 'font-style: italic; opacity: 0.8;';
				} else if ($this->conditions === 'visual') {
					$s_arr['object_name'] = getLabel('lbl_no_name');
					$s_arr['object_name_style'] = ['font_style' => 'italic', 'opacity' => 0.8];
				} else {
					$s_arr['object_name'] = getLabel('lbl_no_name');
				}
			}
		}
		
		$res_objects->freeResult();
		
		// Object definitions
		
		if ($this->arr_columns_object_descriptions) {
			
			$arr_sql_object_descriptions = [];
			$version_select = $this->generateVersion('record', 'nodegoat_to_def');
			
			foreach ($this->arr_columns_object_descriptions as $object_description_id => $arr_columns) {

				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				$is_filtering = $this->isFilteringObjectDescription($object_description_id, true);
				
				$table_name_store = ($is_filtering ? $this->table_name : $this->table_name_objects);
				$column_name_from = ($arr_object_description['object_description_is_referenced'] ? "nodegoat_to_def.ref_object_id" : "nodegoat_to_def.object_id");
				$table_name_affix = StoreType::getValueTypeTable($arr_object_description['object_description_value_type']);
				$use_object_description_id = $arr_object_description['object_description_id']; // In case the object description is referenced

				$arr_sql_object_descriptions[] = "SELECT nodegoat_to.id AS object_id,
							".($arr_columns['select_id'] ? $arr_columns['select_id'] : "NULL")." AS select_id,
							".($arr_columns['select_value'] ? $arr_columns['select_value'] : "NULL")." AS select_value,
							".($arr_columns['sources'] ? DBFunctions::sqlImplode("CONCAT(nodegoat_to_def_src.ref_object_id, '".self::$sql_separator_group2."', nodegoat_to_def_src.ref_type_id, '".self::$sql_separator_group2."', nodegoat_to_def_src.value)", self::$sql_separator_group) : "NULL")." AS sources
						FROM ".$table_name_store." AS nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table_name_affix." AS nodegoat_to_def ON (".$column_name_from." = nodegoat_to.id AND nodegoat_to_def.object_description_id = ".$use_object_description_id." AND ".$version_select."
								".($is_filtering && !$arr_object_description['object_description_is_dynamic'] ? "AND ".($arr_object_description['object_description_ref_type_id'] ? "nodegoat_to.object_definition_".$object_description_id."_ref_object_id = nodegoat_to_def.ref_object_id" : "nodegoat_to.object_definition_".$object_description_id."_value = nodegoat_to_def.value") : "")."
							)
							".$arr_columns['tables']."
							".($arr_columns['sources'] ? "LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')." nodegoat_to_def_src ON (nodegoat_to_def_src.object_description_id = ".$use_object_description_id." AND nodegoat_to_def_src.object_id = ".$column_name_from.")" : "")."
					GROUP BY nodegoat_to.id, nodegoat_to_def.object_id, nodegoat_to_def.object_description_id".($table_name_affix == '_references' ? ", nodegoat_to_def.ref_object_id" : "").", nodegoat_to_def.identifier, nodegoat_to_def.version".($arr_columns['group'] ? ", ".$arr_columns['group'] : "")."
					ORDER BY nodegoat_to.id
				";
			}
			
			$arr_res_object_descriptions = DB::queryMulti(implode(';', $arr_sql_object_descriptions));
			
			foreach ($arr_res_object_descriptions as $key => $res) {
				
				$object_description_id = $arr_object_description_ids[$key];
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
				$is_ref_type_id = $arr_object_description['object_description_ref_type_id'];
				$has_multi = $arr_object_description['object_description_has_multi'];
				$is_dynamic = $arr_object_description['object_description_is_dynamic'];

				$arr_row = $res->fetchRow();
				
				$cur_object_id = (int)$arr_row[0];
				
				while ($arr_row) {
					
					$s_arr =& $arr[$cur_object_id]['object_definitions'][$object_description_id];
					
					$select_id = $arr_row[1];
					$select_value = $arr_row[2];
					
					if ($this->conditions == 'text' && $select_value && $is_ref_type_id) {
						$select_value = Response::addParsePost($select_value, ['strip' => true]);
					}
																
					if ($is_dynamic) {
						$s_arr['object_definition_ref_object_id'] = $func_parse_references('object_definition', $select_id, $object_description_id);
						$s_arr['object_definition_value'] = $select_value;
					} else if ($has_multi) {
						if ($select_id || $select_value) {
							$s_arr['object_definition_ref_object_id'][] = ($select_id === null ? null : (int)$select_id);
							$s_arr['object_definition_value'][] = $select_value;
						}
					} else {
						if ($select_id || $select_value != '') {
							$s_arr['object_definition_ref_object_id'] = ($select_id === null ? null : (int)$select_id);
							$s_arr['object_definition_value'] = $select_value;
						}
					}
					
					if ($arr_row[3]) {
						$s_arr['object_definition_sources'] = $func_parse_sources('object_definition', $arr_row[3]);
					}
										
					$arr_row = $res->fetchRow();
					
					$object_id = (int)$arr_row[0];
					
					if ($object_id != $cur_object_id) {
					
						if (!$this->arr_column_purpose[$object_description_id]['view']) {
							unset($arr[$cur_object_id]['object_definitions'][$object_description_id]);
						}
						
						$cur_object_id = $object_id;
					}
				}
				
				$res->freeResult();
			}
			
			unset($s_arr);
		}

		// Object Names
		
		if ($this->view != 'storage' && ($this->arr_columns_subs || $this->arr_type_object_name_object_description_ids || $this->arr_type_object_name_object_sub_description_ids)) {
			
			self::$do_check_shared_type_object_names = true;
			
			$sql = '';
			
			$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');
			
			if ($this->arr_columns_subs) {
					
				$arr_sql_object_subs = [];
				
				foreach ($this->arr_columns_subs as $object_sub_details_id => $value) {
						
					if ($this->isFilteringObjectSubDetails($object_sub_details_id, true)) {
						
						$arr_sql_object_subs[] = [$this->table_name, 'object_sub_'.$object_sub_details_id.'_id'];
					}
				}
				
				if ($sql_query_subs_preload) {
					
					$arr_sql_object_subs[] = [$table_name_nodegoat_tos, 'id'];
				}
				
				if ($arr_sql_object_subs) {
					
					$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos');
					
					if ($this->view == 'visualise') {
						$sql_column_location_type_id = $arr_sql_location['column_geometry_type_id'];
						$sql_column_location_object_id = $arr_sql_location['column_geometry_object_id'];
					} else if ($this->view == 'all') {
						$sql_column_location_type_id = $arr_sql_location['column_ref_show_type_id'];
						$sql_column_location_object_id = $arr_sql_location['column_ref_show_object_id'];
					} else {
						$sql_column_location_type_id = $arr_sql_location['column_ref_type_id'];
						$sql_column_location_object_id = $arr_sql_location['column_ref_object_id'];
					}
					
					foreach ($arr_sql_object_subs as $arr_sql_object_sub) {

						$sql .= "
							INSERT INTO ".self::$table_name_shared_type_object_names."
								(SELECT DISTINCT COALESCE(".$sql_column_location_type_id.", 0) AS type_id, COALESCE(".$sql_column_location_object_id.", 0) AS id, 0 AS state
									FROM ".$arr_sql_object_sub[0]." AS nodegoat_tos_store
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_store.".$arr_sql_object_sub[1]." AND ".$version_select_tos.")
									".$arr_sql_location['tables']."
								)
								".DBFunctions::onConflict('type_id, id', ['type_id'])."
							;
						";
					}
				}
			}
			
			if ($this->arr_type_object_name_object_description_ids) {
				
				$arr_sql = [];
				
				foreach ($this->arr_type_object_name_object_description_ids as $object_description_id) {
					
					$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
					
					if ($arr_object_description['object_description_is_dynamic']) {
						
						$arr_sql['dynamic'][] = $object_description_id;
					} else if ($arr_object_description['object_description_is_referenced']) {

						$arr_sql['referenced']['object_description_ids'][] = $arr_object_description['object_description_id']; // Get the real object description id
						$arr_sql['referenced']['type_ids'][] = "WHEN nodegoat_to_def.object_description_id = ".$arr_object_description['object_description_id']." THEN ".$arr_object_description['object_description_ref_type_id'];
					} else {
						
						$arr_sql['default']['object_description_ids'][] = $object_description_id;
						$arr_sql['default']['type_ids'][] = "WHEN nodegoat_to_def.object_description_id = ".$object_description_id." THEN ".$arr_object_description['object_description_ref_type_id'];
					}
				}
				
				if ($arr_sql['dynamic']) {
					
					$sql .= "
						INSERT INTO ".self::$table_name_shared_type_object_names."
							(SELECT DISTINCT nodegoat_to_def_ref.ref_type_id AS type_id, nodegoat_to_def_ref.ref_object_id AS id, 0 AS state
								FROM ".$this->table_name_objects." AS nodegoat_to_store
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." nodegoat_to_def_ref ON (nodegoat_to_def_ref.object_id = nodegoat_to_store.id AND nodegoat_to_def_ref.object_description_id IN (".implode(",", $arr_sql['dynamic']).") AND nodegoat_to_def_ref.state = 1)
							)
							".DBFunctions::onConflict('type_id, id', ['type_id'])."
						;
					";
				}
				if ($arr_sql['referenced']) {
					
					$sql .= "
						INSERT INTO ".self::$table_name_shared_type_object_names."
							(SELECT DISTINCT (CASE ".implode(" ", $arr_sql['referenced']['type_ids'])." END) AS type_id, nodegoat_to_def.object_id AS id, 0 AS state
								FROM ".$this->table_name_objects." AS nodegoat_to_store
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def ON (nodegoat_to_def.ref_object_id = nodegoat_to_store.id AND nodegoat_to_def.object_description_id IN (".implode(",", $arr_sql['referenced']['object_description_ids'])."))
							)
							".DBFunctions::onConflict('type_id, id', ['type_id'])."
						;
					";
				}
				if ($arr_sql['default']) {
		 
					$sql .= "
						INSERT INTO ".self::$table_name_shared_type_object_names."
							(SELECT DISTINCT (CASE ".implode(" ", $arr_sql['default']['type_ids'])." END) AS type_id, nodegoat_to_def.ref_object_id AS id, 0 AS state
								FROM ".$this->table_name_objects." AS nodegoat_to_store
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to_store.id AND nodegoat_to_def.object_description_id IN (".implode(",", $arr_sql['default']['object_description_ids'])."))
							)
							".DBFunctions::onConflict('type_id, id', ['type_id'])."
						;
					";
				}
			}
			
			if ($this->arr_type_object_name_object_sub_description_ids) {
				
				$arr_sql = [];
				
				foreach ($this->arr_type_object_name_object_sub_description_ids as $object_sub_details_id => $arr_object_sub_description_ids) {
					
					foreach ($arr_object_sub_description_ids as $object_sub_description_id) {
						
						$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
						
						if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
							
							$arr_sql['dynamic'][] = $object_sub_description_id;
						} else if ($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]) {
							
							if ($arr_object_sub_description['object_sub_description_is_referenced']) {
								
								$arr_sql['referenced']['object_sub_description_ids'][] = $arr_object_sub_description['object_sub_description_id']; // Get the real object description id
								$arr_sql['referenced']['type_ids'][] = "WHEN nodegoat_tos_def.object_sub_description_id = ".$arr_object_sub_description['object_sub_description_id']." THEN ".$arr_object_sub_description['object_sub_description_ref_type_id'];
							} else {
								
								$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id'];
								
								$arr_sql['referenced_sub'][$use_object_sub_details_id]['object_sub_description_ids'][] = $object_sub_description_id;
								$arr_sql['referenced_sub'][$use_object_sub_details_id]['type_ids'][] = "WHEN nodegoat_tos_def.object_sub_description_id = ".$object_sub_description_id." THEN ".$arr_object_sub_description['object_sub_description_ref_type_id'];
							}
						} else {
							
							$arr_sql['default']['object_sub_description_ids'][] = $object_sub_description_id;
							$arr_sql['default']['type_ids'][] = "WHEN nodegoat_tos_def.object_sub_description_id = ".$object_sub_description_id." THEN ".$arr_object_sub_description['object_sub_description_ref_type_id'];
						}
					}
				}
				
				if ($arr_sql['dynamic']) {
					
					$sql .= "
						INSERT INTO ".self::$table_name_shared_type_object_names."
							(SELECT DISTINCT nodegoat_tos_def_ref.ref_type_id AS type_id, nodegoat_tos_def_ref.ref_object_id AS id, 0 AS state
								FROM ".$this->table_name_objects." AS nodegoat_to_store
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_store.id AND ".$version_select_tos.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." nodegoat_tos_def_ref ON (nodegoat_tos_def_ref.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def_ref.object_sub_description_id IN (".implode(",", $arr_sql['dynamic']).") AND nodegoat_tos_def_ref.state = 1)
							)
							".DBFunctions::onConflict('type_id, id', ['type_id'])."
						;
					";
				}
				if ($arr_sql['referenced']) {
					
					$sql .= "
						INSERT INTO ".self::$table_name_shared_type_object_names."
							(SELECT DISTINCT (CASE ".implode(" ", $arr_sql['referenced']['type_ids'])." END) AS type_id, nodegoat_tos.object_id AS id, 0 AS state
								FROM ".$this->table_name_objects." AS nodegoat_to_store
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def ON (nodegoat_tos_def.ref_object_id = nodegoat_to_store.id AND nodegoat_tos_def.object_sub_description_id IN (".implode(",", $arr_sql['referenced']['object_sub_description_ids'])."))
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
							)
							".DBFunctions::onConflict('type_id, id', ['type_id'])."
						;
					";
				}
				if ($arr_sql['referenced_sub']) {
					
					foreach ($arr_sql['referenced_sub'] as $object_sub_details_id => $arr_sql_object_sub) {
						
						$sql .= "
							INSERT INTO ".self::$table_name_shared_type_object_names."
								(SELECT DISTINCT (CASE ".implode(" ", $arr_sql_object_sub['type_ids'])." END) AS type_id, nodegoat_tos_def.ref_object_id AS id, 0 AS state
									FROM ".$table_name_nodegoat_tos." AS nodegoat_tos_store
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_store.id AND nodegoat_tos.object_sub_details_id = ".$object_sub_details_id." AND ".$version_select_tos.")
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def.object_sub_description_id IN (".implode(",", $arr_sql_object_sub['object_sub_description_ids'])."))
								)
								".DBFunctions::onConflict('type_id, id', ['type_id'])."
							;
						";
					}
				}
				if ($arr_sql['default']) {
					
					$sql .= "
						INSERT INTO ".self::$table_name_shared_type_object_names."
							(SELECT DISTINCT (CASE ".implode(" ", $arr_sql['default']['type_ids'])." END) AS type_id, nodegoat_tos_def.ref_object_id AS id, 0 AS state
								FROM ".$this->table_name_objects." AS nodegoat_to_store
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_store.id AND ".$version_select_tos.")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def.object_sub_description_id IN (".implode(",", $arr_sql['default']['object_sub_description_ids'])."))
							)
							".DBFunctions::onConflict('type_id, id', ['type_id'])."
						;
					";
				}
			}
			
			DB::queryMulti($sql);
		}
				
		return $arr;
	}
	
	protected function initQuick() {
		
		$sql_query = $this->sqlQuery('object_ids');
		
		$this->initPre();
		
		$res_objects = DB::query($sql_query);
		
		$arr = [];
		
		while ($arr_row = $res_objects->fetchRow()) {
		
			$cur_object_id = $arr_row[0];
			$arr[$cur_object_id]['object'] = [
				'object_id' => $arr_row[0],
			];
		}
		
		return $arr;
	}
	
	protected function reInitPre() {
		
		if (!$this->arr_sql_pre_settings) {
			return;
		}
		
		$nr_batch = 0;
		$arr_batch_sql = [];
		$func_add_batch_sql = function($sql, $abortable = false) use (&$arr_batch_sql, &$nr_batch) {
			
			if ($this->is_user && $abortable) {
				$nr_batch++;
				$arr_batch_sql[$nr_batch] = $sql;
				$nr_batch++;
			} else {
				$arr_batch_sql[$nr_batch][] = $sql;
			}
		};
		
		foreach ($this->arr_sql_pre_settings as $identifier => $arr_sql_pre_setting) {
			
			if (!$arr_sql_pre_setting['dependent']) {
				continue;
			}
			
			if ($arr_sql_pre_setting['value']['table_name']) {
								
				$table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$arr_sql_pre_setting['value']['table_name']);
				
				if ($arr_sql_pre_setting['value']['query']) {
					
					$sql = "TRUNCATE TABLE ".$table_name.";
						INSERT INTO ".$table_name."
						(".rtrim($arr_sql_pre_setting['value']['query'], ';').")
					";
				} else {
					
					$sql = "TRUNCATE TABLE ".$table_name."";
				}
				
				$func_add_batch_sql($sql, ($arr_sql_pre_setting['abortable'] && $arr_sql_pre_setting['value']['query']));
				
				if ($arr_sql_pre_setting['value']['function']) {
					
					$func_add_batch_sql($arr_sql_pre_setting['value']['function']);
				}
						
				if ($arr_sql_pre_setting['value']['sql']) {
					
					$func_add_batch_sql(rtrim($arr_sql_pre_setting['value']['sql'], ';'), $arr_sql_pre_setting['abortable']);
				}
			}
		}		
		
		foreach ($arr_batch_sql as $sql) {
			
			if (is_callable($sql)) {
				
				$sql();
				
				if ($sql) {
					DB::queryMulti($sql);
				}
			} else if (is_array($sql)) {
				
				DB::queryMulti(implode(';', $sql));
			} else { // Abortable
				
				DB::queryAsync($sql);
			}
		}
	}
	
	protected function initPre() {
		
		if (!$this->arr_sql_pre_settings) {
			return;
		}

		$this->arr_sql_pre_queries[] = $this->sqlQuery('storage');
		
		foreach ($this->query_object_subs as $object_sub_details_id => $arr_tables_query_object_subs) {
			
			if (!$this->arr_selection['object_sub_details'][$object_sub_details_id]) {
				continue;
			}
			
			$this->arr_sql_pre_queries[] = $this->sqlQuerySub('object_sub_ids', $object_sub_details_id, 'foo');
		}
		
		$nr_batch = 0;
		$arr_batch_sql = [];
		$func_add_batch_sql = function($sql, $abortable = false) use (&$arr_batch_sql, &$nr_batch) {
			
			if ($this->is_user && $abortable || is_callable($sql)) {
				$nr_batch++;
				$arr_batch_sql[$nr_batch] = $sql;
				$nr_batch++;
			} else {
				$arr_batch_sql[$nr_batch][] = $sql;
			}
		};
		
		foreach ($this->arr_sql_pre_settings as $identifier => $arr_sql_pre_setting) {
			
			if ($arr_sql_pre_setting['value']['table_name']) {
				
				if (!self::$arr_pre_run[$identifier]) {
					
					self::$arr_pre_run[$identifier] = ['created' => false, 'persist' => false, 'abortable' => false, 'dependent' => false];
				} else if (self::$arr_pre_run[$identifier]['persist']) { // No need to check and/or make the table persist
					
					continue;
				}
				
				$table_name = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.'.$arr_sql_pre_setting['value']['table_name']);
				
				$persist = ($arr_sql_pre_setting['persist'] || self::$keep_tables);
				
				if (DB::ENGINE_IS_MYSQL && !$persist) { // MySQL only: check for multiple temporary table uses in the queries, if so, make them persist
					
					foreach ($this->arr_sql_pre_queries as $sql_query) {
							
						if (substr_count($sql_query, $table_name) > 1) {
							
							$persist = true;
							break;
						}
					}
					
					if (!$persist) {
						
						foreach ($this->arr_sql_pre_settings as $identifier_check => $arr_sql_pre_setting_check) {
							
							if ($identifier_check == $identifier || self::$arr_pre_run[$identifier_check]) {
								continue;
							}

							if (substr_count($arr_sql_pre_setting_check['value']['query'], $table_name) > 1 || substr_count($arr_sql_pre_setting_check['value']['sql'], $table_name) > 1) {
								
								$persist = true;
								break;
							}
						}
					}
				}
				
				if (self::$arr_pre_run[$identifier]['created']) {
					
					if ($persist) {
						
						self::$arr_storage_tables[$table_name]['temporary'] = false;
						
						$func_add_batch_sql("
							ALTER TABLE ".$table_name." RENAME TO ".$table_name."_old;
							CREATE TABLE ".$table_name." LIKE ".$table_name."_old;
							INSERT INTO ".$table_name." SELECT * FROM ".$table_name."_old;
							DROP TABLE ".$table_name."_old
						");
					}
				} else {
					
					$is_temporary = !$persist;
					
					if (self::$arr_storage_tables[$table_name]) {
						
						$existing_is_temporary = self::$arr_storage_tables[$table_name]['temporary'];
						
						$sql_drop = self::dropResult($table_name, true);
						$func_add_batch_sql($sql_drop);
						
						if (!$existing_is_temporary && $is_temporary) { // Make sure the old non-temporary table is still on record, it might still need to be dropped at cleanup if something unexpected happens
							self::$arr_storage_tables[$table_name.'_drop'] = ['temporary' => false, 'alias' => $table_name];
						}
					}
					
					self::$arr_storage_tables[$table_name] = ['temporary' => $is_temporary];
					
					$arr_sql_settings = $arr_sql_pre_setting['value']['settings'];
					$arr_sql_settings = (is_array($arr_sql_settings) ? $arr_sql_settings : ['columns' => $arr_sql_settings]);
					
					$sql = "CREATE ".($is_temporary ? "TEMPORARY" : "")." TABLE ".$table_name." (
							".$arr_sql_settings['columns']."
						) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
							".($arr_sql_pre_setting['value']['query'] ? (DB::ENGINE_IS_MYSQL ?
								"AS (".rtrim($arr_sql_pre_setting['value']['query'], ';').")"
								:
								"; INSERT INTO ".$table_name." ".($arr_sql_settings['select'] ? "(".implode(',', $arr_sql_settings['select']).")" : "")."
									(".rtrim($arr_sql_pre_setting['value']['query'], ';').")
								")
							: "")."
					;";
					
					$sql .= ($arr_sql_settings['indexes'] ?: '');
					
					$func_add_batch_sql(rtrim($sql, ';'), ($arr_sql_pre_setting['abortable'] && $arr_sql_pre_setting['value']['query']));
						
					if ($arr_sql_pre_setting['value']['function']) {
						
						$func_add_batch_sql($arr_sql_pre_setting['value']['function']);
					}
						
					if ($arr_sql_pre_setting['value']['sql']) {
						
						$func_add_batch_sql(rtrim($arr_sql_pre_setting['value']['sql'], ';'), $arr_sql_pre_setting['abortable']);
					}
					
					self::$arr_pre_run[$identifier]['created'] = true;
				}
									
				if ($persist) {

					self::$arr_pre_run[$identifier]['persist'] = true;
				}
			} else {
				
				if (self::$arr_pre_run[$identifier]) {
					continue;
				}
				
				self::$arr_pre_run[$identifier] = true;

				$func_add_batch_sql(rtrim($arr_sql_pre_setting['value']['query'], ';'), $arr_sql_pre_setting['abortable']);
								
				if ($arr_sql_pre_setting['value']['sql']) {
					
					$func_add_batch_sql(rtrim($arr_sql_pre_setting['value']['sql'], ';'), $arr_sql_pre_setting['abortable']);
				}
			}
		}
		
		foreach ($arr_batch_sql as $sql) {
			
			if (is_callable($sql)) {
				
				$sql = $sql();
				
				if ($sql) {
					DB::queryMulti($sql);
				}
			} else if (is_array($sql)) {
				
				DB::queryMulti(implode(';', $sql));
			} else { // Abortable
				
				DB::queryAsync($sql);
			}
		}
	}
	
	public function addPre($identifier, $arr_value, $persist = false, $abortable = false, $dependent = false) {
		
		$arr_value = (is_array($arr_value) ? $arr_value : ['query' => $arr_value]);
		$persist = ($persist ?: (DB::ENGINE_IS_MYSQL && $this->isFilteringObject()));

		$this->arr_sql_pre_settings[$identifier] = ['value' => $arr_value, 'persist' => $persist, 'abortable' => $abortable, 'dependent' => $dependent];
	}
	
	public function getPre() {
		
		return $this->arr_sql_pre_settings;
	}
		
	public function sqlQuery($type = 'full') {
		
		$this->useSettings();
		$this->generateTablesColumns();
		
		$is_filtering = false;
		
		$sql_select = '';
		$sql_group = '';
		$sql_tables = '';
		
		if ($type == 'full') {
			$sql_select = implode(",", $this->arr_columns_object_as);
		} else if ($type == 'count') {
			$sql_select = 'nodegoat_to.id';
		} else if ($type == 'object_ids') {
			$sql_select = 'nodegoat_to.id';
		} else if ($type == 'storage') {
			
			$arr_select = [];
			$arr_group = [];
			
			$arr_select[] = 'nodegoat_to.id';
			$arr_group[] = 'nodegoat_to.id, nodegoat_to.version';
			
			$purpose = ($this->arr_filtering_filters === true ? false : 'filtering');
			
			if ($this->isFilteringObject()) {
				
				foreach ((array)$this->query_object as $arr_table_info) {
					
					if ($arr_table_info['purpose'] != $purpose) {
						continue;
					}
					
					$column_name = "id_".$arr_table_info['filter_code'];
					$arr_select[] = "CASE WHEN ".($arr_table_info['arr_sql']['sql_filtering'] ? $arr_table_info['arr_sql']['sql_filtering']." AND" : "")." ".$arr_table_info['arr_sql']['sql_operator_not']." ".$arr_table_info['arr_sql']['sql_filter']." THEN 1 ELSE 0 END AS ".$column_name;
					$arr_group[] = $column_name;
				}
			}
			
			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if ($this->isFilteringObjectDescription($object_description_id)) {
					
					if ($arr_object_description['object_description_is_dynamic']) {
						
						$column_name = "object_definition_".$object_description_id."_ref_object_id";
						$arr_select[] = "nodegoat_to_def_".$object_description_id."_objects.ref_object_id AS ".$column_name;
						$arr_group[] = $column_name;
					} else if ($arr_object_description['object_description_ref_type_id']) {
						
						$column_name = "object_definition_".$object_description_id."_ref_object_id";
						$arr_select[] = "nodegoat_to_def_".$object_description_id.".ref_object_id AS ".$column_name;
						$arr_group[] = $column_name;
					} else {
						
						$column_name = "object_definition_".$object_description_id."_value";
						$arr_select[] = "nodegoat_to_def_".$object_description_id.".value AS object_definition_".$object_description_id."_value";
						$arr_group[] = $column_name;
					}
					
					$is_filtering = true;
				}				
			}
			
			$sql_template = ($this->arr_sql_filter_purpose[$purpose] ? implode(' ', $this->arr_sql_filter_purpose[$purpose]) : '');
					
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if ($this->isFilteringObjectSubDetails($object_sub_details_id)) {
					
					$sql_template_sub = false;
					
					if ($this->arr_sql_filter_general_purpose[$purpose]) {
						
						$sql_template_sub = $sql_template;
						
						foreach ($this->arr_sql_filter_general_purpose[$purpose] as $filter_code => $sql_value_general) {
							
							$sql_value_sub = $this->arr_sql_filter_subs_purpose[$purpose][$object_sub_details_id][$filter_code];
							
							$sql_template_sub = str_replace($sql_value_general, $sql_value_sub, $sql_template_sub);
						}
					}
									
					$column_name = "object_sub_".$object_sub_details_id."_id";
					$arr_select[] = ($sql_template_sub ? "CASE WHEN ".$sql_template_sub." THEN nodegoat_tos_".$object_sub_details_id.".id ELSE 0 END" : "nodegoat_tos_".$object_sub_details_id.".id")." AS ".$column_name;
					$arr_group[] = $column_name;
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
						if ($this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id)) {
							
							if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
								
								$column_name = "object_sub_definition_".$object_sub_description_id."_ref_object_id";
								$arr_select[] = "nodegoat_tos_def_".$object_sub_description_id."_objects.ref_object_id AS ".$column_name;
								$arr_group[] = $column_name;
							} else if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
								
								$column_name = "object_sub_definition_".$object_sub_description_id."_ref_object_id";
								$arr_select[] = "nodegoat_tos_def_".$object_sub_description_id.".ref_object_id AS ".$column_name;
								$arr_group[] = $column_name;
							} else {
								
								$column_name = "object_sub_definition_".$object_sub_description_id."_value";
								$arr_select[] = "nodegoat_tos_def_".$object_sub_description_id.".value AS ".$column_name;
								$arr_group[] = $column_name;
							}
						}
					}
					
					$is_filtering = true;
				}
			}
			
			$sql_select = implode(',', $arr_select);
			$sql_group = implode(',', $arr_group);
		} else if (is_array($type)) {
			
			if ($type['columns']) {
				$sql_select = implode(',', $type['columns']);
				$sql_group = ($type['group'] ? implode(',', $type['group']) : '');
				$sql_tables = ($type['tables'] ? implode(' ', $type['tables']) : '');
			} else {
				$sql_select = implode(',', $type);
			}
			
			$type = 'custom';
		}
		
		$sql_order = '';
		$sql_limit = '';
		
		$sql_group_default = "nodegoat_to.id, nodegoat_to.version";
		$sql_group_extra = '';
		
		if ($type == 'full' || $type == 'object_ids' || $type == 'storage') {
			
			if ($this->arr_sql_order) {
				$sql_order = "ORDER BY ".implode(',', $this->arr_sql_order).", nodegoat_to.id DESC";
			} else if ($this->arr_limit) {
				$sql_order = "ORDER BY nodegoat_to.id DESC";
			}
			
			if ($this->arr_sql_group) {
				$sql_group_extra .= ",".implode(',', $this->arr_sql_group);
			}
			
			if ($this->sql_limit && !$is_filtering) {
				$sql_limit = $this->sql_limit;
			}
		}
		
		$version_select = $this->generateVersion('object', 'nodegoat_to');
		$arr_sql_queries = [];
		
		$sql_table = DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS');
		$sql_table_where = "nodegoat_to.type_id = ".(int)$this->type_id." AND ".$version_select;
		
		foreach ((array)$this->arr_sql_filter['table'] as $key => $arr_table) {
			
			if ($arr_table['is_source']) {
				
				$sql_table = $arr_table['table_name'];
				$sql_table_where = ($arr_table['query'] ?: "TRUE");
				$sql_group_default = "nodegoat_to.id";
				continue;
			}
			
			$table_alias = ($arr_table['table_alias'] ?: "t".$key);
			
			$arr_sql_queries[] = "JOIN ".$arr_table['table_name']." AS ".$table_alias." ON (".$table_alias.".id = nodegoat_to.id".($arr_table['query'] ? " AND (".$arr_table['query'].")" : "").")";
		}
		
		if ($arr_sql_queries) {
			$sql_tables .= implode(' ', $arr_sql_queries);
		}
		
		$query = "SELECT ".$sql_select."
			FROM ".$sql_table." AS nodegoat_to
				".$sql_tables."
				".($this->arr_sql_filter['search'] ? "JOIN (".$this->arr_sql_filter['search'].") s ON (s.id = nodegoat_to.id)" : "")."
				".($type == 'storage' || $type == 'custom' ? $this->arr_tables['filtering_object'].$this->arr_tables['filtering_object_more'] : $this->arr_tables['query_object'].$this->arr_tables['query_object_more'])."
			WHERE ".$sql_table_where."
				".($this->arr_sql_filter['objects'] ? "AND nodegoat_to.id IN (".implode(',', $this->arr_sql_filter['objects']).")" : "")."
				".($this->arr_sql_filter['filter'] ? "AND (".implode(" AND ", $this->arr_sql_filter['filter']).")" : "")."
				".($this->arr_sql_filter['date'] ? "AND (".implode(" AND ", $this->arr_sql_filter['date']).")" : "")."
			GROUP BY ".($sql_group ?: $sql_group_default).$sql_group_extra."
			".$sql_order."
			".$sql_limit."
		";
		
		if ($type == 'count') {
			
			$query = "SELECT COUNT(nodegoat_to.id) FROM (".$query.") nodegoat_to";
		}

		return $query;
	}
	
	public function sqlKeys($type = 'storage', $table_name) {
		
		$is_filtering = $this->isFilteringObject();
		$has_filtering = false;
		
		$arr_sql_columns = [
			'auto_id '.(DB::ENGINE_IS_MYSQL ? 'INT AUTO_INCREMENT' : 'SERIAL'),
			'id INT'
		];
		$arr_sql_key = ['auto_id'];
		$arr_sql_index = ['id'];
		$arr_sql_select = ['id'];
		
		$purpose = ($this->arr_filtering_filters === true ? false : 'filtering');
		
		if ($is_filtering) {
				
			foreach ((array)$this->query_object as $arr_table_info) {
				
				if ($arr_table_info['purpose'] != $purpose) {
					continue;
				}
				
				$column_name = "id_".$arr_table_info['filter_code'];
				$arr_sql_columns[] = $column_name." SMALLINT";
				$arr_sql_index[] = $column_name;
				$arr_sql_select[] = $column_name;
				
				$has_filtering = true;
			}
		}

		foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if ($this->isFilteringObjectDescription($object_description_id)) {
				
				if ($arr_object_description['object_description_ref_type_id'] || $arr_object_description['object_description_is_dynamic']) {
					
					$column_name = "object_definition_".$object_description_id."_ref_object_id";
					$arr_sql_columns[] = $column_name." INT";
				} else {
					
					$column_name = "object_definition_".$object_description_id."_value";
					$arr_sql_columns[] = $column_name." VARCHAR(5000) ".(DB::ENGINE_IS_MYSQL ? "COLLATE utf8mb4_unicode_ci" : '');
				}
				
				$arr_sql_index[] = $column_name;
				$arr_sql_select[] = $column_name;
				
				$has_filtering = true;
			}				
		}
		
		foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			if ($this->isFilteringObjectSubDetails($object_sub_details_id)) {
				
				$column_name = "object_sub_".$object_sub_details_id."_id";
				$arr_sql_columns[] = $column_name." INT";
				$arr_sql_index[] = $column_name;
				$arr_sql_select[] = $column_name;
				
				$has_filtering = true;
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
					if ($this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id)) {
						
						if ($arr_object_sub_description['object_sub_description_ref_type_id'] || $arr_object_sub_description['object_sub_description_is_dynamic']) {

							$column_name = "object_sub_definition_".$object_sub_description_id."_ref_object_id";
							$arr_sql_columns[] = $column_name." INT";
						} else {
							
							$column_name = "object_sub_definition_".$object_sub_description_id."_value";
							$arr_sql_columns[] = $column_name." VARCHAR(5000) ".(DB::ENGINE_IS_MYSQL ? "COLLATE utf8mb4_unicode_ci" : '');
						}
						
						$arr_sql_index[] = $column_name;
						$arr_sql_select[] = $column_name;
						
						$has_filtering = true;
					}
				}
			}
		}
		
		$sql_columns = implode(',', $arr_sql_columns).",
			PRIMARY KEY (".implode(',', $arr_sql_key).")";
						
		foreach ($arr_sql_index as &$sql_index) {
			
			$sql_index = DBFunctions::createIndex($table_name, $sql_index);
		}
		unset($sql_index);
			
		$sql_indexes = ($arr_sql_index ? implode(';', $arr_sql_index).";" : '');
						
		return ['columns' => $sql_columns, 'indexes' => $sql_indexes, 'select' => $arr_sql_select, 'has_filtering' => $has_filtering];
	}
	
	public function sqlQuerySub($type = 'full', $object_sub_details_id, $table_name_nodegoat_to) {
		
		$this->useSettings();
		$this->generateTablesColumns();
		
		$table_name = "nodegoat_tos_".$object_sub_details_id;
				
		if ($type == 'full') {
			$sql_select = implode(',', $this->arr_columns_subs_as[$object_sub_details_id]);
			$sql_group = $table_name.'.id, '.$table_name.'.version'.($this->arr_columns_subs_group[$object_sub_details_id] ? ', '.implode(',', $this->arr_columns_subs_group[$object_sub_details_id]) : '');
		} else if ($type == 'object_sub_ids' || $type == 'count_object_sub_ids' || $type == 'count_object_sub_ids_object_ids_referenced_object_sub_description_ids') {
			$sql_select = $table_name.'.id';
			$sql_group = $table_name.'.id, '.$table_name.'.version';
		} else if ($type == 'object_ids_object_sub_ids') {
			$sql_select = 'nodegoat_to.id AS object_id, '.$table_name.'.id';
			$sql_group = 'object_id, '.$table_name.'.id, '.$table_name.'.version';
		} else if ($type == 'object_sub_ids_object_ids_referenced_object_sub_description_ids') {
			$sql_select = $table_name.'.id, nodegoat_to.id AS object_id, '.($object_sub_details_id == 'all' ? $table_name.'_all.referenced_object_sub_description_id' : $table_name.'_def.object_sub_description_id').' AS referenced_object_sub_description_id';
			$sql_group = 'object_id, '.$table_name.'.id, '.$table_name.'.version';
		}
		
		$version_select_tos = $this->generateVersion('object_sub', $table_name);
		$version_select_tos_to = $this->generateVersion('object', $table_name.'_to');

		if ($type == 'object_sub_ids_object_ids_referenced_object_sub_description_ids' || $type == 'count_object_sub_ids_object_ids_referenced_object_sub_description_ids') {
			
			$arr_referenced_object_sub_description_ids = $this->generateReferencedObjectSubDescriptionIds($object_sub_details_id);
			
			$version_select = $this->generateVersion('record', $table_name."_def");
			
			if ($object_sub_details_id == 'all') {
				
				$table_name_all = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.temp_referenced_object_subs');
				
				DB::queryMulti("
					CREATE TEMPORARY TABLE IF NOT EXISTS ".$table_name_all." (
						id INT,
						object_sub_id INT,
						referenced_object_sub_description_id INT,
							PRIMARY KEY (id, object_sub_id)
					) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
					
					TRUNCATE TABLE ".$table_name_all.";
					
					INSERT INTO ".$table_name_all." (id, object_sub_id, referenced_object_sub_description_id)
						SELECT nodegoat_to.id, ".$table_name.".id AS object_sub_id, 0 AS referenced_object_sub_description_id
							FROM ".$table_name_nodegoat_to." AS nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to.id AND object_sub_details_id ".($object_sub_details_id == 'all' ? 'IN ('.implode(',', $this->arr_selection_object_sub_details_ids).')' : "= ".$object_sub_details_id)." AND ".$version_select_tos.")
					;
					
					INSERT INTO ".$table_name_all." (id, object_sub_id, referenced_object_sub_description_id)
						SELECT nodegoat_to.id, ".$table_name."_def.object_sub_id, ".$table_name."_def.object_sub_description_id AS referenced_object_sub_description_id
							FROM ".$table_name_nodegoat_to." AS nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$table_name."_def ON (".$table_name."_def.ref_object_id = nodegoat_to.id AND ".$table_name."_def.object_sub_description_id IN (".implode(',', $arr_referenced_object_sub_description_ids).") AND ".$version_select.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".id = ".$table_name."_def.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." ".$table_name."_to ON (".$table_name."_to.id = ".$table_name.".object_id AND ".$version_select_tos_to.")
					;
				");

				$sql_join = "LEFT JOIN ".$table_name_all." ".$table_name."_all ON (".$table_name."_all.id = nodegoat_to.id)
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".id = ".$table_name."_all.object_sub_id AND ".$version_select_tos.")
				";
			} else {
								
				$sql_join = "LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$table_name."_def ON (".$table_name."_def.ref_object_id = nodegoat_to.id AND ".$table_name."_def.object_sub_description_id IN (".implode(',', $arr_referenced_object_sub_description_ids).") AND ".$version_select.")
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".id = ".$table_name."_def.object_sub_id AND ".$version_select_tos.")
					JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." ".$table_name."_to ON (".$table_name."_to.id = ".$table_name.".object_id AND ".$version_select_tos_to.")
				";
			}
		} else {
			
			$sql_join = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to.id AND ".$version_select_tos.")";
		}
		
		$sql_order = '';
		$sql_limit = '';
		
		if ($type == 'full' || $type == 'object_sub_ids' || $type == 'object_ids_object_sub_ids' || $type == 'object_sub_ids_object_ids_referenced_object_sub_description_ids') {
			
			if ($this->arr_order_object_subs[$object_sub_details_id]) {
				
				$sql_order_all = '';
				
				if ($object_sub_details_id == 'all') {
					
					$count = 0;
					$sql_order_all = "CASE ".$table_name.".object_sub_details_id";
					
					foreach ($this->arr_selection_object_sub_details_ids as $cur_object_sub_details_id) {
						$sql_order_all .= " WHEN ".$cur_object_sub_details_id." THEN ".$count;
						$count++;
					}
					
					$sql_order_all .= " ELSE ".$count." END";
				}
				
				$sql_order = "ORDER BY ";
				
				foreach ($this->arr_order_object_subs[$object_sub_details_id] as $key => $value) {
					
					$sql_order .= $this->arr_columns_subs[$object_sub_details_id][$key]." ".$value.", ";
					$sql_group .= ", ".$this->arr_columns_subs[$object_sub_details_id][$key];
				}
				
				$sql_order .= ($sql_order_all ? $sql_order_all.", " : "").$table_name.".id ASC";
			}
			
			$sql_limit = $this->sql_limit_object_subs[$object_sub_details_id];
		}
		
		$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id']; // In case the sub-object is referenced
				
		$query = "SELECT ".$sql_select."
					FROM ".$table_name_nodegoat_to." AS nodegoat_to
					".$sql_join."
					".$this->arr_tables['query_object_subs'][$object_sub_details_id].$this->arr_tables['query_object_subs_more'][$object_sub_details_id]."
					".$this->arr_tables['select_object_subs'][$object_sub_details_id]."
				WHERE ".$table_name.".object_sub_details_id ".($object_sub_details_id == 'all' ? "IN (".implode(',', $this->arr_selection_object_sub_details_ids).")" : "= ".$use_object_sub_details_id)."
					".($this->arr_sql_filter['object_subs'] ? "AND ".$table_name.".id IN (".implode(',', $this->arr_sql_filter['object_subs']).")" : "")."
					".($this->arr_sql_filter_subs[$object_sub_details_id]['filter'] ? "AND ".implode(' AND ', $this->arr_sql_filter_subs[$object_sub_details_id]['filter']) : '')."
				GROUP BY ".$sql_group."
				".$sql_order."
				".$sql_limit."
		";
		
		if ($type == 'count_object_sub_ids' || $type == 'count_object_sub_ids_object_ids_referenced_object_sub_description_ids') {
			
			$query = "SELECT COUNT(nodegoat_tos.id) FROM (".$query.") nodegoat_tos";
		}
			
		return $query;
	}
	
	private function useSettings() {
		
		if ($this->settings_used) {
			return;
		}
		$this->settings_used = true;
		
		self::useStaticSettings();
		
		// Pre-selection sub-objects
			
		if (is_array($this->arr_selection['object_sub_details']) && (!$this->arr_selection['object_sub_details']['all'] || count($this->arr_selection['object_sub_details']) > 1)) { // Make sure there is more than only 'all' in the selection
			
			$this->arr_selection_object_sub_details_ids = $this->arr_selection['object_sub_details'];
		} else {
			
			$this->arr_selection_object_sub_details_ids = $this->arr_type_set['object_sub_details'];
			
			// Include referenced sub-object ids
			foreach ((array)$this->arr_type_set['type']['include_referenced']['object_sub_details'] as $object_sub_details_id => $arr_object_sub_description_ids) {
										
				unset($this->arr_selection_object_sub_details_ids[$object_sub_details_id]);
				
				$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id'];
				$this->arr_selection_object_sub_details_ids[$use_object_sub_details_id] = $use_object_sub_details_id;
			}
		}
		
		if ($this->arr_filter_object_sub_details_ids) {
			
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if ($this->arr_filter_object_sub_details_ids[$object_sub_details_id]) {
					continue;
				}
				
				$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id']; // In case the sub-object is referenced
				
				unset($this->arr_selection_object_sub_details_ids[$use_object_sub_details_id]);
			}
		}
			
		unset($this->arr_selection_object_sub_details_ids['all']);
		$this->arr_selection_object_sub_details_ids = array_keys($this->arr_selection_object_sub_details_ids);
		
		// Selection
		
		$arr_selection_settings = $this->arr_selection;
		$this->arr_selection = ['object' => ['all' => true], 'object_descriptions' => [], 'object_sub_details' => []];
		
		if (is_array($arr_selection_settings['object'])) {
			$this->arr_selection['object'] = $arr_selection_settings['object'];
		}
		
		if ($this->view == 'visualise' || $this->view == 'overview' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') {
			
			$has_selected_object_descriptions = is_array($arr_selection_settings['object_descriptions']);
				
			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$in_preselection = (
					(($this->view == 'overview' && $arr_object_description['object_description_in_overview']) || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage')
				);
				
				if ((!$has_selected_object_descriptions && !$in_preselection) || ($has_selected_object_descriptions && !$arr_selection_settings['object_descriptions'][$object_description_id])) {
					continue;
				}
				
				$select_value = true;
				$select_reference = true;
				
				if ($has_selected_object_descriptions) {
					
					$arr_selection = $arr_selection_settings['object_descriptions'][$object_description_id];
					$has_selected_specific = (is_array($arr_selection) && ($arr_selection['object_description_value'] || $arr_selection['object_description_reference']));
					
					$select_value = (!$has_selected_specific ? true : $arr_selection['object_description_value']);
					$select_reference = (!$has_selected_specific ? true : $arr_selection['object_description_reference']);
					
					if (is_array($select_reference)) {
						$select_reference = arrParseRecursive($select_reference, 'int');
					}
				}
				
				$this->arr_selection['object_descriptions'][$object_description_id] = ['object_description_value' => $select_value, 'object_description_reference' => $select_reference];
			}
		}
		
		if ($this->view == 'visualise' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') {
			
			$has_selected = is_array($arr_selection_settings['object_sub_details']);
			
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$is_selected = ($has_selected && $arr_selection_settings['object_sub_details'][$object_sub_details_id]);
				$has_filtered = $this->arr_filter_object_sub_details_ids;
				$is_filtered = ($has_filtered && $this->arr_filter_object_sub_details_ids[$object_sub_details_id]);
				
				if (($has_selected && !$is_selected) || (!$has_selected && $has_filtered && !$is_filtered)) {
					continue;
				}
				
				$arr_selected_self = $arr_selection_settings['object_sub_details'][$object_sub_details_id]['object_sub_details'];

				$this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = (is_array($arr_selected_self) ? $arr_selected_self : ['all' => true]);
				
				$has_selected_object_sub_descriptions = is_array($arr_selection_settings['object_sub_details'][$object_sub_details_id]['object_sub_descriptions']);

				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$in_preselection = ($this->view == 'all' || $this->view == 'set' || $this->view == 'storage');
					
					if ((!$has_selected_object_sub_descriptions && !$in_preselection) || ($has_selected_object_sub_descriptions && !$arr_selection_settings['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id])) {
						continue;
					}

					$select_value = true;
					$select_reference = true;
					
					if ($has_selected_object_sub_descriptions) {
						
						$arr_selection = $arr_selection_settings['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
						$has_selected_specific = (is_array($arr_selection) && ($arr_selection['object_sub_description_value'] || $arr_selection['object_sub_description_reference']));
						
						$select_value = (!$has_selected_specific ? true : $arr_selection['object_sub_description_value']);
						$select_reference = (!$has_selected_specific ? true : $arr_selection['object_sub_description_reference']);
						
						if (is_array($select_reference)) {
							$select_reference = arrParseRecursive($select_reference, 'int');
						}
					}
					
					$this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = ['object_sub_description_value' => $select_value, 'object_sub_description_reference' => $select_reference];
				}
			}
		}

		// Conditions
		
		if ($this->arr_type_set_conditions) {
				
			$arr_conditions_collect = [];
			
			$func_process_condition_action_values = function(&$arr_condition_setting, $group) {
				
				if ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id']) {
				
					$arr_value = explode('_', $arr_condition_setting['condition_actions']['weight']['number_use_object_description_id']);
					
					if ($arr_value[1] == 'sub') {
						
						if ($group == 'object') {
							// Not possible
						} else {
							$arr_condition_setting['condition_value']['object_sub_description_id'] = $arr_value[3];
						}
					} else {

						$arr_condition_setting['condition_value']['object_description_id'] = $arr_value[2];
					}
				}
				
				if ($arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id']) {
					
					$arr_condition_setting['condition_value']['object_analysis_id'] = $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'];
				}
			};
			
			if ($this->arr_type_set_conditions['object']) {
				
				foreach ($this->arr_type_set_conditions['object'] as $key => &$arr_condition_setting) {
					
					if ($arr_condition_setting['condition_in_object_nodes_object'] && $this->view != 'visualise') {
						
						unset($this->arr_type_set_conditions['object'][$key]);
						continue;
					}

					$func_process_condition_action_values($arr_condition_setting, 'object');
					
					$arr_condition_setting['condition_group'] = 'object';
					$arr_conditions_collect[] =& $arr_condition_setting;
				}
			}
			if ($this->arr_type_set_conditions['object_descriptions']) {
				
				foreach ($this->arr_type_set_conditions['object_descriptions'] as $object_description_id => &$arr_condition_settings) {
					
					$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
					
					foreach ($arr_condition_settings as $key => &$arr_condition_setting) {
						
						if (
							(!$arr_condition_setting['condition_in_object_name'] && !$this->arr_selection['object_descriptions'][$object_description_id])
							||
							($arr_condition_setting['condition_in_object_nodes_referencing'] && $this->view != 'visualise')
						) {
							
							unset($arr_condition_settings[$key]);
							continue;
						}
						
						$func_process_condition_action_values($arr_condition_setting, 'object');
						
						$arr_condition_setting['condition_group'] = 'object';
						$arr_condition_setting['object_description_id'] = $object_description_id;
						$arr_conditions_collect[] =& $arr_condition_setting;
					}
					
					if (!$arr_condition_settings) {
						unset($this->arr_type_set_conditions['object_descriptions'][$object_description_id]);
					}
				}
			}
			if ($this->arr_type_set_conditions['object_sub_details']) {
				
				foreach ($this->arr_type_set_conditions['object_sub_details'] as $object_sub_details_id => &$arr_conditions_object_sub_details) {
					
					$in_selection_object_sub_details = (bool)$this->arr_selection['object_sub_details'][$object_sub_details_id];

					if ($arr_conditions_object_sub_details['object_sub_details']) {
						
						if (!$in_selection_object_sub_details) {
						
							unset($arr_conditions_object_sub_details['object_sub_details']);
						} else {
							
							foreach ($arr_conditions_object_sub_details['object_sub_details'] as $key => &$arr_condition_setting) {
								
								if ($arr_condition_setting['condition_in_object_nodes_object'] && $this->view != 'visualise') {
									
									unset($arr_conditions_object_sub_details['object_sub_details'][$key]);
									continue;
								}
								
								$func_process_condition_action_values($arr_condition_setting, 'object_sub_details');

								$arr_condition_setting['condition_group'] = 'object_sub_details';
								$arr_condition_setting['object_sub_details_id'] = $object_sub_details_id;
								$arr_conditions_collect[] =& $arr_condition_setting;
							}
							
							if (!$arr_conditions_object_sub_details['object_sub_details']) {
								unset($arr_conditions_object_sub_details['object_sub_details']);
							}
						}
					}
					
					if ($arr_conditions_object_sub_details['object_sub_descriptions']) {
						
						foreach ($arr_conditions_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => &$arr_condition_settings) {
							
							$in_selection_object_sub_description = ($in_selection_object_sub_details && $this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]);

							foreach ($arr_condition_settings as $key => &$arr_condition_setting) {
								
								if (
									(!$arr_condition_setting['condition_in_object_name'] && !$in_selection_object_sub_description)
									||
									($arr_condition_setting['condition_in_object_nodes_referencing'] && $this->view != 'visualise')
								) {
									
									unset($arr_condition_settings[$key]);
									continue;
								}
								
								$func_process_condition_action_values($arr_condition_setting, 'object_sub_details');

								$arr_condition_setting['condition_group'] = ($arr_condition_setting['condition_in_object_name'] ? 'object' : 'object_sub_details');
								$arr_condition_setting['object_sub_details_id'] = $object_sub_details_id;
								$arr_condition_setting['object_sub_description_id'] = $object_sub_description_id;
								$arr_conditions_collect[] =& $arr_condition_setting;
							}
							
							if (!$arr_condition_settings) {
								unset($arr_conditions_object_sub_details['object_sub_descriptions'][$object_sub_description_id]);
							}
						}
						
						if (!$arr_conditions_object_sub_details['object_sub_descriptions']) {
							unset($arr_conditions_object_sub_details['object_sub_descriptions']);
						}
					}
					
					if (!$arr_conditions_object_sub_details) {
						unset($this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id]);
					}
				}
			}
			
			foreach ($arr_conditions_collect as &$arr_condition_setting) {

				if ($arr_condition_setting['condition_filter']) {
						
					$arr_filter = FilterTypeObjects::convertFilterInput($arr_condition_setting['condition_filter']);
					
					$filter_condition = new FilterTypeObjects($this->type_id, 'id');
					$filter_condition->setScope($this->arr_scope);
					$filter_condition->setDifferentiationId($this->getDifferentiationId());
					
					$filter_condition->setFilter([
						['table' => [
							['table_name' => '[X]', 'is_source' => true]]
						]
					]);
					$filter_condition->setFilter($arr_filter);
										
					$condition_key = 1 + count($this->arr_columns_object_conditions);
					
					if ($arr_condition_setting['condition_group'] == 'object') {
						
						$sql_filter = $filter_condition->sqlQuery(['nodegoat_to.id', '1', $condition_key]);

						$this->arr_columns_object_conditions[] = ['condition_key' => $condition_key, 'sql' => $sql_filter, 'condition_group' => $arr_condition_setting['condition_group']];
					} else {
						
						$object_sub_details_id = $arr_condition_setting['object_sub_details_id'];
						
						if ($filter_condition->isQueryingObjectSubDetails($object_sub_details_id)) {

							$filter_condition->setFiltering([], ['object_sub_details' => [$object_sub_details_id => true]]);
							
							$sql_filter = $filter_condition->sqlQuery([
								'columns' => ['nodegoat_to.id', 'nodegoat_tos_'.$object_sub_details_id.'.id', $condition_key],
								'group' => ['nodegoat_to.id', 'nodegoat_tos_'.$object_sub_details_id.'.id']
							]);

							$this->arr_columns_object_conditions[] = ['condition_key' => $condition_key, 'sql' => $sql_filter, 'condition_group' => $arr_condition_setting['condition_group'], 'object_sub_details_id' => $object_sub_details_id, 'condition_is_filtering_sub' => true];
							
							$arr_condition_setting['condition_is_filtering_sub'] = true;
						} else {
							
							$sql_filter = $filter_condition->sqlQuery(['nodegoat_to.id', '1', $condition_key]);
							
							$this->arr_columns_object_conditions[] = ['condition_key' => $condition_key, 'sql' => $sql_filter, 'condition_group' => $arr_condition_setting['condition_group'], 'object_sub_details_id' => $object_sub_details_id];
						}
					}
					
					$this->arr_sql_pre_queries[] = $sql_filter;
					
					$arr_sql_pre_settings = $filter_condition->getPre();
					$this->arr_sql_pre_settings += $arr_sql_pre_settings;
					
					$arr_condition_setting['condition_key'] = $condition_key;
				}
				
				if ($arr_condition_setting['condition_value']) {
					
					if ($arr_condition_setting['condition_group'] == 'object') {
						
						$arr_sql_value = [];
						
						$object_description_id = $arr_condition_setting['condition_value']['object_description_id'];
						
						if ($object_description_id) {
							
							$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
							
							$column_name = 'des_'.(int)$object_description_id;
							
							$table_name = 'nodegoat_to_def';
							$version_select = $this->generateVersion('record', $table_name);
							$sql_value = $table_name.".".StoreType::getValueTypeValue($arr_object_description['object_description_value_type']);
							$sql_value = ($arr_object_description['object_description_has_multi'] ? "SUM(".$sql_value.")" : $sql_value);
							
							$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'])." AS ".$table_name."
								WHERE ".$table_name.".object_id = nodegoat_to.id AND ".$table_name.".object_description_id = ".(int)$object_description_id." AND ".$version_select."
							)";
						}
						
						$object_analysis_id = $arr_condition_setting['condition_value']['object_analysis_id'];
						
						if ($object_analysis_id) {
														
							$table_name = 'nodegoat_to_an';
							
							$arr_object_analysis_id = explode('_', $object_analysis_id);
							
							$analysis_id = (int)$arr_object_analysis_id[0];
							$analysis_user_id = (int)$arr_object_analysis_id[1];
							
							if ($analysis_id && !$analysis_user_id) {
								$analysis_user_id = 0;
							} else if ($analysis_user_id) {
								$analysis_user_id = ($this->arr_scope['users'] && in_array($analysis_user_id, $this->arr_scope['users']) ? $analysis_user_id : false);
							} else if (!$analysis_id) {
								$analysis_user_id = ($this->arr_scope['users'] ? current($this->arr_scope['users']) : false);
							}
							
							if (!$analysis_user_id) { // Make sure we have something
								$analysis_user_id = 0;
							}
							
							$column_name = 'an_'.$analysis_id.'_'.$analysis_user_id;
							
							$arr_sql_value[$column_name] = "(SELECT
								".$table_name.".number
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')." AS ".$table_name."
								WHERE ".$table_name.".object_id = nodegoat_to.id
									AND ".$table_name.".user_id = ".$analysis_user_id."
									AND ".$table_name.".analysis_id = ".$analysis_id."
									AND ".$table_name.".state = 1
							)";
						}
												
						$column_name = 'object_condition_value_';
						
						if (count($arr_sql_value) > 1) {
							
							$sql_value = implode(' * ', $arr_sql_value); // Multiply
							$column_name .= implode('_', array_keys($arr_sql_value));
						} else {
							
							$sql_value = current($arr_sql_value);
							$column_name .= key($arr_sql_value);
						}
						
						$s_arr =& $this->arr_columns_object_conditions_values[$column_name];
						
						if (!$s_arr) {
						
							$column_index = count($this->arr_columns_object_conditions_values) - 1;
							
							$s_arr = ['column_name' => $column_name, 'column_index' => $column_index, 'sql' => $sql_value];
						}
						
						$arr_condition_setting['condition_value_column_index'] = $s_arr['column_index'];
					} else {
						
						$arr_sql_value = [];
						
						$object_sub_details_id = $arr_condition_setting['object_sub_details_id'];
						
						if ($arr_condition_setting['condition_value']['object_description_id']) {
							
							$object_description_id = $arr_condition_setting['condition_value']['object_description_id'];
							$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
							
							$column_name = 'des_'.(int)$object_description_id;
							$table_name = 'nodegoat_to_def';
							$version_select = $this->generateVersion('record', $table_name);
							$sql_value = $table_name.".".StoreType::getValueTypeValue($arr_object_description['object_description_value_type']);
							$sql_value = ($arr_object_description['object_description_has_multi'] ? "SUM(".$sql_value.")" : $sql_value);
							
							$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'])." AS ".$table_name."
								WHERE ".$table_name.".object_id = nodegoat_tos_".(int)$object_sub_details_id.".object_id AND ".$table_name.".object_description_id = ".(int)$object_description_id." AND ".$version_select."
							)";							
						} else if ($arr_condition_setting['condition_value']['object_sub_description_id']) {

							$object_sub_description_id = $arr_condition_setting['condition_value']['object_sub_description_id'];
							$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
							
							$column_name = 'des_'.(int)$object_sub_details_id.'_'.(int)$object_sub_description_id;
							$table_name = 'nodegoat_tos_def';
							$version_select = $this->generateVersion('record', $table_name);
							$sql_value = $table_name.".".StoreType::getValueTypeValue($arr_object_sub_description['object_sub_description_value_type']);
										
							$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'])." AS ".$table_name."
								WHERE ".$table_name.".object_sub_id = nodegoat_tos_".(int)$object_sub_details_id.".id AND ".$table_name.".object_sub_description_id = ".(int)$object_sub_description_id." AND ".$version_select."
							)";
						}
						
						$object_analysis_id = $arr_condition_setting['condition_value']['object_analysis_id'];
						
						if ($object_analysis_id) {

							$table_name = 'nodegoat_to_an';
							
							$arr_object_analysis_id = explode('_', $object_analysis_id);
							
							$analysis_id = (int)$arr_object_analysis_id[0];
							$analysis_user_id = (int)$arr_object_analysis_id[1];
							
							if ($analysis_id && !$analysis_user_id) {
								$analysis_user_id = 0;
							} else if ($analysis_user_id) {
								$analysis_user_id = ($this->arr_scope['users'] && in_array($analysis_user_id, $this->arr_scope['users']) ? $analysis_user_id : false);
							} else if (!$analysis_id) {
								$analysis_user_id = ($this->arr_scope['users'] ? current($this->arr_scope['users']) : false);
							}
							
							if (!$analysis_user_id) { // Make sure we have something
								$analysis_user_id = 0;
							}
							
							$column_name = 'an_'.$analysis_id.'_'.$analysis_user_id;
							
							$arr_sql_value[$column_name] = "(SELECT
								".$table_name.".number
									FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')." AS ".$table_name."
								WHERE ".$table_name.".object_id = nodegoat_tos_".(int)$object_sub_details_id.".object_id
									AND ".$table_name.".user_id = ".$analysis_user_id."
									AND ".$table_name.".analysis_id = ".$analysis_id."
									AND ".$table_name.".state = 1
							)";
						}
						
						$column_name = 'object_condition_value_';
						
						if (count($arr_sql_value) > 1) {
							
							$sql_value = implode(' * ', $arr_sql_value); // Multiply
							$column_name .= implode('_', array_keys($arr_sql_value));
						} else {
							
							$sql_value = current($arr_sql_value);
							$column_name .= key($arr_sql_value);
						}
						
						$s_arr =& $this->arr_columns_subs_object_conditions_values[$object_sub_details_id][$column_name];
						
						if (!$s_arr) {
						
							$column_index = count($this->arr_columns_subs_object_conditions_values[$object_sub_details_id]) - 1;
							
							$s_arr = ['column_name' => $column_name, 'column_index' => $column_index, 'sql' => $sql_value];
						}
						
						$arr_condition_setting['condition_value_column_index'] = $s_arr['column_index'];
					}
				}
			}
			unset($s_arr);
		}
		
		// Filtering
		
		if ($this->arr_filtering_filters && $this->arr_filtering_filters !== true) {
			
			if ($this->arr_filtering) {
				$this->setFilter(['object_filter_parsed' => $this->parseFilterObject($this->arr_filtering_filters, 'filtering')]); // Set as a real filter
			} else {
				$this->parseFilterObject($this->arr_filtering_filters, 'filtering'); // Only parse and only trigger filtering functionalities
			}
		}
	}
	
	private function generateTablesColumns($force = false) {
		
		if ($this->sql_tables_columns_generated && !$force) {
			return;
		}
		$this->sql_tables_columns_generated = true;
		
		$this->generateTableName();
	
		$this->arr_columns_object_as[] = "nodegoat_to.id";
		
		$select_object = ($this->arr_selection['object']['all'] ? true : false);
		
		// Columns
		if ($this->view != 'id' && $this->view != 'storage' && $select_object && arrValuesRecursive('object_description_in_name', $this->arr_type_set['object_descriptions'])) {
			
			$s_select = $this->generateTablesColumnsName();
			$this->arr_columns_object_as[] = $s_select." AS object_name";
			
			if ($this->arr_order['object_name']) {
				
				$s_select = $this->generateTablesColumnsNameColumn();
				$this->arr_columns_object['object_name'] = $s_select;
			}
		} else {
			
			$sql_name = "CASE WHEN nodegoat_to.name LIKE '%[[%' THEN CONCAT('".Labels::addLanguageTags(true)."', nodegoat_to.name, '".Labels::addLanguageTags(false)."') ELSE nodegoat_to.name END"; // Add language delimiters when needed
			
			$this->arr_columns_object_as[] = ($this->view != 'id' && $this->view != 'storage' && $select_object ? $sql_name : "NULL")." AS object_name";
		
			if ($this->arr_order['object_name']) {
				
				$this->arr_columns_object['object_name'] = ($this->view != 'id' && $this->view != 'storage' && $select_object ? $sql_name : "NULL");
			}
		}
		
		$this->arr_columns_object_as[] = ((($this->view == 'set' || $this->view == 'storage' || $this->view == 'overview') && $select_object) ? "nodegoat_to.name" : "NULL")." AS object_name_plain";
		
		$this->arr_columns_object_as[] = ((($this->view == 'visualise' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') && $select_object) ? "(SELECT ".DBFunctions::sqlImplode("CONCAT(nodegoat_to_src.ref_object_id, '".self::$sql_separator_group2."', nodegoat_to_src.ref_type_id, '".self::$sql_separator_group2."', nodegoat_to_src.value)", self::$sql_separator_group)."
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')." nodegoat_to_src
			WHERE nodegoat_to_src.object_id = nodegoat_to.id
		)" : "NULL")." AS object_sources";
		
		if ($select_object) {
			
			$this->arr_columns_object_as[] = "(CASE
				WHEN nodegoat_to.status = 3 THEN 'deleted'
				WHEN nodegoat_to.status = 1 THEN 'added'
				WHEN (nodegoat_to.status = 2
					OR
					EXISTS (SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS')." AS nodegoat_to_def WHERE nodegoat_to_def.object_id = nodegoat_to.id AND nodegoat_to_def.active = FALSE AND nodegoat_to_def.status BETWEEN 1 AND 3)
					OR
					EXISTS (SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_to_def WHERE nodegoat_to_def.object_id = nodegoat_to.id AND nodegoat_to_def.active = FALSE AND nodegoat_to_def.status BETWEEN 1 AND 3)
				) THEN 'changed'
				ELSE '' END
			) AS object_version";
		} else {
			
			$this->arr_columns_object_as[] = "NULL AS object_version";
		}
		
		if (($this->view == 'overview' || $this->view == 'all' || $this->view == 'set') && $select_object) {
				
			$this->arr_columns_object_as[] = "(SELECT
				".(DB::ENGINE_IS_MYSQL ? "DATE_FORMAT(nodegoat_to_date.date, '%Y-%m-%dT%TZ')" : (DB::ENGINE_IS_POSTGRESQL ? "TO_CHAR(nodegoat_to_date.date, 'YYYY-MM-DD\"T\"HH24:MI:SSTZ')" : ''))." AS date
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DATING')." nodegoat_to_date
				WHERE nodegoat_to_date.object_id = nodegoat_to.id
			) AS object_dating";
			
			if ($this->arr_order['date']) {
				
				$this->arr_columns_object['date'] = "(SELECT
					nodegoat_to_date.date AS date
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DATING')." nodegoat_to_date
					WHERE nodegoat_to_date.object_id = nodegoat_to.id
				)";
			}
		} else {
			$this->arr_columns_object_as[] = "NULL AS object_dating";
		}
		
		if (($this->view == 'overview' || $this->view == 'all' || $this->view == 'set') && $select_object) {
			
			$s_select = "(SELECT
				CASE WHEN identifier = '' OR (date_updated + ".DBFunctions::interval(StoreTypeObjects::$timeout_lock, 'SECOND').") > NOW() THEN user_id ELSE NULL END
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK')." nodegoat_to_lock
				WHERE nodegoat_to_lock.object_id = nodegoat_to.id
					AND nodegoat_to_lock.type = 1
			)";
				
			$this->arr_columns_object_as[] = $s_select." AS object_locked";
		} else {
			
			$this->arr_columns_object_as[] = "NULL AS object_locked";
		}
		
		if ($this->view == 'overview') {
			
			$this->arr_columns_object_as[] = "(SELECT
				COUNT(DISTINCT nodegoat_to_def.object_description_id)
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS')." AS nodegoat_to_def
				WHERE nodegoat_to_def.object_id = nodegoat_to.id AND nodegoat_to_def.active = FALSE AND nodegoat_to_def.status BETWEEN 1 AND 3
				) + (SELECT
				COUNT(DISTINCT nodegoat_to_def.object_description_id)
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_to_def
				WHERE nodegoat_to_def.object_id = nodegoat_to.id AND nodegoat_to_def.active = FALSE AND nodegoat_to_def.status BETWEEN 1 AND 3
			) AS object_changes";
			
			$this->arr_columns_object_as[] = "(SELECT COUNT(DISTINCT nodegoat_tos.id)
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
				WHERE nodegoat_tos.object_id = nodegoat_to.id
					AND nodegoat_tos.active = FALSE 
					AND (
						nodegoat_tos.status BETWEEN 1 AND 3
						OR 
						EXISTS (
							SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS')." nodegoat_tos_def WHERE nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def.active = FALSE AND nodegoat_tos_def.status BETWEEN 1 AND 3
						)
						OR
						EXISTS ( 
							SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def WHERE nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def.active = FALSE AND nodegoat_tos_def.status BETWEEN 1 AND 3
						)
					)
			) AS object_sub_changes";
		}
		
		if ($this->arr_selection['object']['analysis']) {
			
			$arr_sql = [];
	
			foreach ($this->arr_selection['object']['analysis'] as $arr_analysis) {
				
				$arr_sql[] = "COALESCE(
					(SELECT
						CASE
							WHEN nodegoat_to_an.number_secondary IS NULL THEN ".DBFunctions::castAs('nodegoat_to_an.number', DBFunctions::CAST_TYPE_STRING)."
							ELSE CONCAT(".DBFunctions::castAs('nodegoat_to_an.number', DBFunctions::CAST_TYPE_STRING).", ':', ".DBFunctions::castAs('nodegoat_to_an.number_secondary', DBFunctions::CAST_TYPE_STRING).")
						END
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')." AS nodegoat_to_an
						WHERE nodegoat_to_an.object_id = nodegoat_to.id
							AND nodegoat_to_an.user_id = ".(int)($arr_analysis['user_id'] ?: 0)."
							AND nodegoat_to_an.analysis_id = ".(int)$arr_analysis['analysis_id']."
							AND nodegoat_to_an.state = 1
					),
					'-'
				)";
			}
		
			if (count($arr_sql) > 1) {
				$this->arr_columns_object_as[] = "CONCAT(".implode(", '".self::$sql_separator_analysis."',", $arr_sql).") AS object_analysis";
			} else {
				$this->arr_columns_object_as[] = $arr_sql[0]." AS object_analysis";
			}

			if ($this->arr_order['object_analysis']) {
				
				foreach ($this->arr_selection['object']['analysis'] as $arr_analysis) {
				
					$this->arr_columns_object['object_analysis'][] = "(SELECT
						nodegoat_to_an.number + (COALESCE(nodegoat_to_an.number_secondary, 0) / 1000000000)
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')." AS nodegoat_to_an
						WHERE nodegoat_to_an.object_id = nodegoat_to.id
							AND nodegoat_to_an.user_id = ".(int)($arr_analysis['user_id'] ?: 0)."
							AND nodegoat_to_an.analysis_id = ".(int)$arr_analysis['analysis_id']."
							AND nodegoat_to_an.state = 1
					)";
				}
			}
		}
		
		foreach ($this->query_object_sources as $table_name) {
						
			$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to.id)";
			
			$this->arr_tables['query_object'] .= $sql_table;
			$this->arr_tables['filtering_object'] .= $sql_table;
		}
		
		foreach ($this->query_object_analyses as $table_name => $arr_tables_info) {
			
			$arr_sql_filter = [];
			
			foreach ($arr_tables_info as $arr_table_info) {
				
				$arr_sql_filter[] = $arr_table_info['arr_sql']['sql_filter'];
			}
			
			$sql_filter = ($arr_sql_filter ? "AND (".implode(' OR ', $arr_sql_filter).")" : '');
									
			$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to.id AND ".$table_name.".state = 1 ".$sql_filter.")";
			
			$this->arr_tables['query_object'] .= $sql_table;
			$this->arr_tables['filtering_object'] .= $sql_table;
		}
		
		if ($this->arr_type_set['type']['is_reversal']) {
			
			$this->arr_columns_object_as[] = (($this->view == 'overview' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') ? "(SELECT ".DBFunctions::sqlImplode("CONCAT(nodegoat_to_fil.ref_type_id, '".self::$sql_separator_group2."', nodegoat_to_fil.object, '".self::$sql_separator_group2."', nodegoat_to_fil.scope_object)", self::$sql_separator_group)."
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_FILTERS')." nodegoat_to_fil
				WHERE nodegoat_to_fil.object_id = nodegoat_to.id
			)" : "NULL")." AS object_filters";
		}
				
		if ($this->arr_selection['object_descriptions'] || $this->query_object_descriptions) {
				
			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$is_filtering = $this->isFilteringObjectDescription($object_description_id);
				$table_name = "nodegoat_to_def_".$object_description_id;
				$version_select = $this->generateVersion('record', $table_name, $arr_object_description['object_description_value_type']);
				
				if ($is_filtering) {
						
					if ($arr_object_description['object_description_is_dynamic']) {
						
						$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." ".$table_name."_objects ON (".$table_name."_objects.object_id = nodegoat_to.id AND ".$table_name."_objects.object_description_id = ".$object_description_id." AND ".$table_name."_objects.state = 1)";
					} else {
						
						$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'], 'search')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to.id AND ".$table_name.".object_description_id = ".$object_description_id." AND ".$version_select.")";
					}
					
					$this->arr_tables['filtering_object'] .= $sql_table;
				}
				
				$arr_sql_query = [];
				
				foreach ((array)$this->query_object_descriptions['object_descriptions'][$object_description_id] as $arr_table_info) {
					
					if (!$arr_sql_query[$arr_table_info['table_name']]) {
						$arr_sql_query[$arr_table_info['table_name']] = $arr_table_info;
					}

					if (is_array($arr_table_info['arr_sql']['sql_filter'])) {
						
						if ($arr_table_info['arr_sql']['sql_filter']['value']) {

							$arr_sql_query[$arr_table_info['table_name']]['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']['value']] = $arr_table_info['arr_sql']['sql_filter']['value'];
						}
						if ($arr_table_info['arr_sql']['sql_filter']['objects']) {

							$arr_sql_query[$arr_table_info['table_name']]['arr_sql_filter_objects'][$arr_table_info['arr_sql']['sql_filter']['objects']] = $arr_table_info['arr_sql']['sql_filter']['objects'];
						}
					} else {

						$arr_sql_query[$arr_table_info['table_name']]['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];
					}
				}

				foreach ($arr_sql_query as $arr_table_info) {
					
					$version_select_query = $this->generateVersion('record', $arr_table_info['table_name'], $arr_object_description['object_description_value_type']);
					$sql_filter = '';
					$sql_filter_objects = '';
					
					if ($arr_table_info['arr_sql_filter']) {
						$arr_sql_filter = array_filter($arr_table_info['arr_sql_filter']);
						$sql_filter = ($arr_sql_filter ? "AND (".implode(' OR ', $arr_sql_filter).")" : '');
					}
					if ($arr_table_info['arr_sql_filter_objects']) {
						$arr_sql_filter_objects = array_filter($arr_table_info['arr_sql_filter_objects']);
						$sql_filter_objects = ($arr_sql_filter_objects ? "AND (".implode(' OR ', $arr_sql_filter_objects).")" : '');
					}
					
					$sql_table = '';
					
					if ($arr_table_info['arr_sql_filter']) {
						$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'], 'search')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = nodegoat_to.id AND ".$arr_table_info['table_name'].".object_description_id = ".$object_description_id." AND ".$version_select_query." ".$sql_filter.")";
					}
					if ($arr_table_info['arr_sql_filter_objects']) {
						$sql_table .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." ".$arr_table_info['table_name']."_objects ON (".$arr_table_info['table_name']."_objects.object_id = nodegoat_to.id AND ".$arr_table_info['table_name']."_objects.object_description_id = ".$object_description_id." AND ".$arr_table_info['table_name']."_objects.state = 1 ".$sql_filter_objects.")";
					}
					
					$this->arr_tables['query_object'] .= $sql_table;
					
					if ($is_filtering) {

						if ($arr_object_description['object_description_is_dynamic']) {
							
							if ($arr_table_info['arr_sql_filter_objects']) {
								$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." ".$arr_table_info['table_name']."_objects ON (".$arr_table_info['table_name']."_objects.object_id = ".$table_name."_objects.object_id AND ".$arr_table_info['table_name']."_objects.ref_object_id = ".$table_name."_objects.ref_object_id AND ".$arr_table_info['table_name']."_objects.object_description_id = ".$object_description_id." AND ".$arr_table_info['table_name']."_objects.state = 1 ".$sql_filter_objects.")";
							}
						} else if ($arr_object_description['object_description_ref_type_id']) {
							
							$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = ".$table_name.".object_id AND ".$arr_table_info['table_name'].".ref_object_id = ".$table_name.".ref_object_id AND ".$arr_table_info['table_name'].".object_description_id = ".$object_description_id." AND ".$version_select_query." ".$sql_filter.")";
						} else {
							
							$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'], 'search')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = ".$table_name.".object_id AND ".$arr_table_info['table_name'].".value = ".$table_name.".value AND ".$arr_table_info['table_name'].".object_description_id = ".$object_description_id." AND ".$version_select_query." ".$sql_filter.")";
						}
					}
						
					$this->arr_tables['filtering_object'] .= $sql_table;
				}
				
				foreach ((array)$this->query_object_descriptions['object_descriptions_sources'][$object_description_id] as $arr_table_info) {
						
					$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = nodegoat_to.id AND ".$arr_table_info['table_name'].".object_description_id = ".$object_description_id.")";
				
					$this->arr_tables['query_object'] .= $sql_table;
					$this->arr_tables['filtering_object'] .= $sql_table;
				}

				if ($this->arr_selection['object_descriptions'][$object_description_id]) {
									
					$this->count_object_descriptions++;
										
					$arr_sql_columns = $this->generateTablesColumnsObjectDescription($object_description_id);
					
					$this->arr_columns_object_descriptions[$object_description_id]['select_id'] = $arr_sql_columns['select_id'];
					$this->arr_columns_object_descriptions[$object_description_id]['select_value'] = $arr_sql_columns['select_value'];
					$this->arr_columns_object_descriptions[$object_description_id]['group'] = $arr_sql_columns['group'];
					$this->arr_columns_object_descriptions[$object_description_id]['tables'] = $arr_sql_columns['tables'];
					$this->arr_column_purpose[$object_description_id]['view'] = true;
					
					if ($arr_sql_columns['column']) {
						$this->arr_columns_object[$object_description_id] = $arr_sql_columns['column'];
					}
					
					if ($this->view == 'visualise' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') {
						$this->arr_columns_object_descriptions[$object_description_id]['sources'] = true;
					}
				}
			}
		}
		
		// Subobjects
		if ($this->arr_selection['object_sub_details'] || $this->query_object_subs) {
			
			$arr_sql_query_object_subs_details = [];

			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
				if (($this->query_object_subs && !$this->query_object_subs[$object_sub_details_id] && !$this->arr_selection['object_sub_details'][$object_sub_details_id])
					||
					(!$this->query_object_subs && !$this->arr_selection['object_sub_details'][$object_sub_details_id])
				) {
					continue;
				}
				
				$is_filtering_sub = $this->isFilteringObjectSubDetails($object_sub_details_id);
				$table_name_tos = "nodegoat_tos_".$object_sub_details_id;
				$version_select = $this->generateVersion('object_sub', $table_name_tos);
				
				if ($is_filtering_sub) {
				
					$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_tos." ON (".$table_name_tos.".object_id = nodegoat_to.id AND ".$table_name_tos.".object_sub_details_id = ".$object_sub_details_id." AND ".$version_select.")";
					
					$this->arr_tables['filtering_object'] .= $sql_table;
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
						
						$is_filtering = $this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id);
						$table_name = "nodegoat_tos_def_".$object_sub_description_id;
						$version_select = $this->generateVersion('record', $table_name, $arr_object_sub_description['object_sub_description_value_type']);
						$object_sub_description_use_object_description_id = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_use_object_description_id'];

						if ($is_filtering) {
							
							if ($object_sub_description_use_object_description_id) {
								
								$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$table_name." ON (".$table_name.".object_id = ".$table_name_tos.".object_id AND ".$table_name.".object_description_id = ".$object_sub_description_use_object_description_id." AND ".$version_select.")";
							} else {
								
								$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'], 'search')." ".$table_name." ON (".$table_name.".object_sub_id = ".$table_name_tos.".id AND ".$table_name.".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select.")";
							}
							$this->arr_tables['filtering_object'] .= $sql_table;
						}
					}
				}
				
				$arr_sql_tables = [];
				
				if ($this->query_object_subs[$object_sub_details_id] && is_array($this->query_object_subs[$object_sub_details_id])) {

					foreach ($this->query_object_subs[$object_sub_details_id] as $table_name_query_object_sub => $arr_query_object_sub) {
						
						$arr_sql_query_object_sub_descriptions = [];
						
						foreach ((array)$arr_query_object_sub['date'] as $arr_table_info) {
							
							$s_arr =& $arr_sql_query_object_subs_details[$object_sub_details_id][$arr_table_info['table_name']];
							
							if (!$s_arr) {
								$s_arr = $arr_table_info;
							}
							
							$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];
							
							$s_arr['date'] = true;
						}					
						
						foreach ((array)$arr_query_object_sub['location'] as $arr_table_info) {
							
							$s_arr =& $arr_sql_query_object_subs_details[$object_sub_details_id][$arr_table_info['table_name']];
							
							if (!$s_arr) {
								$s_arr = $arr_table_info;
							}
							
							$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];
							
							$s_arr['location'] = true;
						}
						
						foreach ((array)$arr_query_object_sub['referenced'] as $arr_table_info) {
							
							$s_arr =& $arr_sql_query_object_subs_details[$object_sub_details_id][$arr_table_info['table_name']];
							
							if (!$s_arr) {
								$s_arr = $arr_table_info;
							}
							
							$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];

							$s_arr['referenced'] = true;
						}

						foreach ((array)$arr_query_object_sub['object_sub_sources'] as $arr_table_info) {
						
							$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_sub_id = ".$table_name_query_object_sub.".id)";
							
							$this->arr_tables['query_object_more'] .= $sql_table;
							if ($arr_table_info['filter_object_subs']) {
								$this->arr_tables['query_object_subs_more'][$object_sub_details_id] .= $sql_table;
							}
							$this->arr_tables['filtering_object_more'] .= $sql_table;
						}
						
						foreach ((array)$arr_query_object_sub['object_sub_descriptions'] as $object_sub_description_id => $arr_query_object_sub_description) {
							
							foreach ($arr_query_object_sub_description as $arr_table_info) {
								
								if (!$arr_sql_query_object_subs_details[$object_sub_details_id][$table_name_query_object_sub]) {
									$arr_sql_query_object_subs_details[$object_sub_details_id][$table_name_query_object_sub] = ['table_name' => $table_name_query_object_sub, 'filter_object_subs' => $arr_table_info['filter_object_subs'], 'purpose' => $arr_table_info['purpose'], 'arr_sql_filter' => []];								
								}
								
								$s_arr =& $arr_sql_query_object_sub_descriptions[$object_sub_description_id][$arr_table_info['table_name']];
								
								if (!$s_arr) {
									$s_arr = $arr_table_info;
								}
								
								$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];
							}
						}
						unset($s_arr);
						
						foreach ((array)$arr_query_object_sub['object_sub_descriptions_sources'] as $object_sub_description_id => $arr_query_object_sub_description_sources) {
							foreach ($arr_query_object_sub_description_sources as $arr_table_info) {
						
								$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_sub_id = ".$table_name_query_object_sub.".id AND ".$arr_table_info['table_name'].".object_sub_description_id = ".$object_sub_description_id.")";
								
								$this->arr_tables['query_object_more'] .= $sql_table;
								if ($arr_table_info['filter_object_subs']) {
									$this->arr_tables['query_object_subs_more'][$object_sub_details_id] .= $sql_table;
								}
								$this->arr_tables['filtering_object_more'] .= $sql_table;
							}
						}
						
						foreach ($arr_sql_query_object_sub_descriptions as $object_sub_description_id => $arr_sql_query_object_sub_description) {
							
							$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
							
							foreach ($arr_sql_query_object_sub_description as $arr_table_info) {
							
								$is_filtering = $this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id);
								$table_name = "nodegoat_tos_def_".$object_sub_description_id;
								$object_sub_description_use_object_description_id = $arr_object_sub_description['object_sub_description_use_object_description_id'];
											
								$version_select_query = $this->generateVersion('record', $arr_table_info['table_name'], $arr_object_sub_description['object_sub_description_value_type']);
								$arr_sql_filter = array_filter($arr_table_info['arr_sql_filter']);
								$sql_filter = ($arr_sql_filter ? "AND (".implode(' OR ', $arr_sql_filter).")" : "");
																							
								if ($object_sub_description_use_object_description_id) {
									
									$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = ".$table_name_query_object_sub.".object_id AND ".$arr_table_info['table_name'].".object_description_id = ".$object_sub_description_use_object_description_id." AND ".$version_select_query." ".$sql_filter.")";
								
									$this->arr_tables['query_object_more'] .= $sql_table;
									if ($arr_table_info['filter_object_subs']) {
										$this->arr_tables['query_object_subs_more'][$object_sub_details_id] .= $sql_table;
									}
									
									if ($is_filtering) {
							
										$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = ".$table_name.".object_id AND ".$arr_table_info['table_name'].".ref_object_id = ".$table_name.".ref_object_id AND ".$arr_table_info['table_name'].".object_description_id = ".$object_sub_description_use_object_description_id." AND ".$version_select_query." ".$sql_filter.")";
									}
									
									$this->arr_tables['filtering_object_more'] .= $sql_table;
								} else {
									
									$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'], 'search')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_sub_id = ".$table_name_query_object_sub.".id AND ".$arr_table_info['table_name'].".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select_query." ".$sql_filter.")";
								
									$this->arr_tables['query_object_more'] .= $sql_table;
									if ($arr_table_info['filter_object_subs']) {
										$this->arr_tables['query_object_subs_more'][$object_sub_details_id] .= $sql_table;
									}
							
									if ($is_filtering) {
										
										if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
											
											$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_sub_id = ".$table_name.".object_sub_id AND ".$arr_table_info['table_name'].".ref_object_id = ".$table_name.".ref_object_id AND ".$arr_table_info['table_name'].".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select_query." ".$sql_filter.")";
										} else {
											
											$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'], 'search')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_sub_id = ".$table_name.".object_sub_id AND ".$arr_table_info['table_name'].".value = ".$table_name.".value AND ".$arr_table_info['table_name'].".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select_query." ".$sql_filter.")";
										}
									}
									
									$this->arr_tables['filtering_object_more'] .= $sql_table;
								}
							}
						}
					}
				}
										
				if (!$this->arr_selection['object_sub_details'][$object_sub_details_id]) {
					continue;
				}
				
				if ($this->view == 'storage') {
					
					$arr_sql_date = [
						'column_start' => "CASE
							WHEN ".$table_name_tos.".date_version IS NOT NULL THEN (SELECT date_start FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$table_name_tos."_date WHERE ".$table_name_tos."_date.object_sub_id = ".$table_name_tos.".id AND ".$table_name_tos."_date.version = ".$table_name_tos.".date_version)
							ELSE NULL
						END",
						'column_end' => "CASE
							WHEN ".$table_name_tos.".date_version IS NOT NULL THEN (SELECT date_end FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$table_name_tos."_date WHERE ".$table_name_tos."_date.object_sub_id = ".$table_name_tos.".id AND ".$table_name_tos."_date.version = ".$table_name_tos.".date_version)
							ELSE NULL
						END"
					];
					$arr_sql_location = [
						'column_geometry_readable' => "CASE
							WHEN ".$table_name_tos.".location_geometry_version IS NOT NULL THEN (SELECT ".StoreTypeObjects::formatFromSQLValue('geometry', $table_name_tos.'_geo.geometry')." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_tos."_geo WHERE ".$table_name_tos."_geo.object_sub_id = ".$table_name_tos.".id AND ".$table_name_tos."_geo.version = ".$table_name_tos.".location_geometry_version)
							ELSE ''
						END",
						'column_geometry_object_id' => 'NULL',
						'column_geometry_type_id' => 'NULL',
						'column_ref_object_id' => $table_name_tos.'.location_ref_object_id',
						'column_ref_type_id' => $table_name_tos.'.location_ref_type_id',
						'column_ref_object_sub_details_id' => $table_name_tos.'.location_ref_object_sub_details_id'
					];
				} else {
					
					$arr_sql_date = $this->generateTablesColumnsObjectSubDate($table_name_tos);
					$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference($table_name_tos);
				}
				
				$arr_sql_location['column_ref_object_id'] = ($this->view == 'all' ? $arr_sql_location['column_ref_show_object_id'] : $arr_sql_location['column_ref_object_id']);
				$arr_sql_location['column_ref_type_id'] = ($this->view == 'all' ? $arr_sql_location['column_ref_show_type_id'] : $arr_sql_location['column_ref_type_id']);
				$arr_sql_location['column_ref_object_sub_details_id'] = ($this->view == 'all' ? $arr_sql_location['column_ref_show_object_sub_details_id'] : $arr_sql_location['column_ref_object_sub_details_id']);
				
				$select_object_sub_details = true;
				
				if ($this->view == 'visualise' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') {
					$select_object_sub_details = $this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['all'];
				}
			
				if ($select_object_sub_details) {
					
					$this->arr_tables['select_object_subs'][$object_sub_details_id] = $arr_sql_date['tables'].$arr_sql_location['tables'];
				} else {
					
					if (!$this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date']) {
						
						$arr_sql_date = [
							'column_start' => 'NULL',
							'column_end' => 'NULL',
						];
					} else {
						
						$this->arr_tables['select_object_subs'][$object_sub_details_id] = $arr_sql_date['tables'];
					}
					
					$has_location = false;
					
					if (!$this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_reference']) {
						
						$arr_sql_location['column_ref_object_id'] = 'NULL';
						$arr_sql_location['column_ref_type_id'] = 'NULL';
						$arr_sql_location['column_ref_object_sub_details_id'] = 'NULL';
					} else {
						
						$has_location = true;
					}
					
					if (!$this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_geometry']) {
						
						$arr_sql_location['column_geometry_readable'] = 'NULL';
						$arr_sql_location['column_geometry_object_id'] = 'NULL';
						$arr_sql_location['column_geometry_type_id'] = 'NULL';
					} else {
						
						$has_location = true;
					}
					
					if ($has_location = true) {
						$this->arr_tables['select_object_subs'][$object_sub_details_id] = $arr_sql_location['tables'];
					}
				}

				$this->arr_columns_subs_group[$object_sub_details_id][] = "object_sub_date_start, object_sub_date_end, object_sub_location_geometry, object_sub_location_geometry_ref_object_id, object_sub_location_geometry_ref_type_id, object_sub_location_ref_object_id, object_sub_location_ref_type_id, object_sub_location_ref_object_sub_details_id";

				$this->arr_columns_subs_as[$object_sub_details_id][] = "nodegoat_to.id AS object_id,
					".$table_name_tos.".id AS object_sub_id,
					".$table_name_tos.".object_sub_details_id,
					".$arr_sql_date['column_start']." AS object_sub_date_start,
					".$arr_sql_date['column_end']." AS object_sub_date_end,
					".$arr_sql_location['column_geometry_readable']." AS object_sub_location_geometry,
					".$arr_sql_location['column_geometry_object_id']." AS object_sub_location_geometry_ref_object_id,
					".$arr_sql_location['column_geometry_type_id']." AS object_sub_location_geometry_ref_type_id,
					".$arr_sql_location['column_ref_object_id']." AS object_sub_location_ref_object_id,
					".$arr_sql_location['column_ref_type_id']." AS object_sub_location_ref_type_id,
					".$arr_sql_location['column_ref_object_sub_details_id']." AS object_sub_location_ref_object_sub_details_id,
					".(($this->view == 'visualise' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') && $select_object_sub_details ? "(SELECT ".DBFunctions::sqlImplode("CONCAT(nodegoat_tos_src.ref_object_id, '".self::$sql_separator_group2."', nodegoat_tos_src.ref_type_id, '".self::$sql_separator_group2."', nodegoat_tos_src.value)", self::$sql_separator_group)."
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')." nodegoat_tos_src
						WHERE nodegoat_tos_src.object_sub_id = ".$table_name_tos.".id
					) AS object_sub_sources" : "NULL").",
					".($select_object_sub_details ? "(CASE
						WHEN ".$table_name_tos.".status = 3 THEN 'deleted'
						WHEN ".$table_name_tos.".status = 1 THEN 'added'
						WHEN (".$table_name_tos.".status = 2
							OR EXISTS (SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS')." AS nodegoat_tos_def WHERE nodegoat_tos_def.object_sub_id = ".$table_name_tos.".id AND nodegoat_tos_def.active = FALSE AND nodegoat_tos_def.status > 0)
							OR EXISTS (SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_tos_def WHERE nodegoat_tos_def.object_sub_id = ".$table_name_tos.".id AND nodegoat_tos_def.active = FALSE AND nodegoat_tos_def.status > 0)
						) THEN 'changed'
						ELSE '' END
					)" : "NULL")." AS object_sub_version";
				
				$has_referenced = $this->arr_type_set['type']['include_referenced']['object_sub_details'] && ($object_sub_details_id == 'all' || $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]);
				
				if ($has_referenced) {
					$this->arr_columns_subs_as[$object_sub_details_id][] = $table_name_tos.".object_id AS object_sub_object_id";
					$this->arr_columns_subs_as[$object_sub_details_id][] = "nodegoat_tos_store.referenced_object_sub_description_id AS object_sub_referenced_object_sub_description_id";
				}
				
				if ($object_sub_details_id == 'all') {
					$this->arr_columns_subs[$object_sub_details_id]['object_sub_details_name'] = "(SELECT name FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." WHERE id = ".$table_name_tos.".object_sub_details_id)";
				}
				$this->arr_columns_subs[$object_sub_details_id]['object_sub_date_start'] = $arr_sql_date['column_start'];
				$this->arr_columns_subs[$object_sub_details_id]['object_sub_date_end'] = $arr_sql_date['column_end'];
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$table_name = "nodegoat_tos_def_".$object_sub_description_id;
					
					$version_select = $this->generateVersion('record', $table_name);
					
					if ($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]) {
						
						$this->count_object_sub_descriptions[$object_sub_details_id]++;
												
						$arr_sql_columns = $this->generateTablesColumnsObjectSubDescription($object_sub_details_id, $object_sub_description_id, $table_name_tos.'.id', $table_name_tos.'.object_id');
						
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['select_id'] = $arr_sql_columns['select_id'];
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['select_value'] = $arr_sql_columns['select_value'];
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['tables'] = $arr_sql_columns['tables'];
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['group'] = $arr_sql_columns['group'];
						
						if ($arr_sql_columns['column']) {
							$this->arr_columns_subs[$object_sub_details_id][$object_sub_description_id] = $arr_sql_columns['column'];
						}
						
						if ($this->view == 'visualise' || $this->view == 'all' || $this->view == 'set' || $this->view == 'storage') {
							$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['sources'] = true;
						}
					}
				}
			}
			
			$is_filtering_subs = $this->isFilteringObjectSubDetails();
			
			foreach ($arr_sql_query_object_subs_details as $object_sub_details_id => $arr_sql_query_object_sub_details) {
				foreach ($arr_sql_query_object_sub_details as $arr_table_info) {

					$version_select = $this->generateVersion('object_sub', $arr_table_info['table_name']);
					$arr_sql_filter = array_filter($arr_table_info['arr_sql_filter']);
					$sql_filter = ($arr_sql_filter ? "AND (".implode(' OR ', $arr_sql_filter).")" : "");
					
					$table_name_tos = "nodegoat_tos_".$object_sub_details_id;
										
					if ($object_sub_details_id == 'all' || $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]) {
						
						// Contain referenced sub-objects; sub-object filtering only
						
						$sql_table = "";
					} else {
						
						$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = nodegoat_to.id AND ".$arr_table_info['table_name'].".object_sub_details_id = ".$object_sub_details_id." AND ".$version_select." ".$sql_filter.")";
					}
					
					$sql_table_sub = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".id = ".$table_name_tos.".id AND ".$version_select." ".$sql_filter.")";
					
					$is_filtering_sub = $this->isFilteringObjectSubDetails($object_sub_details_id);
					
					$this->arr_tables['query_object'] .= $sql_table;
					if ($arr_table_info['filter_object_subs']) {
						$this->arr_tables['query_object_subs'][$object_sub_details_id] .= $sql_table_sub;
					}
					
					if ($is_filtering_sub) { // Filter sub-objects on object level
						$this->arr_tables['filtering_object'] .= $sql_table_sub;
					} else {
						$this->arr_tables['filtering_object'] .= $sql_table;
					}
				}
			}
		}
	}
	
	public function generateTablesColumnsName($sql_table = 'nodegoat_to.id') {
		
		$table_name = 'nodegoat_to_name';
		$version_select = $this->generateVersion('name', $table_name);
		
		$arr_type_object_path = StoreType::getTypeObjectPath('name', $this->type_id);
		$sql_dynamic_type_name = $this->format2SQLDynamicTypeObjectName($arr_type_object_path, $table_name, $this->conditions);
		
		$sql = "(SELECT ".$sql_dynamic_type_name['column']."
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." ".$table_name."
				".$sql_dynamic_type_name['tables']."
			WHERE ".$table_name.".id = ".$sql_table." AND ".$version_select."
			GROUP BY ".$table_name.".id, ".$table_name.".version
		)";
			
		return $sql;
	}
	
	public function generateTablesColumnsNameColumn($sql_table_ref = 'nodegoat_to.id') {
		
		$table_name = 'nodegoat_to_name';
		$version_select = $this->generateVersion('name', $table_name);
		
		$arr_type_object_path = StoreType::getTypeObjectPath('name', $this->type_id);
		$sql_dynamic_type_name_column = $this->format2SQLDynamicTypeObjectNameColumn($arr_type_object_path, $table_name.'.id');

		$sql = "(SELECT
			".DBFunctions::sqlImplode($sql_dynamic_type_name_column['column'], ' ')." AS column_value
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." ".$table_name."
				".$sql_dynamic_type_name_column['tables']."
			WHERE ".$table_name.".id = ".$sql_table_ref." AND ".$version_select."
			GROUP BY ".$table_name.".id
		)";
			
		return $sql;
	}
	
	public function generateNameColumn($sql_table = 'nodegoat_to_name.id') {
		
		$arr_type_object_path = StoreType::getTypeObjectPath('name', $this->type_id);
		$sql_dynamic_type_name_column = $this->format2SQLDynamicTypeObjectNameColumn($arr_type_object_path, $sql_table, true);
		
		return $sql_dynamic_type_name_column;
	}
			
	public function generateTablesColumnsObjectDescription($object_description_id, $sql_table_ref = 'nodegoat_to.id') {
		
		$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
		$table_name = "nodegoat_to_def";
		$version_select = $this->generateVersion('record', $table_name);
		$is_filtering = $this->isFilteringObjectDescription($object_description_id, true);
		
		$arr_selection = $this->arr_selection['object_descriptions'][$object_description_id];
		
		if ($arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic']) {
			
			$this->arr_type_object_name_object_description_ids[$object_description_id] = $object_description_id;
			
			$use_object_description_id = $arr_object_description['object_description_id']; // In case the object description is referenced
			
			if ($arr_object_description['object_description_is_referenced']) {
				$sql_select_id = $table_name.".object_id";
				$sql_from_id = $table_name.".ref_object_id";
			} else {
				$sql_select_id = $table_name.".ref_object_id";
				$sql_from_id = $table_name.".object_id";
			}

			$sql_select_tables = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$sql_select_id." AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref').")";
			$sql_group = $sql_select_id.", nodegoat_to_ref.type_id";
			
			$sql_select_value = ($this->view == 'storage' ? '' : "CONCAT('![[', nodegoat_to_ref.type_id, '_', ".$sql_select_id.", ']]')");
			
			if ($this->arr_order[$object_description_id] || $this->arr_order_object_subs_use_object_description_id[$object_description_id]) {
				
				$arr_type_object_path = StoreType::getTypeObjectPath('name', $arr_object_description['object_description_ref_type_id']);
				$sql_dynamic_type_name_column = $this->format2SQLDynamicTypeObjectNameColumn($arr_type_object_path, $sql_select_id);
				
				$sql_column = "(SELECT ".DBFunctions::sqlImplode($sql_dynamic_type_name_column['column'], ' ')." AS column_value
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$table_name."
						".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.id = ".$table_name.".object_id AND nodegoat_to_store.object_definition_".$object_description_id."_ref_object_id = ".$table_name.".ref_object_id)" : "")."
						".$sql_select_tables."
						".$sql_dynamic_type_name_column['tables']."
					WHERE ".$sql_from_id." = ".$sql_table_ref." AND ".$table_name.".object_description_id = ".$use_object_description_id." AND ".$version_select."
					GROUP BY ".$table_name.".object_id
				)";
			}
		} else {
			
			if ($arr_object_description['object_description_is_dynamic']) {
				
				if ($arr_selection['object_description_reference']) {
					
					$this->arr_type_object_name_object_description_ids[$object_description_id] = $object_description_id;
					
					$sql_concat = "nodegoat_to_ref.id, '".self::$sql_separator_group2."', nodegoat_to_ref.type_id, '".self::$sql_separator_group2."', '![[', nodegoat_to_ref.type_id, '_', nodegoat_to_ref.id, ']]'";
				
					if (!$arr_object_description['object_description_ref_type_id']) {
						$sql_concat .= ", '".self::$sql_separator_group2."', ".$table_name."_ref.value";
					}
					
					$sql_select_id = "(SELECT ".DBFunctions::sqlImplode("CONCAT(".$sql_concat.")", self::$sql_separator_group)."
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." ".$table_name."_ref
							".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.id = ".$table_name."_ref.object_id AND nodegoat_to_store.object_definition_".$object_description_id."_ref_object_id = ".$table_name."_ref.ref_object_id)" : "")."
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$table_name."_ref.ref_object_id
								".(is_array($arr_selection['object_description_reference']) ? " AND nodegoat_to_ref.type_id IN (".implode(',', $arr_selection['object_description_reference']).")" : '')."
								AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref')."
							)
						WHERE ".$table_name."_ref.object_id = ".$sql_table_ref." AND ".$table_name."_ref.object_description_id = ".$table_name.".object_description_id AND ".$table_name."_ref.state = 1
					)";
				}
			}
					
			if (!$arr_object_description['object_description_ref_type_id']) {
						
				$sql_value = $table_name.".".StoreType::getValueTypeValue($arr_object_description['object_description_value_type']);
				
				if ($this->arr_order[$object_description_id]) {
					
					$sql_column = "(SELECT ".($arr_object_description['object_description_has_multi'] ? DBFunctions::sqlImplode($sql_value, ' ') : $sql_value)." AS column_value
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($arr_object_description['object_description_value_type'])." AS ".$table_name."
							".($is_filtering && $arr_object_description['object_description_value_type'] != 'text_tags' ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.id = ".$table_name.".object_id AND nodegoat_to_store.object_definition_".$object_description_id."_value = ".$table_name.".value)" : "")."
						WHERE ".$table_name.".object_id = ".$sql_table_ref." AND ".$table_name.".object_description_id = ".$object_description_id." AND ".$version_select."
					)";
				}
				
				if ($arr_selection['object_description_value']) {
					
					if ($arr_object_description['object_description_has_multi']) {
					
						$sql_select_id = $table_name.".identifier";
					}
					
					$sql_select_value = ($this->view == 'set' || $this->view == 'storage' ? $sql_value : StoreTypeObjects::formatFromSQLValue($arr_object_description['object_description_value_type'], $sql_value, $arr_object_description['object_description_value_type_options']));
				}
			}
		}
		
		return ['select_value' => $sql_select_value, 'select_id' => $sql_select_id, 'group' => $sql_group, 'column' => $sql_column, 'tables' => $sql_select_tables];
	}
		
	public function generateTablesColumnsObjectSubDescription($object_sub_details_id, $object_sub_description_id, $sql_table_ref = 'nodegoat_tos.id', $sql_ref_object_table = 'nodegoat_tos.object_id') {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$table_name = 'nodegoat_tos_def';
		$version_select = $this->generateVersion('record', $table_name);
		$is_filtering = $this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id, true);
		
		$arr_selection = $this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		
		if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
			
			$object_sub_description_use_object_description_id = $arr_object_sub_description['object_sub_description_use_object_description_id'];
			
			if ($this->arr_order_object_subs[$object_sub_details_id][$object_sub_description_id]) {
				$this->arr_order_object_subs_use_object_description_id[$object_sub_description_use_object_description_id] = true;
			}
			
			$arr_sql_columns = $this->generateTablesColumnsObjectDescription($object_sub_description_use_object_description_id);
			
			$sql_select_id = $arr_sql_columns['select_id'];
			$sql_select_value = $arr_sql_columns['select_value'];
			$sql_group = $arr_sql_columns['group'];
			$sql_column = $arr_sql_columns['column'];
			$sql_select_tables = $arr_sql_columns['tables'];
		} else if ($arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic']) {
			
			$this->arr_type_object_name_object_sub_description_ids[$object_sub_details_id][$object_sub_description_id] = $object_sub_description_id;
			
			$is_referenced = $arr_object_sub_description['object_sub_description_is_referenced'];
			
			if ($is_referenced) {
				$sql_select_id = $sql_ref_object_table;
			} else {
				$sql_select_id = $table_name.'.ref_object_id';
			}
			
			$sql_select_tables = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$sql_select_id." AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref').")";
			$sql_group = $sql_select_id.", nodegoat_to_ref.type_id";

			$sql_select_value = ($this->view == 'storage' ? '' : "CONCAT('![[', nodegoat_to_ref.type_id, '_', ".$sql_select_id.", ']]')");
			
			if ($this->arr_order_object_subs[$object_sub_details_id][$object_sub_description_id]) {
				
				$sql_select_tables_use = $sql_select_tables;
				if ($is_referenced) {
					$sql_select_id_name_column = 'nodegoat_tos.object_id';
					$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');
					$sql_select_tables_use = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = nodegoat_tos.object_id AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref').")";
				} else {
					$sql_select_id_name_column = $sql_select_id;
				}
				
				$arr_type_object_path = StoreType::getTypeObjectPath('name', $arr_object_sub_description['object_sub_description_ref_type_id']);
				$sql_dynamic_type_name_column = $this->format2SQLDynamicTypeObjectNameColumn($arr_type_object_path, $sql_select_id_name_column);

				$sql_column = "(SELECT ".DBFunctions::sqlImplode($sql_dynamic_type_name_column['column'], ' ')." AS column_value
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." AS ".$table_name."
						".($is_referenced ? "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS nodegoat_tos ON (nodegoat_tos.id = ".$table_name.".object_sub_id AND ".$version_select_tos.")" : "")."
						".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.object_sub_".$object_sub_details_id."_id = ".$table_name.".object_sub_id AND nodegoat_to_store.object_sub_definition_".$object_sub_description_id."_ref_object_id = ".$table_name.".ref_object_id)" : "")."
						".$sql_select_tables_use."
						".$sql_dynamic_type_name_column['tables']."
					WHERE ".$table_name.".object_sub_id = ".$sql_table_ref." AND ".$table_name.".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select."
					GROUP BY ".$table_name.".object_sub_id
				)";
			}
		} else {
			
			if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
				
				if ($arr_selection['object_sub_description_reference']) {
					
					$this->arr_type_object_name_object_sub_description_ids[$object_sub_details_id][$object_sub_description_id] = $object_sub_description_id;
					
					$sql_concat = "nodegoat_to_ref.id, '".self::$sql_separator_group2."', nodegoat_to_ref.type_id, '".self::$sql_separator_group2."', '![[', nodegoat_to_ref.type_id, '_', nodegoat_to_ref.id, ']]'";
				
					if (!$arr_object_sub_description['object_sub_description_ref_type_id']) {
						$sql_concat .= ", '".self::$sql_separator_group2."', ".$table_name."_ref.value";
					}
					
					$sql_select_id = "(SELECT ".DBFunctions::sqlImplode("CONCAT(".$sql_concat.")", self::$sql_separator_group)."
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." ".$table_name."_ref
							".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.object_sub_".$object_sub_details_id."_id = ".$table_name."_ref.object_sub_id AND nodegoat_to_store.object_sub_definition_".$object_sub_description_id."_ref_object_id = ".$table_name."_ref.ref_object_id)" : "")."
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$table_name."_ref.ref_object_id
								".(is_array($arr_selection['object_sub_description_reference']) ? "AND nodegoat_to_ref.type_id IN (".implode(',', $arr_selection['object_sub_description_reference']).")" : '')."
								AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref')."
							)
						WHERE ".$table_name."_ref.object_sub_id = ".$sql_table_ref." AND ".$table_name."_ref.object_sub_description_id = ".$table_name.".object_sub_description_id AND ".$table_name."_ref.state = 1
					)";
				}
			}

			if (!$arr_object_sub_description['object_sub_description_ref_type_id']) {
				
				$sql_value = $table_name.".".StoreType::getValueTypeValue($arr_object_sub_description['object_sub_description_value_type']);
				
				if ($this->arr_order_object_subs[$object_sub_details_id][$object_sub_description_id]) {
					
					$sql_column = "(SELECT ".($arr_object_description['object_sub_description_has_multi'] ? DBFunctions::sqlImplode($sql_value, ' ') : $sql_value)." AS column_value
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'])." AS ".$table_name."
							".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.object_sub_".$object_sub_details_id."_id = ".$table_name.".object_sub_id AND nodegoat_to_store.object_sub_definition_".$object_sub_description_id."_value = ".$table_name.".value)" : "")."
						WHERE ".$table_name.".object_sub_id = ".$sql_table_ref." AND ".$table_name.".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select."
					)";
				}
				
				if ($arr_selection['object_sub_description_value']) {
						
					if ($arr_object_sub_description['object_sub_description_has_multi']) {
						
						$sql_select_id = $table_name.".identifier";
					}
					
					$sql_select_value = ($this->view == 'set' || $this->view == 'storage' ? $sql_value : StoreTypeObjects::formatFromSQLValue($arr_object_sub_description['object_sub_description_value_type'], $sql_value, $arr_object_sub_description['object_sub_description_value_type_options']));			
				}
			}
		}
		
		return ['select_value' => $sql_select_value, 'select_id' => $sql_select_id, 'group' => $sql_group, 'column' => $sql_column, 'tables' => $sql_select_tables];			
	}
	
	protected function generateTablesColumnsObjectSubDate($table_name, $search = false) {
		
		$table_name_date = $table_name."_date";
		
		$arr = [];
		
		$arr['tables'] = "
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$table_name_date." ON (".$table_name_date.".object_sub_id = ".$table_name.".id AND ".$table_name_date.".version = ".$table_name.".date_version)
			LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$table_name_date."_det ON (".$table_name_date."_det.id = ".$table_name.".object_sub_details_id)
			
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_date."_use_tos ON (".$table_name_date."_use_tos.object_id = ".$table_name.".object_id AND ".$table_name_date."_use_tos.object_sub_details_id = ".$table_name_date."_det.date_use_object_sub_details_id AND ".$table_name_date."_use_tos.active = TRUE)
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$table_name_date."_use_tos_date ON (".$table_name_date."_use_tos_date.object_sub_id = ".$table_name_date."_use_tos.id AND ".$table_name_date."_use_tos_date.version = ".$table_name_date."_use_tos.date_version)
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$table_name_date."_use_tos_def ON (".$table_name_date."_use_tos_def.object_sub_id = ".$table_name.".id AND ".$table_name_date."_use_tos_def.object_sub_description_id = ".$table_name_date."_det.date_use_object_sub_description_id AND ".$table_name_date."_use_tos_def.active = TRUE)
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$table_name_date."_use_to_def ON (".$table_name_date."_use_to_def.object_id = ".$table_name.".object_id AND ".$table_name_date."_use_to_def.object_description_id = ".$table_name_date."_det.date_use_object_description_id AND ".$table_name_date."_use_to_def.active = TRUE)
		";
		
		$sql_value = StoreType::getValueTypeValue('date');
		
		$arr['column_start'] = "CASE
			WHEN ".$table_name_date."_use_tos.date_version IS NOT NULL THEN ".$table_name_date."_use_tos_date.date_start
			WHEN ".$table_name_date."_use_tos_def.".$sql_value." != 0 THEN ".$table_name_date."_use_tos_def.".$sql_value."
			WHEN ".$table_name_date."_use_to_def.".$sql_value." != 0 THEN ".$table_name_date."_use_to_def.".$sql_value."
			WHEN ".$table_name.".date_version IS NOT NULL THEN ".$table_name_date.".date_start
			ELSE NULL
		END";
		$arr['column_end'] = "CASE
			WHEN ".$table_name_date."_use_tos.date_version IS NOT NULL THEN ".$table_name_date."_use_tos_date.date_end
			WHEN ".$table_name_date."_use_tos_def.".$sql_value." != 0 THEN ".$table_name_date."_use_tos_def.".$sql_value."
			WHEN ".$table_name_date."_use_to_def.".$sql_value." != 0 THEN ".$table_name_date."_use_to_def.".$sql_value."
			WHEN ".$table_name.".date_version IS NOT NULL THEN ".$table_name_date.".date_end
			ELSE NULL
		END";
		
		return $arr;
	}
	
	protected function generateTablesColumnsObjectSubLocationReference($table_name, $search = false, $reference_only = false) {
		
		$table_name_loc = $table_name."_loc";

		if ($search) {
			
			// Check cache
			$module = 'cms_nodegoat_definitions';
			$method = 'runTypeObjectCaching';
			
			$arr_job = cms_jobs::getJob($module, $method);
			
			if ($arr_job['date_executed']['previous']) { // Check if there is job present to keep track of caching
				
				$date_updated = FilterTypeObjects::getTypesUpdatedSince($arr_job['date_executed']['previous'], $this->arr_scope['types'], 'date');
			
				if ($date_updated && $date_updated > $arr_job['date_executed']['previous']) { // Prevent updates in the same minute
					
					status(getLabel('msg_building_cache'), false, getLabel('msg_wait'));

					cms_jobs::runJob($module, $method, $date_updated);
				}

				$arr = [];

				$arr['column_ref_object_id'] = $table_name_loc.'_cache.ref_object_id';
				$arr['column_ref_type_id'] = $table_name_loc.'_cache.ref_type_id';
				$arr['column_ref_object_sub_details_id'] = $table_name_loc.'_cache.ref_object_sub_details_id';
				
				$arr['column_ref_show_object_id'] = $table_name_loc.'_cache.ref_object_id';
				$arr['column_ref_show_type_id'] = $table_name_loc.'_cache.ref_type_id';
				$arr['column_ref_show_object_sub_details_id'] = $table_name_loc.'_cache.ref_object_sub_details_id';
				
				$arr['column_object_sub_details_id'] = $table_name_loc.'_cache.object_sub_details_id';
				
				$arr['tables'] = "
					LEFT JOIN ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION')." ".$table_name_loc."_cache ON (".$table_name_loc."_cache.object_sub_id = ".$table_name.".id AND ".$table_name_loc."_cache.status = 0)
				";
				
				if (!$reference_only) {
					
					$arr['column_geometry'] = $table_name_loc.'_geo.geometry';
					$arr['column_geometry_readable'] = StoreTypeObjects::formatFromSQLValue('geometry', $table_name_loc.'_geo.geometry');
					$arr['column_geometry_object_sub_id'] = $table_name_loc.'.id';
					$arr['column_geometry_object_id'] = $table_name_loc.'_cache.geometry_object_id';
					$arr['column_geometry_type_id'] = $table_name_loc.'_cache.geometry_type_id';
					
					$arr['tables'] .= "
						LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_loc." ON (".$table_name_loc.".id = ".$table_name_loc."_cache.geometry_object_sub_id AND ".$table_name_loc.".active = TRUE)
						LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_loc."_geo ON (".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = ".$table_name_loc.".location_geometry_version)
					";
				}
			} else {
				
				$search = false;
			}
		}
		
		if (!$search) {

			$arr = self::format2SQLObjectSubLocationReference($table_name, ($reference_only ? 1 : 3));
			
			if (!$reference_only) {
				
				$arr['column_geometry'] = "CASE
					WHEN ".$table_name_loc.".id IS NOT NULL THEN (SELECT geometry FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_loc."_geo WHERE ".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = ".$table_name_loc.".location_geometry_version)
					WHEN ".$table_name.".location_geometry_version IS NOT NULL THEN (SELECT geometry FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name."_geo WHERE ".$table_name."_geo.object_sub_id = ".$table_name.".id AND ".$table_name."_geo.version = ".$table_name.".location_geometry_version)
					ELSE NULL
				END";
				$arr['column_geometry_readable'] = "CASE
					WHEN ".$table_name_loc.".id IS NOT NULL THEN (SELECT ".StoreTypeObjects::formatFromSQLValue('geometry', 'geometry')." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_loc."_geo WHERE ".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = ".$table_name_loc.".location_geometry_version)
					WHEN ".$table_name.".location_geometry_version IS NOT NULL THEN (SELECT ".StoreTypeObjects::formatFromSQLValue('geometry', 'geometry')." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name."_geo WHERE ".$table_name."_geo.object_sub_id = ".$table_name.".id AND ".$table_name."_geo.version = ".$table_name.".location_geometry_version)
					ELSE ''
				END";
				
				$arr['tables'] .= " 
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_loc." ON (".$table_name_loc.".id = ".$arr['column_geometry_object_sub_id']." AND ".$table_name_loc.".active = TRUE)
				";
			}
		}
		
		$arr['table_name_location'] = $table_name_loc;
		$arr['table_name_cache'] = DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION');
		$arr['table_cache_status'] = $arr['table_name_cache'].".status = 0";
		
		return $arr;
	}
	
	protected function generateVersion($type, $table_name, $data_type = false) {
		
		return self::generateVersioning($this->versioning, $type, $table_name, $data_type);
	}
	
	public static function generateVersioning($versioning, $type, $table_name, $data_type = false) {
		
		switch ($type) {
			case 'object':
				if ($versioning == 'full') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status > 0) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,3)))"; // New, changed or deleted object, or current object
				} else if ($versioning == 'added') {	
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status IN (1,3)) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,2,3)))"; // Object is new or new object is deleted, current object
				} else {
					$sql = $table_name.".active = TRUE"; // Current object
				}
				break;
			case 'object_sub':
				if ($versioning == 'full') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status > 0) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,3)))"; // New, changed or deleted subobject, current subobject
				} else if ($versioning == 'added') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status = 1) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,2,3)))"; // Subobject is new, current subobject
				} else {
					$sql = $table_name.".active = TRUE"; // Current subobject
				}
				break;
			case 'record':
				switch ($data_type) {
					case 'text_layout':
					case 'text_tags':
					case 'reversed_collection':
						$sql = "(".$table_name.".active = FALSE AND ".$table_name.".version = -10)";
						break;
				}
				if ($sql) {
					break;
				}
				if ($versioning == 'full') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status IN (1,2)) OR (".$table_name.".active = TRUE AND ".$table_name.".status = 0))"; // New or changed record, current record, no deleted record
				} else if ($versioning == 'added') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status = 1) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,2,3)))"; // Record is new, current record
				} else {
					$sql = $table_name.".active = TRUE"; // Current record
				}
				break;
			case 'name':
			case 'search':
				switch ($data_type) {
					case 'text_layout':
					case 'text_tags':
					case 'reversed_collection':
						$sql = "(".$table_name.".active = FALSE AND ".$table_name.".version = -10)";
						break;
				}
				if ($sql) {
					break;
				}
				if ($versioning == 'full') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status > 0) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,3)))"; // New, changed or deleted record, current record
				} else {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status = 1) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,2,3)))"; // Record is new, current record
				}
				break;
		}
		
		return $sql;
	}
	
	protected function generateReferencedObjectSubDescriptionIds($object_sub_details_id = 'all') {
		
		$arr = [];
		
		if ($object_sub_details_id == 'all') {
			
			if ($this->arr_type_set['type']['include_referenced']['object_sub_details']) {
			
				foreach ($this->arr_type_set['type']['include_referenced']['object_sub_details'] as $cur_object_sub_details_id => $arr_object_sub_description_ids) {
										
					$arr += $arr_object_sub_description_ids;
				}
			}
		} else if ($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]) {
			
			$arr = $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id];
		}
		
		return $arr;
	}
	
	protected function addTypeFound($type, $type_id) {
		
		$this->arr_types_found[$type][$type_id] = $type_id;
	}

	public function getResultInfo($post = false) {
		
		if ($post) {
			
			return [
				'types_found' => $this->arr_types_found
			];
		}
		
		$s_arr =& $_SESSION['FilterTypeObjects'][$this->type_id][$this->versioning];
			
		if (!$s_arr['total']) {
			
			$version_select = $this->generateVersion('object', 'nodegoat_to');

			// Total data set length
			$res = DB::query("SELECT COUNT(nodegoat_to.id)
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				WHERE nodegoat_to.type_id = ".$this->type_id." AND ".$version_select
			);
				
			$arr_row = $res->fetchRow();
			
			$s_arr['total'] = $arr_row[0];
		}
		
		if ($this->arr_combine_filters) {
			
			if ($s_arr['query']['value'] !== $this->arr_combine_filters) {
				
				// Data set length after filtering
				
				if ($this->table_name && !$this->arr_limit) {
					
					$res = DB::query("SELECT COUNT(id)
							FROM ".$this->table_name_objects
					);
					
					$arr_row = $res->fetchRow();
					
					$total = $arr_row[0];
				} else {

					$sql_query = $this->sqlQuery('count');
					
					$this->initPre();
					
					$res = DB::query($sql_query);
					
					$arr_row = $res->fetchRow();
					$total = $arr_row[0];
				}
				
				$s_arr['query']['total'] = $total;
				$s_arr['query']['value'] = $this->arr_combine_filters;
			}
		} else {
			
			$s_arr['query']['total'] = $s_arr['total'];
			$s_arr['query']['value'] = false;
		}

		return [
			'total' => $s_arr['total'],
			'total_filtered' => (isset($s_arr['query']['total']) ? $s_arr['query']['total'] : $s_arr['total'])
		];
	}
	
	public function getResultInfoObjectSubs($object_id, $object_sub_details_id) {
		
		$object_id = (is_array($object_id) ? serialize($object_id) : $object_id); // Possibility to get combined object results
		
		$s_arr =& $_SESSION['FilterTypeObjects'][$this->type_id][$this->versioning]['object'][$object_id][$object_sub_details_id];

		if (!$s_arr['total']) {
			
			$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');
			
			if ($object_sub_details_id == 'all' || !$this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]) {
			
				$sql = "SELECT COUNT(nodegoat_tos.id)
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
						JOIN ".$this->table_name_objects." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
					WHERE object_sub_details_id ".($object_sub_details_id == 'all' ? 'IN ('.implode(',', $this->arr_selection_object_sub_details_ids).')' : "= ".$object_sub_details_id)."
						AND ".$version_select_tos;
			}
			
			$arr_referenced_object_sub_description_ids = $this->generateReferencedObjectSubDescriptionIds($object_sub_details_id);
			
			if ($arr_referenced_object_sub_description_ids) {
								
				$version_select = $this->generateVersion('record', "nodegoat_tos_def");
				
				$sql_referenced = "SELECT COUNT(nodegoat_tos.id)
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
						JOIN ".$this->table_name_objects." nodegoat_to ON (nodegoat_to.id = nodegoat_tos_def.ref_object_id)
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
					WHERE nodegoat_tos_def.object_sub_description_id IN (".implode(',', $arr_referenced_object_sub_description_ids).")
						AND ".$version_select;
			}
			
			if ($sql && $sql_referenced) {
				
				$arr_res = DB::queryMulti($sql.';'.$sql_referenced.';');
			} else {
				
				if ($sql_referenced) {
				
					$sql = $sql_referenced;
				}
				
				$arr_res[] = DB::query($sql);
			}
			
			$s_arr['total'] = 0;

			// Total data set length
			foreach ($arr_res as $res) {
				
				$arr_row = $res->fetchRow();
			
				$s_arr['total'] += $arr_row[0];
			}
		}
		
		if ($this->arr_combine_filters['object_subs'] || $this->arr_sql_filter_subs[$object_sub_details_id]) {
			
			$contains_referenced = $this->arr_type_set['type']['include_referenced']['object_sub_details'];
			
			if ($s_arr['query']['value'] !== $this->arr_combine_filters) {
				
				$is_referenced = ($contains_referenced && $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]);
				$has_referenced = ($is_referenced || ($object_sub_details_id == 'all' && $contains_referenced));
				
				if ($has_referenced) {
					$sql_query = $this->sqlQuerySub('count_object_sub_ids_object_ids_referenced_object_sub_description_ids', $object_sub_details_id, $this->table_name_objects);
				} else {
					$sql_query = $this->sqlQuerySub('count_object_sub_ids', $object_sub_details_id, $this->table_name_objects);
				}

				$this->initPre();
				
				$res = DB::query($sql_query);
				
				$arr_row = $res->fetchRow();
				$total = $arr_row[0];
				
				$s_arr['query']['total'] = $total;
				$s_arr['query']['value'] = $this->arr_combine_filters;
			}
		} else {
			
			$s_arr['query']['total'] = $s_arr['total'];
			$s_arr['query']['value'] = false;
		}
		
		return [
			'total' => $s_arr['total'],
			'total_filtered' => (isset($s_arr['query']['total']) ? $s_arr['query']['total'] : $s_arr['total'])
		];
	}
	
	public function resetResultInfo() {
		
		unset($_SESSION['FilterTypeObjects'][$this->type_id]);
	}
	
	public function getInfoObjectSubs() {
		
		$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');

		$sql = "SELECT nodegoat_to.id, nodegoat_tos.object_sub_details_id, 0, COUNT(nodegoat_tos.id)
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
			JOIN ".$this->table_name_objects." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
			WHERE ".$version_select_tos."
			GROUP BY nodegoat_to.id, nodegoat_tos.object_sub_details_id
		";
		
		$arr_referenced_object_sub_description_ids = $this->generateReferencedObjectSubDescriptionIds();
			
		if ($arr_referenced_object_sub_description_ids) {
						
			$version_select = $this->generateVersion('record', "nodegoat_tos_def");
			
			$sql_referenced = "SELECT nodegoat_to.id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def.object_sub_description_id, COUNT(nodegoat_tos.id)
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." nodegoat_tos_def
				JOIN ".$this->table_name_objects." nodegoat_to ON (nodegoat_to.id = nodegoat_tos_def.ref_object_id)
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND ".$version_select_tos.")
				WHERE nodegoat_tos_def.object_sub_description_id IN (".implode(',', $arr_referenced_object_sub_description_ids).")
					AND ".$version_select."
				GROUP BY nodegoat_to.id, nodegoat_tos.object_sub_details_id, nodegoat_tos_def.object_sub_description_id
			";
		}
		
		if ($sql_referenced) {
			
			$arr_res = DB::queryMulti($sql.';'.$sql_referenced.';');
		} else {
			
			$arr_res[] = DB::query($sql);
		}

		$arr = [];
		
		foreach ($arr_res as $res) {
			
			while ($arr_row = $res->fetchRow()) {
				$arr[$arr_row[0]][($arr_row[2] ? $arr_row[1].'is0referenced'.$arr_row[2] : $arr_row[1])] = $arr_row[3];
			}
		}
		
		return $arr;
	}

	public function isFiltered() {
		
		return (($this->arr_sql_filter || $this->arr_combine_filters['table']) ? true : false);
	}
	
	private function generateTableName($to = true) {
		
		if (!$this->table_name_all) {
			
			$to = ($to === true ? $this->type_id : $to);
			
			$this->table_name_all = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.temp_type_object_filtering_'.hash('md5', SiteStartVars::getSessionId(true).($to ? $to : '')));
			
			$this->table_name_all_conditions = DB::getTableTemporary($this->table_name_all.'_co');
			
			$this->table_name = $this->table_name_all;
			$this->table_name_objects = $this->table_name_all;
		}
	}
	
	public function generateTemporaryTableName($name, $unique = false) {
		
		return $name.'_'.hash('md5', $this->getDifferentiationId().($unique ?: uniqid()));
	}
	
	public function setDifferentiationId($id) {
	
		$this->differentiation_id = $id;
	}
	
	protected function getDifferentiationId() {
	
		return ($this->differentiation_id ?: SiteStartVars::getSessionId(true));
	}
	
	public function storeResultTemporarily($to = true, $return_table_objects_only = false) {
		
		if (!$this->is_stored) {
			
			// Prepare overall settings
			
			$this->useSettings();
			
			$is_custom = (!is_bool($to) && $to);
			
			$this->generateTableName($to);

			$is_filtering = $this->isFilteringObject();
			
			$this->is_temporary = ((DB::ENGINE_IS_MYSQL && ($is_custom || $is_filtering)) || self::$keep_tables ? false : true);
			
			// Prepare main table(s)
			
			$arr_sql_keys = $this->sqlKeys('storage', $this->table_name_all);
			
			self::dropResult($this->table_name_all);

			self::$arr_storage_tables[$this->table_name_all] = ['temporary' => $this->is_temporary];

			if ($arr_sql_keys['has_filtering'] || $this->nr_limit_generate) { // The main table has duplicate object ids (has_filtering) or more than the currently needed object ids (nr_limit_generate)
				
				$this->table_name_objects = DB::getTableTemporary($this->table_name_all.'_to');
				
				self::dropResult($this->table_name_objects);

				self::$arr_storage_tables[$this->table_name_objects] = ['temporary' => $this->is_temporary];
			}
			
			if ($this->nr_limit_generate) {
				
				$this->table_name = DB::getTableTemporary($this->table_name_all.'_ba');
				
				self::dropResult($this->table_name);
				
				self::$arr_storage_tables[$this->table_name] = ['temporary' => $this->is_temporary];
			}
			
			// Run all

			$sql_query = $this->sqlQuery('storage');
			
			$sql_query = "CREATE ".($this->is_temporary ? "TEMPORARY" : "")." TABLE ".$this->table_name_all." (
					".$arr_sql_keys['columns']."
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY)."
					".(DB::ENGINE_IS_MYSQL ? "AS (".$sql_query.")"
					:
					"; INSERT INTO ".$this->table_name_all." (".implode(',', $arr_sql_keys['select']).")
						(".$sql_query.")
					")."
			";
				
			$this->initPre();
			
			DB::queryMulti($sql_query.($arr_sql_keys['indexes'] ? ';'.$arr_sql_keys['indexes'] : ''));
						
			$this->storeResultTemporarilyGenerate();

			$this->is_stored = true;
		}

		return ($return_table_objects_only ? $this->table_name_objects : $this->table_name);
	}
	
	protected function storeResultTemporarilyReload() {
		
		$arr_sql_keys = $this->sqlKeys('storage', $this->table_name_all);
		$sql_query = $this->sqlQuery('storage');
		
		$sql_query = "
			TRUNCATE TABLE ".$this->table_name_all.";
			
			INSERT INTO ".$this->table_name_all." (".implode(',', $arr_sql_keys['select']).")
				(".$sql_query.");
		";
			
		$this->reInitPre();
		
		DB::queryMulti($sql_query);
		
		$this->storeResultTemporarilyGenerate(); // Check for a needed reload of the table_name_objects table in case of filtering
	}	
	
	protected function storeResultTemporarilyGenerate() {
		
		if ($this->table_name_objects != $this->table_name_all) {
				
			DB::queryMulti("
				CREATE ".(self::$arr_storage_tables[$this->table_name_objects]['temporary'] ? "TEMPORARY" : "")." TABLE IF NOT EXISTS ".$this->table_name_objects." (
					id INT,
					PRIMARY KEY (id)
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
				
				TRUNCATE TABLE ".$this->table_name_objects.";
							
				INSERT INTO ".$this->table_name_objects."
					(SELECT DISTINCT id
						FROM ".$this->table_name_all."
						".($this->nr_limit_generate ? "LIMIT ".$this->nr_limit_generate." OFFSET ".$this->nr_offset_generate : "")."
					)
				;
			");
		}
		
		if ($this->table_name != $this->table_name_all) {
			
			DB::queryMulti("
				CREATE ".(self::$arr_storage_tables[$this->table_name]['temporary'] ? "TEMPORARY" : "")." TABLE IF NOT EXISTS ".$this->table_name."
					LIKE ".$this->table_name_all.";
				
				TRUNCATE TABLE ".$this->table_name.";

				INSERT INTO ".$this->table_name."
					(SELECT nodegoat_to_all.*
						FROM ".$this->table_name_objects." nodegoat_to_to
						JOIN ".$this->table_name_all." nodegoat_to_all ON (nodegoat_to_all.id = nodegoat_to_to.id)
					);
			");
		}
	}
	
	public function setDepth(&$arr_depth) {
		
		$this->arr_depth =& $arr_depth;
	}
	
	public function addDepth($arr_filter) {
		
		$hash = hash('md5', serialize($arr_filter));
		
		$this->arr_depth[$this->type_id]['arr_filters']['filter_'.$hash] = $arr_filter;
	}
	
	public function &getDepth($include_self = false) {
		
		if ($include_self) {
			return $this->arr_depth;
		} else {
			return $this->arr_depth[$this->type_id];
		}
	}
		
	private function resetObjectConditions() {
		
		$this->arr_object_conditions = $this->arr_object_conditions_name = [];
	}
	
	private function setObjectNameConditions($target, $arr_actions) {
		
		if (!$this->arr_object_conditions_name[$target]) {
			$this->arr_object_conditions_name[$target] = []; // Make sure to clean redundant [object_description_id][/object_description_id] tags in the object name
		}
		if ($arr_actions) {
			$this->arr_object_conditions_name[$target][] = $arr_actions;
		}
	}
	
	private function setObjectConditions($target, $arr_actions) {
		
		if ($arr_actions) {
			$this->arr_object_conditions[$target][] = $arr_actions;
		}
	}

	private function applyObjectNameConditions(&$arr_object) {
		
		$str_object_name = $arr_object['object_name'];
		
		foreach ($this->arr_object_conditions_name as $target => $arr_condition_actions) {
			
			$str_open = $str_close = $str_spacing = $str_before = $str_after = $str_prefix = $str_affix = '';
			$arr_style = [];
			
			if ($target == 'object') {
				
				$str_self = $str_object_name;
			} else {
				
				$pos_start = strpos($str_object_name, '['.$target.']');
				
				if ($pos_start === false) { // The object description does not exist in the name
					continue;
				}
				
				$length_tag = strlen('['.$target.']');
				$pos_end = strpos($str_object_name, '[/'.$target.']')+$length_tag+1;
				$nr_spacing = (substr($str_object_name, $pos_start+$length_tag, 1) == ' ' ? (substr($str_object_name, $pos_start+$length_tag+1, 1) == '(' ? 2 : 1) : 0);
				
				$str_before = substr($str_object_name, 0, $pos_start);
				$str_after = substr($str_object_name, $pos_end);
				$pos_start = ($pos_start+$length_tag+$nr_spacing);
				$pos_end = ($pos_end-($length_tag+1));
				$str_self = substr($str_object_name, $pos_start, ($pos_end - $pos_start));
				
				if ($nr_spacing) {
					$str_spacing = ($nr_spacing == 2 ? ' (' : ' ');
				}
			}

			foreach ($arr_condition_actions as $arr_actions) {
				
				foreach ($arr_actions as $action => $arr_action) {
					
					switch ($action) {
						case 'background_color':
							$arr_style['background_color'] = ($this->conditions == 'visual' ? $arr_action['color'] : 'background-color: '.$arr_action['color'].';');
							break;
						case 'text_emphasis':
							foreach ($arr_action['emphasis'] as $value) {
								switch ($value) {
									case 'bold':
										$arr_style['font_weight'] = ($this->conditions == 'visual' ? 'bold' : 'font-weight: bold;');
										break;
									case 'italic':
										$arr_style['font_style'] = ($this->conditions == 'visual' ? 'italic' : 'font-style: italic;');
										break;
									case 'strikethrough':
										$arr_style['text_decoration'] = ($this->conditions == 'visual' ? 'line-through' : 'text-decoration: line-through;');
										break;
								}
							}
							break;
						case 'text_color':
							$arr_style['text_color'] = ($this->conditions == 'visual' ? $arr_action['color'] : 'color: '.$arr_action['color'].';');
							break;
						case 'limit_text':
							$arr_tag = Response::addParsePost(false, ['limit' => $arr_action['number'], 'affix' => $arr_action['value']]);
							$str_open = $str_open.$arr_tag['open'];
							$str_close = $arr_tag['close'].$str_close;
							break;
						case 'add_text_prefix':
							if ($target != 'object' && $arr_action['check']) { // 'check' means override default spacing
								$str_spacing = '';
							}
							$str_prefix = ($arr_action['check'] ? '' : $str_prefix).$arr_action['value']; // 'check' means override previous prefix
							break;
						case 'add_text_affix':
							$str_affix = ($arr_action['check'] ? '' : $str_affix).$arr_action['value']; // 'check' means override previous affix
							break;
					}
				}
			}
			
			if ($target == 'object') {
				if ($this->conditions == 'style_include' && $arr_style) {
					$str_open = '<span style="'.implode('', $arr_style).'">'.$str_open;
					$str_close = $str_close.'</span>';
				} else if ($this->conditions == 'style') {
					$arr_style_object = implode('', $arr_style);
				} else if ($this->conditions == 'visual') {
					$arr_style_object = $arr_style;
				}
				$str_object_name = $str_open.$str_prefix.$str_self.$str_affix.$str_close;
			} else {
				if (($this->conditions == 'style' || $this->conditions == 'style_include') && $arr_style) {
					$str_open = '<span style="'.implode('', $arr_style).'">'.$str_open;
					$str_close = $str_close.'</span>';
				}
				$str_object_name = $str_before.$str_spacing.$str_open.$str_prefix.$str_self.$str_affix.$str_close.$str_after;
			}
		}
		
		if ($arr_style_object) {
			$arr_object['object_name_style'] = $arr_style_object;
		}
		$arr_object['object_name'] = $str_object_name;
	}
	
	private function applyObjectConditions(&$arr) {
		
		foreach ($this->arr_object_conditions as $target => $arr_condition_actions) {
			
			$arr_style = [];
						
			foreach ($arr_condition_actions as $arr_actions) {
				
				$remove = false;
				
				foreach ($arr_actions as $action => $arr_action) {
					
					switch ($action) {
						case 'color':
							if ($this->conditions == 'visual') {
								$str_color = $arr_action['color'];
								if ($arr_style['color'] === null || !$arr_action['check']) {
									$arr_style['color'] = $str_color;
								} else {
									$arr_style['color'] = (array)$arr_style['color'];
									$arr_style['color'][] = $str_color;
								}
							}
							break;
						case 'background_color':
							$arr_style['background_color'] = ($this->conditions == 'visual' ? $arr_action['color'] : 'background-color: '.$arr_action['color'].';');
							break;
						case 'text_emphasis':
							foreach ($arr_action['emphasis'] as $value) {
								switch ($value) {
									case 'bold':
										$arr_style['font_weight'] = ($this->conditions == 'visual' ? 'bold' : 'font-weight: bold;');
										break;
									case 'italic':
										$arr_style['font_style'] = ($this->conditions == 'visual' ? 'italic' : 'font-style: italic;');
										break;
									case 'strikethrough':
										$arr_style['text_decoration'] = ($this->conditions == 'visual' ? 'line-through' : 'text-decoration: line-through;');
										break;
								}
							}
							break;
						case 'text_color':
							$arr_style['text_color'] = ($this->conditions == 'visual' ? $arr_action['color'] : 'color: '.$arr_action['color'].';');
							break;
						case 'weight':
							if ($this->conditions == 'visual') {
								$amount = (int)$arr_action['number'];
								if ($arr_action['object_description_value'] !== null) {
									if ($arr_action['object_description_value'] == 0 || ($arr_action['number'] !== null && $amount == 0)) { // Do not apply if weight calculation would end up 0
										break;
									}
									$amount = (($amount ?: 1) * $arr_action['object_description_value']);
								}
								if ($arr_style['weight'] === null || !$arr_action['check']) {
									$arr_style['weight'] = 0;
								}
								$arr_style['weight'] += $amount;
							}
							break;
						case 'remove':
							$remove = true;
							break;
						case 'geometry_color':
							if ($this->conditions == 'visual') {
								if ($arr_action['color']) {
									$arr_style['geometry_color'] = $arr_action['color'];
								}
								if ($arr_action['opacity']) {
									$arr_style['geometry_opacity'] = $arr_action['opacity'];
								}
							}
							break;
						case 'geometry_stroke_color':
							if ($this->conditions == 'visual') {
								if ($arr_action['color']) {
									$arr_style['geometry_stroke_color'] = $arr_action['color'];
								}
								if ($arr_action['opacity']) {
									$arr_style['geometry_stroke_opacity'] = $arr_action['opacity'];
								}
							}
							break;
						case 'icon':
							if ($this->conditions == 'visual') {
								$str_url = ($arr_action['image'] ? '/'.DIR_CUSTOM_PROJECT_WORKSPACE.$arr_action['image'] : '');
								if ($arr_style['icon'] === null || !$arr_action['check']) {
									$arr_style['icon'] = $str_url;
								} else {
									$arr_style['icon'] = (array)$arr_style['icon'];
									$arr_style['icon'][] = $str_url;
								}
							}
							break;
					}
				}
			}
			
			if ($remove) {
				$style = 'hide';
			} else {
				if ($this->conditions == 'style' || $this->conditions == 'style_include') {
					$style = implode('', $arr_style);
				} else if ($this->conditions == 'visual') {
					$style = $arr_style;
				}
			}
			
			if ($target == 'object') {
				$arr['object']['object_style'] = $style;
			} else if ($target == 'object_sub') {
				$arr['object_sub']['object_sub_style'] = $style;
			} else if ($arr['object_sub_definitions']) {
				$arr['object_sub_definitions'][$target]['object_sub_definition_style'] = $style;
			} else {
				$arr['object_definitions'][$target]['object_definition_style'] = $style;
			}
		}
	}
	
	public function setConditions($conditions = true, $arr_conditions = []) {
		
		// $conditions = true/"text" (only text conditions), "style/style_include", "visual"
		
		$conditions = ($conditions === true ? 'text' : $conditions);
		
		$this->conditions = $conditions;
		$this->arr_type_set_conditions = $arr_conditions;
	}
	
	public function setVersioning($version = 'full') {
		
		// $version = 'full', 'added'
				
		$this->versioning = $version;
	}
	
	public function setScope($arr_scope) {
		
		$this->arr_scope = [
			'users' => ($arr_scope['users'] ? (array)$arr_scope['users'] : []),
			'types' => ($arr_scope['types'] ? (array)$arr_scope['types'] : [])
		];
	}
	
	public function getScope() {
		
		return $this->arr_scope;
	}
		
	public function setOrder($arr_order, $overwrite = false) {
		
		// $arr_order = array('object_name' => "asc/desc", 'date' => "asc/desc", 'object_description_id => "asc/desc", object_description_id => "asc/desc")
		
		if ($overwrite || !$this->arr_order) {
			$this->arr_order = $arr_order;
		} else {
			$this->arr_order = $this->arr_order + $arr_order;
		}
	}
	
	public function setOrderObjectSubs($object_sub_details_id, $arr_order, $overwrite = false) {
		
		// $arr_order = array('object_sub_details_name' => "asc/desc", 'object_sub_date_start/object_sub_date_end' => "asc/desc", object_sub_description_id => "asc/desc")
		
		if ($overwrite || !$this->arr_order_object_subs[$object_sub_details_id]) {
			$this->arr_order_object_subs[$object_sub_details_id] = $arr_order;
		} else {
			$this->arr_order_object_subs[$object_sub_details_id] = $this->arr_order_object_subs[$object_sub_details_id] + $arr_order;
		}
	}
	
	public function setLimit($arr_limit) {
		
		// $arr_limit = 100, array(200, 100) (from 200 to 300)
		
		$arr_limit = (!$arr_limit || is_array($arr_limit) ? $arr_limit : [0, $arr_limit]);
		
		$this->arr_limit = $arr_limit;
		
		$this->sql_limit = ($arr_limit ? "LIMIT ".(int)$arr_limit[1]." OFFSET ".(int)$arr_limit[0] : "");
	}
	
	public function setLimitObjectSubs($object_sub_details_id, $arr_limit) {
				
		$arr_limit = (!$arr_limit || is_array($arr_limit) ? $arr_limit : [0, $arr_limit]);
		
		$this->arr_limit_object_subs[$object_sub_details_id] = $arr_limit;
		
		$this->sql_limit_object_subs[$object_sub_details_id] = ($arr_limit ? "LIMIT ".(int)$arr_limit[1]." OFFSET ".(int)$arr_limit[0] : "");
	}
			
	public function setSelection($arr_selection) {
		
		// $arr_selection = array();
		
		if ($arr_selection['object_sub_details']['all']) {
			
			$this->arr_type_set = (array)clone(object)$this->arr_type_set;
			$this->arr_type_set['object_sub_details']['all'] = ['object_sub_details' => [], 'object_sub_descriptions' => []];
		}
		
		$this->arr_selection = $arr_selection;
	}
	
	public function setFiltering($arr_filtering, $arr_filtering_enable, $arr_filtering_filters = [], $table_name_filtering = false) {
		
		// $arr_filtering = array();
		
		$this->arr_filtering = $arr_filtering; // Filtering trigger for this specific instance
		$this->arr_filtering_enable = $arr_filtering_enable; // Filtering trigger for overal 'awareness' (filtering before or after?) mode
		$this->arr_filtering_filters = $arr_filtering_filters; // Filtering filters for this specific instance, or true for all set filters
		$this->table_name_filtering = $table_name_filtering;
	}
	
	public function isFilteringObject($self = false) {
		
		$in_set = ($self ? ($this->arr_filtering['all'] || $this->arr_filtering['object']) : ($this->arr_filtering_enable['all'] || $this->arr_filtering_enable['object']));
		
		$is_filtering = $in_set;
		
		return $is_filtering;
	}
		
	public function isFilteringObjectDescription($object_description_id, $self = false, $in_query = false) {
		
		$in_set = ($self ? ($this->arr_filtering['all'] || $this->arr_filtering['object_descriptions'][$object_description_id]) : ($this->arr_filtering_enable['all'] || $this->arr_filtering_enable['object_descriptions'][$object_description_id]));
		if (!$in_query) {
			if (
				(($self && $this->arr_filtering['object_descriptions'][$object_description_id]) || (!$self && $this->arr_filtering_enable['object_descriptions'][$object_description_id]))
				||
				($this->query_object_descriptions['object_descriptions'][$object_description_id])
			) {
				$in_query = true;
			}
		}

		$is_filtering = ($in_set && $in_query && ($this->arr_type_set['object_descriptions'][$object_description_id]['object_description_ref_type_id'] || $this->arr_type_set['object_descriptions'][$object_description_id]['object_description_has_multi'] || $this->arr_type_set['object_descriptions'][$object_description_id]['object_description_is_dynamic']));
		
		return $is_filtering;
	}
	
	public function isQueryingObjectSubDetails($object_sub_details_id = false) {
		
		if ($object_sub_details_id) {
			$in_query = ($this->query_object_subs[$object_sub_details_id] && is_array($this->query_object_subs[$object_sub_details_id]));
		} else {
			$in_query = is_array($this->query_object_subs);
		}
		
		return $in_query;
	}
	
	public function isFilteringObjectSubDetails($object_sub_details_id = false, $self = false, $in_query = false) {
		
		if ($object_sub_details_id) {
			$in_set = ($self ? ($this->arr_filtering['all'] || $this->arr_filtering['object_sub_details'][$object_sub_details_id]) : ($this->arr_filtering_enable['all'] || $this->arr_filtering_enable['object_sub_details'][$object_sub_details_id]));
		} else {
			$in_set = ($self ? ($this->arr_filtering['all'] || $this->arr_filtering['object_sub_details']) : ($this->arr_filtering_enable['all'] || $this->arr_filtering_enable['all']['object_sub_details']));
		}
		if (!$in_query) {
			if ($object_sub_details_id) {
				if (
					(($self && $this->arr_filtering['object_sub_details'][$object_sub_details_id]) || (!$self && $this->arr_filtering_enable['object_sub_details'][$object_sub_details_id]))
					||
					$this->isQueryingObjectSubDetails($object_sub_details_id)
				) {
					$in_query = true;
				}
			} else {
				$in_query = $this->isQueryingObjectSubDetails();
			}
		}
		
		$is_filtering = ($in_set && $in_query);
				
		return $is_filtering;
	}
	
	public function isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id, $self = false, $in_query = false) {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$arr_filtering_object_sub_description = $this->arr_filtering['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$arr_filtering_enable_object_sub_description = $this->arr_filtering_enable['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		
		$in_set = ($self ? ($this->arr_filtering['all'] || $arr_filtering_object_sub_description) : ($this->arr_filtering_enable['all'] || $arr_filtering_enable_object_sub_description));
		
		$is_filtering = ($in_set && ($arr_object_sub_description['object_sub_description_ref_type_id'] || $arr_object_sub_description['object_sub_description_has_multi'] || $arr_object_sub_description['object_sub_description_is_dynamic']));
		
		if (!$in_query && $is_filtering) {
			
			if (($self && $arr_filtering_object_sub_description) || (!$self && $arr_filtering_enable_object_sub_description)) {
				
				$in_query = true;
			} else if ($this->isQueryingObjectSubDetails($object_sub_details_id)) {
				
				foreach ($this->query_object_subs[$object_sub_details_id] as $table_name_query_object_sub => $arr_query_object_sub) {
					
					if ($arr_query_object_sub['object_sub_descriptions'][$object_sub_description_id]) {
						$in_query = true;
						break;
					}
				}
			}
		}
		
		$is_filtering = ($is_filtering && $in_query);
				
		return $is_filtering;
	}
	
	private function format2SQLDynamicTypeObjectName($arr_type_object_path, $sql_table_source, $tagging = false) {
		
		$arr_sql_collect = [];
		$sql_tables = '';
		
		$use_object_name = $this->arr_type_set['type']['use_object_name']; // The object has its own name and use 'name (extra)'
		
		if ($use_object_name) {
			
			$arr_sql_collect[0] = ['object_description_id' => 0, 'object_sub_details_id' => 0, 'sql_column' => $sql_table_source.'.name'];
		}

		foreach ($arr_type_object_path as $arr_type_object_path_value) {

			if ($arr_type_object_path_value['org_object_description_id'] || !$arr_type_object_path_value['ref_object_description_id']) { // Nested objects or plain object name
				continue;
			}
			
			$object_description_id = $arr_type_object_path_value['ref_object_description_id'];
			$object_sub_details_id = $arr_type_object_path_value['ref_object_sub_details_id'];
			
			if ($object_sub_details_id) {
				
				$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_description_id];
				
				$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
				$ref_type_id = $arr_object_sub_description['object_sub_description_ref_type_id'];
				$value_type = $arr_object_sub_description['object_sub_description_value_type'];
				$table_name = "nodegoat_to_name_tos_def_".$object_description_id;
			} else {
				
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
				$is_dynamic = $arr_object_description['object_description_is_dynamic'];
				$ref_type_id = $arr_object_description['object_description_ref_type_id'];
				$value_type = $arr_object_description['object_description_value_type'];
				$table_name = "nodegoat_to_name_to_def_".$object_description_id;
			}
	
			// Values
				
			if ($ref_type_id && !$is_dynamic) {
								
				$sql_column = "CASE WHEN ".$table_name.".ref_object_id IS NOT NULL THEN CONCAT('![[', ".$ref_type_id.", '_', ".$table_name.".ref_object_id, ']]') ELSE NULL END";
				
				if ($object_sub_details_id) {
					$this->arr_type_object_name_object_sub_description_ids[$object_sub_details_id][$object_description_id] = $object_description_id;
				} else {
					$this->arr_type_object_name_object_description_ids[$object_description_id] = $object_description_id;
				}
				
				$sql_separator = ', ';
			} else {
				
				$sql_column = StoreTypeObjects::formatFromSQLValue($value_type, $table_name.".".StoreType::getValueTypeValue($value_type, 'name'));
				
				$sql_separator = ' ';
			}
			
			$sql_column = DBFunctions::sqlImplode("DISTINCT ".$sql_column, $sql_separator); // Always group, even for supposedly individual values, to create valid aggregate (groupable) columns
			$sql_column = "COALESCE(".$sql_column.", '')"; // Set value to plain empty when there really is nothing
			
			$arr_sql_collect[] = ['object_description_id' => $object_description_id, 'object_sub_details_id' => $object_sub_details_id, 'sql_column' => $sql_column];
			
			// Tables
			
			$version_select = $this->generateVersion('name', $table_name, $value_type);
			
			if ($object_sub_details_id) {
				
				$version_select_tos = $this->generateVersion('name', $table_name.'_tos', $value_type);

				$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_tos ON (".$table_name."_tos.object_sub_details_id = ".$object_sub_details_id." AND ".$table_name."_tos.object_id = ".$sql_table_source.".id AND ".$version_select_tos.")";
				$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'name')." ".$table_name." ON (".$table_name.".object_sub_id = ".$table_name."_tos.id AND ".$table_name.".object_sub_description_id = ".$object_description_id." AND ".$version_select.")";
			} else {
				
				$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'name')." ".$table_name." ON (".$table_name.".object_description_id = ".$object_description_id." AND ".$table_name.".object_id = ".$sql_table_source.".id AND ".$version_select.")";
			}
		}
		
		$arr_sql_concat = [];
		
		$count = 1;
		$length = count($arr_sql_collect);
		
		foreach ($arr_sql_collect as $arr_sql_column) {
			
			$object_description_id = $arr_sql_column['object_description_id'];
			$object_sub_details_id = $arr_sql_column['object_sub_details_id'];
			$sql_column = $arr_sql_column['sql_column'];
			
			if ($object_sub_details_id) {
				
				$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_description_id];
				
				$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
				$ref_type_id = $arr_object_sub_description['object_sub_description_ref_type_id'];
				$arr_conditions_object_sub_description = $this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_description_id];
				$is_condition = ($tagging && $arr_conditions_object_sub_description && count(arrValuesRecursive('condition_in_object_name', $arr_conditions_object_sub_description))); // Check if condition is needed specific for this object description
				$identifier = $object_description_id.'_'.$object_sub_details_id;
			} else {
				
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
				$is_dynamic = $arr_object_description['object_description_is_dynamic'];
				$ref_type_id = $arr_object_description['object_description_ref_type_id'];
				$arr_conditions_object_description = $this->arr_type_set_conditions['object_descriptions'][$object_description_id];
				$is_condition = ($tagging && $object_description_id && $arr_conditions_object_description && count(arrValuesRecursive('condition_in_object_name', $arr_conditions_object_description))); // Check if condition is needed specific for this object description
				$identifier = $object_description_id;
			}
			
			if ($is_condition) {
				$arr_sql_concat[] = "'[".$identifier."]'";
			}
			
			if ($use_object_name && $count == 2) {
				$arr_sql_concat[] = "' ('";
			}

			if (!(($count == 1 && $key == 0) || ($use_object_name && $count == 2 && $key == 0))) { // Spacing
				$arr_sql_concat[] = "CASE WHEN ".$sql_column." = '' THEN '' ELSE ' ' END";
			}
			
			// Select the column
			if ($ref_type_id && !$is_dynamic) {
				$arr_sql_concat[] = $sql_column;
			} else {
				$arr_sql_concat[] = "CASE WHEN ".$sql_column." LIKE '%[[%' THEN CONCAT('".Labels::addLanguageTags(true)."', ".$sql_column.", '".Labels::addLanguageTags(false)."') ELSE ".$sql_column." END"; // Add language delimiters when needed
			}
			
			if ($use_object_name && $count > 1 && $count == $length) {
				$arr_sql_concat[] = "')'";
			}
			
			if ($is_condition) {
				$arr_sql_concat[] = "'[/".$identifier."]'";
			}
			
			$count++;
		}
		
		return ['column' => ($arr_sql_concat ? "CONCAT(".(implode(',', $arr_sql_concat)).")" : "''"), 'tables' => $sql_tables];
	}
	
	private function format2SQLDynamicTypeObjectNameColumn($arr_type_object_path, $sql_table_ref = 'nodegoat_to.id', $is_display = false) {
		
		$arr_tables_reference = [];
		$arr_columns_concat = [];

		foreach ($arr_type_object_path as $arr_type_object_path_value) {
			
			$org_object_description_id = $arr_type_object_path_value['org_object_description_id'];
			$org_object_sub_details_id = $arr_type_object_path_value['org_object_sub_details_id'];
			$ref_object_description_id = $arr_type_object_path_value['ref_object_description_id'];
			$ref_object_sub_details_id = $arr_type_object_path_value['ref_object_sub_details_id'];
			
			if ($org_object_description_id) {
				
				$column_object_id = $arr_tables_reference[$org_object_description_id.'_'.$org_object_sub_details_id];
				
				if (!$column_object_id) { // Path is interrupted (e.g. dynamic path does not need additional segments)
					continue;
				}
			} else {

				$column_object_id = $sql_table_ref;
			}

			if (!$ref_object_description_id) { // An object's plain name in a path
				$table_name = "nodegoat_to_name_path_".$arr_type_object_path_value['ref_type_id']."_".$org_object_description_id."_".$org_object_sub_details_id."_".$arr_type_object_path_value['sort'];
			} else { // An object's description or sub-object description in a path
				$table_name = "nodegoat_to_name_path_".$ref_object_description_id."_".$ref_object_sub_details_id."_".$org_object_description_id."_".$org_object_sub_details_id."_".$arr_type_object_path_value['sort'];
			}
			
			$value_type = $arr_type_object_path_value['value_type'];
					
			// Columns
			if (!$arr_type_object_path_value['is_reference'] || $arr_type_object_path_value['is_dynamic']) {
				
				if (!$ref_object_description_id) {
					
					$sql_column = $table_name.".name";
				} else {
					
					$sql_column = StoreTypeObjects::formatFromSQLValue($value_type, $table_name.".".StoreType::getValueTypeValue($value_type, 'name'));
					
					if ($is_display) {
						 $sql_column = "CASE WHEN ".$sql_column." LIKE '%[[%' THEN CONCAT('".Labels::addLanguageTags(true)."', ".$sql_column.", '".Labels::addLanguageTags(false)."') ELSE ".$sql_column." END"; // Add language delimiters when needed
					}
				}
				
				$arr_columns_concat[] = "COALESCE(".$sql_column.", ' ')";
			}
				
			// Tables
			
			$version_select = $this->generateVersion('name', $table_name, $value_type);
		
			if (!$ref_object_description_id) {
								
				$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." ".$table_name." ON (".$table_name.".id = ".$column_object_id." AND ".$version_select.")";
			} else {
				
				if ($arr_type_object_path_value['is_reference'] && !$arr_type_object_path_value['is_dynamic']) {
					$arr_tables_reference[$ref_object_description_id.'_'.$ref_object_sub_details_id] = $table_name.".ref_object_id";
				}
				
				if ($ref_object_sub_details_id) {
					
					$version_select_tos = $this->generateVersion('name', $table_name.'_tos', $value_type);

					$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_tos ON (".$table_name."_tos.object_sub_details_id = ".$ref_object_sub_details_id." AND ".$table_name."_tos.object_id = ".$column_object_id." AND ".$version_select_tos.")";
					$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'name')." ".$table_name." ON (".$table_name.".object_sub_id = ".$table_name."_tos.id AND ".$table_name.".object_sub_description_id = ".$ref_object_description_id." AND ".$version_select.")";
				} else {
					
					$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'name')." ".$table_name." ON (".$table_name.".object_description_id = ".$ref_object_description_id." AND ".$table_name.".object_id = ".$column_object_id." AND ".$version_select.")";
				}
			}
		}
		
		$sql_column = ($arr_columns_concat ? "CONCAT(".($is_display ? implode(", ' ',", $arr_columns_concat) : implode(",", $arr_columns_concat)).")" : "''");
		
		return ['column' => $sql_column, 'tables' => $sql_tables];
	}
	
	public static function format2SQLObjectSubLocationReference($sql_table_source, $count = 4, $sql_table_source_details = false) {
		
		$arr = [];
		
		$cur_sql_table = $sql_table_source;
		$cur_sql_table_details = ($sql_table_source_details ?: $cur_sql_table."_det");
		
		$func_sql_select_date = function($sql_table, $start_end) {
			
			return "CASE
				WHEN ".$sql_table."_loc_date_use_tos.object_id IS NOT NULL THEN (SELECT date_".($start_end == 'start' ? 'start' : 'end')." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." nodegoat_tos_date WHERE nodegoat_tos_date.object_sub_id = ".$sql_table."_loc_date_use_tos.id AND nodegoat_tos_date.version = ".$sql_table."_loc_date_use_tos.date_version)
				WHEN ".$sql_table."_loc_date_use_tos_def.object_sub_id IS NOT NULL THEN ".$sql_table."_loc_date_use_tos_def.value_int
				WHEN ".$sql_table."_loc_date_use_to_def.object_id IS NOT NULL THEN ".$sql_table."_loc_date_use_to_def.value_int
				ELSE (SELECT date_".($start_end == 'start' ? 'start' : 'end')." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." nodegoat_tos_date WHERE nodegoat_tos_date.object_sub_id = ".$sql_table.".id AND nodegoat_tos_date.version = ".$sql_table.".date_version)
			END";
		};
				
		for ($i = 1; $i <= $count; $i++) {
		
			$new_sql_table = $sql_table_source."_".($i+1);
			$new_sql_table_details = $new_sql_table."_det";
			
			$sql_no_recursion = '';
			
			for ($j = 1; $j <= $i; $j++) {
				$sql_no_recursion .= " AND ".$new_sql_table.".id != ".$sql_table_source.($j == 1 ? "" : "_".$j).".id";
			}
			
			if ($i == 1) {
				
				// Select the end-point with the actual location
				$column_geometry_object_id = "CASE";
				$column_geometry_type_id = "CASE";
				$column_geometry_object_sub_id = "CASE";
				
				// Select the most relevant referencing object for display purposes
				$column_ref_show_object_id = "CASE";
				$column_ref_show_type_id = "CASE";
				$column_ref_show_object_sub_details_id = "CASE";
				
				// Select the referencing object
				// loc_use_nodegoat_to_referencing is an exception (could be relayed to another type), so rely on the location_ref_object_id itself
				$arr['column_ref_object_id'] = "CASE
					WHEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_id IS NOT NULL OR ".$new_sql_table."_pre.object_id IS NULL THEN
						CASE WHEN ".$cur_sql_table.".location_ref_object_id != 0 THEN ".$cur_sql_table.".location_ref_object_id ELSE NULL END
					ELSE ".$new_sql_table."_pre.object_id
				END";
				$arr['column_ref_type_id'] = "CASE WHEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_type_id ELSE ".$new_sql_table_details.".type_id END";
				$arr['column_ref_object_sub_details_id'] = "CASE WHEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_object_sub_details_id ELSE ".$new_sql_table_details.".id END";
				$arr['column_object_sub_details_id'] = $cur_sql_table.".object_sub_details_id"; // Index purposes
				
				if (!$sql_table_source_details) { // Add a first object_sub_details table when not already added
					
					$arr['tables'] .= "
						LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$cur_sql_table_details." ON (".$cur_sql_table_details.".id = ".$cur_sql_table.".object_sub_details_id)
					";
				}
				
				// Add the required tables for the sourced sub-object
				$arr['tables'] .= "
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$cur_sql_table."_loc_date_use_tos ON (".$cur_sql_table."_loc_date_use_tos.object_id = ".$cur_sql_table.".object_id AND ".$cur_sql_table."_loc_date_use_tos.object_sub_details_id = ".$cur_sql_table_details.".date_use_object_sub_details_id AND ".$cur_sql_table."_loc_date_use_tos.active = TRUE)
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$cur_sql_table."_loc_date_use_tos_def ON (".$cur_sql_table."_loc_date_use_tos_def.object_sub_id = ".$cur_sql_table.".id AND ".$cur_sql_table."_loc_date_use_tos_def.object_sub_description_id = ".$cur_sql_table_details.".date_use_object_sub_description_id AND ".$cur_sql_table."_loc_date_use_tos_def.active = TRUE)
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$cur_sql_table."_loc_date_use_to_def ON (".$cur_sql_table."_loc_date_use_to_def.object_id = ".$cur_sql_table.".object_id AND ".$cur_sql_table."_loc_date_use_to_def.object_description_id = ".$cur_sql_table_details.".date_use_object_description_id AND ".$cur_sql_table."_loc_date_use_to_def.active = TRUE)		
				";
			}
			
			$column_geometry_object_id .= "
				WHEN ".$cur_sql_table.".location_geometry_version IS NOT NULL THEN ".$cur_sql_table.".object_id
				WHEN ".$new_sql_table.".id IS NULL THEN NULL
			";
			$column_geometry_type_id .= "
				WHEN ".$cur_sql_table.".location_geometry_version IS NOT NULL THEN ".$cur_sql_table_details.".type_id
				WHEN ".$new_sql_table.".id IS NULL THEN NULL
			";
			$column_geometry_object_sub_id .= "
				WHEN ".$cur_sql_table.".location_geometry_version IS NOT NULL THEN ".$cur_sql_table.".id
				WHEN ".$new_sql_table.".id IS NULL THEN NULL
			";
			
			$column_ref_show_object_id .= " WHEN ".$new_sql_table."_pre.id IS NOT NULL AND ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN ".$new_sql_table."_pre.object_id";
			$column_ref_show_type_id .= " WHEN ".$new_sql_table."_pre.id IS NOT NULL AND ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN ".$new_sql_table_details.".type_id";
			$column_ref_show_object_sub_details_id .= " WHEN ".$new_sql_table."_pre.id IS NOT NULL AND ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN ".$new_sql_table_details.".id";
			
			$arr['column_path_'.$i.'_object_sub_id'] = $new_sql_table.".id";
			
			if ($i == $count) {

				$arr['column_geometry_object_id'] = $column_geometry_object_id." ELSE ".$new_sql_table."_pre.object_id END";
				$arr['column_geometry_type_id'] = $column_geometry_type_id." ELSE ".$new_sql_table_details.".type_id END";
				$arr['column_geometry_object_sub_id'] = $column_geometry_object_sub_id." ELSE ".$new_sql_table.".id END";
				
				$arr['column_ref_show_object_id'] = $column_ref_show_object_id." ELSE ".$new_sql_table."_pre.object_id END";
				$arr['column_ref_show_type_id'] = $column_ref_show_type_id." ELSE ".$new_sql_table_details.".type_id END";
				$arr['column_ref_show_object_sub_details_id'] = $column_ref_show_object_sub_details_id." ELSE ".$new_sql_table_details.".id END";
			}

			$arr['tables'] .= "
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$cur_sql_table."_loc_use_tos ON (".$cur_sql_table."_loc_use_tos.object_id = ".$cur_sql_table.".object_id AND ".$cur_sql_table."_loc_use_tos.object_sub_details_id = ".$cur_sql_table_details.".location_use_object_sub_details_id AND ".$cur_sql_table."_loc_use_tos.active = TRUE)
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$cur_sql_table."_loc_use_tos_def ON (".$cur_sql_table."_loc_use_tos_def.object_sub_id = ".$cur_sql_table.".id AND ".$cur_sql_table."_loc_use_tos_def.object_sub_description_id = ".$cur_sql_table_details.".location_use_object_sub_description_id AND ".$cur_sql_table."_loc_use_tos_def.active = TRUE)
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$cur_sql_table."_loc_use_to_def ON (".$cur_sql_table."_loc_use_to_def.object_id = ".$cur_sql_table.".object_id AND ".$cur_sql_table."_loc_use_to_def.object_description_id = ".$cur_sql_table_details.".location_use_object_description_id AND ".$cur_sql_table."_loc_use_to_def.active = TRUE)
				
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$cur_sql_table."_loc_use_nodegoat_to_referencing ON (".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_id = 
					CASE
						WHEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id
						WHEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id
						WHEN ".$cur_sql_table."_loc_use_to_def.ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_to_def.ref_object_id
						ELSE ".$cur_sql_table.".location_ref_object_id
					END
					AND ".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_description_id = (
						SELECT nodegoat_to_des.id FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des WHERE nodegoat_to_des.id = ".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_description_id AND nodegoat_to_des.id_id = 'rc_ref_type_id'
					)
				)
				
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$new_sql_table_details." ON (".$new_sql_table_details.".id =
					CASE
						WHEN ".$cur_sql_table_details.".location_ref_object_sub_details_id_locked = TRUE THEN ".$cur_sql_table_details.".location_ref_object_sub_details_id
						ELSE ".$cur_sql_table.".location_ref_object_sub_details_id
					END
				)
								
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$new_sql_table."_pre ON (".$new_sql_table."_pre.object_id =
					CASE
						WHEN ".$cur_sql_table_details.".location_use_object_id = TRUE THEN ".$cur_sql_table.".object_id
						WHEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id
						WHEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id
						WHEN ".$cur_sql_table."_loc_use_to_def.ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_to_def.ref_object_id
						WHEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.ref_object_id
						ELSE ".$cur_sql_table.".location_ref_object_id
					END
					AND ".$new_sql_table."_pre.object_sub_details_id = ".$new_sql_table_details.".id
					AND ".$new_sql_table."_pre.active = TRUE
				)
				
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$new_sql_table."_loc_date_use_tos ON (".$new_sql_table."_loc_date_use_tos.object_id = ".$new_sql_table."_pre.object_id AND ".$new_sql_table."_loc_date_use_tos.object_sub_details_id = ".$new_sql_table_details.".date_use_object_sub_details_id AND ".$new_sql_table."_loc_date_use_tos.active = TRUE)
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$new_sql_table."_loc_date_use_tos_def ON (".$new_sql_table."_loc_date_use_tos_def.object_sub_id = ".$new_sql_table."_pre.id AND ".$new_sql_table."_loc_date_use_tos_def.object_sub_description_id = ".$new_sql_table_details.".date_use_object_sub_description_id AND ".$new_sql_table."_loc_date_use_tos_def.active = TRUE)
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$new_sql_table."_loc_date_use_to_def ON (".$new_sql_table."_loc_date_use_to_def.object_id = ".$new_sql_table."_pre.object_id AND ".$new_sql_table."_loc_date_use_to_def.object_description_id = ".$new_sql_table_details.".date_use_object_description_id AND ".$new_sql_table."_loc_date_use_to_def.active = TRUE)
				
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$new_sql_table." ON (".$new_sql_table.".id = ".$new_sql_table."_pre.id
					".$sql_no_recursion."
					AND ".$new_sql_table.".object_sub_details_id = ".$new_sql_table_details.".id
					AND ".$new_sql_table.".active = TRUE
					AND CASE
						WHEN ".$new_sql_table_details.".is_unique = FALSE THEN ".FilterTypeObjects::format2SQLDateIntMatch($func_sql_select_date($new_sql_table, 'start'), $func_sql_select_date($new_sql_table, 'end'), $func_sql_select_date($cur_sql_table, 'start'), $func_sql_select_date($cur_sql_table, 'end'))."
						ELSE TRUE
					END
				)";
			
			$cur_sql_table = $new_sql_table;
			$cur_sql_table_details = $new_sql_table_details;
		}
		
		return $arr;
	}
	
	// Results
	
	public function format2SQLObject() {
				
		$sql = "SELECT nodegoat_to.id
				FROM ".$this->table_name_objects." AS nodegoat_to
		";
		
		return $sql;
	}
	
	public function format2SQLObjectReferencing($arr_selection = [], $ref_type_id) {
		
		$arr_sql = [];
		$arr_sql_collect = [];
		
		foreach ((array)$arr_selection['object_sub_details'] as $object_sub_details_id => $arr_object_sub_connections) {
												
			foreach ($arr_object_sub_connections as $object_sub_connection_id => $value) {
				
				if ($object_sub_connection_id === 'object_sub_location') {
					
					$is_filtering = $this->isFilteringObjectSubDetails($object_sub_details_id);
					
					if ($is_filtering) {
						
						$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true, true);
						
						$arr_sql_collect[] = "SELECT
							".$arr_sql_location['column_ref_object_id']." AS id
								FROM ".$this->table_name." AS nodegoat_to_storage
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.id = nodegoat_to_storage.object_sub_".$object_sub_details_id."_id AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
									".$arr_sql_location['tables']."
							WHERE ".$arr_sql_location['column_ref_type_id']." = ".(int)$ref_type_id."
								AND ".$arr_sql_location['column_object_sub_details_id']." = ".$object_sub_details_id."
						";
					} else {
					
						$arr_sql['object_sub_details_ids']['location'][] = $object_sub_details_id;
					}
				} else {
					
					$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id];
					
					if (!$arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic']) {
						continue;
					}
					
					$object_sub_description_use_object_description_id = $arr_object_sub_description['object_sub_description_use_object_description_id'];
					
					if ($object_sub_description_use_object_description_id) {
						
						$arr_selection['object_descriptions'][$object_sub_description_use_object_description_id] = $value;
						continue;
					}
					
					$is_filtering = $this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_connection_id);
					
					if ($is_filtering) {
						
						$arr_sql_collect[] = "SELECT
							nodegoat_to_storage.object_sub_definition_".$object_sub_connection_id."_ref_object_id AS id
								FROM ".$this->table_name." AS nodegoat_to_storage
							WHERE nodegoat_to_storage.object_sub_definition_".$object_sub_connection_id."_ref_object_id IS NOT NULL
						";
					} else {
						
						if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
							$arr_sql['object_sub_details_ids']['dynamic'][] = $object_sub_details_id;
							$arr_sql['object_sub_description_ids']['dynamic'][] = $object_sub_connection_id;
						} else {
							$arr_sql['object_sub_details_ids']['model'][] = $object_sub_details_id;
							$arr_sql['object_sub_description_ids']['model'][] = $object_sub_connection_id;
						}
					}
				}
			}
		}
		
		foreach ((array)$arr_selection['object_descriptions'] as $object_description_id => $value) {
			
			$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
			if (!$arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic']) {
				continue;
			}
			
			if ($value['object_sub_details_id']) { // Source is object_sub_description_use_object_description_id
				$is_filtering = $this->isFilteringObjectSubDescription($value['object_sub_details_id'], $value['object_sub_description_id']);
			} else {
				$is_filtering = $this->isFilteringObjectDescription($object_description_id);
			}
			
			if ($is_filtering) {
				
				$arr_sql_collect[] = "SELECT
					nodegoat_to_storage.object_definition_".$object_description_id."_ref_object_id AS id
						FROM ".$this->table_name." AS nodegoat_to_storage
					WHERE nodegoat_to_storage.object_definition_".$object_description_id."_ref_object_id IS NOT NULL
				";
			} else {
				
				if ($arr_object_description['object_description_is_dynamic']) {
					$arr_sql['object_description_ids']['dynamic'][] = $object_description_id;
				} else {
					$arr_sql['object_description_ids']['model'][] = $object_description_id;
				}
			}
		}
		
		if ($arr_sql['object_description_ids']['model']) {
				
			$arr_sql_collect[] = "SELECT
				nodegoat_to_def.ref_object_id AS id
					FROM ".$this->table_name_objects." AS nodegoat_to_storage
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_to_def ON (nodegoat_to_def.object_id = nodegoat_to_storage.id AND nodegoat_to_def.object_description_id IN (".implode(',', $arr_sql['object_description_ids']['model']).") AND ".$this->generateVersion('record', 'nodegoat_to_def').")
			";
		}
		
		if ($arr_sql['object_description_ids']['dynamic']) {
				
			$arr_sql_collect[] = "SELECT
				nodegoat_to_def_ref.ref_object_id AS id
					FROM ".$this->table_name_objects." AS nodegoat_to_storage
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." AS nodegoat_to_def_ref ON (nodegoat_to_def_ref.object_id = nodegoat_to_storage.id AND nodegoat_to_def_ref.object_description_id IN (".implode(',', $arr_sql['object_description_ids']['dynamic']).") AND nodegoat_to_def_ref.ref_type_id = ".(int)$ref_type_id." AND nodegoat_to_def_ref.state = 1)
			";
		}
		
		if ($arr_sql['object_sub_description_ids']['model']) {
			
			$arr_sql_collect[] = "SELECT
				nodegoat_tos_def.ref_object_id AS id
					FROM ".$this->table_name_objects." AS nodegoat_to_storage
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_storage.id AND nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_sql['object_sub_details_ids']['model']).") AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_tos_def ON (nodegoat_tos_def.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def.object_sub_description_id IN (".implode(',', $arr_sql['object_sub_description_ids']['model']).") AND ".$this->generateVersion('record', 'nodegoat_tos_def').")
			";
		}
		
		if ($arr_sql['object_sub_description_ids']['dynamic']) {
				
			$arr_sql_collect[] = "SELECT
				nodegoat_tos_def_ref.ref_object_id AS id
					FROM ".$this->table_name_objects." AS nodegoat_to_storage
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_storage.id AND nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_sql['object_sub_details_ids']['dynamic']).") AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." AS nodegoat_tos_def_ref ON (nodegoat_tos_def_ref.object_sub_id = nodegoat_tos.id AND nodegoat_tos_def_ref.object_sub_description_id IN (".implode(',', $arr_sql['object_sub_description_ids']['dynamic']).") AND nodegoat_tos_def_ref.ref_type_id = ".(int)$ref_type_id." AND nodegoat_tos_def_ref.state = 1)
			";
		}
		
		if ($arr_sql['object_sub_details_ids']['location']) {
			
			$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', false, true);
			
			$arr_sql_collect[] = "SELECT
				".$arr_sql_location['column_ref_object_id']." AS id
					FROM ".$this->table_name_objects." AS nodegoat_to_storage
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_id = nodegoat_to_storage.id AND nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_sql['object_sub_details_ids']['location']).") AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
						".$arr_sql_location['tables']."
					WHERE ".$arr_sql_location['column_ref_object_id']." IS NOT NULL
			";
		}
						
		$sql = "SELECT DISTINCT id
			FROM (
				(".implode(") UNION ALL (", $arr_sql_collect).")
			) AS foo
		";
		
		return $sql;
	}
	
	public function format2SQLObjectReferenced($arr_selection = [], $table_name) {
		
		$arr_sql = [];

		foreach ((array)$arr_selection['object_sub_details'] as $object_sub_details_id => $arr_object_sub_connections) {
												
			foreach ($arr_object_sub_connections as $object_sub_connection_id => $value) {
				
				if ($object_sub_connection_id === 'object_sub_location') {
					
					$arr_sql['object_sub_details_ids']['location'][] = $object_sub_details_id;
				} else {
									
					$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_connection_id];
									
					if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
						
						$arr_selection['object_descriptions'][$arr_object_sub_description['object_sub_description_use_object_description_id']] = $value;
						continue;
					}
					
					if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
						$arr_sql['object_sub_details_ids']['dynamic'][] = $object_sub_details_id;
						$arr_sql['object_sub_description_ids']['dynamic'][] = $object_sub_connection_id;
					} else {
						$arr_sql['object_sub_details_ids']['model'][] = $object_sub_details_id;
						$arr_sql['object_sub_description_ids']['model'][] = $object_sub_connection_id;
					}
				}
			}
		}
		
		foreach ((array)$arr_selection['object_descriptions'] as $object_description_id => $value) {
			
			$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
			
			if ($arr_object_description['object_description_is_dynamic']) {
				$arr_sql['object_description_ids']['dynamic'][] = $object_description_id;
			} else {
				$arr_sql['object_description_ids']['model'][] = $object_description_id;
			}
		}
		
		$arr_sql_collect = [];
		
		if ($arr_sql['object_description_ids']['model']) {
			
			$arr_sql_collect[] = "SELECT nodegoat_to_def.object_id AS id
							FROM ".$table_name." AS nodegoat_to_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_to_def ON (nodegoat_to_def.ref_object_id = nodegoat_to_ref.id AND nodegoat_to_def.object_description_id IN (".implode(',', $arr_sql['object_description_ids']['model']).") AND ".$this->generateVersion('record', 'nodegoat_to_def').")
			";
		}
		
		if ($arr_sql['object_description_ids']['dynamic']) {
				
			$arr_sql_collect[] = "SELECT nodegoat_to_def_ref.object_id AS id
							FROM ".$table_name." AS nodegoat_to_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." AS nodegoat_to_def_ref ON (nodegoat_to_def_ref.ref_object_id = nodegoat_to_ref.id AND nodegoat_to_def_ref.object_description_id IN (".implode(',', $arr_sql['object_description_ids']['dynamic']).") AND nodegoat_to_def_ref.state = 1)
			";
		}
		
		if ($arr_sql['object_sub_description_ids']['model']) {
			
			$arr_sql_collect[] = "SELECT nodegoat_tos.object_id AS id
							FROM ".$table_name." AS nodegoat_to_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_tos_def ON (nodegoat_tos_def.ref_object_id = nodegoat_to_ref.id AND nodegoat_tos_def.object_sub_description_id IN (".implode(',', $arr_sql['object_sub_description_ids']['model']).") AND ".$this->generateVersion('record', 'nodegoat_tos_def').")
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def.object_sub_id AND nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_sql['object_sub_details_ids']['model']).") AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
			";
		}
		
		if ($arr_sql['object_sub_description_ids']['dynamic']) {

			$arr_sql_collect[] = "SELECT nodegoat_tos.object_id AS id
							FROM ".$table_name." AS nodegoat_to_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." AS nodegoat_tos_def_ref ON (nodegoat_tos_def_ref.ref_object_id = nodegoat_to_ref.id AND nodegoat_tos_def_ref.object_sub_description_id IN (".implode(',', $arr_sql['object_sub_description_ids']['dynamic']).") AND nodegoat_tos_def_ref.state = 1)
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_def_ref.object_sub_id AND nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_sql['object_sub_details_ids']['dynamic']).") AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
			";
		}
		
		if ($arr_sql['object_sub_details_ids']['location']) {
			
			$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true, true);
			
			$arr_sql_collect[] = "SELECT nodegoat_tos.object_id AS id
							FROM ".$table_name." AS nodegoat_to_ref
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos ON (nodegoat_tos.object_sub_details_id IN (".implode(',', $arr_sql['object_sub_details_ids']['location']).") AND ".$this->generateVersion('object_sub', 'nodegoat_tos').")
								".$arr_sql_location['tables']."
							WHERE ".$arr_sql_location['column_ref_object_id']." = nodegoat_to_ref.id
			";
		}
		
		$sql = "SELECT DISTINCT id
			FROM (
				(".implode(") UNION ALL (", $arr_sql_collect).")
			) AS foo
		";
		
		return $sql;
	}
	
	// Direct calls
	
	public static function encodeTypeObjectId($type_id, $object_id) {
		
		$context_id = $type_id;
		$object_id = (int)$object_id;
		
		$len_o = strlen($object_id);
		$operator = ($object_id < 10 ? '0' : '').substr($object_id, -2); // Operator requires at least two positions: add 10
		
		$mask = (int)str_pad('', $len_o, $operator, STR_PAD_RIGHT);
		$masked_id = $object_id ^ $mask; // Xor

		$len_c = strlen($context_id);
		$len_m = strlen($masked_id);
		$len = ($len_c > $len_m ? $len_c : $len_m);
		$count_c = 0;
		$count_m = 0;
		$count = 0;
		
		$str = '';
		
		while ($count < $len) {
			
			if ($count_c === $len_c) {
				$char = '/';
				$count_c = 0;
			} else {
				$char = substr($context_id, $count_c, 1);
				$count_c++;
			}
			$str .= $char;
			
			if ($count_m === $len_m) {
				$char = '\\';
				$count_m = 0;
			} else {
				$char = substr($masked_id, $count_m, 1);
				$count_m++;
			}
			$str .= $char;
			
			$count++;
		}
		
		$encoded = trim(base64_encode($str), '=');

		$encoded = strShift($encoded, (int)$operator); // Shift string with operator
		
		// Split the operator to overall 3rd and 5th position in identifier, put the object ID's size at 6th position, and append 'ngx'
		$encoded = substr_replace($encoded, substr($operator, 0, 1), 2, 0);
		$encoded = substr_replace($encoded, substr($operator, 1, 1).$len_o, 4, 0);
		$encoded = 'ngx'.$encoded;
		
		return $encoded;
	}
	
	public static function decodeTypeObjectId($str) {
		
		if (substr($str, 0, 3) !== 'ngx') {
			return false;
		}
		
		$str = substr($str, 3); // Remove ngx
		
		$len_o = (int)substr($str, 5, 1); // Retreive object ID's size
		$operator = substr($str, 2, 1).substr($str, 4, 1); // Retrieve operator
		
		$str = substr_replace($str, '', 4, 2); // Remove operator and object ID's size
		$str = substr_replace($str, '', 2, 1); // Remove operator
		
		$decoded = strShift($str, -(int)$operator); // Unshift string with operator
		
		$decoded = base64_decode($decoded);
		
		$count = 0;
		$len = strlen($decoded);
		
		$context_id = '';
				
		while ($count < $len) {
			
			$char = substr($decoded, $count, 1);
			
			if ($char === '/') {
				break;
			}
			
			$context_id .= $char;
			
			$count += 2;
		}
		
		$pos = strpos($context_id, '_');
		$type_id = (int)substr($context_id, 0, $pos);
		$domain_id = substr($context_id, $pos + 1);
		
		$count = 0;
		
		$masked_id = '';
		
		while ($count < $len) {
			
			$char = substr($decoded, $count + 1, 1);
			
			if ($char === '\\') {
				break;
			}
			
			$masked_id .= $char;
			
			$count += 2;
		}
		
		$mask = (int)str_pad('', $len_o, $operator, STR_PAD_RIGHT);
		$object_id = ((int)$masked_id ^ $mask); // Xor
		
		if (!$type_id || !$object_id) {
			return false;
		}
				
		return ['type_id' => $type_id, 'object_id' => $object_id];
	}
				
	public static function getTypeObjectNames($type_id, $arr_objects, $conditions = true) {
				
		$filter = new FilterTypeObjects($type_id, 'name');
		$filter->setVersioning('added');
		$filter->setFilter(['objects' => $arr_objects]);
		if ($conditions) {
			$filter->setConditions($conditions, toolbar::getTypeConditions($type_id));
		}
		
		$arr = [];
		
		foreach ($filter->init() as $object_id => $value) {
			$arr[$object_id] = $value['object']['object_name'];
		}
		
		return $arr;
	}
}
