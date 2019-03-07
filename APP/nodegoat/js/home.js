
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

$(document).on('documentloaded ajaxloaded', function(e) {
	
	if (!getElement(e.detail.elm)) {
		return;
	}
	
	for (var i = 0, len = e.detail.elm.length; i < len; i++) {
		
		var elm = $(e.detail.elm[i]);
		
		elm.find('.colorpicker').each(function() {
			
			var cur = $(this);
			
			if (cur[0].colorpicker) {
				return;
			}
			cur[0].colorpicker = true;
			
			cur.wrap('<span class="input" />').minicolors({
				theme: 'none',
				swatchPosition: 'right'
			});
		});
	}
});

function Marginalia(elm, arr_options) {
	
	var SELf = this;
		
	var elm = $(elm);
	
	if (elm[0].marginalia) {
		return;
	}
	elm[0].marginalia = this;
	
	var height_max =  0;
	
	var elm_marginalia = elm.next('div');
	var elm_marginalia_width = elm_marginalia.width();
	
	var elms_tag = elm[0].querySelectorAll('.tag');
	
	for (var i = 0, len = elms_tag.length; i < len; i++) {

		var elm_tag = $(elms_tag[i]);
		var pos_tag_top = elm_tag.position().top;
		var arr_type_object_group_ids = elm_tag.attr('data-ids').split('|');

		for (var j in arr_type_object_group_ids) {
			
			if (elm_marginalia.find('p.'+arr_type_object_group_ids[j]).length < 1) {

				var arr_type_object_group_id = arr_type_object_group_ids[j].split('_');
				var object_link = elm_tag.closest('.tabs').find('.text-references [class^='+arr_type_object_group_id[0]+'_'+arr_type_object_group_id[1]+']').clone();

				if (object_link.text()) {
					var object_link_width = object_link.appendTo(elm_marginalia).outerWidth();
					object_link.addClass('hide');
					
					if (elm_marginalia.find('p:visible').length) {
						
						var prev_elm = elm_marginalia.find('p:visible').last();
						var pos_prev_elm = {
							top: prev_elm.position().top,
							left: prev_elm.position().left,
							height: prev_elm.height(),
							outerwidth: prev_elm.outerWidth()
						};
						var top = pos_prev_elm.top + pos_prev_elm.height;
						
						if (pos_prev_elm.top >= pos_tag_top) {
							
							if (((object_link_width + pos_prev_elm.outerwidth) < elm_marginalia_width) && ((pos_prev_elm.left + pos_prev_elm.outerwidth + object_link_width) < elm_marginalia_width)) {
								pos_tag_top = pos_prev_elm.top;
								var left = pos_prev_elm.left + pos_prev_elm.outerwidth;
								var separator = $('<p>,</p>').insertAfter(prev_elm).css({'top': pos_tag_top, 'left' : left});
								object_link.css('left', left + separator.outerWidth() + 3);
							} else {
								pos_tag_top = top;
							}
							
						} else if (top > pos_tag_top) {
							pos_tag_top = top;
						}
					}
					
					if (pos_tag_top > height_max) {
						height_max = pos_tag_top;
					}
					
					object_link.css('top', pos_tag_top).removeClass('hide').attr('class', arr_type_object_group_ids[j]);
				} else {
					
					elm_tag.attr('title', 'Referenced object does not exist');
				}
			}
		}
	};
	
	if (elm.height() < height_max) {
		
		elm.parent().children('p').css('margin-top', (height_max - elm.height())+'px');
	}
}

(function($) {
	$.fn.addHover = function(options) {
		
		var options = $.extend({
			hover_action: false
		}, options);
		
		var elm = $(this),
		ids = elm.attr('data-ids'),
		ids_class = ids.replace(/\D/g,''),
		elm_toolbox = getContainerToolbox(elm),
		pos = elm[0].getBoundingClientRect();
		pos_mod = elm_toolbox[0].getBoundingClientRect();

		if (pos_mod.left == 0) {
			pos_mod.left = 75;
		} else {
			pos_mod.left = 150;
		}
		
		if (!elm_toolbox.find('.hover.'+ids_class).length) {
			
			var elm_hover = $('<div></div>').addClass('hover hide '+ids_class).attr('id', options.hover_action+ids);
			
			elm_hover.appendTo(elm_toolbox)
				.removeClass('hide')
				.css({top : pos.top - pos_mod.top - elm_hover[0].offsetHeight - 5, left: pos.left - pos_mod.left + (pos.width/2) - (elm_hover[0].offsetWidth/2)})
				.delay(230).queue(function() {
					elm_hover.quickCommand(function(html) {
						elm_hover.html(html);
						if (elm_hover.find('.image-header').length) {
							elm_hover.addClass('image').css('top', pos.top - pos_mod.top - elm_hover[0].offsetHeight - 5);
						}
					}).data('completed', true);
				});
			
			elm_hover.on('mouseenter', function() {
				elm_hover.clearQueue();
			}).on('mouseleave', function() {
				elm_hover.addClass('hide');
			})
			
		} else {
			
			var elm_hover = elm_toolbox.find('.hover.'+ids_class);
			
			if (!elm_hover.data('completed')) {
				elm_hover.quickCommand(function(html) {
					elm_hover.html(html);
					if (elm_hover.find('.image-header').length) {
						elm_hover.addClass('image').css('top', pos.top - pos_mod.top - elm_hover[0].offsetHeight - 5);
					}
				}).data('completed', true);
			}
			elm_hover
				.removeClass('hide')
				.css({top : pos.top - pos_mod.top - elm_hover[0].offsetHeight - 5, left: pos.left - pos_mod.left + (pos.width/2) - (elm_hover[0].offsetWidth/2)});
		}
		
		elm.on('mouseleave', function() {
			elm_hover.clearQueue().delay(300).queue(function(){
				elm_hover.addClass('hide');
			});	
		});
		
	}
})(jQuery);

function DateParser() {
	
	var SELF = this;
	
	this.time_day = 24*60*60*1000; // hours*minutes*seconds*milliseconds

	var int_date_positive = 10000000000000000000;
		
	this.date2Calc = function(date) {
				
		var int = date.getTime();
		
		int = int + int_date_positive;
					
		return int;
	};

	this.calc2Date = function(int) {
		
		var int = int - int_date_positive;
		
		var date = new Date(int);
		
		return date;
	};

	this.date2Int = function(date) {
		
		var month = (date.getMonth()+1);
		var day = date.getDate();
		var sequence = '0000';

		return parseInt(date.getFullYear()+(month < 10 ? '0' : '')+month+(day < 10 ? '0' : '')+day+sequence);
	};
	
	this.int2Date = function(date) {
		
		var arr_date = SELF.int2Components(date);
		
		return SELF.newDate(arr_date.y, arr_date.m, arr_date.d, arr_date.s);
	};
	
	this.int2Components = function(date) {

		var str_date = date.toString();
		var arr_date = {y: parseInt(str_date.slice(0, -8)), m: parseInt(str_date.slice(-8, -6))-1, d: parseInt(str_date.slice(-6, -4)), s: parseInt(str_date.slice(-4))};
		
		return arr_date;
	};
	this.int2ComponentYear = function(date) {

		var str_date = date.toString();

		return parseInt(str_date.slice(0, -8));
	};
	this.int2ComponentMonth = function(date) {

		var str_date = date.toString();

		return parseInt(str_date.slice(-8, -6))-1;
	};
	this.int2ComponentDay = function(date) {

		var str_date = date.toString();

		return parseInt(str_date.slice(-6, -4));
	};
	this.int2ComponentSequence = function(date) {

		var str_date = date.toString();

		return parseInt(str_date.slice(-4));
	};

	this.date2StrDate = function(date, show_ce) {
		
		var year = date.getFullYear();
		if (show_ce) {
			year = (year < 1 ? Math.abs(year)+ ' BCE' : year+ ' CE');
		}
		var month = (date.getMonth()+1);
		var day = date.getDate();
		var sequence = 0;
		
		var str_date = (day < 10 ? '0' : '')+day+'-'+(month < 10 ? '0' : '')+month+'-'+year+(sequence ? ' '+sequence : '');
		
		return str_date;
	};

	this.strDate2Int = function(str_date) {
		
		var str_date = str_date;
		var sequence = '0000';
		
		if (str_date.indexOf(' ') !== -1) {
			str_date = str_date.split(' ');
			sequence = str_date[1].padStart(4, '0');
			str_date = str_date[0];
		}
		
		var min = '';
		
		if (str_date.substr(0, 1) == '-') { // -13-10-5456
			str_date = str_date.substr(1);
			min = '-';
		} else if (str_date.indexOf('--') !== -1) { // 13-10--5456
			str_date = str_date.replace('--', '-');
			min = '-';
		}
		
		var arr = str_date.split('-');
		var year = arr[2];
		var month = arr[1].padStart(2, '0');
		var day = arr[0].padStart(2, '0');
		
		return min+year+month+day+sequence;
	};

	this.newDate = function(y, m, d, s) {

		if (y >= 0 && y < 100) {
			
			var date = new Date(y + 100, m, d, 1);
			date.setFullYear(date.getFullYear() - 100);
		} else {
			
			// Current JavaScript year limit
			if (y < -271820) {
				var y = -271820;
			} else if (y > 271820) {
				var y = 271820;
			}
			
			var date = new Date(y, m, d, 1); // Also set the hour 1 to prevent possible hopping between years when the date is 01-01 or 31-12
		}
		
		return date;
	};
}
var DATEPARSER = new DateParser();

function TSchuifje(elm, options) {
	
	var obj = this;
	
	var arr_options = $.extend({
		bounds: {},
		min: false,
		max: false,
		call_change: false
	}, options || {});
			
	var elm = $(elm);
	
	if (!elm.hasClass('tschuifje')) {
		elm.addClass('tschuifje');
	}
	
	var elm_slider = $('<div></div>').appendTo(elm);
	var elm_options = $('<div></div>').appendTo(elm);
	
	var elm_button_left = $('<button type="button" class="left"><span class="icon"></span></button>').appendTo(elm_slider);
	var elm_bar = $('<div class="bar"></div>').appendTo(elm_slider);
	var elm_bar_inner = $('<div></div>').appendTo(elm_bar);
	var elm_handler_left = $('<div class="handler left"></div>').appendTo(elm_bar_inner);
	var elm_handler_left_label = $('<time>00-00-0000</time>').appendTo(elm_handler_left);
	var elm_handler_right = $('<div class="handler right"></div>').appendTo(elm_bar_inner);
	var elm_handler_right_label = $('<time>00-00-0000</time>').appendTo(elm_handler_right);
	var elm_day = $('<div class="day"></div>').appendTo(elm_bar_inner);
	var elm_day_label = $('<time>00-00-0000</time>').appendTo(elm_day);
	var elm_button_right = $('<button type="button" class="right"><span class="icon"></span></button>').appendTo(elm_slider);
	
	var elm_date_min = $('<input name="min" type="text" class="date" />').appendTo(elm_options);
	var elm_date_max = $('<input name="max" type="text" class="date" />').appendTo(elm_options);
	$('<span class="split"></span>').appendTo(elm_options);
	var elm_speed_amount = $('<input name="amount" type="number" value="1" min="1" />').appendTo(elm_options);
	var elm_speed_unit = $('<select name="unit"><option value="day">day</option><option value="week">week</option><option value="month">month</option><option value="year">year</option></select>').appendTo(elm_options);
	var elm_play = $('<button type="button" class="play"><span class="icon"></span></button>').appendTo(elm_options);
	var elm_speed_reverse = $('<input type="checkbox" name="reverse" value="1" />').appendTo(elm_options);
	var elm_pause = $('<button type="button" class="pause hide"><span class="icon"></span></button>').appendTo(elm_options);
	var elm_stop = $('<button type="button" class="stop hide"><span class="icon"></span></button>').appendTo(elm_options);
	
	ASSETS.getIcons(elm, ['prev', 'next', 'play', 'pause', 'stop'], function(data) {
		elm_button_left[0].children[0].innerHTML = data.prev;
		elm_button_right[0].children[0].innerHTML = data.next;
		elm_play[0].children[0].innerHTML = data.play;
		elm_pause[0].children[0].innerHTML = data.pause;
		elm_stop[0].children[0].innerHTML = data.stop;
	});
	ASSETS.getLabels(elm,
		['inf_tschuifje_date_min', 'inf_tschuifje_date_max', 'inf_tschuifje_speed_amount', 'inf_tschuifje_speed_unit', 'inf_tschuifje_play', 'inf_tschuifje_pause', 'inf_tschuifje_stop', 'inf_tschuifje_speed_reverse', 'inf_tschuifje_move_left', 'inf_tschuifje_move_right'],
		function(data) {
			elm_date_min[0].title = data.inf_tschuifje_date_min;
			elm_date_max[0].title = data.inf_tschuifje_date_max;
			elm_speed_amount[0].title = data.inf_tschuifje_speed_amount;
			elm_speed_unit[0].title = data.inf_tschuifje_speed_unit;
			elm_play[0].title = data.inf_tschuifje_play;
			elm_pause[0].title = data.inf_tschuifje_pause;
			elm_stop[0].title = data.inf_tschuifje_stop;
			elm_speed_reverse[0].title = data.inf_tschuifje_speed_reverse;
			elm_button_left[0].title = data.inf_tschuifje_move_left;
			elm_button_right[0].title = data.inf_tschuifje_move_right;
		}
	);
	
	var width_elm_handler = elm_handler_left.width();
	var margin_elm_handler_label = parseInt(elm_handler_left_label.css('padding-left')) / 2;
	var width_elm_day = elm_day.width();
	var width_elm_day_label = elm_day_label.outerWidth();
	var offset_elm_day_label = (width_elm_day_label - width_elm_day)/2;
	elm_day.addClass('hide');
	
	var width = elm_bar.width();
	var calc_bounds = {min: false, max: false};
	var cur_calc_bounds = {min: false, max: false};
	var calc_min = false;
	var calc_max = false;
	var calc_time = false;
	var calc_one_width = false;
	var calc_one_perc = false;
	var calc_one_value = false;
	
	var play_mode = 'size';
	var reverse = false;
	var playing = false;
	var paused = false;
	
	var arr_settings = {bounds: {}, min: false, max: false, size_perc: false, time: false, dating: {unit: 'day', amount: 1, show_ce: false}};
	var dating_speed = 1;
						
	this.update = function(arr_update) {
		
		var changed = false;
							
		if (arr_update.bounds) {
			arr_settings.bounds.min = arr_update.bounds.min;
			arr_settings.bounds.max = arr_update.bounds.max;
			calc_bounds.min = DATEPARSER.date2Calc(arr_settings.bounds.min);
			calc_bounds.max = DATEPARSER.date2Calc(arr_settings.bounds.max);
			calc_one_width = width / (calc_bounds.max - calc_bounds.min);
			calc_one_perc = (calc_bounds.max - calc_bounds.min) / 100;
			calc_one_value = (calc_bounds.max - calc_bounds.min) / width;
			var days = ((calc_bounds.max - calc_bounds.min) / 86400) / 1000;
			if (days > (365 * 10)) {
				elm_speed_unit.val('year');
			} else if (days > (365 * 2)) {
				elm_speed_unit.val('month');
			} else if (days > 365) {
				elm_speed_unit.val('week');
			} else {
				elm_speed_unit.val('day');
			}
			SCRIPTER.triggerEvent(elm_speed_unit, 'update');
			changed = true;
		}
		if (arr_update.target) {
			if (arr_update.target.min) {
				var calc_target_min = DATEPARSER.date2Calc(arr_update.target.min);
				if (calc_target_min > calc_bounds.min && calc_target_min < calc_bounds.max) {
					cur_calc_bounds.min = calc_bounds.min;
					calc_bounds.min = calc_target_min;
					changed = true;
				}
			}
			if (arr_update.target.max) {
				var calc_target_max = DATEPARSER.date2Calc(arr_update.target.max);
				if (calc_target_max < calc_bounds.max && calc_target_max > calc_bounds.min) {
					cur_calc_bounds.max = calc_bounds.max;
					calc_bounds.max = calc_target_max;
					changed = true;
				}
			}
		}
		if ((!arr_settings.min && !arr_update.min) || arr_update.min < arr_settings.bounds.min) {
			arr_update.min = arr_settings.bounds.min;
		}
		if ((!arr_settings.max && !arr_update.max) || arr_update.max > arr_settings.bounds.max) {
			arr_update.max = arr_settings.bounds.max;
		}
	
		if (arr_update.min) {
			arr_settings.min = arr_update.min;
			calc_min = DATEPARSER.date2Calc(arr_settings.min);
			changed = true;
		}
		if (arr_update.max) {
			arr_settings.max = arr_update.max;
			calc_max = DATEPARSER.date2Calc(arr_settings.max);
			changed = true;
		}
		
		if (arr_update.move) {
			
			if (typeof arr_update.move != 'object') {
				arr_update.move = {percent: arr_update.move};
			}
				
			if (arr_update.move.percent) {
				
				var calc_difference = calc_one_perc * arr_update.move.percent;
			} else {
			
				var date_new = new Date(arr_settings.max);
				
				if (arr_update.move.day) {
					var value = arr_settings.max.getDate();
					date_new.setDate(value + arr_update.move.day);
				}
				if (arr_update.move.week) {
					var value = arr_settings.max.getDate();
					date_new.setDate(value + (arr_update.move.week * 7));
				}
				if (arr_update.move.month) {
					var value = arr_settings.max.getMonth();
					date_new.setMonth(value + arr_update.move.month);
				}
				if (arr_update.move.year) {
					var value = arr_settings.max.getFullYear();
					date_new.setFullYear(value + arr_update.move.year);
				}
				
				var calc_new = DATEPARSER.date2Calc(date_new);
				var calc_difference = calc_new - calc_max;
			}
			
			func_move('move', calc_difference);
		}
		
		if (arr_update.size) {
			
			if (typeof arr_update.size != 'object') {
				arr_update.size = {percent: arr_update.size};
			}
			var mode = (!arr_update.size.mode ? 'add' : arr_update.size.mode);
			
			if (arr_update.size.percent) {
				
				var calc_new = (arr_update.size.percent < 0 || mode == 'set' ? calc_min : calc_max) + (calc_one_perc * arr_update.size.percent);
			} else {
				
				var amount = (arr_update.size.day ? arr_update.size.day : (arr_update.size.week ? arr_update.size.week : (arr_update.size.month ? arr_update.size.month : (arr_update.size.year ? arr_update.size.year : 0))));
				
				var date_source = (amount < 0 || mode == 'set' ? arr_settings.min : arr_settings.max);
				var date_new = new Date(date_source);
				
				if (arr_update.size.day) {
					var value = date_source.getDate();
					date_new.setDate(value + arr_update.size.day);
				}
				if (arr_update.size.week) {
					var value = date_source.getDate();
					date_new.setDate(value + (arr_update.size.week * 7));
				}
				if (arr_update.size.month) {
					var value = date_source.getMonth();
					date_new.setMonth(value + arr_update.size.month);
				}
				if (arr_update.size.year) {
					var value = date_source.getFullYear();
					date_new.setFullYear(value + arr_update.size.year);
				}
				
				var calc_new = DATEPARSER.date2Calc(date_new);
			}
	
			if (amount > 0 || mode == 'set') {
				calc_max = calc_new;
				if (calc_new > calc_bounds.max) {
					calc_max = calc_bounds.max;
					if (!(arr_update.target && arr_update.target.max)) { // Add excess time to the other side
						calc_min = calc_min - (calc_new - calc_bounds.max);
					}
				}
			} else {
				calc_min = calc_new;
				if (calc_new < calc_bounds.min) {
					calc_min = calc_bounds.min;
					if (!(arr_update.target && arr_update.target.min)) { // Add excess time to the other side
						calc_max = calc_max + (calc_bounds.min - calc_new);
					}
				}
			}
			
			changed = true;
		}
		
		if (cur_calc_bounds.min) {
			calc_bounds.min = cur_calc_bounds.min;
			cur_calc_bounds.min = false;
		}
		if (cur_calc_bounds.max) {
			calc_bounds.max = cur_calc_bounds.max;
			cur_calc_bounds.max = false;
		}
		
		if (arr_update.time !== undefined) {
	
			if (calc_time === false) {
				
				calc_time = calc_min;
				
				arr_size_change.func_stop(true);
				play_mode = 'time';
				elm_day.removeClass('hide');
				
				changed = true;
			} else {
				
				if (playing && !paused) {
										
					calc_time = calc_time + ((arr_update.time / 1000) * (dating_speed * DATEPARSER.time_day));
	
					if (calc_time < calc_min || calc_time > (calc_max + DATEPARSER.time_day)) {
						calc_time = calc_min;
					}
					
					changed = true;
				}
			}
		}
		
		if (arr_update.dating !== undefined) {
			
			if (arr_update.dating.unit && arr_update.dating.unit == 'second') {
				var days = Math.round((calc_max - calc_min) / DATEPARSER.time_day);
				arr_update.dating.amount = days / arr_update.dating.amount;
				arr_update.dating.unit = 'day';
			}
			
			if (arr_update.dating.unit) {
				arr_settings.dating.unit = arr_update.dating.unit;
				elm_speed_unit.val(arr_settings.dating.unit);
				func_dating_speed();
			}
			if (arr_update.dating.amount) {
				arr_settings.dating.amount = arr_update.dating.amount;
				elm_speed_amount.val(arr_settings.dating.amount);
				if (reverse) {
					arr_settings.dating.amount = -arr_settings.dating.amount;
				}
				func_dating_speed();
			}
		}
		
		if (arr_update.player !== undefined) {
			
			if (arr_update.player.action) {
				
				var action = arr_update.player.action;
				
				if (action == 'play') {
					if (play_mode == 'size') {
						arr_size_change.func_run((paused ? false : true));
					}
					elm_pause.removeClass('hide');
					elm_stop.removeClass('hide');
					elm_play.addClass('hide');
					elm_speed_reverse.addClass('hide');
					playing = true;
					paused = false;
				}
				if (action == 'pause') {
					if (play_mode == 'size') {
						arr_size_change.func_stop();
					}
					elm_pause.addClass('hide');
					elm_play.removeClass('hide');
					paused = true;
				}
				if (action == 'stop') {
					if (play_mode == 'size') {
						arr_size_change.func_stop(true);
					} else {
						calc_time = calc_min;
						changed = true;
					}			
					func_player_stop();
				}
			}
			if (arr_update.player.reverse !== undefined) {
				
				reverse = arr_update.player.reverse;
			
				arr_settings.dating.amount = Math.abs(arr_settings.dating.amount);
				if (reverse) {
					arr_settings.dating.amount = -arr_settings.dating.amount;
				}
				
				func_dating_speed();
			}
		}
		
		if (changed) {
			func_change();
		}
		func_draw();
		
		return arr_settings;
	};

	var func_change = function() {
		
		arr_settings.size_perc = (calc_max - calc_min) / (calc_bounds.max - calc_bounds.min);
		arr_settings.min = DATEPARSER.calc2Date(calc_min);
		arr_settings.max = DATEPARSER.calc2Date(calc_max);
		
		arr_settings.dating.show_ce = (arr_settings.bounds.min.getFullYear() < 1);
		
		var str_date_min = DATEPARSER.date2StrDate(arr_settings.min, arr_settings.dating.show_ce);
		var str_date_max = DATEPARSER.date2StrDate(arr_settings.max, arr_settings.dating.show_ce);
		
		elm_handler_left_label.text(str_date_min);
		elm_handler_right_label.text(str_date_max);
		
		if (arr_settings.dating.show_ce) {
			str_date_min = DATEPARSER.date2StrDate(arr_settings.min);
			str_date_max = DATEPARSER.date2StrDate(arr_settings.max);
		}
		
		elm_date_min.val(str_date_min);
		elm_date_max.val(str_date_max);
		
		if (arr_options.call_change) {
			arr_options.call_change(arr_settings);
		}
		
		if (play_mode == 'time') {
			
			arr_settings.time = calc_time - int_date_positive;
			elm_day_label.text(DATEPARSER.date2StrDate(new Date(arr_settings.time), arr_settings.dating.show_ce));
		}
	};
	
	var func_draw = function() {
		
		elm_bar_inner[0].style.left = Math.floor((calc_min - calc_bounds.min) * calc_one_width)+'px';
		elm_bar_inner[0].style.right = Math.floor((calc_bounds.max - calc_max) * calc_one_width)+'px';
		
		var width_elm_handler_left_label = elm_handler_left_label[0].offsetWidth;
		var offset_elm_handler_left_label = ((width_elm_handler_left_label/2) - width_elm_handler);
		var width_elm_handler_right_label = elm_handler_right_label[0].offsetWidth;
		var offset_elm_handler_right_label = (width_elm_handler_right_label/2);
		
		var width_bar = (calc_max - calc_min) * calc_one_width;
		var width_label_left = width_elm_handler_left_label + margin_elm_handler_label; // x extra spacing between labels
		var width_label_right = width_elm_handler_right_label + margin_elm_handler_label; // x extra spacing between labels
		var width_labels = width_label_left + width_label_right;
	
		if (width_bar < (width_labels / 2)) {
			
			var offset_labels = (width_labels / 2) - width_bar;
			
			elm_handler_left_label[0].style.left = Math.floor(-offset_elm_handler_left_label - (offset_labels/2)) + 'px';
			elm_handler_right_label[0].style.left = Math.floor(-offset_elm_handler_right_label + (offset_labels/2)) + 'px';
		} else {
			
			elm_handler_left_label[0].style.left = Math.floor(-offset_elm_handler_left_label) + 'px';
			elm_handler_right_label[0].style.left = Math.floor(-offset_elm_handler_right_label) + 'px';
		}
		
		if (play_mode == 'time') {
	
			elm_day[0].style.left = Math.floor((calc_time - calc_min) * calc_one_width) + 'px';
			elm_day_label[0].style.left = Math.floor(-offset_elm_day_label) + 'px';
		}
	};
	
	var func_move = function(movement, calc_difference) {
		
		var calc_difference = calc_difference;
		
		if (movement == 'time') {
			
			calc_time = calc_time + calc_difference;
		} else {
				
			if (movement == 'move' || movement == 'resize-left') {
				if ((calc_min + calc_difference) < calc_bounds.min) {
					calc_difference = calc_bounds.min - calc_min;
				}
			}
			if (movement == 'move' || movement == 'resize-right') {
				if ((calc_max + calc_difference) > calc_bounds.max) {
					calc_difference = calc_bounds.max - calc_max;
				}
			}
			
			if (movement == 'move' || movement == 'resize-left') {
				
				calc_min = calc_min + calc_difference;
				if (calc_min > calc_bounds.max) {
					calc_min = calc_bounds.max;
				}
				if (calc_min < calc_bounds.min) {
					calc_min = calc_bounds.min;
				}
				
				if (movement == 'resize-left' && calc_min > calc_max) {
					var org_calc = calc_min;
					calc_min = calc_max;
					calc_max = org_calc;
					var movement = 'resize-right';
				}
			}
										
			if (movement == 'move' || movement == 'resize-right') {
				
				calc_max = calc_max + calc_difference;
				if (calc_max > calc_bounds.max) {
					calc_max = calc_bounds.max;
				}
				if (calc_max < calc_bounds.min) {
					calc_max = calc_bounds.min;
				}
				
				if (movement == 'resize-right' && calc_max < calc_min) {
					var org_calc = calc_max;
					calc_max = calc_min;
					calc_min = org_calc;
					var movement = 'resize-left';
				}
			}
		}
		
		if (play_mode == 'time') {
			
			if (calc_time < calc_min) {
				calc_time = calc_min;
			}
			if (calc_time > calc_max) {
				calc_time = calc_max;
			}
		}
	
		if (calc_difference) {
			
			func_change();
			
			return movement;
		} else {
		
			return false;
		}
	};
	
	var func_dating_speed = function() {
		
		dating_speed = arr_settings.dating.amount * (arr_settings.dating.unit == 'year' ? 365 : (arr_settings.dating.unit == 'month' ? 30 : (arr_settings.dating.unit == 'week' ? 7 : 1)));
	};
	
	var func_player_stop = function() {
		
		elm_pause.addClass('hide');
		elm_stop.addClass('hide');
		elm_play.removeClass('hide');
		elm_speed_reverse.removeClass('hide');
		playing = false;
		paused = false;
	};
	
	var arr_size_change = {poller: new PollingBuffer(40), arr_update: {size: {day: 0, mode: 'add'}, target: {min: false, max: false}}, speed_sec: false, min: false, max: false,
		func_run: function(reset) {
		
			arr_size_change.func_stop();
			
			if (!arr_size_change.min || reset) {
				
				arr_size_change.min = arr_settings.min;
				arr_size_change.max = arr_settings.max;
				arr_size_change.arr_update.target.min = arr_settings.min;
				arr_size_change.arr_update.target.max = arr_settings.max;
				
				if (arr_settings.dating.amount > 0) {
					obj.update({max: arr_settings.min});
				} else {
					obj.update({min: arr_settings.max});
				}
			}
										
			var timer = 0;
			
			arr_size_change.poller.run(function() {
																
				var time = window.performance.now();
				var perc_sec = 1000 / (time - (timer ? timer : time));
				var size_sec = Math.abs(dating_speed) / perc_sec;
				
				arr_size_change.speed_sec += size_sec;
				timer = time;
				
				if (arr_size_change.speed_sec >= 1) {
					
					var size = Math.floor(arr_size_change.speed_sec);
					arr_size_change.arr_update.size.day = (arr_settings.dating.amount < 0 ? -size : size);
					
					obj.update(arr_size_change.arr_update);
																																
					if ((arr_settings.dating.amount > 0 && arr_settings.max >= arr_size_change.max) || (arr_settings.dating.amount < 0 && arr_settings.min <= arr_size_change.min)) {
						
						arr_size_change.func_stop();
						func_player_stop();
					}
					
					arr_size_change.speed_sec = (arr_size_change.speed_sec - size);
				}
			}, true);
		},
		func_stop: function(reset) {
			if (arr_size_change.poller.running) {
				arr_size_change.poller.stop();
			}
			if (arr_size_change.min && reset) {
				obj.update({min: arr_size_change.min, max: arr_size_change.max});
			}
		}
	};
	
	this.getSettings = function() {
		
		return arr_settings;
	};
	
	this.close = function() {
		
		arr_size_change.func_stop();
	};
	
	// Initialise
	
	obj.update(arr_options);

	// Listeners
	
	var polling_buffer = new PollingBuffer();
	
	elm_bar_inner.on('mousedown touchstart', function(e) {
		
		e.preventDefault();
							
		var pos_mouse_xy = {x: (e.pageX != undefined ? e.pageX : e.originalEvent.touches[0].pageX), y: (e.pageY != undefined ? e.pageY : e.originalEvent.touches[0].pageY)};
		if (e.target == elm_bar_inner[0]) {
			var movement = 'move';
		} else if (e.target == elm_day[0]) {
			var movement = 'time';
		} else {
			var movement = (e.target == elm_handler_left[0] ? 'resize-left' : 'resize-right');
		}
		var has_moved = false;
		
		if (movement == 'time') {
			var pos_bar = elm_bar_inner[0].getBoundingClientRect();
			var width_bar = ((calc_max - calc_min)*calc_one_width);
			var cur_paused = paused;
			paused = true;
		} else {
			var pos_bar = elm_bar[0].getBoundingClientRect();
			var width_bar = width;
		}
	
		$(document).on('mousemove.tschuifje touchmove.tschuifje', function(e2) {
			
			e2.preventDefault();
			
			var cur_pos_mouse_xy = {x: (e2.pageX != undefined ? e2.pageX : e2.originalEvent.touches[0].pageX), y: (e2.pageY != undefined ? e2.pageY : e2.originalEvent.touches[0].pageY)};
			var calc_difference = calc_one_value * (cur_pos_mouse_xy.x - pos_mouse_xy.x);
			
			if (movement != 'time' && !has_moved && (cur_pos_mouse_xy.x != pos_mouse_xy.x || cur_pos_mouse_xy.y != pos_mouse_xy.y)) { // Check for real movement because of chrome 'always trigger move'-bug
				elm.addClass('moving '+(movement == 'move' ? 'move' : 'resize'));
				SCRIPTER.triggerEvent(elm, 'movingstart');
				has_moved = true;
			}
									
			polling_buffer.run(function() {
				
				var movement_new = func_move(movement, calc_difference);
				
				if (movement_new) {
						
					func_draw();
						
					movement = movement_new;
				}
					
				if (cur_pos_mouse_xy.x < pos_bar.left) {
					pos_mouse_xy.x = pos_bar.left;
				} else if (cur_pos_mouse_xy.x > (pos_bar.left + width_bar)) {
					pos_mouse_xy.x = (pos_bar.left + width_bar);
				} else {
					pos_mouse_xy.x = cur_pos_mouse_xy.x;
				}
				pos_mouse_xy.y = cur_pos_mouse_xy.y;
			});
			
		}).one('mouseup.tschuifje touchend.tschuifje', function() {
			
			if (movement != 'time' && has_moved) {
				elm.removeClass('moving move resize');
				SCRIPTER.triggerEvent(elm, 'movingstop');
			}
			if (movement == 'time') {
				paused = cur_paused;
			}
	
			$(document).off('mousemove.tschuifje touchmove.tschuifje mouseup.tschuifje touchend.tschuifje');
		});
	});
	
	elm_button_right.add(elm_button_left).on('mousedown', function(e) {
		
		var dating_amount = (hasElement(elm_button_right[0], e.target, true) ? Math.abs(arr_settings.dating.amount) : -Math.abs(arr_settings.dating.amount));
		
		var arr_update = {move: {}};
		arr_update.move[arr_settings.dating.unit] = dating_amount;
		obj.update(arr_update);
		
		var timeout = false;
		var count = 0;
		var func_timer = function() {
			
			var timer = (!count ? 500 : 100);
			count++;
			
			timeout = setTimeout(function() {
				obj.update(arr_update);
				func_timer();
			}, timer);
		};
		
		func_timer();
												
		$(document).one('mouseup', function() {
			
			clearTimeout(timeout);
		});
	}).on('mouseenter touchstart', function(e) {
		
		$(document).on('mousemove.tschuifje_button touchend.tschuifje_button', function(e2) {
			
			if (e2.type == 'mousemove') {
				if (!hasElement(elm_button_left[0], e2.target, true) && !hasElement(elm_button_right[0], e2.target, true)) {
					$(document).off('.tschuifje_button');
				}
			} else {
				$(document).off('.tschuifje_button');
			}
		});
	});
	
	elm_date_min.on('change', function(e) {
		obj.update({min: DATEPARSER.int2Date(DATEPARSER.strDate2Int(elm_date_min.val()))});
	});
	elm_date_max.on('change', function(e) {
		obj.update({max: DATEPARSER.int2Date(DATEPARSER.strDate2Int(elm_date_max.val()))});
	});
	
	elm_speed_amount.on('change update', function(e) {
		var amount = elm_speed_amount.val();
		if (amount < 1) {
			amount = 1;
			elm_speed_amount.val(amount);
		}
		obj.update({dating: {amount: amount}});
	});
	elm_speed_unit.on('change update', function(e) {
		obj.update({dating: {unit: elm_speed_unit.val()}});
	});
	
	elm_play.on('click', function(e) {
		obj.update({player: {action: 'play'}});
	});
	elm_pause.on('click', function(e) {
		obj.update({player: {action: 'pause'}});
	});
	elm_stop.on('click', function(e) {
		obj.update({player: {action: 'stop'}});
	});
	elm_speed_reverse.on('change', function(e) {
		obj.update({player: {reverse: elm_speed_reverse.is(':checked')}});
	});
}

function UISelection() {
	
	var obj = this;
	
	this.public_user_interface_id = false;
	this.elm_module = false;
	this.elm_list_container = false;
	this.url = false;
	
	var elm_toolbox = false;
	var elm_selections_hover = false;

	var arr_server_data = {'labels': {}, 'icons': {}};
		
	ASSETS.getIcons(false, ['close', 'plus', 'pages', 'print', 'link', 'users', 'updown', 'email'], function(response_data) {
		
		arr_server_data['icons'] = response_data;
	});
		
	this.init = function(id, elm) {

		obj.public_user_interface_id = id;
		obj.elm_list_container = $(elm);
		obj.elm_module = elm.closest('.ui');	
		obj.url = elm.attr('data-url');
		
		elm_toolbox = getContainerToolbox(obj.elm_list_container),
		elm_selections_hover = $('<div></div>').addClass('selections-hover hide').appendTo(elm_toolbox)

		func_check_external_selection();
		func_list_selections(false);
		
		if (elm.attr('data-print_selection_id')) {
			var print_selection_id = elm.attr('data-print_selection_id');
			var arr_selection = func_get_selection(print_selection_id);
			$('<div id="y:ui_selection:print_selection-0"></div>').appendTo(obj.elm_list_container).data({value: arr_selection, target: obj.elm_module.find('.object'), options: {'html': 'append'}}).quickCommand();
		}
		
		new ResizeSensor(obj.elm_list_container[0], func_check_height);
	}
	
	this.handleElement = function(elm_button) {

		var elm = elm_button;
		var elm_id = elm.attr('data-elm_id');
		var elm_name = elm.attr('data-elm_name');
		var elm_thumbnail = elm.attr('data-elm_thumbnail');
		var arr_selection_ids = func_get_selection_ids(false);
		var arr_selections = func_get_selections();
		
		var obj_elm = {elm_id: elm_id, elm_name: elm_name, elm_thumbnail: elm_thumbnail};

		if (arr_selection_ids.length > 1) { // Multiple selections

			func_create_selections_hover_buttons(obj_elm, elm, false);
			
			elm.on('mouseenter', function() {
				
				if (obj.elm_list_container.hasClass('view')) {
					action = 'edit';
				} else {
					action = false;
				}
				
				var cur = $(this);
				
				func_create_selections_hover_buttons(obj_elm, cur, action);
				
				var pos = cur[0].getBoundingClientRect();
				var pos_mod = elm_toolbox[0].getBoundingClientRect();
				
				var top = pos.top + elm.height() - pos_mod.top;
				var left = pos.left - elm_selections_hover.width() + elm.width() - pos_mod.left;
				
				elm_selections_hover.clearQueue().css({top: top, left: left}).removeClass('hide');
				
			}).on('mouseleave', function() {
				
				elm_selections_hover.clearQueue().delay(300).queue(function(){
					$(this).addClass('hide');
				});	
			});
			
			elm_selections_hover.on('mouseenter', function() {
				
				$(this).clearQueue();
				
			}).on('mouseleave', function() {
				
				$(this).addClass('hide');
			});
				
		} else {
		
			var selection_id = arr_selection_ids[0];
			var arr_selection = arr_selections[selection_id];
		
			if (arr_selection && arr_selection['elements'][elm_id]) { // One selection in which object is present
			
				elm.addClass('in-selection').attr('data-selection_id', selection_id)
				elm.on('click', function() {
					func_list_selections($(this).attr('data-selection_id'));
				});
			
			} else { // One selection in which object is not present
	
				elm.attr('data-selection_id', selection_id);
				elm.on('click', function() {
					
					if (obj.elm_list_container.hasClass('view')) {
						action = 'edit';
					} else {
						action = false;
					}

					func_add_element(obj_elm, $(this).attr('data-selection_id'), 'object', action);
					
					obj.handleElement(elm);
				});
			}
		}
	};
	
	var func_create_selections_hover_buttons = function(obj_elm, elm, action) {
	
		elm_selections_hover.empty();
		var arr_selections = func_get_selections();
		
		for (var selection_id in arr_selections) {
			
			var arr_selection = arr_selections[selection_id];
			var selection_div = $('<div></div>').attr('data-selection_id', selection_id).appendTo(elm_selections_hover);
			var selection_name = $('<span></span>').html(arr_selection['selection_title']).appendTo(selection_div);
			
			if (arr_selection['elements'][obj_elm.elm_id]) {

				elm.addClass('in-selection');
				selection_div.addClass('in-selection').on('click', function() {
					func_list_selections($(this).attr('data-selection_id'));
					elm_selections_hover.addClass('hide');
				});
				
			} else {
				
				selection_div.on('click', function() {

					func_add_element(obj_elm, $(this).attr('data-selection_id'), 'object', action);
					elm.addClass('in-selection');
					elm_selections_hover.addClass('hide');
				});
			}
		}		
	}
	
	var func_add_element = function(obj_elm, selection_id, elm_type, action) {

		var selection_id = (selection_id ? selection_id : func_add_selection());
		var arr_selection = func_get_selection(selection_id);
		
		if (elm_type == 'object') {
			
			arr_selection['count'] = arr_selection['count'] + 1;
		}
	
		if (!arr_selection['elements'][obj_elm.elm_id]) {
		
			var sort = Object.keys(arr_selection['elements']).length;
			arr_selection['elements'][obj_elm.elm_id] = {elm_id: obj_elm.elm_id, elm_notes: '', sort: sort, elm_type: elm_type, elm_name: obj_elm.elm_name, elm_thumbnail: obj_elm.elm_thumbnail};
			
			func_store_selection(selection_id, arr_selection);
		}
		
		if (action == 'edit') {
			func_list_selections(selection_id);
		} else {
			func_list_selections(false);
		}
	};
	
	var func_remove_element = function(elm_id, selection_id) {
			
		var arr_selection = func_get_selection(selection_id);
		var elm_sort = arr_selection['elements'][elm_id]['sort'];
		
		if (arr_selection['elements'][elm_id]['elm_type'] == 'object') {
			arr_selection['count'] = arr_selection['count'] - 1;
		}
		
		for (var loop_elm_id in arr_selection['elements']) {
			if (arr_selection['elements'][loop_elm_id]['sort'] > elm_sort) {
				arr_selection['elements'][loop_elm_id]['sort']--;
			}
		}
	
		delete arr_selection['elements'][elm_id];
	
		func_store_selection(selection_id, arr_selection);
		func_list_selections(selection_id);
	};
	
	var func_create_selection_options = function(selection_id) {

		var arr_external_selections = func_get_selection_ids(true);
		
		if (arr_external_selections[selection_id]) {
			
			var external = true;
			var arr_selection = {selection_title: arr_external_selections[selection_id]['selection_title']};
			
		} else {
				
			var arr_selection = func_get_selection(selection_id);
			
		} 
		
		var elm_header_container = $('<div class="head"></div>').appendTo(obj.elm_list_container);
		var elm_header = $('<h1></h1>').html(arr_selection['selection_title']).appendTo(elm_header_container);
		var elm_navigation_buttons = $('<div class="navigation-buttons"><button class="close" type="button"><span class="icon" data-category="full">'+ arr_server_data.icons['close'] +'</span></button></div>').appendTo(elm_header_container);
		
		elm_navigation_buttons.on('click', function() {
			func_list_selections(false);
		});
		
		var elm_options = $('<menu class="buttons" data-selection_id="' + selection_id + '"></menu>').appendTo(obj.elm_list_container);
		var elm_button_add_selection = $('<button class="add-selection" value="" type="button" title="New selection"><span class="icon">'+ arr_server_data.icons['plus'] +'</span></button>').appendTo(elm_options);
		var elm_button_remove_selection = $('<button class="remove-selection" value="" type="button" title="Remove selection"><span class="icon">'+ arr_server_data.icons['close'] +'</span></button>').appendTo(elm_options);
		var elm_button_bookify_selection = $('<button class="bookify-selection" value="" type="button" title="Generate formatted PDF from selection"><span class="icon">'+ arr_server_data.icons['pages'] +'</span></button>').appendTo(elm_options);
		var elm_button_print_selection = $('<button class="print-selection" value="" type="button" title="Generate local print out from selection" data-href="'+ obj.url + '/selection-print/' + selection_id + '"><span class="icon">'+ arr_server_data.icons['print'] +'</span></button>').appendTo(elm_options);
		var elm_button_url_selection = $('<button class="url-selection" value="" type="button" title="Get selection URL"><span class="icon">'+ arr_server_data.icons['link'] +'</span></button>').appendTo(elm_options);
		var elm_button_share_selection = $('<button class="share-selection" value="" type="button" title="Share selection"><span class="icon">'+ arr_server_data.icons['users'] +'</span></button>').appendTo(elm_options);
		var elm_url_share_container = $('<span></span>').appendTo(elm_options);
		
		elm_options.on('click', 'button', function() {
			
			var cur = $(this);
			var selection_id = cur.closest('[data-selection_id]').attr('data-selection_id');	
				
			if (cur.hasClass('add-selection')) {
				
				var new_selection_id = func_add_selection();
				func_list_selections(new_selection_id);
				
			} else if (cur.hasClass('remove-selection')) {
				
				func_remove_selection(selection_id);
				
			} else if (cur.hasClass('bookify-selection')) {
				
				func_bookify_selection(selection_id);
				
			} else if (cur.hasClass('url-selection')) {
				
				func_get_selection_url(selection_id, false);
				
			} else if (cur.hasClass('share-selection')) {
				
				func_get_selection_url(selection_id, true);
			}
		});		
	}
	
	var func_list_external_selection = function(selection_id) {
	
		$('<div id="y:ui_selection:get_selection_data-0"></div>').appendTo(obj.elm_list_container).data({value: selection_id}).quickCommand(function(arr_selection) {
		
			var elm_selection_options = func_create_selection_options(selection_id);
			
			var elm_selection_editor = $('<h3></h3>').html(arr_selection['selection_editor']).appendTo(obj.elm_list_container);
			var elm_selection_notes = $('<p></p>').html(arr_selection['selection_notes']).appendTo(obj.elm_list_container);	
			var elm_selection = $('<ul data-selection_id="'+ selection_id +'" class="external"></ul>').appendTo(obj.elm_list_container);
			
			var arr_selection_elms = arr_selection['elements'];
			
			// sort
			var arr_sort_selection_elms = [];
			for (var elm_id in arr_selection_elms) {
				
				arr_sort_selection_elms[arr_selection_elms[elm_id]['sort']] = elm_id;
			}
			
			for (var sort in arr_sort_selection_elms) {
			
				var arr_elm = arr_selection_elms[arr_sort_selection_elms[sort]];
				var elm = $('<li data-elm_id="'+ arr_elm['elm_id'] +'"></li>').appendTo(elm_selection);
				var elm_content = $('<div></div>').appendTo(elm);
				
				if (arr_elm['elm_type'] == 'object') {
					
					if (arr_elm['elm_thumbnail']) {
						
						var elm_thumbnail = $('<div id="y:ui_data:show_project_type_object-' + arr_elm['elm_id']  + '"></div>');				
						elm_thumbnail.addClass('a object-thumbnail quick').data({target: obj.elm_module.find('.object'), method: 'show_project_type_object', module: 'ui_data', options: {'html': 'append'}});
						elm_thumbnail.appendTo(elm_content);
						
						if (arr_elm['elm_thumbnail']['object_thumbnail']) {
							var elm_image = $('<div class="image"></div>').css('background-image', 'url(' + arr_elm['elm_thumbnail']['object_thumbnail'] + ')').appendTo(elm_thumbnail);
						} else {
							elm_thumbnail.addClass('no-image');
						}
						
						var elm_name = $('<div class="name"><span>'+arr_elm['elm_thumbnail']['object']['object_name']+'</span></div>').appendTo(elm_thumbnail);	
						
						elm_thumbnail.on('click', function() {
							
							$(this).delay(1000).queue(function() {
								
								if (parseInt(obj.elm_list_container.css('width'), 10) == parseInt($(window).width(), 10)) {
									
									func_list_selections();
								}
								
							});					
						});
					}
								
				} else if (arr_elm['elm_type'] == 'heading') {
				
					elm_content.addClass('heading');
					var elm_heading = $('<h4></h4>').html(arr_elm['elm_heading']).appendTo(elm_content);
					
				}
				
				var elm_notes = $('<p></p>').html(arr_elm['elm_notes']).appendTo(elm_content);
				
			}
			
			
			func_check_height();
		});
	}
	
	var func_list_selection = function(selection_id) {
		
		var elm_selection_options = func_create_selection_options(selection_id);

		var elm_selection = $('<div class="options fieldsets"><form><fieldset><ul data-selection_id="'+ selection_id +'"></ul></fieldset></form></div>').appendTo(obj.elm_list_container);
		elm_selection = elm_selection.find('ul');
		
		var arr_selection = func_get_selection(selection_id);
		var elm_selection_title = $('<li><input type="text" name="selection_title" value="' + arr_selection['selection_title'] + '" placeholder="Enter a title here" /></li>').appendTo(elm_selection);
		var elm_selection_editor = $('<li><input type="text" name="selection_editor" value="' + arr_selection['selection_editor'] + '" placeholder="Enter your name here" /></li>').appendTo(elm_selection);
		var elm_selection_notes = $('<li><textarea placeholder="Notes" name="selection_notes">' + arr_selection['selection_notes'] + '</textarea></li>').appendTo(elm_selection);
		var elm_add_heading = $('<li><button type="button" class="selection-add-elm" value="" data-elm_type="heading"><span>Add heading</span></button></li>').appendTo(elm_selection);
		
		elm_add_heading.on('click', function() {

			var cur = $(this);
			var selection_id = cur.closest('[data-selection_id]').attr('data-selection_id');			
			var elm_id = 'heading_' + Math.floor(Math.random() * 1000000000);
			func_add_element({elm_id: elm_id}, selection_id, 'heading', 'edit');
		});
		
		var elm_selection_sorter = $('<li><ul class="sorter"></ul></li>').appendTo(elm_selection);
		elm_selection_sorter = elm_selection_sorter.find('ul.sorter');
		
		var arr_selection_elms = arr_selection['elements'];

		// sort
		var arr_sort_selection_elms = [];
		for (var elm_id in arr_selection_elms) {
			
			arr_sort_selection_elms[arr_selection_elms[elm_id]['sort']] = elm_id;
		}
	
		var get_data = false;
		var arr_get_data_ids = {};
		
		for (var sort in arr_sort_selection_elms) {
		
			var arr_elm = arr_selection_elms[arr_sort_selection_elms[sort]];
			var elm = $('<li data-elm_id="'+ arr_elm['elm_id'] +'"></li>').appendTo(elm_selection_sorter);
			var elm_handle = $('<span class="icon">'+ arr_server_data.icons['updown'] +'</span>').appendTo(elm);
			var elm_content_container = $('<div></div>').appendTo(elm);
			var elm_content = $('<div></div>').appendTo(elm_content_container);
			
			if (arr_elm['elm_type'] == 'object') {
				
				var elm_thumbnail = $('<div id="y:ui_data:show_project_type_object-' + arr_elm['elm_id']  + '"></div>');				
				elm_thumbnail.addClass('a object-thumbnail quick').data({target: obj.elm_module.find('.object'), options: {'html': 'append'}});
				elm_thumbnail.appendTo(elm_content);
				
				if (arr_elm['elm_thumbnail']) {
					
					var elm_image = $('<div class="image"></div>').css('background-image', 'url(' + arr_elm['elm_thumbnail'] + ')').appendTo(elm_thumbnail);
					
				} else {
					
					elm_thumbnail.addClass('no-image');
					
				}
				
				var elm_name = $('<div class="name"><span>'+arr_elm['elm_name']+'</span></div>').appendTo(elm_thumbnail);
			
				if (!arr_elm['elm_name']) {
					get_data = true;
					arr_get_data_ids[arr_elm['elm_id']] = arr_elm['elm_id'];
				}
				
				elm_thumbnail.on('click', function() {
					
					$(this).delay(300).queue(function() {
						
						if (parseInt(obj.elm_list_container.css('width'), 10) == parseInt($(window).width(), 10)) {
							func_list_selections();
						}
					});					
				});
			
			} else if (arr_elm['elm_type'] == 'heading') {
				
				elm_content.addClass('heading');
				var elm_heading = $('<input type="text" name="selection_elm_heading" value="' + (arr_elm['elm_heading'] ? arr_elm['elm_heading'] : '') + '" placeholder="Enter a heading here" />').appendTo(elm_content);
			}
			
			var elm_remove_button = $('<button class="selection-remove-elm" value="" type="button"><span class="icon" category="full">'+ arr_server_data.icons['close'] +'</span></button>').appendTo(elm_content);
			var elm_notes = $('<textarea placeholder="Notes" name="selection_elm_notes" >' + arr_elm['elm_notes'] + '</textarea>').appendTo(elm_content);
			
			elm_remove_button.on('click', function() {
				
				var cur = $(this);
				var selection_id = cur.closest('[data-selection_id]').attr('data-selection_id');
				var elm_id = cur.closest('[data-elm_id]').attr('data-elm_id');				
				func_remove_element(elm_id, selection_id);
			});
		}
		
		if (get_data) {
		
			func_get_data(arr_get_data_ids, selection_id);
		}
		
		func_check_height();
		
		// store new sort
		elm_selection_sorter.sorter().on('sort', function() {
		
			var list = $(this);
			var selection_id = list.closest('[data-selection_id]').attr('data-selection_id');
			var arr_selection = func_get_selection(selection_id);
			var i = 0;
			
			list.children('li').each(function() {

				var elm_id = $(this).attr('data-elm_id');
				arr_selection['elements'][elm_id]['sort'] = i;
				i++;
			});
			
			func_store_selection(selection_id, arr_selection);
		});	
		
		elm_selection.on('keyup', 'input, textarea', function() {
			
			var cur = $(this);
			
			var selection_id = cur.closest('[data-selection_id]').attr('data-selection_id');
			var elm_id = cur.closest('[data-elm_id]').attr('data-elm_id');
						
			var arr_selection = func_get_selection(selection_id);
					
			if (cur.is('[name=selection_title]')) {
				
				cur.closest('.selections-container').find('h1').html(cur.val());
				
				arr_selection['selection_title'] = cur.val();
				
			} else if (cur.is('[name=selection_editor]')) {
				arr_selection['selection_editor'] = cur.val();
			} else if (cur.is('[name=selection_notes]')) {
				arr_selection['selection_notes'] = cur.val();
			} else if (cur.is('[name=selection_elm_heading]')) {
				arr_selection['elements'][elm_id]['elm_heading'] = cur.val();
			} else if (cur.is('[name=selection_elm_notes]')) {
				arr_selection['elements'][elm_id]['elm_notes'] = cur.val();
			}
			
			func_store_selection(selection_id, arr_selection);
		});
	}
	
	var func_check_height = function() {
		
		obj.elm_module.css('min-height', '100vh');
		obj.elm_list_container.css('min-height', '');
		
		var elm_selection_height = parseInt(obj.elm_list_container.height());
		var elm_module_height = parseInt(obj.elm_module.height());
		var body = document.body;
		var html = document.documentElement;

		var document_height = Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight);
		
		if (obj.elm_list_container.hasClass('view') && document_height > elm_selection_height) {
			
			obj.elm_list_container.css('min-height', document_height);
		}
		
		if (obj.elm_list_container.hasClass('view') && elm_selection_height > elm_module_height) {
		
			obj.elm_module.css('min-height', elm_selection_height);
		}							
	}
	
	var func_list_selections = function(set_selection_id) {

		var arr_selections = func_get_selections();
		var arr_external_selections = func_get_selection_ids(true);
		
		obj.elm_list_container.empty().removeClass('view list');
		
		if (set_selection_id) {
			
			obj.elm_list_container.addClass('view');
			
			if (arr_external_selections[set_selection_id]) {
				
				func_list_external_selection(set_selection_id);
				
			} else {
			
				func_list_selection(set_selection_id);
			}
			
		} else {

			obj.elm_list_container.addClass('list');			
			
			for (var selection_id in arr_selections) {
				
				if (set_selection_id == selection_id) {
					
					continue;
				}
		
				var arr_selection = arr_selections[selection_id];
				var selection_div = $('<div></div>')
										.attr('data-selection_id', selection_id)									
										.appendTo(obj.elm_list_container);
				
				selection_div.on('click', function() {

					func_list_selections($(this).attr('data-selection_id'));
				})						
										
				var selection_name = $('<span></span>').html(arr_selection['selection_title']).appendTo(selection_div);
				var selection_count = $('<span></span>').html(arr_selection['count']).appendTo(selection_div);

			}
				
			for (var selection_id in arr_external_selections) {
				
				var selection_div = $('<div></div>')
											.attr('data-selection_id', selection_id)
											.appendTo(obj.elm_list_container);
											
				var selection_name = $('<span></span>').html(arr_external_selections[selection_id]['selection_title']).appendTo(selection_div);
				
				if (arr_external_selections[selection_id]['open']) {
					
					arr_external_selections[selection_id]['open'] = false;
					localStorage['pui_'+obj.public_user_interface_id+'_external_selections'] = JSON.stringify(arr_external_selections);
					
					func_list_selections(selection_id);
				}
				
				selection_div.on('click', function() {

					func_list_selections($(this).attr('data-selection_id'));
				})		
				
				
			}
			
			func_check_height();
		}		
	}
	
	var func_get_selection_ids = function(external) {
		
		var arr_selection_ids = [];
			
		if (localStorage['pui_'+obj.public_user_interface_id + (external ? '_external' : '') + '_selections']) {

			var arr_selection_ids = JSON.parse(localStorage['pui_' + obj.public_user_interface_id + (external ? '_external' : '') + '_selections']);
		}
		
		return arr_selection_ids;
	}
	
	var func_get_selections = function() {
		
		var arr_selection_ids = func_get_selection_ids(false);
		var arr_selections = {};
		
		for (var i = 0; i < arr_selection_ids.length; i++) {
			
			var selection_id = arr_selection_ids[i];
			
			if (localStorage['pui_selection_'+selection_id]) {
				
				arr_selections[selection_id] = func_get_selection(selection_id);

			}
		}

		return arr_selections;
	}
	
	var func_get_selection = function(selection_id) {

		var arr_selection = false;

		if (localStorage['pui_selection_'+selection_id]) {
			arr_selection = JSON.parse(localStorage['pui_selection_'+selection_id]);
		}

		return arr_selection;
	};
	
	var func_store_selection = function(selection_id, arr_selection) {

		localStorage['pui_selection_'+selection_id] = JSON.stringify(arr_selection);
	};
	
	var func_add_selection = function() {
		
		if (!localStorage['pui_'+obj.public_user_interface_id+'_selections']) {
			
			var arr_selection_ids = [];
			
		} else {
			
			var arr_selection_ids = JSON.parse(localStorage['pui_'+obj.public_user_interface_id+'_selections']);
		}
		
		var new_selection_id = Math.floor(Math.random() * 1000000000);
		arr_selection_ids.push(new_selection_id);
		
		localStorage['pui_'+obj.public_user_interface_id+'_selections'] = JSON.stringify(arr_selection_ids);
		localStorage['pui_selection_'+new_selection_id] = JSON.stringify({pui_id: obj.public_user_interface_id, id: new_selection_id, selection_title: 'New Selection ' + arr_selection_ids.length, selection_editor: '', selection_notes: '', count: 0, url_id: '', elements: {}});
		
		return new_selection_id;
	}
	
	var func_remove_selection = function(selection_id) {
		
		var arr_selection_ids = func_get_selection_ids();
		var arr_external_selections = func_get_selection_ids(true);

		if (arr_external_selections[selection_id]) {

			delete arr_external_selections[selection_id];
			localStorage['pui_'+obj.public_user_interface_id+'_external_selections'] = JSON.stringify(arr_external_selections);		
			
		} else {
				
			var arr_selection = func_get_selection(selection_id);
			
			for (var i = 0; i < arr_selection_ids.length; i++) {
				if (arr_selection_ids[i] == selection_id) {
					arr_selection_ids.splice(i, 1);
				}
			}
			
			if (arr_selection['url_id']) {
				
				$('<div id="y:ui_selection:remove_selection-'+arr_selection['url_id']+'"></div>').appendTo(obj.elm_list_container).quickCommand();	
			}
			
			localStorage['pui_'+obj.public_user_interface_id+'_selections'] = JSON.stringify(arr_selection_ids);
			localStorage.removeItem('pui_selection_'+selection_id);
			
		}	
		
		func_list_selections();	
	}
	
	var func_get_selection_url = function(selection_id, share) {

		var arr_external_selections = func_get_selection_ids(true);

		if (arr_external_selections[selection_id]) {
			
			// external selection
			var arr_selection = arr_external_selections[selection_id];
			var arr_selection = {selection_title: arr_selection['selection_title'], url_id: selection_id}
			
		} else {
			
			var arr_selection = func_get_selection(selection_id);
		}
		
		if (!arr_selection) {
			return false;
		}
		
		$('<div id="y:ui_selection:store_selection-0"></div>').appendTo(obj.elm_list_container).data({value: arr_selection}).quickCommand(function(arr_data) {
		
			if (!arr_selection['url_id']) {
				
				arr_selection['url_id'] = arr_data['id'];
				func_store_selection(selection_id, arr_selection);
			}
			
			var url = arr_data['url'];
			var elm_target = obj.elm_list_container.find('menu > span').empty();
			
			if (share) {
				
				if (elm_target.hasClass('share')) {
					elm_target.removeClass('share');
					return;
				} else {
					elm_target.removeClass('url').addClass('share');
				}
				
				var arr_shares = arr_data['arr_shares'];
				
				for (var i = 0; i < arr_shares.length; i++) {
					
					var arr_share = arr_shares[i];
					var elm_icon = (arr_share['icon_class'] ? '<span class="' + arr_share['icon_class'] + '"></span>' : '<span class="icon">'+ arr_server_data.icons['email'] +'</span>'); 
					var elm_share_button = $('<button value="" type="button" data-href="' + arr_share['share_url'] + url + '" title="' + arr_share['share_name'] + '">' + elm_icon + '</button>').appendTo(elm_target);
				}
				
			} else {
				
				if (elm_target.hasClass('url')) {
					elm_target.removeClass('url');
					return;
				} else {
					elm_target.removeClass('share').addClass('url');
				}
				
				var elm_input = $('<input type="text" value="' + url + '">').appendTo(elm_target).select();
			}
						
		});
	}
	
	var func_check_external_selection = function() {
		
		var new_external_selection_id = obj.elm_list_container.attr('data-new_external_selection_id');
		
		if (new_external_selection_id) {
			
			var new_external_selection_title = obj.elm_list_container.attr('data-new_external_selection_title');
			
			if (localStorage['pui_'+obj.public_user_interface_id+'_external_selections']) {
				
				var arr_external_selections = JSON.parse(localStorage['pui_'+obj.public_user_interface_id+'_external_selections']);
				
			} else {
				
				var arr_external_selections = {};
			}

			arr_external_selections[new_external_selection_id] = {pui_id: obj.public_user_interface_id, selection_title: new_external_selection_title, open: true};
			
			localStorage['pui_'+obj.public_user_interface_id+'_external_selections'] = JSON.stringify(arr_external_selections);
			
			obj.elm_list_container.removeAttr('data-new_external_selection_id');
			obj.elm_list_container.removeAttr('data-new_external_selection_title');
		}
	}
	
	
	var func_get_data = function(arr_get_data_ids, selection_id) {
	
		$('<div id="y:ui_selection:get_selection_data-0"></div>').appendTo(obj.elm_list_container).data({value: arr_get_data_ids}).quickCommand(function(arr_data) {
			
			var arr_selection = func_get_selection(selection_id);
			
			for (var elm_id in arr_data) {

				var elm = arr_selection[elm_id];
				arr_selection['elements'][elm_id]['elm_name'] = arr_data[elm_id]['object']['object_name'];
				arr_selection['elements'][elm_id]['elm_thumbnail'] = arr_data[elm_id]['object_thumbnail'];

			}
			
			func_store_selection(selection_id, arr_selection);
			func_list_selections(selection_id);
		});
	}
	
	var func_bookify_selection = function(selection_id) {
		
		var arr_selection = func_get_selection(selection_id);
		
		$('<div id="y:ui_selection:get_bookify_selection_data-0"></div>').appendTo(obj.elm_list_container).data({value: {selection_id: selection_id, arr_selection: arr_selection}}).quickCommand(function(data) {
			
			ASSETS.fetch({script: [
				'/js/pdfmake.min.js',
				'/js/vfs_fonts.js'
			]}, function() {
				
				var docDefinition = {content: [], styles: {}};
				
				var func_get_content = function(arr_content, width) {
					
					if (arr_content['elm_type'] == 'image') {
						
						var content_data = {image: arr_content['content'], width: width, margin: [0, 0, 0, 0]};
						
					} else if (arr_content['elm_type'] == 'image_full') {
						
						var content_data = {image: arr_content['content'], width: width, margin: [0, 0, 0, 30]};
						
					} else {
						
						var content;
						var temp_elm = $('<div></div>').html(arr_content['content']);
						
						if (temp_elm.find('.body').length) {
							
							content = temp_elm.find('.body').text();
							
						} else {
							
							content = temp_elm.text();
							
						}
						
						var content_data = {text: content.trim().replace(/\t/g, ''), style: arr_content['style'], pageBreak: arr_content['pagebreak']};
					}
					
					return content_data;
				}

				for (var i = 0; i < data.length; i++) {
					
					if (data[i]['elm_type'] == 'article') {
						
						if (data[i]['content']['title']) {
							
							docDefinition.content.push(func_get_content(data[i]['content']['title'], 500));

							if (data[i]['content']['section_2'].length > 0) {
								
								var col_1 = [],
								col_2 = [];
								
								for (var j = 0; j < data[i]['content']['section_1'].length; j++) {
									col_1.push(func_get_content(data[i]['content']['section_1'][j], 400));
								}
								
								for (var j = 0; j < data[i]['content']['section_2'].length; j++) {
									col_2.push(func_get_content(data[i]['content']['section_2'][j], 100));
								}
								
								docDefinition.content.push({table: {widths: [400, 100], body: [[col_1, col_2]]}, layout: 'noBorders'});
								
							} else {
								for (var j = 0; j < data[i]['content']['section_1'].length; j++) {
									docDefinition.content.push(func_get_content(data[i]['content']['section_1'][j], 500));
								}
							}
						}
					} else {
						
						docDefinition.content.push(func_get_content(data[i]), 500);
					}
				}

				docDefinition.footer = function(currentPage) { 
					return { text: currentPage.toString(), alignment: 'center' };
				};
				
				pdfMake.fonts = { lmroman: { normal: 'lmroman10-regular.ttf', bold: 'lmroman10-bold.ttf', italics: 'lmroman10-italic.ttf', bolditalics: 'lmroman10-bolditalic.ttf' } };
				
				docDefinition.defaultStyle = { font: 'lmroman' };
				docDefinition.styles['title'] = { fontSize: 22, bold: true, alignment: 'center' };
				docDefinition.styles['heading'] = { fontSize: 18, bold: true, alignment: 'center', margin: [0,50,0,20] };
				docDefinition.styles['text'] = { fontSize: 11,  margin: [0, 0, 10, 5], lineHeight: 1.2};
				docDefinition.styles['note'] = { fontSize: 12, italics: true, margin: [0, 5, 0, 5] };
				docDefinition.styles['source'] = { fontSize: 10, margin: [0, 0, 0, 15] };
				
				docDefinition.styles['caption'] = { fontSize: 8, italics: true, margin: [0, 3, 0, 0] };
	
				pdfMake.createPdf(docDefinition).download('Selection.pdf');

			});			
		});
		
	}

}

function DeviceLocation(options) {
		
	var options = $.extend({
		enableHighAccuracy: true,
		timeout: 2000,
		maximumAge: 0
	}, options);

	var current_position = {},
	arr_listeners = {},
	key_move = false;
	
	this.getLocation = function() {
		
		return current_position;
	}
	
	this.addLabMapListener = function(identifier, elm_container) {
		
		var obj = elm_container[0].labmap,
		obj_map = obj.obj_map,
		elm = create_elm_dot(elm_container),
		arr_listener = {elm: elm, obj_map: obj_map, elm_container: elm_container};

		arr_listeners[identifier.data] = arr_listener;
		
		if (!key_move) {
			
			key_move = obj_map.move(rePosition);
		}
	}
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
		
		position_elms();
	}
	
	var create_elm_dot = function(elm_container) {
	
		var stage_ns = 'http://www.w3.org/2000/svg',
		elm_svg = elm_container.find('.paint').children('svg');
		
		if (!elm_svg.length) {
			return false;
		}
		
		var elm = document.createElementNS(stage_ns, 'circle');
	
		elm.setAttribute('cx', 0);
		elm.setAttribute('cy', 0);
		elm.setAttribute('r', 10);
		
		elm.style.fill = 'rgb(66,133,244)';
		elm.style.fillOpacity = 1;
		elm.style.stroke = 'rgb(255,255,255)';
		elm.style.strokeWidth = 2;
		elm.style.strokeOpacity = 1;
		
		elm_svg[0].appendChild(elm);
		
		return elm;
		
	}
	
	var position_elms = function() {

		if (!current_position.coords) {
			
			return false;
		}
		
		for (var arr_listener_id in arr_listeners) {
			
			var arr_listener = arr_listeners[arr_listener_id];
			
			if (!document.body.contains(arr_listener.elm_container[0])) {
				
				delete arr_listeners[arr_listener_id];
				
				continue;
			}
			
			if (arr_listener.elm) {
				
				var elm = arr_listener.elm;
				var obj_map = arr_listener.obj_map;
				
				var arr_position = obj_map.plotPoint(current_position.coords.latitude, current_position.coords.longitude);
				var pos = obj_map.getPosition();					
				var x = Math.floor(arr_position.x - pos.offset.x);
				var y = Math.floor(arr_position.y - pos.offset.y);
			
				elm.setAttribute('transform', 'translate('+(x)+' '+(y)+')');
				
			} else {
				
				arr_listener.elm = create_elm_dot(arr_listener.elm_container);
				
			}
		}
	}

	var success = function(pos) {
		
		current_position = pos;
		
		position_elms();
		
	}
	
	var error = function(err) {
		//console.warn('ERROR(' + err.code + '): ' + err.message);
	}
	
	var location_poller = navigator.geolocation.watchPosition(success, error, options);

}


