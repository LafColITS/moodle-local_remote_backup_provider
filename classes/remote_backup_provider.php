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
namespace local_backup_provider;

/**
 * Class backup_provider.
 * Provide basic functions for the remote backup provider. As of now only static functions.
 *
 * @package local_backup_provider
 */
class remote_backup_provider {

    /**
     * Return param type expected for web service.
     *
     * @return string
     * @throws \dml_exception
     */
    public static function get_param_type() {
        $uniquetype = get_config('local_backup_provider', 'uniqueid');
        switch ($uniquetype) {
            case 'username':
                $type = PARAM_USERNAME;
                break;
            case 'email':
                $type = PARAM_EMAIL;
                break;
            default:
                $type = PARAM_ALPHANUM;
        }
        return $type;
    }

    /**
     * Get uniqueidtype and value. Returns userfield as key and unique attribute of user as value.
     * The return array must have as key a fieldname of the user table, if unique attribute is used.
     *
     * @return string[]
     * @throws \dml_exception
     */
    public static function get_uniqueid() {
        global $USER;
        $uniqueidtype = get_config('local_remote_backup_provider', 'uniqueid');
        switch ($uniqueidtype) {
            case 'username':
                $uniqueid = $USER->username;
                break;
            case 'email':
                $uniqueid = $USER->email;
                break;
            case 'idnumber':
                $uniqueid = $USER->idnumber;
                break;
            default:
                $uniqueid = 'username';
        }
        return [$uniqueidtype => $uniqueid];
    }
}



