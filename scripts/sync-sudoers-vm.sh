#!/usr/bin/env bash
# Install gha-runner sudoers from monorepo checkout on the production VM.
# Safe to re-run; validates with visudo before leaving the file in place.
set -euo pipefail

REPO_DIR="${REPO_DIR:-/opt/understandtech-plugins}"
SUDOERS_SRC="${SUDOERS_SRC:-$REPO_DIR/infrastructure/runner/gha-runner-sudoers}"
DEST="/etc/sudoers.d/gha-runner"

if [[ ! -f "$SUDOERS_SRC" ]]; then
  echo "ERROR: sudoers source not found: $SUDOERS_SRC" >&2
  exit 1
fi

tmp="$(mktemp)"
cp "$SUDOERS_SRC" "$tmp"
chmod 0440 "$tmp"
visudo -cf "$tmp"

install -m 0440 "$tmp" "$DEST"
rm -f "$tmp"

echo "SUDOERS: installed from $SUDOERS_SRC"
visudo -cf "$DEST"
