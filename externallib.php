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
 * Web service library functions
 *
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_remote_backup_provider\extended_restore_controller;
use local_remote_backup_provider\remote_backup_provider;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
// Apparently use restore_controller does not work, we have to use require_once.
require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");

/**
 * Web service API definition.
 *
 * @package local_remote_backup_provider
 * @copyright 2015 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_remote_backup_provider_external extends external_api {
    /**
     * Find courses by text search.
     *
     * This function searches the course short name, full name, and idnumber.
     * Only courses are returned where the user has the capability to backup courses.
     *
     * @param string $search The text to search on
     * @return array All courses found
     */
    public static function find_courses($search) {
        global $DB;
        $courses = [];

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::find_courses_parameters(), array('search' => $search));

        // Build query.
        $searchparams = array();
        $searchlikes = array();
        $searchfields = array('c.shortname', 'c.fullname', 'c.idnumber');
        for ($i = 0; $i < count($searchfields); $i++) {
            $searchlikes[$i] = $DB->sql_like($searchfields[$i], ":s{$i}", false, false);
            $searchparams["s{$i}"] = '%' . $params['search'] . '%';
        }

        // Exclude the front page.
        $searchsql = '(' . implode(' OR ', $searchlikes) . ') AND c.id != 1';
        $fields = 'c.id,c.idnumber,c.shortname,c.fullname,c.visible';
        $sql = "SELECT $fields FROM {course} c WHERE $searchsql ORDER BY c.shortname ASC";
        $courserecords = $DB->get_recordset_sql($sql, $searchparams, 0, 500);

        // Get the uniqueid of the user defined to backup courses
        // this can be either a specific export user or -
        // if no specific user was defined - the matching remote user
        $uniqueattribute = remote_backup_provider::get_uniqueid();
        $userid = $DB->get_field('user', 'id', [$uniqueattribute->type => $uniqueattribute->value]);

        // Only return courses user is allowed to backup.
        foreach ($courserecords as $course) {
            context_helper::preload_from_record($course);
            $coursecontext = context_course::instance($course->id);
            if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $userid)) {
                continue;
            }
            if (!has_capability('moodle/backup:backupcourse', $coursecontext, $userid)) {
                continue;
            }
            $courses[$course->id] = $course;
        }
        return $courses;
    }

    /**
     * Parameter description for find_courses().
     *
     * @return external_function_parameters
     */
    public static function find_courses_parameters() {
        return new external_function_parameters(
                array(
                        'search' => new external_value(PARAM_NOTAGS, 'search'),
                )
        );
    }

    /**
     * Parameter description for find_courses().
     *
     * @return external_description
     */
    public static function find_courses_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                                'id' => new external_value(PARAM_INT, 'id of course'),
                                'idnumber' => new external_value(PARAM_RAW, 'idnumber of course'),
                                'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                                'fullname' => new external_value(PARAM_RAW, 'long name of course'),
                        )
                )
        );
    }

    /**
     * Create and retrieve a course backup by course id.
     *
     * The user is looked up by a unique user attribute as it is not a given that user ids match
     * across platforms.
     *
     * @param int $id the course id
     * @param string $uniqueid The unique user attribute like username, email or idnumber
     * @return array|bool An array containing the url or false on failure
     */
    public static function get_course_backup_by_id($id, $uniqueid) {
        global $DB;

        // Check if setting to import user data is enabled / disabled
        $is_userdata_enabled = (bool)get_config('local_remote_backup_provider', 'enableuserdata');

        // Validate parameters passed from web service.
        $params = self::validate_parameters(
                self::get_course_backup_by_id_parameters(), array('id' => $id, 'uniqueid' => $uniqueid)
        );

        // Get the userid based on unique user attribute.
        $uniqueattribute = remote_backup_provider::get_uniqueid();
        $userid = $DB->get_field('user', 'id', [$uniqueattribute->type => $uniqueattribute->value]);

        // Instantiate controller.
        $bc = new backup_controller(backup::TYPE_1COURSE, $id, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid);

        // If setting userdataenabled is not checked
        // ...then create a backup without user data
        if ($is_userdata_enabled == false){
            // The array of initial backup settings.
            $backupsettings = array (
                'users' => 0,               // enrolled users => this is where we turn them off!
                'anonymize' => 0,           // user information
                'role_assignments' => 0,    // user role assignments
                'activities' => 1,          // activities
                'blocks' => 1,              // blocks
                'filters' => 1,             // filters
                'comments' => 0,            // comments
                'userscompletion' => 0,     // user completion details
                'logs' => 0,                // course logs
                'grade_histories' => 0,     // grade history
                'groups' => 0               // groups and groupings
            );

            foreach ($bc->get_plan()->get_tasks() as $taskindex => $task) {
                $settings = $task->get_settings();
                foreach ($settings as $settingindex => $setting) {
                    $setting->set_status(backup_setting::NOT_LOCKED);

                    // Modify the values of the intial backup settings
                    if ($taskindex == 0) {
                        foreach ($backupsettings as $key => $value) {
                            if ($setting->get_name() == $key) {
                                $setting->set_value($value); // do not include enrolled users!
                            }
                        }
                    }
                }
            }
        }

        // Run the backup.
        $bc->set_status(backup::STATUS_AWAITING);
        $bc->execute_plan();
        $result = $bc->get_results();

        if (isset($result['backup_destination']) && $result['backup_destination']) {
            $file = $result['backup_destination'];
            $context = context_course::instance($params['id']);
            $fs = get_file_storage();
            $timestamp = time();

            $filerecord = array(
                    'contextid' => $context->id,
                    'component' => 'local_remote_backup_provider',
                    'filearea' => 'backup',
                    'itemid' => $timestamp,
                    'filepath' => '/',
                    'filename' => 'coursebackup.mbz',
                    'timecreated' => $timestamp,
                    'timemodified' => $timestamp
            );
            $storedfile = $fs->create_file_from_storedfile($filerecord, $file);
            $file->delete();

            // Make the link.
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

    /**
     * Parameter description for get_course_backup_by_id().
     *
     * @return external_function_parameters
     */
    public static function get_course_backup_by_id_parameters() {
        return new external_function_parameters(
                array(
                        'id' => new external_value(PARAM_INT, 'id'),
                        'uniqueid' => new external_value(remote_backup_provider::get_param_type(),
                                'uniqueid')
                )
        );
    }

    /**
     * Parameter description for get_course_backup_by_id().
     *
     * @return external_description
     */
    public static function get_course_backup_by_id_returns() {
        return new external_single_structure(
                array(
                        'url' => new external_value(PARAM_URL, 'url of the backup file'),
                )
        );
    }

    /**
     * Delete record from our users.xml in our backup.
     *
     * @param int $id
     * @param string $restoreid
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function delete_user_entry_from_backup(int $id, string $restoreid): array {
        // Validate parameters passed from web service.
        $params = self::validate_parameters(
                self::delete_user_entry_from_backup_parameters(), array('id' => $id, 'restoreid' => $restoreid)
        );

        // We need the restore controller, to get the path of our backup.
        $rc = restore_controller::load_controller($params['restoreid']);
        $basepath = $rc->get_plan()->get_basepath();
        $pathtofile = $basepath . '/users.xml';

        // Delete the record from our users.xml.
        $success = extended_restore_controller::delete_user_from_xml([$params['id']], $pathtofile);
        $result = array();
        $result['status'] = $success ? 1 : 0;

        return $result;
    }

    /**
     * Parameter description for delete_user_entry_from_backup().
     *
     * @return external_function_parameters
     */
    public static function delete_user_entry_from_backup_parameters(): \external_function_parameters {
        return new external_function_parameters(
                array(
                        'id' => new external_value(PARAM_INT, 'id'),
                        'restoreid' => new external_value(PARAM_ALPHANUMEXT, 'restoreid')
                )
        );
    }

    /**
     * Parameter description for delete_user_entry_from_backup().
     *
     * @return external_description
     */
    public static function delete_user_entry_from_backup_returns() {
        return new external_single_structure(
                array(
                        'status' => new external_value(PARAM_INT, '0 is false, 1 is true'),
                )
        );
    }

    /**
     * Update our users.xml in our backup
     *
     * @param $id course id
     * @param $restoreid restore controller id
     * @param $username
     * @param $firstname
     * @param $lastname
     * @param $useremail
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function update_user_entry_in_backup(int $id, string $restoreid, string $username, string $firstname,
        string $lastname, string $useremail) {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(
                self::update_user_entry_in_backup_parameters(), array('id' => $id,
                'restoreid' => $restoreid,
                'username' => $username,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'useremail' => $useremail)
        );

        // We need the restore controller, to get the path of our backup.
        $rc = restore_controller::load_controller($params['restoreid']);
        $basepath = $rc->get_plan()->get_basepath();
        $pathtofile = $basepath . '/users.xml';

        // Delete the record from our users.xml.
        $success = extended_restore_controller::update_user_from_xml($params['id'], $pathtofile, $params['username'],
            $params['firstname'], $params['lastname'], $params['useremail']);
        $result = array();
        $result['status'] = $success ? 1 : 0;
        return $result;
    }

    /**
     * Parameter description for update_user_entry_in_backup().
     *
     * @return external_function_parameters
     */
    public static function update_user_entry_in_backup_parameters() {
        return new external_function_parameters(
                array(
                        'id' => new external_value(PARAM_INT, 'id'),
                        'restoreid' => new external_value(PARAM_ALPHANUMEXT, 'restoreid'),
                        'username' => new external_value(PARAM_RAW, 'username'),
                        'firstname' => new external_value(PARAM_RAW, 'firstname'),
                        'lastname' => new external_value(PARAM_RAW, 'lastname'),
                        'useremail' => new external_value(PARAM_EMAIL, 'useremail'),
                )
        );
    }

    /**
     * Parameter description for delete_user_entry_from_backup().
     *
     * @return external_description
     */
    public static function update_user_entry_in_backup_returns() {
        return new external_single_structure(
                array(
                        'status' => new external_value(PARAM_INT, '0 is false, 1 is true'),
                )
        );
    }


    /**
     * Delete record from our users.xml in our backup
     *
     *
     * @param int $restoreid
     * @return array|bool An array containing the status
     */
    public static function create_updated_backup($restoreid) {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(
                self::create_updated_backup_parameters(), array('restoreid' => $restoreid)
        );

        // We need the restore controller, to get the path of our backup.
        $rc = restore_controller::load_controller($params['restoreid']);

        $basepath = $rc->get_plan()->get_basepath();

        // Get the list of files in directory.
        $filestemp = get_directory_list($basepath, '', false, true, true);
        $files = array();
        foreach ($filestemp as $file) { // Add zip paths and fs paths to all them.
            $files[$file] = $basepath . '/' . $file;
        }

        $filepath = make_backup_temp_directory('');
        if (!check_dir_exists($filepath, true, true)) {
            throw new restore_controller_exception('cannot_create_backup_temp_dir');
        }
        // Calculate the zip fullpath (in OS temp area it's always backup.mbz).
        $zipfile = $filepath . '/updated_backup.mbz';

        // Get the zip packer.
        $zippacker = get_file_packer('application/vnd.moodle.backup');

        // Zip files.
        $success = $zippacker->archive_to_pathname($files, $zipfile, true);

        $result = array();
        $result['status'] = $success ? 1 : 0;

        return $result;
    }

    /**
     * Parameter description for create_updated_backup().
     *
     * @return external_function_parameters
     */
    public static function create_updated_backup_parameters() {
        return new external_function_parameters(
                array(
                        'restoreid' => new external_value(PARAM_RAW, 'restoreid')
                )
        );
    }

    /**
     * Parameter description for create_updated_backup().
     *
     * @return external_description
     */
    public static function create_updated_backup_returns() {
        return new external_single_structure(
                array(
                        'status' => new external_value(PARAM_INT, '0 is false, 1 is true'),
                )
        );
    }
}
