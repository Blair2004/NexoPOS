import { test as setup, expect } from '@playwright/test';
import path from 'path';
import login from './shared/login';
import changeTaxSettings from './shared/change-tax-settings';

setup( 'configure taxes', async ( { page, request } ) => {
    await login( page );

    /**
     * This will set tax to "Disabled"
     */
    await changeTaxSettings( page, 1 );

    /**
     * We should click on the menu "Inventory" then "Products".
     */
    await page.click('a:has-text("Inventory")');
    await page.click('a:has-text("Products")');
    await page.waitForURL(/\/dashboard\/products/, { timeout: 15_000 });
    await expect(page).toHaveURL(/\/dashboard\/products/);

    /**
     * We expect a button including as text "Options" to be visible and then clicked.
     */
    await page.click('button:has-text("Options"), ns-button:has-text("Options")');

    /**
     * We should now clic on the "a" tag including as text "Edit"
     */
    await page.click('a:has-text("Edit")');

    /**
     * We'll be redirected to a new page where we should check the existence of the element "#product-form"
     */
    await page.waitForSelector('#product-form', { state: 'visible' });

    /**
     * We'll then click on the "Units" tab and expect the XPath to be visible: //*[@id="tabbed-card"]/div[2]/div/div/div[2]
     */
    await page.waitForSelector( 'span:has-text("Units")', { state: 'visible' } );
    await page.click( 'span:has-text("Units")' );

    /**
     * Then we'll focus on "//*[@id="sale_price_edit"]" and set the value "100"
     */
    await page.waitForSelector( '#sale_price_edit' , { state: 'visible' } );
    await page.locator( '#sale_price_edit' ).first().fill( '' );
    await page.locator( '#sale_price_edit' ).first().fill( '100' );

    /** 
     * We'll click on "Taxes"
    */
    await page.click('#form-container span:has-text("Taxes")');

    /**
     * The select input with this attribute: name="tax_group_id"
     */
    await page.waitForSelector('select[name="tax_group_id"]', { state: 'visible' });

    /**
     * We should select the first option of the select input with this attribute: name="tax_group_id"
     */
    await page.selectOption('select[name="tax_group_id"]', { index: 1 });

    /**
     * We'll click on "//*[@id="product-form"]/div[1]/div[2]/button" for saving and make sure we have the text "The product has been updated"
     */
    await page.click('button:has-text("Save")');
    await page.waitForSelector('text=The product has been updated', { state: 'visible' });
});