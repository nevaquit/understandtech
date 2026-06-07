# Security — Input Validation, Output Escaping, Capabilities

> Complete security checklist for Moodle plugin development: trust levels, PARAM_* types, sesskey, capabilities, file serving, SQL safety.

---

## User Trust Levels

Moodle security is designed around user trust levels. Each level has different privileges and restrictions:

| User Type | Trust Level | Can Upload JS/Flash? | Content Sanitised? | Notes |
|-----------|-------------|---------------------|-------------------|-------|
| **Admin** | Fully trusted | Yes | No | Can execute PHP/shell indirectly. Cannot restrict via code — must use `config.php` |
| **Teacher** | Trusted (risky capabilities) | Yes (via SCORM, etc.) | No (for their content) | XSS risk to other users is accepted trade-off. All risky-capability holders must be trusted |
| **Student** | Not trusted | No | **Yes** (HTML Purifier) | All submitted text sanitised. Files served from different domain or forced download |
| **Guest** | Not trusted | No | N/A | Cannot upload files or submit text stored in DB. Prevent spam/social engineering |

> **Key insight**: Browser trusts everything from one server — it can't distinguish between wanted/unwanted code. Uploaded teacher content becomes part of the server application.

## Common Vulnerability Types

Always be aware of these attack vectors when writing Moodle code:

| Vulnerability | Prevention |
|--------------|-----------|
| **Unauthenticated access** | `require_login()` on every page |
| **Unauthorised access** | `require_capability()` / `has_capability()` checks |
| **CSRF (Cross-site request forgery)** | `require_sesskey()` on all write actions |
| **XSS (Cross-site scripting)** | `s()`, `format_string()`, `format_text()`, `{{double braces}}` |
| **SQL injection** | Named placeholders (`:param`); never concatenate |
| **Command-line injection** | Avoid shell commands; use `escapeshellcmd()` / `escapeshellarg()` if unavoidable |
| **Data loss** | Confirmation step before bulk delete |
| **Information leakage** | Check capabilities before showing data; hide debug in production |
| **Session fixation** | Handled by Moodle core — don't manipulate sessions directly |
| **DOS (Denial of service)** | Rate limiting, resource caps on user operations |
| **Brute-force login** | Handled by core — lockout policies |

## Core Principle

**Never trust user input.** Every value from the browser, URL, form, or API must be validated before use and escaped before display.

## Input Validation

### Required Parameters

```php
// From GET/POST:
$id       = required_param('id', PARAM_INT);
$action   = required_param('action', PARAM_ALPHA);

// Optional with default:
$page     = optional_param('page', 0, PARAM_INT);
$search   = optional_param('search', '', PARAM_TEXT);
$format   = optional_param('format', 'html', PARAM_ALPHANUMEXT);
```

### PARAM_* Reference Table

| Constant             | Allows                             | Use For                        |
| -------------------- | ---------------------------------- | ------------------------------ |
| `PARAM_INT`          | Integer only                       | IDs, counts, timestamps        |
| `PARAM_FLOAT`        | Decimal numbers                    | Grades, scores                 |
| `PARAM_BOOL`         | Boolean (0/1)                      | Flags, toggles                 |
| `PARAM_TEXT`         | Multi-lang, strips tags             | Short user strings, names      |
| `PARAM_NOTAGS`       | Strip all HTML tags                | Plain text inputs              |
| `PARAM_RAW`          | No cleaning                        | HTML from editor (MUST escape on output) |
| `PARAM_RAW_TRIMMED`  | No cleaning, trimmed               | Editor content, trimmed        |
| `PARAM_ALPHA`        | `[a-zA-Z]` only                    | Short codes, actions           |
| `PARAM_ALPHANUMEXT`  | `[a-zA-Z0-9_-]`                    | Identifiers, component names   |
| `PARAM_ALPHANUM`     | `[a-zA-Z0-9]`                      | Tokens                         |
| `PARAM_URL`          | Valid URL                          | Links                          |
| `PARAM_LOCALURL`     | Local URL (no external)            | Redirects — prevents open redirect |
| `PARAM_FILE`         | Safe filename (no path separators) | Uploaded filenames             |
| `PARAM_PATH`         | Internal path, cleaned             | File paths within Moodle       |
| `PARAM_SAFEDIR`      | Single directory name              | Directory names                |
| `PARAM_SEQUENCE`     | Comma-separated integers           | Multi-select IDs               |
| `PARAM_TAGLIST`      | Comma-separated tags               | Tag inputs                     |
| `PARAM_COMPONENT`    | Frankenstyle component name        | Plugin names                   |
| `PARAM_AREA`         | File area name                     | File API areas                 |
| `PARAM_LANG`         | Language code                      | Language selection              |

### Rules

- **PARAM_RAW** — only use when you MUST preserve HTML; always escape on output
- **PARAM_TEXT** — default for user-entered short text
- **PARAM_INT** — default for any ID or numeric value
- **Never use `$_GET`, `$_POST`, `$_REQUEST`** directly

## Output Escaping

### In PHP

```php
// All user text displayed in HTML:
echo s($record->name);                          // HTML-escapes a plain string
echo format_string($record->name, true, ['context' => $context]);  // Multi-lang + escape
echo format_text($record->description, $record->descriptionformat, [
    'context' => $context,
    'noclean' => false,   // Default; set true only if trusted
]);

// In URLs:
$url = new moodle_url('/local/myplugin/view.php', ['id' => $id]);
echo $url->out();                               // URL-encoded output
echo $url->out(false);                          // HTML-safe URL
```

### In Mustache Templates

```mustache
{{name}}                    {{! Double-brace = HTML-escaped. SAFE. }}
{{{description_html}}}     {{! Triple-brace = raw HTML. ONLY for format_text() output. }}
{{#str}} pluginname, local_myplugin {{/str}}   {{! Language strings are pre-escaped. }}
```

**Rules:**
- `{{variable}}` — always the default; HTML-escapes automatically
- `{{{variable}}}` — only for content already sanitized via `format_text()` or renderer output
- Never pass raw user input through `{{{triple braces}}}`

## Session Key (Sesskey) — CSRF Protection

All state-changing requests must include and verify a sesskey.

### In Forms

```php
// moodleform handles sesskey automatically.
// For manual forms:
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
```

### In Links

```php
$url = new moodle_url('/local/myplugin/action.php', [
    'id'      => $id,
    'action'  => 'delete',
    'sesskey' => sesskey(),
]);
```

### Verification

```php
// At the top of any action handler:
require_sesskey();  // Dies with error if invalid.

// Or conditionally:
if (!confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey');
}
```

## Capabilities (Access Control)

### Define in db/access.php

```php
<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/myplugin:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'student'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
    'local/myplugin:manage' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'riskbitmask'  => RISK_SPAM | RISK_XSS,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
];
```

### Check in Code

```php
// Hard check — throws exception if denied:
require_capability('local/myplugin:manage', $context);

// Soft check — returns boolean:
if (has_capability('local/myplugin:manage', $context)) {
    // Show edit controls.
}

// Check for logged-in user (any page):
require_login($course, false, $cm);   // With course + cm context
require_login();                       // Simple login check

// Admin-only:
require_capability('moodle/site:config', context_system::instance());

// Site-wide check:
if (is_siteadmin()) {
    // Only for truly admin-only operations.
}
```

### Risk Bitmask Values

| Constant          | Meaning                                               |
| ----------------- | ----------------------------------------------------- |
| `RISK_SPAM`       | User can send messages / create visible content       |
| `RISK_PERSONAL`   | User can access other users' personal information     |
| `RISK_XSS`        | User can submit unfiltered HTML/JS                    |
| `RISK_CONFIG`     | User can change site configuration                    |
| `RISK_MANAGETRUST`| User can change trust level of other users            |
| `RISK_DATALOSS`   | User can destroy large amounts of data                |

## Login & Page Security

```php
// Standard page setup (every page):
require_once(__DIR__ . '/../../config.php');   // Loads Moodle.
require_login($course, false, $cm);            // Enforces authentication.

$context = context_course::instance($course->id);
require_capability('local/myplugin:view', $context);

$PAGE->set_url(new moodle_url('/local/myplugin/view.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_myplugin'));
$PAGE->set_heading($course->fullname);
```

## SQL Injection Prevention

```php
// ALWAYS use placeholders:
$DB->get_records_sql("SELECT * FROM {local_myplugin_items} WHERE courseid = :courseid AND status = :status",
    ['courseid' => $courseid, 'status' => $status]);

// For IN clauses:
[$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
$DB->get_records_sql("SELECT * FROM {local_myplugin_items} WHERE id $insql", $inparams);

// NEVER:
$DB->get_records_sql("SELECT * FROM {table} WHERE id = $id");              // ❌ SQL injection!
$DB->get_records_sql("SELECT * FROM {table} WHERE name = '$name'");        // ❌ SQL injection!
$DB->get_records_sql("SELECT * FROM {table} WHERE id = " . $id);          // ❌ SQL injection!
```

## File Serving Security

```php
// In lib.php — pluginfile callback:
function local_myplugin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    // 1. Check login.
    require_login($course, true, $cm);

    // 2. Check capability.
    require_capability('local/myplugin:view', $context);

    // 3. Validate file area.
    if ($filearea !== 'attachment') {
        return false;
    }

    // 4. Get the file.
    $itemid  = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_myplugin', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    // 5. Send the file.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
```

## Security Checklist

| # | Check | How |
|---|-------|-----|
| 1 | Login enforced | `require_login()` at top of every page |
| 2 | Context set | `$PAGE->set_context()` called |
| 3 | Capability checked | `require_capability()` before any action |
| 4 | Input validated | Every param via `required_param()` / `optional_param()` |
| 5 | Output escaped | `s()`, `format_string()`, `format_text()`, `{{double braces}}` |
| 6 | Sesskey verified | `require_sesskey()` for all write operations |
| 7 | SQL uses placeholders | Named params `:name` in all queries |
| 8 | File access checked | `pluginfile()` callback with auth |
| 9 | No direct globals | Never `$_GET`, `$_POST`, `$_REQUEST`, `$_FILES` |
| 10 | XSS risk flagged | `RISK_XSS` on capabilities that allow HTML input |
| 11 | Event logged | Trigger an event for every significant user action |
| 12 | No dangerous functions | No `eval()`, no backticks, no `preg_replace()` with `/e`, no `goto` |
| 13 | Verify course/module | `require_login($course, false, $cm)` with correct `$course` and `$cm` |

## Dangerous Functions / Constructs

**Never use in Moodle code:**

| Construct | Why | Alternative |
|-----------|-----|-------------|
| `eval()` | Arbitrary code execution | Refactor logic; lang packs are the only exception |
| `preg_replace()` with `/e` | Unintended PHP execution | Use `preg_replace_callback()` |
| Backticks (`` ` ` ``) | Shell command execution | `escapeshellcmd()` + `escapeshellarg()` if absolutely necessary |
| `goto` / labels | Unreadable control flow | Use standard control structures |
| `$_GET`/`$_POST`/`$_REQUEST` | Bypasses validation | `required_param()` / `optional_param()` |
| `print_error()` | Deprecated since Moodle 4.x | `throw new moodle_exception(...)` |

## Output Cleaning Strategy

| Function | Use When |
|----------|----------|
| `s($text)` | Displaying plain-text user input in HTML |
| `format_string($text)` | Short text with possible multi-lang spans (course/activity names) |
| `format_text($text, $format, $options)` | Rich HTML content (descriptions, editor output) |
| `$url->out()` | URL-encoded output |
| `$url->out(false)` | HTML-safe URL in attributes |
| `$PAGE->requires->data_for_js()` | Passing data to JavaScript safely |
