---
name: addyosmani-agent-skills
description: >-
  Production-grade engineering skills from addyosmani/agent-skills — spec-driven
  development, TDD, incremental implementation, code review, security, performance,
  shipping, and CI/CD. Use as /addyosmani-agent-skills or when starting any
  non-trivial feature, bug fix, refactor, or launch task in this repo.
paths:
  - "**/*"
---

# Addy Osmani Agent Skills (orchestrator)

Upstream: [addyosmani/agent-skills](https://github.com/addyosmani/agent-skills) — installed under `.cursor/skills/addyosmani-*`.

## Discovery map

| Phase | Skill directory | When |
|-------|-----------------|------|
| Define | `addyosmani-interview-me` | Unclear requirements |
| Define | `addyosmani-idea-refine` | Vague concept, need variants |
| Define | `addyosmani-spec-driven-development` | New feature or behavior change |
| Plan | `addyosmani-planning-and-task-breakdown` | Spec exists, need tasks |
| Build | `addyosmani-incremental-implementation` | Implement in vertical slices |
| Build | `addyosmani-frontend-ui-engineering` | UI / theme / Mustache / SCSS |
| Build | `addyosmani-api-and-interface-design` | APIs, web services, Worker routes |
| Build | `addyosmani-context-engineering` | Large context, many files |
| Build | `addyosmani-source-driven-development` | Must match official docs |
| Build | `addyosmani-doubt-driven-development` | High-stakes or unfamiliar code |
| Verify | `addyosmani-test-driven-development` | Logic, bugs, behavior changes |
| Verify | `addyosmani-browser-testing-with-devtools` | Browser/runtime verification |
| Verify | `addyosmani-debugging-and-error-recovery` | Failures, regressions |
| Review | `addyosmani-code-review-and-quality` | Pre-merge review |
| Review | `addyosmani-code-simplification` | Reduce complexity |
| Review | `addyosmani-security-and-hardening` | Auth, input, secrets, OWASP |
| Review | `addyosmani-performance-optimization` | Latency, Core Web Vitals |
| Ship | `addyosmani-git-workflow-and-versioning` | Commits, branches, PRs |
| Ship | `addyosmani-ci-cd-and-automation` | GitHub Actions, deploy pipelines |
| Ship | `addyosmani-shipping-and-launch` | Production deploy checklist |
| Ship | `addyosmani-documentation-and-adrs` | ADRs, architecture docs |
| Ship | `addyosmani-observability-and-instrumentation` | Logs, metrics, alerts |
| Ship | `addyosmani-deprecation-and-migration` | Retire old paths safely |

Full meta-skill (lifecycle + rules): read [../addyosmani-using-agent-skills/SKILL.md](../addyosmani-using-agent-skills/SKILL.md).

## Personas (review fan-out)

Use with parallel review before merge:

- [agents/code-reviewer.md](agents/code-reviewer.md)
- [agents/security-auditor.md](agents/security-auditor.md)
- [agents/test-engineer.md](agents/test-engineer.md)
- [agents/web-performance-auditor.md](agents/web-performance-auditor.md)

## Checklists

- [references/security-checklist.md](references/security-checklist.md)
- [references/performance-checklist.md](references/performance-checklist.md)
- [references/accessibility-checklist.md](references/accessibility-checklist.md)

## understandtech.app stack

Combine with project skills — do not replace `.cursorrules` or Moodle/Cloudflare constraints:

| Task | Also load |
|------|-----------|
| Platform / playbook | `/understandtech-platform` |
| LMS + AI enterprise patterns | `/lms-workflow`, `/lms-enterprise-ai-master-skill` |
| Moodle PHP | `moodle-core-php-engineering`, `moodle-development` |
| Cloudflare Workers | `edge-serverless-orchestration` |
| Azure / CI | `iac-async-cloud-devops` |
| Theme / charts | `mathematical-ui-design-engineering` |

## Rules

1. Check for an applicable `addyosmani-*` skill before non-trivial work.
2. Follow skill steps in order; do not skip verification.
3. Surface assumptions and push back on risky approaches.
4. Prefer minimal scope — match existing repo conventions.
