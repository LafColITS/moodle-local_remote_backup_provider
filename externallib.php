<?php

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

class local_remote_backup_provider_external extends external_api {
    public static function find_courses_parameters() {
        return new external_function_parameters(
            array(
                'search' => new external_value(PARAM_CLEAN, 'search'),
            )
        );
    }

    public static function find_courses($search) {
        global $DB;

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::find_courses_parameters(), array('search' => $search));

        // Build query.
        $searchsql    = '';
        $searchparams = array();
        $searchlikes = array();
        $searchfields = array('c.shortname', 'c.fullname', 'c.idnumber');
        for($i=0; $i<count($searchfields); $i++) {
            $searchlikes[$i] = $DB->sql_like($searchfields[$i], ":s{$i}", false, false);
            $searchparams["s{$i}"] = '%' . $search . '%';
        }
        $searchsql = implode(' OR ', $searchlikes);

        // Run query.
        $fields = 'c.id,c.idnumber,c.shortname,c.fullname';
        $sql = "SELECT $fields FROM {course} c WHERE $searchsql ORDER BY c.shortname ASC";
        error_log($sql);
        $courses = $DB->get_records_sql($sql, $searchparams, 0);
        return $courses;
    }

    public static function find_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'        => new external_value(PARAM_INT, 'id of course'),
                    'idnumber'  => new external_value(PARAM_RAW, 'idnumber of course'),
                    'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                    'fullname'  => new external_value(PARAM_RAW, 'long name of course'),
                )
            )
        );
    }

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
