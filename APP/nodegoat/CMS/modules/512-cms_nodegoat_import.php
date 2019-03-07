<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_IMPORT_TEMPLATES', DB::$database_home.'.def_nodegoat_import_templates');
DB::setTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS', DB::$database_home.'.def_nodegoat_import_template_columns');
DB::setTable('DEF_NODEGOAT_IMPORT_FILES', DB::$database_home.'.def_nodegoat_import_files');
DB::setTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS', DB::$database_home.'.def_nodegoat_import_string_object_pairs');

define('DIR_TYPE_IMPORT', DIR_UPLOAD.'import/');
define('DIR_HOME_TYPE_IMPORT', DIR_ROOT_STORAGE.DIR_HOME.DIR_TYPE_IMPORT);

class cms_nodegoat_import extends base_module {
	
	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function handleImportTemplate($import_template_id = false, $arr_details) {

		if ($import_template_id) {
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')."
					SET name = '".DBFunctions::strEscape($arr_details['name'])."', type_id = ".(int)$arr_details['type_id'].", source_file_id = ".(int)$arr_details['source_file_id']."
				WHERE id = ".(int)$import_template_id."");
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')."
				(name, description, type_id, source_file_id)
					VALUES
				('".DBFunctions::strEscape($arr_details['name'])."', '', ".(int)$arr_details['type_id'].", ".(int)$arr_details['source_file_id'].")
			");
			
			$import_template_id = DB::lastInsertID();
		}
	
		$arr_ids = [];
		$sort = 0;
		
		foreach ($arr_details['arr_column_headings'] as $arr_column_heading) {
			
			if (!$arr_column_heading['column_heading']) {
				continue;
			}
			
			$generate = ($arr_column_heading['splitter'] ? $arr_column_heading['generate'] : 0);
					
			if ($arr_column_heading['column_id']) {
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')." SET
						heading = '".DBFunctions::strEscape($arr_column_heading['column_heading'])."',
						splitter = '".DBFunctions::strEscape($arr_column_heading['splitter'])."',
						generate = '".DBFunctions::strEscape($generate)."',
						target_type_id = ".(int)$arr_column_heading['target_type_id'].",
						element_id = '".DBFunctions::strEscape($arr_column_heading['element_id'])."',
						element_type_id = ".($arr_column_heading['element_type_id'] ? (int)$arr_column_heading['element_type_id'] : 0).",
						element_type_object_sub_id = ".($arr_column_heading['element_type_object_sub_id'] ? (int)$arr_column_heading['element_type_object_sub_id'] : 0).",
						element_type_element_id = '".DBFunctions::strEscape($arr_column_heading['element_type_element_id'])."', 
						use_as_filter = ".(int)$arr_column_heading['is_filter'].",
						use_type_object_id = ".(int)$arr_column_heading['use_object_id_as_filter'].",
						sort = ".$sort." 
					WHERE id = ".(int)$arr_column_heading['column_id']."");
								
				$arr_ids[] = (int)$arr_column_heading['column_id'];
			} else {
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')."
					(template_id, heading, splitter, generate, target_type_id, element_id, element_type_id, element_type_object_sub_id, element_type_element_id, use_as_filter, use_type_object_id, sort)
						VALUES
					(".$import_template_id.",
						'".DBFunctions::strEscape($arr_column_heading['column_heading'])."',
						'".DBFunctions::strEscape($arr_column_heading['splitter'])."',
						'".DBFunctions::strEscape($generate)."',
						".(int)$arr_column_heading['target_type_id'].",
						'".DBFunctions::strEscape($arr_column_heading['element_id'])."',
						".(int)$arr_column_heading['element_type_id'].",
						".(int)$arr_column_heading['element_type_object_sub_id'].",
						'".DBFunctions::strEscape($arr_column_heading['element_type_element_id'])."',
						".(int)$arr_column_heading['is_filter'].",
						".(int)$arr_column_heading['use_object_id_as_filter'].",
						".$sort."
					)");
									
				$arr_ids[] = DB::lastInsertID();
			}
			$sort++;
		}

		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')."
			WHERE template_id = ".(int)$import_template_id."
			".($arr_ids ? "AND id NOT IN (".implode(',', $arr_ids).")" : "")."
		");
	
	}
	
	public static function handleSourceFile($source_file_id = false, $type, $arr_details, $arr_files) {
	
		if ($arr_files) {
			
			$extension = FileStore::getExtension($arr_files['file']['name']);

			if ($extension == 'csv' || $extension == 'tsv') {

				$arr_file = self::convertFileToJson($arr_files['file'], $extension);

				$file = $arr_file[0];
				
				$file['type'] = 'application/json';
				
				$number_of_objects = $arr_file[1];
			}
			
			if (!$file) {
				error(getLabel('msg_invalid_file_type'));
			}

			$filename = hash_file('md5', $file['tmp_name']);
			$format = $filename.'.json';
			
			$file['name'] = $format;
			
			if (!isPath(DIR_HOME_TYPE_IMPORT.$format)) {
				$store_file = new FileStore($file, ['dir' => DIR_HOME_TYPE_IMPORT, 'filename' => $format]);
			}
		}
			
		if ($source_file_id) {
			
			$arr_source_file = self::getSourceFiles($source_file_id);
			
			if (!$format) {
				
				$filename = $arr_source_file['filename'];
				$number_of_objects = $arr_source_file['total_objects'];
				
			} else if ($arr_source_file['filename'] && $arr_source_file['filename'] != $format) {
				
				$res = DB::query("SELECT *
						FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
					WHERE filename = '".DBFunctions::strEscape($arr_source_file['filename'])."'
				");

				if ($res->getRowCount() == 1) {
									
					FileStore::deleteFile(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']); 
				}
				
				$filename = $format;
				
			} else {
				
				$filename = $format;
			}
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
					SET name = '".DBFunctions::strEscape($arr_details['name'])."', description = '".DBFunctions::strEscape($arr_details['description'])."', filename = '".DBFunctions::strEscape($filename)."', total_objects = ".(int)$number_of_objects."
				WHERE id = ".(int)$source_file_id."
			");
		
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
				(name, description, filename, total_objects)
					VALUES
				('".DBFunctions::strEscape($arr_details['name'])."', '".DBFunctions::strEscape($arr_details['description'])."', '".DBFunctions::strEscape($format)."', ".(int)$number_of_objects.")
			");
		}
		
	}
	
	public static function getSourceFiles($id = false, $show_rows_in_name = true) {
		
		$arr = [];
		$res = DB::query("SELECT nodegoat_if.* ".($show_rows_in_name ? ", CONCAT(nodegoat_if.name, ' (', nodegoat_if.total_objects, ')') AS name" : "")." 
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')." nodegoat_if
			".($id ? "WHERE nodegoat_if.id = ".(int)$id."" : "")."
			ORDER BY nodegoat_if.id DESC
		");
				 
		while($row = $res->fetchAssoc()) {
			$arr[$row["id"]] = $row;
		}	

		if ($id && is_numeric($id)) {
			
			$arr = current($arr);
			
			if (!file_exists(DIR_HOME_TYPE_IMPORT.$arr['filename'])) {
				return false;
			}
		}
		
		return $arr;
	}
	
	public static function getImportTemplates($id = false) {
		
		$arr = [];
		
		$res = DB::query("SELECT nodegoat_it.*,
			nodegoat_itc.id AS column_id, nodegoat_itc.heading AS column_heading, nodegoat_itc.splitter, nodegoat_itc.generate, nodegoat_itc.target_type_id, nodegoat_itc.element_id, nodegoat_itc.element_type_id, nodegoat_itc.element_type_object_sub_id, nodegoat_itc.element_type_element_id, nodegoat_itc.use_as_filter AS is_filter, nodegoat_itc.use_type_object_id AS use_object_id_as_filter, nodegoat_itc.is_source AS is_source, nodegoat_itc.source_type_id AS source_type_id, nodegoat_itc.source_link_position AS source_link_position
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." nodegoat_it
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')." nodegoat_itc ON (nodegoat_itc.template_id = nodegoat_it.id)
			".($id ? "WHERE nodegoat_it.id = ".(int)$id."" : "")."
			ORDER BY nodegoat_it.id, nodegoat_itc.sort
		");
				 
		while($row = $res->fetchAssoc()) {
			
			$arr['import_template'] = $row;
			if ($row['column_id']) {
				$arr['columns'][$row['column_id']] = $row;
			}
		}
		
		if (!$arr['import_template']['type_id']) { // Update previous format
			
			foreach ($arr['columns'] as $key => $arr_column) {
					
				if ($arr_column['target_type_id']) {
					$type_id = $arr_column['target_type_id'];
				}
				
				$arr_column_heading = json_decode($arr_column['column_heading'], true);
				
				if (is_array($arr_column_heading)) {
					$arr['columns'][$key]['column_heading'] = key($arr_column_heading);
				}	
				
				if (!$arr_column['is_filter'] && $arr_column['use_object_id_as_filter']) {
					$arr['columns'][$key]['element_type_element_id'] = 'o_id';
				}
			}
			
			$arr['import_template']['type_id'] = $type_id;
		}

		return $arr;
	}
	
	public static function getColumnHeadings($source_file_id) {
		
		memoryBoost(1024);
		
		$arr_source_file = self::getSourceFiles($source_file_id);
		$arr_source_file_structure = [];
		
		if (!$arr_source_file) {
			return false;
		}

		$arr_source_file_contents = json_decode(file_get_contents(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']), true); 
		
		if (!$arr_source_file_contents) {
			
			$arr_source_file_contents = [];
		}
		
		foreach ((array)$arr_source_file_contents[key($arr_source_file_contents)] as $column => $value) {
			$arr_source_file_structure[$column] = ['name' => $column, 'id' => $column];
		}
		 
		return $arr_source_file_structure;
	
	}
	
	public static function delImportTemplate($import_template_id) {

		$res = DB::query("DELETE nodegoat_it, nodegoat_itc
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." nodegoat_it
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')." nodegoat_itc ON (nodegoat_itc.template_id = nodegoat_it.id)
			WHERE nodegoat_it.id = ".(int)$import_template_id."
		");
	}
		
	public static function delSourceFile($source_file_id) {
		
		$arr_source_file = self::getSourceFiles($source_file_id);
		
		$res = DB::query("DELETE nodegoat_if
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')." nodegoat_if
			WHERE nodegoat_if.id = ".(int)$source_file_id."
		");
		
		// If Source File had file, check whether it was used by other Source Files and delete accordingly
		if ($arr_source_file['filename']) {
			
			$res = DB::query("SELECT * FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
				WHERE filename = '".DBFunctions::strEscape($arr_source_file['filename'])."'");

			if (!$res->getRowCount()) {
								
				FileStore::deleteFile(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']); 
			}
		}
	}
	
	public static function getStringObjectPair($string_object_pair_id) {
		
		$res = DB::query("SELECT *
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')."
			WHERE id = ".(int)$string_object_pair_id."");
		
		$string_object_pair = $res->fetchAssoc();
		
		return $string_object_pair;
	}
	
	public static function delStringObjectPair($string_object_pair_id) {
		
		$res = DB::query("DELETE nodegoat_isop
			FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')." nodegoat_isop
				WHERE nodegoat_isop.id = ".(int)$string_object_pair_id."
		");
	}
	
	public static function updateStringObjectPair($string_object_pair_id, $object_id) {
		
		$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')."
				SET object_id = ".(int)$object_id."
			WHERE id = ".(int)$string_object_pair_id."");
	}
			
	public static function addStringObjectPair($string, $type_id, $object_id, $filter_values = '') {

		$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')." 
			(string, filter_values, type_id, object_id) 
				VALUES
			('".DBFunctions::strEscape($string)."', '".DBFunctions::strEscape($filter_values)."', ".(int)$type_id.", ".(int)$object_id.")
		");
	}
	
	public static function flattenTypeSet($type_id, $object_id = false, $no_references = false) {
	
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr = [];
		
		//$arr_ref['o_source'] = ['id' => 'o_source', 'name' => 'Object Sources']; 

		if ($object_id) {
			$arr['o_id'] = ['id' => 'o_id', 'name' => 'nodegoat ID']; 
		}
				
		if ($arr_type_set['type']['use_object_name']) {
			$arr['o_name'] = ['id' => 'o_name', 'name' => 'Object Name']; 
		}
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if ($arr_object_description['object_description_value_type_base'] == 'reversal') {
				
				continue;
			}
			
			if ($no_references && $arr_object_description['object_description_ref_type_id']) {
				
				continue;
			}
			
			$arr['o_'.$object_description_id] = ['id' => 'o_'.$object_description_id, 'name' => $arr_object_description['object_description_name'], 'ref_type_id' => $arr_object_description['object_description_ref_type_id']]; 
		}
		
		if ($arr_type_set['object_sub_details']) {
			
			foreach ($arr_type_set['object_sub_details'] as $arr_object_sub_details_id => $arr_object_sub_details) {
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_date_use_object_description_id']) {
					$arr['so_'.$arr_object_sub_details_id.'_date-start'] = ['id' => 'so_'.$arr_object_sub_details_id.'_date-start', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Date Start']; 
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_is_date_range']) {
						$arr['so_'.$arr_object_sub_details_id.'_date-end'] = ['id' => 'so_'.$arr_object_sub_details_id.'_date-end', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Date End']; 
					}
				}
				if (!$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_details_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_sub_description_id'] && !$arr_object_sub_details['object_sub_details']['object_sub_details_location_use_object_description_id']) {
				
					if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id_locked']) {
						if (!$no_references) {
							$arr['so_'.$arr_object_sub_details_id.'_location-ref-type-id_sub-details-lock'] = ['id' => 'so_'.$arr_object_sub_details_id.'_location-ref-type-id_sub-details-lock', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Location Reference', 'ref_type_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'], 'object_sub_details_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'], 'location_reference' => true]; 
						}
					} else if ($arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id_locked']) {
						if (!$no_references) {
							$arr['so_'.$arr_object_sub_details_id.'_location-ref-type-id_type-lock_'.$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id']] = ['id' => 'so_'.$arr_object_sub_details_id.'_location-ref-type-id_type-lock_'.$arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'], 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Location Reference', 'ref_type_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'], 'object_sub_details_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'], 'object_sub_details_is_changeable' => true, 'location_reference' => true]; 
						}
					} else {
						if (!$no_references) {
							$arr['so_'.$arr_object_sub_details_id.'_location-ref-type-id'] = ['id' => 'so_'.$arr_object_sub_details_id.'_location-ref-type-id', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Location Reference', 'ref_type_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_type_id'], 'object_sub_details_id' => $arr_object_sub_details['object_sub_details']['object_sub_details_location_ref_object_sub_details_id'], 'ref_type_is_changeable' => true, 'object_sub_details_is_changeable' => true, 'location_reference' => true]; 
						}
						$arr['so_'.$arr_object_sub_details_id.'_lat'] = ['id' => 'so_'.$arr_object_sub_details_id.'_lat', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Latitude']; 
						$arr['so_'.$arr_object_sub_details_id.'_lon'] = ['id' => 'so_'.$arr_object_sub_details_id.'_lon', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Longitude']; 
						$arr['so_'.$arr_object_sub_details_id.'_geometry'] = ['id' => 'so_'.$arr_object_sub_details_id.'_geometry', 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - Geometry']; 
					}
				}
				if ($arr_object_sub_details['object_sub_descriptions']) {
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $object_sub_description) {
						
						if ($no_references && $object_sub_description['object_sub_description_ref_type_id']) {
							
							continue;
						}
						
						$arr['so_'.$arr_object_sub_details_id.'_osd_'.$object_sub_description_id] = ['id' => 'so_'.$arr_object_sub_details_id.'_osd_'.$object_sub_description_id, 'name' => '['.$arr_object_sub_details['object_sub_details']['object_sub_details_name'].'] - '.$object_sub_description['object_sub_description_name'], 'ref_type_id' => $object_sub_description['object_sub_description_ref_type_id']]; 
					}
				}
			}
		}
		
		return $arr;
	}
	
	public static function checkTemplateFileCompatibility($import_template_id, $source_file_id) {

		memoryBoost(1024);
	
		$arr_import_template = self::getImportTemplates($import_template_id);
		$arr_source_file = self::getSourceFiles($source_file_id);		
		
		if (!$arr_source_file || !$arr_import_template['columns']) {
			return false;
		}
		
		$arr_source_file_contents = json_decode(file_get_contents(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']), true);
		
		$matching_columns = 0;
		
		foreach ($arr_import_template['columns'] as $arr_column) {
			
			$column_heading = $arr_column['column_heading'];
					
			if ($arr_source_file_contents[0][$column_heading]) {
				$matching_columns++;
			}
		}
		
		return $matching_columns;
	}

	private static function convertFileToJson($file, $extension) {
		
		ini_set('auto_detect_line_endings', true);
		
		memoryBoost();
		
		$arr_delimiter_enclosure = self::getFileDelimiterEnclosure($file, $extension);
		$arr_nodegoat_details = cms_nodegoat_details::getDetails();
		$limit_import = $arr_nodegoat_details['limit_import'];
		
		$delimiter = $arr_delimiter_enclosure[0];
		$enclosure = $arr_delimiter_enclosure[1];

		if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
			
			$row_count = 0;
			$columns_count = 0; 
			$column_labels = []; 			
			
			while (($arr_row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
				
				if ($row_count == 0) {
					
					$columns_count = count($arr_row);
					$column_labels = $arr_row;
					
				} else if ($row_count > $limit_import) {
					
					Labels::setVariable('limit', $limit_import);
					error(getLabel('msg_import_limit_exceeded'));

				} else {
				
					for ($column = 0; $column < $columns_count; $column++) {
						
						$value = $arr_row[$column];
						
						if (!mb_check_encoding($value, 'UTF-8')) {
							$value = mb_convert_encoding($value, 'UTF-8');
						}
						
						$arr[$row_count-1][$column_labels[$column]] = $value;
					}
				}
				
				$row_count++;
			}
			
			fclose($handle);
		}
	
		ini_set('auto_detect_line_endings', false);

		file_put_contents($file['tmp_name'], json_encode($arr));

		return [$file, $row_count-1];
	}

	private static function getFileDelimiterEnclosure($file, $extension) {
		
		$arr_delimiters = ($extension == 'tsv' ? ["\t"] : [",", "\t", ";", "|", ":"]);
		$arr_delimiters_results =  [];
		$parse_rows = 100;
		
		$delimiter = ',';
		$enclosure = '"';
		
		// Find all delimiters that yield > 1 column using either single or double quotes as enclosures
		if (($handle = fopen($file['tmp_name'], 'r')) !== false) {

			$i = 0;

			while (($line = fgets($handle)) !== false && $i < $parse_rows) {
				
				foreach ($arr_delimiters as $key => $test_delimiter) {
					
					$arr_columns_single_quote = str_getcsv($line, $test_delimiter, "'");
					$arr_columns_double_quote = str_getcsv($line, $test_delimiter, "\"");
					
					$arr_line = explode($test_delimiter, $line);
					
					if (count($arr_columns_single_quote) > 1 || count($arr_columns_double_quote) > 1) {
		
						// if str_getcsv has yielded a valid response, check if enclosure actually has been correctly used in combination with tested delimiter, i.e. delimiter.enclosure or enclosure.delimiter
						
						$identified_enclosure = false;

						if (preg_match('/^\'.*\''.$test_delimiter.'|'.$test_delimiter.'\'.*\''.$test_delimiter.'|'.$test_delimiter.'\'.*\'$/', $line)) {
							
							$arr_delimiters_results[$test_delimiter]['single_quote'][$i] = count($arr_columns_single_quote);
							
							$identified_enclosure = true;
						
						}
						
						if (preg_match('/^\".*\"'.$test_delimiter.'|'.$test_delimiter.'\".*\"'.$test_delimiter.'|'.$test_delimiter.'\".*\"$/', $line)) {
					
							$arr_delimiters_results[$test_delimiter]['double_quote'][$i] = count($arr_columns_double_quote);
						
							$identified_enclosure = true;
						}
						
						if (!$identified_enclosure) {
							
							$arr_delimiters_results[$test_delimiter]['no_quote'][$i] = count($arr_columns_double_quote);
							
						}
					}
				}

				$i++;
			}
			
			fclose($handle);
		}

		if (count($arr_delimiters_results)) {
		
			// Check if multiple rows have been generated by one delimiter and whether these rows have equal amounts of columns using either single or double quotes
			foreach ($arr_delimiters_results as $key_delimiter => $arr_results) {

				if (
						($arr_results['single_quote'] && count(array_unique($arr_results['single_quote'])) == 1) || 
						($arr_results['double_quote'] && count(array_unique($arr_results['double_quote'])) == 1) || 
						($arr_results['no_quote'] && count(array_unique($arr_results['no_quote'])) == 1)
					
					) {
					
					$valid_delimiter = $key_delimiter;

					if ($arr_results['single_quote'] && count(array_unique($arr_results['single_quote'])) == 1) {
						
						$valid_enclosure = '\'';
						$count_rows = count($arr_results['single_quote']);
						$count_columns = end($arr_results['single_quote']);
						
					} else if ($arr_results['double_quote'] && count(array_unique($arr_results['double_quote'])) == 1) {
						
						$valid_enclosure = '"';
						$count_rows = count($arr_results['double_quote']);
						$count_columns = end($arr_results['double_quote']);
						
					} else if ($arr_results['no_quote'] && count(array_unique($arr_results['no_quote'])) == 1) {
						
						$valid_enclosure = '"';
						$count_rows = count($arr_results['no_quote']);
						$count_columns = end($arr_results['no_quote']);
					}
					
					if ($count_rows > 1) {
						$arr_valid_delimiters[] = ['delimiter' => $valid_delimiter, 'enclosure' => $valid_enclosure, 'count_rows' => $count_rows, 'count_columns' => $count_columns];
					}
				}
			}

			$max_count = false;
		
			// If multiple possibly valid delimiters & enclosures have been identified, use delimiter and enclosure with most occurrences
			foreach ((array)$arr_valid_delimiters as $key => $arr_value) {
				
				if (!$max_count || $arr_value['count_rows'] + $arr_value['count_columns'] > $max_count) {
					
					$max_count = $arr_value['count_rows'] + $arr_value['count_columns'];
					
					$delimiter = $arr_value['delimiter'];
					
					if ($arr_value['enclosure']) {
						
						$enclosure = $arr_value['enclosure'];
						
					}
				}
			}
		}

		return [$delimiter, $enclosure];
	}
	
	public static function getGenerateOptions() {
	
		return [
			['id' => 'multiple', 'name' => 'multiple'],
			['id' => '1', 'name' => '1'],
			['id' => '2', 'name' => '2'],
			['id' => '3', 'name' => '3'],
			['id' => '4', 'name' => '4'],
			['id' => '5', 'name' => '5']
		];
	}
}
