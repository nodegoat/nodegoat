<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class toolbar extends base_module {

	public static function moduleProperties() {
		static::$label = getLabel('lbl_toolbar');
		static::$parent_label = getLabel('ttl_site');
	}
	
	private static $action_space = null;
	private static $dynamic_filtering = false;

	public function contents() {
	
		if ($_SESSION['USER_ID']) {
			
			$str_project_name = Labels::parseTextVariables($_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']]['name']);
			$nr_projects = count($_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')]);
			
			$return .= '<div class="project hide">'
				.'<span>'.($str_project_name ? htmlspecialchars($str_project_name) : getLabel('lbl_none')).'</span>'
				.($nr_projects > 1 ? '<span title="'.getLabel('lbl_select').' '.getLabel('lbl_project').'" class="a icon popup" id="y:custom_projects:select-0">'.getIcon('updown-down').'</span>' : '')
			.'</div>';
	
			$return .= '<ul class="visualisation hide">'
				.'<li class="geo"><span title="'.getLabel('lbl_geo_visualisation').'" class="a icon" data-category="full" id="y:data_visualise:visualise-0">'.getIcon('globe').'</span></li>'
				.'<li class="soc"><span title="'.getLabel('lbl_soc_visualisation').'" class="a icon" data-category="full" id="y:data_visualise:visualise_soc-0">'.getIcon('graph').'</span></li>'
				.'<li class="time"><span title="'.getLabel('lbl_time_visualisation').'" class="a icon" data-category="full" id="y:data_visualise:visualise_time-0">'.getIcon('chart-bar').'</span></li>'
				.'<li class="settings">'
					.'<span title="'.getLabel('lbl_visualisation').' '.getLabel('lbl_settings').'" class="a popup" id="y:data_visualise:edit_visualisation_settings-0">'
						.'<span class="icon" data-category="full">'.getIcon('settings').'</span>'
						.'<sup class="hide"><span class="scope hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_scope').'</strong> active').'">Sc</span><span class="context hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_context').'</strong> active').'">Cx</span><span class="frame hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_frame').'</strong> active').'">Fr</span></sup>'
						.'<sub class="hide"><span class="visual-settings hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_visual_settings').'</strong> active').'">Vi</span></sub>'
					.'</span>'
				.'</li>'
			.'</ul>';
			
			$return .= '<ul class="data hide">'
				.'<li class="export"><span title="'.getLabel('lbl_export').'" class="a icon quick" data-category="full" id="y:toolbar:export-0">'.getIcon('download').'</span></li>'
				.'<li class="settings"><span title="'.getLabel('lbl_export').' '.getLabel('lbl_settings').'" class="a icon popup" data-category="full" id="y:toolbar:export_settings-0">'.getIcon('settings').'</span></li>'
			.'</ul>';
				
			$return .= '<ul class="analysis hide">'
				.'<li class="analyse"><span title="'.getLabel('lbl_run').' '.getLabel('lbl_analysis').'" class="icon a" data-category="full" id="y:data_analysis:analyse-0">'.getIcon('sigma').'</span></li>'
				.'<li class="settings">'
					.'<span title="'.getLabel('lbl_analysis').' '.getLabel('lbl_settings').'" class="a popup" id="y:data_analysis:edit_analysis-0">'
						.'<span class="icon" data-category="full">'.getIcon('settings').'</span>'
						.'<sup class="hide"><span class="analysis hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_analysis').'</strong> active').'">An</span></sup>'
						.'<sub class="hide"><span class="analysis-context hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_analysis_context').'</strong> active').'">Co</span></sub>'
					.'</span>'
				.'</li>'
			.'</ul>';
			
			$return .= '<ul class="analytics hide">'
				.'<li class="settings">'
					.'<span title="'.getLabel('lbl_set').' '.getLabel('lbl_conditions').'" class="a popup" id="y:data_model:edit_condition-0">'
						.'<span class="icon" data-category="full">'.getIcon('marker').'</span>'
						.'<sup class="hide"><span class="condition hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_conditions').'</strong> active').'">Co</span></sup>'
						.'<sub class="hide"><span class="condition-model-conditions hide" title="'.htmlspecialchars('<strong>'.getLabel('lbl_condition_model_conditions').'</strong> active').'">Mo</span></sub>'
					.'</span>'
				.'</li>'
			.'</ul>';

			$return .= '<ul class="scenario hide">'
				.'<li class="settings"><span title="'.getLabel('lbl_manage').' '.getLabel('lbl_scenarios').'" class="a icon popup" data-category="full" id="y:toolbar:manage_scenarios-0">'.getIcon('modules').'</span></li>'
			.'</ul>';
		}
		
		return $return;
	}
		
	public function createExportSettings($type_id, $arr_settings) {
		
		$arr_yes_no = [['id' => 1, 'name' => getLabel('lbl_yes')], ['id' => 0, 'name' => getLabel('lbl_no')]];
		
		$return = '<div class="export-settings tabs">
			<ul>
				<li class="no-tab"><span>'
					.'<input type="button" id="y:toolbar:store_export_settings-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_export_settings_store').'" />'
					.'<input type="button" id="y:toolbar:open_export_settings-'.$type_id.'" class="data add popup" value="open" title="'.getLabel('inf_export_settings_open').'" />'
				.'</span></li>
				<li><a href="#">'.getLabel('lbl_select').'</a></li>
				<li><a href="#">'.getLabel('lbl_format').'</a></li>
			</ul>
					
			<div>
				<div class="options">
				'.data_model::createTypeNetwork($type_id, false, false, ['references' => 'both', 'descriptions' => 'pick', 'network' => ['dynamic' => true, 'object_sub_locations' => true], 'value' => $arr_settings['scope'], 'name' => 'export_settings[scope]']).'
				</div>
			</div>
			
			<div>
				<div class="options fieldsets"><div>
					<fieldset><legend>'.getLabel('lbl_export_format').'</legend><ul>
						<li><label>'.getLabel('lbl_export_format_type').'</label><div><select name="export_settings[format][type]">'.cms_general::createDropdown(ExportObjectNetwork::getExportFormatTypes(), $arr_settings['format']['type']).'</select></div></li>
						<li><label>'.getLabel('lbl_export_format_include_description_name').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'export_settings[format][include_description_name]', $arr_settings['format']['include_description_name']).'</span></li>
					</ul></fieldset>

					<fieldset><legend>'.getLabel('lbl_export_format').' (CSV)</legend><ul>
						<li><label>'.getLabel('lbl_export_format_seperator').'</label><span><input type="text" name="export_settings[format][settings][csv][separator]" value="'.htmlspecialchars($arr_settings['format']['settings']['csv']['separator']).'" /></span></span></li>
						<li><label>'.getLabel('lbl_export_format_enclose').'</label><span><input type="text" name="export_settings[format][settings][csv][enclose]" value="'.htmlspecialchars($arr_settings['format']['settings']['csv']['enclose']).'" /></span></span></li>
					</ul></fieldset>
				</div></div>
			</div>
			
		</div>';
				
		return $return;
	}
	
	private function createSelectExportSettings($type_id, $store = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<fieldset><legend>'.getLabel(($store ? 'lbl_save' : 'lbl_select')).'</legend>
			<ul>
				<li><label>'.getLabel('lbl_export').'</label><span id="x:custom_projects:export_settings_storage-'.(int)$type_id.'">'
					.'<select name="export_settings_id">'.Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeExportSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), false, true, 'label')).'</select>'
					.($store ?
						'<input type="button" class="data add popup add_export_settings_storage" value="store" />'
						.'<input type="button" class="data del msg del_export_settings_storage" value="del" />'
					: '')
				.'</span></li>
			</ul>
		</fieldset>';

		return $return;
	}
		
	private function createSelectScenario($type_id) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_scenarios = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids);
		
		foreach ($arr_scenarios as $scenario_id => &$arr_scenario) {
			
			if ($arr_scenario['cache_retain']) {
				$arr_scenario['attr']['data-cache_retain'] = '1';
			}
		}
		unset($arr_scenario);
		
		$return = '<fieldset><legend>'.getLabel('lbl_select_or_store').'</legend>
			<ul>
				<li><label>'.getLabel('lbl_scenario').'</label>
					<ul class="sorter" id="x:custom_projects:scenario_storage-'.(int)$type_id.'">'
						.'<li><div>'
							.'<select name="scenario_id">'.Labels::parseTextVariables(cms_general::createDropdown($arr_scenarios, false, true, 'label')).'</select>'
							.'<input type="button" class="data add popup add_scenario_storage" value="store" />'
							.'<input type="button" class="data del msg del_scenario_storage" value="del" />'
						.'</div></li>
						<li class="hide">
							<fieldset>
								<ul>
									<li><label>'.getLabel('lbl_cache').'</label><div>'
										.'<input type="button" class="data del quick clear_scenario_storage_cache" value="clear" />'
									.'</div></li>
								</ul>
							</fieldset>
						</li>
					</ul>
				</li>
			</ul>
		</fieldset>';

		return $return;
	}

	public static function css() {
	
		$return = '
			.toolbar { line-height: 1; }
			.toolbar.movable { position: absolute; height: 0px; }
			
			.toolbar > div.project { display: inline-block; text-align:center; font-weight: bold; height: 26px; line-height: 26px; vertical-align: bottom; }
			.toolbar > div.project > span { }
			.toolbar > div.project > span + span.a { text-decoration: none; margin-left: 6px; }
			.toolbar > div.project > span + span.a svg { width: 11px; height: auto; margin-bottom: 1px; }
			.toolbar > div.project > span + span.a:not(:hover) { color: #000000; }
			
			.toolbar > ul { display: inline-block; height: 26px; vertical-align: bottom; border: 2px solid #000000; text-align:center; font-weight: bold; }
			.toolbar > div.project + ul { margin-left: 14px; }
			.toolbar > ul + ul { margin-left: 5px; }
			.toolbar li { display: inline-block; height: 100%; margin: 0; vertical-align: top; }
			.toolbar li > span.a { text-decoration: none; }
			
			.toolbar li.geo,
			.toolbar li.soc,
			.toolbar li.time,
			.toolbar li.export,
			.toolbar li.analyse { padding: 0px 9px; }
			.toolbar li.pulse { }
			.toolbar li.soc,
			.toolbar li.time { padding-left: 2px; }
			
			.toolbar li.settings { background-color: #000000; padding: 0px 5px; }
			.toolbar li.settings > span:not(:hover) { color: #ffffff; }
			.toolbar li.settings sup { margin-left: -4px; margin-top: -4px; }
			.toolbar li.settings sub { margin-left: -4px; margin-top: 21px; }
			.toolbar li.settings sup[data-size="2"] { margin-top: -8px; }
			.toolbar li.settings sup[data-size="3"] { margin-top: -15px; }
			
			.toolbar li.time > span.icon svg { height: 14px; }
			.toolbar li.export > span.icon svg { height: 14px; }
			.toolbar li.analyse > span.icon svg { height: 15px; }
			.toolbar ul.analytics > li.settings > span > span.icon svg { margin: 0px 1px; height: 15px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.toolbar', function(elm_scripter) {
			
			$('[id=y\\\:data_model\\\:edit_condition-0], [id=y\\\:data_analysis\\\:edit_analysis-0], [id=y\\\:toolbar\\\:manage_scenarios-0], [id=y\\\:data_visualise\\\:edit_visualisation_settings-0], [id=y\\\:custom_projects\\\:select-0], [id=y\\\:toolbar\\\:export_settings-0]').each(function() {
				COMMANDS.setOptions(this, {overlay: 'document'});
			});
						
			if ($('.data_entry').length) {
				
				var project_id = false;
				
				FEEDBACK.listen(function(data, elm) {
				
					if (data.project_id) {
						
						if (project_id !== false && project_id != data.project_id) {
							LOCATION.reload();
						}
						
						project_id = data.project_id;
					}
					
					var elm_project = elm_scripter.children('.project');

					if (!elm_project.length) {
						return;
					}

					if (data.project === true) {
						elm_project.removeClass('hide');
					} else if (data.project === false) {
						elm_project.addClass('hide');
					}
					
					var elm_functions = elm_scripter.children('ul');
					
					var elm_analysis = elm_functions.filter('.analysis');
					var elm_analytics = elm_functions.filter('.analytics');
					var elm_visualisation = elm_functions.filter('.visualisation');
					var elm_export = elm_functions.filter('.data');
					var elm_scenario = elm_functions.filter('.scenario');
					
					var elm_hints = $();
					
					if (data.visualise === true) {
						elm_hints = elm_hints.add(elm_visualisation.children('li.geo, li.soc, li.time'));
					}
					if (data.data === true) {
						elm_hints = elm_hints.add(elm_export.children('li.export')).add(elm_analysis.children('li.analyse'));
						elm_export.add(elm_visualisation).add(elm_scenario).add(elm_analysis).add(elm_analytics).removeClass('hide');
					} else if (data.data === false) {
						elm_export.add(elm_visualisation).add(elm_scenario).add(elm_analysis).add(elm_analytics).addClass('hide');
					}
					
					if (elm_hints.length) {
						elm_hints.each(function() {
							new Pulse(this);
						});
					}
					
					var elm_visualisation_settings = elm_visualisation.children('li.settings');

					if (data.scope === true || data.context === true || data.frame === true) {
						elm_visualisation_settings.each(function() {
							new Pulse(this, {duration: 800, delay_out: 300, repeat: true});
						});
						var elm_sup = elm_visualisation_settings.find('sup');
						elm_sup.removeClass('hide');
						elm_sup.children('.scope').toggleClass('hide', data.scope === false);
						elm_sup.children('.context').toggleClass('hide', data.context === false);
						elm_sup.children('.frame').toggleClass('hide', data.frame === false);
						elm_sup.attr('data-size', elm_sup.children().not('.hide').length);
					} else if (data.scope === false && data.context === false && data.frame === false) {
						elm_visualisation_settings.each(function() {
							if (this.pulse) {
								this.pulse.abort();
							}
						});
						var elm_sup = elm_visualisation_settings.find('sup');
						elm_sup.addClass('hide');
						elm_sup.children().addClass('hide');
						elm_sup.attr('data-size', 0);
					}
					
					if (data.visual_settings === true) {
						var elm_sub = elm_visualisation_settings.find('sub');
						elm_sub.removeClass('hide');
						elm_sub.children('.visual-settings').removeClass('hide');
					} else if (data.visual_settings === false) {
						var elm_sub = elm_visualisation_settings.find('sub');
						elm_sub.addClass('hide');
						elm_sub.children().addClass('hide');
					}
					
					var elm_analysis_settings = elm_analysis.children('li.settings');
					
					if (data.analysis === true) {
						var elm_sup = elm_analysis_settings.find('sup');
						elm_sup.removeClass('hide');
						elm_sup.children('.analysis').removeClass('hide');
					} else if (data.analysis === false) {
						var elm_sup = elm_analysis_settings.find('sup');
						elm_sup.addClass('hide');
						elm_sup.children().addClass('hide');
					}
					
					if (data.analysis_context === true) {
						var elm_sub = elm_analysis_settings.find('sub');
						elm_sub.removeClass('hide');
						elm_sub.children('.analysis-context').removeClass('hide');
					} else if (data.analysis_context === false) {
						var elm_sub = elm_analysis_settings.find('sub');
						elm_sub.addClass('hide');
						elm_sub.children().addClass('hide');
					}

					var elm_condition_settings = elm_analytics.children('li.settings');
					
					if (data.condition === true) {
						elm_condition_settings.each(function() {
							new Pulse(this, {duration: 800, delay_out: 300, repeat: true});
						});
						var elm_sup = elm_condition_settings.find('sup');
						elm_sup.removeClass('hide');
						elm_sup.children('.condition').removeClass('hide');
					} else if (data.condition === false) {
						elm_condition_settings.each(function() {
							if (this.pulse) {
								this.pulse.abort();
							}
						});
						var elm_sup = elm_condition_settings.find('sup');
						elm_sup.addClass('hide');
						elm_sup.children().addClass('hide');
					}
					
					if (data.condition_model_conditions === true) {
						var elm_sub = elm_condition_settings.find('sub');
						elm_sub.removeClass('hide');
						elm_sub.children('.condition-model-conditions').removeClass('hide');
					} else if (data.condition_model_conditions === false) {
						var elm_sub = elm_condition_settings.find('sub');
						elm_sub.addClass('hide');
						elm_sub.children().addClass('hide');
					}
					
					var elm_table = $('table[id^=d\\\\:data_entry\\\:data-]');
					
					if (data.analysis_column !== undefined) {
						
						if (!elm.matches('[id=y\\\:data_entry\\\:view-0]')) { // Do not use on Type switch
						
							var elm_analysis_header = elm_table[0].querySelector('[data-identifier=analysis]');
							
							if (data.analysis_column || (!data.analysis_column && elm_analysis_header)) {
								elm_table[0].datatable.handleColumn('[data-identifier=analysis]', data.analysis_column, '[data-identifier=version]');
							}
						}
					}
					
					if (data.filter) {
					
						var elm_target = getElementData(elm_table, 'filter');
						var func_target = COMMANDS.getTarget(elm_target);
						
						func_target({filter: data.filter.filter, active: data.filter.active});
					}
				});
			}
		});
				
		SCRIPTER.dynamic('[data-method=update_export_settings]', function(elm_scripter) {
			
			SCRIPTER.runDynamic(elm_scripter.children('.export-settings'));

			elm_scripter.on('command', '[id^=y\\\:toolbar\\\:open_export_settings-]', function() {
				
				var cur = $(this);
				var elm_export_settings = cur.closest('.export-settings');
				
				COMMANDS.setTarget(this, function(elm_html) {
				
					elm_export_settings.replaceWith(elm_html);
					SCRIPTER.runDynamic(elm_html);
				});
			});
		});
		
		SCRIPTER.dynamic('.export-settings', function(elm_scripter) {
			
			var elm_scope = elm_scripter.find('.network.type');
			SCRIPTER.runDynamic(elm_scope);
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {

		if ($method == "export") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_USER) {
				
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			$type_id = toolbar::getFilterTypeId();

			if (!custom_projects::checkAccessType('view', $type_id)) {
				return;
			}
			
			$arr_export_settings = self::getTypeExportSettings($type_id);
						
			if ($_POST['get-download']) {
				
				$arr_filters = current(self::getFilter());
				$arr_conditions = toolbar::getTypeConditions($type_id);
				
				$collect = self::getExportCollector($type_id, $arr_filters, $arr_export_settings['scope'], $arr_conditions);

				$export = new ExportObjectNetwork($type_id, $arr_export_settings['scope']['types'], $arr_export_settings['format']);

				$arr_nodegoat_details = cms_nodegoat_details::getDetails();
				if ($arr_nodegoat_details['processing_time']) {
					timeLimit($arr_nodegoat_details['processing_time']);
				}
				if ($arr_nodegoat_details['processing_memory']) {
					memoryBoost($arr_nodegoat_details['processing_memory']);
				}
			
				$export->init($collect, $arr_filters);
				
				$response_format = Response::getFormat();
				Response::setFormat(Response::OUTPUT_TEXT);
					
				$resource = $export->getPackage($arr_export_settings['format']['settings']['csv']);
				
				if ($resource === false) {
					
					Response::setFormat($response_format);
					
					$this->msg = getLabel('msg_export_not_available');
					return;
				}

				$return = stream_get_contents($resource);
	
				$return = Response::parse($return);
				
				// UTF-8 BOM
				$return = "\xEF\xBB\xBF".$return;

				Response::sendHeader($return, 'export.'.$arr_export_settings['format']['type']);
				
				echo $return;
				
				die;
			} else {
				 
				$this->download = true;
			}
		}
		
		if ($method == "export_settings") {
			
			$type_id = toolbar::getFilterTypeId();
			
			$arr_export_settings = self::getTypeExportSettings($type_id);
		
			$this->html = '<form class="export_settings" data-method="update_export_settings">'.$this->createExportSettings($type_id, $arr_export_settings)
				.'<input type="submit" name="reset" value="'.getLabel('lbl_reset').' '.getLabel('lbl_export').' '.getLabel('lbl_settings').'" />'
				.'<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_settings').'" />'
			.'</form>';
		}
		
		if ($method == "update_export_settings") {
			
			$type_id = toolbar::getFilterTypeId();
			
			if ($_POST['reset']) {
				
				$arr_export_settings = [];
			} else {
				
				$arr_export_settings = self::parseTypeExportSettings($type_id, $_POST['export_settings']);
			}
			
			cms_nodegoat_custom_projects::handleProjectTypeExportSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_export_settings);
			
			$this->msg = true;
		}
		
		if ($method == "store_export_settings") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage">
				'.$this->createSelectExportSettings($type_id, true).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_export_settings") {
			
			$type_id = $id;
			
			$this->html = '<form class="options storage" data-method="return_export_settings">
				'.$this->createSelectExportSettings($type_id).'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "return_export_settings") {
			
			$type_id = $id;
			
			if ($_POST['export_settings_id']) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_export_settings = cms_nodegoat_custom_projects::getProjectTypeExportSettings($_SESSION['custom_projects']['project_id'], false, false, $_POST['export_settings_id'], $arr_use_project_ids);
				$arr_export_settings = self::parseTypeExportSettings($type_id, $arr_export_settings);
			} else {
				
				$arr_export_settings = [];
			}

			$this->html = $this->createExportSettings($type_id, $arr_export_settings);
		}
				
		if ($method == "manage_scenarios") {
			
			$type_id = self::getFilterTypeId();
			
			$this->html = '<form class="options storage" data-method="select_scenario">
				'.$this->createSelectScenario($type_id).'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "select_scenario") {
			
			$scenario_id = ($_POST['scenario_id'] ?: $value['scenario_id']);
			
			self::setScenario($scenario_id);
		}
	}
	
	public static function setActionSpace($space = null) {
						
		self::$action_space = $space;
	}
	
	public static function getActionSpace() {
		
		return self::$action_space;
	}
	
	public static function setScenario($scenario_id = false) {
		
		$type_id = self::getFilterTypeId();
		$arr_scenario = [];
				
		if ($scenario_id) {

			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $scenario_id, $arr_use_project_ids);
			SiteEndVars::setFeedback('scenario_id', $arr_scenario['id'], true);
		} else {
			
			SiteEndVars::setFeedback('scenario_id', null, true);
		}

		// Filter
		if (!$arr_scenario['filter_use_current']) {
			
			$arr_filters = self::getScenarioFilters($scenario_id);
			self::setFilter([$arr_scenario['type_id'] => $arr_filters]);
			
			if (self::$action_space === 0) {
								
				SiteEndVars::setFeedback('filter', [
					'filter' => ['filter_id' => $arr_scenario['filter_id'], 'form' => [], 'versioning' => []],
					'active' => ($arr_scenario['filter_id'] ? true : false)
				]);
			}
		}
		
		// Scope
		if (!$arr_scenario['scope_use_current']) {
			
			if (self::$action_space === 0) {
				
				$arr_scope = [];
				
				if ($arr_scenario['scope_id']) {
															
					$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($_SESSION['custom_projects']['project_id'], false, false, $arr_scenario['scope_id'], $arr_use_project_ids);
					$arr_scope = $arr_scope['object'];
				}
				
				cms_nodegoat_custom_projects::handleProjectTypeScope($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_scope);
			}
			
			SiteEndVars::setFeedback('scope_id', ($arr_scenario['scope_id'] ?: false), true);
		}
		
		// Condition
		if (!$arr_scenario['condition_use_current']) {
			
			if (self::$action_space === 0) {

				$arr_condition = [];
				
				if ($arr_scenario['condition_id']) {
					
					$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], false, false, $arr_scenario['condition_id'], false, $arr_use_project_ids);
					$arr_model_conditions = $arr_condition['model_object'];
					$arr_condition = $arr_condition['object'];
				}
				
				cms_nodegoat_custom_projects::handleProjectTypeCondition($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_condition, $arr_model_conditions);
			}
			
			SiteEndVars::setFeedback('condition_id', ($arr_scenario['condition_id'] ?: false), true);
		}

		// Context
		if (!$arr_scenario['context_use_current']) {
			
			if (self::$action_space === 0) {
					
				$arr_context = [];
				
				if ($arr_scenario['context_id']) {
					
					$arr_context = cms_nodegoat_custom_projects::getProjectTypeContexts($_SESSION['custom_projects']['project_id'], false, false, $arr_scenario['context_id'], $arr_use_project_ids);
					$arr_context = $arr_context['object'];
				}
				
				cms_nodegoat_custom_projects::handleProjectTypeContext($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_context);
			}
			
			SiteEndVars::setFeedback('context_id', ($arr_scenario['context_id'] ?: false), true);
		}
		
		// Frame
		if (!$arr_scenario['frame_use_current']) {
			
			if (self::$action_space === 0) {
					
				$arr_frame = [];
				
				if ($arr_scenario['frame_id']) {
					
					$arr_frame = cms_nodegoat_custom_projects::getProjectTypeFrames($_SESSION['custom_projects']['project_id'], false, false, $arr_scenario['frame_id'], $arr_use_project_ids);
					$arr_frame = $arr_frame['settings'];
					$arr_frame = cms_nodegoat_custom_projects::parseFrame($arr_frame);
					
					if (data_visualise::getTypeFrame($type_id, false) == $arr_frame) {

						$arr_frame = [];
					}
				}
				
				cms_nodegoat_custom_projects::handleProjectTypeFrame($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_frame);
			}
			
			SiteEndVars::setFeedback('frame_id', ($arr_scenario['frame_id'] ?: false), true);
		}
		
		// Visual settings
		if (!$arr_scenario['visual_settings_use_current']) {
			
			if (self::$action_space === 0) {
					
				$arr_visual_settings = [];
				
				if ($arr_scenario['visual_settings_id']) {
					
					$arr_visual_settings = cms_nodegoat_custom_projects::getProjectVisualSettings($_SESSION['custom_projects']['project_id'], false, $arr_scenario['visual_settings_id'], $arr_use_project_ids);
					$arr_visual_settings = $arr_visual_settings['settings'];
					$arr_visual_settings = cms_nodegoat_custom_projects::parseVisualSettings($arr_visual_settings);
					
					if (data_visualise::getVisualSettings(false) == $arr_visual_settings) {

						$arr_visual_settings = [];
					}
				}
				
				cms_nodegoat_custom_projects::handleProjectVisualSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, [], $arr_visual_settings);
			}
			
			SiteEndVars::setFeedback('visual_settings_id', ($arr_scenario['visual_settings_id'] ?: false), true);
		}
		
		// Analysis
		if (!$arr_scenario['analysis_use_current']) {
			
			if (self::$action_space === 0) {

				$arr_analysis = [];
				
				if ($arr_scenario['analysis_id']) {
					
					$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], false, false, $arr_scenario['analysis_id'], $arr_use_project_ids);
				}
				
				cms_nodegoat_custom_projects::handleProjectTypeAnalysis($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_analysis);
			}
			
			SiteEndVars::setFeedback('analysis_id', ($arr_scenario['analysis_id'] ?: false), true);
		}
		
		// Analysis Context
		if (!$arr_scenario['analysis_context_use_current']) {
			
			if (self::$action_space === 0) {

				$arr_analysis_context = [];
				
				if ($arr_scenario['analysis_context_id']) {
					
					$arr_analysis_context = cms_nodegoat_custom_projects::getProjectTypeAnalysesContexts($_SESSION['custom_projects']['project_id'], false, false, $arr_scenario['analysis_context_id'], $arr_use_project_ids);
					$arr_analysis_context = $arr_analysis_context['object'];
				}
				
				cms_nodegoat_custom_projects::handleProjectTypeAnalysisContext($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_analysis_context);
			}
			
			SiteEndVars::setFeedback('analysis_context_id', ($arr_scenario['analysis_context_id'] ?: false), true);
		}
		
		self::checkActiveSettings();
	}

	public static function checkActiveScenario($type, $arr_cur_filters, $arr_cur_scope, $arr_cur_conditions) {
		
		if (!SiteStartVars::getFeedback('scenario_id')) {
			return false;
		}
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
						
		$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, SiteStartVars::getFeedback('scenario_id'), $arr_use_project_ids);
		
		if (!$arr_scenario) {
			SiteEndVars::setFeedback('scenario_id', null, true);
			return false;
		}
		
		$type_id = $arr_scenario['type_id'];
		
		// Filter
		if ($arr_scenario['filter_use_current']) {
			SiteEndVars::setFeedback('scenario_id', null, true);
			return false;
		}
		
		$arr_filters = self::getScenarioFilters($arr_scenario['id']);
		
		if ($arr_cur_filters) {
			
			$filter = new FilterTypeObjects($type_id, 'id');
			$filter->setDifferentiationId('scenario_'.$_SESSION['custom_projects']['project_id'].'_'.$arr_scenario['id']);
			$filter->setFilter($arr_cur_filters);
			$arr_cur_filters = $filter->getDepth();
			$arr_cur_filters = $arr_cur_filters['arr_filters'];
		}
		
		// Scope
		$scope_id = SiteStartVars::getFeedback('scope_id');
		if ($arr_scenario['scope_use_current'] || (!$scope_id && $scope_id !== false)) {
			SiteEndVars::setFeedback('scenario_id', null, true);
			return false;
		}
		
		$arr_scope = data_visualise::getTypeScope($type_id);
		
		// Conditions
		$condition_id = SiteStartVars::getFeedback('condition_id');
		if ($arr_scenario['condition_use_current'] || (!$condition_id && $condition_id !== false)) {
			SiteEndVars::setFeedback('scenario_id', null, true);
			return false;
		}
		
		$arr_conditions = toolbar::getTypeConditions($type_id);
		
		if (arrKsortRecursive($arr_cur_filters) !== arrKsortRecursive($arr_filters) || $arr_cur_scope !== $arr_scope || $arr_cur_conditions !== $arr_conditions) { // Check if the active (original) filter/scope/condition match the scenario
			
			SiteEndVars::setFeedback('scenario_id', null, true);
			return false;
		}

		$hash = self::getScenarioHash($arr_scenario['id'], $arr_cur_filters, $arr_cur_scope, $arr_cur_conditions);
				
		return $hash;
	}
	
	public static function getScenarioFilters($scenario_id) {
				
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
		$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $scenario_id, $arr_use_project_ids);
		$type_id = $arr_scenario['type_id'];
		
		if ($arr_scenario['filter_id'] && $arr_scenario['filter_id'] != $arr_project['types'][$type_id]['type_filter_id']) {
		
			$arr_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_scenario['filter_id'], (($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN || self::$action_space !== 0) ? true : false), $arr_use_project_ids);
			$arr_filters = FilterTypeObjects::convertFilterInput($arr_filters['object']);
			
			$filter = new FilterTypeObjects($type_id, 'id');
			$filter->setDifferentiationId('scenario_'.$_SESSION['custom_projects']['project_id'].'_'.$arr_scenario['id']);
			$filter->setFilter($arr_filters);
			$arr_filters = $filter->getDepth();
			$arr_filters = $arr_filters['arr_filters'];
		} else {
			
			$arr_filters = [];
		}
		
		return $arr_filters;
	}
	
	public static function getScenarioHash($scenario_id, $arr_filters, $arr_scope, $arr_conditions) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $scenario_id, $arr_use_project_ids);
		$type_id = $arr_scenario['type_id'];
		
		if ($arr_project['types'][$type_id]['type_filter_id']) {
							
			$arr_project_type_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$type_id]['type_filter_id'], true, $arr_use_project_ids);
			$arr_project_type_filters = FilterTypeObjects::convertFilterInput($arr_project_type_filters['object']);
		} else {
			
			$arr_project_type_filters = [];
		}
		
		return hash('md5', serialize($arr_filters + $arr_project_type_filters)).'_'.hash('md5', serialize($arr_scope)).'_'.hash('md5', serialize($arr_conditions));
	}
	
	public static function getScenarioPath($scenario_id, $type) {
				
		return DIR_CACHE_SCENARIOS.$type.'_'.$_SESSION['custom_projects']['project_id'].'_'.$scenario_id;
	}

	public static function setFilter($arr_filters, $dynamic_filtering = false) {
		
		if ($dynamic_filtering) {
			
			if (!self::$dynamic_filtering) {
				return;
			}
			
			$cur_type_id = self::getFilterTypeId();
			$type_id = key($arr_filters);
			
			if ($cur_type_id && $type_id != $cur_type_id) {
				return;
			}
		}
		
		// $arr_filters = array(type_id => arr_filter = see FilterTypeObjects)
		
		$arr_cur_filters = self::getFilter();
		
		if (arrKsortRecursive($arr_filters) === arrKsortRecursive($arr_cur_filters)) {
			return;
		}
		
		SiteEndVars::setFeedback('filter', $arr_filters, true);
		
		self::checkFilter();
	}

	public static function checkFilter() {
		
		$arr_filters = self::getFilter();
		
		SiteEndVars::setFeedback('project_id', $_SESSION['custom_projects']['project_id']);
		
		if ($arr_filters) {
			
			$type_id = key($arr_filters);
			
			self::checkActiveSettings($type_id);
			
			$arr_scope = data_visualise::getTypeScope($type_id);
			$has_scope = (($arr_scope['types'] || $arr_scope['paths']) ? true : false);
			
			$arr_type_set = StoreType::getTypeSet($type_id);
			
			SiteEndVars::setFeedback('visualise', ($arr_type_set['object_sub_details'] || $has_scope ? true : false));
			SiteEndVars::setFeedback('data', true);
		} else {
			
			SiteEndVars::setFeedback('visualise', false);
			SiteEndVars::setFeedback('data', false);
		}
	}
	
	public static function enableDynamicFiltering() {
			
		self::$dynamic_filtering = true;
	}
	
	public static function getFilter() {
		
		$arr = SiteStartVars::getFeedback('filter');
		$arr = ($arr ?: []);
		
		return $arr;
	}
	
	public static function checkActiveSettings($type_id = false) {
		
		if (self::$action_space !== 0) {
			return;
		}
		
		if ($type_id) { // Specific Type check, reset all user settings when applicable
			
			$cur_type_id = SiteStartVars::getFeedback('type_id');
			
			if ($cur_type_id && $type_id != $cur_type_id) {
				
				SiteEndVars::setFeedback('scope_id', 0, true);
				SiteEndVars::setFeedback('condition_id', 0, true);
				SiteEndVars::setFeedback('context_id', 0, true);
				SiteEndVars::setFeedback('frame_id', 0, true);
				SiteEndVars::setFeedback('visual_settings_id', 0, true);
				SiteEndVars::setFeedback('analysis_id', 0, true);
				SiteEndVars::setFeedback('analysis_context_id', 0, true);
			}
		
			SiteEndVars::setFeedback('type_id', $type_id, true);
		} else {
			
			$type_id = self::getFilterTypeId();
		}
		
		$arr_scope = data_visualise::getTypeScope($type_id);
		$bool_scope = (($arr_scope['types'] || $arr_scope['paths']) ? true : false);
		
		SiteEndVars::setFeedback('scope', $bool_scope);
		
		$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, 0);
		
		SiteEndVars::setFeedback('condition', ($arr_condition['object'] ? true : false));
		SiteEndVars::setFeedback('condition_model_conditions', ($arr_condition['model_object'] ? true : false));
		
		$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, 0);
		$bool_analysis = ($arr_analysis['algorithm'] ? true : false);
		
		SiteEndVars::setFeedback('analysis', $bool_analysis);
		
		$arr_analysis_context = cms_nodegoat_custom_projects::getProjectTypeAnalysesContexts($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, 0);
		$bool_analysis_context = ($arr_analysis_context['object'] ? true : false);
		
		SiteEndVars::setFeedback('analysis_context', $bool_analysis_context);
		
		if ($bool_analysis || $bool_analysis_context) {
			
			$arr_analysis = data_analysis::parseTypeAnalysis($type_id, $arr_analysis);
			
			$arr_analysis_context = $arr_analysis_context['object'];
			$arr_analysis_context = data_analysis::parseTypeAnalysisContext($type_id, $arr_analysis_context);
			
			$html_column = '<th class="analysis limit" title="'.getLabel('lbl_analysis').'" data-identifier="analysis"><span>'.data_analysis::createTypeAnalysisTableHeader($type_id, $arr_analysis, $arr_analysis_context).'</span></th>';
		}
		
		SiteEndVars::setFeedback('analysis_column', ($html_column ?: false));
		
		$arr_context = cms_nodegoat_custom_projects::getProjectTypeContexts($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, 0);
		$bool_context = ($arr_context['object'] ? true : false);
		
		SiteEndVars::setFeedback('context', $bool_context);
		
		$arr_frame = cms_nodegoat_custom_projects::getProjectTypeFrames($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, 0);
		$bool_frame = ($arr_frame['settings'] && array_filter($arr_frame['settings']) ? true : false);
		
		SiteEndVars::setFeedback('frame', $bool_frame);
		
		$arr_visual_settings = cms_nodegoat_custom_projects::getProjectVisualSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0);
		$arr_visual_settings = $arr_visual_settings['settings'];
		
		if ($arr_visual_settings) {
			
			$arr_visual_settings = cms_nodegoat_custom_projects::parseVisualSettings($arr_visual_settings);
			$arr_visual_settings_default = cms_nodegoat_custom_projects::parseVisualSettings();
			
			if ($arr_visual_settings == $arr_visual_settings_default) {
				$arr_visual_settings = false;
			}
		}

		SiteEndVars::setFeedback('visual_settings', ($arr_visual_settings ? true : false));
	}
	
	public static function getFilterTypeId() {
						
		$arr_filter = self::getFilter();
		$type_id = ($arr_filter ? key($arr_filter) : 0);
		
		return $type_id;
	}
	
	public static function getTypeFilterSet($str_identifier, $arr_filters) {
		
		$arr_set = SiteStartVars::getFeedback('data_entry_filter_set');
		$arr_set = $arr_set[$str_identifier];
		
		$str_filter = hash('md5', serialize($arr_filters));
		
		if ($arr_set !== null && $str_filter == $arr_set['filter']) {
			return $arr_set;
		}
		
		$arr_set = ['filter' => $str_filter];

		return $arr_set;
	}
	
	public static function setTypeFilterSet($str_identifier, $arr_set) {
		
		$arr_set_all = SiteStartVars::getFeedback('data_entry_filter_set');
		$arr_set_all[$str_identifier] = $arr_set;
		
		SiteEndVars::setFeedback('data_entry_filter_set', $arr_set_all, true);
	}
		
	public static function getTypeConditions($type_id, $object_name_only = false) {
		
		$arr_condition_ids = [];
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
						
		$arr_type_set = StoreType::getTypeSet($type_id);
		if ($arr_type_set['type']['condition_id']) { // Model settings
			$arr_condition_ids[] = $arr_type_set['type']['condition_id'];
		}
		
		if ($arr_project['types'][$type_id]['type_condition_id']) { // Project settings
			$arr_condition_ids[] = $arr_project['types'][$type_id]['type_condition_id'];
		}
		
		// Interaction settings
		if (SiteStartVars::getFeedback('condition_id') === false) {
			
		} else {
			
			$arr_context = SiteStartVars::getFeedback('context');
			
			if ($arr_context) {
				$active_type_id = $arr_context['type_id'];
			} else {
				$active_type_id = self::getFilterTypeId();
			}
			
			$condition_id = SiteStartVars::getFeedback('condition_id');
			if (!$condition_id) {
				$condition_id = 0;
			}

			if ($active_type_id != $type_id) {
				
				$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $active_type_id, $condition_id, true, $arr_use_project_ids);
				
				if ($arr_condition['model_object'][$type_id]['condition_id']) {
					
					$arr_condition_ids[] = $arr_condition['model_object'][$type_id]['condition_id'];
				} else if ($arr_condition['model_object'][$type_id]['condition_use_current']) {
					
					if (!SiteStartVars::getFeedback('scenario_id') && !$arr_context) { // Only apply a user's condition when no scenarios or contexts are active
						$arr_condition_ids[] = 0;
					}
				}
			} else {
					
				$arr_condition_ids[] = $condition_id;
			}
		}
		
		$arr_conditions = cms_nodegoat_custom_projects::getProjectTypeConditions($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $arr_condition_ids, true, $arr_use_project_ids);
				
		$arr_type_set_conditions = [];
	
		foreach ($arr_condition_ids as $condition_id) { // Keep original sort
			
			$arr_condition = data_model::parseTypeCondition($type_id, $arr_conditions[$condition_id]['object']);
			
			if ($object_name_only) {
				
				foreach ((array)$arr_condition['object'] as $arr_condition_setting) {
					
					if (!$arr_condition_setting['condition_in_object_name']) {
						continue;
					}
					
					$arr_type_set_conditions['object'][] = $arr_condition_setting;
				}
				
				foreach ((array)$arr_condition['object_descriptions'] as $object_description_id => $arr_condition_settings) {
					
					foreach ($arr_condition_settings as $arr_condition_setting) {
						
						if (!$arr_condition_setting['condition_in_object_name']) {
							continue;
						}
						
						$arr_type_set_conditions['object_descriptions'][$object_description_id][] = $arr_condition_setting;
					}
				}
				
				foreach ((array)$arr_condition['object_sub_details'] as $object_sub_details_id => $arr_condition_object_sub_details) {
					
					if (!$arr_condition_object_sub_details['object_sub_descriptions']) {
						continue;
					}
							
					foreach ($arr_condition_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_condition_settings) {

						foreach ($arr_condition_settings as $arr_condition_setting) {
							
							if (!$arr_condition_setting['condition_in_object_name']) {
								continue;
							}
							
							$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id][] = $arr_condition_setting;
						}
					}
				}
			} else {

				if ($arr_condition['object']) {
					$arr_type_set_conditions['object'] = array_merge((array)$arr_type_set_conditions['object'], $arr_condition['object']);
				}
				 
				foreach ((array)$arr_condition['object_descriptions'] as $object_description_id => $arr_condition_settings) {
					$arr_type_set_conditions['object_descriptions'][$object_description_id] = array_merge((array)$arr_type_set_conditions['object_descriptions'][$object_description_id], $arr_condition_settings);
				}
				
				foreach ((array)$arr_condition['object_sub_details'] as $object_sub_details_id => $arr_condition_object_sub_details) {
					
					if ($arr_condition_object_sub_details['object_sub_details']) {
						$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_details'] = array_merge((array)$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_details'], $arr_condition_object_sub_details['object_sub_details']);
					}
					
					foreach ((array)$arr_condition_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_condition_object_sub_description) {
						$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = array_merge((array)$arr_type_set_conditions['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id], $arr_condition_object_sub_description);
					}
				}
			}
		}
		
		return $arr_type_set_conditions;
	}
	
	public static function getExportCollector($type_id, $arr_filters, $arr_scope, $arr_conditions = false) {
		
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
		
		$collect = new CollectTypeObjects($arr_type_network_paths);
		$collect->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id']), 'project_id' => $_SESSION['custom_projects']['project_id']]);
		$collect->setConditions('text', function($cur_type_id) use ($type_id, $arr_conditions) {
			
			if ($cur_type_id == $type_id && $arr_conditions !== false) {
				return $arr_conditions;
			}
			
			return toolbar::getTypeConditions($cur_type_id);
		});
		$collect->init($arr_filters, false);
			
		$arr_collect_info = $collect->getResultInfo();
		
		foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
			
			$arr_type_set = StoreType::getTypeSet($cur_type_id);
			
			if ($arr_project['types'][$cur_type_id]['type_filter_id']) {
								
				$arr_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
				$collect->addLimitTypeFilters($cur_type_id, FilterTypeObjects::convertFilterInput($arr_filters['object']), $arr_project['types'][$cur_type_id]['type_filter_object_subs']);
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

				$arr_selection = [
					'object' => [],
					'object_descriptions' => [],
					'object_sub_details' => []
				];
				
				if ($arr_settings['name'] || $arr_settings['sources']) {
					
					$arr_selection['object']['all'] = true;
				}
				
				if ($arr_settings['analysis']) {
					
					$arr_analysis = data_analysis::getTypeAnalysis($cur_type_id);
					$arr_analysis_context = data_analysis::getTypeAnalysisContext($cur_type_id);
					
					if ($arr_analysis || $arr_analysis_context) {
						
						$arr_selection['object']['analysis'] = [];
						
						if ($arr_analysis) {
							
							$arr_selection['object']['analysis'][] = ['id' => $arr_analysis['id'], 'user_id' => $arr_analysis['user_id'], 'algorithm' => $arr_analysis['algorithm']];
						}
						if ($arr_analysis_context['include']) {
							
							$arr_analysis_context_analyses = cms_nodegoat_custom_projects::getProjectTypeAnalyses($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $cur_type_id, array_keys($arr_analysis_context['include']), $arr_use_project_ids);
								
							foreach ($arr_analysis_context_analyses as $arr_analysis_context_analysis) {
									
								$arr_selection['object']['analysis'][] = ['id' => $arr_analysis_context_analysis['id'], 'algorithm' => $arr_analysis_context_analysis['algorithm']];
							}
						}
					}
				}
				
				$arr_selected = ($arr_settings['selection'] ?: []);
				
				foreach ($arr_selected as $id => $value) {
					
					$object_description_id = $value['object_description_id'];
					
					if ($object_description_id) {
							
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_descriptions'][$object_description_id]['object_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, $object_description_id)) {
							continue;
						}
						
						$arr_selection['object_descriptions'][$object_description_id] = ['object_description_id' => true];
					}
					
					$object_sub_details_id = $value['object_sub_details_id'];
					
					if ($object_sub_details_id) {

						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, false, $object_sub_details_id)) {
							continue;
						}
						
						$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['all' => true];
						
						$object_sub_description_id = $value['object_sub_description_id'];
						
						if ($object_sub_description_id) {
							
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_clearance_view'] || !custom_projects::checkAccessTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
							
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = ['object_sub_description_id' => true];
						} else if (!$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions']) { // Set empty selection on sub object descriptions if there are none selected
							
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = [];
						}
					}
				}
								
				$collect->setPathOptions([$path => [
					'arr_selection' => $arr_selection,
					'arr_filtering' => $arr_filtering
				]]);
			}
		}
		
		return $collect;
	}
	
	public static function getTypeExportSettings($type_id) {
		
		$export_settings_id = 0;
				
		$arr_export_settings = cms_nodegoat_custom_projects::getProjectTypeExportSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $export_settings_id);
				
		$arr_export_settings = self::parseTypeExportSettings($type_id, $arr_export_settings);
		
		return $arr_export_settings;
	}
	
	public static function parseTypeExportSettings($type_id, $arr_export_settings) {
		
		if ($arr_export_settings['format_type']) { // 'format_type' etc. is part of the form configuration
			
			$arr =  [
				'format' => [
					'type' => $arr_export_settings['format_type'],
					'include_description_name' => $arr_export_settings['format_include_description_name'],
					'settings' => $arr_export_settings['format_settings'],
				],
				'scope' => $arr_export_settings['scope']
			];
		} else { 
			
			$arr = $arr_export_settings;
		}
		
		$arr = [
			'format' => [
				'type' => ($arr['format']['type'] ?: 'csv'),
				'include_description_name' => ($arr['format']['include_description_name'] !== null ? (bool)$arr['format']['include_description_name'] : true),
				'settings' => $arr['format']['settings'],
			],
			'scope' => $arr['scope']
		];
		
		$s_arr = &$arr['format']['settings']['csv'];
		$s_arr = [
			'separator' => ($s_arr['separator'] ?: ','),
			'enclose' => ($s_arr['enclose'] ?: '"')
		];
		
		$arr['scope'] = data_model::parseTypeNetworkPick($arr['scope']);

		return $arr;
	}
}
