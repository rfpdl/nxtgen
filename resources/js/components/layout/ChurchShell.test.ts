import ChurchShell from '@/components/layout/ChurchShell.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = {
    props: {
        flash: {},
        church: { name: 'CCF', slug: 'ccf' },
        auth: { user: null, can_switch: false },
        churchNav: { can_manage_people: false, can_review_duplicates: false },
    },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({ route: (name: string, slug?: string) => `/${slug ?? ''}#${name}` }))

describe('ChurchShell', () => {
    it('names the church it shares, and links to the registration tools', () => {
        const wrapper = mount(ChurchShell, {
            slots: { default: '<p>body</p>' },
        })

        expect(wrapper.text()).toContain('CCF')
        expect(wrapper.text()).toContain('Families')
        expect(wrapper.text()).toContain('Children')
        expect(wrapper.text()).toContain('Register a family')
        expect(wrapper.text()).toContain('body')
    })

    it('offers sign out', () => {
        const wrapper = mount(ChurchShell)

        expect(wrapper.find('[data-testid="sign-out"]').exists()).toBe(true)
    })

    it('shows People only to someone who may manage people', () => {
        page.props.churchNav.can_manage_people = false
        expect(mount(ChurchShell).text()).not.toContain('People')

        page.props.churchNav.can_manage_people = true
        expect(mount(ChurchShell).text()).toContain('People')
    })

    it('shows Duplicates only to someone who may review them', () => {
        page.props.churchNav.can_review_duplicates = false
        expect(mount(ChurchShell).text()).not.toContain('Duplicates')

        page.props.churchNav.can_review_duplicates = true
        expect(mount(ChurchShell).text()).toContain('Duplicates')
    })
})
