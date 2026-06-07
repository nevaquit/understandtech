<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Seed Security+ SY0-701 certification framework domains.
 *
 * @return bool
 */
function xmldb_local_certmaster_install(): bool {
    global $DB;

    $now = time();
    $certid = $DB->insert_record('certmaster_certifications', (object) [
        'shortname' => 'security_plus_sy0_701',
        'fullname' => 'CompTIA Security+ SY0-701',
        'exam_code' => 'SY0-701',
        'timecreated' => $now,
        'timemodified' => $now,
    ]);

    $domains = [
        ['shortname' => 'general_concepts', 'fullname' => 'General Security Concepts', 'weight' => 12.00, 'sortorder' => 1],
        ['shortname' => 'threats_vulns', 'fullname' => 'Threats, Vulnerabilities, and Mitigations', 'weight' => 22.00, 'sortorder' => 2],
        ['shortname' => 'security_architecture', 'fullname' => 'Security Architecture', 'weight' => 18.00, 'sortorder' => 3],
        ['shortname' => 'security_operations', 'fullname' => 'Security Operations', 'weight' => 28.00, 'sortorder' => 4],
        ['shortname' => 'program_management', 'fullname' => 'Security Program Management and Oversight', 'weight' => 20.00, 'sortorder' => 5],
    ];

    foreach ($domains as $domain) {
        $DB->insert_record('certmaster_domains', (object) [
            'certificationid' => $certid,
            'shortname' => $domain['shortname'],
            'fullname' => $domain['fullname'],
            'blueprint_weight' => $domain['weight'],
            'sortorder' => $domain['sortorder'],
        ]);
    }

    return true;
}
