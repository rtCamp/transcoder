/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

test.describe('Validate Transcoder Settings', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage('/');
    });
    test('Check Transcoder settings Options', async ({ admin, page, editor }) => {

        await page.locator("#toplevel_page_rt-transcoder > a > div.wp-menu-name").click();
        // Check Lisence key Settings
        const licenseSettings = page.locator("input[id='new-api-key']")
        expect(licenseSettings).not.toBeNull();
        await page.focus("input[id='new-api-key']");
        await licenseSettings.fill("8c1a107c6c89bd9dda666a635f441890");
        await page.locator("button[id='api-key-submit']").click();

        //verify Save key
        await expect(page.locator("div[class='updated']")).not.toBeNull()
        await page.locator("#submit").click();

        // Check Feature plan
        const settingBox = page.locator("div[id='transcoder-settings-boxes']")
        expect(settingBox).not.toBeNull();

        // verify Free plan by checking button is disabled
        const checkButton = page.locator("button[class='button button-primary bpm-unsubscribe']").isDisabled();
        expect(checkButton).toBeTruthy();

        // Check thumbnail Settings
        const thumbnailSetting = page.locator('input[name="number_of_thumbs"]');
        expect(thumbnailSetting).not.toBeNull();
        // Check Transcoder Usage 
        const activeUsage = page.locator("div[id='transcoder-usage']");
        expect(activeUsage).not.toBeNull();
        // Validate Checkboxes
        const thumbnailCheckbox = await page.locator("input[name='rtt_override_thumbnail']").isChecked();
        const trackUserprofile = await page.locator("input[name='rtt_client_check_status_button']").isChecked();

        // Ensure Checkbox are checked for checking transcoding status for next test case
        if (thumbnailCheckbox === false) {
            await page.locator("input[name='rtt_override_thumbnail']").check();
        }

        if (trackUserprofile === false) {
            await page.locator("input[name='rtt_client_check_status_button']").check();
        }
        // Save settings and verify
        await page.locator("#submit").click();
        await expect(page.locator("div[class='updated']")).not.toBeNull()

    });
});