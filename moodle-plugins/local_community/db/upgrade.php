<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for local_community.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_community_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026061800) {
        upgrade_plugin_savepoint(true, 2026061800, 'local', 'community');
    }

    return true;
}
