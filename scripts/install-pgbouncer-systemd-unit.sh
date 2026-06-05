#!/usr/bin/env bash
# Replace sysv pgbouncer with a native systemd unit.
set -euo pipefail
sudo tee /etc/systemd/system/pgbouncer.service >/dev/null <<'UNIT'
[Unit]
Description=PgBouncer connection pooler
After=network.target

[Service]
Type=forking
User=postgres
Group=postgres
ExecStart=/usr/sbin/pgbouncer -d /etc/pgbouncer/pgbouncer.ini
ExecReload=/bin/kill -HUP $MAINPID
PIDFile=/var/run/postgresql/pgbouncer.pid
Restart=on-failure

[Install]
WantedBy=multi-user.target
UNIT
sudo systemctl daemon-reload
sudo systemctl enable pgbouncer
sudo systemctl restart pgbouncer
systemctl is-active pgbouncer
