#!/usr/bin/env bash
# Fix Debian pgbouncer sysv init to use correct config path.
set -euo pipefail
sudo tee /etc/default/pgbouncer >/dev/null <<'EOF'
START=1
OPTS="-d /etc/pgbouncer/pgbouncer.ini"
EOF
sudo rm -f /var/run/postgresql/pgbouncer.pid /var/run/pgbouncer/pgbouncer.pid 2>/dev/null || true
sudo systemctl daemon-reload
sudo systemctl enable pgbouncer
sudo systemctl restart pgbouncer
systemctl is-active pgbouncer
ss -lntp | grep 6432 || true
