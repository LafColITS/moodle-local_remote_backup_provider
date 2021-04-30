<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language file for local_remote_backup_provider
 *
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['remove_old_task'] = 'Remove old remote backup files';
$string['import'] = 'Import from remote';
$string['pluginname'] = 'Remote backup provider';
$string['pluginnotconfigured'] = 'The plugin is not configured';
$string['privacy:metadata'] = 'The Remote backup provider plugin does not store any personal data.';
$string['remotesite'] = 'Remote site';
$string['remotesite_desc'] = 'The fully-qualified domain of the remote site';
$string['wstoken'] = 'Web service token';
$string['wstoken_desc'] = 'Add the web service token from the remote site';
$string['enableuserprecheck'] = 'Enable a user check before proceeding to the course restore';
$string['enabluserprecheck_desc'] = 'ATTENTION: You need to configure this setting at the CLIENT site. 
                                    Please uncheck, if you have configured the remote site to create backups without user data.<br/><br/>
                                    Checking this option will display a downloadable user list of the users included in the backup. 
                                    There will be shown which users are going to be enrolled in the restored course and which users 
                                    in the backup match the local users.';
$string['userprecheck_fail_desc'] = '<p style="color: red;">Prechecks failed. This is most likely because the remote site has been configured to export
                 course backup data without user data. Please turn off the setting "enableuserprecheck" at your client site.
                 (Local Plugin: Remote Backup Provider > Settings)</p>';
$string['enableuserdata'] = 'Backup all courses with user data';
$string['enableuserdata_desc'] = 'ATTENTION: You need to configure this setting at the REMOTE site. If you uncheck this option, ALL courses will be backed up WITHOUT user data.';
$string['uniqueid'] = 'Matching user attribute';
$string['uniqueid_desc'] = 'Use same value on remote and local site! On the remote Moodle instance only courses are shown, 
                            that the user with the matching user attribute is allowed to see. In order to identify the 
                            user on the remote instance, you can use either username or email address or the user field 
                            idnumber. Make sure, that idnumber is unique.<br/><br/>
                            BE CAREFUL: If you want "Matching user attribute" to have an effect, do NOT define  a 
                            specific user for course exports.';
$string['specific_export_username'] = 'Specific user for course exports';
$string['specific_export_username_desc'] = 'ATTENTION: You need to configure this setting at the REMOTE site. If you enter a
                                    valid username, the search will be done with the user chosen in the setting. Also, 
                                    the user has to have the rights to create a backup for the courses. <br/>
                                    So no matter which user on the local instance wants to import a course, it will be
                                    determined by the user specified on the remote site, which course can be backed up. 
                                    (This depends on the permissions of the user).';
$string['specific_export_username_valid_desc'] = ' is a valid username.';
$string['specific_export_username_invalid_desc'] = ' is not a valid username.';
$string['nouserstoimport'] = 'No Users to import';
$string['username'] = 'Username';
$string['firstname'] = 'First name';
$string['lastname'] = 'Last name';
$string['useremail'] = 'Email Adress';
$string['issues'] = 'Issues';
$string['userstoimport'] = 'Users to import from course: ';
$string['action'] = 'Action';
$string['perfectmatch'] = 'Perfect match, we merge users';
$string['notallowedtocreate'] = 'Your are not allowed to create this user';
$string['differentusername'] = 'Username is different';
$string['differentmail'] = 'Email is different. This will cause restore to fail.';
$string['nomatch'] = 'No match, create as a new user';
$string['existinguser'] = 'User already in our DB';
$string['samefirstandlastname'] = 'Same first and lastname';
$string['createasnew'] = 'Create user as new user.';
$string['mergewith'] = 'Merge with:';
$string['exportascsv'] = 'Export as CSV... :';

# error messages
$string['error_no_courses_found'] = '<p>We are sorry, no courses could be found...</p>';