# Phase 2 — Cloud Infrastructure ✅

Generated per `docs/playbook.md` prompts 2.1–2.4.

## Deliverables

| Prompt | Path | Status |
|--------|------|--------|
| 2.1 Bicep | `infrastructure/bicep/main.bicep` + `modules/` | Complete |
| 2.2 Cloud-init | `infrastructure/runner/cloud-init.yaml` | Complete |
| 2.3 Nginx | `infrastructure/nginx/understandtech.conf` | Complete |
| 2.3 PHP-FPM | `infrastructure/php-fpm/moodle.conf` | Complete |
| 2.4 PgBouncer | `infrastructure/pgbouncer/pgbouncer.ini`, `userlist.txt` | Complete |

Supporting files:

- `infrastructure/runner/gha-runner-sudoers` — deploy workflow sudo allowlist
- `infrastructure/bicep/parameters.prod.bicepparam` — deployment parameters
- `scripts/render-cloud-init.sh` — template substitution before VM deploy

## Validation (requires Azure CLI + Bicep)

```bash
az bicep build --file infrastructure/bicep/main.bicep
az deployment sub validate --location eastus2 \
  --template-file infrastructure/bicep/main.bicep \
  --parameters infrastructure/bicep/parameters.prod.bicepparam
```

Not run locally — Azure CLI not installed on dev machine (see `docs/phase-0-toolchain.md`).

## Before deploying

1. Update `parameters.prod.bicepparam` with real `adminIpAddress` and `vmAdminPublicKey`
2. Generate GitHub runner registration token
3. Render cloud-init: `POSTGRES_FQDN=... REGISTRATION_TOKEN=... ./scripts/render-cloud-init.sh`
4. Populate Key Vault secrets after first deploy

## Next phase

**Phase 3** — Moodle plugins (`theme_understandtech`, `local_certmaster`, `local_aitutor`, …)

Use `/moodle-development` skill with `@docs/playbook.md` Phase 3 prompts.
