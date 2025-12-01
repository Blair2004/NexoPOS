---
applyTo: '**'
---

# NexoPOS Middleware Guide

This document describes the custom middleware used in NexoPOS for controlling access and managing requests, with a focus on permission-based route protection.

## NsRestrictMiddleware

**Location**: `app/Http/Middleware/NsRestrictMiddleware.php`

The `NsRestrictMiddleware` is the primary middleware for restricting access to routes based on user permissions. It checks if the authenticated user has the required permission(s) to access a route.

### Implementation

```php
<?php

namespace App\Http\Middleware;

use App\Exceptions\NotEnoughPermissionException;
use App\Traits\NsMiddlewareArgument;
use Closure;
use Illuminate\Http\Request;

class NsRestrictMiddleware
{
    use NsMiddlewareArgument;

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle( Request $request, Closure $next, $permission )
    {
        if ( ns()->allowedTo( $permission ) ) {
            return $next( $request );
        }

        $message = sprintf(
            __( 'Your don\'t have enough permission ("%s") to perform this action.' ),
            $permission
        );

        throw new NotEnoughPermissionException( $message );
    }
}
```

## NsMiddlewareArgument Trait

**Location**: `app/Traits/NsMiddlewareArgument.php`

The `NsMiddlewareArgument` trait provides a helper method to format middleware arguments properly for Laravel's middleware system. This trait is used by `NsRestrictMiddleware` and can be used by other custom middleware.

### Implementation

```php
<?php

namespace App\Traits;

trait NsMiddlewareArgument
{
    public static function arguments( string|array $arguments )
    {
        if ( is_array( $arguments ) ) {
            return collect( $arguments )->map( fn( $argument ) => self::class . ':' . $argument )->toArray();
        } else {
            return self::class . ':' . $arguments;
        }
    }
}
```

### How It Works

The `arguments()` method:
- Accepts either a **string** or an **array** of permission namespaces
- Returns them formatted as `MiddlewareClass:permission` for Laravel
- For arrays, it maps each permission to the correct format
- Enables clean, readable middleware usage in route definitions

## Usage Patterns

### 1. Single Permission Protection

Protect a route with a single permission requirement:

```php
use App\Http\Middleware\NsRestrictMiddleware;

Route::get('/products', [ProductsController::class, 'index'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.read.products'));

Route::post('/customers', [CustomersController::class, 'post'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.create.customers'));

Route::delete('/orders/{id}', [OrdersController::class, 'delete'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.delete.orders'));
```

### 2. Multiple Permissions (Any)

Require that the user has **at least one** of multiple permissions:

```php
Route::get('/customers-groups/{id}/customers', [CustomersGroupsController::class, 'getCustomers'])
    ->middleware(NsRestrictMiddleware::arguments([
        'nexopos.read.customers-groups',
        'nexopos.read.customers',
    ]));

Route::post('/customers-groups/transfer-customers', [CustomersGroupsController::class, 'transferOwnership'])
    ->middleware(NsRestrictMiddleware::arguments([
        'nexopos.update.customers-groups',
        'nexopos.update.customers',
    ]));
```

### 3. Route Groups

Apply middleware to route groups to protect multiple routes with the same permission:

```php
Route::middleware(NsRestrictMiddleware::arguments('nexopos.read.customers'))->group(function () {
    Route::get('customers/{customer?}', [CustomersController::class, 'get']);
    Route::get('customers/recently-active', [CustomersController::class, 'getRecentlyActive']);
    Route::get('customers/{customer}/orders', [CustomersController::class, 'getOrders']);
    Route::get('customers/{customer}/addresses', [CustomersController::class, 'getAddresses']);
    Route::get('customers/{customer}/group', [CustomersController::class, 'getGroup']);
    Route::get('customers/{customer}/coupons', [CustomersController::class, 'getCustomerCoupons']);
});
```

### 4. Mixed Route Protection

Combine different permissions for different HTTP methods or routes:

```php
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

// Delete routes
Route::delete('customers/{id}', [CustomersController::class, 'delete'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.delete.customers'));

// Read routes grouped together
Route::middleware(NsRestrictMiddleware::arguments('nexopos.read.customers'))->group(function () {
    Route::get('customers/{customer?}', [CustomersController::class, 'get']);
    Route::get('customers/recently-active', [CustomersController::class, 'getRecentlyActive']);
});

// Create routes
Route::post('customers', [CustomersController::class, 'post'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.create.customers'));

// Update routes
Route::put('customers/{customer}', [CustomersController::class, 'put'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.update.customers'));
```

## Real-World Examples

### Example 1: Products API Routes

```php
use App\Http\Controllers\Dashboard\ProductsController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('products', [ProductsController::class, 'saveProduct'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.create.products'));

Route::post('products/search', [ProductsController::class, 'searchProduct'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.read.products'));

Route::post('products/adjustments', [ProductsController::class, 'createAdjustment'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.update.products'));

Route::put('products/{product}', [ProductsController::class, 'updateProduct'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.update.products'));

Route::delete('products/{product}', [ProductsController::class, 'deleteProduct'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.delete.products'));
```

### Example 2: Orders API with Complex Permissions

```php
use App\Http\Controllers\Dashboard\OrdersController;
use App\Http\Middleware\NsRestrictMiddleware;

Route::post('orders/{order}/void', [OrdersController::class, 'voidOrder'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.void.orders'));

Route::post('orders/{order}/processing', [OrdersController::class, 'changeOrderProcessingStatus'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.update.orders'));

Route::post('orders/{order}/payments', [OrdersController::class, 'addPayment'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.make-payment.orders'));

Route::post('orders/{order}/refund', [OrdersController::class, 'refundOrder'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.refund.orders'));
```

### Example 3: Media Management

```php
use App\Http\Controllers\Dashboard\MediasController;
use App\Http\Middleware\NsRestrictMiddleware;

Route::get('medias', [MediasController::class, 'getMedias'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.see.medias'));

Route::post('medias', [MediasController::class, 'uploadMedias'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.upload.medias'));

Route::put('medias/{media}', [MediasController::class, 'updateMedia'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.update.medias'));

Route::delete('medias/{id}', [MediasController::class, 'deleteMedia'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.see.medias'));

Route::post('medias/bulk-delete/', [MediasController::class, 'bulkDeleteMedias'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.delete.medias'));
```

## Module Usage

Modules can use the `NsRestrictMiddleware` in their own route files:

```php
<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Modules\MyModule\Http\Controllers\MyController;
use Illuminate\Support\Facades\Route;

Route::prefix('mymodule')->group(function () {
    Route::get('dashboard', [MyController::class, 'index'])
        ->middleware(NsRestrictMiddleware::arguments('mymodule.read.dashboard'));
    
    Route::post('settings', [MyController::class, 'updateSettings'])
        ->middleware(NsRestrictMiddleware::arguments('mymodule.update.settings'));
    
    Route::middleware(NsRestrictMiddleware::arguments('mymodule.manage.data'))->group(function () {
        Route::get('data', [MyController::class, 'getData']);
        Route::post('data', [MyController::class, 'saveData']);
        Route::delete('data/{id}', [MyController::class, 'deleteData']);
    });
});
```

## Permission Checking Flow

When a request hits a route protected by `NsRestrictMiddleware`:

1. **Middleware is invoked** with the permission namespace(s)
2. **Permission check** is performed using `ns()->allowedTo($permission)`
3. **Authorization decision**:
   - If the user has permission → Request proceeds to controller
   - If the user lacks permission → `NotEnoughPermissionException` is thrown
4. **Exception handling** returns appropriate error response to the client

## Error Handling

### NotEnoughPermissionException

When a user doesn't have the required permission, the middleware throws:

```php
throw new NotEnoughPermissionException(
    'Your don\'t have enough permission ("permission.name") to perform this action.'
);
```

This exception should be caught by your application's exception handler and converted to an appropriate HTTP response (typically 403 Forbidden).

### Custom Error Messages

For custom error handling, you can catch this exception in your exception handler:

```php
// In app/Exceptions/Handler.php
use App\Exceptions\NotEnoughPermissionException;

public function render($request, Throwable $exception)
{
    if ($exception instanceof NotEnoughPermissionException) {
        return response()->json([
            'status' => 'error',
            'message' => $exception->getMessage()
        ], 403);
    }

    return parent::render($request, $exception);
}
```

## Best Practices

### 1. Always Use the `arguments()` Method

**✅ Correct:**
```php
->middleware(NsRestrictMiddleware::arguments('permission.name'))
->middleware(NsRestrictMiddleware::arguments(['perm.one', 'perm.two']))
```

**❌ Incorrect:**
```php
->middleware('NsRestrictMiddleware:permission.name')
->middleware(NsRestrictMiddleware::class . ':permission.name')
```

### 2. Import the Middleware

Always import the middleware at the top of your route files:

```php
use App\Http\Middleware\NsRestrictMiddleware;
```

### 3. Use Descriptive Permission Namespaces

Follow consistent naming patterns:

**Standard CRUD Pattern:**
```php
'nexopos.create.{resource}'  // Creating resources
'nexopos.read.{resource}'    // Reading/viewing resources
'nexopos.update.{resource}'  // Updating resources
'nexopos.delete.{resource}'  // Deleting resources
```

**Action-Specific Pattern:**
```php
'nexopos.void.orders'              // Voiding orders
'nexopos.make-payment.orders'      // Making payments
'nexopos.refund.orders'            // Refunding orders
'nexopos.see.medias'               // Viewing media
'nexopos.upload.medias'            // Uploading media
```

**Module-Specific Pattern:**
```php
'module-name.action.resource'
'mymodule.manage.settings'
'inventory.adjust.stock'
```

### 4. Group Related Routes

When multiple routes share the same permission, use route groups:

```php
// ✅ Good - DRY principle
Route::middleware(NsRestrictMiddleware::arguments('nexopos.read.customers'))->group(function () {
    Route::get('customers/{customer}', [CustomersController::class, 'get']);
    Route::get('customers/recently-active', [CustomersController::class, 'getRecentlyActive']);
    Route::get('customers/{customer}/orders', [CustomersController::class, 'getOrders']);
});

// ❌ Bad - Repetitive
Route::get('customers/{customer}', [CustomersController::class, 'get'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.read.customers'));
Route::get('customers/recently-active', [CustomersController::class, 'getRecentlyActive'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.read.customers'));
Route::get('customers/{customer}/orders', [CustomersController::class, 'getOrders'])
    ->middleware(NsRestrictMiddleware::arguments('nexopos.read.customers'));
```

### 5. Ensure Permissions Exist

Before using permissions in middleware, ensure they are created in the database:

```php
// In migration or permission file
$permission = Permission::firstOrNew(['namespace' => 'nexopos.create.products']);
$permission->name = 'Create Products';
$permission->namespace = 'nexopos.create.products';
$permission->description = 'Allow creating new products';
$permission->save();
```

### 6. Test Permission Protection

Always test that routes are properly protected:

```php
// In tests
public function test_user_cannot_access_without_permission()
{
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->get('/api/products')
        ->assertStatus(403);
}

public function test_user_can_access_with_permission()
{
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $role->addPermissions('nexopos.read.products');
    $user->assignRole($role->namespace);
    
    $this->actingAs($user)
        ->get('/api/products')
        ->assertStatus(200);
}
```

## Creating Custom Middleware with NsMiddlewareArgument

You can create your own middleware that uses the `NsMiddlewareArgument` trait:

```php
<?php

namespace App\Http\Middleware;

use App\Traits\NsMiddlewareArgument;
use Closure;
use Illuminate\Http\Request;

class CustomAccessMiddleware
{
    use NsMiddlewareArgument;

    public function handle(Request $request, Closure $next, $feature)
    {
        if ($this->hasAccessToFeature($feature)) {
            return $next($request);
        }

        abort(403, "Access denied to feature: {$feature}");
    }

    private function hasAccessToFeature($feature)
    {
        // Custom logic to check feature access
        return true;
    }
}
```

**Usage:**

```php
use App\Http\Middleware\CustomAccessMiddleware;

Route::get('/feature', [FeatureController::class, 'index'])
    ->middleware(CustomAccessMiddleware::arguments('advanced-reporting'));
```

## Troubleshooting

### Issue: Middleware Not Working

**Symptoms:** Routes are accessible without proper permissions

**Solutions:**
1. Check if middleware is properly applied to the route
2. Verify the permission exists in the database
3. Ensure the user's role has the required permission
4. Clear route cache: `php artisan route:clear`
5. Check middleware is imported: `use App\Http\Middleware\NsRestrictMiddleware;`

### Issue: Permission Always Denied

**Symptoms:** Even admin users cannot access routes

**Solutions:**
1. Verify permission namespace spelling matches exactly
2. Check if admin role has the permission assigned
3. Verify `ns()->allowedTo()` is working correctly
4. Check if user is properly authenticated

### Issue: Array of Permissions Not Working

**Symptoms:** Multiple permissions are not checked correctly

**Solutions:**
1. Ensure you're passing an array: `['perm.one', 'perm.two']`
2. Remember: Arrays work as "OR" - user needs at least ONE permission
3. For "AND" logic, chain middleware calls or use custom logic

## Related Documentation

For more information about permissions and roles:
- `.github/instructions/nexopos-permissions.instructions.md` - Permission system details
- `.github/instructions/nexopos-roles-permissions.instructions.md` - Role and permission management

For route organization:
- `.github/instructions/nexopos-modules.instructions.md` - Module route structure
