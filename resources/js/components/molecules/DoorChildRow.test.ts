import DoorChildRow from '@/components/molecules/DoorChildRow.vue'
import type { DoorChild, LevelOption } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const levels: LevelOption[] = [1, 2, 3, 4].map((level) => ({ level, name: `Level ${level}` }))

const amara: DoorChild = {
    id: 1,
    nickname: 'Amara',
    full_name: 'Amara Cruz',
    level: 2,
    squad: 'Level 2 Girls',
    status: 'active',
    status_label: 'Active',
    has_allergies: true,
    check_in_id: null,
    suggested_level: null,
}

function row(child: Partial<DoorChild> = {}, props: Record<string, unknown> = {}) {
    return mount(DoorChildRow, {
        props: { child: { ...amara, ...child }, levels, canCheckIn: true, ...props },
    })
}

describe('DoorChildRow', () => {
    it('shows the nickname, Squad and the allergy flag — never the allergy itself', () => {
        const wrapper = row()

        expect(wrapper.text()).toContain('Amara')
        expect(wrapper.text()).toContain('Level 2 Girls')
        expect(wrapper.find('[data-testid="allergy-flag"]').exists()).toBe(true)
        expect(row({ has_allergies: false }).find('[data-testid="allergy-flag"]').exists()).toBe(
            false,
        )
    })

    it('ticks and unticks the child', async () => {
        const wrapper = row()

        await wrapper.find('[data-testid="tick-child"]').setValue(true)
        expect(wrapper.emitted('update:ticked')).toEqual([[true]])
    })

    it('asks for the health details when the name is tapped', async () => {
        const wrapper = row()

        await wrapper.find('[data-testid="child-name"]').trigger('click')
        expect(wrapper.emitted('health')).toEqual([[1]])
    })

    it('shows a child who is in with a tick mark and Undo, and no box to tick', async () => {
        const wrapper = row({ check_in_id: 9 })

        expect(wrapper.find('[data-testid="checked-in"]').text()).toBe('✓ Checked in')
        expect(wrapper.find('[data-testid="tick-child"]').exists()).toBe(false)
        await wrapper.find('[data-testid="undo"]').trigger('click')
        expect(wrapper.emitted('undo')).toEqual([[9]])
    })

    it('never lets a Graduated child be ticked, and says why', () => {
        const wrapper = row({ status: 'graduated', status_label: 'Graduated' })

        expect(wrapper.find('[data-testid="tick-child"]').attributes('disabled')).toBeDefined()
        expect(wrapper.text()).toContain('Graduated — cannot be checked in')
    })

    it('asks "Make active again?" for a ticked inactive child, and unticks on No', async () => {
        const inactive = { status: 'inactive', status_label: 'Inactive' } as const
        expect(row(inactive).text()).toContain('Inactive')

        const wrapper = row(inactive, { ticked: true })
        expect(wrapper.find('[data-testid="make-active"]').text()).toContain(
            'Amara is inactive. Make them active again?',
        )
        await wrapper.find('[data-testid="make-active-yes"]').trigger('click')
        expect(wrapper.emitted('update:makeActive')).toEqual([[true]])

        await wrapper.find('[data-testid="make-active-no"]').trigger('click')
        expect(wrapper.emitted('update:ticked')).toEqual([[false]])
    })

    it('asks for a Level for a ticked child with none, the age suggestion picked first', async () => {
        const wrapper = row({ level: null, squad: null, suggested_level: 3 })
        expect(wrapper.text()).toContain('No Level yet')

        await wrapper.find('[data-testid="tick-child"]').setValue(true)
        expect(wrapper.emitted('update:level')).toEqual([[3]])

        const ticked = row(
            { level: null, squad: null, suggested_level: 3 },
            { ticked: true, level: 3 },
        )
        const select = ticked.find<HTMLSelectElement>('[data-testid="pick-level"]')
        expect(select.element.value).toBe('3')
        await select.setValue('4')
        expect(ticked.emitted('update:level')).toEqual([[4]])
    })

    it('puts no stray separator before a flag or a status', () => {
        const graduated = row({ status: 'graduated', status_label: 'Graduated' })

        expect(graduated.text()).not.toMatch(/·/)
    })

    it('offers no box to tick when there is no class to check in to', () => {
        expect(
            row({}, { canCheckIn: false })
                .find('[data-testid="tick-child"]')
                .attributes('disabled'),
        ).toBeDefined()
    })
})
