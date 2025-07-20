---
applyTo: '**'
---

# NexoPOS Permission System Guide

The NexoPOS permission system provides fine-grained access control for different features and functionalities within the application. This document explains how to work with permissions, create new ones, and assign them to roles.

## Understanding Permissions

### Permission Structure

Permissions in NexoPOS follow a namespace pattern: `{action}.{resource}`

- **Action**: The operation being performed (create, read, update, delete)
- **Resource**: The entity or feature being accessed (users, products, orders, etc.)

Examples:
- `create.products` - Permission to create products
- `read.orders` - Permission to view orders
- `update.customers` - Permission to modify customers
- `delete.taxes` - Permission to delete taxes

### Permission Model

Permissions are stored in the `nexopos_permissions` table and represented by the `App\Models\Permission` model with these key properties:

- `name`: Human-readable name
- `namespace`: Unique identifier (e.g., "create.products")
- `description`: Detailed description of what the permission allows

## Creating Permissions

### 1. Permission Files

Permissions are defined in PHP files located in `database/permissions/`. Each file typically handles permissions for a specific resource.

**Example: `database/permissions/products.php`**

```php
<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    foreach ( [ 'create', 'read', 'update', 'delete' ] as $crud ) {
        $permission = Permission::firstOrNew( [ 'namespace' => $crud . '.products' ] );
        $permission->name = ucwords( $crud ) . ' Products';
        $permission->namespace = $crud . '.products';
        $permission->description = sprintf( __( 'Can %s products' ), $crud );
        $permission->save();
    }
}
```

### 2. Including Permission Files in Migrations

Permission files must be included in migration files to be executed:

**In core migration (`database/migrations/core/2020_06_20_000000_create_permissions.php`):**

```php
include_once dirname( __FILE__ ) . '/../../permissions/products.php';
```

**In update migrations:**

```php
if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    define( 'NEXO_CREATE_PERMISSIONS', true );
}

include_once dirname( __FILE__ ) . '/../../permissions/new-feature.php';
```

### 3. Custom Permission Creation

For specific permissions that don't follow the CRUD pattern:

```php
<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.pos.edit-purchase-price' ] );
    $permission->name = __( 'Edit Purchase Price' );
    $permission->namespace = 'nexopos.pos.edit-purchase-price';
    $permission->description = __( 'Let the user edit the purchase price of products.' );
    $permission->save();
}
```

## Assigning Permissions to Roles

### 1. Role Files

Roles and their permission assignments are defined in files like `database/permissions/admin-role.php`:

```php
<?php

use App\Models\Permission;
use App\Models\Role;

$admin = Role::firstOrNew( [ 'namespace' => 'admin' ] );
$admin->name = __( 'Administrator' );
$admin->namespace = 'admin';
$admin->locked = true;
$admin->description = __( 'Master role which can perform all actions.' );
$admin->save();

// Assign specific permissions
$admin->addPermissions( [
    'create.users',
    'read.users',
    'update.users',
    'delete.users',
] );

// Assign all permissions matching a pattern
$admin->addPermissions( 
    Permission::includes( '.products' )->get()->map( 
        fn( $permission ) => $permission->namespace 
    ) 
);
```

### 2. Permission Assignment Methods

**Direct assignment:**
```php
$role->addPermissions( ['create.products', 'read.products'] );
```

**Pattern-based assignment:**
```php
// All permissions containing ".products"
$role->addPermissions( 
    Permission::includes( '.products' )->get()->map( 
        fn( $permission ) => $permission->namespace 
    ) 
);
```

## Working with Permissions in Code

### 1. Checking Permissions in Controllers

```php
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        if ( ! Auth::user()->allowedTo( 'read.products' ) ) {
            throw new UnauthorizedException( __( 'You do not have permission to view products.' ) );
        }
        
        // Controller logic here
    }
}
```

### 2. Middleware Protection

```php
Route::get('/products', [ProductController::class, 'index'])->middleware('ns.permission:read.products');
```

### 3. Blade Templates

```blade
@if( Auth::user()->allowedTo( 'create.products' ) )
    <a href="{{ route('products.create') }}">Create Product</a>
@endif
```

### 4. Vue Components

```javascript
// In Vue components, permissions are available through the global state
computed: {
    canCreateProducts() {
        return this.$store.getters['auth/permissions'].includes('create.products');
    }
}
```

## Best Practices

### 1. Permission Naming Conventions

- Use lowercase with dots as separators
- Follow the `{action}.{resource}` pattern when possible
- Use descriptive resource names
- For complex permissions, use the `nexopos.{module}.{specific-action}` pattern

### 2. Permission Organization

- Group related permissions in the same file
- Use CRUD patterns when applicable
- Create specific permission files for modules
- Include all permission files in appropriate migrations

### 3. Role Management

- Keep core roles (admin, user, etc.) locked
- Create specific roles for different user types
- Use pattern-based permission assignment for maintainability
- Document role purposes and capabilities

### 4. Migration Strategy

- Always include permission creation in migrations
- Use the `NEXO_CREATE_PERMISSIONS` constant check
- Include role updates when adding new permissions
- Test permissions after migration

## Common Permission Patterns

### 1. CRUD Resources

```php
foreach ( [ 'create', 'read', 'update', 'delete' ] as $crud ) {
    $permission = Permission::firstOrNew( [ 'namespace' => $crud . '.{resource}' ] );
    $permission->name = ucwords( $crud ) . ' {Resource}';
    $permission->namespace = $crud . '.{resource}';
    $permission->description = sprintf( __( 'Can %s {resource}' ), $crud );
    $permission->save();
}
```

### 2. Module-Specific Permissions

```php
$permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.{module}.{action}' ] );
$permission->name = __( '{Action} in {Module}' );
$permission->namespace = 'nexopos.{module}.{action}';
$permission->description = __( 'Allow specific action in module.' );
$permission->save();
```

### 3. Widget Permissions

```php
// For widgets, permissions are often generated dynamically
$widgets = $widgetService->getAllWidgets();
$widgets->each( function ( $widget ) {
    if ( $widget->instance->getPermission() ) {
        $permission = Permission::firstOrNew( [ 'namespace' => $widget->instance->getPermission() ] );
        $permission->name = sprintf( __( 'Widget: %s' ), $widget->instance->getName() );
        $permission->namespace = $widget->instance->getPermission();
        $permission->description = $widget->instance->getDescription();
        $permission->save();
    }
} );
```

## Debugging Permissions

### 1. Check if Permission Exists

```php
$permission = Permission::namespace( 'create.products' );
if ( ! $permission instanceof Permission ) {
    // Permission doesn't exist
}
```

### 2. List User Permissions

```php
$user = Auth::user();
$permissions = $user->role->permissions->pluck( 'namespace' );
dd( $permissions );
```

### 3. Check Role Permissions

```php
$role = Role::namespace( 'admin' );
$permissions = $role->permissions->pluck( 'namespace' );
dd( $permissions );
```

## Module Integration

When creating modules that require permissions:

1. **Create permission file in module:**
   ```
   modules/{ModuleName}/database/permissions/{module-name}.php
   ```

2. **Include in module's service provider:**
   ```php
   public function boot()
   {
       if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
           define( 'NEXO_CREATE_PERMISSIONS', true );
       }
       
       include_once __DIR__ . '/../database/permissions/{module-name}.php';
   }
   ```

3. **Use module-specific namespace:**
   ```php
   $permission->namespace = 'nexopos.{module-name}.{action}';
   ```

This permission system provides the flexibility needed for complex access control while maintaining consistency across the NexoPOS ecosystem.
