<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class PublishInstanceProject {
	
	const PLACEHOLDER_URL_FILE = '[[url_placeholder]]';
	
	protected $arr_instance = [];
	protected $arr_project = [];
	protected $project_id = false;
	
	
	protected $str_title = '';
	protected $str_svg_model = '';
	protected $arr_html_model = [];
	
	protected $archive = null;
	protected $str_path = null;
	protected $is_contained = true;
	protected $do_data = false;
	
	protected static $num_objects_stream = 5000;
	
	public function __construct($arr_instance) {
		
		$this->arr_instance = $arr_instance;
		
		$this->project_id = (int)$this->arr_instance['project_id'];
		
		if (!$this->project_id) {
			error(getLabel('msg_missing_information'));
		}
		
		$this->arr_project = StoreCustomProject::getProjects($this->project_id);
	}
	
	public function setTarget($str_path = false, $do_read_only = false) {
					
		$this->archive = new FileArchive($str_path, ($do_read_only ? null : true));
		
		$this->str_path = $this->archive->get();
    }
	
	public function store() {
		
		if (!$this->archive) {
			$this->setTarget();
		}
				
		Response::holdFormat(true);
		
		Response::setFormat(Response::OUTPUT_XML | Response::RENDER_HTML);
		$str_html = Response::parse($this->getDocument());
		
		Response::setFormat(Response::OUTPUT_JSON | Response::RENDER_HTML);
		$str_1100cc = Response::parse($this->getDocument1100CC());
		
		Response::holdFormat();
		
		$this->archive->add(['publication.html' => $str_html]);
		$this->archive->add(['1100CC.json' => $str_1100cc]);
		
		return $this->get();
	}
	
	public function get() {
		
		return $this->str_path;
	}
	
	public function readFile($str_path_internal, $do_read = true) {
		
		$str_path_entry = $this->archive->getEntry($str_path_internal);
		
		try {
			
			if ($do_read) {
				return read($str_path_entry);
			}
			
			$file = fopen($str_path_entry, 'r');
		} catch (Exception $e) {
			
			error(getLabel('lbl_not_found'));
		}
			
		return $file;
	}
	
	public function readFileAsArchive($str_path_internal, $do_read = true) {
		
		$str_path_entry = $this->archive->getEntryAsArchive($str_path_internal);
		
		if (!$str_path_entry) {
			error(getLabel('lbl_not_found'));
		}
		
		if ($do_read) {
			return read($str_path_entry);
		}
		
		$file = fopen($str_path_entry, 'r');
		
		return $file;
	}
	
	public function readDocument() {
				
		$str = $this->readFile('publication.html');
		
		return $str;
	}
	
	public function readDocument1100CC($arr_settings = []) {
				
		$arr = $this->readFile('1100CC.json');
		$arr = JSON2Value($arr);
		
		if (isset($arr_settings['url_file'])) {
			$arr['body'] = str_replace(static::PLACEHOLDER_URL_FILE, $arr_settings['url_file'], $arr['body']);
		}
		
		return $arr;
	}

	public function generate($do_data = false) {
		
		$this->do_data = $do_data;
		
		$this->str_title = Labels::parseTextVariables($this->arr_project['project']['name']);
		
		Labels::setVariable('project', $this->arr_project['project']['name']);
		status(getLabel('msg_publish_instance_generating'), false, getLabel('msg_wait'), ['identifier' => SiteStartEnvironment::getSessionId(true).'publish_instance', 'duration' => 1000, 'persist' => true]);
		
		$graph = new CreateProjectOverviewGraph(false, $this->project_id);
		$svg = $graph->generate(['header' => false, 'footer' => false]);
		
		$this->str_svg_model = $svg;
		
		$arr_types = ($this->arr_project['types'] ? StoreType::getTypes(array_keys($this->arr_project['types'])) : []);
		
		$this->arr_html_model = [];
		
		$arr_type_boxes = $graph->getTypeBoxes();
		
		foreach ($arr_type_boxes as $type_id => $arr_box) {
			
			$arr_html_descriptions = ['descriptions' => '', 'subs' => []];
			
			foreach ($arr_box['elements'] as $str_identifier => $arr_element) {
				
				$arr_connect = $graph->getTypeBoxElementLinks($str_identifier);
				$str_html_connect = '';

				if ($arr_connect) {
					if (!$arr_connect['type_id']) {
						$str_html_connect = '<span class="connect">'.$arr_connect['name'].'</span>';
					} else {
						$str_html_connect = '<a class="connect" href="#model-type-'.$arr_connect['type_id'].'">'.$arr_connect['name'].'</a>';
					}
				}

				if ($arr_element['class'] == 'object-sub-details') {
					
					$str_html_header = '<h4><span class="sub-name">'.$arr_element['name'].'</span>'.($arr_element['multi'] ? '<span class="value-multi">'.getLabel('lbl_multiple_abbr').'</span>' : '').'</h4>';
					
					if ($arr_element['information']) {
						$str_html_header .= '<aside>'.parseBody($arr_element['information']).'</aside>';
					}
					
					$arr_html_descriptions['subs'][$str_identifier]['header'] = $str_html_header;
					
					$str_html_sub = '<thead>
							<tr><td></td><th>'.getLabel('lbl_value_type').'</th></tr>
						</thead>';
						
					if ($arr_element['date'] || $arr_element['location']) {
						
						$str_html_sub .= '<tbody>';
						
						if ($arr_element['date']) {
							$str_html_sub .= '<tr><th scope="rowgroup">'.getLabel('lbl_date').'</th><td>'.$arr_element['date'].'</td></tr>';
						}
						if ($arr_element['location']) {
							$str_html_sub .= '<tr><th scope="rowgroup">'.getLabel('lbl_location').'</th><td>'.$arr_element['location'].'</td></tr>';
						}
						
						$str_html_sub .= '</tbody>';
					}
						
					$arr_html_descriptions['subs'][$str_identifier]['details'] = $str_html_sub;
				} else {
					
					$str_html_description = '<tr class="'.$arr_element['class'].'"><td>'.$arr_element['name'].'</td>';
					$str_html_value_type = '<span>'.$arr_element['value_type'].'</span>'.($arr_element['multi'] ? '<span class="value-multi">'.getLabel('lbl_multiple_abbr').'</span>' : '');

					if ($str_html_connect) {
						$str_html_description .= '<td>'.$str_html_value_type.'</td><td>'.$str_html_connect.'</td>';
					} else {
						$str_html_description .= '<td colspan="2">'.$str_html_value_type.'</td>';
					}
					
					$str_html_description .= '</tr>';
					
					if ($arr_element['information']) {
						$str_html_description .= '<tr class="information"><td></td><td colspan="2"><aside>'.parseBody($arr_element['information']).'</aside></td></tr>';
					}
					
					if ($arr_element['class'] == 'object-sub-description') {
					
						$str_identifier_sub = explode(CreateProjectOverviewGraph::ELEMENT_SEPARATOR_IDENTIFIER, $str_identifier);
						$str_identifier_sub = $str_identifier_sub[0];

						$arr_html_descriptions['subs'][$str_identifier_sub]['descriptions'] .= $str_html_description;
					} else {

						$arr_html_descriptions['descriptions'] .= $str_html_description;
					}
				}
			}
			
			$str_html_type = '<h3>'.$arr_box['header']['name'].'</h3>';
			
			if ($arr_box['header']['information']) {
				$str_html_type .= '<aside>'.parseBody($arr_box['header']['information']).'</aside>';
			}
			
			$str_html_type .= '<table>
				<thead>
					<tr><th>'.getLabel('lbl_description').'</th><th>'.getLabel('lbl_value_type').'</th><td></td></tr>
				</thead>
				<tbody>
					'.$arr_html_descriptions['descriptions'];
				
					foreach ($arr_html_descriptions['subs'] as $str_identifier_sub => $arr_html_sub) {
						
						$str_html_type .= '<tr class="object-sub-details"><td colspan="3">
							'.$arr_html_sub['header'].'
							<table>
								'.$arr_html_sub['details'];
							
								if ($arr_html_sub['descriptions']) {
									
									$str_html_type .= '<tbody>
										<tr><th scope="colgroup">'.getLabel('lbl_description').'</th><td></td><td></td></tr>
										'.$arr_html_sub['descriptions'].'
									</tbody>';
								}
							
							$str_html_type .= '</table>
						</td></tr>';
					}
				
				$str_html_type .= '</tbody>
			</table>';
			
			$str_url = $this->generateTypeData($type_id);
			$str_url = static::PLACEHOLDER_URL_FILE.$str_url;
			
			$str_html_type .= '<menu>'
				.'<a href="'.$str_url.'.json" target="_blank">JSON</a><a href="'.$str_url.'/" target="_blank">CSV</a>'
			.'</menu>';
			
			$this->arr_html_model[$arr_box['header']['class']] .= '<section id="model-type-'.$type_id.'">'
				.$str_html_type
			.'</section>';
		}
		
		clearStatus(SiteStartEnvironment::getSessionId(true).'publish_instance');
	}
		
	protected function generateTypeData($type_id) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$str_filename = $this->generateFileName($arr_type_set['type']['name']).'_'.$type_id;
				
		$str_path_base = 'data/'.$str_filename;
		
		if (!$this->do_data) {
			return $str_path_base;
		}

		if (!$this->archive) {
			$this->setTarget();
		}
		
		$this->getTypeObjectsJSON($type_id, $str_path_base);
				
		$this->getTypeObjectsCSV($type_id, $str_path_base);
		
		return $str_path_base;
	}
	
	public function generateFileName($str) {
		
		return str2Label(Labels::parseTextVariables($str, true));
	}
	
	public function setContained($is_contained) {
	
		$this->is_contained = $is_contained; // Rendered document is self-containing
	}
	
	public function getTitle() {
		
		return $this->str_title;
	}
	
	public function getBody() {

		$str_svg_logo = FileStore::getDataURL(DIR_ROOT_SITE.DIR_CSS.'images/nodegoat.svg');
		$str_date = date('Y-m-d H:i:s', (!is_integer($this->arr_instance['date']) ? strtotime($this->arr_instance['date']) : $this->arr_instance['date']));
		
		
		$str_info = '<span>'.getLabel('lbl_instance').': '.SERVER_NAME.'</span><span>'.getLabel('lbl_published').': '.$str_date.'</span>';
		

		$str_html = '<header>'
			.'<div><img src="'.$str_svg_logo.'" /></div>'
			.'<h1>'.$this->getTitle().'</h1>'
			.'<address>'.$str_info.'</address>'
			.'<h3>'.getLabel('lbl_description').'</h3>'
			.'<div>'.parseBody($this->arr_instance['description']).'</div>'
		.'</header>';
				
		$str_html .= '<section id="model-graph">'
			.'<h2>'.getLabel('lbl_model').'</h2>'
			.'<figure>'.$this->str_svg_model.'</figure>'
		.'</section>';
		
		if ($this->arr_html_model['type']) {
			
			$str_html .= '<section id="model-types">'
				.'<h2>'.getLabel('lbl_model').': '.getLabel('lbl_object').' '.getLabel('lbl_types').'</h2>'
				.$this->arr_html_model['type']
			.'</section>';
		}
		
		if ($this->arr_html_model['classification']) {
			
			$str_html .= '<section id="model-classifications">'
				.'<h2>'.getLabel('lbl_model').': '.getLabel('lbl_classifications').'</h2>'
				.$this->arr_html_model['classification']
			.'</section>';
		}
		
		if ($this->arr_html_model['reversal']) {
			
			$str_html .= '<section id="model-reversals">'
				.'<h2>'.getLabel('lbl_model').': '.getLabel('lbl_reversals').'</h2>'
				.$this->arr_html_model['reversal']
			.'</section>';
		}
		
		if ($this->is_contained) {
			$str_html = str_replace(static::PLACEHOLDER_URL_FILE, '', $str_html);
		}
		
		return '<article id="publish">'.$str_html.'</article>';
	}
	
	public function getStyle() {
		
		$str_style = '';
		
		if ($this->is_contained) {
			
			$str_style .= '
				@import url(\'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Roboto+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:ital,wght@0,400;0,700;1,400;1,700\');
				html { font-size: 62.5%; }
				body { margin: 0px; padding: 0px; }
			';
		}
		
		$str_style .= '
			#publish {
				--margin-default: 25px;
				--padding-table: 5px;
				--padding-table-cell: 50px;
				--font-body: Manrope, Helvetica,Arial,sans-serif;
				--font-mono: \'Roboto Mono\', Consolas,Menlo,Monaco,\'Lucida Console\',\'Liberation Mono\',\'DejaVu Sans Mono\',\'Bitstream Vera Sans Mono\',\'Courier New\',monospace,sans-serif;
				
				--text: #000000;
				--text-light: #444444;
				
				--back: #f5f5f5;
				--back-detail: #e5e5e5;
				--back-nested: #e1e1e1;
				--back-super: #444444;
				
				--highlight: #009cff;
				--highlight-soft: #008dcf;
				
				--nodegoat-green: #a3ce6c;
				--nodegoat-blue: #009cff;
				--nodegoat-dark: #131617;
			}
			#publish {
				font-size: 1.4rem;
				line-height: 1.26;
				font-family: var(--font-mono);
				color: var(--text);
			}

			#publish h1 { margin: 0.8em 0px; font-size: 2.4em; font-weight: bold; }
			#publish h2 { margin: 0.8em 0px; font-size: 2em; font-weight: bold; }
			#publish h3 { margin: 0.8em 0px; font-size: 1.8em; font-weight: bold; }
			#publish h4 { margin: 0.8em 0px; font-size: 1.2em; font-weight: bold; }
			#publish p { margin: 10px 0px; }
			#publish aside { margin: 10px 0px; }
			#publish menu { margin: 20px 0px; padding: 0px; }
			#publish table { border-collapse: collapse; border-spacing: 0; }

			#publish a, #publish a:link, #publish a:visited, #publish a:active, #publish a:hover { cursor: pointer; color: inherit; text-decoration: none; }
			#publish a:hover { color: var(--highlight); text-decoration: underline; }
			#publish a.connect, #publish a.connect:link, #publish a.connect:visited, #publish a.connect:active, #publish a.connect:hover { color: var(--highlight); }
			#publish a.connect,
			#publish span.connect { margin-left: 16px; }
			#publish a.connect::before,
			#publish span.connect::before { content: \'-> \'; }
			
			#publish .sub-name::before { content: "["; padding-right: 0.14em; }
			#publish .sub-name::after { content: "]"; padding-left: 0.14em; }
			#publish .value-multi { font-size: 1.1rem; font-weight: normal; margin-left: 1.1rem; text-transform: lowercase; font-style: italic; }

			#publish > header { min-height: 250px; margin-top: var(--margin-default); margin-right: calc(var(--margin-default) + 150px + (var(--margin-default) * 2)); margin-left: var(--margin-default); }
			#publish > header > div:first-child { position: absolute; top: 0px; right: calc(var(--margin-default) * 2); padding: 75px 25px 25px 25px; background-color: var(--nodegoat-green); }
			#publish > header > div:first-child > img { display: block; width: 100px; }
			#publish > header > address { display: block; margin: 30px 0px; background-color: var(--back); padding: var(--margin-default); }
			#publish > header > address > * { display: block; }
			#publish > header > address > * + * { margin-top: 0.2em; }
			#publish > header > h3 + div { 10px 0px; }
			#publish > header > h1 { margin-top: 0px; }
			
			#publish > section { margin-left: var(--margin-default); margin-right: var(--margin-default); }
						
			#publish section > h2 { margin-top: calc(var(--margin-default) * 3); }
			#publish section > section { margin-bottom: calc(var(--margin-default) * 3); border-top: 1px dashed var(--text); }
			
			#publish section table td,
			#publish section table th { padding: calc(var(--padding-table) * 2) calc(var(--padding-table) + var(--padding-table-cell)) calc(var(--padding-table) * 2) var(--padding-table); vertical-align: top; text-align: left; }
			#publish section table th { font-weight: bold; font-size: 1em; }
			#publish section table > thead { letter-spacing: 1px; color: var(--text); border-bottom: 2px solid var(--text); }
			#publish section table > thead th { padding-top: var(--padding-table); padding-bottom: var(--padding-table); }
			#publish section table > tbody tr + tr:not(.information) { border-top: 1px solid var(--back-nested); }
			#publish section table > tbody tr:not(.object-sub-details):last-child { border-bottom: 1px solid var(--back-nested); }
			#publish section table > tbody tr.object-sub-details + tr.object-sub-details { border-top: 0px; }
			#publish section table > tbody tr.object-sub-details > td { padding: calc(var(--margin-default) * 1) calc(var(--margin-default) * 1.5); }
			#publish section table > tbody tr.object-sub-details:not(:last-child) > td { padding-bottom: 0px; }
			#publish section table > tbody tr.object-sub-details h4 { margin: 0px 0px 8px 0px; }
			#publish section table > tbody tr.information > td { padding-top: 0px; }
			
			#publish section aside { display: inline-block; font-style: italic; background-color: var(--back); padding: calc(var(--padding-table) * 2); }
			#publish section aside > *:first-child { margin-top: 0px; }
			#publish section aside > *:last-child { margin-bottom: 0px; }
			#publish section table td > aside:first-child { margin: 0px; }

			#publish section menu > a::before { display: inline-block; content: \'\'; height: 0.9em; width: 0.9em; margin-right: 0.4em; background-color: currentColor; -webkit-mask: url(\''.FileStore::getDataURL(getIcon('download'), FileStore::getExtensionMIMEType('svg')).'\') no-repeat center center; mask: url(\''.FileStore::getDataURL(getIcon('download'), FileStore::getExtensionMIMEType('svg')).'\') no-repeat center center; }
			#publish section menu > a + a { margin-left: 1.5em; }
		';
		
		return $str_style;
	}

	public function getDocument() {
		
		$str_html = '<!DOCTYPE html>'.EOL_1100CC
		.'<html lang="en">'.EOL_1100CC
			.'<head>'.EOL_1100CC
				.'<title>'.$this->getTitle().'</title>'
				.'<style>'.$this->getStyle().'</style>'
			.'</head>'.EOL_1100CC
			.'<body>'.EOL_1100CC
				.$this->getBody().EOL_1100CC
			.'</body>'.EOL_1100CC
		.'</html>';

		return $str_html;
	}
	
	public function getDocument1100CC() {
		
		$str_date = date('c', (!is_integer($this->arr_instance['date']) ? strtotime($this->arr_instance['date']) : $this->arr_instance['date']));
		
		$this->setContained(false);
		
		$arr = [
			'title' => $this->getTitle(),
			'date' => $str_date,
			'style' => $this->getStyle(),
			'body' => $this->getBody()
		];
		
		$this->setContained(true);

		return $arr;
	}
	
	protected function getTypeSelection($type_id, $num_nodegoat_clearance = 0) {
				
		$arr_selection = [
			'object' => true,
			'object_descriptions' => [],
			'object_sub_details' => []
		];
				
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
				
			if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, $object_description_id, false, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, $object_description_id, false, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
				continue;
			}
				
			$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
		}
		
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
			if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
				continue;
			}
			
			$arr_selection['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => true, 'object_sub_descriptions' => []];
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
				if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, $object_sub_description_id, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
				
				$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
			}
		}
		
		return $arr_selection;
	}
	
	public function getTypeObjectsJSON($type_id, $str_path_base) {
		
		$arr_type_sets = [$type_id => StoreType::getTypeSet($type_id)];
		$arr_project_type = $this->arr_project['types'][$type_id];
		
		Labels::setVariable('file_name', $str_path_base);
		Labels::setVariable('file_type', 'JSON');
		status(getLabel('msg_publish_instance_generating_file'));
		
		$stream = new StreamJSONOutput(false, false);
		$stream->getStream();
		$stream->open(false);
		
		$filter = new FilterTypeObjects($type_id, GenerateTypeObjects::VIEW_SET_EXTERNAL, false, false);
		$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE, cms_nodegoat_publish::getTypeConditions($type_id, $this->project_id));
		$filter->setScope(['types' => StoreCustomProject::getScopeTypes($this->project_id), 'project_id' => $this->project_id]);
		$filter->setSelection($this->getTypeSelection($type_id));
		
		if ($arr_project_type['type_filter_id']) {
			
			$arr_use_project_ids = array_keys($this->arr_project['use_projects']);

			$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, false, $arr_project_type['type_filter_id'], true, $arr_use_project_ids);
			$filter->setFilter(FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project_type['type_filter_object_subs']);
		}
	
		$output_objects = new CreateTypesObjectsPackage($arr_type_sets);
		
		$filter->setInitLimit(static::$num_objects_stream);
		
		while ($arr_objects = $filter->init()) {
						
			$arr_objects = $output_objects->init($type_id, $arr_objects);

			$stream->stream($arr_objects);
		}
		
		$resource = $stream->close();
		
		$this->archive->add([$str_path_base.'.json' => $resource]);
		
		fclose($resource);
	}
	
	protected function getTypeNetworkSelections($type_id, $num_nodegoat_clearance = 0) {
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		$arr_type_set_flat = StoreType::getTypeSetFlat($type_id);
		
		$arr_selections = [];
		
		$arr_selections['objects']['name'] = $this->generateFileName($arr_type_set['type']['name']).'_objects_'.$type_id;
		$arr_selections['objects']['model'] = [];
		$arr_selections['objects']['scope'] = [];
		if ($arr_type_set_flat['name']) {
			$arr_selections['objects']['scope'][] = ['id' => 'name'];
		}
		$arr_selections['objects']['filter'] = [];
		
		foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
			
			if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, $object_description_id, false, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, $object_description_id, false, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
				continue;
			}
			
			$str_target = 'objects';

			if ($arr_object_description['object_description_has_multi']) {
				
				$str_target = 'object_description_'.$object_description_id;
				
				$arr_selections[$str_target]['name'] = $this->generateFileName($arr_object_description['object_description_name']).'_object_definitions_'.$object_description_id;
				
				$arr_filter = [];
				$arr_filter['object_filter'][]['object_definitions'][$object_description_id] = ['transcension' => ['value' => 'not_empty']];
				$arr_selections[$str_target]['filter'] = $arr_filter;
			}
			
			$arr_selections[$str_target]['model']['object_descriptions'][$object_description_id] = $arr_object_description;
			
			if ($arr_object_description['object_description_ref_type_id']) {
				$arr_selections[$str_target]['scope'][] = ['id' => 'object_description_'.$object_description_id.'_id'];
			}
			$arr_selections[$str_target]['scope'][] = ['id' => 'object_description_'.$object_description_id];
		}
		
		foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				
			if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, false, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, false, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
				continue;
			}
			
			$str_target = 'object_sub_details_'.$object_sub_details_id;
			
			$arr_selections[$str_target]['name'] = $this->generateFileName($arr_object_sub_details['object_sub_details']['object_sub_details_name']).'_sub_objects_'.$object_sub_details_id;

			$arr_selections[$str_target]['model']['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => true, 'object_sub_descriptions' => []];
				
			$arr_selections[$str_target]['scope'][] = ['id' => 'object_sub_details_'.$object_sub_details_id];
			
			$str_id = 'object_sub_details_'.$object_sub_details_id.'_';
			
			foreach ($arr_type_set_flat as $str_id_check => $value) {

				if (!strStartsWith($str_id_check, $str_id)) {
					continue;
				}
				
				if ($str_id_check == $str_id.'location_ref_type_id') {
					$arr_selections[$str_target]['scope'][] = ['id' => $str_id_check.'_id'];
				}
				$arr_selections[$str_target]['scope'][] = ['id' => $str_id_check];
			}

			$arr_filter = ['object_sub_details' => $object_sub_details_id];
			$arr_selections[$str_target]['filter'] = $arr_filter;
			
			foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
				if (!StoreType::checkTypeConfigurationUserClearance($arr_type_set, $num_nodegoat_clearance, false, $object_sub_details_id, $object_sub_description_id, StoreType::CLEARANCE_PURPOSE_VIEW) || !StoreCustomProject::checkTypeConfigurationAccess($this->arr_project['types'], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id, StoreCustomProject::ACCESS_PURPOSE_VIEW)) {
					continue;
				}
				
				$arr_selections[$str_target]['model']['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
				
				if ($arr_object_sub_description['object_sub_description_ref_type_id']) {
					$arr_selections[$str_target]['scope'][] = ['id' => 'object_sub_description_'.$object_sub_description_id.'_id'];
				}
				$arr_selections[$str_target]['scope'][] = ['id' => 'object_sub_description_'.$object_sub_description_id];
			}
		}
		
		$arr_selection_model = [
			'object' => true,
			'object_descriptions' => [],
			'object_sub_details' => []
		];
		$arr_selection_scope = [
			'nodegoat_id' => true,
			'id' => true,
			'name' => true
		];
		
		foreach ($arr_selections as &$arr_selection) {

			$arr_selection['model'] = arrMergeKeys($arr_selection['model'], $arr_selection_model);

			$arr_scope = [];
			$arr_scope['types'][0][$type_id] = arrMergeKeys(['selection' => $arr_selection['scope']], $arr_selection_scope);
			$arr_scope = StoreType::parseTypeNetworkModePick($arr_scope);
			
			$arr_selection['scope'] = $arr_scope['types'];
		}
		unset($arr_selection);
		
		return $arr_selections;
	}
	
	public function getTypeObjectsCSV($type_id, $str_path_base) {
		
		$arr_project_type = $this->arr_project['types'][$type_id];
		
		$arr_project_filters = false;
		
		if ($arr_project_type['type_filter_id']) {
			
			$arr_use_project_ids = array_keys($this->arr_project['use_projects']);

			$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($this->project_id, false, false, $arr_project_type['type_filter_id'], true, $arr_use_project_ids);
			$arr_project_filters = FilterTypeObjects::convertFilterInput($arr_project_filters['object']);
		}
		
		$arr_conditions = cms_nodegoat_publish::getTypeConditions($type_id, $this->project_id);
		
		$arr_settings = ExportTypesObjectsNetworkCSV::getCollectorSettings();
		
		$func_get_collector = function($arr_selection) use ($type_id, $arr_project_type, $arr_project_filters, $arr_conditions, $arr_settings) {
						
			$arr_type_network_paths = ['start' => [$type_id => ['path' => [0]]]];

			$collect = new CollectTypesObjects($arr_type_network_paths);
			$collect->setScope(['types' => StoreCustomProject::getScopeTypes($this->project_id), 'project_id' => $this->project_id]);
			$collect->setConditions($arr_settings['conditions'], function($cur_type_id) use ($arr_conditions) {
				return $arr_conditions;
			});
			$collect->setGenerateCallback(function($generate, $cur_type_id) {
				$generate->setFormatMode(FormatTypeObjects::FORMAT_DATE_YMD);
			});
			$collect->setTypeOptions([$type_id => ['arr_selection' => $arr_selection]]);
			
			if ($arr_project_filters) {
				$collect->addLimitTypeFilters($type_id, $arr_project_filters, $arr_project_type['type_filter_object_subs']);
			}
			
			return $collect;
		};

		$arr_selections = $this->getTypeNetworkSelections($type_id);

		foreach ($arr_selections as $arr_selection) {
			
			Labels::setVariable('file_name', $str_path_base.'/'.$arr_selection['name']);
			Labels::setVariable('file_type', 'CSV');
			status(getLabel('msg_publish_instance_generating_file'));
						
			$collect = $func_get_collector($arr_selection['model']);
			
			$export = new ExportTypesObjectsNetworkCSV($type_id, $arr_selection['scope'], ['include_description_name' => true]);
			
			$export->init($collect, $arr_selection['filter']);
							
			$has_package = $export->createPackage(['separator' => ',', 'enclose' => '"']);
				
			if ($has_package) {
				
				$resource = $export->getPackage();
				
				$this->archive->add([$str_path_base.'/'.$arr_selection['name'].'.csv' => $resource]);
			}
		}
	}
}
