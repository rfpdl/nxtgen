import PasswordLoginForm from '@/components/organisms/PasswordLoginForm.vue'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()
let form: Record<string, unknown>

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    return {
        Link: { template: '<a><slot /></a>' },
        useForm: (data: Record<string, unknown>) => {
            form = reactive({ ...data, errors: {}, processing: false, post, reset: vi.fn() })
            return form
        },
    }
})

describe('PasswordLoginForm', () => {
    beforeEach(() => post.mockReset())

    it('is the whole form at once — nothing to click first (it has its own page, /loginp)', () => {
        const wrapper = mount(PasswordLoginForm, { props: { action: '/login/password' } })

        expect(wrapper.find('[data-testid="use-password"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="password-password"]').exists()).toBe(true)
    })

    it('sends the email and password to the action', async () => {
        const wrapper = mount(PasswordLoginForm, { props: { action: '/login/password' } })
        await wrapper.find('[data-testid="password-email"]').setValue('leo@example.com')
        await wrapper.find('[data-testid="password-password"]').setValue('a-long-passphrase')
        await wrapper.find('form').trigger('submit')

        expect(form).toMatchObject({ email: 'leo@example.com', password: 'a-long-passphrase' })
        expect(post).toHaveBeenCalledWith('/login/password', expect.anything())
    })

    it('is on the kit: labelled fields and one big Sign in button', () => {
        const wrapper = mount(PasswordLoginForm, { props: { action: '/login/password' } })

        const email = wrapper.find('[data-testid="password-email"]')
        expect(wrapper.find(`label[for="${email.attributes('id')}"]`).text()).toBe('Email')
        expect(wrapper.find('[data-testid="password-submit"]').attributes('type')).toBe('submit')
    })

    it('shows a refused sign-in on the email field', async () => {
        const wrapper = mount(PasswordLoginForm, { props: { action: '/login/password' } })
        form.errors = { email: 'That email and password do not match' }
        await wrapper.vm.$nextTick()

        expect(wrapper.find('[role="alert"]').text()).toBe('That email and password do not match')
    })
})
