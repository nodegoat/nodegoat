INSERT INTO `cms_language` (`lang_code`, `label`, `is_user_selectable`, `is_default`) VALUES
('en', 'English', 1, 1),
('nl', 'Nederlands', 0, 0);

INSERT INTO `site_apis` (`id`, `name`, `clients_user_group_id`, `client_users_user_group_id`, `module`, `request_limit_amount`, `request_limit_unit`, `request_limit_ip`, `request_limit_global`, `documentation_url`) VALUES
(1, 'Data', 0, 1, 'data_api', 15, 1, 30, 10000, 'https://documentation.nodegoat.net/API');

INSERT INTO `site_api_hosts` (`api_id`, `host_name`) VALUES
(1, ':^api(\\.[^\\.]*)?\\.nodegoat\\.net$'),
(1, ':nodegoat.io');

INSERT INTO `site_details` (`unique_row`, `name`, `address`, `address_nr`, `zipcode`, `city`, `country`, `tel`, `fax`, `bank`, `bank_nr`, `email`, `title`, `description`, `head_tags`, `html`, `email_header`, `email_footer`, `analytics_account`, `facebook`, `twitter`, `youtube`, `email_1100cc`, `email_1100cc_host`, `email_1100cc_password`, `caching`, `caching_external`, `logging`, `throttle`, `https`, `show_system_errors`, `show_404`, `use_servers`) VALUES
(1, 'nodegoat', '', '', '', '', '', '', '', '', '', 'nodegoat@yournodegoatserver', 'nodegoat', '', '', '', '', '', '', '', '', '', 'nodegoat@yournodegoatserver', '', '', 1, 0, 1, 0, 0, 0, 0, 0);

INSERT INTO `site_details_hosts` (`name`) VALUES
(':^api(\\.[^\\.]*)?\\.nodegoat\\.net$'),
(':nodegoat.io');

INSERT INTO `site_directories` (`id`, `name`, `title`, `root`, `page_index_id`, `user_group_id`, `require_login`, `page_fallback_id`, `publish`) VALUES
(4, '', '', 1, 27, 0, 0, 0, 0),
(5, 'login', 'Login', 0, 15, 1, 1, 12, 0),
(6, 'management', 'Management', 0, 16, 0, 0, 0, 1),
(7, 'model', 'Model', 0, 13, 0, 0, 0, 1);

INSERT INTO `site_directory_closure` (`ancestor_id`, `descendant_id`, `path_length`, `sort`) VALUES
(4, 4, 0, 0),
(4, 5, 1, 0),
(4, 6, 2, 0),
(4, 7, 2, 0),
(5, 5, 0, 0),
(5, 6, 1, 0),
(5, 7, 1, 0),
(6, 6, 0, 0),
(7, 7, 0, 1);

INSERT INTO `site_jobs` (`module`, `method`, `options`, `seconds`, `running`, `process_id`, `process_date`) VALUES
('cms_nodegoat_definitions', 'buildTypeObjectCache', '', -1, 0, NULL, NULL),
('cms_nodegoat_definitions', 'runTypeObjectCaching', '', 1, 0, NULL, NULL);

INSERT INTO `site_pages` (`id`, `name`, `title`, `directory_id`, `template_id`, `master_id`, `url`, `html`, `script`, `publish`, `clearance`, `sort`) VALUES
(11, 'home', 'Home', 5, 3, 0, '', '', '', 0, 0, 2),
(12, 'login', 'Login', 5, 0, 27, '', '', '', 0, 0, 0),
(13, 'model', 'Model', 7, 0, 11, '', '', '', 1, 1, 0),
(15, 'data', 'Data', 5, 0, 11, '', '', '', 1, 0, 1),
(16, 'projects', 'Projects', 6, 0, 11, '', '', '', 1, 1, 0),
(17, 'users', 'Users', 6, 0, 11, '', '', '', 1, 1, 1),
(18, 'account', 'Account', 5, 7, 0, '', '', '', 0, 0, 5),
(19, 'messaging', 'Messaging', 5, 0, 11, '', '', '', 0, 0, 6),
(27, 'login', 'Login', 4, 13, 0, '', '', '', 1, 0, 4),
(29, 'import', 'Import', 7, 0, 11, '', '', '', 1, 1, 1),
(31, 'viewer', 'Public Interface', 4, 17, 0, '', '', '', 0, 0, 0),
(32, 'publicinterface', 'Public Interfaces', 6, 0, 16, '', '', '', 1, 1, 3),
(34, 'viewer', 'Viewer', 5, 17, 0, '', '', '', 0, 0, 3),
(35, 'linkeddata', 'Linked Data', 7, 0, 13, '', '', '', 1, 1, 0),
(38, 'api', 'API', 6, 0, 11, '', '', '', 1, 1, 2),
(47, 'patternpairs', 'Pattern Pairs', 7, 0, 13, '', '', '', 1, 1, 3);

INSERT INTO `site_page_modules` (`id`, `page_id`, `x`, `y`, `module`, `var`, `shortcut`, `shortcut_root`) VALUES
(42, 13, 0, 1, 'data_model', '', '', 0),
(45, 15, 0, 1, 'data_entry', '', '', 0),
(51, 17, 0, 1, 'register_by_user', '1', '', 0),
(53, 18, 0, 0, 'header', '', '', 0),
(55, 19, 0, 1, 'messaging', '{\"siblings\":\"1\",\"allow_message_all\":\"1\"}', '', 0),
(59, 16, 0, 1, 'custom_projects', '', '', 0),
(68, 18, 0, 1, 'account', '{\"allow_uname\":\"1\"}', '', 0),
(78, 27, 0, 0, 'custom_content', '16', '', 0),
(79, 27, 0, 1, 'login', '5', '', 0),
(85, 29, 0, 1, 'data_import', '', '', 0),
(86, 11, 0, 0, 'header', '', '', 0),
(100, 11, 0, 2, 'custom_content', '14', '', 0),
(105, 27, 0, 2, 'custom_content', '14', '', 0),
(106, 18, 0, 2, 'custom_content', '14', '', 0),
(108, 31, 0, 0, 'ui', '', '', 0),
(109, 32, 0, 1, 'public_interfaces', '', '', 0),
(112, 34, 0, 0, 'ui', '', '', 0),
(113, 35, 0, 1, 'data_linked_data', '', '', 0),
(114, 38, 0, 1, 'api_configuration', '{\"api_id\":\"1\"}', '', 0),
(118, 12, 0, 1, 'login', '', '', 0),
(119, 47, 0, 1, 'data_pattern_pairs', '', '', 0);

INSERT INTO `site_page_templates` (`id`, `name`, `html`, `html_raw`, `css`, `preview`, `column_count`, `row_count`, `margin_back`, `margin_mod`) VALUES
(3, 'app header + 1', '<div id=\"template-3\" class=\"container\"><div class=\"site\"><div class=\"mod\" id=\"mod-0_0\"></div><div class=\"back\" id=\"back-1\"><div class=\"mod\" id=\"mod-0_1\"></div></div></div><div class=\"full footer\" id=\"full-3\"><div class=\"site\"><div class=\"mod\" id=\"mod-0_2\"></div></div></div></div>\n', '<div class=\"mod\" style=\"left: 1px; top: 1px; width: 141px; height: 70px;\" info=\"mod-0-0-2-1\" sort=\"0-0\" mt=\"0\" mr=\"0\" mb=\"0\" ml=\"0\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div><div class=\"back\" style=\"left: 1px; top: 72px; width: 141px; height: 70px;\" info=\"back-0-1-2-1\" sort=\"1-0\" mt=\"1\" mr=\"1\" mb=\"1\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"><div class=\"mod\" style=\"left: 1px; top: 1px; width: 135px; height: 64px;\" info=\"mod-0-0-2-1\" sort=\"0-0\" mt=\"1\" mr=\"1\" mb=\"1\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div></div><div class=\"full\" style=\"left: 1px; top: 143px; width: 141px; height: 70px;\" info=\"full-0-2-2-1\" sort=\"2-0\" aright=\"0\" customclass=\"footer\"><div class=\"mod\" style=\"left: 1px; top: 1px; width: 135px; height: 64px;\" info=\"mod-0-0-2-1\" sort=\"0-0\" mt=\"0\" mr=\"1\" mb=\"0\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div></div>', '#template-3 .site { width: 1000px; margin: 0px auto; }\n						#template-3 .back-spacing { margin: 0px; border-width: 0px; }\n						#template-3 .mod-spacing { margin: 20px; border-width: 20px; }\n#template-3 #mod-0_0 { width: calc(100% - 0px);  margin-top: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }\n#template-3 #back-1 { width: calc(100% - 0px);  margin-top: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }\n#template-3 #mod-0_1 { width: calc(100% - 40px);  margin-left: 20px; margin-right: 20px; margin-top: 20px; margin-bottom: 20px; }\n#template-3 #full-3 {  }\n#template-3 #mod-0_2 { width: calc(100% - 40px);  margin-top: 0px; margin-left: 20px; margin-right: 20px; margin-top: 0px; margin-bottom: 0px; }\n', '<table><col style=\"width: 50%;\" /><col style=\"width: 50%;\" /><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_0\" colspan=\"2\" rowspan=\"1\"></td></tr><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_1\" colspan=\"2\" rowspan=\"1\"></td></tr><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_2\" colspan=\"2\" rowspan=\"1\"></td></tr></table>', '50,50', '0,0,0', 0, 20),
(7, 'app header + 1', '<div id=\"template-7\" class=\"container\"><div class=\"site\"><div class=\"mod\" id=\"mod-0_0\"></div><div class=\"back\" id=\"back-1\"><div class=\"mod\" id=\"mod-0_1\"></div><div class=\"mod\" id=\"mod-1_1\"></div></div></div><div class=\"full footer\" id=\"full-4\"><div class=\"site\"><div class=\"mod\" id=\"mod-0_2\"></div></div></div></div>\n', '<div class=\"mod\" style=\"left: 1px; top: 1px; width: 141px; height: 70px;\" info=\"mod-0-0-2-1\" sort=\"0-0\" mt=\"0\" mr=\"0\" mb=\"0\" ml=\"0\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div><div class=\"back\" style=\"left: 1px; top: 72px; width: 141px; height: 70px;\" info=\"back-0-1-2-1\" sort=\"1-0\" mt=\"1\" mr=\"1\" mb=\"1\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"><div class=\"mod\" style=\"left: 1px; top: 1px; width: 64px; height: 64px;\" info=\"mod-0-0-1-1\" sort=\"0-0\" mt=\"1\" mr=\"1\" mb=\"1\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div><div class=\"mod\" style=\"left: 72px; top: 1px; width: 64px; height: 64px;\" info=\"mod-1-0-1-1\" sort=\"0-1\" mt=\"1\" mr=\"1\" mb=\"1\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div></div><div class=\"full\" style=\"left: 1px; top: 143px; width: 141px; height: 70px;\" info=\"full-0-2-2-1\" sort=\"2-0\" aright=\"0\" customclass=\"footer\"><div class=\"mod\" style=\"left: 1px; top: 1px; width: 135px; height: 64px;\" info=\"mod-0-0-2-1\" sort=\"0-0\" mt=\"0\" mr=\"1\" mb=\"0\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div></div>', '#template-7 .site { width: 1000px; margin: 0px auto; }\n						#template-7 .back-spacing { margin: 0px; border-width: 0px; }\n						#template-7 .mod-spacing { margin: 20px; border-width: 20px; }\n#template-7 #mod-0_0 { width: calc(100% - 0px);  margin-top: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }\n#template-7 #back-1 { width: calc(100% - 0px);  margin-top: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }\n#template-7 #mod-0_1 { width: calc(50% - 40px);  margin-left: 20px; margin-right: 20px; margin-top: 20px; margin-bottom: 20px; }\n#template-7 #mod-1_1 { width: calc(50% - 40px);  margin-left: 20px; margin-right: 20px; margin-top: 20px; margin-bottom: 20px; }\n#template-7 #full-4 {  }\n#template-7 #mod-0_2 { width: calc(100% - 40px);  margin-top: 0px; margin-left: 20px; margin-right: 20px; margin-top: 0px; margin-bottom: 0px; }\n', '<table><col style=\"width: 50%;\" /><col style=\"width: 50%;\" /><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_0\" colspan=\"2\" rowspan=\"1\"></td></tr><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_1\" colspan=\"1\" rowspan=\"1\"></td><td id=\"mod-1_1\" colspan=\"1\" rowspan=\"1\"></td></tr><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_2\" colspan=\"2\" rowspan=\"1\"></td></tr></table>', '50,50', '0,0,0', 0, 20),
(13, 'page header + 1', '<div id=\"template-13\" class=\"container\"><div class=\"site\"><div class=\"mod\" id=\"mod-0_0\"></div><div class=\"back\" id=\"back-1\"><div class=\"mod\" id=\"mod-0_1\"></div></div></div><div class=\"full footer\" id=\"full-3\"><div class=\"site\"><div class=\"mod\" id=\"mod-0_2\"></div></div></div></div>\n', '<div aright=\"0\" pl=\"0\" pb=\"0\" pr=\"0\" pt=\"0\" mle=\"0\" mbe=\"0\" mre=\"0\" mte=\"0\" ml=\"0\" mb=\"0\" mr=\"0\" mt=\"0\" sort=\"0-0\" info=\"mod-0-0-2-1\" style=\"left: 1px; top: 1px; width: 141px; height: 70px;\" class=\"mod\"></div><div aright=\"0\" pl=\"0\" pb=\"0\" pr=\"0\" pt=\"0\" mle=\"0\" mbe=\"0\" mre=\"0\" mte=\"0\" ml=\"1\" mb=\"1\" mr=\"1\" mt=\"1\" sort=\"1-0\" info=\"back-0-1-2-1\" style=\"left: 1px; top: 72px; width: 141px; height: 70px;\" class=\"back\"><div aright=\"0\" pl=\"0\" pb=\"0\" pr=\"0\" pt=\"0\" mle=\"20\" mbe=\"20\" mre=\"20\" mte=\"20\" ml=\"1\" mb=\"1\" mr=\"1\" mt=\"1\" sort=\"0-0\" info=\"mod-0-0-2-1\" style=\"left: 1px; top: 1px; width: 135px; height: 64px;\" class=\"mod\"></div></div><div class=\"full\" style=\"left: 1px; top: 143px; width: 141px; height: 70px;\" info=\"full-0-2-2-1\" sort=\"2-0\" aright=\"0\" customclass=\"footer\"><div class=\"mod\" style=\"left: 1px; top: 1px; width: 135px; height: 64px;\" info=\"mod-0-0-2-1\" sort=\"0-0\" mt=\"0\" mr=\"1\" mb=\"0\" ml=\"1\" mte=\"0\" mre=\"0\" mbe=\"0\" mle=\"0\" pt=\"0\" pr=\"0\" pb=\"0\" pl=\"0\" aright=\"0\"></div></div>', '#template-13 .site { width: 1000px; margin: 0px auto; }\n						#template-13 .back-spacing { margin: 0px; border-width: 0px; }\n						#template-13 .mod-spacing { margin: 20px; border-width: 20px; }\n#template-13 #mod-0_0 { width: calc(100% - 0px);  margin-top: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }\n#template-13 #back-1 { width: calc(100% - 0px);  margin-top: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }\n#template-13 #mod-0_1 { width: calc(100% - 80px);  margin-top: 40px; margin-left: 40px; margin-right: 40px; margin-top: 40px; margin-bottom: 40px; }\n#template-13 #full-3 {  }\n#template-13 #mod-0_2 { width: calc(100% - 40px);  margin-top: 0px; margin-left: 20px; margin-right: 20px; margin-top: 0px; margin-bottom: 0px; }\n', '<table><col style=\"width: 50%;\" /><col style=\"width: 50%;\" /><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_0\" colspan=\"2\" rowspan=\"1\"></td></tr><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_1\" colspan=\"2\" rowspan=\"1\"></td></tr><tr style=\"height: 33.333333333333%;\"><td id=\"mod-0_2\" colspan=\"2\" rowspan=\"1\"></td></tr></table>', '50,50', '0,0,0', 0, 20),
(17, 'full', '<div id=\"template-17\" class=\"container\"><div class=\"full\" id=\"full-0\"><div class=\"site\"><div class=\"mod\" id=\"mod-0_0\"></div></div></div></div>\n', '<div aright=\"0\" pl=\"0\" pb=\"0\" pr=\"0\" pt=\"0\" mle=\"0\" mbe=\"0\" mre=\"0\" mte=\"0\" ml=\"1\" mb=\"1\" mr=\"1\" mt=\"1\" sort=\"0-0\" info=\"full-0-0-1-1\" style=\"left: 1px; top: 1px; width: 70px; height: 70px;\" class=\"full\"><div aright=\"0\" pl=\"0\" pb=\"0\" pr=\"0\" pt=\"0\" mle=\"0\" mbe=\"0\" mre=\"0\" mte=\"0\" ml=\"1\" mb=\"1\" mr=\"1\" mt=\"1\" sort=\"0-0\" info=\"mod-0-0-1-1\" style=\"left: 1px; top: 1px; width: 64px; height: 64px;\" class=\"mod\"></div></div>', '#template-17 .site { width: 1000px; margin: 0px auto; }\n						#template-17 .back-spacing { margin: 0px; border-width: 0px; }\n						#template-17 .mod-spacing { margin: 0px; border-width: 0px; }\n#template-17 #full-0 {  }\n#template-17 #mod-0_0 { width: calc(100% - 0px);  margin-top: 0px; margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }\n', '<table><col style=\"width: 100%;\" /><tr style=\"height: 100%;\"><td id=\"mod-0_0\" colspan=\"1\" rowspan=\"1\"></td></tr></table>', '100', '0', 0, 0);

INSERT INTO `site_user_groups` (`id`, `name`, `parent_id`) VALUES
(1, 'User', 0);

INSERT INTO `site_user_group_link` (`group_id`, `from_table`, `from_column`, `to_table`, `to_column`, `get_column`, `virtual_name`, `multi_source`, `multi_target`, `view`, `sort`) VALUES
(1, '[[cms]].users', 'id', '[[cms]].user_page_clearance', 'user_id', 'page_id', '', 0, 1, 0, 1),
(1, '[[cms]].users', 'id', '[[home]].user_details', 'user_id', '0', '', 0, 0, 0, 0),
(1, '[[cms]].users', 'id', '[[home]].user_link_nodegoat_custom_projects', 'user_id', 'project_id', '', 0, 1, 0, 2),
(1, '[[home]].user_details', 'user_id', '[[home]].user_preferences', 'user_id', '0', '', 0, 0, 0, 4),
(1, '[[home]].user_link_nodegoat_custom_projects', 'project_id', '[[home]].def_nodegoat_custom_projects', 'id', 'name', 'projects', 1, 0, 0, 3);
