#!/usr/bin/env bash
# Origin HTTP smoke via local nginx with Host header (avoids 301 loop).
set -euo pipefail

HOST="${MOODLE_HOST:-understandtech.app}"
BASE="http://127.0.0.1"

fetch() {
  curl -sS -H "Host: ${HOST}" "$@"
}

echo "=== guest login ==="
fetch "${BASE}/login/index.php" -o /tmp/guest.html
grep -o 'Error reading from database' /tmp/guest.html | head -1 || echo 'guest_ok'
grep -o '<title>[^<]*</title>' /tmp/guest.html | head -1

echo "=== auth /my/ ==="
rm -f /tmp/moodle-cj /tmp/login.html /tmp/my.html
fetch -b /tmp/moodle-cj -c /tmp/moodle-cj "${BASE}/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
if [[ -z "${tok:-}" ]]; then
  echo 'no_logintoken'
  head -5 /tmp/login.html
  exit 1
fi
fetch -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=e2etest" \
  --data-urlencode "password=UtE2eTest2026Secure" \
  --data-urlencode "logintoken=${tok}" \
  "${BASE}/login/index.php" -o /tmp/post.html
fetch -b /tmp/moodle-cj "${BASE}/my/" -o /tmp/my.html
grep -o 'Error reading from database' /tmp/my.html | head -1 || echo 'auth_ok'
grep -o 'userId.:3' /tmp/my.html | head -1 || echo 'no_userid'
grep -o '<title>[^<]*</title>' /tmp/my.html | head -1
grep -o 'block-examreadiness' /tmp/my.html | head -1 || echo 'no_exam_block'
