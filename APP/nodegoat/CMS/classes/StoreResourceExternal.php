<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreResourceExternal {
	
	protected static $arr_resources_storage = [];

	public function __construct() {
		
	}
	
	public function storeResource($id, $arr) {
		
		$str_url_headers = ($arr['url_headers'] ? value2JSON($arr['url_headers']) : '');
		
		if (!$id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')."
				("."name, description, protocol, url, url_options, url_headers, query, response_uri_value, response_uri_template, response_uri_conversion_id, response_uri_conversion_output_identifier, response_label_value, response_label_conversion_id, response_label_conversion_output_identifier) 
					VALUES
				(
					"."
					'".DBFunctions::strEscape($arr['name'])."',
					'".DBFunctions::strEscape($arr['description'])."',
					'".DBFunctions::strEscape($arr['protocol'])."',
					'".DBFunctions::strEscape($arr['url'])."',
					'".DBFunctions::strEscape($arr['url_options'])."',
					'".DBFunctions::strEscape($str_url_headers)."',
					'".DBFunctions::strEscape($arr['query'])."',
					'".DBFunctions::strEscape($arr['response_uri_value'])."',
					'".DBFunctions::strEscape($arr['response_uri_template'])."',
					".(int)$arr['response_uri_conversion_id'].",
					'".DBFunctions::strEscape($arr['response_uri_conversion_output_identifier'])."',
					'".DBFunctions::strEscape($arr['response_label_value'])."',
					".(int)$arr['response_label_conversion_id'].",
					'".DBFunctions::strEscape($arr['response_label_conversion_output_identifier'])."'
				)
			");
			
			$id = DB::lastInsertID();
		} else {
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')." SET
					name = '".DBFunctions::strEscape($arr['name'])."',
					description = '".DBFunctions::strEscape($arr['description'])."',
					protocol = '".DBFunctions::strEscape($arr['protocol'])."',
					url = '".DBFunctions::strEscape($arr['url'])."',
					url_options = '".DBFunctions::strEscape($arr['url_options'])."',
					url_headers = '".DBFunctions::strEscape($str_url_headers)."',
					query = '".DBFunctions::strEscape($arr['query'])."',
					response_uri_value = '".DBFunctions::strEscape($arr['response_uri_value'])."',
					response_uri_template = '".DBFunctions::strEscape($arr['response_uri_template'])."',
					response_uri_conversion_id = ".(int)$arr['response_uri_conversion_id'].",
					response_uri_conversion_output_identifier = '".DBFunctions::strEscape($arr['response_uri_conversion_output_identifier'])."',
					response_label_value = '".DBFunctions::strEscape($arr['response_label_value'])."',
					response_label_conversion_id = ".(int)$arr['response_label_conversion_id'].",
					response_label_conversion_output_identifier = '".DBFunctions::strEscape($arr['response_label_conversion_output_identifier'])."'
				WHERE id = ".(int)$id."
			");
		}
		
		$arr_identifiers = [];
		$count = 0;
		
		foreach ((array)$arr['response_values'] as $name => $arr_value) {
			
			if (!$name || !$arr_value['value'] || $arr_identifiers[$name]) {
				continue;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES')."
				(resource_id, name, value, conversion_id, conversion_output_identifier, sort)
					VALUES
				(".(int)$id.", '".DBFunctions::strEscape($name)."', '".DBFunctions::strEscape($arr_value['value'])."', ".(int)$arr_value['conversion_id'].", '".DBFunctions::strEscape($arr_value['conversion_output_identifier'])."', ".$count.")
				".DBFunctions::onConflict('resource_id, name', ['value', 'conversion_id', 'conversion_output_identifier', 'sort'])."
			");
			
			$arr_identifiers[$name] = DBFunctions::strEscape($name);
			
			$count++;
		}
		
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES')."
			WHERE resource_id = ".(int)$id."
				".($arr_identifiers ? "AND name NOT IN('".implode("','", $arr_identifiers)."')" : "")."
		");
	}
	
	public function storeConversion($id, $arr) {
		
		$arr['output_placeholder'] = json_decode($arr['output_placeholder'], true);
		
		if (is_array($arr['output_placeholder'])) {
			
			$output = [];
			
			foreach ($arr['output_placeholder'] as $key => $value) {
				
				$output[$key] = (is_array($value) ? '[]' : '');
			}
		} else {
			
			$output = '';
		}
		
		$arr['output_placeholder'] = value2JSON($output);

		if (!$id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_CONVERSIONS')."
				("."name, description, script, output_placeholder, input_placeholder) 
					VALUES
				(
					"."
					'".DBFunctions::strEscape($arr['name'])."',
					'".DBFunctions::strEscape($arr['description'])."',
					'".DBFunctions::strEscape($arr['script'])."',
					'".DBFunctions::strEscape($arr['output_placeholder'])."',
					'".DBFunctions::strEscape($arr['input_placeholder'])."'
				)
			");
			
			$id = DB::lastInsertID();
		} else {
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_CONVERSIONS')." SET
					name = '".DBFunctions::strEscape($arr['name'])."',
					description = '".DBFunctions::strEscape($arr['description'])."',
					script = '".DBFunctions::strEscape($arr['script'])."',
					output_placeholder = '".DBFunctions::strEscape($arr['output_placeholder'])."',
					input_placeholder = '".DBFunctions::strEscape($arr['input_placeholder'])."'
				WHERE id = ".(int)$id."
			");
		}
	}
	
	public function delResource($resource_id) {
					
		$res = DB::queryMulti("
			".DBFunctions::deleteWith(
				DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES'), 'nodegoat_ldrv', 'resource_id',
				"JOIN ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')." nodegoat_ldr ON (
					nodegoat_ldr.id = nodegoat_ldrv.resource_id
						"."
						AND nodegoat_ldr.id = ".(int)$resource_id."
				)"
			).";
			
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')."
				WHERE "."TRUE"."
					AND id = ".(int)$resource_id.";
		");
	}

	public function delConversion($conversion_id) {
					
		$res = DB::query("
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_CONVERSIONS')."
				WHERE "."TRUE"."
					AND id = ".(int)$conversion_id."
		");
	}
		
	public static function getResources($id = false) {
			
		if ($id) {
			
			if (isset(self::$arr_resources_storage[$id])) {
				return self::$arr_resources_storage[$id];
			}
		}
		
		$arr = [];
		
		$res = DB::query("SELECT nodegoat_ldr.*,
			nodegoat_ldrv.name AS response_values_name, nodegoat_ldrv.value AS response_values_value, nodegoat_ldrv.conversion_id AS response_values_conversion_id, nodegoat_ldrv.conversion_output_identifier AS response_values_conversion_output_identifier
				FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')." nodegoat_ldr
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES')." nodegoat_ldrv ON (nodegoat_ldrv.resource_id = nodegoat_ldr.id)
			WHERE "."TRUE"."
				".($id ? "AND nodegoat_ldr.id = ".(int)$id."" : "")."
			ORDER BY ".(!$id ? "nodegoat_ldr.name ASC, " : "")."nodegoat_ldrv.sort
		");
		
		$arr_conversion_ids = [];
				 
		while ($arr_row = $res->fetchAssoc()) {
			
			$cur_id = $arr_row['id'];
			
			if (!$arr[$cur_id]) {
				
				$arr[$cur_id] = $arr_row;
				
				$arr[$cur_id]['url_headers'] = json_decode($arr_row['url_headers'], true);
				
				$conversion_id = $arr_row['response_uri_conversion_id'];
			
				$arr[$cur_id]['response_uri'] = [
					'value' => $arr_row['response_uri_value'],
					'conversion_id' => $arr_row['response_uri_conversion_id'],
					'conversion_output_identifier' => $arr_row['response_uri_conversion_output_identifier']
				];
				
				if ($conversion_id) {
					$arr_conversion_ids[$conversion_id] = $conversion_id;
				}
				
				$conversion_id = $arr_row['response_label_conversion_id'];
				
				$arr[$cur_id]['response_label'] = [
					'value' => $arr_row['response_label_value'],
					'conversion_id' => $arr_row['response_label_conversion_id'],
					'conversion_output_identifier' => $arr_row['response_label_conversion_output_identifier']
				];
					
				if ($conversion_id) {
					$arr_conversion_ids[$conversion_id] = $conversion_id;
				}
				
				$arr[$cur_id]['response_values'] = [];
				
				unset($arr[$cur_id]['response_values_name'], $arr[$cur_id]['response_values_value']);
			}
			
			if ($arr_row['response_values_name']) {
				
				$conversion_id = $arr_row['response_values_conversion_id'];
				
				$arr[$cur_id]['response_values'][$arr_row['response_values_name']] = [
					'value' => $arr_row['response_values_value'],
					'conversion_id' => $conversion_id,
					'conversion_output_identifier' => $arr_row['response_values_conversion_output_identifier']
				];
				
				if ($conversion_id) {
					$arr_conversion_ids[$conversion_id] = $conversion_id;
				}
			}
		}
		
		if ($arr_conversion_ids) {
			
			$arr_conversions = static::getConversions($arr_conversion_ids);
			
			$func_update = function(&$arr_value) use ($arr_conversions) {
				
				$arr_conversion = $arr_conversions[$arr_value['conversion_id']];
				
				if (!$arr_conversion) {
					return;
				}
				
				$arr_value += [
					'conversion_script' => $arr_conversion['script'],
					'conversion_output_placeholder' => $arr_conversion['output_placeholder'],
					'conversion_input_placeholder' => $arr_conversion['input_placeholder'],
				];
			};
			
			foreach ($arr as $cur_id => &$arr_resource) {
				
				$func_update($arr_resource['response_uri']);
				$func_update($arr_resource['response_label']);
				
				foreach ($arr_resource['response_values'] as &$arr_value) {
					$func_update($arr_value);
				}
			}
		}
		
		if ($id) {
			
			$arr = current($arr);
			self::$arr_resources_storage[$id] = $arr;
		}

		return $arr;
	}

	public static function getConversions($arr_conversion_ids = false) {
				
		$sql_conversion_ids = (is_array($arr_conversion_ids) ? implode(',', arrParseRecursive($arr_conversion_ids, TYPE_INTEGER)) : (int)$arr_conversion_ids);
				
		$arr = [];
		
		$res = DB::query("SELECT nodegoat_ldc.*
				FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_CONVERSIONS')." nodegoat_ldc
			WHERE "."TRUE"."
				".($arr_conversion_ids ? "AND nodegoat_ldc.id IN (".$sql_conversion_ids.")" : "")."
			ORDER BY nodegoat_ldc.name ASC
		");
				 
		while ($arr_row = $res->fetchAssoc()) {
			
			$arr_row['output_placeholder'] = json_decode($arr_row['output_placeholder'], true);
			
			$arr_identifiers = [];
			
			if (is_array($arr_row['output_placeholder'])) {
				
				$arr_identifiers[''] = ['id' => '', 'name' => '[]']; // Whole array
				
				foreach ($arr_row['output_placeholder'] as $key => $value) {
					$arr_identifiers[$key] = ['id' => $key, 'name' => $key.': '.($value ?: '""')];
				}
			} else {
				
				$arr_identifiers[''] = ['id' => '', 'name' => '""'];
			}
			
			$arr_row['output_identifiers'] = $arr_identifiers;
			
			$arr[$arr_row['id']] = $arr_row;
		}	
		
		if ($arr_conversion_ids && !is_array($arr_conversion_ids)) {
			$arr = current($arr);
		}

		return $arr;
	}
}
