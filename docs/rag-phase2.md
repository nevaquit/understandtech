# RAG with pgvector — Phase 2 (post–v1.0.0 core)

Course-grounded AI tutor context is **deferred** until after the core v1.0.0 release. The tutor works today with Socratic system prompts only; RAG adds retrieval from course content without cross-course leakage.

## Current stub

| Component | State |
|-----------|--------|
| `local_aitutor\classes\rag_context::retrieve()` | Returns `[]` until pgvector enabled |
| Worker `src/routes/tutor.ts` | Assembles prompt from `TUTOR_SYSTEM_PROMPT` + user messages; no RAG blocks yet |
| `body.context` in tutor POST | Moodle may pass `courseid`, `activityid`, `conversation_id` via JWT claims |

## Worker-side context injection plan

Retrieval runs at the **edge** (AI Gateway Worker), not in Moodle PHP calling OpenAI/Anthropic directly.

```
Browser → Moodle JWT → Worker /tutor
                          │
                          ├─ validateJwt() → courseid from claims
                          ├─ embedQuery(userMessage)  [Phase 2]
                          ├─ fetchRagChunks(courseid, vector)  [Phase 2]
                          │     └─ origin API or Hyperdrive read (courseid filter mandatory)
                          ├─ assemblePrompt(system + ragBlocks + history + user)
                          ├─ AI Gateway → Anthropic (fallback OpenAI)
                          └─ SSE stream + webhook audit
```

### Planned Worker modules

| File | Role |
|------|------|
| `src/rag/embed.ts` | Normalize query; call embedding model via AI Gateway |
| `src/rag/retrieve.ts` | Top-k cosine search scoped to `courseid`; redact flag/answer patterns |
| `src/rag/prompt.ts` | Inject `## Course context` blocks after system prompt, before history |
| `src/cache.ts` | Extend cache key: `prompt_version + rag_hash + messages` (60s TTL) |

### Origin API option (preferred)

Moodle exposes `local_aitutor_get_rag_context` web service:

1. Worker POSTs with service token or signed JWT including `courseid`
2. PHP `rag_context::retrieve()` runs pgvector query via `$DB` (PgBouncer)
3. Returns redacted `{chunks: [{content, source_type}]}` — never raw SQL to client

Worker never holds Postgres credentials.

### Safety (non-negotiable)

- One `courseid` per retrieval query (from JWT, not client body alone)
- Never index quiz banks, lab flags, or answer keys at ingest time
- Drop chunks matching flag/answer patterns before prompt assembly
- Cache key includes `TUTOR_SYSTEM_PROMPT_VERSION`

## Phase 2 deliverables

| Step | Owner | Notes |
|------|-------|-------|
| Enable `vector` extension on Azure PostgreSQL | Ops | Same cluster as Moodle; origin-only via PgBouncer |
| `mdl_aitutor_embeddings` table | `local_aitutor` | Schema in `.cursor/skills/ai-intelligent-systems/rag-pgvector.md` |
| Ingestion adhoc task | `local_aitutor` | Chunk pages/labels; **exclude** quiz banks and lab flags |
| Worker `fetchRagChunks` | `ai-gateway` | Embed query; filter `courseid`; KV cache 60s |
| Moodle web service | `local_aitutor` | Wraps `rag_context::retrieve()` for Worker |

## Related

- [ai-intelligent-systems skill](../.cursor/skills/ai-intelligent-systems/SKILL.md) §1
- [rag-pgvector.md](../.cursor/skills/ai-intelligent-systems/rag-pgvector.md)
- `cloudflare-worker/ai-gateway/src/routes/tutor.ts` — integration point
