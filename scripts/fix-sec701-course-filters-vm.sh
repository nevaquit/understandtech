#!/usr/bin/env bash
set -euo pipefail

REPO="${REPO:-/opt/understandtech-plugins}"

sudo -u gha-runner git -C "${REPO}" fetch origin main
sudo -u gha-runner git -C "${REPO}" reset --hard origin/main

bash "${REPO}/scripts/recover-origin-db.sh" || true
sudo -u www-data php "${REPO}/scripts/fix-sec701-course-filters.php"
sudo -u www-data php "${REPO}/scripts/diagnose-course-web.php" 3 admin

echo 'fix_sec701_course_filters_complete=1'
