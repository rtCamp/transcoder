/**
* WordPress dependencies
*/
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const { setTimeout } = require('timers');
const { TransCodeStatus } = require("../utils/locator.js");
test.describe('Validate 3gp File Upload and assert All transcoded Status', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage("media-new.php");
    });
    test('Check 3gp sample and verify all Transcoder processing Steps', async ({ admin, page, editor }) => {
        // Upload Video
        const videoPath = "assets/3gp-sample.3gp";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#plupload-browse-button').click(),

        ])
        await fileChooser.setFiles([
            videoPath,
        ])
        // Check Upload is completed 
        const item = await page.locator("#wpbody-content > div.wrap > h1");
        await expect(item).toBeVisible();

        // Check Copy to clipboard is working
        const copyButton = "button[class='button button-small copy-attachment-url']";
        if (await page.locator(copyButton).isEnabled()) {
            await page.click(copyButton)
        }
        // Goto Media and Check for Check status Button visibility 
        await admin.visitAdminPage("upload.php");
        //Select Grid Type
        await page.locator("a[id='view-switch-list']").click();
        const checkStatus = page.locator("button[id^='btn_check_status']").first();
        expect(checkStatus).not.toBeNull();
        await checkStatus.click();
        const checkMessage = page.locator("div[id*='span_status']").first();
        expect(checkMessage).not.toBeNull();

        // Check For Transcoding status and wait until File is getting transcoded
        await checkStatus.click();
        await page.focus("div[id*='span_status']")
        await page.waitForSelector("div[id*='span_status']");
        const tweets = page.locator("div[id*='span_status']");
        var result = await tweets.evaluate(node => node.innerText);
        var _hasTimeElasped = false;
        setTimeout(() => {
            _hasTimeElasped = true;
            console.log("Time Elapsed")
        }, 90000)
        // Loop To Assert Updated Messages
        while (result == TransCodeStatus.Processing || result == TransCodeStatus.Queue || TransCodeStatus.ServerReady) {
            // Loop Breaker After Timeout
            if (_hasTimeElasped) {
                break;
            }
            await checkStatus.click();
            await page.focus("div[id*='span_status']")
            await page.waitForSelector("div[id*='span_status']");
            const tweets = page.locator("div[id*='span_status']");
            result = await tweets.evaluate(node => node.innerText);
            console.log("Inside Loop:", result);
            if (result == TransCodeStatus.Completed || result == TransCodeStatus.Error) {
                break;
            }
        }
        // Delete The media to Execute the next Test cases
        await page.locator("td.title.column-title.has-row-actions.column-primary ").first().hover();
        await page.locator("td.title.column-title.has-row-actions.column-primary > div > span.edit").first().click();
        // Wait for New page to load 
        await page.waitForSelector("#title");
        await page.waitForSelector("#attachment_caption");
        await expect(page).toHaveURL(/action=edit/)
        // Delete media After testing
        page.on('dialog', dialog => dialog.accept());
        await page.locator("#delete-action > a").click();
    });
});