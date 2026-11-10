import ScheduleForm from '@/components/organisms/ScheduleForm.vue'
import type { Schedule } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()
const put = vi.fn()
let form: Record<string, unknown>

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    const { enums } = await import('@/tests/enumPage')
    return {
        usePage: () => ({ props: { enums } }),
        useForm: (data: Record<string, unknown>) => {
            form = reactive({ ...data, errors: {}, processing: false, post, put, reset: vi.fn() })
            return form
        },
    }
})

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
    ...onePm,
    id: 5,
    name: 'Christmas party',
    weekday: null,
    weekday_label: null,
    date: '2026-12-24',
    date_label: 'Thu 24 Dec 2026',
}

describe('ScheduleForm', () => {
    beforeEach(() => {
        post.mockReset()
        put.mockReset()
    })

    it("starts weekly, offering every day from the page's weekday enum, and no date", () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/dubai/admin/classes', submitLabel: 'Add class' },
        })

        expect(wrapper.find('[data-testid="schedule-date"]').exists()).toBe(false)
        const labels = wrapper.find('[data-testid="schedule-weekday"]').findAll('option')
        expect(labels.map((o) => o.text())).toEqual([
            'Choose the day',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ])
    })

    it('adds a one-time class on a date — at the satellite in the address, never one it names', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/dubai/admin/classes', submitLabel: 'Add class' },
        })

        await wrapper.find('[data-testid="schedule-name"]').setValue('Christmas party')
        await wrapper.find('[data-testid="schedule-one-time"]').trigger('click')
        expect(wrapper.find('[data-testid="schedule-weekday"]').exists()).toBe(false)
        await wrapper.find('[data-testid="schedule-date"]').setValue('2026-12-24')
        await wrapper.find('[data-testid="schedule-starts-at"]').setValue('18:00')
        await wrapper.find('[data-testid="schedule-ends-at"]').setValue('20:00')
        await wrapper.find('form').trigger('submit')

        expect(form).not.toHaveProperty('satellite_id')
        expect(form).toMatchObject({
            name: 'Christmas party',
            one_time: true,
            date: '2026-12-24',
            starts_at: '18:00',
            ends_at: '20:00',
        })
        expect(post).toHaveBeenCalledWith('/dubai/admin/classes', expect.anything())
        expect(put).not.toHaveBeenCalled()
    })

    it('edits a class from what is saved, and can be cancelled', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/dubai/admin/classes/4', schedule: onePm, submitLabel: 'Save' },
        })

        expect(form).toMatchObject({
            name: '1PM',
            one_time: false,
            weekday: 'saturday',
            starts_at: '13:00',
        })
        await wrapper.find('[data-testid="schedule-starts-at"]').setValue('13:30')
        await wrapper.find('form').trigger('submit')

        expect(form.starts_at).toBe('13:30')
        expect(put).toHaveBeenCalledWith('/dubai/admin/classes/4', expect.anything())

        await wrapper.find('[data-testid="schedule-cancel"]').trigger('click')
        expect(wrapper.emitted('done')).toHaveLength(1)
    })

    it('edits a one-time class from its date', () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/dubai/admin/classes/5', schedule: party, submitLabel: 'Save' },
        })

        expect(form).toMatchObject({ one_time: true, date: '2026-12-24', weekday: '' })
        expect(wrapper.find('[data-testid="schedule-date"]').exists()).toBe(true)
    })

    it('adds a class and says it is done, so the page can close the form', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/dubai/admin/classes', submitLabel: 'Add class' },
        })

        await wrapper.find('form').trigger('submit')
        const options = post.mock.calls[0]?.[1] as { onSuccess: () => void }
        options.onSuccess()

        expect(wrapper.emitted('done')).toHaveLength(1)
    })

    it('can be cancelled while adding, too', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/dubai/admin/classes', submitLabel: 'Add class' },
        })

        await wrapper.find('[data-testid="schedule-cancel"]').trigger('click')

        expect(wrapper.emitted('done')).toHaveLength(1)
    })

    it("shows the server's words beside the field they are about", async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/dubai/admin/classes', submitLabel: 'Add class' },
        })

        form.errors = { ends_at: 'The class must end after it starts.' }
        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('The class must end after it starts.')
    })
})
