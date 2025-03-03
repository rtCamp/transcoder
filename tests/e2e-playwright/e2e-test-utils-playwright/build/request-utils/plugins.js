"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getPluginsMap = getPluginsMap;
exports.activatePlugin = activatePlugin;
exports.deactivatePlugin = deactivatePlugin;
/**
 * External dependencies
 */
const lodash_1 = require("lodash");
/**
 * Fetch the plugins from API and cache them in memory,
 * since they are unlikely to change during testing.
 *
 * @param {} this           RequestUtils.
 * @param {} [forceRefetch] Force refetch the installed plugins to update the cache.
 */
async function getPluginsMap(forceRefetch = false) {
    if (!forceRefetch && this.pluginsMap) {
        return this.pluginsMap;
    }
    const plugins = await this.rest({
        path: '/wp/v2/plugins',
    });
    this.pluginsMap = {};
    for (const plugin of plugins) {
        // Ideally, we should be using sanitize_title() in PHP rather than kebabCase(),
        // but we don't have the exact port of it in JS.
        this.pluginsMap[(0, lodash_1.kebabCase)(plugin.name)] = plugin.plugin;
    }
    return this.pluginsMap;
}
/**
 * Activates an installed plugin.
 *
 * @param {this}   this RequestUtils.
 * @param {string} slug Plugin slug.
 */
async function activatePlugin(slug) {
    const pluginsMap = await this.getPluginsMap();
    const plugin = pluginsMap[slug];
    if (!plugin) {
        throw new Error(`The plugin "${slug}" isn't installed`);
    }
    await this.rest({
        method: 'PUT',
        path: `/wp/v2/plugins/${plugin}`,
        data: { status: 'active' },
    });
}
/**
 * Deactivates an active plugin.
 *
 * @param {this}   this RequestUtils.
 * @param {string} slug Plugin slug.
 */
async function deactivatePlugin(slug) {
    const pluginsMap = await this.getPluginsMap();
    const plugin = pluginsMap[slug];
    if (!plugin) {
        throw new Error(`The plugin "${slug}" isn't installed`);
    }
    await this.rest({
        method: 'PUT',
        path: `/wp/v2/plugins/${plugin}`,
        data: { status: 'inactive' },
    });
}
//# sourceMappingURL=plugins.js.map