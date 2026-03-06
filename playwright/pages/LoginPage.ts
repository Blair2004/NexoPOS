import { type Page, type Locator, expect } from '@playwright/test';

/**
 * Page Object Model for the NexoPOS sign-in page (/sign-in).
 *
 * The login form is rendered by the ns-login Vue component.
 * Each field uses :id="field.name", so #username and #password
 * are stable selectors.
 */
export class LoginPage {
    readonly page: Page;
    readonly usernameInput: Locator;
    readonly passwordInput: Locator;
    readonly submitButton: Locator;
    readonly forgotPasswordLink: Locator;

    constructor(page: Page) {
        this.page = page;
        this.usernameInput    = page.locator('#username');
        this.passwordInput    = page.locator('#password');
        this.submitButton     = page.locator('ns-button, button').filter({ hasText: /sign.?in|login/i }).first();
        this.forgotPasswordLink = page.locator('a[href*="password-lost"]');
    }

    async goto() {
        await this.page.goto('/sign-in');
        await this.usernameInput.waitFor({ state: 'visible' });
    }

    async login(username: string, password: string) {
        await this.usernameInput.fill(username);
        await this.passwordInput.fill(password);
        await this.submitButton.click();
    }

    async loginAndWaitForDashboard(username: string, password: string) {
        await this.login(username, password);
        await this.page.waitForURL(/\/dashboard/, { timeout: 15_000 });
    }

    async expectErrorVisible() {
        // NexoPOS shows validation errors in ns-alert elements
        const alert = this.page.locator('.ns-alert, [class*="error"], .text-error-primary').first();
        await expect(alert).toBeVisible({ timeout: 5_000 });
    }
}
