import GuardianFieldset from '@/components/molecules/GuardianFieldset.vue'
import type { GuardianDraft } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'

vi.mock('@inertiajs/vue3', async (original) =>
    (await import('@/tests/enumPage')).withEnums(await original<object>()),
)

const guardian = () =>
    reactive<GuardianDraft>({
        first_name: '',
        last_name: '',
        relationship: 'mother',
        phone: '',
        email: '',
        ministries: [],
    })

describe('GuardianFieldset', () => {
    it('asks for the email only on registration', () => {
        const withEmail = mount(GuardianFieldset, {
            props: { modelValue: guardian(), errors: {}, withEmail: true },
        })
        const without = mount(GuardianFieldset, {
            props: { modelValue: guardian(), errors: {}, withEmail: false },
        })

        expect(withEmail.find('[data-testid="guardian-email"]').exists()).toBe(true)
        expect(without.find('[data-testid="guardian-email"]').exists()).toBe(false)
    })

    it('writes the parent details and the one ministry back in place', async () => {
        const g = guardian()
        const wrapper = mount(GuardianFieldset, {
            props: { modelValue: g, errors: {}, withEmail: false },
        })

        await wrapper.find('[data-testid="guardian-first-name"]').setValue('Janice')
        await wrapper.find('[data-testid="guardian-ministry"]').setValue('Music')
        expect(g.first_name).toBe('Janice')
        expect(g.ministries).toEqual(['Music'])

        await wrapper.find('[data-testid="guardian-ministry"]').setValue('  ')
        expect(g.ministries).toEqual([])
    })

    it("offers the relationships the page's enums carry", () => {
        const wrapper = mount(GuardianFieldset, {
            props: { modelValue: guardian(), errors: {}, withEmail: false },
        })

        expect(
            wrapper.findAll('[data-testid="guardian-relationship"] option').map((o) => o.text()),
        ).toEqual([
            'Mother',
            'Father',
            'Guardian',
            'Grandparent',
            'Aunt / uncle',
            'Sibling',
            'Other',
        ])
    })

    it('shows the server errors for the parent fields', () => {
        const wrapper = mount(GuardianFieldset, {
            props: {
                modelValue: guardian(),
                errors: { 'guardian.phone': 'Please enter your mobile number.' },
                withEmail: false,
            },
        })

        expect(wrapper.text()).toContain('Please enter your mobile number.')
    })

    it('speaks about another guardian, with its own test ids and errors', () => {
        const wrapper = mount(GuardianFieldset, {
            props: {
                modelValue: guardian(),
                errors: { 'guardians.1.phone': 'Please enter their mobile number.' },
                withEmail: false,
                errorKey: 'guardians.1',
                testid: 'other-guardian-1',
                other: true,
            },
        })

        expect(wrapper.text()).toContain('First name')
        expect(wrapper.text()).not.toContain('Your first name')
        expect(wrapper.text()).toContain('Please enter their mobile number.')
        expect(wrapper.find('[data-testid="other-guardian-1-phone"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="other-guardian-1-ministry"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="guardian-phone"]').exists()).toBe(false)
    })
})
