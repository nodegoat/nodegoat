DROP TABLE IF EXISTS `user_details`;

CREATE TABLE `data_nodegoat_custom_project_type_scenario_cache` (
  `project_id` int(11) NOT NULL,
  `scenario_id` int(11) NOT NULL,
  `use_project_id` int(11) NOT NULL,
  `hash` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_nodegoat_public_interface_selections` (
  `id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_modified` date NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `editor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_nodegoat_public_interface_selection_elements` (
  `selection_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `elm_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `heading` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_apis` (
  `api_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_api_custom_projects` (
  `api_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `require_authentication` tinyint(1) NOT NULL DEFAULT '1',
  `identifier_url` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_projects` (
  `id` int(11) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_referencing` tinyint(1) NOT NULL,
  `full_scope` tinyint(1) NOT NULL,
  `discussion_provide` tinyint(1) NOT NULL,
  `visual_settings_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_location_types` (
  `project_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_source_types` (
  `project_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_types` (
  `project_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type_definition_id` int(11) NOT NULL DEFAULT '0',
  `type_filter_id` int(11) NOT NULL DEFAULT '0',
  `type_filter_object_subs` tinyint(1) NOT NULL DEFAULT '0',
  `type_context_id` int(11) NOT NULL DEFAULT '0',
  `type_frame_id` int(11) NOT NULL DEFAULT '0',
  `type_condition_id` int(11) NOT NULL DEFAULT '0',
  `configuration_exclude` tinyint(1) NOT NULL DEFAULT '0',
  `sort` smallint(6) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_analyses` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `algorithm` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_analyses_contexts` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_conditions` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_object` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_configuration` (
  `project_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `object_sub_details_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `edit` tinyint(1) NOT NULL,
  `view` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_contexts` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_filters` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int(11) NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_frames` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_geo_latitude` decimal(10,8) DEFAULT NULL,
  `area_geo_longitude` decimal(11,8) DEFAULT NULL,
  `area_geo_scale` smallint(6) NOT NULL,
  `area_social_object_id` int(11) NOT NULL,
  `area_social_zoom` tinyint(4) NOT NULL,
  `time_bounds_date_start` bigint(20) NOT NULL,
  `time_bounds_date_end` bigint(20) NOT NULL,
  `time_selection_date_start` bigint(20) NOT NULL,
  `time_selection_date_end` bigint(20) NOT NULL,
  `object_subs_unknown_date` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_subs_unknown_location` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_include_referenced_types` (
  `project_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `referenced_type_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `object_sub_details_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `edit` tinyint(1) NOT NULL,
  `view` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_scenarios` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int(11) NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attribution` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filter_id` int(11) NOT NULL,
  `filter_use_current` tinyint(1) NOT NULL,
  `scope_id` int(11) NOT NULL,
  `scope_use_current` tinyint(1) NOT NULL,
  `condition_id` int(11) NOT NULL,
  `condition_use_current` tinyint(1) NOT NULL,
  `context_id` int(11) NOT NULL,
  `context_use_current` tinyint(1) NOT NULL,
  `frame_id` int(11) NOT NULL,
  `frame_use_current` tinyint(1) NOT NULL,
  `visual_settings_id` int(11) NOT NULL,
  `visual_settings_use_current` tinyint(1) NOT NULL,
  `analysis_id` int(11) NOT NULL,
  `analysis_use_current` tinyint(1) NOT NULL,
  `analysis_context_id` int(11) NOT NULL,
  `analysis_context_use_current` tinyint(1) NOT NULL,
  `cache_retain` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_type_scopes` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_use_projects` (
  `project_id` int(11) NOT NULL,
  `use_project_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_custom_project_visual_settings` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_show` tinyint(1) DEFAULT NULL,
  `dot_size_min` float NOT NULL,
  `dot_size_max` float NOT NULL,
  `dot_size_start` int(11) NOT NULL,
  `dot_size_stop` int(11) NOT NULL,
  `dot_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_opacity` float DEFAULT NULL,
  `dot_color_condition` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_stroke_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dot_stroke_opacity` float NOT NULL,
  `dot_stroke_width` float DEFAULT NULL,
  `location_show` tinyint(1) DEFAULT NULL,
  `location_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_size` float NOT NULL,
  `location_threshold` smallint(6) NOT NULL,
  `location_condition` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_show` tinyint(1) DEFAULT NULL,
  `line_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_opacity` float NOT NULL,
  `line_width_min` float NOT NULL,
  `line_width_max` float NOT NULL,
  `line_offset` smallint(6) DEFAULT NULL,
  `map_url` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `map_attribution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visual_hints_show` tinyint(1) DEFAULT NULL,
  `visual_hints_size` float NOT NULL,
  `visual_hints_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visual_hints_opacity` float DEFAULT NULL,
  `visual_hints_stroke_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visual_hints_stroke_opacity` float NOT NULL,
  `visual_hints_stroke_width` float DEFAULT NULL,
  `visual_hints_duration` float NOT NULL,
  `visual_hints_delay` float NOT NULL,
  `geometry_show` tinyint(1) DEFAULT NULL,
  `geometry_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `geometry_opacity` float DEFAULT NULL,
  `geometry_stroke_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `geometry_stroke_opacity` float NOT NULL,
  `geometry_stroke_width` float DEFAULT NULL,
  `geo_info_show` tinyint(1) DEFAULT NULL,
  `geo_background_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `geo_mode` tinyint(4) DEFAULT NULL,
  `geo_display` tinyint(4) DEFAULT NULL,
  `geo_advanced` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_dot_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_dot_size_min` float NOT NULL,
  `social_dot_size_max` float NOT NULL,
  `social_dot_size_start` int(11) NOT NULL,
  `social_dot_size_stop` int(11) NOT NULL,
  `social_dot_stroke_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_dot_stroke_width` float DEFAULT NULL,
  `social_line_arrowhead_show` tinyint(1) DEFAULT NULL,
  `social_line_show` tinyint(1) DEFAULT NULL,
  `social_disconnected_dot_show` tinyint(1) DEFAULT NULL,
  `social_include_location_references` tinyint(1) DEFAULT NULL,
  `social_background_color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_display` tinyint(4) DEFAULT NULL,
  `social_static_layout` tinyint(1) DEFAULT NULL,
  `social_static_layout_interval` float DEFAULT NULL,
  `social_advanced` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_conditions_relative` tinyint(1) DEFAULT NULL,
  `time_conditions_cumulative` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_details` (
  `unique_row` tinyint(1) NOT NULL DEFAULT '1',
  `limit_view` int(11) NOT NULL,
  `limit_import` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_import_files` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_objects` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_import_string_object_pairs` (
  `id` int(11) NOT NULL,
  `string` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filter_values` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_import_templates` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `source_file_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_run` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_import_template_columns` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `heading` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `splitter` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generate` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type_id` int(11) NOT NULL,
  `element_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `element_type_id` int(11) NOT NULL,
  `element_type_object_sub_id` int(11) NOT NULL,
  `element_type_element_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `use_as_filter` int(11) NOT NULL,
  `use_type_object_id` int(11) NOT NULL,
  `is_source` int(11) DEFAULT NULL,
  `source_type_id` int(11) DEFAULT NULL,
  `source_link_position` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_linked_data_resources` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `protocol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_options` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `query` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_uri` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_uri_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_label` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_linked_data_resource_values` (
  `resource_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interfaces` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `information` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `css` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_projects` (
  `public_interface_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `sort` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_project_scenarios` (
  `public_interface_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `scenario_id` int(11) NOT NULL,
  `browse` tinyint(4) NOT NULL,
  `list` tinyint(4) NOT NULL,
  `geographic_visualisation` tinyint(4) NOT NULL,
  `social_visualisation` tinyint(4) NOT NULL,
  `time_visualisation` tinyint(4) NOT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_project_types` (
  `public_interface_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `is_filter` tinyint(4) NOT NULL,
  `browse` tinyint(4) NOT NULL,
  `list` tinyint(4) NOT NULL,
  `geographic_visualisation` tinyint(4) NOT NULL,
  `social_visualisation` tinyint(4) NOT NULL,
  `time_visualisation` tinyint(4) NOT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_nodegoat_public_interface_texts` (
  `id` int(11) NOT NULL,
  `public_interface_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_details` (
  `user_id` int(11) NOT NULL,
  `clearance` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_link_nodegoat_custom_projects` (
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_link_nodegoat_custom_project_type_filters` (
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `filter_id` int(11) NOT NULL,
  `source` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_preferences` (
  `user_id` int(11) NOT NULL,
  `format_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `format_include_description_name` tinyint(1) NOT NULL,
  `format_settings` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `export_types` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `data_nodegoat_custom_project_type_scenario_cache`
  ADD PRIMARY KEY (`project_id`,`scenario_id`,`use_project_id`);

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

ALTER TABLE `def_nodegoat_import_string_object_pairs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_import_templates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_import_template_columns`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_linked_data_resources`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_linked_data_resource_values`
  ADD PRIMARY KEY (`resource_id`,`name`),
  ADD KEY `sort` (`sort`);

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

ALTER TABLE `user_details`
  ADD PRIMARY KEY (`user_id`);

ALTER TABLE `user_link_nodegoat_custom_projects`
  ADD PRIMARY KEY (`user_id`,`project_id`);

ALTER TABLE `user_link_nodegoat_custom_project_type_filters`
  ADD PRIMARY KEY (`user_id`,`project_id`,`filter_id`),
  ADD KEY `type` (`source`);

ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);


ALTER TABLE `def_nodegoat_custom_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_import_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_import_string_object_pairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_import_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_import_template_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_linked_data_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_public_interfaces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_nodegoat_public_interface_texts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
