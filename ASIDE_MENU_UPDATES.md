# Aside Menu Instructions Updates Summary

## Document Updated
`.github/instructions/nexopos-asidemenu.instructions.md`

## Key Changes Made

### 1. ✅ Corrected Hook Name
- **Old (Incorrect)**: `'ns.dashboard.menus'`
- **New (Correct)**: `'ns-dashboard-menus'`
- **Impact**: This is the critical fix - the hook name must use dashes, not dots

### 2. ✅ Added Complete Hook Usage Patterns

Added comprehensive section "Using in Module Development" with 4 methods:

#### Method 1: Insert Menu After Specific Menu
```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    $menus = array_insert_after($menus, 'inventory', $newMenu);
    return $menus;
});
```

#### Method 2: Insert Menu Before Specific Menu
```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    $menus = array_insert_before($menus, 'settings', $newMenu);
    return $menus;
});
```

#### Method 3: Add Submenu Using Helper
```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    if (isset($menus['settings'])) {
        $menus['settings']['childrens'] = array_insert_after(
            $menus['settings']['childrens'],
            'general',
            $newSubmenu
        );
    }
    return $menus;
});
```

#### Method 4: Append Submenu Using Spread Operator
```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    if (isset($menus['settings'])) {
        $menus['settings']['childrens'] = [
            ...$menus['settings']['childrens'],
            ...AsideMenu::subMenu(...)
        ];
    }
    return $menus;
});
```

### 3. ✅ Documented Helper Functions

Added complete documentation for array insertion helpers:

```php
// Insert element after a specific key
array_insert_after(array $array, $key, array $new): array

// Insert element before a specific key  
array_insert_before(array $array, $key, array $new): array
```

**Location**: `app/Services/HelperFunctions.php`

### 4. ✅ Added Hook Priority Documentation

Explained the third parameter for hook priority:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    // Your logic
    return $menus;
}, 30); // Priority: default is 10, higher runs later
```

**Use Cases**:
- Lower priority (5): Runs before others - base menus
- Default (10): Standard priority  
- Higher priority (30+): Runs after others - can override/modify

### 5. ✅ Added Complete Menu Identifier Reference

Documented all 15 main menus with their identifiers:

| Identifier | Label | Icon | Permission |
|------------|-------|------|------------|
| `dashboard` | Dashboard | la-home | - |
| `pos` | POS | la-cash-register | nexopos.pos.access |
| `orders` | Orders | la-shopping-cart | read.orders |
| `customers` | Customers | la-users | read.customers |
| `customers-groups` | Groups | la-users | read.customers-groups |
| `providers` | Providers | la-truck | read.providers |
| `expenses` | Expenses | la-wallet | read.expenses |
| `medias` | Medias | la-photo-video | manage.medias |
| `inventory` | Inventory | la-box | read.products |
| `accounting` | Accounting | la-calculator | nexopos.view.accounting |
| `procurements` | Procurements | la-hand-holding-usd | read.procurements |
| `reports` | Reports | la-chart-bar | read.reports |
| `users` | Users | la-users | read.users |
| `modules` | Modules | la-plug | manage.modules |
| `settings` | Settings | la-cog | manage.options |

And all 80+ submenus organized by parent menu.

### 6. ✅ Added Real-World Examples

Included 4 complete working examples from production modules:

1. **NsGastro**: Restaurant module menu inserted after inventory
2. **NsPageBuilder**: Settings submenu using array_insert_after  
3. **NsOptionsExporter**: Settings submenu using spread operator
4. **Complete Module**: Full pattern with main menu + settings submenu

### 7. ✅ Added Best Practices Section

Comprehensive best practices covering:

- **Positioning Strategy**: When to use insert_after vs insert_before vs append
- **Permission Strategy**: Module-specific namespaced permissions
- **Translation Strategy**: Using `__m()` for module translations
- **Hook Priority**: When and how to use priority parameter
- **Error Prevention**: Always check menu existence before extending
- **Consistent Identifiers**: Use namespaced identifiers to prevent conflicts
- **Icon Selection**: Line Awesome icon recommendations

### 8. ✅ Added Common Patterns

Three reusable patterns for common scenarios:

- Pattern 1: Add Feature Module Menu
- Pattern 2: Add Settings Submenu  
- Pattern 3: Extend Existing Menu

### 9. ✅ Added Troubleshooting Section

Solutions for common issues:

- Menu not showing (permission/hook/provider issues)
- Menu position wrong (identifier/priority issues)
- Submenu not showing (parent menu/structure issues)

### 10. ✅ Cleaned Up Document Structure

- Removed duplicate sections
- Removed deprecated manual array approach examples
- Consolidated best practices into one comprehensive section
- Improved document flow and organization

## Document Statistics

- **Total Lines**: 1,008 lines
- **Main Sections**: 11 major sections
- **Code Examples**: 20+ complete working examples
- **Menu Identifiers Documented**: 15 main menus + 80+ submenus
- **Methods Documented**: 4 extension methods + helper functions

## Files Updated

1. `.github/instructions/nexopos-asidemenu.instructions.md` (complete rewrite)
2. `modules/NsPageBuilder/Providers/NsPageBuilderServiceProvider.php` (using correct hook)

## Migration Guide for Existing Modules

If you have existing modules using the old hook name, update as follows:

### Before (❌ Wrong)
```php
Hook::addFilter('ns.dashboard.menus', function ($menus) {
    // Your logic
    return $menus;
});
```

### After (✅ Correct)
```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    // Your logic  
    return $menus;
});
```

## Testing Checklist

To verify your module menu is working:

- [ ] Hook name is `'ns-dashboard-menus'` (dashes, not dots)
- [ ] Using AsideMenu class methods (not manual arrays)
- [ ] Checking menu existence with `isset()` before extending
- [ ] Using module-specific permissions
- [ ] Using `__m()` for module translations
- [ ] Identifiers are namespaced and unique
- [ ] Testing with user who has the required permissions

## Additional Resources

- **AsideMenu Class**: `app/Classes/AsideMenu.php`
- **Helper Functions**: `app/Services/HelperFunctions.php`  
- **MenuService**: `app/Services/MenuService.php`
- **Hook Facade**: `TorMorten\Eventy\Facades\Events as Hook`

## Impact on NsPageBuilder Module

The Page Builder module has been updated to use the correct pattern:

```php
Hook::addFilter('ns-dashboard-menus', function ($menus) {
    $pageBuilderMenu = AsideMenu::menu(
        label: __m('Page Builder', 'NsPageBuilder'),
        identifier: 'nspagebuilder',
        icon: 'la-file-alt',
        permissions: ['read.nspagebuilder.pages'],
        childrens: AsideMenu::childrens(
            AsideMenu::subMenu(
                label: __m('All Pages', 'NsPageBuilder'),
                identifier: 'nspagebuilder-all-pages',
                href: ns()->url('/dashboard/pagebuilder/pages'),
                permissions: ['read.nspagebuilder.pages']
            ),
            AsideMenu::subMenu(
                label: __m('Create Page', 'NsPageBuilder'),
                identifier: 'nspagebuilder-create-page',
                href: ns()->url('/dashboard/pagebuilder/pages/create'),
                permissions: ['create.nspagebuilder.pages']
            )
        )
    );

    return array_insert_before($menus, 'settings', $pageBuilderMenu);
});
```

This menu will appear before the Settings menu in the dashboard.
