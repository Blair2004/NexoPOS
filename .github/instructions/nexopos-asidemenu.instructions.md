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

When developing modules, there are two approaches to extend the dashboard menu. The **recommended approach** is to use the AsideMenu class properly for better structure and maintainability.

### ❌ **Manual Array Approach (Not Recommended)**

```php
<?php

namespace Modules\YourModule\Filters;

class DashboardFilters
{
    public static function registerDashboardMenus($menus)
    {
        // Manual array creation - harder to maintain
        if (isset($menus['users'])) {
            $menus['users']['childrens']['your-feature'] = [
                'label' => __m('Your Feature', 'YourModule'),
                'identifier' => 'your-feature',
                'href' => ns()->url('/dashboard/your-feature'),
                'permissions' => ['your.permission']
            ];
        }
        
        return $menus;
    }
}
```

### ✅ **AsideMenu Class Approach (Recommended)**

The better approach is to use the AsideMenu class methods for consistency and better structure:

```php
<?php

namespace Modules\YourModule\Filters;

use App\Classes\AsideMenu;

class DashboardFilters
{
    public static function registerDashboardMenus($menus)
    {
        // Method 1: Add submenu to existing menu using AsideMenu
        if (isset($menus['users'])) {
            $newSubmenu = AsideMenu::subMenu(
                label: __m('PIN Login Settings', 'YourModule'),
                identifier: 'pin-login-settings',
                href: ns()->route('ns.dashboard.settings', ['settings' => 'pin-login-settings']),
                permissions: ['manage.options']
            );
            
            // Merge the new submenu properly
            $menus['users']['childrens'] = array_merge(
                $menus['users']['childrens'] ?? [],
                $newSubmenu
            );
        }
        
        // Method 2: Add completely new top-level menu
        $newMenu = AsideMenu::menu(
            label: __m('Your Module', 'YourModule'),
            identifier: 'your-module',
            icon: 'la-puzzle-piece',
            permissions: ['your.module.access'],
            childrens: AsideMenu::childrens(
                AsideMenu::subMenu(
                    label: __m('Dashboard', 'YourModule'),
                    identifier: 'module-dashboard',
                    href: ns()->url('/dashboard/your-module'),
                    permissions: ['your.module.read']
                ),
                AsideMenu::subMenu(
                    label: __m('Settings', 'YourModule'),
                    identifier: 'module-settings',
                    href: ns()->route('ns.dashboard.settings', ['settings' => 'your-module-settings']),
                    permissions: ['manage.options']
                ),
                AsideMenu::subMenu(
                    label: __m('Reports', 'YourModule'),
                    identifier: 'module-reports',
                    href: ns()->url('/dashboard/your-module/reports'),
                    permissions: ['your.module.reports']
                )
            )
        );
        
        // Merge the new menu with existing menus
        $menus = array_merge($menus, $newMenu);
        
        return $menus;
    }
}
```

### 🏆 **Advanced Module Menu Pattern**

For larger modules, consider creating a dedicated menu builder:

```php
<?php

namespace Modules\YourModule\Filters;

use App\Classes\AsideMenu;

class DashboardFilters
{
    public static function registerDashboardMenus($menus)
    {
        // Add module-specific menus
        $moduleMenus = self::buildModuleMenus();
        $menus = array_merge($menus, $moduleMenus);
        
        // Extend existing menus
        $menus = self::extendExistingMenus($menus);
        
        return $menus;
    }
    
    /**
     * Build complete module menu structure
     */
    private static function buildModuleMenus(): array
    {
        return AsideMenu::wrapper(
            // Main module menu
            AsideMenu::menu(
                label: __m('Your Module', 'YourModule'),
                identifier: 'your-module',
                icon: 'la-puzzle-piece',
                permissions: ['your.module.access'],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __m('Dashboard', 'YourModule'),
                        identifier: 'module-dashboard',
                        href: ns()->url('/dashboard/your-module'),
                        permissions: ['your.module.read']
                    ),
                    AsideMenu::subMenu(
                        label: __m('Manage Items', 'YourModule'),
                        identifier: 'module-items',
                        href: ns()->url('/dashboard/your-module/items'),
                        permissions: ['your.module.items.read']
                    ),
                    AsideMenu::subMenu(
                        label: __m('Create Item', 'YourModule'),
                        identifier: 'module-create-item',
                        href: ns()->url('/dashboard/your-module/items/create'),
                        permissions: ['your.module.items.create']
                    )
                )
            ),
            
            // Additional module menu if needed
            AsideMenu::menu(
                label: __m('Module Tools', 'YourModule'),
                identifier: 'your-module-tools',
                icon: 'la-tools',
                permissions: ['your.module.tools'],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __m('Import Data', 'YourModule'),
                        identifier: 'module-import',
                        href: ns()->url('/dashboard/your-module/import'),
                        permissions: ['your.module.import']
                    ),
                    AsideMenu::subMenu(
                        label: __m('Export Data', 'YourModule'),
                        identifier: 'module-export',
                        href: ns()->url('/dashboard/your-module/export'),
                        permissions: ['your.module.export']
                    )
                )
            )
        );
    }
    
    /**
     * Extend existing NexoPOS menus
     */
    private static function extendExistingMenus(array $menus): array
    {
        // Add to Settings menu
        if (isset($menus['settings'])) {
            $settingsSubmenu = AsideMenu::subMenu(
                label: __m('Your Module Settings', 'YourModule'),
                identifier: 'your-module-settings',
                href: ns()->route('ns.dashboard.settings', ['settings' => 'your-module-settings']),
                permissions: ['manage.options']
            );
            
            $menus['settings']['childrens'] = array_merge(
                $menus['settings']['childrens'] ?? [],
                $settingsSubmenu
            );
        }
        
        // Add to Reports menu if it exists
        if (isset($menus['reports'])) {
            $reportsSubmenu = AsideMenu::subMenu(
                label: __m('Module Reports', 'YourModule'),
                identifier: 'your-module-reports',
                href: ns()->url('/dashboard/reports/your-module'),
                permissions: ['your.module.reports']
            );
            
            $menus['reports']['childrens'] = array_merge(
                $menus['reports']['childrens'] ?? [],
                $reportsSubmenu
            );
        }
        
        return $menus;
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

## Benefits of Using AsideMenu Class

### 🎯 **Why Use AsideMenu Over Manual Arrays?**

1. **Type Safety**: AsideMenu methods provide parameter validation and structure consistency
2. **Maintainability**: Changes to menu structure are centralized in the AsideMenu class
3. **Documentation**: Method signatures serve as built-in documentation
4. **Consistency**: Ensures all menus follow the same structure across modules
5. **Future-Proof**: Core updates to menu structure automatically benefit all modules
6. **IDE Support**: Better autocomplete and parameter hints during development

### 🔧 **Comparison: Manual vs AsideMenu**

**Manual Array Approach (Avoid):**
```php
// Prone to typos, inconsistent structure
$menu = [
    'lable' => 'Wrong spelling!', // Typo not caught
    'identifier' => 'my-menu',
    'childrens' => [ // Manual nesting
        'submenu1' => [
            'label' => 'Submenu',
            // Missing required fields?
        ]
    ]
];
```

**AsideMenu Class Approach (Recommended):**
```php
// Type-safe, consistent, documented
$menu = AsideMenu::menu(
    label: 'Correct Structure', // Parameter names prevent typos
    identifier: 'my-menu',
    permissions: ['required.permission'], // Clear parameter expectations
    childrens: AsideMenu::childrens( // Structured nesting
        AsideMenu::subMenu(
            label: 'Submenu',
            identifier: 'submenu1',
            href: '/path' // Required parameters enforced
        )
    )
);
```

## Advanced Best Practices

### 1. **Module Organization Pattern**

```php
class DashboardFilters
{
    public static function registerDashboardMenus($menus)
    {
        // Separate concerns: building vs extending
        $menus = self::addModuleMenus($menus);
        $menus = self::extendCoreMenus($menus);
        return $menus;
    }
    
    private static function addModuleMenus($menus)
    {
        // Build module-specific menus using AsideMenu
        return array_merge($menus, self::buildModuleMenus());
    }
    
    private static function extendCoreMenus($menus)
    {
        // Extend existing NexoPOS menus
        return self::addToSettingsMenu($menus);
    }
}
```

### 2. **Permission Strategy**

```php
// Good: Basic permissions
AsideMenu::subMenu(
    label: __m('User Management', 'YourModule'),
    identifier: 'user-management',
    href: ns()->url('/dashboard/users'),
    permissions: ['read.users']
)

// Better: Module-specific namespaced permissions
AsideMenu::subMenu(
    label: __m('Module Settings', 'YourModule'),
    identifier: 'module-settings',
    href: route('module.settings'),
    permissions: ['yourmodule.settings.manage'] // Namespaced permission
)
```

### 3. **Error Prevention Patterns**

```php
// Always check menu exists before extending
if (isset($menus['settings'])) {
    $settingsSubmenu = AsideMenu::subMenu(
        label: __m('Module Settings', 'YourModule'),
        identifier: 'module-settings',
        href: ns()->route('ns.dashboard.settings', ['settings' => 'module-settings']),
        permissions: ['manage.options']
    );
    
    $menus['settings']['childrens'] = array_merge(
        $menus['settings']['childrens'] ?? [], // Null coalescing prevents errors
        $settingsSubmenu
    );
}
```

## Key Recommendations Summary

1. **Always use AsideMenu class methods** instead of manual array construction
2. **Separate menu building logic** into dedicated methods for maintainability  
3. **Use module-specific permissions** following namespaced patterns
4. **Include proper error checking** when extending existing menus
5. **Implement consistent translation** using `__m()` with module namespace
6. **Follow the wrapper → menu → childrens → subMenu hierarchy**

## Basic Best Practices

1. **Use Localization**: Always wrap menu labels with `__()` function for translation support
2. **Proper Permissions**: Define appropriate permissions for each menu item
3. **Meaningful Identifiers**: Use descriptive, unique identifiers for menu items
4. **Icon Consistency**: Use Line Awesome (LA) icons consistently across your menus
5. **URL Generation**: Use `ns()->url()` or `route()` helpers for generating URLs
6. **Module Localization**: Use `__m()` function for module-specific translations

## Icon Guidelines

NexoPOS uses Line Awesome icons. Common icon classes include:
- `la-home` - Dashboard/Home
- `la-users` - Users
- `la-cog` or `la-cogs` - Settings
- `la-shopping-cart` - Orders/Cart
- `la-box` - Products/Inventory
- `la-chart-bar` - Reports
- `la-plug` - Modules
- `la-shield-alt` - Roles/Security

## Generated Structure

The AsideMenu class generates arrays with this structure:

```php
[
    'menu-identifier' => [
        'label' => 'Menu Label',
        'href' => '/dashboard/path',
        'icon' => 'la-icon-class',
        'counter' => 0,
        'permissions' => ['permission1', 'permission2'],
        'show' => true,
        'childrens' => [
            'submenu-identifier' => [
                'label' => 'Submenu Label',
                'href' => '/dashboard/submenu/path',
                'icon' => 'la-icon-class',
                'permissions' => ['permission'],
                'show' => true
            ]
        ]
    ]
]
```

This structure is then used by the frontend Vue.js components to render the dashboard navigation menu.
