# AI Gateway Worker

TypeScript Cloudflare Worker for understandtech.app — JWT-authenticated LLM proxy at `ai.understandtech.app`.

## Endpoints

| Route | Method | Auth | Purpose |
|-------|--------|------|---------|
| `/health` | GET | No | Liveness check |
| `/tutor` | POST | Bearer JWT | Socratic AI tutor (SSE stub) |
| `/grade` | POST | Bearer JWT | AI grading (stub) |

## Development

```bash
cd cloudflare-worker/ai-gateway
npm install
npm run dev
```

## Deploy (Phase 4.3)

Set secrets via `wrangler secret put`: `MOODLE_JWT_SECRET`, `MOODLE_WEBHOOK_HMAC_SECRET`, `ANTHROPIC_API_KEY`, `OPENAI_API_KEY`.

Replace `REPLACE_WITH_KV_NAMESPACE_ID` in `wrangler.jsonc` before first deploy.

```bash
npm run deploy
```

See `docs/playbook.md` Phase 4 for full acceptance criteria.
