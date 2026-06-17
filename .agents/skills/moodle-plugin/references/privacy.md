# Privacy API (GDPR Compliance)

> Implementing the Privacy API so your plugin properly exports and deletes user data.

## When You Need This

**Every plugin that stores, processes, or has access to personal user data** must implement the Privacy API. This includes any plugin with:

- Database tables containing `userid` or `usermodified` columns
- User preferences (`set_user_preference`)
- Stored files linked to users
- Subsystem data (e.g., comments, ratings, tags)

## File Layout

```
local/myplugin/
└── classes/
    └── privacy/
        └── provider.php
```

## Full Implementation

```php
<?php
// classes/privacy/provider.php
namespace local_myplugin\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;

/**
 * Privacy provider for local_myplugin.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the types of personal data stored.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        // Database table.
        $collection->add_database_table('local_myplugin_items', [
            'courseid'          => 'privacy:metadata:items:courseid',
            'name'              => 'privacy:metadata:items:name',
            'description'       => 'privacy:metadata:items:description',
            'usermodified'      => 'privacy:metadata:items:usermodified',
            'timecreated'       => 'privacy:metadata:items:timecreated',
            'timemodified'      => 'privacy:metadata:items:timemodified',
        ], 'privacy:metadata:items');

        // User preferences (if any).
        $collection->add_user_preference(
            'local_myplugin_view_mode',
            'privacy:metadata:preference:viewmode'
        );

        // External systems (if any).
        // $collection->add_external_location_link('externalservice', [...], 'privacy:metadata:external');

        // Subsystems (if you use comments, ratings, tags, etc.).
        // $collection->add_subsystem_link('core_comment', [], 'privacy:metadata:comments');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user data.
     *
     * @param int $userid The user ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_myplugin_items} i ON i.courseid = c.id
                 WHERE i.usermodified = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid'       => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users within a context.
     *
     * @param userlist $userlist The userlist to populate.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT i.usermodified AS userid
                  FROM {local_myplugin_items} i
                 WHERE i.courseid = :courseid";

        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    /**
     * Export user data for the given contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $items = $DB->get_records('local_myplugin_items', [
                'courseid'     => $context->instanceid,
                'usermodified' => $userid,
            ]);

            foreach ($items as $item) {
                $subcontext = [
                    get_string('pluginname', 'local_myplugin'),
                    $item->id,
                ];

                $data = (object) [
                    'name'        => $item->name,
                    'description' => $item->description,
                    'timecreated' => transform::datetime($item->timecreated),
                    'timemodified' => transform::datetime($item->timemodified),
                ];

                writer::with_context($context)->export_data($subcontext, $data);

                // Export associated files.
                helper::export_context_files($context, $subcontext);
            }
        }
    }

    /**
     * Delete all user data in the given context.
     *
     * @param \context $context The context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records('local_myplugin_items', ['courseid' => $context->instanceid]);

        // Delete files.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_myplugin');
    }

    /**
     * Delete user data for a specific user in the given contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $DB->delete_records('local_myplugin_items', [
                'courseid'     => $context->instanceid,
                'usermodified' => $userid,
            ]);
        }
    }

    /**
     * Delete user data for multiple users in a context.
     *
     * @param approved_userlist $userlist The approved userlist.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $context->instanceid;

        $DB->delete_records_select(
            'local_myplugin_items',
            "courseid = :courseid AND usermodified $insql",
            $params
        );
    }
}
```

## Language Strings

Add to `lang/en/local_myplugin.php`:

```php
$string['privacy:metadata:items'] = 'Items created by users in the plugin.';
$string['privacy:metadata:items:courseid'] = 'The course the item belongs to.';
$string['privacy:metadata:items:name'] = 'The name of the item.';
$string['privacy:metadata:items:description'] = 'The description of the item.';
$string['privacy:metadata:items:usermodified'] = 'The user who created or last modified the item.';
$string['privacy:metadata:items:timecreated'] = 'When the item was created.';
$string['privacy:metadata:items:timemodified'] = 'When the item was last modified.';
$string['privacy:metadata:preference:viewmode'] = 'The preferred view mode for the item list.';
```

## Interfaces Reference

| Interface | When to Implement |
|-----------|-------------------|
| `metadata\provider` | **Always** — describes what data your plugin stores |
| `plugin\provider` | When you store data in your own tables |
| `core_userlist_provider` | **Always** with `plugin\provider` — supports bulk user operations |
| `null_provider` | When your plugin stores NO personal data at all |

### Null Provider (No Data)

If your plugin stores no user data:

```php
class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
```

## Testing Privacy

```bash
# Run the built-in privacy test:
vendor/bin/phpunit privacy/tests/provider_test.php

# Run your plugin's privacy tests:
vendor/bin/phpunit --group local_myplugin
```

The core `provider_test` automatically checks that:
- `get_metadata()` returns valid metadata
- `get_contexts_for_userid()` returns contexts
- Export and delete functions work without errors

## Rules

1. **Every plugin** must have a privacy provider — either `null_provider` or full implementation
2. All user-facing metadata strings must be descriptive and translatable
3. `delete_data_for_users()` must handle the bulk case efficiently (use IN clause)
4. Export must use `transform::datetime()` for timestamps
5. Delete functions must also clean up associated files
