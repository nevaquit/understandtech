---
name: understandtech-interactive-labs
description: >-
  Designs and authors rich interactive hands-on labs for understandtech.app
  certification courses (SEC701, NET009, APLUS) in Moodle 4.5 — mod_ctfflag
  scenarios, embedded interactive HTML, H5P, LTI gateways, and portfolio-linked
  completion. Use when creating, expanding, or researching labs per domain,
  deciding lab vs lesson/quiz, writing lab HTML, seeding ctfflag activities,
  or closing white-paper Phase 1 lab gaps. Requires
  /understandtech-cert-research-content completed first.
paths:
  - "content/**/labs/**"
  - "moodle-plugins/mod_ctfflag/**"
  - "scripts/seed-*-course.php"
  - "tests/e2e/lab-flag.spec.ts"
---

# Interactive Labs for Certification Courses

Designs **research-backed, safety-compliant** hands-on labs that produce portfolio artifacts and integrate with Moodle completion, gradebook, XP, and exam readiness.

## Prerequisite gate — MUST run first

**Do not author lab HTML, seed `mod_ctfflag`, or extend lab interactivity until `/understandtech-cert-research-content` steps 1–3 are complete:**

1. **Scope** — track, domain/objective, gap vs [launch-targets.md](../understandtech-cert-content/reference/launch-targets.md)
2. **Research phase** — blueprint, authoritative sources, **gap memo with citations**
3. **Artifact plan** — includes **lab relevance decision per objective/domain** (see decision matrix below)

The research memo must document for each proposed lab:

- Objective(s) mapped (`sy701_X_Y`, `n10009_X_Y`, `ap1101_X_Y`)
- **Lab relevance verdict** — `hands-on justified` | `lesson/quiz sufficient` | `defer Phase 2`
- Modality choice (`ctfflag`, `page+JS`, `LTI`, etc.)
- Flag derivation method (if applicable) — **never the flag value itself**

If the gap memo lacks lab relevance decisions, **stop** and complete research — do not invoke this skill for net-new labs.

**Always load with:** `/understandtech-cert-research-content` (upstream gate), `/understandtech-cert-content` (seed + formats), `/understandtech-platform` (architecture + AI guardrails).

## When to create a lab

Use the decision matrix before designing any lab:

| Signal | Hands-on lab justified | Lesson + KC sufficient |
|--------|------------------------|-------------------------|
| Objective verb | "Given a scenario, **apply/modify/implement/troubleshoot**" | "Compare and contrast", "Summarize", "Explain" |
| Skill type | Procedural analysis, config review, artifact extraction | Conceptual recall, taxonomy |
| Artifact output | Investigation report, config snippet, derived IOC | None required |
| Risk if cognitive-only | Student cannot demonstrate job skill | MCQ adequately tests understanding |
| Platform phase | Phase 1: scenario + flag; Phase 2+: live environment | N/A |

**Default Phase 1 posture:** 3 labs per SEC701 launch track; 1–3 per NET009/APLUS domain where matrix says justified. Full per-domain mapping: [lab-types-by-track.md](reference/lab-types-by-track.md).

## Moodle lab modalities (Moodle 4.5 LTS)

| Modality | Phase | Use when | Repo touchpoints |
|----------|-------|----------|------------------|
| **`mod_ctfflag`** | 1 (primary) | CTF-style derived flags, XP, completion, gradebook | `moodle-plugins/mod_ctfflag/`, `content/<track>/labs/*.html` |
| **`mod_page` + embedded HTML/JS** | 1 | Simulators, drag-drop triage, subnet calculators — **no secrets in JS** | Lab intro or sibling `mod_page` activity |
| **H5P** (`filter_h5p`, `mod_h5pactivity`) | Optional/future | Branching scenarios, interactive video — if enabled on VM | Document only; verify admin before authoring |
| **LTI 1.3 lab gateway** | 2+ | External Sentinel, GNS3, browser VMs | `local_integrations` LTI issuer stub |
| **Virtual lab gateways** | 2+ | Azure Sentinel tenant, GNS3/EVE-NG (NET009) | White paper §2.5; not in Phase 1 seeds |
| **BigBlueButton** | 2+ | Cohort live instructor-led labs | `local_integrations` BBB URL setting |

Patterns and Moodle APIs: [moodle-lab-patterns.md](reference/moodle-lab-patterns.md).

## Rich interactivity patterns

| Track | Pattern examples | Flag / completion |
|-------|------------------|-------------------|
| **SEC701** | SIEM log triage, phishing header analysis, firewall rule review | `UT{DERIVED_TOKEN}` via regex |
| **NET009** | PCAP interpretation, subnetting simulator, VLAN config review | `UT{...}` or config checksum |
| **APLUS** | Hardware ID from specs, OS troubleshooting decision trees | `UT{...}` from scenario data |

**Progressive hints:** embed in lab intro HTML as collapsible `<details>` sections or numbered hint tiers. Hints teach methodology — **never** reveal flag values, regex matches, or quiz answers.

**Completion + portfolio:** enable `completion_required` on `mod_ctfflag`; ensure course module completion tracking on. `block_portfolio` aggregates lab completions from gradebook/completion — verify after seed.

## Safety constraints (non-negotiable)

From `.cursorrules` and white paper §3.1:

- **Never** store flag values in repo HTML, JS, comments, or seed PHP — only `expected_flag_regex`
- **Never** put real malware samples or offensive tooling instructions in lab content
- **AI tutor** must not validate flags or reveal answers — scenario HTML states this explicitly
- **Regex-only** validation in `mod_ctfflag` (`flag_validator::matches()`)
- Use fictional orgs (`contoso.example`), synthetic IOCs, sanitized log excerpts
- Embedded JS must not contain answer keys — derive flags from visible scenario data only

## Implementation workflow

```
Lab task:
- [ ] 0. Research gate passed (/understandtech-cert-research-content) — lab relevance in gap memo
- [ ] 1. Design lab (lab-design-framework.md) — scenario, tasks, flag derivation, modality
- [ ] 2. Author content/<track>/labs/<slug>.html (no flag values)
- [ ] 3. Optional: interactive mod_page or embedded widget (sandboxed, no secrets)
- [ ] 4. Extend seed-*-course.php — upsert_ctfflag / mod_page
- [ ] 5. Quality gates (below + cert-research quality-gates.md)
- [ ] 6. Seed staging → verify VM → E2E (lab-flag.spec.ts)
- [ ] 7. Hand off to /understandtech-cert-content for launch-target counts
```

Full pipeline: [implementation-pipeline.md](reference/implementation-pipeline.md).

### Content paths

```
content/security-plus/labs/lab-1-siem-triage.html   # SEC701
content/network-plus/labs/                          # NET009 (as added)
content/a-plus/labs/                                # APLUS (as added)
```

### Seed pattern (SEC701)

```php
security_plus_upsert_ctfflag(
    $course,
    $sectionnum,
    'Lab 1: SIEM alert triage',
    security_plus_load_lab_intro($repopath, 'lab-1-siem-triage', $fallback),
    'UT\\{[A-Fa-f0-9]{8}\\}',  // regex only — never literal flag
    100  // xp_award
);
```

### E2E verification

```bash
E2E_CTFFLAG_PATH=/mod/ctfflag/view.php?id=N \
E2E_CTFFLAG_VALID_FLAG=UT{derived_on_staging} \
npx playwright test tests/e2e/lab-flag.spec.ts
```

## Quality gates (lab extensions)

Run cert-research [quality-gates.md](../understandtech-cert-research-content/reference/quality-gates.md) **plus**:

- [ ] Lab relevance documented in gap memo with objective mapping
- [ ] No `UT{...}` literal flags in `content/**/labs/` (learners derive from scenario)
- [ ] Lab HTML roots in `<div class="ut-lab-content">`; no `<form>` (submission via `mod_ctfflag`)
- [ ] `expected_flag_regex` tested on staging with a derived value (not committed)
- [ ] Hints do not narrow to single answer without learner work
- [ ] `completion_required=1`, gradebook item exists, XP fires on success
- [ ] Portfolio block shows lab completion after seed (when in scope)
- [ ] Flag leakage scan: `rg 'UT\{[A-Za-z0-9_\-]+\}' content/<track>/labs` → no literal answers

## Anti-patterns

| Do not | Do instead |
|--------|------------|
| Author labs without research gate | Complete cert-research steps 1–3 with lab relevance |
| Store flags in HTML/JS/seed | Regex in ctfflag instance; learner derives flag |
| Replace labs with long lessons | Use decision matrix; labs for procedural objectives |
| Build LTI/Sentinel in Phase 1 | Document Phase 2 plan; ship ctfflag + scenario HTML |
| Let AI tutor confirm flags | State in intro; Worker system prompt enforces guardrail |
| Duplicate cert-content seed docs | Link to cert-content + this skill's pipeline |

## Reference files

- [moodle-lab-patterns.md](reference/moodle-lab-patterns.md) — Moodle 4.5 lab build patterns
- [lab-types-by-track.md](reference/lab-types-by-track.md) — SEC701, NET009, APLUS domain mapping
- [research-sources-labs.md](reference/research-sources-labs.md) — MITRE, sandboxes, H5P, ctfflag, LTI
- [lab-design-framework.md](reference/lab-design-framework.md) — Scenario design, flags, safety, hints
- [implementation-pipeline.md](reference/implementation-pipeline.md) — Research → design → seed → verify
- [prompt-templates-labs.md](reference/prompt-templates-labs.md) — Research and authoring prompts

## Skill stack

| Skill | When |
|-------|------|
| `/understandtech-cert-research-content` | **First** — gap memo + lab relevance |
| `/understandtech-interactive-labs` | Lab design, HTML, interactivity, seed patterns |
| `/understandtech-cert-content` | Formats, launch targets, seed orchestration |
| `/understandtech-platform` | Architecture, AI tutor, Phase gates |
| `/moodle-development` | mod_ctfflag PHP changes |
