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

$functions = [
        'local_remote_backup_provider_find_courses' => [
                'classname' => \local_remote_backup_provider\external\find_courses::class,
                'methodname' => 'find_courses',
                'classpath' => 'local/remote_backup_provider/classes/external/find_courses.php',
                'description' => 'Find courses matching a given string and uniqueid.',
                'type' => 'read',
                'services' => [
                    'remote_backup_provider',
                ],
                'capabilities' => 'moodle/course:viewhiddencourses',
        ],
        'local_remote_backup_provider_get_course_backup_by_id' => [
                'classname' => \local_remote_backup_provider\external\course_backup::class,
                'methodname' => 'get_course_backup_by_id',
                'classpath' => 'local/remote_backup_provider/classes/external/course_backup.php',
                'description' => 'Generate a course backup file and return a link.',
                'type' => 'read',
                'services' => [
                    'remote_backup_provider',
                ],
                'capabilities' => 'moodle/backup:backupcourse',
        ],
        'local_remote_backup_provider_delete_user_entry_from_backup' => [
                'classname' => \local_remote_backup_provider\external\delete_users::class,
                'methodname' => 'delete_user_entry_from_backup',
                'classpath' => 'local/remote_backup_provider/classes/external/delete_users.php',
                'description' => 'Delete a user by id from the users.xml in the backup we will import',
                'type' => 'write',
                'ajax' => true,
                'services' => [
                    'remote_backup_provider',
                ],
                'loginrequired' => true,
        ],
        'local_remote_backup_provider_update_user_entry_in_backup' => [
                'classname' => \local_remote_backup_provider\external\update_user::class,
                'methodname' => 'update_user_entry_in_backup',
                'classpath' => 'local/remote_backup_provider/classes/external/update_user.php',
                'description' => 'Update a user by id in the users.xml in the backup we will import',
                'type' => 'write',
                'ajax' => true,
                'services' => [
                    'remote_backup_provider',
                ],
                'loginrequired' => true,
        ],
        'local_remote_backup_provider_create_updated_backup' => [
                'classname' => \local_remote_backup_provider\external\update_backup::class,
                'methodname' => 'create_updated_backup',
                'classpath' => 'local/remote_backup_provider/classes/external/update_backup.php',
                'description' => 'Create a new backup file with the updated files',
                'type' => 'write',
                'ajax' => true,
                'services' => [
                    'remote_backup_provider',
                ],
                'loginrequired' => true,
        ]
];

$services = [
    'local_remote_backup_provider' => [
        'functions' => [
            'local_remote_backup_provider_find_courses',
            'local_remote_backup_provider_get_course_backup_by_id',
            'local_remote_backup_provider_delete_user_entry_from_backup',
            'local_remote_backup_provider_update_user_entry_in_backup',
            'local_remote_backup_provider_create_updated_backup',
        ],
        'requiredcapability' => 'local/remote_backup_provider:access',
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'remote_backup_provider',
        'downloadfiles' => 1,
        'uploadfiles' => 1,
    ]
];
