
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2023 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

#include <iostream>
#include <sstream>
#include <algorithm>
#include <vector>
#include <string>
#include <limits>
#include <unordered_map>
#include <tuple>
#include <iomanip>

#include <boost/property_map/property_map.hpp>
#include <boost/graph/graph_traits.hpp>
#include <boost/graph/adjacency_list.hpp>
#include <boost/graph/iteration_macros.hpp>
#include <boost/graph/properties.hpp>
#include <boost/graph/undirected_graph.hpp>

#include <boost/graph/breadth_first_search.hpp>

#include <boost/graph/betweenness_centrality.hpp>

#include <boost/graph/dijkstra_shortest_paths.hpp>

#include <boost/graph/clustering_coefficient.hpp>

#include <boost/graph/eccentricity.hpp>

#include <boost/graph/page_rank.hpp>

#include <boost/graph/exterior_property.hpp>
#include <boost/graph/floyd_warshall_shortest.hpp>
#include <boost/graph/johnson_all_pairs_shortest.hpp>
#include <boost/graph/closeness_centrality.hpp>

#include "include/betweenness_centrality_filtered.hpp"

// https://github.com/boostorg/graph_parallel/tree/master/test

namespace LAB1100CC {
	
	using type_mode = unsigned short;
	
	const type_mode MODE_NONE = 0;
	const type_mode MODE_ABSOLUTE = 1;
	const type_mode MODE_RELATIVE = 2;
	const type_mode MODE_NORMALISED = 3;
	
	const type_mode WEIGHTED_UNWEIGHTED = 0;
	const type_mode WEIGHTED_CLOSENESS = 1;
	const type_mode WEIGHTED_DISTANCE = 2;
	
	const type_mode CLOSENESS_CENTRALITY = 1;
	const type_mode CLOSENESS_ECCENTRICITY = 2;
	
	const unsigned int OUTPUT_DECIMALS = std::pow(10, 9);
	
	class GraphAnalysis {
		
		protected:

			using type_number_primary = double;
			using type_number_secondary = double;
			
			using type_weight = unsigned int;
			
			struct Node {
				Node () { }
				Node (const std::string& name) : name(name) { }
				
				std::string name;
			};
			
			struct Power {
				Power () { }
				Power (const std::string& name, type_weight weight) : name(name), weight(weight) { }
				
				std::string name;
				type_weight weight;
			};
			
			using type_graph = boost::adjacency_list<boost::vecS, boost::vecS, boost::directedS, Node, Power>;
			
			using type_vertex = boost::graph_traits<type_graph>::vertex_descriptor;
			using type_edge = boost::graph_traits<type_graph>::edge_descriptor;
			
			using type_map_vertex_index = boost::property_map<type_graph, boost::vertex_index_t>::type;
		
			type_graph g;
			
			type_map_vertex_index map_index = boost::get(boost::vertex_index, g);
			
			using type_node_settings = std::tuple<type_vertex, bool, type_number_primary, type_number_secondary>;
			std::unordered_map<std::string, type_node_settings> map_identifiers_node;
			
			using type_edge_settings = std::tuple<std::string, std::string, type_weight>;
			std::unordered_map<std::string, type_edge_settings> map_identifiers_from_identifiers_to;
			
			std::unordered_map<std::string, type_number_primary[2]> map_identifier_number;

		public:
		
			bool is_dense = false;
			
			unsigned int num_nodes;
			unsigned int num_edges;
			
			unsigned int num_count_primary = 0;
			unsigned int num_count_secondary = 0;
			
			type_mode mode_weighted = WEIGHTED_UNWEIGHTED;
			unsigned int num_weight_min = 0;
			unsigned int num_weight_max = 0;
			unsigned int num_weight_limit_max = 0;
			
			void setWeighted(const type_mode& mode, const type_weight& num_max = 0) {
				
				mode_weighted = mode;
				num_weight_limit_max = num_max;
			}

			void build(const char* str) {
				
				std::istringstream ss(str);

				std::string str_line;
				bool header_found = false;

				using type_identifiers = std::vector<std::pair<std::string, type_weight>>;
				std::unordered_map<std::string, type_identifiers> map_identifiers_from_identifiers_to_resolve;
				
				const type_weight num_weight_default = 1;
				
				// Parse lines into nodes (map_identifiers_node) and edges (map_identifiers_from_identifiers_to)

				while (std::getline(ss, str_line, '\n')) {
					
					if (!header_found) { // Skip the header
						
						header_found = true;
						continue;
					}
					
					const auto& arr_cells = LAB1100CCShed::explode(str_line, ",");
					
					//str_edge = arr_cells[0];
					
					const auto& str_identifier_from = arr_cells[1];
					
					if (map_identifiers_node.find(str_identifier_from) == map_identifiers_node.end()) { // Add primary node if not exists
												
						map_identifiers_node[str_identifier_from] = std::make_tuple(0, true, std::numeric_limits<type_number_primary>::quiet_NaN(), std::numeric_limits<type_number_secondary>::quiet_NaN());
					} else {
						
						type_node_settings& arr_node_settings = map_identifiers_node[str_identifier_from];
						
						std::get<1>(arr_node_settings) = true; // Set to primary node
					}
					
					const auto& str_identifier_to = arr_cells[2];
					
					std::string str_identifier = str_identifier_from + str_identifier_to;
					type_weight num_weight = num_weight_default;
					
					try {
						num_weight = std::stoi(arr_cells.at(3));
					} catch (std::out_of_range const& exc) {
						// No weight available
					}

					if (map_identifiers_from_identifiers_to.find(str_identifier) != map_identifiers_from_identifiers_to.end()) { // Edge exists, add weight
						
						auto& arr_edge_settings = map_identifiers_from_identifiers_to[str_identifier];
						
						std::get<2>(arr_edge_settings) += num_weight;
					} else {
						
						// Check wether the edge is of different Types, if different, add it to a map to resolve the edges
						
						int count = 0;
						bool is_different_type = false;
						
						for (auto n : str_identifier_from) {

							if (n == '-') {
								break;
							}
							
							if (n != str_identifier_to[count]) {
								
								is_different_type = true;
								break;
							}
							
							count++;
						}
						
						if (is_different_type) {
							
							//map_identifiers_from_identifiers_to_resolve[str_identifier_to].push_back(std::make_pair(str_identifier_from, num_weight));
							map_identifiers_from_identifiers_to_resolve[str_identifier_to].emplace_back(str_identifier_from, num_weight);
						} else {
						
							if (map_identifiers_node.find(str_identifier_to) == map_identifiers_node.end()) { // Add connection node if not exists
														
								map_identifiers_node[str_identifier_to] = std::make_tuple(0, false, std::numeric_limits<type_number_primary>::quiet_NaN(), std::numeric_limits<type_number_secondary>::quiet_NaN());
							}

							// Store the edge
							
							map_identifiers_from_identifiers_to[str_identifier] = std::make_tuple(str_identifier_from, str_identifier_to, num_weight);
						}
					}
				}
				
				// Resolve edges without nodes when edges relate to a different Type
								
				for (const auto& arr_identifiers : map_identifiers_from_identifiers_to_resolve) {
					
					for (const auto& arr_identifier_from : arr_identifiers.second) {
						
						const auto& str_identifier_from = arr_identifier_from.first;
						
						for (const auto& arr_identifier_to : arr_identifiers.second) {
							
							const auto& str_identifier_to = arr_identifier_to.first;
							
							if (str_identifier_from == str_identifier_to) {
								continue;
							}
							
							const auto& num_weight = arr_identifier_to.second;
							
							std::string str_identifier = str_identifier_from + str_identifier_to;
							
							if (map_identifiers_from_identifiers_to.find(str_identifier) != map_identifiers_from_identifiers_to.end()) { // Edge exists, add weight
							
								auto& arr_edge = map_identifiers_from_identifiers_to[str_identifier];
								
								std::get<2>(arr_edge) += num_weight;
							} else {
								
								// Store the edge
								
								map_identifiers_from_identifiers_to[str_identifier] = std::make_tuple(str_identifier_from, str_identifier_to, num_weight);
							}
						}
					}
				}
				
				num_nodes = map_identifiers_node.size();
				num_edges = map_identifiers_from_identifiers_to.size();
				
				if ((float)num_edges / ((float)num_nodes * ((float)num_nodes - 1)) > 0.5) {
					is_dense = true;
				}
				
				// Add nodes to the graph
				
				for (auto& map_node : map_identifiers_node) {
					
					const auto& str_identifier_from = map_node.first;
					auto& arr_node_settings = map_node.second;
						
					std::get<0>(arr_node_settings) = boost::add_vertex(str_identifier_from, g);
				}
				
				// Find minimum and maximum weight
				
				if (mode_weighted == WEIGHTED_UNWEIGHTED) {
					
					num_weight_min = 1;
					num_weight_max = 1;
				} else {
					
					for (const auto& map_edge : map_identifiers_from_identifiers_to) {
						
						const auto& num_weight = std::get<2>(map_edge.second);
						
						if (num_weight < num_weight_min || num_weight_min == 0) {
							
							num_weight_min = num_weight;
						}
						if (num_weight > num_weight_max) {
							
							num_weight_max = num_weight;
						}
					}
					
					if (num_weight_limit_max) {
						
						num_weight_max = num_weight_limit_max;
					}
				}
				
				// Add edges to the graph
				
				for (const auto& map_edge : map_identifiers_from_identifiers_to) {
					
					const auto& str_identifier_from = std::get<0>(map_edge.second);
					const auto& arr_vertex_from_settings = map_identifiers_node[str_identifier_from];

					const auto& str_identifier_to = std::get<1>(map_edge.second);
					const auto& arr_vertex_to_settings = map_identifiers_node[str_identifier_to];
					
					auto num_weight = std::get<2>(map_edge.second);
					
					if (mode_weighted == WEIGHTED_UNWEIGHTED) {
						
						num_weight = 1;
					} else { // WEIGHTED_CLOSENESS & WEIGHTED_DISTANCE
						
						if (num_weight > num_weight_max) {
							
							num_weight = num_weight_max;
						}
						
						if (mode_weighted == WEIGHTED_CLOSENESS) { // Reverse weight based on maximum weight
							
							num_weight = 1 + (num_weight_max - num_weight);
						}
					}
					
					boost::add_edge(std::get<0>(arr_vertex_from_settings), std::get<0>(arr_vertex_to_settings), Power("edge", num_weight), g);					
				}
			}
			
			std::string getStatistics() {
				
				std::string str_statistics = "{\"statistics\": {";
				
					str_statistics += "\"nodes\": "+std::to_string(num_nodes)+",";
					str_statistics += "\"edges\": "+std::to_string(num_edges)+",";
					str_statistics += "\"density\": \""+std::string((is_dense ? "dense" : "sparse"))+"\",";
					str_statistics += "\"weighted\": {\"mode\": \""+std::string((mode_weighted == WEIGHTED_CLOSENESS ? "closeness" : (mode_weighted == WEIGHTED_DISTANCE ? "distance" : "unweighted")))+"\", \"min\": "+std::to_string(num_weight_min)+", \"max\": "+std::to_string(num_weight_max)+"}";
				
				str_statistics += "}}";
				
				return str_statistics;
			}
						
			std::string getResults() {
				
				std::stringstream ss;
				
				ss << "{";

				const char* separator = "";
				
				for (const auto& arr_map : map_identifier_number) {
										
					ss << separator;
					
					ss << "\"" << arr_map.first << "\": [" << arr_map.second[0] << "," << arr_map.second[1] << "]";
					
					separator = ", ";
				}
				
				ss << "}";
				
				return ss.str();
			}
			
			void formatResults(const type_mode& mode_primary, const type_mode& mode_secondary = MODE_NONE) {
				
				type_number_primary num_primary_min;
				type_number_primary num_primary_max = 0;
				type_number_secondary num_secondary_min;
				type_number_secondary num_secondary_max = 0;
				
				for (const auto& arr_map : map_identifiers_node) {
					
					auto& arr_node_settings = arr_map.second;
					
					const bool& is_primary = std::get<1>(arr_node_settings);
					
					if (!is_primary) {
						continue;
					}
					
					type_number_primary num_primary = std::get<2>(arr_node_settings);
					type_number_secondary num_secondary = std::get<3>(arr_node_settings);
					
					if (std::isnan(num_primary)) {
						continue;
					}
					
					if (num_primary > num_primary_max || num_primary_max == 0) {
						num_primary_max = num_primary;
					}
					if (std::isnan(num_primary_min) || num_primary < num_primary_min) {
						num_primary_min = num_primary;
					}
					
					if (!std::isnan(num_secondary)) {

						if (num_secondary > num_secondary_max || num_secondary_max == 0) {
							num_secondary_max = num_secondary;
						}
						if (std::isnan(num_secondary_min) || num_secondary < num_secondary_min) {
							num_secondary_min = num_secondary;
						}
					}
					
					map_identifier_number[arr_map.first];
				}
																
				for (const auto& arr_map : map_identifier_number) {
					
					const auto& str_identifier_node = arr_map.first;
					const auto& arr_node_settings = map_identifiers_node[str_identifier_node];
					
					type_number_primary num_primary = std::get<2>(arr_node_settings);
					
					if (mode_primary != MODE_NONE) {
							
						if (mode_primary != MODE_ABSOLUTE) {

							if (mode_primary == MODE_RELATIVE) {
								
								num_primary = (num_primary / (type_number_primary)num_count_primary);
							} else if (mode_primary == MODE_NORMALISED) {

								if (num_primary_max == num_primary_min || num_primary == num_primary_max) {
									
									num_primary = 1;
								} else if (num_primary > 0) {
																			
									num_primary = ((num_primary - num_primary_min) / (num_primary_max - num_primary_min));

									if (num_primary < std::numeric_limits<type_number_primary>::min()) { // Large normalised datasets can get really close to 0, too close for floating point
										num_primary = std::numeric_limits<type_number_primary>::min();
									}
								}
							}

							num_primary = (std::ceil(num_primary * OUTPUT_DECIMALS) / OUTPUT_DECIMALS);
						}
					}
					
					type_number_secondary num_secondary = 0;
					
					if (mode_secondary != MODE_NONE) {
							
						num_secondary = std::get<3>(arr_node_settings);
						
						if (std::isnan(num_secondary)) {
							
							num_secondary = 0;
						} else {
							
							if (mode_secondary != MODE_ABSOLUTE) {
									
								if (mode_secondary == MODE_RELATIVE) {

									num_secondary = (num_secondary / (type_number_secondary)num_count_secondary);
								} else if (mode_secondary == MODE_NORMALISED) {
									
									if (num_secondary_max == num_secondary_min || num_secondary == num_secondary_max) {
										
										num_secondary = 1;
									} else if (num_secondary > 0) {
											
										num_secondary = ((num_secondary - num_secondary_min) / (num_secondary_max - num_secondary_min));

										if (num_secondary < std::numeric_limits<type_number_primary>::min()) { // Large normalised datasets can get really close to 0, too close for floating point
											num_secondary = std::numeric_limits<type_number_primary>::min();
										}
									}
								}
								
								num_secondary = (std::ceil(num_secondary * OUTPUT_DECIMALS) / OUTPUT_DECIMALS);
							}
						}
					}

					map_identifier_number[str_identifier_node][0] = num_primary;
					map_identifier_number[str_identifier_node][1] = num_secondary;
				}
			}
			
			// Algorithms
			
			void runBetweennessCentrality() {
				
				using type_centrality = type_number_primary;
				
				std::vector<type_centrality> arr_vertices_centrality(boost::num_vertices(g)); // To store centrality
				
				if (mode_weighted != WEIGHTED_UNWEIGHTED) {
					
					brandes_betweenness_centrality(g, boost::
						centrality_map(
							boost::make_iterator_property_map(arr_vertices_centrality.begin(), map_index)
						)
						.weight_map(boost::get(&Power::weight, g))
					);
				} else {
					
					brandes_betweenness_centrality(g, boost::
						centrality_map(
							boost::make_iterator_property_map(arr_vertices_centrality.begin(), map_index)
						)
					);
				}
				
				for (auto& arr_map : map_identifiers_node) {
						
					auto& arr_node_check = arr_map.second;
															
					const bool& is_primary = std::get<1>(arr_node_check);
					
					if (!is_primary) {
						continue;
					}
					
					const auto& vertex_check = std::get<0>(arr_node_check);
					
					const auto& num_centrality_found = arr_vertices_centrality[vertex_check];
					
					if (num_centrality_found == std::numeric_limits<type_centrality>::max()) { // Node distance is NaN of infinite
						continue;
					}
					
					std::get<2>(arr_node_check) = num_centrality_found;
				}
				
				num_count_primary = ((map_identifiers_node.size() - 1) * (map_identifiers_node.size() - 2));
			}

			void runCloseness(const type_mode& mode_closeness) {
				
				using type_distance = type_weight;
				using type_closeness = type_number_primary;
				
				using type_distance_property = boost::exterior_vertex_property<type_graph, type_distance>;
				using type_distance_matrix = typename type_distance_property::matrix_type;
				using type_distance_matrix_map = typename type_distance_property::matrix_map_type;
								
				type_distance_matrix arr_vertices_distance(boost::num_vertices(g)); // To store distances
				type_distance_matrix_map map_vertices_distances(arr_vertices_distance, g);
				
				std::vector<type_closeness> arr_closenesses(boost::num_vertices(g)); // To store centrality
				
				if (is_dense) {
					
					floyd_warshall_all_pairs_shortest_paths(g,
						map_vertices_distances,
						weight_map(boost::get(&Power::weight, g))
					);
				} else {
				
					johnson_all_pairs_shortest_paths(g,
						map_vertices_distances,
						weight_map(boost::get(&Power::weight, g))
					);
				}
				
				const type_distance& max_distance = boost::num_vertices(g);
				
				for (const auto& arr_map_from : map_identifiers_node) {
					
					for (const auto& arr_map_to : map_identifiers_node) {
						
						if (arr_map_from.first == arr_map_to.first) {
							continue;
						}
						
						auto& num_distance_found = map_vertices_distances[std::get<0>(arr_map_from.second)][std::get<0>(arr_map_to.second)];
						
						if (num_distance_found == std::numeric_limits<type_distance>::max()) {
							
							if (mode_closeness == CLOSENESS_CENTRALITY) {
								
								num_distance_found = max_distance; // Use total node count as value for disconnected paths
							} else { // Eccentricity
								
								num_distance_found = 0; // Ignore path when looking for eccentricity
							}
						}
					}
				}
				
				if (mode_closeness == CLOSENESS_CENTRALITY) {
					
					all_closeness_centralities(g,
						map_vertices_distances,
						boost::make_iterator_property_map(arr_closenesses.begin(), map_index)
					);
				} else { // Eccentricity
					
					all_eccentricities(g,
						map_vertices_distances,
						boost::make_iterator_property_map(arr_closenesses.begin(), map_index)
					);
				}
	
				for (auto& arr_map : map_identifiers_node) {
						
					auto& arr_node_check = arr_map.second;
															
					const bool& is_primary = std::get<1>(arr_node_check);
					
					if (!is_primary) {
						continue;
					}
					
					const auto& vertex_check = std::get<0>(arr_node_check);
					
					const auto& num_centrality_found = arr_closenesses[vertex_check];
					
					if (num_centrality_found == std::numeric_limits<type_closeness>::max() || num_centrality_found == std::numeric_limits<type_closeness>::infinity()) { // Node distance is NaN of infinite
						continue;
					}
					
					std::get<2>(arr_node_check) = num_centrality_found;
				}
			}
			
			void runClusteringCoefficient() {
				
				using type_coefficient = type_number_primary;
				
				std::vector<type_coefficient> arr_coefficients(boost::num_vertices(g)); // To store coefficients
				
				const auto& num_mean_coefficient = all_clustering_coefficients(g,
					boost::make_iterator_property_map(arr_coefficients.begin(), map_index)
				);

				for (auto& arr_map : map_identifiers_node) {
						
					auto& arr_node_check = arr_map.second;
															
					const bool& is_primary = std::get<1>(arr_node_check);
					
					if (!is_primary) {
						continue;
					}
					
					const auto& vertex_check = std::get<0>(arr_node_check);
					
					const auto& num_coefficient_found = arr_coefficients[vertex_check];
					
					if (num_coefficient_found == std::numeric_limits<type_coefficient>::max()) { // Node distance is NaN of infinite
						continue;
					}
					
					std::get<2>(arr_node_check) = num_coefficient_found;
				}
			}
			
			// https://github.com/boostorg/graph_parallel/blob/master/test/distributed_page_rank_test.cpp
			
			void runPagerank(const unsigned int& num_iterations, const float& num_damping) {
				
				using type_rank = type_number_primary;
				std::vector<type_rank> arr_ranks(boost::num_vertices(g)); // To store centrality
				
				page_rank(g, boost::make_iterator_property_map(arr_ranks.begin(), map_index), boost::graph::n_iterations(num_iterations), num_damping);
				
				for (auto& arr_map : map_identifiers_node) {
						
					auto& arr_node_check = arr_map.second;
															
					const bool& is_primary = std::get<1>(arr_node_check);
					
					if (!is_primary) {
						continue;
					}
					
					const auto& vertex_check = std::get<0>(arr_node_check);
					
					const auto& num_rank_found = arr_ranks[vertex_check];
					
					if (num_rank_found == std::numeric_limits<type_rank>::max()) { // Node distance is NaN of infinite
						continue;
					}
					
					std::get<2>(arr_node_check) = num_rank_found;
				}
			}
			
			using type_arr_vertices = std::vector<type_vertex>;
			
			template <typename typename_report>
			void walkPathsStep(const type_vertex& vertex_from, const type_vertex& vertex_to, const type_weight& num_distance_find, const type_arr_vertices& arr_vertices_distance, const type_graph& g, type_weight num_distance, type_arr_vertices& arr_vertices_path, const typename_report& callback) {
				
				arr_vertices_path.push_back(vertex_from);

				if (vertex_from == vertex_to) {
					
					callback(arr_vertices_path);
				} else {
					
					for (const auto& edge_out : boost::make_iterator_range(boost::out_edges(vertex_from, g))) {
					
						const auto& vertex_target = boost::target(edge_out, g);
						
						if (arr_vertices_path.end() == std::find(arr_vertices_path.begin(), arr_vertices_path.end(), vertex_target)) {
							
							const auto& num_weight = g[edge_out].weight;
							const auto& num_distance_check = arr_vertices_distance[vertex_target];
													
							if (num_distance_check >= (num_distance + num_weight) && (num_distance + num_weight) <= num_distance_find) {
								
								walkPathsStep(vertex_target, vertex_to, num_distance_find, arr_vertices_distance, g, (num_distance + num_weight), arr_vertices_path, callback);
							}
						}
					}
				}

				arr_vertices_path.pop_back();
			}

			template <typename typename_report>
			void walkPaths(const type_vertex& vertex_from, const type_vertex& vertex_to, const type_weight& num_distance_find, const type_arr_vertices& arr_vertices_distance, const type_graph& g, const typename_report& callback) {
				
				type_arr_vertices arr_vertices_path;
				
				walkPathsStep(vertex_from, vertex_to, num_distance_find, arr_vertices_distance, g, 0, arr_vertices_path, callback);
			}
	
			void runShortestPath(const std::vector<std::string>& arr_identifiers_from, const std::vector<std::string>& arr_identifiers_to = {}, const bool& do_betweenness_centrality = false) {
				
				std::vector<type_vertex> arr_nodes_from;
				std::vector<std::string> arr_identifiers_to_checked;
				using type_distance = type_weight;
				
				const bool has_target_nodes = !arr_identifiers_to.empty();
				
				for (const auto& str_identifier_to : arr_identifiers_to) {
					
					if (map_identifiers_node.find(str_identifier_to) == map_identifiers_node.end()) {
						continue;
					}
					
					arr_identifiers_to_checked.push_back(str_identifier_to);
				}
					
				for (const auto& str_identifier_from : arr_identifiers_from) {
					
					if (map_identifiers_node.find(str_identifier_from) == map_identifiers_node.end()) {
						continue;
					}
					
					auto& arr_node_settings = map_identifiers_node[str_identifier_from];
					
					type_arr_vertices arr_vertices_distance(boost::num_vertices(g)); // To store distances
					
					const auto& vertex_from = std::get<0>(arr_node_settings);
					arr_nodes_from.push_back(vertex_from);
					
					// Compute shortest paths from vertex_from to all vertices, and store the output in distances
					boost::dijkstra_shortest_paths(g, vertex_from, boost::
						distance_map(
							boost::make_iterator_property_map(arr_vertices_distance.begin(), map_index)
						).weight_map(boost::get(&Power::weight, g))
					);
										
					std::unordered_map<std::string, std::vector<std::string>> map_paths_nodes;
					
					for (auto& arr_map : map_identifiers_node) {

						const std::string& str_identifier_check = arr_map.first;
						auto& arr_node_check = arr_map.second;
																		
						// Do not look for other sources nodes
						if (std::find(arr_identifiers_from.begin(), arr_identifiers_from.end(), str_identifier_check) != arr_identifiers_from.end()) {
							continue;
						}
						
						// If looking for TO nodes, check if this one is needed
						if (has_target_nodes) {
							
							if (std::find(arr_identifiers_to_checked.begin(), arr_identifiers_to_checked.end(), str_identifier_check) == arr_identifiers_to_checked.end()) {
								continue;
							}
						}
						
						const auto& vertex_check = std::get<0>(arr_node_check);
						const auto& num_distance_found = arr_vertices_distance[vertex_check];
						
						if (num_distance_found == std::numeric_limits<type_distance>::max()) { // Node distance is NaN of infinite
							continue;
						}
						
						auto& num_distance = std::get<2>(arr_node_check);
						
						if (std::isnan(num_distance) || num_distance_found < num_distance) {
							
							num_distance = num_distance_found;
						}
					}
					
					auto& num_primary = std::get<2>(arr_node_settings);
					
					if (std::isnan(num_primary)) {
						num_primary = 0; // Default the FROM node to 0
					}
					
					if (do_betweenness_centrality) { // Include a secondary betweenness centrality
						
						auto& num_secondary = std::get<3>(arr_node_settings);
						
						if (std::isnan(num_secondary)) {
							num_secondary = 0; // Make sure the FROM node is touched to include in the results
						}
						
						if (has_target_nodes) { // Calculate a centrality based on a specific node selection FROM to an other specific node selection TO

							for (const auto& str_identifier_to : arr_identifiers_to_checked) {
								
								const auto& arr_node_settings = map_identifiers_node[str_identifier_to];
								const auto& vertex_to = std::get<0>(arr_node_settings);
								const auto& num_distance_find = arr_vertices_distance[vertex_to];
								
								if (num_distance_find == std::numeric_limits<type_distance>::max()) { // Node distance is NaN of infinite
									continue;
								}

								walkPaths(vertex_from, vertex_to, num_distance_find, arr_vertices_distance, g, [&](const type_arr_vertices& arr_vertices_path) {

									num_count_secondary++; // Count all paths that are touched
									
									type_vertex vertex_path = vertex_from;
									type_weight num_distance = 0;
									
									for (const auto& vertex_check : arr_vertices_path) {
										
										if (vertex_check == vertex_from || vertex_check == vertex_to) {
											continue;
										}
										
										const auto& str_identifier_check = g[vertex_check].name;
										auto& arr_node_check = map_identifiers_node[str_identifier_check];
										auto& num_primary = std::get<2>(arr_node_check);
										auto& num_secondary = std::get<3>(arr_node_check);

										const auto& edge_check = boost::edge(vertex_path, vertex_check, g).first;
										const auto& num_weight = g[edge_check].weight;
										
										num_distance += num_weight;

										if (std::isnan(num_primary) || num_distance < num_primary) {
											num_primary = num_distance;
										}
										
										if (std::isnan(num_secondary)) {
											num_secondary = 1;
										} else {
											num_secondary++;
										}
										
										vertex_path = vertex_check;
									}
								});
							}
						}
					}
				}
				
				if (do_betweenness_centrality) { // Include a secondary betweenness centrality
					
					if (!has_target_nodes) { // Calculate a centrality based on a specific node selection to all other nodes
						
						using type_centrality = type_number_primary;
						std::vector<type_centrality> arr_vertices_centrality(boost::num_vertices(g)); // To store centrality
						
						if (mode_weighted != WEIGHTED_UNWEIGHTED) {
							
							brandes_betweenness_centrality_filtered(g, arr_nodes_from, boost::
								centrality_map(
									boost::make_iterator_property_map(arr_vertices_centrality.begin(), map_index)
								)
								.weight_map(boost::get(&Power::weight, g))
							);
						} else {
							
							brandes_betweenness_centrality_filtered(g, arr_nodes_from, boost::
								centrality_map(
									boost::make_iterator_property_map(arr_vertices_centrality.begin(), map_index)
								)
							);
						}
						
						for (auto& arr_map : map_identifiers_node) {
							
							auto& arr_node_check = arr_map.second;	
							const bool& is_primary = std::get<1>(arr_node_check);
							
							if (!is_primary) {
								continue;
							}
							
							const auto& vertex_check = std::get<0>(arr_node_check);
							const auto& num_centrality_found = arr_vertices_centrality[vertex_check];
							
							if (num_centrality_found == std::numeric_limits<type_centrality>::max()) { // Node distance is NaN of infinite
								continue;
							}
							
							std::get<3>(arr_node_check) = num_centrality_found;
						}
						
						num_count_secondary = (arr_nodes_from.size() * (map_identifiers_node.size() - 1)); // Count all paths that are touched
					} else { // Cleanup of already performed calculations, clear of out of range nodes
						
						for (auto& arr_map : map_identifiers_node) {

							const std::string& str_identifier_check = arr_map.first;
							auto& arr_node_check = arr_map.second;
							
							auto& num_primary = std::get<2>(arr_node_check);
							auto& num_secondary = std::get<3>(arr_node_check);
							
							if (std::isnan(num_secondary)) {
								
								if (std::find(arr_identifiers_to_checked.begin(), arr_identifiers_to_checked.end(), str_identifier_check) == arr_identifiers_to_checked.end()) {
									num_primary = std::numeric_limits<type_number_primary>::quiet_NaN();
								}
							}
						}
					}
				}
			}
	};
}
