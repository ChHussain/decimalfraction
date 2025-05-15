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
 * @copyright  2025 YOUR NAME
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('decimalfraction', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$decimalfraction = $DB->get_record('decimalfraction', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($cm->course, true, $cm);

$PAGE->set_url('/mod/decimalfraction/view.php', ['id' => $id]);
$PAGE->set_title($decimalfraction->name);
$PAGE->set_heading($decimalfraction->name);



function compute_correct_answer($question, $type) {
    if ($type === 'fraction_to_decimal') {
        [$numerator, $denominator] = explode('/', str_replace(' ', '', $question));
        if (is_numeric($numerator) && is_numeric($denominator) && $denominator != 0) {
            return (string)($numerator / $denominator);
        }
    } elseif ($type === 'decimal_to_fraction') {
        $decimal = floatval($question);
        if ($decimal == 0.0) return '0/1';

        $precision = 1000000;
        $numerator = (int)round($decimal * $precision);
        $denominator = $precision;

        $gcd = simple_gcd($numerator, $denominator);

        return ($numerator / $gcd) . '/' . ($denominator / $gcd);
    }

    return '';
}

function simple_gcd($a, $b) {
    while ($b != 0) {
        $temp = $b;
        $b = $a % $b;
        $a = $temp;
    }
    return $a;
}

$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $answer = trim(required_param('answer', PARAM_TEXT));
    $correct = compute_correct_answer($decimalfraction->questiontext, $decimalfraction->conversiontype);

    $iscorrect = trim($answer) === trim($correct);

    // Save the attempt.
    $record = (object)[
        'decimalfractionid' => $decimalfraction->id,
        'userid' => $USER->id,
        'answer' => $answer,
        'timecreated' => time()
    ];
    $DB->insert_record('decimalfraction_attempts', $record);

    $feedback = $iscorrect
        ? get_string('correctfeedback', 'mod_decimalfraction')
        : get_string('incorrectfeedback', 'mod_decimalfraction') . ': ' . $correct;
}





echo $OUTPUT->header();
echo $OUTPUT->heading($decimalfraction->name);

echo $OUTPUT->render_from_template('mod_decimalfraction/view', [
    'questiontext' => $decimalfraction->questiontext,
    'conversiontype' => $decimalfraction->conversiontype,
    'sesskey' => sesskey(),
    'feedback' => $feedback,
]);

echo $OUTPUT->footer();
