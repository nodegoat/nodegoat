<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_ingest extends ingest_source {

	public static function moduleProperties() {
		parent::moduleProperties();
		static::$label = 'Data Ingest';
	}
	
	protected $arr_access = [
		'data_entry' => [],
		'data_view' => [],
		'data_filter' => [],
		'data_pattern_pairs' => []
	];
	
	protected static $use_project = true;
	protected static $use_type_filter = true;
	protected static $use_object_identifier_uri = true;
	protected static $use_query_object_value = true;
	protected static $use_query_type_object_value = true;
	protected static $use_query_value = true;
	
	protected static $arr_labels = [
		'lbl_source' => 'lbl_data_ingest_source',
		'lbl_pointer_data' => 'lbl_data_ingest_pointer_data',
		'lbl_pointer_filter' => 'lbl_data_ingest_pointer_data',
		'lbl_pointer_query' => 'lbl_data_ingest_pointer_query',
		'inf_pointer_data' => 'inf_data_ingest_pointer_data',
		'inf_pointer_filter' => 'inf_data_ingest_pointer_data',
		'inf_pointer_query' => 'inf_data_ingest_pointer_query'
	];
	
	protected static $num_pointer_query_limit = 10;
	protected static $num_pointer_query_resolve_limit = 5;
	protected static $num_pointer_filter_limit = 500;
	protected static $num_pointer_filter_resolve_limit = 50;
	
	protected function createTemplateSettingsExtra($arr_import_template) {
		
		return '';
	}
	
	protected function createCheckTemplate($arr_template, $source_id) {
		
		$arr_resource = StoreResourceExternal::getResources($source_id);
		
		$is_compatible = IngestTypeObjectsResourceExternal::checkTemplateSourceCompatibility($arr_template, $arr_resource);
		
		if (!$is_compatible) {
			
			return '<section class="info attention body">'.parseBody(getLabel('inf_ingest_incompatible_source')).'</section>';
		}
		
		$system_type_id = StoreType::getSystemTypeID('ingestion');
		$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
		$system_object_id = $arr_template['identifier'];
		
		if ($arr_template['is_mode_query']) { // Query mode

			$html_state = '<li>
				<label>'.getLabel('lbl_type_module_objects_processed').'</label>
				<div>'.FilterTypeObjects::getModuleObjectTypeCount($system_object_description_id, $system_object_id, $system_type_id).'</div>
			</li>';
		} else {
			
			$str_cache = '';
			$str_processed = '';
			
			$num_pointer_state = $arr_template['state']['pointer_state'];
			
			if (isPath($arr_template['state']['source']) || $num_pointer_state == -1) {
				
				$num_total = 0;
				
				if (isPath($arr_template['state']['source'])) {
					
					$ingest = new IngestTypeObjectsResourceExternal($arr_template['type_id']);
					$ingest->setTemplate($arr_template['pointers'], $arr_template['mode']);
					$ingest->setSource($arr_template['state']['source']);
					
					$num_total = $ingest->getRowPointerCount();
				}
				
				if ($num_pointer_state == -1) {
					
					$str_cache = getLabel('lbl_incomplete').' ('.$num_total.')';
					$str_processed = getLabel('lbl_none');
				} else {
					
					$str_cache = getLabel('lbl_yes').' ('.$num_total.')';
					$str_processed = $num_pointer_state;
				}
			} else {
				
				$str_cache = getLabel('lbl_no');
				$str_processed = getLabel('lbl_none');
			}
					
			$html_state = '<li>
				<label>'.getLabel('lbl_data_ingest_cache').'</label>
				<div>'.$str_cache .'</div>
			</li>
			<li>
				<label>'.getLabel('lbl_type_module_objects_processed').'</label>
				<div>'.$str_processed.'</div>
			</li>';
		}
		
		$external = new ResourceExternal($arr_resource);
		$arr_response_values = $external->getResponseValues(true);
		
		$has_webservice = false;
		
		if (arrHasKeysRecursive('conversion_id', $arr_response_values, true)) {
			
			SiteEndVars::setFeedback('ingest_source', cms_details::setWebServiceActiveUser($_SESSION['USER_ID'], value2Hash(session_id())));
			$has_webservice = true;
		}
		
		$return = '<div class="ingest-source template-check"'.($has_webservice ? ' data-webservice="1"' : '').'>
			
			<fieldset>
				<legend>'.getLabel('lbl_status').' <input type="button" id="y:data_ingest:reset_process-'.$arr_template['identifier'].'_'.$source_id.'" class="data del quick" value="reset" /></legend>
				<ul>
					'.$html_state.'
				</ul>
			</fieldset>
			
		</div>';
		
		return $return;
	}
	
	public function createProcessTemplate($arr_template, $source_id, $arr_feedback) {
		
		$arr_feedback = $arr_feedback['ingest'];
		
		$arr_resource = StoreResourceExternal::getResources($source_id);
		
		$is_compatible = IngestTypeObjectsResourceExternal::checkTemplateSourceCompatibility($arr_template, $arr_resource);
		
		if (!$is_compatible) {
			
			return [
				'is_done' => true,
				'html' => '<section class="info attention body">'.parseBody(getLabel('inf_ingest_incompatible_source')).'</section>'
			];
		}
		
		$system_object_id = $arr_template['identifier'];
		
		$this->is_new_template_process = (!isset($arr_feedback['in_process'])); // Only true first round
		
		while (true) {
			
			if ($arr_template['is_mode_query']) {
				$html = $this->doProcessTemplateQuery($arr_template, $source_id);
			} else {
				$html = $this->doProcessTemplateFilter($arr_template, $source_id);
			}
			
			if ($this->is_done_template_process || $this->has_feedback_template_process) {
				break;
			} else {
				$arr_template = static::getTemplate($system_object_id);
			}
			
			$this->is_new_template_process = true;
		}
		
		$html = '<div class="ingest-source template-process">
			'.$html.'
		</div>'
		.'<input name="ingest[in_process]" type="hidden" value="1" />';
		
		return [
			'is_done' => $this->is_done_template_process,
			'has_feedback' => $this->has_feedback_template_process,
			'html' => $html
		];
	}
	
	protected function doProcessTemplateQuery($arr_template, $source_id) {
				
		$system_type_id = StoreType::getSystemTypeID('ingestion');
		$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
		$system_object_id = $arr_template['identifier'];
		
		$type_id = $arr_template['type_id'];
		$arr_state = $arr_template['state'];
		$arr_resource = StoreResourceExternal::getResources($source_id);
		
		$arr_pointers = $arr_template['pointers'];
		$use_type_id = $type_id;
		$use_type_as_query = false;
		$arr_filter_objects = [];
		
		if ($arr_pointers['query_type_object_value']) { // IngestTypeObjects::MODE_OVERWRITE && IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS
			
			$use_type_id = $arr_template['query_type_id'];
			$arr_filter_objects = ($arr_template['query_type_filter'] ? FilterTypeObjects::convertFilterInput($arr_template['query_type_filter']) : []);
			$arr_pointers_query_object = $arr_pointers['query_type_object_value'];
			$use_type_as_query = true;
		} else { // IngestTypeObjects::MODE_UPDATE
			
			if ($arr_template['type_filter']) {
				$arr_filter_objects = FilterTypeObjects::convertFilterInput($arr_template['type_filter']);
			}
			$arr_pointers['filter_object_identifier'] = ['pointer_heading' => 'object_id']; // Target the queried object IDs in the source file
			$arr_pointers_query_object = $arr_pointers['query_object_value'];
		}

		$ingest = new IngestTypeObjectsResourceExternal($type_id, ['user_id' => $_SESSION['USER_ID'], 'system_object_id' => $system_object_id]);
		$ingest->useLogIdentifier(false);
		$ingest->setTemplate($arr_pointers, $arr_template['mode']);
		
		if ($arr_template['mode'] == IngestTypeObjects::MODE_OVERWRITE_IF_NOT_EXISTS && $arr_template['type_filter']) {
			$ingest->setFilter(FilterTypeObjects::convertFilterInput($arr_template['type_filter']));
		}
		
		$get_new = true;
		
		if (isPath($arr_state['source'])) { // Still has file, check to continue
						
			$file_source = $arr_state['source'];
			$pointer_state = $arr_state['pointer_state'];
			
			$arr_object_ids = static::getModuleObjectTypeObjects($system_object_id, $use_type_id, $arr_filter_objects, 1, false);
			$arr_object_ids = array_keys($arr_object_ids);
			
			if ($arr_object_ids) {
				$get_new = false;
			}
		}

		if ($get_new) {
			
			$num_limit = ($ingest->hasResolveFilters() ? static::$num_pointer_query_resolve_limit : static::$num_pointer_query_limit);
				
			$arr_object_ids = static::getModuleObjectTypeObjects($system_object_id, $use_type_id, $arr_filter_objects, 0, true, $num_limit);
			$arr_object_ids = array_keys($arr_object_ids);
			
			if (!$arr_object_ids) {
				
				$this->is_done_template_process = true;
				
				$count = (int)FilterTypeObjects::getModuleObjectTypeCount($system_object_description_id, $system_object_id, $system_type_id);
				$arr_result = ['count' => $count, 'mode' => IngestTypeObjects::MODE_UPDATE];
				
				return $this->createProcessTemplateStoreCheck($arr_result);
			}
			
			Mediator::checkState();
			
			$arr_map = arrValuesRecursive('element_id', $arr_pointers_query_object);
			$arr_map = array_combine($arr_map, $arr_map);
			$arr_selection = StoreType::getTypeSelectionByFlatMap($use_type_id, $arr_map);
			
			$filter = new FilterTypeObjects($use_type_id, GenerateTypeObjects::VIEW_SET_EXTERNAL);
			$filter->setFilter(['objects' => $arr_object_ids]);
			$filter->setSelection($arr_selection);
			
			$arr_objects_set = $filter->init();
			
			$external = new ResourceExternal($arr_resource);
			$arr_response_values = $external->getResponseValues(true);
			
			FileStore::storeFile($arr_state['source']);
			$file_source = fopen($arr_state['source'], 'w+');
			$stream = IngestTypeObjectsResourceExternal::getSourceStream($file_source);
			$socket = false;
			$has_results = false;
			
			if (arrHasKeysRecursive('conversion_id', $arr_response_values, true)) {
				$socket = $this->getConversionSocket();
			}
			
			$arr_filter_value = [];
						
			foreach ($arr_pointers['filter_value'] as $arr_pointer) {
				$arr_filter_value[$arr_pointer['pointer_heading']][] = $arr_pointer['value'];
			}
			
			foreach ($arr_object_ids as $use_object_id) {
			
				$arr_filter = [];
				
				if ($arr_pointers_query_object) {

					$arr_object_set = GenerateTypeObjects::getTypeObjectValuesByFlatMap($use_type_id, $arr_objects_set[$use_object_id], $arr_map);
					
					foreach ($arr_pointers_query_object as $arr_pointer) {
						
						$arr_value = $arr_object_set[$arr_pointer['element_id']];
						
						if (!$arr_value) {
							continue;
						}
						
						$arr_filter[$arr_pointer['pointer_heading']] = $arr_value;
					}
				}
				
				if (!$arr_filter) { // Check if the filter contains object-specific values
					continue;
				}
				
				foreach ($arr_pointers['query_value'] as $arr_pointer) {
					$arr_filter[$arr_pointer['pointer_heading']] = $arr_pointer['value'];
				}

				$external = new ResourceExternal($arr_resource);
				$external->setFilter($arr_filter, true);
				$external->setResultConversionSocket($socket);
				$external->request();
			
				if (!$external->hasResult()) {
					continue;
				}
				
				if ($use_type_as_query) { // The object ID is not needed anymore because we only used the object's data
					$use_object_id = false;
				}
				
				IngestTypeObjectsResourceExternal::streamSourceByObjectId($stream, $external, $use_object_id, $arr_filter_value);
				
				$has_results = true;
			}
			
			if (!$has_results) {
				
				static::updateTemplateState($system_object_id, ['object_ids' => $arr_object_ids, 'status' => 'done']);
				return '';
			}

			$stream->close();
			rewind($file_source);
			if ($socket) {
				$socket->close();
			}
			
			static::updateTemplateState($system_object_id, ['object_ids' => $arr_object_ids, 'status' => 'pending']);

			$pointer_state = 0;
		}
		
		$ingest->setSource($file_source);
		
		$is_ignorable = (!$this->is_new_template_process);
		$arr_unresolved_filters = $ingest->resolveFilters($is_ignorable);
		
		if ($arr_unresolved_filters) {
			
			$html = $this->createProcessTemplateResultCheck($arr_unresolved_filters);

			$this->has_feedback_template_process = true;
		} else {
			
			SiteStartVars::stopSession(); // Make sure storage & status update is not interrupted
			
			$ingest->process();
			
			$arr_result = $ingest->store();
			
			if ($arr_result['locked'] !== null || $arr_result['error'] !== null) {
				
				$this->has_feedback_template_process = true;
			} else {
				
				static::updateTemplateState($system_object_id, ['source' => $arr_state['source'], 'object_ids' => $arr_object_ids, 'status' => 'done']);
			}
			
			SiteStartVars::startSession();
			
			if ($this->has_feedback_template_process) {
				
				$html = $this->createProcessTemplateStoreCheck($arr_result);
			}
		}
		
		return $html;
	}
	
	protected function doProcessTemplateFilter($arr_template, $source_id) {
				
		$system_type_id = StoreType::getSystemTypeID('ingestion');
		$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
		$system_object_id = $arr_template['identifier'];
		
		$type_id = $arr_template['type_id'];
		$arr_state = $arr_template['state'];
		$arr_pointers = $arr_template['pointers'];
		$arr_resource = StoreResourceExternal::getResources($source_id);
		
		$ingest = new IngestTypeObjectsResourceExternal($type_id, ['user_id' => $_SESSION['USER_ID'], 'system_object_id' => $system_object_id]);
		$ingest->useLogIdentifier(false);
		$ingest->setTemplate($arr_template['pointers'], $arr_template['mode']);
		
		if ($arr_template['type_filter']) {
			$ingest->setFilter(FilterTypeObjects::convertFilterInput($arr_template['type_filter']));
		}

		if (isPath($arr_state['source']) && $arr_state['pointer_state'] >= 0) { // Still has complete file, continue
			
			$file_source = $arr_state['source'];
			$pointer_state = $arr_state['pointer_state'];
		} else {
			
			$pointer_state = -1; // Creating cache
			static::updateTemplateState($system_object_id, ['pointer_state' => $pointer_state]);

			$arr_filter = [];

			foreach ($arr_pointers['query_value'] as $arr_pointer) {
				$arr_filter[$arr_pointer['pointer_heading']] = $arr_pointer['value'];
			}
			
			$arr_filter_value = [];
							
			foreach ($arr_pointers['filter_value'] as $arr_pointer) {
				$arr_filter_value[$arr_pointer['pointer_heading']][] = $arr_pointer['value'];
			}
			
			$num_offset = false;
			$num_limit = false;
			
			$external = new ResourceExternal($arr_resource);
			$arr_request_variables = $external->getRequestVariables();
			$arr_response_values = $external->getResponseValues(true);
			
			if ($arr_request_variables['offset'] && $arr_request_variables['limit']) {
				
				$num_offset = 0;

				if (is_numeric($arr_request_variables['limit'])) {
					$num_limit = $arr_request_variables['limit'];		
				} else {
					$num_limit = 1000;
				}
			}
			
			$file_source = false;
			$stream = false;
			$socket = false;
			
			if (arrHasKeysRecursive('conversion_id', $arr_response_values, true)) {
				$socket = $this->getConversionSocket();
			}
			
			while (true) {
				
				Mediator::checkState();
			
				$external = new ResourceExternal($arr_resource);
				$external->setFilter($arr_filter, true);
				$external->setLimit([$num_offset, $num_limit]);
				$external->setResultConversionSocket($socket);
				$external->request();
				
				$num_limit = $external->getResultValuesCount();
				
				if (!$num_limit) {
					
					if (!$num_offset) {
					
						$this->is_done_template_process = true;
					
						$arr_result = ['count' => 0, 'mode' => $ingest->getMode()];
					
						return $this->createProcessTemplateStoreCheck($arr_result);
					} else {
						break;
					}
				} else {
					
					if (!$num_offset) {
						
						FileStore::storeFile($arr_state['source']);
						$file_source = fopen($arr_state['source'], 'w+');
						
						$stream = IngestTypeObjectsResourceExternal::getSourceStream($file_source);
					}
				}

				IngestTypeObjectsResourceExternal::streamSource($stream, $external, $arr_filter_value);
				
				if ($num_offset === false) {
					break;
				}
				
				$num_offset += $num_limit;
			}
			
			if ($file_source) {
				$stream->close();
				rewind($file_source);
			}
			if ($socket) {
				$socket->close();
			}
			
			$pointer_state = 0; // Has cache
			static::updateTemplateState($system_object_id, ['pointer_state' => $pointer_state]);
		}
		
		$num_limit = ($ingest->hasResolveFilters() ? static::$num_pointer_filter_resolve_limit : static::$num_pointer_filter_limit);

		$ingest->setSource($file_source);
		$ingest->setLimit([$pointer_state, $num_limit]);
		
		$is_ignorable = (!$this->is_new_template_process);
		$arr_unresolved_filters = $ingest->resolveFilters($is_ignorable);
		
		if ($arr_unresolved_filters) {
			
			$html = $this->createProcessTemplateResultCheck($arr_unresolved_filters);
			
			$this->has_feedback_template_process = true;
		} else {
			
			SiteStartVars::stopSession(); // Make sure storage & status update is not interrupted
			
			$ingest->process();
			
			$arr_result = $ingest->store();
			
			if ($arr_result['locked'] !== null || $arr_result['error'] !== null) {

				$this->has_feedback_template_process = true;
			} else {

				$count_max = $ingest->getRowPointerCount();
				$count_total = ($pointer_state + $num_limit);
				$count_total = ($count_total > $count_max ? $count_max : $count_total);
				
				static::updateTemplateState($system_object_id, ['pointer_state' => $count_total]);
				
				if ($count_total == $count_max) {
					
					$this->is_done_template_process = true;
					
					$arr_result['count'] = $count_max;
				}
			}
			
			SiteStartVars::startSession();
			
			if ($this->has_feedback_template_process || $this->is_done_template_process) {
				
				$html = $this->createProcessTemplateStoreCheck($arr_result);
			}
		}
		
		return $html;
	}
	
	protected function getConversionSocket() {
		
		$arr_socket = cms_details::isWebServiceActiveUser($_SESSION['USER_ID']);
		
		if (!$arr_socket) {
			return false;
		}
		
		$socket = new WebSocketClient('127.0.0.1', $arr_socket['port_local']);
		
		$passkey = $arr_socket['passkey'];
				
		$socket->closed = function() {
			error(getLabel('msg_socket_client_disconnect'));
		};
		$socket->opened = function() use (&$socket, $passkey) {
			
			$socket->send(value2JSON([
				'arr_tasks' => [
					WebServiceTaskIngestSource::$name => ['arr_options' => ['passkey' => $passkey]]
				]
			]));
		};

		$socket->open();
		
		$socket->sendHandshake('owner');

		return $socket;
	}
	
	public static function css() {
	
		$return = parent::css();
		
		$return .= '
		';

		return $return;
	}
	
	public static function js() {
		
		$return = parent::js();

		$return .= "
			SCRIPTER.dynamic('.ingest-source.template-check', function(elm_scripter) {
				
				var elm_reset = elm_scripter.find('[id^=y\\\:data_ingest\\\:reset_process-]');
				
				COMMANDS.setTarget(elm_reset, elm_scripter);
				COMMANDS.setOptions(elm_reset, {html: 'replace'});
				
				if (elm_scripter[0].dataset.webservice) {
					
					const elm_form = elm_scripter.closest('form');
					
					elm_form.on('command', function(e) {
						
						const is_discard = (e.target.getAttribute('name') == 'do_discard');
						const is_active = INGEST_SOURCE.isActive();
						const do_abort = (!is_active && !is_discard);
											
						COMMANDS.setAbort(elm_form, do_abort);

						if (!do_abort) {
							return;
						}
						
						var popup = new MessagePopup(elm_form);
						popup.addButtonDefault();
						
						ASSETS.getLabels(elm_form,
							['msg_ingest_no_webservice_client'],
							function(data) {
								popup.setMessage(data.msg_ingest_no_webservice_client);
							}
						);
					});
				}
			});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		parent::commands($method, $id, $value);
	
		// INTERACT
		
		if ($method == "reset_process") {

			$type_id = StoreType::getSystemTypeID('ingestion');
			
			if (!data_entry::checkClearanceType($type_id) || !custom_projects::checkAccessType(StoreCustomProject::ACCESS_PURPOSE_EDIT, $type_id)) {
				return;
			}
			
			$arr_id = explode('_', $id);
			$object_id = $arr_id[0];
			$source_id = $arr_id[1];
		
			static::clearTemplateState($object_id);
			
			$arr_template = static::getTemplate($object_id);
			
			$this->html = $this->createCheckTemplate($arr_template, $source_id);
			$this->msg = true;
		}
	}
	
	public static function getTemplate($object_id) {
		
		$system_type_id = StoreType::getSystemTypeID('ingestion');
		$arr_object_set = data_entry::getTypeObjectSet($system_type_id, $object_id);
		
		$arr_object_definition = $arr_object_set['object_definitions'][StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module')];
		$arr_template = $arr_object_definition['object_definition_value'];
		$arr_template = ($arr_template ? json_decode($arr_template, true) : []);
		
		$arr_object_definition = $arr_object_set['object_definitions'][StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'state')];
		$num_state = $arr_object_definition['object_definition_value'];
		
		$arr_template['identifier'] = $object_id;
		$arr_template['is_mode_query'] = ($arr_template['pointers']['query_object_value'] || $arr_template['pointers']['query_type_object_value'] ? true : false);
		
		$path_file = ($arr_template['is_mode_query'] ? Settings::get('path_temporary') : DIR_HOME_TYPE_INGEST).'ingest_'.$object_id.'.json';
		
		$arr_template['state']['source'] = $path_file;
		
		$arr_template['state']['pointer_state'] = $num_state;

		return $arr_template;
	}
	
	protected static function updateTemplateState($object_id, $arr_update = []) {
				
		$system_type_id = StoreType::getSystemTypeID('ingestion');
											
		if ($arr_update['object_ids'] !== null) {
			
			$num_status = 0; // Done
			switch ($arr_update['status']) {
				case 'pending':
					$num_status = 1;
					break;
				case 'clear':
					$num_status = 2;
					break;
			}
				
			$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
			StoreTypeObjects::updateModuleObjectTypeObjects($system_object_description_id, $object_id, $arr_update['object_ids'], $num_status);
		}
		
		if ($arr_update['source'] !== null) {	
			fileStore::deleteFile($arr_update['source']);
		}
				
		if ($arr_update['pointer_state'] !== null) {	
			
			$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'state');
			
			$arr_object_definitions = [
				$system_object_description_id => [
					'object_description_id' => $system_object_description_id,
					'object_definition_value' => $arr_update['pointer_state']
				]
			];
			
			$storage = new StoreTypeObjects($system_type_id, $object_id, $_SESSION['USER_ID']);
			$storage->setMode(StoreTypeObjects::MODE_UPDATE, false);
			$storage->setVersioning(false);
			$storage->store([], $arr_object_definitions, []);
			$storage->save();
			$storage->commit(true);
		}
	}
	
	protected static function clearTemplateState($object_id) {
		
		$arr_template = static::getTemplate($object_id);
		
		static::updateTemplateState($object_id, ['pointer_state' => 0, 'source' => $arr_template['state']['source'], 'object_ids' => false, 'status' => 'clear']);
	}
	
	protected static function getModuleObjectTypeObjects($object_id, $type_id, $arr_filter = false, $num_status = 0, $do_exclude = false, $num_limit = false) {
		
		$system_type_id = StoreType::getSystemTypeID('ingestion');
		$system_object_description_id = StoreType::getSystemTypeObjectDescriptionID($system_type_id, 'module');
	
		$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
		$filter->setFilter(['module_object_objects' => ['object_id' => $object_id, 'object_description_id' => $system_object_description_id, 'exclude' => $do_exclude, 'status' => $num_status]]);		
		if ($arr_filter) {
			$filter->setFilter($arr_filter);
		}
		if ($num_limit) {
			$filter->setLimit($num_limit);
		}
		
		$arr_object_ids = $filter->init();
		
		return $arr_object_ids;
	}

	public static function getSources() {
		
		$arr = StoreResourceExternal::getResources();
		
		return $arr;
	}
	
	public static function getPointerDataHeadings($source_id, $pointer_heading = false) {
		
		if (!$source_id) {
			
			$arr = [['id' => $pointer_heading, 'name' => $pointer_heading]];
		} else {
			
			$arr = [];
			
			$arr_resource = StoreResourceExternal::getResources($source_id);
			$external = new ResourceExternal($arr_resource);
			
			$arr_values = $external->getResponseValues(true);

			foreach ($arr_values as $name => $arr_value) {
				
				$arr[] = ['id' => $name, 'name'=> $name];
			}
		}

		return $arr;
	}
	
	public static function getPointerFilterHeadings($source_id, $pointer_heading = false) {
		
		return static::getPointerDataHeadings($source_id, $pointer_heading);
	}
	
	public static function getPointerQueryHeadings($source_id, $pointer_heading = false) {
		
		if (!$source_id) {
			
			$arr = [['id' => $pointer_heading, 'name' => $pointer_heading]];
		} else {

			$arr_resource = StoreResourceExternal::getResources($source_id);
			$external = new ResourceExternal($arr_resource);
			
			$arr = $external->getQueryVariablesFlat();
		}

		return $arr;
	}
}
