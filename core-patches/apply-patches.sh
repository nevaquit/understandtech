#!/usr/bin/env bash
# Apply Moodle core patches from core-patches/ during deploy.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"

if [ ! -d "$MOODLE_DIR" ]; then
  echo "core-patches: MOODLE_DIR not found ($MOODLE_DIR) — skipping"
  exit 0
fi

shopt -s nullglob
patches=("$SCRIPT_DIR"/*.patch)

if [ ${#patches[@]} -eq 0 ]; then
  echo "core-patches: no .patch files — nothing to apply"
  exit 0
fi

for patch in "${patches[@]}"; do
  echo "core-patches: applying $(basename "$patch")"
  # -N: skip hunks already applied (non-interactive deploys must not prompt).
  patch -p1 -N -d "$MOODLE_DIR" < "$patch"
done

echo "core-patches: done"
