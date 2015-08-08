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
 * @copyright  2015 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $r1 = $this->create_role($role);

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
        $r1 = $this->create_role($role); // Manually backported to this module.

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
        set_time_limit(0);
    }

   /**
     * Creates a new role in the system.
     *
     * You can fill $record with the role 'name',
     * 'shortname', 'description' and 'archetype'.
     *
     * If an archetype is specified it's capabilities,
     * context where the role can be assigned and
     * all other properties are copied from the archetype;
     * if no archetype is specified it will create an
     * empty role.
     *
     * @param array|stdClass $record
     * @return int The new role id
     */
    public function create_role($record=null) {
        global $DB;
        $i = 99;
        $record = (array)$record;
        if (empty($record['shortname'])) {
            $record['shortname'] = 'role-' . $i;
        }
        if (empty($record['name'])) {
            $record['name'] = 'Test role ' . $i;
        }
        if (empty($record['description'])) {
            $record['description'] = 'Test role ' . $i . ' description';
        }
        if (empty($record['archetype'])) {
            $record['archetype'] = '';
        } else {
            $archetypes = get_role_archetypes();
            if (empty($archetypes[$record['archetype']])) {
                throw new coding_exception('\'role\' requires the field \'archetype\' to specify a ' .
                    'valid archetype shortname (editingteacher, student...)');
            }
        }
        // Creates the role.
        if (!$newroleid = create_role($record['name'], $record['shortname'], $record['description'], $record['archetype'])) {
            throw new coding_exception('There was an error creating \'' . $record['shortname'] . '\' role');
        }
        // If no archetype was specified we allow it to be added to all contexts,
        // otherwise we allow it in the archetype contexts.
        if (!$record['archetype']) {
            $contextlevels = array_keys(context_helper::get_all_levels());
        } else {
            // Copying from the archetype default rol.
            $archetyperoleid = $DB->get_field(
                'role',
                'id',
                array('shortname' => $record['archetype'], 'archetype' => $record['archetype'])
            );
            $contextlevels = get_role_contextlevels($archetyperoleid);
        }
        set_role_contextlevels($newroleid, $contextlevels);
        if ($record['archetype']) {
            // We copy all the roles the archetype can assign, override and switch to.
            if ($record['archetype']) {
                $types = array('assign', 'override', 'switch');
                foreach ($types as $type) {
                    $rolestocopy = get_default_role_archetype_allows($type, $record['archetype']);
                    foreach ($rolestocopy as $tocopy) {
                        $functionname = 'allow_' . $type;
                        $functionname($newroleid, $tocopy);
                    }
                }
            }
            // Copying the archetype capabilities.
            $sourcerole = $DB->get_record('role', array('id' => $archetyperoleid));
            role_cap_duplicate($sourcerole, $newroleid);
        }
        return $newroleid;
    }
}
