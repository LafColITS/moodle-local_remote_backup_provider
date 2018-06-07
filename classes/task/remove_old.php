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
 * @copyright  2018 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_remote_backup_provider\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task (cron task) that removes old remote backup files.
 */
class remove_old extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('remove_old_task', 'local_remote_backup_provider');
    }

    public function execute() {
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
}
