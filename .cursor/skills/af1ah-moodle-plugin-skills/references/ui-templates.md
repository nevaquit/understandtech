# UI: Mustache Templates, Bootstrap 5, & Output Classes

> Templates, renderables, renderers, and the Moodle Output API.

## Output Architecture (Renderable → Renderer → Template)

```
PHP Page → creates Renderable → passes to Renderer → renders Mustache Template → HTML
```

### 1. Renderable (Data Object)

```php
<?php
namespace local_myplugin\output;

use renderable;
use renderer_base;
use templatable;
use moodle_url;

class item_list implements renderable, templatable {
    public function __construct(
        private readonly array $items,
        private readonly int   $courseid,
        private readonly bool  $canmanage = false,
    ) {}

    public function export_for_template(renderer_base $output): array {
        $data = [
            'items'     => [],
            'hasitems'  => !empty($this->items),
            'canmanage' => $this->canmanage,
            'addurl'    => (new moodle_url('/local/myplugin/edit.php',
                            ['courseid' => $this->courseid]))->out(false),
        ];
        foreach ($this->items as $item) {
            $data['items'][] = [
                'id'          => (int) $item->id,
                'name'        => format_string($item->name),
                'description' => format_text($item->description, $item->descriptionformat),
                'isactive'    => ((int) $item->status === 1),
                'timecreated' => userdate($item->timecreated),
                'editurl'     => (new moodle_url('/local/myplugin/edit.php',
                                  ['id' => $item->id]))->out(false),
                'deleteurl'   => (new moodle_url('/local/myplugin/delete.php',
                                  ['id' => $item->id, 'sesskey' => sesskey()]))->out(false),
            ];
        }
        return $data;
    }
}
```

> **`export_for_template`** must return only simple types: arrays, `stdClass`, `bool`, `int`, `float`, `string`, or objects implementing `Stringable`.

### Using `named_templatable` (Auto-Routing)

If you implement `named_templatable` instead of `templatable`, you can skip writing a custom renderer — Moodle routes automatically:

```php
use core\output\named_templatable;

class item_list implements renderable, named_templatable {
    // ...same as above...

    public function get_template_name(\renderer_base $renderer): string {
        return 'local_myplugin/item_list';
    }
}
```

### 2. Renderer

```php
<?php
namespace local_myplugin\output;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    public function render_item_list(item_list $list): string {
        $data = $list->export_for_template($this);
        return $this->render_from_template('local_myplugin/item_list', $data);
    }
}
```

> You do **not** need a renderer if using `named_templatable` or if the template name matches the renderable's Frankenstyle class path.

### 3. Calling from a Page

```php
// Traditional:
$renderer = $PAGE->get_renderer('local_myplugin');
echo $renderer->render(new \local_myplugin\output\item_list($items, $courseid, $canmanage));

// With DI (Moodle 5.0+):
$helper = \core\di::get(\core\output\renderer_helper::class);
$renderer = $helper->get_renderer('local_myplugin');
echo $renderer->render($renderable);
```

## Mustache Template Reference

### File Location

`templates/item_list.mustache` — maps to `local_myplugin/item_list`.

### Template Example

```mustache
{{!
    @template local_myplugin/item_list

    Context variables:
    - hasitems (bool)
    - canmanage (bool)
    - addurl (string)
    - items[] (id, name, description, isactive, timecreated, editurl, deleteurl)

    Example context (JSON):
    {
        "hasitems": true,
        "canmanage": true,
        "addurl": "/local/myplugin/edit.php?courseid=2",
        "items": [{"id": 1, "name": "Test", "isactive": true, "description": "<p>Hello</p>", "timecreated": "2 Mar 2026", "editurl": "#", "deleteurl": "#"}]
    }
}}
<div data-region="item-container">

    {{#canmanage}}
    <div class="d-flex justify-content-end mb-3">
        <a href="{{addurl}}" class="btn btn-primary">
            {{#pix}} t/add, core {{/pix}} {{#str}} item_new, local_myplugin {{/str}}
        </a>
    </div>
    {{/canmanage}}

    {{^hasitems}}
    <div class="alert alert-info" role="alert">
        {{#str}} noitems, local_myplugin {{/str}}
    </div>
    {{/hasitems}}

    {{#hasitems}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        {{#items}}
        <div class="col">
            <div class="card h-100 shadow-sm" data-item-id="{{id}}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fs-6">{{name}}</h5>
                    {{#isactive}}
                        <span class="badge bg-success">{{#str}} status_active, local_myplugin {{/str}}</span>
                    {{/isactive}}
                    {{^isactive}}
                        <span class="badge bg-secondary">{{#str}} status_draft, local_myplugin {{/str}}</span>
                    {{/isactive}}
                </div>
                <div class="card-body">
                    <div class="card-text text-muted">{{{description}}}</div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-body-secondary">{{timecreated}}</small>
                    {{#canmanage}}
                    <div class="d-flex gap-2">
                        <a href="{{editurl}}" class="btn btn-outline-secondary btn-sm"
                           title="{{#str}} edit {{/str}}">
                            {{#pix}} t/edit, core {{/pix}}
                        </a>
                        <button class="btn btn-outline-danger btn-sm"
                                data-action="local_myplugin/item_list-delete"
                                data-id="{{id}}"
                                title="{{#str}} delete {{/str}}">
                            {{#pix}} t/delete, core {{/pix}}
                        </button>
                    </div>
                    {{/canmanage}}
                </div>
            </div>
        </div>
        {{/items}}
    </div>
    {{/hasitems}}

</div>
```

### Mustache Syntax Quick Reference

| Syntax | Purpose | When to Use |
|--------|---------|-------------|
| `{{var}}` | HTML-escaped output | All text variables (names, titles) |
| `{{{var}}}` | Raw HTML output | ONLY for `format_text()` output |
| `{{#section}} ... {{/section}}` | Truthy block | Conditionals & list iteration |
| `{{^section}} ... {{/section}}` | Falsy / inverse block | Empty states |
| `{{#str}} key, component {{/str}}` | Language string | All user-visible text |
| `{{#pix}} icon, component {{/pix}}` | Moodle icon | Buttons, badges |
| `{{> local_myplugin/partial }}` | Include partial template | Reusable fragments |
| `{{#js}} ... {{/js}}` | Inline JavaScript | AMD module init from template |

### Template Documentation Block

Every template **must** have a documentation comment at the top with:
1. `@template` — Frankenstyle template name
2. Context variable descriptions
3. Example JSON context (used by the component library)

## Bootstrap 5 Rules

- **Never** hardcode colours, fonts, or spacing values
- Use Bootstrap 5 utility classes — they inherit the active theme's variables
- Use `data-*` attributes for JS targeting — never class selectors
- Use `data-action="component/template-action"` namespaced pattern for JS hooks
- Use responsive grid: `row`, `col-*`, breakpoint suffixes (`-sm`, `-md`, `-lg`, `-xl`)
- Prefer `gap-*` and `d-flex` over custom margins between items
- Accessibility: `role="alert"` on alerts, `aria-label` on icon-only buttons, `sr-only` for screen-reader text

### Common Bootstrap 5 Patterns in Moodle

```mustache
{{! Alert }}
<div class="alert alert-warning" role="alert">{{#str}} warning_msg, local_myplugin {{/str}}</div>

{{! Button group }}
<div class="btn-group" role="group" aria-label="Actions">
    <a href="{{viewurl}}" class="btn btn-primary">{{#str}} view {{/str}}</a>
    <a href="{{editurl}}" class="btn btn-secondary">{{#str}} edit {{/str}}</a>
</div>

{{! Table }}
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th scope="col">{{#str}} name {{/str}}</th>
                <th scope="col">{{#str}} status {{/str}}</th>
            </tr>
        </thead>
        <tbody>
            {{#items}}
            <tr>
                <td>{{name}}</td>
                <td>{{status}}</td>
            </tr>
            {{/items}}
        </tbody>
    </table>
</div>
```

## Forms (Moodle Form API)

```php
<?php
namespace local_myplugin\form;

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/formslib.php");

class item_form extends \moodleform {
    public function definition(): void {
        $mform    = $this->_form;
        $courseid = $this->_customdata['courseid'];

        $mform->addElement('text', 'name', get_string('field_name', 'local_myplugin'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255);

        $editoropts = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'context'  => \context_course::instance($courseid),
        ];
        $mform->addElement('editor', 'description_editor',
            get_string('field_description', 'local_myplugin'), null, $editoropts);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('select', 'status', get_string('field_status', 'local_myplugin'), [
            0 => get_string('status_draft', 'local_myplugin'),
            1 => get_string('status_active', 'local_myplugin'),
        ]);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata['itemid'] ?? 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = get_string('required');
        }
        return $errors;
    }
}
```

### Processing Forms

```php
$form = new \local_myplugin\form\item_form(null, [
    'courseid' => $courseid,
    'itemid'   => $id,
]);

if ($item) {
    $item = file_prepare_standard_editor(
        $item, 'description', $editoropts, $context,
        'local_myplugin', 'description', $item->id
    );
    $form->set_data($item);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/myplugin/index.php', ['courseid' => $courseid]));
} else if ($data = $form->get_data()) {
    $data = file_postupdate_standard_editor(
        $data, 'description', $editoropts, $context,
        'local_myplugin', 'description', $data->id ?: null
    );
    $data->timemodified = time();
    if ($data->id) {
        $DB->update_record('local_myplugin_items', $data);
    } else {
        $data->timecreated = time();
        $data->userid = $USER->id;
        $data->id = $DB->insert_record('local_myplugin_items', $data);
    }
    redirect(
        new moodle_url('/local/myplugin/index.php', ['courseid' => $courseid]),
        get_string('item_saved', 'local_myplugin'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
```

## Navigation & Settings

### Course Navigation (lib.php)

```php
function local_myplugin_extend_navigation_course(
    navigation_node $parentnode,
    stdClass $course,
    context_course $context,
): void {
    if (!has_capability('local/myplugin:view', $context)) {
        return;
    }
    $parentnode->add(
        get_string('pluginname', 'local_myplugin'),
        new moodle_url('/local/myplugin/index.php', ['courseid' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_myplugin',
        new pix_icon('i/report', '')
    );
}
```

### Admin Settings (settings.php)

```php
<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_myplugin', get_string('pluginname', 'local_myplugin'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_myplugin/apiurl',
        get_string('setting_apiurl', 'local_myplugin'),
        get_string('setting_apiurl_desc', 'local_myplugin'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_myplugin/enabled',
        get_string('setting_enabled', 'local_myplugin'),
        get_string('setting_enabled_desc', 'local_myplugin'),
        1
    ));
}

// Reading settings in code:
$apiurl = get_config('local_myplugin', 'apiurl');
```
