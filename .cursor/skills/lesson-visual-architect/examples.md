# Lesson Visual Architect — Examples

Canonical implementations in this repository. Read these files before authoring new visuals.

## CIA Triad (SVG + cards)

**File:** `content/security-plus/lessons/sy701_1_2.html` (lines 8–55)

- `ut-lesson-diagram ut-infographic` wrapper
- Inline SVG triangle with branded gradient
- `cia-triad` / `cia-element` supporting cards
- Placed immediately under `Visual Representation: CIA Triad`

**Snippet:** `content/security-plus/snippets/cia-triad-infographic.html`

## AAA process flow

**File:** `content/security-plus/lessons/sy701_1_2.html` (~line 146)

- `flow-diagram` with three `flow-step` blocks
- `flow-arrow` separators between steps
- Under `Visual Representation: AAA Process Flow`

## PKI certificate flow

**File:** `content/security-plus/lessons/sy701_1_4.html` (lines 8–38)

- Four-step horizontal flow with arrows
- Reference for multi-step security processes

## Symmetric vs asymmetric comparison

**File:** `content/security-plus/lessons/sy701_1_4.html` (~line 98)

- `concept-grid` with two `concept-item` cards
- Side-by-side comparison layout

## Security controls matrix

**File:** `content/security-plus/lessons/sy701_1_1.html` (~line 102)

- `controls-matrix` with multiple `control-card` entries
- Large taxonomy grid pattern

## Threat actor landscape

**File:** `content/security-plus/lessons/sy701_2_1.html` (~line 273)

- `threat-actors` container with `threat-actor` profile cards

## Vulnerability taxonomy

**File:** `content/security-plus/lessons/sy701_2_3.html` (~line 225)

- `concept-grid` under condensed intro paragraph
- Demonstrates fixing heading-to-diagram distance

## Invocation

In Cursor chat:

```
/lesson-visual-architect Add a Zero Trust pillar diagram to sy701_3_1
```

or attach the skill when auditing SEC701 visuals.
