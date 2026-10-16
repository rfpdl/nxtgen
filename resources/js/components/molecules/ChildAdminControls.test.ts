import ChildAdminControls from '@/components/molecules/ChildAdminControls.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const child = { id: 4, level: 'Level 3', status: 'Active' }
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
})
