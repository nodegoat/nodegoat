<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_pattern_pairs extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Pattern Pairs';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'data_filter' => [],
		'data_view' => []
	];
		
	public function contents() {
		
		$arr_types_all = StoreType::getTypes();
		
		foreach ($arr_types_all as $cur_type_id => $arr_type) {
			
			if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
				unset($arr_types_all[$cur_type_id]);
			}
		}
		
		$return = '		
		'.$this->createAddPatternTypeObjectPair().'
		
		<form class="options" id="y:data_pattern_pairs:set_filter-0">'
			.'<label>'.getLabel('lbl_type').':</label><select name="type_id">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all, false, true)).'</select>'
			.'<label>'.getLabel('lbl_pattern_composition').':</label><select name="composition">'.cms_general::createDropdown(PatternEntity::getPatternCompositionLevels(), '', true, null, null, ['strict' => true]).'</select>'
		.'</form>
		
		<table class="display" id="d:data_pattern_pairs:data-0">
			<thead>
				<tr>			
					<th class="max">'.getLabel('lbl_pattern').'</th>
					<th>'.getLabel('lbl_type').'</th>
					<th>'.getLabel('lbl_pattern_composition').'</th>
					<th data-sort="desc-0">'.getLabel('lbl_added').'</th>
					<th class="disable-sort menu" id="x:data_pattern_pairs:id-pattern_object_pair"><div class="hide-edit hide"><input type="button" class="data msg del" value="d" title="'.getLabel('lbl_delete').'" /></div><input type="button" class="data neutral" value="multi" /><input type="checkbox" class="multi all" value="" /></th>
				</tr> 
			</thead>
			<tbody>
				<tr>
					<td colspan="4" class="empty">'.getLabel('msg_loading_server_data').'</td>
				</tr>
			</tbody>
		</table>';
	
		return $return;
	}
	
	protected function createAddPatternTypeObjectPair() {
		
		$return = '<form id="f:data_pattern_pairs:add-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_pattern_pair').'" />
			</menu>
		</form>';
		
		return $return;
	}
	
	protected function createPatternTypeObjectPair($type_id = false, $str_identifier = false) {
		
		$arr_pattern_object_pair = [];
		$str_object_name = '';
		$arr_pattern = [];
		$str_pattern = '';
		
		if ($type_id) {
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			if ($str_identifier) {
				
				$arr_pattern_object_pair = StorePatternsTypeObjectPair::getTypeObjectPairs($type_id, $str_identifier);
				$arr_object_name = FilterTypeObjects::getTypeObjectNames($type_id, [$arr_pattern_object_pair['object_id']], false);
				$str_object_name = $arr_object_name[$arr_pattern_object_pair['object_id']];
				
				$arr_pattern = $arr_pattern_object_pair['pattern_value'];
				$pattern = new PatternEntity($arr_pattern, $arr_pattern_object_pair['composition']);
				$arr_pattern_summary = $pattern->getPatternSummary();
				$str_pattern = $arr_pattern_summary['name'];
			}
		}

		$return = '<h1>'.($str_identifier ? Labels::parseTextVariables($arr_type_set['type']['name']).': '.$str_pattern : getLabel('lbl_pattern_pair')).'</h1>
		
		<div class="options">
		
			<fieldset><ul>
				<li>
					<label>'.getLabel('lbl_pattern_composition').'</label>
					<div>';
					
					$arr_composition_levels = PatternEntity::getPatternCompositionLevels();
					
					$num_composition = (!$str_pattern ? PatternEntity::PATTERN_COMPOSITION_MATCH : $arr_pattern_object_pair['composition']);
					
					if ($num_composition == PatternEntity::PATTERN_COMPOSITION_MAP) {
						
						$return .= '<label>'.$arr_composition_levels[$num_composition]['name'].'</label>';
					} else {
						
						unset($arr_composition_levels[PatternEntity::PATTERN_COMPOSITION_MAP]);
						
						$return .= Labels::parseTextVariables(cms_general::createSelectorRadio($arr_composition_levels, 'composition', $num_composition));
					}
					
					$return .= '</div>
				</li>';
				
				if ($num_composition == PatternEntity::PATTERN_COMPOSITION_MAP) {
					
					$return .= '<li>
						<label>'.getLabel('lbl_pattern').'</label>
						<div>'
							.'<code class="input">'.$str_pattern.'</code>'
						.'</div>
					</li>';
				} else {
					
					$arr_text_boundaries = PatternEntity::getTextBoundaryOptions();
					
					$return .= '<li>
						<label>'.getLabel('lbl_pattern').'</label>
						<div>'
							.'<input type="text" name="pattern" value="'.strEscapeHTML($str_pattern).'" />'
						.'</div>
					</li>
					<li>
						<label>'.getLabel('lbl_pattern_context').'</label>
						<div>'
							.'<label>'.getLabel('lbl_before').'</label><select name="context[before]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_text_boundaries, ($arr_pattern['context']['before'] ?? false), true)).'</select>'
							.'<label>'.getLabel('lbl_after').'</label><select name="context[after]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_text_boundaries, ($arr_pattern['context']['after'] ?? false), true)).'</select>'
						.'</div>
					</li>';
				}
				$return .= '<li>
					<label>'.getLabel('lbl_type').'</label>';
					
					if ($type_id) {
						
						$return .= '<div>'.$arr_type_set['type']['name'].'</div>';
					} else {
						
						$arr_types_all = StoreType::getTypes();
				
						foreach ($arr_types_all as $cur_type_id => $arr_type) {
							
							if ($arr_type['class'] == StoreType::TYPE_CLASS_SYSTEM) {
								unset($arr_types_all[$cur_type_id]);
							}
						}
					
						$return .= '<select class="update_object_type" name="type_id">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types_all, $type_id, true)).'</select>';
						
						$this->validate['type_id'] = 'required';
					}
				$return .= '</li>
				<li>
					<label>'.getLabel('lbl_object').'</label>
					<div>'
						.'<input type="hidden" id="y:data_filter:lookup_type_object_pick-'.($type_id ?: '0').'" name="object_id" value="'.$arr_pattern_object_pair['object_id'].'" />'
						.'<input type="text" id="y:data_filter:lookup_type_object-'.($type_id ?: '0').'" class="autocomplete" value="'.$str_object_name.'" />'
					.'</div>
				</li>
			</ul></fieldset>
			
		</div>
		
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_pattern_pair').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
		
		$this->validate['object_id'] = 'required';
				
		return $return;	
	}
	
	public static function css() {
				
		$return = '
			.data_pattern_pairs code { font-family: var(--font-mono); padding: 4px 8px; background-color: #e1e1e1; }
		';

		return $return;
	}
	
	public static function js() {

		$return = "	
		SCRIPTER.static('.data_pattern_pairs', function(elm_scripter) {
		
			elm_scripter.on('click', '[id^=d\\\:data_pattern_pairs\\\:data-] .edit', function() {
			
				COMMANDS.quickCommand(this, elm_scripter.children('form').first(), {html: 'replace'});
			}).on('change', '[id=y\\\:data_pattern_pairs\\\:set_filter-0] [name]', function() {
				
				var elm_command = this.closest('form');
				
				COMMANDS.setData(elm_command, serializeArrayByName(elm_command));
				COMMANDS.quickCommand(elm_command);
			});
		});
				
		SCRIPTER.dynamic('[id^=f\\\:data_pattern_pairs\\\:insert-], [id^=f\\\:data_pattern_pairs\\\:update-]', function(elm_scripter) {
		
			elm_scripter.on('ajaxloaded scripter', function(e) {
				
				runElementSelectorFunction(e.detail.elm, '[name=\"composition\"]:checked', function(elm_found) {
					SCRIPTER.triggerEvent(elm_found, 'update_pattern_pair');
				});
			}).on('change update_pattern_pair', '[name=\"composition\"]', function() {
				
				const elm_target = elm_scripter.find('[name=\"context[before]\"]').closest('li');
				
				if (this.value == ".PatternEntity::PATTERN_COMPOSITION_MATCH_CONTEXT.") {
					elm_target.removeClass('hide');
				} else {
					elm_target.addClass('hide');
				}
			});
		});
		
		SCRIPTER.dynamic('[id^=f\\\:data_pattern_pairs\\\:insert-], [id^=f\\\:data_pattern_pairs\\\:update-]', 'filtering');
			
		SCRIPTER.dynamic('pattern_object_pair_store', function(elm_scripter) {
					
			elm_scripter.on('click', '[id^=y\\\:data_pattern_pairs\\\:store-]', function() {
			
				var elm = $(this);
				var elm_fieldset = elm.closest('fieldset');
				var type_id = elm_fieldset.find('[name=type_id]').val();
				var str_identifier = this.value;
				var object_id = elm_fieldset.find('[name=object_id]').val();
				var pattern_value = elm_fieldset.find('[name=pattern_value]').val();

				if (!type_id || !object_id) {
				
					elm.prop('checked', false);
					return;
				}
				
				elm.closest('li').addClass('hide');
				
				COMMANDS.setData(this, {type_id: type_id, identifier: str_identifier, object_id: object_id, pattern_value: pattern_value});
				COMMANDS.quickCommand(this, function() {
				
					var elm_parent = elm.closest('.results');
					elm.closest('li').remove();
					if (elm_parent.children().length == 0) {
						elm_parent.closest('.options').remove();
					}
				});
			});
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
				
		if ($method == "add") {
			
			$this->html = '<form id="f:data_pattern_pairs:insert-0">'.$this->createPatternTypeObjectPair().'</form>';
		}
		
		if ($method == "edit" && $id) {
			
			$arr_id = explode('_', $id);
			$type_id = $arr_id[0];
			$str_identifier = $arr_id[1];
			
			$this->html = '<form id="f:data_pattern_pairs:update-'.$type_id.'_'.$str_identifier.'">'.$this->createPatternTypeObjectPair($type_id, $str_identifier).'</form>';
		}
		
		if ($method == "store") {
			
			$type_id = $value['type_id'];
			$str_identifier = $value['identifier'];
			$object_id = $value['object_id'];
			
			if (!$str_identifier || !$type_id || !$object_id) {
				error(getLabel('msg_missing_information'));
			}
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER || !static::checkClearanceType($type_id)) {
				error(getLabel('msg_not_allowed'));
			}

			$arr_pattern_value = JSON2Value($value['pattern_value']);
							
			if ($object_id === StorePatternsTypeObjectPair::PATTERN_STR_NO_REFERENCE) {
				$object_id = StorePatternsTypeObjectPair::PATTERN_NO_REFERENCE;
			} else if ($object_id === StorePatternsTypeObjectPair::PATTERN_STR_IGNORE) {
				$object_id = StorePatternsTypeObjectPair::PATTERN_IGNORE;
			} else {
				$object_id = (int)$object_id;
			}
			
			$store_pair = new StorePatternsTypeObjectPair();
			
			$store_pair->storeTypeObjectPair($type_id, $str_identifier, $object_id, $arr_pattern_value);
			$store_pair->commitPairs();
		}
		
		if ($method == "set_filter") {
			
			SiteEndVars::setFeedback('filter_pattern_pairs', $value, true);
				
			$this->refresh_table = true;
		}

		if ($method == "data") {

			$arr_sql_columns = ['nodegoat_ptop.pattern_value', 'nodegoat_t.name', 'nodegoat_ptop.composition', 'nodegoat_ptop.date'];
			$arr_sql_columns_search = [['field' => 'nodegoat_ptop.pattern_value', 'json' => true], 'nodegoat_t.name'];
			$arr_sql_columns_as = ['nodegoat_t.name AS type_name', 'nodegoat_ptop.composition', 'nodegoat_ptop.date', 'LOWER(HEX(nodegoat_ptop.identifier)) AS identifier', 'nodegoat_ptop.pattern_value', 'nodegoat_ptop.type_id'];
			
			if (Settings::get('domain_administrator_mode')) {
				$arr_sql_columns[1] = 'CASE WHEN nodegoat_t.label != \'\' THEN CONCAT(nodegoat_t.label, \' \', nodegoat_t.name) ELSE nodegoat_t.name END';
				$arr_sql_columns_search[1] = 'CONCAT(nodegoat_t.label, \' \', nodegoat_t.name)';
				$arr_sql_columns_as[] = 'nodegoat_t.label AS type_label';
			}
			
			$sql_table = DB::getTable('DEF_NODEGOAT_PATTERN_TYPE_OBJECT_PAIRS')." nodegoat_ptop
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_TYPES')." nodegoat_t ON (nodegoat_t.id = nodegoat_ptop.type_id)
			";
			
			$sql_index = 'nodegoat_ptop.type_id, nodegoat_ptop.identifier';
			$sql_where = 'TRUE';
			
			$arr_filter_pattern_pairs = (SiteStartVars::getFeedback('filter_pattern_pairs') ?: []);
			
			if (!empty($arr_filter_pattern_pairs['type_id'])) {
				
				$sql_where .= " AND nodegoat_ptop.type_id = ".(int)$arr_filter_pattern_pairs['type_id'];
			}
			if (isset($arr_filter_pattern_pairs['composition']) && $arr_filter_pattern_pairs['composition'] !== '') {
				
				$sql_where .= " AND nodegoat_ptop.composition = ".(int)$arr_filter_pattern_pairs['composition'];
			}

			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, $arr_sql_columns_search, $arr_sql_columns_as, $sql_table, $sql_index, '', '', $sql_where);
			
			$arr_pattern_composition_levels = PatternEntity::getPatternCompositionLevels();
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
			
				$arr_data = [];
				
				$str_id = $arr_row['type_id'].'_'.$arr_row['identifier'];
				$arr_data['id'] = 'x:data_pattern_pairs:pattern_object_pair-'.$str_id;

				$arr_pattern = JSON2Value($arr_row['pattern_value']);
				$pattern = new PatternEntity($arr_pattern, $arr_row['composition']);
				$arr_pattern_summary = $pattern->getPatternSummary();
				
				$arr_data[] = $arr_pattern_summary['name'];
				$arr_data[] = Labels::parseTextVariables($arr_row['type_label'] ? $arr_row['type_label'].' '.$arr_row['type_name'] : $arr_row['type_name']);
				
				$arr_data[] = $arr_pattern_composition_levels[$arr_row['composition']]['name'];
				$arr_data[] = date('d-m-Y H:i', strtotime($arr_row['date']));
				
				$arr_data[] = '<input type="button" class="data edit" value="edit" />'
					.'<input type="button" class="data msg del" value="del" />'
					.'<input class="multi" value="'.$str_id.'" type="checkbox" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		if (($method == "insert"  || $method == "update") && $this->is_discard) {
						
			$this->html = $this->createAddPatternTypeObjectPair();
			return;
		}
		
		if ($method == "insert") {
			
			if ($_POST['composition'] == PatternEntity::PATTERN_COMPOSITION_MATCH_CONTEXT) {
				$arr_pattern = PatternEntity::createPattern($_POST['pattern'], $_POST['context']);
			} else {
				$arr_pattern = PatternEntity::createPattern($_POST['pattern']);
			}
			
			$str_identifier = StorePatternsTypeObjectPair::getPatternIdentifier($arr_pattern);
			
			$type_id = $_POST['type_id'];
			$object_id = (int)$_POST['object_id'];
			
			if (!$type_id || !$object_id) {
				error(getLabel('msg_missing_information'));
			}
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER || !static::checkClearanceType($type_id)) {
				error(getLabel('msg_not_allowed'));
			}
			
			$store_pair = new StorePatternsTypeObjectPair();
			
			$store_pair->storeTypeObjectPair($type_id, $str_identifier, $object_id, $arr_pattern);
			$store_pair->commitPairs();
			
			$this->html = $this->createAddPatternTypeObjectPair();
			
			$this->refresh_table = true;
			$this->msg = true;
		}

		if ($method == "update" && $id) {

			$arr_id = explode('_', $id);

			$type_id = $arr_id[0];
			$str_identifier = $arr_id[1];
			$object_id = (int)$_POST['object_id'];
			
			if (!$str_identifier || !$object_id) {
				error(getLabel('msg_missing_information'));
			}
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER || !static::checkClearanceType($type_id)) {
				error(getLabel('msg_not_allowed'));
			}
			
			$store_pair = new StorePatternsTypeObjectPair();
			
			if (isset($_POST['composition'])) {
				
				if ($_POST['composition'] == PatternEntity::PATTERN_COMPOSITION_MATCH_CONTEXT) {
					$arr_pattern = PatternEntity::createPattern($_POST['pattern'], $_POST['context']);
				} else {
					$arr_pattern = PatternEntity::createPattern($_POST['pattern']);
				}
				
				$str_identifier_update = StorePatternsTypeObjectPair::getPatternIdentifier($arr_pattern);
				
				if ($str_identifier_update != $str_identifier) {
					
					$store_pair->delTypeObjectPair($type_id, $str_identifier);
					$store_pair->storeTypeObjectPair($type_id, $str_identifier_update, $object_id, $arr_pattern);
					$store_pair->commitPairs();
					
					$str_identifier = false;
				}
			}
	
			if ($str_identifier) {
			
				$store_pair->updateTypeObjectPair($type_id, $str_identifier, $object_id);
				$store_pair->commitPairs();
			}
			
			$this->html = $this->createAddPatternTypeObjectPair();
			
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && $id) {
			
			foreach ((array)$id as $cur_id) {
				
				$arr_id = explode('_', $cur_id);
				
				$type_id = $arr_id[0];
				$str_identifier = $arr_id[1];
				
				if (!$str_identifier) {
					error(getLabel('msg_missing_information'));
				}
				
				if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER || !static::checkClearanceType($type_id)) {
					error(getLabel('msg_not_allowed'));
				}
				
				$store_pair = new StorePatternsTypeObjectPair();
				
				$store_pair->delTypeObjectPair($type_id, $str_identifier);
				$store_pair->commitPairs();
			
				$this->refresh_table = true;				
				$this->msg = true;
			}
		}
	}
	
	public static function checkClearanceType($type_id, $error = true) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$clearance = (
			$arr_type_set
			&& ($_SESSION['NODEGOAT_CLEARANCE'] >= $arr_type_set['type']['clearance_edit'])
		);
		
		if (!$clearance && $error) {
			error(getLabel('msg_not_allowed'));
		}
		
		return $clearance;
	}
}
