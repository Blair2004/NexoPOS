# NexoPOS module frontend (Vue + Tailwind)

Use this when a module ships Vite/Vue/Tailwind assets. Verify against working modules (`CloudDeployer` for Tailwind prefix, `NsAppointments` for dashboard registration + shared runtime).

## Dashboard mounting (read first)

**Modules must not `createApp()` / `nsCreateApp().mount()` inside `#dashboard-content`.**

Core mounts an empty `nsDashboardContent` app on that node and uses its innerHTML as the template. Nested apps look rendered but lose all reactivity.

→ Full rule, script order, Blade examples, and when `nsCreateApp` is allowed: **[dashboard-vue-mounting.md](dashboard-vue-mounting.md)**

## Shared Vue runtime (required)

NexoPOS core loads one Vue object via `resources/ts/vue-runtime.ts` (`window.ns.vue` / `window.NexoPOSVue`). Module builds **must not** bundle a second Vue copy.

### Vite config

```js
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineNexoPOSModuleConfig } from '../../resources/vite-nexopos-module.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineNexoPOSModuleConfig({
    dirname: __dirname,
    inputs: [
        'Resources/ts/main.ts',
        'Resources/css/style.css',
    ],
    port: 3335,
});
```

`defineNexoPOSModuleConfig` enables `nexoposVueRuntime()`, which rewrites `import … from 'vue'` to the host runtime. Prefer real `.vue` SFCs.

### Mounting (summary)

See **[dashboard-vue-mounting.md](dashboard-vue-mounting.md)** for the full decision table and failure modes.

| Where the UI lives | Pattern |
| --- | --- |
| `#dashboard-content` | `nsExtraComponents['page'] = Page` + `<page>` tag. **No** nested `createApp`. |
| Public / blank layout (no dashboard content root) | `nsCreateApp(Page).mount('#root')` OK |
| Widgets / POS / popups | Register into existing hosts; do not nest a new root under a managed tree. Cart line meta: `ns-pos-product-row-components` ([pos-lifecycle.md](pos-lifecycle.md#product-row-components)) |

Footer inject must run **before** `app-init` so registrations are merged into `nsDashboardContent`.

Full API table: [frontend-apis.md](frontend-apis.md).

## Tailwind prefix (required for module CSS)

### Why

Core already ships a full Tailwind build in each theme entry (`resources/css/light.css`, `dark.css`, `phosphor.css`). If a module also emits unprefixed utilities (`flex`, `md:grid-cols-2`, …), the browser ends up with two Tailwind universes that can clash, bloat CSS, and make debugging hard.

Modules that generate Tailwind **must** use a short, module-specific prefix so every module utility is namespaced.

### How to enable

In the module CSS entry:

```css
@import "tailwindcss" prefix(foo);

/* Limit scanning to this module (avoids huge builds / OOM). */
@source "../ts/**/*.{vue,ts}";
@source "../Views/**/*.blade.php";
```

When using **NexoPOS semantic colors** (`bg-box-background`, `text-fontcolor-soft`, …) under a prefix, bridge host theme variables in `@theme`. Core layouts already define `--color-*`; the prefix renames module theme keys to `--foo-color-*`, so alias them:

```css
@theme {
    --color-box-background: var(--color-box-background);
    --color-box-edge: var(--color-box-edge);
    --color-fontcolor: var(--color-fontcolor);
    --color-fontcolor-soft: var(--color-fontcolor-soft);
    --color-surface: var(--color-surface);
    --color-info-primary: var(--color-info-primary);
    --color-info-tertiary: var(--color-info-tertiary);
    --color-success-primary: var(--color-success-primary);
    --color-success-tertiary: var(--color-success-tertiary);
    --color-warning-primary: var(--color-warning-primary);
    --color-warning-tertiary: var(--color-warning-tertiary);
    --color-error-primary: var(--color-error-primary);
    --color-error-secondary: var(--color-error-secondary);
    --color-error-tertiary: var(--color-error-tertiary);
    /* add every semantic role the module markup uses */
}
```

That yields CSS like `--foo-color-box-background: var(--color-box-background)` and `foo:bg-box-background` that track light/dark/phosphor.

Examples in the repo:

| Module | Prefix | CSS |
| --- | --- | --- |
| CloudDeployer | `cd` | `@import "tailwindcss" prefix(cd);` |
| NsGastro | `go` | `prefix(go)` on utilities/theme imports |
| NsAppointments | `nsapp` | `prefix(nsapp)` + semantic `@theme` bridge |

Pick a short lowercase token from the module name (`cd`, `go`, `nsapp`, `rack`, …). Keep it stable for the life of the module.

### How utilities work with a prefix

With `prefix(foo)`, Tailwind does **not** emit `.flex`. It emits `.foo\:flex`, which you write in markup as:

```html
<div class="foo:flex foo:gap-4">
```

The module prefix is always the **first** segment of the class. Then come Tailwind variants (theme/state, breakpoints, etc.), then the utility name.

**Correct order:**

```text
{modulePrefix}:{variant…}:{utility}
```

| Intent | Class |
| --- | --- |
| Base utility | `foo:flex` |
| Breakpoint | `foo:sm:flex` / `foo:md:grid-cols-2` |
| Hover | `foo:hover:bg-box-elevation-hover` |
| Breakpoint + hover | `foo:md:hover:underline` |
| Dark variant + breakpoint (if you use `dark:`) | `foo:dark:sm:bg-box-background` |

So if the prefix is `foo`, a dark + `sm` layout class is:

```html
<div class="foo:dark:sm:flex">
```

not `dark:foo:sm:flex`, not `foo:sm:dark:flex` as the preferred stacked form for “dark then breakpoint” (Tailwind’s own stacked examples put `dark` before the breakpoint, e.g. `dark:lg:…`; with a module prefix that becomes `foo:dark:lg:…`).

**Always end with the utility** (`flex`, `p-4`, `bg-box-background`). `foo:dark:sm` alone is not a complete class.

### What must be prefixed vs what must not

**Prefix these** (module-owned Tailwind utilities in module markup / `@apply`):

- Layout: `foo:flex`, `foo:grid`, `foo:gap-4`, `foo:p-4`, `foo:w-full`, …
- Responsive: `foo:md:grid-cols-2`, `foo:lg:w-1/2`, …
- State: `foo:hover:…`, `foo:focus:…`, …
- Semantic color utilities generated for the module build: `foo:bg-box-background`, `foo:text-fontcolor-soft`, `foo:border-box-edge`, …

**Do not prefix these** (core contracts, not module Tailwind):

- Core component hooks styled by theme CSS: `ns-button`, `ns-box`, `ns-input`, `ns-notice`, …
- Icon font classes: `las`, `la-check`, …
- Module-specific non-Tailwind hooks you define yourself (e.g. `ns-example-card`) if they are plain CSS hooks, not Tailwind utilities

### Semantic theme colors still apply

NexoPOS themes set CSS variables on the document (`--color-box-background`, etc.). Prefer semantic utilities over palette or `dark:` when the host page already loads a theme stylesheet:

```html
<!-- Preferred in modules (with prefix) -->
<section class="foo:rounded-lg foo:border foo:border-box-edge foo:bg-box-background foo:p-4 foo:text-fontcolor">
```

Avoid treating `dark:` as the primary NexoPOS theme switch. Core loads one of `light` / `dark` / `phosphor` stylesheets; `phosphor` is a third theme, not “dark mode.” Use semantic tokens so all three themes stay correct. See the `nexopos-theming` skill.

### `@apply` and scanning

Inside module CSS, `@apply` must use the prefixed names:

```css
.example-panel {
    @apply foo:flex foo:gap-2 foo:p-4 foo:bg-box-background;
}
```

Ensure Vite/Tailwind can scan module templates (`Resources/**`). Do not build class names dynamically (`` `foo:bg-${x}` ``); Tailwind cannot see them.

### When the host already has core Tailwind

Dashboard/POS pages load core theme CSS (unprefixed utilities exist globally). Modules still **must** prefix their own Tailwind entry so the module stylesheet does not re-emit a second unprefixed framework. Markup for **module-owned** layout should use the module prefix consistently, even if an unprefixed core utility would “happen to work” on the page today.

Public or blank layouts that only load module CSS **require** the prefix + prefixed markup; otherwise styles are missing or conflict.

## UI design conventions (modules)

These match how core NexoPOS builds dashboard/POS UI. Follow them so module pages look native.

### Semantic color utilities (confirmed)

Roles such as `fontcolor`, `fontcolor-soft`, `box-background`, `box-edge`, `info-primary`, `error-tertiary` are **NexoPOS theme tokens**, not stock Tailwind colors. They work in modules **only when**:

1. The host page loads a theme stylesheet (`light` / `dark` / `phosphor`) that defines `--color-*`.
2. The module CSS uses `prefix(…)` **and** the `@theme` bridge above so `foo:text-fontcolor-soft` compiles to something like:

```css
--foo-color-fontcolor-soft: var(--color-fontcolor-soft);
.foo\:text-fontcolor-soft { color: var(--foo-color-fontcolor-soft); }
```

Without the bridge, `foo:text-fontcolor-soft` may not be generated at all. Always bridge every semantic role the markup uses. Re-check the built CSS after adding new roles.

### Typography

| Role | Utility (with module prefix) | Use for |
| --- | --- | --- |
| Default body / headings | `foo:text-fontcolor` | Headings, paragraphs, primary labels, table body text |
| Subtle supporting copy | `foo:text-fontcolor-soft` | Sublines, field descriptions, helper text, meta, empty-state hints, week-day headers |

Prefer `text-fontcolor` over `text-fontcolor-hard` for ordinary headings and paragraphs unless a deliberate strong emphasis is required. Do not invent palette text colors (`text-gray-500`) for normal content.

```html
<h1 class="foo:text-2xl foo:font-semibold foo:text-fontcolor">Title</h1>
<p class="foo:mt-1 foo:text-sm foo:text-fontcolor-soft">Short supporting description.</p>
```

### Buttons and links (do not put `ns-button` on the interactive element alone)

Theme CSS styles **`.ns-button button` and `.ns-button a`** (colors, hover, disabled). The hook is on a **wrapper**; the module owns **shape** (padding, margin, radius).

**Wrong** (colors never apply correctly):

```html
<a href="..." class="ns-button info">Create</a>
<button class="ns-button default">Save</button>
```

**Preferred options (pick one and stay consistent in the feature):**

1. **Vue component** (when the app has core components registered, e.g. via `nsCreateApp`):

```html
<ns-button type="info" :href="createUrl">
    <i class="las la-plus"></i>
    <span>New Appointment</span>
</ns-button>

<ns-button type="default" :disabled="loading" @click="save">
    Save
</ns-button>
```

Types: `default`, `info`, `success`, `warning`, `error`, `primary` (and `hover-*` variants where core uses them).

2. **Theme hook wrapper** (Blade or Vue; matches core list/upload pages):

```html
<div class="ns-button info">
    <a :href="createUrl" class="foo:rounded-lg foo:px-3 foo:py-2">
        <i class="las la-plus"></i>
        <span>New Appointment</span>
    </a>
</div>

<div class="ns-button default">
    <button type="button" class="foo:rounded-lg foo:px-3 foo:py-2" @click="refresh">
        Refresh
    </button>
</div>
```

3. **Themed shape without the full component** — still use the `.ns-button` + type class for colors/borders, module utilities only for layout:

```html
<div class="ns-button success">
    <button type="button" class="foo:flex foo:items-center foo:gap-2 foo:rounded-lg foo:px-4 foo:py-2">
        Confirm
    </button>
</div>
```

For pure navigation that is not a primary action, a plain `foo:text-info-primary foo:hover:underline` link is fine. Do not fake a button with only unthemed borders.

### Loading and empty/error states

`ns-spinner` uses a `size` prop that maps to width/height utilities (default is large). **Always pass an explicit size** for module UI (core auth/setup commonly use `6`–`16`).

| Context | Suggested size |
| --- | --- |
| Inline on a button | `size="6"` or `size="8"`, `border="2"` or `border="4"` |
| Panel / page load | `size="10"`–`size="16"`, `border="4"` |

**Rules:**

1. Size the spinner; never leave a full-page unbounded spinner.
2. Optional “Loading…” **text goes below** the spinner (column layout), not beside it, when both are shown.
3. A **failed** load must replace the spinner with a clear error (message + optional retry). Never leave an infinite spinner after failure.
4. Prefer structured states: `loading` → content | empty | `error`.

```html
<!-- Loading -->
<div v-if="loading && !hasData" class="foo:flex foo:flex-col foo:items-center foo:justify-center foo:gap-2 foo:p-10">
    <ns-spinner size="12" border="4" />
    <span class="foo:text-sm foo:text-fontcolor-soft">Loading…</span>
</div>

<!-- Error (spinner is gone) -->
<div
    v-else-if="error"
    class="foo:border foo:border-error-secondary foo:bg-error-tertiary foo:p-4 foo:text-sm foo:text-error-primary"
>
    {{ error }}
    <div class="foo:mt-3">
        <div class="ns-button default">
            <button type="button" class="foo:rounded-lg foo:px-3 foo:py-2" @click="reload">
                Retry
            </button>
        </div>
    </div>
</div>
```

Inline button busy state may keep a small spinner **inside** the button (core pattern); that is not a page-level load.

### Status and active fills (info / success / warning / error)

NexoPOS status tokens come in three steps: `primary`, `secondary`, `tertiary`.

| Use | Token step | Text |
| --- | --- | --- |
| Solid **active / selected / filled** background | **`*-secondary` only** | **`text-white`** (safe contrast) |
| Soft tint / subtle selected surface | `*-tertiary` | matching `text-*-primary` or `text-fontcolor` |
| Borders, icons, emphasis text | `*-primary` or `border-*-secondary` | — |

**Never** use `bg-info-primary`, `bg-success-primary`, `bg-warning-primary`, or `bg-error-primary` as a solid active background. Those steps are for text/icons/borders, not filled chips or selected tabs.

```html
<!-- Active toggle / filled chip -->
<button class="foo:bg-info-secondary foo:text-white">Selected</button>
<span class="foo:bg-success-secondary foo:text-white">Confirmed</span>

<!-- Soft selection (not a solid active fill) -->
<button class="foo:border foo:border-info-primary foo:bg-info-tertiary foo:text-info-primary">Filter</button>
```

Same rule for `error`, `warning`, and `success`.

### Panels and surfaces

Reuse semantic surfaces: `foo:bg-box-background`, `foo:border-box-edge`, `foo:bg-surface`, status families for feedback. Prefer `ns-box` when it fits. See the `nexopos-theming` skill.

### Localization (`__m` only)

Module UI strings must use **`__m('Literal text', 'ModuleNamespace')`** so NexoPOS language scanning finds them.

- **Do not** introduce `t()`, `translate()`, or similar wrappers around module copy.
- Core provides **`window.__m`**, but Vue templates **cannot** see bare globals. Import a runtime bridge (e.g. `import { __m } from '../i18n'`) that forwards to `window.__m`, and for Options API put **`__m` on `methods`**. `declare const __m` alone is **not** enough (TypeScript-only).
- Keep the second argument equal to the module namespace (e.g. `'NsAppointments'`).

See [frontend-apis.md § Localization](frontend-apis.md#localization).

### HTTP from module Vue

Prefer a small Promise wrapper around `nsHttpClient.*.subscribe(...)` rather than importing `firstValueFrom` from a module-bundled `rxjs`. Dual RxJS copies can leave loading promises pending forever (infinite spinner).

## Blade loading

```blade
@moduleViteAssets('Resources/ts/main.ts', 'ExampleModule')
@moduleViteAssets('Resources/css/style.css', 'ExampleModule')
```

- No `@vite` for module assets
- No leading slash on the path
- Prefer footer inject for registration scripts that must run before `app-init`

## Checklist before calling a module “frontend-complete”

- [ ] `vite.config.js` uses `defineNexoPOSModuleConfig` (or at least `nexoposVueRuntime()`)
- [ ] Entries import from `'vue'` via the shared runtime plugin — no dual Vue
- [ ] **Dashboard UI:** `nsExtraComponents` + component tag inside `#dashboard-content` (no nested `createApp` / `.mount` there)
- [ ] **Standalone UI only:** `nsCreateApp` on a root **outside** `#dashboard-content`
- [ ] UI is real `.vue` SFCs where practical
- [ ] Module CSS uses `@import "tailwindcss" prefix(shortname);` plus `@theme` bridge for every semantic role used
- [ ] **Every** module Tailwind utility in markup uses `shortname:…`
- [ ] Semantic colors compile in the built CSS (`shortname:text-fontcolor`, `shortname:bg-box-background`, …)
- [ ] Buttons use `<ns-button>`, or `.ns-button` **wrapper** + inner `button`/`a` (never `class="ns-button"` on the control alone)
- [ ] Spinners have explicit `size` / `border`; loading text below spinner; errors replace spinner
- [ ] Typography: `text-fontcolor` for titles/body; `text-fontcolor-soft` for sublines/descriptions
- [ ] Active fills use `bg-*-secondary` + `text-white` (not `bg-*-primary`)
- [ ] Core hooks (`ns-button`, `ns-box`, …) stay unprefixed as hook names
- [ ] Module assets built and loaded via `@moduleViteAssets`
- [ ] Focused PHPUnit coverage for backend behavior
