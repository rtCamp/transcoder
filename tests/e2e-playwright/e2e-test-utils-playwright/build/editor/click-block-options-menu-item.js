"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.clickBlockOptionsMenuItem = clickBlockOptionsMenuItem;
/**
 * Clicks a block toolbar button.
 *
 * @param {Editor} this
 * @param {string} label The text string of the button label.
 */
async function clickBlockOptionsMenuItem(label) {
    await this.clickBlockToolbarButton('Options');
    await this.page
        .locator(`role=menu[name="Options"i] >> role=menuitem[name="${label}"i]`)
        .click();
}
//# sourceMappingURL=click-block-options-menu-item.js.map