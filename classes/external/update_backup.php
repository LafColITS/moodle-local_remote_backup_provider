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
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use restore_controller;

defined('MOODLE_INTERNAL') || die();

class update_backup extends external_api {

    /**
     * Create new updated backup of the course
     *
     * @param $restoreid
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function create_updated_backup($restoreid) {
        global $CFG;

        // Validate parameters passed from web service.
        $params = self::validate_parameters(
            self::create_updated_backup_parameters(), ['restoreid' => $restoreid]
        );

        // We need the restore controller, to get the path of our backup.
        $rc = restore_controller::load_controller($params['restoreid']);

        $basepath = $rc->get_plan()->get_basepath();

        // Get the list of files in directory.
        $filestemp = get_directory_list($basepath, '', false, true, true);
        $files = [];
        foreach ($filestemp as $file) { // Add zip paths and fs paths to all them.
            $files[$file] = $basepath . '/' . $file;
        }

        // Calculate the zip fullpath (in OS temp area it's always backup.mbz).
        $zipfile = $CFG->backuptempdir . '/updated_backup.mbz';

        // Get the zip packer.
        $zippacker = get_file_packer('application/vnd.moodle.backup');

        // Zip files.
        $success = $zippacker->archive_to_pathname($files, $zipfile, true);

        $result = [];
        $result['status'] = $success ? 1 : 0;

        return $result;
    }

    /**
     * Parameter description for create_updated_backup().
     *
     * @return external_function_parameters
     */
    public static function create_updated_backup_parameters() {
        return new external_function_parameters([
            'restoreid' => new external_value(PARAM_RAW, 'restoreid')
        ]);
    }

    /**
     * Return description for create_updated_backup().
     *
     * @return external_single_structure
     */
    public static function create_updated_backup_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, '0 is false, 1 is true'),
        ]);
    }
}
