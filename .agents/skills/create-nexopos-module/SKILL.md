---
name: create-nexopos-module
description: Create, extend, or repair modules for the NexoPOS Laravel application. Use when a request involves scaffolding a module under modules/, implementing module routes, controllers, models, migrations, permissions, settings, CRUD classes, menus, widgets, events, view injections, Blade views, Vue components, module Vite assets, or tests while following NexoPOS module conventions.
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
- Let NexoPOS discover module routes, migrations, listeners, commands, and providers where current code does so. Do not duplicate registration.
- Do not register console commands or schedules from a module service provider.
- Make migrations repeat-safe and rollback-safe. Inspect the schema and comparable migrations before choosing columns or constraints.
- Avoid cascade deletion where NexoPOS conventions require application-managed cleanup.
- Use model events only for model-local state. Put broader side effects in listeners, services, or jobs.
- Do not introduce dependencies or new top-level directories without approval.

## Handle frontend assets correctly

Load module assets from Blade with paths relative to the module root and no leading slash:

```blade
@moduleViteAssets('Resources/ts/main.ts', 'ExampleModule')
@moduleViteAssets('Resources/css/style.css', 'ExampleModule')
```

Do not use `@vite` for module assets. Keep Vite inputs and output aligned with `Resources/...` and `Public/build`, and use Tailwind CSS v4 semantic/theme-aware classes rather than hard-coded colors. Build module assets when frontend files change.

## Verify the result

1. Run the smallest relevant PHPUnit file or filter with `php artisan test --compact`.
2. Run `vendor/bin/pint --dirty --format agent` after modifying PHP.
3. Run the module frontend build when frontend assets changed.
4. Inspect routes, migration status, or built manifests only when relevant.
5. Review the final diff for accidental core changes, inconsistent namespace strings, missing permission checks, and generated placeholder code.

Report what was implemented, the verification performed, and any setup the user must still perform. Ask whether to run the full test suite after focused tests pass.
