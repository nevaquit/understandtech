#!/usr/bin/env bash
# Idempotently wire Moodle Redis sessions from /etc/moodle/env (run on production VM).
set -euo pipefail

CONFIG="${MOODLE_CONFIG:-/var/www/moodle/config.php}"
MARKER='$CFG->session_handler_class'

if sudo grep -qF "\\core\\session\\redis" "$CONFIG" 2>/dev/null; then
    echo 'redis_sessions=already_configured'
elif sudo grep -qF 'session_handler_class' "$CONFIG" 2>/dev/null; then
    REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
    sudo python3 "${REPO}/scripts/patch-moodle-redis-sessions.py" "$CONFIG"
    sudo php -l "$CONFIG" >/dev/null
    echo 'redis_sessions=migrated_from_database'
else
    sudo cp "$CONFIG" "${CONFIG}.bak.$(date +%Y%m%d%H%M%S)"
    sudo python3 - <<'PY'
from pathlib import Path

config = Path("/var/www/moodle/config.php")
text = config.read_text()
needle = "require_once(__DIR__ . '/lib/setup.php');"
block = """$CFG->session_handler_class = '\\core\\session\\redis';
$CFG->session_redis_host = getenv('MOODLE_REDIS_HOST') ?: 'understandtech-redis-prod.eastus2.redis.azure.net';
$CFG->session_redis_port = (int) (getenv('MOODLE_REDIS_PORT') ?: 10000);
$CFG->session_redis_auth = getenv('MOODLE_REDIS_PASSWORD') ?: '';
$CFG->session_redis_encrypt = ['verify_peer' => false, 'verify_peer_name' => false];

putenv('AITUTOR_WORKER_SHARED_SECRET=' . (getenv('AITUTOR_WORKER_SHARED_SECRET') ?: ''));

require_once(__DIR__ . '/lib/setup.php');"""
if needle not in text:
    raise SystemExit('require_once setup.php not found')
config.write_text(text.replace(needle, block, 1))
print('redis_sessions=inserted')
PY
    sudo php -l "$CONFIG" >/dev/null
    echo 'config_php_lint=ok'
fi

if ! sudo grep -qF 'fetchbuffersize' "$CONFIG" 2>/dev/null; then
    sudo python3 - <<'PY'
from pathlib import Path
config = Path("/var/www/moodle/config.php")
text = config.read_text()
needle = "'dbsocket' => '',"
replacement = needle + "\n  'fetchbuffersize' => 100000,"
if needle not in text:
    raise SystemExit('dboptions needle not found')
config.write_text(text.replace(needle, replacement, 1))
print('fetchbuffersize=inserted')
PY
    sudo php -l "$CONFIG" >/dev/null
else
    echo 'fetchbuffersize=already_configured'
fi

REDIS_HOST=$(grep MOODLE_REDIS_HOST /etc/moodle/env | cut -d= -f2-)
REDIS_PORT=$(grep MOODLE_REDIS_PORT /etc/moodle/env | cut -d= -f2-)
REDIS_PASS=$(grep MOODLE_REDIS_PASSWORD /etc/moodle/env | cut -d= -f2-)
PONG=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" --tls -a "$REDIS_PASS" PING 2>/dev/null || true)
echo "redis_ping=${PONG:-FAILED}"

sudo systemctl restart php8.3-fpm
sudo systemctl restart pgbouncer || true
echo 'php_fpm_reloaded=1'
