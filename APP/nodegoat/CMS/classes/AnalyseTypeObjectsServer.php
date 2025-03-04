<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class AnalyseTypeObjectsServer extends AnalyseTypeObjects {
	
	protected $str_host = false;
	
	protected $timeout_request = (60 * 10); // Seconds
	
	/*
	protected function openInputResource() {
			
		$this->path = getPathTemporary('nodegoat');
		chmod($this->path, 0666); // Allow other processes to open the file
		
		$this->resource = fopen($this->path, 'w');
	}*/
	
	public function setHost($str_host) {
		
		$this->str_host = $str_host;
	}
	
	public function setTimeout($time) {
		
		$this->timeout_request = $time;
	}
	
	protected function getProcess($do, $arr_options) {
		
		$str_options = '';
		
		foreach ($arr_options as $key => $value) {
			
			$str_options .= '/'.$key.'/'.$value;
		}
		
		$command = 'curl --no-buffer --silent --show-error -X POST "'.$this->str_host.$do.($str_options ?: '/').'" -H  "accept: application/json" -H  "Content-Type: application/json" -H  "Authorization: bearer '.Settings::get('graph_database', 'token').'" --data-binary @-';
		
		$process = new ProcessProgram($command);
		
		return $process;
	}
		
	protected function doAnalysis($name, $arr) {
		
		$arr['data'] = read($this->resource);
		$str_data = value2JSON($arr);
		
		$arr = null;
		$this->closeInputResource();
				
		$process = $this->getProcess($name, ['timeout' => $this->timeout_request]);
		
		$process->writeInput($str_data);
		$process->closeInput();
		
		while (true) {

			$process->checkOutput(true, true);
			
			$str_error = $process->getError();
			$str_result = $process->getOutput();
			
			if ($str_error !== '') {
				
				$process->close();
				
				error($str_error, TROUBLE_ERROR, LOG_BOTH, $str_result);
			}
			
			if ($str_result) {
				
				$str_separator = PHP_EOL;
				$str_line = strtok($str_result, $str_separator);

				while ($str_line !== false) {
					
					$arr_result = json_decode($str_line, true);
				
					if ($arr_result['statistics']) {
					
						$this->graphStatistics($arr_result['statistics']);
						
						$arr_result = false;
					}
					
					$str_line = strtok($str_separator);
				}
			}
			
			if (!$process->isRunning(false)) {
				
				$process->close();
				
				break;
			}
		}
		
		if (!$arr_result) {
			return false;
		}
		
		$this->arr_store = [];
		
		foreach ($arr_result as $key => $arr_number) {
			
			$arr_key = explode('-', $key);
			
			if ($arr_key[0] != $this->type_id) {
				continue;
			}
			
			$this->arr_store[$arr_key[1]] = $arr_number;
		}
		
		return true;
	}

	protected function runBetweennessCentrality() {
		
		$arr_post = [
			'settings' => [
				'mode' => $this->arr_analyse['settings']['mode'],
				'graph' => [
					'nodes' => $this->num_nodes,
					'edges' => $this->num_edges
				],
				'weighted' => [
					'mode' => $this->arr_analyse['settings']['weighted']['mode'],
					'max' => (int)$this->arr_analyse['settings']['weighted']['max']
				]
			]
		];
		
		return $this->doAnalysis('run_betweenness_centrality', $arr_post);
	}
	
	protected function runClosenessCentrality() {
		
		$arr_post = [
			'settings' => [
				'mode' => $this->arr_analyse['settings']['mode'],
				'graph' => [
					'nodes' => $this->num_nodes,
					'edges' => $this->num_edges
				],
				'weighted' => [
					'mode' => $this->arr_analyse['settings']['weighted']['mode'],
					'max' => (int)$this->arr_analyse['settings']['weighted']['max']
				]
			]
		];
		
		return $this->doAnalysis('run_closeness/centrality', $arr_post);
	}
	
	protected function runClosenessEccentricity() {
		
		$arr_post = [
			'settings' => [
				'mode' => $this->arr_analyse['settings']['mode'],
				'graph' => [
					'nodes' => $this->num_nodes,
					'edges' => $this->num_edges
				],
				'weighted' => [
					'mode' => $this->arr_analyse['settings']['weighted']['mode'],
					'max' => (int)$this->arr_analyse['settings']['weighted']['max']
				]
			]
		];
		
		return $this->doAnalysis('run_closeness/eccentricity', $arr_post);
	}
	
	protected function runClusteringCoefficient() {
		
		$arr_post = [
			'settings' => [
				'graph' => [
					'nodes' => $this->num_nodes,
					'edges' => $this->num_edges
				]
			]
		];
		
		return $this->doAnalysis('run_clustering_coefficient', $arr_post);
	}
	
	protected function runPageRank() {
		
		$arr_post = [
			'settings' => [
				'iterations' => $this->arr_analyse['settings']['iterations'],
				'damping' => $this->arr_analyse['settings']['damping'],
				'graph' => [
					'nodes' => $this->num_nodes,
					'edges' => $this->num_edges
				]
			]
		];
		
		return $this->doAnalysis('run_pagerank', $arr_post);
	}
	
	protected function runShortestPath() {
		
		$arr_post = [
			'settings' => [
				'nodes' => [
					'start' => $this->arr_nodes_start,
					'end' => $this->arr_nodes_end,
				],
				'betweenness_centrality_mode' => $this->arr_analyse['settings']['betweenness_centrality_mode'],
				'graph' => [
					'nodes' => $this->num_nodes,
					'edges' => $this->num_edges
				],
				'weighted' => [
					'mode' => $this->arr_analyse['settings']['weighted']['mode'],
					'max' => (int)$this->arr_analyse['settings']['weighted']['max']
				]
			]
		];
		
		return $this->doAnalysis('run_shortest_path', $arr_post);
	}
}
