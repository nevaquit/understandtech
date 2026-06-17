# Coding Style — Moodle Standards

> PSR-12 base + Moodle-specific overrides, Frankenstyle naming, PHPDoc, namespaces, SQL style, Git conventions.

## Contents

- Base standard
- Frankenstyle naming
- File headers
- Naming conventions
- Classes and namespaces
- PHPDoc and type hints
- SQL style
- Git and maintenance rules

---

## Base Standard

Moodle follows **PSR-12** with specific overrides documented below. When PSR-12 and Moodle conflict, Moodle wins.

## Frankenstyle Naming

Frankenstyle is Moodle's component naming convention: `{plugintype}_{pluginname}`.

### Format

1. **Prefix** = plugin type (e.g., `mod`, `block`, `local`, `tool`)
2. **Name** = folder name, always lowercase (e.g., `quiz`, `myplugin`)
3. Combined: `mod_quiz`, `local_myplugin`, `block_news`

### Where Frankenstyle Is Used

| Context | Convention | Example |
|---------|-----------|---------|
| **Functions** | `{frankenstyle}_functionname()` | `local_myplugin_get_items()` |
| **Classes** | Namespace = Frankenstyle | `\local_myplugin\manager` |
| **Constants** | `UPPERCASE_FRANKENSTYLE_NAME` | `MOD_FORUM_MODE_FLATOLDEST` |
| **DB tables** | `{pluginname}_{entity}` (mod_ omits prefix) | `{local_myplugin_items}`, `{forum}` |
| **Capabilities** | `{type}/{name}:{action}` (slash not underscore) | `local/myplugin:manage`, `mod/quiz:view` |
| **Language file** | `lang/en/{frankenstyle}.php` | `lang/en/local_myplugin.php` |
| **JS modules** | `{frankenstyle}/{module}` | `local_myplugin/item_manager` |
| **Templates** | `{frankenstyle}/{template}` | `local_myplugin/item_card` |
| **Web service functions** | `{frankenstyle}_{verb}_{noun}` | `local_myplugin_get_items` |
| **`@package` tag** | Frankenstyle | `@package local_myplugin` |

### Core Subsystems

Core subsystems use `core_{subsystem}`: `core_course`, `core_user`, `core_enrol`, `core_cache`, etc. Code lives in `/lib/` or dedicated directories. Full list via `core_component::get_core_subsystems()`.

### Exceptions

- **Activity modules**: Functions may use just `{modulename}_` prefix for legacy reasons (e.g., `forum_add_instance()`)
- **DB tables for mod_**: Omit the `mod_` prefix (e.g., table `{forum}` not `{mod_forum}`)
- **Language files for mod_**: Use just the module name (e.g., `lang/en/quiz.php`)

## File Header

Every PHP file must start with:

```php
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Description of what this file does.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name <you@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

Non-class files must include: `defined('MOODLE_INTERNAL') || die();` after the docblock (or `require_once(…config.php)` for entry points).

## Naming Conventions

### Files

| Type | Convention | Example |
|------|-----------|---------|
| Class file | `lowercase.php` in PSR-4 path | `classes/manager.php` |
| Entry point | `lowercase.php` | `view.php`, `index.php` |
| DB definition | Fixed names | `db/install.xml`, `db/access.php` |
| Language file | `component.php` | `lang/en/local_myplugin.php` |
| Test file | `classname_test.php` | `tests/manager_test.php` |

### Classes & Interfaces

```php
// Autoloaded class — maps to classes/item_manager.php:
namespace local_myplugin;
class item_manager { }

// Autoloaded sub-namespace — maps to classes/external/get_items.php:
namespace local_myplugin\external;
class get_items extends external_api { }
```

- **Class names**: `lowercase_with_underscores` (Moodle override of PSR-12 PascalCase)
- **Interface names**: Same as classes — lowercase_with_underscores
- **Trait names**: Same as classes
- **Exception classes (4.5+)**: Place in `classes/exception/` directory

### Functions & Methods

```php
// snake_case, descriptive verb + noun:
function get_items_by_course(int $courseid): array { }
function delete_item(int $itemid): bool { }
function is_item_visible(\stdClass $item): bool { }

// Private/protected — no underscore prefix:
private function validate_item_data(array $data): void { }  // ✅
private function _validate_data(array $data): void { }      // ❌ No leading underscore!
```

### Variables

```php
$courseid = 0;           // snake_case, descriptive
$itemcount = 0;          // No abbreviations unless standard (id, url, html)
$DB                      // Globals are UPPERCASE
$CFG
$PAGE
$OUTPUT
$USER
$COURSE
$SITE
```

### Constants

```php
// Class constants:
class manager {
    public const STATUS_DRAFT     = 0;
    public const STATUS_PUBLISHED = 1;
}

// Global constants (legacy — avoid in new code):
define('LOCAL_MYPLUGIN_MAX_ITEMS', 100);
```

### Database Tables & Columns

```
{local_myplugin_items}          -- Frankenstyle prefix, plural, lowercase
    id BIGINT                   -- Always auto-increment PK
    courseid BIGINT             -- FK, compound word, no underscores
    name VARCHAR(255)           -- Use name, not title
    description LONGTEXT
    descriptionformat TINYINT   -- Format field paired with LONGTEXT
    usermodified BIGINT         -- Standard: who last modified
    timecreated BIGINT          -- Standard: unix timestamp
    timemodified BIGINT         -- Standard: unix timestamp
```

## Formatting Rules

### Indentation & Spacing

- **4 spaces** — no tabs
- **Max line length**: 180 characters (soft limit 132)
- **One blank line** between methods
- **No trailing whitespace**
- **Single blank line** at end of file
- **No closing `?>`** tag

### Control Structures

```php
// Braces on same line, space before parenthesis:
if ($condition) {
    // ...
} else if ($other) {      // "else if" NOT "elseif" — Moodle override!
    // ...
} else {
    // ...
}

// Switch:
switch ($value) {
    case 'a':
        do_something();
        break;
    case 'b':
        do_other();
        break;
    default:
        do_default();
        break;
}

// Loops:
foreach ($items as $key => $item) {
    // ...
}

while ($condition) {
    // ...
}

for ($i = 0; $i < $count; $i++) {
    // ...
}
```

**Key Moodle override**: Use `else if` (two words) instead of `elseif` (one word).

### Strings

```php
// Single quotes for non-interpolated:
$name = 'Hello World';

// Double quotes when variables are embedded:
$message = "Hello {$user->firstname}";

// Concatenation:
$sql = "SELECT * FROM {local_myplugin_items} "
     . "WHERE courseid = :courseid "
     . "AND status = :status";
```

### Arrays

```php
// Short array syntax (required):
$items = ['a', 'b', 'c'];

// Associative with alignment:
$record = [
    'name'        => $name,
    'description' => $description,
    'courseid'    => $courseid,
];

// Never use array():
$old = array('a', 'b');    // ❌ Deprecated style
```

## Type Declarations

```php
// Required on all new code:
public function get_item(int $id): ?\stdClass {
    // Nullable return type.
}

public function process_items(array $items): void {
    // Void return type.
}

// Union types (PHP 8.0+):
public function get_value(): int|string {
    // ...
}

// Constructor promotion (PHP 8.0+):
public function __construct(
    private readonly int $id,
    private readonly string $name,
) { }

// #[\Override] attribute (Moodle 5.0+ / PHP 8.3):
#[\Override]
public function get_name(): string {
    return get_string('pluginname', 'local_myplugin');
}
```

## PHPDoc Requirements

### Class

```php
/**
 * Manages items for the plugin.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name <you@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
```

### Method

```php
/**
 * Get all items for a course.
 *
 * Long description if needed. Can span multiple paragraphs.
 *
 * @param int $courseid The course ID.
 * @param int $status Filter by status (0 = all).
 * @return stdClass[] Array of item records.
 * @throws \dml_exception If database query fails.
 */
public function get_items(int $courseid, int $status = 0): array {
```

### Property

```php
/** @var int The course ID. */
private int $courseid;

/** @var \stdClass[] Cached items. */
private array $items = [];
```

### Required Tags

| Tag | Where | Required |
|-----|-------|----------|
| `@package` | File docblock, class docblock | Always |
| `@copyright` | File docblock, class docblock | Always |
| `@license` | File docblock, class docblock | Always |
| `@param` | Method/function docblock | When has parameters |
| `@return` | Method/function docblock | When returns value |
| `@var` | Property docblock | Always |
| `@throws` | Method docblock | When explicitly throws |
| `@covers` | Test class docblock | PHPUnit tests |
| `@group` | Test class docblock | PHPUnit tests |

## Namespace Rules

```
Frankenstyle:    local_myplugin
Root namespace:  \local_myplugin
Sub-namespaces:  \local_myplugin\external
                 \local_myplugin\output
                 \local_myplugin\task
                 \local_myplugin\event
                 \local_myplugin\privacy
                 \local_myplugin\hook
```

- Max 3 levels: `component\level2\level3`
  - Exception: `\core` subsystems can go deeper
- `use` statements at top of file, one per line, alphabetically sorted
- Never use `require_once` for autoloaded classes

## Git Commit Messages

```
MDL-12345 local_myplugin: Add item management API

- Created manager class with CRUD operations
- Added external API functions for AJAX
- Implemented PHPUnit tests with data generator
```

Format: `MDL-TRACKER_ID component: Short description`

- First line: ≤72 characters
- Blank line after first line
- Body: wrap at 72 characters
- Reference the Moodle tracker issue

## Code Checker

```bash
# Run Moodle code checker (requires local_codechecker plugin):
php local/codechecker/cli/checker.php --standard=moodle local/myplugin

# Or via PHPCodeSniffer directly:
vendor/bin/phpcs --standard=moodle local/myplugin

# Auto-fix:
vendor/bin/phpcbf --standard=moodle local/myplugin
```

## Common Mistakes

| Mistake | Correct |
|---------|---------|
| `elseif` | `else if` |
| `array()` | `[]` |
| `$_GET['id']` | `required_param('id', PARAM_INT)` |
| `echo $name` | `echo s($name)` or `echo format_string($name)` |
| Missing `defined('MOODLE_INTERNAL')` | Add to all non-entry-point files |
| `require_once` for autoloaded class | Use `use` statement instead |
| PascalCase class name | `snake_case` class name |
| Leading underscore on private | No prefix for private/protected |
| `global $DB` in constructor | Pass as parameter or use DI |
| `!=` in SQL | Use `<>` |
| `INNER JOIN` | Just `JOIN` |
| Non-namespaced new class | Namespace under `classes/` |

## SQL Coding Style

All SQL in Moodle must follow these conventions:

### General Rules

- All SQL keywords in **UPPER CASE** (`SELECT`, `FROM`, `JOIN`, `WHERE`, `GROUP BY`, `HAVING`, `ORDER BY`)
- Enclose SQL in **double quotes** (single quotes for SQL string values)
- Use **named placeholders** (`:param`) — prefer over `?` when >1 parameter
- Right-align SQL clauses for readability on multi-line queries
- Use `JOIN` not `INNER JOIN`; never use right joins
- Use `<>` for not-equal, not `!=`
- Use `AS` for column aliases; **never** use `AS` for table aliases

### SQL Formatting Example

```php
$sql = "SELECT i.id,
               i.name,
               u.firstname AS author_first,
               COUNT(l.id) AS logcount
          FROM {local_myplugin_items} i
          JOIN {user} u ON u.id = i.userid
     LEFT JOIN {local_myplugin_logs} l ON l.itemid = i.id
         WHERE i.courseid = :courseid
           AND i.status <> :deleted
      GROUP BY i.id, i.name, u.firstname
        HAVING COUNT(l.id) > :minlogs
      ORDER BY i.sortorder ASC";
$records = $DB->get_records_sql($sql, [
    'courseid' => $courseid,
    'deleted'  => STATUS_DELETED,
    'minlogs'  => 5,
]);
```

### Placeholder Types

| Syntax | Style | When to use |
|--------|-------|-------------|
| `:named` | Named | **Preferred** — always use with >1 parameter |
| `?` | Positional | Acceptable for single-parameter simple queries |

## Component Communication Rules

Moodle enforces strict inter-component communication boundaries to keep plugins replaceable:

### Allowed Communication

| From | To | Allowed? |
|------|----|----------|
| Any component | Core / core subsystems | ✅ Always |
| Component | Itself | ✅ Always |
| Component | Declared dependency (`version.php`) | ✅ Yes |
| Sub-plugin | Parent plugin | ✅ Yes |
| Plugin | Another non-dependent plugin | ❌ **Forbidden** — go through a core API |

### How Plugin X Talks to Plugin Y (Without Dependency)

Through a **core API**. Example: `assignment online text` uses the **Editor API** to add a rich text field — it doesn't reference Atto/TinyMCE directly. This allows either plugin to be replaced.

### Communication Channels

1. **Direct PHP calls** — know the function, call it (only to allowed components)
2. **External functions** — call via `\core_external\external_api::call_external_function()` wrapper in PHP
3. **AMD/JS modules** — load from allowed components only
4. **Templates** — render from allowed components
5. **`get_string()`** — fetch strings from allowed components
6. **Event observers** — react to events (read-only). **Must not** be added by core/subsystems.
7. **Callbacks** — `component_callback()` / `get_plugins_with_function()` to call plugin implementations

### Ideal Plugin Architecture (Layered)

```
┌──────────────────────────────────┐
│  Webservice API (db/services.php)│  ← External: Mobile App, AJAX, REST
├──────────────────────────────────┤
│  External API (classes/external/)│  ← Validates params, wraps component API
├──────────────────────────────────┤
│  Component API (classes/)        │  ← Permission checks, business logic
├──────────────────────────────────┤
│  Low-level API ($DB calls)       │  ← Data access, no permission checks
└──────────────────────────────────┘
```

## Moodle App Coding Style (TypeScript / Angular)

The Moodle App uses TypeScript + Angular with these additions to the standard coding style:

### Key Rules

- **async/await** preferred — never mix with `.then/.catch/.finally` in the same function
- **If guards** encouraged to reduce nesting — handle edge cases early with return/throw
- **Spread operator** — allowed but add a comment explaining what it does
- **String interpolation** with backticks encouraged over concatenation
- **No comma-separated declarations** — one `const`/`let` per line
- **Options objects** instead of >2 optional parameters
- **Exported constants** in a dedicated `constants.ts` file (for code splitting)
- **Private/protected constants** as `static readonly` class properties; reference via `ClassName.CONSTANT` not `this.CONSTANT`
- **Angular Signals** — always declare as `readonly` properties
- **Avoid methods in templates** — use values instead (performance); avoid getters that hide method calls
- **Maximise attributes per line** in templates (140 char limit, then break)
- **Standalone page components** — export as `default` class, use `loadComponent` in routes
- **ESLint warnings** treated with same severity as errors
- **User-defined type guards** — use sparingly; prefer built-in type narrowing

---

## .gitignore — Moodle Plugin Repository

Every Moodle plugin repo should include a `.gitignore`. Copy this template into your plugin root.

```gitignore
# ── AI agent & IDE config ──────────────────────────────────────────────────
# Claude Code
.claude/
CLAUDE.md

# OpenAI / general agent instructions
AGENTS.md
.agents/

# Cursor IDE
.cursor/
.cursorignore
.cursorules

# GitHub Copilot
.copilot/
.github/copilot-instructions.md

# Aider AI
.aider*
.aider.conf.yml
.aiderignore

# Continue.dev
.continue/

# Windsurf IDE
.windsurf/
.windsurfrules

# Cline / Roo Code
.clinerules
.roo/

# ── Moodle development ────────────────────────────────────────────────────
# Composer — never commit vendor/ inside a plugin repo
vendor/
composer.lock

# Node / Grunt
node_modules/

# NOTE: amd/build/ MUST be committed for Moodle to serve minified JS
# Do NOT add amd/build/ to .gitignore

# PHPUnit artefacts
.phpunit.result.cache

# Behat artefacts
behat_faildump/

# IDE / editor
.idea/
.vscode/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# PHP / Xdebug profiling
cachegrind.out.*
*.prof
```

### Rules for `amd/build/`

> **Never ignore `amd/build/`** — Moodle reads the minified files from there directly. Always run `npx grunt amd` and commit the build output before pushing.

### When to commit `CLAUDE.md` / `AGENTS.md`

If the file contains project-wide instructions that ALL contributors should use (e.g. "always run phpcs before committing"), commit it. If it contains personal API keys, personal preferences, or workspace paths, add it to `.gitignore`.
