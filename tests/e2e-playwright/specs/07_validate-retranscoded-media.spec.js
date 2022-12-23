/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const { setTimeout } = require('timers');
const { TransCodeStatus } = require("../utils/locator.js");
test.describe('Validate ReTranscoded  Settings', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage('/');
    });
    test('Validate new retranscoded Settings', async ({ admin, page, editor }) => {
        await admin.visitAdminPage("media-new.php")
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
        // Final Assertion after completion.
        const comPleteMessage = page.locator("div[id*='span_status']");
        expect(await comPleteMessage.evaluate(node => node.innerText)).toContain('Your file is transcoded successfully.');
        await expect(checkStatus).toBeHidden();
        // Retranscode
        await page.locator("td.title.column-title.has-row-actions.column-primary > strong > a").first().hover();
        await page.locator("a[title='Retranscode this single media']").first().click();
        // Validate with proper visiblity
        const result_text = await page.locator("div[id='retranscodemedia-bar-percent']").innerText();
        if (result_text == '100%' && page.locator("div[id='retranscodemedia-bar-percent']").isEnabled()) {
            await page.locator("#toplevel_page_rt-transcoder > a > div.wp-menu-name").click()
        }
    });
    
    test('Validate All ReTranscoded Options', async ({ admin, page, editor }) => {
        await page.locator("#toplevel_page_rt-transcoder > a > div.wp-menu-name").click();
        // Check Lisence key Settings Added to stable the test case and for auto timeout
        const licenseSettings = page.locator("input[id='new-api-key']")
        expect(licenseSettings).not.toBeNull();
        await page.locator("role=link[name='Retranscode Media']").click();

        // Goto Retranscode Media
        await page.locator("role=button[name='Retranscode All Media']").click();
        // Validate Retranscoded media to in menu page.
        const result = await page.locator("div[id='retranscodemedia-bar-percent']").innerText();
        if (result == '100%' && page.locator("div[id='retranscodemedia-bar-percent']").isEnabled()) {
            await page.locator("#toplevel_page_rt-transcoder > a > div.wp-menu-name").click()
        }
    });

});