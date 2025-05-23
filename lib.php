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
 * @copyright  2025 hussain shafiq
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */




function decimalfraction_add_instance($data, $mform) {
    global $DB;
    $data->timecreated = time();
    $data->questiontext = json_encode($data->questiontext); // Save as JSON array
    return $DB->insert_record('decimalfraction', $data);

}

function decimalfraction_update_instance($data, $mform) {
     global $DB;
    $data->id = $data->instance;
    $data->questiontext = json_encode($data->questiontext); // Save as JSON array
    return $DB->update_record('decimalfraction', $data);
}

function decimalfraction_delete_instance($id) {
   
    if (!$record = $DB->get_record('decimalfraction', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('decimalfraction_attempts', ['decimalfractionid' => $id]);
    return $DB->delete_records('decimalfraction', ['id' => $id]);
}

/**
 * Returns the information on whether the module supports a feature.
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function decimalfraction_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_OTHER;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default: return null;
    }
}

/**
 * Update grades in the Moodle gradebook for a given user (or all users).
 * Called after attempts or on completion.
 * @param object $decimalfraction The decimalfraction instance object
 * @param int $userid Optional user ID (0 means all users)
 */
function decimalfraction_update_grades($decimalfraction, $userid = 0) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/gradelib.php');

    // Get list of users to grade.
    $where = 'decimalfractionid = ?';
    $params = [$decimalfraction->id];
    if ($userid) {
        $where .= ' AND userid = ?';
        $params[] = $userid;
        $users = [$userid];
    } else {
        $users = $DB->get_fieldset_select('decimalfraction_attempts', 'DISTINCT userid', 'decimalfractionid = ?', [$decimalfraction->id]);
    }

    $grades = [];
    foreach ($users as $uid) {
        // Get all attempts for this user and activity.
        $attempts = $DB->get_records_select('decimalfraction_attempts', 'decimalfractionid = ? AND userid = ?', [$decimalfraction->id, $uid], 'questionindex, timecreated ASC');
        $questions = [];
        foreach ($attempts as $a) {
            $questions[$a->questionindex][] = $a;
        }
        $totalgrade = 0;
        $numquestions = count($questions);
        foreach ($questions as $qtries) {
            $grade = 0;
            foreach ($qtries as $tryidx => $attempt) {
                if (!empty($attempt->iscorrect)) {
                    if ($tryidx == 0) $grade = 1.0;
                    else if ($tryidx == 1) $grade = 0.5;
                    break;
                }
            }
            $totalgrade += $grade;
        }
        $finalgrade = $numquestions ? $totalgrade / $numquestions * 100 : 0;
        $grades[$uid] = (object)['userid' => $uid, 'rawgrade' => $finalgrade];
    }

    // Update Moodle gradebook.
    decimalfraction_grade_item_update($decimalfraction, $grades);
}

/**
 * Create/Update grade item for this activity in the Moodle gradebook.
 *
 * @param object $decimalfraction
 * @param mixed $grades Optional array/object of grade(s)
 */
function decimalfraction_grade_item_update($decimalfraction, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => $decimalfraction->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => 100,
        'grademin' => 0,
    ];
    grade_update('mod/decimalfraction', $decimalfraction->course, 'mod', 'decimalfraction', $decimalfraction->id, 0, $grades, $params);
}

/**
 * Return a list of view actions for completion tracking
 * @return array
 */
function decimalfraction_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * Return a list of post actions for completion tracking
 * @return array
 */
function decimalfraction_get_post_actions() {
    return ['submit'];
}