# Cloudflare Stream — Upload & Smoke Checklist

One-page gate for playbook §7.1 **Stream test video**. Signing key is already in Key Vault; upload and embed are manual dashboard steps.

## Prerequisites (done)

| Item | Status |
|------|--------|
| Key Vault `cf-stream-signing-key` | ✅ Populated (len 57, not `REPLACE-ME`) |
| `scripts/generate-stream-signed-url.sh` | ✅ In repo (needs `python3` + `cryptography`) |
| `local_certmaster` Stream signing | ✅ `stream_helper::sign_manifest_url()` (RS256, 60s) — configure kid + subdomain in admin |
| Signing PEM on VM | ⏸ Run `.\scripts\setup-moodle-env-vm.ps1` → `/etc/moodle/cf-stream-signing-key.pem` |

## 1. Upload test video (Cloudflare dashboard)

1. Open [Cloudflare Dashboard](https://dash.cloudflare.com/) → **Stream** → **Upload**.
2. Upload a short clip (e.g. 30s MP4, &lt; 200 MB).
3. After processing, note:
   - **Video ID** (UID in the video detail URL)
   - **Customer subdomain** from the embed snippet — e.g. `customer-abc123def456` in `https://customer-abc123def456.cloudflarestream.com/...`

## 2. Confirm signing key (Stream → Settings → Signing keys)

1. **Stream → Settings → Signing keys** — note the **Key ID** (`kid`).
2. Confirm the PEM private key matches Key Vault secret `cf-stream-signing-key`:
   ```powershell
   $az = 'C:\Program Files\Microsoft SDKs\Azure\CLI2\wbin\az.cmd'
   & $az keyvault secret show --vault-name utkvnhhwegpz3rem6 --name cf-stream-signing-key --query value -o tsv
   ```
3. If you rotate keys in Cloudflare, update Key Vault and redeploy any signing code.

## 3. Generate a 60-second signed URL (smoke)

Git Bash or WSL from repo root:

```bash
export STREAM_VIDEO_ID='<video-uid-from-dashboard>'
export STREAM_SIGNING_KID='<kid-from-stream-settings>'
export STREAM_CUSTOMER_SUBDOMAIN='customer-<id>'
# PEM from Key Vault (or export CF_STREAM_SIGNING_KEY manually)
export CF_STREAM_SIGNING_KEY="$(az keyvault secret show \
  --vault-name utkvnhhwegpz3rem6 --name cf-stream-signing-key --query value -o tsv)"

TEST_VIDEO_URL="$(./scripts/generate-stream-signed-url.sh)"
echo "$TEST_VIDEO_URL"

PROD_URL=https://understandtech.app TEST_VIDEO_URL="$TEST_VIDEO_URL" \
  ./scripts/smoke-test-deployment.sh
```

Expect the smoke script to pass the Stream URL check (HTTP 200 on manifest).

## 4. Moodle lesson embed

1. **Site administration → Plugins → Local plugins → CertMaster** — set **Stream signing key ID** and **customer subdomain** from the dashboard.
2. Deploy signing PEM: `.\scripts\setup-moodle-env-vm.ps1` (writes `/etc/moodle/cf-stream-signing-key.pem` from Key Vault).
3. In PHP or a custom Mustache filter, call `\local_certmaster\stream_helper::sign_manifest_url($videoid)` — never paste raw video IDs in HTML.
4. Embed iframe: `https://<subdomain>.cloudflarestream.com/<jwt>/iframe` (generate JWT the same way; manifest URL uses `/manifest/video.m3u8`).

Reference: [stream-jwt-signing.md](../.cursor/skills/edge-serverless-orchestration/stream-jwt-signing.md).

## 5. Done when

- [ ] Test video uploaded and playable via signed URL
- [ ] Smoke test passes with `TEST_VIDEO_URL` set
- [ ] At least one Moodle activity embeds the player (manual JWT or future plugin)

## Related

- [v1-release-integrations.md](v1-release-integrations.md) — Stream / Stripe / Postmark status
- [phase-7-production.md](phase-7-production.md) — §7.1 gates
- [post-deployment-validation.md](post-deployment-validation.md) — full validation checklist
