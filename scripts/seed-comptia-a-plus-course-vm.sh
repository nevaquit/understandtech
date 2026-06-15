#!/usr/bin/env bash
# Seed CompTIA A+ certification course on Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

sudo -u www-data php "${REPO}/scripts/seed-comptia-a-plus-course.php"
sudo -u www-data php "${REPO}/scripts/fix-aplus-course-filters.php"
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
echo 'seed_aplus_complete=1'
