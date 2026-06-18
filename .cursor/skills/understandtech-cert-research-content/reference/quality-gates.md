# Quality Gates

Run this checklist before committing research-generated content.

## Safety (non-negotiable)

- [ ] No quiz correct answers in lesson HTML, course notes, or supplements
- [ ] No `UT{...}` flag values in lab HTML (learners derive from scenario data)
- [ ] No “answer is B” / “correct choice” phrasing in `_exam` sub-lessons — teach distractor *logic* only
- [ ] AI tutor prompts in lessons do not reference specific KC or PE question IDs with answers
- [ ] No API keys, credentials, or real PII in scenarios (use contoso.example, fictional hosts)

## Format compliance

- [ ] Lessons use `<div class="ut-lesson-content">` root
- [ ] GIFT objective tags match CSV shortnames (`sy701_X_Y`, `n10009_X_Y`, etc.)
- [ ] Practice exam tags use `peN_qNNN` only (not mixed with objective tags)
- [ ] GIFT syntax valid (4-option MCQ, one `=` correct answer)
- [ ] Lab HTML has no `<form>` — submission is via `mod_ctfflag` activity only

## Brand and accessibility

- [ ] Custom inline colors use palette: navy `#0B1F3A`, gold `#C9A227`, teal `#1A8A7D`
- [ ] Headings are hierarchical (`h3` lesson title, `h4` sections)
- [ ] Diagrams include text alternatives or descriptive captions where visual-only
- [ ] No reliance on color alone for critical distinctions

## Research traceability

- [ ] Gap memo exists for the work unit
- [ ] Each new domain batch cites ≥1 official blueprint source with retrieval date
- [ ] Framework references (NIST, MITRE, etc.) match current edition cited in memo
- [ ] Generated content does not contradict objective verbatim text in CSV

## Repo hygiene

- [ ] Files under correct `content/<track>/` paths
- [ ] No edits to `tmp/` or e2e artifacts committed
- [ ] Question counts documented in commit message or PR summary
- [ ] If seed script changed: idempotent re-run noted in test plan

## Automated checks (when available)

```bash
# GIFT / objective tag spot-check (SEC701)
rg '^::sy701_' content/security-plus/*.gift | wc -l

# Flag leakage scan (should return no matches in lessons)
rg 'UT\{[A-Za-z0-9_\-]+\}' content/security-plus/lessons content/security-plus/labs

# Lesson count vs target
ls content/security-plus/lessons/*.html | wc -l
```

## Staging verification (post-seed)

- [ ] `verify-cert-course-pages.sh` passes
- [ ] Knowledge Check quizzes populate per domain
- [ ] Practice exam has expected question count and time limit
- [ ] Lab activity loads; flag regex accepts format (test with known derived value on staging)
- [ ] Lab relevance documented; lab quality gates from `/understandtech-interactive-labs` passed
