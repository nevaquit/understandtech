# JavaScript in Moodle 5.x

> Based mainly on Moodle 5.2 JavaScript docs, with patterns that remain useful across Moodle 5.0 and 5.1 unless a section says otherwise.

## Contents

- Three JS systems
- AMD modules
- AJAX and repository pattern
- Templates and strings
- Modals and interaction patterns
- `core/reactive`
- Dynamic imports and performance
- Build commands
- Quick reference

---

## Three JS Systems in Moodle 5.x

| System | When to use | Source dir | Build dir | Loader |
|--------|-------------|------------|-----------|--------|
| **AMD / ESM** | AJAX calls, UI logic, core/ajax, modals, Grunt-compiled JS | `amd/src/` | `amd/build/` | RequireJS / `js_call_amd` |
| **React (ESM + import maps)** | TypeScript components, complex interactive UIs | `js/esm/src/` | `js/esm/build/` | Import map + ReactAutoInit |
| **core/reactive** | Stateful UIs without React — course-editor-style | `amd/src/` | `amd/build/` | RequireJS / `js_call_amd` |

**Rule:** Simple AJAX / DOM interaction → AMD. React component needed → ESM/React. Complex state shared across components without React → core/reactive.

---

## AMD / ESM Modules

### File structure

```
plugintype_pluginname/
└── amd/
    ├── src/                # EDIT THESE — ES2015+ / ESM source
    │   ├── mymodule.js
    │   └── local/
    │       ├── selectors.js
    │       └── repository.js   # all AJAX calls go here
    └── build/              # Grunt output — commit, never hand-edit
        ├── mymodule.min.js
        └── mymodule.min.js.map
```

### Module naming

Module names follow Frankenstyle: `<plugintype>_<pluginname>/<filename>`

| File path | AMD module name |
|-----------|-----------------|
| `local/myplugin/amd/src/mymodule.js` | `local_myplugin/mymodule` |
| `mod/forum/amd/src/discussion.js` | `mod_forum/discussion` |
| `lib/amd/src/ajax.js` | `core/ajax` |

Subdirectory names inside `amd/src/` must start with a Moodle API name or `local/`.

### Writing a module (ESM — required for all new code)

```js
// amd/src/item_manager.js
/**
 * @module     local_myplugin/item_manager
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';
import Templates from 'core/templates';
import Selectors from './local/selectors';

export const init = (contextid) => {
    const root = document.querySelector('[data-region="myplugin-root"]');
    if (!root) {
        return;
    }
    registerEvents(root, contextid);
};

const registerEvents = (root, contextid) => {
    root.addEventListener('click', async(e) => {
        const actionEl = e.target.closest('[data-action]');
        if (!actionEl) {
            return;
        }
        e.preventDefault();
        switch (actionEl.dataset.action) {
            case 'delete':
                await handleDelete(parseInt(actionEl.dataset.id, 10), root);
                break;
        }
    });
};

const handleDelete = async(itemid, root) => {
    try {
        await fetchMany([{methodname: 'local_myplugin_delete_item', args: {itemid}}])[0];
        root.querySelector(`[data-item-id="${itemid}"]`)?.remove();
        Notification.addNotification({message: await getString('deleted', 'local_myplugin'), type: 'success'});
    } catch (e) {
        Notification.exception(e);
    }
};
```

### Classic AMD `define()` — reading legacy code only

```js
// Legacy — do not write new code this way
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';
    return {
        init: function(contextid) {
            Ajax.call([{methodname: 'local_myplugin_get_data', args: {contextid}}])[0]
                .then(function(r) { return r; })
                .catch(Notification.exception);
        }
    };
});
```

### Selectors module (always isolate CSS selectors)

```js
// amd/src/local/selectors.js
export default {
    actions: {
        delete: '[data-action="delete"]',
        edit:   '[data-action="edit"]',
    },
    regions: {
        root: '[data-region="myplugin-root"]',
    },
};
```

### Repository module (centralize all AJAX calls)

```js
// amd/src/local/repository.js
import {call as fetchMany} from 'core/ajax';

export const getItems   = (contextid) => fetchMany([{methodname: 'local_myplugin_get_items',   args: {contextid}}])[0];
export const deleteItem = (itemid)    => fetchMany([{methodname: 'local_myplugin_delete_item', args: {itemid}}])[0];
```

### Lazy loading (`-lazy.js` suffix)

Append `-lazy` to the filename; Moodle loads it on demand rather than at page load:

```
amd/src/heavy_feature-lazy.js  →  loaded only when first required
```

---

## Loading AMD Modules from PHP

```php
// Standard — positional args:
$PAGE->requires->js_call_amd('local_myplugin/item_manager', 'init', [$PAGE->context->id]);

// Named args (JS receives single object):
$PAGE->requires->js_call_amd('local_myplugin/item_manager', 'init', [[
    'contextid' => $PAGE->context->id,
    'courseid'  => $COURSE->id,
]]);

// Deferred — loads after page is interactive:
$PAGE->requires->js_call_amd('local_myplugin/tracker', 'init', [$id], ['async' => true]);
```

> Must be called **before** `$OUTPUT->footer()`. Parameters must be JSON-serializable. Params > 1 KB trigger developer warnings.

### From a Mustache template

```mustache
{{#js}}
require(['local_myplugin/item_manager'], function(M) {
    M.init({{contextid}});
});
{{/js}}
```

> JS inside `{{#js}}` is **not transpiled** by Grunt. Keep it to a single `require()` call only.

---

## Grunt — Compilation & Watch

### Setup (once per Moodle root)

```bash
npm install              # installs Grunt + Babel + esbuild toolchain
npm install -g grunt-cli # optional — npx works without it
```

### AMD compilation commands

```bash
npx grunt amd                          # build all AMD modules site-wide
npx grunt amd --root=local/myplugin    # build one plugin only (faster)
npx grunt watch                        # watch + rebuild on save
npx grunt eslint --root=local/myplugin # lint JS
```

### Development source maps

```php
// config.php — see unminified source in browser devtools:
$CFG->cachejs = false;
```

### Commit rules

- **Always commit `amd/build/`** — Moodle production serves from here, never from `src/`
- **Never hand-edit `amd/build/`** — always edit `amd/src/` and recompile
- Run `npx grunt amd` before every commit touching `amd/src/`

---

## AJAX — `core/ajax`

Official guide: moodledev.io/docs/5.2/guides/javascript/ajax

### Repository pattern (recommended)

Centralise all web service calls in a `local/repository.js` module. This simplifies refactoring and debugging.

```js
// amd/src/local/repository.js
import {call as fetchMany} from 'core/ajax';

export const getItems = (courseid) =>
    fetchMany([{methodname: 'local_myplugin_get_items', args: {courseid}}])[0];

export const deleteItem = (itemid) =>
    fetchMany([{methodname: 'local_myplugin_delete_item', args: {itemid}}])[0];
```

### Batched calls (single HTTP transaction)

```js
import {call as fetchMany} from 'core/ajax';

const [items, settings] = await Promise.all(
    fetchMany([
        {methodname: 'local_myplugin_get_items',    args: {courseid}},
        {methodname: 'local_myplugin_get_settings', args: {}},
    ])
);
```

### Critical requirements

- Enable AJAX: `'ajax' => true` in `db/services.php`
- Text output **must** pass through `external_format_text()` or `external_format_string()` with context
- After modifying `db/services.php`: increment plugin version, run `admin/cli/upgrade.php`
- Notify Moodle filters after dynamic content: fire `M.core.event.FILTER_CONTENT_UPDATED`

---

## Modals — `core/modal`

Official guide: moodledev.io/docs/5.2/guides/javascript/modal

### Modern API (Moodle 4.3+ — use this)

```js
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';

const modal = await Modal.create({
    title: 'Confirm',
    body:  '<p>Are you sure?</p>',  // accepts string or Promise<string>
    footer: '',
    show: true,
    removeOnClose: true,
    large: false,
});
modal.getRoot().on(ModalEvents.save,   (e) => { /* handle save */ });
modal.getRoot().on(ModalEvents.hidden, ()  => { /* cleanup    */ });
```

### Built-in typed modals

```js
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalDeleteCancel from 'core/modal_delete_cancel';

const modal = await ModalSaveCancel.create({
    title: getString('confirm', 'core'),
    body:  getString('areyousure', 'local_myplugin'),
});
modal.show();
```

### Custom modal

```js
// amd/src/my_modal.js
import Modal from 'core/modal';

export default class MyModal extends Modal {
    static TYPE     = 'mod_example/my_modal';
    static TEMPLATE = 'mod_example/my_modal';

    configure(modalConfig) {
        modalConfig.show = true;
        modalConfig.removeOnClose = true;
        super.configure(modalConfig);
    }
}
```

```mustache
{{!-- templates/my_modal.mustache --}}
{{< core/modal }}
    {{$title}}My title{{/title}}
    {{$body}}<p>My body</p>{{/body}}
    {{$footer}}<button>OK</button>{{/footer}}
{{/ core/modal }}
```

### Legacy ModalFactory — DEPRECATED (4.3+)

```js
// Do not use for new code — use Modal.create() above
import ModalFactory from 'core/modal_factory';
```

---

## Strings — `core/str`

```js
import {getString, getStrings} from 'core/str';

const label = await getString('pluginname', 'local_myplugin');
const msg   = await getString('greetuser',  'local_myplugin', username); // with placeholder

const [save, cancel] = await getStrings([
    {key: 'save',   component: 'core'},
    {key: 'cancel', component: 'core'},
    {key: 'greet',  component: 'local_myplugin', param: username},
]);
```

> Never use `get_string` / `get_strings` (jQuery Promises, deprecated). Never use `.done` / `.fail`.

---

## Templates — `core/templates`

```js
import Templates from 'core/templates';

// Render and replace:
const {html, js} = await Templates.renderForPromise('local_myplugin/item_card', {items});
Templates.replaceNodeContents(container, html, js);

// Append / prepend:
Templates.appendNodeContents(container, html, js);
Templates.prependNodeContents(container, html, js);
```

---

## Notifications — `core/notification`

```js
import Notification from 'core/notification';

// Toast: 'success' | 'info' | 'warning' | 'error'
Notification.addNotification({message: 'Saved!', type: 'success'});

// Promise error handler:
somePromise.catch(Notification.exception);

// Confirm dialog:
Notification.confirm('Title', 'Message', 'OK', 'Cancel', () => doIt(), () => {});

// Alert dialog:
Notification.alert('Title', 'Message', 'OK');
```

---

## ComboboxSearch — `core/comboboxsearch`

Official guide: moodledev.io/docs/5.2/guides/javascript/comboboxsearch

A reusable accessible dropdown-search component (Moodle 4.3+).

```js
// amd/src/mycomponent.js
import search_combobox from 'core/comboboxsearch/search_combobox';

export default class extends search_combobox {
    // Must implement all 5 required methods:

    async fetchDataset() {
        return this.getItems(); // return data array or Promise
    }

    filterDataset(dataset) {
        const term = this.getPreppedSearchTerm();
        return dataset.filter(item => item.name.includes(term));
    }

    renderDropdown() {
        // update dropdown DOM with this.getMatchedResults()
    }

    componentSelector() {
        return '[data-region="my-combobox"]';
    }

    dropdownSelector() {
        return '[data-region="my-combobox-dropdown"]';
    }
}
```

```js
// amd/src/main.js
import MyComponent from 'local_myplugin/mycomponent';
export const init = () => new MyComponent({});
```

```php
$PAGE->requires->js_call_amd('local_myplugin/main', 'init');
```

---

## Deprecation Utility — `core/deprecated` (Moodle 5.2+)

Official guide: moodledev.io/docs/5.2/guides/javascript/deprecation

```js
import emitDeprecation from 'core/deprecated';

export const myOldFunction = (...args) => {
    emitDeprecation('myOldFunction', {
        replacement: 'myNewFunction',
        since: '5.2',
        mdl: 'MDL-12345',
    });
    return myNewFunction(...args); // backward-compatible
};
```

**Modes:**
- Default — console notice + modal warning
- `emit: false` — console only (silent)
- `final: true` — throws error instead of warning

**Suppress in config.php:**
```php
$CFG->jsdeprecationignorelist = ['myOldFunction'];
```

---

## Event Handling Conventions

- `data-action="actionname"` — marks interactive elements (never use CSS classes for JS hooks)
- `data-region="region-name"` — identifies structural containers
- `data-*` attributes — carry data values (IDs, config, flags)
- Use **event delegation** on a container — one listener, `e.target.closest()` to dispatch

```js
root.addEventListener('click', async(e) => {
    const actionEl = e.target.closest('[data-action]');
    if (!actionEl) {
        return;
    }
    e.preventDefault();
    switch (actionEl.dataset.action) {
        case 'delete': await handleDelete(actionEl.dataset.id); break;
        case 'edit':   await handleEdit(actionEl.dataset.id);   break;
    }
});
```

```html
<!-- data-action naming: verb or verb-noun -->
<button data-action="delete" data-id="{{id}}">Delete</button>
<button data-action="edit"   data-id="{{id}}">Edit</button>
```

---

## Dynamic Imports (Lazy Loading)

```js
// Load on user action:
document.querySelector('[data-action="open-editor"]')
    ?.addEventListener('click', async() => {
        const {setup} = await import('local_myplugin/rich_editor');
        setup();
    });

// Load on scroll (IntersectionObserver):
const observer = new IntersectionObserver(async(entries) => {
    for (const entry of entries) {
        if (entry.isIntersecting) {
            observer.unobserve(entry.target);
            const {render} = await import('local_myplugin/chart');
            render(entry.target);
        }
    }
}, {threshold: 0.1});
observer.observe(document.querySelector('.chart-container'));
```

---

## core/reactive — Stateful UIs (Without React)

Official guide: moodledev.io/docs/5.2/guides/javascript/reactive

Use for complex state shared across components — course editor style. **Do not use React and core/reactive together for the same feature.**

### Architecture

Four elements work together:
1. **Components** — manage DOM sections; watch state; dispatch mutations
2. **Reactive Instance** — registers components; triggers watchers on state change
3. **State Manager** — protects state; only mutations can write to it
4. **Mutation Library** — all state changes live here; components call via `dispatch()`

### Component lifecycle

```js
import {BaseComponent} from 'core/reactive';

export default class ItemList extends BaseComponent {
    create() {
        // Define selectors and CSS classes before registration
        this.selectors = {
            ITEM: '[data-item-id]',
            DELETE_BTN: '[data-action="delete"]',
        };
    }

    getWatchers() {
        return [
            {watch: 'items:created',  handler: this._itemAdded},
            {watch: 'items:deleted',  handler: this._itemRemoved},
            {watch: 'items[3]:updated', handler: this._specificItemUpdated},
        ];
    }

    stateReady(state) {
        // Add event listeners here (after initial state is loaded)
        this.addEventListener(this.element, 'click', this._onClick);
    }

    destroy() {
        // Cleanup when unregistering
    }

    _onClick(e) {
        const btn = e.target.closest(this.selectors.DELETE_BTN);
        if (btn) {
            this.reactive.dispatch('deleteItem', {id: btn.dataset.id});
        }
    }

    _itemAdded({element}) {
        // element = the new item from state
    }
}
```

### State rules

State root can only contain **objects** or **Sets of objects with `id` attribute** — not primitives or id-less arrays:

```js
// ✅ Valid root state:
state = {
    config: {enabled: true},      // object
    items: [{id: 1, name: 'A'}],  // array of objects with id
};

// ❌ Invalid — throws exception:
state = {count: 42};              // primitive at root
state = {tags: ['a', 'b']};      // array without id
```

### Watcher naming

```
items:created          — Set "items" had element created
items:deleted          — element deleted
items.name:updated     — attribute "name" of "items" changed
items[3]:updated       — specific element with id=3 updated
config:updated         — object "config" changed
```

### Mutations

```js
// Manual:
stateManager.setReadOnly(false);
state.items.push({id: 99, name: 'New'});
stateManager.setReadOnly(true);

// Backend-driven (from web service response):
stateManager.processUpdates(updates); // actions: put, override, update, create, delete
```

### Initialising

```js
import {Reactive} from 'core/reactive';

const reactive = new Reactive({
    name: 'local_myplugin_state',
    eventName: 'local_myplugin_state:changed',
    eventDispatch: document,
    state: {
        items: [],
        config: {loading: false},
    },
    mutations: {
        deleteItem: (stateManager, {id}) => {
            stateManager.setReadOnly(false);
            const items = stateManager.state.items;
            const idx = [...items].findIndex(i => i.id == id);
            if (idx !== -1) {
                items.delete([...items][idx]);
            }
            stateManager.setReadOnly(true);
        },
    },
});
```

---

## Prefetch

```js
import Prefetch from 'core/prefetch';

Prefetch.prefetchStrings('local_myplugin', ['confirm_title', 'confirm_body']);
Prefetch.prefetchTemplate('local_myplugin/confirm_dialog');
```

---

## Dropzone (core/dropzone — Moodle 4.4+)

```js
import Dropzone from 'core/dropzone';

const dz = new Dropzone(container, 'image/*', (files) => handleFiles(files));
dz.setLabel('Drop images here');
dz.init();
```

---

## Quick Reference

| Task | API |
|------|-----|
| Write AMD module | `export const init = () => {}` in `amd/src/` |
| Compile AMD | `npx grunt amd --root=plugintype_name` |
| Load from PHP | `$PAGE->requires->js_call_amd('component/module', 'init', [$param])` |
| Defer load | `js_call_amd(..., ..., ..., ['async' => true])` |
| AJAX call | `fetchMany([{methodname, args}])[0]` |
| Error handler | `.catch(Notification.exception)` |
| Get string | `await getString('key', 'component')` |
| Get string + param | `await getString('key', 'component', value)` |
| Render template | `await Templates.renderForPromise('component/tpl', data)` |
| Replace DOM | `Templates.replaceNodeContents(el, html, js)` |
| Delegated events | `root.addEventListener('click', e => e.target.closest('[data-action]'))` |
| Dynamic import | `const {fn} = await import('component/module')` |
| Lazy on scroll | `new IntersectionObserver(...)` |
| Confirm dialog | `Notification.confirm(title, msg, ok, cancel, onOk, onCancel)` |
| Custom modal | extend `core/modal`, set `static TYPE` + `static TEMPLATE` |
| Deprecate API | `emitDeprecation('name', {replacement, since, mdl})` |
| Stateful UI | extend `BaseComponent` from `core/reactive` |
| React component | see `references/react.md` |
