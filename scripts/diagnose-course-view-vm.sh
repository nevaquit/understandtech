#!/usr/bin/env bash
# Diagnose SEC701 course view DB errors on production VM.
set -euo pipefail

REPO="${REPO:-/opt/understandtech-plugins}"
COURSE_ID="${1:-3}"

echo "=== recover db if needed ==="
bash "${REPO}/scripts/recover-origin-db.sh" || true

echo "=== moodle error log ==="
tail -n 40 /var/www/moodledata/error.log 2>/dev/null || echo 'no_error_log'

echo "=== cli course lookup ==="
sudo -u www-data php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
require_once(\$CFG->dirroot . '/course/lib.php');
global \$DB;
\$c = \$DB->get_record('course', ['id' => ${COURSE_ID}]);
echo \$c ? 'cli_course=' . \$c->fullname . PHP_EOL : 'cli_course_missing' . PHP_EOL;
try {
    get_fast_modinfo(${COURSE_ID});
    echo 'modinfo_ok=1' . PHP_EOL;
} catch (Throwable \$e) {
    echo 'modinfo_error=' . \$e->getMessage() . PHP_EOL;
}
"

echo "=== fix page filters + purge ==="
bash "${REPO}/scripts/fix-sec701-page-filters-vm.sh" || true
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

echo "=== web course view (localhost /learn) ==="
rm -f /tmp/moodle-cj /tmp/login.html /tmp/course.html
BASE="https://127.0.0.1"
WWW="/learn"
curl -k -sS -b /tmp/moodle-cj -c /tmp/moodle-cj "${BASE}${WWW}/login/index.php" -o /tmp/login.html
tok=$(grep -oP 'name="logintoken" value="\K[^"]+' /tmp/login.html | head -1 || true)
if [ -z "${tok}" ]; then
  echo 'login_token_missing'
else
  curl -k -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
    --data-urlencode "username=e2etest" \
    --data-urlencode "password=UtE2eTest2026Secure" \
    --data-urlencode "logintoken=${tok}" \
    "${BASE}${WWW}/login/index.php" -o /tmp/login-out.html
  curl -k -sS -b /tmp/moodle-cj -c /tmp/moodle-cj -L \
    -o /tmp/course.html -w "course_http:%{http_code}\n" \
    "${BASE}${WWW}/course/view.php?id=${COURSE_ID}"
  grep -o '<title>[^<]*</title>' /tmp/course.html | head -1
  grep -oE 'Error reading from database|SEC701|SY701|alert-danger' /tmp/course.html | head -10 || echo 'course_content_ok'
fi

echo "=== done ==="
