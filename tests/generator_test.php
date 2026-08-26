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

namespace mod_tincanlaunch;

use advanced_testcase;

/**
 * PHPUnit data generator testcase.
 *
 * @package    mod_tincanlaunch
 * @category   test
 * @copyright  2026 mod_tincanlaunch contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_tincanlaunch_generator
 */
final class generator_test extends advanced_testcase {

    /**
     * Test that the generator creates instances with expected data.
     */
    public function test_create_instance(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');

        // Create an instance with defaults.
        $instance = $generator->create_instance(['course' => $course->id]);
        $this->assertNotEmpty($instance->id);
        $this->assertNotEmpty($instance->cmid);

        $record = $DB->get_record('tincanlaunch', ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals($course->id, $record->course);
        $this->assertEquals('https://example.com/content/launch.html', $record->tincanlaunchurl);
        $this->assertEquals('https://example.com/activity/test-activity', $record->tincanactivityid);

        // The course module record should exist.
        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id, $course->id, false, MUST_EXIST);
        $this->assertEquals($instance->cmid, $cm->id);
    }

    /**
     * Test that instance-specific LRS settings can be overridden via the generator.
     */
    public function test_create_instance_with_overridden_lrs_settings(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');

        $instance = $generator->create_instance([
            'course' => $course->id,
            'overridedefaults' => 1,
            'tincanlaunchlrsendpoint' => 'https://instance-lrs.example.com/xapi/',
            'tincanlaunchlrsauthentication' => '1',
            'tincanlaunchlrslogin' => 'instancelogin',
            'tincanlaunchlrspass' => 'instancepass',
            'tincanlaunchcustomacchp' => 'https://moodle.example.com',
            'tincanlaunchuseactoremail' => '1',
            'tincanlaunchlrsduration' => '9000',
        ]);

        $lrsrecord = $DB->get_record('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals('https://instance-lrs.example.com/xapi/', $lrsrecord->lrsendpoint);
        $this->assertEquals('instancelogin', $lrsrecord->lrslogin);
        $this->assertEquals('instancepass', $lrsrecord->lrspass);
    }
}
