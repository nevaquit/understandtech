# understandtech.app Platform Monorepo

Custom plugins, infrastructure-as-code, and edge workers for **understandtech.app** — an AI-augmented certification training platform built on Moodle 4.5 LTS with a deconstructed edge-native architecture (Cloudflare + Azure).

This repository does **not** contain Moodle core. It tracks only the intellectual property and deployment artifacts defined in the [Technical White Paper v2.0](docs/white-paper.md).

## Architecture overview

- **Origin:** Azure VM running Nginx + PHP-FPM + custom Moodle plugins
- **Data:** Azure PostgreSQL (via PgBouncer) + Redis cache
- **Edge:** Cloudflare (WAF, Stream, Workers, AI Gateway)
- **AI:** All LLM calls routed through the Cloudflare AI Gateway Worker — Moodle PHP never calls providers directly

See [docs/white-paper.md](docs/white-paper.md) for the full architectural blueprint.

## Repository layout

| Directory | Purpose |
|-----------|---------|
| `moodle-plugins/` | Custom Moodle plugins (theme, local, mod, block) |
| `cloudflare-worker/` | Edge workers (AI Gateway) |
| `infrastructure/` | Bicep, Nginx, PHP-FPM, PgBouncer, runner configs |
| `.github/workflows/` | CI/CD pipelines |
| `scripts/` | Utility and conversion scripts |
| `docs/` | White paper, playbook, toolchain audit |
| `tests/` | E2E (Playwright) and integration tests |

## Local development quick start

> Docker Compose stack coming in Phase 3. For now:

```bash
git clone git@github.com:nevaquit/understandtech.git
cd understandtech
```

1. Review [Phase 0 toolchain audit](docs/phase-0-toolchain.md) and install missing tools
2. Follow [Creation Playbook](docs/playbook.md) phase by phase
3. Use the project skill: `/understandtech-platform` in Cursor Agent chat

## Documentation

| Document | Description |
|----------|-------------|
| [docs/white-paper.md](docs/white-paper.md) | Architecture and business strategy (v2.0) |
| [docs/playbook.md](docs/playbook.md) | Cursor-driven build sequence with prompts |
| [docs/phase-0-toolchain.md](docs/phase-0-toolchain.md) | Local toolchain audit |

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for branch naming, commit format, and PR requirements.

## License

Confidential and proprietary — AI Tech Pros, Inc. See [LICENSE](LICENSE).
