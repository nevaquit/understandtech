---
name: understandtech-platform
description: >-
  Builds understandtech.app — an AI-augmented Moodle 4.5 certification training
  platform with deconstructed edge-native architecture (Cloudflare + Azure).
  Applies white paper v2.0 architecture, playbook build phases, custom Moodle
  plugins, Cloudflare AI Gateway Worker, Bicep infrastructure, and CI/CD.
  Use when working on understandtech, understandtech.app, Moodle plugins
  (local_certmaster, local_aitutor, theme_understandtech), Cloudflare Workers,
  Azure Bicep, or playbook phases 0-7.
paths:
  - "**/*"
---

# understandtech.app Platform

AI-augmented certification training platform on Moodle 4.5 LTS with Cloudflare edge + Azure origin.

## Before you start

1. Read `@.cursorrules` for non-negotiable constraints
2. Read `@docs/white-paper.md` for architecture decisions
3. Read `@docs/playbook.md` for the current build phase and Cursor prompts
4. Check `@docs/phase-0-toolchain.md` for tooling gaps

## Workflow

1. **Identify phase** — Which playbook phase (0–7) does this task belong to?
2. **Load context** — Reference the matching playbook prompt block and acceptance criteria
3. **Respect scope** — Only custom plugins and infra in this repo; never Moodle core
4. **Security first** — No secrets in source; AI tutor must not leak answers/flags
5. **Verify** — Run acceptance criteria from the playbook prompt before marking done

## Architecture summary

| Layer | Technology |
|-------|------------|
| LMS | Moodle 4.5 LTS, PHP 8.3, Nginx + PHP-FPM |
| Data | Azure PostgreSQL 16 via PgBouncer, Redis cache |
| Edge | Cloudflare WAF, Stream, Workers, AI Gateway |
| AI | LLM calls only via `cloudflare-worker/ai-gateway/` |
| CI/CD | GitHub Actions + self-hosted runner on Azure VM |

## Repository map

| Path | Contents |
|------|----------|
| `moodle-plugins/` | theme, local, mod, block plugins |
| `cloudflare-worker/ai-gateway/` | TypeScript AI Gateway Worker |
| `infrastructure/` | Bicep, Nginx, PHP-FPM, PgBouncer, runner |
| `docs/white-paper.md` | Full architecture white paper |
| `docs/playbook.md` | Phase-by-phase Cursor prompts |
| `tests/` | E2E and integration tests |

## Playbook phases

| Phase | Goal | Doc section |
|-------|------|-------------|
| 0 | Toolchain setup | `docs/phase-0-toolchain.md` |
| 1 | Monorepo bootstrap | Complete |
| 2 | Azure Bicep provisioning | playbook Phase 2 |
| 3 | Moodle plugin development | playbook Phase 3 |
| 4 | Cloudflare AI Gateway Worker | playbook Phase 4 |
| 5 | CI/CD + runner | playbook Phase 5 |
| 6 | E2E testing | playbook Phase 6 |
| 7 | Production deploy | playbook Phase 7 |

## Critical rules

- **Never** commit Moodle core files
- **Never** hardcode secrets — Azure Key Vault only
- **Never** call Anthropic/OpenAI from Moodle PHP
- **Never** expose raw Cloudflare Stream IDs — use signed JWTs (60s expiry)
- **Always** follow Moodle Coding Style for PHP
- **Always** use `$DB` API, `moodleform`, and `get_string()`

## Additional resources

- Architecture deep-dive: [architecture.md](architecture.md)
- Playbook quick reference: [playbook-phases.md](playbook-phases.md)
- Moodle development: use personal skill `/moodle-development` for plugin patterns
