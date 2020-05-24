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

use moodle_url;
use restore_controller;
use restore_controller_exception;
use restore_dbops;
use backup;

/**
 * Class restore_controller.
 *
 * @package local_remote_backup_provider
 */
class extended_restore_controller {

    /**
     * @var remote_backup_provider
     */
    public $rbp;

    /**
     * @var object object containing the url the the remote backup file
     */
    public $remotecourse;

    /**
     * @var \file_storage
     */
    public $fs;

    /**
     * @var array
     */
    public $filerecord;

    /**
     * @param remote_backup_provider $rbp
     * @param int $remote
     * @throws \dml_exception
     */
    public function __construct(remote_backup_provider $rbp, int $remote){
        $this->rbp = $rbp;
        $params = array('id' => $remote, remote_backup_provider::get_uniqueid());
        // Generate the backup file on remote Moodle and store the link to the file in object.
        $this->remotecourse = $rbp->get_remote_data('local_remote_backup_provider_get_course_backup_by_id', $params);
        $this->fs = get_file_storage();
        $timestamp = time();
        $this->filerecord = array(
            'contextid' => $this->rbp->context->id,
            'component' => 'local_remote_backup_provider',
            'filearea'  => 'backup',
            'itemid'    => $timestamp,
            'filepath'  => '/',
            'filename'  => 'foo1',
            'timecreated' => $timestamp,
            'timemodified' => $timestamp
        );
    }

    /**
     * Get course backup from remote instance and then perform the restore via redirect to Moodle restore dialogue
     *
     * @throws \file_exception
     * @throws \moodle_exception
     */
    public function import_backup_file(){
        // Import the backup file.
        $storedfile = $this->fs->create_file_from_url($this->filerecord,
            $this->remotecourse->url . '?token=' . $this->rbp->token, null, true);
        $restoreurl = new moodle_url('/backup/restore.php',
            array(
                'contextid'    => $this->rbp->context->id,
                'pathnamehash' => $storedfile->get_pathnamehash(),
                'contenthash'  => $storedfile->get_contenthash()
            )
        );
        redirect($restoreurl);
    }

    /**
     *
     */
    public function perform_precheck(){
        global $DB, $USER;
        $tmpid = restore_controller::get_tempdir_name($this->rbp->id, $USER->id);
        $filepath = make_backup_temp_directory($tmpid);
        if (!check_dir_exists($filepath, true, true)) {
            throw new restore_controller_exception('cannot_create_backup_temp_dir');
        }

        $storedfile = $this->fs->create_file_from_url($filerecord, $this->remotecourse->url . '?token=' . $this->rbp->token, null, true);
        $filepathold = $storedfile->get_filepath();

        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($storedfile, $filepath);
        //access user.xml in backup?

        $rc = new restore_controller($tmpid, $this->rbp->id, backup::INTERACTIVE_NO,
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

    }
}



