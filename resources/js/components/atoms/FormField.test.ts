import FormField from '@/components/atoms/FormField.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('FormField', () => {
    it('labels its control and marks it required', () => {
        const wrapper = mount(FormField, {
            props: { label: 'First name', required: true },
            slots: { default: '<input id="x" />' },
        })

        expect(wrapper.text()).toContain('First name')
        expect(wrapper.text()).toContain('*')
    })

    it('shows the error under the control, and only when there is one', () => {
        expect(
            mount(FormField, { props: { label: 'Email' } })
                .find('[role="alert"]')
                .exists(),
        ).toBe(false)

        const wrapper = mount(FormField, {
            props: { label: 'Email', error: 'Please enter an email.' },
        })

        expect(wrapper.find('[role="alert"]').text()).toBe('Please enter an email.')
    })

    it('shows a hint when given one', () => {
        expect(
            mount(FormField, {
                props: { label: 'Nickname', hint: 'What we call your child' },
            }).text(),
        ).toContain('What we call your child')
    })
})
