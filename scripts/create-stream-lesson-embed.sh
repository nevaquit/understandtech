#!/usr/bin/env bash
# Generate Stream lesson embed snippet and optional smoke TEST_VIDEO_URL.
# User must upload a test video in Cloudflare Stream dashboard first.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

: "${STREAM_VIDEO_ID:?Set STREAM_VIDEO_ID from Cloudflare Stream dashboard}"
: "${STREAM_SIGNING_KID:?Set STREAM_SIGNING_KID from Stream signing keys}"
: "${STREAM_CUSTOMER_SUBDOMAIN:?Set STREAM_CUSTOMER_SUBDOMAIN e.g. customer-xxxxx}"

OUT="$SCRIPT_DIR/../docs/stream-lesson-embed-snippet.php"
cat > "$OUT" <<'SNIPPET'
<?php
// Paste into a Moodle Page resource (PHP filtered) after setting $videoid server-side.
// Do not expose video UID in client-side HTML without signed JWT refresh.
require_once($CFG->dirroot . '/local/certmaster/lib.php');
$videoid = getenv('STREAM_TEST_VIDEO_ID') ?: 'REPLACE_WITH_VIDEO_UID';
echo local_certmaster_render_stream_player($videoid);
SNIPPET

echo "Wrote embed snippet: $OUT"

if command -v bash >/dev/null && [ -x "$SCRIPT_DIR/generate-stream-signed-url.sh" ]; then
  export STREAM_VIDEO_ID STREAM_SIGNING_KID STREAM_CUSTOMER_SUBDOMAIN
  TEST_URL="$("$SCRIPT_DIR/generate-stream-signed-url.sh")"
  echo "TEST_VIDEO_URL=$TEST_URL"
  echo "Run smoke: TEST_VIDEO_URL=\"$TEST_URL\" PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh"
fi
