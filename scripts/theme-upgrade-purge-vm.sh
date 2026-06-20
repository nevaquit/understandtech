#!/usr/bin/env bash
# Sync theme_understandtech, upgrade, force SCSS rebuild, apply core patches, purge caches.
set -euo pipefail

REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"

echo "=== apply core patches ==="
if [ -x "${REPO}/core-patches/apply-patches.sh" ]; then
  bash "${REPO}/core-patches/apply-patches.sh"
else
  echo "skip: no core-patches/apply-patches.sh"
fi

echo "=== theme sync + Moodle upgrade ==="
bash "${REPO}/scripts/sync-theme-understandtech-vm.sh"

echo "=== force theme SCSS rebuild ==="
sudo -u www-data php -r '
define("CLI_SCRIPT", true);
require "'"${MOODLE_DIR}"'/config.php";
set_config("theme", "understandtech");
theme_reset_all_caches();
$dir = $CFG->dataroot . "/localcache/theme";
if (is_dir($dir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }
    echo "cleared_localcache_theme=1" . PHP_EOL;
}
$rev = get_config("core", "themerev");
echo "themerev=" . $rev . PHP_EOL;
echo "site_theme=" . get_config("core", "theme") . PHP_EOL;
'

if [ -f "${MOODLE_DIR}/admin/cli/build_theme_css.php" ]; then
  echo "=== build_theme_css understandtech ==="
  sudo -u www-data php "${MOODLE_DIR}/admin/cli/build_theme_css.php" --themes=understandtech || true
fi

echo "=== purge Moodle caches ==="
sudo -u www-data php "${MOODLE_DIR}/admin/cli/purge_caches.php"

echo "=== cert course filter disable ==="
if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "'"${MOODLE_DIR}"'/config.php";global $DB;exit($DB->record_exists("course",["shortname"=>"SEC701"])?0:1);' 2>/dev/null; then
  export SEC701_COURSE_ID="${SEC701_COURSE_ID:-3}"
  sudo -u www-data php "${REPO}/scripts/fix-sec701-course-filters.php" || true
fi
if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "'"${MOODLE_DIR}"'/config.php";global $DB;exit($DB->record_exists("course",["shortname"=>"NET009"])?0:1);' 2>/dev/null; then
  sudo -u www-data php "${REPO}/scripts/fix-net009-course-filters.php" || true
fi
if sudo -u www-data php -r 'define("CLI_SCRIPT",true);require "'"${MOODLE_DIR}"'/config.php";global $DB;exit($DB->record_exists("course",["shortname"=>"APLUS"])?0:1);' 2>/dev/null; then
  sudo -u www-data php "${REPO}/scripts/fix-aplus-course-filters.php" || true
fi

echo "=== recycle PHP-FPM ==="
bash "${REPO}/scripts/restart-php-fpm-vm.sh"

echo "theme_upgrade_purge_complete=1"
