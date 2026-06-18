#!/usr/bin/env bash
# Seed CompTIA A+ certification course on Moodle (run on VM).
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

if [ -d "${REPO}/.git" ]; then
  sudo -u gha-runner git -C "$REPO" fetch origin main
  sudo -u gha-runner git -C "$REPO" reset --hard origin/main
fi

# End orphaned APLUS seed PHP from cancelled workflow runs (this script runs as root).
/usr/bin/pkill -f 'seed-comptia-a-plus-course.php' 2>/dev/null || true
/usr/bin/pkill -f 'fix-aplus-course-filters.php' 2>/dev/null || true
/usr/bin/pkill -f 'enroll-aplus-default-users.php' 2>/dev/null || true
sleep 2
echo 'stale_aplus_seed_cleared=1'

sudo -u www-data timeout 900 php "${REPO}/scripts/seed-comptia-a-plus-course.php"
if [ "${APLUS_SKIP_FILTER_FIX:-0}" != "1" ]; then
  sudo -u www-data timeout 120 php "${REPO}/scripts/fix-aplus-course-filters.php"
else
  echo 'aplus_filter_fix_skipped=1'
fi
echo 'seed_aplus_complete=1'
