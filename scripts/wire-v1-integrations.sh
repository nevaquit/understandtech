#!/usr/bin/env bash
# Orchestrate post-v1.0.0 integrations: Stripe, Postmark, Stream lesson embed.
# Requires: az login, Key Vault secrets populated per docs/v1-release-integrations.md
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
VAULT_NAME="${VAULT_NAME:-utkvnhhwegpz3rem6}"

echo "=== understandtech v1 integration wiring ==="

stripe_ok=0
postmark_ok=0
stream_ok=0

if az keyvault secret show --vault-name "$VAULT_NAME" --name stripe-secret-key --query value -o tsv 2>/dev/null | grep -qv '^$'; then
  echo "[OK] Stripe secrets in Key Vault"
  stripe_ok=1
else
  echo "[SKIP] Stripe — run: ./scripts/stripe-kv-setup-interactive.ps1 then ./scripts/configure-stripe-remote.sh"
fi

if az keyvault secret show --vault-name "$VAULT_NAME" --name postmark-server-token --query value -o tsv 2>/dev/null | grep -qv '^$'; then
  echo "[OK] Postmark token in Key Vault"
  postmark_ok=1
else
  echo "[SKIP] Postmark — store postmark-server-token then run: ./scripts/setup-postmark-smtp-remote.sh"
fi

if az keyvault secret show --vault-name "$VAULT_NAME" --name cf-stream-signing-key --query value -o tsv 2>/dev/null | grep -qv '^REPLACE'; then
  echo "[OK] Stream signing key in Key Vault"
  stream_ok=1
else
  echo "[SKIP] Stream — populate cf-stream-signing-key in Key Vault"
fi

if [ "$stripe_ok" -eq 1 ]; then
  bash "$SCRIPT_DIR/configure-stripe-remote.sh" || echo "[WARN] Stripe VM config failed"
fi

if [ "$postmark_ok" -eq 1 ]; then
  bash "$SCRIPT_DIR/setup-postmark-smtp-remote.sh" || echo "[WARN] Postmark SMTP wiring failed"
fi

if [ "$stream_ok" -eq 1 ]; then
  bash "$SCRIPT_DIR/create-stream-lesson-embed.sh" || echo "[WARN] Stream embed helper — set STREAM_VIDEO_ID manually"
fi

echo "=== Done — see docs/v1-release-integrations.md for manual dashboard steps ==="
