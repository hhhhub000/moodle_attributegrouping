<?php
/**
 * Library functions for mod_attributegrouping.
 *
 * @package    mod_attributegrouping
 * @copyright  2024 飛田北斗 <hokutoh@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Supports.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function attributegrouping_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Adds a new instance of attributegrouping.
 *
 * @param stdClass $attributegrouping Object containing the add form data
 * @param mod_attributegrouping_mod_form $mform
 * @return int The new instance id
 */
function attributegrouping_add_instance($attributegrouping, $mform = null)
{
    global $DB;

    $attributegrouping->timecreated = time();
    $attributegrouping->timemodified = time();

    return $DB->insert_record('attributegrouping', $attributegrouping);
}

/**
 * Updates an instance of attributegrouping.
 *
 * @param stdClass $attributegrouping Object containing the update form data
 * @param mod_attributegrouping_mod_form $mform
 * @return bool True on success
 */
function attributegrouping_update_instance($attributegrouping, $mform = null)
{
    global $DB;

    $attributegrouping->timemodified = time();
    $attributegrouping->id = $attributegrouping->instance;

    return $DB->update_record('attributegrouping', $attributegrouping);
}

/**
 * Deletes an instance of attributegrouping.
 *
 * @param int $id The instance id
 * @return bool True on success
 */
function attributegrouping_delete_instance($id)
{
    global $DB;

    if (!$attributegrouping = $DB->get_record('attributegrouping', array('id' => $id))) {
        return false;
    }

    // Delete related data.
    $DB->delete_records('attributegrouping_entries', array('attributegroupingid' => $attributegrouping->id));

    $DB->delete_records('attributegrouping', array('id' => $attributegrouping->id));

    return true;
}
