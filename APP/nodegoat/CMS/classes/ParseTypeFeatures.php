<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ParseTypeFeatures {
	
	const CONDITION_NAMESPACE_VISUALISE = 'VISUALISE';
	const CONDITION_NAMESPACE_ANALYSE = 'ANALYSE';
	const CONDITION_NAMESPACES = [self::CONDITION_NAMESPACE_VISUALISE, self::CONDITION_NAMESPACE_ANALYSE];
	
	const INPUT_VALUE_SEPERATOR = '::';
	
	public static function parseTypeCondition($type_id, $arr, $arr_files = [], $num_user_clearance = 0) {
		
		if ($arr && !$arr['object'] && !$arr['object_descriptions'] && !$arr['object_sub_details']) { // Form
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			$arr_condition_actions = static::getSetConditionActions();
			
			$arr_condition = [];
			
			foreach ($arr as $key => $arr_condition_setting) {
				
				if (!$arr_condition_setting['id']) {
					continue;
				}
				
				$condition_filter = ($arr_condition_setting['condition_filter'] ? json_decode($arr_condition_setting['condition_filter'], true) : '');
				$condition_scope = ($arr_condition_setting['condition_scope'] ? json_decode($arr_condition_setting['condition_scope'], true) : '');
				$condition_actions = [];
				
				foreach ($arr_condition_actions as $action => $arr_action) {
					
					if (!isset($arr_condition_setting['condition_actions'][$action])) {
						continue;
					}
					
					foreach ($arr_action['value'] as $value) {
						
						$type = (is_array($value) ? $value['type'] : $value);
						$return = null;
						
						switch ($type) {
							case 'emphasis':
								$return = array_filter(array_values($arr_condition_setting['condition_actions'][$action][$type]));
								break;
							case 'color':
								$return = str2Color($arr_condition_setting['condition_actions'][$action][$type]);
								break;
							case 'regex':
							
								$arr_regex = $arr_condition_setting['condition_actions'][$action][$type];
								$return = parseRegularExpression($arr_regex['pattern'], $arr_regex['flags'], $arr_regex['template'], true);
								
								break;
							case 'image':
							
								$return = '';
								$url = $arr_condition_setting['condition_actions'][$action][$type]['url'];
								
								if ($url) {
									
									if (isPath(DIR_HOME_CUSTOM_PROJECT_WORKSPACE.$url)) {
										
										$return = $url;
									}
								} else if ($arr_files[$key]['name']['condition_actions'][$action][$type]['file']) {
									
									$arr_file = $arr_files[$key];
									
									foreach ($arr_file as $key_file => $value_file) {
										
										$arr_file[$key_file] = $value_file['condition_actions'][$action][$type]['file'];
									}
									
									$str_path_file = $arr_file['tmp_name'];
									
									$str_extension = FileStore::getExtension($arr_file['name']);
									if ($str_extension == FileStore::EXTENSION_UNKNOWN) {
										$str_extension = FileStore::getExtension($str_path_file);
									}
									
									if ($str_extension != 'svg') {
										
										Labels::setVariable('type', 'svg');
										error(getLabel('msg_invalid_file_type_specific'));
									}
									
									$str_filename = hash_file('md5', $str_path_file);
									$str_filename = $str_filename.'.'.$str_extension;
									
									if (!isPath(DIR_HOME_CUSTOM_PROJECT_WORKSPACE.$str_filename)) {
										$store_file = new FileStore($arr_file, ['directory' => DIR_HOME_CUSTOM_PROJECT_WORKSPACE, 'filename' => $str_filename], FileStore::getSizeLimit(FileStore::STORE_FILE));
									}

									$return = $str_filename;
								}
								break;
							default:
								$return = $arr_condition_setting['condition_actions'][$action][$type];
						}
						
						if ($return === '' || $return === false || $return === null || $return === []) {
							continue;
						}
						
						$condition_actions[$action][$type] = $return;
					}
				}
				
				if (!$condition_actions && !$arr_condition_setting['condition_label']) {
					continue;
				}

				$arr_condition_setting_clean = [
					'condition_filter' => $condition_filter,
					'condition_scope' => $condition_scope,
					'condition_actions' => $condition_actions,
					'condition_in_object_name' => (bool)$arr_condition_setting['condition_in_object_name'],
					'condition_in_object_values' => (bool)$arr_condition_setting['condition_in_object_values'],
					'condition_in_object_nodes_object' => (bool)$arr_condition_setting['condition_in_object_nodes_object'],
					'condition_in_object_nodes_referencing' => (bool)$arr_condition_setting['condition_in_object_nodes_referencing'],
					'condition_label' => $arr_condition_setting['condition_label']
				];

				if ($arr_condition_setting['id'] == 'id') {
					$arr_condition['object'][] = $arr_condition_setting_clean;
				}				
			
				foreach ($arr_type_set['object_descriptions'] as $object_description_id => $value) {
					
					$str_id = 'object_description_'.$object_description_id;
					
					if ($arr_condition_setting['id'] == $str_id && $num_user_clearance >= $value['object_description_clearance_view']) {
						$arr_condition['object_descriptions'][$object_description_id][] = $arr_condition_setting_clean;
						break;
					}				
				}
				
				foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
					
					$str_id = 'object_sub_details_'.$object_sub_details_id.'_id';
					
					if ($arr_condition_setting['id'] == $str_id) {
						$arr_condition['object_sub_details'][$object_sub_details_id]['object_sub_details'][] = $arr_condition_setting_clean;
						break;
					}
					
					foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $value) {
						
						$str_id = 'object_sub_description_'.$object_sub_description_id;
						
						if ($arr_condition_setting['id'] == $str_id) {
							$arr_condition['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id][] = $arr_condition_setting_clean;
							break;
						}					
					}				
				}
			}
		} else {
			
			$arr_condition = $arr;
			
			$num_count_conditions = 0;
			$str_prefix = value2Hash($arr_condition); // Create a stable and reinitiable (scenario-cacheable) but unique identifier
			
			if ($arr_condition['object']) {
				
				foreach ($arr_condition['object'] as &$arr_condition_setting) {
					
					$num_count_conditions++;
					
					if (!isset($arr_condition_setting['condition_identifier'])) {
						$arr_condition_setting['condition_identifier'] = ($arr_condition_setting['condition_label'] ?: $str_prefix.$num_count_conditions);
					}
				}
			}
			
			if ($arr_condition['object_descriptions']) {
				
				foreach ($arr_condition['object_descriptions'] as $object_description_id => &$arr_condition_settings) {
					
					foreach ($arr_condition_settings as &$arr_condition_setting) {
						
						$num_count_conditions++;
						
						if (!isset($arr_condition_setting['condition_identifier'])) {
							$arr_condition_setting['condition_identifier'] = ($arr_condition_setting['condition_label'] ?: $str_prefix.$num_count_conditions);
						}
					}
				}
			}
			
			if ($arr_condition['object_sub_details']) {
				
				foreach ($arr_condition['object_sub_details'] as $object_sub_details_id => &$arr_condition_object_sub_details) {
					
					if ($arr_condition_object_sub_details['object_sub_details']) {
						
						foreach ($arr_condition_object_sub_details['object_sub_details'] as &$arr_condition_setting) {
					
							$num_count_conditions++;
							
							if (!isset($arr_condition_setting['condition_identifier'])) {
								$arr_condition_setting['condition_identifier'] = ($arr_condition_setting['condition_label'] ?: $str_prefix.$num_count_conditions);
							}
						}
					}
					
					if (!$arr_condition_object_sub_details['object_sub_descriptions']) {
						continue;
					}
							
					foreach ($arr_condition_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => &$arr_condition_settings) {

						foreach ($arr_condition_settings as &$arr_condition_setting) {
							
							$num_count_conditions++;
							
							if (!isset($arr_condition_setting['condition_identifier'])) {
								$arr_condition_setting['condition_identifier'] = ($arr_condition_setting['condition_label'] ?: $str_prefix.$num_count_conditions);
							}
						}
					}
				}
			}
		}
		
		return $arr_condition;
	}
	
	public static function parseTypeConditionNamespace($type_id, $arr_type_set_conditions, $func_check_condition_setting) {
			
		if (!$arr_type_set_conditions) {
			return $arr_type_set_conditions;
		}
		
		$func_check_conditions = function(&$arr_condition_settings) use (&$func_check_condition_setting) {
			
			foreach ($arr_condition_settings as $key => &$arr_condition_setting) {
				
				$arr_condition_setting = $func_check_condition_setting($arr_condition_setting);
				
				if ($arr_condition_setting !== false) {
					continue;
				}
				
				unset($arr_condition_settings[$key]);
			}
			
			return $arr_condition_settings;
		};
		
		if ($arr_type_set_conditions['object']) {
			
			$func_check_conditions($arr_type_set_conditions['object']);
		}
		
		if ($arr_type_set_conditions['object_descriptions']) {
		
			foreach ($arr_type_set_conditions['object_descriptions'] as $object_description_id => &$arr_condition_settings) {
				$func_check_conditions($arr_condition_settings);
			}
		}
		
		if ($arr_type_set_conditions['object_sub_details']) {
			
			foreach ($arr_type_set_conditions['object_sub_details'] as $object_sub_details_id => &$arr_conditions_object_sub_details) {
				
				if ($arr_conditions_object_sub_details['object_sub_details']) {
					$func_check_conditions($arr_conditions_object_sub_details['object_sub_details']);
				}
				
				if ($arr_conditions_object_sub_details['object_sub_descriptions']) {
					
					foreach ($arr_conditions_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => &$arr_condition_settings) {
						$func_check_conditions($arr_condition_settings);
					}
				}
			}
		}
		
		return $arr_type_set_conditions;
	}
	
	public static function checkTypeConditionNamespace($arr_condition_setting, $str_namespace) {
		
		$arr_label = Labels::parseNamespace($arr_condition_setting['condition_label']);
		
		if ($arr_label === false) {
			return $arr_condition_setting;
		}
		
		if (in_array($arr_label['namespace'], static::CONDITION_NAMESPACES)) {
			
			if ($arr_label['namespace'] == $str_namespace) {
				
				$arr_condition_setting['condition_label'] = $arr_label['label']; // Return label without namespace
				
				return $arr_condition_setting;
			} else {
				return false;
			}
		}

		return $arr_condition_setting;
	}
	
	public static function mergeTypeConditions($type_id, $arr, $do_object_name_only = false) {
	
		$arr_type_set_conditions = [];
	
		foreach ($arr as $arr_condition) {
						
			if ($do_object_name_only) {
				
				foreach (($arr_condition['object'] ?? []) as $arr_condition_setting) {
					
					if (!$arr_condition_setting['condition_in_object_name']) {
						continue;
					}
					
					$arr_type_set_conditions['object'][] = $arr_condition_setting;
				}
				
				foreach (($arr_condition['object_descriptions'] ?? []) as $object_description_id => $arr_condition_settings) {
					
					foreach ($arr_condition_settings as $arr_condition_setting) {
						
						if (!$arr_condition_setting['condition_in_object_name']) {
							continue;
						}
						
						$arr_type_set_conditions['object_descriptions'][$object_description_id][] = $arr_condition_setting;
					}
				}
				
				foreach (($arr_condition['object_sub_details'] ?? [])  as $object_sub_details_id => $arr_condition_object_sub_details) {
					
					if (!$arr_condition_object_sub_details['object_sub_descriptions']) {
						continue;
					}
							
					foreach ($arr_condition_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_condition_settings) {

						foreach ($arr_condition_settings as $arr_condition_setting) {
							
							if (!$arr_condition_setting['condition_in_object_name']) {
								continue;
							}
							
							$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id][] = $arr_condition_setting;
						}
					}
				}
			} else {

				if ($arr_condition['object']) {
					$arr_type_set_conditions['object'] = array_merge(($arr_type_set_conditions['object'] ?? []) , $arr_condition['object']);
				}
				 
				foreach (($arr_condition['object_descriptions'] ?? [])  as $object_description_id => $arr_condition_settings) {
					$arr_type_set_conditions['object_descriptions'][$object_description_id] = array_merge(($arr_type_set_conditions['object_descriptions'][$object_description_id] ?? []) , $arr_condition_settings);
				}
				
				foreach (($arr_condition['object_sub_details'] ?? [])  as $object_sub_details_id => $arr_condition_object_sub_details) {
					
					if ($arr_condition_object_sub_details['object_sub_details']) {
						$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_details'] = array_merge(($arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_details'] ?? []) , $arr_condition_object_sub_details['object_sub_details']);
					}
					
					foreach (($arr_condition_object_sub_details['object_sub_descriptions'] ?? [])  as $object_sub_description_id => $arr_condition_object_sub_description) {
						$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = array_merge(($arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] ?? []), $arr_condition_object_sub_description);
					}
				}
			}
		}
		
		return $arr_type_set_conditions;
	}
	
	public static function parseTypeModelConditions($type_id, $arr) {
		
		$arr_collect = [];
		
		foreach ((array)$arr as $cur_type_id => $arr_type_condition) {
			
			if ($arr_type_condition['condition_id']) {
				$arr_collect[$cur_type_id] = ['condition_id' => $arr_type_condition['condition_id']];
			} else if ($arr_type_condition['condition_use_current']) {
				$arr_collect[$cur_type_id] = ['condition_use_current' => true];
			}
		}
		
		return $arr_collect;
	}
	
	public static function getSetConditionActions($type = false, $action = false) {
		
		$arr = [
			'background_color' => ['id' => 'background_color', 'name' => getLabel('lbl_background_color'), 'value' => ['color']],
			'text_emphasis' => ['id' => 'text_emphasis', 'name' => getLabel('lbl_text_emphasis'), 'value' => ['emphasis']],
			'text_color' => ['id' => 'text_color', 'name' => getLabel('lbl_text_color'), 'value' => ['color']],
			'limit_text' => ['id' => 'limit_text', 'name' => getLabel('lbl_limit').' '.getLabel('lbl_text'), 'value' => ['number', ['type' => 'value', 'info' => getLabel('inf_replace_text_value')]]],
			'add_text_prefix' => ['id' => 'add_text_prefix', 'name' => getLabel('lbl_prefix').' '.getLabel('lbl_text'), 'value' => ['value', ['type' => 'check', 'info' => getLabel('inf_override_default_or_previous')]]],
			'add_text_affix' => ['id' => 'add_text_affix', 'name' => getLabel('lbl_affix').' '.getLabel('lbl_text'), 'value' => ['value', ['type' => 'check', 'info' => getLabel('inf_override_default_or_previous')]]],
			'regex_replace' => ['id' => 'regex_replace', 'name' => getLabel('lbl_regular_expression'), 'value' => [['type' => 'regex', 'info' => getLabel('inf_regular_expression_replace')], ['type' => 'check', 'info' => getLabel('inf_override_default_or_previous')]]],
			'color' => ['id' => 'color', 'name' => getLabel('lbl_highlight_color'), 'value' => ['color', ['type' => 'check', 'info' => getLabel('inf_add_to_previous')]]],
			'weight' => ['id' => 'weight', 'name' => getLabel('lbl_weight').' ('.getLabel('lbl_multiply').')', 'value' => ['number', ['type' => 'number_use_object_description_id', 'info' => getLabel('lbl_multiply_with').' '.getLabel('lbl_object_description')], ['type' => 'number_use_object_analysis_id', 'info' => getLabel('lbl_multiply_with').' '.getLabel('lbl_analysis')], ['type' => 'check', 'info' => getLabel('inf_add_to_previous')]]],
			'remove' => ['id' => 'remove', 'name' => getLabel('lbl_remove').' '.getLabel('lbl_value'), 'value' => ['check']],
			'geometry_color' => ['id' => 'geometry_color', 'name' => getLabel('lbl_geometry').' '.getLabel('lbl_color'), 'value' => ['color', 'opacity']],
			'geometry_stroke_color' => ['id' => 'geometry_stroke_color', 'name' => getLabel('lbl_geometry').' '.getLabel('lbl_stroke_color'), 'value' => ['color', 'opacity']],
			'icon' => ['id' => 'icon', 'name' => getLabel('lbl_icon'), 'value' => ['image', ['type' => 'check', 'info' => getLabel('inf_add_to_previous')]]]
		];
		
		if ($type == 'object_name') {
			
			return ['background_color' => $arr['background_color'], 'text_emphasis' => $arr['text_emphasis'], 'text_color' => $arr['text_color'], 'limit_text' => $arr['limit_text'], 'add_text_prefix' => $arr['add_text_prefix'], 'add_text_affix' => $arr['add_text_affix'], 'regex_replace' => $arr['regex_replace']];
		} else if ($type == 'object_values') {
			
			return ['background_color' => $arr['background_color'], 'text_emphasis' => $arr['text_emphasis'], 'text_color' => $arr['text_color'], 'regex_replace' => $arr['regex_replace'], 'remove' => $arr['remove']];
		} else if ($type == 'object_nodes') {
			
			return ['color' => $arr['color'], 'weight' => $arr['weight'], 'geometry_color' => $arr['geometry_color'], 'geometry_stroke_color' => $arr['geometry_stroke_color'], 'icon' => $arr['icon']];
		} else if ($type == 'object_nodes_referencing') {
			
			return ['color' => $arr['color'], 'weight' => $arr['weight']];
		} else {
			
			return $arr;
		}
	}
	
	public static function parseVisualSettings($arr_settings = false) {
		
		if (!$arr_settings) {
			$arr_settings = [];
		}
		
		if (array_key_exists('dot', $arr_settings)) {
			
			$arr_settings_use = $arr_settings;
			
			$arr_settings = [
				'capture_enable' => $arr_settings_use['capture']['enable'],
				'capture_settings' => $arr_settings_use['capture']['settings'],
				'dot_show' => $arr_settings_use['dot']['show'],
				'dot_color' => $arr_settings_use['dot']['color'],
				'dot_opacity' => $arr_settings_use['dot']['opacity'],
				'dot_color_condition' => $arr_settings_use['dot']['color_condition'],
				'dot_size_min' => $arr_settings_use['dot']['size']['min'],
				'dot_size_max' => $arr_settings_use['dot']['size']['max'],
				'dot_size_start' => $arr_settings_use['dot']['size']['start'],
				'dot_size_stop' => $arr_settings_use['dot']['size']['stop'],
				'dot_stroke_color' => $arr_settings_use['dot']['stroke_color'],
				'dot_stroke_opacity' => $arr_settings_use['dot']['stroke_opacity'],
				'dot_stroke_width' => $arr_settings_use['dot']['stroke_width'],
				'location_show' => $arr_settings_use['location']['show'],
				'location_color' => $arr_settings_use['location']['color'],
				'location_opacity' => $arr_settings_use['location']['opacity'],
				'location_size' => $arr_settings_use['location']['size'],
				'location_threshold' => $arr_settings_use['location']['threshold'],
				'location_offset' => $arr_settings_use['location']['offset'],
				'location_position' => $arr_settings_use['location']['position'],
				'location_condition' => $arr_settings_use['location']['condition'],
				'line_show' => $arr_settings_use['line']['show'],
				'line_color' => $arr_settings_use['line']['color'],
				'line_opacity' => $arr_settings_use['line']['opacity'],
				'line_width_min' => $arr_settings_use['line']['width']['min'],
				'line_width_max' => $arr_settings_use['line']['width']['max'],
				'line_offset' => $arr_settings_use['line']['offset'],
				'visual_hints_show' => $arr_settings_use['hint']['show'],
				'visual_hints_color' => $arr_settings_use['hint']['color'],
				'visual_hints_opacity' => $arr_settings_use['hint']['opacity'],
				'visual_hints_size' => $arr_settings_use['hint']['size'],
				'visual_hints_stroke_color' => $arr_settings_use['hint']['stroke_color'],
				'visual_hints_stroke_opacity' => $arr_settings_use['hint']['stroke_opacity'],
				'visual_hints_stroke_width' => $arr_settings_use['hint']['stroke_width'],
				'visual_hints_duration' => $arr_settings_use['hint']['duration'],
				'visual_hints_delay' => $arr_settings_use['hint']['delay'],
				'geometry_show' => $arr_settings_use['geometry']['show'],
				'geometry_color' => $arr_settings_use['geometry']['color'],
				'geometry_opacity' => $arr_settings_use['geometry']['opacity'],
				'geometry_stroke_color' => $arr_settings_use['geometry']['stroke_color'],
				'geometry_stroke_opacity' => $arr_settings_use['geometry']['stroke_opacity'],
				'geometry_stroke_width' => $arr_settings_use['geometry']['stroke_width'],
				'map_show' => $arr_settings_use['settings']['map_show'],
				'map_layers' => $arr_settings_use['settings']['map_layers'],
				'geo_info_show' => $arr_settings_use['settings']['geo_info_show'],
				'geo_background_color' => $arr_settings_use['settings']['geo_background_color'],
				'geo_mode' => $arr_settings_use['settings']['geo_mode'],
				'geo_display' => $arr_settings_use['settings']['geo_display'],
				'geo_advanced' => $arr_settings_use['settings']['geo_advanced'],
				'social_dot_color' => $arr_settings_use['social']['dot']['color'],
				'social_dot_size_min' => $arr_settings_use['social']['dot']['size']['min'],
				'social_dot_size_max' => $arr_settings_use['social']['dot']['size']['max'],
				'social_dot_size_start' => $arr_settings_use['social']['dot']['size']['start'],
				'social_dot_size_stop' => $arr_settings_use['social']['dot']['size']['stop'],
				'social_dot_stroke_color' => $arr_settings_use['social']['dot']['stroke_color'],
				'social_dot_stroke_width' => $arr_settings_use['social']['dot']['stroke_width'],
				'social_label_show' => $arr_settings_use['social']['label']['show'],
				'social_label_threshold' => $arr_settings_use['social']['label']['threshold'],
				'social_label_condition' => $arr_settings_use['social']['label']['condition'],
				'social_line_show' => $arr_settings_use['social']['line']['show'],
				'social_line_color' => $arr_settings_use['social']['line']['color'],
				'social_line_opacity' => $arr_settings_use['social']['line']['opacity'],
				'social_line_width_min' => $arr_settings_use['social']['line']['width']['min'],
				'social_line_width_max' => $arr_settings_use['social']['line']['width']['max'],
				'social_line_arrowhead_show' => $arr_settings_use['social']['line']['arrowhead_show'],
				'social_force' => $arr_settings_use['social']['force'],
				'social_forceatlas2' => $arr_settings_use['social']['forceatlas2'],
				'social_disconnected_dot_show' => $arr_settings_use['social']['settings']['disconnected_dot_show'],
				'social_include_location_references' => $arr_settings_use['social']['settings']['include_location_references'],
				'social_background_color' => $arr_settings_use['social']['settings']['background_color'],
				'social_display' => $arr_settings_use['social']['settings']['display'],
				'social_static_layout' => $arr_settings_use['social']['settings']['static_layout'],
				'social_static_layout_interval' => $arr_settings_use['social']['settings']['static_layout_interval'],
				'social_advanced' => $arr_settings_use['social']['settings']['social_advanced'],
				'time_bar_color' => $arr_settings_use['time']['bar']['color'],
				'time_bar_opacity' => $arr_settings_use['time']['bar']['opacity'],
				'time_background_color' => $arr_settings_use['time']['settings']['background_color'],
				'time_relative_graph' => $arr_settings_use['time']['settings']['relative_graph'],
				'time_cumulative_graph' => $arr_settings_use['time']['settings']['cumulative_graph']
			];
		}
		
		$arr_settings['capture_settings'] = ($arr_settings['capture_settings'] ? (!is_array($arr_settings['capture_settings']) ? (array)json_decode($arr_settings['capture_settings'], true) : $arr_settings['capture_settings']) : []);
		$arr_settings['location_position'] = ($arr_settings['location_position'] ? (!is_array($arr_settings['location_position']) ? (array)json_decode($arr_settings['location_position'], true) : $arr_settings['location_position']) : []);
		$arr_settings['map_layers'] = ($arr_settings['map_layers'] ? (!is_array($arr_settings['map_layers']) ? (array)json_decode($arr_settings['map_layers'], true) : $arr_settings['map_layers']) : []);
		$arr_settings['geo_advanced'] = ($arr_settings['geo_advanced'] ? (!is_array($arr_settings['geo_advanced']) ? (array)json_decode($arr_settings['geo_advanced'], true) : $arr_settings['geo_advanced']) : []);
		$arr_settings['social_force'] = ($arr_settings['social_force'] ? (!is_array($arr_settings['social_force']) ? (array)json_decode($arr_settings['social_force'], true) : $arr_settings['social_force']) : []);
		$arr_settings['social_forceatlas2'] = ($arr_settings['social_forceatlas2'] ? (!is_array($arr_settings['social_forceatlas2']) ? (array)json_decode($arr_settings['social_forceatlas2'], true) : $arr_settings['social_forceatlas2']) : []);
		$arr_settings['social_advanced'] = ($arr_settings['social_advanced'] ? (!is_array($arr_settings['social_advanced']) ? (array)json_decode($arr_settings['social_advanced'], true) : $arr_settings['social_advanced']) : []);
		
		$arr_map_layers = [];
		
		foreach ($arr_settings['map_layers'] as $arr_map_layer) {
			
			if (!$arr_map_layer['url']) {
				continue;
			}
			
			$arr_layer = ['url' => $arr_map_layer['url'], 'opacity' => 1];
			
			if (isset($arr_map_layer['opacity']) && $arr_map_layer['opacity'] >= 0 && $arr_map_layer['opacity'] < 1) {
				$arr_layer['opacity'] = (float)$arr_map_layer['opacity'];
			}
			if (!empty($arr_map_layer['attribution'])) {
				$arr_layer['attribution'] = $arr_map_layer['attribution'];
			}
			
			$arr_map_layers[] = $arr_layer;
		}
		
		if (!$arr_map_layers) {
			$arr_map_layers[] = ['url' => '//mt{s}.googleapis.com/vt?pb=!1m4!1m3!1i{z}!2i{x}!3i{y}!2m3!1e0!2sm!3i278000000!3m14!2sen-US!3sUS!5e18!12m1!1e47!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjE3fHAudjpvZmYscy50OjE4fHAudjpvZmYscy50OjIwfHMuZTpsfHAudjpvZmYscy50OjgxfHAudjpvZmYscy50OjJ8cC52Om9mZixzLnQ6NDl8cC52Om9mZixzLnQ6NTB8cy5lOmx8cC52Om9mZixzLnQ6NHxwLnY6b2ZmLHMudDo2fHMuZTpsfHAudjpvZmY!4e0!20m1!1b1', 'opacity' => 1, 'attribution' => 'Map data ©'.date('Y').' Google']; // //mt{s}.googleapis.com/vt?lyrs=m@205000000&src=apiv3&hl=en-US&x={x}&y={y}&z={z}&s=Galil&apistyle=p.v%3Aoff%2Cs.t%3A6%7Cp.v%3Aon%7Cp.c%3A%23ffc7d7e4%2Cs.t%3A82%7Cp.v%3Aon%2Cs.t%3A19%7Cp.v%3Aon&style=api%7Csmartmaps
		}

		$arr_settings['map_layers'] = $arr_map_layers;
		
		$arr = [
			'capture' => [
				'enable' => (int)((string)$arr_settings['capture_enable'] !== '' ? (bool)$arr_settings['capture_enable'] : false),
				'settings' => [
					'size' => ['width' => (float)($arr_settings['capture_settings']['size']['width'] ?? null ?: 30), 'height' => (float)($arr_settings['capture_settings']['size']['height'] ?? null ?: 20)],
					'resolution' => (int)((string)($arr_settings['capture_settings']['resolution'] ?? '') !== '' ? $arr_settings['capture_settings']['resolution'] : 300),
					'raster_include' => (int)((string)$arr_settings['capture_settings']['raster_include'] !== '' ? (bool)$arr_settings['capture_settings']['raster_include'] : false)
				]
			],
			'dot' => [
				'show' => (int)((string)$arr_settings['dot_show'] !== '' ? (bool)$arr_settings['dot_show'] : true),
				'color' => ($arr_settings['dot_color'] ?: ''),
				'opacity' => (float)((string)$arr_settings['dot_opacity'] !== '' ? $arr_settings['dot_opacity'] : 1),
				'color_condition' => ($arr_settings['dot_color_condition'] ?: ''),
				'size' => ['min' => (float)($arr_settings['dot_size_min'] ?: 8), 'max' => (float)($arr_settings['dot_size_max'] ?: 20), 'start' => ((int)$arr_settings['dot_size_start'] ?: ''), 'stop' => ((int)$arr_settings['dot_size_stop'] ?: '')],
				'stroke_color' => ($arr_settings['dot_stroke_color'] ?: '#f0f0f0'),
				'stroke_opacity' => (float)($arr_settings['dot_stroke_opacity'] ?: 1),
				'stroke_width' => ((string)$arr_settings['dot_stroke_width'] !== '' ? (float)$arr_settings['dot_stroke_width'] : 1.5)
			],
			'location' => [
				'show' => (int)((string)$arr_settings['location_show'] !== '' ? (bool)$arr_settings['location_show'] : false),
				'color' => ($arr_settings['location_color'] ?: '#000000'),
				'opacity' => (float)($arr_settings['location_opacity'] ?: 1),
				'size' => (float)($arr_settings['location_size'] ?: 8),
				'threshold' => (int)($arr_settings['location_threshold'] ?: 1),
				'offset' => ((string)$arr_settings['location_offset'] !== '' ? (int)$arr_settings['location_offset'] : -5),
				'position' => ['mode' => (int)($arr_settings['location_position']['mode'] ?: 0), 'manual' => (bool)$arr_settings['location_position']['manual']],
				'condition' => ($arr_settings['location_condition'] ?: '')
			],
			'line' => [
				'show' => (int)((string)$arr_settings['line_show'] !== '' ? (bool)$arr_settings['line_show'] : true),
				'color' => ($arr_settings['line_color'] ?: ''),
				'opacity' => (float)($arr_settings['line_opacity'] ?: 1),
				'width' =>  ['min' => (float)($arr_settings['line_width_min'] ?: 2), 'max' => (float)($arr_settings['line_width_max'] ?: 10)],
				'offset' => (int)((string)$arr_settings['line_offset'] !== '' ? $arr_settings['line_offset'] : 6)
			],
			'hint' => [
				'show' => (int)((string)$arr_settings['visual_hints_show'] !== '' ? (bool)$arr_settings['visual_hints_show'] : true),
				'color' => ($arr_settings['visual_hints_color'] ?: '#0092d9'),
				'opacity' => (float)((string)$arr_settings['visual_hints_opacity'] !== '' ? $arr_settings['visual_hints_opacity'] : 1),
				'size' => (float)($arr_settings['visual_hints_size'] ?: 20),
				'stroke_color' => ($arr_settings['visual_hints_stroke_color'] ?: '#ffffff'),
				'stroke_opacity' => (float)($arr_settings['visual_hints_stroke_opacity'] ?: 1),
				'stroke_width' => (float)((string)$arr_settings['visual_hints_stroke_width'] !== '' ? $arr_settings['visual_hints_stroke_width'] : 2),
				'duration' => (float)($arr_settings['visual_hints_duration'] ?: 0.5),
				'delay' => (float)($arr_settings['visual_hints_delay'] ?: 0)
			],
			'geometry' => [
				'show' => (int)((string)$arr_settings['geometry_show'] !== '' ? (bool)$arr_settings['geometry_show'] : true),
				'color' => ($arr_settings['geometry_color'] ?: '#666666'),
				'opacity' => (float)((string)$arr_settings['geometry_opacity'] !== '' ? $arr_settings['geometry_opacity'] : 0.4),
				'stroke_color' => ($arr_settings['geometry_stroke_color'] ?: '#444444'),
				'stroke_opacity' => (float)($arr_settings['geometry_stroke_opacity'] ?: 0.6),
				'stroke_width' => (float)((string)$arr_settings['geometry_stroke_width'] !== '' ? $arr_settings['geometry_stroke_width'] : 1)
			],
			'settings' => [
				'map_show' => (int)((string)$arr_settings['map_show'] !== '' ? (bool)$arr_settings['map_show'] : true),
				'map_layers' => $arr_settings['map_layers'],
				'geo_info_show' => (int)((string)$arr_settings['geo_info_show'] !== '' ? (bool)$arr_settings['geo_info_show'] : false),
				'geo_background_color' => ($arr_settings['geo_background_color'] ?: ''),
				'geo_mode' => (int)((string)$arr_settings['geo_mode'] !== '' ? $arr_settings['geo_mode'] : 1),
				'geo_display' => (int)((string)$arr_settings['geo_display'] !== '' ? $arr_settings['geo_display'] : 1),
				'geo_advanced' => $arr_settings['geo_advanced']
			],
			'social' => [
				'dot' => [
					'color' => ($arr_settings['social_dot_color'] ?: '#ffffff'),
					'size' => ['min' => (float)($arr_settings['social_dot_size_min'] ?: 6), 'max' => (float)($arr_settings['social_dot_size_max'] ?: 40), 'start' => ((int)$arr_settings['social_dot_size_start'] ?: ''), 'stop' => ((int)$arr_settings['social_dot_size_stop'] ?: '')],
					'stroke_color' => ($arr_settings['social_dot_stroke_color'] ?: '#aaaaaa'),
					'stroke_width' => (float)((string)$arr_settings['social_dot_stroke_width'] !== '' ? $arr_settings['social_dot_stroke_width'] : 1)
				],
				'label' => [
					'show' => (int)((string)$arr_settings['social_label_show'] !== '' ? (bool)$arr_settings['social_label_show'] : true),
					'threshold' => (float)((string)$arr_settings['social_label_threshold'] !== '' ? $arr_settings['social_label_threshold'] : 0.1),
					'condition' => ($arr_settings['social_label_condition'] ?: '')
				],
				'line' => [
					'show' => (int)((string)$arr_settings['social_line_show'] !== '' ? (bool)$arr_settings['social_line_show'] : true),
					'color' => ($arr_settings['social_line_color'] ?: '#646464'),
					'opacity' => (float)($arr_settings['social_line_opacity'] ?: 0.2),
					'width' =>  ['min' => (float)($arr_settings['social_line_width_min'] ?: 1.5), 'max' => (float)($arr_settings['social_line_width_max'] ?: 1.5)],
					'arrowhead_show' => (int)((string)$arr_settings['social_line_arrowhead_show'] !== '' ? (bool)$arr_settings['social_line_arrowhead_show'] : false)
				],
				'force' => [
					'charge' => (int)((string)$arr_settings['social_force']['charge'] !== '' ? $arr_settings['social_force']['charge'] : -40),
					'theta' => (float)((string)$arr_settings['social_force']['theta'] !== '' ? $arr_settings['social_force']['theta'] : 0.8),
					'friction' => (float)((string)$arr_settings['social_force']['friction'] !== '' ? $arr_settings['social_force']['friction'] : 0.2),
					'gravity' => (float)((string)$arr_settings['social_force']['gravity'] !== '' ? $arr_settings['social_force']['gravity'] : 0.08)
				],
				'forceatlas2' => [
					'lin_log_mode' => (bool)(isset($arr_settings['social_forceatlas2']['lin_log_mode']) ? $arr_settings['social_forceatlas2']['lin_log_mode'] : false),
					'outbound_attraction_distribution' => (bool)(isset($arr_settings['social_forceatlas2']['outbound_attraction_distribution']) ? $arr_settings['social_forceatlas2']['outbound_attraction_distribution'] : true),
					'adjust_sizes' => (bool)(isset($arr_settings['social_forceatlas2']['adjust_sizes']) ? $arr_settings['social_forceatlas2']['adjust_sizes'] : false),
					'edge_weight_influence' => (float)((string)$arr_settings['social_forceatlas2']['edge_weight_influence'] !== '' ? $arr_settings['social_forceatlas2']['edge_weight_influence'] : 0),
					'scaling_ratio' => (float)($arr_settings['social_forceatlas2']['scaling_ratio'] ?: 1),
					'strong_gravity_mode' => (bool)(isset($arr_settings['social_forceatlas2']['strong_gravity_mode']) ? $arr_settings['social_forceatlas2']['strong_gravity_mode'] : false),
					'gravity' => (float)((string)$arr_settings['social_forceatlas2']['gravity'] !== '' ? $arr_settings['social_forceatlas2']['gravity'] : 1),
					'slow_down' => (float)((string)$arr_settings['social_forceatlas2']['slow_down'] !== '' ? $arr_settings['social_forceatlas2']['slow_down'] : 1),
					'optimize_theta' => (float)((string)$arr_settings['social_forceatlas2']['optimize_theta'] !== '' ? $arr_settings['social_forceatlas2']['optimize_theta'] : 0.5)
				],
				'settings' => [
					'disconnected_dot_show' => (int)((string)$arr_settings['social_disconnected_dot_show'] !== '' ? (bool)$arr_settings['social_disconnected_dot_show'] : true),
					'include_location_references' => (int)((string)$arr_settings['social_include_location_references'] !== '' ? (bool)$arr_settings['social_include_location_references'] : false),
					'background_color' => ($arr_settings['social_background_color'] ?: ''),
					'display' => (int)((string)$arr_settings['social_display'] !== '' ? $arr_settings['social_display'] : 1),
					'static_layout' => (int)((string)$arr_settings['social_static_layout'] !== '' ? (bool)$arr_settings['social_static_layout'] : false),
					'static_layout_interval' => ((string)$arr_settings['social_static_layout_interval'] !== '' ? (float)$arr_settings['social_static_layout_interval'] : ''),
					'social_advanced' => $arr_settings['social_advanced']
				]
			],
			'time' => [
				'bar' => [
					'color' => ($arr_settings['time_bar_color'] ?: ''),
					'opacity' => (float)($arr_settings['time_bar_opacity'] ?: 0.5)
				],
				'settings' => [
					'background_color' => ($arr_settings['time_background_color'] ?: ''),
					'relative_graph' => (int)((string)$arr_settings['time_relative_graph'] !== '' ? (bool)$arr_settings['time_relative_graph'] : false),
					'cumulative_graph' => (int)((string)$arr_settings['time_cumulative_graph'] !== '' ? (bool)$arr_settings['time_cumulative_graph'] : false)
				]
			]
		];
		
		$arr['dot']['size']['min'] = min($arr['dot']['size']['min'], $arr['dot']['size']['max']);
		$arr['dot']['size']['start'] = min($arr['dot']['size']['start'], $arr['dot']['size']['stop']);
		$arr['line']['width']['min'] = min($arr['line']['width']['min'], $arr['line']['width']['max']);
		$arr['social']['dot']['size']['min'] = min($arr['social']['dot']['size']['min'], $arr['social']['dot']['size']['max']);
		$arr['social']['dot']['size']['start'] = min($arr['social']['dot']['size']['start'], $arr['social']['dot']['size']['stop']);
		$arr['social']['line']['width']['min'] = min($arr['social']['line']['width']['min'], $arr['social']['line']['width']['max']);
		
		return $arr;
	}
	
	public static function processVisualSettings($arr) {
		
		foreach ($arr['settings']['map_layers'] as &$arr_map_layer) {
		
			if (!$arr_map_layer['attribution']) {
				continue;
			}
			
			$arr_attribution = str2Array($arr_map_layer['attribution'], static::INPUT_VALUE_SEPERATOR);
			
			if (isset($arr_attribution[1])) {
				$arr_map_layer['attribution_parsed'] = ['name' => parseBody(trim($arr_attribution[0])), 'source' => parseBody(trim($arr_attribution[1]))];
			} else {
				$arr_map_layer['attribution_parsed'] = parseBody(trim($arr_attribution[0]));
			}
		}
		unset($arr_map_layer);
		
		return $arr;
	}
	
	public static function parseVisualSettingsInputAdvanced($value) {
		
		$arr = [];
		
		if (!$value) {
			return $arr;
		}
		
		$arr_settings = explode(PHP_EOL, $value);
			
		foreach ($arr_settings as $value) {
			
			$num_pos = strpos($value, ':');
			
			if (!$num_pos) {
				continue;
			}
			
			$key_setting = trim(substr($value, 0, $num_pos));
			$value_setting = trim(substr($value, $num_pos + 1));
			
			if ($key_setting && $value_setting != '') {
				$arr[$key_setting] = $value_setting;
			}
		}
		
		return $arr;
	}
	
	public static function parseVisualSettingsOutputAdvanced($arr) {
		
		$str = '';
		
		foreach ($arr as $key => $value) {
			$str .= $key.':'.$value.PHP_EOL;
		}
		
		return $str;
	}
	
	public static function parseTypeContext($type_id, $arr) {
		
		$arr_collect = [];
		
		foreach ((array)$arr['include'] as $arr_context_include) {
			
			if (!$arr_context_include['type_id'] || !$arr_context_include['scenario_id'] ) {
				continue;
			}
			
			$arr_collect['include'][$arr_context_include['type_id'].'_'.$arr_context_include['scenario_id']] = ['type_id' => $arr_context_include['type_id'], 'scenario_id' => $arr_context_include['scenario_id']];
		}
		
		return $arr_collect;
	}
	
	public static function parseTypeAnalysis($type_id, $arr, $num_user_clearance = 0) {
		
		$arr = ($arr ?? []);
		$arr_collect = [];
		
		$algorithm = $arr['algorithm'];
		
		if (!$algorithm) {
			return $arr_collect;
		}
		
		$arr_scope = StoreType::parseTypeNetwork($arr['scope'], false, $num_user_clearance);
		
		if (!$arr_scope['paths'] && !$arr_scope['types']) {
			return $arr_collect;
		}
		
		if ($arr['algorithm_settings']) {
			
			$arr_settings = $arr['algorithm_settings'][$algorithm];
			$arr_weighted = $arr['weighted_settings'];
		} else { // 'algorithm_settings' is part of the form configuration
			
			$arr_settings = $arr['settings'];
			$arr_weighted = $arr['settings']['weighted'];
		}
		unset($arr['algorithm_settings']);
		unset($arr['weighted_settings']);
			
		$arr_algorithm = AnalyseTypeObjects::getAlgorithms($algorithm);
		$func_parse = $arr_algorithm['parse'];
		
		if ($func_parse) {
			
			$arr_settings = $func_parse($arr_settings);
			
			if ($arr_settings === false) { // Settings are required, otherwise return
				return $arr_collect;
			}
		} else {
			
			$arr_settings = [];
		}
		
		if ($arr_algorithm['weighted']) {
			
			$str_mode = $arr_weighted['mode'];
			$arr_settings['weighted']['mode'] = ($str_mode == 'closeness' || $str_mode == 'distance' ? $str_mode : 'unweighted');
			
			if ($arr_settings['weighted']['mode'] != 'unweighted') {
				
				$arr_settings['weighted']['max'] = ($arr_weighted['max'] && (int)$arr_weighted['max'] > 1 ? (int)$arr_weighted['max'] : '');
			}
		}
		
		$arr_collect['algorithm'] = $algorithm;
		$arr_collect['scope'] = $arr_scope;
		$arr_collect['settings'] = $arr_settings;
		$arr_collect['user_id'] = (int)$arr['user_id'];
		$arr_collect['id'] = (int)$arr['id'];
		
		return $arr_collect;
	}
	
	public static function parseTypeAnalysisContext($type_id, $arr) {
		
		$arr = ($arr ?? []);
		$arr_collect = [];
		
		if ($arr['include']) {
				
			foreach ($arr['include'] as $arr_analysis_context_include) {
				
				if (!$arr_analysis_context_include['analysis_id'] ) {
					continue;
				}
				
				$arr_collect['include'][$arr_analysis_context_include['analysis_id']] = ['analysis_id' => $arr_analysis_context_include['analysis_id']];
			}
		}
		
		return $arr_collect;
	}
	
	public static function parseTypeFrame($type_id, $arr_settings) {
		
		if (!$arr_settings) {
			$arr_settings = [];
		}
		
		if (array_key_exists('area', $arr_settings)) {
			
			$arr_settings_use = $arr_settings;
			
			$arr_settings = [
				'area_geo_latitude' => $arr_settings_use['area']['geo']['latitude'],
				'area_geo_longitude' => $arr_settings_use['area']['geo']['longitude'],
				'area_geo_zoom_scale' => $arr_settings_use['area']['geo']['zoom']['scale'],
				'area_geo_zoom_min' => $arr_settings_use['area']['geo']['zoom']['min'],
				'area_geo_zoom_max' => $arr_settings_use['area']['geo']['zoom']['max'],
				'area_social_object_id' => $arr_settings_use['area']['social']['object_id'],
				'area_social_zoom_level' => $arr_settings_use['area']['social']['zoom']['level'],
				'area_social_zoom_min' => $arr_settings_use['area']['social']['zoom']['min'],
				'area_social_zoom_max' => $arr_settings_use['area']['social']['zoom']['max'],
				'time_bounds_date_start' => $arr_settings_use['time']['bounds']['date_start'],
				'time_bounds_date_end' => $arr_settings_use['time']['bounds']['date_end'],
				'time_selection_date_start' => $arr_settings_use['time']['selection']['date_start'],
				'time_selection_date_end' => $arr_settings_use['time']['selection']['date_end'],
				'object_subs_unknown_date' => $arr_settings_use['object_subs']['unknown']['date'],
				'object_subs_unknown_location' => $arr_settings_use['object_subs']['unknown']['location']
			];
		}
			
		return [
			'area' => [
				'geo' => [
					'latitude' => ((string)$arr_settings['area_geo_latitude'] !== '' ? (float)$arr_settings['area_geo_latitude'] : ''),
					'longitude' => ((string)$arr_settings['area_geo_longitude'] !== '' ? (float)$arr_settings['area_geo_longitude'] : ''),
					'zoom' => [
						'scale' => ($arr_settings['area_geo_zoom_scale'] ? (float)$arr_settings['area_geo_zoom_scale'] : ''),
						'min' => ($arr_settings['area_geo_zoom_min'] ? (int)$arr_settings['area_geo_zoom_min'] : ''),
						'max' => ($arr_settings['area_geo_zoom_max'] ? (int)$arr_settings['area_geo_zoom_max'] : '')
					]
				],
				'social' => [
					'object_id' => ($arr_settings['area_social_object_id'] ? (int)$arr_settings['area_social_object_id'] : ''),
					'zoom' => [
						'level' => ($arr_settings['area_social_zoom_level'] ? (float)$arr_settings['area_social_zoom_level'] : ''),
						'min' => ($arr_settings['area_social_zoom_min'] ? (int)$arr_settings['area_social_zoom_min'] : ''),
						'max' => ($arr_settings['area_social_zoom_max'] ? (int)$arr_settings['area_social_zoom_max'] : '')
					]
				]
			],
			'time' => [
				'bounds' => [
					'date_start' => (int)(FormatTypeObjects::formatToSQLValue('date', $arr_settings['time_bounds_date_start']) ?: ''),
					'date_end' => (int)(FormatTypeObjects::formatToSQLValue('date', $arr_settings['time_bounds_date_end']) ?: '')
				],
				'selection' => [
					'date_start' => (int)(FormatTypeObjects::formatToSQLValue('date', $arr_settings['time_selection_date_start']) ?: ''),
					'date_end' => (int)(FormatTypeObjects::formatToSQLValue('date', $arr_settings['time_selection_date_end']) ?: '')
				]
			],
			'object_subs' => [
				'unknown' => [
					'date' => ($arr_settings['object_subs_unknown_date'] ? $arr_settings['object_subs_unknown_date'] : 'span'),
					'location' => ($arr_settings['object_subs_unknown_location'] ? $arr_settings['object_subs_unknown_location'] : 'ignore')
				]
			]
		];
	}
}
