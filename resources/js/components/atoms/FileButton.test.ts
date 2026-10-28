import FileButton from '@/components/atoms/FileButton.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('FileButton', () => {
    it('is a labelled file picker: the words name the input, which takes the test id', () => {
        const wrapper = mount(FileButton, {
            props: { accept: 'image/*', capture: 'environment' },
            attrs: { 'data-testid': 'take-photo', class: 'w-full' },
            slots: { default: 'Take a photo' },
        })
        const input = wrapper.find('input')

        expect(wrapper.element.tagName).toBe('LABEL')
        expect(wrapper.text()).toBe('Take a photo')
        expect(wrapper.classes()).toContain('w-full')
        expect(input.attributes()).toMatchObject({
            type: 'file',
            accept: 'image/*',
            capture: 'environment',
            'data-testid': 'take-photo',
        })
        expect(input.classes()).toEqual(['sr-only'])
    })

    it('passes the chosen file on (change)', async () => {
        const wrapper = mount(FileButton, { slots: { default: 'Choose a photo' } })

        await wrapper.find('input').trigger('change')
        expect(wrapper.emitted('change')).toHaveLength(1)
    })
})
