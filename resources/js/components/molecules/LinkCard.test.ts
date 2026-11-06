import LinkCard from '@/components/molecules/LinkCard.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({ Link: { template: '<a><slot /></a>' } }))

const link = { id: 7, label: 'WhatsApp group', url: 'https://chat.whatsapp.com/abc' }

describe('LinkCard', () => {
    it('shows the label and the address, and asks to edit or move', async () => {
        const wrapper = mount(LinkCard, { props: { link, first: false, last: false } })

        expect(wrapper.find('[data-testid="link-name"]').text()).toBe('WhatsApp group')
        expect(wrapper.text()).toContain('https://chat.whatsapp.com/abc')
        await wrapper.find('[data-testid="edit-link"]').trigger('click')
        await wrapper.find('[data-testid="link-up"]').trigger('click')
        await wrapper.find('[data-testid="link-down"]').trigger('click')

        expect(wrapper.emitted('edit')).toHaveLength(1)
        expect(wrapper.emitted('move')).toEqual([['up'], ['down']])
    })

    it('moves nothing past either end', () => {
        const top = mount(LinkCard, { props: { link, first: true, last: true } })

        expect(top.find('[data-testid="link-up"]').attributes('disabled')).toBeDefined()
        expect(top.find('[data-testid="link-down"]').attributes('disabled')).toBeDefined()
    })

    it('asks once more before removing, and keeps it when told to', async () => {
        const wrapper = mount(LinkCard, { props: { link, first: true, last: false } })

        await wrapper.find('[data-testid="remove-link"]').trigger('click')
        expect(wrapper.text()).toContain('Remove "WhatsApp group" from the poster?')
        await wrapper.find('[data-testid="keep-link"]').trigger('click')
        expect(wrapper.emitted('remove')).toBeUndefined()

        await wrapper.find('[data-testid="remove-link"]').trigger('click')
        await wrapper.find('[data-testid="confirm-remove-link"]').trigger('click')
        expect(wrapper.emitted('remove')).toHaveLength(1)
    })
})
