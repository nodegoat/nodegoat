<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class CreateProjectOverviewGraph {
	
	const MODE_DEFAULT = 1;
	const MODE_REFERENCES = 2;

	
	protected $user_id = false;
	protected $project_id = false;
	protected $arr_project = [];
	protected $mode = self::MODE_DEFAULT;
	protected $str_info = '';
	
	protected $arr_type_sets = [];
	
	protected $arr_type_boxes = [];
	protected $arr_element_links = [];
	protected $arr_type_box_positions = [];
	protected $arr_element_connect_positions = [];
	
	protected static $num_scene_margin = 20;
	protected static $num_box_margin = 100;
	protected static $num_header_height = 40;
	protected static $num_footer_height = 50;
	protected static $num_box_width = 240;
	protected static $num_full_width_minimum = 600;
		
	public function __construct($user_id, $project_id) {
		
		
		$this->user_id = $user_id;
		$this->arr_project = StoreCustomProject::getProjects($project_id);
		$this->project_id = $this->arr_project['project']['id'];
	
		$this->arr_type_sets = [];
		
		foreach ($this->arr_project['types'] as $type_id => $arr_project_type) {
			
			$arr_type_set = StoreCustomProject::getTypeSetReferenced($type_id, $arr_project_type, StoreCustomProject::ACCESS_PURPOSE_VIEW);
			
			if ($arr_type_set['type']['class'] == StoreType::TYPE_CLASS_REVERSAL || $arr_type_set['type']['class'] == StoreType::TYPE_CLASS_SYSTEM) {
				continue;
			}
			
			
			
			$this->arr_type_sets[$type_id] = $arr_type_set;
		}
	}
	
	public function setMode($mode) {
		
		$this->mode = ($mode ?: static::MODE_DEFAULT);
	}
	public function setInfo($str) {
		
		$this->str_info = $str;
	}
	
	protected function prepareModel() {
		
		$arr_value_types = StoreType::getValueTypesBase();
		
		$arr_ref_type_ids = StoreCustomProject::getScopeTypes($this->project_id);
		$arr_use_project_ids = array_keys($this->arr_project['use_projects']);
				
		foreach ($this->arr_type_sets as $type_id => $arr_type_set) {
			
			$arr_project_type = $this->arr_project['types'][$type_id];
			
			$this->arr_type_boxes[$type_id]['header'] = [
				'name' => Labels::parseTextVariables($arr_type_set['type']['name']),
				'class' => '',
				'color' => ($arr_project_type['color'] ?: $arr_type_set['type']['color'])
			];
			
			$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_ID);
			$filter->setScope(['users' => $this->user_id, 'types' => $arr_ref_type_ids, 'project_id' => $this->project_id]);
			if ($arr_project_type['type_filter_id']) {
							
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, false, $arr_project_type['type_filter_id'], true, $arr_use_project_ids);
				$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']));
			}
			$arr_info = $filter->getResultInfo();
			
			$this->arr_type_boxes[$type_id]['info'] = [
				'filter' => (bool)$arr_project_type['type_filter_id'],
				'condition' => (bool)$arr_project_type['type_condition_id'],
				'lock' => ($arr_project_type['type_edit'] ? false : true),
				'objects' => $arr_info['total_filtered'],
				'name' => ($arr_type_set['type']['class'] == StoreType::TYPE_CLASS_CLASSIFICATION ? getLabel('lbl_categories') : getLabel('lbl_objects'))
			];
			
			$this->arr_type_boxes[$type_id]['elements'] = [];
			
			foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
				if (!StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, $object_description_id, false, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
			
				$str_identifier = 'object_description_'.$object_description_id;
				
				$connect_type_id = $arr_object_description['object_description_ref_type_id'];
				
				if ($this->mode == static::MODE_REFERENCES && !$connect_type_id) {
					continue;
				}
				
				$is_referenced = $arr_object_description['object_description_is_referenced'];
				$str_value_type = $arr_value_types[$arr_object_description['object_description_value_type_base']]['name_base'];
				$has_multi = $arr_object_description['object_description_has_multi'];
				
				if ($is_referenced) {
					$connect_type_id = false;
					$str_value_type = '';
					$has_multi = false;
				}
				
				$this->arr_type_boxes[$type_id]['elements'][$str_identifier] = [
					'name' => Labels::parseTextVariables($arr_object_description['object_description_name']),
					'class' => 'object-description',
					'value_type' => $str_value_type,
					'multi' => $has_multi
				];
				
				if ($connect_type_id) {
					
					$this->arr_element_links[$str_identifier] = (isset($this->arr_type_sets[$connect_type_id]) ? $connect_type_id : true);
				}
			}
			
			foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
				if (!StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
				
				$str_identifier = 'object_sub_details_'.$object_sub_details_id;
				
				$is_referenced = $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_type_id']; // Cross-referenced sub-object
				
				$this->arr_type_boxes[$type_id]['elements'][$str_identifier] = [
					'name' => Labels::parseTextVariables($arr_object_sub_details['object_sub_details']['object_sub_details_name']),
					'class' => 'object-sub-details',
					'value_type' => false,
					'multi' => false
				];
				
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					if (!StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
						continue;
					}
				
					$str_identifier = 'object_sub_details_'.$object_sub_details_id.'_object_sub_description_'.$object_sub_description_id;
					
					$connect_type_id = $arr_object_sub_description['object_sub_description_ref_type_id'];
				
					if ($this->mode == static::MODE_REFERENCES && !$connect_type_id) {
						continue;
					}
					
					$str_value_type = $arr_value_types[$arr_object_sub_description['object_sub_description_value_type_base']]['name_base'];
					
					if ($is_referenced) {
						$connect_type_id = false;
						$str_value_type = '';
					}
					
					$this->arr_type_boxes[$type_id]['elements'][$str_identifier] = [
						'name' => Labels::parseTextVariables($arr_object_sub_description['object_sub_description_name']),
						'class' => 'object-sub-description',
						'value_type' => $str_value_type,
						'multi' => false
					];
					
					if ($connect_type_id) {
						
						$this->arr_element_links[$str_identifier] = (isset($this->arr_type_sets[$connect_type_id]) ? $connect_type_id : true);
					}
				}
			}
		}
	}
	    
	public function generate() {
		
		$this->prepareModel();
		
		$num_columns = 5;
		
		$num_header_text_size = (static::$num_header_height - static::$num_scene_margin);
		$num_footer_text_size = (static::$num_footer_height - (static::$num_scene_margin*2));
		$num_footer_logo_size = (static::$num_footer_height / 2);
		$num_footer_logo_margin = ($num_footer_logo_size/2);
		
		$num_box_header_height = 35;
		$num_box_header_text_size = 15;
		$num_box_header_text_margin_x = 7.5;
		$num_box_header_text_margin_y = (($num_box_header_height-$num_box_header_text_size)/2);
		$num_box_header_connect_size = 10;
		$num_box_info_height = 22;
		$num_box_info_text_size = 10;
		$num_box_info_text_margin_x = 7.5;
		$num_box_info_text_margin_y = (($num_box_info_height-$num_box_info_text_size)/2);
		$num_box_info_icon_size = 10;
		$num_box_info_icon_margin_x = 7.5;
		$num_box_info_icon_margin_y = (($num_box_info_height-$num_box_info_icon_size)/2);
		$num_box_element_height = 22;
		$num_box_element_text_size = 10;
		$num_box_element_text_margin_x = 7.5;
		$num_box_element_text_margin_y = (($num_box_element_height-$num_box_element_text_size)/2);
		$num_box_element_value_text_size = 8;
		$num_box_element_value_text_margin_y = (($num_box_element_height-$num_box_element_value_text_size)/2);
		$num_box_element_value_text_width = 40;
		$num_box_element_connect_size = 6;
						
		$svg_style = '
		<style>
			@import url(\'https://fonts.googleapis.com/css?family=Roboto+Mono:400,400i,700,700i\');
			text {
				font-family: \'Roboto Mono\';
				fill: #000000;
				fill-opacity: 1;
				font-size: 20px;
				dominant-baseline: hanging;
				text-anchor: start;
				shape-rendering: geometricPrecision;
			}
			path, circle {
				shape-rendering: geometricPrecision;
			}
			
			g.header > text {
				font-size: '.$num_header_text_size.'px;
			}
			g.footer > text {
				font-size: '.$num_footer_text_size.'px;
				fill: #000000;
			}
			g.footer > path.back {
				fill: #f5f5f5;
				shape-rendering: crispEdges;
			}
			g.footer > path.logo {
				fill: #a3ce6c;
				shape-rendering: crispEdges;
			}
						
			g.box > path {
				fill: none;
				stroke: #e1e1e1;
				stroke-width: 1.5px;
			}
			
			g.box-header > path {
				fill: #f5f5f5;
				shape-rendering: crispEdges;
			}
			g.box-header > text.name {
				font-size: '.$num_box_header_text_size.'px;
				fill: #000000;
			}

			g.box-info > path {
				fill: #fafafa;
				shape-rendering: crispEdges;
			}
			g.box-info > text.objects {
				font-size: '.$num_box_info_text_size.'px;
				fill: #828282;
			}
			g.box-info > use {
				fill: #828282;
			}
			
			g.box-element > path {
				fill: #ffffff;
				shape-rendering: crispEdges;
			}
			g.box-element.object-sub-details > path {
				fill: #fafafa;
				shape-rendering: crispEdges;
			}
			g.box-element > text.name {
				font-size: '.$num_box_element_text_size.'px;
				fill: #000000;
			}
			g.box-element > text.value {
				font-size: '.$num_box_element_value_text_size.'px;
				fill: #828282;
				text-anchor: end;
			}
			
			g.graph > .connect-boxes > circle {
				fill: #009cff;
			}
			g.graph > .connect-elements > circle {
				fill: #009cff;
			}
			g.graph > .connect-lines > path {
				fill: none;
				stroke: #009cff;
				stroke-width: '.$num_box_element_connect_size.'px;
				stroke-opacity: 0.1;
			}
			
			g.graph > .connect-boxes > circle.highlight {
				
			}
			g.graph.highlighting > .connect-lines > path {
				stroke-opacity: 0.05;
			}
			g.graph > .connect-lines > path.highlight {
				stroke-opacity: 0.4;
			}
		</style>
		
		<defs>
			<path id="box-header-text" d="'
				.'M'.(($num_box_header_text_margin_x*2) + $num_box_header_connect_size).','.$num_box_header_text_margin_y
				.' L'.(static::$num_box_width - $num_box_header_text_margin_x).','.$num_box_header_text_margin_y
			.'" />
			<path id="box-info-objects-text" d="'
				.'M'.$num_box_info_text_margin_x.','.$num_box_info_text_margin_y
				.' L'.(static::$num_box_width - $num_box_info_text_margin_x).','.$num_box_info_text_margin_y
			.'" />
			<path id="box-element-text" d="'
				.'M'.$num_box_element_text_margin_x.','.$num_box_element_text_margin_y
				.' L'.(static::$num_box_width - ($num_box_element_text_margin_x*2)-$num_box_element_value_text_width).','.$num_box_element_text_margin_y
			.'" />
			<path id="box-element-value-text" d="'
				.'M'.(static::$num_box_width - $num_box_element_text_margin_x-$num_box_element_value_text_width).','.$num_box_element_value_text_margin_y
				.' L'.(static::$num_box_width - $num_box_element_text_margin_x).','.$num_box_element_value_text_margin_y
			.'" />
			<svg id="icon-filter" width="'.$num_box_info_icon_size.'" height="'.$num_box_info_icon_size.'">'.getIcon('filter').'</svg>
			<svg id="icon-condition" width="'.$num_box_info_icon_size.'" height="'.$num_box_info_icon_size.'">'.getIcon('marker').'</svg>
			<svg id="icon-lock" width="'.$num_box_info_icon_size.'" height="'.$num_box_info_icon_size.'">'.getIcon('locked').'</svg>
		</defs>
		';
		
		// Draw header
				
		$svg_header = '<g class="header">
			<text x="'.static::$num_scene_margin.'" y="'.static::$num_scene_margin.'">'.Labels::parseTextVariables($this->arr_project['project']['name']).'</text>
		</g>';
		
		// Draw boxes
		
		$arr_svg_boxes = [];
		$num_box_x = 0;
		$num_box_y = 0;
		$num_boxes_width = 0;
		$num_boxes_height = 0;
		$num_box_height_max = 0;
				
		foreach ($this->arr_type_boxes as $type_id => $arr_box) {
			
			$num_box_element_x = 0;
			$num_box_element_y = 0;
			$num_box_height = 0;
			
			$svg_box_header = '<g class="box-header" transform="translate('.$num_box_element_x.', '.$num_box_element_y.')">
				<path d="'
					.'M0,0'
					.' L'.static::$num_box_width.',0'
					.' L'.static::$num_box_width.','.$num_box_header_height
					.' L0,'.$num_box_header_height
					.' L0,0'
				.'" />
				<text class="name"><textPath startOffset="0%" href="#box-header-text">'.$arr_box['header']['name'].'</textPath></text>
			</g>';
			
			$num_box_element_y += $num_box_header_height;
			
			$svg_icons = '';
			$num_icons_x = (static::$num_box_width - $num_box_info_icon_margin_x - $num_box_info_icon_size);
			
			if ($arr_box['info']['filter']) {
				
				$svg_icons .= '<use href="#icon-filter" x="'.$num_icons_x.'" y="'.$num_box_info_icon_margin_y.'" />';
				
				$num_icons_x -= ($num_box_info_icon_margin_x + $num_box_info_icon_size);
			}
			if ($arr_box['info']['condition']) {
				
				$svg_icons .= '<use href="#icon-condition" x="'.$num_icons_x.'" y="'.$num_box_info_icon_margin_y.'" />';
				
				$num_icons_x -= ($num_box_info_icon_margin_x + $num_box_info_icon_size);
			}
			if ($arr_box['info']['lock']) {
				
				$svg_icons .= '<use href="#icon-lock" x="'.$num_icons_x.'" y="'.$num_box_info_icon_margin_y.'" />';
				
				$num_icons_x -= ($num_box_info_icon_margin_x + $num_box_info_icon_size);
			}
			
			$svg_box_info = '<g class="box-info" transform="translate('.$num_box_element_x.', '.$num_box_element_y.')">
				<path d="'
					.'M0,0'
					.' L'.static::$num_box_width.',0'
					.' L'.static::$num_box_width.','.$num_box_info_height
					.' L0,'.$num_box_info_height
					.' L0,0'
				.'" />
				<text class="objects"><textPath startOffset="0%" href="#box-info-objects-text">'.$arr_box['info']['name'].': '.num2String($arr_box['info']['objects']).'</textPath></text>
				'.$svg_icons.'
			</g>';
			
			$num_box_element_y += $num_box_info_height;
			
			$arr_svg_box_elements = [];
			
			foreach ($this->arr_type_boxes[$type_id]['elements'] as $str_identifier => $arr_element) {
				
				$connect_type_id = $this->arr_element_links[$str_identifier];
				
				$svg_text = '';
				
				switch ($arr_element['class']) {
					
					case 'object-sub-details':
						$svg_text = '<text class="name"><textPath startOffset="0%" href="#box-element-text">['.$arr_element['name'].']</textPath></text>';
						break;
					case 'object-sub-description':
						$svg_text = '<text class="name" dx="'.($num_box_element_text_margin_x*2).'"><textPath startOffset="0%" href="#box-element-text">'.$arr_element['name'].'</textPath></text>';
						break;
					default:
						$svg_text = '<text class="name"><textPath startOffset="0%" href="#box-element-text">'.$arr_element['name'].'</textPath></text>';
				}
				
				$svg_value = '';
				
				if (!$connect_type_id) {
					$svg_value = '<text class="value"><textPath startOffset="100%" href="#box-element-value-text">'.$arr_element['value_type'].'</textPath></text>';
				}
				
				$svg_element = '<g class="box-element'.($arr_element['class'] ? ' '.$arr_element['class'] : '').'" transform="translate('.$num_box_element_x.', '.$num_box_element_y.')">
					<path d="'
						.'M0,0'
					.' L'.static::$num_box_width.',0'
					.' L'.static::$num_box_width.','.$num_box_element_height
					.' L0,'.$num_box_element_height
					.' L0,0'
					.'" />
					'.$svg_text.'
					'.$svg_value.'
				</g>';

				$arr_svg_box_elements[] = $svg_element;
				
				if ($connect_type_id) {
					
					$num_connect_x = (static::$num_box_width-($num_box_element_connect_size/2)-$num_box_element_text_margin_x);
					$num_connect_y = ($num_box_element_height/2);
	
					$svg_connect = '<circle data-identifier="'.$str_identifier.'" transform="translate('.$num_box_x.', '.$num_box_y.')" cx="'.($num_box_element_x + $num_connect_x).'" cy="'.($num_box_element_y + $num_connect_y).'" r="'.($num_box_element_connect_size/2).'"></circle>';
					
					$this->arr_element_connect_positions[$str_identifier] = [
						'type_id' => $type_id,
						'connect_type_id' => $connect_type_id,
						'x' => ($num_box_element_x + $num_connect_x),
						'y' => ($num_box_element_y + $num_connect_y),
						'svg' => $svg_connect
					];
				}
				
				$num_box_element_y += $num_box_element_height;
			}
			
			$num_box_height = $num_box_element_y;	
			
			$svg_box = '<g class="box" data-identifier="'.$type_id.'" transform="translate('.$num_box_x.', '.$num_box_y.')">
				'.$svg_box_header.'
				'.$svg_box_info.'
				'.implode('', $arr_svg_box_elements).'
				<path d="'
					.'M0,0'
					//.' L'.static::$num_box_width.',0'
					//.' L'.static::$num_box_width.','.$num_box_height
					.' L0,'.$num_box_height
					//.' L0,0'
				.'" />
			</g>';
			
			$arr_svg_boxes[] = $svg_box;
			
			$num_connect_x = ($num_box_header_text_margin_x + ($num_box_header_connect_size/2));
			$num_connect_y = ($num_box_header_height/2);
			
			$svg_connect = '<circle data-identifier="'.$type_id.'" transform="translate('.$num_box_x.', '.$num_box_y.')" cx="'.$num_connect_x.'" cy="'.$num_connect_y.'" r="'.($num_box_header_connect_size/2).'" style="fill: '.$arr_box['header']['color'].';" />';
			
			$this->arr_type_box_positions[$type_id] = [
				'x' => $num_box_x,
				'y' => $num_box_y,
				'width' => static::$num_box_width,
				'height' => $num_box_height,
				'connect_x' => $num_connect_x,
				'connect_y' => $num_connect_y,
				'connect_svg' => $svg_connect
			];
			
			$num_box_x += static::$num_box_width;
					
			if ($num_box_height > $num_box_height_max) {
				$num_box_height_max = $num_box_height;
			}
			
			if (count($arr_svg_boxes) == count($this->arr_type_boxes)) {
				
				if ($num_box_x > $num_boxes_width) {
					$num_boxes_width = $num_box_x;
				}
				
				$num_box_y += $num_box_height_max;
			} else if ((count($arr_svg_boxes) % $num_columns) == 0) {
				
				if ($num_box_x > $num_boxes_width) {
					$num_boxes_width = $num_box_x;
				}
				$num_box_x = 0;
				
				$num_box_y += ($num_box_height_max + static::$num_box_margin);
				$num_box_height_max = 0;
			} else {
				
				$num_box_x += static::$num_box_margin;
			}
		}
		
		$num_boxes_height = $num_box_y;
		
		// Draw connections
		
		$svg_type_box_connections = '';
		$svg_element_connections = '';
		$svg_connections = '';
		
		foreach ($this->arr_element_links as $str_identifier => $type_id) {
			
			$arr_element_connect_position = $this->arr_element_connect_positions[$str_identifier];
			
			$svg_element_connections .= $arr_element_connect_position['svg'];
			
			if ($type_id === true) { // Type not available in Project
				continue;
			}
			
			$arr_type_box_position = $this->arr_type_box_positions[$arr_element_connect_position['type_id']];
			$arr_type_box_connect_position = $this->arr_type_box_positions[$arr_element_connect_position['connect_type_id']];
			
			$svg_connections .= '<path data-identifier="'.$str_identifier.'-'.$type_id.'" d="'
				.'M'.($arr_type_box_position['x'] + $arr_element_connect_position['x']).','.($arr_type_box_position['y'] + $arr_element_connect_position['y'])
				.' L'.($arr_type_box_connect_position['x'] + $arr_type_box_connect_position['connect_x']).','.($arr_type_box_connect_position['y'] + $arr_type_box_connect_position['connect_y'])
			.'" />';
		}
		
		foreach ($this->arr_type_box_positions as $type_id => $arr_type_box_position) {
			
			$svg_type_box_connections .= $arr_type_box_position['connect_svg'];
		}
		
		// Determine full width
		
		$num_full_width = (($num_boxes_width > static::$num_full_width_minimum ? $num_boxes_width : static::$num_full_width_minimum) + (static::$num_scene_margin*2));
		
		// Draw footer
				
		$svg_footer = '<g class="footer" transform="translate(0, '.(static::$num_header_height + static::$num_scene_margin + $num_boxes_height + (static::$num_scene_margin*2)).')">
			<path class="back" d="'
				.'M0,0'
				.' L'.$num_full_width.',0'
				.' L'.$num_full_width.','.static::$num_footer_height
				.' L0,'.static::$num_footer_height
				.' L0,0'
			.'" />
			<path class="logo" d="'
				.'M0,0'
				.' L'.($num_footer_logo_margin + $num_footer_logo_size + $num_footer_logo_margin).',0'
				.' L'.($num_footer_logo_margin + $num_footer_logo_size + $num_footer_logo_margin).','.static::$num_footer_height
				.' L0,'.static::$num_footer_height
				.' L0,0'
			.'" />
			<image x="'.$num_footer_logo_margin.'" y="'.((static::$num_footer_height - $num_footer_logo_size) / 2).'" width="'.$num_footer_logo_size.'" height="'.$num_footer_logo_size.'" href="/'.DIR_CSS.'images/nodegoat.svg" />
			<text x="'.($num_footer_logo_margin + $num_footer_logo_size + ($num_footer_logo_margin*2)).'" y="'.((static::$num_footer_height - $num_footer_text_size) / 2).'">'.Labels::parseTextVariables($this->str_info).'</text>
		</g>';
		
		// Draw all
		
		$num_full_height = (static::$num_header_height + static::$num_scene_margin + $num_boxes_height + (static::$num_scene_margin*2) + static::$num_footer_height);
		
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$num_full_width.' '.$num_full_height.'" data-width="'.$num_full_width.'" data-height="'.$num_full_height.'">
			'.$svg_style.'
			'.$svg_header.'
			<g class="graph" transform="translate('.static::$num_scene_margin.', '.(static::$num_header_height + static::$num_scene_margin).')">'
				.'<g class="boxes">'.implode('', $arr_svg_boxes).'</g>'
				.'<g class="connect-lines">'.$svg_connections.'</g>'
				.'<g class="connect-boxes">'.$svg_type_box_connections.'</g>'
				.'<g class="connect-elements">'.$svg_element_connections.'</g>'
			.'</g>
			'.$svg_footer.'
		</svg>';
		
		return $svg;
	}
	
	public function getPositions() {
		
		return [
			'boxes' => $this->arr_type_box_positions,
			'elements' => $this->arr_element_connect_positions,
			'margin' => [
				'scene' => static::$num_scene_margin,
				'box' => static::$num_box_margin
			],
			'size' => [
				'header' => static::$num_header_height,
				'box' => static::$num_box_width,
				'footer' => static::$num_footer_height
			],
			'width' => ['min' => static::$num_full_width_minimum]
		];
	}
}
