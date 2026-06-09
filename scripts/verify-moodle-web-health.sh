#!/usr/bin/env bash
# Strict Moodle web health — fails on login exceptions, DB errors, or broken course pages.
# Used as a deploy gate and by origin-health monitoring.
set -euo pipefail

PROD="${PROD_URL:-https://understandtech.app}"
WWW="${MOODLE_WWWROOT_PATH:-/learn}"
E2E_USER="${MOODLE_E2E_USER:-e2etest}"
E2E_PASS="${MOODLE_E2E_PASS:-UtE2eTest2026Secure}"
COURSE_ID="${VERIFY_COURSE_ID:-3}"
CJ="/tmp/verify-moodle-cj-$$"
LOGIN="/tmp/verify-moodle-login-$$"
COURSE="/tmp/verify-moodle-course-$$"

cleanup() {
  rm -f "$CJ" "$LOGIN" "$COURSE"
}
trap cleanup EXIT

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

echo "=== guest login ==="
curl -sS -b "$CJ" -c "$CJ" "${PROD}${WWW}/login/index.php" -o "$LOGIN"
assert_no_fatal_html "guest_login" "$LOGIN"

if ! grep -q 'name="logintoken"' "$LOGIN"; then
  echo "login_token_missing"
  exit 1
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

curl -sS -b "$CJ" -c "$CJ" "${PROD}${WWW}/my/" -o "$LOGIN"
assert_no_fatal_html "auth_my" "$LOGIN"
if ! grep -q 'timeline_fallback' "$LOGIN"; then
  echo "timeline_fallback_missing"
  exit 1
fi
echo "auth_my_ok=1"

echo "=== course view id=${COURSE_ID} ==="
curl -sS -b "$CJ" -c "$CJ" "${PROD}${WWW}/course/view.php?id=${COURSE_ID}" -o "$COURSE"
assert_no_fatal_html "auth_course" "$COURSE"

if ! grep -q 'templates_dom_patch' "$COURSE"; then
  echo "templates_dom_patch_missing"
  exit 1
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

echo "verify_moodle_web_health_ok=1"
