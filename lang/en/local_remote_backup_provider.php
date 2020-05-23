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
$string['enableuserprecheck'] = 'Enable a user check before proceeding to the course restore.';
$string['enabluserprecheck_desc'] = 'Checking this option will display a downloadable user list of the users included in the backup. There will be shown which users are going to be enrolled in the restored course and which users in the backup match the local users.';
$string['uniqueid'] = 'Matching user attribute';
$string['uniqueid_desc'] = 'Use same value on remote and local site! On the remote Moodle instance only courses are shown, that the user with the matching user attribute is allowed to see. In order to identify the user on the remote instance, you can use either username or email address or DANGER!!!! nothing at all. The last options allows the user to backup all courses of the remote instance. ONLY USE IF YOU KNOW WHAT YOU ARE DOING.';