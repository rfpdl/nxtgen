import ChurchShell from '@/components/layout/ChurchShell.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = {
    props: {
        flash: {},
        auth: { user: null, canSwitch: false },
        churchNav: { canManagePeople: false, canReviewDuplicates: false },
    },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({ route: (name: string, slug?: string) => `/${slug ?? ''}#${name}` }))

describe('ChurchShell', () => {
    it('names the church and links to the registration tools', () => {
        const wrapper = mount(ChurchShell, {
            props: { church: { name: 'CCF', slug: 'ccf' } },
            slots: { default: '<p>body</p>' },
        })

        expect(wrapper.text()).toContain('CCF')
        expect(wrapper.text()).toContain('Families')
        expect(wrapper.text()).toContain('Children')
        expect(wrapper.text()).toContain('Register a family')
        expect(wrapper.text()).toContain('body')
    })

    it('offers sign out', () => {
        const wrapper = mount(ChurchShell, { props: { church: { name: 'CCF', slug: 'ccf' } } })

        expect(wrapper.find('[data-testid="sign-out"]').exists()).toBe(true)
    })

    it('shows People only to someone who may manage people', () => {
        page.props.churchNav.canManagePeople = false
        expect(
            mount(ChurchShell, { props: { church: { name: 'CCF', slug: 'ccf' } } }).text(),
        ).not.toContain('People')

        page.props.churchNav.canManagePeople = true
        expect(
            mount(ChurchShell, { props: { church: { name: 'CCF', slug: 'ccf' } } }).text(),
        ).toContain('People')
    })

    it('shows Duplicates only to someone who may review them', () => {
        page.props.churchNav.canReviewDuplicates = false
        expect(
            mount(ChurchShell, { props: { church: { name: 'CCF', slug: 'ccf' } } }).text(),
        ).not.toContain('Duplicates')

        page.props.churchNav.canReviewDuplicates = true
        expect(
            mount(ChurchShell, { props: { church: { name: 'CCF', slug: 'ccf' } } }).text(),
        ).toContain('Duplicates')
    })
})
