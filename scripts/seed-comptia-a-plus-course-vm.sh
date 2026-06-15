#!/usr/bin/env bash
# Seed CompTIA A+ certification course on Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
MOODLE="${MOODLE_DIR:-/var/www/moodle}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

for plugin in local_certmaster theme_understandtech; do
  case "$plugin" in
    local_*) dest="${MOODLE}/local/${plugin#local_}" ;;
    theme_*) dest="${MOODLE}/theme/${plugin#theme_}" ;;
  esac
  if [ -d "${REPO}/moodle-plugins/${plugin}" ]; then
    sudo /usr/bin/rsync -a --delete "${REPO}/moodle-plugins/${plugin}/" "${dest}/"
    sudo /usr/bin/chown -R www-data:www-data "${dest}"
    echo "plugin_synced=${plugin}"
  fi
done

sudo -u www-data php "${MOODLE}/admin/cli/upgrade.php" --non-interactive || true

sudo -u www-data php "${REPO}/scripts/seed-comptia-a-plus-course.php"
sudo -u www-data php "${REPO}/scripts/fix-aplus-course-filters.php"
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
echo 'seed_aplus_complete=1'
