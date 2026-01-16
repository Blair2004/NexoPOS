# PLU System for Scale Barcodes

**Date:** 2026-01-16  
**Type:** Major Feature  
**Affects:** Core, Product Management, POS

## Summary

Implemented a comprehensive Price Lookup (PLU) system for scale barcodes. PLU codes are now stored per unit quantity with proper range management, replacing the simple preferred unit approach.

## Key Changes

- Created `ScaleRange` model and table with 11 default ranges
- Added `scale_plu` and `is_weighable` to product unit quantities
- Added `scale_range_id` to product categories
- Created full CRUD interface for managing PLU ranges
- Backend now handles PLU-to-product lookup
- Frontend simplified with server-side unit selection

## Database Changes

### New Table: nexopos_scale_ranges
Manages PLU code ranges (Test, Fruits & Vegetables, Meat, Seafood, etc.)

### Modified: nexopos_products_unit_quantities
- Added: scale_plu (VARCHAR 10, unique)
- Added: is_weighable (BOOLEAN)

### Modified: nexopos_products_categories
- Added: scale_range_id (FK to scale_ranges)

### Modified: nexopos_products
- Removed: scale_barcode_preferred_unit_id

## Features

- Auto-generate PLU codes from category ranges
- Manual PLU assignment with validation
- Range capacity tracking
- Uniqueness enforcement
- Zero-padded PLU codes
- Per-unit weighable configuration

## Migration Required

Run migrations to:
1. Remove old preferred_unit column
2. Create scale_ranges table
3. Add PLU fields to unit quantities
4. Link categories to ranges

## Breaking Changes

- Scale barcodes now encode PLU instead of product SKU
- Unit selection handled server-side
- Requires PLU assignment for existing weighable products

## Files Changed

- 4 new migrations
- New: ScaleRange model and CRUD
- Updated: ProductUnitQuantity, ProductCategory models
- Updated: ProductCrud, ProductCategoryCrud
- Updated: ProductService, ProductsController
- Updated: ns-pos-grid.vue
