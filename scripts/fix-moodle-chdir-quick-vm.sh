#!/usr/bin/env bash
# Fast chdir recovery — no full-tree find. Safe to run every 15 minutes.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"

chmod 755 /var/www
chmod 755 "${MOODLE_DIR}"

sudo -u www-data php -r "
chdir('${MOODLE_DIR}');
define('CLI_SCRIPT', true);
require '${MOODLE_DIR}/config.php';
global \$DB;
\$DB->get_field('config', 'value', ['name' => 'version']);
echo \"www_data_chdir_db_ok\n\";
"

systemctl restart php8.3-fpm
echo 'fix_moodle_chdir_quick_complete=1'
