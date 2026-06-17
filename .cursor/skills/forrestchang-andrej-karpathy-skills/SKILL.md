---
name: forrestchang-andrej-karpathy-skills
description: >-
  Andrej Karpathy-inspired behavioral guidelines — think before coding,
  simplicity first, surgical changes, and goal-driven execution with verifiable
  success criteria. Use as /forrestchang-andrej-karpathy-skills or when writing,
  reviewing, or refactoring code to avoid LLM overcomplication.
paths:
  - "**/*"
---

# Andrej Karpathy Skills (orchestrator)

Upstream: [forrestchang/andrej-karpathy-skills](https://github.com/forrestchang/andrej-karpathy-skills).

## Active in this repo

The four principles are **always applied** via [`.cursor/rules/karpathy-guidelines.mdc`](../../rules/karpathy-guidelines.mdc) (`alwaysApply: true`).

Invoke the skill explicitly when you want a focused refresh:

- [../forrestchang-karpathy-guidelines/SKILL.md](../forrestchang-karpathy-guidelines/SKILL.md)

## The four principles

| # | Principle | Addresses |
|---|-----------|-----------|
| 1 | **Think Before Coding** | Wrong assumptions, hidden confusion, missing tradeoffs |
| 2 | **Simplicity First** | Overcomplication, bloated abstractions |
| 3 | **Surgical Changes** | Orthogonal edits, touching code you shouldn't |
| 4 | **Goal-Driven Execution** | Tests-first, verifiable success criteria |

## Examples

Real before/after patterns: [references/EXAMPLES.md](references/EXAMPLES.md)

## understandtech.app stack

These guidelines complement — do not replace — project constraints:

| Also load | When |
|-----------|------|
| `.cursorrules`, `AGENTS.md` | Stack, secrets, Moodle/Cloudflare rules |
| `/understandtech-platform` | Architecture and playbook |
| `/obra-superpowers` | Brainstorming → plan → TDD workflow |
| `/addyosmani-agent-skills` | Spec, review, ship lifecycle |

## Tradeoff

Biased toward **caution over speed**. For trivial one-liners, use judgment — not every change needs full rigor.
