<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class ReconcileTypeObjectsValues {
		
	const RESULT_MODE_DEFAULT = 0;
	const RESULT_MODE_TAG = 1;
	const RESULT_MODE_TAGS_OVERWRITE_TYPE = 2; // Value can be found in text
	const RESULT_MODE_TAGS_OVERWRITE_ALL = 4; // Value can be found in text

	
	protected $type_id = false;
	
	protected $do_test_pattern_pairs = false;
	protected $arr_test_map = [];
	protected $arr_test_type_set = [];
	protected $arr_test_filter = false;
	protected $arr_test_conditions = false;

	protected $arr_reconcile = [];
	protected $arr_results = [];
	protected $arr_pattern_pairs = [];
	protected $arr_tests = [];
	protected $arr_test_statistics = [];
	
	protected $num_pattern_threshold = 60; // 100 = value matches an entity (text surrounded by whitespace/punctuation), 0 = Value can be found in text
	protected $num_pattern_distance = 6;
	protected $do_pattern_complete = false;
	protected $num_score_threshold = 25.00;
	protected $num_score_match_difference = 25.00;
	protected $do_prioritise_match_pattern_pair = false;
	protected $num_score_overlap_difference = 50.00;
	
	protected $num_value_absoluteness_limit = 100;
	
	protected $mode = 0;
	
	protected static $str_source_transliteration_compatible = '';
	protected static $str_source_transliteration_compatible_processed = '';
	
	const TRANSLITERATION_RULE = ':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC; :: [:Latin:] Latin-ASCII;'; // Consider http://www.unicode.org/Public/UNIDATA/SpecialCasing.txt
	const TRANSLITERATION_RULE_GRAPHEME_LIGATURE = ['ẞ', 'ß', 'Æ', 'æ', 'Œ', 'œ']; // Consider https://en.wikipedia.org/wiki/Ligature_(writing)
	
	const SOURCE_PARSE_SEPARATOR = EOL_1100CC.'$|$'.EOL_1100CC; // Include EOL for proper parsing
	const SOURCE_PARSE_INDICATOR = '!*!';
	const SOURCE_PARSE_INDICATOR_LENGTH = 3;
				
	public function __construct($type_id) {

		$this->type_id = (int)$type_id;
		
		
		$this->transliterator = Transliterator::createFromRules(static::TRANSLITERATION_RULE, Transliterator::FORWARD);
	}
	
	public function setMode($mode) {
		
		$this->mode = $mode;
	}
	
	public function setPattern($num_threshold, $num_distance = false, $do_complete = null) {
		
		$this->num_pattern_threshold = (float)($num_threshold >= 0 && $num_threshold <= 100 ? $num_threshold : 80);
		$this->num_pattern_distance = (int)($num_distance > 0 ? $num_distance : 6);
		$this->do_pattern_complete = (bool)($do_complete !== null ? $do_complete : false);
	}
	
	public function setThreshold($num_score, $num_score_match = false, $num_score_overlap = false, $do_prioritise_match_pattern_pair = null) {
		
		$this->num_score_threshold = round((float)($num_score > 0 && $num_score <= 100 ? $num_score : 25), 2);
		
		if ($num_score_match !== false) {
			$this->num_score_match_difference = round((float)($num_score_match >= 0 && $num_score_match <= 99 ? $num_score_match : 25), 2);
		}
		if ($num_score_overlap !== false) {
			$this->num_score_overlap_difference = round((float)($num_score_overlap >= 0 && $num_score_overlap <= 99 ? $num_score_overlap : 50), 2);
		}
		
		if ($do_prioritise_match_pattern_pair !== null) {
			$this->do_prioritise_match_pattern_pair = (bool)$do_prioritise_match_pattern_pair;
		}
	}
	
	public function addTest($do_pattern_pairs, $arr_map, $arr_filter = false, $arr_conditions = false) {
		
		$this->do_test_pattern_pairs = (bool)$do_pattern_pairs;
		$this->arr_test_map = $arr_map;
		$this->arr_test_type_set = StoreType::getTypeSetByFlatMap($this->type_id, $arr_map);
		
		$this->arr_test_filter = $arr_filter;
		$this->arr_test_conditions = $arr_conditions;
	}
	 
	public function setSourceTypeObjects($source_type_id, $arr_map, $arr_filter = false, $arr_conditions = false) {
		
		$filter = new FilterTypeObjects($source_type_id, GenerateTypeObjects::VIEW_SET);
		
		if ($arr_filter !== false) {
			$filter->setFilter($arr_filter);
		}
		if ($arr_conditions) {
			$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_TEXT, $arr_conditions);
		}
		
		$arr_selection = StoreType::getTypeSelectionByFlatMap($source_type_id, $arr_map);
		$filter->setSelection($arr_selection);
		
		$arr_objects_set = $filter->init();
		
		if ($arr_conditions) {
			
			Response::holdFormat(true);
			Response::setFormat(Response::OUTPUT_JSON);
			
			$arr_objects_set = json_decode(Response::parse($arr_objects_set), true);
			
			Response::holdFormat();
		}
		
		$this->arr_reconcile = [];
		
		foreach ($arr_objects_set as $object_id => $arr_object) {
							
			$arr_object_values = GenerateTypeObjects::getTypeObjectValuesByFlatMap($source_type_id, $arr_object, $arr_map);
						
			$this->arr_reconcile[$object_id] = $this->parseSourceValue($arr_object_values);
			$this->arr_reconcile[$object_id]['type_id'] = $source_type_id;
		}
	}
	
	public function setSourceValues($arr_source_values) {

		$this->arr_reconcile = [];
			
		foreach ($arr_source_values as $str_source_identifier => $arr_values) {
			
			$this->arr_reconcile[$str_source_identifier] = $this->parseSourceValue([$arr_values]);
		}
	}
	
	protected function parseSourceValue($arr_source_value) {
		
		$arr_process = [];
		$arr_source = [];
		
		foreach ($arr_source_value as $key => $arr_values) {

			if (!is_array($arr_values)) { // Make sure to provide multi-values
				$str_source = $arr_values;
				$arr_values = [$arr_values];
			} else {
				$str_source = implode(' ', $arr_values);
			}
			
			if (strpos($str_source, '[/reconcile]') !== false) {
				
				$num_count = 0;
				$str_parse = '';
				
				$str_source = preg_replace_callback('#\[reconcile\]((?>(?:(?>[^\[]+)|\[(?!/?reconcile\]))*))\[/reconcile\]#', function($arr_match) use (&$str_parse, &$num_count) {
															
					$str_parse .= ($num_count ? static::SOURCE_PARSE_SEPARATOR : '').$arr_match[1];
					
					$num_count++;

					return '[[reconcile:'.$num_count.']]';
				}, $str_source);
				
				$str_source = static::SOURCE_PARSE_INDICATOR.$str_source;
				
				$arr_source[$key] = ['parse' => $str_parse, 'target' => $str_source];
			} else {
				
				$arr_source[$key] = $str_source;
			}		
			
			foreach ($arr_values as $value) {
				
				$value = $this->transliterate($value);
				
				$has_match = preg_match_all('/[^\p{P}\s]{4,}+/', $value, $arr_matches); // Any other than punctuation or whitespace
				
				if (!$has_match) {
					continue;
				}
				
				foreach ($arr_matches[0] as $str_match) {
					$arr_process[$str_match] = $str_match;
				}
			}
		}
		
		return [
			'process' => $arr_process,
			'source' => $arr_source
		];
	}
	
	protected function restoreSourceValue($str_value_target, $str_source_parse = null) {
				
		if (!strStartsWith($str_value_target, static::SOURCE_PARSE_INDICATOR)) {

			return ($str_source_parse !== null ? $str_source_parse : $str_value_target);
		}
		
		$str_value_target = substr($str_value_target, static::SOURCE_PARSE_INDICATOR_LENGTH);
		
		if ($str_source_parse !== null) {
		
			$arr_parse = explode(self::SOURCE_PARSE_SEPARATOR, $str_source_parse);
					
			$num_count = 0;
			
			foreach ($arr_parse as $str_value) {
				
				$num_count++;
				
				$str_value_target = str_replace('[[reconcile:'.$num_count.']]', $str_value, $str_value_target);
			}
		}
		
		return $str_value_target;
	}
	
	protected function getResultPatternTypeObjectPairs() {
		
		$sql_table_name_test = false;
		
		if ($this->arr_test_filter !== false) {
		
			$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_ID);
					
			$filter->setFilter($this->arr_test_filter);
			
			$sql_table_name_test = $filter->storeResultTemporarily(false, true);
		}
		
		$this->arr_pattern_pairs = StorePatternsTypeObjectPair::getTypeObjectPairs($this->type_id, false, ($sql_table_name_test ? ['table' => $sql_table_name_test] : false), [StorePatternsTypeObjectPair::PATTERN_COMPOSITION_MATCH, StorePatternsTypeObjectPair::PATTERN_COMPOSITION_MATCH_CONTEXT]);
	}
	
	protected function getResultTypeObjectTests($arr_object_ids) {
		
		$filter = new FilterTypeObjects($this->type_id, GenerateTypeObjects::VIEW_SET);
		
		$filter->setFilter(['objects' => $arr_object_ids]);
		
		if ($this->arr_test_filter !== false) {
			$filter->setFilter($this->arr_test_filter);
		}
		if ($this->arr_test_conditions) {
			$filter->setConditions(GenerateTypeObjects::CONDITIONS_MODE_TEXT, $this->arr_test_conditions);
		}
		
		$arr_selection = StoreType::getTypeSelectionByFlatMap($this->type_id, $this->arr_test_map);
		$filter->setSelection($arr_selection);
		
		$arr_objects_set = $filter->init();
		
		if ($this->arr_test_conditions) {
			
			Response::holdFormat(true);
			Response::setFormat(Response::OUTPUT_JSON);
			
			$arr_objects_set = json_decode(Response::parse($arr_objects_set), true);
			
			Response::holdFormat();
		}
		
		$this->arr_tests = [];
		
		foreach ($arr_objects_set as $object_id => $arr_object) {
							
			$arr_object_values = GenerateTypeObjects::getTypeObjectValuesByFlatMap($this->type_id, $arr_object, $this->arr_test_map);

			$this->arr_tests[$object_id] = $this->parseTestValue($arr_object_values);		
		}
	}
	
	protected function parseTestValue($arr_test_value) {
		
		$arr_test = [];
		
		foreach ($arr_test_value as $arr_values) {

			if (!is_array($arr_values)) { // Make sure to provide multi-values
				$arr_values = [$arr_values];
			}
			
			foreach ($arr_values as $key => &$value) {
				
				$value = $this->transliterate($value);
				$value = explode(' ', $value);
				$value = arrParseRecursive($value, TYPE_TEXT);
				$value = array_unique(array_filter($value));
				
				if (!$value) {
					unset($arr_values[$key]);
				}
			}
			unset($value);
			
			if (!$arr_values) {
				continue;
			}
			
			$arr_test[] = $arr_values;
		}
		
		return $arr_test;
	}
	
	protected function formatTestValue($str_test_value) {
		
		$str_test_value = parseValue(str_replace(' ', '', $str_test_value), TYPE_TEXT);
		
		return $str_test_value;
	}
	
	protected function getTestValueStatistics($object_id) {
		
		if (isset($this->arr_test_statistics[$object_id])) {
			return $this->arr_test_statistics[$object_id];
		}
		
		$arr_test_value = $this->arr_tests[$object_id];
	
		$arr_length = [];
		$count_match = 0;
		$count_groups = count($arr_test_value); // Value group
		$num_score_sum = 0;
		
		foreach ($arr_test_value as $arr_values) {
			
			$count_values = 1; // Value multi
				
			foreach ($arr_values as $arr_value) {
				
				$count_value = 1; // Value exploded
				
				foreach ($arr_value as $str_value) {
					
					$num_length = ($arr_length[$str_value] ?? null);
					
					if ($num_length === null) {
						$num_length = strlen($str_value);
						$arr_length[$str_value] = $num_length;
					}
					
					$num_score = ($count_groups / $count_values / $count_value);
					
					if ($num_length <= 3) { // Limit score of short values
						$num_score = $num_score * ($num_length / 20);
					}
					
					$this->arr_test_statistics[$object_id][$count_match] = ['length' => $num_length, 'score' => $num_score];
					$num_score_sum += $num_score;
					
					$count_value++;
					$count_match++;
				}
				
				$count_values++;
			}
			
			$count_groups--;
		}
		
		foreach ($this->arr_test_statistics[$object_id] as $count_match => &$arr_statistics) {
			
			$arr_statistics['score'] = (($arr_statistics['score'] / $num_score_sum) * 100);			
		}
		
		return $this->arr_test_statistics[$object_id];
	}
	
	public function init() {
		
		$this->arr_results = [];
		
		if ($this->do_test_pattern_pairs) {
			
			$this->initResultPatternTypeObjectPairs();
		}
		
		if ($this->arr_test_map) {
		
			try {
				$str_host_ir = cms_nodegoat_definitions::getInformationRetrievalHost();
			} catch (Exception $e) { }
			
			if ($str_host_ir) {
				
				$this->initResultTestValuesInformationRetrieval($str_host_ir);
			} else {
			
				$this->initResultTestValuesSQL();
			}
		}
		
		return true;
	}
	
	protected function initResultPatternTypeObjectPairs() {
		
		$this->getResultPatternTypeObjectPairs();
		
		if (!$this->arr_pattern_pairs) {
			return;
		}
		
		foreach ($this->arr_reconcile as $str_source_identifier => $arr_reconcile_value) {
			
			if (!$arr_reconcile_value['process']) {
				continue;
			}
			
			$arr_source_value_clean = [];
			
			foreach ($arr_reconcile_value['source'] as $str_source_value_identifier => $str_source_value) {
				
				if (is_array($str_source_value)) {
					$str_source_value = $str_source_value['parse'];
				}
					
				$arr_source_value_clean[$str_source_value_identifier] = StoreTypeObjects::clearObjectDefinitionText($str_source_value, StoreTypeObjects::TEXT_TAG_OBJECT);
			}
			
			foreach ($this->arr_pattern_pairs as $str_identifier => $arr_pattern_pair) {
				
				$object_id = $arr_pattern_pair['object_id'];
				
				if (isset($this->arr_results[$str_source_identifier][$object_id])) { // Already found in Pattern Pairs
					continue;
				}
				
				$has_match = false;
				
				foreach ($arr_source_value_clean as $str_source_value_identifier => $str_source_value_clean) {
					
					$pattern = new PatternEntity($arr_pattern_pair['pattern_value']);
					$has_match = $pattern->getPatternMatch($str_source_value_clean);
										
					if ($has_match) {
						break;
					}
				}
				
				if (!$has_match) {
					continue;
				}
												
				$this->arr_results[$str_source_identifier][$object_id] = [
					'object_id' => $object_id,
					'pattern_pair' => $str_identifier
				];
			}
		}
	}
	
	protected function initResultTestValuesInformationRetrieval($str_host_ir) {
		
		$arr_object_ids = [];
						
		foreach ($this->arr_reconcile as $str_source_identifier => $arr_reconcile_value) {
			
			if (!$arr_reconcile_value['process']) {
				continue;
			}
			
			$ir = new QueryTypeObjectsInformationRetrieval($str_host_ir);
			
			$arr_query = [];
			
			foreach ($arr_reconcile_value['process'] as $str_value) {
				$arr_query[] = $str_value;
			}
			
			$ir->setQuery($arr_query);	
			$ir->setTypesSetSelection([$this->type_id => $this->arr_test_map]);
			
			if ($this->arr_test_filter !== false) {
				//$ir->setTypesObjectsSelection([$this->type_id => $arr_test_object_ids]);
			}
			
			$ir->query();
			
			$arr_results = $ir->getResult();
			
			if (!$arr_results) {
				continue;
			}
			
			$arr_results = $arr_results[$this->type_id];
			
			foreach ($arr_results as $object_id => $arr_details) {
								
				if (isset($this->arr_results[$str_source_identifier][$object_id])) { // Found in Pattern Pairs
					continue;
				}
				
				$arr_object_ids[$object_id] = $object_id;
				
				$this->arr_results[$str_source_identifier][$object_id] = [
					'object_id' => $object_id
				];
			}
		}
		
		$this->getResultTypeObjectTests($arr_object_ids);
	}
	
	protected function initResultTestValuesSQL() {

		$arr_sql = [];
		
		$func_sql_search = function($arr_group_values, $sql_column) {

			$arr_sql_search = [];
			
			foreach ($arr_group_values as $str_value) {
						
				$str_value = DBFunctions::str2Search($str_value);
				
				$arr_sql_search[] = "CASE
					WHEN LENGTH(".$sql_column.") < ".mb_strlen($str_value)." THEN '".$str_value."' LIKE CONCAT('%', ".$sql_column.", '%')
					ELSE ".$sql_column." LIKE '%".$str_value."%'
				END";
			}
			
			return $arr_sql_search;
		};
		
		if ($this->arr_test_filter !== false) {
			// $sql_table_name_test = ;
		}
			
		foreach ($this->arr_reconcile as $str_source_identifier => $arr_reconcile_value) {
			
			if (!$arr_reconcile_value['process']) {
				continue;
			}
			
			$arr_sql_union = [];
			
			if ($this->arr_test_type_set['type']) {
				
				$version_select = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'search', 's');
				$arr_sql_value = $func_sql_search($arr_reconcile_value['process'], 's.name');

				$arr_sql_union[] = "SELECT
					s.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." AS s
					WHERE s.type_id = ".$this->type_id."
						AND (".implode(' OR ', $arr_sql_value).")
						AND ".$version_select."
				";
			}
			
			foreach ($this->arr_test_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
								
				$value_type = $arr_object_description['object_description_value_type'];
				$sql_column = StoreTypeObjects::formatFromSQLValue($value_type, 's.'.StoreType::getValueTypeValue($value_type, 'search'));
				$version_select = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'search', 's', $value_type);
				$version_select_to = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object', 'nodegoat_to');
				$arr_sql_value = $func_sql_search($arr_reconcile_value['process'], $sql_column);

				$arr_sql_union[] = "SELECT
					nodegoat_to.id
						FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." AS nodegoat_to
						JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'search')." AS s ON (s.object_id = nodegoat_to.id AND s.object_description_id = ".(int)$object_description_id." AND ".$version_select.")
					WHERE nodegoat_to.type_id = ".$this->type_id."
						AND (".implode(' OR ', $arr_sql_value).")
						AND ".$version_select_to."
				";
			}
			
			foreach ($this->arr_test_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
				foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
					
					$value_type = $arr_object_sub_description['object_sub_description_value_type'];
					$sql_column = StoreTypeObjects::formatFromSQLValue($value_type, 's.'.StoreType::getValueTypeValue($value_type, 'search'));
					$version_select = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'search', 's', $value_type);
					$version_select_to = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object', 'nodegoat_to');
					$version_select_tos = GenerateTypeObjects::generateVersioning(GenerateTypeObjects::VERSIONING_ACTIVE, 'object_sub', 's_tos');
					$arr_sql_value = $func_sql_search($arr_reconcile_value['process'], $sql_column);

					$arr_sql_union[] = "SELECT
						nodegoat_to.id
							FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." AS nodegoat_to
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUBS')." AS s_tos on (s_tos.object_id = nodegoat_to.id AND s_tos.object_sub_details_id = ".$object_sub_details_id." AND ".$version_select_tos.")
							JOIN ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_SUB_DEFINITIONS').StoreType::getValueTypeTable($value_type, 'search')." AS s ON (s.object_sub_id = s_tos.id AND s.object_sub_description_id = ".(int)$object_sub_description_id." AND ".$version_select.")
						WHERE nodegoat_to.type_id = ".$this->type_id."
							AND (".implode(' OR ', $arr_sql_value).")
							AND ".$version_select_to."
					";
				}
			}
					
			$arr_sql[] = "SELECT
				object_matches.id
					FROM (".implode(" UNION ALL ", $arr_sql_union).") AS object_matches
				GROUP BY object_matches.id
			;";
		}
		
		$arr_res = DB::queryMulti(implode('', $arr_sql));			
		$count = 0;
		
		$arr_object_ids = [];
		
		foreach ($this->arr_reconcile as $str_source_identifier => $arr_reconcile_value) {
			
			if (!$arr_reconcile_value['process']) {
				continue;
			}
			
			$res = $arr_res[$count];
						
			while ($arr_row = $res->fetchRow()) {
				
				$object_id = (int)$arr_row[0];
				
				if (isset($this->arr_results[$str_source_identifier][$object_id])) { // Found in Pattern Pairs
					continue;
				}
				
				$arr_object_ids[$object_id] = $object_id;
				
				$this->arr_results[$str_source_identifier][$object_id] = [
					'object_id' => $object_id
				];
			}
			
			$count++;
		}
		
		$this->getResultTypeObjectTests($arr_object_ids);
	}
	
	public function getResult($mode_result = self::RESULT_MODE_DEFAULT) {
		
		$this->parseResultsTests($mode_result);
		
		foreach ($this->arr_results as &$arr_result_set) {
			
			usort($arr_result_set['objects'], function($a, $b) {
				return (is_array($b['score']) ? array_sum($b['score']) : $b['score']) <=> (is_array($a['score']) ? array_sum($a['score']) : $a['score']);
			});
		}

		return $this->arr_results;
	}
		
	protected function parseResultsTests($mode_result = 0) { // Target test value using various source values

		foreach ($this->arr_reconcile as $str_source_identifier => $arr_reconcile_value) {
			
			$this->arr_results[$str_source_identifier] = [
				'objects' => ($this->arr_results[$str_source_identifier] ?? []),
				'values' => $arr_reconcile_value['source']
			];
		}
		
		$this->arr_reconcile = null; // Free memory

		foreach ($this->arr_results as $str_source_identifier => &$arr_result_set) {
			
			$arr_source_value = [];
			$arr_source_value_clean = [];
			$arr_source_value_normalised = [];
			$arr_source_value_normalised_clean = [];
			
			foreach ($arr_result_set['values'] as $str_source_value_identifier => $str_source_value) {

				if (is_array($str_source_value)) {
					
					$arr_result_set['values'][$str_source_value_identifier] = $str_source_value['target'];
					$str_source_value = $str_source_value['parse'];
				}
				
				$arr_source_value[$str_source_value_identifier] = $str_source_value;
				
				$arr_source_value_clean[$str_source_value_identifier] = StoreTypeObjects::clearObjectDefinitionText($str_source_value, StoreTypeObjects::TEXT_TAG_OBJECT);
				
				$str_source_value = $this->transliterate($str_source_value);
				$arr_source_value_normalised[$str_source_value_identifier] = $str_source_value;
								
				$arr_source_value_normalised_clean[$str_source_value_identifier] = StoreTypeObjects::clearObjectDefinitionText($str_source_value, StoreTypeObjects::TEXT_TAG_OBJECT);
			}
			
			$has_multi_source_value = (count($arr_result_set['values']) > 1);
			$arr_tests_matches = [];

			foreach ($arr_result_set['objects'] as $key => $arr_result) {
				
				if (isset($arr_result['pattern_pair'])) {
					
					$arr_pattern_pair = $this->arr_pattern_pairs[$arr_result['pattern_pair']];
					$arr_pattern = $arr_pattern_pair['pattern_value'];
																				
					$arr_tests_match = $this->getPatternPairMatches($arr_source_value_clean, $arr_source_value_normalised_clean, $arr_pattern);
				} else {

					$arr_test = $this->arr_tests[$arr_result['object_id']];
					
					if (!$arr_test) { // Could be filtered out
						continue;
					}
					
					$arr_test_statistics = $this->getTestValueStatistics($arr_result['object_id']);
					
					$arr_tests_match = $this->getTestValueMatches($arr_source_value_normalised_clean, $arr_test, $arr_test_statistics);
				}
				
				if (!$arr_tests_match) {
					continue;
				}
				
				foreach ($arr_tests_match as $str_source_value_identifier => $arr_match) {
					$arr_tests_matches[$str_source_value_identifier][$key] = $arr_match;
				}
			}
			
			foreach ($arr_tests_matches as $str_source_value_identifier => $arr_keys_match) {
				
				$arr_keys_match = $this->getMatchesContextualised($arr_keys_match);
				
				if ($mode_result & static::RESULT_MODE_TAG) {
					
					$str_test_value = $arr_source_value_normalised[$str_source_value_identifier];
										
					if ($mode_result & static::RESULT_MODE_TAGS_OVERWRITE_ALL) {
						
						$arr_tags = [];
					} else {

						if ($mode_result & static::RESULT_MODE_TAGS_OVERWRITE_TYPE) {
							
							$arr_type_ids = [$this->type_id => false];
							$str_test_value = StoreTypeObjects::updateObjectDefinitionTextTagsObject($str_test_value, $arr_type_ids);
						}
						
						$arr_tags = $this->getTextTags($str_test_value);
					}
					
					foreach ($arr_keys_match as $key => $arr_match) {
						
						$arr_result =& $arr_result_set['objects'][$key];
						
						$arr_value = $this->getMatchValue($arr_match, $arr_source_value_clean[$str_source_value_identifier], $arr_source_value_normalised_clean[$str_source_value_identifier]);
						
						if ($has_multi_source_value) {
							$arr_result['value'][$str_source_value_identifier] = $arr_value['value'];
							$arr_result['pattern'][$str_source_value_identifier] = $arr_value['pattern'];
							$arr_result['score'][$str_source_value_identifier] = $arr_match['score'];
						} else {
							$arr_result['value'] = $arr_value['value'];
							$arr_result['pattern'] = $arr_value['pattern'];
							$arr_result['score'] = $arr_match['score'];
						}
						
						$str_tag_identifier = $this->type_id.'_'.$arr_result['object_id'];
						
						$arr_tags = $this->addTextTag($arr_tags, $str_tag_identifier, $arr_match['start'], $arr_match['end']);
					}
					unset($arr_result);
					
					$arr_tags = $this->getTextTagsContextualised($arr_tags);
					
					$str_source_value = $this->printTextTags($arr_source_value_clean[$str_source_value_identifier], $arr_tags, $arr_source_value_normalised_clean[$str_source_value_identifier]);
				} else {
					
					foreach ($arr_keys_match as $key => $arr_match) {
						
						$arr_result =& $arr_result_set['objects'][$key];
						
						$arr_value = $this->getMatchValue($arr_match, $arr_source_value_clean[$str_source_value_identifier], $arr_source_value_normalised_clean[$str_source_value_identifier]);
						
						if ($has_multi_source_value) {
							$arr_result['value'][$str_source_value_identifier] = $arr_value['value'];
							$arr_result['pattern'][$str_source_value_identifier] = $arr_value['pattern'];
							$arr_result['score'][$str_source_value_identifier] = $arr_match['score'];
						} else {
							$arr_result['value'] = $arr_value['value'];
							$arr_result['pattern'] = $arr_value['pattern'];
							$arr_result['score'] = $arr_match['score'];
						}
					}
					
					$str_source_value = null; // Nothing parsed to be displayed different
				}
				
				$arr_result_set['values'][$str_source_value_identifier] = $this->restoreSourceValue($arr_result_set['values'][$str_source_value_identifier], $str_source_value);
			}
			
			if ($has_multi_source_value) {
				
				foreach ($arr_result_set['objects'] as $key => &$arr_result) {
					
					if (!isset($arr_result['score'])) {
						
						unset($arr_result_set['objects'][$key]);
						continue;
					}
					
					foreach ($arr_result['score'] as $str_source_value_identifier => &$num_score) {
												
						if (!$num_score) {
							
							unset(
								$arr_result_set['objects'][$key]['value'][$str_source_value_identifier],
								$arr_result_set['objects'][$key]['pattern'][$str_source_value_identifier],
								$arr_result_set['objects'][$key]['score'][$str_source_value_identifier]
							);
							continue;
						}
						
						$num_score = round($num_score, 2);
					}
					
					if (!isset($arr_result['score'])) {
						
						unset($arr_result_set['objects'][$key]);
					}
				}
				unset($arr_result, $num_score);
			} else {
				
				foreach ($arr_result_set['objects'] as $key => &$arr_result) {
					
					$num_score = ($arr_result['score'] ?? null);
					
					if (!$num_score) {
						
						unset($arr_result_set['objects'][$key]);
						continue;
					}
					
					$arr_result['score'] = round($num_score, 2);
				}
				unset($arr_result);
			}
		}
	}
			
	// Find the pattern as early in a text as possible
	
	protected function getPatternPairMatches($arr_source_value, $arr_source_value_normalised, $arr_pattern) {
		
		$arr_tests_match = [];
		
		// Get pattern position, get grapheme position, use to get normalised position.
		
		foreach ($arr_source_value as $str_source_value_identifier => $str_source_value) {
			
			$str_source_value_normalised = $arr_source_value_normalised[$str_source_value_identifier];
			
			$pattern = new PatternEntity($arr_pattern);
			$arr_pattern_match = $pattern->getPatternMatch($str_source_value);
			
			if (!$arr_pattern_match) {
				continue;
			}
			
			$num_pos = $arr_pattern_match['position'];
			$str_pattern = $arr_pattern_match['string'];
			
			$str_pattern = $this->addSourceTransliterationGraphemes($str_pattern);
			$num_match_grapheme_length = grapheme_strlen($str_pattern);
						
			$str_begin = substr($str_source_value, 0, $num_pos);
			$str_begin = $this->addSourceTransliterationGraphemes($str_begin);
			$num_begin_grapheme_length = grapheme_strlen($str_begin);
			
			$str_begin = grapheme_substr($str_source_value_normalised, 0, $num_begin_grapheme_length);
			$num_pos_start = strlen($str_begin);
			
			$str_match = grapheme_substr($str_source_value_normalised, $num_begin_grapheme_length, $num_match_grapheme_length);
			$num_pos_end = ($num_pos_start + strlen($str_match));
		
			$arr_tests_match[$str_source_value_identifier] = ['score' => 100, 'start' => $num_pos_start, 'end' => $num_pos_end, 'is_pattern_pair' => true];
		}
		
		return $arr_tests_match;
	}
	
	// Find the best group of matches, i.e. highest combined score and as early in a text as possible
		
	protected function getTestValueMatches($arr_source_value, $arr_test_value, $arr_test_statistics) {

		$arr_reconcile_matches = [];
		$arr_reconcile_num_score_sum = [];
						
		foreach ($arr_source_value as $str_source_value_identifier => $str_source_value) {
			
			// Collect all possible matches in the normalised source value, and check if they are reasonable
			
			$count_match = 0;
			
			foreach ($arr_test_value as $str_group_identifier => $arr_values) {
				foreach ($arr_values as $arr_value) {
					foreach ($arr_value as $str_value) {
						
						$num_pos_start = stripos($str_source_value, $str_value);
						
						if ($num_pos_start === false) {
							
							$count_match++;
							continue;
						}
						
						$num_length = $arr_test_statistics[$count_match]['length'];
						$num_score = $arr_test_statistics[$count_match]['score'];
						$num_score_highest = 0;

						while ($num_pos_start !== false) {

							$num_pos_end = $num_pos_start + $num_length;

							$str_before = ($num_pos_start > 0 ? ($num_pos_start - 10 > 0 ? substr($str_source_value,  $num_pos_start - 10, 10) : substr($str_source_value, 0, $num_pos_start)) : '');
							$str_after = substr($str_source_value, $num_pos_end, 10);
							$num_lenght_before = 0;
							$num_lenght_after = 0;
							
							if ($str_before === '') { // Begin of string
								$num_boundary_before = 1;
							} else {
								preg_match('/([^\p{P}\s]+)$/', $str_before, $arr_match); // Any other than punctuation or whitespace
								$str_before = ($arr_match[1] ?? null);
								if (!$str_before) {
									$num_boundary_before = 1;
								} else {
									$num_lenght_before = strlen($str_before);
									$num_boundary_before = (($num_lenght_before < 10 && $num_lenght_before < $num_length) ? (1 - ($num_lenght_before / $num_length)) : 0);
								}
							}
							if ($str_after === '') { // End of string
								$num_boundary_after = 1;
							} else {
								preg_match('/^([^\p{P}\s]+)/', $str_after, $arr_match); // Any other than punctuation or whitespace
								$str_after = ($arr_match[1] ?? null);
								if (!$str_after) {
									$num_boundary_after = 1;
								} else {
									$num_lenght_after = strlen($str_after);
									$num_boundary_after = (($num_lenght_after < 10 && $num_lenght_after < $num_length) ? (1 - ($num_lenght_after / $num_length)) : 0);
								}
							}
							
							$num_multiplier = (($num_boundary_before + $num_boundary_after) / 2);

							if (($num_multiplier * 100) >= $this->num_pattern_threshold) {
								
								$num_score_use = $num_score * $num_multiplier;
								$num_pos_start_use = $num_pos_start;
								$num_pos_end_use = $num_pos_end;
								
								if ($this->do_pattern_complete) {
									
									if ($num_lenght_before) {
										$num_pos_start_use -= $num_lenght_before;
									}
									if ($num_lenght_after) {
										$num_pos_end_use += $num_lenght_after;
									}
								}
								
								$arr_reconcile_matches[$str_source_value_identifier][$count_match][] = [$num_pos_start_use, $num_pos_end_use, $num_score_use];
								
								$num_score_highest = ($num_score_use > $num_score_highest ? $num_score_use : $num_score_highest);
							}
							
							$num_pos_start = stripos($str_source_value, $str_value, $num_pos_end);
						}
						
						if ($num_score_highest) {

							$arr_reconcile_num_score_sum[$str_source_value_identifier] += $num_score_highest;
						}
						
						$count_match++;
					}
				}
			}
		}
		
		$arr_tests_match = [];
		
		foreach ($arr_reconcile_matches as $str_source_value_identifier => $arr_matches) {
			
			if (round($arr_reconcile_num_score_sum[$str_source_value_identifier], 2) < $this->num_score_threshold) { // Stop if only a tiny part is found of a value
				continue;
			}
			
			$arr_match = $this->getMatchGrouped($arr_matches);
			
			if (!$arr_match) {
				continue;
			}
			
			$arr_match = $this->getMatchAbsoluteness($arr_match, $arr_source_value[$str_source_value_identifier]);
			
			$arr_tests_match[$str_source_value_identifier] = $arr_match;
		}
				
		return $arr_tests_match;
	}
	
	protected function getMatchValue($arr_match, $str_source_value, $str_source_value_normalised, $do_context = true) {
		
		$arr = [];
		
		$str_source_value = $this->addSourceTransliterationGraphemes($str_source_value);
		
		$num_start = $arr_match['start'];
		$num_end = $arr_match['end'];
		
		$str_begin = substr($str_source_value_normalised, 0, $num_start);
		$num_begin_grapheme_length = grapheme_strlen($str_begin);
				
		$str_match = substr($str_source_value_normalised, $num_start, ($num_end - $num_start));
		$num_match_grapheme_length = grapheme_strlen($str_match);
		
		$str_match = grapheme_substr($str_source_value, $num_begin_grapheme_length, $num_match_grapheme_length);
		$str_match = $this->removeSourceTransliterationGraphemes($str_match);
		
		$arr['value'] = $str_match;
		
		if ($do_context) {
			
			$str_before = '';
			$str_after = '';
			
			if ($num_start > 0) {
				
				$str_before = substr($str_source_value_normalised, ($num_start - 1), 1);
			}
			
			if ($num_end < strlen($str_source_value_normalised)) {
				
				$str_after = substr($str_source_value_normalised, $num_end, 1);
			}
			
			$arr['pattern'] = ['before' => PatternEntity::getTextBoundary($str_before), 'after' => PatternEntity::getTextBoundary($str_after)];
		}
		
		return $arr;
	}
	
	protected function getMatchGrouped($arr_matches) {
			
		$num_tag_pos_start = 0;
		$num_tag_pos_end = 0;
		$num_tag_score = 0;
		$arr_check_matches = $arr_matches;
		
		$str_track_matches = '';

		foreach ($arr_matches as $count_match => $arr_match) {
			foreach ($arr_match as list($num_pos_start, $num_pos_end, $num_score)) {
				
				if ($num_pos_start > $this->num_value_absoluteness_limit) { // Do not track when outside absoluteness consideration
					
					$str_track_matches = null;
				} else if ($str_track_matches !== null) {
					
					if ($num_pos_end > strlen($str_track_matches)) {
						 $str_track_matches = str_pad($str_track_matches, $num_pos_end, '_', STR_PAD_RIGHT);
					}
					
					$str_track_matches = substr_replace($str_track_matches, str_pad('', ($num_pos_end - $num_pos_start), 'x', STR_PAD_RIGHT), $num_pos_start, ($num_pos_end - $num_pos_start));
				}
				
				do {
					
					$do_check = false;
					
					foreach ($arr_check_matches as $count_check => &$arr_check_match) {
						
						if ($count_check == $count_match) {
							continue;
						}
						
						$num_score_use = 0;
			
						foreach ($arr_check_match as $key => list($num_check_pos_start, $num_check_pos_end, $num_check_score)) {
							
							if (
								($num_check_pos_start >= $num_pos_start - $this->num_pattern_distance && $num_check_pos_start <= $num_pos_end + $this->num_pattern_distance) ||
								($num_check_pos_end >= $num_pos_start - $this->num_pattern_distance && $num_check_pos_end <= $num_pos_end + $this->num_pattern_distance) ||
								($num_check_pos_start < $num_pos_start && $num_check_pos_end > $num_pos_end)
							) {
																		
								if ($num_check_pos_start < $num_pos_start) {
									$num_pos_start = $num_check_pos_start;
								}
								if ($num_check_pos_end > $num_pos_end) {
									$num_pos_end = $num_check_pos_end;
								}
								
								unset($arr_check_match[$key]); // This pattern is not needed anymore as it now part of this new pattern
								$num_score_use = ($num_check_score > $num_score_use ? $num_check_score : $num_score_use);
							}
						}
						
						if ($num_score_use) {
							
							$do_check = true;
							$num_score += $num_score_use;
						}
					}
				} while ($do_check);
				
				if ($num_score > $num_tag_score || ($num_score == $num_tag_score && ($num_pos_start < $num_tag_pos_start))) {
					
					$num_tag_pos_start = $num_pos_start;
					$num_tag_pos_end = $num_pos_end;
					$num_tag_score = $num_score;
				}
			}
		}
		
		if (!$num_tag_score || round($num_tag_score, 2) < $this->num_score_threshold) {
			return false;
		}
		
		$num_length = null;
		
		if ($str_track_matches !== null) {
			
			$str_track_matches = str_replace('_', '', $str_track_matches); // Keep all 'x'
			$num_length = strlen($str_track_matches);
		}
		
		return ['score' => $num_tag_score, 'start' => $num_tag_pos_start, 'end' => $num_tag_pos_end, 'absolute_length' => $num_length];
	}
	
	protected function getMatchAbsoluteness($arr_match, $str_source_value) {
		
		if ($arr_match['absolute_length'] === null) {
			return $arr_match;
		}
		
		$num_source_length = strlen($str_source_value);
		
		if ($arr_match['absolute_length'] > ($num_source_length * 0.5)) { // If source value is matched more than half, concentrate on source value
			
			$str_source_value = $this->formatTestValue($str_source_value); // Cleanup source value comparable to parsed test value
			$num_source_length = strlen($str_source_value);
			
			$num_score = ($arr_match['absolute_length'] / $num_source_length) * 100;
			
			if ($num_score > $arr_match['score']) {
				$arr_match['score'] = $num_score;
			}
		}
		
		return $arr_match;
	}
	
	protected function getMatchesContextualised($arr_matches) {
		
		$arr_check_matches = $arr_matches;
		
		foreach ($arr_matches as $key => $arr_match) {
			
			$keep_key = true;
			
			$num_start = $arr_match['start'];
			$num_end = $arr_match['end'];
			$is_pattern_pair = isset($arr_match['is_pattern_pair']);
			
			foreach ($arr_check_matches as $check_key => $arr_check_match) {
				
				if ($key == $key_check) {
					continue;
				}
				
				$keep_check_key = true;
				
				$num_check_start = $arr_check_match['start'];
				$num_check_end = $arr_check_match['end'];
				
				if ($num_check_start == $num_start && $num_check_end == $num_end) {
					
					$is_check_pattern_pair = isset($arr_check_match['is_pattern_pair']);

					if ($this->do_prioritise_match_pattern_pair && ($is_pattern_pair || $is_check_pattern_pair)) {
						
						if (!$is_pattern_pair) {
							$keep_key = false;
						} else if (!$is_check_pattern_pair) {
							$keep_check_key = false;
						}
					} else if (round(abs($arr_check_match['score'] - $arr_match['score']), 2) > $this->num_score_match_difference) {
						
						if ($arr_check_match['score'] > $arr_match['score']) {
							$keep_key = false;
						} else {
							$keep_check_key = false;
						}
					}
				} else if (
					($num_check_start >= $num_start && $num_check_start <= $num_end) ||
					($num_check_end >= $num_start && $num_check_end <= $num_end) ||
					($num_check_start < $num_start && $num_check_end > $num_end)
				) {
					
					if (round(abs($arr_check_match['score'] - $arr_match['score']), 2) > $this->num_score_overlap_difference) {
						
						if ($arr_check_match['score'] > $arr_match['score']) {
							$keep_key = false;
						} else {
							$keep_check_key = false;
						}
						
						continue;
					}
				}
				
				if (!$keep_check_key) {
					unset($arr_matches[$check_key], $arr_check_matches[$check_key]);
				} else if (!$keep_key) {
					unset($arr_matches[$key], $arr_check_matches[$key]);
					break;
				}
			}
		}
		
		return $arr_matches;
	}
			
	public function getTextTagsContextualised(&$arr_tags) {
				
		ksort($arr_tags);
		
		$arr_check_tags = $arr_tags;
		
		foreach ($arr_tags as $num_pos_open => $arr_tag_open_close) {
			
			if (!isset($arr_tag_open_close['open'])) { // Needs an open-statement for this position to be an open
				continue;
			}
			
			foreach ($arr_tag_open_close['open'] as $num_pos_close => $arr_tag_close) {
				
				foreach ($arr_check_tags as $num_check_pos_open => $arr_check_tag_open_close) {
					
					if ($num_check_pos_open == $num_pos_open || $num_check_pos_open > $num_pos_close) {
						break;
					}
					
					if (!isset($arr_check_tag_open_close['open'])) { // Needs an open-statement for this position to be an open
						continue;
					}
			
					foreach ($arr_check_tag_open_close['open'] as $num_check_pos_close => $arr_check_tag_close) { // Needs an open-statement for this position to be an open
						
						if (
							($num_check_pos_close <= $num_pos_open) ||
							($num_check_pos_open <= $num_pos_open && $num_check_pos_close >= $num_pos_close) ||
							($num_check_pos_open >= $num_pos_open && $num_check_pos_close <= $num_pos_close)
						) {
							continue;
						}
						
						// complicated overlap
						
						$arr_tags[$num_pos_close]['close'][$num_pos_open]['scrambled'] = true;
						$arr_tags[$num_pos_open]['open'][$num_pos_close]['scrambled'] = true;
					}
				}
			}
		}
		
		return $arr_tags;
	}
	
	public function getTextTags($str_text) {
		
		if (strpos($str_text, '[object=') === false) {
			return [];
		}
			
		$arr_tags_collect = [];
		$num_pos_start = strpos($str_text, '[object=');
		
		while ($num_pos_start !== false) {
			
			$num_pos_end = strpos($str_text, ']', ($num_pos_start + 8));
			$num_length = ($num_pos_end - ($num_pos_start + 8));
			
			$str_tag_identifiers = substr($str_text, ($num_pos_start + 8), $num_length);
			$arr_tags_collect[$num_pos_start]['open'] = [($num_length + 8 + 1), $str_tag_identifiers, null]; // Position, identifiers, scrambled

			$num_pos_start = strpos($str_text, '[object=', ($num_pos_end + 1));
		}
		
		$num_pos_start = strpos($str_text, '[/object]');
		
		while ($num_pos_start !== false) {
							
			$arr_tags_collect[$num_pos_start]['close'] = [9, false, false];

			$num_pos_start = strpos($str_text, '[/object]', $num_pos_start + 9);
		}
		
		$num_pos_start = strpos($str_text, '[/object=');
		
		while ($num_pos_start !== false) {
			
			$num_pos_end = strpos($str_text, ']', ($num_pos_start + 9));
			$num_length = ($num_pos_end - ($num_pos_start + 9));
			
			$str_tag_identifiers = substr($str_text, ($num_pos_start + 9), $num_length);	
			$arr_tags_collect[$num_pos_start]['close'] = [($num_length + 9 + 1), $str_tag_identifiers, true]; // Position, identifiers, scrambled;

			$num_pos_start = strpos($str_text, '[/object=', ($num_pos_end + 1));
		}
		
		ksort($arr_tags_collect);
		$arr_tags_open = [];
		$arr_tags = [];
		$num_pos_offset = 0;
		
		foreach ($arr_tags_collect as $num_pos_start => $arr_open_close) {
			
			$num_pos = $num_pos_start - $num_pos_offset; // Use tag positions without the actual tags themselves, and possibly combine tags that have the same tag start/end
			
			if ($arr_open_close['open']) {
				
				$arr_tag_open = $arr_open_close['open'];
				
				$num_pos_offset += $arr_tag_open[0];
				
				$arr_tags_open[] = [$num_pos, $arr_tag_open[1], $arr_tag_open[2]];
			} else {
				
				$arr_tag_close = $arr_open_close['close'];
				
				$num_pos_offset += $arr_tag_close[0];
				$is_scrambled = $arr_tag_close[2];
				
				if ($is_scrambled) {
					
					$arr_tag_open = false;
					
					foreach ($arr_tags_open as $key => $arr_tag_open_test) {

						if ($arr_tag_open_test[1] != $arr_tag_close[1]) { // Compare tag identifiers
							continue;
						}
						
						$arr_tag_open = $arr_tag_open_test;
						unset($arr_tags_open[$key]);
						
						break;
					}
				} else {
					
					$arr_tag_open = array_pop($arr_tags_open);
				}
				
				$num_pos_open = $arr_tag_open[0];
				$arr_tag_identifiers = explode('|', $arr_tag_open[1]);
				
				$s_arr =& $arr_tags[$num_pos]['close'][$num_pos_open];
				$arr_identifiers = ($s_arr['identifiers'] ? array_merge($s_arr['identifiers'], $arr_tag_identifiers) : $arr_tag_identifiers);
				$s_arr['identifiers'] = $arr_identifiers;
				if ($is_scrambled) {
					$s_arr['scrambled'] = true;
				}

				$s_arr =& $arr_tags[$num_pos_open]['open'][$num_pos];
				$s_arr['identifiers'] = $arr_identifiers;
				if ($is_scrambled) {
					$s_arr['scrambled'] = true;
				}
			}
		}
		
		return $arr_tags;
	}
	
	public function addTextTag(&$arr_tags, $str_tag_identifier, $num_start, $num_end) {
		
		$s_arr =& $arr_tags[$num_start]['open'][$num_end];	
		$s_arr['identifiers'][] = $str_tag_identifier;

		$s_arr =& $arr_tags[$num_end]['close'][$num_start];
		$s_arr['identifiers'][] = $str_tag_identifier;
		
		return $arr_tags;
	}
	
	public function printTextTags($str_text, $arr_tags, $str_text_normalised = null) {
		
		ksort($arr_tags);
		
		if ($str_text_normalised !== null) {
			$str_text = $this->addSourceTransliterationGraphemes($str_text);
		}
		
		$num_pos_offset = 0;
		
		foreach ($arr_tags as $num_pos => $arr_open_close) {
			
			if ($arr_open_close['close']) {
				
				if ($str_text_normalised !== null) {
					
					$str_begin = substr($str_text_normalised, 0, $num_pos);
					$num_grapheme_length = (grapheme_strlen($str_begin) + $num_pos_offset);
				
					$str_begin = grapheme_substr($str_text, 0, $num_grapheme_length);
					$str_end = grapheme_substr($str_text, $num_grapheme_length);
				} else {
					
					$str_begin = substr($str_text, 0, $num_pos + $num_pos_offset);
					$str_end = substr($str_text, $num_pos + $num_pos_offset);
				}
				
				$str_value = '';
				
				krsort($arr_open_close['close']); // Close tags with nearest (= highest num_pos_open) open tags first
				
				foreach ($arr_open_close['close'] as $num_pos_open => $arr_tag) {
					
					if ($arr_tag['scrambled']) {
						$str_value .= '[/object='.implode('|', array_unique($arr_tag['identifiers'])).']';
					} else {
						$str_value .= '[/object]';
					}
				}
				
				$str_text = $str_begin.$str_value.$str_end;
				$num_pos_offset += strlen($str_value);
			}
			
			if ($arr_open_close['open']) {
				
				krsort($arr_open_close['open']); // Open tags with fartherst closing tags first
				
				foreach ($arr_open_close['open'] as $num_pos_close => $arr_tag) {
					
					if ($str_text_normalised !== null) {
						
						$str_begin = substr($str_text_normalised, 0, $num_pos);
						$num_grapheme_length = (grapheme_strlen($str_begin) + $num_pos_offset);
					
						$str_begin = grapheme_substr($str_text, 0, $num_grapheme_length);
						$str_end = grapheme_substr($str_text, $num_grapheme_length);
					} else {
						
						$str_begin = substr($str_text, 0, $num_pos + $num_pos_offset);					
						$str_end = substr($str_text, $num_pos + $num_pos_offset);
					}
					
					$str_value = '[object='.implode('|', array_unique($arr_tag['identifiers'])).']';
					
					$str_text = $str_begin.$str_value.$str_end;
					$num_pos_offset += strlen($str_value);
				}				
			}
		}
		
		if ($str_text_normalised !== null) {
			$str_text = $this->removeSourceTransliterationGraphemes($str_text);
		}
		
		return $str_text;
	}
	
	protected function transliterate($str) {
		
		return $this->transliterator->transliterate($str);
	}
	
	protected function addSourceTransliterationGraphemes($str_source) {
		
		if ($str_source === static::$str_source_transliteration_compatible) {
			return static::$str_source_transliteration_compatible_processed;
		}
		
		static::$str_source_transliteration_compatible = $str_source;
		
		foreach (static::TRANSLITERATION_RULE_GRAPHEME_LIGATURE as $str_char) {
			$str_source = str_replace($str_char, $str_char.$str_char, $str_source);
		}
		
		static::$str_source_transliteration_compatible_processed = $str_source;
		
		return $str_source;
	}
	
	protected function removeSourceTransliterationGraphemes($str_source) {
		
		foreach (static::TRANSLITERATION_RULE_GRAPHEME_LIGATURE as $str_char) {
			$str_source = str_replace($str_char.$str_char, $str_char, $str_source);
		}
		
		return $str_source;
	}
}
