import EditChildForm from '@/components/organisms/EditChildForm.vue'
import type { ChildPortal } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const put = vi.fn()
let form: Record<string, unknown>

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    const { enums } = await import('@/tests/enumPage')
    return {
        usePage: () => ({ props: { enums } }),
        useForm: (data: Record<string, unknown>) => {
            form = reactive({ ...data, errors: {}, processing: false, put })
            return form
        },
    }
})

const child = (allergies: string | null): ChildPortal => ({
    id: 4,
    first_name: 'Gabriel',
    middle_name: null,
    last_name: 'Ramos',
    nickname: 'Gabby',
    gender: 'boy',
    date_of_birth: '2017-05-10',
    allergies,
    illnesses: null,
    dislikes: null,
    fears: 'Dogs',
    guardian_notes: null,
    level: 'Level 3',
    status_label: 'Active',
    photo_consent: false,
    photo_url: null,
})

describe('EditChildForm', () => {
    beforeEach(() => put.mockReset())

    it('opens with the child as saved, the allergy answer included', async () => {
        const wrapper = mount(EditChildForm, {
            props: { action: '/ccf/family/children/4', child: child('Peanuts') },
        })
        await wrapper.find('[data-testid="edit-child"]').trigger('click')

        expect(
            (wrapper.find('[data-testid="child-0-nickname"]').element as HTMLInputElement).value,
        ).toBe('Gabby')
        expect(
            (wrapper.find('[data-testid="child-0-has-allergies-yes"]').element as HTMLInputElement)
                .checked,
        ).toBe(true)
        expect(form).toMatchObject({ has_allergies: true, allergies: 'Peanuts', fears: 'Dogs' })
    })

    it('reads no saved allergies as an answered "no"', async () => {
        const wrapper = mount(EditChildForm, {
            props: { action: '/x', child: child(null) },
        })
        await wrapper.find('[data-testid="edit-child"]').trigger('click')

        expect(form).toMatchObject({ has_allergies: false })
    })

    it('saves to the action it was given', async () => {
        const wrapper = mount(EditChildForm, {
            props: { action: '/ccf/family/children/4', child: child(null) },
        })
        await wrapper.find('[data-testid="edit-child"]').trigger('click')
        await wrapper.find('form').trigger('submit')

        expect(put).toHaveBeenCalledWith('/ccf/family/children/4', expect.anything())
    })
})
