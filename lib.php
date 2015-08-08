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

function local_remote_backup_provider_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/remote_backup_provider:access', $context)) {
        $url = new moodle_url('/local/remote_backup_provider/index.php', array('id' => $course->id));
        $navigation->add(get_string('import', 'local_remote_backup_provider'), $url,
                navigation_node::TYPE_SETTING, null, null, new pix_icon('i/import', ''));
    }
}

function local_remote_backup_provider_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check that the filearea is sane.
    if ($filearea !== 'backup') {
        return false;
    }

    // Require authentication.
    require_login($course, true);

    // Capability check.
    if (!has_capability('moodle/backup:backupcourse', $context)) {
        return false;
    }

    // Extract the filename / filepath from the $args array.
    $itemid = array_shift($args);
    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    // Retrieve the file.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_remote_backup_provider', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function local_remote_backup_provider_cron() {
    global $DB;
    mtrace('Deleting old remote backup files');

    // Get component files.
    $records = $DB->get_records('files', array('component' => 'local_remote_backup_provider', 'filearea' => 'backup'));
    $fs = get_file_storage();

    foreach ($records as $record) {
        if ($record->timemodified < (time() - DAYSECS) && ($record->filepath != '.')) {
            $file = $fs->get_file(
                $record->contextid,
                $record->component,
                $record->filearea,
                $record->itemid,
                $record->filepath,
                $record->filename
            );
            if ($file) {
                $file->delete();
                mtrace('Deleted ' . $record->pathnamehash);
            }
        }
    }
    return true;
}
