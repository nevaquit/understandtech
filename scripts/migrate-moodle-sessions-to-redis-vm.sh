#!/usr/bin/env bash
# Replace Moodle database sessions with Azure Redis (fixes startattempt 500 / session lock errors).
set -euo pipefail

REPO="${REPO:-/opt/understandtech-plugins}"
CONFIG="${MOODLE_CONFIG:-/var/www/moodle/config.php}"
ENVFILE="/etc/moodle/env"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "${REPO}" fetch origin main
  sudo -u gha-runner git -C "${REPO}" reset --hard origin/main
fi

bash "${REPO}/scripts/fix-redis-session-env-vm.sh"

if [[ ! -f "$ENVFILE" ]]; then
  echo "ERROR: missing ${ENVFILE}" >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a
source "$ENVFILE"
set +a

echo "=== current session handler ==="
grep -E 'session_handler_class|session_redis' "$CONFIG" || true

if grep -q "\\\\core\\\\session\\\\redis" "$CONFIG"; then
  echo "session_handler=already_redis"
else
  sudo cp "$CONFIG" "${CONFIG}.bak.$(date +%Y%m%d%H%M%S)"
  sudo python3 - <<'PY'
from pathlib import Path
import re

config = Path("/var/www/moodle/config.php")
text = config.read_text()

redis_block = """$CFG->session_handler_class = '\\core\\session\\redis';
$CFG->session_redis_host = getenv('MOODLE_REDIS_HOST') ?: 'understandtech-redis-prod.eastus2.redis.azure.net';
$CFG->session_redis_port = (int) (getenv('MOODLE_REDIS_PORT') ?: 10000);
$CFG->session_redis_auth = getenv('MOODLE_REDIS_PASSWORD') ?: '';
$CFG->session_redis_encrypt = ['verify_peer' => false, 'verify_peer_name' => false];
"""

if "$CFG->session_handler_class" in text:
    text = re.sub(
        r"\$CFG->session_handler_class\s*=\s*'[^']*';\n",
        redis_block,
        text,
        count=1,
    )
else:
    needle = "require_once(__DIR__ . '/lib/setup.php');"
    if needle not in text:
        raise SystemExit("setup.php require not found")
    text = text.replace(needle, redis_block + "\n" + needle, 1)

config.write_text(text)
print("session_handler=migrated_to_redis")
PY
  sudo php -l "$CONFIG" >/dev/null
fi

sudo chown root:www-data "$CONFIG"
sudo chmod 640 "$CONFIG"

echo "=== verify redis ==="
PONG=$(redis-cli -h "$MOODLE_REDIS_HOST" -p "$MOODLE_REDIS_PORT" --tls -a "$MOODLE_REDIS_PASSWORD" PING 2>/dev/null || true)
echo "redis_ping=${PONG:-FAILED}"

echo "=== verify session handler in config ==="
grep -E 'session_handler_class|session_redis_host' "$CONFIG"

sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
sudo systemctl reload php8.3-fpm

echo 'migrate_moodle_sessions_to_redis_complete=1'
