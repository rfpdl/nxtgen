// The test map's runs, as pure functions (tests/map/serve.mjs does the I/O): which runner gets which tests, and what
// each runner's report says about each test. A test is { file, title, kind: 'php' | 'e2e' }.

import { join } from 'node:path'

/** The browser projects a Run may pick (playwright.config.ts); Chrome on a desktop is the quick one. */
export const PROJECTS = [
    'desktop', 'phone-portrait', 'phone-portrait-safari', 'phone-landscape', 'tablet-portrait', 'tablet-landscape',
    'phone-small', 'tablet-edge', 'phone-landscape-safari', 'desktop-safari', 'phone-portrait-firefox',
    'phone-landscape-firefox', 'desktop-firefox', 'tablet-portrait-safari', 'tablet-landscape-safari',
]

export const escapeRegex = (s) => s.replace(/[\\^$.*+?()[\]{}|/#]/g, '\\$&')

/**
 * One alternation matching these titles (Pest's --filter, Playwright's --grep), each without its story tags: Pest's
 * filter never matches across them, and the words before are the test's own.
 */
export const titlePattern = (tests) =>
    `(?:${[...new Set(tests.map((t) => escapeRegex(t.title.replace(/\s*@story#\d+/g, '').trim())))].join('|')})`

/** A test's key on the page and in the results: its file and title. */
export const keyOf = (test) => `${test.file}::${test.title}`

/**
 * The steps a Run takes, in order: Pest for the PHP tests (a JUnit report), then — if any — the browser tests on one
 * project (a build first, the e2e server and database as `make e2e` has them; a JSON report).
 */
export function stepsFor(tests, out, project = 'desktop') {
    if (!PROJECTS.includes(project)) throw new Error(`Unknown project: ${project}`)
    const php = tests.filter((t) => t.kind === 'php')
    const e2e = tests.filter((t) => t.kind === 'e2e')
    const steps = []
    if (php.length) {
        const report = join(out, 'pest.xml')
        steps.push({
            label: `Pest: ${php.length} test${php.length === 1 ? '' : 's'}`,
            kind: 'php',
            tests: php,
            report,
            cmd: 'vendor/bin/pest',
            args: [...new Set(php.map((t) => t.file))].concat(['--filter', titlePattern(php), '--log-junit', report]),
            env: {},
        })
    }
    if (e2e.length) {
        const report = join(out, 'playwright.json')
        steps.push({ label: 'Build the assets', kind: 'build', tests: [], cmd: 'npm', args: ['run', 'build'], env: {} })
        steps.push({
            label: `Playwright on ${project}: ${e2e.length} journey${e2e.length === 1 ? '' : 's'}`,
            kind: 'e2e',
            tests: e2e,
            report,
            cmd: 'npx',
            args: ['playwright', 'test', ...new Set(e2e.map((t) => t.file)), `--project=${project}`, '--grep', titlePattern(e2e), '--reporter=json'],
            env: { PLAYWRIGHT_JSON_OUTPUT_NAME: report },
        })
    }
    return steps
}

const unescapeXml = (s) =>
    s.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'").replace(/&#039;/g, "'").replace(/&amp;/g, '&')

/** Pest's JUnit report: each test case's name and whether it passed. */
export function junitResults(xml) {
    const results = []
    for (const [, attrs, body = ''] of xml.matchAll(/<testcase\b([^>]*?)(?:\/>|>([\s\S]*?)<\/testcase>)/g)) {
        const name = unescapeXml(/\bname="([^"]*)"/.exec(attrs)?.[1] ?? '')
        const status = /<(failure|error)\b/.test(body) ? 'failed' : /<skipped\b/.test(body) ? 'skipped' : 'passed'
        results.push({ name, status })
    }
    return results
}

/**
 * Playwright's JSON report: each spec's title and its worst outcome across its runs. A spec it collected but never ran
 * (no results: setup broke, the run was stopped) is `missing`, never passed (Astra).
 */
export function playwrightResults(report) {
    const results = []
    const walk = (suite) => {
        for (const spec of suite.specs ?? []) {
            const statuses = spec.tests.flatMap((t) => t.results.map((r) => r.status))
            const status = !statuses.length
                ? 'missing'
                : statuses.some((s) => s === 'failed' || s === 'timedOut' || s === 'interrupted')
                  ? 'failed'
                  : statuses.every((s) => s === 'skipped') ? 'skipped' : 'passed'
            results.push({ name: spec.title, status })
        }
        for (const child of suite.suites ?? []) walk(child)
    }
    for (const suite of report.suites ?? []) walk(suite)
    return results
}

/**
 * Each asked-for test's outcome: Pest names a test "it <title>" (a dataset adds "with …", a describe a prefix), so a
 * case belongs to a test whose title it contains; a test with several cases failed if any did. A test the runner never
 * reported is "missing" — a Run that found nothing must not look green.
 */
export function outcomes(tests, reported) {
    const out = {}
    for (const test of tests) {
        const mine = reported.filter((r) => r.name === test.title || r.name.includes(test.title))
        out[keyOf(test)] = !mine.length
            ? 'missing'
            : mine.some((r) => r.status === 'failed')
              ? 'failed'
              : mine.some((r) => r.status === 'missing')
                ? 'missing'
                : mine.every((r) => r.status === 'skipped') ? 'skipped' : 'passed'
    }
    return out
}

/**
 * A step's outcomes, given how its runner ended: a runner that failed though none of its tests did (its setup broke, it
 * found nothing to run) fails them all — a run that did not really run must not look green (Astra).
 */
export function settle(stepOutcomes, exitCode) {
    const statuses = Object.values(stepOutcomes)
    if (exitCode === 0 || statuses.includes('failed')) return stepOutcomes
    return Object.fromEntries(Object.keys(stepOutcomes).map((key) => [key, 'failed']))
}
