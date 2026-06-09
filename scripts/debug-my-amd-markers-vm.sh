#!/usr/bin/env bash
# Debug authenticated /my/ HTML for theme AMD markers (run on VM).
set -euo pipefail

PROD="${STAGING_URL:-https://staging.understandtech.app/learn}"
PROD="${PROD%/learn}"
WWW="/learn"
E2E_USER="${MOODLE_E2E_USER:-e2etest}"
E2E_PASS="${MOODLE_E2E_PASS:-UtE2eTest2026Secure}"
CJ="/tmp/debug-my-cj"
LOGIN="/tmp/debug-my-html"

curl -sS -c "$CJ" -b "$CJ" "${PROD}${WWW}/login/index.php" -o "$LOGIN"
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' "$LOGIN" | head -1 || true)
curl -sS -c "$CJ" -b "$CJ" -L \
  --data-urlencode "username=${E2E_USER}" \
  --data-urlencode "password=${E2E_PASS}" \
  --data-urlencode "logintoken=${tok}" \
  "${PROD}${WWW}/login/index.php" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" "${PROD}${WWW}/my/" -o "$LOGIN"

echo "pagelayout=$(grep -oE 'pagelayout-[a-z]+' "$LOGIN" | head -1 || echo none)"
echo "title=$(grep -o '<title>[^<]*</title>' "$LOGIN" | head -1 || true)"
echo "logout_links=$(grep -c 'Log out' "$LOGIN" || true)"
echo "bytes=$(wc -c < "$LOGIN" | tr -d ' ')"
echo "body_tag=$(grep -oE '<body[^>]+>' "$LOGIN" | head -1 || true)"
echo "timeline_fallback_count=$(grep -c 'timeline_fallback' "$LOGIN" || true)"
echo "myoverview_fallback_count=$(grep -c 'myoverview_fallback' "$LOGIN" || true)"
echo "templates_dom_patch_count=$(grep -c 'templates_dom_patch' "$LOGIN" || true)"
grep -oE "require\(\['theme_understandtech/[^']+" "$LOGIN" | head -10 || true
