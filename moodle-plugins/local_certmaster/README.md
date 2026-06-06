# local_certmaster

CertMaster-equivalent certification readiness tracking for understandtech.app.

## Features (v1.0)

- Certification framework schema (certifications, domains, objectives)
- Security+ SY0-701 domain seed on install (blueprint weights 11/22/25/28/14%)
- Confidence-aware mastery algorithm per white paper Section 2.4
- `api::get_user_readiness()` for dashboard/radar JSON
- Hourly scheduled mastery recalculation task

## Cloudflare Stream (signed URLs)

| Item | Value |
|------|-------|
| Key Vault secret | `cf-stream-signing-key` (`stream_helper::KEY_VAULT_SECRET`) |
| VM PEM path | `/etc/moodle/cf-stream-signing-key.pem` (via `scripts/setup-moodle-env-vm.ps1`) |
| JWT expiry | 60 seconds (RS256) |

### PHP API

- `stream_helper::sign_manifest_url($videoid)` — HLS manifest URL
- `stream_helper::sign_iframe_url($videoid)` — iframe embed URL
- `local_certmaster_render_stream_player($videoid)` — Mustache + AMD player with auto-refresh

### Lesson embed

```php
// In a Page resource or theme callback — video UID stored server-side only.
echo local_certmaster_render_stream_player($videoid);
```

Preview page (admin test video): `/local/certmaster/player.php` (uses **Test Stream video ID** setting).

### AMD refresh

`amd/src/stream_player.js` calls `local_certmaster_get_stream_iframe_url` every 50s so the iframe JWT stays valid.

## Not yet implemented

- `certmaster_confidence` question behaviour UI
- CSV objective import admin UI
- `amd/src/radar_chart.js` (consumed by `block_examreadiness`)

## Install

Copy to `{moodleroot}/local/certmaster/` and run `php admin/cli/upgrade.php --non-interactive`.
