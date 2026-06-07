# Scraped Docs Map

Use this reference to route from a task to the smallest useful file under `docs/`. Open targeted pages instead of broad-reading the scraped documentation tree.

## Contents

- Core docs entry points
- Subsystem routing
- Plugin-type routing
- Common-file routing
- Search patterns

## Core Docs Entry Points

Start here only when you need general orientation:

- `docs/intro.md`
- `docs/guides.md`
- `docs/apis.md`
- `docs/devupdate.md`

Prefer the subsystem or plugin-type pages below for real implementation work.

## Subsystem Routing

| Need | Open |
|---|---|
| Hooks | `docs/apis/core/hooks/index.md` |
| Dependency injection | `docs/apis/core/di/index.md` |
| Database API / DML | `docs/apis/core/dml/index.md` |
| Database schema concepts | `docs/apis/core/dml/database-schema.md` |
| External API overview | `docs/apis/subsystems/external/index.md` |
| External API function details | `docs/apis/subsystems/external/functions.md` |
| External API writing guide | `docs/apis/subsystems/external/writing-a-service.md` |
| External API security | `docs/apis/subsystems/external/security.md` |
| Output API | `docs/apis/subsystems/output/index.md` |
| In-place editable output | `docs/apis/subsystems/output/inplace.md` |
| Forms API | `docs/apis/subsystems/form/index.md` |
| Advanced form elements | `docs/apis/subsystems/form/advanced/advanced-elements.md` |
| Checkbox controller | `docs/apis/subsystems/form/advanced/checkbox-controller.md` |
| No-submit button | `docs/apis/subsystems/form/advanced/no-submit-button.md` |
| Repeat elements | `docs/apis/subsystems/form/advanced/repeat-elements.md` |
| `choicedropdown` | `docs/apis/subsystems/form/fields/choicedropdown.md` |
| Files in forms | `docs/apis/subsystems/form/usage/files.md` |
| Files API | `docs/apis/subsystems/files/index.md` |
| Privacy API | `docs/apis/subsystems/privacy/index.md` |
| Privacy FAQ | `docs/apis/subsystems/privacy/faq.md` |
| Privacy utils | `docs/apis/subsystems/privacy/utils.md` |
| Task API | `docs/apis/subsystems/task/index.md` |
| Scheduled tasks | `docs/apis/subsystems/task/scheduled.md` |
| Adhoc tasks | `docs/apis/subsystems/task/adhoc.md` |
| Backup | `docs/apis/subsystems/backup/index.md` |
| Restore | `docs/apis/subsystems/backup/restore.md` |
| Roles and capabilities | `docs/apis/subsystems/roles.md`, `docs/apis/subsystems/access.md` |
| Enrolment API | `docs/apis/subsystems/enrol.md` |
| MUC cache | `docs/apis/subsystems/muc/index.md` |
| Navigation | `docs/apis/core/navigation/index.md` |
| Activity completion | `docs/apis/core/activitycompletion/index.md` |
| Conditional activities | `docs/apis/core/conditionalactivities/index.md` |
| Custom fields | `docs/apis/core/customfields/index.md` |
| Report builder | `docs/apis/core/reportbuilder/index.md` |

## Plugin-Type Routing

| Plugin type | Open |
|---|---|
| `mod_` | `docs/apis/plugintypes/mod/index.mdx`, `docs/apis/plugintypes/mod/activitymodule.md`, `docs/apis/plugintypes/mod/courseoverview.md` |
| `block_` | `docs/apis/plugintypes/blocks/index.md` |
| `local_` | `docs/apis/plugintypes/local/index.mdx` |
| `report_` | `docs/apis/plugintypes/index.md` then related subsystem docs |
| `tool_` | `docs/apis/plugintypes/index.md` then related subsystem docs |
| `theme_` | `docs/apis/plugintypes/theme/index.md`, `layout.md`, `styles.md`, `fonts.md`, `images.md` |
| `enrol_` | `docs/apis/plugintypes/enrol/index.md` |
| `format_` | `docs/apis/plugintypes/format/index.md`, `migration.md` |
| `availability_` | `docs/apis/plugintypes/availability/index.md` |
| `fileconverter_` | `docs/apis/plugintypes/fileconverter/index.md` |
| `qtype_` | `docs/apis/plugintypes/qtype/index.md`, `restore.md` |
| `quizaccess_` | `docs/apis/plugintypes/quizaccess/index.md` |
| `atto_` | `docs/apis/plugintypes/atto/index.md` |
| `tiny_` | `docs/apis/plugintypes/tiny/index.md`, `testing.md` |
| `assignsubmission_` / `assignfeedback_` | `docs/apis/plugintypes/assign/index.md`, `submission.md`, `feedback.md` |
| `filter_` | `docs/apis/plugintypes/filter/index.md` |
| `repository_` | `docs/apis/plugintypes/repository/index.md` |
| `customfield_` | `docs/apis/plugintypes/customfield/index.md` |
| `ai` | `docs/apis/plugintypes/ai/index.md`, `placement.md`, `provider.md` |

## Common-File Routing

These are useful when you need a specific file pattern fast:

| Need | Open |
|---|---|
| `version.php` | `docs/apis/commonfiles/version.php/index.md` |
| `db/tasks.php` | `docs/apis/commonfiles/db-tasks.php/index.md` |
| `tag.php` | `docs/apis/commonfiles/tag.php/index.md` |
| Common file index | `docs/apis/commonfiles/index.mdx` |

## Developer Guides

The local scrape is strongest for API and plugin-type pages. For higher-level guides, use the curated references first, then fall back to the official guide when needed:

| Need | Prefer first | Fallback |
|---|---|---|
| Templates guide | `references/ui-templates.md` | official Templates guide |
| Testing guide | `references/testing.md` | official Writing PHPUnit tests guide |
| Upgrade guide | `references/upgrades.md` | official Plugin Upgrades guide |

## Search Patterns

Use `rg` to jump to precise docs when the map above is not enough:

```bash
rg -n "hook|db/hooks.php|PSR-14" docs/apis
rg -n "external_api|services.php|ajax" docs/apis
rg -n "renderable|renderer|mustache|named_templatable" docs/apis
rg -n "moodleform|repeat_elements|choicedropdown|filemanager" docs/apis
rg -n "scheduled task|adhoc task|db/tasks.php" docs/apis
rg -n "privacy|provider" docs/apis
rg -n "course content items|activity chooser|FEATURE_QUICKCREATE" docs/apis
rg -n "boost|scss|rendererfactory|theme|layout|pix_" docs/apis
```

## Versioning Guidance

The scraped docs are primarily from Moodle 5.2, with pages that note 5.0 and 5.1 additions in-context. When a feature is version-sensitive:

- trust the page-local version notes
- keep the code aligned with the plugin's actual target Moodle version
- prefer conservative patterns if the target version is unclear
