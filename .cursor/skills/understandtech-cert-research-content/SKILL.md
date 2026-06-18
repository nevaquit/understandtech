---
name: understandtech-cert-research-content
description: >-
  Researches certification topics from official blueprints and authoritative
  sources, then auto-generates understandtech.app repo content — lesson HTML,
  GIFT banks, practice exams, and mod_ctfflag lab scenarios. Use when creating
  net-new SEC701, NET009, or APLUS content from research, expanding domains,
  closing white-paper Phase 1 gaps, or when the user asks for research-backed
  content generation before seeding Moodle.
paths:
  - "content/**"
  - "scripts/build-practice-exam-*"
  - "scripts/generate-*-quiz*"
  - "scripts/seed-*"
---

# Research-Backed Certification Content Generation

## Prerequisite gate — MUST run first

**This skill is the mandatory first step for all net-new certification content.** Agents must **NOT** auto-generate or write any of the following until research checklist steps 1–3 are complete:

- Lesson HTML (`content/<track>/lessons/*.html`)
- GIFT question banks (`*-quiz.gift`, `*-quiz-extra.gift`)
- Practice exam banks (`practice-exam-*.gift`)
- Lab scenario HTML (`content/<track>/labs/*.html`)
- Build/generate scripts (`generate-*-quiz*`, `build-practice-exam-*`)

**Steps 1–3 must be done before generation:**

1. **Scope** — track, domain/objective, gap vs launch-targets
2. **Research phase** — blueprint, authoritative sources, **gap memo with citations** ([research-sources.md](reference/research-sources.md))
3. **Artifact plan** — lesson list, question plan, lab scenario outline ([prompt-templates.md](reference/prompt-templates.md))

If the gap memo (template in research-sources.md) and artifact plan are not documented in chat or a work note, **stop** and complete research — do not proceed to step 4 or invoke generation scripts.

**Research first, generate second, validate before commit.** This skill covers the upstream research and AI-assisted authoring workflow. For repo layout, GIFT conventions, seed scripts, and launch targets, defer to `/understandtech-cert-content`.

**Always load with:** `/understandtech-cert-content` (formats + seed) and `/understandtech-platform` (architecture constraints).

## When to use which skill

| Task | Skill |
|------|-------|
| Net-new lessons, questions, labs from blueprint research | **This skill** |
| Format specs, seed PHP, CI workflows, gap counts | `/understandtech-cert-content` |
| Moodle plugin APIs, mod_page/mod_quiz | `/moodle-development` |

## Required workflow

Copy this checklist and track progress:

```
Research-content task:
- [ ] 1. Scope track + domain/objective + gap (launch-targets.md)
- [ ] 2. Research phase — blueprint, sources, gap memo (research-sources.md)
- [ ] 3. Outline artifacts — lesson list, question plan, lab scenario (prompt-templates.md)
- [ ] 4. Generate repo files under content/<track>/ (generation-pipeline.md)
- [ ] 5. Quality gates — no answer/flag leakage, formats, brand (quality-gates.md)
- [ ] 6. Build scripts — generate-*-quiz, build-practice-exam-*-gift.php
- [ ] 7. Hand off to cert-content — validate, seed staging, verify VM
```

### Step 1 — Scope the work unit

Pick a **bounded** deliverable (one domain, one objective family, or one artifact type):

| Track | Blueprint | Objectives CSV | Content dir |
|-------|-----------|----------------|-------------|
| Security+ SY0-701 | [CompTIA SY0-701](https://www.comptia.org/certifications/security) | `content/security-plus/sy0-701-objectives.csv` | `content/security-plus/` |
| Network+ N10-009 | [CompTIA N10-009](https://www.comptia.org/certifications/network) | `content/network-plus/n10-009-objectives.csv` | `content/network-plus/` |
| A+ 220-1101/1102 | [CompTIA A+](https://www.comptia.org/certifications/a) | `content/a-plus/aplus-objectives.csv` | `content/a-plus/` |

Measure current vs target: [launch-targets.md](../understandtech-cert-content/reference/launch-targets.md).

### Step 2 — Research phase (mandatory before writing)

Do **not** generate lesson HTML or GIFT until research is documented.

1. **Official blueprint** — domain weights, objective text verbatim, exam length, item types.
2. **Authoritative supplements** — vendor docs, NIST/CISA frameworks, MITRE ATT&CK (Security+), RFCs/IEEE (Network+), vendor hardware specs (A+). See [research-sources.md](reference/research-sources.md).
3. **Repo gap analysis** — list missing objectives, sub-lessons (`_scenario`, `_exam`), question counts per objective, practice exam slots, labs.
4. **Research memo** — one markdown block per work unit with cited URLs, doc versions, retrieval dates.

**Citation discipline:** every factual claim in generated content must trace to a memo source. Record `URL | version/edition | YYYY-MM-DD`.

### Step 3 — Plan artifacts

From research memo, produce an **artifact plan** before files:

| Artifact | Output path | Naming |
|----------|-------------|--------|
| Core lesson | `lessons/<code>.html` | `sy701_1_1`, `n10009_2_3`, `ap1101_1_1` |
| Scenario sub-lesson | `lessons/<code>_scenario.html` | Per cert-content suffix rules |
| Exam-focus sub-lesson | `lessons/<code>_exam.html` | Traps, distractors, “what they test” |
| Objective MCQs | `*-quiz.gift` / `*-quiz-extra.gift` | `::sy701_X_Y Title::` in name |
| Practice exam bank | `practice-exam-{1,2,3}.gift` | `::pe1_q001::` namespace |
| Lab scenario | `content/<track>/labs/<slug>.html` | `mod_ctfflag` intro only — no flag values |

For **lab relevance decisions, modality choice, flag derivation, and rich interactivity**, defer to `/understandtech-interactive-labs` after this skill's research steps 1–3 (gap memo must include lab relevance per domain).

Use [prompt-templates.md](reference/prompt-templates.md) for structured research and generation prompts. Lab-specific prompts: [prompt-templates-labs.md](../understandtech-interactive-labs/reference/prompt-templates-labs.md).

### Step 4 — Generate content

Follow [generation-pipeline.md](reference/generation-pipeline.md). Rules:

- **Original content only** — synthesize from research; never copy actual exam items or third-party question banks.
- **Objective alignment** — every lesson and tagged MCQ maps to a certmaster objective shortname.
- **Sub-lesson expansion** — prefer `_scenario` and `_exam` suffixes to reach 80–100 pages without inventing objectives.
- **AI tutor safety** — lesson prose explains concepts; no quiz answers, no lab flags, no “correct answer is B” phrasing.

### Step 5 — Quality gates

Run [quality-gates.md](reference/quality-gates.md) checklist before commit. Non-negotiable:

- No assessment answers or `UT{...}` flags in lesson HTML or lab intros.
- GIFT names carry objective tags for Knowledge Check pool; practice exams use `peN_qNNN` only.
- Lesson HTML roots in `<div class="ut-lesson-content">`; brand palette for custom inline styles.
- Validate against [content-formats.md](../understandtech-cert-content/reference/content-formats.md).

### Step 6 — Build and hand off

```bash
# Expand SEC701 to launch scale (after gap memo exists)
node scripts/generate-security-plus-launch-content.mjs
node scripts/build-practice-exams-gift.mjs all
# On VM: php scripts/build-practice-exams-gift.php all
```

Then continue with `/understandtech-cert-content` steps 4–7: seed script updates, staging seed, `verify-cert-course-pages.sh`.

## Research depth bar

| Level | When | Output |
|-------|------|--------|
| **Objective** | Single MCQ or sub-lesson | 2–3 sources, objective text quoted |
| **Domain** | Domain question expansion | Blueprint weight, 5+ sources, ATT&CK/NIST map (SEC701) |
| **Track** | New practice exam or lab set | Full blueprint review, cross-domain distribution plan |

## Anti-patterns

| Do not | Do instead |
|--------|------------|
| Write content from model memory alone | Research memo with citations first |
| Skip gap analysis | Count existing assets vs launch-targets |
| Put answers in `_exam` lessons | Teach traps and distractor logic without revealing KC answers |
| Store lab flags in repo HTML | Regex-only validation; flag derived at runtime by learner |
| Duplicate cert-content seed docs | Link to cert-content reference files |
| Commit without quality gates | Run quality-gates checklist |

## Reference files

- [research-sources.md](reference/research-sources.md) — blueprints, frameworks, citation templates
- [generation-pipeline.md](reference/generation-pipeline.md) — artifact generation order and scripts
- [quality-gates.md](reference/quality-gates.md) — pre-commit validation
- [prompt-templates.md](reference/prompt-templates.md) — copy-paste research and generation prompts

## Skill stack

| Skill | When |
|-------|------|
| `/understandtech-cert-research-content` | Research + net-new generation |
| `/understandtech-cert-content` | Formats, seeds, launch targets |
| `/understandtech-interactive-labs` | Lab design, HTML, interactivity, seed patterns (after research gate) |
| `/understandtech-platform` | Architecture, AI tutor guardrails |
| `/moodle-development` | Seed PHP changes |
