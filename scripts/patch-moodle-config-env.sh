#!/usr/bin/env bash
# Insert /etc/moodle/env loader into live config.php if missing (idempotent).
set -euo pipefail

CONFIG="${MOODLE_CONFIG:-/var/www/moodle/config.php}"
MARKER='# understandtech: load /etc/moodle/env'

if sudo grep -qF "$MARKER" "$CONFIG" 2>/dev/null; then
    echo 'config_env_loader=present'
    exit 0
fi

sudo cp "$CONFIG" "${CONFIG}.bak.$(date +%Y%m%d%H%M%S)"

sudo python3 - <<'PY'
from pathlib import Path

config = Path("/var/www/moodle/config.php")
text = config.read_text()
needle = "$CFG = new stdClass();\n"
block = """$CFG = new stdClass();

// understandtech: load /etc/moodle/env
$envfile = '/etc/moodle/env';
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
if needle not in text:
    raise SystemExit('needle not found in config.php')
config.write_text(text.replace(needle, block, 1))
print('config_env_loader=inserted')
PY

sudo php -l "$CONFIG" >/dev/null
echo 'config_php_lint=ok'
