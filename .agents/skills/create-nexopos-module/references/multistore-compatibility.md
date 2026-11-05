# Multistore module compatibility

NexoPOS Multistore runs the normal application inside an active store context. In path mode, dashboard URLs use `/dashboard/store/{store_id}/...` and API URLs use `/api/store/{store_id}/...`; subdomain mode applies the same context on the store subdomain. Store-owned database tables are prefixed with `store_{store_id}_`.

Use `modules/NsGastro` as the established route-integration reference. `modules/NsAppointments` and `modules/NsQrMenu` also contain compatibility contract tests.

## Models and queries

- Extend `App\Models\NsModel`, not Laravel's base `Model`.
- Define the unprefixed table name in `$table`. `NsModel` applies the `ns-model-table` filter when instantiated.
- Use model queries for store-owned module data.
- If a query builder is necessary, resolve the table with `(new Model)->getTable()` or `Hook::filter('ns-table-name', $table)`. Never pass a hard-coded store-owned table to `DB::table()`.
- Relationships to compatible core models remain valid because those models use the same table filter.
- Shared tables intentionally excluded by Multistore, such as users, roles, permissions, and stores, must remain unprefixed. Verify the current `StoresService::$ignored_tables` list rather than assuming a table is shared.

## Migrations

- Import `App\Classes\Schema`, not `Illuminate\Support\Facades\Schema`.
- Pass unprefixed table names to `Schema`; it applies the `ns-table-name` filter for the active store.
- Use `Schema::createIfMissing()` and guard table, column, and index operations so a store migration can resume safely after partial DDL.
- Permission-only migrations may use shared permission and role models because those tables are intentionally unprefixed.
- Data backfills must resolve store-aware table names too. The custom `Schema` class only affects its schema calls; it does not make a hard-coded `DB::table()` call store-aware.
- Migrations are included in store creation by default. Opt out only for genuinely root-only migrations by declaring `public bool $multistore = false` on the migration.

## Routes

- Keep normal routes in `Routes/web.php` and `Routes/api.php`.
- Put authenticated dashboard routes that must exist inside a store in `Routes/multistore.php`.
- Do not repeat the `dashboard` prefix or authentication middleware in `Routes/multistore.php`; Multistore loads it inside its existing store dashboard group.
- Register the route bridges from the module provider:

```php
use App\Services\ModulesService;
use Illuminate\Support\Facades\Event;
use Modules\NsMultiStore\Events\MultiStoreApiRoutesLoadedEvent;
use Modules\NsMultiStore\Events\MultiStoreWebRoutesLoadedEvent;

Event::listen(MultiStoreApiRoutesLoadedEvent::class, fn () => ModulesService::loadModuleFile('Example', 'Routes/api'));
Event::listen(MultiStoreWebRoutesLoadedEvent::class, fn () => ModulesService::loadModuleFile('Example', 'Routes/multistore'));
```

- Include `SubstituteBindings` in module route groups.
- Define store route names with `ns()->routeName()` and generate links with `ns()->route()` or `ns()->url()`. Direct `route()` and `url()` calls bypass Multistore's route and URL filters.
- Public, unauthenticated storefront routes need a separate design. `MultiStoreWebRoutesLoadedEvent` is dispatched inside the authenticated dashboard group and must not expose public pages. Inspect `MultiStorePublicRoutesLoadedEvent` and both Multistore routing modes before adding public store routes.

## Dashboard menus

Multistore filters the root dashboard menu before later module menu filters run. Add operational menus only when a normal store anchor such as `orders` or `inventory` exists. This keeps module menus off the Multistore root dashboard while preserving them on regular NexoPOS and inside a store.

Use `ns()->route()` or `ns()->url()` for every menu link. `AsideMenu::menu()` and `AsideMenu::subMenu()` already return keyed arrays, so merge them directly; never wrap the result under the identifier again.

## Verification

- Assert every module model extends `NsModel`.
- Assert every schema migration imports `App\Classes\Schema`.
- Assert `Routes/multistore.php` exists and the provider listens for both Multistore route events.
- Assert store-owned runtime and migration queries do not hard-code table names in `DB::table()`.
- Assert the module menu is unchanged when store-dashboard anchors are absent.
- Exercise route generation and table resolution in an active store context when a safe Multistore database fixture is available.
