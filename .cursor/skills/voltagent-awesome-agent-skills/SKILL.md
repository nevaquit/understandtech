---
name: voltagent-awesome-agent-skills
description: >-
  Curated catalog of 1400+ official and community Agent Skills from
  VoltAgent/awesome-agent-skills — discover, evaluate, and install skills for
  Cloudflare, Azure, PostgreSQL, security, and more. Use when choosing external
  skills, browsing officialskills.sh listings, or routing to upstream skill repos.
---

# Awesome Agent Skills (VoltAgent)

Upstream catalog: [VoltAgent/awesome-agent-skills](https://github.com/VoltAgent/awesome-agent-skills)  
Full list: [reference/README.md](reference/README.md) · Browse: [officialskills.sh](https://officialskills.sh)

## What this is

A **curated index**, not a bundled skill pack. Each entry links to an upstream repo or [officialskills.sh](https://officialskills.sh). Install individual skills on demand — do not load the entire catalog into context.

## Install a skill from the catalog

1. Find the skill in [reference/README.md](reference/README.md) or on officialskills.sh.
2. Clone or copy the skill directory into:
   - **Project:** `.cursor/skills/<vendor>-<skill-name>/`
   - **Global:** `~/.cursor/skills/<vendor>-<skill-name>/`
3. Ensure `SKILL.md` has valid frontmatter (`name`, `description`).
4. Add a hub entry to `AGENTS.md` if the skill is repo-critical.
5. **Review source** before production use (see Security below).

Cursor paths: [Cursor Skills docs](https://cursor.com/docs/context/skills)

## Security

Skills are curated, **not audited**. Before installing:

- Read the skill source (prompt injections, tool scope, shell commands)
- Prefer official team skills (Anthropic, Cloudflare, Microsoft, Trail of Bits, etc.)
- Use [Snyk agent-scan](https://github.com/snyk/agent-scan) or [Agent Trust Hub](https://ai.gendigital.com/agent-trust-hub) when evaluating unknown skills

## understandtech.app — skill router

Use **project skills first**; pull from this catalog only when you need upstream-specific depth.

| Need | Already in this repo | Also from catalog |
|------|---------------------|-------------------|
| Platform constraints | `/understandtech-platform`, `.cursorrules` | — |
| Engineering lifecycle | `/addyosmani-agent-skills` | [addyosmani/best-practices](https://officialskills.sh/addyosmani/skills/best-practices) |
| Brainstorm → ship | `/obra-superpowers` | — |
| Coding discipline | `/forrestchang-andrej-karpathy-skills` | — |
| Vibe-coding / harness | `/taskade-awesome-vibe-coding` | — |
| Moodle PHP | `moodle-core-php-engineering`, `moodle-development` | WordPress plugin skills (PHP patterns only) |
| Cloudflare Workers | `edge-serverless-orchestration`, `cloudflare-worker/AGENTS.md` | [cloudflare/workers-best-practices](https://officialskills.sh/cloudflare/skills/workers-best-practices), [cloudflare/wrangler](https://officialskills.sh/cloudflare/skills/wrangler), [cloudflare/cloudflare](https://officialskills.sh/cloudflare/skills/cloudflare) |
| Azure / Bicep | `iac-async-cloud-devops` | [microsoft/cloud-solution-architect](https://officialskills.sh/microsoft/skills/cloud-solution-architect), [hashicorp/azure-verified-modules](https://officialskills.sh/hashicorp/skills/azure-verified-modules) |
| PostgreSQL | `cin12211-orca-q-postgres-expert`, Azure PG skills | [supabase/postgres-best-practices](https://officialskills.sh/supabase/skills/postgres-best-practices) |
| Redis | `affaan-m-ecc-redis-patterns` | [redis/redis-development](https://github.com/redis/agent-skills/tree/main/skills/redis-development) |
| PHP observability | — | [getsentry/sentry-php-sdk](https://officialskills.sh/getsentry/skills/sentry-php-sdk) |
| Security review | `/addyosmani-security-and-hardening` | [trailofbits/differential-review](https://officialskills.sh/trailofbits/skills/differential-review), [openai/security-threat-model](https://officialskills.sh/openai/skills/security-threat-model) |
| Web perf | `davila7-claude-code-templates-web-performance-optimization` | [cloudflare/web-perf](https://officialskills.sh/cloudflare/skills/web-perf) |
| AI / RAG | `ai-intelligent-systems` | Hugging Face skills in catalog |

Do **not** suggest stack swaps (Moodle → Firebase, Azure → Vercel, etc.) based on catalog entries.

## Discovery workflow

```
User need → check installed .cursor/skills/ and AGENTS.md
         → grep reference/README.md for vendor/keyword
         → open officialskills.sh link or upstream GitHub
         → install single skill → verify → document in AGENTS.md
```

## Quality bar (from catalog)

When authoring or evaluating skills:

- Third-person `description` with specific trigger keywords
- Progressive disclosure — body under ~500 lines; load large docs on demand
- No absolute paths; scoped tools (not `"tools": ["*"]`)

Full criteria: [reference/README.md#skill-quality-standards](reference/README.md)

## Cross-references

- Engineering rigor: `/addyosmani-agent-skills`
- Platform: `/understandtech-platform`, `/lms-workflow`
- Agent methodology: `/obra-superpowers`
- Cursor product: https://cursor.com/docs/context/skills
