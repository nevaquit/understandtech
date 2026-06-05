---
name: edge-serverless-orchestration
description: >-
  Designs Cloudflare Workers edge/serverless workloads for understandtech.app—
  TypeScript async routing, Workers KV state, signed JWT video URLs (60s expiry),
  and SSE streaming for AI token delivery without blocking origin threads. Use when
  building or reviewing Cloudflare Workers, wrangler, edge routing, Stream signed
  URLs, Server-Sent Events, AI Gateway, or offloading workloads from Azure origin.
---

# Edge Computing & Serverless Orchestration

## Context: understandtech.app

To lower infrastructure costs and isolate core application processes, key workloads are offloaded to serverless runtimes at the edge.

**Stack (non-negotiable):** Cloudflare Workers (TypeScript, strict mode), Workers KV, Cloudflare Stream, AI Gateway. Moodle PHP **never** calls Anthropic/OpenAI directly—all LLM traffic goes through `cloudflare-worker/ai-gateway/`.

**Repo entry point:** `cloudflare-worker/ai-gateway/`

---

## 1. Cloudflare Workers & TypeScript

### Structure

```
cloudflare-worker/ai-gateway/
├── src/
│   ├── index.ts          # fetch handler + router
│   ├── auth.ts           # JWT validation (jose)
│   ├── types.ts          # Env bindings, claim shapes
│   ├── prompts.ts        # system prompts (no secrets)
│   └── routes/           # one file per route
├── wrangler.jsonc
└── package.json
```

### Rules

- **Strict TypeScript** — `strict: true`; no `any` without documented exception.
- **Async-first** — use `async/await`; never block the event loop with sync I/O.
- **Router pattern** — `itty-router` or native `fetch` dispatch; central error boundary in `index.ts`.
- **Env bindings** — secrets via `wrangler secret put`, never hardcoded. Types in `Env` interface.
- **No Node-only APIs** — use Web APIs (`fetch`, `ReadableStream`, `crypto.subtle`, `TextEncoder`).

### Edge routing

- Route by path + method in the Worker; return early (404/405) before expensive work.
- Validate auth **before** parsing large bodies or opening upstream connections.
- Use `ctx.waitUntil()` for fire-and-forget logging/metrics—not for SSE response bodies.

---

## 2. Workers KV — edge state

Use KV for **small, eventually-consistent** data:

| Use case | Key pattern | TTL |
|----------|-------------|-----|
| Rate limits | `rl:{userId}:{route}` | 60–3600s |
| Session hints | `sess:{jti}` | match JWT exp |
| Feature flags | `ff:{name}` | none or long |

**Avoid KV for:** hot-path per-token AI streaming, large payloads, strong consistency requirements.

```typescript
await env.KV.put(`rl:${userId}:tutor`, '1', { expirationTtl: 60 });
const hit = await env.KV.get(`rl:${userId}:tutor`);
```

Prefer **short keys**, JSON values, explicit TTLs. Read-modify-write races are acceptable for rate limits only.

---

## 3. Token-based video security (Cloudflare Stream)

**Policy:** Never expose raw Stream video IDs to browsers. All playback URLs are **signed JWTs** with **60-second expiry** (`.cursorrules`).

### Signing (origin or Worker)

- Private key: Key Vault `cf-stream-signing-key` (PEM from Cloudflare Stream Settings).
- Claims: `sub` (video id), `kid`, `exp` (now + 60s max), optional `accessRules`.
- Algorithm: RS256 (Stream signing keys).

### Validation

- Reject missing `exp`, expired tokens, wrong `kid`, or clock skew > 5s.
- Never extend expiry client-side; re-sign on the server when URL expires.

### Moodle integration

- Moodle PHP requests a signed URL from a local plugin or pre-signed endpoint—**never** embed the signing key in PHP if avoidable; prefer Worker or short-lived server-side sign.

---

## 4. Server-Sent Events (SSE) — AI token delivery

**Goal:** Keep the browser connection open for streamed AI tokens while the Worker releases the request thread quickly—stream from upstream LLM through the Worker to the client.

### Response headers (required)

```typescript
return new Response(stream, {
  headers: {
    'Content-Type': 'text/event-stream',
    'Cache-Control': 'no-cache',
    'Connection': 'keep-alive',
  },
});
```

### Event format

```
data: {"token":"..."}\n\n
data: [DONE]\n\n
```

### Implementation pattern

1. Authenticate JWT (`auth.ts`) before opening SSE.
2. Build `ReadableStream` that pulls from upstream `fetch` body (Anthropic/OpenAI via AI Gateway).
3. Transform upstream chunks → `data: …\n\n` frames; flush incrementally.
4. On error, emit one `data: {"error":"..."}\n\n` then close.
5. Do **not** buffer the full completion in memory before responding.

### Origin thread release

- **Worker:** streaming response returns immediately; upstream read happens as the client consumes the stream.
- **Moodle PHP:** must **not** hold PHP-FPM workers for SSE—delegate to Worker; Moodle only initiates via AJAX to `ai.understandtech.app/tutor`.

Reference stub: `cloudflare-worker/ai-gateway/src/routes/tutor.ts`

---

## 5. Auth between Moodle and Worker

- Moodle issues HS256 JWT: `issuer: moodle`, `audience: ai-worker`, short TTL (5–15 min for API calls; separate from 60s Stream URLs).
- Worker validates with `MOODLE_JWT_SECRET` / shared secret in KV.
- See `src/auth.ts` for `jose` `jwtVerify` pattern.

---

## 6. Security checklist

- [ ] No API keys in source or wrangler.jsonc (secrets only)
- [ ] JWT validated on every `/tutor` and `/grade` request
- [ ] Stream URLs expire ≤ 60s
- [ ] SSE endpoints require auth; CORS restricted to `understandtech.app`
- [ ] AI tutor prompts must not reveal quiz answers, lab flags, or solutions
- [ ] Rate limit abusive clients via KV

---

## 7. Deploy

```bash
cd cloudflare-worker/ai-gateway
npm ci
npm run build   # if tsc step exists
npx wrangler deploy
npx wrangler secret put MOODLE_JWT_SECRET
npx wrangler secret put ANTHROPIC_API_KEY
```

Route: `ai.understandtech.app/*` per `wrangler.jsonc`.

---

## Related skills & docs

- Platform architecture: `.cursor/skills/understandtech-platform/`
- Moodle PHP (never calls LLM directly): `moodle-core-php-engineering` or `moodle-development`
- Playbook Phase 4: `docs/playbook.md`
- Deep patterns: [sse-streaming.md](sse-streaming.md), [stream-jwt-signing.md](stream-jwt-signing.md)
