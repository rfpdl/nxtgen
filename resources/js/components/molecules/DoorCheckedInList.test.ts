import DoorCheckedInList from '@/components/molecules/DoorCheckedInList.vue'
import type { DoorCheckIn } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const checkIns: DoorCheckIn[] = [
    { id: 22, nickname: 'Bea', squad: 'Level 3 Girls', checked_in_at: '12:55' },
    { id: 21, nickname: 'Amara', squad: 'Level 2 Girls', checked_in_at: '12:40' },
]

describe('DoorCheckedInList', () => {
    it('lists each child in the order given — nickname bold, Squad and time — under "Checked in (N)"', () => {
        const wrapper = mount(DoorCheckedInList, { props: { checkIns } })

        expect(wrapper.find('h2').text()).toBe('Checked in (2)')
        const rows = wrapper.findAll('[data-testid="checked-in-row"]')
        expect(rows.map((r) => r.find('[data-testid="checked-in-name"]').text())).toEqual([
            'Bea',
            'Amara',
        ])
        expect(rows[0]?.find('[data-testid="checked-in-name"]').classes()).toContain('font-bold')
        expect(rows[0]?.text()).toContain('Level 3 Girls')
        expect(rows[0]?.text()).toContain('12:55')
    })

    it('asks to undo the Check-in whose Undo is tapped', async () => {
        const wrapper = mount(DoorCheckedInList, { props: { checkIns } })

        await wrapper.findAll('[data-testid="checked-in-undo"]')[1]?.trigger('click')

        expect(wrapper.emitted('undo')).toEqual([[21]])
    })

    it('says how to start when no one is in yet', () => {
        const wrapper = mount(DoorCheckedInList, { props: { checkIns: [] } })

        expect(wrapper.find('h2').text()).toBe('Checked in (0)')
        expect(wrapper.find('[data-testid="checked-in-empty"]').text()).toBe(
            "No one checked in yet. Search a child's or parent's name to check them in.",
        )
        expect(wrapper.findAll('[data-testid="checked-in-row"]')).toHaveLength(0)
    })
})
