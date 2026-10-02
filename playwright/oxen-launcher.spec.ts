import { expect, Page, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

type LauncherPreference = {
  icon: { edge: 'left' | 'right' | 'top' | 'bottom'; offset_ratio: number };
  popup: { x_ratio: number; y_ratio: number };
};

test.use({ storageState: { cookies: [], origins: [] } });

async function mockOxen(
  page: Page,
  savedPreferences: LauncherPreference[],
  messageRequests: string[] = []
): Promise<void> {
  const lastActivity = new Date(Date.now() - 10 * 60 * 1000).toISOString();
  const buildDirectory = resolve('modules/NsOxen/Public/build');
  const manifest = JSON.parse(readFileSync(resolve(buildDirectory, '.vite/manifest.json'), 'utf8'));
  const launcherFile = manifest['Resources/ts/launcher.ts'].file as string;
  const launcherChunk = readFileSync(resolve(buildDirectory, launcherFile));
  const importedChunkName = launcherFile.match(/launcher-[^/]+\.js$/)
    ? manifest['Resources/ts/launcher.ts'].imports[0]
    : null;
  const importedChunk = importedChunkName ? (manifest[importedChunkName].file as string) : '';

  await page.addInitScript(() => sessionStorage.clear());
  await page.route('**/dashboard', (route) =>
    route.fulfill({
      contentType: 'text/html',
      body: `<!doctype html>
            <html lang="en">
                <head><link rel="stylesheet" href="/__oxen/style.css"></head>
                <body>
                    <div id="ns-oxen-root"></div>
                    <script type="module">
                        import * as Vue from '/__oxen/vue.js';
                        globalThis.NexoPOSVue = Vue;
                        globalThis.ns = { vue: Vue, theme: { value: 'light' } };
                        globalThis.__m = text => text;
                        globalThis.nsSnackBar = { error() {}, success() {} };
                        globalThis.Popup = { show() {} };
                        globalThis.nsConfirmPopup = {};
                        globalThis.nsHttpClient = {};
                        for (const method of ['get', 'post', 'put', 'delete']) {
                            globalThis.nsHttpClient[method] = (url, data) => ({
                                subscribe(observer) {
                                    fetch(url, {
                                        method: method.toUpperCase(),
                                        headers: { 'Content-Type': 'application/json' },
                                        body: ['post', 'put'].includes(method) ? JSON.stringify(data ?? {}) : undefined,
                                    }).then(response => response.json()).then(observer.next).catch(observer.error);
                                    return { unsubscribe() {} };
                                },
                            });
                        }
                        await import('/__oxen/launcher.js');
                    </script>
                </body>
            </html>`
    })
  );
  await page.route('**/__oxen/vue.js', (route) =>
    route.fulfill({
      contentType: 'application/javascript',
      body: readFileSync(resolve('node_modules/vue/dist/vue.esm-browser.prod.js'))
    })
  );
  await page.route('**/__oxen/launcher.js', (route) =>
    route.fulfill({ contentType: 'application/javascript', body: launcherChunk })
  );
  await page.route(`**/__oxen/${importedChunk.split('/').at(-1)}`, (route) =>
    route.fulfill({ contentType: 'application/javascript', body: readFileSync(resolve(buildDirectory, importedChunk)) })
  );
  await page.route('**/__oxen/style.css', (route) =>
    route.fulfill({
      contentType: 'text/css',
      body: readFileSync(resolve(buildDirectory, manifest['Resources/css/style.css'].file))
    })
  );
  await page.route('**/api/oxen/**', async (route) => {
    const request = route.request();
    const path = new URL(request.url()).pathname;

    if (path === '/api/oxen/bootstrap') {
      await route.fulfill({
        json: {
          configured: true,
          enabled: true,
          can_manage: false,
          preference: {
            icon: { edge: 'right', offset_ratio: 0.82 },
            popup: { x_ratio: 1, y_ratio: 1 }
          }
        }
      });
      return;
    }

    if (path === '/api/oxen/tools') {
      await route.fulfill({ json: [] });
      return;
    }

    if (path === '/api/oxen/csrf-token') {
      await route.fulfill({ json: { token: 'test-csrf-token' } });
      return;
    }

    if (path === '/api/oxen/conversations' && request.method() === 'GET') {
      await route.fulfill({
        json: [
          {
            public_id: 'conversation-1',
            title: 'A long conversation title that must remain aligned and truncate cleanly',
            status: 'active',
            last_activity_at: lastActivity
          }
        ]
      });
      return;
    }

    if (path === '/api/oxen/conversations/conversation-1') {
      await route.fulfill({ json: { conversation: { public_id: 'conversation-1' }, messages: [] } });
      return;
    }

    if (path === '/api/oxen/conversations/conversation-1/messages' && request.method() === 'POST') {
      messageRequests.push(request.postDataBuffer()?.toString('utf8') ?? '');
      await route.fulfill({
        contentType: 'text/event-stream',
        body: 'event: message.accepted\ndata: {"attachments":[{"id":"attachment-1","name":"sample-products.csv","mime_type":"text/csv","size":24}]}\n\nevent: text.delta\ndata: {"delta":"I can read the CSV."}\n\nevent: turn.completed\ndata: {"message_id":1}\n\n'
      });
      return;
    }

    if (path === '/api/oxen/launcher-preference' && request.method() === 'PUT') {
      const preference = request.postDataJSON() as LauncherPreference;
      savedPreferences.push(preference);
      await route.fulfill({ json: preference });
      return;
    }

    await route.fulfill({ status: 404, json: {} });
  });
}

test.describe('Oxen launcher positioning', () => {
  test('submits attachment bytes as multipart data and clears them after acceptance', async ({ page }) => {
    const messageRequests: string[] = [];
    await mockOxen(page, [], messageRequests);
    await page.goto('/dashboard');
    await page.getByTestId('oxen-launcher-icon').click();
    await page.getByPlaceholder('Ask about your store…').fill('Import this file');
    await page
      .getByLabel('Attach files')
      .locator('input')
      .setInputFiles({
        name: 'sample-products.csv',
        mimeType: 'text/csv',
        buffer: Buffer.from('name,price\nCoffee,12')
      });

    await page.getByRole('button', { name: 'Send' }).click();

    await expect.poll(() => messageRequests.length).toBe(1);
    expect(messageRequests[0]).toContain('name="attachments[]"; filename="sample-products.csv"');
    expect(messageRequests[0]).toContain('name,price\nCoffee,12');
    expect(messageRequests[0]).not.toContain('[Attached:');
    await expect(page.getByText('I can read the CSV.')).toBeVisible();
    await expect(page.getByText('sample-products.csv')).toBeVisible();
  });

  test('snaps and animates the icon while preserving popup behavior and position', async ({ page }) => {
    const savedPreferences: LauncherPreference[] = [];
    await mockOxen(page, savedPreferences);
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto('/dashboard');

    const icon = page.getByTestId('oxen-launcher-icon');
    await expect(icon).toBeVisible();
    const initialIcon = await icon.boundingBox();
    expect(initialIcon?.x).toBeGreaterThan(1200);

    await page.mouse.move(initialIcon!.x + 28, initialIcon!.y + 28);
    await page.mouse.down();
    await page.mouse.move(36, 240, { steps: 8 });
    await page.mouse.up();

    await expect.poll(() => savedPreferences.length).toBe(1);
    expect(savedPreferences[0].icon.edge).toBe('left');
    expect(savedPreferences[0].popup).toEqual({ x_ratio: 1, y_ratio: 1 });
    expect((await icon.boundingBox())?.x).toBeCloseTo(12, 0);
    await expect(page.getByTestId('oxen-popup')).toBeHidden();
    await expect(icon).toHaveCSS('view-transition-name', 'ns-oxen-launcher');

    await icon.click();
    await expect(page.getByTestId('oxen-popup')).toBeVisible();
    await expect(page.getByTestId('oxen-popup')).toHaveCSS('view-transition-name', 'ns-oxen-launcher');
    await expect(icon).toBeHidden();

    await page.getByLabel('Conversation history').click();
    await expect(page.getByText(/10 minutes ago/)).toBeVisible();

    const popup = page.getByTestId('oxen-popup');
    const popupBeforeDrag = await popup.boundingBox();
    const handle = page.getByTestId('oxen-popup-drag-handle');
    const handleBox = await handle.boundingBox();
    await page.mouse.move(handleBox!.x + 16, handleBox!.y + 16);
    await page.mouse.down();
    await page.mouse.move(handleBox!.x - 260, handleBox!.y - 180, { steps: 8 });
    await page.mouse.up();

    await expect.poll(() => savedPreferences.length).toBe(2);
    expect(savedPreferences[1].icon.edge).toBe('left');
    expect(savedPreferences[1].popup.x_ratio).toBeLessThan(1);
    expect(savedPreferences[1].popup.y_ratio).toBeLessThan(1);
    const popupAfterDrag = await popup.boundingBox();
    expect(popupAfterDrag?.x).toBeLessThan(popupBeforeDrag!.x);
    expect(popupAfterDrag?.y).toBeLessThan(popupBeforeDrag!.y);

    await page.setViewportSize({ width: 700, height: 500 });
    const clampedPopup = await popup.boundingBox();
    expect(clampedPopup!.x).toBeGreaterThanOrEqual(12);
    expect(clampedPopup!.y).toBeGreaterThanOrEqual(12);
    expect(clampedPopup!.x + clampedPopup!.width).toBeLessThanOrEqual(688);
    expect(clampedPopup!.y + clampedPopup!.height).toBeLessThanOrEqual(488);

    await page.getByLabel('Close Oxen').click();
    await expect(page.getByTestId('oxen-popup')).toBeHidden();
    await expect(icon).toBeVisible();
  });

  test('keeps small movements clickable and uses fullscreen reduced-motion behavior on mobile', async ({ page }) => {
    const savedPreferences: LauncherPreference[] = [];
    await mockOxen(page, savedPreferences);
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.setViewportSize({ width: 500, height: 700 });
    await page.goto('/dashboard');

    const icon = page.getByTestId('oxen-launcher-icon');
    await expect(icon).toBeVisible();
    const iconBox = await icon.boundingBox();
    await page.mouse.move(iconBox!.x + 28, iconBox!.y + 28);
    await page.mouse.down();
    await page.mouse.move(iconBox!.x + 31, iconBox!.y + 30);
    await page.mouse.up();

    await expect(page.getByTestId('oxen-popup')).toBeVisible();
    expect(savedPreferences).toHaveLength(0);
    const popup = await page.getByTestId('oxen-popup').boundingBox();
    expect(popup).toEqual({ x: 0, y: 0, width: 500, height: 700 });
    await expect(page.getByTestId('oxen-popup-drag-handle')).toBeHidden();

    await page.getByLabel('Close Oxen').click();
    await expect(page.getByTestId('oxen-popup')).toBeHidden();
    await expect(icon).toBeVisible();
  });
});
