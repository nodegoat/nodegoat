
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapGeoUtilities(PARENT) {

	var SELF = new MapUtilities(PARENT);
	
	SELF.parseColor = SELF.parseColorToHex;
	
	SELF.geometryToPackage = function(arr_geometry, arr_geometry_package) {
		
		var arr_geometry = (arr_geometry.type === 'Feature' ? arr_geometry.geometry : arr_geometry);
		var arr_geometry_package = (arr_geometry_package === undefined ? [] : arr_geometry_package);
		
		var arr_coordinates = arr_geometry.coordinates;

		switch (arr_geometry.type) {
			case 'Point':
			
				arr_geometry_package.push('Point');
				
				SELF.coordinatesToPackage(arr_geometry_package, arr_coordinates);
				
				break;
			case 'MultiPoint':
				
				arr_geometry_package.push('MultiPoint');
				
				for (var i = 0, len = arr_coordinates.length; i < len; i++) {
									
					SELF.coordinatesToPackage(arr_geometry_package, arr_coordinates[i]);
				}
				
				break;
			case 'LineString':
			
				arr_geometry_package.push('LineString');
				
				SELF.coordinatesToPackingLinePackage(arr_geometry_package, arr_coordinates);
				
				break;
			case 'Polygon':
			
				arr_geometry_package.push('Polygon');
				
				SELF.coordinatesToPackingPolygon(arr_geometry_package, arr_coordinates, 1);
				
				break;
			case 'MultiLineString':
			
				arr_geometry_package.push('MultiLineString');
				
				SELF.coordinatesToPackingLine(arr_geometry_package, arr_coordinates, 1);
				
				break;
			case "MultiPolygon":
			
				arr_geometry_package.push('MultiPolygon');
				
				SELF.coordinatesToPackingPolygon(arr_geometry_package, arr_coordinates, 2);
				
				break;
			case "GeometryCollection":
			
				arr_geometry_package.push('GeometryCollection');
			
				for (var i = 0, len = arr_geometry.geometries.length; i < len; i++) {

					SELF.geometryToPackage(arr_geometry.geometries[i], arr_geometry_package);
				}
				
				break;
			default:
				console.log('Invalid GeoJSON object.');
		}
		
		return arr_geometry_package;
	};

	SELF.coordinatesToPackage = function(arr_geometry_package, arr_coordinates) {
		
		arr_coordinates[0] = PARENT.parseLongitude(arr_coordinates[0]);
		arr_coordinates[1] = PARENT.parseLatitude(arr_coordinates[1]);
		
		arr_geometry_package.push(arr_coordinates);
	};

	SELF.coordinatesToPackingLine = function(arr_geometry_package, arr_coordinates, nr_level_deep) {
		
		if (nr_level_deep > 1) {
				
			for (let i = 0, len = arr_coordinates.length; i < len; i++) {

				var arr_level = [];
				arr_geometry_package.push(arr_level);
				
				SELF.coordinatesToPackingLine(arr_level, arr_coordinates[i], nr_level_deep - 1);
			}
		} else {
			
			for (let i = 0, len = arr_coordinates.length; i < len; i++) {

				var arr_level = [];
				arr_geometry_package.push(arr_level);
				
				SELF.coordinatesToPackingLinePackage(arr_level, arr_coordinates[i]);
			}
		}
	};
	
	SELF.coordinatesToPackingLinePackage = function(arr_geometry_package, arr_coordinates) {
			
		var prev_longitude = PARENT.parseLongitude(arr_coordinates[0][0]);
		var prev_latitude = PARENT.parseLatitude(arr_coordinates[0][1]);
		var prev_abs_longitude = Math.abs(prev_longitude);
		
		for (let i = 0, len = arr_coordinates.length; i < len; i++) {
			
			const longitude = PARENT.parseLongitude(arr_coordinates[i][0]);
			const latitude = PARENT.parseLatitude(arr_coordinates[i][1]);
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
	
	SELF.coordinatesToPackingPolygon = function(arr_geometry_package, arr_coordinates, nr_level_deep) {
		
		var len = arr_coordinates.length;
		
		if (nr_level_deep > 1) {
			
			for (let i = 0; i < len; i++) {

				var arr_level = [];
				arr_geometry_package.push(arr_level);
			
				SELF.coordinatesToPackingPolygon(arr_level, arr_coordinates[i], nr_level_deep - 1);
			}
		} else {
			
			var arr_level_first = false;
			
			for (let i = 0; i < len; i++) {

				var arr_level = [];
				
				if (len > 1 && i == 0) {
					arr_level_reference = arr_level;
				}
			
				SELF.coordinatesToPackingPolygonPackage(arr_level, arr_coordinates[i]);
				
				if (i > 0 && arr_level_reference.length > 1) {
					
					const xy_check = arr_level[0][1]; // Check the second coordinate, less intersection trouble
					
					if (SELF.pointIsInside(xy_check, arr_level_reference[1])) {
						
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

	SELF.coordinatesToPackingPolygonPackage = function(arr_geometry_package, arr_coordinates) {
			
		arr_geometry_package[0] = [];
		
		const arr_position = PARENT.getPosition();
		const abs_longitude_origin = Math.abs(arr_position.origin.longitude);

		var prev_longitude = PARENT.parseLongitude(arr_coordinates[0][0]);
		var prev_latitude = PARENT.parseLatitude(arr_coordinates[0][1]);
		var prev_abs_longitude = Math.abs(prev_longitude);
		var in_halve = false;
		
		for (let i = 0, len = arr_coordinates.length; i < len; i++) {
								
			const longitude = PARENT.parseLongitude(arr_coordinates[i][0]);
			const latitude = PARENT.parseLatitude(arr_coordinates[i][1]);
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
	
	SELF.geometryPackageToPath = function(arr_geometry_package) {

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
							
							arr.push(SELF.pointsToCenter(arr_level_points));
							
							i_geo++;
							
							break;
						case 'MultiPolygon':
						
							i_geo++;
						
							while (typeof arr_geometry_package[i_geo] === 'object') {
							
								// Only need the first set for boundary calculation in case of a polygon with holes
								 
								var arr_level_points = arr_geometry_package[i_geo][0][0];
								
								arr.push(SELF.pointsToCenter(arr_level_points));
								
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
	
	return SELF;
};
