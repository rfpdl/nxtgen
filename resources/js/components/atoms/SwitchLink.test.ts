import SwitchLink from '@/components/atoms/SwitchLink.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = { props: { auth: { user: { id: 1, name: 'Ben' }, can_switch: false } } }

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, string | number>) =>
        `#${name}` +
        (params
            ? `?${new URLSearchParams(Object.entries(params).map(([k, v]) => [k, String(v)]))}`
            : ''),
}))

describe('SwitchLink', () => {
    it('appears only for someone with more than one place to go, and asks again', () => {
        page.props.auth.can_switch = false
        expect(mount(SwitchLink).find('[data-testid="switch-place"]').exists()).toBe(false)

        page.props.auth.can_switch = true
        const link = mount(SwitchLink).find('[data-testid="switch-place"]')
        expect(link.exists()).toBe(true)
        expect(link.attributes('href')).toBe('#home?choose=1')
    })

    it('takes its look from explicit props', () => {
        page.props.auth.can_switch = true
        const link = mount(SwitchLink, {
            props: { variant: 'secondary', size: 'lg', block: true },
        }).find('[data-testid="switch-place"]')

        expect(link.classes()).toEqual(expect.arrayContaining(['bg-surface', 'text-xl', 'w-full']))
    })
})
