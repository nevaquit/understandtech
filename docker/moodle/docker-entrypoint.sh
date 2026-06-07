#!/bin/sh
set -e

mkdir -p /var/moodledata
chown -R www-data:www-data /var/moodledata 2>/dev/null || true

exec "$@"
