#!/usr/bin/env bash
# Generate a 60-second Cloudflare Stream signed manifest URL for smoke tests.
#
# Requires:
#   STREAM_VIDEO_ID     — Cloudflare Stream video UID (from dashboard after upload)
#   CF_STREAM_SIGNING_KEY — PEM private key (or fetch from Key Vault)
#   STREAM_SIGNING_KID  — Signing key ID from Cloudflare Stream settings
#   STREAM_CUSTOMER_SUBDOMAIN — e.g. customer-abc123 (from Stream embed URL)
#
# Optional:
#   KEY_VAULT=utkvnhhwegpz3rem6  — read cf-stream-signing-key when env unset
#
# Usage:
#   export STREAM_VIDEO_ID=... STREAM_SIGNING_KID=... STREAM_CUSTOMER_SUBDOMAIN=customer-xxx
#   export CF_STREAM_SIGNING_KEY="$(az keyvault secret show ... --query value -o tsv)"
#   ./scripts/generate-stream-signed-url.sh
#   TEST_VIDEO_URL="$(./scripts/generate-stream-signed-url.sh)" ./scripts/smoke-test-deployment.sh
set -euo pipefail

KEY_VAULT="${KEY_VAULT:-utkvnhhwegpz3rem6}"
VIDEO_ID="${STREAM_VIDEO_ID:-}"
KID="${STREAM_SIGNING_KID:-}"
SUBDOMAIN="${STREAM_CUSTOMER_SUBDOMAIN:-}"
PEM="${CF_STREAM_SIGNING_KEY:-}"

if [[ -z "$PEM" && -n "$KEY_VAULT" ]] && command -v az >/dev/null 2>&1; then
  PEM="$(az keyvault secret show --vault-name "$KEY_VAULT" --name cf-stream-signing-key --query value -o tsv 2>/dev/null || true)"
fi

for var in VIDEO_ID KID SUBDOMAIN PEM; do
  if [[ -z "${!var}" ]]; then
    echo "ERROR: missing ${var} (see script header for env names)" >&2
    exit 1
  fi
done

if ! command -v python3 >/dev/null 2>&1; then
  echo "ERROR: python3 required for RS256 JWT signing" >&2
  exit 1
fi

pem_file="$(mktemp)"
trap 'rm -f "$pem_file"' EXIT
printf '%s\n' "$PEM" > "$pem_file"

python3 - "$pem_file" "$VIDEO_ID" "$KID" "$SUBDOMAIN" <<'PY'
import base64, json, sys, time
from pathlib import Path

try:
    from cryptography.hazmat.primitives import hashes, serialization
    from cryptography.hazmat.primitives.asymmetric import padding
except ImportError:
    sys.stderr.write("ERROR: pip install cryptography (or run on VM with python3-cryptography)\n")
    sys.exit(1)

pem_path, video_id, kid, subdomain = sys.argv[1:5]
key = serialization.load_pem_private_key(Path(pem_path).read_bytes(), password=None)

def b64url(data: bytes) -> str:
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode()

header = b64url(json.dumps({"alg": "RS256", "kid": kid}, separators=(",", ":")).encode())
now = int(time.time())
payload = b64url(json.dumps({
    "sub": video_id,
    "kid": kid,
    "exp": now + 60,
}, separators=(",", ":")).encode())
signing_input = f"{header}.{payload}".encode()
signature = key.sign(signing_input, padding.PKCS1v15(), hashes.SHA256())
token = f"{header}.{payload}.{b64url(signature)}"
print(f"https://{subdomain}.cloudflarestream.com/{token}/manifest/video.m3u8")
PY
