
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
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
		
	var svg_ns = 'http://www.w3.org/2000/svg',
	svg = false,
	elm_svg = false,
	svg_plot = false,
	size = {},
	size_view = {},
	arr_date_intervals = {},
	arr_object_sub_type_id = {},
	obj_conditions = false,
	number_of_dates = 0,
	max_object_subs = 1,
	max_relative_condition_amount = 0,
	max_cumulative_condition_amount = 0,
	total_amount = 0,
	reset_total_amount = true,
	mean = false,
	bar_width = 20,
	box_width = 10,
	interval = {},
	key_move = false,
	metrics = false,
	conditions_relative = false,
	conditions_cumulative = true;
   
	this.init = function() {
				
		conditions_relative = options.arr_visual.time.settings.conditions_relative;
		conditions_cumulative = options.arr_visual.time.settings.conditions_cumulative;
		
		elm_svg = document.createElementNS(svg_ns, 'svg');
		elm_svg.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
		elm[0].appendChild(elm_svg);
		
		svg = elm_svg.ownerDocument;
		
		var pos = obj_map.getPosition();
		size = pos.size;
		
		arr_data = obj_parent.getData();

		setConditions();

		key_move = obj_map.move(rePosition);
		
		addListeners();
	};
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
		
		if (calc_zoom) { // Zoomed
			
			size_view = pos.view;
			
			elm_svg.setAttribute('transform', 'scale('+pos.size.width/size.width+')');
		}
	}
	
	var addListeners = function() {
		
		//metrics = new MapNetworkMetrics(obj_parent.elm_controls.find('.legends'), obj_parent);

		var elm_hover = false;
		
		elm[0].addEventListener('mouseover', function(e) {

			var elm_check = e.target;
			
			var elm_target = false;
			
			var is_rect = false;
			var is_circle = false;
			var is_text = false;
			
			if (elm_check.matches('rect')) {
				
				elm_target = elm_check;
				is_rect = true;
				
			} else if (elm_check.matches('circle')) {

				elm_target = elm_check;
				is_circle = true;
				
			} else if (elm_check.matches('text')) {

				elm_target = elm_check;
				is_text = true;
			}

			if (!elm_target) {
				
				if (elm_hover) {
					
					elm[0].removeAttribute('title');
					TOOLTIP.update();
					
					elm[0].arr_link = false;
					elm[0].arr_info_box = false;
					
					elm_hover = false;
				}
				
			} else if (elm_hover && elm_hover === elm_target) {

				return;
			} 
			
			elm_hover = elm_target;
			var cur = $(elm_hover);

			if (is_rect && cur.data('date_string')) {
				
				var date_string = cur.data('date_string');
				var type_id = cur.data('type_id');
				var object_sub_details_id = cur.data('object_sub_details_id');
								
				elm[0].arr_link = {object_sub_ids: arr_date_intervals[date_string].sorted_object_sub_ids[type_id][object_sub_details_id].ids};

				elm[0].setAttribute('title', '<span>'+arr_data.info.types[type_id].name+'</span> <span class="sub-name">'+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+'</span> '+arr_date_intervals[date_string].sorted_object_sub_ids[type_id][object_sub_details_id].amount+'');
				TOOLTIP.update();
				
				cur.css({'fill-opacity': 0.7}).on('mouseleave', function() {
					$(this).css({'fill-opacity': 0.4});
				});
			}
			
			if (is_circle && cur.data('condition_label')) {
				
				var condition_label = cur.data('condition_label');
				var date_string = cur.data('date_string');
				
				if (arr_date_intervals[date_string].conditions && arr_date_intervals[date_string].conditions[condition_label] && arr_date_intervals[date_string].conditions[condition_label].ids.length) {
					
					elm[0].arr_link = {object_sub_ids: arr_date_intervals[date_string].conditions[condition_label].ids};
					elm[0].setAttribute('title', condition_label+' '+arr_date_intervals[date_string].conditions[condition_label].amount+'x '+(conditions_relative ? '(relative: '+(Math.round(arr_date_intervals[date_string].conditions[condition_label].relative_amount * 100) / 100)+')' : ''));
					
				} else {
					
					elm[0].setAttribute('title', condition_label+ ' (0)');
				}
				
				TOOLTIP.update();
					
				if (obj_conditions[condition_label]) {
					
					obj_conditions[condition_label].path_elm.setAttribute('stroke-width', 2);
					obj_conditions[condition_label].path_elm.setAttribute('stroke', obj_conditions[condition_label].color);
					
					for (var i = 0; i < obj_conditions[condition_label].circle_elms.length; i++) {
						obj_conditions[condition_label].circle_elms[i].setAttribute('r', 4);
					}
					
					
					for (var loop_condition_label in obj_conditions) {
						
						if (loop_condition_label !== condition_label) {
						
							obj_conditions[loop_condition_label].path_elm.setAttribute('opacity', 0.3);
							
							for (var j = 0; j < obj_conditions[loop_condition_label].circle_elms.length; j++) {
								obj_conditions[loop_condition_label].circle_elms[j].setAttribute('opacity', 0.3);
							}
						}
					}
					
				}
				
				cur[0].setAttribute('r', 5);
				
				cur.on('mouseleave', function() {
					
					for (var condition_label in obj_conditions) {
						
						obj_conditions[condition_label].path_elm.setAttribute('stroke-width', 1);
						obj_conditions[condition_label].path_elm.setAttribute('stroke', '#444');
						obj_conditions[condition_label].path_elm.setAttribute('opacity', 1);
						
						for (var i = 0; i < obj_conditions[condition_label].circle_elms.length; i++) {
							obj_conditions[condition_label].circle_elms[i].setAttribute('r', 3);
							obj_conditions[condition_label].circle_elms[i].setAttribute('opacity', 1);
						}
					}
				});
			}
			
			if ((is_text && cur.data('hover_date_string')) || (is_rect && cur.data('hover_date_string'))) {
				
				var date_string = cur.data('hover_date_string');
				var obj_date = arr_date_intervals[date_string];
				
				var str_title = '<ul>\
					<li>\
						<label>Total</label>\
						<span>'+obj_date.unsorted_weighted_object_sub_ids_amount+'</span>\
					</li><hr />';
				
				if (conditions_relative) {

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
				
				if (obj_conditions) {
					
					str_title = str_title+'<li>\
							<label>Conditions</label>\
							<ul>';
					
							for (var condition_label in obj_conditions) {
								
								str_title = str_title+'<li>\
											<label>'+condition_label+'</label>\
											<span>'+(obj_date.conditions[condition_label] && obj_date.conditions[condition_label].amount ? obj_date.conditions[condition_label].amount+'x'+(conditions_relative ? ' (relative: '+(Math.round(obj_date.conditions[condition_label].relative_amount * 100) / 100)+')' : '') : '0x')+'</span>\
										</li>';
							}
								
					str_title = str_title+'</ul>\
						</li>';
				}
				
				str_title = str_title+'</ul>';
				
				elm[0].setAttribute('title', str_title);
				TOOLTIP.update();
				
			}
		});
	};
				
	this.drawData = function(date_range) {
		 
		if (svg_plot) {
			elm_svg.removeChild(svg_plot);
		}
		
		svg_plot = svg.createElementNS(svg_ns, 'g');
		elm_svg.appendChild(svg_plot);

		total_amount = 0

		if (obj_conditions) {
			resetConditions();
		}

		setDateIntervals(date_range); 
		setCheckSub(date_range);
		
		if (conditions_relative) {
			
			max_relative_condition_amount = 0;
			
			if (parseInt(number_of_dates) && parseInt(total_amount)) {

				mean = total_amount/number_of_dates;
			}
			
			setRelativeConditionAmounts();
		}
		
		if (conditions_cumulative) {
			
			max_cumulative_condition_amount = 0;
			
			setCumulativeConditionAmounts();
		}

		drawTimeline();
		drawTimelineBars();
		drawConditions();
			
	};

	var setConditions = function() {
		
		conditions = false;
		
		for (var condition_label in arr_data.legend.conditions) {
			
			var arr_condition = arr_data.legend.conditions[condition_label];
			
			if (arr_condition.label) {
				
				if (!conditions) {
					conditions = {};
				}
				
				conditions[condition_label] = {'label': condition_label, 'color': arr_condition.color, 'icon': arr_condition.icon, 'weight': arr_condition.weight, 'positions': [], 'path_elm': false, 'circle_elms': []};
			}
		}
		
		obj_conditions = conditions;
	}
	
	var resetConditions = function() {
	
		for (var condition_label in obj_conditions) {
			
			obj_conditions[condition_label].positions = [];
			obj_conditions[condition_label].path_elm = false;
			obj_conditions[condition_label].circle_elms = [];
		}
	}

	var setDateIntervals = function(date_range) {
		
		number_of_dates = 0;
		arr_date_intervals = {};
		
		var min_date = DATEPARSER.int2Date(date_range.min),
		max_date = DATEPARSER.int2Date(date_range.max),
		range = max_date.getFullYear().toString() - min_date.getFullYear(),
		new_interval = {};	
		
		var amount_of_bars = (size_view.width - 100) / bar_width;

		if (range <= 1) {

			new_interval['name'] = 'day';
			new_interval['format'] = 'yymmdd';
			new_interval['format_text'] = 'dd-mm-yy';
			new_interval['slice'] = -4;
			new_interval['steps'] = 0;
			
		} else if (range/0.083 <= amount_of_bars) {

			new_interval['name'] = 'month';
			new_interval['format'] = 'yymm';
			new_interval['format_text'] = 'mm-yy';
			new_interval['slice'] = -6;
			new_interval['steps'] = 0;
			
		} else if (range <= amount_of_bars) {

			new_interval['name'] = 'year';
			new_interval['format'] = 'yy';
			new_interval['format_text'] = 'yy';
			new_interval['slice'] = -8;
			new_interval['steps'] = 1;
			
		} else if (range/10 <= amount_of_bars) {

			new_interval['name'] = 'decade';
			new_interval['format'] = 'yy';
			new_interval['format_text'] = 'yy';
			new_interval['slice'] = -9;
			new_interval['steps'] = 10;
			
		} else if (range/100 <= amount_of_bars) {
			
			new_interval['name'] = 'century';
			new_interval['format'] = 'yy';
			new_interval['format_text'] = 'yy';
			new_interval['slice'] = -10;
			new_interval['steps'] = 100;
			
		} else {
			
			new_interval['name'] = 'millennium';
			new_interval['format'] = 'yy';
			new_interval['format_text'] = 'yy';
			new_interval['slice'] = -11;
			new_interval['steps'] = 1000;
			
		}
		
		if (new_interval['name'] != interval['name']) {
			
			interval = new_interval;
			max_object_subs = 1;
			
		}
		
		while (min_date <= max_date) {	
						
			object_sub_details_ids = {};
			
			for (var object_sub_details_id in arr_data.legend.object_subs) {
				
				var type_id = getObjectSubTypeId(object_sub_details_id);
				
				if (type_id) {
					
					if (!object_sub_details_ids[type_id]) {
						
						object_sub_details_ids[type_id] =  {}
					}
					
					object_sub_details_ids[type_id][object_sub_details_id] = {'type_id': type_id, 'object_sub_details_id': object_sub_details_id, 'ids': [], 'amount': 0};
				}
			}
			
			var date = {'date': min_date, 'sorted_object_sub_ids': object_sub_details_ids, 'unsorted_weighted_object_sub_ids_amount': 0, 'string': '', 'conditions': {}, 'pos': {}, 'weight': 0, 'correction_value': 0};

			if (interval.name == 'day') {
				date.string = DATEPARSER.date2Int(min_date).toString();
			} else {
				date.string = DATEPARSER.date2Int(min_date).toString().slice(0, interval.slice);
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
	
	var setCheckSub = function(date_range) {

		// Single date sub objects
		for (var i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
			
			var date = arr_data.date.arr_loop[i];
			var in_range_date = (date >= date_range.min && date <= date_range.max);
			var arr_object_subs = arr_data.date[date];
		
			for (var j = 0; j < arr_object_subs.length; j++) {
				
				var in_range = in_range_date;
				
				if (in_range) {
					
					var arr_object_sub = arr_data.object_subs[arr_object_subs[j]];
					
					in_range = checkInRange(arr_object_sub);
				}
				
				setSub(arr_object_subs[j], !in_range);
			}
		}
		
		// Sub objects with a date range
		for (var i = 0, len = arr_data.range.length; i < len; i++) {
			
			var arr_object_sub = arr_data.object_subs[arr_data.range[i]];
			var in_range = ((arr_object_sub.date_start >= date_range.min && arr_object_sub.date_start <= date_range.max) || (arr_object_sub.date_end >= date_range.min && arr_object_sub.date_end <= date_range.max) || (arr_object_sub.date_start < date_range.min && arr_object_sub.date_end > date_range.max));
			
			if (in_range) {
				in_range = checkInRange(arr_object_sub);
			}
			
			setSub(arr_data.range[i], !in_range);
		}
		
	};
	
	var setSub = function(object_sub_id, remove) {
		
		if (remove) {
			return false;
		}
		
		var object_sub = arr_data.object_subs[object_sub_id];

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
				
				var date = DATEPARSER.date2Int(arr_date_intervals[key].date);
				
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
				
				if (arr_date_intervals[key].unsorted_weighted_object_sub_ids_amount > max_object_subs) {
					max_object_subs = arr_date_intervals[key].unsorted_weighted_object_sub_ids_amount;
				}
				
				total_amount += (object_sub.style.weight ? object_sub.style.weight : 1);
				
				arr_date_intervals[key].sorted_object_sub_ids[type_id][object_sub.object_sub_details_id].amount += (object_sub.style.weight ? object_sub.style.weight : 1);
				arr_date_intervals[key].sorted_object_sub_ids[type_id][object_sub.object_sub_details_id].ids.push(object_sub_id);	
			}
			
			if (object_sub.style.conditions) {
				
				for (var condition_label in object_sub.style.conditions) {
					
					if (!arr_date_intervals[key].conditions[condition_label]) {
						
						arr_date_intervals[key].conditions[condition_label] = {'ids': [], 'amount': 0, 'relative_amount': 0, 'cumulative_amount': 0};
					}
					
					arr_date_intervals[key].conditions[condition_label].amount += (object_sub.style.weight ? object_sub.style.weight : 1);
					arr_date_intervals[key].conditions[condition_label].ids.push(object_sub_id);

				}
			}
			
			if (object.style.conditions) {
				
				for (var condition_label in object.style.conditions) {
					
					if (!arr_date_intervals[key].conditions[condition_label]) {
						
						arr_date_intervals[key].conditions[condition_label] = {'ids': [], 'amount': 0, 'relative_amount': 0, 'cumulative_amount': 0};
					}
					
					arr_date_intervals[key].conditions[condition_label].amount += (object_sub.style.weight ? object_sub.style.weight : 1);
					arr_date_intervals[key].conditions[condition_label].ids.push(object_sub_id);
				}
			}
		}
	}
	
	var setRelativeConditionAmounts = function() {
		
		for (var key in arr_date_intervals) {
			
			var obj_date = arr_date_intervals[key];
			var unsorted_weighted_object_sub_ids_amount = obj_date.unsorted_weighted_object_sub_ids_amount;
			var correction_value = false;
			
			if (unsorted_weighted_object_sub_ids_amount) {
				
				correction_value = mean / unsorted_weighted_object_sub_ids_amount;
				obj_date.correction_value = correction_value;
			}
			
			for (var condition_label in obj_conditions) {
				
				if (obj_date.conditions[condition_label]) {
					
					var relative_amount = obj_date.conditions[condition_label].amount * (correction_value ? correction_value : 1);

					obj_date.conditions[condition_label].relative_amount = relative_amount;

					if (relative_amount > max_relative_condition_amount) {
						max_relative_condition_amount = relative_amount;
					}
				}
			}
		}
	}
	
	var setCumulativeConditionAmounts = function() {
		
		var previous_amount = {};
		
		for (var key in arr_date_intervals) {
			
			var obj_date = arr_date_intervals[key];
			
			for (var condition_label in obj_conditions) {
				
				if (!previous_amount[condition_label]) {
					previous_amount[condition_label] = 0;
				}
				
				if (obj_date.conditions[condition_label]) {
					
					if (conditions_relative) {
						
						var cumulative_amount = obj_date.conditions[condition_label].relative_amount + previous_amount[condition_label];
						
					} else {
						
						var cumulative_amount = obj_date.conditions[condition_label].amount + previous_amount[condition_label];
					}
					
					obj_date.conditions[condition_label].cumulative_amount = cumulative_amount;

					if (conditions_relative) {

						if (cumulative_amount > max_cumulative_condition_amount) {
							max_cumulative_condition_amount = cumulative_amount;
						}
						
					} else {
						
						if (cumulative_amount > max_object_subs) {
							max_object_subs = cumulative_amount;
						}
					}
					
					
					previous_amount[condition_label] = cumulative_amount;
				
				} else {
					
					obj_date.conditions[condition_label] = {'ids': [], 'amount': 0, 'relative_amount': 0, 'cumulative_amount': previous_amount[condition_label]}
				}
			}
		}
	}
	
	var drawTimeline = function() {
		
		var line_x_start = ((size.width / 2) - (size_view.width / 2)) + 50;
		var line_x_end = ((size.width / 2) + (size_view.width / 2)) - 50;
		var line_y = size.height - (size.height/2) + (size_view.height/4);
		
		var elm_time_line = svg.createElementNS(svg_ns, 'line');
		elm_time_line.setAttribute('x1', line_x_start);
		elm_time_line.setAttribute('y1', line_y);
		elm_time_line.setAttribute('x2', line_x_end);
		elm_time_line.setAttribute('y2', line_y);
		elm_time_line.setAttribute('stroke', '#444');
		elm_time_line.setAttribute('stroke-width', 1);		
		elm_time_line.setAttribute('fill', 'none');
		svg_plot.appendChild(elm_time_line);
		
		var line_width = size_view.width - 100; 
		var i = 0;
		
		var bar_x_margin = (line_width - (bar_width * number_of_dates)) / number_of_dates;
		
		for (var key in arr_date_intervals) {
			
			var obj_date = arr_date_intervals[key];
			var date_string = obj_date.string;	
			var text = true;
									
			var x = line_x_start + (i * (bar_width + (i == 0 ? bar_x_margin/2 : bar_x_margin)));

			var elm_draw_hover_rect = svg.createElementNS(svg_ns, 'rect');
			elm_draw_hover_rect.setAttribute('x', x);
			elm_draw_hover_rect.setAttribute('y', line_y);
			elm_draw_hover_rect.setAttribute('width', bar_width);
			elm_draw_hover_rect.setAttribute('height', 40);
			elm_draw_hover_rect.style.fill = 'transparent';
			elm_draw_hover_rect.setAttribute('data-hover_date_string', key);
			svg_plot.appendChild(elm_draw_hover_rect);	
			
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
			
			var elm_label_line = svg.createElementNS(svg_ns, 'line');
			elm_label_line.setAttribute('x1', x + bar_width/2);
			elm_label_line.setAttribute('y1', line_y);
			elm_label_line.setAttribute('x2', x + bar_width/2);
			elm_label_line.setAttribute('stroke', '#aaa');
			elm_label_line.setAttribute('stroke-width', 1);		
			elm_label_line.setAttribute('fill', 'none');

			if (text) {
			
				if (interval.name == 'day') {
					text = DATEPARSER.date2StrDate(obj_date.date);
				} else if (interval.name == 'month') {
					text = DATEPARSER.date2StrDate(obj_date.date);
					text = text.slice(3);
				} else if (interval.name == 'year') {
					text = date_string;
				} else {
					text = date_string + String(interval.steps).substring(1);
				}	
				
				var elm_draw = svg.createElementNS(svg_ns, 'text');
				elm_draw.setAttribute('text-anchor', 'middle');
				elm_draw.setAttribute('x', x + bar_width/2);
				elm_draw.setAttribute('y', line_y + 30);
				elm_draw.style.fill = '#000000';
				
				var elm_text = document.createTextNode(text);
				elm_draw.appendChild(elm_text);	
						
				elm_draw.setAttribute('data-hover_date_string', key);	
			
				svg_plot.appendChild(elm_draw);	
				
				elm_label_line.setAttribute('y2', line_y + 12);
				
			} else {
				
				elm_label_line.setAttribute('y2', line_y + 7);
			}

			svg_plot.appendChild(elm_label_line);
				
			i++;
		}
		
	}
	
	var drawTimelineBars = function() {

		var max_height = size_view.height - (size_view.height/3);
		var absolute_value_scale = (max_object_subs > max_height || max_cumulative_condition_amount > max_height || max_relative_condition_amount > max_height ? false : true);
		var i = 1;
		
		for (var key in arr_date_intervals) {
			
			var obj_date = arr_date_intervals[key];
			var unsorted_weighted_object_sub_ids_amount = obj_date.unsorted_weighted_object_sub_ids_amount;
			
			var x = obj_date.pos.x;
			var y = obj_date.pos.y;
			
			var date_string = obj_date.string;
			var sorted_object_sub_ids = obj_date.sorted_object_sub_ids;
			var bar_height = (absolute_value_scale ? unsorted_weighted_object_sub_ids_amount : ((unsorted_weighted_object_sub_ids_amount / max_object_subs) * max_height));
			var bar_y = y - (absolute_value_scale ? unsorted_weighted_object_sub_ids_amount : bar_height);

			for (var type_id in sorted_object_sub_ids) {
				
				for (var object_sub_details_id in sorted_object_sub_ids[type_id]) {
					
					if (sorted_object_sub_ids[type_id][object_sub_details_id].ids.length) {

						var box_height = (absolute_value_scale ? sorted_object_sub_ids[type_id][object_sub_details_id].amount : (sorted_object_sub_ids[type_id][object_sub_details_id].amount / max_object_subs) * max_height);
						
						var obj_color = arr_data.legend.object_subs[object_sub_details_id].color;				
						var color = 'rgb('+obj_color.red+','+obj_color.green+','+obj_color.blue+')';					

						var elm_draw = svg.createElementNS(svg_ns, 'rect');
						elm_draw.setAttribute('x', x + (bar_width/2)-((bar_width/2)/2));
						elm_draw.setAttribute('y', bar_y);
						elm_draw.setAttribute('width', bar_width/2);
						elm_draw.setAttribute('height', box_height);
						elm_draw.style.fill = color;
						
						elm_draw.setAttribute('data-date_string', key);
						elm_draw.setAttribute('data-type_id', type_id);
						elm_draw.setAttribute('data-object_sub_details_id', object_sub_details_id);
						
						svg_plot.appendChild(elm_draw);
							
						bar_y = bar_y + box_height;
						
					}
				}
			}
			
			for (var condition_label in obj_conditions) {
				
				var cx = x + bar_width/2;
				var amount = 0;
				var max = 0;
				
				if (conditions_cumulative) {
					
					amount = (obj_date.conditions[condition_label] ? obj_date.conditions[condition_label].cumulative_amount : 0);
					
					if (conditions_relative) {
						max = max_cumulative_condition_amount;
					} else {
						max = max_object_subs;
					}
					
				} else if (conditions_relative) {
					
					amount = (obj_date.conditions[condition_label] ? obj_date.conditions[condition_label].relative_amount : 0);
					max = max_relative_condition_amount;
					
				} else {
					
					amount = (obj_date.conditions[condition_label] ? obj_date.conditions[condition_label].amount : 0);
					max = max_object_subs;
					
				}
				
				var cy = y - (absolute_value_scale ? amount : (amount / max) * max_height);
				
				obj_conditions[condition_label].positions.push({'cx': cx, 'cy': cy, 'date_string': key});
				
			}				
				
			i++;	
		}
	}
		
	var drawConditions = function() {

		for (var condition_label in obj_conditions) {
			
			var arr_condition = obj_conditions[condition_label];
			var arr_condition_positions = arr_condition.positions;
			
			var elm_path = svg.createElementNS(svg_ns, 'path');
			elm_path.setAttribute('stroke', '#444');
			elm_path.setAttribute('stroke-width', 1);		
			elm_path.setAttribute('fill', 'none');
			elm_path.setAttribute('data-condition_label', arr_condition.label);
						
			var d = false;
				
			for (var i = 0; i < arr_condition_positions.length; i++) {
				
				var cx = arr_condition_positions[i].cx;
				var cy = arr_condition_positions[i].cy;
				
				if (!d) {
					d = 'M'+cx+' '+cy;
				} else {
					d = d + ' L'+cx+' '+cy;
				}
			}
			
			elm_path.setAttribute('d', d);
			
			arr_condition.path_elm = elm_path;
			
			svg_plot.appendChild(elm_path);	

			for (var i = 0; i < arr_condition_positions.length; i++) {
				
				var cx = arr_condition_positions[i].cx;
				var cy = arr_condition_positions[i].cy;
				
				var elm_circle = svg.createElementNS(svg_ns, 'circle');
				elm_circle.setAttribute('r', 3);
				elm_circle.setAttribute('cx', cx);
				elm_circle.setAttribute('cy', cy);
				elm_circle.setAttribute('stroke', '#444');
				elm_circle.setAttribute('stroke-width', 1);	
				
				elm_circle.setAttribute('data-condition_label', arr_condition.label);
				elm_circle.setAttribute('data-date_string', arr_condition_positions[i].date_string);
					
				elm_circle.style.fill = arr_condition.color;
				
				svg_plot.appendChild(elm_circle);
				
				arr_condition.circle_elms.push(elm_circle);
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

};
