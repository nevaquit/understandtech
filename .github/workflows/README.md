# Workflows

| Workflow | Trigger | Runners |
|----------|---------|---------|
| `deploy.yml` | Push/PR to `main`, manual dispatch | **validate:** `ubuntu-latest` · **deploy:** `[self-hosted, linux, production]` |

## deploy.yml

Two-stage production pipeline (Phase 5.1):

1. **validate** — PHP lint, `version.php` checks, Moodle CodeChecker (warn-only), Bicep build, AI Gateway typecheck, changed-plugin detection
2. **deploy** — maintenance mode, rsync plugins from `/opt/understandtech-plugins`, purge caches, upgrade, Redis flush, health check (self-hosted only; skipped on PRs)
3. **notify** — placeholder for Slack/Discord webhook (`NOTIFY_WEBHOOK_URL` secret)

Deploy sudo commands must match `infrastructure/runner/gha-runner-sudoers`.

## Prerequisites (user action)

- Self-hosted runner registered on the production VM with labels `self-hosted`, `linux`, `production`
- `/opt/understandtech-plugins` cloned and writable by `gha-runner`
- `sudoers.d/gha-runner` installed from repo (includes `question/behaviour` chown for `qbehaviour_*` plugins)
