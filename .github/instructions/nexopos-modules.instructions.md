---
applyTo: '**'
---

# NexoPOS Module Development Guide

This comprehensive guide explains how to create, structure, and develop modules for NexoPOS. Modules extend the functionality of NexoPOS and follow a specific architecture pattern similar to Laravel applications.

## Module Overview

NexoPOS modules are self-contained packages that extend the core functionality of the application. Each module is stored in the `/modules` directory and follows a standardized structure that allows for seamless integration with the main application.

## Module Directory Structure

Modules are located in the `/modules` directory, where each module has its own folder named after the module's namespace. For example, a module with namespace `FooBar` would be stored in `/modules/FooBar/`.

### Basic Module Structure

```
/modules/FooBar/
├── config.xml              # Module configuration and metadata
├── manifest.json           # Export/import file configuration (optional)
├── FooBarModule.php        # Main module entry class
├── Casts/                  # Custom Eloquent casts
├── Crud/                   # Generated CRUD classes
├── Events/                 # Event classes
├── Http/                   # Controllers and Request classes
│   ├── Controllers/
│   └── Requests/
├── Jobs/                   # Queue job classes
├── Lang/                   # Language files
├── Listeners/              # Event listeners
├── Migrations/             # Database migrations
├── Models/                 # Eloquent models
├── Providers/              # Service providers
├── Public/                 # Public assets (symlinked)
├── Resources/              # Views and frontend assets
│   ├── Views/
│   ├── ts/                 # TypeScript files
│   └── scss/               # SCSS files
├── Routes/                 # Route definitions
│   ├── api.php
│   └── web.php
├── Services/               # Service classes
├── Settings/               # Module settings
├── Tests/                  # Test files
└── Traits/                 # Reusable traits
```

## Module Configuration

### config.xml

Every module must include a `config.xml` file that describes the module's metadata and signature. This is how NexoPOS recognizes and loads the module.

**Structure:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<module>
    <namespace>FooBar</namespace>
    <version>1.0.0</version>
    <author>Your Name</author>
    <name>Foo Bar Module</name>
    <description>A sample module that demonstrates NexoPOS module structure</description>
</module>
```

**Required Tags:**

- **`<module>`**: Root container tag for all module metadata
- **`<namespace>`**: Unique identifier for the module
  - Must be in PascalCase format
  - Cannot start with a number
  - No spaces or special characters allowed
  - Examples: `FooBar`, `InventoryManager`, `SalesReport`
- **`<version>`**: Numeric version notation
  - Contains only numbers and dots
  - No leading "v" (e.g., `1.0.0`, not `v1.0.0`)
  - Follow semantic versioning: `major.minor.patch`
- **`<author>`**: Name of the module author/developer
- **`<name>`**: Human-readable display name for the module
- **`<description>`**: Brief description of the module's functionality

### manifest.json (Optional)

The `manifest.json` file controls which files are included or excluded when the module is exported. This is useful for packaging modules for distribution.

**Structure:**

```json
{
    "include": [
        "config.xml",
        "FooBarModule.php",
        "Http/**",
        "Models/**",
        "Resources/Views/**"
    ],
    "exclude": [
        "Tests/**",
        "node_modules/**",
        ".env",
        "*.log"
    ]
}
```

**Properties:**

- **`include`**: Array of relative paths (from module root) to include in exports
- **`exclude`**: Array of relative paths (from module root) to exclude from exports

Both properties support glob patterns for flexible file matching.

## Vite Configuration
NexoPOS is built on top of Vite, Vue and Tailwind. If module can use their own frontend framework, it's recommended to stick to this stack. Therefore, we'll create a default vite.config.js. We'll make use of the following packages:

- laravel-vite-plugin
- @vitejs/plugin-vue
- @tailwindcss/vite

As Vue is already included on NexoPOS, it's not required to use it on our module. In fact, we want our component to work seamlessly with NexoPOS, we'll then use it's API. Typically here is how a vite.config.js looks like:

```js
import { defineConfig, loadEnv } from 'vite';

import { fileURLToPath } from 'node:url';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import vuePlugin from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

const Vue = fileURLToPath(
	new URL(
		'vue',
		import.meta.url
	)
);

export default ({ mode }) => {
    return defineConfig({
        base: '/',
        plugins: [
            vuePlugin(),
            laravel({
                hotFile: 'Public/hot',
                input: [
                    'Resources/css/style.css',
                    'Resources/ts/main.ts',
                ],
                refresh: [ 
                    'Resources/**', 
                ]
            }),
            tailwindcss(),
        ],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'Resources/ts'),
            }
        },
        build: {
            outDir: 'Public/build',
            manifest: true,
            rollupOptions: {
                input: [
                    './Resources/css/style.css',
                    './Resources/ts/main.ts',
                ],
            }
        }        
    });
}
```

## Main Module Class

### Entry Point

Each module must have a main entry class named `{Namespace}Module.php`. For a module with namespace `FooBar`, the file would be `FooBarModule.php`.

**File Location:** `/modules/FooBar/FooBarModule.php`

**Namespace Convention:** All module files use the namespace pattern `Modules\{ModuleNamespace}\{SubNamespace}`

**Example:**

```php
<?php

namespace Modules\FooBar;

use App\Services\Module;

class FooBarModule extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );
    }
}
```

## Module Directory Details

### Casts/

Store custom Eloquent casts following Laravel 11+ patterns:

```php
<?php

namespace Modules\FooBar\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return number_format($value / 100, 2);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return (int) ($value * 100);
    }
}
```

### Crud/

Generated CRUD classes following NexoPOS CRUD patterns:

```php
<?php

namespace Modules\FooBar\Crud;

use App\Services\CrudService;
use Modules\FooBar\Models\Product;

class ProductCrud extends CrudService
{
    const AUTOLOAD = true;
    const IDENTIFIER = 'foobar.products';

    protected $table = 'foobar_products';
    protected $model = Product::class;
    protected $namespace = 'foobar.products';

    // CRUD implementation...
}
```

### Events/

Event classes for module-specific events:

```php
<?php

namespace Modules\FooBar\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated
{
    use Dispatchable, SerializesModels;

    public $product;

    public function __construct($product)
    {
        $this->product = $product;
    }
}
```

### Http/

#### Controllers/

Module controllers following Laravel conventions:

```php
<?php

namespace Modules\FooBar\Http\Controllers;

use App\Http\Controllers\DashboardController;
use Modules\FooBar\Models\Product;

class ProductController extends DashboardController
{
    public function index()
    {
        return view('FooBar::products.index');
    }

    public function create()
    {
        return view('FooBar::products.create');
    }
}
```

#### Requests/

Form request validation classes:

```php
<?php

namespace Modules\FooBar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->allowedTo('create.foobar.products');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ];
    }
}
```

### Jobs/

Queue job classes:

```php
<?php

namespace Modules\FooBar\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Job implementation
    }
}
```

### Lang/

Language files for localization:

```
Lang/
├── en.json
├── fr.json
└── es.json
```

**Example `en.json`:**

```json
{
    "Product Name": "Product Name",
    "Create Product": "Create Product",
    "Product created successfully": "Product created successfully"
}
```

### Listeners/

Event listener classes:

```php
<?php

namespace Modules\FooBar\Listeners;

use Modules\FooBar\Events\ProductCreated;

class ProductCreatedListener
{
    public function handle(ProductCreated $event)
    {
        // Handle the event
    }
}
```

Note that Listeners are automatically discovered if an event class is provided on the handle method.

### Migrations/

Database migration files following Laravel conventions:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('foobar_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('foobar_products');
    }
};
```

However, migration file name aren't required to follow Laravel's timestamp pattern. For a migration that create tables, we'll use the prefix `Create` followed by the table name. For example: `CreateFooBarTable.php`.
For a migration that alters an existing table, we'll use the prefix `Update` followed by the table name. For example: `UpdateFooBarTable.php`.

### Models/

Eloquent model classes:

```php
<?php

namespace Modules\FooBar\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'foobar_products';
    
    protected $fillable = [
        'name',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];
}
```

### Providers/

Service provider classes:

```php
<?php

namespace Modules\FooBar\Providers;

use Illuminate\Support\ServiceProvider;

class FooBarServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services
    }

    public function boot()
    {
        // ...
    }
}
```

There is no need to manually register the service provider as NexoPOS automatically discovers it.

### Public/

Public assets accessible via URL (symlinked):

```
Public/
├── css/
│   └── module.css
├── js/
│   └── module.js
└── images/
    └── logo.png
```

Assets are accessible at `/modules/{namespace}/...`

### Resources/

#### Views/

Blade template files:

```
Resources/Views/
├── layouts/
│   └── master.blade.php
├── products/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
└── partials/
    └── header.blade.php
```

**Example view:**

```blade
@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    <div class="px-4 flex justify-between">
        <h3 class="text-xl font-semibold">{{ __('Products') }}</h3>
        <a href="{{ route('foobar.products.create') }}" class="btn btn-primary">
            {{ __('Create Product') }}
        </a>
    </div>
    
    <div class="px-4 flex-auto">
        <ns-crud
            identifier="foobar.products"
            :columns="columns"
            :actions="actions">
        </ns-crud>
    </div>
</div>
@endsection
```

#### Loading Vite Assets in Views

**IMPORTANT:** When including Vite-compiled assets in module Blade views, you **must** use the `@moduleViteAssets` directive instead of the standard Laravel `@vite` directive.

**Syntax:**
```blade
@moduleViteAssets('path/to/asset', 'ModuleNamespace')
```

**Parameters:**
1. **Asset Path** (string): Relative path from module root directory (no leading slash)
2. **Module Namespace** (string): The module identifier from `config.xml`

**Example:**
```blade
@push('footer-scripts')
    @moduleViteAssets('Resources/ts/main.ts', 'FooBar')
    @moduleViteAssets('Resources/css/style.css', 'FooBar')
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Your module initialization code
            if (typeof myModuleFunction !== 'undefined') {
                myModuleFunction();
            }
        });
    </script>
@endpush
```

**Common Patterns:**

Loading multiple assets:
```blade
@moduleViteAssets('Resources/ts/main.ts', 'FooBar')
@moduleViteAssets('Resources/css/style.css', 'FooBar')
@moduleViteAssets('Resources/ts/admin-panel.ts', 'FooBar')
```

With conditional loading:
```blade
@if(auth()->user()->allowedTo('manage.foobar'))
    @moduleViteAssets('Resources/ts/admin-features.ts', 'FooBar')
@endif
```

**Common Mistakes to Avoid:**

❌ **Wrong - Using standard @vite:**
```blade
@vite(['modules/FooBar/Resources/ts/main.ts'])
```

❌ **Wrong - Including leading slash:**
```blade
@moduleViteAssets('/Resources/ts/main.ts', 'FooBar')
```

❌ **Wrong - Using full path:**
```blade
@moduleViteAssets('modules/FooBar/Resources/ts/main.ts', 'FooBar')
```

✅ **Correct:**
```blade
@moduleViteAssets('Resources/ts/main.ts', 'FooBar')
```

**How It Works:**

The `@moduleViteAssets` directive:
- Resolves the correct module path automatically
- Reads the module's Vite manifest file (`Public/build/.vite/manifest.json`)
- Includes the properly hashed and versioned assets
- Works with hot-reload during development (`npm run dev`)
- Ensures proper cache busting in production

**Build Output Structure:**

After running `npm run build`, your assets will be in:
```
modules/FooBar/Public/build/
├── .vite/
│   └── manifest.json
└── assets/
    ├── main-[hash].js
    └── style-[hash].css
```

The directive automatically resolves these hashed filenames from the manifest.

#### TypeScript (ts/)

TypeScript files for frontend functionality:

```typescript
// Resources/ts/components/ProductManager.ts
export class ProductManager {
    private products: Product[] = [];

    async loadProducts(): Promise<Product[]> {
        const response = await fetch('/api/foobar/products');
        this.products = await response.json();
        return this.products;
    }
}
```

**Exporting Components for Blade Templates:**

When creating Vue components that need to be triggered from Blade templates, export them globally in your main entry file:

```typescript
// Resources/ts/main.ts
import { Popup } from '@/libraries/popup';
import MyComponent from './components/MyComponent.vue';

// Export the component globally for access from Blade templates
const showMyComponent = () => {
    Popup.show(MyComponent, {
        // Component props
    });
};

// Make it available on window object
(window as any).myModuleName = {
    showMyComponent
};

export { showMyComponent };
```

Then in your Blade template:

```blade
@moduleViteAssets('Resources/ts/main.ts', 'MyModule')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof myModuleName !== 'undefined' && myModuleName.showMyComponent) {
            myModuleName.showMyComponent();
        }
    });
</script>
```

**TypeScript Type Declarations:**

Create a `types.d.ts` file for NexoPOS API type declarations:

```typescript
// Resources/ts/types.d.ts
declare module '@/libraries/lang' {
    export function __(key: string): string;
    export function __m(key: string, namespace: string): string;
}

declare module '@/bootstrap' {
    export const nsHttpClient: any;
    export const nsSnackBar: any;
}

declare module '@/libraries/popup' {
    export class Popup {
        static show(component: any, params?: any, config?: any): any;
    }
}

// Global popup components available in NexoPOS
declare const nsAlertPopup: any;
declare const nsConfirmPopup: any;
declare const nsPromptPopup: any;
declare const nsSelectPopup: any;
declare const nsMedia: any;
declare const nsPosLoadingPopup: any;
```

#### CSS with Tailwind CSS v4

**IMPORTANT:** NexoPOS modules use Tailwind CSS v4 which has significant changes from v3:

1. **No `tailwind.config.js` file** - Configuration is done directly in CSS
2. **Prefix required for modules** - Use a unique prefix to avoid class name collisions with core NexoPOS
3. **New `@import` syntax** - Import Tailwind with the `prefix()` function

**Module CSS Structure:**

```css
/* Resources/css/style.css */
@import "tailwindcss" prefix(modulecode);
```

Where `modulecode` is a short, unique identifier for your module (2-4 characters recommended).

**Examples:**
- `FooBar` module → `prefix(fb)`
- `NsQuickConfig` module → `prefix(qc)`
- `CloudDeployer` module → `prefix(cd)`
- `InventoryManager` module → `prefix(im)`

**Using Prefixed Classes in Vue Components:**

```vue
<template>
    <div class="fb:max-w-3xl fb:mx-auto">
        <h2 class="fb:text-2xl fb:font-bold fb:mb-6">Title</h2>
        <p class="fb:text-gray-600 fb:mb-4">Description</p>
        
        <input 
            type="text"
            class="fb:w-full fb:border fb:border-gray-300 fb:rounded-lg fb:px-4 fb:py-2 focus:fb:ring-2 focus:fb:ring-blue-500"
        />
        
        <button class="fb:bg-blue-500 fb:text-white fb:px-4 fb:py-2 fb:rounded hover:fb:bg-blue-600">
            Submit
        </button>
    </div>
</template>
```

**Key Points:**

- **Every Tailwind class must have the prefix** - `fb:text-xl` not `text-xl`
- **Responsive prefixes come after module prefix** - `fb:md:text-2xl`
- **Pseudo-classes use module prefix** - `hover:fb:bg-blue-600`, `focus:fb:ring-2`
- **No tailwind.config.js needed** - Tailwind v4 doesn't use it
- **Prevents collisions** - Your `fb:text-xl` won't conflict with core's `text-xl`

**Complete Example:**

```css
/* Resources/css/style.css */
@import "tailwindcss" prefix(fb);

/* Optional: Add custom CSS for your module */
.fb-custom-component {
    /* Custom styles that aren't Tailwind utilities */
    background-image: linear-gradient(to right, #4f46e5, #7c3aed);
}
```

```vue
<!-- Resources/ts/components/ProductCard.vue -->
<template>
    <div class="fb:bg-white fb:rounded-lg fb:shadow-md fb:p-4 fb:hover:shadow-lg fb:transition-shadow">
        <h3 class="fb:text-lg fb:font-semibold fb:text-gray-800 fb:mb-2">
            {{ product.name }}
        </h3>
        <p class="fb:text-gray-600 fb:text-sm fb:mb-4">
            {{ product.description }}
        </p>
        <div class="fb:flex fb:justify-between fb:items-center">
            <span class="fb:text-xl fb:font-bold fb:text-green-600">
                {{ formatPrice(product.price) }}
            </span>
            <button class="fb:bg-blue-500 fb:text-white fb:px-4 fb:py-2 fb:rounded fb:text-sm hover:fb:bg-blue-600 fb:transition-colors">
                Add to Cart
            </button>
        </div>
    </div>
</template>
```

**Migration from Tailwind v3:**

If you have existing code with Tailwind v3 (using `tailwind.config.js`):

1. **Delete `tailwind.config.js`** - No longer needed
2. **Create/update `Resources/css/style.css`** with `@import "tailwindcss" prefix(yourprefix);`
3. **Add prefix to all Tailwind classes** in your Vue components
4. **Update vite.config.js** to ensure it includes the CSS file in the input array

**Troubleshooting:**

If you see errors like "Cannot apply unknown utility class `border-gray-300`":
- Check that your CSS file has the `@import "tailwindcss" prefix(...)` directive
- Verify all Tailwind classes in your Vue files use your prefix
- Ensure the CSS file is included in vite.config.js inputs
- Run `npm run build` to regenerate assets

#### SCSS (scss/) - Legacy

**Note:** SCSS with `@apply` directives is legacy and not recommended with Tailwind v4. Use the CSS + prefix approach above instead.

### Routes/

Both web.php and api.php routes file doesn't need to be manually registered. NexoPOS discovers it automatically.

#### api.php

API route definitions:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\FooBar\Http\Controllers\Api\ProductController;

Route::prefix('foobar')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

#### web.php

Web route definitions:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\FooBar\Http\Controllers\ProductController;

Route::prefix('dashboard/foobar')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])
        ->name('foobar.products.index');
    
    Route::get('/products/create', [ProductController::class, 'create'])
        ->name('foobar.products.create');
});
```

### Services/

Service classes for business logic:

```php
<?php

namespace Modules\FooBar\Services;

class ProductService
{
    public function createProduct(array $data)
    {
        // Business logic for creating products
        return Product::create($data);
    }

    public function calculatePrice(Product $product)
    {
        // Complex pricing logic
        return $product->base_price * $this->getTaxRate();
    }
}
```

### Settings/

Module setting classes:

```php
<?php

namespace Modules\FooBar\Settings;

use App\Classes\SettingForm;
use App\Services\SettingsPage;

class FooBarSettings extends SettingsPage
{
    const IDENTIFIER = 'foobar_settings';

    public function getForm()
    {
        return SettingForm::form(
            title: __('FooBar Settings'),
            description: __('Configure FooBar module settings'),
            tabs: [
                SettingForm::tab(
                    identifier: 'general',
                    label: __('General'),
                    fields: [
                        // Setting fields
                    ]
                )
            ]
        );
    }
}
```

### Tests/

PHPUnit test files:

```php
<?php

namespace Modules\FooBar\Tests\Feature;

use Tests\TestCase;
use Modules\FooBar\Models\Product;

class ProductTest extends TestCase
{
    public function test_can_create_product()
    {
        $data = [
            'name' => 'Test Product',
            'price' => 99.99
        ];

        $response = $this->post('/api/foobar/products', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('foobar_products', $data);
    }
}
```

### Traits/

Reusable trait classes:

```php
<?php

namespace Modules\FooBar\Traits;

trait HasPrice
{
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    public function scopeInPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }
}
```

## Module Development Best Practices

### 1. Naming Conventions

- **Module Namespace**: PascalCase, no numbers at start, no special characters
- **File Names**: Follow Laravel conventions (PascalCase for classes)
- **Database Tables**: Use module prefix (e.g., `foobar_products`)
- **Routes**: Use module prefix (e.g., `foobar.products.index`)

### 2. Module Changelog Documentation

**IMPORTANT:** When making significant changes to a module that require documentation, create a changelog file in the module's root directory.

#### Module Changelog Directory Structure

```
modules/YourModule/
├── CHANGELOG/
│   ├── YYYY-MM-DD-descriptive-heading-in-kebab-case.md
│   ├── YYYY-MM-DD-another-change.md
│   └── README.md
```

#### Changelog File Naming Convention

Module changelog files must follow this naming format:

```
YYYY-MM-DD-descriptive-heading-in-kebab-case.md
```

**Examples:**
- `2025-11-27-add-new-feature.md`
- `2025-11-27-update-api-endpoints.md`
- `2025-11-27-fix-critical-bug.md`
- `2025-12-01-improve-performance.md`

#### Module Changelog Content Structure

```markdown
# Change Title

**Date:** YYYY-MM-DD
**Type:** Feature | Bug Fix | Enhancement | Breaking Change
**Module:** ModuleName
**Version:** X.Y.Z

## Summary

Brief description of what changed and why.

## Changes Made

- List of specific changes
- Files modified or added
- New features or functionality

## Migration Required

If applicable, document any migration steps needed.

## Breaking Changes

If applicable, list any breaking changes and how to address them.

## Dependencies

List any new dependencies or version requirements.

## Related Issues

Link to any related GitHub issues or tickets.
```

#### When to Create a Module Changelog

Create a changelog for:
- New module features
- Breaking changes to module APIs
- Significant bug fixes
- Database schema changes in module
- Configuration changes
- Deprecated features
- Security updates
- Version updates

#### Example Module Changelog

```markdown
# Add Product Import Feature

**Date:** 2025-11-27
**Type:** Feature
**Module:** FooBar
**Version:** 1.2.0

## Summary

Added bulk product import functionality allowing users to import products from CSV files with validation and error reporting.

## Changes Made

- Created ImportController for handling CSV uploads
- Added validation service for product data
- Implemented batch processing for large imports
- Added import history tracking
- Created new API endpoints for import operations

## Migration Required

Run the following migration:
```bash
php artisan module:migrate FooBar
```

## Breaking Changes

None.

## Dependencies

- Added league/csv: ^9.8
- Requires PHP 8.1+

## Related Issues

- Closes #123
- Related to #456
```

**Note:** Module changelogs are separate from core NexoPOS changelogs (which go in `/changelogs/` at the project root). Only document changes specific to your module in the module's CHANGELOG directory.

### 3. Namespace Organization

All module classes should follow the namespace pattern:
```
Modules\{ModuleNamespace}\{SubNamespace}\{ClassName}
```

Examples:
- `Modules\FooBar\Http\Controllers\ProductController`
- `Modules\FooBar\Models\Product`
- `Modules\FooBar\Services\ProductService`

### 3. Integration with NexoPOS

#### Permissions

Create module-specific permissions:

```php
// In module migration or provider
use App\Models\Permission;

$permission = Permission::firstOrNew(['namespace' => 'create.foobar.products']);
$permission->name = 'Create FooBar Products';
$permission->namespace = 'create.foobar.products';
$permission->description = 'Allow creating FooBar products';
$permission->save();
```

#### Hooks and Events

Utilize NexoPOS hooks for integration:

```php
// In module service provider
Hook::addFilter('ns.dashboard.menus', function($menus) {
    $menus[] = [
        'label' => __('FooBar'),
        'href' => route('foobar.dashboard'),
        'icon' => 'la-box'
    ];
    return $menus;
});
```

#### CRUD Integration

Extend NexoPOS CRUD system:

```php
use App\Services\CrudService;
use App\Classes\CrudForm;
use App\Classes\FormInput;

class ProductCrud extends CrudService
{
    // Implement getTable(), getForm(), getActions() methods
    // Follow NexoPOS CRUD patterns
}
```

### 4. Frontend Integration

#### Vue Components

Create reusable Vue components:

```javascript
// Resources/ts/components/ProductManager.vue
export default {
    name: 'ProductManager',
    data() {
        return {
            products: []
        }
    },
    mounted() {
        this.loadProducts();
    }
}
```

#### Tailwind CSS Classes

Use existing NexoPOS/Tailwind classes for consistency:

```blade
<div class="bg-white rounded-lg shadow-md p-4">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Product Details</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Content -->
    </div>
</div>
```

### 5. Module Installation and Activation

Modules are automatically discovered by NexoPOS when placed in the `/modules` directory. The system reads the `config.xml` file to register the module.

#### Activation Process

1. Upload module to `/modules/{ModuleName}/`
2. Ensure `config.xml` is properly configured
3. NexoPOS automatically discovers and loads the module
4. Run migrations if needed
5. Configure permissions and settings

#### Module Commands

Use Artisan commands for module management:

```bash
# Install module dependencies
php artisan module:install FooBar

# Run module migrations
php artisan module:migrate FooBar

# Publish module assets
php artisan module:publish FooBar
```

## Troubleshooting

### Common Issues

1. **Module not loading**: Check `config.xml` syntax and namespace
2. **Routes not working**: Verify route file loading in service provider
3. **Views not found**: Ensure view paths are registered correctly
4. **Permissions not working**: Check permission creation and assignment
5. **Assets not loading**: Verify Vite build and `@moduleViteAssets` usage

### Asset Loading Issues

**Problem: Module assets (JS/CSS) not loading**

**Solutions:**

1. **Verify build output exists:**
   ```bash
   ls -la modules/YourModule/Public/build/
   ```
   Should contain `.vite/manifest.json` and `assets/` directory

2. **Check manifest file:**
   ```bash
   cat modules/YourModule/Public/build/.vite/manifest.json
   ```
   Should contain entries for your compiled assets

3. **Ensure using correct directive in Blade:**
   ```blade
   @moduleViteAssets('Resources/ts/main.ts', 'YourModule')
   ```
   NOT `@vite()` or incorrect paths

4. **Verify module namespace matches config.xml:**
   ```xml
   <namespace>YourModule</namespace>
   ```

5. **Clear all caches:**
   ```bash
   php artisan cache:clear
   php artisan view:clear
   php artisan config:clear
   ```

6. **Rebuild assets:**
   ```bash
   cd modules/YourModule
   npm install
   npm run build
   ```

7. **Check browser console** for 404 errors or JavaScript errors

8. **Verify vite.config.js** has correct paths:
   ```javascript
   laravel({
       hotFile: 'Public/hot',
       input: ['Resources/ts/main.ts', 'Resources/css/style.css'],
       refresh: ['Resources/**']
   })
   ```

**Problem: Hot reload not working in development**

**Solutions:**

1. **Run dev server:**
   ```bash
   cd modules/YourModule
   npm run dev
   ```

2. **Check `Public/hot` file exists** while dev server is running

3. **Verify Vite is accessible** at `http://localhost:5173`

**Problem: TypeScript compilation errors**

**Solutions:**

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Check tsconfig.json** has correct paths configuration

3. **Create types.d.ts** for NexoPOS API declarations

4. **Restart IDE/editor** to reload TypeScript language server

### Debugging Tips

1. Check Laravel logs for module loading errors: `storage/logs/laravel.log`
2. Verify namespace consistency across all files
3. Ensure proper service provider registration
4. Use browser DevTools Network tab to check asset requests
5. Check Vite build output for compilation warnings
6. Test with minimal module first, then add complexity
7. Use Laravel's debugging tools (`dd()`, `dump()`, `Log::info()`)
8. Verify module directory permissions (755 for directories, 644 for files)

### Development Workflow Best Practices

1. **Start with structure:** Create module skeleton first
2. **Build incrementally:** Add features one at a time
3. **Test early:** Run `npm run build` after adding new assets
4. **Clear caches often:** After any configuration changes
5. **Use version control:** Commit working states frequently
6. **Document changes:** Keep README.md updated with features
7. **Follow conventions:** Match NexoPOS coding standards
8. **Check examples:** Reference existing modules like `NsPageBuilder`, `CloudDeployer`

This comprehensive guide provides the foundation for creating robust, well-structured modules that integrate seamlessly with the NexoPOS ecosystem.
