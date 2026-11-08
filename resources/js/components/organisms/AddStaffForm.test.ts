import AddStaffForm from '@/components/organisms/AddStaffForm.vue'
import type { GrantOption } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()
let form: Record<string, unknown>

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    return {
        useForm: (data: Record<string, unknown>) => {
            form = reactive({ ...data, errors: {}, processing: false, post, reset: vi.fn() })
            return form
        },
    }
})

const options: GrantOption[] = [
    { label: 'Admin · CCF Dubai', role: 'admin', scope_type: 'satellite', scope_id: 3 },
    {
        label: 'Lifeshaper · CCF Dubai',
        role: 'lifeshaper',
        scope_type: 'satellite',
        scope_id: 3,
    },
]

describe('AddStaffForm', () => {
    beforeEach(() => post.mockReset())

    it('lists only the roles it was given', () => {
        const wrapper = mount(AddStaffForm, { props: { action: '/dubai/admin/people', options } })

        const labels = wrapper.findAll('option').map((o) => o.text())
        expect(labels).toEqual(['Admin · CCF Dubai', 'Lifeshaper · CCF Dubai'])
    })

    it('sends the chosen role and place with the person', async () => {
        const wrapper = mount(AddStaffForm, { props: { action: '/dubai/admin/people', options } })

        await wrapper.find('[data-testid="staff-name"]').setValue('Leo')
        await wrapper.find('[data-testid="staff-email"]').setValue('leo@example.com')
        await wrapper.find('[data-testid="staff-role"]').setValue('1')
        await wrapper.find('form').trigger('submit')

        expect(form).toMatchObject({
            name: 'Leo',
            email: 'leo@example.com',
            role: 'lifeshaper',
            scope_type: 'satellite',
            scope_id: 3,
        })
        expect(post).toHaveBeenCalledWith('/dubai/admin/people', expect.anything())
    })
})
