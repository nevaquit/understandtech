#!/usr/bin/env bash
# Fix NET009 mod/page filter MUC errors and verify lesson rendering.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "${REPO}" fetch origin main
  sudo -u gha-runner git -C "${REPO}" reset --hard origin/main
fi

bash "${REPO}/scripts/recover-origin-db.sh" || true
sudo -u www-data php "${REPO}/scripts/fix-net009-course-filters.php"

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

if [ "${NET009_CMID}" -gt 0 ]; then
  echo "=== diagnose NET009 page cmid=${NET009_CMID} ==="
  sudo -u www-data php "${REPO}/scripts/diagnose-page-cmid.php" "${NET009_CMID}" 2>&1 | grep -E 'format_text_final|filter_fail|page_name|db_error' || true
  sudo /usr/bin/bash "${REPO}/scripts/fix-page-filter-cache-vm.sh" "${NET009_CMID}" || true
else
  echo "net009_page_cmid_missing=1"
fi

bash "${REPO}/scripts/restart-php-fpm-vm.sh"

echo 'fix_net009_course_filters_complete=1'
