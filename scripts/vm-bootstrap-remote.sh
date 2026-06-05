#!/usr/bin/env bash
# Partial VM bootstrap (no CF certs, no Moodle core, no GHA runner token).
set -euo pipefail

REPO_ROOT="${REPO_ROOT:-/tmp/understandtech-infra}"
POSTGRES_FQDN="${POSTGRES_FQDN:-understandtech-pg-prod.postgres.database.azure.com}"
STORAGE_ACCOUNT="${STORAGE_ACCOUNT:?STORAGE_ACCOUNT required}"
SMB_PASSWORD="${SMB_PASSWORD:?SMB_PASSWORD required}"
PG_ADMIN_PASSWORD="${PG_ADMIN_PASSWORD:?PG_ADMIN_PASSWORD required}"

log() { echo "[vm-bootstrap] $*"; }

export DEBIAN_FRONTEND=noninteractive
sudo install -d -m 0750 /etc/moodle
sudo install -d -m 0755 /var/www/moodle
sudo install -d -m 0770 /var/www/moodledata

log "Mounting Azure Files"
printf 'username=%s\npassword=%s\n' "$STORAGE_ACCOUNT" "$SMB_PASSWORD" | sudo tee /etc/moodle/smbcred >/dev/null
sudo chmod 0600 /etc/moodle/smbcred
FSTAB_LINE="//$STORAGE_ACCOUNT.file.core.windows.net/moodledata /var/www/moodledata cifs credentials=/etc/moodle/smbcred,dir_mode=0770,file_mode=0660,uid=33,gid=33,nofail 0 0"
if ! grep -q 'file.core.windows.net/moodledata' /etc/fstab; then
  echo "$FSTAB_LINE" | sudo tee -a /etc/fstab
fi
sudo mount -a || true
mountpoint -q /var/www/moodledata && log "moodledata mounted" || log "WARN: moodledata mount failed"

log "PgBouncer config"
sudo sed "s|{{POSTGRES_FQDN}}|$POSTGRES_FQDN|g" "$REPO_ROOT/pgbouncer/pgbouncer.ini" | sudo tee /etc/pgbouncer/pgbouncer.ini >/dev/null
export PGPASSWORD="$PG_ADMIN_PASSWORD"
export PGSSLMODE=require
HASHLINE=$(psql -h "$POSTGRES_FQDN" -U moodle_admin -d postgres -tAc "SELECT concat('\"moodle_user\" \"', rolpassword, '\"') FROM pg_roles WHERE rolname='moodle_user';")
echo "$HASHLINE" | sudo tee /etc/pgbouncer/userlist.txt >/dev/null
sudo chmod 0600 /etc/pgbouncer/userlist.txt
sudo chown postgres:postgres /etc/pgbouncer/userlist.txt

log "PHP-FPM pool"
sudo cp "$REPO_ROOT/php-fpm/moodle.conf" /etc/php/8.3/fpm/pool.d/moodle.conf

log "PHP production ini"
sudo tee /etc/php/8.3/fpm/conf.d/99-understandtech.ini >/dev/null <<'PHPINI'
memory_limit = 512M
upload_max_filesize = 200M
post_max_size = 200M
max_execution_time = 300
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.jit = tracing
opcache.jit_buffer_size = 128M
PHPINI

log "Nginx bootstrap (HTTP only until Cloudflare origin certs)"
sudo tee /etc/nginx/sites-available/understandtech-bootstrap.conf >/dev/null <<'NGINX'
upstream moodle_php {
    server unix:/run/php/moodle.sock;
}
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root /var/www/moodle;
    index index.php;
    client_max_body_size 200M;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass moodle_php;
    }
}
NGINX
sudo ln -sf /etc/nginx/sites-available/understandtech-bootstrap.conf /etc/nginx/sites-enabled/understandtech-bootstrap.conf
sudo rm -f /etc/nginx/sites-enabled/default

log "Enable services"
sudo systemctl enable php8.3-fpm pgbouncer nginx
sudo systemctl restart php8.3-fpm
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart pgbouncer
systemctl is-active php8.3-fpm nginx pgbouncer
