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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/tincanlaunch/lib.php');

/**
 * Unit tests for mod_tincanlaunch lib.php.
 *
 * @package    mod_tincanlaunch
 * @category   test
 * @copyright  2026 mod_tincanlaunch contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends advanced_testcase {

    /**
     * Test that the module declares support for the expected features.
     *
     * @covers ::tincanlaunch_supports
     */
    public function test_supports(): void {
        $this->assertTrue(tincanlaunch_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(tincanlaunch_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(tincanlaunch_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(tincanlaunch_supports(FEATURE_BACKUP_MOODLE2));

        // Unknown features should return null.
        $this->assertNull(tincanlaunch_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertNull(tincanlaunch_supports(FEATURE_GROUPS));
    }

    /**
     * Test adding an instance without LRS overrides.
     *
     * @covers ::tincanlaunch_add_instance
     */
    public function test_add_instance_global_lrs(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Test launch',
            'overridedefaults' => 0,
        ]);

        $record = $DB->get_record('tincanlaunch', ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals('Test launch', $record->name);
        $this->assertEquals(0, $record->overridedefaults);

        // With overridedefaults = 0 and no watershed auth, no tincanlaunch_lrs row should exist.
        $this->assertFalse($DB->record_exists('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id]));
    }

    /**
     * Test adding an instance with overridden LRS settings.
     *
     * @covers ::tincanlaunch_add_instance
     */
    public function test_add_instance_with_lrs_override(): void {
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
            'tincanlaunchlrslogin' => 'mylogin',
            'tincanlaunchlrspass' => 'mypass',
            'tincanlaunchcustomacchp' => 'https://moodle.example.com',
            'tincanlaunchuseactoremail' => '1',
            'tincanlaunchlrsduration' => '9000',
        ]);

        $lrsrecord = $DB->get_record('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals('https://instance-lrs.example.com/xapi/', $lrsrecord->lrsendpoint);
        $this->assertEquals('mylogin', $lrsrecord->lrslogin);
    }

    /**
     * Test updating an instance.
     *
     * @covers ::tincanlaunch_update_instance
     */
    public function test_update_instance(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Original name',
        ]);

        // Build the object that mod_form would pass to tincanlaunch_update_instance().
        $update = $DB->get_record('tincanlaunch', ['id' => $instance->id], '*', MUST_EXIST);
        $update->instance = $instance->id;
        $update->name = 'Updated name';
        $update->tincanlaunchlrsendpoint = 'https://lrs.example.com/xapi/';
        $update->tincanlaunchlrsauthentication = '1';
        $update->tincanlaunchlrslogin = 'login';
        $update->tincanlaunchlrspass = 'password';
        $update->tincanlaunchcustomacchp = 'https://moodle.example.com';
        $update->tincanlaunchuseactoremail = '1';
        $update->tincanlaunchlrsduration = '9000';

        $this->assertTrue(tincanlaunch_update_instance($update));

        $record = $DB->get_record('tincanlaunch', ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals('Updated name', $record->name);
        $this->assertGreaterThan(0, $record->timemodified);
    }

    /**
     * Test deleting an instance removes both the instance and any LRS override record.
     *
     * @covers ::tincanlaunch_delete_instance
     */
    public function test_delete_instance(): void {
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
            'tincanlaunchlrslogin' => 'mylogin',
            'tincanlaunchlrspass' => 'mypass',
            'tincanlaunchcustomacchp' => 'https://moodle.example.com',
            'tincanlaunchuseactoremail' => '1',
            'tincanlaunchlrsduration' => '9000',
        ]);

        $this->assertTrue($DB->record_exists('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id]));

        $this->assertTrue(tincanlaunch_delete_instance($instance->id));

        $this->assertFalse($DB->record_exists('tincanlaunch', ['id' => $instance->id]));
        $this->assertFalse($DB->record_exists('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id]));
    }

    /**
     * Test that deleting a non-existent instance fails gracefully.
     *
     * @covers ::tincanlaunch_delete_instance
     */
    public function test_delete_instance_invalid(): void {
        $this->resetAfterTest();
        $this->assertFalse(tincanlaunch_delete_instance(999999));
    }

    /**
     * Test that get_coursemodule_info returns cached cm info including custom completion data.
     *
     * @covers ::tincanlaunch_get_coursemodule_info
     */
    public function test_get_coursemodule_info(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Course module info test',
        ], [
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id, $course->id, false, MUST_EXIST);
        $info = tincanlaunch_get_coursemodule_info($cm);

        $this->assertInstanceOf(\cached_cm_info::class, $info);
        $this->assertEquals('Course module info test', $info->name);
        // With automatic completion, custom completion rules should be exposed in customdata.
        $this->assertNotEmpty($info->customdata['customcompletionrules']['tincancompletionverb']);
    }

    /**
     * Test that get_coursemodule_info returns false for an invalid instance.
     *
     * @covers ::tincanlaunch_get_coursemodule_info
     */
    public function test_get_coursemodule_info_invalid(): void {
        $this->resetAfterTest();
        $cm = (object) ['instance' => 999999, 'showdescription' => 0, 'completion' => COMPLETION_TRACKING_NONE];
        $this->assertFalse(tincanlaunch_get_coursemodule_info($cm));
    }

    /**
     * Test that getactor builds an agent using email when useactoremail is set.
     *
     * @covers ::tincanlaunch_getactor
     */
    public function test_getactor_uses_email(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user([
            'email' => 'learner@example.com',
            'username' => 'learner1',
            'firstname' => 'Test',
            'lastname' => 'Learner',
        ]);

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $course->id]);

        // Global settings use actoremail by default (set by the generator).
        $agent = tincanlaunch_getactor($instance->id, $user);

        $this->assertInstanceOf(\TinCan\Agent::class, $agent);
        $this->assertEquals('mailto:learner@example.com', $agent->getMbox());
        $this->assertEquals(fullname($user), $agent->getName());
    }

    /**
     * Test that getactor falls back to account with wwwroot when email is not used.
     *
     * @covers ::tincanlaunch_getactor
     */
    public function test_getactor_account_fallback(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user([
            'username' => 'learner1',
            'firstname' => 'Test',
            'lastname' => 'Learner',
            'idnumber' => '',
        ]);

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $course->id]);

        // Disable actor email use globally.
        set_config('tincanlaunchuseactoremail', '0', 'tincanlaunch');

        $agent = tincanlaunch_getactor($instance->id, $user);

        $this->assertInstanceOf(\TinCan\Agent::class, $agent);
        $account = $agent->getAccount();
        $this->assertEquals($CFG->wwwroot, $account->getHomePage());
        $this->assertEquals('learner1', $account->getName());
    }

    /**
     * Test that tincanlaunch_settings returns global settings when not overridden.
     *
     * @covers ::tincanlaunch_settings
     */
    public function test_settings_global(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('tincanlaunchlrsendpoint', 'https://global-lrs.example.com/xapi/', 'tincanlaunch');
        set_config('tincanlaunchlrslogin', 'globallogin', 'tincanlaunch');

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $course->id]);

        $settings = tincanlaunch_settings($instance->id);

        $this->assertEquals('https://global-lrs.example.com/xapi/', $settings['tincanlaunchlrsendpoint']);
        $this->assertEquals('globallogin', $settings['tincanlaunchlrslogin']);
        $this->assertEquals('1.0.0', $settings['tincanlaunchlrsversion']);
    }

    /**
     * Test that tincanlaunch_settings returns instance settings when overridden.
     *
     * @covers ::tincanlaunch_settings
     */
    public function test_settings_instance_override(): void {
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

        $settings = tincanlaunch_settings($instance->id);

        $this->assertEquals('https://instance-lrs.example.com/xapi/', $settings['tincanlaunchlrsendpoint']);
        $this->assertEquals('instancelogin', $settings['tincanlaunchlrslogin']);
        $this->assertEquals('instancepass', $settings['tincanlaunchlrspass']);
        $this->assertEquals('1.0.0', $settings['tincanlaunchlrsversion']);
    }

    /**
     * Test use_global_lrs_settings returns true/false correctly.
     *
     * @covers ::use_global_lrs_settings
     */
    public function test_use_global_lrs_settings(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');

        $globalinstance = $generator->create_instance(['course' => $course->id, 'overridedefaults' => 0]);
        $this->assertTrue(use_global_lrs_settings($globalinstance->id));

        $overrideinstance = $generator->create_instance([
            'course' => $course->id,
            'overridedefaults' => 1,
            'tincanlaunchlrsendpoint' => 'https://instance-lrs.example.com/xapi/',
            'tincanlaunchlrsauthentication' => '1',
            'tincanlaunchlrslogin' => 'login',
            'tincanlaunchlrspass' => 'pass',
            'tincanlaunchcustomacchp' => 'https://moodle.example.com',
            'tincanlaunchuseactoremail' => '1',
            'tincanlaunchlrsduration' => '9000',
        ]);
        $this->assertFalse(use_global_lrs_settings($overrideinstance->id));
    }

    /**
     * Test the launch URL builder produces a well-formed URL with the required query params.
     *
     * @covers ::tincanlaunch_get_launch_url
     */
    public function test_get_launch_url(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user([
            'email' => 'learner@example.com',
            'firstname' => 'Test',
            'lastname' => 'Learner',
        ]);
        $this->setUser($user);

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $course->id]);

        // tincanlaunch_get_launch_url() uses the $tincanlaunch global.
        global $tincanlaunch;
        $tincanlaunch = $DB->get_record('tincanlaunch', ['id' => $instance->id], '*', MUST_EXIST);

        $registrationid = '12345678-1234-1234-1234-123456789012';
        $url = tincanlaunch_get_launch_url($registrationid);

        // The launch URL should start with the instance launch URL.
        $this->assertStringStartsWith($tincanlaunch->tincanlaunchurl . '?', $url);

        // Parse the query string and verify the required Tin Can launch parameters.
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);

        $this->assertArrayHasKey('endpoint', $params);
        $this->assertArrayHasKey('auth', $params);
        $this->assertArrayHasKey('actor', $params);
        $this->assertArrayHasKey('registration', $params);
        $this->assertArrayHasKey('activity_id', $params);

        $this->assertEquals($registrationid, $params['registration']);
        $this->assertEquals($tincanlaunch->tincanactivityid, $params['activity_id']);

        // The actor parameter must be valid JSON containing the user's name.
        $actor = json_decode($params['actor'], true);
        $this->assertIsArray($actor);
        $this->assertEquals(fullname($user), $actor['name']);
    }
}
