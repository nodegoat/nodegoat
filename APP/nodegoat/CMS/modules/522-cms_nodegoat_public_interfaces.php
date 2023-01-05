<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACES', DB::$database_home.'.def_nodegoat_public_interfaces');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS', DB::$database_home.'.def_nodegoat_public_interface_projects');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS', DB::$database_home.'.def_nodegoat_public_interface_texts');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES', DB::$database_home.'.def_nodegoat_public_interface_project_types');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS', DB::$database_home.'.def_nodegoat_public_interface_project_scenarios');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS', DB::$database_home.'.data_nodegoat_public_interface_selections');
DB::setTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS', DB::$database_home.'.data_nodegoat_public_interface_selection_elements');

class cms_nodegoat_public_interfaces extends base_module {

	public static function moduleProperties() {
		static::$label = false;
		static::$parent_label = false;
	}
	
	public static function jobProperties() {
		return [
			'cleanupPublicInterfaceSelections' => [
				'label' => 'nodegoat '.getLabel('lbl_public_interface_cleanup_selections'),
				'options' => function($options) {
					$arr_units = [
						['id' => 1440, 'name' => getLabel('unit_day')],
						['id' => 10080, 'name' => getLabel('unit_week')],
						['id' => 40320, 'name' => getLabel('unit_month')]
					];
					return '<label>'.getLabel('lbl_age').'</label><input type="text" name="options[age_amount]" value="'.$options['age_amount'].'" /><select name="options[age_unit]">'.cms_general::createDropdown($arr_units, $options['age_unit']).'</select>';
				}
			]
		];
	}
	
	public static function handlePublicInterface($public_interface_id, $arr, $arr_texts) {
		
		if (!empty($arr['settings'])) {

			$arr_types_cite_as = [];

			foreach ((array)$arr['settings']['cite_as'] as $type_id => $arr_type_cite_as) {
				
				foreach ((array)$arr_type_cite_as as $option => $arr_values) {

					foreach ($arr_values as $key => $value) {
						
						if ($option == 'string' && $value) {
							$value = strEscapeHTML($value);
						}
						
						$arr_types_cite_as[$type_id][$key][$option] = $value;
					}
				}
			}
			
			foreach ((array)$arr['settings']['projects'] as $project_id => $arr_project) {
				
				$arr_type_scopes = [];
				
				foreach ((array)$arr_project['scope'] as $scope => $arr_scopes) {
				
					foreach ((array)$arr_scopes as $arr_scope) {
						
						$type_id = $arr_scope['type_id'];
						
						if (!$type_id || !in_array($type_id, $arr['project_types'][$project_id]['types']) || !$arr_scope['scope_id']) {
							continue;
						}
						
						$arr_type_scopes[$scope][$type_id][$arr_scope['display_mode']] = $arr_scope['scope_id'];
						
					}
				}
				
				$arr['settings']['projects'][$project_id]['scope'] = $arr_type_scopes;
				
			}
			
			$arr['settings']['cite_as'] = $arr_types_cite_as;
		}

		foreach ((array)$arr['project_filter_mode'] as $project_id => $project_filter_mode) {
			$arr['settings']['projects'][$project_id]['filter_mode'] = $project_filter_mode;
		}
		
		foreach ((array)$arr['project_filter_form'] as $project_id => $arr_project_filter_form) {
			
			if ($arr['project_filter_mode'][$project_id] != 'form') {
				continue;
			}
						
			$arr_filter_form = [];
			
			foreach ($arr_project_filter_form as $arr_form) {
				$arr_filter_form[] = $arr_form;
			}
			
			$arr['settings']['projects'][$project_id]['filter_form'] = $arr_filter_form;
		}	
	
		$public_interface_settings = (is_array($arr['settings']) ? value2JSON($arr['settings']) : $arr['settings']);
		
		if (!$public_interface_id) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." 
				("."name, mode, settings, description, information, css, script, is_default) 
					VALUES 
				(
					"."
					'".DBFunctions::strEscape($arr['name'])."',
					'".DBFunctions::strEscape($arr['mode'])."',
					'".DBFunctions::strEscape($public_interface_settings)."',
					'".DBFunctions::strEscape($arr['description'])."',
					'".DBFunctions::strEscape($arr['information'])."',
					'".DBFunctions::strEscape($arr['css'])."',
					'".DBFunctions::strEscape($arr['script'])."',
					".(int)$arr['is_default']."
				)"
			);
			
			
			$public_interface_id = DB::lastInsertID();
		
		} else {
		
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." SET
					name = '".DBFunctions::strEscape($arr['name'])."',
					mode = '".DBFunctions::strEscape($arr['mode'])."',
					settings = '".DBFunctions::strEscape($public_interface_settings)."',
					description = '".DBFunctions::strEscape($arr['description'])."',
					information = '".DBFunctions::strEscape($arr['information'])."',
					css = '".DBFunctions::strEscape($arr['css'])."',
					script = '".DBFunctions::strEscape($arr['script'])."',
					is_default = ".(int)$arr['is_default']."
				WHERE id = ".(int)$public_interface_id."
					"."
			");
		}
						
		$res = DB::query("DELETE ip, it, ipt, ips
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it ON (it.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt ON (ipt.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')." ips ON (ips.public_interface_id = i.id)
			WHERE i.id = ".(int)$public_interface_id."");

		if ($arr['projects']) {

			$arr_sql_insert = [];
			
			foreach ((array)$arr['projects'] as $value) {
				
				if (!$value) {
					continue;
				}
				
				$arr_sql_insert[] = "(".(int)$public_interface_id.", ".(int)$value.")";
			}

			if ($arr_sql_insert) {
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')."
					(public_interface_id, project_id)
						VALUES
					".implode(",", $arr_sql_insert)."
				");
			}
			
			if ($arr['settings']['projects']) {
				
				$i = 0;
				
				foreach ($arr['settings']['projects'] as $project_id => $arr_project) {
					
					$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." SET
							sort = ".(int)$i."
						WHERE public_interface_id = ".(int)$public_interface_id." 
							AND project_id = ".(int)$project_id."
					");
					
					$i++;					
				}
			}
		}

			
		$arr_sql_types_insert = [];
	
		foreach ((array)$arr['project_types'] as $project_id => $arr_project_types) {
			
			$sort = 0; 
			
			foreach ((array)$arr_project_types['types'] as $key => $type_id) {
				
				if (!(int)$type_id) {
					continue;
				}
				
				$arr_sql_types_insert[] = "(".(int)$public_interface_id.", ".(int)$project_id.", ".(int)$type_id.", 0, ".(int)$arr_project_types['display']['list'].", ".(int)$arr_project_types['display']['grid'].", ".(int)$arr_project_types['display']['geo'].", ".(int)$arr_project_types['display']['soc'].", ".(int)$arr_project_types['display']['time'].", ".$sort.")";
				$sort++;
			}
			
			$sort = 0; 
			
			foreach ((array)$arr_project_types['filters'] as $key => $type_id) {
				
				if (!(int)$type_id) {
					continue;
				}
				
				$arr_sql_types_insert[] = "(".(int)$public_interface_id.", ".(int)$project_id.", ".(int)$type_id.", 1, 0, 0, 0, 0, 0, ".$sort.")";
				$sort++;
			}
		}
			
		if ($arr_sql_types_insert) {
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')."
				(public_interface_id, project_id, type_id, is_filter, list, browse, geographic_visualisation, social_visualisation, time_visualisation, sort)
					VALUES
				".implode(",", $arr_sql_types_insert)."");
		}

	
		if ($arr['project_scenarios']) {

			$arr_sql_insert = [];
			
			foreach ($arr['project_scenarios'] as $project_id => $arr_project_scenarios) {
				
				$sort = 0; 
				
				foreach ($arr_project_scenarios as $key => $arr_scenario) {
	
					if (!$arr_scenario['list'] && !$arr_scenario['grid'] && !$arr_scenario['geo'] && !$arr_scenario['soc'] && !$arr_scenario['time']) {
						continue;
					}
	
					$arr_sql_insert[] = "(".(int)$public_interface_id.", ".(int)$project_id.", ".(int)$arr_scenario['id'].", ".(int)$arr_scenario['list'].", ".(int)$arr_scenario['grid'].", ".(int)$arr_scenario['geo'].", ".(int)$arr_scenario['soc'].", ".(int)$arr_scenario['time'].", ".$sort.")";
					$sort++;
				}
			}
			
			if (!empty($arr_sql_insert)) {
				
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')."
					(public_interface_id, project_id, scenario_id, list, browse, geographic_visualisation, social_visualisation, time_visualisation, sort)
						VALUES
					".implode(",", $arr_sql_insert)."
				");
			}
		}
		
		if ($arr_texts) {
			
			$sort = 0;
			
			foreach ($arr_texts as $value) {	
			
				$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." (public_interface_id, name, text, sort)
						VALUES 
					( ".(int)$public_interface_id.", '".DBFunctions::strEscape($value['name'])."', '".DBFunctions::strEscape($value['text'])."', ".$sort.")
				");
				
				$sort++;
			}
		}
		
		return $public_interface_id;
	}
	
	public static function delPublicInterface($public_interface_id) {
		
		$res = DB::query("DELETE i, ip, it, ipt, ips
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it ON (it.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt ON (ipt.public_interface_id = i.id)
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')." ips ON (ips.public_interface_id = i.id)
			WHERE i.id = ".(int)$public_interface_id."
				"."
		");
	}
	
	public static function getPublicInterfaces($public_interface_id = 0, $project_id = 0) {

		$str_identifier = (int)$public_interface_id.'_'.(int)$project_id;
		
		$cache = self::getCache($str_identifier);
		if ($cache) {
			return $cache;
		}

		$arr = [];

		$arr_res = DB::queryMulti("
			SELECT i.*, 
					it.id AS text_id, it.name AS text_name, it.text, it.sort
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it ON (it.public_interface_id = i.id)
				WHERE TRUE
					"."
					".($public_interface_id ? "AND i.id = ".(int)$public_interface_id."" : "")."
				ORDER BY it.sort;
				
			SELECT i.id, 
					ip.project_id, ip.sort,
					ipt.project_id AS type_project_id, ipt.type_id, ipt.is_filter AS type_is_filter, ipt.browse AS type_grid, ipt.list AS type_list, ipt.geographic_visualisation AS type_geo, ipt.social_visualisation AS type_soc, ipt.time_visualisation AS type_time
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
					JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt ON (ipt.public_interface_id = i.id AND ipt.project_id = ip.project_id)
				WHERE TRUE
					"."
					".($public_interface_id ? "AND i.id = ".(int)$public_interface_id."" : "")."
					".($project_id ? "AND ip.project_id = ".(int)$project_id."" : "")."
				ORDER BY ip.sort, ipt.sort;
				
			SELECT i.id,
					ips.project_id AS scenario_project_id, ips.scenario_id, ips.browse AS scenario_grid, ips.list AS scenario_list, ips.geographic_visualisation AS scenario_geo, ips.social_visualisation AS scenario_soc, ips.time_visualisation AS scenario_time
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
					JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
					JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_SCENARIOS')." ips ON (ips.public_interface_id = i.id AND ips.project_id = ip.project_id)
				WHERE TRUE
					"."
					".($public_interface_id ? "AND i.id = ".(int)$public_interface_id."" : "")."
					".($project_id ? "AND ip.project_id = ".(int)$project_id."" : "")."
				ORDER BY ips.sort;
		");

		while($row = $arr_res[0]->fetchAssoc()) {

			if (!$arr[$row['id']]) {
				
				$row['settings'] = json_decode($row['settings'], true);
				
				$arr[$row['id']] = ['interface' => $row, 'projects' => [], 'texts' => []];
			}
			if ($row['text_id']) {
				$arr[$row['id']]['texts'][$row['text_id']] = $row;
			}
		}
		
		while($row = $arr_res[1]->fetchAssoc()) {

			$arr[$row['id']]['projects'][$row['project_id']] = $row;
			
			if ($row['type_id']) {
				$arr[$row['id']]['project_types'][$row['type_project_id']][$row['type_id']] = $row;
			}
		}
		
		while($row = $arr_res[2]->fetchAssoc()) {

			$arr[$row['id']]['project_scenarios'][$row['scenario_project_id']][$row['scenario_id']] = $row;
		}
		
		$arr = ((int)$public_interface_id ? current($arr) : $arr);
		
		self::setCache($str_identifier, $arr);

		return $arr;
	}
	
	public static function getDefaultPublicInterfaceId() {
		
		$id = false;
		
		$res = DB::query("SELECT i.id
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
			WHERE i.is_default = 1
				"."
		");

		while($row = $res->fetchAssoc()) {
			
			if ($row['id']) {
				$id = $row['id'];
			}
		}
		
		return (int)$id;
	}

	public static function getPublicInterfaceProjectIds($public_interface_id, $limit = false) {
		
		$arr = [];

		$res = DB::query("SELECT
			i.id, 
			ip.project_id, ip.sort
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.public_interface_id = i.id)
			WHERE i.id = ".(int)$public_interface_id."
				"."
			ORDER BY ip.sort
			".($limit ? "LIMIT ".(int)$limit."" : "")."
		");

		while ($row = $res->fetchAssoc()) {
			
			if ($row['project_id']) {
				$arr[$row['project_id']] = $row['project_id']; 
			}
		}
		
		return (count($arr) > 1 ? $arr : key($arr));
	}	

	public static function getPublicInterfaceTypeIds($public_interface_id, $project_id = false, $filter = false) {

		$arr = [];
 	
		$res = DB::query("SELECT ipt.public_interface_id, ipt.type_id, ipt.project_id, ipt.is_filter, ipt.sort  
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip ON (ip.project_id = ipt.project_id)
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i ON (i.id = ip.public_interface_id)
			WHERE ipt.public_interface_id = ".(int)$public_interface_id."
				"."
				".($project_id ? "AND ipt.project_id = ".(int)$project_id."" : "")."
				".($filter ? "AND ipt.is_filter = 1" : "AND ipt.is_filter = 0")."
			ORDER BY ipt.sort
		");

		while ($row = $res->fetchAssoc()) {
			
			if ($row['type_id']) {
				$arr[$row['type_id']] = $row['type_id']; 
			}
		}

		return $arr;
	}
	
	public static function getPublicInterfaceTypeProjectId($public_interface_id, $type_id) {

		$project_id = false;
 	
		$res = DB::query("SELECT ipt.project_id 
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECT_TYPES')." ipt
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_PROJECTS')." ip on (ip.project_id = ipt.project_id)
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i ON (i.id = ip.public_interface_id)
			WHERE ipt.public_interface_id = ".(int)$public_interface_id."
				AND ip.public_interface_id = ".(int)$public_interface_id."
				AND ipt.type_id = ".(int)$type_id."
				"."
			ORDER BY ip.sort
		");

		while ($row = $res->fetchAssoc()) {
			
			if ($row['project_id']) {
				$project_id = $row['project_id']; 
				break;
			}
		}

		return $project_id;
	}

	public static function getPublicInterfaceStyle($public_interface_id) {
		
		$arr = [];
		
		$res = DB::query("SELECT i.id, i.css, i.script
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
			WHERE i.id = ".(int)$public_interface_id."
				"."
		");

		while($row = $res->fetchAssoc()) {
			$arr = $row;
		}
		
		return $arr;
	}
	
	public static function getPublicInterfaceSettings($public_interface_id) {
		
		$arr = [];
		
		$res = DB::query("SELECT i.id, i.settings
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i
			WHERE i.id = ".(int)$public_interface_id."
				"."
		");

		while($row = $res->fetchAssoc()) {
			
			$row['settings'] = json_decode($row['settings'], true);
			
			$arr = $row;
		}
		
		return $arr['settings'];
	}
	
	public static function getPublicInterfaceTexts($public_interface_id) {
	
		$arr = [];
	
		$res = DB::query("SELECT it.*
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_TEXTS')." it
				JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACES')." i ON (i.id = it.public_interface_id)
			WHERE i.id = ".(int)$public_interface_id."
				"."
			ORDER BY it.sort
		");

		while ($row = $res->fetchAssoc()) {
			$arr[] = $row;
		}

		return $arr;
	}
	
	public static function getPublicInterfaceDataOptions() {
					
		$arr = [
			'grid' => ['id' => 'grid', 'name' => getLabel('lbl_grid'), 'icon' => 'tiles-many', 'visualisation_type' => false],
			'list' => ['id' => 'list', 'name' => getLabel('lbl_list'), 'icon' => 'tags', 'visualisation_type' => false],
			'geo' => ['id' => 'geo', 'name' => getLabel('lbl_map'), 'icon' => 'globe', 'visualisation_type' => 'plot'],
			'soc' => ['id' => 'soc', 'name' => getLabel('lbl_network'), 'icon' => 'graph', 'visualisation_type' => 'soc'],
			'time' => ['id' => 'time', 'name' => getLabel('lbl_chart'), 'icon' => 'chart-bar', 'visualisation_type' => 'line']
		];

		return $arr;
	}
	
	public static function getPublicInterfaceShareOptions($title) {
					
		$arr = [
			['share_name' => 'E-Mail', 'share_url' => 'mailto:?subject='.$title.' - &amp;body='.$title.' - ', 'icon' => 'email'],
			['share_name' => 'Facebook', 'share_url' => 'https://www.facebook.com/sharer/sharer.php?u=', 'icon_class' => 'icon-fontawesome-facebook'],
			['share_name' => 'Twitter', 'share_url' => 'https://twitter.com/home?status='.$title.' - ', 'icon_class' => 'icon-fontawesome-twitter'],
			['share_name' => 'Linkedin', 'share_url' => 'https://www.linkedin.com/shareArticle?mini=true&title='.$title.'&url=', 'icon_class' => 'icon-fontawesome-linkedin']
		];

		return $arr;
	}
	
	public static function getPublicInterfaceSelection($selection_id) {

		$arr = [];

		$res = DB::query("SELECT p.id, p.date_modified, p.title as selection_title, p.notes as selection_notes, p.editor as selection_editor, 
				pe.selection_id, pe.elm_id, pe.type as elm_type, pe.heading as elm_heading, pe.notes as elm_notes, pe.sort 
			FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." p
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe ON (pe.selection_id = p.id)
			WHERE p.id = '".DBFunctions::strEscape($selection_id)."'
			ORDER BY pe.sort
		");

		while($row = $res->fetchAssoc()) {

			if (!$arr['id']) {
				$arr = $row;
				$arr['elements'] = [];
			}
			if ($row['elm_id']) {
				$arr['elements'][$row['elm_id']] = $row;
			}
		}

		return $arr;
	}
	
	public static function storePublicInterfaceSelection($arr_selection) {

		$selection_url_id = $arr_selection['url_id'];

		if (!$selection_url_id) {
			
			$selection_url_id = generateRandomString(20);
			$res = DB::query("INSERT INTO ".DB::getTable("DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS")." 
								(id, date_modified, title, notes, editor) 
							VALUES 
								('".DBFunctions::strEscape($selection_url_id)."',
									NOW(),
									'".DBFunctions::strEscape($arr_selection['selection_title'])."',
									'".DBFunctions::strEscape($arr_selection['selection_notes'])."',
									'".DBFunctions::strEscape($arr_selection['selection_editor'])."'
								)");
								
		} else {
			
			$res = DB::query("UPDATE ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." SET
					date_modified = NOW(),
					title = '".DBFunctions::strEscape($arr_selection['selection_title'])."',
					notes = '".DBFunctions::strEscape($arr_selection['selection_notes'])."',
					editor = '".DBFunctions::strEscape($arr_selection['selection_editor'])."'
				WHERE id = '".DBFunctions::strEscape($selection_url_id)."'");
						
		}

		$res = DB::query("DELETE pe
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe
			WHERE pe.selection_id = '".DBFunctions::strEscape($selection_url_id)."'");
		
		$arr_elements = $arr_selection['elements'];
		usort($arr_elements, function($a, $b) { return $a['sort'] > $b['sort']; }); 

		$sort = 0;
		foreach ((array)$arr_elements as $arr_element) {	
			
			$res = DB::query("INSERT INTO ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')."
				(selection_id, elm_id, type, heading, notes, sort)
					VALUES 
				('".DBFunctions::strEscape($selection_url_id)."', '".DBFunctions::strEscape($arr_element['elm_id'])."', '".DBFunctions::strEscape($arr_element['elm_type'])."', '".DBFunctions::strEscape($arr_element['elm_heading'])."', '".DBFunctions::strEscape($arr_element['elm_notes'])."', ".(int)$sort.")");
			
			$sort++;
		}

		return $selection_url_id;
	}
	
	public static function delPublicInterfaceSelection($selection_url_id) {

		$res = DB::query("DELETE p, pe
				FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." p
				LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe ON (pe.selection_id = p.id)
			WHERE p.id = '".DBFunctions::strEscape($selection_url_id)."'
		");
	}
	
	public static function cleanupPublicInterfaceSelections($arr_options = []) {

		if ($arr_options['age_amount'] && $arr_options['age_unit']) {
			
			$minutes = $arr_options['age_amount'] * $arr_options['age_unit'];
			
			$res = DB::query("DELETE p, pe
					FROM ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTIONS')." p
					LEFT JOIN ".DB::getTable('DEF_NODEGOAT_PUBLIC_INTERFACE_SELECTION_ELEMENTS')." pe ON (pe.selection_id = p.id)
				WHERE p.date_modified < (NOW() - ".DBFunctions::interval($minutes, 'MINUTE').")
			");
		} else {
			error(getLabel('msg_missing_information'));
		}
	}
}
