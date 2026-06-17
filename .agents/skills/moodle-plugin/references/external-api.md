# External Services (Web Services / AJAX API)

> Defining external functions, registering them in `db/services.php`, parameter/return types, AJAX-callable services.

## Architecture

External API lets plugins expose functions for:
- **AJAX calls** from Moodle's own JavaScript (`core/ajax`)
- **Web service clients** (mobile app, REST/XML-RPC)
- **Inter-plugin communication**

## File Layout

```
local/myplugin/
├── classes/
│   └── external/
│       ├── get_items.php          # One class per function
│       ├── delete_item.php
│       └── create_item.php
├── db/
│   └── services.php               # Function registry
└── externallib.php                 # DEPRECATED — use classes/external/ instead
```

## Step 1 — External Function Class

Each function is a class extending `\core_external\external_api`.

```php
<?php
// classes/external/get_items.php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use context_course;
use invalid_parameter_exception;

/**
 * External function local_myplugin_get_items.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_items extends external_api {

    /**
     * Define parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'status'   => new external_value(PARAM_INT, 'Status filter', VALUE_DEFAULT, 0),
            'page'     => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage'  => new external_value(PARAM_INT, 'Items per page', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $courseid Course ID.
     * @param int $status Status filter.
     * @param int $page Page number.
     * @param int $perpage Items per page.
     * @return array
     */
    public static function execute(int $courseid, int $status = 0, int $page = 0, int $perpage = 20): array {
        // 1. Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'status'   => $status,
            'page'     => $page,
            'perpage'  => $perpage,
        ]);

        // 2. Validate context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // 3. Check capability.
        require_capability('local/myplugin:view', $context);

        // 4. Business logic.
        $manager = new \local_myplugin\manager();
        $items = $manager->get_items($params['courseid'], $params['status'], $params['page'], $params['perpage']);
        $total = $manager->count_items($params['courseid'], $params['status']);

        return [
            'items' => array_map(fn($item) => [
                'id'          => $item->id,
                'name'        => format_string($item->name, true, ['context' => $context]),
                'description' => format_text($item->description, $item->descriptionformat, ['context' => $context]),
                'timecreated' => $item->timecreated,
            ], $items),
            'total' => $total,
        ];
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'id'          => new external_value(PARAM_INT, 'Item ID'),
                    'name'        => new external_value(PARAM_TEXT, 'Item name'),
                    'description' => new external_value(PARAM_RAW, 'Formatted description HTML'),
                    'timecreated' => new external_value(PARAM_INT, 'Unix timestamp'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total count'),
        ]);
    }
}
```

### Write/Delete Example

```php
<?php
// classes/external/delete_item.php
namespace local_myplugin\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class delete_item extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'itemid' => new external_value(PARAM_INT, 'Item ID to delete'),
        ]);
    }

    public static function execute(int $itemid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['itemid' => $itemid]);

        $item = $DB->get_record('local_myplugin_items', ['id' => $params['itemid']], '*', MUST_EXIST);
        $context = \context_course::instance($item->courseid);
        self::validate_context($context);
        require_capability('local/myplugin:manage', $context);

        $DB->delete_records('local_myplugin_items', ['id' => $item->id]);

        // Fire event.
        \local_myplugin\event\item_deleted::create([
            'objectid' => $item->id,
            'context'  => $context,
        ])->trigger();

        return ['success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether delete succeeded'),
        ]);
    }
}
```

## Step 2 — Register in db/services.php

```php
<?php
// db/services.php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_myplugin_get_items' => [
        'classname'   => \local_myplugin\external\get_items::class,
        'description' => 'Get items for a course',
        'type'        => 'read',
        'ajax'        => true,        // Required for core/ajax calls
        'capabilities' => 'local/myplugin:view',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],  // Optional: expose to mobile app
    ],
    'local_myplugin_delete_item' => [
        'classname'    => \local_myplugin\external\delete_item::class,
        'description'  => 'Delete an item',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'local/myplugin:manage',
    ],
    'local_myplugin_create_item' => [
        'classname'    => \local_myplugin\external\create_item::class,
        'description'  => 'Create a new item',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'local/myplugin:manage',
    ],
];
```

## Parameter Type Reference

| Constant          | Meaning                                      |
| ----------------- | -------------------------------------------- |
| `PARAM_INT`       | Integer                                      |
| `PARAM_FLOAT`     | Floating point number                        |
| `PARAM_BOOL`      | Boolean (0 or 1)                             |
| `PARAM_TEXT`       | Cleaned text (tags stripped)                  |
| `PARAM_RAW`       | No cleaning — use for pre-formatted HTML     |
| `PARAM_ALPHA`     | Alphabetical only `[a-zA-Z]`                 |
| `PARAM_ALPHANUMEXT` | Alphanumeric + `_-`                         |
| `PARAM_URL`       | Valid URL                                    |
| `PARAM_FILE`      | Safe filename                                |
| `PARAM_NOTAGS`    | Text with HTML tags stripped                 |

### Value Flags

```php
// Required (default):
new external_value(PARAM_INT, 'Description')

// Optional with default:
new external_value(PARAM_INT, 'Description', VALUE_DEFAULT, 0)

// Optional, may be null:
new external_value(PARAM_INT, 'Description', VALUE_OPTIONAL)
```

## Calling from JavaScript

```js
import {call as fetchMany} from 'core/ajax';

// Single call:
const result = await fetchMany([{
    methodname: 'local_myplugin_get_items',
    args: {courseid: 42, status: 1},
}])[0];

// Error handling:
try {
    const data = await fetchMany([{
        methodname: 'local_myplugin_delete_item',
        args: {itemid: 17},
    }])[0];
} catch (error) {
    Notification.exception(error);
}
```

## Pagination Pattern

```php
// Parameters:
'page'    => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
'perpage' => new external_value(PARAM_INT, 'Per page', VALUE_DEFAULT, 20),

// Logic:
$offset = $params['page'] * $params['perpage'];
$items = $DB->get_records('local_myplugin_items', $conditions,
    'timecreated DESC', '*', $offset, $params['perpage']);
$total = $DB->count_records('local_myplugin_items', $conditions);

// Returns:
'total' => new external_value(PARAM_INT, 'Total matching items'),
'items' => new external_multiple_structure(/* ... */),
```

## Rules

1. **Always validate parameters first** — call `self::validate_parameters()`
2. **Always validate context** — call `self::validate_context()`
3. **Always check capabilities** — call `require_capability()` or `has_capability()`
4. **Use `format_string()` / `format_text()`** for user-generated text in returns
5. **Set `'ajax' => true`** in services.php for AJAX-callable functions
6. **One class per function** in `classes/external/` — never use `externallib.php` (deprecated)
7. **Function names** follow `component_action` pattern: `local_myplugin_get_items`
8. **Bump version** after changing `db/services.php` — requires upgrade to register
