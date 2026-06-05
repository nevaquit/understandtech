#!/usr/bin/env bash
# Install Cloudflare origin TLS material on the understandtech VM and switch Nginx
# from HTTP bootstrap to production HTTPS (understandtech.conf).
#
# Run on the VM as root (or via sudo). Cert files are expected locally before install.
#
# Usage (on VM after SCP):
#   sudo ./install-cloudflare-origin-certs.sh \
#     --origin-pem /tmp/origin.pem \
#     --origin-key /tmp/origin.key \
#     --nginx-conf /tmp/understandtech.conf \
#     --rate-limit-conf /tmp/understandtech-rate-limit.conf
#
# Defaults:
#   --origin-pem /tmp/origin.pem
#   --origin-key /tmp/origin.key
#   --nginx-conf /etc/nginx/sites-available/understandtech.conf (must exist or pass --nginx-conf)
#
# Authenticated Origin Pull CA is downloaded from Cloudflare if missing.

set -euo pipefail

ORIGIN_PEM="/tmp/origin.pem"
ORIGIN_KEY="/tmp/origin.key"
NGINX_CONF=""
RATE_LIMIT_CONF=""
SSL_DIR="/etc/ssl/cloudflare"
AOP_CA_URL="https://developers.cloudflare.com/ssl/static/authenticated_origin_pull_ca.pem"
BOOTSTRAP_SITE="understandtech-bootstrap.conf"
PROD_SITE="understandtech.conf"

log() { echo "[install-cf-certs] $*"; }

usage() {
  sed -n '2,18p' "$0"
  exit "${1:-0}"
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --origin-pem) ORIGIN_PEM="$2"; shift 2 ;;
    --origin-key) ORIGIN_KEY="$2"; shift 2 ;;
    --nginx-conf) NGINX_CONF="$2"; shift 2 ;;
    --rate-limit-conf) RATE_LIMIT_CONF="$2"; shift 2 ;;
    -h|--help) usage 0 ;;
    *) echo "Unknown option: $1" >&2; usage 1 ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run as root: sudo $0" >&2
  exit 1
fi

for f in "$ORIGIN_PEM" "$ORIGIN_KEY"; do
  if [[ ! -f "$f" ]]; then
    echo "Missing cert file: $f" >&2
    echo "SCP origin.pem and origin.key to the VM first, or create via Cloudflare dashboard/API." >&2
    exit 1
  fi
done

log "Creating $SSL_DIR"
install -d -m 0750 -o root -g root "$SSL_DIR"

log "Installing origin certificate and key"
install -m 0600 -o root -g root "$ORIGIN_PEM" "$SSL_DIR/origin.pem"
install -m 0600 -o root -g root "$ORIGIN_KEY" "$SSL_DIR/origin.key"

AOP_PATH="$SSL_DIR/authenticated_origin_pull_ca.pem"
if [[ ! -f "$AOP_PATH" ]]; then
  log "Downloading Authenticated Origin Pull CA"
  curl -fsSL "$AOP_CA_URL" -o "$AOP_PATH"
  chmod 0644 "$AOP_PATH"
  chown root:root "$AOP_PATH"
else
  log "Authenticated Origin Pull CA already present"
fi

if [[ -n "$RATE_LIMIT_CONF" && -f "$RATE_LIMIT_CONF" ]]; then
  log "Installing rate-limit config"
  install -m 0644 -o root -g root "$RATE_LIMIT_CONF" /etc/nginx/conf.d/understandtech-rate-limit.conf
elif [[ ! -f /etc/nginx/conf.d/understandtech-rate-limit.conf ]]; then
  log "WARN: /etc/nginx/conf.d/understandtech-rate-limit.conf missing — login rate limit zone may fail nginx -t"
fi

DEST_NGINX="/etc/nginx/sites-available/$PROD_SITE"
if [[ -n "$NGINX_CONF" && -f "$NGINX_CONF" ]]; then
  log "Installing production nginx site config"
  install -m 0644 -o root -g root "$NGINX_CONF" "$DEST_NGINX"
elif [[ ! -f "$DEST_NGINX" ]]; then
  echo "Production nginx config not found at $DEST_NGINX. Pass --nginx-conf." >&2
  exit 1
fi

log "Switching sites-enabled from bootstrap to production"
ln -sf "$DEST_NGINX" "/etc/nginx/sites-enabled/$PROD_SITE"
rm -f "/etc/nginx/sites-enabled/$BOOTSTRAP_SITE" "/etc/nginx/sites-enabled/default"

log "Testing nginx configuration"
nginx -t

log "Reloading nginx"
systemctl reload nginx

log "Done. HTTPS origin active on :443 with Authenticated Origin Pulls."
log "Enable Authenticated Origin Pulls in Cloudflare: SSL/TLS -> Origin Server -> toggle ON."
