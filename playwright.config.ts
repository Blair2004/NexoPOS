import { defineConfig, devices } from '@playwright/test';
import { config } from 'dotenv';

/**
 * Load Laravel's .env file so variables like APP_URL, TEST_USERNAME,
 * and TEST_PASSWORD are available to Playwright without needing to
 * export them manually in the shell first.
 */
config(); // reads .env from the project root

/**
 * NexoPOS Playwright Configuration
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
    testDir: './playwright',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ],
    use: {
        baseURL: process.env.APP_URL || 'http://localhost:8000',
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'retain-on-failure',
        headless: true,
    },
    projects: [
        /**
         * Authentication setup — runs first to save session state.
         * Other projects depend on this to skip re-logging-in.
         */
        {
            name: 'setup',
            testMatch: /.*\.setup\.ts/,
        },

        /**
         * Chromium - main browser for all tests.
         * Uses previously saved authentication state.
         */
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },
    ],
    outputDir: 'playwright-results/',
});
