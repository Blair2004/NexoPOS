---
applyTo: '**'
---

# NexoPOS Permission and Role System Guide

This comprehensive guide covers the Permission and Role system in NexoPOS, including all models, methods, and best practices for implementing access control.

## Overview

NexoPOS implements a robust Role-Based Access Control (RBAC) system that provides fine-grained permission management through several interconnected models:

- **Permission**: Defines specific actions or capabilities
- **Role**: Groups of permissions assigned to users
- **User**: Entities that can be assigned roles
- **PermissionAccess**: Temporary permission access requests
- **RolePermission**: Pivot table linking roles to permissions
- **UserRoleRelation**: Pivot table linking users to roles

## Core Models

### Permission Model

**Location**: `app/Models/Permission.php`  
**Table**: `nexopos_permissions`

#### Properties
```php
/**
 * @property int $id
 * @property string $name         // Human-readable name
 * @property string $namespace    // Unique identifier (e.g., "create.products")
 * @property string $description  // Detailed description
 */
```

#### Key Methods

##### Static Methods
```php
// Find permission by namespace
Permission::namespace(string $name): ?Permission

// Find or create new permission instance
Permission::withNamespaceOrNew(string $name): Permission
```

##### Query Scopes
```php
// Filter by namespace
Permission::withNamespace(string $namespace)

// Find permissions containing a string
Permission::includes(string $search)
```

##### Instance Methods
```php
// Get associated roles
$permission->roles(): HasManyThrough

// Remove permission from all roles
$permission->removeFromRoles(): void
```

#### Usage Examples

```php
// Create a permission
$permission = new Permission();
$permission->name = 'Create Products';
$permission->namespace = 'create.products';
$permission->description = 'Allow creating new products';
$permission->save();

// Find by namespace
$permission = Permission::namespace('create.products');

// Find permissions containing "products"
$productPermissions = Permission::includes('.products')->get();

// Remove from all roles
$permission->removeFromRoles();
```

### Role Model

**Location**: `app/Models/Role.php`  
**Table**: `nexopos_roles`

#### Constants
```php
const ADMIN = 'admin';                      // Main admin role
const STOREADMIN = 'nexopos.store.administrator';  // Store manager
const STORECASHIER = 'nexopos.store.cashier';      // Store cashier
const STORECUSTOMER = 'nexopos.store.customer';    // Store customer
const USER = 'user';                        // Base user role
```

#### Properties
```php
/**
 * @property int $total_stores
 * @property string $description
 * @property bool $locked          // Prevents role deletion
 * @property Carbon $updated_at
 */
```

#### Key Methods

##### Static Methods
```php
// Find role by namespace
Role::namespace(string $name): ?Role

// Filter roles by array of namespaces
Role::in(array $namespaces): Query
```

##### Query Scopes
```php
// Filter by namespace
Role::withNamespace(string $namespace)
```

##### Permission Management
```php
// Add single permission
$role->addPermissions(string $permission, bool $silent = false): void

// Add multiple permissions (array)
$role->addPermissions(array $permissions, bool $silent = false): void

// Add permissions from collection
$role->addPermissions(Collection $permissions, bool $silent = false): void

// Add permission instance
$role->addPermissions(Permission $permission, bool $silent = false): void

// Remove permissions
$role->removePermissions(string|Collection $permissionNamespace): void
```

##### Relationships
```php
// Get role users
$role->users(): HasManyThrough

// Get role permissions
$role->permissions(): HasManyThrough
```

#### Usage Examples

```php
// Find admin role
$admin = Role::namespace(Role::ADMIN);

// Add single permission
$admin->addPermissions('create.products');

// Add multiple permissions
$admin->addPermissions([
    'create.products',
    'read.products',
    'update.products',
    'delete.products'
]);

// Add permissions using collection
$productPermissions = Permission::includes('.products')->get();
$admin->addPermissions($productPermissions->pluck('namespace'));

// Remove permission
$admin->removePermissions('delete.products');

// Get role permissions
$permissions = $admin->permissions()->get();
```

### User Model

**Location**: `app/Models/User.php`  
**Table**: `nexopos_users`

#### Key Methods

##### Role Management
```php
// Assign role by namespace
$user->assignRole(string|array $roleName): array

// Check if user has specific roles
$user->hasRoles(array $roles): bool

// Get user roles
$user->roles(): HasManyThrough
```

##### Permission Checking
```php
// Check if user has permission (deprecated, use ns()->allowedTo() instead)
$user->allowedTo(string|array $permission): bool
```

#### Usage Examples

```php
// Assign single role
$result = $user->assignRole('admin');

// Assign multiple roles
$result = $user->assignRole(['admin', 'cashier']);

// Check roles
$hasAdminRole = $user->hasRoles(['admin']);

// Check permissions (deprecated - use CoreService instead)
$canCreate = $user->allowedTo('create.products');
```

### PermissionAccess Model

**Location**: `app/Models/PermissionAccess.php`  
**Table**: `nexopos_permissions_access`

Used for temporary permission access requests in POS systems.

#### Constants
```php
const GRANTED = 'granted';   // Permission granted
const DENIED = 'denied';     // Permission denied
const PENDING = 'pending';   // Awaiting approval
const EXPIRED = 'expired';   // Request expired
const USED = 'used';         // Permission was used
```

#### Properties
```php
/**
 * @property int $requester_id        // User requesting permission
 * @property int $granter_id          // Admin granting permission
 * @property string $status           // Current status
 * @property string $permission       // Permission namespace
 * @property string|null $url         // Associated URL
 * @property Carbon|null $approved_at // When approved
 * @property Carbon|null $expired_at  // When expires
 */
```

#### Relationships
```php
// Get associated permission
$permissionAccess->perm(): BelongsTo
```

## Core Services

### CoreService

**Location**: `app/Services/CoreService.php`

The CoreService provides the primary interface for permission checking throughout the application.

#### Key Methods

```php
// Check if current user has permission(s)
ns()->allowedTo(string|array $permissions): bool

// Check if current user has specific role
ns()->hasRole(string $roleNamespace): bool

// Get current user details
ns()->getUserDetails(): Collection
```

#### Usage Examples

```php
// Check single permission
if (ns()->allowedTo('create.products')) {
    // User can create products
}

// Check multiple permissions (any)
if (ns()->allowedTo(['create.products', 'update.products'])) {
    // User has at least one of these permissions
}

// Check role
if (ns()->hasRole(Role::ADMIN)) {
    // User is admin
}
```

## Middleware

### NsRestrictMiddleware

**Location**: `app/Http/Middleware/NsRestrictMiddleware.php`  
**Alias**: `ns.permission`

Restricts access based on permissions.

#### Usage
```php
// In routes
Route::get('/products', [ProductController::class, 'index'])
    ->middleware('ns.permission:read.products');

// In controllers
public function __construct()
{
    $this->middleware('ns.permission:create.products')->only('create', 'store');
}
```

### AdminApprovalMiddleware

**Location**: `app/Http/Middleware/AdminApprovalMiddleware.php`

Handles temporary permission approval system for POS actions.

#### Usage
```php
Route::post('/pos/action', [PosController::class, 'action'])
    ->middleware('admin.approval:nexopos.cart.product-price');
```

## Permission Management Patterns

### 1. CRUD Pattern

Most resources follow the CRUD permission pattern:

```php
$resources = ['products', 'customers', 'orders', 'taxes'];
$actions = ['create', 'read', 'update', 'delete'];

foreach ($resources as $resource) {
    foreach ($actions as $action) {
        $permission = Permission::firstOrNew(['namespace' => "$action.$resource"]);
        $permission->name = ucwords("$action $resource");
        $permission->namespace = "$action.$resource";
        $permission->description = "Can $action $resource";
        $permission->save();
    }
}
```

### 2. Module-Specific Permissions

For complex features, use namespaced permissions:

```php
$permission = Permission::firstOrNew(['namespace' => 'nexopos.pos.edit-purchase-price']);
$permission->name = 'Edit Purchase Price';
$permission->namespace = 'nexopos.pos.edit-purchase-price';
$permission->description = 'Allow editing purchase price in POS';
$permission->save();
```

### 3. Role Assignment Patterns

#### Assign All CRUD Permissions
```php
$role = Role::namespace('admin');
$productPermissions = Permission::includes('.products')->get();
$role->addPermissions($productPermissions->pluck('namespace'));
```

#### Assign Specific Module Permissions
```php
$role = Role::namespace('cashier');
$posPermissions = Permission::includes('nexopos.pos.')->get();
$role->addPermissions($posPermissions->pluck('namespace'));
```

## Best Practices

### 1. Permission Naming

**Standard CRUD Pattern:**
```
create.{resource}
read.{resource}
update.{resource}
delete.{resource}
```

**Module-Specific Pattern:**
```
nexopos.{module}.{action}
{module}.{feature}.{action}
```

**Examples:**
```
create.products
nexopos.pos.edit-purchase-price
multistore.settings.manage
```

### 2. Role Management

```php
// Create role with permissions
$role = new Role();
$role->namespace = 'store.manager';
$role->name = 'Store Manager';
$role->description = 'Manages store operations';
$role->save();

// Assign comprehensive permissions
$role->addPermissions([
    'create.products',
    'read.products', 
    'update.products',
    'read.orders',
    'update.orders'
]);
```

### 3. Permission Checking

**In Controllers:**
```php
class ProductController extends Controller
{
    public function index()
    {
        if (!ns()->allowedTo('read.products')) {
            throw new NotEnoughPermissionException('Cannot view products');
        }
        // Controller logic
    }
}
```

**In Blade Templates:**
```blade
@if(ns()->allowedTo('create.products'))
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        Create Product
    </a>
@endif
```

**In Vue Components:**
```javascript
computed: {
    canCreateProducts() {
        return this.$store.getters['auth/permissions'].includes('create.products');
    }
}
```

### 4. Migrations

**Creating Permissions in Migrations:**
```php
public function up()
{
    if (!defined('NEXO_CREATE_PERMISSIONS')) {
        define('NEXO_CREATE_PERMISSIONS', true);
    }

    // Create permissions
    foreach (['create', 'read', 'update', 'delete'] as $action) {
        $permission = Permission::firstOrNew(['namespace' => "$action.products"]);
        $permission->name = ucwords("$action Products");
        $permission->namespace = "$action.products";
        $permission->description = "Can $action products";
        $permission->save();
    }

    // Assign to admin role
    $admin = Role::namespace(Role::ADMIN);
    if ($admin) {
        $admin->addPermissions([
            'create.products',
            'read.products',
            'update.products',
            'delete.products'
        ]);
    }
}
```

## Error Handling

### NotEnoughPermissionException

**Location**: `app/Exceptions/NotEnoughPermissionException.php`

Thrown when a user lacks required permissions.

```php
use App\Exceptions\NotEnoughPermissionException;

if (!ns()->allowedTo('create.products')) {
    throw new NotEnoughPermissionException('You cannot create products');
}
```

## Testing Permissions

### Feature Testing

```php
public function test_user_can_create_products_with_permission()
{
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create(['namespace' => 'create.products']);
    
    $role->addPermissions('create.products');
    $user->assignRole($role->namespace);
    
    $this->actingAs($user)
        ->post('/api/products', $productData)
        ->assertStatus(201);
}

public function test_user_cannot_create_products_without_permission()
{
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->post('/api/products', $productData)
        ->assertStatus(403);
}
```

## Advanced Usage

### Dynamic Permission Creation

```php
// For widgets
$widgets = $widgetService->getAllWidgets();
$widgets->each(function ($widget) {
    if ($widget->instance->getPermission()) {
        $permission = Permission::firstOrNew([
            'namespace' => $widget->instance->getPermission()
        ]);
        $permission->name = "Widget: {$widget->instance->getName()}";
        $permission->namespace = $widget->instance->getPermission();
        $permission->description = $widget->instance->getDescription();
        $permission->save();
    }
});
```

### Temporary Permission Access

```php
// Create temporary access request
$access = new PermissionAccess();
$access->requester_id = auth()->id();
$access->permission = 'nexopos.cart.product-price';
$access->status = PermissionAccess::PENDING;
$access->expired_at = now()->addMinutes(5);
$access->save();

// Grant access
$access->status = PermissionAccess::GRANTED;
$access->granter_id = $adminUser->id;
$access->save();
```

### Bulk Role Management

```php
// Assign multiple users to role
$users = User::whereIn('id', $userIds)->get();
$role = Role::namespace('cashier');

$users->each(function ($user) use ($role) {
    $user->assignRole($role->namespace);
});

// Remove role from users
$users->each(function ($user) {
    UserRoleRelation::where('user_id', $user->id)
        ->where('role_id', $role->id)
        ->delete();
});
```

This comprehensive guide covers all aspects of the NexoPOS Permission and Role system, providing developers with the knowledge needed to implement robust access control in their applications.
