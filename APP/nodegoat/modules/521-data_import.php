<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_import extends ingest_source {

	public static function moduleProperties() {
		parent::moduleProperties();
		static::$label = 'Data Import';
	}
	
	protected $arr_access = [
		'data_entry' => [],
		'data_view' => [],
		'data_filter' => [],
		'data_pattern_pairs' => [],
		'ingest_source' => ['*' => ['module' => 'this']]
	];
	
	protected static $use_log = true;
	protected static $use_object_identifier_uri = false;
	protected static $use_filter_value = false;
	
	protected static $arr_labels = [
		'lbl_source' => 'lbl_import_source',
		'lbl_pointer_filter' => 'lbl_import_pointer',
		'lbl_pointer_data' => 'lbl_import_pointer',
		'inf_pointer_filter' => 'inf_import_pointer',
		'inf_pointer_data' => 'inf_import_pointer',
		'inf_pointer_map' => 'inf_import_pointer_map',
		'inf_template' => 'inf_import_template',
		'inf_pointer_link_object' => 'inf_import_pointer_link_object',
		'inf_template_process_select_source' => 'inf_import_template_process_select_source'
	];
		
	public function contents() {
		
		$arr_types = StoreType::getTypes(); 
		
		if (count((array)$arr_types) == 0) {
					
			return '<section class="info attention">
				'.getLabel('msg_no_types_domain').'
			</section>';
		}	
		
		$return .= '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_import_templates').'</a></li>
				<li><a href="#">CSV '.getLabel('lbl_files').'</a></li>
			</ul>
			<div>
			
				'.$this->createAddButtons('template').'
				
				<table class="display" id="d:data_import:data-template">
					<thead>
						<tr>			
							<th class="max">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_type').'</th>
							<th>CSV '.getLabel('lbl_file').'</th>
							<th title="'.getLabel('lbl_added').'" data-sort="desc-0"><span>A</span></th>
							<th class="disable-sort menu" id="x:data_import:id-template"><div class="hide-edit hide"><input type="button" class="data msg del" value="d" title="'.getLabel('lbl_delete').'" /></div><input type="button" class="data neutral" value="multi" /><input type="checkbox" class="multi all" value="" /></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="5" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div>
			
				'.$this->createAddButtons('file').'

				<table class="display" id="d:data_import:data-file">
					<thead>
						<tr>			
							<th class="max">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_rows').'</th>
							<th title="'.getLabel('lbl_added').'" data-sort="desc-0"><span>A</span></th>
							<th class="disable-sort menu" id="x:data_import:id-file"><div class="hide-edit hide"><input type="button" class="data msg del" value="d" title="'.getLabel('lbl_delete').'" /></div><input type="button" class="data neutral" value="multi" /><input type="checkbox" class="multi all" value="" /></th>
						</tr> 
					</thead>
					<tbody>
						<tr>
							<td colspan="4" class="empty">'.getLabel('msg_loading_server_data').'</td>
						</tr>
					</tbody>
				</table>

			</div>
		</div>';
		
		return $return;
	}
	
	private function createAddButtons($what) {
		
		if ($what == 'template') {
			$str_what = getLabel('lbl_import_template');
		} else if ($what == 'file') {
			$str_what = 'CSV '.getLabel('lbl_file');
		}
	
		$return .= '<form id="f:data_import:add-'.$what.'" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.$str_what.'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	public function createTemplate($id = false) {
		
		$arr_template = [];
		
		if ($id) {
			$arr_template = static::getTemplate($id);
		}
		
		$html = '<h1>'.($id ? getLabel('lbl_import_template').': '.$arr_template['name'] : getLabel('lbl_import_template')).'</h1>';
		
		$html .= parent::createTemplate($arr_template);
		
		return $html;
	}
	
	protected function createTemplateSettingsExtra($arr_template) {
				
		$html = '<li>
			<label>'.getLabel('lbl_name').'</label>
			<input type="text" name="'.$this->form_name.'[name]" value="'.$arr_template['name'].'" />
		</li>';
						
		return $html;
	}
		
	private function createSourceFile($id = false) {
		
		if (is_numeric($id)) {
			$arr_details = StoreIngestFile::getSources($id, false);
		}
		
		$return = '<h1>'.($id ? 'CSV '.getLabel('lbl_file').': '.$arr_details['name'] : 'CSV '.getLabel('lbl_file')).'</h1>
		
		<div class="options">
			<section class="info attention body">'.parseBody(getLabel('inf_import_file')).'</section>
			<fieldset><ul>
				<li>
					<label>'.getLabel('lbl_name').'</label>
					<input type="text" name="name" value="'.$arr_details['name'].'" />
				</li>
				<li>
					<label>'.getLabel('lbl_description').'</label>
					<textarea name="description" >'.$arr_details['description'].'</textarea>
				</li>
				<li class="file">
					<label>'.getLabel('lbl_file').':</label>'.cms_general::createFileBrowser();
				$return .= '</li>
				</ul>
			</fieldset>
		</div>
		
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' CSV '.getLabel('lbl_file').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
			
		$this->validate = ['name' => 'required'];
		
		return $return;
	}
		
	public function createRunTemplate($id) {
		
		$arr_template = static::getTemplate($id);
		
		$html = '<h1>'.getLabel('lbl_run').' '.getLabel('lbl_import_template').': "'.$arr_template['name'].'"</h1>';
		
		$html .= parent::createRunTemplate($arr_template);
		
		return $html;
	}
		
	protected function createCheckTemplate($arr_template, $source_id) {

		if (!$source_id || !$arr_template) {
			return false;
		}
		
		$arr_source_file = StoreIngestFile::getSources($source_id);
		
		$is_compatible = IngestTypeObjectsFile::checkTemplateSourceCompatibility($arr_template, $arr_source_file);
		
		if (!$is_compatible) {
			
			return '<section class="info attention body">'.parseBody(getLabel('inf_import_incompatible_source')).'</section>';
		}
		
		$import = new IngestTypeObjectsFile($arr_template['type_id']);
		$import->setTemplate($arr_template['pointers'], $arr_template['mode']);
		$import->setSource(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']);
		
		$num_row_pointers = $import->getRowPointerCount();
		
		if (!$num_row_pointers) {
			
			$return = '<section class="info attention body">'.parseBody(getLabel('msg_no_data')).'</section>';
			return $return;
		}
		
		$arr_rows = [0, (round($num_row_pointers/2) - 1), ($num_row_pointers - 1)];
	
		$return = '<section class="info attention body">'.parseBody(getLabel('inf_import_inspect_rows')).'</section><div class="fieldsets"><div>';
							
		foreach ($arr_rows as $num_row) {
			
			$num_row_show = (int)$num_row;
			$num_row_show = ($num_row_show + 1);
			
			$return .= '<fieldset><legend>Row '.$num_row_show.':</legend><ul>';
			
			foreach ($arr_template['pointers'] as $type => $arr_type_pointers) {
				
				if (!$arr_type_pointers) {
					continue;
				}
				
				if ($type == 'filter_object_identifier') {
					$arr_type_pointers = [$arr_type_pointers];
				}
			
				foreach ($arr_type_pointers as $arr_pointer) {
					
					$pointer_heading = $arr_pointer['pointer_heading'];
					$value_split = $arr_pointer['value_split'];
					$value_index = $arr_pointer['value_index'];
					$str_value = strEscapeHTML($import->getPointerData($num_row, $pointer_heading));
					
					$return .= '<li><label>'.$pointer_heading.'</label>';
					
					if ($value_split) {
						
						$arr_split = explode($value_split, $str_value);
						$return .= '<input type="text" value="'.($value_index == 'multiple' ? implode(', ', $arr_split) : $arr_split[(int)$value_index-1]).'" disabled />';
						
					} else {
						
						$return .= '<input type="text" value="'.$str_value.'" disabled />';
					}
					
					$return .= '</li>';
				}
			}
			
			$return .= '</ul></fieldset>';
		}
		
		$return .= '</div></div>';
				
		return $return;
	}
	
	public function createProcessTemplate($arr_template, $source_id, $arr_feedback) {
		
		if (!$source_id || !$arr_template) {
			return false;
		}
		
		$arr_feedback = $arr_feedback['ingest'];
		
		$arr_source_file = StoreIngestFile::getSources($source_id);

		$is_compatible = IngestTypeObjectsFile::checkTemplateSourceCompatibility($arr_template, $arr_source_file);
		
		if (!$is_compatible) {
			
			return [
				'is_done' => true,
				'html' => '<section class="info attention body">'.parseBody(getLabel('inf_import_incompatible_source')).'</section>'
			];
		}
		
		$this->is_new_template_process = (!isset($arr_feedback['in_process']));
		
		$arr_nodegoat_details = cms_nodegoat_details::getDetails();
		if ($arr_nodegoat_details['processing_time']) {
			timeLimit($arr_nodegoat_details['processing_time']);
		}
		if ($arr_nodegoat_details['processing_memory']) {
			memoryBoost($arr_nodegoat_details['processing_memory']);
		}
							
		$ingest = new IngestTypeObjectsFile($arr_template['type_id'], $_SESSION['USER_ID']);
		$ingest->setTemplate($arr_template['pointers'], $arr_template['mode']);
		$ingest->setSource(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']);
		
		$ingest->useLogIdentifier($arr_template['id']);
		$ingest->clearLog(); // Remove possible log of previous run.
		
		if (!$arr_template['use_log']) {
			$ingest->useLogIdentifier(false);
		}
		
		$is_ignorable = (!$this->is_new_template_process);
		$arr_unresolved_filters = $ingest->resolveFilters($is_ignorable);
		
		if ($arr_unresolved_filters) {
			
			$html = $this->createProcessTemplateResultCheck($arr_unresolved_filters);
			
			$this->has_feedback_template_process = true;
		} else {
			
			$ingest->process();
				
			$arr_result = $ingest->store();
			
			$html = $this->createProcessTemplateStoreCheck($arr_result);
			
			$this->is_done_template_process = true;
		}
		
		$html = '<div class="ingest-source template-process">'.$html.'</div>'
		.'<input name="source_id" type="hidden" value="'.$source_id.'" />'
		.'<input name="ingest[in_process]" type="hidden" value="1" />';
				
		return [
			'is_done' => $this->is_done_template_process,
			'has_feedback' => $this->has_feedback_template_process,
			'html' => $html
		];
	}

	private function createTemplateLog($import_template_id) {

		$arr_import_template = StoreIngestFile::getTemplates($import_template_id);
		$elm_filter_pointers = false;
		$colspan = 0;
		
		foreach ($arr_import_template['pointers'] as $type => $arr_type_pointers) {
			
			if (!$arr_type_pointers || $type == 'filter_object_identifier') {
				continue;
			}
			
			foreach ($arr_type_pointers as $arr_pointer) {
			
				$pointer_heading = $arr_pointer['pointer_heading'];

				if ($type == 'filter_object_value') {
					
					$elm_filter_columns .= '<th class="disable-sort">'.getLabel('lbl_filter').': '.$pointer_heading.'</th>';
					$colspan++;
					
				} else if ($type == 'map') {
					
					$elm_data_columns .= '<th class="disable-sort">'.$pointer_heading.'</th>';
					$colspan++;
				}
			}
		}
		
		$colspan = $colspan + 2;
		
		if ($elm_filter_columns) {
			
			$colspan++;
		}
		
		$date = new DateTime($arr_import_template['last_run']);
		$date->modify('+1 month');
		Labels::setVariable('removal_date', $date->format('d-m-Y'));
						
		$return = '<div class="options"><form class="filter">
				<label>'.getLabel('lbl_filter').':</label>
				<div class="input">
					<label><input type="checkbox" name="no_object" value="1" /><span>'.getLabel('lbl_import_log_no_object').'</span></label>
					<label><input type="checkbox" name="no_reference" value="1" /><span>'.getLabel('lbl_import_log_no_reference').'</span></label>
					<label><input type="checkbox" name="ignore_identical" value="1" /><span>'.getLabel('lbl_import_log_ignored_identical').'</span></label>
					<label><input type="checkbox" name="ignore_empty" value="1" /><span>'.getLabel('lbl_import_log_ignored_empty').'</span></label>
					<label><input type="checkbox" name="error" value="1" /><span>Error</span></label>
				</div>
			</form></div>
			<table class="display" id="d:data_import:data_log-'.$import_template_id.'_'.($elm_filter_columns ? '1' : '0').'">
			<thead>
				<tr>			
					<th class="limit">'.getLabel('lbl_import_row_number').'</th>'.
					$elm_filter_columns.
					($elm_filter_columns ? '<th class="disable-sort">'.getLabel('lbl_filter').'</th>' : '').
					'<th class="disable-sort">'.getLabel('lbl_object').'</th>'.
					$elm_data_columns.
					'</tr> 
			</thead>
			<tbody>
				<tr>
					<td colspan="'. $colspan .'" class="empty">'.getLabel('msg_loading_server_data').'</td>
				</tr>
			</tbody>
		</table>
		<section class="info attention body">'.parseBody(getLabel('inf_import_log_info')).'</section>';
			
		return $return;	
	}
	
	public static function css() {
	
		$return = parent::css();
		
		$return .= '			
			.data_import .import-log-template input.del { pointer-events: none; }
		';

		return $return;
	}
	
	public static function js() {
		
		$return = parent::js();

		$return .= "
		SCRIPTER.static('.data_import', function(elm_scripter) {
		
			elm_scripter.on('ajaxloaded', function() {
				$('[id^=f\\\:data_import\\\:process_template-]').data({'target': elm_scripter, options: {'html': 'html'}});
			}).on('click', '[id^=d\\\:data_import\\\:data-] .edit', function() {
				var cur = $(this);
				cur.quickCommand(cur.closest('.tabs > div').find('form'), {html: 'replace'});
			}).on('click', '[id^=d\\\:data_import\\\:data-template] .run_template', function() {
				$(this).quickCommand(elm_scripter, {html: 'html'});
			});
		});
		
		SCRIPTER.static('.data_import', 'ingest_source');
				
		SCRIPTER.dynamic('.import-log-template', function(elm_scripter) {
		
			elm_scripter.on('change', 'form input', function() {
				var target = $(this).closest('form').closest('div').next('div').find('table[id^=d\\\:data_import\\\:data_log]');
				var value = (target.data('value') ? target.data('value') : {});
				value.filter = JSON.stringify(serializeArrayByName($(this).closest('form')));
				target.data('value', value).dataTable('refresh');
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_import\\\:insert_template], [id^=f\\\:data_import\\\:update_template]', 'ingest_source_template');
		
		SCRIPTER.dynamic('[id^=f\\\:data_import\\\:process_template-]', function(elm_scripter) {
					
			runElementSelectorFunction(elm_scripter, '.ingest-source.run, .ingest-source.template-process', function(elm_found) {
				SCRIPTER.runDynamic(elm_found);
			});
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		parent::commands($method, $id, $value);
	
		// INTERACT
		
		if ($method == "add") {
				
			if ($id == 'file') {

				$return = '<form id="f:data_import:source_file_insert-0">'.$this->createSourceFile().'</form>';
				
			} else if ($id == 'template') { 
				
				$arr_source_files = StoreIngestFile::getSources();

				if (count((array)$arr_source_files) == 0) {
					
					msg(getLabel('msg_import_no_files'));
					
					return;
				}
				
				$return = '<form id="f:data_import:insert_template-0">
					
					'.$this->createTemplate().'
					
					<menu class="options">
						<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_import_template').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
					</menu>
				</form>';
			}
		
			$this->html = $return;
		}
		
		if ($method == "edit" && $id) {
			
			$arr_id = explode('_', $id);
			$what = $arr_id[0];
			$id = $arr_id[1];
			
			if ($what == 'template') {
				
				$this->html = '<form id="f:data_import:update_template-'.$id.'">
					
					'.$this->createTemplate($id).'
					
					<menu class="options">
						<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_import_template').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
					</menu>
				</form>';
			} else if ($what == 'file') {
				$this->html = '<form id="f:data_import:update_source_file-'.$id.'">'.$this->createSourceFile($id).'</form>';
			}
		}
				
		if ($method == "run_template") {
			
			$arr_id = explode('_', $id);
			$what = $arr_id[0];
			$id = $arr_id[1];
			
			$this->html = '<form id="f:data_import:process_template-'.$id.'">
				
				'.$this->createRunTemplate($id).'
				
				<menu>
					<input type="submit" value="'.getLabel('lbl_exit').'" name="do_discard" /><input type="submit" value="'.getLabel('lbl_next').'" />
				</menu>
			</form>';
		}
		
		if ($method == "log_template") {
			
			$this->html = '<div class="import-log-template">'.$this->createTemplateLog($id).'</div>';
		}

		if ($method == "process_template" && $this->is_discard) {
			
			$this->html = $this->contents(); 
			return;
		}
		
		if ($method == "process_template" && !$this->is_discard) {
		
			$source_id = $_POST['source_id'];
			$import_template_id = $id;

			$arr_template = static::getTemplate($import_template_id);
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER || !data_entry::checkClearanceType($arr_template['type_id'])) {
				error(getLabel('msg_not_allowed'));
			}
			
			$arr_result = $this->createProcessTemplate($arr_template, $source_id, $_POST);
			
			if ($arr_result['is_done']) {
				$html_buttons = '<input type="submit" name="do_discard" value="'.getLabel('lbl_exit').'" />';
			} else if ($arr_result['has_feedback']) {
				$html_buttons = '<input type="submit" value="'.getLabel('lbl_next').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_exit').'" />';
			} else {
				$html_buttons = '<input type="submit" value="'.getLabel('lbl_run').' '.strEscapeHTML(Labels::parseTextVariables($arr_type_set['type']['name'])).'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_exit').'" />';
			}
			
			$this->html = '<form id="f:data_import:process_template-'.$arr_template['id'].'">
			
				'.$arr_result['html'].'
				
				<menu>
					'.$html_buttons.'
				</menu>
			</form>';
		}
		
		// DATA

		if ($method == "data") {

			if ($id == 'template') {
				
				$arr_sql_columns = ['nodegoat_it.name', 'nodegoat_t.name', 'nodegoat_if.name', 'nodegoat_it.id'];
				$arr_sql_columns_search = ['nodegoat_it.name', 'nodegoat_t.name', 'nodegoat_if.name'];
				$arr_sql_columns_as = ['nodegoat_it.id', 'nodegoat_it.name', 'nodegoat_t.name AS type_name', 'nodegoat_if.name AS source_file_name'];
				
				if (Settings::get('domain_administrator_mode')) {
					$arr_sql_columns[1] = 'CASE WHEN nodegoat_t.label != \'\' THEN CONCAT(nodegoat_t.label, \' \', nodegoat_t.name) ELSE nodegoat_t.name END';
					$arr_sql_columns_search[1] = 'CONCAT(nodegoat_t.label, \' \', nodegoat_t.name)';
					$arr_sql_columns_as[] = 'nodegoat_t.label AS type_label';
				}
				
				$sql_table = DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." nodegoat_it
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (nodegoat_t.id = nodegoat_it.type_id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')." nodegoat_if ON (nodegoat_if.id = nodegoat_it.source_id)
				";
				
				$sql_index = 'nodegoat_it.id';
				$sql_where = '';
				
			} else if ($id == 'file') {

				$arr_sql_columns = ['nodegoat_if.name', 'nodegoat_if.total_objects', 'nodegoat_if.id'];
				$arr_sql_columns_search = ['nodegoat_if.name'];
				$arr_sql_columns_as = ['nodegoat_if.id', 'nodegoat_if.name', 'nodegoat_if.total_objects'];
			
				$sql_table = DB::getTable('DEF_NODEGOAT_IMPORT_FILES').' nodegoat_if';
				$sql_index = 'nodegoat_if.id';
				$sql_where = '';
			}
				
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);

			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
			
				$arr_data = [];
				
				if ($id == 'template') {
					
					$str_id = $id.'_'.$arr_row['id'];
					$arr_data['id'] = 'x:data_import:id-'.$str_id;
					
					$import = new IngestTypeObjectsFile(0);
					$import->useLogIdentifier($arr_row['id']);
					
					$arr_data[] = Labels::parseTextVariables($arr_row['name']);
					$arr_data[] = Labels::parseTextVariables($arr_row['type_label'] ? $arr_row['type_label'].' '.$arr_row['type_name'] : $arr_row['type_name']);
					$arr_data[] = Labels::parseTextVariables($arr_row['source_file_name']);
					
					$has_log = $import->hasSourcePointerLog();
					
				} else if ($id == 'file') {
					
					$str_id = $id.'_'.$arr_row['id'];
					$arr_data['id'] = 'x:data_import:id-'.$str_id;
					
					$arr_data[] = Labels::parseTextVariables($arr_row['name']);
					$arr_data[] = Labels::parseTextVariables($arr_row['total_objects']);
				}
				
				$arr_data[] = '';
				
				$arr_data[] = ($id == 'template' ? ($has_log ?
						'<input type="button" class="data neutral popup" value="log" id="y:data_import:log_template-'.$arr_row['id'].'" />' : '')
						.'<input type="button" class="data add run_template" value="run" />'
					: '')
					.'<input type="button" class="data edit" value="edit" />'
					.'<input type="button" class="data del msg del" value="del" />'
					.'<input class="multi" value="'.$str_id.'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		if ($method == "data_log") {

			$arr_ids = explode('_', $id);
			$import_template_id = $arr_ids[0];
			$template_has_filter = $arr_ids[1];
			
			$arr_import_template = StoreIngestFile::getTemplates($import_template_id);
			$type_id = $arr_import_template['type_id'];
			
			$arr_log_filter = ($value && $value['filter'] ? json_decode($value['filter'], true) : []);
					
			$arr_sql_columns = ['nodegoat_itl.row_identifier', 'nodegoat_itl.object_id', 'nodegoat_itl.row_data', 'nodegoat_itl.row_filter', 'nodegoat_itl.row_results'];
			$arr_sql_columns_search = ['nodegoat_itl.object_id', 'nodegoat_itl.row_data', 'nodegoat_itl.row_filter', 'nodegoat_itl.row_results'];
			$arr_sql_columns_as = ['nodegoat_itl.row_identifier', 'nodegoat_itl.object_id', 'nodegoat_itl.row_data', 'nodegoat_itl.row_filter', 'nodegoat_itl.row_results'];

			$sql_table = DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')." nodegoat_itl";
			
			$sql_index = 'nodegoat_itl.row_identifier';
			$sql_where = "nodegoat_itl.template_id = ".$import_template_id;

			if ($arr_log_filter['no_object']) {
				
				 $sql_where .= " AND nodegoat_itl.object_id IS NULL";
			}
			
			if ($arr_log_filter['no_reference']) {
				
				 $sql_where .= " AND nodegoat_itl.row_results LIKE '%unmatched_reference%'";
			}
			
			if ($arr_log_filter['ignore_empty']) {
				
				 $sql_where .= " AND nodegoat_itl.row_results LIKE '%ignore_empty%'";
			}
			
			if ($arr_log_filter['ignore_identical']) {
				
				 $sql_where .= " AND nodegoat_itl.row_results LIKE '%ignore_identical%'";
			}
			
			if ($arr_log_filter['error']) {
				
				 $sql_where .= " AND nodegoat_itl.row_results LIKE '%error%'";
			}
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);			
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc()) {
				
				$count = 0;
				$arr_data = [];
				
				$arr_row_data = json_decode(($arr_row['row_data'] != '' ? $arr_row['row_data'] : ''), true);
				$arr_row_results = json_decode(($arr_row['row_results'] != '' ? $arr_row['row_results'] : ''), true);
				
				$num_row = (int)$arr_row['row_identifier'] + 1;
				
				if ($arr_row_results['error']) {
					
					$arr_data[] = $num_row . ' <input type="button" class="data del" value="error" />';
					$arr_data['cell'][$count]['attr']['title'] = getLabel($arr_row_results['error']);
				
				} else {
					
					$arr_data[] = $num_row;
				}
				$count++;
				
				if ($template_has_filter) {

					foreach ($arr_import_template['pointers']['filter_object_value'] as $arr_pointer) {
						
						$arr_data[] = $arr_row_data[$arr_pointer['pointer_heading']];
						$count++;
					}
					
					if ($arr_row['row_filter'] != '') {
						
						$arr_row_filter = '<input type="button" id="y:data_import:view_filter-'.$import_template_id.'_'.$arr_row['row_identifier'].'" class="data edit popup" value="filter" />';
						
					} else {
						
						$arr_row_filter = '<input type="button" class="data del" value="no filter" />';
					}
					
					$arr_data[] = $arr_row_filter;
					$count++;
				}
					
				$arr_data[] = ($arr_row['object_id'] ? '<input type="button" id="y:data_view:view_type_object-'.$type_id.'_'.$arr_row['object_id'].'" class="popup data add" value="object" />' : '<input type="button" class="data del" value="n/a"/>');
				$count++;
				
				foreach ($arr_import_template['pointers']['map'] as $arr_pointer) {
					
					$pointer_heading = $arr_pointer['pointer_heading'];
					$elm = '';
					
					if ($arr_pointer['element_type_id']) {
						
						foreach ((array)$arr_row_results[$pointer_heading]['objects'] as $ref_type_id => $arr_ref_objects) {
							
							foreach ((array)$arr_ref_objects as $ref_object_id => $value) {
								
								$elm .= '<input type="button" id="y:data_view:view_type_object-'.$ref_type_id.'_'.$ref_object_id.'" class="popup data add" value="reference" />';
							}
						}
					}
					
					if ($arr_row_results[$pointer_heading]['options']['ignore_identical']) {
						
						$elm .= '<input type="button" class="data del" value="identical" />';
					}
					
					if ($arr_row_results[$pointer_heading]['options']['unmatched_reference']) {
						
						$elm .= '<input type="button" class="data del" value="n/a" />';
					}
					
					if ($arr_row_data[$pointer_heading]) {
						
						$elm .= ' <span>'.$arr_row_data[$pointer_heading].'</span>';
						
					} else if ($arr_row_results[$pointer_heading]['options']['ignore_empty']) {
							
						$elm .= '<input type="button" class="data del" value="empty" />';
						
					}
					
					$arr_data[] = $elm;
					
					
					$count++;
				}
				
				$arr_datatable['output']['data'][] = $arr_data;
			}

			$this->data = $arr_datatable['output'];
		}
		
		if ($method == "view_filter") {
			
			$arr_ids = explode('_', $id);
			$import_template_id = $arr_ids[0];
			$num_row = $arr_ids[1];
			
			$arr_import_template = StoreIngestFile::getTemplates($import_template_id);
			$type_id = $arr_import_template['type_id'];
			
			$ingest = new IngestTypeObjectsFile($type_id);
			$ingest->useLogIdentifier($import_template_id);
			$arr_log = $ingest->getSourcePointerLog($num_row);

			$arr_filter = json_decode($arr_log['row_filter'], true);
			$arr_filter_form = ['form' => ['filter_1' => ['type_id' => $type_id]]];
		
			foreach ($arr_filter as $key => $value) {
				
				$arr_filter_form['form']['filter_1'][$key] = $value;
			}			
			
			$create_filter = new data_filter();
			$create_filter->form_name = 'filter['.$type_id.']';
			$html_filter = $create_filter->createFilter($type_id, $arr_filter_form);
			
			$this->html = $html_filter;
		}

		// QUERY

		if (($method == "source_file_insert" || $method == "update_source_file" || $method == "insert_template" || $method == "update_template") && $this->is_discard) {
			
			if ($method == "source_file_insert" || $method == "update_source_file") {
				$what = 'file';
			} else if ($method == "insert_template" || $method == "update_template") {
				$what = 'template';
			}
			
			$this->html = $this->createAddButtons($what);
			return;
		}
		
		if ($method == "source_file_insert" || ($method == "update_source_file" && (int)$id)) {

			$arr_details = ['name' => $_POST['name'], 'description' => $_POST['description']];
			
			StoreIngestFile::handleSource((int)$id, $_POST['type'], $arr_details, ($_FILES ? $_FILES : ''));
					
			$this->html = $this->createAddButtons('file');
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "insert_template" || ($method == "update_template" && (int)$id)) {
			
			$arr_template = static::parseTemplate($_POST['template']);
			
			StoreIngestFile::handleTemplate((int)$id, $arr_template);
			
			$this->html = self::createAddButtons('template');
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && $id) {
			
			foreach ((array)$id as $combined_id) {
				
				$arr_id = explode('_', $combined_id);
				$what = $arr_id[0];
				
				if ($what == 'template') {
					StoreIngestFile::delTemplate($arr_id[1]); 
				} else if ($what == 'file') {
					StoreIngestFile::delSource($arr_id[1]); 
				}
			}
			
			$this->refresh_table = true;				
			$this->msg = true;
		}
	}
	
	public static function getTemplate($import_template_id) {
		
		$arr = StoreIngestFile::getTemplates($import_template_id);
		
		$arr['identifier'] = $arr['id'];
		
		return $arr;
	}
	
	public static function getSources() {
		
		$arr = StoreIngestFile::getSources(false);
		
		return $arr;
	}
	
	public static function getPointerDataHeadings($source_id, $pointer_heading = false) {
		
		if (!$source_id) {
			$arr = [['id' => $pointer_heading, 'name' => $pointer_heading]];
		} else {
			$arr = StoreIngestFile::getColumnHeadings($source_id);
		}

		return $arr;
	}
	
	public static function getPointerFilterHeadings($source_id, $pointer_heading = false) {
		
		return static::getPointerDataHeadings($source_id, $pointer_heading);
	}
		
	public static function parseTemplate($arr_template_raw) {
		
		$arr_template = parent::parseTemplate($arr_template_raw);
		
		$arr_template['name'] = $arr_template_raw['name'];

		return $arr_template;
	}
}
