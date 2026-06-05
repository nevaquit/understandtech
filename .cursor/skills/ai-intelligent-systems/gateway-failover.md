# AI Gateway Failover & Caching

## Client flow (`gateway.ts` target)

```typescript
async function completeWithFallback(
  env: Env,
  payload: LlmRequest,
): Promise<Response> {
  const cacheKey = hashRequest(payload);
  const cached = await env.KV.get(cacheKey);
  if (cached) return sseFromCached(cached);

  try {
    const res = await callAnthropicViaGateway(env, payload);
    if (res.ok) {
      await env.KV.put(cacheKey, await res.clone().text(), { expirationTtl: 60 });
      return res;
    }
    if (shouldFallback(res.status)) {
      return callOpenAiViaGateway(env, payload);
    }
    return res;
  } catch (e) {
    if (isTimeout(e)) {
      return callOpenAiViaGateway(env, payload);
    }
    throw e;
  }
}

function shouldFallback(status: number): boolean {
  return status >= 500 || status === 429;
}
```

## AI Gateway URL pattern

```
https://gateway.ai.cloudflare.com/v1/{account_id}/{gateway_id}/anthropic/v1/messages
https://gateway.ai.cloudflare.com/v1/{account_id}/{gateway_id}/openai/chat/completions
```

Bind keys in Cloudflare dashboard; reference via `env.AI_GATEWAY_*` or Wrangler vars.

## Timeouts

- First-token timeout: **10s** on Anthropic before fallback (playbook)
- Total stream: respect Worker limits; cancel upstream on client disconnect

## Secrets (Wrangler)

```
wrangler secret put ANTHROPIC_API_KEY
wrangler secret put OPENAI_API_KEY
wrangler secret put MOODLE_JWT_SECRET
```

## Moodle audit webhook

POST anonymized metadata to `local_aitutor/webhook.php`:
- `prompt_version`, `provider`, `cache_hit`, `duration_ms`, `courseid`, `userid`

Never POST full student messages or LLM responses in production logs unless encrypted storage policy allows.
