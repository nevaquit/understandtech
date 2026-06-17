---
name: obra-superpowers
description: >-
  Composable agent methodology from obra/superpowers — brainstorming before
  creative work, spec/plan writing, subagent-driven implementation, TDD,
  systematic debugging, and verification-before-completion. Use as
  /obra-superpowers or when starting features, fixes, or multi-step engineering.
paths:
  - "**/*"
---

# Superpowers (orchestrator)

Upstream: [obra/superpowers](https://github.com/obra/superpowers) — installed under `.cursor/skills/obra-superpowers-*`.

Session start injects `using-superpowers` via `.cursor/hooks.json`.

## Workflow map

| Phase | Skill directory | When |
|-------|-----------------|------|
| Bootstrap | `obra-superpowers-using-superpowers` | Every session; skill discovery rules |
| Design | `obra-superpowers-brainstorming` | **Required** before any creative/feature work |
| Plan | `obra-superpowers-writing-plans` | After approved design; implementation plan |
| Execute | `obra-superpowers-executing-plans` | Batch execution with checkpoints |
| Execute | `obra-superpowers-subagent-driven-development` | Subagent per task with review gates |
| Parallel | `obra-superpowers-dispatching-parallel-agents` | Independent workstreams |
| Verify | `obra-superpowers-test-driven-development` | Logic and behavior changes |
| Verify | `obra-superpowers-verification-before-completion` | Before claiming done |
| Debug | `obra-superpowers-systematic-debugging` | Failures, regressions, unknown causes |
| Review | `obra-superpowers-requesting-code-review` | Pre-merge review requests |
| Review | `obra-superpowers-receiving-code-review` | Acting on review feedback |
| Ship | `obra-superpowers-finishing-a-development-branch` | Merge/PR/ cleanup decisions |
| Meta | `obra-superpowers-writing-skills` | Authoring or improving skills |
| Isolation | `obra-superpowers-using-git-worktrees` | Feature work in isolated worktrees |

## Typical flow

```text
brainstorming → writing-plans → subagent-driven-development (or executing-plans)
  → test-driven-development → verification-before-completion → finishing-a-development-branch
```

## understandtech.app stack

Combine with project skills — Superpowers does not override `.cursorrules` or stack constraints:

| Task | Also load |
|------|-----------|
| Platform / playbook | `/understandtech-platform` |
| LMS + AI enterprise | `/lms-workflow`, `/lms-enterprise-ai-master-skill` |
| General engineering | `/addyosmani-agent-skills` |
| Vibe-coding patterns | `/taskade-awesome-vibe-coding` |
| Moodle PHP | `moodle-core-php-engineering`, `moodle-development` |
| Cloudflare Workers | `edge-serverless-orchestration` |
| Azure / CI | `iac-async-cloud-devops` |

Design docs from brainstorming land in `docs/superpowers/specs/` when this workflow is active.

## Rules

1. Invoke `obra-superpowers-brainstorming` before implementation or creative changes.
2. Do not skip verification skills before marking work complete.
3. User instructions in `AGENTS.md`, `.cursorrules`, and direct requests override skill defaults.
4. Prefer minimal scope — match existing repo conventions.
