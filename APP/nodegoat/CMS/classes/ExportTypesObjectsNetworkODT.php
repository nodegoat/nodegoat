<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ExportTypesObjectsNetworkODT extends ExportTypesObjectsNetwork {
	
	protected $archive = false;
	
	protected $xhtml = '';
	protected $xml_content = '';
	protected $xml_styles = '';
	protected $arr_media = [];
	
	protected $do_page_break = true;
	protected $arr_xsl_parameters = [];
	
	protected static $path_library = __DIR__.'/'.self::class.'/';
	
	public function &collect($cur_target_object_id, $cur_arr, $source_path, $cur_path, $cur_target_type_id, $arr_info, $collect) {
		
		$arr_selection = $this->arr_type_network_types[$source_path][$cur_target_type_id];
			
		$arr_object = $collect->getPathObject($cur_path, $arr_info['in_out'], $cur_target_object_id, $arr_info['object_id']);

		if ($arr_selection) {

			if ($arr_selection['name']) {
				
				$this->printLabel('name', $cur_target_type_id);
				$this->printValue($arr_object['object']['object_name']);
			}
			if ($arr_selection['nodegoat_id']) {
				
				$this->printLabel('nodegoat_id', $cur_target_type_id);
				$this->printValue(($cur_target_object_id ? GenerateTypeObjects::encodeTypeObjectID($cur_target_type_id, $cur_target_object_id) : ''));
			}
			if ($arr_selection['id']) {
				
				$this->printLabel('id', $cur_target_type_id);
				$this->printValue(($cur_target_object_id ?: ''));
			}
			if ($arr_selection['sources']) {
				
				$this->printLabel('sources', $cur_target_type_id);
				
				if ($arr_object['object']['object_sources']) {
					
					foreach ($arr_object['object']['object_sources'] as $ref_type_id => $arr_source_objects) {
						
						$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($ref_type_id, arrValuesRecursive('object_source_ref_object_id', $arr_source_objects), GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE);
						
						foreach ($arr_source_objects as $arr_source_object) {
																		
							//$this->printValue($type_id.' / '.$arr_source_object['object_source_ref_object_id'].($arr_source_object['object_source_link'] ? ' - '.$arr_source_object['object_source_link'] : ''));
							$this->printValue($arr_type_object_names[$arr_source_object['object_source_ref_object_id']].($arr_source_object['object_source_link'] ? ' - '.$arr_source_object['object_source_link'] : ''));
						}
					}
				} else {
					$this->printValue('');
				}
			}
			if ($arr_selection['analysis']) {
				
				$this->printLabel('analysis', $cur_target_type_id);
				$this->printValue($arr_object['object']['object_analysis']);
			}
			
			foreach ($arr_selection['selection'] as $id => $arr_selected) {

				$arr_options = $arr_selected;
				
				if ($arr_selected['object_description_id']) {
					
					$arr_object_definition = $arr_object['object_definitions'][$arr_selected['object_description_id']];
					
					$this->printLabel('object_description', $cur_target_type_id, $arr_options);
					$this->printDescriptionValue('object_description', $cur_target_type_id, $arr_object_definition, $arr_options);
				}
				
				if ($arr_selected['object_sub_details_id']) {

					foreach (($arr_object['object_subs'] ?: [[]]) as $object_sub_id => $arr_object_sub) {
						
						if ($arr_object_sub['object_sub']['object_sub_details_id'] != $arr_selected['object_sub_details_id']) {
							continue;
						}
						
						if ($arr_selected['object_sub_description_id']) {
							
							$arr_object_sub_definition = $arr_object_sub['object_sub_definitions'][$arr_selected['object_sub_description_id']];
							
							$this->printLabel('object_sub_description', $cur_target_type_id, $arr_options);
							$this->printDescriptionValue('object_sub_description', $cur_target_type_id, $arr_object_sub_definition, $arr_options);
						} else {
														
							$arr_object_sub_value = $arr_object_sub['object_sub'];
							
							if ($id == 'object_sub_details_'.$arr_selected['object_sub_details_id'].'_id') {
								$this->printLabel('object_sub_id', $cur_target_type_id, $arr_options);
								$this->printValue($object_sub_id);
							}
							if ($id == 'object_sub_details_'.$arr_selected['object_sub_details_id'].'_date_start') {
								
								$this->printLabel('object_sub_details_date_start', $cur_target_type_id, $arr_options);
								
								$str_object_sub_date_start = '';
								
								if ($arr_object_sub_value['object_sub_date_chronology']) {
										
									$arr_date_all = $arr_object_sub_value['object_sub_date_all'];
									$arr_chronology = StoreTypeObjects::formatToChronology($arr_object_sub_value['object_sub_date_chronology']);
						
									$str_object_sub_date_start = (StoreTypeObjects::chronologyDateInt2Date($arr_date_all[StoreType::DATE_START_START], ($arr_chronology['start']['start'] ?? null), StoreType::DATE_START_START) ?: '-');
									if ($arr_date_all[StoreType::DATE_START_END]) {
										$str_object_sub_date_start .= ' / '.StoreTypeObjects::chronologyDateInt2Date($arr_date_all[StoreType::DATE_START_END], ($arr_chronology['start']['end'] ?? null), StoreType::DATE_START_END);
									}
								}
								
								$this->printValue($str_object_sub_date_start, true);
							}
							if ($id == 'object_sub_details_'.$arr_selected['object_sub_details_id'].'_date_end') {
								
								$this->printLabel('object_sub_details_date_end', $cur_target_type_id, $arr_options);
								
								$str_object_sub_date_end = '';
								
								if ($arr_object_sub_value['object_sub_date_start'] != $arr_object_sub_value['object_sub_date_end']) {
									
									$arr_date_all = $arr_object_sub_value['object_sub_date_all'];
									$arr_chronology = StoreTypeObjects::formatToChronology($arr_object_sub_value['object_sub_date_chronology']);
									
									$str_object_sub_date_end = StoreTypeObjects::chronologyDateInt2Date($arr_date_all[StoreType::DATE_END_END], ($arr_chronology['end']['end'] ?? null), StoreType::DATE_END_END);
									if ($arr_date_all[StoreType::DATE_END_START]) {
										$str_object_sub_date_end = StoreTypeObjects::chronologyDateInt2Date($arr_date_all[StoreType::DATE_END_START], ($arr_chronology['end']['start'] ?? null), StoreType::DATE_END_START).' / '.$str_object_sub_date_end;
									}
								}
								
								$this->printValue($str_object_sub_date_end, true);
							}
							if ($id == 'object_sub_details_'.$arr_selected['object_sub_details_id'].'_date_chronology') {
								
								$this->printLabel('object_sub_details_date_chronology', $cur_target_type_id, $arr_options);
								
								$str_object_sub_date_chronology = '';
								
								if ($arr_object_sub_value['object_sub_date_chronology']) {
									$str_object_sub_date_chronology = StoreTypeObjects::formatToChronology($arr_object_sub_value['object_sub_date_chronology']);
									$str_object_sub_date_chronology = value2JSON($str_object_sub_date_chronology, JSON_PRETTY_PRINT);
								}
								
								$this->printValue($str_object_sub_date_chronology, true, 'pre');
							}
							if ($id == 'object_sub_details_'.$arr_selected['object_sub_details_id'].'_location_ref_type_id') {
								$this->printLabel('object_sub_details_location_ref', $cur_target_type_id, $arr_options);
								$this->printValue($arr_object_sub_value['object_sub_location_ref_object_name']);
							}
							if ($id == 'object_sub_details_'.$arr_selected['object_sub_details_id'].'_location_ref_type_id_id') {
								$arr_options['use_id'] = true;
								$this->printLabel('object_sub_details_location_ref', $cur_target_type_id, $arr_options);
								$this->printValue($arr_object_sub_value['object_sub_location_ref_object_id']);
							}
							if ($id == 'object_sub_details_'.$arr_selected['object_sub_details_id'].'_location_geometry') {
								$this->printLabel('object_sub_details_location_geometry', $cur_target_type_id, $arr_options);
								$this->printValue($arr_object_sub_value['object_sub_location_geometry']);
							}
						}
					}
				}
			}
		}
		
		return $cur_arr;
	}
	
	protected function openPage() {
		
		if (!$this->do_page_break) {
			return;
		}
		
		$this->xhtml .= '<article>';
	}
	
	protected function closePage() {
		
		if (!$this->do_page_break) {
			return;
		}
		
		$this->xhtml .= '</article>';
	}
	
	protected function printLabel($type, $type_id, $arr_options = []) {
		
		if (!$this->arr_options['include_description_name']) {
			return;
		}
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		$str_label = '';
		
		switch ($type) {
			case 'nodegoat_id':
				$str_label = 'nodegoat ID';
				break;
			case 'id':
				$str_label = 'ID';
				break;
			case 'name':
				$str_label = getLabel('lbl_name');
				break;
			case 'sources':
				$str_label = getLabel('lbl_source');
				break;
			case 'analysis':
				$str_label = getLabel('lbl_analysis');
				break;
			case 'object_description':
				$str_label = Labels::parseTextVariables($arr_type_set['object_descriptions'][$arr_options['object_description_id']]['object_description_name']).($arr_options['use_id'] ? ' ID' : '');
				break;
			case 'object_sub_id':
			case 'object_sub_description':
			case 'object_sub_details_date_start':
			case 'object_sub_details_date_end':
			case 'object_sub_details_date_chronology':
			case 'object_sub_details_location_ref':
			case 'object_sub_details_location_geometry':
				switch ($type) {
					case 'object_sub_id':
						$name = 'ID';
						break;
					case 'object_sub_description':
						$name = Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']]['object_sub_description_name']).($arr_options['use_id'] ? ' ID' : '');
						break;
					case 'object_sub_details_date_start':
						$name = getLabel('lbl_date_start');
						break;
					case 'object_sub_details_date_end':
						$name = getLabel('lbl_date_end');
						break;
					case 'object_sub_details_date_chronology':
						$name = getLabel('lbl_chronology');
						break;
					case 'object_sub_details_location_ref':
						$name = getLabel('lbl_location_reference').($arr_options['use_id'] ? ' ID' : '');
						break;
					case 'object_sub_details_location_geometry':
						$name = getLabel('lbl_geometry');
						break;
				}
				$str_label = '['.Labels::parseTextVariables($arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_details']['object_sub_details_name']).'] '.$name;
				break;
		}
		
		$this->xhtml .= '<h3>'.$str_label.'</h3>';
	}
	
	protected function printValue($value, $do_escape = false, $xml_tag = 'p') {
		
		if (!$value) {
			
			$this->xhtml .= '<p>-</p>';
			return;
		}
		
		if ($do_escape) {
			$value = strEscapeXML($value);
		}
		
		$this->xhtml .= '<'.$xml_tag.'>'.$value.'</'.$xml_tag.'>';
	}
	
	protected function printDescriptionValue($type, $type_id, $arr_definition, $arr_options) {
		
		if (!$arr_definition) {
			
			$this->xhtml .= '<p>-</p>';
			return;
		}
		
		$arr_type_set = StoreType::getTypeSet($type_id);
		
		if ($type == 'object_description') {
			
			$key_ref_object_id = 'object_definition_ref_object_id';
			$key_value = 'object_definition_value';
			
			$arr_object_description = $arr_type_set['object_descriptions'][$arr_options['object_description_id']];
			
			if ($arr_object_description['object_description_ref_type_id'] && !$arr_object_description['object_description_is_dynamic']) {
				$value_type = 'ref_object_id';
			} else {
				$value_type = $arr_object_description['object_description_value_type'];
				$arr_value_type_settings = $arr_object_description['object_description_value_type_settings'];
			}
			
			$arr_definition_style = $arr_definition['object_definition_style'];
		} else {
			
			$key_ref_object_id = 'object_sub_definition_ref_object_id';
			$key_value = 'object_sub_definition_value';
			
			$arr_object_sub_description = $arr_type_set['object_sub_details'][$arr_options['object_sub_details_id']]['object_sub_descriptions'][$arr_options['object_sub_description_id']];
			
			if ($arr_object_sub_description['object_sub_description_ref_type_id'] && !$arr_object_sub_description['object_sub_description_is_dynamic']) {
				$value_type = 'ref_object_id';
			} else {
				$value_type = $arr_object_sub_description['object_sub_description_value_type'];
				$arr_value_type_settings = $arr_object_sub_description['object_sub_description_value_type_settings'];
			}
			
			$arr_definition_style = $arr_definition['object_sub_definition_style'];
		}
	
		$arr_definition[$key_ref_object_id] = ($arr_definition[$key_ref_object_id] ? (array)$arr_definition[$key_ref_object_id] : []);
		$arr_definition[$key_value] = ($arr_definition[$key_value] ? (array)$arr_definition[$key_value] : []);
		
		// References
		
		if ($value_type == 'ref_object_id') {
			
			if ($arr_definition[$key_ref_object_id]) {
					
				foreach ($arr_definition[$key_ref_object_id] as $key => $ref_object_id) {
					
					if ($arr_options['use_id']) {
						$xhtml = $ref_object_id;
					} else {
						$xhtml = $arr_definition[$key_value][$key];
					}
					
					$this->xhtml .= '<p>'.($arr_definition_style ? '<span style="'.$arr_definition_style.'">'.$xhtml.'</span>' : $xhtml).'</p>';
				}
			} else {
				$this->xhtml .= '<p>-</p>';
			}
						
			return;
		}
		
		// Values
		
		if ($arr_options['use_id']) { // Values with IDs
							
			if ($arr_definition[$key_ref_object_id]) {
			
				foreach ($arr_definition[$key_ref_object_id] as $arr_definition_dynamic) {
						
					foreach ($arr_definition_dynamic as $ref_type_id => $arr_dynamic) {
						
						$ref_object_id = $arr_dynamic[$key_ref_object_id];
						
						$this->xhtml .= '<p>'.($arr_definition_style ? '<span style="'.$arr_definition_style.'">'.$ref_object_id.'</span>' : $ref_object_id).'</p>';
					}
				}
			} else {
				$this->xhtml .= '<p>-</p>';
			}
			
			return;
		}
		
		// Values plain
		
		if ($arr_definition[$key_value]) {
			
			if ($value_type == 'media') {
				$extra = ['arr_path_media' => &$this->arr_media]; // Capture local media files
			} else {
				$extra = false;
			}
			
			$no_paragraph = ($value_type == 'text_tags' || $value_type == 'text_layout');
				
			foreach ($arr_definition[$key_value] as $key => $value) {
				
				$value = StoreTypeObjects::formatToHTMLPlainValue($value_type, $value, $arr_value_type_settings, $extra);
				$value = ($arr_definition_style ? '<span style="'.$arr_definition_style.'">'.$value.'</span>' : $value);
				
				if (!$no_paragraph) {
					$this->xhtml .= '<p>'.$value.'</p>';
				} else {
					$this->xhtml .= $value;
				}
			}
		} else {
			$this->xhtml .= '<p>-</p>';
		}
	}
	
	protected function generateXMLODT() {
		
		$xhtml = Response::parse($this->xhtml);

		$xhtml = '<html xmlns="http://www.w3.org/1999/xhtml">'.$xhtml.'</html>';
		
		try {
			$document_xml = new DOMDocument();
			$document_xml->strictErrorChecking = false;
			$document_xml->loadXML($xhtml);
		} catch (Exception $e) {
			error(getLabel('msg_malformed_xml'), TROUBLE_ERROR, LOG_BOTH, false, $e);
		}
		
		$document_xsl = new DOMDocument();
		$document_xsl->load(self::$path_library.'xsl/xhtml2odt.xsl');
		
		$template = new XSLTProcessor();
		$template->importStylesheet($document_xsl);
		
		foreach ($this->arr_xsl_parameters as $key => $value) {
			$template->setParameter('', $key, $value);
		}
		
		$xml = $template->transformToXML($document_xml);
		
		if ($xml === false) {
			error(getLabel('msg_malformed_xml').' XSLT transformation failed.');
		}
		
		$xml = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $xml);
		
		$file_template = read(self::$path_library.'content.xml');
		
		$this->xml_content = str_replace('</office:text>', $xml.'</office:text>', $file_template);
		
		$this->xml_styles = read(self::$path_library.'styles.xml');

		$document_xml_content = new DOMDocument();
		$document_xml_content->loadXML($this->xml_content);
		$document_xml_styles = new DOMDocument();
		$document_xml_styles->loadXML($this->xml_styles);
		$document_xsl = new DOMDocument();
		$document_xsl->load(self::$path_library.'xsl/styles.xsl');
		
		$template = new XSLTProcessor();
		$template->importStylesheet($document_xsl);
		
		$this->xml_content = $template->transformToXML($document_xml_content);
		$this->xml_styles = $template->transformToXML($document_xml_styles);
		
		if ($this->xml_content === false || $this->xml_styles === false) {
			error(getLabel('msg_malformed_xml').' Styles could not be generated.');
		}
	}
	
	protected function createXMLContent() {
		
		return $this->xml_content;
	}
	
	protected function createXMLStyles() {
		
		return $this->xml_styles;
	}
	
	protected function createXMLMeta() {
		
		return '<?xml version="1.0" encoding="UTF-8"?>
		<office:document-meta xmlns:grddl="http://www.w3.org/2003/g/data-view#" xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" office:version="1.2">
			<office:meta>
				<meta:creation-date>'.date('c').'</meta:creation-date>
				<dc:date>'.date('c').'</dc:date>
				<dc:creator>nodegoat User</dc:creator>
				<meta:document-statistic meta:table-count="" meta:image-count="" meta:object-count="" meta:page-count="" meta:paragraph-count="" meta:word-count="" meta:character-count="" meta:non-whitespace-character-count="" />
				<meta:generator>'.Labels::getServerVariable('user_agent').'</meta:generator>
			</office:meta>
		</office:document-meta>';
	}
	
	protected function createXMLSettings() {
		
		return '<?xml version="1.0" encoding="UTF-8"?>
		<office:document-settings xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" office:version="1.2">
			<office:settings>
			</office:settings>
		</office:document-settings>';
	}
	
	protected function createXMLManifest() {
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
		<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2" xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0">
			<manifest:file-entry manifest:full-path="/" manifest:version="1.2" manifest:media-type="application/vnd.oasis.opendocument.text"/>
			<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>
			<manifest:file-entry manifest:full-path="settings.xml" manifest:media-type="text/xml"/>
			<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
			<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>
			<manifest:file-entry manifest:full-path="Thumbnails/thumbnail.png" manifest:media-type="image/png"/>';
		
		foreach ($this->arr_media as $path_local => $path_source) {
			
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$str_type = $finfo->file($path_source);

			$xml .= '<manifest:file-entry manifest:full-path="'.$path_local.'" manifest:media-type="'.$str_type.'"/>';
		}
		
		$xml .= '</manifest:manifest>';
		
		return $xml;
	}
		
	public function createPackage($arr_options) {

		$this->class_collect->setInitLimit(static::$num_objects_stream);
		
		$this->do_page_break = ($arr_options['flow'] == 'break');

		while ($this->class_collect->init($this->arr_filters)) {
			
			$arr_objects = $this->class_collect->getPathObjects('0');
			
			GenerateTypeObjects::setClearSharedTypeObjectNames(false); // Disabled clearing name cache

			foreach ($arr_objects as $object_id => $arr_object) { // 0, source, as collection is path based
				
				$this->openPage();

				$this->class_collect->getWalkedObject($object_id, [], [$this, 'collect']);
				
				$this->closePage();
			}
			
			// Manual clearing name cache, being disabled for the iterator
			GenerateTypeObjects::setClearSharedTypeObjectNames(true);
			GenerateTypeObjects::printSharedTypeObjectNames('');
		}
		
		// See xls/param.xls for parameter options
		$this->arr_xsl_parameters['url'] = SiteStartVars::getBasePath(0, false);
		
		$this->createODT();
		
		return true;
	}
	
	public function createODT() {
		
		Response::setFormat(Response::OUTPUT_XML | Response::RENDER_XML);
		
		$this->generateXMLODT();
		
		$this->archive = new FileArchive();
					
		$this->archive->add([
			'mimetype' => 'application/vnd.oasis.opendocument.text',
			'content.xml' => $this->createXMLContent(),
			'styles.xml' => $this->createXMLStyles(),
			'meta.xml' => $this->createXMLMeta(),
			'settings.xml' => $this->createXMLSettings(),
			'META-INF/manifest.xml' => $this->createXMLManifest(),
			'Thumbnails/thumbnail.png' => self::$path_library.'thumbnail.png'
		]);
		
		if ($this->arr_media) {
			$this->archive->add($this->arr_media);
		}
	}
	
	public function readPackage($str_filename) {
		
		FileStore::readFile($this->archive->get(), $str_filename.'.odt', true);
	}
	
	public static function getCollectorSettings() {
	
		return [
			'conditions' => GenerateTypeObjects::CONDITIONS_MODE_STYLE_INCLUDE
		];
	}
}
