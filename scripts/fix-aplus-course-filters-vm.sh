#!/usr/bin/env bash
# Fix APLUS mod/page filter MUC errors and recycle PHP-FPM.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "${REPO}" fetch origin main
  sudo -u gha-runner git -C "${REPO}" reset --hard origin/main
fi

bash "${REPO}/scripts/recover-origin-db.sh" || true
sudo -u www-data php "${REPO}/scripts/fix-aplus-course-filters.php"
bash "${REPO}/scripts/restart-php-fpm-vm.sh"
echo 'fix_aplus_course_filters_complete=1'
