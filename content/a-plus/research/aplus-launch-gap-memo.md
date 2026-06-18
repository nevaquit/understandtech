# Gap memo: APLUS launch-scale content

Date: 2026-06-18  
Researcher: understandtech content pipeline  
Blueprint: CompTIA A+ 220-1101 / 220-1102 (9 domains, 57 objectives)  
Sources: [CompTIA A+](https://www.comptia.org/certifications/a) | Retrieved: 2026-06-18 | `content/a-plus/aplus-objectives.csv`

## Targets (white paper Phase 2 / launch-targets.md)

| Asset | Current | Milestone B (launch) | Delta |
|-------|---------|----------------------|-------|
| Lesson pages | 57 core | 171 (core + scenario + exam) | +114 sub-lessons |
| Question bank | 912 | ≥400 (ready/tagged) | **no new launch GIFT** — existing bank exceeds target |
| Practice exams | 0 | 3 × 90 Q | +3 exams |
| Labs | 0 | 3 | +3 labs |

## Non-duplication strategy

| Layer | Purpose | Must NOT |
|-------|---------|----------|
| Core (`ap1101_*.html`, `ap1102_*.html`) | Concept teaching | Repeat in sub-lessons |
| Scenario (`*_scenario.html`) | Help-desk / field-service case narrative | Copy core bullet lists |
| Exam focus (`*_exam.html`) | Distractor logic, PBQ traps | Reveal KC answers |
| `aplus-quiz.gift` (912 Q) | Domain KCs + PE pool source | Duplicate stems in new GIFT |
| Practice exams | Mixed Core 1 + Core 2 blueprint assessment | Reuse KC slot-for-slot |

## Question bank plan

- Keep `aplus-quiz.gift` (912 items) unchanged — already exceeds 400-question launch target
- **Do not** add `aplus-quiz-launch.gift`; PE builder selects from existing objective-tagged pool
- Post-import verify: ≥400 `ap110*` tagged questions in course category (ready status)

## Practice exams (combined Core 1 + Core 2 weights on 90 Q)

Domain weights are normalized across both exams (200% total):

| Domain | Blueprint weight | PE slots |
|--------|------------------|----------|
| 1 Mobile Devices (Core 1) | 13% | 6 |
| 2 Networking (Core 1) | 23% | 10 |
| 3 Hardware (Core 1) | 25% | 11 |
| 4 Virtualization and Cloud (Core 1) | 11% | 5 |
| 5 HW/Net Troubleshooting (Core 1) | 28% | 13 |
| 6 Operating Systems (Core 2) | 31% | 14 |
| 7 Security (Core 2) | 25% | 11 |
| 8 Software Troubleshooting (Core 2) | 22% | 10 |
| 9 Operational Procedures (Core 2) | 22% | 10 |

Files: `practice-exam-{1,2,3}.gift` via `build-aplus-practice-exams-gift.mjs`, `peN_qNNN` namespace.  
PE bank idnumbers: `aplus-pe1-bank`, `aplus-pe2-bank`, `aplus-pe3-bank`.

## Labs (interactive-labs skill)

| Lab | Objectives | Theme |
|-----|------------|-------|
| Lab 1: RAM and storage upgrade planning | ap1101_3_3, ap1101_3_4 | Hardware |
| Lab 2: Windows boot troubleshooting | ap1102_1_5 | OS |
| Lab 3: Network connectivity diagnosis | ap1101_5_6 | HW/Net troubleshooting |

## Artifact plan this sprint

- [x] Gap memo (this file)
- [x] `generate-aplus-launch-content.mjs` → 114 sub-lessons (no launch GIFT)
- [x] `build-aplus-practice-exams-gift.mjs` → PE1–PE3 from existing bank
- [x] Lab 1–3 HTML + seed
- [x] Seed sub-lesson loop + PE + labs (sections 10–11)
- [x] Production seed + verify — [run 27786541717](https://github.com/nevaquit/understandtech/actions/runs/27786541717): course_id resolved by shortname, pages=171, quizzes=12, ctfflags=3, PE slots=90×3

## Citation block

### Source: CompTIA A+ exam objectives
- URL: https://www.comptia.org/certifications/a
- Version: 220-1101 / 220-1102
- Retrieved: 2026-06-18
- Relevant objectives: all ap1101_*, ap1102_*
