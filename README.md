Moodle Remote Backup Provider
=============================

This local module allows you to restore a course from a remote Moodle instance into your current instance via a REST web service. The intended use case is quick restores from an archival Moodle instance into the current yearly instance.

It is limited to administrators but could in the future be extended for teacher use. Currently it is limited to one pair of source and target instances.

This is **alpha** code at present. It has been tested in a virtual environment but further testing is needed. Please report back all issues to [Github](https://github.com/mackensen/moodle-local_remote_backup_provider/issues).

Configuration
-------------
You will need to install this plugin on both the source and target instances. On the source Moodle instance you'll need to create the following:

1. An external web service.
2. A user with sufficient permissions to use said web service.
3. A token for that user. For additional security it should be restricted to the target server's IP address.

See [Using web services](https://docs.moodle.org/29/en/Using_web_services) in the Moodle documentation for information about creating and enabling web services. The user will need the following capabilities in addition to whichever protocol you enable:

- `moodle/course:view`
- `moodle/course:viewhiddencourses`
- `moodle/backup:backupcourse`

The web service will need the following functions:

- `local_remote_backup_provider_find_courses`
- `local_remote_backup_provider_get_course_backup_by_id`

On the target Moodle instance you will need to configure the token and source Moodle URL in the System Administration block under Local Plugins > Remote Backup Provider.

Requirements
------------
- Moodle 2.5 (build 2013051400 or later)

See branches for Moodle 2.7+ versions.

Installation
------------
Copy the remote_backup_provider folder into your /local directory and visit your Admin Notification page to complete the installation.

Author
------
Charles Fulton (fultonc@lafayette.edu)
