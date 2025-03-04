
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapManager(element) {

	var elm = $(element),
	SELF = this,
	settings = {
		arr_levels: [],
		default_zoom: false,
		default_center: {x: 0.5, y: 0.5},
		origin: {x: 0.5, y: 0.5, latitude: 0, longitude: 0},
		center_pointer: true,
		pos_view_frame: {top: 0, right: 0, bottom: 0, left: 0},
		default_time: {selection: {}, bounds: {}},
		attribution: '',
		arr_layers: false,
		background_color: false,
		allow_sizing: false,
		map: {}, // Other map properties
		call_class_paint: function() {},
		call_class_data: function() { this.init = function() {}; this.setData = function() {}; this.setSettings = function() {}; },
		arr_class_data_settings: {},
		arr_class_paint_settings: {},
		arr_data: []
	};
	
	this.elm_controls = false,
	this.elm_timeline = false,
	this.obj_timeline_tschuifje = false;
	
	var initiated = false,
	elm_map = false,
	elm_paint = false,
	cur_dateint_range = {},
	date_range_bounds = {},
	dateint_range_bounds = {},
	do_draw = true,
	arr_changed = [];
	
	this.obj_map = {};
	this.obj_paint = {};
	this.elm_paint_host = false;
	this.obj_data = {};
			
	this.init = function(options) {
		
		settings = $.extend(settings, options || {});
		
		if (initiated) {
			
			reinit(options);
			return;
		}
		
		initiated = true;		
		do_draw = false;
		
		elm_map = elm.children('.map');
		SELF.elm_controls = elm.children('.controls');	
		
		drawMap();
		
		drawTimeline();

		SELF.obj_paint = new settings.call_class_paint(elm_paint, SELF, settings.arr_class_paint_settings);
		
		SELF.obj_data = new settings.call_class_data(false, SELF, settings.arr_class_data_settings);
		
		SELF.obj_data.obj_paint = SELF.obj_paint;
		SELF.obj_paint.obj_data = SELF.obj_data;
		SELF.obj_data.init();
		
		SELF.obj_data.setData(settings.arr_data);
		
		if (SELF.obj_paint.prepareData) {
			
			var options_extra = SELF.obj_paint.prepareData(settings.arr_data);
			
			if (options_extra && (options_extra.default_zoom || options_extra.default_center)) {
				
				settings.default_zoom = (options_extra.default_zoom && !options.default_zoom ? options_extra.default_zoom : settings.default_zoom);
				settings.default_center = (options_extra.default_center && !options.default_center ? options_extra.default_center : settings.default_center);
			}
			
			SELF.obj_map.setZoom(settings.default_zoom, settings.default_center);
		}
		
		SELF.obj_paint.init();
		
		do_draw = true;
		SELF.doDraw();
	};
	
	var reinit = function(options) {
		
		do_draw = false;
		
		if (options.arr_levels) {

			SELF.obj_map.close();
			drawMap();
		} else if (options.default_center) {
			
			SELF.obj_map.setZoom(settings.default_zoom, settings.default_center, settings.pos_view_frame);
		}
		
		if (!SELF.elm_timeline || !onStage(SELF.elm_timeline[0])) {
			drawTimeline();
		}
		
		var new_paint = (options.call_class_paint || options.arr_class_paint_settings || options.arr_levels || options.call_class_data || options.arr_data);
					
		if (new_paint) {
			elm_paint.off().empty();
			if (SELF.obj_paint.close) {
				SELF.obj_paint.close();
			}
			SELF.obj_paint = new settings.call_class_paint(elm_paint, SELF, settings.arr_class_paint_settings);
			SELF.obj_data.obj_paint = SELF.obj_paint;
		}
		
		if (options.call_class_data) {
			SELF.obj_data = new settings.call_class_data(false, SELF, (options.arr_class_data_settings ? options.arr_class_data_settings : settings.arr_class_data_settings));
			SELF.obj_paint.obj_data = SELF.obj_data;
			SELF.obj_data.init();
		} else if (options.arr_class_data_settings) {
			SELF.obj_data.setSettings(options.arr_class_data_settings);
		}
		
		if (options.call_class_data || options.arr_data) {
			SELF.obj_data.setData(settings.arr_data);
		} else if (options.default_time) {
			SELF.setDateRange();
		}
		
		if (options.call_class_data || options.call_class_paint || options.arr_data) {
					
			if (SELF.obj_paint.prepareData) {
				
				var options_extra = SELF.obj_paint.prepareData(settings.arr_data);
				
				if (options_extra && (options_extra.default_zoom || options_extra.default_center)) {
					
					settings.default_zoom = (options_extra.default_zoom && !options.default_zoom ? options_extra.default_zoom : settings.default_zoom);
					settings.default_center = (options_extra.default_center && !options.default_center ? options_extra.default_center : settings.default_center);
				}
				
				SELF.obj_map.setZoom(settings.default_zoom, settings.default_center);
			}
		}
		
		if (new_paint) {
			SELF.obj_paint.init();
		}
		
		do_draw = true;
		SELF.doDraw();
	};
	
	this.close = function() {
		
		SELF.obj_map.close();
		
		if (SELF.obj_timeline_tschuifje) {
			SELF.obj_timeline_tschuifje.close();
		}
		
		if (SELF.obj_paint.close) {
			SELF.obj_paint.close();
		}
	};
	
	var drawMap = function() {
		
		const arr_settings_map = {
			arr_levels: settings.arr_levels,
			default_center: settings.default_center,
			origin: settings.origin,
			center_pointer: settings.center_pointer,
			default_zoom: settings.default_zoom,
			pos_view_frame: settings.pos_view_frame,
			show_zoom_levels: true,
			attribution: settings.attribution,
			arr_layers: settings.arr_layers,
			background_color: settings.background_color,
			allow_sizing: settings.allow_sizing
		};
		$.extend(arr_settings_map, settings.map || {});
		
		SELF.obj_map = new MapScroller(elm_map, arr_settings_map);
				
		SELF.obj_map.init();
		
		elm_paint = SELF.obj_map.getPaint();
		SELF.elm_paint_host = elm_paint[0];
				
		elm_paint = ASSETS.createDocumentHost(SELF.elm_paint_host, 'labmap-paint', ['.tooltip']);
		elm_paint = $(elm_paint);
	};	
	
	var drawTimeline = function() {
					
		SELF.elm_timeline = SELF.elm_controls.children('.timeline');
		
		if (!SELF.elm_timeline.length) {
			return;
		}
			
		var elm_timeline_tschuifje = SELF.elm_timeline.find('.slider');
						
		SELF.obj_timeline_tschuifje = new TSchuifje(elm_timeline_tschuifje, {
			bounds: {min: DATEPARSER.newDate(1900, 0, 1, 0), max: DATEPARSER.newDate(2000, 0, 1, 9999)},
			call_change: function(value) {
				
				if (updateDateValue(value)) {
					
					var len = arr_changed.length;
					
					if (len) {					
						for (var i = 0; i < len; i++) {
							if (arr_changed[i]) {
								arr_changed[i](value);
							}
						}
					}
					
					SELF.doDraw();
				}
			}
		});
		
		cur_dateint_range = {};
	};
			
	var updateDateValue = function(values) {

		var new_date_range = {
			min: DATEPARSER.date2Integer(values.min),
			max: DATEPARSER.date2Integer(values.max)
		};

		if (cur_dateint_range.min == new_date_range.min && cur_dateint_range.max == new_date_range.max) {
			return false;
		}
		
		cur_dateint_range = new_date_range;
		
		return true;
	};
	
	this.getMousePosition = function() {
		
		var pos_mouse = SELF.obj_map.getMousePosition();
		
		if (!pos_mouse) {
			return pos_mouse;
		}
		
		return SELF.obj_map.getPoint(pos_mouse.x, pos_mouse.y);
	}
			
	this.getDateRange = function() {
		
		return cur_dateint_range;
	};
			
	this.setDateRange = function(dateint_range) {

		if (settings.default_time.selection.min) {
			
			var dateint_range = {
				min: settings.default_time.selection.min,
				max: settings.default_time.selection.max
			};
		}
		
		if (!dateint_range) {
			return false;
		}
		
		var date_range = {
			min: DATEPARSER.int2Date(dateint_range.min),
			max: DATEPARSER.int2Date(dateint_range.max)
		};
		
		if (settings.default_time.bounds.min) {
			
			date_range_bounds = {
				min: DATEPARSER.int2Date(settings.default_time.bounds.min),
				max: DATEPARSER.int2Date(settings.default_time.bounds.max)
			};
		} else {

			var min_year = date_range.min.getFullYear();
			var max_year = date_range.max.getFullYear();
			
			date_range_bounds = {
				min: DATEPARSER.newDate(Math.floor(min_year-(max_year-min_year)*0.2), 0, 01, 0),
				max: DATEPARSER.newDate(Math.ceil(max_year+(max_year-min_year)*0.2), 11, 31, 9999)
			};
		}
		
		dateint_range_bounds = {
			min: DATEPARSER.date2Integer(date_range_bounds.min),
			max: DATEPARSER.date2Integer(date_range_bounds.max)
		};
		
		SELF.obj_timeline_tschuifje.update({
			bounds: {min: date_range_bounds.min, max: date_range_bounds.max},
			min: date_range.min,
			max: date_range.max
		});
	};
	
	this.doDraw = function() {
		
		if (!do_draw || !SELF.obj_paint.drawData) {
			return;
		}
			
		return SELF.obj_paint.drawData(cur_dateint_range, dateint_range_bounds, (SELF.obj_timeline_tschuifje ? SELF.obj_timeline_tschuifje.getSettings() : false));
	};
	
	this.setData = function(arr) {
				
		SELF.obj_data.setData(arr);
	};
	
	this.setDataState = function(str_target, str_identifier, state) {
		
		if (!SELF.obj_data.setDataState) {
			return;
		}
		
		SELF.obj_data.setDataState(str_target, str_identifier, state);
	};
	
	this.prepareData = function(arr) {
		
		if (!SELF.obj_paint.prepareData) {
			return;
		}
		
		SELF.obj_paint.prepareData(arr);
	};
												
	this.getData = function() {
		
		return SELF.obj_data.getData();
	};
			
	this.setTimeline = function(data) {
					
		return SELF.obj_timeline_tschuifje.update(data);
	};
	
	this.getTimelineSettings = function() {
		
		return SELF.obj_timeline_tschuifje.getSettings();
	};
	
	this.changedTimeline = function(call, key) {
		
		if (key === 0 || key > 0) {
			
			arr_changed[key] = call;
		} else {
			
			for (var i = 0, len = arr_changed.length; i <= len; i++) {
				
				if (arr_changed[i] === null || arr_changed[i] === undefined) {
					
					var key = i;
					arr_changed[key] = call;
					break;
				}
			}
		}
		
		return key;
	};
	
	this.getMap = function() {
		return SELF.obj_map;
	};
}
