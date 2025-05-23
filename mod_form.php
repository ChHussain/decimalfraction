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
 * Form for adding and editing Decimal Fraction instances
 *
 * @package    mod_decimalfraction
 * @copyright  2025 Hussain Shafiq
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_decimalfraction_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;

        // Activity name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Standard intro.
        $this->standard_intro_elements();

        // Conversion type select.
        $mform->addElement('select', 'conversiontype', get_string('conversiontype', 'mod_decimalfraction'), [
            'fraction_to_decimal' => get_string('fractiontodecimal', 'mod_decimalfraction'),
            'decimal_to_fraction' => get_string('decimaltotraction', 'mod_decimalfraction'),
        ]);
        $mform->setDefault('conversiontype', 'fraction_to_decimal');
        $mform->addHelpButton('conversiontype', 'conversiontype', 'mod_decimalfraction');

        // Number of questions select.
        $options = [];
        for ($i = 1; $i <= 10; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'numofquestions', get_string('numofquestions', 'mod_decimalfraction'), $options);
        $mform->setType('numofquestions', PARAM_INT);
        $mform->setDefault('numofquestions', 1);

        // Question mode select.
        $mform->addElement('select',  'questionmode',  get_string('questionmode', 'mod_decimalfraction'),  [
            'random' => get_string('random', 'mod_decimalfraction'),
            'manual' => get_string('manual', 'mod_decimalfraction'),
        ]);
        $mform->setDefault('questionmode', 'manual');

        // Add question inputs (multiple).
        $numquestions = optional_param('numofquestions', 1, PARAM_INT);
        for ($i = 1; $i <= $numquestions; $i++) {
            $mform->addElement('textarea', "questiontext[$i]", get_string('questiontext', 'mod_decimalfraction') . " $i", ['rows' => 2, 'cols' => 60]);
            $mform->setType("questiontext[$i]", PARAM_TEXT);
            $mform->addRule("questiontext[$i]", null, 'required', null, 'client');
        }

        // Standard course module settings, grading, etc.
        $this->standard_coursemodule_elements();

        // Action buttons.
        $this->add_action_buttons();

        // Add JS for dynamic question inputs.
        $this->add_dynamic_js();
    }

    private function add_dynamic_js() {
        global $PAGE;
        $PAGE->requires->js_amd_inline("
            require([], function() {
                var numSelect = document.querySelector('[name=numofquestions]');
                if (!numSelect) return;
                numSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            });
        ");
    }

    public function data_preprocessing(&$default_values) {
        // Ensure questiontext is an array if loaded from DB.
        if (isset($default_values['questiontext']) && !is_array($default_values['questiontext'])) {
            $default_values['questiontext'] = json_decode($default_values['questiontext'], true);
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}