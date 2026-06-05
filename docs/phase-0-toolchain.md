# Phase 0 — Local Toolchain Audit

Audit date: 2026-06-05  
Repository: `understandtech` (understandtech.app platform monorepo)

## Installed and ready

| Tool | Required | Status | Version |
|------|----------|--------|---------|
| Git | 2.40+ | OK | 2.53.0.windows.1 |
| Node.js | 20 LTS+ | OK | v22.22.0 |
| npm | (with Node) | OK | 11.6.2 |
| GitHub CLI | 2.40+ | OK | 2.92.0 |
| Azure CLI | 2.60+ | OK | 2.87.0 |
| Bicep CLI | Latest | OK | 0.43.8 |
| Cursor | Latest | OK | (IDE) |

## Missing — install before Phase 2+

| Tool | Required | Status | Install notes |
|------|----------|--------|---------------|
| PHP CLI | 8.3.x | Missing | [windows.php.net](https://windows.php.net/download/) — enable `openssl`, `curl`, `mbstring`, `intl` |
| Composer | 2.6+ | Missing | [getcomposer.org](https://getcomposer.org/download/) |
| Docker Desktop | Latest | Missing | [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop/) |
| Docker Compose | v2+ | Missing | Bundled with Docker Desktop |
| Wrangler | 3+ | Missing | `npm install -g wrangler` |
| jq | 1.6+ | Missing | `winget install jqlang.jq` |

## Cursor IDE configuration

- [ ] Agent Mode enabled (Settings → Features)
- [ ] PHP Intelephense extension installed
- [ ] Bicep extension installed
- [x] Workspace `.cursorrules` configured (Phase 1)
- [x] Project skill at `.cursor/skills/understandtech-platform/`

## Account prerequisites (manual)

Confirm access before Phase 2:

| Service | Purpose |
|---------|---------|
| GitHub (private repo) | Monorepo + CI/CD |
| Microsoft Azure | VM, Postgres, Redis, Key Vault |
| Cloudflare (Workers Paid) | AI Gateway Worker + Stream |
| Anthropic / OpenAI | AI Tutor LLM providers |
| Stripe | Subscription billing |
| Postmark | Transactional email |

## Phase 0 verdict

**Partially complete.** Git, Node, npm, GitHub CLI, Azure CLI, and Bicep are ready for Phase 2 deployment. Install PHP, Composer, and Docker before Phase 3 (Moodle plugin development). Install Wrangler before Phase 4.

Next step: Phase 1 scaffolding (complete) → install missing tools in parallel with Phase 2 planning.
