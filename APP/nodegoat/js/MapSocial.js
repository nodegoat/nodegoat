
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2025 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 * 
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapSocial(elm_draw, PARENT, options) {

	var elm = $(elm_draw),
	elm_host = PARENT.elm_paint_host,
	SELF = this;
	
	const DISPLAY_VECTOR = 1;
	const DISPLAY_PIXEL = 2;
	
	var has_init = false,
	arr_labels = {},
	stage_ns = 'http://www.w3.org/2000/svg',
	elm_svg = false,
	elm_canvas = false,
	svg = false,
	svg_group = false,
	display = DISPLAY_VECTOR,
	elm_selected_node_container = false,
	elm_search_container = false,
	elm_analyse_container = false,
	elm_layout = false,
	elm_layout_statistics = false,
	elm_layout_select = false,
	elm_layout_status = false,
	elm_plot_lines = false,
	elm_plot_dots = false,
	elm_plot_info = false,
	arr_data = false,
	arr_loop_object_subs = [],
	include_location_nodes = false,
	show_disconnected_node = false,
	in_predraw = true,
	in_first_run = true,
	do_redraw = false,
	do_draw = false,
	count_loop = 0,
	num_node_weight_min = false,
	num_node_weight_max = false,
	num_link_weight_min = 1,
	num_link_weight_max = false,
	arr_nodes = {},
	arr_links = {},
	arr_object_subs_children = {},
	arr_loop_nodes = [],
    arr_loop_links = [],
    arr_active_nodes = [],
    arr_active_links = [],
	key_move = false,
	simulation = false,
	use_simulation_native = false,
	is_dragging_node = false,
	is_dragging = false,
	hovering = false,
	focus_object_id = false,
	cur_node_id = false,
	arr_highlighted_nodes = [],
	arr_remove_nodes = [],
	size_init = {width: 0, height: 0},
	size_renderer = {},
	renderer = false,
	renderer_2 = false,
	stage = false,
	stage_2 = false,
	drawer = false,
	drawer_2 = false,
	drawer_defs = false,
	num_scale = 1,
	static_layout = false,
	static_layout_interval = 0,
	static_layout_timer = window.performance.now(),
	font_family = 'var(--font-site)',
	size_text = 12,
	color_text = '#000000',
	color_highlight_node = '#d92b2b',
	color_highlight_node_connect = '#ff7070',
	color_highlight_link = 'rgba(255,0,0,0.4)',
	
	size_node_max = null,
	size_node_min = null,
	size_node_start = null,
	size_node_stop = null,
	color_node = null,
	color_node_stroke = null,
	width_node_stroke = null,
	
	width_line_min = 1.5,
	width_line_max = 1.5,
	color_line = 'rgb(100,100,100)',
	opacity_line = 0.2,
	arr_color_line = false,
	do_line_color_weight = false,
	do_node_icons_weight = false,
	
	pos_hover_poll = false,
	pos_move = {x: 0, y: 0},
	pos_translation = {x: 0, y: 0},
	
	use_metrics = false,
	metrics_process = false,
		
	geometry_shader = false,
	geometry_lines = false,
	geometry_mesh = false,
	buffer_geometry_lines_position = false,
	buffer_geometry_lines_normal = false,
	buffer_geometry_lines_color = false,
	length_geometry_lines_position = 12,
	length_geometry_lines_color = 12 * 2,
	do_update_geometry_lines_color = false,
	count_links = 0,
	count_nodes = 0,
	arr_assets_texture_icons = {},
	
	is_weighted = false,
	force_options = {},
	forceatlas2_options = {},
	show_line = true,
	show_arrowhead = false,
	label_threshold = 0.1,
	label_condition = false,
	show_icon_as_node = false,
	num_size_dot_icons = 15,
	num_offset_dot_icons = 4,
	num_spacer_dot_icons = 2,
	num_offset_dot_text = 3,

	key_animate = false,
	
	arr_elm_particles = [],
	arr_assets_texture_line_dots = [],
	size_max_elm_container = 15000,
	
	SocialUtilities = new MapUtilities(PARENT.obj_map);
		
	var parseColor = function(str) {
		
		if (display == DISPLAY_VECTOR) {
			return str;
		}
		
		return SocialUtilities.parseColorToHex(str);
	};
	
	var parseColorLink = function(str) {
		
		if (display == DISPLAY_VECTOR) {
			return str;
		}
		
		return SocialUtilities.parseColor(str);
	};
    			
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

		display = options.arr_visual.social.settings.display;
		static_layout = options.arr_visual.social.settings.static_layout;
		static_layout_interval = options.arr_visual.social.settings.static_layout_interval;
		
		var use_capture = (options.arr_visual.capture.enable ? true : false);
				
		size_node_max = parseFloat(options.arr_visual.social.dot.size.max);
		size_node_min = parseFloat(options.arr_visual.social.dot.size.min);
		size_node_start = parseFloat(options.arr_visual.social.dot.size.start);
		size_node_stop = parseFloat(options.arr_visual.social.dot.size.stop);
		color_node = options.arr_visual.social.dot.color;
		color_node_stroke = options.arr_visual.social.dot.stroke_color;
		width_node_stroke = options.arr_visual.social.dot.stroke_width;

		if (!options.arr_visual.social.label.show) {
			label_threshold = 1;
		} else {
			label_threshold = parseFloat(options.arr_visual.social.label.threshold);
			label_condition = options.arr_visual.social.label.condition;
			label_condition = (label_condition ? label_condition : false);
		}
		
		width_line_max = parseFloat(options.arr_visual.social.line.width.max);
		width_line_min = parseFloat(options.arr_visual.social.line.width.min);
		
		color_line = options.arr_visual.social.line.color;
		opacity_line = parseFloat(options.arr_visual.social.line.opacity);
		
		arr_color_line = parseCSSColor(color_line);
		
		force_options = {
			friction: parseFloat(options.arr_visual.social.force.friction),
			charge: parseInt(options.arr_visual.social.force.charge),
			gravity: parseFloat(options.arr_visual.social.force.gravity),
			theta: parseFloat(options.arr_visual.social.force.theta)
		};
		
		forceatlas2_options = {
			lin_log_mode: options.arr_visual.social.forceatlas2.lin_log_mode,
			outbound_attraction_distribution: options.arr_visual.social.forceatlas2.outbound_attraction_distribution,
			adjust_sizes: options.arr_visual.social.forceatlas2.adjust_sizes,
			edge_weight_influence: options.arr_visual.social.forceatlas2.edge_weight_influence,
			scaling_ratio: options.arr_visual.social.forceatlas2.scaling_ratio,
			strong_gravity_mode: options.arr_visual.social.forceatlas2.strong_gravity_mode,
			gravity: options.arr_visual.social.forceatlas2.gravity,
			slow_down: options.arr_visual.social.forceatlas2.slow_down,
			optimize_theta: options.arr_visual.social.forceatlas2.optimize_theta
		};
		
		const arr_setting_advanced = options.arr_visual.social.settings.social_advanced;
		
		if (typeof arr_setting_advanced.force_friction != 'undefined') {
			force_options.friction = parseFloat(arr_setting_advanced.force_friction);
		}
		if (typeof arr_setting_advanced.force_charge != 'undefined') {
			force_options.charge = parseInt(arr_setting_advanced.force_charge);
		}
		if (typeof arr_setting_advanced.force_gravity != 'undefined') {
			force_options.gravity = parseFloat(arr_setting_advanced.force_gravity);
		}
		if (typeof arr_setting_advanced.force_theta != 'undefined') {
			force_options.theta = parseFloat(arr_setting_advanced.force_theta);
		}
		
		if (typeof arr_setting_advanced.highlight_node_color != 'undefined') {
			color_highlight_node = arr_setting_advanced.highlight_node_color;
		}
		if (typeof arr_setting_advanced.highlight_node_connect_color != 'undefined') {
			color_highlight_node_connect = arr_setting_advanced.highlight_node_connect_color;
		}
		if (typeof arr_setting_advanced.highlight_link_color != 'undefined') {
			color_highlight_link = arr_setting_advanced.highlight_link_color;
		}
		
		if (typeof arr_setting_advanced.label_threshold != 'undefined') {
			label_threshold = parseFloat(arr_setting_advanced.label_threshold);
		}
		if (typeof arr_setting_advanced.metrics != 'undefined') {
			//use_metrics = parseBool(arr_setting_advanced.metrics, false);
		}
		if (typeof arr_setting_advanced.node_icons_size != 'undefined') {
			if (arr_setting_advanced.node_icons_size === 'weight') {
				do_node_icons_weight = true;
			} else {
				num_size_dot_icons = parseInt(arr_setting_advanced.node_icons_size);
			}
		}
		if (typeof arr_setting_advanced.node_icons_offset != 'undefined') {
			num_offset_dot_icons = parseInt(arr_setting_advanced.node_icons_offset);
		}
		if (typeof arr_setting_advanced.node_icons_show_as_node != 'undefined') {
			show_icon_as_node = parseBool(arr_setting_advanced.node_icons_show_as_node, true);
		}
		show_line = options.arr_visual.social.line.show;
		show_arrowhead = (show_line ? options.arr_visual.social.line.arrowhead_show : false);			
		if (options.arr_visual.social.settings.disconnected_dot_show) {
			show_disconnected_node = true;
		}
		if (options.arr_visual.social.settings.include_location_references) {
			include_location_nodes = true;
		}
		
		var pos_map = PARENT.obj_map.getPosition();
		size_init = {width: pos_map.size.width, height: pos_map.size.height};
		
		let arr_scripts = ['/js/support/d3-force.pack.js'];
		if (display == DISPLAY_PIXEL) {
			arr_scripts.push('/CMS/js/support/pixi.min.js');
		}

		ASSETS.fetch(elm_host, {
			script: arr_scripts, font: ['pixel'], labels: ['lbl_visualise_layout_complete', 'lbl_visualise_layout_iterations', 'lbl_nodes', 'lbl_links']
		}, function() {
			
			has_init = true;

			arr_data = PARENT.getData();
			
			if (arr_data.focus && arr_data.focus.object_id) {
				focus_object_id = arr_data.focus.object_id;
			}
			
			ASSETS.getLabels(elm_host, ['lbl_visualise_layout_complete', 'lbl_visualise_layout_iterations', 'lbl_nodes', 'lbl_links'], (data) => {arr_labels = data});

			var count_start = 0;
			
			count_start++; // Main loading

			var func_start = function() {
				
				if (count_start > 0) {
					return;
				}
			
				SELF.drawData = drawData;
				
				key_move = PARENT.obj_map.move(rePosition);
				
				if (num_zoom_initialise) {
					PARENT.obj_map.setZoom(PARENT.obj_map.getZoom());
				}
				
				PARENT.doDraw();
			};
			
			if (arr_data.legend.conditions) {
				
				var arr_media = [];
				
				for (const key in arr_data.legend.conditions) {
					
					const arr_condition = arr_data.legend.conditions[key];
					
					if (arr_condition.icon) {			
						arr_media.push(arr_condition.icon);
					}
					
					if (arr_condition.weight && arr_condition.weight > 0) {
						is_weighted = true;
					}
				}	

				if (arr_media.length) {
					
					count_start++ // Media loading
					
					ASSETS.fetch(elm_host, {media: arr_media}, function() {
						
						if (display == DISPLAY_PIXEL) {
							
							for (let i = 0, len = arr_media.length; i < len; i++) {
							
								const resource = arr_media[i];
								const arr_medium = ASSETS.getMedia(resource);
								const elm_image = arr_medium.image.cloneNode(false);
								
								const texture = PIXI.Texture.from(elm_image);
								
								arr_assets_texture_icons[resource] = {texture: texture, width: arr_medium.width, height: arr_medium.height};
							}
						}
						
						count_start--; // Media loaded
						
						func_start();
					});
				}
			}
			
			var num_zoom_initialise = pos_map.level;
				
			if (display == DISPLAY_PIXEL) {
									
				if (num_zoom_initialise < 0) {
					num_scale = num_scale * Math.pow(0.7, Math.abs(num_zoom_initialise));
				} else if (num_zoom_initialise > 0) {
					num_scale = num_scale * Math.pow(1.4286, num_zoom_initialise);
				}
				
				size_renderer = {width: pos_map.size.width, height: pos_map.size.height, resolution: pos_map.render.resolution};
				
				elm_canvas = document.createElement('canvas');
				elm_canvas.width = size_renderer.width;
				elm_canvas.height = size_renderer.height;	
				elm[0].appendChild(elm_canvas);
				
				PIXI.GRAPHICS_CURVES.adaptive = true;
				PIXI.GRAPHICS_CURVES.maxLength = (use_capture ? 2 : 10); // Use higher segment resolution (shorter curve lengths) when capturing
				PIXI.settings.SPRITE_MAX_TEXTURES = Math.min(PIXI.settings.SPRITE_MAX_TEXTURES, 16);

				geometry_lines = new PIXI.Geometry();

				var vertex_shader = `
					precision mediump float;
					attribute vec2 a_position;
					//attribute vec2 a_normal;
					attribute vec4 a_color;
					varying vec4 v_color;
					uniform vec2 u_bounds;
					uniform vec2 u_translation;
					uniform vec2 u_stagetranslation;
					uniform vec2 u_scale;
					uniform float u_width_line;
					void main(void) {
						//vec2 delta = a_normal * u_width_line;
					 	//vec2 pos = ((((a_position.xy + delta.xy + u_translation.xy) * u_scale.xy) + u_stagetranslation.xy) / u_bounds.xy) * 2.0 - 1.0;
					 	vec2 pos = ((((a_position.xy + u_translation.xy) * u_scale.xy) + u_stagetranslation.xy) / u_bounds.xy) * 2.0 - 1.0;
					 	gl_Position = vec4(pos * vec2(1, -1), 0, 1.0);
					 	v_color = a_color;
					}
				`;
					
				var fragment_shader = `
					precision mediump float;
					varying vec4 v_color;
					void main(void) {
						gl_FragColor = v_color;
					}
				`;

				geometry_shader = PIXI.Shader.from(vertex_shader, fragment_shader);
				
				geometry_shader.uniforms.u_bounds = [size_renderer.width, size_renderer.height];
				geometry_shader.uniforms.u_translation = [0, 0];
				geometry_shader.uniforms.u_stagetranslation = [0, 0];
				geometry_shader.uniforms.u_scale = [1.0, 1.0];
				//geometry_shader.uniforms.u_width_line = width_line_min;

				renderer = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: size_renderer.resolution, autoDensity: true});
				renderer_2 = PIXI.autoDetectRenderer({width: size_renderer.width, height: size_renderer.height, backgroundAlpha: 0, antialias: true, preserveDrawingBuffer: use_capture, resolution: size_renderer.resolution, autoDensity: true});

				stage = new PIXI.Container();
				stage_2 = new PIXI.Container();
				
				elm_plot_lines = new PIXI.Container();
				elm_plot_dots = new PIXI.Container();
				elm_plot_info = new PIXI.Container();
				
				stage.addChild(elm_plot_lines);
				stage.addChild(elm_plot_dots);
				stage_2.addChild(elm_plot_info);
				
				drawer = renderer.view;
				elm[0].appendChild(drawer);
				drawer_2 = renderer_2.view;
				elm[0].appendChild(drawer_2);
				
				renderer.events.autoPreventDefault = false;
				renderer_2.events.autoPreventDefault = false;
				drawer.style.removeProperty('touch-action');
				drawer_2.style.removeProperty('touch-action');
				
				font_family = 'pixel';
				size_text = 8;
			} else {

				size_renderer = {width: pos_map.size.width, height: pos_map.size.height};
				
				renderer = document.createElementNS(stage_ns, 'svg');
				renderer.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', stage_ns);
				
				drawer = renderer;
				drawer.style.width = size_renderer.width+'px';
				drawer.style.height = size_renderer.height+'px';
				
				elm[0].appendChild(drawer);
				
				stage = renderer.ownerDocument;
				
				drawer_defs = stage.createElementNS(stage_ns, 'defs');
				drawer.appendChild(drawer_defs);
				
				svg_group = stage.createElementNS(stage_ns, 'g');		
				drawer.appendChild(svg_group);
				
				const drawer_style_body = stage.createElementNS(stage_ns, 'style');
				drawer.appendChild(drawer_style_body);
				
				const node_style = document.createTextNode(`
					*[data-visible="0"] { display: none; }
				`);
				drawer_style_body.appendChild(node_style);
				
				if (use_capture) {
					
					count_start++; // Font loading
										
					font_family = 'pixel';
					size_text = 8;
					
					ASSETS.getFiles(elm_host, ['Unibody8Pro-Regular'], function(arr_files) {
						
						for (const str_identifier in arr_files) {
						
							const reader = new FileReader();
							reader.onload = function(e) {
								
								const str_url = e.target.result;

								const node_style = document.createTextNode(`
									@font-face { 
										font-family: 'pixel';
										src: url('`+str_url+`') format('woff');
										font-style: normal;
										font-weight: normal;
									}
								`);
								drawer_style_body.appendChild(node_style);
								
							};
							reader.readAsDataURL(arr_files[str_identifier]);
						}
						
						count_start--; // Font loaded
						
						func_start();
					}, {}, 'blob', '/css/fonts/', '.woff');
				}
			
				if (show_arrowhead) {
					
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
					marker.setAttribute('fill', color_node_stroke);
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

			addListeners();
			
			count_start--; // Main loaded
				
			func_start();
		});
	};
	
	this.close = function() {
		
		if (!has_init) { // Nothing loaded yet
			return;
		}

		elm_selected_node_container.remove();
		elm_search_container.remove();
		elm_layout.remove();
		
		ANIMATOR.animate(null, key_animate);

		simulation.close();
		
		if (display == DISPLAY_PIXEL) { // Destroy WEBGL memory
			stage.destroy(true);
			stage_2.destroy(true);
			renderer.destroy();
			renderer_2.destroy();
			
			for (var resource in arr_assets_texture_icons) {
				
				if (arr_assets_texture_icons[resource].texture) {
					
					arr_assets_texture_icons[resource].texture.destroy(true);
				}
			}
		}
	};
	
	var drawData = function(dateint_range_new, dateint_range_bounds, settings_timeline_new) {

		in_predraw = false;

		if (count_loop == 0) {
			
			in_predraw = true;
			
			parseData();

			var dateinta_range = {min: DATEPARSER.dateInt2Absolute(dateint_range_bounds.min), max: DATEPARSER.dateInt2Absolute(dateint_range_bounds.max)};
			
			setCheckObjectSubs(dateinta_range);
			checkNodes();
			setNodesLinksValues();
			createLinkElms();
			
			for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
				
				const arr_node = arr_loop_nodes[i];
				
				if (!arr_node.is_active) {
					continue;
				}
				
				const num_index = arr_active_nodes.push(arr_node);
				
				arr_node.index = num_index - 1;
				
				drawNodeElm(arr_node);
			}
			
			for (let i = 0, len = arr_loop_links.length; i < len; i++) {
			
				const arr_link = arr_loop_links[i];
				
				if (!arr_link.is_active) {
					continue;
				}

				arr_active_links.push(arr_link);
			}
			
			simulation = new HandleSimulation();
			simulation.init();
			
			in_predraw = false;
		}

		var dateinta_range = {min: DATEPARSER.dateInt2Absolute(dateint_range_new.min), max: DATEPARSER.dateInt2Absolute(dateint_range_new.max)};
					
		setCheckObjectSubs(dateinta_range);
		checkNodes();
		
		arr_active_nodes.splice(0, arr_active_nodes.length); // Clear live array
		arr_active_links.splice(0, arr_active_links.length); // Clear live array
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
		
			const arr_node = arr_loop_nodes[i];
			
			if (!arr_node.is_active) {
				continue;
			}
			
			const num_index = arr_active_nodes.push(arr_node);
			
			arr_node.index = num_index - 1;
		}
					
		for (let i = 0, len = arr_loop_links.length; i < len; i++) {
			
			const arr_link = arr_loop_links[i];
			
			if (!arr_link.is_active) {
				continue;
			}
			
			arr_active_links.push(arr_link);
			
			setLinkColor(arr_link);
		}
		
		elm_layout_statistics[0].innerHTML = '<p>'+arr_labels.lbl_nodes+': '+arr_active_nodes.length+' '+arr_labels.lbl_links+': '+arr_active_links.length+'</p>';
		
		simulation.start();
		
		if (count_loop == 0) {
			
			doTick();
		} else {
			
			do_draw = true;

			if (metrics_process) {
				metrics_process.update(dateinta_range);
			}
		}
		
		count_loop++;
			
		if (!key_animate) {
			
			key_animate = ANIMATOR.animate(function() {
				
				if (!do_draw) {
					
					if (!is_dragging_node && !in_first_run) {
						interact();
					}
					
					return true;
				}

				if (static_layout) {

					var interval = window.performance.now() - static_layout_timer;
					
					if (interval > static_layout_interval * 1000) {
						drawTick();
						static_layout_timer = window.performance.now();
					}
				} else {
					
					drawTick();
				}
				
				in_first_run = false;
				
				if (!is_dragging_node) {
					interact();
				}
				
				if (simulation.stopDraw()) {
					
					do_draw = false;
					
					if (static_layout) {
						drawTick();
					}
					
					simulation.stop();
				}
				
				return true;
			}, key_animate);
		}
	};
	
	var doTick = function() {
		
		if (!in_first_run) {
			return;
		}
		
		if (simulation.stopDraw()) {
			
			do_draw = false;
			in_first_run = false;
			
			drawTick();
			
			simulation.stop();
			
			return;
		} else {

			setTimeout(function () {
				doTick();
			}, 0);
		}
	};
	
	var HandleSimulation = function() {
		
		const SELF = this;
		
		this.draw = null;
		this.layout = null;
		
		var num_threshold = 1;
		const num_threshold_stop = 0.01;
		var str_layout = '';
				
		this.setSpeed = function(num_state) {
			
			num_threshold = num_state;
		};
		this.getSpeed = function() {
			
			return num_threshold;
		};
		this.getSpeedThreshold = function() {
			
			return num_threshold_stop;
		};
		this.stopDraw = function() {
			
			SELF.step();
			
			// Stop drawing if force is under threshold
			if (num_threshold < num_threshold_stop) {
			
				return true;
			} else {

				return false;
			}
		};
					
		this.setRunning = function(is_running_new) {
			
			const is_running = (is_running_new === false ? false : true);
			
			if (!is_running) {
				elm_layout[0].classList.remove('running');
			} else {
				elm_layout[0].classList.add('running');
			}
		};
		
		this.setRunningLayout = function(str_layout_new) {
			
			var str_layout_new = (str_layout_new ? str_layout_new : '');
			
			if (str_layout === str_layout_new) {
				return false;
			}
			
			str_layout = str_layout_new;
			elm_layout_select[0].value = str_layout;
			
			return true;
		};
		this.isRunningLayout = function(str_layout_check) {

			if (str_layout === (str_layout_check ? str_layout_check : '')) {
				return true;
			}
			
			return false;
		};
		
		this.setRunningStatistics = function(str_html) {
			
			elm_layout_status[0].innerHTML = str_html;
		};
		
		this.draw = (use_simulation_native ? new HandleSimulationDrawNative(SELF) : new HandleSimulationDrawWorker(SELF));
		this.layout = new HandleSimulationLayout(SELF);
	};
	
	var HandleSimulationDrawNative = function(obj) {
		
		const PARENT = obj;
		const SELF = this;
			
		var simulate = false;
		var simulate_force_links = false;
		
		PARENT.init = function() {
			
			PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 0%</p>');
			
			simulate_force_links = d3.forceLink();
			
			simulate = d3.forceSimulation()
				.nodes(arr_active_nodes)
				.force('charge', d3.forceManyBody()
					.strength(force_options.charge)
					.theta(force_options.theta)
				)
				.force('link', simulate_force_links
					.links(arr_active_links)
					.distance(function(d) {
						return ((1 - (d.weight / num_link_weight_max))*80) + size_node_max;
					}))
				.force("x", d3.forceX(size_renderer.width / 2)
					.strength(force_options.gravity)
				)
				.force("y", d3.forceY(size_renderer.height / 2)
					.strength(force_options.gravity)
				)
				//.force('center', d3.forceCenter(size_renderer.width / 2, size_renderer.height / 2)
				//	.strength(force_options.gravity)
				//)
				.velocityDecay(force_options.friction)
			;
			simulate.stop();
		};
		PARENT.start = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			PARENT.setRunningLayout();
			PARENT.setSpeed(1);
			
			simulate.nodes(arr_active_nodes);
			simulate_force_links.links(arr_active_links);
			
			simulate.alpha(1);
		};
		PARENT.step = function() {
			
			if (!PARENT.isRunningLayout()) { // Could be running an other layout
				return;
			}
			
			simulate.tick();
			
			PARENT.setSpeed(simulate.alpha());
			
			const has_stopped = (PARENT.getSpeed() < PARENT.getSpeedThreshold());
			
			if (!has_stopped) {
				
				const num_perc = 100*((1 - PARENT.getSpeed()) / (1 - PARENT.getSpeedThreshold()));
				
				PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': '+num_perc.toFixed(2)+'%</p>');
				
			} else {

				identifier_running = false;
				
				PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 100%</p>');
			}
		};
		PARENT.resume = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			PARENT.setRunningLayout();
			PARENT.setSpeed(0.1);
			
			simulate.alpha(0.1);
		};		
		PARENT.stop = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning(false);
			PARENT.setSpeed(0);
			
			simulate.alpha(0);
		};
		PARENT.close = function() {
			
			PARENT.stop();
		};
		PARENT.resize = function() {
			
			simulate.force("x", d3.forceX(size_renderer.width / 2)
					.strength(force_options.gravity)
				)
				.force("y", d3.forceY(size_renderer.height / 2)
					.strength(force_options.gravity)
				)
			;
		};
	};
	
	var HandleSimulationDrawWorker = function(obj) {
		
		const PARENT = obj;
		const SELF = this;
		
		var worker = false;
		var is_running = false;
		var identifier_running = false;
		
		var arr_matrix_nodes = null;
		var arr_matrix_edges = null;
		const num_properties_nodes = 5;
		const num_properties_edges = 3;
				
		PARENT.init = function() {
			
			PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 0%</p>');
			
			setMatrix();
			
			worker = createForceWorker();

			worker.addEventListener('message', function(event) {
				
				const has_identifier = (event.data.identifier !== false);
				const do_continue = (is_running && has_identifier && event.data.identifier == identifier_running); // Is the running iteration still relevant?
				
				arr_matrix_nodes = new Float32Array(event.data.nodes);
				
				var arr_nodes_matrix_index = [];
				
				for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
					
					const num_index = arr_matrix_nodes[i + 4];
					const arr_node = arr_loop_nodes[num_index];
										
					if (do_continue && (arr_node.fixed || arr_node.fixed != arr_matrix_nodes[i + 3])) {
						
						if (arr_node.fixed) {
						
							arr_matrix_nodes[i] = arr_node.x;
							arr_matrix_nodes[i + 1] = arr_node.y;
						}
						
						arr_matrix_nodes[i + 3] = arr_node.fixed;
							
						arr_nodes_matrix_index.push(i);
					} else {
						
						arr_node.x = arr_matrix_nodes[i];
						arr_node.y = arr_matrix_nodes[i + 1];
					}
				}

				if (do_continue) {
					
					PARENT.setSpeed(event.data.alpha);
					const has_stopped = (PARENT.getSpeed() < PARENT.getSpeedThreshold());
					
					if (!has_stopped) {
						
						worker.postMessage({
								action: 'loop',
								nodes: arr_matrix_nodes.buffer,
								nodes_state: (arr_nodes_matrix_index.length ? arr_nodes_matrix_index : false),
								identifier: identifier_running,
								iterations: 1
							},
							[arr_matrix_nodes.buffer]
						);
						
						const num_perc = 100*((1 - PARENT.getSpeed()) / (1 - PARENT.getSpeedThreshold()));
						
						PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': '+num_perc.toFixed(2)+'%</p>');
						
					} else {

						identifier_running = false;
						
						PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_complete+': 100%</p>');
					}
				}
			});
			
			worker.postMessage({
					action: 'init',
					nodes: arr_matrix_nodes.buffer,
					settings: {
						friction: force_options.friction,
						charge: force_options.charge,
						gravity: force_options.gravity,
						charge: force_options.charge,
						theta: force_options.theta,
						link_weight_max: num_link_weight_max,
						node_size_max: size_node_max,
						width: size_renderer.width,
						height: size_renderer.height
					}
				}
			);
		};
		PARENT.start = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			const has_changed = PARENT.setRunningLayout();
			
			setMatrix();
			
			is_running = true;
			identifier_running = count_loop;
			PARENT.setSpeed(1);
			
			worker.postMessage({
					action: 'start',
					nodes: arr_matrix_nodes.buffer,
					nodes_state: (has_changed ? 'update' : false),
					alpha: 1,
					edges: arr_matrix_edges.buffer,
					identifier: identifier_running,
					iterations: 1
				},
				[arr_matrix_nodes.buffer, arr_matrix_edges.buffer]
			);
		};
		PARENT.step = function() {};
		PARENT.resume = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning();
			const has_changed = PARENT.setRunningLayout();

			if (!is_running || identifier_running === false) {
				
				is_running = true;
				identifier_running = count_loop;
				PARENT.setSpeed(0.1);
				
				if (has_changed) {
					updateMatrixNodes();
				}

				worker.postMessage({
						action: 'resume',
						nodes: arr_matrix_nodes.buffer,
						nodes_state: (has_changed ? 'update' : 'pass'),
						alpha: 0.1,
						identifier: identifier_running,
						iterations: 1
					},
					[arr_matrix_nodes.buffer]
				);
			} else {
				
				is_running = true;
				PARENT.setSpeed(0.1);
				
				worker.postMessage({
						action: 'resume',
						alpha: 0.1,
						identifier: identifier_running,
						iterations: 1
					}
				);
			}
		};		
		PARENT.stop = function() {
			
			PARENT.layout.stop();
			
			PARENT.setRunning(false);
			PARENT.setSpeed(0);
			is_running = false;
			identifier_running = false;
			
			worker.postMessage({
					action: 'stop'
				}
			);
		};
		PARENT.close = function() {
			
			PARENT.stop();
			
			if (!worker) {
				return;
			}
			
			worker.terminate();
			worker = false;
		};
		PARENT.resize = function() {
		
			worker.postMessage({
					action: 'settings',
					settings: {
						width: size_renderer.width,
						height: size_renderer.height,
						gravity: force_options.gravity
					}
				}
			);
		};
		
		var setMatrix = function() {
			
			// Allocating Byte arrays
			let len_matrix = arr_active_nodes.length * num_properties_nodes;
			arr_matrix_nodes = new Float32Array(len_matrix);
			len_matrix = arr_active_links.length * num_properties_edges;
			arr_matrix_edges = new Float32Array(len_matrix);
						
			// Iterate through nodes
			for (let i = 0, j = 0, len = arr_active_nodes.length; i < len; i++) {
				
				const arr_node = arr_active_nodes[i];
							
				// Populating byte array
				arr_matrix_nodes[j] = arr_node.x;
				arr_matrix_nodes[j + 1] = arr_node.y;
				arr_matrix_nodes[j + 2] = arr_node.weight;
				arr_matrix_nodes[j + 3] = arr_node.fixed;
				arr_matrix_nodes[j + 4] = arr_node.count;
				
				j += num_properties_nodes;
			}
			
			// Iterate through edges
			for (let i = 0, j = 0, len = arr_active_links.length; i < len; i++) {
				
				const arr_link = arr_active_links[i];
				
				arr_matrix_edges[j] = arr_link.source.index;
				arr_matrix_edges[j + 1] = arr_link.target.index;
				arr_matrix_edges[j + 2] = arr_link.weight;
				
				j += num_properties_edges;
			}
		};
		
		var updateMatrixNodes = function() {
			
			for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
				
				const num_index = arr_matrix_nodes[i + 4];
				const arr_node = arr_loop_nodes[num_index];
				
				arr_matrix_nodes[i] = arr_node.x;
				arr_matrix_nodes[i + 1] = arr_node.y;
				arr_matrix_nodes[i + 3] = arr_node.fixed;
			}
		};
				
		var createForceWorker = function() {
			
			var func_worker = function() {
				
				var simulate = false;
				var simulate_force_links = false;
				
				var arr_nodes = [];
				var arr_active_edges = [];
				var arr_active_nodes = [];
				
				var arr_matrix_nodes = false;
				var arr_matrix_edges = false;
				
				const num_properties_nodes = 5;
				const num_properties_edges = 3;
				
				function init(arr_settings) {
					
					simulate_force_links = d3.forceLink();
					
					simulate = d3.forceSimulation()
						.nodes(arr_active_nodes)
						.force('charge', d3.forceManyBody()
							.strength(arr_settings.charge)
							.theta(arr_settings.theta)
						)
						.force('link', simulate_force_links
							.links(arr_active_edges)
							.distance(function(d) {
								return ((1 - (d.weight / arr_settings.link_weight_max))*80) + arr_settings.node_size_max;
							}))
						.force("x", d3.forceX(arr_settings.width / 2)
							.strength(arr_settings.gravity)
						)
						.force("y", d3.forceY(arr_settings.height / 2)
							.strength(arr_settings.gravity)
						)
						//.force('center', d3.forceCenter(arr_settings.width / 2, arr_settings.height / 2)
						//	.strength(arr_settings.gravity)
						//)
						.velocityDecay(arr_settings.friction)
					;
					simulate.stop();
				}
				
				function initNodes(arr_matrix_nodes_buffer) {
					
					arr_matrix_nodes = arr_matrix_nodes_buffer;
					
					for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {

						var arr_node = {
							x: arr_matrix_nodes[i],
							y: arr_matrix_nodes[i + 1],
							weight: arr_matrix_nodes[i + 2],
							fixed: arr_matrix_nodes[i + 3],
							count: arr_matrix_nodes[i + 4]
						};
						
						if (arr_node.fixed) {
							arr_node.fx = arr_node.x;
							arr_node.fy = arr_node.y;
						}
						
						arr_nodes[arr_node.count] = arr_node;
						arr_active_nodes.push(arr_node);
					}		
				}
				
				function passNodes(arr_matrix_nodes_buffer) {
					
					arr_matrix_nodes = arr_matrix_nodes_buffer;
				}
				
				function setNodes(arr_matrix_nodes_buffer, do_position) {
					
					arr_matrix_nodes = arr_matrix_nodes_buffer;
					
					arr_active_nodes = [];
					
					for (let i = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
						
						const num_count = arr_matrix_nodes[i + 4];

						var arr_node = arr_nodes[num_count];
						
						arr_node.weight = arr_matrix_nodes[i + 2];
						
						if (do_position) {
							
							arr_node.x = arr_matrix_nodes[i];
							arr_node.y = arr_matrix_nodes[i + 1];
							
							arr_node.fixed = arr_matrix_nodes[i + 3];
							if (arr_node.fixed) {
								arr_node.fx = arr_node.x;
								arr_node.fy = arr_node.y;
							} else {
								arr_node.fx = null;
								arr_node.fy = null;
							}
						}

						arr_active_nodes.push(arr_node);
					}
					
					simulate.nodes(arr_active_nodes);
				}
				
				function updateNodesByMatrixIndex(arr_node_indices) {
										
					for (let i = 0, len = arr_node_indices.length; i < len; i++) {
						
						const num_index_matrix = arr_node_indices[i];										
						const num_count = arr_matrix_nodes[num_index_matrix + 4];

						var arr_node = arr_nodes[num_count];
						
						arr_node.x = arr_matrix_nodes[num_index_matrix];
						arr_node.y = arr_matrix_nodes[num_index_matrix + 1];
						
						arr_node.fixed = arr_matrix_nodes[num_index_matrix + 3];
						if (arr_node.fixed) {
							arr_node.fx = arr_node.x;
							arr_node.fy = arr_node.y;
						} else {
							arr_node.fx = null;
							arr_node.fy = null;
						}
					}		
				}
				
				function getNodes() {
					
					for (let i = 0, j = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
					
						const arr_node = arr_active_nodes[j];
						
						arr_matrix_nodes[i] = arr_node.x;
						arr_matrix_nodes[i + 1] = arr_node.y;
						arr_matrix_nodes[i + 4] = arr_node.count;
						
						j++;
					}
					
					return arr_matrix_nodes;
				}
				
				function setEdges(arr_matrix_edges_buffer) {
					
					arr_matrix_edges = arr_matrix_edges_buffer;
					
					arr_active_edges = [];
					
					for (let i = 0, len = arr_matrix_edges.length; i < len; i += num_properties_edges) {
				
						var arr_edge = {
							source: arr_active_nodes[arr_matrix_edges[i]],
							target: arr_active_nodes[arr_matrix_edges[i + 1]],
							weight: arr_matrix_edges[i + 2]
						};
						
						arr_active_edges.push(arr_edge);
					}
					
					simulate_force_links.links(arr_active_edges);
				}

				function setConfiguration(arr_settings) {
					
					simulate.force("x", d3.forceX(arr_settings.width / 2)
							.strength(arr_settings.gravity)
						)
						.force("y", d3.forceY(arr_settings.height / 2)
							.strength(arr_settings.gravity)
						)
					;
				}
				
				function ready() {
					
					var arr_matrix_nodes_buffer = getNodes();
					
					self.postMessage(
						{
							nodes: arr_matrix_nodes_buffer.buffer,
							alpha: simulate.alpha(),
							identifier: false
						},
						[arr_matrix_nodes_buffer.buffer]
					);
				}
						
				function run(n, identifier) {
					
					for (var i = 0; i < n; i++) {
						simulate.tick();
					}
					
					var arr_matrix_nodes_buffer = getNodes();
					
					self.postMessage(
						{
							nodes: arr_matrix_nodes_buffer.buffer,
							alpha: simulate.alpha(),
							identifier: identifier
						},
						[arr_matrix_nodes_buffer.buffer]
					);
				}

				var func_listener = function(e) {
					
					const nodes_state = (e.data.nodes_state != null ? e.data.nodes_state : false);
					
					switch (e.data.action) {
						case 'init':
						
							initNodes(new Float32Array(e.data.nodes));
													
							init(e.data.settings);
							
							ready();
							break;
							
						case 'settings':

							setConfiguration(e.data.settings);
							break;
							
						case 'start':

							setNodes(new Float32Array(e.data.nodes), (nodes_state == 'update' ? true : false));
							setEdges(new Float32Array(e.data.edges));
							
							simulate.alpha(e.data.alpha);
							
							run(e.data.iterations, e.data.identifier);
							break;
							
						case 'resume':
														
							if (nodes_state == 'pass') {
								passNodes(new Float32Array(e.data.nodes));
							} else if (nodes_state == 'update') {
								setNodes(new Float32Array(e.data.nodes), true);
							}
							
							simulate.alpha(e.data.alpha);
							
							if (nodes_state) {
								run(e.data.iterations, e.data.identifier);
							}
							break;
							
						case 'loop':
						
							passNodes(new Float32Array(e.data.nodes));
							
							if (e.data.nodes_state) {
								updateNodesByMatrixIndex(e.data.nodes_state);
							}
											
							run(e.data.iterations, e.data.identifier);
							break;
								
						case 'stop':
						
							simulate.alpha(0);
							break;

						default:
					}
				};

				self.addEventListener('message', func_listener);
			};

			var worker = ASSETS.createWorker(func_worker, ['/js/support/d3-force.pack.js']);

			return worker;
		};
	};
	
	var HandleSimulationLayout = function(obj) {
		
		const PARENT = obj;
		const SELF = this;
		
		var worker = null;
		
		this.startLayoutForceAtlas2 = function() {
			
			PARENT.stop();
			
			PARENT.setRunning();
			PARENT.setRunningLayout('forceatlas2');
			PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_iterations+': 0</p>');
			
			PARENT.setSpeed(1); // Continuous
			
			const num_properties_nodes = 10;
			const num_properties_edges = 3;
			
			// Allocating Byte arrays
			let len_matrix = arr_active_nodes.length * num_properties_nodes;
			arr_matrix_nodes = new Float32Array(len_matrix);
			len_matrix = arr_active_links.length * num_properties_edges;
			arr_matrix_edges = new Float32Array(len_matrix);
			
			var count_iteration = 0;
			
			// Iterate through nodes
			for (let i = 0, j = 0, len = arr_active_nodes.length; i < len; i++) {
				
				const arr_node = arr_active_nodes[i];

				// Populating byte array
				arr_matrix_nodes[j] = arr_node.x;
				arr_matrix_nodes[j + 1] = arr_node.y;
				arr_matrix_nodes[j + 2] = 0;
				arr_matrix_nodes[j + 3] = 0;
				arr_matrix_nodes[j + 4] = 0;
				arr_matrix_nodes[j + 5] = 0;
				arr_matrix_nodes[j + 6] = arr_node.weight;
				arr_matrix_nodes[j + 7] = 1;
				arr_matrix_nodes[j + 8] = arr_node.radius;
				arr_matrix_nodes[j + 9] = arr_node.fixed;
				
				j += num_properties_nodes;
			}
			
			// Iterate through edges
			for (let i = 0, j = 0, len = arr_active_links.length; i < len; i++) {
				
				const arr_link = arr_active_links[i];
				
				arr_matrix_edges[j] = arr_link.source.index * num_properties_nodes;
				arr_matrix_edges[j + 1] = arr_link.target.index * num_properties_nodes;
				arr_matrix_edges[j + 2] = arr_link.weight;
				
				j += num_properties_edges;
			}
			
			worker = createForceAtlas2Worker();
			
			worker.postMessage({
					action: 'start',
					nodes: arr_matrix_nodes.buffer,
					edges: arr_matrix_edges.buffer,
					iterations: 1,
					settings: {
						linLogMode: forceatlas2_options.lin_log_mode,
						outboundAttractionDistribution: forceatlas2_options.outbound_attraction_distribution,
						adjustSizes: forceatlas2_options.adjust_sizes,
						edgeWeightInfluence: forceatlas2_options.edge_weight_influence,
						scalingRatio: forceatlas2_options.scaling_ratio,
						strongGravityMode: forceatlas2_options.strong_gravity_mode,
						gravity: forceatlas2_options.gravity,
						slowDown: forceatlas2_options.slow_down,
						barnesHutOptimize: (forceatlas2_options.optimize_theta > 0 ? true : false),
						barnesHutTheta: forceatlas2_options.optimize_theta
					}
				},
				[arr_matrix_nodes.buffer, arr_matrix_edges.buffer]
			);
			
			worker.addEventListener('message', function(event) {
				
				arr_matrix_nodes = new Float32Array(event.data.nodes);
				
				for (let i = 0, j = 0, len = arr_matrix_nodes.length; i < len; i += num_properties_nodes) {
					
					const arr_node = arr_active_nodes[j];
					
					arr_node.x = arr_matrix_nodes[i];
					arr_node.y = arr_matrix_nodes[i + 1];
					
					j++;
				}
				
				if (worker) {
					
					count_iteration++;
					PARENT.setRunningStatistics('<p>'+arr_labels.lbl_visualise_layout_iterations+': '+count_iteration+'</p>');
					
					worker.postMessage({
							action: 'loop',
							nodes: arr_matrix_nodes.buffer,
							iterations: 1
						},
						[arr_matrix_nodes.buffer]
					);
				}
			});
		};

		var createForceAtlas2Worker = function() {
			
			var func_worker = function() {
		
				var forceatlas2 = new LayoutForceAtlas2();
				
				function run(n) {
					
					for (var i = 0; i < n; i++) {
						forceatlas2.pass();
					}
					
					arr_matrix_nodes = forceatlas2.getNodes();
					
					self.postMessage(
						{nodes: arr_matrix_nodes.buffer},
						[arr_matrix_nodes.buffer]
					);
				}

				var func_listener = function(e) {
					
					switch (e.data.action) {
						case 'start':
						
							forceatlas2.init(
								new Float32Array(e.data.nodes),
								new Float32Array(e.data.edges),
								e.data.settings
							);

							run(e.data.iterations);
							break;

						case 'loop':
						
							forceatlas2.setNodes(new Float32Array(e.data.nodes));
							
							run(e.data.iterations);
							break;

						case 'settings':

							forceatlas2.setConfiguration(e.data.settings);
							break;

						default:
					}
				};

				self.addEventListener('message', func_listener);
			};

			var worker = ASSETS.createWorker(func_worker, ['/js/LayoutForceAtlas2.js']);

			return worker;
		};
		
		this.stop = function() {
			
			if (!worker) {
				return false;
			}
			
			worker.terminate();
			
			worker = null;
			arr_matrix_nodes = null;
			arr_matrix_edges = null;
			
			return true;
		};
	}
		
	var interact = function() {
		
		const pos_hover = PARENT.obj_map.getMousePosition();
			
		if (!pos_hover) {
			
			if (pos_hover_poll) {
				
				pos_hover_poll = false;
				hoverNode(false, false);
				cur_node_id = false;
				elm_host.classList.remove('hovering');
			}
		} else {
						
			const x_point = pos_hover.x;
			const y_point = pos_hover.y;

			if (!pos_hover_poll || (Math.abs(x_point-pos_hover_poll.x) > 0 || Math.abs(y_point-pos_hover_poll.y) > 0)) {
			
				pos_hover_poll = pos_hover;
				let is_hovering = false;

				for (let i = 0, len = arr_active_nodes.length; i < len; i++) {
					
					const arr_node = arr_active_nodes[i];
					const dx = (((arr_node.x + pos_translation.x) * num_scale) + (stage.position ? stage.position.x : '')) - pos_hover_poll.x;
					const dy = (((arr_node.y + pos_translation.y) * num_scale) + (stage.position ? stage.position.y : '')) - pos_hover_poll.y;
					
					const distance_squared = dx * dx + dy * dy;
					const radius_squared = ((arr_node.radius + width_node_stroke + 2) * num_scale) * ((arr_node.radius + width_node_stroke + 2) * num_scale);

					if (distance_squared < radius_squared) {
						
						is_hovering = true;
						
						if (cur_node_id !== arr_node.id) {
								
							hoverNode(arr_node, true);
							cur_node_id = arr_node.id;
							elm_host.classList.add('hovering');
						}
					}
				}
				
				if (cur_node_id !== false && !is_hovering) {
					
					hoverNode(false, false);
					cur_node_id = false;
					elm_host.classList.remove('hovering');
				}
			}
		}	
    }

	var rePosition = function(move, pos, zoom, calc_zoom) {

		var width = pos.size.width;
		var height = pos.size.height;

		if (calc_zoom) { // Zoomed related
			
			if (display == DISPLAY_PIXEL) {
				
				if (typeof calc_zoom == 'string') { // Zoom in/out
					
					const num_zoom = parseInt(calc_zoom);
					
					if (num_zoom < 0) {
						num_scale = num_scale * Math.pow(0.7, Math.abs(num_zoom));
					} else if (num_zoom > 0) {
						num_scale = num_scale * Math.pow(1.4286, num_zoom);
					}
				}
			} else {
				num_scale = width/size_init.width;
			}

			do_redraw = true;
		}
		
		if (display == DISPLAY_PIXEL) {
			
			if (width != size_renderer.width || height != size_renderer.height) {
				
				do_redraw = true;
				do_draw = true;
					
				size_renderer.resolution = pos.render.resolution;
				
				renderer.resize(width, height);
				renderer.resolution = size_renderer.resolution;
				renderer_2.resize(width, height);
				renderer_2.resolution = size_renderer.resolution;
				
				elm_canvas.width = width;
				elm_canvas.height = height;	
								
				geometry_shader.uniforms.u_bounds = [width, height];	
				
				simulation.resize();
				simulation.resume();
			}
			
			if (move === true) { // Move Starts
				
				pos_move.x = pos.x;
				pos_move.y = pos.y;
				is_dragging = true;
			}
			if (move !== false && is_dragging && !is_dragging_node) { // Moving...
				
				pos_translation.x = pos_translation.x - ((pos_move.x - pos.x) / num_scale);
				pos_translation.y = pos_translation.y - ((pos_move.y - pos.y) / num_scale);
				
				geometry_shader.uniforms.u_translation = [pos_translation.x, pos_translation.y];

				pos_move.x = pos.x;
				pos_move.y = pos.y;
			}
			if (move === false && is_dragging) { // Move Ends
				is_dragging = false;
			}
			
			if (typeof calc_zoom == 'string') { // Zoom in/out
				
				stage.position.x = (width - (width * num_scale)) / 2;
				stage_2.position.x = (width - (width * num_scale)) / 2;
				
				stage.position.y = (height - (height * num_scale)) / 2;
				stage_2.position.y = (height - (height * num_scale)) / 2;

				geometry_shader.uniforms.u_stagetranslation = [stage.position.x, stage.position.y];
				geometry_shader.uniforms.u_scale = [num_scale, num_scale];
			}
		} else {
				
			drawer.style.width = width+'px';
			drawer.style.height = height+'px';
			
			svg_group.setAttribute('transform', 'scale('+num_scale+')');
		}
		
		size_renderer.width = width;
		size_renderer.height = height;

		if (!in_first_run) {	
			drawTick();
		}
	};
	
	var createLinkElms = function() {	
		
		if (!show_line) {
			return;
		}
		
		if (display == DISPLAY_PIXEL) {

			buffer_geometry_lines_position = new PIXI.Buffer(new Float32Array(arr_loop_links.length * length_geometry_lines_position));
			//buffer_geometry_lines_normal = new PIXI.Buffer(new Float32Array(arr_loop_links.length * length_geometry_lines_position));
			buffer_geometry_lines_color = new PIXI.Buffer(new Float32Array(arr_loop_links.length * length_geometry_lines_color));

			geometry_lines.addAttribute('a_position', buffer_geometry_lines_position, 2);
			//geometry_lines.addAttribute('a_normal', buffer_geometry_lines_normal, 2);
			geometry_lines.addAttribute('a_color', buffer_geometry_lines_color, 4);
			
			geometry_mesh = new PIXI.Mesh(geometry_lines, geometry_shader);
			geometry_mesh.blendMode = PIXI.BLEND_MODES.NORMAL_NPM; // Not pre-multiplied alpha
			
			elm_plot_lines.addChild(geometry_mesh);
		} else {
					
			for (var i = 0, len = arr_loop_links.length; i < len; i++) {
				
				var arr_link = arr_loop_links[i];
				
				arr_link.elm = stage.createElementNS(stage_ns, 'path');
				svg_group.appendChild(arr_loop_links[i].elm);
				
				arr_link.elm.setAttribute('fill', 'none');
				arr_link.elm.setAttribute('stroke', arr_loop_links[i].color);
				arr_link.elm.setAttribute('stroke-width', width_line_min+'px');
				
				if (show_arrowhead) {
					arr_link.elm.setAttribute('marker-end', 'url(#end)');
				}
			}
		}
	};
	
	var drawNodeElm = function(arr_node) {
			
		let elm = arr_node.elm;
		let do_highlight = false;
		let str_identifier = '';
			
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
				arr_node.color = color_node;
			}
		}
		
		var color = arr_node.color;
		
		if (arr_node.highlight_color) {
			
			color = arr_node.highlight_color;
			
			do_highlight = true;
			arr_node.highlight_color = false;
		}
		
		str_identifier += color;
	
		if (!do_highlight && arr_node.has_conditions) {
			
			// update conditioned colors and weight
			handleConditions(arr_node);
			
			str_identifier += arr_node.identifier_condition;
		}
		
		const num_count_links = (arr_node.count_in + arr_node.count_out);
		
		if (is_weighted) {

			let num_weight_conditions = arr_node.weight_conditions;
			
			if (size_node_start && num_weight_conditions < size_node_start) {
				num_weight_conditions = size_node_start;
			}	
			
			if (size_node_stop && num_weight_conditions > size_node_stop) {
				num_weight_conditions = size_node_stop;
			}		
			
			arr_node.weight = num_weight_conditions;
		} else {
			
			arr_node.weight = num_count_links;
		}
		
		let num_size = 0;
		
		if (num_count_links == 0 && !show_disconnected_node) {
			
			num_size = 0;
		} else if (!arr_node.is_alive) { // Set Radius to 0 when node has no position (has been removed when it fell out of the selection)
			
			num_size = (show_disconnected_node ? size_node_min : 0);
		} else {
			
			if (arr_node.weight == 0) {
				
				if (show_disconnected_node) {
					num_size = size_node_min;
				}
			} else {
				
				if (num_node_weight_max == num_node_weight_min) {
					num_size = size_node_max;
				} else {
				
					const num_weight_ratio = ((arr_node.weight - num_node_weight_min) / (num_node_weight_max - num_node_weight_min));
					num_size = (size_node_min + (num_weight_ratio * (size_node_max - size_node_min)));
				}
			}
		}
		
		const num_size_radius = (num_size / 2); // Diameter to radius
		
		str_identifier += num_size_radius+'-'+num_scale;
		
		if (str_identifier == arr_node.identifier) {

			if (display == DISPLAY_PIXEL) {
				elm.visible = true;
				if (arr_node.show_text) {
					arr_node.elm_text.visible = true;
				}
			} else {
				elm.dataset.visible = 1;
			}
		} else {

			arr_node.radius = Math.round(num_size_radius + width_node_stroke);

			if (display == DISPLAY_PIXEL) {
				
				pos_elm = false;
							
				if (!elm) {
					
					if (arr_node.has_conditions) {
						
						elm = new PIXI.Container();
					} else {
						
						elm = new PIXI.Graphics();
					}
					
					arr_node.elm = elm;
					elm_plot_dots.addChild(elm);
					
					if (arr_node.show_text) {
						
						const elm_text = new PIXI.Text(arr_node.name_text, {fontSize: size_text, fontFamily: font_family});
						
						elm_text.anchor.x = 0;
						elm_text.anchor.y = 0.5;
									
						elm_plot_info.addChild(elm_text);

						arr_node.elm_text = elm_text;
					}
				} else {

					if (arr_node.has_conditions) {
						
						elm.children = [];
					} else {
						
						elm.clear();
					}
					
					elm.visible = true;
					
					if (arr_node.elm_text) {
						arr_node.elm_text.visible = arr_node.show_text;
					}

					pos_elm = elm.position;
				}
				
				if (num_size_radius) {
					
					const num_size_radius_scaled = num_size_radius * num_scale;
					const num_width_stroke_scaled = width_node_stroke * num_scale;
					
					if (arr_node.has_conditions) {
						
						if (!show_icon_as_node) {
							
							let elm_stroke = new PIXI.Graphics();
							elm_stroke.lineStyle(num_width_stroke_scaled, parseColor(color_node_stroke), 1);
							
							if (arr_node.colors === false) {
								
								elm_stroke.beginFill(parseColor(color), 1);
								elm_stroke.drawCircle(0, 0, num_size_radius_scaled + num_width_stroke_scaled / 2);
								elm_stroke.endFill();
							} else {
								
								elm_stroke.drawCircle(0, 0, num_size_radius_scaled + num_width_stroke_scaled / 2);
								
								if (do_highlight) {
									arr_node.colors = [{color: color, portion: 1}];
								}

								let num_current_portion = 0; 
								
								for (let i = 0; i < arr_node.colors.length; i++) {
									
									const num_start = num_current_portion * 2 * Math.PI;
									num_current_portion = num_current_portion + arr_node.colors[i].portion;
									const num_end = num_current_portion * 2 * Math.PI;
									
									const elm_portion = new PIXI.Graphics();
									elm_portion.beginFill(parseColor(arr_node.colors[i].color), 1);
									elm_portion.moveTo(0, 0)
										.lineTo(num_size_radius_scaled * Math.cos(num_start), num_size_radius_scaled * Math.sin(num_start))
										.arc(0, 0, num_size_radius_scaled, num_start, num_end, false)
										.lineTo(0, 0);
									elm_portion.endFill();
									
									elm.addChild(elm_portion);
								}
							}
							
							elm.addChild(elm_stroke);
						}
		
						if (arr_node.icons !== false && arr_node.icons.length) {
							
							let elms_icon = null;
							let num_height_max = 0;
							let num_width_sum = 0;
							const num_size_icon_scaled = (show_icon_as_node ? num_size_radius_scaled * 2 : num_size_dot_icons * num_scale);
							
							for (let i = 0, len = arr_node.icons.length; i < len; i++) {
								
								const resource = arr_node.icons[i].resource;
								const arr_resource = arr_assets_texture_icons[resource];

								const elm_icon = new PIXI.Sprite(arr_resource.texture);
								const num_scale_icon = (arr_resource.width / arr_resource.height);
								
								const num_height_icon = (do_node_icons_weight ? ((num_size_radius_scaled * 2) * (arr_node.icons[i].weight / arr_node.weight_conditions)) : num_size_icon_scaled);
								
								const num_width_icon = (num_height_icon * num_scale_icon);
								elm_icon.height = num_height_icon;
								elm_icon.width = num_width_icon;
								
								if (i == 0) { // First icon is largest
									num_height_max = num_height_icon;
								}
								if (i > 0) {
									num_width_sum += num_spacer_dot_icons;
								}
								elm_icon.position.x = num_width_sum;
								elm_icon.position.y = ((num_height_max - num_height_icon) / 2);
								num_width_sum += num_width_icon;

								if (i == 0) {
									
									if (len > 1) {
										
										elms_icon = new PIXI.Container();
										elms_icon.addChild(elm_icon);
									} else {
										
										elms_icon = elm_icon;
									}
								} else {
									
									elms_icon.addChild(elm_icon);
								}
							}
							
							let num_offset = 0;
							
							if (show_icon_as_node) {
								
								num_offset = -(num_height_max / 2);
							} else {
								
								if (num_offset_dot_icons == 0) {
									num_offset = -(num_height_max / 2);
								} else if (num_offset_dot_icons < 0) {
									num_offset = (-(num_size_radius_scaled + num_width_stroke_scaled) + (num_offset_dot_icons * num_scale) - num_height_max);
								} else {
									num_offset = (num_size_radius_scaled + num_width_stroke_scaled + (num_offset_dot_icons * num_scale));
								}
							}
							
							elms_icon.position.x = Math.floor(-(num_width_sum / 2));
							elms_icon.position.y = Math.floor(num_offset);
							
							elm.addChild(elms_icon);
						}
					} else {
					
						elm.lineStyle(num_width_stroke_scaled, parseColor(color_node_stroke), 1);
						elm.beginFill(parseColor(color), 1);
						elm.drawCircle(0, 0, num_size_radius_scaled + num_width_stroke_scaled / 2);
						elm.endFill();
					}
					
					if (pos_elm) {
						
						elm.position = pos_elm;
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
				
				let elm_circle = null;
				
				if (!elm) {
					
					elm = stage.createElementNS(stage_ns, 'g');
					svg_group.appendChild(elm);
					
					if (arr_node.show_text) {
						
						const elm_text = stage.createElementNS(stage_ns, 'text');
						elm_text.style.fontSize = size_text+'px';
						elm_text.style.fontFamily = font_family;
						elm_text.style.fill = color_text;
						elm_text.style.dominantBaseline = 'central';
						const node_text = stage.createTextNode(arr_node.name_text);
						elm_text.appendChild(node_text);
						elm.appendChild(elm_text);
						
						arr_node.elm_text = elm_text;
					}
				
					elm_circle = stage.createElementNS(stage_ns, 'circle');
					elm.appendChild(elm_circle);
					elm_circle.setAttribute('stroke', parseColor(color_node_stroke));
					elm_circle.setAttribute('stroke-width', width_node_stroke);
					
					arr_node.elm = elm;
				} else {
					
					const elms_circle = elm.getElementsByTagName('circle');
					elm_circle = elms_circle[0];
					
					elm.dataset.visible = 1;
					
					if (arr_node.elm_text) {
						arr_node.elm_text.dataset.visible = (arr_node.show_text ? 1 : 0);
					}
				}
				
				elm.dataset.node_id = arr_node.id;
				
				if (arr_node.has_conditions) {
				
					// Remove possible previous pie and icons
					const elm_paths = arr_node.elm.getElementsByTagName('g');
					
					while (elm_paths.length) {
						arr_node.elm.removeChild(elm_paths[elm_paths.length - 1]);
					}
					
					if (arr_node.colors !== false && !do_highlight) {
						
						if (arr_node.colors.length == 1) {
							
							color = arr_node.colors[0].color;
						} else {
							
							color = 'none';
							
							if (!show_icon_as_node) {

								let num_current_portion = 0; 
								const num_x = 0;
								const num_y = 0;
								const elms_pie = stage.createElementNS(stage_ns, 'g');
			
								for (let i = 0; i < arr_node.colors.length; i++) {

									const num_start = num_current_portion * 2 * Math.PI;
									num_current_portion = num_current_portion + arr_node.colors[i].portion;
									const num_end = num_current_portion * 2 * Math.PI;

									const elm_path = stage.createElementNS(stage_ns, 'path');
									
									elm_path.setAttribute('d','M '+Math.floor(num_x)+','+Math.floor(num_y)+' L '+(Math.floor(num_x) + num_size_radius * Math.cos(num_start))+','+(Math.floor(num_y) + num_size_radius * Math.sin(num_start))+' A '+num_size_radius+','+num_size_radius+' 0 '+(num_end - num_start < Math.PI ? 0 : 1)+',1 '+(Math.floor(num_x) + num_size_radius * Math.cos(num_end))+','+(Math.floor(num_y) + num_size_radius * Math.sin(num_end))+' z');
									elm_path.style.fill = arr_node.colors[i].color;
									
									elms_pie.appendChild(elm_path);
								}
								
								arr_node.elm.appendChild(elms_pie);
							}
						}
					}
						
					if (arr_node.icons !== false && arr_node.icons.length) {
						
						const elms_icon = stage.createElementNS(stage_ns, 'g');
						let num_height_max = 0; // First icon is largest
						let num_width_sum = 0;
						const num_size_icon = (show_icon_as_node ? num_size_radius * 2 : num_size_dot_icons);
						
						for (let i = 0, len = arr_node.icons.length; i < len; i++) {
						
							const resource = arr_node.icons[i].resource;
							const arr_resource = ASSETS.getMedia(resource);

							const elm_icon = stage.createElementNS(stage_ns, 'image');
							elm_icon.setAttribute('href', arr_resource.resource);
							const num_scale_icon = (arr_resource.width / arr_resource.height);
							
							const num_height_icon = (do_node_icons_weight ? ((num_size_radius * 2) * (arr_node.icons[i].weight / arr_node.weight_conditions)) : num_size_icon);
							
							const num_width_icon = num_height_icon * num_scale_icon;
							elm_icon.setAttribute('height', num_height_icon);
							elm_icon.setAttribute('width', num_width_icon);
							
							if (i == 0) { // First icon is largest
								num_height_max = num_height_icon;
							}
							if (i > 0) {
								num_width_sum += num_spacer_dot_icons;
							}
							elm_icon.setAttribute('x', num_width_sum);
							elm_icon.setAttribute('y', ((num_height_max - num_height_icon) / 2));
							num_width_sum += num_width_icon;

							elms_icon.appendChild(elm_icon);
						}
						
						let num_offset = 0;
						
						if (show_icon_as_node) {
							
							num_offset = -(num_height_max / 2);
						} else {
							
							if (num_offset_dot_icons == 0) {
								num_offset = -(num_height_max / 2);
							} else if (num_offset_dot_icons < 0) {
								num_offset = (-(num_size_radius + width_node_stroke) + num_offset_dot_icons - num_height_max);
							} else {
								num_offset = (num_size_radius + width_node_stroke + num_offset_dot_icons);
							}
						}
						
						elms_icon.setAttribute('transform', 'translate('+(-(num_width_sum / 2))+' '+(num_offset)+')');
						
						arr_node.elm.appendChild(elms_icon);
					}
				}

				if (arr_node.icons !== false && arr_node.icons.length && show_icon_as_node) {

					elm_circle.setAttribute('r', 0);
				} else {
					
					elm_circle.setAttribute('r', (num_size_radius ? (num_size_radius + width_node_stroke / 2) : 0));
				}
				
				elm_circle.setAttribute('fill', color);
				
				if (arr_node.elm_text) {
					
					if (num_size_radius) {
						
						arr_node.elm_text.setAttribute('opacity', 1);		
						arr_node.elm_text.setAttribute('dx', (num_size_radius + width_node_stroke) + num_offset_dot_text);
					} else {
						
						arr_node.elm_text.setAttribute('opacity', 0);
					}
				}
			}
		}
		
		arr_node.color = false;
		arr_node.redraw_node = false;
		arr_node.identifier = str_identifier;
	}
	
	var handleConditions = function(arr_node) {

		// Node size is based on amount of links
		// One link can set multiple colours
		// One part is relative to total amount of parts (i.e. links)
		// One part may contain multiple colours
		// Grouped later by colour
		
		let has_part_condition_object = 0;
		let num_parts_total = 0;
		
		if (arr_node.is_alive) {
			
			// Do we need one part for conditions generated by the object itself? Based on own object or sub-object conditions
			
			// Conditions based on object			
			if (arr_node.conditions.object.length) {
					
				has_part_condition_object = 1;
			} else {
				
				// Conditions based on object subs
				for (let i = 0, len = arr_node.conditions.object_sub.length; i < len; i++) {
					
					const arr_condition = arr_node.conditions.object_sub[i];
				
					if (arr_object_subs_children[arr_condition.source_id].is_active) {
						
						has_part_condition_object = 1;
						break;
					}
				}
			}
			
			// Total number of parts is based on all incoming relations (that can generate cross-referenced conditions) and one part for object conditions
			num_parts_total = has_part_condition_object + arr_node.count_in;
		}
		
		// If no parts are there, return.
		if (!num_parts_total) {
			
			arr_node.weight_conditions = 1;
			arr_node.colors = false;
			arr_node.icons = false;
			arr_node.show_text = arr_node.has_text_threshold;
			arr_node.identifier_condition = '';
			
			return;
		}
		
		let str_identifier = '';
		let arr_condition_colors = [];
		let arr_condition_icons = [];
		let arr_parts = {};
		let arr_colors_group = {};
		let arr_icons_group = [];
		let num_parts_condition_referenced = 0;
		let num_parts_self = 0;
		let num_weight_conditions = 0;
		let do_show_text = null; 
		
		// Cross referenced conditions based on referenced object definitions
		
		for (let i = 0, len = arr_node.conditions.object_parent.length; i < len; i++) {

			const arr_condition = arr_node.conditions.object_parent[i];

			if (arr_node.object_parents[arr_condition.source_id] === false) {
				continue;
			}
			
			const arr_object_definition = arr_data.objects[arr_condition.source_id].object_definitions[arr_condition.source_definition_id];
						
			if (arr_parts['o_'+arr_condition.source_id] === undefined) {
				arr_parts['o_'+arr_condition.source_id] = {colors: []};
				num_parts_condition_referenced++;
			}
			
			if (arr_condition.color !== null) {
				arr_parts['o_'+arr_condition.source_id].colors.push([arr_condition.color, arr_condition.weight]);
			}
			
			if (arr_object_definition.style.weight !== null) {
				num_weight_conditions += arr_condition.weight;
			}
			
			if (arr_condition.icon !== null) {
				arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
			}
			
			if (label_condition !== false) {
				do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
			}
		}
		
		// Cross referenced conditions based on referenced sub object definitions
			
		for (let i = 0, len = arr_node.conditions.object_sub_parent.length; i < len; i++) {
			
			const arr_condition = arr_node.conditions.object_sub_parent[i];
			
			if (arr_node.object_sub_parents[arr_condition.source_id] === false) {
				continue;
			}
			
			const arr_object_sub = arr_data.object_subs[arr_condition.source_id];
			const arr_object_sub_definition = arr_object_sub.object_sub_definitions[arr_condition.source_definition_id]
			const object_id = arr_object_sub.object_id; // Need object_id as identifier for the part as sub object references can be multiple
			
			if (arr_parts['s_'+object_id] === undefined) {
				arr_parts['s_'+object_id] = {colors: []};
				num_parts_condition_referenced++;
			}
			
			if (arr_condition.color !== null) {
				arr_parts['s_'+object_id].colors.push([arr_condition.color, arr_condition.weight]);
			}
			
			if (arr_object_sub_definition.style.weight !== null) {
				num_weight_conditions += arr_condition.weight;
			}
			
			if (arr_condition.icon !== null) {
				arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
			}
			
			if (label_condition !== false) {
				do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
			}
		}

		if (has_part_condition_object) {
			
			arr_parts.object = {colors: []};
				
			// Conditions based on object
			for (let i = 0, len = arr_node.conditions.object.length; i < len; i++) {
				
				const arr_condition = arr_node.conditions.object[i];
				const arr_object = arr_data.objects[arr_node.id];
								
				if (arr_condition.color !== null) {
					arr_parts.object.colors.push([arr_condition.color, arr_condition.weight]);
				}
				
				if (arr_object.style.weight !== null) {
					num_weight_conditions += arr_condition.weight;
				}
				
				if (arr_condition.icon !== null) {
					arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
				}
				
				if (label_condition !== false) {
					do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
				}
			}

			// Conditions based on object subs
			
			for (let i = 0, len = arr_node.conditions.object_sub.length; i < len; i++) {
				
				const arr_condition = arr_node.conditions.object_sub[i];
				
				if (!arr_object_subs_children[arr_condition.source_id].is_active) {
					continue;
				}
				
				const arr_object_sub = arr_data.object_subs[arr_condition.source_id];
				
				if (arr_condition.color !== null) {
					arr_parts.object.colors.push([arr_condition.color, arr_condition.weight]);
				}
				
				if (arr_object_sub.style.weight !== null) {
					num_weight_conditions += arr_condition.weight;
				}
				
				if (arr_condition.icon !== null) {
					arr_icons_group.push([arr_condition.icon, arr_condition.weight]);
				}
				
				if (label_condition !== false) {
					do_show_text = (do_show_text === true || arr_condition.identifier === label_condition);
				}
			}
		}
		
		num_parts_self = (num_parts_total - num_parts_condition_referenced);
		
		for (const part_id in arr_parts) {
			
			const arr_part = arr_parts[part_id];
			const num_part_portion_total = arr_part.colors.reduce(function(num_sum, arr) { return num_sum + arr[1]; }, 0); // arr[1] = weight
			
			let num_part_percentage = 0;
			if (part_id == 'object') {
				num_part_percentage = (num_parts_self / num_parts_total);
			} else {
				num_part_percentage = (1 / num_parts_total);
			}
			const num_part_portion_percentage = (num_part_percentage / num_part_portion_total);
			
			for (let i = 0, len = arr_part.colors.length; i < len; i++) {
				
				const arr_part_color = arr_part.colors[i];
				const str_color = arr_part_color[0];
				
				if (arr_colors_group[str_color] === undefined) {
					
					arr_colors_group[str_color] = {color: str_color, portion: 0};
					arr_condition_colors.push(arr_colors_group[str_color]);
				}
				
				arr_colors_group[str_color].portion += (arr_part_color[1] * num_part_portion_percentage);
			}
		}	

		if (!has_part_condition_object && num_parts_total > num_parts_condition_referenced) {

			if (arr_colors_group[arr_node.color] === undefined) {
				
				arr_colors_group[arr_node.color] = {color: arr_node.color, portion: 0};
				arr_condition_colors.push(arr_colors_group[arr_node.color]);
			}
			
			arr_colors_group[arr_node.color].portion += (num_parts_self / num_parts_total);	
		}

		if (!arr_condition_colors.length) {
			
			arr_condition_colors.push({color: arr_node.color, portion: 1});
			
			str_identifier += arr_node.color;
		} else {
			
			for (let i = 0, len = arr_condition_colors.length; i < len; i++) {
				str_identifier += arr_condition_colors[i].color+arr_condition_colors[i].portion;
			}
		}
		
		if (arr_icons_group.length) {
			
			arr_condition_icons = {};
			
			for (let i = 0, len = arr_icons_group.length; i < len; i++) {
				
				const str_icon_value = arr_icons_group[i][0];
				
				let arr_icon_group = arr_condition_icons[str_icon_value];
				
				if (arr_icon_group === undefined) {
					
					arr_condition_icons[str_icon_value] = {weight: 0, resource: str_icon_value};
					arr_icon_group = arr_condition_icons[str_icon_value];
				}
				
				arr_icon_group.weight += arr_icons_group[i][1];
				
				str_identifier += str_icon_value;
			}
			
			arr_condition_icons = Object.values(arr_condition_icons);
			
			arr_condition_icons.sort(function(a, b) { return b.weight - a.weight }); // Order by size, descending
		}

		arr_node.weight_conditions = 1 + num_weight_conditions;
		arr_node.colors = arr_condition_colors;
		arr_node.icons = arr_condition_icons;
		arr_node.show_text = (do_show_text !== null ? do_show_text : arr_node.has_text_threshold);
		arr_node.identifier_condition = str_identifier;
	}
	
	var drawTick = function() {
		
		if (do_redraw) { // New draw
			
			// Prepare asset tracking
			pos_hover_poll = false;
		}

		// Redraw Links
		if (show_line) {
			
			const do_size_line = (width_line_max != width_line_min && num_link_weight_max != num_link_weight_min);
			
			for (let i = 0, len = arr_active_links.length; i < len; i++) {
				
				const arr_active_link = arr_active_links[i];
				
				let num_line_width = width_line_min;
				
				if (do_size_line) {
					
					const num_weight_ratio = ((arr_active_link.weight - num_link_weight_min) / (num_link_weight_max - num_link_weight_min)); // Correct the range with the minimum weight to get a 0-1 ratio
					num_line_width = (width_line_min + (num_weight_ratio * (width_line_max - width_line_min)));
				}
				
				if (display == DISPLAY_PIXEL) {
							
					const num_arr_offset = arr_active_link.count * length_geometry_lines_position;
					
					const node_source = arr_active_link.source;
					const node_target = arr_active_link.target;
					
					const dx = node_target.x - node_source.x;
					const dy = node_target.y - node_source.y;
					const dl = Math.sqrt(dx * dx + dy * dy);
					const dx_normalised = (dx / dl);
					const dy_normalised = (dy / dl);
					
					const num_width = (num_line_width / 2);
					const num_offset = (arr_active_link.has_reverse ? num_width : 0);
					const num_source_x = node_source.x + ((dy / dl) * num_offset);
					const num_source_y = node_source.y + ((-dx / dl) * num_offset);
					const num_target_x = node_target.x + ((dy / dl) * num_offset);
					const num_target_y = node_target.y + ((-dx / dl) * num_offset);

					buffer_geometry_lines_position.data[num_arr_offset + 0] = num_source_x + (-dy_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 1] = num_source_y + (dx_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 2] = num_source_x + (dy_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 3] = num_source_y + (-dx_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 4] = num_target_x + (-dy_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 5] = num_target_y + (dx_normalised * num_width);
					
					buffer_geometry_lines_position.data[num_arr_offset + 6] = num_target_x + (-dy_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 7] = num_target_y + (dx_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 8] = num_target_x + (dy_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 9] = num_target_y + (-dx_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 10] = num_source_x + (dy_normalised * num_width);
					buffer_geometry_lines_position.data[num_arr_offset + 11] = num_source_y + (-dx_normalised * num_width);
				} else {
					
					if (arr_active_link.elm) {
						
						const num_offset_radius = 2.5; // Lower means more arc
						const dx = arr_active_link.target.x - arr_active_link.source.x;
						const dy = arr_active_link.target.y - arr_active_link.source.y;
						const dr = Math.sqrt(dx * dx + dy * dy) * num_offset_radius;
						
						arr_active_link.elm.setAttribute('d', 'M' + arr_active_link.source.x + ',' + arr_active_link.source.y + 'A' + dr + ',' + dr + ' 1,0,0 ' + arr_active_link.target.x + ',' + arr_active_link.target.y);
						
						if (do_size_line) {
							arr_active_link.elm.setAttribute('stroke-width', num_line_width+'px');
						}
						
						// Show previously hidden link after it has received its new position
						if (arr_active_link.action == 'show') {
							arr_active_link.elm.dataset.visible = 1;
							arr_active_link.action = false;
						}
					}
				}
			}
		}
		
		// Redraw Nodes
		for (let i = 0, len = arr_active_nodes.length; i < len; i++) {
			
			const arr_node = arr_active_nodes[i];			
			const num_size_radius = arr_node.radius;
			const pos_x = arr_node.x;
			const pos_y = arr_node.y;
			
			if (arr_node.redraw_node || (do_redraw && display == DISPLAY_PIXEL)) {
				drawNodeElm(arr_node);
			}
						
			if (display == DISPLAY_PIXEL) {

				arr_node.elm.position.x = (pos_x + pos_translation.x) * num_scale;
				arr_node.elm.position.y = (pos_y + pos_translation.y) * num_scale;
				
				if (arr_node.elm_text) {
					arr_node.elm_text.position.x = arr_node.elm.position.x + (num_size_radius * num_scale) + num_offset_dot_text;
					arr_node.elm_text.position.y = arr_node.elm.position.y;
				}
			} else {
				
				arr_node.elm.setAttribute('transform', 'translate(' + pos_x + ',' + pos_y + ')');
			}
		}
		
		if (display == DISPLAY_PIXEL) {
			
			if (show_line) {
			
				buffer_geometry_lines_position.update();
				//buffer_geometry_lines_normal.update();
				
				if (do_update_geometry_lines_color) {
					
					buffer_geometry_lines_color.update();
					do_update_geometry_lines_color = false;
				}
			}
			
			renderer.render(stage);
			renderer_2.render(stage_2);
		}
		
		do_redraw = false;
	};
		
	var addListeners = function () {
				
		const elm_legends = PARENT.elm_controls.find('.legends');
		elm_search_container = $('<figure />').addClass('search-nodes').appendTo(elm_legends);
				
		const search_input = $('<input type="search" />').appendTo(elm_search_container),
		elm_dropdown = $('<ul />').addClass('dropdown hide').appendTo(elm_search_container);
		
		search_input[0].addEventListener('focus', function() {
			
			searchNodes();
		});
		
		search_input[0].addEventListener('keyup', function() {
			
			searchNodes();
		});
		
		var searchNodes = function() {
			
			const cur_input = search_input.val();
			const max_results = 10;
			const arr_results = [];
			elm_dropdown.empty().removeClass('hide');
			
			for (let i = 0; i < arr_active_nodes.length; i++) {
				
				if (arr_active_nodes[i].name && arr_active_nodes[i].name.toLowerCase().indexOf(cur_input.toLowerCase()) > -1) {
					
					cur_total = (arr_active_nodes[i].count_object_sub_parents + arr_active_nodes[i].count_object_parents);
					arr_results.push({cur_total: cur_total, name: arr_active_nodes[i].name, id: arr_active_nodes[i].id});
				}
			}
			
			if (arr_results.length > 0) {
				
				arr_results.sort(function(a, b) {
					return parseFloat(b.cur_total) - parseFloat(a.cur_total);
				});
				
				for (let i = 0; i < (arr_results.length > max_results ? max_results : arr_results.length); i++) {
					
					const elm_li = $('<li />').appendTo(elm_dropdown);
					const elm_result = $('<a>'+arr_results[i].name+'</a>').appendTo(elm_li);
					
					elm_result[0].dataset.node_id = arr_results[i].id;
					
					elm_result[0].addEventListener('click', function() {
						
						hoverNode(arr_nodes[this.dataset.node_id], true);
						elm_dropdown.addClass('hide');
					});
					
					elm_result[0].addEventListener('mouseenter', function() {
						
						hoverNode(arr_nodes[this.dataset.node_id], true);
						$(this).addClass('active'); 
					});
					
					elm_result[0].addEventListener('mouseleave', function() {
						
						hoverNode(false, false);
						$(this).removeClass('active'); 
					});
				}
			} else {
				
				elm_dropdown.append('<li><a>No results.</a></li>');
			}
		};
		
		elm_dropdown[0].addEventListener('mouseleave', function() {
			
			elm_dropdown.addClass('hide');
			search_input.blur();
		});
		
		elm_layout = $('<figure class="run-layout"></figure>').appendTo(elm_legends);
		elm_layout_statistics = $('<div></div>').appendTo(elm_layout);
		elm_layout_status = $('<div></div>').appendTo(elm_layout);
		elm_layout_select = $('<select><option value="">D3 Force</option><option value="forceatlas2">ForceAtlas2</option></select>').appendTo(elm_layout);
		const elm_layout_run = $('<button type="button"><span class="icon"></span></button>').appendTo(elm_layout);
		const elm_layout_stop = $('<button type="button"><span class="icon"></span></button>').appendTo(elm_layout);
				
		ASSETS.getIcons(elm_host, ['play', 'stop'], function(data) {
			elm_layout_run[0].children[0].innerHTML = data.play;
			elm_layout_stop[0].children[0].innerHTML = data.stop;
		});
			
		elm_layout_run[0].addEventListener('click', function(e) {
			
			if (!simulation) {
				return;
			}
			
			const str_algorithm = elm_layout_select.val();
			
			switch (str_algorithm) {
				case 'forceatlas2':
					simulation.layout.startLayoutForceAtlas2();
					do_draw = true;
					break;
				case '':
				default:
					simulation.resume();
					do_draw = true;
					break;
			}
		});
		elm_layout_stop[0].addEventListener('click', function(e) {
			
			simulation.stop();
			do_draw = true;
		});
		
		elm_selected_node_container = $('<figure />').addClass('selected-node hide').appendTo(elm_legends);
				
		var func_mouse_down = function(e) {
						
			if (cur_node_id === false) {
				
				elm_selected_node_container.addClass('hide');
				
				elm_host.arr_link = false;
				elm_host.arr_info_box = false;
				
				return;
			}
			
			arr_nodes[cur_node_id].fixed = (arr_nodes[cur_node_id].fixed ? 2 : 1);
			
			PARENT.obj_map.onInteractDown = function() {};
						
			PARENT.obj_map.onInteractMove = function() {
				
				is_dragging_node = true;
				
				const pos_hover = PARENT.obj_map.getMousePosition(false);
				const arr_node = arr_nodes[cur_node_id];
				
				arr_node.x = ((pos_hover.x - (stage.position ? stage.position.x : 0)) / num_scale) - pos_translation.x;
				arr_node.y = ((pos_hover.y - (stage.position ? stage.position.y : 0)) / num_scale) - pos_translation.y;
				
				arr_node.fx = arr_node.x;
				arr_node.fy = arr_node.y;
				
				simulation.resume();
				do_draw = true;
			};
			
			PARENT.obj_map.onInteractUp = function() {
				
				pos_hover_poll = false;
				
				if (is_dragging_node) {
					
					const arr_node = arr_nodes[cur_node_id];
					
					is_dragging_node = false;
					
					if (arr_node.fixed == 2) {
						
						arr_node.fixed = 0;
						arr_node.fx = null;
						arr_node.fy = null;
					}

					simulation.resume();
					do_draw = true;
				} else {

					if (cur_node_id !== false && arr_nodes[cur_node_id].fixed == 1) {
						arr_nodes[cur_node_id].fixed = 0;
					}
				}
				
				PARENT.obj_map.onInteractDown = false;
				PARENT.obj_map.onInteractMove = false;
				PARENT.obj_map.onInteractUp = false;
			};
		};
			
		elm[0].addEventListener('touchstart', function(e) {
			
			if (in_first_run) {
				return;
			}
			
			interact();
			
			func_mouse_down(e);
		});		
		elm[0].addEventListener('mousedown',  function(e) {
						
			if (POSITION.isTouch()) {
				return;
			}
			
			func_mouse_down(e);
		});
		
		if (use_metrics) {
			metrics_process = new MapNetworkMetrics(elm_legends, PARENT);
		}
	};
	
	var hoverNode = function (arr_node, show_box, do_highlight) {
		
		elm_host.removeAttribute('title');
		
		if (do_highlight !== false) {
			
			while (arr_highlighted_nodes.length) {
				
				const node_id = arr_highlighted_nodes.pop();
				drawNodeElm(arr_nodes[node_id]);
			}
			
			for (let i = 0; i < arr_loop_links.length; i++) {
				
				const arr_link = arr_loop_links[i];
				
				if (!arr_link.is_active) {
					continue;
				}
				
				setLinkColor(arr_link, false);
				
				if (show_arrowhead && display == DISPLAY_VECTOR) {
					arr_link.elm.setAttribute('marker-end', 'url(#end)');
				}
			}
		}
		
		if (arr_node === false) {
			
			elm_host.arr_link = false;
			elm_host.arr_info_box = false;
		} else {
				
			const connections = {};
			let cur_in = 0;
			let cur_out = 0;
			let connection_object_parents = [];
			let connection_object_sub_parents = [];

			if (do_highlight !== false) {

				arr_node.highlight_color = color_highlight_node;
				drawNodeElm(arr_node);
				
				if (arr_highlighted_nodes.indexOf(arr_node.id) === -1) {
					arr_highlighted_nodes.push(arr_node.id);
				}
				
				elm_host.setAttribute('title', arr_node.name);
			}
						
			const arr_connect_objects = [];
			
			for (let i = 0, len = arr_loop_links.length; i < len; i++) {

				const arr_link = arr_loop_links[i];
				
				if (!arr_link.is_active) {
					continue;
				}
				
				let arr_node_connected = false;
				
				if (arr_link.target.id == arr_node.id) {
					
					cur_in++
					arr_node_connected = arr_link.source;
				} else if (arr_link.source.id == arr_node.id) {
					
					cur_out++
					arr_node_connected = arr_link.target;
				}
				
				if (arr_node_connected) {
					
					const connected_object_id = arr_node_connected.id;
					
					arr_connect_objects.push({object_id: connected_object_id, type_id: arr_node_connected.type_id});
					
					const arr_object_parents = [arr_link.source_object_id, arr_link.target_object_id];
					const count_object_parents = 2;
					
					if (!connections[connected_object_id]) {
						
						connections[connected_object_id] = {
							id: connected_object_id, 
							name: arr_node_connected.name, 
							count: count_object_parents + arr_link.count_object_sub_parents, 
							parents: {
								object_parents: arr_object_parents, 
								object_sub_parents: Object.keys(arr_link.object_sub_parents)
							}, 
							total: (arr_node_connected.count_in + arr_node_connected.count_out)
						};					
					} else {
						
						connections[connected_object_id].parents.object_parents = connections[connected_object_id].parents.object_parents.concat(arr_object_parents);
						connections[connected_object_id].parents.object_sub_parents = connections[connected_object_id].parents.object_sub_parents.concat(Object.keys(arr_link.object_sub_parents));
						connections[connected_object_id].count = (connections[connected_object_id].count + count_object_parents + arr_link.count_object_sub_parents);
					}
					
					if (do_highlight !== false) {
						
						if (show_arrowhead && display == DISPLAY_VECTOR) {
							arr_link.elm.setAttribute('marker-end', 'url(#end-selected)');
						}
						
						setLinkColor(arr_link, color_highlight_link);
						
						arr_node_connected.highlight_color = color_highlight_node_connect;
						drawNodeElm(arr_node_connected);
						
						if (arr_highlighted_nodes.indexOf(connected_object_id) === -1) {
							arr_highlighted_nodes.push(connected_object_id);
						}
				
					}
					
					connection_object_parents = connection_object_parents.concat(arr_object_parents);
					connection_object_sub_parents = connection_object_sub_parents.concat(Object.keys(arr_link.object_sub_parents));
				}
			}
			
			elm_host.arr_link = {object_id: parseInt(arr_node.id), type_id: parseInt(arr_node.type_id), object_sub_ids: arr_node.object_sub_parents, connect_object_ids: arr_connect_objects};
			elm_host.arr_info_box = {name: arr_node.name};

			if (show_box) {
				
				const elm_span = $('<span class="a">'+arr_node.name+'</span>');
				
				elm_span[0].dataset.node_id = arr_node.id;
									
				elm_span[0].addEventListener('mouseenter', function() {
				
					hoverNode(arr_nodes[this.dataset.node_id], false);
				});
							
				elm_span[0].addEventListener('mouseleave', function() {
				
					hoverNode(false, false);
				});		
							
				elm_span[0].addEventListener('click', function() {
				
					elm.click();
				});
				
				elm_selected_node_container.removeClass('hide').html(elm_span);
				const info = [{label: 'out-Links', elm: cur_out+'/'+arr_node.count_out}];
				const details = getDataDetails(arr_node.id, connection_object_parents, connection_object_sub_parents);
				
				for (const object_definition_id in details.source.object_definitions) {
										
					info.push({label: arr_data.info.types[arr_node.type_id].name+' '+arr_data.info.object_descriptions[object_definition_id].object_description_name, elm: details.source.object_definitions[object_definition_id]});
				}
							
				for (const type_id in details.source.object_subs) {
								
					for (const object_sub_details_id in details.source.object_subs[type_id]) {	
								
						for (const object_sub_definition_id in details.source.object_subs[type_id][object_sub_details_id]) {
							
							const str_object_sub_details = (object_sub_details_id !== 'collapse' ? '['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']' : '-'); // Could be collapsed and not exist
													
							info.push({label: arr_data.info.types[type_id].name+' '+str_object_sub_details, elm: details.source.object_subs[type_id][object_sub_details_id][object_sub_definition_id]});
						}
					}
				}
				
				info.push({label: 'in-Links', elm: cur_in+'/'+arr_node.count_in});
				
				for (const type_id in details.target.object_definitions) {
					
					for (const object_definition_id in details.target.object_definitions[type_id]) {
				
						info.push({label: arr_data.info.types[type_id].name+' '+arr_data.info.object_descriptions[object_definition_id].object_description_name, elm: details.target.object_definitions[type_id][object_definition_id]});
					}
				}	
							
				for (const type_id in details.target.object_subs) {	
							
					for (const object_sub_details_id in details.target.object_subs[type_id]) {
								
						for (const object_sub_definition_id in details.target.object_subs[type_id][object_sub_details_id]) {
							
							const str_object_sub_details = (object_sub_details_id !== 'collapse' ? '['+arr_data.info.object_sub_details[object_sub_details_id].object_sub_details_name+']' : '-'); // Could be collapsed and not exist
							
							info.push({label: arr_data.info.types[type_id].name+' '+str_object_sub_details, elm: details.target.object_subs[type_id][object_sub_details_id][object_sub_definition_id]});
						}
					}
				}				
				
				const arr_connections = [];
				
				for (const connected_object_id in connections) {
					
					arr_connections.push(connections[connected_object_id]);
				}
				
				arr_connections.sort(function(a, b) {
					return parseFloat(b.count) - parseFloat(a.count);
				});
				
				for (let i = 0; i < (arr_connections.length > 9 ? 10 : arr_connections.length); i++) {
					
					const elm_connection_relations = $('<span title="Number of relations between '+arr_node.name+' and '+arr_connections[i].name+' (total links of '+arr_connections[i].name+')">'+arr_connections[i].count+' ('+arr_connections[i].total+')</span>');
					const elm_connection_name = $('<span id="y:data_view:view_type_object-'+arr_nodes[arr_connections[i].id].type_id+'_'+arr_connections[i].id+'" class="a popup">'+arr_connections[i].name+'</span>');
					
					elm_connection_name[0].dataset.node_id = arr_connections[i].id;

					elm_connection_name[0].addEventListener('mouseenter', function() {
						
						hoverNode(arr_nodes[this.dataset.node_id], false);
					})
					
					elm_connection_name[0].addEventListener('mouseleave', function() {
						
						hoverNode(false, false);
					});
					
					info.push({label: elm_connection_name, elm: elm_connection_relations});
				};
				
				const list = $('<dl />').appendTo(elm_selected_node_container);
				
				for (const key in info) {
					
					const li = $('<div />').appendTo(list);
					const dt = $('<dt />').html(info[key].label).appendTo(li);
					const dd = $('<dd />').html(info[key].elm).appendTo(li);
				}
			}
		}
		
		TOOLTIP.update();
		
		if (display == DISPLAY_PIXEL && !in_first_run && !do_draw) { // Rerender stage to show/hide highlight colours
			
			if (do_update_geometry_lines_color) {
				
				buffer_geometry_lines_color.update();
				do_update_geometry_lines_color = false;
			}
				
			renderer.render(stage);
		}
	};
	
	var setNodesLinksValues = function() {
		
		num_node_weight_min = null;
		num_node_weight_max = 1;
		num_link_weight_max = 1;

		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
			
			const arr_node = arr_loop_nodes[i];
			
			if (!arr_node.is_active) {
				continue;
			}
			
			if (is_weighted) {
				
				let num_weight_conditions = arr_node.weight_conditions;
				
				if (num_weight_conditions == 0) {
					continue;
				}
				
				if (size_node_stop && num_weight_conditions > size_node_stop) {
					num_weight_conditions = size_node_stop;
				}

				if (num_weight_conditions > num_node_weight_max) {
					num_node_weight_max = num_weight_conditions;
				}
				if (num_weight_conditions < num_node_weight_min || num_node_weight_min === null) {
					num_node_weight_min = num_weight_conditions;
				}
			} else {
				
				const num_weight = (arr_node.count_out + arr_node.count_in);
				
				if (num_weight == 0) {
					continue;
				}

				if (num_weight > num_node_weight_max) {
					num_node_weight_max = num_weight;
				}
				
				// num_node_weight_min = 1 (one connection)
			}
		}
		
		if (num_node_weight_min === null) {
			num_node_weight_min = 1;
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
			
			const arr_node = arr_loop_nodes[i];
			
			if (!arr_node.is_active) {
				continue;
			}
						
			if (is_weighted) {
				
				let num_weight_conditions = arr_node.weight_conditions;

				if (size_node_stop && num_weight_conditions > size_node_stop) {
					num_weight_conditions = size_node_stop;
				}

				if (num_weight_conditions / num_node_weight_max > label_threshold) {
					arr_node.has_text_threshold = true;
					arr_node.show_text = true;
				}
							
			} else {
				
				if ((arr_node.count_out + arr_node.count_in) / num_node_weight_max > label_threshold) {
					arr_node.has_text_threshold = true;
					arr_node.show_text = true;
				}
			}
		}
		
		for (let i = 0, len = arr_loop_links.length; i < len; i++) {
			
			const arr_link = arr_loop_links[i];
			
			if (!arr_link.is_active) {
				continue;
			}
			
			if (arr_link.weight > num_link_weight_max) {
				num_link_weight_max = arr_link.weight;
			}
		}		
	};
	
	var checkObjectSubInRange = function(arr_object_sub) {

		return checkNodeInRange(arr_nodes[arr_object_sub.object_id]);
	};
	
	var setCheckObjectSubs = function(dateinta_range) {

		// Single date sub objects
		for (let i = 0, len = arr_data.date.arr_loop.length; i < len; i++) {
			
			const date = arr_data.date.arr_loop[i];
			const dateinta = DATEPARSER.dateInt2Absolute(date);
			const in_range_date = (dateinta >= dateinta_range.min && dateinta <= dateinta_range.max);
			const arr_object_subs = arr_data.date[date];
		
			for (let j = 0; j < arr_object_subs.length; j++) {
				
				let in_range = in_range_date;
				
				if (in_range) {
					
					const arr_object_sub = arr_data.object_subs[arr_object_subs[j]];
					
					in_range = checkObjectSubInRange(arr_object_sub);
				}
				
				checkObjectSub(arr_object_subs[j], !in_range);
			}
		}
		
		// Sub objects with a date range
		for (let i = 0, len = arr_data.range.length; i < len; i++) {
			
			const arr_object_sub = arr_data.object_subs[arr_data.range[i]];
			
			const dateinta_start = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
			const dateinta_end = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
			
			let in_range = ((dateinta_start >= dateinta_range.min && dateinta_start <= dateinta_range.max) || (dateinta_end >= dateinta_range.min && dateinta_end <= dateinta_range.max) || (dateinta_start < dateinta_range.min && dateinta_end > dateinta_range.max));

			if (in_range) {
				in_range = checkObjectSubInRange(arr_object_sub);
			}
			
			checkObjectSub(arr_data.range[i], !in_range);
		}
	};
	
	var checkObjectSub = function(object_sub_id, do_remove) {

		const arr_object_sub_children = arr_object_subs_children[object_sub_id];
		let count_nodes = arr_object_sub_children.child_nodes.length;
		let count_links = arr_object_sub_children.child_links.length;
		
		arr_object_sub_children.is_active = !do_remove;
		
		// Nodes and Links are added, removed or updated based on sub-object and object parents
		// They set whether a node/link may or may not exist
		// The size of the node/link is based on the number of links
			
		while (count_nodes--) {

			const object_id = arr_object_sub_children.child_nodes[count_nodes];
			const arr_node = arr_nodes[object_id];
			
			const has_parent_id = (arr_node.object_sub_parents[object_sub_id] === true);
			
			if (do_remove) {
				
				if (has_parent_id) {
					
					arr_node.object_sub_parents[object_sub_id] = false;
					arr_node.count_object_sub_parents--;
				}
			} else {

				if (!has_parent_id) { // add node
					
					arr_node.object_sub_parents[object_sub_id] = true;
					arr_node.count_object_sub_parents++;
				} 
			}
		}
		
		while (count_links--) {
			
			const link_id = arr_object_sub_children.child_links[count_links];
			const arr_link = arr_links[link_id];
			
			const has_parent_id = (arr_link.object_sub_parents[object_sub_id] === true);

			if (do_remove) {
				if (has_parent_id) {
					
					arr_link.object_sub_parents[object_sub_id] = false;
					arr_link.count_object_sub_parents--;
				} 
			} else {					
				if (!has_parent_id) {
					
					arr_link.object_sub_parents[object_sub_id] = true;
					arr_link.count_object_sub_parents++;
				}
			}
		}
	};
	
	var checkNodes = function() {
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
				
			const arr_node = arr_loop_nodes[i];
			
			arr_node.is_enabled = checkNodeInRange(arr_node);
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) { // Setting all nodes' static object links to removed, when applicable. Point of depature, clean slate.
			
			const arr_node = arr_loop_nodes[i];
			const do_add = (arr_node.is_enabled && arr_node.count_object_sub_parents);
			
			if (!do_add) {
				checkRemoveNode(arr_node);
			}
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) { // Adding all nodes' static object links, when applicable.
			
			const arr_node = arr_loop_nodes[i];
			const do_add = (arr_node.is_enabled && arr_node.count_object_sub_parents);
			
			if (do_add) {
				checkAddNode(arr_node);
			}
		}
		
		for (let i = 0, len = arr_loop_nodes.length; i < len; i++) {
			
			const arr_node = arr_loop_nodes[i];
			
			if (arr_node.is_enabled && (arr_node.count_object_parents || arr_node.count_object_sub_parents)) {
				addNode(arr_node);
			} else {
				removeNode(arr_node);
			}
		}
		
		for (let i = 0, len = arr_loop_links.length; i < len; i++) {
				
			const arr_link = arr_loop_links[i];
			
			if (in_predraw) {
				arr_link.source = arr_nodes[arr_link.source_object_id];
				arr_link.target = arr_nodes[arr_link.target_object_id];
			}
			
			if (!arr_link.source.is_alive || !arr_link.target.is_alive) {
				removeLink(arr_link);
			} else if (!arr_link.has_object_parent && arr_link.has_object_sub_parents && !arr_link.count_object_sub_parents) {
				removeLink(arr_link);
			} else {
				addLink(arr_link);
			}
			
			arr_link.weight = (arr_link.count_object_parent + arr_link.count_object_sub_parents);
		}
		
		for (let i = 0; i < arr_remove_nodes.length; i++) {
			
			const object_id = arr_remove_nodes[i];

			drawNodeElm(arr_nodes[object_id]);
		}
		
		arr_remove_nodes = [];
	};
	
	var checkRemoveNode = function(arr_node) {
		
		if (!arr_node.child_nodes.length) {
			return;
		}
		
		if (arr_node.is_enabled && arr_node.count_object_sub_parents) {
			return;
		}
		
		arr_node.is_checked = false; // This additional switch helps to identify possible mutual static links that negate a natural flow (cancel each other) in the next checkAddNode-phase
		
		for (let i = 0, len = arr_node.child_nodes.length; i < len; i++) {
			
			const arr_node_child = arr_nodes[arr_node.child_nodes[i]];
			
			if (arr_node_child.object_parents[arr_node.id]) {
				
				arr_node_child.object_parents[arr_node.id] = false;
				arr_node_child.count_object_parents--;
			}

			if (arr_node_child.is_checked) {
				checkRemoveNode(arr_node_child);
			}
		}
	};
	
	var checkAddNode = function(arr_node) {
		
		if (!arr_node.child_nodes.length) {
			return;
		}
		
		if (!arr_node.is_enabled) {
			return;
		}
		
		arr_node.is_checked = true;
		
		for (let i = 0, len = arr_node.child_nodes.length; i < len; i++) {
			
			const arr_node_child = arr_nodes[arr_node.child_nodes[i]];
			
			if (!arr_node_child.object_parents[arr_node.id]) {
				
				arr_node_child.object_parents[arr_node.id] = true;
				arr_node_child.count_object_parents++;
			}
			
			if (!arr_node_child.is_checked) {
				checkAddNode(arr_node_child);
			}
		}
	};
	
	var checkNodeInRange = function(arr_node) {
		
		if (in_predraw) {
			return true;
		}
				
		if (PARENT.obj_data.arr_inactive_types[arr_node.type_id]) {
			return false;
		}
				
		if (PARENT.obj_data.arr_loop_inactive_conditions.length) {
			
			for (let i = 0, len = PARENT.obj_data.arr_loop_inactive_conditions.length; i < len; i++) {
				
				const has_inactive_condition = hasCondition(arr_node, PARENT.obj_data.arr_loop_inactive_conditions[i]);
				
				if (has_inactive_condition) {
					return false;
				}
			}
		}

		return true;
	}
	
	var addNode = function(arr_node) {
	
		if (arr_node.weight_total === 0) {
			return;
		}
		
		if (arr_node.is_alive) {
			
			if (arr_node.conditions.object_sub.length || arr_node.conditions.object_sub_parent.length) { // Check if node has changed internally (node has sub-object conditions).
				
				let str_identifier = '';
				
				for (let i = 0, len = arr_node.conditions.object_sub.length; i < len; i++) {
					
					const arr_condition = arr_node.conditions.object_sub[i];
					
					if (!arr_object_subs_children[arr_condition.source_id].is_active) {
						continue;
					}
						
					str_identifier += arr_condition.source_id;
				}
				
				for (let i = 0, len = arr_node.conditions.object_sub_parent.length; i < len; i++) {
					
					const arr_condition = arr_node.conditions.object_sub_parent[i];
					
					if (arr_node.object_sub_parents[arr_condition.source_id] === false) {
						continue;
					}
						
					str_identifier += arr_condition.source_id;
				}
				
				if (str_identifier != arr_node.identifier_condition_self) {

					arr_node.identifier_condition_self = str_identifier;
					arr_node.redraw_node = true;
					
					return;
				}
			} else {
				return;
			}
		}
		
		arr_node.is_alive = true;
		arr_node.is_active = true;
		arr_node.redraw_node = true;
	}
		
	var removeNode = function(arr_node) {
			
		if (!arr_node.is_alive) {
			return;
		}
		
		arr_node.is_alive = false;
		arr_node.redraw_node = true;

		if (show_disconnected_node == false) {
			
			if (arr_node.is_active) {
					
				arr_node.is_active = false;
				
				if (arr_node.elm !== false) {
					
					if (display == DISPLAY_PIXEL) {
						
						arr_node.elm.visible = false;
						if (arr_node.show_text) {
							arr_node.elm_text.visible = false;
						}
					} else {
						
						arr_node.elm.dataset.visible = 0;
					}
				}
			}
		} else {
			
			if (arr_node.elm) {
		
				arr_remove_nodes.push(arr_node.id);
			}
		}
	}
	
	var addLink = function(arr_link) {

		if (arr_link.is_active) {
			return;
		}

		var arr_source_node = arr_link.source;
		var arr_target_node = arr_link.target;
		
		var has_source_node = (arr_source_node.out[arr_link.id] === true);
		if (!has_source_node) {
			arr_source_node.out[arr_link.id] = true;
			arr_source_node.count_out++;
			arr_source_node.redraw_node = true;
		}
		
		var has_target_object = (arr_target_node.in[arr_link.id] === true);
		if (!has_target_object) {
			arr_target_node.in[arr_link.id] = true;
			arr_target_node.count_in++;
			arr_target_node.redraw_node = true;
		}
		
		arr_link.is_active = true;
		
		if (show_line && !in_predraw) {
			
			if (display == DISPLAY_VECTOR) {
				arr_link.action = 'show'; 
			} else if (display == DISPLAY_PIXEL) {

			}
		}
	};
	
	var removeLink = function(arr_link) {
		
		if (!arr_link.is_active) {
			return;
		}

		const arr_source_node = arr_link.source;
		const arr_target_node = arr_link.target;
						
		const has_source_node = (arr_source_node.out[arr_link.id] === true);
		if (has_source_node) {
			arr_source_node.out[arr_link.id] = false;
			arr_source_node.count_out--;
			arr_source_node.redraw_node = true;
		}
		
		const has_target_object = (arr_target_node.in[arr_link.id] === true);
		if (has_target_object) {
			arr_target_node.in[arr_link.id] = false;
			arr_target_node.count_in--;
			arr_target_node.redraw_node = true;
		}

		if (show_line && !in_predraw) {
			
			if (display == DISPLAY_VECTOR) {
				
				arr_link.elm.dataset.visible = 0;
			} else {
				
				const num_offset = arr_link.count * length_geometry_lines_position;

				buffer_geometry_lines_position.data[num_offset + 0] = 0;
				buffer_geometry_lines_position.data[num_offset + 1] = 0;
				buffer_geometry_lines_position.data[num_offset + 2] = 0;
				buffer_geometry_lines_position.data[num_offset + 3] = 0;
				buffer_geometry_lines_position.data[num_offset + 4] = 0;
				buffer_geometry_lines_position.data[num_offset + 5] = 0;
				buffer_geometry_lines_position.data[num_offset + 6] = 0;
				buffer_geometry_lines_position.data[num_offset + 7] = 0;
				buffer_geometry_lines_position.data[num_offset + 8] = 0;
				buffer_geometry_lines_position.data[num_offset + 9] = 0;
				buffer_geometry_lines_position.data[num_offset + 10] = 0;
				buffer_geometry_lines_position.data[num_offset + 11] = 0;
			}
		}
		
		arr_link.is_active = false;
	};
	
	var setLinkColor = function(arr_link, color) {
		
		if (!show_line) {
			return;
		}

		if (color) {
			
			arr_link.color = parseColorLink(color);
		} else {
			
			let num_line_ratio = 1;
			
			if (do_line_color_weight && num_link_weight_max != num_link_weight_min) {
			
				const num_weight_ratio = 1 - ((arr_link.weight - num_link_weight_min) / (num_link_weight_max - num_link_weight_min)); // Correct the range with the minimum weight to get a 0-1 ratio
				const arr_color = SocialUtilities.colorToBrightColor(color_line, (num_weight_ratio * 40));
				
				arr_link.color = parseColorLink('rgba('+arr_color.r+','+arr_color.g+','+arr_color.b+','+opacity_line+')');
			} else {
				
				arr_link.color = parseColorLink('rgba('+arr_color_line.r+','+arr_color_line.g+','+arr_color_line.b+','+opacity_line+')');
			}
		}
		
		if (show_line) {
			
			if (display == DISPLAY_VECTOR) {
				
				arr_link.elm.setAttribute('stroke', arr_link.color);
			} else {
				
				const num_offset = arr_link.count * length_geometry_lines_color;
				const num_r = (arr_link.color.r / 255);
				const num_g = (arr_link.color.g / 255);
				const num_b = (arr_link.color.b / 255);
				const num_a = arr_link.color.a;
				
				buffer_geometry_lines_color.data[num_offset + 0] = num_r;
				buffer_geometry_lines_color.data[num_offset + 1] = num_g;
				buffer_geometry_lines_color.data[num_offset + 2] = num_b;
				buffer_geometry_lines_color.data[num_offset + 3] = num_a;
				buffer_geometry_lines_color.data[num_offset + 4] = num_r;
				buffer_geometry_lines_color.data[num_offset + 5] = num_g;
				buffer_geometry_lines_color.data[num_offset + 6] = num_b;
				buffer_geometry_lines_color.data[num_offset + 7] = num_a;
				buffer_geometry_lines_color.data[num_offset + 8] = num_r;
				buffer_geometry_lines_color.data[num_offset + 9] = num_g;
				buffer_geometry_lines_color.data[num_offset + 10] = num_b;
				buffer_geometry_lines_color.data[num_offset + 11] = num_a;

				buffer_geometry_lines_color.data[num_offset + 12] = num_r;
				buffer_geometry_lines_color.data[num_offset + 13] = num_g;
				buffer_geometry_lines_color.data[num_offset + 14] = num_b;
				buffer_geometry_lines_color.data[num_offset + 15] = num_a;
				buffer_geometry_lines_color.data[num_offset + 16] = num_r;
				buffer_geometry_lines_color.data[num_offset + 17] = num_g;
				buffer_geometry_lines_color.data[num_offset + 18] = num_b;
				buffer_geometry_lines_color.data[num_offset + 19] = num_a;
				buffer_geometry_lines_color.data[num_offset + 20] = num_r;
				buffer_geometry_lines_color.data[num_offset + 21] = num_g;
				buffer_geometry_lines_color.data[num_offset + 22] = num_b;
				buffer_geometry_lines_color.data[num_offset + 23] = num_a;
				
				do_update_geometry_lines_color = true;
			}
		}
	}

	var getDataDetails = function (object_id, arr_object_parents, arr_object_sub_parents) {
		
		let object = null;
		
		if (arr_data.objects[object_id]) {
			
			object = arr_data.objects[object_id];
			
		} else {
			
			object = {object_definitions: {}};
			
		}
		arr_object_parents = arrUnique(arr_object_parents),
		arr_object_sub_parents = arrUnique(arr_object_sub_parents),
		details = {source: {object_definitions: {}, object_subs: {}}, target: {object_definitions: {}, object_subs: {}}};
		
		//count all relations FROM context object
		for (const object_definition_id in object.object_definitions) {
			
			if (object.object_definitions[object_definition_id] && object.object_definitions[object_definition_id].ref_object_id.length) {
				details.source.object_definitions[object_definition_id] = object.object_definitions[object_definition_id].ref_object_id.length;
			}
		}
		
		//count all relations FROM context object sub objects based on object_sub_parents
		for (let i = 0; i < arr_object_sub_parents.length; i++) {
			
			if (arr_data.object_subs[arr_object_sub_parents[i]].object_id == object_id) {
				
				const object_sub = arr_data.object_subs[arr_object_sub_parents[i]];
				
				if (object_sub.object_sub_definitions) {
					
					for (const object_sub_definition_id in object_sub.object_sub_definitions) {
						
						const arr_object_sub_definition = object_sub.object_sub_definitions[object_sub_definition_id];
						
						if (arr_object_sub_definition && arr_object_sub_definition.ref_object_id.length) {
							
							for (let j = 0; j < arr_object_sub_definition.ref_object_id.length; j++) {
								
								if (arr_object_sub_definition.ref_object_id[j] != object_id) {
									
									const type_id = arr_data.objects[object_sub.object_id].type_id;
									
									if (!details.source.object_subs[type_id]) {
										details.source.object_subs[type_id] = {};
									}
									
									const object_sub_details_id = object_sub.object_sub_details_id; // Could be collapsed
									
									if (!details.source.object_subs[type_id][object_sub_details_id]) {
										details.source.object_subs[type_id][object_sub_details_id] = {};
									}
									
									if (!details.source.object_subs[type_id][object_sub_details_id][object_sub_definition_id]) {
										details.source.object_subs[type_id][object_sub_details_id][object_sub_definition_id] = 1;
									} else {
										details.source.object_subs[type_id][object_sub_details_id][object_sub_definition_id]++;
									}
								}
							}
						}
					}
				}					
			}
		}
								
		//count all relations TOWARDS context object based on object_parents
		for (let i = 0; i < arr_object_parents.length; i++) {
			
			if (arr_object_parents[i] != object_id) {
				
				let ref_object = null;
				
				if (arr_data.objects[arr_object_parents[i]]) {
					ref_object = arr_data.objects[arr_object_parents[i]];
				} else {
					ref_object = {object_definitions: {}};
				}
				
				for (const object_definition_id in ref_object.object_definitions) {
					
					const arr_object_definition = ref_object.object_definitions[object_definition_id];
					
					if (arr_object_definition && arr_object_definition.ref_object_id.length) {
						
						for (let j = 0; j < arr_object_definition.ref_object_id.length; j++) {
							
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
		for (let i = 0; i < arr_object_sub_parents.length; i++) {
			
			if (arr_data.object_subs[arr_object_sub_parents[i]].object_id != object_id) {
				
				const object_sub = arr_data.object_subs[arr_object_sub_parents[i]];
				
				if (object_sub.object_sub_definitions) {
					
					for (const object_sub_definition_id in object_sub.object_sub_definitions) {
						
						const arr_object_sub_definition = object_sub.object_sub_definitions[object_sub_definition_id];
						
						if (arr_object_sub_definition && arr_object_sub_definition.ref_object_id.length) {
							
							for (let j = 0; j < arr_object_sub_definition.ref_object_id.length; j++) {
								
								if (arr_object_sub_definition.ref_object_id[j] == object_id) {
									
									const type_id = arr_data.objects[arr_data.object_subs[arr_object_sub_parents[i]].object_id].type_id;
									
									if (!details.target.object_subs[type_id]) {
										details.target.object_subs[type_id] = {};
									}
									
									const object_sub_details_id = object_sub.object_sub_details_id;
									
									if (!details.target.object_subs[type_id][object_sub_details_id]) {
										details.target.object_subs[type_id][object_sub_details_id] = {};
									}
									
									if (!details.target.object_subs[type_id][object_sub_details_id][object_sub_definition_id]) {
										details.target.object_subs[type_id][object_sub_details_id][object_sub_definition_id] = 1;
									} else {
										details.target.object_subs[type_id][object_sub_details_id][object_sub_definition_id]++;
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
		
	var parseData = function() {
		
		arr_nodes = {};
		arr_links = {};
		arr_object_subs_children = {};
		
		const arr_object_subs = arr_data.object_subs;
		const arr_loop_object_subs = Object.keys(arr_object_subs);
		let count_arr_object_subs = arr_loop_object_subs.length;
		
		const makeLink = function(source_object_id, target_object_id) {
			
			const link_id = source_object_id+'_'+target_object_id;
			
			if (arr_links[link_id] === undefined) {
				
				const arr_link = {id: link_id, count: count_links, source: false, target: false, source_object_id: source_object_id, target_object_id: target_object_id, count_object_parent: 0, has_object_parent: false,
					object_sub_parents: {}, count_object_sub_parents: 0, has_object_sub_parents: false,
					weight: 0, has_reverse: false, elm: false,
					is_active: false
				};
				
				arr_links[link_id] = arr_link;
				arr_loop_links[count_links] = arr_link;
				
				count_links++;
				
				if (arr_links[target_object_id+'_'+source_object_id] !== undefined) {
					
					arr_link.has_reverse = true;
					arr_links[target_object_id+'_'+source_object_id].has_reverse = true;
				}
			}

			return link_id;
		}
		
		const arr_objects = arr_data.objects;
		
		for (const object_id in arr_objects) {
			
			const arr_object = arr_objects[object_id];
			
			setNodeProperties(false, object_id, false);
			
			const arr_node = arr_nodes[object_id];
			
			for (const object_definition_id in arr_object.object_definitions) {
				
				const arr_object_definition = arr_object.object_definitions[object_definition_id];

				if (arr_object_definition && arr_object_definition.ref_object_id.length) {
					
					let has_conditions = false;
					
					for (let i = 0, len = arr_object_definition.ref_object_id.length; i < len; i++) {
						
						const target_object_id = setNodeProperties(arr_data.info.object_descriptions[object_definition_id].object_description_ref_type_id, arr_object_definition.ref_object_id[i], arr_object_definition.value[i]);
						
						if (object_id == target_object_id) {
							continue;
						}
							
						const arr_target_node = arr_nodes[target_object_id];
						
						arr_node.child_nodes.push(target_object_id);	
							
						const link_id = makeLink(object_id, target_object_id);
						const arr_link = arr_links[link_id];
						
						arr_link.has_object_parent = true;
						arr_link.count_object_parent++;
							
						arr_node.child_links.push(link_id);	

						// Weight and other conditions of object applied to target node
						
						if (arr_object_definition.style.weight !== null) {

							arr_target_node.weight_conditions += arr_object_definition.style.weight;
							arr_target_node.weight_total += arr_object_definition.style.weight;
						}
						
						const arr_conditions = setCondition(arr_object_definition.style, object_id, object_definition_id);
						
						if (arr_conditions) {
							
							arr_target_node.has_conditions = true;
							arr_target_node.conditions.object_parent = arr_target_node.conditions.object_parent.concat(arr_conditions);
							
							has_conditions = true;
						}
					}
					
					if (has_conditions) {
						
						for (const str_identifier_condition in arr_object.style.conditions) {
							
							arr_node.conditions.object_definition.push({identifier: str_identifier_condition, source_id: object_id});
						}
					}
				}
			}			
		}
		
		while (count_arr_object_subs--) {
			
			const object_sub_id = arr_loop_object_subs[count_arr_object_subs];
			const arr_object_sub = arr_object_subs[object_sub_id];
			const object_id = arr_object_sub.object_id+'';
			
			const arr_object_sub_children = {child_nodes: [object_id], child_links: [], is_active: false};

			const arr_node = arr_nodes[object_id];

			if (arr_object_sub.object_sub_definitions) {
				
				for (const object_sub_definition_id in arr_object_sub.object_sub_definitions) {
					
					const arr_object_sub_definition = arr_object_sub.object_sub_definitions[object_sub_definition_id];
					
					if (arr_object_sub_definition && arr_object_sub_definition.ref_object_id.length) {
						
						let has_conditions = false;
						
						for (let i = 0, len = arr_object_sub_definition.ref_object_id.length; i < len; i++) {
							
							const target_object_id = setNodeProperties(arr_data.info.object_sub_descriptions[object_sub_definition_id].object_sub_description_ref_type_id, arr_object_sub_definition.ref_object_id[i], arr_object_sub_definition.value[i]);	
							
							if (object_id == target_object_id) {
								continue;
							}
							
							const arr_target_node = arr_nodes[target_object_id];
							
							arr_object_sub_children.child_nodes.push(target_object_id);
								
							const link_id = makeLink(object_id, target_object_id);
							
							arr_links[link_id].has_object_sub_parents = true;
							
							arr_object_sub_children.child_links.push(link_id);

							// Cross Referencing weight and other conditions added to target node
							
							if (arr_object_sub_definition.style.weight !== null) {

								arr_target_node.weight_conditions += arr_object_sub_definition.style.weight;
								arr_target_node.weight_total += arr_object_sub_definition.style.weight;
							}	
							
							const arr_conditions = setCondition(arr_object_sub_definition.style, object_sub_id, object_sub_definition_id);
							
							if (arr_conditions) {
								
								arr_target_node.has_conditions = true;
								arr_target_node.conditions.object_sub_parent = arr_target_node.conditions.object_sub_parent.concat(arr_conditions);
								
								has_conditions = true;
							}
						}
						
						if (has_conditions) {
							
							for (const str_identifier_condition in arr_object_sub.style.conditions) {
								
								arr_node.conditions.object_sub_definition.push({identifier: str_identifier_condition, source_id: object_sub_id});
							}
						}
					}
				}
			} 
			
			// Weight and other conditions of sub-object applied to current node
			
			if (arr_object_sub.style.weight !== null) {
	
				arr_node.weight_conditions += arr_object_sub.style.weight;
				arr_node.weight_total += arr_object_sub.style.weight;
			}
										
			const arr_conditions = setCondition(arr_object_sub.style, object_sub_id);
			
			if (arr_conditions) {
				
				arr_node.has_conditions = true;
				arr_node.conditions.object_sub = arr_node.conditions.object_sub.concat(arr_conditions);
			}
			
			if (include_location_nodes && arr_object_sub.location_object_id) {
				
				var target_object_id = setNodeProperties(arr_object_sub.location_type_id, arr_object_sub.location_object_id, arr_object_sub.location_name);
				
				if (object_id != target_object_id) {
							
					arr_object_sub_children.child_nodes.push(target_object_id);
					
					var link_id = makeLink(object_id, target_object_id);
					
					arr_links[link_id].has_object_sub_parents = true;
					
					arr_object_sub_children.child_links.push(link_id);
				}
			}
			
			arr_object_subs_children[object_sub_id] = arr_object_sub_children;
		}
	}
	
	var setCondition = function(arr_style, source_id, source_definition_id) {
		
		if (!arr_style || (!arr_style.color && !arr_style.icon)) {
			return false;
		}

		const arr_conditions = [];
		
		const is_array_color = (typeof arr_style.color == 'object');
		const is_array_icon = (typeof arr_style.icon == 'object');
		const num_weight_style = (arr_style.weight !== null ? arr_style.weight : null);
		let num_weight_conditions = 0;
		
		const arr_legend_conditions = arr_data.legend.conditions;
		
		for (const str_identifier_condition in arr_style.conditions) {
			
			const arr_legend_condition = arr_legend_conditions[str_identifier_condition];
			
			let num_weight = arr_style.conditions[str_identifier_condition];
			
			if (num_weight === null) {
				continue;
			}
			
			num_weight_conditions += num_weight;
			let color = null;
			let icon = null;

			if (arr_legend_condition.color) {
				
				if (is_array_color) {
					
					for (let i = 0, len = arr_style.color.length; i < len; i++) {
						
						if (arr_legend_condition.color !== arr_style.color[i]) {
							continue;
						}
							
						color = arr_style.color[i];
						break;
					}
				} else if (arr_legend_condition.color === arr_style.color) {
					
					color = arr_style.color;
				}
			}
			
			if (arr_legend_condition.icon) {
				
				if (is_array_icon) {
					
					for (let i = 0, len = arr_style.icon.length; i < len; i++) {
						
						if (arr_legend_condition.icon !== arr_style.icon[i]) {
							continue;
						}
						
						icon = arr_style.icon[i];
						break;
					}
				} else if (arr_legend_condition.icon === arr_style.icon) {
					
					icon = arr_style.icon;
				}
			}
			
			if (color === null && icon === null && num_weight_style === null) {
				continue;
			}
			
			arr_conditions.push({identifier: str_identifier_condition, source_id: source_id, source_definition_id: source_definition_id, weight: num_weight, color: color, icon: icon});
		}
		
		// Adjust the individual condition weights to scale within the overall calculated weight
		
		if (num_weight_style !== null && num_weight_conditions != num_weight_style) {
			
			const num_scale = (num_weight_style / num_weight_conditions);
			
			for (let i = 0, len = arr_conditions.length; i < len; i++) {
				
				const arr_condition = arr_conditions[i];
				arr_condition.weight = arr_condition.weight * num_scale;
			}
		}
		
		return arr_conditions;
	}
	 
	var hasCondition = function(arr_node, condition_label) {
		
		const arr_conditions = arr_node.conditions;
		
		for (let i = 0, len = arr_conditions.object.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object[i].identifier) {
				return true;
			}
		}
		
		for (let i = 0, len = arr_conditions.object_sub.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object_sub[i].identifier) {
				return true;
			}
		}
		
		for (let i = 0, len = arr_conditions.object_definition.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object_definition[i].identifier) {
				return true;
			}
		}
		
		for (let i = 0, len = arr_conditions.object_sub_definition.length; i < len; i++) {
			
			if (condition_label === arr_conditions.object_sub_definition[i].identifier) {
				return true;
			}
		}
		
		return false;
	}
	
	var setNodeProperties = function(type_id, object_id, name) {
		
		var object_id = object_id+''; // Make string so all IDs have same format
	
		if (!arr_nodes[object_id]) {
			
			var arr_node = {};
			
			arr_node.id = object_id;
			arr_node.count = count_nodes;
			arr_node.index = null;
			//arr_node.x = (size_renderer.width / 2);
			//arr_node.y = (size_renderer.height / 2);
			arr_node.x = (Math.random() * 100) - (100 / 2) + (size_renderer.width / 2);
			arr_node.y = (Math.random() * 100) - (100 / 2) + (size_renderer.height / 2);
			arr_node.radius = 1;
			arr_node.weight = 1;
			arr_node.fixed = 0;
			
			arr_node.has_conditions = false;
			arr_node.conditions = {object: [], object_sub: [], object_parent: [], object_sub_parent: [], object_definition: [], object_sub_definition: []};
			arr_node.identifier_condition = '';
			arr_node.identifier_condition_self = '';
			arr_node.colors = false;
			arr_node.icons = false;
			arr_node.weight_conditions = 1;
			arr_node.weight_total = null;
			
			if (arr_data.objects[object_id]) { // Object is present in objects in arr_data
				
				var arr_object = arr_data.objects[object_id];
				var name = arr_object.name;
				var type_id = arr_object.type_id;
				
				if (arr_object.style.weight !== null) {
					
					arr_node.weight_conditions += arr_object.style.weight;
					arr_node.weight_total += arr_object.style.weight;
				}
				
				const arr_conditions = setCondition(arr_object.style, false);
				
				if (arr_conditions) {
					
					arr_node.has_conditions = true;
					arr_node.conditions.object = arr_node.conditions.object.concat(arr_conditions);
				}
			}
			
			arr_node.type_id = type_id;
			
			arr_node.name = name;
			arr_node.name_text = stripHTMLTags(name);
							
			arr_node.child_links = [];
			arr_node.child_nodes = [];
			arr_node.object_parents = {};
			arr_node.object_sub_parents = {};
			arr_node.count_object_parents = 0;
			arr_node.count_object_sub_parents = 0;
			
			arr_node.in = {};
			arr_node.out = {};
			arr_node.count_in = 0;
			arr_node.count_out = 0;
			arr_node.is_enabled = false;
			arr_node.is_alive = false;
			arr_node.is_active = false;
			arr_node.is_checked = false;
			arr_node.identifier = '';
			arr_node.elm = false;
			arr_node.elm_text = false;
			arr_node.color = false;
			arr_node.redraw_node = false;
			arr_node.show_text = false;
			arr_node.has_text_threshold = false;
			
			if (focus_object_id == object_id) {
				
				arr_node.x = (size_renderer.width / 2 / num_scale);
				arr_node.y = (size_renderer.height / 2 / num_scale);
				
				arr_node.fixed = 1;
				arr_node.fx = arr_node.x;
				arr_node.fy = arr_node.y;
			}
			
			arr_nodes[object_id] = arr_node;
			arr_loop_nodes[arr_node.count] = arr_node;
				
			count_nodes++;
		}
	
		return object_id;
	}
};
