import DoorChildRow from '@/components/molecules/DoorChildRow.vue'
import type { ChildHealth, DoorChild, LevelOption } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

const levels: LevelOption[] = [1, 2, 3, 4].map((level) => ({ level, name: `Level ${level}` }))

const amara: DoorChild = {
    id: 1,
    nickname: 'Amara',
    full_name: 'Amara Cruz',
    level: { number: 2, name: 'Level 2' },
    squad: 'Level 2 Girls',
    status: 'active',
    status_label: 'Active',
    has_allergies: true,
    check_in_id: null,
    suggested_level: null,
}

const health: ChildHealth = {
    id: 1,
    nickname: 'Amara',
    allergies: 'Peanuts',
    illnesses: null,
    dislikes: null,
    fears: 'Fear of the dark',
    guardian_notes: null,
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

    it("colours the Squad by the child's Level, and flags allergies in red", () => {
        const wrapper = row()

        expect(wrapper.find('[data-testid="level-dot"]').classes()).toContain('bg-level-2')
        expect(wrapper.find('[data-testid="allergy-flag"]').classes()).toContain('bg-danger-soft')
    })

    it('ticks and unticks the child', async () => {
        const wrapper = row()

        const box = wrapper.find('[data-testid="tick-child"]')
        expect(box.attributes('role')).toBe('checkbox')
        expect(box.attributes('aria-label')).toBe('Tick Amara')
        await box.trigger('click')
        expect(wrapper.emitted('update:ticked')).toEqual([[true]])
    })

    it('makes the rest of the row one collapsed button holding the name — no Health button', () => {
        const wrapper = row()
        const expand = wrapper.find('[data-testid="child-expand"]')
        const name = wrapper.find('[data-testid="child-name"]')

        expect(expand.element.tagName).toBe('BUTTON')
        expect(expand.attributes('aria-expanded')).toBe('false')
        expect(expand.attributes('aria-controls')).toBeDefined()
        expect(expand.find('[data-testid="child-name"]').exists()).toBe(true)
        expect(expand.find('[data-testid="allergy-flag"]').exists()).toBe(true)
        expect(name.element.tagName).toBe('SPAN')
        expect(name.text()).toBe('Amara')
        expect(wrapper.find('[data-testid="child-health-open"]').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('Health')
    })

    it('keeps the tick and the details apart: a tick never opens, an open never ticks', async () => {
        const wrapper = row()

        await wrapper.find('[data-testid="tick-child"]').trigger('click')
        expect(wrapper.find('[data-testid="child-expand"]').attributes('aria-expanded')).toBe(
            'false',
        )
        await wrapper.find('[data-testid="child-expand"]').trigger('click')
        expect(wrapper.emitted('update:ticked')).toEqual([[true]])
        expect(wrapper.find('[data-testid="child-expand"]').attributes('aria-expanded')).toBe(
            'true',
        )
    })

    it('asks for the health details on the first open only, saying they are on the way', async () => {
        const wrapper = row()
        const expand = wrapper.find('[data-testid="child-expand"]')

        await expand.trigger('click')
        expect(wrapper.emitted('health')).toEqual([[1]])
        expect(wrapper.find('[data-testid="health-loading"]').exists()).toBe(true)

        await expand.trigger('click')
        await wrapper.setProps({ health })
        await expand.trigger('click')
        expect(wrapper.emitted('health')).toEqual([[1]])
        expect(wrapper.find('[data-testid="health-allergies"]').text()).toContain('Peanuts')
    })

    it('shows the health details, status and Level when open — and closes again', async () => {
        const wrapper = row({}, { health })

        await wrapper.find('[data-testid="child-expand"]').trigger('click')
        expect(wrapper.emitted('health')).toBeUndefined()
        const details = wrapper.find('[data-testid="child-details"]')
        expect(details.find('[data-testid="health-allergies"]').text()).toBe('⚠ Allergies: Peanuts')
        expect(details.text()).toContain('Fear of the dark')
        expect(details.find('[data-testid="child-status"]').text()).toBe('Active')
        expect(details.find('[data-testid="child-level"]').text()).toBe('Level 2')

        await wrapper.find('[data-testid="child-expand"]').trigger('click')
        expect(wrapper.find('[data-testid="child-details"]').exists()).toBe(false)
    })

    it('says so when there are no health details to show', async () => {
        const wrapper = row({ level: null, squad: null }, { health: null })

        await wrapper.find('[data-testid="child-expand"]').trigger('click')
        expect(wrapper.emitted('health')).toBeUndefined()
        expect(wrapper.find('[data-testid="health-none"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="child-level"]').text()).toBe('No Level yet')
    })

    it('links to the family page only for staff who may open it', async () => {
        const may = row({}, { health, familyHref: '/ccf/families/7' })
        await may.find('[data-testid="child-expand"]').trigger('click')
        const link = may.find('[data-testid="open-family"]')
        expect(link.text()).toBe('Open family page')
        expect(link.attributes('href')).toBe('/ccf/families/7')

        const mayNot = row({}, { health })
        await mayNot.find('[data-testid="child-expand"]').trigger('click')
        expect(mayNot.find('[data-testid="open-family"]').exists()).toBe(false)
    })

    it('shows a child who is in with a tick mark and Undo, and no box to tick', async () => {
        const wrapper = row({ check_in_id: 9 })

        expect(wrapper.find('[data-testid="checked-in"]').text()).toBe('✓ Checked in')
        expect(wrapper.find('[data-testid="tick-child"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="child-expand"] [data-testid="undo"]').exists()).toBe(
            false,
        )
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

        await wrapper.find('[data-testid="tick-child"]').trigger('click')
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
