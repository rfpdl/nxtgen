import { defineConfig, devices } from '@playwright/test'

/**
 * Four projects, and all four run (plan §5.9).
 *
 * Making one of them "the default" would be theatre — Playwright runs the projects it is configured
 * to run and ordering protects nothing. What protects each layout is that it has real journeys
 * assigned to it: teacher screens are phone-first, admin is laptop-first, and the tablet sits
 * between them where both claims could quietly stop being true.
 *
 * Mobile WebKit is the real phone target rather than a resized Chromium: a narrow Chromium is not
 * Mobile Safari — different keyboard behaviour, different viewport units, different service-worker
 * rules, which will matter for TODO #1.
 *
 * **The tablet is not redundant.** It is the width where the admin sidebar stops being a drawer and
 * becomes persistent, and where a teacher screen is wide enough that a phone-first layout can start
 * looking sparse — two things neither the phone nor the laptop project can see.
 *
 * `TOUCH_PROJECTS` below is the list the touch contracts apply to (≥56px targets, no sideways
 * scroll, 16px type floor). A project added here without being added there silently opts out of
 * every one of those.
 */
const PORT = 8100
const BASE_URL = `http://127.0.0.1:${PORT}`

/**
 * Projects whose users are touching glass, and which therefore must satisfy §5.9 and §5.10.
 *
 * Exported so specs can ask rather than each hardcoding `!== 'desktop-chromium'` — which is the
 * form that goes stale the moment a project is added.
 */
export const TOUCH_PROJECTS = ['mobile-webkit', 'mobile-chromium', 'tablet-webkit']

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',

    use: {
        baseURL: BASE_URL,
        trace: 'on-first-retry',
        // A real phone on venue wifi is slow. A tight default turns a slow render into a flake and
        // teaches everyone to re-run the suite.
        actionTimeout: 10_000,
    },

    projects: [
        /*
         * Signs in once per project and saves a session for the specs to reuse. Without it the
         * suite throttled itself — see tests/e2e/auth.setup.ts.
         */
        {
            name: 'setup',
            testMatch: /auth\.setup\.ts/,
        },
        {
            // Names must match E2ESeeder::PROJECTS — each project signs in as its own account, or
            // they exhaust Fortify's login throttle between them.
            name: 'mobile-webkit',
            dependencies: ['setup'],
            use: { ...devices['iPhone 14'] },
            testMatch: /(door|auth|register)\/.*\.spec\.ts/,
        },
        {
            name: 'mobile-chromium',
            dependencies: ['setup'],
            use: { ...devices['Pixel 7'] },
            testMatch: /(door|auth|register)\/.*\.spec\.ts/,
        },
        {
            // The in-between width. Runs BOTH surfaces, because an iPad is a plausible device for a
            // teacher at the desk and for a leader doing admin on the sofa.
            name: 'tablet-webkit',
            dependencies: ['setup'],
            use: { ...devices['iPad Pro 11'] },
            testMatch: /(door|admin|auth|register)\/.*\.spec\.ts/,
        },
        {
            name: 'desktop-chromium',
            dependencies: ['setup'],
            use: { ...devices['Desktop Chrome'] },
            testMatch: /(admin|auth)\/.*\.spec\.ts/,
        },
    ],

    // Its own port and its own database, so a run never touches the working data. Palo learned that
    // one the hard way: a quality gate that destroys an afternoon of hand-entered state is a gate
    // people stop running.
    webServer: {
        // `db:seed --class=E2ESeeder` rather than `migrate:fresh --seeder=Database\\Seeders\\…`.
        // A namespaced class name has to survive a JS template literal AND the shell, which strip a
        // level of backslashes each — the result was `DatabaseSeedersE2ESeeder` and a webServer that
        // failed to start with no useful message. `--class` resolves the seeder namespace itself.
        command: `php artisan migrate:fresh --force --env=e2e && php artisan db:seed --class=E2ESeeder --force --env=e2e && php artisan serve --env=e2e --port=${PORT}`,
        url: `${BASE_URL}/login`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
})
