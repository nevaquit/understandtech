#!/usr/bin/env bash
# Ensure www-data can chdir into Moodle (fixes web "Error reading from database").
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"

echo "=== before ==="
namei -l "${MOODLE_DIR}" 2>/dev/null || ls -ld /var /var/www "${MOODLE_DIR}"

echo "=== fix traverse permissions ==="
chmod 755 /var/www
chmod 755 "${MOODLE_DIR}"
# Moodle code is root-owned; www-data only needs read+execute on directories.
find "${MOODLE_DIR}" -type d -exec chmod 755 {} \;
find "${MOODLE_DIR}" -type f -exec chmod 644 {} \;
chmod 640 "${MOODLE_DIR}/config.php"
chown root:www-data "${MOODLE_DIR}/config.php"

echo "=== verify www-data chdir ==="
sudo -u www-data php -r "
chdir('${MOODLE_DIR}');
define('CLI_SCRIPT', true);
require '${MOODLE_DIR}/config.php';
global \$DB;
\$DB->get_field('config', 'value', ['name' => 'version']);
echo \"www_data_chdir_db_ok\n\";
"

echo "=== php-fpm restart (recycle all workers) ==="
systemctl restart php8.3-fpm
sudo -u www-data php "${MOODLE_DIR}/admin/cli/purge_caches.php"

echo 'fix_moodle_dir_permissions_complete=1'
