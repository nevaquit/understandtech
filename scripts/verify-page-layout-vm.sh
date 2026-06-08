#!/usr/bin/env bash
# Verify mod_page layout markers after theme deploy.
set -euo pipefail
CMID="${1:-4}"
BASE="${2:-https://understandtech.app}"
WWWROOT_PATH="${3:-/learn}"
USER="${E2E_USERNAME:-e2etest}"
PASS="${E2E_PASSWORD:-UtE2eTest2026Secure}"

echo "=== css on disk ==="
grep -c 'path-mod-page .ut-lesson-content' /var/www/moodle/theme/understandtech/style/lesson-content.css
grep -c 'height: auto !important' /var/www/moodle/theme/understandtech/style/lesson-content.css
grep version /var/www/moodle/theme/understandtech/version.php

echo "=== authenticated page ==="
rm -f /tmp/moodle-cj /tmp/login.html /tmp/page.html
LOGIN_URL="${BASE}${WWWROOT_PATH}/login/index.php"
PAGE_URL="${BASE}${WWWROOT_PATH}/mod/page/view.php?id=${CMID}"
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj "${LOGIN_URL}" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
if [ -z "${tok}" ]; then
  echo 'login_token_missing'
  exit 1
fi
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=${USER}" \
  --data-urlencode "password=${PASS}" \
  --data-urlencode "logintoken=${tok}" \
  "${LOGIN_URL}" -o /tmp/login-out.html
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  -o /tmp/page.html -w "page_http:%{http_code}\n" "${PAGE_URL}"
grep -o '<title>[^<]*</title>' /tmp/page.html | head -1
grep -oE 'ut-lesson-content|path-mod-page|lesson-content\.css|SY701\.1\.2|Error reading from database' /tmp/page.html | sort -u | tr '\n' ' '
echo
wc -c /tmp/page.html
echo "ut_lesson_count=$(grep -c 'ut-lesson-content' /tmp/page.html || true)"
echo "path_mod_page_count=$(grep -c 'path-mod-page' /tmp/page.html || true)"
echo "lesson_css_count=$(grep -c 'lesson-content.css' /tmp/page.html || true)"
echo '=== done ==='
