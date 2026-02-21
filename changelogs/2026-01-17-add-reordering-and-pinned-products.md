# Add Reordering and Pinned Products Features

**Date:** 2026-01-17
**Type:** Feature
**Affects:** Core - POS, Products, Categories
**Version:** 6.1.0

## Summary

Added two new features to NexoPOS:
1. **Category & Product Reordering**: Drag-and-drop interface for customizing the order of categories and products
2. **Pinned Products**: Ability to pin important products to display at the top of the POS grid

## Changes Made

### Database Migrations

1. **Add Position Column** (`2026_01_17_120000_add_position_to_categories_and_products.php`)
   - Added `position` (integer, default 0) to `nexopos_products_categories` table
   - Added `position` (integer, default 0) to `nexopos_products` table
   - Automatically sets initial positions based on `created_at` timestamps for existing records

2. **Add Pinned Column** (`2026_01_17_120001_add_pinned_to_products.php`)
   - Added `pinned` (boolean, default false) to `nexopos_products` table

### Backend Changes

#### Settings
- Added "Enable Category & Products Reordering" toggle in POS Settings → Features
  - Path: `app/Settings/pos/features.php`
  - When enabled: Items ordered by `position` column
  - When disabled: Items ordered by `created_at` (backward compatible)

#### API Endpoints
Added two new endpoints with permission checks:
- `POST /api/categories/reorder`
  - Requires permission: `nexopos.update.categories`
  - Accepts: `{ items: [{ id, position }] }`
  - Updates category positions in bulk

- `POST /api/products/reorder`
  - Requires permission: `nexopos.update.products`
  - Accepts: `{ items: [{ id, position }] }`
  - Updates product positions in bulk

#### CategoryController
- Updated `getCategories()` method:
  - Now includes `pinnedProducts` in response
  - Orders categories/products by `position` when feature enabled
  - Orders by `created_at` when feature disabled
- Added `getPinnedProducts()` private method
- Added `reorderCategories()` method

#### ProductsController
- Added `reorderProducts()` method for bulk position updates

#### ProductCrud
- Added "Pin Product" switch field on "identification" tab
- Field type: switch (Yes/No)
- Stored in `pinned` column

#### ProductCategoryCrud
- Added "Reorder" header button
- Opens reordering popup component

### Frontend Changes

#### New Component: ns-reorder-popup.vue
Location: `resources/ts/popups/ns-reorder-popup.vue`

Features:
- Drag-and-drop interface using vuedraggable
- Breadcrumb navigation for nested categories
- Context-aware (handles both categories and products)
- Shows categories when viewing a parent category
- Shows products when category has no subcategories
- Visual drag handles on each item
- Save/Cancel buttons
- Real-time change detection

#### Updated: ns-pos-grid.vue
Changes:
1. Fixed typo: `#grid-breadscrumb` → `#grid-breadcrumb`
2. Added pinned products section:
   - Location: Between breadcrumb and main grid
   - Layout: Horizontal scrollable row
   - Styling: Same as grid items with fixed width (144px)
   - Visual indicator: Thumbtack icon + label
3. Updated `loadCategories()` method to load pinned products
4. Added `pinnedProducts` to component data

#### Updated: popups.ts
- Registered `nsReorderPopup` component globally

### Package Dependencies

Updated `package.json`:
- Updated `vuedraggable` from `^2.24.3` to `^4.1.0` (Vue 3 compatible)
- Added `sortablejs` ^1.15.3 (peer dependency for vuedraggable)

## Migration Required

```bash
# Run migrations
php artisan migrate

# Install updated dependencies
npm install

# Build frontend assets
npm run build
```

## Breaking Changes

None. Feature is opt-in via settings toggle. When disabled, the system behaves exactly as before.

## Usage

### Reordering Categories/Products

1. Navigate to Dashboard → Products → Categories
2. Click the "Reorder" button in the header
3. Drag items to desired positions
4. Click into categories to reorder their subcategories or products
5. Click "Save Order" to persist changes

### Pinning Products

1. Edit a product
2. On the "Identification" tab, enable "Pin Product" switch
3. Save the product
4. Pinned product will appear at top of POS grid in a horizontal scrollable row

### Enable Custom Ordering

1. Navigate to Settings → POS → Features
2. Enable "Enable Category & Products Reordering"
3. Save settings
4. Categories and products will now display in custom order on POS

## Security

- Reorder endpoints protected by `NsRestrictMiddleware`
- Categories require `nexopos.update.categories` permission
- Products require `nexopos.update.products` permission
- No SQL injection vulnerabilities (uses Eloquent ORM)

## Performance Notes

- Position column indexed for optimal query performance
- Pinned products query filtered efficiently with boolean column
- Drag-and-drop interface uses optimized Vue 3 component

## Testing Considerations

- Test reordering at multiple category levels
- Test reordering products within categories
- Test pinned products display with 0, 1, and many products
- Test feature toggle on/off states
- Test permission restrictions for reorder operations
- Verify backward compatibility with feature disabled
- Test on mobile devices (touch drag-and-drop)

## Known Limitations

- Reordering popup requires JavaScript enabled
- Drag-and-drop may not work on very old browsers
- Large lists (1000+ items) may have performance impact

## Related Issues

None

## Future Enhancements

Potential improvements for future versions:
- Bulk position assignment (move to top, move to bottom, etc.)
- Sort by name/date options in reorder popup
- Multi-select and batch operations in reorder interface
- Pin products from POS grid directly
- Maximum number of pinned products setting
