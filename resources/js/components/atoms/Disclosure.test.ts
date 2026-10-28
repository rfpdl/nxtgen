import Disclosure from '@/components/atoms/Disclosure.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

function disclosure(open = false) {
    return mount(Disclosure, {
        props: { open },
        attrs: { 'data-testid': 'more' },
        slots: { default: 'Amara', panel: '<p data-testid="inside">Allergies: none</p>' },
    })
}

describe('Disclosure', () => {
    it('is one button that says it is collapsed and which panel it opens', () => {
        const wrapper = disclosure()
        const button = wrapper.find('button')

        expect(button.attributes('type')).toBe('button')
        expect(button.attributes('data-testid')).toBe('more')
        expect(button.attributes('aria-expanded')).toBe('false')
        expect(button.text()).toContain('Amara')
        const panel = wrapper.find(`#${button.attributes('aria-controls')}`)
        expect(panel.exists()).toBe(true)
        expect(panel.find('[data-testid="inside"]').exists()).toBe(false)
    })

    it('opens and closes on a tap', async () => {
        const wrapper = disclosure()

        await wrapper.find('button').trigger('click')
        await wrapper.setProps({ open: true })
        expect(wrapper.find('button').attributes('aria-expanded')).toBe('true')
        expect(wrapper.find('[data-testid="inside"]').text()).toBe('Allergies: none')

        await wrapper.find('button').trigger('click')
        expect(wrapper.emitted('update:open')).toEqual([[true], [false]])
    })

    it('turns its chevron over when open', () => {
        expect(disclosure(false).find('[data-testid="chevron"]').classes()).not.toContain(
            'rotate-180',
        )
        expect(disclosure(true).find('[data-testid="chevron"]').classes()).toContain('rotate-180')
    })

    it('gives each disclosure its own panel id', () => {
        const page = mount({
            components: { Disclosure },
            template: '<div><Disclosure>Amara</Disclosure><Disclosure>Ben</Disclosure></div>',
        })
        const [a, b] = page.findAll('button').map((x) => x.attributes('aria-controls'))

        expect(a).toBeDefined()
        expect(a).not.toBe(b)
    })
})
