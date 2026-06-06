#!/usr/bin/env bash
# Restore Moodle origin DB connectivity (PgBouncer + config.php). Run on production VM as root.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
CONFIG="${MOODLE_DIR}/config.php"
BACKUP=/tmp/config.pgbouncer.bak

db_ping() {
  sudo -u www-data php -r "
    define('CLI_SCRIPT', true);
    require '${CONFIG}';
    global \$DB;
    \$DB->get_record('config', ['name' => 'version'], 'value');
    echo 'db_ok';
  " 2>/dev/null | grep -q db_ok
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

if db_ping; then
  echo "DB already reachable"
  exit 0
fi

restore_pgbouncer_transaction

for src in "$BACKUP" "${CONFIG}.preinstall" /var/www/moodle/config.php.preinstall; do
  if [ ! -f "$src" ]; then
    continue
  fi
  echo "Trying config restore from ${src}"
  cp "$src" "$CONFIG"
  chown root:www-data "$CONFIG"
  chmod 640 "$CONFIG"
  if db_ping; then
    echo "DB restored from ${src}"
    sudo -u www-data php "${MOODLE_DIR}/admin/cli/purge_caches.php"
    sudo -u www-data php "${MOODLE_DIR}/admin/cli/maintenance.php" --disable || true
    systemctl reload php8.3-fpm
    exit 0
  fi
done

echo "ERROR: could not restore DB connectivity" >&2
exit 1
