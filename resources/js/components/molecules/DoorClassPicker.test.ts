import DoorClassPicker from '@/components/molecules/DoorClassPicker.vue'
import type { DoorClass } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { church: { name: 'CCF', slug: 'ccf' } } }),
}))

const onePm: DoorClass = { id: 1, name: '1PM', satellite: 'CCF DXB', times: '13:00–15:00' }
const sevenPm: DoorClass = { id: 2, name: '7PM', satellite: 'CCF DXB', times: '19:00–21:00' }
const tenAm: DoorClass = { id: 3, name: '10AM', satellite: 'House Church', times: null }

describe('DoorClassPicker', () => {
    it("heads the page with the chosen class's satellite and today, and a chip per class", () => {
        const wrapper = mount(DoorClassPicker, {
            props: { today: 'Sat 31 Oct', classes: [onePm, sevenPm], classId: 2 },
        })

        expect(wrapper.find('[data-testid="door-heading"]').text()).toBe('CCF DXB · Sat 31 Oct')
        const chips = wrapper.findAll('[role="radio"]')
        expect(chips.map((c) => c.text())).toEqual(['1PM 13:00–15:00', '7PM 19:00–21:00'])
        expect(chips[1]?.attributes('aria-checked')).toBe('true')
    })

    it('names the satellite on each chip when the Role covers more than one', () => {
        const wrapper = mount(DoorClassPicker, {
            props: { today: 'Sat 31 Oct', classes: [onePm, tenAm], classId: 1 },
        })

        expect(wrapper.findAll('[role="radio"]').map((c) => c.text())).toEqual([
            '1PM 13:00–15:00 · CCF DXB',
            '10AM House Church',
        ])
    })

    it('asks the page to pick the tapped class', async () => {
        const wrapper = mount(DoorClassPicker, {
            props: { today: 'Sat 31 Oct', classes: [onePm, sevenPm], classId: 1 },
        })

        await wrapper.findAll('[role="radio"]')[1]?.trigger('click')

        expect(wrapper.emitted('pick')).toEqual([[2]])
    })

    it('says what to do when no class meets today, under the church', () => {
        const wrapper = mount(DoorClassPicker, {
            props: { today: 'Mon 2 Nov', classes: [], classId: null },
        })

        expect(wrapper.find('[data-testid="door-heading"]').text()).toBe('CCF · Mon 2 Nov')
        expect(wrapper.find('[data-testid="no-class"]').text()).toBe(
            'No class meets today. Admins add classes on the Classes page.',
        )
    })
})
