<?php

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

class local_remote_backup_provider_external extends external_api {
    public static function get_course_backup_by_id_parameters() {
        return new external_function_parameters(
	    array(
	        'id' => new external_value(PARAM_INT, 'id'),
                'username' => new external_value(PARAM_USERNAME, 'username'),
	    )
	);
    }

    public static function get_course_backup_by_id($id, $username) {
        global $CFG, $DB;

        // Extract the userid from the username.
        $userid = $DB->get_field('user', 'id', array('username' => $username));

        $bc = new backup_controller(\backup::TYPE_1COURSE, $id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid);

        $bc->set_status(backup::STATUS_AWAITING);
	$bc->execute_plan();
	$result = $bc->get_results();

	if(isset($result['backup_destination']) && $result['backup_destination']) {
	    $file = $result['backup_destination'];
	    $context = context_course::instance($id);
	    $fs = get_file_storage();
            $timestamp = time();

	    $file_record = array('contextid' => $context->id, 'component' => 'local_remote_backup_provider', 'filearea' => 'backup', 'itemid' => $timestamp, 'filepath' => '/', 'filename' => 'foo', 'timecreated' => $timestamp, 'timemodified' => $timestamp);
	    $stored_file = $fs->create_file_from_storedfile($file_record, $file);
            $file->delete();

            // Make the link.
	    $filepath = $stored_file->get_filepath().$stored_file->get_filename();
            $fileurl = moodle_url::make_webservice_pluginfile_url($stored_file->get_contextid(), $stored_file->get_component(), $stored_file->get_filearea(), $stored_file->get_itemid(), $stored_file->get_filepath(), $stored_file->get_filename());
	    return array('url' => $fileurl->out(true));
        } else {
	    return false;
	}
    }

    public static function get_course_backup_by_id_returns() {
        return new external_single_structure(
	    array(
	        'url' => new external_value(PARAM_RAW, 'url of the backup file'),
            )
	);
    }
}
