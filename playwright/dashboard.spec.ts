import { test, expect } from '@playwright/test';
import { DashboardPage } from './pages/DashboardPage';

/**
 * Dashboard spec
 *
 * These tests run with the pre-saved authentication state produced
 * by auth.setup.ts, so no login step is needed.
 */

test.describe('Dashboard', () => {

    test('authenticated user can access /dashboard', async ({ page }) => {
        const dashboard = new DashboardPage(page);
        await dashboard.goto();
        await dashboard.expectLoaded();
    });

    test('page title or heading is visible', async ({ page }) => {
        const dashboard = new DashboardPage(page);
        await dashboard.goto();
        await dashboard.waitForHydration();

        // At least one heading element should be present after the Vue app loads
        const heading = page.locator('h1, h2, h3').first();
        await expect(heading).toBeVisible({ timeout: 8_000 });
    });

    test('sidebar / navigation is rendered', async ({ page }) => {
        const dashboard = new DashboardPage(page);
        await dashboard.goto();
        await dashboard.waitForHydration();

        // The aside menu should be present in the DOM
        const aside = page.locator('aside, nav, [class*="aside"]').first();
        await expect(aside).toBeVisible({ timeout: 8_000 });
    });

    test('unauthenticated request for /dashboard redirects to sign-in', async ({ browser }) => {
        // Use a brand-new context with no cookies – simulates a logged-out user
        const ctx  = await browser.newContext();
        const page = await ctx.newPage();

        await page.goto('/dashboard');

        // NexoPOS redirects unauthenticated users to /sign-in
        await page.waitForURL(/sign-in|login/, { timeout: 10_000 });
        expect(page.url()).toMatch(/sign-in|login/);

        await ctx.close();
    });
});

test.describe('Dashboard – sub-sections', () => {

    test('products list page loads', async ({ page }) => {
        const dashboard = new DashboardPage(page);
        await dashboard.navigateTo('products');
        await dashboard.waitForHydration();

        // ns-crud table component should be in the DOM
        const crudTable = page.locator('ns-crud, [class*="crud"]').first();
        await expect(crudTable).toBeVisible({ timeout: 10_000 });
    });

    test('orders list page loads', async ({ page }) => {
        const dashboard = new DashboardPage(page);
        await dashboard.navigateTo('orders');
        await dashboard.waitForHydration();

        const crudTable = page.locator('ns-crud, [class*="crud"]').first();
        await expect(crudTable).toBeVisible({ timeout: 10_000 });
    });

    test('customers list page loads', async ({ page }) => {
        const dashboard = new DashboardPage(page);
        await dashboard.navigateTo('customers');
        await dashboard.waitForHydration();

        const crudTable = page.locator('ns-crud, [class*="crud"]').first();
        await expect(crudTable).toBeVisible({ timeout: 10_000 });
    });
});
