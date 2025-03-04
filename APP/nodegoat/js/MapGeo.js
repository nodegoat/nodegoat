
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapGeo(elm_draw, PARENT, options) {

	var elm = $(elm_draw),
	elm_host = PARENT.elm_paint_host,
	SELF = this;
	
	const DISPLAY_VECTOR = 1;
	const DISPLAY_PIXEL = 2;
	const MODE_CONNECT = 1;
	const MODE_MOVE = 2;
	const REDRAW_NONE = 0;
	const REDRAW_DEFAULT = 1;
	const REDRAW_POSITION = 2;
	const REDRAW_SCALE = 3;
	const REDRAW_RESET = 4;
	
	var	has_init = false,
	arr_labels = {},
	arr_object_sub_dots = {},
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
	size_map = {},
	do_render_geometry = false,
	do_render_locations = false,
	do_render_connection_lines = false,
	do_render_dots = false,
	do_render_lines = false,
	redraw = REDRAW_NONE,
	in_predraw = false,
	do_draw = false,
	cur_zoom = false,
	dateinta_range = false,
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
	is_dragging = false,
	elm_hover = false,
	type_elm_hover = false,
	elm_hover_target = false,
	type_elm_hover_target = false,
	elm_hover_check = null,
	
	display = DISPLAY_VECTOR,
	font_family = 'pixel',
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
	color_highlight = false,
	svg_class_highlight = 'highlight',
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
	width_connection_line = 1.5,
	color_connection_line = '#ffffff',
	show_info_line = false, // false, true, int
	show_info_dot = false, // false, true, int
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
	mode = MODE_CONNECT,
	show_location = false,
	color_location = false,
	opacity_location = false,
	size_location = false,
	width_location_line = 1,
	threshold_location = false,
	location_position_algorithmic = false, // false, true
	location_position_manual = false,
	offset_location = -5,
	location_condition = false,
	show_geo_info = false,
	show_geometry = false,
	color_geometry = false,
	opacity_geometry = false,
	width_geometry_stroke = false,
	color_geometry_stroke = false,
	opacity_geometry_stroke = false,
	opacity_geometry = false,
	size_dot_icons = 15,
	do_dot_icons_weight = false,
	offset_dot_icons = 4,
	svg_style = false,
	use_best_quality = true,
	
	calc_plot_point = 1,
	pos_offset_x = 0,
	pos_offset_y = 0,
	pos_offset_extra_x = 0,
	pos_offset_extra_y = 0,
	
	arr_animate_move = [],
	time_animate = 0,
	time_poll = 0,
	pos_hover_poll = false,
	do_show_info_line = false,
	do_show_info_dot = false,
	worker_labels = null,

	arr_assets_colors_obj = {},
	arr_assets_elm_line_dot_paths = {},
	arr_assets_texture_line_dots = {},
	arr_assets_texture_info = {},
	arr_assets_texture_icons = {},
	is_weighted = false,
	count_dot_weight_min = 0,
	count_dot_weight_max = 0,
	count_line_weight_max = 0,
	spacer_elm_info = 2,
	spacer_elm_icons = 6,
	size_info = 8,
	offset_info_hover = 5,
	size_max_elm_container = 15000,
	num_percentage_hint_location_start = 0.5,
	
	settings_timeline = false,
	
	GeoUtilities = new MapGeoUtilities(PARENT.obj_map);

	this.init = function() {
		
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
		
		display = options.arr_visual.settings.geo_display;
		mode = options.arr_visual.settings.geo_mode;
		
		var use_capture = (options.arr_visual.capture.enable ? true : false);
		if (typeof options.arr_visual.settings.geo_advanced.best_quality != 'undefined') {
			use_best_quality = parseBool(options.arr_visual.settings.geo_advanced.best_quality);
		}

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
		if (typeof options.arr_visual.settings.geo_advanced.connection_line_color != 'undefined') {
			color_connection_line = options.arr_visual.settings.geo_advanced.connection_line_color;
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
		if (typeof options.arr_visual.settings.geo_advanced.connection_line_width != 'undefined') {
			width_connection_line = parseFloat(options.arr_visual.settings.geo_advanced.connection_line_width);
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
			if (options.arr_visual.settings.geo_advanced.dot_icons_size === 'weight') {
				do_dot_icons_weight = true;
			} else {
				size_dot_icons = parseInt(options.arr_visual.settings.geo_advanced.dot_icons_size);
			}
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

		if (display == DISPLAY_PIXEL && mode == MODE_MOVE) {
			show_info_dot = true;
			show_info_line = true;
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
		if (typeof options.arr_visual.settings.geo_advanced.highlight_color != 'undefined') {
			color_highlight = parseBool(options.arr_visual.settings.geo_advanced.highlight_color, true);
			if (color_highlight === true) {
				color_highlight = '#d92b2b';
			}
		}
		if (typeof options.arr_visual.settings.geo_advanced.svg_style != 'undefined') {
			svg_style = parseBool(options.arr_visual.settings.geo_advanced.svg_style, true);
			svg_style = (svg_style ? svg_style : false);
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
		opacity_location = options.arr_visual.location.opacity;
		size_location = options.arr_visual.location.size;
		threshold_location = options.arr_visual.location.threshold;
		location_position_algorithmic = options.arr_visual.location.position.mode;
		location_position_manual = options.arr_visual.location.position.manual;
		offset_location = options.arr_visual.location.offset;
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
			arr_assets_colors_obj.location = parseCSSColor(color_location);
			arr_assets_colors_obj.hint = parseCSSColor(color_hint);
		}
		if (display == DISPLAY_VECTOR) {
			show_info_line = false;
			show_info_dot = false;
		} else if (display == DISPLAY_PIXEL) {
			if (mode == MODE_CONNECT) {
				show_info_line = false;
				moved_hint_line = false;
			}
		}
		
		var pos_map = PARENT.obj_map.getPosition();
		
		let arr_scripts = ['/CMS/js/support/Bezier.js'];
		if (display == DISPLAY_PIXEL) {
			arr_scripts.push('/CMS/js/support/pixi.min.js', '/CMS/js/support/pixi.ExtraFilters.js');
		}
		if (show_location) {
			arr_scripts.push('/js/support/d3-force.pack.js');
		}

		ASSETS.fetch(elm_host, {
			script: arr_scripts, font: ['pixel'], labels: ['lbl_scope', 'lbl_location', 'lbl_date_start', 'lbl_date_end']
		}, function() {
				
			has_init = true;
			
			addListeners();
		
			var count_start = 0;
			
			count_start++; // Main loading

			var func_start = function() {
				
				if (count_start > 0) {
					return;
				}
				
				SELF.drawData = drawData;
			
				key_move = PARENT.obj_map.move(rePosition);
			};

			arr_data = PARENT.getData();
			
			if (arr_data.legend.conditions) {
						
				var arr_media = [];
				
				for (const key in arr_data.legend.conditions) {
					
					const arr_condition = arr_data.legend.conditions[key];
					
					if (arr_condition.icon) {			
						arr_media.push(arr_condition.icon);
					}
					
					if (arr_condition.weight && arr_condition.weight > 0) {
						is_weighted = true;
					}
				}
				
				if (arr_media.length) {
					
					count_start++; // Media loading
						
					ASSETS.fetch(elm_host, {media: arr_media}, function() {
						
						if (display == DISPLAY_PIXEL) {
							
							for (let i = 0, len = arr_media.length; i < len; i++) {
							
								const resource = arr_media[i];
								const arr_medium = ASSETS.getMedia(resource);
								const elm_image = arr_medium.image.cloneNode(false);
								
								const texture = PIXI.Texture.from(elm_image);
								
								arr_assets_texture_icons[resource] = {texture: texture, width: arr_medium.width, height: arr_medium.height};
							}
						}
						
						count_start--; // Media loaded
						
						func_start();
					});
				}
			}
						
			ASSETS.getLabels(elm_host, ['lbl_scope', 'lbl_location', 'lbl_date_start', 'lbl_date_end'], (data) => {arr_labels = data});

			if (display == DISPLAY_PIXEL) {

				size_renderer = {width: pos_map.view.width, height: pos_map.view.height, resolution: pos_map.render.resolution};

				var num_resolution = size_renderer.resolution;
				
				PIXI.GRAPHICS_CURVES.adaptive = true;
				PIXI.GRAPHICS_CURVES.maxLength = (use_capture ? 2 : 10); // Use higher segment resolution (shorter curve lengths) when capturing
				PIXI.settings.SPRITE_MAX_TEXTURES = Math.min(PIXI.settings.SPRITE_MAX_TEXTURES, 16);
				
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
				
				if (mode == MODE_MOVE) {
					
					renderer_geometry = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true});
					renderer_connection_lines = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true, forceCanvas: (use_best_quality ? true : false)});
					renderer_locations = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true});
					renderer_activity = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true});
					renderer_dots = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true, forceCanvas: (use_best_quality ? true : false)});

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
					
					renderer_geometry.events.autoPreventDefault = false;
					renderer_connection_lines.events.autoPreventDefault = false;
					renderer_locations.events.autoPreventDefault = false;
					renderer_activity.events.autoPreventDefault = false;
					renderer_dots.events.autoPreventDefault = false;
					drawer.style.removeProperty('touch-action');
					drawer_2.style.removeProperty('touch-action');
					drawer_3.style.removeProperty('touch-action');
					drawer_4.style.removeProperty('touch-action');
					drawer_5.style.removeProperty('touch-action');
				} else {
					
					renderer_geometry = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true});
					renderer_locations = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true});
					renderer_lines = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true, forceCanvas: (use_best_quality ? true : false)});
					renderer_activity = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true});
					renderer_dots = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: num_resolution, autoDensity: true, forceCanvas: (use_best_quality ? true : false)});

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
					
					renderer_geometry.events.autoPreventDefault = false;
					renderer_locations.events.autoPreventDefault = false;
					renderer_lines.events.autoPreventDefault = false;
					renderer_activity.events.autoPreventDefault = false;
					renderer_dots.events.autoPreventDefault = false;
					drawer.style.removeProperty('touch-action');
					drawer_2.style.removeProperty('touch-action');
					drawer_3.style.removeProperty('touch-action');
					drawer_4.style.removeProperty('touch-action');
					drawer_5.style.removeProperty('touch-action');
				}
			} else {
				
				size_renderer = {width: pos_map.size.width, height: pos_map.size.height};
				
				renderer = document.createElementNS(stage_ns, 'svg');
				renderer.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', stage_ns);
				
				drawer = renderer;
				drawer.style.width = size_renderer.width+'px';
				drawer.style.height = size_renderer.height+'px';
				elm[0].appendChild(drawer);
				
				stage = renderer.ownerDocument;

				elm_plot_locations = stage.createElementNS(stage_ns, 'g');
				drawer.appendChild(elm_plot_locations);
				fragment_plot_locations = document.createDocumentFragment();
				
				drawer_defs = stage.createElementNS(stage_ns, 'defs');
				drawer.appendChild(drawer_defs);
				
				const drawer_style_body = stage.createElementNS(stage_ns, 'style');
				drawer.appendChild(drawer_style_body);
				
				const node_style = document.createTextNode(`
					*[data-visible="0"] { display: none; }
				`);
				drawer_style_body.appendChild(node_style);
				
				if (use_capture) {
					
					count_start++; // Font loading

					ASSETS.getFiles(elm_host, ['Unibody8Pro-Regular'], function(arr_files) {
						
						for (const str_identifier in arr_files) {
						
							const reader = new FileReader();
							reader.onload = function(e) {
								
								const str_url = e.target.result;

								const node_style = document.createTextNode(`
									@font-face { 
										font-family: 'pixel';
										src: url('`+str_url+`') format('woff');
										font-style: normal;
										font-weight: normal;
									}
								`);
								drawer_style_body.appendChild(node_style);
								
							};
							reader.readAsDataURL(arr_files[str_identifier]);
						}
						
						count_start--; // Font loaded
						
						func_start();
					}, {}, 'blob', '/css/fonts/', '.woff');
				}
				
				if (svg_style !== false) {
					
					const drawer_style_custom = stage.createElementNS(stage_ns, 'style');
					drawer.appendChild(drawer_style_custom);
					
					const node_style = document.createTextNode(svg_style);
					drawer_style_custom.appendChild(node_style);
				}
			}
			
			if (show_geo_info) {
				PARENT.elm_controls.children('.geo').removeClass('hide').html('');
			} else {
				PARENT.elm_controls.children('.geo').addClass('hide');
			}
			
			if (display == DISPLAY_PIXEL) {
				
				var elm_pointer = new PIXI.Graphics();
				elm_pointer.beginFill(GeoUtilities.parseColor(color_info), 1);
				elm_pointer.moveTo(0,0);
				elm_pointer.lineTo(3,0);
				elm_pointer.lineTo(0,3);
				elm_pointer.lineTo(0,0);
				elm_pointer.endFill();
				
				if (use_best_quality) {
					arr_assets_texture_info.pointer = elm_pointer.generateCanvasTexture();
				} else {
					arr_assets_texture_info.pointer = renderer_activity.generateTexture(elm_pointer);
				}
			}
			
			count_start--; // Main loaded
				
			func_start();
		});
	};
	
	this.close = function() {
		
		if (!has_init) { // Nothing loaded yet
			return;
		}
		
		animating = false;
		ANIMATOR.animate(null, key_animate);
		PARENT.obj_map.move(null, key_move);
		
		if (worker_labels) {
			worker_labels.terminate();
		}
		
		if (display == DISPLAY_PIXEL) { // Destroy WEBGL memory
					
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
			
			if (mode == MODE_MOVE) {
					
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
		
		var func_update = function() {
				
			for (const object_id in arr_data.objects) {
				
				const arr_object = arr_data.objects[object_id];
				
				if (arr_object.name == undefined) {
					continue;
				}
				
				const len_i = arr_object.connect_object_sub_ids.length;

				for (let i = 0; i < len_i; i++) {
						
					const object_sub_id = arr_object.connect_object_sub_ids[i];
					const arr_object_sub = arr_data.object_subs[object_sub_id];
							
					let use_object_sub_details_id = false;
					let arr_potential_object_sub_ids = [];
					let num_date_potential = false;
					
					if (!arr_object_sub.connect_object_sub_ids) {
						arr_object_sub.connect_object_sub_ids = [];
					}
					
					let len_j = arr_object.connect_object_sub_ids.length;
					
					for (let j = 0; j < len_j; j++) {
						
						const connect_object_sub_id = arr_object.connect_object_sub_ids[j];
						
						if (connect_object_sub_id == object_sub_id) {
							use_object_sub_details_id = true;
							continue;
						}
											
						const arr_connect_object_sub = arr_data.object_subs[connect_object_sub_id];
						
						const num_date_start = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
						const num_connect_date_start = DATEPARSER.dateInt2Absolute(arr_connect_object_sub.date_start);
						
						// Check whether this sub-object is a potential candidate
						if (num_connect_date_start == num_date_start) { // If the connection candidate shares the same date, it has to be a different kind of sub-object and the first in line after the current sub-object
							
							if (arr_connect_object_sub.object_sub_details_id != arr_object_sub.object_sub_details_id && (!use_object_sub_details_id || use_object_sub_details_id == arr_connect_object_sub.object_sub_details_id)) {
								
								use_object_sub_details_id = arr_connect_object_sub.object_sub_details_id;
								arr_object_sub.connect_object_sub_ids.push(connect_object_sub_id);
								arr_potential_object_sub_ids = [];
							}
						} else if (num_connect_date_start < num_date_start) { // The connection presents itself earlier in time, or could still be going on.
							
							const num_date_end = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
							const num_connect_date_end = DATEPARSER.dateInt2Absolute(arr_connect_object_sub.date_end);
							
							if (num_connect_date_end >= num_date_end) { // The connection is still going on. Use!
								
								arr_object_sub.connect_object_sub_ids.push(connect_object_sub_id);
								arr_potential_object_sub_ids = [];
								num_date_potential = num_date_start; // Set the potential date to its own starting date to now check for possible overlapping connection
								
							} else if (!num_date_potential || num_connect_date_end >= num_date_potential) { // The connection presents itself earlier in time
								
								if (num_connect_date_end > num_date_potential) { // Beter potential date
									arr_potential_object_sub_ids = [];
								}

								arr_potential_object_sub_ids.push(connect_object_sub_id);
								num_date_potential = num_connect_date_end;
							}
						}
					}
					
					len_j = arr_potential_object_sub_ids.length;
					
					for (let j = 0; j < len_j; j++) {
						arr_object_sub.connect_object_sub_ids.push(arr_potential_object_sub_ids[j]);
					}
				}
			}
		};
		
		PARENT.obj_data.updateData('geo', func_update);
		
		// Prepare subobjects

		const arr_window = {north_east: {latitude: 0, longitude: 0}, south_west: {latitude: 0, longitude: 0}};
		
		for (const object_sub_id in arr_data.object_subs) {
			
			const arr_object_sub = arr_data.object_subs[object_sub_id];
			
			if (arr_object_sub.object_sub_details_id === 'object') {
				continue;
			}

			arr_object_sub.dot_locate_identifier = false;
			arr_object_sub.dot_locate_identifier_pos = false;
			
			// If connection sub object has identifiable definitions (referencing object ids), use as a possible connecting id
			
			const arr_identifier_ids = [];
			
			for (const key in arr_object_sub.object_sub_definitions) {
				
				const arr_object_sub_definition = arr_object_sub.object_sub_definitions[key];
				
				if (arr_object_sub_definition.ref_object_id.length) {
					
					for (let i = 0, len = arr_object_sub_definition.ref_object_id.length; i < len; i++) {

						arr_identifier_ids.push(arr_object_sub_definition.ref_object_id[i]);
					}
				} else if (arr_object_sub_definition.value.length) {
					
					for (let i = 0, len = arr_object_sub_definition.value.length; i < len; i++) {
						
						arr_identifier_ids.push(arr_object_sub_definition.value[i].hashCode());
					}
				}
			}
			
			arr_object_sub.line_connect_identifier = (arr_identifier_ids.length ? arr_identifier_ids.sort().join('_') : false);
			
			if (arr_object_sub.location_geometry !== '' && arr_object_sub.location_geometry != null) {
				
				let arr_geometry = arr_object_sub.location_geometry;
				
				if (arr_geometry[0] != '{') { // Use lookup from data package
					
					const str_identifier = arr_geometry;
					arr_geometry = arr_data.geometry[str_identifier];
					
					if (typeof arr_geometry === 'string') {
						
						arr_geometry = JSON.parse(arr_geometry);
						const arr_geometry_package = GeoUtilities.geometryToPackage(arr_geometry);
						const arr_geometry_path = GeoUtilities.geometryPackageToPath(arr_geometry_package);
						
						arr_geometry = [arr_geometry, arr_geometry_package, arr_geometry_path];
						
						arr_data.geometry[str_identifier] = arr_geometry;
					}
					
					arr_object_sub.arr_geometry_package = arr_geometry[1];
					arr_object_sub.arr_geometry_path = arr_geometry[2];
				} else {
					
					arr_geometry = JSON.parse(arr_geometry);
					arr_object_sub.arr_geometry_package = GeoUtilities.geometryToPackage(arr_geometry);
					arr_object_sub.arr_geometry_path = GeoUtilities.geometryPackageToPath(arr_object_sub.arr_geometry_package);
				}
				
				arr_object_sub.has_geometry_path = (arr_object_sub.arr_geometry_path[2] != null ? true : false);
				
				const latitude = arr_object_sub.arr_geometry_path[1];
				const longitude = arr_object_sub.arr_geometry_path[0];
				
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
			
			PARENT.setTimeline({time: 0}); // Prepare tschuifje
		}

		return {
			default_center: arr_window,
			default_zoom: arr_window
		};
	};
			
	var getObjectSubsLineDetails = function(arr_object_sub_line) {
		
		const arr_view = {};
		const dateinta_range_sub = {min: false, max: false};
		let arr_first_object_sub = null;
		let arr_first_connect_object_sub = null;
		let has_scope = false;
		
		let len = arr_object_sub_line.object_sub_ids.length;
		
		for (let i = 0; i < len; i++) {
			
			const object_sub_id = arr_object_sub_line.object_sub_ids[i];
			
			if (!object_sub_id) {
				continue;
			}
			
			const arr_object_sub = arr_data.object_subs[object_sub_id];
			
			if (display == DISPLAY_PIXEL && info_condition && (!arr_object_sub.style_inherit.conditions || arr_object_sub.style_inherit.conditions[info_condition] === undefined)) {
				continue;
			}
			
			if (arr_first_object_sub === null) {
				
				arr_first_object_sub = arr_object_sub;
				has_scope = (arr_first_object_sub.connected_object_ids[0] != arr_first_object_sub.object_id);
			}
			
			var dateinta_check = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
						
			if (!dateinta_range_sub.min || dateinta_check < dateinta_range_sub.min) {
				dateinta_range_sub.min = dateinta_check;
			}
			if (!dateinta_range_sub.max || dateinta_check > dateinta_range_sub.max) {
				dateinta_range_sub.max = dateinta_check;
			}
			
			if (arr_object_sub.has_geometry_path) { // Line has geometry path; use the sub-object's ending date
				
				dateinta_check = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
				
				if (dateinta_check < dateinta_range_sub.min) {
					dateinta_range_sub.min = dateinta_check;
				}
				if (dateinta_check > dateinta_range_sub.max) {
					dateinta_range_sub.max = dateinta_check;
				}
			}
		}
		
		len = arr_object_sub_line.connect_object_sub_ids.length;
		
		for (let i = 0; i < len; i++) {
			
			const connect_object_sub_id = arr_object_sub_line.connect_object_sub_ids[i];
			
			if (!connect_object_sub_id) {
				continue;
			}
			
			const arr_connect_object_sub = arr_data.object_subs[connect_object_sub_id];
			
			if (display == DISPLAY_PIXEL && info_condition && (!arr_connect_object_sub.style_inherit.conditions || arr_connect_object_sub.style_inherit.conditions[info_condition] === undefined)) {
				continue;
			}
			
			if (arr_first_connect_object_sub === null) {
				
				arr_first_connect_object_sub = arr_connect_object_sub;
				has_scope = (has_scope || arr_connect_object_sub.connected_object_ids[0] != arr_connect_object_sub.object_id);
			}
			
			if (arr_object_sub_line.has_connection_line) { // Line has a connection line; use the connecting sub-object's ending date
				
				dateinta_check = DATEPARSER.dateInt2Absolute(arr_connect_object_sub.date_end);
				
				if (!dateinta_range_sub.min || dateinta_check < dateinta_range_sub.min) {
					dateinta_range_sub.min = dateinta_check;
				}
			}
			
			if (has_scope) {
			
				for (let j = 0, len_j = arr_connect_object_sub.connected_object_ids.length; j < len_j; j++) {
					
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

		const arr_object_sub_line_details = {
			arr_view: arr_view,
			dateinta_range: dateinta_range_sub,
			arr_first_object_sub: arr_first_object_sub,
			arr_first_connect_object_sub: arr_first_connect_object_sub
		};
		
		return arr_object_sub_line_details;
	}
	
	var getObjectSubsDotDetails = function(arr_object_sub_dot) {
		
		const arr_view = {types: {}, scope: false};
		const arr_object_sub_ids = [];
		const arr_object_ids = [];
		
		let arr_first_object_sub = null;
		
		for (var i = 0, len = arr_object_sub_dot.object_sub_ids.length; i < len; i++) {
							
			const object_sub_id = arr_object_sub_dot.object_sub_ids[i];
			
			if (!object_sub_id) {
				continue;
			}
						
			const arr_object_sub = arr_data.object_subs[object_sub_id];
			
			if (arr_first_object_sub === null) {
				arr_first_object_sub = arr_object_sub;
			}

			if (display == DISPLAY_PIXEL && info_condition && (!arr_object_sub.style_inherit.conditions || arr_object_sub.style_inherit.conditions[info_condition] === undefined)) {
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
		
		const arr_object_sub_dot_details = {
			arr_view: arr_view,
			object_sub_ids: arr_object_sub_ids,
			object_ids: arr_object_ids,
			location_name: (arr_first_object_sub.location_name ? arr_first_object_sub.location_name : arr_data.objects[arr_first_object_sub.object_id].name),
			location_geometry: arr_first_object_sub.location_geometry
		};

		return arr_object_sub_dot_details;
	};
	
	// Interact
		
	var addListeners = function() {
				
		elm_hover = false;
		type_elm_hover = false;
		
		let is_dragging_node = false;
		let func_interact_stop = false;
		
		interactListener = function(e) {
			
			if (is_dragging_node) {
				return;
			}

			let has_changed = false;
			
			if (elm_hover_target === false) {
				
				if (elm_hover !== false) {
					
					if (func_interact_stop) {
						func_interact_stop();
						func_interact_stop = false;
					}
				
					elm_host.classList.remove('hovering');
					
					if (display == DISPLAY_VECTOR || !show_info_dot) {
						
						elm_host.removeAttribute('title');
						TOOLTIP.update();
					}
											
					if (type_elm_hover === 'dot') {
						
						hoverDot(elm_hover, false);
						renderDraw();
					}
				}
			} else if (elm_hover !== false) {
				
				if (elm_hover !== elm_hover_target || type_elm_hover !== type_elm_hover_target) {
				
					if (type_elm_hover === 'dot') {
						
						hoverDot(elm_hover, false);
						if (type_elm_hover_target !== 'dot') {
							renderDraw();
						}
					}
					
					has_changed = true;
				}
			} else {
				
				has_changed = true;
			}

			elm_hover = elm_hover_target;
			type_elm_hover = type_elm_hover_target;
			
			if (!has_changed) {
				return;
			}
			
			if (func_interact_stop) {
				func_interact_stop();
				func_interact_stop = false;
			}
			
			if (type_elm_hover === 'dot') {
				
				if (POSITION.isTouch()) {
					return;
				}

				hoverDot(elm_hover, true);
				renderDraw();
				
				elm_host.classList.add('hovering');
				
				if (display == DISPLAY_VECTOR || !show_info_dot) {
					interactDotTooltip(elm_hover);
				}
			} else if (type_elm_hover === 'line') {
				
				if (POSITION.isTouch()) {
					return;
				}
				
				const arr_object_sub_lines_locate = arr_object_sub_lines[elm_hover.dataset.locate_identifier];
				const arr_object_sub_line = arr_object_sub_lines_locate[elm_hover.dataset.connect_identifier];
				
				elm_host.classList.add('hovering');			
				const elms_tooltip_extra = interactLineTooltip(arr_object_sub_lines_locate, arr_object_sub_line);
				
				func_interact_stop = function() {
		
					elms_tooltip_extra[0].remove();
					elms_tooltip_extra[1].remove();
				};
			} else if (type_elm_hover === 'location') {
				
				elm_host.classList.remove('hovering');
				
				if (display == DISPLAY_VECTOR || !show_info_dot) {
					
					elm_host.removeAttribute('title');
					TOOLTIP.update();
				}

				if (location_position_manual) {

					var func_interact_start = function(e) {
						
						if (e.type == 'mousedown' && POSITION.isTouch()) {
							return;
						}
						
						is_dragging_node = true;
						
						elm[0].addEventListener('touchmove', func_interact_move);
						elm[0].addEventListener('mousemove', func_interact_move);
					};
					var func_interact_move = function(e) {
						
						if (e.type == 'mousemove' && POSITION.isTouch()) {
							return;
						}
						
						e.preventDefault();
						e.stopPropagation();
						
						const pos_target = PARENT.obj_map.getMousePosition();
						
						elm_hover.arr_location.manual = true;
						
						moveDotLocation(elm_hover, pos_target.x, pos_target.y, true);
						
						if (display == DISPLAY_PIXEL) {
							renderDraw();
						}
					};
					func_interact_stop = function(e) {
						
						if (e && e.type == 'mouseup' && POSITION.isTouch()) {
							return;
						}

						elm[0].removeEventListener('mousedown', func_interact_start);
						if (is_dragging_node) {
							elm[0].removeEventListener('touchmove', func_interact_move);
							elm[0].removeEventListener('mousemove', func_interact_move);
						}
						elm[0].removeEventListener('touchend', func_interact_stop);
						elm[0].removeEventListener('mouseup', func_interact_stop);
						
						is_dragging_node = false;
						func_interact_stop = false;
					};
					
					if (e && e.type == 'touchstart') {
						func_interact_start(e);
					}
					elm[0].addEventListener('mousedown', func_interact_start);

					elm[0].addEventListener('touchend', func_interact_stop);
					elm[0].addEventListener('mouseup', func_interact_stop);
				}
			}
		};
		
		const func_check_open = function(e) {
			
			if (!elm_hover || type_elm_hover === 'location') {
				
				elm_host.arr_link = false;
				elm_host.arr_info_box = false;
				
				return;
			}
			
			if (type_elm_hover === 'dot') {
				
				interactDotOpen(elm_hover);
			} else if (type_elm_hover === 'line') {
				
				const arr_object_sub_lines_locate = arr_object_sub_lines[elm_hover.dataset.locate_identifier];
				const arr_object_sub_line = arr_object_sub_lines_locate[elm_hover.dataset.connect_identifier];
				
				interactLineOpen(arr_object_sub_lines_locate, arr_object_sub_line);
			}
		};
		
		if (display == DISPLAY_VECTOR) {
			
			elm[0].addEventListener('touchstart', function(e) {
				
				elm_hover_check = e.target;
				interact(e);
				
				func_check_open(e);
			});

			elm[0].addEventListener('mouseover', function(e) {
								
				elm_hover_check = e.target;
			});
		} else {

			elm[0].addEventListener('touchstart', function(e) {
				
				interact(e);
				
				func_check_open(e);
			});
		}
		
		elm[0].addEventListener('mousedown', function(e) {
			
			if (POSITION.isTouch()) {
				return;
			}
			
			func_check_open(e);
		});
	};
		
	var interactDotOpen = function(arr_object_sub_dot) {
		
		const arr_object_subs_dot_details = getObjectSubsDotDetails(arr_object_sub_dot);
		
		const arr_link = {object_sub_ids: arr_object_subs_dot_details.object_sub_ids, object_ids: arr_object_subs_dot_details.object_ids};

		elm_host.arr_link = arr_link;
				
		elm_host.arr_info_box = {name: arr_object_subs_dot_details.location_name};
	}
	
	var interactDotTooltip = function(arr_object_sub_dot) {
		
		const arr_object_subs_dot_details = getObjectSubsDotDetails(arr_object_sub_dot);
				
		let str_title = '<ul>\
			<li>\
				<label>'+arr_labels.lbl_location+'</label>\
				<span>'+arr_object_subs_dot_details.location_name+'</span>\
			</li>';
		
		const arr_view_types = arr_object_subs_dot_details.arr_view.types;

		for (const type_id in arr_view_types) {
			
			const arr_view_type = arr_view_types[type_id];
			
			str_title = str_title+'<hr />\
			<li>\
				<label>'+arr_data.info.types[type_id].name+'</label>\
				<ul>';
				
				for (const object_sub_details_id in arr_view_type) {
					
					const arr_view_type_object_sub = arr_view_type[object_sub_details_id];
					
					str_title = str_title+'<li>\
						<label class="sub-name">'+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'</label>\
						<ul>';
						
						let has_identifier_definition = false;
						const arr_view_type_object_sub_definitions = arr_view_type_object_sub.object_sub_definitions;

						for (const object_sub_description_id in arr_view_type_object_sub_definitions) {
							
							has_identifier_definition = true;
							const object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_description_id].object_sub_description_name; // Could be collapsed
							
							str_title = str_title+'<li>\
								<label>'+(object_sub_description_name ? object_sub_description_name : 'Object')+'</label>\
								<ul>';
								
								const arr_ref_objects = arr_view_type_object_sub_definitions[object_sub_description_id];
								
								const arr_sort = [];
								for (const ref_object_id in arr_ref_objects) {
									arr_sort.push([ref_object_id, arr_ref_objects[ref_object_id].count]);
								}
								arr_sort.sort(function(a, b) {
									return b[1] - a[1];
								});
								
								let num_count = 0;
								for (let i = 0, len = arr_sort.length; i < len; i++) {
									
									if (num_count > 10) {
										break;
									}
									
									const arr_ref_object = arr_ref_objects[arr_sort[i][0]];
									
									str_title = str_title+'<li>'+arr_ref_object.value+' '+(arr_ref_object.count > 1 ? '('+arr_ref_object.count+'x)' : '')+'</li>';
									num_count++;
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
																
								const arr_objects = arr_view_type_object_sub.objects;
								
								const arr_sort = [];
								for (const object_id in arr_objects) {
									arr_sort.push([object_id, arr_objects[object_id].count]);
								}
								arr_sort.sort(function(a, b) {
									return b[1] - a[1];
								});
								
								let num_count = 0;
								for (let i = 0, len = arr_sort.length; i < len; i++) {
									
									if (num_count > 10) {
										break;
									}
									
									const arr_object = arr_objects[arr_sort[i][0]];
									
									str_title = str_title+'<li>'+arr_object.value+' '+ (arr_object.count > 1 ? '('+arr_object.count+'x)' : '')+'</li>';
									num_count++;
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
		
		const arr_view_scope = arr_object_subs_dot_details.arr_view.scope;
		
		if (arr_view_scope) {
			
			str_title = (info_content == 'default' ? str_title+'<hr />\
			<li>\
				<label>'+arr_labels.lbl_scope+'</label>\
				<ul>' : str_title);
			
				for (const type_id in arr_view_scope) {
					
					const arr_view_scope_type = arr_view_scope[type_id];
					
					str_title = str_title+'<li>\
						<label>'+arr_data.info.types[type_id].name+'</label>\
						<ul>';
						
							const arr_objects = arr_view_scope_type;
							
							const arr_sort = [];
							for (const object_id in arr_objects) {
								arr_sort.push([object_id, arr_objects[object_id].count]);
							}
							arr_sort.sort(function(a, b) {
								return b[1] - a[1];
							});
							
							let num_count = 0;
							for (let i = 0, len = arr_sort.length; i < len; i++) {
								
								if (num_count > 10) {
									break;
								}
								
								const arr_object = arr_objects[arr_sort[i][0]];
								
								str_title = str_title+'<li>'+arr_object.value+' '+ (arr_object.count > 1 ? '('+arr_object.count+'x)' : '')+'</li>';
								num_count++;
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

		elm_host.setAttribute('title', str_title);
		TOOLTIP.update();
	};
	
	var interactLineOpen = function(arr_object_sub_lines_locate, arr_object_sub_line) {
		
		const arr_object_subs_line_details = getObjectSubsLineDetails(arr_object_sub_line);
		const arr_first_object_sub = arr_object_subs_line_details.arr_first_object_sub;
		const arr_first_connect_object_sub = arr_object_subs_line_details.arr_first_connect_object_sub;
		
		const arr_link = {is_line: true, object_sub_ids: [], connect_object_sub_ids: []};

		for (let i = 0, len = arr_object_sub_line.object_sub_ids.length; i < len; i++) {
			
			const object_sub_id = arr_object_sub_line.object_sub_ids[i];
			
			if (!object_sub_id) {
				continue;
			}
			
			arr_link.object_sub_ids.push(object_sub_id);
		}

		for (let i = 0, len = arr_object_sub_line.connect_object_sub_ids.length; i < len; i++) {
			
			const connect_object_sub_id = arr_object_sub_line.connect_object_sub_ids[i];
			
			if (!connect_object_sub_id) {
				continue;
			}
			
			arr_link.connect_object_sub_ids.push(connect_object_sub_id);
		}
		
		elm_host.arr_link = arr_link;
		elm_host.arr_info_box = {name: (arr_first_connect_object_sub.location_name ? arr_first_connect_object_sub.location_name : arr_data.objects[arr_first_connect_object_sub.object_id].name)+' - '+(arr_first_object_sub.location_name ? arr_first_object_sub.location_name : arr_data.objects[arr_first_object_sub.object_id].name)};
	};
	
	var interactLineTooltip = function(arr_object_sub_lines_locate, arr_object_sub_line) {

		const arr_object_subs_line_details = getObjectSubsLineDetails(arr_object_sub_line);
		const dateinta_range_sub = arr_object_subs_line_details.dateinta_range;
		const arr_first_object_sub = arr_object_subs_line_details.arr_first_object_sub;
		const arr_first_connect_object_sub = arr_object_subs_line_details.arr_first_connect_object_sub;

		let str_info_object_sub_connect = '<label class="sub-name">'+arr_data.info.object_sub_details[arr_first_connect_object_sub.object_sub_details_id].object_sub_details_name+'</label>\
		<ul>';
			for (const key in arr_first_connect_object_sub.object_sub_definitions) {
				
				const object_sub_definition = arr_first_connect_object_sub.object_sub_definitions[key];
				
				if (object_sub_definition.value) {
					
					const object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
					
					str_info_object_sub_connect = str_info_object_sub_connect+'<li>\
						<label>'+(object_sub_description_name ? object_sub_description_name : 'Object')+'</label>\
						<span>'+object_sub_definition.value+'</span>\
					</li>';
				}
			}
			str_info_object_sub_connect = str_info_object_sub_connect+'<li>\
				<label>'+arr_labels.lbl_location+'</label>\
				<span>'+(arr_first_connect_object_sub.location_name ? arr_first_connect_object_sub.location_name : 'φ λ°')+'</span>\
			</li>\
		</ul>';
		const str_info_object_sub_connect_object_name = (arr_object_sub_line.count_connect > 1 ? arr_object_sub_line.count_connect+'x' : arr_data.objects[arr_first_connect_object_sub.object_id].name);
			
		let str_info_object_sub_first = '<label class="sub-name">'+arr_data.info.object_sub_details[arr_first_object_sub.object_sub_details_id].object_sub_details_name+'</label>\
		<ul>';
			for (const key in arr_first_object_sub.object_sub_definitions) {
				
				const object_sub_definition = arr_first_object_sub.object_sub_definitions[key];
				
				if (object_sub_definition.value) {
					
					const object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
					
					str_info_object_sub_first = str_info_object_sub_first+'<li>\
						<label>'+(object_sub_description_name ? object_sub_description_name : 'Object')+'</label>\
						<span>'+object_sub_definition.value+'</span>\
					</li>';
				}
			}
			str_info_object_sub_first = str_info_object_sub_first+'<li>\
				<label>'+arr_labels.lbl_location+'</label>\
				<span>'+(arr_first_object_sub.location_name ? arr_first_object_sub.location_name : 'φ λ°')+'</span>\
			</li>\
		</ul>';
		const str_info_object_sub_first_object_name = (arr_object_sub_line.count > 1 ? arr_object_sub_line.count+'x' : arr_data.objects[arr_first_object_sub.object_id].name);
		
		let str_info_object_subs = '';
		
		if (arr_data.objects[arr_first_connect_object_sub.object_id].type_id == arr_data.objects[arr_first_object_sub.object_id].type_id) {
			
			str_info_object_subs = '<li>\
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
			
			str_info_object_subs = '<li>\
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
		
		let arr_view_scope = arr_object_subs_line_details.arr_view.scope;
		let str_info_scope = '';
		
		if (arr_view_scope) {
			
			str_info_scope = '<hr />\
			<li>\
				<label>'+arr_labels.lbl_scope+'</label>\
				<ul>';
			
				for (const type_id in arr_view_scope) {
					
					const arr_view_scope_type = arr_view_scope[type_id];
					
					str_info_scope = str_info_scope+'<li>\
						<label>'+arr_data.info.types[type_id].name+'</label>\
						<ul>';
						
							const arr_objects = arr_view_scope_type;
							
							const arr_sort = [];
							for (const object_id in arr_objects) {
								arr_sort.push([object_id, arr_objects[object_id].count]);
							}
							arr_sort.sort(function(a, b) {
								return b[1] - a[1];
							});
							
							let num_count = 0;
							for (let i = 0, len = arr_sort.length; i < len; i++) {
								
								if (num_count > 10) {
									break;
								}
								const arr_object = arr_objects[arr_sort[i][0]];
								
								str_info_scope = str_info_scope+'<li>'+arr_object.value+' '+ (arr_object.count > 1 ? '('+arr_object.count+'x)' : '')+'</li>';
								num_count++;
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
		
		var date_range_sub_min = DATEPARSER.int2Date(DATEPARSER.dateIntAbsolute2DateInt(dateinta_range_sub.min));
		var date_range_sub_max = DATEPARSER.int2Date(DATEPARSER.dateIntAbsolute2DateInt(dateinta_range_sub.max));
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
				<label>'+arr_labels.lbl_date_start+'</label>\
				<span>'+str_date_start+'</span>\
			</li>\
			<li>\
				<label>'+arr_labels.lbl_date_end+'</label>\
				<span>'+str_date_end+'</span>\
			</li>\
		</ul>';
		
		elm_host.setAttribute('title', str_title);
		TOOLTIP.update();
		
		var pos = elm_host.getBoundingClientRect();
		
		var locate_identifier = arr_object_sub_lines_locate.location_geometry;
		var arr_object_sub_dot = arr_object_sub_dots[locate_identifier];
		var pos_loc = arr_object_sub_dot.xy_geometry_center;
		var radius = arr_object_sub_dot.arr_settings.size / 2;
		
		var connect_locate_identifier = arr_object_sub_lines_locate.connect_location_geometry;
		var arr_object_sub_dot = arr_object_sub_dots[connect_locate_identifier];
		var pos_loc_connect = arr_object_sub_dot.xy_geometry_center;
		var radius_connect = arr_object_sub_dot.arr_settings.size / 2;

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
		
		return [tooltip[0], tooltip2[0]];
	}
	
	// Draw
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
		
		if (calc_zoom || calc_zoom === false) { // Zoomed or resize
			
			redraw = REDRAW_POSITION;
			if (calc_zoom) {
				
				cur_zoom = zoom;
				
				const arr_level = PARENT.obj_map.levelVars(1);
				const arr_level_cur = PARENT.obj_map.levelVars(cur_zoom);
				
				calc_plot_point = arr_level.width / arr_level_cur.width;
				
				size_map = arr_level_cur;
				
				redraw = REDRAW_SCALE;
			}
		}
		if (move === true || move === false || calc_zoom === false || calc_zoom) { // Move start/stop, resize, or zoomed
			
			let num_width = 0;
			let num_height = 0;
			
			// Reposition drawer
			if (display == DISPLAY_PIXEL) {
				
				if (move === true || (is_dragging && move !== false)) {
					
					is_dragging = true;
					animate(false);
				} else {
					
					is_dragging = false;
					animate(true);
				}
				
				num_width = pos.view.width;
				num_height = pos.view.height;
				
				if (size_renderer.width != num_width || size_renderer.height != num_height) {
					
					redraw = REDRAW_POSITION;
					
					size_renderer.width = num_width;
					size_renderer.height = num_height;
					size_renderer.resolution = pos.render.resolution;
					
					renderer_geometry.resize(num_width, num_height);
					renderer_geometry.resolution = size_renderer.resolution;
					if (mode == MODE_MOVE) {
						renderer_connection_lines.resize(num_width, num_height);
						renderer_connection_lines.resolution = size_renderer.resolution;
						renderer_locations.resize(num_width, num_height);
						renderer_locations.resolution = size_renderer.resolution;
					} else {
						renderer_locations.resize(num_width, num_height);
						renderer_locations.resolution = size_renderer.resolution;
						renderer_lines.resize(num_width, num_height);
						renderer_lines.resolution = size_renderer.resolution;
					}
					renderer_activity.resize(num_width, num_height);
					renderer_activity.resolution = size_renderer.resolution;
					renderer_dots.resize(num_width, num_height);
					renderer_dots.resolution = size_renderer.resolution;
				}
			} else {
				
				if (move === true) {
					is_dragging = true;
					return;
				}
				if (move === false) {
					is_dragging = false;
				}
				
				num_width = pos.size.width;
				num_height = pos.size.height;
				
				if (size_renderer.width != num_width || size_renderer.height != num_height) {
					
					redraw = REDRAW_POSITION;
					
					size_renderer.width = num_width;
					size_renderer.height = num_height;
					
					drawer.style.width = num_width+'px';
					drawer.style.height = num_height+'px';
				}
			}
	
			const x = -pos.x - pos.offset.x - (num_width/2);
			const y = -pos.y - pos.offset.y - (num_height/2);

			if (redraw || (x - pos_offset_extra_x) + (pos.view.width/2) > (num_width/2) || (x - pos_offset_extra_x) - (pos.view.width/2) < -(num_width/2) || (y - pos_offset_extra_y) + (pos.view.height/2) > (num_height/2) || (y - pos_offset_extra_y) - (pos.view.height/2) < -(num_height/2)) {
		
				pos_offset_extra_x = x;
				pos_offset_extra_y = y;

				const str = 'translate('+x+'px, '+y+'px)';
				
				drawer.style.transform = drawer.style.webkitTransform = str;
				if (display == DISPLAY_PIXEL) {
					drawer_2.style.transform = drawer_2.style.webkitTransform = str;
					drawer_3.style.transform = drawer_3.style.webkitTransform = str;
					drawer_4.style.transform = drawer_4.style.webkitTransform = str;
					drawer_5.style.transform = drawer_5.style.webkitTransform = str;
				}
				
				if (!redraw) {
					redraw = REDRAW_POSITION;
				}
			}

			pos_offset_x = pos.offset.x + pos_offset_extra_x;
			pos_offset_y = pos.offset.y + pos_offset_extra_y;
		}
		
		if (redraw) {
			PARENT.doDraw();
		}
	};
	
	var checkInRange = function(arr_object_sub, in_date_range) {
						
		if (in_date_range !== undefined) {
			
			if (!in_date_range) {
				return false;
			}
		} else {
			
			const dateinta_start = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
			const dateinta_end = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
			
			const in_range = ((dateinta_start >= dateinta_range.min && dateinta_start <= dateinta_range.max) || (dateinta_end >= dateinta_range.min && dateinta_end <= dateinta_range.max) || (dateinta_start < dateinta_range.min && dateinta_end > dateinta_range.max));
		
			if (!in_range) {
				return false;
			}
		}
		
		if (!in_predraw) { // Check for inactive data on 'live' data
				
			if (PARENT.obj_data.arr_inactive_object_sub_details[arr_object_sub.object_sub_details_id]) {
				return false;
			}
			
			const len = PARENT.obj_data.arr_loop_inactive_conditions.length;
			
			if (len) {
				
				const arr_conditions = arr_object_sub.style_inherit.conditions;
				
				if (arr_conditions) {

					for (let i = 0; i < len; i++) {
						
						if (arr_conditions[PARENT.obj_data.arr_loop_inactive_conditions[i]] !== undefined) {
							return false;
						}
					}
				}
			}
		}

		return true;
	};
	
	var drawData = function(dateint_range_new, dateint_range_bounds, settings_timeline_new) {
						
		dateinta_range = {min: DATEPARSER.dateInt2Absolute(dateint_range_new.min), max: DATEPARSER.dateInt2Absolute(dateint_range_new.max)};
		settings_timeline = settings_timeline_new;
		
		in_predraw = false;
		if (count_loop == 0) {

			dateinta_range = {min: DATEPARSER.dateInt2Absolute(dateint_range_bounds.min), max: DATEPARSER.dateInt2Absolute(dateint_range_bounds.max)};
			
			arr_data = PARENT.getData();

			// Prepare asset tracking
			arr_object_sub_dots = {};
			arr_loop_object_sub_dots = [];
			arr_object_sub_lines = {};
			arr_loop_object_sub_lines = {arr_loop: []};
			arr_elm_plot_line_particles = {};
			arr_elm_plot_line_particles_temp = {};
			count_info_show_object_sub_lines = 0;
			count_info_show_object_sub_dots = 0;

			in_predraw = true;
		}

		if (redraw) { // New draw

			// Prepare ploting container
			if (display == DISPLAY_PIXEL) {
				
				if (redraw === REDRAW_SCALE) {

					elm_plot_geometry.removeChildren();
					elm_plot_connection_lines.removeChildren();
					if (mode != MODE_MOVE) {
						elm_plot_lines.removeChildren();
					}
					elm_plot_between.removeChildren();
					elm_plot_dots.removeChildren();
					
					// Clear cached geometries, which are irrelevant on new zoom level
					renderer_geometry.geometry.disposeAll();
				} else if (redraw === REDRAW_RESET) {
					if (mode == MODE_MOVE && move_retain === 'all') {
						for (const key in arr_elm_plot_line_particles_temp) {
							const elm_container = arr_elm_plot_line_particles_temp[key];
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
				do_render_lines = true;
			} else {
				
				if (redraw === REDRAW_SCALE) {
					
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
			pos_hover_poll = false;
		}
		
		do_draw = false;
		
		const arr_data_object_subs = arr_data.object_subs;
		
		// Single date sub-objects
		for (let i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
			
			const date = arr_data.date.arr_loop[i];
			const dateinta = DATEPARSER.dateInt2Absolute(date);
			const in_range = (dateinta >= dateinta_range.min && dateinta <= dateinta_range.max);
			const arr_object_subs = arr_data.date[date];
			
			for (let j = 0, len_j = arr_object_subs.length; j < len_j; j++) {
			
				const object_sub_id = arr_object_subs[j];
				const arr_object_sub = arr_data_object_subs[object_sub_id];
				
				if (arr_object_sub.object_sub_details_id === 'object' || arr_object_sub.object_sub_details_id === 'collapse') {
					continue;
				}
			
				drawObjectSub(object_sub_id, !checkInRange(arr_object_sub, in_range));
			}
		}
		
		// Sub-objects with a date range
		for (let i = 0, len = arr_data.range.length; i < len; i++) {
			
			const object_sub_id = arr_data.range[i];
			const arr_object_sub = arr_data_object_subs[object_sub_id];
			
			if (arr_object_sub.object_sub_details_id === 'object' || arr_object_sub.object_sub_details_id === 'collapse') {
				continue;
			}
			
			drawObjectSub(object_sub_id, !checkInRange(arr_object_sub));
		}
		
		drawLines();
		drawDots();

		drawInfo();
		drawHints();
				
		if (in_predraw) {
			
			if (display == DISPLAY_PIXEL) {
				
			} else {
				elm_plot_locations.appendChild(fragment_plot_locations);
			}
		}
		
		if (redraw === REDRAW_SCALE) {
			positionLabels();
		}
		
		if (do_draw) {
			
			if (!in_predraw) {
					
				if (!animating && !is_dragging) {
					
					animate(true);
				}
				
				if (show_geo_info) {
					
					if (show_geo_info) {
						
						PARENT.elm_controls.children('.geo').html('');
						displayGeoInfo();
					}
				}
			}
			
			renderDraw();
		}

		redraw = REDRAW_NONE;
		count_loop++;
		
		if (in_predraw) {
			
			redraw = REDRAW_DEFAULT;
			SELF.drawData(dateint_range_new, dateint_range_bounds, settings_timeline_new);
		}
	};
	
	var renderDraw = function() {
		
		if (display == DISPLAY_PIXEL) {
			
			if (!in_predraw) {
				
				if (mode == MODE_MOVE) {
					
				} else {
					
					if (do_render_geometry) {
						renderer_geometry.render(stage_geometry);
						do_render_geometry = false;
					}
					
					if (do_render_lines) {
						renderer_lines.render(stage_lines);
						do_render_lines = false;
					}
					
					if (do_render_dots) {
						renderer_dots.render(stage_dots);
						do_render_dots = false;
					}
				}
				
				if (do_render_locations && !count_animate_locations) {
					renderer_locations.render(stage_locations);
					do_render_locations = false;
				}
			}
		} else {
			
			elm_plot_geometry.appendChild(fragment_plot_geometry);
			elm_plot_lines.appendChild(fragment_plot_lines);
			elm_plot_between.appendChild(fragment_plot_between);
			elm_plot_dots.appendChild(fragment_plot_dots);
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

			if (display == DISPLAY_PIXEL) {
				
				if (!time_animate) {
					time_animate = time;
				}
				
				interact();
				animateMapPixel(time);
				
				time_animate = time;
				
				if (mode == MODE_MOVE) {

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
				
				interact();
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

		const time_diff = time - time_animate;
		time_poll = ((time_poll === true ? time_diff : (time_poll + time_diff < 100 ? time_poll + time_diff : true))); // 0.1 seconds
		
		if (mode == MODE_MOVE) {
			
			if (move_chronological) {
			
				const obj_settings = PARENT.setTimeline({time: time_diff});
				var date_move = obj_settings.time;
			}
			
			for (let i = 0, len = arr_animate_move.length; i < len; i++) {

				const arr_move = arr_animate_move[i];
				let do_remove = false;
				let is_finished = false;
				
				if (arr_move.key === false) {
					do_remove = true;
				} else {
					arr_move.key = i; // Update array position of this moving instance due to array manipulation
				}
				
				const arr_object_sub_lines_locate = arr_move.arr_object_sub_lines_locate;
				const arr_object_sub_line = arr_move.arr_object_sub_line;
				const is_self = (move_retain !== 'all' || arr_object_sub_line.arr_move === arr_move);
				const is_active = arr_object_sub_line.is_active;
								
				if (!do_remove) {
				
					const duration = (do_show_info_line && is_self && is_active && arr_object_sub_line.arr_info.is_added && arr_object_sub_line.arr_info.duration ? arr_object_sub_line.arr_info.duration : arr_move.duration);
					let num_percentage = 0;
					
					if (move_chronological) {
						
						const date_start = arr_move.start + ((arr_move.delay / 1000) * (speed_move * DATEPARSER.time_day));
						const date_end = date_start + ((duration / 1000) * (speed_move * DATEPARSER.time_day));
						
						num_percentage = (date_move - date_start) / (date_end - date_start);
						
						if (num_percentage < 0 || num_percentage > 1) {
							
							if (arr_move.status === false) {
								continue;
							}
						} else {
							
							if (arr_move.status === false) {
								
								if (move_hint_dot) {
									setObjectSubDotHint(false, arr_object_sub_lines_locate.connect_location_geometry);
								}
							}
							
							arr_move.status = 1;
						}
					} else {
						
						if (arr_move.cur_delay > 0) {
							
							arr_move.cur_delay = arr_move.cur_delay - time_diff;
							
							if (arr_move.cur_delay > 0) { // Still waiting for the kick-off
								continue;
							}
						}
						
						if (duration == 0) {
							num_percentage = -1;
						} else {
							num_percentage = arr_move.percentage + (time_diff / duration);
						}
					}

					if (num_percentage > 1) { // Reset or stop
						
						let do_moved = false;
						
						if (move_continuous) {
							
							if (!is_self || !is_active) {
								
								is_finished = true;
								arr_move.percentage = num_percentage;
							} else if (is_active) {
								
								num_percentage = (num_percentage % 1); // Use only the remaining decimals, percentage could be larger than 1.99
								arr_move.percentage = num_percentage; // Preserve overflowing percentage to smoothen flow
								if (arr_move.status < 5) {
									//arr_move.duration = arr_move.duration + (arr_move.delay * 0.1); // Speedup just a little bit, improves dispersion over time
								}
								
								arr_move.status++;
								
								do_moved = true;
							}
						} else {
							
							is_finished = true;
							arr_move.percentage = num_percentage;
							
							if (is_active && is_self && arr_move.status) {

								if (!move_chronological) {
									
									arr_object_sub_line.is_active = false;
									
									if (arr_object_sub_line.info_show) {
										arr_object_sub_line.info_show = false;
										arr_object_sub_lines_locate.count_info_show--;
										count_info_show_object_sub_lines--;
									}
								}
								
								arr_move.status = 0;
								
								do_moved = true;
							}
						}
						
						if (do_moved && move_apply_opacity_connection_line == 'moved' && opacity_connection_line_range && (!move_continuous || arr_move.status == 2)) {
							
							const elm_connection_line = arr_object_sub_lines_locate.arr_connection_line.elm;
							const num_opacity = (opacity_connection_line_range / count_line_weight_max) * arr_object_sub_line.weight;
							
							if (elm_connection_line.alpha < opacity_connection_line_range_min) {
								
								elm_connection_line.alpha += (opacity_connection_line_range_min - opacity_connection_line);
							}
						
							elm_connection_line.alpha += num_opacity;
							arr_object_sub_lines_locate.opacity_connection_line += num_opacity;
							
							do_render_connection_lines = true;
						}
						
						if (do_moved && (moved_hint_dot || moved_hint_line)) {
							
							if (moved_hint_dot) { 
								
								setObjectSubDotHint(false, arr_object_sub_lines_locate.location_geometry);
							}
							if (moved_hint_line) {
								
								const arr_connection_line = arr_object_sub_lines_locate.arr_connection_line;
								const arr_animate = arr_connection_line.arr_animate;

								if (arr_animate.length) {
									
									const elm_connection_line = arr_connection_line.elm;
	
									elm_connection_line.alpha -= arr_animate[1];
								} else {

									count_animate_connection_lines++;
								}
								
								arr_animate[0] = false;
								arr_animate[1] = 0;
							}
						}
					} else if (num_percentage < 0) { // Only applicable for move_chronological
						is_finished = true;
					} else {
						arr_move.percentage = num_percentage;
					}
			
					const elm = arr_move.elm;
					
					if (!is_finished) {
						
						//elm.visible = true;
						elm.alpha = 1;
						
						const p = computeObjectSubLinePosition(arr_object_sub_lines_locate, num_percentage);
						
						elm.position.x = (p.x - pos_offset_x);
						elm.position.y = (p.y - pos_offset_y);
					} else {
						
						//elm.visible = false;
						elm.alpha = 0;
					}
					
					if (arr_object_sub_lines_locate.arr_move_path.length > 1) { // Only show warp when the path actually has length
							
						const num_percentage_one = (1/arr_object_sub_lines_locate.arr_move_path.length);
						
						for (let j = 0; j < length_move_warp; j++) {
							
							const elm_warp = arr_move.arr_elm_warp[j];
							
							if (num_percentage_one >= 1) {
								elm_warp.alpha = 0;
								continue;
							}
							
							const num_percentage_j = j * num_percentage_one;
							let num_percentage_warp = 0;
							
							if (num_percentage_j >= num_percentage) {
								
								if (arr_move.status > 1) {
									num_percentage_warp = 1 - (num_percentage_j - num_percentage); // Put elm_warp at the end of the path
								} else {
									//elm_warp.visible = false; // Do not show elm_warp, it's not yet in play
									elm_warp.alpha = 0;
									continue;
								}
							} else {
								
								num_percentage_warp = (num_percentage - (num_percentage_j + num_percentage_one)); // Put elm_warp where normally should be
							}
							
							if (num_percentage_warp < 0 || num_percentage_warp > 1) { // elm_warp is outside bounds
								//elm_warp.visible = false;
								elm_warp.alpha = 0;
								continue;
							} else {
								//elm_warp.visible = true;
								elm_warp.alpha = 0.6 - ((j + 1) * (0.6 / (length_move_warp+1)));
								is_finished = false;
							}
							
							const p2 = computeObjectSubLinePosition(arr_object_sub_lines_locate, num_percentage_warp);
						
							elm_warp.position.x = (p2.x - pos_offset_x);
							elm_warp.position.y = (p2.y - pos_offset_y);
						}
					} else {
						
						/*
						// Cleanup possible warp when the path is redrawn (zoomed), currently not needed as everything is rebuild
						for (var j = 0; j < length_move_warp; j++) {
							
							var elm_warp = arr_move.arr_elm_warp[j];
							elm_warp.alpha = 0;
						}*/
					}
								
					if (do_show_info_line && is_self && is_active && arr_move.status) {

						const arr_info = arr_object_sub_line.arr_info;
						
						if (arr_info.is_added) {
							
							const elm_info = arr_info.elm;
							
							const time_position = duration * num_percentage;
							let num_alpha = 0;
							
							if (duration > duration_move_info_fade + 1000) {
								
								num_alpha = (time_position < duration_move_info_fade ? time_position / duration_move_info_fade : (duration - time_position < duration_move_info_fade ? (duration - time_position) / duration_move_info_fade : 1));
							} else if (num_percentage > 0.05 && num_percentage < 0.95) {
								
								num_alpha = (num_percentage <= 0.45 ? num_percentage * 2 : 0.95 + ((0.45 - num_percentage)*2));
							} else {
								
								elm_info.alpha = 0;
							}
							
							if (num_alpha) {
								
								elm_info.alpha = num_alpha;
								elm_info.position.x = Math.floor(elm.position.x) + arr_info.x;
								elm_info.position.y = Math.floor(elm.position.y) + arr_info.y;
								arr_info.x_base = Math.floor(elm.position.x + pos_offset_x);
								arr_info.y_base = Math.floor(elm.position.y + pos_offset_y);
								
								arr_info.update = true;
							}
						}
					}
				}

				if (do_remove || is_finished) {
					
					if (!move_chronological || !is_active) {
						
						removeLineInstance(arr_move);
						arr_animate_move.splice(i, 1);
						i--;
						len--;
					}
					
					arr_move.status = false;
				}
			}
		}
		
		if (moved_hint_line || do_show_info_line) {
			
			const arr_loop = arr_loop_object_sub_lines.arr_loop;
			
			for (let i = 0, len = arr_loop.length; i < len; i++) {
						
				const arr_object_sub_lines_locate = arr_loop[i];
									
				if (moved_hint_line) {

					const arr_connection_line = arr_object_sub_lines_locate.arr_connection_line;
					const arr_animate = arr_connection_line.arr_animate;
					
					if (arr_animate.length) {
						
						let time_elapsed = 0;
						
						if (arr_animate[0] === false) {
							arr_animate[0] = 0;
						} else {
							arr_animate[0] += time_diff;
							time_elapsed = arr_animate[0] / 500;
						}
						
						const elm_connection_line = arr_connection_line.elm;

						if (time_elapsed <= 1) {
							
							const calc = TWEEN.Easing.Sinusoidal.Out(time_elapsed);
							
							let calc_opacity = 0.1 + (0.4 - 0.1) * calc;
							
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

							arr_connection_line.arr_animate = [];
						}
					}
				}
				
				if (do_show_info_line) {
					
					const num_angle = arr_object_sub_lines_locate.arr_move_path.angle;	
					const arr_infos = arr_object_sub_lines_locate.arr_infos;
					
					if (arr_infos && arr_infos.length > 1) {
						
						const len_j = arr_infos.length;
						
						for (let j = 0; j < len_j; j++) {

							const arr_info = arr_infos[j];
							const elm_info = arr_info.elm;
							
							if (elm_info.alpha && arr_info.update) {
						
								const x_info = arr_info.x_base + arr_info.x;
								const y_info = arr_info.y_base + arr_info.y;
								let cur_height = arr_info.height + spacer_elm_info;
											
								// Check and set top down
								for (let k = j+1; k < len_j; k++) {
									
									const arr_info_check = arr_infos[k];
									const elm_info_check = arr_info_check.elm;
									
									const x_info_check = arr_info_check.x_base + arr_info_check.x;
									const y_info_check = arr_info_check.y_base + arr_info_check.y;
									
									// Insersect check
									if (!(x_info_check > (x_info + arr_info.width) || (x_info_check + arr_info_check.width) < x_info || y_info_check > (y_info + cur_height) || (y_info_check + arr_info_check.height) < y_info)) {
										
										if (num_angle > 270) {
											elm_info_check.position.y -= cur_height + (arr_info_check.y_base - arr_info.y_base);
											cur_height += arr_info_check.height + spacer_elm_info;
										} else if (num_angle > 180) {
											elm_info_check.position.y -= cur_height + (arr_info_check.y_base - arr_info.y_base);
											cur_height += arr_info_check.height + spacer_elm_info;
										} else if (num_angle > 90) {
											elm_info_check.position.y += cur_height - (arr_info_check.y_base - arr_info.y_base);
											cur_height += arr_info_check.height + spacer_elm_info;
										} else {
											elm_info_check.position.y += cur_height - (arr_info_check.y_base - arr_info.y_base);
											cur_height += arr_info_check.height + spacer_elm_info;
										}
										
										arr_info_check.update = false;
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

			for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

				const arr_object_sub_dot = arr_loop_object_sub_dots[i];
				
				if (hint_dot) {
					
					let len_j = arr_object_sub_dot.arr_hint_queue.length;
					
					if (len_j) {
						
						for (let j = 0; j < len_j; j++) {
							
							const arr_hint = arr_object_sub_dot.arr_hint_queue[j];
							let time_elapsed = 0;
							
							if (arr_hint[0] === false) {
								arr_hint[0] = 0;
							} else {
								arr_hint[0] += time_diff;
								time_elapsed = arr_hint[0] / duration_hint;
							}

							if (hint_dot === 'location') {

								const arr_location = arr_object_sub_dot.arr_location;
								const elm_location = arr_location.elm;

								const matrix = arr_location.matrix;

								if (time_elapsed <= 1) {
									
									const calc = TWEEN.Easing.Sinusoidal.Out(time_elapsed);
								
									let calc_bright = num_percentage_hint_location_start + (2 - num_percentage_hint_location_start) * calc;
									
									if (calc_bright > 1) {
										calc_bright = 2 - calc_bright;
									}

									matrix[4] = (((arr_color_hint.r - arr_color_location.r) * calc_bright) / 255);
									matrix[9] = (((arr_color_hint.g - arr_color_location.g) * calc_bright) / 255);
									matrix[14] = (((arr_color_hint.b - arr_color_location.b) * calc_bright) / 255);
								} else {
										
									count_animate_locations--;
									
									if (!arr_location.visible && !(info_mode == 'hover' && arr_object_sub_dot.info_hover)) {
										
										elm_location.visible = false;
										if (arr_location.elm_line !== false) {
											arr_location.elm_line.visible = false;
										}
										do_render_locations = true;
									}
									
									arr_object_sub_dot.arr_hint_queue = [];
								}
							} else {

								const elm = arr_hint[1];

								if (time_elapsed <= 1) {
									
									const x = Math.floor(arr_object_sub_dot.xy_geometry_center.x - pos_offset_x);
									const y = Math.floor(arr_object_sub_dot.xy_geometry_center.y - pos_offset_y);
									const num_size_radius = arr_hint[2];
									
									const calc = TWEEN.Easing.Sinusoidal.In(time_elapsed);
									
									const calc_scale = 1 + (((num_size_radius + size_hint) / num_size_radius) - 1) * calc;
									const calc_opacity = opacity_hint + (0 - opacity_hint) * calc;
									const calc_opacity_stroke = opacity_hint_stroke + (0 - opacity_hint_stroke) * calc;
									
									elm.clear();
									if (opacity_hint) {
										elm.beginFill(color, calc_opacity);
									}
									if (width_hint_stroke) {
										elm.lineStyle(width_hint_stroke, stroke_color, calc_opacity_stroke);
									}
									elm.drawCircle(x, y, num_size_radius * calc_scale);
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
						
						const arr_info = arr_object_sub_dot.arr_info;
						const elm_info = arr_info.elm;
						
						if (info_mode == 'hover') {
							
							elm_info.alpha = 1;
						} else {
							
							arr_info.duration -= time_diff;

							if (arr_info.duration <= 0) {
								
								arr_object_sub_dot.info_show = false;
								count_info_show_object_sub_dots--;
							} else if (arr_info.duration < duration_info_dot_fade) {

								elm_info.alpha = (arr_info.duration / duration_info_dot_fade);
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

		const time_diff = time - time_animate;
		
		if (hint_dot) {
				
			if (hint_dot === 'pulse') {
				var color = GeoUtilities.parseColor(color_hint);
				var stroke_color = GeoUtilities.parseColor(color_hint_stroke);
			}
			if (hint_dot === 'location') {
				var arr_color_location = arr_assets_colors_obj.location;
				var arr_color_hint = arr_assets_colors_obj.hint;
			}
			
			for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

				const arr_object_sub_dot = arr_loop_object_sub_dots[i];
				
				if (hint_dot) {
					
					let len_j = arr_object_sub_dot.arr_hint_queue.length;
					
					if (len_j) {
						
						for (let j = 0; j < len_j; j++) {
							
							const arr_hint = arr_object_sub_dot.arr_hint_queue[j];
							let time_elapsed = 0;
							
							if (arr_hint[0] === false) {
								arr_hint[0] = 0;
							} else {
								arr_hint[0] += time_diff;
								time_elapsed = arr_hint[0] / duration_hint;
							}

							if (hint_dot === 'location') {

								const arr_location = arr_object_sub_dot.arr_location;
								const elm_location = arr_location.elm;
								
								const arr_filter = arr_location.arr_filter;
								const matrix = arr_filter.matrix;

								if (time_elapsed <= 1) {
									
									const calc = TWEEN.Easing.Sinusoidal.Out(time_elapsed);
								
									let calc_bright = num_percentage_hint_location_start + (2 - num_percentage_hint_location_start) * calc;
									
									if (calc_bright > 1) {
										calc_bright = 2 - calc_bright;
									}

									matrix[4] = (((arr_color_hint.r - arr_color_location.r) * calc_bright) / 255);
									matrix[9] = (((arr_color_hint.g - arr_color_location.g) * calc_bright) / 255);
									matrix[14] = (((arr_color_hint.b - arr_color_location.b) * calc_bright) / 255);
									
									arr_filter.func_update();
								} else {

									count_animate_locations--;
									
									if (!arr_location.visible) {
									
										elm_location.dataset.visible = 0;
										if (arr_location.elm_line !== false) {
											arr_location.elm_line.dataset.visible = 0;
										}
										do_render_locations = true;
									}
									
									arr_object_sub_dot.arr_hint_queue = [];
								}
							} else {

								const elm = arr_hint[1];

								if (time_elapsed <= 1) {
									
									const x = Math.floor(arr_object_sub_dot.xy_geometry_center.x - pos_offset_x);
									const y = Math.floor(arr_object_sub_dot.xy_geometry_center.y - pos_offset_y);
									const num_size_radius = arr_hint[2];
									
									const calc = TWEEN.Easing.Sinusoidal.In(time_elapsed);
									
									const calc_scale = 1 + (((num_size_radius + size_hint) / num_size_radius) - 1) * calc;
									const calc_opacity = opacity_hint + (0 - opacity_hint) * calc;
									const calc_opacity_stroke = opacity_hint_stroke + (0 - opacity_hint_stroke) * calc;
									
									elm.setAttribute('cx', x);
									elm.setAttribute('cy', y);
									elm.setAttribute('r', num_size_radius * calc_scale);								
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

	var interact = function(e) {
		
		elm_hover_target = false;
		type_elm_hover_target = false;
		
		if (display == DISPLAY_PIXEL) {
			interactPixel(e);
		} else {
			interactVector(e);
		}
		
		interactListener(e);
	}
	
	var interactListener = null;
	
	var interactVector = function() {

		const pos_hover = PARENT.obj_map.getMousePosition();
		
		if (!pos_hover) {
			
			if (pos_hover_poll) {
				pos_hover_poll = false;
			}
			
			return;
		}
		
		const num_x_hover = pos_hover.x;
		const num_y_hover = pos_hover.y;
		const has_moved = (!pos_hover_poll || (Math.abs(num_x_hover-pos_hover_poll.x) > 0 || Math.abs(num_y_hover-pos_hover_poll.y) > 0));

		if (!has_moved) {
			
			elm_hover_target = elm_hover;
			type_elm_hover_target = type_elm_hover;
			
			return;
		}
		
		pos_hover_poll = pos_hover;
		
		// Get hovering element

		if (elm_hover_check !== null && elm_hover_check !== renderer && elm_hover_check.parentNode !== elm[0]) { // Not SVG or root
			
			if (elm_hover_check.classList.contains('line')) {
				
				elm_hover_target = elm_hover_check;
				type_elm_hover_target = 'line';
			} else if (elm_hover_check.classList.contains('dot')) {
			
				const dot_locate_identifier = elm_hover_check.dataset.locate_identifier;
				const arr_object_sub_dot = arr_object_sub_dots[dot_locate_identifier];
				
				elm_hover_target = arr_object_sub_dot;
				type_elm_hover_target = 'dot';
			} else if (elm_hover_check.nodeName == 'text') {
						
				elm_hover_target = elm_hover_check.arr_object_sub_dot;
				type_elm_hover_target = 'location';
			} else if (elm_hover_check.parentNode.classList.contains('dot')) {
					
				const dot_locate_identifier = elm_hover_check.parentNode.dataset.locate_identifier;
				const arr_object_sub_dot = arr_object_sub_dots[dot_locate_identifier];
				
				elm_hover_target = arr_object_sub_dot;
				type_elm_hover_target = 'dot';
			}
		}
		
		if (elm_hover_target !== false) {
			return;
		}
			
		for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

			const arr_object_sub_dot = arr_loop_object_sub_dots[i];
			
			if (arr_object_sub_dot.count == 0) {
				continue;
			}

			const num_dx = arr_object_sub_dot.xy_geometry_center.x - num_x_hover;
			const num_dy = arr_object_sub_dot.xy_geometry_center.y - num_y_hover;
			const num_distance_squared = num_dx * num_dx + num_dy * num_dy;
			
			let num_size = arr_object_sub_dot.arr_settings.size / 2 + width_dot_stroke;
			num_size = (num_size < 5 ? 5 : num_size);
			
			const num_radius_squared = (num_size + offset_info_hover) * (num_size + offset_info_hover);

			if (num_distance_squared < num_radius_squared) {

				elm_hover_target = arr_object_sub_dot;
				type_elm_hover_target = 'dot';
				
				break;
			}
		}
	};
	
	var interactPixel = function() {
				
		const pos_hover = PARENT.obj_map.getMousePosition();
		
		if (!pos_hover) {
			
			if (pos_hover_poll) {
				
				pos_hover_poll = false;
				
				// Cleanup
				
				if (info_mode == 'hover') {
				
					if (show_info_line) {
								
						const arr_loop = arr_loop_object_sub_lines.arr_loop;
						
						for (let i = 0, len = arr_loop.length; i < len; i++) {
							
							const arr_object_sub_lines_locate = arr_loop[i];
							
							arr_object_sub_lines_locate.info_hover = false;
						}
					}
					
					if (show_info_dot) {
						
						for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {
							
							const arr_object_sub_dot = arr_loop_object_sub_dots[i];
							
							arr_object_sub_dot.info_hover = false;
						}
					}
					
					drawInfo();
				}
			}
			
			return;
		}
		
		const num_x_hover = pos_hover.x;
		const num_y_hover = pos_hover.y;
		const has_moved = (!pos_hover_poll || (Math.abs(num_x_hover-pos_hover_poll.x) > 0 || Math.abs(num_y_hover-pos_hover_poll.y) > 0));

		if (!has_moved) {
			
			elm_hover_target = elm_hover;
			type_elm_hover_target = type_elm_hover;
			
			return;
		}
		
		pos_hover_poll = pos_hover;
		
		// Get hovering element

		if (info_mode == 'hover') {
		
			count_info_show_object_sub_lines_hover = 0;
			count_info_show_object_sub_dots_hover = 0;
			
			if (show_info_line) {
					
				const arr_loop = arr_loop_object_sub_lines.arr_loop;
				
				for (let i = 0, len = arr_loop.length; i < len; i++) {
							
					const arr_object_sub_lines_locate = arr_loop[i];
					
					if (arr_object_sub_lines_locate.count != 0) {

						const in_range = computeObjectSubLineMatch(arr_object_sub_lines_locate, num_x_hover, num_y_hover, offset_info_hover);
						
						if (in_range) {
								
							if (arr_object_sub_lines_locate.count_info_show) {
								
								count_info_show_object_sub_lines_hover += arr_object_sub_lines_locate.count_info_show;
								arr_object_sub_lines_locate.info_hover = true;
							}
							
							continue;
						}
					}
					
					arr_object_sub_lines_locate.info_hover = false;
				}
			}
			
			if (show_dot) {
				
				for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

					const arr_object_sub_dot = arr_loop_object_sub_dots[i];
					
					if (arr_object_sub_dot.count != 0) {

						const num_dx = arr_object_sub_dot.xy_geometry_center.x - num_x_hover;
						const num_dy = arr_object_sub_dot.xy_geometry_center.y - num_y_hover;
						const num_distance_squared = num_dx * num_dx + num_dy * num_dy;
						
						let num_size = arr_object_sub_dot.arr_settings.size / 2 + width_dot_stroke;
						num_size = (num_size < 5 ? 5 : num_size);
						
						const num_radius_squared = (num_size + offset_info_hover) * (num_size + offset_info_hover);

						if (num_distance_squared < num_radius_squared) {
								
							if (show_info_dot && arr_object_sub_dot.info_show) {
								
								count_info_show_object_sub_dots_hover++;
								arr_object_sub_dot.info_hover = true;
							}

							elm_hover_target = arr_object_sub_dot;
							type_elm_hover_target = 'dot';
							
							continue;
						}
					}

					arr_object_sub_dot.info_hover = false;
				}
			}

			drawInfo();
		}
		
		if (show_location && location_position_manual && !elm_hover_target) {
									
			for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {
		
				const arr_object_sub_dot = arr_loop_object_sub_dots[i];
				
				if (arr_object_sub_dot.count == 0) {
					continue;
				}
				
				const arr_location = arr_object_sub_dot.arr_location;
		
				const num_x_origin = arr_object_sub_dot.xy_geometry_center.x + arr_location.x_offset;
				const num_y_origin = arr_object_sub_dot.xy_geometry_center.y + arr_location.y_offset_origin + arr_location.y_offset;
				
				if (num_x_hover < num_x_origin - (arr_location.width / 2) || num_x_hover > num_x_origin + (arr_location.width / 2) || num_y_hover < num_y_origin - (arr_location.height / 2) || num_y_hover > num_y_origin + (arr_location.height / 2)) {
					continue;
				}
				
				elm_hover_target = arr_object_sub_dot;
				type_elm_hover_target = 'location';
				
				break;
			}
		}
	};
	
	var positionLabels = function() {
		
		if (show_location && location_position_algorithmic) {
			
			if (worker_labels) {
				
				worker_labels.terminate();
				worker_labels = null;
			}
			
			if (in_predraw && display == DISPLAY_VECTOR) {
				
				for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

					const arr_object_sub_dot = arr_loop_object_sub_dots[i];
					const arr_location = arr_object_sub_dot.arr_location;
					
					if (!arr_location) {
						continue;
					}

					const arr_size = arr_location.elm.getBBox();
					
					arr_location.width = arr_size.width;
				}
			}
		
			let arr_nodes = [];
			
			let num_count = 0;
			const arr_level = PARENT.obj_map.levelVars();
			
			for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

				const arr_object_sub_dot = arr_loop_object_sub_dots[i];
				const arr_location = arr_object_sub_dot.arr_location;
				
				if (!arr_location) {
					continue;
				}
				
				if (!arr_location.manual) { // Reset position if not manually altered
					arr_location.x_offset = 0;
					arr_location.y_offset = 0;
				}
				
				const pos_origin_x = arr_object_sub_dot.xy_geometry_center.x;
				const pos_origin_y = arr_object_sub_dot.xy_geometry_center.y;

				const do_use = (arr_location.algorithm && !arr_location.manual);
				
				const arr_node_dot = {
					move: false,
					x: pos_origin_x, y: pos_origin_y,
					width: arr_location.size_algorithm, height: arr_location.size_algorithm
				};
				const arr_node_label = {
					move: do_use,
					x: (pos_origin_x + arr_location.x_offset), y: (pos_origin_y + arr_location.y_offset_origin + arr_location.y_offset),
					width: arr_location.width, height: arr_location.height,
					key: i
				};
				
				arr_nodes.push(arr_node_dot, arr_node_label);

				num_count += 2;
			}

			worker_labels = ElementPositionWorker();
			
			worker_labels.postMessage({
					action: 'init',
					nodes: arr_nodes,
					mode: 'label',
					settings: {
						force_friction: 0.2,
						force_charge: -30,
						force_gravity: 0,
						force_theta: 0.8,
						threshold_update: 0,
						padding: (offset_location ? Math.abs(offset_location) : 1),
						width: arr_level.width,
						height: arr_level.height
					}
				}
			);
		
			worker_labels.addEventListener('message', function(event) {
				
				arr_nodes = event.data.nodes;
			
				for (let i = 0, len = arr_nodes.length; i < len; i += 2) {
					
					const arr_node = arr_nodes[i+1];
					
					const arr_object_sub_dot = arr_loop_object_sub_dots[arr_node.key];
					const arr_location = arr_object_sub_dot.arr_location;
					
					if (arr_location.manual) { // Could be manually positioned in the mean time
						continue
					}
									
					moveDotLocation(arr_object_sub_dot, arr_node.x, arr_node.y, true);
				}
				
				if (event.data.done) {
					
					worker_labels.terminate();
					worker_labels = null;
				}
			});
		}
	};
		
	var drawObjectSub = function(object_sub_id, do_remove) {

		const arr_object_sub = arr_data.object_subs[object_sub_id];
		
		if (in_predraw) {
			arr_object_sub.use = (do_remove || arr_object_sub.location_geometry === '' ? false : true); // Outside of bounds, or no geometry, do not use!
		}
		
		if (!arr_object_sub.use) {
			return;
		}
				
		let arr_connections = arr_object_sub.connect_object_sub_ids;
		
		if (in_predraw) {

			if (arr_connections && !arr_connections.length) {
				arr_object_sub.connect_object_sub_ids = false;
				arr_connections = false;
			}
			
			setObjectSubCondition(arr_object_sub);
		}

		// Dots
		
		if (in_predraw) {

			arr_object_sub.dot_locate_identifier = arr_object_sub.location_geometry;
			arr_object_sub.dot_locate_identifier_pos = addObjectSubDot(arr_object_sub, object_sub_id, arr_object_sub.dot_locate_identifier);
						
			arr_object_sub.arr_object_sub_dot = arr_object_sub_dots[arr_object_sub.dot_locate_identifier];
		}
		
		updateObjectSubDot(arr_object_sub, object_sub_id);
		
		const is_new = setObjectSubDot(arr_object_sub, object_sub_id, do_remove);
	
		if (!redraw && is_new && !do_remove && hint_dot && (mode != MODE_MOVE || (!move_hint_dot && !moved_hint_dot) || (!arr_connections && !move_chronological))) { // Add a hint to the sub-object when move_hint_dot and moved_hint_dot are not explicitly set or when it has no connections (i.e. a starting point)
			setObjectSubDotHint(arr_object_sub);
		}
		
		// Lines
		if (arr_connections && show_line) {
			
			if (in_predraw) {
				arr_object_sub.arr_object_sub_lines = [];
			}

			for (let i = 0, len = arr_connections.length; i < len; i++) {
				
				const connect_object_sub_id = arr_connections[i];
				const arr_connect_object_sub = arr_data.object_subs[connect_object_sub_id];
				let do_remove_connect_object_sub;

				if (in_predraw) {
					
					// If the connecting sub-object is out of bounds, if there is no line needed between identical locations, or missing locations
					if (!checkInRange(arr_connect_object_sub) || arr_object_sub.location_geometry == arr_connect_object_sub.location_geometry || arr_connect_object_sub.location_geometry === '') {
						continue;
					}
					
					setObjectSubCondition(arr_connect_object_sub); // Sub-object could not have yet been accessed before
					
					do_remove_connect_object_sub = false;

					const line_locate_identifier = arr_object_sub.location_geometry+'|'+arr_connect_object_sub.location_geometry;
					let line_connect_identifier;
					
					// If connection sub-object has identifiable definitions, use as connection id, otherwise keep sub-object unique
					if (arr_object_sub.line_connect_identifier) {
						line_connect_identifier = arr_object_sub.line_connect_identifier+'_'+arr_object_sub.object_sub_details_id+'_'+arr_connect_object_sub.object_sub_details_id;
					} else {
						line_connect_identifier = object_sub_id+'_'+connect_object_sub_id;
					}
					
					const line_connect_identifier_pos = addObjectSubLine(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, line_locate_identifier, line_connect_identifier);
					const line_connect_identifier_pos_connect = addObjectSubLineConnect(arr_connect_object_sub, connect_object_sub_id, line_locate_identifier, line_connect_identifier);
					
					const arr_object_sub_lines_locate = arr_object_sub_lines[line_locate_identifier];
					const arr_object_sub_line = arr_object_sub_lines_locate[line_connect_identifier];
					
					arr_object_sub.arr_object_sub_lines[i] = {line_connect_identifier_pos: line_connect_identifier_pos, line_connect_identifier_pos_connect: line_connect_identifier_pos_connect, arr_object_sub_lines_locate: arr_object_sub_lines_locate, arr_object_sub_line: arr_object_sub_line};
				} else {
					
					// If the connecting sub-object is out of bounds, if there is no line needed between identical locations, or missing locations
					if (!arr_connect_object_sub.use || (arr_object_sub.location_geometry == arr_connect_object_sub.location_geometry)) {
						continue;
					}
					
					// If sub-object is to be removed or connecting sub-object is to be removed (outside date range)
					do_remove_connect_object_sub = (do_remove ? true : !checkInRange(arr_connect_object_sub));
				}
				
				const obj_object_sub_line = arr_object_sub.arr_object_sub_lines[i];
				
				updateObjectSubLine(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line);		

				if (!redraw && is_new && !do_remove_connect_object_sub && hint_dot && mode == MODE_MOVE && move_hint_dot && !move_chronological) { // Add a hint to the connecting sub-object as it is used as a starting point for a connection in move mode
					setObjectSubDotHint(arr_connect_object_sub);
				}
				
				setObjectSubLine(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line, do_remove_connect_object_sub);
			}
		}
	};
	
	var setObjectSubCondition = function(arr_object_sub) {
		
		if (arr_object_sub.conditions !== undefined) {
			return;
		}
		
		const arr_style = arr_object_sub.style_inherit;
		arr_object_sub.conditions = {};
		
		if (!arr_style || (!arr_style.color && !arr_style.icon && arr_style.weight == null)) {
			
			arr_object_sub.conditions.weight = null;
			arr_object_sub.conditions.list = null;
			return;
		}

		const arr_conditions = [];
		
		const is_array_color = (typeof arr_style.color == 'object');
		const is_array_icon = (typeof arr_style.icon == 'object');
		const num_weight_style = (arr_style.weight !== null ? arr_style.weight : null);
		let num_weight_conditions = 0;
		
		const arr_legend_conditions = arr_data.legend.conditions;
		
		for (const str_identifier_condition in arr_style.conditions) {
			
			const arr_legend_condition = arr_legend_conditions[str_identifier_condition];
			
			let num_weight = arr_style.conditions[str_identifier_condition];
			
			if (num_weight === null) {
				continue;
			}
			
			num_weight_conditions += num_weight;
			let color = null;
			let icon = null;

			if (arr_legend_condition.color) {
				
				if (is_array_color) {
					
					for (let i = 0, len = arr_style.color.length; i < len; i++) {
						
						if (arr_legend_condition.color !== arr_style.color[i]) {
							continue;
						}
							
						color = arr_style.color[i];
						break;
					}
				} else if (arr_legend_condition.color === arr_style.color) {
					
					color = arr_style.color;
				}
			}
			
			if (arr_legend_condition.icon) {
				
				if (is_array_icon) {
					
					for (let i = 0, len = arr_style.icon.length; i < len; i++) {
						
						if (arr_legend_condition.icon !== arr_style.icon[i]) {
							continue;
						}
						
						icon = arr_style.icon[i];
						break;
					}
				} else if (arr_legend_condition.icon === arr_style.icon) {
					
					icon = arr_style.icon;
				}
			}
			
			if (color === null && icon === null && num_weight_style === null) {
				continue;
			}
			
			arr_conditions.push({identifier: str_identifier_condition, weight: num_weight, color: color, icon: icon});
		}
		
		// Adjust the individual condition weights to scale within the overall calculated weight
		
		arr_object_sub.conditions.weight = num_weight_style;
		
		if (num_weight_style !== null && num_weight_conditions != num_weight_style) {
			
			const num_scale = (num_weight_style / num_weight_conditions);
			arr_object_sub.conditions.weight = 0;
			
			for (let i = 0, len = arr_conditions.length; i < len; i++) {
				
				const arr_condition = arr_conditions[i];
				arr_condition.weight = arr_condition.weight * num_scale;
				
				arr_object_sub.conditions.weight += arr_condition.weight;
			}
		}
		
		arr_object_sub.conditions.list = arr_conditions;
	}
	
	var addObjectSubDot = function(arr_object_sub, object_sub_id, locate_identifier) {
		
		var arr_object_sub_dot = arr_object_sub_dots[locate_identifier];
		
		if (!arr_object_sub_dot) {
			
			arr_object_sub_dots[locate_identifier] = {locate_identifier: locate_identifier, object_sub_ids: [], count: 0, count_unweighted: 0, weight: 0, updated: false, is_added: false, identifier: '', identifier_geometry: '', arr_settings: {size: 0, assets: {}}, elm: false, elm_geometry: false, arr_info: false, info_show: false, info_hover: false, arr_location: false, num_zoom: false, arr_geometry_plotable: false, xy_geometry_center: false, hint: false, arr_hint_queue: [], highlight_color: false, highlight_class: false};
			
			arr_object_sub_dot = arr_object_sub_dots[locate_identifier];
			
			if (show_info_dot) {
				arr_object_sub_dot.arr_info = {identifier: '', elm: false, elm_text: false, elm_pointer: false, width: false, height: false, duration: false, is_added: false};
			}

			var arr_geometry_package = arr_object_sub.arr_geometry_package;
			var arr_geometry_path = arr_object_sub.arr_geometry_path;
			
			var arr_geometry_plotable = [];
			
			if ((!arr_object_sub.has_geometry_path || arr_object_sub.has_geometry_path && !show_line) || arr_object_sub.connect_object_sub_ids === false) { // No geometry is used as line path
					
				handleGeometryPackage(arr_geometry_package, arr_geometry_plotable);
			}
			
			arr_object_sub_dot.arr_geometry_plotable = arr_geometry_plotable;
			
			arr_loop_object_sub_dots.push(arr_object_sub_dot);
		}
		
		arr_object_sub_dot.count++;
		
		if (is_weighted) {
			if (arr_object_sub.style_inherit.weight !== null) {
				arr_object_sub_dot.weight += (arr_object_sub.connected_object_ids.length * arr_object_sub.conditions.weight);
			} else {
				arr_object_sub_dot.count_unweighted++;
				if (arr_object_sub_dot.count_unweighted == 1) {
					arr_object_sub_dot.weight += 1;
				}
			}
		} else {
			if (arr_object_sub.style_inherit.weight !== 0) {
				arr_object_sub_dot.weight += (arr_object_sub.connected_object_ids.length * 1);
			} else {
				// Do not show
			}
		}
		
		return arr_object_sub_dot.object_sub_ids.push(object_sub_id) - 1;
	};
	
	var handleGeometryPackage = function(arr_geometry_package, arr_geometry_plotable) {
		
		let i_geo = 0;
		const len_i_geo = arr_geometry_package.length;
		
		while (i_geo < len_i_geo) {
			
			const cur_value = arr_geometry_package[i_geo];
				
			if (typeof cur_value === 'string') {

				switch (cur_value) {
					case 'Point':
					case 'LineString':
						
						arr_geometry_plotable.push(cur_value);
						i_geo++;
						
						while (typeof arr_geometry_package[i_geo] === 'object' || arr_geometry_package[i_geo] === null) {
							
							if (arr_geometry_package[i_geo] === null) {
								
								arr_geometry_plotable.push(null);
								
								i_geo++;
								continue;
							}
							
							const arr_point = PARENT.obj_map.plotPoint(arr_geometry_package[i_geo][1], arr_geometry_package[i_geo][0], 1, true);
							
							arr_geometry_plotable.push(arr_point.x, arr_point.y);
							
							i_geo++;
						}
						
						break;
					case 'MultiPoint':

						arr_geometry_plotable.push('Group');
						arr_geometry_plotable.push('Point');
						i_geo++;
						
						while (typeof arr_geometry_package[i_geo] === 'object') {
							
							const arr_point = PARENT.obj_map.plotPoint(arr_geometry_package[i_geo][1], arr_geometry_package[i_geo][0], 1, true);
							
							arr_geometry_plotable.push(arr_point.x, arr_point.y);
							
							i_geo++;
						}
						
						break;
					case 'MultiLineString':
				
						arr_geometry_plotable.push('Group');
						i_geo++;
						
						while (typeof arr_geometry_package[i_geo] === 'object') {
							
							arr_geometry_plotable.push('LineString');
							const arr_level_points = arr_geometry_package[i_geo];

							for (let j_geo = 0, len_j_geo = arr_level_points.length; j_geo < len_j_geo; j_geo++) {
								
								if (arr_level_points[j_geo] === null) {
								
									arr_geometry_plotable.push(null);
									continue;
								}
								
								const arr_point = PARENT.obj_map.plotPoint(arr_level_points[j_geo][1], arr_level_points[j_geo][0], 1, true);
													
								arr_geometry_plotable.push(arr_point.x, arr_point.y);
							}
							
							i_geo++;
						}
					
						break;
					case 'Polygon':

						const i_geo_start = i_geo;
						const len_halves = arr_geometry_package[i_geo].length;
						
						if (len_halves > 1) {
							arr_geometry_plotable.push('Group');
						}
						
						for (let i_halves = 0; i_halves < len_halves; i_halves++) {
							
							i_geo = i_geo_start;
							i_geo++;
							
							arr_geometry_plotable.push('Polygon');
						
							let num_count = 0;
							
							const num_length_start = arr_geometry_plotable.length;
							const arr_edge = {};
						
							while (typeof arr_geometry_package[i_geo] === 'object') {
								
								const arr_level_points = arr_geometry_package[i_geo][i_halves];

								if (arr_level_points != null && arr_level_points.length) {
														
									arr_geometry_plotable.push(num_count % 2 ? 'hole' : 'ring');
									
									for (let j_geo = 0, len_j_geo = arr_level_points.length; j_geo < len_j_geo; j_geo++) {
										
										const arr_coordinate = arr_level_points[j_geo];
										
										const arr_point = PARENT.obj_map.plotPoint(arr_coordinate[1], arr_coordinate[0], 1, true);
										
										arr_geometry_plotable.push(arr_point.x, arr_point.y);
										
										if (arr_coordinate[2] === true) { // Is on longitude edge
											arr_edge[arr_geometry_plotable.length - num_length_start - 2] = true;
										}
									}
								}
								
								num_count++;
								i_geo++;
							}
							
							handleGeometryPackageBorder(arr_geometry_plotable, arr_edge, num_length_start);
						}
						
						break;
					case 'MultiPolygon':

						arr_geometry_plotable.push('Group');
						i_geo++;
						
						while (typeof arr_geometry_package[i_geo] === 'object') {
							
							const arr_level_polygon = arr_geometry_package[i_geo];
							const len_halves = arr_level_polygon[0].length;
							
							for (let i_halves = 0; i_halves < len_halves; i_halves++) {
																		
								arr_geometry_plotable.push('Polygon');
							
								let num_count = 0;
								
								const num_length_start = arr_geometry_plotable.length;
								const arr_edge = {};

								for (let j_geo = 0, len_j_geo = arr_level_polygon.length; j_geo < len_j_geo; j_geo++) {
									
									arr_geometry_plotable.push(num_count % 2 ? 'hole' : 'ring');
									const arr_level_points = arr_level_polygon[j_geo][i_halves];
									
									if (arr_level_points != null) {
									
										for (let k_geo = 0, len_k_geo = arr_level_points.length; k_geo < len_k_geo; k_geo++) {
											
											const arr_coordinate = arr_level_points[k_geo];
											
											const arr_point = PARENT.obj_map.plotPoint(arr_coordinate[1], arr_coordinate[0], 1, true);
										
											arr_geometry_plotable.push(arr_point.x, arr_point.y);
											
											if (arr_coordinate[2] === true) { // Is on border
												arr_edge[arr_geometry_plotable.length - num_length_start - 2] = true;
											}
										}
									}
									
									num_count++;
								}
								
								handleGeometryPackageBorder(arr_geometry_plotable, arr_edge, num_length_start);
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
	};
	
	var handleGeometryPackageBorder = function(arr_geometry_plotable, arr_edge, num_length) {
		
		if (Object.keys(arr_edge).length === 0) {
			return;
		}
		
		const arr_copy = arr_geometry_plotable.slice(num_length);
		
		arr_geometry_plotable.splice(num_length, 0, 'noline');
												
		let i = 0;
		const len = arr_copy.length; 
		
		while (i < len) {
			
			if (typeof arr_copy[i] !== 'number') {
				
				arr_geometry_plotable.push('LineString', 'islinepolygon');
				
				i += 1;
			} else {
				
				if (arr_edge[i] === true) {
				
					arr_geometry_plotable.push('LineString', 'islinepolygon');
				}
				
				arr_geometry_plotable.push(arr_copy[i], arr_copy[i+1]);
				
				i += 2;
			}
		}
	};
	
	var setObjectSubDot = function(arr_object_sub, object_sub_id, do_remove) {
		
		const arr_object_sub_dot = arr_object_sub.arr_object_sub_dot;
		const pos = arr_object_sub.dot_locate_identifier_pos;

		if (do_remove) {
			if (arr_object_sub_dot.object_sub_ids[pos]) {
				
				arr_object_sub_dot.object_sub_ids[pos] = 0;
				arr_object_sub_dot.count--;
				if (is_weighted) {
					if (arr_object_sub.style_inherit.weight !== null) {
						arr_object_sub_dot.weight -= (arr_object_sub.connected_object_ids.length * arr_object_sub.conditions.weight);
					} else {
						arr_object_sub_dot.count_unweighted++;
						if (arr_object_sub_dot.count_unweighted == 0) {
							arr_object_sub_dot.weight -= 1;
						}
					}
				} else {
					if (arr_object_sub.style_inherit.weight !== 0) {
						arr_object_sub_dot.weight -= (arr_object_sub.connected_object_ids.length * 1);
					}
				}
				arr_object_sub_dot.updated = count_loop;
			}
		} else {
			if (!arr_object_sub_dot.object_sub_ids[pos]) {
				
				arr_object_sub_dot.object_sub_ids[pos] = object_sub_id;
				arr_object_sub_dot.count++;
				if (is_weighted) {
					if (arr_object_sub.style_inherit.weight !== null) {
						arr_object_sub_dot.weight += (arr_object_sub.connected_object_ids.length * arr_object_sub.conditions.weight);
					} else {
						arr_object_sub_dot.count_unweighted++;
						if (arr_object_sub_dot.count_unweighted == 1) {
							arr_object_sub_dot.weight += 1;
						}
					}
				} else {
					if (arr_object_sub.style_inherit.weight !== 0) {
						arr_object_sub_dot.weight += (arr_object_sub.connected_object_ids.length * 1);
					}
				}
				arr_object_sub_dot.updated = count_loop;
				
				return true;
			}
		}
		
		return false;
	};
	
	var setObjectSubDotHint = function(arr_object_sub, locate_identifier) {

		const arr_object_sub_dot = (locate_identifier ? arr_object_sub_dots[locate_identifier] : arr_object_sub.arr_object_sub_dot);
		
		if (!arr_object_sub_dot.hint && arr_object_sub_dot.weight) {
			
			arr_object_sub_dot.hint = true;
			count_hint_object_sub_dots++;
		}
	};
	
	var updateObjectSubDot = function(arr_object_sub, object_sub_id) {
		
		if (redraw === REDRAW_SCALE) {
			
			const arr_object_sub_dot = arr_object_sub.arr_object_sub_dot;
			
			const xy_geometry_center = PARENT.obj_map.plotPoint(arr_object_sub.arr_geometry_path[1], arr_object_sub.arr_geometry_path[0], null, true);
			
			arr_object_sub_dot.xy_geometry_center = xy_geometry_center;
			arr_object_sub_dot.num_zoom = cur_zoom;
			
			arr_object_sub_dot.arr_hint_queue = [];
		}
	};
			
	var addObjectSubLine = function(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, locate_identifier, connect_identifier) {
				
		let arr_object_sub_lines_locate = arr_object_sub_lines[locate_identifier];

		if (!arr_object_sub_lines_locate) {
			
			arr_object_sub_lines[locate_identifier] = {locate_identifier: locate_identifier, arr_con: [], updated: false, num_zoom: false, count: 0, elm_connection_line: false, opacity_connection_line: false, arr_connection_line: false, arr_move_path: false, arr_infos: false, count_info_show: 0, info_hover: false,
				location_geometry: arr_object_sub.location_geometry,
				connect_location_geometry: arr_connect_object_sub.location_geometry,
				arr_path: []
			};
			
			arr_object_sub_lines_locate = arr_object_sub_lines[locate_identifier];
			
			if (mode == MODE_MOVE) {
				
				arr_object_sub_lines_locate.arr_connection_line = {elm: false, x: false, y: false, visible: false, arr_animate: []};
			}
			
			arr_loop_object_sub_lines.arr_loop.push(arr_object_sub_lines_locate);
		}
		
		let arr_object_sub_line = arr_object_sub_lines_locate[connect_identifier];
		
		if (!arr_object_sub_line) {
			
			arr_object_sub_lines_locate[connect_identifier] = {connect_identifier: connect_identifier, connect_object_sub_ids: [], object_sub_ids: [], count: 0, count_connect: 0, count_unweighted: 0, weight: 0, updated: false, is_added: false, identifier: '', arr_settings: {size: 0}, elm: false, elm_container: false, info_show: false, arr_info: false, arr_elm_warp: false, is_active: false, arr_move: false, arr_move_queue: false,
				object_sub_details_id: arr_object_sub.object_sub_details_id,
				connect_object_sub_details_id: arr_connect_object_sub.object_sub_details_id,
				has_connection_line: false
			};
			
			arr_object_sub_line = arr_object_sub_lines_locate[connect_identifier];
			
			if (show_info_line) {
				arr_object_sub_line.arr_info = {identifier: '', elm: false, elm_text: false, elm_pointer: false, x: false, y: false, width: false, height: false, percentage: false, delay: false, duration: false, x_base: false, y_base: false, update: false, is_added: false};
			}
			
			arr_object_sub_lines_locate.arr_con.push(arr_object_sub_line);
		}
		
		arr_object_sub_lines_locate.count++;
		arr_object_sub_line.count++;
		
		if (is_weighted) {
			if (arr_connect_object_sub.style_inherit.weight !== null) {
				arr_object_sub_line.weight += arr_connect_object_sub.conditions.weight;
			} else {
				arr_object_sub_line.count_unweighted++;
				if (arr_object_sub_line.count_unweighted == 1) {
					arr_object_sub_line.weight += 1;
				}
			}
		} else {
			if (arr_connect_object_sub.style_inherit.weight !== 0) {
				arr_object_sub_line.weight += 1;
			} else {
				// Do not show
			}
		}
		
		return arr_object_sub_line.object_sub_ids.push(object_sub_id) - 1;
	};
	
	var updateObjectSubLine = function(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line) {

		if (redraw === REDRAW_SCALE) {
			
			if (mode == MODE_MOVE) {
				
				const arr_object_sub_lines_locate = obj_object_sub_line.arr_object_sub_lines_locate;
				
				arr_object_sub_lines_locate.elm_connection_line = false
				arr_object_sub_lines_locate.arr_connection_line.elm = false;
				arr_object_sub_lines_locate.arr_connection_line.visible = false;
			}
		}
		
		if (redraw === REDRAW_SCALE) {
			
			const arr_object_sub_lines_locate = obj_object_sub_line.arr_object_sub_lines_locate;
			const arr_object_sub_line = obj_object_sub_line.arr_object_sub_line;
			
			if (!arr_object_sub_lines_locate.num_zoom || arr_object_sub_lines_locate.num_zoom != cur_zoom) { // Do update
				
				const arr_path = [];
				
				const arr_path_geometry = arr_object_sub.arr_geometry_path[2];
				const arr_start = (arr_object_sub.has_geometry_path ? arr_path_geometry[0] : arr_object_sub.arr_geometry_path);
				const arr_end = arr_connect_object_sub.arr_geometry_path;
				
				arr_path.push(PARENT.obj_map.plotPoint(arr_end[1], arr_end[0], null, true));
				
				if (arr_start[0] != arr_end[0] || arr_start[1] != arr_end[1]) { // The first coordinates do match up; there is no connection line present
					arr_object_sub_line.has_connection_line = true;
				}
				
				if (Math.sign(arr_start[0]) !== Math.sign(arr_end[0]) && Math.abs(arr_start[0]) + Math.abs(arr_end[0]) > 180) {
					
					if (arr_end[0] < 0) {
						arr_path.push(PARENT.obj_map.plotPoint(arr_start[1], (-180 - (180 - arr_start[0])), null, true), null, PARENT.obj_map.plotPoint(arr_end[1], (180 + (180 + arr_end[0])), null, true));
					} else {
						arr_path.push(PARENT.obj_map.plotPoint(arr_start[1], (180 + (180 + arr_start[0])), null, true), null, PARENT.obj_map.plotPoint(arr_end[1], (-180 - (180 - arr_end[0])), null, true));
					}					
				}
				
				arr_path.push(PARENT.obj_map.plotPoint(arr_start[1], arr_start[0], null, true));
								
				if (arr_object_sub.has_geometry_path) {
					
					for (let i = 0, len = arr_path_geometry.length; i < len; i++) {
						
						if (i == 0 && !arr_object_sub_line.has_connection_line) {
							continue;
						} else if (arr_path_geometry[i] === null) {
							
							arr_path.push(null);
							continue;
						}
						
						arr_path.push(PARENT.obj_map.plotPoint(arr_path_geometry[i][1], arr_path_geometry[i][0], null, true));
					}
				}

				arr_object_sub_lines_locate.arr_path = arr_path;
				
				if (mode == MODE_MOVE) {

					const x_start = arr_path[0].x;
					const y_start = arr_path[0].y;
					const x_end = arr_path[1].x;
					const y_end = arr_path[1].y;
										
					let angle = (Math.atan2(y_end - y_start, x_end - x_start) * 180 / Math.PI);
					angle -= 90; // Correction
					if (angle < 0) {
						angle = 360 - (-angle);
					}
					if (angle > 360) {
						angle = 360 - angle;
					}
					
					const identifier = x_start+'-'+x_end+'-'+y_start+'-'+y_end+'-'+arr_object_sub.location_geometry;
					let arr_move_path = arr_assets_elm_line_dot_paths[identifier];
						
					if (!arr_move_path) {
						
						const arr_obj_path = (offset_line ? [] : false);
													
						let length_total = 0;
						let x_start = arr_path[0].x;
						let y_start = arr_path[0].y;
						
						for (let i = 1, len = arr_path.length; i < len; i++) {
							
							if (arr_path[i] === null) {
								
								i += 2;
								x_start = arr_path[i].x;
								y_start = arr_path[i].y;
								continue;
							}
							
							const x_end = x_start;
							const y_end = y_start;
							x_start = arr_path[i].x;
							y_start = arr_path[i].y;
							
							const x_vec = x_end-x_start;
							const y_vec = y_end-y_start;

							length_total += Math.sqrt(x_vec*x_vec + y_vec*y_vec);
							
							if (offset_line) {
																
								const c1 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset_line, 0.25);
								const c2 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset_line, 0.75);
									
								arr_obj_path[i] = new Bezier(x_start, y_start, c1.x, c1.y, c2.x, c2.y, x_end, y_end);
							}
						}
						
						arr_move_path = {obj: arr_obj_path, length: length_total, angle: angle};
						
						arr_assets_elm_line_dot_paths[identifier] = arr_move_path;
					}
					
					arr_object_sub_lines_locate.arr_move_path = arr_move_path;
					arr_object_sub_lines_locate.num_zoom = cur_zoom;
				}
			}

			if (mode == MODE_MOVE) {
				
				if (in_predraw) {
					
					arr_object_sub_line.arr_move = {arr_object_sub_lines_locate: arr_object_sub_lines_locate, arr_object_sub_line: arr_object_sub_line, elm: false, elm_container: false, key: false, arr_elm_warp: [], start: false, duration: false, delay: false, cur_delay: false, percentage: false, status: 0};
					
					if (move_retain === 'all') {
						
						arr_object_sub_line.arr_move_queue = [];
					}
				}
				
				const arr_move = arr_object_sub_line.arr_move;
							
				if (move_unit == 'pixel') {
					
					let duration = arr_object_sub_lines_locate.arr_move_path.length / speed_move;
					if (duration_move_min && duration < duration_move_min) {
						duration = duration_move_min;
					} else if (duration_move_max && duration > duration_move_max) {
						duration = duration_move_max;
					}
					arr_move.duration = duration*1000;
				}
				
				if (move_retain === 'all') {
					
					const arr_move_queue = arr_object_sub_line.arr_move_queue;
					
					for (let i = 0, len = arr_move_queue.length; i < len; i++) {
						
						const arr_move_instance = arr_move_queue[i];
						
						arr_move_instance.duration = arr_move.duration;
					}
				}
			}
		} else if (redraw === REDRAW_RESET) {
			
			if (mode == MODE_MOVE) {
				
				if (move_retain === 'all') {
						
					arr_object_sub_line.arr_move_queue = [];
				}
			}
		}
	};
	
	var computeObjectSubLinePosition = function(arr_object_sub_lines_locate, num_percentage) {
		
		var arr_move_path = arr_object_sub_lines_locate.arr_move_path;
		var arr_path = arr_object_sub_lines_locate.arr_path;
					
		var length_find = (arr_move_path.length * num_percentage);

		var length_compute = 0;
		var x_start = arr_path[0].x;
		var y_start = arr_path[0].y;
				
		for (var i = 1, len = arr_path.length; i < len; i++) {
			
			var xy_new = arr_path[i];
			
			if (xy_new === null) {
				
				i += 2;
				x_start = arr_path[i].x;
				y_start = arr_path[i].y;
				continue;
			}

			var x_vec = xy_new.x-x_start;
			var y_vec = xy_new.y-y_start;
			var length_add = Math.sqrt(x_vec*x_vec + y_vec*y_vec);

			if (length_find <= (length_compute + length_add)) {
				
				var num_percentage_pos = ((length_find - length_compute) / length_add);
				
				if (offset_line) {
					var p = arr_move_path.obj[i].compute(1 - num_percentage_pos); // Reverse because connection line computes from start sub-object (direction source) to connection sub-object
				} else {
					var p = {
						x: (x_start + (xy_new.x - x_start) * num_percentage_pos),
						y: (y_start + (xy_new.y - y_start) * num_percentage_pos)
					};
				}
				
				if (p.x > size_map.width) {
					p.x = p.x - size_map.width;
				} else if (p.x < 0) {
					p.x = size_map.width + p.x;
				}
				
				return p;
			}
			
			length_compute += length_add;
			x_start = xy_new.x;
			y_start = xy_new.y;
		}
	};
		
	var computeObjectSubLineMatch = function(arr_object_sub_lines_locate, x_point, y_point, offset) {
		
		var arr_path = arr_object_sub_lines_locate.arr_path;
			
		var x_start = arr_path[0].x;
		var y_start = arr_path[0].y;
		
		for (var i = 1, len = arr_path.length; i < len; i++) {
			
			var xy_new = arr_path[i];
			
			if (xy_new === null) {
				
				i++; // Only skip one point, testing all the lines available, including possible duplicates due to the fold.
				x_start = arr_path[i].x;
				y_start = arr_path[i].y;
				continue;
			}
							
			if (GeoUtilities.isNearLine(x_start, y_start, xy_new.x, xy_new.y, x_point, y_point, offset)) {
				return true;
			}
			
			x_start = xy_new.x;
			y_start = xy_new.y;
		}
		
		return false;
	};
	
	var addObjectSubLineConnect = function(arr_connect_object_sub, connect_object_sub_id, locate_identifier, connect_identifier) {
		
		arr_object_sub_lines[locate_identifier][connect_identifier].count_connect++;

		return arr_object_sub_lines[locate_identifier][connect_identifier].connect_object_sub_ids.push(connect_object_sub_id) - 1;
	};
	
	var setObjectSubLine = function(arr_object_sub, object_sub_id, arr_connect_object_sub, connect_object_sub_id, obj_object_sub_line, do_remove) {
		
		var arr_object_sub_lines_locate = obj_object_sub_line.arr_object_sub_lines_locate;
		var arr_object_sub_line = obj_object_sub_line.arr_object_sub_line;
		var arr_object_sub_line_source = arr_object_sub_line.object_sub_ids;
		var arr_object_sub_line_connect = arr_object_sub_line.connect_object_sub_ids;
		var pos = obj_object_sub_line.line_connect_identifier_pos;
		var pos_connect = obj_object_sub_line.line_connect_identifier_pos_connect;

		if (do_remove) {
			if (arr_object_sub_line_source[pos]) {
				
				arr_object_sub_line_source[pos] = 0;
				arr_object_sub_line.count--;
				if (is_weighted) {
					if (arr_connect_object_sub.style_inherit.weight !== null) {
						arr_object_sub_line.weight -= arr_connect_object_sub.conditions.weight;
					} else {
						arr_object_sub_line.count_unweighted--;
						if (arr_object_sub_line.count_unweighted == 0) {
							arr_object_sub_line.weight -= 1;
						}
					}
				} else {
					if (arr_connect_object_sub.style_inherit.weight !== 0) {
						arr_object_sub_line.weight -= 1;
					}
				}
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_locate.updated = count_loop;
				arr_object_sub_lines_locate.count--;
			}
			if (arr_object_sub_line_connect[pos_connect]) {
				
				arr_object_sub_line_connect[pos_connect] = 0;
				arr_object_sub_line.count_connect--;
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_locate.updated = count_loop;
			}
		} else {
			if (!arr_object_sub_line_source[pos]) {
				
				arr_object_sub_line_source[pos] = object_sub_id;
				arr_object_sub_line.count++;
				if (is_weighted) {
					if (arr_connect_object_sub.style_inherit.weight !== null) {
						arr_object_sub_line.weight += arr_connect_object_sub.conditions.weight;
					} else {
						arr_object_sub_line.count_unweighted++;
						if (arr_object_sub_line.count_unweighted == 1) {
							arr_object_sub_line.weight += 1;
						}
					}
				} else {
					if (arr_connect_object_sub.style_inherit.weight !== 0) {
						arr_object_sub_line.weight += 1;
					}
				}
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_locate.updated = count_loop;
				arr_object_sub_lines_locate.count++;
			}
			if (!arr_object_sub_line_connect[pos_connect]) {
				
				arr_object_sub_line_connect[pos_connect] = connect_object_sub_id;
				arr_object_sub_line.count_connect++;
				arr_object_sub_line.updated = count_loop;
				arr_object_sub_lines_locate.updated = count_loop;
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
			
			if (arr_object_sub_dot.updated != count_loop && !redraw) { // This locate_identifier has not been updated, nothing to do!
				continue;
			}
			
			do_draw = true;
			
			if (redraw === REDRAW_SCALE) {
				arr_object_sub_dot.is_added = false;
			}
			
			if (!arr_object_sub_dot.count) {
				
				if (display == DISPLAY_PIXEL) {
					
					if (show_dot && arr_object_sub_dot.arr_settings.size) {
						
						arr_object_sub_dot.elm.visible = false;
						do_render_dots = true;
						
						const arr_location = arr_object_sub_dot.arr_location;
						
						if (show_location && arr_location.visible) {
							
							arr_location.visible = false;
							arr_location.elm.visible = false;
							if (arr_location.elm_line !== false) {
								arr_location.elm_line.visible = false;
							}
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
						
						if (arr_object_sub_dot.identifier !== false) {
							
							arr_object_sub_dot.elm.dataset.visible = 0;
							
							const arr_location = arr_object_sub_dot.arr_location;
												
							if (show_location && arr_location.visible) {
								
								arr_location.visible = false;
								arr_location.elm.dataset.visible = 0;
								if (arr_location.elm_line !== false) {
									arr_location.elm_line.dataset.visible = 0;
								}
							}
						}
					}
					
					if (arr_object_sub_dot.identifier_geometry) {
						arr_object_sub_dot.elm_geometry.dataset.visible = 0;
					}
				}

				continue;
			}
						
			drawDot(arr_object_sub_dot);
		}
	};
	
	var drawLines = function() {
		
		if (!show_line) {
			return;
		}
		
		if (!count_line_weight_max) {
			
			// Find location with highest amount of references
			
			const arr_loop = arr_loop_object_sub_lines.arr_loop;
			
			for (let i = 0, len = arr_loop.length; i < len; i++) {
				
				let arr_object_sub_lines_locate = arr_loop[i];	
				let num_count_total = 0;
				let arr_loop_loc = arr_object_sub_lines_locate.arr_con;
				
				for (let j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
					
					const arr_object_sub_line = arr_loop_loc[j];
					
					num_count_total += arr_object_sub_line.weight;
				}
				
				if (!offset_line) {
							
					const locate_identifier = arr_object_sub_lines_locate.connect_location_geometry+'|'+arr_object_sub_lines_locate.location_geometry;
					arr_object_sub_lines_locate = arr_object_sub_lines[locate_identifier];
					
					if (arr_object_sub_lines_locate) {
						
						arr_loop_loc = arr_object_sub_lines_locate.arr_con;
						
						for (let j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
							
							const arr_object_sub_line = arr_loop_loc[j];
							
							num_count_total += arr_object_sub_line.weight;
						}
					}
				}	
				
				if (num_count_total > count_line_weight_max) {
					count_line_weight_max = num_count_total;
				}
			}
		}
		
		const arr_loop = arr_loop_object_sub_lines.arr_loop;
		
		for (var i = 0, len = arr_loop.length; i < len; i++) {
						
			const arr_object_sub_lines_locate = arr_loop[i];
			
			if (arr_object_sub_lines_locate.updated != count_loop && !redraw) { // This locate_identifier has not been updated, nothing to do!
				continue;
			}
			
			do_draw = true;

			const num_locate = arr_object_sub_lines_locate.count;
			let num_offset = (mode == MODE_MOVE ? 0 : offset_line);
			let num_count = 0;
			
			let num_count_total = 0;
			let num_count_active = 0;
			
			const arr_loop_loc = arr_object_sub_lines_locate.arr_con;
					
			for (let j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
				
				const arr_object_sub_line = arr_loop_loc[j];
				
				if (mode == MODE_MOVE && (arr_object_sub_line.updated != count_loop && !redraw)) { // This connect_identifier has not been updated, nothing to do!
					
					num_count_total += arr_object_sub_line.weight;
				
					if (arr_object_sub_line.is_active) {
						num_count_active += arr_object_sub_line.weight;
					}
					
					continue;
				}

				if (redraw === REDRAW_SCALE) {
					if (mode == MODE_MOVE) {

					} else {
						arr_object_sub_line.is_added = false;
					}
				}
							
				if (!arr_object_sub_line.weight) {
					
					removeLine(arr_object_sub_line); // Hide line
					
					if (arr_object_sub_line.info_show) {
						arr_object_sub_line.info_show = false;
						arr_object_sub_lines_locate.count_info_show--;
						count_info_show_object_sub_lines--;
					}
					
					arr_object_sub_line.is_active = false;
					
					continue;
				}
 
				drawLine(arr_object_sub_line, arr_object_sub_lines_locate, (mode == MODE_MOVE ? (num_offset/num_locate) : num_offset), num_count);

				num_count_total += arr_object_sub_line.weight;
				
				if (arr_object_sub_line.is_active) {
					num_count_active += arr_object_sub_line.weight;
				}
				
				num_offset += (mode == MODE_MOVE ? 1 : arr_object_sub_line.arr_settings.size);
				num_count++;
			}
			
			if (mode == MODE_MOVE) {
				
				if (display == DISPLAY_PIXEL && opacity_connection_line) {
					
					const arr_path = arr_object_sub_lines_locate.arr_path;
					const x_origin = Math.floor(arr_path[0].x);
					const y_origin = Math.floor(arr_path[0].y);
					
					let arr_connection_line = arr_object_sub_lines_locate.arr_connection_line;

					if (redraw === REDRAW_POSITION || redraw === REDRAW_RESET) {
						
						if (arr_connection_line.visible && redraw === REDRAW_POSITION) {
							
							const elm = arr_connection_line.elm;
				
							elm.position.x = (arr_connection_line.x - pos_offset_x);
							elm.position.y = (arr_connection_line.y - pos_offset_y);
						}
					} else if (num_count && !arr_connection_line.visible) {
						
						if (!arr_connection_line.elm) {

							if (!offset_line) {
								
								var locate_identifier = arr_object_sub_lines_locate.connect_location_geometry+'|'+arr_object_sub_lines_locate.location_geometry;

								if (arr_object_sub_lines[locate_identifier] && arr_object_sub_lines[locate_identifier].arr_connection_line.elm) {
									arr_object_sub_lines_locate.arr_connection_line = arr_object_sub_lines[locate_identifier].arr_connection_line;
									arr_connection_line = arr_object_sub_lines_locate.arr_connection_line;
								}
							}
							
							if (!arr_connection_line.elm) {
								
								const elm = new PIXI.Graphics();
								elm.lineStyle(width_connection_line, GeoUtilities.parseColor(color_connection_line), 1); // 1.5 (default) pixels vs 1: better consistent render quality, though no optimisation

								let x_start = 0;
								let y_start = 0;
																
								if (offset_line) {
	
									for (let j = 1, len_j = arr_path.length; j < len_j; j++) {
										
										if (arr_path[j] === null) {
									
											j++;
											x_start = Math.floor(arr_path[j].x) - x_origin;
											y_start = Math.floor(arr_path[j].y) - y_origin;
											
											elm.moveTo(x_start, y_start);
											continue;
										}
										
										let x_end = x_start;
										let y_end = y_start;
										x_start = Math.floor(arr_path[j].x) - x_origin;
										y_start = Math.floor(arr_path[j].y) - y_origin;

										c1 = GeoUtilities.calcPointOffset(x_end, y_end, x_start, y_start, -offset_line, 0.25);
										c2 = GeoUtilities.calcPointOffset(x_end, y_end, x_start, y_start, -offset_line, 0.75);
										
										elm.bezierCurveTo(c1.x, c1.y, c2.x, c2.y, x_start, y_start);
									}
								} else {

									for (let j = 1, len_j = arr_path.length; j < len_j; j++) {
										
										if (arr_path[j] === null) {
									
											j++;
											x_start = Math.floor(arr_path[j].x) - x_origin;
											y_start = Math.floor(arr_path[j].y) - y_origin;
											
											elm.moveTo(x_start, y_start);
											continue;
										}
										
										x_start = Math.floor(arr_path[j].x) - x_origin;
										y_start = Math.floor(arr_path[j].y) - y_origin;

										elm.lineTo(x_start, y_start);
									}
								}
								
								elm_plot_connection_lines.addChild(elm);
								
								arr_connection_line.arr_animate = [];
								arr_connection_line.elm = elm;
								arr_connection_line.x = x_origin; // Store origin because if !offset_line, the element can be shared between two different location origins
								arr_connection_line.y = y_origin;

								/*var glow = new PIXI.filters.GlowFilter(renderer_activity.width, renderer_activity.height, 8, 2, 2, color_connection_line, 0.5);
								elm.filters = [glow];*/							
							}
						}
						
						const elm = arr_connection_line.elm;
						
						elm.position.x = (arr_connection_line.x - pos_offset_x);
						elm.position.y = (arr_connection_line.y - pos_offset_y);
						
						elm.alpha = opacity_connection_line;
						elm.visible = true;
						arr_connection_line.visible = true;
						
						do_render_connection_lines = true;
						
						arr_object_sub_lines_locate.elm_connection_line = elm;
						arr_object_sub_lines_locate.opacity_connection_line = 0;
						
					} else if (!num_count_total && arr_connection_line.visible) {
						
						let do_hide = true;
						
						if (!offset_line) {
							
							const locate_identifier = arr_object_sub_lines_locate.connect_location_geometry+'|'+arr_object_sub_lines_locate.location_geometry;
							
							if (arr_object_sub_lines[locate_identifier] && arr_object_sub_lines[locate_identifier].elm_connection_line) {
								do_hide = false;
							}
						}
						
						const elm = arr_connection_line.elm;
						
						if (do_hide) {
							
							elm.visible = false;
							arr_connection_line.visible = false;
						} else {

							elm.alpha -= arr_object_sub_lines_locate.opacity_connection_line;
							
							if (moved_hint_line && elm.alpha <= opacity_connection_line_range_min) {
								elm.alpha = opacity_connection_line;
							}
						}
						
						do_render_connection_lines = true;
						
						arr_object_sub_lines_locate.elm_connection_line = false;
					} 

					if (num_count_total && arr_connection_line.visible && opacity_connection_line_range) { // Adjust opacity to the amount of active but non-animating subobjects
						
						const elm = arr_connection_line.elm;
						
						let num_opacity = 0;
						if (move_apply_opacity_connection_line == 'moved') {
							num_opacity = (((num_count_total - num_count_active) * opacity_connection_line_range) / count_line_weight_max);	
						} else {
							num_opacity = ((num_count_total * opacity_connection_line_range) / count_line_weight_max);
						}

						elm.alpha += (num_opacity - arr_object_sub_lines_locate.opacity_connection_line);
						arr_object_sub_lines_locate.opacity_connection_line = num_opacity;
						
						if (num_opacity) { // If num_count_active < num_count_total
							
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
			
			let num_count = 0;
			
			for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

				const arr_object_sub_dot = arr_loop_object_sub_dots[i];
				
				if (arr_object_sub_dot.hint) {
					
					arr_object_sub_dot.hint = false;
					
					if (num_count < max_hint_dots) {
						drawDotHint(arr_object_sub_dot);
					}
					
					num_count++;
				}
			}
		}
		
		count_hint_object_sub_dots = 0;
	};
	
	var drawInfo = function() {
		
		if (display == DISPLAY_PIXEL) {

			const num_count_line = (info_mode == 'hover' ? count_info_show_object_sub_lines_hover : count_info_show_object_sub_lines);
			do_show_info_line = (show_info_line && num_count_line && (show_info_line === true || num_count_line < show_info_line));
			
			const num_count_dot = (info_mode == 'hover' ? count_info_show_object_sub_dots_hover : count_info_show_object_sub_dots);
			do_show_info_dot = (show_info_dot && num_count_dot && (show_info_dot === true || num_count_dot < show_info_dot));
			
			if ((show_info_line && num_count_line && !do_show_info_line) || (show_info_dot && num_count_dot && !do_show_info_dot)) { // Do not show either when one reached the limit
				do_show_info_line = false;
				do_show_info_dot = false;
			}
			
			if (do_show_info_line) {
				
				let is_new = false;
				
				if (!elm_container_info_lines) {
					
					elm_container_info_lines = new PIXI.Container();
					elm_plot_between.addChild(elm_container_info_lines);
					is_new = true;
				} else if (redraw) {
					
					elm_plot_between.addChild(elm_container_info_lines);
				}

				const arr_loop = arr_loop_object_sub_lines.arr_loop;
				
				for (let i = 0, len = arr_loop.length; i < len; i++) {
					
					const arr_object_sub_lines_locate = arr_loop[i];
					
					if (info_mode == 'all' && arr_object_sub_lines_locate.updated != count_loop && !redraw) {
						continue;
					}
					
					if (!arr_object_sub_lines_locate.count_info_show || (info_mode == 'hover' && !arr_object_sub_lines_locate.info_hover)) {
						
						const arr_infos = arr_object_sub_lines_locate.arr_infos;
						
						if (arr_infos) {

							for (let j = 0, len_j = arr_infos.length; j < len_j; j++) {

								const arr_info = arr_infos[j];
								
								if (!is_new) {
									elm_container_info_lines.removeChild(arr_info.elm);
								}
								arr_info.is_added = false;
							}
						}
						
						continue;
					}
					
					let num_angle = null;
					
					arr_object_sub_lines_locate.arr_infos = false;
					arr_infos = [];
					
					const arr_loop_loc = arr_object_sub_lines_locate.arr_con;
					
					let x_start = null;
					let y_start = null;
					let c = null;
					
					for (let j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
						
						const arr_object_sub_line = arr_loop_loc[j];

						const arr_info = arr_object_sub_line.arr_info;
						
						if (!arr_object_sub_line.info_show || (mode == MODE_MOVE && info_mode == 'all' && !arr_info.is_added && arr_object_sub_line.arr_move.percentage >= 0.05)) { // In mode move, do not begin to show info at already traveling instances in mode 'all'
							
							if (arr_info.is_added) {
								
								if (!is_new) {
									elm_container_info_lines.removeChild(arr_info.elm);
								}
								arr_info.is_added = false;
							}
							
							continue;
						}

						let num_identifier = arr_object_sub_line.count;
						let align = '';
						let valign = '';
						
						if (num_angle === null) {
						
							num_angle = arr_object_sub_lines_locate.arr_move_path.angle;
								
							if (num_angle > 270) {
								align = 'left';
								valign = 'bottom';
							} else if (num_angle > 180) {
								align = 'right';
								valign = 'bottom';
							} else if (num_angle > 90) {
								align = 'right';
								valign = 'top';
							} else {
								align = 'left';
								valign = 'top';
							}
							
							let correction = ((4/90) * (90 * ((num_angle/90) - Math.floor(num_angle/90)))); // Perform small corrections to make the pointing corner align nicely
							if ((num_angle < 270 && num_angle > 180) || (num_angle > 0 && num_angle < 90)) {
								correction = -correction;
							} else {
								correction = 4 - correction;
							}
							
							const arr_path = arr_object_sub_lines_locate.arr_path;
							x_start = arr_path[0].x;
							y_start = arr_path[0].y;
							const x_end = arr_path[1].x;
							const y_end = arr_path[1].y;

							c = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, 10, correction/arr_object_sub_lines_locate.arr_move_path.length);
						}

						if (!arr_info.elm || arr_info.identifier != num_identifier) {
							
							const arr_object_subs = getObjectSubsLineDetails(arr_object_sub_line);
							const dateinta_range_sub = arr_object_subs.dateinta_range;
							const arr_first_object_sub = arr_object_subs.arr_first_object_sub;
							const arr_first_connect_object_sub = arr_object_subs.arr_first_connect_object_sub;
							
							let str_date_start = '';
							let str_date_end = '';
							
							const date_range_sub_min = DATEPARSER.int2Date(DATEPARSER.dateIntAbsolute2DateInt(dateinta_range_sub.min));
							const date_range_sub_max = DATEPARSER.int2Date(DATEPARSER.dateIntAbsolute2DateInt(dateinta_range_sub.max));
							if (date_range_sub_min.getFullYear() == -90000000000) {
								str_date_start = '-∞';
							} else {
								str_date_start = DATEPARSER.date2StrDate(date_range_sub_min, settings_timeline.dating.show_ce);
							}
							if (date_range_sub_max.getFullYear() == 90000000000) {
								str_date_end = '∞';
							} else {
								str_date_end = DATEPARSER.date2StrDate(date_range_sub_max, settings_timeline.dating.show_ce);
							}
							
							let spacing_prefix = '';
							let spacing_affix = '';
							if (align == 'left') {
								spacing_prefix = '    ';
							} else {
								spacing_affix = '    ';
							}
							
							let str_object = '';
							let str_object_name = '';
							let str_add = '';
							let object_id = null;
							let object_sub_details_id = null;
							let arr_object_sub_definitions = null;
							
							if (arr_first_connect_object_sub) {
								object_id = arr_first_connect_object_sub.object_id;
								object_sub_details_id = arr_first_connect_object_sub.object_sub_details_id;
								arr_object_sub_definitions = arr_first_connect_object_sub.object_sub_definitions;
							} else {
								object_id = arr_first_object_sub.object_id;
								object_sub_details_id = arr_first_object_sub.object_sub_details_id;
								arr_object_sub_definitions = arr_first_object_sub.object_sub_definitions;
							}

							if (arr_object_sub_line.count > 1) {
								
								const type_id = arr_data.objects[object_id].type_id;
								const first_object_type_id = (arr_first_connect_object_sub && arr_first_object_sub ? arr_data.objects[arr_first_object_sub.object_id].type_id : type_id);
								
								if (align == 'left') {
									str_object_name = arr_data.info.types[type_id].name+(first_object_type_id != type_id ? ' - '+arr_data.info.types[first_object_type_id].name : '')+' '+arr_object_sub_line.count+'x';
								} else {
									str_object_name = arr_object_sub_line.count+'x '+arr_data.info.types[type_id].name+(first_object_type_id != type_id ? ' - '+arr_data.info.types[first_object_type_id].name : '');
								}
							} else {
								
								str_object_name = stripHTMLTags(arr_data.objects[object_id].name);
							}
							
							if (align == 'left') {
								str_add = spacing_prefix+'['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'] '+str_date_start;
							} else {
								str_add = str_date_start+' ['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']'+spacing_affix;
							}
							
							for (const key in arr_object_sub_definitions) {
								
								const object_sub_definition = arr_object_sub_definitions[key];
								
								if (!object_sub_definition.value) {
									continue;
								}
								
								const object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
								
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
									
									str_object_name = stripHTMLTags(arr_data.objects[arr_first_object_sub.object_id].name);
								} else {
									str_object_name = '';
								}
								
								if (align == 'left') {
									str_add = spacing_prefix+'['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'] '+str_date_end;
								} else {
									str_add = str_date_end+' ['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']'+spacing_affix;
								}
								
								for (const key in arr_object_sub_definitions) {
									
									const object_sub_definition = arr_object_sub_definitions[key];
									
									if (!object_sub_definition.value) {
										continue;
									}
									
									const object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_definition.description_id].object_sub_description_name; // Could be collapsed
									
									str_add = str_add+'\n'+spacing_prefix+''+object_sub_definition.value+spacing_affix;
								}
								
								if (valign == 'top') {
									str_object = str_object+'\n'+(str_object_name ? str_object_name+'\n' : '')+str_add;
								} else {
									str_object = str_add+'\n'+(str_object_name ? str_object_name+'\n' : '')+str_object;
								}
							}
							
							let elm_info = null;
							let elm_pointer = null;
							
							if (!arr_info.elm) {
							
								elm_info = new PIXI.Container();

								const elm_text = new PIXI.Text(str_object, {fontSize: size_info, fontFamily: font_family, fill: color_info, align: align});
								elm_info.addChild(elm_text);
								
								elm_pointer = new PIXI.Sprite(arr_assets_texture_info.pointer);
								elm_info.addChild(elm_pointer);
								
								arr_info.elm = elm_info;
								arr_info.elm_text = elm_text;
								arr_info.elm_pointer = elm_pointer;
							} else {
								
								elm_info = arr_info.elm;
								arr_info.elm_text.text = str_object;
								elm_pointer = arr_info.elm_pointer;
							}

							arr_info.x = Math.floor(c.x-x_start);
							arr_info.y = Math.floor(c.y-y_start);
							arr_info.width = Math.floor(elm_info.width);
							arr_info.height = Math.floor(elm_info.height);
							
							let num_angle_info = -45;
							
							elm_pointer.position.x = -4
							elm_pointer.position.y = -2;
							if (num_angle > 270) {
								arr_info.y -= arr_info.height;
								elm_pointer.position.y = arr_info.height + 2;
								elm_pointer.rotation = 270 * Math.PI/180;
								num_angle_info = 270 + (3*45);
							} else if (num_angle > 180) {
								arr_info.x -= arr_info.width;
								arr_info.y -= arr_info.height;
								elm_pointer.position.x = arr_info.width + 4;
								elm_pointer.position.y = arr_info.height + 2;
								elm_pointer.rotation = 180 * Math.PI/180;
								num_angle_info = 180 - 45;
							} else if (num_angle > 90) {
								arr_info.x -= arr_info.width;
								elm_pointer.position.x = arr_info.width + 4;
								elm_pointer.rotation = 90 * Math.PI/180;
								num_angle_info = 90 + (3*45);
							} else {

							}
							
							const num_radius = (arr_object_sub_line.arr_settings.size / 2);
							const num_distance = 1;
							arr_info.x += (num_radius * Math.cos(-num_angle_info*Math.PI/180) * num_distance);
							arr_info.y += (num_radius * Math.sin(-num_angle_info*Math.PI/180) * num_distance);

							arr_info.identifier = num_identifier;
						}
						
						if (!arr_info.is_added || is_new || redraw) {
							
							if (duration_move_info_min || duration_move_info_max) {
								
								let duration = arr_object_sub_line.arr_move.duration;
								if (duration_move_info_min && duration < duration_move_info_min) {
									duration = duration_move_info_min;
								} else if (duration_move_info_max && duration > duration_move_info_max) {
									duration = duration_move_info_max;
								}
								arr_info.duration = duration;
							}
							
							elm_container_info_lines.addChild(arr_info.elm);
							arr_info.is_added = true;
						}
						
						arr_info.elm.alpha = 0;
						
						arr_info.percentage = arr_object_sub_line.arr_move.percentage;
						arr_info.delay = arr_object_sub_line.arr_move.delay;
						
						arr_infos.push(arr_info);
					}

					if (arr_infos.length > 1) {
						
						arr_infos.sort(function(a, b) {
							if (b.percentage || a.percentage) {
								return b.percentage - a.percentage;
							} else {
								return a.delay - b.delay;
							}
						});
					}
						
					arr_object_sub_lines_locate.arr_infos = arr_infos;
				}
			} else if (elm_container_info_lines) {
				
				elm_plot_between.removeChild(elm_container_info_lines);
				elm_container_info_lines = false;
			}
						
			if (do_show_info_dot) {

				let is_new = false;
				
				if (!elm_container_info_dots) {
					
					elm_container_info_dots = new PIXI.Container();
					elm_plot_between.addChild(elm_container_info_dots);
					is_new = true;
				} else if (redraw) {
					
					elm_plot_between.addChild(elm_container_info_dots);
				}
				
				for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

					const arr_object_sub_dot = arr_loop_object_sub_dots[i];
					
					if (info_mode == 'all' && arr_object_sub_dot.updated != count_loop && !redraw) {
						continue;
					}
						
					const arr_info = arr_object_sub_dot.arr_info;
					
					if (!arr_object_sub_dot.info_show || (info_mode == 'hover' && !arr_object_sub_dot.info_hover)) {
						
						if (arr_info.is_added) {
							
							if (!is_new) {
								
								elm_container_info_dots.removeChild(arr_info.elm);

								if (info_mode == 'hover' && show_location) {
									
									const arr_location = arr_object_sub_dot.arr_location;
									
									if (!arr_location.visible) {

										arr_location.elm.visible = false;
										do_render_locations = true;
									}
								}
							}
							
							arr_info.is_added = false;
						}
						
						continue;
					}
					
					let num_identifier = arr_object_sub_dot.count;
					let do_restart = false;

					if (!arr_info.elm || arr_info.identifier != num_identifier) {

						const arr_object_subs_dot_details = getObjectSubsDotDetails(arr_object_sub_dot);
				
						let str_object = '';

						const arr_view = arr_object_subs_dot_details.arr_view;
						
						for (const type_id in arr_view.types) {
														
							const arr_view_type = arr_view.types[type_id];
								
							for (const object_sub_details_id in arr_view_type) {

								str_object = (str_object ? str_object+'\n' : '')+'['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']';
								
								const arr_view_type_object_sub = arr_view_type[object_sub_details_id];
																	
								const arr_view_type_object_sub_definitions = arr_view_type_object_sub.object_sub_definitions;
								
								for (const object_sub_description_id in arr_view_type_object_sub_definitions) {
									
									const object_sub_description_name = arr_data.info.object_sub_descriptions[object_sub_description_id].object_sub_description_name; // Could be collapsed
										
									const arr_ref_objects = arr_view_type_object_sub_definitions[object_sub_description_id];
									
									const arr_sort = [];
									for (const ref_object_id in arr_ref_objects) {
										arr_sort.push([ref_object_id, arr_ref_objects[ref_object_id].count]);
									}
									arr_sort.sort(function(a, b) {
										return b[1] - a[1];
									});
									
									let count = 0;
									for (let j = 0, len_j = arr_sort.length; j < len_j; j++) {
										
										if (count > 3) {
											break;
										}
										
										const arr_ref_object = arr_ref_objects[arr_sort[j][0]];
										
										str_object = str_object+'\n'+stripHTMLTags(arr_ref_object.value)+(arr_ref_object.count > 1 ? ' ('+arr_ref_object.count+'x)' : '');
										count++;
									}
									
									if (arr_sort.length > count) {
										str_object = str_object+'\n... '+(arr_sort.length-count)+'x';
									}
								}
								
								const arr_view_type_object_sub_objects = arr_view_type_object_sub.objects;
								
								if (arr_view_type_object_sub_objects) {
										
									const arr_objects = arr_view_type_object_sub_objects;
									
									const arr_sort = [];
									for (const object_id in arr_objects) {
										arr_sort.push([object_id, arr_objects[object_id].count]);
									}
									arr_sort.sort(function(a, b) {
										return b[1] - a[1];
									});
									
									let count = 0;
									for (let j = 0, len_j = arr_sort.length; j < len_j; j++) {
										
										if (count > 3) {
											break;
										}
										
										const arr_object = arr_objects[arr_sort[j][0]];
										
										str_object = str_object+'\n'+stripHTMLTags(arr_object.value)+(arr_object.count > 1 ? ' ('+arr_object.count+'x)' : '');
										count++;
									}
									
									if (arr_sort.length > count) {
										str_object = str_object+'\n... '+(arr_sort.length-count)+'x';
									}
								}
							}
						}
						
						const arr_view_scope = arr_view.scope;
			
						if (arr_view_scope) {
							
							str_object = (info_content == 'default' ? str_object+'\n-'+arr_labels.lbl_scope+'-' : str_object);
							
							for (const type_id in arr_view_scope) {
																
								const arr_objects = arr_view_scope[type_id];
									
								const arr_sort = [];
								for (const object_id in arr_objects) {
									arr_sort.push([object_id, arr_objects[object_id].count]);
								}
								arr_sort.sort(function(a, b) {
									return b[1] - a[1];
								});
								
								let count = 0;
								for (let j = 0, len_j = arr_sort.length; j < len_j; j++) {
									
									if (count > 3) {
										break;
									}
									
									const arr_object = arr_objects[arr_sort[j][0]];
									
									str_object = (str_object ? str_object+'\n' : '')+stripHTMLTags(arr_object.value)+(arr_object.count > 1 ? ' ('+arr_object.count+'x)' : '');
									count++;
								}
								
								if (arr_sort.length > count) {
									str_object = str_object+'\n... '+(arr_sort.length-count)+'x';
								}
							}
						}
						
						let elm_info = null;
						
						if (!arr_info.elm) {
						
							elm_info = new PIXI.Container();

							const elm_text = new PIXI.Text(str_object, {fontSize: size_info, fontFamily: font_family, fill: color_info, align: 'left'});
							elm_info.addChild(elm_text);

							const elm_pointer = new PIXI.Sprite(arr_assets_texture_info.pointer);
							elm_info.addChild(elm_pointer);
							
							arr_info.elm = elm_info;
							arr_info.elm_text = elm_text;
							arr_info.elm_pointer = elm_pointer;
							
							elm_pointer.position.x = -4
							elm_pointer.position.y = -2;
						} else {
							
							elm_info = arr_info.elm;
							arr_info.elm_text.text = str_object;
						}

						arr_info.width = Math.floor(elm_info.width);
						arr_info.height = Math.floor(elm_info.height);
						
						do_restart = true;
						arr_info.identifier = num_identifier;
					}
					
					if (!arr_info.is_added || is_new || redraw) {
												
						const num_radius = (arr_object_sub_dot.arr_settings.size / 2 + width_dot_stroke);
						const num_angle = -45;					
						const num_distance = 1;
						const num_x = (arr_object_sub_dot.xy_geometry_center.x - pos_offset_x) + (num_radius * Math.cos(-num_angle*Math.PI/180) * num_distance);
						const num_y = (arr_object_sub_dot.xy_geometry_center.y - pos_offset_y) + (num_radius * Math.sin(-num_angle*Math.PI/180) * num_distance);
						
						arr_info.elm.x = Math.floor(num_x + 7);
						arr_info.elm.y = Math.floor(num_y + 6);
						
						elm_container_info_dots.addChild(arr_info.elm);
						arr_info.is_added = true;
						
						if (info_mode == 'hover' && show_location) {
							
							const arr_location = arr_object_sub_dot.arr_location;
							
							if (!arr_location.visible) {

								arr_location.elm.visible = true;
								do_render_locations = true;
							}
						}
						
						do_restart = true;
					}
					
					arr_info.elm.alpha = 0;
					
					if (do_restart) {
						
						arr_info.duration = duration_info_dot_min;
					}
				}
			} else if (elm_container_info_dots) {
				
				if (info_mode == 'hover' && show_location) {
						
					for (let i = 0, len = arr_loop_object_sub_dots.length; i < len; i++) {

						const arr_object_sub_dot = arr_loop_object_sub_dots[i];
							
						const arr_info = arr_object_sub_dot.arr_info;

						if (arr_info.is_added) {

							const arr_location = arr_object_sub_dot.arr_location;
							
							if (!arr_location.visible) {

								arr_location.elm.visible = false;
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
		
		const arr_settings = arr_object_sub_dot.arr_settings;
		
		let num_size = arr_object_sub_dot.weight;
		
		if (!num_size) {
			
			// No dot
		} else if (count_dot_weight_max != count_dot_weight_min) {
			
			if (num_size > count_dot_weight_max) {
				num_size = size_dot.max;
			} else if (num_size < count_dot_weight_min) {
				num_size = size_dot.min;
			} else {
				num_size = Math.round(size_dot.min + ((size_dot.max - size_dot.min) * ((num_size - count_dot_weight_min) / (count_dot_weight_max - count_dot_weight_min))));
			}
		} else {
			
			num_size = size_dot.min;
		}
		
		arr_settings.size = num_size;
		
		let str_identifier = false;
		
		const x_origin = arr_object_sub_dot.xy_geometry_center.x;
		const y_origin = arr_object_sub_dot.xy_geometry_center.y;

		if (show_dot && num_size) {
			
			const num_size_radius = (num_size / 2);
			
			str_identifier = num_size;
			
			const arr_assets = arr_settings.assets;
			const arr_assets_touched = {};
			const arr_dot_colors = [];
			const arr_dot_icons = [];
			let str_svg_classes = 'dot';
			const arr_conditions = {};
			
			let do_override_color = false;
			let color_override = false;
			if (arr_object_sub_dot.highlight_color) {
				
				do_override_color = true;
				color_override = arr_object_sub_dot.highlight_color;
				str_svg_classes+= ' '+arr_object_sub_dot.highlight_class;
			} else if (color_dot) {
				
				if (!dot_color_condition) {
					do_override_color = true;
				}
				color_override = color_dot;
			}
			let do_show_location = (location_condition ? false : true);
			let do_show_info = (info_condition ? false : true);

			let num_weight_total = 0;
			let arr_first_object_sub = false;
			
			for (let i = 0, len = arr_object_sub_dot.object_sub_ids.length; i < len; i++) {
				
				const object_sub_id = arr_object_sub_dot.object_sub_ids[i];
				
				if (!object_sub_id) {
					continue;
				}
				
				const arr_object_sub = arr_data.object_subs[object_sub_id];
				const arr_object_sub_style = arr_object_sub.style_inherit;

				if (!do_override_color && color_dot && dot_color_condition && arr_object_sub_style.conditions && arr_object_sub_style.conditions[dot_color_condition] !== undefined) {
					do_override_color = true;
				}
				
				let is_condition = false;
				let str_color_key = null;
				let str_color_value = null;
					
				if (do_override_color) { // Override colour
					str_color_value = color_override;
					str_color_key = 'c_'+color_override;
				} else if (arr_object_sub_style.color) { // Custom colour
					is_condition = true;
				} else {
					str_color_key = 'c_'+arr_object_sub.object_sub_details_id;
					if (arr_assets[str_color_key] === undefined) {
						const arr_legend = arr_data.legend.object_subs[arr_object_sub.object_sub_details_id];
						const arr_color = arr_legend.color;
						str_color_value = 'rgb('+arr_color.r+','+arr_color.g+','+arr_color.b+')';
					}
				}
				
				let len_j = 1;
				if (is_condition) {
					len_j = arr_object_sub.conditions.list.length;
				}
				
				for (let j = 0; j < len_j; j++) {
					
					if (is_condition) {
						
						str_color_value = arr_object_sub.conditions.list[j].color;
						
						if (!str_color_value) {
							continue;
						}
						
						str_color_key = 'c_'+str_color_value;
					}
						
					let arr_color_group = arr_assets[str_color_key];
					
					if (arr_color_group === undefined) {
												
						arr_assets[str_color_key] = {weight: 0, color: str_color_value};
						arr_color_group = arr_assets[str_color_key];
						
						arr_dot_colors.push(arr_color_group);
						str_identifier += str_color_key;
						
						arr_assets_touched[str_color_key] = true;
					} else if (arr_assets_touched[str_color_key] === undefined) {
					
						arr_color_group.weight = 0;
						arr_dot_colors.push(arr_color_group);
						str_identifier += str_color_key;
						
						arr_assets_touched[str_color_key] = true;
					}
					
					if (is_weighted) {
						
						let num_weight = 1;
						
						if (is_condition) {
							num_weight = arr_object_sub.conditions.list[j].weight;
						} else if (arr_object_sub_style.weight !== null) {
							num_weight = arr_object_sub_style.weight;
						}
						
						arr_color_group.weight += num_weight;
						num_weight_total += num_weight;
					} else {
						
						arr_color_group.weight++;
						num_weight_total++;
					}
				}
				
				if (arr_object_sub_style.icon) {
					
					for (let j = 0, len_j = arr_object_sub.conditions.list.length; j < len_j; j++) {
						
						const str_icon_value = arr_object_sub.conditions.list[j].icon;
						
						if (!str_icon_value) {
							continue;
						}
						
						const str_icon_key = 'i_'+str_icon_value;
						let arr_icon_group = arr_assets[str_icon_key];
						
						if (arr_icon_group === undefined) {
							
							arr_assets[str_icon_key] = {weight: 0, resource: str_icon_value};
							arr_icon_group = arr_assets[str_icon_key];
							arr_dot_icons.push(arr_icon_group);
							
							arr_assets_touched[str_icon_key] = true;
						} else if (arr_assets_touched[str_icon_key] === undefined) {
						
							arr_icon_group.weight = 0;
							arr_dot_icons.push(arr_icon_group);
							
							arr_assets_touched[str_icon_key] = true;
						}

						if (is_weighted) {
							arr_icon_group.weight += arr_object_sub.conditions.list[j].weight;
						} else {
							arr_icon_group.weight++;
						}
					}
				}
				
				if (location_condition && arr_object_sub_style.conditions && arr_object_sub_style.conditions[location_condition] !== undefined) {
					do_show_location = true;
				}
				if (info_condition && arr_object_sub_style.conditions && arr_object_sub_style.conditions[info_condition] !== undefined) {
					do_show_info = true;
				}
				
				if (svg_style !== false && arr_object_sub_style.conditions) {
					
					for (const str_identifier_condition in arr_object_sub_style.conditions) {
						
						const arr_condition = arr_data.legend.conditions[str_identifier_condition];
						
						if (!arr_condition.label) {
							continue;
						}
						
						arr_conditions[str_identifier_condition] = true;
					}
				}

				if (!arr_first_object_sub) {
					arr_first_object_sub = arr_object_sub;
				}
			}
			
			if (arr_dot_icons.length > 1) {
				arr_dot_icons.sort(function(a, b) { return b.weight - a.weight }); // Order by size, descending
			}
			
			if (svg_style !== false) {
				str_svg_classes += ' '+Object.keys(arr_conditions).join(' ');
			}
			
			if (do_show_location && arr_object_sub_dot.weight < threshold_location) {
				do_show_location = false;
			}

			if (arr_object_sub_dot.identifier === str_identifier) {
					
				const elm = arr_object_sub_dot.elm;
				const arr_location = arr_object_sub_dot.arr_location;
				const elm_location = arr_location.elm;
				
				if (!arr_object_sub_dot.is_added) {
					
					if (display == DISPLAY_PIXEL) {
						elm_plot_dots.addChild(elm);
					} else {
						fragment_plot_dots.appendChild(elm);
					}
				}
							
				if (display == DISPLAY_PIXEL) {
					
					elm.visible = true;
					elm.position.x = Math.floor(x_origin - pos_offset_x);
					elm.position.y = Math.floor(y_origin - pos_offset_y);
					do_render_dots = true;
					
					if (show_location) {
						
						let num_offset_origin = 0;
						
						if (offset_location < 0) {
							num_offset_origin = (-(num_size_radius + width_dot_stroke) + offset_location - (arr_location.height / 2));
						} else if (offset_location > 0) {
							num_offset_origin = (num_size_radius + width_dot_stroke + offset_location + (arr_location.height / 2));
						}
						
						arr_location.y_offset_origin = num_offset_origin;
						arr_location.x_offset_element = -(elm_location.width / 2);
						arr_location.y_offset_element = -(elm_location.height / 2);
						
						if (arr_location.elm_line !== false) {
							
							moveDotLocation(arr_object_sub_dot, arr_location.x_offset, arr_location.y_offset);
						} else {
							
							elm_location.position.x = Math.floor((x_origin - pos_offset_x) + arr_location.x_offset_element);
							elm_location.position.y = Math.floor((y_origin - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset_element);
						}
					}
					
					if (do_show_info && !arr_object_sub_dot.info_show) {
						count_info_show_object_sub_dots++;
					}
					arr_object_sub_dot.info_show = do_show_info;
				} else {
					
					elm.dataset.visible = 1;
					elm.setAttribute('class', str_svg_classes);
					elm.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor(y_origin - pos_offset_y)+')');
					
					if (show_location) {
						
						let num_offset_origin = 0;
						
						if (offset_location < 0) {
							num_offset_origin = (-(num_size_radius + width_dot_stroke) + offset_location - (arr_location.height / 2));
						} else if (offset_location > 0) {
							num_offset_origin = (num_size_radius + width_dot_stroke + offset_location + (arr_location.height / 2));
						}
						
						arr_location.y_offset_origin = num_offset_origin;
						arr_location.x_offset_element = 0;
						arr_location.y_offset_element = (arr_location.height / 2);

						if (arr_location.elm_line !== false) {
							
							moveDotLocation(arr_object_sub_dot, arr_location.x_offset, arr_location.y_offset);
						} else {
							
							elm_location.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor((y_origin - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset_element)+')');
						}
					}
				}
			} else {
				
				if (arr_object_sub_dot.is_added && arr_object_sub_dot.identifier !== false) { // Check for dot existance, could have had a 0 size in the past
					elm_plot_dots.removeChild(arr_object_sub_dot.elm);
				}
				
				const has_icon = arr_dot_icons.length;
				const has_colors = (arr_dot_colors.length === 1 || !opacity_dot ? false : true);
				
				let elm_plot = null;
				
				if (display == DISPLAY_PIXEL) {
					
					if (has_colors || has_icon) {
						
						elm_plot = new PIXI.Container();
					}
				
					if (!has_colors) {
									
						const elm_dot = new PIXI.Graphics();
						
						if (width_dot_stroke) {
							elm_dot.lineStyle(width_dot_stroke, GeoUtilities.parseColor(color_dot_stroke), opacity_dot_stroke);
						}
						if (opacity_dot) {
							elm_dot.beginFill(GeoUtilities.parseColor(arr_dot_colors[0].color), opacity_dot);
						}
						if (dot_icon == 'square') {
							const num_width = (num_size + width_dot_stroke);
							const num_offset = Math.floor(-num_width / 2);
							elm_dot.drawRect(num_offset, num_offset, num_width, num_width);
						} else {
							elm_dot.drawCircle(0, 0, (num_size_radius + width_dot_stroke / 2));
						}
						elm_dot.endFill();
						
						if (elm_plot) {
							elm_plot.addChild(elm_dot);
						} else {
							elm_plot = elm_dot;
						}
					} else {
						
						let num_count = 0;
						
						const elm_dot = new PIXI.Graphics();
						if (width_dot_stroke) {
							elm_dot.lineStyle(width_dot_stroke, GeoUtilities.parseColor(color_dot_stroke), opacity_dot_stroke);
						}
						elm_dot.drawCircle(0, 0, (num_size_radius + width_dot_stroke / 2));
						
						elm_plot.addChild(elm_dot);

						for (let i = 0, len = arr_dot_colors.length; i < len; i++) {
									
							const num_start = (num_count / num_weight_total) * 2 * Math.PI;
							num_count += arr_dot_colors[i].weight;
							const num_end = (num_count / num_weight_total) * 2 * Math.PI;
							
							const elm_dot = new PIXI.Graphics();
							elm_dot.beginFill(GeoUtilities.parseColor(arr_dot_colors[i].color), opacity_dot);
							elm_dot.moveTo(0, 0)
								.lineTo(num_size_radius * Math.cos(num_start), num_size_radius * Math.sin(num_start))
								.arc(0, 0, num_size_radius, num_start, num_end, false)
								.lineTo(0, 0);
							elm_dot.endFill();
							
							elm_plot.addChild(elm_dot);
						}
					}
					
					if (has_icon) {
						
						let elms_icon = null;
						let num_height_max = 0;
						let num_width_sum = 0;
						
						for (let i = 0, len = arr_dot_icons.length; i < len; i++) {
							
							const arr_icon_group = arr_dot_icons[i];
							const arr_resource = arr_assets_texture_icons[arr_icon_group.resource];

							const elm_icon = new PIXI.Sprite(arr_resource.texture);
							const num_scale_icon = (arr_resource.width / arr_resource.height);
							
							const num_height_icon = (do_dot_icons_weight ? (num_size * (arr_icon_group.weight / num_weight_total)) : size_dot_icons);
							
							const num_width_icon = num_height_icon * num_scale_icon;
							elm_icon.height = num_height_icon;
							elm_icon.width = num_width_icon;
							
							if (i == 0) { // First icon is largest
								num_height_max = num_height_icon;
							}
							if (i > 0) {
								num_width_sum += spacer_elm_icons;
							}
							elm_icon.position.x = num_width_sum;
							elm_icon.position.y = ((num_height_max - num_height_icon) / 2);
							num_width_sum += num_width_icon;
							
							if (i == 0) {
								
								if (len > 1) {
									
									elms_icon = new PIXI.Container();
									elms_icon.addChild(elm_icon);
								} else {
									
									elms_icon = elm_icon;
								}
							} else {
								
								elms_icon.addChild(elm_icon);
							}
						}
						
						let num_offset = 0;
						
						if (offset_dot_icons == 0) {
							num_offset = -(num_height_max / 2);
						} else if (offset_dot_icons < 0) {
							num_offset = (-(num_size_radius + width_dot_stroke) + offset_dot_icons - num_height_max);
						} else {
							num_offset = (num_size_radius + width_dot_stroke + offset_dot_icons);
						}
						
						elms_icon.position.x = Math.floor(-(num_width_sum / 2));
						elms_icon.position.y = Math.floor(num_offset);
						
						elm_plot.addChild(elms_icon);
					}

					elm_plot.position.x = Math.floor(x_origin - pos_offset_x);
					elm_plot.position.y = Math.floor(y_origin - pos_offset_y);
					
					elm_plot_dots.addChild(elm_plot);
					do_render_dots = true;
					
					if (do_show_info && !arr_object_sub_dot.info_show) {
						count_info_show_object_sub_dots++;
					}
					arr_object_sub_dot.info_show = do_show_info;
									
					if (show_location) {
						
						let elm_location = null;
						
						if (in_predraw) {
							
							elm_location = new PIXI.Text((arr_first_object_sub.location_name ? stripHTMLTags(arr_first_object_sub.location_name) : ''), {fontSize: size_location, fontFamily: font_family, fill: color_location});
							elm_location.alpha = opacity_location;

							elm_plot_locations.addChild(elm_location);
							
							arr_object_sub_dot.arr_location = {elm: elm_location, visible: false, x_offset: 0, y_offset: 0, y_offset_origin: 0, x_offset_element: 0, y_offset_element: 0, width: elm_location.width, height: size_location, matrix: false, algorithm: do_show_location, size_algorithm: num_size, elm_line: false};
							elm_location.arr_object_sub_dot = arr_object_sub_dot;
							
							if (hint_dot === 'location') {
								
								const filter = new PIXI.filters.ColorMatrixFilter();
								/*var matrix = [
									1,0,0,0,0,
									0,1,0,0,0,
									0,0,1,0,0,
									0,0,0,1,0
								];*/
								const matrix = filter.matrix;
								elm_location.filters = [filter];
								
								arr_object_sub_dot.arr_location.matrix = matrix;
							}
						} else {
							
							elm_location = arr_object_sub_dot.arr_location.elm;
						}
						
						const arr_location = arr_object_sub_dot.arr_location;
						
						let num_offset_origin = 0;

						if (offset_location < 0) {
							num_offset_origin = (-(num_size_radius + width_dot_stroke) + offset_location - (arr_location.height / 2));
						} else if (offset_location > 0) {
							num_offset_origin = (num_size_radius + width_dot_stroke + offset_location + (arr_location.height / 2));
						}
						
						arr_location.y_offset_origin = num_offset_origin;
						arr_location.x_offset_element = -(elm_location.width / 2);
						arr_location.y_offset_element = -(elm_location.height / 2);
						
						elm_location.visible = false;
						
						if (arr_location.elm_line !== false) {
							
							moveDotLocation(arr_object_sub_dot, arr_location.x_offset, arr_location.y_offset);
						} else {
							
							elm_location.position.x = Math.floor((x_origin - pos_offset_x) + arr_location.x_offset_element);
							elm_location.position.y = Math.floor((y_origin - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset_element);
						}
					}
				} else {

					if (has_colors || has_icon) {
						
						elm_plot = stage.createElementNS(stage_ns, 'g');
					}
				
					if (!has_colors) {
						
						let elm_dot = null;
						
						if (dot_icon == 'square') {
							
							const num_width = (num_size + width_dot_stroke);
							const num_offset = Math.floor(-num_width / 2);
							
							elm_dot = stage.createElementNS(stage_ns, 'rect');
							elm_dot.setAttribute('x', num_offset);
							elm_dot.setAttribute('y', num_offset);
							elm_dot.setAttribute('width', num_width);
							elm_dot.setAttribute('height', num_width);
						} else {
							
							elm_dot = stage.createElementNS(stage_ns, 'circle');
							elm_dot.setAttribute('cx', 0);
							elm_dot.setAttribute('cy', 0);
							elm_dot.setAttribute('r', (num_size_radius + width_dot_stroke / 2));
						}
						if (opacity_dot) {
							elm_dot.style.fill = arr_dot_colors[0].color;
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

						let num_count = 0;
						
						const elm_circle = stage.createElementNS(stage_ns, 'circle');
						elm_circle.setAttribute('cx', 0);
						elm_circle.setAttribute('cy', 0);
						elm_circle.setAttribute('r', (num_size_radius + width_dot_stroke / 2));
						elm_circle.style.fill = 'none';
						if (width_dot_stroke) {
							elm_circle.style.stroke = color_dot_stroke;
							elm_circle.style.strokeWidth = width_dot_stroke;
							elm_circle.style.strokeOpacity = opacity_dot_stroke;
						}
						elm_plot.appendChild(elm_circle);
						
						for (let i = 0, len = arr_dot_colors.length; i < len; i++) {
							
							const num_start = (num_count / num_weight_total) * 2 * Math.PI;
							num_count += arr_dot_colors[i].weight;
							const num_end = (num_count / num_weight_total) * 2 * Math.PI;
						
							const elm_path = stage.createElementNS(stage_ns, 'path');
							elm_path.setAttribute('d','M '+0+','+0+' L '+(0 + num_size_radius * Math.cos(num_start))+','+(0 + num_size_radius * Math.sin(num_start))+' A '+num_size_radius+','+num_size_radius+' 0 '+(num_end - num_start < Math.PI ? 0 : 1)+',1 '+(0 + num_size_radius * Math.cos(num_end))+','+(0 + num_size_radius * Math.sin(num_end))+' z');
							elm_path.style.fill = arr_dot_colors[i].color;
							elm_path.style.fillOpacity = opacity_dot;
							elm_plot.appendChild(elm_path);
						}
					}
					
					if (has_icon) {
						
						let elms_icon = null;
						let num_height_max = 0; // First icon is largest
						let num_width_sum = 0;
						
						for (let i = 0, len = arr_dot_icons.length; i < len; i++) {
							
							const arr_icon_group = arr_dot_icons[i];
							const arr_resource = ASSETS.getMedia(arr_icon_group.resource);

							const elm_icon = stage.createElementNS(stage_ns, 'image');
							elm_icon.setAttribute('href', arr_resource.resource);
							const num_scale_icon = (arr_resource.width / arr_resource.height);
							
							const num_height_icon = (do_dot_icons_weight ? (num_size * (arr_icon_group.weight / num_weight_total)) : size_dot_icons);
							
							const num_width_icon = num_height_icon * num_scale_icon;
							elm_icon.setAttribute('height', num_height_icon);
							elm_icon.setAttribute('width', num_width_icon);
							
							if (i == 0) { // First icon is largest
								num_height_max = num_height_icon;
							}
							if (i > 0) {
								num_width_sum += spacer_elm_icons;
							}
							elm_icon.setAttribute('x', num_width_sum);
							elm_icon.setAttribute('y', ((num_height_max - num_height_icon) / 2));
							num_width_sum += num_width_icon;
							
							if (i == 0) {
								
								if (len > 1) {
									
									elms_icon = stage.createElementNS(stage_ns, 'g');
									elms_icon.appendChild(elm_icon);
								} else {
									
									elms_icon = elm_icon;
								}
							} else {
								
								elms_icon.appendChild(elm_icon);
							}
						}
						
						let num_offset = 0;
						
						if (offset_dot_icons == 0) {
							num_offset = -(num_height_max / 2);
						} else if (offset_dot_icons < 0) {
							num_offset = (-(num_size_radius + width_dot_stroke) + offset_dot_icons - num_height_max);
						} else {
							num_offset = (num_size_radius + width_dot_stroke + offset_dot_icons);
						}
						
						elms_icon.setAttribute('transform', 'translate('+(-(num_width_sum / 2))+' '+(num_offset)+')');
						
						elm_plot.appendChild(elms_icon);
					}
					
					elm_plot.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor(y_origin - pos_offset_y)+')');
					elm_plot.setAttribute('class', str_svg_classes);
					
					fragment_plot_dots.appendChild(elm_plot);

					if (show_location) {
						
						let elm_location = null;
						
						if (in_predraw) {
							
							elm_location = stage.createElementNS(stage_ns, 'text');
							elm_location.setAttribute('x', 0);
							elm_location.setAttribute('y', 0);
							elm_location.style.fontSize = size_location+'px';
							elm_location.style.fontFamily = font_family;
							elm_location.style.fill = color_location;
							elm_location.style.fillOpacity = opacity_location;
							elm_location.style.textAnchor = 'middle';
							var elm_text = document.createTextNode((arr_first_object_sub.location_name ? stripHTMLTags(arr_first_object_sub.location_name) : ''));
							elm_location.appendChild(elm_text);
							fragment_plot_locations.appendChild(elm_location);
							
							arr_object_sub_dot.arr_location = {elm: elm_location, visible: false, x_offset: 0, y_offset: 0, y_offset_origin: 0, x_offset_element: 0, y_offset_element: 0, width: 0, height: size_location, arr_filter: false, algorithm: do_show_location, size_algorithm: num_size, elm_line: false};
							elm_location.arr_object_sub_dot = arr_object_sub_dot;
							
							//elm_location.setAttribute('x', elm_location.getAttribute('x') - (arr_object_sub_dot.arr_location.width / 2)); // Using text-anchor text is automatically centered 
							//elm_location.setAttribute('y', elm_location.getAttribute('y') - arr_object_sub_dot.arr_location.height); // svg positions the y axis of text to its bottom
							
							if (hint_dot === 'location') {
								
								const id = 'filter_location_'+fragment_plot_locations.children.length;
								
								const elm_filters = stage.createElementNS(stage_ns, 'filter');
								elm_filters.setAttribute('id', id);
								drawer_defs.appendChild(elm_filters);
															
								const matrix = [
									1,0,0,0,0,
									0,1,0,0,0,
									0,0,1,0,0,
									0,0,0,1,0
								];
								const elm_filter = stage.createElementNS(stage_ns, 'feColorMatrix');
								elm_filter.setAttribute('in', 'SourceGraphic');
								elm_filter.setAttribute('type', 'matrix');
								elm_filter.setAttribute('values', matrix.join(' '));
								elm_filters.appendChild(elm_filter);
								
								elm_location.setAttribute('filter', 'url(#'+id+')');
								
								arr_object_sub_dot.arr_location.arr_filter = {func_update: function() {
									elm_filter.setAttribute('values', matrix.join(' '));
								}, matrix: matrix};
							}
						} else {
							
							elm_location = arr_object_sub_dot.arr_location.elm;
						}
						
						const arr_location = arr_object_sub_dot.arr_location;
						
						let num_offset_origin = 0;
						
						if (offset_location < 0) {
							num_offset_origin = (-(num_size_radius + width_dot_stroke) + offset_location - (arr_location.height / 2));
						} else if (offset_location > 0) {
							num_offset_origin = (num_size_radius + width_dot_stroke + offset_location + (arr_location.height / 2));
						}
						
						arr_location.y_offset_origin = num_offset_origin;
						arr_location.x_offset_element = 0;
						arr_location.y_offset_element = (arr_location.height / 2);
						
						elm_location.dataset.visible = 0;
						
						if (arr_location.elm_line !== false) {
							
							moveDotLocation(arr_object_sub_dot, arr_location.x_offset, arr_location.y_offset);
						} else {
							
							elm_location.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor((y_origin - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset_element)+')');
						}
					}
				}
			
				arr_object_sub_dot.elm = elm_plot;
			}
			
			if (show_location) {
				
				const arr_location = arr_object_sub_dot.arr_location;
				const elm_location = arr_location.elm;
				
				const visible = elm_location.visible;
				
				arr_location.visible = do_show_location;
				
				if (display == DISPLAY_PIXEL) {
					
					elm_location.visible = do_show_location;
					if (arr_location.elm_line !== false) {
						arr_location.elm_line.visible = do_show_location;
					}
					if (elm_location.visible != visible) {
						do_render_locations = true;
					}
				} else {
					
					elm_location.dataset.visible = (do_show_location ? 1 : 0);
					if (arr_location.elm_line !== false) {
						arr_location.elm_line.dataset.visible = (do_show_location ? 1 : 0);
					}
				}
			}
		}
		
		if (str_identifier !== false && arr_object_sub_dot.identifier !== str_identifier) {
			
			if (display == DISPLAY_PIXEL) {
				
			} else {
				arr_object_sub_dot.elm.dataset.locate_identifier = arr_object_sub_dot.locate_identifier;
			}
		}
		
		arr_object_sub_dot.identifier = str_identifier;
		
		const has_geometry = show_geometry && !(arr_object_sub_dot.arr_geometry_plotable.length == 3 && arr_object_sub_dot.arr_geometry_plotable[0] == 'Point');

		if (has_geometry) {
			
			let str_svg_classes = 'geometry';
			let arr_conditions = {};
			
			let color_style_geometry = color_geometry;
			let opacity_style_geometry = opacity_geometry;
			let color_style_geometry_stroke = color_geometry_stroke;
			let opacity_style_geometry_stroke = opacity_geometry_stroke;
			
			for (let i = 0, len = arr_object_sub_dot.object_sub_ids.length; i < len; i++) {
				
				const object_sub_id = arr_object_sub_dot.object_sub_ids[i];
				
				if (!object_sub_id) {
					continue;
				}
				
				const arr_object_sub = arr_data.object_subs[object_sub_id];
				const arr_object_sub_style = arr_object_sub.style_inherit;
				
				if (arr_object_sub_style.geometry_color) {
					color_style_geometry = arr_object_sub_style.geometry_color;
				}
				if (arr_object_sub_style.geometry_opacity != null) {
					opacity_style_geometry = arr_object_sub_style.geometry_opacity;
				}
				if (arr_object_sub_style.geometry_stroke_color) {
					color_style_geometry_stroke = arr_object_sub_style.geometry_stroke_color;
				}
				if (arr_object_sub_style.geometry_stroke_opacity != null) {
					opacity_style_geometry_stroke = arr_object_sub_style.geometry_stroke_opacity;
				}
				
				if (svg_style !== false && arr_object_sub_style.conditions) {
					
					for (const str_identifier_condition in arr_object_sub_style.conditions) {
						
						const arr_condition = arr_data.legend.conditions[str_identifier_condition];
						
						if (!arr_condition.label) {
							continue;
						}
						
						arr_conditions[str_identifier_condition] = true;
					}
				}
			}
			
			if (svg_style !== false) {
				str_svg_classes += ' '+Object.keys(arr_conditions).join(' ');
			}
			
			const str_identifier_geometry = cur_zoom;
			
			if (arr_object_sub_dot.identifier_geometry === str_identifier_geometry) {
				
				const elm_geometry = arr_object_sub_dot.elm_geometry;

				if (!arr_object_sub_dot.is_added) {
					
					if (display == DISPLAY_PIXEL) {
						elm_plot_geometry.addChild(elm_geometry);
					} else {
						fragment_plot_geometry.appendChild(elm_geometry);
					}
				}
							
				if (display == DISPLAY_PIXEL) {
					
					elm_geometry.visible = true;
					elm_geometry.position.x = Math.floor(x_origin - pos_offset_x);
					elm_geometry.position.y = Math.floor(y_origin - pos_offset_y);
					do_render_geometry = true;
				} else {
					
					elm_geometry.dataset.visible = 1;
					elm_geometry.setAttribute('class', str_svg_classes);
					elm_geometry.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor(y_origin - pos_offset_y)+')');
				}
			} else {
				
				if (arr_object_sub_dot.is_added) {
					elm_plot_geometry.removeChild(arr_object_sub_dot.elm_geometry);
				}
				
				let elm_geometry = null;
				const arr_geometry_plotable = arr_object_sub_dot.arr_geometry_plotable;
				const geometry_center_x = arr_object_sub_dot.xy_geometry_center.x;
				const geometry_center_y = arr_object_sub_dot.xy_geometry_center.y;
				let i_geo = 0;
				const len_i_geo = arr_geometry_plotable.length;

				if (display == DISPLAY_PIXEL) {
					
					elm_geometry = new PIXI.Container();
					
					let elm_geo = null;
					
					while (i_geo < len_i_geo) {

						switch (arr_geometry_plotable[i_geo]) {
							case 'Group':
								
								i_geo++;
								
								break;
							case 'Point':
								
								i_geo++;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number') {
																		
									elm_geo = new PIXI.Graphics();
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
							
								const is_line_polygon = (arr_geometry_plotable[i_geo] === 'islinepolygon' ? true : false);
								
								if (is_line_polygon) {
									i_geo++;
								}
								
								elm_geo = new PIXI.Graphics();
								if (!is_line_polygon || (is_line_polygon && width_geometry_stroke)) {
									elm_geo.lineStyle((width_geometry_stroke ? width_geometry_stroke : 1), GeoUtilities.parseColor((color_style_geometry_stroke ? color_style_geometry_stroke : color_style_geometry)), opacity_style_geometry_stroke);
								}												
								elm_geo.moveTo((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x, (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
								
								i_geo += 2;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number' || arr_geometry_plotable[i_geo] === null) {
									
									if (arr_geometry_plotable[i_geo] === null) {
								
										i_geo++;
										
										elm_geo.moveTo((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x, (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
										
										i_geo += 2;
										continue;
									}
													
									elm_geo.lineTo((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x, (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
									
									i_geo += 2;
								}

								elm_geometry.addChild(elm_geo);
								
								break;
							case 'Polygon':
							
								i_geo++;
							
								const has_line = (arr_geometry_plotable[i_geo] === 'noline' ? false : true);
								
								if (!has_line) {
									i_geo++;
								}
																	
								elm_geo = new PIXI.Graphics();
								if (opacity_style_geometry) {
									elm_geo.beginFill(GeoUtilities.parseColor(color_style_geometry), opacity_style_geometry);
								}
								if (width_geometry_stroke && has_line) {
									elm_geo.lineStyle(width_geometry_stroke, GeoUtilities.parseColor(color_style_geometry_stroke), opacity_style_geometry_stroke);
								}
	
								let str_ring = arr_geometry_plotable[i_geo];

								while (str_ring === 'ring' || str_ring === 'hole') {
									
									i_geo++;

									const arr_points = [];
									let i = 0;

									while (typeof arr_geometry_plotable[i_geo] === 'number') {

										arr_points[i] = (arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x;
										arr_points[i+1] = (arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y;
											
										i_geo += 2;
										i += 2;
									}
									
									if (str_ring === 'hole') {
										elm_geo.beginHole();
									}
									
									elm_geo.drawPolygon(arr_points);
									
									if (str_ring === 'hole') {
										elm_geo.endHole();
									}
									
									str_ring = arr_geometry_plotable[i_geo];
								}
								
								elm_geo.endFill();
								elm_geometry.addChild(elm_geo);
								
								break;
						}
					}
					
					elm_plot_geometry.addChild(elm_geometry);

					elm_geometry.position.x = Math.floor(x_origin - pos_offset_x);
					elm_geometry.position.y = Math.floor(y_origin - pos_offset_y);
					do_render_geometry = true;
				} else {
					
					elm_geometry = stage.createElementNS(stage_ns, 'g');
					fragment_plot_geometry.appendChild(elm_geometry);
					
					let elm_geo = null;
					let str_path = '';
						
					while (i_geo < len_i_geo) {

						switch (arr_geometry_plotable[i_geo]) {
							case 'Group':
								
								i_geo++;
								
								break;
							case 'Point':
								
								i_geo++;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number') {
									
									elm_geo = stage.createElementNS(stage_ns, 'circle');
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
								
								const is_line_polygon = (arr_geometry_plotable[i_geo] === 'islinepolygon' ? true : false);
								
								if (is_line_polygon) {
									i_geo++;
								}
								
								elm_geo = stage.createElementNS(stage_ns, 'path');
								if (!is_line_polygon || (is_line_polygon && width_geometry_stroke)) {
									elm_geo.style.stroke = (color_style_geometry_stroke ? color_style_geometry_stroke : color_style_geometry);
									elm_geo.style.strokeWidth = (width_geometry_stroke ? width_geometry_stroke : 1);
									elm_geo.style.strokeOpacity = opacity_style_geometry_stroke;
								}
								elm_geo.style.fill = 'none';				
								
								str_path = '';
								
								str_path += 'M '+((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x)+' '+((arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);					
								
								i_geo += 2;
								
								while (typeof arr_geometry_plotable[i_geo] === 'number' || arr_geometry_plotable[i_geo] === null) {
									
									if (arr_geometry_plotable[i_geo] === null) {
								
										i_geo++;
										
										str_path += 'M '+((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x)+' '+((arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);
										
										i_geo += 2;
										continue;
									}
																		
									str_path += 'L '+((arr_geometry_plotable[i_geo] / calc_plot_point) - geometry_center_x)+' '+((arr_geometry_plotable[i_geo+1] / calc_plot_point) - geometry_center_y);																		
									
									i_geo += 2;
								}

								elm_geo.setAttribute('d', str_path);
								elm_geometry.appendChild(elm_geo);
								
								break;
							case 'Polygon':
							
								i_geo++;
							
								const has_line = (arr_geometry_plotable[i_geo] === 'noline' ? false : true);
								
								if (!has_line) {
									i_geo++;
								}
							
								elm_geo = stage.createElementNS(stage_ns, 'path');
								if (opacity_style_geometry) {
									elm_geo.style.fill = color_style_geometry;
									elm_geo.style.fillOpacity = opacity_style_geometry;
								} else {
									elm_geo.style.fill = 'none';
								}
								if (width_geometry_stroke && has_line) {
									elm_geo.style.stroke = color_style_geometry_stroke;
									elm_geo.style.strokeWidth = width_geometry_stroke;
									elm_geo.style.strokeOpacity = opacity_style_geometry_stroke;
								}
								elm_geo.style.fillRule = 'evenodd';

								str_path = '';
								
								let str_ring = arr_geometry_plotable[i_geo];

								while (str_ring === 'ring' || str_ring === 'hole') {
									
									i_geo++;

									if (str_path !== '') {
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
					
					elm_geometry.setAttribute('class', str_svg_classes);
					elm_geometry.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor(y_origin - pos_offset_y)+')');
				}
				
				arr_object_sub_dot.elm_geometry = elm_geometry;
				arr_object_sub_dot.identifier_geometry = str_identifier_geometry;
			}
		}

		arr_object_sub_dot.is_added = true;
	};
	
	var drawDotHint = function(arr_object_sub_dot) {

		if (hint_dot === 'location') {
			
			let arr_hint = arr_object_sub_dot.arr_hint_queue[0];
			
			const arr_location = arr_object_sub_dot.arr_location;
			const elm_location = arr_location.elm;
			
			if (display == DISPLAY_PIXEL) {
				elm_location.visible = true;
				if (arr_location.elm_line !== false) {
					arr_location.elm_line.visible = true;
				}
			} else {
				elm_location.dataset.visible = 1;
				if (arr_location.elm_line !== false) {
					arr_location.elm_line.dataset.visible = 1;
				}
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
			
				const arr_hint = arr_object_sub_dot.arr_hint_queue;
				
				arr_hint[0][0] = false;
				arr_hint.push(arr_hint.shift());
				
				return;
			}
			
			count_animate_between++;
			
			let elm = null;
			const num_size_radius = (arr_object_sub_dot.arr_settings.size / 2);
			
			if (display == DISPLAY_PIXEL) {
						
				elm = new PIXI.Graphics();

				elm_plot_between.addChild(elm);
			} else {
				
				elm = stage.createElementNS(stage_ns, 'circle');

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
							
			const arr_hint = [false, elm, num_size_radius];
			
			arr_object_sub_dot.arr_hint_queue.push(arr_hint);
		}
	};	
	
	var hoverDot = function (arr_object_sub_dot, do_highlight) {
		
		if (color_highlight === false) {
			return;
		}
		
		if (do_highlight !== false) {

			arr_object_sub_dot.highlight_color = color_highlight;
			arr_object_sub_dot.highlight_class = svg_class_highlight;
		} else {
			
			arr_object_sub_dot.highlight_color = false;
			arr_object_sub_dot.highlight_class = false;
		}
		
		if (arr_object_sub_dot.is_added) {
			drawDot(arr_object_sub_dot);
		}
	};
	
	var moveDotLocation = function (arr_object_sub_dot, x, y, is_absolute) {
		
		const arr_location = arr_object_sub_dot.arr_location;
		const elm_location = arr_location.elm;
		
		const pos_origin_x = arr_object_sub_dot.xy_geometry_center.x;
		const pos_origin_y = arr_object_sub_dot.xy_geometry_center.y;
				
		if (is_absolute === true) {
			arr_location.x_offset = (x - pos_origin_x);
			arr_location.y_offset = (y - arr_location.y_offset_origin - pos_origin_y);
		} else {
			arr_location.x_offset = x;
			arr_location.y_offset = y;
		}
		
		if (display == DISPLAY_PIXEL) {
			
			elm_location.position.x = Math.floor((pos_origin_x - pos_offset_x) + arr_location.x_offset + arr_location.x_offset_element);
			elm_location.position.y = Math.floor((pos_origin_y - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset + arr_location.y_offset_element);
		} else {
			
			elm_location.setAttribute('transform', 'translate('+Math.floor((pos_origin_x - pos_offset_x) + arr_location.x_offset)+' '+Math.floor((pos_origin_y - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset + arr_location.y_offset_element)+')');
		}
		
		let do_show = (arr_location.x_offset != 0 || arr_location.y_offset != 0);
				
		if (do_show) {
			
			let num_offset_y = 0;
			if (pos_origin_y <= pos_origin_y + arr_location.y_offset_origin + arr_location.y_offset) {
				num_offset_y = -Math.abs(offset_location) - (arr_location.height / 2);
			} else {
				num_offset_y = Math.abs(offset_location) + (arr_location.height / 2);
			}
			
			let elm_line = false;
			
			if (arr_location.elm_line === false) {

				if (display == DISPLAY_PIXEL) {
					
					elm_line = new PIXI.Graphics();
					elm_plot_locations.addChild(elm_line);
					
					elm_line.visible = arr_location.visible;
				} else {
					
					elm_line = stage.createElementNS(stage_ns, 'line');
					elm_line.setAttribute('x1', (pos_origin_x - pos_offset_x));
					elm_line.setAttribute('y1', (pos_origin_y - pos_offset_y));
					elm_line.setAttribute('x2', 0);
					elm_line.setAttribute('y2', 0);
					elm_line.style.stroke = color_location;
					elm_line.style.strokeOpacity = opacity_location;
					elm_line.style.strokeWidth = width_location_line;
					elm_line.style.strokeLinecap = 'round';
					elm_plot_locations.appendChild(elm_line);
					
					elm_line.dataset.visible = (arr_location.visible ? 1 : 0);
				}
				
				arr_location.elm_line = elm_line;
			} else {
				
				elm_line = arr_location.elm_line;
				
				if (display == DISPLAY_PIXEL) {
					elm_line.clear();
				}
			}
			
			if (display == DISPLAY_PIXEL) {
				
				elm_line.lineStyle({width: width_location_line, color: GeoUtilities.parseColor(color_location), alpha: opacity_location, cap: PIXI.LINE_CAP.ROUND});
				elm_line.moveTo(Math.floor(pos_origin_x - pos_offset_x), Math.floor(pos_origin_y - pos_offset_y));
				elm_line.lineTo(Math.floor((pos_origin_x - pos_offset_x) + arr_location.x_offset), Math.floor((pos_origin_y - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset + num_offset_y));
				
				do_render_locations = true;
			} else {

				elm_line.setAttribute('x1', (pos_origin_x - pos_offset_x));
				elm_line.setAttribute('y1', (pos_origin_y - pos_offset_y));
				elm_line.setAttribute('x2', (pos_origin_x - pos_offset_x) + arr_location.x_offset);				
				elm_line.setAttribute('y2', (pos_origin_y - pos_offset_y) + arr_location.y_offset_origin + arr_location.y_offset + num_offset_y);
			}
		}

		if (!do_show && arr_location.elm_line !== false) {
			
			if (display == DISPLAY_PIXEL) {
				
				elm_plot_locations.removeChild(arr_location.elm_line);
				
				do_render_locations = true;
			} else {
				
				elm_plot_locations.removeChild(arr_location.elm_line);
			}
			
			arr_location.elm_line = false;
		}
	};
	
	var drawLine = function(arr_object_sub_line, arr_object_sub_lines_locate, offset, count) {
		
		const arr_settings = arr_object_sub_line.arr_settings;
		
		let num_size = arr_object_sub_line.weight/3;
		num_size = Math.ceil(num_size > width_line_max ? width_line_max : (num_size < width_line_min ? width_line_min : num_size));
		
		arr_settings.size = num_size;
		
		let color = false;
		let do_show_info = (info_condition ? false : true);
		let str_svg_classes = 'line';
		
		if (color_line) { // Override colour
			color = color_line;
		}
		
		if (!color || info_condition || svg_style !== false) {
			
			let arr_conditions = {};
			
			const arr_connect_object_sub_ids = arr_object_sub_line.connect_object_sub_ids;
			
			for (let i = arr_connect_object_sub_ids.length-1; i >= 0; i--) {
				
				const connect_object_sub_id = arr_connect_object_sub_ids[i];

				if (!connect_object_sub_id) {
					continue;
				}
					
				const arr_object_sub_style = arr_data.object_subs[connect_object_sub_id].style_inherit;
				
				if (!color) {
					
					color = arr_object_sub_style.color;
					
					if (typeof color == 'object') { // Select last color color contains multiple values
						color = color[color.length-1];
					}
				}
				if (info_condition && !do_show_info && arr_object_sub_style.conditions) {
					
					do_show_info = (arr_object_sub_style.conditions[info_condition] !== undefined ? true : false);
				}
				if (svg_style !== false && arr_object_sub_style.conditions) {
					
					for (const str_identifier_condition in arr_object_sub_style.conditions) {
						
						const arr_condition = arr_data.legend.conditions[str_identifier_condition];
						
						if (!arr_condition.label) {
							continue;
						}
						
						arr_conditions[str_identifier_condition] = true;
					}
				}
			}
						
			if (svg_style !== false) {
				
				for (let i = 0, len = arr_object_sub_line.object_sub_ids.length-1; i >= 0; i--) {
						
					const object_sub_id = arr_object_sub_line.object_sub_ids[i];
					
					if (!object_sub_id) {
						continue;
					}
					
					const arr_object_sub_style = arr_data.object_subs[object_sub_id].style_inherit;
					
					if (arr_object_sub_style.conditions) {
						
						for (const str_identifier_condition in arr_object_sub_style.conditions) {
							
							const arr_condition = arr_data.legend.conditions[str_identifier_condition];
							
							if (!arr_condition.label) {
								continue;
							}
							
							arr_conditions[str_identifier_condition] = true;
						}
					}
				}
				
				str_svg_classes += ' '+Object.keys(arr_conditions).join(' ');
			}
		}
		
		if (!color) { // Default colour
			
			const arr_legend = arr_data.legend.object_subs[arr_object_sub_line.connect_object_sub_details_id];
			const arr_color = arr_legend.color;
			
			color = 'rgb('+arr_color.r+','+arr_color.g+','+arr_color.b+')';
		}
				
		const alternate = (mode != MODE_MOVE && count % 2);
		
		const arr_path = arr_object_sub_lines_locate.arr_path;
		
		const x_origin = arr_path[0].x;
		const y_origin = arr_path[0].y;
		
		let str_identifier = (mode != MODE_MOVE ? cur_zoom+''+offset : '')+''+num_size+''+color+''+(alternate ? '1' : '0');
		
		if (mode == MODE_MOVE && move_retain === 'all' && arr_object_sub_line.identifier === str_identifier && arr_object_sub_line.arr_move.status !== false && !redraw) { // Main instance is already running in move_retain mode, force an additional new instance
			str_identifier = false;
		}
		
		let elm = null;
		let arr_move = null;
		
		if (str_identifier !== false && arr_object_sub_line.identifier === str_identifier) {
				
			elm = arr_object_sub_line.elm;
			
			if (!arr_object_sub_line.is_added) {
				
				if (display == DISPLAY_PIXEL) {
				
					if (mode != MODE_MOVE) {

						elm_plot_lines.addChild(elm);
					}
				} else {
					
					fragment_plot_lines.appendChild(elm);
				}
			}
						
			if (display == DISPLAY_PIXEL) {
				
				if (mode == MODE_MOVE) {
					
					arr_move = arr_object_sub_line.arr_move;

					if (do_show_info && !arr_object_sub_line.info_show) {
						count_info_show_object_sub_lines++;
						arr_object_sub_lines_locate.count_info_show++;
					}
					arr_object_sub_line.info_show = do_show_info;

					if (redraw === REDRAW_RESET || (!redraw && !arr_object_sub_line.is_active)) {
						
						arr_move.percentage = 0;
						arr_move.status = 1;
						arr_move.delay = offset*1000;
						arr_move.cur_delay = offset*1000;
						
						if (arr_move.cur_delay) { // The move instance has to wait a bit, reset the visibility while waiting 
							
							arr_move.elm.alpha = 0;
							
							for (let i = 0; i < length_move_warp; i++) {
								
								arr_move.arr_elm_warp[i].alpha = 0;
							}
						}
						
						arr_object_sub_line.is_active = true;
					}
					
					if (arr_move.key === false) {
						arr_move.key = arr_animate_move.push(arr_move);
					}
				} else {
					
					elm.visible = true;
					elm.position.x = Math.floor(x_origin - pos_offset_x);
					elm.position.y = Math.floor(y_origin - pos_offset_y);
					do_render_lines = true;
				}
			} else {
				
				elm.dataset.visible = 1;
				elm.setAttribute('class', str_svg_classes);
				elm.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor(y_origin - pos_offset_y)+')');
			}
		} else {
			
			if (mode != MODE_MOVE) {
				
				if (alternate) { // Alternate colours when stacking them
					const arr_color = GeoUtilities.colorToBrightColor(color, 22);
					color = 'rgb('+arr_color.r+','+arr_color.g+','+arr_color.b+')';
				}
			}
		
			if (display == DISPLAY_PIXEL) {
				
				const hex_color = GeoUtilities.parseColor(color);
				
				if (mode == MODE_MOVE) {
					
					arr_move = arr_object_sub_line.arr_move;
					
					if (arr_object_sub_line.is_added && str_identifier !== false) { // Identifier has changed, require full redraw
						
						arr_move.elm_container[0].removeChild(arr_move.elm);
									
						for (let i = 0; i < length_move_warp; i++) {
							arr_move.elm_container[1].removeChild(arr_move.arr_elm_warp[i]);
						}
						
						arr_move.arr_elm_warp = [];
					}
					
					const str_identifier_texture = num_size+'-'+hex_color;
					const arr_elm_particles = (str_identifier === false ? arr_elm_plot_line_particles_temp : arr_elm_plot_line_particles);
					let elm_container = arr_elm_particles[str_identifier_texture];
					
					if (!elm_container || elm_container[0].children.length >= size_max_elm_container) {
						
						if (str_identifier === false) {
							
							if (elm_container) { // Container is full
								
								elm_container = null;
								let num_count = 0;
								
								while (1) {
									
									const str_identifier_texture_temp = str_identifier_texture+'-'+num_count;
									
									if (!arr_elm_particles[str_identifier_texture_temp]) { // Store the full identifier_texture container to identifier_texture_temp, freeing up identifier_texture for a new container

										arr_elm_particles[str_identifier_texture_temp] = arr_elm_particles[str_identifier_texture];
										break;
									} else if (arr_elm_particles[str_identifier_texture_temp][0].children.length < (size_max_elm_container / 10)) { // Use a previous identifier_texture_temp container when there is enough room again (at least 90% space), replace the full identifier_texture container with the now usable identifier_texture_temp container
										
										elm_container = arr_elm_particles[str_identifier_texture_temp];
										arr_elm_particles[str_identifier_texture_temp] = arr_elm_particles[str_identifier_texture];
										arr_elm_particles[str_identifier_texture] = elm_container;
										break;
									}
									
									num_count++;
								}
							}
						}
						
						if (str_identifier !== false || !elm_container) {
								
							arr_elm_particles[str_identifier_texture] = [];
							
							if (move_glow) {
								arr_elm_particles[str_identifier_texture][0] = new PIXI.ParticleContainer(size_max_elm_container / length_move_warp, {position: true, alpha: true});
								arr_elm_particles[str_identifier_texture][1] = new PIXI.ParticleContainer(size_max_elm_container, {position: true, alpha: true});
								
								elm_container = arr_elm_particles[str_identifier_texture];
								elm_plot_lines.addChild(elm_container[1]);
								elm_plot_lines.addChild(elm_container[0]);
							} else {
								arr_elm_particles[str_identifier_texture][0] = arr_elm_particles[str_identifier_texture][1] = new PIXI.ParticleContainer(size_max_elm_container, {position: true, alpha: true});
								
								elm_container = arr_elm_particles[str_identifier_texture];
								elm_plot_lines.addChild(elm_container[0]);
							}
						}
					}
					
					let arr_texture = arr_assets_texture_line_dots[str_identifier_texture];
					const r = (num_size / 2);
					
					if (!arr_texture) {
						
						arr_texture = [];
						
						elm = new PIXI.Graphics();
						elm.beginFill(hex_color, 1);
						elm.drawCircle(r, r, r);
						elm.endFill();
						
						if (move_glow) {
							
							const num_size_glow = r*3;
							
							const canvas = document.createElement('canvas');
							canvas.width = num_size_glow*2;
							canvas.height = num_size_glow*2;
							const context = canvas.getContext('2d');
							
							const arr_color = parseCSSColor(color);
							
							const gradient = context.createRadialGradient(num_size_glow, num_size_glow, r, num_size_glow, num_size_glow, num_size_glow);
							gradient.addColorStop(0, 'rgba('+arr_color.r+', '+arr_color.g+', '+arr_color.b+', 0.4)');
							gradient.addColorStop(1, 'rgba('+arr_color.r+', '+arr_color.g+', '+arr_color.b+', 0)');
							context.beginPath();
							context.fillStyle = gradient;
							context.arc(num_size_glow, num_size_glow, num_size_glow, 0, 360);
							context.fill();
							context.beginPath();
							context.fillStyle = 'rgba('+arr_color.r+', '+arr_color.g+', '+arr_color.b+', 1)';
							context.arc(num_size_glow, num_size_glow, r, 0, 360);
							context.fill();
							
							arr_texture[0] = PIXI.Texture.from(canvas);
							if (use_best_quality) {
								arr_texture[1] = elm.generateCanvasTexture();
							} else {
								arr_texture[1] = renderer_activity.generateTexture(elm);
							}
						} else {

							if (use_best_quality) {
								arr_texture[0] = elm.generateCanvasTexture();
							} else {
								arr_texture[0] = renderer_activity.generateTexture(elm);
							}
							arr_texture[1] = arr_texture[0];
						}
											
						arr_assets_texture_line_dots[str_identifier_texture] = arr_texture;
					}
					
					elm = new PIXI.Sprite(arr_texture[0]);
					
					/*var glow = new GlowFilter(renderer_activity.width, renderer_activity.height, 15, 2, 2, hex_color, 0.5);
					elm.filters = [glow];*/
					
					elm.position.x = Math.floor(x_origin - pos_offset_x);
					elm.position.y = Math.floor(y_origin - pos_offset_y);
					elm.anchor.x = 0.5;
					elm.anchor.y = 0.5;
					
					elm_container[0].addChild(elm);
					
					if (move_retain === 'all') {
						
						if (str_identifier === false) { // Spawn additional instance replacing the main instance, reset the main instance

							const arr_move_instance_new = {arr_object_sub_lines_locate: arr_move.arr_object_sub_lines_locate, arr_object_sub_line: arr_move.arr_object_sub_line, elm: false, elm_container: false, key: false, arr_elm_warp: [], start: arr_move.start, duration: arr_move.duration, delay: 0, cur_delay: 0, percentage: arr_move.percentage, status: arr_move.status};
							
							// Check and cleanup old moveing instances from previous selections, prevent flooding 
							const len = arr_object_sub_line.arr_move_queue.length;
							
							if (len >= 2) { // Maximum of 2 additional instances
							
								const arr_move_queue = arr_object_sub_line.arr_move_queue;
								
								let num_count = 1;
								
								for (let i = len-1; i >= 0; i--) {
									
									const arr_move_instance = arr_move_queue[i];
									
									if (arr_move_instance.key === false || num_count >= 2) {
										
										arr_move_queue.splice(i, 1);
										arr_move_instance.key = false;
									} else {
										
										num_count++;
									}
								}
							}
							
							arr_object_sub_line.arr_move_queue.push(arr_move_instance_new);
							
							// Reset main instance
							arr_move.percentage = 0;
							arr_move.status = 1;
							arr_move.delay = offset*1000;
							arr_move.cur_delay = offset*1000;
							
							arr_object_sub_line.is_active = true;
							
							// Continue with spawned instance
							arr_move = arr_move_instance_new;
						}
					}
					
					arr_move.elm = elm;
					arr_move.elm_container = elm_container;
					
					for (let i = 1; i < (length_move_warp+1); i++) {
						
						const elm_warp = new PIXI.Sprite(arr_texture[1]);
						elm_warp.anchor.x = 0.5;
						elm_warp.anchor.y = 0.5;
						elm_warp.scale.x = 1 - (i * (0.3/(length_move_warp+1)));
						elm_warp.scale.y = 1 - (i * (0.3/(length_move_warp+1)));
						
						elm_container[1].addChild(elm_warp);
						arr_move.arr_elm_warp.push(elm_warp);
					}
					
					if (str_identifier !== false) {
						
						if (in_predraw || redraw === REDRAW_RESET || (!redraw && !arr_object_sub_line.is_active)) {
							
							arr_move.percentage = 0;
							arr_move.status = 1;
							arr_move.delay = offset*1000;
							arr_move.cur_delay = offset*1000;
							
							arr_object_sub_line.is_active = true;
						}
						
						if (move_unit == 'day') {
							
							const arr_object_subs_line_details = getObjectSubsLineDetails(arr_object_sub_line);
							const dateinta_range_sub = arr_object_subs_line_details.dateinta_range;
							const date_min = DATEPARSER.int2Date(DATEPARSER.dateIntAbsolute2DateInt(dateinta_range_sub.min)).getTime();
							
							const num_time_days = ((DATEPARSER.int2Date(DATEPARSER.dateIntAbsolute2DateInt(dateinta_range_sub.max)).getTime() - date_min) / DATEPARSER.time_day) + 1; // Add one day as one day is absolute minimum (same day)
							let num_duration = num_time_days / speed_move;

							if (duration_move_min && num_duration < duration_move_min) {
								num_duration = duration_move_min;
							} else if (duration_move_max && num_duration > duration_move_max) {
								num_duration = duration_move_max;
							}
							arr_move.duration = num_duration*1000;
							arr_move.start = date_min;
						}
					}
					
					if (arr_move.key === false) {
						arr_move.key = arr_animate_move.push(arr_move);
					}
										
					if (do_show_info && !arr_object_sub_line.info_show) {
						count_info_show_object_sub_lines++;
						arr_object_sub_lines_locate.count_info_show++;
					}
					arr_object_sub_line.info_show = do_show_info;
				} else {
					
					if (arr_object_sub_line.is_added) {
						elm_plot_lines.removeChild(arr_object_sub_line.elm);
					}

					elm = new PIXI.Graphics();
					elm.lineStyle(1, hex_color, 0.8);
					elm.beginFill(hex_color, 0.8);
					
					let x_start = 0;
					let y_start = 0;
										
					for (let i = 1, len = arr_path.length; i < len; i++) {
						
						if (arr_path[i] === null) {
					
							i++;
							x_start = Math.floor(arr_path[i].x - x_origin);
							y_start = Math.floor(arr_path[i].y - y_origin);
							continue;
						}
						
						const x_end = x_start;
						const y_end = y_start;
						x_start = Math.floor(arr_path[i].x - x_origin);
						y_start = Math.floor(arr_path[i].y - y_origin);

						const c1 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, 0.5+offset, 0.25);
						const c2 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, 0.5+offset, 0.75);
						const cc1 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset+num_size-0.5, 0.75);
						const cc2 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset+num_size-0.5, 0.25);
						
						elm.moveTo(x_start, y_start).bezierCurveTo(c1.x, c1.y, c2.x, c2.y, x_end, y_end).bezierCurveTo(cc1.x, cc1.y, cc2.x, cc2.y, x_start, y_start);
					}

					elm.position.x = Math.floor(x_origin - pos_offset_x);
					elm.position.y = Math.floor(y_origin - pos_offset_y);
					
					elm_plot_lines.addChild(elm);
					do_render_lines = true;
				}
			} else {
				
				if (arr_object_sub_line.is_added) {
					elm_plot_lines.removeChild(arr_object_sub_line.elm);
				}
				
				elm = stage.createElementNS(stage_ns, 'path');
				
				let str_svg = '';
				let x_start = 0;
				let y_start = 0;

				for (let i = 1, len = arr_path.length; i < len; i++) {
					
					if (arr_path[i] === null) {
				
						i++;
						x_start = Math.floor(arr_path[i].x - x_origin);
						y_start = Math.floor(arr_path[i].y - y_origin);
						continue;
					}
					
					const x_end = x_start;
					const y_end = y_start;
					x_start = Math.floor(arr_path[i].x - x_origin);
					y_start = Math.floor(arr_path[i].y - y_origin);
					
					if (str_svg !== '') {
						str_svg += ' ';
					}
					
					if (svg_style !== false) {
						
						const c1 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset+(num_size/2), 0.25);
						const c2 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset+(num_size/2), 0.75);

						str_svg += 'M '+x_start+','+y_start+' C '+c1.x+','+c1.y+' '+c2.x+','+c2.y+' '+x_end+','+y_end+'';
					} else {

						const c1 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, 0.5+offset, 0.25);
						const c2 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, 0.5+offset, 0.75);
						const cc1 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset+num_size-0.5, 0.75);
						const cc2 = GeoUtilities.calcPointOffset(x_start, y_start, x_end, y_end, offset+num_size-0.5, 0.25);
						
						str_svg += 'M '+x_start+','+y_start+' C '+c1.x+','+c1.y+' '+c2.x+','+c2.y+' '+x_end+','+y_end+' C '+cc1.x+','+cc1.y+' '+cc2.x+','+cc2.y+' '+x_start+','+y_start+'';
					}
				}

				elm.setAttribute('d', str_svg);
				if (svg_style !== false) {
					elm.style.fill = 'transparent';
					elm.style.stroke = color;
					elm.style.strokeWidth = num_size;
				} else {
					elm.style.fill = color;
					elm.style.stroke = color;
					elm.style.strokeWidth = 1;
				}
				elm.style.opacity = 0.8;
				fragment_plot_lines.appendChild(elm);
				
				elm.setAttribute('transform', 'translate('+Math.floor(x_origin - pos_offset_x)+' '+Math.floor(y_origin - pos_offset_y)+')');
				elm.setAttribute('class', str_svg_classes);
			}
		}
		
		arr_object_sub_line.is_added = true;
		
		if (str_identifier !== false && arr_object_sub_line.identifier !== str_identifier) {
			
			arr_object_sub_line.elm = elm;
			if (mode == MODE_MOVE) {
				arr_object_sub_line.elm_container = arr_move.elm_container;
				arr_object_sub_line.arr_elm_warp = arr_move.arr_elm_warp;
			}
			arr_object_sub_line.identifier = str_identifier;
			
			if (display == DISPLAY_PIXEL) {
			
			} else {
				arr_object_sub_line.elm.dataset.locate_identifier = arr_object_sub_lines_locate.locate_identifier;
				arr_object_sub_line.elm.dataset.connect_identifier = arr_object_sub_line.connect_identifier;
			}
		}
	};
		
	var removeLine = function(arr_object_sub_line) {
		
		if (mode == MODE_MOVE) {
						
			const arr_move = arr_object_sub_line.arr_move;
				
			if (move_retain) {
					
				if (arr_move.key) { // Keep the move instance around until it's finished animating
					return;
				} else { // Remove directly (i.e. it's outside of bounds)
					removeLineInstance(arr_move);
				}
			} else {
					
				arr_move.key = false;
			}
			
			if (arr_object_sub_line.arr_info.is_added) {
				arr_object_sub_line.arr_info.elm.alpha = 0;
			}
		} else {
			
			if (arr_object_sub_line.identifier !== false) {
				
				if (display == DISPLAY_PIXEL) {
					arr_object_sub_line.elm.visible = false;
					do_render_lines = true;
				} else {
					arr_object_sub_line.elm.dataset.visible = 0;
				}
			}
		}
	};
	
	var removeLineInstance = function(arr_move) {
		
		// mode == MODE_MOVE
						
		arr_move.key = false;
			
		const arr_self = arr_move.arr_object_sub_line;
		const is_instance_self = (arr_self.arr_move === arr_move);

		if (is_instance_self) {
			
			if (arr_self.arr_info.is_added) {
				arr_self.arr_info.elm.alpha = 0;
			}
		}

		if (is_instance_self) {
			
			arr_move.elm.alpha = 0;
			
			for (var i = 0; i < length_move_warp; i++) {
				arr_move.arr_elm_warp[i].alpha = 0;
			}
		} else {
			
			arr_move.elm_container[0].removeChild(arr_move.elm);
						
			for (var i = 0; i < length_move_warp; i++) {
				arr_move.elm_container[1].removeChild(arr_move.arr_elm_warp[i]);
			}
		}
	};	

	var displayGeoInfo = function() {
		
		/*var distance = 0,
		longest = 0,
		shortest = 0;
		
		var arr_loop = arr_loop_object_sub_lines.arr_loop;

		for (var i = 0, len = arr_loop.length; i < len; i++) {
			
			var arr_object_sub_lines_locate = arr_loop[i];
						
			var arr_object_sub_dot = arr_object_sub_dots[arr_object_sub_lines_locate.location_geometry];
			var arr_connect_object_sub_dot = arr_object_sub_dots[arr_object_sub_lines_locate.connect_location_geometry];
			
			var arr_loop_loc = arr_object_sub_lines_locate.arr_con;
					
			for (var j = 0, len_j = arr_loop_loc.length; j < len_j; j++) {
				
				var arr_object_sub_line = arr_loop_loc[j];
				
				if (!arr_object_sub_line.count) {
					continue;
				}
				
				var latitude1 = arr_object_sub_dot.xy_geometry_center.x * Math.PI / 180;
				var lonitude1 = arr_object_sub_dot.xy_geometry_center.y * Math.PI / 180;
					
				var latitude2 = arr_connect_object_sub_dot.xy_geometry_center.x * Math.PI / 180;
				var lonitude2 = arr_connect_object_sub_dot.xy_geometry_center.y * Math.PI / 180;
				
				var d = Math.acos(Math.sin(latitude1)*Math.sin(latitude2) + 
								  Math.cos(latitude1)*Math.cos(latitude2) *
								  Math.cos(lonitude2-lonitude1)) * 6371;
								  
				//d = d * arr_object_sub_lines[locate_identifier][connect_identifier].object_sub_ids.length;

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
		
		PARENT.elm_controls.children('.geo').append('<p>Total distance: '+distance+' km</p><p>Longest distance: '+longest+' km</p><p>Shortest distance: '+shortest+' km</p>');*/
	};
};
