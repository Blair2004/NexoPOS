---
applyTo: '**'
---

# NexoPOS Color System Guide

This document explains the NexoPOS color system, which uses CSS custom properties for theming across dark, light, and phosphor themes. Understanding this system is essential for creating modules that integrate seamlessly with NexoPOS's visual design.

## Overview

NexoPOS uses CSS custom properties (CSS variables) defined in theme-specific files:
- `resources/css/dark/_colors.css` - Dark theme (default)
- `resources/css/light/_colors.css` - Light theme
- `resources/css/phosphor/_colors.css` - Phosphor theme

These variables provide semantic color names that automatically adapt to the current theme without requiring theme-specific code.

## Color Organization

Colors are organized into semantic categories based on their usage context:

### 1. Typography Colors

Used for text content throughout the application:

```css
--color-typography       /* Primary body text */
--color-fontcolor        /* Standard text (default gray-300 dark, gray-700 light) */
--color-fontcolor-soft   /* Muted/secondary text (gray-400 dark, gray-500 light) */
--color-fontcolor-hard   /* Emphasized/strong text (gray-100 dark, gray-800 light) */
```

**Usage Pattern:**
- `fontcolor` - Body text, labels, general content
- `fontcolor-soft` - Hints, descriptions, metadata
- `fontcolor-hard` - Headings, important text, emphasis

**Example:**
```vue
<template>
    <div>
        <h2 class="text-fontcolor-hard text-2xl font-bold">{{ title }}</h2>
        <p class="text-fontcolor">{{ description }}</p>
        <span class="text-fontcolor-soft text-sm">{{ metadata }}</span>
    </div>
</template>
```

### 2. Surface Colors

Define background colors for major UI areas:

```css
--color-surface              /* Main application background */
--color-surface-soft         /* Lighter surface variant */
--color-surface-hard         /* Darker surface variant */
--color-aside-background     /* Sidebar background */
--color-aside-color          /* Sidebar text color */
--color-popup-surface        /* Popup/modal background */
```

**Usage Pattern:**
- Use `surface` for main content areas
- Use `popup-surface` for modals and popups
- Use `aside-background` for navigation sidebars

**Example:**
```vue
<template>
    <div class="bg-popup-surface text-fontcolor">
        <!-- Popup content automatically themed -->
    </div>
</template>
```

### 3. Box Colors

Colors for card-like containers and elevated surfaces:

```css
--color-box-background         /* Box/card background */
--color-box-edge               /* Box/card border */
--color-box-elevation-background  /* Elevated box background */
--color-box-elevation-edge     /* Elevated box border */
--color-box-elevation-hover    /* Elevated box hover state */
```

**Color Pairing:**
Always pair `box-background` with `box-edge` for consistent appearance:

```vue
<template>
    <!-- ✅ Correct - Paired colors -->
    <div class="bg-box-background border border-box-edge rounded-lg p-4">
        <h3>Card Content</h3>
    </div>

    <!-- ❌ Wrong - Mismatched colors -->
    <div class="bg-box-background border border-gray-300">
        <h3>Card Content</h3>
    </div>
</template>
```

**Elevation Pattern:**
Use elevation variants for stacked or floating elements:

```vue
<template>
    <div class="bg-box-elevation-background border border-box-elevation-edge shadow-lg">
        <!-- Elevated content appears above other boxes -->
    </div>
</template>
```

### 4. Input Colors

Colors for form inputs and interactive fields:

```css
--color-input-background       /* Input field background */
--color-input-edge             /* Input field border */
--color-input-disabled         /* Disabled input background */
--color-input-button           /* Input button background */
--color-input-button-hover     /* Input button hover state */
--color-input-button-active    /* Input button active state */
--color-input-option-hover     /* Select option hover */
```

**Usage Pattern:**
```vue
<template>
    <input 
        type="text"
        class="bg-input-background border border-input-edge text-fontcolor"
        :disabled="isDisabled"
        :class="{ 'bg-input-disabled': isDisabled }"
    />
</template>
```

### 5. Semantic Colors

State-based colors for feedback and actions:

#### Primary (Base Accent)
```css
--color-primary      /* Main brand color */
--color-secondary    /* Secondary brand color */
--color-tertiary     /* Tertiary brand color */
```

#### Error States
```css
--color-error-primary     /* Main error color (lighter) */
--color-error-secondary   /* Medium error color */
--color-error-tertiary    /* Dark error color */
```

#### Info States
```css
--color-info-primary      /* Main info color (lighter) */
--color-info-secondary    /* Medium info color */
--color-info-tertiary     /* Dark info color */
```

#### Success States
```css
--color-success-primary   /* Main success color (lighter) */
--color-success-secondary /* Medium success color */
--color-success-tertiary  /* Dark success color */
```

#### Warning States
```css
--color-warning-primary   /* Main warning color (lighter) */
--color-warning-secondary /* Medium warning color */
--color-warning-tertiary  /* Dark warning color */
```

**Semantic Usage Pattern:**
- `primary` - Backgrounds, less prominent elements
- `secondary` - Borders, medium prominence
- `tertiary` - Text, high contrast elements

**Example:**
```vue
<template>
    <!-- Success button -->
    <button class="bg-success-primary border border-success-secondary text-success-tertiary">
        Save
    </button>

    <!-- Error message -->
    <div class="bg-error-primary border border-error-secondary text-error-tertiary p-3 rounded">
        {{ errorMessage }}
    </div>

    <!-- Info badge -->
    <span class="bg-info-primary text-info-tertiary px-2 py-1 rounded text-sm">
        New
    </span>
</template>
```

### 6. Component-Specific Colors

Colors optimized for specific UI components:

#### Tabs
```css
--color-tab-active           /* Active tab background */
--color-tab-active-border    /* Active tab border */
--color-tab-inactive         /* Inactive tab background */
--color-tab-table-th         /* Tab table header background */
--color-tab-table-th-edge    /* Tab table header border */
```

#### Tables
```css
--color-table-th       /* Table header background */
--color-table-th-edge  /* Table header border */
```

#### CRUD Components
```css
--color-crud-button-edge       /* CRUD button border */
--color-crud-input-background  /* CRUD input background */
```

#### POS (Point of Sale)
```css
--color-pos-button-edge  /* POS button border */
```

#### Numpad
```css
--color-numpad-background   /* Numpad background */
--color-numpad-typography   /* Numpad text color */
--color-numpad-edge         /* Numpad border */
--color-numpad-hover        /* Numpad button hover */
--color-numpad-hover-edge   /* Numpad hover border */
```

#### Floating Menus
```css
--color-floating-menu          /* Menu background */
--color-floating-menu-hover    /* Menu item hover */
--color-floating-menu-selected /* Selected menu item */
--color-floating-menu-edge     /* Menu border */
```

#### Scrollbars
```css
--color-scroll-thumb        /* Scrollbar thumb */
--color-scroll-track        /* Scrollbar track */
--color-scroll-popup-thumb  /* Popup scrollbar thumb */
```

## Usage Guidelines

### 1. Always Use Semantic Names

**✅ Correct:**
```vue
<div class="bg-box-background border border-box-edge">
```

**❌ Wrong:**
```vue
<div class="bg-gray-800 border border-gray-700">
```

### 2. Pair Related Colors

Many colors are designed to work together. Always pair them:

**Pairings:**
- `box-background` + `box-edge`
- `box-elevation-background` + `box-elevation-edge`
- `input-background` + `input-edge`
- `tab-active` + `tab-active-border`
- `table-th` + `table-th-edge`

**Example:**
```vue
<template>
    <!-- ✅ Correct pairing -->
    <div class="bg-box-background border border-box-edge">
        <input class="bg-input-background border border-input-edge" />
    </div>

    <!-- ❌ Mixed pairings -->
    <div class="bg-box-background border border-input-edge">
        <input class="bg-input-background border border-box-edge" />
    </div>
</template>
```

### 3. Use Typography Hierarchy

Choose the appropriate text color based on importance:

```vue
<template>
    <div>
        <!-- ✅ Correct hierarchy -->
        <h1 class="text-fontcolor-hard">Most Important</h1>
        <p class="text-fontcolor">Normal text</p>
        <span class="text-fontcolor-soft">Less important</span>

        <!-- ❌ Wrong hierarchy -->
        <h1 class="text-fontcolor-soft">Title</h1>
        <span class="text-fontcolor-hard">Metadata</span>
    </div>
</template>
```

### 4. Semantic Color Progression

Use primary → secondary → tertiary for visual depth:

```vue
<template>
    <!-- ✅ Correct progression -->
    <button class="bg-success-primary hover:bg-success-secondary border border-success-secondary text-success-tertiary">
        Submit
    </button>

    <!-- ❌ Wrong progression -->
    <button class="bg-success-tertiary text-success-primary">
        Submit
    </button>
</template>
```

### 5. Respect Component Context

Use component-specific colors for their intended purpose:

```vue
<template>
    <!-- ✅ Correct - Using tab colors for tabs -->
    <div class="bg-tab-active border-b-2 border-tab-active-border">
        Active Tab
    </div>

    <!-- ❌ Wrong - Using tab colors for general content -->
    <div class="bg-tab-active p-4">
        This isn't a tab
    </div>
</template>
```

## Theme Comparison

Here's how the same semantic colors differ across themes:

| Color Variable | Dark Theme | Light Theme | Phosphor Theme |
|----------------|------------|-------------|----------------|
| `fontcolor` | gray-300 | gray-700 | gray-300 |
| `fontcolor-soft` | gray-400 | gray-500 | gray-400 |
| `surface` | #171717 | #FFF | #1a1a17 |
| `box-background` | #333 | #FFF | #433e37 |
| `box-edge` | #222 | #e5e7eb | #222 |
| `input-background` | #333 | #FFF | #333 |
| `primary` | #4F46E5 (indigo) | #4F46E5 (indigo) | #9F9F5E (olive) |

**Key Insight:** The same class names produce appropriate colors for each theme automatically.

## Module Development Best Practices

### 1. Never Hardcode Colors

**✅ Do:**
```vue
<template>
    <div class="bg-box-background border border-box-edge text-fontcolor">
        {{ content }}
    </div>
</template>
```

**❌ Don't:**
```vue
<template>
    <div class="bg-gray-800 border border-gray-700 text-gray-300">
        {{ content }}
    </div>
</template>
```

### 2. Test Across Themes

When developing modules, test your UI in all three themes:
- Dark (default)
- Light
- Phosphor

### 3. Use Hover States Consistently

```vue
<template>
    <button class="bg-primary hover:bg-secondary border border-secondary">
        Click Me
    </button>
</template>
```

### 4. Respect Elevation Layers

```vue
<template>
    <!-- Base layer -->
    <div class="bg-surface">
        <!-- Elevated layer -->
        <div class="bg-box-background border border-box-edge">
            <!-- More elevated layer -->
            <div class="bg-box-elevation-background border border-box-elevation-edge">
                Content
            </div>
        </div>
    </div>
</template>
```

## Common Patterns

### Card/Box Pattern
```vue
<template>
    <div class="bg-box-background border border-box-edge rounded-lg p-4 shadow">
        <h3 class="text-fontcolor-hard text-lg font-semibold mb-2">Title</h3>
        <p class="text-fontcolor">Content</p>
        <span class="text-fontcolor-soft text-sm">Metadata</span>
    </div>
</template>
```

### Form Input Pattern
```vue
<template>
    <div>
        <label class="text-fontcolor font-medium mb-2 block">
            Label
        </label>
        <input 
            type="text"
            class="bg-input-background border border-input-edge text-fontcolor rounded px-3 py-2 w-full focus:ring-2 focus:ring-primary"
        />
        <p class="text-fontcolor-soft text-sm mt-1">Helper text</p>
    </div>
</template>
```

### Button Pattern
```vue
<template>
    <button class="bg-primary hover:bg-secondary text-white border border-secondary px-4 py-2 rounded transition-colors">
        Primary Action
    </button>
</template>
```

### Alert Pattern
```vue
<template>
    <div>
        <!-- Error Alert -->
        <div class="bg-error-primary border border-error-secondary text-error-tertiary p-3 rounded mb-2">
            Error message
        </div>

        <!-- Success Alert -->
        <div class="bg-success-primary border border-success-secondary text-success-tertiary p-3 rounded mb-2">
            Success message
        </div>

        <!-- Info Alert -->
        <div class="bg-info-primary border border-info-secondary text-info-tertiary p-3 rounded mb-2">
            Info message
        </div>

        <!-- Warning Alert -->
        <div class="bg-warning-primary border border-warning-secondary text-warning-tertiary p-3 rounded">
            Warning message
        </div>
    </div>
</template>
```

### Table Pattern
```vue
<template>
    <table class="w-full">
        <thead class="bg-table-th border-b border-table-th-edge">
            <tr>
                <th class="text-fontcolor-hard text-left px-4 py-2">Column</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-box-edge">
                <td class="text-fontcolor px-4 py-2">Data</td>
            </tr>
        </tbody>
    </table>
</template>
```

### Modal/Popup Pattern
```vue
<template>
    <div class="bg-popup-surface border border-box-edge rounded-lg shadow-xl">
        <div class="border-b border-box-edge px-4 py-3">
            <h3 class="text-fontcolor-hard text-xl font-semibold">Title</h3>
        </div>
        <div class="p-4">
            <p class="text-fontcolor">Content</p>
        </div>
        <div class="border-t border-box-edge px-4 py-3 flex justify-end gap-2">
            <button class="bg-default-primary hover:bg-default-secondary text-default-tertiary px-4 py-2 rounded">
                Cancel
            </button>
            <button class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded">
                Confirm
            </button>
        </div>
    </div>
</template>
```

## Troubleshooting

### Colors Not Applying

**Issue:** Colors appear as plain Tailwind defaults instead of themed colors.

**Solution:** Ensure you're using the exact CSS custom property names:
```vue
<!-- ✅ Correct -->
<div class="bg-box-background">

<!-- ❌ Wrong (typo) -->
<div class="bg-box-bg">
```

### Colors Look Wrong in Specific Theme

**Issue:** Colors look good in dark theme but wrong in light theme.

**Solution:** You're likely hardcoding colors. Switch to semantic color names:
```vue
<!-- ✅ Theme-aware -->
<div class="bg-surface text-fontcolor">

<!-- ❌ Dark theme only -->
<div class="bg-gray-900 text-gray-300">
```

### Low Contrast

**Issue:** Text is hard to read against background.

**Solution:** Use proper color pairings and text hierarchy:
```vue
<!-- ✅ High contrast -->
<div class="bg-box-background">
    <h3 class="text-fontcolor-hard">Title</h3>
    <p class="text-fontcolor">Body</p>
</div>

<!-- ❌ Low contrast -->
<div class="bg-box-background">
    <h3 class="text-fontcolor-soft">Title</h3>
    <p class="text-fontcolor-soft">Body</p>
</div>
```

## Summary

**Key Principles:**
1. **Always use semantic color names** - Never hardcode Tailwind colors
2. **Pair related colors** - box-background + box-edge, input-background + input-edge
3. **Respect typography hierarchy** - fontcolor-hard > fontcolor > fontcolor-soft
4. **Follow semantic progression** - primary > secondary > tertiary
5. **Use component-specific colors** - tab colors for tabs, table colors for tables
6. **Test across all themes** - Your module should work in dark, light, and phosphor

By following these guidelines, your module will integrate seamlessly with NexoPOS's theming system and provide a consistent user experience across all themes.
