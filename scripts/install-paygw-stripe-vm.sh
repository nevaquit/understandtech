#!/usr/bin/env bash
# Install paygw_stripe from moodle.org on the production VM (Option A — not vendored in monorepo).
# Safe to re-run; skips if webhook.php already exists.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
TARGET="${MOODLE_DIR}/payment/gateway/stripe"

if [[ -f "${TARGET}/webhook.php" ]]; then
  echo "[ OK ] paygw_stripe already installed (${TARGET})"
  exit 0
fi

WORKDIR="$(mktemp -d)"
trap 'rm -rf "$WORKDIR"' EXIT
cd "$WORKDIR"

# moodle.org ZIP requires session cookies from the versions page; GitHub master matches 1.31 (2026020800).
zip_url="https://github.com/alexmorrisnz/moodle-paygw_stripe/archive/refs/heads/master.zip"
echo "Downloading ${zip_url}"
curl -fsSL -o paygw_stripe.zip "$zip_url"
file paygw_stripe.zip
unzip -q paygw_stripe.zip

src="$(find . -maxdepth 2 -type f -name version.php -path '*/moodle-paygw_stripe-*/version.php' 2>/dev/null | head -1)"
if [[ -n "$src" ]]; then
  src="$(dirname "$src")"
else
  src="$(find . -name version.php -path '*/stripe/version.php' 2>/dev/null | head -1)"
  if [[ -n "$src" ]]; then
    src="$(dirname "$src")"
  else
    src="$(find . -maxdepth 3 -type d -name stripe | head -1)"
  fi
fi
if [[ -z "$src" || ! -f "${src}/version.php" ]]; then
  echo "ERROR: stripe plugin directory not found in ZIP" >&2
  find . -maxdepth 4 -type f -name version.php >&2 || true
  exit 1
fi

mkdir -p "$(dirname "$TARGET")"
rm -rf "$TARGET"
cp -a "$src" "$TARGET"
chown -R www-data:www-data "$TARGET"
echo "[ OK ] paygw_stripe installed to ${TARGET}"
test -f "${TARGET}/webhook.php" && echo paygw_stripe=ok
