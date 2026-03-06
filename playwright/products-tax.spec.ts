import { test, expect } from '@playwright/test';

/**
 * Product Tax Configuration Tests
 *
 * These tests verify that product unit prices and tax settings can be
 * configured correctly through the product edit form.
 *
 * Test product: id=1 ("Twin Comforter Sets"), tax_group_id=1, tax_type="exclusive"
 */

// Run tests serially to avoid race conditions when multiple workers edit the same product
test.describe.configure({ mode: 'serial' });

test.describe('Product Tax Configuration', () => {
    const PRODUCT_EDIT_URL = '/dashboard/products/edit/1';

    test.beforeEach(async ({ page }) => {
        // Navigate to the product edit page directly
        await page.goto(PRODUCT_EDIT_URL);

        // Wait for the CRUD form tabs to load (give extra time for the Vue app to boot)
        await page.waitForSelector('.ns-tab', { timeout: 30000 });
    });

    test('should display product edit form with tabs', async ({ page }) => {
        // Verify expected tabs are visible
        await expect(page.locator('.ns-tab .tab', { hasText: 'Units' })).toBeVisible();
        await expect(page.locator('.ns-tab .tab', { hasText: 'Taxes' })).toBeVisible();
    });

    test('should set unit prices to 100 on the Units tab', async ({ page }) => {
        // Click the "Units" tab
        await page.locator('.ns-tab .tab', { hasText: 'Units' }).click();

        // Wait for the Units tab content to be active
        await page.waitForSelector('#sale_price_edit', { timeout: 10000 });

        // Fill sale price with 100 (first selling group row)
        const salePriceInput = page.locator('#sale_price_edit').first();
        await salePriceInput.fill('');
        await salePriceInput.fill('100');

        // Fill wholesale price with 100 (first selling group row)
        const wholesalePriceInput = page.locator('#wholesale_price_edit').first();
        await wholesalePriceInput.fill('');
        await wholesalePriceInput.fill('100');

        // Verify the values were set
        await expect(salePriceInput).toHaveValue('100');
        await expect(wholesalePriceInput).toHaveValue('100');
    });

    test('should set tax group and inclusive tax type on the Taxes tab', async ({ page }) => {
        // Click the "Taxes" tab
        await page.locator('.ns-tab .tab', { hasText: 'Taxes' }).click();

        // Wait for the Taxes tab content to be active
        await page.waitForSelector('select[name="tax_group_id"]', { timeout: 10000 });

        // Select the first available tax group (index 1 skips the "Choose an option" placeholder)
        await page.selectOption('select[name="tax_group_id"]', { index: 1 });

        // Set tax type to "Inclusive" using the native select
        await page.selectOption('select[name="tax_type"]', 'inclusive');

        // Verify "Inclusive" is selected
        await expect(page.locator('select[name="tax_type"]')).toHaveValue('inclusive');
    });

    test('should save product with updated unit prices and inclusive tax', async ({ page }) => {
        // --- Units tab: set prices ---
        await page.locator('.ns-tab .tab', { hasText: 'Units' }).click();
        await page.waitForSelector('#sale_price_edit', { timeout: 10000 });

        const salePriceInput = page.locator('#sale_price_edit').first();
        await salePriceInput.fill('');
        await salePriceInput.fill('100');

        const wholesalePriceInput = page.locator('#wholesale_price_edit').first();
        await wholesalePriceInput.fill('');
        await wholesalePriceInput.fill('100');

        // --- Taxes tab: set tax group and tax type ---
        await page.locator('.ns-tab .tab', { hasText: 'Taxes' }).click();
        await page.waitForSelector('select[name="tax_group_id"]', { timeout: 10000 });

        // Select the first tax group
        await page.selectOption('select[name="tax_group_id"]', { index: 1 });

        // Switch to inclusive
        await page.selectOption('select[name="tax_type"]', 'inclusive');

        // --- Save ---
        await page.locator('button', { hasText: 'Save' }).first().click();

        // Wait for the info snackbar (CRUD form shows nsSnackBar.info after PUT)
        await expect(page.locator('.ns-notice.info')).toBeVisible({
            timeout: 10000,
        });
    });

    test('should persist tax settings after saving and reloading', async ({ page }) => {
        // --- Apply inclusive tax settings ---
        await page.locator('.ns-tab .tab', { hasText: 'Units' }).click();
        await page.waitForSelector('#sale_price_edit', { timeout: 10000 });

        await page.locator('#sale_price_edit').first().fill('100');
        await page.locator('#wholesale_price_edit').first().fill('100');

        await page.locator('.ns-tab .tab', { hasText: 'Taxes' }).click();
        await page.waitForSelector('select[name="tax_group_id"]', { timeout: 10000 });

        await page.selectOption('select[name="tax_group_id"]', { index: 1 });
        await page.selectOption('select[name="tax_type"]', 'inclusive');

        await page.locator('button', { hasText: 'Save' }).first().click();

        // Wait for the info snackbar (CRUD form shows nsSnackBar.info after PUT)
        await expect(page.locator('.ns-notice.info')).toBeVisible({
            timeout: 10000,
        });

        // Reload and verify values were persisted
        await page.goto(PRODUCT_EDIT_URL);
        await page.waitForSelector('.ns-tab', { timeout: 15000 });

        // Check Taxes tab persisted values
        await page.locator('.ns-tab .tab', { hasText: 'Taxes' }).click();
        await page.waitForSelector('select[name="tax_group_id"]', { timeout: 10000 });

        // Tax group should still be selected (value should not be empty)
        const taxGroupValue = await page.locator('select[name="tax_group_id"]').inputValue();
        expect(taxGroupValue).not.toBe('');
        expect(taxGroupValue).not.toBe('null');

        // Tax type should be "Inclusive"
        await expect(page.locator('select[name="tax_type"]')).toHaveValue('inclusive');
    });

    test('should reset tax type back to exclusive', async ({ page }) => {
        // Navigate to Taxes tab
        await page.locator('.ns-tab .tab', { hasText: 'Taxes' }).click();
        await page.waitForSelector('select[name="tax_type"]', { timeout: 10000 });

        // Switch to exclusive
        await page.selectOption('select[name="tax_type"]', 'exclusive');

        // Verify "Exclusive" is selected
        await expect(page.locator('select[name="tax_type"]')).toHaveValue('exclusive');

        // Save
        await page.locator('button', { hasText: 'Save' }).first().click();

        await expect(page.locator('.ns-notice.info')).toBeVisible({
            timeout: 10000,
        });
    });
});
