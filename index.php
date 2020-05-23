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
 * Landing page for local_remote_backup_provider
 *
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('search_form.php');
require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");
require_once("{$CFG->dirroot}/backup/util/dbops/restore_dbops.class.php");

$id     = required_param('id', PARAM_INT);
$remote = optional_param('remote', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_NOTAGS);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);
$PAGE->set_url('/local/remote_backup_provider/index.php', array('id' => $id));
$PAGE->set_pagelayout('report');
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Check permissions.
$context = context_course::instance($course->id);
require_capability('local/remote_backup_provider:access', $context);

// Get config settings.
$token      = get_config('local_remote_backup_provider', 'wstoken');
$remotesite = get_config('local_remote_backup_provider', 'remotesite');
if (empty($token) || empty($remotesite)) {
    print_error('pluginnotconfigured', 'local_remote_backup_provider', $returnurl);
}

// Get the courses.
if (!empty($search)) {
    $url = $remotesite . '/webservice/rest/server.php?wstoken=' . $token .
        '&wsfunction=local_remote_backup_provider_find_courses&moodlewsrestformat=json';
    $params = array('search' => $search);
    $curl = new curl;
    $results = json_decode($curl->post($url, $params));
    $data = array();
    foreach ($results as $result) {
        $data[] = html_writer::link(
            new moodle_url('/local/remote_backup_provider/index.php',
                array('id' => $id, 'remote' => $result->id)
            ),
            '[' . $result->shortname . '] ' . $result->fullname
        );
    }
} else if ($remote !== 0) {
    // Generate the backup file.
    $fs = get_file_storage();
    $url = $remotesite . '/webservice/rest/server.php?wstoken=' . $token .
        '&wsfunction=local_remote_backup_provider_get_course_backup_by_id&moodlewsrestformat=json';

    $params = array('id' => $remote, 'uniqueid' => $USER->username);
    $curl = new curl;
    $resp = json_decode($curl->post($url, $params));

    // Import the backup file.
    $timestamp = time();
    $filerecord = array(
        'contextid' => $context->id,
        'component' => 'local_remote_backup_provider',
        'filearea'  => 'backup',
        'itemid'    => $timestamp,
        'filepath'  => '/',
        'filename'  => 'foo1',
        'timecreated' => $timestamp,
        'timemodified' => $timestamp
    );

    $tmpid = restore_controller::get_tempdir_name($course->id, $USER->id);
    $filepath = make_backup_temp_directory($tmpid);
        if (!check_dir_exists($filepath, true, true)) {
            throw new restore_controller_exception('cannot_create_backup_temp_dir');
        }

    $storedfile = $fs->create_file_from_url($filerecord, $resp->url . '?token=' . $token, null, true);

        $filepathold = $storedfile->get_filepath();


    $fp = get_file_packer('application/vnd.moodle.backup');
    $fp->extract_to_pathname($storedfile, $filepath);

    // Reminder: To get get_config()


    //access user.xml in backup?

    $rc = new restore_controller($tmpid, $course->id, backup::INTERACTIVE_NO,
             backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);



    $plan = $rc->get_plan();
            
    $restoreinfo = $rc->get_info();


    $rc->execute_precheck();

    $file = $rc->get_plan()->get_basepath() . '/users.xml';

    restore_dbops::load_users_to_tempids($rc->get_restoreid(), $file);

    $test = $DB->get_records('backup_ids_temp', ['backupid' => $rc->get_restoreid(), 'itemname' => 'user']);

    try {
        $result = restore_dbops::precheck_included_users($rc->get_restoreid(), $course->id, $USER->id, false, $rc->get_progress());
    }
    catch (Exception $e) {
        printf($e);
    }

    $restoreurl = new moodle_url(
        '/backup/restore.php',
        array(
            'contextid'    => $context->id,
            'pathnamehash' => $storedfile->get_pathnamehash(),
            'contenthash'  => $storedfile->get_contenthash()
        )
    );


    redirect($restoreurl);
}

$PAGE->set_title($course->shortname . ': ' . get_string('import', 'local_remote_backup_provider'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Display the courses.
if (!empty($data)) {
    echo html_writer::tag('h2', 'Available source courses');
    echo html_writer::tag('i', 'Source: ' . $remotesite);
    echo html_writer::alist($data);
}

// Show the search form.
$mform = new local_remote_backup_provider_search_form();
if (!$mform->is_cancelled()) {
    $toform = new stdClass();
    $toform->id = $id;
    $mform->set_data($toform);
    $mform->display();
}
echo $OUTPUT->footer();
