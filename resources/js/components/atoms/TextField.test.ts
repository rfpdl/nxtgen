import TextField from '@/components/atoms/TextField.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('TextField', () => {
    it('labels its input, so a screen reader and a tap on the label both find it', () => {
        const wrapper = mount(TextField, { props: { label: 'Nickname' } })
        const input = wrapper.find('input')

        expect(wrapper.find('label').text()).toBe('Nickname')
        expect(wrapper.find('label').attributes('for')).toBe(input.attributes('id'))
        expect(input.attributes('type')).toBe('text')
    })

    it('ties the hint and the error to the input, and marks it invalid', () => {
        const wrapper = mount(TextField, {
            props: { label: 'Email', hint: 'We send the link here', error: 'Give an email' },
        })
        const input = wrapper.find('input')
        const ids = (input.attributes('aria-describedby') ?? '').split(' ')

        expect(ids).toHaveLength(2)
        expect(wrapper.find(`#${ids[0]}`).text()).toBe('We send the link here')
        expect(wrapper.find(`#${ids[1]}`).text()).toBe('Give an email')
        expect(wrapper.find(`#${ids[1]}`).attributes('role')).toBe('alert')
        expect(input.attributes('aria-invalid')).toBe('true')
    })

    it('is not described or invalid when there is nothing to say', () => {
        const input = mount(TextField, { props: { label: 'Email' } }).find('input')

        expect(input.attributes('aria-describedby')).toBeUndefined()
        expect(input.attributes('aria-invalid')).toBeUndefined()
    })

    it('works with v-model', async () => {
        const wrapper = mount(TextField, { props: { label: 'Name', modelValue: 'Ana' } })

        expect(wrapper.find('input').element.value).toBe('Ana')
        await wrapper.find('input').setValue('Ben')
        expect(wrapper.emitted('update:modelValue')).toEqual([['Ben']])
    })

    it('takes a type, a test id and a placeholder onto the input itself', () => {
        const input = mount(TextField, {
            props: { label: 'Search', type: 'search' },
            attrs: { 'data-testid': 'door-search', placeholder: 'Search name' },
        }).find('input')

        expect(input.attributes('type')).toBe('search')
        expect(input.attributes('data-testid')).toBe('door-search')
        expect(input.attributes('placeholder')).toBe('Search name')
    })

    it('can keep its label for a screen reader only, and shows an icon in front', () => {
        const wrapper = mount(TextField, {
            props: { label: 'Search', hideLabel: true },
            slots: { leading: '<svg data-testid="icon" />' },
        })

        expect(wrapper.find('label').classes()).toContain('sr-only')
        expect(wrapper.find('[data-testid="icon"]').exists()).toBe(true)
        expect(wrapper.find('input').classes()).toContain('pl-12')
    })

    it('sizes its frame with a class, not the input', () => {
        const wrapper = mount(TextField, { props: { label: 'Search' }, attrs: { class: 'flex-1' } })

        expect(wrapper.classes()).toContain('flex-1')
        expect(wrapper.find('input').classes()).not.toContain('flex-1')
    })

    it('marks a required field', () => {
        const wrapper = mount(TextField, { props: { label: 'Name', required: true } })

        expect(wrapper.find('input').attributes('required')).toBeDefined()
        expect(wrapper.find('label').text()).toContain('*')
    })
})
