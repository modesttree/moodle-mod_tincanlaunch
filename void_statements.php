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
 * Void completed statements in the LRS for a tincanlaunch activity.
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = required_param('id', PARAM_INT); // Course module id.
$userid = optional_param('userid', -1, PARAM_INT); // -1 means no selection made yet.
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('tincanlaunch', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$tincanlaunch = $DB->get_record('tincanlaunch', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/tincanlaunch:addinstance', context_course::instance($course->id));

$pageurl = new moodle_url('/mod/tincanlaunch/void_statements.php', ['id' => $cm->id]);
$returnurl = new moodle_url('/mod/tincanlaunch/view.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($tincanlaunch->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Build the list of enrolled users for the selection dropdown.
$enrolledusers = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);
$useroptions = [0 => get_string('voidstatements_allusers', 'tincanlaunch')];
foreach ($enrolledusers as $enrolleduser) {
    $useroptions[$enrolleduser->id] = fullname($enrolleduser);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('voidstatements', 'tincanlaunch'));

if ($userid === -1) {
    // Step 1: show the user selection form.
    echo html_writer::tag('p', get_string('voidstatements_desc', 'tincanlaunch'));

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_div('form-group');
    echo html_writer::label(get_string('voidstatements_user', 'tincanlaunch'), 'voiduserid', true,
        ['class' => 'mr-2']);
    echo html_writer::select($useroptions, 'userid', 0, false, ['id' => 'voiduserid', 'class' => 'ml-2']);
    echo html_writer::end_div();

    echo html_writer::start_div('mt-3');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-danger',
        'value' => get_string('voidstatements_submit', 'tincanlaunch'),
    ]);
    echo html_writer::link($returnurl, get_string('cancel'), ['class' => 'btn btn-secondary ml-2']);
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
} else if (!$confirm) {
    // Step 2: confirm the destructive action.
    require_sesskey();

    if ($userid > 0) {
        $selecteduser = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $scope = fullname($selecteduser);
    } else {
        $scope = get_string('voidstatements_allusers', 'tincanlaunch');
    }

    $continueurl = new moodle_url($pageurl, ['userid' => $userid, 'confirm' => 1, 'sesskey' => sesskey()]);
    echo $OUTPUT->confirm(
        get_string('voidstatements_confirm', 'tincanlaunch', $scope),
        $continueurl,
        $returnurl
    );
} else {
    // Step 3: perform the voiding.
    require_sesskey();

    $result = tincanlaunch_void_completed_statements($tincanlaunch, max(0, $userid));

    if (!$result['success']) {
        echo $OUTPUT->notification($result['error'], 'error');
    } else {
        echo $OUTPUT->notification(
            get_string('voidstatements_result', 'tincanlaunch', (object) [
                'voided' => $result['voided'],
                'failed' => $result['failed'],
            ]),
            $result['failed'] > 0 ? 'warning' : 'success'
        );
    }

    echo html_writer::link($returnurl, get_string('continue'), ['class' => 'btn btn-primary']);
}

echo $OUTPUT->footer();
