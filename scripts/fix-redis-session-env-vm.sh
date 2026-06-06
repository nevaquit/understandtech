#!/usr/bin/env bash
# Fix Moodle Redis session env loading on production VM.
set -euo pipefail

CONFIG="${MOODLE_CONFIG:-/var/www/moodle/config.php}"
ENVFILE="/etc/moodle/env"

echo "=== env file ==="
if [[ -f "$ENVFILE" ]]; then
  echo "env_exists=yes"
  grep -E '^MOODLE_REDIS_|^MOODLE_DB_' "$ENVFILE" | sed 's/PASSWORD=.*/PASSWORD=***/'
else
  echo "env_exists=no"
fi

echo "=== config env loader ==="
if grep -q '/etc/moodle/env' "$CONFIG"; then
  echo "config_loads_env=yes"
else
  echo "config_loads_env=no — patching"
  sudo cp "$CONFIG" "${CONFIG}.bak.$(date +%Y%m%d%H%M%S)"
  sudo python3 - <<'PY'
from pathlib import Path
config = Path("/var/www/moodle/config.php")
text = config.read_text()
block = """$envfile = '/etc/moodle/env';
if (is_readable($envfile)) {
    foreach (file($envfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

"""
needle = "$CFG = new stdClass();"
if needle not in text:
    raise SystemExit('CFG init not found')
if '/etc/moodle/env' not in text:
    text = text.replace(needle, needle + "\n" + block, 1)
    config.write_text(text)
    print('env_loader=inserted')
else:
    print('env_loader=already_present')
PY
  sudo php -l "$CONFIG" >/dev/null
fi

if [[ ! -f "$ENVFILE" ]]; then
  echo "ERROR: $ENVFILE missing — run setup-moodle-env-vm.ps1 or populate from Key Vault" >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a
source "$ENVFILE"
set +a

echo "=== redis ping ==="
PONG=$(redis-cli -h "$MOODLE_REDIS_HOST" -p "$MOODLE_REDIS_PORT" --tls -a "$MOODLE_REDIS_PASSWORD" PING 2>/dev/null || true)
echo "redis_ping=${PONG:-FAILED}"

echo "=== php-fpm reload ==="
sudo systemctl reload php8.3-fpm
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

echo "=== localhost auth smoke ==="
rm -f /tmp/moodle-cj /tmp/login.html /tmp/my.html
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj "http://127.0.0.1/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1)
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=e2etest" \
  --data-urlencode "password=UtE2eTest2026Secure" \
  --data-urlencode "logintoken=${tok}" \
  "http://127.0.0.1/login/index.php" -o /tmp/my.html
grep -oE 'Error reading from database|userId.:3|Dashboard' /tmp/my.html | head -5 || true
grep -o '<title>[^<]*</title>' /tmp/my.html | head -1
