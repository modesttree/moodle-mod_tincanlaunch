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
 * Compatibility tests for mod_tincanlaunch against the running Moodle version.
 *
 * These tests guard against regressions like the removed
 * core_renderer::activity_information() API (removed in Moodle 5.0, MDL-78869)
 * which previously caused "Call to undefined method" fatal errors in view.php.
 *
 * @package    mod_tincanlaunch
 * @category   test
 * @copyright  2026 mod_tincanlaunch contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class compatibility_test extends advanced_testcase {

    /**
     * Test that the plugin does not call the removed core_renderer::activity_information() method.
     *
     * Regression test for: "Call to undefined method
     * theme_boost\output\core_renderer::activity_information()" on Moodle 5+.
     */
    public function test_view_page_does_not_use_removed_activity_information_method(): void {
        global $CFG;

        // The method must not exist in the running Moodle (it was removed in 5.0).
        // If a future Moodle reintroduces it, this assertion documents the expectation
        // that the plugin no longer depends on it either way.
        $viewsource = file_get_contents($CFG->dirroot . '/mod/tincanlaunch/view.php');

        $this->assertIsString($viewsource);
        $this->assertStringNotContainsString(
            'activity_information(',
            $viewsource,
            'view.php must not call the core_renderer::activity_information() method removed in Moodle 5.0'
        );
    }

    /**
     * Test that all PHP files in the plugin parse without errors on the running PHP version.
     *
     * @dataProvider plugin_php_files_provider
     * @param string $relativepath Path to the file relative to the plugin root.
     */
    public function test_php_file_parses(string $relativepath): void {
        global $CFG;

        $fullpath = $CFG->dirroot . '/mod/tincanlaunch/' . $relativepath;
        $this->assertFileExists($fullpath);

        // php -l equivalent: include the file in a tokenized parse check.
        $code = file_get_contents($fullpath);
        $tokens = @token_get_all($code, TOKEN_PARSE);
        $this->assertNotEmpty($tokens, "Failed to parse {$relativepath}");
    }

    /**
     * Data provider listing the plugin's main PHP entry points.
     *
     * @return array[]
     */
    public static function plugin_php_files_provider(): array {
        return [
            'view.php' => ['view.php'],
            'launch.php' => ['launch.php'],
            'lib.php' => ['lib.php'],
            'locallib.php' => ['locallib.php'],
            'mod_form.php' => ['mod_form.php'],
            'settings.php' => ['settings.php'],
            'settingslib.php' => ['settingslib.php'],
            'version.php' => ['version.php'],
            'completion_check.php' => ['completion_check.php'],
            'index.php' => ['index.php'],
            'custom completion class' => ['classes/completion/custom_completion.php'],
            'check completion task' => ['classes/task/check_completion.php'],
            'activity completed event' => ['classes/event/activity_completed.php'],
            'activity launched event' => ['classes/event/activity_launched.php'],
            'course module viewed event' => ['classes/event/course_module_viewed.php'],
            'instance list viewed event' => ['classes/event/course_module_instance_list_viewed.php'],
        ];
    }

    /**
     * Test that the plugin's events are correctly defined and loadable.
     */
    public function test_events_loadable(): void {
        $this->assertTrue(class_exists(\mod_tincanlaunch\event\course_module_viewed::class));
        $this->assertTrue(class_exists(\mod_tincanlaunch\event\course_module_instance_list_viewed::class));
        $this->assertTrue(class_exists(\mod_tincanlaunch\event\activity_launched::class));
        $this->assertTrue(class_exists(\mod_tincanlaunch\event\activity_completed::class));
    }

    /**
     * Test that the scheduled task class is correctly defined and loadable.
     */
    public function test_scheduled_task_loadable(): void {
        $this->assertTrue(class_exists(\mod_tincanlaunch\task\check_completion::class));

        $task = new \mod_tincanlaunch\task\check_completion();
        $this->assertInstanceOf(\core\task\scheduled_task::class, $task);
        $this->assertNotEmpty($task->get_name());
    }

    /**
     * Test that the custom completion class extends the expected core base class.
     */
    public function test_custom_completion_extends_core_class(): void {
        $this->assertTrue(
            is_subclass_of(
                \mod_tincanlaunch\completion\custom_completion::class,
                \core_completion\activity_custom_completion::class
            )
        );
    }
}
