import RegistrationListFilters from '@/components/molecules/RegistrationListFilters.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', async (original) =>
    (await import('@/tests/enumPage')).withEnums(await original<object>()),
)

const options = {
    levels: [
        { level: 1, name: 'Level 1' },
        { level: 2, name: 'Level 2' },
        { level: 0, name: 'No Level' },
    ],
}
const empty = {
    q: '',
    status: null,
    level: null,
    unconfirmed: false,
    duplicates: false,
}

describe('RegistrationListFilters', () => {
    it('shows the current filters', () => {
        const wrapper = mount(RegistrationListFilters, {
            props: {
                filters: { ...empty, q: 'Ramos', unconfirmed: true },
                options,
                familyToggles: true,
            },
        })

        expect((wrapper.find('[data-testid="filter-q"]').element as HTMLInputElement).value).toBe(
            'Ramos',
        )
        expect(wrapper.find('[data-testid="filter-unconfirmed"]').attributes('aria-checked')).toBe(
            'true',
        )
    })

    it("offers the child statuses the page's enums carry", () => {
        const wrapper = mount(RegistrationListFilters, { props: { filters: empty, options } })

        expect(
            wrapper.findAll('[data-testid="filter-status"] option').map((o) => o.text()),
        ).toEqual(['Any status', 'Active', 'Inactive', 'Graduated'])
    })

    it('offers no satellite choice — a list shows only the satellite in its address (DECISIONS 2026-11-08)', () => {
        const wrapper = mount(RegistrationListFilters, {
            props: { filters: empty, options, familyToggles: true },
        })

        expect(wrapper.find('[data-testid="filter-satellite"]').exists()).toBe(false)
    })

    it('sends only the filters in use, as address parameters', async () => {
        const wrapper = mount(RegistrationListFilters, {
            props: { filters: empty, options, familyToggles: true },
        })

        await wrapper.find('[data-testid="filter-q"]').setValue('Ramos')
        await wrapper.find('[data-testid="filter-status"]').setValue('inactive')
        await wrapper.find('[data-testid="filter-level"]').setValue('2')
        await wrapper.find('[data-testid="filter-duplicates"]').trigger('click')
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('apply')?.at(-1)).toEqual([
            { q: 'Ramos', status: 'inactive', level: 2, duplicates: 1 },
        ])
    })

    it('sends "No Level" as level 0 — a real choice, not an empty one', async () => {
        const wrapper = mount(RegistrationListFilters, { props: { filters: empty, options } })
        await wrapper.find('[data-testid="filter-level"]').setValue('0')
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('apply')?.[0]).toEqual([{ level: 0 }])
    })

    it('clears everything', async () => {
        const wrapper = mount(RegistrationListFilters, {
            props: { filters: { ...empty, q: 'x', level: 2 }, options },
        })

        await wrapper.find('[data-testid="filter-clear"]').trigger('click')

        expect(wrapper.emitted('apply')?.at(-1)).toEqual([{}])
    })

    it('leaves out the family-only filters on the children list', () => {
        const wrapper = mount(RegistrationListFilters, { props: { filters: empty, options } })

        expect(wrapper.find('[data-testid="filter-unconfirmed"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="filter-duplicates"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="filter-level"]').exists()).toBe(true)
    })
})
