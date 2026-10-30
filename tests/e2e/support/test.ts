import { test as base, expect, type BrowserContext, type Page } from '@playwright/test'

/**
 * Every journey's `test` (TODO #12): Playwright's, plus a layout check of the screens it shows, on every project.
 *
 * What a check finds (`__layoutCheck` in the page):
 *   - the page scrolls sideways (the document wider than the screen);
 *   - the menu does not fit: its row (from 1024px) must be ONE line and fit; a sheet must sit on the screen;
 *   - on a touch screen, a visible control under 44×44px — links, buttons, fields, ticks, chips, and a label standing
 *     in for a hidden input (a file picker) — named, with its page.
 *
 * When it runs — deterministically, never on "the page went quiet":
 *   - after every Inertia visit (the page's own `inertia:navigate`, two frames later);
 *   - wherever a journey calls `checkLayout(page)` (a form or the menu opened, before moving on);
 *   - on every open page just before its context closes (a journey's own contexts too) and when the test ends.
 * Those checkpoints must succeed (checkLayout: a navigation mid-check is waited out and retried; anything else fails).
 * A test that checked nothing fails, and a touch project whose browser does not say "coarse pointer" fails — so the
 * 44px rule can never silently not apply.
 */
const MIN_TARGET = 44
const ROW_FROM = 1024

type Result = { problems: string[]; coarse: boolean }

const state = { checks: 0, problems: [] as string[] }

function installCheck(opts: { minTarget: number; rowFrom: number }): void {
    const w = window as Window & {
        __layoutCheck?: () => { problems: string[]; coarse: boolean }
        __reportLayout?: (result: { problems: string[]; coarse: boolean }) => void
    }
    if (w.__layoutCheck) return
    const visible = (el: Element): boolean => {
        const r = el.getBoundingClientRect()
        const style = getComputedStyle(el)
        return (
            r.width > 1 &&
            r.height > 1 &&
            style.visibility !== 'hidden' &&
            style.display !== 'none' &&
            el.closest('[aria-hidden="true"], [inert]') === null
        )
    }
    const name = (el: Element): string => {
        const id = (el as HTMLElement).dataset?.testid
        const text = (el.getAttribute('aria-label') ?? el.textContent ?? '')
            .trim()
            .replace(/\s+/g, ' ')
            .slice(0, 40)
        return `${el.tagName.toLowerCase()}${id ? `[${id}]` : ''} "${text}"`
    }
    w.__layoutCheck = () => {
        const problems: string[] = []
        const where = location.pathname
        const width = window.innerWidth
        const doc = document.documentElement
        if (doc.scrollWidth > width + 1)
            problems.push(
                `${where}: scrolls sideways (${doc.scrollWidth}px on a ${width}px screen)`,
            )
        const row = document.querySelector('[data-testid="menu-row"]')
        if (row && visible(row)) {
            const tops = new Set(
                [...row.querySelectorAll('a, button')]
                    .filter(visible)
                    .map((el) => Math.round(el.getBoundingClientRect().top)),
            )
            if (width >= opts.rowFrom && tops.size > 1)
                problems.push(`${where}: the menu row wraps onto ${tops.size} lines at ${width}px`)
            if (row.scrollWidth > row.clientWidth + 1)
                problems.push(
                    `${where}: the menu row does not fit (${row.scrollWidth}px in ${row.clientWidth}px)`,
                )
        }
        const sheet = document.querySelector('[data-testid="menu-sheet"]')
        if (sheet && visible(sheet)) {
            const r = sheet.getBoundingClientRect()
            if (r.left < -1 || r.right > width + 1 || sheet.scrollWidth > sheet.clientWidth + 1)
                problems.push(`${where}: the menu sheet does not fit the screen`)
        }
        const coarse = matchMedia('(pointer: coarse)').matches
        if (coarse) {
            const controls = [
                ...document.querySelectorAll(
                    'a[href], button, input:not([type="hidden"]), select, textarea, [role="button"], [role="radio"], [role="checkbox"], [role="switch"], [role="tab"], summary',
                ),
                // A label standing in for an input that is hidden (a file picker drawn as a button): the label is the target.
                ...[...document.querySelectorAll('label')].filter(
                    (l) => l.control !== null && !visible(l.control),
                ),
            ]
            for (const el of controls) {
                if (!visible(el)) continue
                const r = el.getBoundingClientRect()
                if (r.width < opts.minTarget - 0.5 || r.height < opts.minTarget - 0.5) {
                    problems.push(
                        `${where}: ${name(el)} is ${Math.round(r.width)}×${Math.round(r.height)}px — under ${opts.minTarget}px to tap`,
                    )
                }
            }
        }
        return { problems, coarse }
    }
    // After every Inertia visit, once the new page is painted.
    document.addEventListener('inertia:navigate', () =>
        requestAnimationFrame(() =>
            requestAnimationFrame(() => w.__reportLayout?.(w.__layoutCheck!())),
        ),
    )
}

let hasTouch = false
let projectName = ''

function record(result: Result, where: string): void {
    state.checks++
    state.problems.push(...result.problems)
    if (result.coarse !== hasTouch) {
        state.problems.push(
            `${where}: the browser says pointer ${result.coarse ? 'coarse' : 'fine'}, but ${projectName} is ${hasTouch ? '' : 'not '}a touch screen`,
        )
    }
}

/**
 * Checks the layout of the page as it is now (a checkpoint: a form or the menu opened, before moving on). It must
 * succeed: a page that navigates during the check is waited for and checked again (three tries), a page without the
 * check installed fails, and any other error fails. Only a blank or closed page is passed over.
 */
export async function checkLayout(page: Page): Promise<void> {
    for (let attempt = 1; ; attempt++) {
        if (page.isClosed() || page.url() === 'about:blank') return
        try {
            const result = await page.evaluate(
                () =>
                    (window as Window & { __layoutCheck?: () => Result }).__layoutCheck?.() ?? null,
            )
            if (result === null)
                throw new Error(`No layout check on ${page.url()} — the watcher was not installed.`)
            record(result, page.url())
            return
        } catch (error) {
            const navigated = /Execution context was destroyed|navigat/i.test(String(error))
            if (!navigated || attempt >= 3) throw error
            await page.waitForLoadState('load')
        }
    }
}

async function watch(ctx: BrowserContext): Promise<void> {
    await ctx.exposeBinding('__reportLayout', ({ page }, result: Result) =>
        record(result, page.url()),
    )
    await ctx.addInitScript(installCheck, { minTarget: MIN_TARGET, rowFrom: ROW_FROM })
    // Every open page is checked before its context closes.
    const close = ctx.close.bind(ctx)
    ctx.close = async (...args) => {
        for (const page of ctx.pages()) await checkLayout(page)
        return close(...args)
    }
}

export const test = base.extend<{ layoutCheck: void }>({
    layoutCheck: [
        async ({ context, browser }, use, info) => {
            state.checks = 0
            state.problems = []
            hasTouch = info.project.use.hasTouch === true
            projectName = info.project.name
            await watch(context)
            const newContext = browser.newContext.bind(browser)
            browser.newContext = async (...args) => {
                const ctx = await newContext(...args)
                await watch(ctx)
                return ctx
            }

            await use()

            browser.newContext = newContext
            // A test that skipped itself showed nothing to check.
            if (info.status === 'skipped') return
            for (const page of context.pages()) await checkLayout(page)
            const unique = [...new Set(state.problems)]
            expect(unique, `Layout on ${info.project.name}:\n${unique.join('\n')}`).toEqual([])
            expect(state.checks, 'The journey checked no screen at all').toBeGreaterThan(0)
        },
        { auto: true },
    ],
})

export { expect }
