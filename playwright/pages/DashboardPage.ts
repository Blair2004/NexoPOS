import { type Page, type Locator, expect } from '@playwright/test';

/**
 * Page Object Model for the NexoPOS Dashboard (/dashboard).
 */
export class DashboardPage {
    readonly page: Page;

    // Sidebar navigation
    readonly sidebar: Locator;
    readonly sidebarToggle: Locator;

    // Common dashboard elements
    readonly pageTitle: Locator;

    constructor(page: Page) {
        this.page         = page;
        this.sidebar      = page.locator('aside, nav, .ns-aside, [class*="sidebar"]').first();
        this.sidebarToggle = page.locator('[class*="toggle"], button[aria-label*="menu"]').first();
        this.pageTitle    = page.locator('h1, h2, h3, .page-title, .ns-title').first();
    }

    async goto() {
        await this.page.goto('/dashboard');
        await this.page.waitForURL(/\/dashboard/);
        await this.page.waitForLoadState('networkidle');
    }

    async expectLoaded() {
        await expect(this.page).toHaveURL(/\/dashboard/);
    }

    /**
     * Navigate to a dashboard sub-section via direct URL.
     */
    async navigateTo(path: string) {
        await this.page.goto(`/dashboard/${path}`);
        await this.page.waitForLoadState('networkidle');
    }

    /**
     * Wait for the Vue app to finish hydrating (no active XHR/fetch).
     */
    async waitForHydration() {
        await this.page.waitForLoadState('networkidle');
    }
}
