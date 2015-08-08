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
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
);
