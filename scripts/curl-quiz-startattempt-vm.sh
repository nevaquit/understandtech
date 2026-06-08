#!/usr/bin/env bash
# Authenticated curl to quiz startattempt (preview) on origin.
set -euo pipefail

CMID="${1:-60}"
HOST="${MOODLE_HOST:-understandtech.app}"
BASE="https://127.0.0.1"
WWW="/learn"

rm -f /tmp/moodle-cj /tmp/login.html /tmp/quiz.html /tmp/start.html
curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj \
  "${BASE}${WWW}/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1)
curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=admin" \
  --data-urlencode "password=${MOODLE_ADMIN_PASSWORD:-}" \
  --data-urlencode "logintoken=${tok}" \
  "${BASE}${WWW}/login/index.php" -o /tmp/post.html || true

# Load quiz view to get sesskey.
curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj \
  "${BASE}${WWW}/mod/quiz/view.php?id=${CMID}" -o /tmp/quiz.html
sesskey=$(grep -oP 'name="sesskey" value="\K[^"]+' /tmp/quiz.html | head -1 || true)
grep -oE 'Preview quiz|Attempt quiz|startattempt' /tmp/quiz.html | head -3 || true

if [ -z "${sesskey}" ]; then
  echo 'sesskey_missing'
  grep -oE 'Error reading from database|alert-danger|Log in' /tmp/quiz.html | head -3 || true
  exit 1
fi

code=$(curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L -o /tmp/start.html -w '%{http_code}' \
  --data "cmid=${CMID}&sesskey=${sesskey}&_qf__mod_quiz_preflight_check_form=1&submitbutton=Start+attempt" \
  "${BASE}${WWW}/mod/quiz/startattempt.php?cmid=${CMID}")
echo "startattempt_http=${code}"
grep -o '<title>[^<]*</title>' /tmp/start.html | head -1
grep -oE 'Error reading from database|HTTP ERROR|alert-danger|Question [0-9]' /tmp/start.html | head -5 || echo 'startattempt_content_ok'
