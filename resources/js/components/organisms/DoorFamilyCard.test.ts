import DoorFamilyCard from '@/components/organisms/DoorFamilyCard.vue'
import type { ChildHealth, DoorChild, DoorFamily, LevelOption } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const routerPost = vi.fn()
const routerPut = vi.fn()

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { church: { name: 'CCF', slug: 'ccf' } } }),
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: {
        post: (...args: unknown[]) => routerPost(...args),
        put: (...args: unknown[]) => routerPut(...args),
    },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `${name}:${JSON.stringify(params)}`,
}))

const levels: LevelOption[] = [1, 2, 3, 4].map((level) => ({ level, name: `Level ${level}` }))

function child(id: number, nickname: string, extra: Partial<DoorChild> = {}): DoorChild {
    return {
        id,
        nickname,
        full_name: `${nickname} Cruz`,
        level: { number: 2, name: 'Level 2' },
        squad: 'Level 2 Girls',
        status: 'active',
        status_label: 'Active',
        has_allergies: false,
        check_in_id: null,
        suggested_level: null,
        ...extra,
    }
}

const cruz: DoorFamily = {
    id: 7,
    name: 'Cruz family',
    primary_name: 'Maria Cruz',
    satellite: 'CCF DXB',
    children: [
        child(1, 'Amara'),
        child(2, 'Ben', { check_in_id: 40 }),
        child(3, 'Noah', { status: 'inactive', status_label: 'Inactive' }),
        child(4, 'Lola', { level: null, squad: null, suggested_level: 3 }),
    ],
    can_open: false,
}

const amaraHealth: ChildHealth = {
    id: 1,
    nickname: 'Amara',
    allergies: 'Peanuts',
    illnesses: null,
    dislikes: null,
    fears: null,
    guardian_notes: null,
}

function card(classId: number | null = 5, family: DoorFamily = cruz) {
    return mount(DoorFamilyCard, { props: { family, levels, classId, healths: {} } })
}

function tick(wrapper: ReturnType<typeof card>, index: number) {
    return wrapper
        .findAll('[data-testid="door-child"]')
        [index]!.find('[data-testid="tick-child"]')
        .trigger('click')
}

describe('DoorFamilyCard', () => {
    beforeEach(() => {
        routerPost.mockReset()
        routerPut.mockReset()
    })

    it('names the family, the parent and their satellite, with every child', () => {
        const wrapper = card()

        expect(wrapper.find('h2').text()).toBe('Cruz family')
        expect(wrapper.text()).toContain('Maria Cruz · CCF DXB')
        expect(wrapper.findAll('[data-testid="door-child"]')).toHaveLength(4)
    })

    it('counts the ticked children on the button, and checks them in to the class', async () => {
        const wrapper = card()
        const button = wrapper.find('[data-testid="check-in"]')
        expect(button.attributes('disabled')).toBeDefined()
        // Short enough not to wrap on a phone.
        expect(button.text()).toBe('Tick to check in')

        await tick(wrapper, 0)
        expect(button.text()).toBe('Check in 1 child')
        await button.trigger('click')

        expect(routerPost).toHaveBeenCalledWith(
            'church.door.check-in:["ccf",5]',
            { children: [{ child_id: 1, level: null, make_active: false }] },
            expect.objectContaining({ preserveScroll: true }),
        )
    })

    it('waits for "Make active again?" before checking in an inactive child, then sends the yes', async () => {
        const wrapper = card()
        await tick(wrapper, 0)
        await tick(wrapper, 2)

        const button = wrapper.find('[data-testid="check-in"]')
        expect(button.text()).toBe('Check in 2 children')
        expect(button.attributes('disabled')).toBeDefined()

        await wrapper.find('[data-testid="make-active-yes"]').trigger('click')
        await button.trigger('click')

        expect(routerPost.mock.calls[0]![1]).toEqual({
            children: [
                { child_id: 1, level: null, make_active: false },
                { child_id: 3, level: null, make_active: true },
            ],
        })
    })

    it('sends the Level picked for a child with none — the age suggestion unless changed', async () => {
        const wrapper = card()
        await tick(wrapper, 3)
        await wrapper.find('[data-testid="check-in"]').trigger('click')

        expect(routerPost.mock.calls[0]![1]).toEqual({
            children: [{ child_id: 4, level: 3, make_active: false }],
        })
    })

    it('undoes a Check-in', async () => {
        const wrapper = card()

        await wrapper.find('[data-testid="undo"]').trigger('click')
        expect(routerPut).toHaveBeenCalledWith(
            'church.door.undo:["ccf",40]',
            {},
            expect.objectContaining({ preserveScroll: true }),
        )
    })

    it("asks up for a child's health details on their first open, and shows them once given", async () => {
        const wrapper = card()

        await wrapper.find('[data-testid="child-expand"]').trigger('click')
        expect(wrapper.emitted('health')).toEqual([[1]])

        await wrapper.setProps({ healths: { 1: amaraHealth } })
        expect(wrapper.find('[data-testid="health-allergies"]').text()).toContain('Peanuts')
    })

    it('links each child to the family page only when the viewer may open it', async () => {
        const may = card(5, { ...cruz, can_open: true })
        await may.find('[data-testid="child-expand"]').trigger('click')
        expect(may.find('[data-testid="open-family"]').attributes('href')).toBe(
            'church.families.show:["ccf",7]',
        )

        const mayNot = card()
        await mayNot.find('[data-testid="child-expand"]').trigger('click')
        expect(mayNot.find('[data-testid="open-family"]').exists()).toBe(false)
    })

    it('checks nobody in when no class is chosen', () => {
        const wrapper = card(null)

        expect(wrapper.find('[data-testid="check-in"]').exists()).toBe(false)
    })
})
