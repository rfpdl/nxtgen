import SwitchLink from '@/components/atoms/SwitchLink.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = { props: { auth: { user: { id: 1, name: 'Ben' }, canSwitch: false } } }

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

describe('SwitchLink', () => {
    it('appears only for someone with more than one place to go, and asks again', () => {
        page.props.auth.canSwitch = false
        expect(mount(SwitchLink).find('[data-testid="switch-place"]').exists()).toBe(false)

        page.props.auth.canSwitch = true
        const link = mount(SwitchLink).find('[data-testid="switch-place"]')
        expect(link.exists()).toBe(true)
        expect(link.attributes('href')).toBe('/home?choose=1')
    })
})
