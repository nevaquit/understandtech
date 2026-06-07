---
name: shipkit-design-system
description: "Scaffold a tiered design system — principles, tokens, and aesthetic direction grounded in project context. Triggers: 'design system', 'design tokens', 'brand guidelines', 'visual direction', 'design principles'."
argument-hint: "[brand colors or focus] [--audit] [--refresh]"
agent: shipkit-architect-agent
allowed-tools: Read, Write, Edit, Glob, Grep, Agent, AskUserQuestion
effort: medium
---

# shipkit-design-system — Tiered Design System

Synthesizes design direction from upstream project context and scaffolds a design system that grows with the product — from tokens + principles on day 1 to a governed component library at maturity.

Combines two approaches:
- **Creative direction** (inspired by Anthropic's frontend-design plugin) — opinionated aesthetic guidance, but derived from YOUR project context instead of generic defaults
- **Structural scaffolding** (from the maturity-tiered model) — principles, tokens, maturity tracking that persist across sessions

---

## When to Invoke

**User triggers**:
- "Set up design system", "Design tokens", "Brand guidelines"
- "Visual direction", "Design principles", "How should this look?"

**Workflow position**:
- After `/shipkit-engineering-definition` (needs stack direction, component structure)
- Before `/shipkit-product-goals` and `/shipkit-engineering-goals` (goals can reference design criteria)
- Also useful standalone at any point for projects with UI

---

## Prerequisites

**Required** (fail gracefully if missing):
- `.shipkit/why.json` — vision, tone, brand identity (drives principles and direction)

**Recommended** (enrich the output):
- `.shipkit/product-discovery.json` — personas, accessibility needs
- `.shipkit/product-definition.json` — features, UX patterns
- `.shipkit/engineering-definition.json` — stack direction, component structure
- `.shipkit/goals/strategic.json` — project stage

**Optional**:
- `.shipkit/stack.json` — tech stack (token format detection)
- `.shipkit/codebase-index.json` — existing code patterns

If why.json is missing, tell the user: "Run `/shipkit-why-project` first — I need to understand the project vision to derive design direction." and stop.

---

## Process

### Completion Tracking

After reading prerequisites, create tasks:
- `TaskCreate`: "Synthesize design direction from project context"
- `TaskCreate`: "Generate 3-5 verb-based design principles"
- `TaskCreate`: "Scaffold token file (CSS or Tailwind)"
- `TaskCreate`: "Write MATURITY.md and DIRECTION.md"
- `TaskCreate`: "Update architecture.json with designSystem section"

### Step 0: Check for Existing Files

1. Check if `.shipkit/design-system/` directory exists
2. Also check for common alternatives: `design-system/`, `design-tokens/`, `tokens/`, `theme/`
3. If `.shipkit/design-system/` exists AND modified < 5 minutes ago: Show user, ask "Use this or regenerate?"
4. If `.shipkit/design-system/` exists AND modified > 5 minutes ago: File Exists Workflow (Step 0b)
5. If alternative directory found: Ask user whether to adopt it or create `.shipkit/design-system/`
6. If nothing exists: Skip to Step 1

**File Exists Workflow (Step 0b)**:
- Options: View / Update / Replace / Cancel
- View: Display current files, then ask what to do
- Update: Read existing, ask what to change, regenerate with updates
- Replace: Archive old version, generate completely new
- Cancel: Exit without changes

---

### Step 0c: Propose Mode (Context-Driven)

If `.shipkit/why.json` AND `.shipkit/product-discovery.json` both exist, attempt to propose without asking:

1. Read all available context files in parallel:
   - `.shipkit/why.json` — vision, tone, brand identity
   - `.shipkit/product-discovery.json` — personas, accessibility needs, pain points
   - `.shipkit/product-definition.json` — features, UX patterns (if exists)
   - `.shipkit/engineering-definition.json` — stack, components (if exists)
   - `.shipkit/goals/strategic.json` — project stage (if exists)
   - `.shipkit/stack.json` — tech stack for token format detection (if exists)

2. Detect token format:
   - If Tailwind in stack.json or package.json → Tailwind config
   - If CSS framework or vanilla → CSS custom properties
   - Default: CSS custom properties

3. Detect project stage for maturity tier:
   - POC/new project → Tier 0 (Seed)
   - MVP with screens built → suggest Tier 1 check via `--audit`

4. Read `references/direction-guide.md` for the creative direction framework

5. Synthesize and write all artifacts:
   - `.shipkit/design-system/DIRECTION.md` — aesthetic brief
   - `.shipkit/design-system/PRINCIPLES.md` — 3-5 principles
   - `.shipkit/design-system/MATURITY.md` — tier declaration
   - `.shipkit/design-system/tokens/tokens.css` or Tailwind extension
   - Update `.shipkit/architecture.json` with `designSystem` section

6. Present summary — the orchestrator's review cycle will catch issues

If insufficient context → fall through to Step 1.

---

### Step 1: Gather Context

**Read these files** (all in parallel):

```
.shipkit/why.json                    → vision, tone (REQUIRED)
.shipkit/product-discovery.json      → personas, accessibility (RECOMMENDED)
.shipkit/product-definition.json     → features, UX patterns (RECOMMENDED)
.shipkit/engineering-definition.json → stack, components (RECOMMENDED)
.shipkit/goals/strategic.json        → stage (OPTIONAL)
.shipkit/stack.json                  → tech stack (OPTIONAL)
```

**Extract key design inputs:**
- From why.json: vision statement, tone keywords, target audience description
- From product-discovery: primary persona, accessibility requirements, emotional journey
- From product-definition: UX patterns chosen, interaction style
- From engineering-definition: frontend framework, component structure approach
- From stage: POC/MVP/Growth → determines appropriate maturity tier

---

### Step 2: Design Direction

Read `references/direction-guide.md` for the creative direction framework.

**Benchmark first:** Before committing to aesthetic choices, identify the key UX patterns from product-definition.json (e.g., dashboard, onboarding, search/filter). For each, web-search for 2-3 products known for excellent implementations and note what makes their design work. Let these benchmarks inform the direction — reference real evidence, not abstract theory. See `references/direction-guide.md` Step 0 for details.

Synthesize an aesthetic brief covering 6 dimensions, each derived from project context:

1. **Aesthetic Tone** — from why.json vision + persona needs
2. **Typography Direction** — from brand personality + accessibility
3. **Color Philosophy** — from brand identity + emotional targets
4. **Spacing & Density** — from product type + primary persona
5. **Motion Principles** — from UX patterns + interaction style
6. **Accessibility Stance** — from personas + legal/ethical requirements

**Use AskUserQuestion** if context is insufficient for any dimension:
```
header: "Design Direction"
question: "What aesthetic tone fits this product?"
options:
  - label: "[Proposed tone from context]"
    description: "Based on your vision and audience"
  - label: "Different direction"
    description: "I have something else in mind"
```

---

### Step 3: Design Principles

Generate 3-5 verb-based principles. Each principle has:
- **Name** — single action verb (not adjective)
- **Means** — one sentence: what it demands
- **Tension** — which other principle it pushes against
- **In practice** — one concrete example

Principles must be **product-specific** (derived from vision and personas), not generic. They should create productive tension with each other.

Ask user to confirm or adjust.

---

### Step 4: Token Scaffold

Read `references/token-templates.md` for the template.

Generate tokens based on direction decisions:
- **Brand colors** (5-8) — from color philosophy
- **Semantic colors** (4-6) — success, error, warning, info, surface
- **Neutral scale** (5-7) — light to dark
- **Type scale** (6 sizes) — xs through 3xl
- **Font families** (2) — heading + body, from typography direction
- **Spacing scale** (6-8) — based on density decision
- **Border radius** (3) — sm, md, lg
- **Breakpoints** (3-4) — sm, md, lg, xl

If user provided brand colors in `$ARGUMENTS`, use those as the brand palette seed.

---

### Step 5: Write All Artifacts

Write these files:

1. **`.shipkit/design-system/DIRECTION.md`** — the aesthetic brief (6 dimensions)
2. **`.shipkit/design-system/PRINCIPLES.md`** — 3-5 verb-based principles
3. **`.shipkit/design-system/MATURITY.md`** — Tier 0 declaration with checklists
4. **`.shipkit/design-system/tokens/tokens.css`** (or Tailwind config extension)
5. **`.shipkit/architecture.json`** — add/update `designSystem` section:

```json
{
  "designSystem": {
    "tier": 0,
    "tierName": "Seed",
    "principles": ["Verb1", "Verb2", "Verb3"],
    "tokenFormat": "css|tailwind",
    "location": ".shipkit/design-system/",
    "lastUpdated": "ISO date"
  }
}
```

For MATURITY.md format, read `references/maturity-tiers.md`.

---

### Step 6: Suggest Next Steps

```
Design system scaffolded to .shipkit/design-system/
Tier: Seed (Tier 0) | Principles: {N} | Token categories: {N}

Files created:
  - DIRECTION.md — aesthetic brief
  - PRINCIPLES.md — design principles
  - MATURITY.md — maturity tracking
  - tokens/ — design tokens ({format})

Next:
  1. /shipkit-product-goals — define success criteria (can now reference design quality)
  2. Start building UI — tokens are ready to use
  3. After 2-3 screens: /shipkit-design-system --audit to check for emerging patterns

Ready to define goals?
```

---

## Audit Mode (`--audit`)

When `$ARGUMENTS` contains `--audit`:

1. Read `.shipkit/design-system/MATURITY.md` (must exist)
2. Read `.shipkit/codebase-index.json` if available, otherwise scan `src/` or `app/`
3. Search for repeated UI patterns:
   - Components imported in 2+ files
   - Similar JSX/HTML structures appearing 3+ times
   - Inline styles or class patterns repeated across files
4. Update `MATURITY.md`:
   - Add new entries to "Emerging Patterns" section (2+ uses)
   - Flag patterns at 3+ uses for promotion to atoms
5. If current tier is 0 and atoms are being suggested → recommend moving to Tier 1
6. Read `references/maturity-tiers.md` for promotion rules

**Audit mode is read-only except for MATURITY.md** — it never creates components.

---

## When $ARGUMENTS is Provided

- **Brand colors** (e.g., `#3b82f6 #10b981`): Use as brand palette seed
- **`--audit`**: Run audit mode instead of scaffold
- **`--refresh`**: Regenerate even if .shipkit/design-system/ exists

---

## When This Skill Integrates with Others

### Before This Skill
| Skill | Why |
|-------|-----|
| `shipkit-why-project` | Vision, tone → drives principles and direction |
| `shipkit-product-discovery` | Personas, a11y needs → drives inclusive design |
| `shipkit-product-definition` | Features, UX patterns → drives component anticipation |
| `shipkit-engineering-definition` | Stack, components → drives token format |

### After This Skill
| Skill | How |
|-------|-----|
| `shipkit-product-goals` | Can reference design quality criteria |
| `shipkit-spec` | Specs reference tokens and principles for UI features |
| `shipkit-plan` | Plans reference component inventory |
| `shipkit-review-shipping` | Reviews check token usage and principle compliance |
| `shipkit-ux-audit` | Checks alignment with design system patterns |

---

## Context Files This Skill Reads

| File | Purpose | If Missing |
|------|---------|------------|
| `.shipkit/why.json` | Vision, tone, brand identity | Stop — route to `/shipkit-why-project` |
| `.shipkit/product-discovery.json` | Personas, accessibility | Ask user about audience |
| `.shipkit/product-definition.json` | Features, UX patterns | Proceed without |
| `.shipkit/engineering-definition.json` | Stack, components | Default to CSS tokens |
| `.shipkit/goals/strategic.json` | Project stage | Default to Tier 0 |
| `.shipkit/stack.json` | Token format detection | Default to CSS |
| `.shipkit/codebase-index.json` | Audit mode file scanning | Scan manually |

## Context Files This Skill Writes

**Artifact strategy: replace** — Overwrites existing files. Archive old versions first.

| File | When |
|------|------|
| `.shipkit/design-system/DIRECTION.md` | Every run |
| `.shipkit/design-system/PRINCIPLES.md` | Every run |
| `.shipkit/design-system/MATURITY.md` | Every run + audit mode |
| `.shipkit/design-system/tokens/tokens.css` | Every run (or Tailwind equivalent) |
| `.shipkit/architecture.json` | Adds/updates `designSystem` section |

**Archive location**: `.shipkit/design-system/archive/{filename}.{timestamp}`

---

<!-- SECTION:after-completion -->
## After Completion

**Guardrails Check:** Before moving to next task, verify:

1. **Persistence** - All artifacts written to `.shipkit/design-system/`?
2. **Prerequisites** - Does the next action need goals first?
3. **Session length** - Long session? Consider `/shipkit-work-memory` for continuity.

**Natural capabilities** (no skill needed): Implementation, debugging, testing, refactoring.

**Suggest skill when:** User needs success criteria (`/shipkit-product-goals`), feature specs (`/shipkit-spec`), or UX audit (`/shipkit-ux-audit`).
<!-- /SECTION:after-completion -->

<!-- SECTION:success-criteria -->
## Success Criteria

Design system scaffold is complete when:
- [ ] why.json read and vision/tone extracted
- [ ] DIRECTION.md covers all 6 aesthetic dimensions, derived from project context
- [ ] PRINCIPLES.md contains 3-5 verb-based principles with tension pairs
- [ ] Principles are product-specific (not generic like "be simple")
- [ ] MATURITY.md declares Tier 0 with checklist and emerging patterns section
- [ ] tokens/ contains full token set matching detected format (CSS or Tailwind)
- [ ] Token set covers: brand colors, semantic colors, neutrals, type, fonts, spacing, radius, breakpoints
- [ ] architecture.json updated with designSystem section
- [ ] All files written to `.shipkit/design-system/`
<!-- /SECTION:success-criteria -->

---

**Remember**: This skill captures design direction — HOW the product looks and feels, grounded in WHO uses it and WHY it exists. It produces persistent artifacts that inform every UI decision downstream. The design system starts small (Tier 0) and grows through real usage, not speculation. Run `--audit` after building screens to track what's emerging.
