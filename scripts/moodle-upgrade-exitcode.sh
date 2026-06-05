#!/usr/bin/env bash
set -uo pipefail

echo "PHP: $(sudo -u www-data php -v | head -1)"

sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --is-pending
echo "is-pending exit=$?"

sudo -u www-data php /var/www/moodle/admin/cli/upgrade.php --non-interactive --maintenance --allow-unstable > /tmp/up.out 2>&1
RC=$?
echo "upgrade exit=$RC"
echo "output bytes=$(wc -c < /tmp/up.out)"
cat /tmp/up.out

bash /tmp/check-moodle-plugin-versions.sh 2>/dev/null || true
