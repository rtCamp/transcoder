 /**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const { TransCodeStatus } = require("../utils/locator.js");
test.describe('Thumbnail Scenarios', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage("media-new.php");
    });
    test('Check Thumbnail is generated after transcoding', async ({ admin, page, editor }) => {
       
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
        // Loop To Assert Updated Messages
        while (result === TransCodeStatus.Processing || TransCodeStatus.Queue || TransCodeStatus.ServerReady) {
            //await page.reload();
            await checkStatus.click();
            await page.focus("div[id*='span_status']")
            await page.waitForSelector("div[id*='span_status']");
            const tweets = page.locator("div[id*='span_status']");
            result = await tweets.evaluate(node => node.innerText);
            console.log("Inside Loop: \n", result);
            if (result == TransCodeStatus.Completed) {
                break;
            }
        }
        // Goto Uploaded media edit
        await page.locator("td.title.column-title.has-row-actions.column-primary ").first().hover();
        await page.locator("td.title.column-title.has-row-actions.column-primary > div > span.edit").first().click();
        // Wait for New page to load 
        await page.waitForSelector("#title");
        await page.waitForSelector("#attachment_caption");
        await expect(page).toHaveURL(/action=edit/)
        // Get Dom Element Length with function
        const VerifyLength = await page.evaluate(selector => document.querySelectorAll("input[id^='rtmedia-upload-select-thumbnail-']").length);
        // Verify thumbnail
        await expect(page.locator("input[id^='rtmedia-upload-select-thumbnail-']")).toHaveCount(VerifyLength);

        // Changing the thumbnail
        await page.locator("input[id^='rtmedia-upload-select-thumbnail-4']").check();
        // Click update and save
        await page.locator("#publish").click();
        // verify assertion
        await expect(page.locator("#message")).toContainText(/Media file updated./)
        
    });
});