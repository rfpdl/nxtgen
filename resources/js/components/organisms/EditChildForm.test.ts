import EditChildForm from '@/components/organisms/EditChildForm.vue'
import type { ChildPortal } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const put = vi.fn()
let form: Record<string, unknown>

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    return {
        useForm: (data: Record<string, unknown>) => {
            form = reactive({ ...data, errors: {}, processing: false, put })
            return form
        },
    }
})

const child = (allergies: string | null): ChildPortal => ({
    id: 4,
    firstName: 'Gabriel',
    middleName: null,
    lastName: 'Ramos',
    nickname: 'Gabby',
    gender: 'boy',
    dateOfBirth: '2017-05-10',
    allergies,
    illnesses: null,
    dislikes: null,
    fears: 'Dogs',
    parentNotes: null,
    level: 'Level 3',
    status: 'Active',
    photoConsent: false,
    photoUrl: null,
})
const genders = [{ value: 'boy', label: 'Boy' }]

describe('EditChildForm', () => {
    beforeEach(() => put.mockReset())

    it('opens with the child as saved, the allergy answer included', async () => {
        const wrapper = mount(EditChildForm, {
            props: { action: '/ccf/family/children/4', child: child('Peanuts'), genders },
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
            props: { action: '/x', child: child(null), genders },
        })
        await wrapper.find('[data-testid="edit-child"]').trigger('click')

        expect(form).toMatchObject({ has_allergies: false })
    })

    it('saves to the action it was given', async () => {
        const wrapper = mount(EditChildForm, {
            props: { action: '/ccf/family/children/4', child: child(null), genders },
        })
        await wrapper.find('[data-testid="edit-child"]').trigger('click')
        await wrapper.find('form').trigger('submit')

        expect(put).toHaveBeenCalledWith('/ccf/family/children/4', expect.anything())
    })
})
