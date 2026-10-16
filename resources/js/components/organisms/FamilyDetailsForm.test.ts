import FamilyDetailsForm from '@/components/organisms/FamilyDetailsForm.vue'
import type { FamilyDetails, GuardianEdit } from '@/types'
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

const details: FamilyDetails = {
    home_satellite_id: 7,
    area_text: 'Al Barsha',
    fb_account: null,
    wants_fb_group: false,
    in_dgroup: true,
    dgroup_area_text: 'Barsha',
    dgroup_leader_name: 'Leah',
    dgroup_leader_phone: null,
    wants_dgroup: false,
    lifestage: null,
    notes: null,
}
const guardian: GuardianEdit = {
    first_name: 'Janice',
    last_name: 'Ramos',
    relationship: 'mother',
    phone: '0501234567',
    ministries: ['Music'],
}
const props = {
    action: '/ccf/family',
    details,
    guardian,
    satellites: [{ id: 7, name: 'CCF DXB' }],
}

describe('FamilyDetailsForm', () => {
    beforeEach(() => put.mockReset())

    it('stays closed until asked', async () => {
        const wrapper = mount(FamilyDetailsForm, { props })
        expect(wrapper.find('[data-testid="area"]').exists()).toBe(false)

        await wrapper.find('[data-testid="edit-family"]').trigger('click')

        expect(wrapper.find('[data-testid="area"]').exists()).toBe(true)
    })

    it('starts from what is saved, and sends the form field names the server expects', async () => {
        const wrapper = mount(FamilyDetailsForm, { props })
        await wrapper.find('[data-testid="edit-family"]').trigger('click')

        expect((wrapper.find('[data-testid="area"]').element as HTMLInputElement).value).toBe(
            'Al Barsha',
        )
        await wrapper.find('form').trigger('submit')

        expect(form).toMatchObject({
            home_satellite_id: 7,
            area_text: 'Al Barsha',
            in_dgroup: true,
            dgroup_area_text: 'Barsha',
            guardian: {
                first_name: 'Janice',
                last_name: 'Ramos',
                relationship: 'mother',
                phone: '0501234567',
                ministries: ['Music'],
            },
        })
        expect(put).toHaveBeenCalledWith('/ccf/family', expect.anything())
    })

    it('never asks for the email — it has its own confirm-by-link flow', async () => {
        const wrapper = mount(FamilyDetailsForm, { props })
        await wrapper.find('[data-testid="edit-family"]').trigger('click')

        expect(wrapper.find('[data-testid="guardian-email"]').exists()).toBe(false)
    })
})
