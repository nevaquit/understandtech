# Stream JWT Signing — 60-Second URLs

## Policy

Raw Cloudflare Stream IDs must never appear in HTML or API responses exposed to learners. Issue signed JWT playback URLs with **maximum 60-second** validity.

## Signing key source

- Cloudflare Dashboard → **Stream → Settings → Signing keys** → Create
- Store PEM in Azure Key Vault as `cf-stream-signing-key`
- Note the **Key ID** (`kid`) for JWT header

## JWT shape (conceptual)

```json
{
  "alg": "RS256",
  "kid": "<stream-signing-key-id>"
}
{
  "sub": "<cloudflare-stream-video-id>",
  "kid": "<same-key-id>",
  "exp": 1717600000,
  "accessRules": []
}
```

`exp` must be `Math.floor(Date.now() / 1000) + 60` or less.

## Verification (Worker or edge)

1. Parse JWT header → resolve `kid`
2. Verify signature with Stream public key / Cloudflare docs
3. Reject if `exp < now` or `exp - iat > 60`

## Anti-patterns

- Long-lived or non-expiring video URLs
- Embedding signing PEM in Moodle PHP or browser JS
- Returning unsigned `videodelivery.net` paths with raw IDs

## Reference

- [Cloudflare Stream signed URLs](https://developers.cloudflare.com/stream/viewing-videos/securing-your-stream/)
- Repo constraint: `.cursorrules` rule 6
