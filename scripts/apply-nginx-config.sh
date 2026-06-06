#!/usr/bin/env bash
# Apply understandtech nginx vhost and reload (run on production VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
SRC="${REPO}/infrastructure/nginx/understandtech.conf"
DST="/etc/nginx/sites-available/understandtech.conf"

if [ ! -f "$SRC" ]; then
  echo "Missing nginx config: $SRC" >&2
  exit 1
fi

sudo cp "$SRC" "$DST"
if [ -f /etc/nginx/sites-enabled/understandtech.conf ]; then
  :
elif [ -L /etc/nginx/sites-enabled/default ]; then
  sudo rm -f /etc/nginx/sites-enabled/default
  sudo ln -sf "$DST" /etc/nginx/sites-enabled/understandtech.conf
else
  sudo ln -sf "$DST" /etc/nginx/sites-enabled/understandtech.conf
fi

sudo cp "${REPO}/infrastructure/nginx/understandtech-rate-limit.conf" /etc/nginx/conf.d/understandtech-rate-limit.conf 2>/dev/null || true
sudo nginx -t
sudo systemctl reload nginx
echo "nginx reloaded — javascript.php and requirejs.php now route to PHP-FPM"
