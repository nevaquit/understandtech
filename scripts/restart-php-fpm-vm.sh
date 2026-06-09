#!/usr/bin/env bash
# Recycle all PHP-FPM workers. NEVER use systemctl reload php8.3-fpm — reload keeps
# stale workers that cause site-wide "Error reading from database" after deploy.
set -euo pipefail

systemctl restart php8.3-fpm
echo 'php_fpm_restart_complete=1'
