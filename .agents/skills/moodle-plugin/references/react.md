# React in Moodle 5.x

> Based mainly on Moodle 5.2 React docs. This is not standard Create React App or Vite-style React.
> React in Moodle uses **esbuild + Grunt**, **import maps**, and **ReactAutoInit** for auto-mounting.

## Contents

- How Moodle React differs
- File structure
- Component contract
- `{{#react}}` auto-init
- Build tools
- Import maps
- Styling rules
- Anti-patterns

---

## How Moodle React Differs from Standard React

| Standard React (CRA / Vite) | Moodle React |
|-----------------------------|--------------|
| Webpack or Vite bundles everything | esbuild via Grunt — React and react-dom are **external**, served from Moodle's own bundles |
| `ReactDOM.render()` / `createRoot()` called manually | `{{#react}}` Mustache helper auto-mounts via **ReactAutoInit** |
| Import React from node_modules | Import maps resolve `react`, `react-dom`, `@moodle/lms/*` via browser-native ESM |
| `.jsx` files everywhere | `.tsx` / `.ts` TypeScript source in `js/esm/src/` |
| `npm run build` | `grunt react`, `grunt react:dev`, `grunt react:watch` |
| Single app bundle | Multiple independent component files, each in `js/esm/build/` |
| Absolute imports from `src/` | Alias `@moodle/lms/<component>/*` resolves to `js/esm/build/` |

---

## File Structure

```
plugintype_pluginname/
└── js/
    └── esm/
        ├── src/                    # TypeScript/TSX source — EDIT THESE
        │   ├── viewer.tsx          # → @moodle/lms/plugintype_pluginname/viewer
        │   ├── editor.tsx
        │   └── types/
        │       └── index.ts
        └── build/                  # esbuild output — commit, never hand-edit
            ├── viewer.js
            └── editor.js
```

**Module specifier format:** `@moodle/lms/<plugintype>_<pluginname>/<filename>`

Example: `@moodle/lms/mod_book/viewer` resolves to `mod/book/js/esm/build/viewer.js`

---

## Required Component Contract

Every React file must:

1. **Default-export** a React function component
2. Accept typed props
3. Handle its own data fetching (use a separate `services.ts` module)

```tsx
// js/esm/src/viewer.tsx
import React from 'react';

type Props = {
    title?: string;
    contextid: number;
};

export default function Viewer({title = 'My Plugin', contextid}: Props) {
    return (
        <div className="local-myplugin-viewer">
            <h1>{title}</h1>
        </div>
    );
}
```

**Rules:**
- No inline styles — use CSS classes (themes must be able to override)
- Use design tokens, not hard-coded colour values
- Keep components simple — delegate API calls to a `services.ts` file

---

## Auto-Initialization via `{{#react}}` (ReactAutoInit)

Official guide: moodledev.io/docs/5.2/guides/javascript/react/reactautoinit

The `{{#react}}` Mustache helper is the **recommended** way to mount components. No manual bootstrap code needed.

### Mustache template syntax

```mustache
{{#react}}{
    "component": "@moodle/lms/local_myplugin/viewer",
    "props": {"title": "{{title}}", "contextid": {{contextid}} },
    "id": "myplugin-viewer",
    "class": "local-myplugin-viewer-wrapper"
}<p>Loading…</p>
{{/react}}
```

**JSON keys:**

| Key | Required | Purpose |
|-----|----------|---------|
| `component` | **Yes** | Specifier — `@moodle/lms/<component>/<path>` |
| `props` | No | JSON object passed to the React component as props |
| `id` | No | HTML `id` for the container div |
| `class` | No | HTML `class` for the container div |

Mustache variables (`{{title}}`) and helpers (`{{#str}}`) resolve before JSON parsing.

### What ReactAutoInit does

1. At `DOMContentLoaded` — scans DOM for `[data-react-component]` elements
2. Imports the component module via the import map
3. Calls `createRoot(el).render(<Component {...props} />)`
4. Sets `data-react-mounted="1"` to prevent duplicate mounts
5. Installs a global `MutationObserver` — auto-mounts/unmounts components added or removed dynamically (AJAX fragments, page updates)

### Equivalent HTML (what the helper generates)

```html
<div data-react-component="@moodle/lms/local_myplugin/viewer"
     data-react-props='{"title":"My Plugin","contextid":42}'
     id="myplugin-viewer"
     class="local-myplugin-viewer-wrapper">
    <p>Loading…</p>
</div>
```

---

## Manual Mounting (when ReactAutoInit is not enough)

Use `mountReactApp` when the mount point is dynamic or created inside an existing AMD module lifecycle.

```js
// amd/src/mymodule.js
import {mountReactApp} from '@moodle/lms/core/mount';
import MyComponent from '@moodle/lms/local_myplugin/viewer';

export const init = (contextid) => {
    const container = document.querySelector('[data-region="viewer-container"]');
    const unmount = mountReactApp(container, MyComponent, {contextid}, {id: 'my-app'});
    // Call unmount() when the container is removed
};
```

---

## Build Tools & Compilation

Official guide: moodledev.io/docs/5.2/guides/javascript/react/buildtools

Moodle uses **esbuild integrated with Grunt** — not Webpack or Vite.

### Commands (run from Moodle root or component `js/esm/src/` directory)

```bash
grunt react           # Production build — minified, no source maps
grunt react:dev       # Development build — inline source maps, unminified
grunt react:watch     # Watch mode — native esbuild incremental rebuilds
```

> Running `grunt` from inside a component's `js/esm/src/` directory automatically triggers `grunt react` for that component only.

### What the build does

1. Discovers all `js/esm/src/**/*.{ts,tsx}` files automatically — **no registration needed**
2. Runs esbuild — transpiles TypeScript/JSX to browser-ready ESM
3. Marks `react`, `react-dom`, `@moodle/lms`, `@moodlehq/design-system` as **external** (not bundled)
4. Outputs individual ES module files to `js/esm/build/`
5. Generates `tsconfig.aliases.json` with path aliases — **never edit this file manually**

### Commit rules

- **Commit `js/esm/build/`** — same rule as `amd/build/`
- **Never hand-edit `js/esm/build/`**
- Run `grunt react` before committing any `js/esm/src/` changes

---

## Import Maps

Official guide: moodledev.io/docs/5.2/guides/javascript/react/importmap

Import maps tell the **browser** how to resolve bare specifiers (`react`, `@moodle/lms/...`) to URLs. No bundler needed at runtime.

### Built-in specifiers

| Specifier | Resolves to |
|-----------|-------------|
| `react` | `lib/js/bundles/react/` (Moodle's shared React bundle) |
| `react-dom` | `lib/js/bundles/react-dom/` |
| `@moodle/lms/` | Component's `js/esm/build/` via ESM endpoint |
| `@moodlehq/design-system` | `lib/js/bundles/design-system.js` |

**ESM endpoint URL pattern:** `https://example.com/esm/{jsrev}/<scriptpath>`

### Custom specifiers (via PHP hook)

```php
// In a pre_render hook:
$importmap = \core\di::get(\core\output\requirements\import_map::class);
$importmap->add_import(
    'my-lib',
    loader: new \core\url('https://cdn.example.com/my-lib.js')
);
```

### Caching

| `jsrev` value | Cache behaviour |
|---------------|-----------------|
| Positive integer | Long-lived immutable headers (production) |
| `-1` (dev mode) | Short-lived, re-fetched every page load |

---

## Profiler

Official guide: moodledev.io/docs/5.2/guides/javascript/react/profiler

The profiler activates automatically when developer mode is on (`$CFG->cachejs = false`).

```php
// config.php — enable profiler:
$CFG->cachejs = false;  // sets jsrev = -1
```

**What activates:**
- PHP serves `client.development.js` (unminified React DOM profiling build) instead of `client.js`
- `mountReactApp` wraps component trees in React's `<Profiler>` automatically
- Console logs render timing data
- Warnings when renders exceed 16 ms (60 fps) or 50 ms (critical)
- `[react_autoinit]` prefixed messages track component detection and mounting

---

## Theming Standards (Mandatory)

React components in Moodle **must** remain compatible with Moodle's theming system. Themes can radically change colours, spacing, and layout — anything that prevents that is a bug.

### Design tokens — not hard-coded values

Use CSS custom properties from the Moodle Design System. Never use raw hex/rgb values or pixel sizes:

```css
/* ❌ WRONG — theme cannot override */
.local-myplugin-card {
    background-color: #ffffff;
    color: #333333;
    padding: 16px;
}

/* ✅ CORRECT — design tokens */
.local-myplugin-card {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
    border-radius: var(--bs-border-radius);
}
```

**Common Bootstrap 5 / Moodle design tokens:**

| Token | Purpose |
|-------|---------|
| `--bs-body-bg` | Page background |
| `--bs-body-color` | Primary text colour |
| `--bs-primary` | Brand primary colour |
| `--bs-border-radius` | Standard border radius |
| `--bs-card-spacer-y`, `--bs-card-spacer-x` | Card padding |
| `--bs-font-size-base` | Base font size (16px in 5.2) |
| `--bs-line-height-base` | Line height |

### No inline styles — ever

```tsx
/* ❌ WRONG — inline styles cannot be overridden by themes */
<div style={{color: 'red', padding: '10px'}}>...</div>

/* ✅ CORRECT — class-based */
<div className="local-myplugin-error p-2">...</div>
```

**Exception:** Inline styles only for values calculated at runtime with no CSS alternative (e.g. `width` for a progress bar percentage).

### Accept `className` props for theme flexibility

```tsx
type Props = {
    title: string;
    className?: string;
};

export default function Card({title, className = ''}: Props) {
    return <div className={`local-myplugin-card ${className}`}><h3>{title}</h3></div>;
}
```

### Use Bootstrap classes first

```tsx
/* ✅ Bootstrap utilities before custom CSS */
<div className="d-flex align-items-center gap-2 p-3 border rounded">
    <span className="badge bg-primary">{count}</span>
    <span className="text-muted small">{label}</span>
</div>
```

### CSS scope — always prefix with plugin Frankenstyle name

```css
/* ✅ Scoped — no collisions */
.local-myplugin-viewer-header { ... }
.local-myplugin-card--active  { ... }

/* ❌ Global — collides with other plugins */
.viewer-header { ... }
```

### Theme-agnostic rules

- Never assume specific contrast ratios — the active theme controls those
- Never use `!important` — it prevents theme overrides
- Test with both Boost (default) and Classic themes before shipping

---

## Security Design (Mandatory)

### JSX auto-escapes — rely on it, never bypass it

JSX `{expression}` output is **automatically HTML-escaped**. This is your primary XSS defence:

```tsx
/* ✅ Safe — JSX escapes all output */
<h1>{userProvidedTitle}</h1>
<p>{item.description}</p>
```

**The React prop that injects raw HTML directly into the DOM is BANNED in Moodle plugin code.** It bypasses all XSS protection and is never acceptable. Never render user-controlled strings as raw HTML markup inside React.

### Server-side sanitisation is still required

JSX escaping protects the React render layer only. Content originating from the database must be sanitised in PHP **before** it reaches the client:

```php
// classes/external/get_items.php — sanitise before sending to JS
return [
    'title'   => external_format_string($item->title, $context->id),
    'content' => external_format_text($item->content, $item->contentformat, $context->id),
];
```

**Rule:** If a field could have been written by any user, it must pass through `external_format_string()` or `external_format_text()` before being sent as a web service response. JSX escaping alone is not enough — it only prevents the rendered output from being interpreted as HTML, it does not sanitise the underlying data.

### Rich HTML content — render server-side, never in React

When you need to display filtered/formatted HTML (e.g. course content, forum posts), do **not** inject it into React. Instead:

1. Render it server-side via `core/fragment` (PHP)
2. Inject the resulting safe HTML into a non-React DOM container using `Templates.replaceNodeContents()` in an AMD module

React is for interactive UI — server-rendered HTML belongs outside the React tree.

### Props carry data, not markup

```mustache
{{! ✅ Pass scalar data values — React renders safely }}
{{#react}}{
    "component": "@moodle/lms/local_myplugin/viewer",
    "props": {"title": "{{title}}", "count": {{count}} }
}{{/react}}
```

Never pass pre-built HTML strings as React props. If HTML is needed, it must be server-rendered and placed outside the React tree.

### CSRF — handled automatically by core/ajax

`core/ajax` automatically includes Moodle's session key in every request. Never build raw `fetch()` or `XMLHttpRequest` calls to Moodle PHP scripts directly — always define web service functions in `db/services.php` and call them through `core/ajax`.

### No dynamic code execution

Do not execute code constructed from strings at runtime. These patterns are code injection vectors. Use typed functions and data-driven logic instead. If you receive data from a web service, parse it as JSON — never run it as code.

### No external script injection

Do not load scripts from external CDNs, use `javascript:` in `href` attributes, or inline event handler attributes (`onclick="..."`). All dependencies must go through Moodle's import maps and `@moodle/lms/` specifiers. Moodle enforces a Content Security Policy that blocks external script sources.

### jQuery is deprecated — do not use

jQuery is **deprecated in Moodle 5.2** and rejected in new code. jQuery UI additionally conflicts with Bootstrap and the theme system. Use native DOM APIs:

```tsx
/* ❌ Deprecated */
import jQuery from 'jquery';
jQuery('.element').hide();

/* ✅ Native */
document.querySelector('.element')?.classList.add('d-none');
```

---

## Accessibility (Mandatory)

Moodle targets **WCAG 2.1 AA** as a minimum. All React components must comply.

### Semantic HTML first

```tsx
/* ❌ div soup — no keyboard access, no screen reader role */
<div role="button" onClick={handleClick}>Delete</div>

/* ✅ Semantic — keyboard and screen reader work out of the box */
<button type="button" onClick={handleClick}>Delete</button>
```

### ARIA — only when semantic HTML is insufficient

```tsx
/* Loading state */
<div role="status" aria-live="polite" aria-label="Loading items">
    <span className="spinner-border" aria-hidden="true" />
</div>

/* Icon-only button */
<button type="button" aria-label={deleteLabel}>
    <i className="fa fa-trash" aria-hidden="true" />
</button>

/* Expandable control */
<button aria-expanded={isOpen} aria-controls="panel-id">Options</button>
<div id="panel-id" hidden={!isOpen}>...</div>
```

### Keyboard navigation

Every interactive element must be operable by keyboard alone:

```tsx
const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handleAction(); }
    if (e.key === 'Escape') { closePanel(); }
};

<div role="button" tabIndex={0} onKeyDown={handleKeyDown} onClick={handleAction}>
    Custom interactive element
</div>
```

### Focus management

When content changes (modal opens, item deleted), move focus to the appropriate element:

```tsx
const containerRef = useRef<HTMLDivElement>(null);

const deleteItem = async(id: number) => {
    await repository.deleteItem(id);
    containerRef.current?.focus(); // never leave focus pointing at a removed element
};
```

### Screen reader announcements for dynamic updates

```tsx
const [announcement, setAnnouncement] = useState('');

const deleteItem = async(id: number) => {
    await repository.deleteItem(id);
    setAnnouncement(await getString('itemdeleted', 'local_myplugin'));
};

return (
    <>
        {/* Live region — always in DOM, visually hidden */}
        <div role="status" aria-live="polite" aria-atomic="true" className="visually-hidden">
            {announcement}
        </div>
        {/* ... rest of component */}
    </>
);
```

### Accessibility checklist before shipping

```
[ ] All interactive elements reachable by Tab
[ ] Enter/Space activate buttons and links
[ ] Escape closes modals/dropdowns
[ ] Focus returns to trigger after modal/panel closes
[ ] No colour-only information (icons or text backup for colour cues)
[ ] WCAG AA contrast — 4.5:1 for normal text, 3:1 for large text
[ ] Screen reader announces dynamic state changes (aria-live)
[ ] No keyboard traps
[ ] Tested at 200% browser zoom
[ ] No inline styles blocking theme overrides
[ ] No hard-coded hex colours or pixel values
[ ] No jQuery or jQuery UI usage
```

---

## Services Pattern

Keep data fetching out of components. Use a dedicated `services.ts`:

```ts
// js/esm/src/services.ts
import {call as fetchMany} from 'core/ajax';

export const getItems = (contextid: number) =>
    fetchMany([{methodname: 'local_myplugin_get_items', args: {contextid}}])[0] as Promise<Item[]>;
```

```tsx
// js/esm/src/viewer.tsx
import React, {useEffect, useState} from 'react';
import {getItems} from './services';

export default function Viewer({contextid}: {contextid: number}) {
    const [items, setItems] = useState<Item[]>([]);

    useEffect(() => {
        getItems(contextid).then(setItems);
    }, [contextid]);

    return <ul>{items.map(i => <li key={i.id}>{i.name}</li>)}</ul>;
}
```

---

## Troubleshooting

| Symptom | Check |
|---------|-------|
| Component not mounting | Verify `data-react-component` specifier format: `@moodle/lms/<component>/<path>` |
| "Module not found" | Check `js/esm/build/<file>.js` exists — run `grunt react` |
| Default export missing | Component **must** use `export default function` |
| Props not passed | Ensure `data-react-props` is valid JSON |
| Profiler not active | Set `$CFG->cachejs = false` in config.php |
| Duplicate mounts | Check for `data-react-mounted="1"` already set on element |
| Console `[react_autoinit]` messages | Normal — shows component detection and mount status |

---

## Quick Reference

| Task | Command / API |
|------|---------------|
| Build production | `grunt react` |
| Build with source maps | `grunt react:dev` |
| Watch mode | `grunt react:watch` |
| Auto-mount via template | `{{#react}}{"component": "@moodle/lms/..."}{{/react}}` |
| Manual mount | `mountReactApp(el, Component, props, {id})` from `@moodle/lms/core/mount` |
| Component specifier | `@moodle/lms/<plugintype>_<name>/<filename>` |
| Source files | `js/esm/src/*.tsx` |
| Build output | `js/esm/build/*.js` — always commit |
| Enable profiler | `$CFG->cachejs = false` |
| Styling | No inline styles; use design tokens + Bootstrap |
| Data fetching | Separate `services.ts` file, never in component |
