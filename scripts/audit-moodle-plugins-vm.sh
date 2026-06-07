#!/usr/bin/env bash
# Read-only audit: custom theme/plugins on production Moodle (safe — no writes).
# Run on VM: sudo -u www-data bash /opt/understandtech-plugins/scripts/audit-moodle-plugins-vm.sh
set -uo pipefail

MOODLE="${MOODLE_DIR:-/var/www/moodle}"
REPO="${PLUGINS_REPO_DIR:-/opt/understandtech-plugins}"

echo "=== moodle audit (read-only) ==="
echo "moodle_dir=$MOODLE"
echo "repo=$REPO"
echo "repo_head=$(sudo -u gha-runner git -C "$REPO" rev-parse --short HEAD 2>/dev/null || echo unknown)"

fail=0
warn=0

check_dir() {
  local label="$1"
  local path="$2"
  if [ -d "$path" ]; then
    echo "OK   dir $label -> $path"
  else
    echo "MISS dir $label -> $path"
    fail=$((fail + 1))
  fi
}

# Expected custom plugins (repo folder -> moodle path).
declare -A EXPECT=(
  [theme_understandtech]="$MOODLE/theme/understandtech"
  [local_certmaster]="$MOODLE/local/certmaster"
  [local_aitutor]="$MOODLE/local/aitutor"
  [local_aigrading]="$MOODLE/local/aigrading"
  [local_gamification]="$MOODLE/local/gamification"
  [local_community]="$MOODLE/local/community"
  [local_integrations]="$MOODLE/local/integrations"
  [mod_ctfflag]="$MOODLE/mod/ctfflag"
  [block_examreadiness]="$MOODLE/blocks/examreadiness"
  [block_portfolio]="$MOODLE/blocks/portfolio"
  [qbehaviour_certmasterconfidence]="$MOODLE/question/behaviour/certmasterconfidence"
)

for src in "${!EXPECT[@]}"; do
  check_dir "$src" "${EXPECT[$src]}"
done

echo "=== CLI plugin status ==="
sudo -u www-data php "$MOODLE/admin/cli/plugins.php" --status=missing 2>/dev/null | head -20 || echo "WARN plugins.php unavailable"
echo "---"

sudo -u www-data php -r "
define('CLI_SCRIPT', true);
chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
global \$CFG, \$DB;
\$components = [
    'theme_understandtech','local_certmaster','local_aitutor','local_aigrading',
    'local_gamification','local_community','local_integrations','mod_ctfflag',
    'block_examreadiness','block_portfolio','qbehaviour_certmasterconfidence',
];
echo \"=== config_plugins versions ===\n\";
foreach (\$components as \$c) {
    \$v = get_config(\$c, 'version');
    echo \$c . ': ' . (\$v ? 'v' . \$v : 'NOT_INSTALLED') . PHP_EOL;
}
echo \"\nactive_theme=\" . get_config('core', 'theme') . PHP_EOL;
if (get_config('core', 'theme') !== 'understandtech') {
    echo \"WARN expected theme_understandtech (understandtech)\n\";
}
echo \"\n=== certmaster seed ===\n\";
try {
    echo 'certifications=' . \$DB->count_records('certmaster_certifications') . PHP_EOL;
    echo 'domains=' . \$DB->count_records('certmaster_domains') . PHP_EOL;
} catch (Throwable \$e) {
    echo 'certmaster_tables_error=' . \$e->getMessage() . PHP_EOL;
}
echo \"\n=== quick DB ping ===\n\";
try {
    \$DB->get_field_sql('SELECT 1');
    echo \"db_ok=1\n\";
} catch (Throwable \$e) {
    echo 'db_ok=0 error=' . \$e->getMessage() . PHP_EOL;
}
echo \"\n=== maintenance ===\n\";
echo 'maintenance_enabled=' . (int)!empty(\$CFG->maintenance_enabled) . PHP_EOL;
"

echo "=== services ==="
for svc in nginx php8.3-fpm pgbouncer; do
  if systemctl is-active --quiet "$svc"; then
    echo "OK   $svc active"
  else
    echo "FAIL $svc inactive"
    fail=$((fail + 1))
  fi
done

echo "=== summary fail=$fail warn=$warn ==="
exit 0
