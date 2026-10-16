import ChildAdminControls from '@/components/molecules/ChildAdminControls.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const child = { id: 4, group: 'Level 3 Boys', status: 'Active' }
const groups = [
    { id: 1, name: 'Level 3 Boys' },
    { id: 2, name: 'Level 4 Boys' },
]

describe('ChildAdminControls', () => {
    it('shows each control only to someone who may use it', () => {
        const none = mount(ChildAdminControls, {
            props: { child, canSetStatus: false, canSetGroup: false, groups: [] },
        })
        expect(none.find('[data-testid="status-4"]').exists()).toBe(false)
        expect(none.find('[data-testid="group-4"]').exists()).toBe(false)

        const both = mount(ChildAdminControls, {
            props: { child, canSetStatus: true, canSetGroup: true, groups },
        })
        expect(both.find('[data-testid="status-4"]').exists()).toBe(true)
        expect(both.find('[data-testid="group-4"]').exists()).toBe(true)
    })

    it('says what changed, for this child', async () => {
        const wrapper = mount(ChildAdminControls, {
            props: { child, canSetStatus: true, canSetGroup: true, groups },
        })

        await wrapper.find('[data-testid="group-4"]').setValue('2')
        await wrapper.find('[data-testid="status-4"]').setValue('inactive')

        expect(wrapper.emitted('group')).toEqual([[4, 2]])
        expect(wrapper.emitted('status')).toEqual([[4, 'inactive']])
    })

    it('shows a refused group change only on the child it was for', () => {
        const wrapper = mount(ChildAdminControls, {
            props: {
                child,
                canSetStatus: false,
                canSetGroup: true,
                groups,
                groupError: 'Level 3 Girls is for girls.',
            },
        })

        expect(wrapper.find('[role="alert"]').text()).toBe('Level 3 Girls is for girls.')
    })
})
