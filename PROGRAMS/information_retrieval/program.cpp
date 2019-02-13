
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
#include <tuple>
#include <string>
#include <chrono>
#include <thread>
#include <pthread.h>
#include <mutex>
#include <unordered_map>

#include "rapidjson/document.h"
#include "rapidjson/pointer.h"

#include "server_http.hpp"

namespace LAB1100CC {
	
	using HttpServer = SimpleWeb::Server<SimpleWeb::HTTP>;
}

#include "program.hpp"
#include "shed.hpp"

#include "information_retrieval_index.hpp"
#include "information_retrieval_query.hpp"

static void show_usage(const std::string name) {
	
    std::cerr << "Usage:" << std::endl
		<< "\t" << name << " <option(s)>" << std::endl
		<< "Options:" << std::endl
		<< "\t-h, --help\t\tShow this help message" << std::endl
		<< "\t-p, --port PORT\t\tSpecify the port" << std::endl
		<< "\t--path PATH\t\tSpecify the working directory path" << std::endl
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
			std::string path_store;
				
			void run() {

				// HTTP-server at (port) using 1 default thread
				HttpServer server;
				server.config.address = "127.0.0.1";
				server.config.port = port;
				server.config.timeout_content = 60*60;
				
				LAB1100CC::Jobs jobs;

				server.resource["^/index/build/(.*)$"]["POST"] = [this, &jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
										
					std::thread work_thread([this, response, request]() {
						
						ProgramClient client(response);
						
						try {
							
							std::string database_collection = "";
							
							auto arr_settings = LAB1100CCShed::explode(request->path_match[1], "/");
							
							for (std::vector<int>::size_type i = 0; i < arr_settings.size(); i++) {
							
								if (arr_settings[i] == "collection") {
									
									i++;
									database_collection = arr_settings[i];
								}
							}
							
							std::stringstream ss;
							ss << request->content.rdbuf();

							LAB1100CC::InformationRetrievalIndex irdb_index;
							
							irdb_index.path_database = path_store;
							irdb_index.database_collection = database_collection;
							
							irdb_index.open(true);
							irdb_index.index(ss);
							
							const auto& str_results = irdb_index.getResults();
							
							client.sendMessage(str_results);
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
					});
					
					work_thread.detach();
				};
				
				server.resource["^/query/term/(.*)$"]["POST"] = [this, &jobs](std::shared_ptr<HttpServer::Response> response, std::shared_ptr<HttpServer::Request> request) {
					
					auto identifier = jobs.getIdentifier();
										
					std::thread work_thread([this, response, request]() {
						
						ProgramClient client(response);
						
						try {
							
							std::stringstream ss;
							ss << request->content.rdbuf();
							
							rapidjson::Document document;
							document.Parse(ss.str().c_str());
							
							const rapidjson::Value* json_queries = rapidjson::Pointer("/queries").Get(document);
							const rapidjson::Value* json_selection = rapidjson::Pointer("/selection").Get(document);
							
							const rapidjson::Value* json_offset = rapidjson::Pointer("/settings/offset").Get(document);
							const auto& num_offset = json_offset->GetInt();
							const rapidjson::Value* json_limit = rapidjson::Pointer("/settings/limit").Get(document);
							const auto& num_limit = json_limit->GetInt();
							const rapidjson::Value* json_include_value = rapidjson::Pointer("/settings/include_value").Get(document);
							const auto& include_value = json_include_value->GetBool();
							
							std::vector<std::string> arr_queries;

							for (auto& json_query : json_queries->GetArray()) {
								
								arr_queries.push_back(json_query.GetString());
							}
							
							std::vector<std::string> arr_selection;

							for (auto& json_selected : json_selection->GetArray()) {
								
								arr_selection.push_back(json_selected.GetString());
							}

							LAB1100CC::InformationRetrievalQuery irdb_query;
							
							irdb_query.path_database = path_store;
							
							irdb_query.num_offset = num_offset;
							irdb_query.num_limit = num_limit;
							irdb_query.include_value = include_value;
							
							irdb_query.query(arr_queries, arr_selection);
							
							const auto& str_results = irdb_query.getResults();
							
							client.sendMessage(str_results);
						} catch (const std::exception &e) {
							
							client.sendError(e);
						} catch (...) {
							
							client.sendErrorUncaught(std::current_exception());
						}
					});
					
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
	std::string path_store;
	
	std::string str_arg;
	
	for (int i = 1; i < argc; i++) {
		
		str_arg = argv[i];
		
		if (str_arg == "-h" || str_arg == "--help") {
			
			i++;
			
			show_usage(str_arg);
			return 0;
			
		} else if (str_arg == "-p" || str_arg == "--port" || str_arg == "--path") {
			
			i++;
			
			if (i == argc) { // No arguments left
				
				show_usage(str_arg);
				return 1;
			}
			
			if (str_arg == "--path") {
				path_store = std::string(argv[i]);
			} else {
				port = std::atoi(argv[i]);
			}
		}
	}
	
	LAB1100CC::Program program;
	
	program.port = port;
	program.path_store = path_store;
	
	program.run();
}
