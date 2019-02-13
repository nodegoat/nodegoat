<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ui_selection extends base_module {
	
	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	protected $arr_access = [
		'ui' => [],
		'ui_data' => []
	];
		
	public static function createViewSelectionsContainer($print = false) {

		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');		
		$selection_id = SiteStartVars::getFeedback('active_selection_id');

		if ($selection_id) {
			
			$arr_external_selection = cms_nodegoat_public_interfaces::getPublicInterfaceSelection($selection_id);
		}
	
		$return = '<div class="selections-container list" 
									'.($arr_external_selection ? '
											data-new_external_selection_id="'.$arr_external_selection['id'].'" 
											data-new_external_selection_title="'.$arr_external_selection['selection_title'].'"
										' : '').' 
									'. ($selection_id && $print && !$arr_external_selection ? '
											data-print_selection_id="'.$selection_id.'"
										' : '').' 
									data-url="'.SiteStartVars::getBasePath(0, false).SiteStartVars::$page['name'].'.p/'.$public_user_interface_id.'/'.$public_user_interface_active_custom_project_id.'"></div>';
		
		SiteEndVars::setFeedback('active_type_object_id', false, true);
		
		return $return;
	}
	
	public static function createViewPrintSelection($selection_id, $arr_selection = false) {
	
		$create_data = new ui_data();
		$arr_public_user_interface = SiteStartVars::getFeedback('arr_public_user_interface');
		
		if (!$arr_selection) {
			
			$arr_selection = cms_nodegoat_public_interfaces::getPublicInterfaceSelection($selection_id);
		}

		if (!$arr_selection) {
			return false;
		}

		$arr_elements = $arr_selection['elements'];
		usort($arr_elements, function($a, $b) { return $a['sort'] > $b['sort']; }); 

		$return = '<h1 class="selection-title">'.$arr_selection['selection_title'].'</h1>
			<h3 class="selection-editor">'.$arr_selection['selection_editor'].'</h3>
			<p class="selection-notes">'.$arr_selection['selection_notes'].'</p>';
					
		foreach ($arr_elements as $arr_elm) {
			
			if ($arr_elm['elm_type'] == 'object') {
				
				$arr_id = explode('_', $arr_elm['elm_id']);
				$return .= str_replace("&", "&amp;", $create_data->createViewTypeObject($arr_id[0], $arr_id[1], true)).'<p>'.$arr_elm['elm_notes'].'</p>'; 
			
			} else if ($arr_elm['elm_type'] == 'heading') {
				
				$return .= '<h2>'.$arr_elm['elm_heading'].'</h2><p>'.$arr_elm['elm_notes'].'</p>';
			}
		}	
		
		return $return;	
	}
	
	public static function createViewPdfSelection($selection_id, $arr_selection = false) {
		
		$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
		$arr_public_interface_settings = cms_nodegoat_public_interfaces::getPublicInterfaceSettings($public_user_interface_id);
		$arr_public_user_interface = SiteStartVars::getFeedback('arr_public_user_interface');
		
		if (!$arr_selection) {
			
			$arr_selection = cms_nodegoat_public_interfaces::getPublicInterfaceSelection($selection_id);
		}

		if (!$arr_selection) {
			return false;
		}

		$arr_elements = $arr_selection['elements'];
		usort($arr_elements, function($a, $b) { return $a['sort'] > $b['sort']; }); 
		
		$create_data = new ui_data();
		$arr_elms = [];
		
		$arr_elms[] = ['elm_type' => 'pui_title', 'content' => Labels::parseTextVariables($arr_public_user_interface['interface']['name']), 'style' => 'heading'];
		$arr_elms[] = ['elm_type' => 'selection_title', 'content' => $arr_selection['selection_title'], 'style' => 'title'];
		$arr_elms[] = ['elm_type' => 'selection_editor', 'content' => $arr_selection['selection_editor'], 'style' => 'heading', 'pagebreak' => 'after'];
		$arr_elms[] = ['elm_type' => 'selection_note', 'content' => $arr_selection['selection_notes'], 'style' => 'note'];

		foreach ((array)$arr_elements as $arr_selection_elm) {
			
			if ($arr_selection_elm['elm_type'] == 'heading') {
				
				if ($arr_selection_elm['elm_heading']) {
					$arr_elms[] = ['elm_type' => 'heading', 'content' => $arr_selection_elm['elm_heading'], 'style' => 'heading', 'pagebreak' => 'before'];
					$arr_elms[] = ['elm_type' => 'note', 'content' => ($arr_selection_elm['elm_notes'] ? $arr_selection_elm['elm_notes'] : ' '), 'style' => 'note', 'pagebreak' => 'after'];	
				}
							
			} else if ($arr_selection_elm['elm_type'] == 'object') {
				
				$arr_id = explode("_", $arr_selection_elm['elm_id']);
				$type_id = $arr_id[0];
				$object_id = $arr_id[1];

				$arr_type_set = StoreType::getTypeSet($type_id);
				
				$arr_pdf_object = $create_data->createViewTypeObject($arr_id[0], $arr_id[1], 'pdf');
			
				$arr_article = ['title' => ['elm_type' => 'heading', 'content' => strip_tags($arr_pdf_object['name']), 'style' => 'heading', 'pagebreak' => ($arr_elms[count($arr_elms) - 1]['pagebreak'] == 'after' ? false : 'before')], 'section_1' => [], 'section_2' => []];
				
				// in media type, allow image to take up complete width of page, otherwise show note in sideline (section 2)
				if (!in_array($type_id, $arr_public_interface_settings['types']['media_types']) && $arr_selection_elm['elm_notes']) {
					
					$arr_article['section_2'][] = ['elm_type' => 'note', 'content' => $arr_selection_elm['elm_notes'], 'style' => 'note'];	
				}
				
				foreach ((array)$arr_pdf_object['images_full'] as $arr_image) {		
					
					if ($arr_image['url']) {
					
						$image_url = DIR_ROOT_STORAGE.DIR_HOME.$arr_image['url'];	
						$data = base64_encode(file_get_contents($image_url));
						$string = 'data:'.mime_content_type($image_url).';base64,'.$data;
						$arr_article['section_1'][] = ['elm_type' => 'image_full', 'content' => $string];
						
					}
				}
			
				foreach ((array)$arr_pdf_object['types'] as $object_description_id => $arr_object_descriptions) {
						
					$elm = $arr_type_set['object_descriptions'][$object_description_id]['object_description_name'].': ';
					
					$elm .= Response::addParseDelay('', function($foo) use ($arr_object_descriptions) {
					
						foreach ($arr_object_descriptions as $key => $arr_object_description) {
							$arr_object_descriptions[$key] = GenerateTypeObjects::printSharedTypeObjectNames($arr_object_description);
						}
						
						usort($arr_object_descriptions, function($a, $b) { return strcmp($a, $b); });
						
						$return = false;
						
						foreach ($arr_object_descriptions as $key => $arr_object_description) {
							
							$return .= ($return ? ', ' : '') . trim($arr_object_description);
						}				
						
						return $return;
					});	
					
					$arr_article['section_1'][] = ['elm_type' => 'object_value', 'content' => $elm, 'style' => 'text'];			
				}
				
				foreach ((array)$arr_pdf_object['values'] as $object_description_id => $arr_object_descriptions) {
					
					$elm = '';
					
					foreach ($arr_object_descriptions as $arr_object_description) {
						
						$elm .= $arr_object_description.' ';
					}
					
					$arr_article['section_1'][] = ['elm_type' => 'object_value', 'content' => $elm, 'style' => 'text'];			
				}
				
				if (!in_array($type_id, $arr_public_interface_settings['types']['media_types'])) {
				
					foreach ((array)$arr_pdf_object['images'] as $arr_image) {
						
						if ($arr_image['cache_url']) {
			
							$image_url = SiteStartVars::getBasePath(0, false).$arr_image['cache_url'];
							$data = base64_encode(file_get_contents($image_url));
							$string = 'data:image/png;base64,'.$data;
							$arr_article['section_2'][] = ['elm_type' => 'image', 'content' => $string];			
							$arr_article['section_2'][] = ['elm_type' => 'object_value', 'content' => $arr_image['caption'], 'style' => 'caption'];			
						}
					}
				}
				
				if (in_array($type_id, $arr_public_interface_settings['types']['media_types']) && $arr_selection_elm['elm_notes']) {
					
					$arr_article['section_1'][] = ['elm_type' => 'note', 'content' => $arr_selection_elm['elm_notes'], 'style' => 'note'];	
				}
								
				foreach ((array)$arr_pdf_object['sources'] as $arr_source_object) {
					
					$arr_article['section_1'][] = ['elm_type' => 'object_source', 'content' => $arr_source_object, 'style' => 'source'];			
				}
				
				$arr_elms[] = ['elm_type' => 'article', 'content' => $arr_article];
			}
		}
		
		return $arr_elms;
		
	}
	
	public static function css() {
		
		$return = '';
	
		return $return;
	}
	
	public static function js() {
	
		$return = "";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT 
		
		if ($method == "get_selection_data") {

			if (is_array($value)) {
				
				$arr_elements = $value;
				
			} else {
				
				$external = true;
				$external_selection_id = $value;
				$arr_external_selection = cms_nodegoat_public_interfaces::getPublicInterfaceSelection($external_selection_id);
				
				if (!$arr_external_selection) {
					return false;
				}
				
				$arr_elements = $arr_external_selection['elements'];
				
			}

			foreach ($arr_elements as $elm_id => $arr_elm) {

				if ($arr_elm['elm_type'] == 'heading') {
					continue;
				}

				$arr_id = explode('_', $elm_id);
			
				if ($external) {
			
					$arr_external_selection['elements'][$elm_id]['elm_thumbnail'] = current(ui_data::getPublicInterfaceObjects($arr_id[0], ['objects' => $arr_id[1]], true, 1));
					
				} else {
								
					$arr_object_elements[$elm_id] = current(ui_data::getPublicInterfaceObjects($arr_id[0], ['objects' => $arr_id[1]], true, 1));
				}
			}
	
			$this->html = ($external ? $arr_external_selection : $arr_object_elements);
		}
		
		if ($method == "store_selection") {

			$arr_selection = $value;
			

			if (!$arr_selection || !is_array($arr_selection) || !$arr_selection['selection_title']) {
				return false;
			}
			
			if ($arr_selection['url_id'] && !count($arr_selection['elements'])) {
				
				// external selection
				$selection_url_id = $arr_selection['url_id'];
			}
			
			if (count($arr_selection['elements'])) {
				
				$selection_url_id = cms_nodegoat_public_interfaces::storePublicInterfaceSelection($arr_selection);
			}

			$public_user_interface_id = SiteStartVars::getFeedback('public_user_interface_id');
			$public_user_interface_active_custom_project_id = SiteStartVars::getFeedback('public_user_interface_active_custom_project_id');
			$arr_public_user_interface = SiteStartVars::getFeedback('arr_public_user_interface');
			$public_interface_name = Labels::parseTextVariables($arr_public_user_interface['interface']['name']);
			
			$arr_shares = cms_nodegoat_public_interfaces::getPublicInterfaceShareOptions($public_interface_name.' - '.$arr_selection['selection_title']);
			
			$url = SiteStartVars::getBasePath(0, false).SiteStartVars::$page['name'].'.p/'.$public_user_interface_id.'/'.$public_user_interface_active_custom_project_id.'/selection/'.$selection_url_id;
	
			$this->html = ['id' => $selection_url_id, 'url' => $url, 'arr_shares' => $arr_shares];
		}
		
		if ($method == "remove_selection") {

			cms_nodegoat_public_interfaces::delPublicInterfaceSelection($id);	
		}
		
		if ($method == "print_selection") {

			$arr_selection = $value;
			
			$return = self::createViewPrintSelection($arr_selection['id'], $arr_selection);
			$return .= '<script>window.print();</script>';
			
			$this->html = $return;
		}
		
		if ($method == "get_bookify_selection_data") {

			$selection_id = $value['selection_id'];
			$arr_selection = $value['arr_selection'];

			$arr_data = self::createViewPdfSelection($selection_id, $arr_selection);

			$this->html = $arr_data;
		}	
		
	}
	
}
