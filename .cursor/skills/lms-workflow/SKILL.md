---
name: lms-workflow
description: >-
  Orchestrates understandtech.app EdTech work — loads lms-enterprise-ai-master-skill
  with platform and domain skills for SCORM, xAPI, LTI 1.3, multi-tenant LMS schemas,
  AI tutoring, grading queues, FERPA/GDPR PII masking, and learning analytics.
  Use as /lms-workflow or alongside /understandtech-platform for any LMS or AI task
  in this repo.
paths:
  - "**/*"
---

# LMS Workflow Orchestrator (understandtech.app)

**Always apply together with** `/understandtech-platform` and the enterprise patterns in [lms-enterprise-ai-master-skill](../lms-enterprise-ai-master-skill/SKILL.md).

## Skill stack by task

| Task area | Also load |
|-----------|-----------|
| Platform scope, playbook phases | `/understandtech-platform` |
| Enterprise LMS + AI orchestration | `/lms-enterprise-ai-master-skill` (this file's parent patterns) |
| Moodle PHP plugins | `moodle-core-php-engineering`, `moodle-development` |
| AI tutor, RAG, prompts | `ai-intelligent-systems` |
| Cloudflare Workers, SSE, JWT | `edge-serverless-orchestration` |
| Azure Bicep, Nginx, CI/CD | `iac-async-cloud-devops` |
| Theme, charts, gamification UX | `mathematical-ui-design-engineering` |

## Blueprint → understandtech monorepo

| Enterprise blueprint | This repository |
|----------------------|-----------------|
| `src/core/middleware/auth.ts` | Moodle auth + `local_aitutor` JWT issuance |
| `src/core/tenants/scoping.ts` | Course/category isolation; `tenant_id` on custom plugin tables |
| `src/modules/ingestion/` | Moodle backup/restore, `mod_scorm`, content import |
| `src/modules/tracking/` | Moodle Events API, analytics, future xAPI statements |
| `src/modules/assessment/` | `mod_quiz`, custom `qbehaviour_*`, server-side grading state |
| `src/ai/queue/` | Moodle adhoc/scheduled tasks, GitHub Actions self-hosted runner |
| `src/ai/gateway/` | `cloudflare-worker/ai-gateway/` (all LLM traffic) |
| `src/ai/agents/` | `prompts.ts`, RAG — see `ai-intelligent-systems` |

## Non-negotiables (from `.cursorrules`)

- Moodle PHP **never** calls Anthropic/OpenAI directly
- AI tutor **must not** leak assessment answers, lab flags, or quiz solutions
- No secrets in source; multi-tenant queries **must** filter by tenant/course scope
- Deterministic metrics and grades are **server-side only**

Full enterprise LMS + AI patterns: [lms-enterprise-ai-master-skill/SKILL.md](../lms-enterprise-ai-master-skill/SKILL.md)
