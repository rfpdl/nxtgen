import StaffMemberCard from '@/components/molecules/StaffMemberCard.vue'
import type { StaffMember } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const leo: StaffMember = {
    id: 7,
    name: 'Leo Lifeshaper',
    email: 'leo@example.com',
    roles: [
        { id: 1, label: 'Lifeshaper · Holy Trinity Church', canRemove: true },
        { id: 2, label: 'Admin · whole church', canRemove: false },
    ],
}

describe('StaffMemberCard', () => {
    it('shows the person and every role they hold', () => {
        const wrapper = mount(StaffMemberCard, { props: { person: leo } })

        expect(wrapper.text()).toContain('Leo Lifeshaper')
        expect(wrapper.text()).toContain('leo@example.com')
        expect(wrapper.text()).toContain('Lifeshaper · Holy Trinity Church')
        expect(wrapper.text()).toContain('Admin · whole church')
    })

    it('offers Remove only on roles the viewer may remove, and says which', async () => {
        const wrapper = mount(StaffMemberCard, { props: { person: leo } })

        const buttons = wrapper.findAll('[data-testid="remove-role"]')
        expect(buttons).toHaveLength(1)

        await buttons[0]!.trigger('click')

        expect(wrapper.emitted('remove')).toEqual([[1]])
    })
})
