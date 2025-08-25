# NexoPOS Blade Layout System Guide

This comprehensive guide explains how to use the Blade templating system in NexoPOS to create layouts, manage headers and titles, and implement various page structures.

## Overview

NexoPOS uses Laravel's Blade templating engine with a structured layout system that provides consistent UI patterns across the application. The system is built around extendable layouts that handle common UI elements like navigation, headers, titles, and footers.

## Layout Structure

The layout files are located in `resources/views/layout/` and provide the foundation for all pages in NexoPOS.

### Core Layout Files

#### 1. Base Layout (`layout/base.blade.php`)

The foundation layout that provides basic HTML structure with theme support:

```php
<?php
use App\Models\UserAttribute;
use Illuminate\Support\Facades\Auth;

if ( Auth::check() && Auth::user()->attribute instanceof UserAttribute ) {
    $theme  =   Auth::user()->attribute->theme ?: ns()->option->get( 'ns_default_theme', 'light' );
} else {
    $theme  =   ns()->option->get( 'ns_default_theme', 'light' );
}
?>

@inject( 'dateService', 'App\Services\DateService' )
<!DOCTYPE html>
<html lang="en" data-theme="{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! $title ?? __( 'Unamed Page' ) !!}</title>
    @include( 'layout._header-injection' )
    @vite([
        'resources/scss/line-awesome/1.3.0/scss/line-awesome.scss',
        'resources/css/grid.css',
        'resources/css/fonts.css',
        'resources/css/animations.css',
        'resources/css/' . $theme . '.css'
    ])
    @yield( 'layout.base.header' )
    @include( 'layout._header-script' )
    @vite([ 'resources/ts/lang-loader.ts' ])
</head>
<body>
    @yield( 'layout.base.body' )
    @section( 'layout.base.footer' )
        @include( 'common.footer' )
    @show
</body>
</html>
```

**Features:**
- Theme support (light/dark themes)
- User-specific theme preferences
- Asset loading with Vite
- Header injection points
- Footer section

**Sections Available:**
- `layout.base.header` - For custom head content
- `layout.base.body` - Main body content
- `layout.base.footer` - Footer content

#### 2. Dashboard Layout (`layout/dashboard.blade.php`)

The main layout for dashboard pages with navigation sidebar:

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <!-- Your content here -->
@endsection
```

**Key Features:**
- Responsive sidebar navigation
- User menu and notifications
- Logo display
- Menu system integration
- Mobile-friendly overlay

**Available Sections:**
- `layout.dashboard.header` - Custom header content
- `layout.dashboard.body` - Main content area
- `layout.dashboard.body.with-header` - Content with dashboard header
- `layout.dashboard.with-header` - Alternative header syntax
- `layout.dashboard.body.with-title` - Content with title section
- `layout.dashboard.with-title` - Alternative title syntax
- `layout.dashboard.footer` - Footer content

#### 3. Dashboard Blank Layout (`layout/dashboard-blank.blade.php`)

Similar to dashboard layout but without the sidebar - useful for full-width pages:

```php
@extends( 'layout.dashboard-blank' )

@section( 'layout.dashboard-blank.body' )
    <!-- Full-width content -->
@endsection
```

#### 4. Default Layout (`layout/default.blade.php`)

Simple layout for public pages without dashboard features:

```php
@extends( 'layout.default' )

@section( 'layout.default.body' )
    <!-- Public page content -->
@endsection
```

## Common Layout Patterns

### 1. Basic Dashboard Page

**Pattern:** Simple dashboard page without header or title:

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="p-4">
    <!-- Your content here -->
</div>
@endsection
```

### 2. Dashboard Page with Header

**Pattern:** Dashboard page with header navigation:

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        <!-- Your content here -->
    </div>
</div>
@endsection
```

### 3. Dashboard Page with Header and Title

**Pattern:** Dashboard page with header and title section (most common):

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @include( 'common.dashboard.title' )
        <!-- Your content here -->
    </div>
</div>
@endsection
```

### 4. Using Built-in Layout Shortcuts

**Pattern:** Using the built-in layout shortcuts for common patterns:

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
    <!-- Content automatically gets header and title -->
    <div class="bg-white rounded shadow p-4">
        <!-- Your content here -->
    </div>
@endsection
```

## Header and Title Management

### Setting Page Title

The page title can be set in multiple ways:

#### 1. From Controller

```php
class ProductController extends DashboardController
{
    public function index()
    {
        return view( 'pages.dashboard.products.list', [
            'title' => __( 'Products' ),
            'description' => __( 'Manage your products inventory' )
        ]);
    }
}
```

#### 2. Using Helper Method

```php
// In the view or controller
$title = App\Services\Helper::pageTitle( $title ?? __( 'Default Title' ) );
```

### Title and Description Variables

When using the title section, these variables are available:

- `$title` - Main page title (required)
- `$description` - Page description (optional)

**Example:**

```php
return view( 'pages.dashboard.settings.general', [
    'title' => __( 'General Settings' ),
    'description' => __( 'Configure general application settings' )
]);
```
While loading a view for module, you'll need to provide the module namespace as the view namespace like this:

```php
return view( 'YourModule::pages.dashboard.index', [
    'title' => __( 'Module Dashboard' ),
    'description' => __( 'Overview of module features' )
]);
```

### Dashboard Header Components

The dashboard header includes:

**1. Navigation Toggle**
- Hamburger menu for sidebar toggle
- Responsive behavior for mobile

**2. User Menu**
- User avatar and name
- Profile and logout links
- Notification center

**3. Extensibility**
- Hook system for custom header content
- Filter: `ns-dashboard-header-file`

## Layout Section Reference

### Dashboard Layout Sections

| Section | Purpose | Includes Header | Includes Title | Container |
|---------|---------|-----------------|----------------|-----------|
| `layout.dashboard.body` | Raw content | No | No | No |
| `layout.dashboard.with-header` | Content with header | Yes | No | Yes (`px-4`) |
| `layout.dashboard.body.with-header` | Alternative syntax | Yes | No | Yes (`px-4`) |
| `layout.dashboard.with-title` | Content with header + title | Yes | Yes | Yes (`px-4`) |
| `layout.dashboard.body.with-title` | Alternative syntax | Yes | Yes | Yes (`px-4`) |

### Section Usage Examples

#### Raw Content (Full Control)
```php
@section( 'layout.dashboard.body' )
<div class="custom-layout">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="custom-content">
        <!-- Custom layout -->
    </div>
</div>
@endsection
```

#### With Header Only
```php
@section( 'layout.dashboard.with-header' )
<div class="bg-white rounded shadow p-6">
    <!-- Content with header but no title -->
</div>
@endsection
```

#### With Header and Title
```php
@section( 'layout.dashboard.with-title' )
<div class="space-y-6">
    <div class="bg-white rounded shadow p-6">
        <!-- Content section 1 -->
    </div>
    <div class="bg-white rounded shadow p-6">
        <!-- Content section 2 -->
    </div>
</div>
@endsection
```

## Specialized Page Patterns

### 1. CRUD Table Pages

**Pattern:** For displaying data tables with CRUD operations:

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @include( 'common.dashboard.title' )
        <ns-crud 
            src="{{ $src }}" 
            :query-params='@json( $queryParams ?? [] )'
            create-url="{{ $createUrl ?? '#' }}">
            <template v-slot:bulk-label>{{ $bulkLabel ?? __( 'Bulk Actions' ) }}</template>
        </ns-crud>
    </div>
</div>
@endsection
```

### 2. CRUD Form Pages

**Pattern:** For create/edit forms:

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col flex-auto">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <ns-crud-form 
            submit-method="{{ $submitMethod ?? 'POST' }}"
            submit-url="{{ $submitUrl }}"
            src="{{ $src }}">
            <template v-slot:title>{{ $mainFieldLabel ?? __( 'Field Label' ) }}</template>
            <template v-slot:save>{{ $saveButton ?? __( 'Save' ) }}</template>
        </ns-crud-form>
    </div>
</div>
@endsection
```

### 3. Settings Pages

**Pattern:** For configuration pages:

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
            @include( 'common.dashboard.title' )
        </div>
        <div>
            <ns-settings url="{{ ns()->url( '/api/settings/ns.general' ) }}">
            </ns-settings>
        </div>
    </div>
</div>
@endsection
```

## Footer Management

### Adding Footer Content

#### 1. Layout Footer Section

```php
@section( 'layout.dashboard.footer' )
    @parent
    <script>
        // Custom footer scripts
    </script>
@endsection
```

#### 2. Footer Injection

```php
@section( 'layout.dashboard.footer.inject' )
    @vite([ 'resources/ts/custom-scripts.ts' ])
@endsection
```

## Hook System Integration

### Header Customization

The header can be customized using the hook system:

```php
// In a service provider or module
Hook::addFilter( 'ns-dashboard-header-file', function( $file ) {
    return 'custom.dashboard-header';
});
```

### Custom Header File

Create `resources/views/custom/dashboard-header.blade.php`:

```php
<div id="dashboard-header" class="w-full flex justify-between p-4">
    <!-- Custom header content -->
    <div class="flex items-center">
        <!-- Left side content -->
    </div>
    <div class="flex items-center">
        <!-- Right side content -->
    </div>
</div>
```

## Best Practices

### 1. Layout Selection

| Use Case | Recommended Layout |
|----------|-------------------|
| Dashboard pages with navigation | `layout.dashboard` |
| Full-width dashboard pages | `layout.dashboard-blank` |
| Public/auth pages | `layout.default` |
| Minimal pages | `layout.base` |

### 2. Section Selection

| Content Type | Recommended Section |
|--------------|-------------------|
| Custom layouts | `layout.dashboard.body` |
| Standard pages | `layout.dashboard.with-title` |
| Pages without title | `layout.dashboard.with-header` |
| Simple content | Use built-in shortcuts |

### 3. Title and Description

- Always provide meaningful titles
- Use localization for titles: `__( 'Page Title' )`
- Provide descriptions for complex pages
- Keep titles concise and descriptive

### 4. Responsive Design

- Use Tailwind CSS classes for responsive design
- Test layouts on different screen sizes
- Consider mobile navigation behavior
- Use appropriate spacing and padding

### 5. Performance Considerations

- Use `@parent` when extending footer sections
- Minimize custom JavaScript in views
- Use Vite for asset management
- Leverage Laravel's view caching

## Common Layout Examples

### Example 1: Simple Settings Page

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
<div class="bg-white rounded shadow p-6">
    <ns-settings url="{{ ns()->url( '/api/settings/custom' ) }}"></ns-settings>
</div>
@endsection
```

### Example 2: Custom Dashboard Page

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @include( 'common.dashboard.title' )
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded shadow p-6">
                <!-- Widget 1 -->
            </div>
            <div class="bg-white rounded shadow p-6">
                <!-- Widget 2 -->
            </div>
            <div class="bg-white rounded shadow p-6">
                <!-- Widget 3 -->
            </div>
        </div>
    </div>
</div>
@endsection
```

### Example 3: Full-Height Form Page

```php
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <div class="flex-auto bg-white rounded shadow p-6">
            <!-- Full-height content -->
        </div>
    </div>
</div>
@endsection
```

## Module Integration

### Module Layout Structure

When creating modules, follow the same layout patterns:

```
modules/YourModule/
└── Resources/
    └── Views/
        ├── layouts/
        │   └── module.blade.php
        └── pages/
            ├── dashboard/
            │   ├── index.blade.php
            │   └── create.blade.php
            └── public/
                └── welcome.blade.php
```

### Module Layout Example

```php
{{-- modules/YourModule/Resources/Views/pages/dashboard/index.blade.php --}}
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
<div class="space-y-6">
    <div class="bg-white rounded shadow p-6">
        <!-- Module content -->
    </div>
</div>
@endsection
```

This layout system provides flexibility while maintaining consistency across the NexoPOS application, allowing developers to create professional-looking pages with minimal effort.
