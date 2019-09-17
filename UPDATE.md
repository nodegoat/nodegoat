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
