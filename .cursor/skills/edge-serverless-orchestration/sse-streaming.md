# SSE Streaming — Edge Patterns

## When to use SSE vs WebSockets

| Protocol | Use on understandtech.app |
|----------|---------------------------|
| **SSE** | AI tutor token stream (`POST /tutor` → `text/event-stream`) |
| **WebSockets** | Not used in Phase 4; prefer SSE for one-way LLM output |

## Worker streaming from upstream LLM

```typescript
async function streamFromUpstream(upstream: Response, controller: ReadableStreamDefaultController): Promise<void> {
  const reader = upstream.body?.getReader();
  if (!reader) throw new Error('No upstream body');

  const decoder = new TextDecoder();
  try {
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      const text = decoder.decode(value, { stream: true });
      // Parse provider-specific chunks; emit SSE frames:
      controller.enqueue(new TextEncoder().encode(`data: ${JSON.stringify({ token: text })}\n\n`));
    }
    controller.enqueue(new TextEncoder().encode('data: [DONE]\n\n'));
  } finally {
    reader.releaseLock();
  }
}
```

## Client (Moodle AMD / fetch)

```javascript
const response = await fetch(workerUrl, { method: 'POST', headers: { Authorization: `Bearer ${jwt}` }, body });
const reader = response.body.getReader();
// Parse SSE lines split on \n\n
```

## Failure modes

- **Client disconnect:** abort upstream `fetch` when `request.signal` aborts.
- **Upstream timeout:** Cloudflare Worker CPU/time limits—keep chunks small; use AI Gateway for retries.
- **Buffer bloat:** never `await response.text()` on full LLM output before streaming.
