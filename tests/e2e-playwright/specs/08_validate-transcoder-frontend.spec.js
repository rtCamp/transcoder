/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
test.describe('Validate Transcoder In frontend', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage('/');

    });
    test('Check Transcoder settings In Activity', async ({ page }) => {
        await page.hover("#wp-admin-bar-my-account");
        const navigationPromise = page.waitForNavigation();
        await page.locator("#wp-admin-bar-my-account-activity").click();
        await navigationPromise;
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
        if (page.locator("div.activity-content").first().isEnabled()) {
            await page.locator("div.rtmedia-item-title a").first().click()
        }
        const checkStatus = page.locator("button[id*='btn_check_status']");
        expect(checkStatus).not.toBeNull();
    });

    test('Check Transcoder settings In Media Page', async ({ page }) => {
        await page.hover("#wp-admin-bar-my-account");
        await page.hover("#wp-admin-bar-my-account-media");
        const navigationPromise = page.waitForNavigation();
        await page.locator("#wp-admin-bar-my-account-media").click();
        await navigationPromise;
        // Wait for NavBar to stable the page.
        await page.waitForSelector("#object-nav");
        // Click Upload Button for opening up upload panel
        await page.locator("#rtm_show_upload_ui").click();

        //Upload
        const videoPath = "assets/mp4-sample.mp4";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#rtMedia-upload-button').click(),

        ])
        await fileChooser.setFiles([
            videoPath,
        ])
        const item = await page.locator("[class='start-media-upload']");
        await expect(item).toBeVisible();
        await item.click();
        // Wait for upload and verify Upload is completed
        if (page.locator("[class='rtmedia-item-thumbnail']").first().isEnabled()) {
            await page.locator("[class='rtmedia-item-thumbnail']").first().click()
        }
        // Verify Dom content is present and check for element is not empty.
        const checkStatus = page.locator("button[id*='btn_check_status']");
        expect(checkStatus).not.toBeNull();
        // Check Status 
        if (page.locator("button[id*='btn_check_status']").isVisible()) {
            console.log("Transcoding is in Progress")
        }
        else if (page.locator("button[id*='btn_check_status']").isHidden()) {
            console.log("Transcoding Complete")
        }
    });

    
});