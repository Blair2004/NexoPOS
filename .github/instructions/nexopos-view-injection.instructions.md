---
applyTo: '**'
---

# NexoPOS View Injection Guide

This guide explains how to inject custom views into various parts of the NexoPOS dashboard using Laravel Events. This is the **official and only supported method** for injecting views in NexoPOS.

## Important Notice

⚠️ **Deprecated Method**: The old `Hook::addAction()` method for injecting views has been completely removed from NexoPOS. Do not use it.

✅ **Current Method**: Use Laravel Events with Event Listeners.

## Overview

NexoPOS fires various `Render*Event` classes at different points in the application. Modules can listen to these events and inject custom views into the rendered output.

## Available Render Events

All render events are located in `app/Events/` and follow the naming pattern `Render*Event`.

### Common Render Events

| Event | Description | Properties |
|-------|-------------|------------|
| `RenderFooterEvent` | Fired in layout footer | `$output`, `$routeName` |
| `RenderHeaderEvent` | Fired in layout header | `$output`, `$routeName` |
| `RenderCrudTableFooterEvent` | Fired in CRUD table footer | `$output`, `$instance` |
| `RenderCrudFormFooterEvent` | Fired in CRUD form footer | `$output`, `$instance` |
| `RenderSettingsFooterEvent` | Fired in settings footer | `$output` |
| `RenderProfileFooterEvent` | Fired in user profile footer | `$output` |
| `RenderLoginFooterEvent` | Fired in login page footer | `$output` |
| `RenderSignUpFooterEvent` | Fired in signup page footer | `$output` |
| `RenderPasswordLostFooterEvent` | Fired in password recovery footer | `$output` |
| `RenderNewPasswordFooterEvent` | Fired in new password page footer | `$output` |

## Event Properties

### Output Object

All render events include an `Output` object (`App\Classes\Output`) with the following methods:

```php
// Add a Blade view
$event->output->addView(string $viewName, array $data = []);

// Get all views
$event->output->getViews();
```

### Instance Property

Events like `RenderCrudTableFooterEvent` include an `$instance` property that contains the CRUD class instance, allowing you to check which CRUD is being rendered.

## Creating Event Listeners

### Step 1: Create Listener Class

Create a listener in your module's `Listeners/` directory:

```php
<?php

namespace Modules\YourModule\Listeners;

use App\Crud\ProductCrud;
use App\Crud\CustomerCrud;
use App\Events\RenderCrudTableFooterEvent;

class RenderCrudTableFooterEventListener
{
    /**
     * Handle the event.
     */
    public function handle(RenderCrudTableFooterEvent $event)
    {
        // Check which CRUD instance is being rendered
        if ($event->instance instanceof ProductCrud) {
            $event->output->addView('YourModule::product-actions', [
                'product_id' => request()->route('id')
            ]);
        }

        if ($event->instance instanceof CustomerCrud) {
            $event->output->addView('YourModule::customer-actions');
        }
    }
}
```

### Step 2: Automatic Event Discovery

Laravel automatically discovers event listeners based on the `handle()` method's type hint. No manual registration is needed.

**How it works:**
- Laravel scans your listener's `handle()` method
- Detects the event type from the parameter type hint
- Automatically registers the listener for that event

**Requirements:**
- Listener must be in a `Listeners/` directory
- Method must be named `handle()`
- Method must type-hint the event class

## Complete Examples

### Example 1: Inject Button in CRUD Table

**Listener:** `Modules/YourModule/Listeners/RenderCrudTableFooterEventListener.php`

```php
<?php

namespace Modules\YourModule\Listeners;

use App\Crud\ProductCrud;
use App\Events\RenderCrudTableFooterEvent;

class RenderCrudTableFooterEventListener
{
    public function handle(RenderCrudTableFooterEvent $event)
    {
        if ($event->instance instanceof ProductCrud) {
            $event->output->addView('YourModule::crud-button', [
                'label' => __m('Custom Action', 'YourModule'),
                'icon' => 'la-magic'
            ]);
        }
    }
}
```

**View:** `Modules/YourModule/Resources/Views/crud-button.blade.php`

```blade
<div class="flex items-center justify-end">
    <button onclick="performAction()" class="ns-button info">
        <i class="las {{ $icon }} mr-2"></i>
        {{ $label }}
    </button>
</div>

@section('layout.dashboard.footer.inject')
    @moduleViteAssets('Resources/ts/main.ts', 'YourModule')
@endsection
```

### Example 2: Inject Script in Footer

**Listener:** `Modules/YourModule/Listeners/RenderFooterEventListener.php`

```php
<?php

namespace Modules\YourModule\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterEventListener
{
    public function handle(RenderFooterEvent $event)
    {
        // Inject on specific route
        if ($event->routeName === 'ns.dashboard.home') {
            $event->output->addView('YourModule::dashboard-widget');
        }

        // Inject on all routes
        $event->output->addView('YourModule::global-script');
    }
}
```

### Example 3: Multiple CRUD Classes

**Listener:** `Modules/YourModule/Listeners/RenderCrudTableFooterEventListener.php`

```php
<?php

namespace Modules\YourModule\Listeners;

use App\Crud\ProductCrud;
use App\Crud\ProductCategoryCrud;
use App\Crud\CustomerCrud;
use App\Events\RenderCrudTableFooterEvent;

class RenderCrudTableFooterEventListener
{
    public function handle(RenderCrudTableFooterEvent $event)
    {
        // Products
        if ($event->instance instanceof ProductCrud) {
            $event->output->addView('YourModule::product-button', [
                'type' => 'product'
            ]);
        }

        // Categories
        if ($event->instance instanceof ProductCategoryCrud) {
            $event->output->addView('YourModule::category-button', [
                'type' => 'category'
            ]);
        }

        // Customers
        if ($event->instance instanceof CustomerCrud) {
            $event->output->addView('YourModule::customer-button', [
                'type' => 'customer'
            ]);
        }
    }
}
```

### Example 4: Settings Page Footer

**Listener:** `Modules/YourModule/Listeners/RenderSettingsFooterEventListener.php`

```php
<?php

namespace Modules\YourModule\Listeners;

use App\Events\RenderSettingsFooterEvent;

class RenderSettingsFooterEventListener
{
    public function handle(RenderSettingsFooterEvent $event)
    {
        $event->output->addView('YourModule::settings-notice', [
            'message' => __m('Additional configuration available', 'YourModule')
        ]);
    }
}
```

## Best Practices

### 1. Use Specific Event Types

```php
// ✅ Good - Specific to CRUD tables
use App\Events\RenderCrudTableFooterEvent;

class RenderCrudTableFooterEventListener
{
    public function handle(RenderCrudTableFooterEvent $event)
    {
        // Your logic
    }
}
```

### 2. Check Instance Types

```php
// ✅ Good - Check before injecting
if ($event->instance instanceof ProductCrud) {
    $event->output->addView('YourModule::view');
}

// ❌ Bad - Inject without checking
$event->output->addView('YourModule::view'); // Will inject on ALL CRUDs
```

### 3. Pass Necessary Data

```php
// ✅ Good - Pass data to view
$event->output->addView('YourModule::view', [
    'item_id' => $itemId,
    'user' => auth()->user(),
    'config' => $config
]);

// ❌ Bad - No data passed when needed
$event->output->addView('YourModule::view'); // View may need data
```

### 4. Use Route Checking for RenderFooterEvent

```php
public function handle(RenderFooterEvent $event)
{
    // Only inject on specific route
    if ($event->routeName === 'ns.dashboard.products') {
        $event->output->addView('YourModule::product-footer');
    }
}
```

### 5. One Listener Per Event Type

```php
// ✅ Good - One listener per event
// RenderCrudTableFooterEventListener.php
// RenderFooterEventListener.php
// RenderSettingsFooterEventListener.php

// ❌ Bad - Don't create multiple listeners for same event
// RenderCrudTableFooterEventListener1.php
// RenderCrudTableFooterEventListener2.php
```

## View Structure

### Basic View Template

```blade
{{-- Modules/YourModule/Resources/Views/injection.blade.php --}}

<div class="your-custom-content">
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>
</div>

{{-- Load module assets if needed --}}
@section('layout.dashboard.footer.inject')
    @moduleViteAssets('Resources/ts/main.ts', 'YourModule')
    @moduleViteAssets('Resources/css/style.css', 'YourModule')
@endsection
```

### With JavaScript Interaction

```blade
<button id="custom-action-btn" class="ns-button info">
    <i class="las la-magic mr-2"></i>
    {{ __m('Custom Action', 'YourModule') }}
</button>

@section('layout.dashboard.footer.inject')
    @moduleViteAssets('Resources/ts/main.ts', 'YourModule')
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('custom-action-btn');
            if (btn) {
                btn.addEventListener('click', function() {
                    // Your logic here
                    if (typeof Popup !== 'undefined') {
                        Popup.show(nsExtraComponents['your-popup']);
                    }
                });
            }
        });
    </script>
@endsection
```

## Debugging

### Check if Event is Firing

Add logging to your listener:

```php
public function handle(RenderCrudTableFooterEvent $event)
{
    \Log::info('RenderCrudTableFooterEvent fired', [
        'instance' => get_class($event->instance)
    ]);
    
    // Your logic
}
```

### Verify Listener Registration

Laravel automatically discovers listeners. To verify:

```bash
php artisan event:list
```

This will show all registered events and their listeners.

## Common Issues

### Issue: View Not Appearing

**Solutions:**
1. Check listener is in `Listeners/` directory
2. Verify `handle()` method type-hints the correct event
3. Clear cache: `php artisan cache:clear`
4. Check view path: `YourModule::view-name`

### Issue: View Appearing in Wrong Place

**Solution:** Check you're listening to the correct event type and using proper instance checks.

### Issue: Multiple Injections

**Solution:** Ensure you're checking instance types to avoid injecting on all CRUDs.

## Migration from Old Hook System

### Old Method (REMOVED - Do Not Use)

```php
// ❌ DEPRECATED - This no longer works
Hook::addAction('ns-crud-footer', function ($output, $identifier) {
    // This will not work
});
```

### New Method (Current)

```php
// ✅ CORRECT - Use Laravel Events
use App\Events\RenderCrudTableFooterEvent;

class RenderCrudTableFooterEventListener
{
    public function handle(RenderCrudTableFooterEvent $event)
    {
        $event->output->addView('YourModule::view');
    }
}
```

## Summary

1. **Use Laravel Events** - The only supported method for view injection
2. **Create Listeners** - Place in `Listeners/` directory with proper type hints
3. **Check Instances** - Use `instanceof` to target specific CRUDs
4. **Automatic Discovery** - No manual registration needed
5. **Pass Data** - Provide necessary data to views
6. **Use @moduleViteAssets** - Load module assets properly

## Reference

For a complete list of available events:
```bash
ls app/Events/Render*Event.php
```

Each event follows the same pattern - create a listener, type-hint the event in `handle()`, and use `$event->output->addView()` to inject your content.
