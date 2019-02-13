<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_analysis extends base_module {

	public static function moduleProperties() {
		static::$label = 'Data Analysis';
		static::$parent_label = 'nodegoat';
	}
	
	public function createAnalysisRun($type_id, $arr_analysis) {
	
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_analysis_storage = [];

		$arr_analyses = self::createTypeAnalysesSelection($type_id);
		
		$arr_analysis_storage[0] = $arr_analyses[0]; // Workspace
		
		foreach ($arr_analyses as $cur_analysis_id => $arr_cur_analysis) {
			
			if ($arr_analysis['algorithm'] != $arr_cur_analysis['algorithm']) { // Only stored analyses with the same algorithm
				continue;
			}
			
			// Remove IDs to compare
			$cur_id = $arr_cur_analysis['id'];
			$arr_cur_analysis['id'] = 0;
			$arr_analysis['id'] = 0;
			
			$arr_cur_analysis['user_id'] = 0;
			$arr_analysis['user_id'] = 0;
			
			if (self::parseTypeAnalysis($type_id, $arr_cur_analysis) !== $arr_analysis) {
				continue;
			}
			
			$arr_cur_analysis['id'] = $cur_id; // Restore main ID
			
			$arr_analysis_storage[] = $arr_cur_analysis;
		}
		
		$return = '<div class="options">
			<fieldset><legend>'.getLabel('lbl_analysis').'</legend><ul>
				<li>
					<label>'.getLabel('lbl_update').'</label>
					<span>'
						.'<select name="storage">'.Labels::parseTextVariables(cms_general::createDropdown($arr_analysis_storage, false, false, 'label')).'</select>'
					.'</span>
				</li>
			</ul></fieldset>
		</div>';
		
		return $return;
	}
			
	public function createAnalysisSettings($type_id, $arr_analysis = [], $arr_analysis_context = []) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		Labels::setVariable('icon', '<span class="icon">'.getIcon('info-point').'</span>');
		
		$return = '<div class="tabs">
			<ul>
				<li><a href="#analysis-analysis">'.getLabel('lbl_analysis').': '.Labels::parseTextVariables($arr_type_set['type']['name']).'</a></li>
				<li><a href="#analysis-context">'.getLabel('lbl_context').'</a></li>
			</ul>
			
			<div>
				<section class="info attention">
					'.Labels::parseTextVariables(getLabel('inf_analysis_introduction')).'
				</section>
				'.$this->createTypeAnalysis($type_id, $arr_analysis).'
			</div>
			
			<div>
				'.$this->createTypeAnalysisContext($type_id, $arr_analysis_context).'
			</div>
		</div>';
			
			
		return $return;
	}
	
	public function createTypeAnalysis($type_id, $arr_analysis = []) {
				
		$html_type_network = data_model::createTypeNetwork($type_id, false, false, ['references' => 'both', 'network' => ['dynamic' => true, 'object_sub_locations' => true], 'value' => $arr_analysis['scope'], 'name' => 'analysis[scope]', 'descriptions' => false, 'functions' => ['filter' => true, 'collapse' => false]]);
		$arr_algorithms = AnalyseTypeObjects::getAlgorithms();
		
		$has_graph_database = Settings::get('graph_analysis_service');
		
		$arr_analysis_weighted = [];
		$arr_analysis_settings = [];
		
		if ($arr_analysis['settings']) {
			
			$arr_analysis_weighted = (array)$arr_analysis['settings']['weighted'];
			unset($arr_analysis['settings']['weighted']);
			$arr_analysis_settings = $arr_analysis['settings'];
		}
		
		$arr_weighted_options = [
			['id' => 'unweighted', 'name' => getLabel('lbl_analysis_unweighted'), 'title' => getLabel('inf_analysis_unweighted')],
			['id' => 'closeness', 'name' => getLabel('lbl_analysis_weighted_closeness'), 'title' => getLabel('inf_analysis_weighted_closeness')],
			['id' => 'distance', 'name' => getLabel('lbl_analysis_weighted_distance'), 'title' => getLabel('inf_analysis_weighted_distance')]
		];
		
		$arr_html_options = [];
				
		foreach ($arr_algorithms as $algorithm => $arr_algorithm) {
			
			$func_options = $arr_algorithm['options'];
			$arr_options = [];
			$is_weighted = false;
			
			if ($arr_algorithm['graph'] && !$has_graph_database) {
				
				$arr_options = [
					'' => '<section class="info attention">'.getLabel('msg_analysis_no_graph').'</section>'
				];
			} else {
				
				if ($func_options) {
			
					$arr_options = $func_options($type_id, 'analysis[algorithm_settings]['.$algorithm.']', ($arr_analysis['algorithm'] == $algorithm ? $arr_analysis_settings : []));
				}
				
				if (!$arr_options) {
					
					$arr_options = [getLabel('lbl_options') => '<section class="info attention">'.getLabel('msg_no_options').'</section>'];
				}
				
				$is_weighted = $arr_algorithm['weighted'];
			}
						
			foreach ($arr_options as $str_label => $html_option) {
				
				$html_option = '<label>'.$str_label.'</label><div>'.$html_option.'</div>';
				
				if (!$arr_html_options[$algorithm]) { // Add more parameters (i.e. weighted) to the first option of the algorithm
					
					$arr_html_options[$algorithm] .= '<li class="section-'.$algorithm.'" data-weighted="'.($is_weighted ? '1' : '0').'">'.$html_option.'</li>';
				} else {
					
					$arr_html_options[$algorithm] .= '<li class="section-'.$algorithm.'">'.$html_option.'</li>';
				}
			}
		}
		
		$return = '<div class="analysis tabs">
			<ul>
				<li class="no-tab"><span>'
					.'<input type="button" id="y:data_analysis:store_analysis-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_analysis_store').'" />'
					.'<input type="button" id="y:data_analysis:open_analysis-'.$type_id.'" class="data add popup" value="open" title="'.getLabel('inf_analysis_open').'" />'
				.'</span></li>
				<li><a href="#">'.getLabel('lbl_analyse').'</a></li>
				<li><a href="#">'.getLabel('lbl_scope').'</a></li>
			</ul>
			
			<div>
				<div class="options">
					<fieldset><ul>
						<li>
							<label>'.getLabel('lbl_algorithm').'</label>
							<div><select name="analysis[algorithm]">'.cms_general::createDropdown($arr_algorithms, $arr_analysis['algorithm']).'</select></div>
						</li>
						'.implode('', $arr_html_options).'
						<li class="section-weighted">
							<label>'.getLabel('lbl_analysis_weighted').'</label>
							<div>
								<section class="info attention">'.getLabel('inf_analysis_weighted').'</section>
								<div>'.cms_general::createSelectorRadio($arr_weighted_options, 'analysis[weighted_settings][mode]', ($arr_analysis_weighted['mode'] ?: 'unweighted')).'</span>
							</div>
						</li>
						<li class="section-weighted">
							<label>'.getLabel('lbl_analysis_weighted_max').'</label>
							<div><input name="analysis[weighted_settings][max]" type="number" step="1" min="0" value="'.($arr_analysis_weighted['max'] ?: '').'" /></div>
						</li>
					</ul></fieldset>
				</div>
			</div>

			<div>
				<div class="options scope">
					'.$html_type_network.'
				</div>
			</div>

		</div>';
		
		return $return;
	}

	public function createTypeAnalysisContext($type_id, $arr_analysis_context = []) {
		
		$arr_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids);
	
		$return = '<div class="analysis-context tabs">
			<ul>
				<li class="no-tab"><span>'
					.'<input type="button" id="y:data_analysis:store_analysis_context-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_analysis_context_store').'" />'
					.'<input type="button" id="y:data_analysis:open_analysis_context-'.$type_id.'" class="data add popup" value="open" title="'.getLabel('inf_analysis_context_open').'" />'
				.'</span></li>
				<li><a href="#">'.getLabel('lbl_include').'</a></li>
			</ul>
			
			<div>
				<div class="options">
		
					<section class="info attention">'.getLabel('inf_analysis_context').'</section>
					
					<fieldset><ul>
						<li>
							<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
						</li>
						<li>
							<label>'.getLabel('lbl_analysis').'</label>';
							
							$arr_analysis_context_includes = $arr_analysis_context['include'];
							if ($arr_analysis_context_includes) {
								array_unshift($arr_analysis_context_includes, []); // Empty run for sorter source
							} else {
								$arr_analysis_context_includes = [[], []]; // Empty run for sorter source
							}
							
							$arr_sorter = [];
							
							foreach ($arr_analysis_context_includes as $key => $arr_include) {

								$unique = uniqid('array_');
								
								$arr_sorter[] = [
									'value' => '<div>'
										.'<select name="analysis_context[include]['.$unique.'][analysis_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_analyses, $arr_include['analysis_id'], true, 'label')).'</select>'
									.'</div>',
									'source' => ($key == 0 ? true : false)
								];
							}
							
							$return .= cms_general::createSorter($arr_sorter);
							
						$return .= '</li>
					</ul></fieldset>
				</div>
				
			</div>
		</div>';
		
		return $return;
	}
		
	private function createSelectAnalysis($type_id, $store = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<fieldset><legend>'.getLabel(($store ? 'lbl_save' : 'lbl_select')).'</legend>
			<ul>
				<li><label>'.getLabel('lbl_analysis').'</label><span id="x:custom_projects:analysis_storage-'.(int)$type_id.'">'
					.'<select name="analysis_id">'.Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), false, true, 'label')).'</select>'
					.($store ?
						'<input type="button" class="data add popup add_analysis_storage" value="store" />'
						.'<input type="button" class="data del msg del_analysis_storage" value="del" />'
					: '')
				.'</span></li>
			</ul>
		</fieldset>';

		return $return;
	}
	
	private function createSelectAnalysisContext($type_id, $store = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<fieldset><legend>'.getLabel(($store ? 'lbl_save' : 'lbl_select')).'</legend>
			<ul>
				<li><label>'.getLabel('lbl_analysis_context').'</label><span id="x:custom_projects:analysis_context_storage-'.(int)$type_id.'">'
					.'<select name="analysis_context_id">'.Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeAnalysesContexts($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), false, true, 'label')).'</select>'
					.($store ?
						'<input type="button" class="data add popup add_analysis_context_storage" value="store" />'
						.'<input type="button" class="data del msg del_analysis_context_storage" value="del" />'
					: '')
				.'</span></li>
			</ul>
		</fieldset>';

		return $return;
	}

	public static function createTypeAnalysisTableHeader($type_id, $arr_analysis, $arr_analysis_context) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '';
		
		if ($arr_analysis) {
			$return .= '<span class="analysis-workspace" title="'.htmlspecialchars(getLabel('lbl_analysis').' <strong>• '.getLabel('lbl_workspace').'</strong>').'">A</span>';
		}
		
		if ($arr_analysis_context['include']) {

			$arr_analysis_context_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, array_keys($arr_analysis_context['include']), $arr_use_project_ids);
			
			foreach ($arr_analysis_context_analyses as $arr_analysis_context_analysis) {
				
				$return .= '<span title="'.htmlspecialchars(getLabel('lbl_analysis_context').' <strong>'.$arr_analysis_context_analysis['label'].'</strong>').'">A</span>';
			}
		}
		
		return $return;
	}
	
	public static function createTypeAnalysisViewValue($type_id, $arr_analysis, $arr_analysis_context, $analysis_value = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_analysis_value = [];
		
		if ($analysis_value !== false) {
			$arr_analysis_value = explode(' | ', $analysis_value);
		}
		
		$count = 0;
		$return = '';
			
		if ($arr_analysis) {
			
			$return .= '<span class="analysis-workspace" title="'.htmlspecialchars(getLabel('lbl_analysis').' <strong>• '.getLabel('lbl_workspace').'</strong>').'">'.$arr_analysis_value[$count].'</span>';
			$count++;
		}
		
		if ($arr_analysis_context['include']) {

			$arr_analysis_context_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, array_keys($arr_analysis_context['include']), $arr_use_project_ids);
			
			foreach ($arr_analysis_context_analyses as $arr_analysis_context_analysis) {
				
				$return .= '<span title="'.htmlspecialchars(getLabel('lbl_analysis_context').' <strong>'.$arr_analysis_context_analysis['label'].'</strong>').'">'.$arr_analysis_value[$count].'</span>';
				$count++;
			}
		}
			
		$return = '<div class="analysis-object">'.$return.'</div>';
		
		return $return;
	}
	
	public static function createTypeAnalysesSelection($type_id) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_object_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids);
		
		foreach ($arr_object_analyses as $analysis_id => &$arr_analysis) {
			
			$arr_analysis['id'] = $arr_analysis['id'].'_'.(int)$arr_analysis['user_id'];
		}
		unset($arr_analysis);
		
		array_unshift($arr_object_analyses, ['id' => '0_0', 'label' => '• '.getLabel('lbl_workspace')]);
		
		return $arr_object_analyses;
	}
	
	public static function css() {
	
		$return = '
			.analysis fieldset > ul > li > label:first-child + div > section { margin: 0px; }
			.analysis fieldset > ul > li > label:first-child + div > section + * { margin-top: 4px; }			
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.toolbar', function(elm_scripter) {
		
			var elm_analysis = elm_scripter.children('ul.analysis');
			
			elm_analysis.on('click run_analysis', '[id=y\\\:data_analysis\\\:analyse-0]', function() {
				
				var cur = $(this);
				
				COMMANDS.setOptions(cur, {overlay: 'document'});
				cur.popupCommand();
			}).on('command', '[id=y\\\:data_analysis\\\:analyse-0]', function() {
											
				COMMANDS.setTarget(this, function(data) {
				
					if (!data || !data.success) {
						return;
					}
					
					var elm_table = $('[id^=d\\\:data_entry\\\:data]');
					elm_table[0].datatable.refresh();
				});
			}).on('commandintermediate', '[id=y\\\:data_analysis\\\:analyse-0]', function(e) {
			
				LOADER.keepAlive(e.detail.elm);
			}).on('command', '[id=y\\\:data_analysis\\\:edit_analysis-0]', function() {
								
				COMMANDS.setTarget(this, function(data) {
					
					if (data.column == null) {
						return;
					}
					
					if (data.run) {
						elm_analysis.find('[id=y\\\:data_analysis\\\:analyse-0]').trigger('run_analysis');
					}
					
					var elm_table = $('[id^=d\\\:data_entry\\\:data]');
					elm_table[0].datatable.handleColumn('[data-identifier=analysis]', data.column, '[data-identifier=version]');
					elm_table[0].datatable.reload();
				});
			});
		});		
				
		SCRIPTER.dynamic('[data-method=update_analysis]', function(elm_scripter) {
		
			SCRIPTER.runDynamic(elm_scripter.find('.analysis'));
			SCRIPTER.runDynamic(elm_scripter.find('.analysis-context'));
		});
		
		SCRIPTER.dynamic('.analysis', function(elm_scripter) {
			
			var elm_scope = elm_scripter.find('.network.type');
			SCRIPTER.runDynamic(elm_scope);
			
			elm_scripter.on('ajaxloaded scripter', function() {
				elm_scripter.find('select[name=\"analysis[algorithm]\"]').trigger('update_selected_algorithm');
			}).on('change update_selected_algorithm', 'select[name=\"analysis[algorithm]\"]', function() {
				
				var cur = $(this);
				var elm_container = cur.closest('ul');
				
				var value = cur.val();
				
				var elms_target = elm_container.children('[class*=\"section-\"]');
				
				elms_target.addClass('hide');
				
				var elms_option = elms_target.filter('.section-'+value);
				
				if (elms_option.length) {
					
					elms_option.removeClass('hide');
					
					if (elms_option[0].dataset.weighted == '1') {
					
						elm_container.children('.section-weighted').removeClass('hide');
					}
				}
			}).on('click', 'fieldset > ul > li > span > .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', 'fieldset > ul > li > span > .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('command', '[id^=y\\\:data_analysis\\\:open_analysis-]', function() {
				
				var cur = $(this);
				var elm_analysis = cur.closest('.analysis');
				
				COMMANDS.setTarget(this, elm_analysis);
				COMMANDS.setOptions(this, {html: 'replace', elm_container: elm_analysis.parent()});	
			});
		});
		
		SCRIPTER.dynamic('.analysis', 'application_filter');
		
		SCRIPTER.dynamic('.analysis-context', function(elm_scripter) {
			
			elm_scripter.on('click', 'fieldset > ul > li > span > .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', 'fieldset > ul > li > span > .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('command', '[id^=y\\\:data_analysis\\\:open_analysis_context-]', function() {
				
				var cur = $(this);
				var elm_analysis_context = cur.closest('.analysis-context');
				
				COMMANDS.setTarget(this, elm_analysis_context);
				COMMANDS.setOptions(this, {html: 'replace', elm_container: elm_analysis_context.parent()});	
			});
		});";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		if ($method == "analyse") {
			
			$type_id = toolbar::getFilterTypeId();
			
			$arr_analysis = self::getTypeAnalysis($type_id);
			
			if (!$arr_analysis) {
				
				$str_message = getLabel('msg_analysis_no_configuration');
			} else {
			
				$arr_algorithm = AnalyseTypeObjects::getAlgorithms($arr_analysis['algorithm']);
				
				if ($arr_algorithm['graph'] && Settings::get('graph_analysis_service', 'host') == 'service') {
					
					$arr_job = self::checkService();
					
					if (!$arr_job) {
						
						$str_message = getLabel('msg_analysis_no_service');
					}
				}
			}
			
			if ($str_message) {
				
				$this->html = '<form data-method="no_run_analysis">'
					.'<section class="info attention">'
						.$str_message
					.'</section>'
					.'<input type="submit" value="'.getLabel('lbl_close').'" />'
				.'</form>';
				
				return;
			}

			$this->html = '<form data-method="run_analysis">'
				.$this->createAnalysisRun($type_id, $arr_analysis)
				.'<input type="submit" value="'.getLabel('lbl_run').' '.getLabel('lbl_analysis').'" />'
			.'</form>';
		}
		
		if ($method == "run_analysis") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_INTERACT) {
				
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			$arr_type_filters = toolbar::getFilter();
			$type_id = toolbar::getFilterTypeId();
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}
			
			$arr_analysis = self::getTypeAnalysis($type_id);
			
			$arr_scope = $arr_analysis['scope'];
			
			$arr_filters = current($arr_type_filters);

			$analysis_id = $_POST['storage'];
			$arr_id = explode('_', $analysis_id);
			
			$arr_analysis['id'] = (int)$arr_id[0];
			if ($arr_analysis['id']) {
				$arr_analysis['user_id'] = ($arr_id[1] ? $_SESSION['USER_ID'] : 0);
			} else {
				$arr_analysis['user_id'] = $_SESSION['USER_ID'];
			}
				
			if (!$arr_analysis['id'] && !$arr_analysis['user_id']) {
				error(getLabel('msg_missing_information'));
			}
			
			$arr_algorithm = AnalyseTypeObjects::getAlgorithms($arr_analysis['algorithm']);
			
			$str_host = false;
			
			if ($arr_algorithm['graph']) {
					
				$str_host = Settings::get('graph_analysis_service', 'host');
			
				if ($str_host == 'service') {
					
					$arr_job = self::checkService();
					
					if (!$arr_job) {
						error(getLabel('msg_analysis_no_service'));
					}
					
					$str_host = $arr_job['host'];
				}
			}

			$collect = self::getTypeAnalysisCollector($type_id, $arr_filters, $arr_scope);
				
			$analyse = new AnalyseTypeObjectsServer($type_id, $arr_analysis);
			
			$analyse->setHost($str_host);

			$analyse->input($collect, $arr_filters);
							
			$analyse->run();
			
			$success = $analyse->store();
			
			$this->html = ['success' => $success];
			
			if (!$success) {
				$this->msg = getLabel('msg_no_results');
			} else {
				$this->msg = true;
			}
		}
		
		if ($method == "edit_analysis") {
			
			$type_id = toolbar::getFilterTypeId();

			$arr_analysis = self::getTypeAnalysis($type_id);
			$arr_analysis_context = self::getTypeAnalysisContext($type_id);
			
			$this->html = '<form data-method="update_analysis">'
				.$this->createAnalysisSettings($type_id, $arr_analysis, $arr_analysis_context)
				.'<input type="submit" data-tab="analysis-analysis" name="clear_analysis" value="'.getLabel('lbl_remove').' '.getLabel('lbl_analysis').'" />'
				.'<input type="submit" data-tab="analysis-analysis" name="save_run_analysis" class="save" value="'.getLabel('lbl_save').' '.getLabel('lbl_settings').' & '.getLabel('lbl_run').'" />'
				.'<input type="submit" data-tab="analysis-context" name="clear_analysis_context" value="'.getLabel('lbl_remove').' '.getLabel('lbl_analysis').' '.getLabel('lbl_context').'" />'
				.'<input type="submit" data-tab="analysis-context" value="'.getLabel('lbl_save').' '.getLabel('lbl_settings').'" />'
			.'</form>';
		}
		
		if ($method == "update_analysis") {
				
			$type_id = toolbar::getFilterTypeId();
			
			if ($_POST['clear_analysis']) {
				
				$arr_analysis = [];
				
				SiteEndVars::setFeedback('analysis_id', false, true);
			} else {
				
				$arr_analysis = self::parseTypeAnalysis($type_id, $_POST['analysis']);
			
				SiteEndVars::setFeedback('analysis_id', 0, true);
			}
			
			SiteEndVars::setFeedback('analysis', ($arr_analysis['algorithm'] ? true : false));
			
			cms_nodegoat_custom_projects::handleProjectTypeAnalysis($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_analysis);
			
			if ($_POST['clear_analysis_context']) {
				
				$arr_analysis_context = [];
				
				SiteEndVars::setFeedback('analysis_context_id', false, true);
			} else {
				
				$arr_analysis_context = self::parseTypeAnalysisContext($type_id, $_POST['analysis_context']);
			
				SiteEndVars::setFeedback('analysis_context_id', 0, true);
			}
			
			SiteEndVars::setFeedback('analysis_context', ($arr_analysis_context ? true : false));
			
			cms_nodegoat_custom_projects::handleProjectTypeAnalysisContext($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_analysis_context);
			
			if ($arr_analysis['algorithm'] || $arr_analysis_context) {
				
				$this->html = [
					'column' => '<th class="analysis limit" title="'.getLabel('lbl_analysis').'" data-identifier="analysis"><span>'.self::createTypeAnalysisTableHeader($type_id, $arr_analysis, $arr_analysis_context).'</span></th>',
					'run' => ($_POST['save_run_analysis'] ? true : false)
				];
			} else {
				$this->html = ['column' => false];
			}
		}

		if ($method == "store_analysis" || $method == "store_analysis_context") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage">
				'.($method == 'store_analysis_context' ? $this->createSelectAnalysisContext($type_id, true) : $this->createSelectAnalysis($type_id, true)).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_analysis" || $method == "open_analysis_context") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage" data-method="'.($method == 'open_analysis_context' ? 'return_analysis_context' : 'return_analysis').'">
				'.($method == 'open_analysis_context' ? $this->createSelectAnalysisContext($type_id) : $this->createSelectAnalysis($type_id)).'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "return_analysis") {
			
			$type_id = $id;
			
			if ($_POST['analysis_id']) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], false, false, $_POST['analysis_id'], $arr_use_project_ids);
				$arr_analysis = self::parseTypeAnalysis($type_id, $arr_analysis);
			}
			
			$this->html = $this->createTypeAnalysis($type_id, $arr_analysis);
		}
		
		if ($method == "return_analysis_context") {
			
			$type_id = $id;
			
			if ($_POST['analysis_context_id']) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_analysis_context = cms_nodegoat_custom_projects::getProjectTypeAnalysesContexts($_SESSION['custom_projects']['project_id'], false, false, $_POST['analysis_context_id'], $arr_use_project_ids);
				$arr_analysis_context = $arr_analysis_context['object'];
				$arr_analysis_context = self::parseTypeAnalysisContext($type_id, $arr_analysis_context);
			}
			
			$this->html = $this->createTypeAnalysisContext($type_id, $arr_analysis_context);
		}
	}
	
	public static function getTypeAnalysisCollector($type_id, $arr_filters, $arr_scope) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		if ($arr_scope['paths']) {
			$trace = new TraceTypeNetwork(array_keys($arr_project['types']), true, true);
			$trace->filterTypeNetwork($arr_scope['paths']);
			$trace->run($type_id, false, 3);
			$arr_type_network_paths = $trace->getTypeNetworkPaths(true);
		} else {
			$arr_type_network_paths = ['start' => [$type_id => ['path' => [0]]]];
		}
		
		$collect = new CollectTypeObjects($arr_type_network_paths, 'all');
		$collect->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id'])]);
		$collect->setConditions(false);
		$collect->init($arr_filters, false);
			
		$arr_collect_info = $collect->getResultInfo();

		foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
			
			$arr_type_set = StoreType::getTypeSet($cur_type_id);
			
			if ($arr_project['types'][$cur_type_id]['type_filter_id']) {
				
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
				$collect->addLimitTypeFilters($cur_type_id, FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project['types'][$cur_type_id]['type_filter_object_subs']);
			}
			
			foreach ($arr_paths as $path) {
				
				$source_path = $path;
				if ($source_path) { // path includes the target type id, remove it
					$source_path = explode('-', $source_path);
					array_pop($source_path);
					$source_path = implode('-', $source_path);
				}
				
				$arr_settings = ($arr_scope ? $arr_scope['types'][$source_path][$cur_type_id] : []);
				
				$arr_filtering = [];
				if ($arr_settings['filter']) {
					$arr_filtering = ['all' => true];
				}

				$arr_selection = [
					'object' => [],
					'object_descriptions' => [],
					'object_sub_details' => []
				];
				
				$collapse = false;
							
				if ($arr_scope && !$arr_collect_info['connections'][$path]['end']) { // Not the end of a path, collapse it
					
					$collapse = true;
				}
	
				$collect->setPathOptions([$path => [
					'arr_selection' => $arr_selection,
					'arr_filtering' => $arr_filtering,
					'collapse' => $collapse
				]]);
			}
		}
		
		return $collect;
	}
		
	public static function getTypeAnalysis($type_id, $include_user = true) {
		
		$analysis_id = SiteStartVars::getFeedback('analysis_id');
		$arr_analysis = [];
		
		if ($analysis_id !== false) {
			
			// User-oriented settings
			if ($include_user) {
					
				// Interaction settings
				if ($analysis_id) {
					
					$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
				} else {
					
					$analysis_id = 0;
					$arr_use_project_ids = [];
				}
			
				$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $analysis_id, $arr_use_project_ids);
				
				$arr_analysis = self::parseTypeAnalysis($type_id, $arr_analysis);
			}
		}
		
		return $arr_analysis;
	}
	
	public static function getTypeAnalysisContext($type_id, $include_user = true) {
		
		$analysis_context_id = SiteStartVars::getFeedback('analysis_context_id');
		$arr_analysis_context = [];
		
		if ($analysis_context_id !== false) {
			
			// User-oriented settings
			if ($include_user) {
					
				// Interaction settings
				if ($analysis_context_id) {
					
					$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
				} else {
					
					$analysis_context_id = 0;
					$arr_use_project_ids = [];
				}
			
				$arr_analysis_context = cms_nodegoat_custom_projects::getProjectTypeAnalysesContexts($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $analysis_context_id, $arr_use_project_ids);
				$arr_analysis_context = $arr_analysis_context['object'];
				$arr_analysis_context = self::parseTypeAnalysisContext($type_id, $arr_analysis_context);
			}
		}
		
		return $arr_analysis_context;
	}
	
	public static function getTypeAnalysesActive($type_id) {

		$arr_analysis = self::getTypeAnalysis($type_id);
		$arr_analysis_context = self::getTypeAnalysisContext($type_id);
		
		$arr_selection = [];
		
		if ($arr_analysis || $arr_analysis_context) {

			if ($arr_analysis) {
				
				$arr_selection[] = ['analysis_id' => $arr_analysis['id'], 'user_id' => $arr_analysis['user_id'], 'algorithm' => $arr_analysis['algorithm']];
			}
			if ($arr_analysis_context['include']) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_analysis_context_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, array_keys($arr_analysis_context['include']), $arr_use_project_ids);
					
				foreach ($arr_analysis_context_analyses as $arr_analysis_context_analysis) {
						
					$arr_selection[] = ['analysis_id' => $arr_analysis_context_analysis['id'], 'user_id' => $arr_analysis_context_analysis['user_id'], 'algorithm' => $arr_analysis_context_analysis['algorithm']];
				}
			}
		}
		
		return $arr_selection;
	}
	
	public static function parseTypeAnalysis($type_id, $arr) {

		$arr_collect = [];
		
		$algorithm = $arr['algorithm'];
		
		if (!$algorithm) {
			return $arr_collect;
		}
		
		$arr_scope = data_model::parseTypeNetwork($arr['scope']);
		
		if (!$arr_scope['paths'] && !$arr_scope['types']) {
			return $arr_collect;
		}
		
		if ($arr['algorithm_settings']) {
			
			$arr_settings = $arr['algorithm_settings'][$algorithm];
			$arr_weighted = $arr['weighted_settings'];
		} else { // 'algorithm_settings' is part of the form configuration
			
			$arr_settings = $arr['settings'];
			$arr_weighted = $arr['settings']['weighted'];
		}
		unset($arr['algorithm_settings']);
		unset($arr['weighted_settings']);
			
		$arr_algorithm = AnalyseTypeObjects::getAlgorithms($algorithm);
		$func_parse = $arr_algorithm['parse'];
		
		if ($func_parse) {
			
			$arr_settings = $func_parse($arr_settings);
			
			if ($arr_settings === false) { // Settings are required, otherwise return
				return $arr_collect;
			}
		} else {
			
			$arr_settings = [];
		}
		
		if ($arr_algorithm['weighted']) {
			
			$str_mode = $arr_weighted['mode'];
			$arr_settings['weighted']['mode'] = ($str_mode == 'closeness' || $str_mode == 'distance' ? $str_mode : 'unweighted');
			
			if ($arr_settings['weighted']['mode'] != 'unweighted') {
				
				$arr_settings['weighted']['max'] = ($arr_weighted['max'] && (int)$arr_weighted['max'] > 1 ? (int)$arr_weighted['max'] : '');
			}
		}
		
		$arr_collect['algorithm'] = $algorithm;
		$arr_collect['scope'] = $arr_scope;
		$arr_collect['settings'] = $arr_settings;
		$arr_collect['user_id'] = (int)$arr['user_id'];
		$arr_collect['id'] = (int)$arr['id'];
		
		return $arr_collect;
	}
	
	public static function parseTypeAnalysisContext($type_id, $arr) {
		
		$arr_collect = [];
		
		if ($arr['include']) {
				
			foreach ($arr['include'] as $arr_analysis_context_include) {
				
				if (!$arr_analysis_context_include['analysis_id'] ) {
					continue;
				}
				
				$arr_collect['include'][$arr_analysis_context_include['analysis_id']] = ['analysis_id' => $arr_analysis_context_include['analysis_id']];
			}
		}
		
		return $arr_collect;
	}
	
	public static function checkService() {
		
		$arr_job = cms_jobs::getJob('cms_nodegoat_definitions', 'runGraphAnalysisService');
		
		if ($arr_job && $arr_job['process_id']) {
			
			$arr_job['host'] = 'http://127.0.0.1:'.$arr_job['port'].'/graph/';
			
			return $arr_job;
		} else {
			return false;
		}	
	}
}
