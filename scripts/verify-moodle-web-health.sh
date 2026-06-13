#!/usr/bin/env bash
# Strict Moodle web health — fails on login exceptions, DB errors, or broken course pages.
# Used as a deploy gate and by origin-health monitoring.
# Retries absorb post-purge / PHP-FPM recycle races (e.g. timeline_fallback AMD not yet served).
#
# Environment (first match wins for origin host):
#   PROD_URL      — production default https://understandtech.app
#   STAGING_URL   — staging host, e.g. https://staging.understandtech.app
# On VM without env: origin host and SEC701 course id are inferred from config.php wwwroot.
set -euo pipefail

WWW="${MOODLE_WWWROOT_PATH:-/learn}"
E2E_USER="${MOODLE_E2E_USER:-e2etest}"
E2E_PASS="${MOODLE_E2E_PASS:-UtE2eTest2026Secure}"
VERIFY_RETRIES="${VERIFY_RETRIES:-5}"
VERIFY_RETRY_DELAY="${VERIFY_RETRY_DELAY:-6}"
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

PROD=""
COURSE_ID=""
if [ -f /var/www/moodle/config.php ]; then
  _wwwroot="$(/usr/bin/php -r 'define("CLI_SCRIPT", true); require "/var/www/moodle/config.php"; echo rtrim($CFG->wwwroot, "/");' 2>/dev/null || true)"
  if [ -n "$_wwwroot" ]; then
    PROD="${_wwwroot%$WWW}"
    PROD="${PROD%/}"
    if [[ "$_wwwroot" == *staging* ]]; then
      COURSE_ID=2
    else
      COURSE_ID=3
    fi
  fi
fi

if [ -n "${STAGING_URL:-}" ]; then
  PROD="${STAGING_URL%/learn}"
  PROD="${PROD%/}"
  COURSE_ID="${VERIFY_COURSE_ID:-2}"
elif [ -n "${PROD_URL:-}" ]; then
  PROD="${PROD_URL%/learn}"
  PROD="${PROD%/}"
  COURSE_ID="${VERIFY_COURSE_ID:-3}"
fi

PROD="${PROD:-https://understandtech.app}"
COURSE_ID="${VERIFY_COURSE_ID:-${COURSE_ID:-3}}"

CJ="/tmp/verify-moodle-cj-$$"
LOGIN="/tmp/verify-moodle-login-$$"
HOME="/tmp/verify-moodle-home-$$"
COURSE="/tmp/verify-moodle-course-$$"
PAGE="/tmp/verify-moodle-page-$$"
PAGE_CMID="${VERIFY_PAGE_CMID:-}"

cleanup() {
  rm -f "$CJ" "$LOGIN" "$HOME" "$COURSE" "$PAGE"
  if [ "${MAINT_WAS_ON:-0}" = 1 ] && [ "${HEALTH_OK:-0}" != 1 ]; then
    sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --enable 2>/dev/null || true
  fi
}
trap cleanup EXIT

lift_maintenance_for_probe() {
  MAINT_WAS_ON=0
  if ! command -v sudo >/dev/null 2>&1 || [ ! -f /var/www/moodle/config.php ]; then
    return 0
  fi
  if sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php 2>/dev/null | grep -qi 'enabled'; then
    MAINT_WAS_ON=1
    sudo -u www-data php /var/www/moodle/admin/cli/maintenance.php --disable
  fi
}

assert_no_fatal_html() {
  local label="$1"
  local file="$2"
  if grep -qiE 'Exception -|Fatal error|Parse error|Call to undefined method|Error reading from database' "$file"; then
    echo "fatal_html label=${label}"
    grep -oiE 'Exception -[^<]{0,120}|Fatal error[^<]{0,120}|Call to undefined method[^<]{0,120}|Error reading from database' "$file" | head -3
    return 1
  fi
  return 0
}

has_timeline_fallback() {
  # AMD module id in page footer (not present on guest login page).
  grep -qE 'theme_understandtech/timeline_fallback|timeline_fallback' "$1"
}

reset_session() {
  rm -f "$CJ"
}

fetch_guest_login() {
  curl -sS -b "$CJ" -c "$CJ" "${PROD}${WWW}/login/index.php" -o "$LOGIN"
}

run_checks() {
  reset_session

  echo "=== guest login ==="
  fetch_guest_login
  assert_no_fatal_html "guest_login" "$LOGIN"

  if ! grep -q 'name="logintoken"' "$LOGIN"; then
    # Stale auth cookies from a prior attempt redirect away from the login form.
    reset_session
    sleep 2
    fetch_guest_login
    assert_no_fatal_html "guest_login_retry" "$LOGIN"
  fi

  if ! grep -q 'name="logintoken"' "$LOGIN"; then
    echo "login_token_missing"
    grep -o '<title>[^<]*</title>' "$LOGIN" | head -1 || true
    return 1
  fi
  grep -o '<title>[^<]*</title>' "$LOGIN" | head -1 || true
  echo "guest_login_ok=1"

  echo "=== authenticated login ==="
  tok=$(grep -oP 'name="logintoken" value="\K[^"]+' "$LOGIN" | head -1 || true)
  curl -sS -b "$CJ" -c "$CJ" -L \
    --data-urlencode "username=${E2E_USER}" \
    --data-urlencode "password=${E2E_PASS}" \
    --data-urlencode "logintoken=${tok}" \
    "${PROD}${WWW}/login/index.php" -o /dev/null

  curl -sS -b "$CJ" -c "$CJ" -L "${PROD}${WWW}/my/" -o "$LOGIN"
  assert_no_fatal_html "auth_my" "$LOGIN"
  if ! has_timeline_fallback "$LOGIN"; then
    echo "timeline_fallback_missing (expected on /my/ after auth, not guest login)"
    return 1
  fi
  echo "auth_my_ok=1"

  echo "=== site home redirect=0 ==="
  curl -sS -b "$CJ" -c "$CJ" -L "${PROD}${WWW}/?redirect=0" -o "$HOME"
  assert_no_fatal_html "auth_home" "$HOME"
  if grep -qiE '<title>Error</title>' "$HOME"; then
    echo "home_bare_error_title"
    grep -o '<title>[^<]*</title>' "$HOME" | head -1 || true
    return 1
  fi
  if grep -qiE '<title>Error \|' "$HOME"; then
    echo "home_error_title"
    grep -o '<title>[^<]*</title>' "$HOME" | head -1 || true
    return 1
  fi
  if ! grep -qiE '<title>Home \|' "$HOME"; then
    echo "home_title_unexpected"
    grep -o '<title>[^<]*</title>' "$HOME" | head -1 || true
    return 1
  fi
  if ! grep -q 'ut-frontpage' "$HOME"; then
    echo "home_frontpage_markup_missing"
    return 1
  fi
  echo "auth_home_ok=1"

  echo "=== course view id=${COURSE_ID} ==="
  curl -sS -b "$CJ" -c "$CJ" -L \
    "${PROD}${WWW}/course/view.php?id=${COURSE_ID}" -o "$COURSE"
  assert_no_fatal_html "auth_course" "$COURSE"

  if ! grep -q 'templates_dom_patch' "$COURSE"; then
    echo "templates_dom_patch_missing"
    grep -o '<title>[^<]*</title>' "$COURSE" | head -1 || true
    echo "course_bytes=$(wc -c < "$COURSE" | tr -d ' ')"
    return 1
  fi

  # Server prerender or hydrated index must appear in page output (not skeleton-only).
  sectioncount=$(grep -o 'courseindex-section' "$COURSE" | wc -l | tr -d ' ')
  if [ "${sectioncount:-0}" -lt 1 ]; then
    echo "course_index_sections_count_low count=${sectioncount:-0}"
    return 1
  fi
  echo "course_index_sections=${sectioncount}"

  if grep -q 'course-index-placeholder' "$COURSE" && ! grep -q 'courseindex-section' "$COURSE"; then
    echo "course_index_skeleton_only"
    return 1
  fi

  if grep -qE 'id="course-index-placeholder"[^>]*class="[^"]*placeholders' "$COURSE" \
    && ! grep -q 'courseindex-item' "$COURSE"; then
    echo "course_index_placeholder_without_items"
    return 1
  fi

  if command -v sudo >/dev/null 2>&1 && [ -f /var/www/moodle/config.php ]; then
    sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
\$state = core_courseformat\external\get_state::execute(${COURSE_ID});
\$data = json_decode(\$state, true);
\$sections = is_array(\$data['section'] ?? null) ? count(\$data['section']) : 0;
if (\$sections < 1) { fwrite(STDERR, 'course_index_state_empty sections=' . \$sections . PHP_EOL); exit(1); }
echo 'course_index_state_sections=' . \$sections . PHP_EOL;
    "
  fi

  if [ -z "${PAGE_CMID}" ] && command -v sudo >/dev/null 2>&1 && [ -f /var/www/moodle/config.php ]; then
    PAGE_CMID="$(sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global \$DB;
\$cmid = \$DB->get_field_sql(
    'SELECT cm.id FROM {course_modules} cm JOIN {modules} m ON m.id = cm.module AND m.name = ? WHERE cm.course = ? AND cm.deletioninprogress = 0 ORDER BY cm.id ASC',
    ['page', ${COURSE_ID}],
    IGNORE_MISSING
);
echo (int) (\$cmid ?: 0);
" 2>/dev/null || true)"
  fi
  PAGE_CMID="${PAGE_CMID:-4}"

  echo "=== mod_page view id=${PAGE_CMID} ==="
  curl -sS -b "$CJ" -c "$CJ" -L \
    "${PROD}${WWW}/mod/page/view.php?id=${PAGE_CMID}" -o "$PAGE"
  assert_no_fatal_html "auth_page" "$PAGE"

  if grep -qi 'Error reading from database' "$PAGE"; then
    echo "page_db_error cmid=${PAGE_CMID}"
    return 1
  fi

  if ! grep -q 'ut-lesson-content' "$PAGE"; then
    echo "page_lesson_content_missing cmid=${PAGE_CMID}"
    return 1
  fi

  if grep -qiE '<title>Error \|' "$PAGE"; then
    echo "page_error_title cmid=${PAGE_CMID}"
    return 1
  fi

  echo "page_lesson_content_ok=1 cmid=${PAGE_CMID}"

  echo "verify_moodle_web_health_ok=1"
  return 0
}

prepare_retry() {
  echo "health retry prep: reset session + purge caches + PHP-FPM restart"
  reset_session
  sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php 2>/dev/null || true
  if [ -x "${REPO}/scripts/fix-moodle-chdir-quick-vm.sh" ]; then
    bash "${REPO}/scripts/fix-moodle-chdir-quick-vm.sh" 2>/dev/null || true
  else
    systemctl restart php8.3-fpm 2>/dev/null || true
  fi
  sleep 2
}

attempt=1
HEALTH_OK=0
lift_maintenance_for_probe
while [ "$attempt" -le "$VERIFY_RETRIES" ]; do
  echo "=== health attempt ${attempt}/${VERIFY_RETRIES} ==="
  if run_checks; then
    HEALTH_OK=1
    exit 0
  fi
  if [ "$attempt" -lt "$VERIFY_RETRIES" ]; then
    echo "attempt ${attempt} failed; waiting ${VERIFY_RETRY_DELAY}s before retry"
    prepare_retry
    sleep "$VERIFY_RETRY_DELAY"
  fi
  attempt=$((attempt + 1))
done

echo "verify_moodle_web_health_failed after ${VERIFY_RETRIES} attempts" >&2
exit 1
