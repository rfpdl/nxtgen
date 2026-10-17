import OtherGuardiansFieldset from '@/components/organisms/OtherGuardiansFieldset.vue'
import type { OtherGuardianDraft } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'

vi.mock('@inertiajs/vue3', async (original) =>
    (await import('@/tests/enumPage')).withEnums(await original<object>()),
)

const lola = (): OtherGuardianDraft => ({
    id: 4,
    first_name: 'Lola',
    last_name: 'Ramos',
    relationship: 'grandparent',
    phone: '0501112222',
    ministries: [],
})

function mountWith(guardians: OtherGuardianDraft[], errors: Record<string, string> = {}) {
    const model = reactive({ guardians })
    const wrapper = mount(OtherGuardiansFieldset, {
        props: {
            modelValue: model.guardians,
            'onUpdate:modelValue': (v: OtherGuardianDraft[]) => (model.guardians = v),
            errors,
        },
    })
    return { wrapper, model }
}

it('says which saved guardian was taken off, so only that one is removed — a new row just goes', async () => {
    const { wrapper } = mountWith([lola()])

    await wrapper.find('[data-testid="add-guardian"]').trigger('click')
    await wrapper.find('[data-testid="other-guardian-1-remove"]').trigger('click')
    await wrapper.find('[data-testid="other-guardian-0-remove"]').trigger('click')

    expect(wrapper.emitted('removed')).toEqual([[4]])
})

describe('OtherGuardiansFieldset', () => {
    it('shows each saved guardian with their details, in words about someone else', () => {
        const { wrapper } = mountWith([lola()])

        const first = wrapper.find('[data-testid="other-guardian-0-first-name"]')
        expect((first.element as HTMLInputElement).value).toBe('Lola')
        expect(wrapper.text()).toContain("They are the child's")
        expect(wrapper.find('[data-testid="other-guardian-0-email"]').exists()).toBe(false)
    })

    it('adds a blank guardian with no id, and removes the one asked', async () => {
        const { wrapper, model } = mountWith([lola()])

        await wrapper.find('[data-testid="add-guardian"]').trigger('click')
        expect(model.guardians).toHaveLength(2)
        expect(model.guardians[1]).toMatchObject({ id: null, first_name: '', phone: '' })

        await wrapper.find('[data-testid="other-guardian-0-remove"]').trigger('click')
        expect(model.guardians.map((g) => g.id)).toEqual([null])
    })

    it("shows each guardian's server errors on that guardian only", () => {
        const { wrapper } = mountWith([lola(), { ...lola(), id: 5 }], {
            'guardians.1.phone': 'Please enter their mobile number.',
        })

        const rows = wrapper.findAll('[data-testid^="other-guardian-"][data-testid$="-row"]')
        expect(rows[0]?.text()).not.toContain('Please enter their mobile number.')
        expect(rows[1]?.text()).toContain('Please enter their mobile number.')
    })
})
