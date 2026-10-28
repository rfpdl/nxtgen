import AppButton from '@/components/atoms/AppButton.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href', 'method', 'as'],
        template: '<a :href="href" :data-method="method" :data-as="as"><slot /></a>',
    },
}))

describe('AppButton', () => {
    it('is a plain button by default, never a form submit by accident', () => {
        const button = mount(AppButton, { slots: { default: 'Check in' } }).find('button')

        expect(button.text()).toBe('Check in')
        expect(button.attributes('type')).toBe('button')
    })

    it('submits a form when asked', () => {
        const button = mount(AppButton, { props: { type: 'submit' } }).find('button')

        expect(button.attributes('type')).toBe('submit')
    })

    it.each([
        ['primary', 'bg-action'],
        ['secondary', 'bg-surface'],
        ['quiet', 'bg-transparent'],
        ['danger', 'bg-danger'],
        ['link', 'underline'],
    ] as const)('draws the %s variant from tokens', (variant, token) => {
        expect(mount(AppButton, { props: { variant } }).classes()).toContain(token)
    })

    it('makes a large button bigger than a medium one', () => {
        expect(mount(AppButton, { props: { size: 'lg' } }).classes()).toContain('text-xl')
        expect(mount(AppButton, { props: { size: 'md' } }).classes()).toContain('text-lg')
    })

    it('presses down only when it can be pressed', () => {
        expect(mount(AppButton).classes()).toContain('not-disabled:active:translate-y-[5px]')
        expect(mount(AppButton, { props: { variant: 'quiet' } }).classes()).not.toContain(
            'not-disabled:active:translate-y-[5px]',
        )
    })

    it('cannot be pressed while disabled', async () => {
        const wrapper = mount(AppButton, { props: { disabled: true } })

        expect(wrapper.find('button').attributes('disabled')).toBeDefined()
    })

    it('says it is busy while loading, and cannot be pressed twice', () => {
        const button = mount(AppButton, {
            props: { loading: true },
            slots: { default: 'Save' },
        }).find('button')

        expect(button.attributes('disabled')).toBeDefined()
        expect(button.attributes('aria-busy')).toBe('true')
        expect(button.find('[data-testid="button-spinner"]').exists()).toBe(true)
        expect(button.text()).toContain('Save')
    })

    it('renders as an Inertia link when given an address', () => {
        const link = mount(AppButton, {
            props: { href: '/ccf/families' },
            slots: { default: 'Families' },
        }).find('a')

        expect(link.attributes('href')).toBe('/ccf/families')
        expect(link.attributes('data-as')).toBe('a')
        expect(link.text()).toBe('Families')
    })

    it('sends a POST from a link as a button, so a link preview cannot fire it', () => {
        const link = mount(AppButton, { props: { href: '/logout', method: 'post' } }).find('a')

        expect(link.attributes('data-method')).toBe('post')
        expect(link.attributes('data-as')).toBe('button')
    })

    it('does not navigate as a disabled or loading link, and says so', () => {
        const disabled = mount(AppButton, { props: { href: '/ccf/families', disabled: true } })
        const loading = mount(AppButton, { props: { href: '/ccf/families', loading: true } })

        for (const wrapper of [disabled, loading]) {
            const link = wrapper.find('[role="link"]')
            expect(link.attributes('aria-disabled')).toBe('true')
            expect(link.attributes('href')).toBeUndefined()
            expect(link.classes()).toContain('aria-disabled:pointer-events-none')
        }
        expect(loading.find('[role="link"]').attributes('aria-busy')).toBe('true')
        expect(loading.find('[data-testid="button-spinner"]').exists()).toBe(true)
    })

    it('is a disabled button as a disabled POST link', () => {
        const button = mount(AppButton, {
            props: { href: '/logout', method: 'post', disabled: true },
        }).find('button')

        expect(button.attributes('disabled')).toBeDefined()
        expect(button.attributes('type')).toBe('button')
    })

    it('says so when asked to submit a form as a link, which it cannot', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined)
        mount(AppButton, { props: { href: '/x', type: 'submit' } })

        expect(warn).toHaveBeenCalledWith(expect.stringContaining('AppButton'))
    })

    it('passes test ids and labels through to the control', () => {
        const button = mount(AppButton, {
            attrs: { 'data-testid': 'check-in', 'aria-label': 'Check in Amara' },
        }).find('button')

        expect(button.attributes('data-testid')).toBe('check-in')
        expect(button.attributes('aria-label')).toBe('Check in Amara')
    })
})
