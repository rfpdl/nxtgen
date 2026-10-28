import SignOutButton from '@/components/atoms/SignOutButton.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href', 'method', 'as'],
        template: '<a :href="href" :data-method="method"><slot /></a>',
    },
}))

vi.mock('ziggy-js', () => ({ route: (name: string) => `/#${name}` }))

describe('SignOutButton', () => {
    it('signs out with a POST to the logout route', () => {
        const link = mount(SignOutButton).find('[data-testid="sign-out"]')

        expect(link.text()).toBe('Sign out')
        expect(link.attributes('href')).toBe('/#logout')
        expect(link.attributes('data-method')).toBe('post')
        expect(link.classes()).toContain('bg-transparent')
    })

    it('is a big secondary button when the menu sheet asks for one', () => {
        const link = mount(SignOutButton, {
            props: { variant: 'secondary', size: 'lg', block: true },
        }).find('[data-testid="sign-out"]')

        expect(link.classes()).toEqual(expect.arrayContaining(['bg-surface', 'text-xl', 'w-full']))
    })
})
