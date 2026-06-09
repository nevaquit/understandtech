#!/usr/bin/env bash
# Exit 0 when Moodle web bootstrap responds without DB error (loopback + Host header).
set -euo pipefail

HOST="${MOODLE_HOST:-understandtech.app}"
BASE="${MOODLE_ORIGIN_BASE:-http://127.0.0.1}"
WWW="${MOODLE_WWWROOT_PATH:-/learn}"

check_url() {
  local path="$1"
  local html
  html="$(curl -sS -H "Host: ${HOST}" "${BASE}${path}" || true)"
  if echo "$html" | grep -q 'Error reading from database'; then
    echo "db_error path=${path}"
    return 1
  fi
  return 0
}

check_url "${WWW}/login/index.php"
check_url "${WWW}/my/"
echo 'origin_web_health_ok=1'
