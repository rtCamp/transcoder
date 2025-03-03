"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.pressKeyTimes = pressKeyTimes;
/**
 * Presses the given keyboard key a number of times in sequence.
 *
 * @param {string} key   Key to press.
 * @param {number} count Number of times to press.
 */
async function pressKeyTimes(key, count) {
    while (count--) {
        await this.page.keyboard.press(key);
    }
}
//# sourceMappingURL=press-key-times.js.map