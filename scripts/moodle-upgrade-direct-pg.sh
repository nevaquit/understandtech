#!/usr/bin/env bash
# Run Moodle upgrade.php against Azure PostgreSQL directly (bypass PgBouncer transaction pool).
# PgBouncer transaction mode breaks DDL in upgrade_noncore(); restore config.php after success.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
CONFIG="${MOODLE_DIR}/config.php"
BACKUP=/tmp/config.pgbouncer.bak
LOG="${MOODLE_UPGRADE_LOG:-/tmp/moodle-upgrade.log}"
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

exec > >(tee -a "$LOG") 2>&1

echo "=== Moodle direct-Postgres upgrade $(date -Is) ==="

if [ -x "${REPO}/scripts/recover-origin-db.sh" ]; then
  bash "${REPO}/scripts/recover-origin-db.sh" || true
fi

if ! sudo -u www-data php -r "define('CLI_SCRIPT',true); require '${CONFIG}'; global \$DB; \$DB->get_record('config',['name'=>'version'],'value'); echo 'ok';" 2>/dev/null | grep -q ok; then
  echo "ERROR: DB still unreachable after recovery" >&2
  exit 1
fi

ensure_pgbouncer_backup() {
  if grep -q '127.0.0.1' "$CONFIG" && grep -q "'dbport' => 6432" "$CONFIG"; then
    cp "$CONFIG" "$BACKUP"
    echo "PgBouncer config backed up to ${BACKUP}"
    return 0
  fi
  if [ -f "$BACKUP" ] && grep -q '127.0.0.1' "$BACKUP" && grep -q "'dbport' => 6432" "$BACKUP"; then
    echo "Using existing PgBouncer backup at ${BACKUP}"
    return 0
  fi
  cp "$CONFIG" "$BACKUP"
  sed -i 's|understandtech-pg-prod.postgres.database.azure.com|127.0.0.1|g' "$BACKUP"
  sed -i "s|'dbport' => 5432, 'sslmode' => 'require'|'dbport' => 6432|g" "$BACKUP"
  sed -i "s|'dbport' => 5432|'dbport' => 6432|g" "$BACKUP"
  sed -i "/'sslmode'/d" "$BACKUP"
  echo "Built PgBouncer backup from direct Postgres config"
}

restore_pgbouncer_config() {
  if [ ! -f "$BACKUP" ]; then
    echo "ERROR: missing PgBouncer backup at ${BACKUP}" >&2
    return 1
  fi
  for bak in /tmp/pgbouncer.ini.transaction.bak /tmp/pb.full.bak /tmp/pb.bak; do
    if [ -f "$bak" ]; then
      cp "$bak" /etc/pgbouncer/pgbouncer.ini
      sed -i 's/pool_mode=session/pool_mode=transaction/g' /etc/pgbouncer/pgbouncer.ini
      sed -i 's/^pool_mode = session$/pool_mode = transaction/' /etc/pgbouncer/pgbouncer.ini
      systemctl restart pgbouncer
      echo "PgBouncer transaction mode restored from ${bak}"
      break
    fi
  done
  cp "$BACKUP" "$CONFIG"
  chown root:www-data "$CONFIG"
  chmod 640 "$CONFIG"
  if ! sudo -u www-data php -r "define('CLI_SCRIPT',true); require '${CONFIG}'; global \$DB; \$DB->get_record('config',['name'=>'version'],'value'); echo 'ok';" 2>/dev/null | grep -q ok; then
    echo "ERROR: PgBouncer config restore failed DB ping" >&2
    return 1
  fi
  echo "PgBouncer config restored and verified"
}

ensure_pgbouncer_backup

sudo sed -i "s|127.0.0.1|understandtech-pg-prod.postgres.database.azure.com|g" "$CONFIG"
sudo sed -i "s|'dbport' => 6432|'dbport' => 5432, 'sslmode' => 'require'|g" "$CONFIG"

echo "--- config dboptions ---"
sudo grep -A6 dboptions "$CONFIG" || true

cd "$MOODLE_DIR"
if ! sudo -u www-data /usr/bin/php admin/cli/upgrade.php --non-interactive --allow-unstable; then
  echo "Upgrade failed; restoring PgBouncer config"
  restore_pgbouncer_config || cp "$BACKUP" "$CONFIG"
  chown root:www-data "$CONFIG"
  chmod 640 "$CONFIG"
  exit 1
fi

if ! restore_pgbouncer_config; then
  echo "ERROR: could not restore PgBouncer after upgrade" >&2
  exit 1
fi

if [ -f "${REPO}/scripts/moodle-sync-version-hash.sh" ]; then
  bash "${REPO}/scripts/moodle-sync-version-hash.sh" || echo "WARN: version hash sync failed"
fi

sudo -u www-data /usr/bin/php admin/cli/purge_caches.php
# Full restart (never reload) — reload leaves stale workers with broken DB bootstrap.
systemctl restart php8.3-fpm

NGINX_SRC="${REPO}/infrastructure/nginx/understandtech.conf"
NGINX_DST="/etc/nginx/sites-available/understandtech.conf"
if [ -f "$NGINX_SRC" ] && { [ ! -f "$NGINX_DST" ] || ! cmp -s "$NGINX_SRC" "$NGINX_DST"; }; then
  echo "Applying nginx vhost from ${NGINX_SRC}"
  cp "$NGINX_SRC" "$NGINX_DST"
  ln -sf "$NGINX_DST" /etc/nginx/sites-enabled/understandtech.conf
  if [ -f "${REPO}/infrastructure/nginx/understandtech-rate-limit.conf" ]; then
    cp "${REPO}/infrastructure/nginx/understandtech-rate-limit.conf" /etc/nginx/conf.d/understandtech-rate-limit.conf
  fi
  nginx -t
  systemctl reload nginx
  echo "nginx reloaded"
fi

# Deploy marketing site files
MARKETING_SRC="${REPO}/marketing"
MARKETING_DEST="/var/www/marketing"
if [ -d "$MARKETING_SRC" ]; then
  echo "Deploying marketing site from ${MARKETING_SRC}"
  mkdir -p "$MARKETING_DEST"
  rsync -av --delete "$MARKETING_SRC/" "$MARKETING_DEST/"
  chown -R www-data:www-data "$MARKETING_DEST"
  find "$MARKETING_DEST" -type d -exec chmod 755 {} \;
  find "$MARKETING_DEST" -type f -exec chmod 644 {} \;
  echo "Marketing site deployed to ${MARKETING_DEST}"
fi

if [ -f "${REPO}/scripts/test-tutor-jwt.php" ]; then
  echo "--- tutor JWT smoke ---"
  sudo -u www-data /usr/bin/php "${REPO}/scripts/test-tutor-jwt.php" --curl || echo "WARN: tutor JWT/worker check failed"
fi



echo "--- SCSS compilation diagnostic ---"
sudo -u www-data /usr/bin/php << 'PHPEOF'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
require_once($CFG->libdir . '/outputlib.php');
try {
  $theme = theme_config::load('understandtech');
  $css = $theme->get_css_content();
  echo 'SCSS OK: ' . strlen($css) . ' bytes' . PHP_EOL;
  echo 'First 100: ' . substr($css, 0, 100) . PHP_EOL;
} catch (\Throwable $e) {
  echo 'SCSS ERROR: ' . $e->getMessage() . PHP_EOL;
  echo 'At: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
PHPEOF

echo "--- Active theme diagnostic ---"
sudo -u www-data /usr/bin/php << 'PHPEOF2'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
$theme = get_config('core', 'theme');
echo "Active theme: " . $theme . PHP_EOL;
$themeDir = $CFG->dirroot . '/theme/understandtech';
echo "Theme dir exists: " . (is_dir($themeDir) ? 'YES' : 'NO') . PHP_EOL;
echo "lib.php exists: " . (file_exists($themeDir . '/lib.php') ? 'YES' : 'NO') . PHP_EOL;
$dbversion = get_config('theme_understandtech', 'version');
echo "DB theme version: " . ($dbversion ?: 'not set') . PHP_EOL;
// Check if styles.php would serve CSS for this theme
$themerev = get_config('core', 'themerev');
echo "themerev: " . $themerev . PHP_EOL;
PHPEOF2

echo "--- Force theme reset ---"
sudo -u www-data /usr/bin/php << 'PHPEOF3'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
// Force the theme to understandtech
set_config('theme', 'understandtech');
// Reset all theme caches
theme_reset_all_caches();
echo "Theme set to: " . get_config('core', 'theme') . PHP_EOL;
echo "New themerev: " . get_config('core', 'themerev') . PHP_EOL;
// Also clear the moodledata/cache/theme directory
$themecachedir = $CFG->dataroot . '/cache/theme';
if (is_dir($themecachedir)) {
    $files = glob($themecachedir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
        elseif (is_dir($file)) {
            array_map('unlink', glob($file . '/*'));
            rmdir($file);
        }
    }
    echo "Theme cache cleared: $themecachedir" . PHP_EOL;
}
PHPEOF3

echo "--- PHP syntax check ---"
/usr/bin/php -l /var/www/moodle/theme/understandtech/lib.php && echo "lib.php: OK" || echo "lib.php: SYNTAX ERROR"
/usr/bin/php -l /var/www/moodle/theme/understandtech/config.php && echo "config.php: OK" || echo "config.php: SYNTAX ERROR"
/usr/bin/php -l /var/www/moodle/theme/understandtech/classes/output/core_renderer.php 2>/dev/null && echo "core_renderer.php: OK" || echo "core_renderer.php: SYNTAX ERROR or missing"
echo "--- styles.php deep diagnostic ---"
sudo -u www-data /usr/bin/php << 'PHPEOF4'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
try {
    $themename = 'understandtech';
    $theme = theme_config::load($themename);
    echo "theme_config::load OK" . PHP_EOL;
    // Load lib.php explicitly
    require_once '/var/www/moodle/theme/understandtech/lib.php';
    // Call get_main_scss_content directly
    $mainscss = theme_understandtech_get_main_scss_content($theme);
    echo "get_main_scss_content length: " . strlen($mainscss) . " bytes" . PHP_EOL;
    echo "get_main_scss_content first 300: " . substr($mainscss, 0, 300) . PHP_EOL;
    // Check the scss property (closure vs string)
    $scssProp = $theme->get_scss_property();
    if ($scssProp) {
        [$paths, $scss] = $scssProp;
        $type = $scss instanceof Closure ? 'Closure' : 'string: ' . $scss;
        echo "scss property type: " . $type . PHP_EOL;
        echo "scss import paths: " . implode(', ', $paths) . PHP_EOL;
        if ($scss instanceof Closure) {
            $content = $scss($theme);
            echo "scss closure returned: " . strlen($content) . " bytes" . PHP_EOL;
            echo "scss closure first 300: " . substr($content, 0, 300) . PHP_EOL;
        }
    } else {
        echo "scss property: FALSE - no scss defined!" . PHP_EOL;
    }
    // Check pre_scss
    $preSCSS = $theme->get_pre_scss_code();
    echo "pre_scss length: " . strlen($preSCSS) . " bytes" . PHP_EOL;
    echo "pre_scss first 200: " . substr($preSCSS, 0, 200) . PHP_EOL;
    // Full CSS compilation
    $css = $theme->get_css_content();
    echo "get_css_content OK: " . strlen($css) . " bytes" . PHP_EOL;
    echo "CSS starts with: " . substr($css, 0, 100) . PHP_EOL;
    // Check if our classes are in the CSS
    echo "ut-nav-sticky in CSS: " . (strpos($css, 'ut-nav-sticky') !== false ? 'YES' : 'NO') . PHP_EOL;
    echo "ut-hero in CSS: " . (strpos($css, 'ut-hero') !== false ? 'YES' : 'NO') . PHP_EOL;
    echo "--ut-navy in CSS: " . (strpos($css, '--ut-navy') !== false ? 'YES' : 'NO') . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "At: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
PHPEOF4
echo "Upgrade complete via direct Postgres."

# === SCSS file existence check ===
SCSS_FILE="/var/www/moodle/theme/understandtech/scss/preset/default.scss"
if [ -f "$SCSS_FILE" ]; then
  SCSS_SIZE=$(wc -c < "$SCSS_FILE")
  echo "default.scss exists: YES ($SCSS_SIZE bytes)"
  echo "default.scss first 100 chars: $(head -c 100 $SCSS_FILE)"
else
  echo "default.scss EXISTS: NO - FILE MISSING!"
  echo "scss directory contents:"
  ls -la /var/www/moodle/theme/understandtech/scss/ 2>/dev/null || echo "scss dir missing"
fi

# === Direct scssphp compilation test ===
sudo -u www-data /usr/bin/php << 'PHPEOF5'
<?php
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
require_once($CFG->libdir . '/classes/scss.php');

// Read the SCSS files
$prescsspath = '/var/www/moodle/theme/understandtech/lib.php';
require_once $prescsspath;
$theme = theme_config::load('understandtech');

$prescss = $theme->get_pre_scss_code();
$mainscss = theme_understandtech_get_main_scss_content($theme);

echo "pre_scss: " . strlen($prescss) . " bytes\n";
echo "main_scss: " . strlen($mainscss) . " bytes\n";

// Compile with scssphp directly
$compiler = new core_scss([]);
$compiler->prepend_raw_scss($prescss);
$compiler->append_raw_scss($mainscss);
$compiler->setImportPaths([
    '/var/www/moodle/theme/understandtech/scss',
    '/var/www/moodle/theme/boost/scss',
]);

try {
    $css = $compiler->to_css();
    echo "Compilation SUCCESS: " . strlen($css) . " bytes\n";
    echo "CSS starts with: " . substr($css, 0, 200) . "\n";
    echo "ut-nav-sticky: " . (strpos($css, 'ut-nav-sticky') !== false ? 'YES' : 'NO') . "\n";
    echo "--ut-navy: " . (strpos($css, '--ut-navy') !== false ? 'YES' : 'NO') . "\n";
} catch (\Throwable $e) {
    echo "Compilation ERROR: " . $e->getMessage() . "\n";
    echo "At: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
PHPEOF5
