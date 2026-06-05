#!/usr/bin/env bash
# Remove Moodle plugin dirs for monorepo folders that lack version.php.
# Invoked via passwordless sudo from the deploy workflow (see gha-runner-sudoers).
set -euo pipefail

PLUGINS_REPO_DIR="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"

plugin_moodle_path() {
  local name="$1"
  case "$name" in
    local_*) echo "local/${name#local_}" ;;
    mod_*) echo "mod/${name#mod_}" ;;
    theme_*) echo "theme/${name#theme_}" ;;
    block_*) echo "blocks/${name#block_}" ;;
    qbehaviour_*) echo "question/behaviour/${name#qbehaviour_}" ;;
    *)
      echo "Unknown plugin prefix: $name" >&2
      return 1
      ;;
  esac
}

for srcpath in "${PLUGINS_REPO_DIR}"/moodle-plugins/*/; do
  [ -d "$srcpath" ] || continue
  srcname="$(basename "$srcpath")"
  if [ -f "${srcpath}version.php" ]; then
    continue
  fi
  relpath="$(plugin_moodle_path "$srcname")"
  dst="${MOODLE_DIR}/${relpath}"
  if [ -d "$dst" ]; then
    rm -rf "$dst"
    echo "Removed placeholder dir (no version.php): $dst"
  fi
done
