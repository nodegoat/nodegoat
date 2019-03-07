<?php

/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

class data_visualise extends base_module {
	
	public static function moduleProperties() {
		static::$label = 'Data Visualise';
		static::$parent_label = 'nodegoat';
	}
		
	public function createVisualisationSettings($type_id, $arr_scope, $arr_context, $arr_frame, $arr_visual_settings) {
				
		$return = '<div class="tabs visualise-settings">
			<ul>
				<li><a href="#scope">'.getLabel('lbl_scope').'</a></li>
				<li><a href="#context">'.getLabel('lbl_context').'</a></li>
				<li><a href="#frame">'.getLabel('lbl_frame').'</a></li>
				<li><a href="#visual-settings">'.getLabel('lbl_visual_settings').'</a></li>
			</ul>
			
			<div>
				'.$this->createScope($type_id, $arr_scope).'
			</div>

			<div>
				'.$this->createContext($type_id, $arr_context).'
			</div>
			
			<div>
				'.$this->createFrame($type_id, $arr_frame).'
			</div>
			
			<div>
				'.$this->createVisualSettings($arr_visual_settings).'
			</div>
			
		</div>';
				
		return $return;
	}
	
	private function createScope($type_id, $arr_scope) {
		
		$return = '<div class="tabs">
			<ul>
				<li class="no-tab"><span>'
					.'<input type="button" id="y:data_visualise:store_scope-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_scope_store').'" />'
					.'<input type="button" id="y:data_visualise:open_scope-'.$type_id.'" class="data add popup" value="open" title="'.getLabel('inf_scope_open').'" />'
				.'</span></li>
				<li><a href="#">'.getLabel('lbl_model').'</a></li>
			</ul>
			
			<div>

				<div class="options scope">
					'.data_model::createTypeNetwork($type_id, false, false, ['references' => 'both', 'network' => ['dynamic' => true, 'object_sub_locations' => true], 'value' => $arr_scope, 'name' => 'scope', 'descriptions' => 'combine']).'
				</div>
				
			</div>
		</div>';
		
		return $return;
	}
	
	private function createContext($type_id, $arr_context) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$arr_types = ($arr_project['types'] ? StoreType::getTypes(array_keys($arr_project['types'])) : []);
		
		$return .= '<div class="context">
						
			<div class="tabs">
				<ul>
					<li class="no-tab"><span>'
						.'<input type="button" id="y:data_visualise:store_context-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_context_store').'" />'
						.'<input type="button" id="y:data_visualise:open_context-'.$type_id.'" class="data add popup" value="open" title="'.getLabel('inf_context_open').'" />'
					.'</span></li>
					<li><a href="#">'.getLabel('lbl_include').'</a></li>
				</ul>
				
				<div>
					<div class="options">
						
						<section class="info attention">'.getLabel('inf_context_include_scenarios').'</section>
						
						<fieldset><ul>
							<li>
								<label></label><span><input type="button" class="data del" value="del" title="'.getLabel('inf_remove_empty_fields').'" /><input type="button" class="data add" value="add" /></span>
							</li>
							<li>
								<label>'.getLabel('lbl_type').' - '.getLabel('lbl_scenario').'</label>';
								
								$arr_context_includes = $arr_context['include'];
								if ($arr_context_includes) {
									array_unshift($arr_context_includes, []); // Empty run for sorter source
								} else {
									$arr_context_includes = [[], []]; // Empty run for sorter source
								}
								
								$arr_sorter = [];
								
								foreach ($arr_context_includes as $key => $arr_include) {
									
									$arr_scenarios = ($arr_include['type_id'] ? cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $arr_include['type_id'], false, $arr_use_project_ids) : []);
									
									$unique = uniqid('array_');
									
									$arr_sorter[] = [
										'value' => '<div id="y:data_visualise:select_context_include_type-0">'
											.'<select name="context[include]['.$unique.'][type_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_types, $arr_include['type_id'], true)).'</select>'
											.'<select name="context[include]['.$unique.'][scenario_id]">'.Labels::parseTextVariables(cms_general::createDropdown($arr_scenarios, $arr_include['scenario_id'], true, 'label')).'</select>'
										.'</div>',
										'source' => ($key == 0 ? true : false)
									];
								}
								
								$return .= cms_general::createSorter($arr_sorter);
								
							$return .= '</li>
						</ul></fieldset>
						
					</div>
				</div>
			
			</div>
		</div>';
		
		return $return;
	}
	
	private function createFrame($type_id, $arr_frame) {
		
		if ($arr_frame['area']['social']['object_id']) {
			$arr_type_object_names = FilterTypeObjects::getTypeObjectNames($type_id, $arr_frame['area']['social']['object_id']);
			$area_social_object_name = current($arr_type_object_names);
		}
		
		$arr_date = [['id' => 'ignore', 'name' => getLabel('lbl_ignore')], ['id' => 'span', 'name' => getLabel('lbl_span').' '.getLabel('lbl_selection')], ['id' => 'prefix', 'name' => getLabel('lbl_prefix').' '.getLabel('lbl_selection')], ['id' => 'affix', 'name' => getLabel('lbl_affix').' '.getLabel('lbl_selection')]];
		$arr_location = [['id' => 'ignore', 'name' => getLabel('lbl_ignore')], ['id' => 'equator', 'name' => 'Equator']];
		
		$return = '<div class="frame">
			
			<div class="tabs">
				<ul>
					<li class="no-tab"><span>'
						.'<input type="button" id="y:data_visualise:store_frame-'.$type_id.'" class="data edit popup" value="save" title="'.getLabel('inf_frame_store').'" />'
						.'<input type="button" id="y:data_visualise:open_frame-'.$type_id.'" class="data add popup" value="open" title="'.getLabel('inf_frame_open').'" />'
					.'</span></li>
					<li><a href="#">'.getLabel('lbl_geo').'</a></li>
					<li><a href="#">'.getLabel('lbl_soc').'</a></li>
				</ul>
				
				<div>
					<div class="options">
					
						<fieldset><legend>'.getLabel('lbl_area').'</legend><ul>
							<li><label>'.getLabel('lbl_latitude').'</label><span><input name="frame[area][geo][latitude]" type="text" value="'.$arr_frame['area']['geo']['latitude'].'" /></span></li>
							<li><label>'.getLabel('lbl_longitude').'</label><input name="frame[area][geo][longitude]" type="text" value="'.$arr_frame['area']['geo']['longitude'].'" /></li>
							<li><label></label><span><input type="button" class="data add" id="y:data_filter:select_geometry-0" value="map" /></span></li>
							<li><label>'.getLabel('lbl_scale').'</label><div><span>1 pixel = </span><input name="frame[area][geo][scale]" type="number" value="'.$arr_frame['area']['geo']['scale'].'" /><span> '.getLabel('unit_meter').' (equatorial)</span></div></li>
						</ul></fieldset>
						
					</div>
				</div>
				<div>
					<div class="options">
					
						<fieldset><legend>'.getLabel('lbl_area').'</legend><ul>
							<li><label>'.getLabel('lbl_focus').'</label><span><input type="hidden" name="frame[area][social][object_id]" value="'.$arr_frame['area']['social']['object_id'].'" /><input type="search" id="y:data_filter:lookup_type_object-'.$type_id.'" class="autocomplete" value="'.$area_social_object_name.'" /></span></li>
							<li><label>'.getLabel('lbl_zoom_level').'</label><div><input type="range" min="1" max="100" step="1" /><input type="number" name="frame[area][social][zoom]" value="'.$arr_frame['area']['social']['zoom'].'" /><label>%</label></div></li>
						</ul></fieldset>
						
					</div>
				</div>
				
			</div>

			<div class="options fieldsets"><div>
			
				<fieldset><legend>'.getLabel('lbl_time').'</legend><ul>
					<li><label>'.getLabel('lbl_boundary').'</label><span>'
						.'<label>'.getLabel('lbl_start').'</label><input type="text" value="'.StoreTypeObjects::formatToCleanValue('date', $arr_frame['time']['bounds']['date_start']).'" name="frame[time][bounds][date_start]" class="date" placeholder="d-m-y">'
						.'<label>'.getLabel('lbl_end').'</label><input type="text" value="'.StoreTypeObjects::formatToCleanValue('date', $arr_frame['time']['bounds']['date_end']).'" name="frame[time][bounds][date_end]" class="date" placeholder="d-m-y">'
					.'</span></li>
					<li><label>'.getLabel('lbl_selection').'</label><span>'
						.'<label>'.getLabel('lbl_start').'</label><input type="text" value="'.StoreTypeObjects::formatToCleanValue('date', $arr_frame['time']['selection']['date_start']).'" name="frame[time][selection][date_start]" class="date" placeholder="d-m-y">'
						.'<label>'.getLabel('lbl_end').'</label><input type="text" value="'.StoreTypeObjects::formatToCleanValue('date', $arr_frame['time']['selection']['date_end']).'" name="frame[time][selection][date_end]" class="date" placeholder="d-m-y">'
					.'</span></li>
				</ul></fieldset>
				
				<fieldset><legend>'.getLabel('lbl_object_subs_unknown').'</legend><ul>
					<li><label>'.getLabel('lbl_date').'</label><span>'.cms_general::createSelectorRadio($arr_date, 'frame[object_subs][unknown][date]', $arr_frame['object_subs']['unknown']['date']).'</span></li>
					<li><label>'.getLabel('lbl_location').'</label><span>'.cms_general::createSelectorRadio($arr_location, 'frame[object_subs][unknown][location]', $arr_frame['object_subs']['unknown']['location']).'</span></li>
				</ul></fieldset>
				
			</div></div>
	
		</div>';
		
		return $return;
	}
	
	private function createVisualSettings($arr_settings) {
		
		$arr_yes_no = [['id' => 1, 'name' => getLabel('lbl_yes')], ['id' => 0, 'name' => getLabel('lbl_no')]];
		$arr_mode = [['id' => 1, 'name' => getLabel('lbl_mode_connect')], ['id' => 2, 'name' => getLabel('lbl_mode_move')]];
		$arr_display = [['id' => 1, 'name' => getLabel('lbl_display_vector')], ['id' => 2, 'name' => getLabel('lbl_display_pixel')]];
		
		$func_parse_advanced = function($arr) {
			
			$str = '';
			
			foreach ($arr as $key => $value) {
				$str .= $key.':'.$value.PHP_EOL;
			}
			
			return $str;
		};
		
		$str_geo_advanced = $func_parse_advanced($arr_settings['settings']['geo_advanced']);
		$str_social_advanced = $func_parse_advanced($arr_settings['social']['settings']['social_advanced']);
		
		$return = '<div class="tabs visual-settings">
			<ul>
				<li class="no-tab"><span>'
					.'<input type="button" id="y:data_visualise:store_visual_settings-0" class="data edit popup" value="save" title="'.getLabel('inf_visual_settings_store').'" />'
					.'<input type="button" id="y:data_visualise:open_visual_settings-0" class="data add popup" value="open" title="'.getLabel('inf_visual_settings_open').'" />'
				.'</span></li>
				<li><a href="#">'.getLabel('lbl_geo').'</a></li>
				<li><a href="#">'.getLabel('lbl_soc').'</a></li>
				<li><a href="#">'.getLabel('lbl_time').'</a></li>
			</ul>
			
			<div>
				<div class="options fieldsets"><div>
					<fieldset><legend>'.getLabel('lbl_design').'</legend><ul>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_dot').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[dot][show]', $arr_settings['dot']['show']).'</span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_color').'</label><span>'
							.'<input name="visual_settings[dot][color]" type="text" value="'.$arr_settings['dot']['color'].'" class="colorpicker" />'
							.'<input title="'.getLabel('lbl_opacity').'" name="visual_settings[dot][opacity]" type="number" step="0.01" min="0" max="1" value="'.$arr_settings['dot']['opacity'].'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_color').' '.getLabel('lbl_condition').'</label><input name="visual_settings[dot][color_condition]" type="text" value="'.$arr_settings['dot']['color_condition'].'" /></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_size_min').'</label><span>'
							.'<input name="visual_settings[dot][size][min]" type="number" step="0.5" min="1" value="'.$arr_settings['dot']['size']['min'].'" />'
							.'<input name="visual_settings[dot][size][start]" type="number" step="1" min="0" value="'.$arr_settings['dot']['size']['start'].'" title="'.getLabel('inf_size_start').'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_size_max').'</label><span>'
							.'<input name="visual_settings[dot][size][max]" type="number" step="0.5" min="1" value="'.$arr_settings['dot']['size']['max'].'" />'
							.'<input name="visual_settings[dot][size][stop]" type="number" step="1" min="0" value="'.$arr_settings['dot']['size']['stop'].'" title="'.getLabel('inf_size_stop').'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_stroke_color').'</label><span>'
							.'<input name="visual_settings[dot][stroke_color]" type="text" value="'.$arr_settings['dot']['stroke_color'].'" class="colorpicker" />'
							.'<input title="'.getLabel('lbl_opacity').'" name="visual_settings[dot][stroke_opacity]" type="number" step="0.01" min="0" max="1" value="'.$arr_settings['dot']['stroke_opacity'].'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_stroke_width').'</label><input name="visual_settings[dot][stroke_width]" type="number" step="0.5" min="0" value="'.$arr_settings['dot']['stroke_width'].'" /></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_location').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[location][show]', $arr_settings['location']['show']).'</span></li>
						<li><label>'.getLabel('lbl_location').' '.getLabel('lbl_color').'</label><span><input name="visual_settings[location][color]" type="text" value="'.$arr_settings['location']['color'].'" class="colorpicker" /></span></li>
						<li><label>'.getLabel('lbl_location').' '.getLabel('lbl_size').'</label><input name="visual_settings[location][size]" type="number" step="1" min="1" value="'.$arr_settings['location']['size'].'" /></li>
						<li><label>'.getLabel('lbl_location').' '.getLabel('lbl_threshold').'</label><input name="visual_settings[location][threshold]" type="number" step="1" min="1" value="'.$arr_settings['location']['threshold'].'" /></li>
						<li><label>'.getLabel('lbl_location').' '.getLabel('lbl_condition').'</label><input name="visual_settings[location][condition]" type="text" value="'.$arr_settings['location']['condition'].'" /></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_line').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[line][show]', $arr_settings['line']['show']).'</span></li>
						<li><label>'.getLabel('lbl_line').' '.getLabel('lbl_color').'</label><span>'
							.'<input name="visual_settings[line][color]" type="text" value="'.$arr_settings['line']['color'].'" class="colorpicker" />'
							.'<input title="'.getLabel('lbl_opacity').'" name="visual_settings[line][opacity]" type="number" step="0.01" min="0" max="1" value="'.$arr_settings['line']['opacity'].'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_line').' '.getLabel('lbl_width_min').'</label><input name="visual_settings[line][width][min]" type="number" step="0.5" min="1" value="'.$arr_settings['line']['width']['min'].'" /></li>
						<li><label>'.getLabel('lbl_line').' '.getLabel('lbl_width_max').'</label><input name="visual_settings[line][width][max]" type="number" step="0.5" min="1" value="'.$arr_settings['line']['width']['max'].'" /></li>
						<li><label>'.getLabel('lbl_line').' '.getLabel('lbl_offset').'</label><input name="visual_settings[line][offset]" type="number" step="1" min="0" value="'.$arr_settings['line']['offset'].'" /></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_geometry').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[geometry][show]', $arr_settings['geometry']['show']).'</span></li>
						<li><label>'.getLabel('lbl_geometry').' '.getLabel('lbl_color').'</label><span>'
							.'<input name="visual_settings[geometry][color]" type="text" value="'.$arr_settings['geometry']['color'].'" class="colorpicker" />'
							.'<input title="'.getLabel('lbl_opacity').'" name="visual_settings[geometry][opacity]" type="number" step="0.01" min="0" max="1" value="'.$arr_settings['geometry']['opacity'].'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_geometry').' '.getLabel('lbl_stroke_color').'</label><span>'
							.'<input name="visual_settings[geometry][stroke_color]" type="text" value="'.$arr_settings['geometry']['stroke_color'].'" class="colorpicker" />'
							.'<input title="'.getLabel('lbl_opacity').'" name="visual_settings[geometry][stroke_opacity]" type="number" step="0.01" min="0" max="1" value="'.$arr_settings['geometry']['stroke_opacity'].'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_geometry').' '.getLabel('lbl_stroke_width').'</label><input name="visual_settings[geometry][stroke_width]" type="number" step="0.5" min="0" value="'.$arr_settings['geometry']['stroke_width'].'" /></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_visual_hint').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[hint][show]', $arr_settings['hint']['show']).'</span></li>
						<li><label>'.getLabel('lbl_visual_hint').' '.getLabel('lbl_color').'</label><span>'
							.'<input name="visual_settings[hint][color]" type="text" value="'.$arr_settings['hint']['color'].'" class="colorpicker" />'
							.'<input title="'.getLabel('lbl_opacity').'" name="visual_settings[hint][opacity]" type="number" step="0.01" min="0" max="1" value="'.$arr_settings['hint']['opacity'].'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_visual_hint').' '.getLabel('lbl_size').'</label><input name="visual_settings[hint][size]" type="number" step="0.5" min="1" value="'.$arr_settings['hint']['size'].'" /></li>
						<li><label>'.getLabel('lbl_visual_hint').' '.getLabel('lbl_stroke_color').'</label><span>'
							.'<input name="visual_settings[hint][stroke_color]" type="text" value="'.$arr_settings['hint']['stroke_color'].'" class="colorpicker" />'
							.'<input title="'.getLabel('lbl_opacity').'" name="visual_settings[hint][stroke_opacity]" type="number" step="0.01" min="0" max="1" value="'.$arr_settings['hint']['stroke_opacity'].'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_visual_hint').' '.getLabel('lbl_stroke_width').'</label><input name="visual_settings[hint][stroke_width]" type="number" step="0.5" min="0" value="'.$arr_settings['hint']['stroke_width'].'" /></li>
						<li><label>'.getLabel('lbl_visual_hint').' '.getLabel('lbl_duration').'</label><input name="visual_settings[hint][duration]" type="number" step="0.01" min="0.05" value="'.$arr_settings['hint']['duration'].'" /></li>
						<li><label>'.getLabel('lbl_visual_hint').' '.getLabel('lbl_delay').'</label><input name="visual_settings[hint][delay]" type="number" step="0.01" min="0" value="'.$arr_settings['hint']['delay'].'" /></li>
					</ul></fieldset>
					
					<fieldset><legend>'.getLabel('lbl_settings').'</legend><ul>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_geo_info').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[settings][geo_info_show]', $arr_settings['settings']['geo_info_show']).'</span></li>
						<li><label>'.getLabel('lbl_map').'</label><input name="visual_settings[settings][map_url]" type="text" value="'.$arr_settings['settings']['map_url'].'" /><input name="visual_settings[settings][map_attribution]" type="text" value="'.$arr_settings['settings']['map_attribution'].'" /></li>
						<li><label>'.getLabel('lbl_background_color').'</label><span><input name="visual_settings[settings][geo_background_color]" type="text" value="'.$arr_settings['settings']['geo_background_color'].'" class="colorpicker" /></span></li>
						<li><label>'.getLabel('lbl_mode').'</label><span>'.cms_general::createSelectorRadio($arr_mode, 'visual_settings[settings][geo_mode]', $arr_settings['settings']['geo_mode']).'</span></li>
						<li><label>'.getLabel('lbl_display').'</label><span>'.cms_general::createSelectorRadio($arr_display, 'visual_settings[settings][geo_display]', $arr_settings['settings']['geo_display']).'</span></li>
						<li><label>'.getLabel('lbl_advanced').'</label><span><textarea name="visual_settings[settings][geo_advanced]">'.$str_geo_advanced.'</textarea></span></li>
					</ul></fieldset>
				</div></div>
			</div>
			
			<div>
				<div class="options fieldsets"><div>
					<fieldset><legend>'.getLabel('lbl_design').'</legend><ul>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_color').'</label><span><input name="visual_settings[social][dot][color]" type="text" value="'.$arr_settings['social']['dot']['color'].'" class="colorpicker" /></span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_size_min').'</label><span>'
							.'<input name="visual_settings[social][dot][size][min]" type="number" step="0.5" min="1" value="'.$arr_settings['social']['dot']['size']['min'].'" />'
							.'<input name="visual_settings[social][dot][size][start]" type="number" step="1" min="0" value="'.$arr_settings['social']['dot']['size']['start'].'" title="'.getLabel('inf_size_start').'" />'
						.'</span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_size_max').'</label><span>'
							.'<input name="visual_settings[social][dot][size][max]" type="number" step="0.5" min="1" value="'.$arr_settings['social']['dot']['size']['max'].'" />'
							.'<input name="visual_settings[social][dot][size][stop]" type="number" step="1" min="0" value="'.$arr_settings['social']['dot']['size']['stop'].'" title="'.getLabel('inf_size_stop').'" />'
						.'<span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_stroke_color').'</label><span><input name="visual_settings[social][dot][stroke_color]" type="text" value="'.$arr_settings['social']['dot']['stroke_color'].'" class="colorpicker" /></span></li>
						<li><label>'.getLabel('lbl_dot').' '.getLabel('lbl_stroke_width').'</label><input name="visual_settings[social][dot][stroke_width]"  type="number" step="0.5" min="0" value="'.$arr_settings['social']['dot']['stroke_width'].'" /></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_line').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[social][line][show]', $arr_settings['social']['line']['show']).'</span></li>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_arrowhead').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[social][line][arrowhead_show]', $arr_settings['social']['line']['arrowhead_show']).'</span></li>
					</ul></fieldset>
					
					<fieldset><legend>'.getLabel('lbl_settings').'</legend><ul>
						<li><label>'.getLabel('lbl_show').' '.getLabel('lbl_disconnected').' '.getLabel('lbl_dot').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[social][settings][disconnected_dot_show]', $arr_settings['social']['settings']['disconnected_dot_show']).'</span></li>
						<li><label>'.getLabel('lbl_include').' '.getLabel('lbl_location_references').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[social][settings][include_location_references]', $arr_settings['social']['settings']['include_location_references']).'</span></li>
						<li><label>'.getLabel('lbl_background_color').'</label><span><input name="visual_settings[social][settings][background_color]" type="text" value="'.$arr_settings['social']['settings']['background_color'].'" class="colorpicker" /></span></li>
						<li><label>'.getLabel('lbl_display').'</label><span>'.cms_general::createSelectorRadio($arr_display, 'visual_settings[social][settings][display]', $arr_settings['social']['settings']['display']).'</span></li>
						<li><label>'.getLabel('lbl_static').' '.getLabel('lbl_layout').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[social][settings][static_layout]', $arr_settings['social']['settings']['static_layout']).'</span></li>
						<li><label>'.getLabel('lbl_static').' '.getLabel('lbl_layout').' '.getLabel('lbl_interval').'</label><input name="visual_settings[social][settings][static_layout_interval]" type="text" value="'.$arr_settings['social']['settings']['static_layout_interval'].'" /></li>
						<li><label>'.getLabel('lbl_advanced').'</label><span><textarea name="visual_settings[social][settings][social_advanced]">'.$str_social_advanced.'</textarea></span></li>					
					</ul></fieldset>
				</div></div>
			</div>
			
			<div>
				<div class="options fieldsets"><div>
					<fieldset><legend>'.getLabel('lbl_settings').'</legend><ul>
						<li><label>'.getLabel('lbl_relative').' '.getLabel('lbl_conditions').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[time][settings][conditions_relative]', $arr_settings['time']['settings']['conditions_relative']).'</span></li>	
						<li><label>'.getLabel('lbl_cumulative').' '.getLabel('lbl_conditions').'</label><span>'.cms_general::createSelectorRadio($arr_yes_no, 'visual_settings[time][settings][conditions_cumulative]', $arr_settings['time']['settings']['conditions_cumulative']).'</span></li>			
					</ul></fieldset>
				</div></div>
			</div>
		</div>';
		
		return $return;
	}
	
	private function createSelectContext($type_id, $store = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<fieldset><legend>'.getLabel(($store ? 'lbl_save' : 'lbl_select')).'</legend>
			<ul>
				<li><label>'.getLabel('lbl_context').'</label><div id="x:custom_projects:context_storage-'.(int)$type_id.'">'
					.'<select name="context_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeContexts($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), false, true, 'label').'</select>'
					.($store ? 
						'<input type="button" class="data add popup add_context_storage" value="save" />'
						.'<input type="button" class="data del msg del_context_storage" value="del" />'
					: '')
				.'</div></li>
			</ul>
		</fieldset>';

		return $return;
	}
	
	private function createSelectScope($type_id, $arr_scope = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = [];
		
		if ($arr_scope !== false) {
			
			$arr_scope = data_model::parseTypeNetwork($arr_scope);
		} else {

			$arr_use_project_ids = array_keys($arr_project['use_projects']);
		}
		
		$return = '<div class="tabs">
			<ul>
				<li><a href="#storage">'.getLabel(($arr_scope === false ? 'lbl_select' : 'lbl_save')).'</a></li>
				<li><a href="#advanced">'.getLabel('lbl_advanced').'</a></li>
			</ul>
			<div>
				<div class="options">
					<fieldset>
						<ul>
							<li><label>'.getLabel('lbl_scope').'</label><div id="x:custom_projects:scope_storage-'.(int)$type_id.'">'
								.'<select name="scope_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeScopes($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), false, true, 'label').'</select>'
								.($arr_scope ? 
									'<input type="button" class="data add popup add_scope_storage" value="save" />'
									.'<input type="button" class="data del msg del_scope_storage" value="del" />'
								: '')
							.'</div></li>
						</ul>
					</fieldset>
				</div>
			</div>
			<div>
				<div class="options">
					<fieldset>
						<ul>
							<li><label>'.getLabel('lbl_form').'</label><div>'
								.'<textarea name="plain">'.($arr_scope ? json_encode($arr_scope, JSON_PRETTY_PRINT) : '').'</textarea>'
							.'</div></li>
						</ul>
					</fieldset>
				</div>
			</div>
		</div>';

		return $return;
	}
	
	private function createSelectFrame($type_id, $store = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<fieldset><legend>'.getLabel(($store ? 'lbl_save' : 'lbl_select')).'</legend>
			<ul>
				<li><label>'.getLabel('lbl_frame').'</label><div id="x:custom_projects:frame_storage-'.(int)$type_id.'">'
					.'<select name="frame_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectTypeFrames($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids), false, true, 'label').'</select>'
					.($store ? 
						'<input type="button" class="data add popup add_frame_storage" value="save" />'
						.'<input type="button" class="data del msg del_frame_storage" value="del" />'
					: '')
				.'</div></li>
			</ul>
		</fieldset>';

		return $return;
	}
	
	private function createSelectVisualSettings($store = false) {
		
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		$return = '<fieldset><legend>'.getLabel(($store ? 'lbl_save' : 'lbl_select')).'</legend>
			<ul>
				<li><label>'.getLabel('lbl_visual_settings').'</label><div id="x:custom_projects:visual_settings_storage-0">'
					.'<select name="visual_settings_id">'.cms_general::createDropdown(cms_nodegoat_custom_projects::getProjectVisualSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], false, $arr_use_project_ids), false, true, 'label').'</select>'
					.($store ? 
						'<input type="button" class="data add popup add_visual_settings_storage" value="save" />'
						.'<input type="button" class="data del msg del_visual_settings_storage" value="del" />'
					: '')
				.'</div></li>
			</ul>
		</fieldset>';

		return $return;
	}

	public static function css() {
		
		$return = '.frame [name="frame[area][geo_scale]"] { width: 50px; }
				.visual-settings fieldset > ul > li > label:first-child + * textarea[name="visual_settings[settings][geo_advanced]"],
				.visual-settings fieldset > ul > li > label:first-child + * textarea[name="visual_settings[social][settings][social_advanced]"] { height: 160px; width: 275px; }
				.visual-settings fieldset > ul > li > label:first-child + input[type="number"],
				.visual-settings fieldset > ul > li > label:first-child + span > input[type="number"] { width: 70px; }
				.visual-settings fieldset > ul > li > label:first-child + span > input[type="number"][name*="opacity"] { width: 60px; }
		
				.visualise.toolbar.movable { top: 0px; right: 50%; }
				.visualise.toolbar.movable ul > li.settings { display: none; }
				
				.scope-storage ul > li > label:first-child + div > textarea[name=plain] { width: 400px; height: 300px; }';
		
		return $return;
		
	}
	
	public static function js() {
	
		$return = "SCRIPTER.static('.toolbar', function(elm_scripter) {
		
			var elm_visualisation = elm_scripter.children('ul.visualisation');
			
			elm_visualisation.on('command', '[id=y\\\:data_visualise\\\:visualise-0], [id=y\\\:data_visualise\\\:visualise_soc-0], [id=y\\\:data_visualise\\\:visualise_time-0]', function() {
				
				LOADER.keepAlive(this);
			}).on('click', '[id=y\\\:data_visualise\\\:visualise-0], [id=y\\\:data_visualise\\\:visualise_soc-0], [id=y\\\:data_visualise\\\:visualise_time-0]', function() {
				
				var cur = $(this);
				var type = (cur.is('[id^=y\\\:data_visualise\\\:visualise-]') ? 'plot' : (cur.is('[id^=y\\\:data_visualise\\\:visualise_soc-]') ? 'soc' : 'line'));
				
				var func_visualise = function() {
				
					if (type == 'plot') {
						
						if (is_same) {
						
							var obj_options = {};
							
							if (obj_data.data.center) {
								obj_options.default_center = obj_data.data.center.coordinates;
							}
							if (obj_data.data.zoom) {
								obj_options.default_zoom = obj_data.data.zoom;
							}
						} else {
						
							var arr_levels = [];
							
							for (var i = 1; i <= 18; i++) {
								arr_levels.push({width: 256 * Math.pow(2,i), height: 256 * Math.pow(2,i), tile_width: 256, tile_height: 256});
							}
							
							var attribution = obj_data.data.attribution;
							attribution = (attribution.source ? attribution.source+' - ' : '')+(obj_data.visual.settings.map_attribution ? obj_data.visual.settings.map_attribution+' - ' : '')+attribution.base;
							
							var obj_options = {
								call_class_paint: MapGeo,
								arr_class_paint_settings: {arr_visual: obj_data.visual},
								arr_class_data_settings: obj_data.data.options,
								arr_levels: arr_levels,
								tile_path: (!obj_data.visual.settings.geo_background_color ? obj_data.visual.settings.map_url : false),
								tile_subdomain_range: [1,2,3],
								attribution: attribution,
								background_color: obj_data.visual.settings.geo_background_color,
								allow_sizing: true,
								center_pointer: true
							};
							
							if (obj_data.data.center) {
								obj_options.default_center = obj_data.data.center.coordinates;
							}
							if (obj_data.data.zoom) {
								obj_options.default_zoom = obj_data.data.zoom;
							}
						}
					} else if (type == 'soc') {
					
						if (is_same) {
							
							var obj_options = {};
						} else {
						
							var arr_levels = [];
						
							if (obj_data.visual.social.settings.display == 2) {
								
								for (var i = 1; i <= 14; i++) {
									arr_levels.push({auto: true});
								}
							} else {
							
								for (var i = 1; i <= 14; i++) {
									arr_levels.push({width: 6000 * Math.pow(1.5,i), height: 3000 * Math.pow(1.5,i)});
								}
							}
						
							var attribution = obj_data.data.attribution;
							attribution = (attribution.source ? attribution.source+' - ' : '')+attribution.base;
														
							var obj_options = {
								call_class_paint: MapSocial,
								arr_class_paint_settings: {arr_visual: obj_data.visual},
								arr_class_data_settings: obj_data.data.options,
								arr_levels: arr_levels,
								tile_path: false,
								attribution: attribution,
								background_color: obj_data.visual.social.settings.background_color,
								allow_sizing: false,
								default_center: {x: 0.5, y: 0.5},
								default_zoom: 7,
								center_pointer: false
							};
						}
					} else if (type == 'line') {
					
						if (is_same) {
							
							var obj_options = {};
						} else {
							
							var arr_levels = [{width: 4000, height: 2000}];
							
							var attribution = obj_data.data.attribution;
							attribution = (attribution.source ? attribution.source+' - ' : '')+attribution.base;
							
							var obj_options = {
								call_class_paint: MapTimeline,
								arr_class_paint_settings: {arr_visual: obj_data.visual},
								arr_class_data_settings: obj_data.data.options,
								arr_levels: arr_levels,
								tile_path: false,
								attribution: attribution,
								background_color: false,
								allow_sizing: false,
								default_center: {x: 0.5, y: 0.5},
								default_zoom: 1,
								center_pointer: true
							};
						}
					}

					if (is_new) {
						obj_options.call_class_data = MapData;
					}
					if (is_new || has_new_data) {
						obj_options.arr_data = obj_data.data;
					}
					if (is_new) {
						obj_options.default_time = obj_data.data.time;
					}
					
					if (!is_same) {
						elm_overlay.children('.dialog').css('background-color', (obj_options.background_color ? obj_options.background_color : ''));
					}
					
					var obj_labmap = elm_map[0].labmap;
					
					if (!obj_labmap) {
					
						obj_labmap = new Map(elm_map);
						elm_map[0].labmap = obj_labmap;
					}
					
					obj_labmap.init(obj_options);
				};
				
				var obj_data = elm_visualisation[0].obj_data;
				
				var elm_overlay = $('body > .overlay');
				var elm_map = elm_overlay.find('.labmap');
				var is_new = true;
				var is_same = false;
				var has_new_data = false;
				
				if (elm_map.length) {
				
					is_new = false;
					is_same = elm_map.hasClass(type);
					elm_map.removeClass('plot soc line').addClass(type);
					elm_map.children('.controls').children('.geo, .soc').addClass('hide');
				}
				
				if (is_new) {
											
					if (obj_data) {
						COMMANDS.setData(cur[0], {identifier: obj_data.identifier});
					}
					
					COMMANDS.checkCacher(cur, 'quick', function(data) {
				
						if (!data) {
							return;
						}
						
						if (!obj_data || (obj_data.identifier.data != data.identifier.data || obj_data.identifier.date != data.identifier.date)) {
							obj_data = data;
							elm_visualisation[0].obj_data = obj_data;
							has_new_data = true;
						} else {
							for (var key in data) {
								$.extend(obj_data[key], data[key]);
							}
						}
						
						if (is_new) {
						
							elm_map = $(obj_data.html).addClass(type);
							
							var elm_placeholder = $('<ul class=\"hide\" />').insertBefore(elm_visualisation);

							var obj_overlay = new Overlay(document.body, elm_map, {
								sizing: 'full',
								call_close: function() {
									elm_visualisation.insertBefore(elm_placeholder);
									elm_placeholder.remove();
									var obj_labmap = elm_map[0].labmap;
									obj_labmap.close();
									elm_map = false;
								}
							});
							
							var elm_overlay = obj_overlay.getOverlay();
							setElementData(elm_overlay, 'mod', getModID(cur));
							elm_overlay.dataset.keep_focus = '1';

							var elm_container = $('<div class=\"visualise toolbar movable\"></div>').insertBefore(elm_map).append(elm_visualisation);
							elm_container.css({top: '-'+elm_visualisation.outerHeight()+'px', marginRight: '-'+(elm_visualisation.outerWidth()/2)+'px'});
							
							new ToolExtras(elm_map, {fullscreen: true, maximize: 'fixed', tools: true});
						}
						
						func_visualise();
						
						return elm_map;
					});
				} else {
				
					func_visualise();
				}
			});
		});
		
		SCRIPTER.dynamic('.review_data_selection', '.view_type_object');
		
		SCRIPTER.dynamic('.review_data_selection', function(elm_scripter) {
		
			elm_scripter.on('open', '> .tabs > div', function(e) {
				
				if (e.target != e.currentTarget) {
					return;
				}
				
				var elm_table = $(this).find('> table[id^=d\\\:data_view\\\:data-], > div > table[id^=d\\\:data_view\\\:data-]');

				if (!elm_table.length) {
					return;
				}
				
				COMMANDS.dataTableContinue(elm_table);
			});
		});
		
		SCRIPTER.dynamic('.labmap', function(elm_scripter) {

			elm_scripter.on('click.review', '[id=y\\\:data_visualise\\\:review_data-date]', function() {

				var cur = $(this);
				
				var obj_labmap = elm_scripter[0].labmap;
				var dateint_range = obj_labmap.getDateRange();
				var arr_data = obj_labmap.getData();

				var arr_type_object_ids = {};
				
				for (var type_id in arr_data.info.types) { // Prepare and order the Types list
					arr_type_object_ids[type_id] = {};
				}
				
				var arr_value = {use_visualise: true, type_object_ids: arr_type_object_ids};

				// Single date sub-objects
				for (var i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
					
					var date = arr_data.date.arr_loop[i];
					var in_range = (date >= dateint_range.min && date <= dateint_range.max);
					
					if (!in_range) {
						continue;
					}

					var arr_object_subs = arr_data.date[date];
					
					for (var j = 0, len_j = arr_object_subs.length; j < len_j; j++) {
					
						var object_sub_id = arr_object_subs[j];
						var arr_object_sub = arr_data.object_subs[object_sub_id];
						
						if (arr_object_sub.object_sub_details_id === 'object') { // Dummy sub-object
							continue;
						}
						
						var object_id = arr_object_sub.object_id;
						var type_id = arr_data.objects[object_id].type_id;
						arr_type_object_ids[type_id][object_id] = object_id;
						
						// Full objects and possible partake in scopes
						for (var i_connected = 0, len_i_connected = arr_object_sub.connected_object_ids.length; i_connected < len_i_connected; i_connected++) {
							
							var connected_object_id = arr_object_sub.connected_object_ids[i_connected];	
							var in_scope = (connected_object_id != object_id);
				
							if (in_scope) {			
								var type_id = arr_data.objects[connected_object_id].type_id;
								arr_type_object_ids[type_id][connected_object_id] = connected_object_id;
							}
						}						
					}
				}
				
				// Sub-objects with a date range
				for (var i = 0, len = arr_data.range.length; i < len; i++) {
					
					var object_sub_id = arr_data.range[i];
					var arr_object_sub = arr_data.object_subs[object_sub_id];
					
					if (arr_object_sub.object_sub_details_id === 'object') { // Dummy sub-object
						continue;
					}
					
					var in_range = ((arr_object_sub.date_start >= dateint_range.min && arr_object_sub.date_start <= dateint_range.max) || (arr_object_sub.date_end >= dateint_range.min && arr_object_sub.date_end <= dateint_range.max) || (arr_object_sub.date_start < dateint_range.min && arr_object_sub.date_end > dateint_range.max));
					
					if (!in_range) {
						continue;
					}
					
					var object_id = arr_object_sub.object_id;
					var type_id = arr_data.objects[object_id].type_id;
					arr_type_object_ids[type_id][object_id] = object_id;
					
					// Full objects and possible partake in scopes
					for (var i_connected = 0, len_i_connected = arr_object_sub.connected_object_ids.length; i_connected < len_i_connected; i_connected++) {
						
						var connected_object_id = arr_object_sub.connected_object_ids[i_connected];	
						var in_scope = (connected_object_id != object_id);
				
						if (in_scope) {		
							var type_id = arr_data.objects[connected_object_id].type_id;
							arr_type_object_ids[type_id][connected_object_id] = connected_object_id;
						}
					}		
				}
										
				cur.one('ajaxloaded', function(e) {
				
					delete arr_value.type_object_ids;
				
					var elm_tabs = e.detail.elm.children('.tabs');
					elm_tabs.find('table[id^=d\\\:data_view\\\:data-]').each(function() {
						
						var elm_table = $(this);
						
						if (elm_table.closest('.tabs')[0] !== elm_tabs[0]) {
							return;
						}
						
						var type_id = elm_table.attr('id').split('-')[1].split('_')[0];
						
						delete arr_value.object_ids;
						arr_value.object_ids = arr_type_object_ids[type_id];
						
						COMMANDS.setData(elm_table[0], arr_value);
					});
				});
				
				COMMANDS.setData(cur[0], arr_value);
				cur.popupCommand();
			}).on('click', '.paint', function(e) {
			
				var cur = $(this);
				var arr_link = cur[0].arr_link;
				
				if (!arr_link) {
					return;
				}

				var obj_labmap = elm_scripter[0].labmap;
				var arr_data = obj_labmap.getData();
				
				var arr_type_object_sub_ids = {};
				var arr_type_object_descriptions_object_ids = {};
				var arr_type_object_ids = {};
				
				for (var type_id in arr_data.info.types) { // Prepare and order the Types list
					arr_type_object_ids[type_id] = {};
				}
				
				if (arr_link.is_line) {
					arr_link.object_sub_ids = arrUnique(arr_link.object_sub_ids.concat(arr_link.connect_object_sub_ids));
				}
				if (!arr_link.object_ids) {
					arr_link.object_ids = [];
				}
				
				if (arr_link.object_sub_ids) { // Sub-objects
				
					for (var i = 0; i < arr_link.object_sub_ids.length; i++) {
					
						var object_sub_id = arr_link.object_sub_ids[i];
						var arr_object_sub = arr_data.object_subs[object_sub_id];
						var object_id = (arr_object_sub.original_object_id ? arr_object_sub.original_object_id : arr_object_sub.object_id);
						var type_id = arr_data.objects[object_id].type_id;
						var object_sub_details_id = arr_object_sub.object_sub_details_id;
						
						if (object_sub_details_id == 'unknown') {
							arr_link.object_ids.push(object_id);
						} else {
							var arr_type = arr_type_object_sub_ids[type_id];
							if (!arr_type) {
								arr_type = arr_type_object_sub_ids[type_id] = {};
							}
							var arr_type_object_sub = arr_type[object_sub_details_id];
							if (!arr_type_object_sub) {
								arr_type_object_sub = arr_type[object_sub_details_id] = {};
							}
							var arr_type_object_sub_object = arr_type_object_sub[object_id];
							if (!arr_type_object_sub_object) {
								arr_type_object_sub_object = arr_type_object_sub[object_id] = {};
							}
							arr_type_object_sub_object[object_sub_id] = object_sub_id;
						}
					}
				}
				if (arr_link.connect_object_ids) { // Object descriptions
				
					for (var i = 0; i < arr_link.connect_object_ids.length; i++) {
					
						var arr_object_link = arr_link.connect_object_ids[i];
						var type_id = arr_data.objects[arr_object_link.object_id].type_id;
						
						if (!arr_type_object_descriptions_object_ids[type_id]) {
							arr_type_object_descriptions_object_ids[type_id] = {};
						}
						if (!arr_type_object_descriptions_object_ids[type_id][arr_object_link.object_description_id]) {
							arr_type_object_descriptions_object_ids[type_id][arr_object_link.object_description_id] = {};
						}
						arr_type_object_descriptions_object_ids[type_id][arr_object_link.object_description_id][arr_object_link.object_id] = arr_object_link.object_id;
					}
				}
				if (arr_link.object_ids) { // Objects
				
					for (var i = 0; i < arr_link.object_ids.length; i++) {
					
						var object_id = arr_link.object_ids[i];
						var arr_object = arr_data.objects[object_id];
						var type_id = arr_object.type_id;
						
						arr_type_object_ids[type_id][object_id] = object_id;
					}
				}
				
				var arr_value = {use_visualise: true, info_box: cur[0].arr_info_box, type_id: arr_link.type_id, object_id: arr_link.object_id, type_object_ids: arr_type_object_ids, type_object_descriptions_object_ids: arr_type_object_descriptions_object_ids, type_object_sub_ids: arr_type_object_sub_ids};
	 
				cur.one('ajaxloaded', function(e) {
				
					delete arr_value.type_object_sub_ids;
					delete arr_value.type_object_descriptions_object_ids;
					delete arr_value.type_object_ids;
					
					var elm_tabs = e.detail.elm.children('.tabs');
					elm_tabs.find('table[id^=d\\\:data_view\\\:data-]').each(function() {
						
						var elm_table = $(this);
						
						if (elm_table.closest('.tabs')[0] !== elm_tabs[0]) {
							return;
						}
						
						var type_id = elm_table.attr('id').split('-')[1].split('_')[0];
						
						delete arr_value.object_sub_ids;
						delete arr_value.object_descriptions_object_ids;
						delete arr_value.object_ids;
						arr_value.object_sub_ids = arr_type_object_sub_ids[type_id];
						arr_value.object_descriptions_object_ids = arr_type_object_descriptions_object_ids[type_id];
						arr_value.object_ids = arr_type_object_ids[type_id];
						
						COMMANDS.setData(elm_table[0], arr_value);
					});
				});
				
				COMMANDS.setData(cur[0], arr_value);
				cur.attr('id', 'y:data_visualise:review_data-object').popupCommand();
			}).on('click.review', 'figure.types li, figure.object-sub-details li, figure.conditions li', function() {
				
				var cur = $(this);
				var elm_source = cur.closest('figure');
				
				var str_target = (elm_source.hasClass('conditions') ? 'condition' : (elm_source.hasClass('object-sub-details') ? 'object-sub-details' : 'type'));
				var str_identifier = this.dataset.identifier;
				
				var obj_labmap = elm_scripter[0].labmap;
				
				var state = (this.dataset.state == '1' || this.dataset.state === undefined ? false : true);
				this.dataset.state = (state ? '1' : '0');			
				
				obj_labmap.setDataState(str_target, str_identifier, state);
				obj_labmap.doDraw();
			});
		});
		
		// SETTINGS
				
		SCRIPTER.dynamic('[data-method=update_visualisation_settings]', 'select_geometry');
		
		SCRIPTER.dynamic('[data-method=update_visualisation_settings]', function(elm_scripter) {
		
			var elm_scope = elm_scripter.find('.network.type');
			
			SCRIPTER.runDynamic(elm_scope);
			
			elm_scripter.on('ajaxloaded scripter', function(e) {
				
				if (!getElement(e.detail.elm)) {
					return;
				}
					
				e.detail.elm.find('[name$=\"[dot][show]\"]:checked, [name$=\"[line][show]\"]:checked, [name$=\"[location][show]\"]:checked, [name$=\"[hint][show]\"]:checked, [name$=\"[geometry][show]\"]:checked, [name$=\"[geo_mode]\"]:checked').trigger('update_show_hide');
			}).on('click', '.visualise-settings .del + .add', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('addRow');
			}).on('click', '.visualise-settings .del', function() {
				$(this).closest('li').next('li').find('.sorter').first().sorter('clean');
			}).on('command', '.visualise-settings [id^=y\\\:data_visualise\\\:open_scope-]', function() {
				
				var cur = $(this);
				var elm_scope = cur.closest('.tabs').find('> div > .scope');
				
				COMMANDS.setTarget(this, elm_scope);				
			}).on('command', '.visualise-settings [id^=y\\\:data_visualise\\\:store_scope-]', function() {
				
				var cur = $(this);
				var elm_scope = cur.closest('.tabs').find('> div > .scope');
				
				var str_scope = JSON.stringify(serializeArrayByName(elm_scope));
				
				COMMANDS.setData(cur[0], {scope: str_scope});		
			}).on('change', '.visualise-settings [id=y\\\:data_visualise\\\:select_context_include_type-0] > select:first-child', function() {
				
				var cur = $(this);
				var elm_command = cur.parent();
				
				COMMANDS.setData(elm_command[0], {type_id: this.value});
				COMMANDS.setTarget(elm_command[0], cur.next());
				
				elm_command.quickCommand();
			}).on('command', '.visualise-settings [id^=y\\\:data_visualise\\\:open_context-]', function() {
				
				var cur = $(this);
				var elm_context = cur.closest('.context');
				
				COMMANDS.setTarget(this, elm_context);
				COMMANDS.setOptions(this, {html: 'replace', elm_container: elm_context.parent()});
			}).on('command', '.visualise-settings [id^=y\\\:data_visualise\\\:open_frame-]', function() {
				
				var cur = $(this);
				var elm_frame = cur.closest('.frame');
				
				COMMANDS.setTarget(this, elm_frame);
				COMMANDS.setOptions(this, {html: 'replace', elm_container: elm_frame.parent()});
			}).on('command', '.visualise-settings [id^=y\\\:data_visualise\\\:open_visual_settings-]', function() {
				
				var cur = $(this);
				var elm_visual_settings = cur.closest('.visual-settings');
				
				COMMANDS.setTarget(this, elm_visual_settings);
				COMMANDS.setOptions(this, {html: 'replace', elm_container: elm_visual_settings.parent()});				
			}).on('change update_show_hide', '.visualise-settings [name$=\"[dot][show]\"]', function() {
				
				var target = $('[name^=\"visual_settings[dot]\"]').not('[name$=\"[dot][show]\"]').closest('li');
				
				if ($(this).val() == 1) {
					target.show();
				} else {
					target.hide();
				}
			}).on('change update_show_hide', '.visualise-settings [name$=\"[line][show]\"]', function() {
				
				var target = $('[name^=\"visual_settings[line]\"]').not('[name$=\"[line][show]\"]').closest('li');
				
				if ($(this).val() == 1) {
					target.show();
				} else {
					target.hide();
				}
			}).on('change update_show_hide', '.visualise-settings [name$=\"[location][show]\"]', function() {
				
				var target = $('[name^=\"visual_settings[location]\"]').not('[name$=\"[location][show]\"]').closest('li');
				
				if ($(this).val() == 1) {
					target.show();
				} else {
					target.hide();
				}
			}).on('change update_show_hide', '.visualise-settings [name$=\"[hint][show]\"]', function() {
				
				var target = $('[name^=\"visual_settings[hint]\"]').not('[name$=\"[hint][show]\"]').closest('li');
				
				if ($(this).val() == 1) {
					target.show();
				} else {
					target.hide();
				}
			}).on('change update_show_hide', '.visualise-settings [name$=\"[geometry][show]\"]', function() {
				
				var target = $('[name^=\"visual_settings[geometry]\"]').not('[name$=\"[geometry][show]\"]').closest('li');
				
				if ($(this).val() == 1) {
					target.show();
				} else {
					target.hide();
				}
			}).on('change update_show_hide', '.visualise-settings [name$=\"[geo_mode]\"]:checked', function() {
				
				var cur = $(this);
				var target = $('[name$=\"[geo_display]\"]');
				
				if (cur.val() == 1) {
					target.closest('label').removeClass('hide');
				} else {
					target.closest('label').addClass('hide');
					target.filter('[value=2]').prop('checked', true).closest('label').removeClass('hide');
				}
			});
		});
		";
		
		return $return;
	}

	public function commands($method, $id, $value = "") {
	
		// INTERACT
		
		if ($method == "visualise" || $method == "visualise_soc" || $method == "visualise_time") {
			
			$arr_type_filters = toolbar::getFilter();
			$type_id = toolbar::getFilterTypeId();
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}

			$arr_filters = current($arr_type_filters);
			$arr_scope = self::getTypeScope($type_id);
			$arr_context = self::getTypeContext($type_id);
			$arr_conditions = toolbar::getTypeConditions($type_id);
			$arr_frame = self::getTypeFrame($type_id);
			$arr_visual_settings = self::getVisualSettings();

			$scenario_hash = toolbar::checkActiveScenario('visualise', $arr_filters, $arr_scope, $arr_conditions);
			$scenario_id = SiteStartVars::getFeedback('scenario_id');
			
			$identifier_data = $type_id.'_'.md5(serialize($arr_filters).'_'.serialize($arr_scope).'_'.serialize($arr_context).'_'.serialize($arr_conditions));
			$identifier_date = time();
			$has_data = ($value['identifier'] && $value['identifier']['data'] == $identifier_data);
			
			if ($has_data) {
				
				$is_updated = FilterTypeObjects::getTypesUpdatedSince($value['identifier']['date'], cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id']), true);
				
				if ($is_updated) {
					$has_data = false;
				} else {
					
					$arr_analyses_active = data_analysis::getTypeAnalysesActive($type_id);
					
					if ($arr_analyses_active) {
											
						$is_updated_analysis = FilterTypeObjects::getTypesAnalysesUpdatedSince($value['identifier']['date'], $arr_analyses_active);
						
						if ($is_updated_analysis) {
							$has_data = false;
						}
					}
				}
				
				if ($has_data) {
					
					$identifier_date = $value['identifier']['date'];
				}
			}
			
			$arr_types_all = StoreType::getTypes();
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);

			$create_visualisation_package = new createVisualisationPackage($arr_project, $arr_types_all, $arr_frame, $arr_visual_settings);
			
			$arr =& $this->html;
			
			$create_visualisation_package->setOutput($arr);

			if (!$has_data) {

				$collect = self::getVisualisationCollector($type_id, $arr_filters, $arr_scope, $arr_conditions);
				
				if ($scenario_hash) {
						
					$arr_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $scenario_id, $arr_use_project_ids);
					
					if ($arr_scenario['cache_retain']) {
						
						memoryBoost(2048);
						timeLimit(120);
					}
				}
				
				$create_visualisation_package->addType($type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $scenario_id, $scenario_hash);

				if ((!$arr_frame['object_subs']['unknown']['date'] && !$arr['data']['pack'][0]['object_subs']) || !$arr['data']['pack'][0]['objects']) { // No usable data
					
					$this->html = false;
					$this->msg = getLabel('msg_visualisation_not_set');
					
					return;
				}

				if ($arr_context['include']) {

					foreach ($arr_context['include'] as $arr_include) {

						$context_type_id = $arr_include['type_id'];
						$context_scenario_id = $arr_include['scenario_id'];
						$arr_context_scenario = cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], false, false, $context_scenario_id, $arr_use_project_ids);
						
						if (!$arr_context_scenario) {
							continue;
						}
						
						SiteEndVars::setFeedback('context', ['type_id' => $context_type_id], true);
						
						$arr_filters = toolbar::getScenarioFilters($context_scenario_id);
						
						$cur_scope_id = SiteStartVars::getFeedback('scope_id');
						SiteEndVars::setFeedback('scope_id', ($arr_context_scenario['scope_id'] ?: false), true);
						$arr_scope = self::getTypeScope($context_type_id);
						SiteEndVars::setFeedback('scope_id', $cur_scope_id, true);
						
						$cur_condition_id = SiteStartVars::getFeedback('condition_id');
						SiteEndVars::setFeedback('condition_id', ($arr_context_scenario['condition_id'] ?: false), true);
						$arr_conditions = toolbar::getTypeConditions($context_type_id);
						SiteEndVars::setFeedback('condition_id', $cur_condition_id, true);
						
						$collect = self::getVisualisationCollector($context_type_id, $arr_filters, $arr_scope, $arr_conditions);
						
						$context_scenario_hash = toolbar::getScenarioHash($context_scenario_id, $arr_filters, $arr_scope, $arr_conditions);
						
						$create_visualisation_package->addType($context_type_id, $collect, $arr_filters, $arr_scope, $arr_conditions, $context_scenario_id, $context_scenario_hash);
					}
					
					SiteEndVars::setFeedback('context', null, true);
				}
			}
			
			$create_visualisation_package->getPackage();
			
			$arr['identifier'] = ['data' => $identifier_data, 'date' => $identifier_date];
		}
		
		if ($method == "edit_visualisation_settings") {
			
			$type_id = toolbar::getFilterTypeId();
			
			$arr_visual_settings = self::getVisualSettings();
			$arr_scope = self::getTypeScope($type_id);
			$arr_context = self::getTypeContext($type_id);
			$arr_frame = self::getTypeFrame($type_id);
		
			$this->html = '<form data-method="update_visualisation_settings">'
				.$this->createVisualisationSettings($type_id, $arr_scope, $arr_context, $arr_frame, $arr_visual_settings)
				.'<input type="submit" data-tab="scope" name="default_scope" value="'.getLabel('lbl_remove').' '.getLabel('lbl_scope').'" />'
				.'<input type="submit" data-tab="context" name="default_context" value="'.getLabel('lbl_default').' '.getLabel('lbl_context').'" />'
				.'<input type="submit" data-tab="frame" name="default_frame" value="'.getLabel('lbl_default').' '.getLabel('lbl_frame').'" />'
				.'<input type="submit" data-tab="visual-settings" name="default_visual_settings" value="'.getLabel('lbl_default').' '.getLabel('lbl_visual_settings').'" />'
				.'<input type="submit" value="'.getLabel('lbl_save').' '.getLabel('lbl_settings').'" />'
			.'</form>';
		}		
		
		if ($method == "update_visualisation_settings") {
			
			$type_id = toolbar::getFilterTypeId();
			
			if ($_POST['default_scope']) {
				
				$arr_scope = [];
				
				SiteEndVars::setFeedback('scope_id', false, true);
			} else {
				
				$arr_scope = data_model::parseTypeNetwork($_POST['scope']);
				
				SiteEndVars::setFeedback('scope_id', 0, true);
			}
			
			cms_nodegoat_custom_projects::handleProjectTypeScope($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_scope);
			
			if ($_POST['default_context']) {
				
				$arr_context = [];
				
				SiteEndVars::setFeedback('context_id', false, true);
			} else {
												
				$arr_context = data_model::parseTypeContext($type_id, $_POST['context']);
				
				if (self::getTypeContext($type_id, false) == $arr_context) {
					$arr_context = [];
				}
				
				SiteEndVars::setFeedback('context_id', 0, true);
			}
			
			cms_nodegoat_custom_projects::handleProjectTypeContext($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_context);
			
			if ($_POST['default_frame']) {
				
				$arr_frame = [];
				
				SiteEndVars::setFeedback('frame_id', false, true);
			} else {
												
				$arr_frame = cms_nodegoat_custom_projects::parseFrame($_POST['frame']);
				
				if (self::getTypeFrame($type_id, false) == $arr_frame) {
					$arr_frame = [];
				}
				
				SiteEndVars::setFeedback('frame_id', 0, true);
			}
			
			cms_nodegoat_custom_projects::handleProjectTypeFrame($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, $type_id, [], $arr_frame);
									
			if ($_POST['default_visual_settings']) {
				
				$arr_visual_settings = [];
				
				SiteEndVars::setFeedback('visual_settings_id', false, true);
			} else {
				
				$arr_visual_settings = $_POST['visual_settings'];
				
				$func_parse_advanced = function($str) {
					
					$arr = [];
					
					if ($str) {
					
						$arr_settings = explode(PHP_EOL, $str);
						
						foreach ($arr_settings as $value) {
							
							$arr_setting = explode(':', $value);
							$key_setting = trim($arr_setting[0]);
							$value_setting = trim($arr_setting[1]);
							
							if ($key_setting && $value_setting != '') {
								$arr[$key_setting] = $value_setting;
							}
						}
					}
					
					return $arr;
				};
				
				$arr_visual_settings['settings']['geo_advanced'] = $func_parse_advanced($arr_visual_settings['settings']['geo_advanced']);		
				$arr_visual_settings['social']['settings']['social_advanced'] = $func_parse_advanced($arr_visual_settings['social']['settings']['social_advanced']);
				
				$arr_visual_settings = cms_nodegoat_custom_projects::parseVisualSettings($arr_visual_settings);

				if (self::getVisualSettings(false) == $arr_visual_settings) {
					$arr_visual_settings = [];
				}
				
				SiteEndVars::setFeedback('visual_settings_id', 0, true);
			}
			
			cms_nodegoat_custom_projects::handleProjectVisualSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], 0, [], $arr_visual_settings);
			
			toolbar::checkActiveSettings();
									
			$this->msg = true;
		}
		
		if ($method == "review_data") {
			
			$type_id = toolbar::getFilterTypeId();
			
			if (!custom_projects::checkAccesType($type_id)) {
				return;
			}

			$arr_value = ($value ?: []);
			
			$arr_type_objects = [];
			
			if ($arr_value['date_range']) { // Use active filters to evaluate results
				
				$arr_filters = current(toolbar::getFilter());
				$collect = self::getVisualisationCollector($type_id, $arr_filters, data_visualise::getTypeScope($type_id));
				$arr_collect_info = $collect->getResultInfo();
					
				foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
					foreach ($arr_paths as $path) {
											
						$filter = new FilterTypeObjects($cur_type_id, 'id');
						$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id'])]);
						
						$filter->setFilter($arr_collect_info['filters'][$path]);
						
						$arr_filter_set = [];
					
						$arr_filter_set['date_int'] = ['start' => $arr_value['date_range']['min'], 'end' => $arr_value['date_range']['max']];

						$filter->setFilter($arr_filter_set);
						$filter->setLimit(2);
						
						$arr_objects = $filter->init();
						
						if ($arr_objects) {
							
							$arr_type_objects[$cur_type_id] = $arr_objects;
						}
					}
				}
			} else {
				
				$arr_type_filter_set = [];
				
				foreach ((array)$arr_value['type_object_sub_ids'] as $cur_type_id => $arr_object_sub_ids) {
					foreach ($arr_object_sub_ids as $object_sub_details_id => $arr_objects) {
						
						if (!$arr_objects) {
							continue;
						}
						
						$arr_type_filter_set[$cur_type_id]['objects'] = array_merge((array)$arr_type_filter_set[$cur_type_id]['objects'], array_keys($arr_objects));
						
						/*foreach ($arr_objects as $object_id => $arr_object_sub_ids) {
							$arr_type_filter_set[$cur_type_id]['object_subs'] = array_merge((array)$arr_type_filter_set[$cur_type_id]['object_subs'], $arr_object_sub_ids);
						}*/
					}
				}
				
				foreach ((array)$arr_value['type_object_descriptions_object_ids'] as $cur_type_id => $arr_object_descriptions_object_ids) {
					foreach ($arr_object_descriptions_object_ids as $object_description_id => $arr_object_ids) {
						
						if (!$arr_object_ids) {
							continue;
						}
							
						$arr_type_filter_set[$cur_type_id]['objects'] = array_merge((array)$arr_type_filter_set[$cur_type_id]['objects'], $arr_object_ids);
					}
				}
				
				foreach ((array)$arr_value['type_object_ids'] as $cur_type_id => $arr_object_ids) {
					
					if (!$arr_object_ids) {
						continue;
					}
					
					$arr_type_filter_set[$cur_type_id]['objects'] = array_merge((array)$arr_type_filter_set[$cur_type_id]['objects'], $arr_object_ids);
				}
				
				foreach ($arr_type_filter_set as $cur_type_id => $arr_filter_set) {

					$filter = new FilterTypeObjects($cur_type_id, 'id');
					$filter->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id'])]);

					$filter->setFilter($arr_filter_set);
					$filter->setLimit(2);
					
					$arr_objects = $filter->init();
					
					if ($arr_objects) {
						
						$arr_type_objects[$cur_type_id] = $arr_objects;
					}
				}
			}
			
			if ($arr_type_objects) {

				$arr_html_tabs = [];
				
				$arr_types = StoreType::getTypes(array_keys($arr_type_objects));
				
				if ($arr_types[$type_id]) { // Put the active Type first
					
					$arr_types_all = $arr_types;
					$arr_types = [];
					$arr_types[$type_id] = $arr_types_all[$type_id];
					$arr_types += $arr_types_all;
				}

				foreach ($arr_types as $cur_type_id => $arr_type) {
					
					$arr_objects = $arr_type_objects[$cur_type_id];
										
					$arr_html_tabs['links'][$cur_type_id] = '<li><a href="#tab-review-'.$cur_type_id.'">'.Labels::parseTextVariables($arr_type['name']).'</a></li>';
					
					$return_tab = '<div id="tab-review-'.$cur_type_id.'">';
					
					if (count($arr_objects) == 1) {
						
						$return_tab .= data_view::createViewTypeObject($cur_type_id, key($arr_objects));
					} else {
						
						$return_tab .= data_view::createViewTypeObjects($cur_type_id, [], true);
					}
					
					$return_tab .= '</div>';
					
					$arr_html_tabs['content'][$cur_type_id] = $return_tab;
				}
				
				if ($arr_value['info_box']['name']) {
					$name = $arr_value['info_box']['name'];
				} else {
					$name = getLabel('lbl_in_selection');
				}
				
				$this->html = '<div class="review_data_selection data_viewer">
					<h1>'.htmlspecialchars($name).'</h1>
					<div class="record"><dl>
						'.($arr_value['object_id'] ? '<li><dt>'.getLabel('lbl_object').'</dt><dd>'.data_view::createTypeObjectLink($arr_value['type_id'], $arr_value['object_id'], $name).'<span class="icon">'.getIcon('link').'</span></dd></li>' : '').'
						'.($arr_value['date_range'] ? '<li><dt>'.getLabel('lbl_date_range').'</dt><dd>'.StoreTypeObjects::formatToCleanValue('date', $arr_value['date_range']['min']).' - '.StoreTypeObjects::formatToCleanValue('date', $arr_value['date_range']['max']).'</dd></li>' : '').'
					</dl></div>
					<div class="tabs">
						<ul>
							'.implode('', $arr_html_tabs['links']).'
						</ul>
						'.implode('', $arr_html_tabs['content']).'
					</div>
				</div>';
			}
		}
		
		if ($method == "store_scope") {
			
			$arr_scope = json_decode($value['scope'], true);
			$arr_scope = $arr_scope['scope'];
			
			$this->html = '<form class="scope-storage storage">
				'.$this->createSelectScope($id, $arr_scope).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_scope") {
			
			$this->html = '<form class="scope-storage" data-method="return_scope">
				'.$this->createSelectScope($id).'
				<input class="hide" type="submit" name="" value="" />
				<input data-tab="storage" type="submit" name="select" value="'.getLabel('lbl_select').'" />
				<input data-tab="advanced" type="submit" name="apply" value="'.getLabel('lbl_apply').'" />
			</form>';
		}
		
		if ($method == "return_scope") {
			
			if ($_POST['select']) {
				
				if ($_POST['scope_id']) {
				
					$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
					
					$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($_SESSION['custom_projects']['project_id'], false, false, $_POST['scope_id'], $arr_use_project_ids);
					$arr_scope = $arr_scope['object'];
					$arr_scope = data_model::parseTypeNetwork($arr_scope);
				}
			} else {
				
				$arr_scope = json_decode($_POST['plain'], true);
				$arr_scope = data_model::parseTypeNetwork($arr_scope);
			}

			$this->html = data_model::createTypeNetwork($id, false, false, ['references' => 'both', 'network' => ['dynamic' => true, 'object_sub_locations' => true], 'value' => $arr_scope, 'name' => 'scope', 'descriptions' => 'combine']);
		}
		
		if ($method == "select_context_include_type") {
			
			$type_id = $value['type_id'];
			
			$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
			$arr_use_project_ids = array_keys($arr_project['use_projects']);
			
			$arr_scenarios = ($type_id ? cms_nodegoat_custom_projects::getProjectTypeScenarios($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, false, $arr_use_project_ids) : []);
			
			$this->html = Labels::parseTextVariables(cms_general::createDropdown($arr_scenarios, false, true, 'label'));
		}
		
		if ($method == "store_context") {
			
			$this->html = '<form class="options storage">
				'.$this->createSelectContext($id, true).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_context") {
			
			$this->html = '<form class="options storage" data-method="return_context">
				'.$this->createSelectContext($id).'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "return_context") {
			
			if ($_POST['context_id']) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_context = cms_nodegoat_custom_projects::getProjectTypeContexts($_SESSION['custom_projects']['project_id'], false, false, $_POST['context_id'], $arr_use_project_ids);
				$arr_context = $arr_context['object'];
				$arr_context = data_model::parseTypeContext($id, $arr_context);
			} else {
				
				$arr_context = [];
			}

			$this->html = $this->createContext($id, $arr_context);
		}
		
		if ($method == "store_frame") {
			
			$this->html = '<form class="options storage">
				'.$this->createSelectFrame($id, true).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_frame") {
			
			$this->html = '<form class="options storage" data-method="return_frame">
				'.$this->createSelectFrame($id).'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "return_frame") {
			
			if ($_POST['frame_id']) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_frame = cms_nodegoat_custom_projects::getProjectTypeFrames($_SESSION['custom_projects']['project_id'], false, false, $_POST['frame_id'], $arr_use_project_ids);
				$arr_frame = $arr_frame['settings'];
			}
			$arr_frame = cms_nodegoat_custom_projects::parseFrame($arr_frame);
			
			$this->html = $this->createFrame($id, $arr_frame);
		}
		
		if ($method == "store_visual_settings") {
			
			$this->html = '<form class="options storage">
				'.$this->createSelectVisualSettings(true).'
				<input class="hide" type="submit" name="" value="" />
				<input type="submit" name="discard" value="'.getLabel('lbl_close').'" />
			</form>';
		}
		
		if ($method == "open_visual_settings") {
			
			$this->html = '<form class="options storage" data-method="return_visual_settings">
				'.$this->createSelectVisualSettings().'
				<input type="submit" value="'.getLabel('lbl_select').'" />
			</form>';
		}
		
		if ($method == "return_visual_settings") {
			
			if ($_POST['visual_settings_id']) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
				
				$arr_visual_settings = cms_nodegoat_custom_projects::getProjectVisualSettings($_SESSION['custom_projects']['project_id'], false, $_POST['visual_settings_id'], $arr_use_project_ids);
				$arr_visual_settings = $arr_visual_settings['settings'];
			}
			
			$arr_visual_settings = cms_nodegoat_custom_projects::parseVisualSettings($arr_visual_settings);
						
			$this->html = $this->createVisualSettings($arr_visual_settings);
		}
	}
	
	public static function getVisualisationCollector($type_id, $arr_filters, $arr_scope, $arr_conditions = false) {
				
		$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
		$arr_use_project_ids = array_keys($arr_project['use_projects']);
		
		if ($arr_scope['paths']) {
						
			$trace = new TraceTypeNetwork(array_keys($arr_project['types']), true, true);
			$trace->filterTypeNetwork($arr_scope['paths']);
			$trace->run($type_id, false, 3);
			$arr_type_network_paths = $trace->getTypeNetworkPaths(true);
		} else {
			$arr_type_network_paths = ['start' => [$type_id => ['path' => [0]]]];
		}
		
		$collect = new CollectTypeObjects($arr_type_network_paths, 'visualise');
		$collect->setScope(['users' => $_SESSION['USER_ID'], 'types' => cms_nodegoat_custom_projects::getProjectScopeTypes($_SESSION['custom_projects']['project_id'])]);
		$collect->setConditions('visual', function($cur_type_id) use ($type_id, $arr_conditions) {
			
			if ($cur_type_id == $type_id && $arr_conditions !== false) {
				return $arr_conditions;
			}
			
			return toolbar::getTypeConditions($cur_type_id);
		});
		$collect->init($arr_filters, false);
			
		$arr_collect_info = $collect->getResultInfo();
		
		foreach ($arr_collect_info['types'] as $cur_type_id => $arr_paths) {
			
			$arr_type_set = StoreType::getTypeSet($cur_type_id);
			
			if ($arr_project['types'][$cur_type_id]['type_filter_id']) {
				
				$arr_project_filters = cms_nodegoat_custom_projects::getProjectTypeFilters($_SESSION['custom_projects']['project_id'], false, false, $arr_project['types'][$cur_type_id]['type_filter_id'], true, $arr_use_project_ids);
				$collect->addLimitTypeFilters($cur_type_id, FilterTypeObjects::convertFilterInput($arr_project_filters['object']), $arr_project['types'][$cur_type_id]['type_filter_object_subs']);
			}
			
			foreach ($arr_paths as $path) {
				
				$source_path = $path;
				if ($source_path) { // path includes the target type id, remove it
					$source_path = explode('-', $source_path);
					array_pop($source_path);
					$source_path = implode('-', $source_path);
				}
				
				$arr_settings = $arr_scope['types'][$source_path][$cur_type_id];
				
				$arr_filtering = [];
				if ($arr_settings['filter']) {
					$arr_filtering = ['all' => true];
				}
				
				$collapse = $arr_settings['collapse'];
				$arr_in_selection = ($arr_settings['selection'] ?: []);
				
				$arr_selection = [
					'object' => true,
					'object_descriptions' => [],
					'object_sub_details' => []
				];
				
				if ($arr_in_selection || $arr_settings['object_only']) {
										
					foreach ($arr_in_selection as $id => $arr_selected) {
						
						$object_description_id = $arr_selected['object_description_id'];
						
						if ($object_description_id) {
							
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_descriptions'][$object_description_id]['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, $object_description_id)) {
								continue;
							}
							
							$s_arr =& $arr_selection['object_descriptions'][$object_description_id];
							$s_arr['object_description_id'] = true;
							
							if ($arr_selected['object_description_value']) {
								$s_arr['object_description_value'] = true;
							}
							if ($arr_selected['object_description_reference']) {
								$s_arr['object_description_reference'] = $arr_selected['object_description_reference'];
							}
						}
						
						$object_sub_details_id = $arr_selected['object_sub_details_id'];
						
						if ($object_sub_details_id) {

							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, false, $object_sub_details_id)) {
								continue;
							}
							
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_details'] = ['all' => true];
							
							$object_sub_description_id = $arr_selected['object_sub_description_id'];
							
							if ($object_sub_description_id) {

								if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_type_set['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id]['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
									continue;
								}
								
								$s_arr =& $arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id];
								$s_arr['object_sub_description_id'] = true;
								
								if ($arr_selected['object_sub_description_value']) {
									$s_arr['object_sub_description_value'] = true;
								}
								if ($arr_selected['object_sub_description_reference']) {
									$s_arr['object_sub_description_reference'] = $arr_selected['object_sub_description_reference'];
								}
							} else if (!$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions']) { // Set empty selection on sub object descriptions if there are none selected
								
								$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'] = [];
							}
						}
					}
					unset($s_arr);
				} else { // Nothing selected, use default
					
					foreach ($arr_type_set['object_descriptions'] as $object_description_id => $arr_object_description) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_description['object_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, $object_description_id)) {
							continue;
						}
						
						if (!($arr_object_description['object_description_ref_type_id'] || $arr_object_description['object_description_is_dynamic'])) {
							continue;
						}
						
						$arr_selection['object_descriptions'][$object_description_id] = $object_description_id;
					}
								
					foreach ($arr_type_set['object_sub_details'] as $object_sub_details_id => $arr_object_sub_details) {
						
						if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_details['object_sub_details']['object_sub_details_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, false, $object_sub_details_id)) {
							continue;
						}

						$arr_selection['object_sub_details'][$object_sub_details_id] = ['object_sub_details' => true, 'object_sub_descriptions' => []];

						foreach ($arr_object_sub_details['object_sub_descriptions'] as $object_sub_description_id => $arr_object_sub_description) {
													
							if ($_SESSION['NODEGOAT_CLEARANCE'] < $arr_object_sub_description['object_sub_description_clearance_view'] || !custom_projects::checkClearanceTypeConfiguration('view', $arr_project['types'][$cur_type_id], $arr_type_set, false, $object_sub_details_id, $object_sub_description_id)) {
								continue;
							}
								
							$arr_selection['object_sub_details'][$object_sub_details_id]['object_sub_descriptions'][$object_sub_description_id] = $object_sub_description_id;
						}
					}
				}
								
				$collect->setPathOptions([$path => [
					'arr_selection' => $arr_selection,
					'arr_filtering' => $arr_filtering,
					'collapse' => $collapse
				]]);
			}
		}
		
		return $collect;
	}
		
	public static function getTypeScope($type_id) {
		
		$scope_id = SiteStartVars::getFeedback('scope_id');
		
		if ($scope_id !== false) {
			
			// Interaction settings
			if ($scope_id) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
			} else {
				
				$scope_id = 0;
				$arr_use_project_ids = [];
			}
		
			$arr_scope = cms_nodegoat_custom_projects::getProjectTypeScopes($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $scope_id, $arr_use_project_ids);
			$arr_scope = $arr_scope['object'];
		} else {
			
			$arr_scope = [];
		}
		
		$arr_scope = data_model::parseTypeNetwork($arr_scope);
		
		return $arr_scope;
	}
	
	public static function getTypeContext($type_id, $include_user = true) {
		
		$context_id = SiteStartVars::getFeedback('context_id');
		$arr_context = [];
		
		if ($context_id !== false) {
			
			// User-oriented settings
			if ($include_user) {
					
				// Interaction settings
				if ($context_id) {
					
					$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
				} else {
					
					$context_id = 0;
					$arr_use_project_ids = [];
				}
			
				$arr_context = cms_nodegoat_custom_projects::getProjectTypeContexts($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $context_id, $arr_use_project_ids);
				$arr_context = $arr_context['object'];
			}
		}
			
		// Project settings
		if (!$arr_context) {
			
			if (!$arr_project) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
			}
			
			if ($arr_project['types'][$type_id]['type_context_id']) { // Project settings
									
				$arr_context = cms_nodegoat_custom_projects::getProjectTypeContexts($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $arr_project['types'][$type_id]['type_context_id'], $arr_use_project_ids);
				$arr_context = $arr_context['object'];
			}
		}
		
		$arr_context = data_model::parseTypeContext($type_id, $arr_context);

		return $arr_context;
	}
	
	public static function getTypeFrame($type_id, $include_user = true) {
		
		$frame_id = SiteStartVars::getFeedback('frame_id');
		$arr_frame = [];
		
		if ($frame_id !== false) {
			
			// User-oriented settings
			if ($include_user) {
					
				// Interaction settings
				if ($frame_id) {
					
					$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
				} else {
					
					$frame_id = 0;
					$arr_use_project_ids = [];
				}
			
				$arr_frame = cms_nodegoat_custom_projects::getProjectTypeFrames($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $frame_id, $arr_use_project_ids);
				$arr_frame = (array)$arr_frame['settings'];
				
				if (!array_filter($arr_frame)) { // User settings are empty
					$arr_frame = [];
				}
			}
		}
			
		// Project settings
		if (!$arr_frame) {
			
			if (!$arr_project) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
			}
			
			if ($arr_project['types'][$type_id]['type_frame_id']) { // Project settings
									
				$arr_frame = cms_nodegoat_custom_projects::getProjectTypeFrames($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $type_id, $arr_project['types'][$type_id]['type_frame_id'], $arr_use_project_ids);
				$arr_frame = $arr_frame['settings'];
			}
		}
		
		$arr_frame = cms_nodegoat_custom_projects::parseFrame($arr_frame);

		return $arr_frame;
	}
	
	public static function getVisualSettings($include_user = true) {
		
		$visual_settings_id = SiteStartVars::getFeedback('visual_settings_id');
		$arr_visual_settings = [];
		
		if ($visual_settings_id !== false) {
			
			// User-oriented settings
			if ($include_user) {
				
				// Interaction settings
				if ($visual_settings_id) {
										
					$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
					$arr_use_project_ids = array_keys($arr_project['use_projects']);
				} else {
					
					$visual_settings_id = 0;
					$arr_use_project_ids = [];
				}
				
				$arr_visual_settings = cms_nodegoat_custom_projects::getProjectVisualSettings($_SESSION['custom_projects']['project_id'], $_SESSION['USER_ID'], $visual_settings_id, $arr_use_project_ids);
				$arr_visual_settings = $arr_visual_settings['settings'];
				
				if ($arr_visual_settings) {
					
					$arr_visual_settings = cms_nodegoat_custom_projects::parseVisualSettings($arr_visual_settings);
					$arr_visual_settings_default = cms_nodegoat_custom_projects::parseVisualSettings();
					
					if ($arr_visual_settings == $arr_visual_settings_default) {
					
						$arr_visual_settings = [];
					}
				}
			}
		}
		
		// Project settings
		if (!$arr_visual_settings) {
			
			if (!$arr_project) {
				
				$arr_project = cms_nodegoat_custom_projects::getProjects($_SESSION['custom_projects']['project_id']);
				$arr_use_project_ids = array_keys($arr_project['use_projects']);
			}
			
			if ($arr_project['project']['visual_settings_id']) {
									
				$arr_visual_settings = cms_nodegoat_custom_projects::getProjectVisualSettings($_SESSION['custom_projects']['project_id'], false, $arr_project['project']['visual_settings_id'], $arr_use_project_ids);
				$arr_visual_settings = $arr_visual_settings['settings'];
			}
			
			$arr_visual_settings = cms_nodegoat_custom_projects::parseVisualSettings($arr_visual_settings);
		}
		
		return $arr_visual_settings;
	}
}
