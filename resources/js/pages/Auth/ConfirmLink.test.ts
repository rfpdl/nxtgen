import ConfirmLink from '@/pages/Auth/ConfirmLink.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const post = vi.fn()

vi.mock('@inertiajs/vue3', () => ({
    Head: { props: ['title'], template: '<title>{{ title }}</title>' },
    router: { post: (...args: unknown[]) => post(...args) },
    usePage: () => ({ props: { auth: { user: null }, flash: {} } }),
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

vi.mock('ziggy-js', () => ({ route: (name: string) => `/#${name}` }))

describe('ConfirmLink', () => {
    it("says what the link is for in the server's words (MagicLinkRouteEnum), and posts to its action", async () => {
        const wrapper = mount(ConfirmLink, {
            props: {
                purpose: 'claim',
                title: 'Connect your family',
                prompt: 'Press to connect.',
                button: 'Connect now',
                action: '/magic/claim/abc',
            },
        })

        expect(wrapper.find('h1').text()).toBe('Connect your family')
        expect(wrapper.text()).toContain('Press to connect.')
        const button = wrapper.find('[data-testid="confirm-link"]')
        expect(button.text()).toBe('Connect now')
        await button.trigger('click')
        expect(post).toHaveBeenCalledWith('/magic/claim/abc', {}, expect.any(Object))
    })
})
