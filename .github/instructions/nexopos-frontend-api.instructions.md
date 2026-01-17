---
applyTo: '**'
---

# NexoPOS Frontend API and Global Objects Guide

This document describes the global JavaScript/TypeScript objects and APIs available in NexoPOS frontend code, particularly in Vue components and TypeScript files.

## Global Window Object Structure

NexoPOS exposes several objects on the `window` global scope that are accessible throughout the application.

### window.ns

The main NexoPOS namespace object containing core system information.

```typescript
interface Window {
    ns: {
        language: string;        // Current system language (e.g., 'en', 'fr', 'es')
        // ... other properties
    };
}
```

#### window.ns.language

**Type**: `string`  
**Description**: The current language code being used by the NexoPOS system.  
**Common Values**: `'en'`, `'fr'`, `'es'`, `'de'`, `'ar'`, `'pt'`, `'tr'`, `'km'`, `'it'`, `'nl'`, `'sq'`, `'vi'`

**Usage**:
```typescript
const currentLang = (window as any).ns?.language || 'en';
```

**Example - Handling Localized Content**:
```typescript
// Module descriptions come as localized objects
interface Module {
    name: string;
    description: {
        en: string;
        fr: string;
        es: string;
        // ... other languages
    };
}

function getLocalizedDescription(module: Module): string {
    const currentLang = (window as any).ns?.language || 'en';
    return module.description[currentLang] || module.description['en'] || '';
}
```

### window.nsHttpClient

**Type**: Observable-based HTTP client  
**Description**: RxJS-based HTTP client for making API requests  
**See**: `.github/instructions/nexopos-httpclient.instructions.md`

```typescript
declare const nsHttpClient: {
    get(url: string, config?: any): Observable<any>;
    post(url: string, data?: any, config?: any): Observable<any>;
    put(url: string, data?: any, config?: any): Observable<any>;
    delete(url: string, config?: any): Observable<any>;
};
```

### window.nsSnackBar

**Type**: Notification service  
**Description**: Display toast notifications to users

```typescript
declare const nsSnackBar: {
    success(message: string, title?: string): void;
    error(message: string, title?: string): void;
    info(message: string, title?: string): void;
};
```

**Usage**:
```typescript
nsSnackBar.success('Operation completed successfully');
nsSnackBar.error('An error occurred');
nsSnackBar.info('Processing your request...');
```

### window.Popup

**Type**: Popup management service  
**Description**: Show modal popups and dialogs  
**See**: `.github/instructions/nexopos-popup.instructions.md`

```typescript
declare const Popup: {
    show(component: any, params?: any, config?: any): PopupInstance;
};
```

### window.nsExtraComponents

**Type**: Component registry  
**Description**: Global registry for Vue components that can be used throughout the application

```typescript
declare const nsExtraComponents: {
    [key: string]: any; // Vue component definitions
};
```

**Module Usage**:
```typescript
// Register module components
import MyComponent from './components/MyComponent';

nsExtraComponents['my-module-component'] = MyComponent;
```

### Global Component Declarations

NexoPOS provides several globally available components that don't need to be imported:

```typescript
// Built-in popup components (globally available)
declare const nsAlertPopup: any;
declare const nsConfirmPopup: any;
declare const nsPromptPopup: any;
declare const nsSelectPopup: any;
declare const nsMedia: any;
declare const nsPosLoadingPopup: any;

// UI components
declare const nsCloseButton: any;
declare const nsButton: any;

// Helper functions
declare const popupCloser: any;
declare const popupResolver: any;
```

## Localization Functions

### __() - Core Localization

**Type**: `(key: string) => string`  
**Description**: Translate keys from core NexoPOS language files  
**See**: `.github/instructions/nexopos-localization.instructions.md`

```typescript
declare const __: (key: string) => string;

// Usage
const message = __('Create Product');
```

### __m() - Module Localization

**Type**: `(key: string, module: string) => string`  
**Description**: Translate keys from module-specific language files

```typescript
declare const __m: (key: string, module: string) => string;

// Usage
const message = __m('Backup Name', 'OpusBackup');
```

## Module Data Structures

### Localized Content Pattern

Many objects in NexoPOS, particularly module configurations, use localized content objects instead of plain strings.

**Pattern**:
```typescript
interface LocalizedObject {
    [languageCode: string]: string;
}

// Example
{
    en: "English description",
    fr: "Description française",
    es: "Descripción en español"
}
```

**Common Use Cases**:
- Module descriptions
- Product descriptions
- Category descriptions
- Any user-facing text that needs translation

**Handling Localized Content**:
```typescript
function getLocalizedText(
    content: string | LocalizedObject, 
    fallback: string = ''
): string {
    // If it's already a string, return it
    if (typeof content === 'string') {
        return content;
    }
    
    // If it's an object, get the current language version
    if (typeof content === 'object' && content !== null) {
        const currentLang = (window as any).ns?.language || 'en';
        return content[currentLang] 
            || content['en'] 
            || content[Object.keys(content)[0]] 
            || fallback;
    }
    
    return fallback;
}
```

**Example - Module Description**:
```typescript
interface Module {
    namespace: string;
    name: string;
    version: string;
    description: string | { [key: string]: string }; // Can be string or localized
    enabled: boolean;
}

// Usage in component
function displayModuleDescription(module: Module): string {
    const currentLang = (window as any).ns?.language || 'en';
    
    if (typeof module.description === 'string') {
        return module.description;
    }
    
    if (typeof module.description === 'object') {
        return module.description[currentLang] 
            || module.description['en'] 
            || module.description[Object.keys(module.description)[0]] 
            || 'No description';
    }
    
    return 'No description';
}
```

## TypeScript Type Declarations

### Recommended Global Type Declarations

For TypeScript modules, create a `types.d.ts` file or declare at the top of your component:

```typescript
// Window namespace
interface Window {
    ns: {
        language: string;
    };
}

// HTTP Client
declare const nsHttpClient: any;
declare const nsSnackBar: any;

// Localization
declare const __: (key: string) => string;
declare const __m: (key: string, module: string) => string;

// Vue
declare const defineComponent: any;

// Popup system
declare const Popup: any;
declare const popupCloser: any;
declare const popupResolver: any;

// Built-in popups (globally available - no import needed)
declare const nsAlertPopup: any;
declare const nsConfirmPopup: any;
declare const nsPromptPopup: any;
declare const nsSelectPopup: any;
declare const nsMedia: any;
declare const nsPosLoadingPopup: any;

// UI Components
declare const nsCloseButton: any;
declare const nsButton: any;

// Component registry
declare const nsExtraComponents: any;
```

## Best Practices

### 1. Always Handle Localized Content

When receiving data from APIs that might contain user-facing text, always assume it could be localized:

```typescript
// ❌ Bad - Assumes string
const description = module.description;

// ✅ Good - Handles both string and localized object
const description = typeof module.description === 'string' 
    ? module.description 
    : module.description[(window as any).ns?.language || 'en'] || module.description['en'];
```

### 2. Provide Fallback Language

Always fall back to English (`'en'`) if the current language is not available:

```typescript
const currentLang = (window as any).ns?.language || 'en';
const text = localizedObject[currentLang] || localizedObject['en'] || '';
```

### 3. Null-Safe Access

Always use optional chaining when accessing `window.ns`:

```typescript
// ✅ Safe
const lang = (window as any).ns?.language || 'en';

// ❌ Unsafe - could throw error
const lang = window.ns.language;
```

### 4. Type Guard for Localized Content

Create helper functions for common patterns:

```typescript
function isLocalizedObject(value: any): value is { [key: string]: string } {
    return typeof value === 'object' 
        && value !== null 
        && !Array.isArray(value);
}

function getLocalizedValue(
    value: string | { [key: string]: string }, 
    fallback: string = ''
): string {
    if (typeof value === 'string') return value;
    if (isLocalizedObject(value)) {
        const lang = (window as any).ns?.language || 'en';
        return value[lang] || value['en'] || value[Object.keys(value)[0]] || fallback;
    }
    return fallback;
}
```

## Module Integration

When creating modules that interact with NexoPOS data:

1. **Always check for localized content** in descriptions, names, or any user-facing text
2. **Use `window.ns.language`** to determine the current system language
3. **Provide English fallback** for any localized content
4. **Handle both string and object types** in interfaces where localization is possible

### Example Module Component

```typescript
import { defineComponent } from 'vue';

interface LocalizedModule {
    namespace: string;
    name: string;
    description: string | { [key: string]: string };
}

export default defineComponent({
    data() {
        return {
            modules: [] as LocalizedModule[]
        };
    },
    methods: {
        getDescription(module: LocalizedModule): string {
            if (typeof module.description === 'string') {
                return module.description;
            }
            
            const lang = (window as any).ns?.language || 'en';
            return module.description[lang] 
                || module.description['en'] 
                || Object.values(module.description)[0] 
                || 'No description';
        }
    }
});
```

## Related Documentation

- **HTTP Client**: `.github/instructions/nexopos-httpclient.instructions.md`
- **Popup System**: `.github/instructions/nexopos-popup.instructions.md`
- **Localization**: `.github/instructions/nexopos-localization.instructions.md`
- **Module Development**: `.github/instructions/nexopos-modules.instructions.md`

## Summary

- **window.ns.language** contains the current system language
- Module descriptions and other user-facing content are often **localized objects** (not strings)
- Always **handle both string and object types** when dealing with potentially localized content
- Provide **English fallback** (`'en'`) when current language is unavailable
- Use **optional chaining** (`?.`) when accessing window.ns properties
- Built-in popup components are **globally available** without imports
