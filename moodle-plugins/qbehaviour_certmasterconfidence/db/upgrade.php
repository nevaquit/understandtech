<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_qbehaviour_certmasterconfidence_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026061800) {
        upgrade_plugin_savepoint(true, 2026061800, 'qbehaviour', 'certmasterconfidence');
    }

    return true;
}
