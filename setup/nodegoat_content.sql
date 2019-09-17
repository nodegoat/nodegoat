CREATE TABLE `cache_type_object_sub_date` (
  `object_sub_id` int(11) NOT NULL,
  `date_start_start` bigint(11) NOT NULL DEFAULT '0',
  `date_start_end` bigint(11) DEFAULT NULL,
  `date_end_start` bigint(11) DEFAULT NULL,
  `date_end_end` bigint(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `state` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_type_object_sub_date_path` (
  `object_sub_id` int(11) NOT NULL,
  `path_object_sub_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `state` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_type_object_sub_location` (
  `object_sub_id` int(11) NOT NULL,
  `object_sub_details_id` int(11) NOT NULL,
  `geometry_object_sub_id` int(11) NOT NULL,
  `geometry_object_id` int(11) NOT NULL,
  `geometry_type_id` int(11) NOT NULL,
  `ref_object_id` int(11) DEFAULT NULL,
  `ref_type_id` int(11) DEFAULT NULL,
  `ref_object_sub_details_id` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `state` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_type_object_sub_location_path` (
  `object_sub_id` int(11) NOT NULL,
  `path_object_sub_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `state` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_objects` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_analyses` (
  `user_id` int(11) NOT NULL,
  `analysis_id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `number` double NOT NULL,
  `number_secondary` double DEFAULT NULL,
  `state` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_analysis_status` (
  `user_id` int(11) NOT NULL,
  `analysis_id` int(11) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_definitions` (
  `object_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `value` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value_int` bigint(20) NOT NULL DEFAULT '0',
  `value_text` longtext COLLATE utf8mb4_unicode_ci,
  `identifier` smallint(6) NOT NULL DEFAULT '0',
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_definitions_references` (
  `object_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL DEFAULT '0',
  `identifier` smallint(6) NOT NULL DEFAULT '0',
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_definition_objects` (
  `object_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `value` varchar(5000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identifier` int(11) NOT NULL,
  `state` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_definition_sources` (
  `object_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `value` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` binary(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_definition_version` (
  `object_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `user_id_audited` int(11) NOT NULL DEFAULT '0',
  `date_audited` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_discussion` (
  `object_id` int(11) NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_edited` datetime NOT NULL,
  `user_id_edited` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_filters` (
  `object_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_object` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_lock` (
  `object_id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `identifier` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sources` (
  `object_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `value` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` binary(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_status` (
  `object_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `date_object` datetime NOT NULL,
  `date_discussion` datetime DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_subs` (
  `id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `object_sub_details_id` int(11) NOT NULL,
  `date_version` int(11) DEFAULT NULL,
  `location_geometry_version` int(11) DEFAULT NULL,
  `location_ref_object_id` int(11) NOT NULL,
  `location_ref_type_id` int(11) NOT NULL,
  `location_ref_object_sub_details_id` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_date` (
  `object_sub_id` int(11) NOT NULL,
  `span_period_amount` smallint(6) NOT NULL DEFAULT '0',
  `span_period_unit` tinyint(4) NOT NULL DEFAULT '0',
  `span_cycle_object_id` int(11) NOT NULL DEFAULT '0',
  `version` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_date_chronology` (
  `object_sub_id` int(11) NOT NULL,
  `offset_amount` smallint(6) NOT NULL DEFAULT '0',
  `offset_unit` tinyint(4) NOT NULL DEFAULT '0',
  `cycle_object_id` int(11) NOT NULL DEFAULT '0',
  `cycle_direction` tinyint(4) NOT NULL DEFAULT '0',
  `date_value` bigint(20) DEFAULT NULL,
  `date_object_sub_id` int(11) DEFAULT NULL,
  `date_direction` tinyint(4) NOT NULL DEFAULT '0',
  `identifier` tinyint(4) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `version` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_definitions` (
  `object_sub_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `value` varchar(5000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value_int` bigint(20) NOT NULL DEFAULT '0',
  `value_text` mediumtext COLLATE utf8mb4_unicode_ci,
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_definitions_references` (
  `object_sub_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL DEFAULT '0',
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_definition_objects` (
  `object_sub_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `value` varchar(5000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identifier` int(11) NOT NULL,
  `state` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_definition_sources` (
  `object_sub_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `value` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` binary(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_definition_version` (
  `object_sub_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `user_id_audited` int(11) NOT NULL DEFAULT '0',
  `date_audited` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_location_geometry` (
  `object_sub_id` int(11) NOT NULL,
  `geometry` geometry NOT NULL,
  `version` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_sources` (
  `object_sub_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `value` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` binary(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_sub_version` (
  `object_sub_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `user_id_audited` int(11) NOT NULL DEFAULT '0',
  `date_audited` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `data_type_object_version` (
  `object_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `user_id_audited` int(11) NOT NULL DEFAULT '0',
  `date_audited` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_types` (
  `id` int(11) NOT NULL,
  `is_classification` tinyint(1) NOT NULL,
  `is_reversal` tinyint(1) NOT NULL,
  `mode` smallint(6) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `condition_id` int(11) NOT NULL,
  `clearance_edit` tinyint(4) NOT NULL,
  `date` datetime NOT NULL,
  `use_object_name` tinyint(1) NOT NULL,
  `object_name_in_overview` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_type_definitions` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_type_object_descriptions` (
  `id` int(11) NOT NULL,
  `id_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_type_base` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value_type_options` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_unique` tinyint(1) NOT NULL DEFAULT '0',
  `has_multi` tinyint(1) NOT NULL DEFAULT '0',
  `ref_type_id` int(11) NOT NULL,
  `in_name` tinyint(1) NOT NULL,
  `in_search` tinyint(1) NOT NULL DEFAULT '0',
  `in_overview` tinyint(1) NOT NULL DEFAULT '0',
  `is_identifier` tinyint(1) NOT NULL DEFAULT '0',
  `clearance_edit` tinyint(4) NOT NULL DEFAULT '0',
  `clearance_view` tinyint(4) NOT NULL DEFAULT '0',
  `sort` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_type_object_name_path` (
  `type_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `ref_object_description_id` int(11) NOT NULL,
  `ref_object_sub_details_id` int(11) NOT NULL,
  `org_object_description_id` int(11) NOT NULL,
  `org_object_sub_details_id` int(11) NOT NULL,
  `is_reference` tinyint(1) NOT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_type_object_search_path` (
  `type_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `ref_object_description_id` int(11) NOT NULL,
  `ref_object_sub_details_id` int(11) NOT NULL,
  `org_object_description_id` int(11) NOT NULL,
  `org_object_sub_details_id` int(11) NOT NULL,
  `is_reference` tinyint(1) NOT NULL,
  `sort` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_type_object_sub_descriptions` (
  `id` int(11) NOT NULL,
  `object_sub_details_id` int(11) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_type_base` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value_type_options` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL,
  `use_object_description_id` int(11) NOT NULL,
  `ref_type_id` int(11) NOT NULL,
  `in_name` tinyint(1) NOT NULL,
  `in_search` tinyint(1) NOT NULL,
  `in_overview` tinyint(1) NOT NULL,
  `clearance_edit` tinyint(4) NOT NULL,
  `clearance_view` tinyint(4) NOT NULL,
  `sort` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `def_type_object_sub_details` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL,
  `is_single` tinyint(1) NOT NULL,
  `clearance_edit` tinyint(4) NOT NULL,
  `clearance_view` tinyint(4) NOT NULL,
  `has_date` tinyint(1) NOT NULL,
  `is_date_period` tinyint(1) NOT NULL,
  `date_use_object_sub_details_id` int(11) NOT NULL DEFAULT '0',
  `date_start_use_object_sub_description_id` int(11) NOT NULL DEFAULT '0',
  `date_start_use_object_description_id` int(11) NOT NULL DEFAULT '0',
  `date_end_use_object_sub_description_id` int(11) NOT NULL DEFAULT '0',
  `date_end_use_object_description_id` int(11) NOT NULL DEFAULT '0',
  `has_location` tinyint(1) NOT NULL,
  `location_ref_only` tinyint(1) NOT NULL DEFAULT '0',
  `location_ref_type_id` int(11) NOT NULL,
  `location_ref_type_id_locked` tinyint(1) NOT NULL DEFAULT '0',
  `location_ref_object_sub_details_id` int(11) NOT NULL,
  `location_ref_object_sub_details_id_locked` tinyint(1) NOT NULL DEFAULT '0',
  `location_use_object_sub_details_id` int(11) NOT NULL DEFAULT '0',
  `location_use_object_sub_description_id` int(11) NOT NULL DEFAULT '0',
  `location_use_object_description_id` int(11) NOT NULL DEFAULT '0',
  `location_use_object_id` tinyint(1) NOT NULL DEFAULT '0',
  `sort` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `cache_type_object_sub_date`
  ADD PRIMARY KEY (`object_sub_id`,`active`,`status`) USING BTREE,
  ADD KEY `state` (`state`);

ALTER TABLE `cache_type_object_sub_date_path`
  ADD PRIMARY KEY (`object_sub_id`,`path_object_sub_id`,`active`,`status`) USING BTREE,
  ADD KEY `state` (`state`);

ALTER TABLE `cache_type_object_sub_location`
  ADD PRIMARY KEY (`object_sub_id`,`geometry_object_sub_id`,`active`,`status`) USING BTREE,
  ADD KEY `ref_type_id` (`ref_type_id`,`object_sub_details_id`,`ref_object_id`) USING BTREE,
  ADD KEY `state` (`state`);

ALTER TABLE `cache_type_object_sub_location_path`
  ADD PRIMARY KEY (`object_sub_id`,`path_object_sub_id`,`active`,`status`) USING BTREE,
  ADD KEY `state` (`state`),
  ADD KEY `path_object_sub_id` (`path_object_sub_id`);

ALTER TABLE `data_type_objects`
  ADD PRIMARY KEY (`id`,`version`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `active` (`active`,`status`);

ALTER TABLE `data_type_object_analyses`
  ADD PRIMARY KEY (`user_id`,`analysis_id`,`object_id`) USING BTREE,
  ADD KEY `state` (`state`) USING BTREE;

ALTER TABLE `data_type_object_analysis_status`
  ADD PRIMARY KEY (`user_id`,`analysis_id`),
  ADD KEY `date` (`date`);

ALTER TABLE `data_type_object_definitions`
  ADD PRIMARY KEY (`object_id`,`object_description_id`,`identifier`,`version`),
  ADD KEY `object_description_id` (`object_description_id`),
  ADD KEY `active` (`active`,`status`) USING BTREE;

ALTER TABLE `data_type_object_definitions_references`
  ADD PRIMARY KEY (`object_id`,`object_description_id`,`ref_object_id`,`identifier`,`version`),
  ADD KEY `ref_object_id` (`ref_object_id`),
  ADD KEY `object_id` (`object_id`,`ref_object_id`),
  ADD KEY `object_description_id` (`object_description_id`,`ref_object_id`),
  ADD KEY `active` (`active`,`status`);

ALTER TABLE `data_type_object_definition_objects`
  ADD PRIMARY KEY (`object_id`,`object_description_id`,`ref_object_id`,`identifier`),
  ADD KEY `ref_object_id` (`ref_object_id`),
  ADD KEY `object_description_id` (`object_description_id`,`ref_type_id`) USING BTREE,
  ADD KEY `state` (`state`);

ALTER TABLE `data_type_object_definition_sources`
  ADD PRIMARY KEY (`object_id`,`object_description_id`,`ref_object_id`,`hash`),
  ADD KEY `ref_type_id` (`ref_type_id`),
  ADD KEY `ref_object_id` (`ref_object_id`) USING BTREE;

ALTER TABLE `data_type_object_definition_version`
  ADD PRIMARY KEY (`object_description_id`,`object_id`,`version`,`user_id`,`date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_audited` (`user_id_audited`),
  ADD KEY `object_id` (`object_id`,`object_description_id`,`date_audited`);

ALTER TABLE `data_type_object_discussion`
  ADD PRIMARY KEY (`object_id`),
  ADD KEY `date_edited` (`date_edited`),
  ADD KEY `user_id_edited` (`user_id_edited`);

ALTER TABLE `data_type_object_filters`
  ADD PRIMARY KEY (`object_id`,`ref_type_id`);

ALTER TABLE `data_type_object_lock`
  ADD PRIMARY KEY (`object_id`,`type`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `data_type_object_sources`
  ADD PRIMARY KEY (`object_id`,`ref_object_id`,`hash`),
  ADD KEY `ref_type_id` (`ref_type_id`),
  ADD KEY `ref_object_id` (`ref_object_id`) USING BTREE;

ALTER TABLE `data_type_object_status`
  ADD PRIMARY KEY (`object_id`),
  ADD KEY `date` (`date`),
  ADD KEY `date_edited` (`date_object`,`date_discussion`),
  ADD KEY `status` (`status`);

ALTER TABLE `data_type_object_subs`
  ADD PRIMARY KEY (`id`,`version`),
  ADD KEY `object_id` (`object_id`,`object_sub_details_id`),
  ADD KEY `location_ref_object_id` (`location_ref_object_id`,`location_ref_type_id`,`location_ref_object_sub_details_id`),
  ADD KEY `object_sub_details_id` (`object_sub_details_id`,`location_ref_type_id`,`object_id`,`location_ref_object_id`) USING BTREE COMMENT 'Reversed Classifications',
  ADD KEY `active` (`active`,`status`);

ALTER TABLE `data_type_object_sub_date`
  ADD PRIMARY KEY (`object_sub_id`,`version`),
  ADD KEY `span_cycle_object_id` (`span_cycle_object_id`);

ALTER TABLE `data_type_object_sub_date_chronology`
  ADD PRIMARY KEY (`object_sub_id`,`version`,`identifier`) USING BTREE,
  ADD KEY `cycle_object_id` (`cycle_object_id`) USING BTREE,
  ADD KEY `date_object_sub_id` (`date_object_sub_id`);

ALTER TABLE `data_type_object_sub_definitions`
  ADD PRIMARY KEY (`object_sub_id`,`object_sub_description_id`,`version`),
  ADD KEY `active` (`active`,`status`);

ALTER TABLE `data_type_object_sub_definitions_references`
  ADD PRIMARY KEY (`object_sub_id`,`object_sub_description_id`,`ref_object_id`,`version`),
  ADD KEY `ref_object_id` (`ref_object_id`),
  ADD KEY `object_sub_id` (`object_sub_id`,`ref_object_id`),
  ADD KEY `active` (`active`,`status`);

ALTER TABLE `data_type_object_sub_definition_objects`
  ADD PRIMARY KEY (`object_sub_id`,`object_sub_description_id`,`ref_object_id`,`identifier`),
  ADD KEY `object_sub_description_id` (`object_sub_description_id`,`ref_type_id`) USING BTREE,
  ADD KEY `ref_object_id` (`ref_object_id`),
  ADD KEY `state` (`state`);

ALTER TABLE `data_type_object_sub_definition_sources`
  ADD PRIMARY KEY (`object_sub_id`,`object_sub_description_id`,`ref_object_id`,`hash`),
  ADD KEY `ref_type_id` (`ref_type_id`),
  ADD KEY `ref_object_id` (`ref_object_id`);

ALTER TABLE `data_type_object_sub_definition_version`
  ADD PRIMARY KEY (`object_sub_id`,`object_sub_description_id`,`version`,`user_id`,`date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_audited` (`user_id_audited`),
  ADD KEY `object_sub_id` (`object_sub_id`,`object_sub_description_id`,`date_audited`);

ALTER TABLE `data_type_object_sub_location_geometry`
  ADD PRIMARY KEY (`object_sub_id`,`version`) USING BTREE,
  ADD SPATIAL KEY `geometry` (`geometry`);

ALTER TABLE `data_type_object_sub_sources`
  ADD PRIMARY KEY (`object_sub_id`,`ref_object_id`,`hash`),
  ADD KEY `ref_type_id` (`ref_type_id`),
  ADD KEY `ref_object_id` (`ref_object_id`);

ALTER TABLE `data_type_object_sub_version`
  ADD PRIMARY KEY (`object_sub_id`,`version`,`user_id`,`date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_audited` (`user_id_audited`),
  ADD KEY `object_sub_id` (`object_sub_id`,`date_audited`);

ALTER TABLE `data_type_object_version`
  ADD PRIMARY KEY (`object_id`,`version`,`user_id`,`date`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_audited` (`user_id_audited`),
  ADD KEY `object_id` (`object_id`,`date_audited`);

ALTER TABLE `def_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_classification` (`is_classification`),
  ADD KEY `condition_id` (`condition_id`),
  ADD KEY `is_reversal` (`is_reversal`) USING BTREE;

ALTER TABLE `def_type_definitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

ALTER TABLE `def_type_object_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_id` (`id_id`,`type_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `ref_type_id` (`ref_type_id`),
  ADD KEY `in_search` (`in_search`),
  ADD KEY `in_name` (`in_name`),
  ADD KEY `value_type` (`value_type_base`),
  ADD KEY `is_identifier` (`is_identifier`);

ALTER TABLE `def_type_object_name_path`
  ADD PRIMARY KEY (`type_id`,`ref_type_id`,`ref_object_description_id`,`ref_object_sub_details_id`,`org_object_description_id`,`org_object_sub_details_id`,`sort`);

ALTER TABLE `def_type_object_search_path`
  ADD PRIMARY KEY (`type_id`,`ref_type_id`,`ref_object_description_id`,`ref_object_sub_details_id`,`org_object_description_id`,`org_object_sub_details_id`,`sort`);

ALTER TABLE `def_type_object_sub_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `object_sub_details_id` (`object_sub_details_id`),
  ADD KEY `ref_type_id` (`ref_type_id`),
  ADD KEY `use_object_description_id` (`use_object_description_id`),
  ADD KEY `value_type` (`value_type_base`);

ALTER TABLE `def_type_object_sub_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_ref_object_sub_details_id` (`location_ref_object_sub_details_id`),
  ADD KEY `location_ref_type_id` (`location_ref_type_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `date_use_object_sub_details_id` (`date_use_object_sub_details_id`),
  ADD KEY `date_use_object_sub_description_id` (`date_start_use_object_sub_description_id`),
  ADD KEY `date_use_object_description_id` (`date_start_use_object_description_id`),
  ADD KEY `location_use_object_sub_details_id` (`location_use_object_sub_details_id`),
  ADD KEY `location_use_object_sub_description_id` (`location_use_object_sub_description_id`),
  ADD KEY `location_use_object_description_id` (`location_use_object_description_id`),
  ADD KEY `location_use_object_id` (`location_use_object_id`);


ALTER TABLE `data_type_objects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `data_type_object_subs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_type_definitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_type_object_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_type_object_sub_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `def_type_object_sub_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
