<?php
function local_remote_backup_provider_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check that the filearea is sane.
    if ($filearea !== 'backup') {
        return false;
    }

    // Require authentication.
    require_login($course, true);

    // Capability check.
    if(!has_capability('moodle/backup:backupcourse', $context)) {
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
    if(!$file) {
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

    foreach($records as $record) {
        if($record->timemodified < (time() - DAYSECS) && ($record->filepath != '.')) {
            $file = $fs->get_file($record->contextid, $record->component, $record->filearea, $record->itemid, $record->filepath, $record->filename);
            if($file) {
                $file->delete();
                mtrace('Deleted ' . $record->pathnamehash);
            }
        }
    }
    return true;
}
