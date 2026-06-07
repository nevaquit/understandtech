<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_certmaster;

defined('MOODLE_INTERNAL') || die();

/**
 * CSV objective import: cert_shortname,domain_shortname,objective_shortname,objective_fullname
 */
class csv_importer {

    /**
     * @param string $csvcontent
     * @return int Rows imported.
     */
    public static function import_from_csv(string $csvcontent): int {
        global $DB;

        $lines = preg_split('/\r\n|\r|\n/', trim($csvcontent)) ?: [];
        $imported = 0;

        foreach ($lines as $i => $line) {
            if ($i === 0 && stripos($line, 'cert_shortname') !== false) {
                continue;
            }
            if (trim($line) === '') {
                continue;
            }

            $cols = str_getcsv($line);
            if (count($cols) < 4) {
                continue;
            }

            [$certshort, $domainshort, $objshort, $objfull] = array_map('trim', $cols);
            $cert = $DB->get_record('certmaster_certifications', ['shortname' => $certshort]);
            if (!$cert) {
                continue;
            }

            $domain = $DB->get_record('certmaster_domains', [
                'certificationid' => $cert->id,
                'shortname' => $domainshort,
            ]);
            if (!$domain) {
                continue;
            }

            if ($DB->record_exists('certmaster_objectives', ['domainid' => $domain->id, 'shortname' => $objshort])) {
                continue;
            }

            $DB->insert_record('certmaster_objectives', (object) [
                'domainid' => $domain->id,
                'shortname' => $objshort,
                'fullname' => $objfull,
                'sortorder' => 0,
            ]);
            $imported++;
        }

        return $imported;
    }
}
