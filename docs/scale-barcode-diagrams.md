# Scale Barcode Feature - Visual Guide

## Barcode Format Diagram

```
┌─────────────── EAN-13 Scale Barcode ───────────────┐
│                                                     │
│   2    12345    00123    9                        │
│   │      │        │      │                         │
│   │      │        │      └─ Check Digit (1 digit)  │
│   │      │        └──────── Value (5 digits)       │
│   │      └───────────────── Product Code (5 digits)│
│   └──────────────────────── Prefix (1 digit)       │
│                                                     │
│   Total Length: 13 digits (EAN-13 Standard)        │
└─────────────────────────────────────────────────────┘
```

## Weight-Based Example

```
Barcode: 2 12345 00123 9
         │   │     │    │
         │   │     │    └─ Check: 9
         │   │     └────── Weight: 123 grams = 0.123 kg
         │   └──────────── Product: Apples (code 12345)
         └──────────────── Scale Prefix: 2

Customer weighs apples → Scale prints barcode
Cashier scans → System adds 0.123 kg to cart
Price: $2.00/kg × 0.123 = $0.25
```

## Price-Based Example

```
Barcode: 2 12345 01234 9
         │   │     │    │
         │   │     │    └─ Check: 9
         │   │     └────── Price: 1234 cents = $12.34
         │   └──────────── Product: Cheese (code 12345)
         └──────────────── Scale Prefix: 2

Pre-priced item → Scale prints barcode with price
Cashier scans → System calculates quantity from price
If unit price is $20.00/kg: quantity = $12.34 ÷ $20.00 = 0.617 kg
```

## System Flow Diagram

```
┌──────────────┐
│   Customer   │
│ weighs item  │
│  on scale    │
└──────┬───────┘
       │
       ↓
┌──────────────┐
│    Scale     │
│   generates  │
│   barcode    │
│ 2123450012349│
└──────┬───────┘
       │
       ↓
┌──────────────┐
│   Cashier    │
│    scans     │
│   barcode    │
└──────┬───────┘
       │
       ↓
┌──────────────────────────────────────┐
│         NexoPOS POS System           │
│                                      │
│  1. Frontend receives barcode input  │
│     └─ ns-pos-grid.vue               │
│                                      │
│  2. API call to search endpoint      │
│     └─ GET /api/products/search/     │
│        using-barcode/{barcode}       │
│                                      │
│  3. ScaleBarcodeService detects      │
│     └─ isScaleBarcode() → true       │
│                                      │
│  4. Parse scale barcode              │
│     └─ parseScaleBarcode()           │
│        ├─ Product Code: 12345        │
│        └─ Weight: 0.123 kg           │
│                                      │
│  5. Search product by code           │
│     └─ Product::findUsingBarcode()   │
│                                      │
│  6. Attach scale data to product     │
│     └─ product.scale_barcode_data    │
│                                      │
│  7. Return to frontend               │
│     └─ JSON response with scale data │
│                                      │
│  8. Process scale data               │
│     └─ Set product.$quantity = 0.123 │
│                                      │
│  9. Show notification                │
│     └─ "Scale barcode: 0.123 kg"     │
│                                      │
│ 10. Add to cart                      │
│     └─ POS.addToCart(product)        │
└──────────────────────────────────────┘
       │
       ↓
┌──────────────┐
│  Cart shows  │
│   Apples     │
│  0.123 kg    │
│   @ $2.00    │
│ Total: $0.25 │
└──────────────┘
```

## Configuration Flow

```
┌────────────────────────────────────────┐
│  Dashboard → Settings → POS            │
│       → Scale Barcode                  │
└────────────┬───────────────────────────┘
             │
             ↓
┌────────────────────────────────────────┐
│        Configuration Form              │
│                                        │
│  [✓] Enable Scale Barcode              │
│                                        │
│  Prefix: [2___] (default)              │
│                                        │
│  Type: [Weight ▼]                      │
│        • Weight (grams → kg)           │
│        • Price (cents → currency)      │
│                                        │
│  Product Code Length: [5____]          │
│                                        │
│  Value Length: [5____]                 │
│                                        │
│  ┌──────────────────────────────────┐  │
│  │ Configuration Example:           │  │
│  │                                  │  │
│  │ Format: 2XXXXXWWWWWC            │  │
│  │                                  │  │
│  │ Where:                           │  │
│  │ - 2 = Scale barcode prefix       │  │
│  │ - XXXXX = Product code (5)       │  │
│  │ - WWWWW = Weight in grams (5)    │  │
│  │ - C = Check digit                │  │
│  │                                  │  │
│  │ Example: 2123450012349           │  │
│  │ - Product: 12345                 │  │
│  │ - Weight: 123g = 0.123kg         │  │
│  └──────────────────────────────────┘  │
│                                        │
│              [Save Settings]           │
└────────────────────────────────────────┘
             │
             ↓
┌────────────────────────────────────────┐
│  Settings saved to options table:      │
│                                        │
│  ns_scale_barcode_enabled: 'yes'      │
│  ns_scale_barcode_prefix: '2'         │
│  ns_scale_barcode_type: 'weight'      │
│  ns_scale_barcode_product_length: 5   │
│  ns_scale_barcode_value_length: 5     │
└────────────────────────────────────────┘
```

## Data Conversion Examples

### Weight Conversion

```
Scale Output          NexoPOS Quantity
─────────────────    ─────────────────
00001 grams    →     0.001 kg
00123 grams    →     0.123 kg
01000 grams    →     1.000 kg
12345 grams    →     12.345 kg
```

### Price Conversion

```
Scale Output          NexoPOS Price
─────────────────    ─────────────────
00001 cents    →     $0.01
00123 cents    →     $1.23
01000 cents    →     $10.00
12345 cents    →     $123.45
```

## Product Setup Diagram

```
┌─────────────────────────────────────┐
│         NexoPOS Product             │
│                                     │
│  Name: Fresh Apples                 │
│  Barcode: 12345 ← Must match scale │
│  Unit: Kilogram (kg)                │
│  Sale Price: $2.00 per kg           │
│  Stock Management: Enabled          │
│  Status: Available                  │
└─────────────────────────────────────┘
           │
           │ matches
           ↓
┌─────────────────────────────────────┐
│      Electronic Scale Setup         │
│                                     │
│  Product Code: 12345 ← Same code   │
│  Product Name: Apples               │
│  Price per kg: $2.00                │
│  Barcode Format: EAN-13             │
│  Prefix: 2                          │
└─────────────────────────────────────┘
           │
           │ generates
           ↓
┌─────────────────────────────────────┐
│      Scale Barcode Output           │
│                                     │
│  When 0.500 kg weighed:             │
│  Generates: 2123450050009           │
│                                     │
│  Breakdown:                         │
│  - Prefix: 2                        │
│  - Product: 12345                   │
│  - Weight: 00500 (500 grams)        │
│  - Check: 9                         │
└─────────────────────────────────────┘
```

## Error Handling Flow

```
┌──────────────┐
│ Scan Barcode │
└──────┬───────┘
       │
       ↓
┌──────────────────┐
│ Is Scale Barcode?│
│  isScaleBarcode()│
└──────┬───────────┘
       │
       ├─ No → Regular barcode handling
       │
       └─ Yes
          │
          ↓
   ┌──────────────┐
   │ Parse Barcode│
   │ parseScale() │
   └──────┬───────┘
          │
          ├─ Error → Show error message
          │          "Invalid scale barcode format"
          │
          └─ Success
             │
             ↓
      ┌─────────────┐
      │ Find Product│
      │  by code    │
      └─────┬───────┘
            │
            ├─ Not Found → "Product not found"
            │
            └─ Found
               │
               ↓
         ┌─────────────┐
         │ Add to Cart │
         │ with scale  │
         │    data     │
         └─────┬───────┘
               │
               ↓
         ┌─────────────┐
         │   Success   │
         │ Notification│
         └─────────────┘
```

## Testing Scenario

```
Test Case: Weight-Based Scale Barcode
─────────────────────────────────────

Setup:
  Product: Test Apples
  Barcode: 12345
  Price: $2.00/kg
  
Generate Test Barcode:
  Code: 12345
  Weight: 500 grams
  Expected: 2123450050009
  
Test Steps:
  1. Enable scale barcode feature ✓
  2. Configure: prefix=2, type=weight ✓
  3. Create product with barcode=12345 ✓
  4. Open POS ✓
  5. Scan: 2123450050009 ✓
  
Expected Results:
  ✓ Product added: Test Apples
  ✓ Quantity set: 0.500 kg
  ✓ Notification: "Scale barcode detected: 0.500 kg"
  ✓ Cart total: $1.00 ($2.00 × 0.500)
```

## Architecture Layers

```
┌─────────────────────────────────────────┐
│          Presentation Layer             │
│  • Vue Components (ns-pos-grid.vue)     │
│  • User Notifications                   │
│  • Cart Display                         │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│          Controller Layer               │
│  • ProductsController                   │
│  • Request Handling                     │
│  • Response Formation                   │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│           Service Layer                 │
│  • ScaleBarcodeService                  │
│    - Detection Logic                    │
│    - Parsing Logic                      │
│    - Generation Logic                   │
│    - Configuration Management           │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│            Data Layer                   │
│  • Product Model                        │
│  • Options (Configuration)              │
│  • Database Queries                     │
└─────────────────────────────────────────┘
```

## Settings Page Layout

```
┌─────────────────────────────────────────────────┐
│  Dashboard > Settings > POS > Scale Barcode     │
├─────────────────────────────────────────────────┤
│                                                 │
│  Scale Barcode Configuration                    │
│  ───────────────────────────────────────────   │
│                                                 │
│  ○ Enable Scale Barcode         [Yes ▼]        │
│    Enable support for scale barcodes            │
│                                                 │
│  ○ Barcode Prefix              [2______]        │
│    The prefix that identifies scale barcodes    │
│                                                 │
│  ○ Barcode Type                [Weight ▼]       │
│    Select weight or price encoding              │
│                                                 │
│  ○ Product Code Length         [5______]        │
│    Number of digits for product code            │
│                                                 │
│  ○ Weight/Price Value Length   [5______]        │
│    Number of digits for weight/price            │
│                                                 │
│  ┌───────────────────────────────────────────┐ │
│  │  Configuration Example                    │ │
│  │                                           │ │
│  │  Format: 2XXXXXWWWWWC                    │ │
│  │                                           │ │
│  │  Example: 2123450012349                  │ │
│  │  - Product code: 12345                   │ │
│  │  - Weight: 00123 grams = 0.123 kg        │ │
│  └───────────────────────────────────────────┘ │
│                                                 │
│             [Save Settings]                     │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

These diagrams provide a visual reference for understanding how the scale barcode feature works, from configuration through to actual usage at the POS.
