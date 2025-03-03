"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.openPreviewPage = openPreviewPage;
/**
 * Opens the preview page of an edited post.
 *
 * @param {Editor} this
 *
 * @return {Promise<Page>} preview page.
 */
async function openPreviewPage() {
    const editorTopBar = this.page.locator('role=region[name="Editor top bar"i]');
    const previewButton = editorTopBar.locator('role=button[name="Preview"i]');
    await previewButton.click();
    const [previewPage] = await Promise.all([
        this.context.waitForEvent('page'),
        this.page.click('role=menuitem[name="Preview in new tab"i]'),
    ]);
    return previewPage;
}
//# sourceMappingURL=preview.js.map