#!/usr/bin/env bash
# Generate PgBouncer userlist.txt SCRAM line for moodle_user.
# Usage:
#   MOODLE_APP_PASSWORD="$(az keyvault secret show --vault-name utkvnhhwegpz3rem6 --name moodle-app-password --query value -o tsv)" \
#     ./scripts/generate-pgbouncer-hash.sh
# Or: ./scripts/generate-pgbouncer-hash.sh 'plain-password'
set -euo pipefail

PASSWORD="${1:-${MOODLE_APP_PASSWORD:-}}"
USERNAME="${PGUSER:-moodle_user}"

if [ -z "$PASSWORD" ]; then
  echo "Usage: MOODLE_APP_PASSWORD=... $0   OR   $0 <password>" >&2
  exit 1
fi

if command -v psql >/dev/null 2>&1; then
  HASH=$(psql -tAc "SELECT rolpassword FROM pg_roles WHERE rolname = '$USERNAME'" 2>/dev/null || true)
  if [ -n "$HASH" ] && [ "$HASH" != "" ]; then
    echo "\"$USERNAME\" \"$HASH\""
    exit 0
  fi
fi

python3 - "$USERNAME" "$PASSWORD" <<'PY'
import hashlib, hmac, secrets, base64, sys
username, password = sys.argv[1], sys.argv[2]
salt = secrets.token_bytes(16)
iterations = 4096
salted = hashlib.pbkdf2_hmac('sha256', password.encode('utf-8'), salt, iterations)
client_key = hmac.new(salted, b'Client Key', hashlib.sha256).digest()
stored_key = hashlib.sha256(client_key).digest()
server_key = hmac.new(salted, b'Server Key', hashlib.sha256).digest()
s = base64.b64encode(salt).decode()
sk = base64.b64encode(stored_key).decode()
ssk = base64.b64encode(server_key).decode()
print(f'"{username}" "SCRAM-SHA-256${iterations}:{s}${sk}:{ssk}"')
PY
