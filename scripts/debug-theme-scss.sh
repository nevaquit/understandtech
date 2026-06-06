#!/usr/bin/env bash
# Debug script: test SCSS compilation on the production server
set -euo pipefail
MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"

echo "=== Theme SCSS Debug $(date -Is) ==="
echo ""

# Check if the theme files exist
echo "--- Theme files ---"
ls -la "${MOODLE_DIR}/theme/understandtech/lib.php" 2>/dev/null || echo "lib.php NOT FOUND"
ls -la "${MOODLE_DIR}/theme/understandtech/scss/preset/default.scss" 2>/dev/null || echo "default.scss NOT FOUND"
echo ""

# Check for process_css function
echo "--- process_css function check ---"
grep -n "process_css" "${MOODLE_DIR}/theme/understandtech/lib.php" 2>/dev/null || echo "process_css NOT FOUND in lib.php"
echo ""

# Check the PHP error log
echo "--- PHP error log (last 30 lines) ---"
sudo find /var/log -name "php*" -newer /var/www/moodle/config.php 2>/dev/null | head -5
sudo tail -30 /var/log/php8.3-fpm.log 2>/dev/null || echo "No php8.3-fpm.log"
sudo tail -30 /var/log/nginx/error.log 2>/dev/null || echo "No nginx error.log"
echo ""

# Test SCSS compilation directly via PHP
echo "--- Direct SCSS compilation test ---"
sudo -u www-data ${PHP_BIN} -r "
define('CLI_SCRIPT', true);
require '${MOODLE_DIR}/config.php';
require_once(\$CFG->libdir . '/outputlib.php');
\$theme = theme_config::load('understandtech');
try {
    \$css = \$theme->get_css_content();
    echo 'SCSS compiled OK: ' . strlen(\$css) . ' bytes' . PHP_EOL;
    echo 'First 200 chars: ' . substr(\$css, 0, 200) . PHP_EOL;
} catch (\Throwable \$e) {
    echo 'SCSS FAILED: ' . \$e->getMessage() . PHP_EOL;
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
    echo 'Trace: ' . \$e->getTraceAsString() . PHP_EOL;
}
" 2>&1 || echo "PHP execution failed"
