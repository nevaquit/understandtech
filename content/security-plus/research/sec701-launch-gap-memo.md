# Gap memo: SEC701 launch-scale content

Date: 2026-06-18  
Researcher: understandtech content pipeline  
Blueprint: CompTIA Security+ SY0-701 (5 domains, 28 objectives)  
Sources: [CompTIA Security+](https://www.comptia.org/certifications/security) | Retrieved: 2026-06-18 | `content/security-plus/sy0-701-objectives.csv`

## Targets (white paper Phase 1 / launch-targets.md)

| Asset | Current | Milestone B (launch) | Delta |
|-------|---------|----------------------|-------|
| Lesson pages | 28 core | 84 (core + scenario + exam) | +56 sub-lessons |
| Question bank | 84 | 400 | +316 |
| Practice exams | 1 seeded | 3 × 90 Q | +2 exams |
| Labs | 1 seeded | 3 | +2 labs |

## Non-duplication strategy

| Layer | Purpose | Must NOT |
|-------|---------|----------|
| Core (`sy701_X_Y.html`) | Concept teaching | Repeat in sub-lessons |
| Scenario (`*_scenario.html`) | Enterprise case narrative | Copy core bullet lists |
| Exam focus (`*_exam.html`) | Distractor logic, traps | Reveal KC answers |
| GIFT launch pool | Additional MCQs | Duplicate existing stems |
| Practice exams | Mixed blueprint assessment | Reuse KC slot-for-slot |

## Objectives — sub-lesson plan (all 28)

Every objective receives `_scenario` and `_exam` companions. Scenario pages use fictional org narratives; exam pages teach trap patterns without answer keys.

## Question bank plan

- Keep `sy0-701-quiz.gift` (28) + `sy0-701-quiz-extra.gift` (56) unchanged
- Add `sy0-701-quiz-launch.gift` (~316 items, ~11 per objective) via `generate-security-plus-launch-content.mjs`
- Target post-import: **398–400** unique objective-tagged items

## Practice exams

| Exam | File | Selection |
|------|------|-----------|
| PE1 | `practice-exam-1.gift` | `build-practice-exam-1-gift.php` (existing) |
| PE2 | `practice-exam-2.gift` | `build-practice-exams-gift.php --exam=2` |
| PE3 | `practice-exam-3.gift` | `build-practice-exams-gift.php --exam=3` |

Each: 90 questions, blueprint-weighted, `peN_qNNN` namespace.

## Labs (interactive-labs skill)

| Lab | Status | Objectives |
|-----|--------|------------|
| Lab 1: SIEM triage | Seeded | sy701_4_4, sy701_4_9 |
| Lab 2: Phishing analysis | Seeded | sy701_2_2, sy701_2_4 |
| Lab 3: Firewall rule review | Seeded | sy701_3_2 |

## Artifact plan this sprint

- [x] Gap memo (this file)
- [x] `generate-security-plus-launch-content.mjs` → 56 sub-lessons + launch GIFT
- [x] `build-practice-exams-gift.mjs` → PE1, PE2, PE3
- [x] Lab 2–3 HTML + seed
- [x] Seed sub-lesson loop + PE2/PE3 + launch GIFT import
- [ ] Staging seed + verify (workflow + `verify-sec701-launch-scale.sh`; pending run)

## Citation block

### Source: CompTIA SY0-701 exam objectives
- URL: https://www.comptia.org/certifications/security
- Version: SY0-701
- Retrieved: 2026-06-18
- Relevant objectives: all sy701_*

### Source: NIST CSF 2.0
- URL: https://www.nist.gov/cyberframework
- Retrieved: 2026-06-18
- Relevant objectives: sy701_5_*, sy701_4_*

### Source: MITRE ATT&CK
- URL: https://attack.mitre.org/
- Retrieved: 2026-06-18
- Relevant objectives: sy701_2_*, sy701_4_*
