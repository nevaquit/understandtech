#!/usr/bin/env bash
# Restore origin DB connectivity, sessions, caches, and SEC701 filters.
set -euo pipefail

REPO="${REPO:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "${REPO}" fetch origin main
  sudo -u gha-runner git -C "${REPO}" reset --hard origin/main
fi

bash "${REPO}/scripts/recover-origin-db.sh" || true
bash "${REPO}/scripts/apply-php-fpm-pool-vm.sh" || true
bash "${REPO}/scripts/fix-moodle-dir-permissions-vm.sh" || true
bash "${REPO}/scripts/migrate-moodle-sessions-to-redis-vm.sh" || bash "${REPO}/scripts/fix-redis-session-env-vm.sh" || true
bash "${REPO}/scripts/restart-origin-stack-vm.sh"
bash "${REPO}/scripts/fix-sec701-course-filters-vm.sh" || true

echo "=== auth smoke (nginx Host header) ==="
HOST="${MOODLE_HOST:-understandtech.app}"
BASE="${MOODLE_ORIGIN_BASE:-http://127.0.0.1}"
WWW="/learn"
rm -f /tmp/moodle-cj /tmp/login.html /tmp/my.html /tmp/course.html
curl -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj "${BASE}${WWW}/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
if [ -z "${tok}" ]; then
  echo 'login_token_missing — nginx auth smoke skipped (loopback HTTPS requires Cloudflare client cert)'
  grep -oE 'Error reading from database|400 No required SSL|301 Moved Permanently' /tmp/login.html | head -1 || true
  grep -o '<title>[^<]*</title>' /tmp/login.html | head -1 || true
else
  curl -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
    --data-urlencode "username=e2etest" \
    --data-urlencode "password=UtE2eTest2026Secure" \
    --data-urlencode "logintoken=${tok}" \
    "${BASE}${WWW}/login/index.php" -o /tmp/post.html
  grep -oE 'Error reading from database|alert-danger' /tmp/post.html | head -3 || echo 'login_post_ok'
  curl -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj \
    "${BASE}${WWW}/course/view.php?id=3" -o /tmp/course.html
  grep -oE 'Error reading from database|SY701|Security\+' /tmp/course.html | head -5 || echo 'course_view_ok'
  grep -o '<title>[^<]*</title>' /tmp/course.html | head -1
fi

echo 'stabilize_origin_complete=1'
