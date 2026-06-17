#!/usr/bin/env bash
# Sync moodle-plugins from monorepo into Moodle dirroot on VM.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
SRC="${1:-${SRC:-${REPO_ROOT}/moodle-plugins}}"
MOODLE="${MOODLE_DIR:-/var/www/moodle}"

# Map monorepo folder names to Moodle dirroot paths.
declare -A PLUGIN_MAP=(
  [theme_understandtech]="theme/understandtech"
  [local_certmaster]="local/certmaster"
  [local_aitutor]="local/aitutor"
  [local_aigrading]="local/aigrading"
  [local_gamification]="local/gamification"
  [local_community]="local/community"
  [local_integrations]="local/integrations"
  [block_examreadiness]="block/examreadiness"
  [block_studyplan]="block/studyplan"
  [block_portfolio]="block/portfolio"
  [mod_ctfflag]="mod/ctfflag"
  [qbehaviour_certmasterconfidence]="question/behaviour/certmasterconfidence"
)

for srcname in "${!PLUGIN_MAP[@]}"; do
  relpath="${PLUGIN_MAP[$srcname]}"
  type="${relpath%%/*}"
  name="${relpath#*/}"
  srcpath="$SRC/$srcname"
  dstpath="$MOODLE/$relpath"
  if [ ! -d "$srcpath" ]; then
    continue
  fi
  if [ ! -f "$srcpath/version.php" ]; then
    if [ -d "$dstpath" ]; then
      sudo rm -rf "$dstpath"
      echo "removed placeholder $relpath"
    fi
    continue
  fi
  sudo mkdir -p "$(dirname "$dstpath")"
  sudo rsync -a --delete "$srcpath/" "$dstpath/"
  echo "deployed $relpath"
done

sudo chown -R www-data:www-data "$MOODLE/theme" "$MOODLE/local" "$MOODLE/block" "$MOODLE/mod" "$MOODLE/question/behaviour" 2>/dev/null || true
echo "plugins deployed"

REPO="${PLUGINS_REPO_DIR:-$(cd "${SCRIPT_DIR}/.." && pwd)}"
if [ -x "${REPO}/scripts/post-deploy-stabilize-vm.sh" ]; then
  bash "${REPO}/scripts/post-deploy-stabilize-vm.sh"
fi
