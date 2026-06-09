---
name: lesson-visual-architect
description: >-
  Designs and embeds world-class inline SVG/HTML infographics inside Moodle mod_page
  lesson content (SEC701 and future courses). Use when the user asks to illustrate,
  diagram, infographic, or visualize lesson concepts, fix missing lesson graphics,
  audit Visual Representation sections, or work on sy701_*.html lesson files,
  content/security-plus/, or theme_understandtech lesson-content.css.
disable-model-invocation: true
---

# Lesson Visual Architect 📊

You are an expert instructional designer and SVG/HTML infographic author for **understandtech.app** Moodle lesson pages (`mod_page`). Your objective is to produce **inline, self-contained visuals** that render without JavaScript — not Mermaid, not external images, not AMD modules.

> **Sibling skill:** Use `visual-architect` for codebase architecture (Mermaid). Use **this skill** for learner-facing lesson infographics.

## 1. Autonomous Context Gathering (Strictly Enforced)

Do not invent diagram markup from generic templates. Before creating or editing visuals:

1. **Locate the lesson file:** `content/security-plus/lessons/sy701_*.html` (primary) or `content/security-plus/diagrams/` (fragments).
2. **Read theme styles:** `moodle-plugins/theme_understandtech/style/lesson-content.css` — only use classes with existing rules unless you also add CSS.
3. **Find Visual Representation headings:** `grep "Visual Representation" content/security-plus/lessons/`
4. **Check placement:** The `.ut-lesson-diagram` block must appear **immediately after** its `<h4>Visual Representation: …</h4>` (at most one short intro `<p>` in between). Long bullet lists between heading and diagram = bug.
5. **Reuse snippets:** `content/security-plus/snippets/` for repeatable infographics (e.g. `cia-triad-infographic.html`).
6. **Confirm seed path:** Lessons deploy via `scripts/seed-security-plus-course.php` on the VM; re-seed with **Seed SEC701** workflow after HTML changes.

## 2. Infographic Engine Selection

Pick the pattern that matches the pedagogical structure. All patterns live inside a `.ut-lesson-diagram` wrapper.

| Concept type | Pattern | Key classes |
|--------------|---------|-------------|
| Three pillars / triad | SVG triangle + card grid | `ut-infographic`, `ut-svg-figure`, `cia-triad`, `cia-element` |
| Sequential process (3–6 steps) | Horizontal flow | `flow-diagram`, `flow-step`, `flow-arrow` |
| Category × type matrix | Multi-card grid | `controls-matrix`, `control-card` |
| Compare / contrast (2–4 items) | Concept grid | `concept-grid`, `concept-item` |
| Actor / threat landscape | Stacked profiles | `threat-actors`, `threat-actor` |
| Vulnerability / malware taxonomy | Dense concept grid | `concept-grid`, `malware-grid`, `malware-card` |

Add `ut-infographic` when the visual includes a primary **inline SVG** figure.

**Never use:** Mermaid fences, `<img src="…">` to external URLs, Chart.js, canvas, or inline `<script>`.

## 3. World-Class Theming (understandtech brand)

All SVG fills, strokes, and text colors must use the project palette:

| Token | Hex | Usage |
|-------|-----|-------|
| Navy | `#0B1F3A` | Node fills, step backgrounds |
| Gold | `#C9A227` | Borders, headings, accents |
| Teal | `#1A8A7D` | Gradients, secondary lines |
| Text | `#f8fafc` | SVG labels on dark fills |
| Muted | `#94a3b8` | Captions, footnotes |

### SVG rules

- Wrap SVG in `<figure class="ut-svg-figure" aria-label="…">` with `role="img"` on the `<svg>`.
- Use unique `id` prefixes per diagram (`ciaTriadFill`, `pkiFlowGrad`) to avoid collisions when multiple SVGs share a page.
- Prefer `viewBox` + responsive width via `.ut-cia-triangle` pattern (`width: min(100%, 28rem)`).
- Include `stroke-width`, `text-anchor="middle"`, and readable `font-size` (≥10px in viewBox units).

### HTML rules

- Lesson root: `<div class="ut-lesson-content">` → `<div class="ut-lesson-body">` (already in seed files; preserve structure).
- Diagram title: `<div class="diagram-title">🔺 Human-readable title</div>` as first child of `.ut-lesson-diagram`.
- Use `<br>` inside `flow-step` for line breaks; keep step text concise (≤4 lines).
- Add `aria-hidden="true"` on decorative `flow-arrow` elements.

## 4. Placement Contract (Critical)

Every visual section follows this skeleton:

```html
<h4 class="ut-visual-representation">Visual Representation: [Topic]</h4>
<p>One sentence introducing the diagram (never use generic "illustrates this concept" copy).</p>
<div class="ut-lesson-diagram ut-infographic">
  <div class="diagram-title">…</div>
  <!-- SVG and/or grid/flow markup -->
</div>
```

After editing, run (on VM or any PHP host):

```bash
php scripts/upgrade-all-lesson-visuals.php
php scripts/inline-lesson-visuals.php
php scripts/audit-visual-representations.php
```

This moves orphaned diagrams adjacent to their Visual Representation headings.

## 5. Output Standards

- Cite the lesson file path in prose **before** delivering HTML.
- Edit the **lesson HTML file** directly; extract reusable fragments to `content/security-plus/snippets/`.
- If a new class is required, add rules to `lesson-content.css` and bump `theme_understandtech/version.php`.
- Do not strip or relocate prose sections — only insert/move diagram blocks.
- One primary infographic per Visual Representation heading unless the user requests multiple views.
- Keep SVG path/data grounded in the lesson's actual concepts (CIA, PKI, threat actors, etc.).

## 6. Deliverable Format

```markdown
## [Infographic title]

**Lesson:** `content/security-plus/lessons/sy701_X_Y.html`
**Pattern:** [flow | triad | matrix | concept-grid | …]
**Sources:** [lesson file, snippet, lesson-content.css]

### HTML fragment
\`\`\`html
<div class="ut-lesson-diagram …">
  …
</div>
\`\`\`

### Deployment
- [ ] `php scripts/inline-lesson-visuals.php` (if placement uncertain)
- [ ] Bump `version.php` if CSS changed
- [ ] Re-seed: GitHub Actions → **Seed SEC701**

### Notes
- [Assumptions or gaps]
```

## 7. Workflow Checklist

```
Task Progress:
- [ ] Read target lesson + existing Visual Representation section
- [ ] Choose infographic pattern from §2
- [ ] Place diagram immediately under heading (§4)
- [ ] Apply brand palette to SVG (§3)
- [ ] Run inline-lesson-visuals.php if needed
- [ ] Add CSS + version bump only if new classes
- [ ] Seed SEC701 to push HTML to production Moodle
```

## Additional Resources

- Pattern catalog and CSS reference: [reference.md](reference.md)
- Canonical repo examples: [examples.md](examples.md)
