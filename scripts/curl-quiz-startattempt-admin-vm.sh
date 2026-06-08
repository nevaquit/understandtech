#!/usr/bin/env bash
# Reproduce quiz startattempt as admin and capture HTTP code + errors.
set -euo pipefail

CMID="${1:-60}"
USER="${2:-admin}"
PASS="${3:-}"
HOST="${MOODLE_HOST:-understandtech.app}"
BASE="https://127.0.0.1"
WWW="/learn"

if [ -z "$PASS" ] && [ -f /etc/moodle/env ]; then
  # shellcheck disable=SC1091
  set -a
  source /etc/moodle/env
  set +a
fi

rm -f /tmp/moodle-cj /tmp/login.html /tmp/post.html /tmp/quiz.html /tmp/start.html

curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj \
  "${BASE}${WWW}/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1)
if [ -z "$tok" ]; then
  echo 'login_token_missing'
  grep -oE 'Error reading from database' /tmp/login.html | head -1 || true
  exit 1
fi

curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=${USER}" \
  --data-urlencode "password=${PASS}" \
  --data-urlencode "logintoken=${tok}" \
  "${BASE}${WWW}/login/index.php" -o /tmp/post.html
if grep -q 'Error reading from database' /tmp/post.html; then
  echo 'login_db_error'
  exit 1
fi
if grep -qi 'invalid login' /tmp/post.html; then
  echo 'login_invalid'
  grep -oE 'alert-danger[^<]*' /tmp/post.html | head -1 || true
  exit 1
fi
grep -o '<title>[^<]*</title>' /tmp/post.html | head -1

curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj \
  "${BASE}${WWW}/mod/quiz/view.php?id=${CMID}" -o /tmp/quiz.html
sesskey=$(grep -oP 'name="sesskey" value="\K[^"]+' /tmp/quiz.html | head -1 || true)
previewurl=$(grep -oP 'href="[^"]*startattempt\.php[^"]*"' /tmp/quiz.html | head -1 | sed 's/href="//;s/"$//' || true)
echo "sesskey=${sesskey:-missing}"
echo "preview_link=${previewurl:-missing}"

if [ -z "$sesskey" ]; then
  grep -oE 'Error reading from database|Preview quiz|Log in' /tmp/quiz.html | head -5 || true
  exit 1
fi

# Preview attempt POST (same as green button).
code=$(curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  -o /tmp/start.html -w '%{http_code}' \
  --data-urlencode "cmid=${CMID}" \
  --data-urlencode "sesskey=${sesskey}" \
  --data-urlencode "_qf__mod_quiz_preflight_check_form=1" \
  --data-urlencode "submitbutton=Start attempt" \
  "${BASE}${WWW}/mod/quiz/startattempt.php?cmid=${CMID}")
echo "startattempt_http=${code}"
grep -o '<title>[^<]*</title>' /tmp/start.html | head -1
grep -oE 'Error reading from database|Coding error|Exception|Question [0-9]|alert-danger|HTTP ERROR' /tmp/start.html | head -10 || echo 'start_body_ok'
