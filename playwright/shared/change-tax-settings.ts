import { expect } from "@playwright/test";

export default async function changeTaxSettings( page, index = 1 ) {
    /**
     * click on a link that text includes "Settings".
     */
    await page.click('a:has-text("Settings"), a:has-text("Configuration")');

    /**
     * move the mouse over the element "#menu-dashboard" and scroll down until you see "POS"
     */
    await page.hover('#menu-dashboard');
    await page.waitForSelector('a:has-text("POS")', { state: 'visible' });
    await page.click('li.submenu>a:has-text("POS")');
    await page.waitForURL(/\/dashboard\/settings\/pos/, { timeout: 15_000 });
    await expect(page).toHaveURL(/\/dashboard\/settings\/pos/);

    /**
     * Click on tab having text "VAT Settings"
     */
    await page.click('span:has-text("VAT Settings")');

    /**
     * We should see input with this attribute name="ns_pos_vat"
     */
    await page.waitForSelector('select[name="ns_pos_vat"]', { state: 'visible' });

    /**
     * We should clic on that and select the second options.
     */
    await page.click('select[name="ns_pos_vat"]');
    await page.selectOption('select[name="ns_pos_vat"]', { index });

    /**
     * Click on the save button
     */
    await page.click('button:has-text("Save Settings"), ns-button:has-text("Save")');

    /**
     * We should see a message having "The form has been"
     */
    await page.waitForSelector('text=The form has been', { state: 'visible' });
}