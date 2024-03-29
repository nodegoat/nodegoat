<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

abstract class FormatTypeObjectsBase {
		
	const DATE_INT_MIN = -9000000000000000000;
	const DATE_INT_MAX = 9000000000000000000;
	const DATE_INT_CALC = 200000000000000000; // DATE_INT_MAX + DATE_INT_CALC is just within 64-bit margin.
	const DATE_INT_SEQUENCE_NULL = 5000;
	const VALUE_NUMERIC_DECIMALS = 10;
	const VALUE_NUMERIC_MULTIPLIER = 10 ** 10;
	const GEOMETRY_SRID = 4326; // 4326 for WGS84
	const GEOMETRY_COORDINATE_DECIMALS = 20;
	const TEXT_TAG_OBJECT = 1;
	const TEXT_TAG_ENTITY = 2;
	const TAGCODE_PARSE_TEXT_OBJECT_OPEN = '\[object=([0-9-_\|]+)\]';
	const TAGCODE_PARSE_TEXT_OBJECT_CLOSE = '\[\/object(?:=([0-9-_\|]+))?\]';
	const TAGCODE_PARSE_TEXT_ENTITY_OPEN = '\[entity(?:=([^\]]*))?\]';
	const TAGCODE_PARSE_TEXT_ENTITY_CLOSE = '\[\/entity\]';
	const PIXEL_TO_CENTIMETER = 0.026458333;
	
	const FORMAT_DATE_YMD = 1;
	/*const FORMAT_X = 2;
	const FORMAT_XX = 4;*/
	
	const FORMAT_MULTI_SEPERATOR = ' '; // U+2002 - EN SPACE
	const FORMAT_MULTI_SEPERATOR_LIST = ', ';
	
	protected static $func_create_link = 'data_view::createTypeObjectLink';
	protected static $func_create_link_tag = 'data_view::createTypeObjectLinkTag';
	protected static $str_command_hover = false;
	
	public static $num_media_preview_height = 50;
	public static $num_text_preview_characters = 500;
	
	public static function setInteractionCreateLink($func) {
		static::$func_create_link = ($func ?: 'data_view::createTypeObjectLink');
	}
	public static function getInteractionCreateLink() {
		return static::$func_create_link;
	}
	public static function setInteractionCreateLinkTag($func) {
		static::$func_create_link_tag = ($func ?: 'data_view::createTypeObjectLinkTag');
	}
	public static function getInteractionCreateLinkTag() {
		return static::$func_create_link_tag;
	}
	public static function setInteractionCommandHover($str) {
		static::$str_command_hover = ($str ?: false);
	}
	public static function getInteractionCommandHover() {
		return static::$str_command_hover;
	}
	
	public static function clearObjectDefinitionText($str_text, $target_tag = false) {
		
		if (!$target_tag || $target_tag == static::TEXT_TAG_OBJECT) {
			
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_OBJECT_OPEN.'/s', '', $str_text);
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_OBJECT_CLOSE.'/s', '', $str_text);
		}
		
		if (!$target_tag || $target_tag == static::TEXT_TAG_ENTITY) {
			
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_ENTITY_OPEN.'/s', '', $str_text);
			$str_text = preg_replace('/'.static::TAGCODE_PARSE_TEXT_ENTITY_CLOSE.'/s', '', $str_text);
		}
		
		return $str_text;
	}
	
	public static function updateObjectDefinitionTextTagsObject($str_text, $arr_types_objects, $any_type = false) {
		
		$num_length_text = 0;
		$num_length_tag_open = 0;
		$num_length_tag_close = 0;
		
		$func_parse = function($num_pos_close, $num_pos_open, $str_tag_identifiers, $has_close_tag_identifiers = false) use (&$str_text, &$num_length_text, &$num_length_tag_open, &$num_length_tag_close, $arr_types_objects, $any_type) {
			
			$num_length_tag_identifiers = strlen($str_tag_identifiers);
			$arr_tag_identifiers = explode('|', $str_tag_identifiers);
			$has_change = false;
			
			foreach ($arr_tag_identifiers as $key => $str_tag_identifier) {
				
				$arr_tag = explode('_', $str_tag_identifier);
				
				$arr_type_objects = ($arr_types_objects[$arr_tag[0]] ?? null);
				
				if ($arr_type_objects === null) {
					
					if (!$any_type) { // Keep/ignore when type is not included
						continue;
					} else {
						unset($arr_tag_identifiers[$key]);
						$has_change = true;
					}
				} else if ($arr_type_objects === false) {
					
					unset($arr_tag_identifiers[$key]);
					$has_change = true;
				} else {
					
					$is_active = ($arr_type_objects[$arr_tag[1]] ?? null);
					
					if ($is_active === false) {
					
						unset($arr_tag_identifiers[$key]);
						$has_change = true;
					}
				}
			}
			
			if ($has_change) {
				
				if (!$arr_tag_identifiers) { // Remove whole tag
					
					$num_length = ($num_length_tag_open + $num_length_tag_identifiers + 1);
					$str_text = substr_replace($str_text, '', $num_pos_open, $num_length);
					
					$num_pos_close -= $num_length;
					$num_length_text -= $num_length;
					
					$num_length = ($num_length_tag_close + ($has_close_tag_identifiers ? $num_length_tag_identifiers : 0) + 1);
					$str_text = substr_replace($str_text, '', $num_pos_close, $num_length);

					$num_length_text -= $num_length;
				} else { // Update tag identifiers
					
					$str_tag_identifiers = implode('|', $arr_tag_identifiers);
					$num_length = strlen($str_tag_identifiers);
					
					$num_length_tag_identifiers_updated = ($num_length_tag_identifiers - $num_length);
					
					$str_text = substr_replace($str_text, $str_tag_identifiers, ($num_pos_open + $num_length_tag_open), $num_length_tag_identifiers);

					$num_pos_close -= $num_length_tag_identifiers_updated;
					$num_length_text -= $num_length_tag_identifiers_updated;
					
					if ($has_close_tag_identifiers) {
						
						$str_text = substr_replace($str_text, $str_tag_identifiers, ($num_pos_close + $num_length_tag_close), $num_length_tag_identifiers);

						$num_length_text -= $num_length_tag_identifiers_updated;
					}
															
					$num_pos_close += ($num_length_tag_close + ($has_close_tag_identifiers ? $num_length_tag_identifiers_updated : 0) + 1); // Set position after closing tag
				}				
			} else {
				
				$num_pos_close += ($num_length_tag_close + ($has_close_tag_identifiers ? $num_length_tag_identifiers : 0) + 1); // Set position after closing tag
			}
			
			return $num_pos_close;
		};
		
		$num_length_tag_open = 8;
		$num_length_tag_close = 9;
		
		$num_length_text = strlen($str_text);	
		$num_pos_close = strpos($str_text, '[/object=');

		while ($num_pos_close !== false) {
			
			$num_pos_close_end = strpos($str_text, ']', ($num_pos_close + $num_length_tag_close));
			
			$str_tag_identifiers = substr($str_text, ($num_pos_close + $num_length_tag_close), ($num_pos_close_end - ($num_pos_close + $num_length_tag_close)));

			$num_pos_open = strrpos($str_text, '[object='.$str_tag_identifiers.']', ($num_pos_close - $num_length_text));

			$num_pos_close = $func_parse($num_pos_close, $num_pos_open, $str_tag_identifiers, true);
			
			$num_pos_close = strpos($str_text, '[/object=', $num_pos_close);
		}
		
		$num_length_tag_close = 8;
		
		$num_length_text = strlen($str_text);	
		$num_pos_close = strpos($str_text, '[/object]');

		while ($num_pos_close !== false) {

			$num_pos_open = strrpos($str_text, '[object=', ($num_pos_close - $num_length_text));
			$num_pos_open_end = strpos($str_text, ']', ($num_pos_open + $num_length_tag_open));
			
			$str_tag_identifiers = substr($str_text, ($num_pos_open + $num_length_tag_open), ($num_pos_open_end - ($num_pos_open + $num_length_tag_open)));

			$num_pos_close = $func_parse($num_pos_close, $num_pos_open, $str_tag_identifiers, false);
			
			$num_pos_close = strpos($str_text, '[/object]', $num_pos_close);
		}
		
		return $str_text;
	}

	public static function formatToCleanValue($type, $value, $arr_type_settings = [], $mode_format = null) { // From raw database to display
		
		$format = false;
			
		switch ($type) {
			case 'numeric':
				$format = ($value ? self::int2Numeric($value) : '');
				break;
			case 'date':
			
				$do_mode_ymd = ($mode_format !== null && ($mode_format & static::FORMAT_DATE_YMD));
				
				$format = ($value ? self::int2Date($value, $do_mode_ymd) : '');
				break;
			case 'chronology':
				$format = self::formatToChronologyDetails($value);
				break;
			case 'geometry':
				$format = self::formatToGeometrySummary($value);
				break;
			case 'boolean':
				$format = ($value !== '' && $value !== null ? ($value == 1 || $value === 'yes' ? getLabel('lbl_yes') : getLabel('lbl_no')) : '');
				break;
			case 'module':
				
				$module = EnucleateValueTypeModule::init($arr_type_settings['type']);
				$module->setValue(JSON2Value($value));
				
				$format = $module->enucleate(EnucleateValueTypeModule::VIEW_TEXT);

				break;
			case 'external_module':
			case 'reconcile_module':
			case 'reversal_module':
				$format = $value;
				break;
			default:
				$format = $value;
		}
		
		return $format;
	}
		
	public static function formatToHTMLValue($type, $value, $reference, $arr_type_settings = [], $arr_extra = []) { // From formatted database to display
		
		$do_group = true;
		$format = false;
		
		$func_create_link = ($arr_extra['link'] ?? static::$func_create_link);
				
		switch ($type) {
			case 'reference':
			case 'type':
			case 'classification':
			case 'reversed_classification':
			case 'object_description':
			
				if ($arr_extra['has_multi']) {
					
					$arr_html = [];
					
					foreach ($reference as $key => $object_id) {
						
						$str_html = ($arr_extra['style'] ? '<span style="'.$arr_extra['style'].'">'.$value[$key].'</span>' : $value[$key]);
						
						$arr_html[] = $func_create_link($arr_extra['ref_type_id'], $object_id, $str_html);
					}
					
					$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
				} else {
					
					$str_html = ($arr_extra['style'] ? '<span style="'.$arr_extra['style'].'">'.$value.'</span>' : $value);
					
					$format = $func_create_link($arr_extra['ref_type_id'], $reference, $str_html);
				}
				
				$do_group = false;
				
				break;
			case 'int':
			case 'numeric':
			case 'float':
			case 'serial_varchar':
			case '':
								
				if (is_array($value)) {
					$format = '<span>'.implode('</span><span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span><span>', arrParseRecursive($value, 'strEscapeHTML')).'</span>';
				} else {
					$format = strEscapeHTML($value);
				}
				
				break;
			case 'text':
				
				$value = strEscapeHTML($value);
				$format = parseBody($value);
				
				break;
			case 'text_layout':
			case 'text_tags':
				
				if ($arr_type_settings['html']) {
					$value = parseBody($value, ['sanitise' => true]);
				} else {
					$value = strEscapeHTML($value);
					$value = parseBody($value);
				}

				$html_word_count = Response::addParseDelay($value, function($value) {
					
					$count = str_word_count(strip_tags($value));
					
					if ($count > 1) {
						return '<p>'.getLabel('lbl_word_count', 'L', true).': '.$count.'</p>';
					} else {
						return '';
					}
				}, true);
				
				$arr_type_references = [];
				$str_command_hover = static::$str_command_hover;
			
				if ($reference) {
					$arr_type_references = $reference;
				}
				
				if ($arr_type_references && ($arr_type_settings['marginalia'] || keyIsUncontested('list', $arr_type_settings))) { // If there are tags
					
					$arr_types_all = StoreType::getTypes(); // Source can be any type
					$arr_html_tabs = [];
					
					$arr_collect_type_object_names = [];
					
					foreach ($arr_type_references as $ref_type_id => $arr_ref_objects) {
					
						foreach($arr_ref_objects as $cur_object_id => $arr_reference) {
							
							$arr_reference = (is_array($arr_reference) ? ($arr_reference['object_definition_ref_object_name'] ?: ($arr_reference['object_sub_definition_ref_object_name'] ?: $arr_reference)) : $arr_reference);
							
							$html_object_name = '';
							$html_object_input = false;
							
							if (is_array($arr_reference)) {
								$html_object_name = $arr_reference['name'];
								$html_object_input = $arr_reference['input'];
							} else {
								$html_object_name = $arr_reference;
							}
							
							if ($arr_collect_type_object_names[$ref_type_id][$cur_object_id.'_'.$html_object_name]) { // Check for duplicate ID-name/value combination
								continue;
							}
							
							$html = '<p class="'.$ref_type_id.'_'.$cur_object_id.'">'.($html_object_input ? $html_object_input : '').$func_create_link($ref_type_id, $cur_object_id, $html_object_name).'</p>';
							$arr_collect_type_object_names[$ref_type_id][$cur_object_id.'_'.$html_object_name] = $html;
						}
						
						if (!$arr_collect_type_object_names[$ref_type_id]) {
							continue;
						}

						ksort($arr_collect_type_object_names[$ref_type_id]);
						$arr_html_tabs['content'][] = '<div>
							'.implode('', $arr_collect_type_object_names[$ref_type_id]).'
						</div>';
						$arr_html_tabs['links'][] = '<li><a href="#">'.Labels::parseTextVariables($arr_types_all[$ref_type_id]['name']).'</a></li>';
					}
					
					if ($arr_html_tabs) {
						
						if ($arr_type_settings['marginalia']) {
							
							$html_page = '<div class="page">
								<div class="body">'.$value.'</div>
								<div class="marginalia"></div>
							</div>';
						} else {
							
							$html_page = '<div class="body">'.$value.'</div>';
						}
						
						$html_value = '<div>'
							.$html_page
							.$html_word_count
						.'</div>
						<div class="text-references">
							<div class="tabs">
								<ul>
									'.implode('', $arr_html_tabs['links']).'
								</ul>
								'.implode('', $arr_html_tabs['content']).'
							</div>
						</div>';
						
						if (keyIsUncontested('list', $arr_type_settings)) {
							
							$format = '<div class="tabs '.$type.'"'.($str_command_hover ? ' data-command_hover="'.$str_command_hover.'"' : '').'>
								<ul>
									<li><a href="#">'.getLabel('lbl_text').'</a></li>
									<li><a href="#">'.getLabel('lbl_referencing').'</a></li>

								</ul>
								'.$html_value.'
							</div>';
						} else {
							
							$format = '<div class="'.$type.'"'.($str_command_hover ? ' data-command_hover="'.$str_command_hover.'"' : '').'>
								'.$html_value.'
							</div>';
						}
						
						break;
					}
				}
				
				$format = '<div class="body '.$type.'"'.($str_command_hover ? ' data-command_hover="'.$str_command_hover.'"' : '').'>'
					.$value
				.'</div>'.$html_word_count;
				
				break;
			case 'reversed_collection':
				
				$arr_html = [];
				
				if ($reference) {
					
					foreach ($reference as $key => $arr_reference_type_objects) { // Reversals are is_dynamic and has_multi and therefore grouped values
						
						foreach ($arr_reference_type_objects as $type_id => $arr_reference_objects) {
						
							foreach ($arr_reference_objects as $object_id => $arr_reference_object) {
								
								if (isset($arr_html[$object_id])) {
									continue;
								}
								
								$str_object_name = ($arr_reference_object['object_definition_ref_object_name'] ?? $arr_reference_object['object_sub_definition_ref_object_name']);
								
								$str_html = ($arr_extra['style'] ? '<span style="'.$arr_extra['style'].'">'.$str_object_name.'</span>' : $str_object_name);
							
								$arr_html[$object_id] = $func_create_link($type_id, $object_id, $str_html);
							}
						}
					}
				}
				
				$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
				$do_group = false;
				
				break;
			case 'reversed_collection_resource_path':
				
				$arr_html = [];
				
				if ($value) {
					
					foreach ($value as $key => $str_path) { // Reversals are is_dynamic and has_multi and therefore grouped values
						
						/*foreach ($reference[$key] as $type_id => $arr_reference_objects) {
							
							foreach ($arr_reference_objects as $object_id => $arr_reference_object) {
								
								$str_object_name = ($arr_reference_object['object_definition_ref_object_name'] ?? $arr_reference_object['object_sub_definition_ref_object_name']);
							
								$str_html = $func_create_link($type_id, $object_id, $str_object_name);
							
								$str_path = str_replace(GenerateTypeObjects::NAME_REFERENCE_TYPE_OBJECT_OPEN.$type_id.'_'.$object_id.GenerateTypeObjects::NAME_REFERENCE_TYPE_OBJECT_CLOSE, $str_html, $str_path);
							}
						}*/
						
						$str_path = preg_replace_callback(
							'/'.FormatTypeObjects::TAGCODE_PARSE_TEXT_OBJECT_OPEN.'/s',
							function ($arr_matches) use ($func_create_link) {
								$arr_type_object = explode('_', $arr_matches[1]);
								return $func_create_link($arr_type_object[0], $arr_type_object[1], null);
							},
							$str_path
						);
						$str_path = preg_replace('/'.FormatTypeObjects::TAGCODE_PARSE_TEXT_OBJECT_CLOSE.'/s', '</span>', $str_path);
						
						$arr_html[] = nl2br($str_path);
					}
				}
				
				$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
					
				break;
			case 'media':
			case 'media_external':
			
				if ($value) {
					
					$show_url = ($arr_type_settings['display'] && $arr_type_settings['display'] == 'url');
					
					if (is_array($value)) {
						
						$arr_html = [];
							
						foreach ($value as $media) {
							
							$media = new EnucleateMedia($media, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
							
							if ($show_url) {
								
								$str_url = $media->enucleate(EnucleateMedia::VIEW_URL);
								$str_url = strEscapeHTML($str_url);
								$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
							} else {
								$arr_html[] = $media->enucleate();
							}
						}
						
						if (!$show_url && count($arr_html) > 1) {
							
							$format = '<div class="album">'.implode('', $arr_html).'</div>';
						} else {
							$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
						}
					} else {
								
						$media = new EnucleateMedia($value, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
						
						if ($show_url) {
							
							$str_url = $media->enucleate(EnucleateMedia::VIEW_URL);
							$str_url = strEscapeHTML($str_url);
							$format = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
						} else {
							$format = $media->enucleate();
						}
					}
				}
				break;
			case 'external':
				
				if (is_array($value)) {
						
					$arr_html = [];
					
					foreach ($value as $ref_value) {
													
						$reference = new ResourceExternal(StoreResourceExternal::getResources($arr_type_settings['id']), $ref_value);
						$arr_html[] = '<span>'.$reference->getURL().'</span>';
					}
					
					$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
				} else {
					
					$reference = new ResourceExternal(StoreResourceExternal::getResources($arr_type_settings['id']), $value);
					$format = $reference->getURL();
				}
				break;
			case 'module':
				
				$module = EnucleateValueTypeModule::init($arr_type_settings['type']);
				$module->setConfiguration($arr_extra);
				$module->setValue(JSON2Value($value));
				
				$format = $module->enucleate();

				break;
			case 'external_module':
			case 'reconcile_module':
			case 'reversal_module':
			
				$format = strEscapeHTML($value);
				
				break;
			case 'date_cycle':
			
				$format = getLabel('unit_month').' '.(int)$value[0].'<br />'.getLabel('unit_day').' '.(int)$value[1];
				
				break;
			case 'date_compute':
			
				$format = getLabel('unit_year').' +'.(int)$value[0].'<br />'.getLabel('unit_month').' +'.(int)$value[1].'<br />'.getLabel('unit_day').' +'.(int)$value[2];
			
				break;
			default:
				$format = strEscapeHTML($value);
		}
		
		if ($do_group) {
			
			if ($arr_extra['style']) {
				
				if ($arr_extra['has_multi']) {
					$format = '<div style="'.$arr_extra['style'].'">'.$format.'</div>';
				} else {
					$format = '<span style="'.$arr_extra['style'].'">'.$format.'</span>';
				}
			}
		}
		
		return $format;
	}

	public static function formatToHTMLPreviewValue($type, $value, $reference, $arr_type_settings = [], $arr_extra = []) { // From formatted database to preview display
		
		$do_group = true;
		$format = false;
		
		switch ($type) {
			case 'int':
			case 'numeric':
			case 'float':
			case 'serial_varchar':
			case '':
						
				if (is_array($value)) {
					$format = implode(($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR), $value);
				} else {
					$format = $value;
				}
				
				$format = strEscapeHTML($format);
				
				break;
			case 'media':
			case 'media_external':
			
				if ($value) {
					
					$show_url = ($arr_type_settings['display'] && $arr_type_settings['display'] == 'url');
					
					if (is_array($value)) {
						
						$arr_html = [];
							
						foreach ($value as $media) {
							
							$media = new EnucleateMedia($media, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
							$media->setSizing(false, static::$num_media_preview_height);
							
							if ($show_url) {
								$str_url = $media->enucleate(EnucleateMedia::VIEW_URL);
								$str_url = strEscapeHTML($str_url);
								$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
							} else {
								$arr_html[] = $media->enucleate();
							}
						}
						
						$format = implode(($show_url ? ($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR_LIST) : ''), $arr_html);
					} else {
								
						$media = new EnucleateMedia($value, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
						$media->setSizing(false, static::$num_media_preview_height);
						
						if ($show_url) {
							$str_url = $media->enucleate(EnucleateMedia::VIEW_URL);
							$str_url = strEscapeHTML($str_url);
							$format = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
						} else {
							$format = $media->enucleate();
						}
					}
				}
				break;
			case 'module':
				
				if ($value) {
						
					$module = EnucleateValueTypeModule::init($arr_type_settings['type']);
					$module->setConfiguration(($arr_extra ?: []) + ['size' => ['height' => static::$num_media_preview_height]]);
					$module->setValue(JSON2Value($value));
					
					$format = $module->enucleate();
				}
				break;
			case 'external':
				
				if (is_array($value)) {
						
					$arr_html = [];
					
					foreach ($value as $ref_value) {
							
						$arr_html[] = $ref_value;
					}
					
					$format = implode(($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR_LIST), $arr_html);
				} else {
					
					$format = $value;
				}
				break;
			case 'reversed_collection_resource_path':

				if ($value) {
					
					$arr_html = [];
					
					foreach ($value as $key => $str_path) { // Reversals are is_dynamic and has_multi and therefore grouped values
						
						$str_path = static::clearObjectDefinitionText($str_path, static::TEXT_TAG_OBJECT);
						
						$arr_html[] = nl2br($str_path);
					}
					
					$format = implode(($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR), $arr_html);
					
					if (mb_strlen($format) > static::$num_text_preview_characters) {
						$format = mb_substr($format, 0, static::$num_text_preview_characters).' [...]';
					}
				}
				break;
			case 'text':
				
				if ($value) {
					
					if (mb_strlen($value) > static::$num_text_preview_characters) {
						$value = mb_substr($value, 0, static::$num_text_preview_characters).' [...]';
					}
					
					$value = strEscapeHTML($value);
					$format = parseBody($value);
				}
				break;
			case 'text_layout':
			case 'text_tags':
				
				if ($value) {
					
					if (mb_strlen($value) > static::$num_text_preview_characters) {
						$value = mb_substr($value, 0, static::$num_text_preview_characters).' [...]';
					}
					
					if ($arr_type_settings['html']) {
						$format = parseBody($value, ['sanitise' => true]);
					} else {
						$value = strEscapeHTML($value);
						$format = parseBody($value);
					}

					if ($format) {
						$format = Response::addParsePost($format, ['strip' => true]);
					}
				}
				break;
			default:
			
				$format = self::formatToHTMLValue($type, $value, $reference, $arr_type_settings, $arr_extra);
				$do_group = false;
		}
		
		if ($do_group) {
			
			if ($arr_extra['style']) {
				
				if ($arr_extra['has_multi']) {
					$format = '<div style="'.$arr_extra['style'].'">'.$format.'</div>';
				} else {
					$format = '<span style="'.$arr_extra['style'].'">'.$format.'</span>';
				}
			}
		}
		
		return $format;
	}
	
	public static function formatToHTMLPreviewPlainValue($type, $value, $reference, $arr_type_settings = [], $arr_extra = []) { // From formatted database to preview display
		
		$do_group = true;
		$format = false;
		
		switch ($type) {
			case 'reference':
			case 'type':
			case 'classification':
			case 'reversed_classification':
			case 'object_description':

				if ($arr_extra['has_multi']) {
					
					$arr_html = [];
					
					foreach ($value as $key => $str_value) {
						
						$str_html = ($arr_extra['style'] ? '<span style="'.$arr_extra['style'].'">'.$str_value.'</span>' : $str_value);
						
						$arr_html[] = $str_html;
					}
					
					$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
				} else {
					
					$str_html = ($arr_extra['style'] ? '<span style="'.$arr_extra['style'].'">'.$value.'</span>' : $value);
					
					$format = $str_html;
				}
				
				$do_group = false;
				
				break;
			case 'reversed_collection':
				
				$arr_html = [];
				
				if ($reference) {
					
					foreach ($reference as $key => $arr_reference_type_objects) { // Reversals are is_dynamic and has_multi and therefore grouped values
						
						foreach ($arr_reference_type_objects as $type_id => $arr_reference_objects) {
						
							foreach ($arr_reference_objects as $object_id => $arr_reference_object) {
								
								if (isset($arr_html[$object_id])) {
									continue;
								}
								
								$str_object_name = ($arr_reference_object['object_definition_ref_object_name'] ?? $arr_reference_object['object_sub_definition_ref_object_name']);
								
								$str_html = ($arr_extra['style'] ? '<span style="'.$arr_extra['style'].'">'.$str_object_name.'</span>' : $str_object_name);
							
								$arr_html[$object_id] = $str_html;
							}
						}
					}
				}
				
				$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
				$do_group = false;
				
				break;
			case 'reversed_collection_resource_path':
				
				$arr_html = [];
				
				if ($value) {
					
					foreach ($value as $key => $str_path) { // Reversals are is_dynamic and has_multi and therefore grouped values
						
						$str_path = static::clearObjectDefinitionText($str_path, static::TEXT_TAG_OBJECT);
						
						$arr_html[] = nl2br($str_path);
					}
				}
				
				$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);
								
				break;
			default:
			
				$format = self::formatToHTMLPreviewValue($type, $value, $reference, $arr_type_settings, $arr_extra);
				$do_group = false;
		}
		
		if ($do_group) {
			
			if ($arr_extra['style']) {
				
				if ($arr_extra['has_multi']) {
					$format = '<div style="'.$arr_extra['style'].'">'.$format.'</div>';
				} else {
					$format = '<span style="'.$arr_extra['style'].'">'.$format.'</span>';
				}
			}
		}
		
		return $format;
	}
	
	public static function formatToHTMLPlainValue($type, $value, $reference, $arr_type_settings = [], $arr_extra = []) { // From formatted database to display
		
		$format = false;
		
		switch ($type) {
			case 'int':
			case 'numeric':
			case 'float':
			case 'serial_varchar':
			case '':
			
				if (is_array($value)) {
					$format = '<span>'.implode('</span><span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span><span>', $value).'</span>';
				} else {
					$format = $value;
				}
				
				break;
			case 'text':
			
				$value = strEscapeHTML($value);
				$value = parseBody($value);
				$format = '<section>'.$value.'</section>';

				break;
			case 'text_layout':
			case 'text_tags':
				
				if ($arr_type_settings['html']) {
					$value = parseBody($value, ['sanitise' => true]);
				} else {
					$value = strEscapeHTML($value);
					$value = parseBody($value);
				}

				$format = '<section>'.$value.'</section>';

				break;
			case 'media':
			case 'media_external':
			
				$show_url = ($arr_type_settings['display'] && $arr_type_settings['display'] == 'url');
				
				$value = (array)$value;
				$arr_html = [];
				
				foreach ($value as $media) {
					
					$media = new EnucleateMedia($media, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);

					if ($show_url) {
						
						$media->setPath(rtrim(URL_BASE_HOME, '/'), true);
						$str_url = $media->enucleate(EnucleateMedia::VIEW_URL);
						$str_url = strEscapeHTML($str_url);
						
						$arr_html[] = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
					} else {
						
						$media->setSizing('15cm', '10cm', false);

						if (!$media->isExternal()) {
							
							$arr_size = $media->getSizing();
							
							if ($arr_size) {
									
								$width = $arr_size['width'] * static::PIXEL_TO_CENTIMETER;
								$width = ($width > 15 ? 15 : $width);
								$height = ($width * ($arr_size['height'] / $arr_size['width']));
								$media->setSizing($width.'cm', $height.'cm', false);
							}
							
							$str_url = $media->enucleate(EnucleateMedia::VIEW_URL);
						
							$media->setPath('Pictures/', false);
							$str_url_new = $media->enucleate(EnucleateMedia::VIEW_URL);
							
							if ($arr_extra && is_array($arr_extra['arr_path_media'])) {
								$arr_extra['arr_path_media'][$str_url_new] = rtrim(DIR_ROOT_STORAGE.DIR_HOME, '/').$str_url;
							}
						}
						
						$arr_html[] = $media->enucleate();
					}
				}
				
				$format = implode('<span class="separator">'.($arr_type_settings['separator'] ?: static::FORMAT_MULTI_SEPERATOR).'</span>', $arr_html);

				break;
			case 'module':
				
				$module = EnucleateValueTypeModule::init($arr_type_settings['type']);
				$module->setConfiguration($arr_extra);
				$module->setValue(JSON2Value($value));
				
				$format = $module->enucleate();

				break;
			default:
				$format = $value;
		}
		
		return $format;
	}
	
	public static function formatToInputValue($type, $value, $arr_type_settings = []) { // From raw database to direct input (i.e in a field)
		
		$format = false;
			
		switch ($type) {
			case 'numeric':
				$format = ($value ? static::int2Numeric($value) : '');
				break;
			case 'date':
				$format = ($value ? static::int2Date($value) : '');
				break;
			case 'boolean':
				$format = ($value !== '' && $value !== null ? ($value == 1 || $value === 'yes' ? 'yes' : 'no') : '');
				break;
			case 'date_cycle':
			
				if ($value) {
					
					$format = [
						'month' => $value[0],
						'day' => $value[1],
					];
				}
				break;
			case 'date_compute':
			
				if ($value) {
					
					$format = [
						'year' => $value[0],
						'month' => $value[1],
						'day' => $value[2],
					];
				}
				break;
			default:
				$format = $value;
		}
		
		return $format;
	}
	
	public static function formatToFormValue($type, $value, $reference, $name, $arr_type_settings = [], $arr_extra = []) { // From raw database to input field 
		
		$format = false;
		$html_menu = ($arr_extra['menu'] ?? '');
	
		switch ($type) {
			case 'media_external':
			case 'serial_varchar':
			case '':
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
					
					foreach ($value as $str) {
						$format[] = ['value' => '<input type="text" class="default" name="'.$name.'[]" value="'.strEscapeHTML($str).'" />'];
					}
				} else {
					
					$format = '<input type="text" class="default" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
				}
				
				break;
			case 'int':
			case 'numeric':
			case 'float':
			
				if ($type == 'numeric' && $value) {
					$value = arrParseRecursive($value, __CLASS__.'::int2Numeric');
				}
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
				
					foreach ($value as $num) {
						$format[] = ['value' => '<input type="number" name="'.$name.'[]" value="'.strEscapeHTML($num).'" />'];
					}
				} else {
					
					$format = '<input type="number" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
				}
				
				break;
			case 'media':
			
				if (is_array($value)) {
					
					$format = [];
					
					if (!$value) {
						$value[] = '';
					}
					
					array_unshift($value, ''); // Empty run for sorter source
					
					foreach ($value as $key => $str_url) {
						
						$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
						
						$html_media = '';
						
						if ($str_url) {
							
							$media = new EnucleateMedia($str_url, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
							
							$html_media = '<div class="media-preview">'.$media->enucleate().'</div>';
						}
						
						$format[] = [
							'source' => ($key == 0 ? true : false),
							'value' => $html_media
								.'<label>'.getLabel('lbl_url').':</label>'
								.'<input type="text" name="'.$name.'['.$unique.'][url]" value="'.strEscapeHTML($str_url).'" />'
								.'<div class="hide-edit hide">'
									.'<label>'.getLabel('lbl_file').':</label>'
									.cms_general::createFileBrowser(false, $name.'['.$unique.'][file]')
								.'</div>'
								.'<input type="button" class="data neutral" value="'.getLabel('lbl_upload').'" />'
						];
					}
				} else {
					
					$html_media = '';
						
					if ($value) {
						
						$media = new EnucleateMedia($value, DIR_HOME_TYPE_OBJECT_MEDIA, '/'.DIR_TYPE_OBJECT_MEDIA);
						
						$html_media = '<div class="media-preview">'.$media->enucleate().'</div>';
					}
					
					$format = $html_media
						.'<label>'.getLabel('lbl_url').':</label>'
						.'<input type="text" name="'.$name.'[url]" value="'.strEscapeHTML($value).'" />'
						.'<div class="hide-edit hide">'
							.'<label>'.getLabel('lbl_file').':</label>'
							.cms_general::createFileBrowser(false, $name.'[file]')
						.'</div>'
						.'<input type="button" class="data neutral" value="'.getLabel('lbl_upload').'" />';
				}
				
				break;
			case 'text':
				$format = '<textarea name="'.$name.'">'.strEscapeHTML($value).'</textarea>';
				break;
			case 'text_layout':
				$format = cms_general::editBody($value, $name, ['inline' => true, 'menu' => $html_menu]);
				$html_menu = '';
				break;
			case 'text_tags':
				$format = cms_general::editBody($value, $name, ['inline' => true, 'menu' => $html_menu, 'data' => ['tag_object' => true]]);
				$html_menu = '';
				break;
			case 'boolean':
				$arr_boolean = [['id' => 'yes', 'name' => getLabel('lbl_yes')], ['id' => 'no', 'name' => getLabel('lbl_no')], ['id' => '', 'name' => getLabel('lbl_none')]];
				$format = '<span class="input">'.cms_general::createSelectorRadio($arr_boolean, $name, ($value !== '' && $value !== null ? ($value == 1 || $value === 'yes' ? 'yes' : 'no') : '')).'</span>';
				break;
			case 'date':
				$format = '<input type="text" class="date" name="'.$name.'" value="'.($value ? static::int2Date($value) : '').'" />';
				break;
			case 'date_cycle':
				
				$day = $month = '';
				if ($value) {
					$month = $value[0];
					$day = $value[1];
				}

				$format = '<fieldset class="input"><ul>'
					.'<li><input type="number" class="date" placeholder="'.getLabel('unit_day').'" name="'.$name.'[day]" value="'.$day.'" /><label>'.getLabel('unit_day').'</label></li>'
					.'<li><input type="number" class="date" placeholder="'.getLabel('unit_month').'" name="'.$name.'[month]" value="'.$month.'" /><label>'.getLabel('unit_month').'</label></li>'
				.'</ul></fieldset>';
					
				break;
			case 'date_compute':
				
				$day = $month = $year = 0;
				if ($value) {
					$year = (int)$value[0];
					$month = (int)$value[1];
					$day = (int)$value[2];
				}
				
				$format = '<fieldset class="input"><ul>'
					.'<li><input type="number" name="'.$name.'[day]" value="'.$day.'" /><label>+ '.getLabel('unit_days').'</label></li>'
					.'<li><input type="number" name="'.$name.'[month]" value="'.$month.'" /><label>+ '.getLabel('unit_months').'</label></li>'
					.'<li><input type="number" name="'.$name.'[year]" value="'.$year.'" /><label>+ '.getLabel('unit_years').'</label></li>'
				.'</ul></fieldset>';
					
				break;
			case 'external':
				
				$is_multi = is_array($value);
				$is_static = true;
				
				if ($arr_type_settings['id']) {
					
					$arr_resource = StoreResourceExternal::getResources($arr_type_settings['id']);
					$is_static = ($arr_resource && $arr_resource['protocol'] == 'static' ? true : false);
				}
				
				if (!$is_static) {
					
					if ($is_multi) {

						$format = cms_general::createMultiSelect($name, 'y:data_filter:lookup_external-'.$arr_type_settings['id'], ($value ? array_combine($value, $value) : []), 'y:data_filter:lookup_external_pick-'.$arr_type_settings['id'], ['delay' => 2]);
					} else {
						
						$format = '<input type="hidden" id="y:data_filter:lookup_external_pick-'.$arr_type_settings['id'].'" name="'.$name.'" value="'.$value.'" /><input type="search" id="y:data_filter:lookup_external-'.$arr_type_settings['id'].'" class="autocomplete external" data-delay="3" value="'.strEscapeHTML($value).'" />';
					}
				} else {
					
					if ($is_multi) {
						
						$format = [];
						
						if (!$value) {
							$value[] = '';
						}
						
						foreach ($value as $key => $ref_value) {

							$format[] = ['value' => '<input type="text" class="default" name="'.$name.'[]" value="'.strEscapeHTML($ref_value).'" />'];
						}
					} else {
						
						$format = '<input type="text" class="default" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
					}
				}
				
				break;
			case 'module':
			case 'external_module':
			case 'reconcile_module':
			case 'reversal_module':
				// Handled by data entry module
			default:
				$format = '<input type="text" class="default" name="'.$name.'" value="'.strEscapeHTML($value).'" />';
		}
		
		if (is_array($format)) {
			
			$format = '<fieldset class="input"><ul>
				<li>
					<label></label><div><menu class="sorter">'
						.'<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'.$html_menu			
					.'</menu></div>
				</li><li>
					<label></label>
					'.cms_general::createSorter($format, true, true).'
				</li>
			</ul></fieldset>';
		} else {
			
			$format .= $html_menu;
		}
		
		return $format;
	}
	
	public static function formatToFormValueFilter($type, $value, $name, $arr_type_settings = []) { // No database involved
		
		$format = false;
	
		switch ($type) {
			case 'boolean':
				$arr_boolean = [['id' => 'yes', 'name' => getLabel('lbl_yes')], ['id' => 'no', 'name' => getLabel('lbl_no')], ['id' => '', 'name' => getLabel('lbl_any')]];
				$format = '<span class="input">'.cms_general::createSelectorRadio($arr_boolean, $name, ($value !== '' && $value !== null ? ($value == 1 || $value === 'yes' ? 'yes' : 'no') : '')).'</span>';
				break;
			case 'date':
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				$arr_equality = FilterTypeObjects::getEqualityValues();
				unset($arr_equality['≠']);
				$format = '<select name="'.$name.'[equality]">'.cms_general::createDropdown($arr_equality, $value['equality']).'</select>'
					.'<input type="text" class="date" name="'.$name.'[value]" value="'.($value['value'] !== 'now' ? $value['value'] : '').'" />'
					.'<input type="checkbox" name="'.$name.'[value_now]" value="1" title="'.getLabel('inf_date_now').'"'.($value['value'] === 'now' ? ' checked="checked"' : '').' />'
					.'<input type="text" class="date" name="'.$name.'[range]" value="'.($value['range'] !== 'now' ? $value['range'] : '').'" />'
					.'<input type="checkbox" name="'.$name.'[range_now]" value="1" title="'.getLabel('inf_date_now').'"'.($value['range'] === 'now' ? ' checked="checked"' : '').' />';
				break;
			case 'int':
			case 'numeric':
			case 'float':
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				$arr_equality = FilterTypeObjects::getEqualityValues();
				unset($arr_equality['≠']);
				$format = '<select name="'.$name.'[equality]">'.cms_general::createDropdown($arr_equality, $value['equality']).'</select>'
					.'<input type="number" name="'.$name.'[value]" value="'.$value['value'].'" />'
					.'<input type="number" name="'.$name.'[range]" value="'.$value['range'].'" />';
				break;
			case 'text':
			case 'text_layout':
			case 'media_external':
			case 'external':
			case 'serial_varchar':
			case '':
				$value = (is_array($value) ? $value : ['equality' => '*', 'value' => $value]);
				$arr_equality = FilterTypeObjects::getEqualityStringValues();
				unset($arr_equality['≠']);
				$format = '<select name="'.$name.'[equality]">'.cms_general::createDropdown($arr_equality, $value['equality']).'</select>'
					.'<input type="text" name="'.$name.'[value]" value="'.strEscapeHTML($value['value']).'" />';
				break;
			default:
				$format = self::formatToFormValue($type, $value, false, $name, $arr_type_settings);
		}
		
		return $format;
	}
		
	public static function formatFromSQLValue($type, $value, $arr_type_settings = [], $mode_format = null) {
		
		$format = false;
		
		switch ($type) {
			case 'boolean':
				$format = "CASE WHEN ".$value." = 1 THEN '".getLabel('lbl_yes')."' WHEN ".$value." = 0 THEN '".getLabel('lbl_no')."' ELSE '' END";
				break;
			case 'date':
				
				$do_mode_ymd = ($mode_format !== null && ($mode_format & static::FORMAT_DATE_YMD));
				
				$sql_string = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);

				$format = "CASE ".$value."
					WHEN ".static::DATE_INT_MAX." THEN '∞'
					WHEN ".static::DATE_INT_MIN." THEN '-∞'
					ELSE CONCAT(";
						
						$sql_year = "CASE WHEN LENGTH(".$sql_string.")-8 < 3 
							THEN SUBSTRING(CONCAT('00', ".$sql_string.") FROM -(8+3) FOR 3)
							ELSE SUBSTRING(".$sql_string." FROM 1 FOR LENGTH(".$sql_string.")-8)
						END";
						
						if ($do_mode_ymd) {
							
							$format .="
								".$sql_year.",
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2) = '00' THEN '' ELSE CONCAT('-', SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2)) END,
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2) = '00' THEN '' ELSE CONCAT('-', SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2)) END,
							";
						} else {
							
							$format .="
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2) = '00' THEN '' ELSE CONCAT(SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-5) FOR 2), '-') END,
								CASE WHEN SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2) = '00' THEN '' ELSE CONCAT(SUBSTRING(".$sql_string." FROM (LENGTH(".$sql_string.")-7) FOR 2), '-') END,
								".$sql_year.",
							";
						}
						$format .="
						CASE WHEN RIGHT(".$sql_string.", 4) = '".static::DATE_INT_SEQUENCE_NULL."' THEN '' ELSE CONCAT(' ', ".DBFunctions::castAs("(".DBFunctions::castAs("RIGHT(".$sql_string.", 4)", DBFunctions::CAST_TYPE_INTEGER)." - ".static::DATE_INT_SEQUENCE_NULL.")", DBFunctions::CAST_TYPE_STRING).") END
					)
				END";
				
				break;
			case 'chronology':
				$format = self::formatFromSQLChronology($value);
				break;
			case 'geometry':
			
				if (static::GEOMETRY_SRID && keyIsUncontested('srid', $arr_type_settings)) {
					
					if (DB::ENGINE_IS_MYSQL) {
						
						//$sql_geojson_srid = "ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.", 2)"; // MariaDB does not support the option for adding the crs
						$sql_geojson_srid = 'INSERT(ST_AsGeoJSON('.$value.', '.static::GEOMETRY_COORDINATE_DECIMALS.'), 2, 0, CONCAT(\'"crs": {"type": "name", "properties": {"name": "EPSG:\', ST_SRID('.$value.'), \'"}}, \'))';
					} else {
						
						$sql_geojson_srid = "ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.", 2)";
					}

					$format = "CASE
						WHEN ST_SRID(".$value.") != ".static::GEOMETRY_SRID." THEN ".$sql_geojson_srid."
						ELSE ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.")
					END";
				} else {
					
					$format = "ST_AsGeoJSON(".$value.", ".static::GEOMETRY_COORDINATE_DECIMALS.")";
				}
				
				break;
			case 'int':
			case 'float':
				$format = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);
				break;
			case 'numeric':
				
				$value = DBFunctions::castAs($value, DBFunctions::CAST_TYPE_STRING);
				$value = "LPAD(".$value.", GREATEST(LENGTH(".$value."), ".(static::VALUE_NUMERIC_DECIMALS + 1)."), '0')";
				
				if (DB::ENGINE_IS_MYSQL) {
					$format = "INSERT(".$value.", LENGTH(".$value.")+1-".static::VALUE_NUMERIC_DECIMALS.", 0, '.')";
					$format = "TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM ".$format."))";
				} else {
					$format = "OVERLAY(".$value." PLACING '.' FROM LENGTH(".$value.")+1-".static::VALUE_NUMERIC_DECIMALS." FOR 0)";
					$format = "RTRIM(RTRIM(".$format.", '0'), '.')";
				}

				break;
			default:
				$format = $value;
		}

		return $format;
	}
	
	public static function formatToSQLValue($type, $value) {
		
		$do_unique = true;
		$format = false;
	
		switch ($type) {
			case 'boolean':
				$format = ($value !== '' && $value !== null ? ($value === 'no' || !$value ? 0 : 1) : '');
				break;
			case 'date':
				$format = self::date2Integer($value);
				break;
			case 'date_cycle':
				$do_unique = false;
				if (!$value || !$value['day'] || !$value['month']) {
					$format = [1, 1];
					break;
				}
				$format = [(int)$value['month'], (int)$value['day']];
				break;
			case 'date_compute':
				$do_unique = false;
				if (!$value || (!$value['day'] && !$value['month'] && !$value['year'])) {
					$format = [0, 0, 0];
					break;
				}
				$format = [(int)$value['year'], (int)$value['month'], (int)$value['day']];
				break;
			case 'chronology':
				$value = self::formatToSQLChronology($value);
				$format = ($value ? implode(',', $value['date']).';'.implode(';', $value['chronology']) : '');
				break;
			case 'geometry':
				$format = self::formatToSQLGeometry($value);
				break;
			case 'media':
			
				$is_multi = false;
				
				// Ensure the array is iterable by making it multi
				if (!is_array($value)) {
					
					$value = [$value];
				} else {
					
					if (isset($value['file']) || isset($value['url'])) {
						$value = [$value];
					} else {
						$is_multi = true;
					}
				}
				
				$format = [];
				
				foreach ($value as $arr_media) {
					
					if (!is_array($arr_media)) {
						$arr_media = ['url' => $arr_media];
					}
					
					$str_filename = false;
						
					if ($arr_media['file']) {
						
						$arr_file = (array)$arr_media['file'];
						
						if ($arr_file['size']) {
														
							$str_filename = hash_file('md5', $arr_file['tmp_name']);
							$str_extension = FileStore::getExtension($arr_file['name']);
							if ($str_extension == FileStore::EXTENSION_UNKNOWN) {
								$str_extension = FileStore::getExtension($arr_file['tmp_name']);
							}
							$str_filename = $str_filename.'.'.$str_extension;
							
							if (!isPath(DIR_HOME_TYPE_OBJECT_MEDIA.$str_filename)) {
	
								$store_file = new FileStore($arr_file, ['directory' => DIR_HOME_TYPE_OBJECT_MEDIA, 'filename' => $str_filename], FileStore::getSizeLimit(FileStore::STORE_FILE));
							}
						}
					}
					
					if (!$str_filename && $arr_media['url']) {
						
						$file = new FileGet($arr_media['url'], ['redirect' => 4]);
						
						if ($file->load()) {
							
							$str_path_file = $file->getPath();
							$str_filename = hash_file('md5', $str_path_file);
							$str_extension = FileStore::getExtension($file->getSource());
							if ($str_extension == FileStore::EXTENSION_UNKNOWN) {
								$str_extension = FileStore::getExtension($str_path_file);
							}
							
							$str_filename = $str_filename.'.'.$str_extension;
							
							if (!isPath(DIR_HOME_TYPE_OBJECT_MEDIA.$str_filename)) {
								
								$store_file = new FileStore($file, ['directory' => DIR_HOME_TYPE_OBJECT_MEDIA, 'filename' => $str_filename], FileStore::getSizeLimit(FileStore::STORE_FILE));
							} else {
								
								$file->abort();
							}
						} else {
							
							$str_filename = $arr_media['url'];
						}
					}
					
					$format[] = $str_filename;
				}
				
				if (!$is_multi) {
					$format = current($format);
				}
				break;
			case 'int':
				$format = ($value !== '' && $value !== null ? arrParseRecursive($value, TYPE_INTEGER) : '');
				break;
			case 'numeric':
				$format = ($value !== '' && $value !== null ? arrParseRecursive($value, __CLASS__.'::num2Integer') : '');
				break;
			case 'float':
				$format = ($value !== '' && $value !== null ? arrParseRecursive($value, TYPE_FLOAT) : '');
				break;
			case 'text':
			case 'text_layout':
			case 'text_tags':
				$format = arrParseRecursive($value, TYPE_TEXT);
				break;
			case 'media_external':
			case 'external':
			case 'serial_varchar':
			case '':
				$format = arrParseRecursive($value, TYPE_STRING);
				break;
			case 'module':
			case 'external_module':
			case 'reconcile_module':
			case 'reversal_module':
				$do_unique = false;
				$format = (!empty($value) ? (is_array($value) ? value2JSON($value) : $value) : '');
				break;
			default:
				$format = $value;
		}
		
		if ($do_unique && is_array($format) && $format) {
			$format = array_values(array_unique(array_filter($format)));
		}
		
		return $format;
	}
	
	public static function formatToSQLValueFilter($type, $value, $name) {
		
		if (is_array($name)) {
			
			$name = 'JSON_VALUE('.$name['name'].', \''.$name['path'].'\' RETURNING '.static::formatToSQLType($type).')';
		}
		
		$value_plain = (is_array($value) ? $value['value'] : $value);
		$format = false;
	
		switch ($type) {
			case 'date':
				
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				$arr_num_value = static::dateInt2Compontents(static::date2Integer($value['value']));
				$arr_num_range = static::dateInt2Compontents(static::date2Integer($value['range']));
				$arr_sql_name = static::dateSQL2Compontents($name);

				switch ($value['equality']) {
					case '=':
					case '>':
					case '<':
						$format = $arr_sql_name['absolute'].' '.$value['equality'].' '.$arr_num_value['absolute'];
						break;
					case '≥':
						$format = $arr_sql_name['absolute'].' >= '.$arr_num_value['absolute'];
						break;
					case '≤':
						$format = $arr_sql_name['absolute'].' <= '.$arr_num_value['absolute'];
						break;
					case '><':
						$format = '('.$arr_sql_name['absolute'].' > '.$arr_num_value['absolute'].' AND '.$arr_sql_name['absolute'].' < '.$arr_num_range['absolute'].')';
						break;
					case '≥≤':
						$format = '('.$arr_sql_name['absolute'].' >= '.$arr_num_value['absolute'].' AND '.$arr_sql_name['absolute'].' <= '.$arr_num_range['absolute'].')';
						break;
				}
				break;
			case 'int':
			case 'numeric':
			case 'float':
			
				$value = (is_array($value) ? $value : ['equality' => '=', 'value' => $value]);
				if ($type == 'float') {
					$value['value'] = (float)$value['value'];
					$value['range'] = (float)$value['range'];
				} else if ($type == 'numeric') {
					$value['value'] = self::num2Integer($value['value']);
					$value['range'] = self::num2Integer($value['range']);
				} else {
					$value['value'] = (int)$value['value'];
					$value['range'] = (int)$value['range'];
				}

				switch ($value['equality']) {
					case '=':
					case '>':
					case '<':
						$format = $name.' '.$value['equality'].' '.$value['value'];
						break;
					case '≥':
						$format = $name.' >= '.$value['value'];
						break;
					case '≤':
						$format = $name.' <= '.$value['value'];
						break;
					case '><':
						$format = '('.$name.' > '.$value['value'].' AND '.$name.' < '.$value['range'].')';
						break;
					case '≥≤':
						$format = '('.$name.' >= '.$value['value'].' AND '.$name.' <= '.$value['range'].')';
						break;
				}
				break;
			case 'text':
			case 'text_layout':
			case 'text_tags':
			case 'media_external':
			case 'external':
			case 'serial_varchar':
			case '':
			
				$value = (is_array($value) ? $value : ['equality' => '*', 'value' => $value]);
				$sql_value = self::formatToSQLValue($type, $value['value']);

				switch ($value['equality']) {
					case '*':
						$format = $name." LIKE '%".DBFunctions::str2Search($sql_value)."%'";
						break;
					case '^':
						$format = $name." LIKE '".DBFunctions::str2Search($sql_value)."%'";
						break;
					case '$':
						$format = $name." LIKE '%".DBFunctions::str2Search($sql_value)."'";
						break;
					case '=':
						$format = $name." LIKE '".DBFunctions::str2Search($sql_value)."'";
						break;
				}
				break;
			case 'boolean':
				$format = self::formatToSQLValue($type, $value_plain);
				$format = ($format !== '' ? $name." = ".$format : 'TRUE');
				break;
			default:
				$format = DBFunctions::searchMatch($name, self::formatToSQLValue($type, $value_plain));
				break;
		}
		
		return $format;
	}
	
	public static function formatToSQLTranscension($type, $value, $name) {
		
		if (!$value['value'] || $value['value'] == 'any') {
			return '';
		}
				
		if (is_array($name) && isset($name['path'])) {
						
			$name = 'JSON_VALUE('.$name['name'].', \''.$name['path'].'\' RETURNING '.static::formatToSQLType($type).')';
		}
		
		$format = false;
		
		if (is_array($name)) { // Check the combined result of multiple columns
			
			$arr_format = [];
			
			foreach ($name as $cur_name) {
				
				$arr_format[] = self::formatToSQLTranscension($type, $value, $cur_name);
			}
			
			if ($value['value'] == 'empty') {
				$format = "(".implode(' AND ', $arr_format).")";
			} else if ($value['value'] == 'not_empty') {
				$format = "(".implode(' OR ', $arr_format).")";
			}
		} else {
			
			switch ($type) {
				case 'text':
				case 'text_layout':
				case 'text_tags':
				case 'media':
				case 'media_external':
				case 'external':
				case 'serial_varchar':
				case 'reversed_collection':
				case 'reversed_collection_resource_path':
				case '':
					if ($value['value'] == 'empty') {
						$format = "COALESCE(".$name.", '') = ''";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." != ''";
					}
					break;
				case 'boolean':
				case 'null':
					if ($value['value'] == 'empty') {
						$format = $name." IS NULL";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." IS NOT NULL";
					}
					break;
				case 'condition':
					if ($value['value'] == 'empty') {
						$format = $name." = FALSE";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." = TRUE";
					}
					break;
				default:
					if ($value['value'] == 'empty') {
						$format = "COALESCE(".$name.", 0) = 0";
					} else if ($value['value'] == 'not_empty') {
						$format = $name." != 0";
					}
					break;
			}
		}
		
		return $format;
	}
	
	public static function formatToSQLType($type) {
		
		switch ($type) {
			case 'int':
			case 'numeric':
				$cast = DBFunctions::CAST_TYPE_INTEGER;
				break;
			case 'float':
				$cast = DBFunctions::CAST_TYPE_DECIMAL;
				break;
			default:
				$cast = DBFunctions::CAST_TYPE_STRING;
				break;
		}
		
		return $cast;
	}
	
	// Numeric
	
	public static function num2Integer($num) {
		
		if ((int)$num >= static::VALUE_NUMERIC_MULTIPLIER && strpos((string)$num, '.') === false) { // Already converted
			return $num;
		}
		if (!is_numeric($num)) {
			return '';
		}
		
		return bcmul($num, static::VALUE_NUMERIC_MULTIPLIER);
	}
	
	public static function int2Numeric($int) {
		
		return rtrim(rtrim(bcdiv($int, static::VALUE_NUMERIC_MULTIPLIER, static::VALUE_NUMERIC_DECIMALS), '0'), '.');
	}
	
	public static function sqlInt2SQLNumeric($sql_value) {
		
		return '('.$sql_value.' * '.(1 / static::VALUE_NUMERIC_MULTIPLIER).')'; // Convert multiplier to decimal for float precision purposes
	}
	
	// Chronology
	
	public static function formatToSQLChronology($value) {
		
		if (!$value) {
			return '';
		}
		
		$arr_chronology = self::formatToChronology($value);
		
		$is_period = ($arr_chronology['type'] == 'period');
		
		$arr_sql = [
			'date' => [
				'span_period_amount' => 0, 'span_period_unit' => 0, 'span_cycle_object_id' => 0
			],
			'chronology' => []
		];
		
		$arr_time_directions = StoreType::getTimeDirectionsInternal();
		$arr_time_units = StoreType::getTimeUnitsInternal();
		
		$func_compute_date = function($arr) {
			
			if (!$arr) {
				return false;
			}
			
			if ($arr['date_object_sub_id'] || $arr['date_path']) {
				
				$date = 0;
			} else {
				
				$date = (int)self::formatToSQLValue('date', $arr['date_value']);
				
				if (!$date) {
					$date = false;
				}
			}
			
			return $date;
		};
		
		$date_start_start = $func_compute_date($arr_chronology['start']['start']);
		$date_start_end = $func_compute_date($arr_chronology['start']['end']);
		
		if ($is_period) {
			
			$date_end_start = $func_compute_date($arr_chronology['end']['start']);
			$date_end_end = $func_compute_date($arr_chronology['end']['end']);
			
			$date_end_end = ($date_end_end !== false ? $date_end_end : $date_start_start);

			$arr_sql['date']['span_period_amount'] = (int)$arr_chronology['span']['period_amount'];
			$arr_sql['date']['span_period_unit'] = (int)$arr_time_units[$arr_chronology['span']['period_unit']];
		} else {
			
			$date_end_start = false;
			
			$date_end_end = ($date_start_end !== false ? $date_start_end : $date_start_start);
		}
		
		if ($date_start_start === false) {
			return '';
		}
				
		$arr_sql['date']['span_cycle_object_id'] = (int)$arr_chronology['span']['cycle_object_id'];
		
		$func_sql_statement = function($arr, $identifier) use (&$arr_sql, $arr_time_directions, $arr_time_units) {
			
			if (!$arr) {
				return;
			}
			
			$arr_statement = [
				'offset_amount' => (int)$arr['offset_amount'],
				'offset_unit' => (int)$arr_time_units[$arr['offset_unit']],
				'cycle_object_id' => (int)$arr['cycle_object_id'],
				'cycle_direction' => (int)$arr_time_directions[$arr['cycle_direction']],
				'date_value' => ($arr['date_value'] ? (int)self::formatToSQLValue('date', $arr['date_value']) : 'NULL'),
				'date_object_sub_id' => ((int)$arr['date_object_sub_id'] ?: 'NULL'),
				'date_direction' => (int)$arr_time_directions[$arr['date_direction']]
			];
			
			$arr_sql['chronology'][] = implode(',', $arr_statement).', '.$identifier;
		};
		
		$func_sql_statement($arr_chronology['start']['start'], StoreType::DATE_START_START);
		$func_sql_statement($arr_chronology['start']['end'], StoreType::DATE_START_END);
		if ($is_period) {
			$func_sql_statement($arr_chronology['end']['start'], StoreType::DATE_END_START);
			$func_sql_statement($arr_chronology['end']['end'], StoreType::DATE_END_END);
		}
		
		return $arr_sql;
	}
	
	public static function formatFromSQLChronology($sql_table_name) {
				
		$sql = 'CONCAT(
			CONCAT('.$sql_table_name.'.span_period_amount, \',\', '.$sql_table_name.'.span_period_unit, \',\', '.$sql_table_name.'.span_cycle_object_id),
			\';\',
			COALESCE((SELECT
					'.DBFunctions::sqlImplode(
						'CONCAT(identifier, \',\', offset_amount, \',\', offset_unit, \',\', cycle_object_id, \',\', cycle_direction, \',\', COALESCE('.DBFunctions::castAs('date_value', DBFunctions::CAST_TYPE_STRING).', \'\'), \',\', COALESCE('.DBFunctions::castAs('date_object_sub_id', DBFunctions::CAST_TYPE_STRING).', \'\'), \',\', date_direction)',
						';'
					).'
				FROM '.DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DATE_CHRONOLOGY').' AS '.$sql_table_name.'_chrono
				WHERE '.$sql_table_name.'_chrono.object_sub_id = '.$sql_table_name.'.object_sub_id
					AND '.$sql_table_name.'_chrono.version = '.$sql_table_name.'.version
					AND '.$sql_table_name.'_chrono.active = TRUE
			), \'\')
		)';
	
		return $sql;
	}
	
	public static function formatToChronology($value) {
		
		if (!$value) {
			return false;
		}
		
		if (is_array($value) || substr($value, 0, 1) == '{') { // ChronoJSON
			
			if (is_array($value)) {
				$arr_source = $value;
			} else {
				$arr_source = json_decode($value, true);
			}
			
			$is_period = ($arr_source['type'] == 'period');
			$date_start = ($arr_source['date']['start'] ?? false);
			$date_end = ($arr_source['date']['end'] ?? false);
			$reference_start = ($arr_source['reference']['start'] ?? false);
			$reference_end = ($arr_source['reference']['end'] ?? false);
			
			$arr_source_span = $arr_source['span'];
			
			$arr_chronology = [
				'type' => ($is_period ? 'period' : 'point'),
				'span' => [
					'period_amount' => (int)$arr_source_span['period_amount'],
					'period_unit' => ($arr_source_span['period_amount'] ? $arr_source_span['period_unit'] : null),
					'cycle_object_id' => (int)$arr_source_span['cycle_object_id']
				]
			];
			
			static::parseChronologyStatementJSON($arr_chronology['start']['start'], ($arr_source['start']['start'] ?? null));
			static::parseChronologyStatementJSON($arr_chronology['start']['end'], ($arr_source['start']['end'] ?? null));
			static::parseChronologyStatementJSON($arr_chronology['end']['start'], ($arr_source['end']['start'] ?? null));
			static::parseChronologyStatementJSON($arr_chronology['end']['end'], ($arr_source['end']['end'] ?? null));
			
		} else { // Internal
			
			$arr_time_units = StoreType::getTimeUnitsInternal(true);
			
			$arr_parts = explode(';', $value);
			
			$arr_part = explode(',', $arr_parts[0]);
			unset($arr_parts[0]);
			
			$date_start = false;
			$date_end = false;
			$reference_start = false;
			$reference_end = false;

			$arr_chronology = [
				'type' => '',
				'span' => [
					'period_amount' => (int)$arr_part[0],
					'period_unit' => $arr_time_units[$arr_part[1]],
					'cycle_object_id' => (int)$arr_part[2]
				]
			];
						
			foreach ($arr_parts as $str) {
				static::parseChronologyStatementInternal($arr_chronology, $str);
			}
			
			$is_period = ($arr_chronology['end']['end'] ?? null);
			$arr_chronology['type'] = ($is_period ? 'period' : 'point');
		}
		
		// Parse
		
		$is_single = (!$is_period && empty($arr_chronology['start']['end']) && empty($arr_chronology['start']['start']['offset_amount']) && (!empty($arr_chronology['start']['start']['date_value']) || !empty($arr_chronology['start']['start']['date_object_sub_id'])) && (empty($arr_chronology['start']['start']['date_direction']) || $arr_chronology['start']['start']['date_direction'] == '|>|')); // Chronology only involves a single date statement (and possibly a Cycle)
		
		if (!isset($arr_chronology['start']['start'])) {
			
			if ($date_start || $reference_start) {
				if ($date_start) {
					$arr_chronology['start']['start']['date_value'] = $date_start;
				} else {
					$arr_chronology['start']['start']['date_object_sub_id'] = $reference_start;
				}
			} else if ($date_end || $reference_end) { // Copy end to start on quick entry (non-statement) only
				if ($date_end) {
					$arr_chronology['start']['start']['date_value'] = $date_end;
				} else {
					$arr_chronology['start']['start']['date_object_sub_id'] = $reference_end;
				}
			} else {
				return [];
			}
		} else {
			
			if ($is_single) {
				$arr_chronology['start']['start']['date_direction'] = '';
			}
		}
		
		if ($arr_chronology['start']['start']['date_value'] === '∞') {
			$arr_chronology['start']['start']['date_value'] = '-∞';
		}
		
		if ($is_period) {
				
			if (!isset($arr_chronology['end']['end'])) {
				
				if ($date_end) {
					$arr_chronology['end']['end']['date_value'] = $date_end;
				}  else if ($reference_end) {
					$arr_chronology['end']['end']['date_object_sub_id'] = $reference_end;
				} else {
					$arr_chronology['end']['end'] = $arr_chronology['start']['start'];
					
					// Apply default direction if not further specified
					if ($arr_chronology['end']['end']['date_direction'] == '|>|' && !$arr_chronology['end']['end']['offset_amount'] && !$arr_chronology['end']['end']['cycle_object_id']) {
						$arr_chronology['end']['end']['date_direction'] = '|<|';
					}
				}
			}
			
			if ($arr_chronology['end']['end']['date_value'] === '-∞') {
				$arr_chronology['end']['end']['date_value'] = '∞';
			}
		}
				
		return $arr_chronology;
	}
	
	public static function parseChronologyStatementJSON(&$arr_chronology_statement, $arr_source_statement) {
		
		if (!$arr_source_statement) {
			return;
		}
		
		// date_value takes precedent (more specific)
		if ($arr_source_statement['date_value_type']) {
			if ($arr_source_statement['date_value_type'] == 'object_sub') {
				$arr_source_statement['date_value'] = null;
				$arr_source_statement['date_path'] = null;
				$arr_source_statement['date_object_sub_id'] = (int)$arr_source_statement['date_object_sub_id'];
			} else if ($arr_source_statement['date_value_type'] == 'path') {
				$arr_source_statement['date_value'] = null;
				$arr_source_statement['date_object_sub_id'] = null;
			} else {
				$arr_source_statement['date_object_sub_id'] = null;
				$arr_source_statement['date_path'] = null;
			}
		} else if ($arr_source_statement['date_value']) {
			
			$arr_source_statement['date_object_sub_id'] = null;
			$arr_source_statement['date_path'] = null;
									
			if ($arr_source_statement['date_value'] == '∞' || $arr_source_statement['date_value'] == '-∞') {
				
				$arr_source_statement = [
					'date_value' => $arr_source_statement['date_value']
				];
			}
		}
		
		if (!$arr_source_statement['date_value'] && !$arr_source_statement['date_object_sub_id'] && !$arr_source_statement['date_path']) {
			return;
		}
		
		$arr_chronology_statement = [
			'offset_amount' => (int)$arr_source_statement['offset_amount'],
			'offset_unit' => ($arr_source_statement['offset_amount'] ? $arr_source_statement['offset_unit'] : null),
			'cycle_object_id' => (int)$arr_source_statement['cycle_object_id'],
			'cycle_direction' => ($arr_source_statement['cycle_object_id'] ? $arr_source_statement['cycle_direction'] : null),
			'date_value' => $arr_source_statement['date_value'],
			'date_object_sub_id' => $arr_source_statement['date_object_sub_id'],
			'date_direction' => $arr_source_statement['date_direction']
		];
		
		if ($arr_source_statement['date_path']) {
			
			$arr_chronology_statement['date_path'] = $arr_source_statement['date_path'];
			
			if (!is_array($arr_chronology_statement['date_path'])) {
				$arr_chronology_statement['date_path'] = json_decode($arr_chronology_statement['date_path'], true);
			}
		}
	}
	
	public static function parseChronologyStatementInternal(&$arr_chronology, $str) {
		
		$arr_time_directions = StoreType::getTimeDirectionsInternal(true);
		$arr_time_units = StoreType::getTimeUnitsInternal(true);
	
		$arr_part = explode(',', $str);
		$int_identifier = (int)$arr_part[0];
		
		$arr_chronology_statement = [
			'offset_amount' => (int)$arr_part[1],
			'offset_unit' => $arr_time_units[$arr_part[2]],
			'cycle_object_id' => (int)$arr_part[3],
			'cycle_direction' => $arr_time_directions[$arr_part[4]],
			'date_value' => ($arr_part[5] ? self::formatToInputValue('date', $arr_part[5]) : null),
			'date_object_sub_id' => ($arr_part[6] ? (int)$arr_part[6] : null),
			'date_direction' => $arr_time_directions[$arr_part[7]]
		];
		
		switch ($int_identifier) {
			case StoreType::DATE_START_START:
				$arr_chronology['start']['start'] = $arr_chronology_statement;
				break;
			case StoreType::DATE_START_END:
				$arr_chronology['start']['end'] = $arr_chronology_statement;
				break;
			case StoreType::DATE_END_START:
				$arr_chronology['end']['start'] = $arr_chronology_statement;
				break;
			case StoreType::DATE_END_END:
				$arr_chronology['end']['end'] = $arr_chronology_statement;
				break;
		}
	}
	
	public static function formatToChronologyDetails($value) {
		
		if (!$value) {
			return false;
		}
		
		if (!is_array($value)) {
			$arr_chronology = self::formatToChronology($value);
		} else {
			$arr_chronology = $value;
		}
			
		$arr_cycle_object_ids = arrValuesRecursive('cycle_object_id', $arr_chronology);
						
		if ($arr_cycle_object_ids) {
			
			$arr_object_names = FilterTypeObjects::getTypeObjectNames(StoreType::getSystemTypeID('cycle'), $arr_cycle_object_ids);
			
			$func_set_cycle_names = function(&$arr) use ($arr_object_names) {
				
				if (!$arr['cycle_object_id']) {
					return;
				}
				
				$arr['cycle_object_name'] = $arr_object_names[$arr['cycle_object_id']];
			};
			
			$func_set_cycle_names($arr_chronology['span']);
			$func_set_cycle_names($arr_chronology['start']['start']);
			$func_set_cycle_names($arr_chronology['start']['end']);
			$func_set_cycle_names($arr_chronology['end']['start']);
			$func_set_cycle_names($arr_chronology['end']['end']);
		}

		$arr_object_sub_ids = arrValuesRecursive('date_object_sub_id', $arr_chronology);
		
		if ($arr_object_sub_ids) {
		
			$arr_object_subs_info = FilterTypeObjects::getObjectSubsTypeObjects($arr_object_sub_ids);
			
			$arr_type_object_sub_ids = [];
			
			foreach ($arr_object_subs_info as $object_sub_id => $arr_info) {
				
				$arr_type_object_sub_ids[$arr_info['type_id']][$arr_info['object_id']][] = $object_sub_id;
			}
			
			$arr_type_object_names = [];
			$arr_object_subs_names = [];
			
			foreach ($arr_type_object_sub_ids as $type_id => $arr_objects_subs) {
				
				$arr_object_ids = array_keys($arr_objects_subs);
				$arr_object_sub_ids = arrMergeValues($arr_objects_subs);

				$arr_type_object_names[$type_id] = FilterTypeObjects::getTypeObjectNames($type_id, $arr_object_ids);
				$arr_object_subs_names += FilterTypeObjects::getTypeObjectSubsNames($type_id, $arr_object_ids, $arr_object_sub_ids);
			}
			
			$func_set_object_sub_names = function(&$arr) use ($arr_object_subs_info, $arr_type_object_names, $arr_object_subs_names) {
				
				if (!$arr['date_object_sub_id']) {
					return;
				}
				
				$arr_info = $arr_object_subs_info[$arr['date_object_sub_id']];
				
				$arr['date_type_id'] = $arr_info['type_id'];
				$arr['date_object_id'] = $arr_info['object_id'];
				$arr['date_object_sub_details_id'] = $arr_info['object_sub_details_id'];
				
				$arr['date_object_name'] = $arr_type_object_names[$arr_info['type_id']][$arr_info['object_id']];
				$arr['date_object_sub_name'] = $arr_object_subs_names[$arr['date_object_sub_id']];
			};
			
			$func_set_object_sub_names($arr_chronology['start']['start']);
			$func_set_object_sub_names($arr_chronology['start']['end']);
			$func_set_object_sub_names($arr_chronology['end']['start']);
			$func_set_object_sub_names($arr_chronology['end']['end']);
		}
				
		return $arr_chronology;
	}
	
	public static function formatToChronologyPointOnly($value) {
		
		if (!is_array($value)) {
			$arr_chronology = self::formatToChronology($value);
		} else {
			$arr_chronology = $value;
		}
		
		$arr_chronology = static::formatToChronology([
			'type' => $arr_chronology['type'],
			'date' => ['start' => $arr_chronology['start']['start']['date_value'], 'end' => $arr_chronology['end']['end']['date_value']]
		]);
		
		$arr_chronology = arrFilterRecursive($arr_chronology);

		return $arr_chronology;
	}
	
	public static function formatToChronologyReferenceOnly($value) {
		
		if (!is_array($value)) {
			$arr_chronology = self::formatToChronology($value);
		} else {
			$arr_chronology = $value;
		}
		
		$arr_chronology = static::formatToChronology([
			'type' => $arr_chronology['type'],
			'reference' => ['start' => $arr_chronology['start']['start']['date_object_sub_id'], 'end' => $arr_chronology['end']['end']['date_object_sub_id']]
		]);
		
		$arr_chronology = arrFilterRecursive($arr_chronology);

		return $arr_chronology;
	}
	
	// Geometry
	
	public static function formatToSQLGeometry($value) {
		
		if (!$value) {
			return '';
		}
		
		if (is_array($value)) {
			$value = value2JSON($value);
		}
		$value = ltrim($value);
					
		$is_json = (substr($value, 0, 1) == '{');
			
		// Check if a transformation is needed
		
		$num_srid = static::GEOMETRY_SRID;
		
		if (static::GEOMETRY_SRID) {
			
			$num_srid = static::geometryToSRID($value, true);
		}
		
		if (strStartsWith($value, 'SRID=')) { // Clean optional SRID prefix
			
			$value = preg_replace('/^SRID=\d+;?/', '', $value);
		}
		
		try {
			
			$value = static::formatToSQLGeometryValid($value, $num_srid, $is_json);
			
		} catch (Exception $e) {
			
			Labels::setVariable('type', ($is_json ? 'GeoJSON' : 'Geometry'));
			
			$e_previous = $e->getPrevious(); // Get DBTrouble
			$num_code = ($e_previous instanceof DBTrouble ? $e_previous->getCode() : false);
			
			$msg_client = '';
			if (DB::ENGINE_IS_MYSQL && ($num_code == 3731 || $num_code == 3732 || $num_code == 3616 || $num_code == 3617) && $is_json) { // Out of bounds
				
				try {
					
					$value = static::translateToGeometry($value, $num_srid); // Try to fix the bounds
					$value = static::formatToSQLGeometryValid($value, $num_srid, true);
				} catch (Exception $e2) {
					
					$msg_client = getLabel('msg_malformed_geometry_bounds');
				}
			} else {
				
				$msg_client = getLabel('msg_malformed_geometry');
			}
			
			if ($msg_client) {
				
				error($msg_client, TROUBLE_ERROR, LOG_BOTH, false, $e);
			}
		}
						
		return $value;
	}
	
	public static function formatToSQLGeometryValid($value, $num_srid, $is_json) {
		
		// Check if it is valid GeoJSON or geometry text (geometry WKT), and is queryable
		
		$value = DBFunctions::strEscape($value);
						
		if (DB::ENGINE_IS_MYSQL) {
			
			if ($is_json) {
				$sql_value = "ST_GeomFromGeoJSON('".$value."', 2, ".$num_srid.")";
			} else {
				$sql_value = "ST_SRID(ST_GeomFromText('".$value."'), ".$num_srid.")";
			}
			
			//$sql_geojson = "ST_AsGeoJSON(@check, ".static::GEOMETRY_COORDINATE_DECIMALS.", ".($num_srid != static::GEOMETRY_SRID ? '2' : '0').")"; // MariaDB does not support the option for adding the crs
			if ($num_srid != static::GEOMETRY_SRID) {
				$sql_geojson = 'INSERT(ST_AsGeoJSON(@check, '.static::GEOMETRY_COORDINATE_DECIMALS.'), 2, 0, CONCAT(\'"crs": {"type": "name", "properties": {"name": "EPSG:\', ST_SRID(@check), \'"}}, \'))';
			} else {
				$sql_geojson = 'ST_AsGeoJSON(@check, '.static::GEOMETRY_COORDINATE_DECIMALS.')';
			}
			
			$sql = "
				SET @check = ".$sql_value.";
				SELECT ST_Intersects(@check, @check), ST_IsEmpty(@check);
				SELECT ".$sql_geojson.";
			";
		} else {
			
			$sql_value = ($is_json ? "ST_GeomFromGeoJSON('".$value."')" : "ST_GeomFromText('".$value."', ".$num_srid.")");
			$sql_geojson = "ST_AsGeoJSON(vars.check, ".static::GEOMETRY_COORDINATE_DECIMALS.", ".($num_srid != static::GEOMETRY_SRID ? '2' : '0').")";
			
			$sql = "
				WITH vars AS (SELECT ".$sql_value." AS check);
				SELECT ST_Intersects(vars.check, vars.check), ST_IsEmpty(vars.check) FROM vars;
				SELECT ".$sql_geojson." FROM vars;
			";
		}

		$res = DB::queryMulti($sql);
		
		$is_empty = $res[1]->fetchRow();
		$is_empty = (bool)$is_empty[1];
		
		if ($is_empty) {
			
			Labels::setVariable('type', ($is_json ? 'GeoJSON' : 'Geometry'));
			
			error(getLabel('msg_malformed_geometry_empty'), TROUBLE_ERROR, LOG_BOTH, false, $e);
		}
		
		$value = $res[2]->fetchRow();
		$value = $value[0];
		
		return $value;
	}
	
	public static function translateToGeometry($value, $num_srid) {
			
		$process = new ProcessProgram('ogr2ogr'
			.' -t_srs EPSG:'.$num_srid
			.' -lco RFC7946=YES' // Output lastest GeoJSON standard, also automatically splits at the antimeridian
			//.' -lco COORDINATE_PRECISION='.static::GEOMETRY_COORDINATE_DECIMALS
			//.' -makevalid'
			.' -f GeoJSON'
			.' "/vsistdout/"'
			.' "/vsistdin/"'
		);
		
		$process->writeInput($value);
		$process->closeInput();
		
		$process->checkOutput(false, true);
		
		$str_error = $process->getError();
		
		if ($str_error !== '') {
			
			error(getLabel('msg_malformed_geometry_transform'), TROUBLE_ERROR, LOG_BOTH, $str_error, $e);
		}
		
		$value = $process->getOutput();
		
		return $value;
	}
	
	public static function geometryToSRID($value, $do_external = false) {
				
		if ($do_external) { // Check for SRID in non-parsed source
			
			if (strpos($value, 'EPSG:') !== false || strpos($value, 'epsg:') !== false) {
				
				preg_match('/(?:EPSG|epsg)::?(\d+)/', $value, $arr_match);
				$num_srid = (int)$arr_match[1];
				
				if ($num_srid) {
					return $num_srid;
				}
			} else if (strpos($value, 'urn:ogc:def:crs:') !== false) {
				
				if (strpos($value, 'OGC:1.3:CRS84') !== false || strpos($value, 'ogc:1.3:crs84') !== false) { // CRS84 for WGS84 for 4326
					return 4326;
				}
			} else if (strStartsWith($value, 'SRID=')) {
				
				preg_match('/^SRID=(\d+);?/', $value, $arr_match);
				$num_srid = (int)$arr_match[1];
				
				if ($num_srid) {
					return $num_srid;
				}
			}
		} else if (strpos($value, 'EPSG:') !== false || strpos($value, 'epsg:') !== false) {
			
			preg_match('/(?:EPSG|epsg):(\d+)/', $value, $arr_match);
			$num_srid = (int)$arr_match[1];
			
			if ($num_srid) {
				return $num_srid;
			}
		}
		
		return static::GEOMETRY_SRID;
	}
	
	public static function formatToGeometry($value) {
		
		if (!$value) {
			return false;
		}
		
		if (!is_array($value)) {
			$arr_geometry = json_decode($value, true);
		} else {
			$arr_geometry = $value;
		}
		
		return $arr_geometry;
	}
	
	public static function formatToGeometryPoint($value) {
		
		if (!$value) {
			return false;
		}
		
		$arr_geometry = self::formatToGeometry($value);
		
		if ($arr_geometry['type'] && $arr_geometry['type'] == 'Point') {
			return $arr_geometry['coordinates'];
		} else {
			return false;
		}
	}
	
	public static function formatToGeometrySummary($value) {
		
		if (!$value) {
			return '';
		}
		
		$arr_geometry = self::formatToGeometry($value);
			
		$arr_summary = [];
		$arr_values = arrValuesRecursive('type', $arr_geometry);
		
		foreach ($arr_values as $type) {
			
			if (!$arr_summary[$type]) {
				$arr_summary[$type] = 1;
			} else {
				$arr_summary[$type]++;
			}
		}
		
		if (count($arr_summary) == 1 && $arr_summary['Point']) {
			
			$arr_summary['Point'] = $type.': '.$arr_geometry['coordinates'][1].' <span title="Latitude">λ</span> '.$arr_geometry['coordinates'][0].' <span title="Longitude">φ</span>'; // Switch geometry around to latitude - longitude
		} else {
				
			foreach ($arr_summary as $type => &$value) {
				
				$value = $type.': '.$value;
			}
		}
		unset($value);
		
		$value = implode(', ', $arr_summary);
		
		return $value;
	}
	
	// Date

	public static function date2Integer($date, $int_direction = false) {
		
		// *ymmdd OR *y OR *y-mm / mm-*y OR *y-mm-dd / dd-mm-*y => *ymmdds
		// $int_direction false OR StoreType::TIME_AFTER_BEGIN / StoreType::TIME_BEFORE_BEGIN OR StoreType::TIME_BEFORE_END / StoreType::TIME_AFTER_END
		
		$arr_date = [];
		
		if ($date) {
			
			if (is_integer($date)) { // Real integer: should be the internal nodegoat date-value
				
				$date = $date;
			} else if ($date === 'now') {
				
				$date = date('Ymd').static::DATE_INT_SEQUENCE_NULL;
			} else if ($date !== (string)(int)$date) { // Check whether date equals a whole number (i.e. year only)
				
				if ($date == '∞') {
					
					$date = static::DATE_INT_MAX;
				} else if ($date == '-∞') {
					
					$date = static::DATE_INT_MIN;
				} else {

					$arr_date = static::date2Components($date);
					$date = false;
					
					if (!$arr_date[0]) {
						$arr_date = [];
					}
				}
			} else {
				
				$arr_date = [$date, '00', '00', static::DATE_INT_SEQUENCE_NULL];
			}
		}
		
		if ($arr_date) {
			
			if ($int_direction) {
				
				if ($arr_date[1] == '00') {
					$arr_date[1] = ($int_direction == StoreType::TIME_BEFORE_END || $int_direction == StoreType::TIME_AFTER_END ? '12' : '00');
				}
				if ($arr_date[2] == '00') {
					$arr_date[2] = ($int_direction == StoreType::TIME_BEFORE_END || $int_direction == StoreType::TIME_AFTER_END ? '31' : '00');
				}
				if ($arr_date[3] == static::DATE_INT_SEQUENCE_NULL) {
					$arr_date[3] = ($int_direction == StoreType::TIME_BEFORE_END || $int_direction == StoreType::TIME_AFTER_END ? '9999' : '0000');
				}
			}
			
			$date = implode('', $arr_date);
		}
		
		if ($date) {
			
			if ($date <= static::DATE_INT_MIN) {
				$date = static::DATE_INT_MIN;
			} else if ($date >= static::DATE_INT_MAX) {
				$date = static::DATE_INT_MAX;
			}
		}

		return ((int)$date ?: false);
	}
	
	public static function date2Components($date) {
		
		$num_sequence = 0;
		
		$str_sequence_separator = null;
		
		if (strpos($date, ' ') !== false) {
			$str_sequence_separator = ' ';
		} else if (strpos($date, 'T') !== false) {
			$str_sequence_separator = 'T';
		}
		
		if ($str_sequence_separator !== null) {
				
			$date = explode($str_sequence_separator, $date);

			if (strpos($date[1], ':') !== false) { // hours:minutes(:seconds+zone)
				
				$arr_time = explode(':', $date[1]);
				$num_sequence = (((int)$arr_time[0] * 60) + (int)$arr_time[1]);
			} else {
				
				$num_sequence = (int)$date[1];
			}
			
			$date = $date[0];
		}
		
		$num_sequence += static::DATE_INT_SEQUENCE_NULL;
		
		$date = str_replace(['/', '.'], '-', $date);
		$str_min = '';
		
		if (substr($date, 0, 1) == '-') { // -13-10-5456
			$date = substr($date, 1);
			$str_min = '-';
		} else if (strpos($date, '--') !== false) { // 13-10--5456
			$date = str_replace('--', '-', $date);
			$str_min = '-';
		}
		$arr_date = array_filter(explode('-', $date));
		
		$year = (string)($arr_date[0] ?? 0);
		
		if (strlen($year) > 2) { // yyy-m-d or ∞ (unicode length 3)
			
			$year = ($year === '∞' ? '∞' : (int)$year);
				
			$arr_date = [($year !== 0 ? $str_min.$year : 0), str_pad((int)($arr_date[1] ?? 0), 2, '0', STR_PAD_LEFT), str_pad((int)($arr_date[2] ?? 0), 2, '0', STR_PAD_LEFT), str_pad($num_sequence, 4, '0', STR_PAD_LEFT)];
		} else { // d-m-y
			
			$num_length = count($arr_date);
		
			$year = $arr_date[$num_length-1];
			$year = ($year === '∞' ? '∞' : (int)$year);
				
			$arr_date = [($year !== 0 ? $str_min.$year : 0), str_pad((int)($arr_date[$num_length-2] ?? 0), 2, '0', STR_PAD_LEFT), str_pad((int)($arr_date[$num_length-3] ?? 0), 2, '0', STR_PAD_LEFT), str_pad($num_sequence, 4, '0', STR_PAD_LEFT)];
		}

		return $arr_date;
	}
	
	public static function int2Date($date, $do_ymd = false) {
		
		if ($date == static::DATE_INT_MAX) {
			
			$date = '∞';
		} else if ($date == static::DATE_INT_MIN) {
			
			$date = '-∞';
		} else {
			
			$str_min = '';
			
			if ($date < 0) {
				$str_min = '-';
				$str_year = substr($date, 1, -8);
			} else {
				$str_year = substr($date, 0, -8);
			}
			
			if (strlen($date) < 8 + 3) {
				$str_year = str_pad($str_year, 3, '0', STR_PAD_LEFT);
			}
			
			$num_sequence = (int)substr($date, -4, 4);
			
			if ($do_ymd) {
				$date = $str_min.$str_year.'-'.substr($date, -8, 2).'-'.substr($date, -6, 2);
				$date = str_replace('-00', '', $date);
			} else {
				$date = substr($date, -6, 2).'-'.substr($date, -8, 2).'-'.$str_min.$str_year;
				$date = str_replace('00-', '', $date);
			}

			if ($num_sequence != static::DATE_INT_SEQUENCE_NULL) {

				$date .= ' '.($num_sequence - static::DATE_INT_SEQUENCE_NULL);
			}
		}
		
		return $date;
	}
		
	public static function dateInt2DateStandard($date) {
		
		$date = substr($date, 0, -8).'-'.substr($date, -8, 2).'-'.substr($date, -6, 2);
		$date = str_replace('-00', '', $date);
		
		return $date;
	}
	
	public static function chronologyDateInt2Date($date, $arr_chronology_statement, $int_identifier) {
		
		if (!$date) {
			return false;
		}
		
		$str_date = self::int2Date($date);
		$str_classifier = '';
		
		if ($arr_chronology_statement) {
				
			if ($arr_chronology_statement['offset_amount'] || $arr_chronology_statement['cycle_object_id']) {
				
				$str_classifier = '~';
			} else if ($arr_chronology_statement['date_direction']) {
				
				$arr_time_directions_internal = StoreType::getTimeDirectionsInternal(true);
				
				switch ($int_identifier) {
					case StoreType::DATE_START_START:
					case StoreType::DATE_END_START:
						if ($arr_chronology_statement['date_direction'] != $arr_time_directions_internal[StoreType::TIME_AFTER_BEGIN]) {
							$str_classifier = $arr_chronology_statement['date_direction'];
						}
						break;
					case StoreType::DATE_START_END:
					case StoreType::DATE_END_END:
						if ($arr_chronology_statement['date_direction'] != $arr_time_directions_internal[StoreType::TIME_BEFORE_END]) {
							$str_classifier = $arr_chronology_statement['date_direction'];
						}
						break;
				}
			}
		}
		
		if ($str_classifier) {
			$str_date = $str_classifier.' '.$str_date;
		}
		
		return $str_date;
	}
	
	public static function dateInt2Compontents($date) {
		
		$date = (int)$date;
		$num_date = (abs($date) % 100000000);
		$num_year = ((abs($date) - $num_date) / 100000000) * (($date > 0) - ($date < 0));
		$num_absolute = (($num_year + (static::DATE_INT_CALC / 100000000)) * 100000000) + $num_date;
		
		$num_date = $num_date + 100000000; // Add 100000000 to make e.g. '00005000' a separate valid integer
		
		return ['year' => $num_year, 'date' => $num_date, 'absolute' => $num_absolute];
	}
	
	public static function dateInt2Absolute($date) {
		
		$date = (int)$date;
		$num_date = (abs($date) % 100000000);
		$num_year = ((abs($date) - $num_date) / 100000000) * (($date > 0) - ($date < 0));
		$num_absolute = (($num_year + (static::DATE_INT_CALC / 100000000)) * 100000000) + $num_date;

		return $num_absolute;
	}
	
	public static function dateSQL2Compontents($name) {
		
		$name_date = '(ABS('.$name.') % 100000000)'; // Add 100000000 to make e.g. '00005000' a valid integer
		$name_year = '((ABS('.$name.') - '.$name_date.') / 100000000) * SIGN('.$name.')';
		$name_absolute = '(('.$name_year.' + '.(static::DATE_INT_CALC / 100000000).') * 100000000) + '.$name_date;
		
		$name_date = '('.$name_date.' + 100000000)'; // Add 100000000 to make e.g. '00005000' a valid integer
		
		return ['year' => $name_year, 'date' => $name_date, 'absolute' => $name_absolute];
	}
	
	public static function dateSQL2Absolute($name) {
		
		$name_date = '(ABS('.$name.') % 100000000)'; // Add 100000000 to make e.g. '00005000' a valid integer
		$name_year = '((ABS('.$name.') - '.$name_date.') / 100000000) * SIGN('.$name.')';
		$name_absolute = '(('.$name_year.' + '.(static::DATE_INT_CALC / 100000000).') * 100000000) + '.$name_date;
			
		return $name_absolute;
	}
}
