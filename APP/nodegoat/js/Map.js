
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function Map(element) {

	var elm = $(element),
	obj = this,
	settings = {
		arr_levels: [],
		default_zoom: 5,
		default_center: {x: 0.5, y: 0.5},
		center_pointer: true,
		pos_view_frame: {top: 0, right: 0, bottom: 0, left: 0},
		default_time: {selection: {}, bounds: {}},
		attribution: '',
		tile_path: '',
		tile_subdomain_range: ['a','b','c'], // e.g. a,b,c - 1,2,3
		background_color: false,
		allow_sizing: false,
		call_class_paint: function() {},
		call_class_data: function() {},
		call_data_fetch: function() {},
		arr_class_data_settings: {},
		arr_class_paint_settings: {},
		arr_data: []
	};
	
	obj.elm_controls = false,
	obj.elm_timeline = false,
	obj.obj_timeline_tschuifje = false;
	
	var initiated = false,
	elm_map = false,
	elm_paint = false,
	cur_dateint_range = {},
	date_range_bounds = {},
	dateint_range_bounds = {},
	draw = true,
	arr_changed = [];
	
	this.obj_map = {};
	this.obj_paint = {};
	this.obj_data = {};
			
	this.init = function(options) {
		
		settings = $.extend(settings, options || {});
		
		if (initiated) {
			
			obj.reinit(options);
			return;
		}
		
		initiated = true;		
		draw = false;
		
		elm_map = elm.children('.map');
		obj.elm_controls = elm.children('.controls');	
		
		drawMap();
		
		drawTimeline();

		obj.obj_paint = new settings.call_class_paint(elm_paint, obj, settings.arr_class_paint_settings);
		
		obj.obj_data = new settings.call_class_data(false, obj, settings.arr_class_data_settings);
		
		obj.obj_data.obj_paint = obj.obj_paint;
		obj.obj_paint.obj_data = obj.obj_data;
		if (obj.obj_data.init) {
			obj.obj_data.init();
		}
		
		if (obj.obj_data.setData) {
			obj.obj_data.setData(settings.arr_data);
		}
		
		if (obj.obj_paint.prepareData) {
			
			var options_extra = obj.obj_paint.prepareData(settings.arr_data);
			
			if (options_extra && (options_extra.default_zoom || options_extra.default_center)) {
				
				settings.default_zoom = (options_extra.default_zoom && !options.default_zoom ? options_extra.default_zoom : settings.default_zoom);
				settings.default_center = (options_extra.default_center && !options.default_center ? options_extra.default_center : settings.default_center);
			}
			
			obj.obj_map.setZoom(settings.default_zoom, settings.default_center);
		}
		
		if (obj.obj_paint.init) {
			obj.obj_paint.init();
		}
		
		draw = true;
		obj.doDraw();
	};
	
	this.reinit = function(options) {
		
		draw = false;
		
		if (options.arr_levels) {

			obj.obj_map.close();
			drawMap();
		} else if (options.default_center) {
			
			obj.obj_map.setZoom(settings.default_zoom, settings.default_center, settings.pos_view_frame);
		}
		
		if (!obj.elm_timeline || !onStage(obj.elm_timeline[0])) {
			drawTimeline();
		}
		
		var new_paint = (options.call_class_paint || options.arr_class_paint_settings || options.arr_levels || options.call_class_data || options.arr_data);
					
		if (new_paint) {
			elm_paint.off().empty();
			if (obj.obj_paint.close) {
				obj.obj_paint.close();
			}
			obj.obj_paint = new settings.call_class_paint(elm_paint, obj, settings.arr_class_paint_settings);
			obj.obj_data.obj_paint = obj.obj_paint;
		}
		
		if (options.call_class_data) {
			obj.obj_data = new settings.call_class_data(false, obj, settings.arr_class_data_settings);
			obj.obj_paint.obj_data = obj.obj_data;
			if (obj.obj_data.init) {
				obj.obj_data.init();
			}
		}
		
		if (options.call_class_data || options.arr_data) {
			if (obj.obj_data.setData) {
				obj.obj_data.setData(settings.arr_data);
			}
		} else if (options.default_time) {
			obj.setDateRange();
		}
		
		if (options.call_class_data || options.call_class_paint || options.arr_data) {
					
			if (obj.obj_paint.prepareData) {
				
				var options_extra = obj.obj_paint.prepareData(settings.arr_data);
				
				if (options_extra && (options_extra.default_zoom || options_extra.default_center)) {
					
					settings.default_zoom = (options_extra.default_zoom && !options.default_zoom ? options_extra.default_zoom : settings.default_zoom);
					settings.default_center = (options_extra.default_center && !options.default_center ? options_extra.default_center : settings.default_center);
				}
				
				obj.obj_map.setZoom(settings.default_zoom, settings.default_center);
			}
		}
		
		if (new_paint && obj.obj_paint.init) {
			obj.obj_paint.init();
		}
		
		draw = true;
		obj.doDraw();
	};
	
	this.close = function() {
		
		obj.obj_map.close();
		
		if (obj.obj_timeline_tschuifje) {
			obj.obj_timeline_tschuifje.close();
		}
		
		if (obj.obj_paint.close) {
			obj.obj_paint.close();
		}
	};
	
	var drawMap = function() {
					
		obj.obj_map = new MapScroller(elm_map, {
			arr_levels: settings.arr_levels,
			default_center: settings.default_center,
			center_pointer: settings.center_pointer,
			default_zoom: settings.default_zoom,
			pos_view_frame: settings.pos_view_frame,
			show_zoom_levels: true,
			attribution: settings.attribution,
			tile_path: settings.tile_path,
			background_color: settings.background_color,
			allow_sizing: settings.allow_sizing,
			tile_subdomain_range: settings.tile_subdomain_range
		});
		
		obj.obj_paint.obj_map = obj.obj_map;
		obj.obj_data.obj_map = obj.obj_map;
		
		obj.obj_map.init();
		
		elm_paint = obj.obj_map.getPaint();					
	};	
	
	var drawTimeline = function() {
					
		obj.elm_timeline = obj.elm_controls.children('.timeline');
		
		if (!obj.elm_timeline.length) {
			return;
		}
			
		var elm_timeline_tschuifje = obj.elm_timeline.find('.slider');
						
		obj.obj_timeline_tschuifje = new TSchuifje(elm_timeline_tschuifje, {
			bounds: {min: new Date(1900, 0, 1), max: new Date(2000, 0, 1)},
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
					
					obj.doDraw();
				}
			}
		});
		
		cur_dateint_range = {};
	};
			
	var updateDateValue = function(values) {

		var new_date_range = {
			min: DATEPARSER.date2Int(values.min),
			max: DATEPARSER.date2Int(values.max)
		};

		if (cur_dateint_range.min == new_date_range.min && cur_dateint_range.max == new_date_range.max) {
			return false;
		}
		
		cur_dateint_range = new_date_range;
		
		return true;
	};
	
	this.getMousePosition = function() {
		
		var pos_mouse = obj.obj_map.getMousePosition();
		
		if (!pos_mouse) {
			return pos_mouse;
		}
		
		return obj.obj_map.getPoint(pos_mouse.x, pos_mouse.y);
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
				min: DATEPARSER.newDate(Math.floor(min_year-(max_year-min_year)*0.2), 0, 01),
				max: DATEPARSER.newDate(Math.ceil(max_year+(max_year-min_year)*0.2), 11, 31)
			};
		}
		
		dateint_range_bounds = {
			min: DATEPARSER.date2Int(date_range_bounds.min),
			max: DATEPARSER.date2Int(date_range_bounds.max)
		};
		
		obj.obj_timeline_tschuifje.update({
			bounds: {min: date_range_bounds.min, max: date_range_bounds.max},
			min: date_range.min,
			max: date_range.max
		});
	};
	
	this.doDraw = function() {
		
		if (draw && obj.obj_paint.drawData) {
			
			return obj.obj_paint.drawData(cur_dateint_range, dateint_range_bounds, (obj.obj_timeline_tschuifje ? obj.obj_timeline_tschuifje.getSettings() : false));
		}
	};
	
	this.setData = function(arr) {
		
		if (!obj.obj_data.setData) {
			return;
		}
		
		obj.obj_data.setData(arr);
	};
	
	this.setDataState = function(str_target, str_identifier, state) {
		
		if (!obj.obj_data.setDataState) {
			return;
		}
		
		obj.obj_data.setDataState(str_target, str_identifier, state);
	};
	
	this.prepareData = function(arr) {
		
		if (!obj.obj_paint.prepareData) {
			return;
		}
		
		obj.obj_paint.prepareData(arr);
	};
											
	this.getDataCaller = function(callback) {
		settings.call_data_fetch = callback;
	};
	
	this.getData = function() {
		return settings.call_data_fetch();
	};
			
	this.setTimeline = function(data) {
					
		return obj.obj_timeline_tschuifje.update(data);
	};
	
	this.getTimelineSettings = function() {
		
		return obj.obj_timeline_tschuifje.getSettings();
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
}
