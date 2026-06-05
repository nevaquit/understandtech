# Playbook Phase Quick Reference

Full prompts with acceptance criteria: `docs/playbook.md`

## Phase 0 — Prerequisites

Install: PHP 8.3, Composer, Docker, Azure CLI, Bicep, Wrangler, jq.  
Audit: `docs/phase-0-toolchain.md`

## Phase 1 — Repository bootstrap ✅

- Monorepo scaffolding
- `.cursorrules`, `.editorconfig`, `.gitignore`
- README, CONTRIBUTING, LICENSE
- Project Cursor skill

## Phase 2 — Cloud infrastructure ✅

Prompts 2.1–2.4 complete. See `docs/phase-2-infrastructure.md`.

- Azure Bicep: `infrastructure/bicep/`
- Cloud-init: `infrastructure/runner/cloud-init.yaml`
- Nginx / PHP-FPM / PgBouncer configs in `infrastructure/`

## Phase 3 — Moodle plugins

Prompts 3.1–3.4:

- `theme_understandtech`
- `local_certmaster`
- `local_aitutor`
- Batch: `local_aigrading`, `mod_ctfflag`, blocks

## Phase 4 — Cloudflare Worker

Prompt 4.2: AI Gateway Worker (TypeScript)

## Phase 5 — CI/CD

Prompt 5.1: GitHub Actions deployment workflow + self-hosted runner

## Phase 6 — Testing

Prompts 6.1–6.2: Playwright E2E + deployment smoke tests

## Phase 7 — Production

Prompt 7.3: Post-deployment validation checklist

## Prompt templates (appendix)

- Add plugin feature
- Refactor
- Write tests
- Debug
- Generate docs

When running a prompt: copy the full block, include `@.cursorrules`, verify acceptance criteria, commit before multi-file agent runs.
