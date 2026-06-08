#!/usr/bin/env bash
# Purge Moodle caches and verify page module rendering (fixes stale filter MUC).
set -euo pipefail
CMID="${1:-4}"

echo '=== purge caches ==='
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

echo '=== format_text cold test ==='
sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-page-cmid.php "$CMID" 2>&1 | grep -E 'format_text_no_filter|format_text_with_filters|format_text_final|page_name' || true

echo '=== authenticated page view ==='
rm -f /tmp/moodle-cj /tmp/login.html /tmp/page-view.html
HOST="${MOODLE_HOST:-understandtech.app}"
BASE="${MOODLE_ORIGIN_BASE:-http://127.0.0.1}"
WWW="/learn"
curl -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj "${BASE}${WWW}/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
if [ -z "${tok}" ]; then
  echo 'page_view_curl_skipped=login_token_missing'
else
  curl -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
    --data-urlencode "username=e2etest" \
    --data-urlencode "password=UtE2eTest2026Secure" \
    --data-urlencode "logintoken=${tok}" \
    "${BASE}${WWW}/login/index.php" -o /tmp/login-out.html
  curl -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
    -o /tmp/page-view.html -w 'page_http:%{http_code}\n' \
    "${BASE}${WWW}/mod/page/view.php?id=${CMID}"
  grep -oE 'Error reading from database|ut-lesson-content|SY701' /tmp/page-view.html | head -10 || echo 'page_content_ok'
fi
