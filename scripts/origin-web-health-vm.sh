#!/usr/bin/env bash
# Exit 0 when Moodle web responds without DB error (guest + authenticated /my/).
set -euo pipefail

HOST="${MOODLE_HOST:-understandtech.app}"
BASE="${MOODLE_ORIGIN_BASE:-http://127.0.0.1}"
WWW="${MOODLE_WWWROOT_PATH:-/learn}"
E2E_USER="${MOODLE_E2E_USER:-e2etest}"
E2E_PASS="${MOODLE_E2E_PASS:-UtE2eTest2026Secure}"
CJ="/tmp/origin-health-cj-$$"
LOGIN="/tmp/origin-health-login-$$"
MY="/tmp/origin-health-my-$$"

cleanup() {
  rm -f "$CJ" "$LOGIN" "$MY"
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

curl -sS -H "Host: ${HOST}" -b "$CJ" -c "$CJ" "${BASE}${WWW}/login/index.php" -o "$LOGIN"
check_html "guest_login" "$LOGIN"

tok=$(grep -oP 'name="logintoken" value="\K[^"]+' "$LOGIN" | head -1 || true)
if [ -z "${tok}" ]; then
  echo "login_token_missing"
  exit 1
fi

curl -sS -H "Host: ${HOST}" -b "$CJ" -c "$CJ" -L \
  --data-urlencode "username=${E2E_USER}" \
  --data-urlencode "password=${E2E_PASS}" \
  --data-urlencode "logintoken=${tok}" \
  "${BASE}${WWW}/login/index.php" -o "$MY"

curl -sS -H "Host: ${HOST}" -b "$CJ" -c "$CJ" \
  "${BASE}${WWW}/my/" -o "$MY"

check_html "auth_my" "$MY"
grep -o '<title>[^<]*</title>' "$MY" | head -1 || true
echo 'origin_web_health_ok=1'
