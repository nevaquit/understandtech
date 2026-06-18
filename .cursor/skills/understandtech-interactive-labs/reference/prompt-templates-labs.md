# Prompt Templates for Labs

Copy-paste prompts for lab research and authoring. Replace `[BRACKET]` placeholders.

Use **after** cert-research domain prompt when lab relevance is uncertain or confirmed.

## 1. Lab relevance assessment (per domain)

```markdown
Assess hands-on lab relevance for [TRACK] Domain [N]: [DOMAIN NAME].

Inputs:
- Objectives CSV: content/[TRACK]/*-objectives.csv (domain rows only)
- Launch targets: .cursor/skills/understandtech-cert-content/reference/launch-targets.md
- Lab types guide: .cursor/skills/understandtech-interactive-labs/reference/lab-types-by-track.md

For each objective in domain [N]:
1. Quote objective verbatim
2. Classify verb: procedural ("Given a scenario…") vs conceptual
3. Verdict: hands-on justified | lesson/quiz sufficient | defer Phase 2
4. If justified: propose lab pattern (1 sentence)

Output table + recommended Phase 1 lab count (0–3 for domain).

Do not write lab HTML. Research and decisions only.
```

## 2. Single lab research deep dive

```markdown
Research lab design for [TRACK] — [LAB TITLE].

Objectives: [sy701_X_Y, ...]
Modality: mod_ctfflag (Phase 1)

Requirements:
1. Cite 3+ authoritative sources (blueprint, MITRE/NIST/RFC/vendor as applicable)
2. Define realistic fictional scenario (org, role, artifacts)
3. List synthetic artifacts needed (log lines, email headers, config rows)
4. Specify flag derivation RULE (not the flag value)
5. Propose expected_flag_regex pattern
6. Draft 3 progressive hints (methodology only — no answer leakage)
7. Map to white paper portfolio output type

Safety: no real malware, no offensive tooling, contoso.example, synthetic IOCs.

Output gap memo section only — no HTML yet.
```

## 3. Lab HTML generation

```markdown
Generate lab intro HTML for content/[TRACK]/labs/[slug].html from the research memo below.

Format:
- Root: <div class="ut-lab-content">
- h3 scenario title, h4 sections for Artifacts / Your tasks
- Include synthetic artifacts as <ul>, <table>, or <pre> (sanitized)
- Final task: flag submission RULE (format only — never literal UT{answer})
- 2–3 <details class="ut-lab-hint"> progressive hints
- Footer: AI tutor will not validate flags; no forum sharing

Constraints:
- No <form> tags
- No literal UT{...} flag values anywhere
- Brand palette if inline styles needed: #0B1F3A, #C9A227, #1A8A7D
- Original content — do not copy vendor labs

[PASTE RESEARCH MEMO]
```

## 4. Interactive widget spec (mod_page or embedded JS)

```markdown
Design client-side interactivity for [LAB TITLE] — [TRACK].

Purpose: [subnet calculator | log sorter | troubleshooting tree]
Modality: embedded in lab HTML OR sibling mod_page

Deliver:
1. User interaction flow (steps)
2. UI wireframe in markdown
3. Vanilla JS pseudocode (no answer keys in code)
4. How derived result connects to ctfflag flag rule
5. Accessibility notes (keyboard, screen reader labels)

Constraints: no fetch to external APIs; no secrets in JS source.
```

## 5. Seed script extension prompt

```markdown
Extend scripts/seed-[track]-course.php to seed lab: [LAB TITLE].

Inputs:
- Lab slug: [slug] → content/[TRACK]/labs/[slug].html
- Section number: [N]
- Activity name: [display name]
- expected_flag_regex: [PCRE escaped for PHP string]
- xp_award: 100

Follow existing security_plus_upsert_ctfflag() pattern:
- Idempotent find-by-name
- security_plus_load_lab_intro() or track equivalent
- Echo ctfflag_created|updated|unchanged

Do not hardcode flag values. Regex only.
```

## 6. Lab quality review

```markdown
Review lab artifact for safety and quality gates:

Files:
- content/[TRACK]/labs/[slug].html
- Seed regex: [pattern]

Checklist:
1. Flag leakage scan — any literal UT{...} answers?
2. Hint tiers — do any reveal the answer?
3. Artifact consistency — do logs/configs contradict?
4. Objective alignment — do tasks map to [objectives]?
5. AI tutor safety — disclaimer present?
6. Regex — does staging derived value match rule?

Output: PASS / FAIL with specific line-level fixes.
```

## 7. Phase 2 LTI lab planning (document only)

```markdown
Plan Phase 2 external lab for [TRACK] — [LAB TITLE].

Environment: [Azure Sentinel | GNS3 | browser VM]
Integration: LTI 1.3 via local_integrations

Deliver:
1. Tool provider requirements
2. Learner provisioning flow
3. Grade return mapping to Moodle gradebook
4. Relationship to mod_ctfflag (keep/replace/combine)
5. Cost and ops considerations
6. What ships in Phase 1 instead (HTML simulation)

Do not implement LTI — planning memo only.
```

## Integration with cert-research prompts

| cert-research prompt | Add for labs |
|---------------------|--------------|
| Domain research (#1) | Append lab relevance assessment (#1 above) |
| Single-objective deep dive (#2) | Skip lab unless objective is procedural |
| Artifact plan (step 3) | Include lab row with modality + derivation rule |

Full cert-research templates: [prompt-templates.md](../../understandtech-cert-research-content/reference/prompt-templates.md)
