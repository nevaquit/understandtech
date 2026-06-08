#!/usr/bin/env bash
# Restore origin DB connectivity, sessions, caches, and SEC701 filters.
set -euo pipefail

REPO="${REPO:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "${REPO}" fetch origin main
  sudo -u gha-runner git -C "${REPO}" reset --hard origin/main
fi

bash "${REPO}/scripts/recover-origin-db.sh" || true
bash "${REPO}/scripts/fix-redis-session-env-vm.sh" || true
bash "${REPO}/scripts/restart-origin-stack-vm.sh"
bash "${REPO}/scripts/fix-sec701-course-filters-vm.sh" || true

echo "=== auth smoke (nginx Host header) ==="
HOST="${MOODLE_HOST:-understandtech.app}"
BASE="https://127.0.0.1"
WWW="/learn"
rm -f /tmp/moodle-cj /tmp/login.html /tmp/my.html /tmp/course.html
curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj "${BASE}${WWW}/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
if [ -z "${tok}" ]; then
  echo 'login_token_missing'
  grep -oE 'Error reading from database' /tmp/login.html | head -1 || true
  exit 1
fi
curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=e2etest" \
  --data-urlencode "password=UtE2eTest2026Secure" \
  --data-urlencode "logintoken=${tok}" \
  "${BASE}${WWW}/login/index.php" -o /tmp/post.html
grep -oE 'Error reading from database|alert-danger' /tmp/post.html | head -3 || echo 'login_post_ok'
curl -k -sS -H "Host: ${HOST}" -b /tmp/moodle-cj -c /tmp/moodle-cj \
  "${BASE}${WWW}/course/view.php?id=3" -o /tmp/course.html
grep -oE 'Error reading from database|SY701|Security\+' /tmp/course.html | head -5 || echo 'course_view_ok'
grep -o '<title>[^<]*</title>' /tmp/course.html | head -1

echo 'stabilize_origin_complete=1'
