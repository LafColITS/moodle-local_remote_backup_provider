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

use external_function_parameters;
use external_single_structure;
use external_value;
use local_remote_backup_provider\extended_restore_controller;
use restore_controller;

defined('MOODLE_INTERNAL') || die();

class update_user extends \external_api {

    /**
     * Update our users.xml in our backup
     *
     * @param int $id
     * @param string $restoreid
     * @param string $username
     * @param string $firstname
     * @param string $lastname
     * @param string $useremail
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function update_user_entry_in_backup(int $id, string $restoreid, string $username, string $firstname,
        string $lastname, string $useremail) {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::update_user_entry_in_backup_parameters(), [
            'id' => $id,
            'restoreid' => $restoreid,
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'useremail' => $useremail
        ]);

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
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'id'),
            'restoreid' => new external_value(PARAM_ALPHANUMEXT, 'restoreid'),
            'username' => new external_value(PARAM_RAW, 'username'),
            'firstname' => new external_value(PARAM_RAW, 'firstname'),
            'lastname' => new external_value(PARAM_RAW, 'lastname'),
            'useremail' => new external_value(PARAM_EMAIL, 'useremail'),
        ]);
    }

    /**
     * Parameter description for delete_user_entry_from_backup().
     *
     * @return external_single_structure
     */
    public static function update_user_entry_in_backup_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, '0 is false, 1 is true'),
        ]);
    }
}
