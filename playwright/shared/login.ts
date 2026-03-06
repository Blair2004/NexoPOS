import { expect } from "@playwright/test";

export default async function login( page ) {
    const username = process.env.TEST_USERNAME || 'admin';
    const password = process.env.TEST_PASSWORD || 'admin123';
    const baseUrl  = process.env.APP_URL || 'http://localhost:8000';

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
}