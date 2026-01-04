<?php
/**
 * Upgrade script for mod_attributegrouping.
 *
 * @package    mod_attributegrouping
 * @copyright  2024 飛田北斗 <hokutoh@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the mod_attributegrouping module.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Success.
 */
function xmldb_attributegrouping_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024112204) {

        // Define field field_options to be added to attributegrouping.
        $table = new xmldb_table('attributegrouping');
        $field = new xmldb_field('field_options', XMLDB_TYPE_TEXT, null, null, null, null, null, 'introformat');

        // Conditionally launch add field field_options.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field major_options to be added to attributegrouping.
        $field = new xmldb_field('major_options', XMLDB_TYPE_TEXT, null, null, null, null, null, 'field_options');

        // Conditionally launch add field major_options.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table attributegrouping_entries to be modified.
        $table = new xmldb_table('attributegrouping_entries');

        // Add field international.
        $field = new xmldb_field('international', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field selfintro.
        $field = new xmldb_field('selfintro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'field');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Drop field year.
        $field = new xmldb_field('year');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Drop field interest.
        $field = new xmldb_field('interest');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Attributegrouping savepoint reached.
        upgrade_mod_savepoint(true, 2024112204, 'attributegrouping');
    }

    return true;
}
