// Copyright 2004 The Trustees of Indiana University.

// Distributed under the Boost Software License, Version 1.0.
// (See accompanying file LICENSE_1_0.txt or copy at
// http://www.boost.org/LICENSE_1_0.txt)

//  Authors: Douglas Gregor
//           Andrew Lumsdaine
#ifndef BOOST_GRAPH_BRANDES_BETWEENNESS_CENTRALITY_FILTERED_HPP
#define BOOST_GRAPH_BRANDES_BETWEENNESS_CENTRALITY_FILTERED_HPP

#include <boost/graph/betweenness_centrality.hpp>

namespace boost {

namespace detail { namespace graph {

  template<typename Graph, typename VertexSource, typename CentralityMap, typename EdgeCentralityMap,
           typename IncomingMap, typename DistanceMap, 
           typename DependencyMap, typename PathCountMap,
           typename VertexIndexMap, typename ShortestPaths>
  void 
  brandes_betweenness_centrality_impl_filtered(const Graph& g,
									  const VertexSource& vertex_source,
                                      CentralityMap centrality,     // C_B
                                      EdgeCentralityMap edge_centrality_map,
                                      IncomingMap incoming, // P
                                      DistanceMap distance,         // d
                                      DependencyMap dependency,     // delta
                                      PathCountMap path_count,      // sigma
                                      VertexIndexMap vertex_index,
                                      ShortestPaths shortest_paths)
  {
    typedef typename graph_traits<Graph>::vertex_iterator vertex_iterator;
    typedef typename graph_traits<Graph>::vertex_descriptor vertex_descriptor;

    // Initialize centrality
    init_centrality_map(vertices(g), centrality);
    init_centrality_map(edges(g), edge_centrality_map);

    std::stack<vertex_descriptor> ordered_vertices;
    for (const vertex_descriptor& s : vertex_source) {

	  // Initialize for this iteration
	  vertex_iterator w, w_end;
      for (boost::tie(w, w_end) = vertices(g); w != w_end; ++w) {
		incoming[*w].clear();
		put(path_count, *w, 0);
		put(dependency, *w, 0);
	  }
      put(path_count, s, 1);
      
      // Execute the shortest paths algorithm. This will be either
      // Dijkstra's algorithm or a customized breadth-first search,
      // depending on whether the graph is weighted or unweighted.
      shortest_paths(g, s, ordered_vertices, incoming, distance,
                     path_count, vertex_index);
      
      while (!ordered_vertices.empty()) {
        vertex_descriptor w = ordered_vertices.top();
        ordered_vertices.pop();
        
        typedef typename property_traits<IncomingMap>::value_type
          incoming_type;
        typedef typename incoming_type::iterator incoming_iterator;
        typedef typename property_traits<DependencyMap>::value_type 
          dependency_type;
        
        for (incoming_iterator vw = incoming[w].begin();
             vw != incoming[w].end(); ++vw) {
          vertex_descriptor v = source(*vw, g);
          dependency_type factor = dependency_type(get(path_count, v))
            / dependency_type(get(path_count, w));
          factor *= (dependency_type(1) + get(dependency, w));
          put(dependency, v, get(dependency, v) + factor);
          update_centrality(edge_centrality_map, *vw, factor);
        }
        
        if (w != s) {
          update_centrality(centrality, w, get(dependency, w));
        }
      }
    }

    typedef typename graph_traits<Graph>::directed_category directed_category;
    const bool is_undirected = 
      is_convertible<directed_category*, undirected_tag*>::value;
    if (is_undirected) {
      divide_centrality_by_two(vertices(g), centrality);
      divide_centrality_by_two(edges(g), edge_centrality_map);
    }
  }

} } // end namespace detail::graph

template<typename Graph, typename VertexSource, typename CentralityMap, typename EdgeCentralityMap,
         typename IncomingMap, typename DistanceMap, 
         typename DependencyMap, typename PathCountMap, 
         typename VertexIndexMap>
void 
brandes_betweenness_centrality_filtered(const Graph& g,
							   const VertexSource& vertex_source,
                               CentralityMap centrality,     // C_B
                               EdgeCentralityMap edge_centrality_map,
                               IncomingMap incoming, // P
                               DistanceMap distance,         // d
                               DependencyMap dependency,     // delta
                               PathCountMap path_count,      // sigma
                               VertexIndexMap vertex_index
                               BOOST_GRAPH_ENABLE_IF_MODELS_PARM(Graph,vertex_list_graph_tag))
{
  detail::graph::brandes_unweighted_shortest_paths shortest_paths;

  detail::graph::brandes_betweenness_centrality_impl_filtered(g, vertex_source, centrality, 
                                                     edge_centrality_map,
                                                     incoming, distance,
                                                     dependency, path_count,
                                                     vertex_index, 
                                                     shortest_paths);
}

template<typename Graph, typename VertexSource, typename CentralityMap, typename EdgeCentralityMap, 
         typename IncomingMap, typename DistanceMap, 
         typename DependencyMap, typename PathCountMap, 
         typename VertexIndexMap, typename WeightMap>    
void 
brandes_betweenness_centrality_filtered(const Graph& g,
							   const VertexSource& vertex_source,
                               CentralityMap centrality,     // C_B
                               EdgeCentralityMap edge_centrality_map,
                               IncomingMap incoming, // P
                               DistanceMap distance,         // d
                               DependencyMap dependency,     // delta
                               PathCountMap path_count,      // sigma
                               VertexIndexMap vertex_index,
                               WeightMap weight_map
                               BOOST_GRAPH_ENABLE_IF_MODELS_PARM(Graph,vertex_list_graph_tag))
{
  detail::graph::brandes_dijkstra_shortest_paths<WeightMap>
    shortest_paths(weight_map);

  detail::graph::brandes_betweenness_centrality_impl_filtered(g, vertex_source, centrality, 
                                                     edge_centrality_map,
                                                     incoming, distance,
                                                     dependency, path_count,
                                                     vertex_index, 
                                                     shortest_paths);
}

namespace detail { namespace graph {
  template<typename Graph, typename VertexSource, typename CentralityMap, typename EdgeCentralityMap,
           typename WeightMap, typename VertexIndexMap>
  void 
  brandes_betweenness_centrality_dispatch2_filtered(const Graph& g,
										   const VertexSource& vertex_source,
                                           CentralityMap centrality,
                                           EdgeCentralityMap edge_centrality_map,
                                           WeightMap weight_map,
                                           VertexIndexMap vertex_index)
  {
    typedef typename graph_traits<Graph>::degree_size_type degree_size_type;
    typedef typename graph_traits<Graph>::edge_descriptor edge_descriptor;
    typedef typename mpl::if_c<(is_same<CentralityMap, 
                                        dummy_property_map>::value),
                                         EdgeCentralityMap, 
                               CentralityMap>::type a_centrality_map;
    typedef typename property_traits<a_centrality_map>::value_type 
      centrality_type;

    typename graph_traits<Graph>::vertices_size_type V = num_vertices(g);
    
    std::vector<std::vector<edge_descriptor> > incoming(V);
    std::vector<centrality_type> distance(V);
    std::vector<centrality_type> dependency(V);
    std::vector<degree_size_type> path_count(V);

    brandes_betweenness_centrality_filtered(
      g, vertex_source, centrality, edge_centrality_map,
      make_iterator_property_map(incoming.begin(), vertex_index),
      make_iterator_property_map(distance.begin(), vertex_index),
      make_iterator_property_map(dependency.begin(), vertex_index),
      make_iterator_property_map(path_count.begin(), vertex_index),
      vertex_index,
      weight_map);
  }
  

  template<typename Graph, typename VertexSource, typename CentralityMap, typename EdgeCentralityMap,
           typename VertexIndexMap>
  void 
  brandes_betweenness_centrality_dispatch2_filtered(const Graph& g,
										   const VertexSource& vertex_source,
                                           CentralityMap centrality,
                                           EdgeCentralityMap edge_centrality_map,
                                           VertexIndexMap vertex_index)
  {
    typedef typename graph_traits<Graph>::degree_size_type degree_size_type;
    typedef typename graph_traits<Graph>::edge_descriptor edge_descriptor;
    typedef typename mpl::if_c<(is_same<CentralityMap, 
                                        dummy_property_map>::value),
                                         EdgeCentralityMap, 
                               CentralityMap>::type a_centrality_map;
    typedef typename property_traits<a_centrality_map>::value_type 
      centrality_type;

    typename graph_traits<Graph>::vertices_size_type V = num_vertices(g);
    
    std::vector<std::vector<edge_descriptor> > incoming(V);
    std::vector<centrality_type> distance(V);
    std::vector<centrality_type> dependency(V);
    std::vector<degree_size_type> path_count(V);

    brandes_betweenness_centrality_filtered(
      g, vertex_source, centrality, edge_centrality_map,
      make_iterator_property_map(incoming.begin(), vertex_index),
      make_iterator_property_map(distance.begin(), vertex_index),
      make_iterator_property_map(dependency.begin(), vertex_index),
      make_iterator_property_map(path_count.begin(), vertex_index),
      vertex_index);
  }

  template<typename WeightMap>
  struct brandes_betweenness_centrality_dispatch1_filtered
  {
    template<typename Graph, typename VertexSource, typename CentralityMap, 
             typename EdgeCentralityMap, typename VertexIndexMap>
    static void 
    run(const Graph& g, const VertexSource& vertex_source, CentralityMap centrality, 
        EdgeCentralityMap edge_centrality_map, VertexIndexMap vertex_index,
        WeightMap weight_map)
    {
      brandes_betweenness_centrality_dispatch2_filtered(g, vertex_source, centrality, edge_centrality_map,
                                               weight_map, vertex_index);
    }
  };

  template<>
  struct brandes_betweenness_centrality_dispatch1_filtered<param_not_found>
  {
    template<typename Graph, typename VertexSource, typename CentralityMap, 
             typename EdgeCentralityMap, typename VertexIndexMap>
    static void 
    run(const Graph& g, const VertexSource& vertex_source, CentralityMap centrality, 
        EdgeCentralityMap edge_centrality_map, VertexIndexMap vertex_index,
        param_not_found)
    {
      brandes_betweenness_centrality_dispatch2_filtered(g, vertex_source, centrality, edge_centrality_map,
                                               vertex_index);
    }
  };
} } // end namespace detail::graph

template<typename Graph, typename VertexSource, typename Param, typename Tag, typename Rest>
void 
brandes_betweenness_centrality_filtered(const Graph& g, const VertexSource& vertex_source,
                               const bgl_named_params<Param,Tag,Rest>& params
                               BOOST_GRAPH_ENABLE_IF_MODELS_PARM(Graph,vertex_list_graph_tag))
{
  typedef bgl_named_params<Param,Tag,Rest> named_params;

  typedef typename get_param_type<edge_weight_t, named_params>::type ew;
  detail::graph::brandes_betweenness_centrality_dispatch1_filtered<ew>::run(
    g, 
    vertex_source,
    choose_param(get_param(params, vertex_centrality), 
                 dummy_property_map()),
    choose_param(get_param(params, edge_centrality), 
                 dummy_property_map()),
    choose_const_pmap(get_param(params, vertex_index), g, vertex_index),
    get_param(params, edge_weight));
}

// disable_if is required to work around problem with MSVC 7.1 (it seems to not
// get partial ordering getween this overload and the previous one correct)
template<typename Graph, typename VertexSource, typename CentralityMap>
typename disable_if<detail::graph::is_bgl_named_params<CentralityMap>,
                    void>::type
brandes_betweenness_centrality_filtered(const Graph& g, const VertexSource& vertex_source, CentralityMap centrality
                               BOOST_GRAPH_ENABLE_IF_MODELS_PARM(Graph,vertex_list_graph_tag))
{
  detail::graph::brandes_betweenness_centrality_dispatch2_filtered(
    g, vertex_source, centrality, dummy_property_map(), get(vertex_index, g));
}

template<typename Graph, typename VertexSource, typename CentralityMap, typename EdgeCentralityMap>
void 
brandes_betweenness_centrality_filtered(const Graph& g, const VertexSource& vertex_source, CentralityMap centrality,
                               EdgeCentralityMap edge_centrality_map
                               BOOST_GRAPH_ENABLE_IF_MODELS_PARM(Graph,vertex_list_graph_tag))
{
  detail::graph::brandes_betweenness_centrality_dispatch2_filtered(
    g, vertex_source, centrality, edge_centrality_map, get(vertex_index, g));
}

} // end namespace boost

#endif // BOOST_GRAPH_BRANDES_BETWEENNESS_CENTRALITY_FILTERED_HPP
