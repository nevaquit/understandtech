#!/usr/bin/env bash
# Sync theme_understandtech from monorepo to Moodle webroot (idempotent).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
SRC="${REPO}/moodle-plugins/theme_understandtech/"
DST="${MOODLE_DIR}/theme/understandtech/"

if [ ! -d "$SRC" ] || [ ! -f "${SRC}version.php" ]; then
  echo "skip: theme_understandtech not present in monorepo"
  exit 0
fi

echo "=== sync theme_understandtech ==="
mkdir -p "$DST"
rsync -av --delete \
  --exclude='.git' --exclude='.github' --exclude='tests/' --exclude='*.md' \
  "$SRC" "$DST"
chown -R www-data:www-data "$DST"

if [ "${SKIP_MOODLE_UPGRADE:-0}" = 1 ] || [ -f /tmp/understandtech-skip-moodle-upgrade ]; then
  echo "skip: Moodle upgrade (rollback recovery — DB plugin versions may be ahead of disk)"
else
  sudo -u www-data php "${MOODLE_DIR}/admin/cli/upgrade.php" --non-interactive
fi
sudo -u www-data php "${MOODLE_DIR}/admin/cli/purge_caches.php"
echo 'sync_theme_understandtech_complete=1'
