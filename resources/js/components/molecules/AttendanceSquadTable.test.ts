import AttendanceSquadTable from '@/components/molecules/AttendanceSquadTable.vue'
import type { AttendanceSquad } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const girls: AttendanceSquad = {
    name: 'Level 2 Girls',
    level: { number: 2, name: 'Level 2' },
    count: 2,
    children: [
        {
            id: 11,
            nickname: 'Amara',
            full_name: 'Amara Joy Cruz',
            checked_in_at: '13:05',
            checked_in_by: 'Church Admin',
            present: true,
        },
        {
            id: 12,
            nickname: 'Zoe',
            full_name: 'Zoe Cruz',
            checked_in_at: '13:02',
            checked_in_by: '',
            present: false,
        },
    ],
    head_count: null,
}

describe('AttendanceSquadTable', () => {
    it('names the Squad and how many are in', () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })

        expect(wrapper.find('[data-testid="squad-name"]').text()).toBe('Level 2 Girls')
        expect(wrapper.find('[data-testid="squad-count"]').text()).toBe('2 in')
    })

    it("heads the Squad with a pill in its Level's colour", () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })

        expect(wrapper.find('[data-testid="squad-pill"]').classes()).toContain('bg-level-2-soft')
    })

    it('shows a table from the sm breakpoint up: names, time, who and the roll call — no Level column', () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })
        const table = wrapper.find('[data-testid="attendance-table"]')

        expect(table.classes()).toEqual(expect.arrayContaining(['hidden', 'sm:table']))
        expect(table.findAll('th').map((th) => th.text())).toEqual([
            'Nickname',
            'Full name',
            'Checked in',
            'By',
            'Roll call',
        ])
        const rows = table.findAll('[data-testid="attendance-child"]')
        expect(rows.map((row) => row.findAll('td').map((td) => td.text()))).toEqual([
            ['Amara', 'Amara Joy Cruz', '13:05', 'Church Admin', 'Present ✓'],
            ['Zoe', 'Zoe Cruz', '13:02', '', 'Not marked'],
        ])
    })

    it('stacks each child on a phone: nickname, full name, time and who, then the roll call', () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })
        const list = wrapper.find('[data-testid="attendance-list"]')

        expect(list.classes()).toContain('sm:hidden')
        const rows = list.findAll('[data-testid="attendance-list-child"]')
        expect(
            rows.map((row) =>
                [...row.find('p').element.children].map((s) => s.textContent?.trim()),
            ),
        ).toEqual([
            ['Amara', 'Amara Joy Cruz', '13:05 · by Church Admin', 'Present ✓'],
            ['Zoe', 'Zoe Cruz', '13:02', 'Not marked'],
        ])
        expect(rows[0]?.find('span').classes()).toContain('font-bold')
    })

    it('shows each child present or not marked, on a phone and in the table', () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })

        const phone = wrapper.findAll(
            '[data-testid="attendance-list-child"] [data-testid="child-roll-call"]',
        )
        const table = wrapper.findAll(
            '[data-testid="attendance-child"] [data-testid="child-roll-call"]',
        )
        for (const cells of [phone, table]) {
            expect(cells.map((c) => c.text())).toEqual(['Present ✓', 'Not marked'])
        }
        expect(wrapper.findAll('th').map((th) => th.text())).toContain('Roll call')
    })

    it('says "No head count" until the Squad is counted, with no warning', () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })

        expect(wrapper.find('[data-testid="squad-head-count"]').text()).toBe('No head count')
        expect(wrapper.find('[data-testid="squad-mismatch"]').exists()).toBe(false)
    })

    it("shows the head count with when and who, and the same warning as Who's in when it does not match", () => {
        const wrapper = mount(AttendanceSquadTable, {
            props: {
                squad: {
                    ...girls,
                    head_count: { count: 3, counted_at: '13:20', counted_by: 'Joy' },
                },
            },
        })

        expect(wrapper.find('[data-testid="squad-head-count"]').text()).toBe(
            'Head count 3 · 13:20 by Joy',
        )
        const warning = wrapper.find('[data-testid="squad-mismatch"]')
        expect(warning.attributes('role')).toBe('status')
        expect(warning.text()).toBe('⚠ Checked in 2, counted 3. Not marked: Zoe')
    })

    it('gives no warning when the count matches, and leaves out a name that has gone', () => {
        const wrapper = mount(AttendanceSquadTable, {
            props: {
                squad: { ...girls, head_count: { count: 2, counted_at: '13:20', counted_by: '' } },
            },
        })

        expect(wrapper.find('[data-testid="squad-head-count"]').text()).toBe('Head count 2 · 13:20')
        expect(wrapper.find('[data-testid="squad-mismatch"]').exists()).toBe(false)
    })

    it('keeps a counted Squad with no one in now: "0 in", its head count and the warning, no rows', () => {
        const wrapper = mount(AttendanceSquadTable, {
            props: {
                squad: {
                    ...girls,
                    count: 0,
                    children: [],
                    head_count: { count: 2, counted_at: '13:30', counted_by: 'Joy' },
                },
            },
        })

        expect(wrapper.find('[data-testid="squad-count"]').text()).toBe('0 in')
        expect(wrapper.find('[data-testid="squad-head-count"]').text()).toBe(
            'Head count 2 · 13:30 by Joy',
        )
        expect(wrapper.find('[data-testid="squad-mismatch"]').text()).toBe(
            '⚠ Checked in 0, counted 2.',
        )
        expect(wrapper.find('[data-testid="attendance-table"]').exists()).toBe(false)
        expect(wrapper.findAll('[data-testid="attendance-list-child"]')).toHaveLength(0)
    })
})
