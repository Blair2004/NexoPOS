---
applyTo: '**'
---

# NexoPOS Options/Settings API Guide

The NexoPOS Options API provides a centralized system for storing and retrieving application settings and configuration values. This comprehensive guide explains how to use the Options service throughout the application.

## Overview

The Options service (`app/Services/Options.php`) manages key-value pairs stored in the `nexopos_options` database table. It provides methods for reading, writing, and deleting configuration options with support for various data types including strings, integers, floats, arrays, and JSON objects.

**Service Class**: `app/Services/Options.php`  
**Database Table**: `nexopos_options`  
**Access Method**: `ns()->option` (global helper)

## Architecture

### Option Model

**Location**: `app/Models/Option.php`  
**Table**: `nexopos_options`

#### Table Structure
```php
- id: Primary key
- key: Option identifier (string, indexed)
- value: Stored value (text)
- array: Boolean flag indicating if value is JSON array
- expire_on: Optional expiration timestamp
- user_id: Nullable user association
- created_at: Timestamp
- updated_at: Timestamp
```

### Access Through CoreService

The Options service is accessible through the global `ns()` helper:

```php
ns()->option->get($key);      // Via CoreService
app(Options::class)->get($key); // Direct instantiation
```

## Core Methods

### 1. Get Option(s)

Retrieve one or more option values from storage.

#### Signature
```php
public function get(string|array|null $key = null, mixed $default = null): mixed
```

#### Single Option
```php
// Get single option with default value
$theme = ns()->option->get('ns_default_theme', 'light');

// Get store name
$storeName = ns()->option->get('ns_store_name');

// Get logo URL
$logo = ns()->option->get('ns_store_rectangle_logo');

// Get boolean option
$registrationEnabled = ns()->option->get('ns_registration_enabled') === 'yes';
```

#### Multiple Options
```php
// Get array of specific options
$options = ns()->option->get([
    'ns_store_name',
    'ns_default_theme',
    'ns_currency_symbol'
]);

// Returns: ['NexoPOS', 'light', '$']
```

#### All Options
```php
// Get all options as collection
$allOptions = ns()->option->get();

// Iterate through all options
foreach ($allOptions as $key => $option) {
    echo $option->key . ' => ' . $option->value;
}
```

#### Return Types

The `get()` method automatically decodes values based on type:

- **String values**: Returned as-is
- **Numeric values**: Cast to `int` or `float`
- **JSON arrays**: Decoded to PHP array
- **Non-existent keys**: Returns `$default` parameter

```php
// Integer option
$precision = ns()->option->get('ns_currency_precision', 2);
// Returns: (int) 2

// Array option
$orderTypes = ns()->option->get('ns_pos_order_types', []);
// Returns: ['takeaway', 'delivery']

// Missing option with default
$value = ns()->option->get('non_existent_key', 'fallback');
// Returns: 'fallback'
```

### 2. Set Option

Create or update an option value.

#### Signature
```php
public function set(string $key, mixed $value, Carbon|null $expiration = null): void
```

#### Basic Usage
```php
// Set string value
ns()->option->set('ns_store_name', 'My Store');

// Set integer
ns()->option->set('ns_currency_precision', 2);

// Set float
ns()->option->set('ns_default_tax_rate', 15.5);

// Set boolean (stored as string)
ns()->option->set('ns_registration_enabled', 'yes');
ns()->option->set('ns_registration_enabled', 'no');
```

#### Array Values
```php
// Set array - automatically JSON encoded
$orderTypes = ['takeaway', 'delivery', 'dine-in'];
ns()->option->set('ns_pos_order_types', $orderTypes);

// Set associative array
$config = [
    'enabled' => true,
    'max_retries' => 3,
    'timeout' => 30
];
ns()->option->set('api_config', $config);
```

#### With Expiration
```php
use Carbon\Carbon;

// Cache for 1 hour
ns()->option->set('temp_token', $token, now()->addHour());

// Cache for 24 hours
ns()->option->set('daily_stats', $stats, now()->addDay());

// Cache for 1 week
ns()->option->set('weekly_report', $data, Carbon::now()->addWeek());

// Cache until specific date
ns()->option->set('promo_data', $promo, Carbon::parse('2026-12-31'));
```

### 3. Delete Option

Remove an option from storage.

#### Signature
```php
public function delete(string $key): void
```

#### Usage
```php
// Delete single option
ns()->option->delete('temporary_setting');

// Delete cache entry
ns()->option->delete('cached_products');

// Delete user preference
ns()->option->delete('_custom_option');
```

### 4. Rebuild Cache

Refresh the internal options cache from database.

#### Signature
```php
public function rebuild(): void
```

#### Usage
```php
// Rebuild options cache
ns()->option->rebuild();

// Typically used after bulk database changes
DB::table('nexopos_options')->where('key', 'like', 'temp_%')->delete();
ns()->option->rebuild();
```

### 5. Reset to Defaults

Reset all options to system defaults (destructive operation).

#### Signature
```php
public function setDefault(array $options = []): void
```

#### Usage
```php
// Reset to system defaults
ns()->option->setDefault();

// Reset with custom defaults
ns()->option->setDefault([
    'ns_store_name' => 'Custom Store',
    'ns_currency_symbol' => '€',
    'ns_currency_precision' => 2
]);
```

**Warning**: This method truncates the options table before setting defaults.

## Data Type Handling

### Automatic Type Casting

The Options service automatically handles type conversion:

```php
// Integer
ns()->option->set('count', 42);
$count = ns()->option->get('count');
// Returns: (int) 42

// Float
ns()->option->set('price', 19.99);
$price = ns()->option->get('price');
// Returns: (float) 19.99

// String
ns()->option->set('name', 'Product Name');
$name = ns()->option->get('name');
// Returns: (string) "Product Name"

// Boolean (stored as string)
ns()->option->set('enabled', 'yes');
$enabled = ns()->option->get('enabled') === 'yes';
// Returns: (bool) true

// Array (JSON encoded/decoded)
ns()->option->set('tags', ['new', 'featured', 'sale']);
$tags = ns()->option->get('tags');
// Returns: (array) ['new', 'featured', 'sale']

// Associative array
ns()->option->set('meta', ['key1' => 'value1', 'key2' => 'value2']);
$meta = ns()->option->get('meta');
// Returns: (array) ['key1' => 'value1', 'key2' => 'value2']
```

### Type Conversion Details

**Encoding (when setting):**
- Arrays → JSON string with `array` flag set to `true`
- Empty values → Empty string (unless numeric zero)
- All other types → String representation

**Decoding (when getting):**
- JSON strings with `array` flag → PHP array
- Numeric strings → Integer or Float
- All others → String

### Value Sanitization

All values are automatically sanitized using `strip_tags()` before storage:

```php
// Input with HTML tags
ns()->option->set('description', '<script>alert("xss")</script>Hello');

// Retrieved value is sanitized
$description = ns()->option->get('description');
// Returns: "Hello" (tags stripped)
```

## Usage Patterns

### 1. Configuration Values

```php
// Store configuration
ns()->option->set('ns_store_name', 'My Retail Store');
ns()->option->set('ns_store_address', '123 Main Street');
ns()->option->set('ns_store_phone', '+1234567890');
ns()->option->set('ns_store_email', 'contact@store.com');

// Retrieve configuration
$storeName = ns()->option->get('ns_store_name');
$storeAddress = ns()->option->get('ns_store_address');
```

### 2. Feature Flags

```php
// Enable/disable features
ns()->option->set('ns_registration_enabled', 'yes');
ns()->option->set('ns_recovery_enabled', 'no');
ns()->option->set('ns_pos_quick_product', 'yes');

// Check feature status
if (ns()->option->get('ns_registration_enabled') === 'yes') {
    // Allow user registration
}

if (ns()->option->get('ns_recovery_enabled') === 'yes') {
    // Show password recovery link
}
```

### 3. User Preferences

```php
// Save user preferences
ns()->option->set('ns_default_theme', 'dark');
ns()->option->set('ns_language', 'en');
ns()->option->set('ns_items_per_page', 20);

// Load user preferences
$theme = ns()->option->get('ns_default_theme', 'light');
$language = ns()->option->get('ns_language', 'en');
$itemsPerPage = ns()->option->get('ns_items_per_page', 10);
```

### 4. Application State

```php
// Store application state
ns()->option->set('last_backup_date', now()->toDateTimeString());
ns()->option->set('maintenance_mode', 'no');
ns()->option->set('app_version', '4.0.0');

// Retrieve state
$lastBackup = ns()->option->get('last_backup_date');
$maintenanceMode = ns()->option->get('maintenance_mode') === 'yes';
$appVersion = ns()->option->get('app_version');
```

### 5. Temporary Cache

```php
// Cache expensive query results
$products = Product::with('category')->get();
ns()->option->set('cached_products', $products->toArray(), now()->addHours(6));

// Retrieve from cache
$cachedProducts = ns()->option->get('cached_products');
if ($cachedProducts) {
    return $cachedProducts;
}

// Delete when no longer needed
ns()->option->delete('cached_products');
```

### 6. API Keys and Secrets

```php
// Store API credentials
ns()->option->set('stripe_public_key', env('STRIPE_PUBLIC_KEY'));
ns()->option->set('stripe_secret_key', env('STRIPE_SECRET_KEY'));
ns()->option->set('paypal_client_id', env('PAYPAL_CLIENT_ID'));

// Retrieve credentials
$stripeKey = ns()->option->get('stripe_public_key');
$paypalClientId = ns()->option->get('paypal_client_id');
```

## Usage in Different Contexts

### 1. In Controllers

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function update(Request $request)
    {
        // Update multiple settings
        ns()->option->set('ns_store_name', $request->store_name);
        ns()->option->set('ns_store_address', $request->store_address);
        ns()->option->set('ns_currency_symbol', $request->currency_symbol);
        
        return response()->json([
            'status' => 'success',
            'message' => __('Settings updated successfully')
        ]);
    }
    
    public function show()
    {
        return response()->json([
            'store_name' => ns()->option->get('ns_store_name'),
            'store_address' => ns()->option->get('ns_store_address'),
            'currency_symbol' => ns()->option->get('ns_currency_symbol')
        ]);
    }
}
```

### 2. In Blade Templates

```blade
{{-- Display store name --}}
<h1>{{ ns()->option->get('ns_store_name') }}</h1>

{{-- Display logo if exists --}}
@if(ns()->option->get('ns_store_rectangle_logo'))
    <img src="{{ ns()->option->get('ns_store_rectangle_logo') }}" alt="Store Logo">
@endif

{{-- Conditional rendering based on option --}}
@if(ns()->option->get('ns_registration_enabled') === 'yes')
    <a href="{{ route('register') }}">Register</a>
@endif

{{-- Default theme --}}
@php
    $theme = Auth::check() 
        ? Auth::user()->attribute->theme 
        : ns()->option->get('ns_default_theme', 'light');
@endphp

<body class="{{ $theme }}">
    <!-- Content -->
</body>

{{-- Pass to Vue component --}}
<div id="app" 
     data-store-name="{{ ns()->option->get('ns_store_name') }}"
     data-currency="{{ ns()->option->get('ns_currency_symbol') }}">
</div>
```

### 3. In Services

```php
namespace App\Services;

class OrderService
{
    public function calculateTax($amount)
    {
        $taxRate = ns()->option->get('ns_default_tax_rate', 0);
        $taxType = ns()->option->get('ns_pos_tax_type', 'exclusive');
        
        if ($taxType === 'inclusive') {
            return $amount * ($taxRate / (100 + $taxRate));
        }
        
        return $amount * ($taxRate / 100);
    }
    
    public function getDefaultPaymentType()
    {
        return ns()->option->get(
            'ns_pos_registers_default_change_payment_type',
            1
        );
    }
}
```

### 4. In Models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            // Get default tax settings
            $order->tax_type = ns()->option->get('ns_pos_tax_type', 'exclusive');
            $order->tax_rate = ns()->option->get('ns_default_tax_rate', 0);
        });
    }
    
    public function getPriceAttribute()
    {
        $precision = ns()->option->get('ns_currency_precision', 2);
        return number_format($this->attributes['price'], $precision);
    }
}
```

### 5. In Migrations

```php
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        // Set default options during migration
        ns()->option->set('ns_scale_enabled', 'no');
        ns()->option->set('ns_scale_barcode_product_length', 4);
        ns()->option->set('ns_scale_barcode_weight_length', 5);
    }
    
    public function down()
    {
        // Clean up options
        ns()->option->delete('ns_scale_enabled');
        ns()->option->delete('ns_scale_barcode_product_length');
        ns()->option->delete('ns_scale_barcode_weight_length');
    }
};
```

### 6. In Module Service Providers

```php
namespace Modules\MyModule\Providers;

use Illuminate\Support\ServiceProvider;

class MyModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Set module defaults
        if (ns()->option->get('mymodule_enabled') === null) {
            ns()->option->set('mymodule_enabled', 'yes');
            ns()->option->set('mymodule_api_key', '');
            ns()->option->set('mymodule_webhook_url', '');
        }
    }
}
```

### 7. In Tests

```php
namespace Tests\Feature;

use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_can_save_and_retrieve_option()
    {
        // Set option
        ns()->option->set('test_option', 'test_value');
        
        // Retrieve and assert
        $value = ns()->option->get('test_option');
        $this->assertEquals('test_value', $value);
        
        // Clean up
        ns()->option->delete('test_option');
    }
    
    public function test_option_with_array()
    {
        $array = ['item1', 'item2', 'item3'];
        
        // Set array
        ns()->option->set('test_array', $array);
        
        // Retrieve and assert
        $retrieved = ns()->option->get('test_array');
        $this->assertIsArray($retrieved);
        $this->assertEquals($array, $retrieved);
        
        // Clean up
        ns()->option->delete('test_array');
    }
    
    public function test_option_expiration()
    {
        // Set with expiration
        ns()->option->set('temp_option', 'value', now()->subMinute());
        
        // Should be expired (manual check needed)
        $option = Option::where('key', 'temp_option')->first();
        $this->assertTrue($option->expire_on < now());
        
        // Clean up
        ns()->option->delete('temp_option');
    }
}
```

## Common Option Keys

### Store Configuration
```php
'ns_store_name'              // Store name
'ns_store_address'           // Store address
'ns_store_phone'             // Store phone number
'ns_store_email'             // Store email
'ns_store_square_logo'       // Square logo URL
'ns_store_rectangle_logo'    // Rectangle logo URL
'ns_invoice_receipt_logo'    // Receipt logo URL
'ns_invoice_receipt_footer'  // Receipt footer text
```

### System Settings
```php
'ns_default_theme'           // Default theme (light/dark)
'ns_language'                // Default language
'ns_datetime_timezone'       // Timezone
'ns_datetime_format'         // Datetime format string
'ns_currency_symbol'         // Currency symbol
'ns_currency_precision'      // Decimal places
```

### Authentication
```php
'ns_registration_enabled'    // yes/no
'ns_recovery_enabled'        // yes/no
```

### POS Settings
```php
'ns_pos_quick_product'                      // yes/no
'ns_pos_show_quantity'                      // yes/no
'ns_pos_allow_decimal_quantities'           // yes/no
'ns_pos_unit_price_ediable'                 // yes/no
'ns_pos_hide_empty_categories'              // yes/no
'ns_pos_items_merge'                        // yes/no
'ns_pos_order_types'                        // Array of order types
'ns_pos_registers_default_change_payment_type' // Default payment type ID
'ns_pos_vat'                                // VAT tax ID
'ns_pos_tax_group'                          // Tax group ID
'ns_pos_tax_type'                           // exclusive/inclusive
```

### Keyboard Shortcuts
```php
'ns_pos_keyboard_cancel_order'      // Cancel order shortcut
'ns_pos_keyboard_hold_order'        // Hold order shortcut
'ns_pos_keyboard_create_customer'   // Create customer shortcut
'ns_pos_keyboard_payment'           // Payment shortcut
'ns_pos_keyboard_shipping'          // Shipping shortcut
'ns_pos_keyboard_note'              // Note shortcut
'ns_pos_keyboard_order_type'        // Order type shortcut
'ns_pos_keyboard_quick_search'      // Quick search shortcut
'ns_pos_keyboard_toggle_merge'      // Toggle merge shortcut
'ns_pos_amount_shortcut'            // Amount shortcut
```

### Printing
```php
'ns_pos_printing_document'      // receipt/invoice
'ns_pos_printing_gateway'       // default/custom
'ns_pos_printing_enabled_for'   // all_orders/specific
```

### Notifications
```php
'ns_notifications_registrations_administrator_email_body'
'ns_notifications_registrations_user_activate_body'
```

## Best Practices

### 1. Use Descriptive Keys

```php
// ✅ Good - Clear and descriptive
ns()->option->set('ns_store_name', 'My Store');
ns()->option->set('ns_default_theme', 'dark');
ns()->option->set('mymodule_api_enabled', 'yes');

// ❌ Bad - Unclear and ambiguous
ns()->option->set('name', 'My Store');
ns()->option->set('theme', 'dark');
ns()->option->set('enabled', 'yes');
```

### 2. Always Provide Defaults

```php
// ✅ Good - Has fallback
$theme = ns()->option->get('ns_default_theme', 'light');
$precision = ns()->option->get('ns_currency_precision', 2);
$itemsPerPage = ns()->option->get('items_per_page', 10);

// ❌ Bad - May return null unexpectedly
$theme = ns()->option->get('ns_default_theme');
$precision = ns()->option->get('ns_currency_precision');
```

### 3. Use Consistent Prefixes

```php
// Core NexoPOS options
ns()->option->set('ns_store_name', 'Store');
ns()->option->set('ns_currency_symbol', '$');

// Module-specific options
ns()->option->set('mymodule_enabled', 'yes');
ns()->option->set('mymodule_api_key', 'key123');

// Temporary/cache options
ns()->option->set('_cache_products', $data);
ns()->option->set('_temp_session', $session);
```

### 4. Handle Boolean Values Consistently

```php
// ✅ Good - Consistent yes/no pattern
ns()->option->set('feature_enabled', 'yes');
if (ns()->option->get('feature_enabled') === 'yes') {
    // Feature is enabled
}

// ✅ Also acceptable - 1/0 pattern
ns()->option->set('feature_enabled', 1);
if (ns()->option->get('feature_enabled') == 1) {
    // Feature is enabled
}

// ❌ Bad - Inconsistent boolean handling
ns()->option->set('feature_enabled', true); // Stored as "1"
if (ns()->option->get('feature_enabled')) {  // May cause issues
    // Unreliable check
}
```

### 5. Clean Up Temporary Options

```php
// Create temporary option
ns()->option->set('_processing_import', 'yes', now()->addHour());

try {
    // Process import
    processImport();
} finally {
    // Always clean up
    ns()->option->delete('_processing_import');
}
```

### 6. Use Expiration for Cache

```php
// ✅ Good - Cache with expiration
$products = ns()->option->get('cached_products');
if (!$products) {
    $products = Product::all()->toArray();
    ns()->option->set('cached_products', $products, now()->addHours(6));
}

// ❌ Bad - No expiration (cache never refreshes)
$products = ns()->option->get('cached_products');
if (!$products) {
    $products = Product::all()->toArray();
    ns()->option->set('cached_products', $products);
}
```

### 7. Validate Before Setting

```php
// ✅ Good - Validate before saving
public function updateTheme(Request $request)
{
    $validated = $request->validate([
        'theme' => 'required|in:light,dark'
    ]);
    
    ns()->option->set('ns_default_theme', $validated['theme']);
}

// ❌ Bad - No validation
public function updateTheme(Request $request)
{
    ns()->option->set('ns_default_theme', $request->theme);
}
```

### 8. Group Related Settings

```php
// ✅ Good - Get multiple related options at once
$storeSettings = [
    'name' => ns()->option->get('ns_store_name'),
    'address' => ns()->option->get('ns_store_address'),
    'phone' => ns()->option->get('ns_store_phone'),
    'email' => ns()->option->get('ns_store_email')
];

// Or use array retrieval
$storeSettings = ns()->option->get([
    'ns_store_name',
    'ns_store_address',
    'ns_store_phone',
    'ns_store_email'
]);
```

### 9. Document Custom Options

```php
/**
 * Custom module options:
 * - mymodule_enabled: Enable/disable module (yes/no)
 * - mymodule_api_key: API authentication key
 * - mymodule_webhook_url: Webhook callback URL
 * - mymodule_retry_count: Number of retry attempts (int)
 * - mymodule_timeout: Request timeout in seconds (int)
 */
class MyModuleService
{
    public function isEnabled(): bool
    {
        return ns()->option->get('mymodule_enabled', 'no') === 'yes';
    }
}
```

### 10. Rebuild After Bulk Changes

```php
// When making bulk database changes
DB::table('nexopos_options')
    ->where('key', 'like', 'temp_%')
    ->delete();

// Rebuild the options cache
ns()->option->rebuild();
```

## Advanced Patterns

### 1. Option Caching Strategy

```php
class ProductService
{
    const CACHE_KEY = 'cached_featured_products';
    const CACHE_DURATION = 3600; // 1 hour
    
    public function getFeaturedProducts()
    {
        // Try cache first
        $cached = ns()->option->get(self::CACHE_KEY);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Fetch from database
        $products = Product::where('featured', true)
            ->with('category')
            ->get()
            ->toArray();
        
        // Cache for 1 hour
        ns()->option->set(
            self::CACHE_KEY,
            $products,
            now()->addSeconds(self::CACHE_DURATION)
        );
        
        return $products;
    }
    
    public function clearFeaturedProductsCache()
    {
        ns()->option->delete(self::CACHE_KEY);
    }
}
```

### 2. Configuration Manager

```php
class ConfigurationManager
{
    protected array $configKeys = [
        'store_name' => 'ns_store_name',
        'store_address' => 'ns_store_address',
        'currency_symbol' => 'ns_currency_symbol',
        'currency_precision' => 'ns_currency_precision',
    ];
    
    public function get(string $key, mixed $default = null): mixed
    {
        $optionKey = $this->configKeys[$key] ?? $key;
        return ns()->option->get($optionKey, $default);
    }
    
    public function set(string $key, mixed $value): void
    {
        $optionKey = $this->configKeys[$key] ?? $key;
        ns()->option->set($optionKey, $value);
    }
    
    public function all(): array
    {
        $config = [];
        foreach ($this->configKeys as $key => $optionKey) {
            $config[$key] = ns()->option->get($optionKey);
        }
        return $config;
    }
}
```

### 3. Feature Flag Manager

```php
class FeatureFlags
{
    public function isEnabled(string $feature): bool
    {
        $key = "feature_flag_{$feature}";
        return ns()->option->get($key, 'no') === 'yes';
    }
    
    public function enable(string $feature): void
    {
        ns()->option->set("feature_flag_{$feature}", 'yes');
    }
    
    public function disable(string $feature): void
    {
        ns()->option->set("feature_flag_{$feature}", 'no');
    }
    
    public function toggle(string $feature): void
    {
        $current = $this->isEnabled($feature);
        $this->isEnabled($feature) ? $this->disable($feature) : $this->enable($feature);
    }
}

// Usage
$flags = new FeatureFlags();
if ($flags->isEnabled('new_checkout_flow')) {
    // Use new checkout
}
```

### 4. Multi-Tenant Settings

```php
class TenantSettings
{
    protected int $tenantId;
    
    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        $tenantKey = "tenant_{$this->tenantId}_{$key}";
        return ns()->option->get($tenantKey, $default);
    }
    
    public function set(string $key, mixed $value): void
    {
        $tenantKey = "tenant_{$this->tenantId}_{$key}";
        ns()->option->set($tenantKey, $value);
    }
}

// Usage
$tenantSettings = new TenantSettings(auth()->user()->tenant_id);
$tenantSettings->set('custom_logo', $logoUrl);
$logo = $tenantSettings->get('custom_logo');
```

## Troubleshooting

### Issue: Option Not Persisting

**Problem:** Option value doesn't save or reverts to old value.

**Solutions:**
```php
// 1. Check if option exists in database
$option = Option::where('key', 'my_option')->first();
dd($option);

// 2. Ensure proper key formatting (lowercase, no spaces)
ns()->option->set('my_option', 'value'); // ✅ Good
ns()->option->set('My Option', 'value'); // ❌ Bad

// 3. Rebuild options cache
ns()->option->rebuild();

// 4. Check for database issues
try {
    ns()->option->set('test_key', 'test_value');
} catch (\Exception $e) {
    Log::error('Option save failed: ' . $e->getMessage());
}
```

### Issue: Array Not Decoding Properly

**Problem:** Array retrieved as string instead of array.

**Solutions:**
```php
// 1. Verify array flag is set
$option = Option::where('key', 'my_array')->first();
dd($option->array); // Should be true

// 2. Manually decode if needed
$value = ns()->option->get('my_array');
if (is_string($value)) {
    $value = json_decode($value, true);
}

// 3. Re-save the option
ns()->option->set('my_array', ['item1', 'item2']);
```

### Issue: Default Value Not Returning

**Problem:** `get()` returns null instead of default.

**Solutions:**
```php
// 1. Ensure default parameter is provided
$value = ns()->option->get('key', 'default'); // ✅ Good
$value = ns()->option->get('key'); // ❌ Returns null

// 2. Check for empty string vs null
$value = ns()->option->get('key', '');
if ($value === null) {
    $value = 'fallback';
}

// 3. Use null coalescing operator
$value = ns()->option->get('key') ?? 'default';
```

### Issue: Performance Problems

**Problem:** Slow page loads due to many option queries.

**Solutions:**
```php
// 1. Fetch multiple options at once
$options = ns()->option->get([
    'ns_store_name',
    'ns_store_address',
    'ns_currency_symbol'
]);

// 2. Use caching for expensive operations
$data = Cache::remember('heavy_calculation', 3600, function() {
    return expensiveCalculation();
});

// 3. Avoid repeated get() calls in loops
// ❌ Bad
foreach ($products as $product) {
    $tax = ns()->option->get('ns_tax_rate'); // Called 1000 times!
}

// ✅ Good
$tax = ns()->option->get('ns_tax_rate');
foreach ($products as $product) {
    $product->tax = $tax;
}
```

## Security Considerations

### 1. Sanitize Input

Options automatically strip HTML tags, but validate input before saving:

```php
// ✅ Good - Validate before saving
$validated = $request->validate([
    'store_name' => 'required|string|max:255',
    'store_email' => 'required|email'
]);

ns()->option->set('ns_store_name', $validated['store_name']);
ns()->option->set('ns_store_email', $validated['store_email']);

// ❌ Bad - No validation
ns()->option->set('ns_store_name', $request->store_name);
```

### 2. Protect Sensitive Data

```php
// ✅ Good - Encrypt sensitive data
use Illuminate\Support\Facades\Crypt;

ns()->option->set('api_secret', Crypt::encryptString($secret));
$secret = Crypt::decryptString(ns()->option->get('api_secret'));

// Consider using Laravel's native env() for secrets
$apiKey = env('API_KEY'); // Better for sensitive data
```

### 3. Restrict Access

```php
// ✅ Good - Check permissions
if (ns()->allowedTo('manage.settings')) {
    ns()->option->set('critical_setting', $value);
}

// Use middleware
Route::post('/settings', [SettingsController::class, 'update'])
    ->middleware('ns.permission:manage.settings');
```

## Summary

The NexoPOS Options API provides a powerful and flexible system for managing application settings:

- **Simple Interface**: Easy-to-use `get()`, `set()`, and `delete()` methods
- **Type Safety**: Automatic type casting for strings, integers, floats, and arrays
- **Caching**: Built-in caching with optional expiration
- **Global Access**: Available everywhere via `ns()->option`
- **Flexible**: Supports simple values, arrays, and JSON objects
- **Secure**: Automatic HTML tag stripping

Use the Options API whenever you need to store configuration values, feature flags, or temporary cache data that should persist across requests.

## Related Documentation

- `.github/instructions/nexopos-modules.instructions.md` - Module development
- `.github/instructions/nexopos-permissions.instructions.md` - Permission system
- `app/Models/Option.php` - Option model source
- `app/Services/Options.php` - Options service source
