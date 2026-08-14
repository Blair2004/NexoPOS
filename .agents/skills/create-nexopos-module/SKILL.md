---
name: create-nexopos-module
description: >
  Create, extend, or repair modules for the NexoPOS Laravel application.
  Use when a request involves scaffolding a module under modules/, module routes,
  controllers, models, migrations, permissions, settings, CRUD, menus, widgets,
  events, view injections, Blade/Vue, module Vite assets, dashboard Vue mounting
  (nsExtraComponents vs createApp), Tailwind module prefixes, POS cart
  (product-row meta, line-extra, unit price, order types, pay queue, cart buttons),
  or tests while following NexoPOS module conventions.
  Also use whenever the task is about mastering or extending the live POS cart.
---

# Create NexoPOS Modules

Build modules that match the current repository rather than relying on generic Laravel package patterns.

## Establish the scope

1. Inspect `AGENTS.md`, the requested feature, and nearby modules before changing files.
2. Identify the module namespace, display name, author, description, version, and required capabilities. Infer low-risk values from the request or existing module; ask only when a choice materially changes the result.
3. Determine whether to create a module or extend an existing one. Never overwrite an existing module unless the user explicitly requests it.
4. Search version-specific Laravel documentation before changing Laravel code, as required by the repository instructions.
5. Read [references/nexopos-module-conventions.md](references/nexopos-module-conventions.md). Load only the linked `.github/instructions` files relevant to the feature.
6. **Widget work (mandatory):** read `.github/instructions/nexopos-widgets.instructions.md` completely before reading or editing widget PHP, Vue, registration, layout policy, default order, or tests. Inspect `WidgetService`, `ns-dragzone.vue`, and one current widget.
7. **POS work (mandatory):** if the feature touches cart, products on POS, order types, payments, product-row UI, unit price, or once-per-line fees, read the full POS mastery guide: [references/pos-lifecycle.md](references/pos-lifecycle.md).
8. For `nsHttpClient`, frontend globals, notifications, localization, or module TypeScript declarations, read [references/frontend-apis.md](references/frontend-apis.md).
9. For module Vue + Tailwind (shared runtime, Tailwind prefix, UI conventions), read [references/module-frontend.md](references/module-frontend.md).
10. **Any dashboard Vue page or UI under `#dashboard-content`:** read [references/dashboard-vue-mounting.md](references/dashboard-vue-mounting.md) first. Nested `createApp()` there breaks reactivity.
11. For a settings-managed external font or reusable asset registry, read [references/module-font-registries.md](references/module-font-registries.md).
12. For module tests or module PHPUnit configuration, read [references/module-testing.md](references/module-testing.md).
13. **Custom fields under a cart product line:** `ns-pos-product-row-components` — not raw HTML.
14. **Once-per-line money (room, setup fee):** `ns-pos-product-line-extra` — not unit price × qty.

## Master POS extensions

There is no separate skill for POS: **this skill + [pos-lifecycle.md](references/pos-lifecycle.md)** are authoritative for module POS work.

### Load order for POS tasks

1. [pos-lifecycle.md](references/pos-lifecycle.md) — decision matrix, hooks, pricing, product-row, queues  
2. Source of truth: `resources/ts/pos-init.ts`, `ns-pos-cart.vue`  
3. Reference modules: `modules/NsAppointments` (row meta + line-extra + order types), `modules/NsGastro` (cart buttons)

### Pricing (do not get this wrong)

```text
line_total = (unit_price × quantity − discount) + line_extra
```

| Kind of amount | Filter / path | Multiplied by qty? |
| --- | --- | --- |
| Service / product unit | `ns-pos-product-*-price`, `ns-pos-product-unit-price` | **Yes** |
| Room / setup / cover fee on the same line | `ns-pos-product-line-extra` | **No** (once per line) |

Never bake a once-fee into unit price: quantity 2 would charge the fee twice.

### Cart line UI

- Register with `nsHooks.addFilter('ns-pos-product-row-components', …)` + `markRaw()`.
- Prefer **Options API + string `template`** for components injected into POS cart (dual-Vue: POS app ≠ `NexoPOSVue` SFCs).
- Update lines with `POS.updateProduct(product, patch, index)`.
- Gate on flags set in `addToCartQueue` (merge **`productData`**, not only `this.product` — unit `$quantities` live there).

### Boot checklist (every POS module)

| Step | Mechanism |
| --- | --- |
| Load assets only on POS | `RenderFooterEvent` → `@moduleViteAssets('Resources/ts/pos.ts', …)` |
| Blocking boot requirement | `POS.bootGuards` (no timeout; operator/security input) |
| Init context | `POS.initialQueue` |
| Enrich products | `POS.addToCartQueue` |
| Product-row UI | `ns-pos-product-row-components` |
| Once-per-line fees | `ns-pos-product-line-extra` |
| Cart buttons | `ns-after-cart-reset` (priority ≥ 20) → `POS.cartButtons` / `cartHeaderButtons` |
| POS header buttons | `ns-pos-header` → `header.buttons.MyButton` (Options API + string `template` preferred) |
| Order types | PHP `ns-orders-types` + enabled in `ns_pos_order_types` + optional `orderTypeQueue` |
| Before Pay | `ns-pay-queue` classes |
| On submit | `ns-order-before-submit` (sync only) |
| Module i18n | Always `__m('Text', 'ModuleNamespace')` in PHP and Vue/TS (no `t()` wrappers) |

Compare `order.type.identifier`, never `order.type === 'booking'`.

Full detail, complete examples, and debugging: [references/pos-lifecycle.md](references/pos-lifecycle.md).

## Prefer repository evidence

Use this priority when examples conflict:

1. Working code in a maintained, comparable module
2. Current framework and NexoPOS APIs in `app/` / `resources/ts/pos-init.ts`
3. Relevant `.github/instructions/*.instructions.md` guidance
4. Generic Laravel conventions

Inspect at least one comparable module and the core class or API being extended. Preserve its naming, registration, localization, authorization, and testing patterns.

## Scaffold safely

For a new module, inspect the command first with `php artisan make:module --help`, then run it non-interactively:

```bash
php artisan make:module --no-interaction \
  --namespace=ExampleModule \
  --name="Example Module" \
  --author="Example Author" \
  --description="What the module does" \
  --vers=1.0
```

Do not pass `--force` without explicit overwrite authorization. Add only directories and files required by the feature; the generator supplies the baseline structure.

For PHP classes not supplied by a NexoPOS generator, use the appropriate `php artisan make:* --no-interaction` command when it supports the target location. Otherwise, follow a sibling module exactly.

## Implement in vertical slices

Build the smallest complete path through the module:

1. Define storage and domain behavior with module-prefixed tables, models, services, and migrations where needed.
2. Add authorization before exposing operations. Define module permissions, protect server routes or controllers, and keep UI permission checks as a convenience rather than the security boundary.
3. Add request validation, controllers, and routes. Module `Routes/api.php` is already mounted under `/api`; do not add an `api` prefix inside it.
4. Integrate through current NexoPOS events, listeners, menus, settings, CRUD APIs, or widgets. Prefer event-based view injection; do not restore removed hook patterns.
5. Add Blade or Vue UI only when required. Reuse existing NexoPOS components, semantic theme classes, localization helpers, and frontend globals.
6. Add focused PHPUnit coverage for happy paths, authorization or validation failures, and relevant edge cases.

Every visible field created through a NexoPOS form descriptor, `FormInput`, settings page, or CRUD form must include a concise localized `description`. Explain the field's operational effect, units, scope, or consequences instead of merely repeating its label. Use the module localization helper for module-owned descriptions.

Keep business logic out of controllers and listeners when it warrants a service. Use explicit PHP types, Laravel 12 conventions, factories in tests, and existing module namespaces: `Modules\{Namespace}\...`.

## Observe module invariants

- Keep `config.xml`, the module directory, the main module class, PHP namespaces, view namespace, translation namespace, and asset namespace consistent.
- Use PascalCase for the module namespace and a module-specific lowercase prefix for tables, routes, option keys, and permissions.
- Use named routes for generated links.
- **Blade JSON serialization:** never pass an inline multiline array directly to `@json`, such as `@json([ ... ])`; Blade may parse it incorrectly. Assign the array to a PHP variable in an `@php` block, then render `@json($config)`.
- Use **`__m('Text', 'ModuleNamespace')` for all module-owned strings** (PHP and Vue/TS). Do not wrap copy in `t()` / `translate()` — NexoPOS scans `__m(...)` for translations. Frontend: global `__m` / `window.__m` on dashboard and POS.
- Treat the live POS `order.type` value as an order-type object. Compare `order.type.identifier`, not `order.type` itself, with an identifier string.
- **POS product-row meta:** `ns-pos-product-row-components` + `markRaw()` + gate on product flags + `POS.updateProduct`. Prefer Options API string templates for POS-injected components. Reference: `modules/NsAppointments` (`AppointmentsCartMeta.ts` + `pos.ts`).
- **POS once-per-line fees:** `ns-pos-product-line-extra` (e.g. room). Unit price stays service-only. See [pos-lifecycle.md](references/pos-lifecycle.md#unit-price-vs-once-per-line-extra).
- **POS cart fields that must reload:** name cart keys like **order product DB columns**; migrate columns; copy from `getData()` in BeforeCreated/BeforeUpdated. Flash `getData()` alone is not durable. See [pos-lifecycle.md § Persist cart fields](references/pos-lifecycle.md#persist-cart-fields-on-order-products-round-trip).
- **POS add-to-cart:** queue results merge via `productData`; always merge `$quantities` from `productData` when reading sale price.
- Let NexoPOS discover module routes, migrations, listeners, commands, and providers where current code does so. Do not duplicate registration.
- Do not register console commands or schedules from a module service provider.
- Make migrations repeat-safe and rollback-safe. Inspect the live schema and comparable migrations before choosing columns or constraints.
- **Before every migration schema operation, check existence:** use `Schema::hasTable()` before altering or dropping a table, `Schema::hasColumn()` before adding or dropping each column, and `Schema::hasIndex()` before adding or dropping each index. Use `Schema::createIfMissing()` for module tables. Guard columns individually so a migration can recover safely after a previous partial DDL failure; never assume that because one new column exists, the remaining columns or indexes also exist.
- Avoid cascade deletion where NexoPOS conventions require application-managed cleanup.
- Use model events only for model-local state. Put broader side effects in listeners, services, or jobs.
- Do not introduce dependencies or new top-level directories without approval.
- **Dashboard Vue:** never `createApp()` / `nsCreateApp().mount()` inside `#dashboard-content`. Register on `nsExtraComponents` and use a component tag so the UI is a child of `nsDashboardContent`. See [references/dashboard-vue-mounting.md](references/dashboard-vue-mounting.md).
- **Dashboard widgets:** declare the suggested `1x1`–`3x5` footprint and an intentional strict/restricted/unrestricted policy; pass the `widget` prop; provide `widget-handle` and `onRemove`; and place `ns-widget-layout-selector` inside the widget template only when sizing should be exposed. Preserve `WidgetService::DEFAULT_WIDGET_ORDER` packing when core footprints change.

## Handle frontend assets correctly

Load module assets from Blade with paths relative to the module root and no leading slash. On dashboard pages, load the standalone CSS entry in the header and load the Vue registration script in the footer inject before `app-init`:

```blade
@section('layout.dashboard.header')
@parent
@moduleViteAssets('Resources/css/style.css', 'ExampleModule')
@endsection

@section('layout.dashboard.footer.inject')
@parent
@moduleViteAssets('Resources/ts/page.ts', 'ExampleModule')
@endsection
```

Treat this placement as required when `style.css` supplies prefixed Tailwind utilities: declaring or importing the TypeScript entry does not replace explicitly loading the CSS entry in the layout header. Include both files as Vite inputs, and do not rely on a TypeScript-side CSS import for dashboard page styles. Do not use `@vite` for module assets. Keep Vite inputs and output aligned with `Resources/...` and `Public/build`, and use Tailwind CSS v4 semantic/theme-aware classes rather than hard-coded colors. Build module assets when frontend files change.

For POS-only module UI, scope two listeners to the POS route: add the standalone prefixed CSS view through `RenderHeaderEvent`, and add the TypeScript view through `RenderFooterEvent`. A footer TypeScript directive or a TypeScript-side CSS import is not a substitute for loading the stylesheet in the document header.

### Vue + Tailwind modules (required pattern)

Full detail: [references/module-frontend.md](references/module-frontend.md).  
**Dashboard mount (read this):** [references/dashboard-vue-mounting.md](references/dashboard-vue-mounting.md).  
**POS mount / hooks:** [references/pos-lifecycle.md](references/pos-lifecycle.md).

1. **Dashboard vs standalone mount**
   - **Inside `#dashboard-content`:** only `nsExtraComponents['my-page'] = MyPage` + `<my-page>` in Blade. Script in footer inject before `app-init`. **No** nested `createApp`.
   - **Standalone** (no dashboard content root): `nsCreateApp(Page).mount('#root')` is OK.
   - Symptom of the wrong approach: UI visible, clicks/`ref` dead after page load.
2. **Shared Vue** — `defineNexoPOSModuleConfig` (or `nexoposVueRuntime()`). Prefer `.vue` SFCs for dashboard; for **POS cart injection**, prefer Options API + string `template` (see pos-lifecycle dual-Vue note). Never bundle a second Vue.
3. **Tailwind prefix** — every module CSS entry that imports Tailwind **must** use a short module prefix:

```css
@import "tailwindcss" prefix(foo);
```

Every module-owned utility in markup uses that prefix first: `foo:flex`, `foo:md:grid-cols-2`, `foo:hover:underline`. With theme + breakpoint variants: **`foo:dark:sm:utility`** (prefix → theme/state → breakpoint → utility). Incomplete stacks like `foo:dark:sm` are invalid; always end with the utility.

Do **not** prefix core hooks (`ns-button`, `ns-box`, …). Prefer semantic colors (`foo:bg-box-background`, `foo:text-fontcolor`) over palette/`dark:` for NexoPOS theme compatibility. Bridge semantic roles in module `@theme` so they compile under the prefix.

**UI polish (required for native look):**

- Buttons: `<ns-button>` or `.ns-button` **wrapper** + inner `button`/`a` with module padding — never `class="ns-button"` on the control alone.
- Type: `text-fontcolor` for titles/body; `text-fontcolor-soft` for sublines/descriptions.
- Loading: sized `ns-spinner`; optional label under spinner; failures settle the spinner (no infinite spin).
- Failure feedback: use `nsSnackBar.error(...)` for transient request, submit, refresh, and action failures. Do not insert a new full-width error block after an async failure; it causes cumulative layout shift. Keep inline errors only when they are anchored to a specific field/row or when a persistent fatal state needs retry controls. Preserve the previous content when possible, and reserve a stable minimum-height content region for an initial load failure.
- Confirm consequential actions with the native popup manager: `Popup.show(nsConfirmPopup, { title, message, onAction })`. Run the mutation only when `onAction` receives `true`; cancellation must have no side effect. Use this for actions such as delete, cancel, close, refund, reset, or returning stock (including “End Block”). Do not use `window.confirm()` or execute the request directly from the first click. The current core component is named `nsConfirmPopup` (not `nsConfirmDialog`).
- Semantic status steps darken in this order: `primary` (lighter), `secondary`, `tertiary` (darkest). Filled backgrounds may use `bg-*-primary` or `bg-*-secondary` with `text-white`; never use `bg-*-tertiary`, including hover states. Apply this to info, success, warning, and error.

**Dashboard page entry (copy-paste):**

```ts
// Resources/ts/page.ts — footer inject before app-init
import Page from './components/Page.vue';
declare const nsExtraComponents: Record<string, unknown>;
nsExtraComponents['example-module-page'] = Page;
```

```blade
<div id="dashboard-content" class="…">
    <example-module-page></example-module-page>
</div>
```

```js
// modules/ExampleModule/vite.config.js
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineNexoPOSModuleConfig } from '../../resources/vite-nexopos-module.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineNexoPOSModuleConfig({
    dirname: __dirname,
    inputs: ['Resources/ts/page.ts', 'Resources/css/style.css'],
    port: 3335,
});
```

## Verify the result

1. Run the smallest relevant PHPUnit file or filter with `php artisan test --compact`. Module tests live under `modules/{Namespace}/Tests` and use the host runner and shared bootstrap described in [references/module-testing.md](references/module-testing.md); never run a module `vendor/bin/phpunit`.
2. Run `vendor/bin/pint --dirty --format agent` after modifying PHP.
3. Run the module frontend build when frontend assets changed.
4. If core POS sources changed (`pos-init.ts`, `ns-pos-cart.vue`), rebuild core (`npm run build`) or confirm Vite HMR; stale `public/build` omits hooks.
5. For widget work, run the layout-policy/default-order tests and build the core or module widget entry. Verify the PHP and Vue component identifiers match.
6. Inspect routes, migration status, or built manifests only when relevant.
7. Review the final diff for accidental core changes, inconsistent namespace strings, missing permission checks, and generated placeholder code.

Report what was implemented, the verification performed, and any setup the user must still perform. Ask whether to run the full test suite after focused tests pass.
