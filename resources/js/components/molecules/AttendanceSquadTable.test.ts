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
        },
        {
            id: 12,
            nickname: 'Zoe',
            full_name: 'Zoe Cruz',
            checked_in_at: '13:02',
            checked_in_by: '',
        },
    ],
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

    it('shows a table from the sm breakpoint up: names, time and who — no Level column', () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })
        const table = wrapper.find('[data-testid="attendance-table"]')

        expect(table.classes()).toEqual(expect.arrayContaining(['hidden', 'sm:table']))
        expect(table.findAll('th').map((th) => th.text())).toEqual([
            'Nickname',
            'Full name',
            'Checked in',
            'By',
        ])
        const rows = table.findAll('[data-testid="attendance-child"]')
        expect(rows.map((row) => row.findAll('td').map((td) => td.text()))).toEqual([
            ['Amara', 'Amara Joy Cruz', '13:05', 'Church Admin'],
            ['Zoe', 'Zoe Cruz', '13:02', ''],
        ])
    })

    it('stacks each child on a phone: nickname, full name, then time and who', () => {
        const wrapper = mount(AttendanceSquadTable, { props: { squad: girls } })
        const list = wrapper.find('[data-testid="attendance-list"]')

        expect(list.classes()).toContain('sm:hidden')
        const rows = list.findAll('[data-testid="attendance-list-child"]')
        expect(rows.map((row) => row.findAll('span').map((s) => s.text()))).toEqual([
            ['Amara', 'Amara Joy Cruz', '13:05 · by Church Admin'],
            ['Zoe', 'Zoe Cruz', '13:02'],
        ])
        expect(rows[0]?.find('span').classes()).toContain('font-bold')
    })
})
