cmake_minimum_required (VERSION 3.0)

project(information_retrieval)

get_filename_component(PARENT_DIR ${CMAKE_CURRENT_SOURCE_DIR} DIRECTORY)

set(ICU_ROOT ${PARENT_DIR}/LIBRARIES/icu)

set(BOOST_ROOT ${PARENT_DIR}/LIBRARIES/boost)
set(Boost_NO_SYSTEM_PATHS ON)

add_compile_options(-std=c++11 -Wall -Wextra -Wsign-conversion -O3 -march=native)

# Include the LAB1100CC library
add_subdirectory(${PARENT_DIR}/LAB1100CC ${CMAKE_CURRENT_BINARY_DIR}/LAB1100CC)

# Include the simple-web-server library
add_subdirectory(${PARENT_DIR}/SERVICES/web_server ${CMAKE_CURRENT_BINARY_DIR}/SERVICES/web_server)

# Include the RapidJSON header files
include_directories(${PARENT_DIR}/LIBRARIES/rapidjson/include)

find_package(Boost COMPONENTS system filesystem REQUIRED)

find_package(ICU COMPONENTS uc data i18n io REQUIRED)

find_package(Xapian REQUIRED PATHS ${PARENT_DIR}/LIBRARIES/xapian NO_DEFAULT_PATH)

# Prepare program
add_executable(information_retrieval program.cpp)

# Link libraries to program
target_link_libraries(information_retrieval LAB1100CC)
target_link_libraries(information_retrieval simple-web-server)

target_link_libraries(information_retrieval ${Boost_LIBRARIES})
target_include_directories(information_retrieval PRIVATE ${Boost_INCLUDE_DIR})

target_link_libraries(information_retrieval ${ICU_LIBRARIES})
target_include_directories(information_retrieval PRIVATE ${ICU_INCLUDE_DIRS})

target_link_libraries(information_retrieval ${XAPIAN_LIBRARIES})
target_include_directories(information_retrieval PRIVATE ${XAPIAN_INCLUDE_DIR})

# Install the program

set_target_properties(information_retrieval PROPERTIES INSTALL_RPATH_USE_LINK_PATH TRUE)

install(TARGETS information_retrieval DESTINATION ${PARENT_DIR}/RUN)

