#!/usr/bin/env bash
# Sync playbook plugins missing from production without touching working installs.
# Safe: pre/post DB ping, no config.php edits, standard upgrade path.
#
# Usage on VM:
#   sudo bash /opt/understandtech-plugins/scripts/sync-missing-plugins-vm.sh
#
# Missing targets (as of audit): block_portfolio, local_aigrading, local_community,
# local_integrations — plus local_aitutor bump required by local_aigrading dependency.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
MOODLE="${MOODLE_DIR:-/var/www/moodle}"
RSYNC="${RSYNC_BIN:-/usr/bin/rsync}"

PLUGINS=(
  local_aitutor
  block_portfolio
  local_aigrading
  local_community
  local_integrations
)

plugin_moodle_path() {
  case "$1" in
    local_*) echo "local/${1#local_}" ;;
    mod_*) echo "mod/${1#mod_}" ;;
    theme_*) echo "theme/${1#theme_}" ;;
    block_*) echo "blocks/${1#block_}" ;;
    qbehaviour_*) echo "question/behaviour/${1#qbehaviour_}" ;;
    *) echo "Unknown plugin: $1" >&2; return 1 ;;
  esac
}

preflight_db() {
  sudo -u www-data php -r "
define('CLI_SCRIPT', true);
chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
global \$DB;
\$DB->get_field_sql('SELECT 1');
echo \"preflight_db_ok\n\";
"
}

echo "=== sync missing plugins (safe) ==="
preflight_db

if [ -d "$REPO/.git" ]; then
  cd "$REPO"
  sudo -u gha-runner git fetch origin main
  sudo -u gha-runner git reset --hard origin/main
  echo "repo_synced=$(sudo -u gha-runner git rev-parse --short HEAD)"
else
  echo "WARN repo not at $REPO — using existing checkout"
fi

for name in "${PLUGINS[@]}"; do
  relpath="$(plugin_moodle_path "$name")"
  src="${REPO}/moodle-plugins/${name}/"
  dst="${MOODLE}/${relpath}/"
  if [ ! -f "${src}version.php" ]; then
    echo "SKIP $name (no version.php)"
    continue
  fi
  sudo mkdir -p "$dst"
  sudo "$RSYNC" -av \
    --exclude='.git' --exclude='.github' --exclude='*.md' --exclude='tests/' \
    "$src" "$dst"
  sudo chown -R www-data:www-data "$dst"
  echo "synced $name -> $relpath"
done

echo "=== upgrade ==="
sudo /usr/bin/bash "${REPO}/scripts/moodle-upgrade-direct-pg.sh"

echo "=== purge caches ==="
sudo /usr/bin/php "${MOODLE}/admin/cli/purge_caches.php"

preflight_db
echo "=== post-sync plugin versions ==="
sudo -u www-data php -r "
define('CLI_SCRIPT', true);
chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
foreach (['local_aitutor','local_aigrading','local_community','local_integrations','block_portfolio'] as \$c) {
  \$v = get_config(\$c, 'version');
  echo \$c . ': ' . (\$v ? 'v' . \$v : 'NOT_INSTALLED') . PHP_EOL;
}
echo 'active_theme=' . get_config('core', 'theme') . PHP_EOL;
"

bash "${REPO}/scripts/post-deploy-stabilize-vm.sh"

echo "=== done ==="
