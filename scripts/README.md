# Scripts

Deployment and bootstrap helpers for understandtech.app.

## Key Vault secrets (`populate-keyvault-secrets`)

Four secrets still ship as `REPLACE-ME` after Bicep deploy. Populate them before Moodle AI/Stream features work.

| Key Vault secret | Environment variable(s) | How to obtain |
|------------------|-------------------------|---------------|
| `anthropic-api-key` | `ANTHROPIC_API_KEY` | [Anthropic console](https://console.anthropic.com/) → API keys |
| `openai-api-key` | `OPENAI_API_KEY` | [OpenAI platform](https://platform.openai.com/api-keys) |
| `cf-stream-signing-key` | `CF_STREAM_SIGNING_KEY` | Cloudflare **Stream → Settings → Signing Keys → Create** — store the signing key PEM (not the key id) |
| `cf-worker-shared-secret` | `AITUTOR_WORKER_SHARED_SECRET` or `CF_WORKER_SHARED_SECRET` | Generate a random 32+ byte secret; must match the Cloudflare Worker secret and `AITUTOR_WORKER_SHARED_SECRET` in `/etc/moodle/env` |

```powershell
$env:Path = [System.Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path','User')

# Option A: set env vars, then run
$env:ANTHROPIC_API_KEY = '<from-console>'
$env:OPENAI_API_KEY = '<from-console>'
$env:CF_STREAM_SIGNING_KEY = '<from-stream-dashboard>'
$env:AITUTOR_WORKER_SHARED_SECRET = '<random-shared-secret>'
.\scripts\populate-keyvault-secrets.ps1

# Option B: interactive secure prompts (no env vars)
.\scripts\populate-keyvault-secrets.ps1

# Option C: auto-generate worker shared secret only
.\scripts\populate-keyvault-secrets.ps1 -GenerateWorkerSecret
```

Bash equivalent: `./scripts/populate-keyvault-secrets.sh` (add `--generate-worker-secret` if needed).

After population, refresh VM env:

```powershell
.\scripts\setup-moodle-env-vm.ps1
```

## Cloudflare AI Gateway Worker (Phase 4.3)

Worker code lives in `cloudflare-worker/ai-gateway/`. Before first deploy, replace placeholders in `wrangler.jsonc`:

| Placeholder | Replace with |
|-------------|--------------|
| `REPLACE_WITH_KV_NAMESPACE_ID` | Output of `npx wrangler kv namespace create PROMPT_CACHE` |
| `REPLACE_ACCOUNT` in `AI_GATEWAY_URL` | Cloudflare account ID |

Secrets (never commit — use Key Vault values):

```bash
cd cloudflare-worker/ai-gateway
npx wrangler login
npx wrangler secret put MOODLE_JWT_SECRET
npx wrangler secret put MOODLE_WEBHOOK_HMAC_SECRET
npx wrangler secret put ANTHROPIC_API_KEY
npx wrangler secret put OPENAI_API_KEY
```

Deploy (checks auth, placeholders, runs typecheck):

```bash
./scripts/deploy-ai-gateway.sh
```

Expected route after deploy: `https://ai.understandtech.app/*`

## Cloudflare origin certificate

Production nginx (`infrastructure/nginx/understandtech.conf`) requires:

- `/etc/ssl/cloudflare/origin.pem` and `origin.key` (mode 600)
- `/etc/ssl/cloudflare/authenticated_origin_pull_ca.pem` (downloaded by install script)
- **Authenticated Origin Pulls** enabled in Cloudflare: **SSL/TLS → Origin Server**

### Option A — Cloudflare API (token required)

```powershell
$env:CLOUDFLARE_API_TOKEN = '<zone ssl + origin ca permissions>'
.\scripts\create-cloudflare-origin-cert.ps1
.\scripts\deploy-cloudflare-origin-certs.ps1
```

Certs are written to `infrastructure/ssl/cloudflare/` (gitignored `*.pem` / `*.key`).

### Option B — Cloudflare dashboard (no API token)

1. Zone **understandtech.app** → **SSL/TLS** → **Origin Server** → **Create Certificate**
2. RSA, hostnames: `understandtech.app`, `*.understandtech.app`, validity 15 years
3. Save **Origin Certificate** as `infrastructure/ssl/cloudflare/origin.pem`
4. Save **Private Key** as `infrastructure/ssl/cloudflare/origin.key`
5. **SSL/TLS → Origin Server → Authenticated Origin Pulls → ON**
6. `.\scripts\deploy-cloudflare-origin-certs.ps1`

### VM-only install (certs already on VM)

```bash
sudo ./scripts/install-cloudflare-origin-certs.sh \
  --origin-pem /tmp/origin.pem \
  --origin-key /tmp/origin.key \
  --nginx-conf /tmp/understandtech.conf \
  --rate-limit-conf /tmp/understandtech-rate-limit.conf
```

## Other scripts

| Script | Purpose |
|--------|---------|
| `setup-moodle-env-vm.ps1` | Key Vault → `/etc/moodle/env` on VM |
| `install-moodle-vm.sh` | Clone Moodle 4.5 to `/var/www/moodle` |
| `deploy-plugins-vm.sh` | Deploy `moodle-plugins/*` to VM |
| `deploy-ai-gateway.sh` | Phase 4.3 Worker deploy (KV + secrets + `wrangler deploy`) |
| `smoke-test-deployment.sh` | Phase 6.2 post-deploy checks (DNS, SSL, AI Worker, VM health) |
| `render-cloud-init.ps1` | Render cloud-init with storage key |
| `vm-bootstrap-remote.sh` | Partial VM bootstrap (HTTP nginx) |
