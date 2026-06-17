#!/usr/bin/env bash
# Strict health check for NET009 lesson pages (course id 5, N10009.1.2 cmid).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

NET009_CMID="$(sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global \$DB;
\$courseid = (int) \$DB->get_field('course', 'id', ['shortname' => 'NET009'], MUST_EXIST);
\$cmid = \$DB->get_field_sql(
    'SELECT cm.id FROM {course_modules} cm
       JOIN {modules} m ON m.id = cm.module AND m.name = ?
       JOIN {page} p ON p.id = cm.instance
      WHERE cm.course = ? AND cm.deletioninprogress = 0 AND p.name LIKE ?
      ORDER BY cm.id ASC',
    ['page', \$courseid, 'N10-009 1.2:%'],
    IGNORE_MISSING
);
if (!\$cmid) {
    \$cmid = \$DB->get_field_sql(
        'SELECT cm.id FROM {course_modules} cm
           JOIN {modules} m ON m.id = cm.module AND m.name = ?
           JOIN {page} p ON p.id = cm.instance
          WHERE cm.course = ? AND cm.deletioninprogress = 0 AND p.name LIKE ?
          ORDER BY cm.id ASC',
        ['page', \$courseid, 'N10009.1.2:%'],
        IGNORE_MISSING
    );
}
echo (int) (\$cmid ?: 0);
")"

if [ "${NET009_CMID}" -le 0 ]; then
  echo "net009_page_cmid_missing"
  exit 1
fi

export VERIFY_COURSE_ID=5
export PAGE_CMID="${NET009_CMID}"
bash "${REPO}/scripts/verify-moodle-web-health.sh"
