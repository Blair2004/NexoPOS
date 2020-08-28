# Authentication
This section describe how the users, roles, permissions and guards works.

## About Users
The authentication on NexoPOS uses closely the default [Laravel authentication](https://laravel.com/docs/7.x/authentication). 
However, it provides new attributes such as :

- active : boolean that determine wether a user is active or not.
- role_id : relation column that link a user to a role.

## How to create roles
The Role works as a group. This helps to give specific capacity to users. A role can be : Administrator, User, etc.
In order to create a new role, you can use the `App\Models\Role` class like so : 

**Example :**
```php
use App\Models\Role;

//...
    $role   = new Role;
    $role->namespace    = 'custom.role';
    $role->name         = 'My Custom Role';
    $role->locked       = false; // determine wether it can be edited
    $role->description  = '...';
    $role->save();
```

## How to create Permissions

A permission is an express grant to do something. That could be "delete orders", "create orders", etc. For the developpers, working with permissions is a 
better way to make their app more secure. A permission is created using the class `App\Models\Permission`.

**Example :**
```php
use App\Models\Permission;
  // ...
  $permission   = new Permission;
  $permission->namespace  = 'can.fly'; // should be a name with no spaces, no special characters. That's just a convention.
  $permission->name     = 'Fly';
  $permission->description = 'Helps the users to fly.';
  $permission->save();
```

## How to add permissions to roles

Once a permission has been added, it needs to be linked to a Role. But as many role can share same permission, we'll use an intermediate model to achieve that.
That intermediate model is `App\Models\RolePermission`. This class should be used like so : 

**Example :**
```php
use App\Models\RolePermission;
  // ...
  $relation   = new RolePermission;
  $relation->role_id  = $role->id; // assuming $role is a Role instance
  $relation->permission_id  = $permission->id; // assuming $permission is a Permission instance
  $relation->save();
```

You can also use the built-in method on Role that allow you to add many permissions by only using their "namespace".

**Example :**
```php
$role   = Role::namespace( 'my.custom.role' );
$role->addPermissions([ 'fly', 'run' ]);
```

Similarily, you can remove permission using `removePermissions` on the Role instance.

**Example :**
```php
$role   = Role::namespace( 'my.custom.role' );
$role->removePermissions([ 'fly', 'run' ]);
```

## How to secure pages and sections

Guards are security measure added to NexoPOS to restrict some action to the right users (or roles). 
By default, NexoPOS is built with various roles that has various permissions. It's also possible to create customs roles and permissions. 

Once you have your roles, permissions created and your permissions and roles linked, it's time to protect your pages. 
This can be made on any of your controller methods or on middleware. We'll here just need to use the helper `ns()`.

**Example :**
```php
ns()->restrict([ 'manage.options' ]);
```

The method "restrict" takes an array of permission's namespace as values. by default, if more than one permission is passed, that means the user with a specific role
will have to have all the permissions provided. In order to grant the access for one of the permissions provided, a second parameter is required. That second parameter
can either be "all" or "any". By default, the second parameter is set to "all".

**Example 1 :**
```php
ns()->restrict([ 'nexopos.delete.orders', 'nexopos.create.orders' ]); // the role must have both permissions
```

**Example 2 :**
```php
ns()->restrict([ 'nexopos.delete.orders', 'nexopos.create.orders' ], 'any' ); // the role must have at least one permissions
```
As mentionned above, you can also use it on a middleware to completely secure a whole controller.

**Example : as a middleware**
```php
    // ...
    public function __construct()
    {
        $this->middleware( function( $request, $next ) {
            ns()->restrict([ 'manage.modules' ]);
            return $next( $request );
        });
    }
```
