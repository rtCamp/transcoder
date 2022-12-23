/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

test.describe('Validate ReTranscoded  Settings', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage('/');
    });
    test('Validate new retranscoded Settings', async ({ admin, page, editor }) => {
        await admin.visitAdminPage("media-new.php")
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
        // Retranscode
        await page.locator("td.title.column-title.has-row-actions.column-primary > strong > a").first().hover();
        await page.locator("a[title='Retranscode this single media']").first().click();
        // Validate with proper visiblity
        const result = await page.locator("div[id='retranscodemedia-bar-percent']").innerText();
        if (result == '100%' && page.locator("div[id='retranscodemedia-bar-percent']").isEnabled()) {
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