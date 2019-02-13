<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ExternalResource {
	
	private $arr_resource = [];
	private $identifier = false;
	private $view = false;
	
	private $arr_filter = [];
	private $arr_limit = [0, 100];
	private $arr_order = [];
	
	private $timeout = 45;
		
    public function __construct($arr_resource, $identifier = false, $view = 'plain') {
		
		$this->arr_resource = $arr_resource;
		$this->identifier = $identifier;
		$this->view = $view;
    }
		
	public function request() {
		
		// [query=name]...[/query]
		// [variable(=name(:type))]...[/variable]
		
		$query = $this->arr_resource['query'];
		
		if ($this->arr_filter) {
			
			foreach ($this->arr_filter as $name_query => $arr_filter_variables) {
				
				$query = preg_replace_callback(
					'/\[query='.$name_query.'\](.+?)\[\/query\]/si',
					function($arr_matches) use ($arr_filter_variables) {
						
						$str = $arr_matches[1];
						$count = 1;
						
						return preg_replace_callback(
							'/\[variable(?:=([\w]+(?:\:[a-z]+)?))?\](.+?)\[\/variable\]/si',
							function($arr_matches) use ($arr_filter_variables, $count) {
								
								$name_variable = $arr_matches[1];
								if (!$name_variable) {
									$name_variable = $count;
									$count++;
								}
								
								$str_value = '';
								
								if (!is_array($arr_filter_variables)) {
									$str_value = $arr_filter_variables;
								} else if ($arr_filter_variables[$name_variable]) {
									if (is_array($arr_filter_variables[$name_variable])) {
										$str_value = $arr_filter_variables[$name_variable][0]['value'];
									} else {
										$str_value = $arr_filter_variables[$name_variable];
									}
								}
								
								if ($str_value && $this->arr_resource['protocol'] == 'api') { // Encode only the values in case of url targeted protocols
									$str_value = rawurlencode($str_value);
								}
								
								return $str_value;
							},
							$str
						);
					},
					$query
				);
			}
			$query = preg_replace('/\[query=([\w]+)\](.+?)\[\/query\]/si', '', $query);
		} else {
			
			$query = preg_replace('/\[query=([\w]+)\]/si', '', $query);
			$query = preg_replace('/\[\/query\]/si', '', $query);
			$query = preg_replace('/\[variable(=[\w]+(\:[a-z]+)?)?\]/si', '', $query);
			$query = preg_replace('/\[\/variable\]/si', '', $query);
		}
		
		if ($this->arr_resource['protocol'] == 'sparql') {
			
			$str_pos = strpos($query, 'SELECT');
			$str_before = trim(substr($query, 0, $str_pos));
			$str_after = trim(substr($query, $str_pos + strlen('SELECT')));
			
			$query = $str_before.' SELECT';
			
			if (strpos($str_after, 'DISTINCT') !== 0) {
			
				$query .= ' DISTINCT';
			}
			
			$query .= ' '.$str_after;
		}
		
		$func_url = function($query, $arr_options) {

			if ($this->arr_resource['protocol'] == 'sparql' && strpos($query, '[[limit]]') === false) {
				
				$query .= " OFFSET ".$arr_options['offset']." LIMIT ".$arr_options['limit'];
			} else {
				
				$query = str_replace('[[offset]]', $arr_options['offset'], $query);
				$query = str_replace('[[limit]]', $arr_options['limit'], $query);
			}

			if ($this->arr_resource['protocol'] == 'sparql') {
				
				$url = $this->arr_resource['url'].rawurlencode($query).$this->arr_resource['url_options']; // Encode the full query to be passed verbatim
			} else {
				
				$query = str_replace(["\r", "\n"], '', $query); // Allow for line breaks in the query, but do clean it before running it
				$query = str_replace(' ', '%20', $query); // Preserve spaces
				
				$url = $this->arr_resource['url'].$query.$this->arr_resource['url_options'];
			}
			
			$url .= (!strpos($url, '?') ? '?' : '&').'timeout='.(($this->timeout * 1000) - (5 * 1000)); // Try to indicate a endpoint timeout is milliseconds, get possible results gracefully
			
			return $url;
		};
		
		Labels::setVariable('name', $this->arr_resource['name']);
		Labels::setVariable('seconds', $this->timeout);
		status(getLabel('msg_external_resource_running'), false, false, 3000);

		$url = $func_url($query, ['offset' => $this->arr_limit[0], 'limit' => $this->arr_limit[1]]);
		$data = new FileGet($url, ['timeout' => $this->timeout], true);
		$result = $data->get();
		
		if (!$result) {
			
			if ($data->getError() == 'timeout') {
				
				status(getLabel('msg_external_resource_timeout'), false, false, 5000);
				$msg_found_records = getLabel('msg_external_resource_timeout_found_records');
				
				$timer = time();
				$limit = 1;
				
				while ((time() - $timer) < $this->timeout) {

					$url = $func_url($query, ['offset' => $this->arr_limit[0], 'limit' => $limit]);
					$data = new FileGet($url, ['timeout' => $this->timeout/2], true);
					$result_test = $data->get();
					
					if (!$result_test) {
						
						if (!$result && $data->getError() == 'timeout') {
							msg(getLabel('msg_external_resource_timeout_stop'), 'ATTENTION', LOG_CLIENT, false, false, 10000);
						}
						
						break;
					}
					
					usleep(500000); // Do not pressure, 0.5 seconds
					
					Labels::setVariable('count', $limit);
					status(Labels::parseTextVariables($msg_found_records), false, false, 2000);
					
					$result = $result_test;
					$limit++;
				}
			} else if ($data->getError()) {
				
				msg(getLabel('msg_external_resource_error'), 'ATTENTION', LOG_CLIENT, false, false, 10000);
			}
		}

		$arr = [];
		$arr['raw'] = $result;
					
		$arr_result = json_decode($result, true);

		$arr['result'] = $arr_result;
		$arr['values'] = [];
		
		if ($arr_result) {
			
			if ($this->arr_resource['response_uri'] || $this->arr_resource['response_label']) {
			
				$arr_uri = self::arrValueByArr($arr_result, json_decode($this->arr_resource['response_uri'], true));
				$arr_label = self::arrValueByArr($arr_result, json_decode($this->arr_resource['response_label'], true));
				$arr_values = [];
				
				foreach ((array)$this->arr_resource['response_values'] as $name => $value) {
					$arr_values[$name] = self::arrValueByArr($arr_result, json_decode($value, true));
				}
				
				$has_template = ($this->arr_resource['response_uri_template'] && strpos($this->arr_resource['response_uri_template'], '[[identifier]]') !== false);
				
				foreach ($arr_uri as $key => $uri) {
					
					if ($has_template) {
						$uri = str_replace('[[identifier]]', $uri, $this->arr_resource['response_uri_template']);
					} else {
						$uri = $this->arr_resource['response_uri_template'].$uri;
					}
					
					$arr_add = ['uri' => $uri, 'label' => $arr_label[$key]];
					
					foreach ((array)$this->arr_resource['response_values'] as $name => $value) {
						
						$str = $arr_values[$name][$key];
						$str = (is_array($str) ? implode(', ', $str) : $str);
						
						$arr_add[$name] = $str;
					}
					
					$arr['values'][] = $arr_add;
				}
			}			
		}
			
		return $arr;
	}
	
	public function setFilter($arr_filter) {
		
		$this->arr_filter = $arr_filter;
	}
	
	public function setLimit($arr_limit) {
		
		// $arr_limit = 100, array(200, 100) (from 200 to 300)
		
		$arr_limit = (!$arr_limit || is_array($arr_limit) ? $arr_limit : [0, $arr_limit]);
		
		$this->arr_limit = $arr_limit;
	}
	
	public function setOrder($arr_order) {
		
		// $arr_order = array('name' => "asc/desc", value => "asc/desc")
				
		$this->arr_order = $arr_order;
	}
	
	public function getQueryVariables($name = false) {
		
		$arr = [];
		
		$query = $this->arr_resource['query'];
		
		preg_match_all('/\[query=('.($name ?: '[\w]+').')\](.+?)\[\/query\]/si', $query, $arr_matches_queries, PREG_SET_ORDER);
		
		foreach ($arr_matches_queries as $arr_matches_query) {
			
			$name_query = $arr_matches_query[1];
			$value_query = $arr_matches_query[2];
			$count = 1;
			
			preg_match_all('/\[variable(?:=([\w]+)(?:\:([a-z]+))?)?\](.+?)\[\/variable\]/si', $value_query, $arr_matches_variables, PREG_SET_ORDER);
			
			foreach ($arr_matches_variables as $arr_matches_variable) {
				
				$name_variable = $arr_matches_variable[1];
				if (!$name_variable) {
					$name_variable = $count;
					$count++;
				}
				$type_variable = $arr_matches_variable[2];
				$value_variable = $arr_matches_variable[3];
				
				$arr[$name_query][$name_variable] = ['type' => $type_variable, 'value' => $value_variable];
			}
		}
			
		return $arr;
	}
	
	public function getValues() {
		
		$arr = ($this->arr_resource['response_values'] ?: []);
					
		return $arr;
	}
	
	public static function formatToFormValueFilter($type, $value, $name, $type_options = false) {
	
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
			} else if ($type == 'date') {
				if ($value['value_now']) {
					$value['value'] = $use_value = 'now';
				}
				if ($value['range_now']) {
					$value['range'] = 'now';
				}
				unset($value['value_now'], $value['range_now']);
				$arr_values[$key] = $value;
				if (StoreTypeObjects::date2Int($use_value)) {
					continue;
				}
			} else if ($use_value) {
				continue;
			}
			unset($arr_values[$key]);
		}
		
		return $arr_values;
	}
		
	private function getViafReference($reference_id) {

		$resources_array = ['LC' => 'http://id.loc.gov/authorities/names/', 'BNF' => '', 'DNB' => '', 'WKP' => 'http://wikipedia.org/wiki/', 'SUDOC' => 'http://www.idref.fr/', 'BNE' => 'http://catalogo.bne.es/uhtbin/authoritybrowse.cgi?action=display&authority_id='];
		
		$cache = new FileCache('json', false, 'http://viaf.org/viaf/'.$reference_id.'/justlinks.json', DIR_HOME);
		$cache->generate(false);
		try {
			$cache->cache();
		} catch (Exception $e) {

		}
		
		$arr_viaf = json_decode($cache->getData(), true);
		$number_of_links = count($arr_viaf) - 1;
		
		$cache = new FileCache('json', false, 'http://dbpedia.org/data/'.rawurlencode($arr_viaf['WKP'][0]).'.json', DIR_HOME);
		$cache->generate(false);
		try {
			$cache->cache();
		} catch (Exception $e) {

		}
		
		$dbpedia_array = json_decode($cache->getData(), true);
		$image_url = $dbpedia_array['http://dbpedia.org/resource/'.rawurlencode($arr_viaf['WKP'][0])]['http://dbpedia.org/ontology/thumbnail'][0]['value'];
		$image_url = ($image_url ? SiteStartVars::getCacheUrl('img', ['error_source' => '/css/images/user_picture_placeholder.png'], $image_url) : '/css/images/user_picture_placeholder.png');
		
		if($this->view == 'box') {

			$return .= '<div class="viaf_box">
				<div class="viaf_expandable">
					<p>'.$number_of_links .' '.getLabel('lbl_links_available').'.</p>
					<img class="enlarge" src="'.$image_url.'" />
					<ul>';
					
						foreach ((array)$arr_viaf as $key => $value) {
							
							if(array_key_exists($key, $arr_resources)) {
								
								if($arr_resources[$key]){
									$return .= '<li><a href="'.$arr_resources[$key].$value[0].'" target="_new">'.$key.'</a></li>';
								} else {
									$return .= '<li><a href="'.$value[0].'/" target="_new">'.$key.'</a></li>';
								}
							}
						}
						
						$return .= '<li><a href="http://viaf.org/viaf/'.$reference_id.'/" target="_new">View all</a></li>
					</ul>
				</div>
			</div>';
					
		} else if ($this->view == 'image_only') {
			
			$return .= '<img class="enlarge" src="'.$image_url.'" />';
		} else if ($this->view == 'image_lazy') {
			
			$return .= '<img class="enlarge" data-original="'.htmlspecialchars($image_url).'" src="/css/images/blank.png" />';
		}
				

		return $return;
	}
	
	public function getURL() {
		
		if ($this->arr_resource['protocol'] == 'static') {
			$url = $this->arr_resource['url'].$this->identifier.$this->arr_resource['url_options'];
		} else {
			$url = $this->identifier;
		}
		
		if ($this->view == 'plain') {
			$return = '<a href="'.$url.'" target="_new">'.$url.'</a>';
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
    
    private static function arrValueByArr($arr, $arr_path) {
		
		$nr_group = 0;
		$arr_return = [];
		
		$func_walk = function($arr, $arr_path, $group = false) use (&$arr_return, &$func_walk, &$nr_group) {
			
			if (!$arr) {
				
				if ($group && !$arr_return[$group]) {
					$arr_return[$group] = '';
				}
				return;
			}
			
			$path_key = key($arr_path);
			$path_value = current($arr_path);
			
			if (is_array($path_value)) {
				
				if ($path_key === '[]') {

					foreach ($arr as $key => $value) {
						
						if (!$group) { // Start grouping for each row
							$nr_group++;
							$use_group = $nr_group;
						} else {
							$use_group = $group;
						}
						
						$func_walk($value, $path_value, $use_group);
					}
				} else {
					
					$func_walk($arr[$path_key], $path_value, $group);
				}
			} else {
				
				$s_arr =& $arr_return[$group];
				$value = $arr[$path_key];

				if ($s_arr) {
					
					if (!is_array($s_arr)) {
						
						$arr_return[$group] = [$s_arr => $s_arr];
						$s_arr =& $arr_return[$group];
					}
					
					$s_arr[$value] = $value;
				} else {
					
					$s_arr = $value;
				}
			}
		};
		
		$func_walk($arr, $arr_path);
		
		return $arr_return;
	}
}
