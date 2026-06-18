# Generation Pipeline

Order of operations after research memo and artifact plan are approved.

## 1. Lessons

| Step | Action |
|------|--------|
| 1 | Copy structure from nearest existing lesson in `content/<track>/lessons/` |
| 2 | Root wrapper: `<div class="ut-lesson-content">` |
| 3 | Include scenario callouts, diagrams (reuse `content/<track>/snippets/` where possible) |
| 4 | Map filename → certmaster objective shortname (seed uses CSV + filename) |

Sub-lesson suffixes (see cert-content skill):

| Suffix | File | Focus |
|--------|------|-------|
| (none) | `sy701_X_Y.html` | Core concept |
| `_scenario` | `sy701_X_Y_scenario.html` | Case study, SOC/helpdesk narrative |
| `_exam` | `sy701_X_Y_exam.html` | Distractor patterns, “what CompTIA tests” — **no KC answers** |

Optional companions (when research supports them):

- `content/<track>/course-notes/<code>.html`
- `content/<track>/supplements/<code>.html`
- `content/<track>/diagrams/<code>.html` (injected by seed when present)

## 2. GIFT question banks

Append to existing files before creating new ones:

| Track | Primary | Extra pool |
|-------|---------|------------|
| SEC701 | `sy0-701-quiz.gift` | `sy0-701-quiz-extra.gift` |
| NET009 | `n10-009-quiz.gift` | `n10-009-quiz-extra.gift` |
| APLUS | `aplus-quiz.gift` | `aplus-quiz-extra.gift` |

**Naming:** `::sy701_2_3 Short title::` — objective tag must appear in question name for seed mapping.

**Per objective quota:** ~14 MCQs at launch scale (400 ÷ 28 objectives). Prefer 4-option single-answer MCQ.

**Generator scripts** (expand banks from objectives CSV):

```bash
node scripts/generate-security-plus-quiz-gift.mjs
node scripts/generate-network-plus-quiz-gift.mjs
node scripts/generate-comptia-a-plus-quiz-gift.mjs
```

Review generated GIFT manually or via quality-gates before commit.

## 3. Practice exams

Independent namespaces — do not reuse `sy701_*` tags in practice exam banks.

| File | Tag pattern | Count |
|------|-------------|-------|
| `practice-exam-1.gift` | `::pe1_q001::` | 90 (SEC701) |
| `practice-exam-2.gift` | `::pe2_q001::` | 90 |
| `practice-exam-3.gift` | `::pe3_q001::` | 90 |

Build PE1 from objective banks (blueprint-weighted sampling):

```bash
php scripts/build-practice-exam-1-gift.php
```

Distribution target (SY0-701): D1 11, D2 20, D3 16, D4 25, D5 18 = 90.

## 4. Labs (`mod_ctfflag`)

| Step | Action |
|------|--------|
| 1 | Write scenario HTML in `content/<track>/labs/lab-N-<slug>.html` |
| 2 | Include derivable data (hashes, IDs, log excerpts) — **never** the flag string |
| 3 | Document expected flag *format* only (`UT{first 8 hex of SHA-256}`) |
| 4 | Seed via `security_plus_upsert_ctfflag()` pattern in `seed-security-plus-course.php` |

Lab regex examples: `UT\{[A-Fa-f0-9]{8}\}` for hash-prefix flags.

## 5. Hand off to cert-content skill

After files exist in repo:

1. Validate formats — [content-formats.md](../../understandtech-cert-content/reference/content-formats.md)
2. Extend seed script only if new activity types or sections needed
3. Commit content + seed changes
4. Run `seed-sec701.yml` (or NET009/APLUS) on staging → verify → production

## Generation principles

- **Synthesize** from cited research; do not copy exam dumps or third-party question banks.
- **One objective, many angles** — scenario + exam sub-lessons beat single thin pages.
- **Progressive difficulty** — Knowledge Check pool = objective mastery; practice exams = mixed blueprint.
- **Idempotent seeds** — content names must match seed lookup keys (quiz names, lab titles).
