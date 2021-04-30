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
 * Settings for local_remote_backup_provider
 *
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_remote_backup_provider\remote_backup_provider;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_remote_backup_provider',
        get_string('pluginname', 'local_remote_backup_provider'));
    $ADMIN->add('localplugins', $settings);

    $adminsetting = new admin_setting_configtext('remotesite',
        get_string('remotesite', 'local_remote_backup_provider'),
        get_string('remotesite_desc', 'local_remote_backup_provider'), '');
    $adminsetting->plugin = 'local_remote_backup_provider';
    $settings->add($adminsetting);

    $adminsetting = new admin_setting_configtext('wstoken',
        get_string('wstoken', 'local_remote_backup_provider'),
        get_string('wstoken_desc', 'local_remote_backup_provider'), '');
    $adminsetting->plugin = 'local_remote_backup_provider';
    $settings->add($adminsetting);

    $options = ['username' => get_string('username'), 'email' => get_string('email'),
        'idnumber' => get_string('idnumber')];
    // Check if email is unique in the site administration settings. Otherwise it is not possible to use email as unique attribute.
    if ($CFG->allowaccountssameemail) {
        unset($options['email']);
    }
    $adminsetting = new admin_setting_configselect('uniqueid',
        get_string('uniqueid', 'local_remote_backup_provider'),
        get_string('uniqueid_desc', 'local_remote_backup_provider'), 'username', $options);
    $adminsetting->plugin = 'local_remote_backup_provider';
    $settings->add($adminsetting);

    // when loading, check if we have a valid username and prepare the description string
    $specific_export_username = get_config('local_remote_backup_provider', 'specific_export_username');
    if (empty($specific_export_username)) {
        $specific_export_username_desc = get_string('specific_export_username_desc', 'local_remote_backup_provider');
    }
    else if (remote_backup_provider::is_valid_specific_export_user()){
        $specific_export_username_desc = '<p style="color:green;"><b>'.$specific_export_username.'</b>' .
        get_string('specific_export_username_valid_desc', 'local_remote_backup_provider').'</p><p>'.
        get_string('specific_export_username_desc', 'local_remote_backup_provider').'</p>';
    } else {
        $specific_export_username_desc = '<p style="color:red;"><b>'.$specific_export_username.'</b>' .
            get_string('specific_export_username_invalid_desc', 'local_remote_backup_provider').'</p><p>'.
            get_string('specific_export_username_desc', 'local_remote_backup_provider').'</p>';
    }

    $adminsetting = new admin_setting_configtext('specific_export_username',
        get_string('specific_export_username', 'local_remote_backup_provider'),
        $specific_export_username_desc, '');
    $adminsetting->plugin = 'local_remote_backup_provider';
    $settings->add($adminsetting);

    $adminsetting = new admin_setting_configcheckbox('enableuserprecheck',
        get_string('enableuserprecheck', 'local_remote_backup_provider'),
        get_string('enabluserprecheck_desc', 'local_remote_backup_provider'), '0');
    $adminsetting->plugin = 'local_remote_backup_provider';
    $settings->add($adminsetting);

    $adminsetting = new admin_setting_configcheckbox('enableuserdata',
        get_string('enableuserdata', 'local_remote_backup_provider'),
        get_string('enableuserdata_desc', 'local_remote_backup_provider'), '1');
    $adminsetting->plugin = 'local_remote_backup_provider';
    $settings->add($adminsetting);
}
