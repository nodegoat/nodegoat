<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class IngestTypeObjectsResourceExternal extends IngestTypeObjects {
		
	public static function streamSource($stream, $external, $arr_filter = false, $num_limit = false) {
		
		$arr_values = $external->getResponseValues(true);
		$arr_results = $external->getResultValues(false);

		foreach ($arr_results as $arr_result) {
			
			if ($arr_filter && !static::matchFilterSource($arr_filter, $arr_result)) {
				continue;
			}
			
			$arr_data = [];

			foreach ($arr_values as $name => $arr_value) {
				
				$arr_data[$name] = $arr_result[$name];
			}
			
			$stream->stream([$arr_data]);
		}
	}
	
	public static function streamSourceByObjectId($stream, $external, $object_id = false, $arr_filter = false) {
		
		$arr_values = $external->getResponseValues(true);
		$arr_results = $external->getResultValues(false);
		
		foreach ($arr_results as $arr_result) {
			
			if ($arr_filter && !static::matchFilterSource($arr_filter, $arr_result)) {
				continue;
			}
			
			$arr_data = [];
			
			if ($object_id) {
				$arr_data['object_id'] = $object_id;
			}
			
			foreach ($arr_values as $name => $arr_value) {
				
				$arr_data[$name] = $arr_result[$name];
			}
			
			$stream->stream([$arr_data]);
		}
	}
	
	public static function getSourceStream($file) {
		
		$stream = new StreamJSONOutput($file);
		
		$arr_output = [$stream->getStream('', '')];
		
		$stream->open($arr_output);
		
		return $stream;
	}
	
	public static function checkTemplateSourceCompatibility($arr_template, $arr_resource) {
		
		if (!$arr_resource || !$arr_template['pointers']) {
			return false;
		}
		
		$external = new ResourceExternal($arr_resource);
		$arr_values = $external->getResponseValues(true);
		$arr_variables = $external->getQueryVariablesFlat(false);
		
		foreach ($arr_template['pointers'] as $type => $arr_type_pointers) {
			
			if ($type == 'filter_object_identifier') {
				continue;
			}
			
			foreach ($arr_type_pointers as $arr_pointer) {
				
				if (!$arr_pointer['pointer_heading']) {
					continue;
				}
				
				switch ($type) {
					case 'query_value':
					case 'query_object_value':
						
						if (!arrHasValuesRecursive('id', $arr_pointer['pointer_heading'], $arr_variables)) {
							return false;
						}
						
						break;
					case 'map':
					case 'filter_value':
					case 'filter_object_value':
						
						if (!$arr_values[$arr_pointer['pointer_heading']]) {
							return false;
						}
						
						break;
				}
			}
		}
		
		return true;
	}
}
