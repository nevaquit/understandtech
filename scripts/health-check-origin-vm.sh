#!/usr/bin/env bash
# Full origin health check + recovery on production VM.
set -euo pipefail

bash "$(dirname "$0")/recover-origin-db.sh" || true

echo "=== pgbouncer status ==="
systemctl status pgbouncer --no-pager | head -15 || true

echo "=== postgres connectivity ==="
sudo -u postgres psql -h 127.0.0.1 -p 6432 -U moodle_user -d moodle -c 'SELECT 1 AS ok;' 2>&1 | tail -3 || true

echo "=== moodle error log ==="
tail -n 30 /var/www/moodledata/error.log 2>/dev/null || echo 'no error.log'

echo "=== guest curl ==="
curl -sS -o /tmp/guest.html -w 'guest:%{http_code}\n' http://127.0.0.1/login/index.php
grep -o 'Error reading from database' /tmp/guest.html | head -1 || echo 'guest_no_db_error'
grep -o '<title>[^<]*</title>' /tmp/guest.html | head -1

echo "=== auth curl ==="
rm -f /tmp/moodle-cj /tmp/login.html /tmp/my.html
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj http://127.0.0.1/login/index.php -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
if [[ -z "${tok:-}" ]]; then
  echo 'no_logintoken'
  exit 0
fi
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=e2etest" \
  --data-urlencode "password=UtE2eTest2026Secure" \
  --data-urlencode "logintoken=${tok}" \
  http://127.0.0.1/login/index.php -o /tmp/post.html
curl -sS -b /tmp/moodle-cj http://127.0.0.1/my/ -o /tmp/my.html
grep -o 'Error reading from database' /tmp/my.html | head -1 || echo 'auth_no_db_error'
grep -o 'userId.:3' /tmp/my.html | head -1 || echo 'no_userid'
grep -o '<title>[^<]*</title>' /tmp/my.html | head -1
