# local_aitutor

AI Tutor sidebar for understandtech.app. All LLM traffic goes through the Cloudflare AI Gateway Worker — this plugin never calls Anthropic/OpenAI directly.

## Configuration

- Worker URL: `https://ai.understandtech.app/tutor` (admin setting)
- Shared secret: `AITUTOR_WORKER_SHARED_SECRET` in `/etc/moodle/env` (from Key Vault `cf-worker-shared-secret`)
- Webhook: `/local/aitutor/webhook.php` (HMAC via `X-Moodle-Signature`)

## Install

Copy to `{moodleroot}/local/aitutor/`, run `php admin/cli/upgrade.php`, purge caches, `grunt amd` for production JS builds.
