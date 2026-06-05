#!/usr/bin/env bash
# One-time VM bootstrap for Phase 5.2 self-hosted runner (run as azureadmin with sudo).
# Registration token must be supplied via REGISTRATION_TOKEN env or as first argument.
set -euo pipefail

REGISTRATION_TOKEN="${REGISTRATION_TOKEN:-${1:-}}"
REPO_URL="${REPO_URL:-https://github.com/nevaquit/understandtech.git}"

log() { echo "[bootstrap-gha-runner] $*"; }

if ! id gha-runner >/dev/null 2>&1; then
  log "Creating gha-runner user"
  sudo useradd --system --home-dir /opt/actions-runner --create-home --shell /bin/bash gha-runner
fi

if [ ! -f /opt/actions-runner/config.sh ]; then
  log "Downloading GitHub Actions runner"
  RUNNER_VERSION=$(curl -fsSL https://api.github.com/repos/actions/runner/releases/latest | jq -r .tag_name | sed 's/v//')
  curl -fsSL -o /tmp/actions-runner.tar.gz -L \
    "https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/actions-runner-linux-x64-${RUNNER_VERSION}.tar.gz"
  sudo mkdir -p /opt/actions-runner
  sudo tar -xzf /tmp/actions-runner.tar.gz -C /opt/actions-runner
  sudo chown -R gha-runner:gha-runner /opt/actions-runner
  rm -f /tmp/actions-runner.tar.gz
  log "Runner v${RUNNER_VERSION} extracted"
fi

if [ ! -f /opt/actions-runner/.runner ]; then
  if [ -z "$REGISTRATION_TOKEN" ]; then
    log "SKIP registration — set REGISTRATION_TOKEN or pass token as arg 1"
  else
    log "Registering runner understandtech-web-prod"
    sudo -u gha-runner /opt/actions-runner/config.sh --unattended \
      --url https://github.com/nevaquit/understandtech \
      --token "$REGISTRATION_TOKEN" \
      --labels self-hosted,linux,production \
      --name understandtech-web-prod
    sudo bash -c 'cd /opt/actions-runner && ./svc.sh install gha-runner'
    sudo bash -c 'cd /opt/actions-runner && ./svc.sh start'
    log "Runner service started"
  fi
else
  log "Runner already registered"
fi

if [ ! -d /opt/understandtech-plugins/.git ]; then
  log "Cloning plugin monorepo"
  sudo mkdir -p /opt/understandtech-plugins
  sudo chown gha-runner:gha-runner /opt/understandtech-plugins
  sudo -u gha-runner git clone "$REPO_URL" /opt/understandtech-plugins
else
  log "Plugins repo already present"
fi

SUDOERS_SRC="${SUDOERS_SRC:-/tmp/gha-runner-sudoers}"
if [ -f "$SUDOERS_SRC" ]; then
  log "Installing sudoers from $SUDOERS_SRC"
  sudo cp "$SUDOERS_SRC" /etc/sudoers.d/gha-runner
  sudo chmod 0440 /etc/sudoers.d/gha-runner
  sudo visudo -cf /etc/sudoers.d/gha-runner
else
  REPO_SUDOERS=/opt/understandtech-plugins/infrastructure/runner/gha-runner-sudoers
  if [ -f "$REPO_SUDOERS" ]; then
    log "Installing sudoers from repo checkout"
    sudo cp "$REPO_SUDOERS" /etc/sudoers.d/gha-runner
    sudo chmod 0440 /etc/sudoers.d/gha-runner
    sudo visudo -cf /etc/sudoers.d/gha-runner
  else
    log "WARNING: no sudoers file found"
  fi
fi

echo "=== STATUS ==="
id gha-runner
test -f /opt/actions-runner/.runner && echo "RUNNER: registered" || echo "RUNNER: not registered"
systemctl is-active 'actions.runner.*' 2>/dev/null || echo "RUNNER_SERVICE: inactive"
test -d /opt/understandtech-plugins/.git && echo "PLUGINS_REPO: ok" || echo "PLUGINS_REPO: missing"
sudo test -f /etc/sudoers.d/gha-runner && echo "SUDOERS: ok" || echo "SUDOERS: missing"
