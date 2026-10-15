import FamilyListFilters from '@/components/molecules/FamilyListFilters.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const options = {
    satellites: [
        { id: 7, name: 'CCF DXB' },
        { id: 8, name: 'House Church' },
    ],
    groups: [{ id: 3, name: 'Level 2 Girls' }],
    statuses: [
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' },
    ],
}
const empty = {
    q: '',
    satellite: null,
    status: null,
    group: null,
    unconfirmed: false,
    duplicates: false,
}

describe('FamilyListFilters', () => {
    it('shows the current filters', () => {
        const wrapper = mount(FamilyListFilters, {
            props: { filters: { ...empty, q: 'Ramos', satellite: 8, unconfirmed: true }, options },
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

    it('hides the satellite choice when there is only one', () => {
        const wrapper = mount(FamilyListFilters, {
            props: {
                filters: empty,
                options: { ...options, satellites: [options.satellites[0]!] },
            },
        })

        expect(wrapper.find('[data-testid="filter-satellite"]').exists()).toBe(false)
    })

    it('sends only the filters in use, as address parameters', async () => {
        const wrapper = mount(FamilyListFilters, { props: { filters: empty, options } })

        await wrapper.find('[data-testid="filter-q"]').setValue('Ramos')
        await wrapper.find('[data-testid="filter-status"]').setValue('inactive')
        await wrapper.find('[data-testid="filter-duplicates"]').setValue(true)
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('apply')?.at(-1)).toEqual([
            { q: 'Ramos', status: 'inactive', duplicates: 1 },
        ])
    })

    it('clears everything', async () => {
        const wrapper = mount(FamilyListFilters, {
            props: { filters: { ...empty, q: 'x', group: 3 }, options },
        })

        await wrapper.find('[data-testid="filter-clear"]').trigger('click')

        expect(wrapper.emitted('apply')?.at(-1)).toEqual([{}])
    })
})
