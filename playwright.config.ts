import { defineConfig, devices } from '@playwright/test'

/**
 * Browser journeys (`make e2e`): a real browser against a real server, for what Pest and Vitest cannot
 * see — routing, middleware, Inertia, the email link, and the pages working together.
 *
 * **Its own database and port.** `.env.e2e` points at `nxtgen_e2e` (wiped by global setup every run) and
 * serves on 3021, so the dev server on 3020 and your working data are never touched. E2EServiceProvider
 * refuses to start against a database not named `*_e2e`.
 *
 * Eight projects, by the app's layout bands (TODO #12, Roy 2026-10-30) — not by device. The app's breakpoints are
 * Tailwind's `sm` (640px), `md` (768px: two columns begin) and `lg` (1024px: the menu turns from a sheet into a row),
 * so each project sits in one band, and every journey runs on every project (admins use phones and tablets too):
 *   - base: `phone-portrait` 390×844 (chromium), `phone-portrait-safari` the same in WebKit (parents' iPhones;
 *     Safari's bugs are its own), and `phone-small` 360×740, where a wide line first pushes the page sideways;
 *   - `sm`: `tablet-portrait` 720×1080;
 *   - `md`: `tablet-edge` 768×1024 (its very edge) and `phone-landscape` 844×390 — still the sheet menu;
 *   - `lg`: `tablet-landscape` 1024×768 (its very edge: the row menu at its tightest) and `desktop` 1280×800.
 * Phones and tablets are touch screens (hasTouch, isMobile); the desktop is not. Every screen a journey shows is checked
 * for its layout (support/test.ts): no sideways scroll, the menu fits (its row one line, from 1024px), and on touch screens
 * every control ≥ 44px.
 *
 * One worker: journeys share one seeded church and the app's per-IP rate limits, and parallel runs would
 * turn those into flakes in the test, not bugs in the app.
 */
const PORT = 3021
const BASE_URL = `http://127.0.0.1:${PORT}`

export default defineConfig({
    testDir: './tests/e2e',
    testMatch: /(parent|staff)\/.*\.spec\.ts/,
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
            name: 'phone-portrait',
            dependencies: ['setup'],
            use: { ...devices['Pixel 7'], viewport: { width: 390, height: 844 } },
        },
        {
            // Parents' iPhones: the same band in WebKit.
            name: 'phone-portrait-safari',
            dependencies: ['setup'],
            use: { ...devices['iPhone 14'], viewport: { width: 390, height: 844 } },
        },
        {
            name: 'phone-landscape',
            dependencies: ['setup'],
            use: { ...devices['Pixel 7 landscape'], viewport: { width: 844, height: 390 } },
        },
        {
            name: 'tablet-portrait',
            dependencies: ['setup'],
            use: { ...devices['Galaxy Tab S4'], viewport: { width: 720, height: 1080 } },
        },
        {
            name: 'tablet-landscape',
            dependencies: ['setup'],
            // The lg edge: the row menu at its tightest.
            use: { ...devices['Galaxy Tab S4 landscape'], viewport: { width: 1024, height: 768 } },
        },
        {
            name: 'phone-small',
            dependencies: ['setup'],
            use: { ...devices['Pixel 7'], viewport: { width: 360, height: 740 } },
        },
        {
            // The md edge: two columns begin; the menu is still a sheet.
            name: 'tablet-edge',
            dependencies: ['setup'],
            use: { ...devices['Galaxy Tab S4'], viewport: { width: 768, height: 1024 } },
        },
        {
            name: 'desktop',
            dependencies: ['setup'],
            use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } },
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
