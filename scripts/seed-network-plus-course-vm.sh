#!/usr/bin/env bash
# Seed CompTIA Network+ N10-009 course on Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -f /tmp/ut-net009-course-id-gha ]; then
  NET009_COURSE_ID="$(tr -d '[:space:]' < /tmp/ut-net009-course-id-gha)"
fi
if [ -z "${NET009_COURSE_ID:-}" ] && [ -f /var/www/moodle/config.php ]; then
  _wwwroot="$(/usr/bin/php -r 'define("CLI_SCRIPT", true); require "/var/www/moodle/config.php"; echo $CFG->wwwroot;' 2>/dev/null || true)"
  if [[ "$_wwwroot" == *staging* ]]; then
    NET009_COURSE_ID=2
  fi
fi
export NET009_COURSE_ID="${NET009_COURSE_ID:-3}"

ut_www_data_php() {
  sudo -u www-data env NET009_COURSE_ID="${NET009_COURSE_ID}" php "$@"
}

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

if [ "${SKIP_VISUAL_UPGRADE:-0}" != "1" ]; then
  if [ -f "${REPO}/scripts/upgrade-network-plus-lesson-visuals.php" ]; then
    php "${REPO}/scripts/upgrade-network-plus-lesson-visuals.php" || true
  fi
fi

/usr/bin/pkill -f 'seed-network-plus-course.php' 2>/dev/null || true
sleep 2
echo 'stale_net009_seed_cleared=1'

ut_www_data_php "${REPO}/scripts/seed-network-plus-course.php"
echo 'seed_purge_caches_deferred=1'
echo 'seed_net009_complete=1'
