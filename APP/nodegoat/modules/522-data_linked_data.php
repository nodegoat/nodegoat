<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_linked_data extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Linked Data';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'general' => [],
		'data_filter' => [],
		'data_view' => [],
		'ingest_source' => ['*' => ['module' => 'data_ingest']]
	];
		
	public function contents() {
		
		$return = '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_resources').'</a></li>
				<li><a href="#">'.getLabel('lbl_conversions').'</a></li>
			</ul>
			<div>
	
				'.$this->createAddResource().'

				<table class="display" id="d:data_linked_data:data-0">
					<thead>
						<tr>			
							<th class="max" data-sort="asc-0">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_protocol').'</th>
							<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="3" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
				
			</div>
			
			<div>
			
				'.$this->createAddResourceConversion().'

				<table class="display" id="d:data_linked_data:data_conversions-0">
					<thead>
						<tr>			
							<th class="max" data-sort="asc-0">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_output').'</th>
							<th class="disable-sort"></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="3" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
			
			</div>
			
		</div>';
		
		return $return;
	}
	
	private function createAddResource() {
	
		$return = '<form id="f:data_linked_data:add-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_linked_data').' '.getLabel('lbl_resource').'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	private function createAddResourceConversion() {
	
		$return = '<form id="f:data_linked_data:add_conversion-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_linked_data').' '.getLabel('lbl_conversion').'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	private function createResource($id = false) {
	
		if (is_numeric($id)) {
			$arr_resource = StoreResourceExternal::getResources($id);
		}

		$id = (int)$id;
		
		Labels::setVariable('example', 'SELECT ?subject ?label ?abstract<br/>'
			.'WHERE {<br/>'
			.'<span class="tab"></span>?subject rdfs:label ?label .<br/>'
	
			.'<span class="tab"></span>OPTIONAL {<br/>'
			.'<span class="tab"></span><span class="tab"></span>?subject dbo:abstract ?abstract .<br/>'
			.'<span class="tab"></span>}<br/>'
			
			.'<span class="tab"></span><strong>[query=name]</strong><br/>'
			.'<span class="tab"></span><span class="tab"></span>FILTER regex(?label, "<strong>[variable]</strong>Hans Filbinger<strong>[/variable]</strong>", "i") .<br/>'
			.'<span class="tab"></span><strong>[/query]</strong><br/>'
			
			.'<span class="tab"></span><strong>[query=exact]</strong><br/>'
			.'<span class="tab"></span><span class="tab"></span>?subject rdfs:label "<strong>[variable=subject]</strong>Hans Filbinger<strong>[/variable]</strong>"@en .<br/>'
			.'<span class="tab"></span><strong>[/query]</strong><br/>'
			
			.'}<br/>'
			.'OFFSET <strong>[[offset]]</strong> LIMIT <strong>[[limit]]</strong>'
		);
		
		$arr_sorter_headers = [];
		
		foreach (($arr_resource['url_headers'] ?: ['']) as $key => $value) {
						
			$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
			
			$arr_sorter_headers[] = ['value' => '<input type="text" name="url_headers['.$unique.'][key]" value="'.strEscapeHTML(($key ?: '')).'" /><label>:</label><input type="text" name="url_headers['.$unique.'][value]" value="'.strEscapeHTML($value).'" />'];
		}
		
		$arr_sorter_values = [];
		
		$arr_conversions = StoreResourceExternal::getConversions();
					
		foreach (($arr_resource['response_values'] ?: [[]]) as $name => $arr_value) {
			
			$html_select_output_identifier = '';
			if ($arr_value['conversion_id']) {
				$html_select_output_identifier = cms_general::createDropdown($arr_conversions[$arr_value['conversion_id']]['output_identifiers'], $arr_value['conversion_output_identifier'], false);
			}
			
			$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
			
			$arr_sorter_values[] = ['value' => [
				'<input type="text" name="response_values['.$unique.'][name]" value="'.strEscapeHTML(($name ?: '')).'" /><input type="text" name="response_values['.$unique.'][value]" placeholder="{}" value="'.strEscapeHTML($arr_value['value']).'" />',
				'<fieldset><ul>
					<li>
						<div>'
							.'<select name="response_values['.$unique.'][conversion_id]" id="y:data_linked_data:selector_conversion_output_identifiers-0">'.cms_general::createDropdown($arr_conversions, $arr_value['conversion_id'], true).'</select>'
							.'<select name="response_values['.$unique.'][conversion_output_identifier]">'.$html_select_output_identifier.'</select>'
						.'</div>'
					.'</li>
				</ul></fieldset>'
			]];
		}
			
		$return = '<h1>'.($id ? getLabel('lbl_resource').': '.strEscapeHTML($arr_resource['name']) : getLabel('lbl_resource')).'</h1>
		
		<div class="options">
			<fieldset>
				<ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_resource['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_description').'</label>
						<div><textarea name="description" >'.$arr_resource['description'].'</textarea></div>
					</li>
					<li>
						<label>'.getLabel('lbl_protocol').'</label>
						<div><select name="protocol">'.cms_general::createDropdown(ResourceExternal::getProtocols(), $arr_resource['protocol']).'</select></div>
					</li>
					<li>
						<label>'.getLabel('lbl_url').'</label>
						<div><input type="text" name="url" value="'.strEscapeHTML($arr_resource['url']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_url').' '.getLabel('lbl_options').'</label>
						<div><input type="text" name="url_options" value="'.strEscapeHTML($arr_resource['url_options']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_url').' '.getLabel('lbl_headers').'</label><span>
							<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'
						.'</span>
					</li><li>
						<label></label>
						'.cms_general::createSorter($arr_sorter_headers, true).'
					</li>
					<li>
						<label></label>
						<section class="info attention body">'.parseBody(getLabel('inf_external_resource_query', 'L', true)).'</section>
					</li>
					<li>
						<label>'.getLabel('lbl_query').'</label>
						<div>'
							.'<textarea name="query">'.strEscapeHTML($arr_resource['query']).'</textarea>'
							.'<input type="button" value="test" id="y:data_linked_data:test_query-0" class="data add" />'
						.'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_response').'</label>
						<div>'
							.'<textarea name="response"></textarea>'
							.'<input type="button" value="use" id="y:data_linked_data:test_response-0" class="data add" />'
						.'</div>
					</li>
					<li>
						<label>URI '.getLabel('lbl_template').'</label>
						<div><input type="text" name="response_uri_template" placeholder="http://uri.host/[[identifier]]" value="'.strEscapeHTML($arr_resource['response_uri_template']).'" /></div>
					</li>
					<li>
						<label>URI</label>
						<div><input type="text" name="response_uri_value" placeholder="{}" value="'.strEscapeHTML($arr_resource['response_uri_value']).'" /></div>
					</li>
					<li>
						<label>URI '.getLabel('lbl_conversion').'</label>
						<div>';
							
							$html_select_output_identifier = '';
							if ($arr_resource['response_uri_conversion_id']) {
								$html_select_output_identifier = cms_general::createDropdown($arr_conversions[$arr_resource['response_uri_conversion_id']]['output_identifiers'], $arr_resource['response_uri_conversion_output_identifier'], false);
							}
						
							$return .= '<select name="response_uri_conversion_id" id="y:data_linked_data:selector_conversion_output_identifiers-0">'.cms_general::createDropdown($arr_conversions, $arr_resource['response_uri_conversion_id'], true).'</select>'
							.'<select name="response_uri_conversion_output_identifier">'.$html_select_output_identifier.'</select>';
						
						$return .= '</div>
					</li>
					<li>
						<label>'.getLabel('lbl_label').'</label>
						<div><input type="text" name="response_label_value" placeholder="{}" value="'.strEscapeHTML($arr_resource['response_label_value']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_label').' '.getLabel('lbl_conversion').'</label>
						<div>';
							
							$html_select_output_identifier = '';
							if ($arr_resource['response_label_conversion_id']) {
								$html_select_output_identifier = cms_general::createDropdown($arr_conversions[$arr_resource['response_label_conversion_id']]['output_identifiers'], $arr_resource['response_label_conversion_output_identifier'], false);
							}
						
							$return .= '<select name="response_label_conversion_id" id="y:data_linked_data:selector_conversion_output_identifiers-0">'.cms_general::createDropdown($arr_conversions, $arr_resource['response_label_conversion_id'], true).'</select>'
							.'<select name="response_label_conversion_output_identifier">'.$html_select_output_identifier.'</select>';
						
						$return .= '</div>
					</li>
					<li>
						<label>'.getLabel('lbl_values').'</label><span>
							<input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" />'
						.'</span>
					</li><li>
						<label></label>
						'.cms_general::createSorter($arr_sorter_values, true).'
					</li>
				</ul>
			</fieldset>
		</div>';
						
		$return .= '<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_linked_data').' '.getLabel('lbl_resource').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
			
		$this->validate = ['name' => 'required'];
		
		return $return;
	}
	
	private function createResourceConversion($id = false) {
	
		if (is_numeric($id)) {
			$arr_conversion = StoreResourceExternal::getConversions($id);
		}

		$id = (int)$id;
		
		$str_script_placeholder = 'var OUTPUT = INPUT;';
			
		$return = '<h1>'.($id ? getLabel('lbl_conversion').': '.strEscapeHTML($arr_conversion['name']) : getLabel('lbl_conversion')).'</h1>
		
		<div class="options">
			<fieldset>
				<ul>
					<li>
						<label>'.getLabel('lbl_name').'</label>
						<div><input type="text" name="name" value="'.strEscapeHTML($arr_conversion['name']).'" /></div>
					</li>
					<li>
						<label>'.getLabel('lbl_description').'</label>
						<div><textarea name="description" >'.$arr_conversion['description'].'</textarea></div>
					</li>
					<li>
						<label></label>
						<section class="info attention body">'.parseBody(getLabel('inf_external_resource_script', 'L', true)).'</section>
					</li>
					<li>
						<label>INPUT =</label>
						<div>'
							.'<textarea name="input_placeholder">'.($arr_conversion['input_placeholder'] ?: '').'</textarea>'
						.'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_script').'</label>
						<div>'
							.'<textarea name="script" placeholder="'.$str_script_placeholder.'">'.strEscapeHTML($arr_conversion['script']).'</textarea>'
							.'<input type="button" value="test" id="y:data_linked_data:test_script-0" class="data add" />'
						.'</div>
					</li>
					<li>
						<label>OUTPUT =</label>
						<div>'
							.'<textarea name="output_placeholder">'.($id ? strEscapeHTML(value2JSON($arr_conversion['output_placeholder'], JSON_PRETTY_PRINT)) : '').'</textarea>'
						.'</div>
					</li>
				</ul>
			</fieldset>
		</div>';
						
		$return .= '<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_linked_data').' '.getLabel('lbl_conversion').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
			
		$this->validate = ['name' => 'required', 'script' => 'required', 'output_placeholder' => 'required'];
		
		return $return;
	}
	
	public static function css() {
	
	
		$return = '
			.data_linked_data fieldset > ul > li > label:first-child + div textarea { height: 300px; width: 600px; }
			.data_linked_data fieldset > ul > li > label:first-child + div textarea[name=description] { height: 100px; width: 450px; }
			.data_linked_data fieldset > ul > li > label:first-child + div textarea[name=response] { height: 150px; white-space: pre; }
			.data_linked_data fieldset > ul > li > label:first-child + div textarea[name=response].active { height: 500px; }
			.data_linked_data fieldset > ul > li > label:first-child + div input[name=url],
			.data_linked_data fieldset > ul > li > label:first-child + div input[name=url_options],
			.data_linked_data fieldset > ul > li > label:first-child + div input[name=response_uri_template] { width: 450px; }
			.data_linked_data fieldset > ul > li > label:first-child + ul input[name^=url_headers][name$="[key]"] { width: 200px; }
			.data_linked_data fieldset > ul > li > label:first-child + ul input[name^=url_headers][name$="[value]"] { width: 300px; }
			.data_linked_data fieldset > ul > li > label:first-child + div input[name=response_uri_value],
			.data_linked_data fieldset > ul > li > label:first-child + div input[name=response_label_value],
			.data_linked_data fieldset > ul > li > label:first-child + ul input[name^=response_values][name$="[value]"] { width: 600px; }
			.data_linked_data fieldset > ul > li > label:first-child + div input[name=response_uri_value] + select,
			.data_linked_data fieldset > ul > li > label:first-child + div input[name=response_label_value] + select,
			.data_linked_data fieldset > ul > li > label:first-child + ul input[name^=response_values][name$="[value]"] + select { max-width: 500px; }
			.data_linked_data fieldset > ul > li > label:first-child + div select[name$="conversion_output_identifier"]:empty,
			.data_linked_data fieldset > ul > li > label:first-child + ul select[name$="[conversion_output_identifier]"]:empty { display: none; }
			.data_linked_data fieldset > ul > li > label:first-child + section { width: 600px; }
			
			.data_linked_data fieldset > ul > li > label:first-child + div textarea[name=input_placeholder] { height: 120px; white-space: pre; }
			.data_linked_data fieldset > ul > li > label:first-child + div textarea[name=output_placeholder] { height: 120px; white-space: pre; }';

		return $return;
	}
	
	public static function js() {

		$return = "SCRIPTER.static('.data_linked_data', function(elm_scripter) {
			
			elm_scripter.on('click', '[id^=d\\\:data_linked_data\\\:data] .edit', function() {
				var cur = $(this);
				COMMANDS.quickCommand(cur, cur.closest('.datatable').prev('form'), {html: 'replace'});
			});
		});
		
		SCRIPTER.static('.data_linked_data', 'ingest_source');
		
		SCRIPTER.dynamic('[id^=f\\\:data_linked_data\\\:insert-], [id^=f\\\:data_linked_data\\\:update-]', function(elm_scripter) {
		
			var func_select_response = function(elm_input, mode) {
				
				var value = elm_input.value;
				var elms_target = $(elm_input).nextAll();
				
				elms_target[0].value = value; // Select
				if (mode == 'edit' || (!mode && (elms_target[0].value == '' && value))) {
					elm_input.classList.remove('hide');
					elms_target[0].classList.add('hide');
					elms_target[1].classList.add('hide'); // Button 'edit'
					elms_target[2].classList.remove('hide'); // Button 'select'
				} else { // Mode 'select'
					elm_input.classList.add('hide');
					elms_target[0].classList.remove('hide');
					elms_target[1].classList.remove('hide'); // Button 'edit'
					elms_target[2].classList.add('hide'); // Button 'select'
				}
			};
		
			elm_scripter.on('ajaxloaded scripter', function(e) {
				
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				e.detail.elm.find('[name=protocol]').trigger('change');
			}).on('change', '[name=protocol]', function() {
				var cur = $(this);
				var value = cur.val();
				var elm_target = cur.closest('ul').find('[name=url_options]').closest('li').nextAll();
				elm_target.toggleClass('hide', (value == 'static'));
			}).on('click', '[id^=y\\\:data_linked_data\\\:test_query]', function() {
				
				var cur = $(this);
				COMMANDS.setData(this, serializeArrayByName(elm_scripter));
				COMMANDS.quickCommand(this, function(data) {
					
					let str = data;

					if (str) { // Make the output pretty
					
						str = JSON.parse(data);
						str = JSON.stringify(str, null, \"\t\");
					}
					
					cur.closest('ul').find('textarea[name=response]').addClass('active').val(str);
				});
			}).on('click', '[id^=y\\\:data_linked_data\\\:test_response]', function() {
				
				var cur = $(this);
				COMMANDS.setData(this, serializeArrayByName(elm_scripter));
				COMMANDS.quickCommand(this, function(data) {
					
					if (!data) {
						return;
					}
					
					var elms_select = data;
					var elm_target = cur.closest('ul');
					
					runElementSelectorFunction(elm_target, 'input[name=response_uri_value], input[name=response_label_value], input[name^=response_values][name$=\"[value]\"]', function(elm_found) {
						
						var elms_select_new = elms_select.clone();
						var elm_input = $(elm_found);
						elm_input.nextAll().remove();
						elm_input.after(elms_select_new);
						
						func_select_response(elm_found, false);
					});
					
					var obj_sorter = elm_target.find('input[name^=response_values][name$=\"[value]\"]').closest('.sorter')[0].sorter;
					
					var elms_select_new = elms_select.clone();
					var elm_sorter_source = $(obj_sorter.getSource());
					var elm_input = elm_sorter_source.find('input[name^=response_values][name$=\"[value]\"]');
					elm_input.nextAll().remove();
					elm_input.after(elms_select_new);
					elm_input[0].value = '';
					
					func_select_response(elm_input[0], false);					
						
					obj_sorter.setSource(elm_sorter_source[0].outerHTML);
				});
			}).on('click', 'fieldset select + input[type=button]', function() {
				var elm_input = $(this).prev().prev();
				func_select_response(elm_input[0], 'edit');
			}).on('click', 'fieldset select + input[type=button] + input[type=button]', function() {
				var elm_input = $(this).prev().prev().prev();
				func_select_response(elm_input[0], 'select');
			}).on('change', 'input[name=response_uri_value] + select, input[name=response_label_value] + select, input[name^=response_values][name$=\"[value]\"] + select', function() {
				var elm_target = $(this).prev('input');
				elm_target[0].value = this.value;
			}).on('click', 'fieldset .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', 'fieldset .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('change', '[id=y\\\:data_linked_data\\\:selector_conversion_output_identifiers-0]', function() {
			
				var elm_target = $(this).next('select');
				
				if (!this.value) {
					elm_target[0].innerHTML = '';
				} else {
					COMMANDS.quickCommand(this, elm_target);
				}
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_linked_data\\\:insert_conversion-], [id^=f\\\:data_linked_data\\\:update_conversion-]', function(elm_scripter) {
			
			elm_scripter.on('click', '[id^=y\\\:data_linked_data\\\:test_script]', function() {
			
				var cur = $(this);
				var str_script = elm_scripter.find('textarea[name=script]')[0].value;
				var elm_input = elm_scripter.find('textarea[name=input_placeholder]');
				var elm_output = elm_scripter.find('textarea[name=output_placeholder]');
			
				var func = `function() {
		
					var func_convert = function(INPUT) {
						
						`+str_script+`
						
						return OUTPUT;						
					};
					
					self.onmessage = function(event) {
													
						var OUTPUT = func_convert(event.data.INPUT);
						
						self.postMessage({OUTPUT: OUTPUT});
					};
				}`;

				var worker = ASSETS.createWorker(func);
				
				var INPUT = str2TypedValue(elm_input[0].value);
								
				worker.postMessage({INPUT: INPUT});
				
				worker.addEventListener('message', function(event) {
					
					var OUTPUT = event.data.OUTPUT;
					
					elm_output[0].value = JSON.stringify(OUTPUT);
					
					worker.terminate();
				});
				
				worker.addEventListener('error', function(event) {
					
					elm_output[0].value = 'ERROR: '+EOL_1100CC+event.message;
					
					worker.terminate();
				});
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
	
		if ($method == "add") {
			
			$this->html = '<form id="f:data_linked_data:insert-0">'.$this->createResource().'</form>';
		}
		
		if ($method == "edit") {
			
			$this->html = '<form id="f:data_linked_data:update-'.$id.'">'.$this->createResource($id).'</form>';
		}
		
		if ($method == "add_conversion") {
			
			$this->html = '<form id="f:data_linked_data:insert_conversion-0">'.$this->createResourceConversion().'</form>';
		}
		
		if ($method == "edit_conversion") {
			
			$this->html = '<form id="f:data_linked_data:update_conversion-'.$id.'">'.$this->createResourceConversion($id).'</form>';
		}
		
		if ($method == "selector_conversion_output_identifiers") {
			
			$arr_conversion = StoreResourceExternal::getConversions((int)$value);
									
			$this->html = cms_general::createDropdown($arr_conversion['output_identifiers'], false, false);
		}
		
		if ($method == "test_query") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			if (!$value['query']) {
				return;
			}
			
			$arr_resource = static::parseResourceExternal([
				'name' => $value['name'],
				'protocol' => $value['protocol'],
				'url' => $value['url'],
				'url_options' => $value['url_options'],
				'url_headers' => $value['url_headers'],
				'query' => $value['query']
			]);
			
			$external = new ResourceExternal($arr_resource);
			$external->setLimit(1);
			
			$external->request();
			$str_result = $external->getResultRaw();
			
			$this->html = $str_result;
		}
		
		if ($method == "test_response") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			if (!$value['response']) {
				return;
			}
			
			try {
				
				$arr = JSON2Value($value['response']);
				
				$func_parse_arr = function(&$arr) use (&$func_parse_arr) { // Convert first single array occurences (['0']) to array notation ('[]') for initial iteration.
				
					foreach ($arr as $k => &$v) {
						
						if ($k == '0' && !$arr['1']) {
							
							$arr['[]'] = $arr['0'];
							unset($arr['0']);
							continue;
						}
						if (is_array($v)) { // recursive
							$func_parse_arr($v);
						}
					}
				};
				$func_parse_arr($arr);
			} catch (Exception $e) {
				
				Labels::setVariable('resource_name', $value['name']);
				error(getLabel('msg_external_resource_error_parse'), TROUBLE_ERROR, LOG_CLIENT, false, $e); // Make notice
			}
			
			$arr_flat = arrFlattenKeysRecursive($arr);
			$arr_keys = [];
			
			foreach ($arr_flat as $key => $value) {
				
				$value = value2JSON($value, JSON_FORCE_OBJECT);
				$arr_keys[] = ['id' => $value, 'name' => $value];
			}
			
			$this->html = '<select title="'.getLabel('inf_linked_data_copy_response_value').'">'.cms_general::createDropdown($arr_keys, false, true).'</select>'
				.'<input type="button" class="data edit" value="edit" />'
				.'<input type="button" class="data neutral" value="select" />';
		}
		
		if ($method == "data") {
			
			$arr_sql_columns = ['ldr.name', 'ldr.protocol'];
			$arr_sql_columns_as = ['ldr.name', 'ldr.protocol', 'ldr.id'];
						
			$sql_table = DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES').' ldr';

			$sql_index = 'ldr.id';

			$sql_where = '';
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:data_linked_data:id-'.$arr_row['id']."";
				$arr_data[] = $arr_row['name'];
				$arr_data[] = $arr_row['protocol'];
				$arr_data[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data msg del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
		
		if ($method == "data_conversions") {
			
			$arr_sql_columns = ['ldc.name', 'ldc.output_placeholder'];
			$arr_sql_columns_as = ['ldc.name', 'ldc.output_placeholder', 'ldc.id'];
						
			$sql_table = DB::getTable('DEF_NODEGOAT_LINKED_DATA_CONVERSIONS').' ldc';

			$sql_index = 'ldc.id';

			$sql_where = '';
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{

				$arr_data = [];
				
				$arr_data['id'] = 'x:data_linked_data:conversion_id-'.$arr_row['id']."";
				$arr_data[] = $arr_row['name'];
				$arr_data[] = $arr_row['output_placeholder'];
				$arr_data[] = '<input type="button" class="data edit edit_conversion" value="edit" /><input type="button" class="data msg del del_conversion" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
		
		// QUERY
		
		if (($method == "insert" || $method == "update") && $this->is_discard) {
							
			$this->html = $this->createAddResource();
			return;
		}
		
		if (($method == "insert_conversion" || $method == "update_conversion") && $this->is_discard) {
							
			$this->html = $this->createAddResourceConversion();
			return;
		}
				
		if ($method == "insert" || ($method == "update" && (int)$id)) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
		
			if (!$_POST['name']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr = static::parseResourceExternal($_POST);
			
			$store_resource = new StoreResourceExternal();
			$store_resource->storeResource((int)$id, $arr);
			
			$this->html = $this->createAddResource();
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "insert_conversion" || ($method == "update_conversion" && (int)$id)) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
		
			if (!$_POST['name'] || !$_POST['script'] || !$_POST['output_placeholder']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr = $_POST;
			
			$store_resource = new StoreResourceExternal();
			$store_resource->storeConversion((int)$id, $arr);
			
			$this->html = $this->createAddResourceConversion();
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			$store_resource = new StoreResourceExternal();
			$store_resource->delResource($id);
								
			$this->msg = true;
		}
		
		if ($method == "del_conversion" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			$store_resource = new StoreResourceExternal();
			$store_resource->delConversion($id);
								
			$this->msg = true;
		}
	}
	
	public static function parseResourceExternal($arr) {
		
		$arr_url_headers = (array)$arr['url_headers'];
		$arr['url_headers'] = [];
		
		foreach ($arr_url_headers as $key => $value) {
			
			if (is_array($value)) { // Form
							
				$key = trim($value['key']);
				$value = trim($value['value']);
			} else {
				
				$key = trim($key);
				$value = trim($value);
			}
				
			if (!$key) {
				continue;
			}
				
			$arr['url_headers'][$key] = $value;
		}
		
		$arr_response_values = (array)$arr['response_values'];
		$arr['response_values'] = [];
		
		foreach ($arr_response_values as $arr_value) {
			
			$arr['response_values'][$arr_value['name']] = $arr_value;
		}
		
		return $arr;
	}
}
