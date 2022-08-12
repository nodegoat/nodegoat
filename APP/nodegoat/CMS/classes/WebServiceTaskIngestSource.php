<?php

/**
 * 1100CC - web application framework.
 * Copyright (C) 2022 LAB1100.
 *
 * See http://lab1100.com/1100cc/release for the latest version of 1100CC and its license.
 */

class WebServiceTaskIngestSource Extends WebServiceTask {
	
	public static $name = 'ingest_source';

	protected static $conversion_passkey = false;
	protected static $stmt_has_conversions = false;
	protected static $stmt_add_conversion = false;
	protected static $stmt_get_conversion = false;
	protected static $stmt_claim_conversion = false;
	protected static $stmt_add_conversion_result = false;
	protected static $stmt_get_conversion_result = false;
	protected static $stmt_delete_conversion = false;
	
	protected $timeout_claimed = 5; // Seconds
	
    public function check() {
		
		return ($this->arr_passkeys_data_input || WebServiceUser::hasTaskOwnerUsers(static::$name));
	}
	
	// User-Server data
	
	public function setData($arr_conversions) {

		foreach ($arr_conversions as $arr_conversion) {
		
			if ($arr_conversion['delete']) {
			
				unset($this->user->arr_data[$arr_conversion['identifier']]);
				continue;
			}
			
			$this->user->arr_data[$arr_conversion['identifier']] = $arr_conversion;
		}
	}
	
	public function getData() {
		
		if (!$this->user->arr_data) {
			return false;
		}
		
		$arr = [];
		
		foreach ($this->user->arr_data as $str_identifier => $arr_conversion) {
			
			if (!$arr_conversion['done']) {
				continue;
			}
			
			$arr[$str_identifier] = $arr_conversion;
			unset($this->user->arr_data[$str_identifier]);
		}
		
		return $arr;
	}
	
	// User-Client data
		    
    protected function processUserData() {
		
		$user_owner = WebServiceUser::getTaskOwnerUserByPasskey(static::$name, $this->user->passkey);
		
		if (!$user_owner) {
			return false;
		}
		
		// Input
		
		if ($this->arr_passkeys_data_output[$this->user->passkey]) {
			return $this->arr_passkeys_data_output[$this->user->passkey];
		}
		
		if ($this->arr_passkeys_data_input[$this->user->passkey]) {
			
			foreach ($this->arr_passkeys_data_input[$this->user->passkey] as $arr_input) {
				
				$arr_conversion = ($user_owner->arr_data[$arr_input['identifier']] ?? null);
				
				if ($arr_conversion === null || $arr_conversion['done']) {
					continue;
				}
				
				$arr_conversion = &$user_owner->arr_data[$arr_input['identifier']];
				
				$arr_conversion['output'] = $arr_input['output'];
				$arr_conversion['done'] = true;
				
				unset($arr_conversion);
			}
		}
		
		// Output
		
		$arr = [];
		$time_now = microtime(true);
							
		foreach ($user_owner->arr_data as $str_identifier => $arr_conversion) {
		
			if (isset($arr_conversion['claimed'])) {
				
				if (($time_now - $arr_conversion['claimed']) < $this->timeout_claimed) { // Release claimed after x seconds
					continue;
				}
				
				unset($arr_conversion['claimed']);
			}
			
			$user_owner->arr_data[$str_identifier]['claimed'] = $time_now;
			
			$arr[] = $arr_conversion;
		}

		return $arr;
    }
}
