import ChildAdminControls from '@/components/molecules/ChildAdminControls.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', async (original) =>
    (await import('@/tests/enumPage')).withEnums(await original<object>()),
)

const child = { id: 4, level: { number: 3, name: 'Level 3' }, status_label: 'Active' }
const levels = [
    { level: 3, name: 'Level 3' },
    { level: 4, name: 'Level 4' },
]

describe('ChildAdminControls', () => {
    it('shows each control only to someone who may use it', () => {
        const none = mount(ChildAdminControls, {
            props: { child, canSetStatus: false, canSetLevel: false, levels: [] },
        })
        expect(none.find('[data-testid="status-4"]').exists()).toBe(false)
        expect(none.find('[data-testid="level-4"]').exists()).toBe(false)

        const both = mount(ChildAdminControls, {
            props: { child, canSetStatus: true, canSetLevel: true, levels },
        })
        expect(both.find('[data-testid="status-4"]').exists()).toBe(true)
        expect(both.find('[data-testid="level-4"]').exists()).toBe(true)
    })

    it('says what changed, for this child', async () => {
        const wrapper = mount(ChildAdminControls, {
            props: { child, canSetStatus: true, canSetLevel: true, levels },
        })

        await wrapper.find('[data-testid="level-4"]').setValue('4')
        await wrapper.find('[data-testid="status-4"]').setValue('inactive')

        expect(wrapper.emitted('level')).toEqual([[4, 4]])
        expect(wrapper.emitted('status')).toEqual([[4, 'inactive']])
    })

    it("shows the child's status, and offers the statuses the page's enums carry", () => {
        const options = mount(ChildAdminControls, {
            props: { child, canSetStatus: true, canSetLevel: false, levels: [] },
        }).findAll('[data-testid="status-4"] option')

        expect(options.map((o) => o.text())).toEqual(['Active', 'Active', 'Inactive', 'Graduated'])
    })

    it("shows the child's Level, or that they have none yet", () => {
        const placed = mount(ChildAdminControls, {
            props: { child, canSetStatus: false, canSetLevel: true, levels },
        })
        expect(placed.find('[data-testid="level-4"] option').text()).toBe('Level 3')

        const none = mount(ChildAdminControls, {
            props: {
                child: { ...child, level: null },
                canSetStatus: false,
                canSetLevel: true,
                levels,
            },
        })
        expect(none.find('[data-testid="level-4"] option').text()).toBe('No Level')
    })

    it('shows a refused Level change only on the child it was for', () => {
        const wrapper = mount(ChildAdminControls, {
            props: {
                child,
                canSetStatus: false,
                canSetLevel: true,
                levels,
                levelError: "Please choose one of the church's Levels.",
            },
        })

        expect(wrapper.find('[role="alert"]').text()).toBe(
            "Please choose one of the church's Levels.",
        )
    })
    it("keeps the child's current Level and status as the first choice, not pickable", () => {
        const wrapper = mount(ChildAdminControls, {
            props: { child, canSetStatus: true, canSetLevel: true, levels },
        })

        for (const id of ['level-4', 'status-4']) {
            expect(
                wrapper.find(`[data-testid="${id}"] option`).attributes('disabled'),
            ).toBeDefined()
        }
    })
})
