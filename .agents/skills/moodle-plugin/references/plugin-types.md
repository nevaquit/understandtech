# Plugin Types — Type-Specific Patterns

> Patterns for mod_, block_, local_, report_, tool_, theme_ and other plugin types.

## Contents

- `mod_`
- `block_`
- `local_`
- `report_` and `tool_`
- `theme_`
- `enrol_`
- `format_`
- `availability_`
- `fileconverter_`
- `qtype_`
- `quizaccess_`
- `tiny_`
- assignment extension plugins
- plugin-type decision notes

## Activity Module (mod_)

Activity modules are the richest plugin type. They appear as resources/activities in a course.

### Required Files

```
mod/myplugin/
├── mod_form.php              # Activity settings form
├── lib.php                   # Core callbacks (add, update, delete, features)
├── view.php                  # Student view page
├── index.php                 # List all instances in a course
├── version.php               # Plugin version
├── db/
│   ├── install.xml           # Schema — must have matching table name
│   ├── access.php            # Capabilities
│   └── services.php          # External functions (optional)
├── classes/
│   └── ...                   # Autoloaded classes
├── lang/en/
│   └── myplugin.php          # Language strings
├── pix/
│   ├── monologo.svg          # Activity icon (required, monochrome)
│   └── icon.svg              # Activity icon (legacy fallback)
└── backup/moodle2/           # Backup/restore (required for course duplication)
```

### lib.php — Required Callbacks

```php
<?php
// mod/myplugin/lib.php

/**
 * Add a new instance (called when teacher adds the activity).
 *
 * @param stdClass $data Form data from mod_form.
 * @param mod_myplugin_mod_form $form The form instance.
 * @return int New instance ID.
 */
function myplugin_add_instance(stdClass $data, mod_myplugin_mod_form $form = null): int {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = time();
    $data->id = $DB->insert_record('myplugin', $data);

    // Handle intro editor files.
    $cmid = $data->coursemodule;
    $context = context_module::instance($cmid);
    if (isset($data->introeditor)) {
        $data->intro = file_save_draft_area_files(
            $data->introeditor['itemid'], $context->id, 'mod_myplugin', 'intro', 0,
            ['subdirs' => false], $data->introeditor['text']
        );
        $data->introformat = $data->introeditor['format'];
        $DB->update_record('myplugin', $data);
    }

    return $data->id;
}

/**
 * Update an existing instance.
 */
function myplugin_update_instance(stdClass $data, mod_myplugin_mod_form $form = null): bool {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('myplugin', $data);
}

/**
 * Delete an instance.
 */
function myplugin_delete_instance(int $id): bool {
    global $DB;
    if (!$DB->record_exists('myplugin', ['id' => $id])) {
        return false;
    }
    $DB->delete_records('myplugin', ['id' => $id]);
    return true;
}

/**
 * Declare supported features.
 */
function myplugin_supports(string $feature): ?bool {
    return match ($feature) {
        FEATURE_MOD_INTRO               => true,
        FEATURE_SHOW_DESCRIPTION        => true,
        FEATURE_BACKUP_MOODLE2          => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GRADE_HAS_GRADE         => false,
        FEATURE_MOD_PURPOSE             => MOD_PURPOSE_COLLABORATION,
        FEATURE_QUICKCREATE             => false,  // Moodle 5.0+ — skip form, create with defaults
        FEATURE_MOD_OTHERPURPOSE        => null,   // Moodle 5.x — secondary classification
        default                         => null,
    };
}
```

### MOD_PURPOSE Constants (Moodle 4.0+)

| Constant                             | Color/Category          |
| ------------------------------------ | ----------------------- |
| `MOD_PURPOSE_ADMINISTRATION`         | Grey                    |
| `MOD_PURPOSE_ASSESSMENT`             | Pink/red                |
| `MOD_PURPOSE_COLLABORATION`          | Purple                  |
| `MOD_PURPOSE_COMMUNICATION`          | Pink                    |
| `MOD_PURPOSE_CONTENT`                | Green                   |
| `MOD_PURPOSE_INTERFACE`              | Blue                    |
| `MOD_PURPOSE_INTERACTIVECONTENT`     | (Moodle 5.x+) Interactive learning |
| `MOD_PURPOSE_OTHER`                  | Grey (default)          |

### FEATURE Constants Reference

| Constant | Purpose |
|----------|---------|
| `FEATURE_MOD_INTRO` | Supports intro editor on settings form |
| `FEATURE_SHOW_DESCRIPTION` | Show description on course page |
| `FEATURE_BACKUP_MOODLE2` | Supports backup/restore |
| `FEATURE_COMPLETION_TRACKS_VIEWS` | Activity completion by view |
| `FEATURE_GRADE_HAS_GRADE` | Pushes grades to gradebook |
| `FEATURE_GROUPS` | Supports groups |
| `FEATURE_GROUPINGS` | Supports groupings |
| `FEATURE_MOD_PURPOSE` | Activity purpose classification |
| `FEATURE_QUICKCREATE` | (5.0+) Create without settings form — instance from defaults |
| `FEATURE_MOD_OTHERPURPOSE` | (5.x+) Secondary purpose classification |

### index.php — Activity Overview Base (Moodle 5.0+)

Since Moodle 5.0, activity `index.php` pages that list all instances in a course should redirect to the course page with the activity filter applied. Use `activityoverviewbase`:

```php
<?php
// mod/myplugin/index.php
require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.
$course = get_course($id);
require_login($course);

// Redirect to course page with mod filter — standard in Moodle 5.0+.
$overview = new \core_course\output\activityoverviewbase($course, 'myplugin');
$overview->redirect();
```

### Mobile Support (db/mobile.php)

Activity modules can define a mobile app interface via `db/mobile.php`:

```php
<?php
defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_myplugin' => [
        'handlers' => [
            'coursemyview' => [
                'displaydata' => [
                    'title' => 'pluginname',
                    'icon'  => $CFG->wwwroot . '/mod/myplugin/pix/icon.svg',
                    'class' => '',
                ],
                'delegate'  => 'CoreCourseModuleDelegate',
                'method'    => 'mobile_course_view',
                'offlinefunctions' => [
                    'mod_myplugin_get_instance' => [],
                ],
            ],
        ],
        'lang' => [
            ['pluginname', 'myplugin'],
        ],
    ],
];
```

### mod_form.php

```php
<?php
// mod/myplugin/mod_form.php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_myplugin_mod_form extends moodleform_mod {

    public function definition(): void {
        $mform = $this->_form;

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Standard intro editor.
        $this->standard_intro_elements();

        // Custom fields.
        $mform->addElement('header', 'mypluginheader', get_string('settings', 'myplugin'));
        $mform->addElement('select', 'status', get_string('status', 'myplugin'), [
            0 => get_string('draft', 'myplugin'),
            1 => get_string('published', 'myplugin'),
        ]);

        // Standard course module elements (availability, completion, etc.).
        $this->standard_coursemodule_elements();

        // Action buttons.
        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty(trim($data['name']))) {
            $errors['name'] = get_string('required');
        }
        return $errors;
    }
}
```

### view.php Pattern

```php
<?php
// mod/myplugin/view.php
require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.

$cm     = get_coursemodule_from_id('myplugin', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$instance = $DB->get_record('myplugin', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/myplugin:view', $context);

// Trigger view event.
$event = \mod_myplugin\event\course_module_viewed::create([
    'objectid' => $instance->id,
    'context'  => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('myplugin', $instance);
$event->trigger();

// Completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Page setup.
$PAGE->set_url('/mod/myplugin/view.php', ['id' => $id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name));

// Render content.
$renderable = new \mod_myplugin\output\view_page($instance, $context);
$renderer = $PAGE->get_renderer('mod_myplugin');
echo $renderer->render($renderable);

echo $OUTPUT->footer();
```

---

## Block Plugin (block_)

Blocks display content in sidebars or on dashboards. Two base classes exist:

- **`block_base`** — standard block with free-form HTML content
- **`block_list`** — block that renders a list of items (icons + text links)

```
block/myplugin/
├── block_myplugin.php         # Main block class
├── edit_form.php              # Per-instance configuration form (optional)
├── version.php
├── settings.php               # Global block settings (optional, requires has_config)
├── lang/en/block_myplugin.php
├── db/access.php              # Capabilities
├── classes/
│   └── ...
└── templates/
    └── content.mustache
```

### Block Class

```php
<?php
// block_myplugin.php
class block_myplugin extends block_base {

    public function init(): void {
        $this->title = get_string('pluginname', 'block_myplugin');
    }

    /**
     * Called after instance data is loaded — use to customise title per instance.
     */
    public function specialization(): void {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    public function get_content(): stdClass {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $renderable = new \block_myplugin\output\content($this->config);
        $renderer = $this->page->get_renderer('block_myplugin');
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Where this block can appear. Page type formats:
     * - 'all'              → everywhere
     * - 'site-index'       → front page only
     * - 'course-view'      → any course page (any format)
     * - 'course-view-*'    → specific course format (e.g. course-view-weeks)
     * - 'mod'              → any activity page
     * - 'mod-quiz-*'       → specific activity type pages
     * - 'my'               → dashboard
     * - 'admin'            → admin pages
     */
    public function applicable_formats(): array {
        return [
            'all'         => false,
            'course-view' => true,
            'site-index'  => true,
            'my'          => true,
        ];
    }

    /**
     * Return true if this block has global settings (settings.php).
     */
    public function has_config(): bool {
        return true;
    }

    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Set to true to hide the block header.
     */
    public function hide_header(): bool {
        return false;
    }

    /**
     * Add custom HTML attributes to the block wrapper.
     */
    public function html_attributes(): array {
        $attrs = parent::html_attributes();
        $attrs['class'] .= ' block_myplugin-custom';
        return $attrs;
    }
}
```

### Per-Instance Configuration (edit_form.php)

Instance config fields are stored automatically. Field names **must** use the `config_` prefix:

```php
<?php
// block/myplugin/edit_form.php
defined('MOODLE_INTERNAL') || die();

class block_myplugin_edit_form extends block_edit_form {
    protected function specific_definition($mform): void {
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block_myplugin'));

        // Field name MUST start with "config_" — stored in $this->config->title.
        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_myplugin'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('select', 'config_display_mode', get_string('displaymode', 'block_myplugin'), [
            'compact' => get_string('compact', 'block_myplugin'),
            'full'    => get_string('full', 'block_myplugin'),
        ]);

        // Use advcheckbox instead of checkbox (checkbox doesn't save unchecked state).
        $mform->addElement('advcheckbox', 'config_show_footer', get_string('showfooter', 'block_myplugin'));
        $mform->setDefault('config_show_footer', 1);
    }
}
```

> **Important**: Use `advcheckbox` instead of `checkbox` — standard checkbox doesn't submit when unchecked.

---

## Local Plugin (local_)

The most flexible type — no UI commitment, used for general-purpose functionality.

**Key characteristics:**
- Local plugins are **always executed last** during install/upgrade (after all other plugin types)
- Ideal for **event observers / hook callbacks** — react to events from any other plugin
- Can add admin settings to **any settings page** via navigation callbacks

```
local/myplugin/
├── version.php
├── lang/en/local_myplugin.php
├── db/
│   ├── install.xml           # Optional
│   ├── access.php            # Optional
│   ├── services.php          # Optional
│   ├── hooks.php             # Optional — Hooks API (4.3+)
│   └── tasks.php             # Optional — scheduled/adhoc tasks
├── classes/
│   ├── hook/                 # Hook callback classes
│   ├── task/                 # Task classes
│   ├── external/             # External API classes
│   ├── output/               # Renderables + renderers
│   └── privacy/
│       └── provider.php
├── templates/                # Mustache templates
└── lib.php                   # Navigation callbacks, pluginfile, etc.
```

### Navigation Callbacks

Local plugins can extend the navigation tree and admin settings from `lib.php`:

```php
<?php
// local/myplugin/lib.php

/**
 * Extend the global navigation tree.
 * Called for every page load when navigation is built.
 */
function local_myplugin_extend_navigation(global_navigation $navigation): void {
    if (isloggedin() && !isguestuser()) {
        $node = $navigation->add(
            get_string('pluginname', 'local_myplugin'),
            new moodle_url('/local/myplugin/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_myplugin',
        );
    }
}

/**
 * Extend the settings navigation.
 * Add settings to any page's settings menu.
 */
function local_myplugin_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return;
    }
    if (has_capability('local/myplugin:manage', $context)) {
        $settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
        if ($settingnode) {
            $settingnode->add(
                get_string('pluginname', 'local_myplugin'),
                new moodle_url('/local/myplugin/manage.php', ['courseid' => $context->instanceid]),
                navigation_node::TYPE_SETTING,
                null,
                'local_myplugin_manage',
                new pix_icon('i/settings', ''),
            );
        }
    }
}
```

### settings.php — Admin Settings Page

```php
<?php
// local/myplugin/settings.php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $page = new admin_settingpage(
        'local_myplugin_settings',
        get_string('pluginname', 'local_myplugin')
    );
    $page->add(new admin_setting_configtext(
        'local_myplugin/apikey',
        get_string('apikey', 'local_myplugin'),
        get_string('apikey_desc', 'local_myplugin'),
        '',
        PARAM_ALPHANUMEXT,
    ));
    $ADMIN->add('localplugins', $page);
}
```

---

## Report Plugin (report_)

```
report/myplugin/
├── index.php                 # Main report page
├── version.php
├── lang/en/report_myplugin.php
├── classes/
│   └── output/
│       └── report_table.php  # Renderable
├── templates/
│   └── report.mustache
└── lib.php                   # Navigation callback
```

### Navigation — lib.php

```php
<?php
/**
 * Add report link to course navigation.
 */
function report_myplugin_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context): void {
    if (has_capability('report/myplugin:view', $context)) {
        $url = new moodle_url('/report/myplugin/index.php', ['courseid' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'report_myplugin'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'report_myplugin',
            new pix_icon('i/report', '')
        );
    }
}
```

---

## Admin Tool (tool_)

```
admin/tool/myplugin/
├── index.php                 # Main page
├── version.php
├── settings.php              # Admin settings (auto-loaded)
├── lang/en/tool_myplugin.php
└── classes/
```

### settings.php

```php
<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $page = new admin_externalpage(
        'tool_myplugin',
        get_string('pluginname', 'tool_myplugin'),
        new moodle_url('/admin/tool/myplugin/index.php')
    );
    $ADMIN->add('tools', $page);
}
```

---

## Theme Plugin (theme_)

See [theme-and-output.md](theme-and-output.md) for complete theme development patterns.

---

## Plugin Type Summary

| Type | Prefix | Location | Primary Use |
|------|--------|----------|-------------|
| Activity module | `mod_` | `mod/` | Course activities & resources |
| Block | `block_` | `blocks/` | Side blocks with content |
| Local | `local_` | `local/` | General purpose, no category |
| Report | `report_` | `report/` | Data reports |
| Admin tool | `tool_` | `admin/tool/` | Admin utilities |
| Theme | `theme_` | `theme/` | Visual appearance |
| Auth | `auth_` | `auth/` | Authentication methods |
| Enrol | `enrol_` | `enrol/` | Enrolment methods |
| Repository | `repository_` | `repository/` | File picker sources |
| Question type | `qtype_` | `question/type/` | Quiz question types |
| Course format | `format_` | `course/format/` | Course layouts |
| Assignment submission | `assignsubmission_` | `mod/assign/submission/` | Assignment submission types |
| Assignment feedback | `assignfeedback_` | `mod/assign/feedback/` | Assignment feedback types |
| Enrolment | `enrol_` | `enrol/` | Enrolment methods |
| Availability condition | `availability_` | `availability/condition/` | Restrict access conditions |
| File converter | `fileconverter_` | `files/converter/` | Document format conversion |

---

## Enrolment Plugin (enrol_)

Enrolment plugins manage how users are enrolled/unenrolled from courses. Extend the `enrol_plugin` base class from `lib/enrollib.php`.

### Workflow Types

| Type | How it works | Key method |
|------|-------------|------------|
| **Manual** | Admin/teacher adds users directly | `allow_enrol()`, `allow_unenrol()`, `allow_manage()` |
| **Automatic** | System enrols based on rules (e.g., cohort sync) | Cron task, event observer |
| **Interactive** | Student self-enrols via course page | `show_enrolme_link()`, `enrol_page_hook()` |

### File Structure

```
enrol/myplugin/
├── lib.php                   # Main class extending enrol_plugin
├── version.php
├── lang/en/enrol_myplugin.php
├── db/
│   ├── access.php            # Capabilities (enrol/myplugin:enrol, :unenrol, :manage, :config)
│   └── install.xml           # Optional — custom tables
├── classes/
│   └── ...
└── settings.php              # Plugin settings
```

### lib.php — enrol_plugin Subclass

```php
<?php
// enrol/myplugin/lib.php

class enrol_myplugin_plugin extends enrol_plugin {

    /**
     * Can current user enrol users into this instance?
     */
    public function allow_enrol(stdClass $instance): bool {
        return has_capability('enrol/myplugin:enrol',
            context_course::instance($instance->courseid));
    }

    /**
     * Can current user unenrol users from this instance?
     */
    public function allow_unenrol(stdClass $instance): bool {
        return has_capability('enrol/myplugin:unenrol',
            context_course::instance($instance->courseid));
    }

    /**
     * Can current user manage enrolments in this instance?
     */
    public function allow_manage(stdClass $instance): bool {
        return has_capability('enrol/myplugin:manage',
            context_course::instance($instance->courseid));
    }

    /**
     * Whether roles are protected (cannot be changed by other plugins).
     */
    public function roles_protected(): bool {
        return true;
    }

    // -- Interactive enrolment methods (self-enrol pattern) --

    /**
     * Show "Enrol me" link on course page? Return null to hide.
     */
    public function show_enrolme_link(stdClass $instance): ?bool {
        return true;
    }

    /**
     * Render enrolment form/button on course entry page.
     */
    public function enrol_page_hook(stdClass $instance): ?string {
        // Return HTML form for self-enrolment.
        return '<form ...>...</form>';
    }

    // -- Standard editing UI (Moodle 4.0+) --

    /**
     * Return true to use the standard enrolment instance editing form.
     */
    public function use_standard_editing_ui(): bool {
        return true;
    }

    /**
     * Add fields to the standard editing form.
     */
    public function edit_instance_form($mform, $instance, $context): void {
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);
    }

    /**
     * Validate the instance editing form.
     */
    public function edit_instance_validation($data, $files, $instance, $context): array {
        return [];
    }

    /**
     * Can a new instance be added to a course?
     */
    public function can_add_instance($courseid): bool {
        $context = context_course::instance($courseid);
        return has_capability('moodle/course:enrolconfig', $context)
            && has_capability('enrol/myplugin:config', $context);
    }
}
```

### Capability Naming Convention for Enrolment

| Capability | Purpose |
|-----------|---------|
| `enrol/myplugin:enrol` | Enrol users |
| `enrol/myplugin:unenrol` | Unenrol users |
| `enrol/myplugin:manage` | Manage enrolments (edit suspended, time) |
| `enrol/myplugin:unenrolself` | Allow user to unenrol themselves |
| `enrol/myplugin:config` | Configure enrolment instances |

---

## Availability Condition Plugin (availability_)

Availability conditions restrict access to activities/sections based on custom rules.

### File Structure

```
availability/condition/myplugin/
├── classes/
│   ├── condition.php          # Server-side availability logic
│   └── frontend.php           # Client-side form integration
├── yui/src/form/              # YUI module for editing form UI
│   ├── build/
│   └── js/form.js
├── lang/en/availability_myplugin.php
└── version.php
```

### condition.php

```php
<?php
namespace availability_myplugin;

use core_availability\condition as base_condition;

class condition extends base_condition {

    /** @var int Custom parameter. */
    protected int $threshold;

    public function __construct($structure) {
        $this->threshold = $structure->threshold ?? 0;
    }

    /**
     * Check if the condition is met for the user.
     *
     * @param bool $not True if condition should be negated.
     * @param \core_availability\info $info Availability info.
     * @param bool $grabthelot Optimise for bulk checking.
     * @param int $userid User to check.
     * @return bool True if available.
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid): bool {
        $allow = $this->check_threshold($userid);
        return $not ? !$allow : $allow;
    }

    public function get_description($full, $not, \core_availability\info $info): string {
        return get_string($not ? 'requires_not' : 'requires', 'availability_myplugin',
            $this->threshold);
    }

    public function save(): \stdClass {
        return (object) ['type' => 'myplugin', 'threshold' => $this->threshold];
    }

    public function get_debug_string(): string {
        return "threshold={$this->threshold}";
    }

    private function check_threshold(int $userid): bool {
        // Custom logic here.
        return true;
    }
}
```

### frontend.php

```php
<?php
namespace availability_myplugin;

use core_availability\frontend as base_frontend;

class frontend extends base_frontend {

    protected function get_javascript_strings(): array {
        return ['title', 'description'];
    }

    protected function get_javascript_init_params($course, ?\cm_info $cm = null,
            ?\section_info $section = null): array {
        return [$this->get_threshold_options()];
    }

    protected function allow_add($course, ?\cm_info $cm = null,
            ?\section_info $section = null): bool {
        return true;
    }

    private function get_threshold_options(): array {
        return [10, 20, 50, 100];
    }
}
```

---

## File Converter Plugin (fileconverter_)

File converter plugins handle document format conversion (e.g., DOCX → PDF). Implement `\core_files\converter_interface`.

```php
<?php
namespace fileconverter_myplugin;

use core_files\converter_interface;
use core_files\conversion;

class converter implements converter_interface {

    /**
     * Check if system requirements are met.
     */
    public static function are_requirements_met(): bool {
        // Check for external tool availability, API key, etc.
        return true;
    }

    /**
     * Begin a document conversion.
     */
    public function start_document_conversion(conversion $conversion): void {
        $file = $conversion->get_sourcefile();
        // Start conversion — may be async.
        $conversion->set_status(conversion::STATUS_IN_PROGRESS);
    }

    /**
     * Poll for conversion completion (for async converters).
     */
    public function poll_conversion_status(conversion $conversion): void {
        // Check if conversion is complete.
        $conversion->set_status(conversion::STATUS_COMPLETE);
        // Store the converted file:
        // $conversion->store_destfile_from_path($filepath);
    }

    /**
     * Check if this converter supports a given format pair.
     *
     * @param string $from Source format (e.g., 'docx').
     * @param string $to Target format (e.g., 'pdf').
     * @return bool
     */
    public function supports(string $from, string $to): bool {
        $supported = ['docx' => ['pdf'], 'odt' => ['pdf']];
        return isset($supported[$from]) && in_array($to, $supported[$from]);
    }

    /**
     * Get all supported conversions.
     *
     * @return string[] Array of "from-to" strings.
     */
    public function get_supported_conversions(): array {
        return ['docx-pdf', 'odt-pdf'];
    }
}
```

### Conversion Status Constants

| Constant | Meaning |
|----------|---------|
| `conversion::STATUS_PENDING` | Not yet started |
| `conversion::STATUS_IN_PROGRESS` | Conversion underway |
| `conversion::STATUS_COMPLETE` | Finished successfully |
| `conversion::STATUS_FAILED` | Conversion failed |

---

## Deprecated / Sunset Plugin Types

### Atto Editor Plugin (atto_)

> **Atto was removed in Moodle 5.0.** The TinyMCE editor is the default editor. Do not create new Atto plugins. Migrate existing Atto plugins to TinyMCE (`tiny_`) plugins instead.
