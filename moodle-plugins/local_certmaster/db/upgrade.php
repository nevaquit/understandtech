<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_certmaster_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060801) {
        local_certmaster_seed_certification('network_plus_n10_009', 'CompTIA Network+ N10-009', 'N10-009', [
            ['shortname' => 'network_fundamentals', 'fullname' => 'Networking Fundamentals', 'weight' => 24.00, 'sortorder' => 1],
            ['shortname' => 'network_impl', 'fullname' => 'Network Implementations', 'weight' => 19.00, 'sortorder' => 2],
            ['shortname' => 'network_ops', 'fullname' => 'Network Operations', 'weight' => 22.00, 'sortorder' => 3],
            ['shortname' => 'network_security', 'fullname' => 'Network Security', 'weight' => 19.00, 'sortorder' => 4],
            ['shortname' => 'network_troubleshoot', 'fullname' => 'Network Troubleshooting', 'weight' => 16.00, 'sortorder' => 5],
        ]);

        local_certmaster_seed_certification('aplus_core1', 'CompTIA A+ Core 1 (220-1101)', '220-1101', [
            ['shortname' => 'mobile_devices', 'fullname' => 'Mobile Devices', 'weight' => 15.00, 'sortorder' => 1],
            ['shortname' => 'networking', 'fullname' => 'Networking', 'weight' => 20.00, 'sortorder' => 2],
            ['shortname' => 'hardware', 'fullname' => 'Hardware', 'weight' => 25.00, 'sortorder' => 3],
            ['shortname' => 'virtualization', 'fullname' => 'Virtualization and Cloud', 'weight' => 11.00, 'sortorder' => 4],
            ['shortname' => 'troubleshooting', 'fullname' => 'Hardware and Network Troubleshooting', 'weight' => 29.00, 'sortorder' => 5],
        ]);

        upgrade_plugin_savepoint(true, 2026060801, 'local', 'certmaster');
    }

    if ($oldversion < 2026060802) {
        $table = new xmldb_table('certmaster_study_plans');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('certificationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('weakobjectives', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('planjson', XMLDB_TYPE_TEXT, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('user_cert', XMLDB_INDEX_NOTUNIQUE, ['userid', 'certificationid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026060802, 'local', 'certmaster');
    }

    if ($oldversion < 2026060803) {
        $cert = $DB->get_record('certmaster_certifications', ['shortname' => 'security_plus_sy0_701']);
        if ($cert) {
            $weights = [
                'general_concepts' => 12.00,
                'threats_vulns' => 22.00,
                'security_architecture' => 18.00,
                'security_operations' => 28.00,
                'program_management' => 20.00,
            ];
            foreach ($weights as $shortname => $weight) {
                $DB->set_field('certmaster_domains', 'blueprint_weight', $weight, [
                    'certificationid' => $cert->id,
                    'shortname' => $shortname,
                ]);
            }
        }
        upgrade_plugin_savepoint(true, 2026060803, 'local', 'certmaster');
    }

    if ($oldversion < 2026061500) {
        local_certmaster_seed_certification('comptia_a_plus', 'CompTIA A+ certification', '220-1101 / 220-1102', [
            ['shortname' => 'mobile_devices', 'fullname' => 'Mobile Devices (Core 1)', 'weight' => 6.50, 'sortorder' => 1],
            ['shortname' => 'networking', 'fullname' => 'Networking (Core 1)', 'weight' => 11.50, 'sortorder' => 2],
            ['shortname' => 'hardware', 'fullname' => 'Hardware (Core 1)', 'weight' => 12.50, 'sortorder' => 3],
            ['shortname' => 'virtualization', 'fullname' => 'Virtualization and Cloud Computing (Core 1)', 'weight' => 5.50, 'sortorder' => 4],
            ['shortname' => 'hw_net_troubleshooting', 'fullname' => 'Hardware and Network Troubleshooting (Core 1)', 'weight' => 14.00, 'sortorder' => 5],
            ['shortname' => 'operating_systems', 'fullname' => 'Operating Systems (Core 2)', 'weight' => 15.50, 'sortorder' => 6],
            ['shortname' => 'security', 'fullname' => 'Security (Core 2)', 'weight' => 12.50, 'sortorder' => 7],
            ['shortname' => 'software_troubleshooting', 'fullname' => 'Software Troubleshooting (Core 2)', 'weight' => 11.00, 'sortorder' => 8],
            ['shortname' => 'operational_procedures', 'fullname' => 'Operational Procedures (Core 2)', 'weight' => 11.00, 'sortorder' => 9],
        ]);

        upgrade_plugin_savepoint(true, 2026061500, 'local', 'certmaster');
    }

    if ($oldversion < 2026061600) {
        $cert = $DB->get_record('certmaster_certifications', ['shortname' => 'network_plus_n10_009']);
        if ($cert) {
            $weights = [
                'network_fundamentals' => 23.00,
                'network_impl' => 20.00,
                'network_ops' => 19.00,
                'network_security' => 14.00,
                'network_troubleshoot' => 24.00,
            ];
            foreach ($weights as $shortname => $weight) {
                $DB->set_field('certmaster_domains', 'blueprint_weight', $weight, [
                    'certificationid' => $cert->id,
                    'shortname' => $shortname,
                ]);
            }
        }
        upgrade_plugin_savepoint(true, 2026061600, 'local', 'certmaster');
    }

    if ($oldversion < 2026061800) {
        upgrade_plugin_savepoint(true, 2026061800, 'local', 'certmaster');
    }

    return true;
}

/**
 * @param string $shortname
 * @param string $fullname
 * @param string $examcode
 * @param array $domains
 * @return void
 */
function local_certmaster_seed_certification(string $shortname, string $fullname, string $examcode, array $domains): void {
    global $DB;

    if ($DB->record_exists('certmaster_certifications', ['shortname' => $shortname])) {
        return;
    }

    $now = time();
    $certid = $DB->insert_record('certmaster_certifications', (object) [
        'shortname' => $shortname,
        'fullname' => $fullname,
        'exam_code' => $examcode,
        'timecreated' => $now,
        'timemodified' => $now,
    ]);

    foreach ($domains as $domain) {
        $DB->insert_record('certmaster_domains', (object) [
            'certificationid' => $certid,
            'shortname' => $domain['shortname'],
            'fullname' => $domain['fullname'],
            'blueprint_weight' => $domain['weight'],
            'sortorder' => $domain['sortorder'],
        ]);
    }
}
