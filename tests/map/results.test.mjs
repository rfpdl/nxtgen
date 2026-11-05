import { strict as assert } from 'node:assert'
import { describe, it } from 'node:test'
import { junitResults, keyOf, outcomes, playwrightResults, settle, stepsFor, titlePattern } from './results.mjs'

const php = { file: 'tests/Feature/Services/Church/ChurchMovingUpTest.php', title: 'moves the ticked children up a Level @story#156', kind: 'php' }
const e2e = { file: 'tests/e2e/staff/moving-up.spec.ts', title: 'a church admin moves two children up @story#154', kind: 'e2e' }

describe('stepsFor', () => {
    it('runs PHP tests with Pest, filtered to exactly their titles, into a JUnit report', () => {
        const [step] = stepsFor([php], '/tmp/run')
        assert.equal(step.cmd, 'vendor/bin/pest')
        assert.deepEqual(step.args, [php.file, '--filter', '(?:moves the ticked children up a Level)', '--log-junit', '/tmp/run/pest.xml'])
    })

    it('builds, then runs browser tests on the chosen project only, into a JSON report', () => {
        const steps = stepsFor([e2e], '/tmp/run', 'desktop-safari')
        assert.deepEqual(steps.map((s) => s.kind), ['build', 'e2e'])
        assert.ok(steps[1].args.includes('--project=desktop-safari'))
        assert.equal(steps[1].env.PLAYWRIGHT_JSON_OUTPUT_NAME, '/tmp/run/playwright.json')
    })

    it('refuses a project the config does not have — nothing from the page reaches a command unchecked', () => {
        assert.throws(() => stepsFor([e2e], '/tmp/run', 'desktop; rm -rf /'), /Unknown project/)
    })

    it('leaves the story tags out — Pest never matches across them', () => {
        assert.equal(titlePattern([{ title: 'opens Cards @story#134 @story#135' }]), '(?:opens Cards)')
    })

    it('escapes a title so it matches itself only', () => {
        assert.equal(titlePattern([{ title: 'a (b) c.d' }, { title: 'a (b) c.d' }]), '(?:a \\(b\\) c\\.d)')
    })
})

describe('reports', () => {
    it('reads each Pest case and whether it failed', () => {
        const xml = `<testsuite><testcase name="it moves the ticked children up a Level @story#156" file="x"/>
            <testcase name="it refuses &amp; says so"><failure>boom</failure></testcase></testsuite>`
        assert.deepEqual(junitResults(xml), [
            { name: 'it moves the ticked children up a Level @story#156', status: 'passed' },
            { name: 'it refuses & says so', status: 'failed' },
        ])
    })

    it('reads each Playwright spec, failed if any run of it failed', () => {
        const report = { suites: [{ suites: [{ specs: [{ title: e2e.title, tests: [{ results: [{ status: 'failed' }, { status: 'passed' }] }] }] }] }] }
        assert.deepEqual(playwrightResults(report), [{ name: e2e.title, status: 'failed' }])
    })
})

describe('a run that did not really run (Astra)', () => {
    it('counts a spec Playwright collected but never ran as not run, never as passed', () => {
        const report = { suites: [{ specs: [{ title: e2e.title, tests: [{ results: [] }] }] }] }
        assert.deepEqual(playwrightResults(report), [{ name: e2e.title, status: 'missing' }])
        assert.deepEqual(outcomes([e2e], playwrightResults(report)), { [keyOf(e2e)]: 'missing' })
    })

    it('fails every test of a step whose runner failed though no test did — a setup that broke is not green', () => {
        assert.deepEqual(settle({ a: 'passed', b: 'missing' }, 1), { a: 'failed', b: 'failed' })
        assert.deepEqual(settle({ a: 'passed', b: 'failed' }, 1), { a: 'passed', b: 'failed' })
        assert.deepEqual(settle({ a: 'passed' }, 0), { a: 'passed' })
    })
})

describe('outcomes', () => {
    it('matches a test to the cases that carry its title, and says "missing" when none ran', () => {
        const reported = [
            { name: `it ${php.title} with data set "a"`, status: 'passed' },
            { name: `it ${php.title} with data set "b"`, status: 'failed' },
        ]
        assert.deepEqual(outcomes([php, e2e], reported), { [keyOf(php)]: 'failed', [keyOf(e2e)]: 'missing' })
    })
})
