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
 * Calculate groups based on diversity.
 *
 * @param int $attributegroupingid The instance ID.
 * @param int $target_size
 * @return array Array of groups, each containing an array of student objects.
 */
function mod_attributegrouping_calculate_groups($attributegroupingid, $target_size)
{
    global $DB;

    // Fetch all students with their attributes for this instance.
    // Sort priority: International DESC, Field, Major.
    $sql = "SELECT d.*, u.firstname, u.lastname, u.email
            FROM {attributegrouping_entries} d
            JOIN {user} u ON d.userid = u.id
            WHERE d.attributegroupingid = :instanceid
            ORDER BY d.international DESC, d.field, d.major";

    $students = $DB->get_records_sql($sql, array('instanceid' => $attributegroupingid));

    if (empty($students)) {
        return array();
    }

    $students = array_values($students); // Re-index array.
    $total_students = count($students);
    $num_groups = ceil($total_students / $target_size);

    $groups = array_fill(0, $num_groups, array());

    // Round-robin distribution.
    foreach ($students as $index => $student) {
        $group_index = $index % $num_groups;
        $groups[$group_index][] = $student;
    }

    return $groups;
}
