# Dashboard Vue mounting (modules)

**Mandatory for any module Vue UI that lives under the NexoPOS dashboard layout.**

If you only read one frontend rule for dashboard modules, read this file.

## Rule

| Context | Allowed? | How |
| --- | --- | --- |
| UI inside `#dashboard-content` | **Register a component** on the existing dashboard app | `nsExtraComponents['tag-name'] = Component` + `<tag-name>` in Blade |
| UI inside other dashboard roots (`#dashboard-aside`, header, overlay) | Register on that app (or shared registries), do not nest `createApp` on a node that app already owns | Same idea |
| Standalone page **outside** those roots (public booking, custom blank layout, auth shell without dashboard content) | **`nsCreateApp` / `createApp` OK** | Mount on an empty element that core does not recompile as an in-DOM template |

**Never** call `createApp()` / `nsCreateApp()` and `.mount()` on a node that is (or will be) **inside** `#dashboard-content`.

## Why

Core boots the dashboard content app like this (`resources/ts/app-init.ts` + `app.ts`):

```ts
window.nsDashboardContent = createApp({}); // empty root — no template/render
// registers nsComponents + nsExtraComponents…
nsDashboardContent.mount('#dashboard-content');
```

Vue 3 behavior when the root has **no** template and the runtime compiler is available:

> The **innerHTML of `#dashboard-content` is used as the template**.

So the dashboard page HTML is the template for **one** app instance. A second app that previously mounted on a child is discarded; the markup is recompiled as **static** VNodes with **no** setup state and **no** handlers.

### Failure symptom (easy to misdiagnose)

- Module UI **renders** (you see the HTML).
- Buttons, `ref`/`reactive`, lists, watchers **do nothing**.
- Looks like “shared Vue runtime broke reactivity.”
- Actual cause: nested `createApp` inside `#dashboard-content`.

## Correct dashboard page pattern

### 1. Script entry (footer inject, **before** `app-init`)

Dashboard footer order (simplified):

1. `bootstrap.ts`
2. Footer inject / `@moduleViteAssets` ← **register here**
3. `app-init.ts` (merges `nsExtraComponents` into `nsDashboardContent`)
4. `app.ts` (`ns-before-mount`, then mount)

```ts
// modules/ExampleModule/Resources/ts/page.ts
import Page from './components/Page.vue';
import '../css/style.css';

declare const nsExtraComponents: Record<string, unknown>;

// Key becomes the custom element / component name
nsExtraComponents['example-module-page'] = Page;
```

Do **not**:

```ts
// WRONG on dashboard pages
import { nsCreateApp } from 'vue';
nsCreateApp(Page).mount('#example-module-app');
```

### 2. Blade (tag inside `#dashboard-content`)

```blade
@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full overflow-hidden flex flex-col">
    @include(Hook::filter('ns-dashboard-header', '../common/dashboard-header'))
    <div id="dashboard-content" class="overflow-auto flex-auto w-full p-4">
        <example-module-page></example-module-page>
    </div>
</div>
@endsection

@section('layout.dashboard.footer.inject')
@parent
@moduleViteAssets('Resources/ts/page.ts', 'ExampleModule')
@endsection
```

Props/config: prefer `window.ExampleModulePage = { … }` set in the same inject section and read inside the component, or pass static attributes if the in-DOM template supports them. Keep the registration key and tag name aligned (`example-module-page`).

### 3. What `app-init` does with your registration

```ts
const allComponents = Object.assign({ /* core pages */ }, nsExtraComponents);
window.nsDashboardContent = createApp({});
for (const name in allComponents) {
    window.nsDashboardContent.component(name, allComponents[name]);
}
// later: mount('#dashboard-content')
```

Your tag is a **real child component** of `nsDashboardContent` → full reactivity, core components (`ns-button`, …) already registered on that app.

## When `nsCreateApp` is correct

Use a dedicated app only if the mount target is **not** owned by the empty dashboard content root:

| Layout / situation | Example mount |
| --- | --- |
| `layout.base` / public page without `#dashboard-content` | `#ns-appointments-public` |
| Blank or custom shell | `#my-module-root` outside dashboard content |
| True multi-app island that core never remounts | Rare; prefer registration when unsure |

```ts
import { nsCreateApp } from 'vue';
import PublicPage from './components/PublicPage.vue';

nsCreateApp(PublicPage).mount('#my-module-public-root');
```

`nsCreateApp` still uses the shared `window.ns.vue` / `NexoPOSVue` runtime and can register core components on **that** app instance.

## Widgets, POS, popups

Same principle: **extend existing apps/registries**, do not nest a new root under a managed tree.

- **Dashboard widgets:** assign `window['WidgetName']` / component name expected by the widget system; load assets in footer inject on the home route.
- **POS:** inject via POS queues, `nsExtraComponents`, header button maps, `Popup.show(Component)` — POS has its own app; do not mount a third app inside POS DOM nodes Vue already owns.
- **Popups:** pass a component definition into `Popup.show`; popup host creates the instance.

## Decision checklist

1. Is the UI rendered under `#dashboard-content`? → **Yes:** `nsExtraComponents` + tag. **No nested `createApp`.**
2. Is the script loaded in footer inject before `app-init`? → Required for registration.
3. Is this a public/standalone layout without dashboard content? → `nsCreateApp` is fine.
4. UI shows but nothing is clickable? → Suspect nested mount first, not Tailwind or `ref`.

## Related

- Shared Vue runtime + Tailwind prefix: [module-frontend.md](module-frontend.md)
- Frontend globals: [frontend-apis.md](frontend-apis.md)
- Core files: `resources/ts/app-init.ts`, `resources/ts/app.ts`, `resources/views/common/dashboard-footer.blade.php`
