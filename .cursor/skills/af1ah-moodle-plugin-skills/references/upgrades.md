# Plugin Upgrades

Use this reference for `version.php`, `install.xml`, `upgrade.php`, upgrade-safe migrations, and version-bump decisions.

## Contents

- When to load this file
- Key files
- Version bump triggers
- Upgrade restrictions
- Delayed migrations with adhoc tasks
- Upgrade rules

## When To Load This File

Load this first when the task mentions any of:

- `version.php`
- `install.xml`
- `upgrade.php`
- upgrade step
- plugin upgrade
- version bump
- `db/` file changes
- cache reset after code changes

## Key Files

- `version.php`
  Declares plugin version, requirements, maturity, release, and dependencies.
- `db/install.xml`
  Defines the full current schema used on first install.
- `db/upgrade.php`
  Applies incremental upgrade steps from older installed versions.
- `db/upgradelib.php`
  Optional helper functions for non-trivial upgrade logic that should stay testable.

## Version Bump Triggers

Increase the plugin version when changing any of these:

- anything in `db/`
- JavaScript that requires cache rebuild or new generated assets
- new autoloaded classes
- new settings
- language changes that must be picked up by upgrade and cache reset

Practical rule:

- if Moodle must notice the change via upgrade flow or cache reset, bump the version

## Upgrade Restrictions

Upgrade code must be conservative.

- use the basic DB API freely
- do not call your plugin’s normal runtime functions from plugin upgrade code
- if the plugin already expects the new schema/state, do not reuse it during upgrade
- prefer direct data migrations in upgrade code over calling behavior-oriented plugin services

Core functions are generally safe during plugin upgrade because core is already current, but plugin functions are not safe because plugin data is still old.

## Delayed Migrations With Adhoc Tasks

If an upgrade step is too long-running, depends on post-upgrade state, or needs cache rebuilds or later APIs, queue an adhoc task from the upgrade step instead of forcing all work inline.

```php
if ($oldversion < 2020031001) {
    $task = new \plugintype_pluginname\task\migrate_course_completion();
    \core\task\manager::queue_adhoc_task($task, true);

    upgrade_plugin_savepoint(true, 2020031001, 'plugintype', 'pluginname');
}
```

Use this pattern when:

- the migration is long-running
- the step depends on a rebuilt cache
- the step needs APIs that are not safe during upgrade execution

## Upgrade Rules

- Keep `install.xml` as the full up-to-date schema.
- Generate schema changes with the XMLDB editor.
- Use ordered `if ($oldversion < X)` blocks.
- End each block with `upgrade_plugin_savepoint(...)`.
- Keep each step idempotent and safe if partially rerun.
- Put complex helper logic in `db/upgradelib.php` when that improves clarity and testability.
- After upgrade-related edits, run:

```bash
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```
