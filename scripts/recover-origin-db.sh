#!/usr/bin/env bash
# Restore Moodle origin DB connectivity (PgBouncer + config.php). Run on production VM as root.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
CONFIG="${MOODLE_DIR}/config.php"
BACKUP=/tmp/config.pgbouncer.bak

is_pgbouncer_config() {
  grep -qE '127\.0\.0\.1' "$CONFIG" && grep -qE "'dbport'[[:space:]]*=>[[:space:]]*6432" "$CONFIG"
}

db_ping() {
  sudo -u www-data php -r "
    define('CLI_SCRIPT', true);
    require '${CONFIG}';
    global \$DB;
    \$DB->get_record('config', ['name' => 'version'], 'value');
    echo 'db_ok';
  " 2>/dev/null | grep -q db_ok
}

build_pgbouncer_backup_from_config() {
  cp "$CONFIG" "$BACKUP"
  sed -i 's|understandtech-pg-prod.postgres.database.azure.com|127.0.0.1|g' "$BACKUP"
  sed -i "s|'dbport' => 5432, 'sslmode' => 'require'|'dbport' => 6432|g" "$BACKUP"
  sed -i "s|'dbport' => 5432|'dbport' => 6432|g" "$BACKUP"
  sed -i "/'sslmode'/d" "$BACKUP"
  echo "Built PgBouncer backup from current config.php"
}

restore_pgbouncer_transaction() {
  for bak in /tmp/pgbouncer.ini.transaction.bak /tmp/pb.full.bak /tmp/pb.bak; do
    if [ -f "$bak" ]; then
      cp "$bak" /etc/pgbouncer/pgbouncer.ini
      break
    fi
  done
  sed -i 's/pool_mode=session/pool_mode=transaction/g' /etc/pgbouncer/pgbouncer.ini
  sed -i 's/^pool_mode = session$/pool_mode = transaction/' /etc/pgbouncer/pgbouncer.ini
  systemctl restart pgbouncer
  echo "PgBouncer transaction mode restored"
}

apply_config() {
  local src="$1"
  cp "$src" "$CONFIG"
  chown root:www-data "$CONFIG"
  chmod 640 "$CONFIG"
}

finalize_recovery() {
  sudo -u www-data php "${MOODLE_DIR}/admin/cli/purge_caches.php"
  sudo -u www-data php "${MOODLE_DIR}/admin/cli/maintenance.php" --disable || true
  systemctl reload php8.3-fpm
}

if is_pgbouncer_config && db_ping; then
  echo "PgBouncer DB connectivity OK"
  exit 0
fi

restore_pgbouncer_transaction

if db_ping && ! is_pgbouncer_config; then
  echo "DB reachable via direct Postgres; rebuilding PgBouncer config"
  build_pgbouncer_backup_from_config
  apply_config "$BACKUP"
  if db_ping; then
    echo "DB restored on PgBouncer from direct Postgres config"
    finalize_recovery
    exit 0
  fi
fi

for src in "$BACKUP" "${CONFIG}.preinstall" /var/www/moodle/config.php.preinstall; do
  if [ ! -f "$src" ]; then
    continue
  fi
  echo "Trying config restore from ${src}"
  apply_config "$src"
  if db_ping; then
    echo "DB restored from ${src}"
    finalize_recovery
    exit 0
  fi
done

echo "ERROR: could not restore DB connectivity" >&2
exit 1
