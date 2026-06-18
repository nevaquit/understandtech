# Practice Exams and Labs

Patterns to add to seed scripts — **not yet in production seeds** (white paper gap).

## Full-length practice exams

### Design

| Setting | Value |
|---------|-------|
| Questions | 90 (SY0-701 exam length) |
| Time limit | 90 minutes |
| Attempts | 1–3 (configurable) |
| Shuffle | Questions yes; answers yes |
| Behaviour | `certmasterconfidence` |
| Grade | 100 point scale, pass 750/900 equivalent → use 83% or custom |

### Content files

Create three banks independent of objective-tagged KC pool:

```
content/security-plus/practice-exam-1.gift   # 90 × ::pe1_qNNN::
content/security-plus/practice-exam-2.gift   # 90 × ::pe2_qNNN::
content/security-plus/practice-exam-3.gift   # 90 × ::pe3_qNNN::
```

Question distribution should approximate blueprint weights (e.g. Domain 4 ~28% of 90 ≈ 25 Q).

### Seed PHP sketch

Add after domain Knowledge Check loop in `seed-security-plus-course.php`:

```php
function security_plus_sync_practice_exam(
    stdClass $course,
    int $sectionnum,
    string $quizname,
    array $questionids,
    int $timelimitsecs = 5400
): void {
    // Mirror security_plus_sync_quiz() but:
    // - timelimit = 5400 (90 min)
    // - preferredbehaviour = certmasterconfidence
    // - sumgrades / grade settings for scored attempt
    // - do NOT call ut_curate_knowledge_check_questions (use all PE slots)
}
```

Add course sections 6–8: `Practice Exams` or one section per exam.

**Do not** run `cleanup-cert-knowledge-checks.php` rebuild on practice exam quizzes if names do not match `Knowledge Check` pattern (current cleanup targets `*Knowledge Check*` only — verify before extending).

## Labs (`mod_ctfflag`)

### Design (Phase 1 — Security+)

| Lab | Domain | Scenario | Flag format |
|-----|--------|----------|-------------|
| Lab 1 | 4 — SecOps | Parse SIEM alert, identify IOC | `UT{IOC_SHA256_PREFIX}` |
| Lab 2 | 2 — Threats | Phishing email analysis | `UT{PHISH_CAMPAIGN_ID}` |
| Lab 3 | 3 — Architecture | Firewall rule review | `UT{RULE_SET_ID}` |

Flags are **validated by regex** only — never stored in DB or lesson HTML.

### Instance fields (`mod_ctfflag`)

| Field | Example |
|-------|---------|
| name | `Lab 1: SIEM alert triage` |
| intro | Scenario HTML (no flag) |
| expected_flag_regex | `UT\{[A-F0-9]{8}\}` |
| grade | 100 |
| completion_required | 1 |

### Seed PHP sketch

```php
function security_plus_upsert_ctfflag(
    stdClass $course,
    int $sectionnum,
    string $name,
    string $intro,
    string $regex
): void {
    global $DB;
    // Find existing cm by name + module ctfflag
    // ctfflag_add_instance() / update via mod_ctfflag APIs
    // add_moduleinfo() if new
}
```

Place labs in **section 5** tail or new **section 9: Hands-on Labs**.

### E2E

`tests/e2e/lab-flag.spec.ts` expects `E2E_CTFFLAG_PATH` — set after seeding lab URL.

### Future (Phase 2+)

- LTI 1.3 lab gateway (`local_integrations` settings stub)
- Microsoft Sentinel tenant labs
- GNS3/EVE-NG for NET009

## Portfolio linkage

`block_portfolio` aggregates completions — ensure labs use completion tracking and appear in course gradebook for portfolio export.
