#!/usr/bin/env bash
# Deploy Moodle PHP-FPM pool config from repo and restart workers.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
SRC="${REPO}/infrastructure/php-fpm/moodle.conf"
DEST="/etc/php/8.3/fpm/pool.d/moodle.conf"

if [ ! -f "$SRC" ]; then
  echo "Missing pool config: $SRC" >&2
  exit 1
fi

# Origin health runs every 3 minutes; restarting PHP-FPM on every pass drops live
# requests and surfaces as Cloudflare 502 Bad Gateway (e.g. menu Home link).
if [ -f "$DEST" ] && cmp -s "$SRC" "$DEST"; then
  echo 'apply_php_fpm_pool_unchanged=1'
  exit 0
fi

cp "$SRC" "$DEST"
chmod 644 "$DEST"
php-fpm8.3 -t
systemctl restart php8.3-fpm
echo 'apply_php_fpm_pool_complete=1'
