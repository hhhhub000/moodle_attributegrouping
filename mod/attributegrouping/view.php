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

// Handle CSV export request.
$exportcsv = optional_param('exportcsv', 0, PARAM_INT);
if ($exportcsv && has_capability('mod/attributegrouping:manage', $context)) {
    $groupsize = optional_param('groupsize', 4, PARAM_INT);
    $groups = mod_attributegrouping_calculate_groups($attributegrouping->id, $groupsize);
    
    if (!empty($groups)) {
        // Set headers for CSV download.
        $filename = clean_filename($attributegrouping->name . '_groups_' . date('Ymd_His') . '.csv');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output BOM for Excel compatibility.
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Write CSV header.
        fputcsv($output, array(
            get_string('group_number', 'mod_attributegrouping'),
            get_string('name'),
            get_string('email'),
            get_string('international', 'mod_attributegrouping'),
            get_string('field', 'mod_attributegrouping'),
            get_string('major', 'mod_attributegrouping'),
            get_string('selfintro', 'mod_attributegrouping')
        ));
        
        // Write data rows.
        foreach ($groups as $i => $group) {
            foreach ($group as $student) {
                fputcsv($output, array(
                    ($i + 1),
                    fullname($student),
                    $student->email,
                    ($student->international ? get_string('yes') : get_string('no')),
                    $student->field,
                    $student->major,
                    $student->selfintro
                ));
            }
        }
        
        fclose($output);
        exit;
    }
}

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

    echo '<form method="get" action="view.php" class="form-inline" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">';
    echo '<input type="hidden" name="id" value="' . $id . '">';
    echo '<label for="groupsize">' . get_string('group_size', 'mod_attributegrouping') . ': </label>';
    echo '<input type="number" name="groupsize" id="groupsize" value="' . $groupsize . '" min="1" class="form-control" style="width: auto;">';
    echo '<input type="submit" value="' . get_string('calculate', 'mod_attributegrouping') . '" class="btn btn-primary">';
    echo '<input type="submit" name="exportcsv" value="1" style="display:none;" id="exportcsv_hidden">';
    echo '<button type="button" onclick="exportCSV()" class="btn btn-secondary">' . get_string('export_csv', 'mod_attributegrouping') . '</button>';
    echo '</form>';
    echo '<script>
    function exportCSV() {
        var form = document.querySelector("form.form-inline");
        var input = document.createElement("input");
        input.type = "hidden";
        input.name = "exportcsv";
        input.value = "1";
        form.appendChild(input);
        form.submit();
        form.removeChild(input);
    }
    </script>';
    echo '<br>';

    $groups = mod_attributegrouping_calculate_groups($attributegrouping->id, $groupsize);

    if (empty($groups)) {
        echo $OUTPUT->notification(get_string('no_data', 'mod_attributegrouping'), 'warning');
    } else {
        // Define color palettes for attributes.
        // International: 2 colors (No=red, Yes=blue).
        $international_colors = array(
            0 => array('bg' => '#f8d7da', 'text' => '#842029'),  // No - Red
            1 => array('bg' => '#cfe2ff', 'text' => '#084298'),  // Yes - Blue
        );

        // Field and Major: 12 distinct colors for variety.
        $attribute_colors = array(
            array('bg' => '#d1e7dd', 'text' => '#0f5132'),  // Green
            array('bg' => '#fff3cd', 'text' => '#664d03'),  // Yellow
            array('bg' => '#f8d7da', 'text' => '#842029'),  // Red
            array('bg' => '#cff4fc', 'text' => '#055160'),  // Cyan
            array('bg' => '#e2d9f3', 'text' => '#432874'),  // Purple
            array('bg' => '#ffe5d0', 'text' => '#984c0c'),  // Orange
            array('bg' => '#d3d3d3', 'text' => '#333333'),  // Gray
            array('bg' => '#fce4ec', 'text' => '#880e4f'),  // Pink
            array('bg' => '#e8f5e9', 'text' => '#1b5e20'),  // Light Green
            array('bg' => '#e3f2fd', 'text' => '#0d47a1'),  // Light Blue
            array('bg' => '#fff8e1', 'text' => '#ff6f00'),  // Amber
            array('bg' => '#f3e5f5', 'text' => '#7b1fa2'),  // Light Purple
        );

        // Build color maps for Field and Major values.
        $field_values = array();
        $major_values = array();
        foreach ($groups as $group) {
            foreach ($group as $student) {
                if (!in_array($student->field, $field_values)) {
                    $field_values[] = $student->field;
                }
                if (!in_array($student->major, $major_values)) {
                    $major_values[] = $student->major;
                }
            }
        }
        sort($field_values);
        sort($major_values);

        $field_color_map = array();
        foreach ($field_values as $index => $value) {
            $field_color_map[$value] = $attribute_colors[$index % count($attribute_colors)];
        }

        $major_color_map = array();
        foreach ($major_values as $index => $value) {
            $major_color_map[$value] = $attribute_colors[$index % count($attribute_colors)];
        }

        // Output CSS for attribute badges.
        echo '<style>
        .attr-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.875em;
            font-weight: 500;
        }
        </style>';

        foreach ($groups as $i => $group) {
            echo '<div class="card mb-3">';
            echo '<div class="card-header">Group ' . ($i + 1) . '</div>';
            echo '<div class="card-body">';
            echo '<table class="table table-sm">';
            echo '<thead><tr><th>' . get_string('name') . '</th><th>' . get_string('international', 'mod_attributegrouping') . '</th><th>' . get_string('field', 'mod_attributegrouping') . '</th><th>' . get_string('major', 'mod_attributegrouping') . '</th><th>' . get_string('selfintro', 'mod_attributegrouping') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($group as $student) {
                $int_color = $international_colors[$student->international ? 1 : 0];
                $field_color = $field_color_map[$student->field];
                $major_color = $major_color_map[$student->major];
                
                echo '<tr>';
                echo '<td>' . fullname($student) . '</td>';
                echo '<td><span class="attr-badge" style="background-color: ' . $int_color['bg'] . '; color: ' . $int_color['text'] . ';">' . ($student->international ? get_string('yes') : get_string('no')) . '</span></td>';
                echo '<td><span class="attr-badge" style="background-color: ' . $field_color['bg'] . '; color: ' . $field_color['text'] . ';">' . s($student->field) . '</span></td>';
                echo '<td><span class="attr-badge" style="background-color: ' . $major_color['bg'] . '; color: ' . $major_color['text'] . ';">' . s($student->major) . '</span></td>';
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
