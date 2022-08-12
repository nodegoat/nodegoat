<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_api extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	private static $arr_output_modes_data = [
		'raw' => 'raw',
		'default' => 'default'
	];
	private static $arr_output_modes_data_model = [
		'template' => 'template',
		'default' => 'default'
	];
	
	private static $nr_objects_stream = 5000;
	private static $nr_objects_scope_stream = 1000;
	private static $nr_store_objects_buffer = 1000;
	
	private $arr_settings = [];
	private $arr_request_vars = [];
	private $is_user = false;
	private $is_administrator = false;
	private $versioning = true;
	
	private $type_id;
	private $arr_client_update_objects;
	private $arr_client_add_objects;
	private $count_objects_updated;
	private $count_objects_added;
		
	public function api() {

		$arr_request_vars = SiteStartVars::getModVariables(0);
		
		if ($arr_request_vars && !end($arr_request_vars)) { // Remove the last empty request variable to allow for a final '/'
			unset($arr_request_vars[key($arr_request_vars)]);
		}
		
		$num_request_vars = count($arr_request_vars);
				
		if ($num_request_vars == 1 && $arr_request_vars[0] != 'reconcile') {
						
			$this->apiIdentifier($arr_request_vars[0]);
			return;
		} else if ($num_request_vars == 3 && $arr_request_vars[0] == 'project' && ($arr_request_vars[2] != 'data' && $arr_request_vars[2] != 'model' && $arr_request_vars[2] != 'analysis' && $arr_request_vars[2] != 'reconcile')) {
						
			$this->apiIdentifier($arr_request_vars[2]);
			return;
		} else if (!$num_request_vars && $_REQUEST['id']) {
						
			$this->apiIdentifier($_REQUEST['id']);
			return;
		} else if (!$num_request_vars) {
			
			return;
		}
		
		$setting = false;
		
		foreach ($arr_request_vars as $value) {
			
			if ($value == 'data' || $value == 'model' || $value == 'analysis' || $value == 'reconcile') {
				
				$this->arr_settings['mode'] = $value;
			} else if ($value == 'type' || $value == 'scope' || $value == 'filter' || $value == 'object' || $value == 'use') {
				
				$setting = $value;
				$this->arr_settings[$setting] = false;
			} else if ($setting) {
				
				if ($setting == 'object') {
					
					$value = explode(',', $value);
					
					foreach ($value as &$object_id) {
						$object_id = GenerateTypeObjects::parseTypeObjectID($object_id);
					}
					unset($object_id);
				}
				
				$this->arr_settings[$setting] = $value;
			}
		}
		
		$this->arr_request_vars = $arr_request_vars;
		
		$this->is_user = ($_SESSION['USER_ID'] && $_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_UNDER_REVIEW);
		$this->is_administrator = ($_SESSION['USER_ID'] && $_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN);
				
		if ($this->arr_settings['mode'] == 'data') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				$this->apiDataStore();
			} else {
				$this->apiData();
			}
		} else if ($this->arr_settings['mode'] == 'model') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				$this->apiDataModelStore();
			} else {
				$this->apiDataModel();
			}
		} else if ($this->arr_settings['mode'] == 'analysis') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				$this->apiDataAnalysisStore();
			} else {
				$this->apiDataAnalysis();
			}
		} else if ($this->arr_settings['mode'] == 'reconcile') {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				// Not possible
			} else {
				$this->apiDataReconcile();
			}
		} else {
			
			$this->errorInput('No mode specified');
		}
	}
	
	private function apiIdentifier($identifier) {
		
		$arr_id = GenerateTypeObjects::decodeTypeObjectID($identifier);
		
		if ($arr_id) { // nodegoat ID
			
			$type_id = $arr_id['type_id'];
			$arr_object_ids[$arr_id['object_id']] = $arr_id['object_id'];
		} else {
			
			$project_id = $_SESSION['custom_projects']['project_id'];
			$arr_project = StoreCustomProject::getProjects($project_id);
			
			$arr_type_ids = array_keys($arr_project['types']);
			
			if (!$arr_type_ids) {

				return false;
			}
			
			$arr_type_object_descriptions = StoreType::getTypesObjectIdentifierDescriptions($arr_type_ids);
			
			$arr_type_objects = [];
			
			if ($arr_type_object_descriptions) {

				$arr_type_objects = FilterTypeObjects::getTypesObjectsByObjectDescriptions($identifier, $arr_type_object_descriptions);
			}
			
			if (!$arr_type_objects) {
				
				$arr_type_objects = FilterTypeObjects::getObjectsTypeObjects($identifier);
			}
			
			if (!$arr_type_objects) {

				return false;
			}

			if (count($arr_type_objects) > 1) { // Result contains multiple Types
				
				// Get result for the first sorted Type
				foreach ($arr_type_ids as $type_id) {
					
					if ($arr_type_objects[$type_id]) {
						
						$type_id = $type_id;
						$arr_object_ids = $arr_type_objects[$type_id];
						break;
					}
				}
			} else {
				
				$type_id = key($arr_type_objects);
				$arr_object_ids = current($arr_type_objects);
			}
			
			if (!$arr_object_ids) {
				
				return false;
			}
		}
		
		$arr_api_configuration = cms_nodegoat_api::getConfiguration(SiteStartVars::$api['id']);
		$url = $arr_api_configuration['projects'][$_SESSION['custom_projects']['project_id']]['identifier_url'];
		$url = ($url && !SiteStartVars::getRequestOutputFormat(['application/json', 'application/ld+json']) ? $url : false);
		
		if ($url) {
			
			$func_parse_type_object = function($str, $object_id) use ($type_id) {
				
				$str = str_replace('[[type]]', $type_id, $str);
				$str = str_replace('[[object]]', $object_id, $str);
				
				return $str;
			};
			
			if (strpos($url, '[/multi]') === false) {
				
				$object_id = current($arr_object_ids);
				
				$url = $func_parse_type_object($url, $object_id);
			} else {
				
				$url = preg_replace_callback(
					'/\[multi(?:=(.+?))?\](.+?)\[\/multi\]/i',
					function($arr_matches) use ($arr_object_ids, $func_parse_type_object) {
						
						$arr_str = [];
						
						foreach ($arr_object_ids as $object_id) {
							
							$arr_str[] = $func_parse_type_object($arr_matches[2], $object_id);
						}
						
						return implode($arr_matches[1], $arr_str);
					},
					$url
				);
			}
			
			$url = Labels::parseTextVariables($url);
			
			Response::location($url);
		} else {
		
			$this->arr_settings['mode'] = 'data';
			$this->arr_settings['type'] = $type_id;
			$this->arr_settings['object'] = $arr_object_ids;
			
			$this->apiData();
		}
	}
	
	// Get Data
		
	private function apiData() {
		
		if (!$this->arr_settings['type'] || !isset($this->arr_settings['object'])) {
			$this->errorInput('No Type/Object specified');
		}
		
		$project_id = $_SESSION['custom_projects']['project_id'];
		$arr_project = StoreCustomProject::getProjects($project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$arr_scope = [];
		
		if ($_REQUEST['scope'] && !is_numeric($_REQUEST['scope'])) { // Scope form

			$arr_scope = JSON2Value($_REQUEST['scope']);
			
			$arr_scope = data_model::parseTypeNetwork($arr_scope);
		} else if ($_REQUEST['scope'] || (int)$this->arr_settings['scope']) { // Scope ID
				
			$scope_id = (int)($_REQUEST['scope'] ?: $this->arr_settings['scope']);
			
			$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($project_id, $_SESSION['USER_ID'], $type_id, $scope_id, $arr_use_project_ids);
			$arr_scope = $arr_scope['object'];
			
			$arr_scope = data_model::parseTypeNetwork($arr_scope);
		}
		
		$_SESSION['custom_projects'][toolbar::getActionSpace()]['condition_id'] = false;
		
		if ((int)$this->arr_settings['condition']) {
			
			$_SESSION['custom_projects'][toolbar::getActionSpace()]['condition_id'] = (int)$this->arr_settings['condition'];
		}

		$arr_filters = $this->getRequestTypeFilters();
	
		$arr_limit = [];
		$arr_order = [];
		
		if ($_REQUEST['limit'] || $_REQUEST['offset']) {
			
			$arr_limit = [(int)$_REQUEST['offset'], (int)$_REQUEST['limit']];
		}
		if ($_REQUEST['order']) {
			
			$arr_order = JSON2Value($_REQUEST['order']);
			
			if (!is_array($arr_order)) {
				
				$arr_order = explode(':', $_REQUEST['order']);
				$arr_order = [$arr_order[0] => (strtoupper(($arr_order[1] ?? '')) == 'DESC' ? 'DESC' : 'ASC')];
			}
		}
		
		$output_mode = (self::$arr_output_modes_data[$_REQUEST['output']] ?: 'default');
		
		$arr_ref_type_ids = StoreCustomProject::getScopeTypes($project_id);

		if ($arr_scope['paths']) {
						
			$trace = new TraceTypesNetwork(array_keys($arr_project['types']), true, true);
			$trace->filterTypesNetwork($arr_scope['paths']);
			$trace->run($type_id, false, 3);
			$arr_type_network_paths = $trace->getTypeNetworkPaths(true);
		} else {
			$arr_type_network_paths = ['start' => [$type_id => ['path' => [0]]]];
		}
		
		$collect = new CollectTypesObjects($arr_type_network_paths, ($output_mode == 'raw' ? GenerateTypeObjects::VIEW_STORAGE : GenerateTypeObjects::VIEW_SET));
		$collect->setScope(['users' => $_SESSION['USER_ID'], 'types' => $arr_ref_type_ids, 'project_id' => $project_id]);
		
		if ($output_mode != 'raw') {
			
			$collect->setConditions(GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE, function($type_id) {
				return toolbar::getTypeConditions($type_id);
			});
		}
		
		$collect->init($arr_filters, false);
			
		$arr_collect_info = $collect->getResultInfo();
		
		$arr_type_sets = [];
		
		foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
			
			if (!$arr_type_sets[$cur_type_id]) {
				$arr_type_sets[$cur_type_id] = StoreType::getTypeSet($cur_type_id);
			}
			
			$arr_type_set = $arr_type_sets[$cur_type_id];
			
			if ($arr_project['types'][$cur_type_id]['type_filter_id']) {
				
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
				$collect->addLimitTypeFilters($cur_type_id, FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project['types'][$cur_type_id]['type_filter_object_subs']);
			}
			
			foreach ($arr_paths as $path) {
				
				$source_path = $path;
				if ($source_path) { // path includes the target type id, remove it
					$source_path = explode('-', $source_path);
					array_pop($source_path);
					$source_path = implode('-', $source_path);
				}
				
				$arr_settings = $arr_scope['types'][$source_path][$cur_type_id];
				
				$arr_filtering = [];
				if ($arr_settings['filter']) {
					$arr_filtering = ['all' => true];
				}
				
				$collapse = $arr_settings['collapse'];
				$arr_in_selection = ($arr_settings['selection'] ?: []);
				
				$arr_selection = ['object' => true, 'object_descriptions' => [], 'object_sub_details' => []];
				
				if ($arr_in_selection || $arr_settings['object_only']) {
					
					foreach ($arr_in_selection as $id => $arr_selected) {
						
						$object_description_id = $arr_selected['object_description_id'];
						
						if ($object_description_id) {
							
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_descriptions'][$object_description_id]['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, $object_description_id)) {
								continue;
							}
							
							$s_arr =& $arr_selection['object_descriptions'][$object_description_id];
							$s_arr['object_description_id'] = true;
							
							if ($arr_selected['object_description_value']) {
								$s_arr['object_description_value'] = true;
							}
							if ($arr_selected['object_description_reference']) {
								$s_arr['object_description_reference'] = $arr_selected['object_description_reference'];
								if ($arr_selected['object_description_reference_value']) {
									$s_arr['object_description_reference_value'] = $arr_selected['object_description_reference_value'];
								}
							}
						}
						
						$object_sub_details_id = $arr_selected['object_sub_details_id'];
						
						if ($object_sub_details_id) {

							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
								continue;
							}
							
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['all' => true];
							
							$object_sub_description_id = $arr_selected['object_sub_description_id'];
							
							if ($object_sub_description_id) {

								if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
									continue;
								}
								
								$s_arr =& $arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
								$s_arr['object_sub_description_id'] = true;
								
								if ($arr_selected['object_sub_description_value']) {
									$s_arr['object_sub_description_value'] = true;
								}
								if ($arr_selected['object_sub_description_reference']) {
									$s_arr['object_sub_description_reference'] = $arr_selected['object_sub_description_reference'];
									if ($arr_selected['object_sub_description_reference_value']) {
										$s_arr['object_sub_description_reference_value'] = $arr_selected['object_sub_description_reference_value'];
									}
								}
							} else if (!$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions']) { // Set empty selection on sub object descriptions if there are none selected
								
								$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = [];
							}
						}
					}
					unset($s_arr);
				} else { // Nothing selected, use default

					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, $object_description_id)) {
							continue;
						}
						
						$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
					}
								
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id)) {
							continue;
						}

						$arr_selection['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => true, 'object_sub_descriptions' => []];

						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
													
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
								
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
						}
					}
				}
				
				$arr_options = [
					'arr_selection' => $arr_selection,
					'arr_filtering' => $arr_filtering,
					'collapse' => $collapse
				];
				
				if ($path == 0) {
					if ($arr_limit) {
						$arr_options['limit'] = $arr_limit;
					}
					if ($arr_order) {
						$arr_options['order'] = $arr_order;
					}
				}
				
				$collect->setPathOptions([$path => $arr_options]);
			}
		}

		$str_format = SiteStartVars::getRequestOutputFormat(['application/json', 'application/ld+json']);
		
		$obj_response = Response::getObject();
				
		if ($str_format == 'application/ld+json') {
			
			Response::setFormat(Response::getFormat() | Response::RENDER_LINKED_DATA);
			
			$request_id = URL_BASE.implode('/', $this->arr_request_vars);
			if ($_REQUEST) {
				$request_id = $request_id.'?'.implode('&', $_REQUEST);
			}
			$schema = SERVER_PROTOCOL.SERVER_NAME_1100CC.'/model/type/';
			
			// Target and output to the Response object directly
			
			$this->data = null;
			
			$obj_response->{'@context'} = [
				'nodegoat' => $schema,
				'prov' => 'http://www.w3.org/ns/prov#',
				'schema' => 'http://schema.org/',
				'dc' => 'http://purl.org/dc/terms/',
				'modified' => [
					'@id' => 'dc:modified',
					'@type' => 'schema:dateTime'
				],
				'generated' => [
					'@id' => 'prov:generatedAtTime',
					'@type' => 'schema:dateTime'
				]
			];
			
			$arr_context = Settings::get('nodegoat_api', 'context');
			
			if ($arr_context) {				
				$obj_response->{'@context'} += $arr_context;
			}
			
			$obj_response->{'@id'} = $request_id;
			$obj_response->{'generated'} = $obj_response->timestamp;
			
			$obj_response->{'@graph'} = Response::getStream('[', ']');
		} else {
			
			$this->data['objects'] = Response::getStream('{', '}');
		}
		
		Response::openStream(false, $obj_response);

		$output_objects = new CreateTypesObjectsPackage($arr_type_sets);
		if ($output_mode == 'raw') {
			$output_objects->setMode(CreateTypesObjectsPackage::MODE_RAW);
		}
		
		Mediator::checkState();

		memoryBoost(1000);
		
		$nr_stream = self::$nr_objects_stream;
		
		if ($arr_collect_info['connections']) {
			$nr_stream = self::$nr_objects_scope_stream;
		}
		
		$collect->setInitLimit($nr_stream);
		
		while ($collect->init($arr_filters)) {
			
			$arr_objects = $collect->getPathObjects('0');
			
			Mediator::checkState();

			timeLimit(2 * 60);
			
			if ($arr_collect_info['connections']) {
				$arr_objects = $output_objects->initPath($collect, $arr_objects);
			} else {
				$arr_objects = $output_objects->init($type_id, $arr_objects);
			}
			
			Mediator::checkState();
			
			if (count($arr_objects) > 20) { // Do not pretty print above a certain limit, use normal JSON
				Response::setFormat(Response::getFormat() & ~Response::PARSE_PRETTY);
			}
			
			Response::stream($arr_objects);
		}
	}
	
	// Store Data
	
	private function apiDataStore() {
		
		if (!$_SESSION['USER_ID']) {
			error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		if (!$this->is_user) {
			error(getLabel('msg_not_allowed'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		if (!$this->arr_settings['type']) {
			$this->errorInput('No Type specified');
		}
		
		$project_id = $_SESSION['custom_projects']['project_id'];
		$arr_project = StoreCustomProject::getProjects($project_id);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$this->type_id = $type_id;
		
		if ($_REQUEST['versioning'] !== null && $this->is_administrator) {
			
			$this->versioning = ($_REQUEST['versioning'] ? true : false);
		}
		
		$this->arr_client_update_objects = [];
		$this->arr_client_add_objects = [];
		$this->count_objects_updated = 0;
		$this->count_objects_added = 0;
		
		memoryBoost(1000);
		timeLimit(30 * 60);
		
		if (!$this->arr_settings['object']) { // Get Object IDs from the data
			
			$input = fopen('php://input', 'r');
			$resource = fopen('php://temp/maxmemory:'.(100 * BYTE_MULTIPLIER * BYTE_MULTIPLIER), 'r+'); // Keep resource in memory until it reaches 100MB, otherwise create a temporary file
			
			stream_copy_to_stream($input, $resource);
			
			fclose($input);
			
			$stream = new StreamJSONInput($resource);

			if ($_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				
				Mediator::checkState();

				$count = 0;
				
				$stream->init('{', function($str) use (&$count) {
								
					$arr_object = json_decode($str, true);
					
					if (!$arr_object) {
						return;
					}
					
					$object_id = GenerateTypeObjects::parseTypeObjectID(key($arr_object));

					$this->arr_client_update_objects[$object_id] = current($arr_object);
					
					$count++;
					
					if ($count == self::$nr_store_objects_buffer) {
						
						$count = 0;

						$this->apiDataStoreUpdateTypeObjects();
						Mediator::checkState();
					}
				});
				
				if ($this->arr_client_update_objects) {

					$this->apiDataStoreUpdateTypeObjects();
				}
				
				if ($this->count_objects_updated) {
					
					Labels::setVariable('count', $this->count_objects_updated);
					msg(getLabel(($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 'msg_object_deleted' : 'msg_object_updated')), false, LOG_CLIENT);
				}
			} else {
				
				Mediator::checkState();
								
				$count = 0;
				
				$stream->init('{"add":[', function($str) use (&$count) {
					
					$arr_object = json_decode($str, true);
					
					if (!$arr_object) {
						return;
					}
					
					$this->arr_client_add_objects[] = current($arr_object);
					
					$count++;
					
					if ($count == self::$nr_store_objects_buffer) {
						
						$count = 0;

						$this->apiDataStoreAddTypeObjects();
						Mediator::checkState();
					}
				});
				
				if ($this->arr_client_add_objects) {

					$this->apiDataStoreAddTypeObjects();
				}
				
				if ($this->count_objects_added) {
					
					Labels::setVariable('count', $this->count_objects_added);
					msg(getLabel('msg_object_added'), false, LOG_CLIENT);
				}
				
				Mediator::checkState();
				
				$count = 0;
				
				$stream->init('{"update":{', function($str) use (&$count) {
			
					$arr_object = json_decode($str, true);
					
					if (!$arr_object) {
						return;
					}
					
					$object_id = GenerateTypeObjects::parseTypeObjectID(key($arr_object));
					
					$this->arr_client_update_objects[$object_id] = current($arr_object);
					
					$count++;
					
					if ($count == self::$nr_store_objects_buffer) {
						
						$count = 0;

						$this->apiDataStoreUpdateTypeObjects();	
						Mediator::checkState();
					}
				});
				
				if ($this->arr_client_update_objects) {

					$this->apiDataStoreUpdateTypeObjects();
				}
				
				if ($this->count_objects_updated) {
					
					Labels::setVariable('count', $this->count_objects_updated);
					msg(getLabel('msg_object_updated'), false, LOG_CLIENT);
				}
				
				if (!$this->count_objects_updated && !$this->count_objects_added) {
					
					Mediator::checkState();
					
					$count = 0;
					
					$stream->init('[', function($str) use (&$count) {
					
						$arr_object = json_decode($str, true);
						
						if (!$arr_object) {
							return;
						}
						
						$this->arr_client_add_objects[] = current($arr_object);
						
						$count++;
						
						if ($count == self::$nr_store_objects_buffer) {
						
							$count = 0;

							$this->apiDataStoreAddTypeObjects();			
							Mediator::checkState();
						}
					});
					
					if ($this->arr_client_add_objects) {
					
						$this->apiDataStoreAddTypeObjects();
					}
					
					if ($this->count_objects_added) {
						
						Labels::setVariable('count', $this->count_objects_added);
						msg(getLabel('msg_object_added'), false, LOG_CLIENT);
					}
				}
			}
			
			fclose($resource);
		} else {
			
			if (count($this->arr_settings['object']) > 1) { // There should be one Object ID provided
				$this->errorInput('No Object specified');
			}
			
			$str_client = file_get_contents('php://input');
			
			if ($str_client) {
				$arr_object = json_decode($str_client, true);
			} else {
				$arr_object = ($_SERVER['REQUEST_METHOD'] === 'DELETE' ? true : false);
			}
			
			if ($arr_object) {
				
				$this->arr_client_update_objects[$this->arr_settings['object'][0]] = $arr_object;
				
				$this->apiDataStoreUpdateTypeObjects();
				
				if ($this->count_objects_updated) {
					
					Labels::setVariable('count', $this->count_objects_updated);
					msg(getLabel(($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 'msg_object_deleted' : 'msg_object_updated')), false, LOG_CLIENT);
				}
			}
		}

		if (!$this->count_objects_updated && !$this->count_objects_added) {
			
			$this->errorInput('No (valid) data provided');
		}
	}
	
	private function apiDataStoreUpdateTypeObjects() {
					
		$arr_filters = [];
		
		foreach ($this->arr_client_update_objects as $object_id => $arr_client_object) {
			
			$arr_filters['objects'][$object_id] = $object_id;
		}
		
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);
		$filter->setVersioning();
		$filter->setFilter($arr_filters);

		$arr_objects = $filter->init();
		
		if (!$arr_objects) {
			
			$this->arr_client_update_objects = [];
			return;
		}
		
		$arr_locked = [];
		
		$storage_lock = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID'], 'lock');
		
		foreach ($arr_objects as $object_id => $arr_object) {
			
			$storage_lock->setObjectID($object_id);
			
			try {
				$storage_lock->handleLockObject();
			} catch (Exception $e) {
				$arr_locked[] = $e;
			}
		}
		
		if ($arr_locked) {
			
			$storage_lock->removeLockObject(); // Remove locks from all possible successful ones
			
			foreach ($arr_locked as &$e) {
				
				$e = Trouble::strMsg($e); // Convert to message only
			}
			unset($e);
			
			Labels::setVariable('total', count($arr_locked));
			
			$str_locked = '<ul><li>'.implode('</li><li>', $arr_locked).'</li></ul>';
			
			error(getLabel('msg_object_locked_multi').PHP_EOL
				.$str_locked
			, TROUBLE_ERROR, LOG_CLIENT);
		}
		
		$storage_lock->upgradeLockObject(); // Apply permanent lock
		
		$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
		$storage->setVersioning($this->versioning);
		
		if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
			
			$storage->setMode(($_SERVER['REQUEST_METHOD'] === 'PUT' ? StoreTypeObjects::MODE_OVERWRITE : StoreTypeObjects::MODE_UPDATE), false);

			GenerateTypeObjects::dropResults(); // Cleanup possible leftover tables: clean transaction
			
			DB::startTransaction('data_api_store');
			
			try {
				
				$object_id_processing = 0;
				
				foreach ($arr_objects as $object_id => $arr_object) {
					
					$object_id_processing = $object_id;
				
					$storage->setObjectID($object_id);
					
					$storage->store((array)$this->arr_client_update_objects[$object_id]['object'], (array)$this->arr_client_update_objects[$object_id]['object_definitions'], (array)$this->arr_client_update_objects[$object_id]['object_subs']);
				}
					
				$storage->save();
					
				$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
			} catch (Exception $e) {
				
				Labels::setVariable('object', 'ID '.$object_id_processing);
				msg(getLabel('msg_object_error'), false, LOG_CLIENT);

				DB::rollbackTransaction('data_api_store');
				throw($e);
			}

			DB::commitTransaction('data_api_store');

			if (!$this->count_objects_updated) {
				
				$this->data['objects']['updated'] = Response::getStream('[', ']');
		
				Response::openStream(false, Response::getObject());
			}
			
			Response::stream(array_keys($arr_objects));
						
		} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
			
			foreach ($arr_objects as $object_id => $arr_object) {
			
				$storage->setObjectID($object_id);
			}
		
			$storage->delTypeObject(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
			
			$this->data['objects']['deleted'] = array_merge((array)$this->data['objects']['deleted'], array_keys($arr_objects));
		}
		
		$storage_lock->removeLockObject();
		
		$this->count_objects_updated += count($arr_objects);
		
		$this->arr_client_update_objects = [];
	}
	
	private function apiDataStoreAddTypeObjects() {
				
		$storage = new StoreTypeObjects($this->type_id, false, $_SESSION['USER_ID']);
		
		$storage->setVersioning($this->versioning);
		$storage->setMode(null, false);

		DB::startTransaction('data_api_store');
		
		$arr_object_ids = [];
		
		try {
			
			$object_id_processing = 0;
			
			foreach ($this->arr_client_add_objects as $arr_client_object) {
								
				$storage->setObjectID(false);
				
				$object_id_processing = $storage->store((array)$arr_client_object['object'], (array)$arr_client_object['object_definitions'], (array)$arr_client_object['object_subs']);
				
				$arr_object_ids[] = $object_id_processing;
			}
				
			$storage->save();
				
			$storage->commit(($_SESSION['NODEGOAT_CLEARANCE'] >= NODEGOAT_CLEARANCE_USER));
		} catch (Exception $e) {
			
			msg('An error occured after processing a new Object with the Object ID '.$object_id_processing.'.', false, LOG_CLIENT);

			DB::rollbackTransaction('data_api_store');
			throw($e);
		}

		DB::commitTransaction('data_api_store');
		
		if (!$this->count_objects_added) {
			
			$this->data['objects']['added'] = Response::getStream('[', ']');
		
			Response::openStream(false, Response::getObject());
		}
		
		Response::stream($arr_object_ids);

		$this->count_objects_added += count($arr_object_ids);
		
		$this->arr_client_add_objects = [];
	}
	
	// Get Data Model
	
	private function apiDataModel() {
		
		if (!isset($this->arr_settings['type'])) {
			$this->errorInput('No Type specified');
		}
		
		if ($this->is_administrator) {
				
			$arr_types = StoreType::getTypes();
		} else {
			
			$project_id = $_SESSION['custom_projects']['project_id'];
			$arr_project = StoreCustomProject::getProjects($project_id);
			
			$arr_types = $arr_project['types'];
		}
		
		if ($this->arr_settings['type']) {
			
			$arr_type_ids = explode(',', $this->arr_settings['type']);
			$arr_type_ids = arrParseRecursive($arr_type_ids, 'int');
		
			foreach ($arr_type_ids as $type_id) {
				
				if (!$arr_types[$type_id]) {
					$this->errorInput('No valid Type specified');
				}
			}
		} else {
			
			$arr_type_ids = array_keys($arr_types);
		}
		
		$output_mode = (self::$arr_output_modes_data_model[$_REQUEST['output']] ?: 'default');
		
		if ($output_mode == 'template') {
			$store_type = new StoreType(false, $_SESSION['USER_ID']);
		}
		
		$arr_date_options = StoreType::getDateOptions();
		$arr_location_options = StoreType::getLocationOptions();
		
		$arr_data = [];
		
		foreach ($arr_type_ids as $type_id) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			if ($arr_type_set['type']['class'] == StoreType::TYPE_CLASS_SYSTEM || ($output_mode == 'template' && $arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL)) {
				continue;
			}
						
			$use_type_id = ($output_mode == 'template' ? $arr_type_set['type']['name'] : (int)$type_id);
			
			$s_arr_type =& $arr_data['types'][$use_type_id];
			
			$s_arr_type = ['type' => [], 'object_descriptions' => [], 'object_sub_details' => []];
			
			$s_arr_type['type'] = [
				'id' => $use_type_id,
				'class' => $arr_type_set['type']['class'],
				'name' => $arr_type_set['type']['name'],
				'color' => $arr_type_set['type']['color']
			];

			if ($this->is_administrator) {
				
				$s_arr_type['type'] += [
					'use_object_name' => (bool)$arr_type_set['type']['use_object_name'],
					'object_name_in_overview' => (bool)$arr_type_set['type']['object_name_in_overview']
				];
			}
			
			foreach ($arr_type_set['definitions'] as $definition_id => $arr_definition) {
				
				$definition_id = (int)$definition_id;
				
				if ($output_mode == 'template') {
					
					$s_arr_type['definitions'][] = [
						'definition_name' => $arr_definition['definition_name'],
						'definition_text' => $arr_definition['definition_text']
					];
				} else {

					$s_arr_type['definitions'][$definition_id] = [
						'definition_id' => $definition_id,
						'definition_name' => $arr_definition['definition_name'],
						'definition_text' => $arr_definition['definition_text']
					];
				}
			}
            
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				$object_description_id = (int)$object_description_id;
				$object_description_ref_type_id = ((int)$arr_object_description['object_description_ref_type_id'] ?: false);
				
				if ($output_mode == 'template') {
					
					$object_description_id = $arr_object_description['object_description_name'];
					$object_description_ref_type_id = $store_type->getTypeName($object_description_ref_type_id);
				}
				
				$s_arr_type['object_descriptions'][$object_description_id] = [
					'object_description_id' => $object_description_id,
					'object_description_name' => $arr_object_description['object_description_name'],
					'object_description_value_type_base' => $arr_object_description['object_description_value_type_base'],
					'object_description_value_type_settings' => $arr_object_description['object_description_value_type_settings'],
					'object_description_is_required' => (bool)$arr_object_description['object_description_is_required'],
					'object_description_is_unique' => (bool)$arr_object_description['object_description_is_unique'],
					'object_description_is_identifier' => (bool)$arr_object_description['object_description_is_identifier'],
					'object_description_has_multi' => (bool)$arr_object_description['object_description_has_multi'],
					'object_description_ref_type_id' => $object_description_ref_type_id
				];
				
				if ($this->is_administrator) {
					
					$s_arr_type['object_descriptions'][$object_description_id] += [
						'object_description_in_name' => (bool)$arr_object_description['object_description_in_name'],
						'object_description_in_search' => (bool)$arr_object_description['object_description_in_search'],
						'object_description_in_overview' => (bool)$arr_object_description['object_description_in_overview'],
						'object_description_clearance_view' => (int)$arr_object_description['object_description_clearance_view'],
						'object_description_clearance_edit' => (int)$arr_object_description['object_description_clearance_edit']
					];
				}
			}
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				$arr_object_sub_details_self = $arr_object_sub_details['object_sub_details'];
				
				$object_sub_details_id = (int)$object_sub_details_id;
				$object_sub_details_date_use_object_sub_details_id = ((int)$arr_object_sub_details_self['object_sub_details_date_use_object_sub_details_id'] ?: false);
				$object_sub_details_date_start_use_object_sub_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_start_use_object_sub_description_id'] ?: false);
				$object_sub_details_date_start_use_object_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_start_use_object_description_id'] ?: false);
				$object_sub_details_date_end_use_object_sub_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_end_use_object_sub_description_id'] ?: false);
				$object_sub_details_date_end_use_object_description_id = ((int)$arr_object_sub_details_self['object_sub_details_date_end_use_object_description_id'] ?: false);
				$object_sub_details_location_ref_type_id = ((int)$arr_object_sub_details_self['object_sub_details_location_ref_type_id'] ?: false);
				$object_sub_details_location_ref_object_sub_details_id = ((int)$arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id'] ?: false);
				$object_sub_details_location_use_object_sub_details_id = ((int)$arr_object_sub_details_self['object_sub_details_location_use_object_sub_details_id'] ?: false);
				$object_sub_details_location_use_object_sub_description_id = ((int)$arr_object_sub_details_self['object_sub_details_location_use_object_sub_description_id'] ?: false);
				$object_sub_details_location_use_object_description_id = ((int)$arr_object_sub_details_self['object_sub_details_location_use_object_description_id'] ?: false);
				
				if ($output_mode == 'template') {

					$object_sub_details_date_use_object_sub_details_id = $store_type->getTypeObjectSubDetailsName($type_id, $object_sub_details_date_use_object_sub_details_id);
					$object_sub_details_date_start_use_object_sub_description_id = $store_type->getTypeObjectSubDescriptionName($type_id, $object_sub_details_id, $object_sub_details_date_start_use_object_sub_description_id);
					$object_sub_details_date_start_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_details_date_start_use_object_description_id);
					$object_sub_details_date_end_use_object_sub_description_id = $store_type->getTypeObjectSubDescriptionName($type_id, $object_sub_details_id, $object_sub_details_date_end_use_object_sub_description_id);
					$object_sub_details_date_end_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_details_date_end_use_object_description_id);
					$object_sub_details_location_ref_object_sub_details_id = $store_type->getTypeObjectSubDetailsName($object_sub_details_location_ref_type_id, $object_sub_details_location_ref_object_sub_details_id);
					$object_sub_details_location_ref_type_id = $store_type->getTypeName($object_sub_details_location_ref_type_id);
					$object_sub_details_location_use_object_sub_details_id = $store_type->getTypeObjectSubDetailsName($type_id, $object_sub_details_location_use_object_sub_details_id);
					$object_sub_details_location_use_object_sub_description_id = $store_type->getTypeObjectSubDescriptionName($type_id, $object_sub_details_id, $object_sub_details_location_use_object_sub_description_id);
					$object_sub_details_location_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_details_location_use_object_description_id);
					$object_sub_details_id = $arr_object_sub_details_self['object_sub_details_name'];
				}
				
				$s_arr_object_sub_details =& $s_arr_type['object_sub_details'][$object_sub_details_id];
				
				$s_arr_object_sub_details = ['object_sub_details' => [], 'object_sub_descriptions' => []];
				
				$s_arr_object_sub_details['object_sub_details'] = [
					'object_sub_details_id' => $object_sub_details_id,
					'object_sub_details_name' => $arr_object_sub_details_self['object_sub_details_name'],
					'object_sub_details_is_single' => (bool)$arr_object_sub_details_self['object_sub_details_is_single'],
					'object_sub_details_is_required' => (bool)$arr_object_sub_details_self['object_sub_details_is_required'],
					'object_sub_details_has_date' => (bool)$arr_object_sub_details_self['object_sub_details_has_date'],
					'object_sub_details_is_date_period' => (bool)$arr_object_sub_details_self['object_sub_details_is_date_period'],
					'object_sub_details_date_setting' => ($arr_date_options[$arr_object_sub_details_self['object_sub_details_date_setting']]['id'] ?? ''),
					'object_sub_details_date_use_object_sub_details_id' => $object_sub_details_date_use_object_sub_details_id,
					'object_sub_details_date_start_use_object_sub_description_id' => $object_sub_details_date_start_use_object_sub_description_id,
					'object_sub_details_date_start_use_object_description_id' => $object_sub_details_date_start_use_object_description_id,
					'object_sub_details_date_end_use_object_sub_description_id' => $object_sub_details_date_end_use_object_sub_description_id,
					'object_sub_details_date_end_use_object_description_id' => $object_sub_details_date_end_use_object_description_id,
					'object_sub_details_has_location' => (bool)$arr_object_sub_details_self['object_sub_details_has_location'],
					'object_sub_details_location_setting' => ($arr_location_options[$arr_object_sub_details_self['object_sub_details_location_setting']]['id'] ?? ''),
					'object_sub_details_location_ref_type_id' => $object_sub_details_location_ref_type_id,
					'object_sub_details_location_ref_type_id_locked' => (bool)$arr_object_sub_details_self['object_sub_details_location_ref_type_id_locked'],
					'object_sub_details_location_ref_object_sub_details_id' => $object_sub_details_location_ref_object_sub_details_id,
					'object_sub_details_location_ref_object_sub_details_id_locked' => (bool)$arr_object_sub_details_self['object_sub_details_location_ref_object_sub_details_id_locked'],
					'object_sub_details_location_use_object_sub_details_id' => $object_sub_details_location_use_object_sub_details_id,
					'object_sub_details_location_use_object_sub_description_id' => $object_sub_details_location_use_object_sub_description_id,
					'object_sub_details_location_use_object_description_id' => $object_sub_details_location_use_object_description_id,
					'object_sub_details_location_use_object_id' => (bool)$arr_object_sub_details_self['object_sub_details_location_use_object_id']
				];
				
				if ($this->is_administrator) {
					
					$s_arr_object_sub_details['object_sub_details'] += [
						'object_sub_details_clearance_view' => (int)$arr_object_sub_details_self['object_sub_details_clearance_view'],
						'object_sub_details_clearance_edit' => (int)$arr_object_sub_details_self['object_sub_details_clearance_edit']
					];
				}
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$object_sub_description_id = (int)$object_sub_description_id;
					$object_sub_description_ref_type_id = ((int)$arr_object_sub_description['object_sub_description_ref_type_id'] ?: false);
					$object_sub_description_use_object_description_id = ((int)$arr_object_sub_description['object_sub_description_use_object_description_id'] ?: false);
					
					if ($output_mode == 'template') {
						
						$object_sub_description_id = $arr_object_sub_description['object_sub_description_name'];
						$object_sub_description_ref_type_id = $store_type->getTypeName($object_sub_description_ref_type_id);
						$object_sub_description_use_object_description_id = $store_type->getTypeObjectDescriptionName($type_id, $object_sub_description_use_object_description_id);
					}
									
					$s_arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id] = [
						'object_sub_description_id' => $object_sub_description_id,
						'object_sub_description_name' => $arr_object_sub_description['object_sub_description_name'],
						'object_sub_description_value_type_base' => $arr_object_sub_description['object_sub_description_value_type_base'],
						'object_sub_description_value_type_settings' => $arr_object_sub_description['object_sub_description_value_type_settings'],
						'object_sub_description_is_required' => $arr_object_sub_description['object_sub_description_is_required'],
						'object_sub_description_ref_type_id' => $object_sub_description_ref_type_id,
						'object_sub_description_use_object_description_id' => $object_sub_description_use_object_description_id,
					];
					
					if ($this->is_administrator) {
					
						$s_arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id] += [
							'object_sub_description_in_name' => (bool)$arr_object_sub_description['object_sub_description_in_name'],
							'object_sub_description_in_search' => (bool)$arr_object_sub_description['object_sub_description_in_search'],
							'object_sub_description_in_overview' => (bool)$arr_object_sub_description['object_sub_description_in_overview'],
							'object_sub_description_clearance_view' => (int)$arr_object_sub_description['object_sub_description_clearance_view'],
							'object_sub_description_clearance_edit' => (int)$arr_object_sub_description['object_sub_description_clearance_edit']
						];
					}
				}
			}
		}
		unset($s_arr_type, $s_arr_object_sub_details);
				
		$this->data = $arr_data;
	}
	
	// Store Data Model
	
	private function apiDataModelStore() {
		
		if (!$_SESSION['USER_ID']) {
			error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}
		
		if (!$this->is_administrator) {
			error(getLabel('msg_not_allowed'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
		}

		$arr_client = file_get_contents('php://input');
		if ($arr_client) {
			$arr_client = json_decode($arr_client, true);
		}
		
		if (!$arr_client) {
			$this->errorInput('No data provided');
		}
		
		$arr_client_update_types = [];
		$arr_client_add_types = [];
	
		if (!$this->arr_settings['type']) {
			
			if ($_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
				
				$arr_client_update_types = $arr_client;
			} else {
				
				if ($arr_client['update'] || $arr_client['add']) {
					
					$arr_client_update_types = $arr_client['update'];
					$arr_client_add_types = $arr_client['add'];
				} else {
					
					$arr_client_add_types = $arr_client;
				}
			}
		} else {
			
			$arr_client_update_types[$this->arr_settings['type']] = $arr_client;
		}
		
		if ($arr_client_add_types) {

			DB::startTransaction('data_model_api_store');
			
			$arr_type_ids = [];
			
			try {
				
				$arr_client_resolve_types = [];
								
				foreach ($arr_client_add_types as $arr_client_type) {
					
					$store_type = new StoreType(false, $_SESSION['USER_ID']);
					
					$type_id = $store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
					
					$arr_type_ids[] = $type_id;					
					
					if ($store_type->hasUnresolvedIDs()) {
						
						$arr_client_resolve_types[$type_id] = $arr_client_type;
					}
				}
				
				foreach ($arr_client_resolve_types as $type_id => $arr_client_type) {
					
					$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
					$store_type->setMode(StoreType::MODE_UPDATE);
					
					$store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
				}
				
				StoreType::setTypesObjectPaths();
			} catch (Exception $e) {

				DB::rollbackTransaction('data_model_api_store');
				throw($e);
			}

			DB::commitTransaction('data_model_api_store');
			
			Labels::setVariable('count', count($arr_type_ids));
			msg(getLabel('msg_type_added'), false, LOG_CLIENT);
			
			$this->data['types']['added'] = $arr_type_ids;
		}
		
		if ($arr_client_update_types) {
			
			$store_type = new StoreType(false, $_SESSION['USER_ID']);
						
			foreach ($arr_client_update_types as $type_id => $arr_client_type) {
				
				if (!$type_id || !$store_type->getTypeID($type_id)) {
					$this->errorInput('No valid Type specified');
				}
			}

			if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
				
				$store_mode = ($_SERVER['REQUEST_METHOD'] === 'PUT' ? StoreType::MODE_OVERWRITE : StoreType::MODE_UPDATE);
				
				DB::startTransaction('data_model_api_store');

				try {
					
					$arr_client_resolve_types = [];
						
					foreach ($arr_client_update_types as $type_id => $arr_client_type) {
						
						$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
						$store_type->setMode($store_mode);
						
						$store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
						
						if ($store_type->hasUnresolvedIDs()) {
						
							$arr_client_resolve_types[$type_id] = $arr_client_type;
						}
					}
					
					foreach ($arr_client_resolve_types as $type_id => $arr_client_type) {
					
						$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
						$store_type->setMode(StoreType::MODE_UPDATE);
						
						$store_type->store((array)$arr_client_type['type'], (array)$arr_client_type['definitions'], (array)$arr_client_type['object_descriptions'], (array)$arr_client_type['object_sub_details']);
					}
					
					StoreType::setTypesObjectPaths();
				} catch (Exception $e) {

					DB::rollbackTransaction('data_model_api_store');
					throw($e);
				}
				
				DB::commitTransaction('data_model_api_store');
				
				Labels::setVariable('count', count($arr_client_update_types));
				msg(getLabel('msg_type_updated'), false, LOG_CLIENT);
					
				$this->data['types']['updated'] = array_keys($arr_client_update_types);
			} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
				
				foreach ($arr_client_update_types as $type_id => $arr_client_type) {
					
					$store_type = new StoreType($type_id, $_SESSION['USER_ID']);
					
					$store_type->delType();
				}
				
				Labels::setVariable('count', count($arr_client_update_types));
				msg(getLabel('msg_type_deleted'), false, LOG_CLIENT);
				
				$this->data['types']['deleted'] = array_keys($arr_client_update_types);
			}
		}
	}
	
	// Get Data Analysis
		
	private function apiDataAnalysis() {
		
		if (!$this->arr_settings['type']) {
			$this->errorInput('No Type specified');
		}
		
		$project_id = $_SESSION['custom_projects']['project_id'];
		$arr_project = StoreCustomProject::getProjects($project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$analysis_id = (int)$this->arr_settings['use'];
		$arr_analysis = false;
		
		if ($analysis_id) {
			
			$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($project_id, false, false, $analysis_id, $arr_use_project_ids);
			$arr_analysis = data_analysis::parseTypeAnalysis($type_id, $arr_analysis);
		}
				
		if (!$arr_analysis) {
			$this->errorInput('No valid Analysis ID specified');
		}
		
		$arr_filters = $this->getRequestTypeFilters();
		$arr_scope = $arr_analysis['scope'];
		
		$collect = data_analysis::getTypeAnalysisCollector($type_id, $arr_filters, $arr_scope);
			
		$analyse = new AnalyseTypeObjects($type_id, $arr_analysis);
			
		$resource = $analyse->input($collect, $arr_filters);
		
		Response::setFormat(Response::OUTPUT_TEXT);
		
		Response::sendFileHeaders(false, 'graph.csv');
		
		fpassthru($resource);
	}
	
	private function apiDataAnalysisStore() {
		
		if (!$this->arr_settings['type'] || !isset($this->arr_settings['object'])) {
			$this->errorInput('No Type/Object specified');
		}
		
		$project_id = $_SESSION['custom_projects']['project_id'];
		$arr_project = StoreCustomProject::getProjects($project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$type_id = (int)$this->arr_settings['type'];
		
		if (!$arr_project['types'][$type_id]) {
			$this->errorInput('No valid Type specified');
		}
		
		$analysis_id = (int)$this->arr_settings['use'];
		$arr_analysis = false;
		
		if ($analysis_id) {
			
			$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($project_id, false, false, $analysis_id, $arr_use_project_ids);
			$arr_analysis = self::parseTypeAnalysis($type_id, $arr_analysis);
		}
				
		if (!$arr_analysis) {
			$this->errorInput('No valid Analysis ID specified');
		}
		
		$storage = new StoreTypeObjectsExtensions($type_id, false, $_SESSION['USER_ID']);
		
		if (!$this->arr_settings['object']) { // Get Object IDs from the data
			
			$file_client = fopen('php://input', 'r');
		
			DB::startTransaction('data_api_store_analyse');
			
			if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
				$storage->resetTypeObjectAnalysis(0, $analysis_id);
			}
			
			try {
				
				 while (($arr_row = fgetcsv($file_client)) !== false) {

					$object_id_processing = GenerateTypeObjects::parseTypeObjectID($arr_row[0]);
				
					$storage->setObjectID($object_id_processing);
					
					$number = ($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 0 : $arr_row[1]);
				
					$storage->addTypeObjectAnalysis(0, $analysis_id, $number);
					
					$this->count_objects_updated++;
				}
				
				$storage->save();
			} catch (Exception $e) {
				
				Labels::setVariable('object', 'ID '.$object_id_processing);
				msg(getLabel('msg_object_error'), false, LOG_CLIENT);

				DB::rollbackTransaction('data_api_store_analyse');
				throw($e);
			}
			
			fclose($file_client);

			DB::commitTransaction('data_api_store_analyse');			
		} else {
			
			if (count($this->arr_settings['object']) > 1) { // There should be one Object ID provided
				$this->errorInput('No Object specified');
			}
			
			$str_client = file_get_contents('php://input');
		
			DB::startTransaction('data_api_store_analyse');

			try {
				
				$object_id_processing = $this->arr_settings['object'][0];
				
				$storage->setObjectID($object_id_processing);
				
				$number = ($_SERVER['REQUEST_METHOD'] === 'DELETE' ? 0 : $str_client);
						
				$storage->addTypeObjectAnalysis(0, $analysis_id, $number);
				
				$this->count_objects_updated++;
				
				$storage->save();
			} catch (Exception $e) {
				
				Labels::setVariable('object', 'ID '.$object_id_processing);
				msg(getLabel('msg_object_error'), false, LOG_CLIENT);

				DB::rollbackTransaction('data_api_store_analyse');
				throw($e);
			}
			
			DB::commitTransaction('data_api_store_analyse');
		}

		if ($this->count_objects_updated) {
			
			Labels::setVariable('count', $this->count_objects_updated);
			msg(getLabel('msg_object_updated'), false, LOG_CLIENT);
		} else {
			
			$this->errorInput('No (valid) data provided');
		}
	}
	
	// Get Data Reconcile
		
	private function apiDataReconcile() {
	
		// Version 8.1
	}
	
	protected function getRequestTypeFilters() {
		
		$project_id = $_SESSION['custom_projects']['project_id'];
		$arr_project = StoreCustomProject::getProjects($project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_filters = [];
		
		if ($this->arr_settings['filter']) { // Filter ID
				
			$filter_id = (int)$this->arr_settings['filter'];
			
			$arr_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, false, false, $filter_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
			$arr_filter = FilterTypeObjects::convertFilterInput($arr_filter['object']);
			
			$arr_filters[] = $arr_filter;
		}
		
		if ($_REQUEST['filter']) { // Filter (additional)
			
			if (is_numeric($_REQUEST['filter'])) { // Filter ID
				
				$filter_id = (int)$_REQUEST['filter'];
			
				$arr_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, false, false, $filter_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids);
				$arr_filter = FilterTypeObjects::convertFilterInput($arr_filter['object']);
				
				$arr_filters[] = $arr_filter;
			} else { // Filter form
				
				$arr_filter = json_decode($_REQUEST['filter'], true);
				$arr_filter = FilterTypeObjects::convertFilterInput($arr_filter);
			
				$arr_filters[] = $arr_filter;
			}
		}
		
		if ($_REQUEST['object_id']) {
			
			$arr_filters['objects'] = explode(',', $_REQUEST['object_id']);
			
			foreach ($arr_filters['objects'] as &$object_id) {
				$object_id = GenerateTypeObjects::parseTypeObjectID($object_id);
			}
			unset($object_id);
			
		} else if ($this->arr_settings['object']) {
			
			$arr_filters['objects'] = $this->arr_settings['object'];
		}
		
		if ($_REQUEST['search']) {
			$arr_filters['search'] = $_REQUEST['search'];
		}
		
		return $arr_filters;
	}
	
	private function errorInput($msg) {
		
		error(getLabel('msg_missing_information').' '.$msg.'.', TROUBLE_INVALID_REQUEST, LOG_CLIENT);
	}
}
