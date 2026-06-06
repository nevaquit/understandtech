# local_aitutor

AI Tutor sidebar for understandtech.app. All LLM traffic goes through the Cloudflare AI Gateway Worker — this plugin never calls Anthropic/OpenAI directly.

## Configuration

- Worker URL: `https://ai.understandtech.app/tutor` (admin setting)
- Shared secret: `AITUTOR_WORKER_SHARED_SECRET` in `/etc/moodle/env` (from Key Vault `cf-worker-shared-secret`)
- Webhook: `/local/aitutor/webhook.php` (HMAC via `X-Moodle-Signature`)

## RAG (Phase 2 — post–v1.0.0 core)

Course-grounded retrieval is stubbed: `classes/rag_context.php` returns `[]` until pgvector ingestion ships. Worker-side context injection plan: [docs/rag-phase2.md](../../docs/rag-phase2.md). Deep reference: `.cursor/skills/ai-intelligent-systems/SKILL.md`.

## Install

Copy to `{moodleroot}/local/aitutor/`, run `php admin/cli/upgrade.php`, purge caches, `grunt amd` for production JS builds.
