#!/usr/bin/env bash
set -euo pipefail

# Load DB creds from env file without printing values.
while IFS='=' read -r key value; do
  [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
  export "$key=$value"
done < /etc/moodle/env

export PGPASSWORD="$MOODLE_DB_PASSWORD"

psql -h 127.0.0.1 -p 6432 -U "$MOODLE_DB_USER" -d "$MOODLE_DB_NAME" -v ON_ERROR_STOP=1 <<'SQL'
DELETE FROM mdl_config WHERE name = 'upgraderunning';
UPDATE mdl_config SET value = '0' WHERE name = 'maintenance_enabled';
SELECT name, value FROM mdl_config WHERE name IN ('upgraderunning', 'maintenance_enabled', 'version');
SELECT plugin, name, value FROM mdl_config_plugins WHERE plugin IN ('local_certmaster','block_examreadiness','local_aitutor') AND name = 'version';
SQL

unset PGPASSWORD
