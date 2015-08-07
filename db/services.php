<?php

$functions = array(
    'local_remote_backup_provider_find_courses' => array(
         'classname' => 'local_remote_backup_provider_external',
         'methodname' => 'find_courses',
         'classpath' => 'local/remote_backup_provider/externallib.php',
         'description' => 'Find courses matching a given string.',
         'type' => 'read',
         'capabilities' => '',
    ),
    'local_remote_backup_provider_get_course_backup_by_id' => array(
         'classname' => 'local_remote_backup_provider_external',
         'methodname' => 'get_course_backup_by_id',
         'classpath' => 'local/remote_backup_provider/externallib.php',
         'description' => 'Generate a course backup file and return a link.',
         'type' => 'read',
         'capabilities' => 'moodle/backup:backupcourse',
    ),
);
