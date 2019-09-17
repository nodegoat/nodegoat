<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_import extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Import';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'data_entry' => [],
		'data_view' => [],
		'data_filter' => []
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
				<li><a href="#">'.getLabel('lbl_string_object_pairs').'</a></li>
			</ul>
			<div class="tab-import">
			
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
			<div class="tab-import">
			
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
			<div class="tab-import">
			
				'.$this->createAddButtons('pair').'

				<table class="display" id="d:data_import:data-pair">
					<thead>
						<tr>			
							<th class="max">'.getLabel('lbl_name').'</th>
							<th>'.getLabel('lbl_type').'</th>
							<th title="'.getLabel('lbl_added').'" data-sort="desc-0"><span>A</span></th>
							<th class="disable-sort menu" id="x:data_import:id-pair"><div class="hide-edit hide"><input type="button" class="data msg del" value="d" title="'.getLabel('lbl_delete').'" /></div><input type="button" class="data neutral" value="multi" /><input type="checkbox" class="multi all" value="" /></th>
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
		} else {
			return '<form></form>';
		}
	
		$return .= '<form id="f:data_import:add-'.$what.'" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.$str_what.'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	private function createImportTemplate($id = false) {
		
		$type_id = $template_has_filter = $template_has_filter_type_object_id = false;
		$arr_source_files = cms_nodegoat_import::getSourceFiles(false); 
		$arr_type_sets = StoreType::getTypes();
				
		if (is_numeric($id)) {
			
			$arr_details = cms_nodegoat_import::getImportTemplates($id);
			$type_id = $arr_details['import_template']['type_id'];
			$source_file_id = $arr_details['import_template']['source_file_id'];
			
			if ($arr_details['columns']) {
				
				foreach ($arr_details['columns'] as $key => $arr_column) {
					
					if ($arr_column['use_as_filter']) {
						
						$template_has_filter = true;
					}
					
					if ($arr_column['use_object_id_as_filter']) {
						
						$template_has_filter_type_object_id = true;
					}
				}	
						
			} else {
				
				$arr_details['columns'] = [];
			}
			
			if (!$type_id || !$arr_type_sets[$type_id]) {
				error(getLabel('msg_type_does_not_exist'));
			}
		}
	
		$return = '<h1>'.($id ? getLabel('lbl_import_template').': '.$arr_details['import_template']['name'] : getLabel('lbl_import_template')).'</h1>

				<div class="options">
					<section class="info attention body">'.parseBody(getLabel('inf_import_template')).'</section>
					<fieldset>
						<ul>
							<li>
								<label>CSV '.getLabel('lbl_file').'</label>
								<select name="template_source_file_id">'.cms_general::createDropdown($arr_source_files, $source_file_id, true).'</select>
							</li>
							<li>
								<label>'.getLabel('lbl_target').'</label>
								<select name="template_type_id">'.Labels::parseTextVariables(cms_general::createDropdown($arr_type_sets, $type_id, true)).'</select>
							</li>
							<li>
								<label>'.getLabel('lbl_name').'</label>
								<input type="text" name="name" value="'.$arr_details['import_template']['name'].'" />
							</li>
							<li>
								<label>'.getLabel('lbl_mode').'</label>
								'.cms_general::createSelectorRadio([['id' => 'add', 'name' => getLabel('lbl_import_add_new_objects')], ['id' => 'update', 'name' => getLabel('lbl_import_update_objects')]], 'template_mode', ($template_has_filter ? 'update' : 'add')).'
								<span class="split"></span>
								<label><input type="checkbox" name="use_log" value="1" '.($arr_details['import_template']['use_log'] ? 'checked="checked"' : '').'/><span>'.getLabel('lbl_import_show_log').'</span></label>
							</li>
						</ul>
					</fieldset>
				</div>
				
				<div class="options filter '.($template_has_filter ? '' : 'hide').'">
					<fieldset>
						<legend>'.getLabel('lbl_import_columns_used_as_filter').'</legend>
						<section class="info attention body">'.parseBody(getLabel('inf_import_filter_columns')).'</section>
						<ul>
							<li>
								<label>'.getLabel('lbl_import_identify_objects').'</label>
								'.cms_general::createSelectorRadio([['id' => 'id', 'name' => 'nodegoat ID'], ['id' => 'filter', 'name' => getLabel('lbl_filter')]], 'filter_mode', ($template_has_filter && !$template_has_filter_type_object_id ? 'filter' : 'id')).'
							</li>
							<li class="'.($template_has_filter && $template_has_filter_type_object_id ? '' : 'hide').'">
								<label>nodegoat ID</label>
								<div id="y:data_import:set_columns-filter_id">'.($arr_details ? self::createColumns($source_file_id, $type_id, $arr_details['columns'], 'filter_id') : '').'</div>
							</li>
							<li class="'.($template_has_filter && !$template_has_filter_type_object_id ? '' : 'hide').'">
								<label></label>
								<input type="button" class="data add" value="add" />
							</li>
							<li class="'.($template_has_filter && !$template_has_filter_type_object_id ? '' : 'hide').'">
								<label>'.getLabel('lbl_filter').' '.getLabel('lbl_columns').'</label>
								<div id="y:data_import:set_columns-filter">'.($arr_details ? self::createColumns($source_file_id, $type_id, $arr_details['columns'], 'filter') : '').'</div>
							</li>
						</ul>
					</fieldset>
				</div>
				
				<div class="options columns '.($arr_details['columns'] ? '' : 'hide').'">
					<fieldset>
						<legend>'.getLabel('lbl_import_connect_columns_to_data_model').'</legend>
						<section class="info attention body">'.parseBody(getLabel('inf_import_connect_columns')).'</section>
						<ul>
							<li>
								<label></label>
								<input type="button" class="data add" value="add" />
							</li>
							<li>
								<label>'.getLabel('lbl_columns').'</label>
								<div id="y:data_import:set_columns-0">'.($arr_details ? self::createColumns($source_file_id, $type_id, $arr_details['columns'], false, ($template_has_filter ? 'update' : 'store')) : '').'</div>
							</li>
						</ul>
					</fieldset>
				</div>

	
			<menu class="options">
				<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_import_template').'" /><input type="submit" name="discard" value="'.getLabel('lbl_cancel').'" />
			</menu>';
				
		$this->validate = ['template_type_id' => 'required'];
		
		return $return;
	}
	
	private function createSourceFile($id = false) {
		
		if (is_numeric($id)) {
			$arr_details = cms_nodegoat_import::getSourceFiles($id, false);
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
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_source').'" /><input type="submit" name="discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
			
		$this->validate = ['name' => 'required'];
		
		return $return;
	}
	
	private function createStringObjectPair($id) {
	
		$arr_string_object_pair = cms_nodegoat_import::getStringObjectPair($id);
		$arr_object_name = FilterTypeObjects::getTypeObjectNames($arr_string_object_pair['type_id'], [$arr_string_object_pair['object_id']], false);
		$arr_type_set = StoreType::getTypeSet($arr_string_object_pair['type_id']);	
		
		$string = $arr_string_object_pair['string'];
		
		if ($arr_string_object_pair['filter_values'] != '') {
			
			$arr_filter_values = json_decode($arr_string_object_pair['filter_values'], true);
			$string = '';
			
			foreach ($arr_filter_values as $arr_filter_value) {
				
				$string .= (!$arr_filter_value ? '[no value]' : $arr_filter_value).', ';
			}
			
			$string = substr($string, 0, -2);
		}
		
		$return = '<h1>'.Labels::parseTextVariables($arr_type_set['type']['name']).': '.$string.'</h1>
		<div class="options"><fieldset><ul>
			<li>
				<label>'.$string.'</label>
				<span>
					<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$arr_string_object_pair['type_id'].'" name="object_id" value="" />
					<input type="text" id="y:data_filter:lookup_type_object-'.$arr_string_object_pair['type_id'].'" class="autocomplete" value="'.$arr_object_name[$arr_string_object_pair['object_id']].'" />
					<input type="hidden" name="type_id" value="'.$arr_string_object_pair['type_id'].'" />
					<input type="hidden" name="string" value="'.$arr_string_object_pair['string'].'" />
				</span>
			</li>
		</ul></fieldset></div>
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_string_object_pair').'" /><input type="submit" name="discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
				
		return $return;
		
	}
	
	private function createColumns($source_file_id, $type_id, $arr_columns = [], $filter = false, $template_mode = 'store') {
		
		$arr_sorter = [];
		
		array_unshift($arr_columns, ['target_type_id' => $type_id, 'source_file_id' => $source_file_id, 'use_as_filter' => $filter]); // Empty run for sorter source

		foreach ((array)$arr_columns as $key => $arr_column) {

			if ($arr_column['target_type_id'] != $type_id) {
				
				continue;
			}
			
			$set_column = false;
			
			if ($filter == 'filter_id' && $arr_column['use_as_filter'] && $arr_column['use_object_id_as_filter']) {
	
				$set_column = true;
				
			} else if ($filter == 'filter' && $arr_column['use_as_filter'] && !$arr_column['use_object_id_as_filter']) {
					
				$set_column = true;
		
			} else if (!$filter && !$arr_column['use_as_filter']) {
							
				if ($template_mode == 'update') {
						
					$arr_column['template_mode'] = $template_mode;
				}
				
				$set_column = true;
			}
			
			if ($set_column) {
				
				$arr_sorter[] = ['source' => ($key == 0 ? true : false), 'value' => self::createColumn($arr_column)];
			}
		}

		return cms_general::createSorter($arr_sorter, ($filter == 'filter_id' ? false : true));
	}
	
	private function createColumn($arr_column = []) {

		$arr_type_set = cms_nodegoat_import::flattenTypeSet($arr_column['target_type_id'], false, $arr_column['use_as_filter']);
		$column_heading = $arr_column['column_heading'];
	
		if (!$arr_column['arr_column_headings']) {
		
			if (!$arr_column['source_file_id']) {
				
				$arr_column['arr_column_headings'] = [['id' => $column_heading, 'name'=> $column_heading]];

			} else {
			
				$arr_column['arr_column_headings'] = cms_nodegoat_import::getColumnHeadings($arr_column['source_file_id']);
			}
		}
		
		$element_id = $arr_column['element_id'];
		
		if (!$element_id) {
			
			$element_id = key($arr_type_set);
		}

		$unique = uniqid('array_');
		
		$elm_column = '<input name="columns['.$unique.'][column_id]" data-name="column_id" type="hidden" value="'.$arr_column['column_id'].'" />'
			.'<input name="columns['.$unique.'][source_file_id]" data-name="source_file_id" type="hidden" value="'.$arr_column['source_file_id'].'" />'
			.'<select name="columns['.$unique.'][column_heading]" data-name="column_heading" title="'.getLabel('inf_import_column_heading').'">'.cms_general::createDropdown((array)$arr_column['arr_column_headings'], $column_heading, false).'</select>'
			.'<input name="columns['.$unique.'][target_type_id]" data-name="target_type_id" type="hidden" value="'.$arr_column['target_type_id'].'" />'
			.'<input name="columns['.$unique.'][use_as_filter]" data-name="use_as_filter" type="hidden" value="'.$arr_column['use_as_filter'].'" />'
			.'<input name="columns['.$unique.'][use_object_id_as_filter]" data-name="use_object_id_as_filter" type="hidden" value="'.$arr_column['use_object_id_as_filter'].'" />'
			.'<input name="columns['.$unique.'][cell_splitter]" '.($arr_column['use_as_filter'] ? 'class="hide"' : '').' data-name="cell_splitter" type="text" value="'.$arr_column['cell_splitter'].'" title="'.getLabel('inf_import_use_splitter_character').'" />'
			.'<select name="columns['.$unique.'][generate_from_split]" '.($arr_column['use_as_filter'] ? 'class="hide"' : '').' data-name="generate_from_split" '.($arr_column['generate_from_split'] && $arr_column['cell_splitter'] ? '' : 'class="hide"').' title="'.getLabel('inf_import_specify_multiple_contents').'">'.cms_general::createDropdown(cms_nodegoat_import::getGenerateOptions(), $arr_column['generate_from_split']).'</select>'
			.'<select name="columns['.$unique.'][element_id]" '.($arr_column['use_as_filter'] && $arr_column['use_object_id_as_filter'] ? 'class="hide"' : '').' data-name="element_id" title="'.getLabel('inf_import_target_in_data_model').'">'.cms_general::createDropdown((array)$arr_type_set, $element_id, false).'</select>';

			if ($arr_type_set[$element_id]['ref_type_id'] || $arr_type_set[$element_id]['location_reference']) {
				
				if ($arr_type_set[$element_id]['ref_type_is_changeable']) {
					
					$ref_type_id = ($arr_column['element_type_id'] ? $arr_column['element_type_id'] : $arr_type_set[$element_id]['ref_type_id']);
					
				} else {
					
					$disabled = true;
					$ref_type_id = $arr_type_set[$element_id]['ref_type_id'];
				}
			
				$arr_ref_type_set = cms_nodegoat_import::flattenTypeSet($ref_type_id, true, true);
				$arr_types = StoreType::getTypes();
				
				$elm_column .= '<select name="columns['.$unique.'][element_type_id]" data-name="element_type_id" '.($disabled ? 'disabled' : '').' title="'.getLabel('inf_import_type_of_target_element').'">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types, $ref_type_id, false)).'</select>';
				
				if ($arr_type_set[$element_id]['location_reference']) {
					
					if ($arr_type_set[$element_id]['object_sub_details_is_changeable']) {
						
						$object_sub_details_id = ($arr_column['element_type_object_sub_id'] ? $arr_column['element_type_object_sub_id'] : $arr_type_set[$element_id]['object_sub_details_id']);
						
					} else {
						
						$sub_disabled = true;
						$object_sub_details_id = $arr_type_set[$element_id]['object_sub_details_id'];
					}
							
					$elm_column .= '<select name="columns['.$unique.'][element_type_object_sub_id]" data-name="element_type_object_sub_id" '.($sub_disabled ? 'disabled' : '').' title="'.getLabel('inf_import_sub_object_of_location_reference_type').'">'.Labels::parseTextVariables(cms_general::createDropdown(StoreType::getTypeObjectSubsDetails($ref_type_id), $object_sub_details_id, false, 'object_sub_details_name', 'object_sub_details_id')).'</select>';
				
				} else {
					
					$elm_column .= '<input name="columns['.$unique.'][element_type_object_sub_id]" type="hidden"/>';
					
				}
				 
				$elm_column .= '<select name="columns['.$unique.'][element_type_element_id]" data-name="element_type_element_id" title="'.getLabel('inf_import_element_used_for_reference').'">'.cms_general::createDropdown((array)$arr_ref_type_set, $arr_column['element_type_element_id'], true).'</select>';
				
			} else {
				
				$elm_column .= '<input name="columns['.$unique.'][element_type_id]" type="hidden" value="0" />'
					.'<input name="columns['.$unique.'][element_type_object_sub_id]" type="hidden" value="0" />'
					.'<input name="columns['.$unique.'][element_type_element_id]" type="hidden" value="0" />';
			}
		
			$options_set = $arr_column['overwrite'] || $arr_column['ignore_when'];
			$elm_column .= '<input type="button" class="data neutral '.($arr_column['use_as_filter'] ? 'hide' : ($arr_column['template_mode'] == 'update' ? ($options_set ? 'hide' : '') : 'hide')).' column-options" name="options" value="more" title="'.getLabel('lbl_import_specify_additional_options').'" /><input type="button" class="data del '.($arr_column['use_as_filter'] && $arr_column['use_object_id_as_filter'] ? 'hide' : '').'" value="del" title="'.getLabel('lbl_import_remove_column').'" />';
			$elm_column = '<div id="y:data_import:create_column-'.$arr_column['source_file_id'].'_'.$arr_column['target_type_id'].($arr_column['use_as_filter'] ? '_filter' : '').'">'.$elm_column.'</div>';

			$elm_options = '<fieldset class="column-options '.($arr_column['template_mode'] == 'update' && $options_set ? '' : 'hide').'">
							<ul><li><div>
								<label><input type="radio" name="columns['.$unique.'][write]" value="overwrite" '.($arr_column['overwrite'] ? 'checked="checked"' : '').'/><span>'.getLabel('lbl_overwrite').'</span></label>
								<label><input type="radio" name="columns['.$unique.'][write]" value="append" '.(!$arr_column['overwrite'] ? 'checked="checked"' : '').' /><span>'.getLabel('lbl_append').'</span></label>
								<label><input type="checkbox" name="columns['.$unique.'][ingore_empty]" value="1" '.($arr_column['ignore_when'] == 1 || $arr_column['ignore_when'] == 3 ? 'checked="checked"' : '').'/><span>'.getLabel('lbl_import_ignore_empty').'</span></label>
								<label><input type="checkbox" name="columns['.$unique.'][ingore_identical]" value="2" '.($arr_column['ignore_when'] == 2 || $arr_column['ignore_when'] == 3 ? 'checked="checked"' : '').'/><span>'.getLabel('lbl_import_ignore_identical').'</span></label>
							</div></li></ul>
						</fieldset>';

		return [$elm_column, $elm_options];
	}
	
	private function createFileSelection($import_template_id) {
		
		$arr_import_template = cms_nodegoat_import::getImportTemplates($import_template_id);
		$arr_types = StoreType::getTypes();
		
		if (!$arr_import_template['columns']) {
			return false;
		}
				
		$type_id = $arr_import_template['import_template']['type_id'];
		
		if (!$arr_types[$type_id]) {
			
			error(getLabel('msg_type_does_not_exist'));
		}

		$return = '<form id="f:data_import:run_import-'.$import_template_id.'">
			<h1>'.getLabel('lbl_run').' '.getLabel('lbl_import_template').': "'.$arr_import_template['import_template']['name'].'"</h1>
			<div class="options">
				<section class="info attention body">'.parseBody(getLabel('inf_import_select_file')).'</section>
				<fieldset>
					<ul>
						<li>
							<label>Select File</label>
							<span>
								<select id="y:data_import:check_template-'.$import_template_id.'" name="source_file_id">'.cms_general::createDropdown(cms_nodegoat_import::getSourceFiles(false), $arr_import_template['import_template']['source_file_id'], true).'</select>
							</span>
						</li>
					</ul>
				</fieldset>
			</div>
			<div class="options">';
			
			if ($arr_import_template['import_template']['source_file_id']) {

				$return .= self::createCheckTemplate($import_template_id, $arr_import_template['import_template']['source_file_id']);
			}
		
			$return .= '</div>
			<menu>
				<input type="submit" value="'.getLabel('lbl_exit').'" name="exit" /><input type="submit" value="'.getLabel('lbl_next').'" class="continue" />
			</menu>
		</form>';
				
		return $return;
	}

	private function createCheckTemplate($import_template_id, $source_file_id) {

		if (!$source_file_id || !$import_template_id) {
			return false;
		}
			
		memoryBoost(1024);	
		
		$arr_import_template = cms_nodegoat_import::getImportTemplates($import_template_id);
		$arr_source_file = cms_nodegoat_import::getSourceFiles($source_file_id);

		if (!$arr_source_file || !$arr_import_template['columns']) {
			return false;
		}
		
		$arr_source_file_contents = (array)json_decode(file_get_contents(DIR_HOME_TYPE_IMPORT.$arr_source_file['filename']), true);

		$arr_rows = [0, (int)round(count($arr_source_file_contents)/2) - 1, (int)count($arr_source_file_contents) - 1];
	
		$return = '<section class="info attention body">'.parseBody(getLabel('inf_import_inspect_rows')).'</section><div class="fieldsets"><div>';
							
		foreach ($arr_rows as $row) {
			
			$row_number = (int)$row;
			$row_number = $row_number + 1;	
			$return .= '<fieldset><legend>Row '. $row_number .':</legend><ul>';
			
			foreach ($arr_import_template['columns'] as $arr_column) {
				
				$column_heading = $arr_column['column_heading'];
				$cell_splitter = $arr_column['cell_splitter'];
				$generate_from_split = $arr_column['generate_from_split'];
				$value = htmlspecialchars($arr_source_file_contents[$row][$column_heading]);
				
				$return .= '<li><label>'.$column_heading.'</label>';
				
				if ($cell_splitter) {
					
					$arr_split_values = explode($cell_splitter, $value);
					$return .= '<input type="text" value="'.($generate_from_split == 'multiple' ? implode(', ', $arr_split_values) : $arr_split_values[$generate_from_split-1]).'" disabled />';
					
				} else {
					
					$return .= '<input type="text" value="'.$value.'" disabled />';
				}
				
				$return .= '</li>';
			}
			
			$return .= '</ul></fieldset>';
		}
		
		$return .= '</div></div>';
				
		return $return;
	}
	
	private function createImportTemplateRun($import_template_id, $source_file_id) {
		
		if (!$source_file_id || !$import_template_id) {
			return false;
		}
		
		$arr_import_template = cms_nodegoat_import::getImportTemplates($import_template_id);
		$arr_source_file = cms_nodegoat_import::getSourceFiles($source_file_id);
		
		if (!$arr_source_file) {
			return false;
		}
							
		$import = new ImportTypeObjects($import_template_id, $source_file_id);
		
		$arr_unresolved_filters = $import->resolveFilters();
		
		if ($arr_unresolved_filters) {
			
			if ($arr_unresolved_filters['error_msg']) {

				$done = true;
				$legend = getLabel('msg_import_error_resolving_filters').' '.getLabel('msg_import_error_message_was').' '.implode(',', $arr_unresolved_filters['error_msg']).' '.getLabel('msg_import_inspect_template_source');
				$elm_done = '<section class="info attention body"><strong>'.getLabel('msg_import_error_at_value').'</strong> <i>'.implode(',', $arr_unresolved_filters['error_values']).'</i></section>';
			
			} else {

				$total = 0;
				foreach ((array)$arr_unresolved_filters as $type_id => $arr_results) {
					$total = $total + count($arr_results);
				}
				
				if ($total > 250) {
					
					$done = true;
					$legend = getLabel('msg_import_over_250_unmatched_filters');
				
				} else {
					
					$legend = getLabel('msg_import_resolve_unmatched_results');
					$elm_check = self::createResultsCheck($arr_unresolved_filters);
				}
			}
			
		} else {
				
			$arr_result = $import->store();
			$update_objects = $import->update_objects;
			
			$done = true;
			
			if ($arr_result['locked'] !== false) {

				$str_locked = '<ul><li>'.implode('</li><li>', $arr_result['locked']).'</li></ul>';
				
				Labels::setVariable('total', count($arr_result['locked']));
				$message = parseBody(getLabel('msg_import_stopped').' '.getLabel('msg_object_locked_multi')).parseBody($str_locked);
				
			} else if ($arr_result['error_row_number'] !== false) {
					
				$error_row_number = $arr_result['error_row_number'] + 1;
				Labels::setVariable('row_number', $error_row_number);				
				$message = parseBody(getLabel('msg_import_error_at_row_number'));
				
			} else {

				Labels::setVariable('object_amount', $arr_result['object_amount']);
				Labels::setVariable('verb', ($update_objects ? getLabel('lbl_import_updated') : getLabel('lbl_import_added')));
				
				$message = parseBody(getLabel('msg_import_done'));
				
			}
			
			if ($arr_import_template['import_template']['use_log']) {
				
				$message .= parseBody(getLabel('msg_import_log_created').' <span class="a popup" id="y:data_import:log_template-'.$import_template_id.'">'.getLabel('lbl_import_log_open').'</span>');
			} 
			
			$elm_done = '<section class="info attention body">'.$message.'</section>';
		}
		
		$return .= '<form id="f:data_import:run_import-'.$import_template_id.'">'.
			($legend ? '<div class="options"><legend>'.$legend.'</legend></div>' : '').
			$elm_done.
			$elm_check.
			'<input name="source_file_id" type="hidden" value="'.$source_file_id.'" />
			<menu>
				<input type="submit" value="'.getLabel('lbl_exit').'" name="exit" /><input '.($done ? 'class="hide"' : '').' type="submit" value="'.getLabel('lbl_next').'" class="continue" />
			</menu>
		</form>';
				
		return $return;
	}
	
	private function createImportTemplateLog($import_template_id) {

		$arr_import_template = cms_nodegoat_import::getImportTemplates($import_template_id);
		$elm_filter_columns = false;
		$colspan = 0;
		
		foreach ((array)$arr_import_template['columns'] as $arr_column) {
		
			$column_heading = $arr_column['column_heading'];

			if ($arr_column['use_as_filter'] && !$arr_column['use_object_id_as_filter']) {
				
				$elm_filter_columns .= '<th class="disable-sort">'.getLabel('lbl_filter').': '.$column_heading.'</th>';
				$colspan++;
				
			} else if (!$arr_column['use_as_filter'] && !$arr_column['use_object_id_as_filter']) {
				
				$elm_data_columns .= '<th class="disable-sort">'.$column_heading.'</th>';
				$colspan++;
			}
		}
		
		$colspan = $colspan + 2;
		
		if ($elm_filter_columns) {
			
			$colspan++;
		}
		
		$date = new DateTime($arr_import_template['import_template']['last_run']);
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
	
	private function createResultsCheck($arr_type_results) {
	
		$arr_types = StoreType::getTypes();
		
		foreach ((array)$arr_type_results as $type_id => $arr_results) {
			
			$return .= '<div class="options"><fieldset><legend>'.count($arr_results).' unmatched results in "'.Labels::parseTextVariables($arr_types[$type_id]['name']).'"</legend>
				<section class="info attention body">'.parseBody(getLabel('inf_import_unmatched_results')).'</section>
				<ul class="results">';
			
				foreach ((array)$arr_results as $arr_result) {
					
					$unique_id = uniqid();
					$string = '';
						
					foreach ((array)$arr_result['filter_values'] as $value) {
						
						if (!$value) {
							$value = '[no value]';
						}
						
						if ($string) {
							
							$string .= ', "'.mb_strimwidth($value, 0, 100, '...').'"';
							
						} else {
							
							$string = '"'.mb_strimwidth($value, 0, 100, '...').'"';
						}
					}
					
					$return .= '<li><label>'.$string.'</label><div class="fieldsets"><div>';
					
					if (count($arr_result['object_ids'])) {
						
						$arr_object_names = GenerateTypeObjects::getTypeObjectNames($type_id, (array)$arr_result['object_ids'], false);

						foreach ((array)$arr_result['object_ids'] as $object_id) {
							
							$return .= '<fieldset>
								<span id="y:data_view:view_type_object-'.$type_id.'_'.$object_id.'" class="a popup" title="Click to inspect object">'.$arr_object_names[$object_id].'</span> 
								<input type="hidden" name="filter_values" value="'.htmlspecialchars(json_encode($arr_result['filter_values'])).'" />
								<input type="hidden" name="type_id" value="'.$type_id.'" />
								<input type="hidden" name="object_id" value="'.$object_id.'" />
								<input type="radio" name="'.$unique_id.'" id="y:data_import:store_object_string_pair-0" value="'.$arr_result['string'].'" title="'.getLabel('inf_import_assign_object_to_string').'"/>
							</fieldset>';
						}
					}
						
					$return .= '<fieldset>
									<input type="hidden" name="filter_values" value="'.htmlspecialchars(json_encode($arr_result['filter_values'])).'" />
									<input type="hidden" name="type_id" value="'.$type_id.'" />
									<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.$type_id.'" name="object_id" value="" />
									<input type="search" id="y:data_filter:lookup_type_object-'.$type_id.'" class="autocomplete" value="" />
									<input type="radio" name="'.$unique_id.'" id="y:data_import:store_object_string_pair-0" value="'.$arr_result['string'].'" title="'.getLabel('inf_import_assign_object_to_string').'"/>
								</fieldset>
								<fieldset>
									<span>N/A</span> 
									<input type="hidden" name="filter_values" value="'.htmlspecialchars(json_encode($arr_result['filter_values'])).'" />
									<input type="hidden" name="type_id" value="'.$type_id.'" />
									<input type="hidden" name="object_id" value="no-reference" />
									<input type="radio" name="'.$unique_id.'" id="y:data_import:store_object_string_pair-0" value="'.$arr_result['string'].'" title="'.getLabel('inf_import_assign_no_object_to_string').'"/>
								</fieldset>
							</div>
						</div>
					</li>';
				}
			
			$return .= '</ul></fieldset></div>';
		}
		
		return $return;
	}

	public static function css() {
	
	
		$return = '.data_import form > div:first-of-type input[type="text"],
					.data_import form > div:first-of-type select,
					.data_import form > div:first-of-type textarea { width: 400px !important; } 
					.data_import fieldset input[name*=splitter] {width: 20px !important; }
					.data_import form > div:not(:first-of-type) select:nth-of-type(1),
					.data_import form > div:not(:first-of-type) select:nth-of-type(3) { width: 200px; } 
					
					.data_import form .options > fieldset > ul.results > li:nth-child(even) { background-color: rgba(0,0,0,0.03)} 
					.data_import form .options > fieldset > ul.results > li > label:before { display: none; } 
					.data_import form .options > fieldset > ul.results > li > label { padding: 0 8px; text-align: right; vertical-align: middle;} 
					.data_import form .options > fieldset > ul.results > li > .fieldsets > div { margin: 3px !important; } 
					.data_import form .options > fieldset > ul.results > li > .fieldsets > div > fieldset { background-color: #e1e1e1; padding: 4px 8px; margin: 4px !important;} 
					.data_import form .options > fieldset > ul.results > li > .fieldsets > div > fieldset span { line-height: 26px; }
					
					.data_import .import-log-template input.del {pointer-events: none; }';

		return $return;
	}
	
	public static function js() {

		$return = "SCRIPTER.static('.data_import', function(elm_scripter) {
		
			elm_scripter.on('ajaxloaded', function() {
				$('[id^=f\\\:data_import\\\:run_import-]').data({'target': elm_scripter, options: {'html': 'html'}});
			}).on('click', '[id^=d\\\:data_import\\\:data-] .edit', function() {
				var cur = $(this);
				cur.quickCommand(cur.closest('.tab-import').find('form'), {html: 'replace'});
			}).on('click', '[id^=d\\\:data_import\\\:data-template] .run_template', function() {
				$(this).quickCommand(elm_scripter, {html: 'html'});
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_import\\\:run_import]', function(elm_scripter) {
				
			elm_scripter.on('change', '[id^=y\\\:data_import\\\:check_template-]', function() {
				$(this).quickCommand($(this).closest('.options').next('div'));
			}).on('click', '[id^=y\\\:data_import\\\:store_object_string_pair-]', function() {
			
				var elm = $(this);
				var type_id = elm.siblings('[name=type_id]').val();
				var object_id = elm.siblings('[name=object_id]').val();
				var filter_values = elm.siblings('[name=filter_values]').val();

				if (!type_id || !object_id) {
				
					elm.prop('checked', false);
					return;
				}
				
				elm.closest('li').addClass('hide');
				elm.data('value', {type_id: type_id, object_id: object_id, filter_values: filter_values}).quickCommand(function() {
					var elm_parent = elm.closest('.results');
					elm.closest('li').remove();
					if (elm_parent.children().length == 0) {
						elm_parent.closest('.options').remove();
					}
				});
			});
		});
		
		SCRIPTER.dynamic('.import-log-template', function(elm_scripter) {
		
			elm_scripter.on('change', 'form input', function() {
				var target = $(this).closest('form').closest('div').next('div').find('table[id^=d\\\:data_import\\\:data_log]');
				var value = (target.data('value') ? target.data('value') : {});
				value.filter = JSON.stringify(serializeArrayByName($(this).closest('form')));
				target.data('value', value).dataTable('refresh');
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_import\\\:import_template]', function(elm_scripter) {		

			elm_scripter.on('click', '.add', function() {
			
				var elm_sorter = $(this).closest('li').next('li').find('.sorter')
				elm_sorter.sorter('addRow')
				elm_new = elm_sorter.children('li').last()
				elm_new.find('select').first().trigger('change');
				
			}).on('click', '.del', function() {
			
				$(this).closest('ul').closest('li').remove();
				
			}).on('click', '[id^=y\\\:data_import\\\:create_column-] input[name=options]', function() {
			
				$(this).closest('li').next('li').find('fieldset').removeClass('hide');
				$(this).addClass('hide');
				
			}).on('change', '[id^=y\\\:data_import\\\:create_column-] select', function() {
			
				var elm_column = $(this).parent();
				var values = {};
				
				values['template_mode'] = elm_column.closest('form').find('[name=template_mode]:checked').val();
				
				elm_column.children().each(function() {
					var elm_child = $(this);
					values[elm_child.attr('data-name')] = elm_child.val();
				});
				
				elm_column.data('value', values).quickCommand(function(data) {
		
					elm_column.parent().next('li').html(data[1]);	
					elm_column.parent().html(data[0]);
				});
				
			}).on('change init_data_template update_filter', '[name=template_source_file_id], [name=template_type_id]', function(e) {

				var elm_form = $(this).closest('form');
				var type_selector_elm = elm_form.find('[name=template_type_id]');
				var type_id = type_selector_elm.val();
				var source_file_selector_elm = elm_form.find('[name=template_source_file_id]');
				var source_file_id = source_file_selector_elm.val();

				if (e.type == 'init_data_template' && type_id) {
				
					return;
				}
				
				if (!type_id || !source_file_id) {
				
					elm_form.find('.columns').addClass('hide');
					elm_form.find('[name=template_mode]').attr('disabled', true);
					
					return;

				} else {
				
					elm_form.find('.columns').removeClass('hide');
					elm_form.find('[name=template_mode]').attr('disabled', false);
				}
				
				if (e.type == 'update_filter') {
					var target = elm_form.find('.filter [id^=y\\\:data_import\\\:set_columns-]');
				} else {
					var target = elm_form.find('[id^=y\\\:data_import\\\:set_columns-]');
				}
				
				var template_mode = elm_form.find('[name=template_mode]:checked').val();
				
				target.data('value', {source_file_id: source_file_id, target_type_id: type_id, template_mode: template_mode}).quickCommand(function(elm) {
					$(this).html(elm);
				});
				
			}).on('change', '[name=template_mode]', function() {	
			
				var elm = $(this);
				var mode = elm.val();
				var elm_form = elm.closest('form');
				
				if (mode == 'update' && !$(this).closest('form').find('.columns').hasClass('hide')) {
				
					elm_form.find('.filter').removeClass('hide');
					elm_form.find('.columns').find('input[name=options]').removeClass('hide');
					elm.closest('ul').find('[name=template_type_id]').trigger('update_filter');
					
				} else {
				
					elm_form.find('.filter').addClass('hide');
					elm_form.find('.columns').find('.column-options').addClass('hide');
				}
				
				$(this).closest('form').find('[name=filter_mode]:checked').trigger('update_data_template');
				
			}).on('change update_data_template', '[name=filter_mode]', function() {
			
				var filter = $(this).val();
				$(this).closest('li').nextAll('li').addClass('hide');
				
				if (filter == 'id') {
				
					$(this).closest('li').next('li').removeClass('hide');
					
				} else {
				
					$(this).closest('li').nextAll('li').slice(1).removeClass('hide');
				}
				
				$(this).closest('form').find('[name=template_type_id]').trigger('update_filter');
				
			}).on('keyup', '[name*=splitter]', function() {
				if($(this).val()) {
					$(this).next('select').removeClass('hide');
				} else {
					$(this).next('select').addClass('hide');
				}
			});
			
			elm_scripter.find('[name=template_type_id]').trigger('init_data_template');		
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == "add") {
				
			if ($id == 'file') {

				$return = '<form id="f:data_import:source_file_insert-0">'.$this->createSourceFile().'</form>';
				
			} else if ($id == 'template') { 
				
				$arr_source_files = cms_nodegoat_import::getSourceFiles();

				if (count((array)$arr_source_files) == 0) {
					
					msg(getLabel('msg_import_no_files'));
					
					return;
				}
				
				$return = '<form id="f:data_import:import_template_insert-0">'.$this->createImportTemplate().'</form>';
			}
		
			$this->html = $return;
		}
		
		if ($method == "edit" && $id) {
			
			$arr_id = explode('_', $id);
			$what = $arr_id[0];
			$id = $arr_id[1];
			
			if ($what == 'template') {
				$this->html = '<form id="f:data_import:import_template_update-'.$id.'">'.$this->createImportTemplate($id).'</form>';
			} else if ($what == 'file') {
				$this->html = '<form id="f:data_import:source_file_update-'.$id.'">'.$this->createSourceFile($id).'</form>';
			} else if ($what == 'pair') {
				$this->html = '<form id="f:data_import:string_object_pair_update-'.$id.'">'.$this->createStringObjectPair($id).'</form>';
			}
		}
		
		if ($method == "create_column") {

			$arr_column = $value;
			$arr_ids = explode('_', $id);
			$source_file_id = $arr_ids[0];
			$type_id = $arr_ids[1];
			$use_as_filter = $arr_ids[2];
			
			$arr_column['source_file_id'] = $source_file_id;
			$arr_column['target_type_id'] = $type_id;
			
			if ($use_as_filter) {
				$arr_column['use_as_filter'] = true;
			}

			$this->html = self::createColumn($arr_column);
		}
		
		if ($method == "set_columns") {

			if ($value['target_type_id']) {
				
				$type_id = $value['target_type_id'];
			}
		
			if ($value['source_file_id']) {
				
				$arr_column_headings = cms_nodegoat_import::getColumnHeadings($value['source_file_id']);

				$arr_columns = [];
				
				if ($id == 'filter') {
				
					$arr_columns = [['source_file_id' => $value['source_file_id'], 'arr_column_headings' => $arr_column_headings, 'target_type_id' => $type_id, 'use_as_filter' => true], ['source_file_id' => $value['source_file_id'], 'arr_column_headings' => $arr_column_headings, 'target_type_id' => $type_id, 'use_as_filter' => true]];
				
				} else if ($id == 'filter_id') {
					
					$arr_columns = [['source_file_id' => $value['source_file_id'], 'arr_column_headings' => $arr_column_headings, 'target_type_id' => $type_id, 'use_as_filter' => true, 'use_object_id_as_filter' => true]];
				
				} else {
					
					foreach ((array)$arr_column_headings as $column_heading => $row) {
						
						$arr_columns[] = ['source_file_id' => $value['source_file_id'], 'arr_column_headings' => $arr_column_headings, 'column_heading' => $column_heading, 'target_type_id' => $type_id];
					}
				}
			}
			
			$this->html = self::createColumns($value['source_file_id'], $type_id, $arr_columns, $id, $value['template_mode']);
		}
		
		if ($method == "run_template") {
			
			$arr_ids = explode('_', $id);
			$this->html = $this->createFileSelection($arr_ids[1]);
		}
		
		if ($method == "log_template") {
			
			$this->html = '<div class="import-log-template">'.$this->createImportTemplateLog($id).'</div>';
		}
		
		if ($method == "check_template") {

			$source_file_id = $value;
			$import_template_id = $id;
			
			$this->html = $this->createCheckTemplate($import_template_id, $source_file_id);
		}
		
		if ($method == "run_import" && $_POST['exit']) {
			
			$this->html = $this->contents(); 
			return;
		}
		
		if ($method == "run_import" && !$_POST['exit']) {
		
			$source_file_id = $_POST['source_file_id'];
			$import_template_id = $id;
			
			$compatible = cms_nodegoat_import::checkTemplateFileCompatibility($import_template_id, $source_file_id);
			
			if (!$compatible) {
				return false;
			}
			
			cms_nodegoat_import::emptyImportTemplateLog($import_template_id);
			
			$this->html = $this->createImportTemplateRun($import_template_id, $source_file_id);
		}
		
		if ($method == "store_object_string_pair") {

			if ($value['value_element'] && $value['type_id'] && $value['object_id']) {

				$arr_filter_values = json_decode($value['filter_values'], true);
					
				if (is_array($arr_filter_values)) {
						
					$filter_values = json_encode($arr_filter_values);
				}
				
				cms_nodegoat_import::addStringObjectPair($value['value_element'], $value['type_id'], $value['object_id'], $filter_values);
			}
		}
		
		//DATA

		if ($method == "data") {

			if ($id == 'template') {
				
				$arr_sql_columns = ['nodegoat_it.name', 'nodegoat_t.name', 'nodegoat_if.name', 'nodegoat_it.id'];
				$arr_sql_columns_search = ['nodegoat_it.name', 'nodegoat_t.name', 'nodegoat_if.name'];
				$arr_sql_columns_as = ['nodegoat_it.id', 'nodegoat_it.name', 'nodegoat_t.name AS type_name', 'nodegoat_if.name AS source_file_name'];
				
				if (Settings::get('domain_administrator_mode')) {
					$arr_sql_columns[0] = 'CASE WHEN nodegoat_t.label != \'\' THEN CONCAT(nodegoat_t.label, \' \', nodegoat_t.name) ELSE nodegoat_t.name END';
					$arr_sql_columns_search[0] = 'CONCAT(nodegoat_t.label, \' \', nodegoat_t.name)';
					$arr_sql_columns_as[] = 'nodegoat_t.label AS type_label';
				}
				
				$sql_table = DB::getTable('DEF_NODEGOAT_IMPORT_TEMPLATES')." nodegoat_it
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (nodegoat_t.id = nodegoat_it.type_id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_IMPORT_FILES')." nodegoat_if ON (nodegoat_if.id = nodegoat_it.source_file_id)
				";
				
				$sql_index = 'nodegoat_it.id';
				
			} else if ($id == 'file') {

				$arr_sql_columns = ['nodegoat_if.name', 'nodegoat_if.total_objects', 'nodegoat_if.id'];
				$arr_sql_columns_search = ['nodegoat_if.name'];
				$arr_sql_columns_as = ['nodegoat_if.id', 'nodegoat_if.name', 'nodegoat_if.total_objects'];
			
				$sql_table = DB::getTable('DEF_NODEGOAT_IMPORT_FILES').' nodegoat_if';
				$sql_index = 'nodegoat_if.id';
				
			} else if ($id == 'pair') {

				$arr_sql_columns = ['nodegoat_isop.string', 'nodegoat_t.name', 'nodegoat_isop.id', 'nodegoat_isop.filter_values'];
				$arr_sql_columns_search = ['nodegoat_isop.filter_values', 'nodegoat_t.name'];
				$arr_sql_columns_as = ['nodegoat_isop.id', 'nodegoat_isop.string', 'nodegoat_t.name AS type_name', 'nodegoat_isop.filter_values'];
				
				if (Settings::get('domain_administrator_mode')) {
					$arr_sql_columns[0] = 'CASE WHEN nodegoat_t.label != \'\' THEN CONCAT(nodegoat_t.label, \' \', nodegoat_t.name) ELSE nodegoat_t.name END';
					$arr_sql_columns_search[0] = 'CONCAT(nodegoat_t.label, \' \', nodegoat_t.name)';
					$arr_sql_columns_as[] = 'nodegoat_t.label AS type_label';
				}
				
				$sql_table = DB::getTable('DEF_NODEGOAT_IMPORT_STRING_OBJECT_PAIRS')." nodegoat_isop
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (nodegoat_t.id = nodegoat_isop.type_id)
				";
				
				$sql_index = 'nodegoat_isop.id';
			}
				
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index);
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				$arr_data['id'] = 'x:data_import:id-'.$id.'_'.$arr_row['id'];
				
				if ($id == 'pair') {	
				
					if ($arr_row['filter_values'] != '') {
						
						$arr_filter_values = json_decode($arr_row['filter_values'], true);
						$values = '';
						foreach ($arr_filter_values as $arr_filter_value) {
							$values .= (!$arr_filter_value ? '[no value]' : $arr_filter_value).', ';
						}
						$arr_data[] = substr($values, 0, -2);
						
					} else {
						$arr_data[] = Labels::parseTextVariables($arr_row['string']);
					}
					$arr_data[] = Labels::parseTextVariables($arr_row['type_label'] ? $arr_row['type_label'].' '.$arr_row['type_name'] : $arr_row['type_name']);
				
				} else if ($id == 'template') {
					
					$arr_data[] = Labels::parseTextVariables($arr_row['name']);
					$arr_data[] = Labels::parseTextVariables($arr_row['type_label'] ? $arr_row['type_label'].' '.$arr_row['type_name'] : $arr_row['type_name']);
					$arr_data[] = Labels::parseTextVariables($arr_row['source_file_name']);
					
					$result = cms_nodegoat_import::getImportTemplateLog($arr_row['id'], 0);
					
				} else if ($id == 'file') {
					
					$arr_data[] = Labels::parseTextVariables($arr_row['name']);
					$arr_data[] = Labels::parseTextVariables($arr_row['total_objects']);
				}
				
				$arr_data[] = '';
				
				$arr_data[] = ($id == 'template' ? ($result ? '<input type="button" class="data neutral popup" value="log" id="y:data_import:log_template-'.$arr_row['id'].'" />' : '').'<input type="button" class="data add run_template" value="run" />' : '').'<input type="button" class="data edit" value="edit" /><input type="button" class="data del msg del" value="del" /><input class="multi" value="'.$id.'_'.$arr_row['id'].'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		if ($method == "data_log") {

			$arr_ids = explode('_', $id);
			$import_template_id = $arr_ids[0];
			$template_has_filter = $arr_ids[1];
			
			$arr_import_template = cms_nodegoat_import::getImportTemplates($import_template_id);
			$type_id = $arr_import_template['import_template']['type_id'];
			
			$arr_log_filter = ($value && $value['filter'] ? json_decode($value['filter'], true) : []);
					
			$arr_sql_columns = ['nodegoat_itl.row_number', 'nodegoat_itl.object_id', 'nodegoat_itl.row_data', 'nodegoat_itl.row_filter', 'nodegoat_itl.row_results'];
			$arr_sql_columns_search = ['nodegoat_itl.object_id', 'nodegoat_itl.row_data', 'nodegoat_itl.row_filter', 'nodegoat_itl.row_results'];
			$arr_sql_columns_as = ['nodegoat_itl.row_number', 'nodegoat_itl.object_id', 'nodegoat_itl.row_data', 'nodegoat_itl.row_filter', 'nodegoat_itl.row_results'];

			$sql_table = DB::getTable('DATA_NODEGOAT_IMPORT_TEMPLATE_LOG')." nodegoat_itl";
			
			$sql_index = 'nodegoat_itl.row_number';
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
				
				$row_number = (int)$arr_row['row_number'] + 1;
				
				if ($arr_row_results['error']) {
					
					$arr_data[] = $row_number . ' <input type="button" class="data del" value="error" />';
					$arr_data['cell'][$count]['attr']['title'] = getLabel($arr_row_results['error']);
				
				} else {
					
					$arr_data[] = $row_number;
				}
				$count++;
				
				if ($template_has_filter) {

					foreach ($arr_import_template['columns'] as $arr_column) {

						if (!$arr_column['use_as_filter'] || $arr_column['use_object_id_as_filter']) {
							continue;
						}
						
						$arr_data[] = $arr_row_data[$arr_column['column_heading']];
						$count++;
					}
					
					if ($arr_row['row_filter'] != '') {
						
						$arr_row_filter = '<input type="button" id="y:data_import:view_filter-'.$import_template_id.'_'.$arr_row['row_number'].'" class="data edit popup" value="filter" />';
						
					} else {
						
						$arr_row_filter = '<input type="button" class="data del" value="no filter" />';
					}
					
					$arr_data[] = $arr_row_filter;
					$count++;
				}
					
				$arr_data[] = ($arr_row['object_id'] ? '<input type="button" id="y:data_view:view_type_object-'.$type_id.'_'.$arr_row['object_id'].'" class="popup data add" value="object" />' : '<input type="button" class="data del" value="n/a"/>');
				$count++;
				
				foreach ($arr_import_template['columns'] as $arr_column) {
					
					if ($arr_column['use_as_filter'] || $arr_column['use_object_id_as_filter']) {
						continue;
					}
					
					$column_heading = $arr_column['column_heading'];
					$elm = '';
					
					if ($arr_column['element_type_id']) {
						
						foreach ((array)$arr_row_results[$column_heading]['objects'] as $ref_type_id => $arr_ref_objects) {
							
							foreach ((array)$arr_ref_objects as $ref_object_id => $value) {
								
								$elm .= '<input type="button" id="y:data_view:view_type_object-'.$ref_type_id.'_'.$ref_object_id.'" class="popup data add" value="reference" />';
							}
						}
					}
					
					if ($arr_row_results[$column_heading]['options']['ignore_identical']) {
						
						$elm .= '<input type="button" class="data del" value="identical" />';
					}
					
					if ($arr_row_results[$column_heading]['options']['unmatched_reference']) {
						
						$elm .= '<input type="button" class="data del" value="n/a" />';
					}
					
					if ($arr_row_data[$column_heading]) {
						
						$elm .= ' <span>'.$arr_row_data[$column_heading].'</span>';
						
					} else if ($arr_row_results[$column_heading]['options']['ignore_empty']) {
							
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
			$row_number = $arr_ids[1];
			
			$arr_import_template = cms_nodegoat_import::getImportTemplates($import_template_id);
			$type_id = $arr_import_template['import_template']['type_id'];
			$arr_log = cms_nodegoat_import::getImportTemplateLog($import_template_id, $row_number);

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

		if (($method == "source_file_insert" || $method == "source_file_update" || $method == "import_template_insert" || $method == "import_template_update" || $method == "string_object_pair_update") && $_POST['discard']) {
			
			if ($method == "source_file_insert" || $method == "source_file_update") {
				$what = 'file';
			} else if ($method == "import_template_insert" || $method == "import_template_update") {
				$what = 'template';
			} else {
				$what = 'pair';
			}  
			
			$this->html = $this->createAddButtons($what);
			return;
		}
		
		if ($method == "source_file_insert" || ($method == "source_file_update" && (int)$id)) {

			$arr_details = ['name' => $_POST['name'], 'description' => $_POST['description']];
			
			cms_nodegoat_import::handleSourceFile((int)$id, $_POST['type'], $arr_details, ($_FILES ? $_FILES : ''));
					
			$this->html = $this->createAddButtons('file');
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "import_template_insert" || ($method == "import_template_update" && (int)$id)) {
			
			$arr_column_headings = []; 
			
			$template_mode = $_POST['template_mode'];
			$filter_mode = $_POST['filter_mode'];

			foreach ((array)$_POST['columns'] as $key => $value) {
				
				$use_as_filter = (int)$value['use_as_filter'];
			
				if (($use_as_filter && $template_mode != 'update') || 
					($filter_mode == 'filter' && ($use_as_filter && (int)$value['use_object_id_as_filter'])) || 
					($filter_mode == 'id' && ($use_as_filter && !(int)$value['use_object_id_as_filter']))) {
					
					continue;
				}
							
				$ignore_when = (int)$value['ingore_empty'] + (int)$value['ingore_identical'];

				$arr_column_headings[] = [
					'id' => $value['column_id'],
					'column_heading' => $value['column_heading'],
					'cell_splitter' => $value['cell_splitter'],
					'generate_from_split' => $value['generate_from_split'],
					'target_type_id' => (int)$value['target_type_id'],
					'element_id' => $value['element_id'],
					'element_type_id' => (int)$value['element_type_id'],
					'element_type_element_id' => $value['element_type_element_id'],
					'element_type_object_sub_id' => (int)$value['element_type_object_sub_id'],
					'use_as_filter' => $use_as_filter,
					'use_object_id_as_filter' => (int)$value['use_object_id_as_filter'],
					'overwrite' => ($value['write'] == 'overwrite' ? 1 : 0),
					'ignore_when' =>(int)$ignore_when
				];
			}

			$arr_details = ['name' => $_POST['name'], 'source_file_id' => (int)$_POST['template_source_file_id'], 'type_id' => (int)$_POST['template_type_id'], 'use_log' => (int)$_POST['use_log'], 'arr_column_headings' => $arr_column_headings];
		
			cms_nodegoat_import::handleImportTemplate((int)$id, $arr_details);
			
			$this->html = self::createAddButtons('template');
			$this->refresh_table = true;
			$this->msg = true;
		}
	
		if ($method == "string_object_pair_update" && (int)$id) {

			cms_nodegoat_import::updateStringObjectPair($id, $_POST['object_id']);
			
			$this->html = '<form></form>';
			$this->refresh_table = true;
			$this->msg = true;
		}
	
		if ($method == "del" && $id) {
			
			foreach ((array)$id as $combined_id) {
				
				$arr_id = explode('_', $combined_id);
				$what = $arr_id[0];
				$element_id = $arr_id[1];
				
				if ($what == 'template') {
					cms_nodegoat_import::delImportTemplate($element_id); 
				} else if ($what == 'file') {
					cms_nodegoat_import::delSourceFile($element_id); 
				} else if ($what == 'pair') {
					cms_nodegoat_import::delStringObjectPair($element_id); 
				}
			}
			
			$this->refresh_table = true;				
			$this->msg = true;
		}
	}	
}
