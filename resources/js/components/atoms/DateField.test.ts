import DateField from '@/components/atoms/DateField.vue'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

/** "Today" is pinned so "no future date" is the same every run. */
beforeEach(() => {
    vi.useFakeTimers({ now: new Date('2026-11-07T10:00:00'), toFake: ['Date'] })
})
afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
})

async function open(wrapper: ReturnType<typeof mount>): Promise<HTMLElement> {
    await wrapper.find('[data-testid="dob-open"]').trigger('click')
    await flushPromises()
    const calendar = document.body.querySelector<HTMLElement>('[data-testid="dob-calendar"]')
    if (calendar === null) throw new Error('the calendar did not open')
    return calendar
}

async function choose(select: HTMLSelectElement | null, value: string): Promise<void> {
    if (select === null) throw new Error('no select')
    select.value = value
    select.dispatchEvent(new Event('change'))
    await flushPromises()
}

describe('DateField', () => {
    it('picks a birthday years back in three taps — the year, the month, the day — as YYYY-MM-DD', async () => {
        const wrapper = mount(DateField, {
            props: { label: 'Birthday', 'data-testid': 'dob' },
            attachTo: document.body,
        })
        const calendar = await open(wrapper)

        await choose(calendar.querySelector('[data-testid="dob-year"]'), '2018')
        await choose(calendar.querySelector('[data-testid="dob-month"]'), '3')
        calendar.querySelector<HTMLElement>('[data-value="2018-03-14"]')?.click()
        await flushPromises()

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['2018-03-14'])
        expect(document.body.querySelector('[data-testid="dob-calendar"]')).toBeNull()
    })

    it("offers no day after today, and opens on a child's age back", async () => {
        const wrapper = mount(DateField, {
            props: { label: 'Birthday', 'data-testid': 'dob' },
            attachTo: document.body,
        })
        const calendar = await open(wrapper)
        expect(calendar.querySelector<HTMLSelectElement>('[data-testid="dob-year"]')?.value).toBe(
            '2020',
        )
        await choose(calendar.querySelector('[data-testid="dob-year"]'), '2026')
        await choose(calendar.querySelector('[data-testid="dob-month"]'), '11')

        expect(
            calendar.querySelector('[data-value="2026-11-08"]')?.hasAttribute('data-disabled'),
        ).toBe(true)
        expect(
            calendar.querySelector('[data-value="2026-11-07"]')?.hasAttribute('data-disabled'),
        ).toBe(false)
        const years = [
            ...(calendar.querySelector('[data-testid="dob-year"]')?.querySelectorAll('option') ??
                []),
        ]
        expect(years[0]?.textContent).toBe('2026')
    })

    it('shows a saved birthday as day, month and year', () => {
        const wrapper = mount(DateField, {
            props: { label: 'Birthday', modelValue: '2019-05-10', 'data-testid': 'dob' },
        })

        expect(wrapper.find('[data-testid="dob"]').text().replace(/\s/g, '')).toContain(
            '10/05/2019',
        )
    })

    it('labels the field and says its error', () => {
        const wrapper = mount(DateField, {
            props: {
                label: 'Birthday',
                required: true,
                error: 'Please enter a birthday.',
                'data-testid': 'dob',
            },
        })

        expect(wrapper.text()).toContain('Birthday')
        expect(wrapper.find('[role="alert"]').text()).toBe('Please enter a birthday.')
    })
})
