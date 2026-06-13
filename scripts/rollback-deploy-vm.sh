#!/usr/bin/env bash
# Roll back plugin deploy on production VM to a known-good git SHA.
# Does NOT disable maintenance — caller must run ensure-origin-healthy-vm.sh and verify health.
set -euo pipefail

PLUGINS_REPO_DIR="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
ROLLBACK_SHA="${ROLLBACK_SHA:-}"

if [ -z "${ROLLBACK_SHA}" ]; then
  if [ -f /tmp/understandtech-pre-deploy-sha ]; then
    ROLLBACK_SHA="$(cat /tmp/understandtech-pre-deploy-sha)"
  fi
fi

if [ -z "${ROLLBACK_SHA}" ]; then
  echo "ERROR: ROLLBACK_SHA not set and no /tmp/understandtech-pre-deploy-sha" >&2
  exit 1
fi

echo "=== rollback to ${ROLLBACK_SHA} ==="
export SKIP_MOODLE_UPGRADE=1
cd "${PLUGINS_REPO_DIR}"
sudo -u gha-runner git fetch origin main
sudo -u gha-runner git reset --hard "${ROLLBACK_SHA}"

plugin_moodle_path() {
  local name="$1"
  case "$name" in
    local_*) echo "local/${name#local_}" ;;
    mod_*) echo "mod/${name#mod_}" ;;
    theme_*) echo "theme/${name#theme_}" ;;
    block_*) echo "blocks/${name#block_}" ;;
    qbehaviour_*) echo "question/behaviour/${name#qbehaviour_}" ;;
    *) return 1 ;;
  esac
}

for srcname in $(find "${PLUGINS_REPO_DIR}/moodle-plugins" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort); do
  [ -f "${PLUGINS_REPO_DIR}/moodle-plugins/${srcname}/version.php" ] || continue
  relpath="$(plugin_moodle_path "$srcname")" || continue
  src="${PLUGINS_REPO_DIR}/moodle-plugins/${srcname}/"
  dst="${MOODLE_DIR}/${relpath}/"
  sudo mkdir -p "$dst"
  sudo rsync -av --delete \
    --exclude='.git' --exclude='.github' --exclude='*.md' --exclude='tests/' \
    "$src" "$dst"
  sudo chown -R www-data:www-data "$dst"
  echo "rolled_back ${srcname}"
done

sudo php "${MOODLE_DIR}/admin/cli/purge_caches.php"
sudo /usr/bin/bash "${PLUGINS_REPO_DIR}/scripts/fix-moodle-chdir-quick-vm.sh"

# Skip file must exist before stabilize — sudo drops SKIP_MOODLE_UPGRADE from the env.
echo 1 | sudo tee /tmp/understandtech-skip-moodle-upgrade >/dev/null
sudo /usr/bin/bash "${PLUGINS_REPO_DIR}/scripts/post-deploy-stabilize-vm.sh"

echo "rollback_deploy_complete sha=${ROLLBACK_SHA}"
