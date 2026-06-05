#!/usr/bin/env bash
set -euo pipefail
PGHOST="${PGHOST:-understandtech-pg-prod.postgres.database.azure.com}"
PGUSER="${PGUSER:-moodle_admin}"
export PGPASSWORD="${PGPASSWORD:?PGPASSWORD required}"
export PGSSLMODE=require

psql -h "$PGHOST" -U "$PGUSER" -d postgres -f /tmp/setup-moodle-user.sql -v ON_ERROR_STOP=1
psql -h "$PGHOST" -U "$PGUSER" -d moodle -v ON_ERROR_STOP=1 -c "GRANT ALL ON SCHEMA public TO moodle_user;"
psql -h "$PGHOST" -U "$PGUSER" -d moodle -v ON_ERROR_STOP=1 -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO moodle_user;"
psql -h "$PGHOST" -U "$PGUSER" -d moodle -v ON_ERROR_STOP=1 -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO moodle_user;"
psql -h "$PGHOST" -U "$PGUSER" -d postgres -tAc "SELECT rolname FROM pg_roles WHERE rolname='moodle_user'"
