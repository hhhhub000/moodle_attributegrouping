<?php
/**
 * Module settings form.
 *
 * @package    mod_attributegrouping
 * @copyright  2024 飛田北斗 <hokutoh@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_attributegrouping_mod_form extends moodleform_mod
{

    function definition()
    {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'field_options', get_string('field_options', 'mod_attributegrouping'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('field_options', PARAM_TEXT);
        $mform->addHelpButton('field_options', 'field_options', 'mod_attributegrouping');

        $mform->addElement('textarea', 'major_options', get_string('major_options', 'mod_attributegrouping'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('major_options', PARAM_TEXT);
        $mform->addHelpButton('major_options', 'major_options', 'mod_attributegrouping');

        $this->standard_intro_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
