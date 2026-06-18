#!/usr/bin/env bash
# Post-deploy / post-recovery stabilization: enrolment, theme sync, permissions, PHP-FPM recycle.
# Idempotent — safe after deploy, seed, DB recovery, or rollback.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

echo "=== sync gha-runner sudoers (restart-only policy) ==="
bash "${REPO}/scripts/sync-sudoers-vm.sh"

echo "=== SEC701 default enrolment ==="
sudo -u www-data php "${REPO}/scripts/enroll-sec701-default-users.php"

if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "/var/www/moodle/config.php";global $DB;exit($DB->record_exists("course",["shortname"=>"APLUS"])?0:1);' 2>/dev/null; then
  echo "=== APLUS default enrolment ==="
  sudo -u www-data php "${REPO}/scripts/enroll-aplus-default-users.php"
fi

if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "/var/www/moodle/config.php";global $DB;exit($DB->record_exists("course",["shortname"=>"NET009"])?0:1);' 2>/dev/null; then
  echo "=== NET009 default enrolment ==="
  sudo -u www-data php "${REPO}/scripts/enroll-net009-default-users.php"
fi

_wwwroot="$(sudo -u www-data php -r 'define("CLI_SCRIPT", true); require "/var/www/moodle/config.php"; echo $CFG->wwwroot;' 2>/dev/null || true)"
if [[ "${_wwwroot}" == *staging* ]]; then
  echo "=== staging E2E test user (health gate login) ==="
  export E2E_PASSWORD="${MOODLE_E2E_PASS:-UtE2eTest2026Secure}"
  bash "${REPO}/scripts/setup-e2e-test-user-vm.sh"
fi

echo "=== theme sync ==="
bash "${REPO}/scripts/sync-theme-understandtech-vm.sh"

echo "=== directory permissions + chdir verify ==="
bash "${REPO}/scripts/fix-moodle-dir-permissions-vm.sh"

echo "=== SEC701 page filter disable (prevents mod_page dmlreadexception) ==="
if [[ "${_wwwroot}" == *staging* ]]; then
  export SEC701_COURSE_ID="${SEC701_COURSE_ID:-2}"
else
  export SEC701_COURSE_ID="${SEC701_COURSE_ID:-3}"
fi
sudo -u www-data php "${REPO}/scripts/fix-sec701-course-filters.php"

if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "/var/www/moodle/config.php";global $DB;exit($DB->record_exists("course",["shortname"=>"APLUS"])?0:1);' 2>/dev/null; then
  echo "=== APLUS page filter disable ==="
  sudo -u www-data php "${REPO}/scripts/fix-aplus-course-filters.php"
fi

if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "/var/www/moodle/config.php";global $DB;exit($DB->record_exists("course",["shortname"=>"NET009"])?0:1);' 2>/dev/null; then
  echo "=== NET009 page filter disable ==="
  sudo -u www-data php "${REPO}/scripts/fix-net009-course-filters.php"
fi

echo "=== Study Coach block (block_studyplan) ==="
if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "/var/www/moodle/config.php";exit(is_dir($CFG->dirroot."/blocks/studyplan")?0:1);' 2>/dev/null; then
  sudo -u www-data php "${REPO}/scripts/seed-study-plan-block.php"
fi

# Redis flush + PHP-FPM restart recycle workers after theme/cache changes (purge_caches hangs on VM).
bash "${REPO}/scripts/restart-php-fpm-vm.sh"

echo 'post_deploy_stabilize_complete=1'
