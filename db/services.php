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
 * Web service definitions for local_remote_backup_provider
 *
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
        'local_remote_backup_provider_find_courses' => array(
                'classname' => 'local_remote_backup_provider_external',
                'methodname' => 'find_courses',
                'classpath' => 'local/remote_backup_provider/externallib.php',
                'description' => 'Find courses matching a given string.',
                'type' => 'read',
                'capabilities' => 'moodle/course:viewhiddencourses',
        ),
        'local_remote_backup_provider_get_course_backup_by_id' => array(
                'classname' => 'local_remote_backup_provider_external',
                'methodname' => 'get_course_backup_by_id',
                'classpath' => 'local/remote_backup_provider/externallib.php',
                'description' => 'Generate a course backup file and return a link.',
                'type' => 'read',
                'capabilities' => 'moodle/backup:backupcourse',
        ),
        'local_remote_backup_provider_delete_user_entry_from_backup' => array(
                'classname' => 'local_remote_backup_provider_external',
                'methodname' => 'delete_user_entry_from_backup',
                'classpath' => 'local/remote_backup_provider/externallib.php',
                'description' => 'Delete a user by id from the users.xml in the backup we will import',
                'type' => 'write',
                'ajax' => true,
                'loginrequired' => true,
        ),
        'local_remote_backup_provider_update_user_entry_in_backup' => array(
                'classname' => 'local_remote_backup_provider_external',
                'methodname' => 'update_user_entry_in_backup',
                'classpath' => 'local/remote_backup_provider/externallib.php',
                'description' => 'Update a user by id in the users.xml in the backup we will import',
                'type' => 'write',
                'ajax' => true,
                'loginrequired' => true,
        ),
        'local_remote_backup_provider_create_updated_backup' => array(
                'classname' => 'local_remote_backup_provider_external',
                'methodname' => 'create_updated_backup',
                'classpath' => 'local/remote_backup_provider/externallib.php',
                'description' => 'Create a new backup file with the updated files',
                'type' => 'write',
                'ajax' => true,
                'loginrequired' => true,
        )
);
