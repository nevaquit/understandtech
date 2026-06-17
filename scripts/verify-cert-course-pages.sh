#!/usr/bin/env bash
# Verify mod/page lesson rendering for all certification courses (no DB error, ut-lesson-content).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

readarray -t CHECKS < <(sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global \$DB;

\$specs = [
    ['SEC701', 'sy701_1_1', ['SY701.1.1:%', 'SY0-701 1.1:%']],
    ['NET009', 'n10009_1_2', ['N10-009 1.2:%', 'N10009.1.2:%']],
    ['APLUS', 'ap1101_1_1', ['220-1101 1.1:%', 'AP1101.1.1:%']],
];

foreach (\$specs as \$spec) {
    [\$cshort, \$obj, \$patterns] = \$spec;
    if (!\$DB->record_exists('course', ['shortname' => \$cshort])) {
        continue;
    }
    \$courseid = (int) \$DB->get_field('course', 'id', ['shortname' => \$cshort]);
    \$cmid = 0;
    foreach (\$patterns as \$like) {
        \$cmid = (int) \$DB->get_field_sql(
            'SELECT cm.id FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module AND m.name = ?
               JOIN {page} p ON p.id = cm.instance
              WHERE cm.course = ? AND cm.deletioninprogress = 0 AND p.name LIKE ?
              ORDER BY cm.id ASC',
            ['page', \$courseid, \$like],
            IGNORE_MISSING
        );
        if (\$cmid > 0) {
            break;
        }
    }
    if (\$cmid > 0) {
        echo \$courseid . '|' . \$cmid . '|' . \$cshort . PHP_EOL;
    }
}
")

if [ "${#CHECKS[@]}" -eq 0 ]; then
  echo "verify_cert_pages_skipped=no_courses"
  exit 0
fi

for entry in "${CHECKS[@]}"; do
  IFS='|' read -r courseid cmid label <<< "${entry}"
  echo "=== verify ${label} course=${courseid} cmid=${cmid} ==="
  export VERIFY_COURSE_ID="${courseid}"
  export PAGE_CMID="${cmid}"
  bash "${REPO}/scripts/verify-moodle-web-health.sh"
done

echo "verify_cert_course_pages_ok=1"
