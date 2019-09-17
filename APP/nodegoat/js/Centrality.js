
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

function Centrality(graph, object_parent, kind, max_link_value) {
	
	var kind = (kind || 'degree').toLowerCase();
	
	this.init = function() {
		switch (kind) {
			case "degree":
				degree('both');
				break;
			case "indegree":
				degree('in');
				break;
			case "outdegree":
				degree('out');
				break;
			case "betweenness":
				betweenness(false, false);
				break;
			case "betweenness directed":
				betweenness(true, false);
				break;
			case "betweenness weighted":
				betweenness(false, max_link_value);
				break;
			case "betweenness weighted directed":
				betweenness(true, max_link_value);
				break;
			case "pagerank":
				pageRank();
				break;
			default:
				false;
		};
	};
	
	var singleSourceShortestPathWeighted = function (nodeId, directed, total_link_value) {

		var distance = {},
			previous = {},
			sigma = {},
			queue = [], 
			stack = [],
			seen = {},
			count = 0;
		
		for (var node in graph.nodes) {
			previous[node] = [];
			sigma[node] = 0;
		}			
		
		sigma[nodeId] = 1;
		seen[nodeId] = 0;
		
		queue.push({distance : 0, predecessor : nodeId, v : nodeId});
		
		while (queue.length) {
			
			object_v = queue.pop();
			
			if (distance.hasOwnProperty(object_v.v)) {
				continue
			}
			
			sigma[object_v.v] += sigma[object_v.predecessor];
			stack.push(object_v.v);
			distance[object_v.v] = object_v.distance;
			
			for (i = 0; i < graph.links.length; i++) {
				var link = graph.links[i],
				w = false,
				vw_dist;
				
				if (directed === true && link.source === object_v.v) {
					w = link.target;
				} else if (directed === false && link.target === object_v.v || link.source === object_v.v) {
					if (link.target === object_v.v) {
						w = link.source;
					} else {
						w = link.target;
					}
				} else {
					w = false;
				}
				
				vw_dist = object_v.distance; + link.value;
				
				if (distance.hasOwnProperty(w)) {
					continue;
				}
				
				if (!seen.hasOwnProperty(w) || vw_dist < seen[w]) {
					seen[w] = vw_dist;
					queue.push({distance : vw_dist, predecessor : object_v.v, v : w});
					sigma[w] = 0;
					previous[w] = [object_v.v]
				} else if (vw_dist === seen[w]) {
					sigma[w] += sigma[object_v.v];
					previous[w].push(object_v.v);
				}
			}			
		}

		return {
			S : stack,
			P : previous,
			sigma : sigma
		};

	};
	
	var singleSourceShortestPath = function (nodeId, directed) {
		// http://www.inf.uni-konstanz.de/algo/publications/b-fabc-01.pdf
		var P = {}, // predecessors lists.
			S = [], // Stack
			sigma = {}, // accumulation for everytime a node is on a shortest path
			d = {}, // distance to reach node from starting point per node
			Q = [nodeId]; // BFS queue
		
		for (var node in graph.nodes) {
			P[node] = [];
			sigma[node] = 0;
		}		
		
		d[nodeId] = 0; // start with zero distance
		sigma[nodeId] = 1; // start with sigma of zero
		
		// Using BFS to find shortest paths http://en.wikipedia.org/wiki/Breadth-first_search
		while (Q.length) { 
			var v = Q.shift(), // get nodeID as vertice and remove from queue
			dV = d[v], // now get current distance, starts with 0, grows as number of loops grows
			sigmaV = sigma[v];
			S.push(v);
			for (var i = 0; i < graph.links.length; i++) {
				var link = graph.links[i];
				var w = false;
				// Loop through all links of this node
				if (directed === true && link.source === v) {
					w = link.target;
				} else if (directed === false && link.target === v || link.source === v) {
					if (link.target === v) {
						w = link.source;
					} else {
						w = link.target;
					}
				} else {
					w = false;
				}
				
				if (w) {
					// w found for the first time
					if (!d.hasOwnProperty(w)) {
						Q.push(w); // add to the BFS queue
						d[w] = dV + 1; // add 1 to total distance from v to w
					}
					
					// Shortest path to w via v?
					if (d[w] === dV + 1) {
						sigma[w] += sigmaV; // sigma of node v is added to sigma of node w 							
						P[w].push(v); // add node v to list of node w
					} 		
				}			
			}
		}
		
		return {
			S : S,
			P : P,
			sigma : sigma
		};
	};
	
	var betweenness = function(directed, max_link_value) {

		var betweenness = {},
			shortestPath,
			betweennessTotal = 0,
			nodeCount = 0,
			arr_nodes = [],
			node,
			result = {},
			iterations,
			max = 0;
			
		for (node in graph.nodes) {
			betweenness[graph.nodes[node].id] = 0;
			nodeCount++;
			arr_nodes.push(graph.nodes[node]); 
		}
		
		// if a max link value has been set, the weight of the links is inlcuded in 
		// the calculation of the shortest paths
		if (max_link_value) {
			for (var i = 0; i < graph.links.length; i++) {
				graph.links[i].value = max_link_value - graph.links[i].value;
			}
		}
		
		var finish = function(iterations) {
			for (node in betweenness) {
				if (!isNaN(parseFloat(betweenness[node]))) {
					// Normalisation to values 0-1
					// If nondirected, divide results by 2, see http://en.wikipedia.org/wiki/Betweenness_centrality#Definition
					result[node] = directed ? parseFloat(betweenness[node]/betweennessTotal) : parseFloat(betweenness[node]/betweennessTotal/2);
					
					if (result[node] > max) {
						max = result[node];
					}
				}
			}
			object_parent.processCentrality(result, max);
			object_parent.processCentralityProgress(100, true);
		}
		
		
		var betweennessCompute = function(iterations) {

			var i = 0;
			while (iterations > 0 && i < 10) {
				iterations--;
				i++;
				node = arr_nodes.shift();
				shortestPath = max_link_value ? singleSourceShortestPathWeighted(node.id, directed) : singleSourceShortestPath(node.id, directed),
				delta = {},
				S = shortestPath.S; // array of all vertices
				
				for (var j = 0; j < S.length; j++) {
					delta[S[j]] = 0;
				}
				
				// S returns vertices in order of non-increasing distance from s
				while (S.length) {
					var w = S.pop(), // get vetice farthest away from s
					coeff = (1 + delta[w]) / shortestPath.sigma[w],
					pW = shortestPath.P[w]; // all predecessors
				
					for (var j = 0; j < pW.length; j++) {
						var v = pW[j];
						delta[v] += shortestPath.sigma[v] * coeff;
					}
					
					if (w !== node.id) {
						betweenness[w] += delta[w];
						betweennessTotal += delta[w];
					}
				}
			}

			if (iterations > 0) {
				setTimeout(function () {
					object_parent.processCentralityProgress(100-(iterations/nodeCount*100));
					betweennessCompute(iterations);
				}, 0);
			} else {
				finish(); 
			}
		}

		betweennessCompute(nodeCount); 
		
	};
			
	var pageRank = function() {

		var node,
		outbound = {},
		pageRank = {},
		tempRank = {},
		nodeCount = 0,
		i,
		j,
		outboundCount,
		total,
		iterations = 1000,
		max = 0;
		
		for (node in graph.nodes) {
			outbound[graph.nodes[node].id] = [];
			tempRank[graph.nodes[node].id] = 0;
			nodeCount++;
		}			
		
		for (node in graph.nodes) {
			pageRank[graph.nodes[node].id] = 1/nodeCount;
		}
		
		for (i = 0; i < graph.links.length; i++) {
			var link = graph.links[i];
			outbound[link.source].push(link.target);
		}
		
		if (graph.links.length < iterations) {
			iterations = graph.links.length;
		}
		
			
		i = 0;
		while (i < graph.links.length) {
			i++;
		
			for (node in outbound) {
				outboundCount = outbound[node].length;
				for (j = 0; j < outboundCount; j++) {
					tempRank[outbound[node][j]] += pageRank[node] / outboundCount;
				}
			}
			
			total = 0;
			for (node in outbound) { 
				pageRank[node] = tempRank[node];
				tempRank[node] = 0;
				total += pageRank[node];
			}
			
			for (node in pageRank) {
				// Normalisation to values 0-1
				pageRank[node] = pageRank[node]/total;
				
				// Set max
				if (pageRank[node] > max) {
					max = pageRank[node];
				}
			}
		
		}
		
		object_parent.processCentrality(pageRank, max);
	};
			
	var degree = function(direction) {
		var calcDegFunction,
			sortedDegrees = [],
			result = {},
			degree,
			direction = (direction || 'both').toLowerCase(),
			links,
			i,
			count,
			total = 0,
			max = 0;
			
		if (direction === 'in') {
			calcDegFunction = function (links, nodeId) {
				count = 0;
				for (i = 0; i < links.length; i += 1) {
					count += (links[i].target === nodeId) ? 1 : 0;
					total += (links[i].target === nodeId) ? 1 : 0;
				}
				return count;
			};
		} else if (direction === 'out') {
			calcDegFunction = function (links, nodeId) {
				count = 0;
				for (i = 0; i < links.length; i += 1) {
					count += (links[i].source === nodeId) ? 1 : 0;
					total += (links[i].source === nodeId) ? 1 : 0;
				}
				return count;
			};
		} else if (direction === 'both') {
			calcDegFunction = function (links) {
				total = total + links.length; 
				return links.length;
			};
		}
		
		for (node in graph.nodes) {
			var checknode = graph.nodes[node],
			links = [];
			
			for (var i = 0; i < graph.links.length; ++i) {
				var link = graph.links[i];
				if (link.target === checknode.id || link.source === checknode.id) {
					links.push(link);
				}
			}
			
			result[checknode.id] = calcDegFunction(links, checknode.id);
		}
		
		for (node in result) {
			// Normalisation to values 0-1
			result[node] = result[node]/total;
			
			// Set max
			if (result[node] > max) {
				max = result[node];
			}
		}
		
		object_parent.processCentrality(result, max);
	};
	
	
	this.init();

};
