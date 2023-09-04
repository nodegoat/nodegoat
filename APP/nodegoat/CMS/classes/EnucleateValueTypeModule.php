<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class EnucleateValueTypeModule {
	
	const VIEW_HTML = 1;
	const VIEW_TEXT = 2;
	
	protected static $arr_modules = [ // Add all possible modules to this list
		'music_notation' => true
	];
	
	public static function enable($type) {
		
		static::$arr_modules[$type] = true;
	}
	public static function disable($type) {
		
		static::$arr_modules[$type] = false;
	}
	
	public static function getClassName($type) {
		
		$str_class = get_class().str_replace('_', '', ucwords($type, '_'));
		
		return $str_class;
	}
		
	public static function init($type) {
		
		$is_active = (static::$arr_modules[$type] ?? null);
		
		if (!$is_active) {
			error(getLabel('msg_object_description_value_type_missing'));
		}
		
		$str_class = static::getClassName($type);
		
		$class = new $str_class;
		
		return $class;
	}
		
	public static function iterateModules() {
				
		foreach (static::$arr_modules as $type => $is_active) {
			
			if (!$is_active) {
				continue;
			}
			
			$str_class = static::getClassName($type);
			
			yield $type => $str_class;
		}
	}
	
	public static function getModules() {
		
		$arr = [];
		
		foreach (static::iterateModules() as $type => $str_class) {
						
			$arr[$type] = ['id' => $type, 'name' => $str_class::getName()];
		}
		
		return $arr;
	}
	
	public static function getModulesStyles() {
		
		$str = '';
		
		foreach (static::iterateModules() as $type => $str_class) {
			
			$str .= $str_class::getStyle();
		}
		
		return $str;
	}
	
	public static function getModulesScripts() {
		
		$str = '';
		
		foreach (static::iterateModules() as $type => $str_class) {
			
			$str .= $str_class::getScript();
		}
		
		return $str;
	}
}
