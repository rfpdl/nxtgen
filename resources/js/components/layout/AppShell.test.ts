import AppShell from '@/components/layout/AppShell.vue'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const page = { props: { flash: {}, auth: { user: null, can_switch: true } } }

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({ route: (name: string) => `/#${name}` }))

const items = [
    { label: 'Check in', href: '/ccf/door', testid: 'nav-door', current: true },
    { label: 'Families', href: '/ccf/families' },
]

const flush = () => new Promise((resolve) => setTimeout(resolve, 10))

/** A screen whose width can cross 1024px mid-test: matchMedia('(min-width: 1024px)') with its change listeners. */
const wide = { matches: false, listeners: new Set<(e: { matches: boolean }) => void>() }
function crossTo(matches: boolean): void {
    wide.matches = matches
    for (const listener of wide.listeners) listener({ matches })
}
vi.stubGlobal('matchMedia', (query: string) => ({
    get matches() {
        return query.includes('1024') ? wide.matches : false
    },
    media: query,
    addEventListener: (_: string, l: (e: { matches: boolean }) => void) => wide.listeners.add(l),
    removeEventListener: (_: string, l: (e: { matches: boolean }) => void) =>
        wide.listeners.delete(l),
    addListener: (l: (e: { matches: boolean }) => void) => wide.listeners.add(l),
    removeListener: (l: (e: { matches: boolean }) => void) => wide.listeners.delete(l),
}))
const sheet = () => document.body.querySelector<HTMLElement>('[data-testid="menu-sheet"]')

function shell() {
    return mount(AppShell, {
        props: { title: 'Check in', place: 'CCF DXB', items },
        slots: { default: '<p>the page</p><button data-testid="outside">outside</button>' },
        attachTo: document.body,
    })
}

async function openSheet(wrapper: ReturnType<typeof shell>): Promise<HTMLElement> {
    const button = wrapper.find<HTMLElement>('[data-testid="menu-open"]')
    button.element.focus()
    await button.trigger('click')
    await flush()
    return button.element
}

beforeEach(() => {
    document.body.innerHTML = ''
    wide.matches = false
})

describe('AppShell', () => {
    it('shows the logo, the page name and the place in the amber bar, and the page under it', () => {
        const wrapper = shell()

        expect(wrapper.find('header').classes()).toEqual(
            expect.arrayContaining(['bg-bar', 'focus-on-bar']),
        )
        expect(wrapper.find('svg[aria-label="NxtGen"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="page-name"]').text()).toBe('Check in')
        expect(wrapper.text()).toContain('CCF DXB')
        expect(wrapper.find('main').text()).toContain('the page')
        wrapper.unmount()
    })

    it('picks the sheet or the row with CSS alone — the row from 1024px (lg), where the whole menu fits on one line', () => {
        const wrapper = shell()

        expect(wrapper.find('[data-testid="menu-open"]').classes()).toContain('lg:hidden')
        const row = wrapper.find('[data-testid="menu-row"]')
        expect(row.classes()).toEqual(expect.arrayContaining(['hidden', 'lg:flex']))
        expect(row.classes()).not.toContain('md:flex')
        // Base-size words, so an admin's ten items fit one line in the 1024px frame.
        const families = row.findAll('a').find((a) => a.text() === 'Families')
        expect(families?.classes()).toContain('text-base!')
        expect(row.text()).toContain('Families')
        expect(row.find('[data-testid="sign-out"]').exists()).toBe(true)
        wrapper.unmount()
    })

    it('keeps the desktop items compact, so Sign out shares their row and wraps only when narrow', () => {
        const wrapper = shell()
        const row = wrapper.find('[data-testid="menu-row"]')

        expect(row.classes()).toContain('flex-wrap')
        const items = row.findAll('a').filter((a) => ['Check in', 'Families'].includes(a.text()))
        expect(items).toHaveLength(2)
        expect(items.every((a) => a.classes().includes('px-2.5!'))).toBe(true)
        wrapper.unmount()
    })

    it('opens the menu as a sheet of big buttons, with Switch and Sign out', async () => {
        const wrapper = shell()
        await openSheet(wrapper)

        expect(sheet()?.getAttribute('role')).toBe('dialog')
        expect(sheet()?.textContent).toContain('Families')
        expect(sheet()?.querySelector('[aria-current="page"]')?.textContent).toBe('Check in')
        expect(sheet()?.querySelector('[data-testid="switch-place"]')).not.toBeNull()
        expect(sheet()?.querySelector('[data-testid="sign-out"]')).not.toBeNull()
        wrapper.unmount()
    })

    it('closes on Esc and gives focus back to the Menu button', async () => {
        const wrapper = shell()
        const button = await openSheet(wrapper)
        expect(sheet()?.contains(document.activeElement)).toBe(true)

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
        await flush()

        expect(sheet()).toBeNull()
        expect(document.activeElement).toBe(button)
        wrapper.unmount()
    })

    it('keeps focus inside the open sheet', async () => {
        const wrapper = shell()
        await openSheet(wrapper)

        document.body.querySelector<HTMLElement>('[data-testid="outside"]')?.focus()
        await flush()

        expect(sheet()?.contains(document.activeElement)).toBe(true)
        wrapper.unmount()
    })

    it("wraps Safari's Option+Tab round the sheet too (the focus trap ignores Tab with Alt)", async () => {
        const wrapper = shell()
        await openSheet(wrapper)
        const controls = [...(sheet()?.querySelectorAll<HTMLElement>('a[href], button') ?? [])]
        const [first, last] = [controls[0], controls[controls.length - 1]]

        last?.focus()
        last?.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'Tab', altKey: true, bubbles: true }),
        )
        expect(document.activeElement).toBe(first)

        first?.dispatchEvent(
            new KeyboardEvent('keydown', {
                key: 'Tab',
                altKey: true,
                shiftKey: true,
                bubbles: true,
            }),
        )
        expect(document.activeElement).toBe(last)
        wrapper.unmount()
    })

    it('closes from its own close button', async () => {
        const wrapper = shell()
        await openSheet(wrapper)

        document.body.querySelector<HTMLElement>('[data-testid="menu-close"]')?.click()
        await flush()
        expect(sheet()).toBeNull()
        wrapper.unmount()
    })
    it('without items it is just the bar and the logo: no Menu button, no menu row, no Sign out', () => {
        const wrapper = mount(AppShell, {
            props: { title: 'Sign in' },
            slots: { default: '<p>the page</p>' },
        })

        expect(wrapper.find('svg[aria-label="NxtGen"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="page-name"]').text()).toBe('Sign in')
        expect(wrapper.find('[data-testid="menu-open"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="menu-row"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="sign-out"]').exists()).toBe(false)
    })

    it('keeps a narrow page to one easy column on a wide screen, the bar lined up with it', () => {
        const wrapper = mount(AppShell, { props: { title: 'My family', items, narrow: true } })

        expect(wrapper.find('main').classes()).toContain('max-w-2xl')
        expect(wrapper.find('header > div').classes()).toContain('max-w-2xl')
        expect(shell().find('main').classes()).toContain('max-w-5xl')
    })

    it('closes the sheet when the screen grows to the row (1024px) — nothing left trapped or inert', async () => {
        const wrapper = shell()
        await openSheet(wrapper)
        expect(sheet()).not.toBeNull()

        crossTo(true)
        await flush()

        expect(sheet()).toBeNull()
        expect(wrapper.find('[data-testid="menu-open"]').attributes('aria-expanded')).toBe('false')
        // Shrinking back opens nothing by itself.
        crossTo(false)
        await flush()
        expect(sheet()).toBeNull()
        wrapper.unmount()
    })

    it('stops listening to the screen once it is gone', () => {
        const before = wide.listeners.size
        const wrapper = shell()
        expect(wide.listeners.size).toBe(before + 1)

        wrapper.unmount()

        expect(wide.listeners.size).toBe(before)
    })
})
