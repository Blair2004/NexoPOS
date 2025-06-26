# Creating a Widgets

The creation of a widget depends on wether we want to do it for a module or for the core. When a module is created for the core, it's stored on the diretory `app/Widgets`. 
When a module is created, it's stored on the directory `modules/<module_name>/Widgets`.

## Declaring a Widgets
The first step is to create a class that extends the `App\Services\Widgets` class. 
This class is mostly declarative as it's there the following properties are defined:

- `name`: The human readable name of the widget.
- `description`: A short description of the widget.
- `permission`: which is a string that represents the permission required to access the widget.
- `vueComponent`: The name of the Vue component that will be used to render the widget.

**Example:**
This is an example of a widget created on NexoPOS
```php
namespace App\Widgets;
use App\Services\Widgets;

class MyWidget extends Widgets
{
    
    protected string $permission = 'view_my_widget';
    protected string $vueComponent = 'CustomWidgetComponent';

    public function __construct()
    {
        $this->name = __( 'Widget name' );
        $this->description = __( 'Widget Description' );
    }
}
```

This is an example of a widget created on a module
```php
namespace Modules\MyModule\Widgets;
use App\Services\WidgetService;

class MyModuleWidget extends WidgetService
{
    
    protected string $permission = 'view_my_module_widget';
    protected string $vueComponent = 'CustomWidgetComponent';

    public function __construct()
    {
        $this->name = __m( 'My Module Widget Name', 'MyModule' );
        $this->description = __m( 'My Module Widget Description', 'MyModule' );
    }
}
```

## Register Widget Class
For NexoPOS to be aware of the widget, we need to register it. This is done using the WidgetService class like so:

```php
namespace App\Providers;
use App\Services\WidgetService;
use App\Widgets\MyWidget;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register the widget class
        $service = app()->make( WidgetService::class );
        // or for a module
        $service->registerWidgets([
            MyWidget::class,
        ]);
    }
}
```

## Injecting Vue Component For Module
In order for the widget to be rendered, we need to inject the Vue component on the dashboard. There is one place
the widgets needs to be injected: the dashboard. We'll make use of the event `App\Events\RenderFooterEvent`. Typically, we'll create a Listeners that listen
that listen to that event which has as properties output (`App\Classes\Output`) and routeName which is a string of the current route name.

We'll then need to make sure the route name is "ns.dashboard.home", then we'll use the method addView of the output class like so:

```php
namespace Modules\MyModule\Listeners;
use App\Classes\Output;
use App\Events\RenderFooterEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RenderFooterEventListener
{
    public function handle(RenderFooterEvent $event)
    {
        if ( $event->routeName === 'ns.dashboard.home' ) {
            $event->output->addView( 'MyModule::widgets.my-module-widget' ); // if loaded on a module
        }
    }
}
```

Now, the content of the view file might looks like this:

```blade
<script>
document.addEventListener( 'DOMContentLoaded', function() {
    window[ 'CustomWidgetComponent' ] = defineComponent({
        template: `<div class="ns-box">
            <!-- The content of the widget goes here -->
        </div>`
    })
})
</script>
```

You'll note that we used `CustomWidgetComponent` as the name of the Vue component. This is the same name we used in the widget class.

## Registering The Component on NexoPOS
When creating the widget for NexoPOS, you need to define it with the core application files instead of injecting as we did for modules.
We'll then open the file `resources\ts\widgets.ts` where all core widgets are defined. Within that file, we'll add our widget like so:

```typescript
import { defineAsyncComponent } from 'vue';

window['CustomWidgetComponent'] = defineAsyncComponent(() => import( './widgets/custom-widget-component.vue' ));
```

## Providing Closure Feature
A widget should be closable. This is done by providing a closure feature.
We need to include a button that emit the string `onRemove` when clicked. You might use the built-in component such as <ns-close-button></ns-close-button> or create your own button that emits the event.
Here is an example of how to do that:

```vue
<template>
    <div class="ns-box">
        <div class="ns-box-header flex justify-between items-center p-2">
            <h3>{{ $t('Widget Name') }}</h3>
            <ns-close-button @click="$emit('onRemove')" />
        </div>
        <!-- The content of the widget goes here -->
    </div>
</template>
```

## Widget Handle
To allow a widget to be draggable, we need to define a handle. The handle is any visual element having as class "widget-handle". You can use the element <ns-icon-button></ns-icon-button> to create a handle like so:

```vue
<template>
    <div class="ns-box">
        <div class="ns-box-header flex justify-between items-center p-2">
            <h3>{{ $t('Widget Name') }}</h3>
            <div class="flex -mx-1">
                <div class="px-1">
                    <ns-icon-button class="widget-handle" className="[line icon]" />
                </div>
                <div class="px-1">
                    <ns-close-button @click="$emit('onRemove')" />
                </div>
            </div>
        </div>
        <!-- The content of the widget goes here -->
    </div>
</template>
```