
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapGeoUtilities() {

	var obj = this;
	
	var arr_assets_colors_parsed = {};
	
	this.geometryToPackage = function (arr_geometry, arr_geometry_package) {
		
		var arr_geometry = (arr_geometry.type === 'Feature' ? arr_geometry.geometry : arr_geometry);
		var arr_geometry_package = (arr_geometry_package === undefined ? [] : arr_geometry_package);
		
		var arr_coordinates = arr_geometry.coordinates;
		var arr_layers = [];

		switch (arr_geometry.type) {
			case 'Point':
			
				arr_geometry_package.push('Point');
				
				obj.coordinatesToPackage(arr_geometry_package, arr_coordinates);
				
				break;
			case 'MultiPoint':
				
				arr_geometry_package.push('MultiPoint');
				
				for (var i = 0, len = arr_coordinates.length; i < len; i++) {
									
					obj.coordinatesToPackage(arr_geometry_package, arr_coordinates[i]);
				}
				
				break;
			case 'LineString':
			
				arr_geometry_package.push('LineString');
				
				obj.coordinatesToPacking(arr_geometry_package, arr_coordinates);
				
				break;
			case 'Polygon':
			
				arr_geometry_package.push('Polygon');
				
				obj.coordinatesToPacking(arr_geometry_package, arr_coordinates, 1);
				
				break;
			case 'MultiLineString':
			
				arr_geometry_package.push('MultiLineString');
				
				obj.coordinatesToPacking(arr_geometry_package, arr_coordinates, 1);
				
				break;
			case "MultiPolygon":
			
				arr_geometry_package.push('MultiPolygon');
				
				obj.coordinatesToPacking(arr_geometry_package, arr_coordinates, 2);
				
				break;
			case "GeometryCollection":
			
				arr_geometry_package.push('GeometryCollection');
			
				for (var i = 0, len = arr_geometry.geometries.length; i < len; i++) {

					obj.geometryToPackage(arr_geometry.geometries[i], arr_geometry_package);
				}
				
				break;
			default:
				console.log('Invalid GeoJSON object.');
		}
		
		return arr_geometry_package;
	};

	this.coordinatesToPackage = function (arr_geometry_package, arr_coordinates) {
		
		arr_coordinates[0] = parseFloat(arr_coordinates[0]);
		arr_coordinates[1] = parseFloat(arr_coordinates[1]);
		
		arr_geometry_package.push(arr_coordinates);
	};

	this.coordinatesToPacking = function (arr_geometry_package, arr_coordinates, nr_level_deep) {
		
		for (var i = 0, len = arr_coordinates.length; i < len; i++) {
			
			if (nr_level_deep) {
				
				var arr_level = [];
				arr_geometry_package.push(arr_level);
			
				obj.coordinatesToPacking(arr_level, arr_coordinates[i], nr_level_deep - 1);
			} else {
				
				obj.coordinatesToPackage(arr_geometry_package, arr_coordinates[i]);
			}
		}
	};
	
	this.geometryPackageToCenter = function (arr_geometry_package) {

		var len = arr_geometry_package.length;
		
		if (len == 2 && arr_geometry_package[0] == 'Point') {
			
			return arr_geometry_package[1];
		} else {
			
			var arr = [];
			var i_geo = 0;
			var len_i_geo = arr_geometry_package.length;
			
			while (i_geo < len_i_geo) {
				
				var cur_value = arr_geometry_package[i_geo];
				
				if (typeof cur_value === 'string') {
					
					switch (cur_value) {
						case 'Point':
						case 'MultiPoint':
						case 'LineString':
						
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object') {
								
								arr.push(arr_geometry_package[i_geo]);
								
								i_geo++;
							}
							
							break;
						case 'MultiLineString':
							
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object') {
									
								var arr_level_points = arr_geometry_package[i_geo];
									
								arr.push(obj.pointsToCenter(arr_level_points));
								
								i_geo++;
							}
							
							break;
						case 'Polygon':
						
							i_geo++;
							
							// Only need the first set for boundary calculation in case of a polygon with holes
							
							var arr_level_points = arr_geometry_package[i_geo];
							
							arr.push(obj.pointsToCenter(arr_level_points));
							
							i_geo++;
							
							break;
						case 'MultiPolygon':
						
							i_geo++;
						
							while (typeof arr_geometry_package[i_geo] === 'object') {
							
								// Only need the first set for boundary calculation in case of a polygon with holes
								 
								var arr_level_points = arr_geometry_package[i_geo][0];
								
								arr.push(obj.pointsToCenter(arr_level_points));
								
								i_geo++;
							}
							
							break;
						case 'GeometryCollection':
						
							i_geo++;
							
							break;
					}
				} else {
					
					i_geo++;
				}
			}
				
			var len = arr.length;
			var x = 0;
			var y = 0;
				
			for (var i = 0; i < len; i++) {
					
				x += arr[i][0];
				y += arr[i][1];
			}
				
			return [x / len, y / len];
		}
	}
	
	this.pointsToCenter = function(arr) {
		
		var len = arr.length;
		
		if (len == 1) {
			
			return arr[0];
		} else if (len == 2) {
			
			return [(arr[0][0] + arr[1][0]) / 2, (arr[0][1] + arr[1][1]) / 2];
		} else {
				
			var x = function (i) { return arr[i % len][0] }; // Longitude
			var y = function (i) { return arr[i % len][1] }; // Latitude
			
			var twoTimesSignedArea = 0;
			var cxTimes6SignedArea = 0;
			var cyTimes6SignedArea = 0;

			for (var i = 0; i < len; i++) {
				
				var twoSA = x(i)*y(i+1) - x(i+1)*y(i);
				
				twoTimesSignedArea += twoSA;
				cxTimes6SignedArea += (x(i) + x(i+1)) * twoSA;
				cyTimes6SignedArea += (y(i) + y(i+1)) * twoSA;
			}
			
			if (!twoTimesSignedArea) { // Something is not right
				
				var x = 0;
				var y = 0;
				
				for (var i = 0; i < len; i++) {
					
					x += arr[i][0];
					y += arr[i][1];
				}
				
				return [x / len, y / len];
			}
			
			var sixSignedArea = 3 * twoTimesSignedArea;
			var x = cxTimes6SignedArea / sixSignedArea;
			var y = cyTimes6SignedArea / sixSignedArea;
		
			return [x, y]; // Longitude, latitude
		}
	};
	
	this.calcPointOffset = function(p1, p2, offset, perc) {
	
		//Place p3 in between (percentage) p1 and p2
		var p3 = {x: p1.x+(p2.x - p1.x)*perc, y: p1.y+(p2.y - p1.y)*perc};
		
		var vec = {x: p2.x-p1.x, y: p2.y-p1.y}; // vector
		var l = Math.sqrt(vec.x*vec.x+vec.y*vec.y); // vector length, Pythagoras
		var a = {x: vec.y, y: -vec.x}; // shift angle 90 deg
		
		if (l) {
			p3.x += (a.x/l) * offset;
			p3.y += (a.y/l) * offset;
		}
		
		return p3;
	};
	
	this.colorToBrightColor = function(str, percent) {

		var arr_color = arr_assets_colors_parsed['b_'+str];
		
		if (!arr_color) {
			
			var arr_color = parseCssColor(str);
			
			arr_color.r = Math.floor(arr_color.r + (256 - arr_color.r) * percent / 100);
			arr_color.g = Math.floor(arr_color.g + (256 - arr_color.g) * percent / 100);
			arr_color.b = Math.floor(arr_color.b + (256 - arr_color.b) * percent / 100);
			
			arr_assets_colors_parsed['b_'+str] = arr_color;
		}
			
		return arr_color;
	};
	
	this.parseColor = function(str) {
		
		var hex = arr_assets_colors_parsed['h_'+str];
		
		if (!hex) {
			
			var hex = parseCssColorToHex(str);
			arr_assets_colors_parsed['h_'+str] = hex;
		}
		
		return hex;
	};
	
	// Cohen-Sutherland line clippign algorithm, adapted to efficiently
	// handle polylines rather than just segments

	this.lineclip = function(points, bbox, result) {

		var len = points.length,
			codeA = bitCode(points[0], bbox),
			part = [],
			i, a, b, codeB, lastCode;

		if (!result) result = [];

		for (i = 1; i < len; i++) {
			
			a = points[i - 1];
			b = points[i];
			codeB = lastCode = bitCode(b, bbox);

			while (true) {

				if (!(codeA | codeB)) { // accept
					part.push(a);

					if (codeB !== lastCode) { // segment went outside
						part.push(b);

						if (i < len - 1) { // start a new line
							result.push(part);
							part = [];
						}
					} else if (i === len - 1) {
						part.push(b);
					}
					break;

				} else if (codeA & codeB) { // trivial reject
					break;

				} else if (codeA) { // a outside, intersect with clip edge
					a = intersect(a, b, codeA, bbox);
					codeA = bitCode(a, bbox);

				} else { // b outside
					b = intersect(a, b, codeB, bbox);
					codeB = bitCode(b, bbox);
				}
			}

			codeA = lastCode;
		}

		if (part.length) result.push(part);

		return result;
	};

	// Sutherland-Hodgeman polygon clipping algorithm

	this.clipPolygon = function(points, bbox) {

		var result, edge, prev, prevInside, i, p, inside;

		// clip against each side of the clip rectangle
		for (edge = 1; edge <= 8; edge *= 2) {
			result = [];
			prev = points[points.length - 1];
			prevInside = !(bitCode(prev, bbox) & edge);

			for (i = 0; i < points.length; i++) {
				p = points[i];
				inside = !(bitCode(p, bbox) & edge);

				// if segment goes through the clip window, add an intersection
				if (inside !== prevInside) result.push(intersect(prev, p, edge, bbox));

				if (inside) result.push(p); // add a point if it's inside

				prev = p;
				prevInside = inside;
			}

			points = result;

			if (!points.length) break;
		}

		return result;
	};

	// intersect a segment against one of the 4 lines that make up the bbox

	var intersect = function(a, b, edge, bbox) {
		
		return edge & 8 ? [a[0] + (b[0] - a[0]) * (bbox[3] - a[1]) / (b[1] - a[1]), bbox[3]] : // top
			   edge & 4 ? [a[0] + (b[0] - a[0]) * (bbox[1] - a[1]) / (b[1] - a[1]), bbox[1]] : // bottom
			   edge & 2 ? [bbox[2], a[1] + (b[1] - a[1]) * (bbox[2] - a[0]) / (b[0] - a[0])] : // right
			   edge & 1 ? [bbox[0], a[1] + (b[1] - a[1]) * (bbox[0] - a[0]) / (b[0] - a[0])] : // left
			   null;
	};

	// bit code reflects the point position relative to the bbox:

	//         left  mid  right
	//    top  1001  1000  1010
	//    mid  0001  0000  0010
	// bottom  0101  0100  0110

	var bitCode = function(p, bbox) {
		
		var code = 0;

		if (p[0] < bbox[0]) code |= 1; // left
		else if (p[0] > bbox[2]) code |= 2; // right

		if (p[1] < bbox[1]) code |= 4; // bottom
		else if (p[1] > bbox[3]) code |= 8; // top

		return code;
	};
};
