#!/usr/bin/env bash
set -euo pipefail
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
CMID="${1:-4}"
if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

echo '=== moodle config wwwroot ==='
grep -E 'wwwroot|dirroot' /var/www/moodle/config.php | head -5

echo '=== error log tail ==='
tail -n 50 /var/www/moodle/data/moodledata/error.log 2>/dev/null || echo 'no_error_log'

echo '=== web simulate admin ==='
sudo -u www-data php "${REPO}/scripts/diagnose-page-web.php" "$CMID" admin

echo '=== localhost curl /learn/ path ==='
curl -sS -o /tmp/page-learn.html -w 'learn_http:%{http_code}\n' "http://127.0.0.1/learn/mod/page/view.php?id=${CMID}" || true
grep -oE 'Error reading from database|debuginfo|ut-lesson-content' /tmp/page-learn.html | head -5 || echo 'learn_guest_ok'

echo '=== localhost curl /mod/ path ==='
curl -sS -o /tmp/page-mod.html -w 'mod_http:%{http_code}\n' "http://127.0.0.1/mod/page/view.php?id=${CMID}" || true
grep -oE 'Error reading from database|debuginfo|ut-lesson-content' /tmp/page-mod.html | head -5 || echo 'mod_guest_ok'
