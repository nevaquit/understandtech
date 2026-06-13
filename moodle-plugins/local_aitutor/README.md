# local_aitutor

AI Tutor sidebar for understandtech.app (white paper §3.1, playbook prompt 3.3). All LLM traffic goes through the Cloudflare AI Gateway Worker — this plugin never calls Anthropic/OpenAI directly.

## Features

| Capability | Implementation |
|------------|----------------|
| Sidebar on course + activity pages | Moodle Hooks API (`hook_callbacks.php`) |
| Short-lived HS256 JWT | `classes/api.php`, `classes/jwt_helper.php` |
| SSE streaming to Worker | `amd/src/tutor_sidebar.js` → `https://ai.understandtech.app/tutor` |
| Learner context (readiness, quizzes, activity) | `classes/context_builder.php` → Worker `learner_context` |
| Conversation history + multi-turn chat | `aitutor_conversations` / `aitutor_messages` + history dropdown |
| Transcript webhook | `/local/aitutor/webhook.php` (`X-Moodle-Signature` HMAC) |
| RAG course chunks | `classes/rag_context.php`, `classes/ingest.php` (keyword fallback; pgvector Phase 2) |
| Privacy export/delete | `classes/privacy/provider.php` |

## Configuration

**Site administration → Plugins → Local plugins → AI Tutor**

- Enable course sidebar (default on)
- Worker URL (default `https://ai.understandtech.app/tutor`)
- JWT expiry (default 300 seconds)
- Shared secret fallback — prefer `AITUTOR_WORKER_SHARED_SECRET` in `/etc/moodle/env` (Key Vault `cf-worker-shared-secret`)

## Web services (AJAX)

- `local_aitutor_get_jwt`
- `local_aitutor_get_conversations`
- `local_aitutor_get_messages`
- `local_aitutor_get_rag_context`

## Install

Copy to `{moodleroot}/local/aitutor/`, run `php admin/cli/upgrade.php`, purge caches, `grunt amd` for production JS builds.

## Pedagogical constraint

The tutor must not reveal assessment answers, lab flags, or quiz solutions. Enforcement is in the Worker system prompt (`cloudflare-worker/ai-gateway/src/prompts.ts`); Moodle redacts flag patterns in RAG chunks.
