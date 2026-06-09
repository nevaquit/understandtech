#!/usr/bin/env bash
set -euo pipefail
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi
bash "${REPO}/scripts/deploy-plugins-vm.sh" "${REPO}/moodle-plugins"
cd /var/www/moodle
sudo -u www-data php admin/cli/upgrade.php --non-interactive --allow-unstable
sudo -u www-data php admin/cli/purge_caches.php
bash "${REPO}/scripts/seed-security-plus-course-vm.sh"
bash "${REPO}/scripts/post-deploy-stabilize-vm.sh"
echo 'deploy_lesson_layout_complete=1'
