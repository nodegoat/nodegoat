
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapData(element, PARENT, arr_settings_options) {

	var elm = $(element),
	SELF = this,
	arr_settings = {};

	var arr_data = {};
	
	this.arr_inactive_types = {};
	this.arr_inactive_object_sub_details = {};
	this.arr_inactive_conditions = {};
	this.arr_loop_inactive_conditions = [];

	this.init = function() {

	};
	
	this.setSettings = function(arr_settings_options) {

		arr_settings = $.extend(arr_settings, arr_settings_options || {});
	};
	
	this.setData = function(arr_source_data) {
					
		arr_data = arr_source_data;

		var identifier_parse = JSON.stringify(arr_settings);
		
		if (!arr_data.source) {
			
			var Merge = new DeepMerge();
			
			var arr_package = {};
			
			for (var i = 0, len = arr_data.pack.length; i < len; i++) {

				arr_package = Merge.single(arr_package, arr_data.pack[i]);

				arr_data.pack[i] = null;
			}
			
			delete arr_data.pack;
			
			for (var key in arr_package) {
				
				arr_data[key] = arr_package[key];
			}
			
			arr_data.identifier_parse = false;
			arr_data.update = []; // To store/indicate further processing updates

			arr_data.source = JSON.stringify(arr_data);
		}
		
		if (arr_data.identifier_parse && arr_data.identifier_parse != identifier_parse) {

			var arr_source_data = JSON.parse(arr_data.source);

			for (var key in arr_data) {
				
				if (key == 'source') {
					continue;
				}
				
				if (arr_source_data[key]) {
					arr_data[key] = arr_source_data[key];
				} else {
					delete arr_data[key];
				}
			}
		}
		
		if (!arr_data.identifier_parse) {
			
			// Objects
			
			for (const object_id in arr_data.objects) {
			
				const arr_object = arr_data.objects[object_id];
				
				if (typeof arr_object.style === 'undefined') {
					arr_object.style = [];
				}
								
				if (typeof arr_object.style.weight !== 'undefined' && arr_object.style.weight instanceof Array) {
					arr_object.style.weight = arr_object.style.weight.reduce(function(acc, val) { return acc + val; }, 0);
				}
				
				if (arr_object.object_definitions) {
						
					for (const object_definition_id in arr_object.object_definitions) {
					
						const arr_object_definition = arr_object.object_definitions[object_definition_id];
						
						if (typeof arr_object_definition.style === 'undefined') {
							arr_object_definition.style = [];
						}
						
						if (typeof arr_object_definition.style.weight !== 'undefined' && arr_object_definition.style.weight instanceof Array) {
							arr_object_definition.style.weight = arr_object_definition.style.weight.reduce(function(acc, val) { return acc + val; }, 0);
						}
					}
				}
			}
			
			// Sub-Objects
			
			if (!arr_data.range) {
				arr_data.range = [];
			} else {
				arr_data.range = Object.values(arr_data.range); // Force array
			}
			if (!arr_data.date) {
				arr_data.date = {};
			}
			
			var obj_date_unknown = {start: 0, end: 0};
			var location_geometry_unknown = '';
			
			var unknown_date = (typeof arr_settings.object_subs.unknown.date != 'undefined' ? arr_settings.object_subs.unknown.date : false);
			unknown_date = (unknown_date == 'ignore' ? false : unknown_date);
			if (unknown_date) {
				if (unknown_date == 'span') {
					obj_date_unknown = {start: arr_data.date_range.min, end: arr_data.date_range.max};
				} else if (unknown_date == 'prefix') {
					obj_date_unknown = {start: arr_data.date_range.min, end: arr_data.date_range.min};
				} else if (unknown_date == 'affix') {
					obj_date_unknown = {start: arr_data.date_range.max, end: arr_data.date_range.max};
				}
				if (obj_date_unknown.start == obj_date_unknown.end) {
					if (!arr_data.date[obj_date_unknown.start]) {
						arr_data.date[obj_date_unknown.start] = [];
					}
				}
			}
			var unknown_location = (typeof arr_settings.object_subs.unknown.location != 'undefined' ? arr_settings.object_subs.unknown.location : false);
			unknown_location = (unknown_location == 'ignore' ? false : unknown_location);
			if (unknown_location) {
				location_geometry_unknown = '{"type": "Point", "coordinates": [0, 0]}';
			}
			
			if (!arr_data.object_subs) {
				arr_data.object_subs = {};
			}

			for (const object_sub_id in arr_data.object_subs) {
				
				const arr_object_sub = arr_data.object_subs[object_sub_id];
				
				arr_data.objects[arr_object_sub.object_id].has_object_subs = true;
				
				if (typeof arr_object_sub.style === 'undefined') {
					arr_object_sub.style = [];
				}
				
				if (typeof arr_object_sub.style.weight !== 'undefined' && arr_object_sub.style.weight instanceof Array) {
					arr_object_sub.style.weight = arr_object_sub.style.weight.reduce(function(acc, val) { return acc + val; }, 0);
				}
				
				if (arr_object_sub.object_sub_definitions) {
					
					for (const object_sub_definition_id in arr_object_sub.object_sub_definitions) {
						
						const arr_object_sub_definition = arr_object_sub.object_sub_definitions[object_sub_definition_id];

						if (typeof arr_object_sub_definition.style === 'undefined') {
							arr_object_sub_definition.style = [];
						}
							
						if (typeof arr_object_sub_definition.style.weight !== 'undefined' && arr_object_sub_definition.style.weight instanceof Array) {
							arr_object_sub_definition.style.weight = arr_object_sub_definition.style.weight.reduce(function(acc, val) { return acc + val; }, 0);
						}
					}
				}
				
				if (unknown_date && !arr_object_sub.date_start) { // Force date, if applicable, before looping through all sub-objects and connecting them based on their date
					
					arr_object_sub.date_start = obj_date_unknown.start;
					arr_object_sub.date_end = obj_date_unknown.end;
					
					if (obj_date_unknown.start == obj_date_unknown.end) {
						arr_data.date[obj_date_unknown.start].push(object_sub_id);
					} else {
						arr_data.range.push(object_sub_id);
					}
				}
			}
		
			for (const object_id in arr_data.objects) {
				
				const arr_object = arr_data.objects[object_id];
				
				if (arr_object.name == undefined) {
					continue;
				}
								
				if (unknown_date && !arr_object.has_object_subs) { // Make sure every object has a sub-object to make it 'exist' for relational purposes
					
					var object_sub_id = 'object_'+object_id;
					
					arr_data.object_subs[object_sub_id] = {
						object_id: object_id,
						object_sub_details_id: 'object',
						location_object_id: false,
						location_type_id: false,
						date_start: obj_date_unknown.start,
						date_end: obj_date_unknown.end,
						style: []
					};
					
					if (obj_date_unknown.start == obj_date_unknown.end) {
						arr_data.date[obj_date_unknown.start].push(object_sub_id);
					} else {
						arr_data.range.push(object_sub_id);
					}
				}
				
				// Connect all relevant sub-objects for geographical lineage
				
				if (arr_object.connect_object_sub_ids == undefined) { // Object is not a starting point (i.e. not the main scoped object)
					
					arr_object.connect_object_sub_ids = [];
				} else {
					
					arr_object.connect_object_sub_ids = Object.values(arr_object.connect_object_sub_ids); // Force array
					
					const len_i = arr_object.connect_object_sub_ids.length;

					for (let i = 0; i < len_i; i++) {
						
						const object_sub_id = arr_object.connect_object_sub_ids[i];
						const arr_object_sub = arr_data.object_subs[object_sub_id];
						
						// Link sub-objects to the scope of their objects
						if (!arr_object_sub.connected_object_ids) {
							arr_object_sub.connected_object_ids = [];
						}
						arr_object_sub.connected_object_ids.push(object_id);

						// Visual settings
						const arr_object_sub_style = arr_object_sub.style;
						const original_object_id = (arr_object_sub.original_object_id ? arr_object_sub.original_object_id : arr_object_sub.object_id);
						if (original_object_id != object_id) { // Sub-object is part of a (possibly collapsed) scope, check their native object for the style
							var arr_object_style = arr_data.objects[original_object_id].style;
						} else {
							var arr_object_style = arr_object.style;
						}
						if (arr_object_style.color && !arr_object_sub_style.color) {
							arr_object_sub_style.color = arr_object_style.color;
						}
						if (arr_object_style.weight != null && arr_object_sub_style.weight == null) {
							arr_object_sub_style.weight = arr_object_style.weight;
						}
						if (arr_object_style.geometry_color && !arr_object_sub_style.geometry_color) {
							arr_object_sub_style.geometry_color = arr_object_style.geometry_color;
						}
						if (arr_object_style.geometry_opacity != null && arr_object_sub_style.geometry_opacity == null) {
							arr_object_sub_style.geometry_opacity = arr_object_style.geometry_opacity;
						}
						if (arr_object_style.geometry_stroke_color && !arr_object_sub_style.geometry_stroke_color) {
							arr_object_sub_style.geometry_stroke_color = arr_object_style.geometry_stroke_color;
						}
						if (arr_object_style.geometry_stroke_opacity != null && arr_object_sub_style.geometry_stroke_opacity == null) {
							arr_object_sub_style.geometry_stroke_opacity = arr_object_style.geometry_stroke_opacity;
						}
						if (arr_object_style.icon && !arr_object_sub_style.icon) {
							arr_object_sub_style.icon = arr_object_style.icon;
						}
						if (arr_object_style.conditions) {
							
							if (typeof arr_object_sub_style.conditions == 'undefined') {
								arr_object_sub_style.conditions = {};
							}
							
							for (const str_identifier_condition in arr_object_style.conditions) {
								
								if (typeof arr_object_sub_style.conditions[str_identifier_condition] != 'undefined') {
									continue;
								}

								arr_object_sub_style.conditions[str_identifier_condition] = arr_object_style.conditions[str_identifier_condition];
							}
						}

						// Force location
						if (arr_object_sub.location_geometry === '' && unknown_location) {
							
							arr_object_sub.location_geometry = location_geometry_unknown;
						}
					}
				}
			}
			
			var arr_loop = [];
			for (var date in arr_data.date) {
				arr_loop.push(date);
			}
			arr_data.date.arr_loop = arr_loop;
			
			arr_data.identifier_parse = identifier_parse;
		}
		
		PARENT.setDateRange((arr_data.date_range ? arr_data.date_range : false));
	};
	
	this.updateData = function(identifier, func_update) {
			
		if (arr_data.update[identifier]) {
			return;
		}
		
		func_update(arr_data);
		
		arr_data.update[identifier] = true;
	};
				
	this.getData = function() {
	
		return arr_data;
	};
	
	this.setDataState = function(str_target, str_identifier, state) {
		
		switch (str_target) {
				
			case 'condition':
			
				if (str_identifier == null) {
					SELF.arr_inactive_conditions = {};
				} else {
					SELF.arr_inactive_conditions = updateStateInactive(SELF.arr_inactive_conditions, str_identifier, state);
				}
				
				SELF.arr_loop_inactive_conditions = Object.keys(SELF.arr_inactive_conditions);

				break;
			case 'object-sub-details':
				
				if (str_identifier == null) {
					SELF.arr_inactive_object_sub_details = {};
				} else {
					SELF.arr_inactive_object_sub_details = updateStateInactive(SELF.arr_inactive_object_sub_details, str_identifier, state);
				}
				
				break;
			case 'type':
			default:
			
				if (str_identifier == null) {
					SELF.arr_inactive_types = {};
				} else {
					SELF.arr_inactive_types = updateStateInactive(SELF.arr_inactive_types, str_identifier, state);
				}
				
				break;
		}
	};
	
	var updateStateInactive = function(arr_inactive, str_identifier, state) {
		
		if (state == false) {
			
			arr_inactive[str_identifier] = true;
			
		} else {
						
			delete arr_inactive[str_identifier];			
		}
		
		return arr_inactive;
	};
	
	SELF.setSettings(arr_settings_options);
	
	SELF.init();
};

