// Captures the real NxtGen screens for the brag video, against the isolated brag server (port 3022, nxtgen_brag_e2e).
import { chromium, devices } from 'playwright'
import { readFileSync, existsSync, mkdirSync } from 'node:fs'

const BASE = 'http://127.0.0.1:3022'
const OUT = 'brag-output/work/shots'
const MAILBOX = 'storage/app/e2e/mailbox.jsonl'
mkdirSync(OUT, { recursive: true })

const only = process.argv[2] ?? 'all'
const phone = { ...devices['Pixel 7'], viewport: { width: 390, height: 844 }, deviceScaleFactor: 3, baseURL: BASE }
const desk = { viewport: { width: 1280, height: 800 }, deviceScaleFactor: 2, baseURL: BASE }

const browser = await chromium.launch()
const shot = async (page, name, opts = {}) => {
    await page.waitForTimeout(400)
    await page.screenshot({ path: `${OUT}/${name}.png`, ...opts })
    console.log('shot', name)
}
const settle = (page) => page.waitForLoadState('networkidle')

async function staffLogin(options, email) {
    const ctx = await browser.newContext(options)
    const page = await ctx.newPage()
    await page.goto('/login')
    await page.getByTestId('use-password').click()
    await page.getByTestId('password-email').fill(email)
    await page.getByTestId('password-password').fill('password')
    await page.getByTestId('password-submit').click()
    await page.waitForURL((u) => !u.pathname.startsWith('/login'))
    return page
}

function magicFor(email) {
    if (!existsSync(MAILBOX)) return null
    const mails = readFileSync(MAILBOX, 'utf8').split('\n').filter(Boolean).map((l) => JSON.parse(l))
    const mine = mails.filter((m) => m.to.includes(email))
    const m = mine.at(-1)?.body.match(/\/magic\/[a-z]+\/[A-Za-z0-9]+/)
    return m?.[0] ?? null
}

if (only === 'all' || only === 'register') {
    const ctx = await browser.newContext(phone)
    const page = await ctx.newPage()
    const email = `ocampo-${Date.now()}@brag.test`
    await page.goto('/ccf/register')
    await settle(page)
    await shot(page, 'reg-0-empty')
    await page.getByTestId('guardian-first-name').fill('Maria')
    await page.getByTestId('guardian-last-name').fill('Ocampo')
    await page.getByTestId('guardian-relationship').selectOption('mother')
    await page.getByTestId('guardian-phone').fill('0505550099')
    await page.getByTestId('guardian-email').fill(email)
    await page.getByTestId('home-satellite').selectOption({ label: 'CCF DXB' })
    await page.getByTestId('area').fill('Al Barsha')
    const kids = [
        { first: 'Amara', gender: 'girl', dob: '2019-08-12' },
        { first: 'Ben', gender: 'boy', dob: '2021-02-20' },
    ]
    for (const [i, k] of kids.entries()) {
        if (i > 0) await page.getByTestId('add-child').click()
        await page.getByTestId(`child-${i}-first-name`).fill(k.first)
        await page.getByTestId(`child-${i}-last-name`).fill('Ocampo')
        await page.getByTestId(`child-${i}-nickname`).fill(k.first)
        await page.getByTestId(`child-${i}-gender`).selectOption(k.gender)
        await page.getByTestId(`child-${i}-dob`).fill(k.dob)
        await page.getByTestId(`child-${i}-has-allergies-no`).click().catch(() => {})
    }
    await page.getByTestId('privacy').check()
    await shot(page, 'reg-1-filled-full', { fullPage: true })
    await page.getByTestId('register-submit').click()
    await page.waitForURL(/register\/thanks/)
    await shot(page, 'reg-2-thanks')
    let path = null
    for (let i = 0; i < 40 && !path; i++) { await page.waitForTimeout(250); path = magicFor(email) }
    await page.goto(path)
    await page.getByTestId('confirm-link').click()
    await page.waitForURL(/\/ccf\/family/)
    await settle(page)
    await shot(page, 'reg-3-family')
    await shot(page, 'reg-3-family-full', { fullPage: true })
    await ctx.close()
}

if (only === 'all' || only === 'door') {
    const page = await staffLogin(phone, 'lifeshaper@nxtgen.test')
    await page.goto('/ccf/door')
    const onePm = page.getByTestId('door-class').getByRole('radio', { name: '1PM' })
    await onePm.click()
    await settle(page)
    // The Ocampo children are already in, so Who's in has company.
    await page.getByTestId('door-search').fill('Ocampo')
    await page.getByTestId('door-search').press('Enter')
    const ocampo = page.getByTestId('door-family').filter({ hasText: 'Ocampo' })
    await ocampo.first().waitFor()
    for (const t of await ocampo.getByTestId('tick-child').all()) await t.check().catch(() => {})
    if (await ocampo.getByTestId('check-in').isEnabled()) {
        await ocampo.getByTestId('check-in').click()
        await page.getByTestId('flash-success').waitFor()
    }
    await shot(page, 'door-x-ocampo')
    await page.goto('/ccf/door?class=' + new URL(page.url()).searchParams.get('class'))
    await settle(page)
    await shot(page, 'door-0-start')
    await page.getByTestId('door-search').fill('AMARAJOYREYES')
    await shot(page, 'door-1-typed')
    await page.getByTestId('door-search').press('Enter')
    const reyes = page.getByTestId('door-family').filter({ hasText: 'Reyes' })
    await reyes.waitFor()
    await shot(page, 'door-2-found')
    const ben = reyes.getByTestId('door-child').filter({ hasText: 'Ben' }).getByTestId('tick-child')
    await ben.check()
    await shot(page, 'door-3-both')
    await reyes.getByTestId('check-in').click()
    await page.getByTestId('flash-success').waitFor()
    await shot(page, 'door-4-checked')

    await page.getByTestId('door-tab-whos-in').click()
    await settle(page)
    await shot(page, 'whos-0')
    await shot(page, 'whos-0-full', { fullPage: true })
    for (const box of await page.getByTestId('whos-in-child').getByRole('checkbox').all()) {
        await Promise.all([
            page.waitForResponse((r) => r.request().method() === 'PUT' && r.url().endsWith('/present')),
            box.click(),
        ])
    }
    await shot(page, 'whos-1-present-full', { fullPage: true })
    for (const btn of await page.getByTestId('head-count-confirm').all()) {
        await btn.click()
        await page.waitForTimeout(700)
    }
    await settle(page)
    await shot(page, 'whos-2-confirmed')
    await shot(page, 'whos-2-confirmed-full', { fullPage: true })

    // Ben Reyes lost his card: the staff family page.
    await page.goto('/ccf/families?q=Reyes')
    await page.getByTestId('family-row').filter({ hasText: 'Reyes' }).getByRole('link').first().click()
    await page.waitForURL(/\/families\/\d+$/)
    await settle(page)
    const kid = page.getByTestId('family-child').filter({ hasText: 'Ben' })
    await kid.scrollIntoViewIfNeeded()
    await shot(page, 'card-0-family-full', { fullPage: true })
    await kid.getByTestId('new-card').click()
    await page.waitForTimeout(300)
    await shot(page, 'card-1-picker-full', { fullPage: true })
    await kid.getByTestId('card-reason-lost').click()
    await kid.getByTestId('card-note').fill('Lost at the park')
    await shot(page, 'card-2-lost-full', { fullPage: true })
    await kid.getByTestId('card-request-submit').click()
    await kid.getByTestId('card-request-step').waitFor()
    await shot(page, 'card-3-asked-full', { fullPage: true })
    await page.context().storageState({ path: 'brag-output/work/lifeshaper.json' })
    await page.context().close()
}

if (only === 'all' || only === 'cards') {
    const page = await staffLogin(desk, 'admin@nxtgen.test')
    await page.goto('/ccf/card-requests?tab=asked')
    await settle(page)
    await shot(page, 'cards-0-asked')
    const row = (name) => page.getByTestId('card-request').filter({ hasText: name })
    const ben = row('Ben Reyes')
    await ben.getByTestId('card-action-mark_paid').click()
    await page.getByTestId('flash-success').waitFor()
    await settle(page)
    await shot(page, 'cards-1-paid')
    for (const name of ['Ben Reyes', 'Amara Ocampo', 'Ben Ocampo']) {
        const r = row(name)
        if (await r.getByTestId('card-action-approve').count()) {
            await r.getByTestId('card-action-approve').click()
            await page.waitForTimeout(500)
            await settle(page)
        }
    }
    await page.goto('/ccf/card-requests?tab=approved')
    await settle(page)
    await shot(page, 'cards-2-approved')
    await page.getByTestId('cards-make-print-list').click()
    await page.getByTestId('print-list').first().waitFor()
    await settle(page)
    await shot(page, 'cards-3-printlist')
    await page.getByTestId('print-list').first().getByTestId('print-list-mark').click()
    await page.waitForTimeout(800)
    await settle(page)
    await shot(page, 'cards-4-printed')
    await page.goto('/ccf/card-requests?tab=printed')
    await settle(page)
    await shot(page, 'cards-5-printed-tab')
    await page.context().close()

    // Back at the desk: Given.
    const ctx = await browser.newContext({ ...phone, storageState: 'brag-output/work/lifeshaper.json' })
    const p = await ctx.newPage()
    await p.goto('/ccf/families?q=Reyes')
    await p.getByTestId('family-row').filter({ hasText: 'Reyes' }).getByRole('link').first().click()
    await p.waitForURL(/\/families\/\d+$/)
    await settle(p)
    const kid = p.getByTestId('family-child').filter({ hasText: 'Ben' })
    await shot(p, 'card-4-printed-full', { fullPage: true })
    await kid.getByTestId('card-given').click()
    await p.waitForTimeout(800)
    await settle(p)
    await shot(p, 'card-5-given-full', { fullPage: true })
    await ctx.close()
}

await browser.close()
