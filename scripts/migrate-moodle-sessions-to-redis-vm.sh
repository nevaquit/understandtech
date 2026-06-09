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
  sudo python3 "${REPO}/scripts/patch-moodle-redis-sessions.py" "$CONFIG"
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
sudo systemctl restart php8.3-fpm

echo 'migrate_moodle_sessions_to_redis_complete=1'
