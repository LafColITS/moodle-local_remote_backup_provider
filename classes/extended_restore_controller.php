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

use backup;
use dml_exception;
use file_exception;
use file_storage;
use moodle_exception;
use moodle_url;
use restore_controller;
use restore_controller_exception;
use restore_dbops;
use local_remote_backup_provider\output\viewpage;

use context_course;

// apparently use restore_controller does not work, we have to use require_once
require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");

defined('MOODLE_INTERNAL') || die();

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
     * @var file_storage
     */
    public $fs;

    /**
     * @var array
     */
    public $filerecord;

    /**
     * @param remote_backup_provider $rbp
     * @param int $remote
     * @throws dml_exception
     */
    public function __construct(remote_backup_provider $rbp, int $remote) {
        $this->rbp = $rbp;
        $params['uniqueid'] = remote_backup_provider::get_uniqueid()->value;
        $params['id'] = $remote;
        // Generate the backup file on remote Moodle and store the link to the file in object.
        $this->remotecourse = $rbp->get_remote_data('local_remote_backup_provider_get_course_backup_by_id', $params);
        $this->fs = get_file_storage();
        $timestamp = time();
        $this->filerecord = array(
                'contextid' => $this->rbp->context->id,
                'component' => 'local_remote_backup_provider',
                'filearea' => 'backup',
                'itemid' => $timestamp,
                'filepath' => '/',
                'filename' => 'foo1',
                'timecreated' => $timestamp,
                'timemodified' => $timestamp
        );
    }

    /**
     * Get course backup from remote instance and then perform the restore via redirect to Moodle restore dialogue
     *
     * @throws file_exception
     * @throws moodle_exception
     */
    public function import_backup_file() {
        // Import the backup file.
        $storedfile = $this->fs->create_file_from_url($this->filerecord,
                $this->remotecourse->url . '?token=' . $this->rbp->token, null, true);
        $restoreurl = new moodle_url('/backup/restore.php',
                array(
                        'contextid' => $this->rbp->context->id,
                        'pathnamehash' => $storedfile->get_pathnamehash(),
                        'contenthash' => $storedfile->get_contenthash()
                )
        );
        redirect($restoreurl);
    }

    /**
     *
     */
    public function perform_precheck() {
        global $DB, $USER, $CFG;


        // TODO: Delete, only for debugging!
        $CFG->cachejs = false;
        
        $tmpid = restore_controller::get_tempdir_name($this->rbp->id, $USER->id);
        $filepath = make_backup_temp_directory($tmpid);
        if (!check_dir_exists($filepath, true, true)) {
            throw new restore_controller_exception('cannot_create_backup_temp_dir');
        }

        $storedfile = $this->fs->create_file_from_url($this->filerecord, $this->remotecourse->url . '?token=' . $this->rbp->token,
                null, true);

        $restoreurl = new moodle_url('/backup/restore.php',
                array(
                        'contextid' => $this->rbp->context->id,
                        'pathnamehash' => $storedfile->get_pathnamehash(),
                        'contenthash' => $storedfile->get_contenthash()
                )
        );
        
        $filepathold = $storedfile->get_filepath();

        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($storedfile, $filepath);
        // Access user.xml in backup?

        $rc = new restore_controller($tmpid, $this->rbp->id, backup::INTERACTIVE_NO,
                backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);
        $plan = $rc->get_plan();
        $restoreinfo = $rc->get_info();

        $rc->execute_precheck();

        $file = $rc->get_plan()->get_basepath() . '/users.xml';

        //$this->deleteuserfromuserxml([1811, 1810, 2], $rc->get_plan()->get_basepath() . '/users.xml');


        restore_dbops::load_users_to_tempids($rc->get_restoreid(), $file);

        // $newuser = $DB->get_records('backup_ids_temp', ['backupid' => $rc->get_restoreid(), 'itemname' => 'user']);
        // $recordtodelete = $DB->delete_records('backup_ids_temp', ['itemid' => 1810]);
        // $recordtodelete = $DB->delete_records('backup_ids_temp', ['itemid' => 1811]);

        $users = $this->return_list_of_users_to_import($USER->id, $this->rbp->id, $rc->get_restoreid());

        return $this->checkandmanipulateusers($users, $rc->get_restoreid(), $restoreurl);
    }


    public function return_list_of_users_to_import($userid, $courseid, $restoreid) {

        global $CFG, $DB;



        // To return any problem found
        $users = array();

        // We are going to map mnethostid, so load all the available ones
        // $mnethosts = $DB->get_records('mnet_host', array(), 'wwwroot', 'wwwroot, id');

        // Calculate the context we are going to use for capability checking
        $context = context_course::instance($courseid);

        // Prepare for reporting progress.
        $conditions = array('backupid' => $restoreid, 'itemname' => 'user');

        // Iterate over all the included users
        $rs = $DB->get_recordset('backup_ids_temp', $conditions, '', 'itemid, info');
        foreach ($rs as $recuser) {
            $user = (object)\backup_controller_dbops::decode_backup_temp_info($recuser->info);
            $users[] = $user;
        }

    return $users;

    }

    public function displaylistofusers($list) {
        global $PAGE;

        $output = $PAGE->get_renderer('local_remote_backup_provider');
        $out = '';
        // Create the list of open games we can pass on to the renderer.

        $viewpage = new viewpage($list);
        $out .= $output->render_viewpage($viewpage);

        //$PAGE->requires->js_call_amd('local_remote_backup_provider/list', 'init');

        return $out;
    }


    private function checkandmanipulateusers($users, $restoreid, $restoreurl) {

        global $DB, $USER, $CFG;


        $context = \context_system::instance();
        $userid = $USER->id;

        // we need to know if we are allowed to create entries in db
        $cancreateuser = false;
        if (has_capability('moodle/restore:createuser', $context, $userid) and
                has_capability('moodle/restore:userinfo', $context, $userid) and
                empty($CFG->disableusercreationonrestore)) { // Can create users
            $cancreateuser = true;
        }

        $list = array();

        foreach ($users as $user) {

            $existinguser = null;
            $matchuserstring = '';
            $classstring = '';
            
            // We look for troubles;
            // if ($rec = $DB->get_record('user', array('id'=>$user->id, 'username'=>$user->username, 'mnethostid'=>$user->mnethostid))) {

            // First, no troubles, clean match
            if ($recs = $DB->get_records('user', array('username'=>$user->username, 'email'=>$user->email))) {
                $matchuserstring = get_string('perfectmatch', 'local_remote_backup_provider');
            } else if ($recs = $DB->get_records('user', array('username'=>$user->username))) {
                $matchuserstring = get_string('differentmail', 'local_remote_backup_provider');
            } else if ($recs = $DB->get_records('user', array('email'=>$user->email))) {
                $matchuserstring = get_string('differentusername', 'local_remote_backup_provider');
            } else if ($recs = $DB->get_records('user', array('firstname'=>$user->firstname, 'lastname'=>$user->lastname))) {
                $matchuserstring = get_string('samefirstandlastname', 'local_remote_backup_provider');
            } else {

                //if you are allowed to create a new user, this will be green, else it will be red
                if ($cancreateuser) {
                    $matchuserstring = get_string('createasnew', 'local_remote_backup_provider');
                } else {
                    $matchuserstring = get_string('notallowedtocreate', 'local_remote_backup_provider');
                }
                
            }

            $newuser = [
                'id' => $user->id,
                'username' => $user->username,
                'useremail' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'matchuser' => $matchuserstring,
                'class' => 'table-success'
            ];

            // We can't import users if we don't have the right to.
            if ($matchuserstring == get_string('notallowedtocreate', 'local_remote_backup_provider')) {
                $newuser['class'] = 'table-danger';
            }


            $newuser['matchingusers'] = array();
            
            if ($recs && count($recs) > 0) {
                
                // We run through the result of our DB Search, we might have more than one match
                foreach ($recs as $rec) {
                    $existinguser = [
                        'id' => $rec->id,
                        'username' => $this->addclassifsame($rec->username, $user->username,  $rec->id),
                        'useremail' => $this->addclassifsame($rec->email, $user->email,  $rec->id),
                        'firstname' => $this->addclassifsame($rec->firstname, $user->firstname,  $rec->id),
                        'lastname' => $this->addclassifsame($rec->lastname, $user->lastname,  $rec->id),
                        'matchuser' => get_string('existinguser', 'local_remote_backup_provider'),
                    ];
                    
                    //we overwrite newuser with span classes to show similarities to found records
                        $newuser['username'] = $this->addclassifsame($user->username, $rec->username,  $rec->id);
                        $newuser['useremail'] = $this->addclassifsame($user->email, $rec->email,  $rec->id);
                        $newuser['firstname'] = $this->addclassifsame($user->firstname, $rec->firstname,  $rec->id);
                        $newuser['lastname'] = $this->addclassifsame($user->lastname, $rec->lastname,  $rec->id);
                        
                        if ($matchuserstring != null) {
                            $newuser['class'] = 'table-danger';
                        }
                        
                    array_push($newuser['matchingusers'], $existinguser);

                }

            }


            //we add the user we have now to list
            $list[] = $newuser;
        }

        $list['users'] = $list;
        $list['restoreid'] = $restoreid;
        $list['restoreurl'] = $restoreurl;
        

        return $list;
    }


    public static function deleteuserfromuserxml(array $userids, $pathtoxml) {

        $contents = file_get_contents($pathtoxml);

        foreach ($userids as $userid) {

            $cutstring = strstr($contents, '<user id="'. $userid . '"');
            $cutstring = strstr($cutstring, '</user>', true);
            $contents = str_replace($cutstring . '</user>', '', $contents);

        }
        
        $result = file_put_contents($pathtoxml, $contents);

        return $result;
    }



    
    private function addclassifsame($firststring, $secondstring, $userid) {

        global $CFG;

        if (strtolower($firststring) == strtolower($secondstring)) {
            return '<a href="' . $CFG->httpswwwroot . '/user/profile.php?id=' . $userid .  '" class="text-success">' . $firststring . '</a>';
        } else {
            return $firststring;
        }

    }
}



