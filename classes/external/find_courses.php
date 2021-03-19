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
 * local_remote_backup_provider\external comment created event.
 *
 * @package    local_remote_backup_provider\external
 * @copyright  2021 SysBind Ltd. <service@sysbind.co.il>
 * @auther     avi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_remote_backup_provider\external;

use coding_exception;
use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use local_remote_backup_provider\remote_backup_provider;

class find_courses extends external_api {

    /**
     * @return external_function_parameters
     * @throws dml_exception
     */
    public static function find_courses_parameters() {
        return new external_function_parameters([
            'search' => new external_value(PARAM_NOTAGS, 'search'),
            'uniqueid' => new external_value(remote_backup_provider::get_param_type(), 'uniqueid'),
        ]);
    }

    /**
     * @param $search
     * @param $uniquied
     * @return array|false
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function find_courses($search, $uniqueid) {
        global $DB;
        $courses = [];

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::find_courses_parameters(), ['search' => $search, 'uniqueid' => $uniqueid]);

        // Get the userid based on unique user attribute.
        $uniqueattribute = remote_backup_provider::get_uniqueid($uniqueid);
        $userid = $DB->get_field('user', 'id', [$uniqueattribute->type => $params['uniqueid']]);
        if ($uniqueid == null || empty($uniqueid) || $userid == null || $userid == false) {
            return false;
        }
        $isadmin = is_siteadmin($userid);

        // Build query.
        $searchparams = [];
        $searchlikes = [];
        $searchfields = ['c.shortname', 'c.fullname', 'c.idnumber'];
        $countfields = count($searchfields);
        for ($i = 0; $i < $countfields; $i++) {
            $searchlikes[$i] = $DB->sql_like($searchfields[$i], ":s{$i}", false, false);
            $searchparams["s{$i}"] = '%' . $params['search'] . '%';
        }

        // Exclude the front page.
        $searchsql = '(' . implode(' OR ', $searchlikes) . ') AND c.id != 1';
        $fields = 'c.id,c.idnumber,c.shortname,c.fullname,c.visible';
        $sql = "SELECT $fields FROM {course} c WHERE $searchsql ORDER BY c.shortname ASC";
        $courserecords = $DB->get_recordset_sql($sql, $searchparams, 0, 500);
        // Only return courses user is allowed to backup.
        foreach ($courserecords as $course) {
            if (!$isadmin) {
                context_helper::preload_from_record($course);
                $coursecontext = context_course::instance($course->id);
                if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $userid)) {
                    continue;
                }
                if (!has_capability('moodle/backup:backupcourse', $coursecontext, $userid)) {
                    continue;
                }
                if (!remote_backup_provider::has_course_permission($coursecontext, $userid)) {
                    continue;
                }
            }
            $courses[$course->id] = $course;
        }
        return $courses;
    }

    /**
     * Return description for fincd courses
     *
     * @return external_multiple_structure
     */
    public static function find_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'id of course'),
                    'idnumber' => new external_value(PARAM_RAW, 'idnumber of course'),
                    'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                    'fullname' => new external_value(PARAM_RAW, 'long name of course'),
                ]
            )
        );
    }
}
