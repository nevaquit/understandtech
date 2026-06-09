#!/usr/bin/env bash
# Exit 0 when production Moodle responds without DB error (via Cloudflare).
set -euo pipefail

PROD="${PROD_URL:-https://understandtech.app}"
WWW="${MOODLE_WWWROOT_PATH:-/learn}"
E2E_USER="${MOODLE_E2E_USER:-e2etest}"
E2E_PASS="${MOODLE_E2E_PASS:-UtE2eTest2026Secure}"
CJ="/tmp/origin-health-cj-$$"
LOGIN="/tmp/origin-health-login-$$"
MY="/tmp/origin-health-my-$$"
COURSE="/tmp/origin-health-course-$$"
COURSE_ID="${ORIGIN_HEALTH_COURSE_ID:-3}"

cleanup() {
  rm -f "$CJ" "$LOGIN" "$MY" "$COURSE"
}
trap cleanup EXIT

check_html() {
  local label="$1"
  local file="$2"
  if grep -q 'Error reading from database' "$file"; then
    echo "db_error label=${label}"
    grep -o '<title>[^<]*</title>' "$file" | head -1 || true
    return 1
  fi
  return 0
}

curl -sS -b "$CJ" -c "$CJ" "${PROD}${WWW}/login/index.php" -o "$LOGIN"
check_html "guest_login" "$LOGIN"

tok=$(grep -oP 'name="logintoken" value="\K[^"]+' "$LOGIN" | head -1 || true)
if [ -z "${tok}" ]; then
  echo "login_token_missing"
  exit 1
fi

curl -sS -b "$CJ" -c "$CJ" -L \
  --data-urlencode "username=${E2E_USER}" \
  --data-urlencode "password=${E2E_PASS}" \
  --data-urlencode "logintoken=${tok}" \
  "${PROD}${WWW}/login/index.php" -o "$MY"

curl -sS -b "$CJ" -c "$CJ" \
  "${PROD}${WWW}/my/" -o "$MY"

check_html "auth_my" "$MY"
grep -o '<title>[^<]*</title>' "$MY" | head -1 || true

curl -sS -b "$CJ" -c "$CJ" \
  "${PROD}${WWW}/course/view.php?id=${COURSE_ID}" -o "$COURSE"
check_html "auth_course" "$COURSE"

if ! grep -q 'courseindex_fallback' "$COURSE"; then
  echo "courseindex_fallback_missing course_id=${COURSE_ID}"
  exit 1
fi

# Course index sections hydrate client-side; verify webservice state on the VM.
if command -v sudo >/dev/null 2>&1 && [ -f /var/www/moodle/config.php ]; then
  sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
\$state = core_courseformat\external\get_state::execute(${COURSE_ID});
\$data = json_decode(\$state, true);
\$sections = is_array(\$data['section'] ?? null) ? count(\$data['section']) : 0;
if (\$sections < 1) { fwrite(STDERR, 'course_index_state_empty sections=' . \$sections . PHP_EOL); exit(1); }
echo 'course_index_state_sections=' . \$sections . PHP_EOL;
  " || exit 1
fi

grep -o '<title>[^<]*</title>' "$COURSE" | head -1 || true
echo 'origin_web_health_ok=1'
