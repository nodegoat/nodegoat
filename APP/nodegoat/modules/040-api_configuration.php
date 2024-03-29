<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class api_configuration extends api {

	public static function moduleProperties() {
		parent::moduleProperties();
		static::$label = getLabel('lbl_api');
	}
	
	protected function contentsFormConfiguration() {
		
		$arr_projects = StoreCustomProject::getProjects();
		$arr_api_configuration = cms_nodegoat_api::getConfiguration($this->arr_api['id']);
						
		$return = '<div class="tabs">
			<ul>
				<li><a href="#">'.getLabel('lbl_general').'</a></li>
				<li><a href="#">'.getLabel('lbl_projects').'</a></li>
			</ul>
			
			<div>
				<div class="fieldsets options"><div>
					
					<fieldset><legend>'.getLabel('lbl_projects').'</legend><ul>
						<li>'.Labels::parseTextVariables(cms_general::createSelectorList(arrParseRecursive(arrValuesRecursive('project', $arr_projects), 'strEscapeHTML'), 'projects', array_keys((array)$arr_api_configuration['projects']))).'</li>
					</ul></fieldset>
					
				</div></div>
			</div>
			
			<div>
				<div class="options">
					
					<section class="info attention">'.getLabel('inf_save_and_edit_section').'</section>';
					
					if ($arr_api_configuration['projects']) {
						
						Labels::setVariable('example', '://DOMAIN.nodegoat.net/viewer.p/PUI-ID/Project-ID/object/<strong>[multi=_]</strong><strong>[[type]]</strong>-<strong>[[object]]</strong><strong>[/multi]</strong>');
								
						$arr_html_tabs = [];
						
						foreach ($arr_api_configuration['projects'] as $project_id => $arr_api_project) {
							
							$arr_project = $arr_projects[$project_id];
							
							$arr_html_tabs['links'][] = '<li><a href="#">'.strEscapeHTML(Labels::parseTextVariables($arr_project['project']['name'])).'</a></li>';
							
							$return_tab = '<div>
								<div class="fieldsets options"><div>
								
									<fieldset><legend>'.getLabel('lbl_access').'</legend><ul>
										<li>
											<label>'.getLabel('lbl_default').'</label>
											<div><input type="radio" name="default_project" value="'.$project_id.'"'.($arr_api_project['is_default'] ? ' checked="checked"' : '').' /></div>
										</li>
										<li>
											<label>'.getLabel('lbl_authentication').'</label>
											<div><input type="checkbox" name="projects_organise['.$project_id.'][require_authentication]" value="1"'.($arr_api_project['require_authentication'] ? ' checked="checked"' : '').' /></div>
										</li>
										<li>
											<label></label>
											<section class="info attention body">'.parseBody(getLabel('inf_api_nodegoat_identifier_url', 'L', true)).'</section>
										</li>
										<li>
											<label>'.getLabel('lbl_api_nodegoat_identifier_url').'</label>
											<div><input type="text" name="projects_organise['.$project_id.'][identifier_url]" value="'.($arr_api_project['identifier_url']).'" /></div>
										</li>
									</ul></fieldset>
																		
								</div></div>
							</div>';
							
							$arr_html_tabs['content'][] = $return_tab;
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

		return $return;
	}
	
	public static function css() {
	
		$return = parent::css();
		
		$return .= '
			.api_configuration fieldset > ul > li > label:first-child + section { margin: 0px; width: 600px; }
			.api_configuration fieldset > ul > li > label:first-child + div input[name*=identifier_url] { width: 400px; }
		';
		
		return $return;
	}
	
	public static function js() {
	
		$return = parent::js();
	
		$return .= "";
		
		return $return;
	}
		
	protected function processFormConfiguration() {

		$arr_data = $_POST;
				
		cms_nodegoat_api::handleAPIConfiguration($this->arr_api['id'], $arr_data);
				
		return $arr_data;
	}
}
