# UPDATE

To get the version for your current nodegoat installation, view `./APP/nodegoat/CMS/info/version.txt`

Follow each section subsequently that comes after your current version:

## VERSION 7.1

Initial release.

## VERSION 7.2

Update 1100CC to 10.2 ([1100CC UPDATE](https://github.com/LAB1100/1100CC/blob/master/UPDATE.md)).

Update nodegoat [nodegoat_cms.cms_labels.sql](/setup/nodegoat_cms.cms_labels.sql).

---

Run SQL queries in database nodegoat_home:

```sql
ALTER TABLE `def_nodegoat_details` ADD `limit_import` INT(11) NOT NULL AFTER `limit_view`;

UPDATE def_nodegoat_details SET limit_import = 50000;

ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `social_dot_size_start` INT(11) NOT NULL AFTER `social_dot_size_max`, ADD `social_dot_size_stop` INT(11) NOT NULL AFTER `social_dot_size_start`;
```

## VERSION 7.3

Update 1100CC to 10.3 ([1100CC UPDATE](https://github.com/LAB1100/1100CC/blob/master/UPDATE.md)).

Update nodegoat [nodegoat_cms.cms_labels.sql](/setup/nodegoat_cms.cms_labels.sql).

---

Run SQL queries in database nodegoat_home:

```sql
ALTER TABLE `def_nodegoat_custom_projects` CHANGE `source_referencing` `source_referencing_enable` TINYINT(1) NOT NULL, CHANGE `discussion_provide` `discussion_enable` TINYINT(1) NOT NULL, CHANGE `full_scope` `full_scope_enable` TINYINT(1) NOT NULL;
ALTER TABLE `def_nodegoat_custom_projects` ADD `date_cycle_enable` BOOLEAN NOT NULL AFTER `discussion_enable`;

CREATE TABLE `def_nodegoat_custom_project_date_types` (
  `project_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `def_nodegoat_custom_project_date_types`
  ADD PRIMARY KEY (`project_id`,`type_id`);

CREATE TABLE `def_nodegoat_custom_project_type_export_settings` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `format_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `format_include_description_name` tinyint(1) DEFAULT NULL,
  `format_object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_object` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `def_nodegoat_custom_project_type_export_settings`
  ADD PRIMARY KEY (`project_id`,`user_id`,`id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

ALTER TABLE `user_preferences` DROP `format_type`, DROP `format_include_description_name`, DROP `format_settings`, DROP `export_types`;

ALTER TABLE `def_nodegoat_custom_project_types` CHANGE `type_definition_id` `type_information` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `def_nodegoat_custom_project_type_configuration` ADD `information` TEXT NULL DEFAULT NULL AFTER `view`;
ALTER TABLE `def_nodegoat_custom_project_type_include_referenced_types` ADD `information` TEXT NULL DEFAULT NULL AFTER `view`;

ALTER TABLE `def_nodegoat_import_template_columns` ADD `overwrite` INT(11) NOT NULL AFTER `use_type_object_id`, ADD `ignore_when` INT(11) NOT NULL AFTER `overwrite`; 
ALTER TABLE `def_nodegoat_import_templates` ADD `use_log` BOOLEAN NOT NULL AFTER `description`; 

CREATE TABLE `data_nodegoat_import_template_log` (
  `template_id` int(11) NOT NULL,
  `row_number` int(11) NOT NULL,
  `object_id` int(11) DEFAULT NULL,
  `row_data` text COLLATE utf8mb4_unicode_ci,
  `row_filter` text COLLATE utf8mb4_unicode_ci,
  `row_results` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `data_nodegoat_import_template_log`
  ADD PRIMARY KEY (`template_id`,`row_number`);

ALTER TABLE `data_nodegoat_import_template_log` ADD INDEX(`template_id`);
ALTER TABLE `def_nodegoat_import_string_object_pairs` ADD INDEX(`type_id`);

ALTER TABLE `def_nodegoat_custom_project_types` ADD `type_edit` BOOLEAN NOT NULL DEFAULT TRUE AFTER `type_information`;

ALTER TABLE `def_nodegoat_details` ADD `processing_memory` INT NOT NULL AFTER `unique_row`, ADD `processing_time` INT NOT NULL AFTER `processing_memory`;
UPDATE def_nodegoat_details SET processing_memory = 2048, processing_time = 240;

ALTER TABLE `def_nodegoat_import_template_columns` CHANGE `use_as_filter` `use_as_filter` BOOLEAN NOT NULL, CHANGE `use_type_object_id` `use_object_id_as_filter` BOOLEAN NOT NULL, CHANGE `overwrite` `overwrite` BOOLEAN NOT NULL, CHANGE `ignore_when` `ignore_when` TINYINT(1) NOT NULL, CHANGE `source_link_position` `heading_for_source_link` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `heading` `column_heading` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, CHANGE `splitter` `cell_splitter` VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, CHANGE `generate` `generate_from_split` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

ALTER TABLE `def_nodegoat_import_template_columns`
  DROP `is_source`,
  DROP `source_type_id`;
```

---

Run SQL queries in database nodegoat_content:

```sql
ALTER TABLE `data_type_objects` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

UPDATE data_type_objects SET name = NULL
	WHERE name = '' AND EXISTS (SELECT TRUE FROM def_types AS test WHERE test.use_object_name = FALSE AND test.id = type_id);

ALTER TABLE `def_type_object_sub_details` CHANGE `is_date_range` `is_date_period` TINYINT(1) NOT NULL;

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

ALTER TABLE `data_type_object_sub_date_chronology`
  ADD PRIMARY KEY (`object_sub_id`,`version`,`identifier`) USING BTREE,
  ADD KEY `cycle_object_id` (`cycle_object_id`) USING BTREE,
  ADD KEY `date_object_sub_id` (`date_object_sub_id`);

ALTER TABLE `data_type_object_sub_date` CHANGE `date_start` `date_start_start` BIGINT NOT NULL, CHANGE `date_end` `date_end_end` BIGINT NOT NULL;

ALTER TABLE `data_type_object_sub_date` ADD `span_period_amount` SMALLINT NOT NULL DEFAULT '0' AFTER `date_end_end`, ADD `span_period_unit` TINYINT NOT NULL DEFAULT '0' AFTER `span_period_amount`, ADD `span_cycle_object_id` INT NOT NULL DEFAULT '0' AFTER `span_period_unit`;

ALTER TABLE `data_type_object_sub_date` ADD INDEX(`span_cycle_object_id`);

INSERT INTO data_type_object_sub_date_chronology
	(object_sub_id, date_value, date_direction, identifier, active, version)
	SELECT nodegoat_tos_date.object_sub_id, nodegoat_tos_date.date_start_start, 0, 1, TRUE, nodegoat_tos_date.version
			FROM data_type_object_sub_date AS nodegoat_tos_date
ON DUPLICATE KEY UPDATE object_sub_id = nodegoat_tos_date.object_sub_id;

INSERT INTO data_type_object_sub_date_chronology
	(object_sub_id, date_value, date_direction, identifier, active, version)
	SELECT nodegoat_tos_date.object_sub_id, nodegoat_tos_date.date_end_end, 0, 4, TRUE, nodegoat_tos_date.version
			FROM data_type_object_sub_date AS nodegoat_tos_date
			JOIN data_type_object_subs AS nodegoat_tos ON (nodegoat_tos.id = nodegoat_tos_date.object_sub_id AND nodegoat_tos.date_version = nodegoat_tos_date.version)
			JOIN def_type_object_sub_details AS nodegoat_tos_det ON (nodegoat_tos_det.id = nodegoat_tos.object_sub_details_id AND nodegoat_tos_det.is_date_period = TRUE)
ON DUPLICATE KEY UPDATE object_sub_id = nodegoat_tos_date.object_sub_id;

ALTER TABLE `data_type_object_sub_date` DROP `date_start_start`, DROP `date_end_end`;

RENAME TABLE `data_type_object_dating` TO `data_type_object_status`;

ALTER TABLE `data_type_object_status` ADD `status` SMALLINT NOT NULL DEFAULT '0' AFTER `date_discussion`;
ALTER TABLE `data_type_object_status` ADD INDEX(`status`);

CREATE TABLE `cache_type_object_sub_date_path` (
  `object_sub_id` int(11) NOT NULL,
  `path_object_sub_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `cache_type_object_sub_date_path`
  ADD PRIMARY KEY (`object_sub_id`,`path_object_sub_id`),
  ADD KEY `status` (`status`);

ALTER TABLE `cache_type_object_sub_location` CHANGE `status` `status` TINYINT(4) NOT NULL DEFAULT '0';
ALTER TABLE `cache_type_object_sub_location_path` CHANGE `status` `status` TINYINT(4) NOT NULL DEFAULT '0';

CREATE TABLE `cache_type_object_sub_date` (
  `object_sub_id` int(11) NOT NULL,
  `date_start_start` bigint(11) NOT NULL DEFAULT '0',
  `date_start_end` bigint(11) NULL,
  `date_end_start` bigint(11) NULL,
  `date_end_end` bigint(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `cache_type_object_sub_date`
  ADD PRIMARY KEY (`object_sub_id`),
  ADD KEY `status` (`status`);

ALTER TABLE `def_type_object_sub_details` CHANGE `date_use_object_sub_description_id` `date_start_use_object_sub_description_id` INT(11) NOT NULL DEFAULT '0', CHANGE `date_use_object_description_id` `date_start_use_object_description_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `def_type_object_sub_details` ADD `date_end_use_object_sub_description_id` INT NOT NULL DEFAULT '0' AFTER `date_start_use_object_description_id`, ADD `date_end_use_object_description_id` INT NOT NULL DEFAULT '0' AFTER `date_end_use_object_sub_description_id`;
ALTER TABLE `def_type_object_sub_details` CHANGE `is_unique` `is_single` TINYINT(1) NOT NULL;

ALTER TABLE `cache_type_object_sub_date_path` DROP INDEX `status`;
ALTER TABLE `cache_type_object_sub_date_path` DROP PRIMARY KEY;
ALTER TABLE `cache_type_object_sub_date_path` ADD `state` TINYINT NOT NULL DEFAULT '0' AFTER `status`, ADD INDEX (`state`);
ALTER TABLE `cache_type_object_sub_date_path` ADD `active` BOOLEAN NOT NULL DEFAULT FALSE AFTER `path_object_sub_id`;
ALTER TABLE `cache_type_object_sub_date_path` ADD PRIMARY KEY (`object_sub_id`, `path_object_sub_id`, `active`, `status`) USING BTREE;

ALTER TABLE `cache_type_object_sub_date` DROP INDEX `status`;
ALTER TABLE `cache_type_object_sub_date` DROP PRIMARY KEY;
ALTER TABLE `cache_type_object_sub_date` ADD `state` TINYINT NOT NULL DEFAULT '0' AFTER `status`, ADD INDEX (`state`);
ALTER TABLE `cache_type_object_sub_date` ADD `active` BOOLEAN NOT NULL DEFAULT FALSE AFTER `date_end_end`;
ALTER TABLE `cache_type_object_sub_date` ADD PRIMARY KEY (`object_sub_id`, `active`, `status`) USING BTREE;

ALTER TABLE `cache_type_object_sub_location_path` DROP INDEX `status`;
ALTER TABLE `cache_type_object_sub_location_path` DROP PRIMARY KEY;
ALTER TABLE `cache_type_object_sub_location_path` ADD `state` TINYINT NOT NULL DEFAULT '0' AFTER `status`, ADD INDEX (`state`);
ALTER TABLE `cache_type_object_sub_location_path` ADD `active` BOOLEAN NOT NULL DEFAULT FALSE AFTER `path_object_sub_id`;
ALTER TABLE `cache_type_object_sub_location_path` ADD PRIMARY KEY (`object_sub_id`, `path_object_sub_id`, `active`, `status`) USING BTREE;
ALTER TABLE `cache_type_object_sub_location_path` ADD INDEX(`path_object_sub_id`);

ALTER TABLE `cache_type_object_sub_location` DROP INDEX `status`;
ALTER TABLE `cache_type_object_sub_location` DROP PRIMARY KEY;
ALTER TABLE `cache_type_object_sub_location` ADD `state` TINYINT NOT NULL DEFAULT '0' AFTER `status`, ADD INDEX (`state`);
ALTER TABLE `cache_type_object_sub_location` ADD `active` BOOLEAN NOT NULL DEFAULT FALSE AFTER `ref_object_sub_details_id`;
ALTER TABLE `cache_type_object_sub_location` ADD PRIMARY KEY (`object_sub_id`, `geometry_object_sub_id`, `active`, `status`) USING BTREE;

ALTER TABLE `def_types` ADD `clearance_edit` TINYINT NOT NULL AFTER `condition_id`;
```

---

Run SQL queries in database nodegoat_cms:

```sql
INSERT INTO `site_jobs` (`module`, `method`, `options`, `seconds`, `date_executed`, `running`, `process_id`, `process_date`) VALUES
('cms_nodegoat_definitions', 'runTypeObjectCaching', '', 1, '2000-01-01 00:00:00', 0, NULL, NULL);
```

Login to your nodegoat CMS, go to Jobs, and make sure the Job Scheduler is running (see the [1100CC Guides](https://lab1100.com/1100cc/guides#run-jobs)).

---

Run SQL queries in database nodegoat_temp:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, CREATE TEMPORARY TABLES, EXECUTE, CREATE ROUTINE, ALTER ROUTINE ON nodegoat_temp.* TO 1100CC_cms@localhost;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, CREATE TEMPORARY TABLES, EXECUTE ON nodegoat_temp.* TO 1100CC_home@localhost;
```

## VERSION 8.0

Update 1100CC to 10.4 ([1100CC UPDATE](https://github.com/LAB1100/1100CC/blob/master/UPDATE.md)).

Update nodegoat [nodegoat_cms.cms_labels.sql](/setup/nodegoat_cms.cms_labels.sql).

Install the GDAL library (Debian & Ubunutu: gdal-bin).

Create the directory `./SAFE/nodegoat` with restrictive clearance.

Empty the directory `./APP/CACHE/nodegoat/scenarios/`.

---

Run SQL queries in database nodegoat_cms:

```sql
UPDATE `site_apis` SET `documentation_url` = 'https://documentation.nodegoat.net/API' WHERE `site_apis`.`id` = 1;

INSERT INTO `site_pages` (`id`, `name`, `title`, `directory_id`, `template_id`, `master_id`, `url`, `html`, `script`, `publish`, `clearance`, `sort`) VALUES
(47, 'patternpairs', 'Pattern Pairs', 7, 0, 13, '', '', '', 1, 1, 3);

INSERT INTO `site_page_modules` (`id`, `page_id`, `x`, `y`, `module`, `var`, `shortcut`, `shortcut_root`) VALUES
(119, 47, 0, 1, 'data_pattern_pairs', '', '', 0);

INSERT INTO user_page_clearance
	(user_id, page_id)
	SELECT user_id, 47 AS page_id
		FROM user_page_clearance
		WHERE (page_id = 29 OR page_id = 35)
		GROUP BY user_id;
```

---

Run SQL queries in database nodegoat_home:

```sql
ALTER TABLE `def_nodegoat_custom_projects` CHANGE `date_cycle_enable` `system_date_cycle_enable` TINYINT(1) NOT NULL;
ALTER TABLE `def_nodegoat_custom_projects` ADD `system_ingestion_enable` BOOLEAN NOT NULL AFTER `system_date_cycle_enable`;

ALTER TABLE `def_nodegoat_import_template_columns` DROP `target_type_id`;
ALTER TABLE `def_nodegoat_import_templates` CHANGE `source_file_id` `source_id` INT(11) NOT NULL;

ALTER TABLE `def_nodegoat_import_template_columns` CHANGE `column_heading` `pointer_heading` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `def_nodegoat_import_template_columns` CHANGE `use_as_filter` `use_filter_object` TINYINT(1) NOT NULL, CHANGE `use_object_id_as_filter` `use_filter_object_identifier` TINYINT(1) NOT NULL;

ALTER TABLE `def_nodegoat_import_template_columns` ADD `pointer_class` SMALLINT NOT NULL AFTER `pointer_heading`;

UPDATE `def_nodegoat_import_template_columns` SET `pointer_class` = 2 WHERE `use_filter_object_identifier` = 0 AND `use_filter_object` = 1;
UPDATE `def_nodegoat_import_template_columns` SET `pointer_class` = 1 WHERE `use_filter_object_identifier` = 1 AND `use_filter_object` = 1;
UPDATE `def_nodegoat_import_template_columns` SET `pointer_class` = 0 WHERE `use_filter_object_identifier` = 0 AND `use_filter_object` = 0;

ALTER TABLE `def_nodegoat_import_template_columns` DROP `use_filter_object`, DROP `use_filter_object_identifier`;

ALTER TABLE `def_nodegoat_import_template_columns` CHANGE `element_id` `element_id` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `def_nodegoat_import_template_columns` CHANGE `element_type_element_id` `element_type_element_id` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `def_nodegoat_import_template_columns` CHANGE `cell_splitter` `value_split` VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, CHANGE `generate_from_split` `value_index` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, 'so_', 'object_sub_details-'), element_type_element_id = REPLACE(element_type_element_id, 'so_', 'object_sub_details-');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, 'o_id', 'object-id'), element_type_element_id = REPLACE(element_type_element_id, 'o_id', 'object-id');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, 'o_name', 'object-name'), element_type_element_id = REPLACE(element_type_element_id, 'o_name', 'object-name');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, 'o_', 'object_description-'), element_type_element_id = REPLACE(element_type_element_id, 'o_', 'object_description-');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_osd_', '-object_sub_description-'), element_type_element_id = REPLACE(element_type_element_id, '_osd_', '-object_sub_description-');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_date-', '-date_'), element_type_element_id = REPLACE(element_type_element_id, '_date-', '-date_');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_chronology', '-date_chronology'), element_type_element_id = REPLACE(element_type_element_id, '_chronology', '-date_chronology');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_geometry', '-location_geometry'), element_type_element_id = REPLACE(element_type_element_id, '_geometry', '-location_geometry');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_lat', '-location_latitude'), element_type_element_id = REPLACE(element_type_element_id, '_lat', '-location_latitude');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_lon', '-location_longitude'), element_type_element_id = REPLACE(element_type_element_id, '_lon', '-location_longitude');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_location-ref-type-id_', '-location_ref_type_id-'), element_type_element_id = REPLACE(element_type_element_id, '_location-ref-type-id_', '-location_ref_type_id-');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_location-ref-type-id', '-location_ref_type_id'), element_type_element_id = REPLACE(element_type_element_id, '_location-ref-type-id', '-location_ref_type_id');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_sub-details-lock', '-object_sub_details_lock'), element_type_element_id = REPLACE(element_type_element_id, '_sub-details-lock', '-object_sub_details_lock');
UPDATE def_nodegoat_import_template_columns SET element_id = REPLACE(element_id, '_type-lock_', '-type_lock-'), element_type_element_id = REPLACE(element_type_element_id, '_type-lock_', '-type_lock-');

ALTER TABLE `data_nodegoat_import_template_log` DROP INDEX `template_id`;

DROP TABLE `def_nodegoat_import_string_object_pairs`;

CREATE TABLE `def_nodegoat_ingest_string_object_pairs` (
  `type_id` int(11) NOT NULL,
  `identifier` binary(16) NOT NULL,
  `filter_values` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `def_nodegoat_ingest_string_object_pairs`
  ADD PRIMARY KEY (`type_id`,`identifier`);

ALTER TABLE `def_nodegoat_details` ADD `limit_file_size` VARCHAR(20) NOT NULL AFTER `limit_import`;

ALTER TABLE `data_nodegoat_import_template_log` CHANGE `row_number` `row_identifier` INT NOT NULL;

ALTER TABLE `def_nodegoat_linked_data_resources` ADD `response_uri_conversion_id` INT NOT NULL AFTER `response_uri_template`, ADD `response_uri_conversion_output_identifier` VARCHAR(100) NOT NULL DEFAULT '' AFTER `response_uri_conversion_id`; 
ALTER TABLE `def_nodegoat_linked_data_resources` ADD `response_label_conversion_id` INT NOT NULL AFTER `response_label`, ADD `response_label_conversion_output_identifier` VARCHAR(100) NOT NULL DEFAULT '' AFTER `response_label_conversion_id`; 

ALTER TABLE `def_nodegoat_linked_data_resources` CHANGE `response_uri` `response_uri_value` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, CHANGE `response_label` `response_label_value` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL; 

ALTER TABLE `def_nodegoat_linked_data_resource_values` ADD `conversion_id` INT NOT NULL AFTER `value`, ADD `conversion_output_identifier` VARCHAR(100) NOT NULL DEFAULT '' AFTER `conversion_id`; 

CREATE TABLE `def_nodegoat_linked_data_conversions` (
  `id` int NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `output_placeholder` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_placeholder` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `def_nodegoat_linked_data_conversions`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `def_nodegoat_linked_data_conversions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `def_nodegoat_custom_project_visual_settings` CHANGE `map_url` `map_url` VARCHAR(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, CHANGE `geo_advanced` `geo_advanced` VARCHAR(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, CHANGE `social_advanced` `social_advanced` VARCHAR(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL; 

ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `capture_enable` BOOLEAN NULL AFTER `description`, ADD `capture_settings` VARCHAR(1000) NOT NULL AFTER `capture_enable`; 

ALTER TABLE `def_nodegoat_custom_project_visual_settings` CHANGE `time_conditions_relative` `time_relative_graph` TINYINT(1) NULL DEFAULT NULL, CHANGE `time_conditions_cumulative` `time_cumulative_graph` TINYINT(1) NULL DEFAULT NULL; 
ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `time_background_color` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `social_advanced`; 

ALTER TABLE `def_nodegoat_custom_project_type_frames` CHANGE `area_geo_scale` `area_geo_zoom_scale` SMALLINT NOT NULL, CHANGE `area_social_zoom` `area_social_zoom_level` TINYINT NOT NULL;
ALTER TABLE `def_nodegoat_custom_project_type_frames` ADD `area_geo_zoom_min` TINYINT NOT NULL AFTER `area_geo_zoom_scale`, ADD `area_geo_zoom_max` TINYINT NOT NULL AFTER `area_geo_zoom_min`; 
ALTER TABLE `def_nodegoat_custom_project_type_frames` ADD `area_social_zoom_min` TINYINT NOT NULL AFTER `area_social_zoom_level`, ADD `area_social_zoom_max` TINYINT NOT NULL AFTER `area_social_zoom_min`; 

ALTER TABLE `def_nodegoat_public_interface_project_types` CHANGE `list` `list` TINYINT NULL DEFAULT NULL, CHANGE `browse` `browse` TINYINT NULL DEFAULT NULL, CHANGE `geographic_visualisation` `geographic_visualisation` TINYINT NULL DEFAULT NULL, CHANGE `social_visualisation` `social_visualisation` TINYINT NULL DEFAULT NULL, CHANGE `time_visualisation` `time_visualisation` TINYINT NULL DEFAULT NULL; 
ALTER TABLE `def_nodegoat_linked_data_resources` ADD `url_headers` VARCHAR(5000) NOT NULL AFTER `url_options`; 
ALTER TABLE `def_nodegoat_ingest_string_object_pairs` CHANGE `object_id` `object_id` INT NULL; 
ALTER TABLE `def_nodegoat_import_templates` ADD `mode` TINYINT NOT NULL AFTER `source_id`;

ALTER TABLE `def_nodegoat_linked_data_resources` CHANGE `name` `name` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL; 
ALTER TABLE `def_nodegoat_import_templates` CHANGE `name` `name` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL; 

ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `map_show` BOOLEAN NULL DEFAULT NULL AFTER `line_offset`; 
UPDATE `def_nodegoat_custom_project_visual_settings` SET `map_show` = FALSE WHERE `geo_background_color` != '';

ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `location_offset` SMALLINT NULL AFTER `location_threshold`; 
ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `location_position` VARCHAR(100) NOT NULL AFTER `location_offset`; 
ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `social_force` VARCHAR(100) NOT NULL AFTER `social_line_show`;
ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `social_forceatlas2` VARCHAR(250) NOT NULL AFTER `social_force`;
ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `location_opacity` FLOAT NOT NULL AFTER `location_color`; 

ALTER TABLE `def_nodegoat_custom_project_visual_settings` ADD `time_bar_color` VARCHAR(10) NOT NULL AFTER `social_advanced`, ADD `time_bar_opacity` FLOAT NOT NULL AFTER `time_bar_color`; 

RENAME TABLE `def_nodegoat_ingest_string_object_pairs` TO `def_nodegoat_pattern_type_object_pairs`; 

ALTER TABLE `def_nodegoat_pattern_type_object_pairs` ADD `composition` TINYINT NOT NULL AFTER `object_id`; 
UPDATE `def_nodegoat_pattern_type_object_pairs` SET `composition` = 1 WHERE `filter_values` LIKE '{"%';

ALTER TABLE `def_nodegoat_pattern_type_object_pairs` CHANGE `filter_values` `pattern_value` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL; 

TRUNCATE TABLE `data_nodegoat_custom_project_type_scenario_cache`;
```

---

Run SQL queries in database nodegoat_content:

```sql
ALTER TABLE `def_types` ADD `class` TINYINT NOT NULL AFTER `id`;

UPDATE `def_types` SET `class` = 1 WHERE `is_classification` = TRUE;
UPDATE `def_types` SET `class` = 2 WHERE `is_reversal` = TRUE;

ALTER TABLE `def_types` DROP `is_classification`, DROP `is_reversal`;
ALTER TABLE `def_types` ADD INDEX(`class`);

START TRANSACTION;

ALTER TABLE `data_type_object_version` ADD `system_object_id` INT NULL DEFAULT NULL AFTER `user_id`;
ALTER TABLE `data_type_object_definition_version` ADD `system_object_id` INT NULL DEFAULT NULL AFTER `user_id`;
ALTER TABLE `data_type_object_sub_version` ADD `system_object_id` INT NULL DEFAULT NULL AFTER `user_id`;
ALTER TABLE `data_type_object_sub_definition_version` ADD `system_object_id` INT NULL DEFAULT NULL AFTER `user_id`;

ALTER TABLE `data_type_object_version` ADD INDEX `system_object_id` (`system_object_id`, `object_id`) USING BTREE;
ALTER TABLE `data_type_object_definition_version` ADD INDEX `system_object_id` (`system_object_id`, `object_id`) USING BTREE;
ALTER TABLE `data_type_object_sub_version` ADD INDEX `system_object_id` (`system_object_id`, `object_sub_id`) USING BTREE;
ALTER TABLE `data_type_object_sub_definition_version` ADD INDEX `system_object_id` (`system_object_id`, `object_sub_id`) USING BTREE;

COMMIT;

CREATE TABLE `data_type_object_definitions_modules` (
  `object_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `object` mediumtext COLLATE utf8mb4_unicode_ci,
  `identifier` smallint(6) NOT NULL DEFAULT '0',
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `data_type_object_definitions_modules`
  ADD PRIMARY KEY (`object_id`,`object_description_id`,`identifier`,`version`),
  ADD KEY `object_description_id` (`object_description_id`),
  ADD KEY `active` (`active`,`status`);
  
CREATE TABLE `data_type_object_sub_definitions_modules` (
  `object_sub_id` int(11) NOT NULL,
  `object_sub_description_id` int(11) NOT NULL,
  `object` mediumtext COLLATE utf8mb4_unicode_ci,
  `version` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `data_type_object_sub_definitions_modules`
  ADD PRIMARY KEY (`object_sub_id`,`object_sub_description_id`,`version`),
  ADD KEY `object_sub_description_id` (`object_sub_description_id`),
  ADD KEY `active` (`active`,`status`);
  
CREATE TABLE `data_type_object_definitions_module_status` (
  `object_id` int(11) NOT NULL,
  `object_description_id` int(11) NOT NULL,
  `ref_object_id` int(11) NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `data_type_object_definitions_module_status`
  ADD PRIMARY KEY (`object_id`,`object_description_id`,`ref_object_id`);
```

---

Create and edit `./APP/SETTINGS/nodegoat/update.php` with the following and navigate to /cms_admin and Run 'Update 1100CC'.

```php
<?php

$res = DB::query("
	SELECT * FROM ".DATABASE_NODEGOAT_CONTENT.".data_type_object_filters
");

$arr = [];

while ($arr_row = $res->fetchArray()) {
	
	$arr[$arr_row['object_id']][$arr_row['ref_type_id']] = ['filter' => json_decode($arr_row['object'], true), 'scope' => json_decode($arr_row['scope_object'], true)];
}

foreach ($arr as $object_id => $arr_row) {
	
	$res = DB::query("
		INSERT INTO ".DB::getTable('DATA_NODEGOAT_TYPE_OBJECT_DEFINITIONS_MODULES')."
			(object_description_id, object_id, object, active)
				VALUES
			(-10, ".$object_id.", '".DBFunctions::strEscape(value2JSON($arr_row))."', TRUE)
	");
}
```

---

Run SQL queries in database nodegoat_content:

```sql
UPDATE `def_type_object_descriptions` SET id_id = -9 WHERE id_id = 'rc_ref_type_id';
ALTER TABLE `def_type_object_descriptions` CHANGE `id_id` `id_id` INT NULL DEFAULT NULL;

DROP TABLE `data_type_object_filters`;

UPDATE `def_types` SET `mode` = 2 WHERE `class` = 2 AND `mode` = 1;
UPDATE `def_types` SET `mode` = 1 WHERE `class` = 2 AND `mode` = 0;

START TRANSACTION;

UPDATE data_type_object_sub_date_chronology SET date_value = date_value + 5000 WHERE date_value IS NOT NULL AND date_value != 0 AND date_value > -9000000000000000000 AND date_value < 9000000000000000000;

COMMIT;

START TRANSACTION;

UPDATE data_type_object_definitions SET value_int = value_int + 5000
	WHERE value_int != 0 AND value_int > -9000000000000000000 AND value_int < 9000000000000000000 AND EXISTS (SELECT TRUE FROM def_type_object_descriptions AS test WHERE (test.value_type_base = 'date') AND test.id = object_description_id);
UPDATE data_type_object_sub_definitions SET value_int = value_int + 5000
	WHERE value_int != 0 AND value_int > -9000000000000000000 AND value_int < 9000000000000000000 AND EXISTS (SELECT TRUE FROM def_type_object_sub_descriptions AS test WHERE (test.value_type_base = 'date') AND test.id = object_sub_description_id);

COMMIT;

START TRANSACTION;

UPDATE data_type_object_definitions SET value_int = -(ABS(value_int - 5000) + 5000)
	WHERE value_int != 0 AND value_int < -5000 AND ABS(value_int) % 1000000 = 995000
		AND EXISTS (SELECT TRUE FROM def_type_object_descriptions AS test WHERE (test.value_type_base = 'date') AND test.id = object_description_id);

UPDATE data_type_object_sub_date_chronology SET date_value = -(ABS(date_value - 5000) + 5000) WHERE date_value IS NOT NULL AND date_value != 0 AND date_value < -5000 AND ABS(date_value) % 1000000 = 995000;

COMMIT;

ALTER TABLE `def_type_object_descriptions` CHANGE `sort` `sort` SMALLINT NOT NULL DEFAULT '0';
ALTER TABLE `def_type_object_sub_details` CHANGE `date_use_object_sub_details_id` `date_use_object_sub_details_id` INT NOT NULL, CHANGE `date_start_use_object_sub_description_id` `date_start_use_object_sub_description_id` INT NOT NULL, CHANGE `date_start_use_object_description_id` `date_start_use_object_description_id` INT NOT NULL, CHANGE `date_end_use_object_sub_description_id` `date_end_use_object_sub_description_id` INT NOT NULL, CHANGE `date_end_use_object_description_id` `date_end_use_object_description_id` INT NOT NULL, CHANGE `location_ref_only` `location_ref_only` TINYINT(1) NOT NULL, CHANGE `location_ref_type_id_locked` `location_ref_type_id_locked` TINYINT(1) NOT NULL, CHANGE `location_ref_object_sub_details_id_locked` `location_ref_object_sub_details_id_locked` TINYINT(1) NOT NULL, CHANGE `location_use_object_sub_details_id` `location_use_object_sub_details_id` INT NOT NULL, CHANGE `location_use_object_sub_description_id` `location_use_object_sub_description_id` INT NOT NULL, CHANGE `location_use_object_description_id` `location_use_object_description_id` INT NOT NULL, CHANGE `location_use_object_id` `location_use_object_id` TINYINT(1) NOT NULL, CHANGE `sort` `sort` SMALLINT NOT NULL; 
ALTER TABLE `def_type_object_sub_descriptions` CHANGE `value_type_base` `value_type_base` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL; 

ALTER TABLE `def_type_object_descriptions` ADD `value_type_serial` INT NULL DEFAULT NULL AFTER `value_type_options`;
ALTER TABLE `def_type_object_sub_descriptions` ADD `value_type_serial` INT NULL DEFAULT NULL AFTER `value_type_options`;

ALTER TABLE `def_type_object_sub_details` ADD `date_setting` TINYINT NOT NULL AFTER `is_date_period`, ADD `date_setting_type_id` INT NOT NULL AFTER `date_setting`, ADD `date_setting_object_sub_details_id` INT NOT NULL AFTER `date_setting_type_id`; 
ALTER TABLE `def_type_object_sub_details` CHANGE `location_ref_only` `location_setting` TINYINT NOT NULL;

ALTER TABLE `def_type_object_descriptions` CHANGE `value_type_options` `value_type_settings` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `def_type_object_sub_descriptions` CHANGE `value_type_options` `value_type_settings` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL; 

ALTER TABLE `data_type_object_definitions_modules` CHANGE `object` `object` JSON NULL DEFAULT NULL;
ALTER TABLE `data_type_object_sub_definitions_modules` CHANGE `object` `object` JSON NULL DEFAULT NULL; 

UPDATE `data_type_object_sub_location_geometry` SET `geometry` = ST_GeomFromGeoJSON(ST_AsGeoJSON(`geometry`), 2, 4326);

ALTER TABLE `data_type_object_definitions_modules` ADD `state` SMALLINT NOT NULL DEFAULT '0' AFTER `identifier`; 
ALTER TABLE `data_type_object_sub_definitions_modules` ADD `state` SMALLINT NOT NULL DEFAULT '0' AFTER `object`; 
```

## VERSION 8.1

Update 1100CC to 10.5 ([1100CC UPDATE](https://github.com/LAB1100/1100CC/blob/master/UPDATE.md)).

Optionally, use the `creation_station.sh` script to rebuild and link network_analysis. See [Programs - Network Analysis](SETUP.md#network-analysis).

## VERSION 8.2

Update 1100CC to 10.6 ([1100CC UPDATE](https://github.com/LAB1100/1100CC/blob/master/UPDATE.md)).

Empty the directory `./APP/CACHE/nodegoat/scenarios/`.

Update nodegoat [nodegoat_cms.cms_labels.sql](/setup/nodegoat_cms.cms_labels.sql).

---

Run SQL queries in database nodegoat_home:

```sql
ALTER TABLE `def_nodegoat_custom_projects` ADD `system_reconciliation_enable` BOOLEAN NOT NULL AFTER `system_ingestion_enable`;
```

---

Run SQL queries in database nodegoat_content:

```sql
ALTER TABLE `data_type_object_definition_version` DROP PRIMARY KEY, ADD PRIMARY KEY (`object_id`, `object_description_id`, `version`, `user_id`, `date`) USING BTREE;
```
