import { test, expect } from '@playwright/test';

/**
 * Point-of-Sale spec
 *
 * Covers the POS interface at /dashboard/pos.
 * Runs with the pre-saved auth state from auth.setup.ts.
 */

test.describe('POS interface', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('/dashboard/pos');
        // Wait for the Vue POS app to fully load
        await page.waitForLoadState('networkidle');
    });

    test('POS page navigates successfully', async ({ page }) => {
        await expect(page).toHaveURL(/\/dashboard\/pos/);
    });

    test('product grid is rendered', async ({ page }) => {
        // The product grid component ns-pos-grid should be visible
        const grid = page.locator('ns-pos-grid, [class*="pos-grid"], [id*="pos-grid"]').first();
        await expect(grid).toBeVisible({ timeout: 15_000 });
    });

    test('cart is rendered', async ({ page }) => {
        const cart = page.locator(
            'ns-pos-cart, [class*="pos-cart"], [class*="cart"]'
        ).first();
        await expect(cart).toBeVisible({ timeout: 15_000 });
    });

    test('keyboard shortcut: payment button is accessible', async ({ page }) => {
        // The payment button or shortcut chip should exist somewhere in the POS UI
        const paymentTrigger = page.locator(
            'button:has-text("Payment"), ns-button:has-text("Payment"), [class*="payment"]'
        ).first();
        await expect(paymentTrigger).toBeVisible({ timeout: 15_000 });
    });
});
