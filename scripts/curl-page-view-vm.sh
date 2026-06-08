#!/usr/bin/env bash
set -euo pipefail
CMID="${1:-4}"
BASE="${2:-http://127.0.0.1}"
WWWROOT_PATH="${3:-/learn}"

rm -f /tmp/moodle-cj /tmp/login.html /tmp/page.html
LOGIN_URL="${BASE}${WWWROOT_PATH}/login/index.php"
PAGE_URL="${BASE}${WWWROOT_PATH}/mod/page/view.php?id=${CMID}"

echo "login_url=${LOGIN_URL}"
echo "page_url=${PAGE_URL}"

try_login() {
  local u="$1"
  local p="$2"
  echo "=== try user=${u} ==="
  rm -f /tmp/moodle-cj
  curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj "${LOGIN_URL}" -o /tmp/login.html
  local tok
  tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
  if [ -z "${tok}" ]; then
    echo 'login_token_missing'
    return 1
  fi
  curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
    --data-urlencode "username=${u}" \
    --data-urlencode "password=${p}" \
    --data-urlencode "logintoken=${tok}" \
    "${LOGIN_URL}" -o /tmp/login-out.html
  if grep -q 'alert-danger' /tmp/login-out.html; then
    echo 'login_failed'
    return 1
  fi
  curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
    -o /tmp/page.html -w "page_http:%{http_code}\n" "${PAGE_URL}"
  grep -o '<title>[^<]*</title>' /tmp/page.html | head -1
  grep -oE 'Error reading from database|ut-lesson-content|debuginfo|SY701\.1\.2' /tmp/page.html | head -10 || echo 'no_match'
  if grep -q 'Error reading from database' /tmp/page.html; then
    echo 'page_db_error=1'
  else
    echo 'page_db_error=0'
  fi
}

try_login e2etest 'UtE2eTest2026Secure' || true
