# Launch Targets by Track

Aligned to `docs/white-paper.md` §5 Phase 1–2 and current repo inventory (2026-06).

## Phase 1 — Security+ private beta (SEC701)

| Metric | White paper | Current | Milestone A (beta) | Milestone B (launch) |
|--------|-------------|---------|--------------------|-----------------------|
| Lesson pages | 80–100 | 28 | 56 (2 per objective) | 84–100 (3+ per objective) |
| Question bank | ~400 | ~84 | 200 | 400 |
| Domain Knowledge Checks | 5 (1 per domain) | 5 | 5 | 5 |
| Full-length practice exams | 3 × ~90 Q | 0 | 1 | 3 |
| Labs (`mod_ctfflag`) | 3 | 0 | 1 | 3 |
| Stream videos in lessons | Core IP on Stream | Signing only | 10 lessons | All core lessons |

**Course:** `SEC701` · **Cert shortname:** `security_plus_sy0_701` · **Objective prefix:** `sy701_`

**Domains (5):** general_concepts, threats_vulns, security_architecture, security_operations, program_management

## Phase 2 — Network+ and A+ (NET009, APLUS)

| Metric | White paper | Current (approx) |
|--------|-------------|------------------|
| SEC701 | (maintain) | baseline above |
| NET009 lessons | 80–100 | 26 |
| NET009 questions | ~400 | ~160 |
| APLUS lessons | 80–100 per core | 57 combined |
| APLUS questions | ~400 per core | ~912 (flashcard-heavy) |

**Courses:** `NET009` (`network_plus_n10_009`), `APLUS` (`comptia_a_plus`)

**Objective prefixes:** `n10009_`, `ap1101_`, `ap1102_`

## Gap closure priority (recommended order)

1. SEC701 question bank → 200 → 400 (lowest risk, highest readiness impact)
2. SEC701 sub-lessons (_scenario, _exam) → 56 → 84 pages
3. Practice Exam 1 (90 Q timed)
4. Lab 1–3 seed + portfolio linkage
5. Stream embeds in top-traffic lessons
6. NET009 / APLUS parity after SEC701 launch gate

## Counting commands

```bash
# Lessons
ls content/security-plus/lessons/*.html | wc -l
ls content/network-plus/lessons/*.html | wc -l
ls content/a-plus/lessons/*.html | wc -l

# Objectives in CSV (source of truth for certmaster)
tail -n +2 content/security-plus/sy0-701-objectives.csv | wc -l

# GIFT questions (objective-tagged)
rg -c '^::' content/security-plus/sy0-701-quiz.gift content/security-plus/sy0-701-quiz-extra.gift
```
