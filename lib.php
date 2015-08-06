<?php
function local_remote_backup_provider_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check that the filearea is sane.
    if ($filearea !== 'local_remote_backup_provider') {
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
