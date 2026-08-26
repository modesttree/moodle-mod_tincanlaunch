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

declare(strict_types=1);

namespace mod_tincanlaunch;

use advanced_testcase;
use cm_info;
use coding_exception;
use mod_tincanlaunch\completion\custom_completion;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/tincanlaunch/lib.php');

/**
 * Unit tests for the tincanlaunch custom completion class.
 *
 * Note: tests do not exercise get_state() against a live LRS; instead they
 * cover rule definition, descriptions, ordering and validation behaviour.
 *
 * @package    mod_tincanlaunch
 * @category   test
 * @copyright  2026 mod_tincanlaunch contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_tincanlaunch\completion\custom_completion
 */
final class custom_completion_test extends advanced_testcase {

    /**
     * Test that the module defines the expected custom completion rules.
     */
    public function test_get_defined_custom_rules(): void {
        $rules = custom_completion::get_defined_custom_rules();

        $this->assertContains('tincancompletionverb', $rules);
        $this->assertContains('tincancompletioexpiry', $rules);
    }

    /**
     * Test the sort order of the completion rules includes the core view rule.
     */
    public function test_get_sort_order(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id, $course->id, false, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new custom_completion($cminfo, $user->id);
        $sortorder = $customcompletion->get_sort_order();

        $this->assertContains('completionview', $sortorder);
        $this->assertContains('tincancompletionverb', $sortorder);
        $this->assertContains('tincancompletioexpiry', $sortorder);
        $this->assertEquals('completionview', $sortorder[0]);
    }

    /**
     * Test that custom rule descriptions are generated from the instance settings.
     */
    public function test_get_custom_rule_descriptions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 90,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id, $course->id, false, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new custom_completion($cminfo, $user->id);
        $descriptions = $customcompletion->get_custom_rule_descriptions();

        $this->assertArrayHasKey('tincancompletionverb', $descriptions);
        $this->assertArrayHasKey('tincancompletioexpiry', $descriptions);

        // The verb description should contain the humanised verb name.
        $this->assertStringContainsString('Completed', $descriptions['tincancompletionverb']);
        // The expiry description should contain the number of days.
        $this->assertStringContainsString('90', $descriptions['tincancompletioexpiry']);
    }

    /**
     * Test that manual completion is always shown regardless of course settings.
     */
    public function test_manual_completion_always_shown(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id, $course->id, false, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new custom_completion($cminfo, $user->id);
        $this->assertTrue($customcompletion->manual_completion_always_shown());
    }

    /**
     * Test that requesting the state of an undefined rule throws an exception.
     */
    public function test_get_state_invalid_rule(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id, $course->id, false, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new custom_completion($cminfo, $user->id);

        $this->expectException(coding_exception::class);
        $customcompletion->get_state('somerandomrule');
    }

    /**
     * Test that get_state returns incomplete when no completion verb is configured.
     *
     * With no verb configured, no LRS request is made and the rule cannot be met.
     */
    public function test_get_state_no_verb_configured(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();

        /** @var \mod_tincanlaunch_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance([
            'course' => $course->id,
            'tincanverbid' => '',
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id, $course->id, false, MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $customcompletion = new custom_completion($cminfo, $user->id);

        $this->assertEquals(COMPLETION_INCOMPLETE, $customcompletion->get_state('tincancompletionverb'));
    }
}
