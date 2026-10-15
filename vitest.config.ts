import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vitest/config'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'happy-dom',
        globals: true,
        include: ['resources/js/**/*.test.ts'],
        setupFiles: ['resources/js/tests/setup.ts'],
        // Deliberately NOT `resources/js/**/*.spec.ts`: `.spec.ts` is Playwright's extension in
        // tests/e2e, and a glob that caught both would try to run browser tests in happy-dom and
        // fail in a way that reads like a broken component.
        restoreMocks: true,

        /**
         * Line coverage for the frontend, added 2026-09-10 — `make coverage` had measured PHP only,
         * so 517 tests had never been asked what they never RUN. Asking found the whole of
         * `useQrScanner`'s frame loop at 0%, including the dedupe that stops one card in front of the
         * lens from posting sixty check-ins a second.
         *
         * **No `thresholds`, deliberately, and pages are why.** Every page sits at 0% here and that
         * is the intended split: a page is thin wiring and Playwright owns it, so a number that
         * counted them would either be permanently red or force unit tests onto the layer least
         * suited to them. `make coverage`'s PHP floor of 90% is enforceable because nothing there is
         * deliberately excluded. Read this report, do not gate on it.
         */
        coverage: {
            provider: 'v8',
            include: ['resources/js/**'],
            exclude: [
                'resources/js/**/*.test.ts',
                // Fixtures and helpers. They exist to be executed by tests, so they measure nothing.
                'resources/js/tests/**',
                // Vendored shadcn-vue. Not ours, and the reuse gate in REGISTRY.md is what governs it.
                'resources/js/components/ui/**',
                'resources/js/types/**',
                'resources/js/app.ts',
                'resources/js/ssr.ts',
            ],
        },
    },
})
