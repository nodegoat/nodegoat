
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapTimeline(element, obj_parent, options) {

	var elm = $(element),
	obj = this,
	obj_map = obj_parent.obj_map;

	var	arr_data = {};
		
	var dateint_range = false,
	date_int_absolute_range = false,
	number_of_plots = 0,
	number_of_columns = 0,
	number_of_rows = 0,
	size_renderer = false,
	size_drawer = false,
	size_view = false,
	renderer_canvas = false,
	renderer = false,
	drawer_canvas = false,
	drawer = false,
	stage = false,
	stage_timeline_canvas = false,
	stage_timeline = false,
	stage_graphs_canvas = false,
	stage_graphs = false,
	padding = {},
	arr_date_intervals = {},
	arr_object_sub_type_id = {},
	obj_conditions = false,
	elm_graph_options = false,
	bars_object_subs = true,
	graph_conditions = false,
	graph_objects_type_id = false,
	graph_relative = false,
	graph_cumulative = false,
	graph_relative_option = false,
	graph_cumulative_option = false,
	is_grid = false,
	number_of_dates = 0,
	max_object_subs_amount = 1,
	max_objects_amount = 1,
	max_relative_amount = 0,
	max_cumulative_amount = 0,
	total_amount = 0,
	reset_total_amount = true,
	mean = false,
	num_width_bar = 20,
	num_height_max = false,
	font_family = window.getComputedStyle(elm[0])['font-family'],
	size_text = 10,
	color_bar = false,
	opacity_bar = 0.5,
	color_text_date = '#000000',
	color_text_label = '#444444',
	arr_labels = {},
	arr_circles = [],
	arr_info_boxes = [],
	arr_paths = [],
	arr_bars = [],
	interval = {},
	key_move = false,
	num_resolution = false,
	pos_hover_poll = false,
	hover_id = false;
	
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
		
		opacity_bar = options.arr_visual.time.bar.opacity;
		if (options.arr_visual.time.bar.color) {
			color_bar = options.arr_visual.time.bar.color;
		}
		
		var pos_map = obj_map.getPosition();
		
		/*ASSETS.fetch({
			font: [
				'pixel'
			]
		}, function() {*/
			
			arr_data = obj_parent.getData();
			
			num_resolution = pos_map.render.resolution;

			num_width_bar = num_width_bar * num_resolution;
			
			size_drawer = {width: pos_map.size.width * num_resolution, height: pos_map.size.height * num_resolution};			
			size_view = {width: pos_map.size.width * num_resolution, height: pos_map.size.height * num_resolution};
			
			renderer_canvas = document.createElement('canvas');
			renderer_canvas.width = size_view.width;
			renderer_canvas.height = size_view.height;
			renderer = renderer_canvas.getContext('2d');
			
			elm[0].appendChild(renderer_canvas);

			renderer_canvas.style.transform = 'scale('+ 1 / num_resolution +')';
			renderer_canvas.style.transformOrigin = '0 0';
			
			drawer_canvas = document.createElement('canvas');
			drawer_canvas.width = size_drawer.width;
			drawer_canvas.height = size_drawer.height;
			drawer = drawer_canvas.getContext('2d');
			
			stage_timeline_canvas = document.createElement('canvas');
			stage_timeline_canvas.width = size_drawer.width;
			stage_timeline_canvas.height = size_drawer.height;
			stage_timeline = stage_timeline_canvas.getContext('2d');
			
			stage_graphs_canvas = document.createElement('canvas');
			stage_graphs_canvas.width = size_view.width;
			stage_graphs_canvas.height = size_view.height;
			stage_graphs = stage_graphs_canvas.getContext('2d');

			graph_relative_option = options.arr_visual.time.settings.relative_graph;
			
			if (graph_relative_option) {
				graph_relative = true;
			}
			
			graph_cumulative_option = options.arr_visual.time.settings.cumulative_graph;
		
			if (graph_cumulative_option) {
				graph_cumulative = true;
			}

			var count_start = 0;

			var func_start = function() {
				
				if (count_start > 0) {
					return;
				}
							
				if (!obj_conditions) {
					setConditions();
				}
				
				if (!elm_graph_options) {
					addListeners();
				}
				
				obj.drawData = drawData;
				
				obj_parent.doDraw();
				
			};
			
			count_start++ // Labels loading
			
			ASSETS.getLabels(elm,
				['lbl_condition', 'lbl_conditions', 'lbl_object_subs', 'lbl_object', 'lbl_objects', 'lbl_relative', 'lbl_cumulative', 'lbl_total', 'lbl_amount', 'lbl_date'],
				function(data) {
					
					arr_labels = data;
					
					count_start--; // Labels loaded
					
					func_start();
				}
			);
			
			if (arr_data.legend.conditions) {
				
				var arr_media = [];
				
				for (var key in arr_data.legend.conditions) {
					
					var arr_condition = arr_data.legend.conditions[key];
					
					if (arr_condition.icon) {			
						arr_media.push(arr_condition.icon);
					}
				}	

				if (arr_media.length) {
					
					count_start++ // Media loading
					
					ASSETS.fetch({media: arr_media}, function() {

						count_start--; // Media loaded
						
						func_start();
						
					});
				}
			}
		//});
	};

	this.close = function() {
		
		if (!obj_map) { // Nothing loaded yet
			return;
		}

		runElementsSelectorFunction(obj_parent.elm_controls, '.legends figure[class^="timeline-graph"]', function(elm) {
			elm.remove();
		});
	};
		
	var resizeDrawer = function() {
	
		if (is_grid) {
			
			number_of_plots = 0;
			padding = {'top': 0, 'right': 3 * num_resolution, 'bottom': 30 * num_resolution, 'left': 3 * num_resolution};
			
			if (graph_objects_type_id) {
				
				for (const object_id in arr_data.objects) {
					
					if (graph_objects_type_id == arr_data.objects[object_id].type_id) {
						
						number_of_plots++;
					}	
				}
			}		
			
			if (graph_conditions) {
				
				for (const condition_identifier in obj_conditions) {
					
					number_of_plots++;
				}
			}
			
		} else {
			
			number_of_plots = 1;
			padding = {'top': 50 * num_resolution, 'right': 100 * num_resolution, 'bottom': 200 * num_resolution, 'left': 100 * num_resolution};
		}
		
		if (number_of_plots > 1) {
			
			number_of_columns = Math.ceil(Math.sqrt(number_of_plots));
				
			if (number_of_columns < 1) {
				number_of_columns = 1;
			}
			
			number_of_rows = Math.ceil(number_of_plots / number_of_columns);
			size_drawer = {width: Math.ceil(size_view.width / number_of_columns), height: Math.ceil(size_view.height / number_of_rows)};
		
		} else {
			
			size_drawer = {width: size_view.width, height: size_view.height};			
		}
		
		num_width_bar = size_drawer.width * 0.01
		
		if (num_width_bar > (20 * num_resolution)) {
			num_width_bar = 20 * num_resolution;
		}
		
		if (num_width_bar < (10 * num_resolution)) {
			num_width_bar = 10 * num_resolution;
		}
		
		drawer_canvas.width = size_drawer.width;
		drawer_canvas.height = size_drawer.height;
	}
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
	
		stage_graphs_canvas.width = pos.view.width * num_resolution;
		stage_graphs_canvas.height = pos.view.height * num_resolution;
				
		if (calc_zoom) { // Zoomed

		}
	}
	
	var addListeners = function() {
		
		let elm_legends = false;
		runElementSelectorFunction(obj_parent.elm_controls, '.legends', function(elm_legend) {
			elm_legends = elm_legend;
		});
		
		let elm_graphs_figure = document.createElement('figure'); 
		elm_graphs_figure.classList.add('timeline-graphs');
		elm_legends.appendChild(elm_graphs_figure);
		let elm_graphs_list = document.createElement('dl'); 
		elm_graphs_figure.appendChild(elm_graphs_list);
		
		let obj_graph_options = {};
		
		for (let type_id in arr_data.info.types) {
			obj_graph_options[type_id] = arr_data.info.types[type_id];
		}
	
		if (obj_conditions) {
			
			obj_graph_options['conditions'] = {'name' : arr_labels.lbl_conditions};
		}
		
		for (let id in obj_graph_options) {
			
			let elm_graphs_list_item = document.createElement('li');
			elm_graphs_list.appendChild(elm_graphs_list_item);
			
			let elm_graph_name = document.createElement('dt');
			elm_graphs_list_item.appendChild(elm_graph_name);
			
			let graph_name = obj_graph_options[id].name;
			let elm_graph_name_value = document.createTextNode(graph_name);
			elm_graph_name.appendChild(elm_graph_name_value);
			
			let elm_graph_options = document.createElement('dd');
			elm_graphs_list_item.appendChild(elm_graph_options);
			
			let elm_button_graph = document.createElement('button');
			elm_button_graph.setAttribute('type', 'button');
			elm_button_graph.classList.add('show-objects');
			elm_graph_options.appendChild(elm_button_graph);
			
			if (id == 'conditions') {
				elm_button_graph.classList.add('active');
			}
			
			let elm_button_graph_span = document.createElement('span');
			elm_button_graph_span.classList.add('icon');
			elm_button_graph.appendChild(elm_button_graph_span);
			
			let elm_button_grid = document.createElement('button');
			elm_button_grid.setAttribute('type', 'button');
			elm_button_grid.classList.add('show-objects-grid');
			elm_graph_options.appendChild(elm_button_grid);
			
			let elm_button_grid_span = document.createElement('span');
			elm_button_grid_span.classList.add('icon');
			elm_button_grid.appendChild(elm_button_grid_span);
			
			[elm_button_graph, elm_button_grid].forEach(elm_button => {
				
				elm_button.addEventListener('click', function(e) {
					
					runElementSelectorFunction(obj_parent.elm_controls, '.timeline-graphs button', function(elm_inactive_button) {
						elm_inactive_button.classList.remove('active');
					});
		
					let do_grid_action = false;
					if (elm_button.classList.contains('show-objects-grid')) {
						
						if (is_grid && (id == graph_objects_type_id || (graph_conditions && id == 'conditions'))) {
								
							is_grid = false;
							
						} else {

							is_grid = true;
							do_grid_action = 'set';
							elm_button.classList.add('active');
						}
						
					} else {
				
						if (is_grid) {
								
							is_grid = false;
							do_grid_action = 'unset';	
						}				
					}
					
					if (id == 'conditions') {
						
						graph_objects_type_id = false;
						
						if (graph_conditions) {
							
							if (do_grid_action) {
								
								elm_button.classList.add('active');
								
							} else {
								
								graph_conditions = false;
							}
							
						} else {
							
							graph_conditions = true;
							elm_button.classList.add('active');
						}
						
					} else {
						
						graph_conditions = false;
						
						if (id == graph_objects_type_id) {
							
							if (do_grid_action) {
								
								elm_button.classList.add('active');
								
							} else {
								
								graph_objects_type_id = false;
							}
							
						} else {
				
							graph_objects_type_id = id;
							elm_button.classList.add('active');
						}
						
					}
					
					checkData();
				});				
			});
		}	
		
		ASSETS.getIcons(elm, ["chart-graph", "tiles-few"], function(data) {

			runElementSelectorFunction(obj_parent.elm_controls, '.show-objects > .icon', function(elm_icon) {
				elm_icon.innerHTML = data["chart-graph"];
			});
			runElementSelectorFunction(obj_parent.elm_controls, '.show-objects-grid > .icon', function(elm_icon) {
				elm_icon.innerHTML = data["tiles-few"];
			});
		});
		
		let elm_graph_options_figure = document.createElement('figure'); 
		elm_graph_options_figure.classList.add('timeline-graph-options');
		elm_legends.appendChild(elm_graph_options_figure);
		let elm_graph_options_list = document.createElement('dl'); 
		elm_graph_options_figure.appendChild(elm_graph_options_list);

		var elm_graph_options_list_item = document.createElement('li');
		elm_graph_options_list.appendChild(elm_graph_options_list_item);
				
		let elm_button_object_subs = document.createElement('button');
		elm_button_object_subs.setAttribute('type', 'button');
		elm_button_object_subs.classList.add('object-subs');
		elm_button_object_subs.classList.add('active');
		elm_graph_options_list_item.appendChild(elm_button_object_subs);

		let elm_button_object_subs_value = document.createTextNode(arr_labels.lbl_object_subs);
		elm_button_object_subs.appendChild(elm_button_object_subs_value);
	
		elm_button_object_subs.addEventListener('click', function(e) {
			
			if (bars_object_subs) {
				
				bars_object_subs = false;
				elm_button_object_subs.classList.remove('active');
				
			} else {
				
				bars_object_subs = true;
				elm_button_object_subs.classList.add('active');
			}		
			
			checkData();	
		});	
		
		if (graph_cumulative_option) {
				
			var elm_graph_options_list_item = document.createElement('li');
			elm_graph_options_list.appendChild(elm_graph_options_list_item);
			
			let elm_button_cumulative = document.createElement('button');
			elm_button_cumulative.setAttribute('type', 'button');
			elm_button_cumulative.classList.add('cumulative');
			
			if (graph_cumulative) {
				elm_button_cumulative.classList.add('active');
			}
			
			elm_graph_options_list_item.appendChild(elm_button_cumulative);

			let elm_button_cumulative_value = document.createTextNode(arr_labels.lbl_cumulative);
			elm_button_cumulative.appendChild(elm_button_cumulative_value);
			
			elm_button_cumulative.addEventListener('click', function(e) {
				
				if (graph_cumulative) {
					
					graph_cumulative = false;
					elm_button_cumulative.classList.remove('active');
					
				} else {
					
					graph_cumulative = true;
					elm_button_cumulative.classList.add('active');
				}			
				
				checkData();
			});
		}
		
		if (graph_relative_option) {
		
			var elm_graph_options_list_item = document.createElement('li');
			elm_graph_options_list.appendChild(elm_graph_options_list_item);
			
			let elm_button_relative = document.createElement('button');
			elm_button_relative.setAttribute('type', 'button');
			elm_button_relative.classList.add('relative');
			
			if (graph_relative) {
				elm_button_relative.classList.add('active');
			}
			
			elm_graph_options_list_item.appendChild(elm_button_relative);

			let elm_button_relative_value = document.createTextNode(arr_labels.lbl_relative);
			elm_button_relative.appendChild(elm_button_relative_value);

			elm_button_relative.addEventListener('click', function(e) {
				
				if (graph_relative) {
					
					graph_relative = false;
					elm_button_relative.classList.remove('active');
					
				} else {
					
					graph_relative = true;
					elm_button_relative.classList.add('active');
				}		
				
				checkData();	
			});
		}
		
		elm_graph_options = true;
		
		elm[0].addEventListener('mousemove', function(e) {
		
			var pos_hover = obj_map.getMousePosition();
			var do_redraw = false; 
	
			if (!pos_hover) {
				
				if (pos_hover_poll.x) {
					
					pos_hover_poll = pos_hover;
				}
				
			} else {
										
				var x_point = pos_hover.x * num_resolution;
				var y_point = pos_hover.y * num_resolution;
				var date_string = false;
				
				if (!pos_hover_poll || (Math.abs(x_point-pos_hover_poll.x) > 4 || Math.abs(y_point-pos_hover_poll.y) > 4)) {
				
					pos_hover_poll = pos_hover;
					var object_id = false;
					var condition_identifier = false;
					var type_id = false;
					var object_sub_details_id = false;
					var bar_id = false;
					var info_box = false;
					
					elm.addClass('hovering');
					
					// First check if hovering over circle
					for (var i = 0, len = arr_circles.length; i < len; i++) {
					
						if (stage_graphs.isPointInPath(arr_circles[i].path, x_point, y_point)) {
							
							if (arr_circles[i].object_id) {
								
								object_id = arr_circles[i].object_id;
								date_string = arr_circles[i].date_string;
							}
						
							if (arr_circles[i].condition_identifier) {
								
								condition_identifier = arr_circles[i].condition_identifier;
								date_string = arr_circles[i].date_string;
							}
							
							break;
						}
					}
					
					// No circle? Check paths
					if (!object_id && !condition_identifier) {
					
						for (var i = 0, len = arr_paths.length; i < len; i++) {
						
							if (stage_graphs.isPointInStroke(arr_paths[i].path, x_point, y_point)) {
								
								if (arr_paths[i].object_id) {
									
									object_id = arr_paths[i].object_id;
								}
								
								if (arr_paths[i].condition_identifier) {
									
									condition_identifier = arr_paths[i].condition_identifier;
								}
								
								break;
							}
						}
					}
					
					// No circle or path? Check bars
					if (!object_id && !condition_identifier && !is_grid) {
							
						for (var i = 0, len = arr_bars.length; i < len; i++) {
						
							if (stage_graphs.isPointInPath(arr_bars[i].path, x_point, y_point)) {
			
								type_id = arr_bars[i].type_id;
								object_sub_details_id = arr_bars[i].object_sub_details_id;
								date_string = arr_bars[i].date_string;
								
								bar_id = date_string+type_id+object_sub_details_id;
								
								break;
							}
						}
					}
					
					// No circle, path, or bar? Check info boxes
					if (!object_id && !condition_identifier && !object_sub_details_id) {
							
						for (var i = 0, len = arr_info_boxes.length; i < len; i++) {
						
							if (stage_graphs.isPointInPath(arr_info_boxes[i].path, x_point, y_point)) {
									
								info_box = true;
								
								if (arr_info_boxes[i].object_id) {
									
									object_id = arr_info_boxes[i].object_id;
								}
								
								if (arr_info_boxes[i].condition_identifier) {
									
									condition_identifier = arr_info_boxes[i].condition_identifier;
								}	
								
								if (arr_info_boxes[i].date_string) {
									
									date_string = arr_info_boxes[i].date_string;
								}	
																					
								break;
							}
						}
					}
			
					if (object_id) { // show object
						
						if (object_id !== hover_id) {

							do_redraw = true;
							hover_id = object_id;
							var arr_object_sub_ids = [];
							var name = arr_data.objects[hover_id].name;
							
							if (info_box) {

								str_title = '<ul>\
									<li>\
										<label>'+arr_labels.lbl_object+'</label>\
										<span>'+name+'</span>\
									</li>\
									<hr />\
									<li>\
										<label>'+arr_labels.lbl_date+'</label>\
										<span>'+arr_labels.lbl_amount+'</span>\
									</li>';
									
								var total_amount = 0;

								for (var key in arr_date_intervals) {
									
									var obj_date = arr_date_intervals[key];
									var date_label = getDateLabel(obj_date);
									
									var amount = 0;
									
					
									if (obj_date.objects[object_id]) {
										
										amount = obj_date.objects[object_id].amount;
										total_amount = total_amount + amount;
										arr_object_sub_ids = arr_object_sub_ids.concat(obj_date.objects[object_id].ids);
									}
									
									str_title = str_title+'<li>\
											<label>'+date_label+'</label>\
											<span>'+amount+'</span>\
										</li>';
								}
								
								str_title = str_title+'<hr />\
									<li>\
										<label>'+arr_labels.lbl_total+'</label>\
										<span>'+total_amount+'</span>\
									</li>';
								
								str_title = str_title+'</ul>';
								
							} else {
								
								if (date_string && arr_date_intervals[date_string].objects[hover_id]) {

									var obj_date = arr_date_intervals[date_string];
									var date_label = getDateLabel(obj_date);
									var amount = arr_date_intervals[date_string].objects[hover_id].amount;
									
									str_title = '<ul>\
										<li>\
											<label>'+arr_labels.lbl_date+'</label>\
											<span>'+date_label+'</span>\
										</li>\
										<li>\
											<label>'+arr_labels.lbl_object+'</label>\
											<span>'+name+'</span>\
										</li>\
										<li>\
											<label>'+arr_labels.lbl_amount+'</label>\
											<span>'+amount+'</span>\
										</li>';
									
									if (graph_relative) {
										
										var relative_amount = (Math.round(arr_date_intervals[date_string].objects[hover_id].relative_amount * 100) / 100);
										
										str_title = str_title+'<li>\
												<label>'+arr_labels.lbl_relative+' '+arr_labels.lbl_amountt+'</label>\
												<span>'+relative_amount+'</span>\
											</li>x_point';
									}
									
									str_title = str_title+'</ul>';
									
									arr_object_sub_ids = arr_date_intervals[date_string].objects[hover_id].ids;
									
								} else {
									
									// Hover over line can only show name
									
									var str_title = '<ul>\
										<li>\
											<label>'+arr_labels.lbl_object+'</label>\
											<span>'+name+'</span>\
										</li>\
									</ul>';
								
								}
							}
							
							elm[0].arr_link = {object_sub_ids: arr_object_sub_ids};
							elm[0].setAttribute('title', str_title);
							TOOLTIP.update();
						}
						
					} else if (condition_identifier) { // show condition
						
						if (condition_identifier !== hover_id) {

							do_redraw = true;
							hover_id = condition_identifier;
							var arr_object_sub_ids = [];
							
							if (info_box) {

								str_title = '<ul>\
									<li>\
										<label>'+arr_labels.lbl_condition+'</label>\
										<span>'+obj_conditions[condition_identifier].label+'</span>\
									</li>\
									<hr />\
									<li>\
										<label>'+arr_labels.lbl_date+'</label>\
										<span>'+arr_labels.lbl_amount+'</span>\
									</li>';
									
								var total_amount = 0;

								for (var key in arr_date_intervals) {
									
									var obj_date = arr_date_intervals[key];
									var date_label = getDateLabel(obj_date);
									
									var amount = 0;
					
									if (obj_date.conditions[condition_identifier]) {
										
										amount = obj_date.conditions[condition_identifier].amount;
										total_amount = total_amount + amount;
										
										arr_object_sub_ids = arr_object_sub_ids.concat(obj_date.conditions[condition_identifier].ids);
									}
									
									str_title = str_title+'<li>\
											<label>'+date_label+'</label>\
											<span>'+amount+'</span>\
										</li>';
								}
								
								str_title = str_title+'<hr />\
									<li>\
										<label>'+arr_labels.lbl_total+'</label>\
										<span>'+total_amount+'</span>\
									</li>';
								
								str_title = str_title+'</ul>';
								
							} else {
						
								if (date_string && arr_date_intervals[date_string].conditions[condition_identifier]) {

									var obj_date = arr_date_intervals[date_string];
									var date_label = getDateLabel(obj_date);
									var amount = arr_date_intervals[date_string].conditions[condition_identifier].amount;
																	
									str_title = '<ul>\
										<li>\
											<label>'+arr_labels.lbl_date+'</label>\
											<span>'+date_label+'</span>\
										</li>\
										<li>\
											<label>'+arr_labels.lbl_condition+'</label>\
											<span>'+obj_conditions[condition_identifier].label+'</span>\
										</li>\
										<li>\
											<label>'+arr_labels.lbl_amount+'</label>\
											<span>'+amount+'</span>\
										</li>';
									
									if (graph_relative) {
										
										var relative_amount = (Math.round(arr_date_intervals[date_string].conditions[condition_identifier].relative_amount * 100) / 100);
										
										str_title = str_title+'<li>\
												<label>'+arr_labels.lbl_relative+' '+arr_labels.lbl_amount+'</label>\
												<span>'+relative_amount+'</span>\
											</li>';
									}
									
									str_title = str_title+'</ul>';

									arr_object_sub_ids = arr_date_intervals[date_string].conditions[condition_identifier].ids;
																	
								} else {

									// Hover over line can only show name
									
									var str_title = '<ul>\
										<li>\
											<label>'+arr_labels.lbl_condition+'</label>\
											<span>'+obj_conditions[condition_identifier].label+'</span>\
										</li>\
									</ul>';								
								}
							}
							
							elm[0].arr_link = {object_sub_ids: arr_object_sub_ids};
							elm[0].setAttribute('title', str_title);
							TOOLTIP.update();
						}
						
					} else if (type_id && object_sub_details_id) { // show data of a sub-object in a date interval
						
						if (bar_id !== hover_id) {
						
							do_redraw = true;
							hover_id = bar_id;
							
							elm[0].arr_link = {object_sub_ids: arr_date_intervals[date_string].sorted_object_sub_ids[type_id][object_sub_details_id].ids};

							elm[0].setAttribute('title', '<span>'+arr_data.info.types[type_id].name+'</span> <span class="sub-name">'+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'</span> '+arr_date_intervals[date_string].sorted_object_sub_ids[type_id][object_sub_details_id].amount+'');
							TOOLTIP.update();

						}
						
					} else if (info_box && date_string) { // show all data for a single date interval
						
						var obj_date = arr_date_intervals[date_string];
						
						var str_title = '<ul>\
							<li>\
								<label>'+arr_labels.lbl_total+'</label>\
								<span>'+obj_date.unsorted_weighted_object_sub_ids_amount+'</span>\
							</li><hr />';
						
						if (graph_relative) {

							str_title = str_title+'<li>\
									<label>Mean</label>\
									<span>'+(Math.round(mean * 100) / 100)+'</span>\
								</li>\
								<li>\
									<label>Correction Value</label>\
									<span>'+(Math.round(obj_date.correction_value * 100) / 100)+'</span>\
								</li><hr />';
											
						}
						
						for (var type_id in obj_date.sorted_object_sub_ids) {
							
							var str_title_type = false;
							
							for (var object_sub_details_id in obj_date.sorted_object_sub_ids[type_id]) {
								
								var object_sub_details = obj_date.sorted_object_sub_ids[type_id][object_sub_details_id];
								
								if (object_sub_details.amount) {
															
									str_title_type = (str_title_type ? str_title_type : '')+'<li>\
										<label class="sub-name">'+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'</label>\
										<span>'+object_sub_details.amount+'x</span>\
									</li>';
								}
							}
							
							if (str_title_type) {
								str_title = str_title+'<li>\
									<label>'+arr_data.info.types[type_id].name+'</label>\
									<ul>'+str_title_type+'</ul>';
							}
						}
						
						str_title = str_title+'</li><hr />';

						if (graph_conditions && obj_conditions) { 
								
							let arr_ordered_conditions = [];
							
							for (const condition_identifier in obj_conditions) {
								
								const arr_date_condition = obj_date.conditions[condition_identifier];
								
								if (arr_date_condition && arr_date_condition.amount) {
									
									let amount = arr_date_condition.amount;
									let relative_amount = arr_date_condition.relative_amount;
									let str_condition = '<li>\
												<label>'+obj_conditions[condition_identifier].label+'</label>\
												<span>'+amount+'x'+(graph_relative ? ' (relative: '+(Math.round(relative_amount * 100) / 100)+')' : '')+'</span>\
											</li>';
											
									arr_ordered_conditions.push({'str_condition': str_condition, 'amount': (graph_relative ? relative_amount : amount)});
								}
							}
							
							if (arr_ordered_conditions.length) {
										
								arr_ordered_conditions.sort(function (a, b) {
									return b.amount - a.amount;
								});

								str_title = str_title+'<li>\
										<label>'+arr_labels.lbl_conditions+'</label>\
										<ul>';
								
								for (let i = 0; i < arr_ordered_conditions.length; i++) {
									
									str_title = str_title+arr_ordered_conditions[i].str_condition;	
								}		
												
								str_title = str_title+'</ul>\
									</li>';
							}
						}
						
						if (graph_objects_type_id) {
	
							let arr_ordered_objects = [];
							
							for (let object_id in obj_date.objects) {
								
								let name = arr_data.objects[object_id].name;
								let amount = obj_date.objects[object_id].amount;
								let relative_amount = obj_date.objects[object_id].relative_amount;
								let str_object = '<li>\
											<label>'+name+'</label>\
											<span>'+amount+'x'+(graph_relative ? ' (relative: '+(Math.round(relative_amount * 100) / 100)+')' : '')+'</span>\
										</li>';
										
								arr_ordered_objects.push({'str_object': str_object, 'amount': (graph_relative ? relative_amount : amount)});
							}
							
							if (arr_ordered_objects.length) {
								
								arr_ordered_objects.sort(function (a, b) {
									return b.amount - a.amount;
								});

								str_title = str_title+'<li>\
										<label>'+arr_labels.lbl_objects+'</label>\
										<ul>';
								
								for (let i = 0; i < arr_ordered_objects.length; i++) {
									
									str_title = str_title+arr_ordered_objects[i].str_object;	
								}		
												
								str_title = str_title+'</ul>\
									</li>';
							}
						}
						
						str_title = str_title+'</ul>';
						
						elm[0].setAttribute('title', str_title);
						
						
						TOOLTIP.update();					
					
					} else {
						
						if (hover_id) {
							do_redraw = true;
						}
					
						hover_id = false;
						elm[0].removeAttribute('title');
						elm.removeClass('hovering');
						TOOLTIP.update();
	
						elm[0].arr_link = false;
						elm[0].arr_info_box = false;						
					}
				}
			}

			if (do_redraw) {
			
				doDraw();
			}
		});
	};
				
	 var drawData = function(dateint_range) {
		
		date_int_range = dateint_range;
		date_int_absolute_range = {min: DATEPARSER.dateInt2Absolute(dateint_range.min), max: DATEPARSER.dateInt2Absolute(dateint_range.max)};
		
		checkData();
	};
	
	var checkData = function() {

		resizeDrawer();
		
		setDateIntervals(); 

		max_object_subs_amount = 1;
		max_objects_amount = 1;
		total_amount = 0
		
		setCheckObjectSubs();
		
		if (graph_relative) {
			
			max_relative_amount = 0;
			
			if (parseInt(number_of_dates) && parseInt(total_amount)) {

				mean = total_amount/number_of_dates;
			}
			
			setRelativeAmounts();
		}
		
		if (graph_cumulative) {
			
			max_cumulative_amount = 0;
			max_cumulative_amount = 0;
			
			setCumulativeAmounts();
		}
		
		num_height_max = size_drawer.height - padding.top - padding.bottom;

		doDraw();		
	}
	
	var doDraw = function() {
		
		if (obj_conditions) {
			resetConditions();
		}		
			
		renderer.clearRect(0, 0, size_view.width, size_view.height);
		stage_graphs.clearRect(0, 0, size_view.width, size_view.height);
		drawer.clearRect(0, 0, size_view.width, size_view.height);
		stage_timeline.clearRect(0, 0, size_view.width, size_view.height);

		arr_circles = [];
		arr_paths = [];
		arr_bars = [];
		arr_info_boxes = [];
		
		drawAxes();
		
		var drawer_pattern = drawer.createPattern(stage_timeline_canvas, 'no-repeat');
		drawer.fillStyle = drawer_pattern;
		drawer.fillRect(0, 0, size_drawer.width, size_drawer.height);

		if (bars_object_subs) {
				
			drawBarsObjectSubs();
		}
		
		if (graph_objects_type_id) {

			drawGraphObjects();
		}
		
		if (graph_conditions) {
				
			drawGraphConditions();
		}
		
		var renderer_pattern = renderer.createPattern(drawer_canvas, 'repeat');
		renderer.fillStyle = renderer_pattern;
		renderer.fillRect(0, 0, size_view.width, size_view.height);

		var renderer_pattern = renderer.createPattern(stage_graphs_canvas, 'no-repeat');
		renderer.fillStyle = renderer_pattern;
		renderer.fillRect(0, 0, size_view.width, size_view.height);	
			
		if (is_grid) {
			
			var number_of_cells = number_of_columns * number_of_rows; 
			var clear_cells = number_of_cells - number_of_plots;
			
			renderer.clearRect((number_of_columns - clear_cells) * size_drawer.width, (number_of_rows - 1) * size_drawer.height, size_drawer.width * clear_cells, size_drawer.height);
		}
	}

	var setConditions = function() {
		
		var has_conditions = false;
		obj_conditions = {};
		
		for (const condition_identifier in arr_data.legend.conditions) {
			
			const arr_condition = arr_data.legend.conditions[condition_identifier];
			
			obj_conditions[condition_identifier] = {label: (arr_condition.label ? arr_condition.label : 'N/A'), color: arr_condition.color, icon: arr_condition.icon, weight: arr_condition.weight, positions: [], path_elm: false, circle_elms: []};
			has_conditions = true;
		}
		
		if (!has_conditions) {
			obj_conditions = false;
		}
		graph_conditions = true;
	}
	
	var resetConditions = function() {
	
		for (const condition_identifier in obj_conditions) {
			
			obj_conditions[condition_identifier].positions = [];
			obj_conditions[condition_identifier].path_elm = false;
			obj_conditions[condition_identifier].circle_elms = [];
		}
	}

	var setDateIntervals = function() {
		
		number_of_dates = 0;
		arr_date_intervals = {};
		
		var min_date = DATEPARSER.int2Date(date_int_range.min),
		max_date = DATEPARSER.int2Date(date_int_range.max),
		range = max_date.getFullYear().toString() - min_date.getFullYear(),
		new_interval = {};	
		
		var amount_of_bars = size_drawer.width / num_width_bar;

		if (range <= 1) {

			new_interval.name = 'day';
			new_interval.format = 'yymmdd';
			new_interval.format_text = 'dd-mm-yy';
			new_interval.slice = -4;
			new_interval.steps = 0;
			
		} else if (range/0.083 <= amount_of_bars) {

			new_interval.name = 'month';
			new_interval.format = 'yymm';
			new_interval.format_text = 'mm-yy';
			new_interval.slice = -6;
			new_interval.steps = 0;
			
		} else if (range <= amount_of_bars) {

			new_interval.name = 'year';
			new_interval.format = 'yy';
			new_interval.format_text = 'yy';
			new_interval.slice = -8;
			new_interval.steps = 1;
			
		} else if (range/10 <= amount_of_bars) {

			new_interval.name = 'decade';
			new_interval.format = 'yy';
			new_interval.format_text = 'yy';
			new_interval.slice = -9;
			new_interval.steps = 10;
			
		} else if (range/100 <= amount_of_bars) {
			
			new_interval.name = 'century';
			new_interval.format = 'yy';
			new_interval.format_text = 'yy';
			new_interval.slice = -10;
			new_interval.steps = 100;
			
		} else {
			
			new_interval.name = 'millennium';
			new_interval.format = 'yy';
			new_interval.format_text = 'yy';
			new_interval.slice = -11;
			new_interval.steps = 1000;
			
		}
		
		if (new_interval.name != interval.name) {
			
			interval = new_interval;
			
		}
		
		while (min_date <= max_date) {	
						
			var object_sub_details_ids = {};
			
			for (var object_sub_details_id in arr_data.legend.object_subs) {
				
				var type_id = getObjectSubTypeId(object_sub_details_id);
				
				if (type_id) {
					
					if (!object_sub_details_ids[type_id]) {
						
						object_sub_details_ids[type_id] =  {}
					}
					
					object_sub_details_ids[type_id][object_sub_details_id] = {type_id: type_id, object_sub_details_id: object_sub_details_id, ids: [], amount: 0};
				}
			}
			
			var date = {date: min_date, sorted_object_sub_ids: object_sub_details_ids, unsorted_weighted_object_sub_ids_amount: 0, string: '', conditions: {}, objects: {}, pos: {}, weight: 0, correction_value: 0};

			if (interval.name == 'day') {
				date.string = DATEPARSER.date2Integer(min_date).toString();
			} else {
				date.string = DATEPARSER.date2Integer(min_date).toString().slice(0, interval.slice);
			}
			
			if (parseInt(date.string)) {
				
				arr_date_intervals['date_'+date.string] = date;

				number_of_dates++;
			}
			
			var next = new Date(min_date);
			
			if (interval.name == 'day') {
				
				next.setDate(next.getDate() + 1);
				
			} else if (interval.name == 'month') {
				
				if (next.getMonth() == 11) {
					
					next = DATEPARSER.newDate(next.getFullYear() + 1, 0, 1);
					
				} else {
					
					next = DATEPARSER.newDate(next.getFullYear(), next.getMonth() + 1, 1);

				}	
				
			} else {
				
				next = DATEPARSER.newDate(next.getFullYear() + interval.steps, 0, 1);
					
			} 

			min_date = next;
			
		}
	}
	
	var checkInRange = function(arr_object_sub) {
						
		if (obj_parent.obj_data.arr_inactive_object_sub_details[arr_object_sub.object_sub_details_id]) {
			return false;
		}
		
		var len = obj_parent.obj_data.arr_loop_inactive_conditions.length;
		
		if (len) {
			
			var arr_conditions = arr_object_sub.style.conditions;
			
			if (arr_conditions) {
				
				for (var i = 0; i < len; i++) {
					
					if (arr_conditions[obj_parent.obj_data.arr_loop_inactive_conditions[i]]) {
						return false;
					}
				}
			}
			
			arr_conditions = arr_data.objects[arr_object_sub.object_id].style.conditions;
			
			if (arr_conditions) {
				
				for (var i = 0; i < len; i++) {
					
					if (arr_conditions[obj_parent.obj_data.arr_loop_inactive_conditions[i]]) {
						return false;
					}
				}
			}
		}

		return true;
	};
	
	var setCheckObjectSubs = function() {

		// Single date sub objects
		for (let i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
			
			var date = arr_data.date.arr_loop[i];
			var dateinta = DATEPARSER.dateInt2Absolute(date);
			var in_range_date = (dateinta >= date_int_absolute_range.min && dateinta <= date_int_absolute_range.max);
			var arr_object_subs = arr_data.date[date];
		
			for (let j = 0, len_j = arr_object_subs.length; j < len_j; j++) {
				
				var in_range = in_range_date;
				
				if (in_range) {
					
					var arr_object_sub = arr_data.object_subs[arr_object_subs[j]];
					
					in_range = checkInRange(arr_object_sub);
				}
				
				setObjectSub(arr_object_subs[j], !in_range);
			}
		}
		
		// Sub objects with a date range
		for (let i = 0, len = arr_data.range.length; i < len; i++) {
			
			var arr_object_sub = arr_data.object_subs[arr_data.range[i]];
			
			var dateinta_start = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
			var dateinta_end = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
			
			var in_range = ((dateinta_start >= date_int_absolute_range.min && dateinta_start <= date_int_absolute_range.max) || (dateinta_end >= date_int_absolute_range.min && dateinta_end <= date_int_absolute_range.max) || (dateinta_start < date_int_absolute_range.min && dateinta_end > date_int_absolute_range.max));

			if (in_range) {
				in_range = checkInRange(arr_object_sub);
			}
			
			setObjectSub(arr_data.range[i], !in_range);
		}
		
	};
	
	var setObjectSub = function(object_sub_id, remove) {
		
		if (remove) {
			return false;
		}
		
		var object_sub = arr_data.object_subs[object_sub_id];
		
		if (object_sub.object_sub_details_id == 'object') {
			return false;
		}

		if (interval.name == 'day') {
			var object_sub_date_start_interval = parseInt(object_sub.date_start);
			var object_sub_date_end_interval = parseInt(object_sub.date_end);
		} else {
			var object_sub_date_start_interval = parseInt(String(object_sub.date_start).slice(0, interval.slice));
			var object_sub_date_end_interval = parseInt(String(object_sub.date_end).slice(0, interval.slice));
		}
		
		if (object_sub_date_start_interval == object_sub_date_end_interval) {

			addSubToInterval(object_sub_id, 'date_'+object_sub_date_start_interval);
			
		} else {
			
			for (var key in arr_date_intervals) {
				
				var date = DATEPARSER.date2Integer(arr_date_intervals[key].date);
				
				if (interval.name != 'day') {
					
					date = parseInt(String(date).slice(0, interval.slice));
				}
				
				if (date >= object_sub_date_start_interval && date <= object_sub_date_end_interval) {

					addSubToInterval(object_sub_id, key);
				}
			}
		}
	}
	
	var addSubToInterval = function(object_sub_id, key) {

		if (arr_date_intervals[key]) {
			
			var object_sub = arr_data.object_subs[object_sub_id];
			var object = arr_data.objects[object_sub.object_id];
			var type_id = getObjectSubTypeId(object_sub.object_sub_details_id);
			
			if (arr_date_intervals[key].sorted_object_sub_ids[type_id] && arr_date_intervals[key].sorted_object_sub_ids[type_id][object_sub.object_sub_details_id]) {
				
				arr_date_intervals[key].unsorted_weighted_object_sub_ids_amount += (object_sub.style.weight ? object_sub.style.weight : 1);
				
				if (arr_date_intervals[key].unsorted_weighted_object_sub_ids_amount > max_object_subs_amount) {
					max_object_subs_amount = arr_date_intervals[key].unsorted_weighted_object_sub_ids_amount;
				}
				
				total_amount += (object_sub.style.weight ? object_sub.style.weight : 1);
				
				arr_date_intervals[key].sorted_object_sub_ids[type_id][object_sub.object_sub_details_id].amount += (object_sub.style.weight ? object_sub.style.weight : 1);
				arr_date_intervals[key].sorted_object_sub_ids[type_id][object_sub.object_sub_details_id].ids.push(object_sub_id);	
			}
			
			// Object Sub Style Conditions are inherited from Object Style Conditions (see MapData 251)
			if (object_sub.style.conditions) {
				addConditionsToInterval(object_sub.style.conditions, object_sub, object_sub_id, key);
			}
			
			// Plot Objects of Type as paths
			if (graph_objects_type_id) {
				
				let object_sub_connected_object_ids_amount = object_sub.connected_object_ids.length;

				if (object_sub_connected_object_ids_amount == 1) {
					
					addObjectToInterval(object_sub.connected_object_ids[0], object_sub_id, key);
					
				} else if (object_sub_connected_object_ids_amount > 1) { 
					
					for (let i = 0; i < object_sub_connected_object_ids_amount; i++) {
						
						addObjectToInterval(object_sub.connected_object_ids[i], object_sub_id, key);
					}
				}
			}
		}
	}
	
	// Plot Objects of Type as paths	
	var addObjectToInterval = function(object_id, object_sub_id, key) {
		
		var arr_date_condition = arr_date_intervals[key].objects[object_id];
	
		if (arr_data.objects[object_id].type_id != graph_objects_type_id) {
			
			if (arr_date_condition) {
				delete arr_date_intervals[key].objects[object_id];
			}
			
			return;
		}

		if (!arr_date_condition) {
			
			arr_date_condition = {ids: [], amount: 0, relative_amount: 0, cumulative_amount: 0};
			
			arr_date_intervals[key].objects[object_id] = arr_date_condition;			
		}
			
		arr_date_condition.amount += 1;
		arr_date_condition.ids.push(object_sub_id);
		
		if (arr_date_condition.amount > max_objects_amount) {
			max_objects_amount = arr_date_condition.amount;
		}
	}
	
	var addConditionsToInterval = function(conditions, object_sub, object_sub_id, key) {
		
		for (const condition_identifier in conditions) {
			
			var arr_date_condition = arr_date_intervals[key].conditions[condition_identifier];
			
			if (!arr_date_condition) {
				
				arr_date_condition = {ids: [], amount: 0, relative_amount: 0, cumulative_amount: 0};
				
				arr_date_intervals[key].conditions[condition_identifier] = arr_date_condition;
			}
			
			arr_date_condition.amount += (object_sub.style.weight ? object_sub.style.weight : 1);
			arr_date_condition.ids.push(object_sub_id);
		}
	}
	
	var setRelativeAmounts = function() {
		
		for (const key in arr_date_intervals) {
			
			const obj_date = arr_date_intervals[key];
			const unsorted_weighted_object_sub_ids_amount = obj_date.unsorted_weighted_object_sub_ids_amount;
			var num_correction_value = false;
			
			if (unsorted_weighted_object_sub_ids_amount) {
				
				num_correction_value = (mean / unsorted_weighted_object_sub_ids_amount);
				obj_date.correction_value = num_correction_value;
			}
			
			if (graph_conditions) {
			
				for (const condition_identifier in obj_conditions) {
					
					const arr_date_condition = obj_date.conditions[condition_identifier];
					
					if (arr_date_condition) {
						
						const num_relative_amount = (arr_date_condition.amount * (num_correction_value ? num_correction_value : 1));

						arr_date_condition.relative_amount = num_relative_amount;

						if (num_relative_amount > max_relative_amount) {
							max_relative_amount = num_relative_amount;
						}
					}
				}
			}
			
			if (graph_objects_type_id) {

				for (const object_id in arr_data.objects) {
					
					const arr_object = arr_data.objects[object_id];
					
					if (graph_objects_type_id != arr_object.type_id) {
						continue;
					}
					
					if (obj_date.objects[object_id]) {
						
						const num_relative_amount = (obj_date.objects[object_id].amount * (num_correction_value ? num_correction_value : 1));

						obj_date.objects[object_id].relative_amount = num_relative_amount;

						if (num_relative_amount > max_relative_amount) {
							max_relative_amount = num_relative_amount;
						}
					}
				}		
			}
		}
	}
	
	var setCumulativeAmounts = function() {
		
		var previous_amount = {};
		
		for (const key in arr_date_intervals) {
			
			const obj_date = arr_date_intervals[key];

			if (graph_conditions) {
				
				for (var condition_identifier in obj_conditions) {
					
					if (!previous_amount[condition_identifier]) {
						previous_amount[condition_identifier] = 0;
					}
					
					if (obj_date.conditions[condition_identifier]) {
						
						if (graph_relative) {
							
							var cumulative_amount = obj_date.conditions[condition_identifier].relative_amount + previous_amount[condition_identifier];
							
						} else {
							
							var cumulative_amount = obj_date.conditions[condition_identifier].amount + previous_amount[condition_identifier];
						}
						
						obj_date.conditions[condition_identifier].cumulative_amount = cumulative_amount;

						if (graph_relative) {

							if (cumulative_amount > max_cumulative_amount) {
								max_cumulative_amount = cumulative_amount;
							}
							
						} else {
							
							if (cumulative_amount > max_object_subs_amount) {
								max_object_subs_amount = cumulative_amount;
							}
						}
						
						previous_amount[condition_identifier] = cumulative_amount;
					
					} else {
						
						obj_date.conditions[condition_identifier] = {ids: [], amount: 0, relative_amount: 0, cumulative_amount: previous_amount[condition_identifier]}
					}
				}
			}
			
			if (graph_objects_type_id) {

				for (var object_id in arr_data.objects) {
					
					var object = arr_data.objects[object_id];
					
					if (graph_objects_type_id != object.type_id) {
						continue;
					}
					
					if (!previous_amount[object_id]) {
						previous_amount[object_id] = 0;
					}
					
					if (obj_date.objects[object_id]) {
						
						if (graph_relative) {
							
							var cumulative_amount = obj_date.objects[object_id].relative_amount + previous_amount[object_id];
							
						} else {
							
							var cumulative_amount = obj_date.objects[object_id].amount + previous_amount[object_id];
						}
						
						obj_date.objects[object_id].cumulative_amount = cumulative_amount;

						if (graph_relative) {

							if (cumulative_amount > max_cumulative_amount) {
								max_cumulative_amount = cumulative_amount;
							}
							
						} else {
							
							if (bars_object_subs) {
	
								if (cumulative_amount > max_object_subs_amount) {
									max_object_subs_amount = cumulative_amount;
								}	
														
							} else {
								
								if (cumulative_amount > max_objects_amount) {
									max_objects_amount = cumulative_amount;
								}
							}
						}
						
						previous_amount[object_id] = cumulative_amount;
						
					} else {
						
						obj_date.objects[object_id] = {ids: [], amount: 0, relative_amount: 0, cumulative_amount: previous_amount[object_id]};
					}
					
				}				
			}
		}
	}
	
	var drawAxes = function() {
		
		var line_x_start = padding.left;
		var line_x_end = size_drawer.width - padding.right;
		var line_y = size_drawer.height - padding.bottom;
			
		stage_timeline.beginPath();
		stage_timeline.moveTo(line_x_start, line_y);
		stage_timeline.lineTo(line_x_end, line_y);
		stage_timeline.lineWidth = 1;
		stage_timeline.strokeStyle = '#444';
		stage_timeline.stroke();

		var line_width = line_x_end - line_x_start;
		var bar_x_margin = (line_width - (num_width_bar * number_of_dates)) / number_of_dates;
		var i = 0;
			
		for (var key in arr_date_intervals) {
			
			var obj_date = arr_date_intervals[key];
			var date_string = obj_date.string;	
			var text = true;
									
			var x = line_x_start + (i * (num_width_bar + (i == 0 ? bar_x_margin/2 : bar_x_margin)));	
			
			arr_date_intervals[key].pos.x = x;
			arr_date_intervals[key].pos.y = line_y;
			
			if (line_width/number_of_dates < 100) {
				
				if (interval.name == 'day') {
					
					if (i % 20) {
						text = false;
					}
						
				} else if (interval.name == 'month') {
				
					if (i % 4) {
						text = false;
					}
				
				} else {
					
					if (i % 2) {
						text = false;
					}
				}	
			} 

			if (text) {
			
				var date_label = getDateLabel(obj_date);

				stage_timeline.beginPath();
				stage_timeline.moveTo(x + num_width_bar/2, line_y);
				stage_timeline.lineTo(x + num_width_bar/2, line_y + (12 * num_resolution));
				stage_timeline.lineWidth = 1;
				stage_timeline.strokeStyle = '#aaa';
				stage_timeline.stroke();
				
				if (!is_grid) {
					
					stage_timeline.textAlign = 'center'; 
					stage_timeline.fillStyle = color_text_date;
					stage_timeline.font = (size_text * num_resolution)+'px '+font_family;
					stage_timeline.fillText(date_label, x + num_width_bar/2, line_y + (30 * num_resolution));
				} 
				
			} else {
				
				stage_timeline.beginPath();
				stage_timeline.moveTo(x + num_width_bar/2, line_y);
				stage_timeline.lineTo(x + num_width_bar/2, line_y + (7 * num_resolution));
				stage_timeline.lineWidth = 1;
				stage_timeline.strokeStyle = '#aaa';
				stage_timeline.stroke();

			}
			
			if (!is_grid) {
				
				let path = new Path2D();	
				path.rect(x, line_y, num_width_bar, 30 * num_resolution);	
				stage_graphs.beginPath();	
				stage_graphs.fillStyle = 'rgba(0,0,0,0)';
				stage_graphs.fill(path);
				arr_info_boxes.push({'date_string': key, 'path': path});
			}
				
			i++;
		}
	}
	
	var drawBarsObjectSubs = function() {
		
		for (var key in arr_date_intervals) {
			
			var obj_date = arr_date_intervals[key];
			var unsorted_weighted_object_sub_ids_amount = obj_date.unsorted_weighted_object_sub_ids_amount;
			
			var x = obj_date.pos.x;
			var y = obj_date.pos.y;
			
			var date_string = obj_date.string;
			var sorted_object_sub_ids = obj_date.sorted_object_sub_ids;
			var num_height_bar = (unsorted_weighted_object_sub_ids_amount / max_object_subs_amount) * num_height_max;
			var bar_y = y - num_height_bar;

			for (var type_id in sorted_object_sub_ids) {
				
				for (var object_sub_details_id in sorted_object_sub_ids[type_id]) {
					
					if (sorted_object_sub_ids[type_id][object_sub_details_id].ids.length) {

						const bar_id = key+type_id+object_sub_details_id;
						const box_height = (sorted_object_sub_ids[type_id][object_sub_details_id].amount / max_object_subs_amount) * num_height_max;
						
						let obj_color = false;
						let num_opacity = opacity_bar;

						if (is_grid) {	
							num_opacity = num_opacity * 0.5;
						}
						if (bar_id == hover_id) {
							num_opacity = 1;								
						}

						if (color_bar) {
							obj_color = parseCSSColor(color_bar);
						} else {
							obj_color = arr_data.legend.object_subs[object_sub_details_id].color;
						}
						
						const str_color = 'rgba('+obj_color.r+','+obj_color.g+','+obj_color.b+', '+num_opacity+')';
												
						const path = new Path2D();	
						path.rect(x + (num_width_bar/2)-((num_width_bar/2)/2), bar_y, num_width_bar/2, box_height);		
						drawer.fillStyle = str_color;
						drawer.fill(path);
						
						arr_bars.push({date_string: key, type_id: type_id, object_sub_details_id: object_sub_details_id, path: path});
							
						bar_y = bar_y + box_height;	
					}
				}
			}
		}
	}
	
	var drawGraphObjects = function() {

		var plots = 0;
		var cols = 0;
		var rows = 0;
		
		var arr_object_ids = Object.keys(arr_data.objects);
		arr_object_ids.sort(function(a, b) {
			return arr_data.objects[a].sort - arr_data.objects[b].sort;
		});
		
		for (let count_objects = 0, len_objects = arr_object_ids.length; count_objects < len_objects; count_objects++) {
			
			var object_id = arr_object_ids[count_objects];
			var arr_object = arr_data.objects[object_id];
			var arr_positions = [];
			var num_positions = 0;
			
			if (graph_objects_type_id != arr_object.type_id) {
				continue;
			}
			
			if (number_of_columns > 0 && cols == number_of_columns) {
				cols = 0;
				rows++;
			}
			
			for (var key in arr_date_intervals) {
				
				var obj_date = arr_date_intervals[key];
				var x = obj_date.pos.x + (size_drawer.width * cols);
				var y = obj_date.pos.y + (size_drawer.height * rows);
				var cx = x + num_width_bar/2;
				
				var amount = 0;
				var max = 0;
					
				if (obj_date.objects[object_id]) {

					if (graph_cumulative) {
						
						amount = obj_date.objects[object_id].cumulative_amount;
						
						if (graph_relative) {
							
							max = max_cumulative_amount;
							
						} else {

							if (bars_object_subs) {
								
								max = max_object_subs_amount;
								
							} else {
								
								max = max_objects_amount;
							}
						}
						
					} else if (graph_relative) {
						
						amount = obj_date.objects[object_id].relative_amount;
						max = max_relative_amount;
						
					} else {
						
						amount = obj_date.objects[object_id].amount;
						
						if (bars_object_subs) {
							
							max = max_object_subs_amount;
							
						} else {
							
							max = max_objects_amount;
						}
						
					}
					
					num_positions++;
				}
					
				var cy = y - (amount ? (amount / max) * num_height_max : 0);
					
				arr_positions.push({'cx': cx, 'cy': cy, 'date_string': key});
			}

			// Object has to appear in at least two bars to be drawn
			if (!is_grid && num_positions < 2) {
				continue;
			}
			
			var color = '#aaa';
			var line_width = 1 * num_resolution;
			var radius = 3 * num_resolution;
			
			if (is_grid) {
				radius = 2 * num_resolution;
				color = '#cc0000';
			}
			
			if (object_id == hover_id) {
				
				color = '#ff0000';
				line_width = 2 * num_resolution;
				
				if (!is_grid) {
					
					radius = 5 * num_resolution;
				}
			}

			stage_graphs.beginPath();
			var path = new Path2D();
			for (let i = 0, len = arr_positions.length; i < len; i++) {
				
				var cx = arr_positions[i].cx;
				var cy = arr_positions[i].cy;
				
				if (!i) {
					stage_graphs.moveTo(cx, cy);
					path.moveTo(cx, cy);
				} else {
					stage_graphs.lineTo(cx, cy);
					path.lineTo(cx, cy);
				}
			}				
			stage_graphs.lineWidth = line_width;
			stage_graphs.strokeStyle = color;
			stage_graphs.stroke();
			
			stage_graphs.lineWidth = 4 * num_resolution;
			stage_graphs.strokeStyle = 'rgba(0,0,0,0)';
			stage_graphs.stroke(path);

			
			if (!is_grid) { // only allow mouseover when multiple object paths are shown together
			
				arr_paths.push({'object_id': object_id, 'path': path});
			}
			
			for (let i = 0, len = arr_positions.length; i < len; i++) {
				
				var cx = arr_positions[i].cx;
				var cy = arr_positions[i].cy;
				var date_string = arr_positions[i].date_string;
				
				var path = new Path2D();
				path.arc(cx, cy, radius, 0, 2 * Math.PI, false);
				stage_graphs.fillStyle = color;
				stage_graphs.fill(path);
				
				arr_circles.push({'object_id': object_id, 'date_string': date_string, 'path': path});
			}

			
			if (is_grid) {
				
				var text_x = arr_date_intervals[arr_positions[0].date_string].pos.x + (size_drawer.width * cols);
				var text_y = arr_date_intervals[arr_positions[0].date_string].pos.y + (size_drawer.height * rows);
				
				stage_graphs.save();
				
				stage_graphs.rect(text_x, text_y, size_drawer.width - (15 * num_resolution), 20 * num_resolution);
				stage_graphs.stroke();
				stage_graphs.clip();
				stage_graphs.fillStyle = color_text_label;
				stage_graphs.textAlign = 'left'; 
				stage_graphs.font = (size_text * num_resolution)+'px '+font_family;
				stage_graphs.fillText(arr_object.name, text_x, text_y + (15 * num_resolution)); 
				
				stage_graphs.restore();
				
				var path = new Path2D();	
				path.rect(text_x, text_y, size_drawer.width - 15, 20);		
				stage_graphs.fillStyle = 'rgba(0,0,0,0)';
				stage_graphs.fill(path);
				arr_info_boxes.push({'object_id': object_id, 'path': path});
			}
			
			if (number_of_plots > 1) {
				
				plots++
				cols++
			}
		}
	}
		
	var drawGraphConditions = function() {

		var plots = 0;
		var cols = 0;
		var rows = 0;
		
		for (const condition_identifier in obj_conditions) {
			
			const arr_condition = obj_conditions[condition_identifier];
			var arr_positions = [];

			if (number_of_columns > 0 && cols == number_of_columns) {
				cols = 0;
				rows++;
			}	
				
			for (var key in arr_date_intervals) {
				
				var obj_date = arr_date_intervals[key];
				var x = obj_date.pos.x + (size_drawer.width * cols);
				var y = obj_date.pos.y + (size_drawer.height * rows);
				var cx = x + num_width_bar/2;
				var amount = 0;
				var max = 0;
				
				if (graph_cumulative) {
					
					amount = (obj_date.conditions[condition_identifier] ? obj_date.conditions[condition_identifier].cumulative_amount : 0);
					
					if (graph_relative) {
						max = max_cumulative_amount;
					} else {
						max = max_object_subs_amount;
					}
					
				} else if (graph_relative) {
					
					amount = (obj_date.conditions[condition_identifier] ? obj_date.conditions[condition_identifier].relative_amount : 0);
					max = max_relative_amount;
					
				} else {
					
					amount = (obj_date.conditions[condition_identifier] ? obj_date.conditions[condition_identifier].amount : 0);
					max = max_object_subs_amount;
					
				}
					
				var cy = y - (amount / max) * num_height_max;
					
				arr_positions.push({'cx': cx, 'cy': cy, 'date_string': key});
			}	

			var color = '#aaa';
			var line_width = 1 * num_resolution;
			var radius = 3 * num_resolution;
			
			if (condition_identifier == hover_id) {
				
				color = arr_condition.color;
				line_width = 2 * num_resolution;
				
				if (!is_grid) {
					
					radius = 4 * num_resolution;
				}
			}
				
			let path = new Path2D();
			let path_hover = new Path2D();
			
			for (let i = 0, len = arr_positions.length; i < len; i++) {
				
				let cx = arr_positions[i].cx;
				let cy = arr_positions[i].cy;
				
				if (!i) {
					path.moveTo(cx, cy);
					path_hover.moveTo(cx, cy);
				} else {
					path.lineTo(cx, cy);
					path_hover.lineTo(cx, cy);
				}
			}
			
			stage_graphs.beginPath();
			stage_graphs.lineWidth = line_width;
			stage_graphs.strokeStyle = color;
			stage_graphs.stroke(path);
			
			stage_graphs.beginPath();
			stage_graphs.lineWidth = 5;
			stage_graphs.strokeStyle = 'rgba(0,0,0,0)';
			stage_graphs.stroke(path_hover);
			
			arr_paths.push({condition_identifier: condition_identifier, path: path_hover});
			stage_graphs.beginPath();
				
			for (let i = 0, len = arr_positions.length; i < len; i++) {
				
				let cx = arr_positions[i].cx;
				let cy = arr_positions[i].cy;
				let date_string = arr_positions[i].date_string;

				if (arr_condition.icon) {
					
					let resource = arr_condition.icon;
					let arr_resource = ASSETS.getMedia(resource);
					let scale_icon = (arr_resource.width / arr_resource.height);
					let icon_size = radius * 4;
					var width_icon = icon_size * scale_icon;						
					stage_graphs.drawImage(arr_resource.image, cx - (width_icon / 2), cy - (icon_size / 2), width_icon, icon_size);

				} else {
				
					let path = new Path2D();
					path.arc(cx, cy, radius, 0, 2 * Math.PI, false);
					
					stage_graphs.beginPath();
					stage_graphs.fillStyle = (arr_condition.icon ? 'rgba(0,0,0,0)' : arr_condition.color);
					stage_graphs.fill(path);
					
					stage_graphs.beginPath();
					stage_graphs.lineWidth = 1;
					stage_graphs.strokeStyle = (arr_condition.icon ? 'rgba(0,0,0,0)' : '#444');
					stage_graphs.stroke(path);
				}
				
				var path_hover_box = new Path2D();	
				path_hover_box.rect(cx - (4 * num_resolution), cy - (4 * num_resolution), 8 * num_resolution, 8 * num_resolution);	
				stage_graphs.fillStyle = 'rgba(0,0,0,0)';
				stage_graphs.fill(path_hover_box);	
							
				arr_circles.push({condition_identifier: condition_identifier, date_string: date_string, path: path_hover_box});
			}
			
			if (is_grid) {
				
				var text_x =  arr_date_intervals[arr_positions[0].date_string].pos.x + (size_drawer.width * cols);
				var text_y = arr_date_intervals[arr_positions[0].date_string].pos.y + (size_drawer.height * rows);
				
				stage_graphs.save();
				
				stage_graphs.beginPath();
				stage_graphs.rect(text_x, text_y, size_drawer.width - (15 * num_resolution), 20 * num_resolution);
				stage_graphs.strokeStyle = 'rgba(0,0,0,0)';
				stage_graphs.stroke();
				stage_graphs.clip();
				stage_graphs.fillStyle = color_text_label;
				stage_graphs.textAlign = 'left';
				stage_graphs.font = (size_text * num_resolution)+'px '+font_family; 
				stage_graphs.fillText(arr_condition.label, text_x, text_y + (15 * num_resolution)); 
				
				stage_graphs.restore();
				
				let path = new Path2D();	
				path.rect(text_x, text_y, size_drawer.width - (15 * num_resolution), 20 * num_resolution);	
				stage_graphs.beginPath();	
				stage_graphs.fillStyle = 'rgba(0,0,0,0)';
				stage_graphs.lineWidth = 0;
				stage_graphs.fill(path);
				arr_info_boxes.push({condition_identifier: condition_identifier, path: path});
			}
			
			if (number_of_plots > 1) {
			
				plots++
				cols++
			}
		}	
	}
	
	var getObjectSubTypeId = function(object_sub_details_id) {
	
		if (arr_object_sub_type_id[object_sub_details_id]) {
			return arr_object_sub_type_id[object_sub_details_id];
		}
	
		for (var object_sub_id in arr_data.object_subs) {
			
			var object_sub = arr_data.object_subs[object_sub_id];
			
			if (object_sub.object_sub_details_id == object_sub_details_id) {
				
				arr_object_sub_type_id[object_sub_details_id] = arr_data.objects[object_sub.object_id].type_id;
				
				return arr_object_sub_type_id[object_sub_details_id];
				
			}
		}
	}
	
	var getDateLabel = function(obj_date) {
		
		if (interval.name == 'day') {
			label = DATEPARSER.date2StrDate(obj_date.date);
		} else if (interval.name == 'month') {
			label = DATEPARSER.date2StrDate(obj_date.date);
			label = label.slice(3);
		} else if (interval.name == 'year') {
			label = obj_date.string;
		} else {
			label = obj_date.string + String(interval.steps).substring(1);
		}	
		
		return label;
	}

};
