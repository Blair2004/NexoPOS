import { test as setup, expect } from '@playwright/test';
import path from 'path';

/**
 * Authentication setup — logs in once and saves the session to disk.
 * All other test projects depend on this setup so they reuse the
 * stored session instead of re-logging-in for every spec.
 */

const authFile = path.join(import.meta.dirname, '.auth/user.json');

setup('authenticate as admin', async ({ page, request }) => {

    const username = process.env.TEST_USERNAME || 'admin';
    const password = process.env.TEST_PASSWORD || 'admin123';
    const baseUrl  = process.env.APP_URL || 'https://nexocloud-v6.dev';

    /**
     * 1 – Navigate to the sign-in page and fill the Vue-rendered form.
     *     The ns-input component sets :id="field.name", so we can use
     *     #username and #password as stable selectors.
     */
    await page.goto('/sign-in');

    // Wait for the Vue app to hydrate the form fields
    await page.waitForSelector('#username', { state: 'visible' });

    await page.fill('#username', username);
    await page.fill('#password', password);

    /**
     * 2 – Click the sign-in button and wait for navigation
     *     to the dashboard to confirm a successful login.
     */
    await page.click('button:has-text("Sign In"), button:has-text("Login"), ns-button:has-text("Sign In")');

    await page.waitForURL(/\/dashboard/, { timeout: 15_000 });
    await expect(page).toHaveURL(/\/dashboard/);

    /**
     * 3 – Persist the authenticated state (cookies + localStorage)
     *     so dependent projects don't need to log in again.
     */
    await page.context().storageState({ path: authFile });
});
