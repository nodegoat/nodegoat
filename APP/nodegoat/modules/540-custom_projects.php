<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class custom_projects extends base_module {

	public static function moduleProperties() {
		static::$label = 'Custom Projects';
		static::$parent_label = 'nodegoat';
	}
	
	protected $arr_access = [
		'general' => []
	];
	
	public static function modulePreload() {
		
		if (SiteStartVars::getPage('name') == 'viewer') {

			$arr_request_vars = SiteStartVars::getModuleVariables(0);

			if ($arr_request_vars[0]) {
				
				$public_user_interface_id = $arr_request_vars[0];
				
			} else {
				
				$public_user_interface_id = cms_nodegoat_public_interfaces::getDefaultPublicInterfaceID();
			}
			
			toolbar::setActionSpace('viewer_'.(int)$public_user_interface_id);
			
			ui::getPublicUserInterfaceModuleVars();
			
			if (SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX) {

				$_SESSION['public_interface'][$public_user_interface_id]['clearance'] = 0;

				$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
				$theme_color = $arr_public_interface_settings['color'];
			
				if ($theme_color) {
					
					$arr_theme = ['theme_color' => $theme_color, 'background_color' => $theme_color];
					SiteEndVars::setTheme($arr_theme);
				}
				
				SiteEndVars::addHeadTag('<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">');
				
				$arr_public_interface_style = cms_nodegoat_public_interfaces::getPublicInterfaceStyle($public_user_interface_id);
				SiteEndVars::addHeadTag('<style>'.$arr_public_interface_style['css'].'</style>');
				
				$arr_types_all = StoreType::getTypes(); // Source can be any type
				$arr_style_type = [];

				foreach ($arr_types_all as $type_id => $arr_type) {
					
					if (!$arr_type['color']) {
						continue;
					}
						
					$arr_style_type[] = '.body span.a[data-id^="'.$type_id.'_"], .body span.a[id*="-'.$type_id.'_"] { color: '.$arr_type['color'].'; }
						.body span.tag-active[id*="-'.$type_id.'_"], .view_type_object .marginalia p[class^="'.$type_id.'"].tag-active span { color: #fff; background-color: '.$arr_type['color'].'; border-color: '.$arr_type['color'].';}
						.labmap.soc g[class~="type-'.$type_id.'"] > circle { fill: '.$arr_type['color'].'; }
						.ui .graph g[class~="type-'.$type_id.'"] > circle { fill: '.$arr_type['color'].'; }
						.ui li[class~="type-'.$type_id.'"], .ui div[class~="type-'.$type_id.'"], .ui .keywords span[class~="type-'.$type_id.'"] { background-color: '.$arr_type['color'].'; } ';
				}
				
				$arr_public_interface_projects = cms_nodegoat_public_interfaces::getPublicInterfaceProjectIDs($public_user_interface_id);

				foreach ((array)$arr_public_interface_projects as $public_interface_project_id) {
					
					$arr_project = StoreCustomProject::getProjects($public_interface_project_id);

					foreach ($arr_project['types'] as $type_id => $arr_type) {
						
						if (!$arr_type['color']) {
							continue;
						}
							
						$arr_style_type[] = '.body span.a[data-id^="'.$type_id.'_"], .body span.a[id*="-'.$type_id.'_"] { color: '.$arr_type['color'].'; }
							.body span.tag-active[id*="-'.$type_id.'_"], .view_type_object .marginalia p[class^="'.$type_id.'"].tag-active span { color: #fff; background-color: '.$arr_type['color'].'; border-color: '.$arr_type['color'].';}
							.labmap.soc g[class~="type-'.$type_id.'"] > circle { fill: '.$arr_type['color'].'; }
							.ui .graph g[class~="type-'.$type_id.'"] > circle { fill: '.$arr_type['color'].'; }
							.ui li[class~="type-'.$type_id.'"], .ui div[class~="type-'.$type_id.'"], .ui .keywords span[class~="type-'.$type_id.'"] { background-color: '.$arr_type['color'].'; } ';
					}
				}					
				
				if ($arr_style_type) {
					
					SiteEndVars::addHeadTag('<style>
						'.implode(' ', $arr_style_type).'
					</style>');
				}
			}
		} else if (SiteStartVars::getRequestState() == SiteStartVars::REQUEST_API) {
			
			$_SESSION['custom_projects']['project_id'] = false;
			toolbar::setActionSpace('api');
			
			$arr_request_vars = SiteStartVars::getModuleVariables(0);
				
			$arr_api_configuration = cms_nodegoat_api::getConfiguration(SiteStartVars::getAPI('id'));
			$arr_projects = StoreCustomProject::getProjects();
			
			$is_data_model = ($arr_request_vars[0] == 'model');
			$is_administrator = ($_SESSION['USER_ID'] && $_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN);

			if ($is_data_model && $is_administrator) {
				
				 // Full access to domain's data model, not project related
			} else if ($arr_request_vars[0] == 'project') { // Select user selected project
				
				$project_id = (int)$arr_request_vars[1];
				
				if (isset($arr_api_configuration['projects'][$project_id]) && $arr_projects[$project_id]) {
					$_SESSION['custom_projects']['project_id'] = $project_id;
				}
			} else if ($arr_api_configuration['projects']) { // Select default configured API project
					
				foreach ($arr_api_configuration['projects'] as $project_id => $arr_api_project) {
					
					if (!$arr_projects[$project_id]) { // Make sure the project itself also exists
						continue;
					}
					
					$_SESSION['custom_projects']['project_id'] = (!$_SESSION['custom_projects']['project_id'] || $arr_api_project['is_default'] ? $project_id : $_SESSION['custom_projects']['project_id']);
					
					if ($arr_api_project['is_default']) {
						break;
					}
				}
			}
			
			if ($is_data_model && $is_administrator) {
				
			} else if (!$_SESSION['custom_projects']['project_id']) {

				error(getLabel('msg_missing_information').' No valid project provided.', TROUBLE_INVALID_REQUEST, LOG_CLIENT);
			} else if ($_SESSION['USER_ID'] && !isset($_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$_SESSION['custom_projects']['project_id']])) {
						
				error(getLabel('msg_access_denied').' No valid project provided.', TROUBLE_ACCESS_DENIED, LOG_CLIENT);
			} else if (!$_SESSION['USER_ID'] && $arr_api_configuration['projects'][$_SESSION['custom_projects']['project_id']]['require_authentication']) {
					
				error(getLabel('msg_access_denied'), TROUBLE_ACCESS_DENIED, LOG_CLIENT);
			}
		} else {
		
			$_SESSION['custom_projects']['project_id'] = false;
			toolbar::setActionSpace();
			
			if (!$_SESSION['USER_ID']) {
				return;
			}
			
			toolbar::setActionSpace(0);
			
			if ($_SESSION['CUR_USER'][DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')]) {
				
				foreach ($_SESSION['CUR_USER'][DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')] as $project_id => $arr_project_link) {
					
					if (!$_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$project_id]) { // Make sure the project itself is also available and exists
						continue;
					}
					
					$_SESSION['custom_projects']['project_id'] = (!$_SESSION['custom_projects']['project_id'] || $arr_project_link['is_active'] ? $project_id : $_SESSION['custom_projects']['project_id']);
				}
				
				$arr_request_vars = SiteStartVars::getModuleVariables(0);
				
				if (!empty($arr_request_vars['project'][0])) {
					
					SiteEndVars::setModuleVariables(0, ['project' => false]);
					
					if ($arr_request_vars['project'][0] != $_SESSION['custom_projects']['project_id'] && !empty($_SESSION['CUR_USER'][DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')][$arr_request_vars['project'][0]])) {
						
						self::setUserProjectID($arr_request_vars['project'][0]);
						$_SESSION['custom_projects']['project_id'] = $arr_request_vars['project'][0];
					}
				}
			} else if ($_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')]) {
				
				$arr_project = current($_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')]);
				$_SESSION['custom_projects']['project_id'] = $arr_project['id'];
			}
			
			if ($_SESSION['custom_projects']['project_id']) {
				
				if (SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX) {
							
					$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
					$arr_types_all = StoreType::getTypes(); // Source can be any type
					$arr_style_type = [];
					
					foreach ($arr_types_all as $type_id => $arr_type) {
						
						if ($arr_type['color'] || !empty($arr_project['types'][$type_id]['color'])) {
							
							$str_color = ($arr_project['types'][$type_id]['color'] ?? null ?: $arr_type['color']);
							
							$arr_style_type[] = '.network.type .node[data-type_id="'.$type_id.'"] > h4 > span:first-child { display: inline-block; color: '.$str_color.'; }'
								.' .body span.a[data-id^="'.$type_id.'_"], .body span.a[id*="-'.$type_id.'_"] { color: '.$str_color.'; }'
								.' .body span.tag-active[id*="-'.$type_id.'_"], .view_type_object .marginalia p[class^="'.$type_id.'"].tag-active span { color: #fff; background-color: '.$str_color.'; border-color: '.$str_color.';}'
								.' .labmap.soc g[class~="type-'.$type_id.'"] > circle { fill: '.$str_color.'; }';
						}
					}

					if ($arr_style_type) {
						
						SiteEndVars::addHeadTag('<style>
							'.implode(' ', $arr_style_type).'
						</style>');
					}
				}
				
				if (SiteStartVars::getRequestState() == SiteStartVars::REQUEST_INDEX || SiteStartVars::getRequestState() == SiteStartVars::REQUEST_COMMAND) {
					
					// Check client side if the current project matches.			
					SiteEndVars::setFeedback('project_id', $_SESSION['custom_projects']['project_id']);
				}
			}
		}
		
		Settings::get('hook_custom_project');
		
		if ($_SESSION['custom_projects']['project_id']) {
						
			GenerateTypeObjects::setConditionsResource(function($type_id) {
				
				return toolbar::getTypeConditions($type_id, true);
			});
		}
	}

	public static function accountSettings() {
		
		account::$arr_external_modules['custom_projects'] = [];
		
		return [
			'values' => function() {
				
				$arr_user_project_type_filters = cms_nodegoat_custom_projects::getUserProjectTypeFilters($_SESSION['USER_ID']);
				$arr_types = StoreType::getTypes();
				
				foreach ((array)$_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')] as $project_id => $arr_project) {

					$arr_project_type_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, $_SESSION['USER_ID']);
					
					if (!$arr_project_type_filters) {
						continue;
					}
													
					$arr_project_types = array_intersect_key($arr_types, array_flip(array_keys($arr_project_type_filters)));
					$arr_sorter = [];
				
					foreach (($arr_user_project_type_filters[$project_id] ?: [[]]) as $type_id => $arr_filters) {
						
						$arr_source_filter_names = [];
						foreach ((array)$arr_filters as $key => $value) {
							$arr_source_filter_names[$value['source']][$key] = $arr_project_type_filters[$type_id][$key]['label'];
						}
						
						foreach (($arr_source_filter_names ?: [[]]) as $source => $arr_filter_names) {
						
							$unique = uniqid(cms_general::NAME_GROUP_ITERATOR);
							
							$arr_sorter[] = ['value' => [
									'<select>'.Labels::parseTextVariables(cms_general::createDropdown($arr_project_types, $type_id, true)).'</select>',
									'<fieldset><ul>
										<li><label></label><div>
											'.cms_general::createSelectorRadio([
												['id' => 1, 'name' => getLabel('lbl_object')],
												['id' => 2, 'name' => getLabel('lbl_discussion')],
												['id' => 3, 'name' => getLabel('lbl_both')]
											], 'project_type_filters['.$project_id.']['.$unique.'][source]', $source)
										.'</div></li>
										<li><label></label><div>
											'.cms_general::createMultiSelect('project_type_filters['.$project_id.']['.$unique.'][filter_id]', 'y:custom_projects:lookup_user_project_type_filter-'.$project_id, $arr_filter_names, false, ['list' => true])
										.'</div></li>
									</ul></fieldset>'
								]
							];
						}
					}
						
					$return .= '<fieldset><legend>'.strEscapeHTML(Labels::parseTextVariables($arr_project['name'])).'</legend><ul>
						<li>
							<label></label><div><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></div>
						</li>
						<li>
							<label></label>'.cms_general::createSorter($arr_sorter, false).'
						</li>
					</ul></fieldset>';
				}
				
				$return = '<div class="custom_project_filters options fieldsets"><div>
					'.($return ?: getLabel('lbl_none')).'
				</div></div>';
								
				return [
					getLabel('lbl_filter_notify') => $return
				];
			},
			'update' => function($value) {
				cms_nodegoat_custom_projects::updateUserProjectTypeFilters($_SESSION['USER_ID'], $value['project_type_filters']);
			}
		];
	}
	
	private $show_user_settings = false;
	
	function __construct() {
		
		parent::__construct();
		
		$arr_users_link = pages::getClosestModule('register_by_user');
		$this->show_user_settings = ($arr_users_link && pages::filterClearance([$arr_users_link], $_SESSION['USER_GROUP'], $_SESSION['CUR_USER'][DB::getTableName('TABLE_USER_PAGE_CLEARANCE')]));
	}
	
	public function contents() {
	
		$return .= self::createAddProject();

		$return .= '<table class="display" id="d:custom_projects:data-0">
				<thead>
					<tr>
						<th class="max limit" data-sort="asc-0"><span>'.getLabel('lbl_name').'</span></th>
						<th class="disable-sort"><span>'.getLabel('lbl_types').'</span></th>
						<th class="disable-sort"><span>'.getLabel('lbl_classifications').'</span></th>
						<th class="disable-sort"><span>'.getLabel('lbl_reversals').'</span></th>
						<th class="disable-sort"></th>
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
	
	private static function createAddProject() {
	
		$return .= '<form id="f:custom_projects:add-0" class="options">
			<menu>
				<input type="submit" value="'.getLabel('lbl_add').' '.getLabel('lbl_project').'" />
			</menu>
		</form>';
		
		return $return;
	}

	private function createProject($id = false) {
		
		$arr_project = [];
		$arr_use_project_ids = [];
	
		if ((int)$id) {
				
			$arr_project = StoreCustomProject::getProjects($id);
			
			if ($arr_project) {
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
			} else {
				$id = false;
			}
		}
		
		$arr_types = StoreType::getTypes();
		$arr_classes_types = static::getTypesByClass($arr_types);
		$arr_projects = StoreCustomProject::getProjects();
		$arr_project_use_sources = [];
		
		foreach ($arr_projects as $project_id => $arr_cur_project) {
			
			if ($id == $project_id) {
				continue;
			}
			
			$arr_project_use_sources[$project_id] = ['id' => $project_id, 'name' => strEscapeHTML($arr_cur_project['project']['name'])];
		}
		
		$return = '<h1>'.($id ? '<span>'.getLabel('lbl_project').': '.strEscapeHTML(Labels::parseTextVariables($arr_project['project']['name'])).'</span><small title="'.getLabel('lbl_project').' ID">'.$id.'</small>' : '<span>'.getLabel('lbl_project').'</span>').'</h1>
		
		<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_project').'</a></li>
				<li><a href="#">'.getLabel('lbl_organise').'</a></li>
			</ul>
			
			<div>
				<div class="options">
				
					<div class="fieldsets"><div>
				
						<fieldset><legend>'.getLabel('lbl_project').'</legend><ul>
							<li><label>'.getLabel('lbl_name').'</label><div><input name="name" type="text" value="'.strEscapeHTML($arr_project['project']['name']).'" /></div></li>
							<li><label>'.getLabel('lbl_project_full_scope').'</label><div><input name="full_scope_enable" type="checkbox" title="'.getLabel('inf_project_full_scope').'" value="1"'.(!$id || $arr_project['project']['full_scope_enable'] ? ' checked="checked"' : '').' /></div></li>
							<li><label>'.getLabel('lbl_source_referencing').'</label><div><input name="source_referencing_enable" type="checkbox" value="1"'.(!$id || $arr_project['project']['source_referencing_enable'] ? ' checked="checked"' : '').' /></div></li>
							<li><label>'.getLabel('lbl_discussion_objects').'</label><div><input name="discussion_enable" type="checkbox" value="1"'.($arr_project['project']['discussion_enable'] ? ' checked="checked"' : '').' /></div></li>
							<li><label>'.getLabel('lbl_visual_settings').'</label><div><select name="visual_settings_id">'.($id ? Labels::parseTextVariables(cms_general::createDropdown(arrParseRecursive(cms_nodegoat_custom_projects::getProjectVisualSettings($id, false, false, $arr_use_project_ids), 'strEscapeHTML'), $arr_project['project']['visual_settings_id'], true, 'label')) : '').'</select></div></li>
						</ul></fieldset>
								
					</div></div>
					
					<h3>'.getLabel('lbl_use').'</h3>
					
					<div class="tabs">
						<ul>
							<li><a href="#">'.getLabel('lbl_model').'</a></li>
							<li><a href="#">'.getLabel('lbl_references').'</a></li>
							<li><a href="#">'.getLabel('lbl_sources').'</a></li>
							<li><a href="#">'.getLabel('lbl_projects').'</a></li>
						</ul>
						
						<div>
							<div class="options fieldsets"><div>
								
								'.($arr_classes_types[StoreType::TYPE_CLASS_TYPE] ? '<fieldset><legend>'.getLabel('lbl_types').'</legend><ul>
									<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_TYPE], 'types', array_keys((array)$arr_project['types']))).'</li>
								</ul></fieldset>' : '').'
								
								'.($arr_classes_types[StoreType::TYPE_CLASS_CLASSIFICATION] ? '<fieldset><legend>'.getLabel('lbl_classifications').'</legend><ul>
									<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_CLASSIFICATION], 'types', array_keys((array)$arr_project['types']))).'</li>
								</ul></fieldset>' : '').'
								
								'.($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL] ? '<fieldset><legend>'.getLabel('lbl_reversals').'</legend><ul>
									<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL], 'types', array_keys((array)$arr_project['types']))).'</li>
								</ul></fieldset>' : '');
								
								$arr_system_types = [
									['id' => 'system_date_cycle_enable', 'name' => getLabel('lbl_date_cycle')],
									['id' => 'system_ingestion_enable', 'name' => getLabel('lbl_system_ingestion')],
									['id' => 'system_reconciliation_enable', 'name' => getLabel('lbl_system_reconciliation')]
								];
								$arr_system_types_selected = [];
								
								if ($arr_project['project']['system_date_cycle_enable']) {
									$arr_system_types_selected[] = 'system_date_cycle_enable';
								}
								if ($arr_project['project']['system_ingestion_enable']) {
									$arr_system_types_selected[] = 'system_ingestion_enable';
								}
								if ($arr_project['project']['system_reconciliation_enable']) {
									$arr_system_types_selected[] = 'system_reconciliation_enable';
								}
								
								$return .= '<fieldset><legend>'.getLabel('lbl_system').'</legend><ul>
									<li>'.Labels::parseTextVariables(cms_general::createSelectorList(arrParseRecursive($arr_system_types, 'strEscapeHTML'), false, $arr_system_types_selected)).'</li>
								</ul></fieldset>';
						
							$return .= '</div></div>
						</div>
						
						<div>
							<div class="options">
							
								<div class="tabs">
									<ul>
										<li><a href="#">'.getLabel('lbl_date').'</a></li>
										<li><a href="#">'.getLabel('lbl_location').'</a></li>
									</ul>
									
									<div>
										<div class="options">
										
											<section class="info attention">'.getLabel('inf_project_date_references').'</section>
											
											<div class="fieldsets"><div>
												
												'.($arr_classes_types[StoreType::TYPE_CLASS_TYPE] ? '<fieldset><legend>'.getLabel('lbl_types').'</legend><ul>
													<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_TYPE], 'date_types', array_keys((array)$arr_project['date_types']))).'</li>
												</ul></fieldset>' : '').'
												
												'.($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL] ? '<fieldset><legend>'.getLabel('lbl_reversals').'</legend><ul>
													<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL], 'date_types', array_keys((array)$arr_project['date_types']))).'</li>
												</ul></fieldset>' : '').'
												
											</div></div>
											
										</div>
									</div>
									
									<div>
										<div class="options">
										
											<section class="info attention">'.getLabel('inf_project_location_references').'</section>
											
											<div class="fieldsets"><div>
												
												'.($arr_classes_types[StoreType::TYPE_CLASS_TYPE] ? '<fieldset><legend>'.getLabel('lbl_types').'</legend><ul>
													<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_TYPE], 'location_types', array_keys((array)$arr_project['location_types']))).'</li>
												</ul></fieldset>' : '').'
												
												'.($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL] ? '<fieldset><legend>'.getLabel('lbl_reversals').'</legend><ul>
													<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL], 'location_types', array_keys((array)$arr_project['location_types']))).'</li>
												</ul></fieldset>' : '').'
												
											</div></div>
											
										</div>
									</div>
									
								</div>
								
							</div>
						</div>
						
						<div>
							<div class="options">
							
								<section class="info attention">'.getLabel('inf_project_source_references').'</section>
								
								<div class="fieldsets"><div>
									
									'.($arr_classes_types[StoreType::TYPE_CLASS_TYPE] ? '<fieldset><legend>'.getLabel('lbl_types').'</legend><ul>
										<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_classes_types[StoreType::TYPE_CLASS_TYPE], 'source_types', array_keys((array)$arr_project['source_types']))).'</li>
									</ul></fieldset>' : '').'
									
								</div></div>

							</div>
						</div>
						
						<div>
							<div class="options">
																							
								<section class="info attention">'.getLabel('inf_project_use_projects').'</section>
								
								<div class="fieldsets"><div>
									
									'.($arr_project_use_sources ? '<fieldset><legend>'.getLabel('lbl_projects').'</legend><ul>
										<li>'.Labels::parseTextVariables(cms_general::createSelectorList($arr_project_use_sources, 'use_projects', $arr_use_project_ids)).'</li>
									</ul></fieldset>' : '').'
									
								</div></div>
								
							</div>
						</div>

					</div>
					
				</div>
			</div>

			<div>
				<div class="options">

					<section class="info attention">'.getLabel('inf_save_and_edit_section').'</section>';
					
					if ($arr_project['types']) {
						
						$arr_html_tabs = [];
						$arr_ref_type_ids = array_keys($arr_types);
						
						foreach ($arr_project['types'] as $type_id => $arr_project_type) {
							
							$arr_type_set = StoreType::getTypeSet($type_id);
							
							if (!$arr_type_set['type'] || $arr_type_set['type']['class'] == StoreType::TYPE_CLASS_SYSTEM
								
							) {
								continue;
							}
								
							$arr_types_referenced = FilterTypeObjects::getTypesReferenced($type_id, $arr_ref_type_ids, ['dynamic' => false, 'object_sub_locations' => false]);
							
							$arr_html_tabs['links'][] = '<li><a href="#">'.strEscapeHTML(Labels::parseTextVariables($arr_types[$type_id]['name'])).'</a></li>';
							
							$return_tab = '<div>
								<div class="options">
								
									<div class="fieldsets"><div>
									
										<fieldset><legend>'.getLabel('lbl_configuration').'</legend><ul>'
											.($this->show_user_settings ? '<li>
												<label>'.getLabel('lbl_information').'</label>
												<div>'
													.'<input type="hidden" name="types_organise[type_id-'.$type_id.'][type_information]" value="'.($arr_project_type['type_information'] ? strEscapeHTML($arr_project_type['type_information']) : '').'" />'
													.'<button type="button" id="y:custom_projects:set_information-'.$type_id.'" title="'.getLabel('inf_project_type_information').'" class="data neutral popup"><span>info</span></button>'
												.'</div>
											</li>' : '')
											.'<li>
												<label>'.getLabel('lbl_add').'/'.getLabel('lbl_edit').'</label>
												<div title="'.getLabel('inf_project_type_edit').'">'.cms_general::createSelectorRadio([['id' => 1, 'name' => getLabel('lbl_yes')], ['id' => 0, 'name' => getLabel('lbl_no')]], 'types_organise[type_id-'.$type_id.'][type_edit]', (!$arr_project_type || $arr_project_type['type_edit'] ? true : false)).'</div>
											</li>											
										</ul></fieldset>
										
										<fieldset><legend>'.getLabel('lbl_appearance').'</legend><ul>
											<li><label>'.getLabel('lbl_color').'</label><input name="types_organise[type_id-'.$type_id.'][color]" type="text" value="'.$arr_project_type['color'].'" class="colorpicker" title="'.getLabel('inf_project_type_color').'" /></li>
										</ul></fieldset>
										
										<fieldset><legend>'.getLabel('lbl_apply').'</legend><ul>
											<li><label>'.getLabel('lbl_filter').'</label>'
												.'<select name="types_organise[type_id-'.$type_id.'][type_filter_id]" title="'.getLabel('inf_project_type_filter').'">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFilters($id, false, $type_id, false, true, $arr_use_project_ids), $arr_project_type['type_filter_id'], true, 'label').'</select>'
												.'<input name="types_organise[type_id-'.$type_id.'][type_filter_object_subs]" type="checkbox" value="1" title="'.getLabel('inf_project_type_filter_object_subs').'"'.($arr_project_type['type_filter_object_subs'] ? ' checked="checked"' : '').' />'
											.'</li>
											<li><label>'.getLabel('lbl_condition').'</label><select name="types_organise[type_id-'.$type_id.'][type_condition_id]" title="'.getLabel('inf_project_type_condition').'">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeConditions($id, false, $type_id, false, true, $arr_use_project_ids), $arr_project_type['type_condition_id'], true, 'label').'</select></li>
										</ul></fieldset>
										
										<fieldset><legend>'.getLabel('lbl_defaults').'</legend><ul>
											<li><label>'.getLabel('lbl_context').'</label><select name="types_organise[type_id-'.$type_id.'][type_context_id]" title="'.getLabel('inf_project_type_context').'">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeContexts($id, false, $type_id, false, $arr_use_project_ids), $arr_project_type['type_context_id'], true, 'label').'</select></li>
											<li><label>'.getLabel('lbl_frame').'</label><select name="types_organise[type_id-'.$type_id.'][type_frame_id]" title="'.getLabel('inf_project_type_frame').'">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFrames($id, false, $type_id, false, $arr_use_project_ids), $arr_project_type['type_frame_id'], true, 'label').'</select></li>
										</ul></fieldset>
										
									</div></div>
									
									<h3>'.getLabel('lbl_model').'</h3>
									
									<div class="tabs">
										<ul>
											<li><a href="#">'.getLabel('lbl_configuration').'</a></li>
											'.($arr_types_referenced ? '<li><a href="#">'.getLabel('lbl_referenced').'</a></li>' : '').'
										</ul>
											
										<div>
											<div class="options">

												<div class="fieldsets"><div>';
													
													if ($arr_type_set['object_descriptions']) {
															
														$return_tab .= '<fieldset><legend>'.getLabel('lbl_object').' '.getLabel('lbl_descriptions').'</legend><ul>
															<li><ul class="select">';
														
																foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
																	
																	$arr_configuration = (array)$arr_project_type['configuration']['object_descriptions'][$object_description_id];
																	$name = 'types_organise[type_id-'.$type_id.'][configuration][object_descriptions]['.$object_description_id.']';
																	
																	$is_reversal = ($arr_object_description['object_description_ref_type_id'] && $arr_types[$arr_object_description['object_description_ref_type_id']]['class'] == StoreType::TYPE_CLASS_REVERSAL ? true : false);
																	$is_not_editable = $is_reversal;
																	
																	$return_tab .= '<li>'
																		.'<input type="checkbox" name="'.$name.'[edit]" value="1" title="'.getLabel('inf_project_object_description_edit').'"'.($arr_configuration['edit'] ? ' checked="checked"' : '').($is_not_editable ? ' disabled="disabled"' : '').' />'
																		.'<input type="checkbox" name="'.$name.'[view]" value="1" title="'.getLabel('inf_project_object_description_view').'"'.($arr_configuration['view'] ? ' checked="checked"' : '').' />'
																		.($this->show_user_settings ?
																			'<input type="hidden" name="'.$name.'[information]" value="'.($arr_configuration['information'] ? strEscapeHTML($arr_configuration['information']) : '').'" />'
																			.'<button type="button" id="y:custom_projects:set_information-'.$type_id.'_'.$object_description_id.'" title="'.getLabel('inf_project_object_description_information').'" class="data neutral popup"><span>info</span></button>'
																		: '')
																		.'<label>'.strEscapeHTML(Labels::parseTextVariables($arr_object_description['object_description_name'])).'</label>'
																	.'</li>';
																}
															
															$return_tab .= '</ul></li>
														</ul></fieldset>';
													}
													
													if ($arr_type_set['object_sub_details']) {
														
														$return_tab .= '<fieldset><legend>'.getLabel('lbl_object_subs').'</legend><ul>
															<li><ul class="select">';
														
																foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
																	
																	$arr_configuration = (array)$arr_project_type['configuration']['object_sub_details'][$object_sub_details_id]['object_sub_details'];
																	$name = 'types_organise[type_id-'.$type_id.'][configuration][object_sub_details]['.$object_sub_details_id.'][object_sub_details]';

																	$return_tab .= '<li>'
																		.'<input type="checkbox" name="'.$name.'[edit]" value="1" title="'.getLabel('inf_project_object_sub_details_edit').'"'.($arr_configuration['edit'] ? ' checked="checked"' : '').' />'
																		.'<input type="checkbox" name="'.$name.'[view]" value="1" title="'.getLabel('inf_project_object_sub_details_view').'"'.($arr_configuration['view'] ? ' checked="checked"' : '').' />'
																		.($this->show_user_settings ?
																			'<input type="hidden" name="'.$name.'[information]" value="'.($arr_configuration['information'] ? strEscapeHTML($arr_configuration['information']) : '').'" />'
																			.'<button type="button" id="y:custom_projects:set_information-'.$type_id.'_0_'.$object_sub_details_id.'" title="'.getLabel('inf_project_object_sub_details_information').'" class="data neutral popup"><span>info</span></button>'
																		: '')
																		.'<label><span class="sub-name">'.strEscapeHTML(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span></label>'
																	.'</li>';
																	
																	if ($arr_object_sub_details['object_sub_descriptions']) {
																		
																		$return_tab .= '<li><fieldset><ul class="select">';
																				
																			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
																				
																				$arr_configuration = (array)$arr_project_type['configuration']['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
																				$name = 'types_organise[type_id-'.$type_id.'][configuration][object_sub_details]['.$object_sub_details_id.'][object_sub_descriptions]['.$object_sub_description_id.']';
																				
																				$is_reversal = ($arr_object_sub_description['object_sub_description_ref_type_id'] && $arr_types[$arr_object_sub_description['object_sub_description_ref_type_id']]['class'] == StoreType::TYPE_CLASS_REVERSAL ? true : false);
																				$use_object_description_id = ($arr_object_sub_description['object_sub_description_ref_type_id'] && $arr_object_sub_description['object_sub_description_use_object_description_id'] ? true : false);
																				$is_not_editable = ($is_reversal || $use_object_description_id);
																			
																				$return_tab .= '<li>'
																					.'<input type="checkbox" name="'.$name.'[edit]" value="1" title="'.getLabel('inf_project_object_description_edit').'"'.($arr_configuration['edit'] ? ' checked="checked"' : '').($is_not_editable ? ' disabled="disabled"' : '').' />'
																					.'<input type="checkbox" name="'.$name.'[view]" value="1" title="'.getLabel('inf_project_object_description_view').'"'.($arr_configuration['view'] ? ' checked="checked"' : '').' />'
																					.($this->show_user_settings ?
																						'<input type="hidden" name="'.$name.'[information]" value="'.($arr_configuration['information'] ? strEscapeHTML($arr_configuration['information']) : '').'" />'
																						.'<button type="button" id="y:custom_projects:set_information-'.$type_id.'_0_'.$object_sub_details_id.'_'.$object_sub_description_id.'" title="'.getLabel('inf_project_object_description_information').'" class="data neutral popup"><span>info</span></button>'
																					: '')
																					.'<label><span>'.strEscapeHTML(Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'])).'</span></label>'
																				.'</li>';
																			}
																		
																		$return_tab .= '</ul></fieldset></li>';
																	}
																}
															
															$return_tab .= '</ul></li>
														</ul></fieldset>';
													}
													
												$return_tab .= '</div></div>
												
												<fieldset><legend>'.getLabel('lbl_mode_view_add_edit').'</legend><ul>
													<li>
														<label></label>
														<div><label><input type="radio" name="types_organise[type_id-'.$type_id.'][configuration_exclude]" value="0"'.(!$arr_project_type['configuration_exclude'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_include').'</span></label><label><input type="radio" name="types_organise[type_id-'.$type_id.'][configuration_exclude]" value="1"'.($arr_project_type['configuration_exclude'] ? ' checked="checked"' : '').' /><span>'.getLabel('lbl_exclude').'</span></label></div>
													</li>
												</ul></fieldset>
												
											</div>
										</div>';
										
										if ($arr_types_referenced) {
									
											$arr_html_type_referenced_tabs = [];
											
											foreach ($arr_types_referenced as $ref_type_id => $arr_type_referenced) {
											
												$arr_referenced_type_set = StoreType::getTypeSet($ref_type_id);
												$arr_project_include_referenced_types = $arr_project_type['include_referenced_types'][$ref_type_id];
												
												$arr_html_type_referenced_tabs['links'][] = '<li><a href="#">'.Labels::parseTextVariables($arr_referenced_type_set['type']['name']).'</a></li>';
												
												$return_type_referenced_tab = '<div>
													<div class="options fieldsets"><div>';
													
														if ($arr_type_referenced['object_descriptions']) {
															
															$return_type_referenced_tab .= '<fieldset><legend>'.getLabel('lbl_object').' '.getLabel('lbl_descriptions').'</legend><ul>
																<li><ul class="select">';
															
																	foreach ($arr_type_referenced['object_descriptions'] as $object_description_id => $arr_object_description_referenced) {
																		
																		$arr_object_description = $arr_referenced_type_set['object_descriptions'][$object_description_id];
																		
																		$arr_configuration = (array)$arr_project_include_referenced_types['object_descriptions'][$object_description_id];
																		$name = 'types_organise[type_id-'.$type_id.'][include_referenced_types]['.$ref_type_id.'][object_descriptions]['.$object_description_id.']';
																		
																		$return_type_referenced_tab .= '<li>'
																			.'<input type="checkbox" name="'.$name.'[edit]" value="1" title="'.getLabel('inf_project_include_referenced_edit').'"'.($arr_configuration['edit'] ? ' checked="checked"' : '').' />'
																			.'<input type="checkbox" name="'.$name.'[view]" value="1" title="'.getLabel('inf_project_include_referenced_view').'"'.($arr_configuration['view'] ? ' checked="checked"' : '').' />'
																			.($this->show_user_settings ?
																				'<input type="hidden" name="'.$name.'[information]" value="'.($arr_configuration['information'] ? strEscapeHTML($arr_configuration['information']) : '').'" />'
																				.'<button type="button" id="y:custom_projects:set_information-'.$ref_type_id.'_'.$object_description_id.'" title="'.getLabel('inf_project_object_description_information').'" class="data neutral popup"><span>info</span></button>'
																			: '')
																			.'<label>'.strEscapeHTML(Labels::parseTextVariables($arr_object_description['object_description_name'])).'</label>'
																		.'</li>';
																	}
																
																$return_type_referenced_tab .= '</ul></li>
															</ul></fieldset>';
														}
													
														if ($arr_type_referenced['object_sub_details']) {
															
															$return_type_referenced_tab .= '<fieldset><legend>'.getLabel('lbl_object_sub').' '.getLabel('lbl_descriptions').'</legend><ul>
																<li><ul class="select">';
															
																	foreach ($arr_type_referenced['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details_referenced) {
																		
																		$arr_object_sub_details = $arr_referenced_type_set['object_sub_details'][$object_sub_details_id];

																		foreach ((array)$arr_object_sub_details_referenced['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description_referenced) {
																			
																			$arr_object_sub_description = $arr_object_sub_details['object_sub_descriptions'][$object_sub_description_id];
																			
																			$arr_configuration = (array)$arr_project_include_referenced_types['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
																			$name = 'types_organise[type_id-'.$type_id.'][include_referenced_types]['.$ref_type_id.'][object_sub_details]['.$object_sub_details_id.'][object_sub_descriptions]['.$object_sub_description_id.']';
																		
																			$return_type_referenced_tab .= '<li>'
																				.'<input type="checkbox" name="'.$name.'[edit]" value="1" title="'.getLabel('inf_project_include_referenced_edit').'"'.($arr_configuration['edit'] ? ' checked="checked"' : '').' />'
																				.'<input type="checkbox" name="'.$name.'[view]" value="1" title="'.getLabel('inf_project_include_referenced_view').'"'.($arr_configuration['view'] ? ' checked="checked"' : '').' />'
																				.($this->show_user_settings ?
																					'<input type="hidden" name="'.$name.'[information]" value="'.($arr_configuration['information'] ? strEscapeHTML($arr_configuration['information']) : '').'" />'
																					.'<button type="button" id="y:custom_projects:set_information-'.$ref_type_id.'_0_'.$object_sub_details_id.'_'.$object_sub_description_id.'" title="'.getLabel('inf_project_object_description_information').'" class="data neutral popup"><span>info</span></button>'
																				: '')
																				.'<label><span class="sub-name">'.strEscapeHTML(Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name'])).'</span> <span>'.strEscapeHTML(Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name'])).'</span></label>'
																			.'</li>';
																		}
																	}
																
																$return_type_referenced_tab .= '</ul></li>
															</ul></fieldset>';
														}
																																	
													$return_type_referenced_tab .= '</div></div>
												</div>';
												
												$arr_html_type_referenced_tabs['content'][] = $return_type_referenced_tab;
											}

											$return_tab .= '<div>
												<div class="options">
												
													<div class="tabs">
														<ul>
															'.implode('', $arr_html_type_referenced_tabs['links']).'
														</ul>
														'.implode('', $arr_html_type_referenced_tabs['content']).'
													</div>
												
												</div>
											</div>';
										}

									$return_tab .= '</div>
									
								</div>
							</div>';
							
							$arr_html_tabs['content'][] = $return_tab;
						}
						
						if ($arr_html_tabs['links']) {
								
							$return .= '<div class="tabs" data-sorting="1">
								<ul>
									'.implode('', $arr_html_tabs['links']).'
								</ul>
								'.implode('', $arr_html_tabs['content']).'
							</div>';
						}
					}
					
				$return .= '</div>
			</div>
		</div>
		
		<menu class="options">
			<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_project').'" /><input type="submit" name="do_discard" value="'.getLabel('lbl_cancel').'" />
		</menu>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createProjectOverview($project_id) {
		
		$arr_project = StoreCustomProject::getProjects($project_id);
		
		$return = '<div class="project-overview">
			<div>
				<h1><span>'.getLabel('lbl_project').': '.strEscapeHTML(Labels::parseTextVariables($arr_project['project']['name'])).'</span><small title="'.getLabel('lbl_project').' ID">'.$project_id.'</small></h1>
				<form class="options" id="y:custom_projects:get_overview_graph-'.$project_id.'">
					<label>'.getLabel('lbl_mode').':</label><label><input type="checkbox" name="mode[references]" value="1" /><span>'.getLabel('lbl_references_only').'</span></label>
					<label>'.getLabel('lbl_size').':</label><label><input type="checkbox" name="size_full" value="1" /><span>'.getLabel('lbl_height_full').'</span></label>
					<label>'.getLabel('lbl_download').':</label>'
						.'<input type="number" name="download_resolution" step="1" min="72" value="300" title="DPI" />'
						.'<button type="button" name="download" value=""><span class="icon">'.getIcon('download').'</span></button>'
				.'</form>
			</div>
			'.$this->createProjectOverviewGraph($project_id).'
		</div>';

		return $return;
	}
	
	public function createProjectOverviewGraph($project_id, $arr_options = []) {
		 
		$str_info = '<tspan>'.SERVER_NAME.' · '.date('d-m-Y').'</tspan>';
		
		$graph = new CreateProjectOverviewGraph($_SESSION['USER_ID'], $project_id);
		$graph->setMode($arr_options['mode']);
		$graph->setInfo($str_info);
		
		$svg = $graph->generate();
		$arr_positions = $graph->getPositions();
		$str_positions = value2JSON($arr_positions);
		
		$return = '<div data-positions="'.strEscapeHTML($str_positions).'">
			'.$svg.'
		</div>';

		return $return;
	}
	
	public function createSelectProjectUser() {
		
		$arr_select_projects = self::getUserProjects();
						
		$return .= '<fieldset><legend>'.getLabel('lbl_select').' '.getLabel('lbl_project').'</legend><ul>
			<li>'.Labels::parseTextVariables(cms_general::createSelectorRadioList(arrParseRecursive(arrValuesRecursive('project', $arr_select_projects), 'strEscapeHTML'), 'project_id', $_SESSION['custom_projects']['project_id'])).'</li>
		</ul></fieldset>';
							
		return $return;
	}
	
	public function createFilterStore($project_id, $user_id, $filter_id) {
		
		if ($filter_id) {
			$arr_filter = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, $user_id, false, $filter_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false));
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		if ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN) {
			$arr_level[] = ['id' => 'admin', 'name' => getLabel('lbl_clearance_admin')];
		}
		
		$return = '<h2>'.($filter_id ? '<span>'.getLabel('lbl_filter').': '.strEscapeHTML($arr_filter['name']).'</span><small title="'.getLabel('lbl_filter').' ID">'.$filter_id.'</small>' : '<span>'.getLabel('lbl_filter').'</span>').'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_filter['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_filter['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_filter['parent_user_id'] ? 'admin' : ($arr_filter['user_id'] ? 'personal' : 'project'))).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createScopeStore($project_id, $user_id, $scope_id) {
		
		if ($scope_id) {
			
			$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($project_id, $user_id, false, $scope_id);
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		$return = '<h2>'.($scope_id ? '<span>'.getLabel('lbl_scope').': '.strEscapeHTML($arr_scope['name']).'</span><small title="'.getLabel('lbl_scope').' ID">'.$scope_id.'</small>' : '<span>'.getLabel('lbl_scope').'</span>').'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_scope['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_scope['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_scope['user_id'] ? 'personal' : 'project')).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createContextStore($project_id, $user_id, $context_id) {
		
		if ($context_id) {
			
			$arr_context = cms_nodegoat_custom_projects::getProjectTypeContexts($project_id, $user_id, false, $context_id);
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		$return = '<h2>'.($filter_id ? getLabel('lbl_context').': '.strEscapeHTML($arr_context['name']) : getLabel('lbl_context')).'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_context['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_context['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_context['user_id'] ? 'personal' : 'project')).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createFrameStore($project_id, $user_id, $frame_id) {
		
		if ($frame_id) {
			
			$arr_frame = cms_nodegoat_custom_projects::getProjectTypeFrames($project_id, $user_id, false, $frame_id);
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		$return = '<h2>'.($frame_id ? getLabel('lbl_frame').': '.strEscapeHTML($arr_frame['name']) : getLabel('lbl_frame')).'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_frame['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_frame['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_frame['user_id'] ? 'personal' : 'project')).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createVisualSettingsStore($project_id, $user_id, $visual_settings_id) {
		
		if ($visual_settings_id) {
			$arr_visual_settings = cms_nodegoat_custom_projects::getProjectVisualSettings($project_id, $user_id, $visual_settings_id);
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		$return = '<h2>'.($visual_settings_id ? getLabel('lbl_visual_settings').': '.strEscapeHTML($arr_visual_settings['name']) : getLabel('lbl_visual_settings')).'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_visual_settings['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_visual_settings['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_visual_settings['user_id'] ? 'personal' : 'project')).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createScenarioStore($project_id, $user_id, $type_id, $scenario_id) {
		
		if ($scenario_id) {
			$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($project_id, $user_id, false, $scenario_id);
		}
		
		$arr_project = StoreCustomProject::getProjects($project_id);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		Labels::setVariable('setting', 'Filter');
		$inf_use_current_filter = getLabel('inf_scenario_use_current_setting', 'L', true).' '.getLabel('inf_scenario_disables_cache');
		Labels::setVariable('setting', 'Scope');
		$inf_use_current_scope = getLabel('inf_scenario_use_current_setting', 'L', true).' '.getLabel('inf_scenario_disables_cache');
		Labels::setVariable('setting', 'Condition');
		$inf_use_current_condition = getLabel('inf_scenario_use_current_setting', 'L', true).' '.getLabel('inf_scenario_disables_cache');
		
		Labels::setVariable('setting', 'Context');
		$inf_use_current_context = getLabel('inf_scenario_use_current_setting', 'L', true);
		Labels::setVariable('setting', 'Analysis');
		$inf_use_current_analysis = getLabel('inf_scenario_use_current_setting', 'L', true);
		Labels::setVariable('setting', 'Analysis Context');
		$inf_use_current_analysis_context = getLabel('inf_scenario_use_current_setting', 'L', true);
		
		Labels::setVariable('setting', 'Frame');
		$inf_use_current_frame = getLabel('inf_scenario_use_current_setting', 'L', true);
		Labels::setVariable('setting', 'Visual Settings');
		$inf_use_current_visual_settings = getLabel('inf_scenario_use_current_setting', 'L', true);
		
		$return = '<h2>'.($scenario_id ? '<span>'.getLabel('lbl_scenario').': '.strEscapeHTML(Labels::parseTextVariables($arr_scenario['name'])).'</span><small title="'.getLabel('lbl_scenario').' ID">'.$scenario_id.'</small>' : '<span>'.getLabel('lbl_scenario').'</span>').'</h2>
		
		<div class="scenario options fieldsets"><div>
		
			<fieldset><legend>'.getLabel('lbl_scenario').'</legend>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_scenario['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_scenario['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_attribution').'</label><input type="text" name="attribution" value="'.strEscapeHTML($arr_scenario['attribution']).'" /></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_scenario['user_id'] ? 'personal' : 'project')).'</span></li>
					<li><label>'.getLabel('lbl_cache_retain').'</label>'
						.'<input type="checkbox" value="1" name="cache_retain" title="'.getLabel('inf_scenario_cache_retain').'"'.($arr_scenario['cache_retain'] ? ' checked="checked"' : '').' />'
					.'</li>
				</ul>
			</fieldset>
			
			<fieldset><legend>'.getLabel('lbl_data').'</legend>
				<ul>
					<li><label>'.getLabel('lbl_filter').'</label>'
						.'<select name="filter_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, $_SESSION['USER_ID'], $type_id, false, false, $arr_use_project_ids), $arr_scenario['filter_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="filter_use_current" title="'.$inf_use_current_filter.'"'.($arr_scenario['filter_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
					<li><label>'.getLabel('lbl_scope').'</label>'
						.'<select name="scope_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeScopes($project_id, $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), $arr_scenario['scope_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="scope_use_current" title="'.$inf_use_current_scope.'"'.($arr_scenario['scope_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
					<li><label>'.getLabel('lbl_conditions').'</label>'
						.'<select name="condition_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeConditions($project_id, $_SESSION['USER_ID'], $type_id, false, false, $arr_use_project_ids), $arr_scenario['condition_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="condition_use_current" title="'.$inf_use_current_condition.'"'.($arr_scenario['condition_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
				</ul>
			</fieldset>
			
			<fieldset><legend>'.getLabel('lbl_output').'</legend>
				<ul>
					<li><label>'.getLabel('lbl_context').'</label>'
						.'<select name="context_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeContexts($project_id, $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), $arr_scenario['context_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="context_use_current" title="'.$inf_use_current_context.'"'.($arr_scenario['context_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
					<li><label>'.getLabel('lbl_frame').'</label>'
						.'<select name="frame_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFrames($project_id, $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), $arr_scenario['frame_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="frame_use_current" title="'.$inf_use_current_frame.'"'.($arr_scenario['frame_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
					<li><label>'.getLabel('lbl_visual_settings').'</label>'
						.'<select name="visual_settings_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectVisualSettings($project_id, $_SESSION['USER_ID'], false, $arr_use_project_ids), $arr_scenario['visual_settings_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="visual_settings_use_current" title="'.$inf_use_current_visual_settings.'"'.($arr_scenario['visual_settings_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
				</ul>
			</fieldset>
			
			<fieldset><legend>'.getLabel('lbl_workspace').'</legend>
				<ul>
					<li><label>'.getLabel('lbl_analysis').'</label>'
						.'<select name="analysis_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeAnalyses($project_id, $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), $arr_scenario['analysis_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="analysis_use_current" title="'.$inf_use_current_analysis.'"'.($arr_scenario['analysis_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
					<li><label>'.getLabel('lbl_analysis_context').'</label>'
						.'<select name="analysis_context_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeAnalysesContexts($project_id, $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), $arr_scenario['analysis_context_id'], true, 'label').'</select>'
						.'<input type="checkbox" value="1" name="analysis_context_use_current" title="'.$inf_use_current_analysis_context.'"'.($arr_scenario['analysis_context_use_current'] ? ' checked="checked"' : '').' />'
					.'</li>
				</ul>
			</fieldset>
		
		</div></div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createConditionStore($project_id, $user_id, $condition_id) {
		
		if ($condition_id) {
			$arr_condition = cms_nodegoat_custom_projects::getProjectTypeConditions($project_id, $user_id, false, $condition_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false));
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		if ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN) {
			$arr_level[] = ['id' => 'admin', 'name' => getLabel('lbl_clearance_admin')];
		}
		
		$return = '<h2>'.($condition_id ? '<span>'.getLabel('lbl_condition').': '.strEscapeHTML($arr_condition['name']).'</span><small title="'.getLabel('lbl_condition').' ID">'.$condition_id.'</small>' : '<span>'.getLabel('lbl_condition').'</span>').'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_condition['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_condition['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_condition['parent_user_id'] ? 'admin' : ($arr_condition['user_id'] ? 'personal' : 'project'))).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createAnalysisStore($project_id, $user_id, $analysis_id) {
		
		if ($analysis_id) {
			
			$arr_analysis = cms_nodegoat_custom_projects::getProjectTypeAnalyses($project_id, $user_id, false, $analysis_id);
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		$return = '<h2>'.($analysis_id ? getLabel('lbl_analysis').': '.strEscapeHTML($arr_analysis['name']) : getLabel('lbl_analysis')).'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_analysis['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_analysis['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_analysis['user_id'] ? 'personal' : 'project')).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createAnalysisContextStore($project_id, $user_id, $analysis_context_id) {
		
		if ($analysis_context_id) {
			
			$arr_analysis_context = cms_nodegoat_custom_projects::getProjectTypeAnalysesContexts($project_id, $user_id, false, $analysis_context_id);
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		$return = '<h2>'.($analysis_context_id ? getLabel('lbl_analysis_context').': '.strEscapeHTML($arr_analysis_context['name']) : getLabel('lbl_analysis_context')).'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_analysis_context['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_analysis_context['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_analysis_context['user_id'] ? 'personal' : 'project')).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public function createExportSettingsStore($project_id, $user_id, $export_setting_id) {
		
		if ($export_setting_id) {
			
			$arr_export_settings = cms_nodegoat_custom_projects::getProjectTypeExportSettings($project_id, $user_id, false, $export_setting_id);
		}
		
		$arr_level = [['id' => 'project', 'name' => getLabel('lbl_project')], ['id' => 'personal', 'name' => getLabel('lbl_personal')]];
		
		$return = '<h2>'.($export_setting_id ? getLabel('lbl_export').': '.strEscapeHTML($arr_export_settings['name']) : getLabel('lbl_export')).'</h2>
		
		<div class="options">
			<fieldset>
				<ul>
					<li><label>'.getLabel('lbl_name').'</label><input type="text" name="name" value="'.strEscapeHTML($arr_export_settings['name']).'" /></li>
					<li><label>'.getLabel('lbl_description').'</label><textarea name="description">'.$arr_export_settings['description'].'</textarea></li>
					<li><label>'.getLabel('lbl_scope').'</label><span>'.cms_general::createSelectorRadio($arr_level, 'usage', ($arr_export_settings['user_id'] ? 'personal' : 'project')).'</span></li>
				</ul>
			</fieldset>
		</div>';
		
		$this->validate['name'] = 'required';
		
		return $return;
	}
	
	public static function createInformationIcon($str_html, $str_icon = 'info-point') {
		
		if (!$str_html) {
			return '';
		}
		
		$str_html = parseBody($str_html, ['function' => 'strEscapeHTML']);
		$str_html = '<span title="'.$str_html.'" class="icon a info">'.getIcon($str_icon).'</span>';
		
		return $str_html;
	}
	
	public static function css() {
	
		$return = '
			.custom_projects ul.select > li > label > div .info.icon.a { margin-left: 0.1em; }
			.custom_projects ul.select > li > label > div .info.icon.a svg { height: 1em; }
			.custom_projects ul.select > li > label > div .info.icon.a + span  { margin-left: 0.4em; font-style: italic; }
			.custom_projects ul.select > li > label > div .info.icon.a + span + span { margin-left: 0.4em; }
		
			.project-overview { height: 100%; display: flex; flex-flow: column nowrap; align-content: flex-start; }
			.project-overview > div:last-child { flex: 1 1 auto; overflow: hidden; }
			.project-overview > div:last-child > svg { width: 100%; height: 100%; }
			
			.project-overview > div:last-child > svg .box { cursor: pointer; }
			.project-overview > div:last-child > svg .connect-boxes > circle,
			.project-overview > div:last-child > svg .connect-lines > path { pointer-events: none; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.custom_projects', function(elm_scripter) {
			
			elm_scripter.on('click', '[id=d\\\:custom_projects\\\:data-0] .edit', function() {
			
				COMMANDS.quickCommand(this, elm_scripter.find('form'), {html: 'replace'});
			}).on('command', '[id^=y\\\:custom_projects\\\:set_information-]', function() {
			
				var cur = $(this);
				var elm_target = cur.prev('input[type=hidden]');
				
				COMMANDS.setData(cur, {information: elm_target.val()});
				COMMANDS.setTarget(cur, function(data) {
					elm_target.val(data);
				});
			}).on('command', '[id^=x\\\:custom_projects\\\:project_id-][data-method=view]', function(e) {
			
				if (e.target.matches('[type=button]')) {
					return;
				}

				COMMANDS.setOptions(this, {overlay: 'document', overlay_settings: {sizing: 'full'}});				
			});
		});	
			
		SCRIPTER.dynamic('.project-overview', function(elm_scripter) {
		
			let elm_overview = elm_scripter.children('div:last-child');
			let data_model_graph = null;
		
			elm_scripter.on('change', 'input[name=size_full]', function() {
				
				const elm_target = elm_scripter.closest('.overlay').parent();
				const obj_overlay = elm_target[0].overlay;
				
				if (this.checked) {
					obj_overlay.sizing('full-width');
				} else {
					obj_overlay.sizing('full');
				}
			}).on('change', 'input[name^=mode]', function() {
			
				const elm_command = this.closest('form');
				COMMANDS.setData(elm_command, serializeArrayByName(elm_command), true);
				
				COMMANDS.quickCommand(elm_command, function(data) {
					
					if (data_model_graph) {
					
						data_model_graph.close();
						data_model_graph = null;
					}
					
					const elm_overview_new = data;
					
					elm_overview_new.insertBefore(elm_overview);
					elm_overview.remove();
					
					elm_overview = elm_overview_new;
					
					data_model_graph = new ModelGraph(elm_overview);
				});
			}).on('click', 'button[name=download]', function() {
			
				const cur = $(this);
				const elm_resolution = cur.closest('form').find('[name=download_resolution]');
				const elm_svg = elm_overview.children('svg');
				
				const arr_settings_capture = {
					name: 'nodegoat_model',
					resolution: elm_resolution[0].value,
					width: (elm_svg[0].dataset.width / 100),
					height: (elm_svg[0].dataset.height / 100)
				};
				
				const obj_capture = new CaptureElementImage(elm_svg, arr_settings_capture);
				obj_capture.addLayers();
				obj_capture.setBackgroundColor('#ffffff');
				obj_capture.download();
			});
									
			data_model_graph = new ModelGraph(elm_overview);
		});
		
		SCRIPTER.static('.account', function(elm_scripter) {
			
			elm_scripter.on('click', '.custom_project_filters fieldset .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').sorter('addRow');
			}).on('click', '.custom_project_filters fieldset .del', function() {
				$(this).closest('li').next('li').find('.sorter').sorter('clean');
			}).on('change', '.custom_project_filters select', function() {
				var value = $(this).val();
				$(this).closest('ul').find('[id^=y\\\:custom_projects\\\:lookup_user_project_type_filter-]').data('value', {'type_id': value});
			});
			
			elm_scripter.find('.custom_project_filters select').trigger('change');
		});
		
		SCRIPTER.dynamic('[data-method=handle_scenario_storage]', function(elm_scripter) {
			
			elm_scripter.on('ajaxloaded scripter', function() {
				elm_scripter.find('[name$=_use_current]').trigger('change');
			}).on('change', '[name$=_use_current]', function() {
				$(this).prev('select').prop('disabled', $(this).is(':checked'));
			});
		});
		
		SCRIPTER.dynamic('form.storage', function(elm_scripter) {
		
			elm_scripter.on('ajaxloaded scripter', function() {
			
				elm_scripter.find('select[name=scenario_id]').trigger('change');
			}).on('change', 'select[name=scope_id], select[name=context_id], select[name=frame_id], select[name=visual_settings_id], select[name=condition_id], select[name=analysis_id], select[name=export_settings_id], select[name=scenario_id]', function() {
									
				var cur = $(this);
				var elm_option = cur.find('option:selected');
				var elm_target = cur.nextAll();
				
				if (elm_option.parent('optgroup').not('[label=Administrator]').length) {
					elm_target.addClass('hide');
				} else {
					elm_target.removeClass('hide');
				}
			}).on('change', 'select[name=scenario_id]', function() {
				
				var cur = $(this);
				var elm_target = cur.closest('ul').find('.clear_scenario_storage_cache').closest('fieldset').parent('li');
			
				if (cur.find('option:selected')[0].dataset.cache_retain) {
					elm_target.removeClass('hide');
				} else {
					elm_target.addClass('hide');
				}
			});
			
			elm_scripter.on('command', '[id^=x\\\:custom_projects\\\:frame_storage-] *[type=button], [id^=x\\\:custom_projects\\\:context_storage-] *[type=button], [id^=x\\\:custom_projects\\\:visual_settings_storage-] *[type=button], [id^=x\\\:custom_projects\\\:export_settings_storage-] *[type=button], [id^=x\\\:custom_projects\\\:condition_storage-] *[type=button], [id^=x\\\:custom_projects\\\:analysis_storage-] *[type=button], [id^=x\\\:custom_projects\\\:analysis_context_storage-] *[type=button]', function() {
		
				var cur = $(this);
				var elm_command = cur.parent();
				var elm_form = cur.closest('form');
				
				var elm_context = cur.closest('.overlay')[0].context;
				var elm_self = elm_context.closest('form');
				
				COMMANDS.setData(elm_command[0], {forms: [elm_self[0], elm_form[0]]});
				COMMANDS.setTarget(elm_command[0], cur.prevAll('select'));
				COMMANDS.setOptions(elm_command[0], {remove: false});
			}).on('command', '[id^=x\\\:custom_projects\\\:scope_storage-] *[type=button]', function() {
		
				var cur = $(this);
				var elm_command = cur.parent();
				var elm_form = cur.closest('form');
				
				var elm_context = cur.closest('.overlay')[0].context;
				var str_scope = elm_context.data('value').scope;

				COMMANDS.setData(elm_command[0], {forms: [elm_form[0]], scope: str_scope});
				COMMANDS.setTarget(elm_command[0], cur.prevAll('select'));
				COMMANDS.setOptions(elm_command[0], {remove: false});
			}).on('command', '[id^=x\\\:custom_projects\\\:scenario_storage-] *[type=button]', function() {
			
				var cur = $(this);
				var elm_command = cur.closest('[id^=x\\\:custom_projects\\\:scenario_storage-]');
				
				var elm_form = elm_command.closest('form');
				
				COMMANDS.setData(elm_command[0], {forms: [elm_form[0]]});
				COMMANDS.setTarget(elm_command[0], elm_command.find('select[name=scenario_id]'));
				COMMANDS.setOptions(elm_command[0], {remove: false});
			});
		});
		
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
		
		if ($method == "add") {
			
			$this->html = '<form id="f:custom_projects:insert-0">'.$this->createProject().'</form>';
		}
		if ($method == "edit") {
			
			$this->html = '<form id="f:custom_projects:update-'.$id.'">'.$this->createProject($id).'</form>';
		}
		if ($method == "view") {

			$this->html = $this->createProjectOverview($id);
		}
		if ($method == "get_overview_graph") {

			$arr_options = [
				'mode' => ($value['mode']['references'] ? CreateProjectOverviewGraph::MODE_REFERENCES : false)
			];

			$this->html = $this->createProjectOverviewGraph($id, $arr_options);
		}
		
		if ($method == "set_information") {
			
			if ($id) {
				
				$arr_id = explode('_', $id);
							
				$arr_type_set = StoreType::getTypeSet($arr_id[0]);
				
				$str_name = strEscapeHTML(Labels::parseTextVariables($arr_type_set['type']['name']));
				
				if ($arr_id[1]) {
					$str_name .= ' - '.strEscapeHTML(Labels::parseTextVariables($arr_type_set['object_descriptions'][$arr_id[1]]['object_description_name']));
				} else if ($arr_id[2]) {
					$str_name .= ' <span class="sub-name">'.strEscapeHTML(Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_id[2]]['object_sub_details']['object_sub_details_name'])).'</span>';
					if ($arr_id[3]) {
						$str_name .= ' '.strEscapeHTML(Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_id[2]]['object_sub_descriptions'][$arr_id[3]]['object_sub_description_name']));
					}
				}
			} else {
				
				$str_name = getLabel('lbl_new');
			}
			
			$this->html = '<form data-method="return_information" data-lock="1">
				
				<h1>'.getLabel('lbl_information').': '.$str_name.'</h1>
				
				<div class="options" >
					'.cms_general::editBody($value['information'], 'body').'
				</div>
			</form>';
		}
		if ($method == "return_information") {
			
			$this->html = $_POST['body'];
		}
		
		if ($method == "select") {
			
			$this->html = '<form class="options" data-method="set">'.self::createSelectProjectUser().'</form>';
		}
	
		if ($method == "data") {
			
			$arr_sql_columns = ['p.name'];
			$arr_sql_columns_as = ['p.name', DBFunctions::sqlImplode(DBFunctions::castAs('pt.type_id', DBFunctions::CAST_TYPE_STRING), DBFunctions::SQL_GROUP_SEPERATOR, 'ORDER BY pt.type_id DESC').' AS types', 'p.id'];

			$sql_table = DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECTS').' AS p';
									
			$sql_index = 'p.id';
			
			$sql_body = $sql_table."
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_CUSTOM_PROJECT_TYPES')." pt ON (pt.project_id = p.id)
			";
			
			$sql_where_default = '';
			
			$arr_datatable = cms_general::prepareDataTable($arr_sql_columns, false, $arr_sql_columns_as, $sql_table, $sql_index, $sql_body, '', $sql_where_default);
			
			$arr_types = StoreType::getTypes();
			
			while ($arr_row = $arr_datatable['result']->fetchAssoc())	{
				
				$arr_data = [];
				
				$arr_data['id'] = 'x:custom_projects:project_id-'.$arr_row['id'];
				$arr_data['attr']['class'] = 'popup';
				$arr_data['attr']['data-method'] = 'view';
				$arr_data[] = Labels::parseTextVariables($arr_row['name']);
				
				$arr_classes_types = [StoreType::TYPE_CLASS_TYPE => [], StoreType::TYPE_CLASS_CLASSIFICATION => [], StoreType::TYPE_CLASS_REVERSAL => []];
				
				foreach (array_filter(str2Array($arr_row['types'], DBFunctions::SQL_GROUP_SEPERATOR)) as $type_id) {
					
					$arr_classes_types[$arr_types[$type_id]['class']][$type_id] = $arr_types[$type_id]['name'];
				}
				
				$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_classes_types[StoreType::TYPE_CLASS_TYPE] ? strEscapeHTML(Labels::parseTextVariables(implode('<br />', $arr_classes_types[StoreType::TYPE_CLASS_TYPE]))) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)count($arr_classes_types[StoreType::TYPE_CLASS_TYPE]).'</span></span>';
				$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_classes_types[StoreType::TYPE_CLASS_CLASSIFICATION] ? strEscapeHTML(Labels::parseTextVariables(implode('<br />', $arr_classes_types[StoreType::TYPE_CLASS_CLASSIFICATION]))) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)count($arr_classes_types[StoreType::TYPE_CLASS_CLASSIFICATION]).'</span></span>';
				$arr_data[] = '<span class="info"><span class="icon" title="'.($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL] ? strEscapeHTML(Labels::parseTextVariables(implode('<br />', $arr_classes_types[StoreType::TYPE_CLASS_REVERSAL]))) : getLabel('inf_none')).'">'.getIcon('info').'</span><span>'.(int)count($arr_classes_types[StoreType::TYPE_CLASS_REVERSAL]).'</span></span>';
				$arr_data[] = '<input type="button" class="data edit" value="edit" /><input type="button" class="data del msg del" value="del" />';
				
				$arr_datatable['output']['data'][] = $arr_data;
			}
			
			$this->data = $arr_datatable['output'];
		}
		
		// Filter storage
		
		if ($method == 'add_filter_storage' || $method == 'handle_filter_storage' || $method == 'del_filter_storage') {
			
			$type_id = (int)$id;
			
			$arr_filter = json_decode($value['filter'], true);
			$arr_filter = $arr_filter['filter']; // Set correct key in form
			
			if ($arr_filter[$type_id]) { // Tabbed filtering with various types (e.g. Reversed Classification)
				$arr_filter = $arr_filter[$type_id];
			}
		}
		if ($method == 'add_filter_storage') {
						
			if (!$arr_filter['form'] && $arr_filter['versioning']) {
				error(getLabel('msg_filter_store_empty'));
			}
								
			$this->html = '<form data-method="handle_filter_storage">
				'.$this->createFilterStore($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $_POST['filter_id']).'
			</form>';
		}
		if ($method == 'handle_filter_storage' || $method == 'del_filter_storage') {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_INTERACT) {
				
				error(getLabel('msg_not_allowed'));
				return;
			}
			
			$type_id = $id;
		}
		if ($method == 'handle_filter_storage') {
			
			$filter = new FilterTypeObjects($type_id);
			$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => StoreCustomProject::getScopeTypes($_SESSION['custom_projects']['project_id']), 'project_id' => $_SESSION['custom_projects']['project_id']]);
			$arr_type_filter = $filter->cleanupFilterInput(($arr_filter ?: []));
			
			if (!$arr_type_filter) {
				
				error(getLabel('msg_filter_store_empty'));
			}
			
			$user_id = ($_POST['usage'] == 'personal' ? $_SESSION['USER_ID'] : false);
			$is_domain = (($_POST['usage'] == 'admin' && $_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN) ? true : false);

			$filter_id = cms_nodegoat_custom_projects::handleProjectTypeFilter($_SESSION['custom_projects']['project_id'], $user_id, $_POST['filter_id'], $type_id, $_POST, $arr_type_filter, $is_domain);
		}
		if ($method == 'del_filter_storage') {
			
			cms_nodegoat_custom_projects::delProjectTypeFilter($_SESSION['custom_projects']['project_id'], $_POST['filter_id'], ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false));
		}
		if ($method == 'handle_filter_storage' || $method == 'del_filter_storage') {
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$this->msg = true;
			$this->html = Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids), $filter_id, true, 'label'));
		}

		// Visual settings storage
		
		if ($method == 'add_visual_settings_storage') {
						
			$this->html = '<form data-method="handle_visual_settings_storage">
				'.$this->createVisualSettingsStore($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $_POST['visual_settings_id']).'
			</form>';
		}
		if ($method == 'handle_visual_settings_storage' || $method == 'del_visual_settings_storage') {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_INTERACT) {
				
				error(getLabel('msg_not_allowed'));
				return;
			}
		}
		if ($method == 'handle_visual_settings_storage') {
			
			$arr_visual_settings = $_POST['visual_settings'];

			$arr_visual_settings['settings']['geo_advanced'] = cms_nodegoat_custom_projects::parseVisualSettingsInputAdvanced($arr_visual_settings['settings']['geo_advanced']);
			$arr_visual_settings['social']['settings']['social_advanced'] = cms_nodegoat_custom_projects::parseVisualSettingsInputAdvanced($arr_visual_settings['social']['settings']['social_advanced']);
						
			$visual_settings_id = cms_nodegoat_custom_projects::handleProjectVisualSettings($_SESSION['custom_projects']['project_id'], ($_POST['usage'] == 'personal' ? $_SESSION['USER_ID'] : false), $_POST['visual_settings_id'], $_POST, $arr_visual_settings);
		}
		if ($method == 'del_visual_settings_storage') {
			
			cms_nodegoat_custom_projects::delProjectVisualSettings($_SESSION['custom_projects']['project_id'], $_POST['visual_settings_id']);
		}
		if ($method == 'handle_visual_settings_storage' || $method == 'del_visual_settings_storage') {
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$this->msg = true;
			$this->html = Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectVisualSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], false, $arr_use_project_ids), $visual_settings_id, true, 'label'));
		}
		
		// Scenario storage
		
		if ($method == 'add_scenario_storage') {
				
			$this->html = '<form data-method="handle_scenario_storage">
				'.$this->createScenarioStore($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $id, $_POST['scenario_id']).'
			</form>';
		}
		if ($method == 'handle_scenario_storage' || $method == 'del_scenario_storage' || $method == 'clear_scenario_storage_cache') {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_INTERACT) {
				
				error(getLabel('msg_not_allowed'));
				return;
			}
						
			$type_id = $id;
		}
		if ($method == 'handle_scenario_storage') {
			
			if (!$_POST['filter_id'] && !$_POST['filter_use_current'] && !$_POST['scope_id'] && !$_POST['condition_id'] && !$_POST['context_id'] && !$_POST['analysis_id'] && !$_POST['analysis_context_id'] && !$_POST['frame_id'] && !$_POST['visual_settings_id']) {
				error(getLabel('msg_scenario_store_empty'));
			}
						
			$arr_type_scenario = [
				'filter_id' => $_POST['filter_id'],
				'filter_use_current' => $_POST['filter_use_current'],
				'scope_id' => $_POST['scope_id'],
				'scope_use_current' => $_POST['scope_use_current'],
				'condition_id' => $_POST['condition_id'],
				'condition_use_current' => $_POST['condition_use_current'],
				'context_id' => $_POST['context_id'],
				'context_use_current' => $_POST['context_use_current'],
				'analysis_id' => $_POST['analysis_id'],
				'analysis_use_current' => $_POST['analysis_use_current'],
				'analysis_context_id' => $_POST['analysis_context_id'],
				'analysis_context_use_current' => $_POST['analysis_context_use_current'],
				'frame_id' => $_POST['frame_id'],
				'frame_use_current' => $_POST['frame_use_current'],
				'visual_settings_id' => $_POST['visual_settings_id'],
				'visual_settings_use_current' => $_POST['visual_settings_use_current'],
				'cache_retain' => $_POST['cache_retain']
			];
			
			$scenario_id = cms_nodegoat_custom_projects::handleProjectTypeScenario($_SESSION['custom_projects']['project_id'], ($_POST['usage'] == 'personal' ? $_SESSION['USER_ID'] : false), $_POST['scenario_id'], $type_id, $_POST, $arr_type_scenario);
		}
		if ($method == 'del_scenario_storage') {
			
			cms_nodegoat_custom_projects::delProjectTypeScenario($_SESSION['custom_projects']['project_id'], $_POST['scenario_id']);
		}
		if ($method == 'handle_scenario_storage' || $method == 'del_scenario_storage') {
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$arr_scenarios = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids);
			
			foreach ($arr_scenarios as $scenario_id => &$arr_scenario) {
				
				if ($arr_scenario['cache_retain']) {
					$arr_scenario['attr']['data-cache_retain'] = '1';
				}
			}
			unset($arr_scenario);
			
			$this->msg = true;
			$this->html = Labels::parseTextVariables(cms_general::createDropdown($arr_scenarios, $_POST['scenario_id'], true, 'label'));
		}
		
		if ($method == 'clear_scenario_storage_cache') {
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $_POST['scenario_id'], $arr_use_project_ids);
		
			if ($arr_scenario['cache_retain']) {
				
				StoreCustomProject::delTypeScenarioHash($arr_scenario['project_id'], $_POST['scenario_id'], $_SESSION['custom_projects']['project_id']);
				
				$this->msg = true;
			}
		}
		
		// Scope / Context / Frame / Condition / Analysis / Analysis Context storage
		
		if (
			$method == 'add_scope_storage' || $method == 'add_context_storage' || $method == 'add_frame_storage' || $method == 'add_condition_storage' || $method == 'add_analysis_storage' || $method == 'add_analysis_context_storage' || $method == 'add_export_settings_storage'
			|| $method == 'handle_scope_storage' || $method == 'handle_context_storage' || $method == 'handle_frame_storage' || $method == 'handle_condition_storage' || $method == 'handle_analysis_storage' || $method == 'handle_analysis_context_storage' || $method == 'handle_export_settings_storage'
			|| $method == 'del_scope_storage' || $method == 'del_context_storage' || $method == 'del_frame_storage' || $method == 'del_condition_storage' || $method == 'del_analysis_storage' || $method == 'del_analysis_context_storage' || $method == 'del_export_settings_storage'
		) {
			
			$arr_what = explode('_', $method);
			if (($arr_what[1] == 'export' && $arr_what[2] == 'settings') || ($arr_what[1] == 'analysis' && $arr_what[2] == 'context')) {
				$what = $arr_what[1].'_'.$arr_what[2];
				$what_function_name = ucfirst($arr_what[1]).ucfirst($arr_what[2]);
			} else {
				$what = $arr_what[1];
				$what_function_name = ucfirst($arr_what[1]);
			}
			
			$method_do = 'add';
			if ($arr_what[0] == 'handle') {
				$method_do = 'handle';
			} else if ($arr_what[0] == 'del') {
				$method_do = 'del';
			}
			
			$what_id = $_POST[$what.'_id'];
			
			if ($method_do == 'add' || $method_do == 'handle') {
				
				$arr_data = $_POST[$what];
				
				if ($what == 'scope') {
					
					$arr_data = json_decode($value['scope'], true);
					$arr_data = $arr_data['scope']; // Set correct key in form
				}
			}
		}
		if ($method_do == 'add') {

			if (!$arr_data) {
				error(getLabel('msg_'.$what.'_store_empty'));
			}
			
			$function = 'create'.$what_function_name.'Store';
				
			$this->html = '<form data-method="handle_'.$what.'_storage">
				'.$this->$function($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $what_id).'
			</form>';
		}
		if ($method_do == 'handle' || $method_do == 'del') {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_INTERACT) {
				
				error(getLabel('msg_not_allowed'));
				return;
			}
						
			$type_id = $id;
		}
		if ($method_do == 'handle') {
			
			$function = 'handleProjectType'.$what_function_name;
			$user_id = ($_POST['usage'] == 'personal' ? $_SESSION['USER_ID'] : false);
			
			if ($what == 'condition') {
				
				$arr_files = ($_FILES['condition'] ? arrRearrangeParams($_FILES['condition']) : []);
					
				$arr_condition = data_model::parseTypeCondition($type_id, $arr_data, $arr_files);
				$arr_model_conditions = data_model::parseTypeModelConditions($type_id, $_POST['model_conditions']);
				
				if (!$arr_condition && !$arr_model_conditions) {
					error(getLabel('msg_condition_store_empty'));
				}

				$is_domain = (($_POST['usage'] == 'admin' && $_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN) ? true : false);
				
				$what_id = cms_nodegoat_custom_projects::$function($_SESSION['custom_projects']['project_id'], $user_id, $what_id, $type_id, $_POST, $arr_condition, $arr_model_conditions, $is_domain);
			} else {

				if ($what == 'scope') {
				
					$arr_data = data_model::parseTypeNetwork($arr_data);
				} else if ($what == 'context') {
					
					$arr_data = data_model::parseTypeContext($type_id, $arr_data);
				} else if ($what == 'analysis') {

					$arr_data = data_analysis::parseTypeAnalysis($type_id, $arr_data);
				} else if ($what == 'analysis_context') {

					$arr_data = data_analysis::parseTypeAnalysisContext($type_id, $arr_data);
				} else if ($what == 'export_settings') {

					$arr_data = toolbar::parseTypeExportSettings($type_id, $arr_data);
				}
				
				$what_id = cms_nodegoat_custom_projects::$function($_SESSION['custom_projects']['project_id'], $user_id, $what_id, $type_id, $_POST, $arr_data);
			}
		}
		if ($method_do == 'del') {
			
			$function = 'delProjectType'.$what_function_name;
			
			if ($what == 'condition') {
				
				cms_nodegoat_custom_projects::$function($_SESSION['custom_projects']['project_id'], false, $what_id, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false));
			} else {
				
				cms_nodegoat_custom_projects::$function($_SESSION['custom_projects']['project_id'], $what_id);
			}
		}
		if ($method_do == 'handle' || $method_do == 'del') {
			
			$arr_project = StoreCustomProject::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$function = 'getProjectType'.$what_function_name.'s';
			if ($what == 'export_settings') {
				$function = 'getProjectTypeExportSettings';
			} else if ($what == 'analysis') {
				$function = 'getProjectTypeAnalyses';
			} else if ($what == 'analysis_context') {
				$function = 'getProjectTypeAnalysesContexts';
			}
			
			$this->msg = true;
			
			if ($what == 'condition') {
				
				$this->html = Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::$function($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, ($_SESSION['NODEGOAT_CLEARANCE'] == NODEGOAT_CLEARANCE_ADMIN ? true : false), $arr_use_project_ids), $what_id, true, 'label'));
			} else {
				
				$this->html = Labels::parseTextVariables(cms_general::createDropdown(cms_nodegoat_custom_projects::$function($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), $what_id, true, 'label'));
			}
		}
		
		// Other
		
		if ($method == "lookup_user_project_type_filter") {
			
			$project_id = (int)$id;
			$type_id = $value['type_id'];
			if (!$type_id) {
				return;
			}
			$value_search = $value['value_element'];
			$arr_project_type_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($project_id, $_SESSION['USER_ID'], $type_id);
			
			$arr = [];
			foreach ((array)$arr_project_type_filters as $key => $value) {
				
				if ($value_search && stripos($value['name'], $value_search) === false) {
					continue;
				}
				
				$arr[] = ['id' => $key, 'label' => $value['label'], 'value' => $value['label']];
			}
		
			$this->html = $arr;
		}
	
		// QUERY
		
		if (($method == "insert" || $method == "update") && $this->is_discard) {
			
			$this->html = self::createAddProject();
			return;
		}
				
		if ($method == "insert" || $method == "update") {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				
				error(getLabel('msg_not_allowed'));
			}

			if ($method == 'insert') {
				
				$custom_project = new StoreCustomProject(false);
				$project_id = $custom_project->store($_POST);
				
				$user_data = [
					DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS').'.project_id' => array_merge(array_keys($_SESSION['CUR_USER'][DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')]), [$project_id])
				];
				user_management::updateUserLinkedData($_SESSION['USER_ID'], $user_data);
			} else if ($method == 'update') {
				
				if (!$_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$id]) {
					error(getLabel('msg_not_allowed'));
				}
				
				$custom_project = new StoreCustomProject($id);
				$custom_project->store($_POST);
			}
						
			$this->html = self::createAddProject();						
			$this->refresh_table = true;
			$this->msg = true;
		}
		
		if ($method == "del" && (int)$id) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN || !$_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$id]) {
				error(getLabel('msg_not_allowed'));
			}
			
			$custom_project = new StoreCustomProject($id);
			$custom_project->delProject();
								
			$this->msg = true;
		}
		
		if ($method == "set") {
			
			$project_id = (int)($_POST['project_id'] ?: $id);
			
			if (!$_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$project_id]) { // Make sure the project itself also exists
				return;
			}
		
			self::setUserProjectID($project_id);
			
			Response::location(SiteStartVars::getPageURL());
		}
	}
	
	public static function getTypesByClass($arr_types) {
		
		$arr_classes_types = [StoreType::TYPE_CLASS_TYPE => [], StoreType::TYPE_CLASS_CLASSIFICATION => [], StoreType::TYPE_CLASS_REVERSAL => [], StoreType::TYPE_CLASS_SYSTEM => []];
		
		foreach ($arr_types as $type_id => $arr_type) {
			
			$arr_type['name'] = strEscapeHTML($arr_type['name']);
			
			$arr_classes_types[$arr_type['class']][$type_id] = $arr_type;
		}
		
		return $arr_classes_types;
	}
	
	public static function setUserProjectID($project_id) {
		
		if ($project_id == $_SESSION['custom_projects']['project_id'] || !$_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$project_id]) { // Make sure the project itself also exists
			return;
		}
				
		$_SESSION['custom_projects']['project_id'] = $project_id;
		
		$res = DB::queryMulti("
			UPDATE ".DB::getTable('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')." SET 
				is_active = FALSE
			WHERE user_id = ".(int)$_SESSION['USER_ID'].";
			
			UPDATE ".DB::getTable('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')." SET 
				is_active = TRUE
			WHERE project_id = ".(int)$project_id." AND user_id = ".(int)$_SESSION['USER_ID'].";
		");
		
		return true;
	}
	
	public static function getUserProjects() {
		
		$arr_projects = StoreCustomProject::getProjects();
		$arr_select_projects = [];
		
		foreach ($arr_projects as $project_id => $arr_project) {
					
			if (!$_SESSION['CUR_USER'][DB::getTableName('USER_LINK_NODEGOAT_CUSTOM_PROJECTS')][$project_id]) { // Make sure the user has access to the project 
				continue;
			}
			
			$arr_select_projects[$project_id] = $arr_project;
		}
		
		return $arr_select_projects;
	}
	
	public static function checkAccessTypeConfiguration($type, $arr_project_types, $arr_type_set, $object_description_id, $object_sub_details_id = false, $object_sub_description_id = false) {
		
		$has_access = StoreCustomProject::checkTypeConfigurationAccess($arr_project_types, $arr_type_set, $object_description_id, $object_sub_details_id, $object_sub_description_id, $type);
		
		return $has_access;
	}
	
	public static function checkAccessType($type, $type_id, $do_error = true) {
		
		if (!$_SESSION['custom_projects']['project_id']) {
			
			if ($do_error) {
				error(getLabel('msg_no_custom_project_active'));
			} else {
				return false;
			}
		}
				
		$has_access = StoreCustomProject::checkTypeAccess($_SESSION['custom_projects']['project_id'], $type_id, $type);
		
		if (!$has_access && $do_error) {
			error(getLabel('msg_not_allowed'));
		}
		
		return $has_access;
	}
}
