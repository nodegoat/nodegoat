CREATE TABLE `data_nodegoat_custom_project_type_scenario_cache` (
  `project_id` int NOT NULL,
  `scenario_id` int NOT NULL,
  `use_project_id` int NOT NULL,
  `hash` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_nodegoat_import_template_log` (
  `template_id` int NOT NULL,
  `row_identifier` int NOT NULL,
  `object_id` int DEFAULT NULL,
  `row_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `row_filter` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `row_results` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_nodegoat_public_interface_selections` (
  `id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_modified` date NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `editor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_nodegoat_public_interface_selection_elements` (
  `selection_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `elm_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_apis` (
  `api_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_api_custom_projects` (
  `api_id` int NOT NULL,
  `project_id` int NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `require_authentication` tinyint(1) NOT NULL DEFAULT '1',
  `identifier_url` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_projects` (
  `id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_referencing_enable` tinyint(1) NOT NULL,
  `full_scope_enable` tinyint(1) NOT NULL,
  `discussion_enable` tinyint(1) NOT NULL,
  `system_date_cycle_enable` tinyint(1) NOT NULL,
  `system_ingestion_enable` tinyint(1) NOT NULL,
  `visual_settings_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_date_types` (
  `project_id` int NOT NULL,
  `type_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_location_types` (
  `project_id` int NOT NULL,
  `type_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_source_types` (
  `project_id` int NOT NULL,
  `type_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_types` (
  `project_id` int NOT NULL,
  `type_id` int NOT NULL,
  `color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type_information` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type_edit` tinyint(1) NOT NULL DEFAULT '1',
  `type_filter_id` int NOT NULL DEFAULT '0',
  `type_filter_object_subs` tinyint(1) NOT NULL DEFAULT '0',
  `type_context_id` int NOT NULL DEFAULT '0',
  `type_frame_id` int NOT NULL DEFAULT '0',
  `type_condition_id` int NOT NULL DEFAULT '0',
  `configuration_exclude` tinyint(1) NOT NULL DEFAULT '0',
  `sort` smallint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_analyses` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `algorithm` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_object` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_analyses_contexts` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_conditions` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_object` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_configuration` (
  `project_id` int NOT NULL,
  `type_id` int NOT NULL,
  `object_description_id` int NOT NULL,
  `object_sub_details_id` int NOT NULL,
  `object_sub_description_id` int NOT NULL,
  `edit` tinyint(1) NOT NULL,
  `view` tinyint(1) NOT NULL,
  `information` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_contexts` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_export_settings` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `format_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `format_include_description_name` tinyint(1) DEFAULT NULL,
  `format_object` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_object` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_filters` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_frames` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_geo_latitude` decimal(10,8) DEFAULT NULL,
  `area_geo_longitude` decimal(11,8) DEFAULT NULL,
  `area_geo_zoom_scale` smallint NOT NULL,
  `area_geo_zoom_min` tinyint NOT NULL,
  `area_geo_zoom_max` tinyint NOT NULL,
  `area_social_object_id` int NOT NULL,
  `area_social_zoom_level` tinyint NOT NULL,
  `area_social_zoom_min` tinyint NOT NULL,
  `area_social_zoom_max` tinyint NOT NULL,
  `time_bounds_date_start` bigint NOT NULL,
  `time_bounds_date_end` bigint NOT NULL,
  `time_selection_date_start` bigint NOT NULL,
  `time_selection_date_end` bigint NOT NULL,
  `object_subs_unknown_date` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_subs_unknown_location` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_include_referenced_types` (
  `project_id` int NOT NULL,
  `type_id` int NOT NULL,
  `referenced_type_id` int NOT NULL,
  `object_description_id` int NOT NULL,
  `object_sub_details_id` int NOT NULL,
  `object_sub_description_id` int NOT NULL,
  `edit` tinyint(1) NOT NULL,
  `view` tinyint(1) NOT NULL,
  `information` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_scenarios` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribution` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `filter_id` int NOT NULL,
  `filter_use_current` tinyint(1) NOT NULL,
  `scope_id` int NOT NULL,
  `scope_use_current` tinyint(1) NOT NULL,
  `condition_id` int NOT NULL,
  `condition_use_current` tinyint(1) NOT NULL,
  `context_id` int NOT NULL,
  `context_use_current` tinyint(1) NOT NULL,
  `frame_id` int NOT NULL,
  `frame_use_current` tinyint(1) NOT NULL,
  `visual_settings_id` int NOT NULL,
  `visual_settings_use_current` tinyint(1) NOT NULL,
  `analysis_id` int NOT NULL,
  `analysis_use_current` tinyint(1) NOT NULL,
  `analysis_context_id` int NOT NULL,
  `analysis_context_use_current` tinyint(1) NOT NULL,
  `cache_retain` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_scopes` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_use_projects` (
  `project_id` int NOT NULL,
  `use_project_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_visual_settings` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `capture_enable` tinyint(1) DEFAULT NULL,
  `capture_settings` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_show` tinyint(1) DEFAULT NULL,
  `dot_size_min` float NOT NULL,
  `dot_size_max` float NOT NULL,
  `dot_size_start` int NOT NULL,
  `dot_size_stop` int NOT NULL,
  `dot_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_opacity` float DEFAULT NULL,
  `dot_color_condition` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_stroke_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_stroke_opacity` float NOT NULL,
  `dot_stroke_width` float DEFAULT NULL,
  `location_show` tinyint(1) DEFAULT NULL,
  `location_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_opacity` float NOT NULL,
  `location_size` float NOT NULL,
  `location_threshold` smallint NOT NULL,
  `location_offset` smallint DEFAULT NULL,
  `location_position` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_condition` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_show` tinyint(1) DEFAULT NULL,
  `line_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_opacity` float NOT NULL,
  `line_width_min` float NOT NULL,
  `line_width_max` float NOT NULL,
  `line_offset` smallint DEFAULT NULL,
  `map_show` tinyint(1) DEFAULT NULL,
  `map_url` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `map_attribution` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `visual_hints_show` tinyint(1) DEFAULT NULL,
  `visual_hints_size` float NOT NULL,
  `visual_hints_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `visual_hints_opacity` float DEFAULT NULL,
  `visual_hints_stroke_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `visual_hints_stroke_opacity` float NOT NULL,
  `visual_hints_stroke_width` float DEFAULT NULL,
  `visual_hints_duration` float NOT NULL,
  `visual_hints_delay` float NOT NULL,
  `geometry_show` tinyint(1) DEFAULT NULL,
  `geometry_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `geometry_opacity` float DEFAULT NULL,
  `geometry_stroke_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `geometry_stroke_opacity` float NOT NULL,
  `geometry_stroke_width` float DEFAULT NULL,
  `geo_info_show` tinyint(1) DEFAULT NULL,
  `geo_background_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `geo_mode` tinyint DEFAULT NULL,
  `geo_display` tinyint DEFAULT NULL,
  `geo_advanced` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_dot_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_dot_size_min` float NOT NULL,
  `social_dot_size_max` float NOT NULL,
  `social_dot_size_start` int NOT NULL,
  `social_dot_size_stop` int NOT NULL,
  `social_dot_stroke_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_dot_stroke_width` float DEFAULT NULL,
  `social_line_arrowhead_show` tinyint(1) DEFAULT NULL,
  `social_line_show` tinyint(1) DEFAULT NULL,
  `social_force` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_forceatlas2` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_disconnected_dot_show` tinyint(1) DEFAULT NULL,
  `social_include_location_references` tinyint(1) DEFAULT NULL,
  `social_background_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_display` tinyint DEFAULT NULL,
  `social_static_layout` tinyint(1) DEFAULT NULL,
  `social_static_layout_interval` float DEFAULT NULL,
  `social_advanced` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_bar_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_bar_opacity` float NOT NULL,
  `time_background_color` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_relative_graph` tinyint(1) DEFAULT NULL,
  `time_cumulative_graph` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_details` (
  `unique_row` tinyint(1) NOT NULL DEFAULT '1',
  `processing_memory` int NOT NULL,
  `processing_time` int NOT NULL,
  `limit_view` int NOT NULL,
  `limit_import` int NOT NULL,
  `limit_file_size` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_import_files` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_objects` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_import_templates` (
  `id` int NOT NULL,
  `type_id` int NOT NULL,
  `source_id` int NOT NULL,
  `mode` tinyint NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `use_log` tinyint(1) NOT NULL,
  `last_run` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_import_template_columns` (
  `id` int NOT NULL,
  `template_id` int NOT NULL,
  `pointer_heading` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pointer_class` smallint NOT NULL,
  `value_split` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_index` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `element_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `element_type_id` int NOT NULL,
  `element_type_object_sub_id` int NOT NULL,
  `element_type_element_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `overwrite` tinyint(1) NOT NULL,
  `ignore_when` tinyint(1) NOT NULL,
  `heading_for_source_link` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_linked_data_conversions` (
  `id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `output_placeholder` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_placeholder` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_linked_data_resources` (
  `id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `protocol` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_options` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_headers` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `query` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_uri_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_uri_template` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_uri_conversion_id` int NOT NULL,
  `response_uri_conversion_output_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `response_label_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_label_conversion_id` int NOT NULL,
  `response_label_conversion_output_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_linked_data_resource_values` (
  `resource_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversion_id` int NOT NULL,
  `conversion_output_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_pattern_type_object_pairs` (
  `type_id` int NOT NULL,
  `identifier` binary(16) NOT NULL,
  `pattern_value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_id` int DEFAULT NULL,
  `composition` tinyint NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interfaces` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `information` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `css` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_projects` (
  `public_interface_id` int NOT NULL,
  `project_id` int NOT NULL,
  `sort` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_project_scenarios` (
  `public_interface_id` int NOT NULL,
  `project_id` int NOT NULL,
  `scenario_id` int NOT NULL,
  `browse` tinyint NOT NULL,
  `list` tinyint NOT NULL,
  `geographic_visualisation` tinyint NOT NULL,
  `social_visualisation` tinyint NOT NULL,
  `time_visualisation` tinyint NOT NULL,
  `sort` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_project_types` (
  `public_interface_id` int NOT NULL,
  `project_id` int NOT NULL,
  `type_id` int NOT NULL,
  `is_filter` tinyint NOT NULL,
  `browse` tinyint DEFAULT NULL,
  `list` tinyint DEFAULT NULL,
  `geographic_visualisation` tinyint DEFAULT NULL,
  `social_visualisation` tinyint DEFAULT NULL,
  `time_visualisation` tinyint DEFAULT NULL,
  `sort` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_texts` (
  `id` int NOT NULL,
  `public_interface_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_link_nodegoat_custom_projects` (
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_link_nodegoat_custom_project_type_filters` (
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `filter_id` int NOT NULL,
  `source` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_preferences` (
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `data_nodegoat_custom_project_type_scenario_cache`
  ADD PRIMARY KEY (`project_id`,`scenario_id`,`use_project_id`);

ALTER TABLE `data_nodegoat_import_template_log`
  ADD PRIMARY KEY (`template_id`,`row_identifier`);

ALTER TABLE `data_nodegoat_public_interface_selections`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `data_nodegoat_public_interface_selection_elements`
  ADD PRIMARY KEY (`selection_id`,`elm_id`);

ALTER TABLE `def_nodegoat_apis`
  ADD PRIMARY KEY (`api_id`);

ALTER TABLE `def_nodegoat_api_custom_projects`
  ADD PRIMARY KEY (`api_id`,`project_id`);

ALTER TABLE `def_nodegoat_custom_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visual_settings_id` (`visual_settings_id`);

ALTER TABLE `def_nodegoat_custom_project_date_types`
  ADD PRIMARY KEY (`project_id`,`type_id`);

ALTER TABLE `def_nodegoat_custom_project_location_types`
  ADD PRIMARY KEY (`project_id`,`type_id`);

ALTER TABLE `def_nodegoat_custom_project_source_types`
  ADD PRIMARY KEY (`project_id`,`type_id`);

ALTER TABLE `def_nodegoat_custom_project_types`
  ADD PRIMARY KEY (`project_id`,`type_id`);

ALTER TABLE `def_nodegoat_custom_project_type_analyses`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

ALTER TABLE `def_nodegoat_custom_project_type_analyses_contexts`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `def_nodegoat_custom_project_type_conditions`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`);

ALTER TABLE `def_nodegoat_custom_project_type_configuration`
  ADD PRIMARY KEY (`project_id`,`type_id`,`object_description_id`,`object_sub_details_id`,`object_sub_description_id`),
  ADD KEY `edit` (`edit`),
  ADD KEY `view` (`view`);

ALTER TABLE `def_nodegoat_custom_project_type_contexts`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `def_nodegoat_custom_project_type_export_settings`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

ALTER TABLE `def_nodegoat_custom_project_type_filters`
  ADD PRIMARY KEY (`project_id`,`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `def_nodegoat_custom_project_type_frames`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

ALTER TABLE `def_nodegoat_custom_project_type_include_referenced_types`
  ADD PRIMARY KEY (`project_id`,`type_id`,`referenced_type_id`,`object_description_id`,`object_sub_details_id`,`object_sub_description_id`),
  ADD KEY `edit` (`edit`),
  ADD KEY `view` (`view`);

ALTER TABLE `def_nodegoat_custom_project_type_scenarios`
  ADD PRIMARY KEY (`project_id`,`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `filter_id` (`filter_id`,`scope_id`,`condition_id`,`frame_id`,`visual_settings_id`);

ALTER TABLE `def_nodegoat_custom_project_type_scopes`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `def_nodegoat_custom_project_use_projects`
  ADD PRIMARY KEY (`project_id`,`use_project_id`);

ALTER TABLE `def_nodegoat_custom_project_visual_settings`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `def_nodegoat_details`
  ADD PRIMARY KEY (`unique_row`);

ALTER TABLE `def_nodegoat_import_files`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_import_templates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_import_template_columns`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_linked_data_conversions`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_linked_data_resources`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_linked_data_resource_values`
  ADD PRIMARY KEY (`resource_id`,`name`),
  ADD KEY `sort` (`sort`);

ALTER TABLE `def_nodegoat_pattern_type_object_pairs`
  ADD PRIMARY KEY (`type_id`,`identifier`);

ALTER TABLE `def_nodegoat_public_interfaces`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_public_interface_projects`
  ADD PRIMARY KEY (`public_interface_id`,`project_id`);

ALTER TABLE `def_nodegoat_public_interface_project_scenarios`
  ADD PRIMARY KEY (`public_interface_id`,`project_id`,`scenario_id`);

ALTER TABLE `def_nodegoat_public_interface_project_types`
  ADD PRIMARY KEY (`public_interface_id`,`project_id`,`type_id`);

ALTER TABLE `def_nodegoat_public_interface_texts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `user_link_nodegoat_custom_projects`
  ADD PRIMARY KEY (`user_id`,`project_id`);

ALTER TABLE `user_link_nodegoat_custom_project_type_filters`
  ADD PRIMARY KEY (`user_id`,`project_id`,`filter_id`),
  ADD KEY `type` (`source`);

ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);


ALTER TABLE `def_nodegoat_custom_projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_import_files`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_import_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_import_template_columns`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_linked_data_conversions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_linked_data_resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_public_interfaces`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_public_interface_texts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
