#!/usr/bin/env bash
# Sync moodle-plugins from monorepo into local Moodle dirroot (./moodle).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="${1:-$ROOT/moodle-plugins}"
MOODLE="${MOODLE_DIR:-$ROOT/moodle}"

declare -A PLUGIN_MAP=(
  [theme_understandtech]="theme/understandtech"
  [local_certmaster]="local/certmaster"
  [local_aitutor]="local/aitutor"
  [local_aigrading]="local/aigrading"
  [local_gamification]="local/gamification"
  [local_community]="local/community"
  [local_integrations]="local/integrations"
  [block_examreadiness]="block/examreadiness"
  [block_portfolio]="block/portfolio"
  [mod_ctfflag]="mod/ctfflag"
  [qbehaviour_certmasterconfidence]="question/behaviour/certmasterconfidence"
)

if [ ! -d "$MOODLE" ]; then
  echo "Moodle dirroot not found: $MOODLE (clone MOODLE_405_STABLE first)" >&2
  exit 1
fi

for srcname in "${!PLUGIN_MAP[@]}"; do
  relpath="${PLUGIN_MAP[$srcname]}"
  srcpath="$SRC/$srcname"
  dstpath="$MOODLE/$relpath"
  if [ ! -d "$srcpath" ]; then
    continue
  fi
  if [ ! -f "$srcpath/version.php" ]; then
    if [ -d "$dstpath" ]; then
      rm -rf "$dstpath"
      echo "removed placeholder $relpath"
    fi
    continue
  fi
  mkdir -p "$(dirname "$dstpath")"
  if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete "$srcpath/" "$dstpath/"
  else
    rm -rf "$dstpath"
    cp -R "$srcpath" "$dstpath"
  fi
  echo "deployed $relpath"
done

echo "plugins synced to $MOODLE"
