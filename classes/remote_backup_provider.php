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
namespace local_remote_backup_provider;

use context_course;
use curl;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Class backup_provider.
 * Provide basic functions for the remote backup provider. As of now only static functions.
 *
 * @package local_remote_backup_provider
 */
class remote_backup_provider {

    /**
     * @var int course id of the local course from where the remote backup provider was called.
     */
    public $id;

    /**
     * Token for webservice client.
     * @var string
     */
    public $token = '';

    /**
     * The url to the remote site.
     * @var string
     */
    public $remotesite = '';

    /**
     * The user attribute to match the local user with the remote user.
     * @var string
     */
    public $uniqueid = '';

    /**
     * The username of a specific user defined on the remote site
     * which will be used for course exports:
     * If a valid username is entered, the search will be done with the user chosen in the setting.
     * Also, the user has to have the rights to create a backup for the courses.
     * So no matter which user on the local instance wants to import a course, it will be determined by the user
     * specified on the remote site, which course can be backed up. (This depends on the permissions of the user).
     *
     * @var string
     */
    public $specific_export_username = '';

    /**
     * @var context_course
     */
    public $context;

    /**
     * Enable extended user check upon course restore process.
     * @var bool
     */
    public $enableuserprecheck = false;

    /**
     * Enable export of user data upon course restore process.
     * Enabled by default
     * @var bool
     */
    public $enableuserdata = true;

    /**
     * @var \stdClass
     */
    public $course;

    /**
     * Return param type expected for web service.
     *
     * @param int $id course id from where the remote backup provider is called
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(int $id) {
        global $DB;
        $this->id = $id;
        $this->token = get_config('local_remote_backup_provider', 'wstoken');
        $this->remotesite = get_config('local_remote_backup_provider', 'remotesite');
        $this->uniqueid = get_config('local_remote_backup_provider', 'uniqueid');
        $this->specific_export_username = get_config('local_remote_backup_provider', 'specific_export_username');
        $this->enableuserprecheck = (bool)get_config('local_remote_backup_provider', 'enableuserprecheck');
        $this->enableuserdata = (bool)get_config('local_remote_backup_provider', 'enableuserdata');
        $this->course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
        $this->context = context_course::instance($id);
        $returnurl = new moodle_url('/course/view.php', array('id' => $id));
        if (empty($this->token) || empty($this->remotesite)) {
            print_error('pluginnotconfigured', 'local_remote_backup_provider', $returnurl);
        }
    }

    /**
     * Get the url for the course search service on the remote instance.
     *
     * @return string[]|object[]
     */
    public function get_remote_data(string $service, array $params) {
        $url = $this->remotesite . '/webservice/rest/server.php?wstoken=' . $this->token .
            '&wsfunction=' . $service . '&moodlewsrestformat=json';
        $curl = new curl;
        return json_decode($curl->post($url, $params));
    }

    /**
     * Return param type expected for web service.
     *
     * @return string
     * @throws \dml_exception
     */
    public static function get_param_type() {

        // at first, have a look if a specific user for course exports has been defined
        if (self::get_specific_export_user() !== false){
            if (!empty(self::get_specific_export_user()->username)){
                return PARAM_USERNAME;
            }
        }

        // if no specific export user, could be found, we continue
        $uniquetype = get_config('local_remote_backup_provider', 'uniqueid');
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
     * If a specific user for course exports has been defined on the remote site,
     * this username will be used as uniqueid.
     *
     * @return \stdClass
     * @throws \dml_exception
     */
    public static function get_uniqueid() {
        global $USER;

        $uniqueid = new \stdClass();

        // at first, have a look if a specific user for course exports has been defined
        $specific_export_user = self::get_specific_export_user();
        if ($specific_export_user !== false){
            if (!empty($specific_export_user->username)){
                $uniqueid->value = $specific_export_user->username;
                $uniqueid->type = 'username';
                return $uniqueid;
            }
        }

        // if no specific export user could be found, we continue...
        $uniqueid->type = get_config('local_remote_backup_provider', 'uniqueid');
        switch ($uniqueid->type) {
            case 'username':
                $uniqueid->value = $USER->username;
                break;
            case 'email':
                $uniqueid->value = $USER->email;
                break;
            case 'idnumber':
                $uniqueid->value = $USER->idnumber;
                break;
            default:
                $uniqueid->value = $USER->username;
                $uniqueid->type = 'username';
        }
        return $uniqueid;
    }

    /**
     * Gets a user object from the DB for a specific export user
     * which has been defined in the plugin config settings (settings.php)
     *
     * @return stdClass an object containing value und type of the uniqueid, returns false if no user can be found
     */
    public static function get_specific_export_user() {
        // at first, we have a look if a specific user for course exports has been defined
        $specific_export_username = get_config('local_remote_backup_provider', 'specific_export_username');
        if (!empty($specific_export_username)){
            global $DB;
            // now we look up the user in the DB:
            $specific_export_user = $DB->get_record('user', array('username' => $specific_export_username ));
            if (!empty($specific_export_user)){
                return $specific_export_user;
            }
        }
        // if no specific export user can be found, we return false
        return false;
    }

    /**
     * Helper function to find out if the specific export user
     * defined in the plugin config settings (settings.php)
     * is valid
     *
     * @return bool returns true only if the defined specific export user is valid
     */
    public static function is_valid_specific_export_user() {
        // at first, we have a look if a specific user for course exports has been defined
        $specific_export_username = get_config('local_remote_backup_provider', 'specific_export_username');
        if (!empty($specific_export_username)){
            global $DB;
            // now we look up the user in the DB:
            $specific_export_user = $DB->get_record('user', array('username' => $specific_export_username ));
            if (!empty($specific_export_user)){
                // user could be found, so we have a valid export user
                return true;
            }
        }
        // if no specific export user can be found, we return false
        return false;
    }
}



