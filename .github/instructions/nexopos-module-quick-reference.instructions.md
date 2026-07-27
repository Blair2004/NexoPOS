# NexoPOS Module Quick Reference

## Asset Loading (Critical!)

### ✅ Correct Way - Use @moduleViteAssets

```blade
@moduleViteAssets('Resources/ts/main.ts', 'ModuleName')
@moduleViteAssets('Resources/css/style.css', 'ModuleName')
```

### ❌ Wrong Ways - Do NOT Use

```blade
@vite(['modules/ModuleName/Resources/ts/main.ts'])  ❌
@moduleViteAssets('/Resources/ts/main.ts', 'ModuleName')  ❌ (no leading slash)
@moduleViteAssets('modules/ModuleName/Resources/ts/main.ts', 'ModuleName')  ❌ (no full path)
```

## Quick Setup Commands

```bash
# Create module structure
mkdir -p modules/YourModule/{Http/Controllers,Resources/{Views,ts,css},Routes,Providers,Listeners}

# Install dependencies
cd modules/YourModule
npm install

# Build assets
npm run build

# Development with hot-reload
npm run dev

# Clear Laravel caches
php artisan cache:clear && php artisan view:clear && php artisan config:clear
```

## Essential Files Checklist

- [ ] `config.xml` - Module metadata
- [ ] `YourModuleModule.php` - Main class
- [ ] `package.json` - NPM dependencies
- [ ] `vite.config.js` - Vite configuration
- [ ] `tsconfig.json` - TypeScript config
- [ ] `Providers/YourModuleServiceProvider.php` - Service provider
- [ ] `Routes/api.php` - API routes
- [ ] `Routes/web.php` - Web routes (optional)
- [ ] `Resources/Views/*.blade.php` - Blade templates
- [ ] `Resources/ts/main.ts` - TypeScript entry
- [ ] `Resources/ts/types.d.ts` - Type declarations
- [ ] `Resources/css/style.css` - Styles

## config.xml Template

```xml
<?xml version="1.0" encoding="UTF-8"?>
<module>
    <namespace>YourModule</namespace>
    <version>1.0.0</version>
    <author>Your Name</author>
    <name>Your Module Name</name>
    <description>Module description</description>
</module>
```

## Service Provider Template

```php
<?php
namespace Modules\YourModule\Providers;

use Illuminate\Support\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ...
    }

    public function boot(): void
    {
        // ...
    }
}
```

## Blade View Template

```blade
@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    <div class="px-4">
        <h3>{{ __('My Module') }}</h3>
    </div>
</div>
@endsection

@section('layout.dashboard.footer.inject')
    @moduleViteAssets('Resources/ts/main.ts', 'YourModule')
    @moduleViteAssets('Resources/css/style.css', 'YourModule')
    @parent
@endsection
```

## TypeScript main.ts Template

```typescript
import { Popup } from '@/libraries/popup';
import MyComponent from './components/MyComponent.vue';

// Export globally for Blade templates
(window as any).yourModule = {
    showComponent: () => {
        Popup.show(MyComponent);
    }
};

export { MyComponent };
```

## TypeScript types.d.ts Template

```typescript
declare module '@/libraries/lang' {
    export function __(key: string): string;
    export function __m(key: string, namespace: string): string;
}

declare module '@/bootstrap' {
    export const nsHttpClient: any;
    export const nsSnackBar: any;
}

declare module '@/libraries/popup' {
    export class Popup {
        static show(component: any, params?: any, config?: any): any;
    }
}

declare const nsAlertPopup: any;
declare const nsConfirmPopup: any;
declare const nsPromptPopup: any;
```

## vite.config.js Template

Always use the shared NexoPOS Vue runtime so the module does not ship a second Vue copy. Prefer real `.vue` SFCs and `import { ref } from 'vue'`.

```javascript
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineNexoPOSModuleConfig } from '../../resources/vite-nexopos-module.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineNexoPOSModuleConfig({
    dirname: __dirname,
    inputs: [
        'Resources/css/style.css',
        'Resources/ts/main.ts',
    ],
    port: 3335,
});
```

### Dashboard Vue mount (required for `#dashboard-content`)

Core mounts `nsDashboardContent = createApp({})` on `#dashboard-content` and uses that node’s HTML as the template. **Do not** nest `createApp()` / `nsCreateApp().mount()` inside it — the UI will paint but **reactivity and clicks die**.

**Dashboard page entry** (`Resources/ts/main.ts`, footer inject before `app-init`):

```typescript
import Page from './components/Page.vue';

declare const nsExtraComponents: Record<string, unknown>;

nsExtraComponents['example-module-page'] = Page;
```

```blade
<div id="dashboard-content" class="…">
    <example-module-page></example-module-page>
</div>
```

### Standalone page only (no `#dashboard-content`)

```typescript
import { nsCreateApp } from 'vue';
import Page from './components/Page.vue';

nsCreateApp(Page).mount('#example-module-app');
```

### Module Tailwind (required prefix)

```css
/* Resources/css/style.css — pick a short stable prefix from the module name */
@import "tailwindcss" prefix(foo);
```

Markup: prefix **first**, then variants, then utility:

```html
<div class="foo:flex foo:md:grid-cols-2 foo:hover:underline">
  <!-- if using dark + sm: foo:dark:sm:bg-box-background -->
</div>
```

Do not prefix core hooks (`ns-button`, `ns-box`). Prefer semantic tokens (`foo:bg-box-background`, `foo:text-fontcolor`). Bridge those roles in `@theme` so they compile under the prefix.

**Buttons:** use `<ns-button type="info">` or a `.ns-button.info` **wrapper** with an inner `button`/`a` (module owns padding). Never `<a class="ns-button info">`.

**Typography:** `foo:text-fontcolor` for titles/body; `foo:text-fontcolor-soft` for sublines/descriptions.

**Loading:** sized `<ns-spinner size="12" border="4" />`; optional label **below**; on error, replace spinner with a message + retry.

**Active fills:** `bg-*-secondary` + `text-white` — never `bg-*-primary` as a solid active background.

Agent detail: `.agents/skills/create-nexopos-module/references/dashboard-vue-mounting.md`

## API Controller Template

```php
<?php
namespace Modules\YourModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class YourController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => []
        ]);
    }
}
```

## API Routes Template

Route should not be prefixed with "api" as NexoPOS already prefix all api.php files routes with "api".

```php
<?php
use Illuminate\Support\Facades\Route;
use Modules\YourModule\Http\Controllers\YourController;

Route::prefix('your-module')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/data', [YourController::class, 'index']);
    Route::post('/data', [YourController::class, 'store']);
});
```

## Event Listener Template

```php
<?php
namespace Modules\YourModule\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterListener
{
    public function handle(RenderFooterEvent $event): void
    {
        if ($event->routeName === 'ns.dashboard.home') {
            $event->output->addView('YourModule::view-name');
        }
    }
}
```

## Common NexoPOS APIs

### HTTP Client
```typescript
nsHttpClient.get('/api/endpoint').subscribe({
    next: (response) => { /* success */ },
    error: (error) => { /* error */ }
});

nsHttpClient.post('/api/endpoint', data).subscribe({ /* ... */ });
```

### Snackbar Notifications
```typescript
nsSnackBar.success('Success message');
nsSnackBar.error('Error message');
nsSnackBar.info('Info message');
```

### Popup System
```typescript
import { Popup } from '@/libraries/popup';

Popup.show(nsAlertPopup, {
    title: 'Alert',
    message: 'Message text'
});

Popup.show(nsConfirmPopup, {
    title: 'Confirm',
    message: 'Are you sure?',
    onAction: (confirmed) => { /* handle */ }
});
```

### Translations
```typescript
import { __ } from '@/libraries/lang';

__('Text to translate')
__m('Text to translate', 'ModuleNamespace')
```

## Troubleshooting Checklist

- [ ] Built assets: `npm run build`
- [ ] Cleared caches: `php artisan cache:clear && php artisan view:clear`
- [ ] Correct directive: `@moduleViteAssets` (not `@vite`)
- [ ] Correct namespace in config.xml
- [ ] Module namespace matches everywhere
- [ ] Public/build directory exists with manifest.json
- [ ] Browser console checked for errors
- [ ] Service provider registered (auto-discovered)

## File Permissions

```bash
# Set correct permissions
find modules/YourModule -type d -exec chmod 755 {} \;
find modules/YourModule -type f -exec chmod 644 {} \;
```

## Testing Assets Load

1. Visit your module page
2. Open browser DevTools (F12)
3. Check Network tab for asset requests
4. Look for 404 errors
5. Check Console tab for JavaScript errors
6. Verify assets load from `modules/YourModule/Public/build/assets/`

---

**Remember:** Always use `@moduleViteAssets` for loading module assets!
