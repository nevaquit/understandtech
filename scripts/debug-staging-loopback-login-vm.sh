#!/usr/bin/env bash
set -euo pipefail
URL="https://staging.understandtech.app/learn/login/index.php"
code=$(curl -sk --resolve staging.understandtech.app:443:127.0.0.1 -o /tmp/login.html -w '%{http_code}' "$URL")
echo "http_code=${code}"
echo "bytes=$(wc -c < /tmp/login.html | tr -d ' ')"
grep -o '<title>[^<]*</title>' /tmp/login.html | head -1 || true
grep -c logintoken /tmp/login.html || true
head -c 200 /tmp/login.html
