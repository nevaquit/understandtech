# Theme Development & Output API

> Building Boost child themes, SCSS compilation, template/renderer overrides, Output API, stored progress bars.

---

## Boost Child Theme

All custom themes should extend Boost (the core responsive theme). Never fork Boost — extend it.

### File Structure

```
theme/mytheme/
├── config.php                    # Theme configuration
├── version.php                   # Plugin version
├── lang/en/theme_mytheme.php     # Language strings
├── lib.php                       # SCSS injection callbacks
├── settings.php                  # Admin settings (optional)
├── scss/
│   ├── preset/
│   │   └── default.scss          # Main SCSS preset
│   └── _custom.scss              # Additional SCSS partials
├── pix/
│   └── favicon.ico               # Optional custom favicon
├── layout/                       # Only if overriding layouts
│   └── columns2.php
├── classes/
│   └── output/
│       └── core_renderer.php     # Renderer overrides
└── templates/                    # Mustache template overrides
    └── core/
        └── loginform.mustache    # Override core template
```

### config.php

```php
<?php
// theme/mytheme/config.php
defined('MOODLE_INTERNAL') || die();

$THEME->name     = 'mytheme';
$THEME->parents  = ['boost'];    // Extend Boost.
$THEME->sheets   = [];           // No legacy CSS — use SCSS.
$THEME->editor_sheets = [];
$THEME->usefallback = true;      // Fall back to parent for missing templates.
$THEME->enable_dock = false;

$THEME->scss = function($theme) {
    return theme_mytheme_get_main_scss_content($theme);
};

$THEME->prescsscallback  = 'theme_mytheme_get_pre_scss';
$THEME->extrascsscallback = 'theme_mytheme_get_extra_scss';

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Layout overrides (only if needed):
// $THEME->layouts = [...];
```

### lib.php — SCSS Callbacks

```php
<?php
// theme/mytheme/lib.php

/**
 * Get the main SCSS content.
 */
function theme_mytheme_get_main_scss_content($theme): string {
    global $CFG;

    // Use the selected preset or default.
    $preset = !empty($theme->settings->preset) ? $theme->settings->preset : 'default.scss';
    $presetfile = $CFG->dirroot . '/theme/mytheme/scss/preset/' . $preset;

    if (file_exists($presetfile)) {
        return file_get_contents($presetfile);
    }

    // Fallback to Boost's default.
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}

/**
 * SCSS variables injected BEFORE the preset.
 */
function theme_mytheme_get_pre_scss($theme): string {
    $scss = '';

    // Override Bootstrap variables from admin settings.
    if (!empty($theme->settings->brandcolor)) {
        $scss .= '$primary: ' . $theme->settings->brandcolor . ";\n";
    }

    return $scss;
}

/**
 * Extra SCSS appended AFTER the preset.
 */
function theme_mytheme_get_extra_scss($theme): string {
    $scss = '';

    // Custom SCSS from admin settings.
    if (!empty($theme->settings->scss)) {
        $scss .= $theme->settings->scss;
    }

    return $scss;
}

/**
 * Serve plugin files (e.g., background images from settings).
 */
function theme_mytheme_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'backgroundimage') {
        $theme = \theme_config::load('mytheme');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }
    send_file_not_found();
}
```

### settings.php (Admin Colour Picker)

```php
<?php
// theme/mytheme/settings.php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('theme_mytheme', get_string('configtitle', 'theme_mytheme'));

    // Brand colour.
    $setting = new admin_setting_configcolourpicker(
        'theme_mytheme/brandcolor',
        get_string('brandcolor', 'theme_mytheme'),
        get_string('brandcolor_desc', 'theme_mytheme'),
        '#0f6cbf'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Custom SCSS.
    $setting = new admin_setting_configtextarea(
        'theme_mytheme/scss',
        get_string('rawscss', 'theme_mytheme'),
        get_string('rawscss_desc', 'theme_mytheme'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $ADMIN->add('themes', $settings);
}
```

---

## Template Overrides

Override any core or plugin Mustache template by placing it in your theme's `templates/` directory with the same relative path.

```
theme/mytheme/templates/
├── core/
│   ├── loginform.mustache      # Override core login form
│   └── notification.mustache   # Override core notification
├── mod_forum/
│   └── forum_post.mustache     # Override forum post template
└── local_myplugin/
    └── item_card.mustache      # Override plugin template
```

Moodle automatically uses the theme's template if it exists.

---

## Renderer Overrides

Override a plugin's renderer to change its HTML output.

```php
<?php
// theme/mytheme/classes/output/core_renderer.php
namespace theme_mytheme\output;

defined('MOODLE_INTERNAL') || die();

class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Override the login info display.
     */
    public function login_info($withlinks = null) {
        // Custom implementation.
        return parent::login_info($withlinks) . '<span class="custom-badge">Beta</span>';
    }
}
```

For plugin renderers:
```php
<?php
// theme/mytheme/classes/output/local_myplugin_renderer.php
namespace theme_mytheme\output;

class local_myplugin_renderer extends \local_myplugin\output\renderer {
    // Override specific render methods.
}
```

---

## Output API

### format_text & format_string

```php
// User-generated multi-line content (descriptions, intro, HTML editor output):
echo format_text($record->description, $record->descriptionformat, [
    'context'  => $context,
    'noclean'  => false,     // Default: clean HTML. Only set true for trusted content.
    'filter'   => true,      // Apply text filters (multi-lang, etc.).
    'para'     => true,      // Wrap in <p> if plain text.
    'newlines' => true,      // Convert newlines to <br> for PLAIN format.
]);

// Single-line user text (names, titles):
echo format_string($record->name, true, ['context' => $context]);
```

### Output Functions

```php
// HTML-safe output:
echo $OUTPUT->container('Content here', 'my-class', 'my-id');
echo $OUTPUT->container_start('wrapper-class');
echo $OUTPUT->container_end();

echo $OUTPUT->heading('Page Title', 2);  // <h2>
echo $OUTPUT->paragraph('Some text.');   // <p>

echo $OUTPUT->sr_text('Screen reader only text');  // Visually hidden

// Pix icons:
echo $OUTPUT->pix_icon('t/edit', get_string('edit'), 'core', ['class' => 'iconsmall']);

// Action icons:
echo $OUTPUT->action_icon(
    new moodle_url('/local/myplugin/edit.php', ['id' => $id]),
    new pix_icon('t/edit', get_string('edit'))
);

// Notifications:
echo $OUTPUT->notification('Saved successfully', \core\output\notification::NOTIFY_SUCCESS);
echo $OUTPUT->notification('Something went wrong', \core\output\notification::NOTIFY_ERROR);
```

### Stored Progress Bar (Moodle 5.0+)

For long-running operations visible to users:

```php
use core\output\stored_progress_bar;

$progressbar = new stored_progress_bar('local_myplugin_import_' . $importid);
$progressbar->create();

// In the long-running process (e.g., adhoc task):
foreach ($items as $i => $item) {
    process_item($item);
    $progressbar->update(
        ($i + 1) / count($items) * 100,
        "Processing item {$item->name}..."
    );
}
$progressbar->finish();
```

### named_templatable Interface (Moodle 5.0+)

Auto-routes renderables to templates without explicit render methods:

```php
<?php
namespace local_myplugin\output;

use core\output\named_templatable;
use core\output\renderable;

class item_card implements renderable, named_templatable {

    public function __construct(
        private readonly \stdClass $item,
        private readonly \context $context,
    ) {}

    public function get_template_name(\renderer_base $renderer): string {
        return 'local_myplugin/item_card';  // Auto-resolved template.
    }

    public function export_for_template(\renderer_base $output): array {
        return [
            'id'   => $this->item->id,
            'name' => format_string($this->item->name, true, ['context' => $this->context]),
        ];
    }
}

// Rendering — no custom renderer method needed:
echo $OUTPUT->render(new \local_myplugin\output\item_card($item, $context));
```

### renderer_helper (Moodle 5.0+)

DI-based renderer access without `$PAGE`:

```php
use core\output\renderer_helper;

$renderer = renderer_helper::get_renderer('local_myplugin');
echo $renderer->render($renderable);
```

---

## Page Layouts

Moodle defines standard layout types. Override in `config.php` only when necessary:

| Layout | Use |
|--------|-----|
| `standard` | Default with blocks |
| `course` | Course pages |
| `incourse` | Pages inside a course |
| `admin` | Admin pages |
| `login` | Login page |
| `popup` | Popup windows |
| `embedded` | Minimal, no nav |
| `maintenance` | Maintenance mode |

```php
// In your page:
$PAGE->set_pagelayout('incourse');

// In theme config.php (override):
$THEME->layouts = [
    'mydashboard' => [
        'file'    => 'columns2.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['langmenu' => true],
    ],
];
```

## SCSS Best Practices

1. **Never modify Boost core files** — use pre/extra SCSS callbacks
2. **Use Bootstrap variables** (`$primary`, `$secondary`, etc.) via pre-SCSS injection
3. **Scope custom styles** with a theme-specific class or `body.theme-mytheme`
4. **Use SCSS nesting max 3 levels deep**
5. Call `theme_reset_all_caches` after setting changes that affect SCSS
6. Test with `$CFG->themedesignermode = true` in config.php during development
