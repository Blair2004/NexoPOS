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
        // Boot services
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'FooBar');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
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

#### SCSS (scss/)

Stylesheet files:

```scss
// Resources/scss/module.scss
.foobar-product {
    &__card {
        @apply bg-white rounded-lg shadow-md p-4;
    }
    
    &__title {
        @apply text-lg font-semibold text-gray-800;
    }
}
```

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

### 2. Namespace Organization

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
5. **Assets not loading**: Verify public folder symlink creation

### Debugging Tips

1. Check Laravel logs for module loading errors
2. Verify namespace consistency across all files
3. Ensure proper service provider registration
4. Test with minimal module first, then add complexity
5. Use Laravel's debugging tools (dd(), dump(), etc.)

This comprehensive guide provides the foundation for creating robust, well-structured modules that integrate seamlessly with the NexoPOS ecosystem.
