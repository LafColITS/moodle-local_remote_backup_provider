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
 * Unit tests for local_remote_backup_provider
 *
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/remote_backup_provider/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Unit tests for local_remote_backup_provider
 *
 * @package    local_remote_backup_provider
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_remote_backup_provider_testcase extends externallib_advanced_testcase {
    /**
     * Ensure that the find_courses() web service function works.
     */
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

    /**
     * Ensure that the get_course_backup_by_id() web service function works.
     */
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
