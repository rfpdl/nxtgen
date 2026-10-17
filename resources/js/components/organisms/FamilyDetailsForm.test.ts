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
    id: 1,
}
const others: GuardianEdit[] = [
    {
        id: 4,
        first_name: 'Lola',
        last_name: 'Ramos',
        relationship: 'grandparent',
        phone: '0501112222',
        ministries: [],
    },
]
const props = {
    action: '/ccf/family',
    details,
    guardian,
    others,
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

    it("lists the family's other guardians from what is saved, and sends the list as it is left", async () => {
        const wrapper = mount(FamilyDetailsForm, { props })
        await wrapper.find('[data-testid="edit-family"]').trigger('click')

        expect(
            (
                wrapper.find('[data-testid="other-guardian-0-first-name"]')
                    .element as HTMLInputElement
            ).value,
        ).toBe('Lola')
        await wrapper.find('[data-testid="add-guardian"]').trigger('click')
        await wrapper.find('[data-testid="other-guardian-1-first-name"]').setValue('Jun')
        await wrapper.find('[data-testid="other-guardian-0-remove"]').trigger('click')
        await wrapper.find('form').trigger('submit')

        expect(form.guardians).toMatchObject([{ id: null, first_name: 'Jun' }])
        // The saved list is never changed by editing the form.
        expect(others).toHaveLength(1)
    })

    it('sends the ids of the saved guardians taken off, so a guardian added elsewhere since is kept', async () => {
        const wrapper = mount(FamilyDetailsForm, { props })
        await wrapper.find('[data-testid="edit-family"]').trigger('click')

        await wrapper.find('[data-testid="other-guardian-0-remove"]').trigger('click')
        await wrapper.find('form').trigger('submit')

        expect(form.guardians).toEqual([])
        expect(form.removed_guardian_ids).toEqual([4])
    })

    it('keeps what was typed when the save is refused, so each error stays on its row', async () => {
        const wrapper = mount(FamilyDetailsForm, { props })
        await wrapper.find('[data-testid="edit-family"]').trigger('click')
        await wrapper.find('[data-testid="add-guardian"]').trigger('click')
        await wrapper.find('[data-testid="other-guardian-1-first-name"]').setValue('Jun')
        await wrapper.find('form').trigger('submit')

        // A refused save comes back with the errors and the page's props again (a fresh copy of the same list).
        form.errors = { 'guardians.1.phone': 'Please enter their mobile number.' }
        await wrapper.setProps({ others: others.map((g) => ({ ...g })) })

        expect(form.guardians).toMatchObject([{ id: 4 }, { id: null, first_name: 'Jun' }])
        expect(wrapper.find('[data-testid="other-guardian-1-row"]').text()).toContain(
            'Please enter their mobile number.',
        )
    })

    it('starts again from what is saved after a save, so a guardian just added keeps its new id', async () => {
        const wrapper = mount(FamilyDetailsForm, { props })
        await wrapper.find('[data-testid="edit-family"]').trigger('click')
        await wrapper.find('[data-testid="add-guardian"]').trigger('click')
        await wrapper.find('form').trigger('submit')

        const saved: GuardianEdit[] = [...others, { ...others[0]!, id: 9, first_name: 'Jun' }]
        await wrapper.setProps({ others: saved })
        const options = put.mock.calls[0]?.[1] as { onSuccess: () => void }
        options.onSuccess()

        expect(form.guardians).toMatchObject([
            { id: 4, first_name: 'Lola' },
            { id: 9, first_name: 'Jun' },
        ])
        expect(form.removed_guardian_ids).toEqual([])
    })
})
