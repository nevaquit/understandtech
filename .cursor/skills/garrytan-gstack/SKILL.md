---
name: garrytan-gstack
description: >-
  gstack design workflow from garrytan/gstack — design consultation (DESIGN.md),
  plan-time design review (audit only), and design-review (audit + fix loop with
  before/after screenshots). Use as /garrytan-gstack for theme/UI polish on
  understandtech.app.
paths:
  - "**/*"
---

# gstack Design (orchestrator)

Upstream: [garrytan/gstack](https://github.com/garrytan/gstack)

Runtime vendored at `.cursor/skills/gstack/` (bin, browse, design, setup).

## Skills in this repo

| Skill | Invoke | When |
|-------|--------|------|
| Design consultation | `/garrytan-gstack-design-consultation` | Create or refine `DESIGN.md` / design system |
| Plan design review | `/garrytan-gstack-plan-design-review` | **Before** implementation — audit only, no code changes |
| Design review (fix loop) | `/garrytan-gstack-design-review` | **After** UI exists — audit, fix in code, verify with screenshots |

## Typical flow

```text
/design-consultation → DESIGN.md
/plan-design-review  → approved visual direction
(implement theme/UI)
/design-review       → polish + atomic fix commits
```

## understandtech.app

| Context | Notes |
|---------|-------|
| Theme | `moodle-plugins/theme_understandtech/` — navy `#0B1F3A`, gold `#C9A227`, teal `#1A8A7D` |
| Frontend skill | `mathematical-ui-design-engineering` for Chart.js, clamp(), Mustache |
| Platform rules | `.cursorrules` — do not swap stack |

## Browser tooling

`/garrytan-gstack-design-review` uses gstack **browse** for snapshots and compare boards. If browse is not built:

1. Install [bun](https://bun.sh) if missing
2. Run from repo root: `cd .cursor/skills/gstack && bash setup` (or let the skill prompt you)

On Windows, run setup via **Git Bash**. Re-run setup after `git pull` on gstack (file-copy install).

**Alternative:** Cursor's built-in browser MCP can supplement live URL checks when browse is unavailable.

## Related skills

- `/forrestchang-andrej-karpathy-skills` — surgical diffs during fixes
- `/mathematical-ui-design-engineering` — Moodle theme patterns
- `/voltagent-awesome-agent-skills` — discover more design/perf skills
