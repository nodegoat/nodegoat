<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class publish_instances extends base_module {

	public static function moduleProperties() {
		static::$label = 'Publish Instances';
		static::$parent_label = 'nodegoat';
	}
	
	public function contents() {
					
		$return = '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_publish').'</a></li>
				<li><a href="#">'.getLabel('lbl_repositories').'</a></li>
			</ul>
			
			<div>
				'.$this->createPublishProjects().'
			</div>
			
			<div>
				'.$this->createPublishRepositories().'
			</div>
		</div>';
				
		return $return;
	}
	
	protected function createPublishProjects() {
		
		$arr_projects = StoreCustomProject::getProjects();
		$arr_instance_projects = StorePublishInstances::getInstanceProjects();
						
		$return = '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_general').'</a></li>
				<li><a href="#">'.getLabel('lbl_projects').'</a></li>
			</ul>
			
			<div>
				<div class="fieldsets options"><div>
					
					<fieldset><legend>'.getLabel('lbl_projects').'</legend><ul>
						<li>'.Labels::parseTextVariables(cms_general::createSelectorList(arrParseRecursive(arrValuesRecursive('project', $arr_projects), 'strEscapeHTML'), 'projects', array_keys((array)$arr_instance_projects['projects']))).'</li>
					</ul></fieldset>
					
				</div></div>
			</div>
			
			<div>
				<div class="options">
					
					<section class="info attention">'.getLabel('inf_save_and_edit_section').'</section>';
					
					if ($arr_instance_projects['projects']) {
														
						$arr_html_tabs = [];
						
						$arr_public_interfaces = static::getConnectPublicInterfaces();
						
						$arr_link = publish::findMainPublish();
						$str_url_base = SiteStartEnvironment::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']);
						
						foreach ($arr_instance_projects['projects'] as $project_id => $arr_instance_project) {
							
							$arr_project = $arr_projects[$project_id];
							
							$arr_html_tabs['links'][] = '<li><a href="#">'.strEscapeHTML(Labels::parseTextVariables($arr_project['project']['name'])).'</a></li>';
							
							$str_name = 'projects_organise['.$project_id.']';
							
							$str_html_tab = '<div>
								<div class="fieldsets options"><div>
								
									<fieldset><legend>'.getLabel('lbl_settings').'</legend><ul>
										<li>
											<label>'.getLabel('lbl_default').'</label>
											<div><input type="radio" name="default_project" value="'.$project_id.'"'.($arr_instance_project['is_default'] ? ' checked="checked"' : '').' /></div>
										</li>
										<li>
											<label>'.getLabel('lbl_description').'</label>
											<div>'.cms_general::editBody($arr_instance_project['description'], $str_name.'[description]', ['inline' => true]).'</div>
										</li>
										<li>
											<label>'.getLabel('lbl_public_interface').'</label>
											<div><select name="'.$str_name.'[public_interface_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_public_interfaces, $arr_instance_project['public_interface_id'], true)).'</select></div>
										</li>
									</ul></fieldset>';

									$arr_action = [['id' => '', 'name' => getLabel('lbl_none')], ['id' => cms_nodegoat_publish::PUBLICATION_CREATE, 'name' => getLabel('lbl_new'), 'title' => getLabel('inf_publish_instance_action_create')]];
									
									$str_html_archive_live = getLabel('lbl_not_available');

									if ($arr_instance_project['date']) {
										
										$str_filename = StorePublishInstances::getProjectArchives($arr_instance_project, $arr_instance_project['date']);
										
										if (isPath(DIR_HOME_PUBLISH_CUSTOM_PROJECT.$str_filename)) {

											$str_date = date('YmdHis', strtotime($arr_instance_project['date']));
											$str_url = $str_url_base.$project_id.'/'.$str_date;
											
											$str_html_archive_live = '<a href="'.$str_url.'" target="_blank">'.$str_date.'</a>';
										}
									}
									
									$str_html_archive = ($this->createPublishProjectArchiveSelector($arr_instance_project) ?: getLabel('lbl_not_available'));
									
									$str_html_tab .= '<fieldset><legend>'.getLabel('lbl_publication').'</legend><ul>
										<li>
											<label>'.getLabel('lbl_action').'</label>
											<div>'.cms_general::createSelectorRadioList($arr_action, $str_name.'[action]').'</div>
										</li>
										<li>
											<label>'.getLabel('lbl_date').'</label>
											<div><time>'.($arr_instance_project['date'] ? date('d-m-Y H:i', strtotime($arr_instance_project['date'])) : getLabel('lbl_not_published')).'</time></div>
										</li>
										<li>
											<label>'.getLabel('lbl_live').'</label>
											<div>'.$str_html_archive_live.'</div>
										</li>
										<li>
											<label>'.getLabel('lbl_archive').'</label>
											<div id="x:publish_instances:archive-'.$project_id.'">'.$str_html_archive.'</div>
										</li>
									</ul></fieldset>
																		
								</div></div>
							</div>';

							$arr_html_tabs['content'][] = $str_html_tab;
						}
						
						if ($arr_html_tabs['links']) {
								
							$return .= '<div class="tabs">
								<ul>
									'.implode('', $arr_html_tabs['links']).'
								</ul>
								'.implode('', $arr_html_tabs['content']).'
							</div>';
						}
					}
			
				$return .= '</div>
			</div>
			
		</div>';
		
		$return = '<form id="f:publish_instances:configure_projects-0">
			'.$return.'
			<menu class="options">
				<input type="submit" value="'.getLabel('lbl_save').'" />
			</menu>
		</form>';

		return $return;
	}
	
	protected function createPublishProjectArchiveSelector($arr_instance_project) {

		$arr_files = StorePublishInstances::getProjectArchives($arr_instance_project);
				
		$str_html = '';
		
		if ($arr_files) {
			
			rsort($arr_files);
			$arr_archives = [];
			
			$str_date = ($arr_instance_project['date'] ? date('YmdHis', strtotime($arr_instance_project['date'])) : false);
			
			foreach ($arr_files as $str_filename) {
				
				$str_filename = basename($str_filename);
				$str_name = $str_filename;
				
				if ($str_date && strpos($str_filename, $str_date) !== false) {
					$str_name .= ' ('.getLabel('lbl_live').')';
				}
				
				$arr_archives[] = ['id' => $str_filename, 'name' => $str_name];
			}
				
			$str_html = '<select>'.Labels::parseTextVariables(cms_general::createDropdown($arr_archives, false, true)).'</select><button type="button" class="quick open_archive" title="'.getLabel('inf_new_window').'" value="open"><span class="icon">'.getIcon('linked').'</span></button><button type="button" class="quick download_archive" title="'.getLabel('lbl_download').'" value="download"><span class="icon">'.getIcon('download').'</span></button><input type="button" class="data del msg del_archive" value="del" />';
		}
		
		return $str_html;
	}
	
	protected function createPublishRepositories() {
		
		$return = '<div class="options">
			<section class="info attention">'.getLabel('msg_not_available').'</section>
		</div>';

		return $return;
	}
	
	public static function css() {
			
		$return = '
			.publish_instances fieldset > ul > li > label:first-child + div > textarea.body-content,
			.publish_instances fieldset > ul > li > label:first-child + div > .editor-content > .body-content { height: 250px; }
			.publish_instances fieldset > ul > li > label:first-child + div[id*="publish_instances:archive-"] > select:has(> option:checked[value=""]) ~ * { display: none; }
		';
		
		return $return;
	}
	
	public static function js() {
		
		$return = "SCRIPTER.static('.publish_instances', function(elm_scripter) {
			
			elm_scripter.on('command', '[id^=x\\\:publish_instances\\\:archive-]', function() {
			
				var cur = $(this);
				var elm_target = cur.children('select');
				
				COMMANDS.setData(this, {archive: elm_target.val()});
				COMMANDS.setTarget(this, cur);
				COMMANDS.setOptions(this, {remove: false});
			});
		});";
		
		return $return;
	}
	
	public function commands($method, $id, $value = '') {
		
		if ($method == 'configure_projects' && $this->is_confirm !== false) {
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN) {
				error(getLabel('msg_not_allowed'));
			}
			
			$arr_data = $_POST;
			
			foreach ($arr_data['projects'] as $project_id) {
				
				if (!$_SESSION['CUR_USER'][DB::getTableName('DEF_NODEGOAT_CUSTOM_PROJECTS')][$project_id]) {
					error(getLabel('msg_not_allowed'));
				}
			}
			
			$arr_action = [];
			
			if ($arr_data['projects_organise']) {
				
				foreach ($arr_data['projects_organise'] as $project_id => $arr_organise) {
					
					if (!$arr_organise['action']) {
						continue;
					}
										
					if ($arr_organise['action'] == cms_nodegoat_publish::PUBLICATION_CREATE) {
						$arr_action[$project_id] = $project_id;
					}
				}
				
				if ($arr_action && !$this->is_confirm) {
				
					foreach ($arr_action as $key => $project_id) {
						
						$arr_project = StoreCustomProject::getProjects($project_id);
						$arr_action[$key] = $arr_project['project']['name'];
					}
					
					$this->do_confirm = true;
					Labels::setVariable('projects', '<li>'.arr2String($arr_action, '</li><li>').'</li>');
					$this->html = getLabel('conf_publish_instance_create');
					return;
				}
			}

			$store_publish = new StorePublishInstances();
			$store_publish->storeProjects($arr_data);
				
			if ($arr_action) {
				
				$arr_instance_projects = StorePublishInstances::getInstanceProjects();
				
				foreach ($arr_action as $project_id) {
					
					$arr_instance_project = $arr_instance_projects['projects'][$project_id];
					
					if (!$arr_instance_project) {
						error(getLabel('msg_missing_information'));
					}

					$arr_nodegoat_details = cms_nodegoat_details::getDetails();
					if ($arr_nodegoat_details['processing_time']) {
						timeLimit($arr_nodegoat_details['processing_time'] * 4);
					}
					if ($arr_nodegoat_details['processing_memory']) {
						memoryBoost($arr_nodegoat_details['processing_memory']);
					}
					
					$store_publish->publishProject($arr_instance_project);
				}
			}
					
			$this->html = $this->createPublishProjects();
			$this->msg = true;
		}
		
		if ($method == 'open_archive') {
			
			$str_filename = $value['archive'];
			$project_id = $id;
			
			if (!$str_filename) {
				return;
			}
			
			$str_filename = basename($str_filename);
			preg_match(StorePublishInstances::FILENAME_PARSE_ARCHIVE, $str_filename, $arr_match);
			
			$arr_link = publish::findMainPublish();
			$str_url_base = SiteStartEnvironment::getModuleURL($arr_link['id'], $arr_link['page_name'], $arr_link['sub_dir']);
			$str_date = $arr_match[2];
			
			$str_url = $str_url_base.$project_id.'/'.$str_date;
			
			Response::location(['open' => $str_url]);
		}
			
		if ($method == 'download_archive' || $method == 'del_archive') {
			
			$str_filename = $value['archive'];
			$project_id = $id;
			
			if (!$str_filename) {
				error(getLabel('msg_missing_information'));
			}
			$str_filename = basename($str_filename);
			
			if ($_SESSION['NODEGOAT_CLEARANCE'] < NODEGOAT_CLEARANCE_ADMIN && $method == 'del_archive') {
				error(getLabel('msg_not_allowed'));
			}

			$arr_instance_projects = StorePublishInstances::getInstanceProjects();
			$arr_instance_project = $arr_instance_projects['projects'][$project_id];
			
			if (!$arr_instance_project) {
				error(getLabel('msg_not_allowed'));
			}
			
			preg_match(StorePublishInstances::FILENAME_PARSE_ARCHIVE, $str_filename, $arr_match);
			$str_date = $arr_match[2];
			$arr_instance_project['date'] = $str_date;
		
			if ($method == 'download_archive') {
				
				if ($this->is_download) {

					$store_publish = new StorePublishInstances();
					
					$store_publish->readProject($arr_instance_project, 'publication-'.$project_id.'-'.$str_date.'.zip');
					exit;
				} else {
					 
					$this->do_download = true;
				}
			}
			
			if ($method == 'del_archive') {
				
				$store_publish = new StorePublishInstances();
				
				$store_publish->delProjectPaths($arr_instance_project, $str_date);
				
				$this->html = $this->createPublishProjectArchiveSelector($arr_instance_projects['projects'][$project_id]);
				$this->msg = true;
			}
		}
	}
	
	public static function getConnectPublicInterfaces() {
		
		$arr = [];
		
		$arr_public_interfaces = cms_nodegoat_public_interfaces::getPublicInterfaces();
		
		foreach ($arr_public_interfaces as $public_interface_id => $arr_public_interface) {
			
			$arr[$public_interface_id] = $arr_public_interface['interface'];
		}
		
		return $arr;
	}
}
