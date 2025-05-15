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
 * View mod_decimalfraction instance
 *
 * @package    mod_decimalfraction
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
// require_once('locallib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('decimalfraction', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$decimalfraction = $DB->get_record('decimalfraction', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($cm->course, true, $cm);

$PAGE->set_url('/mod/decimalfraction/view.php', ['id' => $id]);
$PAGE->set_title($decimalfraction->name);
$PAGE->set_heading($decimalfraction->name);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $answer = required_param('answer', PARAM_TEXT);
    $record = (object)[
        'decimalfractionid' => $decimalfraction->id,
        'userid' => $USER->id,
        'answer' => $answer,
        'timecreated' => time()
    ];
    $DB->insert_record('decimalfraction_attempts', $record);
    redirect($PAGE->url, 'Answer submitted', 2);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($decimalfraction->name);

echo $OUTPUT->render_from_template('mod_decimalfraction/view', [
    'questiontext' => $decimalfraction->questiontext,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->footer();
