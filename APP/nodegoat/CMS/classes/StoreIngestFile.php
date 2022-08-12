<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreIngestFile {
	
	const POINTER_CLASS_MAP = 0;
	const POINTER_CLASS_FILTER_OBJECT_IDENTIFIER = 1;
	const POINTER_CLASS_FILTER_OBJECT_VALUE = 2;
	
	public static function handleTemplate($template_id, $arr_template) {

		if ($template_id) {
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." SET
					name = '".DBFunctions::strEscape($arr_template['name'])."',
					type_id = ".(int)$arr_template['type_id'].",
					source_id = ".(int)$arr_template['source_id'].",
					mode = ".(int)$arr_template['mode'].",
					use_log = ".DBFunctions::escapeAs($arr_template['use_log'], DBFunctions::TYPE_BOOLEAN)."
				WHERE id = ".(int)$template_id."
			");
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')."
				("."name, description, type_id, source_id, mode, use_log)
					VALUES
				(
					"."
					'".DBFunctions::strEscape($arr_template['name'])."',
					'',
					".(int)$arr_template['type_id'].",
					".(int)$arr_template['source_id'].",
					".(int)$arr_template['mode'].",
					".DBFunctions::escapeAs($arr_template['use_log'], DBFunctions::TYPE_BOOLEAN)."
				)
			");
			
			$template_id = DB::lastInsertID();
		}
	
		$arr_ids = [];
		$sort = 0;
		
		$arr_collect = [];
		
		if ($arr_template['pointers']['filter_object_identifier']) {
			
			$arr_pointer = $arr_template['pointers']['filter_object_identifier'];

			if ($arr_pointer['pointer_heading']) {

				$arr_collect[] = [
					'id' => $arr_pointer['pointer_id'],
					'pointer_heading' => $arr_pointer['pointer_heading'],
					'pointer_class' => static::POINTER_CLASS_FILTER_OBJECT_IDENTIFIER
				];
			}
		}
		
		foreach ($arr_template['pointers']['filter_object_value'] as $arr_pointer) {
			
			if (!$arr_pointer['pointer_heading']) {
				continue;
			}
												
			$arr_collect[] = [
				'id' => $arr_pointer['pointer_id'],
				'pointer_heading' => $arr_pointer['pointer_heading'],
				'pointer_class' => static::POINTER_CLASS_FILTER_OBJECT_VALUE,
				'element_id' => $arr_pointer['element_id']
			];
		}
		
		foreach ($arr_template['pointers']['map'] as $arr_pointer) {
			
			if (!$arr_pointer['pointer_heading']) {
				continue;
			}
			
			$ignore_when = 0;
			$ignore_when += ($arr_pointer['ingore_empty'] ? 1 : 0);
			$ignore_when += ($arr_pointer['ingore_identical'] ? 2 : 0);
				
			$arr_collect[] = [
				'id' => $arr_pointer['pointer_id'],
				'pointer_heading' => $arr_pointer['pointer_heading'],
				'pointer_class' => static::POINTER_CLASS_MAP,
				'value_split' => $arr_pointer['value_split'],
				'value_index' => ($arr_pointer['value_split'] ? $arr_pointer['value_index'] : '0'),
				'element_id' => $arr_pointer['element_id'],
				'element_type_id' => ($arr_pointer['element_type_id'] ? (int)$arr_pointer['element_type_id'] : 0),
				'element_type_object_sub_id' => ($arr_pointer['element_type_object_sub_id'] ? (int)$arr_pointer['element_type_object_sub_id'] : 0),				
				'element_type_element_id' => $arr_pointer['element_type_element_id'],
				'overwrite' => ($arr_pointer['mode_write'] == 'overwrite' ? true : false),
				'ignore_when' => (int)$ignore_when
			];
		}
		
		foreach ($arr_collect as $arr_pointer) {
					
			if ($arr_pointer['id']) {
				
				$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')." SET
						pointer_heading = '".DBFunctions::strEscape($arr_pointer['pointer_heading'])."',
						pointer_class = ".(int)$arr_pointer['pointer_class'].",
						value_split = '".DBFunctions::strEscape($arr_pointer['value_split'])."',
						value_index = '".DBFunctions::strEscape($arr_pointer['value_index'])."',
						element_id = '".DBFunctions::strEscape($arr_pointer['element_id'])."',
						element_type_id = ".(int)$arr_pointer['element_type_id'].",
						element_type_object_sub_id = ".(int)$arr_pointer['element_type_object_sub_id'].",
						element_type_element_id = '".DBFunctions::strEscape($arr_pointer['element_type_element_id'])."',
						overwrite = ".DBFunctions::escapeAs($arr_pointer['overwrite'], DBFunctions::TYPE_BOOLEAN).",
						ignore_when = ".(int)$arr_pointer['ignore_when'].",
						sort = ".$sort." 
					WHERE id = ".(int)$arr_pointer['id']."
				");
								
				$arr_ids[] = (int)$arr_pointer['id'];
			} else {
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')."
					(template_id, pointer_heading, pointer_class, value_split, value_index, element_id, element_type_id, element_type_object_sub_id, element_type_element_id, overwrite, ignore_when, sort)
						VALUES
					(
						".(int)$template_id.",
						'".DBFunctions::strEscape($arr_pointer['pointer_heading'])."',
						".(int)$arr_pointer['pointer_class'].",
						'".DBFunctions::strEscape($arr_pointer['value_split'])."',
						'".DBFunctions::strEscape($arr_pointer['value_index'])."',
						'".DBFunctions::strEscape($arr_pointer['element_id'])."',
						".(int)$arr_pointer['element_type_id'].",
						".(int)$arr_pointer['element_type_object_sub_id'].",
						'".DBFunctions::strEscape($arr_pointer['element_type_element_id'])."',
						".DBFunctions::escapeAs($arr_pointer['overwrite'], DBFunctions::TYPE_BOOLEAN).",
						".(int)$arr_pointer['ignore_when'].",
						".$sort."
					)
				");
									
				$arr_ids[] = DB::lastInsertID();
			}
			$sort++;
		}

		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')."
			WHERE template_id = ".(int)$template_id."
			".($arr_ids ? "AND id NOT IN (".implode(',', $arr_ids).")" : "")."
		");
	}
	
	public static function delTemplate($template_id) {

		$res = DB::queryMulti("			
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')." WHERE template_id = ".(int)$template_id.";
			DELETE FROM ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')." WHERE template_id = ".(int)$template_id.";
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." WHERE id = ".(int)$template_id.";
		");
	}

	public static function handleSource($source_id, $type, $arr_details, $arr_files) {
		
		$filename = false;
		
		if ($arr_files) {
			
			$arr_file = false;
			$extension = FileStore::getExtension($arr_files['file']['name']);
			
			if ($extension == 'csv' || $extension == 'tsv' || $extension == FileStore::EXTENSION_UNKNOWN) {
				
				$arr_nodegoat_details = cms_nodegoat_details::getDetails();
				if ($arr_nodegoat_details['processing_time']) {
					timeLimit($arr_nodegoat_details['processing_time']);
				}
				$num_limit = $arr_nodegoat_details['limit_import'];

				$arr_source = IngestTypeObjectsFile::convertSource($arr_files['file'], false, $num_limit);
				
				if ($arr_source) {
					$arr_file = $arr_source['file'];
					$num_objects = $arr_source['num_objects'];
				}
			}
			
			if (!$arr_file) {
				error(getLabel('msg_invalid_file_type'));
			}

			$filename = hash_file('md5', $arr_file['tmp_name']);
			$filename = $filename.'.json';
			
			$arr_file['name'] = $filename;
			
			if (!isPath(DIR_HOME_TYPE_IMPORT.$filename)) {
				$store_file = new FileStore($arr_file, ['dir' => DIR_HOME_TYPE_IMPORT, 'filename' => $filename]);
			}
		}
			
		if ($source_id) {
			
			$arr_source_file = static::getSources($source_id);
			
			if (!$filename) {
				
				$filename = $arr_source_file['filename'];
				$num_objects = $arr_source_file['total_objects'];
				
			} else if ($arr_source_file['filename'] && $arr_source_file['filename'] != $filename) {
				
				$res = DB::query("SELECT *
						FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
					WHERE filename = '".DBFunctions::strEscape($arr_source_file['filename'])."'
				");

				if ($res->getRowCount() == 1) {		
					FileStore::deleteFile(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']); 
				}
			}
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')." SET
					name = '".DBFunctions::strEscape($arr_details['name'])."',
					description = '".DBFunctions::strEscape($arr_details['description'])."',
					filename = '".DBFunctions::strEscape($filename)."',
					total_objects = ".(int)$num_objects."
				WHERE id = ".(int)$source_id."
			");
		} else {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
				("."name, description, filename, total_objects)
					VALUES
				(
					"."
					'".DBFunctions::strEscape($arr_details['name'])."',
					'".DBFunctions::strEscape($arr_details['description'])."',
					'".DBFunctions::strEscape($filename)."',
					".(int)$num_objects."
				)
			");
		}
	}
	
	public static function getSources($id = false, $show_rows_in_name = true) {
		
		$arr = [];
		
		$res = DB::query("SELECT nodegoat_if.* ".($show_rows_in_name ? ", CONCAT(nodegoat_if.name, ' (', nodegoat_if.total_objects, ')') AS name" : "")." 
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')." nodegoat_if
			WHERE "."TRUE"."
				".($id ? "AND nodegoat_if.id = ".(int)$id."" : "")."
			ORDER BY nodegoat_if.id DESC
		");
				 
		while ($arr_row = $res->fetchAssoc()) {
			$arr[$arr_row['id']] = $arr_row;
		}	

		if ($id && is_numeric($id)) {
			
			$arr = current($arr);
			
			if (!isPath(DIR_HOME_TYPE_IMPORT.$arr['filename'])) {
				return [];
			}
		}
		
		return $arr;
	}
	
	public static function getTemplates($id = false) {
		
		$arr = [];
		
		$res = DB::query("SELECT nodegoat_it.*,
			nodegoat_itc.id AS pointer_id, nodegoat_itc.pointer_heading, nodegoat_itc.pointer_class, nodegoat_itc.value_split, nodegoat_itc.value_index, nodegoat_itc.element_id, nodegoat_itc.element_type_id, nodegoat_itc.element_type_object_sub_id, nodegoat_itc.element_type_element_id, nodegoat_itc.overwrite, nodegoat_itc.ignore_when, nodegoat_itc.heading_for_source_link
				FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." nodegoat_it
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATE_COLUMNS')." nodegoat_itc ON (nodegoat_itc.template_id = nodegoat_it.id)
			WHERE "."TRUE"."
				".($id ? "AND nodegoat_it.id = ".(int)$id."" : "")."
			ORDER BY nodegoat_it.id, nodegoat_itc.sort
		");
				 
		while ($arr_row = $res->fetchAssoc()) {
			
			if (!$arr[$arr_row['id']]) {
					
				$arr_row['use_log'] = DBFunctions::unescapeAs($arr_row['use_log'], DBFunctions::TYPE_BOOLEAN);
				
				$arr[$arr_row['id']] = $arr_row;
			}
			
			if (!$arr[$arr_row['id']]['pointers']) {
				
				$arr[$arr_row['id']]['pointers'] = [
					'filter_object_identifier' => [],
					'filter_object_value' => [],
					'map' => []
				];
			}
			
			if ($arr_row['pointer_id']) {
				
				if ($arr_row['pointer_class'] == static::POINTER_CLASS_FILTER_OBJECT_IDENTIFIER) {
					$arr[$arr_row['id']]['pointers']['filter_object_identifier'] = $arr_row;
				} else if ($arr_row['pointer_class'] == static::POINTER_CLASS_FILTER_OBJECT_VALUE) {
					$arr[$arr_row['id']]['pointers']['filter_object_value'][$arr_row['pointer_id']] = $arr_row;
				} else {
					
					$arr_row['mode_write'] = ($arr_row['overwrite'] ? 'overwrite' : 'append');
					$arr_row['ingore_empty'] = ($arr_row['ignore_when'] == 1 || $arr_row['ignore_when'] == 3);
					$arr_row['ingore_identical'] = ($arr_row['ignore_when'] == 2 || $arr_row['ignore_when'] == 3);
					
					$arr[$arr_row['id']]['pointers']['map'][$arr_row['pointer_id']] = $arr_row;
				}
			}
		}
		
		$arr = ($id ? current($arr) : $arr);
		
		return $arr;
	}
	
	public static function getColumnHeadings($source_id) {
		
		$arr_source_file = static::getSources($source_id);
		$arr_source_structure = [];
		
		if (!$arr_source_file) {
			return $arr_source_structure;
		}
		
		$import = new IngestTypeObjectsFile(false);
		$import->setSource(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']);
		
		$arr_row = $import->getPointerData(0);
		
		if ($arr_row) {

			foreach ($arr_row as $str_pointer => $value) {
				
				$arr_source_structure[$str_pointer] = ['name' => $str_pointer, 'id' => $str_pointer];
			}
		}
		 
		return $arr_source_structure;
	}
		
	public static function delSource($source_id) {
		
		$arr_source_file = static::getSources($source_id);
		
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
			WHERE id = ".(int)$source_id."
		");
		
		// If Source File had file, check whether it was used by other Source Files and delete accordingly
		if ($arr_source_file['filename']) {
			
			$res = DB::query("SELECT *
					FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')."
				WHERE filename = '".DBFunctions::strEscape($arr_source_file['filename'])."'
			");

			if (!$res->getRowCount()) {
								
				FileStore::deleteFile(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']); 
			}
		}
	}
		
	public static function cleanupTemplateLogs($num_minutes) {
		
		if (!$num_minutes) {
			return;
		}
				
		$res = DB::queryMulti("
			".DBFunctions::deleteWith(
				DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG'), 'nodegoat_itl', 'template_id',
				"JOIN ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." nodegoat_it ON (nodegoat_it.id = nodegoat_itl.template_id
					AND nodegoat_it.last_run < (NOW() - ".DBFunctions::interval($num_minutes, 'MINUTE').")
				)"
			).";
			
			DELETE FROM ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')."
				WHERE NOT EXISTS (SELECT TRUE
						FROM ".DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." nodegoat_it
					WHERE nodegoat_it.id = ".DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG').".template_id
				)
			;
		");
	}
}
