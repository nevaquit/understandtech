# Design System Maturity Tiers

Reference for tier definitions, promotion rules, and governance. Read by the skill when scaffolding MATURITY.md and during `--audit` mode.

---

## Tier Definitions

| Tier | Name | When | What Exists | Time to Set Up |
|------|------|------|-------------|----------------|
| 0 | Seed | Day 1 of any project | Principles + tokens only | 30 min |
| 1 | Sprout | After 2-3 screens built | Atoms extracted from real UI | Emergent |
| 2 | Branch | After MVP (5-10 screens) | Molecules, organisms, Storybook | 1-2 days |
| 3 | Bark | Mature product, multiple contributors | Full governance, Figma sync | Ongoing |

**Tiers are not goals** — most projects never reach Tier 3, and that's correct.

---

## File Structure Per Tier

### Tier 0
```
.shipkit/design-system/
├── DIRECTION.md
├── PRINCIPLES.md
├── MATURITY.md
└── tokens/
    └── tokens.css (or Tailwind config)
```

### Tier 1 (adds atoms/)
```
└── components/
    └── atoms/          ← only things used 3+ times
```

### Tier 2 (adds molecules, organisms, patterns, Storybook)
```
├── components/
│   ├── atoms/
│   ├── molecules/
│   └── organisms/
├── patterns/
│   ├── forms.md
│   ├── navigation.md
│   ├── feedback.md
│   └── layout.md
└── .storybook/
```

### Tier 3 (adds governance, Figma sync)
```
├── CONTRIBUTING.md
├── tokens/
│   ├── tokens.css
│   └── figma-tokens.json
└── patterns/
    └── inclusive-design.md
```

---

## Promotion Rules

| From | To | Criteria |
|------|-----|----------|
| Ad-hoc code | Atom | Used in **3+ places** with same/similar implementation |
| Atoms | Molecule | 2+ atoms consistently composed together in **3+ places** |
| Molecules | Organism | Molecule appears in a distinct, repeated layout in **3+ places** |
| Ad-hoc layout | Pattern | Same page structure built **3+ times** |

### How to Promote

1. Identify the repeated pattern in MATURITY.md emerging section
2. Extract the component from product code into `.shipkit/design-system/components/`
3. Refactor product code to import from the design system
4. Document the component (inline, co-located)
5. Add a Storybook story (Tier 2+)
6. Record the promotion in MATURITY.md recently promoted section

### When NOT to Promote

- Used in only 1-2 places (keep it in product code)
- Highly page-specific and unlikely to be reused
- Anticipating future needs, not responding to real repetition
- The abstraction would be more complex than the duplication

---

## Component Categories

| Category | Answers | Examples |
|----------|---------|---------|
| Forms | "How does the user give us data?" | Button, TextInput, Select, Checkbox |
| Navigation | "How does the user move around?" | NavBar, Breadcrumbs, Tabs, Sidebar |
| Layout | "How is content structured?" | Modal, Accordion, Card, Grid |
| Feedback | "How does the system talk back?" | Alert, Toast, ProgressBar, Skeleton |
| Data Display | "How do we show information?" | Table, List, Stats, Badge |
| Media | "How do we handle visuals?" | Icon, Avatar, ImageContainer |

---

## Naming Conventions

Components are named by **what they are**, not where they're used:

| Good | Bad |
|------|-----|
| `Button` | `HeroButton` |
| `TextInput` | `LoginEmailField` |
| `Badge` | `CourseBadge` |

Page-specific variants use props: `<Button variant="primary" size="lg" />` not `<HeroCTAButton />`

---

## Governance Model

### Tier 0-1: No governance
Just follow promotion rules. No process needed.

### Tier 2: Lightweight
- Any developer can promote if 3-use rule is met
- Naming must follow conventions
- PR review covers design system changes

### Tier 3: Principle-Based (from Bark model)
1. **Propose** — describe component and its 3+ existing uses
2. **Interrogate** — does it satisfy all principles? Where do they conflict?
3. **Resolve** — document how tensions are resolved
4. **Build** — implement in code and Figma
5. **Document** — co-located docs + Storybook story

No single "owner" approves/rejects. The principles are the authority.

---

## MATURITY.md Template

```markdown
# Design System Maturity

**Current Tier:** Seed (Tier 0)
**Last Updated:** YYYY-MM-DD

## What's Defined
- [x] Design direction (DIRECTION.md)
- [x] Principles (3-5)
- [x] Color tokens
- [x] Type scale
- [x] Spacing scale
- [ ] Atoms extracted
- [ ] Molecules composed
- [ ] Storybook running
- [ ] Figma library synced

## Emerging Patterns
<!-- Components/patterns seen 2+ times but not yet promoted (need 3) -->
<!-- Updated by --audit mode -->

## Recently Promoted
<!-- Track what moved from ad-hoc to system component -->

## Not Yet Needed
<!-- Explicitly declare what's intentionally absent -->
- Component library (only tokens exist — too early)
- Storybook (need atoms first)
- Figma sync (single designer or no designer)
```
