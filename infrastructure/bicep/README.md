# Azure infrastructure deployment (Phase 2)

Deploys the understandtech.app v2.0 production stack from Bicep.

## Prerequisites

- Azure CLI 2.60+ and Bicep CLI
- Contributor access on the target subscription
- `parameters.prod.bicepparam` updated with your SSH public key and admin IP

## Validate locally

```bash
az bicep build --file infrastructure/bicep/main.bicep
az deployment sub validate \
  --location eastus2 \
  --template-file infrastructure/bicep/main.bicep \
  --parameters infrastructure/bicep/parameters.prod.bicepparam
```

## Deploy

```bash
az deployment sub create \
  --name understandtech-prod-$(date +%Y%m%d) \
  --location eastus2 \
  --template-file infrastructure/bicep/main.bicep \
  --parameters infrastructure/bicep/parameters.prod.bicepparam
```

## Post-deployment

1. Populate Key Vault secrets (replace `REPLACE-ME` placeholders)
2. Generate PgBouncer SCRAM hash in `infrastructure/pgbouncer/userlist.txt`
3. Render cloud-init with secrets and re-deploy VM customData if needed
4. Install Cloudflare Origin Certificate on the VM at `/etc/ssl/cloudflare/`

## Modules

| Module | Resources |
|--------|-----------|
| `modules/network.bicep` | VNet, subnets, NSG (Cloudflare + admin SSH), Postgres private DNS |
| `modules/data.bicep` | Postgres 16, Redis, Storage Files, Key Vault |
| `modules/monitoring.bicep` | Log Analytics, Application Insights |
| `modules/vm.bicep` | Ubuntu 24.04 VM, public IP, managed identity |

## Related configs (Phase 2.2–2.4)

- `../runner/cloud-init.yaml` — first-boot bootstrap
- `../nginx/understandtech.conf` — origin web server
- `../php-fpm/moodle.conf` — PHP-FPM pool
- `../pgbouncer/` — database connection pooling
