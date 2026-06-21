# Design System — understandtech.app (Moodle LMS)

## Product Context

- **What this is:** AI-augmented certification training LMS (Moodle 4.5 child theme + custom plugins).
- **Who it's for:** Professionals preparing for vendor certifications (SEC+, Network+, A+, etc.) in long study sessions.
- **Space/industry:** EdTech / B2B SaaS LMS — peers include Coursera for Business, Pluralsight, CompTIA CertMaster-style flows.
- **Project type:** Light-first learning interface aligned with marketing OCW shell (dashboard, course, lesson, quiz) — white canvas, navy headings, OCW red CTAs.

## Research Summary (2025–2026)

| Finding | Source | Design implication |
|---------|--------|------------------|
| CTA **contrast** beats hue for conversion; 7:1+ against background wins | [roast.page CTA analysis](https://roast.page/blog/cta-button-analysis) | Gold `#C9A227` on navy `#0B1F3A` ≈ **7.4:1** — primary action colour |
| No universal “best” button colour; visual prominence in viewport matters | [CXL](https://cxl.com/blog/which-color-converts-the-best/) | One accent for CTAs; teal reserved for progress/links so it does not compete |
| Navy/blue signals trust and premium in finance/enterprise SaaS | CXL, ColorArchive SaaS guide | Keep navy canvas; gold reads certification/premium |
| Dark dashboards need restrained accents + semantic colours for status only | Dark mode UI guides | Teal = progress; amber `#F5B731` = warning (distinct from gold CTA) |
| WCAG 2.2 AA: 4.5:1 body text, 3:1 large text/UI, 3:1 focus rings | WebAIM 2025, Section508.gov | Token pairs audited below; focus ring uses gold 3px |

## Palette — Before / After

### Before (v1.0)

| Role | Hex | Issue |
|------|-----|-------|
| Canvas | `#0B1F3A` | OK — trust anchor |
| CTA (`.btn-primary`) | `#1A8A7D` teal | Low prominence vs navy-heavy UI; competed with links |
| Secondary (`.btn-secondary`) | `#C9A227` gold | Inverted hierarchy |
| Surface | Mixed `#0f2447` / `#0F2035` | Inconsistent elevation |
| Semantic | Ad hoc rgba | No shared success/warning/error tokens |

### After (v1.2 — light-first, marketing-aligned)

| Token | Hex / value | Role | Notes |
|-------|-------------|------|-------|
| `--ut-bg` | `#ffffff` | Page canvas | Matches marketing |
| `--ut-surface-2` | `#f5f5f5` | Alt panels, table headers | — |
| `--ut-text` | `#1a1a1a` | Body copy | WCAG AAA on white |
| `--ut-text-muted` | `#525252` | Metadata | — |
| `--ut-border` | `#e0e0e0` | Cards, drawers, inputs | Neutral gray |
| `--ut-navy` | `#0B1F3A` | Headings | Brand anchor |
| `--ut-ocw-red` / `--ut-link` | `#A31F34` | Primary CTA, links | MIT OCW accent |
| `--ut-ocw-red-dark` | `#750014` | Link/CTA hover | — |
| `--ut-teal-on-light` | `#0d5c52` | Progress, secondary accents | Accessible on white |
| `--ut-gold-on-light` | `#7a5f10` | Sparring gold accents | Warnings, badges |
| `--ut-action` | `#A31F34` | **Primary CTA** | OCW red fill, white text |
| `--ut-action-secondary` | `#1A8A7D` | Continue / progress | Teal |

## Aesthetic Direction

- **Direction:** Industrial / utilitarian — certification war-room, not consumer social app.
- **Decoration level:** Minimal — borders and surface steps only; no decorative gradients in LMS chrome.
- **Mood:** Calm focus for 45–90 minute study blocks; gold punctuates decisions; teal tracks momentum.
- **Signature:** Gold enrollment CTAs on deep navy with teal progress rings (exam readiness radar, study plan).

## CTA Hierarchy

1. **Primary (`--ut-action` / `.btn-primary`):** Gold fill, navy text — Enroll, Start activity, Submit quiz.
2. **Secondary (`--ut-action-secondary` / `.btn-secondary`):** Teal fill — Continue module, regenerate plan.
3. **Tertiary (`.btn-outline-secondary`):** Gold border, transparent — Cancel, low-commit actions.
4. **Links (`--ut-link`):** OCW red default, darker red on hover — never styled as buttons.

## Typography

| Role | Font | Rationale |
|------|------|-----------|
| All UI / body | system-ui stack | Matches marketing; crisp, low cognitive load |
| Code / flags | ui-monospace stack | Lab flags, CLI snippets |
| Scale | 1rem body, modular headings via `--ut-fluid-*` | Responsive without breakpoint soup |

## Spacing

- **Base unit:** 8px (0.5rem)
- **Density:** Comfortable — certification content is dense; whitespace separates domains not decorates.
- **Scale:** xs(4) sm(8) md(16) lg(24) xl(40) 2xl(64) 3xl(96)

## Layout

- **Approach:** Grid-disciplined Boost drawers — sidebar same hue as canvas, border separation only.
- **Max content width:** `--ut-content-max: 75rem`
- **Border radius:** sm 6px inputs/buttons, md 12px cards, lg 20px hero cards

## Motion

- **Approach:** Minimal-functional — 150ms hovers; `prefers-reduced-motion` zeroes animations.
- **Easing:** ease for micro; no bounce in professional LMS chrome.

## Surface Elevation (light-first)

```
Canvas (#ffffff)
  └─ Surface alt (#f5f5f5) — table headers, inset panels
       └─ Cards (#ffffff + #e0e0e0 border)
Footer / hero accents: navy-deep (#071528) used sparingly
```

## Implementation Map

| Layer | Location |
|-------|----------|
| SCSS variables | `moodle-plugins/theme_understandtech/scss/partials/_design-tokens.scss` |
| Admin injection | `theme_understandtech/lib.php` → `theme_understandtech_derive_palette()` |
| Compiled preset | `scss/preset/default.scss` (regenerate from partials) |
| Runtime CSS vars | `theme_understandtech_process_css()` prepended to compiled CSS |
| Plain CSS shell | `style/global-shell.css`, `style/lesson-content.css`, `style/editor.css` |
| Plugin alignment | `local_aitutor/styles.css`, `mod_ctfflag/styles.css`, `qbehaviour_certmasterconfidence/styles.css` |

## Decisions Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-06-19 | Gold primary CTA, teal secondary | Research: contrast + role separation for enrollment vs progress |
| 2026-06-19 | Semantic token layer `--ut-action`, `--ut-success`, etc. | WCAG-audited pairs; admin brand colours derive full palette |
| 2026-06-22 | Light-first LMS shell aligned with marketing OCW palette | White canvas, system-ui fonts, OCW red CTAs/links, navy headings |
| 2026-06-19 | Dark-first AI tutor sidebar | Superseded by light shell; tutor plugin CSS may need separate pass |
| 2026-06-19 | Warning amber distinct from gold | Prevents deadline alerts looking like CTAs |
