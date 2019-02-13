
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapSocial(element, obj_parent, options) {

	var elm = $(element),
	obj = this,
	obj_map = false,
	
	stage_ns = 'http://www.w3.org/2000/svg',
	elm_svg = false,
	elm_canvas = false,
	svg = false,
	svg_group = false,
	display = 'vector',
	elm_selected_node_container = false,
	elm_search_container = false,
	elm_analyse_container = false,
	elm_plot_dots = false,
	arr_data = false,
	arr_object_subs_loop = [],
	include_location_nodes = false,
	show_disconnected_node = false,
	width = elm.width(),
	height = elm.height(),
	setdata = false,
	pre_graph_init = true,
	max_complete_connections = false,
	max_current_connections = 1,
	max_value = false,
	nodes = [],
    links = [],
	force_options = {},
	key_move = false,
	position = {x: 0, y: 0},
	simulation = false,
	node_drag = false,
	dragging = false,
	hovering = false,
	cur_node_position = false,
	arr_highlighted_nodes = [],
	arr_remove_nodes = [],
	size_renderer = {},
	renderer = false,
	renderer_2 = false,
	stage = false,
	stage_2 = false,
	drawer = false,
	first_run = true,
	scale = 1,
	init_size = {width: 0, height: 0},
	static_layout = false,
	static_layout_interval = 0,
	static_layout_timer = window.performance.now(),

	pos_hover_poll = {x: 0, y: 0},
	pos_translation = {x: 0, y: 0},
	
	metrics = false,
	metrics_process = false,
	
	arr_assets_colors_parsed = {},
	
	ua = window.navigator.userAgent,
	msie = ua.indexOf("MSIE "),
	
	shader = false,
	gl = false,
	glCore = false,
	vertices = [],
	colors = [],
	position_vertexbuffer = false,
	link_vertex_position = 0,
	linkcount = 0,
	
	draw = false,
	label_threshold = 0.1,
	weight_is_size = false,

	key_animate = false,
	
	arr_elm_particles = [],
	arr_assets_texture_line_dots = [],
	size_max_elm_container = 15000;
	
	var device_is_touch = (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0));

	var parseColor = function(str) {
		
		if (display == 'vector') {
			return str;
		}
		
		var hex = arr_assets_colors_parsed['h_'+str];
		
		if (!hex) {
			
			var hex = parseCssColorToHex(str);
			arr_assets_colors_parsed['h_'+str] = hex;
		}
		
		return hex;
	};
    			
	this.init = function() {

		ASSETS.fetch({script: [
			'/js/d3.min.js',
			'/CMS/js/pixi.min.js'
		], font: [
			'pixel'
		]}, function() {
			
			obj_map = obj_parent.obj_map;
			
			display = (options.arr_visual.social.settings.display == 2 ? 'pixel' : 'vector');
			static_layout = options.arr_visual.social.settings.static_layout;
			static_layout_interval = options.arr_visual.social.settings.static_layout_interval;
			
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
			
			force_options.node_max_size = options.arr_visual.social.dot.size.max;
			force_options.node_min_size = options.arr_visual.social.dot.size.min;
			force_options.node_color = options.arr_visual.social.dot.color;
			force_options.node_stroke_color = options.arr_visual.social.dot.stroke_color;
			force_options.node_stroke_width = options.arr_visual.social.dot.stroke_width;
			force_options.show_line = options.arr_visual.social.line.show;
			force_options.link_arrowhead = options.arr_visual.social.line.arrowhead_show;
			force_options.friction = 0.9;
			force_options.charge = -100;
			force_options.gravity = 0.08;
			force_options.theta = 0.8;
			
			if (typeof options.arr_visual.social.settings.social_advanced.force_friction != 'undefined') {
				force_options.friction = parseBool(options.arr_visual.social.settings.social_advanced.force_friction, true);
			}
			if (typeof options.arr_visual.social.settings.social_advanced.force_charge != 'undefined') {
				force_options.charge = parseBool(options.arr_visual.social.settings.social_advanced.force_charge, true);
			}
			if (typeof options.arr_visual.social.settings.social_advanced.force_gravity != 'undefined') {
				force_options.gravity = parseBool(options.arr_visual.social.settings.social_advanced.force_gravity, true);
			}
			if (typeof options.arr_visual.social.settings.social_advanced.force_theta != 'undefined') {
				force_options.theta = parseBool(options.arr_visual.social.settings.social_advanced.force_theta, true);
			}
			if (typeof options.arr_visual.social.settings.social_advanced.label_threshold != 'undefined') {
				label_threshold = parseBool(options.arr_visual.social.settings.social_advanced.label_threshold, true);
			}
			if (typeof options.arr_visual.social.settings.social_advanced.weight_is_size != 'undefined') {
				weight_is_size = parseBool(options.arr_visual.social.settings.social_advanced.weight_is_size, true);
			}
			if (typeof options.arr_visual.social.settings.social_advanced.metrics != 'undefined') {
				metrics = parseBool(options.arr_visual.social.settings.social_advanced.metrics, false);
			}

			if (options.arr_visual.social.settings.disconnected_dot_show) {
				show_disconnected_node = true;
			}
			
			if (options.arr_visual.social.settings.include_location_references) {
				include_location_nodes = true;
			}
			
			var pos_map = obj_map.getPosition();
			init_size = {width: pos_map.size.width, height: pos_map.size.height};
						
			if (display == 'pixel') {
				
				elm_canvas = document.createElement('canvas');
				elm_canvas.width = width;
				elm_canvas.height = height;	
				elm[0].appendChild(elm_canvas);
				
				PIXI.Graphics.CURVES.adaptive = true;
				
				elm_plot_dots = new PIXI.Container();
							
				glCore = PIXI.glCore;
				
				var contextOptions = {
					antialias:true,
					transparent: true
				};
				
				gl = glCore.createContext(elm_canvas, contextOptions);
				
				var vertex_shader = 
					'precision mediump float;' +
					'attribute vec2 a_position;' +
					'attribute vec4 a_color;' +
					'varying vec4 v_color;' +
					'uniform vec2 bounds;' +
					'uniform vec2 translation;' +
					'uniform vec2 stagetranslation;' +
					'uniform vec2 scale;' +
					'void main(void) {' +
					' 	vec2 pos = ((((a_position.xy + translation.xy) * scale.xy) + stagetranslation.xy) / bounds.xy) * 2.0 - 1.0;' +
					' 	gl_Position = vec4(pos * vec2(1, -1), 0, 1.0);' +
					' 	v_color = a_color;' +
					'}';
					
				var fragment_shader =
					'precision mediump float;' +
					'varying vec4 v_color;' +
					'void main(void) {' +
					'	gl_FragColor = v_color;' +
					'}';
				
				
				shader = new glCore.GLShader(gl, vertex_shader, fragment_shader);
				shader.bind();
				
				shader.uniforms.bounds = [width, height];
				shader.uniforms.translation = [0, 0];
				shader.uniforms.fulltranslation = [0, 0];
				shader.uniforms.scale = [1.0, 1.0];

				size_renderer = {width: pos_map.view.width, height: pos_map.view.height};
				renderer = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});
				renderer_2 = PIXI.autoDetectRenderer(size_renderer.width, size_renderer.height, {transparent: true, antialias: true});

				stage = new PIXI.Container();
				elm_plot_particles = new PIXI.Container();
				//stage.addChild(elm_plot_particles);
				stage.addChild(elm_plot_dots);
				
				stage_2 = new PIXI.Container();
				
				elm[0].appendChild(renderer.view);
				elm[0].appendChild(renderer_2.view);
				
			} else {


				size_renderer = {width: pos_map.size.width, height: pos_map.size.height};
				
				renderer = document.createElementNS(stage_ns, 'svg');
				renderer.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
				
				drawer = renderer;
				drawer.style.width = size_renderer.width+'px';
				drawer.style.height = size_renderer.height+'px';
				
				elm[0].appendChild(drawer);
				
				stage = renderer.ownerDocument;		
				svg_group = stage.createElementNS(stage_ns, 'g');		
				drawer.appendChild(svg_group);				
				
				
				if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
					// no markers
				} else { 				
					if (force_options.link_arrowhead) {
						
						var defs = stage.createElementNS(stage_ns, 'defs');
						var marker = stage.createElementNS(stage_ns, 'marker');
						var marker_path = stage.createElementNS(stage_ns, 'path');
						var marker_selected = stage.createElementNS(stage_ns, 'marker');
						var marker_selected_path = stage.createElementNS(stage_ns, 'path');
						
						svg_group.appendChild(defs);
						defs.appendChild(marker);
						defs.appendChild(marker_selected);
						
						marker.setAttribute('id', 'end');
						marker.setAttribute('class', 'marker-end');
						marker.setAttribute('viewBox', '0 -5 10 10');
						marker.setAttribute('refX', 15);
						marker.setAttribute('refY', -1.5);
						marker.setAttribute('fill', force_options.node_stroke_color);
						marker.setAttribute('markerWidth', 6);
						marker.setAttribute('markerHeight', 6);
						marker.setAttribute('orient', 'auto');
						
						marker_selected.setAttribute('id', 'end-selected');
						marker_selected.setAttribute('class', 'marker-end');
						marker_selected.setAttribute('viewBox', '0 -5 10 10');
						marker_selected.setAttribute('refX', 15);
						marker_selected.setAttribute('refY', -1.5);
						marker_selected.setAttribute('fill', '#ff9999');
						marker_selected.setAttribute('markerWidth', 6);
						marker_selected.setAttribute('markerHeight', 6);
						marker_selected.setAttribute('orient', 'auto');
						
						marker_path.setAttribute('d', 'M0,-5L10,0L0,5');
						marker_selected_path.setAttribute('d', 'M0,-5L10,0L0,5');
						
						marker.appendChild(marker_path);
						marker_selected.appendChild(marker_selected_path);
						
					}
				}
			}
			
			addListeners();
			
			obj.drawData = drawData;
			
			key_move = obj_map.move(rePosition);
			
			obj_parent.doDraw();
		});
	};
	
	this.close = function() {
		
		if (!obj_map) { // Nothing loaded yet
			return;
		}

		elm_selected_node_container.remove();
		elm_search_container.remove();
		
		ANIMATOR.animate(null, key_animate);

		simulation.stop();
		
		if (display == 'pixel') { // Destroy WEBGL memory
			stage.destroy(true);
			stage_2.destroy(true);
			renderer.destroy();
			renderer_2.destroy();
		}
	};
	
	var drawData = function(date_range) {
		
		if (!arr_data) {
			arr_data = obj_parent.getData();
		}

		var drawn = false;

		if (!setdata) {

			// prep and clean from possible previous run
			for (var object_id in arr_data.objects) {
				
				setNodeProperties(false, object_id, false);
				
				arr_data.objects[object_id].social_visualisation_attributes_added = false;
				arr_data.objects[object_id].checked = false;

			}	
			
			// Update arr_data.objects to inlcude all objects (refs in descs) and create arr_data.links for links between objects
			arr_set_data = obj.setObjectsLinks(arr_data);
			
			arr_data.objects = arr_set_data.objects;
			arr_data.links = arr_set_data.links;

			// Run first to set all values correctly, then draw with current frame
			//if (date_range.min != arr_data.date_range.min || date_range.max != arr_data.date_range.max) {
				
				setCheckSub(arr_data.date_range);
				
				setNodesLinks();
				
				createLinkElms();
				drawn = true;
				
			//}
			
			setdata = true;
		}

		
		for (var object_id in arr_data.objects) {
			
			arr_data.objects[object_id].checked = false;

		}
					
		setCheckSub(date_range);

		// Check if graph was not drawn before as a result of a pre defined frame
		if (pre_graph_init && !drawn) {
			
			setNodesLinks();
			createLinkElms();		
		}
					
		for (var i = 0, len = links.length; i < len; i++) {
			
			setLinkColor(links[i]);
		}
			
		if (pre_graph_init) {

			for (var i = 0, len = nodes.length; i < len; i++) {
				
				drawNodeElm(nodes[i]);
			}
		}

		if (pre_graph_init) {
			
			pre_graph_init = false;

			simulation = new handleForceSimulation();
			simulation.init();
			simulation.start();
				
			// Calculate layout before first draw
			if (nodes.length + links.length < 1000) {
				
				// For small graphs, just run simulation 100- or nodes.lenght-times
				var runs = Math.max(100, nodes.length);
				for (var i = 0; i < runs; i++) {
					
					simulation.resume();
					simulation.step();
					simulation.stop();
				}

				draw = true;

			} else {
				// For larger graphs, show progress of running simulation
				elm_selected_node_container.removeClass('hide');
				doTick();
			}
			
		} else {
			
			draw = true;
			simulation.start();
			
			if (metrics_process) {
				metrics_process.update(date_range);
			}
		}	
			
		if (!key_animate) {
			
			key_animate = ANIMATOR.animate(function() {

				if (draw && static_layout) {

					var interval = window.performance.now() - static_layout_timer;
					
					if (interval > static_layout_interval * 1000) {
						drawTick();
						static_layout_timer = window.performance.now();
					}
					
					first_run = false;
					
				} else if (draw && !static_layout) {
					
					drawTick();
					first_run = false;
					
				}
				
				if (!node_drag && !first_run && !device_is_touch) {
					interact();
				}

				if (simulation.stopDraw()) {
					
					draw = false;
					
					if (static_layout) {
						drawTick();
					}
					
					simulation.stop();
					
				}
				
				return true;
			}, key_animate);
		}
	};
	
	var handleForceSimulation = function() {
		
		var obj = this;
		var layout = false;
		
		this.init = function() {
			
			// D3 Force

			layout = d3.layout.force();	
			layout.nodes(nodes)
				.links(links)
				.size([width, height])
				.charge(force_options.charge)
				.gravity(force_options.gravity)
				.theta(force_options.theta)
				.friction(force_options.friction)
				.linkDistance(function(d) {
					return ((1 - (d.value / max_value))*80)+parseInt(force_options.node_max_size);
				});

			
			//layout = d3.forceSimulation()
			//	.nodes(nodes)
			//	.force('charge', d3.forceManyBody())
			//	.force('link', d3.forceLink().links(links))
			//	.force('center', d3.forceCenter(width / 2, height / 2));
			
			// FR
			// Alternative force implementation
		};	
		this.start = function() {
			
			// D3 Force
			// D3 force timer runs in the background until alpha is below threshold
			// Can be stopped with .stop()/.alpha(0)
			layout.start();
				
			// FR
		};
		this.resume = function() {
			
			// D3 Force
			layout.alpha(0.1);
				
			// FR
		};		
		this.step = function() {
			
			// D3 Force
			layout.tick();
				
			// FR
		};		
		this.stop = function() {
			
			// D3 Force
			layout.alpha(0);
				
			// FR
		};
		this.stopDraw = function() {
			
			// D3 Force
			// Stop drawing if force is under threshold
			if (layout.alpha() < 0.01) {
				return true;
			} else {
				return false;
			}
				
			// FR
		};
		this.getSpeed = function() {
			
			// D3 Force
			return layout.alpha();
				
			// FR
		};
		this.resize = function() {
			
			// D3 Force
			layout.size([width, height])
				
			// FR
		};
	};
	
	var doTick = function() {

		if (simulation.stopDraw()) {
			
			simulation.stop();
			
			elm_selected_node_container.addClass('hide');	
					
			draw = true;
			
			return;
			
		} else {

			var perc = 100*((1-simulation.getSpeed())/0.99);
			
			elm_selected_node_container.html('<p>Nodes: '+nodes.length+', Links: '+links.length+'</p><p>Layout complete: '+perc.toFixed(2)+'%</p>');
			
			setTimeout(function () {
				doTick();
			}, 0);
			
		}
		
	};
	
	var interact = function() {

		var pos_hover = obj_map.getMousePosition();
			
		if (!pos_hover) {
			
			if (pos_hover_poll.x) {
				
				pos_hover_poll = pos_hover;
				hover(false, false);
				cur_node_position = false;
				elm.removeClass('hovering');
			}
		} else {
						
			var x_point = pos_hover.x;
			var y_point = pos_hover.y;

			if (!pos_hover_poll || (Math.abs(x_point-pos_hover_poll.x) > 4 || Math.abs(y_point-pos_hover_poll.y) > 4)) {
			
				pos_hover_poll = pos_hover;
				var hovering = false;

				for (var i = 0, len = nodes.length; i < len; i++) {
					
					var arr_node = nodes[i];
					var distance_between_mouse_position_x_and_node_position_x = (((arr_node.x + pos_translation.x) * scale) + (stage.position ? stage.position.x : '')) - pos_hover_poll.x;
					var distance_between_mouse_position_y_and_node_position_y = (((arr_node.y + pos_translation.y) * scale) + (stage.position ? stage.position.y : '')) - pos_hover_poll.y;
					
					var totall_distance_squared = distance_between_mouse_position_x_and_node_position_x * distance_between_mouse_position_x_and_node_position_x + distance_between_mouse_position_y_and_node_position_y * distance_between_mouse_position_y_and_node_position_y;
					var radius_squared = ((arr_node.r + force_options.node_stroke_width) * scale) * ((arr_node.r + force_options.node_stroke_width) * scale);

					if (totall_distance_squared < radius_squared) {
						
						hover(arr_node.node_position, true);
						hovering = true;	
						cur_node_position = arr_node.node_position;
						elm.addClass('hovering');
						
					}
					
				}
				
				if (cur_node_position !== false && !hovering) {
					hover(false, false);
					cur_node_position = false;
					elm.removeClass('hovering');
				}
			}
		}	
    }

	var rePosition = function(move, pos, zoom, calc_zoom) {

		width = pos.size.width;
		height = pos.size.height;
		var redraw = false;

		if (calc_zoom) { // Zoomed
			
			if (display == 'pixel') {
				if (calc_zoom == '-1') {
					scale = scale * 0.7;
				} else if (calc_zoom == '+1') {
					scale = scale * 1.4286;
				}
			} else {
				scale = width/init_size.width;
			}

			redraw = true;
		}
		
		if (display == 'pixel') {
			
			if (width != init_size.width || height != init_size.height) {
				
				redraw = true;
				draw = true;
					
				size_renderer.width = width;
				size_renderer.height = height;
				
				renderer.resize(width, height);
				renderer_2.resize(width, height);		
				
				elm_canvas.width = width;
				elm_canvas.height = height;	
								
				shader.uniforms.bounds = [width, height];	
				
				simulation.resize();
				simulation.start();
				
			}
									
			if (move === true) { // Move Starts
				if (node_drag === false) {
					position.x = pos.x;
					position.y = pos.y;
					dragging = true;
				}
			}
			if (move !== false && dragging) { // Moving...
				
				pos_translation.x = pos_translation.x - (position.x - pos.x) / scale;
				pos_translation.y = pos_translation.y - (position.y - pos.y) / scale;
				
				shader.uniforms.translation = [pos_translation.x, pos_translation.y];

				position.x = pos.x;
				position.y = pos.y;
				
				
			}
			if (move === false && dragging) { // Move Ends
				dragging = false;
			}
			
			if (calc_zoom == '-1' || calc_zoom == '+1') { 
				
				stage.position.x = (size_renderer.width - (size_renderer.width * scale)) / 2;
				stage_2.position.x = (size_renderer.width - (size_renderer.width * scale)) / 2;
				
				stage.position.y = (size_renderer.height - (size_renderer.height * scale)) / 2;
				stage_2.position.y = (size_renderer.height - (size_renderer.height * scale)) / 2;
				
				//stage.position.x = size_renderer.width - ((Math.abs(pos.x) - size_renderer.width) * scale - (Math.abs(pos.x) - size_renderer.width));
				//stage.position.y = size_renderer.height - ((Math.abs(pos.y) - size_renderer.height) * scale - (Math.abs(pos.y) - size_renderer.height));
				
				//stage.position.x = stage.position.x - ((Math.abs(pos.x) - stage.position.x) * scale - (Math.abs(pos.x) - stage.position.x));
				//stage.position.y = stage.position.y - ((Math.abs(pos.y) - stage.position.y) * scale - (Math.abs(pos.y) - stage.position.y));

				shader.uniforms.stagetranslation = [stage.position.x, stage.position.y];
				shader.uniforms.scale = [scale, scale];
				
			}
			
		} else {
				
			drawer.style.width = width+'px';
			drawer.style.height = height+'px';
			
			svg_group.setAttribute('transform', 'scale('+scale+')');
			
		}

		if (!first_run) {
			
			if (redraw && display == 'pixel') {
				for (var i = 0, len = nodes.length; i < len; i++) {
					nodes[i].redraw_node = true;
				}
			}
			
			drawTick();
		}
	};
	
	var createLinkElms = function() {	
		
		if (!force_options.show_line) {
			
			return;
		}
		
		if (display == 'pixel') {

			var uvs = new Uint16Array(links.length * 4);
			
			for (var i = 0; i < links.length * 4; i++) {
				vertices[i] = 0;
				uvs[i/2] = i/2;	
			}
			
			for (var i = 0; i < links.length * 8; i++) {
				colors[i] = 0;
			}
			
			vertices = new Float32Array(vertices);
			colors = new Float32Array(colors);
			
			position_vertexbuffer = new glCore.GLBuffer.createVertexBuffer(gl, vertices, gl.DYNAMIC_DRAW);
			colors_vertexbuffer = new glCore.GLBuffer.createVertexBuffer(gl, colors, gl.DYNAMIC_DRAW);
			
			var indexbuffer = new glCore.GLBuffer.createIndexBuffer(gl, uvs);
			var vertexarrayobject = new glCore.VertexArrayObject(gl);
			vertexarrayobject.addIndex(indexbuffer)
				.addAttribute(position_vertexbuffer, shader.attributes.a_position)
				.addAttribute(colors_vertexbuffer, shader.attributes.a_color)
				.bind();
				
		} else {
					
			for (var i = 0, len = links.length; i < len; i++) {
				
				var arr_link = links[i];
				
				arr_link.elm = stage.createElementNS(stage_ns, 'path');
				svg_group.appendChild(links[i].elm);
				
				arr_link.elm.setAttribute('fill', 'none');
				arr_link.elm.setAttribute('stroke', links[i].color);
				arr_link.elm.setAttribute('stroke-width', '1.5px');
				
				if (force_options.link_arrowhead) {
					arr_link.elm.setAttribute('marker-end', 'url(#end)');
				}
				
			}
			
		}
				
	};
	
	var drawNodeElm = function(arr_node) {
			
		var elm = arr_node.elm;
		var radius = 0;
		var highlight = false;
		arr_node.redraw_node = false;
		
		// Set the primary color of the node
		if (!arr_node.color) {
						
			if (arr_node.style && arr_node.style.color) {
				
				// Color set by Object
				if (typeof arr_node.style.color == 'object') { // Select last color color contains multiple values
					arr_node.color = arr_node.style.color[arr_node.style.color.length-1];
				} else {
					arr_node.color = arr_node.style.color;
				}
				
			} else if (arr_data.legend.types && arr_data.legend.types[arr_node.type_id] && arr_data.legend.types[arr_node.type_id].color) {
				
				// Color set by Type
				arr_node.color = arr_data.legend.types[arr_node.type_id].color;
				
			} else {
				
				// Color set by Visualisation 
				arr_node.color = force_options.node_color;
			}
		}
		
		var color = arr_node.color;
		
		if (arr_node.hightlight_color) {
			
			color = arr_node.hightlight_color;
			
			highlight = true;
			arr_node.hightlight_color = false;

		}
		
		if (!highlight && arr_node.has_conditions) {
			
			// update conditioned colors and weight
			handleConditions(arr_node);	
						
		}
		
		if (weight_is_size) {
			
			var weighted_connections = arr_node.conditions_weight;
			
		} else {
			
			var weighted_connections = (arr_node.in.length + arr_node.out.length) * arr_node.conditions_weight;
		}
		
		radius = weighted_connections/max_complete_connections * force_options.node_max_size;

		if (radius > 0 && radius < force_options.node_min_size) {
			
			radius = force_options.node_min_size;
			
		}
		
		if (radius == 0 && show_disconnected_node == true) {
			
			radius = force_options.node_min_size;
			
		}

		arr_node.r = Math.round(radius);

		if (display == 'pixel') {
			
			/*
			var size = arr_node.r * scale;
			var hex_color = arr_node.color;
			
			var identifier_texture = size+'-'+hex_color;
			var elm_container = arr_elm_particles[identifier_texture];

			if (!elm_container || elm_container[0].children.length >= size_max_elm_container) {
				
				if (elm_container) { // Container is full
					
					var elm_container = false;
					var count = 0;
					
					while (1) {
						
						var identifier_texture_temp = identifier_texture+'-'+count;
						
						if (!arr_elm_particles[identifier_texture_temp]) {

							arr_elm_particles[identifier_texture_temp] = arr_elm_particles[identifier_texture];
							break;
						} else if (arr_elm_particles[identifier_texture_temp][0].children.length < (size_max_elm_container / 10)) {
							
							elm_container = arr_elm_particles[identifier_texture_temp];
							arr_elm_particles[identifier_texture_temp] = arr_elm_particles[identifier_texture];
							arr_elm_particles[identifier_texture] = elm_container;
							break;
						}
						
						count++;
					}
				}
				
				arr_elm_particles[identifier_texture] = [];
				arr_elm_particles[identifier_texture][0] = arr_elm_particles[identifier_texture][1] = new PIXI.particles.ParticleContainer(size_max_elm_container, {position: true, alpha: true});
				var elm_container = arr_elm_particles[identifier_texture];
				elm_plot_particles.addChild(elm_container[0]);
			}
			
			var arr_texture = arr_assets_texture_line_dots[identifier_texture];
							
			if (!arr_texture) {
				
				var arr_texture = [];
				
				var elm = new PIXI.Graphics();
				elm.beginFill(hex_color, 1);
				elm.drawCircle(size, size, size);
				elm.endFill();
				
				arr_texture[0] = elm.generateCanvasTexture();
				arr_texture[1] = arr_texture[0];
					
				arr_assets_texture_line_dots[identifier_texture] = arr_texture;
			}
			
			var elm = new PIXI.Sprite(arr_texture[0]);
			elm.position.x = 0;
			elm.position.y = 0;
			elm.anchor.x = 0.5;
			elm.anchor.y = 0.5;
			
			elm_container[0].addChild(elm);
			
					var elm_group = new PIXI.Container();
					
					var cur_count = 0;
					
					var elm = new PIXI.Graphics();
					if (width_dot_stroke) {
						elm.lineStyle(width_dot_stroke, parseColor(color_dot_stroke), 1);
					}
					elm.drawCircle(0, 0, (r + width_dot_stroke/2));
					
					elm_group.addChild(elm);

					for(var i = 0, len = arr.length; i < len; i++) {
								
						var start = (cur_count / count) * 2 * Math.PI;
						cur_count += arr[i].count;
						var end = (cur_count / count) * 2 * Math.PI;
						
						var elm = new PIXI.Graphics();
						elm.beginFill(parseColor(arr[i].color), 1);
						elm.moveTo(0, 0)
							.lineTo(r * Math.cos(start), r * Math.sin(start))
							.arc(0, 0, r, start, end, false)
							.lineTo(0, 0);
						elm.endFill();
						
						elm_group.addChild(elm);
						
						arr[i].count = 0;
					}
					
					elm_plot_dots.addChild(elm_group);
					
					var elm = elm_group; 
			
			*/
						
			if (!elm) {
				
				if (arr_node.conditions.length) {
					
					elm = new PIXI.Container();
					
				} else {
					
					elm = new PIXI.Graphics();
					
				}
				
				arr_node.elm = elm;
				elm_plot_dots.addChild(elm);
				
				if (arr_node.show_text) {
					
					var elm_text = new PIXI.Text(arr_node.name, {fontSize: 8, fontFamily: 'pixel'});
								
					stage_2.addChild(elm_text);
								
					elm_text.position.x = 0;
					elm_text.position.y = 0;
					
					arr_node.elm_text = elm_text;
					
				}
				
				
			} else {
				

				if (arr_node.has_conditions) {
					
					arr_node.elm.children = [];
					
				} else {
					
					arr_node.elm.clear();
					
				}				
				
				var current_node_elm_position = elm.position;
				
			}
			
			if (radius) {
				
				var size = radius * scale;
				
				if (arr_node.has_conditions) {
					
					var elm_stroke = new PIXI.Graphics();
					elm_stroke.lineStyle(force_options.node_stroke_width, parseColor(force_options.node_stroke_color), 1);
					elm_stroke.drawCircle(0, 0, (size + force_options.node_stroke_width/2));
					
					elm.addChild(elm_stroke);
					
					if (highlight) {
						
						arr_node.colors = [{'color': color, 'portion': 1}];
						
					}

					var current_portion = 0; 
					
					for (var i = 0; i < arr_node.colors.length; i++) {
						
						var start = current_portion * 2 * Math.PI;
						current_portion = current_portion + arr_node.colors[i].portion;
						var end = current_portion * 2 * Math.PI;
						
						var elm_portion = new PIXI.Graphics();
						elm_portion.beginFill(parseColor(arr_node.colors[i].color), 1);
						elm_portion.moveTo(0, 0)
							.lineTo(size * Math.cos(start), size * Math.sin(start))
							.arc(0, 0, size, start, end, false)
							.lineTo(0, 0);
						elm_portion.endFill();
						
						elm.addChild(elm_portion);
						
					}
								
				} else {
				
					elm.lineStyle(force_options.node_stroke_width, parseColor(force_options.node_stroke_color), 1);
					elm.beginFill(parseColor(color), 1);
					elm.drawCircle(0, 0, size);
					elm.endFill();
					
				}
				
				if (current_node_elm_position) {
					
					elm.position = current_node_elm_position;
					
				} else {
					
					elm.position.x = 0;
					elm.position.y = 0;
				}
				
				if (arr_node.elm_text) {
					arr_node.elm_text.alpha = 1;
				}
				
			} else {
				
				if (arr_node.elm_text) {
					arr_node.elm_text.alpha = 0;
				}
			}			
		
		} else {
			
			if (!elm) {
				
				var elm = stage.createElementNS(stage_ns, 'g');
				svg_group.appendChild(elm);
				
				if (arr_node.show_text) {
					var elm_text = stage.createElementNS(stage_ns, 'text');
					var textnode = stage.createTextNode(arr_node.name);
					elm_text.appendChild(textnode);
					elm.appendChild(elm_text);
				}
			
				var elm_circle = stage.createElementNS(stage_ns, 'circle');
				elm.appendChild(elm_circle);
				elm_circle.setAttribute('stroke', parseColor(force_options.node_stroke_color));
				elm_circle.setAttribute('stroke-width', force_options.node_stroke_width);
				
				arr_node.elm = elm;
				
			} else {
				
				var elm_circles = arr_node.elm.getElementsByTagName('circle');
				var elm_circle = elm_circles[0];
				
				if (arr_node.show_text) {
					var elm_texts = arr_node.elm.getElementsByTagName('text');
					var elm_text = elm_texts[0];
				}
				
			}
			
			elm.setAttribute('data-node_position', arr_data.objects[arr_node.id].node_position);
			
			if (arr_node.has_conditions) {
			
				// Remove previous pie
				var elm_paths = arr_node.elm.getElementsByTagName('path');
				
				while (elm_paths.length) {
					arr_node.elm.removeChild(elm_paths[elm_paths.length - 1]);
				}
				
				if (!highlight) {
					
					if (arr_node.colors.length == 1) {
						
						color = arr_node.colors[0].color;
						
					} else {
						
						color = 'none';
						
						var current_portion = 0; 
						var x = 0;
						var y = 0;
							
						for (var i = 0; i < arr_node.colors.length; i++) {

							var start = current_portion * 2 * Math.PI;
							current_portion = current_portion + arr_node.colors[i].portion;
							var end = current_portion * 2 * Math.PI;

							var elm_path = stage.createElementNS(stage_ns, 'path');
							
							elm_path.setAttribute('d','M '+Math.floor(x)+','+Math.floor(y)+' L '+(Math.floor(x) + radius * Math.cos(start))+','+(Math.floor(y) + radius * Math.sin(start))+' A '+radius+','+radius+' 0 '+(end - start < Math.PI ? 0 : 1)+',1 '+(Math.floor(x) + radius * Math.cos(end))+','+(Math.floor(y) + radius * Math.sin(end))+' z');

							elm_path.style.fill = arr_node.colors[i].color;
							
							arr_node.elm.appendChild(elm_path);

						}
						
					}
				}
			
			}
			
			elm_circle.setAttribute('r', (radius ? (radius + force_options.node_stroke_width/2) : 0));
			elm_circle.setAttribute('fill', color);
			
			if (arr_node.show_text) {
				if (radius) {	
					elm_text.setAttribute('opacity', 1);		
					elm_text.setAttribute('dx', (radius ? (radius + force_options.node_stroke_width/2) + 3 : 0));
				} else {
					elm_text.setAttribute('opacity', 0);
				}
			}

		}
		
		arr_node.color = false;
		
	}
	
	var handleConditions = function(arr_node) {

		// Node size is based on amount of links
		// One link can set multiple colours
		// One part is relative to total amount of parts (i.e. links)
		// One part may containe multiple colours
		// Grouped later by colour
		
		// Do we need one part for conditions generated by the object itself? Based on object or sub-object conditions
		var part_based_on_object_conditions = 0;
		
		// Conditions based on object
		if (arr_node.conditions.object.length) {
			
			part_based_on_object_conditions = 1;
		} else {

			// Conditions based on object subs
			for (var i = 0; i < arr_node.conditions.object_sub.length; i++) {
			
				if (arr_data.object_subs[arr_node.conditions.object_sub[i].source_id].active) {
					part_based_on_object_conditions = 1;
					break;
				}
			}
		}
		
		// Total number of parts is based on all incoming relations (that can generate cross-referenced conditions) and one part for object conditions
		var total_number_of_parts = part_based_on_object_conditions + arr_node.in.length;
		
		// If no parts are there, return.
		if (!total_number_of_parts) {
			
			arr_node.colors = [{'color': arr_node.color, 'portion': 1}];
			return;
		}
	
		var arr_condition_colors = [];
		var arr_parts = {};
		var arr_grouped_colors = {};
		var count = 0;
		var conditions_weight = 0;
		var unconditioned_part = 0;
		
		// Cross referenced conditions based on object definitions
		for (var i = 0; i < arr_node.conditions.object_definition.length; i++) {

			var arr_condition = arr_node.conditions.object_definition[i];
			var parent_pos = indexOfFor(arr_node.object_parents, arr_condition.source_id);
			if (parent_pos == -1) {
				continue;
			}
			
			if (!arr_parts['o_'+arr_condition.source_id]) {
				arr_parts['o_'+arr_condition.source_id] = {'colors': []};
				count++;
			}
			
			arr_parts['o_'+arr_condition.source_id].colors.push({'color': arr_condition.color});

			conditions_weight += arr_condition.conditions_weight;
		}
		
		// Cross referenced conditions based on sub object definitions
		for (var i = 0; i < arr_node.conditions.object_sub_definition.length; i++) {
			
			var arr_condition = arr_node.conditions.object_sub_definition[i];
			var parent_pos = indexOfFor(arr_node.sub_object_parents, arr_condition.source_id);
			if (parent_pos == -1) {
				continue;
			}
			
			if (!arr_parts['s_'+arr_condition.source_id]) {
				arr_parts['s_'+arr_condition.source_id] = {'colors': []};
				count++;
			}
			
			arr_parts['s_'+arr_condition.source_id].colors.push({'color': arr_condition.color});

			conditions_weight += arr_condition.conditions_weight;
		}
		
		if (count > total_number_of_parts) {
			total_number_of_parts = count;
		} else {
			unconditioned_part = total_number_of_parts - count;
		}
	
		if (part_based_on_object_conditions) {

			arr_parts['object'] = {'colors': []};	
				
			// Conditions based on object
			for (var i = 0; i < arr_node.conditions.object.length; i++) {
				
				var arr_condition = arr_node.conditions.object[i];
				arr_parts['object'].colors.push({'color': arr_condition.color});

				conditions_weight += arr_condition.conditions_weight;
			}

			// Conditions based on object subs
			for (var i = 0; i < arr_node.conditions.object_sub.length; i++) {
				
				var arr_condition = arr_node.conditions.object_sub[i];
				if (arr_data.object_subs[arr_condition.source_id].active) {
			
					var arr_condition = arr_node.conditions.object_sub[i];
					arr_parts['object'].colors.push({'color': arr_condition.color});

					conditions_weight += arr_condition.conditions_weight;
					
				}
			}
		}
			
		for (var part_id in arr_parts) {
			
			var part = arr_parts[part_id];
			
			if (part_id == 'object') {
				
				var portion_of_part = unconditioned_part/total_number_of_parts;
				
			} else {
			
				var portion_of_part = 1/total_number_of_parts;
			}
			
			var portion_of_part_colours = portion_of_part / part.colors.length;
			
			for (var i = 0; i < part.colors.length; i++) {
				
				if (!arr_grouped_colors[part.colors[i].color]) {
					
					arr_grouped_colors[part.colors[i].color] = {'color': part.colors[i].color, 'portion': 0};
					arr_condition_colors.push(arr_grouped_colors[part.colors[i].color]);
				}
				
				arr_grouped_colors[part.colors[i].color].portion += portion_of_part_colours;
			}
		}	

		if (!part_based_on_object_conditions && total_number_of_parts > count) {

			if (!arr_grouped_colors[arr_node.color]) {
				
				arr_grouped_colors[arr_node.color] = {'color': arr_node.color, 'portion': 0};
				arr_condition_colors.push(arr_grouped_colors[arr_node.color]);
			}
			
			arr_grouped_colors[arr_node.color].portion += unconditioned_part/total_number_of_parts;	
		}
		

		if (conditions_weight) {
			arr_node.conditions_weight = 1 + conditions_weight;
		}
		
					
		if (!arr_condition_colors.length) {
			arr_condition_colors.push({'color': arr_node.color, 'portion': 1})
		}

		arr_node.colors = arr_condition_colors;
		
	}
	
	var drawTick = function() {

		if (display == 'pixel') {
			var position_points = 4;
			var color_points = 8;
		}
		
		// Redraw Links
		if (force_options.show_line) {
			
			for (var i = 0, len = links.length; i < len; i++) {
				
				if (display == 'pixel') {

					var start = links[i].count * position_points;
					vertices[start++] = links[i].source.x;
					vertices[start++] = links[i].source.y;				
					vertices[start++] = links[i].target.x;				
					vertices[start++] = links[i].target.y;

					var start = links[i].count * color_points;
					colors[start++] = 0;
					colors[start++] = 0;
					colors[start++] = 0;
					colors[start++] = (links[i].value/max_value < 0.1 ? 0.1 : links[i].value/max_value);
					colors[start++] = 0;
					colors[start++] = 0;
					colors[start++] = 0;
					colors[start++] = (links[i].value/max_value < 0.1 ? 0.1 : links[i].value/max_value);
					
					
				} else {
					
					if (links[i].elm) {
						var dx = links[i].target.x - links[i].source.x,
						dy = links[i].target.y - links[i].source.y,
						dr = Math.sqrt(dx * dx + dy * dy) * 2.5;
						links[i].elm.setAttribute('d', "M" + links[i].source.x + "," + links[i].source.y + "A" + dr + "," + dr + " 0 0,1 " + links[i].target.x + "," + links[i].target.y);

						// Show previously hidden link after it has received its new position
						if (links[i].action == 'show') {
							links[i].elm.setAttribute('class', '');
							links[i].action = false;
						}
					}
				}
			}
		}
		
		// Redraw Nodes
		for (var i = 0, len = nodes.length; i < len; i++) {
			
			var arr_node = nodes[i];			
			var radius = arr_node.r;
			var pos_x = arr_node.x;
			var pos_y = arr_node.y;
			
			if (arr_node.redraw_node) {

				drawNodeElm(arr_node);
				arr_node.redraw_node = false;
			}
			
			arr_node.checked = false;
			
			if (display == 'pixel') {

				arr_node.elm.position.x = (pos_x + pos_translation.x) * scale;
				arr_node.elm.position.y = (pos_y + pos_translation.y) * scale;
				
				if (arr_node.elm_text) {
					arr_node.elm_text.position.x = arr_node.elm.position.x + (radius * scale) + 3;
					arr_node.elm_text.position.y = arr_node.elm.position.y;
				}
    
			} else {
				
				arr_node.elm.setAttribute('transform', 'translate(' + pos_x + ',' + pos_y + ')');
				
			}
		}
		
		if (display == 'pixel') {
			
			if (force_options.show_line) {
				
				position_vertexbuffer.upload(vertices);			
				colors_vertexbuffer.upload(colors);			
				gl.clear(gl.COLOR_BUFFER_BIT);
				gl.enable(gl.BLEND);
				gl.viewport(0, 0, width, height);
				gl.drawElements(gl.LINES, vertices.length, gl.UNSIGNED_SHORT, 0);

			}
			
			renderer.render(stage);
			renderer_2.render(stage_2);
		}
		
	};
	
	var addListeners = function () {
		
		var legends = obj_parent.elm_controls.find('.legends');
		elm_search_container = $('<figure />').addClass('search-nodes').appendTo(legends);
		
		elm_download = $('<button type="button" class="hide"><span class="icon"></span></button>').appendTo(legends);
		
		ASSETS.getIcons(elm, ['download'], function(data) {
				
			elm_download[0].children[0].innerHTML = data.download;
		});
				
		elm_download_graph = $('<a href="#"></a>').appendTo(legends);
		
		elm_download.on('click', function() {
			var svg_data = elm[0].innerHTML;
			var filename = 'graph.svg';
			svg_data = svg_data.replace(/^<svg/, '<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg"');
			
			if (window.navigator.msSaveOrOpenBlob) {
				var fileData = [svg_data];
				var blobObject = new Blob(fileData);
				elm_download_graph.click(function(){
					window.navigator.msSaveOrOpenBlob(blobObject, filename);
				});
			} else {
				var url = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg_data);
				elm_download_graph.attr('download', filename).attr('href', url);
			}
			
			elm_download_graph[0].click();
		});
		
		var search_input = $('<input type="search" />').appendTo(elm_search_container),
		dropdown = $('<ul />').addClass('dropdown hide').appendTo(elm_search_container);
		
		search_input.on('focus keyup', function() {
		
			var elm = $(this);
			var cur_input = elm.val();
			var max_results = 10;
			var arr_results = [];
			dropdown.empty().removeClass('hide');
			
			for (var i = 0; i < nodes.length; i++) {
				
				if (nodes[i].name && nodes[i].name.toLowerCase().indexOf(cur_input.toLowerCase()) > -1) {
					
					cur_total = nodes[i].sub_object_parents.length+nodes[i].object_parents.length;
					arr_results.push({cur_total: cur_total, name: nodes[i].html_name, node_position: i});
				}
			}
			
			if (arr_results.length > 0) {
				
				arr_results.sort(function(a, b) {
					return parseFloat(b.cur_total) - parseFloat(a.cur_total);
				});
				
				for (var i = 0; i < (arr_results.length > max_results ? max_results : arr_results.length); i++) {
					
					var li = $('<li />').appendTo(dropdown);
					var a = $('<a>'+arr_results[i].name+'</a>').data('node_position', arr_results[i].node_position).appendTo(li);
					
					a.on('click', function() {
						
						hover($(this).data('node_position'), true);
						dropdown.addClass('hide');
						
					}).on('mouseenter', function() {
						
						hover($(this).data('node_position'), true);
						$(this).addClass('active'); 
						
					}).on('mouseleave', function() {
						
						hover(false, false);
						$(this).removeClass('active'); 
						
					});
					
				}
			} else {
				
				dropdown.append('<li><a>No results.</a></li>');
			}
		});
		
		dropdown.on('mouseleave', function() {
			dropdown.addClass('hide');
		});
		
		elm_selected_node_container = $('<figure />').addClass('selected-node hide').appendTo(legends);

		if (device_is_touch) {
			
			elm[0].ontouchstart = function() {
				interact();
			}
			
		} else {
			
			var mousedown = false;
			
			elm[0].onmousedown = function(e) {
				
				mousedown = true;
				
				if (cur_node_position !== false) {
					nodes[cur_node_position].fixed = (nodes[cur_node_position].fixed ? 2 : 1);
				}
			}
			
			elm[0].onmousemove = function(e) {

				if (mousedown && cur_node_position !== false) {
					
					elm[0].arr_link = false;
					elm[0].arr_info_box = false;
					
					if (mousedown) {
						e.stopPropagation();
					}
					
					node_drag = true;
					
					var pos_hover = obj_map.getMousePosition();

					nodes[cur_node_position].x = nodes[cur_node_position].px = (pos_hover.x - pos_translation.x - (stage.position ? stage.position.x : '')) / scale;
					nodes[cur_node_position].y = nodes[cur_node_position].py = (pos_hover.y - pos_translation.y - (stage.position ? stage.position.y : '')) / scale;
					
					drawTick();
				}
			}
			
			elm[0].onmouseup = function(e) {
					
				mousedown = false;
				
				if (node_drag) {
					
					node_drag = false;
					nodes[cur_node_position].fixed = (nodes[cur_node_position].fixed == 2 ? false : 2);
					
					simulation.resume();
					draw = true;
				}
				
			}
		}	

		if (metrics) {
			metrics_process = new MapNetworkMetrics(legends, obj_parent);
		}

	};
	
	var hover = function (node_position, show_box, highlight) {
		
		elm[0].removeAttribute('title');
		
		if (highlight !== false) {
			
			while (arr_highlighted_nodes.length) {
				var unhighlighted_node_position = arr_highlighted_nodes.pop();
				drawNodeElm(nodes[unhighlighted_node_position]);
			}
			
			for (var i = 0; i < links.length; i++) {
				setLinkColor(links[i], false);
				if (force_options.link_arrowhead && display == 'vector' && links[i].elm) {
					links[i].elm.setAttribute('marker-end', 'url(#end)');
				}
			}
			
		}
		
		if (node_position === false) {
			elm[0].arr_link = false;
			elm[0].arr_info_box = false;
		}
			
		if (node_position !== false) {
				
			var object = arr_data.objects[nodes[node_position].id],
			connections = {},
			cur_in = 0,
			cur_out = 0,
			connection_object_parents = [],
			connection_object_sub_parents = [];

			if (highlight !== false) {

				nodes[node_position].hightlight_color = '#D92B2B';
				drawNodeElm(nodes[node_position]);
				
				if (arr_highlighted_nodes.indexOf(node_position) === -1) {
					arr_highlighted_nodes.push(node_position);
				}
				
				elm[0].setAttribute('title', nodes[node_position].html_name);
				
			}
						
			elm[0].arr_link = {object_id: parseInt(object.id), type_id: parseInt(object.type_id), object_sub_ids: object.sub_object_parents, connect_object_ids: getConnectObjectIds(object.id, object.object_parents)};
			elm[0].arr_info_box = {name: object.name};
			
			for (var i = 0, len = links.length; i < len; i++) {
				
				var connected_object_id = false;
				
				if (links[i].target.id == object.id) {
					
					cur_in++
					connected_object_id = links[i].source.id;
				} else if (links[i].source.id == object.id) {
					
					cur_out++
					connected_object_id = links[i].target.id;
				}
				
				if (connected_object_id) {
					
					if (!connections[connected_object_id]) {
						connections[connected_object_id] = {id: connected_object_id, name: arr_data.objects[connected_object_id].name, count: links[i].object_parents.length+links[i].sub_object_parents.length, parents: {'object_parents': links[i].object_parents, 'sub_object_parents': links[i].sub_object_parents}, total: arr_data.objects[connected_object_id].in.length+arr_data.objects[connected_object_id].out.length}
					} else {
						connections[connected_object_id].parents.object_parents = connections[connected_object_id].parents.object_parents.concat(links[i].object_parents);
						connections[connected_object_id].parents.sub_object_parents = connections[connected_object_id].parents.sub_object_parents.concat(links[i].sub_object_parents);
						connections[connected_object_id].count = connections[connected_object_id].count + links[i].object_parents.length+links[i].sub_object_parents.length;
					}
					
					if (highlight !== false) {
						if (force_options.link_arrowhead && display == 'vector' && links[i].elm) {
							links[i].elm.setAttribute('marker-end', 'url(#end-selected)');
						}
						setLinkColor(links[i], 'rgba(255,0,0,0.4)');
						
						
						nodes[arr_data.objects[connected_object_id].node_position].hightlight_color = '#FF7070';
						drawNodeElm(nodes[arr_data.objects[connected_object_id].node_position]);
						
						if (arr_highlighted_nodes.indexOf(arr_data.objects[connected_object_id].node_position) === -1) {
							arr_highlighted_nodes.push(arr_data.objects[connected_object_id].node_position);
						}
				
					}
					
					connection_object_parents = connection_object_parents.concat(links[i].object_parents);
					connection_object_sub_parents = connection_object_sub_parents.concat(links[i].sub_object_parents);
				}
			}

			if (show_box && !device_is_touch) {
				
				var span = $('<span class="a">'+object.html_name+'</span>').data('node_position', node_position);
									
				span.on('mouseenter', function() {
					hover($(this).data('node_position'), false);
				}).on('mouseleave', function() {
					hover(false, false);
				}).on('click', function() {
					elm.click();
				});
				
				elm_selected_node_container.removeClass('hide').html(span);
				var info = [{label: 'out-Links', elm: cur_out+'/'+object.out.length}];
				var details = getDataDetails(nodes[node_position].id, connection_object_parents, connection_object_sub_parents);
				
				for (var object_definition_id in details.source.object_definitions) {
					
					info.push({label: arr_data.info.types[object.type_id].name+' '+arr_data.info.object_descriptions[object_definition_id].object_description_name, elm: details.source.object_definitions[object_definition_id]});
				}
							
				for (var type_id in details.source.object_subs) {
								
					for (var object_sub_details_id in details.source.object_subs[type_id]) {	
								
						for (var object_sub_definition_id in details.source.object_subs[type_id][object_sub_details_id]) {
							
							info.push({label: arr_data.info.types[type_id].name+' ['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']', elm: details.source.object_subs[type_id][object_sub_details_id][object_sub_definition_id]});
						}
					}
				}
				
				info.push({label: 'in-Links', elm: cur_in+'/'+object.in.length});
				
				for (var type_id in details.target.object_definitions) {
					
					for (var object_definition_id in details.target.object_definitions[type_id]) {
						
						info.push({label: arr_data.info.types[type_id].name+' '+arr_data.info.object_descriptions[object_definition_id].object_description_name, elm: details.target.object_definitions[type_id][object_definition_id]});
					}
				}	
							
				for (var type_id in details.target.object_subs) {	
							
					for (var object_sub_details_id in details.target.object_subs[type_id]) {
								
						for (var object_sub_definition_id in details.target.object_subs[type_id][object_sub_details_id]) {
							
							info.push({label: arr_data.info.types[type_id].name+' ['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']', elm: details.target.object_subs[type_id][object_sub_details_id][object_sub_definition_id]});
						}
					}
				}				
				
				var arr_connections = [];
				
				for (var connected_object_id in connections) {
					
					arr_connections.push(connections[connected_object_id]);
				}
				
				arr_connections.sort(function(a, b) {
					return parseFloat(b.count) - parseFloat(a.count);
				});
				
				for (var i = 0; i < (arr_connections.length > 9 ? 10 : arr_connections.length); i++) {
					
					var connection_relations = $('<span title="Number of relations between '+object.name+' and '+arr_connections[i].name+' (total links of '+arr_connections[i].name+')">'+arr_connections[i].count+' ('+arr_connections[i].total+')</span>');
					
					//connection_relations.performanceData('info_box', {'name': arr_connections[i].name});
					//connection_relations.performanceData('link', {object_id: arr_connections[i].id, type_id: arr_data.objects[arr_connections[i].id].type_id, object_sub_ids: arr_connections[i].parents.sub_object_parents, connect_object_ids: getConnectObjectIds(arr_connections[i].id, arr_connections[i].parents.object_parents)});
					
					var connection_name = $('<span id="y:data_view:view_type_object-'+arr_data.objects[arr_connections[i].id].type_id+'_'+arr_connections[i].id+'" class="a popup">'+arr_connections[i].name+'</span>').data('node_position', arr_data.objects[arr_connections[i].id].node_position);
					
					connection_name.on('mouseenter', function() {
						hover($(this).data('node_position'), false);
					}).on('mouseleave', function() {
						hover(false, false);
					});
					
					info.push({label: connection_name, elm: connection_relations});
				};
				
				var list = $('<dl />').appendTo(elm_selected_node_container);
				
				for (var key in info) {
					var li = $('<li />').appendTo(list);
					var dt = $('<dt />').html(info[key].label).appendTo(li);
					var dd = $('<dd />').html(info[key].elm).appendTo(li);
				}
			}
		}
		
		
		if (!device_is_touch) {
			TOOLTIP.update();
		}
		
		if (display == 'pixel' && !first_run && !draw) {
			// Rerender stage to show/hide highlight colours
			renderer.render(stage);
		}
	};
	
	var getConnectObjectIds = function(child_object_id, arr_parent_object_ids) {
		
		// Get connecting object ids and object description ids between child and parent, to build performanceData -> link -> connect_object_ids object.
		
		arr_connect_objects = [];
		
		for (var i = 0; i < arr_parent_object_ids.length; i++) {
			
			var arr_parent_object_id = arr_parent_object_ids[i];
			var arr_parent_object = arr_data.objects[arr_parent_object_id];
			
			if (!arr_parent_object.inferred) {
				
				for (var object_definition_id in arr_parent_object.object_definitions) {
					
					var arr_object_definition = arr_parent_object.object_definitions[object_definition_id];
					
					if (arr_object_definition.ref_object_id.length) {
						
						for (var j = 0; j < arr_object_definition.ref_object_id.length; j++) {
							
							if (arr_object_definition.ref_object_id[j] == child_object_id) {
								
								arr_connect_objects.push({object_id: arr_parent_object_id, object_description_id: object_definition_id});
							}
						}
					}
				}
			}
		}
		
		return arr_connect_objects;	
	};
	
	var setNodesLinks = function() {

		max_complete_connections = 1;
		max_value = 1;

		for (var i = 0, len = nodes.length; i < len; i++) {
			var arr_node = nodes[i];
			
			if (weight_is_size) {
				
				if (arr_node.conditions_weight > max_complete_connections) {
					max_complete_connections = arr_node.conditions_weight;
				}
				
			} else {
				
				if ((arr_node.out.length + arr_node.in.length) * arr_node.conditions_weight > max_complete_connections) {
					max_complete_connections = (arr_node.out.length + arr_node.in.length) * arr_node.conditions_weight;
				}
			}
		}
		
		for (var i = 0, len = nodes.length; i < len; i++) {
			
			var arr_node = nodes[i];
			
			if (weight_is_size) {

				if (arr_node.conditions_weight / max_complete_connections > label_threshold) {
					arr_node.show_text = true;
				}				
			} else {
				
				if (((arr_node.out.length + arr_node.in.length) * arr_node.conditions_weight) / max_complete_connections > label_threshold) {
					arr_node.show_text = true;
				}
			}
		}
		
		for (var i = 0, len = links.length; i < len; i++) {
			
			var arr_link = links[i];
			
			if (arr_link.value > max_value) {
				max_value = arr_link.value;
			}
			
			arr_link.source = nodes[arr_data.objects[arr_link.source].node_position];
			arr_link.target = nodes[arr_data.objects[arr_link.target].node_position];
		}		
	};
	
	var checkInRange = function(arr_object_sub) {
		
		if (setdata) {
						
			if (obj_parent.obj_data.arr_inactive_types[arr_data.objects[arr_object_sub.object_id].type_id]) {
				return false;
			}
			
			var arr_object = arr_data.objects[arr_object_sub.object_id];
			
			if (obj_parent.obj_data.arr_loop_inactive_conditions.length) {
				
				for (var i = 0, len = obj_parent.obj_data.arr_loop_inactive_conditions.length; i < len; i++) {
					
					var has_inactive_condition = hasCondition(arr_object, obj_parent.obj_data.arr_loop_inactive_conditions[i]);
					
					if (has_inactive_condition) {
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
			var in_range = (date >= date_range.min && date <= date_range.max);
			var arr_object_subs = arr_data.date[date];
		
			for (var j = 0; j < arr_object_subs.length; j++) {
				
				if (in_range) {
					
					var arr_object_sub = arr_data.object_subs[arr_object_subs[j]];
					
					in_range = checkInRange(arr_object_sub);
				}
				
				checkSub(arr_object_subs[j], !in_range);
			}
		}
		
		// Sub objects with a date range
		for (var i = 0, len = arr_data.range.length; i < len; i++) {
			
			var arr_object_sub = arr_data.object_subs[arr_data.range[i]];
			var in_range = ((arr_object_sub.date_start >= date_range.min && arr_object_sub.date_start <= date_range.max) || (arr_object_sub.date_end >= date_range.min && arr_object_sub.date_end <= date_range.max) || (arr_object_sub.date_start < date_range.min && arr_object_sub.date_end > date_range.max));
			
			if (in_range) {
				in_range = checkInRange(arr_object_sub);
			}
			
			checkSub(arr_data.range[i], !in_range);
		}
		
		for (var i = 0; i < arr_remove_nodes.length; i++) {
			
			var object_id = arr_remove_nodes[i];
			arr_data.objects[object_id].checked = false;
			drawNodeElm(arr_data.objects[object_id]);
		}
		
	};
	
	var checkSub = function(object_sub_id, remove) {

		var object_sub = arr_data.object_subs[object_sub_id],
		count_object_sub_child_nodes = object_sub.child_nodes.length,
		count_object_sub_child_links = object_sub.child_links.length;
		
		object_sub.active = !remove;
		
		// Nodes and Links are added, removed or updated based on sub-object and object parents
		// They set whether a node/link may or may not exist
		// The size of the node/link is based on the number of links
			
		while (count_object_sub_child_nodes--) {
			
			var active = false,
			object_id = object_sub.child_nodes[count_object_sub_child_nodes],
			arr_object = arr_data.objects[object_id],
			arr_sub_object_parents = arr_object.sub_object_parents,
			arr_object_parents = arr_object.object_parents;
			
			if (arr_sub_object_parents.length || arr_object_parents.length) {
				var active = true;
			}
					
			var parent_pos = indexOfFor(arr_sub_object_parents, object_sub_id);
			if (remove) {
				if (parent_pos > -1) {
					arr_sub_object_parents.splice(parent_pos, 1);
					if (active) {				
						
						arr_object.redraw_node = true;
								
						if (arr_sub_object_parents.length == 0 && arr_object_parents.length == 0) {
							removeNode(arr_object);
						}
					} 
				}
			} else {

				if (parent_pos == -1) { // add node

					arr_object.redraw_node = true;
					
					arr_sub_object_parents.push(object_sub_id);		
					if (!active) {
	
						addNode(arr_object, true);
					} else {
	
						addNode(arr_object, false);
					}
				} 
			}
			
			checkNode(arr_object);
		}
		
		while (count_object_sub_child_links--) {
			
			var active = false,
			link_id = object_sub.child_links[count_object_sub_child_links],
			arr_link = arr_data.links[link_id],
			arr_sub_object_parents = arr_link.sub_object_parents,
			arr_object_parents = arr_link.object_parents;
			
			if (arr_sub_object_parents.length || arr_object_parents.length) {
				var active = true;
			}
			
			var parent_pos = indexOfFor(arr_sub_object_parents, object_sub_id);

			if (remove) {
				if (parent_pos > -1) {
					
					arr_sub_object_parents.splice(parent_pos, 1);
					
					if (active) {
						
						removeLink(arr_link);
					} 
				} 
			} else {					
				if (parent_pos == -1) {
					
					arr_sub_object_parents.push(object_sub_id);
					
					if (!active) {
						
						addLink(arr_link);
					}
				}
			}
			
			checkLink(arr_link);
			
		}
		
	}
	
	var checkNode = function(arr_object) {

		if (!setdata) {
			return;
		}

		var has_inactive_condition = false;
		
		if (!arr_object.checked) {
		
			arr_object.checked = true;
			
			if (obj_parent.obj_data.arr_loop_inactive_conditions.length) {
				
				for (var i = 0, len = obj_parent.obj_data.arr_loop_inactive_conditions.length; i < len; i++) {
					
					var has_inactive_condition = hasCondition(arr_object, obj_parent.obj_data.arr_loop_inactive_conditions[i]);
					
					if (has_inactive_condition) {
						break;
					}
				}
			}
			
			if (has_inactive_condition || obj_parent.obj_data.arr_inactive_types[arr_object.type_id]) {
					
				arr_object.inactive = true;
				removeNode(arr_object);
				
			} else {
				
				if (arr_object.inactive) {
					
					arr_object.inactive = false;
					
					addNode(arr_object, true);
				}
			}
			
			if (arr_object.child_nodes && arr_object.child_nodes.length) {
				
				for (var i = 0, len = arr_object.child_nodes.length; i < len; i++) {
					
					var arr_object_child = arr_data.objects[arr_object.child_nodes[i]];
					
					if (arr_object_child.id !== arr_object.id) {
						checkNode(arr_object_child);
					}
				}
			}
			
			if (arr_object.child_links && arr_object.child_links.length) {
				
				for (var i = 0, len = arr_object.child_links.length; i < len; i++) {
					
					var arr_link = arr_data.links[arr_object.child_links[i]];
					checkLink(arr_link);
				}
			}
		}
	}
	
	var checkLink = function(arr_link) {
		
		if (!setdata) {
			return;
		}
			
		if ((arr_data.objects[arr_link.source_id].inactive || arr_data.objects[arr_link.target_id].inactive) ||
			(arr_data.objects[arr_link.source_id].sub_object_parents.length + arr_data.objects[arr_link.source_id].object_parents.length == 0 || arr_data.objects[arr_link.target_id].sub_object_parents.length + arr_data.objects[arr_link.target_id].object_parents.length == 0)) {
			
			arr_link.inactive = true;				
			removeLink(arr_link);
			
		} else {
		
			if (arr_link.inactive) {
				
				arr_link.inactive = false;
				addLink(arr_link);
			}
		}
	}
	
	var addNode = function(arr_object, add) {		
			
		if (setdata && !arr_object.checked) {
			checkNode(arr_object);
		}
		
		if (setdata && arr_object.inactive) {
			return;
		}
		
		if (setdata && (arr_object.sub_object_parents.length + arr_object.object_parents.length == 0)) {
			return;
		}
		
		if (add) {
			
			if (arr_object.node_position === false) {
			
				var pos = nodes.push(arr_object);
				
				arr_object.node_position = pos - 1;
								
				arr_object.redraw_node = true;
			}
			
		}
		
		if (arr_object.child_nodes && arr_object.child_nodes.length) {
			
			for (var i = 0; i < arr_object.child_nodes.length; i++) {
				
				var object_child_id = arr_object.child_nodes[i],
				arr_object_child = arr_data.objects[object_child_id];
			
				var parent_pos = indexOfFor(arr_object_child.object_parents, arr_object.id);
				
				checkNode(arr_object_child);
				
				if (parent_pos == -1) {
					
					arr_object_child.object_parents[arr_object_child.object_parents.length] = arr_object.id;
							
					if ((arr_object_child.object_parents.length + arr_object_child.sub_object_parents) == 1) {
						addNode(arr_object_child, true);
					} else {						
						updateNode(arr_object_child, arr_object.id, false);
					}
				}
			}
		}
		
		if (arr_object.child_links && arr_object.child_links.length) {
			
			for (var i = 0; i < arr_object.child_links.length; i++) {
				
				var active = false,
				link_id = arr_object.child_links[i],
				arr_link = arr_data.links[link_id];
				
				if (arr_link) {
					
					if (arr_link.sub_object_parents.length || arr_link.object_parents.length) {
						var active = true;
					}
					
					var parent_pos = indexOfFor(arr_link.object_parents, arr_object.id);
					
					if (parent_pos == -1) {
						
						arr_link.object_parents.push(arr_object.id);
						
						if (!active) {
							
							addLink(arr_link);
							
						}
					} else {
						
						checkLink(arr_link);
					}
				}
			}
		}
	}
	
	var updateNode = function(arr_object, parent_id, remove) {

		if (setdata && arr_object.inactive) {
			return;
		}
		
		var parent_pos = indexOfFor(arr_object.object_parents, parent_id);
		
		if (remove) {
			if (parent_pos > -1) {
				arr_object.object_parents.splice(parent_pos, 1);
			}
		} else {
			if (parent_pos == -1) {
				arr_object.object_parents.push(parent_id);
			}
		}

		if (arr_object.object_parents.length == 0 && arr_object.sub_object_parents.length == 0) {
		
			removeNode(arr_object);
		
		} else {

			arr_object.redraw_node = true;
		}
	}
	
	var removeNode = function(arr_object) {

		if (show_disconnected_node == false) {
			
			var cur_pos = arr_object.node_position;
			
			if (cur_pos !== false) {
				
				if (cur_pos == cur_node_position) {
					
					cur_node_position = false;
				}
				
				if (cur_pos == nodes.length - 1) {
					
					nodes.pop();
				} else {
					
					nodes[cur_pos] = nodes.pop();
					
					var arr_repositioned_node = arr_data.objects[nodes[cur_pos].id];
					arr_repositioned_node.node_position = cur_pos;
					arr_repositioned_node.redraw_node = true;

				}
				
				arr_object.node_position = false;
			}
		}
		
		if (arr_object.child_links && arr_object.child_links.length) {
			
			for (var i = 0; i < arr_object.child_links.length; i++) {
				
				var active = false,
				link_id = arr_object.child_links[i],
				arr_link = arr_data.links[link_id];
				
				if (arr_link.object_parents.length) {
					var parent_pos = indexOfFor(arr_link.object_parents, arr_object.id);
					if (parent_pos > -1) {
						
						arr_link.object_parents.splice(parent_pos, 1);
						
						removeLink(arr_link);
					}
				}
			}
		}
		
		if (arr_object.child_nodes && arr_object.child_nodes.length) {
			
			for (var i = 0; i < arr_object.child_nodes.length; i++) {
				
				var arr_object_child = arr_data.objects[arr_object.child_nodes[i]];
				var parent_pos = indexOfFor(arr_object_child.object_parents, arr_object.id);
				
				if (parent_pos > -1) {
					
					arr_object_child.object_parents.splice(parent_pos, 1);
					
					if (arr_object_child.object_parents.length == 0 && arr_object_child.sub_object_parents.length == 0) {
						removeNode(arr_object_child);
					} else {
						updateNode(arr_object_child, arr_object.id, true);
					}
				}	
			}
		}
		
		
		if (arr_object.elm) {
		
			arr_remove_nodes.push(arr_object.id);

		}
		
	
	}
	var addLink = function(arr_link) {

		if (arr_link.link_position !== false) {
			return;
		}
	
		var arr_source_node = arr_data.objects[arr_link.source_id];
		var arr_target_node = arr_data.objects[arr_link.target_id];
		
		if (setdata && (arr_source_node.inactive || arr_target_node.inactive)) {
			return;
		}
		
		var pos_source_node = indexOfFor(arr_source_node.out, arr_link.id);	
		if (pos_source_node == -1) {
			arr_source_node.out.push(arr_link.id);
			arr_source_node.redraw_node = true;
		}
		
		var pos_target_object = indexOfFor(arr_target_node.in, arr_link.id);	
		if (pos_target_object == -1) {
			arr_target_node.in.push(arr_link.id);
			arr_target_node.redraw_node = true;
		}
				
		var pos = links.push(arr_link);
		arr_link.link_position = pos - 1;
				
		if (display == 'vector' && arr_link.elm) {
			arr_link.action = 'show'; 
		} else if (display == 'pixel') {
			//arr_link.pos = [0,0,0,0];
		}
		
	}
	
	var removeLink = function(arr_link) {
		
		if (arr_link.inactive || (arr_link.sub_object_parents.length == 0 && arr_link.object_parents.length == 0)) {

			var cur_pos = arr_link.link_position;
			
			if (cur_pos === false) {
				return;
			}

			var arr_source_node = arr_data.objects[arr_link.source_id];
			var arr_target_node = arr_data.objects[arr_link.target_id];
							
			var pos_source_node = indexOfFor(arr_source_node.out, arr_link.id);	
			if (pos_source_node > -1) {
				arr_source_node.out.splice(pos_source_node, 1);
				arr_source_node.redraw_node = true;
			}
			
			var pos_target_object = indexOfFor(arr_target_node.in, arr_link.id);	
			if (pos_target_object > -1) {
				arr_target_node.in.splice(pos_target_object, 1);
				arr_target_node.redraw_node = true;
			}
			
			if (cur_pos == links.length - 1) {
				links.pop();
			} else {
				links[cur_pos] = links.pop();
				arr_data.links[links[cur_pos].id].link_position = cur_pos;
			}
			
			arr_link.link_position = false;
			
			if (display == 'vector' && arr_link.elm) {
				arr_link.elm.setAttribute('class', 'hide');
			} else if (display == 'pixel') {
				vertices[arr_link.pos[0]] = 0;
				vertices[arr_link.pos[1]] = 0;				
				vertices[arr_link.pos[2]] = 0;				
				vertices[arr_link.pos[3]] = 0;
			}
		}
	}
	
	var setLinkColor = function(link, color) {

		if (color) {
			link.color = parseColor(color);
		} else {
			var level = Math.round((1 - (link.value/max_value))*200);
			if (level < 10) {
				level = 10;
			}
			var alpha = (display == 'pixel' ? '0.8' : '0.2');
			link.color = parseColor('rgba('+level+','+level+','+level+','+alpha+')');
		}
		if (display == 'vector' && link.elm) {
			link.elm.setAttribute('stroke', link.color);
		}
	}

	var getDataDetails = function (object_id, arr_object_parents, arr_object_sub_parents) {
		
		//instead of storing this in the arr we work with, we collect it when we need it
		var object = arr_data.objects[object_id];
		arr_object_parents = arrUnique(arr_object_parents),
		arr_object_sub_parents = arrUnique(arr_object_sub_parents),
		details = {source: {object_definitions: {}, object_subs: {}}, target: {object_definitions: {}, object_subs: {}}};
		
		//count all relations FROM context object
		for (var object_definition_id in object.object_definitions) {
			
			if (object.object_definitions[object_definition_id] && object.object_definitions[object_definition_id].ref_object_id.length) {
				details.source.object_definitions[object_definition_id] = object.object_definitions[object_definition_id].ref_object_id.length;
			}
		}
		
		//count all relations FROM context object sub objects based on object_sub_parents
		for (var i = 0; i < arr_object_sub_parents.length; i++) {
			
			if (arr_data.object_subs[arr_object_sub_parents[i]].object_id == object_id) {
				
				var object_sub = arr_data.object_subs[arr_object_sub_parents[i]];
				
				if (object_sub.object_sub_definitions) {
					
					for (var object_sub_definition_id in object_sub.object_sub_definitions) {
						
						var arr_object_sub_definition = object_sub.object_sub_definitions[object_sub_definition_id];
						
						if (arr_object_sub_definition && arr_object_sub_definition.ref_object_id.length) {
							
							for (var j = 0; j < arr_object_sub_definition.ref_object_id.length; j++) {
								
								if (arr_object_sub_definition.ref_object_id[j] != object_id) {
									
									var type_id = arr_data.objects[object_sub.object_id].type_id;
									
									if (!details.source.object_subs[type_id]) {
										details.source.object_subs[type_id] = {};
									}
									
									if (!details.source.object_subs[type_id][object_sub.object_sub_details_id]) {
										details.source.object_subs[type_id][object_sub.object_sub_details_id] = {};
									}
									
									if (!details.source.object_subs[type_id][object_sub.object_sub_details_id][object_sub_definition_id]) {
										details.source.object_subs[type_id][object_sub.object_sub_details_id][object_sub_definition_id] = 1;
									} else {
										details.source.object_subs[type_id][object_sub.object_sub_details_id][object_sub_definition_id]++;
									}
									
								}
							}
						}
					}
				}					
			}
		}
								
		//count all relations TOWARDS context object based on object_parents
		for (var i = 0; i < arr_object_parents.length; i++) {
			
			if (arr_object_parents[i] != object_id) {
				
				var ref_object = arr_data.objects[arr_object_parents[i]];
				
				for (var object_definition_id in ref_object.object_definitions) {
					
					var arr_object_definition = ref_object.object_definitions[object_definition_id];
					
					if (arr_object_definition && arr_object_definition.ref_object_id.length) {
						
						for (var j = 0; j < arr_object_definition.ref_object_id.length; j++) {
							
							if (arr_object_definition.ref_object_id == object_id) {
								
								if (!details.target.object_definitions[ref_object.type_id]) {
									details.target.object_definitions[ref_object.type_id] = {};
								}
								
								if (!details.target.object_definitions[ref_object.type_id][object_definition_id]) {
									details.target.object_definitions[ref_object.type_id][object_definition_id] = 1;
								} else {
									details.target.object_definitions[ref_object.type_id][object_definition_id]++;
								}								
							}
						}
					}
				}
			}
		}
		
		//count all relations TOWARDS context object based on object_sub_parents
		for (var i = 0; i < arr_object_sub_parents.length; i++) {
			
			if (arr_data.object_subs[arr_object_sub_parents[i]].object_id != object_id) {
				
				var object_sub = arr_data.object_subs[arr_object_sub_parents[i]];
				
				if (object_sub.object_sub_definitions) {
					
					for (var object_sub_definition_id in object_sub.object_sub_definitions) {
						
						var arr_object_sub_definition = object_sub.object_sub_definitions[object_sub_definition_id];
						
						if (arr_object_sub_definition && arr_object_sub_definition.ref_object_id.length) {
							
							for (var j = 0; j < arr_object_sub_definition.ref_object_id.length; j++) {
								
								if (arr_object_sub_definition.ref_object_id[j] == object_id) {
									
									var type_id = arr_data.objects[arr_data.object_subs[arr_object_sub_parents[i]].object_id].type_id;
									
									if (!details.target.object_subs[type_id]) {
										details.target.object_subs[type_id] = {};
									}
									
									if (!details.target.object_subs[type_id][object_sub.object_sub_details_id]) {
										details.target.object_subs[type_id][object_sub.object_sub_details_id] = {};
									}
									
									if (!details.target.object_subs[type_id][object_sub.object_sub_details_id][object_sub_definition_id]) {
										details.target.object_subs[type_id][object_sub.object_sub_details_id][object_sub_definition_id] = 1;
									} else {
										details.target.object_subs[type_id][object_sub.object_sub_details_id][object_sub_definition_id]++;
									}
								}
							}
						}
					}
				}					
			}
		}
		
		return details;
	}
		
	this.setObjectsLinks = function(arr_data) {

		var arr_data = arr_data;
		var arr_object_subs_loop = Object.keys(arr_data.object_subs);
		var count_arr_object_subs = arr_object_subs_loop.length;
		var arr_objects = arr_data.objects;
		var arr_links = {};
		var arr_object_subs = arr_data.object_subs;

		var mutuals = {};
		
		var makeLink = function(source_id, target_id) {
			
			var link_id = source_id + target_id;
			
			if (!arr_links[link_id]) {

				arr_links[link_id] = {id: link_id, count: linkcount++, source: source_id, target: target_id, source_id: source_id, target_id: target_id, object_parents: [], sub_object_parents: [], value: 0, show_text: false, link_position: false, pos: [link_vertex_position++, link_vertex_position++, link_vertex_position++, link_vertex_position++]};
			}
			
			arr_links[link_id].value++;

			return link_id;
		}
		
		for (var object_id in arr_objects) {
			
			var object = arr_objects[object_id];
			
			setNodeProperties(false, object_id, false);
			
			for (var object_definition_id in object.object_definitions) {
				
				var arr_object_definition = object.object_definitions[object_definition_id];

				if (arr_object_definition && arr_object_definition.ref_object_id.length) {
					
					for (var i = 0; i < arr_object_definition.ref_object_id.length; i++) {
													
						var target_object_id = setNodeProperties(arr_data.info.object_descriptions[object_definition_id].object_description_ref_type_id, arr_object_definition.ref_object_id[i] + '', arr_object_definition.value[i]);
						
						if (object_id != target_object_id) {
							
							var arr_target_object = arr_objects[target_object_id];
							
							if (arr_target_object && arr_target_object.child_nodes && arr_target_object.child_nodes.length && indexOfFor(arr_target_object.child_nodes, object_id) != -1) {
								
								// Mutual object descriptions keep both objects alive. To avoid this, we store mutual object descriptions the second time at the sub-objects
								if (!mutuals[object_id]) {
									mutuals[object_id] = [];
								}
								
								var link_id = makeLink(object_id, target_object_id);
								
								mutuals[object_id].push({target_object_id: target_object_id, link_id: link_id});
								
							} else {
								
								object.child_nodes.push(target_object_id);	
								
								var link_id = makeLink(object_id, target_object_id);
								
								object.child_links.push(link_id);	
								
							}

							// Weight of object applied to target node 
							if (arr_object_definition.style && arr_object_definition.style.weight) {

								arr_target_object.conditions_weight += arr_object_definition.style.weight;
							}
							
							if (arr_object_definition.style && arr_object_definition.style.color) {
								
								arr_target_object.has_conditions = true;
								
								if (typeof arr_object_definition.style.color == 'object') {
									
									for (var i = 0; i < arr_object_definition.style.color.length; i++) {
										
										var label = false;
										
										if (object.style.conditions) {
											
											label = getConditionLabel(arr_object_definition.style.color[i], object.style.conditions);
										}
										arr_target_object.conditions.object_definition.push({'label': label, 'source_id': object_id, 'color': arr_object_definition.style.color[i], 'conditions_weight': (arr_object_definition.style.weight ? arr_object_definition.style.weight : 0)});
									}
									
								} else {
									
									var label = false;
									
									if (object.style.conditions) {
										
										label = getConditionLabel(arr_object_definition.style.color, object.style.conditions);
									}
									
									arr_target_object.conditions.object_definition.push({'label': label, 'source_id': object_id, 'color': arr_object_definition.style.color, 'conditions_weight': (arr_object_definition.style.weight ? arr_object_definition.style.weight : 0)});
								}
							}
						}
					}
				}
			}			
		}
		
		var arr_objects_with_sub_object_parent = {};
		var orphanage_sub_object_id = false;
		
		while (count_arr_object_subs--) {
			
			var object_sub_id = arr_object_subs_loop[count_arr_object_subs];
			var object_sub = arr_object_subs[object_sub_id];
			var object_id = object_sub.object_id+'';
			object_sub.child_nodes = [object_id];
			object_sub.child_links = [];

			var object = arr_objects[object_id];
			
			arr_objects_with_sub_object_parent[object_id] = true;

			if (object_sub.object_sub_definitions) {
				
				for (var object_sub_definition_id in object_sub.object_sub_definitions) {
					
					var arr_object_sub_definition = object_sub.object_sub_definitions[object_sub_definition_id];
					
					if (arr_object_sub_definition && arr_object_sub_definition.ref_object_id.length) {
						
						for (var i = 0; i < arr_object_sub_definition.ref_object_id.length; i++) {
							
							var target_object_id = setNodeProperties(arr_data.info.object_sub_descriptions[object_sub_definition_id].object_sub_description_ref_type_id, arr_object_sub_definition.ref_object_id[i] + '', arr_object_sub_definition.value[i]);	
							
							if (object_id != target_object_id) {
								
								object_sub.child_nodes.push(target_object_id);
									
								var link_id = makeLink(object_id, target_object_id);
								
								object_sub.child_links.push(link_id);
								
								
								// Cross Referencing weight added to target node
								if (arr_object_sub_definition.style && arr_object_sub_definition.style.weight) {

									arr_objects[target_object_id].conditions_weight += arr_object_sub_definition.style.weight;
								}	
								
								// Cross Referencing color added to target node
								if (arr_object_sub_definition.style && arr_object_sub_definition.style.color) {
									
									arr_objects[target_object_id].has_conditions = true;
									
									if (typeof arr_object_sub_definition.style.color == 'object') {
										
										for (var i = 0; i < arr_object_sub_definition.style.color.length; i++) {
											
											var label = false;
											
											if (object_sub.style.conditions) {
												
												label = getConditionLabel(arr_object_sub_definition.style.color[i], object_sub.style.conditions);
											}
											
											arr_objects[target_object_id].conditions.object_sub_definition.push({'label': label, 'source_id': object_sub_id, 'color': arr_object_sub_definition.style.color[i], 'conditions_weight': (arr_object_sub_definition.style.weight ? arr_object_sub_definition.style.weight : 0)});
										}

									} else { 
										
										var label = false;
										
										if (object_sub.style.conditions) {
												
											label = getConditionLabel(arr_object_sub_definition.style.color, object_sub.style.conditions);
										}
										
										arr_objects[target_object_id].conditions.object_sub_definition.push({'label': label, 'source_id': object_sub_id, 'color': arr_object_sub_definition.style.color, 'conditions_weight': (arr_object_sub_definition.style.weight ? arr_object_sub_definition.style.weight : 0)});
									}
								}
								
							}
						}
					}
				}
			} 
			
			// Weight of sub-object applied to current node
			if (object_sub.style && object_sub.style.weight) {
	
				object.conditions_weight += object_sub.style.weight;
			}
										
			// Color of sub-object applied to current node
			if (object_sub.style && object_sub.style.color) {
				
				object.has_conditions = true;
				
				if (typeof object_sub.style.color == 'object') {
					
					for (var i = 0; i < object_sub.style.color.length; i++) {
						
						var label = false;
						
						if (object_sub.style.conditions) {
							
							label = getConditionLabel(object_sub.style.color[i], object_sub.style.conditions);
						}
						
						object.conditions.object_sub.push({'label': label, 'source_id': object_sub_id, 'color': object_sub.style.color[i], 'conditions_weight': (object_sub.style.weight ? object_sub.style.weight : 0)});
					
					}
					
				} else {
					
					var label = false;
						
					if (object_sub.style.conditions) {
							
						label = getConditionLabel(object_sub.style.color, object_sub.style.conditions);
					}
					
					object.conditions.object_sub.push({'label': label, 'source_id': object_sub_id, 'color': object_sub.style.color, 'conditions_weight': (object_sub.style.weight ? object_sub.style.weight : 0)});
				}
			}
			
			if (include_location_nodes && object_sub.location_object_id) {
				
				var target_object_id = setNodeProperties(object_sub.location_type_id, object_sub.location_object_id + '', object_sub.location_name);
				
				if (object_id != target_object_id) {	
							
					object_sub.child_nodes.push(target_object_id);
					
					var link_id = makeLink(object_id, target_object_id);
					
					object_sub.child_links.push(link_id);
					
				}
			}
		}

		
		if (mutuals[object_id]) {

			var arr_mutual = mutuals[object_id];
			
			for (var i = 0; i < arr_mutual.length; i++) {
				
				object_sub.child_links.push(arr_mutual[i].link_id);
				
				object_sub.child_nodes.push(arr_mutual[i].target_object_id);	
			}
		}

		return {objects: arr_objects, links: arr_links, object_subs: arr_object_subs};
	}
	
	var getConditionLabel = function(color, arr_parent_conditions) {
		
		var arr_legend_conditions = arr_data.legend.conditions;
		
		for (var label in arr_legend_conditions) {
			
			if (arr_parent_conditions[label] && arr_legend_conditions[label].color == color) {
				
				return label;
			}
		}
		
		return false;
	}
	 
	var hasCondition = function(arr_object, condition_label) {
		
		var arr_conditions = arr_object.conditions;
		
		for (var i = 0, len = arr_conditions.object.length; i < len; i++) {
			
			if (condition_label == arr_conditions.object[i].label) {
				return true;
			}
		}
		
		for (var i = 0, len = arr_conditions.object_sub.length; i < len; i++) {
			
			if (condition_label == arr_conditions.object_sub[i].label) {
				return true;
			}
		}
		
		for (var i = 0, len = arr_conditions.object_definition.length; i < len; i++) {
			
			if (condition_label == arr_conditions.object_definition[i].label) {
				return true;
			}
		}
		
		for (var i = 0, len = arr_conditions.object_sub_definition.length; i < len; i++) {
			
			if (condition_label == arr_conditions.object_sub_definition[i].label) {
				return true;
			}
		}
		
		return false;
	}
	
	var setNodeProperties = function(type_id, object_id, name) {
		
		var arr_object = {};
		var inferred = true;
		
		if (arr_data.objects[object_id]) { // Object is present in objects in arr_data
			
			arr_object = arr_data.objects[object_id];
			inferred = false;
			name = arr_object.name;
			type_id = arr_object.type_id;
		}
		
		if (!arr_object.social_visualisation_attributes_added) {
			
			arr_object.id = object_id + ''; //make string
			arr_object.type_id = type_id;
			
			arr_object.html_name = name;
			arr_object.name = stripHTMLTags(name);
			
			arr_object.social_visualisation_attributes_added = true;
			arr_object.inferred = inferred;
							
			arr_object.child_links = [];
			arr_object.child_nodes = [];
			arr_object.object_parents = [];
			arr_object.sub_object_parents = [];
			
			arr_object.in = [];
			arr_object.out = [];
			arr_object.r = 0;
			arr_object.checked = false;
			arr_object.inactive = false;
			arr_object.node_position = false;
			arr_object.elm = false;
			arr_object.redraw_node = false;
			arr_object.show_text = false;
			
			arr_object.has_conditions = false;
			arr_object.conditions = {'object': [], 'object_sub': [], 'object_definition': [], 'object_sub_definition': []};
			arr_object.colors = [];
			arr_object.conditions_weight = 1;
			
			if (arr_object.style && arr_object.style.weight) {
				arr_object.conditions_weight += arr_object.style.weight;
			}
			
			if (arr_object.style && arr_object.style.color) {
				
				arr_object.has_conditions = true;
				
				if (typeof arr_object.style.color == 'object') {
					
					for (var i = 0; i < arr_object.style.color.length; i++) {
						
						var label = false;
							
						if (arr_object.style.conditions) {
								
							label = getConditionLabel(arr_object.style.color[i], arr_object.style.conditions);
						}
						
						arr_object.conditions.object.push({'label': label, 'color': arr_object.style.color[i], 'conditions_weight': (arr_object.style.weight ? arr_object.style.weight : 0)});
					}
					
				} else {
					
					var label = false;
						
					if (arr_object.style.conditions) {
							
						label = getConditionLabel(arr_object.style.color, arr_object.style.conditions);
					}

					arr_object.conditions.object.push({'label': label, 'color': arr_object.style.color, 'conditions_weight': (arr_object.style.weight ? arr_object.style.weight : 0)});
				}
			}
		
			arr_data.objects[object_id] = arr_object;
			
		}
	
		return arr_object.id;
		
	}
		
	var indexOfFor = function(array, check) {
		
		for (var i = 0, len = array.length; i < len; i++) {
			
			if (array[i] == check) {
				return i;
			}
		}
		
		return -1;
    };


};
