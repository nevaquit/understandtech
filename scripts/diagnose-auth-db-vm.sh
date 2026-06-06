#!/usr/bin/env bash
# Diagnose authenticated web DB errors on production VM.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"

echo "=== services ==="
systemctl is-active redis-server pgbouncer php8.3-fpm nginx || true

echo "=== config session/redis ==="
grep -E 'session_handler_class|dbsessions|redis|fetchbuffersize|dbhost|dbport' "$MOODLE_DIR/config.php" | sed 's/password.*$/password=***/'

echo "=== cli db user lookup ==="
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
global $DB;
$u = $DB->get_record('user', ['id' => 3]);
echo $u ? "cli_user={$u->username}\n" : "cli_user_missing\n";
$blocks = $DB->count_records('block_instances');
echo "cli_blocks={$blocks}\n";
PHP

echo "=== localhost guest ==="
curl -sS -o /dev/null -w 'guest_http:%{http_code}\n' http://127.0.0.1/

echo "=== localhost authenticated /my/ ==="
rm -f /tmp/moodle-cj /tmp/login.html /tmp/my.html
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj "http://127.0.0.1/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1)
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=e2etest" \
  --data-urlencode "password=UtE2eTest2026Secure" \
  --data-urlencode "logintoken=${tok}" \
  "http://127.0.0.1/login/index.php" -o /tmp/my.html
grep -oE 'Error reading from database|userId.:3|Dashboard|alert-danger' /tmp/my.html | head -10 || true
grep -o '<title>[^<]*</title>' /tmp/my.html | head -1

echo "=== php-fpm error log tail ==="
tail -n 20 /var/log/php8.3-fpm.log 2>/dev/null || tail -n 20 /var/log/php-fpm.log 2>/dev/null || echo 'no fpm log'

echo "=== nginx error log tail ==="
tail -n 10 /var/log/nginx/error.log 2>/dev/null || echo 'no nginx error log'
