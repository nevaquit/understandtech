#!/usr/bin/env bash
# Restart origin stack and verify web + CLI DB paths.
set -euo pipefail

CONFIG="/var/www/moodle/config.php"

echo "=== restart services ==="
systemctl restart pgbouncer
systemctl reload php8.3-fpm
systemctl reload nginx

echo "=== cli db ==="
sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '${CONFIG}';
global \$DB;
\$DB->get_record('config', ['name' => 'version'], 'value');
echo \"cli_db_ok\n\";
"

echo "=== web bootstrap (no CLI_SCRIPT) ==="
sudo -u www-data php -r "
require '${CONFIG}';
global \$DB;
\$DB->get_record('config', ['name' => 'version'], 'value');
echo \"web_db_ok\n\";
" 2>&1 || echo web_db_failed

echo "=== redis env ==="
grep MOODLE_REDIS /etc/moodle/env | sed 's/PASSWORD=.*/PASSWORD=***/'

echo "=== redis ping ==="
set -a
source /etc/moodle/env
set +a
redis-cli -h "$MOODLE_REDIS_HOST" -p "$MOODLE_REDIS_PORT" --tls -a "$MOODLE_REDIS_PASSWORD" PING

echo "=== purge caches ==="
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

echo "=== guest login via nginx ==="
curl -sS -H "Host: understandtech.app" -k "https://127.0.0.1/login/index.php" -o /tmp/guest.html || \
  curl -sS -H "Host: understandtech.app" "http://127.0.0.1/login/index.php" -o /tmp/guest.html
grep -o 'Error reading from database' /tmp/guest.html | head -1 || echo guest_login_ok
grep -o '<title>[^<]*</title>' /tmp/guest.html | head -1
