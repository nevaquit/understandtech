# Workflow and Project Layout

Use this reference when you need the surrounding Moodle plugin structure rather than a subsystem-specific API.

## Contents

- Installation directory map
- Plugin file structure
- `version.php` baseline
- Page bootstrap pattern
- Hooks and DI reminders
- Composer packaging
- Release checklist

## Moodle Installation Directory Map

Moodle 4.5+ separates the application root from the public web root:

```text
moodle/
├── admin/
│   └── cli/
├── config.php
├── vendor/
└── public/
    ├── mod/
    ├── block/
    ├── local/
    ├── admin/tool/
    ├── auth/
    ├── availability/
    ├── course/format/
    ├── enrol/
    ├── filter/
    ├── grade/report/
    ├── question/type/
    ├── report/
    ├── theme/
    ├── repository/
    └── editor/
        ├── atto/plugins/
        └── tiny/plugins/
```

Key globals:

| Variable | Purpose |
|---|---|
| `$CFG->dirroot` | Application root |
| `$CFG->wwwroot` | Public base URL |
| `$CFG->dataroot` | File storage outside the web root |
| `$CFG->admin` | Admin directory name |

Plugin placement rule:

- `local_myplugin` lives at `public/local/myplugin/`
- `mod_quiz` lives at `public/mod/quiz/`

Useful CLI commands:

```bash
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
php public/local/myplugin/cli/migrate_data.php
```

Never trigger CLI workflows from a web request. Use scheduled or adhoc tasks instead.

## Universal Plugin Structure

```text
plugintype_pluginname/
├── version.php
├── lang/en/plugintype_pluginname.php
├── db/
│   ├── install.xml
│   ├── upgrade.php
│   ├── access.php
│   ├── services.php
│   ├── events.php
│   ├── hooks.php
│   ├── tasks.php
│   ├── caches.php
│   └── messages.php
├── classes/
│   ├── external/
│   ├── event/
│   ├── hook/
│   ├── task/
│   ├── form/
│   ├── output/
│   ├── local/
│   ├── exception/
│   └── privacy/provider.php
├── templates/
├── amd/
│   ├── src/
│   └── build/
├── js/esm/
│   ├── src/
│   └── build/
├── pix/icon.svg
├── tests/
│   ├── *_test.php
│   ├── generator/lib.php
│   └── behat/
├── lib.php
├── settings.php
└── backup/moodle2/
```

Not every plugin uses every path. Add only what the feature requires.

## `version.php` Baseline

```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_myplugin';
$plugin->version = 2026030200;
$plugin->requires = 2024042200;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
$plugin->dependencies = [
    'mod_forum' => 2024042200,
];
```

Rules:

- `component` must match the Frankenstyle component exactly.
- `version` must increase on every release.
- `requires` should reflect the real minimum Moodle version.
- Declare plugin dependencies here even if Composer is also used.

## Page Bootstrap Pattern

```php
<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/myplugin:view', $context);

$PAGE->set_url(new moodle_url('/local/myplugin/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_myplugin'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo $OUTPUT->footer();
```

Adjust the relative path to `config.php` and the context type for the actual plugin location.

## Hooks and Dependency Injection

Prefer hooks over legacy callbacks when supported by the target Moodle version.

```php
$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_footer_html::class,
        'callback' => [\local_myplugin\local\hook_callbacks::class, 'before_footer'],
        'priority' => 500,
    ],
];
```

Use DI rather than globals in DI-capable classes:

```php
$hookmanager = \core\di::get(\core\hook\manager::class);
$hookmanager->dispatch($hook);
```

For full hook, event, task, and cache patterns, read [events-tasks-cache.md](events-tasks-cache.md).

## Composer Packaging

Use this only for Moodle versions and deployment flows that support Composer-installed plugins.

```json
{
  "name": "vendor/moodle-block_myblock",
  "type": "moodle-block",
  "require": {
    "moodle/moodle": "^5.2",
    "moodle/composer-installer": "*"
  }
}
```

Rules:

- Package name format: `vendor/moodle-{plugintype}_{pluginname}`
- Package type format: `moodle-{plugintype}`
- Keep `version.php` dependency metadata accurate for non-Composer installs too

Examples:

```bash
composer require vendor/moodle-block_myblock
composer create-project moodle/seed my-moodle-site
```

For local path-based development:

```json
{
  "repositories": [
    {"type": "path", "url": "../my-plugin", "options": {"symlink": false}}
  ]
}
```

## Release Checklist

Before release:

- Confirm `version.php` matches the component and has an incremented version.
- Make sure upgrade steps are idempotent and safe for re-entry.
- Keep all user-facing strings in `lang/en/`.
- Validate `install.xml` and index strategy.
- Add savepoints for each `upgrade.php` version block.
- Implement Privacy API when personal data is stored.
- Implement backup/restore when course-scoped data must survive duplication or restore.
- Confirm task, hook, cache, and service definitions are registered through the correct `db/*.php` files.
- Rebuild and commit generated frontend assets.
- Run the relevant PHPUnit, Behat, and coding-style checks when available.
- Test with the real roles affected by the feature.

After install or update:

```bash
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```
