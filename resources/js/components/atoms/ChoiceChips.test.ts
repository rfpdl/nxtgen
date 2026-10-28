import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const options = [
    { value: 1, label: '1PM', sub: '13:00–15:00' },
    { value: 2, label: '7PM', sub: null },
]

function chips(modelValue: number | null) {
    return mount(ChoiceChips, {
        props: { label: 'Class', options, modelValue },
        attachTo: document.body,
    })
}

describe('ChoiceChips', () => {
    it('is a named radio group, the chosen chip checked', () => {
        const wrapper = chips(1)
        const radios = wrapper.findAll('[role="radio"]')
        const group = wrapper.find('[role="radiogroup"]')

        expect(group.attributes('aria-label')).toBe('Class')
        expect(radios.map((c) => c.attributes('aria-checked'))).toEqual(['true', 'false'])
        expect(radios[0]?.text()).toContain('13:00–15:00')
        expect(radios[1]?.attributes('data-value')).toBe('2')
        wrapper.unmount()
    })

    it('says which was picked (v-model), keeping the value a number', async () => {
        const wrapper = chips(1)

        await wrapper.findAll('[role="radio"]')[1]?.trigger('click')
        expect(wrapper.emitted('update:modelValue')).toEqual([[2]])
        wrapper.unmount()
    })

    it('is one Tab stop that lands on the checked chip, and the arrow keys move between chips', async () => {
        const wrapper = chips(1)
        await new Promise((resolve) => setTimeout(resolve, 10))
        const radios = wrapper.findAll('[role="radio"]')

        const group = wrapper.find('[role="radiogroup"]')
        expect(group.attributes('tabindex')).toBe('0')
        expect(radios.map((c) => c.attributes('tabindex'))).toEqual(['-1', '-1'])
        ;(group.element as HTMLElement).focus()
        await new Promise((resolve) => setTimeout(resolve))
        expect(document.activeElement).toBe(radios[0]?.element)

        await radios[0]?.trigger('keydown', { key: 'ArrowRight' })
        expect(document.activeElement).toBe(radios[1]?.element)
        wrapper.unmount()
    })

    it("puts an option's test id on its chip", () => {
        const wrapper = mount(ChoiceChips, {
            props: {
                label: 'Allergies?',
                options: [
                    { value: 'yes', label: 'Yes', testid: 'allergies-yes' },
                    { value: 'no', label: 'No' },
                ],
                modelValue: null,
            },
        })

        expect(wrapper.find('[data-testid="allergies-yes"]').attributes('role')).toBe('radio')
        expect(wrapper.findAll('[role="radio"]')[1]?.attributes('data-testid')).toBeUndefined()
    })

    it('can be named by a visible question, and says it is required', () => {
        const wrapper = mount(ChoiceChips, {
            props: { labelledby: 'q-1', required: true, options, modelValue: null },
            attrs: { 'data-testid': 'chips' },
        })
        const group = wrapper.find('[role="radiogroup"]')

        expect(group.attributes('aria-labelledby')).toBe('q-1')
        expect(group.attributes('aria-label')).toBeUndefined()
        expect(group.attributes('aria-required')).toBe('true')
        expect(group.attributes('data-testid')).toBe('chips')
        expect(group.attributes('aria-invalid')).toBeUndefined()
    })

    it('ties its error to the group for a screen reader', () => {
        const wrapper = mount(ChoiceChips, {
            props: { label: 'Allergies?', error: 'Please answer.', options, modelValue: null },
        })
        const group = wrapper.find('[role="radiogroup"]')
        const error = wrapper.find('[role="alert"]')

        expect(error.text()).toBe('Please answer.')
        expect(group.attributes('aria-invalid')).toBe('true')
        expect(group.attributes('aria-describedby')).toBe(error.attributes('id'))
    })

    it('checks none when nothing is chosen', () => {
        const wrapper = chips(null)

        expect(wrapper.findAll('[aria-checked="true"]')).toHaveLength(0)
        wrapper.unmount()
    })
})
