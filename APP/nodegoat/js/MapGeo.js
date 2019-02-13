
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapGeo(element, obj_parent, options) {

	var elm = $(element),
	obj = this,
	settings = $.extend({
		arr_visual: false
	}, options || {});

	var	arr_object_sub_dots = {},
	arr_loop_object_sub_dots = [],
	arr_object_sub_lines = {},
	arr_loop_object_sub_lines = {},
	arr_data = {},
	elm_plot_geometry = false,
	elm_plot_lines = false,
	elm_plot_dots = false,
	elm_plot_connection_lines = false,
	elm_plot_between = false,
	elm_plot_locations = false,
	elm_container_info_lines = false,
	elm_container_info_dots = false,
	arr_elm_plot_line_particles = {},
	arr_elm_plot_line_particles_temp = {},
	fragment_plot_locations = false,
	fragment_plot_lines = false,
	fragment_plot_dots = false,
	fragment_plot_geometry = false,
	fragment_plot_between = false,
	renderer = false,
	renderer_geometry = false,
	renderer_connection_lines = false,
	renderer_locations = false,
	renderer_lines = false,
	renderer_activity = false,
	renderer_dots = false,
	stage = false,
	stage_geometry = false,
	stage_connection_lines = false,
	stage_locations = false,
	stage_lines = false,
	stage_activity = false,
	stage_dots = false,
	drawer = false,
	drawer_defs = false,
	drawer_2 = false,
	drawer_3 = false,
	drawer_4 = false,
	drawer_5 = false,
	stage_ns = 'http://www.w3.org/2000/svg',
	size_renderer = {},
	do_render_geometry = false,
	do_render_locations = false,
	do_render_connection_lines = false,
	do_render_dots = false,
	redraw = false,
	predraw = false,
	draw = false,
	cur_zoom = false,
	dateint_range = false,
	count_loop = 0,
	count_animate_locations = 0,
	count_animate_between = 0,
	count_animate_connection_lines = 0,
	count_hint_object_sub_dots = 0,
	count_info_show_object_sub_lines = 0,
	count_info_show_object_sub_lines_hover = 0,
	count_info_show_object_sub_dots = 0,
	count_info_show_object_sub_dots_hover = 0,
	key_move = false,
	key_animate = false,
	animating = false,
	dragging = false,
	elm_hover = false,
	
	display = 'vector',
	show_dot = false,
	dot_icon = 'circle',
	color_dot = false,
	opacity_dot = false,
	dot_color_condition = false,
	size_dot = false,
	width_dot_stroke = false,
	color_dot_stroke = false,
	opacity_dot_stroke = false,
	show_hint = false,
	color_hint = false,
	opacity_hint = false,
	size_hint = false,
	duration_hint = false,
	width_hint_stroke = false,
	color_hint_stroke = false,
	opacity_hint_stroke = false,
	show_line = false,
	width_line_min = 2,
	width_line_max = 10,
	offset_line = 6,
	color_line = false,
	opacity_line = false,
	opacity_connection_line = 0.4,
	opacity_connection_line_range_min = 0.5,
	opacity_connection_line_range_max = 1,
	opacity_connection_line_range = false,
	move_apply_opacity_connection_line = 'moved', // 'move', 'moved'
	show_info_line = true, // false, true, int
	show_info_dot = true, // false, true, int
	color_info = '#000000',
	info_condition = false,
	info_mode = 'hover', // 'all', 'hover'
	info_content = 'default', // 'default', 'scope', 'condition'
	hint_dot = 'pulse', // false, 'pulse', 'location'
	hint_line = false, // false, true
	move_hint_dot = true, // false, true
	moved_hint_dot = false, // false, true
	moved_hint_line = false, // false, true
	max_hint_dots = 100,
	length_move_warp = 8,
	move_unit = 'pixel', // 'pixel', 'day'
	speed_move = 30, // Units per second
	duration_move_min = false, // Seconds
	duration_move_max = false, // Seconds
	duration_move_info_min = false, // Seconds
	duration_move_info_max = false, // Seconds
	duration_move_info_fade = 1.2, // Second
	duration_info_dot_min = 5, // Seconds
	duration_info_dot_fade = 1.2, // Seconds
	move_continuous = true,
	move_retain = false, // false, 'single', 'all'
	move_chronological = false,
	move_glow = false,
	mode = 'connect',
	show_location = false,
	color_location = false,
	size_location = false,
	threshold_location = false,
	location_condition = false,
	offset_location = -5,
	show_geo_info = false,
	show_geometry = false,
	color_geometry = false,
	opacity_geometry = false,
	width_geometry_stroke = false,
	color_geometry_stroke = false,
	opacity_geometry_stroke = false,
	opacity_geometry = false,
	size_dot_icons = 15,
	offset_dot_icons = 4,
	
	calc_plot_point = 1,
	pos_offset_x = 0,
	pos_offset_y = 0,
	pos_offset_extra_x = 0,
	pos_offset_extra_y = 0,
	pos_origin = {},
	
	arr_animate_move = [],
	time_animate = 0,
	time_poll = 0,
	pos_hover_poll = {x: 0, y: 0},
	do_show_info_line = false,
	do_show_info_dot = false,

	arr_assets_colors_obj = {},
	arr_assets_elm_line_dot_paths = {},
	arr_assets_texture_line_dots = {},
	arr_assets_texture_info = {},
	arr_assets_texture_icons = {},
	func_interact_stop = function() {},
	count_dot_weight_min = 0,
	count_dot_weight_max = 0,
	count_line_weight_max = 0,
	spacer_elm_info = 2,
	spacer_elm_icons = 6,
	size_info_font = 8,
	offset_info_hover = 5,
	size_max_elm_container = 15000,
	perc_hint_location_start = 0.5,
	
	obj_map = false,
	obj_data = false,
	settings_timeline = false,
	
	GeoUtilities = new MapGeoUtilities();

	this.init = function() {

		ASSETS.fetch({script: [
				'/CMS/js/pixi.min.js',
				'/CMS/js/pixi.ExtraFilters.js',
				'/CMS/js/Bezier.js'
			], font: [
				'pixel'
			]}, function() {
			
			obj_map = obj_parent.obj_map;
			obj_data = obj_parent.obj_data;
			
			display = (options.arr_visual.settings.geo_display == 2 ? 'pixel' : 'vector');
			mode = (options.arr_visual.settings.geo_mode == 2 ? 'move' : 'connect');
			
			addListeners();
			
			var parseBool = function(value, loose) {
				if (value == 'true') {
					return true;
				} else if (value == 'false') {
					return false;
				} else if (loose) {
					return value;
				} else {
					return false;
				}
			};
			
			if (typeof options.arr_visual.settings.geo_advanced.move_continuous != 'undefined') {
				move_continuous = parseBool(options.arr_visual.settings.geo_advanced.move_continuous);
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_retain != 'undefined') {
				move_retain = parseBool(options.arr_visual.settings.geo_advanced.move_retain, true);
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_unit != 'undefined') {
				move_unit = options.arr_visual.settings.geo_advanced.move_unit;
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_chronological != 'undefined') {
				move_chronological = parseBool(options.arr_visual.settings.geo_advanced.move_chronological);
			}
			if (move_chronological) {
				move_continuous = false;
				move_retain = false;
				move_unit = 'day';
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_speed != 'undefined' && parseBool(options.arr_visual.settings.geo_advanced.move_speed, true)) {
				speed_move = parseFloat(options.arr_visual.settings.geo_advanced.move_speed);
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_duration_min != 'undefined' && parseBool(options.arr_visual.settings.geo_advanced.move_duration_min, true)) {
				duration_move_min = parseFloat(options.arr_visual.settings.geo_advanced.move_duration_min);
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_duration_max != 'undefined' && parseBool(options.arr_visual.settings.geo_advanced.move_duration_max, true)) {
				duration_move_max = parseFloat(options.arr_visual.settings.geo_advanced.move_duration_max);
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_duration_info_min != 'undefined' && parseBool(options.arr_visual.settings.geo_advanced.move_duration_info_min, true)) {
				duration_move_info_min = parseFloat(options.arr_visual.settings.geo_advanced.move_duration_info_min);
				duration_move_info_min = (duration_move_info_min ? duration_move_info_min * 1000 : duration_move_info_min);
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_duration_info_max != 'undefined' && parseBool(options.arr_visual.settings.geo_advanced.move_duration_info_max, true)) {
				duration_move_info_max = parseFloat(options.arr_visual.settings.geo_advanced.move_duration_info_max);
				duration_move_info_max = (duration_move_info_max ? duration_move_info_max * 1000 : duration_move_info_max);
			}
			duration_move_info_fade = duration_move_info_fade * 1000;
			duration_info_dot_min = duration_info_dot_min * 1000;
			duration_info_dot_fade = duration_info_dot_fade * 1000;
			if (typeof options.arr_visual.settings.geo_advanced.move_glow != 'undefined') {
				move_glow = parseBool(options.arr_visual.settings.geo_advanced.move_glow);
			}
			if (typeof options.arr_visual.settings.geo_advanced.connection_line_opacity != 'undefined') {
				opacity_connection_line = parseFloat(options.arr_visual.settings.geo_advanced.connection_line_opacity);
			}
			if (typeof options.arr_visual.settings.geo_advanced.connection_line_opacity_range_min != 'undefined') {
				opacity_connection_line_range_min = parseFloat(options.arr_visual.settings.geo_advanced.connection_line_opacity_range_min);
			}
			if (typeof options.arr_visual.settings.geo_advanced.connection_line_opacity_range_max != 'undefined') {
				opacity_connection_line_range_max = parseFloat(options.arr_visual.settings.geo_advanced.connection_line_opacity_range_max);
			}
			opacity_connection_line_range = (opacity_connection_line_range_max - opacity_connection_line_range_min);
			if (move_continuous) {
				move_apply_opacity_connection_line = 'move';
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_connection_line_apply_opacity != 'undefined') {
				move_apply_opacity_connection_line = options.arr_visual.settings.geo_advanced.move_connection_line_apply_opacity;
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_warp_length != 'undefined') {
				length_move_warp = parseInt(options.arr_visual.settings.geo_advanced.move_warp_length);
			}
			size_max_elm_container = size_max_elm_container * (1+length_move_warp);
			if (typeof options.arr_visual.settings.geo_advanced.hint_dot != 'undefined') {
				hint_dot = parseBool(options.arr_visual.settings.geo_advanced.hint_dot, true);
			}
			if (typeof options.arr_visual.settings.geo_advanced.move_hint_dot != 'undefined') {
				move_hint_dot = parseBool(options.arr_visual.settings.geo_advanced.move_hint_dot);
			}
			if (typeof options.arr_visual.settings.geo_advanced.moved_hint_dot != 'undefined') {
				moved_hint_dot = parseBool(options.arr_visual.settings.geo_advanced.moved_hint_dot);
			}
			if (typeof options.arr_visual.settings.geo_advanced.hint_line != 'undefined') {
				hint_line = parseBool(options.arr_visual.settings.geo_advanced.hint_line);
			}
			if (typeof options.arr_visual.settings.geo_advanced.moved_hint_line != 'undefined') {
				moved_hint_line = parseBool(options.arr_visual.settings.geo_advanced.moved_hint_line);
			}
			if (typeof options.arr_visual.settings.geo_advanced.max_hint_dots != 'undefined') {
				max_hint_dots = parseInt(options.arr_visual.settings.geo_advanced.max_hint_dots);
			}
			if (typeof options.arr_visual.settings.geo_advanced.dot_icon != 'undefined') {
				dot_icon = options.arr_visual.settings.geo_advanced.dot_icon;
			}
			if (typeof options.arr_visual.settings.geo_advanced.dot_icons_size != 'undefined') {
				size_dot_icons = parseInt(options.arr_visual.settings.geo_advanced.dot_icons_size);
			}
			if (typeof options.arr_visual.settings.geo_advanced.dot_icons_offset != 'undefined') {
				offset_dot_icons = parseInt(options.arr_visual.settings.geo_advanced.dot_icons_offset);
			}
			if (options.arr_visual.line.width.min) {
				width_line_min = options.arr_visual.line.width.min;
			}
			if (options.arr_visual.line.width.max) {
				width_line_max = options.arr_visual.line.width.max;
			}
			if (options.arr_visual.line.color) {
				color_line = options.arr_visual.line.color;
			}
			if (typeof options.arr_visual.line.offset != 'undefined') {
				offset_line = options.arr_visual.line.offset;
			}
			if (typeof options.arr_visual.settings.geo_advanced.info_show != 'undefined') {
				show_info_dot = parseBool(options.arr_visual.settings.geo_advanced.info_show, true);
				show_info_line = show_info_dot;
			}
			if (typeof options.arr_visual.settings.geo_advanced.info_show_dot != 'undefined') {
				show_info_dot = parseBool(options.arr_visual.settings.geo_advanced.info_show_dot, true);
			}
			if (typeof options.arr_visual.settings.geo_advanced.info_show_line != 'undefined') {
				show_info_line = parseBool(options.arr_visual.settings.geo_advanced.info_show_line, true);
			}
			if (typeof options.arr_visual.settings.geo_advanced.location_offset != 'undefined') {
				offset_location = parseInt(options.arr_visual.settings.geo_advanced.location_offset);
			}
			if (options.arr_visual.location.color) {
				color_info = options.arr_visual.location.color;
			} 
			if (typeof options.arr_visual.settings.geo_advanced.info_color != 'undefined') {
				color_info = options.arr_visual.settings.geo_advanced.info_color;
			}
			if (typeof options.arr_visual.settings.geo_advanced.info_mode != 'undefined') {
				info_mode = options.arr_visual.settings.geo_advanced.info_mode;
			}
			if (typeof options.arr_visual.settings.geo_advanced.info_condition != 'undefined') {
				info_condition = options.arr_visual.settings.geo_advanced.info_condition;
			}
			if (typeof options.arr_visual.settings.geo_advanced.info_content != 'undefined') {
				info_content = options.arr_visual.settings.geo_advanced.info_content;
			}
			show_line = options.arr_visual.line.show;
			opacity_line = options.arr_visual.line.opacity;
			show_dot = options.arr_visual.dot.show;
			color_dot = options.arr_visual.dot.color;
			opacity_dot = options.arr_visual.dot.opacity;
			dot_color_condition = options.arr_visual.dot.color_condition;
			size_dot = options.arr_visual.dot.size;
			width_dot_stroke = options.arr_visual.dot.stroke_width;
			color_dot_stroke = options.arr_visual.dot.stroke_color;
			opacity_dot_stroke = options.arr_visual.dot.stroke_opacity;
			show_hint = options.arr_visual.hint.show;
			color_hint = options.arr_visual.hint.color;
			opacity_hint = options.arr_visual.hint.opacity;
			size_hint = options.arr_visual.hint.size;
			duration_hint = options.arr_visual.hint.duration * 1000;
			width_hint_stroke = options.arr_visual.hint.stroke_width;
			color_hint_stroke = options.arr_visual.hint.stroke_color;
			opacity_hint_stroke = options.arr_visual.hint.stroke_opacity;
			show_location = options.arr_visual.location.show;
			color_location = options.arr_visual.location.color;
			size_location = options.arr_visual.location.size;
			threshold_location = options.arr_visual.location.threshold;
			location_condition = options.arr_visual.location.condition;
			show_geo_info = options.arr_visual.settings.geo_info_show;
			show_geometry = options.arr_visual.geometry.show;
			color_geometry = options.arr_visual.geometry.color;
			opacity_geometry = options.arr_visual.geometry.opacity;
			width_geometry_stroke = options.arr_visual.geometry.stroke_width;
			color_geometry_stroke = options.arr_visual.geometry.stroke_color;
			opacity_geometry_stroke = options.arr_visual.geometry.stroke_opacity;
			
			if (!show_line) {
				show_info_line = false;
			}
			if (!show_dot) {
				show_info_dot = false;
				show_location = false;
			}
			if (!show_hint) {
				hint_dot = false;
				move_hint_dot = false;
				moved_hint_dot = false;
			}
			if (!show_location) {
				if (hint_dot == 'location') {
					hint_dot = false;
				}
			}
			if (hint_dot == 'location') {
				arr_assets_colors_obj.location = parseCssColor(color_location);
				arr_assets_colors_obj.hint = parseCssColor(color_hint);
			}
			if (display == 'vector') {
				show_info_line = false;
				show_info_dot = false;
			} else if (display == 'pixel') {
				if (mode == 'connect') {
					show_info_line = false;
					moved_hint_line = false;
				}
			}
						
			var pos_map = obj_map.getPosition();
			
			pos_origin = pos_map.origin;
			
			var count_start = 0;
			
			count_start++; // Main loading

			var func_start = function() {
				
				if (count_start > 0) {
					return;
				}
				
				obj.drawData = drawData;
			
				key_move = obj_map.move(rePosition);
			};

			arr_data = obj_parent.getData();
			
			if (arr_data.legend.conditions) {
						
				var arr_media = [];
				
				for (var key in arr_data.legend.conditions) {
					
					var arr_condition = arr_data.legend.conditions[key];
					
					if (!arr_condition.icon) {
						continue;
					}
					
					arr_media.push(arr_condition.icon);
				}
				
				if (arr_media.length) {
					
					count_start++; // Media loading
						
					ASSETS.fetch({media: arr_media}, function() {
						
						if (display == 'pixel') {
							
							for (var i = 0, len = arr_media.length; i < len; i++) {
							
								var resource = arr_media[i];
								var arr_medium = ASSETS.getMedia(resource);
								
								var texture_base = new PIXI.BaseTexture(arr_medium.image);
								var texture = new PIXI.Texture(texture_base);
								
								arr_assets_texture_icons[resource] = {texture: texture, width: arr_medium.width, height: arr_medium.height};
							}
						}
						
						count_start--; // Media loaded
						
						func_start();
					});
				}
			}
			
			if (display == 'pixel') {

				size_renderer = {width: pos_map.view.width, height: pos_map.view.height};
				
				PIXI.Graphics.CURVES.adaptive = true;
				
				elm_plot_geometry = new PIXI.Container();
				elm_plot_connection_lines = new PIXI.Container();
				elm_plot_locations = new PIXI.Container();
				elm_plot_lines = new PIXI.Container();
				elm_plot_between = new PIXI.Container();
				elm_plot_dots = new PIXI.Container();
				
				stage_geometry = new PIXI.Container();
				stage_connection_lines = new PIXI.Container();
				stage_locations = new PIXI.Container();
				stage_lines = new PIXI.Container();
				stage_activity = new PIXI.Container();
				stage_dots = new PIXI.Container();
				
				if (mode == 'move') {
					
					renderer_geometry = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_connection_lines = new PIXI.CanvasRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_locations = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_activity = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_dots = new PIXI.CanvasRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					
					stage_geometry.addChild(elm_plot_geometry);
					stage_connection_lines.addChild(elm_plot_connection_lines);
					stage_locations.addChild(elm_plot_locations);
					stage_activity.addChild(elm_plot_lines);
					stage_activity.addChild(elm_plot_between);
					stage_dots.addChild(elm_plot_dots);
					
					drawer = renderer_geometry.view;
					elm[0].appendChild(drawer);
					drawer_2 = renderer_connection_lines.view;
					elm[0].appendChild(drawer_2);
					drawer_3 = renderer_locations.view;
					elm[0].appendChild(drawer_3);
					drawer_4 = renderer_activity.view;
					elm[0].appendChild(drawer_4);
					drawer_5 = renderer_dots.view;
					elm[0].appendChild(drawer_5);
				} else {
					
					renderer_geometry = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_locations = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_lines = new PIXI.CanvasRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_activity = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					renderer_dots = new PIXI.CanvasRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
					
					stage_geometry.addChild(elm_plot_geometry);
					stage_locations.addChild(elm_plot_locations);
					stage_lines.addChild(elm_plot_lines);
					stage_activity.addChild(elm_plot_between);
					stage_dots.addChild(elm_plot_dots);
					
					drawer = renderer_geometry.view;
					elm[0].appendChild(drawer);
					drawer_2 = renderer_locations.view;
					elm[0].appendChild(drawer_2);
					drawer_3 = renderer_lines.view;
					elm[0].appendChild(drawer_3);
					drawer_4 = renderer_activity.view;
					elm[0].appendChild(drawer_4);
					drawer_5 = renderer_dots.view;
					elm[0].appendChild(drawer_5);
				}
			} else {
				
				size_renderer = {width: pos_map.size.width, height: pos_map.size.height};
				
				renderer = document.createElementNS(stage_ns, 'svg');
				renderer.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
				
				drawer = renderer;
				drawer.style.width = size_renderer.width+'px';
				drawer.style.height = size_renderer.height+'px';
				elm[0].appendChild(drawer);
				
				stage = renderer.ownerDocument;
				
				drawer_defs = stage.createElementNS(stage_ns, 'defs');
				drawer.appendChild(drawer_defs);
				
				elm_plot_locations = stage.createElementNS(stage_ns, 'g');
				drawer.appendChild(elm_plot_locations);
				fragment_plot_locations = document.createDocumentFragment();
			}
			
			if (show_geo_info) {
				obj_parent.elm_controls.children('.geo').removeClass('hide').html('');
			} else {
				obj_parent.elm_controls.children('.geo').addClass('hide');
			}
			
			if (display == 'pixel') {
				
				var elm_pointer = new PIXI.Graphics();
				elm_pointer.beginFill(GeoUtilities.parseColor(color_info), 1);
				elm_pointer.moveTo(0,0);
				elm_pointer.lineTo(3,0);
				elm_pointer.lineTo(0,3);
				elm_pointer.lineTo(0,0);
				elm_pointer.endFill();
				
				arr_assets_texture_info.pointer = elm_pointer.generateCanvasTexture();
			}
			
			count_start--; // Main loaded
				
			func_start();
		});
	};
	
	this.close = function() {
		
		if (!obj_map) { // Nothing loaded yet
			return;
		}
		
		animating = false;
		ANIMATOR.animate(null, key_animate);
		obj_map.move(null, key_move);
		
		if (display == 'pixel') { // Destroy WEBGL memory
					
			stage_geometry.destroy(true);
			stage_connection_lines.destroy(true);
			stage_locations.destroy(true);
			stage_lines.destroy(true);
			stage_activity.destroy(true);
			stage_dots.destroy(true);
			
			for (var texture in arr_assets_texture_line_dots) {
				
				if (arr_assets_texture_line_dots[texture]) {
					
					arr_assets_texture_line_dots[texture][0].destroy(true);
					
					if (move_glow) {
						arr_assets_texture_line_dots[texture][1].destroy(true);
					}
				}
			}
			if (arr_assets_texture_info.pointer) {
				
				arr_assets_texture_info.pointer.destroy(true);
			}
			for (var resource in arr_assets_texture_icons) {
				
				if (arr_assets_texture_icons[resource].texture) {
					
					arr_assets_texture_icons[resource].texture.destroy(true);
				}
			}
			
			if (mode == 'move') {
					
				renderer_geometry.destroy(true);
				renderer_connection_lines.destroy(true);
				renderer_locations.destroy(true);
				renderer_activity.destroy(true);
				renderer_dots.destroy(true);
			} else {
				
				renderer_geometry.destroy(true);
				renderer_locations.destroy(true);
				renderer_lines.destroy(true);
				renderer_activity.destroy(true);
				renderer_dots.destroy(true);
			}
		}
	};
	
	// Data
	
	this.prepareData = function(arr_data) {
		
		// Prepare subobjects

		var arr_window = {north_east: {latitude: 0, longitude: 0}, south_west: {latitude: 0, longitude: 0}};
		
		for (var object_sub_id in arr_data.object_subs) {
			
			var arr_object_sub = arr_data.object_subs[object_sub_id];
			
			if (arr_object_sub.object_sub_details_id === 'object') {
				continue;
			}

			arr_object_sub.dot_loc_id = false;
			arr_object_sub.dot_loc_id_pos = false;
			
			// If connection sub object has identifiable definitions (referencing object ids), use as a possible connecting id
			
			var arr_identifier_ids = [];
			
			for (var key in arr_object_sub.object_sub_definitions) {
				
				var arr_object_sub_definition = arr_object_sub.object_sub_definitions[key];
				
				if (arr_object_sub_definition.ref_object_id.length) {
					
					for (var i = 0, len = arr_object_sub_definition.ref_object_id.length; i < len; i++) {

						arr_identifier_ids.push(arr_object_sub_definition.ref_object_id[i]);
					}
				} else if (arr_object_sub_definition.value.length) {
					
					for (var i = 0, len = arr_object_sub_definition.value.length; i < len; i++) {
						
						arr_identifier_ids.push(arr_object_sub_definition.value[i].hashCode());
					}
				}
			}
			
			arr_object_sub.line_con_identifier = (arr_identifier_ids.length ? arr_identifier_ids.sort().join('_') : false);
			
			if (arr_object_sub.location_geometry) {
					
				var arr_geometry = JSON.parse(arr_object_sub.location_geometry);
				
				arr_object_sub.arr_geometry_package = GeoUtilities.geometryToPackage(arr_geometry);
				arr_object_sub.arr_geometry_center = GeoUtilities.geometryPackageToCenter(arr_object_sub.arr_geometry_package);
								
				var latitude = arr_object_sub.arr_geometry_center[1];
				var longitude = arr_object_sub.arr_geometry_center[0];
				
				if (latitude > arr_window.north_east.latitude || !arr_window.north_east.latitude) {
					arr_window.north_east.latitude = latitude;
				}
				if (longitude > arr_window.north_east.longitude || !arr_window.north_east.longitude) {
					arr_window.north_east.longitude = longitude;
				}
				if (latitude < arr_window.south_west.latitude || !arr_window.south_west.latitude) {
					arr_window.south_west.latitude = latitude;
				}
				if (longitude < arr_window.south_west.longitude || !arr_window.south_west.longitude) {
					arr_window.south_west.longitude = longitude;
				}
			}
		}
		
		if (move_chronological) {
			
			obj_parent.setTimeline({time: 0}); // Prepare tschuifje
		}

		return {
			default_center: arr_window,
			default_zoom: arr_window
		};
	};
			
	var getObjectSubsLineDetails = function(arr_object_sub_line) {
		
		var arr_view = {};
		var dateint_range_sub = {min: false, max: false};
		var arr_first_object_sub = false;
		var arr_first_connect_object_sub = false;
		var has_scope = false;
		
		var len = arr_object_sub_line.ref.length;
		for (var i = 0; i < len; i++) {
			
			var object_sub_id = arr_object_sub_line.ref[i];
			
			if (!object_sub_id) {
				continue;
			}
			
			var arr_object_sub = arr_data.object_subs[object_sub_id];
			
			if (display == 'pixel' && info_condition && (!arr_object_sub.style.conditions || !arr_object_sub.style.conditions[info_condition])) {
				continue;
			}
			
			if (!arr_first_object_sub) {
				arr_first_object_sub = arr_object_sub;
				has_scope = (arr_first_object_sub.connected_object_ids[0] != arr_first_object_sub.object_id);
			}
			
			if (!dateint_range_sub.min || arr_object_sub.date_start < dateint_range_sub.min) {
				dateint_range_sub.min = arr_object_sub.date_start;
			}
			if (!dateint_range_sub.max || arr_object_sub.date_start > dateint_range_sub.max) {
				dateint_range_sub.max = arr_object_sub.date_start;
			}
		}
		var len = arr_object_sub_line.connect_object_sub_ids.length;
		for (var i = 0; i < len; i++) {
			
			var connect_object_sub_id = arr_object_sub_line.connect_object_sub_ids[i];
			
			if (!connect_object_sub_id) {
				continue;
			}
			
			var arr_connect_object_sub = arr_data.object_subs[connect_object_sub_id];
			
			if (display == 'pixel' && info_condition && (!arr_connect_object_sub.style.conditions || !arr_connect_object_sub.style.conditions[info_condition])) {
				continue;
			}
			
			if (!arr_first_connect_object_sub) {
				arr_first_connect_object_sub = arr_connect_object_sub;
				has_scope = (has_scope || arr_connect_object_sub.connected_object_ids[0] != arr_connect_object_sub.object_id);
			}
			
			if (!dateint_range_sub.min || arr_connect_object_sub.date_end < dateint_range_sub.min) {
				dateint_range_sub.min = arr_connect_object_sub.date_end;
			}
			
			if (has_scope) {
			
				for (var j = 0, len_j = arr_connect_object_sub.connected_object_ids.length; j < len_j; j++) {
					
					var object_id = arr_connect_object_sub.connected_object_ids[j];
					
					var type_id = arr_data.objects[object_id].type_id;
					
					var arr_view_scope = arr_view.scope;
					if (!arr_view_scope) {
						arr_view_scope = arr_view.scope = {};
					}
										
					var arr_view_scope_type = arr_view_scope[type_id];
					if (!arr_view_scope_type) {
						arr_view_scope_type = arr_view_scope[type_id] = {};
					}
					
					var arr_object = arr_view_scope_type[object_id];
					if (!arr_object) {
						arr_object = {value: arr_data.objects[object_id].name, count: 1};
						arr_view_scope_type[object_id] = arr_object;
					} else {
						arr_object.count = arr_object.count+1;
					}
				}
			}
		}

		var obj_object_sub_line_details = {
			arr_view: arr_view,
			dateint_range: dateint_range_sub,
			arr_first_object_sub: arr_first_object_sub,
			arr_first_connect_object_sub: arr_first_connect_object_sub
		};
		
		return obj_object_sub_line_details;
	}
	
	var getObjectSubsDotDetails = function(arr_object_sub_dot) {
		
		var arr_view = {types: {}, scope: false};
		var arr_object_sub_ids = [];
		var arr_object_ids = [];
		
		for (var i = 0, len = arr_object_sub_dot.ref.length; i < len; i++) {
							
			var object_sub_id = arr_object_sub_dot.ref[i];
			
			if (!object_sub_id) {
				continue;
			}
						
			var arr_object_sub = arr_data.object_subs[object_sub_id];

			if (display == 'pixel' && info_condition && (!arr_object_sub.style.conditions || !arr_object_sub.style.conditions[info_condition])) {
				continue;
			}
			
			arr_object_sub_ids.push(object_sub_id);
			
			if (info_content == 'default') {
				
				var type_id = arr_data.objects[arr_object_sub.object_id].type_id;
				
				var arr_view_type = arr_view.types[type_id];
				if (!arr_view_type) {
					arr_view_type = arr_view.types[type_id] = {};
				}
				
				var arr_view_type_object_sub = arr_view_type[arr_object_sub.object_sub_details_id];
				if (!arr_view_type_object_sub) {
					arr_view_type_object_sub = arr_view_type[arr_object_sub.object_sub_details_id] = {};
				}
	
				var has_identifier_definition = false;
				
				for (var key in arr_object_sub.object_sub_definitions) {
					
					var arr_object_sub_definition = arr_object_sub.object_sub_definitions[key];
					
					var arr_view_type_object_sub_definitions = arr_view_type_object_sub.object_sub_definitions;
					if (!arr_view_type_object_sub_definitions) {
						arr_view_type_object_sub_definitions = arr_view_type_object_sub.object_sub_definitions = {};
					}
					
					var arr_view_type_object_sub_definition = arr_view_type_object_sub_definitions[arr_object_sub_definition.description_id];
					if (!arr_view_type_object_sub_definition) {
						arr_view_type_object_sub_definition = arr_view_type_object_sub_definitions[arr_object_sub_definition.description_id] = {};
					}
					
					if (arr_object_sub_definition.ref_object_id.length) {

						has_identifier_definition = true;
							
						for (var j = 0, len_j = arr_object_sub_definition.ref_object_id.length; j < len_j; j++) {
							
							var ref_object_id = arr_object_sub_definition.ref_object_id[j];
							var arr_object = arr_view_type_object_sub_definition[ref_object_id];
							if (!arr_object) {
								arr_object = {value: arr_object_sub_definition.value[j], count: 1};
								arr_view_type_object_sub_definition[ref_object_id] = arr_object;
							} else {
								arr_object.count = arr_object.count+1;
							}
						}
					} else if (arr_object_sub_definition.value) {
						
						for (var j = 0, len_j = arr_object_sub_definition.value.length; j < len_j; j++) {
							
							var value = arr_object_sub_definition.value[j];
							var hash = value.hashCode();
							var arr_object = arr_view_type_object_sub_definition[hash];
							if (!arr_object) {
								arr_object = {value: value, count: 1};
								arr_view_type_object_sub_definition[hash] = arr_object;
							} else {
								arr_object.count = arr_object.count+1;
							}
						}
					}
				}
				
				// Use object name when there are no sub object definition identifiers
				if (!has_identifier_definition) {
					
					if (arr_data.objects[arr_object_sub.object_id].name) {
						
						var arr_view_type_object_sub_objects = arr_view_type_object_sub.objects;
						if (!arr_view_type_object_sub_objects) {
							arr_view_type_object_sub_objects = arr_view_type_object_sub.objects = {};
						}
						
						var arr_object = arr_view_type_object_sub_objects[arr_object_sub.object_id];
						if (!arr_object) {
							arr_object = {value: arr_data.objects[arr_object_sub.object_id].name, count: 1};
							arr_view_type_object_sub_objects[arr_object_sub.object_id] = arr_object;
						} else {
							arr_object.count = arr_object.count+1;
						}
					}
				}
			}
				
			// Full objects and possible partake in scopes
			for (var j = 0, len_j = arr_object_sub.connected_object_ids.length; j < len_j; j++) {
				
				var object_id = arr_object_sub.connected_object_ids[j];
				var in_scope = (object_id != arr_object_sub.object_id);
				
				if (in_scope) {
					arr_object_ids.push(object_id);
				}

				if (in_scope || info_content == 'scope') { // Sub-object is part of a scope or include the object scope as content
					
					var type_id = arr_data.objects[object_id].type_id;
					
					var arr_view_scope = arr_view.scope;
					if (!arr_view_scope) {
						arr_view_scope = arr_view.scope = {};
					}
										
					var arr_view_scope_type = arr_view_scope[type_id];
					if (!arr_view_scope_type) {
						arr_view_scope_type = arr_view_scope[type_id] = {};
					}
					
					var arr_object = arr_view_scope_type[object_id];
					if (!arr_object) {
						arr_object = {value: arr_data.objects[object_id].name, count: 1};
						arr_view_scope_type[object_id] = arr_object;
					} else {
						arr_object.count = arr_object.count+1;
					}
				}
			}
		}
		
		var obj_object_sub_dot_details = {
			arr_view: arr_view,
			object_sub_ids: arr_object_sub_ids,
			object_ids: arr_object_ids,
			location_name: (arr_object_sub.location_object_id ? arr_object_sub.location_name : arr_data.objects[arr_object_sub.object_id].name),
			location_geometry: arr_object_sub.location_geometry
		};

		return obj_object_sub_dot_details;
	};
	
	// Interact
		
	var addListeners = function() {
		
		elm_hover = false;
		
		if (display == 'vector') {
				
			var func_interact_stop = false;
			
			var func_add_listener = function(e) {
				
				var elm_check = e.target;
				
				var elm_target = false;
				var is_line = false;
				var is_dot = false;
				
				if (elm_check.matches('.line')) {
					
					elm_target = elm_check;
					is_line = true;
				} else if (elm_check.matches('.dot')) {
				
					elm_target = elm_check;
					is_dot = true;
				} else {
						
					elm_target = elm_check.closest('.dot');
					
					if (elm_target) {
						is_dot = true;
					}
				}
				
				if (!elm_target) {
					
					if (elm_hover) {
						
						if (func_interact_stop) {
							func_interact_stop();
							func_interact_stop = false;
						}
						
						elm[0].removeAttribute('title');
						TOOLTIP.update();
						
						elm[0].arr_link = false;
						elm[0].arr_info_box = false;
						
						elm_hover = false;
					}
				} else if (elm_hover && elm_hover === elm_target) {
					
					return;
				}
				
				if (func_interact_stop) {
					
					func_interact_stop();
					func_interact_stop = false;
				}
				
				elm_hover = elm_target;
				var cur = $(elm_hover);
				
				if (is_line) {
									
					var arr_object_sub_lines_loc = arr_object_sub_lines[cur[0].getAttribute('data-loc_id')];
					var arr_object_sub_line = arr_object_sub_lines_loc[cur[0].getAttribute('data-con_id')];
					var arr_object_subs_line_details = getObjectSubsLineDetails(arr_object_sub_line);
					var dateint_range_sub = arr_object_subs_line_details.dateint_range;
					var arr_first_object_sub = arr_object_subs_line_details.arr_first_object_sub;
					var arr_first_connect_object_sub = arr_object_subs_line_details.arr_first_connect_object_sub;
					
					var arr_link = {is_line: true, object_sub_ids: [], connect_object_sub_ids: []};

					for (var i = 0, len = arr_object_sub_line.ref.length; i < len; i++) {
						
						var object_sub_id = arr_object_sub_line.ref[i];
						
						if (!object_sub_id) {
							continue;
						}
						
						arr_link.object_sub_ids.push(object_sub_id);
					}

					for (var i = 0, len = arr_object_sub_line.connect_object_sub_ids.length; i < len; i++) {
						
						var connect_object_sub_id = arr_object_sub_line.connect_object_sub_ids[i];
						
						if (!connect_object_sub_id) {
							continue;
						}
						
						arr_link.connect_object_sub_ids.push(connect_object_sub_id);
					}
					
					elm[0].arr_link = arr_link;
					elm[0].arr_info_box = {name: (arr_first_connect_object_sub.location_object_id ? arr_first_connect_object_sub.location_name : arr_data.objects[arr_first_connect_object_sub.object_id].name)+' - '+(arr_first_object_sub.location_object_id ? arr_first_object_sub.location_name : arr_data.objects[arr_first_object_sub.object_id].name)};
					
					if (POSITION.isTouch()) {
						return;
					}
					
					var str_info_object_sub_connect = '<label class="sub-name">'+arr_data.info.object_sub_details[arr_first_connect_object_sub.object_sub_details_id].object_sub_details_name+'</label>\
					<ul>';
						for (var key in arr_first_connect_object_sub.object_sub_definitions) {
							
							var object_sub_definition = arr_first_connect_object_sub.object_sub_definitions[key];
							
							if (object_sub_definition.value) {
								
								var object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
								
								str_info_object_sub_connect = str_info_object_sub_connect+'<li>\
									<label>'+(object_sub_description_name ? object_sub_description_name : 'Object')+'</label>\
									<span>'+object_sub_definition.value+'</span>\
								</li>';
							}
						}
						str_info_object_sub_connect = str_info_object_sub_connect+'<li>\
							<label>Location</label>\
							<span>'+(arr_first_connect_object_sub.location_name ? arr_first_connect_object_sub.location_name : 'φ λ°')+'</span>\
						</li>\
					</ul>';
					var str_info_object_sub_connect_object_name = (arr_object_sub_line.count_con > 1 ? arr_object_sub_line.count_con+'x' : arr_data.objects[arr_first_connect_object_sub.object_id].name);
						
					var str_info_object_sub_first = '<label class="sub-name">'+arr_data.info.object_sub_details[arr_first_object_sub.object_sub_details_id].object_sub_details_name+'</label>\
					<ul>';
						for (var key in arr_first_object_sub.object_sub_definitions) {
							
							var object_sub_definition = arr_first_object_sub.object_sub_definitions[key];
							
							if (object_sub_definition.value) {
								
								var object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
								
								str_info_object_sub_first = str_info_object_sub_first+'<li>\
									<label>'+(object_sub_description_name ? object_sub_description_name : 'Object')+'</label>\
									<span>'+object_sub_definition.value+'</span>\
								</li>';
							}
						}
						str_info_object_sub_first = str_info_object_sub_first+'<li>\
							<label>Location</label>\
							<span>'+(arr_first_object_sub.location_name ? arr_first_object_sub.location_name : 'φ λ°')+'</span>\
						</li>\
					</ul>';
					var str_info_object_sub_first_object_name = (arr_object_sub_line.count > 1 ? arr_object_sub_line.count+'x' : arr_data.objects[arr_first_object_sub.object_id].name);
					
					if (arr_data.objects[arr_first_connect_object_sub.object_id].type_id == arr_data.objects[arr_first_object_sub.object_id].type_id) {
						
						var str_info_object_subs = '<li>\
							<label>'+arr_data.info.types[arr_data.objects[arr_first_connect_object_sub.object_id].type_id].name+'</label>\
							<ul>\
								<li>\
									<label></label>\
									<span>'+str_info_object_sub_connect_object_name+'</span>\
								</li>\
								<li>\
									'+str_info_object_sub_connect+'\
								</li>\
								<li>\
									'+str_info_object_sub_first+'\
								</li>\
							</ul>\
						</li>';
					} else {
						
						var str_info_object_subs = '<li>\
							<label>'+arr_data.info.types[arr_data.objects[arr_first_connect_object_sub.object_id].type_id].name+'</label>\
							<ul>\
								<li>\
									<label></label>\
									<span>'+str_info_object_sub_connect_object_name+'</span>\
								</li>\
								<li>\
									'+str_info_object_sub_connect+'\
								</li>\
							</ul>\
						</li>\
						<hr />\
						<li>\
							<label>'+arr_data.info.types[arr_data.objects[arr_first_object_sub.object_id].type_id].name+'</label>\
							<ul>\
								<li>\
									<label></label>\
									<span>'+str_info_object_sub_first_object_name+'</span>\
								</li>\
								<li>\
									'+str_info_object_sub_first+'\
								</li>\
							</ul>\
						</li>';
					}
					
					var arr_view_scope = arr_object_subs_line_details.arr_view.scope;
					var str_info_scope = '';
					
					if (arr_view_scope) {
						
						str_info_scope = '<hr />\
						<li>\
							<label>Scope</label>\
							<ul>';
						
							for (var type_id in arr_view_scope) {
								
								var arr_view_scope_type = arr_view_scope[type_id];
								
								str_info_scope = str_info_scope+'<li>\
									<label>'+arr_data.info.types[type_id].name+'</label>\
									<ul>';
									
										var arr_objects = arr_view_scope_type;
										
										var arr_sort = [];
										for (var object_id in arr_objects) {
											arr_sort.push([object_id, arr_objects[object_id].count]);
										}
										arr_sort.sort(function(a, b) {
											return b[1] - a[1];
										});
										
										var count = 0;
										for (var i = 0, len = arr_sort.length; i < len; i++) {
											
											if (count > 10) {
												break;
											}
											var arr_object = arr_objects[arr_sort[i][0]];
											
											str_info_scope = str_info_scope+'<li>'+arr_object.value+' '+ (arr_object.count > 1 ? '('+arr_object.count+'x)' : '')+'</li>';
											count++;
										}
										
										if (arr_sort.length > 10) {
											str_info_scope = str_info_scope+'<li>... '+(arr_sort.length-10)+'x</li>';
										}
								
									str_info_scope = str_info_scope+'</ul>\
								</li>';
							}
							
							str_info_scope = str_info_scope+'</ul>\
						</li>';
					}
					
					var date_range_sub_min = DATEPARSER.int2Date(dateint_range_sub.min);
					var date_range_sub_max = DATEPARSER.int2Date(dateint_range_sub.max);
					if (date_range_sub_min.getFullYear() == -90000000000) {
						var str_date_start = '-∞';
					} else {
						var str_date_start = DATEPARSER.date2StrDate(date_range_sub_min, settings_timeline.dating.show_ce);
					}
					if (date_range_sub_max.getFullYear() == 90000000000) {
						var str_date_end = '∞';
					} else {
						var str_date_end = DATEPARSER.date2StrDate(date_range_sub_max, settings_timeline.dating.show_ce);
					}

					var str_title = '<ul>\
						'+str_info_object_subs+'\
						'+str_info_scope+'\
						<hr />\
						<li>\
							<label>Date Start</label>\
							<span>'+str_date_start+'</span>\
						</li>\
						<li>\
							<label>Date End</label>\
							<span>'+str_date_end+'</span>\
						</li>\
					</ul>';
					
					elm[0].setAttribute('title', str_title);
					TOOLTIP.update();
					
					var pos = elm[0].getBoundingClientRect();
					
					var loc_id = arr_object_sub_lines_loc.location_geometry;
					var arr_object_sub_dot = arr_object_sub_dots[loc_id];
					var pos_loc = arr_object_sub_dot.arr_geometry_center;
					var radius = arr_object_sub_dot.obj_settings.size / 2;
					
					var connect_loc_id = arr_object_sub_lines_loc.connect_location_geometry;
					var arr_object_sub_dot = arr_object_sub_dots[connect_loc_id];
					var pos_loc_connect = arr_object_sub_dot.arr_geometry_center;
					var radius_connect = arr_object_sub_dot.obj_settings.size / 2;

					var tooltip = $('<div class="tooltip label"><ul>\
						<li>\
							<label>'+arr_data.info.types[arr_data.objects[arr_first_object_sub.object_id].type_id].name+'</label>\
							<ul>\
								<li>\
									<label></label>\
									<span>'+str_info_object_sub_first_object_name+'</span>\
								</li>\
								<li>\
									'+str_info_object_sub_first+'\
								</li>\
							</ul>\
						</li>\
					</ul></div>').appendTo(elm);
					
					if (pos_loc.y > pos_loc_connect.y) {
						var top = (pos_loc.y - pos_offset_y +radius);
						var left = (pos_loc.x - pos_offset_x -tooltip.outerWidth()-radius);
					} else {
						var top = (pos_loc.y - pos_offset_y -tooltip.outerHeight()-radius);
						var left = (pos_loc.x - pos_offset_x +radius);
					}

					tooltip.css({'left': left, 'top': top});
				
					var tooltip2 = $('<div class="tooltip label"><ul>\
						<li>\
							<label>'+arr_data.info.types[arr_data.objects[arr_first_connect_object_sub.object_id].type_id].name+'</label>\
							<ul>\
								<li>\
									<label></label>\
									<span>'+str_info_object_sub_connect_object_name+'</span>\
								</li>\
								<li>\
									'+str_info_object_sub_connect+'\
								</li>\
							</ul>\
						</li>\
					</ul></div>').appendTo(elm);

					if (pos_loc_connect.y > pos_loc.y) {
						var top = (pos_loc_connect.y - pos_offset_y +radius_connect);
						var left = (pos_loc_connect.x - pos_offset_x -tooltip2.outerWidth()-radius_connect);
					} else {
						var top = (pos_loc_connect.y - pos_offset_y -tooltip2.outerHeight()-radius_connect);
						var left = (pos_loc_connect.x - pos_offset_x +radius_connect);
					}

					tooltip2.css({'left': left, 'top': top});
					
					func_interact_stop = function() {
						
						tooltip.remove();
						tooltip2.remove();
					};
				} else if (is_dot) {

					var arr_object_sub_dot = arr_object_sub_dots[cur[0].getAttribute('data-loc_id')];
					var arr_object_subs_dot_details = getObjectSubsDotDetails(arr_object_sub_dot);
					
					var arr_link = {object_sub_ids: arr_object_subs_dot_details.object_sub_ids, object_ids: arr_object_subs_dot_details.object_ids};

					elm[0].arr_link = arr_link;
					
					var str_title = '<ul>\
						<li>\
							<label>Location</label>\
							<span>'+arr_object_subs_dot_details.location_name+'</span>\
						</li>';
					
					elm[0].arr_info_box = {name: arr_object_subs_dot_details.location_name};
					
					var arr_view_types = arr_object_subs_dot_details.arr_view.types;
					
					if (POSITION.isTouch()) {
						return;
					}
					
					for (var type_id in arr_view_types) {
						
						var arr_view_type = arr_view_types[type_id];
						
						str_title = str_title+'<hr />\
						<li>\
							<label>'+arr_data.info.types[type_id].name+'</label>\
							<ul>';
							
							for (var object_sub_details_id in arr_view_type) {
								
								var arr_view_type_object_sub = arr_view_type[object_sub_details_id];
								
								str_title = str_title+'<li>\
									<label class="sub-name">'+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'</label>\
									<ul>';
									
									var has_identifier_definition = false;
									var arr_view_type_object_sub_definitions = arr_view_type_object_sub.object_sub_definitions;

									for (var object_sub_description_id in arr_view_type_object_sub_definitions) {
										
										has_identifier_definition = true;
										var object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_description_id].object_sub_description_name; // Could be collapsed
										
										str_title = str_title+'<li>\
											<label>'+(object_sub_description_name ? object_sub_description_name : 'Object')+'</label>\
											<ul>';
											
											var arr_ref_objects = arr_view_type_object_sub_definitions[object_sub_description_id];
											
											var arr_sort = [];
											for (var ref_object_id in arr_ref_objects) {
												arr_sort.push([ref_object_id, arr_ref_objects[ref_object_id].count]);
											}
											arr_sort.sort(function(a, b) {
												return b[1] - a[1];
											});
											
											var count = 0;
											for (var i = 0, len = arr_sort.length; i < len; i++) {
												
												if (count > 10) {
													break;
												}
												
												var arr_ref_object = arr_ref_objects[arr_sort[i][0]];
												
												str_title = str_title+'<li>'+arr_ref_object.value+' '+(arr_ref_object.count > 1 ? '('+arr_ref_object.count+'x)' : '')+'</li>';
												count++;
											}
											
											if (arr_sort.length > 10) {
												str_title = str_title+'<li>... '+(arr_sort.length-10)+'x</li>';
											}
											
											str_title = str_title+'</ul>\
										</li>';
									}
									if (arr_view_type_object_sub.objects) {
										
										str_title = str_title+'<li>\
											<label>'+(has_identifier_definition ? 'Object' : '')+'</label>\
											<ul>';
																			
											var arr_objects = arr_view_type_object_sub.objects;
											
											var arr_sort = [];
											for (var object_id in arr_objects) {
												arr_sort.push([object_id, arr_objects[object_id].count]);
											}
											arr_sort.sort(function(a, b) {
												return b[1] - a[1];
											});
											
											var count = 0;
											for (var i = 0, len = arr_sort.length; i < len; i++) {
												
												if (count > 10) {
													break;
												}
												var arr_object = arr_objects[arr_sort[i][0]];
												
												str_title = str_title+'<li>'+arr_object.value+' '+ (arr_object.count > 1 ? '('+arr_object.count+'x)' : '')+'</li>';
												count++;
											}
											
											if (arr_sort.length > 10) {
												str_title = str_title+'<li>... '+(arr_sort.length-10)+'x</li>';
											}
											
											str_title = str_title+'</ul>\
										</li>';
									}
									
									str_title = str_title+'</ul>\
								</li>';
							}
							
							str_title = str_title+'</ul>\
						</li>';
					}
					
					var arr_view_scope = arr_object_subs_dot_details.arr_view.scope;
					
					if (arr_view_scope) {
						
						str_title = (info_content == 'default' ? str_title+'<hr />\
						<li>\
							<label>Scope</label>\
							<ul>' : str_title);
						
							for (var type_id in arr_view_scope) {
								
								var arr_view_scope_type = arr_view_scope[type_id];
								
								str_title = str_title+'<li>\
									<label>'+arr_data.info.types[type_id].name+'</label>\
									<ul>';
									
										var arr_objects = arr_view_scope_type;
										
										var arr_sort = [];
										for (var object_id in arr_objects) {
											arr_sort.push([object_id, arr_objects[object_id].count]);
										}
										arr_sort.sort(function(a, b) {
											return b[1] - a[1];
										});
										
										var count = 0;
										for (var i = 0, len = arr_sort.length; i < len; i++) {
											
											if (count > 10) {
												break;
											}
											var arr_object = arr_objects[arr_sort[i][0]];
											
											str_title = str_title+'<li>'+arr_object.value+' '+ (arr_object.count > 1 ? '('+arr_object.count+'x)' : '')+'</li>';
											count++;
										}
										
										if (arr_sort.length > 10) {
											str_title = str_title+'<li>... '+(arr_sort.length-10)+'x</li>';
										}
								
									str_title = str_title+'</ul>\
								</li>';
							}
							
							str_title = (info_content == 'default' ? str_title+'</ul>\
						</li>' : str_title);
					}
					
					str_title = str_title+'</ul>';
								
					elm[0].setAttribute('title', str_title);
					TOOLTIP.update();
				}
			};
			
			elm[0].addEventListener('touchstart', function(e) {
				
				func_add_listener(e);
			}, false);
			
			elm[0].addEventListener('mouseover', function(e) {
				
				if (POSITION.isTouch()) {
					return;
				}
				
				func_add_listener(e);
			}, false);
		} else {
			
			var func_add_listener = function(e) {
					
				if (!elm_hover) {
					
					elm[0].arr_link = false;
					elm[0].arr_info_box = false;
						
					return;
				}
				
				var arr_object_sub_dot = elm_hover;
				var arr_object_subs_dot_details = getObjectSubsDotDetails(arr_object_sub_dot);
				
				var arr_link = {object_sub_ids: arr_object_subs_dot_details.object_sub_ids, object_ids: arr_object_subs_dot_details.object_ids};

				elm[0].arr_link = arr_link;
				
				elm[0].arr_info_box = {name: arr_object_subs_dot_details.location_name};
			};
			
			elm[0].addEventListener('touchstart', function(e) {
				
				func_add_listener(e);
			}, false);
			
			elm[0].addEventListener('mousedown', function(e) {
				
				if (POSITION.isTouch()) {
					return;
				}
				
				func_add_listener(e);
			}, false);
		}
	};
	
	// Draw
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
		
		if (calc_zoom || calc_zoom === false) { // Zoomed or resize
		
			func_interact_stop();
			
			redraw = 'reposition';
			if (calc_zoom) {
				
				cur_zoom = zoom;
				
				var arr_level = obj_map.levelVars(1);
				var arr_level_cur = obj_map.levelVars(cur_zoom);
				
				calc_plot_point = arr_level.width / arr_level_cur.width;
				
				redraw = 'rescale';
			}
		}
		if (move === false || move === true || calc_zoom === false || calc_zoom) { // Move start/stop, resize, or zoomed

			// Reposition drawer
			if (display == 'pixel') {
				
				if (move === true || (dragging && move !== false)) {
					
					dragging = true;
					animate(false);
				} else {
					
					dragging = false;
					animate(true);
				}
				
				var width = pos.view.width;
				var height = pos.view.height;
				
				if (size_renderer.width != width || size_renderer.height != height) {
					
					redraw = 'reposition';
					
					size_renderer.width = width;
					size_renderer.height = height;
					
					if (mode == 'move') {
					
						renderer_geometry.resize(width, height);
						renderer_connection_lines.resize(width, height);
						renderer_locations.resize(width, height);
						renderer_activity.resize(width, height);
						renderer_dots.resize(width, height);
					} else {
						
						renderer_geometry.resize(width, height);
						renderer_locations.resize(width, height);
						renderer_lines.resize(width, height);
						renderer_activity.resize(width, height);
						renderer_dots.resize(width, height);
					}
				}
			} else {
				
				if (move === true) {
					dragging = true;
					return;
				}
				if (move === false) {
					dragging = false;
				}
				
				var width = pos.size.width;
				var height = pos.size.height;
				
				if (size_renderer.width != width || size_renderer.height != height) {
					
					redraw = 'reposition';
					
					size_renderer.width = width;
					size_renderer.height = height;
					
					drawer.style.width = width+'px';
					drawer.style.height = height+'px';
				}
			}
	
			var x = -pos.x - pos.offset.x - (width/2);
			var y = -pos.y - pos.offset.y - (height/2);

			if (redraw || (x - pos_offset_extra_x) + (pos.view.width/2) > (width/2) || (x - pos_offset_extra_x) - (pos.view.width/2) < -(width/2) || (y - pos_offset_extra_y) + (pos.view.height/2) > (height/2) || (y - pos_offset_extra_y) - (pos.view.height/2) < -(height/2)) {
		
				pos_offset_extra_x = x;
				pos_offset_extra_y = y;

				var str = 'translate('+x+'px, '+y+'px)';
				drawer.style.transform = drawer.style.webkitTransform = str;
				if (display == 'pixel') {
					drawer_2.style.transform = drawer_2.style.webkitTransform = str;
					drawer_3.style.transform = drawer_3.style.webkitTransform = str;
					drawer_4.style.transform = drawer_4.style.webkitTransform = str;
					drawer_5.style.transform = drawer_5.style.webkitTransform = str;
				}
				
				if (!redraw) {
					redraw = 'reposition';
				}
			}

			pos_offset_x = pos.offset.x + pos_offset_extra_x;
			pos_offset_y = pos.offset.y + pos_offset_extra_y;
		}
		
		if (redraw) {
			obj_parent.doDraw();
		}
	};
	
	var checkInRange = function(arr_object_sub, in_date_range) {
						
		if (in_date_range !== undefined) {
			
			if (!in_date_range) {
				return false;
			}
		} else {
			
			var in_range = ((arr_object_sub.date_start >= dateint_range.min && arr_object_sub.date_start <= dateint_range.max) || (arr_object_sub.date_end >= dateint_range.min && arr_object_sub.date_end <= dateint_range.max) || (arr_object_sub.date_start < dateint_range.min && arr_object_sub.date_end > dateint_range.max));
		
			if (!in_range) {
				return false;
			}
		}
		
		if (!predraw) { // Check for inactive data on 'live' data
				
			if (obj_data.arr_inactive_object_sub_details[arr_object_sub.object_sub_details_id]) {
				return false;
			}
			
			var len = obj_data.arr_loop_inactive_conditions.length;
			
			if (len) {
				
				var arr_conditions = arr_object_sub.style.conditions;
				
				if (arr_conditions) {

					for (var i = 0; i < len; i++) {
						
						if (arr_conditions[obj_data.arr_loop_inactive_conditions[i]]) {
							return false;
						}
					}
				}
				
				arr_conditions = arr_data.objects[arr_object_sub.object_id].style.conditions;
				
				if (arr_conditions) {
					
					for (var i = 0; i < len; i++) {
						
						if (arr_conditions[obj_data.arr_loop_inactive_conditions[i]]) {
							return false;
						}
					}
				}
			}
		}

		return true;
	};
				
	var drawData = function(dateint_range_new, dateint_range_bounds, settings_timeline_new) {
						
		dateint_range = dateint_range_new;
		settings_timeline = settings_timeline_new;
		
		predraw = false;
		if (count_loop == 0) {

			dateint_range = dateint_range_bounds;
			
			arr_data = obj_parent.getData();

			// Prepare asset tracking
			arr_object_sub_dots = {};
			arr_loop_object_sub_dots = [];
			arr_object_sub_lines = {};
			arr_loop_object_sub_lines = {arr_loop: []};
			arr_elm_plot_line_particles = {};
			arr_elm_plot_line_particles_temp = {};
			count_info_show_object_sub_lines = 0;
			count_info_show_object_sub_dots = 0;

			predraw = true;
		}

		if (redraw) { // New draw

			// Prepare ploting container
			if (display == 'pixel') {
				
				if (redraw === 'rescale') {
					
					elm_plot_geometry.removeChildren();
					elm_plot_connection_lines.removeChildren();
					if (mode != 'move') {
						elm_plot_lines.removeChildren();
					}
					elm_plot_between.removeChildren();
					elm_plot_dots.removeChildren();
				} else if (redraw === 'reset') {
					if (mode == 'move' && move_retain === 'all') {
						for (var key in arr_elm_plot_line_particles_temp) {
							var elm_container = arr_elm_plot_line_particles_temp[key];
							elm_container[0].removeChildren();
							if (move_glow) {
								elm_container[1].removeChildren();
							}
						}
					}
				}
					
				do_render_geometry = true;
				do_render_connection_lines = true;
				do_render_locations = true;
				do_render_dots = true;
			} else {
				
				if (redraw === 'rescale') {
					
					if (elm_plot_geometry) {
						drawer.removeChild(elm_plot_geometry);
						drawer.removeChild(elm_plot_lines);
						drawer.removeChild(elm_plot_between);
						drawer.removeChild(elm_plot_dots);
					}
					elm_plot_geometry = stage.createElementNS(stage_ns, 'g');
					drawer.appendChild(elm_plot_geometry);
					drawer.appendChild(elm_plot_locations); // Re-insert locations layer
					elm_plot_lines = stage.createElementNS(stage_ns, 'g');
					drawer.appendChild(elm_plot_lines);
					elm_plot_between = stage.createElementNS(stage_ns, 'g');
					drawer.appendChild(elm_plot_between);
					elm_plot_dots = stage.createElementNS(stage_ns, 'g');
					drawer.appendChild(elm_plot_dots);
					
					fragment_plot_geometry = document.createDocumentFragment();
					fragment_plot_lines = document.createDocumentFragment();
					fragment_plot_between = document.createDocumentFragment();
					fragment_plot_dots = document.createDocumentFragment();
				}
			}
			
			// Prepare asset tracking
			count_animate_locations = 0;
			count_animate_between = 0;
			count_animate_connection_lines = 0;
			pos_hover_poll.x = 0;
			pos_hover_poll.y = 0;
		}
		
		draw = false;
		
		var arr_data_object_subs = arr_data.object_subs;
		
		// Single date sub-objects
		for (var i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
			
			var date = arr_data.date.arr_loop[i];
			var in_range = (date >= dateint_range.min && date <= dateint_range.max);
			var arr_object_subs = arr_data.date[date];
			
			for (var j = 0, len_j = arr_object_subs.length; j < len_j; j++) {
			
				var object_sub_id = arr_object_subs[j];
				var arr_object_sub = arr_data_object_subs[object_sub_id];
				
				if (arr_object_sub.object_sub_details_id === 'object') {
					continue;
				}
			
				drawObjectSub(object_sub_id, !checkInRange(arr_object_sub, in_range));
			}
		}
		
		// Sub-objects with a date range
		for (var i = 0, len = arr_data.range.length; i < len; i++) {
			
			var object_sub_id = arr_data.range[i];
			var arr_object_sub = arr_data_object_subs[object_sub_id];
			
			if (arr_object_sub.object_sub_details_id === 'object') {
				continue;
			}
			
			drawObjectSub(object_sub_id, !checkInRange(arr_object_sub));
		}
		
		drawLines();
		drawDots();

		drawInfo();
		drawHints();
		
		if (predraw) {
			
			if (display == 'pixel') {
				
			} else {
				elm_plot_locations.appendChild(fragment_plot_locations);
			}
		}
		
		if (draw) {
			
			if (!predraw) {
					
				if (!animating && !dragging) {
					
					animate(true);
				}
				
				if (show_geo_info) {
					
					if (show_geo_info) {
						
						obj_parent.elm_controls.children('.geo').html('');
						displayGeoInfo();
					}
				}
			}
			
			if (display == 'pixel') {
				
				if (!predraw) {
					
					if (mode == 'move') {
						
					} else {
						
						if (do_render_geometry) {
							renderer_geometry.render(stage_geometry);
							do_render_geometry = false;
						}

						renderer_lines.render(stage_lines);
						
						if (do_render_dots) {
							renderer_dots.render(stage_dots);
							do_render_dots = false;
						}
						
						if (do_render_locations && !count_animate_locations) {
							renderer_locations.render(stage_locations);
							do_render_locations = false;
						}
					}
				}
			} else {
				
				elm_plot_geometry.appendChild(fragment_plot_geometry);
				elm_plot_lines.appendChild(fragment_plot_lines);
				elm_plot_between.appendChild(fragment_plot_between);
				elm_plot_dots.appendChild(fragment_plot_dots);
			}
		}

		redraw = false;
		count_loop++;
		
		if (predraw) {
			
			redraw = true;
			obj.drawData(dateint_range_new, dateint_range_bounds, settings_timeline_new);
		}
	};
	
	var animate = function(state) {
		
		if (state == animating) {
			return;
		}
		
		animating = state;
		
		if (!animating) {
			return;
		}
		
		time_animate = 0;
		
		key_animate = ANIMATOR.animate(function(time) {
			
			if (!animating) {
				
				animating = false;
				ANIMATOR.animate(false, key_animate);
			}

			if (display == 'pixel') {
				
				if (!time_animate) {
					time_animate = time;
				}
				
				animateMapPixel(time);
				
				time_animate = time;
				
				if (mode == 'move') {

					if (moved_hint_dot || (move_hint_dot && move_chronological)) {
						drawHints();
					}

					if (do_render_geometry) {
						renderer_geometry.render(stage_geometry);
						do_render_geometry = false;
					}
					
					if (do_render_connection_lines || count_animate_connection_lines) {
						renderer_connection_lines.render(stage_connection_lines);
						do_render_connection_lines = false;
					}
					
					if (do_render_dots) {
						renderer_dots.render(stage_dots);
						do_render_dots = false;
					}
				}
				
				renderer_activity.render(stage_activity);
				
				if (do_render_locations || count_animate_locations) {
					renderer_locations.render(stage_locations);
					do_render_locations = false;
				}
			} else {
				
				if (!time_animate) {
					time_animate = time;
				}
				
				animateMapVector(time);
				
				time_animate = time;
			}
			
			return animating;
		}, key_animate);
	};
	
	var animateMapPixel = function(time) {
		
		if (!animating) {
			return;
		}

		var time_diff = time - time_animate;
		time_poll = ((time_poll === true ? time_diff : (time_poll + time_diff < 100 ? time_poll + time_diff : true))); // 0.1 seconds

		if (info_mode == 'hover') {
			
			var pos_hover = obj_map.getMousePosition();
			
			if (!pos_hover) {
				
				if (pos_hover_poll) {
					
					pos_hover_poll = pos_hover;
					
					if (show_info_line) {
								
						var arr_loop = arr_loop_object_sub_lines.arr_loop;
						
						for (var i = 0, len = arr_loop.length; i < len; i++) {
							
							var arr_object_sub_lines_loc = arr_loop[i];
							
							arr_object_sub_lines_loc.info_hover = false;
						}
					}
					
					if (show_info_dot) {
						
						for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {
							
							var arr_object_sub_dot = arr_loop_object_sub_dots[i];
							
							arr_object_sub_dot.info_hover = false;
						}
					}
					
					drawInfo();
				}
			} else {
				
				var x_point = pos_hover.x;
				var y_point = pos_hover.y;
			
				if (!pos_hover_poll || (Math.abs(x_point-pos_hover_poll.x) > 4 || Math.abs(y_point-pos_hover_poll.y) > 4)) {
				
					pos_hover_poll = pos_hover;
								
					count_info_show_object_sub_lines_hover = 0;
					count_info_show_object_sub_dots_hover = 0;
					
					if (show_info_line) {
							
						var arr_loop = arr_loop_object_sub_lines.arr_loop;
						
						for (var i = 0, len = arr_loop.length; i < len; i++) {
									
							var arr_object_sub_lines_loc = arr_loop[i];
							
							var angle = arr_object_sub_lines_loc.obj_move_path.angle;
							var xy_start = arr_object_sub_lines_loc.xy_start;
							var xy_end = arr_object_sub_lines_loc.xy_end;
							var x_start = xy_start.x + (xy_end.x - xy_start.x);
							var y_start = xy_start.y + (xy_end.y - xy_start.y);
							var x_end = xy_end.x + (xy_start.x - xy_end.x);
							var y_end = xy_end.y + (xy_start.y - xy_end.y);
							
							if (angle > 270) {
								var check = x_point > x_end && x_point < x_start && y_point > y_end && y_point < y_start;
							} else if (angle > 180) {
								var check = x_point > x_end && x_point < x_start && y_point < y_end && y_point > y_start;
							} else if (angle > 90) {
								var check = x_point < x_end && x_point > x_start && y_point < y_end && y_point > y_start;
							} else {
								var check = x_point < x_end && x_point > x_start && y_point > y_end && y_point < y_start;
							}
							
							if (check) {
								
								var xx = x_end - x_start;
								var yy = y_end - y_start; 
								var length_short = ((xx * (x_point - x_start)) + (yy * (y_point - y_start))) / ((xx * xx) + (yy * yy));
								var x_offset = x_start + xx * length_short; 
								var y_offset = y_start + yy * length_short;

								if (Math.abs(x_offset-x_point) < offset_info_hover && Math.abs(y_offset-y_point) < offset_info_hover) {
									
									if (arr_object_sub_lines_loc.count_info_show) {
										
										count_info_show_object_sub_lines_hover += arr_object_sub_lines_loc.count_info_show;
										arr_object_sub_lines_loc.info_hover = true;
									}
									
									continue;
								}
							}
							
							arr_object_sub_lines_loc.info_hover = false;
						}
					}
					
					elm_hover = false;
					
					if (show_info_dot) {

						for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

							var arr_object_sub_dot = arr_loop_object_sub_dots[i];
							
							var dx = arr_object_sub_dot.arr_geometry_center.x - pos_hover_poll.x;
							var dy = arr_object_sub_dot.arr_geometry_center.y - pos_hover_poll.y;
							var dist_sq = dx * dx + dy * dy;
							
							var size = arr_object_sub_dot.obj_settings.size;
							size = (size < 5 ? 5 : size);
							
							var radius_sq = (size + offset_info_hover) * (size + offset_info_hover);

							if (dist_sq < radius_sq) {
									
								if (arr_object_sub_dot.info_show) {
									
									count_info_show_object_sub_dots_hover++;
									arr_object_sub_dot.info_hover = true;
									
									radius_sq = size * size;
									
									if (dist_sq < radius_sq) {
										elm_hover = arr_object_sub_dot;
									}
								}

								continue;
							}

							arr_object_sub_dot.info_hover = false;
						}
					}
					
					drawInfo();
				}
			}
		}
		
		if (mode == 'move') {
			
			if (move_chronological) {
			
				var obj_settings = obj_parent.setTimeline({time: time_diff});
				var date_move = obj_settings.time;
			}
			
			for (var i = 0, len = arr_animate_move.length; i < len; i++) {

				var obj_move = arr_animate_move[i];
				var remove = false;
				var finished = false;
				
				if (obj_move.key === false) {
					remove = true;
				} else {
					obj_move.key = i; // Update array position of this moving instance due to array manipulation
				}
				
				var arr_object_sub_lines_loc = obj_move.arr_object_sub_lines_loc;
				var arr_object_sub_line = obj_move.arr_object_sub_line;
				var is_self = (move_retain !== 'all' || arr_object_sub_line.obj_move === obj_move);
				var is_active = arr_object_sub_line.active;
								
				if (!remove) {
				
					var duration = (do_show_info_line && is_self && is_active && arr_object_sub_line.obj_info.added && arr_object_sub_line.obj_info.duration ? arr_object_sub_line.obj_info.duration : obj_move.duration);
					
					if (move_chronological) {
						
						var date_start = obj_move.start + ((obj_move.delay / 1000) * DATEPARSER.time_day * speed_move);
						var date_end = date_start + ((duration / 1000) * DATEPARSER.time_day * speed_move);
						
						var perc = (date_move - date_start) / (date_end - date_start);
						
						if (perc < 0 || perc > 1) {
							
							if (obj_move.status === false) {
								continue;
							}
						} else {
							
							if (obj_move.status === false) {
								
								if (move_hint_dot) {
									setDotHint(false, arr_object_sub_lines_loc.connect_location_geometry);
								}
							}
							
							obj_move.status = 1;
						}
					} else {
						
						if (obj_move.cur_delay > 0) {
							
							obj_move.cur_delay = obj_move.cur_delay - time_diff;
							
							if (obj_move.cur_delay > 0) { // Still waiting for the kick-off
								continue;
							}
						}
						
						var perc = obj_move.perc + (time_diff / duration);
					}

					if (perc > 1) { // Reset or stop
						
						var do_moved = false;
						
						if (move_continuous) {
							
							if (!is_self || !is_active) {
								
								finished = true;
								obj_move.perc = perc;
							} else if (is_active) {
								
								perc = 1 + (perc % 1); // Use only the remaining decimals, percentage could be larger than 1.99
								obj_move.perc = perc - 1; // Preserve overflowing percentage to smoothen flow
								if (obj_move.status < 5) {
									//obj_move.duration = obj_move.duration + (obj_move.delay * 0.1); // Speedup just a little bit, improves dispersion over time
								}
								
								obj_move.status++;
								
								do_moved = true;
							}
						} else {
							
							finished = true;
							obj_move.perc = perc;
							
							if (is_active && is_self && obj_move.status) {

								if (!move_chronological) {
									
									arr_object_sub_line.active = false;
									
									if (arr_object_sub_line.info_show) {
										arr_object_sub_line.info_show = false;
										arr_object_sub_lines_loc.count_info_show--;
										count_info_show_object_sub_lines--;
									}
								}
								
								obj_move.status = 0;
								
								do_moved = true;
							}
						}
						
						if (do_moved && move_apply_opacity_connection_line == 'moved' && opacity_connection_line_range && (!move_continuous || obj_move.status == 2)) {
							
							var elm_connection_line = arr_object_sub_lines_loc.obj_connection_line.elm;
							var opacity = (opacity_connection_line_range / count_line_weight_max) * arr_object_sub_line.weight;
							
							if (elm_connection_line.alpha < opacity_connection_line_range_min) {
								
								elm_connection_line.alpha += (opacity_connection_line_range_min - opacity_connection_line);
							}
						
							elm_connection_line.alpha += opacity;
							arr_object_sub_lines_loc.opacity_connection_line += opacity;
							
							do_render_connection_lines = true;
						}
						
						if (do_moved && (moved_hint_dot || moved_hint_line)) {
							
							if (moved_hint_dot) { 
								
								setDotHint(false, arr_object_sub_lines_loc.location_geometry);
							}
							if (moved_hint_line) {
								
								var obj_connection_line = arr_object_sub_lines_loc.obj_connection_line;
								var arr_animate = obj_connection_line.arr_animate;

								if (arr_animate.length) {
									
									var elm_connection_line = obj_connection_line.elm;
	
									elm_connection_line.alpha -= arr_animate[1];
								} else {

									count_animate_connection_lines++;
								}
								
								arr_animate[0] = false;
								arr_animate[1] = 0;
							}
						}
					} else if (perc < 0) { // Only applicable for move_chronological
						finished = true;
					} else {
						obj_move.perc = perc;
					}
			
					var elm = obj_move.elm;
					
					var x_start = Math.floor(obj_move.xy_start.x - pos_offset_x);
					var y_start = Math.floor(obj_move.xy_start.y - pos_offset_y);
					var x_end = Math.floor(obj_move.xy_end.x - pos_offset_x);
					var y_end = Math.floor(obj_move.xy_end.y - pos_offset_y);
					
					if (!finished) {
						
						//elm.visible = true;
						elm.alpha = 1;
						
						if (offset_line) {
							var p = obj_move.obj_path.obj.compute(1 - perc);
							elm.position.x = p.x - pos_offset_x;
							elm.position.y = p.y - pos_offset_y;
						} else {
							elm.position.x = x_start + (x_end - x_start) * (1 - perc);
							elm.position.y = y_start + (y_end - y_start) * (1 - perc);
						}
					} else {
						
						//elm.visible = false;
						elm.alpha = 0;
					}
					
					if (obj_move.obj_path.length > 1) { // Only show warp when the path actually has length
							
						var perc_one = (1/obj_move.obj_path.length);
						
						for (var j = 0; j < length_move_warp; j++) {
							
							var elm_warp = obj_move.arr_elm_warp[j];
							
							if (perc_one >= 1) {
								elm_warp.alpha = 0;
								continue;
							}
							
							var perc_j = j * perc_one;
							
							if (perc_j >= perc) {
								
								if (obj_move.status > 1) {
									var cur_pos = perc_j - perc; // Put elm_warp at the end of the path
								} else {
									//elm_warp.visible = false; // Do not show elm_warp, it's not yet in play
									elm_warp.alpha = 0;
									continue;
								}
							} else {
								
								var cur_pos = 1 - (perc - (perc_j+perc_one)); // Put elm_warp where normally should be
							}
							
							if (cur_pos < 0 || cur_pos > 1) { // elm_warp is outside bounds
								//elm_warp.visible = false;
								elm_warp.alpha = 0;
								continue;
							} else {
								//elm_warp.visible = true;
								elm_warp.alpha = 0.6 - ((j + 1) * (0.6/(length_move_warp+1)));
								finished = false;
							}
							
							if (offset_line) {
								
								var p2 = obj_move.obj_path.obj.compute(cur_pos);
								elm_warp.position.x = p2.x - pos_offset_x;
								elm_warp.position.y = p2.y - pos_offset_y;
							} else {
								
								elm_warp.position.x = x_start + (x_end - x_start) * cur_pos;
								elm_warp.position.y = y_start + (y_end - y_start) * cur_pos;
							}
						}
					} else {
						
						/*
						// Cleanup possible warp when the path is redrawn (zoomed), currently not needed as everything is rebuild
						for (var j = 0; j < length_move_warp; j++) {
							
							var elm_warp = obj_move.arr_elm_warp[j];
							elm_warp.alpha = 0;
						}*/
					}
								
					if (do_show_info_line && is_self && is_active && obj_move.status) {

						var obj_info = arr_object_sub_line.obj_info;
						
						if (obj_info.added) {
							
							var elm_info = obj_info.elm;
							
							var time_position = duration * perc;
							var alpha = 0;
							
							if (duration > duration_move_info_fade + 1000) {
								
								var alpha = (time_position < duration_move_info_fade ? time_position / duration_move_info_fade : (duration - time_position < duration_move_info_fade ? (duration - time_position) / duration_move_info_fade : 1));
							} else if (perc > 0.05 && perc < 0.95) {
								
								var alpha = (perc <= 0.45 ? perc * 2 : 0.95 + ((0.45 - perc)*2));
							} else {
								
								elm_info.alpha = 0;
							}
							
							if (alpha) {
								
								elm_info.alpha = alpha;
								elm_info.position.x = Math.floor(elm.position.x) + obj_info.x;
								elm_info.position.y = Math.floor(elm.position.y) + obj_info.y;
								obj_info.x_base = Math.floor(elm.position.x + pos_offset_x);
								obj_info.y_base = Math.floor(elm.position.y + pos_offset_y);
								
								obj_info.update = true;
							}
						}
					}
				}

				if (remove || finished) {
					
					if (!move_chronological || !is_active) {
						
						removeLineInstance(obj_move);
						arr_animate_move.splice(i, 1);
						i--;
						len--;
					}
					
					obj_move.status = false;
				}
			}
		}
		
		if (moved_hint_line || do_show_info_line) {
			
			var arr_loop = arr_loop_object_sub_lines.arr_loop;
			
			for (var i = 0, len = arr_loop.length; i < len; i++) {
						
				var arr_object_sub_lines_loc = arr_loop[i];
									
				if (moved_hint_line) {

					var obj_connection_line = arr_object_sub_lines_loc.obj_connection_line;
					var arr_animate = obj_connection_line.arr_animate;
					
					if (arr_animate.length) {
						
						if (arr_animate[0] === false) {
							arr_animate[0] = 0;
							var time_elapsed = 0;
						} else {
							arr_animate[0] += time_diff;
							var time_elapsed = arr_animate[0] / 500;
						}
						
						var elm_connection_line = obj_connection_line.elm;

						if (time_elapsed <= 1) {
							
							var calc = TWEEN.Easing.Sinusoidal.Out(time_elapsed);
							
							var calc_opacity = 0.1 + (0.4 - 0.1) * calc;
							
							elm_connection_line.alpha -= arr_animate[1];
							
							if (calc_opacity > 0.2) {
								calc_opacity = 0.4 - calc_opacity;
							}
							
							if (elm_connection_line.alpha + calc_opacity > 1) {
								calc_opacity = 1 - elm_connection_line.alpha;
							}
							
							elm_connection_line.alpha += calc_opacity;

							arr_animate[1] = calc_opacity;
						} else {
							
							count_animate_connection_lines--;
							
							elm_connection_line.alpha -= arr_animate[1];

							obj_connection_line.arr_animate = [];
						}
					}
				}
				
				if (do_show_info_line) {
					
					var angle = arr_object_sub_lines_loc.obj_move_path.angle;	
					var arr_obj_info = arr_object_sub_lines_loc.arr_obj_info;
					
					if (arr_obj_info && arr_obj_info.length > 1) {
						
						var len_j = arr_obj_info.length;
						
						for (var j = 0; j < len_j; j++) {

							var obj_info = arr_obj_info[j];
							var elm_info = obj_info.elm;
							
							if (elm_info.alpha && obj_info.update) {
						
								var x_info = obj_info.x_base + obj_info.x;
								var y_info = obj_info.y_base + obj_info.y;
								var cur_height = obj_info.height + spacer_elm_info;
											
								// Check and set top down
								for (var k = j+1; k < len_j; k++) {
									
									var obj_info_check = arr_obj_info[k];
									var elm_info_check = obj_info_check.elm;
									
									var x_info_check = obj_info_check.x_base + obj_info_check.x;
									var y_info_check = obj_info_check.y_base + obj_info_check.y;
									
									// Insersect check
									if (!(x_info_check > (x_info + obj_info.width) || (x_info_check + obj_info_check.width) < x_info || y_info_check > (y_info + cur_height) || (y_info_check + obj_info_check.height) < y_info)) {
										
										if (angle > 270) {
											elm_info_check.position.y -= cur_height + (obj_info_check.y_base - obj_info.y_base);
											cur_height += obj_info_check.height + spacer_elm_info;
										} else if (angle > 180) {
											elm_info_check.position.y -= cur_height + (obj_info_check.y_base - obj_info.y_base);
											cur_height += obj_info_check.height + spacer_elm_info;
										} else if (angle > 90) {
											elm_info_check.position.y += cur_height - (obj_info_check.y_base - obj_info.y_base);
											cur_height += obj_info_check.height + spacer_elm_info;
										} else {
											elm_info_check.position.y += cur_height - (obj_info_check.y_base - obj_info.y_base);
											cur_height += obj_info_check.height + spacer_elm_info;
										}
										
										obj_info_check.update = false;
									} else {
										
										break;
									}
								}
							}
						}
					}
				}
			}
		}

		if (hint_dot || do_show_info_dot) {
			
			if (hint_dot === 'pulse') {
				var color = GeoUtilities.parseColor(color_hint);
				var stroke_color = GeoUtilities.parseColor(color_hint_stroke);
			}
			if (hint_dot === 'location') {
				var arr_color_location = arr_assets_colors_obj.location;
				var arr_color_hint = arr_assets_colors_obj.hint;
			}

			for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

				var arr_object_sub_dot = arr_loop_object_sub_dots[i];
				
				if (hint_dot) {
					
					var len_j = arr_object_sub_dot.arr_hint_queue.length;
					
					if (len_j) {
						
						for (var j = 0; j < len_j; j++) {
							
							var arr_hint = arr_object_sub_dot.arr_hint_queue[j];
							
							if (arr_hint[0] === false) {
								arr_hint[0] = 0;
								var time_elapsed = 0;
							} else {
								arr_hint[0] += time_diff;
								var time_elapsed = arr_hint[0] / duration_hint;
							}

							if (hint_dot === 'location') {

								var obj_location = arr_object_sub_dot.obj_location;
								var elm_location = obj_location.elm;

								var matrix = obj_location.matrix;

								if (time_elapsed <= 1) {
									
									var calc = TWEEN.Easing.Sinusoidal.Out(time_elapsed);
								
									var calc_bright = perc_hint_location_start + (2 - perc_hint_location_start) * calc;
									
									if (calc_bright > 1) {
										calc_bright = 2 - calc_bright;
									}

									matrix[4] = (((arr_color_hint.r - arr_color_location.r) * calc_bright) / 255);
									matrix[9] = (((arr_color_hint.g - arr_color_location.g) * calc_bright) / 255);
									matrix[14] = (((arr_color_hint.b - arr_color_location.b) * calc_bright) / 255);
								} else {
										
									count_animate_locations--;
									
									if (!obj_location.visible && !(info_mode == 'hover' && arr_object_sub_dot.info_hover)) {
										
										elm_location.visible = false;
										do_render_locations = true;
									}
									
									arr_object_sub_dot.arr_hint_queue = [];
								}
							} else {

								var elm = arr_hint[1];

								if (time_elapsed <= 1) {
									
									var x = Math.floor(arr_object_sub_dot.arr_geometry_center.x - pos_offset_x);
									var y = Math.floor(arr_object_sub_dot.arr_geometry_center.y - pos_offset_y);
									var size_r = arr_hint[2];
									
									var calc = TWEEN.Easing.Sinusoidal.In(time_elapsed);
									
									var calc_scale = 1 + (((size_r + size_hint) / size_r) - 1) * calc;
									var calc_opacity = opacity_hint + (0 - opacity_hint) * calc;
									var calc_opacity_stroke = opacity_hint_stroke + (0 - opacity_hint_stroke) * calc;
									
									elm.clear();
									if (opacity_hint) {
										elm.beginFill(color, calc_opacity);
									}
									if (width_hint_stroke) {
										elm.lineStyle(width_hint_stroke, stroke_color, calc_opacity_stroke);
									}
									elm.drawCircle(x, y, size_r * calc_scale);
									elm.endFill();
								} else {
									
									elm_plot_between.removeChild(elm);
									count_animate_between--;

									arr_object_sub_dot.arr_hint_queue.shift();
									len_j--;
									j--;
								}
							}
						}
					}
				}
				
				if (do_show_info_dot) {
					
					if (!arr_object_sub_dot.info_show || (info_mode == 'hover' && !arr_object_sub_dot.info_hover)) {

					} else {
						
						var obj_info = arr_object_sub_dot.obj_info;
						var elm_info = obj_info.elm;
						
						if (info_mode == 'hover') {
							
							elm_info.alpha = 1;
						} else {
							
							obj_info.duration -= time_diff;

							if (obj_info.duration <= 0) {
								
								arr_object_sub_dot.info_show = false;
								count_info_show_object_sub_dots--;
							} else if (obj_info.duration < duration_info_dot_fade) {

								elm_info.alpha = (obj_info.duration / duration_info_dot_fade);
							} else {
								
								elm_info.alpha = 1;
							}
						}
					}
				}
			}
		}
	};
	
	var animateMapVector = function(time) {
		
		if (!animating) {
			return;
		}

		var time_diff = time - time_animate;
		
		if (hint_dot) {
				
			if (hint_dot === 'pulse') {
				var color = GeoUtilities.parseColor(color_hint);
				var stroke_color = GeoUtilities.parseColor(color_hint_stroke);
			}
			if (hint_dot === 'location') {
				var arr_color_location = arr_assets_colors_obj.location;
				var arr_color_hint = arr_assets_colors_obj.hint;
			}
			
			for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

				var arr_object_sub_dot = arr_loop_object_sub_dots[i];
				
				if (hint_dot) {
					
					var len_j = arr_object_sub_dot.arr_hint_queue.length;
					
					if (len_j) {
						
						for (var j = 0; j < len_j; j++) {
							
							var arr_hint = arr_object_sub_dot.arr_hint_queue[j];
							
							if (arr_hint[0] === false) {
								arr_hint[0] = 0;
								var time_elapsed = 0;
							} else {
								arr_hint[0] += time_diff;
								var time_elapsed = arr_hint[0] / duration_hint;
							}

							if (hint_dot === 'location') {

								var obj_location = arr_object_sub_dot.obj_location;
								var elm_location = obj_location.elm;
								
								var obj_filter = obj_location.obj_filter;
								var matrix = obj_filter.matrix;

								if (time_elapsed <= 1) {
									
									var calc = TWEEN.Easing.Sinusoidal.Out(time_elapsed);
								
									var calc_bright = perc_hint_location_start + (2 - perc_hint_location_start) * calc;
									
									if (calc_bright > 1) {
										calc_bright = 2 - calc_bright;
									}

									matrix[4] = (((arr_color_hint.r - arr_color_location.r) * calc_bright) / 255);
									matrix[9] = (((arr_color_hint.g - arr_color_location.g) * calc_bright) / 255);
									matrix[14] = (((arr_color_hint.b - arr_color_location.b) * calc_bright) / 255);
									
									obj_filter.func_update();
								} else {

									count_animate_locations--;
									
									if (!obj_location.visible) {
									
										elm_location.setAttribute('class', 'hide');
										do_render_locations = true;
									}
									
									arr_object_sub_dot.arr_hint_queue = [];
								}
							} else {

								var elm = arr_hint[1];

								if (time_elapsed <= 1) {
									
									var x = Math.floor(arr_object_sub_dot.arr_geometry_center.x - pos_offset_x);
									var y = Math.floor(arr_object_sub_dot.arr_geometry_center.y - pos_offset_y);
									var size_r = arr_hint[2];
									
									var calc = TWEEN.Easing.Sinusoidal.In(time_elapsed);
									
									var calc_scale = 1 + (((size_r + size_hint) / size_r) - 1) * calc;
									var calc_opacity = opacity_hint + (0 - opacity_hint) * calc;
									var calc_opacity_stroke = opacity_hint_stroke + (0 - opacity_hint_stroke) * calc;
									
									elm.setAttribute('cx', x);
									elm.setAttribute('cy', y);
									elm.setAttribute('r', size_r * calc_scale);								
									if (opacity_hint) {
										elm.style.fillOpacity = calc_opacity;
									}
									if (width_hint_stroke) {
										elm.style.strokeOpacity = calc_opacity_stroke;
									}
								} else {

									elm_plot_between.removeChild(elm);
									count_animate_between--;
									
									arr_object_sub_dot.arr_hint_queue.shift();
									len_j--;
									j--;
								}
							}
						}
					}
				}
			}
		}
	};
	
	var drawObjectSub = function(object_sub_id, remove) {

		var arr_object_sub = arr_data.object_subs[object_sub_id];
		
		if (predraw) {
			arr_object_sub.use = (remove || arr_object_sub.location_geometry === '' ? false : true); // Outside of bounds, or no geometry, do not use!
		}
		
		if (!arr_object_sub.use) {
			return;
		}
				
		var arr_connections = arr_object_sub.connect_object_sub_ids;
		
		if (predraw && arr_connections && !arr_connections.length) {
			arr_object_sub.connect_object_sub_ids = false;
			arr_connections = false;
		}

		// Dots
		
		if (predraw) {

			arr_object_sub.dot_loc_id = arr_object_sub.location_geometry;
			arr_object_sub.dot_loc_id_pos = addObjectSubDot(arr_object_sub, object_sub_id, arr_object_sub.dot_loc_id);
						
			arr_object_sub.arr_object_sub_dot = arr_object_sub_dots[arr_object_sub.dot_loc_id];
		}
		
		updateObjectSubDot(arr_object_sub, object_sub_id);
		
		var is_new = setObjectSubDot(arr_object_sub, object_sub_id, remove);
	
		if (!redraw && is_new && !remove && hint_dot && (mode != 'move' || (!move_hint_dot && !moved_hint_dot) || (!arr_connections && !move_chronological))) { // Add a hint to the sub-object when move_hint_dot and moved_hint_dot are not explicitly set or when it has no connections (i.e. a starting point)
			setDotHint(arr_object_sub);
		}
		
		// Lines
		if (arr_connections && show_line) {
			
			if (predraw) {
				arr_object_sub.arr_object_sub_lines = [];
			}

			for (var i = 0, len = arr_connections.length; i < len; i++) {
				
				var connect_object_sub_id = arr_connections[i];
				var arr_connect_object_sub = arr_data.object_subs[connect_object_sub_id];

				if (predraw) {
					
					// If the connecting sub-object is out of bounds, if there is no line needed between identical locations, or missing locations
					if (!checkInRange(arr_connect_object_sub) || arr_object_sub.location_geometry == arr_connect_object_sub.location_geometry || arr_connect_object_sub.location_geometry === '') {
						continue;
					}

					var remove_connect_object_sub = false;

					var line_loc_id = arr_object_sub.location_geometry+'|'+arr_connect_object_sub.location_geometry;

					// If connection sub-object has identifiable definitions, use as connection id, otherwise keep sub-object unique
					if (arr_object_sub.line_con_identifier) {
						var line_con_id = arr_object_sub.line_con_identifier+'_'+arr_object_sub.object_sub_details_id+'_'+arr_connect_object_sub.object_sub_details_id;
					} else {
						var line_con_id = object_sub_id+'_'+connect_object_sub_id;
					}
					
					var line_con_id_pos = addObjectSubLine(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, line_loc_id, line_con_id);
					var line_con_id_pos_con = addObjectSubLineCon(arr_connect_object_sub, connect_object_sub_id, line_loc_id, line_con_id);
					
					var arr_object_sub_lines_loc = arr_object_sub_lines[line_loc_id];
					var arr_object_sub_line = arr_object_sub_lines_loc[line_con_id];
					
					arr_object_sub.arr_object_sub_lines[i] = {line_con_id_pos: line_con_id_pos, line_con_id_pos_con: line_con_id_pos_con, arr_object_sub_lines_loc: arr_object_sub_lines_loc, arr_object_sub_line: arr_object_sub_line};
				} else {
					
					// If the connecting sub-object is out of bounds, if there is no line needed between identical locations, or missing locations
					if (!arr_connect_object_sub.use || (arr_object_sub.location_geometry == arr_connect_object_sub.location_geometry)) {
						continue;
					}
					
					// If sub-object is to be removed or connecting sub-object is to be removed (outside date range)
					var remove_connect_object_sub = (remove ? true : !checkInRange(arr_connect_object_sub));
				}
				
				var obj_object_sub_line = arr_object_sub.arr_object_sub_lines[i];
				
				updateObjectSubLine(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line);		

				if (!redraw && is_new && !remove_connect_object_sub && hint_dot && mode == 'move' && move_hint_dot && !move_chronological) { // Add a hint to the connecting sub-object as it is used as a starting point for a connection in move mode
					setDotHint(arr_connect_object_sub);
				}
				
				setObjectSubLine(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line, remove_connect_object_sub);
			}
		}
	};
	
	var addObjectSubDot = function(arr_object_sub, object_sub_id, loc_id) {
		
		var arr_object_sub_dot = arr_object_sub_dots[loc_id];
		
		if (!arr_object_sub_dot) {
						
			arr_object_sub_dots[loc_id] = {loc_id: loc_id, ref: [], count: 0, weight: 0, updated: false, added: false, identifier: '', identifier_geometry: '', obj_settings: {size: 0, obj_colors: {}}, elm: false, elm_geometry: false, obj_info: false, info_show: false, info_hover: false, obj_location: false, nr_zoom: false, arr_geometry_plotable: false, arr_geometry_center: false, hint: false, arr_hint_queue: []};
			
			arr_object_sub_dot = arr_object_sub_dots[loc_id];
			
			if (show_info_dot) {
				arr_object_sub_dot.obj_info = {identifier: '', elm: false, elm_text: false, elm_pointer: false, width: false, height: false, duration: false, added: false};
			}

			var arr_geometry_package = arr_object_sub.arr_geometry_package;
			
			var arr_geometry_plotable = [];
			//var arr_bbox = [pos_origin.longitude - 180, pos_origin.latitude - 90, pos_origin.longitude + 180, pos_origin.latitude + 90];
			var i_geo = 0;
			var len_i_geo = arr_geometry_package.length;
			
			while (i_geo < len_i_geo) {
				
				var cur_value = arr_geometry_package[i_geo];
					
				if (typeof cur_value === 'string') {

					switch (cur_value) {
						case 'Point':
						case 'LineString':
							
							arr_geometry_plotable.push(cur_value);
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object') {
								
								var arr_point = obj_map.plotPoint(arr_geometry_package[i_geo][1], arr_geometry_package[i_geo][0], 1);
								
								arr_geometry_plotable.push(arr_point.x, arr_point.y);
								
								i_geo++;
							}
							
							break;
						case 'MultiPoint':

							arr_geometry_plotable.push('Group');
							arr_geometry_plotable.push('Point');
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object') {
								
								var arr_point = obj_map.plotPoint(arr_geometry_package[i_geo][1], arr_geometry_package[i_geo][0], 1);
								
								arr_geometry_plotable.push(arr_point.x, arr_point.y);
								
								i_geo++;
							}
							
							break;
						case 'MultiLineString':
					
							arr_geometry_plotable.push('Group');
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object') {
								
								arr_geometry_plotable.push('LineString');
								var arr_level_points = arr_geometry_package[i_geo];

								for (var j_geo = 0, len_j_geo = arr_level_points.length; j_geo < len_j_geo; j_geo++) {
									
									var arr_point = obj_map.plotPoint(arr_level_points[j_geo][1], arr_level_points[j_geo][0], 1);
														
									arr_geometry_plotable.push(arr_point.x, arr_point.y);
								}
								
								i_geo++;
							}
						
							break;
						case 'Polygon':
							
							arr_geometry_plotable.push(cur_value);
							i_geo++;
							
							count = 0;
						
							while (typeof arr_geometry_package[i_geo] === 'object') {
								
								var arr_level_points = arr_geometry_package[i_geo];
								//arr_level_points = GeoUtilities.clipPolygon(arr_level_points, arr_bbox);
								
								if (arr_level_points.length) {
														
									arr_geometry_plotable.push(count % 2 ? 'hole' : 'ring');
									
									for (var j_geo = 0, len_j_geo = arr_level_points.length; j_geo < len_j_geo; j_geo++) {
										
										var arr_point = obj_map.plotPoint(arr_level_points[j_geo][1], arr_level_points[j_geo][0], 1);
										
										arr_geometry_plotable.push(arr_point.x, arr_point.y);
									}
								}
								
								count++;
								i_geo++;
							}
							
							break;
						case 'MultiPolygon':

							arr_geometry_plotable.push('Group');
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object') {
								
								arr_geometry_plotable.push('Polygon');
								var arr_level_polygon = arr_geometry_package[i_geo];
								
								count = 0;

								for (var j_geo = 0, len_j_geo = arr_level_polygon.length; j_geo < len_j_geo; j_geo++) {
									
									arr_geometry_plotable.push(count % 2 ? 'hole' : 'ring');
									var arr_level_points = arr_level_polygon[j_geo];
									//arr_level_points = GeoUtilities.clipPolygon(arr_level_points, arr_bbox);
									
									for (var k_geo = 0, len_k_geo = arr_level_points.length; k_geo < len_k_geo; k_geo++) {
										
										var arr_point = obj_map.plotPoint(arr_level_points[k_geo][1], arr_level_points[k_geo][0], 1);
									
										arr_geometry_plotable.push(arr_point.x, arr_point.y);
									}

									count++;
								}
								
								i_geo++;
							}
							
							break;
						case 'GeometryCollection':
					
							i_geo++;
						
							break;
					}
				}
			}
			
			arr_object_sub_dot.arr_geometry_plotable = arr_geometry_plotable;
			
			arr_loop_object_sub_dots.push(arr_object_sub_dot);
		}
		
		arr_object_sub_dot.count++;
		arr_object_sub_dot.weight += arr_object_sub.connected_object_ids.length * (arr_object_sub.style.weight != null ? arr_object_sub.style.weight : 1);
		
		return arr_object_sub_dot.ref.push(object_sub_id) - 1;
	};
	
	var setObjectSubDot = function(arr_object_sub, object_sub_id, remove) {
		
		var arr_object_sub_dot = arr_object_sub.arr_object_sub_dot;
		var pos = arr_object_sub.dot_loc_id_pos;

		if (remove) {
			if (arr_object_sub_dot.ref[pos]) {
				
				arr_object_sub_dot.ref[pos] = 0;
				arr_object_sub_dot.count--;
				arr_object_sub_dot.weight -= arr_object_sub.connected_object_ids.length * (arr_object_sub.style.weight != null ? arr_object_sub.style.weight : 1);
				arr_object_sub_dot.updated = count_loop;
			}
		} else {
			if (!arr_object_sub_dot.ref[pos]) {
				
				arr_object_sub_dot.ref[pos] = object_sub_id;
				arr_object_sub_dot.count++;
				arr_object_sub_dot.weight += arr_object_sub.connected_object_ids.length * (arr_object_sub.style.weight != null ? arr_object_sub.style.weight : 1);
				arr_object_sub_dot.updated = count_loop;
				
				return true;
			}
		}
		
		return false;
	};
	
	var setDotHint = function(arr_object_sub, loc_id) {

		var arr_object_sub_dot = (loc_id ? arr_object_sub_dots[loc_id] : arr_object_sub.arr_object_sub_dot);
		
		if (!arr_object_sub_dot.hint && arr_object_sub_dot.weight) {
			
			arr_object_sub_dot.hint = true;
			count_hint_object_sub_dots++;
		}
	};
	
	var updateObjectSubDot = function(arr_object_sub, object_sub_id) {
		
		if (redraw === 'rescale') {
			
			var arr_object_sub_dot = arr_object_sub.arr_object_sub_dot;
			
			var arr_geometry_center = obj_map.plotPoint(arr_object_sub.arr_geometry_center[1], arr_object_sub.arr_geometry_center[0]);
			
			arr_object_sub_dot.arr_geometry_center = arr_geometry_center;
			arr_object_sub_dot.nr_zoom = cur_zoom;
			
			arr_object_sub_dot.arr_hint_queue = [];
		}
	};
			
	var addObjectSubLine = function(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, loc_id, con_id) {
				
		var arr_object_sub_lines_loc = arr_object_sub_lines[loc_id];

		if (!arr_object_sub_lines_loc) {
			
			arr_object_sub_lines[loc_id] = {loc_id: loc_id, arr_con: [], updated: false, nr_zoom: false, count: 0, elm_connection_line: false, opacity_connection_line: false, obj_connection_line: false, obj_move_path: false, arr_obj_info: false, count_info_show: 0, info_hover: false,
				location_geometry: arr_object_sub.location_geometry, xy_start: 0,
				connect_location_geometry: arr_connect_object_sub.location_geometry, xy_end: 0,
			};
			
			arr_object_sub_lines_loc = arr_object_sub_lines[loc_id];
			
			if (mode == 'move') {
				
				arr_object_sub_lines_loc.obj_connection_line = {elm: false, visible: false, arr_animate: []};
			}
			
			arr_loop_object_sub_lines.arr_loop.push(arr_object_sub_lines_loc);
		}
		
		var arr_object_sub_line = arr_object_sub_lines_loc[con_id];
		
		if (!arr_object_sub_line) {
			
			arr_object_sub_lines_loc[con_id] = {con_id: con_id, connect_object_sub_ids: [], ref: [], count: 0, count_con: 0, weight: 0, updated: false, added: false, identifier: '', obj_settings: {size: 0}, elm: false, elm_container: false, info_show: false, obj_info: false, arr_elm_warp: false, active: false, obj_move: false, arr_move_queue: false,
				object_sub_details_id: arr_object_sub.object_sub_details_id,
				connect_object_sub_details_id: arr_connect_object_sub.object_sub_details_id
			};
			
			arr_object_sub_line = arr_object_sub_lines_loc[con_id];
			
			if (show_info_line) {
				arr_object_sub_line.obj_info = {identifier: '', elm: false, elm_text: false, elm_pointer: false, x: false, y: false, width: false, height: false, perc: false, delay: false, duration: false, x_base: false, y_base: false, update: false, added: false};
			}
			
			arr_object_sub_lines_loc.arr_con.push(arr_object_sub_line);
		}
		
		arr_object_sub_lines_loc.count++;
		arr_object_sub_line.count++;
		
		arr_object_sub_line.weight += (arr_connect_object_sub.style.weight != null ? arr_connect_object_sub.style.weight : 1);

		return arr_object_sub_line.ref.push(object_sub_id) - 1;
	};
	
	var updateObjectSubLine = function(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line) {

		if (redraw === 'rescale') {
			
			if (mode == 'move') {
				
				var arr_object_sub_lines_loc = obj_object_sub_line.arr_object_sub_lines_loc;
				
				arr_object_sub_lines_loc.elm_connection_line = false
				arr_object_sub_lines_loc.obj_connection_line.elm = false;
				arr_object_sub_lines_loc.obj_connection_line.visible = false;
			}
		}
		
		if (redraw === 'rescale') {
			
			var arr_object_sub_lines_loc = obj_object_sub_line.arr_object_sub_lines_loc;
			
			if (!arr_object_sub_lines_loc.nr_zoom || arr_object_sub_lines_loc.nr_zoom != cur_zoom) { // Do update
							
				var xy_start = obj_map.plotPoint(arr_object_sub.arr_geometry_center[1], arr_object_sub.arr_geometry_center[0]);
				var xy_end = obj_map.plotPoint(arr_connect_object_sub.arr_geometry_center[1], arr_connect_object_sub.arr_geometry_center[0]);
				
				arr_object_sub_lines_loc.xy_start = xy_start;
				arr_object_sub_lines_loc.xy_end = xy_end;
				
				if (mode == 'move') {
					
					var x_start = xy_start.x;
					var y_start = xy_start.y;
					var x_end = xy_end.x;
					var y_end = xy_end.y;
					
					var obj_move_path = false;
					
					var angle = (Math.atan2(y_end - y_start, x_end - x_start) * 180 / Math.PI);
					angle -= 90; // Correction
					if (angle < 0) {
						angle = 360 - (-angle);
					}
					if (angle > 360) {
						angle = 360 - angle;
					}
					
					if (offset_line) {
						
						var identifier = x_start+'-'+x_end+'-'+y_start+'-'+y_end;
						obj_move_path = arr_assets_elm_line_dot_paths[identifier];
						
						if (!obj_move_path) {
							
							var p1 = {x: x_start, y: y_start};
							var p2 = {x: x_end, y: y_end};
							
							var c1 = GeoUtilities.calcPointOffset(p1, p2, offset_line, 0.25);
							var c2 = GeoUtilities.calcPointOffset(p1, p2, offset_line, 0.75);
							
							var path = new Bezier(x_start, y_start, c1.x, c1.y, c2.x, c2.y, x_end, y_end);

							obj_move_path = {obj: path, length: path.length(), angle: angle};
							
							arr_assets_elm_line_dot_paths[identifier] = obj_move_path;
						}
					} else {
						
						var vec = {x: x_end-x_start, y: y_end-y_start};
						
						obj_move_path = {length: Math.sqrt(vec.x*vec.x+vec.y*vec.y), angle: angle};
					}
					
					arr_object_sub_lines_loc.obj_move_path = obj_move_path;
					arr_object_sub_lines_loc.nr_zoom = cur_zoom;
				}
			}

			if (mode == 'move') {
				
				var arr_object_sub_line = obj_object_sub_line.arr_object_sub_line;

				if (predraw) {
					
					arr_object_sub_line.obj_move = {arr_object_sub_lines_loc: arr_object_sub_lines_loc, arr_object_sub_line: arr_object_sub_line, elm: false, elm_container: false, key: false, xy_start: false, xy_end: false, obj_path: false, arr_elm_warp: [], start: false, duration: false, delay: false, cur_delay: false, perc: false, status: 0};
					
					if (move_retain === 'all') {
						
						arr_object_sub_line.arr_move_queue = [];
					}
				}
				
				var obj_move = arr_object_sub_line.obj_move;
				
				obj_move.xy_start = arr_object_sub_lines_loc.xy_start;
				obj_move.xy_end = arr_object_sub_lines_loc.xy_end;
				obj_move.obj_path = arr_object_sub_lines_loc.obj_move_path;
			
				if (move_unit == 'pixel') {
					
					var duration = arr_object_sub_lines_loc.obj_move_path.length / speed_move;
					if (duration_move_min && duration < duration_move_min) {
						duration = duration_move_min;
					} else if (duration_move_max && duration > duration_move_max) {
						duration = duration_move_max;
					}
					obj_move.duration = duration*1000;
				}
				
				if (move_retain === 'all') {
					
					var arr_move_queue = arr_object_sub_line.arr_move_queue;
					
					for (var len = arr_move_queue.length, i = 0; i < len; i++) {
						
						var obj_move_instance = arr_move_queue[i];
						
						obj_move_instance.xy_start = obj_move.xy_start;
						obj_move_instance.xy_end = obj_move.xy_end;
						obj_move_instance.obj_path = obj_move.obj_path;
						obj_move_instance.duration = obj_move.duration;
					}
				}
			}
		} else if (redraw === 'reset') {
			
			if (mode == 'move') {
				
				if (move_retain === 'all') {
						
					arr_object_sub_line.arr_move_queue = [];
				}
			}
		}
	}
	
	var addObjectSubLineCon = function(arr_connect_object_sub, connect_object_sub_id, loc_id, con_id) {
		
		arr_object_sub_lines[loc_id][con_id].count_con++;

		return arr_object_sub_lines[loc_id][con_id].connect_object_sub_ids.push(connect_object_sub_id) - 1;
	};
	
	var setObjectSubLine = function(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line, remove) {
		
		var arr_object_sub_lines_loc = obj_object_sub_line.arr_object_sub_lines_loc;
		var arr_object_sub_line = obj_object_sub_line.arr_object_sub_line;
		var arr_object_sub_line_ref = arr_object_sub_line.ref;
		var arr_object_sub_line_con = arr_object_sub_line.connect_object_sub_ids;
		var pos = obj_object_sub_line.line_con_id_pos;
		var pos_con = obj_object_sub_line.line_con_id_pos_con;

		if (remove) {
			if (arr_object_sub_line_ref[pos]) {
				
				arr_object_sub_line_ref[pos] = 0;
				arr_object_sub_line.count--;
				arr_object_sub_line.weight -= (arr_connect_object_sub.style.weight != null ? arr_connect_object_sub.style.weight : 1);
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_loc.updated = count_loop;
				arr_object_sub_lines_loc.count--;
			}
			if (arr_object_sub_line_con[pos_con]) {
				
				arr_object_sub_line_con[pos_con] = 0;
				arr_object_sub_line.count_con--;
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_loc.updated = count_loop;
			}
		} else {
			if (!arr_object_sub_line_ref[pos]) {
				
				arr_object_sub_line_ref[pos] = object_sub_id;
				arr_object_sub_line.count++;
				arr_object_sub_line.weight += (arr_connect_object_sub.style.weight != null ? arr_connect_object_sub.style.weight : 1);
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_loc.updated = count_loop;
				arr_object_sub_lines_loc.count++;
			}
			if (!arr_object_sub_line_con[pos_con]) {
				
				arr_object_sub_line_con[pos_con] = connect_object_sub_id;
				arr_object_sub_line.count_con++;
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_loc.updated = count_loop;
			}
		}
	};

	var drawDots = function() {

		if (!count_dot_weight_max) {
			
			count_dot_weight_min = (size_dot.start ? size_dot.start : 1);
			
			if (size_dot.min == size_dot.max) {
				
				count_dot_weight_max = 1;
			} else if (size_dot.stop) {
				
				count_dot_weight_max = size_dot.stop;
			} else {
				
				// Find location with highest amount of references
				for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {
					
					var arr_object_sub_dot = arr_loop_object_sub_dots[i];
					
					if (arr_object_sub_dot.weight > count_dot_weight_max) {
						count_dot_weight_max = arr_object_sub_dot.weight;
					}
				}
			}
			
			if (count_dot_weight_min > count_dot_weight_max) {
				count_dot_weight_min = count_dot_weight_max;
			}
		}
						
		for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

			var arr_object_sub_dot = arr_loop_object_sub_dots[i];
			
			if (arr_object_sub_dot.updated != count_loop && !redraw) { // This loc_id has not been updated, nothing to do!
				continue;
			}
			
			draw = true;
			
			if (redraw === 'rescale') {
				arr_object_sub_dot.added = false;
			}
			
			if (!arr_object_sub_dot.count) {
				
				if (display == 'pixel') {
					
					if (show_dot && arr_object_sub_dot.obj_settings.size) {
						
						arr_object_sub_dot.elm.visible = false;
						do_render_dots = true;
						
						if (show_location && arr_object_sub_dot.obj_location.elm.visible) {
							arr_object_sub_dot.obj_location.elm.visible = false;
							do_render_locations = true;
						}
						if (arr_object_sub_dot.info_show) {
							arr_object_sub_dot.info_show = false;
							count_info_show_object_sub_dots--;
						}
					}
					
					if (arr_object_sub_dot.identifier_geometry) {
						arr_object_sub_dot.elm_geometry.visible = false;
						do_render_geometry = true;
					}
				} else {
					
					if (show_dot) {
						
						if (arr_object_sub_dot.identifier) {
							
							arr_object_sub_dot.elm.setAttribute('class', 'hide');
												
							if (show_location) {
								arr_object_sub_dot.obj_location.elm.setAttribute('class', 'hide');
							}
						}
					}
					
					if (arr_object_sub_dot.identifier_geometry) {
						arr_object_sub_dot.elm_geometry.setAttribute('class', 'hide');
					}
				}

				continue;
			}
						
			var is_new = drawDot(arr_object_sub_dot);
			
			if (is_new) {
				
				if (display == 'pixel') {
					
				} else {
					if (arr_object_sub_dot.identifier) {
						arr_object_sub_dot.elm.setAttribute('data-loc_id', arr_object_sub_dot.loc_id);
					}
				}
			}
		}
	};
	
	var drawLines = function() {
		
		if (!show_line) {
			return;
		}
		
		if (!count_line_weight_max) {
			
			// Find location with highest amount of references
			
			var arr_loop = arr_loop_object_sub_lines.arr_loop;
			
			for (var i = 0, len = arr_loop.length; i < len; i++) {
				
				var arr_object_sub_lines_loc = arr_loop[i];
				
				var count_total = 0;
				
				var arr_loop_loc = arr_object_sub_lines_loc.arr_con;
				
				for (var j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
					
					var arr_object_sub_line = arr_loop_loc[j];
					
					count_total += arr_object_sub_line.weight;
				}
				
				if (!offset_line) {
							
					var loc_id = arr_object_sub_lines_loc.connect_location_geometry+'|'+arr_object_sub_lines_loc.location_geometry;
					
					var arr_object_sub_lines_loc = arr_object_sub_lines[loc_id];
					
					if (arr_object_sub_lines_loc) {
						
						var arr_loop_loc = arr_object_sub_lines_loc.arr_con;
						
						for (var j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
							
							var arr_object_sub_line = arr_loop_loc[j];
							
							count_total += arr_object_sub_line.weight;
						}
					}
				}	
				
				if (count_total > count_line_weight_max) {
					count_line_weight_max = count_total;
				}
			}
		}
		
		var arr_loop = arr_loop_object_sub_lines.arr_loop;
		
		for (var i = 0, len = arr_loop.length; i < len; i++) {
						
			var arr_object_sub_lines_loc = arr_loop[i];
			
			if (arr_object_sub_lines_loc.updated != count_loop && !redraw) { // This loc_id has not been updated, nothing to do!
				continue;
			}
			
			draw = true;

			var count_loc = arr_object_sub_lines_loc.count;
			var offset = (mode == 'move' ? 0 : offset_line);
			var count = 0;
			
			var count_total = 0;
			var count_active = 0;
			
			var arr_loop_loc = arr_object_sub_lines_loc.arr_con;
					
			for (var j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
				
				var arr_object_sub_line = arr_loop_loc[j];
				
				if (mode == 'move' && (arr_object_sub_line.updated != count_loop && !redraw)) { // This con_id has not been updated, nothing to do!
					
					count_total = count_total + arr_object_sub_line.weight;
				
					if (arr_object_sub_line.active) {
						count_active = count_active + arr_object_sub_line.weight;
					}
					
					continue;
				}

				if (redraw === 'rescale') {
					if (mode == 'move') {

					} else {
						arr_object_sub_line.added = false;
					}
				}
							
				if (!arr_object_sub_line.weight) {
					
					removeLine(arr_object_sub_line); // Hide line
					
					if (arr_object_sub_line.info_show) {
						arr_object_sub_line.info_show = false;
						arr_object_sub_lines_loc.count_info_show--;
						count_info_show_object_sub_lines--;
					}
					
					arr_object_sub_line.active = false;
					
					continue;
				}
 
				var is_new = drawLine(arr_object_sub_line, arr_object_sub_lines_loc, (mode == 'move' ? (offset/count_loc) : offset), count);
				
				if (is_new) {
					if (display == 'pixel') {
					
					} else {
						arr_object_sub_line.elm.setAttribute('data-loc_id', arr_object_sub_lines_loc.loc_id);
						arr_object_sub_line.elm.setAttribute('data-con_id', arr_object_sub_line.con_id);
					}
				}
				
				count_total = count_total + arr_object_sub_line.weight;
				
				if (arr_object_sub_line.active) {
					count_active = count_active + arr_object_sub_line.weight;
				}
				
				offset += (mode == 'move' ? 1 : arr_object_sub_line.obj_settings.size);
				count++;
			}
			
			if (mode == 'move') {
				
				if (display == 'pixel' && opacity_connection_line) {
					
					var obj_connection_line = arr_object_sub_lines_loc.obj_connection_line;
					
					if (redraw === 'reposition' || redraw === 'reset') {
						
						if (obj_connection_line.visible && redraw === 'reposition') {
							
							var elm = obj_connection_line.elm;
													
							elm.position.x = -pos_offset_x;
							elm.position.y = -pos_offset_y;
						}
					} else if (count && !obj_connection_line.visible) {
						
						if (!obj_connection_line.elm) {

							if (!offset_line) {
								
								var loc_id = arr_object_sub_lines_loc.connect_location_geometry+'|'+arr_object_sub_lines_loc.location_geometry;

								if (arr_object_sub_lines[loc_id] && arr_object_sub_lines[loc_id].obj_connection_line.elm) {
									arr_object_sub_lines_loc.obj_connection_line = arr_object_sub_lines[loc_id].obj_connection_line;
									obj_connection_line = arr_object_sub_lines_loc.obj_connection_line;
								}
							}
							
							if (!obj_connection_line.elm) {
								
								var x_start = Math.floor(arr_object_sub_lines_loc.xy_start.x);
								var y_start = Math.floor(arr_object_sub_lines_loc.xy_start.y);
								var x_end = Math.floor(arr_object_sub_lines_loc.xy_end.x);
								var y_end = Math.floor(arr_object_sub_lines_loc.xy_end.y);
									
								var color = 0xffffff;
								
								var elm = new PIXI.Graphics();
								elm.lineStyle(1, color, 1); // 1.5 pixels vs 1: better consistent render quality, though no optimisation
							
								if (offset_line) {
									
									var p1 = {x: x_start, y: y_start};
									var p2 = {x: x_end, y: y_end};
								
									var c1 = GeoUtilities.calcPointOffset(p1, p2, offset_line, 0.25);
									var c2 = GeoUtilities.calcPointOffset(p1, p2, offset_line, 0.75);
									
									elm.moveTo(x_start, y_start).bezierCurveTo(c1.x, c1.y, c2.x, c2.y, x_end, y_end).bezierCurveTo(c2.x, c2.y, c1.x, c1.y, x_start, y_start);
								} else {
									
									elm.moveTo(x_start, y_start).lineTo(x_end, y_end).lineTo(x_start, y_start);
								}
								
								elm_plot_connection_lines.addChild(elm);
								
								obj_connection_line.arr_animate = [];
								obj_connection_line.elm = elm;

								/*var glow = new PIXI.filters.GlowFilter(renderer_activity.width, renderer_activity.height, 8, 2, 2, color, 0.5);
								elm.filters = [glow];*/							
							}
						} else {
							
							var elm = obj_connection_line.elm;
						}
						
						elm.position.x = -pos_offset_x;
						elm.position.y = -pos_offset_y;
						
						elm.alpha = opacity_connection_line;
						elm.visible = true;
						obj_connection_line.visible = true;
						
						do_render_connection_lines = true;
						
						arr_object_sub_lines_loc.elm_connection_line = elm;
						arr_object_sub_lines_loc.opacity_connection_line = 0;
						
					} else if (!count_total && obj_connection_line.visible) {
						
						var hide = true;
						
						if (!offset_line) {
							
							var loc_id = arr_object_sub_lines_loc.connect_location_geometry+'|'+arr_object_sub_lines_loc.location_geometry;
							
							if (arr_object_sub_lines[loc_id] && arr_object_sub_lines[loc_id].elm_connection_line) {
								hide = false;
							}
						}
						
						var elm = obj_connection_line.elm;
						
						if (hide) {
							
							elm.visible = false;
							obj_connection_line.visible = false;
						} else {

							elm.alpha -= arr_object_sub_lines_loc.opacity_connection_line;
							
							if (moved_hint_line && elm.alpha <= opacity_connection_line_range_min) {
								elm.alpha = opacity_connection_line;
							}
						}
						
						do_render_connection_lines = true;
						
						arr_object_sub_lines_loc.elm_connection_line = false;
					} 

					if (count_total && obj_connection_line.visible && opacity_connection_line_range) { // Adjust opacity to the amount of active but non-animating subobjects
						
						var elm = obj_connection_line.elm;
						
						if (move_apply_opacity_connection_line == 'moved') {
							var opacity = (((count_total - count_active) * opacity_connection_line_range) / count_line_weight_max);	
						} else {
							var opacity = ((count_total * opacity_connection_line_range) / count_line_weight_max);
						}

						elm.alpha += (opacity - arr_object_sub_lines_loc.opacity_connection_line);
						arr_object_sub_lines_loc.opacity_connection_line = opacity;
						
						if (opacity) { // If count_active < count_total
							
							if (elm.alpha < opacity_connection_line_range_min) {
								elm.alpha += (opacity_connection_line_range_min - opacity_connection_line);
							}
							
							do_render_connection_lines = true;
						}
					}
				}
			}
		}
	};
	
	var drawHints = function() {
		
		if (count_hint_object_sub_dots) {
			
			var count = 0;
			
			for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

				var arr_object_sub_dot = arr_loop_object_sub_dots[i];
				
				if (arr_object_sub_dot.hint) {
					
					arr_object_sub_dot.hint = false;
					
					if (count < max_hint_dots) {
						
						drawDotHint(arr_object_sub_dot);
					}
					
					count++;
				}
			}
		}
		
		count_hint_object_sub_dots = 0;
	};
	
	var drawInfo = function() {
		
		if (display == 'pixel') {

			var count_line = (info_mode == 'hover' ? count_info_show_object_sub_lines_hover : count_info_show_object_sub_lines);
			do_show_info_line = (show_info_line && count_line && (show_info_line === true || count_line < show_info_line));
			
			var count_dot = (info_mode == 'hover' ? count_info_show_object_sub_dots_hover : count_info_show_object_sub_dots);
			do_show_info_dot = (show_info_dot && count_dot && (show_info_dot === true || count_dot < show_info_dot));
			
			if ((show_info_line && count_line && !do_show_info_line) || (show_info_dot && count_dot && !do_show_info_dot)) { // Do not show either when one reached the limit
				do_show_info_line = false;
				do_show_info_dot = false;
			}
			
			if (do_show_info_line) {
				
				var is_new = false;
				
				if (!elm_container_info_lines) {
					
					elm_container_info_lines = new PIXI.Container();
					elm_plot_between.addChild(elm_container_info_lines);
					is_new = true;
				} else if (redraw) {
					
					elm_plot_between.addChild(elm_container_info_lines);
				}

				var arr_loop = arr_loop_object_sub_lines.arr_loop;
				
				for (var i = 0, len = arr_loop.length; i < len; i++) {
					
					var arr_object_sub_lines_loc = arr_loop[i];
					
					if (info_mode == 'all' && arr_object_sub_lines_loc.updated != count_loop && !redraw) {
						continue;
					}
					
					if (!arr_object_sub_lines_loc.count_info_show || (info_mode == 'hover' && !arr_object_sub_lines_loc.info_hover)) {
						
						var arr_obj_info = arr_object_sub_lines_loc.arr_obj_info;
						
						if (arr_obj_info) {

							for (var j = 0, len_j = arr_obj_info.length; j < len_j; j++) {

								var obj_info = arr_obj_info[j];
								
								if (!is_new) {
									elm_container_info_lines.removeChild(obj_info.elm);
								}
								obj_info.added = false;
							}
						}
						
						continue;
					}
					
					var angle = false;
					
					arr_object_sub_lines_loc.arr_obj_info = false;
					arr_obj_info = [];
					
					var arr_loop_loc = arr_object_sub_lines_loc.arr_con;
					
					for (var j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
						
						var arr_object_sub_line = arr_loop_loc[j];

						var obj_info = arr_object_sub_line.obj_info;
						
						if (!arr_object_sub_line.info_show || (mode == 'move' && info_mode == 'all' && !obj_info.added && arr_object_sub_line.obj_move.perc >= 0.05)) { // In mode move, do not begin to show info at already traveling instances in mode 'all'
							
							if (obj_info.added) {
								
								if (!is_new) {
									elm_container_info_lines.removeChild(obj_info.elm);
								}
								obj_info.added = false;
							}
							
							continue;
						}

						var identifier = arr_object_sub_line.count;
						
						if (angle === false) {
						
							var angle = arr_object_sub_lines_loc.obj_move_path.angle;
						
							if (angle > 270) {
								var align = 'left';
								var valign = 'bottom';
							} else if (angle > 180) {
								var align = 'right';
								var valign = 'bottom';
							} else if (angle > 90) {
								var align = 'right';
								var valign = 'top';
							} else {
								var align = 'left';
								var valign = 'top';
							}
							
							var correction = ((4/90) * (90 * ((angle/90) - Math.floor(angle/90)))); // Perform small corrections to make the pointing corner align nicely
							if ((angle < 270 && angle > 180) || (angle > 0 && angle < 90)) {
								correction = -correction;
							} else {
								correction = 4 - correction;
							}
							
							var x_start = arr_object_sub_lines_loc.xy_start.x;
							var y_start = arr_object_sub_lines_loc.xy_start.y;
							var x_end = arr_object_sub_lines_loc.xy_end.x;
							var y_end = arr_object_sub_lines_loc.xy_end.y;

							var c = GeoUtilities.calcPointOffset({x: x_start, y: y_start}, {x: x_end, y: y_end}, 10, correction/arr_object_sub_lines_loc.obj_move_path.length);
						}

						if (!obj_info.elm || obj_info.identifier != identifier) {
							
							var arr_object_subs = getObjectSubsLineDetails(arr_object_sub_line);
							var dateint_range_sub = arr_object_subs.dateint_range;
							var arr_first_object_sub = arr_object_subs.arr_first_object_sub;
							var arr_first_connect_object_sub = arr_object_subs.arr_first_connect_object_sub;
							
							var date_range_sub_min = DATEPARSER.int2Date(dateint_range_sub.min);
							var date_range_sub_max = DATEPARSER.int2Date(dateint_range_sub.max);
							if (date_range_sub_min.getFullYear() == -90000000000) {
								var str_date_start = '-∞';
							} else {
								var str_date_start = DATEPARSER.date2StrDate(date_range_sub_min, settings_timeline.dating.show_ce);
							}
							if (date_range_sub_max.getFullYear() == 90000000000) {
								var str_date_end = '∞';
							} else {
								var str_date_end = DATEPARSER.date2StrDate(date_range_sub_max, settings_timeline.dating.show_ce);
							}
							
							spacing_prefix = spacing_affix = '';
							if (align == 'left') {
								spacing_prefix = '    ';
							} else {
								spacing_affix = '    ';
							}
							
							var str_object = '';
							var str_object_name = '';
							var str_add = '';
							
							if (arr_first_connect_object_sub) {
								var object_id = arr_first_connect_object_sub.object_id;
								var object_sub_details_id = arr_first_connect_object_sub.object_sub_details_id;
								var arr_object_sub_definitions = arr_first_connect_object_sub.object_sub_definitions;
							} else {
								var object_id = arr_first_object_sub.object_id;
								var object_sub_details_id = arr_first_object_sub.object_sub_details_id;
								var arr_object_sub_definitions = arr_first_object_sub.object_sub_definitions;
							}

							if (arr_object_sub_line.count > 1) {
								
								var type_id = arr_data.objects[object_id].type_id;
								var first_object_type_id = (arr_first_connect_object_sub && arr_first_object_sub ? arr_data.objects[arr_first_object_sub.object_id].type_id : type_id);
								
								if (align == 'left') {
									str_object_name = arr_data.info.types[type_id].name+(first_object_type_id != type_id ? ' - '+arr_data.info.types[first_object_type_id].name : '')+' '+arr_object_sub_line.count+'x';
								} else {
									str_object_name = arr_object_sub_line.count+'x '+arr_data.info.types[type_id].name+(first_object_type_id != type_id ? ' - '+arr_data.info.types[first_object_type_id].name : '');
								}
							} else {
								
								str_object_name = arr_data.objects[object_id].name;
							}
							
							if (align == 'left') {
								str_add = spacing_prefix+'['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'] '+str_date_start;
							} else {
								str_add = str_date_start+' ['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']'+spacing_affix;
							}
							
							for (var key in arr_object_sub_definitions) {
								
								var object_sub_definition = arr_object_sub_definitions[key];
								
								if (!object_sub_definition.value) {
									continue;
								}
								
								var object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
								
								str_add = str_add+'\n'+spacing_prefix+''+object_sub_definition.value+spacing_affix;
							}
							
							if (valign == 'top') {
								str_object = str_object_name+'\n'+str_add;
							} else {
								str_object = str_add+'\n'+str_object_name;
							}
							
							if (arr_first_connect_object_sub && arr_first_object_sub) {
								
								object_sub_details_id = arr_first_object_sub.object_sub_details_id;
								arr_object_sub_definitions = arr_first_object_sub.object_sub_definitions;
										
								if (arr_object_sub_line.count == 1 && object_id != arr_first_object_sub.object_id) {
									
									str_object_name = arr_data.objects[arr_first_object_sub.object_id].name;
								} else {
									str_object_name = '';
								}
								
								if (align == 'left') {
									str_add = spacing_prefix+'['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'] '+str_date_end;
								} else {
									str_add = str_date_end+' ['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']'+spacing_affix;
								}
								
								for (var key in arr_object_sub_definitions) {
									
									var object_sub_definition = arr_object_sub_definitions[key];
									
									if (!object_sub_definition.value) {
										continue;
									}
									
									var object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
									
									str_add = str_add+'\n'+spacing_prefix+''+object_sub_definition.value+spacing_affix;
								}
								
								if (valign == 'top') {
									str_object = str_object+'\n'+(str_object_name ? str_object_name+'\n' : '')+str_add;
								} else {
									str_object = str_add+'\n'+(str_object_name ? str_object_name+'\n' : '')+str_object;
								}
							}

							if (!obj_info.elm) {
							
								var elm_info = new PIXI.Container();

								var elm_text = new PIXI.Text(str_object, {fontSize: size_info_font, fontFamily: 'pixel', fill: color_info, align: align});
								elm_info.addChild(elm_text);
								
								var elm_pointer = new PIXI.Sprite(arr_assets_texture_info.pointer);
								elm_info.addChild(elm_pointer);
								
								obj_info.elm = elm_info;
								obj_info.elm_text = elm_text;
								obj_info.elm_pointer = elm_pointer;
							} else {
								
								var elm_info = obj_info.elm;
								obj_info.elm_text.text = str_object;
								var elm_pointer = obj_info.elm_pointer;
							}

							obj_info.x = Math.floor(c.x-x_start);
							obj_info.y = Math.floor(c.y-y_start);
							obj_info.width = Math.floor(elm_info.width);
							obj_info.height = Math.floor(elm_info.height);
							
							elm_pointer.position.x = -4
							elm_pointer.position.y = -2;
							if (angle > 270) {
								obj_info.y -= obj_info.height;
								elm_pointer.position.y = obj_info.height + 2;
								elm_pointer.rotation = 270 * Math.PI/180;
							} else if (angle > 180) {
								obj_info.x -= obj_info.width;
								obj_info.y -= obj_info.height;
								elm_pointer.position.x = obj_info.width + 4;
								elm_pointer.position.y = obj_info.height + 2;
								elm_pointer.rotation = 180 * Math.PI/180;
							} else if (angle > 90) {
								obj_info.x -= obj_info.width;
								elm_pointer.position.x = obj_info.width + 4;
								elm_pointer.rotation = 90 * Math.PI/180;
							} else {
								
							}

							obj_info.identifier = identifier;
						}
						
						if (!obj_info.added || is_new || redraw) {
							
							if (duration_move_info_min || duration_move_info_max) {
								
								var duration = arr_object_sub_line.obj_move.duration;
								if (duration_move_info_min && duration < duration_move_info_min) {
									duration = duration_move_info_min;
								} else if (duration_move_info_max && duration > duration_move_info_max) {
									duration = duration_move_info_max;
								}
								obj_info.duration = duration;
							}
							
							elm_container_info_lines.addChild(obj_info.elm);
							obj_info.added = true;
						}
						
						obj_info.elm.alpha = 0;
						
						obj_info.perc = arr_object_sub_line.obj_move.perc;
						obj_info.delay = arr_object_sub_line.obj_move.delay;
						
						arr_obj_info.push(obj_info);
					}

					if (arr_obj_info.length > 1) {
						
						arr_obj_info.sort(function(a, b) {
							if (b.perc || a.perc) {
								return b.perc - a.perc;
							} else {
								return a.delay - b.delay;
							}
						});
					}
						
					arr_object_sub_lines_loc.arr_obj_info = arr_obj_info;
				}
			} else if (elm_container_info_lines) {
				
				elm_plot_between.removeChild(elm_container_info_lines);
				elm_container_info_lines = false;
			}
						
			if (do_show_info_dot) {

				var is_new = false;
				
				if (!elm_container_info_dots) {
					
					elm_container_info_dots = new PIXI.Container();
					elm_plot_between.addChild(elm_container_info_dots);
					is_new = true;
				} else if (redraw) {
					
					elm_plot_between.addChild(elm_container_info_dots);
				}
				
				for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

					var arr_object_sub_dot = arr_loop_object_sub_dots[i];
					
					if (info_mode == 'all' && arr_object_sub_dot.updated != count_loop && !redraw) {
						continue;
					}
						
					var obj_info = arr_object_sub_dot.obj_info;
					
					if (!arr_object_sub_dot.info_show || (info_mode == 'hover' && !arr_object_sub_dot.info_hover)) {
						
						if (obj_info.added) {
							
							if (!is_new) {
								
								elm_container_info_dots.removeChild(obj_info.elm);

								if (info_mode == 'hover' && show_location) {
									
									var obj_location = arr_object_sub_dot.obj_location;
									
									if (!obj_location.visible) {

										obj_location.elm.visible = false;
										do_render_locations = true;
									}
								}
							}
							
							obj_info.added = false;
						}
						
						continue;
					}
					
					var identifier = arr_object_sub_dot.count;
					var restart = false;

					if (!obj_info.elm || obj_info.identifier != identifier) {

						var arr_object_subs_dot_details = getObjectSubsDotDetails(arr_object_sub_dot);
				
						var str_object = '';

						var arr_view = arr_object_subs_dot_details.arr_view;
						
						for (var type_id in arr_view.types) {
														
							var arr_view_type = arr_view.types[type_id];
								
							for (var object_sub_details_id in arr_view_type) {

								str_object = (str_object ? str_object+'\n' : '')+'['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']';
								
								var arr_view_type_object_sub = arr_view_type[object_sub_details_id];
																	
								var arr_view_type_object_sub_definitions = arr_view_type_object_sub.object_sub_definitions;
								
								for (var object_sub_description_id in arr_view_type_object_sub_definitions) {
									
									var object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_description_id].object_sub_description_name; // Could be collapsed
										
									var arr_ref_objects = arr_view_type_object_sub_definitions[object_sub_description_id];
									
									var arr_sort = [];
									for (var ref_object_id in arr_ref_objects) {
										arr_sort.push([ref_object_id, arr_ref_objects[ref_object_id].count]);
									}
									arr_sort.sort(function(a, b) {
										return b[1] - a[1];
									});
									
									var count = 0;
									for (var j = 0, len_j = arr_sort.length; j < len_j; j++) {
										
										if (count > 3) {
											break;
										}
										
										var arr_ref_object = arr_ref_objects[arr_sort[j][0]];
										
										str_object = str_object+'\n'+arr_ref_object.value+(arr_ref_object.count > 1 ? ' ('+arr_ref_object.count+'x)' : '');
										count++;
									}
									
									if (arr_sort.length > count) {
										str_object = str_object+'\n... '+(arr_sort.length-count)+'x';
									}
								}
								
								var arr_view_type_object_sub_objects = arr_view_type_object_sub.objects;
								
								if (arr_view_type_object_sub_objects) {
										
									var arr_objects = arr_view_type_object_sub_objects;
									
									var arr_sort = [];
									for (var object_id in arr_objects) {
										arr_sort.push([object_id, arr_objects[object_id].count]);
									}
									arr_sort.sort(function(a, b) {
										return b[1] - a[1];
									});
									
									var count = 0;
									for (var j = 0, len_j = arr_sort.length; j < len_j; j++) {
										
										if (count > 3) {
											break;
										}
										
										var arr_object = arr_objects[arr_sort[j][0]];
										
										str_object = str_object+'\n'+arr_object.value+(arr_object.count > 1 ? ' ('+arr_object.count+'x)' : '');
										count++;
									}
									
									if (arr_sort.length > count) {
										str_object = str_object+'\n... '+(arr_sort.length-count)+'x';
									}
								}
							}
						}
						
						var arr_view_scope = arr_view.scope;
			
						if (arr_view_scope) {
							
							str_object = (info_content == 'default' ? str_object+'\n-Scope-' : str_object);
							
							for (var type_id in arr_view_scope) {
																
								var arr_objects = arr_view_scope[type_id];
									
								var arr_sort = [];
								for (var object_id in arr_objects) {
									arr_sort.push([object_id, arr_objects[object_id].count]);
								}
								arr_sort.sort(function(a, b) {
									return b[1] - a[1];
								});
								
								var count = 0;
								for (var j = 0, len_j = arr_sort.length; j < len_j; j++) {
									
									if (count > 3) {
										break;
									}
									
									var arr_object = arr_objects[arr_sort[j][0]];
									
									str_object = (str_object ? str_object+'\n' : '')+arr_object.value+(arr_object.count > 1 ? ' ('+arr_object.count+'x)' : '');
									count++;
								}
								
								if (arr_sort.length > count) {
									str_object = str_object+'\n... '+(arr_sort.length-count)+'x';
								}
							}
						}

						if (!obj_info.elm) {
						
							var elm_info = new PIXI.Container();

							var elm_text = new PIXI.Text(str_object, {fontSize: size_info_font, fontFamily: 'pixel', fill: color_info, align: 'left'});
							elm_info.addChild(elm_text);

							var elm_pointer = new PIXI.Sprite(arr_assets_texture_info.pointer);
							elm_info.addChild(elm_pointer);
							
							obj_info.elm = elm_info;
							obj_info.elm_text = elm_text;
							obj_info.elm_pointer = elm_pointer;
							
							elm_pointer.position.x = -4
							elm_pointer.position.y = -2;
						} else {
							
							var elm_info = obj_info.elm;
							obj_info.elm_text.text = str_object;
						}

						obj_info.width = Math.floor(elm_info.width);
						obj_info.height = Math.floor(elm_info.height);
						
						restart = true;
						obj_info.identifier = identifier;
					}
					
					if (!obj_info.added || is_new || redraw) {
												
						var x = (arr_object_sub_dot.arr_geometry_center.x - pos_offset_x) + ((arr_object_sub_dot.obj_settings.size / 2) + width_dot_stroke);
						var y = (arr_object_sub_dot.arr_geometry_center.y - pos_offset_y) + ((arr_object_sub_dot.obj_settings.size / 2) + width_dot_stroke);
						
						obj_info.elm.x = Math.floor(x + 7);
						obj_info.elm.y = Math.floor(y + 6);
						
						elm_container_info_dots.addChild(obj_info.elm);
						obj_info.added = true;
						
						if (info_mode == 'hover' && show_location) {
							
							var obj_location = arr_object_sub_dot.obj_location;
							
							if (!obj_location.visible) {

								obj_location.elm.visible = true;
								do_render_locations = true;
							}
						}
						
						restart = true;
					}
					
					obj_info.elm.alpha = 0;
					
					if (restart) {
						
						obj_info.duration = duration_info_dot_min;
					}
				}
			} else if (elm_container_info_dots) {
				
				if (info_mode == 'hover' && show_location) {
						
					for (var i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

						var arr_object_sub_dot = arr_loop_object_sub_dots[i];
							
						var obj_info = arr_object_sub_dot.obj_info;

						if (obj_info.added) {

							var obj_location = arr_object_sub_dot.obj_location;
							
							if (!obj_location.visible) {

								obj_location.elm.visible = false;
								do_render_locations = true;
							}
						}
					}
				}
				
				elm_plot_between.removeChild(elm_container_info_dots);
				elm_container_info_dots = false;
			}
		}
	};
	
	var drawDot = function(arr_object_sub_dot) {
		
		var obj_settings = arr_object_sub_dot.obj_settings;
		
		var size = arr_object_sub_dot.weight;
		
		if (!size) {
			
			// No dot
		} else if (count_dot_weight_max != count_dot_weight_min) {
			
			if (size > count_dot_weight_max) {
				size = size_dot.max;
			} else if (size < count_dot_weight_min) {
				size = size_dot.min;
			} else {
				size = Math.round(size_dot.min + ((size_dot.max - size_dot.min) * ((size - count_dot_weight_min) / (count_dot_weight_max - count_dot_weight_min))));
			}
		} else {
			
			size = size_dot.min;
		}
		
		obj_settings.size = size;
		
		var identifier = false;
		
		var x = Math.floor(arr_object_sub_dot.arr_geometry_center.x);
		var y = Math.floor(arr_object_sub_dot.arr_geometry_center.y);
		
		var is_new = false;

		if (show_dot && size) {
			
			var size_r = (size / 2);
			
			identifier = size;
			
			var do_override_color = (!color_dot || dot_color_condition ? false : true);
			var do_show_location = (location_condition ? false : true);
			var do_show_info = (info_condition ? false : true);
			
			var obj_colors = obj_settings.obj_colors;
			var obj_colors_touched = {};
			var arr_dot_color = [];
			var obj_icons_touched = {};
			var arr_dot_icon = [];
			var count = 0;
			var arr_first_object_sub = false;
			
			for (var i = 0, len = arr_object_sub_dot.ref.length; i < len; i++) {
				
				var object_sub_id = arr_object_sub_dot.ref[i];
				
				if (!object_sub_id) {
					continue;
				}
				
				var arr_object_sub = arr_data.object_subs[object_sub_id];

				if (color_dot && dot_color_condition && arr_object_sub.style.conditions && arr_object_sub.style.conditions[dot_color_condition]) {
					do_override_color = true;
				}
				
				if (do_override_color) { // Override colour
					var group = color_dot;
					var color = group;
				} else if (arr_object_sub.style.color) { // Custom colour
					var group = arr_object_sub.style.color;
					var color = group;
				} else {
					var group = arr_object_sub.object_sub_details_id;
					if (!obj_colors[group]) {
						var arr_legend = arr_data.legend.object_subs[arr_object_sub.object_sub_details_id];
						var arr_color = arr_legend.color;
						var color = 'rgb('+arr_color.red+','+arr_color.green+','+arr_color.blue+')';
					}
				}
				
				if (typeof group == 'object') {
					
					var is_array = true;
					var len_j = group.length;
				} else {
					
					var is_array = false;
					var len_j = 1;
				}
				
				for (var j = 0; j < len_j; j++) {
					
					var key = (is_array ? group[j] : group);
					
					var arr_group_color = obj_colors[key];
					
					if (!arr_group_color) {
						
						var value = (is_array ? color[j] : color);
						
						obj_colors[key] = {count: 0, color: value};
						var arr_group_color = obj_colors[key];
						
						arr_dot_color.push(arr_group_color);
						identifier += key;
						
						obj_colors_touched[key] = true;
					} else if (!obj_colors_touched[key]) {
					
						arr_group_color.count = 0;
						arr_dot_color.push(arr_group_color);
						identifier += key;
						
						obj_colors_touched[key] = true;
					}
					
					arr_group_color.count++;
					count++;
				}
				
				if (arr_object_sub.style.icon) {
					
					var icon = arr_object_sub.style.icon;
					
					if (typeof icon == 'object') {
						
						var is_array = true;
						var len_j = icon.length;
					} else {
						
						var is_array = false;
						var len_j = 1;
					}

					for (var j = 0; j < len_j; j++) {
						
						var value = (is_array ? icon[j] : icon);
						
						if (obj_icons_touched[value]) {
							continue;
						}

						arr_dot_icon.push(value);
						obj_icons_touched[value] = true;
					}
				}
				
				if (location_condition && arr_object_sub.style.conditions && arr_object_sub.style.conditions[location_condition]) {
					do_show_location = true;
				}
				if (info_condition && arr_object_sub.style.conditions && arr_object_sub.style.conditions[info_condition]) {
					do_show_info = true;
				}

				if (!arr_first_object_sub) {
					arr_first_object_sub = arr_object_sub;
				}
			}
			
			if (do_show_location && arr_object_sub_dot.weight < threshold_location) {
				do_show_location = false;
			}

			if (arr_object_sub_dot.identifier === identifier) {
					
				var elm = arr_object_sub_dot.elm;
				var obj_location = arr_object_sub_dot.obj_location;
				var elm_location = obj_location.elm;
				
				if (!arr_object_sub_dot.added) {
					
					if (display == 'pixel') {
						elm_plot_dots.addChild(elm);
					} else {
						fragment_plot_dots.appendChild(elm);
					}
				}
							
				if (display == 'pixel') {
					
					elm.visible = true;
					elm.position.x = x - pos_offset_x;
					elm.position.y = y - pos_offset_y;
					do_render_dots = true;
					
					if (show_location) {
						
						if (offset_location == 0) {
							var offset = -(obj_location.height / 2);
						} else if (offset_location < 0) {
							var offset = -((size + width_dot_stroke) / 2) + offset_location - obj_location.height;
						} else {
							var offset = ((size + width_dot_stroke) / 2) + offset_location;
						}
						
						elm_location.position.x = Math.floor(x - pos_offset_x - obj_location.width / 2);
						elm_location.position.y = Math.floor(y - pos_offset_y + offset);
					}
					
					if (do_show_info && !arr_object_sub_dot.info_show) {
						count_info_show_object_sub_dots++;
					}
					arr_object_sub_dot.info_show = do_show_info;
				} else {
					
					elm.setAttribute('class', 'dot');
					elm.setAttribute('transform', 'translate('+(x - pos_offset_x)+' '+(y - pos_offset_y)+')');
					
					if (show_location) {
						
						if (offset_location == 0) {
							var offset = (obj_location.height / 2);
						} else if (offset_location < 0) {
							var offset = -((size + width_dot_stroke) / 2) + offset_location;
						} else {
							var offset = ((size + width_dot_stroke) / 2) + offset_location + obj_location.height;
						}
						
						elm_location.setAttribute('transform', 'translate('+(x - pos_offset_x)+' '+(y - pos_offset_y + offset)+')');
					}
				}
			} else {
				
				if (arr_object_sub_dot.added && arr_object_sub_dot.identifier) { // Check for dot existance, could have had a 0 size in the past
					elm_plot_dots.removeChild(arr_object_sub_dot.elm);
				}
				
				var has_icon = arr_dot_icon.length;
				var has_colors = (arr_dot_color.length === 1 || !opacity_dot ? false : true);
				
				if (display == 'pixel') {
					
					if (has_colors || has_icon) {
						
						var elm_plot = new PIXI.Container();
					} else {
						
						var elm_plot = false;
					}
				
					if (!has_colors) {
									
						var elm_dot = new PIXI.Graphics();
						
						if (width_dot_stroke) {
							elm_dot.lineStyle(width_dot_stroke, GeoUtilities.parseColor(color_dot_stroke), opacity_dot_stroke);
						}
						if (opacity_dot) {
							elm_dot.beginFill(GeoUtilities.parseColor(arr_dot_color[0].color), opacity_dot);
						}
						if (dot_icon == 'square') {
							var width = (size + width_dot_stroke);
							var offset = Math.floor(-width/2);
							elm_dot.drawRect(offset, offset, width, width);
						} else {
							elm_dot.drawCircle(0, 0, (size_r + width_dot_stroke/2));
						}
						elm_dot.endFill();
						
						if (elm_plot) {
							elm_plot.addChild(elm_dot);
						} else {
							elm_plot = elm_dot;
						}
					} else {
						
						var cur_count = 0;
						
						var elm_dot = new PIXI.Graphics();
						if (width_dot_stroke) {
							elm_dot.lineStyle(width_dot_stroke, GeoUtilities.parseColor(color_dot_stroke), opacity_dot_stroke);
						}
						elm_dot.drawCircle(0, 0, (size_r + width_dot_stroke/2));
						
						elm_plot.addChild(elm_dot);

						for (var i = 0, len = arr_dot_color.length; i < len; i++) {
									
							var start = (cur_count / count) * 2 * Math.PI;
							cur_count += arr_dot_color[i].count;
							var end = (cur_count / count) * 2 * Math.PI;
							
							var elm_dot = new PIXI.Graphics();
							elm_dot.beginFill(GeoUtilities.parseColor(arr_dot_color[i].color), opacity_dot);
							elm_dot.moveTo(0, 0)
								.lineTo(size_r * Math.cos(start), size_r * Math.sin(start))
								.arc(0, 0, size_r, start, end, false)
								.lineTo(0, 0);
							elm_dot.endFill();
							
							elm_plot.addChild(elm_dot);
						}
					}
					
					if (has_icon) {
						
						var height_sum = 0;
						var width_sum = 0;
						
						for (var i = 0, len = arr_dot_icon.length; i < len; i++) {
							
							var resource = arr_dot_icon[i];
							var arr_resource = arr_assets_texture_icons[resource];

							var elm_icon = new PIXI.Sprite(arr_resource.texture);
							var scale_icon = (arr_resource.width / arr_resource.height);
							
							var width_icon = size_dot_icons * scale_icon;
							elm_icon.height = size_dot_icons;
							elm_icon.width = width_icon;
							
							if (i > 0) {
								width_sum += spacer_elm_icons;
							}
							elm_icon.position.x = width_sum;
							elm_icon.position.y = height_sum;
							height_sum += 0;
							width_sum += width_icon;
							
							if (i == 0) {
								
								if (len > 1) {
									
									var elm_icons = new PIXI.Container();
									elm_icons.addChild(elm_icon);
								} else {
									
									var elm_icons = elm_icon;
								}
							} else {
								
								elm_icons.addChild(elm_icon);
							}
						}
						
						if (offset_dot_icons == 0) {
							var offset = -(size_dot_icons / 2);
						} else if (offset_dot_icons < 0) {
							var offset = -((size + width_dot_stroke) / 2) + offset_dot_icons - size_dot_icons;
						} else {
							var offset = ((size + width_dot_stroke) / 2) + offset_dot_icons;
						}
						
						elm_icons.position.x = Math.floor(-(width_sum / 2));
						elm_icons.position.y = Math.floor(offset);
						
						elm_plot.addChild(elm_icons);
					}

					elm_plot.position.x = x - pos_offset_x;
					elm_plot.position.y = y - pos_offset_y;
					
					elm_plot_dots.addChild(elm_plot);
					do_render_dots = true;
					
					if (do_show_info && !arr_object_sub_dot.info_show) {
						count_info_show_object_sub_dots++;
					}
					arr_object_sub_dot.info_show = do_show_info;
									
					if (show_location) {
						
						if (predraw) {
							
							var elm_location = new PIXI.Text((arr_first_object_sub.location_name ? arr_first_object_sub.location_name : ''), {fontSize: size_location, fontFamily: 'pixel', fill: color_location});

							elm_plot_locations.addChild(elm_location);
							
							arr_object_sub_dot.obj_location = {elm: elm_location, visible: false, width: elm_location.width, height: elm_location.height, matrix: false};
							
							if (hint_dot === 'location') {
								
								var filter = new PIXI.filters.ColorMatrixFilter();
								/*var matrix = [
									1,0,0,0,0,
									0,1,0,0,0,
									0,0,1,0,0,
									0,0,0,1,0
								];*/
								var matrix = filter.matrix;
								elm_location.filters = [filter];
								
								arr_object_sub_dot.obj_location.matrix = matrix;
							}
						} else {
							
							var elm_location = arr_object_sub_dot.obj_location.elm;
						}
						
						if (offset_location == 0) {
							var offset = -(arr_object_sub_dot.obj_location.height / 2);
						} else if (offset_location < 0) {
							var offset = -((size + width_dot_stroke) / 2) + offset_location - arr_object_sub_dot.obj_location.height;
						} else {
							var offset = ((size + width_dot_stroke) / 2) + offset_location;
						}
												
						elm_location.visible = false;
						elm_location.position.x = Math.floor(x - pos_offset_x - (arr_object_sub_dot.obj_location.width / 2));
						elm_location.position.y = Math.floor(y - pos_offset_y + offset);
					}
				} else {
					
					if (has_colors || has_icon) {
						
						var elm_plot = stage.createElementNS(stage_ns, 'g');
					} else {
						
						var elm_plot = false;
					}
				
					if (!has_colors) {
						
						if (dot_icon == 'square') {
							
							var width = (size + width_dot_stroke);
							var offset = Math.floor(-width/2);
							
							var elm_dot = stage.createElementNS(stage_ns, 'rect');
							elm_dot.setAttribute('x', offset);
							elm_dot.setAttribute('y', offset);
							elm_dot.setAttribute('width', width);
							elm_dot.setAttribute('height', width);
						} else {
							
							var elm_dot = stage.createElementNS(stage_ns, 'circle');
							elm_dot.setAttribute('cx', 0);
							elm_dot.setAttribute('cy', 0);
							elm_dot.setAttribute('r', (size_r + width_dot_stroke/2));
						}
						if (opacity_dot) {
							elm_dot.style.fill = arr_dot_color[0].color;
							elm_dot.style.fillOpacity = opacity_dot;
						} else {
							elm_dot.style.fill = 'none';
						}
						if (width_dot_stroke) {
							elm_dot.style.stroke = color_dot_stroke;
							elm_dot.style.strokeWidth = width_dot_stroke;
							elm_dot.style.strokeOpacity = opacity_dot_stroke;
						}
						
						if (elm_plot) {
							
							elm_plot.appendChild(elm_dot);
						} else {
							
							elm_plot = elm_dot;
						}
					} else {

						var cur_count = 0;
						
						var elm_circle = stage.createElementNS(stage_ns, 'circle');
						elm_circle.setAttribute('cx', 0);
						elm_circle.setAttribute('cy', 0);
						elm_circle.setAttribute('r', (size_r + width_dot_stroke/2));
						elm_circle.style.fill = 'none';
						if (width_dot_stroke) {
							elm_circle.style.stroke = color_dot_stroke;
							elm_circle.style.strokeWidth = width_dot_stroke;
							elm_circle.style.strokeOpacity = opacity_dot_stroke;
						}
						elm_plot.appendChild(elm_circle);
						
						for (var i = 0, len = arr_dot_color.length; i < len; i++) {
							
							var start = (cur_count / count) * 2 * Math.PI;
							cur_count += arr_dot_color[i].count;
							var end = (cur_count / count) * 2 * Math.PI;
						
							var elm_path = stage.createElementNS(stage_ns, 'path');
							elm_path.setAttribute('d','M '+0+','+0+' L '+(0 + size_r * Math.cos(start))+','+(0 + size_r * Math.sin(start))+' A '+size_r+','+size_r+' 0 '+(end - start < Math.PI ? 0 : 1)+',1 '+(0 + size_r * Math.cos(end))+','+(0 + size_r * Math.sin(end))+' z');
							elm_path.style.fill = arr_dot_color[i].color;
							elm_path.style.fillOpacity = opacity_dot;
							elm_plot.appendChild(elm_path);
						}
					}
					
					if (has_icon) {
						
						var height_sum = 0;
						var width_sum = 0;
						
						for (var i = 0, len = arr_dot_icon.length; i < len; i++) {
							
							var resource = arr_dot_icon[i];
							var arr_resource = ASSETS.getMedia(resource);

							var elm_icon = stage.createElementNS(stage_ns, 'image');
							elm_icon.setAttribute('href', arr_resource.resource);
							var scale_icon = (arr_resource.width / arr_resource.height);
							
							var width_icon = size_dot_icons * scale_icon;
							elm_icon.setAttribute('height', size_dot_icons);
							elm_icon.setAttribute('width', width_icon);
							if (i > 0) {
								width_sum += spacer_elm_icons;
							}
							elm_icon.setAttribute('x', width_sum);
							elm_icon.setAttribute('y', height_sum);
							height_sum += 0;
							width_sum += width_icon;
							
							if (i == 0) {
								
								if (len > 1) {
									
									var elm_icons = stage.createElementNS(stage_ns, 'g');
									elm_icons.appendChild(elm_icon);
								} else {
									
									var elm_icons = elm_icon;
								}
							} else {
								
								elm_icons.appendChild(elm_icon);
							}
						}
						
						if (offset_dot_icons == 0) {
							var offset = -(size_dot_icons / 2);
						} else if (offset_dot_icons < 0) {
							var offset = -((size + width_dot_stroke) / 2) + offset_dot_icons - size_dot_icons;
						} else {
							var offset = ((size + width_dot_stroke) / 2) + offset_dot_icons;
						}
						
						elm_icons.setAttribute('transform', 'translate('+(-(width_sum / 2))+' '+(offset)+')');
						
						elm_plot.appendChild(elm_icons);
					}
					
					elm_plot.setAttribute('transform', 'translate('+(x - pos_offset_x)+' '+(y - pos_offset_y)+')');
					elm_plot.setAttribute('class', 'dot');
					
					fragment_plot_dots.appendChild(elm_plot);

					if (show_location) {
						
						if (predraw) {
							
							var elm_location = stage.createElementNS(stage_ns, 'text');
							elm_location.setAttribute('x', 0);
							elm_location.setAttribute('y', 0);
							elm_location.style.fontSize = size_location+'px';
							elm_location.style.fontFamily = 'pixel';
							elm_location.style.fill = color_location;
							elm_location.style.textAnchor = 'middle';
							var elm_text = document.createTextNode((arr_first_object_sub.location_name ? arr_first_object_sub.location_name : ''));
							elm_location.appendChild(elm_text);
							fragment_plot_locations.appendChild(elm_location);

							arr_object_sub_dot.obj_location = {elm: elm_location, visible: false, height: size_location, obj_filter: false};
							
							//elm_location.setAttribute('x', elm_location.getAttribute('x') - (arr_object_sub_dot.obj_location.width / 2)); // Using text-anchor text is automatically centered 
							//elm_location.setAttribute('y', elm_location.getAttribute('y') - arr_object_sub_dot.obj_location.height); // svg positions the y axis of text to its bottom
							
							if (hint_dot === 'location') {
								
								var id = 'filter_location_'+fragment_plot_locations.children.length;
								
								var elm_filters = stage.createElementNS(stage_ns, 'filter');
								elm_filters.setAttribute('id', id);
								drawer_defs.appendChild(elm_filters);
															
								var matrix = [
									1,0,0,0,0,
									0,1,0,0,0,
									0,0,1,0,0,
									0,0,0,1,0
								];
								var elm_filter = stage.createElementNS(stage_ns, 'feColorMatrix');
								elm_filter.setAttribute('in', 'SourceGraphic');
								elm_filter.setAttribute('type', 'matrix');
								elm_filter.setAttribute('values', matrix.join(' '));
								elm_filters.appendChild(elm_filter);
								
								elm_location.setAttribute('filter', 'url(#'+id+')');
								
								arr_object_sub_dot.obj_location.obj_filter = {func_update: function() {
									elm_filter.setAttribute('values', matrix.join(' '));
								}, matrix: matrix};
							}
						} else {
							
							var elm_location = arr_object_sub_dot.obj_location.elm;
						}
						
						if (offset_location == 0) {
							var offset = (arr_object_sub_dot.obj_location.height / 2);
						} else if (offset_location < 0) {
							var offset = -((size + width_dot_stroke) / 2) + offset_location;
						} else {
							var offset = ((size + width_dot_stroke) / 2) + offset_location + arr_object_sub_dot.obj_location.height;
						}
						
						elm_location.setAttribute('class', 'hide');
						elm_location.setAttribute('transform', 'translate('+(x - pos_offset_x)+' '+(y - pos_offset_y + offset)+')');
					}
				}
			
				arr_object_sub_dot.elm = elm_plot;

				is_new = true;
			}
			
			if (show_location) {
			
				var visible = elm_location.visible;
				
				arr_object_sub_dot.obj_location.visible = do_show_location;
				
				if (display == 'pixel') {
					
					elm_location.visible = do_show_location;
					if (elm_location.visible != visible) {
						do_render_locations = true;
					}
				} else {
					
					elm_location.setAttribute('class', (do_show_location ? '' : 'hide'));
				}
			}
		}
		
		arr_object_sub_dot.identifier = identifier;
		
		var has_geometry = show_geometry && !(arr_object_sub_dot.arr_geometry_plotable.length == 3 && arr_object_sub_dot.arr_geometry_plotable[0] == 'Point');

		if (has_geometry) {
			
			var color_style_geometry = color_geometry;
			var opacity_style_geometry = opacity_geometry;
			var color_style_geometry_stroke = color_geometry_stroke;
			var opacity_style_geometry_stroke = opacity_geometry_stroke;
			
			for (var i = 0, len = arr_object_sub_dot.ref.length; i < len; i++) {
				
				var object_sub_id = arr_object_sub_dot.ref[i];
				
				if (!object_sub_id) {
					continue;
				}
				
				var arr_object_sub = arr_data.object_subs[object_sub_id];
				
				if (arr_object_sub.style.geometry_color) {
					color_style_geometry = arr_object_sub.style.geometry_color;
				}
				if (arr_object_sub.style.geometry_opacity) {
					opacity_style_geometry = arr_object_sub.style.geometry_opacity;
				}
				if (arr_object_sub.style.geometry_stroke_color) {
					color_style_geometry_stroke = arr_object_sub.style.geometry_stroke_color;
				}
				if (arr_object_sub.style.geometry_stroke_opacity) {
					opacity_style_geometry_stroke = arr_object_sub.style.geometry_stroke_opacity;
				}
			}
			
			var identifier_geometry = cur_zoom;
			
			if (arr_object_sub_dot.identifier_geometry === identifier_geometry) {
				
				var elm_geometry = arr_object_sub_dot.elm_geometry;

				if (!arr_object_sub_dot.added) {
					
					if (display == 'pixel') {
						elm_plot_geometry.addChild(elm_geometry);
					} else {
						fragment_plot_geometry.appendChild(elm_geometry);
					}
				}
							
				if (display == 'pixel') {
					
					elm_geometry.visible = true;
					elm_geometry.position.x = x - pos_offset_x;
					elm_geometry.position.y = y - pos_offset_y;
					do_render_geometry = true;
				} else {
					
					elm_geometry.setAttribute('class', 'geometry');
					elm_geometry.setAttribute('transform', 'translate('+(x - pos_offset_x)+' '+(y - pos_offset_y)+')');
				}
			} else {
				
				if (arr_object_sub_dot.added) {
					elm_plot_geometry.removeChild(arr_object_sub_dot.elm_geometry);
				}

				var arr_geometry_plotable = arr_object_sub_dot.arr_geometry_plotable;
				var geometry_center_x = arr_object_sub_dot.arr_geometry_center.x;
				var geometry_center_y = arr_object_sub_dot.arr_geometry_center.y;
				var i_geo = 0;
				var len_i_geo = arr_geometry_plotable.length;

				if (display == 'pixel') {
					
					var elm_geometry = new PIXI.Container();
						
					while (i_geo < len_i_geo) {
							
						switch (arr_geometry_plotable[i_geo]) {
							case 'Group':
								
								i_geo++;
								
								break;
							case 'Point':
								
								i_geo++;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number') {
																		
									var elm_geo = new PIXI.Graphics();
									if (opacity_style_geometry) {
										elm_geo.beginFill(GeoUtilities.parseColor(color_style_geometry), opacity_style_geometry);
									}
									if (width_geometry_stroke) {
										elm_geo.lineStyle(width_geometry_stroke, GeoUtilities.parseColor(color_style_geometry_stroke), opacity_style_geometry_stroke);
									}
									elm_geo.drawCircle((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x, (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y, size_dot.min/2);
									
									elm_geo.endFill();
									elm_geometry.addChild(elm_geo);

									i_geo += 2;
								}
								
								break;
							case 'LineString':
								
								i_geo++;
								
								var elm_geo = new PIXI.Graphics();
								elm_geo.lineStyle((width_geometry_stroke ? width_geometry_stroke : 1), GeoUtilities.parseColor((color_style_geometry_stroke ? color_style_geometry_stroke : color_style_geometry)), opacity_style_geometry_stroke);								
															
								elm_geo.moveTo((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x, (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
								
								i_geo += 2;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number') {
																											
									elm_geo.lineTo((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x, (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
									
									i_geo += 2;
								}

								elm_geometry.addChild(elm_geo);
								
								break;
							case 'Polygon':
																	
								var elm_geo = new PIXI.Graphics();
								if (opacity_style_geometry) {
									elm_geo.beginFill(GeoUtilities.parseColor(color_style_geometry), opacity_style_geometry);
								}
								if (width_geometry_stroke) {
									elm_geo.lineStyle(width_geometry_stroke, GeoUtilities.parseColor(color_style_geometry_stroke), opacity_style_geometry_stroke);
								}
								
								i_geo++;
								
								var str_ring = arr_geometry_plotable[i_geo];

								while (str_ring === 'ring' || str_ring === 'hole') {
									
									i_geo++;

									var arr_points = [];
									var i = 0;

									while (typeof arr_geometry_plotable[i_geo] === 'number') {

										arr_points[i] = (arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x;
										arr_points[i+1] = (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y;
											
										i_geo += 2;
										i += 2;
									}
									
									elm_geo.drawPolygon(arr_points);
									
									if (str_ring === 'hole') {
										elm_geo.addHole();
									}
									
									str_ring = arr_geometry_plotable[i_geo];
								}
								
								elm_geo.endFill();
								elm_geometry.addChild(elm_geo);
								
								break;
						}
					}
					
					elm_plot_geometry.addChild(elm_geometry);

					elm_geometry.position.x = x - pos_offset_x;
					elm_geometry.position.y = y - pos_offset_y;
					do_render_geometry = true;
				} else {
					
					var elm_geometry = stage.createElementNS(stage_ns, 'g');
					fragment_plot_geometry.appendChild(elm_geometry);
						
					while (i_geo < len_i_geo) {
							
						switch (arr_geometry_plotable[i_geo]) {
							case 'Group':
								
								i_geo++;
								
								break;
							case 'Point':
								
								i_geo++;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number') {
									
									var elm_geo = stage.createElementNS(stage_ns, 'circle');
									elm_geo.setAttribute('cx', (arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x);
									elm_geo.setAttribute('cy', (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
									elm_geo.setAttribute('r', size_dot.min/2);
									if (opacity_style_geometry) {
										elm_geo.style.fill = color_style_geometry;
										elm_geo.style.fillOpacity = opacity_style_geometry;
									} else {
										elm_geo.style.fill = 'none';
									}
									if (width_geometry_stroke) {
										elm_geo.style.stroke = color_style_geometry_stroke;
										elm_geo.style.strokeWidth = width_geometry_stroke;
										elm_geo.style.strokeOpacity = opacity_style_geometry_stroke;
									}
												
									elm_geometry.appendChild(elm_geo);

									i_geo += 2;
								}
								
								break;
							case 'LineString':
								
								i_geo++;
								
								var elm_geo = stage.createElementNS(stage_ns, 'path');
								elm_geo.style.stroke = (color_style_geometry_stroke ? color_style_geometry_stroke : color_style_geometry);
								elm_geo.style.strokeWidth = (width_geometry_stroke ? width_geometry_stroke : 1);
								elm_geo.style.strokeOpacity = opacity_style_geometry_stroke;		
								elm_geo.style.fill = 'none';				
								
								var str_path = '';
								
								str_path += 'M '+((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x)+' '+((arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);					
								
								i_geo += 2;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number') {
									
									str_path += 'L '+((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x)+' '+((arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);																		
									
									i_geo += 2;
								}
								
								elm_geo.setAttribute('d', str_path);
								elm_geometry.appendChild(elm_geo);
								
								break;
							case 'Polygon':
							
								var elm_geo = stage.createElementNS(stage_ns, 'path');
								if (opacity_style_geometry) {
									elm_geo.style.fill = color_style_geometry;
									elm_geo.style.fillOpacity = opacity_style_geometry;
								} else {
									elm_geo.style.fill = 'none';
								}
								if (width_geometry_stroke) {
									elm_geo.style.stroke = color_style_geometry_stroke;
									elm_geo.style.strokeWidth = width_geometry_stroke;
									elm_geo.style.strokeOpacity = opacity_style_geometry_stroke;
								}
								elm_geo.style.fillRule = 'evenodd';
								
								i_geo++;

								var str_path = '';
								
								var str_ring = arr_geometry_plotable[i_geo];

								while (str_ring === 'ring' || str_ring === 'hole') {
									
									i_geo++;

									if (str_path) {
										str_path += ' ';
									}
																		
									str_path += 'M '+((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x)+' '+((arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
									
									i_geo += 2;
									
									while (typeof arr_geometry_plotable[i_geo] === 'number') {
										
										str_path += 'L '+((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x)+' '+((arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
										
										i_geo += 2;
									}

									str_path += ' z';
									
									str_ring = arr_geometry_plotable[i_geo];
								}
								
								elm_geo.setAttribute('d', str_path);
								elm_geometry.appendChild(elm_geo);
								
								break;
						}
					}
					
					elm_geometry.setAttribute('class', 'geometry');
					elm_geometry.setAttribute('transform', 'translate('+(x - pos_offset_x)+' '+(y - pos_offset_y)+')');
				}
				
				arr_object_sub_dot.elm_geometry = elm_geometry;
				arr_object_sub_dot.identifier_geometry = identifier_geometry;
			}
		}
		
		arr_object_sub_dot.added = true;
		
		return is_new;
	};
	
	var drawDotHint = function(arr_object_sub_dot) {

		if (hint_dot === 'location') {
			
			var arr_hint = arr_object_sub_dot.arr_hint_queue[0];
			
			var obj_location = arr_object_sub_dot.obj_location;
			var elm_location = obj_location.elm;
			
			if (display == 'pixel') {
				elm_location.visible = true;
			} else {
				elm_location.setAttribute('class', '');
			}
			
			if (arr_hint) {

				arr_hint[0] = false;
			} else {
				
				count_animate_locations++;
				
				arr_hint = [false];

				arr_object_sub_dot.arr_hint_queue[0] = arr_hint;
			}
		} else {
			
			if (arr_object_sub_dot.arr_hint_queue.length == 3) {
			
				var arr_hint = arr_object_sub_dot.arr_hint_queue;
				
				arr_hint[0][0] = false;
				arr_hint.push(arr_hint.shift());
				
				return;
			}
			
			count_animate_between++;
			
			var size_r = (arr_object_sub_dot.obj_settings.size / 2);
			
			if (display == 'pixel') {
						
				var elm = new PIXI.Graphics();

				elm_plot_between.addChild(elm);
			} else {
				
				var elm = stage.createElementNS(stage_ns, 'circle');

				if (opacity_hint) {
					elm.style.fill = color_hint;
					elm.style.fillOpacity = opacity_hint;
				} else {
					elm.style.fill = 'none';
				}
				if (width_hint_stroke) {
					elm.style.stroke = color_hint_stroke;
					elm.style.strokeWidth = width_hint_stroke;
				}
				
				fragment_plot_between.appendChild(elm);
			}
							
			var arr_hint = [false, elm, size_r];
			
			arr_object_sub_dot.arr_hint_queue.push(arr_hint);
		}
	};	
	
	var drawLine = function(arr_object_sub_line, arr_object_sub_lines_loc, offset, count) {
		
		var obj_settings = arr_object_sub_line.obj_settings;
		
		var size = arr_object_sub_line.weight/3;
		size = Math.ceil(size > width_line_max ? width_line_max : (size < width_line_min ? width_line_min : size));
		
		obj_settings.size = size;
		
		var color = false;
		var show_info = (info_condition ? false : true);
		
		if (color_line) { // Override colour
			
			color = color_line;
		}
		
		if (!color || info_condition) {
			
			var arr_connect_object_sub_ids = arr_object_sub_line.connect_object_sub_ids;
			
			for (var i = arr_connect_object_sub_ids.length-1; i >= 0; i--) {
				
				var connect_object_sub_id = arr_connect_object_sub_ids[i];

				if (connect_object_sub_id) {
					
					var arr_object_sub_style = arr_data.object_subs[connect_object_sub_id].style;
					
					if (!color) {
						
						color = arr_object_sub_style.color;
						
						if (typeof color == 'object') { // Select last color color contains multiple values
							color = color[color.length-1];
						}
					}
					if (info_condition && !show_info && arr_object_sub_style.conditions) {
						
						show_info = arr_object_sub_style.conditions[info_condition];
					}
				}
			}
		}
		
		if (!color) { // Default colour
			
			var arr_legend = arr_data.legend.object_subs[arr_object_sub_line.connect_object_sub_details_id];
			
			var arr_color = arr_legend.color;
			color = 'rgb('+arr_color.red+','+arr_color.green+','+arr_color.blue+')';
		}
				
		var alternate = (mode != 'move' && count % 2);
		
		var x_start = Math.floor(arr_object_sub_lines_loc.xy_start.x);
		var y_start = Math.floor(arr_object_sub_lines_loc.xy_start.y);
		var x_end = Math.floor(arr_object_sub_lines_loc.xy_end.x);
		var y_end = Math.floor(arr_object_sub_lines_loc.xy_end.y);
		
		var identifier = (mode != 'move' ? cur_zoom+''+offset : '')+''+size+''+color+''+(alternate ? '1' : '0');
		
		if (mode == 'move' && move_retain === 'all' && arr_object_sub_line.identifier == identifier && arr_object_sub_line.obj_move.status !== false && !redraw) { // Main instance is already running in move_retain mode, force an additional new instance
			identifier = false;
		}
		
		if (identifier && arr_object_sub_line.identifier == identifier) {
				
			var elm = arr_object_sub_line.elm;
			
			if (!arr_object_sub_line.added) {
				
				if (display == 'pixel') {
				
					if (mode != 'move') {

						elm_plot_lines.addChild(elm);
					}
				} else {
					
					fragment_plot_lines.appendChild(elm);
				}
			}
						
			if (display == 'pixel') {
				
				if (mode == 'move') {
					
					var obj_move = arr_object_sub_line.obj_move;

					if (show_info && !arr_object_sub_line.info_show) {
						count_info_show_object_sub_lines++;
						arr_object_sub_lines_loc.count_info_show++;
					}
					arr_object_sub_line.info_show = show_info;

					if (redraw === 'reset' || (!redraw && !arr_object_sub_line.active)) {
						
						obj_move.perc = 0;
						obj_move.status = 1;
						obj_move.delay = offset*1000;
						obj_move.cur_delay = offset*1000;
						
						if (obj_move.cur_delay) { // The move instance has to wait a bit, reset the visibility while waiting 
							
							obj_move.elm.alpha = 0;
							
							for (var i = 0; i < length_move_warp; i++) {
								
								obj_move.arr_elm_warp[i].alpha = 0;
							}
						}
						
						arr_object_sub_line.active = true;
					}
					
					if (obj_move.key === false) {
						obj_move.key = arr_animate_move.push(obj_move);
					}
				} else {
					
					elm.visible = true;
					elm.position.x = -pos_offset_x;
					elm.position.y = -pos_offset_y;
				}
			} else {
				
				elm.setAttribute('class', 'line');
				elm.setAttribute('transform', 'translate('+(-pos_offset_x)+' '+(-pos_offset_y)+')');
			}
		} else {
			
			if (mode != 'move') {
				
				var p1 = {x: x_start, y: y_start};
				var p2 = {x: x_end, y: y_end};
			
				var c1 = GeoUtilities.calcPointOffset(p1, p2, 0.5+offset, 0.25);
				var c2 = GeoUtilities.calcPointOffset(p1, p2, 0.5+offset, 0.75);
				var cc1 = GeoUtilities.calcPointOffset(p1, p2, offset+size-0.5, 0.75);
				var cc2 = GeoUtilities.calcPointOffset(p1, p2, offset+size-0.5, 0.25);
				
				if (alternate) { // Alternate colours when stacking them
					var arr_color = GeoUtilities.colorToBrightColor(color, 22);
					color = 'rgb('+arr_color.r+','+arr_color.g+','+arr_color.b+')';
				}
			}
		
			if (display == 'pixel') {
				
				var hex_color = GeoUtilities.parseColor(color);
				
				if (mode == 'move') {
					
					var obj_move = arr_object_sub_line.obj_move;
					
					if (arr_object_sub_line.added && identifier) { // Identifier has changed, require full redraw
						
						obj_move.elm_container[0].removeChild(obj_move.elm);
									
						for (var i = 0; i < length_move_warp; i++) {
							obj_move.elm_container[1].removeChild(obj_move.arr_elm_warp[i]);
						}
						
						obj_move.arr_elm_warp = [];
					}
					
					var identifier_texture = size+'-'+hex_color;
					
					var arr_elm_particles = (!identifier ? arr_elm_plot_line_particles_temp : arr_elm_plot_line_particles);
					
					var elm_container = arr_elm_particles[identifier_texture];
					
					if (!elm_container || elm_container[0].children.length >= size_max_elm_container) {
						
						if (!identifier) {
							
							if (elm_container) { // Container is full
								
								var elm_container = false;
								var count = 0;
								
								while (1) {
									
									var identifier_texture_temp = identifier_texture+'-'+count;
									
									if (!arr_elm_particles[identifier_texture_temp]) { // Store the full identifier_texture container to identifier_texture_temp, freeing up identifier_texture for a new container

										arr_elm_particles[identifier_texture_temp] = arr_elm_particles[identifier_texture];
										break;
									} else if (arr_elm_particles[identifier_texture_temp][0].children.length < (size_max_elm_container / 10)) { // Use a previous identifier_texture_temp container when there is enough room again (at least 90% space), replace the full identifier_texture container with the now usable identifier_texture_temp container
										
										elm_container = arr_elm_particles[identifier_texture_temp];
										arr_elm_particles[identifier_texture_temp] = arr_elm_particles[identifier_texture];
										arr_elm_particles[identifier_texture] = elm_container;
										break;
									}
									
									count++;
								}
							}
						}
						
						if (identifier || (!identifier && !elm_container)) {
								
							arr_elm_particles[identifier_texture] = [];
							
							if (move_glow) {
								arr_elm_particles[identifier_texture][0] = new PIXI.particles.ParticleContainer(size_max_elm_container / length_move_warp, {position: true, alpha: true});
								arr_elm_particles[identifier_texture][1] = new PIXI.particles.ParticleContainer(size_max_elm_container, {position: true, alpha: true});
								
								var elm_container = arr_elm_particles[identifier_texture];
								elm_plot_lines.addChild(elm_container[1]);
								elm_plot_lines.addChild(elm_container[0]);
							} else {
								arr_elm_particles[identifier_texture][0] = arr_elm_particles[identifier_texture][1] = new PIXI.particles.ParticleContainer(size_max_elm_container, {position: true, alpha: true});
								
								var elm_container = arr_elm_particles[identifier_texture];
								elm_plot_lines.addChild(elm_container[0]);
							}
						}
					}
					
					var arr_texture = arr_assets_texture_line_dots[identifier_texture];
					var r = (size/2);
					
					if (!arr_texture) {
						
						var arr_texture = [];
						
						var elm = new PIXI.Graphics();
						elm.beginFill(hex_color, 1);
						elm.drawCircle(r, r, r);
						elm.endFill();
						
						if (move_glow) {
							
							var size = r*3;
							
							var canvas = document.createElement('canvas');
							canvas.width = size*2;
							canvas.height = size*2;
							var context = canvas.getContext('2d');
							
							var arr_color = parseCssColor(color);
							
							var gradient = context.createRadialGradient(size, size, r, size, size, size);
							gradient.addColorStop(0, 'rgba('+arr_color.r+', '+arr_color.g+', '+arr_color.b+', 0.4)');
							gradient.addColorStop(1, 'rgba('+arr_color.r+', '+arr_color.g+', '+arr_color.b+', 0)');
							context.beginPath();
							context.fillStyle = gradient;
							context.arc(size, size, size, 0, 360);
							context.fill();
							context.beginPath();
							context.fillStyle = 'rgba('+arr_color.r+', '+arr_color.g+', '+arr_color.b+', 1)';
							context.arc(size, size, r, 0, 360);
							context.fill();
							
							arr_texture[0] = new PIXI.Texture.fromCanvas(canvas);
							arr_texture[1] = elm.generateCanvasTexture();
						} else {
							
							arr_texture[0] = elm.generateCanvasTexture();
							arr_texture[1] = arr_texture[0];
						}
											
						arr_assets_texture_line_dots[identifier_texture] = arr_texture;
					}
					
					var elm = new PIXI.Sprite(arr_texture[0]);
					
					/*var glow = new GlowFilter(renderer_activity.width, renderer_activity.height, 15, 2, 2, hex_color, 0.5);
					elm.filters = [glow];*/
					
					elm.position.x = x_start - pos_offset_x;
					elm.position.y = y_start - pos_offset_y;
					elm.anchor.x = 0.5;
					elm.anchor.y = 0.5;
					
					elm_container[0].addChild(elm);
					
					if (move_retain === 'all') {
						
						if (!identifier) { // Spawn additional instance replacing the main instance, reset the main instance

							var obj_move_instance_new = {arr_object_sub_lines_loc: obj_move.arr_object_sub_lines_loc, arr_object_sub_line: obj_move.arr_object_sub_line, elm: false, elm_container: false, key: false, xy_start: obj_move.xy_start, xy_end: obj_move.xy_end, obj_path: obj_move.obj_path, arr_elm_warp: [], start: obj_move.start, duration: obj_move.duration, delay: 0, cur_delay: 0, perc: obj_move.perc, status: obj_move.status};
							
							// Check and cleanup old moveing instances from previous selections, prevent flooding 
							var len = arr_object_sub_line.arr_move_queue.length;
							
							if (len >= 2) { // Maximum of 2 additional instances
							
								var arr_move_queue = arr_object_sub_line.arr_move_queue;
								
								var count = 1;
								for (var i = len-1; i >= 0; i--) {
									
									var obj_move_instance = arr_move_queue[i];
									
									if (obj_move_instance.key === false || count >= 2) {
										
										arr_move_queue.splice(i, 1);
										obj_move_instance.key = false;
									} else {
										
										count++;
									}
								}
							}
							
							arr_object_sub_line.arr_move_queue.push(obj_move_instance_new);
							
							// Reset main instance
							obj_move.perc = 0;
							obj_move.status = 1;
							obj_move.delay = offset*1000;
							obj_move.cur_delay = offset*1000;
							
							arr_object_sub_line.active = true;
							
							// Continue with spawned instance
							obj_move = obj_move_instance_new;
						}
					}
					
					obj_move.elm = elm;
					obj_move.elm_container = elm_container;
					
					for (var i = 1; i < (length_move_warp+1); i++) {
						
						var elm_warp = new PIXI.Sprite(arr_texture[1]);
						elm_warp.anchor.x = 0.5;
						elm_warp.anchor.y = 0.5;
						elm_warp.scale.x = 1 - (i * (0.3/(length_move_warp+1)));
						elm_warp.scale.y = 1 - (i * (0.3/(length_move_warp+1)));
						
						elm_container[1].addChild(elm_warp);
						obj_move.arr_elm_warp.push(elm_warp);
					}
					
					if (identifier) {
						
						if (predraw || redraw === 'reset' || (!redraw && !arr_object_sub_line.active)) {
							
							obj_move.perc = 0;
							obj_move.status = 1;
							obj_move.delay = offset*1000;
							obj_move.cur_delay = offset*1000;
							
							arr_object_sub_line.active = true;
						}
						
						if (move_unit == 'day') {
							
							var arr_object_subs_line_details = getObjectSubsLineDetails(arr_object_sub_line);
							var dateint_range_sub = arr_object_subs_line_details.dateint_range;
							var date_min = DATEPARSER.int2Date(dateint_range_sub.min).getTime();
							
							var time_days = ((DATEPARSER.int2Date(dateint_range_sub.max).getTime() - date_min) / DATEPARSER.time_day) + 1; // Add one day as one day is absolute minimum (same day)
							var duration = time_days / speed_move;

							if (duration_move_min && duration < duration_move_min) {
								duration = duration_move_min;
							} else if (duration_move_max && duration > duration_move_max) {
								duration = duration_move_max;
							}
							obj_move.duration = duration*1000;
							obj_move.start = date_min;
						}
					}
					
					if (obj_move.key === false) {
						obj_move.key = arr_animate_move.push(obj_move);
					}
										
					if (show_info && !arr_object_sub_line.info_show) {
						count_info_show_object_sub_lines++;
						arr_object_sub_lines_loc.count_info_show++;
					}
					arr_object_sub_line.info_show = show_info;
				} else {
					
					if (arr_object_sub_line.added) {
						elm_plot_lines.removeChild(arr_object_sub_line.elm);
					}

					var elm = new PIXI.Graphics();
					elm.lineStyle(1, hex_color, 0.8);
					elm.beginFill(hex_color, 0.8);
					elm.moveTo(x_start, y_start).bezierCurveTo(c1.x, c1.y, c2.x, c2.y, x_end, y_end).bezierCurveTo(cc1.x, cc1.y, cc2.x, cc2.y, x_start, y_start);

					elm.position.x = -pos_offset_x;
					elm.position.y = -pos_offset_y;
					
					elm_plot_lines.addChild(elm);
				}
			} else {
				
				if (arr_object_sub_line.added) {
					elm_plot_lines.removeChild(arr_object_sub_line.elm);
				}
				
				var elm = stage.createElementNS(stage_ns, 'path');
				elm.setAttribute('d','M '+x_start+','+y_start+' C '+c1.x+','+c1.y+' '+c2.x+','+c2.y+' '+x_end+','+y_end+' C '+cc1.x+','+cc1.y+' '+cc2.x+','+cc2.y+' '+x_start+','+y_start+'');
				elm.style.fill = color;
				elm.style.stroke = color;
				elm.style.strokeWidth = 1;
				elm.style.opacity = 0.8;
				fragment_plot_lines.appendChild(elm);
				
				elm.setAttribute('transform', 'translate('+(-pos_offset_x)+' '+(-pos_offset_y)+')');
				elm.setAttribute('class', 'line');
			}
		}
		
		arr_object_sub_line.added = true;
		
		if (identifier && arr_object_sub_line.identifier != identifier) {
			
			arr_object_sub_line.elm = elm;
			if (mode == 'move') {
				arr_object_sub_line.elm_container = obj_move.elm_container;
				arr_object_sub_line.arr_elm_warp = obj_move.arr_elm_warp;
			}
			arr_object_sub_line.identifier = identifier;
			return true;
		}
		
		return false;
	};
		
	var removeLine = function(obj) {
		
		if (mode == 'move') {
						
			var obj_move = obj.obj_move;
				
			if (move_retain) {
					
				if (obj_move.key) { // Keep the move instance around until it's finished animating
					return;
				} else { // Remove directly (i.e. it's outside of bounds)
					removeLineInstance(obj_move);
				}
			} else {
					
				obj_move.key = false;
			}
			
			if (obj.obj_info.added) {
				obj.obj_info.elm.alpha = 0;
			}
		} else {
			
			if (obj.identifier) {
				
				if (display == 'pixel') {
					obj.elm.visible = false;
				} else {
					obj.elm.setAttribute('class', 'hide');
				}
			}
		}
	};
	
	var removeLineInstance = function(obj_move) {
		
		// mode == 'move'
						
		obj_move.key = false;
			
		var obj_self = obj_move.arr_object_sub_line;
		var is_instance_self = (obj_self.obj_move === obj_move);

		if (is_instance_self) {
			
			if (obj_self.obj_info.added) {
				obj_self.obj_info.elm.alpha = 0;
			}
		}

		if (is_instance_self) {
			
			obj_move.elm.alpha = 0;
			
			for (var i = 0; i < length_move_warp; i++) {
				obj_move.arr_elm_warp[i].alpha = 0;
			}
		} else {
			
			obj_move.elm_container[0].removeChild(obj_move.elm);
						
			for (var i = 0; i < length_move_warp; i++) {
				obj_move.elm_container[1].removeChild(obj_move.arr_elm_warp[i]);
			}
		}
	};	

	var displayGeoInfo = function() {
		
		/*var distance = 0,
		longest = 0,
		shortest = 0;
		
		var arr_loop = arr_loop_object_sub_lines.arr_loop;

		for (var i = 0, len = arr_loop.length; i < len; i++) {
			
			var arr_object_sub_lines_loc = arr_loop[i];
						
			var arr_object_sub_dot = arr_object_sub_dots[arr_object_sub_lines_loc.location_geometry];
			var arr_connect_object_sub_dot = arr_object_sub_dots[arr_object_sub_lines_loc.connect_location_geometry];
			
			var arr_loop_loc = arr_object_sub_lines_loc.arr_con;
					
			for (var j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
				
				var arr_object_sub_line = arr_loop_loc[j];
				
				if (!arr_object_sub_line.count) {
					continue;
				}
				
				var latitude1 = arr_object_sub_dot.arr_geometry_center[1] * Math.PI / 180;
				var lonitude1 = arr_object_sub_dot.arr_geometry_center[0] * Math.PI / 180;
					
				var latitude2 = arr_connect_object_sub_dot.arr_geometry_center[1] * Math.PI / 180;
				var lonitude2 = arr_connect_object_sub_dot.arr_geometry_center[0] * Math.PI / 180;
				
				var d = Math.acos(Math.sin(latitude1)*Math.sin(latitude2) + 
								  Math.cos(latitude1)*Math.cos(latitude2) *
								  Math.cos(lonitude2-lonitude1)) * 6371;
								  
				//d = d * arr_object_sub_lines[loc_id][con_id].ref.length;

				distance = distance + d;
				if (d > longest) {longest = d};
				
				if (shortest) {
					if (d < shortest) {shortest = d};
				} else { 
					shortest = d;
				}

			}
		}
		
		distance = Math.round(distance);
		longest = Math.round(longest);
		shortest = Math.round(shortest);
		
		obj_parent.elm_controls.children('.geo').append('<p>Total distance: '+distance+' km</p><p>Longest distance: '+longest+' km</p><p>Shortest distance: '+shortest+' km</p>');*/
	};
};
