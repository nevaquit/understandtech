# Design System — understandtech.app (Moodle LMS)

## Product Context

- **What this is:** AI-augmented certification training LMS (Moodle 4.5 child theme + custom plugins).
- **Who it's for:** Professionals preparing for vendor certifications (SEC+, Network+, A+, etc.) in long study sessions.
- **Space/industry:** EdTech / B2B SaaS LMS — peers include Coursera for Business, Pluralsight, CompTIA CertMaster-style flows.
- **Project type:** Dark-first learning interface (dashboard, course, lesson, quiz) — not marketing.

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

### After (v1.1 — conversion-focused)

| Token | Hex / value | Role | Contrast on `#0F2035` |
|-------|-------------|------|------------------------|
| `--ut-navy-deep` | `#071529` | Page canvas | — |
| `--ut-navy` | `#0B1F3A` | Brand / headings on light | — |
| `--ut-navy-mid` | `#0C1E35` | Inset inputs (darker = “type here”) | — |
| `--ut-surface` | `#0F2035` | Cards, blocks | — |
| `--ut-surface-elevated` | `#152B45` | Modals, dropdowns, AI tutor panel | — |
| `--ut-surface-hover` | `#1A3554` | Hover panels | — |
| `--ut-gold` | `#C9A227` | Brand gold | 7.4:1 (large text/UI) |
| `--ut-gold-hover` | `#D4B23A` | CTA hover | — |
| `--ut-teal` | `#1A8A7D` | Progress, readiness, links base | 4.8:1 |
| `--ut-teal-light` | `#22B5A5` | Links, active nav secondary | 5.9:1 |
| `--ut-action` | `#C9A227` | **Primary CTA** (enroll, submit) | 7.4:1 |
| `--ut-action-on` | `#0B1F3A` | Text on gold buttons | 7.4:1 |
| `--ut-action-secondary` | `#1A8A7D` | Continue / secondary submit | — |
| `--ut-success` | `#2DD4A0` | Exam readiness, completion | 5.2:1 |
| `--ut-warning` | `#F5B731` | Deadlines, caution (≠ gold CTA) | 6.1:1 |
| `--ut-error` | `#F07178` | Errors, failed attempts | 4.6:1 |
| `--ut-info` | `#5CB8FF` | Informational alerts | 5.4:1 |
| `--ut-text-primary` | `rgba(255,255,255,0.94)` | Body | ~12:1 |
| `--ut-text-secondary` | `rgba(255,255,255,0.72)` | Labels | ~5.5:1 |
| `--ut-text-muted` | `rgba(255,255,255,0.55)` | Metadata | ~4.5:1 (AA) |
| `--ut-focus-ring` | `#C9A227` | Keyboard focus (3px offset) | 3:1+ vs adjacent |

## Aesthetic Direction

- **Direction:** Industrial / utilitarian — certification war-room, not consumer social app.
- **Decoration level:** Minimal — borders and surface steps only; no decorative gradients in LMS chrome.
- **Mood:** Calm focus for 45–90 minute study blocks; gold punctuates decisions; teal tracks momentum.
- **Signature:** Gold enrollment CTAs on deep navy with teal progress rings (exam readiness radar, study plan).

## CTA Hierarchy

1. **Primary (`--ut-action` / `.btn-primary`):** Gold fill, navy text — Enroll, Start activity, Submit quiz.
2. **Secondary (`--ut-action-secondary` / `.btn-secondary`):** Teal fill — Continue module, regenerate plan.
3. **Tertiary (`.btn-outline-secondary`):** Gold border, transparent — Cancel, low-commit actions.
4. **Links (`--ut-link`):** Teal-light default, gold on hover — never styled as buttons.

## Typography

| Role | Font | Rationale |
|------|------|-----------|
| Display / nav / buttons | Rajdhani | Technical, uppercase-friendly; certification badge aesthetic |
| Body / lessons | Source Serif 4 | Long-form reading comfort |
| Code / flags | Share Tech Mono | Lab flags, CLI snippets |
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

## Surface Elevation (dark-first)

```
Canvas (#071529)
  └─ Surface-1 cards (#0F2035)
       └─ Surface-2 elevated (#152B45)
            └─ Surface-3 hover (#1A3554)
Inset inputs: #0C1E35 (below card level)
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
| 2026-06-19 | Dark-first AI tutor sidebar | Matches LMS shell; removes white panel cognitive break |
| 2026-06-19 | Warning amber distinct from gold | Prevents deadline alerts looking like CTAs |
