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
require_once($CFG->dirroot . '/mod/tincanlaunch/locallib.php');

/**
 * Unit tests for mod_tincanlaunch locallib.php.
 *
 * @package    mod_tincanlaunch
 * @category   test
 * @copyright  2026 mod_tincanlaunch contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class locallib_test extends advanced_testcase {

    /**
     * Test that tincanlaunch_myjson_encode does not escape forward slashes.
     *
     * @covers ::tincanlaunch_myjson_encode
     */
    public function test_myjson_encode_unescaped_slashes(): void {
        $data = ['url' => 'https://example.com/some/path'];
        $encoded = tincanlaunch_myjson_encode($data);

        // Standard json_encode escapes slashes; the plugin's variant must not.
        $this->assertStringNotContainsString('\\/', $encoded);
        $this->assertStringContainsString('https://example.com/some/path', $encoded);

        // The result must still be valid JSON.
        $decoded = json_decode($encoded, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($data, $decoded);
    }

    /**
     * Test that tincanlaunch_myjson_encode handles nested structures.
     *
     * @covers ::tincanlaunch_myjson_encode
     */
    public function test_myjson_encode_nested(): void {
        $data = [
            'actor' => [
                'name' => 'Test User',
                'mbox' => 'mailto:user@example.com',
            ],
            'object' => [
                'id' => 'https://example.com/activity/1',
            ],
        ];

        $encoded = tincanlaunch_myjson_encode($data);
        $decoded = json_decode($encoded, true);

        $this->assertEquals($data, $decoded);
        $this->assertStringNotContainsString('\\/', $encoded);
    }

    /**
     * Test the Moodle language to RFC 5646 conversion for a two-part lang code.
     *
     * @covers ::tincanlaunch_get_moodle_language
     */
    public function test_get_moodle_language_two_part(): void {
        global $SESSION, $USER;

        $this->resetAfterTest();

        // Force a language with a country code (e.g. pt_br).
        $SESSION->forcelang = 'pt_br';
        $USER->lang = 'pt_br';

        // current_language() honours forced/user language in Moodle.
        $this->assertEquals('pt-BR', tincanlaunch_get_moodle_language());
    }

    /**
     * Test the Moodle language conversion for a simple (single-part) lang code.
     *
     * @covers ::tincanlaunch_get_moodle_language
     */
    public function test_get_moodle_language_single_part(): void {
        global $SESSION, $USER;

        $this->resetAfterTest();

        $SESSION->forcelang = 'en';
        $USER->lang = 'en';

        $this->assertEquals('en', tincanlaunch_get_moodle_language());
    }
}
