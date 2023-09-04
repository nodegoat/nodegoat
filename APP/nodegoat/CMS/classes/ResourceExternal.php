<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ResourceExternal {
	
	
	protected $arr_resource = [];
	protected $identifier = false;
	protected $view = false;
	
	const PARSE_DEFAULT = 'json'; // JSON
	const PARSE_TEXT = 'text';
	const PARSE_XML = 'xml';
	const PARSE_YAML = 'yaml';
	
	protected $str_result = '';
	protected $arr_result_values = false;
	protected $mode_result_parse = self::PARSE_DEFAULT;
	
	protected $arr_filter = false;
	protected $arr_limit = [0, 100];
	protected $arr_order = [];
	
	protected $socket_conversion = false;
	
	protected $timeout = 45; // Seconds
	protected $timeout_conversion = 30; // Seconds
	
	const VIEW_PLAIN = 1;
	
	const TAGCODE_TEST_LIMIT = '/\[\[limit(?:=[\d]*)?\]\]/';
	const TAGCODE_PARSE_LIMIT = '/\[\[limit(?:=([\d]*))?\]\]/';
		
    public function __construct($arr_resource, $identifier = false, $view = self::VIEW_PLAIN) {
		
		
		$this->arr_resource = $arr_resource;
		$this->identifier = $identifier;
		$this->view = $view;
    }
		
	public function request() {
		
		// [query=name]...[/query]
		// [variable(=name(:type))]...[/variable]
		
		$str_query = $this->arr_resource['query'];
		
		if ($this->arr_filter !== false) {
			
			$str_uri_template = $this->arr_resource['response_uri_template'];
			
			$str_uri_template_begin = false;
			$str_uri_template_end = false;
			
			if ($str_uri_template) {
			
				$pos_identifier = strpos($str_uri_template, '[[identifier]]');
				if ($pos_identifier !== false) {
					$str_uri_template_begin = substr($str_uri_template, 0, $pos_identifier);
					$str_uri_template_end = substr($str_uri_template, $pos_identifier + 14);
				} else {
					$str_uri_template_begin = $str_uri_template;
				}
			}
			
			foreach ($this->arr_filter as $name_query => $arr_filter_variables) {
				
				$str_query = preg_replace_callback(
					'/\[query='.$name_query.'\](.+?)\[\/query\]/si',
					function($arr_matches_query) use ($arr_filter_variables, $str_uri_template_begin, $str_uri_template_end) {
						
						$str = $arr_matches_query[1];
						$count = 1;
						
						return preg_replace_callback(
							'/\[variable(?:=([\w]+)(?:\:([a-z-]+))?)?\](.+?)\[\/variable\]/si',
							function($arr_matches_variable) use ($arr_filter_variables, $str_uri_template_begin, $str_uri_template_end, &$count) {
								
								$name_variable = $arr_matches_variable[1];
								$type_variable = $arr_matches_variable[2];
								
								if (!$name_variable) {
									
									$name_variable = $count;
									$count++;
								}

								$str_value = '';
								
								if (!is_array($arr_filter_variables)) {
									
									$str_value = $arr_filter_variables;
								} else if ($arr_filter_variables[$name_variable]) {
									
									if (is_array($arr_filter_variables[$name_variable])) {
																	
										foreach ($arr_filter_variables[$name_variable] as $key => $arr_value) {
											
											$str_value_check = (is_array($arr_value) ? $arr_value['value'] : $arr_value);
											
											if ($str_uri_template_begin) { // Try to find a match for the current URI template, in a possible list with other identifiers
												
												if (strpos($str_value_check, $str_uri_template_begin) !== false && (!$str_uri_template_end || strpos($str_value_check, $str_uri_template_end) !== false)) {
													
													$str_value = $str_value_check;
													break;
												} else if (!$str_value) {
													
													$str_value = $str_value_check;
												}
											} else {
												$str_value = $str_value_check;
												break;
											}
										}
									} else {
										$str_value = $arr_filter_variables[$name_variable];
									}
								}
								
								$str_value = trim($str_value);
								
								if ($str_value) {
									
									if ($type_variable == 'uri-identifier' && $str_uri_template_begin) {
										
										if (strpos($str_value, $str_uri_template_begin) !== false) {
											$str_value = substr($str_value, strlen($str_uri_template_begin));
											if ($str_uri_template_end) {
												$pos_end = strrpos($str_value, $str_uri_template_end);
												if ($pos_end !== false) {
													$str_value = substr($str_value, 0, $pos_end);
												}
											}
										}
									}
									
									if ($this->arr_resource['protocol'] == 'api') { // Encode only the values in case of url targeted protocols
										$str_value = rawurlencode($str_value);
									}
								}
								
								return $str_value;
							},
							$str
						);
					},
					$str_query
				);
			}
			$str_query = preg_replace('/\[query=[\w]+\].+?\[\/query\]/si', '', $str_query);
		} else {
			
			$str_query = preg_replace('/\[query=[\w]+\]/si', '', $str_query);
			$str_query = preg_replace('/\[\/query\]/si', '', $str_query);
			
			if ($this->arr_resource['protocol'] == 'api') { // Encode only the values in case of url targeted protocols
				
				$str_query = preg_replace_callback(
					'/\[variable(?:=[\w]+(?:\:[a-z-]+)?)?\](.+?)\[\/variable\]/si',
					function($arr_matches_variable) {
						
						$str_value = rawurlencode($arr_matches_variable[1]);
						
						return $str_value;
					},
					$str_query
				);
			} else {
				
				$str_query = preg_replace('/\[variable(?:=[\w]+(?:\:[a-z-]+)?)?\]/si', '', $str_query);
				$str_query = preg_replace('/\[\/variable\]/si', '', $str_query);
			}
		}
		
		if ($this->arr_resource['protocol'] == 'sparql') {
			
			$num_pos = stripos($str_query, 'SELECT');
			$str_before = trim(substr($str_query, 0, $num_pos));
			$str_after = trim(substr($str_query, $num_pos + strlen('SELECT')));
			
			$str_query = ($str_before ? $str_before.' ' : '').'SELECT';
			
			if (stripos($str_after, 'DISTINCT') !== 0) {
			
				$str_query .= ' DISTINCT';
			}
			
			$str_query .= ' '.$str_after;
		} else {
			
			$str_query = trim($str_query);
		}
		
		$func_url = function($str_query, $arr_options) {

			if ($this->arr_resource['protocol'] == 'sparql' && !preg_match(static::TAGCODE_TEST_LIMIT, $str_query)) {
				
				$str_query .= " OFFSET ".$arr_options['offset']." LIMIT ".$arr_options['limit'];
			} else {
							
				$num_limit = $arr_options['limit'];
				$num_offset = $arr_options['offset'];
				
				preg_match(static::TAGCODE_PARSE_LIMIT, $str_query, $arr_match);
				$num_limit_max = (int)$arr_match[1];
				
				if ($num_limit_max && $num_limit > $num_limit_max) {
					$num_offset = floor($num_offset / $num_limit) * $num_limit_max;
					$num_limit = $num_limit_max;
				}
				
				$str_query = preg_replace(static::TAGCODE_TEST_LIMIT, $num_limit, $str_query);
				$str_query = str_replace('[[offset]]', $num_offset, $str_query);
			}

			if ($this->arr_resource['protocol'] == 'sparql') {
				
				$str_url = $this->arr_resource['url'].rawurlencode($str_query).$this->arr_resource['url_options']; // Encode the full query to be passed verbatim
			} else {
				
				$str_query = str_replace(["\r", "\n"], '', $str_query); // Allow for line breaks in the query, but do clean it before running it
				$str_query = str_replace(' ', '%20', $str_query); // Preserve spaces
				
				$str_url = $this->arr_resource['url'].$str_query.$this->arr_resource['url_options'];
			}
			
			//$str_url .= (!strpos($str_url, '?') ? '?' : '&').'timeout='.(($this->timeout * 1000) - (5 * 1000)); // Try to indicate a endpoint timeout is milliseconds, get possible results gracefully
			
			return $str_url;
		};
		
		Labels::setVariable('resource_name', $this->arr_resource['name']);
		Labels::setVariable('seconds', $this->timeout);
		status(getLabel('msg_external_resource_running'), false, false, 3000);
		
		$str_content_type = '';
		$str_url = $func_url($str_query, ['offset' => $this->arr_limit[0], 'limit' => $this->arr_limit[1]]);
		$arr_request_settings = [
			'timeout' => $this->timeout,
			'headers' => (($this->arr_resource['url_headers'] ?: []) + ['Accept' => 'application/json, application/ld+json, */*; q=0.01']),
			'header_callback' => function($str_header) use (&$str_content_type) {
				
				if (strpos($str_header, 'Content-Type:') !== false) {
					$str_content_type = trim(str_replace('Content-Type:', '', $str_header));
				}
			}
		];
		
		$data = new FileGet($str_url, $arr_request_settings, true);
		$str_result = $data->get();

		if (!$str_result) {
			
			if ($data->getError() == 'timeout') {
				
				status(getLabel('msg_external_resource_timeout'), false, false, 5000);
				$msg_found_records = getLabel('msg_external_resource_timeout_found_records');
				
				$timer = time();
				$limit = 1;
				
				while ((time() - $timer) < $this->timeout) {

					$str_url = $func_url($str_query, ['offset' => $this->arr_limit[0], 'limit' => $limit]);
					$arr_request_settings['timeout'] = ($this->timeout / 2);
					
					$data = new FileGet($str_url, $arr_request_settings, true);
					$str_result_test = $data->get();
					
					if (!$str_result_test) {
						
						if (!$str_result && $data->getError() == 'timeout') {
							msg(getLabel('msg_external_resource_timeout_stop'), 'ATTENTION', LOG_CLIENT, false, false, 10000);
						}
						
						break;
					}
					
					usleep(500000); // Do not pressure, 0.5 seconds
					
					Labels::setVariable('count', $limit);
					status(Labels::parseTextVariables($msg_found_records), false, false, 2000);
					
					$str_result = $str_result_test;
					$limit++;
				}
			} else if ($data->getError()) {
				
				Labels::setVariable('response', $data->getErrorResponse());
				Labels::setVariable('debug_url', $str_url);
				Labels::setVariable('debug_query', $str_query);
				
				$str_message = getLabel('msg_external_resource_error');
				
				msg($str_message, 'ATTENTION', LOG_CLIENT, false, false, 10000);
			}
		}
		
		if ($str_result) {
				
			$this->parseContentType($str_content_type, $str_url);
			
			if ($this->mode_result_parse == static::PARSE_TEXT) {
				
				$str_result = value2JSON(['text' => $str_result]);
			} else if ($this->mode_result_parse == static::PARSE_XML) {
				
				$parse = new ParseXML2JSON($str_result);
				$parse->setMode(ParseXML2JSON::MODE_COMPACT);
				$str_result = $parse->get();
			} else if ($this->mode_result_parse == static::PARSE_YAML) {
				
				$str_result = yaml_parse($str_result);
				$str_result = value2JSON($str_result);
			}

			$this->str_result = $str_result;
		}

		return (bool)$this->str_result;
	}
	
	protected function parseContentType($str_content_type, $str_url) {
		
		$str_content_type = explode(';', $str_content_type); // E.g. 'application/json; charset=utf-8'
		$str_content_type = $str_content_type[0];
		
		if ($str_content_type) {
			
			if (strEndsWith($str_content_type, static::PARSE_DEFAULT)) {
				
				return;
			} else if (strEndsWith($str_content_type, static::PARSE_TEXT)) {
				
				$this->mode_result_parse = static::PARSE_TEXT;
				return;
			} else if (strEndsWith($str_content_type, static::PARSE_XML)) {
				
				$this->mode_result_parse = static::PARSE_XML;
				return;
			} else if (strEndsWith($str_content_type, static::PARSE_YAML)) {
				
				$this->mode_result_parse = static::PARSE_YAML;
				return;
			}
		}
		
		$arr_url = parse_url($str_url);
		
		if ($arr_url['query']) {
			
			if (strEndsWith($arr_url['query'], static::PARSE_DEFAULT)) {
				
				return;
			} else if (strEndsWith($arr_url['query'], static::PARSE_TEXT)) {
				
				$this->mode_result_parse = static::PARSE_TEXT;
				return;
			} else if (strEndsWith($arr_url['query'], static::PARSE_XML)) {
				
				$this->mode_result_parse = static::PARSE_XML;
				return;
			} else if (strEndsWith($arr_url['query'], static::PARSE_YAML)) {
				
				$this->mode_result_parse = static::PARSE_YAML;
				return;
			}
		}
		
		if ($arr_url['path']) {
			
			if (strEndsWith($arr_url['path'], static::PARSE_DEFAULT)) {
				
			} else if (strEndsWith($arr_url['path'], static::PARSE_TEXT)) {
				
				$this->mode_result_parse = static::PARSE_TEXT;
			} else if (strEndsWith($arr_url['path'], static::PARSE_XML)) {
				
				$this->mode_result_parse = static::PARSE_XML;
			} else if (strEndsWith($arr_url['path'], static::PARSE_YAML)) {
				
				$this->mode_result_parse = static::PARSE_YAML;
			}
		}
	}
		
	public function getRequestVariables() {
		
		$arr = [
			'offset' => false,
			'limit' => false
		];
		
		$str_query = $this->arr_resource['query'];
		
		if ($this->arr_resource['protocol'] == 'sparql' || strpos($str_query, '[[offset]]') !== false) {
			$arr['offset'] = true;
		}
		
		$has_match = preg_match(static::TAGCODE_PARSE_LIMIT, $str_query, $arr_match);
		
		if ($has_match) {
			
			$arr['limit'] = ($arr_match[1] ?: true);
		} else {
			
			if ($this->arr_resource['protocol'] == 'sparql') {
				$arr['limit'] = true;
			}
		}
				
		return $arr;	
	}
	
	public function getQueryVariables($name = false) {
		
		$arr = [];
		
		$str_query = $this->arr_resource['query'];
		
		preg_match_all('/\[query=('.($name ?: '[\w]+').')\](.+?)\[\/query\]/si', $str_query, $arr_matches_queries, PREG_SET_ORDER);
		
		foreach ($arr_matches_queries as $arr_matches_query) {
			
			$name_query = $arr_matches_query[1];
			$value_query = $arr_matches_query[2];
			$count = 1;
			
			preg_match_all('/\[variable(?:=([\w]+)(?:\:([a-z-]+))?)?\](.+?)\[\/variable\]/si', $value_query, $arr_matches_variables, PREG_SET_ORDER);
			
			foreach ($arr_matches_variables as $arr_matches_variable) {
				
				$name_variable = $arr_matches_variable[1];
				$type_variable = $arr_matches_variable[2];
				$value_variable = $arr_matches_variable[3];
				
				if (!$name_variable) {
					
					$name_variable = $count;
					$count++;
				}

				$arr[$name_query][$name_variable] = ['type' => $type_variable, 'value' => $value_variable];
			}
		}
			
		return $arr;
	}
	
	public function getQueryVariablesFlat($name = false) {
		
		$arr_query_variables = $this->getQueryVariables($name);
		
		$arr = [];
		
		foreach ($arr_query_variables as $name_query => $arr_variables) {
			foreach ($arr_variables as $name_variable => $arr_variable) {
				
				$str_identifier = 'query_'.str2Label($name_query).'_variable_'.str2Label($name_variable);
				$str_name = $name_query.': '.$name_variable.($arr_variable['type'] ? ' ('.$arr_variable['type'].')' : '');
				
				$arr[] = ['id' => $str_identifier, 'name' => $str_name];
			}
		}
		
		return $arr;
	}
	
	public function getResponseValues($include_default = false) {
			
		$arr = [];
		
		if ($include_default) {
			
			$arr['uri'] = $this->arr_resource['response_uri'];
			$arr['label'] = $this->arr_resource['response_label'];
		}
		
		$arr += ($this->arr_resource['response_values'] ?: []);
					
		return $arr;
	}
	
	public function hasResult() {
		
		$arr_result = json_decode($this->str_result, true);
		
		if (!$arr_result) {
			return false;
		}
		
		return true;
	}
	
	public function getResultRaw() {
		
		return $this->str_result;
	}
	
	public function getResultValuesCount() {
		
		if ($this->arr_result_values === false) {
			$this->getResultValues(false);
		}
		
		return count($this->arr_result_values);
	}
	
	public function setResultConversionSocket($socket) {
		
		$this->socket_conversion = $socket;
		
		if (!$socket) {
			return false;
		}
	}
	
	public function getResultValues($do_flat = true) {
		
		if ($this->arr_result_values !== false) {
			return $this->processResultValues($do_flat);
		}
			
		$arr_result = json_decode($this->str_result, true);
		$this->arr_result_values = [];
		
		if (!$arr_result) {
			return $this->arr_result_values;
		}
		
		if ($this->socket_conversion) { // Prepare conversion process

			$arr_result_value = [];
			$arr_result_value_options = [];
			$is_processing = false;
			
			$this->socket_conversion->process = function($str) use (&$arr_result_value_options, &$arr_result_value, &$is_processing) {
				
				$arr_conversions_output = json_decode($str, true);
				$arr_conversions_output = $arr_conversions_output[WebServiceTaskIngestSource::$name];
				
				foreach ($arr_result_value_options as $name => $arr_options) {
					
					$value = $arr_conversions_output[$arr_options['identifier']]['output'];
					
					if ($arr_options['output_identifier']) {
						$value = $value[$arr_options['output_identifier']];
					}
					
					$arr_result_value[$name] = $value;
				}
				
				$this->arr_result_values[] = $arr_result_value;
				
				$is_processing = false;
			};
		}

		// Parse Result
		 
		$arr_response_values = $this->getResponseValues(true);

		if ($this->arr_resource['response_uri']['value'] || $this->arr_resource['response_label']['value']) {
			
			try {
				$traverse = new TraverseJSON($this->arr_resource['response_uri']['value']);
				$arr_uri = $traverse->get($arr_result);
			} catch (Exception $e) {
				
				Labels::setVariable('parse_name', 'Response URI');
				error(getLabel('msg_external_resource_error_parse_value'), TROUBLE_ERROR, LOG_BOTH, $this->arr_resource['response_uri']['value'], $e);
			}
			$has_multi = $traverse->hasGroups();
			
			try {
				$traverse = new TraverseJSON($this->arr_resource['response_label']['value'], $has_multi);
				$arr_label = $traverse->get($arr_result);
			} catch (Exception $e) {
				
				Labels::setVariable('parse_name', 'Response Label');
				error(getLabel('msg_external_resource_error_parse_value'), TROUBLE_ERROR, LOG_BOTH, $this->arr_resource['response_label']['value'], $e);
			}
			$arr_values = [];
				
			foreach ($this->arr_resource['response_values'] as $name => $arr_response_value) {
				
				try {
					$traverse = new TraverseJSON($arr_response_value['value'], $has_multi);
					$arr_values[$name] = $traverse->get($arr_result);
				} catch (Exception $e) {
				
					Labels::setVariable('parse_name', 'Response Value \''.strEscapeHTML($name).'\'');
					error(getLabel('msg_external_resource_error_parse_value'), TROUBLE_ERROR, LOG_BOTH, $arr_response_value['value'], $e);
				}
			}
			
			foreach ($arr_uri as $key_group => $str_uri) {
								
				$str_label = $arr_label[$key_group];

				$arr_result_value = ['uri' => $str_uri, 'label' => $str_label];

				foreach ($this->arr_resource['response_values'] as $name => $arr_response_value) {
					
					$str = $arr_values[$name][$key_group];
									
					$arr_result_value[$name] = $str;
				}
				
				if ($this->socket_conversion) {
					
					$arr_result_value_options = [];
					$arr_conversions = [];

					foreach ($arr_response_values as $name => $arr_response_value) {
						
						if (!$arr_response_value['conversion_id']) {
							continue;
						}
							
						$value = $arr_result_value[$name];
						$str_identifier = ($value ? value2Hash($value) : '').'_'.$arr_response_value['conversion_id'];
										
						$arr_conversions[$str_identifier] = [
							'identifier' => $str_identifier,
							'script' => $arr_response_value['conversion_script'],
							'input' => $value
						];
						
						$arr_result_value_options[$name] = ['identifier' => $str_identifier, 'output_identifier' => $arr_response_value['conversion_output_identifier']];
						
						$arr_result_value[$name] = '';
					}
					
					if ($arr_conversions) {

						$this->socket_conversion->send(value2JSON([
							'arr_tasks' => [
								WebServiceTaskIngestSource::$name => [
									'arr_data' => $arr_conversions
								]
							]
						]));
						
						$is_processing = true;
						$time_conversion = microtime(true);

						while ($is_processing) {
							
							$this->socket_conversion->run();
							
							if ((microtime(true) - $time_conversion) > $this->timeout_conversion) {
								error(getLabel('msg_socket_client_timeout'));
							}
						}
					} else {
						
						$this->arr_result_values[] = $arr_result_value;
					}
				} else {
					
					$this->arr_result_values[] = $arr_result_value;
				}
			}
		}
		
		return $this->processResultValues($do_flat);
	}
	
	protected function processResultValues($do_flat) {
		
		$str_uri_template = $this->arr_resource['response_uri_template'];
			
		if ($str_uri_template) {
			
			$pos_identifier = strpos($str_uri_template, '[[identifier]]');
			$str_uri_template_start = ($pos_identifier !== false ? substr($str_uri_template, 0, $pos_identifier) : $str_uri_template);
		}
		
		foreach ($this->arr_result_values as &$arr_result_value) {
			
			foreach ($arr_result_value as $name => &$value) {
				
				if ($name === 'uri') {
						
					$value = (is_array($value) ? current($value) : $value); // Get first URI when array
					
					if ($str_uri_template && strpos($value, $str_uri_template_start) === false) {

						if ($pos_identifier !== false) {
							$value = str_replace('[[identifier]]', $value, $str_uri_template);
						} else {
							$value = $str_uri_template.$value;
						}
					}
					
					continue;
				}
				
				if ($do_flat) {
					$value = (is_array($value) ? implode(', ', $value) : $value);
				}
			}
		}
		
		return $this->arr_result_values;
	}

	public function setFilter($arr_filter, $is_flat = false) {
		
		if (!is_array($arr_filter)) {
			
			$arr_filter = false;
		} else {
			
			if ($is_flat) {
			
				$arr_query_variables = $this->getQueryVariables();
				$arr_filter_collect = [];
				
				foreach ($arr_filter as $key => $value) {
				
					foreach ($arr_query_variables as $name_query => $arr_variables) {
						foreach ($arr_variables as $name_variable => $arr_variable) {
							
							$str_identifier = 'query_'.str2Label($name_query).'_variable_'.str2Label($name_variable);
							
							if ($str_identifier == $key) {
								$arr_filter_collect[$name_query][$name_variable] = $value;
							}
						}
					}
				}
				
				$arr_filter = $arr_filter_collect;
			}			
		}
		
		$this->arr_filter = $arr_filter;
	}
	
	public function setLimit($arr_limit) {
		
		// $arr_limit = 100, array(200, 100) (from 200 to 300)
		
		$arr_limit = (is_array($arr_limit) ? $arr_limit : [0, $arr_limit]);
		
		$this->arr_limit = $arr_limit;
	}
	
	public function setOrder($arr_order) {
		
		// $arr_order = array('name' => "asc/desc", value => "asc/desc")
				
		$this->arr_order = $arr_order;
	}
		
	public static function formatToFormValueFilter($type, $value, $name, $arr_type_options = false) {
	
		switch ($type) {
			case 'int':
				$value = (is_array($value) ? $value : ['value' => $value]);
				$format = '<input type="number" name="'.$name.'[value]" value="'.$value['value'].'" />';
				break;
			case 'text':
			case '':
			default:
				$value = (is_array($value) ? $value : ['value' => $value]);
				$format = '<input type="text" name="'.$name.'[value]" value="'.$value['value'].'" />';
				break;
		}
		
		return $format;
	}
	
	public function cleanupFilterForm($arr_filter) {
		
		$arr_query_variables = $this->getQueryVariables();

		foreach ($arr_filter as $name_query => &$arr_filter_variables) {
				
			foreach ($arr_filter_variables as $name_variable => &$arr_filter_variable) {
				
				if (!$arr_query_variables[$name_query][$name_variable]) {
					unset($arr_filter[$name_query][$name_variable]);
					continue;
				}
				
				$arr_filter_variable = self::cleanupFilterFormTypeValuesArr($arr_query_variables[$name_query][$name_variable]['type'], $arr_filter_variable);
				
				if (!$arr_filter[$name_query][$name_variable]) {
					unset($arr_filter[$name_query][$name_variable]);
				}
			}
			
			if (!$arr_filter[$name_query]) {
				unset($arr_filter[$name_query]);
			}
		}
		
		return $arr_filter;
	}
	
	private static function cleanupFilterFormTypeValuesArr($type, $arr_values) {
		
		foreach ($arr_values as $key => $value) {
		
			$use_value = (is_array($value) ? $value['value'] : $value); // Account for complex filter values (i.e. using equality)
						
			if ($type == 'boolean') {
				continue;
			} else if ($type == 'int' && (int)$use_value) {
				continue;
			} else if ($type == 'numeric' && (float)$use_value) {
				continue;
			} else if ($type == 'date') {
				if ($value['value_now']) {
					$value['value'] = $use_value = 'now';
				}
				if ($value['range_now']) {
					$value['range'] = 'now';
				}
				unset($value['value_now'], $value['range_now']);
				$arr_values[$key] = $value;
				if (StoreTypeObjects::date2Integer($use_value)) {
					continue;
				}
			} else if ($use_value) {
				continue;
			}
			unset($arr_values[$key]);
		}
		
		return $arr_values;
	}
	
	public function getURL() {
		
		if ($this->arr_resource['protocol'] == 'static') {
			$str_url = $this->arr_resource['url'].$this->identifier.$this->arr_resource['url_options'];
		} else {
			$str_url = $this->identifier;
		}
		
		if ($this->view == self::VIEW_PLAIN) {
			$str_url = strEscapeHTML($str_url);
			$return = '<a href="'.$str_url.'" target="_blank">'.$str_url.'</a>';
		}
		
		return $return;
	}
	
		
	public static function getProtocols() {
	
		return [
			['id' => 'sparql', 'name' => 'SPARQL'],
			['id' => 'api', 'name' => 'API'],
			['id' => 'static', 'name' => getLabel('inf_external_resource_static')]
		];
	}
	
	public static function getReferenceTypes() {
		
		$arr = [['id' => '', 'name' => 'URL', 'value' => 'url']];
		
		return $arr;
	}
}
