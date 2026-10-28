import TextArea from '@/components/atoms/TextArea.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('TextArea', () => {
    it('labels its box and works with v-model', async () => {
        const wrapper = mount(TextArea, { props: { label: 'Allergies', modelValue: 'Peanuts' } })
        const box = wrapper.find('textarea')

        expect(wrapper.find('label').attributes('for')).toBe(box.attributes('id'))
        expect(box.element.value).toBe('Peanuts')
        expect(box.attributes('rows')).toBe('4')
        await box.setValue('Eggs')
        expect(wrapper.emitted('update:modelValue')).toEqual([['Eggs']])
    })

    it('ties the hint and the error to the box, and marks it invalid', () => {
        const wrapper = mount(TextArea, {
            props: { label: 'Notes', hint: 'Anything we should know', error: 'Too long' },
            attrs: { 'data-testid': 'notes' },
        })
        const box = wrapper.find('[data-testid="notes"]')
        const ids = (box.attributes('aria-describedby') ?? '').split(' ')

        expect(wrapper.find(`#${ids[0]}`).text()).toBe('Anything we should know')
        expect(wrapper.find(`#${ids[1]}`).text()).toBe('Too long')
        expect(box.attributes('aria-invalid')).toBe('true')
    })
})
