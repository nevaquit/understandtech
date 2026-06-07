# Commands and Validation Workflow

Use this reference when the task needs Moodle CLI commands, JS build commands, test commands, or a practical verification sequence.

## Contents

- Common Moodle CLI commands
- Frontend build commands
- Quality and test commands
- Suggested implementation workflow
- Fast verification by change type

## Common Moodle CLI Commands

Run these from the Moodle application root unless the command path is plugin-specific.

```bash
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
php admin/cli/cron.php
```

Plugin-specific CLI pattern:

```bash
php public/local/myplugin/cli/some_task.php
```

Use CLI after plugin install or upgrade, and after changes that rely on caches, tasks, or generated metadata.

## Frontend Build Commands

For AMD JavaScript:

```bash
npx grunt amd
npx grunt amd --root=local/myplugin
npx grunt watch
```

For React or ESM workflows where the Moodle setup provides them:

```bash
npx grunt react
npx grunt react:dev
npx grunt react:watch
```

Important rules:

- Edit source files only.
- Commit generated build output if the repository expects it.
- Never hand-edit `amd/build/` or `js/esm/build/`.

## Quality and Test Commands

Coding style:

```bash
vendor/bin/phpcs --standard=moodle path/to/plugin
vendor/bin/phpcbf --standard=moodle path/to/plugin
```

PHPUnit:

```bash
vendor/bin/phpunit --testsuite local_myplugin
vendor/bin/phpunit path/to/plugin/tests/
```

Behat:

```bash
php admin/tool/behat/cli/run.php --tags=@local_myplugin
```

Use the narrowest relevant command for the change. Do not default to broad test suites when a plugin-scoped run is available.

## Suggested Implementation Workflow

1. Read the relevant `references/*.md` file.
2. If needed, use [docs-map.md](docs-map.md) to open a precise `docs/` source page.
3. Implement the feature in source files only.
4. Rebuild generated assets if JS or React sources changed.
5. Run the smallest relevant checks:
   - `phpcs` for PHP and style-sensitive changes
   - `upgrade.php` for upgrade steps, install metadata, hooks, tasks, caches, or DB definitions
   - PHPUnit for business logic and external APIs
   - Behat for UI workflows
   - `purge_caches.php` for install or metadata changes

## Fast Verification by Change Type

| Change type | Minimum useful checks |
|---|---|
| `install.xml`, `upgrade.php`, `version.php`, `db/*.php` | `php admin/cli/upgrade.php`, `php admin/cli/purge_caches.php` |
| `mod_form.php`, `classes/form/*`, filemanager, filepicker, editor draft handling | manual form flow test, focused PHPUnit if form processing logic exists |
| PHP service or manager classes | `phpcs`, focused PHPUnit |
| External API or AJAX endpoint | focused PHPUnit plus a manual permission review |
| Mustache, renderer, output changes | `phpcs`, manual page render test, relevant Behat if available |
| AMD JS | `npx grunt amd --root=plugintype/pluginname`, manual UI smoke test |
| React / `js/esm` | `npx grunt react` or project equivalent, manual UI smoke test |
| Hooks, events, tasks, caches | `upgrade.php`, `purge_caches.php`, focused PHPUnit if logic exists |
| Theme / SCSS | relevant build step plus manual visual check in Boost-based pages |

## Task Usage Rules

Use tasks to remove slow or non-interactive work from request handling:

- use **scheduled tasks** for recurring maintenance, sync, cleanup, or polling
- use **adhoc tasks** for one-off background work queued by a user action or event
- do not keep expensive loops, remote calls, or bulk recalculation inside normal page requests if a task can do it
- after adding, removing, or changing task definitions, run `php admin/cli/upgrade.php` and `php admin/cli/purge_caches.php`

## Debugging Helpers

Useful during development:

```php
debugging('Debug message', DEBUG_DEVELOPER);
```

Use temporary debugging deliberately, and remove it before finalizing.
