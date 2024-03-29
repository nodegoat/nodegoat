
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2024 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapGeoUtilities(obj_map) {

	var obj = this;
	
	var arr_assets_colors_parsed = {};
		
	this.geometryToPackage = function(arr_geometry, arr_geometry_package) {
		
		var arr_geometry = (arr_geometry.type === 'Feature' ? arr_geometry.geometry : arr_geometry);
		var arr_geometry_package = (arr_geometry_package === undefined ? [] : arr_geometry_package);
		
		var arr_coordinates = arr_geometry.coordinates;

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
				
				obj.coordinatesToPackingLinePackage(arr_geometry_package, arr_coordinates);
				
				break;
			case 'Polygon':
			
				arr_geometry_package.push('Polygon');
				
				obj.coordinatesToPackingPolygon(arr_geometry_package, arr_coordinates, 1);
				
				break;
			case 'MultiLineString':
			
				arr_geometry_package.push('MultiLineString');
				
				obj.coordinatesToPackingLine(arr_geometry_package, arr_coordinates, 1);
				
				break;
			case "MultiPolygon":
			
				arr_geometry_package.push('MultiPolygon');
				
				obj.coordinatesToPackingPolygon(arr_geometry_package, arr_coordinates, 2);
				
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

	this.coordinatesToPackage = function(arr_geometry_package, arr_coordinates) {
		
		arr_coordinates[0] = obj_map.parseLongitude(arr_coordinates[0]);
		arr_coordinates[1] = obj_map.parseLatitude(arr_coordinates[1]);
		
		arr_geometry_package.push(arr_coordinates);
	};

	this.coordinatesToPackingLine = function(arr_geometry_package, arr_coordinates, nr_level_deep) {
		
		if (nr_level_deep > 1) {
				
			for (let i = 0, len = arr_coordinates.length; i < len; i++) {

				var arr_level = [];
				arr_geometry_package.push(arr_level);
				
				obj.coordinatesToPackingLine(arr_level, arr_coordinates[i], nr_level_deep - 1);
			}
		} else {
			
			for (let i = 0, len = arr_coordinates.length; i < len; i++) {

				var arr_level = [];
				arr_geometry_package.push(arr_level);
				
				obj.coordinatesToPackingLinePackage(arr_level, arr_coordinates[i]);
			}
		}
	};
	
	this.coordinatesToPackingLinePackage = function(arr_geometry_package, arr_coordinates) {
			
		var prev_longitude = obj_map.parseLongitude(arr_coordinates[0][0]);
		var prev_latitude = obj_map.parseLatitude(arr_coordinates[0][1]);
		var prev_abs_longitude = Math.abs(prev_longitude);
		
		for (let i = 0, len = arr_coordinates.length; i < len; i++) {
			
			const longitude = obj_map.parseLongitude(arr_coordinates[i][0]);
			const latitude = obj_map.parseLatitude(arr_coordinates[i][1]);
			const abs_longitude = Math.abs(longitude) 
			
			if (Math.sign(longitude) !== Math.sign(prev_longitude) && abs_longitude + prev_abs_longitude > 180 && abs_longitude !== prev_abs_longitude) {
			
				if (prev_longitude < 0) {
					arr_geometry_package.push([(-180 - (180 - longitude)), latitude], null, [(180 + (180 + prev_longitude)), prev_latitude]); // Add new point to previous segment, map border separator, add previous point to new segment
				} else {
					arr_geometry_package.push([(180 + (180 + longitude)), latitude], null, [(-180 - (180 - prev_longitude)), prev_latitude]); // Add new point to previous segment, map border separator, add previous point to new segment
				}
			}

			arr_geometry_package.push([longitude, latitude]);
			
			prev_longitude = longitude;
			prev_latitude = latitude;
			prev_abs_longitude = abs_longitude;
		}
	}
	
	this.coordinatesToPackingPolygon = function(arr_geometry_package, arr_coordinates, nr_level_deep) {
		
		var len = arr_coordinates.length;
		
		if (nr_level_deep > 1) {
			
			for (let i = 0; i < len; i++) {

				var arr_level = [];
				arr_geometry_package.push(arr_level);
			
				obj.coordinatesToPackingPolygon(arr_level, arr_coordinates[i], nr_level_deep - 1);
			}
		} else {
			
			var arr_level_first = false;
			
			for (let i = 0; i < len; i++) {

				var arr_level = [];
				
				if (len > 1 && i == 0) {
					arr_level_reference = arr_level;
				}
			
				obj.coordinatesToPackingPolygonPackage(arr_level, arr_coordinates[i]);
				
				if (i > 0 && arr_level_reference.length > 1) {
					
					const xy_check = arr_level[0][1]; // Check the second coordinate, less intersection trouble
					
					if (pointIsInside(xy_check, arr_level_reference[1])) {
						
						if (arr_level.length > 1) { // Switch
							arr_level = [arr_level[1], arr_level[0]];
						} else { // Move
							arr_level = [null, arr_level[0]];
						}
					}
				}
				
				arr_geometry_package.push(arr_level);
			}
		}
	};

	this.coordinatesToPackingPolygonPackage = function(arr_geometry_package, arr_coordinates) {
			
		arr_geometry_package[0] = [];
		
		const arr_position = obj_map.getPosition();
		const abs_longitude_origin = Math.abs(arr_position.origin.longitude);

		var prev_longitude = obj_map.parseLongitude(arr_coordinates[0][0]);
		var prev_latitude = obj_map.parseLatitude(arr_coordinates[0][1]);
		var prev_abs_longitude = Math.abs(prev_longitude);
		var in_halve = false;
		
		for (let i = 0, len = arr_coordinates.length; i < len; i++) {
								
			const longitude = obj_map.parseLongitude(arr_coordinates[i][0]);
			const latitude = obj_map.parseLatitude(arr_coordinates[i][1]);
			const abs_longitude = Math.abs(longitude) 
			
			var is_edge = false;
			
			if (Math.sign(longitude) !== Math.sign(prev_longitude) && (abs_longitude + prev_abs_longitude) > 180 && abs_longitude !== prev_abs_longitude) {
				
				if (arr_geometry_package[1] === undefined) {
					arr_geometry_package[1] = [];
				}

				if (prev_longitude < 0) {
					arr_geometry_package[(in_halve ? 1 : 0)].push([(-180 - (180 - longitude)), latitude, false]); // Add new point to previous segment
					arr_geometry_package[(in_halve ? 0 : 1)].push([(180 + (180 + prev_longitude)), prev_latitude, false]); // Add previous point to new segment
				} else {
					arr_geometry_package[(in_halve ? 1 : 0)].push([(180 + (180 + longitude)), latitude, false]); // Add new point to previous segment
					arr_geometry_package[(in_halve ? 0 : 1)].push([(-180 - (180 - prev_longitude)), prev_latitude, false]); // Add previous point to new segment
				}
				
				in_halve = (in_halve ? false : true);
			} else if (abs_longitude_origin !== 0 && (abs_longitude + abs_longitude_origin) == 180 && (prev_abs_longitude + abs_longitude_origin) == 180) { // If longitude origin has an offset, check if polygon is on the -180/180 edge
				
				is_edge = true;
			}
			
			arr_geometry_package[(in_halve ? 1 : 0)].push([longitude, latitude, is_edge]);
			
			prev_longitude = longitude;
			prev_latitude = latitude;
			prev_abs_longitude = abs_longitude;
		}
	};
	
	this.geometryPackageToPath = function(arr_geometry_package) {

		var len = arr_geometry_package.length;
		
		if (len == 2 && arr_geometry_package[0] == 'Point') {
			
			return arr_geometry_package[1];
		} else {
			
			var is_path = true; // True when geometry does not have polygons
			var has_point = false; // True if last geometry is a point

			const len_i_geo = arr_geometry_package.length;
			
			for (let i_geo = 0; i_geo < len_i_geo; i_geo++) {
				
				const cur_value = arr_geometry_package[i_geo];
				
				if (cur_value === 'Polygon' || cur_value === 'MultiPolygon') {
					
					is_path = false;
					break;
				}				
			}
			
			var arr = [];
			var i_geo = 0;
			
			while (i_geo < len_i_geo) {
				
				const cur_value = arr_geometry_package[i_geo];
				
				if (typeof cur_value === 'string') {
					
					switch (cur_value) {
						case 'Point':
							
							has_point = true;
							
						case 'MultiPoint':
						case 'LineString':
						
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object' || arr_geometry_package[i_geo] === null) {
								
								arr.push(arr_geometry_package[i_geo]);
								
								i_geo++;
							}
							
							break;
						case 'MultiLineString':
							
							i_geo++;
							
							while (typeof arr_geometry_package[i_geo] === 'object') {
									
								var arr_level_points = arr_geometry_package[i_geo];
								
								for (var i = 0, len = arr_level_points.length; i < len; i++) {									
									
									arr.push(arr_level_points[i]);
								}						
								
								i_geo++;
							}
							
							break;
						case 'Polygon':
						
							i_geo++;
							
							// Only need the first set for boundary calculation in case of a polygon with holes
							
							var arr_level_points = arr_geometry_package[i_geo][0];
							
							arr.push(obj.pointsToCenter(arr_level_points));
							
							i_geo++;
							
							break;
						case 'MultiPolygon':
						
							i_geo++;
						
							while (typeof arr_geometry_package[i_geo] === 'object') {
							
								// Only need the first set for boundary calculation in case of a polygon with holes
								 
								var arr_level_points = arr_geometry_package[i_geo][0][0];
								
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
			var longitude = 0;
			var latitude = 0;
			
			if (is_path || has_point) {
				
				// Use last point as main/center point
				longitude = arr[len-1][0];
				latitude = arr[len-1][1];
				
				if (len == 1) {
					arr = null;
				}
			} else {
					
				for (var i = 0; i < len; i++) {
					
					if (arr[i] === null) { // A map border separator
						continue;
					}
						
					longitude += arr[i][0];
					latitude += arr[i][1];
				}
				
				longitude = longitude / len;
				latitude = latitude / len;
				arr = null;
			}

			return [longitude, latitude, arr];
		}
	}
	
	this.pointsToCenter = function(arr) {
		
		var len = arr.length;
		
		if (len == 1) {
			
			return arr[0];
		} else if (len == 2) {
			
			return [(arr[0][0] + arr[1][0]) / 2, (arr[0][1] + arr[1][1]) / 2];
		} else {
				
			var x = function(i) { return arr[i % len][0] }; // Longitude
			var y = function(i) { return arr[i % len][1] }; // Latitude
			
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
	
	this.calcPointOffset = function(x1, y1, x2, y2, offset, perc) {
	
		//Place p3 in between (percentage) p1 and p2
		var p3 = {x: x1+(x2 - x1)*perc, y: y1+(y2 - y1)*perc};
		
		var vec = {x: x2-x1, y: y2-y1}; // vector
		var l = Math.sqrt(vec.x*vec.x+vec.y*vec.y); // vector length, Pythagoras
		var a = {x: vec.y, y: -vec.x}; // shift angle 90 deg
		
		if (l) {
			p3.x += (a.x/l) * offset;
			p3.y += (a.y/l) * offset;
		}
		
		return p3;
	};

	this.isNearLine = function(x1, y1, x2, y2, xp, yp, offset) {
		
		if (xp <= (Math.min(x1, x2) - offset) || xp >= (Math.max(x1, x2) + offset) || yp <= (Math.min(y1, y2) - offset) || yp >= (Math.max(y1, y2) + offset)) { // Check bounding box
			return false;
		}
		
		var xd1 = x1 + (x2 - x1);
		var yd1 = y1 + (y2 - y1);
		var xd2 = x2 + (x1 - x2);
		var yd2 = y2 + (y1 - y2);
		
		var xx = xd2 - xd1;
		var yy = yd2 - yd1; 
		var length_short = ((xx * (xp - xd1)) + (yy * (yp - yd1))) / ((xx * xx) + (yy * yy));
		var x_offset = xd1 + xx * length_short; 
		var y_offset = yd1 + yy * length_short;
		
		return (Math.abs(x_offset-xp) < offset && Math.abs(y_offset-yp) < offset);
	};
	
	this.isNearLineRounded = function(x1, y1, x2, y2, xp, yp, offset) {
		
		var vec = (((x2 - x1) * (x2 - x1)) + ((y2 - y1) * (y2 - y1)));
		
		if (vec == 0) {
			return false;
		}
		
		var r = (((xp - x1) * (x2 - x1)) + ((yp - y1) * (y2 - y1))) / vec;

		// Assume line thickness is circular
		if (r < 0) { // Outside line1
			
			return (Math.sqrt(((x1 - xp) * (x1 - xp)) + ((y1 - yp) * (y1 - yp))) <= offset);
		} else if ((0 <= r) && (r <= 1)) { // On the line segment
			
			var s = (((y1 - yp) * (x2 - x1)) - ((x1 - xp) * (y2 - y1))) / vec;
			
			return (Math.abs(s) * Math.sqrt(vec) <= offset);
		} else { // Outside line2
			
			return (Math.sqrt(((x2 - xp) * (x2 - xp)) + ((y2 - yp) * (y2 - yp) )) <= offset);
		}
	};

	this.colorToBrightColor = function(str, percent) {

		var arr_color = arr_assets_colors_parsed['b_'+str];
		
		if (arr_color == null) {
			
			var arr_color = parseCSSColor(str);
			
			arr_color.r = Math.floor(arr_color.r + (256 - arr_color.r) * percent / 100);
			arr_color.g = Math.floor(arr_color.g + (256 - arr_color.g) * percent / 100);
			arr_color.b = Math.floor(arr_color.b + (256 - arr_color.b) * percent / 100);
			
			arr_assets_colors_parsed['b_'+str] = arr_color;
		}
			
		return arr_color;
	};
	
	this.parseColor = function(str) {
		
		var hex = arr_assets_colors_parsed['h_'+str];
		
		if (hex == null) {
			
			var hex = parseCSSColorToHex(str);
			arr_assets_colors_parsed['h_'+str] = hex;
		}
		
		return hex;
	};
	
	var pointIsInside = function(xy, arr) {
		
		// ray-casting algorithm based on
		// http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html

		var x = xy[0], y = xy[1];

		var inside = false;
		
		for (var i = 0, len = arr.length, j = len - 1; i < len; j = i++) {
			
			var xi = arr[i][0], yi = arr[i][1];
			var xj = arr[j][0], yj = arr[j][1];

			var intersect = ((yi > y) != (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
			if (intersect) {
				inside = !inside;
			}
		}

		return inside;
	};
	
	// Cohen-Sutherland line clippign algorithm, adapted to efficiently
	// handle polylines rather than just segments

	this.lineclip = function(arr, bbox, result) {

		var len = arr.length,
			codeA = bitCode(arr[0], bbox),
			part = [],
			i, a, b, codeB, lastCode;

		if (!result) {
			var result = [];
		}

		for (i = 1; i < len; i++) {
			
			a = arr[i - 1];
			b = arr[i];
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

		if (part.length) {
			result.push(part);
		}

		return result;
	};

	// Sutherland-Hodgeman polygon clipping algorithm

	this.clipPolygon = function(arr, bbox) {

		var result, edge, prev, prevInside, i, p, inside;

		// clip against each side of the clip rectangle
		for (edge = 1; edge <= 8; edge *= 2) {
			result = [];
			prev = arr[arr.length - 1];
			prevInside = !(bitCode(prev, bbox) & edge);

			for (i = 0; i < arr.length; i++) {
				p = arr[i];
				inside = !(bitCode(p, bbox) & edge);

				// if segment goes through the clip window, add an intersection
				if (inside !== prevInside) result.push(intersect(prev, p, edge, bbox));

				if (inside) result.push(p); // add a point if it's inside

				prev = p;
				prevInside = inside;
			}

			var arr = result;

			if (!arr.length) break;
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
