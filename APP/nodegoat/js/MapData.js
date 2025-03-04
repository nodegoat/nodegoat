
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
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
				
				if (arr_object.style.weight == null) {
					arr_object.style.weight = null;
				} else if (arr_object.style.weight instanceof Array) {
					arr_object.style.weight = arr_object.style.weight.reduce(function(acc, val) { return acc + val; }, 0);
				}
				
				if (arr_object.object_definitions) {
						
					for (const object_definition_id in arr_object.object_definitions) {
					
						const arr_object_definition = arr_object.object_definitions[object_definition_id];
						
						if (typeof arr_object_definition.style === 'undefined') {
							arr_object_definition.style = [];
						}
						
						if (arr_object_definition.style.weight == null) {
							arr_object_definition.style.weight = null;
						} else if (arr_object_definition.style.weight instanceof Array) {
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
			
			let arr_date_unknown = {start: 0, end: 0};
			let str_location_geometry_unknown = '';
			
			let unknown_date = (typeof arr_settings.object_subs.unknown.date != 'undefined' ? arr_settings.object_subs.unknown.date : false);
			unknown_date = (unknown_date == 'ignore' ? false : unknown_date);
			if (unknown_date) {
				if (unknown_date == 'span') {
					arr_date_unknown = {start: arr_data.date_range.min, end: arr_data.date_range.max};
				} else if (unknown_date == 'prefix') {
					arr_date_unknown = {start: arr_data.date_range.min, end: arr_data.date_range.min};
				} else if (unknown_date == 'affix') {
					arr_date_unknown = {start: arr_data.date_range.max, end: arr_data.date_range.max};
				}
				if (arr_date_unknown.start == arr_date_unknown.end) {
					if (!arr_data.date[arr_date_unknown.start]) {
						arr_data.date[arr_date_unknown.start] = [];
					}
				}
			}
			let unknown_location = (typeof arr_settings.object_subs.unknown.location != 'undefined' ? arr_settings.object_subs.unknown.location : false);
			unknown_location = (unknown_location == 'ignore' ? false : unknown_location);
			if (unknown_location) {
				str_location_geometry_unknown = '{"type": "Point", "coordinates": [0, 0]}';
			}
			
			if (!arr_data.object_subs) {
				arr_data.object_subs = {};
			}

			for (const object_sub_id in arr_data.object_subs) {
				
				const arr_object_sub = arr_data.object_subs[object_sub_id];

				if (arr_object_sub.style === undefined) {
					arr_object_sub.style = [];
				}
				
				if (arr_object_sub.style.weight == null) {
					arr_object_sub.style.weight = null;
				} else if (arr_object_sub.style.weight instanceof Array) {
					arr_object_sub.style.weight = arr_object_sub.style.weight.reduce(function(acc, val) { return acc + val; }, 0);
				}
				
				if (arr_object_sub.object_sub_definitions) {
					
					for (const object_sub_definition_id in arr_object_sub.object_sub_definitions) {
						
						const arr_object_sub_definition = arr_object_sub.object_sub_definitions[object_sub_definition_id];

						if (typeof arr_object_sub_definition.style === 'undefined') {
							arr_object_sub_definition.style = [];
						}
						
						if (arr_object_sub_definition.style.weight == null) {
							arr_object_sub_definition.style.weight = null;
						} else if (arr_object_sub_definition.style.weight instanceof Array) {
							arr_object_sub_definition.style.weight = arr_object_sub_definition.style.weight.reduce(function(acc, val) { return acc + val; }, 0);
						}
					}
				}
				
				if (!arr_object_sub.date_start) {
					
					if (unknown_date) { // Force date, if applicable, before looping through all sub-objects and connecting them based on their date
						
						arr_object_sub.date_start = arr_date_unknown.start;
						arr_object_sub.date_end = arr_date_unknown.end;
						
						if (arr_date_unknown.start == arr_date_unknown.end) {
							arr_data.date[arr_date_unknown.start].push(object_sub_id);
						} else {
							arr_data.range.push(object_sub_id);
						}
					} else {
						
						delete arr_data.object_subs[object_sub_id];
						
						continue;
					}
				}
				
				arr_data.objects[arr_object_sub.object_id].has_object_subs = true;
			}
		
			for (const object_id in arr_data.objects) {
				
				const arr_object = arr_data.objects[object_id];
				
				if (arr_object.name == undefined) {
					continue;
				}
								
				if (unknown_date && !arr_object.has_object_subs) { // Make sure every Object has a Sub-Object to make it 'exist' for relational purposes
					
					var object_sub_id = 'object_'+object_id;
					
					arr_data.object_subs[object_sub_id] = {
						object_id: object_id,
						object_sub_details_id: 'object',
						location_object_id: false,
						location_type_id: false,
						date_start: arr_date_unknown.start,
						date_end: arr_date_unknown.end,
						style: {weight: null}
					};
					
					if (arr_date_unknown.start == arr_date_unknown.end) {
						arr_data.date[arr_date_unknown.start].push(object_sub_id);
					} else {
						arr_data.range.push(object_sub_id);
					}
				}
				
				// Connect all relevant Sub-Objects for geographical lineage
				
				if (arr_object.connect_object_sub_ids == undefined) { // Object is not a starting point (i.e. not the main scoped Object)
					
					arr_object.connect_object_sub_ids = [];
				} else {
					
					arr_object.connect_object_sub_ids = Object.values(arr_object.connect_object_sub_ids); // Force array
					
					for (let i = 0, len_i = arr_object.connect_object_sub_ids.length; i < len_i; i++) {
						
						const object_sub_id = arr_object.connect_object_sub_ids[i];
						const arr_object_sub = arr_data.object_subs[object_sub_id];
						
						if (arr_object_sub === undefined) { // Removed due to i.e. ignored date
							
							arr_object.connect_object_sub_ids.splice(i, 1);
							
							i--;
							len_i--;
							continue;
						}
						
						// Link Sub-Objects to the scope of their Objects
						
						if (!arr_object_sub.connected_object_ids) {
							arr_object_sub.connected_object_ids = [];
						}
						arr_object_sub.connected_object_ids.push(object_id);
						
						// Force location
						
						if (arr_object_sub.location_geometry === '' && unknown_location) {
							arr_object_sub.location_geometry = str_location_geometry_unknown;
						}

						// Visual settings Sub-Object inheritance (Geo and Timeline)
						
						arr_object_sub.style_inherit = {};
						const arr_object_sub_style = arr_object_sub.style_inherit;
						
						for (const key in arr_object_sub.style) {
							arr_object_sub_style[key] = arr_object_sub.style[key];
						}

						let arr_object_style = arr_object.style;
						
						if (arr_object_sub.object_id != object_id) { // Sub-Object is part of a scope, check their native Object (or collapse Object) for the style
							arr_object_style = arr_data.objects[arr_object_sub.object_id].style;
						}
						
						if (arr_object_style.color && !arr_object_sub_style.color) {
							arr_object_sub_style.color = arr_object_style.color;
						}
						if (arr_object_style.weight !== null && arr_object_sub_style.weight === null) {
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

