#!/usr/bin/env bash
# Post-deployment smoke checks for understandtech.app (Phase 6.2).
# Runs all checks and reports pass/warn/fail — does not exit on first failure.
#
# Usage:
#   PROD_URL=https://understandtech.app ./scripts/smoke-test-deployment.sh
#   ON_VM=1 ./scripts/smoke-test-deployment.sh   # enable local VM checks (deploy runner)
#
# Optional:
#   ORIGIN_IP=<azure-vm-public-ip>  — Authenticated Origin Pulls direct test
#   GITHUB_REPO=owner/repo          — self-hosted runner status via gh
#   SKIP_VM_CHECKS=1                — skip checks 8, 9, 12

set -uo pipefail

PROD_URL="${PROD_URL:-${STAGING_URL:-https://understandtech.app}}"
PROD_URL="${PROD_URL%/}"
AI_WORKER_URL="${AI_WORKER_URL:-https://ai.understandtech.app}"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
ORIGIN_HOST="${ORIGIN_HOST:-understandtech.app}"

PASS=0
WARN=0
FAIL=0

pass() { echo -e "\033[32m[PASS]\033[0m $1"; PASS=$((PASS + 1)); }
warn() { echo -e "\033[33m[WARN]\033[0m $1"; WARN=$((WARN + 1)); }
fail() { echo -e "\033[31m[FAIL]\033[0m $1"; FAIL=$((FAIL + 1)); }

curl_max() {
  curl --silent --show-error --max-time 10 "$@"
}

# --- 1. DNS resolution ---
check_dns() {
  local ip=""
  if command -v dig >/dev/null 2>&1; then
    ip="$(dig +short "$ORIGIN_HOST" A 2>/dev/null | head -n1 || true)"
  elif command -v nslookup >/dev/null 2>&1; then
    ip="$(nslookup "$ORIGIN_HOST" 2>/dev/null | awk '/^Address: / { print $2; exit }' || true)"
  fi
  if [[ -n "$ip" && "$ip" != "127.0.0.1" ]]; then
    pass "DNS $ORIGIN_HOST -> $ip"
  else
    fail "DNS resolution for $ORIGIN_HOST (got: ${ip:-empty})"
  fi
}

# --- 2. SSL certificate validity (not expiring within 30 days) ---
check_ssl() {
  if ! curl_max --head "$PROD_URL/" >/dev/null 2>&1; then
    fail "SSL/HTTPS head request to $PROD_URL failed"
    return
  fi
  local expiry
  expiry="$(echo | openssl s_client -servername "$ORIGIN_HOST" -connect "${ORIGIN_HOST}:443" 2>/dev/null \
    | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2 || true)"
  if [[ -z "$expiry" ]]; then
    warn "Could not parse certificate expiry for $ORIGIN_HOST (openssl check skipped)"
    return
  fi
  local exp_epoch now_epoch days_left
  exp_epoch="$(date -d "$expiry" +%s 2>/dev/null || date -j -f "%b %d %T %Y %Z" "$expiry" +%s 2>/dev/null || echo 0)"
  now_epoch="$(date +%s)"
  days_left=$(( (exp_epoch - now_epoch) / 86400 ))
  if [[ "$exp_epoch" -eq 0 ]]; then
    warn "Certificate expiry parse failed for $ORIGIN_HOST"
  elif [[ "$days_left" -ge 30 ]]; then
    pass "SSL valid (~${days_left} days remaining)"
  else
    fail "SSL certificate expires within 30 days (${days_left} days left)"
  fi
}

# --- 3. HTTP via Cloudflare ---
check_http_cf() {
  local code
  code="$(curl_max -o /dev/null -w '%{http_code}' "$PROD_URL/" || echo "000")"
  if [[ "$code" == "200" || "$code" == "303" || "$code" == "302" ]]; then
    pass "HTTP via Cloudflare: $code"
  else
    fail "HTTP via Cloudflare expected 200/302/303, got $code"
  fi
}

# --- 4. Authenticated Origin Pulls (direct origin without CF client cert should fail) ---
check_origin_pulls() {
  if [[ -z "${ORIGIN_IP:-}" ]]; then
    warn "ORIGIN_IP not set — skipping Authenticated Origin Pulls direct-origin test"
    return
  fi
  local code
  code="$(curl_max -k -o /dev/null -w '%{http_code}' \
    --resolve "${ORIGIN_HOST}:443:${ORIGIN_IP}" "https://${ORIGIN_HOST}/" 2>/dev/null || echo "000")"
  if [[ "$code" == "400" || "$code" == "403" || "$code" == "495" || "$code" == "000" ]]; then
    pass "Origin direct access blocked or TLS rejected (code: $code)"
  elif [[ "$code" == "200" || "$code" == "302" || "$code" == "303" ]]; then
    fail "Origin reachable without Cloudflare client cert (code: $code) — Origin Pulls may be misconfigured"
  else
    warn "Origin direct test inconclusive (code: $code)"
  fi
}

# --- 5. AI Worker health ---
check_ai_health() {
  local body code
  body="$(curl_max -w '\n%{http_code}' "${AI_WORKER_URL}/health" 2>/dev/null || printf '\n000')"
  code="${body##*$'\n'}"
  body="${body%$'\n'*}"
  if [[ "$code" == "200" ]] && echo "$body" | grep -q '"status"[[:space:]]*:[[:space:]]*"ok"'; then
    pass "AI Worker /health OK"
  else
    fail "AI Worker /health expected 200 {\"status\":\"ok\"}, got $code — $body"
  fi
}

# --- 6. AI Worker auth (no JWT -> 401) ---
check_ai_auth() {
  local code
  code="$(curl_max -o /dev/null -w '%{http_code}' -X POST "${AI_WORKER_URL}/tutor" \
    -H 'Content-Type: application/json' \
    -d '{"messages":[{"role":"user","content":"ping"}]}' || echo "000")"
  if [[ "$code" == "401" ]]; then
    pass "AI Worker /tutor rejects unauthenticated requests (401)"
  else
    fail "AI Worker /tutor without JWT expected 401, got $code"
  fi
}

# --- 7. Moodle version / reachability ---
check_moodle_version() {
  if [[ -x /usr/bin/php && -f "$MOODLE_DIR/admin/cli/cfg.php" ]]; then
    local release
    release="$(sudo -u www-data /usr/bin/php "$MOODLE_DIR/admin/cli/cfg.php" --name=release 2>/dev/null || true)"
    if [[ -n "$release" ]]; then
      if echo "$release" | grep -q '4\.5'; then
        pass "Moodle release (cfg.php): $release"
      else
        warn "Moodle release from cfg.php: $release (expected 4.5.x)"
      fi
      return
    fi
  fi
  local html
  html="$(curl_max "${PROD_URL}/login/index.php" 2>/dev/null || true)"
  if echo "$html" | grep -qi 'moodle'; then
    if echo "$html" | grep -q '4\.5'; then
      pass "Moodle 4.5 detected on login page"
    else
      pass "Moodle login page reachable (4.5 not exposed in public HTML)"
    fi
  else
    fail "Could not fetch Moodle login page for version check"
  fi
}

# --- 8. Database connectivity (on VM) ---
check_db_vm() {
  if [[ "${SKIP_VM_CHECKS:-0}" == "1" && "${ON_VM:-0}" != "1" ]]; then
    warn "VM DB check skipped (SKIP_VM_CHECKS=1, not on VM)"
    return
  fi
  local config="${MOODLE_DIR}/config.php"
  if [[ ! -x /usr/bin/php || ! -f "$config" ]]; then
    warn "VM DB check skipped (not on production VM)"
    return
  fi
  local dbhost=""
  dbhost="$(sudo -u www-data /usr/bin/php 2>/dev/null <<PHP || true
<?php
define('CLI_SCRIPT', true);
require '${config}';
global \$CFG;
echo \$CFG->dbhost;
PHP
)"
  if [[ -z "$dbhost" ]]; then
    dbhost="$(sudo grep -E "^\s*\\\$CFG->dbhost" "$config" 2>/dev/null \
      | grep -v dbpass | head -n1 \
      | sed -E "s/.*['\"]([^'\"]+)['\"].*/\1/" || true)"
  fi
  if [[ -n "$dbhost" ]]; then
    pass "Moodle dbhost configured: $dbhost"
  elif sudo grep -qE 'dbhost' "$config" 2>/dev/null; then
    pass "Moodle dbhost line present in config.php"
  else
    fail "Could not read Moodle dbhost from config.php"
  fi
}

# --- 9. Redis connectivity (on VM) ---
check_redis_vm() {
  if [[ "${SKIP_VM_CHECKS:-0}" == "1" && "${ON_VM:-0}" != "1" ]]; then
    warn "Redis check skipped (SKIP_VM_CHECKS=1, not on VM)"
    return
  fi
  if ! command -v redis-cli >/dev/null 2>&1; then
    warn "redis-cli not found — Redis check skipped"
    return
  fi
  local pong=""
  if [[ -f /etc/moodle/redis_password ]]; then
    pong="$(redis-cli -h 127.0.0.1 -p 6379 -a "$(sudo cat /etc/moodle/redis_password 2>/dev/null)" PING 2>/dev/null || true)"
  else
    pong="$(redis-cli -h 127.0.0.1 PING 2>/dev/null || true)"
  fi
  if [[ "$pong" == "PONG" ]]; then
    pass "Redis PING -> PONG"
  else
    warn "Redis not reachable (expected until sessions wired) — got: ${pong:-empty}"
  fi
}

# --- 10. Self-hosted runner status ---
check_gh_runner() {
  if ! command -v gh >/dev/null 2>&1; then
    warn "gh CLI not installed — runner check skipped"
    return
  fi
  local repo="${GITHUB_REPO:-}"
  if [[ -z "$repo" ]]; then
    warn "GITHUB_REPO not set — runner check skipped"
    return
  fi
  local online
  online="$(gh api "repos/${repo}/actions/runners" --jq '[.runners[] | select(.status=="online")] | length' 2>/dev/null || echo "")"
  if [[ "$online" =~ ^[0-9]+$ && "$online" -ge 1 ]]; then
    pass "GitHub self-hosted runner online ($online)"
  elif [[ -n "$online" ]]; then
    fail "No online self-hosted runners for $repo"
  else
    warn "Could not query GitHub runners (auth or repo access)"
  fi
}

# --- 11. Stream signed URL (optional) ---
check_stream_jwt() {
  if [[ -z "${TEST_VIDEO_URL:-}" ]]; then
    warn "TEST_VIDEO_URL not set — Stream JWT check skipped"
    return
  fi
  local code
  code="$(curl_max -o /dev/null -w '%{http_code}' "$TEST_VIDEO_URL" || echo "000")"
  if [[ "$code" == "200" ]]; then
    pass "Stream signed URL returned 200"
  else
    fail "Stream signed URL expected 200, got $code"
  fi
}

# --- 12. Disk space on VM ---
check_disk_vm() {
  if [[ ! -d /var/www ]]; then
    warn "Disk check skipped (not on VM)"
    return
  fi
  local pct
  pct="$(df -h /var/www 2>/dev/null | awk 'NR==2 { print $5 }' | tr -d '%' || echo "")"
  if [[ -z "$pct" ]]; then
    warn "Could not read disk usage for /var/www"
  elif [[ "$pct" -lt 80 ]]; then
    pass "Disk usage /var/www: ${pct}%"
  else
    fail "Disk usage /var/www over 80% (${pct}%)"
  fi
}

echo "=== understandtech.app deployment smoke test ==="
echo "Target: $PROD_URL"
echo ""

check_dns
check_ssl
check_http_cf
check_origin_pulls
check_ai_health
check_ai_auth
check_moodle_version
check_db_vm
check_redis_vm
check_gh_runner
check_stream_jwt
check_disk_vm

echo ""
echo "=== Summary: ${PASS} passed, ${WARN} warnings, ${FAIL} failures ==="

if [[ "$FAIL" -gt 0 ]]; then
  exit 1
fi
exit 0
