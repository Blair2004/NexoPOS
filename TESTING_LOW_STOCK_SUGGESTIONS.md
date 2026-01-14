# Testing Guide: Low Stock Suggestions for Procurement

## Overview

This guide provides step-by-step instructions for testing the low stock product suggestion feature in NexoPOS.

## Prerequisites

- NexoPOS installation with admin access
- At least one product with stock management enabled
- Database access for setup

## Test Environment Setup

### 1. Create Test Products

Run these SQL commands to set up test products:

```sql
-- Create or update a product with low stock
UPDATE nexopos_products_unit_quantities 
SET 
    stock_alert_enabled = 1,
    low_quantity = 50,
    quantity = 10
WHERE product_id = 1
LIMIT 1;

-- Create another low stock product
UPDATE nexopos_products_unit_quantities 
SET 
    stock_alert_enabled = 1,
    low_quantity = 30,
    quantity = 5
WHERE product_id = 2
LIMIT 1;

-- Verify settings
SELECT 
    puq.id,
    p.name,
    p.sku,
    puq.quantity,
    puq.low_quantity,
    puq.stock_alert_enabled
FROM nexopos_products_unit_quantities puq
JOIN nexopos_products p ON p.id = puq.product_id
WHERE puq.stock_alert_enabled = 1
AND puq.low_quantity > puq.quantity;
```

### 2. Verify API Endpoint

Test the API endpoint directly:

**Option A: Using Browser**
1. Open browser DevTools (F12)
2. Go to Console tab
3. Run:
```javascript
nsHttpClient.get('/api/procurements/low-stock-suggestions')
  .subscribe({
    next: result => {
      console.log('API Response:', result);
      console.table(result.data);
    },
    error: error => console.error('Error:', error)
  });
```

**Option B: Using cURL**
```bash
curl -X GET \
  http://localhost/api/procurements/low-stock-suggestions \
  -H 'Accept: application/json' \
  -H 'Cookie: your-session-cookie'
```

**Expected Response:**
```json
{
  "status": "success",
  "data": [...],
  "count": 2
}
```

## Test Cases

### Test Case 1: Notification Display

**Objective**: Verify notification appears when low stock products exist

**Steps**:
1. Log in to NexoPOS as admin
2. Navigate to **Procurement → New Procurement** (`/dashboard/procurements/create`)
3. Wait for page to fully load

**Expected Result**:
- Floating notification appears in bottom-right corner
- Title: "Low Stock Alert"
- Message: "X products are running low on stock."
- Two buttons visible: "Load Products" and "Dismiss"
- Notification persists (doesn't auto-dismiss)

**Pass Criteria**:
- [ ] Notification appears within 2-3 seconds
- [ ] Message shows correct count
- [ ] Buttons are clickable
- [ ] Notification styled correctly (info theme)

---

### Test Case 2: Load Products Functionality

**Objective**: Verify products are loaded correctly when clicking "Load Products"

**Steps**:
1. From notification, click **"Load Products"** button
2. Observe the interface changes

**Expected Result**:
- Notification closes
- Interface switches to "Products" tab
- Low stock products appear in the products list
- Success message shows: "X products have been added to the procurement."
- Products have default values set

**Pass Criteria**:
- [ ] All low stock products added
- [ ] Tab switches automatically
- [ ] Success message appears
- [ ] Products have proper unit selection
- [ ] Quantities are set (default: 1)

---

### Test Case 3: Dismiss Functionality

**Objective**: Verify notification can be dismissed without loading products

**Steps**:
1. Navigate to procurement create page
2. Wait for notification to appear
3. Click **"Dismiss"** button

**Expected Result**:
- Notification closes with fade-out animation
- No products added to procurement
- No error messages
- Form remains in "Details" tab

**Pass Criteria**:
- [ ] Notification closes smoothly
- [ ] Products list remains empty
- [ ] No JavaScript errors in console
- [ ] User can continue with normal workflow

---

### Test Case 4: Duplicate Prevention

**Objective**: Verify products already in procurement are not added again

**Steps**:
1. Navigate to procurement create page
2. Manually add a product that's in the low stock list:
   - Search for the product
   - Click to add it
3. Click "Load Products" from notification

**Expected Result**:
- Only products NOT already in procurement are added
- Success message reflects actual count of added products
- Existing products remain unchanged

**Pass Criteria**:
- [ ] No duplicate products in list
- [ ] Correct count in success message
- [ ] Existing product quantities unchanged

---

### Test Case 5: No Low Stock Products

**Objective**: Verify notification doesn't appear when no products have low stock

**Steps**:
1. Set all products to have sufficient stock:
```sql
UPDATE nexopos_products_unit_quantities 
SET quantity = 100
WHERE stock_alert_enabled = 1;
```
2. Navigate to procurement create page
3. Wait for page to fully load

**Expected Result**:
- No notification appears
- Page loads normally
- No console errors

**Pass Criteria**:
- [ ] No floating notification
- [ ] Page functions normally
- [ ] No JavaScript errors
- [ ] API returns count: 0

---

### Test Case 6: Multiple Products Loading

**Objective**: Verify behavior with many low stock products

**Steps**:
1. Set 10+ products to have low stock:
```sql
UPDATE nexopos_products_unit_quantities 
SET 
    stock_alert_enabled = 1,
    low_quantity = 50,
    quantity = 10
WHERE product_id IN (1,2,3,4,5,6,7,8,9,10);
```
2. Navigate to procurement create page
3. Click "Load Products"

**Expected Result**:
- All products load successfully
- UI remains responsive
- Products tab displays all items
- Scrollbar appears if needed

**Pass Criteria**:
- [ ] All products loaded
- [ ] No performance degradation
- [ ] UI remains responsive
- [ ] Success message shows correct count

---

### Test Case 7: Notification Persistence

**Objective**: Verify notification behavior on page refresh

**Steps**:
1. Navigate to procurement create page
2. Note notification appears
3. Refresh the page (F5)

**Expected Result**:
- Notification appears again after refresh
- Same products shown
- Functionality works as before

**Pass Criteria**:
- [ ] Notification reappears
- [ ] Same products available
- [ ] All functionality intact

---

### Test Case 8: Edit Procurement Page

**Objective**: Verify notification also works on edit page

**Steps**:
1. Create a procurement
2. Navigate to edit that procurement
3. Observe notification behavior

**Expected Result**:
- Notification should appear (if low stock products exist)
- Behavior identical to create page
- Products can be added to existing procurement

**Pass Criteria**:
- [ ] Notification appears on edit page
- [ ] Products can be loaded
- [ ] No conflicts with existing products

---

### Test Case 9: Permission-Based Access

**Objective**: Verify feature respects user permissions

**Steps**:
1. Test as user with procurement create permission
2. Test as user without procurement permission

**Expected Result**:
- Users with permission: Feature works normally
- Users without permission: Cannot access procurement page

**Pass Criteria**:
- [ ] Permission checks work
- [ ] API endpoint respects permissions
- [ ] Appropriate error messages if unauthorized

---

### Test Case 10: Mobile Responsiveness

**Objective**: Verify feature works on mobile devices

**Steps**:
1. Open NexoPOS on mobile device or use browser DevTools mobile view
2. Navigate to procurement create page
3. Test notification and buttons

**Expected Result**:
- Notification appears in appropriate location
- Buttons are easily tappable
- Text is readable
- No layout issues

**Pass Criteria**:
- [ ] Notification visible on mobile
- [ ] Buttons tap-friendly (min 44px)
- [ ] Text readable without zoom
- [ ] No horizontal scroll

---

## Performance Testing

### Load Time Test

**Objective**: Measure impact on page load time

**Steps**:
1. Open browser DevTools → Network tab
2. Navigate to procurement create page
3. Note the API call timing

**Metrics to Collect**:
- Time to first notification appearance: ______ ms
- API response time: ______ ms
- Total page load time: ______ ms

**Acceptable Ranges**:
- API response: < 500ms
- Notification display: < 2 seconds after entities load
- No significant delay in page load

---

### Stress Test

**Objective**: Test with maximum products (50)

**Steps**:
1. Create 50+ products with low stock
2. Navigate to procurement create
3. Click "Load Products"

**Metrics to Collect**:
- Time to load all products: ______ ms
- Browser memory usage: ______ MB
- Any UI freezing: Yes/No

**Acceptable Ranges**:
- Load time: < 3 seconds
- No browser freezing
- Smooth scrolling in products list

---

## Error Scenarios

### Test Error Case 1: API Failure

**Objective**: Verify graceful handling of API errors

**Setup**: Temporarily break the API endpoint or simulate network failure

**Expected Result**:
- No notification appears
- No error shown to user (silent failure)
- Console logs error for debugging
- Procurement page remains functional

---

### Test Error Case 2: Invalid Product Data

**Objective**: Verify handling of malformed data

**Setup**: Modify API to return incomplete product data

**Expected Result**:
- Products with valid data are processed
- Invalid products are skipped
- No JavaScript errors thrown
- User sees appropriate message

---

## Browser Compatibility

Test on these browsers:

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

## Regression Testing

Verify these existing features still work:

- [ ] Manual product search and add
- [ ] Product quantity editing
- [ ] Unit selection
- [ ] Tax selection
- [ ] Procurement submission
- [ ] Product deletion from list
- [ ] Tab switching
- [ ] Form validation

## Bug Report Template

If issues are found, report using this template:

```
**Bug Title**: [Brief description]

**Severity**: Critical / High / Medium / Low

**Test Case**: [Which test case failed]

**Steps to Reproduce**:
1. 
2. 
3. 

**Expected Result**:


**Actual Result**:


**Screenshots/Videos**:
[Attach if applicable]

**Console Errors**:
[Paste JavaScript errors]

**Environment**:
- Browser: 
- OS: 
- NexoPOS Version: 
- Screen Resolution: 

**Additional Notes**:

```

## Sign-Off

After completing all tests:

**Tester Name**: ______________________

**Test Date**: ______________________

**Test Environment**: ______________________

**Overall Result**: PASS / FAIL / PARTIAL

**Critical Issues Found**: ______________________

**Recommendations**: ______________________

---

## Automated Testing (Optional)

For developers, create automated tests:

```php
// tests/Feature/LowStockSuggestionsTest.php

public function test_api_returns_low_stock_products()
{
    // Create product with low stock
    $product = $this->createProductWithLowStock();
    
    // Call API
    $response = $this->getJson('/api/procurements/low-stock-suggestions');
    
    // Assert
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'status',
                 'data' => [
                     '*' => ['id', 'name', 'sku', 'unit_quantities']
                 ],
                 'count'
             ]);
}
```

---

## Conclusion

This comprehensive testing guide ensures the low stock suggestion feature:
- Works correctly across all scenarios
- Performs well under load
- Handles errors gracefully
- Maintains compatibility
- Doesn't break existing functionality
