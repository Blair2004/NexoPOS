# Testing Guide: Reordering and Pinned Products Features

## Prerequisites

1. Fresh NexoPOS installation or test environment
2. Admin user with all permissions
3. Some existing categories and products for testing

## Setup

```bash
# 1. Install dependencies (if not already done)
npm install

# 2. Build frontend assets
npm run build

# 3. Run migrations
php artisan migrate

# 4. Clear caches (optional but recommended)
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

## Test Plan

### Part 1: Database Migrations

**Test 1.1: Verify Position Column on Categories**
```bash
# Check if position column exists
php artisan tinker
>>> \App\Models\ProductCategory::first();
>>> # Should see 'position' field in attributes
```

**Test 1.2: Verify Position Column on Products**
```bash
php artisan tinker
>>> \App\Models\Product::first();
>>> # Should see 'position' field in attributes
```

**Test 1.3: Verify Pinned Column on Products**
```bash
php artisan tinker
>>> \App\Models\Product::first();
>>> # Should see 'pinned' field in attributes (default: false)
```

**Expected Results:**
- All columns should exist
- Existing records should have position values (based on created_at)
- Pinned should default to false

### Part 2: Feature Toggle

**Test 2.1: Access POS Settings**
1. Login as admin
2. Navigate to: Settings → POS → Features tab
3. Verify "Enable Category & Products Reordering" toggle exists

**Test 2.2: Toggle Feature On**
1. Set "Enable Category & Products Reordering" to "Yes"
2. Click "Save"
3. Verify success message

**Test 2.3: Toggle Feature Off**
1. Set "Enable Category & Products Reordering" to "No"
2. Click "Save"
3. Verify success message

**Expected Results:**
- Toggle should be visible
- Changes should save successfully
- No errors in console

### Part 3: Reordering Interface

**Test 3.1: Access Reorder Button**
1. Navigate to: Dashboard → Products → Categories
2. Verify "Reorder" button appears in header
3. Click "Reorder" button

**Expected Results:**
- Button should be visible
- Clicking should open popup modal
- Modal should display categories

**Test 3.2: Root Categories Reordering**
1. Open reorder popup
2. Verify all root categories are listed
3. Drag a category to new position
4. Verify visual feedback during drag
5. Click "Save Order"
6. Close popup and refresh page
7. Open reorder popup again
8. Verify order persisted

**Expected Results:**
- Drag-and-drop should work smoothly
- Visual indicators should show during drag
- Order should persist after save

**Test 3.3: Navigate into Category**
1. Open reorder popup
2. Click arrow button on a category (if it has subcategories)
3. Verify breadcrumb updates
4. Verify subcategories or products load

**Expected Results:**
- Navigation should work
- Breadcrumb should update correctly
- Content should change based on navigation

**Test 3.4: Reorder Subcategories**
1. Navigate into a category with subcategories
2. Drag subcategories to new positions
3. Click "Save Order"
4. Navigate back (click "Home" or breadcrumb)
5. Navigate into category again
6. Verify order persisted

**Expected Results:**
- Subcategory ordering works independently
- Order persists correctly

**Test 3.5: Reorder Products**
1. Navigate into a category with products but no subcategories
2. Verify products are displayed
3. Drag products to new positions
4. Click "Save Order"
5. Verify order persisted

**Expected Results:**
- Products can be reordered
- Order persists correctly

**Test 3.6: Breadcrumb Navigation**
1. Navigate: Home → Category A → Category B
2. Click on "Category A" in breadcrumb
3. Verify you return to Category A's contents
4. Click "Home"
5. Verify you return to root categories

**Expected Results:**
- Breadcrumb navigation works correctly
- No errors occur

**Test 3.7: Cancel Changes**
1. Reorder some items
2. Click "Cancel"
3. Reopen popup
4. Verify changes were not saved

**Expected Results:**
- Cancel should discard changes
- Original order should remain

### Part 4: Pinned Products

**Test 4.1: Pin a Product**
1. Navigate to: Dashboard → Products
2. Edit any product
3. Go to "Identification" tab
4. Find "Pin Product" switch
5. Set to "Yes"
6. Save product

**Expected Results:**
- Pin Product field should exist
- Product should save successfully

**Test 4.2: View Pinned Products on POS**
1. Navigate to POS
2. Look above the main product grid
3. Verify pinned products section appears
4. Verify pinned product is displayed

**Expected Results:**
- Pinned products section should show with thumbtack icon
- Pinned product should appear in horizontal scrollable row
- Product should have same styling as grid items

**Test 4.3: Pin Multiple Products**
1. Pin 3-5 different products
2. Go to POS
3. Verify all pinned products appear
4. Try horizontal scrolling if needed

**Expected Results:**
- All pinned products should appear
- Horizontal scroll should work
- No layout issues

**Test 4.4: Unpin a Product**
1. Edit a pinned product
2. Set "Pin Product" to "No"
3. Save
4. Go to POS
5. Verify product no longer appears in pinned section

**Expected Results:**
- Product should be removed from pinned section
- Other pinned products remain

**Test 4.5: Add Pinned Product to Cart**
1. Go to POS
2. Click on a pinned product
3. Verify product is added to cart

**Expected Results:**
- Clicking works same as regular grid items
- Product adds to cart correctly

### Part 5: Ordering Logic

**Test 5.1: Feature Enabled - Custom Order**
1. Enable reordering feature in settings
2. Reorder some categories
3. Go to POS
4. Verify categories appear in custom order

**Expected Results:**
- Categories should display in custom order
- Order should match what was set in reorder popup

**Test 5.2: Feature Disabled - Creation Order**
1. Disable reordering feature in settings
2. Go to POS
3. Verify categories appear in creation date order

**Expected Results:**
- Categories should display by created_at
- Custom positions should be ignored

**Test 5.3: Products Ordering**
1. Enable reordering feature
2. Navigate to a category with products
3. Verify products appear in custom order

**Expected Results:**
- Products should respect custom order when feature enabled

### Part 6: Permissions

**Test 6.1: Reorder Without Permission (Categories)**
1. Create test user without `nexopos.update.categories` permission
2. Login as test user
3. Try to access reorder popup (if button is visible)
4. Try to POST to `/api/categories/reorder` directly

**Expected Results:**
- Should get permission denied error
- Changes should not save

**Test 6.2: Reorder Without Permission (Products)**
1. Create test user without `nexopos.update.products` permission
2. Try to POST to `/api/products/reorder`

**Expected Results:**
- Should get permission denied error
- Changes should not save

### Part 7: Edge Cases

**Test 7.1: Empty Category**
1. Create category with no products or subcategories
2. Try to navigate into it in reorder popup

**Expected Results:**
- Should show empty state message
- No errors

**Test 7.2: Single Item**
1. Navigate to category with only one item
2. Verify it's still displayed
3. Drag should work but have no effect

**Expected Results:**
- Single item should display correctly
- No errors

**Test 7.3: Large Number of Items**
1. Create category with 50+ products
2. Open reorder popup
3. Navigate to that category
4. Try dragging items

**Expected Results:**
- Should handle reasonably well
- May have slight performance impact but no crashes

**Test 7.4: Breadcrumb Typo Fix**
1. Inspect POS grid DOM
2. Verify element ID is `grid-breadcrumb` (not `grid-breadscrumb`)

**Expected Results:**
- Typo should be fixed
- No references to old ID

## Browser Compatibility

Test in:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Common Issues and Solutions

### Issue: Reorder button doesn't appear
- Solution: Clear browser cache, rebuild assets with `npm run build`

### Issue: Drag and drop doesn't work
- Solution: Check console for JavaScript errors, ensure vuedraggable is installed

### Issue: Positions don't persist
- Solution: Check API endpoint returns success, verify database columns exist

### Issue: Pinned products don't show
- Solution: Verify `pinnedProducts` is returned from API, check product has `pinned = true`

### Issue: Permission denied on reorder
- Solution: Verify user has correct permissions in their role

## Success Criteria

✅ All migrations run without errors
✅ Feature toggle works correctly
✅ Reorder popup opens and functions
✅ Drag-and-drop works smoothly
✅ Order persists after saving
✅ Breadcrumb navigation works
✅ Pinned products display on POS
✅ Pinned products are clickable
✅ Custom ordering works when enabled
✅ Falls back to creation order when disabled
✅ Permission checks work correctly
✅ No console errors
✅ Works on mobile browsers

## Reporting Issues

If you find any issues during testing:
1. Note the exact steps to reproduce
2. Check browser console for errors
3. Check Laravel logs (`storage/logs/laravel.log`)
4. Include browser and OS information
5. Take screenshots if applicable
