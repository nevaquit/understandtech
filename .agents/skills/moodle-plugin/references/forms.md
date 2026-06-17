# Forms API

Use this reference for any Moodle form work. Do not treat forms as generic HTML or a custom controller problem unless Moodle's Form API clearly cannot support the requirement.

## Contents

- When to load this file
- Core form pattern
- Advanced form triggers
- Files in forms
- Form rules
- Built-in-first policy

## When To Load This File

Load this first when the task mentions any of:

- `moodleform`, `MoodleQuickForm`, `formslib.php`
- `definition()`, `validation()`, `set_data()`, `get_data()`
- `mod_form.php`
- file picker, file manager, editor draft areas
- repeated fields, add-more buttons, no-submit buttons
- advanced settings, checkbox controller, `choicedropdown`

## Core Form Pattern

Define forms in a class under `classes/form/` extending `\moodleform`.

```php
<?php
namespace local_myplugin\form;

require_once($CFG->libdir . '/formslib.php');

class edit_item_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name', 'local_myplugin'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = [];
        return $errors;
    }
}
```

Typical handling flow:

```php
$mform = new \local_myplugin\form\edit_item_form(null, $customdata);

if ($mform->is_cancelled()) {
    // Redirect or abort.
} else if ($data = $mform->get_data()) {
    // Process validated submission.
} else {
    $mform->set_data($toform);
    $mform->display();
}
```

## Advanced Form Triggers

### Advanced elements

Use `setAdvanced()` for fields or whole sections that should be initially hidden.

```php
$mform->addElement('header', 'displayhdr', get_string('display', 'local_myplugin'));
$mform->setAdvanced('displayhdr');
```

Rules:

- Always give header elements unique names.
- Do not hide too many controls as advanced; use sparingly.

### Checkbox controller

Use grouped `advcheckbox` elements with `add_checkbox_controller()` when the UI needs select-all or deselect-all behavior.

```php
$mform->addElement('advcheckbox', 'opt1', 'Option 1', null, ['group' => 1]);
$mform->addElement('advcheckbox', 'opt2', 'Option 2', null, ['group' => 1]);
$this->add_checkbox_controller(1);
```

### No-submit button

Use `registerNoSubmitButton()` when a form needs an internal sub-action such as “add another tag” or “recalculate preview” without final submission.

```php
$mform->registerNoSubmitButton('addtags');
```

Handle it before normal submission:

```php
if ($mform->is_cancelled()) {
    // ...
} else if ($mform->no_submit_button_pressed()) {
    $data = $mform->get_submitted_data();
} else if ($data = $mform->get_data()) {
    // Final validated submission.
}
```

### Repeat elements

Use `repeat_elements()` when the number of items is user-driven or unknown in advance.

Typical use cases:

- answer options
- repeated configuration rows
- multiple limits or mappings

Rules:

- prefer `repeat_elements()` over hand-rolled JS duplication
- use the options array for `type`, `default`, `helpbutton`, `advanced`, and `disabledif`
- remember returned data is zero-indexed arrays

### `choicedropdown`

Use `choicedropdown` instead of a plain select when each option needs richer metadata such as description or icon.

It is based on `core\output\choicelist`, so prefer it over custom HTML dropdown implementations.

## Files In Forms

Use built-in form elements for file handling:

- `filepicker` for one uploaded file that is processed immediately
- `filemanager` for stored attachments or collections
- `editor` for rich text with embedded files

Key rules:

- prepare draft areas before display
- save draft areas into permanent storage on submission
- prefer `filemanager` over a custom upload widget
- use File API helpers instead of manual file handling

Important functions:

```php
file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area(...);
file_save_draft_area_files(...);
```

## Form Rules

- Put forms in `classes/form/` unless a plugin-type convention requires something else.
- Use Moodle form elements first, not raw HTML.
- Use `setType()` for every value that needs cleaning.
- Use `addRule()`, `disabledIf()`, `hideIf()`, and built-in helpers before custom JS validation.
- Keep form rendering within Moodle output patterns.
- For activity settings forms, follow `mod_form.php` conventions.

## Built-In-First Policy

Prefer built-in Moodle features over custom implementations whenever possible:

- `moodleform` instead of manual `<form>` markup
- `repeat_elements()` instead of custom repeat-row JS
- `registerNoSubmitButton()` instead of custom intermediate submit flows
- `add_checkbox_controller()` instead of hand-written select-all logic
- `choicedropdown` instead of custom rich selects
- `filepicker` / `filemanager` / `editor` instead of custom upload UIs
- `add_action_buttons()` instead of manually composing standard submit/cancel rows

Only build a custom form element or client-side behavior when Moodle’s existing Form API cannot express the requirement cleanly.
