import SatelliteSchedules from '@/components/organisms/SatelliteSchedules.vue'
import type { SatelliteSchedules as SatelliteSchedulesProp } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const routerPut = vi.fn()

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    const { enums } = await import('@/tests/enumPage')
    return {
        usePage: () => ({ props: { enums, church: { name: 'CCF', slug: 'ccf' } } }),
        useForm: (data: Record<string, unknown>) =>
            reactive({ ...data, errors: {}, processing: false, post: vi.fn(), put: vi.fn() }),
        router: { put: (...args: unknown[]) => routerPut(...args) },
    }
})

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `${name}:${JSON.stringify(params)}`,
}))

const dxb: SatelliteSchedulesProp = {
    id: 3,
    name: 'CCF DXB',
    schedules: [
        {
            id: 4,
            name: '1PM',
            weekday: 'saturday',
            weekday_label: 'Saturdays',
            starts_at: '13:00',
            ends_at: '15:00',
        },
        {
            id: 5,
            name: '7PM',
            weekday: 'saturday',
            weekday_label: 'Saturdays',
            starts_at: '19:00',
            ends_at: '21:00',
        },
    ],
}

describe('SatelliteSchedules', () => {
    beforeEach(() => routerPut.mockReset())

    it('names the satellite and lists its classes, the add form closed behind a button', () => {
        const wrapper = mount(SatelliteSchedules, { props: { satellite: dxb } })

        expect(wrapper.find('h2').text()).toBe('CCF DXB')
        expect(wrapper.findAll('[data-testid="schedule"]')).toHaveLength(2)
        expect(wrapper.find('[data-testid="open-add-schedule"]').text()).toBe('+ Add a class')
        expect(wrapper.find('[data-testid="add-schedule"]').exists()).toBe(false)
    })

    it('opens the add form on the button, and closes it again when the form is done', async () => {
        const wrapper = mount(SatelliteSchedules, { props: { satellite: dxb } })

        await wrapper.find('[data-testid="open-add-schedule"]').trigger('click')
        expect(wrapper.find('[data-testid="add-schedule"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="open-add-schedule"]').exists()).toBe(false)

        await wrapper.find('[data-testid="schedule-cancel"]').trigger('click')
        expect(wrapper.find('[data-testid="add-schedule"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="open-add-schedule"]').exists()).toBe(true)
    })

    it('says when a satellite has no classes yet', () => {
        const wrapper = mount(SatelliteSchedules, {
            props: { satellite: { ...dxb, schedules: [] } },
        })

        expect(wrapper.text()).toContain('No classes yet.')
    })

    it('opens one class at a time for editing, in place', async () => {
        const wrapper = mount(SatelliteSchedules, { props: { satellite: dxb } })

        await wrapper.findAll('[data-testid="edit-schedule"]')[1]!.trigger('click')

        expect(wrapper.findAll('[data-testid="schedule"]')).toHaveLength(1)
        expect(wrapper.find('[data-testid="edit-schedule-form"]').exists()).toBe(true)

        await wrapper.find('[data-testid="schedule-cancel"]').trigger('click')
        expect(wrapper.findAll('[data-testid="schedule"]')).toHaveLength(2)
    })

    it('retires a class through its retire address', async () => {
        const wrapper = mount(SatelliteSchedules, { props: { satellite: dxb } })

        await wrapper.findAll('[data-testid="retire-schedule"]')[0]!.trigger('click')
        await wrapper.find('[data-testid="confirm-retire"]').trigger('click')

        expect(routerPut).toHaveBeenCalledWith(
            'church.schedules.retire:["ccf",4]',
            {},
            expect.anything(),
        )
    })
})
