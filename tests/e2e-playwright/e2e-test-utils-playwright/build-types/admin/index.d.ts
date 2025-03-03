/**
 * External dependencies
 */
import type { Browser, Page, BrowserContext } from '@playwright/test';
import type { PageUtils } from '../page-utils';
type AdminConstructorProps = {
    page: Page;
    pageUtils: PageUtils;
};
export declare class Admin {
    browser: Browser;
    page: Page;
    pageUtils: PageUtils;
    context: BrowserContext;
    constructor({ page, pageUtils }: AdminConstructorProps);
    createNewPost: any;
    getPageError: () => Promise<string | null>;
    visitAdminPage: (adminPath: string, query: string) => Promise<void>;
    visitSiteEditor: (query: import("./visit-site-editor").SiteEditorQueryParams, skipWelcomeGuide?: boolean | undefined) => Promise<void>;
}
export {};
//# sourceMappingURL=index.d.ts.map