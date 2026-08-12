# Creating Dashboard Widgets

Dashboard widgets consist of a PHP declaration and a Vue component. Core widget classes live in `app/Widgets`; module widget classes live in `modules/{Module}/Widgets`.

Before changing widget behavior, inspect `App\Services\WidgetService`, `resources/ts/components/ns-dragzone.vue`, and a comparable current widget. The dashboard uses one responsive CSS grid and persists one ordered layout per user.

## Widget declaration

Extend `App\Services\WidgetService`. Declare these properties:

- `name`: Human-readable name, assigned in the constructor.
- `description`: Short explanation, assigned in the constructor.
- `permission`: Optional permission required to see and configure the widget.
- `vueComponent`: Global Vue component identifier. Keep this property untyped to match the base class.
- `layout`: Suggested footprint as `columns x rows`, from `1x1` through `3x5`. Invalid declarations normalize to `1x1`.
- `layoutPolicy`: `strict`, `restricted`, or `unrestricted`. The default is `strict`.
- `supportedLayouts`: User-selectable sizes for a `restricted` widget. The suggested layout is always included.

Policy behavior:

| Policy | User sizing |
| --- | --- |
| `strict` | Suggested layout only; no selector is shown. |
| `restricted` | Suggested layout plus valid entries from `supportedLayouts`. |
| `unrestricted` | Every layout from `1x1` through `3x5`. |

Core example:

```php
<?php

namespace App\Widgets;

use App\Services\WidgetService;

class SalesOverviewWidget extends WidgetService
{
    protected $vueComponent = 'nsSalesOverviewWidget';

    protected string $layout = '2x2';

    protected string $layoutPolicy = 'restricted';

    protected array $supportedLayouts = [ '1x2', '2x2', '2x3', '3x2' ];

    public function __construct()
    {
        $this->name = __( 'Sales Overview' );
        $this->description = __( 'Displays recent sales activity.' );
        $this->permission = 'nexopos.see.sales-overview-widget';
    }
}
```

Module example:

```php
<?php

namespace Modules\MyModule\Widgets;

use App\Services\WidgetService;

class MyModuleWidget extends WidgetService
{
    protected $vueComponent = 'myModuleDashboardWidget';

    protected string $layout = '2x1';

    protected string $layoutPolicy = 'unrestricted';

    public function __construct()
    {
        $this->name = __m( 'Module Widget', 'MyModule' );
        $this->description = __m( 'Displays module information.', 'MyModule' );
        $this->permission = 'mymodule.view.widget';
    }

    public function getData(): array
    {
        return [ 'status' => 'ready' ];
    }
}
```

`getData()` must return JSON-safe data. It is exposed to Vue as `widget.data`. Do not perform authorization in Vue; `permission` is enforced by the server registry.

## Register the PHP widget

`WidgetService` is a singleton. Register module widgets from the module provider using dependency injection:

```php
use App\Services\WidgetService;
use Modules\MyModule\Widgets\MyModuleWidget;

public function boot( WidgetService $widgetService ): void
{
    $widgetService->registerWidgets( MyModuleWidget::class );
}
```

Core widgets are registered in `WidgetService`. Keep registry order separate from the fresh-install layout order described below.

## Register the Vue component

Core widgets are registered in `resources/ts/widgets.ts`:

```ts
import { defineAsyncComponent } from 'vue';

window['nsSalesOverviewWidget'] = defineAsyncComponent(
    () => import('./widgets/ns-sales-overview-widget.vue')
);
```

For modules, create a module Vite entry that assigns the exact `vueComponent` identifier:

```ts
// modules/MyModule/Resources/ts/widgets.ts
import { defineAsyncComponent } from 'vue';

window['myModuleDashboardWidget'] = defineAsyncComponent(
    () => import('./widgets/MyModuleDashboardWidget.vue')
);
```

Load that entry only on `ns.dashboard.home` through `RenderFooterEvent` and a small injected Blade view:

```php
public function handle( RenderFooterEvent $event ): void
{
    if ( $event->routeName === 'ns.dashboard.home' ) {
        $event->output->addView( 'MyModule::widgets.assets' );
    }
}
```

```blade
{{-- modules/MyModule/Resources/Views/widgets/assets.blade.php --}}
@moduleViteAssets('Resources/ts/widgets.ts', 'MyModule')
```

Use the shared Vue runtime supplied by the module Vite configuration. Never mount a nested Vue application inside `#dashboard-content`.

## Vue widget contract

Every widget component receives a `widget` prop and should provide:

- A root that fills the grid cell.
- An element with `widget-handle` for pointer dragging.
- A control that emits `onRemove` when the widget is closed.
- `<ns-widget-layout-selector :widget="widget" />` wherever a resizable widget should expose sizing.

The layout selector is opt-in and owned by the widget template. It self-hides for strict or single-layout widgets. Do not add floating layout controls to the grid.

```vue
<template>
    <article class="ns-box">
        <header class="ns-box-header">
            <h3>{{ __m( 'Module Widget', 'MyModule' ) }}</h3>
            <div>
                <ns-widget-layout-selector :widget="widget" />
                <ns-icon-button class="widget-handle" className="la-expand-arrows-alt" />
                <ns-close-button @click="$emit( 'onRemove' )" />
            </div>
        </header>
    </article>
</template>

<script>
import { __m } from '../i18n';

export default {
    name: 'my-module-dashboard-widget',
    props: [ 'widget' ],
    emits: [ 'onRemove' ],
    methods: { __m },
};
</script>
```

For module-owned Tailwind utilities, follow the module prefix and semantic theme-token rules in the `create-nexopos-module` skill. Core `ns-*` component hooks remain unprefixed.

## Fresh-install default order

`WidgetService::DEFAULT_WIDGET_ORDER` defines the order assigned when a user is first activated. It is deliberately different from registry order and packs the current core footprints without internal gaps:

1. Orders Chart (`2x2`)
2. Best Customers (`1x2`)
3. Orders Summary (`1x2`)
4. Best Cashiers (`1x2`)
5. Profile (`1x2`)
6. My NexoPOS (`2x1`)
7. Incomplete Sale (`1x1`)
8. Expense (`1x1`)
9. Sale (`1x1`)

This fills the first five rows completely and leaves only the final cell of row six empty—the minimum possible for 17 occupied cells in a three-column grid. Registered module widgets follow the core defaults in their registration order. Widgets removed by registry filters must not be reintroduced by default ordering.

When changing a core widget's suggested footprint or adding a core default, update `DEFAULT_WIDGET_ORDER` and the default-order test together. Existing users keep their persisted order; this default applies only when their widget layout is first created.

## Verification checklist

- PHP declaration uses a valid suggested layout and intentional policy.
- Restricted layouts contain only `1x1` through `3x5` values.
- Permission is enforced server-side.
- Vue identifier matches the PHP `vueComponent` exactly.
- Widget receives `widget`, emits `onRemove`, and provides a `widget-handle`.
- Resizable controls are placed by the widget with `ns-widget-layout-selector`.
- Module asset loads only on the dashboard home route and uses the shared Vue runtime.
- Module strings use `__m( ..., 'ModuleNamespace' )` in PHP and Vue.
- Default-order coverage is updated when core footprints change.
- Focused PHPUnit tests and the relevant Vite build pass.
