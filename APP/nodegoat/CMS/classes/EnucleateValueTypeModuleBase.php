<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

abstract class EnucleateValueTypeModuleBase extends EnucleateValueTypeModule {
	
	protected static $str_type;
	
	protected $arr_value = [];
	protected $arr_settings = [];
	
	protected $str_template_name = '';
	protected $arr_template_validate = [];
	
	const STYLE_CLASS_ELEMENT = '.value-type-module';
	const SCRIPT_ELEMENT = 'elm_scripter';
		
    public function __construct($arr_value = [], $arr_settings = []) {
		
		$this->setValue($arr_value);
		$this->setConfiguration($arr_settings);
	}
	
	public function setValue($arr_value) {
		
		$this->arr_value = $arr_value;
	}
	public function setConfiguration($arr_settings) {
		
		$this->arr_settings = $arr_settings;
	}
	
	public function createTemplate($str_template_name) {
		
		$this->str_template_name = $str_template_name;
		
		$html_template = $this->createModuleTemplate();
				
		$return = '<div class="value-type-module '.static::$str_type.' template" data-form_name="'.$this->str_template_name.'">
		
			<div class="options">
				 '.$html_template.'
			</div>
			
		</div>';
		
		return $return;
	}
	
	abstract protected function createModuleTemplate();
	
	public function getTemplateValidate() {
				
		return $this->arr_template_validate;
	}
	
	public function parseTemplate() {
				
		$arr_value = $this->parseModuleTemplate();
		
		return $arr_value;
	}
	
	abstract protected function parseModuleTemplate();
	
	public function enucleate($mode = parent::VIEW_HTML) {
		
		$return = $this->enucleateModule($mode);
		
		return $return;
	}
	
	abstract protected function enucleateModule($mode);
	
	public static function getValueFields() {
		
		$arr = [
			'any' => ['name' => getLabel('lbl_any'), 'path' => '$', 'type' => '']
		];
		
		$arr += static::getModuleValueFields();
		
		return $arr;
	}
	
	abstract protected static function getModuleValueFields();
	
	public static function getName() {
		
		return getLabel('lbl_object_description_value_type_'.static::$str_type);
	}
	
	public static function getStyle() {
		
		return static::getModuleStyle();
	}
	
	protected static function getModuleStyle() {
		return '';
	}
	
	public static function getScript() {
		
		$str_script = "";
		
		$str_script_template = static::getModuleScriptTemplate();
		
		if ($str_script_template) {
			
			$str_script .= "
				SCRIPTER.dynamic('.value-type-module.".static::$str_type.".template', function(".static::SCRIPT_ELEMENT.") {
					".$str_script_template."
				});
			";
		}
		
		$str_script_enucleate = static::getModuleScriptEnucleate();
		
		if ($str_script_enucleate) {
			
			$str_script .= "
				SCRIPTER.dynamic('.value-type-module.".static::$str_type.".enucleate', function(".static::SCRIPT_ELEMENT.") {
					".$str_script_enucleate."
				});
			";
		}
				
		return $str_script;
	}
	
	protected static function getModuleScriptTemplate() {
		return '';
	}
	
	protected static function getModuleScriptEnucleate() {
		return '';
	}
}
