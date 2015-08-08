<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/remote_backup_provider/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

class local_remote_backup_provider_testcase extends externallib_advanced_testcase {
    public function test_find_courses() {
        $this->resetAfterTest(true);
	$contextid = context_system::instance()->id;

        $role = new stdClass();
        $role->name = 'Web service user';
        $r1 = $this->getDataGenerator()->create_role($role);

	$user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($r1, $user->id);
        $this->assignUserCapability('moodle/course:viewhiddencourses', $contextid, $r1);

	$this->setUser($user);

        $course1 = new stdClass();
        $course1->fullname  = 'Test Course 1';
        $course1->shortname = 'CF101';
        $course2 = new stdClass();
        $course2->fullname  = 'Test Course 2';
        $course2->shortname = 'CF102';
        $c1 = $this->getDataGenerator()->create_course($course1);
        $c2 = $this->getDataGenerator()->create_course($course2);

        $results = local_remote_backup_provider_external::find_courses('test');
        $this->assertEquals(2, count($results));
    }

    public function test_get_course_backup_by_id() {
        $this->resetAfterTest(true);
        $contextid = context_system::instance()->id;

        $role = new stdClass();
        $role->name = 'Web service user';
        $r1 = $this->getDataGenerator()->create_role($role);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($r1, $user->id);
        $this->assignUserCapability('moodle/backup:backupcourse', $contextid, $r1);    

        $this->setUser($user);

        $course1 = new stdClass();
        $course1->fullname  = 'Test Course 1';
        $course1->shortname = 'CF101';
        $c1 = $this->getDataGenerator()->create_course($course1);

        $result = local_remote_backup_provider_external::get_course_backup_by_id($c1->id, $user->username);
        $this->assertNotEmpty($result);
    }
}
