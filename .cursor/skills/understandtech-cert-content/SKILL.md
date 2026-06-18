---
name: understandtech-cert-content
description: >-
  Produces certification course content at launch scale for understandtech.app —
  lesson HTML, GIFT question banks, full-length practice exams, and mod_ctfflag
  labs mapped to CompTIA objectives. Use when expanding SEC701, NET009, or APLUS
  tracks, seeding Moodle courses, closing white-paper Phase 1 content gaps
  (80-100 lessons, 400 questions, 3 practice exams, 3 labs), writing GIFT,
  extracting CyberKraft lessons, or running seed-* workflows on staging/production.
paths:
  - "content/**"
  - "scripts/seed-*"
  - "scripts/extract-*"
  - "scripts/generate-*-quiz*"
  - "scripts/lib/moodle-cert-*"
  - ".github/workflows/seed-*.yml"
---

# Certification Content at Launch Scale

Build **lessons**, **question banks**, **practice exams**, and **labs** for understandtech.app certification tracks, then seed Moodle and verify on VM.

**Always load with:** `/understandtech-platform` (architecture constraints) and `/moodle-development` or `moodle-core-php-engineering` (when editing seed PHP).

## Launch targets (white paper Phase 1 — SEC701)

| Asset | Phase 1 target | Current baseline | Gap |
|-------|----------------|------------------|-----|
| Lesson pages | 80–100 | 28 (1 per objective) | ~52–72 pages |
| Question bank | ~400 | ~84 GIFT items | ~316 questions |
| Full-length practice exams | 3 | 0 | 3 timed quizzes |
| Hands-on labs (`mod_ctfflag`) | 3 | 0 seeded | 3 lab activities |

NET009 and APLUS have separate Phase 2 targets — see [reference/launch-targets.md](reference/launch-targets.md).

## Required workflow

Copy this checklist and track progress:

```
Cert content task:
- [ ] 1. Confirm track + gap (launch-targets.md)
- [ ] 2. Author/update repo content under content/<track>/
- [ ] 3. Validate formats (content-formats.md)
- [ ] 4. Extend seed script if new activity types (practice exam / lab)
- [ ] 5. Run extract/generate scripts locally
- [ ] 6. Commit content + seed changes
- [ ] 7. Seed staging → verify → seed production
- [ ] 8. Run verify-cert-course-pages + quiz dedup cleanup
```

### Step 1 — Pick track and measure gap

| Track | Course shortname | Content dir | Objectives CSV | Seed script |
|-------|------------------|-------------|----------------|-------------|
| Security+ SY0-701 | `SEC701` | `content/security-plus/` | `sy0-701-objectives.csv` | `scripts/seed-security-plus-course.php` |
| Network+ N10-009 | `NET009` | `content/network-plus/` | `n10-009-objectives.csv` | `scripts/seed-network-plus-course.php` |
| A+ 220-1101/1102 | `APLUS` | `content/a-plus/` | `aplus-objectives.csv` | `scripts/seed-comptia-a-plus-course.php` |

Count current assets:

```bash
# Lessons (example SEC701)
ls content/security-plus/lessons/*.html | wc -l

# GIFT questions tagged with objective id
rg -c '^::.*sy701_' content/security-plus/*.gift
```

### Step 2 — Close the lesson gap (28 → 80–100)

**Default model today:** one `mod_page` per certmaster objective (`sy701_X_Y`).

**To reach 80–100 without changing blueprint:** add **sub-lessons per objective** (recommended suffixes):

| Suffix | Purpose | Example shortname |
|--------|---------|-------------------|
| `_core` | Primary lesson (existing) | `sy701_1_1` |
| `_scenario` | Scenario / case study | `sy701_1_1_scenario` |
| `_exam` | Exam traps & distractors | `sy701_1_1_exam` |

Rules:

- Every lesson HTML **must** root in `<div class="ut-lesson-content">` (see existing `content/security-plus/lessons/sy701_1_1.html`).
- Optional: `content/<track>/diagrams/<code>.html` merged by seed loaders.
- Optional Stream video: embed via `local_certmaster_render_stream_player()` — **videoid must appear in page content** for JWT refresh (see `docs/stream-lesson-embed-snippet.php`).
- Do **not** put quiz answers or lab flags in lesson HTML (AI tutor guardrail).

**Extract from source material** (when CyberKraft paths available):

```bash
node scripts/extract-security-plus-lessons.mjs
node scripts/extract-security-plus-diagrams.mjs
node scripts/extract-security-plus-course-notes.mjs
```

Source paths: `content/security-plus/sources.json`.

### Step 3 — Close the question bank gap (~84 → 400)

**GIFT is canonical.** One question name **must** embed the objective tag for CertMaster linking:

```gift
::sy701_1_1 Security control categories::Which of the following...?{
=Correct answer
~Distractor one
~Distractor two
~Distractor three
}
```

Files per track:

| File | Role |
|------|------|
| `<exam>-quiz.gift` | Base: 1 MCQ per objective (required) |
| `<exam>-quiz-extra.gift` | Expansion: 2+ MCQs per objective |

Generate extra questions:

```bash
node scripts/generate-security-plus-quiz-gift.mjs
node scripts/generate-network-plus-quiz-gift.mjs
```

**Target:** ~14 questions per objective × 28 objectives ≈ 392 (round to 400).

After import, seed scripts call `ut_dedupe_question_bank_category()` and build **Domain N Knowledge Check** quizzes (one question per objective, curated). Do not break objective tags in question names.

### Step 4 — Add full-length practice exams (missing today)

White paper: **3 timed practice exams** per launch track.

**Convention (add to seed script):**

| Quiz name | Section | Questions | Time limit | Behaviour |
|-----------|---------|-----------|------------|-----------|
| `Practice Exam 1` | 6 (new) | 90 | 90 min | `certmasterconfidence` |
| `Practice Exam 2` | 7 | 90 | 90 min | same |
| `Practice Exam 3` | 8 | 90 | 90 min | same |

Implementation pattern:

1. Add `content/<track>/practice-exam-{1,2,3}.gift` — 90 questions, mixed domains, **unique** `::pe1_q001::` names (do not reuse `sy701_*` tags for slot dedup).
2. Extend seed script: import GIFT → create quiz with `security_plus_sync_quiz()` pattern → set `timelimit`, `grade`, shuffle, one attempt policy.
3. Add section 6+ to course (`course_create_sections_if_missing`).
4. Run `scripts/cleanup-cert-knowledge-checks.php` only on **Knowledge Check** quizzes — not practice exams.

Details: [reference/practice-exams-and-labs.md](reference/practice-exams-and-labs.md).

### Step 5 — Seed labs (`mod_ctfflag`)

White paper Phase 1: **3 Security+ labs** (Sentinel/IR themed; start with CTF flags).

Each lab activity:

- Module: `mod_ctfflag`
- Name: `Lab N: <title>`
- Section: end of relevant domain or dedicated **Labs** section
- `expected_flag_regex`: `UT\{[A-Za-z0-9_\-]+\}` — flag never stored server-side
- Intro HTML: scenario + instructions (no flag value)
- Links to `local_certmaster` objective via admin mapping or lesson cross-links

Seed labs in the track's `seed-*-course.php` (pattern in reference). Re-run awards XP via `local_gamification` when `block_xp` installed.

### Step 6 — Seed and verify on VM

**Staging first**, then production:

```bash
# GitHub Actions (preferred)
gh workflow run seed-sec701.yml -f target=staging
gh workflow run seed-sec701.yml -f target=production

# Or on VM directly
sudo -u www-data php /opt/understandtech-plugins/scripts/seed-security-plus-course.php
sudo /usr/bin/bash /opt/understandtech-plugins/scripts/fix-sec701-course-filters-vm.sh
sudo /usr/bin/bash /opt/understandtech-plugins/scripts/cleanup-cert-knowledge-checks.php SEC701
sudo /usr/bin/bash /opt/understandtech-plugins/scripts/verify-cert-course-pages.sh
```

Post-seed: `post-deploy-stabilize-vm.sh` disables page text filters (prevents DB errors on large HTML).

### Step 7 — Acceptance criteria (mark task done)

- [ ] Lesson count meets track milestone (document actual vs target in PR)
- [ ] GIFT import: `question_objective_links` > 0 in seed output
- [ ] Each domain has **Domain N Knowledge Check** with **one question per objective**, no duplicates
- [ ] Practice exams exist (when in scope): 90 Q, timed, confidence behaviour
- [ ] Labs exist (when in scope): 3 `mod_ctfflag` instances, submit flow works
- [ ] `verify-cert-course-pages.sh` passes — `ut-lesson-content` present, no DB errors
- [ ] Strict web health passes (`verify-moodle-web-health.sh`)
- [ ] No assessment answers or flags in lesson HTML / AI-ingestible text

## Content quality bar

- **Original questions only** — instructors write; never copy actual exam items (white paper §6.1).
- **Objective alignment** — every lesson and MCQ maps to a certmaster objective shortname.
- **Confidence pedagogy** — domain quizzes use `qbehaviour_certmasterconfidence`.
- **Accessibility** — headings hierarchy, alt text on diagrams, sufficient contrast in custom HTML.
- **Idempotent seeds** — re-running seed scripts upserts pages; safe for CI.

## Anti-patterns

| Do not | Do instead |
|--------|------------|
| Commit Moodle core or course DB dumps | Content in `content/` + seed PHP |
| Hand-edit production Moodle pages | Change repo + re-seed |
| Strip `sy701_*` tags from GIFT names | Keep tags for objective linking |
| Create 200-question Knowledge Check quizzes | One Q per objective; full exams separate |
| Expose Stream UIDs without signed player | `local_certmaster_render_stream_player()` |
| Put flags/answers in page HTML | CTF regex validation only |

## Reference files

- [launch-targets.md](reference/launch-targets.md) — per-track numbers and milestones
- [content-formats.md](reference/content-formats.md) — HTML, GIFT, CSV, diagrams
- [seed-pipeline.md](reference/seed-pipeline.md) — scripts, libs, CI workflows
- [practice-exams-and-labs.md](reference/practice-exams-and-labs.md) — new activity patterns

## Skill stack

| Skill | When |
|-------|------|
| `/understandtech-cert-content` | This workflow |
| `/understandtech-platform` | Architecture, constraints |
| `/moodle-development` | Seed PHP, mod_page, mod_quiz APIs |
| `behat-automation-skill` | Browser tests after major content drops |
