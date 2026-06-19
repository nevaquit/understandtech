# Advanced AI — implementation plan

Aligned to `docs/white-paper.md` §3 (tutoring, grading, content production, adaptive learning) and Phase 2+ roadmap.

## Skills to apply (in order)

| Pillar | Primary skills | Repo touchpoints |
|--------|----------------|------------------|
| **RAG** | `/ai-intelligent-systems`, `/edge-serverless-orchestration`, `/moodle-core-php-engineering` | `local_aitutor` ingest + `rag_context`, Worker `src/rag/*`, `rag.php` |
| **Content generation** | `/ai-intelligent-systems`, `/understandtech-cert-content`, `/moodle-core-php-engineering` | Worker `/content`, `local_aitutor` drafts (instructor review queue) |
| **LLM adaptive plans** | `/ai-intelligent-systems`, `/lms-workflow`, `/mathematical-ui-design-engineering` | Worker `/study-plan`, `local_certmaster\study_plan`, `block_studyplan` |
| **Predictive readiness** | `/ai-intelligent-systems`, `/mathematical-ui-design-engineering` | `local_certmaster\readiness_predictor`, `block_examreadiness` |

Supporting: `/understandtech-platform` (architecture), `/iac-async-cloud-devops` (pgvector on Azure PG).

## Architecture constraints (non-negotiable)

- Moodle PHP **never** calls LLM providers directly — all inference via `cloudflare-worker/ai-gateway/`
- Tutor and content flows **must not** reveal assessment answers, lab flags, or quiz solutions
- RAG retrieval scoped by `courseid` from JWT; quiz banks and ctfflag modules excluded at ingest
- AI-generated content is **draft-only** until an instructor publishes

## Pillar 1 — RAG (course-grounded tutor)

**Status:** Implemented in repo; requires VM pgvector + post-seed reindex.

| Component | Role |
|-----------|------|
| `ingest::index_course()` | Chunk pages/labels; exclude quiz/ctfflag |
| `reindex_courses_task` | Nightly full cert-course sweep |
| `rag_context::retrieve()` | pgvector or keyword fallback |
| Worker `fetchRagChunks` | Embed query → Moodle `rag.php` |
| `scripts/reindex-rag-cert-courses.php` | Manual reindex after content seed |

## Pillar 2 — Content generation (instructor-reviewed)

**Use cases (white paper §3.3):** lesson summaries, quiz drafts, flashcards, scenario variants.

| Flow | |
|------|--|
| Instructor selects type + source lesson in Moodle | |
| Moodle → Worker `POST /content` with JWT | |
| Worker returns structured draft JSON | |
| Stored in `aitutor_content_drafts` (`status=draft`) | |
| Instructor reviews at `/local/aitutor/drafts.php` | |

## Pillar 3 — LLM adaptive study plans

**Flow:**

1. Deterministic engine (`study_plan::get_weakest_objectives`) picks 5 blueprint-weighted weak objectives
2. Moodle calls Worker `POST /study-plan` with mastery profile + misconception flags
3. LLM returns enriched activities: `type` (lesson_review | practice_quiz | lab), `minutes`, `reason`, `summary`
4. URLs and mastery scores merged from deterministic layer (LLM does not invent links)
5. `block_studyplan` surfaces plan; hourly `generate_study_plans_task` refreshes active learners

## Pillar 4 — Predictive exam readiness

**Phase A (this sprint):** Deterministic weighted readiness + cohort pass-rate adjustment when `certmaster_exam_outcomes` has enough samples.

| Field | Meaning |
|-------|---------|
| `overall_readiness` | Blueprint-weighted mastery (existing) |
| `predictive_readiness` | Cohort-adjusted score (same scale 0–100) |
| `pass_probability` | Estimated P(pass) from historical band |
| `prediction_model` | `deterministic` or `cohort_adjusted` |

Outcomes recorded via `readiness_predictor::record_outcome()` (admin/reporting or future learner self-report form). Threshold: **20** outcomes minimum for cohort model (white paper target: 100 for production ML).

## Verification checklist

- [x] `POST /study-plan` and `POST /content` return 401 without JWT (Worker tests — 8/8 pass)
- [ ] Study plan regenerates with LLM summary when Worker URL configured
- [ ] Exam readiness block shows pass probability when outcomes exist
- [ ] RAG reindex script runs for SEC701, NET009, APLUS
- [ ] Content drafts require teacher capability; never auto-published

## Related docs

- [rag-phase2.md](rag-phase2.md)
- [.cursor/skills/ai-intelligent-systems/SKILL.md](../.cursor/skills/ai-intelligent-systems/SKILL.md)
- `docs/playbook.md` Phase 4 (AI Gateway Worker)
