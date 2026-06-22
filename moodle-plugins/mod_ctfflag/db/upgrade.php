<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for mod_ctfflag.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_ctfflag_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060800) {
        $table = new xmldb_table('ctfflag');

        $field = new xmldb_field('expected_flag_regex', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'UT{.*}');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('xp_award', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('completion_required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $submissionstable = new xmldb_table('ctfflag_submissions');
        $submissionstable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $submissionstable->add_field('ctfflagid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $submissionstable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $submissionstable->add_field('success', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $submissionstable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $submissionstable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $submissionstable->add_key('ctfflagid', XMLDB_KEY_FOREIGN, ['ctfflagid'], 'ctfflag', ['id']);
        $submissionstable->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $submissionstable->add_index('ctfflag_user', XMLDB_INDEX_NOTUNIQUE, ['ctfflagid', 'userid']);

        if (!$dbman->table_exists($submissionstable)) {
            $dbman->create_table($submissionstable);
        }

        upgrade_mod_savepoint(true, 2026060800, 'ctfflag');
    }

    if ($oldversion < 2026062202) {
        upgrade_mod_savepoint(true, 2026062202, 'ctfflag');
    }

    return true;
}
