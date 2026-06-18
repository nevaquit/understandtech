# Implementation Pipeline

End-to-end: **Research → design → content → seed → verify** for understandtech.app labs.

## Pipeline overview

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. RESEARCH (cert-research-content)                             │
│    Blueprint + sources + gap memo + lab relevance per domain    │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. DESIGN (interactive-labs — lab-design-framework.md)          │
│    Scenario, tasks, modality, flag derivation rule, hints       │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. CONTENT                                                      │
│    content/<track>/labs/<slug>.html                             │
│    Optional: interactive mod_page assets                        │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. SEED                                                         │
│    scripts/seed-<track>-course.php → upsert_ctfflag             │
└────────────────────────────┬────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. VERIFY                                                       │
│    Quality gates + staging seed + E2E + portfolio check         │
└─────────────────────────────────────────────────────────────────┘
```

## Stage 1 — Research (mandatory gate)

**Skill:** `/understandtech-cert-research-content`

**Outputs required before lab work:**

1. Gap memo with citations ([research-sources-labs.md](research-sources-labs.md))
2. Lab relevance table per domain ([lab-types-by-track.md](lab-types-by-track.md))
3. Artifact plan row per lab:

```markdown
| Lab slug | Objectives | Modality | Phase | Flag derivation rule |
|----------|------------|----------|-------|----------------------|
| lab-1-siem-triage | sy701_4_4, sy701_4_9 | ctfflag | 1 | 8 hex of SHA-256 |
```

**Stop condition:** If verdict is `lesson/quiz sufficient` — do not proceed to Stage 2 for that objective.

## Stage 2 — Design

**Skill:** `/understandtech-interactive-labs` + [lab-design-framework.md](lab-design-framework.md)

Deliverables (in chat or work note):

- Scenario narrative (role, artifacts, constraints)
- Task list (3–7 items)
- Flag derivation rule + proposed regex (not literal flag)
- Hint tiers (3 max before answer territory)
- Modality selection from [moodle-lab-patterns.md](moodle-lab-patterns.md)

## Stage 3 — Content authoring

**Paths:**

```
content/security-plus/labs/lab-1-siem-triage.html
content/security-plus/labs/lab-2-phishing-analysis.html   # planned
content/security-plus/labs/lab-3-firewall-review.html       # planned
content/network-plus/labs/                                   # as needed
content/a-plus/labs/                                         # as needed
```

**Format rules:**

- Root: `<div class="ut-lab-content">`
- No `<form>` elements
- No literal `UT{...}` answer values
- Brand palette for custom styles: navy `#0B1F3A`, gold `#C9A227`, teal `#1A8A7D`
- Tutor disclaimer footer

**Optional interactive assets:**

- Co-locate JS in lab HTML or `content/<track>/labs/assets/<slug>.js`
- Keep assets secret-free

## Stage 4 — Seed

**Skill:** `/understandtech-cert-content` for orchestration; this skill for lab-specific patterns.

### SEC701 (existing pattern)

File: `scripts/seed-security-plus-course.php`

```php
$labintro = security_plus_load_lab_intro(
    $repopath,
    'lab-1-siem-triage',
    '<p>Fallback intro</p>'
);
security_plus_upsert_ctfflag(
    $course,
    7,                              // section number — verify course layout
    'Lab 1: SIEM alert triage',
    $labintro,
    'UT\\{[A-Fa-f0-9]{8}\\}',
    100
);
```

### Adding Labs 2–3

1. Add HTML files to `content/security-plus/labs/`
2. Add `security_plus_upsert_ctfflag()` calls in same section
3. Use distinct regex per lab flag format
4. Re-run seed idempotently on staging

### NET009 / APLUS (when ready)

Copy `security_plus_upsert_ctfflag` pattern to `seed-network-plus-course.php` / `seed-comptia-a-plus-course.php` with track-specific `load_lab_intro` helper.

### Plugin sync before seed

```bash
# Local dev
./scripts/sync-plugins-local.sh   # or .ps1 on Windows
```

Ensure `mod_ctfflag` exists on target VM.

## Stage 5 — Verify

### Quality gates

1. [cert-research quality-gates.md](../../understandtech-cert-research-content/reference/quality-gates.md)
2. Lab extensions in `SKILL.md` Quality gates section

### Automated scans

```bash
# Flag leakage (should return no literal answers)
rg 'UT\{[A-Za-z0-9_\-]+\}' content/security-plus/labs

# Lab file count
ls content/security-plus/labs/*.html | wc -l
```

### Staging seed

```bash
# Per cert-content workflow
php scripts/seed-security-plus-course.php
# or CI workflow seed-security-plus-course.yml
```

### Page verification

```bash
./scripts/verify-cert-course-pages.sh
```

Confirm lab cm loads, intro renders, flag form visible.

### E2E

```bash
E2E_CTFFLAG_PATH=/mod/ctfflag/view.php?id=CMID \
E2E_CTFFLAG_VALID_FLAG=UT{staging_derived} \
npx playwright test tests/e2e/lab-flag.spec.ts
```

Set `E2E_CTFFLAG_PATH` in CI secrets after seed documents cm id.

### Manual smoke

- [ ] Invalid flag → error notification
- [ ] Valid derived flag → success + completion + XP
- [ ] AI tutor refuses flag validation
- [ ] Portfolio block lists lab (when configured)

## Handoff to cert-content

After lab verify:

- Update launch-target counts in PR summary
- Continue cert-content workflow steps 6–8 (commit policy per user, production seed)

## Phase 2 branch (document only)

When playbook Phase 2 lab gateway prompt activates:

1. Extend `local_integrations` LTI registration
2. Add `mod_lti` instances per external lab
3. Keep ctfflag as optional checkpoint or replace with LTI grade return
4. Update this pipeline with gateway-specific verify steps
