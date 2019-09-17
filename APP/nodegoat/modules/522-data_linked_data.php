<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES', DB::$database_home.'.def_nodegoat_linked_data_resources');
DB::setTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES', DB::$database_home.'.def_nodegoat_linked_data_resource_values');

class data_linked_data extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Linked Data';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'general' => [],
		'data_filter' => [],
		'data_view' => []
	];
		
	public function contents() {
	
		$return .= self::createAddResource();
					
		$return .= '<div>
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
			</div>';
		
		return $return;
	}
	
	private static function createAddResource() {
	
		$return .= '<form id="f:data_linked_data:add-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_linked_data').' '.getLabel('lbl_resource').'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	private function createResource($id = false) {
	
		if (is_numeric($id)) {
			$arr_resource = self::getLinkedDataResources($id);
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
		
		$arr_sorter_values = [];
					
		foreach (($arr_resource['response_values'] ?: ['']) as $name => $value) {
			
			$unique = uniqid('array_');
			$arr_sorter_values[] = ['value' => '<input type="text" name="response_values['.$unique.'][name]" value="'.htmlspecialchars(($name ?: '')).'" /><select name="response_values['.$unique.'][value]">'.cms_general::createDropdown([['id' => $value, 'name' => $value]]).'</select>'];
		}
			
		$return = '<div class="options">
					<fieldset>
						<ul>
							<li>
								<label>'.getLabel('lbl_name').'</label>
								<input type="text" name="name" value="'.htmlspecialchars($arr_resource['name']).'" />
							</li>
							<li>
								<label>'.getLabel('lbl_description').'</label>
								<textarea name="description" >'.$arr_resource['description'].'</textarea>
							</li>
							<li>
								<label>'.getLabel('lbl_protocol').'</label>
								<select name="protocol">'.cms_general::createDropdown(ExternalResource::getProtocols(), $arr_resource['protocol']).'</select>
							</li>
							<li>
								<label>'.getLabel('lbl_url').'</label>
								<input type="text" name="url" value="'.htmlspecialchars($arr_resource['url']).'" />
							</li>
							<li>
								<label>'.getLabel('lbl_url').' '.getLabel('lbl_options').'</label>
								<input type="text" name="url_options" value="'.htmlspecialchars($arr_resource['url_options']).'" />
							</li>
							<li>
								<label></label>
								<section class="info attention body">'.parseBody(getLabel('inf_external_resource_query', 'L', true)).'</section>
							</li>
							<li>
								<label>'.getLabel('lbl_query').'</label>
								<textarea name="query">'.htmlspecialchars($arr_resource['query']).'</textarea>
								<input type="button" value="test" id="y:data_linked_data:test_query-0" class="data add" />
							</li>
							<li>
								<label>'.getLabel('lbl_response').'</label>
								<textarea name="response"></textarea>
								<input type="button" value="use" id="y:data_linked_data:test_response-0" class="data add" />
							</li>
							<li>
								<label>URI '.getLabel('lbl_template').'</label>
								<input type="text" name="response_uri_template" value="'.htmlspecialchars($arr_resource['response_uri_template']).'" />
							</li>
							<li>
								<label>URI</label>
								<select name="response_uri">'.cms_general::createDropdown([['id' => $arr_resource['response_uri'], 'name' => $arr_resource['response_uri']]]).'</select>
							</li>
							<li>
								<label>'.getLabel('lbl_label').'</label>
								<select name="response_label">'.cms_general::createDropdown([['id' => $arr_resource['response_label'], 'name' => $arr_resource['response_label']]]).'</select>
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
						
		$return .= '</div>
	
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_linked_data').' '.getLabel('lbl_resource').'" /><input type="submit" name="discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
			
		$this->validate = ['name' => 'required'];
		
		return $return;
	}
	
	public static function css() {
	
	
		$return = '.data_linked_data fieldset > ul > li > label:first-child + textarea { height: 300px; width: 600px; }
			.data_linked_data fieldset > ul > li > label:first-child + textarea[name=description] { height: 100px; width: 450px; }
			.data_linked_data fieldset > ul > li > label:first-child + textarea[name=response] { height: 150px; white-space: pre; }
			.data_linked_data fieldset > ul > li > label:first-child + textarea[name=response].active { height: 500px; }
			.data_linked_data fieldset > ul > li > label:first-child + section { margin: 0px; width: 600px; }';

		return $return;
	}
	
	public static function js() {

		$return = "SCRIPTER.static('.data_linked_data', function(elm_scripter) {
		
			elm_scripter.on('ajaxloaded', function(e) {
				
				if (!getElement(e.detail.elm)) {
					return;
				}
				
				e.detail.elm.find('[name=protocol]').trigger('change');
			}).on('click', '[id^=d\\\:data_linked_data\\\:data-] .edit', function() {
				$(this).quickCommand($('.data_linked_data').children('form'), {html: 'replace'});
			}).on('change', '[name=protocol]', function() {
				var cur = $(this);
				var value = cur.val();
				var elm_target = cur.closest('ul').find('[name=url_options]').closest('li').nextAll();
				elm_target.toggleClass('hide', (value == 'static'));
			}).on('click', '[id^=y\\\:data_linked_data\\\:test]', function() {
				var cur = $(this),
				value = {};
				cur.closest('ul').find('input[type=text], textarea, select').each(function(){
					value[$(this).attr('name')] = $(this).val();
				});
				cur.data('value', value).quickCommand(function(data) {
				
					if (cur.is('[id=y\\\:data_linked_data\\\:test_query-0]')) {
						
						// Make the output pretty
						var str = JSON.parse(data);
						str = JSON.stringify(str, null, \"\t\");
					
						cur.closest('ul').find('textarea[name=response]').addClass('active').val(str);
					} else {
					
						var elm_select = data;
					
						var elm_target = cur.closest('ul');
						elm_target.find('select[name=response_uri], select[name=response_label], select[name^=response_values]').html(elm_select.clone());
						
						var obj_sorter = elm_target.find('select[name^=response_values]').closest('.sorter')[0].sorter;
						
						var elm_sorter_source = $(obj_sorter.getSource());
						elm_sorter_source.find('select[name^=response_values]').html(elm_select.clone());
						
						obj_sorter.setSource(elm_sorter_source[0].outerHTML);
					}
				});
			}).on('click', 'form fieldset .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', 'form fieldset .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
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
		
		if ($method == "test_query") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			if (!$value['query']) {
				return;
			}
			
			$arr_resource = [
				'name' => $value['name'],
				'protocol' => $value['protocol'],
				'url' => $value['url'],
				'url_options' => $value['url_options'],
				'query' => $value['query']
			];
			
			$external = new ExternalResource($arr_resource);
			$external->setLimit(1);
			
			$arr = $external->request();
			
			$this->html = $arr['raw'];
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
				
				$arr = json_decode($value['response'], true);
				
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
				
				Labels::setVariable('name', $value['name']);
				error(getLabel('msg_external_resource_error_parse'), TROUBLE_ERROR, LOG_CLIENT, false, $e); // Make notice
			}
			
			$arr_flat = arrFlattenKeysRecursive($arr);
			$arr_keys = [];
			
			foreach ($arr_flat as $key => $value) {
				
				$value = json_encode($value, JSON_FORCE_OBJECT);
				$arr_keys[] = ['id' => $value, 'name' => $value];
			}
			
			$this->html = cms_general::createDropdown($arr_keys);
		}
		
		if ($method == "data") {
			
			$arr_sql_columns = ['ldr.name', 'ldr.protocol'];
			$arr_sql_columns_as = ['ldr.name', 'ldr.protocol', 'ldr.id'];
						
			$sql_table = DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES').' ldr';

			$sql_index = 'ldr.id';
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index);

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
		
		// QUERY
		
		if (($method == "insert" || $method == "update") && $_POST['discard']) {
							
			$this->html = self::createAddResource();
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
			
			$arr = $_POST;
			
			$arr_response_values = $arr['response_values'];
			$arr['response_values'] = [];
			
			foreach ((array)$arr_response_values as $arr_value) {
				
				$arr['response_values'][$arr_value['name']] = $arr_value['value'];
			}
			
			self::handleLinkedDataResource((int)$id, $arr);
			
			$this->html = self::createAddResource();
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			self::delLinkedDataResource($id);
								
			$this->msg = true;
		}
	}
	
	private static function handleLinkedDataResource($id = false, $arr) {

		if (!$id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')."
				(name, description, protocol, url, url_options, query, response_uri, response_uri_template, response_label) 
					VALUES
				(
					'".DBFunctions::strEscape($arr['name'])."',
					'".DBFunctions::strEscape($arr['description'])."',
					'".DBFunctions::strEscape($arr['protocol'])."',
					'".DBFunctions::strEscape($arr['url'])."',
					'".DBFunctions::strEscape($arr['url_options'])."',
					'".DBFunctions::strEscape($arr['query'])."',
					'".DBFunctions::strEscape($arr['response_uri'])."',
					'".DBFunctions::strEscape($arr['response_uri_template'])."',
					'".DBFunctions::strEscape($arr['response_label'])."'
				)
			");
			
			$id = DB::lastInsertID();
		} else {
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')." SET
					name = '".DBFunctions::strEscape($arr['name'])."',
					description = '".DBFunctions::strEscape($arr['description'])."',
					protocol = '".DBFunctions::strEscape($arr['protocol'])."',
					url = '".DBFunctions::strEscape($arr['url'])."',
					url_options = '".DBFunctions::strEscape($arr['url_options'])."',
					query = '".DBFunctions::strEscape($arr['query'])."',
					response_uri = '".DBFunctions::strEscape($arr['response_uri'])."',
					response_uri_template = '".DBFunctions::strEscape($arr['response_uri_template'])."',
					response_label = '".DBFunctions::strEscape($arr['response_label'])."'
				WHERE id = ".(int)$id."
			");
		}
		
		$arr_identifiers = [];
		$count = 0;
		
		foreach ((array)$arr['response_values'] as $name => $value) {
			
			if (!$name || !$value || $arr_identifiers[$name]) {
				continue;
			}
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES')."
				(resource_id, name, value, sort)
					VALUES
				(".(int)$id.", '".DBFunctions::strEscape($name)."', '".DBFunctions::strEscape($value)."', ".$count.")
				".DBFunctions::onConflict('resource_id, name', ['value', 'sort'])."
			");
			
			$arr_identifiers[$name] = DBFunctions::strEscape($name);
			
			$count++;
		}
		
		$res = DB::query("DELETE FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES')."
			WHERE resource_id = ".(int)$id."
				".($arr_identifiers ? "AND name NOT IN('".implode("','", $arr_identifiers)."')" : "")."
		");
	}
	
	public static function getLinkedDataResources($id = false) {
			
		if ($id) {
			
			$str_identifier = 'resource_'.$id;
		
			$cache = self::getCache($str_identifier);
			if ($cache) {
				return $cache;
			}
		}
		
		$arr = [];
		
		$res = DB::query("SELECT nodegoat_ldr.*, nodegoat_ldrv.name AS response_values_name, nodegoat_ldrv.value AS response_values_value
				FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')." nodegoat_ldr
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES')." nodegoat_ldrv ON (nodegoat_ldrv.resource_id = nodegoat_ldr.id)
			WHERE TRUE
				".($id ? "AND nodegoat_ldr.id = ".(int)$id."" : "")."
			ORDER BY nodegoat_ldrv.sort
		");
				 
		while ($row = $res->fetchAssoc()) {
			
			$cur_id = $row['id'];
			
			if (!$arr[$cur_id]) {
				
				$arr[$cur_id] = $row;
				$arr[$cur_id]['response_values'] = [];
				unset($arr[$cur_id]['response_values_name'], $arr[$cur_id]['response_values_value']);
			}
			
			if ($row['response_values_name']) {
				
				$arr[$cur_id]['response_values'][$row['response_values_name']] = $row['response_values_value'];
			}
		}	
		
		if ($id) {
			
			$arr = current($arr);
			self::setCache($str_identifier, $arr);
		}

		return $arr;
	}
	
	public static function delLinkedDataResource($resource_id) {
					
		$res = DB::queryMulti("
			".DBFunctions::deleteWith(
				DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCE_VALUES'), 'nodegoat_ldrv', 'resource_id',
				"JOIN ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')." nodegoat_ldr ON (
					nodegoat_ldr.id = nodegoat_ldrv.resource_id
						AND nodegoat_ldr.id = ".(int)$resource_id."
				)"
			).";
			
			DELETE FROM ".DB::getTable('DEF_NODEGOAT_LINKED_DATA_RESOURCES')."
				WHERE id = ".(int)$resource_id.";
		");
	}
}
