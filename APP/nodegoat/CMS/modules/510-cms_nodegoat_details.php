<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('TABLE_NODEGOAT_DETAILS', DB::$database_home.'.def_nodegoat_details');

DB::setTable('TABLE_USER_DETAILS' , DB::$database_home.'.user_details');
DB::setTable('TABLE_USER_PREFERENCES' , DB::$database_home.'.user_preferences');


define('NODEGOAT_CLEARANCE_DEMO', 1);
define('NODEGOAT_CLEARANCE_INTERACT', 2);
define('NODEGOAT_CLEARANCE_UNDER_REVIEW', 3);
define('NODEGOAT_CLEARANCE_USER', 4);
define('NODEGOAT_CLEARANCE_ADMIN', 5);
define('NODEGOAT_CLEARANCE_SYSTEM', 6);

define('DIR_CACHE_SCENARIOS', DIR_ROOT_CACHE.SITE_NAME.'/scenarios/');

class cms_nodegoat_details extends base_module {
	
	public static $num_network_trace_depth = 5;

	public static function moduleProperties() {
		static::$label = getLabel('ttl_details');
		static::$parent_label = 'nodegoat';
	}
	
	public function contents() {
		
		$return = '<div class="section"><h1>'.self::$label.'</h1>
		<div class="nodegoat_details">';

			$arr = self::getDetails();
			
			$return .= '<div class="tabs">
				<ul>
					<li><a href="#">'.getLabel('lbl_settings').'</a></li>
					<li><a href="#">'.getLabel('lbl_system').'</a></li>
				</ul>
				
				<div>
					<form id="f:cms_nodegoat_details:update-0">
						
						<div class="options">
							<fieldset><ul>
								'.'
								<li>
									<label>'.getLabel('lbl_processing_memory').'</label>
									<span><input type="text" name="details[processing_memory]" value="'.$arr['processing_memory'].'" /><label>MB</label></span>
								</li>
								<li>
									<label>'.getLabel('lbl_processing_time').'</label>
									<span><input type="text" name="details[processing_time]" value="'.$arr['processing_time'].'" /><label>'.Response::addParseDelay(getLabel('unit_seconds'), 'ucfirst').'</label></span>
								</li>
								<li>
									<label>'.getLabel('lbl_view_limit').'</label>
									<span><input type="text" name="details[limit_view]" value="'.$arr['limit_view'].'" /><label>'.getLabel('lbl_objects').' / '.getLabel('lbl_object_subs').'</label></span>
								</li>
								<li>
									<label>'.getLabel('lbl_import_limit').'</label>
									<span><input type="text" name="details[limit_import]" value="'.$arr['limit_import'].'" /><label>'.getLabel('lbl_rows').'</label></span>
								</li>
								<li>
									<label>'.getLabel('lbl_file_size_limit').'</label>
									<span><input type="text" name="details[limit_file_size]" value="'.$arr['limit_file_size'].'" /><label>B / kB / MB / GB</label></span>
								</li>
							</ul></fieldset>
						</div>
						
						<menu><input type="submit" value="'.getLabel('lbl_save').'" /></menu>
					
					</form>
				</div>
				
				<div>
				
					<div class="options">
						<fieldset><ul>
							<li>
								<label>'.getLabel('lbl_model').' ('.getLabel('lbl_quick_search').'/'.getLabel('lbl_name').')</label>
								<div><input type="button" id="y:cms_nodegoat_details:reset_data_model_paths-0" class="data neutral quick" value="'.getLabel('lbl_reset').'" /></div>
							</li>
						</ul></fieldset>
					</div>

				</div>
				
			</div>
		</div></div>';
		
		return $return;
	}
		
	public static function css() {
	
		$return = '';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		// INTERACT
						
		// DATATABLE
							
		// QUERY
		
		if ($method == 'update') {

			$arr_sql_fields = DBFunctions::arrEscape(array_keys($_POST['details']));
			$str_values = implode("','", DBFunctions::arrEscape($_POST['details']));

			$res = DB::query("INSERT INTO ".DB::getTable('TABLE_NODEGOAT_DETAILS')."
				(".implode(',', $arr_sql_fields).")
					VALUES
				('".$str_values."')
				".DBFunctions::onConflict('unique_row', $arr_sql_fields)."
			");
	
			$this->msg = true;
		}
		
		if ($method == 'reset_data_model_paths') {
			
			$arr_type_ids = StoreType::getTypes();
			
			if (!$arr_type_ids) {
				return;
			}
			
			$arr_type_ids = array_keys($arr_type_ids);
			
			StoreType::setTypesObjectPath($arr_type_ids, 'name');
			StoreType::setTypesObjectPath($arr_type_ids, 'search');
			
			$this->msg = true;
		}
	}
	
	public static function getDetails() {
		
		$cache = self::getCache('details');
		if ($cache) {
			return $cache;
		}
					
		$arr = [];

		$res = DB::query("SELECT * FROM ".DB::getTable('TABLE_NODEGOAT_DETAILS')."");
		$arr = $res->fetchAssoc();
		
		self::setCache('details', $arr);
		
		return $arr;
	}
	
	public static function getClearanceLevels() {
					
		$arr = [
			['id' => NODEGOAT_CLEARANCE_DEMO, 'label' => getLabel('lbl_demo')],
			['id' => NODEGOAT_CLEARANCE_INTERACT, 'label' => getLabel('lbl_clearance_interact')],
			['id' => NODEGOAT_CLEARANCE_UNDER_REVIEW, 'label' => getLabel('lbl_clearance_under_review')],
			['id' => NODEGOAT_CLEARANCE_USER, 'label' => getLabel('lbl_clearance_user')],
			['id' => NODEGOAT_CLEARANCE_ADMIN, 'label' => getLabel('lbl_clearance_admin')]
		];

		return $arr;
	}
}
