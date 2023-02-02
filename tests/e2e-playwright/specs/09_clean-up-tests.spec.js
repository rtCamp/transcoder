/**
* WordPress dependencies
*/
const { test } = require('@wordpress/e2e-test-utils-playwright');

test.describe('Clear All data after testing', () => {
    test.beforeEach(async ({ admin }) => {
        await admin.visitAdminPage("upload.php");
    });
    test('Clear All Media after Testing', async ({ page }) => {
        //Select All Media
        await page.locator("#cb-select-all-1").check();
        // Select Bulk Delete Option
        await page.locator("#bulk-action-selector-top").selectOption("delete");
        // Click Apply
        page.on('dialog', dialog => dialog.accept());
        await page.locator("#doaction").click();
    });
});