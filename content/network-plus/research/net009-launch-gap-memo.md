# Gap memo: NET009 launch-scale content

Date: 2026-06-18  
Researcher: understandtech content pipeline  
Blueprint: CompTIA Network+ N10-009 (5 domains, 26 objectives)  
Sources: [CompTIA Network+](https://www.comptia.org/certifications/network) | Retrieved: 2026-06-18 | `content/network-plus/n10-009-objectives.csv`

## Targets (white paper Phase 2 / launch-targets.md)

| Asset | Current | Milestone B (launch) | Delta |
|-------|---------|----------------------|-------|
| Lesson pages | 26 core | 75 (core + scenario + exam) | +49 sub-lessons |
| Question bank | ~160 | 400 | +240 |
| Practice exams | 0 | 3 × 90 Q | +3 exams |
| Labs | 0 | 3 | +3 labs |

## Non-duplication strategy

| Layer | Purpose | Must NOT |
|-------|---------|----------|
| Core (`n10009_X_Y.html`) | Concept teaching | Repeat in sub-lessons |
| Scenario (`*_scenario.html`) | Enterprise network case narrative | Copy core bullet lists |
| Exam focus (`*_exam.html`) | Distractor logic, traps | Reveal KC answers |
| GIFT launch pool | Additional MCQs | Duplicate existing stems |
| Practice exams | Mixed blueprint assessment | Reuse KC slot-for-slot |

## Question bank plan

- Keep `n10-009-quiz.gift` (130) + `n10-009-quiz-extra.gift` (30) unchanged
- Add `n10-009-quiz-launch.gift` (~240 items, ~9 per objective) via `generate-network-plus-launch-content.mjs`
- Target post-import: **~400** unique objective-tagged items

## Practice exams (N10-009 blueprint weights on 90 Q)

| Domain | Weight | PE slots |
|--------|--------|----------|
| 1 Networking Concepts | 23% | 21 |
| 2 Network Implementation | 20% | 18 |
| 3 Network Operations | 19% | 17 |
| 4 Network Security | 14% | 13 |
| 5 Network Troubleshooting | 24% | 21 |

Files: `practice-exam-{1,2,3}.gift` via `build-network-plus-practice-exams-gift.mjs`, `peN_qNNN` namespace.

## Labs (interactive-labs skill)

| Lab | Objectives |
|-----|------------|
| Lab 1: IPv4 subnet planning | n10009_1_7 |
| Lab 2: VLAN troubleshooting | n10009_2_2 |
| Lab 3: ACL rule review | n10009_4_3 |

## Artifact plan this sprint

- [x] Gap memo (this file)
- [x] `generate-network-plus-launch-content.mjs` → 50 sub-lessons + launch GIFT
- [x] `build-network-plus-practice-exams-gift.mjs` → PE1–PE3
- [x] Lab 1–3 HTML + seed
- [x] Seed sub-lesson loop + PE + launch GIFT import
- [x] Staging seed + verify (`verify-net009-launch-scale.sh`) — [run 27785118420](https://github.com/nevaquit/understandtech/actions/runs/27785118420): course_id=5, pages=75, quizzes=8, ctfflags=3, PE slots=90×3

## Citation block

### Source: CompTIA N10-009 exam objectives
- URL: https://www.comptia.org/certifications/network
- Version: N10-009
- Retrieved: 2026-06-18
- Relevant objectives: all n10009_*
