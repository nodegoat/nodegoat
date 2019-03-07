
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
 * 
 * nodegoat runs on 1100CC (http://lab1100.com/1100cc).
 *
 * See http://nodegoat.net/release for the latest version of nodegoat and its license.
 */

#include <sstream>
#include <vector>
#include <string>
#include <chrono>
#include <thread>

#include "rapidjson/document.h"
#include "rapidjson/pointer.h"

#include "server_http.hpp"

namespace LAB1100CC {
	
	using HttpServer = SimpleWeb::Server<SimpleWeb::HTTP>;
}

#include "program.hpp"
#include "shed.hpp"

#include "graph_analysis.hpp"

static void show_usage(const std::string name) {
	
    std::cerr << "Usage:" << std::endl
		<< "\t" << name << " <option(s)>" << std::endl
		<< "Options:" << std::endl
		<< "\t-h, --help\t\tShow this help message" << std::endl
		<< "\t-p, --port PORT\t\tSpecify the port" << std::endl
	;
}

namespace LAB1100CC {
	
	class Program {
		
		protected:
			
			static void handleSignal(const int signum) {
				
				exit(signum);
			}

		public:
				
			unsigned int port;
				
			void run() {

				// HTTP-server at (port) using 1 default thread
				HttpServer server;
				server.config.address = "127.0.0.1";
				server.config.port = port;
				server.config.timeout_content = 60*60;
				
				LAB1100CC::Jobs jobs;

				server.resource["^/graph/run_shortest_path/(.*)$"]["POST"] = [&jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
					
					LAB1100CC::Jobs::type_timeout timeout = 0;
					
					auto arr_settings = LAB1100CCShed::explode(request->path_match[1], "/");
					
					for (std::vector<int>::size_type i = 0; i < arr_settings.size(); i++) {
					
						if (arr_settings[i] == "timeout") {
							
							i++;
							timeout = std::atoi(arr_settings[i].c_str());
						}
					}
					
					std::thread work_thread([&jobs, response, request](const LAB1100CC::Jobs::type_identifier &identifier) {
						
						ProgramClient client(response);
						
						try {

							std::ostringstream ss;
							ss << request->content.rdbuf();
							
							rapidjson::Document document;
							document.Parse(ss.str().c_str());
							
							const rapidjson::Value* json_data = rapidjson::Pointer("/data").Get(document);
							const auto& str_data = json_data->GetString();
							const rapidjson::Value* json_nodes_start = rapidjson::Pointer("/settings/nodes/start").Get(document);
							const rapidjson::Value* json_nodes_end = rapidjson::Pointer("/settings/nodes/end").Get(document);
							const rapidjson::Value* json_mode = rapidjson::Pointer("/settings/betweenness_centrality_mode").Get(document);
							const auto& str_mode = std::string(json_mode->GetString());
							const rapidjson::Value* json_weighted_mode = rapidjson::Pointer("/settings/weighted/mode").Get(document);
							const auto& str_weighted_mode = std::string(json_weighted_mode->GetString());
							const rapidjson::Value* json_weighted_max = rapidjson::Pointer("/settings/weighted/max").Get(document);
							const auto& num_weighted_max = json_weighted_max->GetInt();
							
							std::vector<std::string> arr_identifiers_from;

							for (auto& node : json_nodes_start->GetArray()) {
																
								arr_identifiers_from.push_back(node.GetString());
							}
							
							std::vector<std::string> arr_identifiers_to;

							if (json_nodes_end != nullptr && json_nodes_end->IsArray()) {
								
								for (auto& node : json_nodes_end->GetArray()) {
									
									arr_identifiers_to.push_back(node.GetString());
								}
							}
							
							auto mode = LAB1100CC::MODE_NONE;
							
							if (str_mode == "absolute") {
								mode = LAB1100CC::MODE_ABSOLUTE;
							} else if (str_mode == "relative") {
								mode = LAB1100CC::MODE_RELATIVE;
							} else if (str_mode == "normalised") {
								mode = LAB1100CC::MODE_NORMALISED;
							}
							
							auto mode_weighted = LAB1100CC::WEIGHTED_UNWEIGHTED;
							
							if (str_weighted_mode == "closeness") {
								mode_weighted = LAB1100CC::WEIGHTED_CLOSENESS;
							} else if (str_weighted_mode == "distance") {
								mode_weighted = LAB1100CC::WEIGHTED_DISTANCE;
							}
							
							LAB1100CC::GraphAnalysis graph;
							
							graph.setWeighted(mode_weighted, num_weighted_max);
														
							graph.build(str_data);
							
							client.openChunk();

							client.sendChunk(graph.getStatistics());
							
							graph.runShortestPath(arr_identifiers_from, arr_identifiers_to, (mode == LAB1100CC::MODE_NONE ? false : true));
			
							graph.formatResults(LAB1100CC::MODE_NONE, mode);
							
							client.sendChunk(graph.getResults());
							
							client.closeChunk();
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
						
						jobs.remove(identifier);
					}, identifier);
					
					jobs.add(identifier, work_thread, timeout);
					
					work_thread.detach();
				};
				
				server.resource["^/graph/run_betweenness_centrality/(.*)$"]["POST"] = [&jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
					
					LAB1100CC::Jobs::type_timeout timeout = 0;
					
					auto arr_settings = LAB1100CCShed::explode(request->path_match[1], "/");
					
					for (std::vector<int>::size_type i = 0; i < arr_settings.size(); i++) {
					
						if (arr_settings[i] == "timeout") {
							
							i++;
							timeout = std::atoi(arr_settings[i].c_str());
						}
					}
					
					std::thread work_thread([&jobs, response, request](const LAB1100CC::Jobs::type_identifier &identifier) {
						
						ProgramClient client(response);
						
						try {
							
							std::ostringstream ss;
							ss << request->content.rdbuf();
							
							rapidjson::Document document;
							document.Parse(ss.str().c_str());
							
							const rapidjson::Value* json_data = rapidjson::Pointer("/data").Get(document);
							const auto& str_data = json_data->GetString();
							const rapidjson::Value* json_mode = rapidjson::Pointer("/settings/mode").Get(document);
							const auto& str_mode = std::string(json_mode->GetString());
							const rapidjson::Value* json_weighted_mode = rapidjson::Pointer("/settings/weighted/mode").Get(document);
							const auto& str_weighted_mode = std::string(json_weighted_mode->GetString());
							const rapidjson::Value* json_weighted_max = rapidjson::Pointer("/settings/weighted/max").Get(document);
							const auto& num_weighted_max = json_weighted_max->GetInt();
							
							auto mode = LAB1100CC::MODE_NONE;
							
							if (str_mode == "absolute") {
								mode = LAB1100CC::MODE_ABSOLUTE;
							} else if (str_mode == "relative") {
								mode = LAB1100CC::MODE_RELATIVE;
							} else if (str_mode == "normalised") {
								mode = LAB1100CC::MODE_NORMALISED;
							}
							
							auto mode_weighted = LAB1100CC::WEIGHTED_UNWEIGHTED;
							
							if (str_weighted_mode == "closeness") {
								mode_weighted = LAB1100CC::WEIGHTED_CLOSENESS;
							} else if (str_weighted_mode == "distance") {
								mode_weighted = LAB1100CC::WEIGHTED_DISTANCE;
							}
															
							LAB1100CC::GraphAnalysis graph;
							
							graph.setWeighted(mode_weighted, num_weighted_max);
							
							graph.build(str_data);
							
							client.openChunk();

							client.sendChunk(graph.getStatistics());
							
							graph.runBetweennessCentrality();
			
							graph.formatResults(mode);
							
							client.sendChunk(graph.getResults());
							
							client.closeChunk();
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
						
						jobs.remove(identifier);
					}, identifier);
					
					jobs.add(identifier, work_thread, timeout);
					
					work_thread.detach();
				};
				
				server.resource["^/graph/run_closeness/([^/]*)/(.*)$"]["POST"] = [&jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
					
					Jobs::type_timeout timeout = 0;
					
					auto arr_settings = LAB1100CCShed::explode(request->path_match[2], "/");
					
					for (std::vector<int>::size_type i = 0; i < arr_settings.size(); i++) {
					
						if (arr_settings[i] == "timeout") {
							
							i++;
							timeout = std::atoi(arr_settings[i].c_str());
						}
					}
					
					std::thread work_thread([&jobs, response, request](const LAB1100CC::Jobs::type_identifier &identifier) {
						
						ProgramClient client(response);
						
						try {
							
							std::ostringstream ss;
							ss << request->content.rdbuf();
							
							rapidjson::Document document;
							document.Parse(ss.str().c_str());
							
							const auto& str_mode_closeness = request->path_match[1];

							const rapidjson::Value* json_data = rapidjson::Pointer("/data").Get(document);
							const auto& str_data = json_data->GetString();
							const rapidjson::Value* json_mode = rapidjson::Pointer("/settings/mode").Get(document);
							const auto& str_mode = std::string(json_mode->GetString());
							const rapidjson::Value* json_weighted_mode = rapidjson::Pointer("/settings/weighted/mode").Get(document);
							const auto& str_weighted_mode = std::string(json_weighted_mode->GetString());
							const rapidjson::Value* json_weighted_max = rapidjson::Pointer("/settings/weighted/max").Get(document);
							const auto& num_weighted_max = json_weighted_max->GetInt();
							
							auto mode_closeness = LAB1100CC::CLOSENESS_CENTRALITY;
							
							if (str_mode_closeness == "eccentricity") {
								mode_closeness = LAB1100CC::CLOSENESS_ECCENTRICITY;
							}
							
							auto mode = LAB1100CC::MODE_NONE;
														
							auto mode_weighted = LAB1100CC::WEIGHTED_UNWEIGHTED;
							
							if (str_weighted_mode == "closeness") {
								mode_weighted = LAB1100CC::WEIGHTED_CLOSENESS;
							} else if (str_weighted_mode == "distance") {
								mode_weighted = LAB1100CC::WEIGHTED_DISTANCE;
							}
							
							LAB1100CC::GraphAnalysis graph;

							graph.setWeighted(mode_weighted, num_weighted_max);
							
							graph.build(str_data);
							
							client.openChunk();

							client.sendChunk(graph.getStatistics());

							graph.runCloseness(mode_closeness);
	
							graph.formatResults(mode);
							
							client.sendChunk(graph.getResults());
							
							client.closeChunk();
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
						
						jobs.remove(identifier);
					}, identifier);
					
					jobs.add(identifier, work_thread, timeout);
					
					work_thread.detach();
				};
				
				server.resource["^/graph/run_clustering_coefficient/(.*)$"]["POST"] = [&jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
					
					Jobs::type_timeout timeout = 0;
					
					auto arr_settings = LAB1100CCShed::explode(request->path_match[1], "/");
					
					for (std::vector<int>::size_type i = 0; i < arr_settings.size(); i++) {
					
						if (arr_settings[i] == "timeout") {
							
							i++;
							timeout = std::atoi(arr_settings[i].c_str());
						}
					}
					
					std::thread work_thread([&jobs, response, request](const LAB1100CC::Jobs::type_identifier &identifier) {
						
						ProgramClient client(response);
						
						try {
							
							std::ostringstream ss;
							ss << request->content.rdbuf();
							
							rapidjson::Document document;
							document.Parse(ss.str().c_str());
							
							const rapidjson::Value* json_data = rapidjson::Pointer("/data").Get(document);
							const auto& str_data = json_data->GetString();
							
							auto mode = LAB1100CC::MODE_NONE;
														
							LAB1100CC::GraphAnalysis graph;
							
							graph.build(str_data);
							
							client.openChunk();

							client.sendChunk(graph.getStatistics());
							
							graph.runClusteringCoefficient();
	
							graph.formatResults(mode);
							
							client.sendChunk(graph.getResults());
							
							client.closeChunk();
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
						
						jobs.remove(identifier);
					}, identifier);
					
					jobs.add(identifier, work_thread, timeout);
					
					work_thread.detach();
				};
				
				server.resource["^/graph/run_pagerank/(.*)$"]["POST"] = [&jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
					
					LAB1100CC::Jobs::type_timeout timeout = 0;
					
					auto arr_settings = LAB1100CCShed::explode(request->path_match[1], "/");
					
					for (std::vector<int>::size_type i = 0; i < arr_settings.size(); i++) {
					
						if (arr_settings[i] == "timeout") {
							
							i++;
							timeout = std::atoi(arr_settings[i].c_str());
						}
					}
					
					std::thread work_thread([&jobs, response, request](const LAB1100CC::Jobs::type_identifier &identifier) {
						
						ProgramClient client(response);
						
						try {
							
							std::ostringstream ss;
							ss << request->content.rdbuf();
							
							rapidjson::Document document;
							document.Parse(ss.str().c_str());
							
							const rapidjson::Value* json_data = rapidjson::Pointer("/data").Get(document);
							const auto& str_data = json_data->GetString();
							const rapidjson::Value* json_iterations = rapidjson::Pointer("/settings/iterations").Get(document);
							const auto& num_iterations = json_iterations->GetInt();
							const rapidjson::Value* json_damping = rapidjson::Pointer("/settings/damping").Get(document);
							const auto& num_damping = json_damping->GetFloat();
							
							auto mode = LAB1100CC::MODE_NONE;
							
							LAB1100CC::GraphAnalysis graph;
							
							graph.build(str_data);
							
							client.openChunk();

							client.sendChunk(graph.getStatistics());
							
							graph.runPagerank(num_iterations, num_damping);
			
							graph.formatResults(mode);

							client.sendChunk(graph.getResults());
							
							client.closeChunk();
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
						
						jobs.remove(identifier);
					}, identifier);
					
					jobs.add(identifier, work_thread, timeout);
					
					work_thread.detach();
				};

				server.default_resource["GET"] = [](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					response->write(SimpleWeb::StatusCode::client_error_bad_request, "Could not open path: "+request->path);
				};

				server.on_error = [](std::shared_ptr<HttpServer::Request> /*request*/, const SimpleWeb::error_code & /*ec*/) {
					// Handle errors here
					// Note that connection timeouts will also call this handle with ec set to SimpleWeb::errc::operation_canceled
				};

				std::thread server_thread([&server]() {
					
					server.start();
				});
				
				server_thread.detach();
				
				std::cout << "Server running on port " << port << std::endl;
				
				std::signal(SIGINT, handleSignal);
				
				while (true) {
					
					std::this_thread::sleep_for(std::chrono::seconds(1));
					
					jobs.check();
					
					// Output statistics
					std::cout << "{\"statistics\": " << jobs.getStatistics() << "}" << std::endl;
				}
			}
	};
}

int main(int argc, char** argv) {
	
	if (argc < 3) { // No valid arguments
		
		show_usage(argv[0]);
		return 1;
	}

	//std::vector <std::string> sources;
	unsigned int port;
	
	std::string str_arg;
	
	for (int i = 1; i < argc; i++) {
		
		str_arg = argv[i];
		
		if (str_arg == "-h" || str_arg == "--help") {
			
			i++;
			
			show_usage(str_arg);
			return 0;
			
		} else if (str_arg == "-p" || str_arg == "--port") {
			
			i++;
			
			if (i == argc) { // No arguments left
				
				show_usage(str_arg);
				return 1;
			}
			
			port = std::atoi(argv[i]);
		}
	}
	
	LAB1100CC::Program program;
	
	program.port = port;
	
	program.run();
}
