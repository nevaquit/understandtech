#!/usr/bin/env bash
set -uo pipefail

cd /var/www/moodle

for flags in "--non-interactive --allow-unstable --maintenance=false" "--non-interactive --allow-unstable" "--is-pending"; do
  echo "=== php admin/cli/upgrade.php $flags ==="
  set +e
  OUT=$(sudo -u www-data php admin/cli/upgrade.php $flags 2>&1)
  RC=$?
  set -e
  echo "$OUT"
  echo "exit=$RC"
done

bash /tmp/moodle-query-custom-plugins.sh 2>/dev/null | head -20
