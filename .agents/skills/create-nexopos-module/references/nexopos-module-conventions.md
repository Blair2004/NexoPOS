# NexoPOS module convention map

Use this reference to select only the source material needed for the requested module. Do not load every instruction file.

## Core module work

- Read `.github/instructions/nexopos-modules.instructions.md` for module structure, metadata, discovery, namespaces, Vite configuration, and generator behavior.
- Read `.github/instructions/nexopos-module-quick-reference.instructions.md` only for a compact asset or file checklist. Treat `php artisan make:module` as authoritative over its manual `mkdir` example.
- Read `.github/instructions/nexopos.instructions.md` only when the change crosses between core application code and module code.
- Inspect `php artisan make:module --help` before scaffolding because command options may evolve.

Required identity alignment:

- Directory: `modules/{PascalCaseNamespace}`
- Metadata: `modules/{Namespace}/config.xml`
- Entry class: `{Namespace}Module.php`
- PHP namespace root: `Modules\{Namespace}`
- Views and module translations: use the same namespace string expected by nearby modules
- Tables, route names, permission namespaces, and option keys: use a stable module-specific prefix

## Backend capabilities

Read only the matching file:

| Capability | Source instruction | Key concern |
| --- | --- | --- |
| Migrations | `nexopos-migrations.instructions.md` | Discovery naming, existence guards, indexes, rollback safety |
| Models | `nexopos-models.instructions.md` | Keep cross-domain side effects out of model `boot()` |
| CRUD | `nexopos-crud-accurate.instructions.md` | Current CRUD helpers, lifecycle hooks, filters, permissions |
| Permissions | `nexopos-permissions.instructions.md` | Permission files, namespaces, assignment, UI checks |
| Roles and access internals | `nexopos-roles-permissions.instructions.md` | Models, service methods, assignment and test patterns |
| Restricted routes | `nexopos-middleware.instructions.md` | `NsRestrictMiddleware::arguments(...)` and server enforcement |
| Settings/options | `nexopos-options.instructions.md` | Defaults, casting, module key prefixes, validation, secrets |

Never rely solely on a hidden menu or button for authorization. Enforce access on the server and cover denial in a feature test.

## NexoPOS extension points

| Capability | Source instruction | Key concern |
| --- | --- | --- |
| Sidebar menus | `nexopos-asidemenu.instructions.md` | Stable identifiers, placement, permissions, translation |
| Render injection | `nexopos-view-injection.instructions.md` | Current render events and route/instance scoping |
| Widgets | `nexopos-widgets.instructions.md` | Widget registration, Vue injection, closure behavior |
| POS lifecycle | [pos-lifecycle.md](pos-lifecycle.md) | Cart buttons, initialization, order types, payment and submission hooks |

Prefer current event classes and listener discovery visible in comparable modules. The old string-hook view injection mechanism is removed; do not reintroduce it. Avoid broad footer injection without checking the route or target instance.

## Frontend capabilities

| Capability | Source instruction | Key concern |
| --- | --- | --- |
| Blade pages | `nexopos-blade-layouts.instructions.md` | Correct layout and section names |
| Inputs | `nexopos-forminput.instructions.md` | Existing input descriptors and conditional behavior |
| Frontend globals/localization | [frontend-apis.md](frontend-apis.md), then `nexopos-frontend-api.instructions.md` if needed | Globals, module localization, safe declarations |
| HTTP requests | [frontend-apis.md](frontend-apis.md), then `nexopos-httpclient.instructions.md` if needed | Observable API, current config limitations, errors |
| Popups | `nexopos-popup.instructions.md` | Built-ins, promise resolution, cancellation, cleanup |
| Tabs | `nexopos-tabs.instructions.md` | Identifiers, dynamic state, validation, accessibility |
| Theme colors | `nexopos-colors.instructions.md` | Semantic classes, theme parity, contrast |

`.github/instructions/nexopos-localization.instructions.md` currently contains only a heading. Use the localization guidance in [frontend-apis.md](frontend-apis.md) and verify real `Lang/` files and `__m()` usage in a maintained module.

Use existing components before adding new ones. For module-owned strings, follow nearby module usage of `__m`. Provide loading, success, error, and cancellation behavior where applicable.

Module asset rules:

```blade
@moduleViteAssets('Resources/ts/main.ts', 'ModuleNamespace')
```

- Do not use `@vite` for module entries.
- Do not add a leading slash or `modules/{Namespace}` to the asset path.
- Keep Vite output under `Public/build` and verify its manifest after building.
- Use Tailwind CSS v4 and NexoPOS semantic theme tokens; avoid legacy SCSS `@apply` patterns and hard-coded palette values.

## Choosing examples

Search `modules/` for a maintained module that exercises the same extension point. Compare it with the corresponding class under `app/`. A concise search for an exact event, directive, middleware, or helper is usually more useful than reading a full tutorial.

When the prose conflicts with working current code, follow the current code and preserve the discrepancy in the final handoff if it materially affected the implementation.
