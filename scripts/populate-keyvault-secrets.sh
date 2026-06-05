#!/usr/bin/env bash
# Populate understandtech Key Vault secrets that still hold REPLACE-ME placeholders.
# Reads from environment variables or interactive prompts (input hidden via read -s).
# Never echoes secret values.
#
# Env vars -> Key Vault:
#   ANTHROPIC_API_KEY / OPENAI_API_KEY / CF_STREAM_SIGNING_KEY
#   AITUTOR_WORKER_SHARED_SECRET or CF_WORKER_SHARED_SECRET
#
# Usage:
#   export ANTHROPIC_API_KEY='...'
#   ./scripts/populate-keyvault-secrets.sh
#   ./scripts/populate-keyvault-secrets.sh --generate-worker-secret

set -euo pipefail

KEY_VAULT="${KEY_VAULT:-utkvnhhwegpz3rem6}"
GENERATE_WORKER=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --vault) KEY_VAULT="$2"; shift 2 ;;
    --generate-worker-secret) GENERATE_WORKER=1; shift ;;
    -h|--help)
      sed -n '2,12p' "$0"
      exit 0
      ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

az account show -o none >/dev/null 2>&1 || { echo 'Run: az login' >&2; exit 1; }

get_env() {
  local names=("$@")
  for n in "${names[@]}"; do
    if [[ -n "${!n:-}" ]]; then
      printf '%s' "${!n}"
      return 0
    fi
  done
  return 1
}

random_worker_secret() {
  openssl rand -base64 32
}

set_secret() {
  local name="$1" value="$2"
  az keyvault secret set --vault-name "$KEY_VAULT" --name "$name" --value "$value" -o none >/dev/null
}

declare -A SECRET_ENVS=(
  [anthropic-api-key]="ANTHROPIC_API_KEY"
  [openai-api-key]="OPENAI_API_KEY"
  [cf-stream-signing-key]="CF_STREAM_SIGNING_KEY CLOUDFLARE_STREAM_SIGNING_KEY"
  [cf-worker-shared-secret]="AITUTOR_WORKER_SHARED_SECRET CF_WORKER_SHARED_SECRET"
)

echo "Key Vault: $KEY_VAULT"

for kv_name in anthropic-api-key openai-api-key cf-stream-signing-key cf-worker-shared-secret; do
  current="$(az keyvault secret show --vault-name "$KEY_VAULT" --name "$kv_name" --query value -o tsv)"
  if [[ "$current" != "REPLACE-ME" && -n "$current" ]]; then
    echo "[skip] $kv_name already configured"
    continue
  fi

  IFS=' ' read -r -a env_names <<< "${SECRET_ENVS[$kv_name]}"
  value=""
  if value="$(get_env "${env_names[@]}")"; then
    :
  elif [[ "$kv_name" == "cf-worker-shared-secret" && "$GENERATE_WORKER" -eq 1 ]]; then
    value="$(random_worker_secret)"
    echo "[gen]  $kv_name generated random value (use same in Cloudflare Worker secrets)"
  else
    env_hint="${env_names[*]}"
    printf 'Enter value for %s (env: %s): ' "$kv_name" "$env_hint" >&2
    read -rs value
    echo >&2
  fi

  if [[ -z "$value" || "$value" == "REPLACE-ME" ]]; then
    echo "No valid value for $kv_name" >&2
    exit 1
  fi

  set_secret "$kv_name" "$value"
  echo "[set]  $kv_name updated"
done

echo ''
echo 'Validation:'
ok=1
for kv_name in anthropic-api-key openai-api-key cf-stream-signing-key cf-worker-shared-secret; do
  val="$(az keyvault secret show --vault-name "$KEY_VAULT" --name "$kv_name" --query value -o tsv)"
  if [[ "$val" == "REPLACE-ME" || -z "$val" ]]; then
    echo "[FAIL] $kv_name still REPLACE-ME or empty"
    ok=0
  else
    echo "[ OK ] $kv_name configured"
  fi
done

[[ "$ok" -eq 1 ]] || exit 1
echo ''
echo 'Next: pwsh ./scripts/setup-moodle-env-vm.ps1'
