 /**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
test.describe('Validate Different Media types', () => {
    test.beforeEach(async ({ admin }) => {
        //await admin.visitAdminPage('upload.php');
        await admin.visitAdminPage("media-new.php")
        

    });
    test('Check 3gp sample', async ({ admin, page, editor }) => {
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
        const item = await page.locator("#wpbody-content > div.wrap > h1");
        await expect(item).toBeVisible();
        //page.focus("button[class='button button-small copy-attachment-url']")
        const copyButton = "button[class='button button-small copy-attachment-url']";
        if (await page.locator(copyButton).isEnabled()) {
            await page.click(copyButton)
        }
        await admin.visitAdminPage("upload.php");
        //Select Grid and verify assertion
        await page.locator("a[id='view-switch-list']").click();
        const checkStatus = page.locator("button[id^='btn_check_status']").first();
        expect(checkStatus).not.toBeNull();
        await checkStatus.click();
        const checkMessage = page.locator("div[id*='span_status']");
        expect(checkMessage).not.toBeNull();
    });
    test('Check webm sample', async ({ admin, page, editor }) => {
        const webmPath = "assets/webm-sample.webm";
        const [fileChooser] = await Promise.all([
            // It is important to call waitForEvent before click to set up waiting.
            page.waitForEvent('filechooser'),
            // Opens the file chooser.
            page.locator('#plupload-browse-button').click(),

        ])
        await fileChooser.setFiles([
            webmPath,
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
        const checkStatus = page.locator("button[id^='btn_check_status']").first();
        expect(checkStatus).not.toBeNull();
        await checkStatus.click();
        const checkMessage = page.locator("div[id*='span_status']");
        expect(checkMessage).not.toBeNull();
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
        await admin.visitAdminPage("upload.php");
        //Select Grid
        await page.locator("a[id='view-switch-list']").click();
        const checkStatus = page.locator("button[id^='btn_check_status']").first();
        expect(checkStatus).not.toBeNull();
        await checkStatus.click();
        const checkMessage = page.locator("div[id*='span_status']");
        expect(checkMessage).not.toBeNull();
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
        await admin.visitAdminPage("upload.php");
        //Select Grid
        await page.locator("a[id='view-switch-list']").click();
        const checkStatus = page.locator("button[id^='btn_check_status']").first();
        expect(checkStatus).not.toBeNull();
    });
    test('Check mp4 sample', async ({ admin, page, editor }) => {
        const mp4Path = "assets/mp3-sample.mp3";
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
        //page.focus("button[class='button button-small copy-attachment-url']")
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