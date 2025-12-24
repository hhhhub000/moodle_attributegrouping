<?php
/**
 * Main view page.
 *
 * @package    mod_attributegrouping
 * @copyright  2024 飛田北斗 <hokutoh@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/attributegrouping/lib.php');
require_once($CFG->dirroot . '/mod/attributegrouping/locallib.php');
require_once($CFG->libdir . '/formslib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.

$cm = get_coursemodule_from_id('attributegrouping', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$attributegrouping = $DB->get_record('attributegrouping', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/attributegrouping/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($attributegrouping->name));
$PAGE->set_heading(format_string($course->fullname));

// --- Form Definition ---
class mod_attributegrouping_input_form extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;
        $attributegrouping = $this->_customdata['attributegrouping'];

        $mform->addElement('header', 'general', get_string('input_attributes', 'mod_attributegrouping'));

        // International Student (Yes/No).
        $options = array(0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'international', get_string('international', 'mod_attributegrouping'), $options);
        $mform->setType('international', PARAM_INT);

        // Field (Select from options).
        $field_options_raw = explode("\n", $attributegrouping->field_options);
        $field_options = array();
        foreach ($field_options_raw as $opt) {
            $opt = trim($opt);
            if ($opt !== '') {
                $field_options[$opt] = $opt;
            }
        }
        $mform->addElement('select', 'field', get_string('field', 'mod_attributegrouping'), $field_options);
        $mform->setType('field', PARAM_TEXT);
        $mform->addRule('field', null, 'required', null, 'client');

        // Major (Select from options).
        $major_options_raw = explode("\n", $attributegrouping->major_options);
        $major_options = array();
        foreach ($major_options_raw as $opt) {
            $opt = trim($opt);
            if ($opt !== '') {
                $major_options[$opt] = $opt;
            }
        }
        $mform->addElement('select', 'major', get_string('major', 'mod_attributegrouping'), $major_options);
        $mform->setType('major', PARAM_TEXT);
        $mform->addRule('major', null, 'required', null, 'client');

        // Self Introduction.
        $mform->addElement('textarea', 'selfintro', get_string('selfintro', 'mod_attributegrouping'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('selfintro', PARAM_TEXT);
        $mform->addRule('selfintro', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id'); // CM ID
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(false, get_string('save', 'mod_attributegrouping'));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($attributegrouping->name));

if ($attributegrouping->intro) {
    echo $OUTPUT->box(format_module_intro('attributegrouping', $attributegrouping, $cm->id), 'generalbox mod_introbox', 'intro');
}

// --- Logic ---

// 1. Check if user is a teacher (can manage).
if (has_capability('mod/attributegrouping:manage', $context)) {
    // TEACHER VIEW
    echo $OUTPUT->heading(get_string('grouping_candidates', 'mod_attributegrouping'), 3);

    $groupsize = optional_param('groupsize', 4, PARAM_INT);

    echo '<form method="get" action="view.php" class="form-inline">';
    echo '<input type="hidden" name="id" value="' . $id . '">';
    echo '<label for="groupsize">' . get_string('group_size', 'mod_attributegrouping') . ': </label>';
    echo '<input type="number" name="groupsize" id="groupsize" value="' . $groupsize . '" min="1" class="form-control" style="width: auto; display: inline-block; margin-right: 10px;">';
    echo '<input type="submit" value="' . get_string('calculate', 'mod_attributegrouping') . '" class="btn btn-primary">';
    echo '</form>';
    echo '<br>';

    $groups = mod_attributegrouping_calculate_groups($attributegrouping->id, $groupsize);

    if (empty($groups)) {
        echo $OUTPUT->notification(get_string('no_data', 'mod_attributegrouping'), 'warning');
    } else {
        foreach ($groups as $i => $group) {
            echo '<div class="card mb-3">';
            echo '<div class="card-header">Group ' . ($i + 1) . '</div>';
            echo '<div class="card-body">';
            echo '<table class="table table-sm">';
            echo '<thead><tr><th>Name</th><th>International</th><th>Field</th><th>Major</th><th>Self Intro</th></tr></thead>';
            echo '<tbody>';
            foreach ($group as $student) {
                echo '<tr>';
                echo '<td>' . fullname($student) . '</td>';
                echo '<td>' . ($student->international ? get_string('yes') : get_string('no')) . '</td>';
                echo '<td>' . s($student->field) . '</td>';
                echo '<td>' . s($student->major) . '</td>';
                echo '<td>' . s($student->selfintro) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';
        }
    }

} else {
    // STUDENT VIEW
    $mform = new mod_attributegrouping_input_form(null, array('id' => $id, 'attributegrouping' => $attributegrouping));

    // Check for existing data
    $record = $DB->get_record('attributegrouping_entries', array('userid' => $USER->id, 'attributegroupingid' => $attributegrouping->id));

    if ($mform->is_cancelled()) {
        // Do nothing or redirect
    } else if ($data = $mform->get_data()) {
        $entry = new stdClass();
        $entry->attributegroupingid = $attributegrouping->id;
        $entry->userid = $USER->id;
        $entry->international = $data->international;
        $entry->major = $data->major;
        $entry->field = $data->field;
        $entry->selfintro = $data->selfintro;
        $entry->timemodified = time();

        if ($record) {
            $entry->id = $record->id;
            $DB->update_record('attributegrouping_entries', $entry);
        } else {
            $entry->timecreated = time();
            $DB->insert_record('attributegrouping_entries', $entry);
        }
        echo $OUTPUT->notification(get_string('saved', 'mod_attributegrouping'), 'success');
        $record = $entry; // Update local record for display
    }

    if ($record) {
        $mform->set_data($record);
    }
    $mform->set_data(array('id' => $id));

    $mform->display();
}

echo $OUTPUT->footer();
