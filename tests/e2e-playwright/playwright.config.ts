/**
 * External dependencies
 */
import path from 'path';
import { fileURLToPath } from 'url';
import { devices } from '@playwright/test';
import type { PlaywrightTestConfig } from '@playwright/test';

const STORAGE_STATE_PATH =
    process.env.STORAGE_STATE_PATH ||
    path.join(process.cwd(), 'artifacts/storage-states/admin.json');
const config: PlaywrightTestConfig = {
    // reporter: process.env.CI
    //     ? [['github'], ['./config/flaky-tests-reporter.ts']]
    //     : 'list',
    reporter: [
        ["html", { open: "never" }],
        ["junit", { outputFile: "playwright-report/results.xml" }],
        [
            "playwright-tesults-reporter",
            { "tesults-target": process.env.TESRESULT_TOKEN },
        ],
    ],
    forbidOnly: !!process.env.CI,
    workers: 1,
    retries: process.env.CI ? 2 : 0,
    timeout: parseInt(process.env.TIMEOUT || '', 100) || 100_0000, // Defaults to 100 seconds.
    // Don't report slow test "files", as we will be running our tests in serial.
    reportSlowTests: null,
    testDir: fileURLToPath(new URL('./specs', 'file:' + __filename).href),
    outputDir: path.join(process.cwd(), 'artifacts/test-results'),
    globalSetup: fileURLToPath(
        new URL('./config/global-setup.ts', 'file:' + __filename).href
    ),
    use: {
        baseURL: 'https://transcoder-test.rt.gw/', //https://transcoder.com
        headless: true,
        viewport: {
            width: 960,
            height: 700,
        },
        ignoreHTTPSErrors: true,
        locale: 'en-US',
        contextOptions: {
            reducedMotion: 'reduce',
            strictSelectors: true,
        },
        storageState: STORAGE_STATE_PATH,
        actionTimeout: 100_000, // 10 seconds.
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
};

export default config;