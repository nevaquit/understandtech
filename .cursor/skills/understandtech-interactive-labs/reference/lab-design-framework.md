# Lab Design Framework

Scenario design, flag derivation, progressive hints, safety, and completion for understandtech.app labs.

## Design principles

1. **Job-relevant** — Lab mirrors a task a certified professional performs (Tier 1 triage, config review, troubleshooting)
2. **Bounded** — Completable in 15–45 minutes async; 3–7 clear tasks
3. **Derive, don't guess** — Flag comes from scenario data via stated rule (hash prefix, rule ID, computed subnet)
4. **Portfolio-ready** — Learner could describe findings in an interview or written report
5. **Safe** — Fictional orgs, synthetic artifacts, no offensive instructions

## Scenario structure template

```html
<div class="ut-lab-content">
<h3>Scenario: [TITLE]</h3>
<p>Role + context (1–2 sentences).</p>

<h4>Artifacts</h4>
<!-- Tables, logs, email headers, config snippets — all synthetic -->

<h4>Your tasks</h4>
<ol>
<li>Task with observable deliverable</li>
<li>...</li>
<li>Submit flag: [FORMAT RULE — not the value]</li>
</ol>

<details class="ut-lab-hint">
<summary>Hint 1 — methodology</summary>
<p>Teach approach without revealing answer.</p>
</details>

<p><em>Do not share flags in forums. The AI tutor will not validate flags.</em></p>
</div>
```

## Flag derivation (never store values)

| Pattern | Example rule | Regex |
|---------|--------------|-------|
| Hash prefix | First 8 hex of SHA-256 in scenario | `UT\{[A-Fa-f0-9]{8}\}` |
| Campaign/token ID | `CAMP-` + year from email metadata | `UT\{CAMP-[0-9]{4}\}` |
| Rule set ID | Sum or ID from firewall table column | `UT\{RULE-[0-9]{3}\}` |
| Subnet answer | Network address from given hosts | `UT\{10\.[0-9]+\.[0-9]+\.0\}` |
| Component SKU | Model from spec table | `UT\{[A-Z0-9\-]+\}` |

**Rules:**

- Scenario HTML contains **inputs** to the derivation rule
- Seed PHP stores **regex only** in `expected_flag_regex`
- Staging test uses derived value via env var (`E2E_CTFFLAG_VALID_FLAG`) — never commit
- `flag_validator::matches()` uses PCRE — test regex edge cases (case, length)

## Progressive hints without answer leakage

| Tier | Content | Allowed | Forbidden |
|------|---------|---------|-----------|
| Hint 1 | Methodology ("Start with the process column") | ✅ | Naming the flag |
| Hint 2 | Framework reference ("MITRE initial access") | ✅ | Exact technique ID as answer |
| Hint 3 | Partial structure ("Flag uses hex from hash") | ✅ | First 8 chars literal |
| Hint 4 | — | ❌ Too close to answer | Full flag |

Implement as `<details>` elements or numbered "Need a hint?" links in intro HTML. Do not add server-side hint API that reveals validation state.

## Interactivity levels

| Level | Description | Phase | Example |
|-------|-------------|-------|---------|
| L0 | Static scenario + ctfflag | 1 | Lab 1 SIEM triage |
| L1 | Client-side widget (calculator, sorter) | 1 | Subnet calculator |
| L2 | H5P branching | Optional | Phishing path |
| L3 | LTI live environment | 2+ | Sentinel workspace |
| L4 | Cohort BBB walkthrough | 2+ | Instructor-led |

Choose minimum level that satisfies objective — avoid L3 when L0 teaches the skill.

## Safety checklist

- [ ] Organization: `contoso.example`, `fabrikam.example` — not real companies
- [ ] IPs: RFC 5737 TEST-NET or documentation ranges
- [ ] Hashes/IOCs: obviously synthetic or generated for exercise
- [ ] No malware binaries — describe behavior in prose/logs only
- [ ] No instructions to scan/attack external hosts
- [ ] No real employee names — use generic personas
- [ ] Embedded JS: no answer keys, no `fetch` to exfiltrate flags
- [ ] AI tutor disclaimer in every lab intro

## Completion and grading

| Setting | Value | Why |
|---------|-------|-----|
| `completion_required` | 1 | Portfolio + progress tracking |
| `xp_award` | 100 (default) | Large tier per gamification design |
| Gradebook max | 100 | Shows on transcript export |
| Resubmission | Allowed until success | `ctfflag_submissions` logs attempts |

Optional Phase 2: `mod_assign` for written reflection (incident report) graded by AI + instructor — separate from flag activity.

## Portfolio linkage

`block_portfolio` aggregates evidence. Ensure:

1. Lab activity has completion enabled on course module
2. Grade item visible in gradebook
3. Lab section named consistently (`Hands-on Labs`)
4. Future: export reflection PDF from assign submission

## Objective alignment

Every lab maps to 1–3 objectives in gap memo:

```markdown
| Lab | Primary objective | Secondary |
|-----|-------------------|-----------|
| SIEM triage | sy701_4_9 (investigation data sources) | sy701_4_4 (alerting/monitoring) |
```

Tag in course section description or certmaster metadata when APIs support it.

## Anti-patterns in lab design

| Bad | Good |
|-----|------|
| "The flag is UT{abc123}" in HTML | "Submit first 8 hex chars of hash as UT{...}" |
| Trivia question ("Who invented AES?") | Analyze provided log line for IOC type |
| 2-hour open-ended scope | 3–7 numbered tasks with clear done state |
| Copy vendor lab verbatim | Original scenario inspired by framework |
| Hidden flag in HTML comment | Derivation from visible artifact data |

## Review rubric (before seed)

| Criterion | Pass |
|-----------|------|
| Realistic role | Learner has job title + constraint |
| Artifact quality | Logs/configs internally consistent |
| Derivation clarity | Student can explain how they got the flag |
| Hint safety | No tier reveals literal flag |
| Regex match | Staging test with derived value succeeds |
| Tutor safety | Disclaimer present; no answer leakage |
