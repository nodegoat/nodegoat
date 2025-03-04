<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class StoreTypeObjectsExtensions extends StoreTypeObjects {
	
	protected $arr_sql_insert_non_version = [];
		
	protected $stmt_object_id = false;
		
    public function __construct($type_id, $object_id, $arr_owner) {
	
		$this->type_id = $type_id;
		
		if (is_array($arr_owner)) {
			$this->user_id = (int)$arr_owner['user_id'];
			$this->system_object_id = ($arr_owner['system_object_id'] ? (int)$arr_owner['system_object_id'] : null);
		} else {
			$this->user_id = (int)$arr_owner;
		}
		
		
		if ($object_id) {
			$this->setObjectID($object_id);
		}
    }

    public function setObjectID($object_id, $do_verify = true) {
		
		$object_id = (int)$object_id;
		
		if (!$do_verify) {
			
			$this->object_id = $object_id;
			return true;
		}
		
		if (!$this->stmt_object_id) {
			
			$this->stmt_object_id = DB::prepare("SELECT TRUE
					FROM ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECTS')." nodegoat_to
				WHERE nodegoat_to.id = ".DBStatement::assign('object_id', 'i')." AND nodegoat_to.type_id = ".$this->type_id."
				LIMIT 1
			");
		}
		
		$this->stmt_object_id->bindParameters(['object_id' => $object_id]);
		$res = $this->stmt_object_id->execute();
		
		if (!$res->getRowCount()) {
			error('Not a valid Object ID');
		}
		
		$this->object_id = $object_id;
		
		return true;
	}
	
	public function resetTypeObjectAnalysis($user_id, $analysis_id) {
		
		DB::query("
			UPDATE ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')."
					SET state = 0
				WHERE user_id = ".(int)$user_id." AND analysis_id = ".(int)$analysis_id." AND state = 1
		");
	}
	
	public function updateTypeObjectAnalysis($user_id, $analysis_id) {
		
		DB::query("
			INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSIS_STATUS')."
				(user_id, analysis_id, date)
					VALUES
				(".(int)$user_id.", ".(int)$analysis_id.", ".DBFunctions::timeNow().")
			".DBFunctions::onConflict('user_id, analysis_id', ['date'])."
		");
	}
	
	public function addTypeObjectAnalysis($user_id, $analysis_id, $number = false, $number_secondary = false) {
		
		$this->arr_sql_insert_non_version['object_analysis'][] = '('.(int)$user_id.', '.(int)$analysis_id.', '.(int)$this->object_id.', '.(float)$number.', '.($number_secondary ? (float)$number_secondary : 'NULL').', 1)';
	}
	
	public function save() {
		
		$arr_sql_query = [];
		
		foreach ($this->arr_sql_insert_non_version as $task => $arr_sql_insert) {
			
			switch ($task) {
				case 'object_analysis':
				
					$arr_sql_query[] = "INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_ANALYSES')."
						(user_id, analysis_id, object_id, number, number_secondary, state)
							VALUES
						".implode(',', $arr_sql_insert)."
						".DBFunctions::onConflict('user_id, analysis_id, object_id', ['number', 'number_secondary', 'state'])."
					";
					break;
			}
		}
		
		if ($arr_sql_query) {
			
			$res = DB::queryMulti(
				implode(';', $arr_sql_query)
			);
		}
		
		$this->arr_sql_insert_non_version = [];
	}
}
