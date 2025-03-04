<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class AnalyseTypeObjects {
	
	protected $type_id = false;
	protected $arr_analyse = false;
	protected $arr_algorithm = false;
	
	protected $is_external = false; // Target for external usage
	protected $resource = false;
	
	protected $num_nodes = false;
	protected $num_edges = false;
	
	protected $arr_scope = false;
	protected $arr_limit_filters = false;
	protected $arr_nodes_start = false;
	protected $arr_nodes_end = false;
	
	protected $arr_store = false;
	
	protected static $num_objects_collect = 100000;
	
    public function __construct($type_id, $arr_analyse, $is_external = false) {
		
		$this->type_id = $type_id;
		$this->arr_analyse = $arr_analyse;
		
		$this->arr_algorithm = static::getAlgorithms($arr_analyse['algorithm']);
		
		$this->is_external = (bool)$is_external;
		
		$this->openInputResource();
    }
    
	protected function openInputResource() {
		
		$this->resource = getStreamMemory(false);
	}
	
	protected function closeInputResource() {
		
		fclose($this->resource);
	}

    public function input($collect, $arr_filters) {
		
		status('Collecting data.', 'ANALYSIS', false, ['persist' => true]);

		$arr_header = ['key', 'from', 'to', 'weight', 'time'];
		
		fputcsv($this->resource, $arr_header);
		
		$collect->setInitLimit(static::$num_objects_collect);
		
		$this->num_nodes = 0;
		$this->num_edges = 0;
		
		$do_weighted = ($this->arr_algorithm['weighted'] && $this->arr_analyse['settings']['weighted']['mode'] != 'unweighted');
		
		while ($collect->init($arr_filters)) {
			
			$arr_objects = $collect->getPathObjects('0');
			
			Mediator::checkState();
		
			foreach ($arr_objects as $object_id => $arr_object) {
				
				$this->num_nodes++;
			
				$collect->getWalkedObject($object_id, [], function &($cur_target_object_id, $cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) use ($object_id, $do_weighted) {
					
					$cur_arr[$cur_path] = [$cur_target_type_id.'-'.$cur_target_object_id, null, null];
					
					if ($do_weighted) {
						
						$arr_object = $collect->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id'], true);
						
						$num_weight = ($arr_object['object']['object_style']['weight'] ?? null);
						
						if ($num_weight !== null && is_array($num_weight)) {
							$num_weight = array_sum($num_weight);
						}
						
						if (isset($arr_info['object_sub_id'])) {
							
							if ($arr_info['in_out'] == 'in') {
								
								$arr_object_source = $arr_object;
							} else {
								
								if (strpos($source_path, '-') === false) {
									$arr_object_source = $collect->getPathObject('0', 'start', $arr_info['object_id'], $cur_target_object_id, true);
								} else {
									$arr_object_source = $collect->getPathObject($source_path, 'in', $arr_info['object_id'], $cur_target_object_id, true);
								}
							}
							
							$num_weight_sub = ($arr_object_source['object_subs'][$arr_info['object_sub_id']]['object_sub']['object_sub_style']['weight'] ?? null);
							
							if ($num_weight_sub !== null) {
								
								if (is_array($num_weight_sub)) {
									$num_weight_sub = array_sum($num_weight_sub);
								}
								$num_weight = ($num_weight ?? 0) + $num_weight_sub;
							}
						}
						
						$cur_arr[$cur_path][1] = $num_weight;
					}

					if (!$arr_info['arr_collapse_source'] && isset($arr_info['date'])) { // Path end, use time

						$s_arr = &$cur_arr[$cur_path][2];
						$s_arr = '';
							
						foreach ($arr_info['date'] as $arr_date) {
							if ($this->is_external) {
								$s_arr .= ($s_arr !== '' ? ' ' : '').FormatTypeObjects::dateInt2DateStandard($arr_date['start']).','.FormatTypeObjects::dateInt2DateStandard($arr_date['end']);
							} else {
								$s_arr .= ($s_arr !== '' ? ' ' : '').$arr_date['start'].','.$arr_date['end'];
							}
						}
						unset($s_arr);
					}
					
					$do_collapse = ($arr_info['in_out'] == 'start' || $arr_info['arr_collapse_source'] ? true : false); // Path start or not path end

					if ($do_collapse) {
						return $cur_arr;
					}
					
					$this->num_edges++;
											
					$str_path = '';
					$num_weight = null;
					$str_time = null;

					foreach ($cur_arr as $arr_path) {
						
						$str_path .= ($str_path === '' ? '' : '_').$arr_path[0];
						
						if ($arr_path[1] !== null) {
							$num_weight = (($num_weight ?? 0) + $arr_path[1]);
						}
						if ($arr_path[2] !== null) {
							$str_time = $arr_path[2];
						}
					}
					
					if ($num_weight !== null) {
						$num_weight = (int)$num_weight;
					} else {
						$num_weight = 1;
					}
									
					if ($num_weight !== 0) {
						
						$arr_row = [$str_path, $this->type_id.'-'.$object_id, $cur_target_type_id.'-'.$cur_target_object_id, $num_weight, $str_time];
						
						fputcsv($this->resource, $arr_row);
					}

					return $cur_arr;
				});
			}
		}
		
		rewind($this->resource);
		
		$this->arr_scope = $collect->getScope();
		
		$this->arr_limit_filters = []; // Collect the possible active limiting filters for other queries in the analysis
		
		$arr_limit_filters = $collect->getLimitTypeFilters($this->type_id);
		
		foreach ((array)$arr_limit_filters as $arr_limit_type_filter) {
						
			$this->arr_limit_filters[] = $arr_limit_type_filter['filter'];
		}
		
		status('<strong>Statistics Data</strong>'.PHP_EOL
			.'<strong>'.num2String($this->num_nodes).'</strong> nodes'.PHP_EOL
			.'<strong>'.num2String($this->num_edges).'</strong> edges',
		'ANALYSIS', false, ['persist' => true]);
		
		return $this->resource;
	}
	
	public function run() {
		
		status('Running algorithm <strong>'.$this->arr_algorithm['name'].'</strong>.', 'ANALYSIS', false, ['persist' => true]);
		
		$function = $this->arr_algorithm['function'];
		
		$this->$function();
	}
	
	protected function graphStatistics($arr) {
		
		if (!$arr['nodes']) {
			return false;
		}
		
		status('<strong>Statistics Graph</strong>'.PHP_EOL
			.'<strong>'.num2String($arr['nodes']).'</strong> nodes connected'.PHP_EOL
			.'<strong>'.num2String($arr['edges']).'</strong> edges resolved'.PHP_EOL
			.($arr['weighted']['mode'] != 'unweighted' ?
				'<strong>'.$arr['weighted']['min'].' - '.$arr['weighted']['max'].'</strong> '.($arr['weighted']['mode'] == 'closeness' ? getLabel('lbl_analysis_weighted_closeness') : getLabel('lbl_analysis_weighted_distance'))
					:
				'<strong>'.getLabel('lbl_analysis_unweighted').'</strong> graph'
			).PHP_EOL
			.'<strong>'.($arr['density'] == 'dense' ? getLabel('lbl_analysis_graph_dense') : getLabel('lbl_analysis_graph_sparse')).'</strong> graph',
		'ANALYSIS', false, ['persist' => true]);
	}

	public function store() {
		
		$user_id = ((int)$this->arr_analyse['user_id'] ?: 0);
		$analysis_id = (int)$this->arr_analyse['id'];
		
		$storage = new StoreTypeObjectsExtensions($this->type_id, false, $_SESSION['USER_ID']);
		
		DB::startTransaction('analyse_type_objects');
		
		$storage->resetTypeObjectAnalysis($user_id, $analysis_id);
		
		if (!$this->arr_store) {
			
			DB::commitTransaction('analyse_type_objects');

			return false;
		}
		
		status('Storing <strong>'.num2String(count($this->arr_store)).'</strong> results.', 'ANALYSIS', false, ['persist' => true]);
		
		foreach ($this->arr_store as $object_id => $arr_value) {
			
			$storage->setObjectID($object_id, false);
			
			$storage->addTypeObjectAnalysis($user_id, $analysis_id, $arr_value[0], $arr_value[1]);
		}
		
		$this->arr_store = [];
		
		$storage->save();
		
		$storage->updateTypeObjectAnalysis($user_id, $analysis_id);
		
		DB::commitTransaction('analyse_type_objects');
		
		return true;
	}
	
	protected function runDegreeCentrality() {
		
		$mode_weighted = $this->arr_analyse['settings']['weighted']['mode'];
		$num_weight_limit_max = (int)$this->arr_analyse['settings']['weighted']['max'];
		
		$this->arr_store = [];
		
		$arr_row = fgetcsv($this->resource, 0, ','); // Heading
		
		if ($mode_weighted == 'unweighted') {
			
			$arr_check = [];
			
			while (($arr_row = fgetcsv($this->resource, 0, ',')) !== false) {
				
				$str_identifier = $arr_row[1].'_'.$arr_row[2];
				
				if ($arr_check[$str_identifier]) {
					continue;
				}
					
				$arr_check[$str_identifier] = true;
				
				$object_id_from = explode('-', $arr_row[1]);
				$object_id_from = $object_id_from[1];
				
				$s_arr = &$this->arr_store[$object_id_from][0];
				
				if ($s_arr === null) {
					$s_arr = 0;
				}
			
				$s_arr++;
			}
		} else {
			
			$num_weight_max = 0;
			
			while (($arr_row = fgetcsv($this->resource, 0, ',')) !== false) {
				
				$object_id_from = explode('-', $arr_row[1]);
				$object_id_from = $object_id_from[1];
				
				$s_arr = &$this->arr_store[$object_id_from][0];
				
				if ($s_arr === null) {
					$s_arr = 0;
				}
			
				$s_arr += (int)$arr_row[3]; // Weight
				
				if ($s_arr > $num_weight_max) {
					$num_weight_max = $s_arr;
				}
			}
					
			if ($num_weight_limit_max) {
				$num_weight_max = $num_weight_limit_max;
			}
			
			foreach ($this->arr_store as $object_id => &$arr_value) {
				
				$s_arr = &$arr_value[0];
			
				if ($s_arr > $num_weight_max) {
					$s_arr = $num_weight_max;
				}
				
				if ($mode_weighted == 'closeness') { // Reverse weight based on maximum weight

					$s_arr = 1 + ($num_weight_max - $s_arr);
				}
			}
		}
	}
	
	protected function runBetweennessCentrality() {}
	
	protected function runClosenessCentrality() {}
	
	protected function runClusteringCoefficient() {}
	
	protected function runPageRank() {}

	protected function prepareShortestPath() {
		
		$arr_filter = FilterTypeObjects::convertFilterInput($this->arr_analyse['settings']['filter_start']);

		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);
		$filter->setScope($this->arr_scope);
		$filter->setFilter($arr_filter);
		
		if ($this->arr_limit_filters) {
			$filter->setFilter($this->arr_limit_filters);
		}
		
		$arr_objects = $filter->init();
		
		if (!$arr_objects) {
			return false;
		}
		
		$this->arr_nodes_start = [];
		
		foreach ($arr_objects as $object_id => $arr_object) {
			
			$this->arr_nodes_start[] = $this->type_id.'-'.$object_id;
		}
		
		if ($this->arr_analyse['settings']['filter_end']) {
			
			$arr_filter = FilterTypeObjects::convertFilterInput($this->arr_analyse['settings']['filter_end']);

			$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);
			$filter->setScope($this->arr_scope);
			$filter->setFilter($arr_filter);
			
			if ($this->arr_limit_filters) {
				$filter->setFilter($this->arr_limit_filters);
			}
			
			$arr_objects = $filter->init();
			
			$this->arr_nodes_end = [];
			
			foreach ($arr_objects as $object_id => $arr_object) {
				
				$this->arr_nodes_end[] = $this->type_id.'-'.$object_id;
			}
		}
		
		$this->runShortestPath();
	}
	
	public static function getAlgorithms($algorithm = false) {
		
		$arr = [
			'degree_centrality' => [
				'id' => 'degree_centrality',
				'name' => getLabel('lbl_analysis_degree_centrality'),
				'options' => function($type_id, $form_name, $arr_options = []) {

					return false;
				},
				'parse' => function($arr_settings) {
					
					return $arr_settings;
				},
				'function' => 'runDegreeCentrality',
				'graph' => false,
				'weighted' => true
			],
			'shortest_path' => [
				'id' => 'shortest_path',
				'name' => getLabel('lbl_analysis_shortest_path'),
				'options' => function($type_id, $form_name, $arr_options = []) {
				
					Labels::setVariable('application', 'Shortest Path');
					
					$arr_modes = [
						['id' => '', 'name' => getLabel('lbl_no')],
						['id' => 'absolute', 'name' => getLabel('lbl_absolute')],
						['id' => 'relative', 'name' => getLabel('lbl_relative')],
						['id' => 'normalised', 'name' => getLabel('lbl_normalised')]
					];
					
					$arr_html = [
						getLabel('lbl_from') => '<div>'
							.'<input type="hidden" name="'.$form_name.'[filter_start]" value="'.strEscapeHTML(value2JSON($arr_options['filter_start'])).'" />'
							.'<button type="button" id="y:data_filter:configure_application_filter-'.$type_id.'" value="filter" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>'
							.'<label>'.getLabel('lbl_required').'</label>'
						.'</div>',
						getLabel('lbl_target') => '<div>'
							.'<input type="hidden" name="'.$form_name.'[filter_end]" value="'.strEscapeHTML(value2JSON($arr_options['filter_end'])).'" />'
							.'<button type="button" id="y:data_filter:configure_application_filter-'.$type_id.'" value="filter" title="'.getLabel('inf_application_filter').'" class="data edit popup"><span>filter</span></button>'
							.'<label>'.getLabel('lbl_optional').'</label>'
						.'</div>',
						getLabel('lbl_analysis_centrality') => '<div title="'.getLabel('lbl_analysis_shortest_path_betweenness_centrality').'">
							'.cms_general::createSelectorRadio($arr_modes, $form_name.'[betweenness_centrality_mode]', $arr_options['betweenness_centrality_mode']).'
						</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['filter_start'] = (!is_array($arr_settings['filter_start']) ? json_decode($arr_settings['filter_start'], true) : $arr_settings['filter_start']);
								
					if (!$arr_settings_parsed['filter_start']) { // Shortest path requires settings
						return false;
					}
					
					$arr_settings_parsed['filter_end'] = (!is_array($arr_settings['filter_end']) ? json_decode($arr_settings['filter_end'], true) : $arr_settings['filter_end']);
					
					$arr_settings_parsed['betweenness_centrality_mode'] = $arr_settings['betweenness_centrality_mode'];

					return $arr_settings_parsed;
				},
				'function' => 'prepareShortestPath',
				'graph' => true,
				'weighted' => true
			],
			'betweenness_centrality' => [
				'id' => 'betweenness_centrality',
				'name' => getLabel('lbl_analysis_betweenness_centrality'),
				'options' => function($type_id, $form_name, $arr_options = []) {

					$arr_modes = [
						['id' => 'absolute', 'name' => getLabel('lbl_absolute')],
						['id' => 'relative', 'name' => getLabel('lbl_relative')],
						['id' => 'normalised', 'name' => getLabel('lbl_normalised')]
					];
					
					$arr_html = [
						getLabel('lbl_mode') => '<div>'.cms_general::createSelectorRadio($arr_modes, $form_name.'[mode]', ($arr_options['mode'] ?: 'absolute')).'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['mode'] = ($arr_settings['mode'] ?: 'absolute');

					return $arr_settings_parsed;
				},
				'function' => 'runBetweennessCentrality',
				'graph' => true,
				'weighted' => true
			],
			'closeness_centrality' => [
				'id' => 'closeness_centrality',
				'name' => getLabel('lbl_analysis_closeness_centrality'),
				'options' => function($type_id, $form_name, $arr_options = []) {

					$arr_modes = [
						['id' => 'normalised', 'name' => getLabel('lbl_normalised')]
					];
					
					$arr_html = [
						getLabel('lbl_mode') => '<div>'.cms_general::createSelectorRadio($arr_modes, $form_name.'[mode]', ($arr_options['mode'] ?: 'normalised')).'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['mode'] = ($arr_settings['mode'] ?: 'relative');

					return $arr_settings_parsed;
				},
				'function' => 'runClosenessCentrality',
				'graph' => true,
				'weighted' => true
			],
			'closeness_eccentricity' => [
				'id' => 'closeness_eccentricity',
				'name' => getLabel('lbl_analysis_closeness_eccentricity'),
				'options' => function($type_id, $form_name, $arr_options = []) {

					$arr_modes = [
						['id' => 'absolute', 'name' => getLabel('lbl_absolute')]
					];
					
					$arr_html = [
						getLabel('lbl_mode') => '<div>'.cms_general::createSelectorRadio($arr_modes, $form_name.'[mode]', ($arr_options['mode'] ?: 'absolute')).'</div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$arr_settings_parsed['mode'] = ($arr_settings['mode'] ?: 'relative');

					return $arr_settings_parsed;
				},
				'function' => 'runClosenessEccentricity',
				'graph' => true,
				'weighted' => true
			],
			'clustering_coefficient' => [
				'id' => 'clustering_coefficient',
				'name' => getLabel('lbl_analysis_clustering_coefficient'),
				'options' => function($type_id, $form_name, $arr_options = []) {
					
					return false;
				},
				'parse' => function($arr_settings) {

					return $arr_settings;
				},
				'function' => 'runClusteringCoefficient',
				'graph' => true,
				'weighted' => false
			],
			'pagerank' => [
				'id' => 'pagerank',
				'name' => getLabel('lbl_analysis_pagerank'),
				'options' => function($type_id, $form_name, $arr_options = []) {

					$arr_html = [
						getLabel('lbl_analysis_iterations') => '<div><input name="'.$form_name.'[iterations]" type="number" step="1" min="1" max="50" value="'.($arr_options['iterations'] ?: 28).'" /></div>',
						getLabel('lbl_analysis_damping') => '<div><input name="'.$form_name.'[damping]" type="number" step="0.01" min="0.01" max="1" value="'.($arr_options['damping'] ?: 0.85).'" /></div>'
					];
					
					return $arr_html;
				},
				'parse' => function($arr_settings) {
					
					$arr_settings_parsed = [];
					
					$num_iterations = (int)$arr_settings['iterations'];
					
					if (!($num_iterations >= 1 && $num_iterations <= 50)) {
						$num_iterations = 28;
					}
					
					$arr_settings_parsed['iterations'] = $num_iterations;
					
					$num_damping = (float)$arr_settings['damping'];
					
					if (!($num_damping >= 0.01 && $num_damping <= 1)) {
						$num_damping = 0.85;
					}
					
					$arr_settings_parsed['damping'] = $num_damping;

					return $arr_settings_parsed;
				},
				'function' => 'runPageRank',
				'graph' => true,
				'weighted' => false
			]
		];
		
		return ($algorithm ? $arr[$algorithm] : $arr);
	}
}
