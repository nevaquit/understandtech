# Design Direction Guide

Framework for deriving aesthetic direction FROM project context rather than applying generic defaults. Inspired by Anthropic's frontend-design plugin approach — opinionated creative guidance — but grounded in the specific project.

---

## Step 0: Benchmark Research

**Before committing to any aesthetic choices, ground the direction in real-world evidence.**

1. Read `product-definition.json` for the key UX patterns the product uses (e.g., dashboard, onboarding wizard, search/filter, data table, checkout flow)
2. For each pattern, identify 2-3 products known for excellent implementations:
   - Use web search: "[pattern] best UX examples" or "[pattern] design inspiration"
   - Prefer products in the same domain or serving similar personas
   - Note what makes their design work — not just how it looks, but why the choices serve the user
3. Synthesize benchmark findings into the direction:
   - "For your dashboard pattern, Stripe uses dense but scannable cards with clear hierarchy — this works because their users are data-oriented"
   - "For your onboarding, Linear uses progressive disclosure with minimal screens — this works because their users are technical and impatient"
4. Let benchmarks inform each of the 6 dimensions below — typography, color, spacing decisions should reference what works in production, not abstract theory

**The goal is NOT to copy.** The goal is to make design choices informed by evidence of what works for similar patterns and audiences, then adapt to the specific project's vision and principles.

---

## The 6 Dimensions

Every design direction covers these 6 dimensions. Each is derived from upstream context, not chosen in isolation.

### 1. Aesthetic Tone

**Derived from:** why.json (vision, approach) + product-discovery.json (personas)

| Vision Keywords | Suggests |
|----------------|----------|
| Professional, enterprise, trust | Refined, structured, restrained palette |
| Creative, playful, young | Bold, asymmetric, saturated colors |
| Technical, developer, tools | Dense, monospace-friendly, dark-mode-first |
| Warm, personal, community | Organic, rounded, warm neutrals |
| Luxury, premium, exclusive | Minimal, high-contrast, serif typography |
| Educational, accessible, inclusive | Clear, high-contrast, generous spacing |

**Don't:** Pick "clean and modern" — that's the default Claude gives without guidance. Be specific.

### 2. Typography Direction

**Derived from:** aesthetic tone + persona accessibility needs + brand personality

| Tone | Heading Font Style | Body Font Style |
|------|-------------------|-----------------|
| Refined/luxury | Serif (editorial) | Clean sans-serif |
| Playful/creative | Display/decorative | Rounded sans-serif |
| Technical/developer | Monospace or geometric sans | System or clean sans |
| Warm/personal | Humanist sans-serif | Humanist sans-serif |
| Bold/startup | Heavy geometric sans | Light geometric sans |

**Rules:**
- Always pair a distinctive heading font with a readable body font
- Never default to Inter, Roboto, Arial, or system fonts for headings
- Body font can be more conservative — readability wins
- Check Google Fonts or Fontsource for free options that match the direction
- Minimum body text: 16px (1rem). Never smaller for primary content.

### 3. Color Philosophy

**Derived from:** brand identity + emotional targets from personas + accessibility

| Emotional Target | Color Strategy |
|-----------------|---------------|
| Trust & stability | Blue-anchored, conservative palette |
| Energy & action | Warm accents (orange, red), high saturation |
| Calm & wellness | Green/teal anchored, muted tones |
| Creativity & expression | Purple/magenta, diverse accent palette |
| Neutrality & professionalism | Monochrome + single strong accent |

**Rules:**
- One dominant brand color + one sharp accent outperforms evenly distributed palettes
- Semantic colors (error, success, warning, info) are functional — don't let brand override them
- Ensure WCAG AA contrast (4.5:1 for text, 3:1 for large text/UI) at minimum
- Define both light and dark mode tokens from the start, even if only shipping one initially

### 4. Spacing & Density

**Derived from:** product type + primary persona + platform

| Product Type | Density |
|-------------|---------|
| Consumer app (mobile-first) | Generous — large touch targets, breathing room |
| Data dashboard | Dense — information-rich, compact controls |
| Content/editorial | Balanced — wide reading column, generous margins |
| Developer tool | Dense — maximize code/data visibility |
| E-commerce | Mixed — generous hero, dense product grids |

**Base unit:** 4px (0.25rem) for most projects. Data-heavy UIs may use 2px or 8px.

### 5. Motion Principles

**Derived from:** UX patterns + interaction style + persona expectations

| UX Pattern | Motion Approach |
|-----------|----------------|
| Dashboard with real-time data | Subtle transitions, no blocking animations |
| Onboarding/wizard flow | Directional slides, progress reveals |
| Content browsing | Scroll-triggered reveals, parallax hints |
| Form-heavy workflow | Micro-feedback only (success checks, error shakes) |
| Marketing/landing | Orchestrated load sequence, scroll storytelling |

**Rules:**
- CSS-only animations for HTML projects; Motion/Framer Motion for React
- One well-orchestrated page load > scattered micro-interactions
- Respect `prefers-reduced-motion` — always provide a reduced/off fallback
- Animation duration: 150-300ms for micro, 300-500ms for transitions, 500-1000ms for reveals

### 6. Accessibility Stance

**Derived from:** personas + legal requirements + ethical position

| Persona Need | Minimum Standard |
|-------------|-----------------|
| General consumer | WCAG AA (4.5:1 contrast, keyboard nav) |
| Education/government | WCAG AA, ideally AAA for text |
| Users with disabilities identified in personas | WCAG AAA + specific accommodations |
| Developer/power user | Keyboard-first, screen reader support |

**Always include:**
- Focus indicators on all interactive elements
- Sufficient color contrast (don't rely on color alone)
- Semantic HTML structure
- alt text strategy (not just "add alt text" — define when to use decorative vs. descriptive)
- Touch target minimum: 44x44px on mobile

---

## Anti-Patterns (from Anthropic's frontend-design guidance)

**Never default to:**
- Inter, Roboto, Arial, or system fonts for display/heading use
- Purple gradients on white backgrounds (cliched AI aesthetic)
- Predictable centered-content layouts with no spatial tension
- Cookie-cutter component patterns with no context-specific character
- Space Grotesk (overused in AI/tech products)

**Instead:**
- Commit to a specific aesthetic and execute it with precision
- Bold maximalism and refined minimalism both work — the key is intentionality
- Every project should look different from the last
- Match implementation complexity to the vision — maximalist needs elaborate code, minimalist needs restraint

---

## DIRECTION.md Template

```markdown
# Design Direction — {Project Name}

**Generated by:** /shipkit-design-system
**Date:** YYYY-MM-DD
**Based on:** why.json, product-discovery.json, [other sources]

## Aesthetic Tone
{Specific tone description, derived from vision and audience.}

## Typography
- **Heading:** {Font name} — {why this font}
- **Body:** {Font name} — {why this font}
- **Scale:** {description of type hierarchy}

## Color Philosophy
- **Brand:** {dominant color + accent strategy}
- **Mood:** {emotional target}
- **Contrast:** {WCAG level and rationale}

## Spacing & Density
- **Approach:** {generous/balanced/dense}
- **Base unit:** {4px/8px}
- **Rationale:** {derived from product type and persona}

## Motion
- **Approach:** {orchestrated/subtle/minimal}
- **Key moments:** {where animation adds value}
- **Reduced motion:** {fallback strategy}

## Accessibility
- **Standard:** {WCAG AA/AAA}
- **Specific accommodations:** {from persona needs}
- **Focus strategy:** {visible focus indicators approach}
```
