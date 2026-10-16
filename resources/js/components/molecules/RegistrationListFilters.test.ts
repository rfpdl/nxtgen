import RegistrationListFilters from '@/components/molecules/RegistrationListFilters.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', async (original) =>
    (await import('@/tests/enumPage')).withEnums(await original<object>()),
)

const options = {
    satellites: [
        { id: 7, name: 'CCF DXB' },
        { id: 8, name: 'House Church' },
    ],
    levels: [
        { level: 1, name: 'Level 1' },
        { level: 2, name: 'Level 2' },
    ],
}
const empty = {
    q: '',
    satellite: null,
    status: null,
    level: null,
    unconfirmed: false,
    duplicates: false,
}

describe('RegistrationListFilters', () => {
    it('shows the current filters', () => {
        const wrapper = mount(RegistrationListFilters, {
            props: {
                filters: { ...empty, q: 'Ramos', satellite: 8, unconfirmed: true },
                options,
                familyToggles: true,
            },
        })

        expect((wrapper.find('[data-testid="filter-q"]').element as HTMLInputElement).value).toBe(
            'Ramos',
        )
        expect(
            (wrapper.find('[data-testid="filter-satellite"]').element as HTMLSelectElement).value,
        ).toBe('8')
        expect(
            (wrapper.find('[data-testid="filter-unconfirmed"]').element as HTMLInputElement)
                .checked,
        ).toBe(true)
    })

    it("offers the child statuses the page's enums carry", () => {
        const wrapper = mount(RegistrationListFilters, { props: { filters: empty, options } })

        expect(
            wrapper.findAll('[data-testid="filter-status"] option').map((o) => o.text()),
        ).toEqual(['Any status', 'Active', 'Inactive', 'Graduated'])
    })

    it('hides the satellite choice when there is only one', () => {
        const wrapper = mount(RegistrationListFilters, {
            props: {
                filters: empty,
                options: { ...options, satellites: [options.satellites[0]!] },
            },
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
        await wrapper.find('[data-testid="filter-duplicates"]').setValue(true)
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('apply')?.at(-1)).toEqual([
            { q: 'Ramos', status: 'inactive', level: 2, duplicates: 1 },
        ])
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
