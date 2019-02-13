
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
#include <regex>

#include <boost/filesystem.hpp>
#include <xapian.h>

#include <unicode/utypes.h>
#include <unicode/unistr.h>
#include <unicode/translit.h>

namespace LAB1100CC {
		
	class InformationRetrievalIndex {
		
		protected:
		
			unsigned int count_indexed = 0;
			
			static bool isASCII(const std::string& str) {
				
				/*
				std::regex expression_test("[\x01-\x7F]+");
				
				if (!std::regex_match(str.begin(), str.end(), expression_test)) {
					return false;
				}
				
				return true; 
				*/
				
				return !std::any_of(str.begin(), str.end(), [](char c) {
					return static_cast<unsigned char>(c) > 127; 
				});
			}
			
			static std::string transliterateString(const std::string& str, const icu::Transliterator *transliterator) {
				
				// UTF-8 std::string -> UTF-16 UnicodeString
				icu::UnicodeString source = icu::UnicodeString::fromUTF8(icu::StringPiece(str));

				transliterator->transliterate(source); // Transliterate UTF-16 UnicodeString
				
				// TODO: handle errors with status_transliterate

				// UTF-16 UnicodeString -> UTF-8 std::string
				std::string str_result;
				source.toUTF8String(str_result);

				return str_result;
			}
			
			// http://userguide.icu-project.org/transforms/general
			UErrorCode status_transliterate = U_ZERO_ERROR;
			const icu::Transliterator *transliterate_accents = icu::Transliterator::createInstance("NFD; [:M:] Remove; NFC", UTRANS_FORWARD, status_transliterate);
			//const icu::Transliterator *convert_accents = icu::Transliterator::createInstance("NFD; Latin-ASCII", UTRANS_FORWARD, status_transliterate); // To transliterate to Latin oly

		public:
			
			std::string path_database = "";
			std::string database_collection = "";
			
			Xapian::WritableDatabase database_ir;
			
			void open(const bool& overwrite = false) {
				
				database_ir = Xapian::WritableDatabase(path_database+database_collection+"/", (overwrite ? Xapian::DB_CREATE_OR_OVERWRITE : Xapian::DB_CREATE_OR_OPEN));
			}
		
			void index(std::stringstream& ss) {

				Xapian::TermGenerator termgenerator;
				termgenerator.set_stemmer(Xapian::Stem("en"));

				std::string str_line;
				
				while (std::getline(ss, str_line, '\n')) {
			
					const auto& arr_cells = LAB1100CCShed::explode(str_line, "$|$");
					
					const auto& type_id = arr_cells[0];
					const auto& object_id = arr_cells[1];
					const auto& field_id = arr_cells[2];
					const auto& field_value = arr_cells[3];
					
					std::string field_collection;
					
					if (database_collection == "object_values") {
						
						field_collection = "ov";
					} else if (database_collection == "object_descriptions") {
						
						field_collection = "od";
					} else if (database_collection == "object_sub_descriptions") {
						
						field_collection = "osd";
					}
					
					const std::string str_identifier = type_id+":"+object_id+":"+field_collection+":"+field_id;
					const std::string str_identifier_field = type_id+":"+field_collection+":"+field_id;
					
					Xapian::Document doc;
					termgenerator.set_document(doc);
					
					// Add identification value
					doc.add_value(0, str_identifier);
					
					// Add search term for the field
					doc.add_boolean_term("T"+str_identifier_field);
										
					// General search
					termgenerator.index_text(field_value, 1); // Index text with a default term weight of 1.
					
					if (!isASCII(field_value)) {
						termgenerator.index_text(transliterateString(field_value, transliterate_accents), 1); // Index text without any accents with a default term weight of 1.
					}

					// Store the field for display purposes
					doc.set_data(field_value);
					
					// Create identifier and use for unique storage
					
					std::string term_identifier = "Q"+str_identifier;
					doc.add_boolean_term(term_identifier);
					database_ir.replace_document(term_identifier, doc);
					
					count_indexed++;
				}
			}
			
			void drop() {
				
				if (path_database == "") {
					return;
				}
				
				boost::filesystem::remove_all(path_database+database_collection+"/");
			}
			
			std::string getResults() {
				
				std::string str_result = "{\"indexed\": "+std::to_string(count_indexed)+"}";
				
				return str_result;
			}
	};
}
