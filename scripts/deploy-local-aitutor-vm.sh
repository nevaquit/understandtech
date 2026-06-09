#!/usr/bin/env bash
# Run on production VM: sync local_aitutor, upgrade, purge caches.
set -euo pipefail

MOODLE="${MOODLE_DIR:-/var/www/moodle}"
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
SRC="${REPO}/moodle-plugins/local_aitutor"
DST="${MOODLE}/local/aitutor"

if [ ! -d "$SRC" ]; then
  echo "Missing plugin source: $SRC" >&2
  exit 1
fi

sudo rsync -a --delete "$SRC/" "$DST/"
sudo chown -R www-data:www-data "$DST"
echo "synced local/aitutor"

cd "$REPO" && sudo git pull --ff-only origin main || true

sudo -u www-data /usr/bin/php "${MOODLE}/admin/cli/upgrade.php" --non-interactive
sudo -u www-data /usr/bin/php "${MOODLE}/admin/cli/purge_caches.php"

bash "${REPO}/scripts/post-deploy-stabilize-vm.sh"

echo "upgrade + stabilize complete"
