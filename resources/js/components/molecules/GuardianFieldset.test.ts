import GuardianFieldset from '@/components/molecules/GuardianFieldset.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { reactive } from 'vue'

const relationships = [
    { value: 'mother', label: 'Mother' },
    { value: 'father', label: 'Father' },
]

const guardian = () =>
    reactive({
        first_name: '',
        last_name: '',
        relationship: 'mother',
        phone: '',
        email: '',
        ministries: [] as string[],
    })

describe('GuardianFieldset', () => {
    it('asks for the email only on registration', () => {
        const withEmail = mount(GuardianFieldset, {
            props: { modelValue: guardian(), relationships, errors: {}, withEmail: true },
        })
        const without = mount(GuardianFieldset, {
            props: { modelValue: guardian(), relationships, errors: {}, withEmail: false },
        })

        expect(withEmail.find('[data-testid="guardian-email"]').exists()).toBe(true)
        expect(without.find('[data-testid="guardian-email"]').exists()).toBe(false)
    })

    it('writes the parent details and the one ministry back in place', async () => {
        const g = guardian()
        const wrapper = mount(GuardianFieldset, {
            props: { modelValue: g, relationships, errors: {}, withEmail: false },
        })

        await wrapper.find('[data-testid="guardian-first-name"]').setValue('Janice')
        await wrapper.find('[data-testid="ministry"]').setValue('Music')
        expect(g.first_name).toBe('Janice')
        expect(g.ministries).toEqual(['Music'])

        await wrapper.find('[data-testid="ministry"]').setValue('  ')
        expect(g.ministries).toEqual([])
    })

    it('shows the server errors for the parent fields', () => {
        const wrapper = mount(GuardianFieldset, {
            props: {
                modelValue: guardian(),
                relationships,
                errors: { 'guardian.phone': 'Please enter your mobile number.' },
                withEmail: false,
            },
        })

        expect(wrapper.text()).toContain('Please enter your mobile number.')
    })
})
