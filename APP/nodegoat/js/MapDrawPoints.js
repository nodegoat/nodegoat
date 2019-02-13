
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapDrawPoints(element, obj_parent, options) {

	var elm = $(element),
	obj = this,
	settings = $.extend({
		arr_visual: false
	}, options || {});

	var	arr_data = {},
	svg = false,
	svg_plot = false,
	svg_ns = 'http://www.w3.org/2000/svg',
	elm_svg = false,
	key_move = false,
	
	pos_offset_x = 0,
	pos_offset_y = 0,
	
	obj_map = false;
	
	this.init = function() {
		
		obj_map = obj_parent.obj_map;
					
		elm_svg = document.createElementNS(svg_ns, 'svg');
		elm_svg.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
		elm[0].appendChild(elm_svg);
		
		svg = elm_svg.ownerDocument;
				
		key_move = obj_map.move(rePosition);
	};
	
	this.close = function() {
		
		obj_map.move(null, key_move);
	};
	
	var rePosition = function(move, pos, zoom, calc_zoom) {
		
		if (move === false || calc_zoom) {
	
			// Reposition svg
			var width = elm.width();
			var height = elm.height();
			
			pos_offset_x = pos.offset.x;
			pos_offset_y = pos.offset.y;
			
			elm_svg.style.width = width;
			elm_svg.style.height = height;
			
			if (calc_zoom) {
				obj_parent.doDraw();
			}
		}
	};
	
	this.prepareData = function(arr_data_source) {
		
		arr_data = arr_data_source;
		
		obj_parent.doDraw();
	}
	
	this.drawData = function() {
		
		if (svg_plot) {
			elm_svg.removeChild(svg_plot);
		}
		
		svg_plot = svg.createElementNS(svg_ns, 'g');
		svg_plot.setAttribute('class', 'plot');
		elm_svg.appendChild(svg_plot);
		
		if (!options.arr_visual) {
			return;
		}
		
		for (var key in arr_data.points) {
			
			var arr_point = arr_data.points[key];
			
			var xy = obj_map.plotPoint(arr_point.latitude, arr_point.longitude);
						
			var dot = addDot(xy, options.arr_visual.dot.color);
		}
	};
	
	var addDot = function(xy, color) {
		
		var r = options.arr_visual.dot.size.min;
	
		var x = xy.x - (r/2) - pos_offset_x;
		var y = xy.y - (r/2) - pos_offset_y;
		
		var elm = svg.createElementNS(svg_ns, 'circle');
		elm.setAttribute('cx', x);
		elm.setAttribute('cy', y);
		elm.setAttribute('r', r);
		elm.style.fill = color;
		elm.style.stroke = options.arr_visual.dot.stroke_color;
		elm.style.strokeWidth = options.arr_visual.dot.stroke_width;
		svg_plot.appendChild(elm);
		
		return elm;
	};
};
