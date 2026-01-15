# Add Theme Support System to NexoPOS

**Date:** 2026-01-15  
**Type:** Feature  
**Affects:** Core, Dashboard, Frontend

## Summary

This change adds a comprehensive WordPress-inspired theme support system to NexoPOS. The system allows users to upload, enable, disable, and manage themes that provide custom frontend experiences with support for blogs, pages, stores, and custom menus.

## Changes Made

### Core Services
- **ThemeService** (`app/Services/ThemeService.php`): Complete theme management service with methods for loading, enabling, disabling, uploading, deleting, and extracting themes. Includes symlink management for theme assets on both Windows and Unix systems.
- **PageEditorService** (`app/Services/PageEditorService.php`): Service for managing page editor blocks and rendering page content.
- **ThemeServiceProvider** (`app/Providers/ThemeServiceProvider.php`): Service provider that bootstraps themes on application start, creates symlinks, and registers theme views.

### Events System
- `ThemeEnabledEvent`: Fired when a theme is enabled
- `ThemeDisabledEvent`: Fired when a theme is disabled
- `EditorInitializedEvent`: Fired when page editor loads (allows modules to register custom blocks)
- `ThemeRoutesLoadedEvent`: Fired when theme routes are being registered
- `MenuRenderingEvent`: Fired before menu is rendered (allows modification)
- `PageRenderingEvent`: Fired before page is rendered (allows modification)

### Database Schema
Created 4 new tables:
- `nexopos_themes_menus`: Store theme menus with identifiers and theme associations
- `nexopos_themes_menu_items`: Hierarchical menu items with parent-child relationships (max depth: 3)
- `nexopos_themes_slugs`: Customizable URL slugs for theme features (blog, store, pages)
- `nexopos_themes_pages`: Pages with block-based content editor, status, and hierarchy support

### Models
- **ThemeMenu**: Menu management with items relationship
- **ThemeMenuItem**: Menu items with parent-child hierarchy
- **ThemeSlug**: Slug configuration for theme features
- **ThemePage**: Page model with content blocks, publishing status, and hierarchy

### Controllers
- **ThemesController** (`app/Http/Controllers/Dashboard/ThemesController.php`): Dashboard controller for theme management (list, upload, enable, disable, delete, download)
- **ThemeFrontendController** (`app/Http/Controllers/ThemeFrontendController.php`): Frontend controller for theme routing (blog, store, pages, search)

### Routes
- **Web Routes** (`routes/web/themes.php`): Dashboard routes for theme list, upload, and download
- **API Routes** (`routes/api/themes.php`): REST API for theme operations (protected by `manage.themes` permission)
- Integrated into `routes/nexopos.php` and `routes/api-base.php`

### Console Commands
- `php artisan themes:symlink {namespace?}`: Create symbolic links for theme assets
- `php artisan themes:enable {namespace}`: Enable a theme via CLI
- `php artisan themes:disable {namespace}`: Disable a theme via CLI
- `php artisan themes:list`: List all themes with their status

### Dashboard Views
- **Theme List** (`resources/views/pages/dashboard/themes/list.blade.php`): Grid view of all themes with preview images
- **Theme Upload** (`resources/views/pages/dashboard/themes/upload.blade.php`): Upload form for theme .zip files
- **Vue Component** (`resources/ts/pages/dashboard/themes.vue`): Interactive theme management UI with search, filtering, and actions

### Permissions
Created 4 new permissions:
- `manage.themes`: Manage themes (upload, enable, disable, delete)
- `manage.theme.menus`: Create and edit theme menus
- `manage.theme.pages`: Create and edit theme pages
- `manage.theme.settings`: Configure theme slugs and settings

All permissions are automatically assigned to the admin role.

### Storage Configuration
Added two new storage disks in `config/filesystems.php`:
- `ns-themes`: Points to `themes/` directory for theme storage
- `ns-themes-temp`: Points to `storage/temporary-files/themes/` for temporary upload processing

### Theme Directory Structure
Themes are stored in `/themes/{ThemeNamespace}/` with the following structure:
```
themes/
├── ThemeName/
│   ├── config.xml           # Required: Theme configuration
│   ├── Public/             # Assets (CSS, JS, images) - will be symlinked
│   ├── Views/              # Blade templates
│   ├── preview.png/.jpg    # Theme preview image
│   └── ThemeModule.php     # Optional: Main theme class for hooks
```

### Config.xml Structure
Each theme must have a `config.xml` file:
```xml
<?xml version="1.0"?>
<theme>
    <namespace>ThemeName</namespace>
    <version>1.0.0</version>
    <name>Theme Display Name</name>
    <author>Author Name</author>
    <description>
        <locale lang="en">English description</locale>
        <locale lang="fr">French description</locale>
    </description>
    <core min-version="6.0.0" max-version="7.0.0">
    <features>
        <item name="Blog" identifier="blog" />
        <item name="Pages" identifier="pages" />
        <item name="Store" identifier="store" />
    </features>
</theme>
```

## Features

### Theme Management
- Upload themes as .zip files
- Only one theme can be enabled at a time
- Enable/disable themes with automatic asset symlinking
- Download themes as .zip for backup or sharing
- Delete themes (disabled themes only)
- Preview images displayed in theme list
- Multi-language description support

### Symlink Management
- Automatic creation of symbolic links from `public/themes/{namespace}` to `themes/{Namespace}/Public`
- Support for both Windows (using `mklink /J`) and Unix systems
- Automatic cleanup of broken symlinks
- CLI command for manual symlink management

### Extensibility
- Event system allows modules to hook into theme lifecycle
- Custom blocks can be registered for page editor
- Filters available for menu and page content modification
- Theme-specific routes can be registered dynamically

### Version Compatibility
- Themes can specify min/max NexoPOS version requirements in config.xml
- Installation blocked if version requirements aren't met
- Clear error messages for incompatible themes

## Migration Required

Run migrations to create theme database tables:
```bash
php artisan migrate
```

Create theme symlinks:
```bash
php artisan themes:symlink
```

## Breaking Changes

None. This is a new feature that doesn't affect existing functionality.

## Dependencies

No new external dependencies. Uses existing Laravel and NexoPOS infrastructure.

## Related Issues

This implements a comprehensive theme support system for NexoPOS, enabling:
- Custom frontend designs without modifying core
- Blog and e-commerce storefronts
- Drag-and-drop page builder (foundation laid)
- Menu management (foundation laid)
- URL slug customization (foundation laid)

## Next Steps (Not Included in This Change)

The following features have their database structure and services in place but need additional UI implementation:
1. **Page Editor**: Vue component for drag-and-drop block editing
2. **Menu Builder**: Vue component for visual menu management
3. **Slug Configuration**: UI for customizing feature URLs
4. **Core Blocks**: Implementation of layout, heading, paragraph, image, and button blocks
5. **Frontend Routes**: Dynamic route registration in `routes/web-base.php` based on enabled theme

## Usage Example

### Enable a Theme via Dashboard
1. Navigate to `/dashboard/themes`
2. Click "Upload" and select a theme .zip file
3. Click "Enable" on the uploaded theme
4. Theme assets are automatically symlinked

### Enable a Theme via CLI
```bash
php artisan themes:enable ThemeNamespace
```

### List All Themes
```bash
php artisan themes:list
```

## Testing Recommendations

1. Upload a valid theme .zip file
2. Enable the theme and verify symlink creation
3. Disable the theme and verify symlink removal
4. Test theme with min/max version requirements
5. Verify only one theme can be enabled at a time
6. Test theme deletion (should be blocked if enabled)
7. Download a theme and verify .zip integrity

## Security Considerations

- Theme uploads validated for .zip format
- config.xml parsed securely using SimpleXMLElement
- Permissions required for all theme management operations
- Symlink creation includes security checks
- Theme deletion blocked for enabled themes
