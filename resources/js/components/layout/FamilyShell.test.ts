import FamilyShell from '@/components/layout/FamilyShell.vue'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const page = {
    url: '/ccf/family',
    props: {
        flash: {},
        church: { name: 'CCF DXB', slug: 'ccf' },
        auth: { user: null, can_switch: false },
    },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, slug?: string) => `http://app.test/${slug ?? ''}#${name}`,
}))

beforeEach(() => {
    page.props.auth.can_switch = false
})

describe('FamilyShell', () => {
    it('names the page and the church, in one narrow column, with the page under it', () => {
        const wrapper = mount(FamilyShell, { slots: { default: '<p>body</p>' } })

        expect(wrapper.find('[data-testid="page-name"]').text()).toBe('My family')
        expect(wrapper.text()).toContain('CCF DXB')
        expect(wrapper.find('main').classes()).toContain('max-w-2xl')
        expect(wrapper.find('main').text()).toBe('body')
    })

    it("has the parent's menu: My family and Sign out; Switch only for a parent who also serves", () => {
        const row = () => mount(FamilyShell).find('[data-testid="menu-row"]')

        expect(row().find('[data-testid="nav-family"]').attributes('href')).toBe(
            'http://app.test/ccf#family.show',
        )
        expect(row().find('[data-testid="sign-out"]').exists()).toBe(true)
        expect(row().find('[data-testid="switch-place"]').exists()).toBe(false)

        page.props.auth.can_switch = true
        expect(row().find('[data-testid="switch-place"]').exists()).toBe(true)
    })
})
