# RAG with pgvector — Phase 2

Course-grounded AI tutor context is **implemented** in this repo. Retrieval runs at the Cloudflare AI Gateway Worker edge; Moodle exposes chunks via `rag.php` / `local_aitutor_get_rag_context`.

## Implemented components

| Component | State |
|-----------|--------|
| `local_aitutor\classes\ingest::index_course()` | Chunks pages/labels/books; excludes quiz, ctfflag, assign, lesson |
| `local_aitutor\classes\task\reindex_courses_task` | Nightly cert-course sweep |
| `local_aitutor\classes\rag_context::retrieve()` | pgvector cosine search with keyword fallback |
| `mdl_aitutor_embeddings` | Chunk store + optional `vector(1536)` column (upgrade 2026060803) |
| Worker `src/rag/embed.ts` | Query embedding via AI Gateway |
| Worker `src/rag/retrieve.ts` | `fetchRagChunks` scoped to JWT `courseid` |
| Worker `src/rag/prompt.ts` | Injects `## Course context` blocks after system prompt |
| Worker `src/routes/tutor.ts` | RAG-augmented SSE tutor stream |
| `local_aitutor_get_rag_context` | Web service wrapping `rag_context::retrieve()` |
| `scripts/reindex-rag-cert-courses.php` | Manual reindex for SEC701, NET009, APLUS after content seed |

## Architecture flow

```
Browser → Moodle JWT → Worker /tutor
                          │
                          ├─ validateJwt() → courseid from claims
                          ├─ embedQuery(userMessage)
                          ├─ fetchRagChunks(courseid, vector)
                          │     └─ POST Moodle rag.php (JWT, courseid filter)
                          ├─ assemblePrompt(system + ragBlocks + history + user)
                          ├─ AI Gateway → Anthropic (fallback OpenAI)
                          └─ SSE stream + webhook audit
```

Worker never holds Postgres credentials. Moodle PHP never calls LLM providers directly.

## Safety (non-negotiable)

- One `courseid` per retrieval query (from JWT, not client body alone)
- Never index quiz banks, lab flags, or answer keys at ingest time
- Drop chunks matching flag/answer patterns before prompt assembly
- Cache key includes `TUTOR_SYSTEM_PROMPT_VERSION` and RAG fingerprint

## Ops checklist

| Step | Owner | Notes |
|------|-------|-------|
| Enable `vector` extension on Azure PostgreSQL | Ops | Same cluster as Moodle; origin-only via PgBouncer |
| Run Moodle upgrade for `local_aitutor` | Deploy | Creates `mdl_aitutor_embeddings` + pgvector column |
| Seed cert course content | Content | SEC701, NET009, APLUS via seed scripts |
| Reindex embeddings | Ops | `scripts/reindex-rag-cert-courses.php` on VM |
| Verify tutor grounding | QA | Ask concept question; response should cite course material |

## Advanced AI (related)

See [advanced-ai.md](advanced-ai.md) for content generation (`POST /content`), LLM study plans (`POST /study-plan`), and predictive readiness (`readiness_predictor`).

## Related

- [ai-intelligent-systems skill](../.cursor/skills/ai-intelligent-systems/SKILL.md) §1
- [advanced-ai.md](advanced-ai.md)
- `cloudflare-worker/ai-gateway/src/routes/tutor.ts`
