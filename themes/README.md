# NexoPOS Themes

This directory contains themes for NexoPOS. Themes allow you to customize the frontend appearance and functionality of your store.

## Theme Structure

Each theme must follow this directory structure:

```
themes/
└── YourTheme/
    ├── config.xml          # Required: Theme configuration
    ├── preview.png/.jpg    # Optional: Preview image (shown in dashboard)
    ├── Public/            # Assets directory (will be symlinked)
    │   ├── css/
    │   ├── js/
    │   └── images/
    ├── Views/             # Blade templates
    │   ├── blog.blade.php
    │   ├── blog-single.blade.php
    │   ├── page.blade.php
    │   ├── store.blade.php
    │   ├── product.blade.php
    │   ├── cart.blade.php
    │   ├── checkout.blade.php
    │   ├── search.blade.php
    │   ├── 404.blade.php
    │   ├── login.blade.php (optional)
    │   └── register.blade.php (optional)
    └── ThemeModule.php    # Optional: Main theme class for hooks
```

## config.xml Structure

Every theme must have a `config.xml` file at its root:

```xml
<?xml version="1.0"?>
<theme>
    <namespace>YourTheme</namespace>
    <version>1.0.0</version>
    <name>Your Theme Name</name>
    <author>Your Name</author>
    <description>
        <locale lang="en">English description</locale>
        <locale lang="fr">French description</locale>
    </description>
    <core min-version="6.0.0" max-version="7.0.0" />
    <features>
        <item name="Blog" identifier="blog" />
        <item name="Pages" identifier="pages" />
        <item name="Store" identifier="store" />
        <item name="Header Menu" identifier="header-menu" />
        <item name="Footer Menu" identifier="footer-menu" />
    </features>
</theme>
```

### Required Fields
- `namespace`: Unique identifier (PascalCase, no spaces)
- `version`: Semantic version (e.g., 1.0.0)
- `name`: Display name
- `author`: Theme author
- `description`: Multi-language support with `<locale>` tags

### Optional Fields
- `core`: Version compatibility (min-version, max-version attributes)
- `features`: Declare theme capabilities

## Theme Features

### Available Features
- `blog`: Blog support (requires blog.blade.php, blog-single.blade.php)
- `pages`: Static pages (requires page.blade.php)
- `store`: E-commerce store (requires store.blade.php, product.blade.php, cart.blade.php, checkout.blade.php)
- `header-menu`: Custom header menu
- `footer-menu`: Custom footer menu

### Required Templates by Feature

**Blog Feature:**
- `blog.blade.php` - Blog listing page
- `blog-single.blade.php` - Single blog post
- `search.blade.php` - Search results

**Pages Feature:**
- `page.blade.php` - Generic page template

**Store Feature:**
- `store.blade.php` - Product listing
- `product.blade.php` - Single product page
- `cart.blade.php` - Shopping cart
- `checkout.blade.php` - Checkout page

**Common Templates:**
- `404.blade.php` - 404 error page
- `login.blade.php` - Custom login (optional)
- `register.blade.php` - Custom registration (optional)

## Installation

### Via Dashboard
1. Navigate to `/dashboard/themes`
2. Click "Upload"
3. Select your theme .zip file
4. Click "Upload"
5. Enable the theme from the themes list

### Via CLI
```bash
# Copy theme to themes directory
cp -r YourTheme/ /path/to/nexopos/themes/

# Create symlinks
php artisan themes:symlink YourTheme

# Enable theme
php artisan themes:enable YourTheme
```

## Asset Management

When a theme is enabled:
1. A symbolic link is created: `public/themes/yourtheme` → `themes/YourTheme/Public`
2. Assets are accessible at: `/themes/yourtheme/css/theme.css`
3. Symlinks work on both Windows and Unix systems

## Theme Development

### Accessing Theme Assets
```blade
<link rel="stylesheet" href="/themes/yourtheme/css/theme.css">
<script src="/themes/yourtheme/js/theme.js"></script>
<img src="/themes/yourtheme/images/logo.png">
```

### Using NexoPOS Data
Themes receive data through controller variables:

**Blog Template:**
```blade
@foreach($posts as $post)
    <h2>{{ $post->title }}</h2>
    <p>{{ $post->content }}</p>
@endforeach
```

**Page Template:**
```blade
<h1>{{ $page->title }}</h1>
<div>{!! $content !!}</div>
```

**Store Template:**
```blade
@foreach($products as $product)
    <h3>{{ $product->name }}</h3>
    <p>${{ $product->price }}</p>
@endforeach
```

### Hooks and Events
Create a `ThemeModule.php` to hook into NexoPOS:

```php
<?php

namespace Themes\YourTheme;

class ThemeModule
{
    public function __construct()
    {
        // Listen to events
        Event::listen(MenuRenderingEvent::class, function($event) {
            // Modify menu before rendering
        });
    }
}
```

## Best Practices

1. **Use Semantic Versioning**: Follow semver (major.minor.patch)
2. **Responsive Design**: Ensure mobile compatibility
3. **Accessibility**: Follow WCAG guidelines
4. **Performance**: Optimize images and minimize CSS/JS
5. **Security**: Sanitize all user inputs
6. **Documentation**: Include README with installation instructions
7. **Preview Image**: Include a preview.png (1200x800px recommended)

## Example Theme

See `DefaultTheme/` for a complete working example with:
- Clean, modern design
- All required templates
- Responsive layout
- Proper asset organization

## Troubleshooting

### Theme Not Appearing
- Check config.xml syntax
- Verify namespace matches directory name (case-sensitive)
- Ensure all required templates exist

### Assets Not Loading
```bash
# Recreate symlinks
php artisan themes:symlink YourTheme

# Check symlink exists
ls -la public/themes/
```

### Version Compatibility Error
Update the `<core>` tag in config.xml:
```xml
<core min-version="6.0.0" max-version="7.0.0" />
```

## CLI Commands

```bash
# List all themes
php artisan themes:list

# Enable a theme
php artisan themes:enable ThemeName

# Disable a theme
php artisan themes:disable ThemeName

# Create symlinks
php artisan themes:symlink ThemeName

# Create symlinks for all themes
php artisan themes:symlink
```

## Support

For theme development support, visit:
- Documentation: [NexoPOS Docs](https://my.nexopos.com/en/documentation)
- Community: [NexoPOS Forum](https://my.nexopos.com/en/forum)
- GitHub: [NexoPOS Repository](https://github.com/Blair2004/NexoPOS)

## License

Themes inherit the license of NexoPOS unless otherwise specified in the theme's license file.
