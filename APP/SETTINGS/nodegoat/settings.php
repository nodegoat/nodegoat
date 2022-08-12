<?php
		
	define('DATABASE_NODEGOAT_CONTENT', 'nodegoat_content');
	define('DATABASE_NODEGOAT_TEMP', 'nodegoat_temp');
	DB::$database_cms = 'nodegoat_cms';
	DB::$database_home = 'nodegoat_home';
	
	DB::setConnectionDetails('localhost', '1100CC_cms', './database_cms.pass', DB::CONNECT_CMS);
	DB::setConnectionDetails('localhost', '1100CC_home', './database_home.pass', DB::CONNECT_HOME);
	
	Settings::set('graph_analysis_service', [
		'name' => '1100CC',
		'host' => 'service',
		'token' => 'none'
	]);
