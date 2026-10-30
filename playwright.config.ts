import { defineConfig, devices } from '@playwright/test'

/**
 * Browser journeys (`make e2e`): a real browser against a real server, for what Pest and Vitest cannot
 * see — routing, middleware, Inertia, the email link, and the pages working together.
 *
 * **Its own database and port.** `.env.e2e` points at `nxtgen_e2e` (wiped by global setup every run) and
 * serves on 3021, so the dev server on 3020 and your working data are never touched. E2EServiceProvider
 * refuses to start against a database not named `*_e2e`.
 *
 * Three browsers, chosen by who uses them (plan §10):
 *   - `mobile-webkit` (iPhone Safari) and `mobile-chromium` (Android) — parents registering on their phone,
 *     Lifeshapers at the door.
 *   - `desktop-chromium` — admins at a laptop. Runs every journey.
 *
 * One worker: journeys share one seeded church and the app's per-IP rate limits, and parallel runs would
 * turn those into flakes in the test, not bugs in the app.
 */
const PORT = 3021
const BASE_URL = `http://127.0.0.1:${PORT}`

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/support/global-setup.ts',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    // A test that passes only on retry is a bug somewhere; CI says so instead of going green (aegis, admin-v2).
    failOnFlakyTests: !!process.env.CI,
    /*
     * The HTML report is the page to read a run on: every journey, its steps, and — for a failure — the
     * screenshot, video and trace. `make e2e-report` opens it; `make e2e-ui` watches journeys run one by one.
     */
    reporter: [
        process.env.CI ? ['github'] : ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ],
    timeout: 30_000,
    expect: { timeout: 5_000 },

    use: {
        baseURL: BASE_URL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },

    projects: [
        // Signs each staff member in ONCE (by email link) and saves the session; specs reuse it. Signing in
        // per test would hit the app's own limit of 5 emails per address per 10 minutes.
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        {
            name: 'mobile-webkit',
            dependencies: ['setup'],
            use: { ...devices['iPhone 14'] },
            testMatch: /(parent\/.*|staff\/(lifeshaper|door|whos-in|history|cards|cards-list))\.spec\.ts/,
        },
        {
            // Android: most parents' phones. A narrow Chromium is not Safari, and the reverse holds too.
            name: 'mobile-chromium',
            dependencies: ['setup'],
            use: { ...devices['Pixel 7'] },
            testMatch: /(parent\/.*|staff\/(lifeshaper|door|whos-in|history|cards|cards-list))\.spec\.ts/,
        },
        {
            name: 'desktop-chromium',
            dependencies: ['setup'],
            use: { ...devices['Desktop Chrome'] },
            testMatch: /(parent|staff)\/.*\.spec\.ts/,
        },
    ],

    webServer: {
        command: `php artisan serve --env=e2e --host=127.0.0.1 --port=${PORT}`,
        // Only an APP_ENV=e2e server answers this (E2EServiceProvider) — never a dev server on this port.
        url: `${BASE_URL}/__e2e/health`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
})
