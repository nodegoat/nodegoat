
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2022 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

var arr_network_data = false;
var dateinta_range = false;
var update_metrics = [];
var arr_nodes = [];
var arr_links = [];

onmessage = function(event) {

	if (event.data.dateinta_range) {
		dateinta_range = event.data.dateinta_range;
	}
	
	if (event.data.action) {
		
		var action = event.data.action;
		
		if (action == 'init') {
			arr_network_data = event.data.arr_network_data;
			updateData();
		}
		
		if (action == 'update') {
			
			if (update_metrics.length) {
				
				updateData();
			
				for (var i = 0; i < update_metrics.length; i++) {
					runMetric(update_metrics[i].metric, update_metrics[i].id);
				}
			}
		}
		
		if (action == 'add_metric') {
			
			update_metrics.push({id: event.data.id, metric: event.data.metric});
			
		}
		
		if (action == 'remove_metric') {
			
			for (var i = 0; i < update_metrics.length; i++) {
				
				if (update_metrics[i].id == event.data.id) {
					update_metrics.splice(i, 1);
				}
			}
			
		}
	}
	
	if (event.data.metric && event.data.id != 'undefined') {

		runMetric(event.data.metric, event.data.id);
		
	}

}

var runMetric = function(metric, id) {

	if (metric == 'frequency') {
		frequency(id);
	};
	
	if (metric == 'occurrences') {
		occurrences(id);
	};
	
	if (metric == 'cooccurrences') {
		cooccurrences(id);
	};
	
	if (metric == 'degree_centrality') {
		centrality_degree(id);
	};
	
	if (metric == 'weighted_degree_centrality') {
		weighted_centrality_degree(id);
	};
	
}
	
var frequency = function(id) {


	
};

var occurrences = function(id) {

	
};

var cooccurrences = function(id) {

	var set_max = false;

	if (!arr_network_data.clusters) {
		
		var arr_objects = arr_network_data.objects;
		var arr_object_subs = arr_network_data.object_subs;
		var arr_clusters = {};
		
		var total_iterations = Object.keys(arr_objects).length;
		var iterations = 0;
		set_max = true;

		for (var object_id in arr_objects) {

			iterations++;
			var arr_object = arr_objects[object_id];
			
			var cluster_id = false;
			var arr_cluster = [];

			if (arr_object.child_nodes) {
				
				for (var i = 0; i < arr_object.child_nodes.length; i++) {
					
					if (indexOfFor(arr_cluster, arr_object.child_nodes[i]) == -1) {
					
						arr_cluster.push(arr_object.child_nodes[i]);
						
						if (cluster_id) {
							
							cluster_id = cluster_id+arr_object.child_nodes[i];
							
						} else {
							
							cluster_id = arr_object.child_nodes[i]+'';
							
						}
					
					}
				}
			}
			
			
			if (arr_object.connect_object_sub_ids) {
				
				for (var i = 0; i < arr_object.connect_object_sub_ids.length; i ++) {
					
					var arr_object_sub = arr_object_subs[arr_object.connect_object_sub_ids[i]];
					
					if (arr_object_sub.child_nodes) {
						
						for (var j = 0; j < arr_object_sub.child_nodes.length; j++) {
							
							var child_object_id = arr_object_sub.child_nodes[j];
							
							if (child_object_id !== object_id) {
								
								if (indexOfFor(arr_cluster, child_object_id) == -1) {
									
									arr_cluster.push(child_object_id);
									
									if (cluster_id) {
										
										cluster_id = cluster_id+child_object_id;
										
									} else {
										
										cluster_id = child_object_id+'';
										
									}
								}
							}
						}
					}

				}
			}
			
			if (cluster_id) {	
				
				if (arr_clusters[cluster_id]) {
					
					arr_clusters[cluster_id].count++;
					arr_clusters[cluster_id].total++;
					
				} else {
				
					arr_clusters[cluster_id] = {id: cluster_id, count: 1, total: 1, arr_cluster_ids: arr_cluster};
					
				}
				
				arr_object.child_clusters.push(cluster_id);
				
				
			}
			
			postMessage({type: "status", progress: Math.round((iterations / total_iterations) * 100), id: id});
			
		}
		
		arr_network_data.clusters = arr_clusters;
	}
	
	
	
	var arr_sorted_clusters = [];
	
	if (set_max) {
		
		var max = 0;
		
		for (var cluster_id in arr_network_data.clusters) {
			max = Math.max(arr_network_data.clusters[cluster_id].count, max);
		}
	}

	updateData();
	
	for (var cluster_id in arr_network_data.clusters) {
		
		if (arr_network_data.clusters[cluster_id].count) {
			arr_sorted_clusters.push(arr_network_data.clusters[cluster_id]);
		}
	}
	
	arr_sorted_clusters.sort(function(a,b) {
		return b.count - a.count;
	});


	postMessage({type: "end", result: {arr_sorted_clusters: arr_sorted_clusters, max: max}, id: id});
	
};

var centrality_degree = function(id) {

	var total_iterations = arr_links.length + arr_nodes.length;
	var iterations = 0;
	
	var total_degree = 0;
	
	for (var i = 0, len = arr_links.length; i < len; i++) {

		arr_nodes[arr_links[i].target.node_position].in_degree ? arr_nodes[arr_links[i].target.node_position].in_degree++ : arr_nodes[arr_links[i].target.node_position].in_degree = 1;
		arr_nodes[arr_links[i].source.node_position].out_degree ? arr_nodes[arr_links[i].source.node_position].out_degree++ : arr_nodes[arr_links[i].source.node_position].out_degree = 1;
		
		total_degree++;
		
		postMessage({type: "status", progress: Math.round((iterations / total_iterations) * 100), id: id});
		iterations++;

	}
	
	for (var i = 0, len = arr_nodes.length; i < len; i++) {
		
		!arr_nodes[i].in_degree ? arr_nodes[i].in_degree = 0 : '';
		!arr_nodes[i].out_degree ? arr_nodes[i].out_degree = 0 : '';
		
		arr_nodes[i].degree = Math.round(((arr_nodes[i].in_degree + arr_nodes[i].out_degree) / total_degree) * 100) / 100;
		
		arr_nodes[i].in_degree = Math.round((arr_nodes[i].in_degree / (total_degree / 2)) * 100) / 100;
		arr_nodes[i].out_degree = Math.round((arr_nodes[i].out_degree / (total_degree / 2)) * 100) / 100;

		postMessage({type: "status", progress: Math.round((iterations / total_iterations) * 100), id: id});
		iterations++;			
	}
	
	var arr_sorted_nodes = [];
	
	for (var i = 0, len = arr_nodes.length; i < len; i++) {
		arr_sorted_nodes.push(arr_nodes[i]);		
	}
	
	arr_sorted_nodes.sort(function(a,b) {
		return b.degree - a.degree;
	});

	postMessage({type: "end", result: arr_sorted_nodes, id: id});
	
};


var weighted_centrality_degree = function(id) {
	
	var total_iterations = arr_links.length + arr_nodes.length;
	var iterations = 0;
	
	var total_weighted_degree = 0;
	
	for (var i = 0, len = arr_links.length; i < len; i++) {

		var value = arr_links[i].value;
		arr_nodes[arr_links[i].target.node_position].in_weighted_degree ? arr_nodes[arr_links[i].target.node_position].in_weighted_degree += value : arr_nodes[arr_links[i].target.node_position].in_weighted_degree = value;
		arr_nodes[arr_links[i].source.node_position].out_weighted_degree ? arr_nodes[arr_links[i].source.node_position].out_weighted_degree += value : arr_nodes[arr_links[i].source.node_position].out_weighted_degree = value;
		
		total_weighted_degree += value;
		
		postMessage({type: "status", progress: Math.round((iterations / total_iterations) * 100), id: id});
		iterations++;

	}
	
	for (var i = 0, len = arr_nodes.length; i < len; i++) {
		
		!arr_nodes[i].in_weighted_degree ? arr_nodes[i].in_weighted_degree = 0 : '';
		!arr_nodes[i].out_weighted_degree ? arr_nodes[i].out_weighted_degree = 0 : '';
		
		arr_nodes[i].weighted_degree = Math.round(((arr_nodes[i].in_weighted_degree + arr_nodes[i].out_weighted_degree) / total_weighted_degree) * 100) / 100;
		
		arr_nodes[i].in_weighted_degree = Math.round((arr_nodes[i].in_weighted_degree / (total_weighted_degree / 2)) * 100) / 100;
		arr_nodes[i].out_weighted_degree = Math.round((arr_nodes[i].out_weighted_degree / (total_weighted_degree / 2)) * 100) / 100;

		postMessage({type: "status", progress: Math.round((iterations / total_iterations) * 100), id: id});
		iterations++;			
	}
	
	var arr_sorted_nodes = [];
	
	for (var i = 0, len = arr_nodes.length; i < len; i++) {
		arr_sorted_nodes.push(arr_nodes[i]);		
	}
	
	arr_sorted_nodes.sort(function(a,b) {
		return b.weighted_degree - a.weighted_degree;
	});
	
	postMessage({type: "end", result: arr_sorted_nodes, id: id});
	
};


var updateData = function() {

	var setCheckObjectSubs = function() {

		// Single date sub objects
		for (var i = 0, len = arr_network_data.date.arr_loop.length; i < len; i++) {
			
			var date = arr_network_data.date.arr_loop[i];
			var dateinta = DATEPARSER.dateInt2Absolute(date);
			var in_range = (dateinta >= dateinta_range.min && dateinta <= dateinta_range.max);
			var arr_object_subs = arr_network_data.date[date];

			for (var j = 0; j < arr_object_subs.length; j++) {					
				checkObjectSub(arr_object_subs[j], !in_range);
			}
		}
		
		// Sub objects with a date range
		for (var i = 0, len = arr_network_data.range.length; i < len; i++) {
			
			var arr_object_sub = arr_network_data.object_subs[arr_network_data.range[i]];
			
			var dateinta_start = DATEPARSER.dateInt2Absolute(arr_object_sub.date_start);
			var dateinta_end = DATEPARSER.dateInt2Absolute(arr_object_sub.date_end);
			
			var in_range = ((dateinta_start >= dateinta_range.min && dateinta_start <= dateinta_range.max) || (dateinta_end >= dateinta_range.min && dateinta_end <= dateinta_range.max) || (dateinta_start < dateinta_range.min && dateinta_end > dateinta_range.max));
					
			checkObjectSub(arr_network_data.range[i], !in_range);
		}

	};

	var checkObjectSub = function(object_sub_id, remove) {

		var object_sub = arr_network_data.object_subs[object_sub_id],
		count_object_sub_child_nodes = object_sub.child_nodes.length,
		count_object_sub_child_links = object_sub.child_links.length;

		arr_remove_links = [];
			
		while (count_object_sub_child_nodes--) {
			
			var active = false,
			object_id = object_sub.child_nodes[count_object_sub_child_nodes],
			arr_object = arr_network_data.objects[object_id],
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
						update = true;					
						if (arr_sub_object_parents.length == 0 && arr_object_parents.length == 0) {
							removeNode(arr_object);
						}
					}
				}
			} else {
				if (parent_pos == -1) {
					update = true;
					arr_sub_object_parents.push(object_sub_id);		
					if (!active) {
						addNode(arr_object, true);
					} else {
						addNode(arr_object, false);
					}
				}
			}				
		}

		while (count_object_sub_child_links--) {
			
			var active = false,
			link_id = object_sub.child_links[count_object_sub_child_links],
			arr_link = arr_network_data.links[link_id],
			arr_sub_object_parents = arr_link.sub_object_parents,
			arr_object_parents = arr_link.object_parents;
			
			if (arr_link) {
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
							var pos = arr_links.push(arr_link);
							arr_link.link_position =  pos - 1;
						}
					}
				}
			}			
		}
	}
	
	var addNode = function(arr_object, add) {

		if (add) {
			
			var pos = arr_nodes.push(arr_object);
			
			arr_object.node_position = pos - 1;
			
			if (arr_object.child_clusters.length) {
				
				for (var i = 0; i < arr_object.child_clusters.length; i++) {
					
					arr_network_data.clusters[arr_object.child_clusters[i]].count++;
					
				}
			}
		}
		
		if (arr_object.child_nodes && arr_object.child_nodes.length) {
			
			for (var i = 0; i < arr_object.child_nodes.length; i++) {
				
				var object_child_id = arr_object.child_nodes[i],
				arr_object_child = arr_network_data.objects[object_child_id];
				
				var parent_pos = indexOfFor(arr_object_child.object_parents, arr_object.id);
				
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
				arr_link = arr_network_data.links[link_id];
				
				if (arr_link) {
					
					if (arr_link.sub_object_parents.length || arr_link.object_parents.length) {
						var active = true;
					}
					
					var parent_pos = indexOfFor(arr_link.object_parents, arr_object.id);
					
					if (parent_pos == -1) {
						
						arr_link.object_parents.push(arr_object.id);
						
						if (!active) {
							var pos = arr_links.push(arr_link);
							arr_link.link_position = pos - 1;
						}
					}
				}
			}
		}			
	}
	
	var updateNode = function(arr_object, parent_id, remove) {

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
		}

	}
	
	var removeNode = function(arr_object) {
		
		var cur_pos = arr_object.node_position;
		
		if (cur_pos == arr_nodes.length - 1) {
			arr_nodes.pop();
		} else {
			
			arr_nodes[cur_pos] = arr_nodes.pop();
			var arr_repositioned_node = arr_network_data.objects[arr_nodes[cur_pos].id];
			arr_repositioned_node.node_position = cur_pos;
		}
		
		if (arr_object.child_nodes && arr_object.child_nodes.length) {
			
			for (var i = 0; i < arr_object.child_nodes.length; i++) {
				
				var arr_object_child = arr_network_data.objects[arr_object.child_nodes[i]];
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
		
		if (arr_object.child_links && arr_object.child_links.length) {
			
			for (var i = 0; i < arr_object.child_links.length; i++) {
				
				var active = false,
				link_id = arr_object.child_links[i],
				arr_link = arr_network_data.links[link_id];
				
				if (arr_link.object_parents.length) {
					var parent_pos = indexOfFor(arr_link.object_parents, arr_object.id);
					if (parent_pos > -1) {
						arr_link.object_parents.splice(parent_pos, 1);
						removeLink(arr_link);
					}
				}
			}
		}
		
		
		if (arr_object.child_clusters.length) {
			
			for (var i = 0; i < arr_object.child_clusters.length; i++) {
				
				arr_network_data.clusters[arr_object.child_clusters[i]].count--;
			}
		}
		
	}
	
	var removeLink = function(arr_link) {
		
		if (arr_link.sub_object_parents.length == 0 && arr_link.object_parents.length == 0) {
			
			var cur_pos = arr_link.link_position;
			
			if (cur_pos == arr_links.length - 1) {
				arr_links.pop();
			} else {
				arr_links[cur_pos] = arr_links.pop();
				arr_network_data.links[arr_links[cur_pos].id].link_position = cur_pos;
			}
		}
		
	}
	
	setCheckObjectSubs();
}

var indexOfFor = function(array, check) {
	
	for (var i = 0, len = array.length; i < len; i++) {
		
		if (array[i] == check) {
			return i;
		}
	}
	
	return -1;
};
