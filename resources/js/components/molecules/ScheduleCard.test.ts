import ScheduleCard from '@/components/molecules/ScheduleCard.vue'
import type { Schedule } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const onePm: Schedule = {
    id: 4,
    name: '1PM',
    weekday: 'saturday',
    weekday_label: 'Saturdays',
    date: null,
    date_label: null,
    starts_at: '13:00',
    ends_at: '15:00',
}

const party: Schedule = {
    id: 5,
    name: 'Christmas party',
    weekday: null,
    weekday_label: null,
    date: '2026-12-24',
    date_label: 'Thu 24 Dec 2026',
    starts_at: '18:00',
    ends_at: '20:00',
}

describe('ScheduleCard', () => {
    it('shows the class, its day and its times', () => {
        const wrapper = mount(ScheduleCard, { props: { schedule: onePm } })

        expect(wrapper.text()).toContain('1PM')
        expect(wrapper.text()).toContain('Saturdays · 13:00–15:00')
    })

    it('shows a one-time class on its date', () => {
        expect(mount(ScheduleCard, { props: { schedule: party } }).text()).toContain(
            'Thu 24 Dec 2026 · 18:00–20:00',
        )
    })

    it('shows a past class without Edit, but it can be retired (its name is free again)', () => {
        const wrapper = mount(ScheduleCard, { props: { schedule: party, past: true } })

        expect(wrapper.text()).toContain('Thu 24 Dec 2026 · 18:00–20:00')
        expect(wrapper.find('[data-testid="edit-schedule"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="retire-schedule"]').exists()).toBe(true)
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
