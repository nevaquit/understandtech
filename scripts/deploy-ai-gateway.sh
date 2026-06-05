#!/usr/bin/env bash
# Phase 4.3 — deploy Cloudflare AI Gateway Worker (secrets via wrangler, never in repo).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORKER_DIR="${ROOT}/cloudflare-worker/ai-gateway"
WRANGLER_JSONC="${WORKER_DIR}/wrangler.jsonc"

log() { echo "[deploy-ai-gateway] $*"; }
die() { echo "[deploy-ai-gateway] ERROR: $*" >&2; exit 1; }

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing required command: $1"
}

check_placeholders() {
  local issues=0
  if grep -q 'REPLACE_WITH_KV_NAMESPACE_ID' "$WRANGLER_JSONC"; then
    log "wrangler.jsonc still has REPLACE_WITH_KV_NAMESPACE_ID"
    issues=1
  fi
  if grep -q 'REPLACE_ACCOUNT' "$WRANGLER_JSONC"; then
    log "wrangler.jsonc still has REPLACE_ACCOUNT in AI_GATEWAY_URL"
    issues=1
  fi
  if [ "$issues" -ne 0 ]; then
    cat >&2 <<'EOF'

Fix placeholders before deploy:
  1. Create KV namespace:
       cd cloudflare-worker/ai-gateway
       npx wrangler kv namespace create PROMPT_CACHE
     Copy the id into wrangler.jsonc → kv_namespaces[0].id

  2. Set AI_GATEWAY_URL in wrangler.jsonc vars:
       https://gateway.ai.cloudflare.com/v1/<account-id>/understandtech

  3. Authenticate (once per machine):
       npx wrangler login

  4. Set secrets (paste from Azure Key Vault — never commit):
       npx wrangler secret put MOODLE_JWT_SECRET
       npx wrangler secret put MOODLE_WEBHOOK_HMAC_SECRET
       npx wrangler secret put ANTHROPIC_API_KEY
       npx wrangler secret put OPENAI_API_KEY

EOF
    exit 1
  fi
}

main() {
  require_cmd npm
  require_cmd npx

  [ -d "$WORKER_DIR" ] || die "Worker directory not found: $WORKER_DIR"

  if ! npx wrangler whoami >/dev/null 2>&1; then
    die "Not authenticated — run: cd cloudflare-worker/ai-gateway && npx wrangler login"
  fi

  check_placeholders

  log "Installing dependencies"
  cd "$WORKER_DIR"
  npm ci
  npm run typecheck

  log "Deploying understandtech-ai-gateway"
  npx wrangler deploy

  log "Done. Route: https://ai.understandtech.app/*"
}

main "$@"
