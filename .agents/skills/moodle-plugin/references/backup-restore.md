# Backup & Restore

> How to make plugin data survive course backup/restore and duplication.

## When You Need This

If your plugin stores data in custom DB tables that are associated with courses, activities, or users, you must implement backup/restore so that data is preserved when a course is:

- Backed up and restored
- Duplicated
- Imported

## File Layout

```
local/myplugin/
└── backup/
    └── moodle2/
        ├── backup_local_myplugin_plugin.class.php
        └── restore_local_myplugin_plugin.class.php
```

> For activity modules (`mod_`), use `backup_mod_xxx_activity_task.class.php` instead. The pattern below shows the **local plugin** approach; `mod_` uses a different base class but the same structural concepts.

## Backup Class

```php
<?php
// backup/moodle2/backup_local_myplugin_plugin.class.php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_local_plugin.class.php');

/**
 * Backup plugin for local_myplugin.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_myplugin_plugin extends backup_local_plugin {

    /**
     * Define the plugin structure for backup.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure(): backup_plugin_element {
        // Create the plugin element (wrapper).
        $plugin = $this->get_plugin_element(null, null, null);

        // Create the plugin wrapper element.
        $wrapper = new backup_nested_element('local_myplugin_items');

        // Define the source table and fields.
        $item = new backup_nested_element('item', ['id'], [
            'name',
            'description',
            'descriptionformat',
            'status',
            'usermodified',
            'timecreated',
            'timemodified',
        ]);

        // Build the tree: plugin -> wrapper -> item.
        $plugin->add_child($wrapper);
        $wrapper->add_child($item);

        // Set the source for the item element.
        $item->set_source_table('local_myplugin_items', [
            'courseid' => backup::VAR_COURSEID,
        ]);

        // Annotate user IDs (for user-mapping on restore).
        $item->annotate_ids('user', 'usermodified');

        // Annotate files (if items have file areas).
        $item->annotate_files('local_myplugin', 'attachment', 'id');
        $item->annotate_files('local_myplugin', 'description', 'id');

        return $plugin;
    }
}
```

## Restore Class

```php
<?php
// backup/moodle2/restore_local_myplugin_plugin.class.php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_local_plugin.class.php');

/**
 * Restore plugin for local_myplugin.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_myplugin_plugin extends restore_local_plugin {

    /**
     * Define the restore structure.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure(): array {
        $paths = [];

        $paths[] = new restore_path_element(
            'local_myplugin_item',
            $this->get_pathfor('/local_myplugin_items/item')
        );

        return $paths;
    }

    /**
     * Process a single item record.
     *
     * @param array|object $data The item data from backup.
     */
    public function process_local_myplugin_item($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        // Map course ID to the new course.
        $data->courseid = $this->task->get_courseid();

        // Map user ID.
        $data->usermodified = $this->get_mappingid('user', $data->usermodified) ?: 0;

        // Adjust timestamps if needed.
        $data->timecreated = time();
        $data->timemodified = time();

        // Insert with new ID.
        $newid = $DB->insert_record('local_myplugin_items', $data);

        // Store the mapping (old ID -> new ID) for file restoration.
        $this->set_mapping('local_myplugin_item', $oldid, $newid, true);
    }

    /**
     * After restore — handle file mappings.
     */
    public function after_restore_course(): void {
        // Restore files for the 'attachment' file area.
        $this->add_related_files('local_myplugin', 'attachment', 'local_myplugin_item');
        $this->add_related_files('local_myplugin', 'description', 'local_myplugin_item');
    }
}
```

## Activity Module Backup (mod_)

For `mod_` plugins, the structure is different:

```
mod/myplugin/
└── backup/
    └── moodle2/
        ├── backup_myplugin_stepslib.php       # Backup step definitions
        ├── backup_myplugin_activity_task.class.php  # Task orchestrator
        ├── restore_myplugin_stepslib.php      # Restore step definitions
        └── restore_myplugin_activity_task.class.php # Restore task
```

The key difference: `mod_` backup integrates with the activity backup lifecycle and must define `backup_activity_structure_step` and `restore_activity_structure_step`.

## Key Concepts

| Concept | Purpose |
|---------|---------|
| `backup_nested_element` | Defines the XML structure for backup data |
| `annotate_ids('user', 'field')` | Marks a field as a user ID for remapping on restore |
| `annotate_files('component', 'area', 'idfield')` | Includes files from a file area in the backup |
| `$this->get_mappingid('user', $oldid)` | Gets the new ID for a mapped entity during restore |
| `$this->set_mapping('type', $oldid, $newid)` | Stores old→new ID mapping for use by other restore steps |
| `add_related_files()` | Restores files using the stored mappings |

## Rules

1. Always annotate user IDs — restoration maps old users to new ones
2. Always annotate files — otherwise file areas won't be backed up
3. Store mappings during restore — needed for file restoration and cross-references
4. Reset timestamps on restore if appropriate for your use case
5. Test with course duplication — it uses the same backup/restore mechanism
