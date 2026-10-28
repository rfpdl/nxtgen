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

    it('picks phone or desktop with CSS alone, so the first paint is already right', () => {
        const wrapper = shell()

        expect(wrapper.find('[data-testid="menu-open"]').classes()).toContain('md:hidden')
        const row = wrapper.find('[data-testid="menu-row"]')
        expect(row.classes()).toEqual(expect.arrayContaining(['hidden', 'md:flex']))
        expect(row.text()).toContain('Families')
        expect(row.find('[data-testid="sign-out"]').exists()).toBe(true)
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
})
