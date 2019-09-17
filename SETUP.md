# SETUP

## 1100CC & nodegoat

Make sure 1100CC is configured or your server and a new SITE named 'nodegoat' has been added and setup (see the [1100CC SETUP](https://github.com/LAB1100/1100CC/blob/master/SETUP.md) instructions).

Update the new nodegoat setup with this repository:
1. Overwrite the new `./APP/nodegoat` and  `./APP/SETTINGS/nodegoat` directories in your 1100CC directory with the `./APP/nodegoat` and `./APP/SETTINGS/nodegoat` directories from this repository.
1. Copy `./APP/STORAGE/nodegoat/CMS/css/templates.css` from this repository to the same path in your 1100CC directory.
1. Update `./APP/SETTINGS/nodegoat/settings.php` to configure the database passwords.
1. Add the appropriate databases and run the additional SQL, see [Database](SETUP.md#database).
1. Compile nodegoat's services, see [Programs](SETUP.md#programs).
1. Login to your nodegoat CMS (cms.yournodegoathost.com), go to User Management, and add a new user to 'User' (see the [1100CC Guides](https://lab1100.com/1100cc/guides#create-user)).
1. You can now login with your new user account to your nodegoat HOME (yournodegoathost.com).
1. Login to your nodegoat CMS, go to Jobs, and make sure the Job Scheduler is running (see the [1100CC Guides](https://lab1100.com/1100cc/guides#run-jobs)).

## Database

In addition to the already created nodegoat_cms and nodegoat_home databases when setting up 1100CC, create the databases nodegoat_content and nodegoat_temp:

```sql
CREATE DATABASE nodegoat_content CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nodegoat_temp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Grant the 1100CC MySQL users their nodegoat_content and a nodegoat_temp privileges:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, DROP ON nodegoat_content.* TO 1100CC_cms@localhost;
GRANT SELECT, INSERT, UPDATE, DELETE ON nodegoat_content.* TO 1100CC_home@localhost;

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, CREATE TEMPORARY TABLES, EXECUTE, CREATE ROUTINE, ALTER ROUTINE ON nodegoat_temp.* TO 1100CC_cms@localhost;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, CREATE TEMPORARY TABLES, EXECUTE ON nodegoat_temp.* TO 1100CC_home@localhost;
```

Import additional SQL to their respective databases:
* [nodegoat_cms.cms_labels.sql](/setup/nodegoat_cms.cms_labels.sql) to the nodegoat_cms database.
* [nodegoat_cms.various.sql](/setup/nodegoat_cms.various.sql) to the nodegoat_cms database.
* [nodegoat_home.sql](/setup/nodegoat_home.sql) to the nodegoat_home database.
* [nodegoat_home.various.sql](/setup/nodegoat_home.various.sql) to the nodegoat_home database.
* [nodegoat_content.sql](/setup/nodegoat_content.sql) to the nodegoat_content database.

## Programs

The following services make use of the 1100CC Programs environment. Make sure you have setup Programs when configuring 1100CC.

### Network Analysis

To be able to use nodegoat's Network Analysis features, you have to build its service.

Copy `./PROGRAMS/network_analysis` from this repository to the same path in your 1100CC directory.

Libraries needed for inclusion:
* no additional libraries needed.

Libraries that need to be compiled:
* no additional libraries needed.
* Make sure you compiled Boost with its Graph Library (BGL) included.

Use to `creation_station.sh` script to build and link network_analysis.

```bash
cd /var/1100CC/PROGRAMS
./creation_station.sh
```

When a program is compiled successfully, the path `var/1100CC/PROGRAMS/RUN/network_analysis` is returned and is ready to be used.

Setup a Job to run the service:
1. Login to your nodegoat CMS and go to 'Jobs' (see the [1100CC Guides](https://lab1100.com/1100cc/guides#run-jobs)).
1. Find the job 'nodegoat Analysis Service'.
1. Set a port e.g. '44444' by editing its options. The service listens to your localhost.
1. Set the timing to 'Always'.
1. Save the updated Jobs list and make sure Jobs is running.
