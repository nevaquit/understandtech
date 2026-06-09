#!/usr/bin/env bash
# Diagnose SEC701 course index drawer hydration on production VM.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
COURSE_ID="${1:-3}"
PROD="${PROD_URL:-https://understandtech.app}"
WWW="${MOODLE_WWWROOT_PATH:-/learn}"
E2E_USER="${MOODLE_E2E_USER:-e2etest}"
E2E_PASS="${MOODLE_E2E_PASS:-UtE2eTest2026Secure}"
CJ="/tmp/course-index-cj-$$"
LOGIN="/tmp/course-index-login-$$"
COURSE="/tmp/course-index-view-$$"

cleanup() {
  rm -f "$CJ" "$LOGIN" "$COURSE"
}
trap cleanup EXIT

echo "=== theme version ==="
grep version /var/www/moodle/theme/understandtech/version.php | head -1

echo "=== amd fallback present ==="
test -f /var/www/moodle/theme/understandtech/amd/build/courseindex_fallback.min.js && echo courseindex_fallback_ok=1 || echo courseindex_fallback_missing=1

echo "=== authenticated course view ==="
curl -sS -b "$CJ" -c "$CJ" "${PROD}${WWW}/login/index.php" -o "$LOGIN"
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' "$LOGIN" | head -1 || true)
if [ -z "${tok}" ]; then
  echo login_token_missing=1
  exit 1
fi

curl -sS -b "$CJ" -c "$CJ" -L \
  --data-urlencode "username=${E2E_USER}" \
  --data-urlencode "password=${E2E_PASS}" \
  --data-urlencode "logintoken=${tok}" \
  "${PROD}${WWW}/login/index.php" -o /dev/null

curl -sS -b "$CJ" -c "$CJ" \
  "${PROD}${WWW}/course/view.php?id=${COURSE_ID}" -o "$COURSE"

grep -o '<title>[^<]*</title>' "$COURSE" | head -1 || true
grep -o 'Error reading from database' "$COURSE" | head -1 || echo db_ok=1
grep -o 'courseindex_fallback' "$COURSE" | head -1 || echo fallback_amd_missing=1
sections=$(grep -o 'courseindex-section' "$COURSE" | wc -l | tr -d ' ')
items=$(grep -o 'courseindex-item' "$COURSE" | wc -l | tr -d ' ')
placeholder=$(grep -o 'course-index-placeholder' "$COURSE" | wc -l | tr -d ' ')
echo "server_sections=${sections} server_items=${items} placeholder_tags=${placeholder}"

if [ "${sections}" -gt 0 ]; then
  echo course_index_server_render_ok=1
else
  echo course_index_server_render_empty=1
  echo "NOTE: course index hydrates client-side; empty HTML is expected until AMD runs."
fi

echo "=== cli get_state ==="
sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
require_once(\$CFG->libdir . '/externallib.php');
\$state = core_courseformat\external\get_state::execute(${COURSE_ID});
\$data = json_decode(\$state, true);
echo 'cli_sections=' . (is_array(\$data['section'] ?? null) ? count(\$data['section']) : 0) . PHP_EOL;
echo 'cli_cms=' . (is_array(\$data['cm'] ?? null) ? count(\$data['cm']) : 0) . PHP_EOL;
"

echo diagnose_course_index_complete=1
