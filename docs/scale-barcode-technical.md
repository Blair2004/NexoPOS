# Scale Barcode Support - Technical Implementation

## Architecture Overview

The scale barcode feature is implemented across multiple layers of the NexoPOS application:

### Components

1. **ScaleBarcodeService** (`app/Services/ScaleBarcodeService.php`)
   - Core service for barcode detection, parsing, and generation
   - Handles configuration and validation
   - Provides utility methods for barcode operations

2. **Settings Configuration** (`app/Settings/pos/scale-barcode.php`)
   - User interface for configuring scale barcode parameters
   - Provides example output based on current settings
   - Validates configuration inputs

3. **ProductsController Integration** (`app/Http/Controllers/Dashboard/ProductsController.php`)
   - Detects scale barcodes in searchUsingArgument method
   - Extracts product code and searches for product
   - Attaches scale data to product response

4. **POS Frontend Integration** (`resources/ts/pages/dashboard/pos/ns-pos-grid.vue`)
   - Processes scale barcode data in submitSearch method
   - Sets quantity automatically for weight-based barcodes
   - Calculates quantity from price for price-based barcodes
   - Displays user notifications

## Service Methods

### ScaleBarcodeService

```php
class ScaleBarcodeService
{
    // Configuration Methods
    public function isEnabled(): bool
    public function getPrefix(): string
    public function getProductCodeLength(): int
    public function getValueLength(): int
    public function getType(): string
    public function getConfiguration(): array
    
    // Detection Methods
    public function isScaleBarcode(string $barcode): bool
    
    // Parsing Methods
    public function parseScaleBarcode(string $barcode): array
    public function extractProductCode(string $barcode): string
    public function extractValue(string $barcode): float
    
    // Generation Methods
    public function generateScaleBarcode(string $productCode, float $value): string
}
```

## Data Flow

### Barcode Scanning Flow

```
1. User scans barcode at POS
   ↓
2. Frontend: ns-pos-grid.vue → submitSearch(barcode)
   ↓
3. API Call: GET /api/products/search/using-barcode/{barcode}
   ↓
4. Backend: ProductsController → searchUsingArgument(barcode)
   ↓
5. Scale Detection: ScaleBarcodeService → isScaleBarcode(barcode)
   ↓
6. If scale barcode:
   a. Parse barcode → extract product_code and value
   b. Search product using product_code
   c. Attach scale_barcode_data to product
   ↓
7. Return product with scale data to frontend
   ↓
8. Frontend processes scale data:
   a. Weight type → set product.$quantity
   b. Price type → calculate quantity from price
   ↓
9. Add product to cart with correct quantity
   ↓
10. Display notification to user
```

## Configuration Storage

Scale barcode settings are stored in the options table:

| Option Key | Type | Default | Description |
|------------|------|---------|-------------|
| `ns_scale_barcode_enabled` | string | 'no' | Enable/disable feature |
| `ns_scale_barcode_prefix` | string | '2' | Barcode prefix |
| `ns_scale_barcode_type` | string | 'weight' | Type: weight or price |
| `ns_scale_barcode_product_length` | integer | 5 | Product code length |
| `ns_scale_barcode_value_length` | integer | 5 | Value data length |

## API Response Format

### Standard Product Response
```json
{
  "type": "product",
  "product": {
    "id": 123,
    "name": "Product Name",
    "barcode": "12345",
    // ... standard product fields
  }
}
```

### Scale Barcode Response
```json
{
  "type": "product",
  "product": {
    "id": 123,
    "name": "Product Name",
    "barcode": "12345",
    "scale_barcode_data": {
      "product_code": "12345",
      "value": 0.123,
      "type": "weight",
      "original_barcode": "2123450012349"
    },
    // ... standard product fields
  }
}
```

## Barcode Format Specification

### Structure

```
[Prefix][Product Code][Value][Check Digit]
```

### Components

1. **Prefix** (1-2 digits)
   - Identifies barcode as scale type
   - Default: "2"
   - Configurable in settings

2. **Product Code** (3-6 digits)
   - Unique product identifier
   - Must match product barcode in NexoPOS
   - Default length: 5 digits

3. **Value** (4-6 digits)
   - Weight in grams OR price in cents
   - Default length: 5 digits
   - Padded with leading zeros

4. **Check Digit** (1 digit)
   - EAN-13 check digit
   - Calculated using standard algorithm

### Examples

**Weight Barcode:**
```
2 12345 00123 9
│   │     │    │
│   │     │    └─ Check digit
│   │     └────── 123 grams = 0.123 kg
│   └──────────── Product code
└──────────────── Scale prefix
```

**Price Barcode:**
```
2 12345 01234 9
│   │     │    │
│   │     │    └─ Check digit
│   │     └────── 1234 cents = $12.34
│   └──────────── Product code
└──────────────── Scale prefix
```

## Check Digit Calculation

The EAN-13 check digit is calculated using standard algorithm:

```php
private function calculateEAN13CheckDigit(string $barcode): int
{
    $sum = 0;
    $length = strlen($barcode);
    
    for ($i = 0; $i < $length; $i++) {
        $digit = (int) $barcode[$i];
        // Odd positions (1-based) get multiplied by 1, even by 3
        $multiplier = ($i % 2 === 0) ? 1 : 3;
        $sum += $digit * $multiplier;
    }
    
    $checkDigit = (10 - ($sum % 10)) % 10;
    return $checkDigit;
}
```

## Frontend Integration

### Vue Component Changes

**File:** `resources/ts/pages/dashboard/pos/ns-pos-grid.vue`

**Modified Method:** `submitSearch(value)`

```typescript
submitSearch(value) {
    if (value.length > 0) {
        const url = nsHooks.applyFilters(
            'ns-pos-submit-search-url',
            `/api/products/search/using-barcode/${value}`,
            value
        );

        nsHttpClient.get(url).subscribe({
            next: result => {
                this.barcode = '';
                
                // Process scale barcode data
                if (result.product.scale_barcode_data) {
                    const scaleData = result.product.scale_barcode_data;
                    
                    if (scaleData.type === 'weight') {
                        // Set quantity directly
                        result.product.$quantity = scaleData.value;
                        
                        // Show notification
                        nsSnackBar.info(
                            __('Scale barcode detected: {weight} kg')
                                .replace('{weight}', scaleData.value.toFixed(3))
                        );
                    } else if (scaleData.type === 'price') {
                        // Calculate quantity from price
                        const unitPrice = result.product.unit_quantities[0]?.sale_price || 0;
                        if (unitPrice > 0) {
                            result.product.$quantity = scaleData.value / unitPrice;
                        }
                        
                        // Show notification
                        nsSnackBar.info(
                            __('Scale barcode detected: {price}')
                                .replace('{price}', this.nsCurrency(scaleData.value))
                        );
                    }
                }
                
                POS.addToCart(result.product);
            },
            error: (error) => {
                this.barcode = '';
                nsSnackBar.error(error.message);
            }
        });
    }
}
```

## Extension Points

### Hooks Available

1. **Filter: `ns-pos-submit-search-url`**
   - Modify search URL before API call
   - Useful for custom routing

2. **Custom Scale Barcode Processing**
   ```php
   // Add custom processing after scale barcode detection
   Hook::addFilter('ns-scale-barcode-detected', function($product, $scaleData) {
       // Custom logic
       return $product;
   }, 10, 2);
   ```

3. **Custom Barcode Format**
   ```php
   // Override barcode validation
   Hook::addFilter('ns-scale-barcode-is-valid', function($isValid, $barcode) {
       // Custom validation logic
       return $isValid;
   }, 10, 2);
   ```

## Testing

### Test Coverage

**File:** `tests/Feature/ScaleBarcodeTest.php`

**Test Cases:**
1. `test_scale_barcode_detection()` - Validates barcode detection logic
2. `test_scale_barcode_parsing_weight()` - Tests weight extraction
3. `test_scale_barcode_parsing_price()` - Tests price extraction
4. `test_scale_barcode_generation_weight()` - Tests barcode generation
5. `test_scale_barcode_generation_price()` - Tests price-based generation
6. `test_scale_barcode_disabled()` - Tests disabled state
7. `test_scale_barcode_configuration()` - Tests configuration retrieval
8. `test_extract_product_code()` - Tests product code extraction
9. `test_extract_value_weight()` - Tests weight value extraction
10. `test_extract_value_price()` - Tests price value extraction

### Running Tests

```bash
php artisan test --filter=ScaleBarcodeTest
```

## Performance Considerations

### Optimization Strategies

1. **Caching Configuration**
   - Configuration loaded on first use
   - Cached for request lifecycle
   - No database queries per barcode scan

2. **Early Return Pattern**
   - Quick detection check before parsing
   - Minimal overhead for regular barcodes
   - Only parse when scale format detected

3. **Lazy Loading**
   - Service instantiated only when needed
   - No impact on non-scale operations

## Security Considerations

1. **Input Validation**
   - Barcode format validated before parsing
   - Length checks prevent buffer issues
   - Digit-only validation prevents injection

2. **Configuration Validation**
   - Settings validated before save
   - Reasonable range limits enforced
   - Invalid configurations rejected

3. **No Sensitive Data**
   - Scale barcodes contain only:
     - Product codes (public)
     - Weights/prices (transactional)
   - No customer or payment information

## Migration Path

### Enabling Feature

No database migrations required. The feature uses existing options table.

**Steps:**
1. Navigate to Settings → POS → Scale Barcode
2. Enable the feature
3. Configure prefix and format
4. Save settings

### Upgrading from Manual Process

**Before:** Staff manually entered weight/price
**After:** Automatic via scale barcode

**Migration Steps:**
1. Configure products with scale codes
2. Program scales with matching codes
3. Enable scale barcode feature
4. Train staff on new process
5. Monitor first few days

## Troubleshooting Guide

### Debug Mode

Enable detailed logging:

```php
// In ScaleBarcodeService
use Illuminate\Support\Facades\Log;

public function parseScaleBarcode(string $barcode): array
{
    Log::debug('Scale barcode detected', [
        'barcode' => $barcode,
        'config' => $this->getConfiguration()
    ]);
    
    // ... parsing logic
}
```

### Common Issues

1. **Barcode Not Detected**
   - Check prefix matches configuration
   - Verify barcode length is correct
   - Ensure feature is enabled

2. **Wrong Product Returned**
   - Verify product code matches
   - Check product barcode field
   - Ensure product is active

3. **Incorrect Quantity**
   - Verify weight vs price type
   - Check scale generates grams/cents
   - Validate conversion logic

## Future Enhancements

### Potential Features

1. **Multiple Prefixes**
   - Support multiple prefix values
   - Useful for mixed scale types

2. **Custom Formats**
   - Support non-EAN-13 formats
   - Configurable field positions

3. **Scale Management**
   - Track scales in system
   - Monitor scale health
   - Scale-specific settings

4. **Reporting**
   - Scale barcode usage statistics
   - Weight/price trends
   - Scale performance metrics

## References

- EAN-13 Standard: GS1 Barcode Specification
- NexoPOS Documentation: https://nexopos.com/docs
- Scale Integration Guide: Manufacturer specific

## Support

For technical implementation questions:
- GitHub Issues: https://github.com/Blair2004/NexoPOS/issues
- Documentation: https://nexopos.com/docs
- Community Forum: https://nexopos.com/community
