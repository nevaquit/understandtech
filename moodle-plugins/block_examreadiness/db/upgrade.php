<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_examreadiness_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026060801) {
        upgrade_plugin_savepoint(true, 2026060801, 'block', 'examreadiness');
    }

    return true;
}
