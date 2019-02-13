
/**
 * nodegoat - web-based data management, network analysis & visualisation environment.
 * Copyright (C) 2019 LAB1100.
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

#include <xapian.h>

namespace LAB1100CC {
		
	class InformationRetrievalQuery {
		
		protected:
		
			typedef std::tuple<unsigned int, unsigned int, std::vector<unsigned int>, std::string> type_arr_query_result;
			typedef std::unordered_map<std::string, type_arr_query_result> type_map_query_results;

			type_map_query_results map_query_results;
		
		public:
		
			std::string path_database = "";
			
			unsigned int num_offset = 0;
			unsigned int num_limit = 100;
			bool include_value = true;
			
			void query(const std::vector<std::string>& arr_queries, const std::vector<std::string>& arr_selection = {}) {
				
				Xapian::Database databases_ir;
				try {
					Xapian::Database database_ir_object_values(path_database+"object_values/");
					databases_ir.add_database(database_ir_object_values);
				} catch (const Xapian::Error &e) {
					// Database not ready
				}
				try {
					Xapian::Database database_ir_object_descriptions(path_database+"object_descriptions/");
					databases_ir.add_database(database_ir_object_descriptions);
				} catch (const Xapian::Error &e) {
					// Database not ready
				}
				try {
					Xapian::Database database_ir_object_sub_descriptions(path_database+"object_sub_descriptions/");
					databases_ir.add_database(database_ir_object_sub_descriptions);
				} catch (const Xapian::Error &e) {
					// Database not ready
				}
				
				// Filter on Type IDs
				
				Xapian::Query query_ir_identifiers;
				
				if (!arr_selection.empty()) {
					
					std::vector<Xapian::Query> arr_query_identifiers;
					
					for (const auto& str_identifier_field : arr_selection) {

						arr_query_identifiers.push_back(Xapian::Query("T"+str_identifier_field));
					}
					
					query_ir_identifiers = Xapian::Query(Xapian::Query::OP_OR, arr_query_identifiers.begin(), arr_query_identifiers.end());
				}

				Xapian::QueryParser queryparser;
				queryparser.set_database(databases_ir); // Needed to enable specific query flags
				queryparser.set_stemmer(Xapian::Stem("en"));
				queryparser.set_stemming_strategy(queryparser.STEM_SOME);
				queryparser.add_boolean_prefix("identifier", "T");
				//queryparser.add_prefix("value", "SV");
				
				unsigned int count_queries = 0;
				
				for (const auto& str_query : arr_queries) {
					
					const auto query_id = count_queries;
					count_queries++;
						
					Xapian::Query query_ir = queryparser.parse_query(str_query, Xapian::QueryParser::FLAG_DEFAULT | Xapian::QueryParser::FLAG_WILDCARD);
					
					if (!arr_selection.empty()) {
						
						// Update main query
						
						query_ir = Xapian::Query(Xapian::Query::OP_FILTER, query_ir, query_ir_identifiers);
					}
					
					// Run query
					
					Xapian::Enquire enquire(databases_ir);
					enquire.set_query(query_ir);
					
					Xapian::MSet arr_msets = enquire.get_mset(num_offset, num_limit);

					for (Xapian::MSetIterator iterate_arr_mset = arr_msets.begin(); iterate_arr_mset != arr_msets.end(); iterate_arr_mset++) {
											
						//Xapian::docid did = *iterate_arr_mset;
						const int unsigned& nr_rank = iterate_arr_mset.get_rank();
						const int unsigned& nr_weight = iterate_arr_mset.get_weight();
						
						const Xapian::Document doc = iterate_arr_mset.get_document();
						const std::string& str_identifier = doc.get_value(0);
						
						if (map_query_results.find(str_identifier) == map_query_results.end()) {
							
							std::vector<unsigned int> arr_matches;
							
							arr_matches.push_back(query_id);

							const std::string& str_value = (include_value ? doc.get_data() : "");
							
							map_query_results[str_identifier] = std::make_tuple(nr_rank, nr_weight, arr_matches, str_value);
						} else {
							
							type_arr_query_result& arr_query_result = map_query_results[str_identifier];
							
							std::get<0>(arr_query_result) += nr_rank;
							std::get<1>(arr_query_result) += nr_weight;
							
							std::get<2>(arr_query_result).push_back(query_id);
						}
					}
				}
			}
			
			std::string getResults() {
				
				std::string str_result = "{";
				std::string separator_results = "";
				
				for (const auto& arr_map : map_query_results) {
					
					type_arr_query_result arr_query_result = arr_map.second;
					
					std::string str_query_ids = "[";
					std::string separator_query_ids = "";
					
					for (const auto& query_id : std::get<2>(arr_query_result)) {
						
						str_query_ids += separator_query_ids;
						str_query_ids += std::to_string(query_id);
						
						separator_query_ids = ", ";
					}
					
					str_query_ids += "]";
					
					str_result += separator_results;
					str_result += "\""+arr_map.first+"\": {\"rank\": "+std::to_string(std::get<0>(arr_query_result))+", \"weight\": "+std::to_string(std::get<1>(arr_query_result))+", \"matches\": "+str_query_ids+", \"value\": \""+(include_value ? LAB1100CCShed::escapeJSON(std::get<3>(arr_query_result)) : "")+"\"}";
					
					separator_results = ", ";
				}
				
				str_result += "}";
				
				return str_result;
			}
	};
}
