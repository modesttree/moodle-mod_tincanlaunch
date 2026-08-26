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
 * Data generator for mod_tincanlaunch.
 *
 * @package    mod_tincanlaunch
 * @category   test
 * @copyright  2026 mod_tincanlaunch contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * tincanlaunch module data generator class.
 *
 * @package    mod_tincanlaunch
 * @category   test
 * @copyright  2026 mod_tincanlaunch contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_tincanlaunch_generator extends testing_module_generator {

    /**
     * Create a new tincanlaunch module instance.
     *
     * @param array|stdClass|null $record Data for the module instance.
     * @param array|null $options General options for creating the course module.
     * @return stdClass The tincanlaunch instance record.
     */
    public function create_instance($record = null, $options = null) {
        $record = (object) (array) $record;

        $defaults = [
            'tincanlaunchurl' => 'https://example.com/content/launch.html',
            'tincanactivityid' => 'https://example.com/activity/test-activity',
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 365,
            'overridedefaults' => 0,
            'tincanmultipleregs' => 1,
            'tincansimplelaunchnav' => 0,
        ];

        foreach ($defaults as $field => $value) {
            if (!isset($record->{$field})) {
                $record->{$field} = $value;
            }
        }

        // Ensure global LRS defaults exist for instances that do not override them.
        $this->set_default_lrs_config();

        return parent::create_instance($record, $options);
    }

    /**
     * Ensure that the global LRS plugin config defaults are set.
     *
     * These mirror the defaults in settings.php so that instances using
     * global settings have a valid (but non-routable) endpoint.
     */
    protected function set_default_lrs_config(): void {
        $defaults = [
            'tincanlaunchlrsendpoint' => 'https://lrs.example.com/xapi/',
            'tincanlaunchlrsauthentication' => '1',
            'tincanlaunchlrslogin' => 'login',
            'tincanlaunchlrspass' => 'password',
            'tincanlaunchlrsduration' => '9000',
            'tincanlaunchcustomacchp' => 'https://moodle.example.com',
            'tincanlaunchuseactoremail' => '1',
        ];

        foreach ($defaults as $name => $value) {
            if (get_config('tincanlaunch', $name) === false) {
                set_config($name, $value, 'tincanlaunch');
            }
        }
    }
}
