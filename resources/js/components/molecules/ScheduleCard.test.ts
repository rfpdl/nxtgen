import ScheduleCard from '@/components/molecules/ScheduleCard.vue'
import type { Schedule } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const onePm: Schedule = {
    id: 4,
    name: '1PM',
    weekday: 'saturday',
    weekday_label: 'Saturdays',
    starts_at: '13:00',
    ends_at: '15:00',
}

const special: Schedule = {
    id: 5,
    name: 'Special class',
    weekday: null,
    weekday_label: null,
    starts_at: null,
    ends_at: null,
}

describe('ScheduleCard', () => {
    it('shows the class, its day and its times', () => {
        const wrapper = mount(ScheduleCard, { props: { schedule: onePm } })

        expect(wrapper.text()).toContain('1PM')
        expect(wrapper.text()).toContain('Saturdays · 13:00–15:00')
    })

    it('says a class with no day meets any day, and shows a start with no end', () => {
        expect(mount(ScheduleCard, { props: { schedule: special } }).text()).toContain('Any day')

        const startOnly = mount(ScheduleCard, {
            props: { schedule: { ...onePm, ends_at: null } },
        })
        expect(startOnly.text()).toContain('Saturdays · from 13:00')
    })

    it('asks to edit', async () => {
        const wrapper = mount(ScheduleCard, { props: { schedule: onePm } })

        await wrapper.find('[data-testid="edit-schedule"]').trigger('click')

        expect(wrapper.emitted('edit')).toHaveLength(1)
    })

    it('retires only on the second tap, after saying what retiring means', async () => {
        const wrapper = mount(ScheduleCard, { props: { schedule: onePm } })

        await wrapper.find('[data-testid="retire-schedule"]').trigger('click')
        expect(wrapper.emitted('retire')).toBeUndefined()
        expect(wrapper.text()).toContain('It will no longer show at the door.')

        await wrapper.find('[data-testid="confirm-retire"]').trigger('click')
        expect(wrapper.emitted('retire')).toEqual([[4]])
    })

    it('keeps the class when the second tap is "Keep it"', async () => {
        const wrapper = mount(ScheduleCard, { props: { schedule: onePm } })

        await wrapper.find('[data-testid="retire-schedule"]').trigger('click')
        await wrapper.find('[data-testid="keep-schedule"]').trigger('click')

        expect(wrapper.emitted('retire')).toBeUndefined()
        expect(wrapper.find('[data-testid="confirm-retire"]').exists()).toBe(false)
    })
})
