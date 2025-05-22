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

// Decode questions from JSON (if stored as JSON array)
$questions = json_decode($decimalfraction->questiontext, true);
if (!is_array($questions)) {
    // fallback: single string
    $questions = [$decimalfraction->questiontext];
}

$feedbacks = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $answers = optional_param_array('answer', [], PARAM_TEXT);

    foreach ($questions as $i => $question) {
        $useranswer = isset($answers[$i]) ? trim($answers[$i]) : '';
        $correct = compute_correct_answer($question, $decimalfraction->conversiontype);

        $iscorrect = ($useranswer === trim($correct));

        // Save each attempt
        $record = (object)[
            'decimalfractionid' => $decimalfraction->id,
            'userid' => $USER->id,
            'questionindex' => $i,
            'answer' => $useranswer,
            'timecreated' => time()
        ];
        $DB->insert_record('decimalfraction_attempts', $record);

        $feedbacks[$i] = $iscorrect
            ? get_string('correctfeedback', 'mod_decimalfraction')
            : get_string('incorrectfeedback', 'mod_decimalfraction') . ': ' . $correct;
    }
}

// Prepare questions for mustache: add index for each and feedback if available
$mustache_questions = [];
foreach ($questions as $i => $q) {
    $mustache_questions[] = [
        'index' => $i,
        'text' => $q,
        'feedback' => isset($feedbacks[$i]) ? $feedbacks[$i] : null,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading($decimalfraction->name);

echo $OUTPUT->render_from_template('mod_decimalfraction/view', [
    'questions' => $mustache_questions,
    'conversiontype' => $decimalfraction->conversiontype,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->footer();