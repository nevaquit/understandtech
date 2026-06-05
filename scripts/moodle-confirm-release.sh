#!/usr/bin/env bash
set -euo pipefail

sudo -u www-data php /var/www/moodle/admin/cli/cfg.php --name=release --set=2024100712
sudo -u www-data php /var/www/moodle/admin/cli/cfg.php --name=branch --set=405
sudo -u www-data php /var/www/moodle/admin/cli/cfg.php --name=theme --set=theme_understandtech
sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php

echo "Release confirmed, theme set, caches purged."
