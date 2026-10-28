import ChildAttendanceList from '@/components/molecules/ChildAttendanceList.vue'
import type { ChildCheckIn } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

function line(id: number, date: string, present: boolean): ChildCheckIn {
    return { id, date, class: '1PM', satellite: 'CCF DXB', squad: 'Level 2 Girls', present }
}

describe('ChildAttendanceList', () => {
    it('lists each Check-in in the order given: date · class · satellite · Squad, then present or not marked', () => {
        const wrapper = mount(ChildAttendanceList, {
            props: { checkIns: [line(2, 'Sat 31 Oct', true), line(1, 'Sat 24 Oct', false)] },
        })

        expect(wrapper.find('h3').text()).toBe('Attendance')
        const rows = wrapper.findAll('[data-testid="child-attendance-line"]')
        expect(rows.map((r) => r.find('[data-testid="child-attendance-where"]').text())).toEqual([
            'Sat 31 Oct · 1PM · CCF DXB · Level 2 Girls',
            'Sat 24 Oct · 1PM · CCF DXB · Level 2 Girls',
        ])
        expect(rows.map((r) => r.find('[data-testid="child-roll-call"]').text())).toEqual([
            'Present ✓',
            'Not marked',
        ])
        expect(wrapper.find('[data-testid="child-attendance-all"]').exists()).toBe(false)
    })

    it('shows the newest 12 — the first is the newest, the 12th the 12th-newest — and all of them on "Show all"', async () => {
        // Newest first, as the server sends them: Aug 15, 14, … 1.
        const many = Array.from({ length: 15 }, (_, i) => line(15 - i, `Sat ${15 - i} Aug`, false))
        const wrapper = mount(ChildAttendanceList, { props: { checkIns: many } })

        const dates = () =>
            wrapper
                .findAll('[data-testid="child-attendance-where"]')
                .map((w) => w.text().split(' · ')[0])
        expect(dates()).toHaveLength(12)
        expect(dates()[0]).toBe('Sat 15 Aug')
        expect(dates()[11]).toBe('Sat 4 Aug')
        const all = wrapper.find('[data-testid="child-attendance-all"]')
        expect(all.text()).toBe('Show all 15')
        await all.trigger('click')

        expect(dates()).toHaveLength(15)
        expect(dates()[14]).toBe('Sat 1 Aug')
        expect(wrapper.find('[data-testid="child-attendance-all"]').exists()).toBe(false)
    })

    it('shows exactly 12 lines in full, with no "Show all"', () => {
        const twelve = Array.from({ length: 12 }, (_, i) =>
            line(12 - i, `Sat ${12 - i} Sep`, false),
        )
        const wrapper = mount(ChildAttendanceList, { props: { checkIns: twelve } })

        expect(wrapper.findAll('[data-testid="child-attendance-line"]')).toHaveLength(12)
        expect(wrapper.find('[data-testid="child-attendance-all"]').exists()).toBe(false)
    })

    it('says "Not checked in yet." for a child never in', () => {
        const wrapper = mount(ChildAttendanceList, { props: { checkIns: [] } })

        expect(wrapper.find('[data-testid="child-attendance-empty"]').text()).toBe(
            'Not checked in yet.',
        )
        expect(wrapper.findAll('[data-testid="child-attendance-line"]')).toHaveLength(0)
    })
})
