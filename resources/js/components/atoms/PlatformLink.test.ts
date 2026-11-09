import PlatformLink from '@/components/atoms/PlatformLink.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = {
    url: '/dubai/admin/families',
    props: { auth: { user: { id: 1, name: 'Pat' }, is_platform_admin: false } },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

vi.mock('ziggy-js', () => ({ route: (name: string) => `#${name}` }))

const link = (props = {}) => mount(PlatformLink, { props }).find('[data-testid="platform-link"]')

describe('PlatformLink', () => {
    it('appears only for the platform admin, and opens /platform', () => {
        page.props.auth.is_platform_admin = false
        expect(link().exists()).toBe(false)

        page.props.auth.is_platform_admin = true
        expect(link().attributes('href')).toBe('#platform.satellites.index')
        expect(link().text()).toBe('Platform')
    })

    it('is not offered on the platform pages themselves', () => {
        page.props.auth.is_platform_admin = true
        page.url = '/platform/people'
        expect(link().exists()).toBe(false)
        page.url = '/dubai/admin/families'
    })

    it('takes its look from explicit props', () => {
        page.props.auth.is_platform_admin = true
        expect(link({ variant: 'secondary', size: 'lg', block: true }).classes()).toEqual(
            expect.arrayContaining(['bg-surface', 'text-xl', 'w-full']),
        )
    })
})
