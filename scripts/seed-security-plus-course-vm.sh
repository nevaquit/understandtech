#!/usr/bin/env bash
# Seed Security+ SY0-701 course on production Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

ut_log() {
  echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] $*"
}

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

ut_www_data_php() {
  sudo -u www-data env SEC701_COURSE_ID="${SEC701_COURSE_ID}" php "$@"
}

if [ -f /tmp/ut-seed-skip-cleanup-gha ]; then
  export SKIP_CLEANUP=1
fi

ut_log "seed_start course_id=${SEC701_COURSE_ID} skip_cleanup=${SKIP_CLEANUP:-0}"

if [ -d "${REPO}/.git" ]; then
  ut_log "git_sync"
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

_is_staging=0
if [ "${SEC701_COURSE_ID}" = "2" ]; then
  _is_staging=1
elif [ -f /var/www/moodle/config.php ]; then
  _wwwroot="$(/usr/bin/php -r 'define("CLI_SCRIPT", true); require "/var/www/moodle/config.php"; echo $CFG->wwwroot;' 2>/dev/null || true)"
  if [[ "$_wwwroot" == *staging* ]]; then
    _is_staging=1
  fi
fi

if [ "${SKIP_VISUAL_UPGRADE:-0}" != "1" ] && [ "${_is_staging}" -eq 0 ]; then
  if [ -f "${REPO}/scripts/upgrade-all-lesson-visuals.php" ]; then
    ut_log "upgrade_all_lesson_visuals"
    php "${REPO}/scripts/upgrade-all-lesson-visuals.php" || true
  fi
  if [ -f "${REPO}/scripts/insert-missing-lesson-diagrams.php" ]; then
    ut_log "insert_missing_lesson_diagrams"
    php "${REPO}/scripts/insert-missing-lesson-diagrams.php" || true
  fi
  if [ -f "${REPO}/scripts/ensure-lesson-visual-headings.php" ]; then
    ut_log "ensure_lesson_visual_headings"
    php "${REPO}/scripts/ensure-lesson-visual-headings.php" || true
  fi
  if [ -f "${REPO}/scripts/add-flow-arrows.php" ]; then
    ut_log "add_flow_arrows"
    php "${REPO}/scripts/add-flow-arrows.php" || true
  fi
  if [ -f "${REPO}/scripts/inline-lesson-visuals.php" ]; then
    ut_log "inline_lesson_visuals"
    php "${REPO}/scripts/inline-lesson-visuals.php" || true
  fi
else
  ut_log "visual_upgrades_skipped staging=${_is_staging} skip_flag=${SKIP_VISUAL_UPGRADE:-0}"
fi

if [ "${SKIP_CLEANUP:-0}" != "1" ]; then
  ut_log "cleanup_duplicate_pages course_id=${SEC701_COURSE_ID}"
  ut_www_data_php "${REPO}/scripts/cleanup-sec701-duplicate-pages.php"
  ut_log "cleanup_duplicate_questions"
  ut_www_data_php "${REPO}/scripts/cleanup-sec701-duplicate-questions.php"
else
  ut_log "cleanup_skipped"
fi

ut_log "seed_security_plus_course_php"
ut_www_data_php "${REPO}/scripts/seed-security-plus-course.php"
echo 'seed_purge_caches_deferred=1'
ut_log "seed_security_plus_complete"
echo 'seed_security_plus_complete=1'
