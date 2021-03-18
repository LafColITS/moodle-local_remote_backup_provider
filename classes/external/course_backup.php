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

use backup;
use backup_controller;
use context_course;
use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use file_exception;
use invalid_parameter_exception;
use local_remote_backup_provider\remote_backup_provider;
use moodle_url;
use stored_file_creation_exception;

defined('MOODLE_INTERNAL') || die();

class course_backup extends external_api {

    /**
     * @return external_function_parameters
     * @throws dml_exception
     */
    public static function get_course_backup_by_id_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'id'),
            'uniqueid' => new external_value(remote_backup_provider::get_param_type(), 'uniqueid'),
        ]);
    }

    /**
     * Create and retrieve a course backup by course id.
     *
     * The user is looked up by a unique user attribute as it is not a given that user ids match
     * across platforms.
     *
     * @param $id
     * @param $uniqueid
     * @return array|false
     * @throws dml_exception
     * @throws file_exception
     * @throws invalid_parameter_exception
     * @throws stored_file_creation_exception
     */
    public static function get_course_backup_by_id($id, $uniqueid) {
        global $DB;

        // Validate parameters passed from web service.
        $params = self::validate_parameters(
            self::get_course_backup_by_id_parameters(), ['id' => $id, 'uniqueid' => $uniqueid]
        );

        // Get the userid based on unique user attribute.
        $uniqueattribute = remote_backup_provider::get_uniqueid($uniqueid);
        $userid = $DB->get_field('user', 'id', [$uniqueattribute->type => $uniqueattribute->value]);

        // Instantiate controller.
        $bc = new backup_controller(backup::TYPE_1COURSE, $id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid);

        // Run the backup.
        $bc->set_status(backup::STATUS_AWAITING);
        $bc->execute_plan();
        $result = $bc->get_results();

        if (isset($result['backup_destination']) && $result['backup_destination']) {
            $file = $result['backup_destination'];
            $context = context_course::instance($params['id']);
            $fs = get_file_storage();
            $timestamp = time();

            $filerecord = [
                'contextid' => $context->id,
                'component' => 'local_remote_backup_provider',
                'filearea' => 'backup',
                'itemid' => $timestamp,
                'filepath' => '/',
                'filename' => 'coursebackup.mbz',
                'timecreated' => $timestamp,
                'timemodified' => $timestamp
            ];
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
            return ['url' => $fileurl->out(true)];
        }
        return false;
    }

    /**
     * Return Description for get_course_backup_by_id function
     *
     * @return external_single_structure
     */
    public static function get_course_backup_by_id_returns() {
        return new external_single_structure([
            'url' => new external_value(PARAM_URL, 'url of the backup file'),
        ]);
    }
}
