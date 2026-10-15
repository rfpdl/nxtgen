import OrgShell from '@/components/layout/OrgShell.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = { props: { flash: {}, orgNav: { canManagePeople: false } } }

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({ route: (name: string, slug?: string) => `/${slug ?? ''}#${name}` }))

describe('OrgShell', () => {
    it('names the church and links to the registration tools', () => {
        const wrapper = mount(OrgShell, {
            props: { church: { name: 'CCF', slug: 'ccf' } },
            slots: { default: '<p>body</p>' },
        })

        expect(wrapper.text()).toContain('CCF')
        expect(wrapper.text()).toContain('Families')
        expect(wrapper.text()).toContain('Register a family')
        expect(wrapper.text()).toContain('body')
    })

    it('offers sign out', () => {
        const wrapper = mount(OrgShell, { props: { church: { name: 'CCF', slug: 'ccf' } } })

        expect(wrapper.find('[data-testid="sign-out"]').exists()).toBe(true)
    })

    it('shows People only to someone who may manage people', () => {
        page.props.orgNav.canManagePeople = false
        expect(
            mount(OrgShell, { props: { church: { name: 'CCF', slug: 'ccf' } } }).text(),
        ).not.toContain('People')

        page.props.orgNav.canManagePeople = true
        expect(
            mount(OrgShell, { props: { church: { name: 'CCF', slug: 'ccf' } } }).text(),
        ).toContain('People')
    })
})
