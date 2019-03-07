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
