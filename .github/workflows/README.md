# Workflows

| Workflow | Trigger | Runners |
|----------|---------|---------|
| `deploy.yml` | Push/PR to `main`, manual dispatch | **validate/e2e:** `ubuntu-latest` · **staging:** `[self-hosted, linux, staging]` · **prod:** `[self-hosted, linux, production]` |
| `deploy-staging.yml` | Reusable (called by `deploy.yml`) | `[self-hosted, linux, staging]` |
| `deploy-staging-infra.yml` | Manual dispatch | `ubuntu-latest` — Bicep validate + provision docs |
| `test.yml` | Push/PR to `main` | `ubuntu-latest` — PHPUnit lint + integration smoke + Worker unit tests |
| `e2e.yml` | Manual dispatch, weekly cron | `ubuntu-latest` — Playwright against staging (default) |
| `seed-sec701.yml` | Manual dispatch | `staging` or `production` runner (input `target`) |
| `deploy-ai-gateway.yml` | Push to `main` (worker paths), manual | `ubuntu-latest` — typecheck + optional wrangler deploy |

## deploy.yml (staging-first)

Four-stage pipeline (Phase 5.1 + 6):

1. **validate** — PHP lint, `version.php` checks, Moodle CodeChecker (warn-only), Bicep build, AI Gateway typecheck, changed-plugin detection
2. **deploy-staging** — same steps as prod on staging VM; health/smoke against `STAGING_URL`
3. **staging-e2e** — Playwright chromium on staging (blocks prod)
4. **deploy** (production) — only after staging + E2E pass; `skip_staging_gate` on manual dispatch for emergencies
5. **notify** — posts to Slack/Discord when `NOTIFY_WEBHOOK_URL` secret is set

Deploy sudo commands must match `infrastructure/runner/gha-runner-sudoers`.

## GitHub secrets (staging pipeline)

| Secret | Required | Notes |
|--------|----------|-------|
| `STAGING_URL` | Recommended | `https://staging.understandtech.app/learn` (Playwright base URL) |
| `STAGING_TEST_USER_EMAIL` | E2E | Or reuse `MOODLE_E2E_USER` (username `e2etest`) |
| `STAGING_TEST_USER_PASSWORD` | E2E | Or reuse `MOODLE_E2E_PASS` |
| `MOODLE_E2E_USER` / `MOODLE_E2E_PASS` | Fallback | Same test user on staging after `setup-e2e-test-user-vm.sh` |
| `E2E_COURSE_PATH` | Optional | Default `/course/view.php?id=3` |

## Prerequisites (user action)

1. Provision staging: `az deployment sub create … parameters.staging.bicepparam` (see `infrastructure/bicep/README.md`)
2. Cloudflare A record `staging` → staging VM IP (proxied)
3. Self-hosted runner on **staging** VM: `RUNNER_NAME=understandtech-web-staging RUNNER_LABELS=self-hosted,linux,staging REGISTRATION_TOKEN=<token> sudo -E bash scripts/bootstrap-gha-runner-vm.sh`
4. Self-hosted runner on **production** VM with labels `self-hosted`, `linux`, `production`
5. `/opt/understandtech-plugins` cloned and writable by `gha-runner` on both VMs
6. `sudoers.d/gha-runner` installed from repo on both VMs
7. Seed SEC701 on staging: `gh workflow run seed-sec701.yml -f target=staging`
