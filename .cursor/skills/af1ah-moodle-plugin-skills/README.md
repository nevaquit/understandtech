# Moodle Plugin Skill

This directory is a Moodle plugin development skill optimized for agent use. The goal is to help an agent load the smallest useful context, follow Moodle-native patterns, and avoid generic PHP or frontend mistakes.

## Structure

- `SKILL.md`
  The control file. It defines trigger coverage, routing rules, hook policy, and the required workflow.
- `references/`
  Curated, agent-friendly references. These are the fast path and should be loaded before the scraped docs.
- `docs/`
  Scraped Moodle developer docs used as deeper primary-source material when the curated references are not enough.
- `scripts/`
  Utility scripts such as scaffolding.
- `agents/openai.yaml`
  UI metadata for discovery.

## Loading Strategy

1. Start with `SKILL.md`.
2. Load the smallest matching file in `references/`.
3. Use `references/docs-map.md` to route into `docs/` only when needed.
4. Open targeted docs pages, not entire folders.

## Important References

- `references/plugin-types.md`
  Decide which plugin type and file layout to use.
- `references/workflow.md`
  Project structure, `version.php`, page bootstrap, release checks.
- `references/commands.md`
  CLI, build, and verification commands.
- `references/events-tasks-cache.md`
  Hooks, events, tasks, and caching decisions.
- `references/docs-map.md`
  Fast map from task to scraped docs path.

## Source Notes

The scraped `docs/` tree is mainly based on Moodle 5.2 developer documentation, with 5.0 and 5.1 changes noted inside many pages. The skill references aim to keep patterns practical and conservative so an agent can work safely even when the exact target version needs confirming.
