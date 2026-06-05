# Contributing to understandtech.app

Engineering conventions for the platform monorepo.

## Branch naming

| Prefix | Use |
|--------|-----|
| `feature/<short-description>` | New functionality |
| `fix/<short-description>` | Bug fixes |
| `chore/<short-description>` | Tooling, docs, scaffolding |

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
type(scope): subject
```

Examples:

- `feat(local_aitutor): add quiz hint endpoint`
- `fix(infrastructure): correct PgBouncer pool size`
- `chore(docs): update phase-0 toolchain audit`

## Pull request requirements

- CI must pass (when configured in Phase 5)
- Include test updates for behavioral changes
- Reference an issue or playbook phase when applicable
- At least one approval before merge to `main`

## Code review expectations

- Verify no secrets in diff (API keys, passwords, JWT keys)
- Verify no Moodle core files added
- PHP changes follow Moodle Coding Style
- AI tutor changes must not leak assessment answers or lab flags

## Deployment

Merging to `main` triggers deployment via the self-hosted GitHub Actions runner (configured in Phase 5). Do not deploy manually unless the playbook explicitly requires it.

## AI-assisted development

- Read `.cursorrules` before starting work
- Follow prompts in `docs/playbook.md` in phase order
- Commit before multi-file agent sessions
- Run linters and tests after each prompt completes

## Questions

Refer to [docs/white-paper.md](docs/white-paper.md) for architecture decisions and [docs/playbook.md](docs/playbook.md) for build sequence.
