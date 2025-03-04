
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapDrawPoints(element, PARENT, options) {

	var elm = $(element),
	SELF = this,
	settings = $.extend({
		arr_visual: false
	}, options || {});

	var	arr_data = {},
	stage = false,
	stage_ns = 'http://www.w3.org/2000/svg',
	renderer = false,
	drawer = false,
	elm_plot = false,
	key_move = false,
	
	pos_offset_x = 0,
	pos_offset_y = 0,
	pos_offset_extra_x = 0,
	pos_offset_extra_y = 0;
	
	this.init = function() {
					
		renderer = document.createElementNS(stage_ns, 'svg');
		renderer.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', stage_ns);
		
		drawer = renderer;
		elm[0].appendChild(drawer);
		
		stage = renderer.ownerDocument;
				
		key_move = PARENT.obj_map.move(rePosition);
	};
	
	this.close = function() {
		
		PARENT.obj_map.move(null, key_move);
	};
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
		
		if (move === false || calc_zoom === false || calc_zoom) { // Move stop, resize, or zoomed
	
			// Reposition drawer
			var width = pos.size.width;
			var height = pos.size.height;
			
			drawer.style.width = width+'px';
			drawer.style.height = height+'px';
			
			var redraw = (calc_zoom ? true : false);
			
			var x = -pos.x - pos.offset.x - (width/2);
			var y = -pos.y - pos.offset.y - (height/2);

			if (redraw || (x - pos_offset_extra_x) + (pos.view.width/2) > (width/2) || (x - pos_offset_extra_x) - (pos.view.width/2) < -(width/2) || (y - pos_offset_extra_y) + (pos.view.height/2) > (height/2) || (y - pos_offset_extra_y) - (pos.view.height/2) < -(height/2)) {
		
				pos_offset_extra_x = x;
				pos_offset_extra_y = y;

				var str = 'translate('+x+'px, '+y+'px)';
				drawer.style.transform = drawer.style.webkitTransform = str;
				
				redraw = true;
			}

			pos_offset_x = pos.offset.x + pos_offset_extra_x;
			pos_offset_y = pos.offset.y + pos_offset_extra_y;
			
			if (redraw) {
				PARENT.doDraw();
			}
		}
	};
	
	this.prepareData = function(arr_data_source) {
		
		arr_data = arr_data_source;
		
		PARENT.doDraw();
	};
	
	this.drawData = function() {
		
		if (elm_plot) {
			drawer.removeChild(elm_plot);
		}
		
		elm_plot = stage.createElementNS(stage_ns, 'g');
		elm_plot.setAttribute('class', 'plot');
		drawer.appendChild(elm_plot);
		
		if (!options.arr_visual) {
			return;
		}
		
		for (var key in arr_data.points) {
			
			var arr_point = arr_data.points[key];
			
			var xy = PARENT.obj_map.plotPoint(arr_point.latitude, arr_point.longitude);
						
			var dot = addDot(xy, options.arr_visual.dot.color);
		}
	};
	
	var addDot = function(xy, color) {
		
		var r = options.arr_visual.dot.size.min;
	
		var x = xy.x - (r/2) - pos_offset_x;
		var y = xy.y - (r/2) - pos_offset_y;
		
		var elm = stage.createElementNS(stage_ns, 'circle');
		elm.setAttribute('cx', x);
		elm.setAttribute('cy', y);
		elm.setAttribute('r', r);
		elm.style.fill = color;
		elm.style.stroke = options.arr_visual.dot.stroke_color;
		elm.style.strokeWidth = options.arr_visual.dot.stroke_width;
		elm_plot.appendChild(elm);
		
		return elm;
	};
};
