import ChurchShell from '@/components/layout/ChurchShell.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = {
    props: {
        flash: {},
        church: { name: 'CCF', slug: 'ccf' },
        auth: { user: null, can_switch: false },
        church_nav: { can_manage_people: false },
    },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({ route: (name: string, slug?: string) => `/${slug ?? ''}#${name}` }))

describe('ChurchShell', () => {
    it('names the church it shares, and links to the registration lists', () => {
        const wrapper = mount(ChurchShell, {
            slots: { default: '<p>body</p>' },
        })

        expect(wrapper.text()).toContain('CCF')
        expect(wrapper.text()).toContain('Families')
        expect(wrapper.text()).toContain('Children')
        expect(wrapper.text()).toContain('body')
    })

    it('has no Register or Duplicates tab: both start from the Families list', () => {
        page.props.church_nav.can_manage_people = true
        const wrapper = mount(ChurchShell)

        expect(wrapper.text()).not.toContain('Register a family')
        expect(wrapper.text()).not.toContain('Duplicates')
    })

    it('offers sign out', () => {
        const wrapper = mount(ChurchShell)

        expect(wrapper.find('[data-testid="sign-out"]').exists()).toBe(true)
    })

    it('shows People only to someone who may manage people', () => {
        page.props.church_nav.can_manage_people = false
        expect(mount(ChurchShell).text()).not.toContain('People')

        page.props.church_nav.can_manage_people = true
        expect(mount(ChurchShell).text()).toContain('People')
    })
})
