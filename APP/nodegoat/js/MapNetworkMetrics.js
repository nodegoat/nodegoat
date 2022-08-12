
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function MapNetworkMetrics(element, obj_parent) {

	var obj = this,
	elm_parent = element[0],
	elm_container = false,
	arr_network_data = false,
	cur_dateinta_range = false,
	dateinta_range_full = false,
	arr_metrics = [],
	worker = new Worker("../../js/MapNetworkMetricsWorker.js");
	moving = false;

	worker.onmessage = function(event) {
		switch (event.data.type) {
			case "status": 
				statusWorker(event.data.id, event.data.progress);
				break;
			case "end": 
				finishedWorker(event.data.id, event.data);
				break;
		}
	};	

	var metric_functions = {
		frequency: {name: 'Frequency'}, 
		occurrences: {name: 'Occurrences'}, 
		cooccurrences: {name: 'Cooccurrences'}, 
		degree_centrality: {name: 'Degree Centrality'}, 
		weighted_degree_centrality: {name: 'Weighted Degree Centrality'}
	};

	this.init = function() {
		
		elm_container = document.createElement('figure');
		elm_container.setAttribute('class', 'metrics-container');
		
		elm_parent.appendChild(elm_container);
		
		var elm_list = document.createElement('ul');
		elm_list.setAttribute('class', 'hide');
		elm_container.appendChild(elm_list);
		
		
		for (var metric in metric_functions) {
			var elm_metric_button = document.createElement('li');
			var metric_name = document.createTextNode(metric_functions[metric].name);
			elm_metric_button.appendChild(metric_name);
			elm_metric_button.setAttribute('data-metric', metric);
			elm_list.appendChild(elm_metric_button);
			
			elm_metric_button.addEventListener('click', function() {
				setMetricBox(this.getAttribute('data-metric'));
			});
		}
		
		var elm_button = document.createElement('span');
		elm_button.setAttribute('class', 'icon-chart-bar');
		
		elm_container.appendChild(elm_button);
		
		elm_container.addEventListener('mouseenter', function() {
			elm_list.setAttribute('class', '');
		});
		
		elm_container.addEventListener('mouseleave', function() {
			elm_list.setAttribute('class', 'hide');
		});
	};
	
	this.update = function(dateinta_range) {

		worker.postMessage({
			dateinta_range: dateinta_range,
			action: 'update'
		});
		
		cur_dateinta_range = dateinta_range;
	};
	
	var getNodePosition = function(node) { 
		  
		var top = 0;
		var left = 0;
		
		while (node) {  
				
			if (node.tagName) {
				
				top = top + node.offsetTop;
				left = left + node.offsetLeft;   	
				node = node.offsetParent;
				
			} else {
				
				node = node.parentNode;
			}
		}
		 
		return [left, top];
	};

	var setMetricBox = function(metric) {

		if (!arr_network_data) {
			
			setNetworkData();
			
			worker.postMessage({
				arr_network_data: arr_network_data,
				dateinta_range: dateinta_range_full,
				action: 'init'
			});
			
			window.addEventListener('mousemove', function(e) {
				
				if (moving !== false) {
					
					var elm_drag = arr_metrics[moving.id].elm;
					elm_drag.style.position = 'fixed';
					elm_drag.style.left = (e.clientX - moving.offset[0]) + 'px';
					elm_drag.style.top = (e.clientY - moving.offset[1]) + 'px';

				}
			});
			
			window.addEventListener('mouseup', function(e) {
				moving = false;
			});
			
		}
		
		var elm_box = document.createElement('figure');
		var id = arr_metrics.push({metric: metric, elm: elm_box}) - 1;
		
		elm_box.setAttribute('data-id', id);
		elm_box.setAttribute('class', 'metric');
		
		var elm_metric_name = document.createElement('span');
		var metric_name = document.createTextNode(metric_functions[metric].name);
		elm_metric_name.appendChild(metric_name);
		 
		elm_metric_name.addEventListener('mousedown', function(e) {
			var parent_container = this.parentNode;
			var cur_id = parent_container.getAttribute('data-id');
			var parent_container_pos = getNodePosition(parent_container);

			moving = {id: cur_id, offset: [e.clientX - parent_container_pos[0], e.clientY - parent_container_pos[1]]};
		});
		
		
		elm_box.appendChild(elm_metric_name);
		
		var elm_buttons = document.createElement('div');
		
		var elm_button_refresh = document.createElement('span');
		elm_button_refresh.setAttribute('class', 'icon-reload');
		elm_button_refresh.addEventListener('click', function() {
			var parent_div = this.parentNode;
			var parent_container = parent_div.parentNode;
			var cur_id = parent_container.getAttribute('data-id');
			var cur_metric = arr_metrics[cur_id].metric;
			worker.postMessage({
				dateinta_range: cur_dateinta_range,
				id: cur_id,
				metric: cur_metric
			});
		});
		
		elm_buttons.appendChild(elm_button_refresh);
		
		var elm_button_update = document.createElement('span');
		elm_button_update.setAttribute('class', 'icon-refresh');
		elm_button_update.addEventListener('click', function() {

			var parent_div = this.parentNode;
			var parent_container = parent_div.parentNode;
			var cur_id = parent_container.getAttribute('data-id');
			var cur_metric = arr_metrics[cur_id].metric;
						
			var cur_class = this.getAttribute('class');
			
			if (cur_class.indexOf('active') > -1) {
				
				this.setAttribute('class', 'icon-refresh');
				worker.postMessage({
					id: cur_id,
					action: 'remove_metric',
					metric: cur_metric
				});
				
			} else {
				
				this.setAttribute('class', 'icon-refresh active');
				worker.postMessage({
					id: cur_id,
					action: 'add_metric',
					metric: cur_metric
				});
					
				
			}
			
		});
		
		elm_buttons.appendChild(elm_button_update);
		
		var elm_button_remove = document.createElement('span');
		elm_button_remove.setAttribute('class', 'icon-close');
		elm_button_remove.addEventListener('click', function() {
			var parent_div = this.parentNode;
			var parent_container = parent_div.parentNode;
			elm_parent.removeChild(parent_container);			
		});
		
		elm_buttons.appendChild(elm_button_remove);
		
		
		elm_box.addEventListener('mousemove', function(e) {
			var pos = getNodePosition(this);
			var cur_id = this.getAttribute('data-id');
			
			if (e.clientX - pos[0] < 3) {
				//cur_id
				this.style.cursor = 'w-resize';
			} else if (e.clientX - pos[0] > (this.offsetWidth - 3)) {
				this.style.cursor = 'e-resize';
			} else {
				this.style.cursor = 'inherit';
				this.resize = false;
			}
			//moving = {id: cur_id, offset: [e.clientX - parent_container_pos[0], e.clientY - parent_container_pos[1]]};
		});
		
		elm_box.addEventListener('mousedown', function(e) {
			if (this.resize) {
				
			}
		});
		
		elm_box.addEventListener('mouseout', function() {
			this.style.cursor = 'inherit';
		});
		
		var elm_result = document.createElement('div');		
		elm_result.setAttribute('class', 'result');

		elm_box.appendChild(elm_buttons);
		elm_box.appendChild(elm_result);
		
		elm_parent.appendChild(elm_box);
				
		worker.postMessage({
			dateinta_range: cur_dateinta_range,
			id: id,
			metric: metric
		});
	};
	
	var statusWorker = function(id, progress) {

		var elm_box = arr_metrics[id].elm;

		var elm_result = elm_box.getElementsByClassName('result')[0];
		if (elm_result.firstChild) {
			elm_result.removeChild(elm_result.firstChild);
		}
		
		var progress_text = document.createTextNode(progress + '%');
		elm_result.appendChild(progress_text);
		
	};
	
	var finishedWorker = function(id, data) {
		
		var elm_box = arr_metrics[id].elm;
		var metric = arr_metrics[id].metric;
		
		var elm_result = elm_box.getElementsByClassName('result')[0];
		if (elm_result.firstChild) {
			elm_result.removeChild(elm_result.firstChild);
		}
		
		var elm_list = document.createElement('ul');
		elm_result.appendChild(elm_list);

		if (metric == 'degree_centrality') {
			
			var arr_nodes = data.result;
			var len = Math.min(arr_nodes.length, 10);
			
			for (var i = 0; i < len; i++) {
				var elm_metric_result = document.createElement('li');
				elm_metric_result.innerHTML = '<span class="rank">'+(i+1)+'.</span><span class="name">'+arr_network_data.objects[arr_nodes[i].id].name+'</span><span class="result">'+arr_nodes[i].degree+'</span>';
				elm_list.appendChild(elm_metric_result);
			}
		}
		
		if (metric == 'weighted_degree_centrality') {
						
			var arr_nodes = data.result;
			var len = Math.min(arr_nodes.length, 10);
			
			for (var i = 0; i < len; i++) {
				var elm_metric_result = document.createElement('li');
				elm_metric_result.innerHTML = '<span class="rank">'+(i+1)+'.</span><span class="name">'+arr_network_data.objects[arr_nodes[i].id].name+'</span><span class="result">'+arr_nodes[i].weighted_degree+'</span>';
				elm_list.appendChild(elm_metric_result);
			}
		}
		
		if (metric == 'cooccurrences') {
			
			if (data.result.max) {
				arr_metrics[id].max = data.result.max;
			}
			
			var max = arr_metrics[id].max;
			var arr_sorted_clusters = data.result.arr_sorted_clusters;
			var len = Math.min(arr_sorted_clusters.length, 10);
			
			for (var i = 0; i < len; i++) {
				
				var arr_cluster = arr_sorted_clusters[i].arr_cluster_ids;
				var name = ' ';
				for (var j = 0; j < arr_cluster.length; j++) {
					name = arr_network_data.objects[arr_cluster[j]].name+' '+name;
				}
				
				var elm_metric_result = document.createElement('li');
				var elm_metric_result_bar = document.createElement('div');
				
				elm_metric_result_bar.style.width = ((arr_sorted_clusters[i].count/max)*100)+'%';
				elm_metric_result_bar.style.height = '10px';
				elm_metric_result_bar.style.backgroundColor = '#000';
				
				elm_metric_result.appendChild(elm_metric_result_bar);
				
				var elm_metric_result_text_container = document.createElement('div');	
				elm_metric_result_text_container.innerHTML = '<span class="rank">'+(i+1)+'.</span><span class="name">'+name+'</span><span class="result">'+arr_sorted_clusters[i].count+'</span>';
				elm_metric_result.appendChild(elm_metric_result_text_container);
				
				elm_list.appendChild(elm_metric_result);
			}
		}
		
	};
	
	var setNetworkData = function() {
		
		var arr_data = obj_parent.getData();
		arr_network_data = {objects: {}, links: {}, object_subs: {}, date: arr_data.date, range: arr_data.range, active_nodes: [], active_links: []};
		
		if (!arr_data.links) {
			
			var labSoc = new MapSocial(false, obj_parent, {});
			var arr_set_data = labSoc.setObjectsLinks(arr_data);
			
			arr_data_objects = arr_set_data.objects;
			arr_data_links = arr_set_data.links;
			arr_data_object_subs = arr_set_data.object_subs;
			
		} else {
			
			arr_data_objects = arr_data.objects;
			arr_data_links = arr_data.links;
			arr_data_object_subs = arr_data.object_subs;
			
		}
		
		for (var object_id in arr_data_objects) {
			
			arr_data_object = arr_data_objects[object_id];

			arr_network_data.objects[object_id] = {id: object_id, name: arr_data_object.name, object_parents: [], sub_object_parents: [], child_nodes: arr_data_object.child_nodes, child_links: arr_data_object.child_links, child_clusters: [], connect_object_sub_ids: arr_data_object.connect_object_sub_ids, node_position: false};
		}
		
		for (var link_id in arr_data_links) {
			
			arr_data_link = arr_data_links[link_id];
			
			arr_network_data.links[link_id] = {id: link_id, source: arr_network_data.objects[arr_data_link.source.id], target: arr_network_data.objects[arr_data_link.target.id], object_parents: [], sub_object_parents: [], value: arr_data_link.value, link_position: false};
		}
		
		for (var object_sub_id in arr_data_object_subs) {
			
			arr_data_object_sub = arr_data_object_subs[object_sub_id];

			arr_network_data.object_subs[object_sub_id] = {id: object_sub_id, date_start: arr_data_object_sub.date_start, date_end: arr_data_object_sub.date_end, object_id: arr_data_object_sub.object_id, child_links: arr_data_object_sub.child_links, child_nodes: arr_data_object_sub.child_nodes};
		}
		
		var dateinta_range = {min: DATEPARSER.dateInt2Absolute(arr_data.date_range.min), max: DATEPARSER.dateInt2Absolute(arr_data.date_range.max)};
		
		dateinta_range_full = dateinta_range;
	};
	
	this.init();
};
