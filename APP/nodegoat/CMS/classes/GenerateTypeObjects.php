<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class GenerateTypeObjects {
	
	const VIEW_ALL = 1; // Selects everything by default, and processes data
	const VIEW_ID = 2; // Only Object IDs, fast
	const VIEW_NAME = 3; // Only Object names
	const VIEW_SET = 4; // Selects everything by default, does not process data, for internal use
	const VIEW_STORAGE = 5; // Unprocessed data that includes only storage-related data (i.e. the stored values)
	const VIEW_OVERVIEW = 6;
	const VIEW_VISUALISE = 7;
	
	const CONDITIONS_MODE_TEXT = 1;
	const CONDITIONS_MODE_STYLE = 2;
	const CONDITIONS_MODE_STYLE_INCLUDE = 3;
	const CONDITIONS_MODE_FULL = 4;
	
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
	
	protected $query_object_names = [];
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
	protected $arr_sql_limit_object_subs = [];
	protected $arr_selection = [];
	protected $arr_selection_object_sub_details_ids = [];
	protected $arr_filtering = [];
	protected $arr_filtering_enable = [];
	protected $arr_filtering_filters = [];
	protected $table_name_filtering = false;
	protected $versioning = false;
	protected $conditions = false;
	protected $differentiation_id = false;
	protected $mode_format = null;
	
	protected $arr_sql_order = [];
	protected $arr_sql_group = [];
	protected $arr_sql_order_subs = [];
	protected $arr_sql_filter_purpose = [];
	protected $arr_sql_filter_purpose_object = [];
	protected $arr_sql_filter_purpose_general = [];
	protected $arr_sql_filter_purpose_subs = [];
	protected $arr_sql_filter = [];
	protected $arr_sql_filter_subs = [];
	protected $arr_sql_query_object_subs_details = [];
	protected $sql_limit = false;
	protected $arr_sql_pre_queries = [];
	protected $arr_sql_pre_settings = [];
	protected $arr_sql_conditions = [];
	
	protected $arr_scope = ['users' => [], 'types' => [], 'project_id' => false];
	protected $str_identifier_scope = false;
	protected $arr_types_found = [];
	
	protected $sql_tables_columns_generated = false;
	protected $settings_used = false;
	
	protected $arr_type_object_name_object_description_ids = [];
	protected $arr_type_object_name_object_sub_description_ids = [];
	protected $arr_type_object_name_object_sub_details_ids = [];
	protected $arr_type_object_names = [];
	
	public static $keep_tables = false;
	
	protected static $arr_pre_run = [];
	
	protected static $table_name_shared_type_object_names = '';
	protected static $func_shared_type_set_conditions_name = false;
	protected static $arr_shared_type_set_conditions_name = [];
	protected static $arr_shared_type_object_names = [];
	protected static $do_check_shared_type_object_names = false;
	protected static $is_active_shared_type_object_names = false;
	protected static $num_shared_type_object_name_depth = 5;
	protected static $num_shared_type_object_names_cache = 10000;
	protected static $do_clear_shared_type_object_names = true;
	protected static $identifier_cached_object_subs = false;
	protected static $is_cached_object_subs = false;
	
	const REFERENCED_ID_MODIFIER = 'is0referenced';
	const SQL_GROUP_SEPERATOR = '$|$';
	const SQL_GROUP_SEPERATOR_2 = '$||$';
	const SQL_ANALYSIS_SEPERATOR = ' | ';
		
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
		
		if (strpos($str, '[![') === false) {
			return $str;
		}

		$str = preg_replace_callback('/\[\!\[(\d+)_(\d+)\]\!\]/', function($arr_match) use ($encode) {
			
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
			
			if ($type_id == 0) { // Captured an empty type ID
				continue;
			}
			
			if (self::$func_shared_type_set_conditions_name && !isset(self::$arr_shared_type_set_conditions_name[$type_id])) {
				
				$function = self::$func_shared_type_set_conditions_name;
				self::$arr_shared_type_set_conditions_name[$type_id] = $function($type_id);
			}
						
			$arr_table = ['table_name' => self::$table_name_shared_type_object_names, 'table_alias' => 'nodegoat_share_to_names', 'query' => "nodegoat_share_to_names.type_id = nodegoat_to.type_id AND nodegoat_share_to_names.state = 1"];
			
			$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_NAME);
			$filter->setVersioning('added');
			if (self::$func_shared_type_set_conditions_name) {
				$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE, self::$arr_shared_type_set_conditions_name[$type_id]); // Use 'style_include' since the style will be stripped by the requesting object when applicable
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
			DROP INDEX ".(DB::ENGINE_IS_POSTGRESQL ? "state_index_nodegoat_share_to_names" : "state_index_nodegoat_share_to_names ON ".self::$table_name_shared_type_object_names).";
		");
		
		self::$is_active_shared_type_object_names = false;
	}
	
	public static function setConditionsResource($function) {
		
		self::$func_shared_type_set_conditions_name = $function;
	}

	public function __construct($type_id, $view = GenerateTypeObjects::VIEW_NAME, $is_user = false, $arr_type_set = false) {

		// $view = "id/name/overview/visualise/all/set/storage"

		$this->type_id = (int)$type_id;
		$this->view = $view;
		$this->is_user = $is_user;
		
		$this->setConditions(($this->view == GenerateTypeObjects::VIEW_ID || $this->view == GenerateTypeObjects::VIEW_STORAGE ? false : true));
		
		if ($arr_type_set) {
			$this->arr_type_set = $arr_type_set;
		} else {
			$this->arr_type_set = StoreType::getTypeSet($this->type_id);
		}
		
		$this->arr_type_set_object_sub_details_ids = $this->arr_type_set['object_sub_details'];
		
		if (isset($this->arr_type_set['type']['include_referenced']['object_sub_details'])) {
			
			foreach ($this->arr_type_set['type']['include_referenced']['object_sub_details'] as $cur_object_sub_details_id => $arr_object_sub_description_ids) {
										
				unset($this->arr_type_set_object_sub_details_ids[$cur_object_sub_details_id]);
				
				$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$cur_object_sub_details_id]['object_sub_details']['object_sub_details_id'];
				$this->arr_type_set_object_sub_details_ids[$use_object_sub_details_id] = $use_object_sub_details_id;
			}
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
			
			if ($this->view == GenerateTypeObjects::VIEW_ID) {
				
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
				
		$this->storeResultTemporarily();

		if ($this->view == GenerateTypeObjects::VIEW_VISUALISE || ($this->view == GenerateTypeObjects::VIEW_OVERVIEW && !$this->arr_limit)) {
			
			$arr_result = $this->getResultInfo();
			
			$arr_nodegoat_details = cms_nodegoat_details::getDetails();
			
			if ($arr_result['total_filtered'] > $arr_nodegoat_details['limit_view']) {
				error(getLabel('msg_data_range_too_wide'));
			}
		}
	}
	
	private function generateObjects() {
	
		// Prepare Objects
		
		$sql_group = 'GROUP BY nodegoat_to.id, nodegoat_to.version';

		if ($this->arr_sql_group) {
			
			$sql_group .= ','.implode(',', $this->arr_sql_group);
		}
		
		$sql_order = '';
		
		if ($this->arr_sql_order) {
			
			$sql_order = 'ORDER BY nodegoat_to_store.auto_id';
			$sql_group .= ', nodegoat_to_store.auto_id';
		}
		
		$sql_tables = ($this->arr_sql_tables ? implode(' ', $this->arr_sql_tables) : '');

		$version_select = $this->generateVersion('object', 'nodegoat_to');
		
		$sql_select_object_conditions = false;
		
		if ($this->arr_columns_object_conditions && arrHasValuesRecursive('condition_group', 'object', $this->arr_type_set_conditions)) {
			
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
		
		// Prepare Sub-objects
		
		if ($this->arr_selection['object_sub_details']) {
			
			$table_name_nodegoat_tos = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.nodegoat_tos_temp');
			$contains_referenced = isset($this->arr_type_set['type']['include_referenced']['object_sub_details']);
			
			DB::queryMulti("
				DROP ".(self::$keep_tables ? '' : (DB::ENGINE_IS_MYSQL ? 'TEMPORARY' : ''))." TABLE IF EXISTS ".$table_name_nodegoat_tos.";
				
				CREATE ".(self::$keep_tables ? '' : 'TEMPORARY')." TABLE ".$table_name_nodegoat_tos." (
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
						
						$value = (strtoupper($value) == 'ASC' ? 'ASC' : 'DESC');
						
						$this->arr_sql_order_subs[$object_sub_details_id][] = $this->arr_columns_subs[$object_sub_details_id][$key]." ".$value;
					}
				}

				$is_filtering = $this->isFilteringObjectSubDetails($object_sub_details_id, true);
				$is_referenced = ($contains_referenced && isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]));
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
				
				if ($this->arr_columns_object_conditions && !empty($this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id])) {
						
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
		}
		
		if ($this->view == static::VIEW_VISUALISE) {
				
			$count_objects = $res_objects->getRowCount();
			
			$count_object_subs = 0;
			
			if ($this->arr_selection['object_sub_details']) {
				
				foreach ($arr_res_objects_subs as $res) {
					$count_object_subs += $res->getRowCount();
				}
			}
			
			Labels::setVariable('count_objects', num2String($count_objects));
			Labels::setVariable('count_object_subs', num2String($count_object_subs));
			Labels::setVariable('type', Labels::parseTextVariables($this->arr_type_set['type']['name']));
			status(getLabel('msg_data_range_objects_found', 'L', true));
			
			$arr_nodegoat_details = cms_nodegoat_details::getDetails();
			
			if ($count_object_subs > $arr_nodegoat_details['limit_view']) {
				error(getLabel('msg_data_range_too_wide'));
			}
		}
		
		// Generate

		$arr = [];
		
		$func_parse_references = function($type, $value, $object_description_id) {
			
			$arr = [];
			
			if (!$value) {
				return $arr;
			}
			
			foreach (array_filter(explode(static::SQL_GROUP_SEPERATOR, $value)) as $arr_ref) {
				
				$arr_ref = explode(static::SQL_GROUP_SEPERATOR_2, $arr_ref);
				
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
			
			if (!$value) {
				return $arr;
			}
			
			foreach (array_filter(explode(static::SQL_GROUP_SEPERATOR, $value)) as $arr_source) {
				
				$arr_source = explode(static::SQL_GROUP_SEPERATOR_2, $arr_source);
				
				$type_id = (int)$arr_source[1];
				$ref_object_id = (int)$arr_source[0];
				
				$arr[$type_id][] = [$type.'_source_ref_object_id' => $ref_object_id, $type.'_source_link' => $arr_source[2]];
				
				$this->addTypeFound('sources', $type_id);
			}
			
			return $arr;
		};
		
		$func_parse_filters = function($value) {
			
			$arr = [];
			
			foreach (array_filter(explode(static::SQL_GROUP_SEPERATOR, $value)) as $arr_filter) {
				
				$arr_filter = explode(static::SQL_GROUP_SEPERATOR_2, $arr_filter);
				
				$ref_type_id = (int)$arr_filter[0];
				
				$arr[$ref_type_id] = ['object_filter_ref_type_id' => $ref_type_id, 'object_filter_object' => ($arr_filter[1] ? json_decode($arr_filter[1], true) : []), 'object_filter_scope_object' => ($arr_filter[2] ? json_decode($arr_filter[2], true) : [])];
			}
			
			return $arr;
		};
				
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
			if ($this->view == static::VIEW_OVERVIEW) {
				$s_arr['object_changes'] = $arr_row[$cur_row+0];
				$s_arr['object_sub_changes'] = $arr_row[$cur_row+1];
				$cur_row = $cur_row+2;
			}
			if ($has_analysis) {
				$s_arr['object_analysis'] = $arr_row[$cur_row+0];
				$cur_row = $cur_row+1;
			}
						
			$arr[$cur_object_id]['object_definitions'] = $arr_object_definitions_placeholder;
			
			if (!isset($arr[$cur_object_id]['object_subs'])) {
				$arr[$cur_object_id]['object_subs'] = [];
			}
			
			if ($this->conditions) {
					
				$this->resetObjectConditions();
				
				$arr_condition_identifiers = []; // Store the identified conditions with the objects
				
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
							
							$condition_identifier = $arr_condition_setting['condition_identifier'];
							
							$arr_condition_identifiers[$condition_identifier] = $condition_identifier;
							
							if (isset($arr_condition_setting['condition_actions']['weight']) && ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'])) {
								
								$column_index = $column_index_object_conditions_values + $arr_condition_setting['condition_value_column_index'];
								
								$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (float)$arr_row[$column_index];
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
								
									$condition_identifier = $arr_condition_setting['condition_identifier'];
									
									$arr_condition_identifiers[$condition_identifier] = $condition_identifier;
									
									if (isset($arr_condition_setting['condition_actions']['weight']) && ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'])) {
								
										$column_index = $column_index_object_conditions_values + $arr_condition_setting['condition_value_column_index'];
										
										$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (float)$arr_row[$column_index];
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
				
				if (isset($s_arr['object_name'])) {
					
					$s_arr['object_name'] = Labels::parseContainers($s_arr['object_name']);
					
					$this->applyObjectNameConditions($s_arr);
					
					if ($this->conditions == static::CONDITIONS_MODE_TEXT && $s_arr['object_name']) {
						$s_arr['object_name'] = Response::addParsePost($s_arr['object_name'], ['strip' => true]);
					}
				}
				
				$this->applyObjectConditions($arr[$cur_object_id]);

				if ($this->conditions == static::CONDITIONS_MODE_FULL && $arr_condition_identifiers && $s_arr['object_style'] !== 'hide') {
					$s_arr['object_style']['conditions'] = $arr_condition_identifiers;
				}
			} else {
				
				if (isset($s_arr['object_name'])) {
					$s_arr['object_name'] = Labels::parseContainers($s_arr['object_name']);
				}
			}
			
			if (isset($s_arr['object_name']) && !$s_arr['object_name']) {
				
				if ($this->conditions == static::CONDITIONS_MODE_STYLE_INCLUDE) {
					$s_arr['object_name'] = '<span style="font-style: italic; opacity: 0.8;">'.getLabel('lbl_no_name').'</span>';
				} else if ($this->conditions == static::CONDITIONS_MODE_STYLE) {
					$s_arr['object_name'] = getLabel('lbl_no_name');
					$s_arr['object_name_style'] = 'font-style: italic; opacity: 0.8;';
				} else if ($this->conditions == static::CONDITIONS_MODE_FULL) {
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
				$use_object_description_id = $arr_object_description['object_description_id']; // In case the object description is referenced

				$arr_sql_object_descriptions[] = "SELECT nodegoat_to.id AS object_id,
							".($arr_columns['select_id'] ? $arr_columns['select_id'] : "NULL")." AS select_id,
							".($arr_columns['select_value'] ? $arr_columns['select_value'] : "NULL")." AS select_value,
							".($arr_columns['sources'] ? DBFunctions::sqlImplode("CONCAT(nodegoat_to_def_src.ref_object_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_to_def_src.ref_type_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_to_def_src.value)", static::SQL_GROUP_SEPERATOR) : "NULL")." AS sources
						FROM ".$table_name_store." AS nodegoat_to
							".$arr_columns['tables']."
							".($arr_columns['sources'] ? "LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')." nodegoat_to_def_src ON (nodegoat_to_def_src.object_description_id = ".$use_object_description_id." AND nodegoat_to_def_src.object_id = nodegoat_to.id)" : "")."
					GROUP BY nodegoat_to.id".($arr_columns['group'] ? ", ".$arr_columns['group'] : "")."
					ORDER BY nodegoat_to.id".($arr_columns['order'] ? ", ".$arr_columns['order'] : "")."
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
				
				$cur_object_id = (int)($arr_row[0] ?? 0);
				
				if ($cur_object_id === 0) {
					continue;
				}
				
				$s_arr =& $arr[$cur_object_id]['object_definitions'][$object_description_id];
				
				while ($arr_row) {

					$select_id = $arr_row[1];
					$select_value = $arr_row[2];
					
					if ($this->conditions == static::CONDITIONS_MODE_TEXT && $select_value && $is_ref_type_id) {
						$select_value = Response::addParsePost($select_value, ['strip' => true]);
					}
					if (isset($s_arr['processing'])) {
						$select_value = $s_arr['processing'][0].$select_value.$s_arr['processing'][1];
					}
																
					if ($is_dynamic) {
						$s_arr['object_definition_ref_object_id'] = $func_parse_references('object_definition', $select_id, $object_description_id);
						$s_arr['object_definition_value'] = $select_value;
					} else if ($has_multi) {
						if ($select_id || $select_value != '') {
							$s_arr['object_definition_ref_object_id'][] = ($select_id === null ? null : (int)$select_id);
							$s_arr['object_definition_value'][] = $select_value;
						}
					} else {
						if ($select_id || $select_value != '') {
							$s_arr['object_definition_ref_object_id'] = ($select_id === null ? null : (int)$select_id);
							$s_arr['object_definition_value'] = $select_value;
						}
					}
					
					if (isset($arr_row[3])) {
						$s_arr['object_definition_sources'] = $func_parse_sources('object_definition', $arr_row[3]);
					}
										
					$arr_row = $res->fetchRow();
					
					$object_id = (int)($arr_row[0] ?? 0);
					
					if ($object_id !== $cur_object_id) {
					
						if (!$this->arr_column_purpose[$object_description_id]['view']) {
							unset($arr[$cur_object_id]['object_definitions'][$object_description_id]);
						} else if (isset($s_arr['processing'])) {
							unset($s_arr['processing']);
						}
						
						$cur_object_id = $object_id;
						
						if ($cur_object_id === 0) {
							break;
						}
						
						$s_arr =& $arr[$cur_object_id]['object_definitions'][$object_description_id];
					}
				}
				
				$res->freeResult();
			}
			
			unset($s_arr);
		}
		
		// Sub-objects
		
		if ($this->arr_selection['object_sub_details']) {
			
			foreach ($arr_res_objects_subs as $key => $res) {
				
				$object_sub_details_id = $arr_object_sub_details_ids[$key];
				$arr_object_sub_details = $this->arr_type_set['object_sub_details'][$object_sub_details_id];
				$arr_conditions_object_sub_details = ($this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id] ?? null);
				
				$arr_object_sub_description_ids = (isset($this->arr_columns_subs_descriptions[$object_sub_details_id]) ? array_keys($this->arr_columns_subs_descriptions[$object_sub_details_id]) : []);
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
				
				$has_referenced = (isset($this->arr_type_set['type']['include_referenced']['object_sub_details']) && ($object_sub_details_id == 'all' || isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])));
				
				while ($arr_row = $res->fetchRow()) {
					
					$cur_object_id = $arr_row[0];
					$cur_row = 1;
					
					$cur_object_sub_id = (int)$arr_row[$cur_row+0];
					$cur_object_sub_details_id = (int)$arr_row[$cur_row+1];
					
					$has_chronology_resolve = ($arr_row[$cur_row+2] !== null);
					$has_location_geometry_resolve = ($arr_row[$cur_row+8] !== null);
					$has_location_reference = ($arr_row[$cur_row+9] !== null);
					
					$object_sub_location_ref_object_name = null;
					
					if ($has_location_reference) {
						
						$location_object_id = ($this->view !== GenerateTypeObjects::VIEW_VISUALISE ? $arr_row[$cur_row+9] : $arr_row[$cur_row+7]);
						$location_type_id = ($this->view !== GenerateTypeObjects::VIEW_VISUALISE ? $arr_row[$cur_row+10] : $arr_row[$cur_row+8]);
						$object_sub_location_ref_object_name = ($location_object_id && $this->view !== GenerateTypeObjects::VIEW_STORAGE ? '[!['.$location_type_id.'_'.$location_object_id.']!]' : '');
						
						$this->addTypeFound('locations', $arr_row[$cur_row+10]);
					}
					
					$object_sub_location_type = ($arr_row[$cur_row+9] || (!$arr_row[$cur_row+6] && $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']) ? 'reference' : 'geometry');
					
					$s_arr =& $arr[$cur_object_id]['object_subs'][$cur_object_sub_id];

					$s_arr['object_sub'] = [
						'object_sub_id' => $cur_object_sub_id,
						'object_sub_details_id' => $cur_object_sub_details_id,
						'object_sub_date_start' => ($has_chronology_resolve ? (int)$arr_row[$cur_row+2] : null),
						'object_sub_date_end' => ($has_chronology_resolve ? (int)$arr_row[$cur_row+3] : null),
						'object_sub_date_all' => ($has_chronology_resolve && $arr_row[$cur_row+4] !== null ? explode(':', $arr_row[$cur_row+4]) : null),
						'object_sub_date_chronology' => ($arr_row[$cur_row+5] !== null ? (string)$arr_row[$cur_row+5] : null),
						'object_sub_location_geometry' => ($arr_row[$cur_row+6] !== null ? (string)$arr_row[$cur_row+6] : null),
						'object_sub_location_geometry_ref_object_id' => ($has_location_geometry_resolve ? (int)$arr_row[$cur_row+7] : null),
						'object_sub_location_geometry_ref_type_id' => ($has_location_geometry_resolve ? (int)$arr_row[$cur_row+8] : null),
						'object_sub_location_ref_object_id' => ($has_location_reference ? (int)$arr_row[$cur_row+9] : null),
						'object_sub_location_ref_type_id' => ($has_location_reference ? (int)$arr_row[$cur_row+10] : null),
						'object_sub_location_ref_object_sub_details_id' => ($has_location_reference ? (int)$arr_row[$cur_row+11] : null),
						'object_sub_location_ref_object_name' => $object_sub_location_ref_object_name,
						'object_sub_location_type' => $object_sub_location_type,
						'object_sub_sources' => $func_parse_sources('object_sub', $arr_row[$cur_row+12]),
						'object_sub_version' => $arr_row[$cur_row+13],
						'object_sub_style' => []
					];
					
					$cur_row = $cur_row+14;

					if ($this->conditions == static::CONDITIONS_MODE_TEXT) {
						
						$s_object_sub_location_ref_object_name = $s_arr['object_sub']['object_sub_location_ref_object_name'];
						
						if ($s_object_sub_location_ref_object_name) {
							$s_arr['object_sub']['object_sub_location_ref_object_name'] = Response::addParsePost($s_object_sub_location_ref_object_name, ['strip' => true]);
						}
					}
					
					if ($has_referenced) {
						$s_arr['object_sub']['object_sub_object_id'] = (int)$arr_row[$cur_row+0];
						if ($arr_row[$cur_row+1]) {
							$s_arr['object_sub']['object_sub_details_id'] = $cur_object_sub_details_id.static::REFERENCED_ID_MODIFIER.$arr_row[$cur_row+1];
						}
						$cur_row = $cur_row+2;
					}
					
					$s_arr['object_sub_definitions'] = $arr_object_sub_definitions_placeholder;
					
					if ($arr_conditions_object_sub_details) {
						
						$this->resetObjectConditions();
						
						$arr_condition_identifiers = []; // Store the identified conditions with the sub objects
						
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
									
									$condition_identifier = $arr_condition_setting['condition_identifier'];
									
									$arr_condition_identifiers[$condition_identifier] = $condition_identifier;
									
									if (isset($arr_condition_setting['condition_actions']['weight']) && ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'])) {
								
										$column_index = $column_index_object_conditions_subs_values + $arr_condition_setting['condition_value_column_index'];
										
										$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (float)$arr_row[$column_index];
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
										
											$condition_identifier = $arr_condition_setting['condition_identifier'];
											
											$arr_condition_identifiers[$condition_identifier] = $condition_identifier;
											
											if (isset($arr_condition_setting['condition_actions']['weight']) && ($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'] || $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'])) {
										
												$column_index = $column_index_object_conditions_subs_values + $arr_condition_setting['condition_value_column_index'];
												
												$arr_condition_setting['condition_actions']['weight']['object_description_value'] = (float)$arr_row[$column_index];
											}
										}
										
										$this->setObjectConditions($object_sub_description_id, $arr_condition_setting['condition_actions']);
									}
								}
							}
						}
						
						$this->applyObjectConditions($s_arr);
						
						if ($this->conditions == static::CONDITIONS_MODE_FULL && $arr_condition_identifiers && $s_arr['object_sub']['object_sub_style'] !== 'hide') {
							$s_arr['object_sub']['object_sub_style']['conditions'] = $arr_condition_identifiers;
						}
					}
				}
				
				unset($s_arr);
				$res->freeResult();
			}
			
			// Sub-object definitions
		
			if ($this->arr_columns_subs_descriptions) {

				$arr_sql_object_subs_descriptions = [];
				$version_select = $this->generateVersion('record', 'nodegoat_tos_def');
				$version_select_object_definition = $this->generateVersion('record', 'nodegoat_to_def');
				$arr_object_sub_description_ids = [];
				
				foreach ($this->arr_columns_subs_descriptions as $object_sub_details_id => $arr_columns_sub_descriptions) {
					
					$is_filtering = $this->isFilteringObjectSubDetails($object_sub_details_id, true);
					$has_referenced = isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]);
					$arr_object_sub_details = $this->arr_type_set['object_sub_details'][$object_sub_details_id];
					$use_object_sub_details_id = $arr_object_sub_details['object_sub_details']['object_sub_details_id']; // In case the sub-object is referenced;
					
					$table_name = "nodegoat_tos_".$object_sub_details_id;
					$version_select_sub = $this->generateVersion('object_sub', $table_name);
					$column_name_store = ($is_filtering ? "nodegoat_to_store.object_sub_".$object_sub_details_id."_id" : "nodegoat_tos_store.id");
					$column_name_object = ($has_referenced ? "nodegoat_tos_store.object_id" : $table_name.".object_id");
					
					foreach ($arr_columns_sub_descriptions as $object_sub_description_id => $arr_columns) {
						
						$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];

						$arr_object_sub_description_ids[] = [$object_sub_details_id, $object_sub_description_id];
						
						$arr_sql_object_subs_descriptions[] = "SELECT ".$column_name_object.",
									".$table_name.".id AS object_sub_id,
									".($arr_columns['select_id'] ? $arr_columns['select_id'] : "NULL")." AS select_id,
									".($arr_columns['select_value'] ? $arr_columns['select_value'] : "NULL")." AS select_value,
									".($arr_columns['sources'] ? DBFunctions::sqlImplode("CONCAT(nodegoat_tos_def_src.ref_object_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_tos_def_src.ref_type_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_tos_def_src.value)", static::SQL_GROUP_SEPERATOR) : "NULL")." AS sources
								FROM ".($is_filtering ? $this->table_name." AS nodegoat_to_store" : $table_name_nodegoat_tos." AS nodegoat_tos_store")."
									JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".object_sub_details_id = ".$use_object_sub_details_id." AND ".$table_name.".id = ".$column_name_store." AND ".$version_select_sub.")
									".$arr_columns['tables']."
									".($arr_columns['sources'] ? "LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_SOURCES')." nodegoat_tos_def_src ON (nodegoat_tos_def_src.object_sub_description_id = ".$object_sub_description_id." AND nodegoat_tos_def_src.object_sub_id = ".$table_name.".id)" : "")."
							GROUP BY ".$column_name_object.", ".$table_name.".id, ".$table_name.".version ".($arr_columns['group'] ? ", ".$arr_columns['group'] : "")."
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
					
					$cur_object_sub_id = (int)($arr_row[1] ?? 0);
					
					if ($cur_object_sub_id == 0) {
						continue;
					}
					
					$cur_object_id = (int)$arr_row[0];
					
					$s_arr =& $arr[$cur_object_id]['object_subs'][$cur_object_sub_id]['object_sub_definitions'][$object_sub_description_id];
					
					while ($arr_row) {

						$select_id = $arr_row[2];
						$select_value = $arr_row[3];
						
						if ($this->conditions == static::CONDITIONS_MODE_TEXT && $select_value && $is_ref_type_id) {
							$select_value = Response::addParsePost($select_value, ['strip' => true]);
						}
						if ($s_arr['processing'] !== null) {
							$select_value = $s_arr['processing'][0].$select_value.$s_arr['processing'][1];
						}
																	
						if ($is_dynamic) {
							$s_arr['object_sub_definition_ref_object_id'] = $func_parse_references('object_sub_definition', $select_id, $object_sub_description_id);
							$s_arr['object_sub_definition_value'] = $select_value;
						} else if ($has_multi) {
							if ($select_id || $select_value != '') {
								$s_arr['object_sub_definition_ref_object_id'][] = ($select_id === null ? null : (int)$select_id);
								$s_arr['object_sub_definition_value'][] = $select_value;
							}
						} else {
							if ($select_id || $select_value != '') {
								$s_arr['object_sub_definition_ref_object_id'] = ($select_id === null ? null : (int)$select_id);
								$s_arr['object_sub_definition_value'] = $select_value;
							}
						}
						
						if (isset($arr_row[4])) {
							$s_arr['object_sub_definition_sources'] = $func_parse_sources('object_sub_definition', $arr_row[4]);
						}
											
						$arr_row = $res->fetchRow();
						
						$object_sub_id = (int)($arr_row[1] ?? 0);
						
						if ($object_sub_id != $cur_object_sub_id) {
							
							if ($s_arr['processing'] !== null) {
								unset($s_arr['processing']);
							}
														
							$cur_object_sub_id = $object_sub_id;
							
							if ($cur_object_sub_id == 0) {
								break;
							}
							
							$cur_object_id = (int)$arr_row[0];
							
							$s_arr =& $arr[$cur_object_id]['object_subs'][$cur_object_sub_id]['object_sub_definitions'][$object_sub_description_id];
						}
					}
					
					$res->freeResult();
				}
				unset($s_arr);
			}
		}

		// Object Names
		
		if ($this->view !== GenerateTypeObjects::VIEW_STORAGE && ($this->arr_type_object_name_object_sub_details_ids || $this->arr_type_object_name_object_description_ids || $this->arr_type_object_name_object_sub_description_ids)) {
			
			self::$do_check_shared_type_object_names = true;
			
			$sql = '';
			
			$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');
			
			if ($this->arr_type_object_name_object_sub_details_ids) {
					
				$arr_sql_object_subs = [];
				
				foreach ($this->arr_type_object_name_object_sub_details_ids as $object_sub_details_id => $arr_type) {
						
					if ($this->isFilteringObjectSubDetails($object_sub_details_id, true)) {
						
						$arr_sql_object_subs[] = [$this->table_name, 'object_sub_'.$object_sub_details_id.'_id'];
					}
				}
				
				if ($sql_query_subs_preload) {
					
					$arr_sql_object_subs[] = [$table_name_nodegoat_tos, 'id'];
				}
				
				if ($arr_sql_object_subs) {
					
					$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos');
					
					if ($this->view == static::VIEW_VISUALISE) {
						$sql_column_location_type_id = $arr_sql_location['column_geometry_type_id'];
						$sql_column_location_object_id = $arr_sql_location['column_geometry_object_id'];
					} else if ($this->view == static::VIEW_ALL) {
						$sql_column_location_type_id = $arr_sql_location['column_ref_show_type_id'];
						$sql_column_location_object_id = $arr_sql_location['column_ref_show_object_id'];
					} else if ($this->view == static::VIEW_SET || $this->view == static::VIEW_STORAGE) {
						$sql_column_location_type_id = 'nodegoat_tos.location_ref_type_id';
						$sql_column_location_object_id = 'nodegoat_tos.location_ref_object_id';
					}else {
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
						} else if (isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])) {
							
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
		
		$func_add_batch_sql = function($sql, $abortable = false) use (&$arr_batch_sql, &$nr_batch, &$func_run_batch_sql) {
			
			if ($this->is_user && $abortable) {
				$nr_batch++;
				$arr_batch_sql[$nr_batch] = $sql;
				$nr_batch++;
			} else if (is_callable($sql)) {
				$nr_batch++;
				$arr_batch_sql[$nr_batch] = $sql;
				$func_run_batch_sql(); // Run the current queue because a function could contain its own logic and queue 
			} else {
				$arr_batch_sql[$nr_batch][] = $sql;
			}
		};
		
		$func_run_batch_sql = function() use (&$arr_batch_sql) {
			
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
			
			$arr_batch_sql = [];
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
		
		$func_run_batch_sql();
	}
	
	protected function initPre() {
		
		if (!$this->arr_sql_pre_settings) {
			return;
		}

		$this->arr_sql_pre_queries[] = $this->sqlQuery('storage');
		
		foreach ($this->query_object_subs as $object_sub_details_id => $arr_tables_query_object_subs) {
			
			if (!isset($this->arr_selection['object_sub_details'][$object_sub_details_id])) {
				continue;
			}
			
			$this->arr_sql_pre_queries[] = $this->sqlQuerySub('object_sub_ids', $object_sub_details_id, 'foo');
		}
		
		$nr_batch = 0;
		$arr_batch_sql = [];
		
		$func_add_batch_sql = function($sql, $abortable = false) use (&$arr_batch_sql, &$nr_batch, &$func_run_batch_sql) {
			
			if ($this->is_user && $abortable) {
				$nr_batch++;
				$arr_batch_sql[$nr_batch] = $sql;
				$nr_batch++;
			} else if (is_callable($sql)) {
				$nr_batch++;
				$arr_batch_sql[$nr_batch] = $sql;
				$func_run_batch_sql(); // Run the current queue because a function could contain its own logic and queue 
			} else {
				$arr_batch_sql[$nr_batch][] = $sql;
			}
		};
		
		$func_run_batch_sql = function() use (&$arr_batch_sql) {
			
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
			
			$arr_batch_sql = [];
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
							
							$arr_sql_value = $arr_sql_pre_setting_check['value'];

							if ((isset($arr_sql_value['query']) && substr_count($arr_sql_value['query'], $table_name) > 1) || (isset($arr_sql_value['sql']) && substr_count($arr_sql_value['sql'], $table_name) > 1)) {
								
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
		
		$func_run_batch_sql();
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
		$sql_having = '';
		$sql_tables = '';
		
		if ($type == 'full') {
			$sql_select = implode(',', $this->arr_columns_object_as);
		} else if ($type == 'count') {
			$sql_select = 'nodegoat_to.id';
		} else if ($type == 'count_statistics') {
			$sql_select = 'nodegoat_to.id, nodegoat_to.active, nodegoat_to.status';
		} else if ($type == 'object_ids' || $type == 'object_ids_all' || $type == 'object_ids_all_statistics') {
			$sql_select = 'nodegoat_to.id';
		} else if ($type == 'storage') {
			
			$arr_select = [];
			$arr_group = [];
			$arr_having = [];
			
			$arr_select[] = 'nodegoat_to.id';
			$arr_group[] = 'nodegoat_to.id, nodegoat_to.version';
			
			$purpose = ($this->arr_filtering_filters === true ? false : 'filtering');
			
			if ($this->isFilteringObject()) {
				
				foreach (($this->arr_sql_filter_purpose_object[$purpose] ?? []) as $filter_code => $sql_filter) {
					
					$sql_column_name = "id_".$filter_code;
					$arr_select[] = "CASE WHEN ".$sql_filter." THEN 1 ELSE 0 END AS ".$sql_column_name;
					$arr_group[] = $sql_column_name;
				}
			}
			
			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if ($this->isFilteringObjectDescription($object_description_id)) {
					
					if ($arr_object_description['object_description_is_dynamic']) {
						
						$sql_column_name_affix = '_ref_object_id';
						$sql_field_name_affix = '_objects.ref_object_id';
						$sql_no_value = '0';
					} else if ($arr_object_description['object_description_ref_type_id']) {
						
						$sql_column_name_affix = '_ref_object_id';
						$sql_field_name_affix = '.ref_object_id';
						$sql_no_value = '0';
					} else {
						
						$sql_column_name_affix = '_value';
						$sql_field_name_affix = '.value';
						$sql_no_value = "''";
					}
					
					$sql_column_name = 'object_definition_'.$object_description_id.$sql_column_name_affix;
					$sql_field_name = 'nodegoat_to_def_'.$object_description_id.$sql_field_name_affix;
					
					$has_optional = (isset($this->query_object_descriptions['object_descriptions'][$object_description_id]) && arrHasValuesRecursive('optional', true, $this->query_object_descriptions['object_descriptions'][$object_description_id]));
					
					if ($has_optional) { // Needs alignment of tables

						$arr_sql_align_select = [];
						$arr_sql_align_having = [];
						
						foreach ($this->query_object_descriptions['object_descriptions'][$object_description_id] as $filter_code => $arr_table_info) {
							
							if ($arr_table_info['purpose'] != $purpose) {
								continue;
							}
							
							$sql_column_name_align = 'align_'.$filter_code.'_object_definition_'.$object_description_id;
							$sql_field_name_align = $arr_table_info['table_name'].$sql_field_name_affix;
							
							$arr_select[] = "CASE WHEN ".$sql_field_name_align." = ".$sql_field_name." THEN TRUE ELSE FALSE END AS ".$sql_column_name_align;
							$arr_group[] = $sql_column_name_align;
							
							$arr_sql_align_select[] = $sql_field_name_align.' = '.$sql_field_name;
							
							if ($arr_table_info['optional']) {
								
								$arr_sql_align_having[] = '(id_'.$filter_code.' != 0 AND '.$sql_column_name_align.' = TRUE)';
							} else {
								
								$arr_sql_align_having[] = $sql_column_name_align.' = TRUE';
							}
						}
						
						$arr_select[] = "CASE WHEN ".implode(' OR ', $arr_sql_align_select)." THEN ".$sql_field_name." ELSE ".$sql_no_value." END AS ".$sql_column_name;
						$arr_having[] = '('.implode(' OR ', $arr_sql_align_having).')';
					} else {
						
						$arr_select[] = $sql_field_name." AS ".$sql_column_name;
					}

					$arr_group[] = $sql_column_name;

					$is_filtering = true;
				}				
			}
			
			$sql_template = ($this->arr_sql_filter_purpose[$purpose] ? implode(' ', $this->arr_sql_filter_purpose[$purpose]) : '');
					
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if ($this->isFilteringObjectSubDetails($object_sub_details_id)) {
					
					$is_filtering_sub_or = ($this->arr_sql_query_object_subs_details[$object_sub_details_id] && arrHasValuesRecursive('sql_operator_sub', 'OR', $this->arr_sql_query_object_subs_details[$object_sub_details_id]));
					$sql_align_object_sub = false;
					
					if ($is_filtering_sub_or) { // Needs alignment of sub-objects
						
						$arr_sql_align_object_sub = [];
					
						foreach ($this->arr_sql_query_object_subs_details[$object_sub_details_id] as $arr_table_info) {
							
							if ($arr_table_info['purpose'] != $purpose) {
								continue;
							}
							
							if ($arr_table_info['optional']) {
								
								$sql_filter = ($this->arr_sql_filter_purpose_object[$purpose][$arr_table_info['filter_code']] ?? null);
								
								if ($sql_filter) {
									$arr_sql_align_object_sub[$arr_table_info['table_name']] = '('.$sql_filter.')';
								}
							} else {
							
								$arr_sql_align_object_sub[$arr_table_info['table_name']] = $arr_table_info['table_name'].'.id = nodegoat_tos_'.$object_sub_details_id.'.id';
							}
						}
						
						$sql_align_object_sub = implode(' OR ', $arr_sql_align_object_sub);
					}
					
					$sql_template_object_sub = false;
					
					if ($this->arr_sql_filter_purpose_general[$purpose]) {
						
						$sql_template_object_sub = $sql_template;
						
						foreach ($this->arr_sql_filter_purpose_general[$purpose] as $filter_code => $sql_value_general) {
							
							$sql_value_sub = $this->arr_sql_filter_purpose_subs[$purpose][$object_sub_details_id][$filter_code];
							
							$sql_template_object_sub = str_replace($sql_value_general, $sql_value_sub, $sql_template_object_sub);
						}
					}
														
					$sql_column_name_object_sub = 'object_sub_'.$object_sub_details_id.'_id';
					if ($sql_template_object_sub) {
						$arr_select[] = "CASE WHEN ".$sql_template_object_sub.($sql_align_object_sub ? " AND (".$sql_align_object_sub.")" : "")." THEN nodegoat_tos_".$object_sub_details_id.".id ELSE 0 END AS ".$sql_column_name_object_sub;
					} else {
						$arr_select[] = ($sql_align_object_sub ? "CASE WHEN ".$sql_align_object_sub." THEN nodegoat_tos_".$object_sub_details_id.".id ELSE 0 END" : "nodegoat_tos_".$object_sub_details_id.".id")." AS ".$sql_column_name_object_sub;
					}
					$arr_group[] = $sql_column_name_object_sub;

					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
						if ($this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id)) {
							
							if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
								
								$sql_column_name = 'object_sub_definition_'.$object_sub_description_id.'_ref_object_id';
								$arr_select[] = 'nodegoat_tos_def_'.$object_sub_description_id.'_objects.ref_object_id AS '.$sql_column_name;
								$sql_no_value = '0';
							} else if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
								
								$sql_column_name = 'object_sub_definition_'.$object_sub_description_id.'_ref_object_id';
								$arr_select[] = 'nodegoat_tos_def_'.$object_sub_description_id.'.ref_object_id AS '.$sql_column_name;
								$sql_no_value = '0';
							} else {
								
								$sql_column_name = 'object_sub_definition_'.$object_sub_description_id.'_value';
								$arr_select[] = 'nodegoat_tos_def_'.$object_sub_description_id.'.value AS '.$sql_column_name;
								$sql_no_value = "''";
							}
							
							$arr_group[] = $sql_column_name;
							
							if ($sql_align_object_sub) {
								$arr_having[] = '('.$sql_column_name_object_sub.' != 0 AND '.$sql_column_name.' != '.$sql_no_value.')';
							}
						}
					}
					
					$is_filtering = true;
				}
			}
			
			$sql_select = implode(',', $arr_select);
			$sql_group = implode(',', $arr_group);
			$sql_having = implode(' AND ', $arr_having);
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
		
		$sql_group_default = "nodegoat_to.id, nodegoat_to.version";
		$sql_group_extra = '';
		
		$sql_order = '';
		$sql_order_extra = '';

		$sql_limit = '';
		
		if ($type == 'full' || $type == 'object_ids' || $type == 'storage') {
			
			if ($this->arr_sql_order) {
				$sql_order = implode(',', $this->arr_sql_order).", nodegoat_to.id DESC";
			} else if ($this->arr_limit) {
				$sql_order = "nodegoat_to.id DESC";
			}
			
			if ($this->arr_sql_group) {
				$sql_group_extra .= ','.implode(',', $this->arr_sql_group);
			}
			
			if ($this->sql_limit && !$is_filtering) {
				$sql_limit = $this->sql_limit;
			}
		} else if ($type == 'object_ids_all' || $type == 'object_ids_all_statistics') { // No limits
			
			if ($this->arr_sql_order) {
				$sql_order = implode(',', $this->arr_sql_order).", nodegoat_to.id DESC";
			}
			
			if ($this->arr_sql_group) {
				$sql_group_extra .= ','.implode(',', $this->arr_sql_group);
			}
			
			if ($type == 'object_ids_all_statistics') {
				
				$sql_select .= ", CASE
					WHEN nodegoat_to.active = TRUE THEN 'active'
					WHEN nodegoat_to.status = 1 THEN 'added'
					WHEN nodegoat_to.status = 3 THEN 'deleted'
				END";
			}
		}
		
		$version_select = $this->generateVersion('object', 'nodegoat_to');
		$arr_sql_queries = [];
		
		$sql_table = DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS');
		$sql_table_where = "nodegoat_to.type_id = ".(int)$this->type_id
			
			." AND ".$version_select;
		
		foreach ((array)$this->arr_sql_filter['table'] as $key => $arr_table) {
			
			if ($arr_table['group']) {
				$sql_group_extra .= ','.$arr_table['group'];
			}
			
			if ($arr_table['order']) {
				
				$sql_order_extra .= ($sql_order_extra ? ',' : '').$arr_table['order'];
				
				if (!$arr_table['group']) {
					$sql_group_extra .= ','.$arr_table['order'];
				}
			}
			
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
				".($this->arr_sql_filter['search'] ? "JOIN (".$this->arr_sql_filter['search'].") s ON (s.id = nodegoat_to.id)" : '')."
				".($type == 'storage' || $type == 'custom' ? $this->arr_tables['filtering_object'].$this->arr_tables['filtering_object_more'] : $this->arr_tables['query_object'].$this->arr_tables['query_object_more'])."
			WHERE ".$sql_table_where."
				".($this->arr_sql_filter['objects'] ? "AND nodegoat_to.id IN (".implode(',', $this->arr_sql_filter['objects']).")" : '')."
				".($this->arr_sql_filter['filter'] ? "AND (".implode(" AND ", $this->arr_sql_filter['filter']).")" : '')."
				".($this->arr_sql_filter['date'] ? "AND (".implode(" AND ", $this->arr_sql_filter['date']).")" : '')."
			GROUP BY ".($sql_group ?: $sql_group_default).$sql_group_extra."
			".($sql_having ? "HAVING ".$sql_having : '')."
			".($sql_order || $sql_order_extra ? "ORDER BY ".$sql_order_extra.($sql_order_extra && $sql_order ? ',' : '').$sql_order : '')."
			".$sql_limit."
		";
		
		if ($type == 'count' || $type == 'count_statistics') {
			
			$sql_count = 'COUNT(nodegoat_to.id)';
			
			if ($type == 'count_statistics') {
				
				$sql_count .= ",
					COUNT(CASE WHEN nodegoat_to.active = TRUE THEN 1 ELSE NULL END) AS 'active',
					COUNT(CASE WHEN nodegoat_to.status = 1 THEN 1 ELSE NULL END) AS 'added',
					COUNT(CASE WHEN nodegoat_to.status = 3 THEN 1 ELSE NULL END) AS 'deleted'
				";
			}
			
			$query = "SELECT ".$sql_count." FROM (".$query.") nodegoat_to";
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
				
			foreach (($this->arr_sql_filter_purpose_object[$purpose] ?? []) as $filter_code => $sql_filter) {
				
				$sql_column_name = "id_".$filter_code;
				$arr_sql_columns[] = $sql_column_name." BOOLEAN";
				$arr_sql_index[] = $sql_column_name;
				$arr_sql_select[] = $sql_column_name;
				
				$has_filtering = true;
			}
		}

		foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if ($this->isFilteringObjectDescription($object_description_id)) {
				
				$has_optional = (isset($this->query_object_descriptions['object_descriptions'][$object_description_id]) && arrHasValuesRecursive('optional', true, $this->query_object_descriptions['object_descriptions'][$object_description_id]));

				if ($has_optional) { // Needs alignment of tables
					
					foreach ($this->query_object_descriptions['object_descriptions'][$object_description_id] as $filter_code => $arr_table_info) {
						
						$sql_column_name = 'align_'.$filter_code.'_object_definition_'.$object_description_id;

						$arr_sql_columns[] = $sql_column_name." BOOLEAN";
						$arr_sql_index[] = $sql_column_name;
					}
				}
				
				if ($arr_object_description['object_description_ref_type_id'] || $arr_object_description['object_description_is_dynamic']) {
					
					$sql_column_name = "object_definition_".$object_description_id."_ref_object_id";
					$arr_sql_columns[] = $sql_column_name." INT";
					$arr_sql_index[] = $sql_column_name;
				} else {
					
					$sql_column_name = "object_definition_".$object_description_id."_value";
					$arr_sql_columns[] = $sql_column_name." VARCHAR(5000) ".(DB::ENGINE_IS_MYSQL ? "COLLATE utf8mb4_unicode_ci" : '');
					$arr_sql_index[] = $sql_column_name."(100)"; // Limit index size for char
				}
				
				$arr_sql_select[] = $sql_column_name;
				
				$has_filtering = true;
			}				
		}
		
		foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
			if ($this->isFilteringObjectSubDetails($object_sub_details_id)) {
				
				$sql_column_name = "object_sub_".$object_sub_details_id."_id";
				$arr_sql_columns[] = $sql_column_name." INT";
				$arr_sql_index[] = $sql_column_name;
				$arr_sql_select[] = $sql_column_name;
				
				$has_filtering = true;
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
				
					if ($this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id)) {
						
						if ($arr_object_sub_description['object_sub_description_ref_type_id'] || $arr_object_sub_description['object_sub_description_is_dynamic']) {

							$sql_column_name = "object_sub_definition_".$object_sub_description_id."_ref_object_id";
							$arr_sql_columns[] = $sql_column_name." INT";
							$arr_sql_index[] = $sql_column_name;
						} else {
							
							$sql_column_name = "object_sub_definition_".$object_sub_description_id."_value";
							$arr_sql_columns[] = $sql_column_name." VARCHAR(5000) ".(DB::ENGINE_IS_MYSQL ? "COLLATE utf8mb4_unicode_ci" : '');
							$arr_sql_index[] = $sql_column_name."(100)"; // Limit index size for char
						}
						
						$arr_sql_select[] = $sql_column_name;
						
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
			$sql_group = 'nodegoat_to.id, '.$table_name.'.id, '.$table_name.'.version';
		} else if ($type == 'object_sub_ids_object_ids_referenced_object_sub_description_ids') {
			$sql_select = $table_name.'.id, nodegoat_to.id AS object_id, '.($object_sub_details_id == 'all' ? $table_name.'_all.referenced_object_sub_description_id' : $table_name.'_def.object_sub_description_id').' AS referenced_object_sub_description_id';
			$sql_group = 'nodegoat_to.id, '.$table_name.'.id, '.$table_name.'.version';
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
						referenced_object_sub_description_id INT NULL,
							PRIMARY KEY (id, object_sub_id)
					) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
					
					TRUNCATE TABLE ".$table_name_all.";
					
					INSERT INTO ".$table_name_all." (id, object_sub_id, referenced_object_sub_description_id)
						SELECT nodegoat_to.id, ".$table_name.".id AS object_sub_id, NULL AS referenced_object_sub_description_id
							FROM ".$table_name_nodegoat_to." AS nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".object_id = nodegoat_to.id AND object_sub_details_id ".($object_sub_details_id == 'all' ? 'IN ('.implode(',', $this->arr_selection_object_sub_details_ids).')' : "= ".$object_sub_details_id)." AND ".$version_select_tos.")
					;
					
					INSERT INTO ".$table_name_all." (id, object_sub_id, referenced_object_sub_description_id)
						SELECT nodegoat_to.id, ".$table_name."_def.object_sub_id, ".$table_name."_def.object_sub_description_id AS referenced_object_sub_description_id
							FROM ".$table_name_nodegoat_to." AS nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$table_name."_def ON (".$table_name."_def.ref_object_id = nodegoat_to.id AND ".$table_name."_def.object_sub_description_id IN (".implode(',', $arr_referenced_object_sub_description_ids).") AND ".$version_select.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name." ON (".$table_name.".id = ".$table_name."_def.object_sub_id AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." ".$table_name."_to ON (".$table_name."_to.id = ".$table_name.".object_id AND ".$version_select_tos_to.")
						".DBFunctions::onConflict('id, object_sub_id', false)."
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
					
					$value = (strtoupper($value) == 'ASC' ? 'ASC' : 'DESC');
					
					$sql_order .= $this->arr_columns_subs[$object_sub_details_id][$key]." ".$value.", ";
					$sql_group .= ", ".$this->arr_columns_subs[$object_sub_details_id][$key];
				}
				
				$sql_order .= ($sql_order_all ? $sql_order_all.", " : "").$table_name.".id ASC";
			}
			
			$sql_limit = $this->arr_sql_limit_object_subs[$object_sub_details_id];
		}
		
		$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id']; // In case the sub-object is referenced
				
		$query = "SELECT ".$sql_select."
					FROM ".$table_name_nodegoat_to." AS nodegoat_to
					".$sql_join."
					".($this->arr_tables['query_object_subs'][$object_sub_details_id] ?? '').($this->arr_tables['query_object_subs_more'][$object_sub_details_id] ?? '')."
					".($this->arr_tables['select_object_subs'][$object_sub_details_id] ?? '')."
				WHERE ".$table_name.".object_sub_details_id ".($object_sub_details_id == 'all' ? "IN (".implode(',', $this->arr_selection_object_sub_details_ids).")" : "= ".$use_object_sub_details_id)."
					".($this->arr_sql_filter['object_subs'] ? "AND ".$table_name.".id IN (".implode(',', $this->arr_sql_filter['object_subs']).")" : "")."
					".(isset($this->arr_sql_filter_subs[$object_sub_details_id]['filter']) ? "AND ".implode(' AND ', $this->arr_sql_filter_subs[$object_sub_details_id]['filter']) : '')."
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
			if (isset($this->arr_type_set['type']['include_referenced']['object_sub_details'])) {
				
				foreach ($this->arr_type_set['type']['include_referenced']['object_sub_details'] as $object_sub_details_id => $arr_object_sub_description_ids) {
											
					unset($this->arr_selection_object_sub_details_ids[$object_sub_details_id]);
					
					$use_object_sub_details_id = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_id'];
					$this->arr_selection_object_sub_details_ids[$use_object_sub_details_id] = $use_object_sub_details_id;
				}
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
		
		if ($this->view == GenerateTypeObjects::VIEW_VISUALISE || $this->view == GenerateTypeObjects::VIEW_OVERVIEW || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) {
			
			$has_selected_object_descriptions = is_array($arr_selection_settings['object_descriptions']);
				
			foreach ($this->arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$in_preselection = (
					(($this->view == GenerateTypeObjects::VIEW_OVERVIEW && $arr_object_description['object_description_in_overview']) || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE)
				);
				
				if ((!$has_selected_object_descriptions && !$in_preselection) || ($has_selected_object_descriptions && !$arr_selection_settings['object_descriptions'][$object_description_id])) {
					continue;
				}
				
				$select_value = true;
				$select_reference = true;
				$select_reference_value = true;
				
				if ($has_selected_object_descriptions) {
					
					$arr_selection = $arr_selection_settings['object_descriptions'][$object_description_id];
					$has_selected_specific = (is_array($arr_selection) && ($arr_selection['object_description_value'] || $arr_selection['object_description_reference']));
					
					$select_value = (!$has_selected_specific ? true : $arr_selection['object_description_value']);
					$select_reference = (!$has_selected_specific ? true : $arr_selection['object_description_reference']);
					$select_reference_value = (!$has_selected_specific ? true : $arr_selection['object_description_reference_value']); // Dynamic
					
					if (is_array($select_reference)) {
						$select_reference = arrParseRecursive($select_reference, 'int');
					}
				}
				
				$this->arr_selection['object_descriptions'][$object_description_id] = ['object_description_value' => $select_value, 'object_description_reference' => $select_reference, 'object_description_reference_value' => $select_reference_value];
			}
		}
		
		if ($this->view == GenerateTypeObjects::VIEW_VISUALISE || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) {
			
			$has_selected = is_array($arr_selection_settings['object_sub_details']);
			
			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$is_selected = ($has_selected && $arr_selection_settings['object_sub_details'][$object_sub_details_id]);
				$has_filtered = $this->arr_filter_object_sub_details_ids;
				$is_filtered = ($has_filtered && !empty($this->arr_filter_object_sub_details_ids[$object_sub_details_id]));
				
				if (($has_selected && !$is_selected) || (!$has_selected && $has_filtered && !$is_filtered)) {
					continue;
				}
				
				$arr_selected_self = ($arr_selection_settings['object_sub_details'][$object_sub_details_id]['object_sub_details'] ?? null);

				$this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = (is_array($arr_selected_self) ? $arr_selected_self : ['all' => true]);
				
				$arr_selected_object_sub_descriptions = ($arr_selection_settings['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] ?? null);
				$has_selected_object_sub_descriptions = is_array($arr_selected_object_sub_descriptions);

				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$in_preselection = ($this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE);
					
					if ((!$has_selected_object_sub_descriptions && !$in_preselection) || ($has_selected_object_sub_descriptions && !$arr_selected_object_sub_descriptions[$object_sub_description_id])) {
						continue;
					}

					$select_value = true;
					$select_reference = true;
					$select_reference_value = true;
					
					if ($has_selected_object_sub_descriptions) {
						
						$arr_selection = $arr_selected_object_sub_descriptions[$object_sub_description_id];
						$has_selected_specific = (is_array($arr_selection) && ($arr_selection['object_sub_description_value'] || $arr_selection['object_sub_description_reference']));
						
						$select_value = (!$has_selected_specific ? true : $arr_selection['object_sub_description_value']);
						$select_reference = (!$has_selected_specific ? true : $arr_selection['object_sub_description_reference']);
						$select_reference_value = (!$has_selected_specific ? true : $arr_selection['object_sub_description_reference_value']); // Dynamic
						
						if (is_array($select_reference)) {
							$select_reference = arrParseRecursive($select_reference, 'int');
						}
					}
					
					$this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = ['object_sub_description_value' => $select_value, 'object_sub_description_reference' => $select_reference, 'object_sub_description_reference_value' => $select_reference_value];
				}
			}
		}

		// Conditions
		
		if ($this->arr_type_set_conditions) {
				
			$arr_conditions_collect = [];
			
			$func_process_condition_action_values = function(&$arr_condition_setting, $group) {
				
				if ($this->str_identifier_scope !== false && $arr_condition_setting['condition_scope'] && !$arr_condition_setting['condition_scope'][$this->str_identifier_scope]) {
					
					$arr_condition_setting = false;
					return;
				}
				
				if (!empty($arr_condition_setting['condition_actions']['weight']['number_use_object_description_id'])) {
				
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
				
				if (!empty($arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'])) {
					
					$arr_condition_setting['condition_value']['object_analysis_id'] = $arr_condition_setting['condition_actions']['weight']['number_use_object_analysis_id'];
				}
			};
			
			if ($this->arr_type_set_conditions['object']) {
				
				foreach ($this->arr_type_set_conditions['object'] as $key => &$arr_condition_setting) {
					
					if ($arr_condition_setting['condition_in_object_nodes_object'] && $this->conditions != GenerateTypeObjects::CONDITIONS_MODE_FULL) {
						
						unset($this->arr_type_set_conditions['object'][$key]);
						continue;
					}

					$func_process_condition_action_values($arr_condition_setting, 'object');
					
					if (!$arr_condition_setting) {
						
						unset($this->arr_type_set_conditions['object'][$key]);
						continue;
					}
					
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
							($arr_condition_setting['condition_in_object_nodes_referencing'] && $this->conditions != GenerateTypeObjects::CONDITIONS_MODE_FULL)
						) {
							
							unset($arr_condition_settings[$key]);
							continue;
						}
						
						$func_process_condition_action_values($arr_condition_setting, 'object');
						
						if (!$arr_condition_setting) {
						
							unset($arr_condition_settings[$key]);
							continue;
						}
						
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
					
					$in_selection_object_sub_details = (bool)($this->arr_selection['object_sub_details'][$object_sub_details_id] ?? false);

					if ($arr_conditions_object_sub_details['object_sub_details']) {
						
						if (!$in_selection_object_sub_details) {
						
							unset($arr_conditions_object_sub_details['object_sub_details']);
						} else {
							
							foreach ($arr_conditions_object_sub_details['object_sub_details'] as $key => &$arr_condition_setting) {
								
								if ($arr_condition_setting['condition_in_object_nodes_object'] && $this->conditions != GenerateTypeObjects::CONDITIONS_MODE_FULL) {
									
									unset($arr_conditions_object_sub_details['object_sub_details'][$key]);
									continue;
								}
								
								$func_process_condition_action_values($arr_condition_setting, 'object_sub_details');
								
								if (!$arr_condition_setting) {
						
									unset($arr_conditions_object_sub_details['object_sub_details'][$key]);
									continue;
								}
								
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
							
							$in_selection_object_sub_description = ($in_selection_object_sub_details && !empty($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]));

							foreach ($arr_condition_settings as $key => &$arr_condition_setting) {
								
								if (
									(!$arr_condition_setting['condition_in_object_name'] && !$in_selection_object_sub_description)
									||
									($arr_condition_setting['condition_in_object_nodes_referencing'] && $this->conditions != GenerateTypeObjects::CONDITIONS_MODE_FULL)
								) {
									
									unset($arr_condition_settings[$key]);
									continue;
								}
								
								$func_process_condition_action_values($arr_condition_setting, 'object_sub_details');
								
								if (!$arr_condition_setting) {
						
									unset($arr_condition_settings[$key]);
									continue;
								}

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
					
					$filter_condition = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);
					$filter_condition->setScope($this->arr_scope);
					$filter_condition->setDifferentiationIdentifier($this->getDifferentiationIdentifier());
					
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
							$str_value_type = $arr_object_description['object_description_value_type'];
							$sql_value = $table_name.".".StoreType::getValueTypeValue($str_value_type);
							$sql_value = ($str_value_type == 'numeric' ? StoreTypeObjects::sqlInt2SQLNumeric($sql_value) : $sql_value);
							$sql_value = ($arr_object_description['object_description_has_multi'] ? "SUM(".$sql_value.")" : $sql_value);
							
							$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($str_value_type)." AS ".$table_name."
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
							
							if ($analysis_user_id === false) { // Make sure we have something
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
							$str_value_type = $arr_object_description['object_description_value_type'];
							$sql_value = $table_name.".".StoreType::getValueTypeValue($str_value_type);
							$sql_value = ($str_value_type == 'numeric' ? StoreTypeObjects::sqlInt2SQLNumeric($sql_value) : $sql_value);
							$sql_value = ($arr_object_description['object_description_has_multi'] ? "SUM(".$sql_value.")" : $sql_value);
							
							$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($str_value_type)." AS ".$table_name."
								WHERE ".$table_name.".object_id = nodegoat_tos_".(int)$object_sub_details_id.".object_id AND ".$table_name.".object_description_id = ".(int)$object_description_id." AND ".$version_select."
							)";							
						} else if ($arr_condition_setting['condition_value']['object_sub_description_id']) {

							$object_sub_description_id = $arr_condition_setting['condition_value']['object_sub_description_id'];
							$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
							
							$column_name = 'des_'.(int)$object_sub_details_id.'_'.(int)$object_sub_description_id;
							$table_name = 'nodegoat_tos_def';
							$version_select = $this->generateVersion('record', $table_name);
							$str_value_type = $arr_object_sub_description['object_sub_description_value_type'];
							$sql_value = $table_name.".".StoreType::getValueTypeValue($str_value_type);
							$sql_value = ($str_value_type == 'numeric' ? StoreTypeObjects::sqlInt2SQLNumeric($sql_value) : $sql_value);
										
							$arr_sql_value[$column_name] = "(SELECT ".$sql_value." AS column_value
								FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($str_value_type)." AS ".$table_name."
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
							
							if ($analysis_user_id === false) { // Make sure we have something
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
	
	private function generateTablesColumns() {
		
		if ($this->sql_tables_columns_generated) {
			return;
		}
		$this->sql_tables_columns_generated = true;
		
		$this->generateTableName();
	
		$this->arr_columns_object_as[] = "nodegoat_to.id";
		
		$do_select_object = ($this->arr_selection['object']['all'] ? true : false);
		$do_select_object_name = ($this->view != GenerateTypeObjects::VIEW_ID && $this->view != GenerateTypeObjects::VIEW_STORAGE && $do_select_object);
		$do_order_object_name = ($this->arr_order['object_name'] ?? false);
		
		// Columns
		if (($do_select_object_name || $do_order_object_name) && arrValuesRecursive('object_description_in_name', $this->arr_type_set['object_descriptions'])) {
			
			if ($do_select_object_name) {
				
				$s_select = $this->generateTablesColumnsName();
				$this->arr_columns_object_as[] = $s_select." AS object_name";
			} else {
				
				$this->arr_columns_object_as[] = "NULL AS object_name";
			}
			
			if ($do_order_object_name) {
				
				$s_select = $this->generateTablesColumnsNameColumn();
				$this->arr_columns_object['object_name'] = $s_select;
			}
		} else {
			
			$sql_name = "CASE WHEN nodegoat_to.name LIKE '%[[%' THEN CONCAT('".Labels::addContainerOpen()."', nodegoat_to.name, '".Labels::addContainerClose()."') ELSE nodegoat_to.name END"; // Add language delimiters when needed
			
			$this->arr_columns_object_as[] = ($do_select_object_name ? $sql_name : "NULL")." AS object_name";
		
			if ($do_order_object_name) {
				
				$this->arr_columns_object['object_name'] = $sql_name;
			}
		}
		
		$this->arr_columns_object_as[] = ((($this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE || $this->view == GenerateTypeObjects::VIEW_OVERVIEW) && $do_select_object) ? "nodegoat_to.name" : "NULL")." AS object_name_plain";
		
		$this->arr_columns_object_as[] = ((($this->view == GenerateTypeObjects::VIEW_VISUALISE || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) && $do_select_object) ? "(SELECT ".DBFunctions::sqlImplode("CONCAT(nodegoat_to_src.ref_object_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_to_src.ref_type_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_to_src.value)", static::SQL_GROUP_SEPERATOR)."
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SOURCES')." nodegoat_to_src
			WHERE nodegoat_to_src.object_id = nodegoat_to.id
		)" : "NULL")." AS object_sources";
		
		if ($do_select_object) {
			
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
		
		if (($this->view == GenerateTypeObjects::VIEW_OVERVIEW || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET) && $do_select_object) {
				
			$this->arr_columns_object_as[] = "(SELECT
				CASE
					WHEN nodegoat_to_status.date_discussion > nodegoat_to_status.date_object THEN ".(DB::ENGINE_IS_MYSQL ? "DATE_FORMAT(nodegoat_to_status.date_discussion, '%Y-%m-%dT%TZ')" : (DB::ENGINE_IS_POSTGRESQL ? "TO_CHAR(nodegoat_to_status.date_discussion, 'YYYY-MM-DD\"T\"HH24:MI:SSTZ')" : ''))."
					ELSE ".(DB::ENGINE_IS_MYSQL ? "DATE_FORMAT(nodegoat_to_status.date_object, '%Y-%m-%dT%TZ')" : (DB::ENGINE_IS_POSTGRESQL ? "TO_CHAR(nodegoat_to_status.date_object, 'YYYY-MM-DD\"T\"HH24:MI:SSTZ')" : ''))."
				END AS date
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status
				WHERE nodegoat_to_status.object_id = nodegoat_to.id
			) AS object_dating";
			
			if ($this->arr_order['date']) {
				
				$this->arr_columns_object['date'] = "(SELECT
					CASE
						WHEN nodegoat_to_status.date_discussion > nodegoat_to_status.date_object THEN nodegoat_to_status.date_discussion
						ELSE nodegoat_to_status.date_object
					END AS date
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_STATUS')." nodegoat_to_status
					WHERE nodegoat_to_status.object_id = nodegoat_to.id
				)";
			}
		} else {
			$this->arr_columns_object_as[] = "NULL AS object_dating";
		}
		
		if (($this->view == GenerateTypeObjects::VIEW_OVERVIEW || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET) && $do_select_object) {
			
			$s_select = "(SELECT
				CASE
					WHEN identifier = '' OR (date_updated + ".DBFunctions::interval(StoreTypeObjects::$timeout_lock, 'SECOND').") > NOW() THEN user_id
					ELSE NULL
				END
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_LOCK')." nodegoat_to_lock
				WHERE nodegoat_to_lock.object_id = nodegoat_to.id
					AND nodegoat_to_lock.type = 1
			)";
				
			$this->arr_columns_object_as[] = $s_select." AS object_locked";
		} else {
			
			$this->arr_columns_object_as[] = "NULL AS object_locked";
		}
		
		if ($this->view == GenerateTypeObjects::VIEW_OVERVIEW) {
			
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
				$this->arr_columns_object_as[] = "CONCAT(".implode(", '".static::SQL_ANALYSIS_SEPERATOR."',", $arr_sql).") AS object_analysis";
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
		
		foreach ($this->query_object_names as $table_name) {
			
			$version_select = $this->generateVersion('object', $table_name);
			
			$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." ".$table_name." ON (".$table_name.".id = nodegoat_to.id AND ".$version_select.")";
			
			$this->arr_tables['query_object'] .= $sql_table;
			$this->arr_tables['filtering_object'] .= $sql_table;
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
		
		if ($this->arr_selection['object_descriptions'] || $this->query_object_descriptions || $this->arr_order) {
				
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
				
				if (isset($this->query_object_descriptions['object_descriptions'][$object_description_id])) {
				
					$arr_sql_query = [];
					
					$has_optional = arrHasValuesRecursive('optional', true, $this->query_object_descriptions['object_descriptions'][$object_description_id]);
					
					foreach ($this->query_object_descriptions['object_descriptions'][$object_description_id] as $arr_table_info) {
						
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
						
						if ($is_filtering && !$has_optional) {

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
				}
				
				if (isset($this->query_object_descriptions['object_descriptions_sources'][$object_description_id])) {
						
					foreach ($this->query_object_descriptions['object_descriptions_sources'][$object_description_id] as $arr_table_info) {
							
						$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_SOURCES')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = nodegoat_to.id AND ".$arr_table_info['table_name'].".object_description_id = ".$object_description_id.")";
					
						$this->arr_tables['query_object'] .= $sql_table;
						$this->arr_tables['filtering_object'] .= $sql_table;
					}
				}

				if (!empty($this->arr_selection['object_descriptions'][$object_description_id]) || $this->arr_order['object_description_'.$object_description_id]) {
									
					$this->count_object_descriptions++;
										
					$arr_sql_columns = $this->generateTablesColumnsObjectDescription($object_description_id);
					
					if ($arr_sql_columns['column']) {
						$this->arr_columns_object['object_description_'.$object_description_id] = $arr_sql_columns['column'];
					}
					
					if (!empty($this->arr_selection['object_descriptions'][$object_description_id])) {
						
						$this->arr_columns_object_descriptions[$object_description_id]['select_id'] = $arr_sql_columns['select_id'];
						$this->arr_columns_object_descriptions[$object_description_id]['select_value'] = $arr_sql_columns['select_value'];
						$this->arr_columns_object_descriptions[$object_description_id]['group'] = $arr_sql_columns['group'];
						$this->arr_columns_object_descriptions[$object_description_id]['order'] = $arr_sql_columns['order'];
						$this->arr_columns_object_descriptions[$object_description_id]['tables'] = $arr_sql_columns['tables'];
						$this->arr_column_purpose[$object_description_id]['view'] = true;
						
						if ($this->view == GenerateTypeObjects::VIEW_VISUALISE || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) {
							$this->arr_columns_object_descriptions[$object_description_id]['sources'] = true;
						}
					}
				}
			}
		}
		
		// Sub-objects
		if ($this->arr_selection['object_sub_details'] || $this->query_object_subs) {

			foreach ($this->arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
			
				if (!isset($this->query_object_subs[$object_sub_details_id]) && !isset($this->arr_selection['object_sub_details'][$object_sub_details_id])) {
					continue;
				}
				
				$is_filtering_sub = $this->isFilteringObjectSubDetails($object_sub_details_id);
				$table_name_tos = 'nodegoat_tos_'.$object_sub_details_id;
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
				
				if (isset($this->query_object_subs[$object_sub_details_id]) && is_array($this->query_object_subs[$object_sub_details_id])) {
					
					$is_filtering_sub_or = ($is_filtering_sub && arrHasValuesRecursive('sql_operator_sub', 'OR', $this->query_object_subs[$object_sub_details_id])); // Do not align filtering tables with the main tables as we need to check various possibilities. Final alignment happens in the select clause.

					foreach ($this->query_object_subs[$object_sub_details_id] as $table_name_query_object_sub => $arr_query_object_sub) {
						
						$arr_sql_query_object_sub_descriptions = [];
						
						foreach (($arr_query_object_sub['date'] ?? []) as $arr_table_info) {
							
							$s_arr =& $this->arr_sql_query_object_subs_details[$object_sub_details_id][$arr_table_info['table_name']];
							
							if (!$s_arr) {
								$s_arr = $arr_table_info;
							}
							
							$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];
							
							$s_arr['date'] = true;
						}					
						
						foreach (($arr_query_object_sub['location'] ?? []) as $arr_table_info) {
							
							$s_arr =& $this->arr_sql_query_object_subs_details[$object_sub_details_id][$arr_table_info['table_name']];
							
							if (!$s_arr) {
								$s_arr = $arr_table_info;
							}
							
							$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];
							
							$s_arr['location'] = true;
						}
						
						foreach (($arr_query_object_sub['referenced'] ?? []) as $arr_table_info) {
							
							$s_arr =& $this->arr_sql_query_object_subs_details[$object_sub_details_id][$arr_table_info['table_name']];
							
							if (!$s_arr) {
								$s_arr = $arr_table_info;
							}
							
							$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];

							$s_arr['referenced'] = true;
						}
						
						foreach (($arr_query_object_sub['referencing'] ?? []) as $referencing_id => $arr_query_referencing) {
							
							foreach ($arr_query_referencing as $arr_table_info) {
								
								$s_arr =& $this->arr_sql_query_object_subs_details[$object_sub_details_id][$arr_table_info['table_name']];
								
								if (!$s_arr) {
									$s_arr = $arr_table_info;
								}
								
								$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];

								$s_arr['referencing'] = true;
							}
						}

						foreach (($arr_query_object_sub['object_sub_sources'] ?? []) as $arr_table_info) {
						
							$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_sub_id = ".$table_name_query_object_sub.".id)";
							
							$this->arr_tables['query_object_more'] .= $sql_table;
							if ($arr_table_info['filter_object_subs']) {
								$this->arr_tables['query_object_subs_more'][$object_sub_details_id] .= $sql_table;
							}
							$this->arr_tables['filtering_object_more'] .= $sql_table;
						}
						
						foreach (($arr_query_object_sub['object_sub_descriptions'] ?? []) as $object_sub_description_id => $arr_query_object_sub_description) {
							
							foreach ($arr_query_object_sub_description as $arr_table_info) {
								
								// Prepare new sub-object filter configuration
								
								$s_arr =& $this->arr_sql_query_object_subs_details[$object_sub_details_id][$table_name_query_object_sub];
								
								if (!$s_arr) {
									
									$s_arr = $arr_table_info;
									
									$s_arr['table_name'] = $table_name_query_object_sub;
									$s_arr['arr_sql_filter'] = [];		
								}
								
								if ($arr_table_info['filter_object_subs']) { // Check for specific settings as sub-object descriptions could be sharing the same sub-object table
									$s_arr['filter_object_subs'] = $arr_table_info['filter_object_subs'];
								}
								
								// Prepare sub-object description filter configuration
								
								$s_arr =& $arr_sql_query_object_sub_descriptions[$object_sub_description_id][$arr_table_info['table_name']];
								
								if (!$s_arr) {
									$s_arr = $arr_table_info;
								}
								
								$s_arr['arr_sql_filter'][$arr_table_info['arr_sql']['sql_filter']] = $arr_table_info['arr_sql']['sql_filter'];
							}
						}
						unset($s_arr);
						
						foreach (($arr_query_object_sub['object_sub_descriptions_sources'] ?? []) as $object_sub_description_id => $arr_query_object_sub_description_sources) {
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
									
									if ($is_filtering && !$is_filtering_sub_or) {
							
										$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = ".$table_name.".object_id AND ".$arr_table_info['table_name'].".ref_object_id = ".$table_name.".ref_object_id AND ".$arr_table_info['table_name'].".object_description_id = ".$object_sub_description_use_object_description_id." AND ".$version_select_query." ".$sql_filter.")";
									}
									
									$this->arr_tables['filtering_object_more'] .= $sql_table;
								} else {
									
									$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'], 'search')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_sub_id = ".$table_name_query_object_sub.".id AND ".$arr_table_info['table_name'].".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select_query." ".$sql_filter.")";
								
									$this->arr_tables['query_object_more'] .= $sql_table;
									if ($arr_table_info['filter_object_subs']) {
										$this->arr_tables['query_object_subs_more'][$object_sub_details_id] .= $sql_table;
									}
							
									if ($is_filtering && !$is_filtering_sub_or) {
										
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
				
				if (!isset($this->arr_selection['object_sub_details'][$object_sub_details_id])) {
					continue;
				}
				
				if ($this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) {
					
					if ($this->view == GenerateTypeObjects::VIEW_STORAGE) {
							
						$arr_sql_date = [
							'column_start' => 'NULL',
							'column_end' => 'NULL',
							'column_all' => 'NULL',
							'column_chronology' => "CASE
								WHEN ".$table_name_tos.".date_version IS NOT NULL THEN (SELECT ".StoreTypeObjects::formatFromSQLValue('chronology', $table_name_tos.'_date')." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$table_name_tos."_date WHERE ".$table_name_tos."_date.object_sub_id = ".$table_name_tos.".id AND ".$table_name_tos."_date.version = ".$table_name_tos.".date_version)
								ELSE NULL
							END"
						];
					} else {
						
						$arr_sql_date = $this->generateTablesColumnsObjectSubDate($table_name_tos);
						$arr_sql_date['column_all'] = 'NULL';
					}
					
					$arr_sql_location = [
						'column_geometry' => "CASE
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
					
					if ($this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_OVERVIEW) {
						
						$arr_sql_date = $this->generateTablesColumnsObjectSubDate($table_name_tos, true, true);
						$arr_sql_date['column_all'] = "CONCAT(':', COALESCE(".DBFunctions::castAs($arr_sql_date['column_start'], DBFunctions::CAST_TYPE_STRING).", ''), ':', COALESCE(".DBFunctions::castAs($arr_sql_date['column_start_end'], DBFunctions::CAST_TYPE_STRING).", ''), ':', COALESCE(".DBFunctions::castAs($arr_sql_date['column_end_start'], DBFunctions::CAST_TYPE_STRING).", ''), ':', COALESCE(".DBFunctions::castAs($arr_sql_date['column_end'], DBFunctions::CAST_TYPE_STRING).", ''))"; // Extra to fill index 0
					} else {
						
						$arr_sql_date = $this->generateTablesColumnsObjectSubDate($table_name_tos);
						$arr_sql_date['column_all'] = 'NULL';
					}

					$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference($table_name_tos, false, true); // Get readable geometries
					
					if ($this->view == GenerateTypeObjects::VIEW_VISUALISE) {
						
						$arr_sql_location['column_geometry'] = $arr_sql_location['column_geometry_translate'];
						$arr_sql_location['tables'] = $arr_sql_location['tables_translate'];
					}
				}
				
				$arr_sql_location['column_ref_object_id'] = ($this->view == GenerateTypeObjects::VIEW_ALL ? $arr_sql_location['column_ref_show_object_id'] : $arr_sql_location['column_ref_object_id']);
				$arr_sql_location['column_ref_type_id'] = ($this->view == GenerateTypeObjects::VIEW_ALL ? $arr_sql_location['column_ref_show_type_id'] : $arr_sql_location['column_ref_type_id']);
				$arr_sql_location['column_ref_object_sub_details_id'] = ($this->view == GenerateTypeObjects::VIEW_ALL ? $arr_sql_location['column_ref_show_object_sub_details_id'] : $arr_sql_location['column_ref_object_sub_details_id']);
				
				$do_select_object_sub_details = true;
				
				if ($this->view == GenerateTypeObjects::VIEW_VISUALISE || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) {
					$do_select_object_sub_details = ($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['all'] ?? false);
				}
			
				if ($do_select_object_sub_details) {
					
					$this->arr_tables['select_object_subs'][$object_sub_details_id] = $arr_sql_date['tables'].$arr_sql_location['tables'];
					
					$this->arr_type_object_name_object_sub_details_ids[$object_sub_details_id]['location'] = true;
				} else {
					
					$this->arr_tables['select_object_subs'][$object_sub_details_id] = '';
					
					if (empty($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_date'])) {
						
						$arr_sql_date = [
							'column_start' => 'NULL',
							'column_end' => 'NULL',
							'column_all' => 'NULL',
							'column_chronology' => 'NULL'
						];
					} else {
						
						$this->arr_tables['select_object_subs'][$object_sub_details_id] .= $arr_sql_date['tables'];
					}
					
					$has_location = false;
					
					if (empty($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_reference'])) {
						
						$arr_sql_location['column_ref_object_id'] = 'NULL';
						$arr_sql_location['column_ref_type_id'] = 'NULL';
						$arr_sql_location['column_ref_object_sub_details_id'] = 'NULL';
					} else {
						
						$has_location = true;
						
						if (!empty($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_reference_value'])) {
							$this->arr_type_object_name_object_sub_details_ids[$object_sub_details_id]['location'] = true;
						}
					}
					
					if (empty($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_location_geometry'])) {
						
						$arr_sql_location['column_geometry'] = 'NULL';
						$arr_sql_location['column_geometry_object_id'] = 'NULL';
						$arr_sql_location['column_geometry_type_id'] = 'NULL';
					} else {
						
						$has_location = true;
					}
					
					if ($has_location == true) {
						$this->arr_tables['select_object_subs'][$object_sub_details_id] .= $arr_sql_location['tables'];
					}
				}

				$this->arr_columns_subs_group[$object_sub_details_id][] = "object_sub_date_start, object_sub_date_end, object_sub_date_all, object_sub_date_chronology, object_sub_location_geometry, object_sub_location_geometry_ref_object_id, object_sub_location_geometry_ref_type_id, object_sub_location_ref_object_id, object_sub_location_ref_type_id, object_sub_location_ref_object_sub_details_id";

				$this->arr_columns_subs_as[$object_sub_details_id][] = "nodegoat_to.id AS object_id,
					".$table_name_tos.".id AS object_sub_id,
					".$table_name_tos.".object_sub_details_id,
					".$arr_sql_date['column_start']." AS object_sub_date_start,
					".$arr_sql_date['column_end']." AS object_sub_date_end,
					".$arr_sql_date['column_all']." AS object_sub_date_all,
					".$arr_sql_date['column_chronology']." AS object_sub_date_chronology,
					".$arr_sql_location['column_geometry']." AS object_sub_location_geometry,
					".$arr_sql_location['column_geometry_object_id']." AS object_sub_location_geometry_ref_object_id,
					".$arr_sql_location['column_geometry_type_id']." AS object_sub_location_geometry_ref_type_id,
					".$arr_sql_location['column_ref_object_id']." AS object_sub_location_ref_object_id,
					".$arr_sql_location['column_ref_type_id']." AS object_sub_location_ref_type_id,
					".$arr_sql_location['column_ref_object_sub_details_id']." AS object_sub_location_ref_object_sub_details_id,
					".(($this->view == GenerateTypeObjects::VIEW_VISUALISE || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) && $do_select_object_sub_details ? "(SELECT ".DBFunctions::sqlImplode("CONCAT(nodegoat_tos_src.ref_object_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_tos_src.ref_type_id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_tos_src.value)", static::SQL_GROUP_SEPERATOR)."
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_SOURCES')." nodegoat_tos_src
						WHERE nodegoat_tos_src.object_sub_id = ".$table_name_tos.".id
					) AS object_sub_sources" : "NULL").",
					".($do_select_object_sub_details ? "(CASE
						WHEN ".$table_name_tos.".status = 3 THEN 'deleted'
						WHEN ".$table_name_tos.".status = 1 THEN 'added'
						WHEN (".$table_name_tos.".status = 2
							OR EXISTS (SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS')." AS nodegoat_tos_def WHERE nodegoat_tos_def.object_sub_id = ".$table_name_tos.".id AND nodegoat_tos_def.active = FALSE AND nodegoat_tos_def.status > 0)
							OR EXISTS (SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." AS nodegoat_tos_def WHERE nodegoat_tos_def.object_sub_id = ".$table_name_tos.".id AND nodegoat_tos_def.active = FALSE AND nodegoat_tos_def.status > 0)
						) THEN 'changed'
						ELSE '' END
					)" : "NULL")." AS object_sub_version";
				
				$has_referenced = (isset($this->arr_type_set['type']['include_referenced']['object_sub_details']) && ($object_sub_details_id == 'all' || isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])));
				
				if ($has_referenced) {
					$this->arr_columns_subs_as[$object_sub_details_id][] = $table_name_tos.".object_id AS object_sub_object_id";
					$this->arr_columns_subs_as[$object_sub_details_id][] = "nodegoat_tos_store.referenced_object_sub_description_id AS object_sub_referenced_object_sub_description_id";
				}
				
				if ($object_sub_details_id == 'all') {
					$this->arr_columns_subs[$object_sub_details_id]['object_sub_details_name'] = "(SELECT name FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." WHERE id = ".$table_name_tos.".object_sub_details_id)";
				}
				$this->arr_columns_subs[$object_sub_details_id]['object_sub_date_start'] = StoreTypeObjects::dateSQL2Absolute($arr_sql_date['column_start']);
				$this->arr_columns_subs[$object_sub_details_id]['object_sub_date_end'] = StoreTypeObjects::dateSQL2Absolute($arr_sql_date['column_end']);
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$table_name = "nodegoat_tos_def_".$object_sub_description_id;
					
					$version_select = $this->generateVersion('record', $table_name);
					
					if (!empty($this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id])) {
						
						$this->count_object_sub_descriptions[$object_sub_details_id]++;
												
						$arr_sql_columns = $this->generateTablesColumnsObjectSubDescription($object_sub_details_id, $object_sub_description_id, $table_name_tos.'.id', $table_name_tos.'.object_id');
						
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['select_id'] = $arr_sql_columns['select_id'];
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['select_value'] = $arr_sql_columns['select_value'];
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['group'] = $arr_sql_columns['group'];
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['order'] = $arr_sql_columns['order'];
						$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['tables'] = $arr_sql_columns['tables'];
						
						if ($arr_sql_columns['column']) {
							$this->arr_columns_subs[$object_sub_details_id]['object_sub_description_'.$object_sub_description_id] = $arr_sql_columns['column'];
						}
						
						if ($this->view == GenerateTypeObjects::VIEW_VISUALISE || $this->view == GenerateTypeObjects::VIEW_ALL || $this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE) {
							$this->arr_columns_subs_descriptions[$object_sub_details_id][$object_sub_description_id]['sources'] = true;
						}
					}
				}
			}
			
			foreach ($this->arr_sql_query_object_subs_details as $object_sub_details_id => $arr_sql_query_object_sub_details) {
				
				$is_filtering_sub = $this->isFilteringObjectSubDetails($object_sub_details_id);
				$is_filtering_sub_or = ($is_filtering_sub && arrHasValuesRecursive('sql_operator_sub', 'OR', $arr_sql_query_object_sub_details));
				
				foreach ($arr_sql_query_object_sub_details as $arr_table_info) {

					$version_select = $this->generateVersion('object_sub', $arr_table_info['table_name']);
					$arr_sql_filter = array_filter($arr_table_info['arr_sql_filter']);
					$sql_filter = ($arr_sql_filter ? "AND (".implode(' OR ', $arr_sql_filter).")" : "");
					
					$table_name_tos = 'nodegoat_tos_'.$object_sub_details_id;
										
					if ($object_sub_details_id == 'all' || isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])) {
						
						// Contain referenced sub-objects; sub-object filtering only
						
						$sql_table = "";
					} else {
						
						$sql_table = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".object_id = nodegoat_to.id AND ".$arr_table_info['table_name'].".object_sub_details_id = ".$object_sub_details_id." AND ".$version_select." ".$sql_filter.")";
					}
					
					$sql_table_sub = " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$arr_table_info['table_name']." ON (".$arr_table_info['table_name'].".id = ".$table_name_tos.".id AND ".$version_select." ".$sql_filter.")";
										
					$this->arr_tables['query_object'] .= $sql_table;
					if ($arr_table_info['filter_object_subs']) {
						$this->arr_tables['query_object_subs'][$object_sub_details_id] .= $sql_table_sub;
					}
					
					if ($is_filtering_sub) { // Filter sub-objects on object level
						
						if ($is_filtering_sub_or && !$arr_table_info['optional']) { // Do not align filtering tables with the main tables as we need to check various possibilities. When table is needed for optional filtering, we can align the table, because they are not part of WHERE clause. Final alignment happens in the select clause.
							$this->arr_tables['filtering_object'] .= $sql_table;
						} else {
							$this->arr_tables['filtering_object'] .= $sql_table_sub;
						}
					} else {
						$this->arr_tables['filtering_object'] .= $sql_table;
					}
				}
			}
		}
		
		// Order based on result set
		
		$this->arr_sql_tables = [];
			
		foreach ($this->arr_order as $column_name => $value) {
			
			$value = (strtoupper($value) == 'ASC' ? 'ASC' : 'DESC');
			
			if ($column_name == 'object_name') {
				
				if ($this->arr_combine_filters['search'] || $this->arr_combine_filters['search_name']) { // Apply additional advanced sorting based on filtering
				
					if ($this->arr_columns_object['order_object_name_search']) {
					
						$arr_table_info = $this->arr_sql_filter['table']['object_name_search'];
						
						$this->arr_sql_tables[] = "LEFT JOIN ".$arr_table_info['table_name']." AS ".$arr_table_info['table_alias']." ON (".$arr_table_info['table_alias'].".id = nodegoat_to_store.id)";
						
						$this->arr_sql_order[] = implode(' '.$value.',', $this->arr_columns_object['order_object_name_search']).' '.$value;
						$this->arr_sql_group[] = implode(',', $this->arr_columns_object_group['order_object_name_search']);
					}
				
					$this->arr_sql_order[] = "CHAR_LENGTH(".$this->arr_columns_object['object_name'].") ".$value;
				}
			}
			
			if ($this->arr_columns_object[$column_name]) {
				
				$sql_column = $this->arr_columns_object[$column_name];
				
				if (is_array($sql_column)) {
					
					foreach ($sql_column as $sql) {
						
						$this->arr_sql_order[] = $sql.' '.$value;
					}
				} else {
					
					$this->arr_sql_order[] = $sql_column.' '.$value;
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
			
	public function generateTablesColumnsObjectDescription($object_description_id, $sql_table_ref = 'nodegoat_to.id', $sql_table_store_object = 'nodegoat_to', $from_object_sub_details_id = false, $arr_selection = false) {
		
		$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
		$table_name = "nodegoat_to_def";
		$table_name_affix = StoreType::getValueTypeTable($arr_object_description['object_description_value_type']);
		$version_select = $this->generateVersion('record', $table_name);
		$is_filtering = $this->isFilteringObjectDescription($object_description_id, true);
		
		$use_object_description_id = $arr_object_description['object_description_id']; // In case the object description is referenced
		
		$arr_selection = ($arr_selection ?: $this->arr_selection['object_descriptions'][$object_description_id]);
		
		$sql_select_value = '';
		$sql_select_id = '';
		$sql_group = '';
		$sql_order = '';
		$sql_column = '';
		$sql_select_tables = '';
		
		if ($arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic']) {
			
			$use_main_table = true;

			if ($arr_selection['object_description_value']) {
				$this->arr_type_object_name_object_description_ids[$object_description_id] = $object_description_id;
			}
			
			if ($arr_object_description['object_description_is_referenced']) {
				$sql_select_id = $table_name.".object_id";
				$sql_from_id = $table_name.".ref_object_id";
			} else {
				$sql_select_id = $table_name.".ref_object_id";
				$sql_from_id = $table_name.".object_id";
			}

			$sql_select_tables = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$sql_select_id." AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref').")";
			$sql_group = $sql_select_id.", nodegoat_to_ref.type_id";
			
			$sql_select_value = ($this->view == GenerateTypeObjects::VIEW_STORAGE ? '' : "CONCAT('[![', nodegoat_to_ref.type_id, '_', ".$sql_select_id.", ']!]')");
			
			if ($this->arr_order['object_description_'.$object_description_id] || $this->arr_order_object_subs_use_object_description_id[$object_description_id]) {
				
				$arr_type_object_path = StoreType::getTypeObjectPath('name', $arr_object_description['object_description_ref_type_id']);
				$sql_dynamic_type_name_column = $this->format2SQLDynamicTypeObjectNameColumn($arr_type_object_path, $sql_select_id);
				
				$sql_column = "(SELECT ".DBFunctions::sqlImplode($sql_dynamic_type_name_column['column'], ' ', ($arr_object_description['object_description_has_multi'] ? "ORDER BY ".$table_name.".identifier ASC" : false))." AS column_value
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$table_name."
						".($is_filtering && $this->arr_order_object_subs_use_object_description_id[$object_description_id] ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.id = ".$table_name.".object_id AND nodegoat_to_store.object_definition_".$object_description_id."_ref_object_id = ".$table_name.".ref_object_id)" : "")."
						".$sql_select_tables."
						".$sql_dynamic_type_name_column['tables']."
					WHERE ".$sql_from_id." = ".$sql_table_ref." AND ".$table_name.".object_description_id = ".$use_object_description_id." AND ".$version_select."
						".($is_filtering && !$this->arr_order_object_subs_use_object_description_id[$object_description_id] ? "AND nodegoat_to_def_".$object_description_id.".ref_object_id = ".$table_name.".ref_object_id" : "")."
					GROUP BY ".$table_name.".object_id
				)";
			}
		} else {

			if ($arr_object_description['object_description_ref_type_id']) { // Dynamic with referencing; value is not stored in the main value type table
				$use_main_table = false;
			} else {
				$use_main_table = true;
			}
			
			$sql_from_id = $table_name.".object_id";
			$sql_value = $table_name.".".StoreType::getValueTypeValue($arr_object_description['object_description_value_type']);
			
			if ($arr_object_description['object_description_is_dynamic']) {
				
				if ($arr_selection['object_description_reference']) {

					if ($arr_selection['object_description_reference_value']) {
						$this->arr_type_object_name_object_description_ids[$object_description_id] = $object_description_id;
					}
					
					$sql_concat = "nodegoat_to_ref.id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_to_ref.type_id, '".static::SQL_GROUP_SEPERATOR_2."', '[![', nodegoat_to_ref.type_id, '_', nodegoat_to_ref.id, ']!]'";
				
					if (!$arr_object_description['object_description_ref_type_id']) {

						$sql_concat .= ", '".static::SQL_GROUP_SEPERATOR_2."', ".$table_name."_ref.value";
					}
					
					$sql_select_id = "(SELECT ".DBFunctions::sqlImplode("CONCAT(".$sql_concat.")", static::SQL_GROUP_SEPERATOR)."
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITION_OBJECTS')." ".$table_name."_ref
							".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.id = ".$table_name."_ref.object_id AND nodegoat_to_store.object_definition_".$object_description_id."_ref_object_id = ".$table_name."_ref.ref_object_id)" : "")."
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$table_name."_ref.ref_object_id
								".(is_array($arr_selection['object_description_reference']) ? " AND nodegoat_to_ref.type_id IN (".implode(',', $arr_selection['object_description_reference']).")" : '')."
								AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref')."
							)
						WHERE ".$table_name."_ref.object_id = ".($use_main_table ? $sql_from_id : $sql_table_ref)." AND ".$table_name."_ref.object_description_id = ".$object_description_id." AND ".$table_name."_ref.state = 1
					)";
				}
			}

			if (!$arr_object_description['object_description_ref_type_id'] && $arr_selection['object_description_value']) {

				if ($arr_object_description['object_description_has_multi']) {
				
					$sql_select_id = $table_name.".identifier";
				}
				
				$sql_select_value = ($this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE ? $sql_value : StoreTypeObjects::formatFromSQLValue($arr_object_description['object_description_value_type'], $sql_value, $arr_object_description['object_description_value_type_settings'], $this->mode_format));
			}
			
			if ($this->arr_order['object_description_'.$object_description_id]) {
				
				if ($arr_object_description['object_description_ref_type_id']) {
					
					$sql_value_order = $table_name.".".StoreType::getValueTypeValue($arr_object_description['object_description_value_type'], 'name');
					$version_select_order = $this->generateVersion('name', $table_name, $arr_object_description['object_description_value_type']);
					$table_name_affix_order = StoreType::getValueTypeTable($arr_object_description['object_description_value_type'], 'name');
				} else {
					
					$sql_value_order = $sql_value;
					$version_select_order = $version_select;
					$table_name_affix_order = $table_name_affix;
				}
				
				$sql_column = "(SELECT ".($arr_object_description['object_description_has_multi'] ? DBFunctions::sqlImplode($sql_value_order, ' ', "ORDER BY ".$table_name.".identifier ASC") : $sql_value_order)." AS column_value
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table_name_affix_order." AS ".$table_name."
					WHERE ".$table_name.".object_id = ".$sql_table_ref." AND ".$table_name.".object_description_id = ".$object_description_id." AND ".$version_select_order."
						".($is_filtering && !$arr_object_description['object_description_ref_type_id'] ? "AND nodegoat_to_def_".$object_description_id.".identifier = ".$table_name.".identifier" : "")."
				)";
			}
		}
		
		if ($use_main_table) {
			
			$sql_filter_table = '';
			
			if ($is_filtering && !$arr_object_description['object_description_is_dynamic']) {
				
				if ($arr_object_description['object_description_ref_type_id']) {
					
					if ($from_object_sub_details_id) {
						
						$is_filtering_object_sub_details = $this->isFilteringObjectSubDetails($from_object_sub_details_id, true);
						
						if (!$is_filtering_object_sub_details) { // Filtering table is not available on Sub-Object level
							$sql_filter_table = "AND EXISTS (SELECT TRUE FROM ".$this->table_name." AS nodegoat_to_store WHERE nodegoat_to_store.object_definition_".$object_description_id."_ref_object_id = ".$table_name.".ref_object_id)";
						} else {
							$sql_filter_table = "AND ".$sql_table_store_object.".object_definition_".$object_description_id."_ref_object_id = ".$table_name.".ref_object_id";
						}
					} else {
						$sql_filter_table = "AND ".$sql_table_store_object.".object_definition_".$object_description_id."_ref_object_id = ".$table_name.".ref_object_id";
					}
				} else {
					$sql_filter_table = "AND ".$sql_table_store_object.".object_definition_".$object_description_id."_value = ".$table_name.".value";
				}
			}
			
			$sql_select_tables = "
				JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').$table_name_affix." AS ".$table_name." ON (".$sql_from_id." = ".$sql_table_ref." AND ".$table_name.".object_description_id = ".$use_object_description_id." AND ".$version_select." ".$sql_filter_table.")
				".$sql_select_tables."
			";
			
			$sql_group = $table_name.".object_id, ".$table_name.".object_description_id".($table_name_affix == '_references' ? ", ".$table_name.".ref_object_id" : "").", ".$table_name.".identifier, ".$table_name.".version".($sql_group ? ", ".$sql_group : "");
			$sql_order = $table_name.".identifier ASC";
		}
		
		return ['select_value' => $sql_select_value, 'select_id' => $sql_select_id, 'group' => $sql_group, 'order' => $sql_order, 'column' => $sql_column, 'tables' => $sql_select_tables];
	}
		
	public function generateTablesColumnsObjectSubDescription($object_sub_details_id, $object_sub_description_id, $sql_table_ref = 'nodegoat_tos.id', $sql_table_object_ref = 'nodegoat_tos.object_id', $sql_table_store_object = 'nodegoat_to_store') {
		
		$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		$table_name = 'nodegoat_tos_def';
		$version_select = $this->generateVersion('record', $table_name);
		$is_filtering = $this->isFilteringObjectSubDescription($object_sub_details_id, $object_sub_description_id, true);
		
		$arr_selection = $this->arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
		
		$sql_select_value = '';
		$sql_select_id = '';
		$sql_group = '';
		$sql_order = '';
		$sql_column = '';
		$sql_select_tables = '';
		
		if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {
			
			$object_sub_description_use_object_description_id = $arr_object_sub_description['object_sub_description_use_object_description_id'];
			
			if (!empty($this->arr_order_object_subs[$object_sub_details_id]['object_sub_description_'.$object_sub_description_id])) {
				$this->arr_order_object_subs_use_object_description_id[$object_sub_description_use_object_description_id] = true;
			}
			
			$arr_selection = ['object_description_value' => $arr_selection['object_sub_description_value'], 'object_description_reference' => $arr_selection['object_sub_description_reference'], 'object_description_reference_value' => $arr_selection['object_sub_description_reference_value']];
			
			$arr_sql_columns = $this->generateTablesColumnsObjectDescription($object_sub_description_use_object_description_id, $sql_table_object_ref, $sql_table_store_object, $object_sub_details_id, $arr_selection);
			
			$sql_select_id = $arr_sql_columns['select_id'];
			$sql_select_value = $arr_sql_columns['select_value'];
			$sql_group = $arr_sql_columns['group'];
			$sql_order = $arr_sql_columns['order'];
			$sql_column = $arr_sql_columns['column'];
			$sql_select_tables = $arr_sql_columns['tables'];
		} else {
			
			$table_name_affix = StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type']);
			
			if ($arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic']) {
				
				$use_main_table = true;
				
				if ($arr_selection['object_sub_description_value']) {
					$this->arr_type_object_name_object_sub_description_ids[$object_sub_details_id][$object_sub_description_id] = $object_sub_description_id;
				}
				
				$is_referenced = $arr_object_sub_description['object_sub_description_is_referenced'];
				
				if ($is_referenced) {
					$sql_select_id = $sql_table_object_ref;
				} else {
					$sql_select_id = $table_name.'.ref_object_id';
				}
				
				$sql_select_tables = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$sql_select_id." AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref').")";
				$sql_group = $sql_select_id.", nodegoat_to_ref.type_id";

				$sql_select_value = ($this->view == GenerateTypeObjects::VIEW_STORAGE ? '' : "CONCAT('[![', nodegoat_to_ref.type_id, '_', ".$sql_select_id.", ']!]')");
				
				if (!empty($this->arr_order_object_subs[$object_sub_details_id]['object_sub_description_'.$object_sub_description_id])) {
					
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
				
				if ($arr_object_sub_description['object_sub_description_ref_type_id']) { // Dynamic with referencing; value is not stored in the main value type table
					$use_main_table = false;
				} else {
					$use_main_table = true;
				}
				
				$sql_value = $table_name.".".StoreType::getValueTypeValue($arr_object_sub_description['object_sub_description_value_type']);
				
				if ($arr_object_sub_description['object_sub_description_is_dynamic']) {
					
					if ($arr_selection['object_sub_description_reference']) {

						if ($arr_selection['object_sub_description_reference_value']) {
							$this->arr_type_object_name_object_sub_description_ids[$object_sub_details_id][$object_sub_description_id] = $object_sub_description_id;
						}
						
						$sql_concat = "nodegoat_to_ref.id, '".static::SQL_GROUP_SEPERATOR_2."', nodegoat_to_ref.type_id, '".static::SQL_GROUP_SEPERATOR_2."', '[![', nodegoat_to_ref.type_id, '_', nodegoat_to_ref.id, ']!]'";
					
						if (!$arr_object_sub_description['object_sub_description_ref_type_id']) {

							$sql_concat .= ", '".static::SQL_GROUP_SEPERATOR_2."', ".$table_name."_ref.value";
						}
						
						$sql_select_id = "(SELECT ".DBFunctions::sqlImplode("CONCAT(".$sql_concat.")", static::SQL_GROUP_SEPERATOR)."
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITION_OBJECTS')." ".$table_name."_ref
								".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.object_sub_".$object_sub_details_id."_id = ".$table_name."_ref.object_sub_id AND nodegoat_to_store.object_sub_definition_".$object_sub_description_id."_ref_object_id = ".$table_name."_ref.ref_object_id)" : "")."
								JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to_ref ON (nodegoat_to_ref.id = ".$table_name."_ref.ref_object_id
									".(is_array($arr_selection['object_sub_description_reference']) ? "AND nodegoat_to_ref.type_id IN (".implode(',', $arr_selection['object_sub_description_reference']).")" : '')."
									AND ".self::generateVersioning('added', 'object', 'nodegoat_to_ref')."
								)
							WHERE ".$table_name."_ref.object_sub_id = ".$sql_table_ref." AND ".$table_name."_ref.object_sub_description_id = ".$object_sub_description_id." AND ".$table_name."_ref.state = 1
						)";
					}
				}

				if (!$arr_object_sub_description['object_sub_description_ref_type_id'] && $arr_selection['object_sub_description_value']) {
						
					if ($arr_object_sub_description['object_sub_description_has_multi']) {
						
						$sql_select_id = $table_name.".identifier";
					}
					
					$sql_select_value = ($this->view == GenerateTypeObjects::VIEW_SET || $this->view == GenerateTypeObjects::VIEW_STORAGE ? $sql_value : StoreTypeObjects::formatFromSQLValue($arr_object_sub_description['object_sub_description_value_type'], $sql_value, $arr_object_sub_description['object_sub_description_value_type_settings'], $this->mode_format));			
				}
				
				if (!empty($this->arr_order_object_subs[$object_sub_details_id]['object_sub_description_'.$object_sub_description_id])) {
					
					if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
						
						$sql_value_order = $table_name.".".StoreType::getValueTypeValue($arr_object_sub_description['object_sub_description_value_type'], 'name');
						$version_select_order = $this->generateVersion('name', $table_name, $arr_object_sub_description['object_sub_description_value_type']);
						$table_name_affix_order = StoreType::getValueTypeTable($arr_object_sub_description['object_sub_description_value_type'], 'name');
					} else {
						
						$sql_value_order = $sql_value;
						$version_select_order = $version_select;
						$table_name_affix_order = $table_name_affix;
					}
					
					$sql_column = "(SELECT ".($arr_object_description['object_sub_description_has_multi'] ? DBFunctions::sqlImplode($sql_value_order, ' ') : $sql_value_order)." AS column_value
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table_name_affix_order." AS ".$table_name."
							".($is_filtering ? "JOIN ".$this->table_name." AS nodegoat_to_store ON (nodegoat_to_store.object_sub_".$object_sub_details_id."_id = ".$table_name.".object_sub_id AND nodegoat_to_store.object_sub_definition_".$object_sub_description_id."_value = ".$table_name.".value)" : "")."
						WHERE ".$table_name.".object_sub_id = ".$sql_table_ref." AND ".$table_name.".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select_order."
					)";
				}
			}
			
			if ($use_main_table) {
				
				$sql_filter_table = '';
				if ($is_filtering) {
					if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
						$sql_filter_table = "AND ".$sql_table_store_object.".object_sub_definition_".$object_sub_description_id."_ref_object_id = ".$table_name.".ref_object_id";
					} else {
						$sql_filter_table = "AND ".$sql_table_store_object.".object_sub_definition_".$object_sub_description_id."_value = ".$table_name.".value";
					}
				}
		
				$sql_select_tables = "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').$table_name_affix." AS ".$table_name." ON (".$table_name.".object_sub_id = ".$sql_table_ref." AND ".$table_name.".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select." ".$sql_filter_table.")
					".$sql_select_tables."
				";
				
				$sql_group = "nodegoat_tos_def.object_sub_id, nodegoat_tos_def.object_sub_description_id, nodegoat_tos_def.version".($sql_group ? ", ".$sql_group : "");
			}
		}
		
		return ['select_value' => $sql_select_value, 'select_id' => $sql_select_id, 'group' => $sql_group, 'order' => $sql_order, 'column' => $sql_column, 'tables' => $sql_select_tables];			
	}
	
	protected function cacheTypeObjects() {
		
		$identifier_cache = $this->arr_scope['types'];
		
		if (self::$identifier_cached_object_subs && self::$identifier_cached_object_subs == $identifier_cache) {
			return self::$is_cached_object_subs;
		}
		
		self::$identifier_cached_object_subs = $identifier_cache;
		
		$module = 'cms_nodegoat_definitions';
		$method = 'runTypeObjectCaching';
		
		$arr_job = cms_jobs::getJob($module, $method);
		
		if ($arr_job['date_executed']['previous']) { // Check if there is job present to keep track of caching
			
			if ($this->arr_scope['types']) { // Only do domain-based caching when a scope is supplied
				
				$date_updated = FilterTypeObjects::getTypesUpdatedAfter($arr_job['date_executed']['previous'], $this->arr_scope['types'], 'last');
				
				if ($date_updated) {
					
					status(getLabel('msg_building_cache_object'), false, getLabel('msg_wait'), ['identifier' => SiteStartVars::getSessionId(true).'cache_object', 'duration' => 1000, 'persist' => true]);

					cms_jobs::runJob($module, $method, $date_updated);
					
					clearStatus(SiteStartVars::getSessionId(true).'cache_object');
				}
			}
			
			self::$is_cached_object_subs = true;
			
			return true;
		}
		
		self::$is_cached_object_subs = false;
		
		return false;
	}
	
	protected function generateTablesColumnsObjectSubDate($table_name, $include_secondary = false, $include_source = false, $do_cache = true) {
		
		if ($do_cache) {
			$do_cache = $this->cacheTypeObjects();
		}
				
		return self::generateTablesColumnsObjectSubDating($table_name, $include_secondary, $include_source, $do_cache);
	}
	
	protected static function generateTablesColumnsObjectSubDating($table_name, $include_secondary = false, $include_source = false, $do_cache = true) {

		$table_name_date = $table_name.'_date';

		if ($do_cache) {
			
			$arr = self::format2SQLObjectSubDateCached($table_name, $include_source);
		} else {
			
			$arr = self::format2SQLObjectSubDateBase($table_name);
			
			$arr_date = self::format2SQLObjectSubDate($table_name.'_sel', StoreType::DATE_START_START, 3);
			
			$arr['column_start'] = "(SELECT ".$arr_date['column_date']."
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_sel
				".$arr_date['tables']."
				WHERE ".$table_name."_sel.id = ".$table_name.".id AND ".$table_name."_sel.version = ".$table_name.".version
			)";

			$arr_date = self::format2SQLObjectSubDate($table_name.'_sel', StoreType::DATE_END_END, 3);
			
			$arr['column_end'] = "(SELECT ".$arr_date['column_date']."
				FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_sel
				".$arr_date['tables']."
				WHERE ".$table_name."_sel.id = ".$table_name.".id AND ".$table_name."_sel.version = ".$table_name.".version
			)";
			
			if ($include_secondary) {
				
				$arr_date = self::format2SQLObjectSubDate($table_name.'_sel', StoreType::DATE_START_END, 3);
			
				$arr['column_start_end'] = "(SELECT ".$arr_date['column_date']."
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_sel
					".$arr_date['tables']."
					WHERE ".$table_name."_sel.id = ".$table_name.".id AND ".$table_name."_sel.version = ".$table_name.".version
				)";
				
				$arr_date = self::format2SQLObjectSubDate($table_name.'_sel', StoreType::DATE_END_START, 3);
			
				$arr['column_end_start'] = "(SELECT ".$arr_date['column_date']."
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_sel
					".$arr_date['tables']."
					WHERE ".$table_name."_sel.id = ".$table_name.".id AND ".$table_name."_sel.version = ".$table_name.".version
				)";
			}
		}

		if (!$include_secondary) {
			
			$arr['column_start_end'] = 'NULL';
			$arr['column_end_start'] = 'NULL';
		}
		
		$arr['table_name_date'] = $table_name_date;
		$arr['table_name_cache'] = DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE');
		$arr['table_cache_state'] = $arr['table_name_cache'].".state = 0";
		
		return $arr;
	}
	
	protected function generateTablesColumnsObjectSubLocationReference($table_name, $do_reference_only = false, $do_readable = false, $do_cache = true) {
		
		if ($do_cache) {
			$do_cache = $this->cacheTypeObjects();
		}
				
		return self::generateTablesColumnsObjectSubLocationReferencing($table_name, $do_reference_only, $do_readable, $do_cache);
	}

	protected static function generateTablesColumnsObjectSubLocationReferencing($table_name, $do_reference_only, $do_readable, $do_cache) {
		
		$table_name_loc = $table_name."_loc";

		if ($do_cache) {
			
			$arr = [];

			$arr['column_ref_object_id'] = $table_name_loc.'_cache.ref_object_id';
			$arr['column_ref_type_id'] = $table_name_loc.'_cache.ref_type_id';
			$arr['column_ref_object_sub_details_id'] = $table_name_loc.'_cache.ref_object_sub_details_id';
			
			$arr['column_ref_show_object_id'] = $table_name_loc.'_cache.ref_object_id';
			$arr['column_ref_show_type_id'] = $table_name_loc.'_cache.ref_type_id';
			$arr['column_ref_show_object_sub_details_id'] = $table_name_loc.'_cache.ref_object_sub_details_id';
			
			$arr['column_object_sub_details_id'] = $table_name_loc.'_cache.object_sub_details_id';
			
			$sql_tables = "
				LEFT JOIN ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION')." ".$table_name_loc."_cache ON (".$table_name_loc."_cache.object_sub_id = ".$table_name.".id AND ".$table_name_loc."_cache.active = ".$table_name.".active AND ".$table_name_loc."_cache.status = ".$table_name.".status AND ".$table_name_loc."_cache.state = 0)
			";
			$arr['tables'] = $sql_tables;
			
			if (!$do_reference_only) {
				
				$sql_tables .= "
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_loc." ON (".$table_name_loc.".id = ".$table_name_loc."_cache.geometry_object_sub_id AND ".self::generateVersioning('added', 'object_sub', $table_name_loc).")
				";
								
				$arr['column_geometry'] = ($do_readable ? StoreTypeObjects::formatFromSQLValue('geometry', $table_name_loc.'_geo.geometry') : $table_name_loc.'_geo.geometry');
				$arr['column_geometry_object_sub_id'] = $table_name_loc.'.id';
				$arr['column_geometry_object_id'] = $table_name_loc.'_cache.geometry_object_id';
				$arr['column_geometry_type_id'] = $table_name_loc.'_cache.geometry_type_id';
				
				$arr['tables'] = $sql_tables."
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_loc."_geo ON (".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = ".$table_name_loc.".location_geometry_version)
				";
				
				$sql_geometry_translate = "(
					SELECT
						geometry
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_loc."_geo
					WHERE
						(".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = ".$table_name_loc.".location_geometry_version)
						OR (".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = (".StoreTypeObjects::VERSION_OFFSET_ALTERNATE." - ".$table_name_loc.".location_geometry_version))
					ORDER BY ".$table_name_loc."_geo.version ASC
					LIMIT 1
				)";
				
				$arr['column_geometry_translate'] = ($do_readable ? StoreTypeObjects::formatFromSQLValue('geometry', $sql_geometry_translate, ['srid' => false]) : $sql_geometry_translate);
				
				$arr['tables_translate'] = $sql_tables;
			}
		} else {

			$arr = self::format2SQLObjectSubLocationReference($table_name, ($do_reference_only ? 1 : 3));
						
			if (!$do_reference_only) {
				
				$sql_tables = $arr['tables'];
				$sql_tables .= " 
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name_loc." ON (".$table_name_loc.".id = ".$arr['column_geometry_object_sub_id']." AND ".self::generateVersioning('added', 'object_sub', $table_name_loc).")
				";
				
				$sql_geometry = ($do_readable ? StoreTypeObjects::formatFromSQLValue('geometry', 'geometry') : 'geometry');
				
				$arr['column_geometry'] = "CASE
					WHEN ".$table_name_loc.".id IS NOT NULL THEN (SELECT ".$sql_geometry." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_loc."_geo WHERE ".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = ".$table_name_loc.".location_geometry_version)
					WHEN ".$table_name.".location_geometry_version IS NOT NULL THEN (SELECT ".$sql_geometry." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name."_geo WHERE ".$table_name."_geo.object_sub_id = ".$table_name.".id AND ".$table_name."_geo.version = ".$table_name.".location_geometry_version)
					ELSE NULL
				END";
				
				$sql_geometry = ($do_readable ? StoreTypeObjects::formatFromSQLValue('geometry', 'geometry', ['srid' => false]) : 'geometry');
				
				$arr['column_geometry_translate'] = "CASE
					WHEN ".$table_name_loc.".id IS NOT NULL THEN (SELECT ".$sql_geometry." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name_loc."_geo
						WHERE
							(".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = ".$table_name_loc.".location_geometry_version)
							OR (".$table_name_loc."_geo.object_sub_id = ".$table_name_loc.".id AND ".$table_name_loc."_geo.version = (".StoreTypeObjects::VERSION_OFFSET_ALTERNATE." - ".$table_name_loc.".location_geometry_version))
						ORDER BY ".$table_name_loc."_geo.version ASC
						LIMIT 1
					)
					WHEN ".$table_name.".location_geometry_version IS NOT NULL THEN (SELECT ".$sql_geometry." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_LOCATION_GEOMETRY')." ".$table_name."_geo
						WHERE
							(".$table_name."_geo.object_sub_id = ".$table_name.".id AND ".$table_name."_geo.version = ".$table_name.".location_geometry_version)
							OR (".$table_name."_geo.object_sub_id = ".$table_name.".id AND ".$table_name."_geo.version = (".StoreTypeObjects::VERSION_OFFSET_ALTERNATE." - ".$table_name.".location_geometry_version))
						ORDER BY ".$table_name."_geo.version ASC
						LIMIT 1
					)
					ELSE ''
				END";

				$arr['tables'] = $sql_tables;
				$arr['tables_translate'] = $sql_tables;
			}
		}
		
		$arr['table_name_location'] = $table_name_loc;
		$arr['table_name_cache'] = DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_LOCATION');
		$arr['table_cache_state'] = $arr['table_name_cache'].".state = 0";
		
		return $arr;
	}
	
	protected function generateVersion($type, $table_name, $data_type = false) {
		
		return self::generateVersioning($this->versioning, $type, $table_name, $data_type);
	}
	
	public static function generateVersioning($versioning, $type, $table_name, $data_type = false) {
		
		$sql = false;
		
		switch ($type) {
			case 'object':
				if ($versioning == 'full') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status > 0) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,3)))"; // New, changed or deleted object, or current object
				} else if ($versioning == 'added') {	
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status IN (1,3)) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,2,3)))"; // Object is new or new object is deleted, current object
				} else if ($versioning == 'any') {
					$sql = "(".$table_name.".active = TRUE OR ".$table_name.".status > 0)"; // Any object with a version
				} else {
					$sql = $table_name.".active = TRUE"; // Current object
				}
				break;
			case 'object_sub':
				if ($versioning == 'full') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status > 0) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,3)))"; // New, changed or deleted sub-object, current sub-object
				} else if ($versioning == 'added') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status = 1) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,2,3)))"; // Sub-object is new, current sub-object
				} else if ($versioning == 'any') {
					$sql = "(".$table_name.".active = TRUE OR ".$table_name.".status > 0)"; // Any sub-object with a version
				} else {
					$sql = $table_name.".active = TRUE"; // Current sub-object
				}
				break;
			case 'record':
				switch ($data_type) {
					case 'text_layout':
					case 'text_tags':
					case 'reversed_collection':
						$sql = "(".$table_name.".active = FALSE AND ".$table_name.".version = ".StoreTypeObjects::VERSION_OFFSET_ALTERNATE_ACTIVE.")";
						break;
				}
				if ($sql) {
					break;
				}
				if ($versioning == 'full') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status IN (1,2)) OR (".$table_name.".active = TRUE AND ".$table_name.".status = 0))"; // New or changed record, current record, no deleted record
				} else if ($versioning == 'added') {
					$sql = "((".$table_name.".active = FALSE AND ".$table_name.".status = 1) OR (".$table_name.".active = TRUE AND ".$table_name.".status IN (0,2,3)))"; // Record is new, current record
				} else if ($versioning == 'any') {
					$sql = "(".$table_name.".active = TRUE OR ".$table_name.".status > 0)"; // Any record with a version
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
						$sql = "(".$table_name.".active = FALSE AND ".$table_name.".version = ".StoreTypeObjects::VERSION_OFFSET_ALTERNATE_ACTIVE.")";
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
			
			if (isset($this->arr_type_set['type']['include_referenced']['object_sub_details'])) {
			
				foreach ($this->arr_type_set['type']['include_referenced']['object_sub_details'] as $cur_object_sub_details_id => $arr_object_sub_description_ids) {
										
					$arr += $arr_object_sub_description_ids;
				}
			}
		} else if (isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])) {
			
			$arr = $this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id];
		}
		
		return $arr;
	}
	
	protected function addTypeFound($type, $type_id) {
		
		if (!$type_id) {
			return;
		}
		
		$this->arr_types_found[$type][$type_id] = $type_id;
	}
	
	public function getResultInfo($arr_options = [], $str_identifier = false) {
		
		$arr_options = ($arr_options ?: []);
		$include_objects = (bool)$arr_options['objects'];
		$include_statistics = (bool)$arr_options['statistics'];
		$version_select = $this->generateVersion('object', 'nodegoat_to');
		
		$str_identifier = 'type_filter_result_'.$this->type_id.($str_identifier ? '_'.$str_identifier : '');		
		$arr_cache_all = SiteStartVars::getFeedback($str_identifier);
		
		$str_identifier_value = 'object_'.$this->versioning;
		$arr_cache = ($arr_cache_all[$str_identifier_value] ?? []);
			
		if (!$arr_cache['total']) {
			
			$sql_select = 'COUNT(nodegoat_to.id)';
			
			if ($include_statistics) {
				
				$sql_select .= ",
					COUNT(CASE WHEN nodegoat_to.active = TRUE THEN 1 ELSE NULL END) AS 'active',
					COUNT(CASE WHEN nodegoat_to.status = 1 THEN 1 ELSE NULL END) AS 'added',
					COUNT(CASE WHEN nodegoat_to.status = 3 THEN 1 ELSE NULL END) AS 'deleted'
				";
			}
			
			// Total data set length
			$res = DB::query("SELECT ".$sql_select."
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				WHERE nodegoat_to.type_id = ".$this->type_id
					
					." AND ".$version_select
			);
				
			$arr_row = $res->fetchRow();
			
			$arr_cache['total'] = $arr_row[0];
			
			if ($include_statistics) {
				
				$arr_cache['statistics'] = [
					'active' => $arr_row[1],
					'added' => $arr_row[2],
					'deleted' => $arr_row[3]
				];
			}
		}
		
		if ($this->arr_combine_filters || $include_objects) {
			
			$str_filter = ($this->arr_combine_filters ? value2Hash($this->arr_combine_filters) : '');
			$str_cache_filter = ($arr_cache['query']['value'] ?? '');
			
			if ($str_cache_filter != $str_filter || $include_objects) {
				
				// Data set length after filtering
				
				if ($this->table_name && !$this->arr_limit) {
					
					$sql_select = ($include_objects ? 'nodegoat_to_to.id' : 'COUNT(nodegoat_to_to.id)');
					
					if ($include_statistics) {
						
						if ($include_objects) {
							
							$sql_select .= ", CASE
								WHEN nodegoat_to.active = TRUE THEN 'active'
								WHEN nodegoat_to.status = 1 THEN 'added'
								WHEN nodegoat_to.status = 3 THEN 'deleted'
							END";
						} else {
							
							$sql_select .= ",
								COUNT(CASE WHEN nodegoat_to.active = TRUE THEN 1 ELSE NULL END) AS 'active',
								COUNT(CASE WHEN nodegoat_to.status = 1 THEN 1 ELSE NULL END) AS 'added',
								COUNT(CASE WHEN nodegoat_to.status = 3 THEN 1 ELSE NULL END) AS 'deleted'
							";
						}
					}
					
					$res = DB::query("SELECT
						".$sql_select."
							FROM ".$this->table_name_objects." AS nodegoat_to_to
							".($include_statistics ? "JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to ON (nodegoat_to.id = nodegoat_to_to.id AND ".$version_select.")" : "")."
					");
				} else {
					
					if ($include_objects) {
						$sql_query = $this->sqlQuery(($include_statistics ? 'object_ids_all_statistics' : 'object_ids_all'));
					} else {
						$sql_query = $this->sqlQuery(($include_statistics ? 'count_statistics' : 'count'));
					}
					
					$this->initPre();
					
					$res = DB::query($sql_query);
				}
				
				$num_total = 0;
				$num_active = $num_added = $num_deleted = 0;
				
				if ($include_objects) {

					$arr_objects = [];
					
					while ($arr_row = $res->fetchRow()) {
						
						$arr_objects[] = $arr_row[0];
						$num_total++;
						
						if ($include_statistics) {
							
							switch ($arr_row[1]) {
								case 'active':
									$num_active++;
									break;
								case 'added':
									$num_added++;
									break;
								case 'deleted':
									$num_deleted++;
									break;
							}
						}
					}
				} else {
				
					$arr_row = $res->fetchRow();
					$num_total = $arr_row[0];
					
					if ($include_statistics) {
						
						$num_active = $arr_row[1];
						$num_added = $arr_row[2];
						$num_deleted = $arr_row[3];
					}
				}
				
				$arr_cache['query']['total'] = $num_total;
				$arr_cache['query']['value'] = $str_filter;
				
				if ($include_statistics) {
					
					$arr_cache['query']['statistics'] = [
						'active' => $num_active,
						'added' => $num_added,
						'deleted' => $num_deleted
					];
				}
			}
		} else {
			
			$arr_cache['query']['total'] = $arr_cache['total'];
			$arr_cache['query']['value'] = false;
			
			if ($include_statistics) {
				$arr_cache['query']['statistics'] = $arr_cache['statistics'];
			}
		}
		
		$arr_cache_all[$str_identifier_value] = $arr_cache;
		SiteEndVars::setFeedback($str_identifier, $arr_cache_all, true);
		
		$arr_info = [
			'total' => $arr_cache['total'],
			'total_filtered' => $arr_cache['query']['total']
		];
		
		if ($include_statistics) {
			$arr_info['statistics'] = $arr_cache['statistics'];
			$arr_info['statistics_filtered'] = $arr_cache['query']['statistics'];
		}
		if ($include_objects) {
			$arr_info['objects'] = $arr_objects;
		}
		
		return $arr_info;
	}
	
	public function getResultInfoObjectSubs($object_id, $object_sub_details_id, $str_identifier = false) {

		$str_identifier = 'type_filter_result_'.$this->type_id.($str_identifier ? '_'.$str_identifier : '');		
		$arr_cache_all = SiteStartVars::getFeedback($str_identifier);
		
		$object_id = (is_array($object_id) ? serialize($object_id) : $object_id); // Possibility to get combined object results
		
		$str_identifier_value = 'object_sub_'.$this->versioning.'_'.$object_id.'_'.$object_sub_details_id;
		$arr_cache = ($arr_cache_all[$str_identifier_value] ?? []);
		
		if (!$arr_cache['total']) {
			
			$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');
			
			$sql = false;
			$sql_referenced = false;
			
			if ($object_sub_details_id == 'all' || !isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id])) {
			
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
			
			$arr_cache['total'] = 0;

			// Total data set length
			foreach ($arr_res as $res) {
				
				$arr_row = $res->fetchRow();
			
				$arr_cache['total'] += $arr_row[0];
			}
		}
		
		if ($this->arr_combine_filters['object_subs'] || $this->arr_sql_filter_subs[$object_sub_details_id]) {
			
			$contains_referenced = $this->arr_type_set['type']['include_referenced']['object_sub_details'];
			
			$str_filter = value2Hash($this->arr_combine_filters);
			
			if ($arr_cache['query']['value'] != $str_filter) {
				
				$is_referenced = ($contains_referenced && isset($this->arr_type_set['type']['include_referenced']['object_sub_details'][$object_sub_details_id]));
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
				
				$arr_cache['query']['total'] = $total;
				$arr_cache['query']['value'] = $str_filter;
			}
		} else {
			
			$arr_cache['query']['total'] = $arr_cache['total'];
			$arr_cache['query']['value'] = false;
		}
		
		$arr_cache_all[$str_identifier_value] = $arr_cache;
		SiteEndVars::setFeedback($str_identifier, $arr_cache_all, true);
		
		return [
			'total' => $arr_cache['total'],
			'total_filtered' => (isset($arr_cache['query']['total']) ? $arr_cache['query']['total'] : $arr_cache['total'])
		];
	}
	
	public function clearResultInfo($str_identifier = false) {
		
		$str_identifier = 'type_filter_result_'.$this->type_id.($str_identifier ? '_'.$str_identifier : '');		
		
		SiteEndVars::setFeedback($str_identifier, null, true);
	}
	
	public function getProcessedResultInfo() {
		
		return [
			'types_found' => $this->arr_types_found
		];
	}
	
	public function getInfoObjectSubs() {
		
		$version_select_tos = $this->generateVersion('object_sub', 'nodegoat_tos');

		$sql = "SELECT nodegoat_to.id, nodegoat_tos.object_sub_details_id, 0, COUNT(nodegoat_tos.id)
			FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." nodegoat_tos
			JOIN ".$this->table_name_objects." nodegoat_to ON (nodegoat_to.id = nodegoat_tos.object_id)
			WHERE ".$version_select_tos."
			GROUP BY nodegoat_to.id, nodegoat_tos.object_sub_details_id
		";
		
		$sql_referenced = false;
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
				$arr[$arr_row[0]][($arr_row[2] ? $arr_row[1].static::REFERENCED_ID_MODIFIER.$arr_row[2] : $arr_row[1])] = $arr_row[3];
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
			
			$this->table_name_all = DB::getTableTemporary(DATABASE_NODEGOAT_TEMP.'.temp_type_object_filtering_'.value2Hash(SiteStartVars::getSessionId(true).($to ? $to : '')));
			
			if ($this->arr_columns_object_conditions) {
				$this->table_name_all_conditions = DB::getTableTemporary($this->table_name_all.'_co');
			}
			
			$this->table_name = $this->table_name_all;
			$this->table_name_objects = $this->table_name_all;
		}
	}
	
	public function generateTemporaryTableName($name, $unique = false) {
		
		return DBFunctions::str2Name($name).'_'.value2Hash($this->getDifferentiationIdentifier().($unique ?: uniqid()));
	}
	
	public function setDifferentiationIdentifier($id) {
	
		$this->differentiation_id = $id;
	}
	
	protected function getDifferentiationIdentifier() {
	
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
			
			// Prepare queries

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
			
			if ($this->table_name_all_conditions) {
					
				foreach ($this->arr_columns_object_conditions as $arr_sql_condition) {
					
					$sql_condition = str_replace('[X]', $this->table_name_objects, $arr_sql_condition['sql']);
					
					$this->arr_sql_conditions[] = "INSERT INTO ".$this->table_name_all_conditions."
						(id, condition_match, condition_key)
						".$sql_condition."
					";
				}
			}
			
			// Run all
				
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
		
		if ($this->table_name_all_conditions) {

			DB::queryMulti("				
				CREATE ".(self::$keep_tables ? '' : 'TEMPORARY')." TABLE IF NOT EXISTS ".$this->table_name_all_conditions." (
					id INT,
					condition_match INT,
					condition_key SMALLINT,
					".(DB::ENGINE_IS_MYSQL ? "PRIMARY KEY USING BTREE (id, condition_match, condition_key)" : "PRIMARY KEY (id, condition_match, condition_key)")."
				) ".DBFunctions::sqlTableOptions(DBFunctions::TABLE_OPTION_MEMORY).";
				
				TRUNCATE TABLE ".$this->table_name_all_conditions.";
				
				".implode(';', $this->arr_sql_conditions).";
			");
		}
	}
	
	public function setDepth(&$arr_depth) {
		
		$this->arr_depth =& $arr_depth;
	}
	
	public function addDepth($arr_filter) {
		
		$arr_filter = arrKsortRecursive($arr_filter);
		$hash = value2Hash($arr_filter);
		
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
		
		$arr_style_object = false;
		
		foreach ($this->arr_object_conditions_name as $target => $arr_condition_actions) {
			
			$str_open_regex = $str_close_regex = $str_open_limit = $str_close_limit = $str_spacing = $str_before = $str_after = $str_prefix = $str_affix = '';
			$arr_style = [];
			$arr_style_key_value = [];
			
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
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$arr_style_key_value['background_color'] = $arr_action['color'];
							}
							$arr_style['background_color'] = 'background-color: '.$arr_action['color'].';';
							break;
						case 'text_emphasis':
							foreach ($arr_action['emphasis'] as $value) {
								switch ($value) {
									case 'bold':
										if ($this->conditions == static::CONDITIONS_MODE_FULL) {
											$arr_style_key_value['font_weight'] = 'bold';
										}
										$arr_style['font_weight'] = 'font-weight: bold;';
										break;
									case 'italic':
										if ($this->conditions == static::CONDITIONS_MODE_FULL) {
											$arr_style_key_value['font_style'] = 'italic';
										}
										$arr_style['font_style'] = 'font-style: italic;';
										break;
									case 'strikethrough':
										if ($this->conditions == static::CONDITIONS_MODE_FULL) {
											$arr_style_key_value['text_decoration'] = 'line-through';
										}
										$arr_style['text_decoration'] = 'text-decoration: line-through;';
										break;
								}
							}
							break;
						case 'text_color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$arr_style_key_value['text_color'] = $arr_action['color'];
							}
							$arr_style['text_color'] = 'color: '.$arr_action['color'].';';
							break;
						case 'limit_text':
							$arr_tag = Response::addParsePost(false, ['limit' => $arr_action['number'], 'affix' => $arr_action['value']]);
							$str_open_limit = $str_open_limit.$arr_tag['open'];
							$str_close_limit = $arr_tag['close'].$str_close_limit;
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
						case 'regex_replace':
							$arr_tag = Response::addParsePost(false, ['regex' => $arr_action['regex']]);
							$str_open_regex = ($arr_action['check'] ? '' : $str_open_regex).$arr_tag['open']; // 'check' means override previous regex
							$str_close_regex = $arr_tag['close'].($arr_action['check'] ? '' : $str_close_regex);
							break;
					}
				}
			}
			
			$str_open = $str_open_regex.$str_open_limit;
			$str_close = $str_close_limit.$str_close_regex;
			
			if ($target == 'object') {
				
				if ($this->conditions == static::CONDITIONS_MODE_STYLE_INCLUDE && $arr_style) {
					$str_open = '<span style="'.implode('', $arr_style).'">'.$str_open;
					$str_close = $str_close.'</span>';
				} else if ($this->conditions == static::CONDITIONS_MODE_STYLE) {
					$arr_style_object = implode('', $arr_style);
				} else if ($this->conditions == static::CONDITIONS_MODE_FULL) {
					$str_open = '<span style="'.implode('', $arr_style).'">'.$str_open;
					$str_close = $str_close.'</span>';
					$arr_style_object = $arr_style_key_value;
				}
				$str_object_name = $str_open.$str_prefix.$str_self.$str_affix.$str_close;
			} else {
				
				if (($this->conditions == static::CONDITIONS_MODE_STYLE || $this->conditions == static::CONDITIONS_MODE_STYLE_INCLUDE || $this->conditions == static::CONDITIONS_MODE_FULL) && $arr_style) {
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
			$str_open = $str_close = '';
						
			foreach ($arr_condition_actions as $arr_actions) {
				
				$remove = false;
				
				foreach ($arr_actions as $action => $arr_action) {
					
					switch ($action) {
						case 'color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$str_color = $arr_action['color'];
								if (!$arr_action['check']) {
									$arr_style['color'] = $str_color;
								} else {
									if (!isset($arr_style['color'])) {
										$arr_style['color'] = [];
									} else {
										$arr_style['color'] = (array)$arr_style['color'];
									}
									$arr_style['color'][] = $str_color;
								}
							}
							break;
						case 'background_color':
							$arr_style['background_color'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? $arr_action['color'] : 'background-color: '.$arr_action['color'].';');
							break;
						case 'text_emphasis':
							foreach ($arr_action['emphasis'] as $value) {
								switch ($value) {
									case 'bold':
										$arr_style['font_weight'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? 'bold' : 'font-weight: bold;');
										break;
									case 'italic':
										$arr_style['font_style'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? 'italic' : 'font-style: italic;');
										break;
									case 'strikethrough':
										$arr_style['text_decoration'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? 'line-through' : 'text-decoration: line-through;');
										break;
								}
							}
							break;
						case 'text_color':
							$arr_style['text_color'] = ($this->conditions == static::CONDITIONS_MODE_FULL ? $arr_action['color'] : 'color: '.$arr_action['color'].';');
							break;
						case 'regex_replace':
							$arr_tag = Response::addParsePost(false, ['regex' => $arr_action['regex']]);
							$str_open = ($arr_action['check'] ? '' : $str_open).$arr_tag['open']; // 'check' means override previous regex
							$str_close = $arr_tag['close'].($arr_action['check'] ? '' : $str_close);
							break;
						case 'weight':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$num_amount = (float)$arr_action['number'];
								if (isset($arr_action['object_description_value'])) {
									if ($arr_action['object_description_value'] == 0 || (isset($arr_action['number']) && $num_amount == 0)) { // Do not apply if weight calculation would end up 0
										break;
									}
									$num_amount = (($num_amount ?: 1) * $arr_action['object_description_value']);
								}
								if (!$arr_action['check']) {
									$arr_style['weight'] = $num_amount;
								} else {
									if (!isset($arr_style['weight'])) {
										$arr_style['weight'] = [];
									} else {
										$arr_style['weight'] = (array)$arr_style['weight'];
									}
									$arr_style['weight'][] = $num_amount;
								}
							}
							break;
						case 'remove':
							$remove = true;
							break;
						case 'geometry_color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								if ($arr_action['color']) {
									$arr_style['geometry_color'] = $arr_action['color'];
								}
								if (isset($arr_action['opacity'])) {
									$arr_style['geometry_opacity'] = $arr_action['opacity'];
								}
							}
							break;
						case 'geometry_stroke_color':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								if ($arr_action['color']) {
									$arr_style['geometry_stroke_color'] = $arr_action['color'];
								}
								if (isset($arr_action['opacity'])) {
									$arr_style['geometry_stroke_opacity'] = $arr_action['opacity'];
								}
							}
							break;
						case 'icon':
							if ($this->conditions == static::CONDITIONS_MODE_FULL) {
								$str_url = ($arr_action['image'] ? '/'.DIR_CUSTOM_PROJECT_WORKSPACE.$arr_action['image'] : '');
								if (!$arr_action['check']) {
									$arr_style['icon'] = $str_url;
								} else {
									if (!isset($arr_style['icon'])) {
										$arr_style['icon'] = [];
									} else {
										$arr_style['icon'] = (array)$arr_style['icon'];
									}
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
				if ($this->conditions == static::CONDITIONS_MODE_STYLE || $this->conditions == static::CONDITIONS_MODE_STYLE_INCLUDE) {
					$style = implode('', $arr_style);
				} else if ($this->conditions == static::CONDITIONS_MODE_FULL) {
					$style = $arr_style;
				}
			}
			
			if ($target == 'object') {
				$arr['object']['object_style'] = $style;
			} else if ($target == 'object_sub') {
				$arr['object_sub']['object_sub_style'] = $style;
			} else if ($arr['object_sub_definitions']) {
				$arr['object_sub_definitions'][$target]['object_sub_definition_style'] = $style;
				if ($str_open !== '') {
					$arr['object_sub_definitions'][$target]['processing'] = [$str_open, $str_close];
				}
			} else {
				$arr['object_definitions'][$target]['object_definition_style'] = $style;
				if ($str_open !== '') {
					$arr['object_definitions'][$target]['processing'] = [$str_open, $str_close];
				}
			}
		}
	}
	
	public function setConditions($conditions = true, $arr_conditions = []) {
		
		// $conditions = true/"text" (only text conditions), "style/style_include", "visual"
		
		$conditions = ($conditions === true ? GenerateTypeObjects::CONDITIONS_MODE_TEXT : $conditions);
		
		$this->conditions = $conditions;
		$this->arr_type_set_conditions = $arr_conditions;
	}
	
	public function setVersioning($version = 'full') {
		
		// $version = 'full', 'added', 'active'
				
		$this->versioning = $version;
	}
	
	public function getVersioning() {
		
		return $this->versioning;
	}
	
	public function setScope($arr_scope, $str_identifier = false) {
		
		$this->arr_scope = [
			'users' => ($arr_scope['users'] ? (array)$arr_scope['users'] : []),
			'types' => ($arr_scope['types'] ? (array)$arr_scope['types'] : []),
			'project_id' => ($arr_scope['project_id'] ? (int)$arr_scope['project_id'] : false)
		];
		
		$this->str_identifier_scope = $str_identifier;
	}
	
	public function getScope() {
		
		return $this->arr_scope;
	}
		
	public function setOrder($arr_order, $overwrite = false) {
		
		// $arr_order = array('object_name' => "asc/desc", 'date' => "asc/desc", 'object_description_id => "asc/desc", 'object_description_'.id => "asc/desc")
		
		if (isset($arr_order['object_subs'])) {
			
			foreach ($arr_order['object_subs'] as $object_sub_details_id => $arr_order_object_sub) {
				
				$this->setOrderObjectSubs($object_sub_details_id, $arr_order_object_sub, $overwrite);
			}
		}
		if (isset($arr_order['object'])) {
			
			$arr_order = $arr_order['object'];
		}
		
		if ($overwrite || !$this->arr_order) {
			$this->arr_order = $arr_order;
		} else {
			$this->arr_order = $this->arr_order + $arr_order;
		}
	}
	
	public function setOrderObjectSubs($object_sub_details_id, $arr_order, $overwrite = false) {
		
		// $arr_order = array('object_sub_details_name' => "asc/desc", 'object_sub_date_start/object_sub_date_end' => "asc/desc", 'object_sub_description_'.id => "asc/desc")
		
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
		
		$this->arr_sql_limit_object_subs[$object_sub_details_id] = ($arr_limit ? "LIMIT ".(int)$arr_limit[1]." OFFSET ".(int)$arr_limit[0] : "");
	}
			
	public function setSelection($arr_selection) {
		
		// $arr_selection = array();
		
		if (!empty($arr_selection['object_sub_details']['all'])) {
			
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
	
	public function setFormatMode($mode_format = null) {
		
		$this->mode_format = $mode_format;
	}
	
	public function isFilteringObject($self = false) {
		
		$in_set = ($self ? ($this->arr_filtering['all'] || $this->arr_filtering['object']) : ($this->arr_filtering_enable['all'] || $this->arr_filtering_enable['object']));
		
		$is_filtering = $in_set;
		
		return $is_filtering;
	}
		
	public function isFilteringObjectDescription($object_description_id, $self = false, $in_query = false) {
		
		$in_set = ($self ? ($this->arr_filtering['all'] || !empty($this->arr_filtering['object_descriptions'][$object_description_id])) : ($this->arr_filtering_enable['all'] || !empty($this->arr_filtering_enable['object_descriptions'][$object_description_id])));
		if (!$in_query) {
			if (
				(($self && !empty($this->arr_filtering['object_descriptions'][$object_description_id])) || (!$self && !empty($this->arr_filtering_enable['object_descriptions'][$object_description_id])))
				||
				(!empty($this->query_object_descriptions['object_descriptions'][$object_description_id]))
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
			$in_set = ($self ? ($this->arr_filtering['all'] || !empty($this->arr_filtering['object_sub_details'][$object_sub_details_id])) : ($this->arr_filtering_enable['all'] || !empty($this->arr_filtering_enable['object_sub_details'][$object_sub_details_id])));
		} else {
			$in_set = ($self ? ($this->arr_filtering['all'] || !empty($this->arr_filtering['object_sub_details'])) : ($this->arr_filtering_enable['all'] || !empty($this->arr_filtering_enable['all']['object_sub_details'])));
		}
		if (!$in_query) {
			if ($object_sub_details_id) {
				if (
					(($self && !empty($this->arr_filtering['object_sub_details'][$object_sub_details_id])) || (!$self && !empty($this->arr_filtering_enable['object_sub_details'][$object_sub_details_id])))
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
		$arr_filtering_object_sub_description = ($this->arr_filtering['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] ?? null);
		$arr_filtering_enable_object_sub_description = ($this->arr_filtering_enable['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] ?? null);
		
		$in_set = ($self ? ($this->arr_filtering['all'] || $arr_filtering_object_sub_description) : ($this->arr_filtering_enable['all'] || $arr_filtering_enable_object_sub_description));
		
		$is_filtering = ($in_set && ($arr_object_sub_description['object_sub_description_ref_type_id'] || $arr_object_sub_description['object_sub_description_has_multi'] || $arr_object_sub_description['object_sub_description_is_dynamic']));
		
		if (!$in_query && $is_filtering) {
			
			if (($self && $arr_filtering_object_sub_description) || (!$self && $arr_filtering_enable_object_sub_description)) {
				
				$in_query = true;
			} else if ($this->isQueryingObjectSubDetails($object_sub_details_id)) {
				
				foreach ($this->query_object_subs[$object_sub_details_id] as $table_name_query_object_sub => $arr_query_object_sub) {
					
					if (!empty($arr_query_object_sub['object_sub_descriptions'][$object_sub_description_id])) {
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
			$object_sub_description_id = false;
			$str_separator = false;

			if ($object_sub_details_id) {
				
				$object_sub_description_id = $object_description_id;
				$object_description_id = false;
				
				$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
				
				$table_name = "nodegoat_to_name_tos_def_".$object_sub_description_id;
				
				if ($arr_object_sub_description['object_sub_description_use_object_description_id']) {

					$object_description_id = $arr_object_sub_description['object_sub_description_use_object_description_id'];
				} else {

					$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
					$has_multi = false;
					$ref_type_id = $arr_object_sub_description['object_sub_description_ref_type_id'];
					$value_type = $arr_object_sub_description['object_sub_description_value_type'];
					$str_separator = ($arr_object_sub_description['object_sub_description_value_type_settings']['name']['separator'] ?? null);
				}
			} else {
				
				$table_name = "nodegoat_to_name_to_def_".$object_description_id;
			}
			
			if ($object_description_id) {
				
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
				$is_dynamic = $arr_object_description['object_description_is_dynamic'];
				$has_multi = $arr_object_description['object_description_has_multi'];
				$ref_type_id = $arr_object_description['object_description_ref_type_id'];
				$value_type = $arr_object_description['object_description_value_type'];
				$str_separator = ($arr_object_description['object_description_value_type_settings']['name']['separator'] ?? null);
			}
	
			// Values
			
			$sql_clause = '';
				
			if ($ref_type_id && !$is_dynamic) {
								
				$sql_column = "CASE WHEN ".$table_name.".ref_object_id IS NOT NULL THEN CONCAT('[![', ".$ref_type_id.", '_', ".$table_name.".ref_object_id, ']!]') ELSE NULL END";
				
				$sql_separator = ($str_separator ? DBFunctions::strEscape($str_separator) : ', ');
				
				if ($object_sub_description_id) {
					$this->arr_type_object_name_object_sub_description_ids[$object_sub_details_id][$object_sub_description_id] = $object_sub_description_id;
				} else {
					$this->arr_type_object_name_object_description_ids[$object_description_id] = $object_description_id;
					if ($has_multi) {
						$sql_clause = 'ORDER BY '.$table_name.'.identifier ASC';
					}
				}
			} else {
				
				$sql_column = StoreTypeObjects::formatFromSQLValue($value_type, $table_name.".".StoreType::getValueTypeValue($value_type, 'name'), [], bitUpdateMode($this->mode_format, BIT_MODE_SUBTRACT, StoreTypeObjects::FORMAT_DATE_YMD));
				
				$sql_separator = ($str_separator ? DBFunctions::strEscape($str_separator) : ' ');
				if (!$object_sub_details_id && $has_multi) {
					$sql_clause = 'ORDER BY '.$table_name.'.identifier ASC';
				}
			}
			
			$sql_column = DBFunctions::sqlImplode("DISTINCT ".$sql_column, $sql_separator, $sql_clause); // Always group, even for supposedly individual values, to create valid aggregate (groupable) columns
			$sql_column = "COALESCE(".$sql_column.", '')"; // Set value to plain empty when there really is nothing
			
			$arr_sql_collect[] = ['object_description_id' => $object_description_id, 'object_sub_details_id' => $object_sub_details_id, 'object_sub_description_id' => $object_sub_description_id, 'sql_column' => $sql_column];
			
			// Tables
			
			$version_select = $this->generateVersion('name', $table_name, $value_type);
			
			if ($object_sub_details_id) {
				
				$version_select_tos = $this->generateVersion('name', $table_name.'_tos', $value_type);

				$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$table_name."_tos ON (".$table_name."_tos.object_sub_details_id = ".$object_sub_details_id." AND ".$table_name."_tos.object_id = ".$sql_table_source.".id AND ".$version_select_tos.")";
				if ($object_description_id) {
					$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'name')." ".$table_name." ON (".$table_name.".object_id = ".$table_name."_tos.object_id AND ".$table_name.".object_description_id = ".$object_description_id." AND ".$version_select.")";
				} else {
					$sql_tables .= " LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'name')." ".$table_name." ON (".$table_name.".object_sub_id = ".$table_name."_tos.id AND ".$table_name.".object_sub_description_id = ".$object_sub_description_id." AND ".$version_select.")";
				}
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
			$object_sub_description_id = $arr_sql_column['object_sub_description_id'];
			$sql_column = $arr_sql_column['sql_column'];
			
			if ($object_sub_details_id) {
				
				if ($object_description_id) {
				
					$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
					
					$is_dynamic = $arr_object_description['object_description_is_dynamic'];
					$ref_type_id = $arr_object_description['object_description_ref_type_id'];
				} else {
					
					$arr_object_sub_description = $this->arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
				
					$is_dynamic = $arr_object_sub_description['object_sub_description_is_dynamic'];
					$ref_type_id = $arr_object_sub_description['object_sub_description_ref_type_id'];
				}
				
				$arr_conditions_object_sub_description = ($this->arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] ?? null);
				$is_condition = ($tagging && $arr_conditions_object_sub_description && count(arrValuesRecursive('condition_in_object_name', $arr_conditions_object_sub_description))); // Check if condition is needed specific for this object description
				$identifier = $object_sub_description_id.'_'.$object_sub_details_id;
			} else if ($object_description_id) {
				
				$arr_object_description = $this->arr_type_set['object_descriptions'][$object_description_id];
				
				$is_dynamic = $arr_object_description['object_description_is_dynamic'];
				$ref_type_id = $arr_object_description['object_description_ref_type_id'];
				$arr_conditions_object_description = ($this->arr_type_set_conditions['object_descriptions'][$object_description_id] ?? null);
				$is_condition = ($tagging && $object_description_id && $arr_conditions_object_description && count(arrValuesRecursive('condition_in_object_name', $arr_conditions_object_description))); // Check if condition is needed specific for this object description
				$identifier = $object_description_id;
			} else {
				
				$is_dynamic = false;
				$ref_type_id = false;
				$is_condition = false;
			}
			
			if ($is_condition) {
				$arr_sql_concat[] = "'[".$identifier."]'";
			}
			
			if ($use_object_name && $count == 2) {
				$arr_sql_concat[] = "' ('";
			}

			if (!($count == 1 || ($use_object_name && $count == 2))) { // Spacing
				$arr_sql_concat[] = "CASE WHEN ".$sql_column." = '' THEN '' ELSE ' ' END";
			}
			
			// Select the column
			if ($ref_type_id && !$is_dynamic) {
				$arr_sql_concat[] = $sql_column;
			} else {
				$arr_sql_concat[] = "CASE WHEN ".$sql_column." LIKE '%[[%' THEN CONCAT('".Labels::addContainerOpen()."', ".$sql_column.", '".Labels::addContainerClose()."') ELSE ".$sql_column." END"; // Add language delimiters when needed
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
					
					$sql_column = StoreTypeObjects::formatFromSQLValue($value_type, $table_name.".".StoreType::getValueTypeValue($value_type, 'name'), [], bitUpdateMode($this->mode_format, BIT_MODE_SUBTRACT, StoreTypeObjects::FORMAT_DATE_YMD));
					
					if ($is_display) {
						 $sql_column = "CASE WHEN ".$sql_column." LIKE '%[[%' THEN CONCAT('".Labels::addContainerOpen()."', ".$sql_column.", '".Labels::addContainerClose()."') ELSE ".$sql_column." END"; // Add language delimiters when needed
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
	
	public static function format2SQLObjectSubDate($sql_table_source, $identifier, $count = 4, $only_chronology = false, $sql_table_source_details = false) {
		
		$arr = [];
		
		$only_chronology = ($only_chronology || ($identifier == StoreType::DATE_START_END || $identifier == StoreType::DATE_END_START));
		
		$cur_sql_table = $sql_table_source;
		$cur_sql_table_details = ($sql_table_source_details ?: $cur_sql_table.'_det');
		$prev_sql_table = '';
		
		$arr_sql_select = [];
		
		$sql_value = StoreType::getValueTypeValue('date');
		
		$func_sql_check_identifier = function($sql_table_name, $identifier) {
			
			return "(SELECT TRUE FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')." check_chrono WHERE check_chrono.object_sub_id = ".$sql_table_name.".id AND check_chrono.version = ".$sql_table_name.".date_version AND check_chrono.active = TRUE AND check_chrono.identifier = ".$identifier.")";
		};

		for ($i = 1; $i <= $count; $i++) {
		
			$new_sql_table = $sql_table_source."_".($i+1);
			$new_sql_table_details = $new_sql_table."_det";
			
			$sql_no_recursion = '';
			
			for ($j = 1; $j <= $i; $j++) {
				$sql_no_recursion .= " AND ".$new_sql_table.".id != ".$sql_table_source.($j == 1 ? "" : "_".$j).".id";
			}
			
			if ($i == 1) {
				
				$arr['column_ref_object_sub_id'] = $cur_sql_table."_date_chrono.date_object_sub_id";
				$arr['column_ref_cycle_object_id'] = $cur_sql_table."_date_chrono.cycle_object_id";
				$arr['column_object_sub_details_id'] = $cur_sql_table.".object_sub_details_id"; // Index purposes
				
				// Check whether the statement needs calculation
				$arr['has_calculation'] = "(".$cur_sql_table."_date_chrono.date_object_sub_id IS NOT NULL OR ".$cur_sql_table."_date_chrono.cycle_object_id != 0 OR ".$cur_sql_table."_date_chrono.offset_amount != 0)";
				
				if (!$sql_table_source_details) { // Add a first object_sub_details table when not already added
					
					$arr['tables'] .= "
						LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$cur_sql_table_details." ON (".$cur_sql_table_details.".id = ".$cur_sql_table.".object_sub_details_id)
					";
				}
				
				if ($identifier == StoreType::DATE_END_END) {

					$sql_identifier = "CASE
						WHEN ".$cur_sql_table_details.".is_date_period = FALSE OR NOT EXISTS ".$func_sql_check_identifier($cur_sql_table, StoreType::DATE_END_END)." THEN
							CASE
								WHEN EXISTS ".$func_sql_check_identifier($cur_sql_table, StoreType::DATE_START_END)." THEN ".StoreType::DATE_START_END."
								ELSE ".StoreType::DATE_START_START."
							END
						ELSE ".StoreType::DATE_END_END."
					END";
				} else {
					$sql_identifier = (int)$identifier;
				}
				
				// Add the required tables for the sourced sub-object
				$arr['tables'] .= "
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." AS ".$cur_sql_table."_date ON (".$cur_sql_table."_date.object_sub_id = ".$cur_sql_table.".id AND ".$cur_sql_table."_date.version = ".$cur_sql_table.".date_version)
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')." AS ".$cur_sql_table."_date_chrono ON (".$cur_sql_table."_date_chrono.object_sub_id = ".$cur_sql_table.".id AND ".$cur_sql_table."_date_chrono.version = ".$cur_sql_table.".date_version AND ".$cur_sql_table."_date_chrono.active = TRUE
						AND ".$cur_sql_table."_date_chrono.identifier = ".$sql_identifier."
					)";
			}
			
			$arr_sql_select[$i] = "CASE";
				
				if (!$only_chronology || $i > 1) {
					
					$arr_sql_select[$i] .= "
						WHEN ".$cur_sql_table."_date_use_tos.id IS NOT NULL THEN
							(SELECT [".($i+1)."])
					";

					$arr_sql_select[$i] .= "
						WHEN ".$cur_sql_table."_date_use_tos_def.".$sql_value." != 0 THEN
							".$cur_sql_table."_date_use_tos_def.".$sql_value."
					";
											
					$arr_sql_select[$i] .= "
						WHEN ".$cur_sql_table."_date_use_to_def.".$sql_value." != 0 THEN
							".$cur_sql_table."_date_use_to_def.".$sql_value."
					";
				}
				
				$arr_sql_select[$i] .= "
					WHEN ".$cur_sql_table."_date_chrono.date_value IS NOT NULL THEN
						CASE
							WHEN ".$cur_sql_table."_date_chrono.offset_amount != 0 OR ".$cur_sql_table."_date_chrono.cycle_object_id != 0 THEN ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE(
								".$cur_sql_table."_date_chrono.date_value,
								".$cur_sql_table."_date_chrono.offset_amount, ".$cur_sql_table."_date_chrono.offset_unit, ".$cur_sql_table."_date_chrono.date_direction,
								".$cur_sql_table."_date_chrono.cycle_object_id, ".$cur_sql_table."_date_chrono.cycle_direction,
								".$cur_sql_table."_date_chrono.identifier,
								FALSE
							)
							ELSE ".$cur_sql_table."_date_chrono.date_value
						END
					WHEN ".$cur_sql_table."_date_chrono.date_object_sub_id IS NOT NULL THEN
						CASE
							WHEN ".$cur_sql_table."_date_chrono.offset_amount != 0 OR ".$cur_sql_table."_date_chrono.cycle_object_id != 0 OR ".$cur_sql_table."_date_chrono.date_direction != 0 THEN ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE(
								(SELECT [".($i+1)."]),
								".$cur_sql_table."_date_chrono.offset_amount, ".$cur_sql_table."_date_chrono.offset_unit, ".$cur_sql_table."_date_chrono.date_direction,
								".$cur_sql_table."_date_chrono.cycle_object_id, ".$cur_sql_table."_date_chrono.cycle_direction,
								".$cur_sql_table."_date_chrono.identifier,
								TRUE
							)
							ELSE (SELECT [".($i+1)."])
						END
					ELSE NULL
			END";

			$arr['column_path_'.$i.'_object_sub_id'] = $new_sql_table.".id";
			
			if ($i == $count) {
				
				$sql_column_date = '[1]';
				
				$arr_sql_select[$i+1] = "CASE
					WHEN ".$new_sql_table."_date_chrono.offset_amount != 0 OR ".$new_sql_table."_date_chrono.cycle_object_id != 0 THEN ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE(
						".$new_sql_table."_date_chrono.date_value,
						".$new_sql_table."_date_chrono.offset_amount, ".$new_sql_table."_date_chrono.offset_unit, ".$new_sql_table."_date_chrono.date_direction,
						".$new_sql_table."_date_chrono.cycle_object_id, ".$new_sql_table."_date_chrono.cycle_direction,
						".$new_sql_table."_date_chrono.identifier,
						FALSE
					)
					ELSE ".$new_sql_table."_date_chrono.date_value
				END";
				
				foreach ($arr_sql_select as $i_column => $sql_select) {
					
					$sql_column_date = str_replace('['.$i_column.']', $sql_select, $sql_column_date);
				}

				$arr['column_date'] = $sql_column_date;
			}

			if (!$only_chronology || $i > 1) {
				
				$arr['tables'] .= "
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$cur_sql_table."_date_use_tos ON (".$cur_sql_table."_date_use_tos.object_id = ".$cur_sql_table.".object_id AND ".$cur_sql_table."_date_use_tos.object_sub_details_id = ".$cur_sql_table_details.".date_use_object_sub_details_id AND ".self::generateVersioning('added', 'object_sub', $cur_sql_table."_date_use_tos").")
				";
				
				if ($i == 1) {
					
					if ($identifier == StoreType::DATE_END_END) {
						$sql_identifier = $cur_sql_table_details.".date_end_use_object_sub_description_id";
					} else {
						$sql_identifier = $cur_sql_table_details.".date_start_use_object_sub_description_id";
					}
				} else {
					
					$sql_identifier = "CASE ".$prev_sql_table."_date_chrono.date_direction
						WHEN ".StoreType::TIME_AFTER_BEGIN." THEN ".$cur_sql_table_details.".date_start_use_object_sub_description_id
						WHEN ".StoreType::TIME_BEFORE_BEGIN." THEN ".$cur_sql_table_details.".date_start_use_object_sub_description_id
						WHEN ".StoreType::TIME_AFTER_END." THEN ".$cur_sql_table_details.".date_end_use_object_sub_description_id
						WHEN ".StoreType::TIME_BEFORE_END." THEN ".$cur_sql_table_details.".date_end_use_object_sub_description_id
						WHEN 0 THEN
							CASE
								WHEN ".$prev_sql_table."_date_chrono.identifier = ".StoreType::DATE_END_END." OR ".$prev_sql_table."_date_chrono.identifier = ".StoreType::DATE_START_END." THEN
									".$cur_sql_table_details.".date_end_use_object_sub_description_id
								ELSE
									".$cur_sql_table_details.".date_start_use_object_sub_description_id
							END
						ELSE NULL
					END";
				}
				
				$arr['tables'] .= "
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$cur_sql_table."_date_use_tos_def ON (".$cur_sql_table."_date_use_tos_def.object_sub_id = ".$cur_sql_table.".id AND ".self::generateVersioning('added', 'record', $cur_sql_table."_date_use_tos_def")."
						AND ".$cur_sql_table."_date_use_tos_def.object_sub_description_id = 
							CASE
								WHEN ".$cur_sql_table_details.".is_date_period = FALSE OR ".$cur_sql_table_details.".date_end_use_object_sub_description_id = 0 THEN
									".$cur_sql_table_details.".date_start_use_object_sub_description_id
								ELSE
									".$sql_identifier."
							END
					)
				";
				
				if ($i == 1) {
					
					if ($identifier == StoreType::DATE_END_END) {
						$sql_identifier = $cur_sql_table_details.".date_end_use_object_description_id";
					} else {
						$sql_identifier = $cur_sql_table_details.".date_start_use_object_description_id";
					}
				} else {
					
					$sql_identifier = "CASE ".$prev_sql_table."_date_chrono.date_direction
						WHEN ".StoreType::TIME_AFTER_BEGIN." THEN ".$cur_sql_table_details.".date_start_use_object_description_id
						WHEN ".StoreType::TIME_BEFORE_BEGIN." THEN ".$cur_sql_table_details.".date_start_use_object_description_id
						WHEN ".StoreType::TIME_AFTER_END." THEN ".$cur_sql_table_details.".date_end_use_object_description_id
						WHEN ".StoreType::TIME_BEFORE_END." THEN ".$cur_sql_table_details.".date_end_use_object_description_id
						WHEN 0 THEN
							CASE
								WHEN ".$prev_sql_table."_date_chrono.identifier = ".StoreType::DATE_END_END." OR ".$prev_sql_table."_date_chrono.identifier = ".StoreType::DATE_START_END." THEN
									".$cur_sql_table_details.".date_end_use_object_description_id
								ELSE
									".$cur_sql_table_details.".date_start_use_object_description_id
							END
						ELSE NULL
					END";
				}
				
				$arr['tables'] .= "
					LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$cur_sql_table."_date_use_to_def ON (".$cur_sql_table."_date_use_to_def.object_id = ".$cur_sql_table.".object_id AND ".self::generateVersioning('added', 'record', $cur_sql_table."_date_use_to_def")."
						AND ".$cur_sql_table."_date_use_to_def.object_description_id = 
							CASE
								WHEN ".$cur_sql_table_details.".is_date_period = FALSE OR ".$cur_sql_table_details.".date_end_use_object_description_id = 0 THEN
									".$cur_sql_table_details.".date_start_use_object_description_id
								ELSE
									".$sql_identifier."
							END
					)
				";
			}
			
			$func_sql_identifier = function($use_sql_table) use ($new_sql_table_details, $new_sql_table, $func_sql_check_identifier) {
				
				$sql = "CASE
					WHEN ".$new_sql_table_details.".is_date_period = FALSE OR ((".$use_sql_table."_date_chrono.date_direction = ".StoreType::TIME_AFTER_END." OR ".$use_sql_table."_date_chrono.date_direction = ".StoreType::TIME_BEFORE_END.") AND NOT EXISTS ".$func_sql_check_identifier($new_sql_table, StoreType::DATE_END_END).") THEN
						CASE ".$use_sql_table."_date_chrono.date_direction
							WHEN ".StoreType::TIME_AFTER_BEGIN." THEN ".StoreType::DATE_START_START."
							WHEN ".StoreType::TIME_BEFORE_BEGIN." THEN ".StoreType::DATE_START_START."
							WHEN ".StoreType::TIME_AFTER_END." THEN
								CASE
									WHEN EXISTS ".$func_sql_check_identifier($new_sql_table, StoreType::DATE_START_END)." THEN ".StoreType::DATE_START_END."
									ELSE ".StoreType::DATE_START_START."
								END
							WHEN ".StoreType::TIME_BEFORE_END." THEN
								CASE
									WHEN EXISTS ".$func_sql_check_identifier($new_sql_table, StoreType::DATE_START_END)." THEN ".StoreType::DATE_START_END."
									ELSE ".StoreType::DATE_START_START."
								END
							WHEN 0 THEN
								CASE
									WHEN (".$use_sql_table."_date_chrono.identifier = ".StoreType::DATE_END_END." OR ".$use_sql_table."_date_chrono.identifier = ".StoreType::DATE_START_END.") AND EXISTS ".$func_sql_check_identifier($new_sql_table, StoreType::DATE_START_END)." THEN
										".StoreType::DATE_START_END."
									ELSE
										".StoreType::DATE_START_START."
								END
							ELSE NULL
						END
					ELSE
						CASE ".$use_sql_table."_date_chrono.date_direction
							WHEN ".StoreType::TIME_AFTER_BEGIN." THEN ".StoreType::DATE_START_START."
							WHEN ".StoreType::TIME_BEFORE_BEGIN." THEN ".StoreType::DATE_START_START."
							WHEN ".StoreType::TIME_AFTER_END." THEN ".StoreType::DATE_END_END."
							WHEN ".StoreType::TIME_BEFORE_END." THEN ".StoreType::DATE_END_END."
							WHEN 0 THEN
								CASE
									WHEN (".$use_sql_table."_date_chrono.identifier = ".StoreType::DATE_END_END." OR ".$use_sql_table."_date_chrono.identifier = ".StoreType::DATE_START_END.") THEN
										".StoreType::DATE_END_END."
									ELSE
										".StoreType::DATE_START_START."
								END
							ELSE NULL
						END
				END";
			
				return $sql;
			};
			
			if (!$only_chronology || $i > 1) {
				
				if ($i == 1) {
					
					if ($identifier == StoreType::DATE_END_END) {
						
						$sql_identifier_use_object_sub = "CASE
							WHEN ".$new_sql_table_details.".is_date_period = FALSE OR NOT EXISTS ".$func_sql_check_identifier($new_sql_table, StoreType::DATE_END_END)." THEN
								CASE
									WHEN EXISTS ".$func_sql_check_identifier($new_sql_table, StoreType::DATE_START_END)." THEN ".StoreType::DATE_START_END."
									ELSE ".StoreType::DATE_START_START."
								END
							ELSE ".StoreType::DATE_END_END."
						END";
					} else {
						$sql_identifier_use_object_sub = (int)$identifier;
					}
				} else {
					
					$sql_identifier_use_object_sub = $func_sql_identifier($prev_sql_table);
				}
				
				$sql_identifier = "
					CASE
						WHEN ".$cur_sql_table."_date_use_tos.id IS NOT NULL THEN ".$sql_identifier_use_object_sub."
						ELSE ".$func_sql_identifier($cur_sql_table)."
					END
				";
			} else {
				
				$sql_identifier = $func_sql_identifier($cur_sql_table);
			}
			
			$arr['tables'] .= "LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$new_sql_table." ON (".$new_sql_table.".id = 
					".(!$only_chronology || $i > 1 ? "CASE
						WHEN ".$cur_sql_table."_date_use_tos.id IS NOT NULL THEN ".$cur_sql_table."_date_use_tos.id
						ELSE ".$cur_sql_table."_date_chrono.date_object_sub_id
					END" : $cur_sql_table."_date_chrono.date_object_sub_id")."
					".$sql_no_recursion."
					AND ".self::generateVersioning('added', 'object_sub', $new_sql_table)."
				)
				
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$new_sql_table_details." ON (".$new_sql_table_details.".id = ".$new_sql_table.".object_sub_details_id)
				
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." AS ".$new_sql_table."_date ON (".$new_sql_table."_date.object_sub_id = ".$new_sql_table.".id AND ".$new_sql_table."_date.version = ".$new_sql_table.".date_version)
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY')." AS ".$new_sql_table."_date_chrono ON (".$new_sql_table."_date_chrono.object_sub_id = ".$new_sql_table.".id AND ".$new_sql_table."_date_chrono.version = ".$new_sql_table.".date_version AND ".$new_sql_table."_date_chrono.active = TRUE
					AND ".$new_sql_table."_date_chrono.identifier = ".$sql_identifier."
				)";
			
			$prev_sql_table = $cur_sql_table;
			$cur_sql_table = $new_sql_table;
			$cur_sql_table_details = $new_sql_table_details;
		}
		
		if (Settings::get('update_sql_object_sub_date')) {
			static::setSQLFunctionObjectSubDate();
		}
		
		return $arr;
	}
	
	public static function format2SQLObjectSubDateBase($sql_table_source) {
		
		$sql_table_source_date = $sql_table_source.'_date';
		
		$arr = [];
			
		$arr['tables'] = "
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$sql_table_source_date." ON (".$sql_table_source_date.".object_sub_id = ".$sql_table_source.".id AND ".$sql_table_source_date.".version = ".$sql_table_source.".date_version)
			LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$sql_table_source_date."_det ON (".$sql_table_source_date."_det.id = ".$sql_table_source.".object_sub_details_id)
			
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$sql_table_source_date."_use_tos ON (".$sql_table_source_date."_use_tos.object_id = ".$sql_table_source.".object_id AND ".$sql_table_source_date."_use_tos.object_sub_details_id = ".$sql_table_source_date."_det.date_use_object_sub_details_id AND ".self::generateVersioning('added', 'object_sub', $sql_table_source_date."_use_tos").")
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$sql_table_source_date."_use_tos_date ON (".$sql_table_source_date."_use_tos_date.object_sub_id = ".$sql_table_source_date."_use_tos.id AND ".$sql_table_source_date."_use_tos_date.version = ".$sql_table_source_date."_use_tos.date_version)
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$sql_table_source_date."_use_tos_def ON (".$sql_table_source_date."_use_tos_def.object_sub_id = ".$sql_table_source.".id AND ".self::generateVersioning('added', 'record', $sql_table_source_date."_use_tos_def")."
				AND ".$sql_table_source_date."_use_tos_def.object_sub_description_id = ".$sql_table_source_date."_det.date_start_use_object_sub_description_id
			)
			LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('date')." ".$sql_table_source_date."_use_to_def ON (".$sql_table_source_date."_use_to_def.object_id = ".$sql_table_source.".object_id AND ".self::generateVersioning('added', 'record', $sql_table_source_date."_use_to_def")."
				AND ".$sql_table_source_date."_use_to_def.object_description_id = ".$sql_table_source_date."_det.date_start_use_object_description_id
			)
		";
		
		$sql_value = StoreType::getValueTypeValue('date');
		
		$arr['column_chronology'] = "CASE
			WHEN ".$sql_table_source_date."_use_tos.date_version IS NOT NULL THEN ".StoreTypeObjects::formatFromSQLValue('chronology', $sql_table_source_date.'_use_tos_date')."
			WHEN ".$sql_table_source_date."_use_tos_def.".$sql_value." != 0 THEN NULL
			WHEN ".$sql_table_source_date."_use_to_def.".$sql_value." != 0 THEN NULL
			WHEN ".$sql_table_source.".date_version IS NOT NULL THEN ".StoreTypeObjects::formatFromSQLValue('chronology', $sql_table_source_date)."
			ELSE NULL
		END";
		
		return $arr;
	}
		
	public static function format2SQLObjectSubDateCached($sql_table_source, $include_source = false) {
		
		$sql_table_source_date = $sql_table_source.'_date';
		
		if ($include_source) {
			
			$arr = self::format2SQLObjectSubDateBase($sql_table_source);
		} else {
			
			$arr = [
				'column_chronology' => "CASE
					WHEN ".$sql_table_source.".date_version IS NOT NULL THEN (SELECT ".StoreTypeObjects::formatFromSQLValue('chronology', $sql_table_source_date)." FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE')." ".$sql_table_source_date." WHERE ".$sql_table_source_date.".object_sub_id = ".$sql_table_source.".id AND ".$sql_table_source_date.".version = ".$sql_table_source.".date_version)
					ELSE NULL
				END"
			];
		}
		
		$arr['tables'] .= "
			LEFT JOIN ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE')." AS ".$sql_table_source_date."_cache ON (".$sql_table_source_date."_cache.object_sub_id = ".$sql_table_source.".id AND ".$sql_table_source_date."_cache.active = ".$sql_table_source.".active AND ".$sql_table_source_date."_cache.status = ".$sql_table_source.".status AND ".$sql_table_source_date."_cache.state = 0)
		";
				
		$arr['column_start'] = $sql_table_source_date."_cache.date_start_start";
		$arr['column_end'] = $sql_table_source_date."_cache.date_end_end";
		$arr['column_start_end'] = $sql_table_source_date."_cache.date_start_end";
		$arr['column_end_start'] = $sql_table_source_date."_cache.date_end_start";
		
		return $arr;
	}
	
	public static function format2SQLObjectSubDateStatement($sql_table_chronology, $identifier = false, $arr_options = []) {
		
		$arr = [];
		
		$sql_table_chronology_tos = 'nodegoat_tos_chrono_sel';
		$arr_date = self::generateTablesColumnsObjectSubDating($sql_table_chronology_tos);
		
		$versioning = ($arr_options['versioning'] ?: 'active');
		
		$sql_object_sub_date = "(
			SELECT
				CASE ".$sql_table_chronology.".date_direction
					WHEN ".StoreType::TIME_AFTER_BEGIN." THEN ".$arr_date['column_start']."
					WHEN ".StoreType::TIME_BEFORE_BEGIN." THEN ".$arr_date['column_start']."
					WHEN ".StoreType::TIME_AFTER_END." THEN ".$arr_date['column_end']."
					WHEN ".StoreType::TIME_BEFORE_END." THEN ".$arr_date['column_end']."
					WHEN 0 THEN
						CASE
							WHEN (".$sql_table_chronology.".identifier = ".StoreType::DATE_END_END." OR ".$sql_table_chronology.".identifier = ".StoreType::DATE_START_END.") THEN
								".$arr_date['column_end']."
							ELSE
								".$arr_date['column_start']."
						END
					ELSE NULL
				END
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$sql_table_chronology_tos."
					".$arr_date['tables']."
				WHERE ".$sql_table_chronology_tos.".id = ".$sql_table_chronology.".date_object_sub_id AND ".self::generateVersioning($versioning, 'object_sub', $sql_table_chronology_tos)."
		)";
		
		$sql = "SELECT
			CASE
				WHEN ".$sql_table_chronology.".date_value IS NOT NULL THEN
					CASE
						WHEN ".$sql_table_chronology.".offset_amount != 0 OR ".$sql_table_chronology.".cycle_object_id != 0 THEN ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE(
							".$sql_table_chronology.".date_value,
							".$sql_table_chronology.".offset_amount, ".$sql_table_chronology.".offset_unit, ".$sql_table_chronology.".date_direction,
							".$sql_table_chronology.".cycle_object_id, ".$sql_table_chronology.".cycle_direction,
							".$sql_table_chronology.".identifier,
							FALSE
						)
						ELSE ".$sql_table_chronology.".date_value
					END
				WHEN ".$sql_table_chronology.".date_object_sub_id IS NOT NULL THEN
					CASE
						WHEN ".$sql_table_chronology.".offset_amount != 0 OR ".$sql_table_chronology.".cycle_object_id != 0 OR ".$sql_table_chronology.".date_direction != 0 THEN ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE(
							".$sql_object_sub_date.",
							".$sql_table_chronology.".offset_amount, ".$sql_table_chronology.".offset_unit, ".$sql_table_chronology.".date_direction,
							".$sql_table_chronology.".cycle_object_id, ".$sql_table_chronology.".cycle_direction,
							".$sql_table_chronology.".identifier,
							TRUE
						)
						ELSE ".$sql_object_sub_date."
					END
				ELSE NULL
			END AS date_value,
			".$sql_table_chronology.".identifier
				FROM ".$sql_table_chronology."
			".($identifier ? "WHERE ".$sql_table_chronology.".identifier = ".$identifier : "")."
		";
		
		return $sql;
	}
	
	public static function setSQLFunctionObjectSubDate() {
		
		$func_sql_date_cycle = function($sql_variable, $identifier) {
			
			return "SELECT ".StoreType::getValueTypeValue('date_cycle')." INTO ".$sql_variable."
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('date_cycle')." AS nodegoat_date_cycle
				WHERE nodegoat_date_cycle.object_id = cycle_object_id AND nodegoat_date_cycle.object_description_id = -1 AND nodegoat_date_cycle.identifier = ".$identifier." AND ".self::generateVersioning('added', 'record', 'nodegoat_date_cycle')."
			";
		};
		$func_sql_date_compute = function($sql_variable, $identifier) {
			
			return "SELECT ".StoreType::getValueTypeValue('date_compute')." INTO ".$sql_variable."
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('date_compute')." AS nodegoat_date_compute 
				WHERE nodegoat_date_compute.object_id = cycle_object_id AND nodegoat_date_compute.object_description_id = -2 AND nodegoat_date_compute.identifier = ".$identifier." AND ".self::generateVersioning('added', 'record', 'nodegoat_date_compute')."
			";
		};
		
		$func_sql_set_variable = function($sql_variable, $sql_value) {
			
			if (DB::ENGINE_IS_POSTGRESQL) {
				return $sql_variable.' := '.$sql_value;
			} else {
				return 'SET '.$sql_variable.' = '.$sql_value;
			}
		};

		$sql_program = "
		
			IF date_value IS NULL OR date_value = 0 THEN
				
				RETURN date_value;
			ELSEIF (amount = 0 OR unit = 0) AND cycle_object_id = 0 THEN
			
				IF is_reference = TRUE AND (direction = ".StoreType::TIME_BEFORE_BEGIN." OR direction = ".StoreType::TIME_AFTER_END.") THEN

					".$func_sql_set_variable('value_sequence', DBFunctions::castAs("RIGHT(date_value, 4)", DBFunctions::CAST_TYPE_INTEGER)).";
					
					IF direction = ".StoreType::TIME_BEFORE_BEGIN." THEN -- Before begin makes a sequence explicit
						".$func_sql_set_variable('value_sequence', 'value_sequence - 1').";
					ELSEIF direction = ".StoreType::TIME_AFTER_END." THEN -- After end makes a sequence explicit  
						".$func_sql_set_variable('value_sequence', 'value_sequence + 1').";
					END IF;
					
					IF value_sequence < 0 THEN
						".$func_sql_set_variable('value_sequence', '0').";
					ELSEIF value_sequence > 9999 THEN
						".$func_sql_set_variable('value_sequence', '9999').";
					END IF;
					
					".$func_sql_set_variable('length_value', 'LENGTH('.DBFunctions::castAs('date_value', DBFunctions::CAST_TYPE_STRING).')').";
						
					".$func_sql_set_variable('date_value', DBFunctions::castAs("
						CONCAT(SUBSTRING(date_value FROM 1 FOR length_value-4), LPAD(value_sequence, 4, '0'))
					", DBFunctions::CAST_TYPE_INTEGER)).";
				END IF;
				
				RETURN date_value;
			END IF;
			
			IF direction = 0 THEN
				IF identifier = ".StoreType::DATE_END_END." OR identifier = ".StoreType::DATE_START_END." THEN
					".$func_sql_set_variable('direction', StoreType::TIME_BEFORE_END).";
				ELSE
					".$func_sql_set_variable('direction', StoreType::TIME_AFTER_BEGIN).";
				END IF;
			END IF;
		
			".$func_sql_set_variable('length_value', 'LENGTH('.DBFunctions::castAs('date_value', DBFunctions::CAST_TYPE_STRING).')').";
			
			".$func_sql_set_variable('value_year', DBFunctions::castAs("SUBSTRING(date_value FROM 1 FOR length_value-8)", DBFunctions::CAST_TYPE_INTEGER)).";
			".$func_sql_set_variable('value_month', DBFunctions::castAs("SUBSTRING(date_value FROM (length_value-7) FOR 2)", DBFunctions::CAST_TYPE_INTEGER)).";
			".$func_sql_set_variable('value_day', DBFunctions::castAs("SUBSTRING(date_value FROM (length_value-5) FOR 2)", DBFunctions::CAST_TYPE_INTEGER)).";	
			".$func_sql_set_variable('value_sequence', DBFunctions::castAs("RIGHT(date_value, 4)", DBFunctions::CAST_TYPE_INTEGER)).";
			
			-- Prepare month
			
			IF value_month = 0 THEN
				
				".$func_sql_set_variable('has_month', 'FALSE').";
				
				IF direction = ".StoreType::TIME_BEFORE_END." THEN
					".$func_sql_set_variable('value_month', '13').";
				ELSEIF direction = ".StoreType::TIME_AFTER_END." THEN
					".$func_sql_set_variable('value_month', '13').";
				END IF;
			END IF;
		
			-- Apply Cycle
			
			IF cycle_object_id != 0 THEN
			
				".$func_sql_date_cycle('cycle_month', 0).";
				".$func_sql_date_cycle('cycle_day', 1).";
				".$func_sql_date_compute('cycle_compute_year', 0).";
				".$func_sql_date_compute('cycle_compute_month', 1).";
				".$func_sql_date_compute('cycle_compute_day', 2).";
			
				IF cycle_direction = ".StoreType::TIME_BEFORE_BEGIN." OR cycle_direction = ".StoreType::TIME_AFTER_BEGIN." THEN	-- Begin of cycle
					IF direction = ".StoreType::TIME_AFTER_BEGIN." OR direction = ".StoreType::TIME_AFTER_END." THEN -- After the date
						".$func_sql_set_variable('cycle_year', '0').";
					ELSE -- Before the date
						".$func_sql_set_variable('cycle_year', 'cycle_compute_year * -1').";
					END IF;
				ELSE -- End of cycle
					IF direction = ".StoreType::TIME_AFTER_BEGIN." OR direction = ".StoreType::TIME_AFTER_END." THEN -- After the date
						".$func_sql_set_variable('cycle_year', 'cycle_compute_year').";
					ELSE -- Before the date
						".$func_sql_set_variable('cycle_year', '0').";
					END IF;
					".$func_sql_set_variable('cycle_month', 'cycle_month + cycle_compute_month').";
					".$func_sql_set_variable('cycle_day', 'cycle_day + cycle_compute_day').";
				END IF;
				
				IF direction = ".StoreType::TIME_AFTER_BEGIN." OR direction = ".StoreType::TIME_AFTER_END." THEN -- After the date
					".$func_sql_set_variable('value_year', 'value_year + cycle_year').";
					IF cycle_month != 0 AND cycle_day != 0 THEN
						IF cycle_month < value_month OR (cycle_month = value_month AND cycle_day < value_day) THEN
							".$func_sql_set_variable('value_year', 'value_year + 1').";
						END IF;
						".$func_sql_set_variable('value_month', 'cycle_month').";
						".$func_sql_set_variable('has_month', 'TRUE').";
						".$func_sql_set_variable('value_day', 'cycle_day').";
					ELSEIF cycle_day != 0 THEN
						IF cycle_day < value_day THEN
							".$func_sql_set_variable('value_month', 'value_month + 1').";
						END IF;
						".$func_sql_set_variable('value_day', 'cycle_day').";
					END IF;
				ELSE -- Before the date
					".$func_sql_set_variable('value_year', 'value_year + cycle_year').";
					IF cycle_month != 0 AND cycle_day != 0 THEN
						IF cycle_month > value_month OR (cycle_month = value_month AND cycle_day > value_day) THEN
							".$func_sql_set_variable('value_year', 'value_year - 1').";
						END IF;
						".$func_sql_set_variable('value_month', 'cycle_month').";
						".$func_sql_set_variable('has_month', 'TRUE').";
						".$func_sql_set_variable('value_day', 'cycle_day').";
					ELSEIF cycle_day != 0 THEN
						IF cycle_day > value_day THEN
							".$func_sql_set_variable('value_month', 'value_month - 1').";
						END IF;
						".$func_sql_set_variable('value_day', 'cycle_day').";
					END IF;
				END IF;
				
				-- Use the cycle direction from now on
				".$func_sql_set_variable('direction', 'cycle_direction').";
			END IF;
			
			-- Apply offset
								
			IF direction = ".StoreType::TIME_BEFORE_BEGIN." OR direction = ".StoreType::TIME_BEFORE_END." THEN -- Before the date
				".$func_sql_set_variable('amount', 'amount * -1').";
			END IF;
			
			IF unit = ".StoreType::TIME_UNIT_DAY." THEN
				
				".$func_sql_set_variable('value_day', 'value_day + amount').";
				
				IF value_day < 0 THEN
					".$func_sql_set_variable('value_day', '0').";
				END IF;
			ELSEIF unit = ".StoreType::TIME_UNIT_MONTH." THEN
				
				".$func_sql_set_variable('value_month', 'value_month + amount').";
			ELSEIF unit = ".StoreType::TIME_UNIT_YEAR." THEN
				
				".$func_sql_set_variable('value_year', 'value_year + amount').";						
			END IF;
			
			-- Restore month if applicable
			
			IF has_month = FALSE THEN
			
				IF direction = ".StoreType::TIME_BEFORE_END." THEN
					IF value_month = 13 THEN
						IF value_day != 0 THEN -- Date has a day specified; keep the month
							".$func_sql_set_variable('value_month', '12').";
						ELSE
							".$func_sql_set_variable('value_month', '0').";
						END IF;
					END IF;
				ELSEIF direction = ".StoreType::TIME_AFTER_END." THEN
					IF value_month = 13 THEN
						IF value_day != 0 THEN
							".$func_sql_set_variable('value_month', 'value_month - 1')."; -- Correction based on the direction
						ELSE
							".$func_sql_set_variable('value_month', '0').";
						END IF;
					ELSEIF value_month > 13 THEN
						".$func_sql_set_variable('value_month', 'value_month - 1')."; -- Correction based on the direction
					END IF;
				ELSE
					IF value_month = 0 THEN
						IF value_day != 0 THEN
							".$func_sql_set_variable('value_month', '1').";
						END IF;
					END IF;
				END IF;
				
			END IF;
			
			-- Parse month
			
			IF value_month > 12 THEN
				".$func_sql_set_variable('value_year', 'value_year + FLOOR(value_month / 12)').";
				".$func_sql_set_variable('value_month', 'value_month % 12').";
			ELSEIF value_month < 0 THEN
				".$func_sql_set_variable('value_year', 'value_year + FLOOR(value_month / 12)').";
				".$func_sql_set_variable('value_month', '12 + (value_month % 12)').";
			END IF;
			
			-- Parse sequence
			
			IF is_reference = TRUE THEN
				".$func_sql_set_variable('value_sequence', StoreTypeObjects::DATE_INT_SEQUENCE_NULL).";
			END IF;
			
			-- Return date
			
			".$func_sql_set_variable('date_value', DBFunctions::castAs("
				CONCAT(value_year, LPAD(value_month, 2, '0'), LPAD(value_day, 2, '0'), LPAD(value_sequence, 4, '0'))
			", DBFunctions::CAST_TYPE_INTEGER)).";
					 
			RETURN date_value;
		";
		
		$sql_function = "
			DROP FUNCTION IF EXISTS ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE;
		";
		
		if (DB::ENGINE_IS_MYSQL) {
			
			$sql_function .= "
				CREATE FUNCTION ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE(date_value BIGINT, amount SMALLINT, unit SMALLINT, direction SMALLINT, cycle_object_id INT, cycle_direction SMALLINT, identifier SMALLINT, is_reference BOOLEAN) RETURNS BIGINT
					DETERMINISTIC READS SQL DATA
					LANGUAGE SQL
					BEGIN

						DECLARE length_value SMALLINT;
						DECLARE value_year BIGINT;
						DECLARE value_month SMALLINT;
						DECLARE value_day SMALLINT;
						DECLARE value_sequence SMALLINT;
					
						DECLARE has_month BOOLEAN DEFAULT TRUE;

						DECLARE cycle_year SMALLINT;
						DECLARE cycle_month SMALLINT;
						DECLARE cycle_day SMALLINT;
						DECLARE cycle_compute_year SMALLINT;
						DECLARE cycle_compute_month SMALLINT;
						DECLARE cycle_compute_day SMALLINT;
						
						".$sql_program."
					END
			;";
		} else {
			
			$sql_function .= "
				CREATE FUNCTION ".DATABASE_NODEGOAT_TEMP.".PARSE_NODEGOAT_DATE(date_value BIGINT, amount SMALLINT, unit SMALLINT, direction SMALLINT, cycle_object_id INT, cycle_direction SMALLINT, identifier SMALLINT, is_reference BOOLEAN) RETURNS BIGINT
					STABLE
					LANGUAGE plpgsql
					AS $$
					DECLARE
						length_value SMALLINT;
						value_year BIGINT;
						value_month SMALLINT;
						value_day SMALLINT;
						value_sequence SMALLINT;
					
						has_month BOOLEAN DEFAULT TRUE;

						cycle_year SMALLINT;
						cycle_month SMALLINT;
						cycle_day SMALLINT;
						cycle_compute_year SMALLINT;
						cycle_compute_month SMALLINT;
						cycle_compute_day SMALLINT;
					BEGIN

						".$sql_program."
					END;
					$$
			;";
		}
		
		DB::setConnection(DB::CONNECT_CMS);
		
		DB::queryMulti($sql_function);
		
		DB::setConnection();
	}
	
	public static function format2SQLObjectSubLocationReference($sql_table_source, $count = 4, $sql_table_source_details = false) {
		
		$arr = [];
		
		$func_sql_select_date = function($sql_table, $identifier) {
			
			/*
			return "(SELECT ".($identifier == StoreType::DATE_END_END ? 'date_end_end' :  'date_start_start')."
				FROM ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE')." AS nodegoat_tos_date_cache
				WHERE nodegoat_tos_date_cache.object_sub_id = ".$sql_table.".id AND nodegoat_tos_date_cache.active = ".$sql_table.".active AND nodegoat_tos_date_cache.status = ".$sql_table.".status AND nodegoat_tos_date_cache.state = 0
			)";
			*/
	
			return $sql_table.'.'.($identifier == StoreType::DATE_END_END ? 'date_end_end' :  'date_start_start');
		};
		
		$cur_sql_table = $sql_table_source;
		$cur_sql_table_details = ($sql_table_source_details ?: $cur_sql_table."_det");
				
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
				
				$arr['has_geometry'] = $cur_sql_table.".location_geometry_version IS NOT NULL";
				$arr['has_reference'] = "(".$new_sql_table."_pre.object_id IS NOT NULL OR ".$cur_sql_table_details.".location_use_object_id = TRUE OR ".$cur_sql_table."_loc_use_tos.location_ref_object_id IS NOT NULL OR ".$cur_sql_table."_loc_use_tos_def.ref_object_id IS NOT NULL OR ".$cur_sql_table."_loc_use_to_def.ref_object_id IS NOT NULL OR ".$cur_sql_table."_loc_use_nodegoat_to_referencing.ref_object_id IS NOT NULL OR ".$cur_sql_table.".location_ref_object_id != 0)";
				
				if (!$sql_table_source_details) { // Add a first object_sub_details table when not already added
					
					$arr['tables'] .= "
						LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_SUB_DETAILS')." ".$cur_sql_table_details." ON (".$cur_sql_table_details.".id = ".$cur_sql_table.".object_sub_details_id)
					";
				}
				
				$arr['tables'] .= "
					LEFT JOIN ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE')." AS ".$cur_sql_table."_date_cache ON (
						".$cur_sql_table."_date_cache.object_sub_id = ".$cur_sql_table.".id AND ".$cur_sql_table."_date_cache.active = ".$cur_sql_table.".active AND ".$cur_sql_table."_date_cache.status = ".$cur_sql_table.".status AND ".$cur_sql_table."_date_cache.state = 0
					)";
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
			
			// Take the first referencing object outside a use(relayed)-chain
			$sql_not_in_chain = "(".$cur_sql_table_details.".location_use_object_id = FALSE AND ".$cur_sql_table."_loc_use_tos.location_ref_object_id IS NULL AND ".$cur_sql_table."_loc_use_tos_def.ref_object_id IS NULL AND ".$cur_sql_table."_loc_use_to_def.ref_object_id IS NULL AND ".$cur_sql_table."_loc_use_nodegoat_to_referencing.ref_object_id IS NULL)";
			$column_ref_show_object_id .= " WHEN ".$sql_not_in_chain." OR ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN
				CASE WHEN ".$new_sql_table."_pre.object_id IS NOT NULL THEN ".$new_sql_table."_pre.object_id
					WHEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id IS NOT NULL THEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id
					WHEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id IS NOT NULL THEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id
					WHEN ".$cur_sql_table."_loc_use_to_def.ref_object_id IS NOT NULL THEN ".$cur_sql_table."_loc_use_to_def.ref_object_id
					WHEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.ref_object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_object_id
					WHEN ".$cur_sql_table.".location_ref_object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_object_id
					ELSE NULL
				END";
			$column_ref_show_type_id .= " WHEN ".$sql_not_in_chain." OR ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN
				CASE WHEN ".$new_sql_table."_pre.object_id IS NOT NULL THEN ".$new_sql_table_details.".type_id
					WHEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id IS NOT NULL THEN ".$cur_sql_table_details.".location_ref_type_id
					WHEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id IS NOT NULL THEN ".$cur_sql_table_details.".location_ref_type_id
					WHEN ".$cur_sql_table."_loc_use_to_def.ref_object_id IS NOT NULL THEN ".$cur_sql_table_details.".location_ref_type_id
					WHEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.ref_object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_type_id
					WHEN ".$cur_sql_table.".location_ref_object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_type_id
					ELSE NULL
				END";
			$column_ref_show_object_sub_details_id .= " WHEN ".$sql_not_in_chain." OR ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN
				CASE WHEN ".$new_sql_table."_pre.object_id IS NOT NULL THEN ".$new_sql_table_details.".id
					WHEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id IS NOT NULL THEN ".$cur_sql_table_details.".location_ref_object_sub_details_id
					WHEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id IS NOT NULL THEN ".$cur_sql_table_details.".location_ref_object_sub_details_id
					WHEN ".$cur_sql_table."_loc_use_to_def.ref_object_id IS NOT NULL THEN ".$cur_sql_table_details.".location_ref_object_sub_details_id
					WHEN ".$cur_sql_table."_loc_use_nodegoat_to_referencing.ref_object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_object_sub_details_id
					WHEN ".$cur_sql_table.".location_ref_object_id IS NOT NULL THEN ".$cur_sql_table.".location_ref_object_sub_details_id
					ELSE NULL
				END";

			/*
			// Take the first referencing object ouside a possibly chained object's own scope
			$column_ref_show_object_id .= " WHEN ".$new_sql_table."_pre.id IS NOT NULL AND ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN ".$new_sql_table."_pre.object_id";
			$column_ref_show_type_id .= " WHEN ".$new_sql_table."_pre.id IS NOT NULL AND ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN ".$new_sql_table_details.".type_id";
			$column_ref_show_object_sub_details_id .= " WHEN ".$new_sql_table."_pre.id IS NOT NULL AND ".$sql_table_source.".object_id != ".$new_sql_table."_pre.object_id THEN ".$new_sql_table_details.".id";
			*/
			
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
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$cur_sql_table."_loc_use_tos ON (".$cur_sql_table."_loc_use_tos.object_id = ".$cur_sql_table.".object_id AND ".$cur_sql_table."_loc_use_tos.object_sub_details_id = ".$cur_sql_table_details.".location_use_object_sub_details_id AND ".self::generateVersioning('added', 'object_sub', $cur_sql_table."_loc_use_tos").")
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$cur_sql_table."_loc_use_tos_def ON (".$cur_sql_table."_loc_use_tos_def.object_sub_id = ".$cur_sql_table.".id AND ".$cur_sql_table."_loc_use_tos_def.object_sub_description_id = ".$cur_sql_table_details.".location_use_object_sub_description_id AND ".self::generateVersioning('added', 'record', $cur_sql_table."_loc_use_tos_def").")
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$cur_sql_table."_loc_use_to_def ON (".$cur_sql_table."_loc_use_to_def.object_id = ".$cur_sql_table.".object_id AND ".$cur_sql_table."_loc_use_to_def.object_description_id = ".$cur_sql_table_details.".location_use_object_description_id AND ".self::generateVersioning('added', 'record', $cur_sql_table."_loc_use_to_def").")
				
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable('type')." ".$cur_sql_table."_loc_use_nodegoat_to_referencing ON (".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_id = 
					CASE
						WHEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_tos.location_ref_object_id
						WHEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_tos_def.ref_object_id
						WHEN ".$cur_sql_table."_loc_use_to_def.ref_object_id != 0 THEN ".$cur_sql_table."_loc_use_to_def.ref_object_id
						ELSE ".$cur_sql_table.".location_ref_object_id
					END
					AND ".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_description_id = (
						SELECT nodegoat_to_des.id FROM ".DB::getTable('DEF_NODEGOAT_TYPE_OBJECT_DESCRIPTIONS')." nodegoat_to_des WHERE nodegoat_to_des.id = ".$cur_sql_table."_loc_use_nodegoat_to_referencing.object_description_id AND nodegoat_to_des.id_id = ".StoreType::getSystemTypeObjectDescriptionID(StoreType::getSystemTypeID('reversal'), 'reference')."
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
					AND ".self::generateVersioning('added', 'object_sub', $new_sql_table."_pre")."
				)
				
				LEFT JOIN ".DB::getTable('CACHE_NODEGOAT_TYPE_OBJECT_SUB_DATE')." AS ".$new_sql_table."_date_cache ON (
					".$new_sql_table."_date_cache.object_sub_id = ".$new_sql_table."_pre.id AND ".$new_sql_table."_date_cache.active = ".$new_sql_table."_pre.active AND ".$new_sql_table."_date_cache.status = ".$new_sql_table."_pre.status AND ".$new_sql_table."_date_cache.state = 0
				)
				
				LEFT JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." ".$new_sql_table." ON (".$new_sql_table.".id = ".$new_sql_table."_pre.id
					".$sql_no_recursion."
					AND ".$new_sql_table.".object_sub_details_id = ".$new_sql_table_details.".id
					AND ".self::generateVersioning('added', 'object_sub', $new_sql_table)."
					AND CASE
						WHEN ".$new_sql_table_details.".is_single = FALSE THEN ".FilterTypeObjects::format2SQLDateIntMatch($func_sql_select_date($new_sql_table."_date_cache", StoreType::DATE_START_START), $func_sql_select_date($new_sql_table."_date_cache", StoreType::DATE_END_END), $func_sql_select_date($cur_sql_table."_date_cache", StoreType::DATE_START_START), $func_sql_select_date($cur_sql_table."_date_cache", StoreType::DATE_END_END))."
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
						
						$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true);
						
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
			
			$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true);
			
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
			
			$arr_sql_location = $this->generateTablesColumnsObjectSubLocationReference('nodegoat_tos', true);
			
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
	
	public static function encodeTypeObjectID($type_id, $object_id) {
		
		$context_id = $type_id;
		$object_id = (int)$object_id;
		
		$len_mask = strlen($object_id);
		$operator = ($object_id < 10 ? '0' : '').substr($object_id, -2); // Operator requires two positions: add 10
		
		$mask = (int)str_pad('', $len_mask, $operator, STR_PAD_RIGHT);
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
		
		// Store operator and version (or mask size when no (default) version) in encoded string
		
		$str_len_mask = '';
		$num_len = 0;
		
		while ($len_mask >= $num_len + 10) {
			
			$str_len_mask .= '0';
			$num_len += 10;
		}
		
		$str_len_mask .= (($len_mask-$num_len) ?: 'a'); // Put 'a' when the remainder is 0 (and therefore a whole of 10)
				
		// Split the operator to overall 3rd and 5th position in identifier, put the mask size (or version) from 6th position, and append 'ngx'
		$encoded = substr_replace($encoded, substr($operator, 0, 1), 2, 0);
		$encoded = substr_replace($encoded, substr($operator, 1, 1).$str_len_mask, 4, 0);
		$encoded = 'ngx'.$encoded;
		
		return $encoded;
	}
	
	public static function decodeTypeObjectID($str) {
		
		if (substr($str, 0, 3) !== 'ngx') {
			return false;
		}
		
		$str = substr($str, 3); // Remove ngx
		
		$str_version = substr($str, 5, 1); // Retreive version (or mask size when no (default) version)
		
		if (!is_numeric($str_version)) {
			
			// Parse version 'a', 'b' etc.
			return false;
		}
		
		// Parse default version
		
		$str_pos_mask = $str_version;
		$pos_mask = 0;

		while ($str_pos_mask === '0') {
			
			$pos_mask++;
			$str_pos_mask = substr($str, (5+$pos_mask), 1);
		}
		
		$len_mask = (($pos_mask * 10) + ($str_pos_mask === 'a' ? 0 : (int)$str_pos_mask));
		
		$operator = substr($str, 2, 1).substr($str, 4, 1); // Retrieve operator
		
		$str = substr_replace($str, '', 4, 2); // Remove operator and mask size
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
		
		
		$type_id = (int)$context_id;
		
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
		
		$mask = (int)str_pad('', $len_mask, $operator, STR_PAD_RIGHT);
		$object_id = ((int)$masked_id ^ $mask); // Xor
		
		if (!$type_id || !$object_id) {
			return false;
		}
				
		return ['type_id' => $type_id, 'object_id' => $object_id];
	}
	
	public static function parseTypeObjectID($string) {
		
		$object_id = (int)$string; // Object ID

		if (!$object_id) {
			
			$object_id = false;
					
			// nodegoat ID
			$arr_type_object_id = GenerateTypeObjects::decodeTypeObjectID($string);
			
			if ($arr_type_object_id !== false) {
				$object_id = (int)$arr_type_object_id['object_id'];
			}
		}
		
		return $object_id;
	}
				
	public static function getTypeObjectNames($type_id, $arr_objects, $conditions = true) {
				
		$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_NAME);
		$filter->setVersioning('added');
		$filter->setFilter(['objects' => $arr_objects]);
		if ($conditions) {
			$filter->setConditions($conditions, toolbar::getTypeConditions($type_id));
		}
		
		$arr = [];
		
		foreach ($filter->init() as $object_id => $arr_object) {
			$arr[$object_id] = $arr_object['object']['object_name'];
		}
		
		return $arr;
	}
	
	public static function getTypeObjectSubsNames($type_id, $arr_objects, $arr_object_subs, $arr_object_sub_details = false, $conditions = true) {
				
		$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ALL);
		$filter->setVersioning('added');
		$filter->setSelection(['object' => [], 'object_descriptions' => []]);
		$filter->setFilter(['objects' => $arr_objects, 'object_subs' => $arr_object_subs, 'object_sub_details' => $arr_object_sub_details], true);
		if ($conditions) {
			$filter->setConditions($conditions, toolbar::getTypeConditions($type_id));
		}
		
		$arr = [];
		
		foreach ($filter->init() as $object_id => $arr_object) {
			foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
				
				$arr_object_sub_self = $arr_object_sub['object_sub'];
				
				$str_name = StoreTypeObjects::int2Date($arr_object_sub_self['object_sub_date_start']).($arr_object_sub_self['object_sub_date_end'] != $arr_object_sub_self['object_sub_date_start'] ? ' - '.StoreTypeObjects::int2Date($arr_object_sub_self['object_sub_date_end']) : '').' '.$arr_object_sub_self['object_sub_location_ref_object_name'];
								
				foreach ($arr_object_sub['object_sub_definitions'] as $object_sub_description_id => $arr_object_sub_definition) {
					
					if (!$arr_object_sub_definition['object_sub_definition_value']) {
						continue;
					}
					
					$str_name .= ($str_name ? ' ' : '').$arr_object_sub_definition['object_sub_definition_value'];
				}
				
				$arr[$object_sub_id] = $str_name;
			}
		}
		
		return $arr;
	}
	
	public static function getTypeObjectValuesByFlatMap($type_id, $arr_object, $arr_map) {
				
		$is_multi = true;
		if (!is_array($arr_map)) {
			$arr_map = [$arr_map => $arr_map];
			$is_multi = false;
		}
		
		$arr_collect = [];
		
		foreach ($arr_map as $element_identifier => $arr_select) {
			
			$has_select = is_array($arr_select);
			$arr_element = explode('-', $element_identifier);
			
			if ($arr_element[0] == 'object') {
				
				if ($arr_element[1] == 'name') {
					
					$arr_collect[$element_identifier] = $arr_object['object']['object_name_plain'];
				} else if ($arr_element[1] == 'id' || $arr_element[1] == 'nodegoat_id') {
					$arr_collect[$element_identifier] = static::encodeTypeObjectID($type_id, $arr_object['object']['object_id']);
				}
			} else if ($arr_element[0] == 'object_description') {
					
				$object_description_id = $arr_element[1];
							
				if ($has_select) {
					
					if ($arr_select['value'] !== null || $arr_select['reference_value'] !== null) {
						$arr_collect[$element_identifier] = $arr_object['object_definitions'][$object_description_id]['object_definition_value'];
					}
					if ($arr_select['reference'] !== null) {
						$arr_collect[$element_identifier] = $arr_object['object_definitions'][$object_description_id]['object_definition_ref_object_id'];
					}
				} else if ((bool)$arr_select) {

					$arr_collect[$element_identifier] = $arr_object['object_definitions'][$object_description_id]['object_definition_value'];
				}
			} else if ($arr_element[0] == 'object_sub_details') {

				$object_sub_details_id = $arr_element[1];
				
				foreach ($arr_object['object_subs'] as $object_sub_id => $arr_object_sub) {
						
					if ($arr_object_sub['object_sub']['object_sub_details_id'] != $object_sub_details_id) {
						continue;
					}
					
					$str_element = $arr_element[2];
					
					switch ($str_element) {
						
						case 'id':
						
							$arr_collect[$element_identifier][] = $object_sub_id;
							break;
						case 'date_chronology':
						
							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_date_chronology'];
							break;
						case 'date_start':
						
							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_date_start'];
							break;
						case 'date_end':

							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_date_end'];
							break;
						case 'location_geometry':
							
							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_location_geometry'];
							break;
						case 'location_latitude':
						
							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_location_latitude'];
							break;
						case 'location_longitude':
						
							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_location_longitude'];
							break;
						case 'location_reference':
						case 'location_ref_type_id':
						
							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_location_ref_type_id'];
							break;
						case 'location_reference_value':
						
							$arr_collect[$element_identifier][] = $arr_object_sub['object_sub']['object_sub_location_reference_value'];
							break;
						case 'object_sub_description':
						
							$object_sub_description_id = $arr_element[3];
																				
							if ($has_select) {
								
								if ($arr_select['value'] !== null || $arr_select['reference_value'] !== null) {
									$arr_collect[$element_identifier][] = $arr_object_sub['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value'];
								}
								if ($arr_select['reference'] !== null) {
									$arr_collect[$element_identifier][] = $arr_object_sub['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_ref_object_id'];
								}
							} else if ((bool)$arr_select) {
															
								$arr_collect[$element_identifier][] = $arr_object_sub['object_sub_definitions'][$object_sub_description_id]['object_sub_definition_value'];
							}

							break;
					}
				}
			}
		}
		
		return (!$is_multi ? current($arr_collect) : $arr_collect);	
	}
}
