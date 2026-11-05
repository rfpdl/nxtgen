#!/usr/bin/env node
// The test map (`make test-map`, TODO #10): every staff and parent route with its access rule and who gets in, each
// story with the tests that prove it, and a Run that hands those tests to Pest or Playwright — served on 127.0.0.1
// only. A Run names tests the map itself listed; nothing from the page reaches a command unchecked.
//
// `--static` writes storage/app/test-map.html instead: the same page, nothing to run.

import { spawn, execFileSync } from 'node:child_process'
import { randomBytes } from 'node:crypto'
import { existsSync, mkdirSync, mkdtempSync, readFileSync, writeFileSync } from 'node:fs'
import { createServer } from 'node:http'
import { tmpdir } from 'node:os'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { PROJECTS, junitResults, keyOf, outcomes, playwrightResults, settle, stepsFor } from './results.mjs'

const HERE = dirname(fileURLToPath(import.meta.url))
const ROOT = join(HERE, '..', '..')
const PORT = Number(process.env.MAP_PORT ?? 8790)
const TOKEN = randomBytes(24).toString('hex')

/** The map's data, read fresh: tests move, and a Run must find them where they are now. */
function exportData() {
    const out = execFileSync('php', ['tests/map/export.php'], { cwd: ROOT, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 })
    return JSON.parse(out)
}

function page(boot) {
    const html = readFileSync(join(HERE, 'index.html'), 'utf8')
    return html.replace('/*__MAP_BOOT__*/', `window.MAP_BOOT = ${JSON.stringify(boot).replace(/</g, '\\u003c')};`)
}

if (process.argv.includes('--static')) {
    const target = join(ROOT, 'storage', 'app', 'test-map.html')
    mkdirSync(dirname(target), { recursive: true })
    writeFileSync(target, page({ live: false, data: exportData(), projects: PROJECTS }))
    console.log(`Written: storage/app/test-map.html`)
    process.exit(0)
}

// ── Runs: one at a time ───────────────────────────────────────────────────────────────────────────────────────────
// `run` is the current or last run; a Run is taken (state "running") the moment its request arrives, before its body
// is read, so a second request is refused — two browser runs would wipe each other's e2e database (Astra).

const fresh = (label) => ({ state: 'running', label, log: [], results: {}, exit: null })
let run = { ...fresh(''), state: 'idle' }

/** Every test the map lists, by key — a Run may name only these. */
function knownTests(data) {
    const known = new Map()
    for (const item of [...data.routes, ...data.features]) {
        for (const story of item.stories) for (const test of story.tests) known.set(keyOf(test), test)
    }
    return known
}

function say(current, line) {
    current.log.push(line)
    if (current.log.length > 400) current.log.splice(0, current.log.length - 400)
}

/** One Run, on its own state object: nothing it does later can touch another Run's. */
async function startRun(current, keys, project) {
    const known = knownTests(exportData())
    const tests = keys.map((k) => known.get(k))
    if (!tests.length || tests.some((t) => !t)) throw new Error('Run only tests the map lists (reload the page: tests may have moved).')
    const out = mkdtempSync(join(tmpdir(), 'nxtgen-map-'))
    const steps = stepsFor(tests, out, project)
    current.label = steps.map((s) => s.label).join(', then ')
    for (const test of tests) current.results[keyOf(test)] = 'running'

    for (const step of steps) {
        say(current, `▶ ${step.label}`)
        const code = await new Promise((resolve) => {
            const child = spawn(step.cmd, step.args, { cwd: ROOT, env: { ...process.env, ...step.env } })
            const take = (chunk) => String(chunk).split('\n').filter(Boolean).forEach((l) => say(current, l.replace(/\x1b\[[0-9;]*m/g, '')))
            child.stdout.on('data', take)
            child.stderr.on('data', take)
            child.on('close', resolve)
            child.on('error', (e) => { say(current, String(e)); resolve(1) })
        })
        current.exit = code
        if (step.kind === 'build') {
            if (code !== 0) {
                for (const test of tests) current.results[keyOf(test)] = 'failed'
                say(current, 'The build failed, so no browser test ran.')
                break
            }
            continue
        }
        const reported = !existsSync(step.report)
            ? []
            : step.kind === 'php'
              ? junitResults(readFileSync(step.report, 'utf8'))
              : playwrightResults(JSON.parse(readFileSync(step.report, 'utf8')))
        Object.assign(current.results, settle(outcomes(step.tests, reported), code))
    }
    for (const [key, status] of Object.entries(current.results)) if (status === 'running') current.results[key] = 'missing'
    current.state = 'done'
    const statuses = Object.values(current.results)
    say(current, `■ Done: ${statuses.filter((s) => s === 'passed').length} passed, ${statuses.filter((s) => s !== 'passed').length} not`)
}

// ── HTTP ──────────────────────────────────────────────────────────────────────────────────────────────────────────

const json = (res, status, body) => {
    res.writeHead(status, { 'content-type': 'application/json' })
    res.end(JSON.stringify(body))
}

createServer((req, res) => {
    const url = new URL(req.url ?? '/', `http://127.0.0.1:${PORT}`)
    // A page on another site cannot drive this one: every write carries the token only this page was given.
    if (req.method === 'POST' && req.headers['x-map-token'] !== TOKEN) return json(res, 403, { error: 'Bad token' })

    if (req.method === 'GET' && url.pathname === '/') {
        res.writeHead(200, { 'content-type': 'text/html; charset=utf-8' })
        return res.end(page({ live: true, token: TOKEN, data: exportData(), projects: PROJECTS }))
    }
    if (req.method === 'GET' && url.pathname === '/run') return json(res, 200, run)
    if (req.method === 'POST' && url.pathname === '/run') {
        if (run.state === 'running') return json(res, 409, { error: 'A run is going; wait for it to finish.' })
        const current = fresh('Starting…')
        run = current
        let body = ''
        req.on('data', (c) => (body += c))
        req.on('end', () => {
            const fail = (e) => {
                current.state = 'done'
                current.exit = 1
                say(current, String(e?.message ?? e))
            }
            try {
                const { keys, project } = JSON.parse(body || '{}')
                startRun(current, Array.isArray(keys) ? keys.map(String) : [], String(project ?? 'desktop')).catch(fail)
                json(res, 202, { ok: true })
            } catch (e) {
                fail(e)
                json(res, 400, { error: String(e.message ?? e) })
            }
        })
        return
    }
    json(res, 404, { error: 'Not found' })
}).listen(PORT, '127.0.0.1', () => console.log(`Test map: http://127.0.0.1:${PORT}  (Ctrl+C stops it)`))
