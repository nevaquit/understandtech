---
name: taskade-awesome-vibe-coding
description: >-
  Curated vibe-coding playbook from taskade/awesome-vibe-coding — prompt patterns,
  harness engineering, context management, tool selection, and production workflows
  for AI-assisted development in Cursor. Use when choosing tools, improving agent
  setup, prompt strategy, session hygiene, or "how should I vibe-code this?"
---

# Awesome Vibe Coding (Taskade)

Upstream catalog: [taskade/awesome-vibe-coding](https://github.com/taskade/awesome-vibe-coding)  
Full list (285+ tools): [reference/README.md](reference/README.md)

## This repository's stack

| Layer | Tool | Notes |
|-------|------|-------|
| IDE | **Cursor** | Primary agent IDE; rules in `.cursorrules`, skills in `.cursor/skills/` |
| Engineering skills | `/addyosmani-agent-skills` | Spec, TDD, review, ship |
| Platform | `/understandtech-platform` | Moodle 4.5, Azure, Cloudflare — non-negotiable constraints |
| MCP | Cloudflare, browser | See repo MCP config |

When vibe-coding here, **steering files beat mega-prompts**. Read `.cursorrules`, `AGENTS.md`, and `docs/playbook.md` before large changes.

## Harness engineering (apply always)

1. **Fewer tools > more tools** — prefer focused skills and native repo patterns over tool sprawl.
2. **File system as memory** — `AGENTS.md`, `DISCOVERIES.md` (if present), skills, and docs persist context.
3. **Verify, don't trust** — tests, diffs, health checks; pair with `/addyosmani-test-driven-development`.
4. **Progressive disclosure** — load skills and files on demand; do not dump the whole README into context.
5. **Simplify relentlessly** — minimal diffs; match existing conventions.

## Prompting patterns

| Technique | When |
|-----------|------|
| **Progressive prompting** | Architecture first, then components — never one-shot entire features |
| **Function-signature method** | Define types/interfaces, then implement bodies |
| **Test-first prompting** | Tests as spec before implementation |
| **Doc embedding** | Paste official docs (Moodle 4.5, Cloudflare Workers) into task context |
| **Pseudocode-first** | Step logic before code for non-trivial flows |

## Session hygiene

- **Checkpoint with git** before large agent edits.
- **Reset session** when context rot appears (repeated mistakes, lost track of files).
- **Split complex tasks** — one clear objective per agent turn.
- **Review every diff** — especially auth, enrolment, AI tutor, and deploy scripts.
- **Lock dependencies** — do not swap stack items from `.cursorrules` (Moodle, Azure, Cloudflare).

## Production vs exploration

| Mode | When | Behavior |
|------|------|----------|
| **Exploration** | Spikes, prototypes | More freedom; discard after |
| **Production** | This repo | Spec → small slices → tests → review → deploy workflow |

For production work in understandtech.app, use **Production mode** and `/addyosmani-spec-driven-development` for non-trivial features.

## Tool router (external — not this repo's stack)

Use [reference/README.md](reference/README.md) when the user asks about tools **outside** this monorepo:

- **IDE agent:** Cursor, Windsurf, Cline, Aider
- **CLI agents:** Claude Code, OpenCode, Gemini CLI
- **App builders:** Bolt, Lovable, v0 (export code) vs full living systems
- **MCP servers:** Context7, Playwright, Supabase MCP, etc.
- **Steering templates:** `cursor-rules`, Caliber, AGENTS.md patterns

Do **not** suggest replacing Moodle/Azure/Cloudflare with alternatives from the list.

## Practical workflow (understandtech)

```
Intent → read .cursorrules + playbook phase
      → load domain skill (Moodle / Workers / Bicep)
      → load addyosmani phase skill (spec / TDD / review)
      → small vertical slice + verify
      → CI/deploy per docs/playbook.md
```

## Cross-references

- Engineering rigor: `/addyosmani-agent-skills`
- Platform constraints: `/understandtech-platform`, `/lms-workflow`
- Cursor product docs: https://docs.cursor.com/

## Glossary (quick)

| Term | Meaning |
|------|---------|
| **Vibe coding** | Build via natural-language intent; AI generates and iterates code |
| **Harness engineering** | Scaffolding around models — rules, skills, tests, verification |
| **Steering files** | `.cursorrules`, `AGENTS.md`, skill files |
| **Context engineering** | What the agent sees — budget context deliberately |
| **Middle loop** | Orchestrating agents between writing code and shipping |

Full glossary: [reference/README.md#glossary](reference/README.md)
