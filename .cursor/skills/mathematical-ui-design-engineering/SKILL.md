---
name: mathematical-ui-design-engineering
description: >-
  Designs fluid, data-driven Moodle frontend UX for understandtech.app—mathematical
  visual scaling (clamp(), container queries), Chart.js telemetry in AMD modules,
  four-quadrant domain radar charts, and Level Up XP gamification with Skool-like
  community patterns. Use when building or reviewing theme_understandtech SCSS,
  block_examreadiness charts, Mustache templates, AMD modules, Figma-to-CSS tokens,
  responsive layouts, or XP milestone UX.
---

# Mathematical UI Design Engineering

## Context: understandtech.app

**Product:** AI-augmented certification training on Moodle 4.5 LTS with Skool-inspired community UX.

**Frontend stack (non-negotiable):** Boost child theme (`theme_understandtech`), Mustache templates, AMD modules (`define([...])`), SCSS compiled via Moodle Grunt. No React/Vue in Moodle plugins. No secrets, API keys, or JWT signing material in frontend code.

**Brand palette (`.cursorrules` / theme defaults):**

| Token | Hex | SCSS variable |
|-------|-----|---------------|
| Navy | `#0B1F3A` | `$ut-brand-navy` |
| Gold | `#C9A227` | `$ut-brand-gold` |
| Teal | `#1A8A7D` | `$ut-brand-teal` |

Injected in `theme_understandtech/lib.php` via `theme_understandtech_get_pre_scss()`. Typography: Rajdhani (headings), Source Serif 4 (body), Share Tech Mono (code).

**Repo entry points:**

| Area | Path | Status |
|------|------|--------|
| Theme SCSS + overrides | `moodle-plugins/theme_understandtech/scss/` | Implemented |
| Skool layout classes | `.ut-skool-layout`, `.ut-community-feed`, `.ut-dashboard-cards` in `scss/preset/default.scss` | Implemented |
| XP leaderboard skin | `templates/block_xp/main.mustache` + `.ut-leaderboard` in `scss/post.scss` | Implemented (requires `block_xp`) |
| Exam readiness block | `moodle-plugins/block_examreadiness/` | PHP + Mustache scaffold; canvas placeholder |
| Readiness API | `local_certmaster/classes/api.php` → `get_user_readiness()` | Implemented |
| Radar Chart.js AMD | `local_certmaster/amd/src/radar_chart.js` | **Planned — not in repo yet** |
| Reference AMD pattern | `local_aitutor/amd/src/tutor_sidebar.js` | Implemented |

---

## Domain description

Mathematical UI Design EngineeringTo provide a smooth, engaging environment modeled after systems like Skool, your frontend designers need strong, data-driven design chops.  Mathematical Visual Scaling: Translating design layouts from Figma directly to fluid, modern CSS using calculated scales (clamp()) and container queries to elegantly scale user displays effortlessly from mobile layouts to 8K studio screens.Canvas & Telemetry Component Rendering: Interfacing with analytical data engines (Chart.js) to parse and project asynchronous JSON telemetry profiles seamlessly into functional four-quadrant domain radar charts.  Gamification Architecture: Calibrating point economies via Moodle plugins (Level Up XP) to transform student participation, lab completions, and peer support logs into dynamic, unlocked milestones.

---

## 1. Mathematical Visual Scaling

### Figma token → SCSS mapping

Map Figma variables to theme tokens before writing layout CSS:

1. **Brand colours** → `$ut-brand-*` (admin-overridable via `settings.php`; never hardcode hex in component SCSS except fallbacks in `lib.php`).
2. **Spacing scale** → derive from a base unit (e.g. 4px) using `clamp()` for fluid gutters/padding.
3. **Type scale** → fluid heading/body sizes tied to viewport or container width.
4. **Radii / shadows** → reuse `$card-border-radius` and navy-tinted shadows (see `default.scss`).

### Fluid scales with `clamp()`

Prefer calculated scales over fixed breakpoints for typography and spacing:

```scss
// Example tokens for post.scss or default.scss
$ut-space-md: clamp(0.75rem, 0.5rem + 1vw, 1.25rem);
$ut-heading-lg: clamp(1.25rem, 1rem + 1.2vw, 2rem);
$ut-content-max: min(100%, 90rem);
```

Use `clamp(min, preferred, max)` where `preferred` combines rem + vw/vi so layouts scale from mobile to ultrawide/8K without discrete jumps.

### Container queries (`@container`)

Wrap Skool-style widgets (cards, lesson panes, radar block) in a named container, then size inner elements by container width—not only viewport:

```scss
.block-examreadiness {
    container-type: inline-size;
    container-name: readiness;
}

@container readiness (min-width: 20rem) {
    .block-examreadiness-radar {
        max-height: clamp(12rem, 40cqw, 24rem);
    }
}
```

Fall back to existing viewport rules (e.g. `.ut-skool-layout .ut-lesson-grid` at `@media (max-width: 991.98px)`) when container queries are insufficient for page-level layout.

### Theme file layout

| File | Role |
|------|------|
| `scss/preset/default.scss` | Layout, nav, Skool grids, buttons |
| `scss/post.scss` | Widget overrides (leaderboard, member cards, toasts) |
| `lib.php` | Pre-SCSS brand injection, `get_extra_scss()` loader |

After SCSS edits: `npx grunt scss --themes=understandtech` and purge theme caches.

### Skool UX targets (white paper §2.2)

- Top nav ≤5 items (Community, Classroom, Calendar, Members, Leaderboards)
- Classroom: horizontal card carousel of tracks
- Lesson: two-pane `.ut-lesson-grid` (media left, nav right)
- Community: merged feed styling via `.ut-community-feed`
- Profiles: `.ut-member-card` pattern in `post.scss`

---

## 2. Canvas & Telemetry Component Rendering

### Data flow (exam readiness)

```
local_certmaster\api::get_user_readiness()
  → block_examreadiness.php (json_encode radar)
  → templates/main.mustache (data-radar on canvas)
  → AMD init reads data attribute → Chart.js radar
```

**Radar JSON shape** (from `api.php`):

```json
[
  {"domain": "short", "label": "Full domain name", "score": 72.5, "weight": 22},
  ...
]
```

Scores are 0–100 per certification domain (Security+ seeds five domains). The **four-quadrant mastery model** (Guessing/Unsure/Confident/Certain × correct/incorrect) lives in CertMaster pedagogy (`docs/white-paper.md` §2.1); the **dashboard radar** visualizes per-domain aggregated mastery, not the quadrant grid itself.

### Mustache + canvas (current scaffold)

`block_examreadiness/templates/main.mustache` exposes:

```html
<canvas class="block-examreadiness-radar" data-radar="{{radar}}" height="200"
        aria-label="Domain mastery radar"></canvas>
```

Keep markup in Mustache; never inject chart HTML from PHP strings.

### Chart.js in Moodle AMD

**Planned module:** `local_certmaster/amd/src/radar_chart.js` (see `local_certmaster/README.md`).

Implementation rules:

1. **AMD define** — follow `local_aitutor/amd/src/tutor_sidebar.js`: `define(['jquery', 'core/chartjs', ...], function($, ChartJS) { ... })` or register Chart.js as a Moodle thirdparty module if core bundle version differs.
2. **Version compatibility** — use the Chart.js version bundled with Moodle 4.5 core (`core/chartjs` or theme `thirdpartylibs.xml`). Do not CDN-load Chart.js; AMD bundling requires `grunt amd` and committed `amd/build/*.min.js`.
3. **Init from block** — in `block_examreadiness.php`, call `$PAGE->requires->js_call_amd('local_certmaster/radar_chart', 'init', ['.block-examreadiness-radar'])` once the AMD file exists.
4. **Parse telemetry** — read `dataset.radar` JSON from canvas; validate array shape before render; handle empty state (Mustache `{{#empty}}` already covers no attempts).
5. **Async refresh** — prefer server-rendered data on page load; for live updates after quiz, use `core/ajax` web service calling `get_user_readiness` (define in `db/services.php` when needed), then `chart.update()`.

### Radar chart spec (domain readiness)

| Property | Value |
|----------|-------|
| Type | `radar` |
| Labels | `radar[].label` (truncate long blueprint names for mobile) |
| Dataset values | `radar[].score` |
| Scale | 0–100, `beginAtZero: true` |
| Colors | Stroke/fill using `$ut-brand-teal` / `$ut-brand-navy` (pass hex from PHP `$OUTPUT` or CSS variables on canvas parent) |
| Responsive | `maintainAspectRatio: true`; resize on `@container` width changes via `ResizeObserver` |
| Accessibility | `aria-label` on canvas; provide screen-reader summary table or `role="img"` + off-screen text listing domain scores |

### Responsive canvas pitfalls

- Set canvas **CSS width 100%**; let Chart.js manage device pixel ratio on resize.
- Debounce resize handlers; destroy chart instance on AMD `unload` if SPA-like navigation applies.
- Never embed user PII in chart labels or tooltips.

---

## 3. Gamification Architecture

### Level Up XP integration

**Plugin:** `block_xp` (Level Up XP) — third-party, installed on production VM, not committed to this repo.

**Theme override:** `theme_understandtech/templates/block_xp/main.mustache` skins the leaderboard with `.ut-leaderboard`, rank gold styling, teal/gold XP bars (`scss/post.scss`).

### XP economy calibration (white paper §2.2)

| Behavior | Relative XP | Notes |
|----------|-------------|-------|
| Community post | Small, frequent | Drives Skool-like feed activity |
| Peer support (forum answer) | Substantial | Reward helping others |
| Lesson completion | Moderate | Core progression |
| Lab submission | Large | CTF / `mod_ctfflag` integration (playbook Phase 3) |
| Practice exam pass | Largest | Tied to cert readiness milestones |

Configure rules in Level Up XP admin UI; custom triggers via Moodle **events** observed in local plugins (`db/events.php`), not theme JS.

### Milestone unlock UX

- Level thresholds unlock advanced content, office hours, profile badges (white paper).
- Surface unlocks via `core/toast` override (`templates/core/toast.mustache`) + `.ut-notification-toast` styling.
- Milestone state lives server-side; frontend only reflects capabilities returned in Mustache context—never trust client-side XP math.

### Event/hook patterns

- Award XP on lab flag success: observer on `\mod_ctfflag\event\flag_submitted` (when implemented) calling Level Up XP API or `\block_xp\...` helper.
- Forum participation: core `\mod_forum\event\post_created`.
- Quiz mastery: tie to `local_certmaster` readiness thresholds, not raw attempt count.

PHP for observers and XP grants: see **moodle-core-php-engineering** skill.

---

## Operational checklists

### New Skool-style UI component

- [ ] Figma tokens mapped to `$ut-brand-*` / fluid `clamp()` scales
- [ ] Markup in Mustache under correct plugin or `theme_understandtech/templates/`
- [ ] SCSS in `preset/default.scss` (layout) or `post.scss` (widgets)
- [ ] Container query or mobile fallback tested at 320px and ≥2560px widths
- [ ] Strings via `get_string()` / `{{#str}}` — no raw English in templates
- [ ] Grunt SCSS + AMD build; purge caches

### Radar chart delivery

- [ ] `get_user_readiness()` returns valid `radar` array
- [ ] `amd/src/radar_chart.js` + `amd/build/` committed
- [ ] Chart.js version matches Moodle core bundle
- [ ] `js_call_amd` from block; canvas has `aria-label`
- [ ] Empty and error states handled without console exceptions

### Gamification touchpoint

- [ ] XP rule documented with relative weight
- [ ] Server-side event observer (not frontend-only)
- [ ] Leaderboard uses theme `block_xp/main` override
- [ ] Unlock messaging uses toast template + accessible text

---

## Common pitfalls

| Pitfall | Fix |
|---------|-----|
| Fixed px typography breaks on 8K | Replace with `clamp()` + container queries |
| Chart.js loaded from CDN | Use Moodle AMD + core/thirdparty Chart.js |
| Missing `amd/build/*.min.js` | Run `grunt amd`; commit build artifacts |
| Inline `style=""` for brand colours | Use SCSS variables / theme settings |
| JSON in `data-*` without escaping | Pass via Mustache; validate parse in AMD |
| XP logic in JavaScript | Observers + Level Up XP config server-side |
| Secrets in frontend for “live” charts | Web services with session capability checks only |
| Breaking Boost upgrade path | Child theme only—never edit Moodle core templates in place |

---

## Related skills & docs

- PHP, forms, observers, `$DB`: **moodle-core-php-engineering** (`.cursor/skills/moodle-core-php-engineering/`)
- Broad Moodle plugin patterns: personal **moodle-development** skill
- Enterprise LMS + AI orchestration: **lms-workflow**, **lms-enterprise-ai-master-skill**
- Premium layout/a11y bar: personal **premium-web-development** skill (when elevating visual polish)
- Platform architecture: `.cursor/skills/understandtech-platform/`
- Pedagogy + XP economy: `docs/white-paper.md` §2.1–2.2
- Build sequence: `docs/playbook.md` Phase 3 (theme, certmaster, blocks)
