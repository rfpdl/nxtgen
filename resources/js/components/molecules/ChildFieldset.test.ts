import ChildFieldset from '@/components/molecules/ChildFieldset.vue'
import type { ChildDraft } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'

vi.mock('@inertiajs/vue3', async (original) =>
    (await import('@/tests/enumPage')).withEnums(await original<object>()),
)

const blank = (): ChildDraft => ({
    first_name: '',
    middle_name: '',
    last_name: '',
    nickname: '',
    gender: '',
    date_of_birth: '',
    has_allergies: null,
    allergies: '',
    illnesses: '',
    dislikes: '',
    fears: '',
    guardian_notes: '',
})

const fieldset = (props: Record<string, unknown> = {}) =>
    mount(ChildFieldset, {
        props: { modelValue: blank(), index: 0, errors: {}, removable: false, ...props },
    })

describe('ChildFieldset', () => {
    it('numbers the child for the parent', () => {
        expect(fieldset({ index: 1 }).text()).toContain('Child 2')
    })

    it('writes what the parent types back to the child', async () => {
        const child = reactive(blank())
        const wrapper = fieldset({ modelValue: child })

        await wrapper.find('[data-testid="child-0-first-name"]').setValue('Alexis')

        expect(child.first_name).toBe('Alexis')
    })

    it('keeps every field when several change at once, as phone autofill does', () => {
        const child = reactive(blank())
        const wrapper = fieldset({ modelValue: child })

        // No awaits between the writes: the same tick, like autofill filling the whole fieldset.
        for (const [id, value] of [
            ['first-name', 'Alexis'],
            ['last-name', 'Ramos'],
            ['nickname', 'Amara'],
        ] as const) {
            const input = wrapper.find(`[data-testid="child-0-${id}"]`).element as HTMLInputElement
            input.value = value
            input.dispatchEvent(new Event('input'))
        }

        expect(child).toMatchObject({ first_name: 'Alexis', last_name: 'Ramos', nickname: 'Amara' })
    })

    it("offers the genders the page's enums carry", () => {
        const options = fieldset().findAll('[data-testid="child-0-gender"] option')

        expect(options.map((o) => o.text())).toEqual(['Choose…', 'Boy', 'Girl'])
    })

    it('shows the server error for its own child only', () => {
        const wrapper = fieldset({
            index: 1,
            errors: {
                'children.1.first_name': 'Please enter a first name.',
                'children.0.first_name': 'Not mine.',
            },
        })

        expect(wrapper.text()).toContain('Please enter a first name.')
        expect(wrapper.text()).not.toContain('Not mine.')
    })

    it('can be removed only when the form allows it', async () => {
        expect(fieldset().find('[data-testid="child-0-remove"]').exists()).toBe(false)

        const wrapper = fieldset({ removable: true })
        await wrapper.find('[data-testid="child-0-remove"]').trigger('click')

        expect(wrapper.emitted('remove')).toHaveLength(1)
    })

    it('asks the allergy question with no answer chosen, and shows details only for yes', async () => {
        const child = reactive(blank())
        const wrapper = fieldset({ modelValue: child })

        const yes = wrapper.find('[data-testid="child-0-has-allergies-yes"]')
        const no = wrapper.find('[data-testid="child-0-has-allergies-no"]')
        expect(yes.attributes('aria-checked')).toBe('false')
        expect(no.attributes('aria-checked')).toBe('false')
        expect(wrapper.find('[data-testid="child-0-allergies"]').exists()).toBe(false)

        await yes.trigger('click')
        expect(child.has_allergies).toBe(true)
        expect(wrapper.find('[data-testid="child-0-allergies"]').exists()).toBe(true)

        await no.trigger('click')
        expect(child.has_allergies).toBe(false)
        expect(wrapper.find('[data-testid="child-0-allergies"]').exists()).toBe(false)
    })

    it('writes the picked gender back as its value, and the placeholder as blank', async () => {
        const child = reactive(blank())
        const wrapper = fieldset({ modelValue: child })

        await wrapper.find('[data-testid="child-0-gender"]').setValue('girl')
        expect(child.gender).toBe('girl')
        await wrapper.find('[data-testid="child-0-gender"]').setValue('')
        expect(child.gender).toBe('')
    })

    it("shows the server's allergy messages on this child", () => {
        const wrapper = fieldset({
            errors: {
                'children.0.has_allergies': 'Please answer: does your child have any allergies?',
            },
        })

        expect(wrapper.text()).toContain('Please answer: does your child have any allergies?')
        const group = wrapper.find('[role="radiogroup"]')
        expect(group.attributes('aria-invalid')).toBe('true')
        expect(group.attributes('aria-describedby')).toBe(
            wrapper.find('[role="alert"]').attributes('id'),
        )
    })

    it('names the allergy chips by the question, and marks them required', () => {
        const group = fieldset().find('[role="radiogroup"]')

        expect(group.attributes('aria-labelledby')).toBe('child-0-has-allergies-label')
        expect(group.attributes('aria-required')).toBe('true')
    })

    it('draws "Remove this child" as a destructive button', () => {
        expect(
            fieldset({ removable: true }).find('[data-testid="child-0-remove"]').classes(),
        ).toContain('bg-danger')
    })
})
