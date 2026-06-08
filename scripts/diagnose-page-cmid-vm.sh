#!/usr/bin/env bash
set -euo pipefail
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
CMID="${1:-4}"
if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi
sudo -u www-data php "${REPO}/scripts/diagnose-page-cmid.php" "$CMID"
echo '=== authenticated page view ==='
rm -f /tmp/moodle-cj /tmp/login.html /tmp/page-view.html
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj "http://127.0.0.1/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1)
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  --data-urlencode "username=e2etest" \
  --data-urlencode "password=UtE2eTest2026Secure" \
  --data-urlencode "logintoken=${tok}" \
  "http://127.0.0.1/login/index.php" -o /tmp/login-out.html
curl -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
  -o /tmp/page-view.html -w 'page_http:%{http_code}\n' \
  "http://127.0.0.1/mod/page/view.php?id=${CMID}"
grep -oE 'Error reading from database|debuginfo|SY701\.1\.2|ut-lesson-content|alert-danger' /tmp/page-view.html | head -20 || true
grep -o '<title>[^<]*</title>' /tmp/page-view.html | head -1
echo '=== moodle error log ==='
find /var/www/moodle/data -name 'error.log' 2>/dev/null | head -3
tail -n 40 /var/www/moodle/data/moodledata/error.log 2>/dev/null || true
