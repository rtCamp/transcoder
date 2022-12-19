/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const SITE_URL = 'http://transcoder.com/activity/';
test.describe('Validate Transcoder Settings', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage('/');
        
    });
    test('Check Transcoder settings In frontend', async ({ admin, page, editor }) => {
        
        await page.goto(SITE_URL, {waitUntil:'load'});
        await page.locator("#whats-new").click();
        //Upload
        const videoPath = "assets/3gp-sample.3gp";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#rtmedia-add-media-button-post-update').click(),

        ])
        await fileChooser.setFiles([
            videoPath,
        ])
        const item = await page.locator("#aw-whats-new-submit");
        await expect(item).toBeVisible();
        await item.click();
        // Wait for upload
        if (page.locator("div.activity-content").first().isEnabled())
        {
            await page.locator("div.rtmedia-item-title a").first().click()   
        }
        const checkStatus = page.locator("button[id*='btn_check_status']");
        expect(checkStatus).not.toBeNull();
    });
});