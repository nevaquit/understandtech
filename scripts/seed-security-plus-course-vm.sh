#!/usr/bin/env bash
# Seed Security+ SY0-701 course on production Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

sudo -u www-data php "${REPO}/scripts/cleanup-sec701-duplicate-pages.php"
sudo -u www-data php "${REPO}/scripts/cleanup-sec701-duplicate-questions.php"
sudo -u www-data php "${REPO}/scripts/seed-security-plus-course.php"
sudo -u www-data php "${REPO}/scripts/fix-sec701-course-filters.php"
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
echo 'seed_security_plus_complete=1'
