<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//      z                                                                             x            
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

// Retrieve all previous attempts for this user/activity from the DB
$userattempts = $DB->get_records('decimalfraction_attempts', [
    'decimalfractionid' => $decimalfraction->id,
    'userid' => $USER->id
], 'questionindex, timecreated ASC');

// Organize attempts by question index
$attempts_by_q = [];
foreach ($userattempts as $ua) {
    $attempts_by_q[$ua->questionindex][] = $ua;
}

$feedbacks = [];
$showanswers = [];
$iscompleted = true;
$maxtries = 2;
$iscorrect_array = [];
$score_array = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $answers = optional_param_array('answer', [], PARAM_TEXT);

    foreach ($questions as $i => $question) {
        $useranswer = isset($answers[$i]) ? trim($answers[$i]) : '';
        $correct = compute_correct_answer($question, $decimalfraction->conversiontype);

        // Count previous tries for this question
        $currentattempts = isset($attempts_by_q[$i]) ? $attempts_by_q[$i] : [];
        $numtries = count($currentattempts);

        // If already correct or max attempts reached, don't record further attempts
        $wasalreadycorrect = false;
        foreach ($currentattempts as $prev) {
            if (!empty($prev->iscorrect)) {
                $wasalreadycorrect = true;
                break;
            }
        }
        if (!$wasalreadycorrect && $numtries < $maxtries) {
            $iscorrect = ($useranswer === trim($correct));
            $record = (object)[
                'decimalfractionid' => $decimalfraction->id,
                'userid' => $USER->id,
                'questionindex' => $i,
                'answer' => $useranswer,
                'iscorrect' => $iscorrect ? 1 : 0,
                'timecreated' => time()
            ];
            $DB->insert_record('decimalfraction_attempts', $record);

            // Add to local attempts for current page load
            $currentattempts[] = $record;
            $attempts_by_q[$i] = $currentattempts;
        }
    }

    // Refresh attempts after new insert
    $userattempts = $DB->get_records('decimalfraction_attempts', [
        'decimalfractionid' => $decimalfraction->id,
        'userid' => $USER->id
    ], 'questionindex, timecreated ASC');
    $attempts_by_q = [];
    foreach ($userattempts as $ua) {
        $attempts_by_q[$ua->questionindex][] = $ua;
    }
}

// Prepare questions for mustache: add index, feedback, state, etc.
$mustache_questions = [];
$all_attempts_used = true;
$totalgrade = 0;
foreach ($questions as $i => $q) {
    $currentattempts = isset($attempts_by_q[$i]) ? $attempts_by_q[$i] : [];
    $numtries = count($currentattempts);
    $correctanswer = compute_correct_answer($q, $decimalfraction->conversiontype);

    // Find if correct in any try, and which try
    $gotcorrect = false;
    $firstcorrecttry = null;
    foreach ($currentattempts as $tryidx => $item) {
        if (!empty($item->iscorrect)) {
            $gotcorrect = true;
            $firstcorrecttry = $tryidx+1; // 1-based
            break;
        }
    }

    // Grading: 1st try = 100%, 2nd try = 50%, else 0%
    $grade = 0;
    if ($gotcorrect) {
        if ($firstcorrecttry == 1)      $grade = 1.0;
        else if ($firstcorrecttry == 2) $grade = 0.5;
    }
    $totalgrade += $grade;

    $iscorrect_array[$i] = $gotcorrect;
    $score_array[$i] = $grade;
     $showanswer=true;
    // Should we still allow submit?
    $cansubmit = !$gotcorrect && ($numtries < $maxtries);

    if ($gotcorrect) {
        $feedback = get_string('correctfeedback', 'mod_decimalfraction');
        $showanswer;
     } else if ($numtries > 0 && $numtries < $maxtries) {
        $cansubmit = false;
        $showanswer;
    } else if ($numtries >= $maxtries && !$gotcorrect) {
         $feedback = get_string('incorrectfeedback', 'mod_decimalfraction', $correctanswer);
    } else {
        $feedback = null;
        $showanswer=true;
    }

    // Show answer button after two tries if not correct
    $showanswer = (!$gotcorrect && $numtries >= $maxtries);

    if ($cansubmit) $all_attempts_used = false;
    $showanswer = $showanswer || $gotcorrect;
    $mustache_questions[] = [
        'index' => $i,
        'text' => $q,
        'feedback' => $feedback,
        'cansubmit' => $cansubmit,
        'showanswer' => $showanswer,
        'correct' => $correctanswer
    ];
}

// Calculate overall grade (average)
$gradeoutof = count($questions) ? (array_sum($score_array) / count($questions)) * 100 : 0;

// Update Moodle Gradebook (call lib.php function, if exists)
if (function_exists('decimalfraction_update_grades')) {
    decimalfraction_update_grades($decimalfraction, $USER->id);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($decimalfraction->name);

echo $OUTPUT->render_from_template('mod_decimalfraction/view', [
    'questions' => $mustache_questions,
    'conversiontype' => $decimalfraction->conversiontype,
    'sesskey' => sesskey(),
    'all_attempts_used' => $all_attempts_used,
    'grade' => round($gradeoutof, 2)
]);

echo $OUTPUT->footer();