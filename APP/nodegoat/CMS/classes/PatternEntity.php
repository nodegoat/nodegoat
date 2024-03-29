<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class PatternEntity {
	
	const PATTERN_COMPOSITION_MATCH = 0;
	const PATTERN_COMPOSITION_MAP = 1;
	const PATTERN_COMPOSITION_MATCH_CONTEXT = 2;
		
	const MATCH_BOUNDARY_VOID = 1;
	const MATCH_BOUNDARY_CHARACTER = 2;
	
	protected $arr_pattern = [];
	protected $num_composition = 0;
	
	protected static $arr_pattern_composition_levels = [];
	protected static $arr_text_boundary_options = [];
	
	public function __construct($arr_pattern, $num_composition = null) {

		$this->arr_pattern = $arr_pattern;
		
		if ($num_composition === null) {
			$this->num_composition = $this->getPatternComposition();
		} else {
			$this->num_composition = (int)$num_composition;
		}
	}
		
	public static function createPattern($str_value, $arr_context = false) {
		
		$arr_pattern = [];
		
		if ($arr_context && (!empty($arr_context['before']) || !empty($arr_context['after']))) {
			
			$arr_pattern = ['value' => $str_value, 'context' => $arr_context];
		} else {
			
			if (is_array($str_value)) {
				
				$arr_pattern = $str_value;
			} else {
				
				$arr_pattern[] = $str_value;
			}
		}
		
		return $arr_pattern;
	}
	
	public function getPatternComposition() {
		
		$num_composition = static::PATTERN_COMPOSITION_MATCH;

		if (isset($this->arr_pattern['context'])) {
			$num_composition = static::PATTERN_COMPOSITION_MATCH_CONTEXT;
		} else if (!(count($this->arr_pattern) == 1 && key($this->arr_pattern) === 0)) { // Is a pattern with a map when not single value array
			$num_composition = static::PATTERN_COMPOSITION_MAP;
		} 
		
		return $num_composition;
	}
	
	public function getPatternSummary() {
				
		$str_name = '';
		
		if (!empty($this->arr_pattern)) {
			
			if ($this->num_composition == static::PATTERN_COMPOSITION_MATCH_CONTEXT) {
				$arr_values = (array)$this->arr_pattern['value'];
			} else {
				$arr_values = $this->arr_pattern;
			}
						
			foreach ($arr_values as $str_pattern_value) {
			
				$str_name .= ($str_name !== '' ? ' ,' : '').(!$str_pattern_value ? '[no value]' : $str_pattern_value);
			}
		}
		
		return ['name' => $str_name];
	}
	
	public function getPatternMatch($str) {
				
		$has_context = ($this->num_composition == static::PATTERN_COMPOSITION_MATCH_CONTEXT);
		$arr_values = ($has_context ? (array)$this->arr_pattern['value'] : $this->arr_pattern);
		
		foreach ($arr_values as $str_pattern) {
			
			$arr_match = $this->getPatternMatchValue($str_pattern, ($has_context ? $this->arr_pattern['context'] : false), $str);
			
			if ($arr_match !== false) {
				return $arr_match;
			}
		}
		
		return null;
	}
		
	public function getPatternMatches($str) {
				
		$has_context = ($this->num_composition == static::PATTERN_COMPOSITION_MATCH_CONTEXT);
		$arr_values = ($has_context ? (array)$this->arr_pattern['value'] : $this->arr_pattern);
		
		$arr = [];
		
		foreach ($arr_values as $str_pattern) {
			
			$arr_match = $this->getPatternMatchValue($str_pattern, ($has_context ? $this->arr_pattern['context'] : false), $str);
			
			while ($arr_match !== false) {
								
				$arr[] = $arr_match;
				
				$num_offset = ($arr_match['position'] + strlen($arr_match['string']));
				
				$arr_match = $this->getPatternMatchValue($str_pattern, ($has_context ? $this->arr_pattern['context'] : false), $str, $num_offset);
			}
		}
		
		return $arr;
	}
		
	public function getPatternMatchValue($str_pattern, $arr_context, $str, $num_offset = 0) {
		
		if (isset($arr_context['before'])) {
			
			$str_regex = '';
			
			if ($arr_context['before'] == static::MATCH_BOUNDARY_VOID) {
				$str_regex .= '(?<=^|[\p{P}\s])';
			}
			$str_regex .= '('.preg_quote($str_pattern, '/').')';
			if ($arr_context['after'] == static::MATCH_BOUNDARY_VOID) {
				$str_regex .= '(?=[\p{P}\s]|$)';
			}
			
			$has_match = preg_match('/'.$str_regex.'/', $str, $arr_match, PREG_OFFSET_CAPTURE, $num_offset);
			
			if ($has_match) {
				
				$arr_matched = $arr_match[1];
				
				return ['position' => $arr_matched[1], 'string' => $arr_matched[0]];
			}
		} else {
		
			$num_pos = strpos($str, $str_pattern, $num_offset);
			
			if ($num_pos !== false) {
				
				return ['position' => $num_pos, 'string' => $str_pattern];
			}
		}
		
		return false;
	}
	
	public static function getTextBoundary($str) {
		
		$boundary = null;
		
		if (!$str || preg_match('/([\p{P}\s])/', $str)) { // Any punctuation or whitespace
			
			$boundary = static::MATCH_BOUNDARY_VOID;
		} else {
			
			$boundary = static::MATCH_BOUNDARY_CHARACTER;
		}
		
		return $boundary;
	}
	
	public static function getPatternCompositionLevels() {
		
		if (static::$arr_pattern_composition_levels) {
			return static::$arr_pattern_composition_levels;
		}
		
		static::$arr_pattern_composition_levels = [
			static::PATTERN_COMPOSITION_MATCH => ['id' => static::PATTERN_COMPOSITION_MATCH, 'name' => getLabel('lbl_pattern_composition_match')],
			static::PATTERN_COMPOSITION_MAP => ['id' => static::PATTERN_COMPOSITION_MAP, 'name' => getLabel('lbl_pattern_composition_map')],
			static::PATTERN_COMPOSITION_MATCH_CONTEXT => ['id' => static::PATTERN_COMPOSITION_MATCH_CONTEXT, 'name' => getLabel('lbl_pattern_composition_match_context')]
		];
		
		return static::$arr_pattern_composition_levels;
	}
	
	public static function getTextBoundaryOptions() {
		
		if (static::$arr_text_boundary_options) {
			return static::$arr_text_boundary_options;
		}
		
		static::$arr_text_boundary_options = [
			static::MATCH_BOUNDARY_VOID => ['id' => static::MATCH_BOUNDARY_VOID, 'name' => getLabel('lbl_pattern_text_boundary_void')],
			static::MATCH_BOUNDARY_CHARACTER => ['id' => static::MATCH_BOUNDARY_CHARACTER, 'name' => getLabel('lbl_pattern_text_boundary_character')]
		];
		
		return static::$arr_text_boundary_options;
	}
}
