<?php
		
	define('DATABASE_NODEGOAT_CONTENT', 'nodegoat_content');
	define('DATABASE_NODEGOAT_TEMP', 'nodegoat_temp');
	DB::$database_home = 'nodegoat_home';
	DB::$database_cms = 'nodegoat_cms';

	DB::setConnectionDetails('localhost', '1100CC_home', '*PASSWORD*', DB::CONNECT_HOME);
	DB::setConnectionDetails('localhost', '1100CC_cms', '*PASSWORD*', DB::CONNECT_CMS);
	
	Settings::set('graph_analysis_service', [
		'name' => '1100CC',
		'host' => 'service',
		'token' => 'none'
	]);
