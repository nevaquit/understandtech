# Lesson Visual Architect — Reference

## File map

| Path | Role |
|------|------|
| `content/security-plus/lessons/sy701_*.html` | Authoritative lesson HTML (28 objectives) |
| `content/security-plus/diagrams/sy701_*.html` | Standalone diagram fragments (fallback merge) |
| `content/security-plus/snippets/*.html` | Reusable infographic partials |
| `moodle-plugins/theme_understandtech/style/lesson-content.css` | All diagram styling |
| `moodle-plugins/theme_understandtech/config.php` | Loads `lesson-content` sheet on theme |
| `scripts/inline-lesson-visuals.php` | Repositions diagrams under headings |
| `scripts/seed-security-plus-course.php` | Upserts page content into Moodle DB |

## CSS class catalog

Scoped under `.ut-lesson-content .ut-lesson-diagram`:

| Class | Layout |
|-------|--------|
| `.diagram-title` | Gold Rajdhani heading inside diagram card |
| `.cia-triad` | `grid`, auto-fit ≥10rem columns |
| `.cia-element` | Centered pillar card, gold top border |
| `.flow-diagram` | Flex row, wrap, stretch |
| `.flow-step` | Navy card, centered text |
| `.flow-arrow` | Gold `→` between steps |
| `.controls-matrix` | Grid of `.control-card` |
| `.control-card` | Navy card, gold left border |
| `.concept-grid` | Grid of `.concept-item` |
| `.concept-item` | Same card style as control-card |
| `.threat-actors` | Container for `.threat-actor` blocks |
| `.ut-infographic` | Enhanced gradient border for hero visuals |
| `.ut-svg-figure` | Centers responsive SVG |
| `.ut-cia-triangle` | `width: min(100%, 28rem)` + drop shadow |

## SVG gradient template

```html
<defs>
  <linearGradient id="uniqueIdFill" x1="0%" y1="0%" x2="0%" y2="100%">
    <stop offset="0%" stop-color="#1A8A7D" stop-opacity="0.35"/>
    <stop offset="100%" stop-color="#0B1F3A" stop-opacity="0.15"/>
  </linearGradient>
</defs>
```

## Flow diagram template

```html
<div class="ut-lesson-diagram">
  <div class="diagram-title">🔐 Process Title</div>
  <div class="flow-diagram">
    <div class="flow-step">
      <strong>1. Step name</strong><br>
      Short detail line<br>
      Second detail line
    </div>
    <div class="flow-arrow" aria-hidden="true">→</div>
    <div class="flow-step">
      <strong>2. Next step</strong><br>
      …
    </div>
  </div>
</div>
```

## Triad infographic template

See `content/security-plus/snippets/cia-triad-infographic.html` for the canonical SVG + `cia-triad` card combination. Copy into lesson files; do not link via `<img>`.

## Moodle constraints

- Content format: `FORMAT_HTML` in seed script — raw HTML is stored in `mdl_page.content`.
- No Moodle filters required for SVG; avoid `onclick`, `<script>`, and `foreignObject` with untrusted HTML.
- Theme sheet `lesson-content.css` applies on `path-mod-page` / `.ut-lesson-page` layouts only.
- Purge caches after theme CSS changes: `php admin/cli/purge_caches.php` on VM.

## Adding new diagram types

1. Prototype HTML in the target lesson file.
2. Add scoped CSS under `.ut-lesson-content .ut-lesson-diagram .your-class`.
3. Bump `moodle-plugins/theme_understandtech/version.php`.
4. Deploy (push to `main`) then seed lesson HTML if only markup changed.
