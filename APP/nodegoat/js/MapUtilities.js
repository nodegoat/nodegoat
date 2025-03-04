
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapUtilities(PARENT) {

	var SELF = this;
	
	var arr_assets_colors_parsed = {};
			
	this.pointsToCenter = function(arr) {
		
		const len = arr.length;
		
		if (len == 1) {
			
			return arr[0];
		} else if (len == 2) {
			
			return [(arr[0][0] + arr[1][0]) / 2, (arr[0][1] + arr[1][1]) / 2];
		} else {
				
			let x = function(i) { return arr[i % len][0] }; // Longitude
			let y = function(i) { return arr[i % len][1] }; // Latitude
			
			let twoTimesSignedArea = 0;
			let cxTimes6SignedArea = 0;
			let cyTimes6SignedArea = 0;

			for (let i = 0; i < len; i++) {
				
				const twoSA = x(i)*y(i+1) - x(i+1)*y(i);
				
				twoTimesSignedArea += twoSA;
				cxTimes6SignedArea += (x(i) + x(i+1)) * twoSA;
				cyTimes6SignedArea += (y(i) + y(i+1)) * twoSA;
			}
			
			if (!twoTimesSignedArea) { // Something is not right
				
				x = 0;
				y = 0;
				
				for (let i = 0; i < len; i++) {
					
					x += arr[i][0];
					y += arr[i][1];
				}
				
				return [x / len, y / len];
			}
			
			const sixSignedArea = 3 * twoTimesSignedArea;
			x = cxTimes6SignedArea / sixSignedArea;
			y = cyTimes6SignedArea / sixSignedArea;
		
			return [x, y]; // Longitude, latitude
		}
	};
	
	this.calcPointOffset = function(x1, y1, x2, y2, num_offset, num_percentage) {
	
		// Place p3 in between (percentage) p1 and p2
		const p3 = {x: x1 + (x2 - x1) * num_percentage, y: y1 + (y2 - y1) * num_percentage};
		
		const num_vec_x = x2-x1; // Vector
		const num_vec_y = y2-y1;
		const num_l = Math.sqrt(num_vec_x*num_vec_x+num_vec_y*num_vec_y); // Vector length, Pythagoras

		if (num_l) {
			
			const num_a_x = num_vec_y; // Shift angle 90 deg
			const num_a_y = -num_vec_x;
		
			p3.x += (num_a_x/num_l) * num_offset;
			p3.y += (num_a_y/num_l) * num_offset;
		}
		
		return p3;
	};

	this.isNearLine = function(x1, y1, x2, y2, xp, yp, offset) {
		
		if (xp <= (Math.min(x1, x2) - offset) || xp >= (Math.max(x1, x2) + offset) || yp <= (Math.min(y1, y2) - offset) || yp >= (Math.max(y1, y2) + offset)) { // Check bounding box
			return false;
		}
		
		const xd1 = x1 + (x2 - x1);
		const yd1 = y1 + (y2 - y1);
		const xd2 = x2 + (x1 - x2);
		const yd2 = y2 + (y1 - y2);
		
		const xx = xd2 - xd1;
		const yy = yd2 - yd1; 
		const length_short = ((xx * (xp - xd1)) + (yy * (yp - yd1))) / ((xx * xx) + (yy * yy));
		const x_offset = xd1 + xx * length_short; 
		const y_offset = yd1 + yy * length_short;
		
		return (Math.abs(x_offset-xp) < offset && Math.abs(y_offset-yp) < offset);
	};
	
	this.isNearLineRounded = function(x1, y1, x2, y2, xp, yp, offset) {
		
		const vec = (((x2 - x1) * (x2 - x1)) + ((y2 - y1) * (y2 - y1)));
		
		if (vec == 0) {
			return false;
		}
		
		const r = (((xp - x1) * (x2 - x1)) + ((yp - y1) * (y2 - y1))) / vec;

		// Assume line thickness is circular
		if (r < 0) { // Outside line1
			
			return (Math.sqrt(((x1 - xp) * (x1 - xp)) + ((y1 - yp) * (y1 - yp))) <= offset);
		} else if ((0 <= r) && (r <= 1)) { // On the line segment
			
			const s = (((y1 - yp) * (x2 - x1)) - ((x1 - xp) * (y2 - y1))) / vec;
			
			return (Math.abs(s) * Math.sqrt(vec) <= offset);
		} else { // Outside line2
			
			return (Math.sqrt(((x2 - xp) * (x2 - xp)) + ((y2 - yp) * (y2 - yp) )) <= offset);
		}
	};
	
	this.parseColor = function(str) {
		
		let arr_color = arr_assets_colors_parsed['a_'+str];
		
		if (arr_color === undefined) {
			
			arr_color = parseCSSColor(str);
			arr_assets_colors_parsed['a_'+str] = arr_color;
		}
		
		return arr_color;
	};
	
	this.parseColorToHex = function(str) {
		
		let hex = arr_assets_colors_parsed['h_'+str];
		
		if (hex === undefined) {
			
			hex = parseCSSColorToHex(str);
			arr_assets_colors_parsed['h_'+str] = hex;
		}
		
		return hex;
	};
		
	this.colorToBrightColor = function(str, num_percent) {
		
		const str_identifier = 'b_'+str+'_'+num_percent;
		let arr_color = arr_assets_colors_parsed[str_identifier];
		
		if (arr_color === undefined) {
			
			arr_color = parseCSSColor(str);
			
			arr_color.r = Math.floor(arr_color.r + (256 - arr_color.r) * num_percent / 100);
			arr_color.g = Math.floor(arr_color.g + (256 - arr_color.g) * num_percent / 100);
			arr_color.b = Math.floor(arr_color.b + (256 - arr_color.b) * num_percent / 100);
			
			arr_assets_colors_parsed[str_identifier] = arr_color;
		}
			
		return arr_color;
	};
	
	this.pointIsInside = function(xy, arr) {
		
		// ray-casting algorithm based on
		// http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html

		const x = xy[0], y = xy[1];

		let inside = false;
		
		for (let i = 0, len = arr.length, j = len - 1; i < len; j = i++) {
			
			const xi = arr[i][0], yi = arr[i][1];
			const xj = arr[j][0], yj = arr[j][1];

			const intersect = ((yi > y) != (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
			
			if (intersect) {
				inside = !inside;
			}
		}

		return inside;
	};
	
	// Cohen-Sutherland line clippign algorithm, adapted to efficiently
	// handle polylines rather than just segments

	this.clipLine = function(arr, bbox, result) {

		let len = arr.length,
			codeA = SELF.bitCode(arr[0], bbox),
			part = [],
			i, a, b, codeB, lastCode;

		if (!result) {
			var result = [];
		}

		for (i = 1; i < len; i++) {
			
			a = arr[i - 1];
			b = arr[i];
			codeB = lastCode = SELF.bitCode(b, bbox);

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
					a = SELF.intersect(a, b, codeA, bbox);
					codeA = SELF.bitCode(a, bbox);

				} else { // b outside
					b = SELF.intersect(a, b, codeB, bbox);
					codeB = SELF.bitCode(b, bbox);
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

		let result, edge, prev, prevInside, i, p, inside;

		// clip against each side of the clip rectangle
		for (edge = 1; edge <= 8; edge *= 2) {
			
			result = [];
			prev = arr[arr.length - 1];
			prevInside = !(SELF.bitCode(prev, bbox) & edge);

			for (i = 0; i < arr.length; i++) {
				p = arr[i];
				inside = !(SELF.bitCode(p, bbox) & edge);

				// if segment goes through the clip window, add an intersection
				if (inside !== prevInside) result.push(SELF.intersect(prev, p, edge, bbox));

				if (inside) result.push(p); // add a point if it's inside

				prev = p;
				prevInside = inside;
			}

			var arr = result;

			if (!arr.length) {
				break;
			}
		}

		return result;
	};

	// intersect a segment against one of the 4 lines that make up the bbox

	this.intersect = function(a, b, edge, bbox) {
		
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

	this.bitCode = function(p, bbox) {
		
		let code = 0;

		if (p[0] < bbox[0]) code |= 1; // left
		else if (p[0] > bbox[2]) code |= 2; // right

		if (p[1] < bbox[1]) code |= 4; // bottom
		else if (p[1] > bbox[3]) code |= 8; // top

		return code;
	};
};
