import CheckRow from '@/components/atoms/CheckRow.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('CheckRow', () => {
    it('is a checkbox to a screen reader, unticked by default', () => {
        const row = mount(CheckRow, { props: { label: 'Tick Amara' } }).find('button')

        expect(row.attributes('role')).toBe('checkbox')
        expect(row.attributes('aria-checked')).toBe('false')
        expect(row.attributes('aria-label')).toBe('Tick Amara')
        expect(row.attributes('type')).toBe('button')
    })

    it('ticks and unticks on a tap', async () => {
        const wrapper = mount(CheckRow, { props: { modelValue: false } })

        await wrapper.find('button').trigger('click')
        await wrapper.setProps({ modelValue: true })
        expect(wrapper.find('button').attributes('aria-checked')).toBe('true')
        await wrapper.find('button').trigger('click')
        expect(wrapper.emitted('update:modelValue')).toEqual([[true], [false]])
    })

    it('shows a ticked box green with a white check', () => {
        const box = mount(CheckRow, { props: { modelValue: true } }).find(
            '[data-testid="tick-box"]',
        )

        expect(box.classes()).toContain('bg-action')
        expect(box.find('svg').exists()).toBe(true)
    })

    it('cannot be ticked while disabled', async () => {
        const wrapper = mount(CheckRow, { props: { disabled: true } })

        expect(wrapper.find('button').attributes('disabled')).toBeDefined()
        await wrapper.find('button').trigger('click')
        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
    })

    it('makes the whole row the tick when it has a label', () => {
        const wrapper = mount(CheckRow, { slots: { default: 'I agree' } })

        expect(wrapper.find('button').text()).toBe('I agree')
        expect(wrapper.find('button').classes()).toContain('w-full')
    })
})
