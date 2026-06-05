# AI Gateway Worker

TypeScript Cloudflare Worker for understandtech.app — JWT-authenticated LLM proxy at `ai.understandtech.app`.

## Endpoints

| Route | Method | Auth | Purpose |
|-------|--------|------|---------|
| `/health` | GET | No | Liveness check |
| `/tutor` | POST | Bearer JWT | Socratic AI tutor (SSE stream) |
| `/grade` | POST | Bearer JWT | AI grading (JSON) |

## Architecture

```
Browser → Worker (/tutor) → Cloudflare AI Gateway → Anthropic (primary)
                              ↓ fail (5xx/429/timeout)
                           OpenAI (fallback)
```

- JWT validation via `jose` (HS256, `iss: moodle`, `aud: ai-worker`)
- KV cache: 60s TTL keyed by prompt version + context + user message
- Rate limit: 30 requests/minute per user via KV
- Transcript audit webhook → `https://understandtech.app/local/aitutor/webhook.php`

## Development

```bash
cd cloudflare-worker/ai-gateway
npm install
npm run typecheck
npm test
npm run dev
```

## Deploy (Phase 4.3)

### 1. Create KV namespace

```bash
npx wrangler kv namespace create PROMPT_CACHE
```

Copy the returned `id` into `wrangler.jsonc` (replace `REPLACE_WITH_KV_NAMESPACE_ID`).

### 2. Configure AI Gateway URL

Set `AI_GATEWAY_URL` in `wrangler.jsonc` to your Cloudflare AI Gateway endpoint:

```
https://gateway.ai.cloudflare.com/v1/{account_id}/understandtech
```

### 3. Set secrets

Values must match Azure Key Vault / Moodle `AITUTOR_WORKER_SHARED_SECRET`:

```bash
npx wrangler secret put MOODLE_JWT_SECRET
npx wrangler secret put MOODLE_WEBHOOK_HMAC_SECRET   # same value as JWT secret
npx wrangler secret put ANTHROPIC_API_KEY
npx wrangler secret put OPENAI_API_KEY
```

### 4. Deploy

```bash
npm run deploy
```

### 5. Smoke test

```bash
curl https://ai.understandtech.app/health
# {"status":"ok"}

curl -X POST https://ai.understandtech.app/tutor
# {"error":"Unauthorized"}  (401)
```

See `docs/playbook.md` Phase 4 for full acceptance criteria.
