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
    starts_at: '13:00',
    ends_at: '15:00',
}

describe('ScheduleForm', () => {
    beforeEach(() => {
        post.mockReset()
        put.mockReset()
    })

    it('offers "no set day" and every day from the page\'s weekday enum', () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/ccf/classes', satelliteId: 3, submitLabel: 'Add class' },
        })

        const labels = wrapper.find('[data-testid="schedule-weekday"]').findAll('option')
        expect(labels.map((o) => o.text())).toEqual([
            'No set day (shows every day)',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ])
    })

    it('adds a class at its satellite, with no day or times if none are given', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/ccf/classes', satelliteId: 3, submitLabel: 'Add class' },
        })

        await wrapper.find('[data-testid="schedule-name"]').setValue('Special class')
        await wrapper.find('form').trigger('submit')

        expect(form).toMatchObject({
            satellite_id: 3,
            name: 'Special class',
            weekday: '',
            starts_at: '',
            ends_at: '',
        })
        expect(post).toHaveBeenCalledWith('/ccf/classes', expect.anything())
        expect(put).not.toHaveBeenCalled()
    })

    it('edits a class from what is saved, and can be cancelled', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/ccf/classes/4', schedule: onePm, submitLabel: 'Save' },
        })

        expect(form).toMatchObject({ name: '1PM', weekday: 'saturday', starts_at: '13:00' })
        await wrapper.find('[data-testid="schedule-starts-at"]').setValue('13:30')
        await wrapper.find('form').trigger('submit')

        expect(form.starts_at).toBe('13:30')
        expect(put).toHaveBeenCalledWith('/ccf/classes/4', expect.anything())

        await wrapper.find('[data-testid="schedule-cancel"]').trigger('click')
        expect(wrapper.emitted('done')).toHaveLength(1)
    })

    it('adds a class and says it is done, so the page can close the form', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/ccf/classes', satelliteId: 3, submitLabel: 'Add class' },
        })

        await wrapper.find('form').trigger('submit')
        const options = post.mock.calls[0]?.[1] as { onSuccess: () => void }
        options.onSuccess()

        expect(wrapper.emitted('done')).toHaveLength(1)
    })

    it('can be cancelled while adding, too', async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/ccf/classes', satelliteId: 3, submitLabel: 'Add class' },
        })

        await wrapper.find('[data-testid="schedule-cancel"]').trigger('click')

        expect(wrapper.emitted('done')).toHaveLength(1)
    })

    it("shows the server's words beside the field they are about", async () => {
        const wrapper = mount(ScheduleForm, {
            props: { action: '/ccf/classes', satelliteId: 3, submitLabel: 'Add class' },
        })

        form.errors = { ends_at: 'The class must end after it starts.' }
        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('The class must end after it starts.')
    })
})
