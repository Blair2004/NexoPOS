---
name: create-nexopos-module
description: Create, extend, or repair modules for the NexoPOS Laravel application. Use when a request involves scaffolding a module under modules/, implementing module routes, controllers, models, migrations, permissions, settings, CRUD classes, menus, widgets, events, view injections, Blade views, Vue components, module Vite assets, dashboard Vue mounting (nsExtraComponents vs createApp), Tailwind module prefixes, or tests while following NexoPOS module conventions.
---

# Create NexoPOS Modules

Build modules that match the current repository rather than relying on generic Laravel package patterns.

## Establish the scope

1. Inspect `AGENTS.md`, the requested feature, and nearby modules before changing files.
2. Identify the module namespace, display name, author, description, version, and required capabilities. Infer low-risk values from the request or existing module; ask only when a choice materially changes the result.
3. Determine whether to create a module or extend an existing one. Never overwrite an existing module unless the user explicitly requests it.
4. Search version-specific Laravel documentation before changing Laravel code, as required by the repository instructions.
5. Read [references/nexopos-module-conventions.md](references/nexopos-module-conventions.md). Load only the linked `.github/instructions` files relevant to the feature.
6. For POS cart buttons, order types, payment gates, submission hooks, or cart scripts, read [references/pos-lifecycle.md](references/pos-lifecycle.md).
7. For `nsHttpClient`, frontend globals, notifications, localization, or module TypeScript declarations, read [references/frontend-apis.md](references/frontend-apis.md).
8. For module Vue + Tailwind (shared runtime, Tailwind prefix, UI conventions), read [references/module-frontend.md](references/module-frontend.md).
9. **Any dashboard Vue page or UI under `#dashboard-content`:** read [references/dashboard-vue-mounting.md](references/dashboard-vue-mounting.md) first. Nested `createApp()` there breaks reactivity.

## Prefer repository evidence

Use this priority when examples conflict:

1. Working code in a maintained, comparable module
2. Current framework and NexoPOS APIs in `app/`
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

Keep business logic out of controllers and listeners when it warrants a service. Use explicit PHP types, Laravel 12 conventions, factories in tests, and existing module namespaces: `Modules\{Namespace}\...`.

## Observe module invariants

- Keep `config.xml`, the module directory, the main module class, PHP namespaces, view namespace, translation namespace, and asset namespace consistent.
- Use PascalCase for the module namespace and a module-specific lowercase prefix for tables, routes, option keys, and permissions.
- Use named routes for generated links.
- Use `__m('Text', 'ModuleNamespace')` for module-owned translations when that is the surrounding convention.
- Treat the live POS `order.type` value as an order-type object. Compare `order.type.identifier`, not `order.type` itself, with an identifier string.
- Let NexoPOS discover module routes, migrations, listeners, commands, and providers where current code does so. Do not duplicate registration.
- Do not register console commands or schedules from a module service provider.
- Make migrations repeat-safe and rollback-safe. Inspect the schema and comparable migrations before choosing columns or constraints.
- Avoid cascade deletion where NexoPOS conventions require application-managed cleanup.
- Use model events only for model-local state. Put broader side effects in listeners, services, or jobs.
- Do not introduce dependencies or new top-level directories without approval.
- **Dashboard Vue:** never `createApp()` / `nsCreateApp().mount()` inside `#dashboard-content`. Register on `nsExtraComponents` and use a component tag so the UI is a child of `nsDashboardContent`. See [references/dashboard-vue-mounting.md](references/dashboard-vue-mounting.md).

## Handle frontend assets correctly

Load module assets from Blade with paths relative to the module root and no leading slash:

```blade
@moduleViteAssets('Resources/ts/main.ts', 'ExampleModule')
@moduleViteAssets('Resources/css/style.css', 'ExampleModule')
```

Do not use `@vite` for module assets. Keep Vite inputs and output aligned with `Resources/...` and `Public/build`, and use Tailwind CSS v4 semantic/theme-aware classes rather than hard-coded colors. Build module assets when frontend files change.

### Vue + Tailwind modules (required pattern)

Full detail: [references/module-frontend.md](references/module-frontend.md).  
**Dashboard mount (read this):** [references/dashboard-vue-mounting.md](references/dashboard-vue-mounting.md).

1. **Dashboard vs standalone mount**
   - **Inside `#dashboard-content`:** only `nsExtraComponents['my-page'] = MyPage` + `<my-page>` in Blade. Script in footer inject before `app-init`. **No** nested `createApp`.
   - **Standalone** (no dashboard content root): `nsCreateApp(Page).mount('#root')` is OK.
   - Symptom of the wrong approach: UI visible, clicks/`ref` dead after page load.
2. **Shared Vue** — `defineNexoPOSModuleConfig` (or `nexoposVueRuntime()`). Prefer `.vue` SFCs. Never bundle a second Vue.
3. **Tailwind prefix** — every module CSS entry that imports Tailwind **must** use a short module prefix:

```css
@import "tailwindcss" prefix(foo);
```

Every module-owned utility in markup uses that prefix first: `foo:flex`, `foo:md:grid-cols-2`, `foo:hover:underline`. With theme + breakpoint variants: **`foo:dark:sm:utility`** (prefix → theme/state → breakpoint → utility). Incomplete stacks like `foo:dark:sm` are invalid; always end with the utility.

Do **not** prefix core hooks (`ns-button`, `ns-box`, …). Prefer semantic colors (`foo:bg-box-background`, `foo:text-fontcolor`) over palette/`dark:` for NexoPOS theme compatibility. Bridge semantic roles in module `@theme` so they compile under the prefix.

**UI polish (required for native look):**

- Buttons: `<ns-button>` or `.ns-button` **wrapper** + inner `button`/`a` with module padding — never `class="ns-button"` on the control alone.
- Type: `text-fontcolor` for titles/body; `text-fontcolor-soft` for sublines/descriptions.
- Loading: sized `ns-spinner`; optional label under spinner; errors replace spinner (no infinite spin).
- Active fills: `bg-*-secondary` + `text-white` — never `bg-*-primary` as a solid active background.

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
    inputs: ['Resources/ts/main.ts', 'Resources/css/style.css'],
    port: 3335,
});
```

## Verify the result

1. Run the smallest relevant PHPUnit file or filter with `php artisan test --compact`.
2. Run `vendor/bin/pint --dirty --format agent` after modifying PHP.
3. Run the module frontend build when frontend assets changed.
4. Inspect routes, migration status, or built manifests only when relevant.
5. Review the final diff for accidental core changes, inconsistent namespace strings, missing permission checks, and generated placeholder code.

Report what was implemented, the verification performed, and any setup the user must still perform. Ask whether to run the full test suite after focused tests pass.
