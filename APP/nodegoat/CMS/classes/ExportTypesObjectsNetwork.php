<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

abstract class ExportTypesObjectsNetwork {
	
	protected $type_id = false;
	protected $arr_options = [];
	protected $arr_type_network_types = [];
	
	protected $arr_filters = [];
	protected $class_collect = false;

    protected static $num_objects_stream = 5000;
    
    public function __construct($type_id, $arr_type_network_types, $arr_options = []) {
		
		$this->type_id = $type_id;
		$this->arr_options = $arr_options;
		
		$this->arr_type_network_types = $arr_type_network_types;
    }
		
    public function init($collect, $arr_filters = []) {
		
		$this->class_collect = $collect;
		$this->arr_filters = $arr_filters;
	}
		
	abstract public function createPackage($arr_options_file);
	
	abstract public function readPackage($str_filename);
	
	public static function getCollectorSettings() {
	
		return [
			'conditions' => true
		];
	}
	
	public static function getExportFormatTypes() {
		
		return [
			'csv' => ['id' => 'csv', 'name' => 'CSV (Comma Separated Values)'],
			'odt' => ['id' => 'odt', 'name' => 'ODT (OpenDocument Text)']
		];
	}
}
