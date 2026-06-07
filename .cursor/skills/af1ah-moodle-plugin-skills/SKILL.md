---
name: moodle-plugin
description: Build, scaffold, review, debug, and modernize Moodle plugins with Moodle-native patterns and lean agent routing. Use when Codex needs to create or modify Moodle LMS plugin code such as `mod_`, `block_`, `local_`, `report_`, `tool_`, `theme_`, `auth_`, `enrol_`, `format_`, `filter_`, `availability_`, `fileconverter_`, `qtype_`, `quizaccess_`, `atto_`, `tiny_`, `assignsubmission_`, or `assignfeedback_` plugins; generate XMLDB schemas; write `$DB` queries; implement capabilities, hooks, events, scheduled or adhoc tasks, MUC caches, external APIs, web services, mobile support, forms, renderables, renderers, Mustache templates, Bootstrap UI, React, AMD, ESM, `core/reactive`, theming, privacy providers, backup and restore, PHPUnit, or Behat coverage; or answer Moodle coding standards, security, workflow, CLI command, testing, or deployment questions. Trigger when the request mentions Moodle 4.x/5.x, Frankenstyle, XMLDB, `version.php`, `install.xml`, `upgrade.php`, `db/access.php`, `db/services.php`, `db/hooks.php`, `db/tasks.php`, `require_login`, `require_capability`, sesskey, `moodleform`, `formslib.php`, `mod_form.php`, `repeat_elements`, `registerNoSubmitButton`, `add_checkbox_controller`, `choicedropdown`, Activity Chooser, course content items, Mustache, templates, renderer, renderable, Boost, layout, SCSS, `core/ajax`, `core/modal`, `core/notification`, `core/templates`, `core/str`, `core/reactive`, `js/esm`, import maps, `ReactAutoInit`, `{{#react}}`, `mountReactApp`, plugin scaffolding, Moodle upgrade, upgrade step, PHPUnit, Behat, cache definitions, enrolment APIs, or Moodle plugin release checks.
---

# Moodle Plugin Development

Read this overview first. Before writing Moodle-specific code, load the matching reference file from the table below. Do not rely on memory when a reference exists.

## Required Workflow

1. Classify the task before coding.
2. Load every relevant reference file from `references/`.
3. If the task is a new plugin, decide the plugin type before scaffolding or writing files.
4. Implement using Moodle-native patterns, not generic PHP or frontend defaults.
5. Validate security, strings, upgrade safety, and build/test requirements before finishing.
6. If a bundled reference is not enough, load the smallest matching file from `docs/` rather than broad-reading the scraped docs.

## Mandatory Reference Routing

| If the task involves... | Load this first |
|---|---|
| XMLDB, `install.xml`, `$DB`, SQL | [references/database.md](references/database.md) |
| `version.php`, upgrade steps, version bumps, `db/upgrade.php`, upgrade-safe migrations | [references/upgrades.md](references/upgrades.md) |
| `moodleform`, `mod_form.php`, repeated fields, filepicker, filemanager, editor draft areas, checkbox controller, no-submit buttons | [references/forms.md](references/forms.md) |
| Mustache, renderers, output classes, Bootstrap UI | [references/ui-templates.md](references/ui-templates.md) |
| AMD, ESM, `core/ajax`, `core/modal`, `core/str`, templates, Grunt, `core/reactive` | [references/javascript.md](references/javascript.md) |
| React, `{{#react}}`, `ReactAutoInit`, import maps, `@moodle/lms/*`, `js/esm/` | [references/react.md](references/react.md) |
| External functions, web services, mobile APIs | [references/external-api.md](references/external-api.md) |
| Capabilities, `PARAM_*`, sesskey, XSS, SQL injection, trust boundaries | [references/security.md](references/security.md) |
| PHPUnit, Behat, generators, test strategy, upgrade verification | [references/testing.md](references/testing.md) |
| Backup or restore | [references/backup-restore.md](references/backup-restore.md) |
| Privacy API or GDPR work | [references/privacy.md](references/privacy.md) |
| Plugin-type-specific boilerplate or file layout | [references/plugin-types.md](references/plugin-types.md) |
| Hooks, events, observers, tasks, MUC caches | [references/events-tasks-cache.md](references/events-tasks-cache.md) |
| Themes, Boost, Output API, renderer overrides | [references/theme-and-output.md](references/theme-and-output.md) |
| Frankenstyle, PHPDoc, naming, SQL style, `use` statements | [references/coding-style.md](references/coding-style.md) |
| Directory layout, `version.php`, page bootstrap, DI, Composer, release checks | [references/workflow.md](references/workflow.md) |
| Moodle CLI commands, build steps, test commands, validation workflow | [references/commands.md](references/commands.md) |
| Which scraped `docs/` file to open for a subsystem or plugin type | [references/docs-map.md](references/docs-map.md) |

## Stop Conditions

Stop and load the reference before coding if you notice any of these:

- You are about to write a `$DB->` query without reading `references/database.md`.
- You are about to edit `version.php` or `db/upgrade.php` without reading `references/upgrades.md`.
- You are about to add HTML or Mustache without reading `references/ui-templates.md`.
- You are about to build or edit a Moodle form without reading `references/forms.md`.
- You are about to define a service class or `db/services.php` without reading `references/external-api.md`.
- You are about to add JS behavior without reading `references/javascript.md` or `references/react.md`.
- You are about to enforce permissions without reading `references/security.md`.
- You are about to scaffold type-specific files without reading `references/plugin-types.md`.
- You are about to dig through `docs/` without first checking `references/docs-map.md`.

## Plugin-Type Selection

Use these routing rules before creating files:

- Course activity with completion, grading, or participation: `mod_`
- Sidebar or dashboard widget: `block_`
- Standalone page, API, CLI tool, or integration: `local_`
- Course or site reporting surface: `report_`
- Site administration utility: `tool_`
- Visual branding or renderer override: `theme_`
- Access restriction: `availability_`
- Enrolment method: `enrol_`
- Course layout: `format_`
- Text transformation: `filter_`
- Question type: `qtype_`
- Quiz access rule: `quizaccess_`
- Editor extension: `atto_` or `tiny_`
- Assignment extension: `assignsubmission_` or `assignfeedback_`
- File conversion: `fileconverter_`

For file-by-file boilerplate and examples, read [references/plugin-types.md](references/plugin-types.md).

## Preferred Working Pattern

When implementing a feature:

1. Read the relevant reference files.
2. Inspect the existing plugin structure before adding new files.
3. Keep all user-facing strings in `lang/en/...`.
4. Use namespaced classes under `classes/` for new PHP code.
5. Prefer hooks over legacy callbacks where Moodle version support allows it.
6. Keep source files editable and generated files generated:
   - edit `amd/src/`, never hand-edit `amd/build/`
   - edit `js/esm/src/`, never hand-edit `js/esm/build/`
7. Match Moodle conventions for escaping, permissions, navigation, and upgrade steps.
8. Use the scraped `docs/` only as a targeted primary-source lookup for details, examples, and subsystem edge cases.
9. Prefer built-in Moodle features over custom code whenever a core API, form helper, output helper, task, cache, or plugin-type convention already fits.
10. For upgrades, use upgrade-safe DB and task patterns rather than normal runtime plugin services.

## Hook Policy

Use hooks deliberately:

- Prefer `db/hooks.php` plus autoloaded callback classes when a modern hook exists.
- Keep legacy `lib.php` callbacks only where Moodle still requires them or the plugin type depends on them.
- When touching hooks, events, tasks, or caches, load [references/events-tasks-cache.md](references/events-tasks-cache.md) first.
- If you need hook naming, replacement, discovery, or callback migration details, open the targeted docs path from [references/docs-map.md](references/docs-map.md).

## Built-In First Rule

Default to Moodle’s built-in features before writing custom implementations.

- Use `moodleform` and standard form elements before raw HTML forms.
- Use `repeat_elements()`, `add_checkbox_controller()`, `registerNoSubmitButton()`, `disabledIf()`, and `hideIf()` before custom JS form behavior.
- Use renderables, renderers, Mustache templates, and theme overrides before custom output stacks.
- Use MUC before inventing ad-hoc caching.
- Use scheduled or adhoc tasks before long-running work in page requests.
- Use existing core APIs and plugin-type extension points before custom tables, endpoints, or frontend widgets.

Only choose a custom approach when the built-in Moodle mechanism clearly cannot satisfy the requirement cleanly.

## Scraped Docs Policy

The `references/` files are the fast path. The `docs/` tree is the deep source material.

Use this order:

1. Load the matching file from `references/`.
2. If you still need subsystem-specific detail, open the smallest matching file from `docs/` using [references/docs-map.md](references/docs-map.md).
3. Prefer one or two precise docs pages over broad folder reads.

## Scaffolding

Use the bundled scaffold script for a fresh plugin skeleton:

```bash
./scripts/scaffold.sh <plugintype> <pluginname>
```

Run it only after confirming the plugin type. Then load [references/plugin-types.md](references/plugin-types.md) and [references/workflow.md](references/workflow.md) before filling in the generated files.

## Non-Negotiable Rules

- Put every user-visible string in a language file.
- Use the maximum practical built-in Moodle APIs and extension points before custom code.
- Call `require_login()` and the appropriate `require_capability()` on protected pages.
- Use `require_sesskey()` for write actions.
- Use named SQL placeholders only.
- Escape output with Moodle helpers or Mustache auto-escaping.
- Keep Frankenstyle names consistent across component, namespace, JS modules, templates, and capabilities.
- Add tests when changing behavior that can reasonably be validated.
- If personal data is stored, evaluate whether a Privacy provider is required.

## Completion Checklist

Before finishing, verify that you:

- Read the relevant reference files before implementation.
- Did not hardcode user-facing strings.
- Did not write unsafe SQL or skip required permission checks.
- Updated generated frontend build artifacts when source JS changed.
- Considered upgrade, backup/restore, privacy, and tests when the feature touches those areas.
- Used commands and verification steps appropriate to the work you changed.

Use [references/workflow.md](references/workflow.md) for install layout, `version.php`, page setup, hooks, Composer, and deployment checks. Use [references/commands.md](references/commands.md) for CLI and build commands.
