import CorrectEmailForm from '@/components/organisms/CorrectEmailForm.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'

const put = vi.fn()
const form = reactive({
    email: '',
    errors: {} as Record<string, string>,
    processing: false,
    put,
    reset: vi.fn(),
})

vi.mock('@inertiajs/vue3', () => ({ useForm: () => form }))

describe('CorrectEmailForm', () => {
    it('puts the corrected email to the given action, keeping the scroll', async () => {
        const wrapper = mount(CorrectEmailForm, { props: { action: '/ccf/families/3/email' } })

        await wrapper.find('[data-testid="correct-email"]').setValue('real@example.com')
        await wrapper.find('form').trigger('submit')

        expect(form.email).toBe('real@example.com')
        expect(put).toHaveBeenCalledWith(
            '/ccf/families/3/email',
            expect.objectContaining({ preserveScroll: true }),
        )
        expect(wrapper.find('button').text()).toBe('Save email')
    })

    it("shows the server's error on the field", async () => {
        form.errors = { email: 'That email is already used.' }
        const wrapper = mount(CorrectEmailForm, { props: { action: '/x' } })

        expect(wrapper.find('[role="alert"]').text()).toBe('That email is already used.')
        expect(wrapper.find('input').attributes('aria-invalid')).toBe('true')
    })
})
