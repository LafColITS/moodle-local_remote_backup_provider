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

require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

class local_remote_backup_provider_external extends external_api {
    public static function find_courses_parameters() {
        return new external_function_parameters(
            array(
                'search' => new external_value(PARAM_CLEAN, 'search'),
            )
        );
    }

    public static function find_courses($search) {
        global $DB;

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::find_courses_parameters(), array('search' => $search));

        // Capability check.
        if (!has_capability('moodle/course:viewhiddencourses', context_system::instance())) {
            return false;
        }

        // Build query.
        $searchsql    = '';
        $searchparams = array();
        $searchlikes = array();
        $searchfields = array('c.shortname', 'c.fullname', 'c.idnumber');
        for ($i = 0; $i < count($searchfields); $i++) {
            $searchlikes[$i] = $DB->sql_like($searchfields[$i], ":s{$i}", false, false);
            $searchparams["s{$i}"] = '%' . $search . '%';
        }
        // We exclude the front page.
        $searchsql = '(' . implode(' OR ', $searchlikes) . ') AND c.id != 1';

        // Run query.
        $fields = 'c.id,c.idnumber,c.shortname,c.fullname';
        $sql = "SELECT $fields FROM {course} c WHERE $searchsql ORDER BY c.shortname ASC";
        $courses = $DB->get_records_sql($sql, $searchparams, 0);
        return $courses;
    }

    public static function find_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'        => new external_value(PARAM_INT, 'id of course'),
                    'idnumber'  => new external_value(PARAM_RAW, 'idnumber of course'),
                    'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                    'fullname'  => new external_value(PARAM_RAW, 'long name of course'),
                )
            )
        );
    }

    public static function get_course_backup_by_id_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id'),
                'username' => new external_value(PARAM_USERNAME, 'username'),
            )
        );
    }

    public static function get_course_backup_by_id($id, $username) {
        global $CFG, $DB;

        // Validate parameters passed from web service.
        $params = self::validate_parameters(
            self::get_course_backup_by_id_parameters(), array('id' => $id, 'username' => $username)
        );

        // Extract the userid from the username.
        $userid = $DB->get_field('user', 'id', array('username' => $username));

        // Instantiate controller.
        $bc = new backup_controller(
            \backup::TYPE_1COURSE, $id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid);

        // Run the backup.
        $bc->set_status(backup::STATUS_AWAITING);
        $bc->execute_plan();
        $result = $bc->get_results();

        if (isset($result['backup_destination']) && $result['backup_destination']) {
            $file = $result['backup_destination'];
            $context = context_course::instance($id);
            $fs = get_file_storage();
            $timestamp = time();

            $filerecord = array(
                'contextid' => $context->id,
                'component' => 'local_remote_backup_provider',
                'filearea' => 'backup',
                'itemid' => $timestamp,
                'filepath' => '/',
                'filename' => 'foo',
                'timecreated' => $timestamp,
                'timemodified' => $timestamp
            );
            $storedfile = $fs->create_file_from_storedfile($filerecord, $file);
            $file->delete();

            // Make the link.
            $filepath = $storedfile->get_filepath() . $storedfile->get_filename();
            $fileurl = moodle_url::make_webservice_pluginfile_url(
                $storedfile->get_contextid(),
                $storedfile->get_component(),
                $storedfile->get_filearea(),
                $storedfile->get_itemid(),
                $storedfile->get_filepath(),
                $storedfile->get_filename()
            );
            return array('url' => $fileurl->out(true));
        } else {
            return false;
        }
    }

    public static function get_course_backup_by_id_returns() {
        return new external_single_structure(
            array(
                'url' => new external_value(PARAM_RAW, 'url of the backup file'),
            )
        );
    }
}
