"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.insertBlock = insertBlock;
/**
 * Insert a block.
 *
 * @param {Editor}              this
 * @param {BlockRepresentation} blockRepresentation Inserted block representation.
 */
async function insertBlock(blockRepresentation) {
    await this.page.evaluate((_blockRepresentation) => {
        function recursiveCreateBlock({ name, attributes = {}, innerBlocks = [], }) {
            // @ts-ignore (Reason: wp isn't typed).
            return window.wp.blocks.createBlock(name, attributes, innerBlocks.map((innerBlock) => recursiveCreateBlock(innerBlock)));
        }
        const block = recursiveCreateBlock(_blockRepresentation);
        // @ts-ignore (Reason: wp isn't typed).
        window.wp.data.dispatch('core/block-editor').insertBlock(block);
    }, blockRepresentation);
}
//# sourceMappingURL=insert-block.js.map