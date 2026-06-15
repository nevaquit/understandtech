#!/usr/bin/env bash
# End orphaned APLUS seed PHP from cancelled GitHub Actions runs (best-effort).
set -euo pipefail

/usr/bin/pkill -f 'seed-comptia-a-plus-course.php' 2>/dev/null || true
/usr/bin/pkill -f 'fix-aplus-course-filters.php' 2>/dev/null || true
/usr/bin/pkill -f 'enroll-aplus-default-users.php' 2>/dev/null || true
sleep 2
echo 'stale_aplus_seed_cleared=1'
