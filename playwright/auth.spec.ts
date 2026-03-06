import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage';

/**
 * Authentication spec
 *
 * These tests exercise the sign-in page directly (no pre-saved auth state)
 * so they are intentionally excluded from the 'chromium' project which uses
 * stored state. Run them separately when needed:
 *
 *   npx playwright test playwright/auth.spec.ts --project=chromium
 *
 * NOTE: The project-level setup (auth.setup.ts) still handles saving session
 * state. These tests use a fresh browser context each time.
 */

test.describe('Sign-in page', () => {

    test('displays the login form', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();

        await expect(loginPage.usernameInput).toBeVisible();
        await expect(loginPage.passwordInput).toBeVisible();
        await expect(loginPage.submitButton).toBeVisible();
    });

    test('shows a "forgot password" link', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();

        await expect(loginPage.forgotPasswordLink).toBeVisible();
        await expect(loginPage.forgotPasswordLink).toHaveAttribute('href', /password-lost/);
    });

    test('rejects wrong credentials', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();

        await loginPage.login('wronguser', 'wrongpassword');

        // Expect either an error notification or staying on the sign-in page
        await page.waitForTimeout(1_500);
        const url = page.url();
        const stayedOnLogin = url.includes('sign-in') || !url.includes('dashboard');
        expect(stayedOnLogin).toBe(true);
    });

    test('redirects to /dashboard on successful login', async ({ page }) => {
        const username = process.env.TEST_USERNAME || 'admin';
        const password = process.env.TEST_PASSWORD || 'admin123';

        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.loginAndWaitForDashboard(username, password);

        await expect(page).toHaveURL(/\/dashboard/);
    });
});
