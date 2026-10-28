import SelectField from '@/components/atoms/SelectField.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const options = [
    { value: 1, label: 'Level 1' },
    { value: 2, label: 'Level 2' },
]

describe('SelectField', () => {
    it('labels its list and shows the options, the empty choice first', () => {
        const wrapper = mount(SelectField, {
            props: { label: 'Level', options, placeholder: 'Choose…' },
        })
        const select = wrapper.find('select')

        expect(wrapper.find('label').attributes('for')).toBe(select.attributes('id'))
        expect(select.findAll('option').map((o) => o.text())).toEqual([
            'Choose…',
            'Level 1',
            'Level 2',
        ])
    })

    it('gives back the option’s own value — a number stays a number — and null for the empty choice', async () => {
        const wrapper = mount(SelectField, {
            props: { label: 'Level', options, placeholder: 'Choose…', modelValue: null },
        })

        await wrapper.find('select').setValue('2')
        await wrapper.find('select').setValue('')
        expect(wrapper.emitted('update:modelValue')).toEqual([[2], [null]])
    })

    it('shows what is chosen', () => {
        const wrapper = mount(SelectField, { props: { label: 'Level', options, modelValue: 2 } })

        expect(wrapper.find('select').element.value).toBe('2')
    })

    it('ties the error to the list and puts the test id on it', () => {
        const wrapper = mount(SelectField, {
            props: { label: 'Level', options, error: 'Pick a Level' },
            attrs: { 'data-testid': 'pick-level' },
        })
        const select = wrapper.find('[data-testid="pick-level"]')

        expect(select.attributes('aria-invalid')).toBe('true')
        expect(wrapper.find(`#${select.attributes('aria-describedby')}`).text()).toBe(
            'Pick a Level',
        )
    })
    it('can show its placeholder without letting it be picked (a current value to change from)', () => {
        const wrapper = mount(SelectField, {
            props: {
                label: 'Level',
                placeholder: 'Level 3',
                placeholderDisabled: true,
                options: [],
            },
        })

        expect(wrapper.find('option').attributes('disabled')).toBeDefined()
        expect(wrapper.find('option').text()).toBe('Level 3')
    })
})
