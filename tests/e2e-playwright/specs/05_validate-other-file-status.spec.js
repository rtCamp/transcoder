/**
* WordPress dependencies
*/
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const { setTimeout } = require('timers');
const { TransCodeStatus } = require("../utils/locator.js");
test.describe('Validate mp3 and mp4 ogg, PDF  types and Assert All Steps', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage("media-new.php");
    });

    test('Check ogg sample', async ({ admin, page, editor }) => {
        const oggPath = "assets/ogg-sample.ogg";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#plupload-browse-button').click(),
        ])
        await fileChooser.setFiles([
            oggPath,
        ])
        const item = await page.locator("#wpbody-content > div.wrap > h1");
        await expect(item).toBeVisible();
        //page.focus("button[class='button button-small copy-attachment-url']")
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
        while (result === TransCodeStatus.Processing || TransCodeStatus.Queue || TransCodeStatus.ServerReady) {
            // Loop Breaker After Timeout
            if (_hasTimeElasped) {
                break;
            }
            await checkStatus.click();
            await page.focus("div[id*='span_status']")
            await page.waitForSelector("div[id*='span_status']");
            const tweets = page.locator("div[id*='span_status']");
            result = await tweets.evaluate(node => node.innerText);
            console.log("Inside Loop: \n", result);
            if (result == TransCodeStatus.Processed) {
                break;
            }
        }
        // Final Assertion after completion.
        const comPleteMessage = page.locator("div[id*='span_status']");
        expect(await comPleteMessage.evaluate(node => node.innerText)).toContain(TransCodeStatus.Processed);

    });

    test('Check mp3 sample', async ({ admin, page, editor }) => {
        const mp3Path = "assets/mp3-sample.mp3";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#plupload-browse-button').click(),
        ])
        await fileChooser.setFiles([
            mp3Path,
        ])
        const item = await page.locator("#wpbody-content > div.wrap > h1");
        await expect(item).toBeVisible();
        //page.focus("button[class='button button-small copy-attachment-url']")
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

        // Check File is already transcoded 
        const checkMessage = page.locator("div[id*='span_status']").first();
        expect(checkMessage).not.toBeNull();

    });

    test('Check mp4 sample', async ({ admin, page, editor }) => {
        const mp4Path = "assets/mp4-sample.mp4";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#plupload-browse-button').click(),
        ])
        await fileChooser.setFiles([
            mp4Path,
        ])
        const item = await page.locator("#wpbody-content > div.wrap > h1");
        await expect(item).toBeVisible();
        //page.focus("button[class='button button-small copy-attachment-url']")
        const copyButton = "button[class='button button-small copy-attachment-url']";
        if (await page.locator(copyButton).isEnabled()) {
            await page.click(copyButton)
        }
        await admin.visitAdminPage("upload.php");
        //Select Grid
        await page.locator("a[id='view-switch-list']").click();
        // Check File is already transcoded 
        const checkStatus = page.locator("button[id^='btn_check_status']").first();
        expect(checkStatus).not.toBeNull();
    });
    test('Check pdf sample', async ({ admin, page, editor }) => {
        const pdfPath = "assets/pdf-sample.pdf";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#plupload-browse-button').click(),
        ])
        await fileChooser.setFiles([
            pdfPath,
        ])
        const item = await page.locator("#wpbody-content > div.wrap > h1");
        await expect(item).toBeVisible();
        const copyButton = "button[class='button button-small copy-attachment-url']";
        if (await page.locator(copyButton).isEnabled()) {
            await page.click(copyButton)
        }
        await admin.visitAdminPage("upload.php");
        //Select Grid
        await page.locator("a[id='view-switch-list']").click();
        const checkStatus = page.locator("button[id^='btn_check_status']").first();
        expect(checkStatus).not.toBeNull();
    });
});