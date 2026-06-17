#!/usr/bin/env bash
# Seed CompTIA Network+ N10-009 course on Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

/usr/bin/pkill -f 'seed-network-plus-course.php' 2>/dev/null || true
/usr/bin/pkill -f 'fix-net009-course-filters.php' 2>/dev/null || true
/usr/bin/pkill -f 'enroll-net009-default-users.php' 2>/dev/null || true
sleep 2
echo 'stale_net009_seed_cleared=1'

sudo -u www-data timeout 480 php "${REPO}/scripts/seed-network-plus-course.php"
if [ "${NET009_SKIP_FILTER_FIX:-0}" != "1" ]; then
  sudo -u www-data timeout 120 php "${REPO}/scripts/fix-net009-course-filters.php"
else
  echo 'net009_filter_fix_skipped=1'
fi
echo 'seed_net009_complete=1'
