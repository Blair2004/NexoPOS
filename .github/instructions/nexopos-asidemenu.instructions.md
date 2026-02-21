# NexoPOS AsideMenu Class Documentation

The `AsideMenu` class is a helper utility for building dashboard navigation menus in NexoPOS. It provides a fluent and structured way to create hierarchical menus with proper permissions, icons, and routing.

## Class Location
```
app/Classes/AsideMenu.php
```

## Overview

The AsideMenu class provides static methods to construct menu structures that are used in the NexoPOS dashboard. It supports:
- Main menu items with icons and permissions
- Submenu items (childrens)
- Permission-based visibility
- Icons using Line Awesome (LA) classes
- Counters for menu items
- Hierarchical menu structure

## Available Methods

### `AsideMenu::wrapper(...$menus)`

Combines multiple menu arrays into a single menu structure.

**Parameters:**
- `$menus` - Variable number of menu arrays to merge

**Returns:** Combined array of all menus

**Example:**
```php
$menus = AsideMenu::wrapper(
    AsideMenu::menu('Dashboard', 'dashboard'),
    AsideMenu::menu('Users', 'users'),
    AsideMenu::menu('Settings', 'settings')
);
```

### `AsideMenu::menu()`

Creates a main menu item with optional submenus.

**Method Signature:**
```php
public static function menu(
    string $label,
    string $identifier,
    string $href = '',
    array $childrens = [],
    string $icon = 'la la-star',
    array $permissions = [],
    int $counter = 0,
    bool $show = true
)
```

**Parameters:**
- `$label` (string) - Display text for the menu item
- `$identifier` (string) - Unique identifier for the menu item
- `$href` (string, optional) - URL for the menu item
- `$childrens` (array, optional) - Array of submenu items
- `$icon` (string, optional) - Line Awesome icon class (default: 'la la-star')
- `$permissions` (array, optional) - Array of required permissions
- `$counter` (int, optional) - Badge counter to display
- `$show` (bool, optional) - Whether to show the menu item

**Example:**
```php
AsideMenu::menu(
    label: __('Users'),
    identifier: 'users',
    icon: 'la-users',
    permissions: ['read.users', 'create.users'],
    childrens: AsideMenu::childrens(
        AsideMenu::subMenu(
            label: __('Users List'),
            identifier: 'users-list',
            href: ns()->url('/dashboard/users'),
            permissions: ['read.users']
        )
    )
)
```

### `AsideMenu::subMenu()`

Creates a submenu item (child menu).

**Method Signature:**
```php
public static function subMenu(
    string $label,
    string $identifier,
    string $href = '',
    string $icon = 'la la-star',
    array $permissions = [],
    bool $show = true
)
```

**Parameters:**
- `$label` (string) - Display text for the submenu item
- `$identifier` (string) - Unique identifier for the submenu item
- `$href` (string, optional) - URL for the submenu item
- `$icon` (string, optional) - Line Awesome icon class
- `$permissions` (array, optional) - Array of required permissions
- `$show` (bool, optional) - Whether to show the submenu item

**Example:**
```php
AsideMenu::subMenu(
    label: __('Create User'),
    identifier: 'create-user',
    href: ns()->url('/dashboard/users/create'),
    permissions: ['create.users']
)
```

### `AsideMenu::childrens(...$childrens)`

Combines multiple submenu items into a children array.

**Parameters:**
- `$childrens` - Variable number of submenu arrays

**Returns:** Combined array of submenu items

**Example:**
```php
AsideMenu::childrens(
    AsideMenu::subMenu('List Users', 'users-list', '/dashboard/users'),
    AsideMenu::subMenu('Create User', 'create-user', '/dashboard/users/create'),
    AsideMenu::subMenu('User Roles', 'user-roles', '/dashboard/users/roles')
)
```

## Complete Example

Here's a complete example of creating a menu structure:

```php
<?php

use App\Classes\AsideMenu;

$menus = AsideMenu::wrapper(
    // Simple menu without children
    AsideMenu::menu(
        label: __('Dashboard'),
        identifier: 'dashboard',
        icon: 'la-home',
        href: ns()->url('/dashboard'),
        permissions: ['read.dashboard']
    ),
    
    // Menu with children
    AsideMenu::menu(
        label: __('Users'),
        identifier: 'users',
        icon: 'la-users',
        permissions: ['read.users', 'create.users'],
        childrens: AsideMenu::childrens(
            AsideMenu::subMenu(
                label: __('Users List'),
                identifier: 'users-list',
                href: ns()->url('/dashboard/users'),
                permissions: ['read.users']
            ),
            AsideMenu::subMenu(
                label: __('Create User'),
                identifier: 'create-user',
                href: ns()->url('/dashboard/users/create'),
                permissions: ['create.users']
            ),
            AsideMenu::subMenu(
                label: __('User Roles'),
                identifier: 'user-roles',
                href: ns()->url('/dashboard/users/roles'),
                permissions: ['read.roles']
            )
        )
    ),
    
    // Menu with counter
    AsideMenu::menu(
        label: __('Orders'),
        identifier: 'orders',
        icon: 'la-shopping-cart',
        href: ns()->url('/dashboard/orders'),
        permissions: ['read.orders'],
        counter: 5  // Shows a badge with "5"
    )
);
```

## Using in Module Development

When developing modules, you need to extend the dashboard menu using the **Hook system**. The correct hook name is `ns-dashboard-menus` (not `ns.dashboard.menus`).

### Basic Hook Usage

```php
<?php

namespace Modules\YourModule\Providers;

use App\Classes\AsideMenu;
use App\Classes\Hook;
use Illuminate\Support\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            // Add your menu modifications here
            return $menus;
        });
    }
}
```

### Method 1: Adding Menu After Existing Menu

Use `array_insert_after()` helper function to add a menu after a specific menu identifier:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    if (isset($menus['inventory'])) {
        $newMenu = AsideMenu::menu(
            label: __m('Restaurant', 'NsGastro'),
            identifier: 'ns-gastro',
            icon: 'la-utensils',
            permissions: ['gastro.read.table'],
            childrens: AsideMenu::childrens(
                AsideMenu::subMenu(
                    label: __m('Tables', 'NsGastro'),
                    identifier: 'tables',
                    href: ns()->route('ns-gastro-tables'),
                    permissions: ['gastro.read.table']
                )
            )
        );

        // Insert after 'inventory' menu
        $menus = array_insert_after($menus, 'inventory', $newMenu);
    }

    return $menus;
});
```

### Method 2: Adding Menu Before Existing Menu

Use `array_insert_before()` helper function to add a menu before a specific menu identifier:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    $newMenu = AsideMenu::menu(
        label: __('My Custom Menu'),
        identifier: 'custom-menu',
        icon: 'la-star',
        href: ns()->url('/dashboard/custom'),
        permissions: ['custom.read']
    );

    // Insert before 'settings' menu
    $menus = array_insert_before($menus, 'settings', $newMenu);

    return $menus;
});
```

### Method 3: Adding Submenu to Existing Menu

Add submenu items to existing parent menus:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    if (isset($menus['settings'])) {
        $newSubmenu = AsideMenu::subMenu(
            label: __m('My Settings', 'YourModule'),
            identifier: 'my-settings',
            href: ns()->route('ns.dashboard.settings', ['settings' => 'my-settings']),
            permissions: ['manage.options']
        );

        // Add after 'pos' submenu in settings
        $menus['settings']['childrens'] = array_insert_after(
            $menus['settings']['childrens'], 
            'pos', 
            $newSubmenu
        );
    }

    return $menus;
});
```

### Method 4: Appending to End of Submenu

Simply append to the childrens array:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    if (isset($menus['settings'])) {
        $menus['settings']['childrens'] = [
            ...$menus['settings']['childrens'],
            ...AsideMenu::subMenu(
                label: __m('Export/Import Options', 'NsOptionsExporter'),
                identifier: 'export-import-options',
                href: ns()->route('ns.dashboard.settings', ['settings' => 'export-import']),
                permissions: ['manage.options']
            ),
        ];
    }

    return $menus;
});
```

### Hook Priority

You can specify hook priority as the third parameter (default is 10, higher runs later):

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    // Your modifications
    return $menus;
}, 30); // Runs after hooks with priority 10-29
```

## Available Menu Identifiers

Below is a complete list of menu and submenu identifiers available in NexoPOS core (defined in `app/Services/MenuService.php`). Use these identifiers with `array_insert_after()` or `array_insert_before()` functions.

### Main Menu Identifiers

| Identifier | Label | Icon | Description |
|------------|-------|------|-------------|
| `dashboard` | Dashboard | `la-home` | Main dashboard menu |
| `pos` | POS | `la-cash-register` | Point of Sale |
| `orders` | Orders | `la-list-ol` | Orders management |
| `medias` | Medias | `la-photo-video` | Media library |
| `customers` | Customers | `la-user-friends` | Customer management |
| `providers` | Providers | `la-user-tie` | Provider/Supplier management |
| `accounting` | Accounting | `la-stream` | Accounting & expenses |
| `inventory` | Inventory | `la-boxes` | Inventory management |
| `taxes` | Taxes | `la-balance-scale-left` | Tax management |
| `modules` | Modules | `la-plug` | Module management |
| `users` | Users | `la-users` | User management |
| `roles` | Roles | `la-shield-alt` | Role & permissions |
| `procurements` | Procurements | `la-truck-loading` | Procurement management |
| `reports` | Reports | `la-chart-pie` | Reports & analytics |
| `settings` | Settings | `la-cogs` | System settings |

### Submenu Identifiers by Parent

#### Dashboard (`dashboard`)
| Identifier | Label |
|------------|-------|
| `index` | Home |

#### Orders (`orders`)
| Identifier | Label |
|------------|-------|
| `order-list` | Orders List |
| `payment-type` | Payment Types |
| `assignated-orders` | Assignated Orders |

#### Customers (`customers`)
| Identifier | Label |
|------------|-------|
| `customers` | List |
| `create-customer` | Create Customer |
| `customers-groups` | Customers Groups |
| `create-customers-group` | Create Group |
| `list-reward-system` | Reward Systems |
| `create-reward-system` | Create Reward |
| `list-coupons` | List Coupons |
| `create-coupons` | Create Coupon |

#### Providers (`providers`)
| Identifier | Label |
|------------|-------|
| `providers` | List |
| `create-provider` | Create A Provider |

#### Accounting (`accounting`)
| Identifier | Label |
|------------|-------|
| `transactions` | Expenses |
| `create-transaction` | Create Expense |
| `transactions-history` | Transaction History |
| `transacations-rules` | Rules |
| `transactions-account` | Accounts |
| `create-transactions-account` | Create Account |

#### Inventory (`inventory`)
| Identifier | Label |
|------------|-------|
| `products` | Products |
| `create-products` | Create Product |
| `labels-printing` | Print Labels |
| `categories` | Categories |
| `create-categories` | Create Category |
| `units` | Units |
| `create-units` | Create Unit |
| `unit-groups` | Unit Groups |
| `create-unit-groups` | Create Unit Groups |
| `stock-adjustment` | Stock Adjustment |
| `product-history` | Stock Flow Records |

#### Taxes (`taxes`)
| Identifier | Label |
|------------|-------|
| `taxes-groups` | Taxes Groups |
| `create-taxes-group` | Create Tax Groups |
| `taxes` | Taxes |
| `create-tax` | Create Tax |

#### Modules (`modules`)
| Identifier | Label |
|------------|-------|
| `modules` | List |
| `upload-module` | Upload Module |

#### Users (`users`)
| Identifier | Label |
|------------|-------|
| `profile` | My Profile |
| `users` | Users List |
| `create-user` | Create User |

#### Roles (`roles`)
| Identifier | Label |
|------------|-------|
| `all-roles` | Roles |
| `create-role` | Create Roles |
| `permissions` | Permissions Manager |

#### Procurements (`procurements`)
| Identifier | Label |
|------------|-------|
| `procurements` | Procurements List |
| `procurements-create` | New Procurement |
| `procurements-products` | Products |

#### Reports (`reports`)
| Identifier | Label |
|------------|-------|
| `sales` | Sale Report |
| `products-report` | Sales Progress |
| `customers-statement` | Customers Statement |
| `low-stock` | Stock Report |
| `stock-history` | Stock History |
| `sold-stock` | Sold Stock |
| `profit` | Incomes & Loosses |
| `transactions` | Transactions |
| `annulal-sales` | Annual Report |
| `payment-types` | Sales By Payments |

#### Settings (`settings`)
| Identifier | Label |
|------------|-------|
| `general` | General |
| `pos` | POS |
| `customers` | Customers |
| `orders` | Orders |
| `accounting` | Accounting |
| `reports` | Reports |
| `invoices` | Invoices |
| `reset` | Reset |
| `about` | About |

## Complete Examples

### Example 1: Adding New Top-Level Menu After Inventory

```php
<?php

namespace Modules\NsGastro\Providers;

use App\Classes\AsideMenu;
use App\Classes\Hook;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            if (isset($menus['inventory'])) {
                $gastroMenu = AsideMenu::menu(
                    label: __m('Restaurant', 'NsGastro'),
                    identifier: 'ns-gastro',
                    icon: 'la-utensils',
                    permissions: ['gastro.read.table'],
                    childrens: AsideMenu::childrens(
                        AsideMenu::subMenu(
                            label: __m('Kitchen Screen', 'NsGastro'),
                            identifier: 'kitchen-screen',
                            href: ns()->route('ns-gastro-kitchen-screen'),
                            permissions: ['gastro.use.kitchens']
                        ),
                        AsideMenu::subMenu(
                            label: __m('Tables', 'NsGastro'),
                            identifier: 'tables',
                            href: ns()->route('ns-gastro-tables'),
                            permissions: ['gastro.read.table']
                        )
                    )
                );

                // Insert after 'inventory' menu
                $menus = array_insert_after($menus, 'inventory', $gastroMenu);
            }

            return $menus;
        }, 30);
    }
}
```

### Example 2: Adding Submenu to Settings

```php
<?php

namespace Modules\NsPageBuilder\Providers;

use App\Classes\AsideMenu;
use App\Classes\Hook;
use Illuminate\Support\ServiceProvider;

class NsPageBuilderServiceProvider extends ServiceProvider
{
    public function register()
    {
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            if (isset($menus['settings'])) {
                $newSubmenu = AsideMenu::subMenu(
                    label: __m('Page Builder', 'NsPageBuilder'),
                    identifier: 'pagebuilder-settings',
                    href: ns()->route('ns.dashboard.settings', ['settings' => 'pagebuilder']),
                    permissions: ['manage.options']
                );

                // Add after 'pos' submenu
                $menus['settings']['childrens'] = array_insert_after(
                    $menus['settings']['childrens'], 
                    'pos', 
                    $newSubmenu
                );
            }

            return $menus;
        });
    }
}
```

### Example 3: Appending Submenu to End

```php
<?php

namespace Modules\NsOptionsExporter\Filters;

use App\Classes\AsideMenu;

class MenuFilter 
{
    public static function saveMenu($menus)
    {
        if (isset($menus['settings'])) {
            // Append to end of settings submenus
            $menus['settings']['childrens'] = [
                ...$menus['settings']['childrens'],
                ...AsideMenu::subMenu(
                    label: __m('Export/Import Options', 'NsOptionsExporter'),
                    identifier: 'export-import-options',
                    href: ns()->route('ns.dashboard.settings', ['settings' => 'export-import'])
                ),
            ];
        }

        return $menus;
    }
}
```

### Example 4: Complete Module with Multiple Menus

```php
<?php

namespace Modules\YourModule\Providers;

use App\Classes\AsideMenu;
use App\Classes\Hook;
use Illuminate\Support\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            // Add main menu before settings
            $newMenu = AsideMenu::menu(
                label: __m('Your Module', 'YourModule'),
                identifier: 'your-module',
                icon: 'la-puzzle-piece',
                permissions: ['yourmodule.read'],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __m('Dashboard', 'YourModule'),
                        identifier: 'module-dashboard',
                        href: ns()->url('/dashboard/your-module'),
                        permissions: ['yourmodule.read']
                    ),
                    AsideMenu::subMenu(
                        label: __m('Manage Items', 'YourModule'),
                        identifier: 'module-items',
                        href: ns()->url('/dashboard/your-module/items'),
                        permissions: ['yourmodule.items.read']
                    ),
                    AsideMenu::subMenu(
                        label: __m('Create Item', 'YourModule'),
                        identifier: 'module-create-item',
                        href: ns()->url('/dashboard/your-module/items/create'),
                        permissions: ['yourmodule.items.create']
                    ),
                    AsideMenu::subMenu(
                        label: __m('Reports', 'YourModule'),
                        identifier: 'module-reports',
                        href: ns()->url('/dashboard/your-module/reports'),
                        permissions: ['yourmodule.reports']
                    )
                )
            );

            $menus = array_insert_before($menus, 'settings', $newMenu);

            // Also add settings submenu
            if (isset($menus['settings'])) {
                $settingsSubmenu = AsideMenu::subMenu(
                    label: __m('Your Module', 'YourModule'),
                    identifier: 'your-module-settings',
                    href: ns()->route('ns.dashboard.settings', ['settings' => 'your-module']),
                    permissions: ['manage.options']
                );

                $menus['settings']['childrens'] = array_insert_after(
                    $menus['settings']['childrens'],
                    'general',
                    $settingsSubmenu
                );
            }

            return $menus;
        });
    }
}
```

### 🎯 **Real-World Example: PIN Login Module**

Here's how the PIN Login module should implement its menu:

```php
<?php

namespace Modules\NsPinLogin\Filters;

use App\Classes\AsideMenu;
use App\Classes\Output;

class DashboardFilters
{
    public static function registerDashboardMenus($menus)
    {
        // Add PIN Login lockout management to Users menu
        if (isset($menus['users'])) {
            $lockoutSubmenu = AsideMenu::subMenu(
                label: __m('PIN Lockouts', 'NsPinLogin'),
                identifier: 'pin-lockouts',
                href: route('ns.pin-client-lockout'),
                permissions: ['update.users']
            );
            
            $menus['users']['childrens'] = array_merge(
                $menus['users']['childrens'] ?? [],
                $lockoutSubmenu
            );
        }
        
        // Add PIN Login settings to Settings menu
        if (isset($menus['settings'])) {
            $settingsSubmenu = AsideMenu::subMenu(
                label: __m('PIN Login', 'NsPinLogin'),
                identifier: 'pin-login-settings',
                href: ns()->route('ns.dashboard.settings', ['settings' => 'ns-pin-login-settings']),
                permissions: ['manage.options']
            );
            
            $menus['settings']['childrens'] = array_merge(
                $menus['settings']['childrens'] ?? [],
                $settingsSubmenu
            );
        }
        
        return $menus;
    }
}
```

## Best Practices

### 1. Positioning Strategy

**Choose the right insertion method:**

- **Insert After (`array_insert_after`)**: Use when you want your menu to appear after a specific menu
  ```php
  // Good for feature modules that extend core functionality
  $menus = array_insert_after($menus, 'inventory', $gastroMenu);
  ```

- **Insert Before (`array_insert_before`)**: Use when you want your menu to appear before a specific menu
  ```php
  // Good for important modules that should be prominently placed
  $menus = array_insert_before($menus, 'settings', $myModule);
  ```

- **Append (Spread Operator)**: Use when order doesn't matter
  ```php
  // Good for settings submenus
  $menus['settings']['childrens'] = [
      ...$menus['settings']['childrens'],
      ...AsideMenu::subMenu(...)
  ];
  ```

### 2. Permission Strategy

Always use module-specific namespaced permissions:

```php
// ✅ Good: Module-specific permission
permissions: ['nspagebuilder.pages.read']

// ✅ Better: Following standard CRUD pattern  
permissions: ['read.nspagebuilder.pages']

// ❌ Avoid: Generic permissions that might conflict
permissions: ['read.pages']
```

### 3. Translation Strategy

Use module-specific translations:

```php
// ✅ Correct: Module-specific translation
label: __m('Page Builder', 'NsPageBuilder')

// ❌ Incorrect: Core translation for module features
label: __('Page Builder')
```

### 4. Hook Priority

Use hook priority when menu order matters:

```php
// Higher priority (runs later) - can override earlier additions
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    // Your menu logic
    return $menus;
}, 30); // Priority 30 - runs after default (10)

// Lower priority (runs earlier)
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    // Your menu logic  
    return $menus;
}, 5); // Priority 5 - runs before default (10)
```

### 5. Error Prevention

Always check for menu existence before extending:

```php
// ✅ Safe: Check before accessing
if (isset($menus['settings'])) {
    $menus['settings']['childrens'] = array_insert_after(
        $menus['settings']['childrens'],
        'pos',
        $newSubmenu
    );
}

// ❌ Unsafe: Will throw error if menu doesn't exist
$menus['settings']['childrens'] = array_insert_after(
    $menus['settings']['childrens'],
    'pos',
    $newSubmenu
);
```

### 6. Consistent Identifiers

Use descriptive, unique identifiers:

```php
// ✅ Good: Clear, namespaced identifiers
identifier: 'nspagebuilder-all-pages'
identifier: 'nspagebuilder-create-page'
identifier: 'nspagebuilder-settings'

// ❌ Bad: Generic identifiers that might conflict
identifier: 'pages'
identifier: 'create'
identifier: 'settings'
```

### 7. Icon Selection

Use appropriate Line Awesome icons:

```php
// Feature modules
icon: 'la-utensils'      // Restaurant/Food
icon: 'la-file-alt'      // Pages/Documents
icon: 'la-box'           // Products/Inventory
icon: 'la-users'         // Users/Customers

// Tools/Utilities
icon: 'la-cogs'          // Settings
icon: 'la-chart-bar'     // Reports/Analytics
icon: 'la-plug'          // Integrations
icon: 'la-download'      // Import/Export
```

## Common Patterns

### Pattern 1: Add Feature Module Menu

For modules that add major functionality:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    $moduleMenu = AsideMenu::menu(
        label: __m('Module Name', 'ModuleName'),
        identifier: 'module-identifier',
        icon: 'la-puzzle-piece',
        permissions: ['module.access'],
        childrens: AsideMenu::childrens(
            AsideMenu::subMenu(
                label: __m('Dashboard', 'ModuleName'),
                identifier: 'module-dashboard',
                href: ns()->url('/dashboard/module'),
                permissions: ['module.read']
            )
        )
    );

    // Insert after related core menu
    return array_insert_after($menus, 'inventory', $moduleMenu);
});
```

### Pattern 2: Add Settings Submenu

For module configuration:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    if (isset($menus['settings'])) {
        $settingsSubmenu = AsideMenu::subMenu(
            label: __m('Module Settings', 'ModuleName'),
            identifier: 'module-settings',
            href: ns()->route('ns.dashboard.settings', ['settings' => 'module-settings']),
            permissions: ['manage.options']
        );

        $menus['settings']['childrens'] = array_insert_after(
            $menus['settings']['childrens'],
            'general',
            $settingsSubmenu
        );
    }

    return $menus;
});
```

### Pattern 3: Extend Existing Menu

For adding functionality to existing sections:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    if (isset($menus['customers'])) {
        $newSubmenu = AsideMenu::subMenu(
            label: __m('Customer Groups', 'ModuleName'),
            identifier: 'customer-groups',
            href: ns()->url('/dashboard/customers/groups'),
            permissions: ['read.customers']
        );

        $menus['customers']['childrens'] = [
            ...$menus['customers']['childrens'],
            ...$newSubmenu
        ];
    }

    return $menus;
});
```

## Troubleshooting

### Menu Not Showing

1. **Check Permission**: Verify user has the required permission
   ```php
   // Test if permission exists
   $permission = \App\Models\Permission::namespace('your.permission');
   
   // Check if user has permission
   ns()->allowedTo('your.permission');
   ```

2. **Verify Hook Name**: Must be exactly `'ns-dashboard-menus'`
   ```php
   // ✅ Correct
   Hook::addFilter('ns-dashboard-menus', ...)
   
   // ❌ Wrong
   Hook::addFilter('ns.dashboard.menus', ...)
   ```

3. **Check Service Provider**: Ensure provider is registered and `register()` method is called

### Menu Position Wrong

1. **Verify Target Identifier**: Check the identifier you're inserting after/before exists
   ```php
   // Debug: Print all menu identifiers
   array_keys($menus);
   ```

2. **Check Hook Priority**: Lower priority runs first
   ```php
   // If menu not appearing in right place, try different priority
   Hook::addFilter('ns-dashboard-menus', function($menus) {
       // ...
   }, 20); // Try different priorities: 5, 10, 20, 30
   ```

### Submenu Not Showing

1. **Verify Parent Menu Exists**: Always check with `isset()`
2. **Check Array Structure**: Ensure using `childrens` key correctly
3. **Verify Merge Method**: Use `array_insert_after`/`before` or spread operator correctly

## Summary

- **Always use the AsideMenu class** for consistency and maintainability
- **Use the correct hook**: `'ns-dashboard-menus'` (note the dash, not dot)
- **Position with helpers**: `array_insert_after()` and `array_insert_before()` for precise control
- **Check existence**: Always verify menus exist before extending them
- **Module translations**: Use `__m()` with module namespace
- **Namespaced identifiers**: Prevent conflicts with unique, descriptive identifiers
- **Proper permissions**: Use module-specific permissions following CRUD patterns

This approach ensures your module menus integrate seamlessly with NexoPOS while maintaining code quality and preventing conflicts.
