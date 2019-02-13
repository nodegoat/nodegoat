
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapData(element, obj_parent, options) {

	var elm = $(element),
	obj = this,
	obj_map = obj_parent,
	settings = $.extend({}, options || {});

	var arr_data = {},
	arr_data_parsed = {};
	
	this.arr_inactive_types = {};
	this.arr_inactive_object_sub_details = {};
	this.arr_inactive_conditions = {};
	this.arr_loop_inactive_conditions = [];
	
	this.init = function() {

		obj_map.getDataCaller(getData);
	};
	
	this.setData = function(arr_source_data) {
					
		arr_data = arr_source_data;

		var identifier_parse = JSON.stringify(settings);
		
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
			
			var unknown_date = (typeof settings.object_subs.unknown.date != 'undefined' ? settings.object_subs.unknown.date : false);
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
			var unknown_location = (typeof settings.object_subs.unknown.location != 'undefined' ? settings.object_subs.unknown.location : false);
			unknown_location = (unknown_location == 'ignore' ? false : unknown_location);
			if (unknown_location) {
				location_geometry_unknown = '{"type": "Point", "coordinates": [0, 0]}';
			}
			
			if (!arr_data.object_subs) {
				arr_data.object_subs = {};
			}
			
			if (unknown_date) { // Force date, if applicable, before looping through all sub-objects and connecting them based on their date
				
				for (var object_sub_id in arr_data.object_subs) {
					
					var arr_object_sub = arr_data.object_subs[object_sub_id];
					
					arr_data.objects[arr_object_sub.object_id].has_object_subs = true;

					if (!arr_object_sub.date_start) {
						
						arr_object_sub.date_start = obj_date_unknown.start;
						arr_object_sub.date_end = obj_date_unknown.end;
						
						if (obj_date_unknown.start == obj_date_unknown.end) {
							arr_data.date[obj_date_unknown.start].push(object_sub_id);
						} else {
							arr_data.range.push(object_sub_id);
						}
					}
				}
			}
		
			for (var object_id in arr_data.objects) {
				
				var arr_object = arr_data.objects[object_id];
				
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
					
					var len_i = arr_object.connect_object_sub_ids.length;

					for (var i = 0; i < len_i; i++) {
						
						var object_sub_id = arr_object.connect_object_sub_ids[i];
						var arr_object_sub = arr_data.object_subs[object_sub_id];
						
						// Link sub-objects to the scope of their objects
						if (!arr_object_sub.connected_object_ids) {
							arr_object_sub.connected_object_ids = [];
						}
						arr_object_sub.connected_object_ids.push(object_id);
						
						// Ordering
						if (!arr_object_sub.connect_object_sub_ids) {
							arr_object_sub.connect_object_sub_ids = [];
						}
						
						// Visual settings
						var original_object_id = (arr_object_sub.original_object_id ? arr_object_sub.original_object_id : arr_object_sub.object_id);
						if (original_object_id != object_id) { // Sub-object is part of a (possibly collapsed) scope, check their native object for the style
							var arr_object_style = arr_data.objects[original_object_id].style;
						} else {
							var arr_object_style = arr_object.style;
						}
						if (arr_object_style.color && !arr_object_sub.style.color) {
							arr_object_sub.style.color = arr_object_style.color;
						}
						if (arr_object_style.weight && !arr_object_sub.style.weight) {
							arr_object_sub.style.weight = arr_object_style.weight;
						}

						// Force location
						if (arr_object_sub.location_geometry === '' && unknown_location) {
							
							arr_object_sub.location_geometry = location_geometry_unknown;
						}
						
						var use_object_sub_details_id = false;
						var arr_potential_object_sub_ids = [];
						var date_potential = false;
						
						var total_j = arr_object.connect_object_sub_ids.length;
						for (var j = 0; j < total_j; j++) {
							
							var connect_object_sub_id = arr_object.connect_object_sub_ids[j];
							
							if (connect_object_sub_id == object_sub_id) {
								use_object_sub_details_id = true;
								continue;
							}
												
							var arr_connect_object_sub = arr_data.object_subs[connect_object_sub_id];
							
							// Check whether this sub-object is a potential candidate
							if (arr_connect_object_sub.date_start == arr_object_sub.date_start) { // If the connection candidate shares the same date, it has to be a different kind of sub-object and the first in line after the current sub-object
								
								if (arr_connect_object_sub.object_sub_details_id != arr_object_sub.object_sub_details_id && (!use_object_sub_details_id || use_object_sub_details_id == arr_connect_object_sub.object_sub_details_id)) {
									
									use_object_sub_details_id = arr_connect_object_sub.object_sub_details_id;
									arr_object_sub.connect_object_sub_ids.push(connect_object_sub_id);
									arr_potential_object_sub_ids = [];
								}
							} else if (arr_connect_object_sub.date_start < arr_object_sub.date_start) { // The connection presents itself earlier in time, or could still be going on.
								
								if (arr_connect_object_sub.date_end >= arr_object_sub.date_end) { // The connection is still going on. Use!
									
									arr_object_sub.connect_object_sub_ids.push(connect_object_sub_id);
									arr_potential_object_sub_ids = [];
									date_potential = arr_object_sub.date_start; // Set the potential date to its own starting date to now check for possible overlapping connection
									
								} else if (!date_potential || arr_connect_object_sub.date_end >= date_potential) { // The connection presents itself earlier in time
									
									if (arr_connect_object_sub.date_end > date_potential) { // Beter potential date
										arr_potential_object_sub_ids = [];
									}

									arr_potential_object_sub_ids.push(connect_object_sub_id);
									date_potential = arr_connect_object_sub.date_end;
								}
							}
						}
						
						var total_j = arr_potential_object_sub_ids.length;
						for (var j = 0; j < total_j; j++) {
							arr_object_sub.connect_object_sub_ids.push(arr_potential_object_sub_ids[j]);
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
		
		obj_map.setDateRange((arr_data.date_range ? arr_data.date_range : false));

		arr_data_parsed = arr_data;
	};
				
	var getData = function() {
	
		return arr_data_parsed;
	};
	
	this.setDataState = function(str_target, str_identifier, state) {
		
		switch (str_target) {
				
			case 'condition':
			
				if (str_identifier == null) {
					obj.arr_inactive_conditions = {};
				} else {
					obj.arr_inactive_conditions = updateStateInactive(obj.arr_inactive_conditions, str_identifier, state);
				}
				
				obj.arr_loop_inactive_conditions = Object.keys(obj.arr_inactive_conditions);

				break;
			case 'object-sub-details':
				
				if (str_identifier == null) {
					obj.arr_inactive_object_sub_details = {};
				} else {
					obj.arr_inactive_object_sub_details = updateStateInactive(obj.arr_inactive_object_sub_details, str_identifier, state);
				}
				
				break;
			case 'type':
			default:
			
				if (str_identifier == null) {
					obj.arr_inactive_types = {};
				} else {
					obj.arr_inactive_types = updateStateInactive(obj.arr_inactive_types, str_identifier, state);
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
						
	this.init();
};

