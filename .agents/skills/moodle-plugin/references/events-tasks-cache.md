# Events, Hooks, Tasks & Caching

> Route event-driven and background work correctly: events record what happened, hooks customize behavior, tasks run async work, and MUC caches reduce repeated cost.

## Contents

- Decision guide
- Events
- Hooks
- Tasks
- Caching
- Selection rules

## Decision Guide

| Need | Use |
|---|---|
| Record that something already happened | Event |
| Let another plugin alter or extend behavior at a known point | Hook |
| Run work later or on a schedule | Adhoc or scheduled task |
| Avoid repeated expensive computation | MUC cache |

## Events

Events log what happened in Moodle. They are fired after an action and consumed by observers, logstore, and analytics.

### Defining an Event

```php
<?php
// classes/event/item_created.php
namespace local_myplugin\event;

use core\event\base;

/**
 * Event fired when an item is created.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_created extends base {

    protected function init(): void {
        $this->data['objecttable'] = 'local_myplugin_items';
        $this->data['crud'] = 'c';           // c=create, r=read, u=update, d=delete
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        // LEVEL_TEACHING, LEVEL_PARTICIPATING, LEVEL_OTHER
    }

    public static function get_name(): string {
        return get_string('event_item_created', 'local_myplugin');
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' created item with id '{$this->objectid}' " .
               "in course '{$this->courseid}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/myplugin/view.php', [
            'id' => $this->objectid,
        ]);
    }

    public static function get_objectid_mapping(): array {
        return ['db' => 'local_myplugin_items', 'restore' => 'local_myplugin_item'];
    }
}
```

### Firing an Event

```php
$event = \local_myplugin\event\item_created::create([
    'objectid' => $item->id,
    'context'  => $context,
    'courseid' => $courseid,
    'other'    => ['name' => $item->name],
]);
$event->add_record_snapshot('local_myplugin_items', $item);
$event->trigger();
```

### Observing Events (db/events.php)

```php
<?php
// db/events.php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_created',
        'callback'  => '\local_myplugin\observer::course_module_created',
    ],
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback'  => '\local_myplugin\observer::user_enrolled',
        'priority'  => 200,   // Higher = runs first (default 0).
    ],
];
```

```php
<?php
// classes/observer.php
namespace local_myplugin;

class observer {
    public static function course_module_created(\core\event\course_module_created $event): void {
        $data = $event->get_data();
        // React to the new module.
    }

    public static function user_enrolled(\core\event\user_enrolment_created $event): void {
        // React to user enrolment.
    }
}
```

## Hooks API

The Hooks API is the modern replacement for many legacy `lib.php` callbacks. Use it when a real hook exists and the target Moodle version supports it.

### Why Hooks?

- Autoloaded classes instead of callback-heavy `lib.php`
- Multiple plugins can listen to the same extension point
- Priority-based execution ordering
- Type-safe signatures and better IDE support
- Clearer migration path from older callback systems

### Step 1 — Define a Hook Class

```php
<?php
// classes/hook/before_item_deleted.php
namespace local_myplugin\hook;

/**
 * Hook dispatched before an item is deleted.
 *
 * Other plugins can listen to prevent deletion or perform cleanup.
 *
 * @package    local_myplugin
 */
#[\core\attribute\label('Dispatched before an item is deleted')]
#[\core\attribute\tags('item', 'local_myplugin')]
final class before_item_deleted implements \Psr\EventDispatcher\StoppableEventInterface {

    use \core\hook\stoppable_trait;

    /** @var string|null Reason if deletion was prevented. */
    private ?string $preventreason = null;

    public function __construct(
        public readonly int $itemid,
        public readonly int $courseid,
        public readonly \stdClass $item,
    ) {
    }

    /**
     * Prevent the deletion.
     *
     * @param string $reason Reason for prevention.
     */
    public function prevent_delete(string $reason): void {
        $this->preventreason = $reason;
        $this->stop_propagation();
    }

    public function is_prevented(): bool {
        return $this->preventreason !== null;
    }

    public function get_prevent_reason(): ?string {
        return $this->preventreason;
    }
}
```

### Step 2 — Dispatch the Hook

```php
use local_myplugin\hook\before_item_deleted;

$hook = new before_item_deleted(
    itemid: $item->id,
    courseid: $item->courseid,
    item: $item,
);

// Dispatch via DI (Moodle 4.4+):
\core\di::get(\core\hook\manager::class)->dispatch($hook);

// If you must support older dispatch style:
// \core\hook\manager::get_instance()->dispatch($hook);

if ($hook->is_prevented()) {
    throw new \moodle_exception('deleteprevented', 'local_myplugin', '', $hook->get_prevent_reason());
}

// Proceed with deletion.
$DB->delete_records('local_myplugin_items', ['id' => $item->id]);
```

### Step 3 — Register a Callback (db/hooks.php)

```php
<?php
// db/hooks.php (in the LISTENING plugin, e.g., local_otherplugin)
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    // Class-based callback (preferred):
    [
        'hook'     => \local_myplugin\hook\before_item_deleted::class,
        'callback' => \local_otherplugin\hook_callbacks::class . '::before_item_deleted',
        'priority' => 500,  // Higher = runs first.
    ],

    // Array notation callback (Moodle 4.4+):
    [
        'hook'     => \core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => [\local_otherplugin\hooks::class, 'inject_banner'],
    ],
];
```

### Step 4 — Implement the Callback

```php
<?php
namespace local_otherplugin;

use local_myplugin\hook\before_item_deleted;

class hook_callbacks {
    public static function before_item_deleted(before_item_deleted $hook): void {
        if ($hook->item->status === 1) {
            $hook->prevent_delete('Active items cannot be deleted yet.');
        }
    }
}
```

### Hook Rules

- Prefer hooks when a modern hook exists.
- Keep legacy callbacks only when the plugin type still requires them.
- Use `db/hooks.php` for registration and autoloaded classes for listeners.
- If you are unsure whether a hook exists, open `docs/apis/core/hooks/index.md` via [docs-map.md](docs-map.md).

## Tasks

Use tasks for work that should not run during a user request or that needs recurring execution.

### Scheduled Tasks

Register in `db/tasks.php`:

```php
$tasks = [
    [
        'classname' => '\local_myplugin\task\cleanup',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
```

```php
<?php
namespace local_myplugin\task;

class cleanup extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_cleanup', 'local_myplugin');
    }

    public function execute(): void {
        // Do background cleanup here.
    }
}
```

### Adhoc Tasks

Use adhoc tasks for one-off background work:

```php
$task = new \local_myplugin\task\rebuild_item_cache();
$task->set_custom_data(['courseid' => $courseid]);
\core\task\manager::queue_adhoc_task($task);
```

### Task Rules

- Move slow or bulk work out of request/response flows.
- If existing page, form, observer, or external API code is doing heavy background-style work, remove that work from the request path and queue a task instead.
- Keep task payloads small and serializable.
- Re-check permissions and data assumptions inside the task if needed.
- If task definitions changed, run `upgrade.php` and `purge_caches.php`.

## Caching

Use MUC when data is expensive to compute or repeatedly fetched.

Minimal `db/caches.php` example:

```php
$definitions = [
    'itemsummary' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
```

Usage:

```php
$cache = \cache::make('local_myplugin', 'itemsummary');
$value = $cache->get($courseid);
if ($value === false) {
    $value = $this->build_summary($courseid);
    $cache->set($courseid, $value);
}
```

### Cache Rules

- Cache derived data, not source-of-truth data.
- Invalidate or update caches when writes occur.
- Choose application, session, or request mode based on scope.

## Selection Rules

- Event if you need an audit trail or post-action notification.
- Hook if you need extension, interception, or customization.
- Task if the work can be delayed or retried.
- Cache if repeated reads dominate and recomputation is wasteful.

```php
<?php
// classes/hook_callbacks.php (in local_otherplugin)
namespace local_otherplugin;

class hook_callbacks {

    public static function before_item_deleted(\local_myplugin\hook\before_item_deleted $hook): void {
        global $DB;

        // Check if this item is referenced.
        $references = $DB->count_records('local_otherplugin_refs', ['itemid' => $hook->itemid]);
        if ($references > 0) {
            $hook->prevent_delete("Item has {$references} references in other plugin.");
        }
    }
}
```

### Core Hooks (Common)

| Hook Class | When |
|-----------|------|
| `\core\hook\output\before_standard_top_of_body_html_generation` | Inject content at top of body |
| `\core\hook\output\before_footer_html_generation` | Inject content before footer |
| `\core\hook\output\before_http_headers` | Modify HTTP headers |
| `\core\hook\navigation\primary_extend` | Add items to primary navigation |
| `\core\hook\access\after_capability_loaded` | Modify capabilities dynamically |

### Migrating lib.php Callbacks to Hooks

Many traditional `lib.php` callbacks now have hook equivalents. When a `lib.php` callback has a deprecated tag pointing to a hook, use the hook instead.

```php
// OLD (lib.php — deprecated):
function local_myplugin_before_standard_top_of_body_html() {
    return '<div class="banner">Hello</div>';
}

// NEW (hook callback):
// In db/hooks.php:
[
    'hook'     => \core\hook\output\before_standard_top_of_body_html_generation::class,
    'callback' => [\local_myplugin\hooks::class, 'add_banner'],
],

// In classes/hooks.php:
public static function add_banner(
    \core\hook\output\before_standard_top_of_body_html_generation $hook
): void {
    $hook->add_html('<div class="banner">Hello</div>');
}
```

---

## Scheduled Tasks

Run periodically on cron.

### Define in db/tasks.php

```php
<?php
// db/tasks.php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => \local_myplugin\task\cleanup_old_items::class,
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '3',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
];
```

### Task Class

```php
<?php
// classes/task/cleanup_old_items.php
namespace local_myplugin\task;

/**
 * Scheduled task to remove old items.
 *
 * @package    local_myplugin
 */
class cleanup_old_items extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_cleanup', 'local_myplugin');
    }

    public function execute(): void {
        global $DB;

        $cutoff = time() - (90 * DAYSECS);
        $deleted = $DB->delete_records_select(
            'local_myplugin_items',
            'status = :status AND timemodified < :cutoff',
            ['status' => 0, 'cutoff' => $cutoff]
        );

        mtrace("Cleaned up {$deleted} old items.");
    }
}
```

---

## Adhoc Tasks

Run once, queued by code. Use for expensive operations that shouldn't block the request.

```php
<?php
// classes/task/send_notification.php
namespace local_myplugin\task;

/**
 * Adhoc task to send a notification.
 *
 * @package    local_myplugin
 */
class send_notification extends \core\task\adhoc_task {

    public function execute(): void {
        $data = $this->get_custom_data();

        // $data->userid, $data->itemid, etc.
        $user = \core_user::get_user($data->userid);
        if (!$user || $user->deleted) {
            return;
        }

        // Send message via messaging API.
        $message = new \core\message\message();
        $message->component = 'local_myplugin';
        $message->name = 'item_notification';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = 'Item Update';
        $message->fullmessage = "Item {$data->itemid} was updated.";
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = "<p>Item {$data->itemid} was updated.</p>";
        $message->smallmessage = 'Item updated.';
        $message->notification = 1;

        message_send($message);
    }
}
```

### Queueing an Adhoc Task

```php
$task = new \local_myplugin\task\send_notification();
$task->set_custom_data((object) [
    'userid' => $userid,
    'itemid' => $item->id,
]);
$task->set_userid($userid);
\core\task\manager::queue_adhoc_task($task);
```

---

## MUC — Moodle Universal Cache

### Cache Modes

| Mode | Constant | Shared | Persists | Use For |
|------|----------|--------|----------|---------|
| Application | `cache_store::MODE_APPLICATION` | All users | Until purged | Config, definitions, slow DB queries |
| Session | `cache_store::MODE_SESSION` | Per user session | Session lifetime | User-specific computed data |
| Request | `cache_store::MODE_REQUEST` | Per request | Request only | Repeated lookups in one request |

### Define Cache in db/caches.php

```php
<?php
// db/caches.php
defined('MOODLE_INTERNAL') || die();

$definitions = [
    'items' => [
        'mode'            => cache_store::MODE_APPLICATION,
        'simplekeys'      => true,    // Keys are simple strings — faster.
        'simpledata'      => false,   // Data needs serialisation.
        'staticacceleration' => true, // In-memory cache within one request.
        'staticaccelerationsize' => 100,
    ],
    'user_prefs' => [
        'mode'       => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
```

### Using Cache

```php
// Get cache instance:
$cache = \cache::make('local_myplugin', 'items');

// Read:
$item = $cache->get('item_42');
if ($item === false) {
    // Cache miss — load from DB.
    $item = $DB->get_record('local_myplugin_items', ['id' => 42]);
    $cache->set('item_42', $item);
}

// Write:
$cache->set('item_42', $item);

// Delete:
$cache->delete('item_42');

// Multiple operations:
$items = $cache->get_many(['item_1', 'item_2', 'item_3']);
$cache->set_many(['item_1' => $obj1, 'item_2' => $obj2]);
$cache->delete_many(['item_1', 'item_2']);

// Purge all:
$cache->purge();
```

### Invalidation Events

```php
// In db/caches.php, add:
'items' => [
    'mode'             => cache_store::MODE_APPLICATION,
    'invalidationevents' => ['local_myplugin_items_changed'],
],

// To trigger invalidation:
cache_helper::invalidate_by_event('local_myplugin_items_changed', ['courseid' => $courseid]);

// Or purge the entire definition:
cache_helper::purge_by_definition('local_myplugin', 'items');
```

## Rules

1. **Events**: Fire events AFTER the action succeeds, not before
2. **Hooks**: Use hooks for cross-plugin communication; events for logging/analytics
3. **Scheduled tasks**: Always include `mtrace()` output for cron logs
4. **Adhoc tasks**: Use for anything that takes >1 second (notifications, bulk ops)
5. **Cache**: Always handle cache miss (return `false`); never assume cache has data
6. **Bump version** after adding `db/events.php`, `db/hooks.php`, `db/tasks.php`, or `db/caches.php`
