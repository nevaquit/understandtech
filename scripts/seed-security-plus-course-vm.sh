#!/usr/bin/env bash
# Seed Security+ SY0-701 course on production Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -z "${SEC701_COURSE_ID:-}" ] && [ -f /var/www/moodle/config.php ]; then
  _wwwroot="$(/usr/bin/php -r 'define("CLI_SCRIPT", true); require "/var/www/moodle/config.php"; echo $CFG->wwwroot;' 2>/dev/null || true)"
  if [[ "$_wwwroot" == *staging* ]]; then
    SEC701_COURSE_ID=2
  fi
fi
export SEC701_COURSE_ID="${SEC701_COURSE_ID:-3}"
if [ -f /tmp/ut-seed-skip-cleanup-gha ]; then
  export SKIP_CLEANUP=1
fi

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

if [ -f "${REPO}/scripts/upgrade-all-lesson-visuals.php" ]; then
  php "${REPO}/scripts/upgrade-all-lesson-visuals.php" || true
fi
if [ -f "${REPO}/scripts/insert-missing-lesson-diagrams.php" ]; then
  php "${REPO}/scripts/insert-missing-lesson-diagrams.php" || true
fi
if [ -f "${REPO}/scripts/ensure-lesson-visual-headings.php" ]; then
  php "${REPO}/scripts/ensure-lesson-visual-headings.php" || true
fi
if [ -f "${REPO}/scripts/add-flow-arrows.php" ]; then
  php "${REPO}/scripts/add-flow-arrows.php" || true
fi
if [ -f "${REPO}/scripts/inline-lesson-visuals.php" ]; then
  php "${REPO}/scripts/inline-lesson-visuals.php" || true
fi
if [ "${SKIP_CLEANUP:-0}" != "1" ]; then
  sudo -u www-data php "${REPO}/scripts/cleanup-sec701-duplicate-pages.php"
  sudo -u www-data php "${REPO}/scripts/cleanup-sec701-duplicate-questions.php"
fi
sudo -u www-data php "${REPO}/scripts/seed-security-plus-course.php"
sudo -u www-data php "${REPO}/scripts/fix-sec701-course-filters.php"
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
echo 'seed_security_plus_complete=1'
