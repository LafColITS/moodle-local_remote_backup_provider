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

namespace local_remote_backup_provider;

use html_writer;
use local_remote_backup_provider\forms\search_form;
use moodle_url;
use stdClass;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
// Remote course id.
$remote = optional_param('remote', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_NOTAGS);

$listofusers = null;

// Create new instance of local_remote_backup_provider.
$rbp = new remote_backup_provider($id);

require_login($rbp->course);
$PAGE->set_url('/local/remote_backup_provider/index.php', array('id' => $id));
$PAGE->set_pagelayout('report');

// Check permissions.
require_capability('local/remote_backup_provider:access', $rbp->context);

// Get the courses.
if (!empty($search)) {
    $params = array('search' => $search);
    $results = $rbp->get_remote_data('local_remote_backup_provider_find_courses', $params);
    $data = array();
    foreach ($results as $result) {
        $data[] = html_writer::link(
                new moodle_url('/local/remote_backup_provider/index.php',
                        array('id' => $id, 'remote' => $result->id)
                ),
                '[' . $result->shortname . '] ' . $result->fullname
        );
    }
    if (empty($results)) $data[] = get_string('error_no_courses_found','local_remote_backup_provider');

} else if ($remote !== 0) {
    // Instantiate the restore controller, which handles the restore of the remote course.
    $restorecontroller = new extended_restore_controller($rbp, $remote);

    if ($rbp->enableuserprecheck == false) {
        // Direct import without prechecks.
        $restorecontroller->import_backup_file();
    }
    else {
        // Perform extended user checks and reporting.
        $listofusers = $restorecontroller->perform_precheck();

        // Skip user checks and proceed to direct import
        // ...in case of a fail (most likely because of a misconfiguration of the remote/client plugin)
        if ($listofusers === false){
            $restorecontroller->import_backup_file();
        }
    }
}

$PAGE->set_title($rbp->course->shortname . ': ' . get_string('import', 'local_remote_backup_provider'));
$PAGE->set_heading($rbp->course->fullname);

echo $OUTPUT->header();

// Show the list of users to import.
if ($listofusers) {
    echo $restorecontroller->display_userlist($listofusers);
} else {
    // Display the courses.
    if (!empty($data)) {
        echo html_writer::tag('h2', 'Available source courses');
        echo html_writer::tag('i', 'Source: ' . $rbp->remotesite);
        echo html_writer::alist($data);
    }

    // Show the search form.
    $mform = new search_form(null, ['id' => $id]);
    if (!$mform->is_cancelled()) {
        $toform = new stdClass();
        $toform->id = $id;
        $mform->set_data($toform);
        $mform->display();
    }
}

echo $OUTPUT->footer();
