#!/usr/bin/env bash
set -euo pipefail
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
CMID="${1:-4}"
if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi
sudo -u www-data php "${REPO}/scripts/diagnose-page-cmid.php" "$CMID"
echo '=== curl page view ==='
curl -sS -o /tmp/page-view.html -w 'http:%{http_code}\n' "http://127.0.0.1/mod/page/view.php?id=${CMID}" || true
grep -oE 'Error reading from database|debuginfo|Cannot find|exception' /tmp/page-view.html | head -5 || echo 'no_obvious_error_in_guest_html'
tail -n 30 /var/www/moodle/data/moodledata/localcache/error.log 2>/dev/null || tail -n 30 /var/log/nginx/error.log 2>/dev/null || true
