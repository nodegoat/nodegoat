<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

// 1100CC Framework:

	$arr['js'] = [
		DIR_JS.'home.js',
		DIR_JS.'MapManager.js',
		DIR_JS.'MapUtilities.js',
		DIR_JS.'MapTimeline.js',
		DIR_JS.'MapSocial.js',
		DIR_JS.'MapData.js',
		DIR_JS.'MapGeo.js',
		DIR_JS.'MapGeoUtilities.js',
		DIR_JS.'MapDrawPoints.js',
		DIR_JS.'MapNetworkMetrics.js'
	];
	$arr['css'] = [
		DIR_CSS.'home.css',
		DIR_CSS.'nodegoat.css',
		DIR_CSS.'labmap.css'
	];
	
	$arr_core['js'] = [
		DIR_CMS.DIR_JS.'support/jquery.minicolors.js',
		DIR_CMS.DIR_JS.'support/DeepMerge.js',
		DIR_JS.'MapScroller.js'
	];
	
	$arr_core['css'] = [
		DIR_CMS.DIR_CSS.'support/jquery.minicolors.css',
		DIR_CSS.'MapScroller.css'
	];
