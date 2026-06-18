#!/usr/bin/env bash
set -euo pipefail

REPO="${REPO:-/opt/understandtech-plugins}"

if [ -f /tmp/ut-sec701-course-id-gha ]; then
  SEC701_COURSE_ID="$(tr -d '[:space:]' < /tmp/ut-sec701-course-id-gha)"
fi
if [ -z "${SEC701_COURSE_ID:-}" ] && [ -f /var/www/moodle/config.php ]; then
  _wwwroot="$(/usr/bin/php -r 'define("CLI_SCRIPT", true); require "/var/www/moodle/config.php"; echo $CFG->wwwroot;' 2>/dev/null || true)"
  if [[ "$_wwwroot" == *staging* ]]; then
    SEC701_COURSE_ID=2
  fi
fi
export SEC701_COURSE_ID="${SEC701_COURSE_ID:-3}"

sudo -u gha-runner git -C "${REPO}" fetch origin main
sudo -u gha-runner git -C "${REPO}" reset --hard origin/main

bash "${REPO}/scripts/recover-origin-db.sh" || true
sudo -u www-data env SEC701_COURSE_ID="${SEC701_COURSE_ID}" php "${REPO}/scripts/fix-sec701-course-filters.php"
sudo -u www-data php "${REPO}/scripts/diagnose-course-web.php" "${SEC701_COURSE_ID}" admin

echo 'fix_sec701_course_filters_complete=1'
