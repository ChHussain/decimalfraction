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
    return $DB->insert_record('decimalfraction', $data);
}

function decimalfraction_update_instance($data, $mform) {
    global $DB;
    $data->id = $data->instance;
    return $DB->update_record('decimalfraction', $data);
}

function decimalfraction_delete_instance($id) {
    global $DB;

    if (!$record = $DB->get_record('decimalfraction', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('decimalfraction_attempts', ['decimalfractionid' => $id]);
    return $DB->delete_records('decimalfraction', ['id' => $id]);
}



