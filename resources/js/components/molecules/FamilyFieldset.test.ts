import FamilyFieldset from '@/components/molecules/FamilyFieldset.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { reactive } from 'vue'

const satellites = [
    { id: 7, name: 'CCF DXB' },
    { id: 8, name: 'House Church' },
]

const family = () =>
    reactive({
        home_satellite_id: '' as number | '',
        area_text: '',
        fb_account: '',
        wants_fb_group: false,
        in_dgroup: false,
        dgroup_area_text: '',
        dgroup_leader_name: '',
        dgroup_leader_phone: '',
        wants_dgroup: false,
        lifestage: '',
    })

describe('FamilyFieldset', () => {
    it('offers the church satellites', () => {
        const wrapper = mount(FamilyFieldset, {
            props: { modelValue: family(), satellites, errors: {} },
        })

        expect(
            wrapper
                .find('[data-testid="home-satellite"]')
                .findAll('option')
                .map((o) => o.text()),
        ).toEqual(['Choose…', 'CCF DXB', 'House Church'])
    })

    it('asks the DGroup details only of a parent already in one, and the wish to join otherwise', async () => {
        const f = family()
        const wrapper = mount(FamilyFieldset, { props: { modelValue: f, satellites, errors: {} } })
        expect(wrapper.find('[data-testid="dgroup-area"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="wants-dgroup"]').exists()).toBe(true)

        await wrapper.find('[data-testid="in-dgroup"]').trigger('click')

        expect(f.in_dgroup).toBe(true)
        expect(wrapper.find('[data-testid="dgroup-area"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="dgroup-leader-phone"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="wants-dgroup"]').exists()).toBe(false)
    })

    it('writes the fields back in place', async () => {
        const f = family()
        const wrapper = mount(FamilyFieldset, { props: { modelValue: f, satellites, errors: {} } })

        await wrapper.find('[data-testid="area"]').setValue('Deira')
        await wrapper.find('[data-testid="lifestage"]').setValue('Married')

        expect(f.area_text).toBe('Deira')
        expect(f.lifestage).toBe('Married')
    })
})
