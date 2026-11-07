import DateCalendar from '@/components/atoms/DateCalendar.vue'
import { CalendarDate, type DateValue } from '@internationalized/date'
import { flushPromises, mount } from '@vue/test-utils'
import { DatePickerRoot } from 'reka-ui'
import { afterEach, describe, expect, it } from 'vitest'
import { defineComponent, h, shallowRef } from 'vue'

afterEach(() => {
    document.body.innerHTML = ''
})

/** The calendar as DateField holds it: inside an open DatePickerRoot. */
function mountOpen(shown = shallowRef<DateValue>(new CalendarDate(2020, 11, 1))) {
    const Host = defineComponent(
        () => () =>
            h(
                DatePickerRoot,
                { defaultOpen: true, locale: 'en-GB', placeholder: shown.value },
                () =>
                    h(DateCalendar, {
                        shown: shown.value,
                        'onUpdate:shown': (value: DateValue) => (shown.value = value),
                        max: new CalendarDate(2026, 11, 7),
                        label: 'Birthday',
                        testid: 'dob',
                    }),
            ),
    )
    mount(Host, { attachTo: document.body })
    return shown
}

const $ = <T extends Element>(selector: string): T => {
    const el = document.body.querySelector<T>(selector)
    if (el === null) throw new Error(`no ${selector}`)
    return el
}

describe('DateCalendar', () => {
    it('jumps to the year and the month picked', async () => {
        const shown = mountOpen()
        await flushPromises()

        const year = $<HTMLSelectElement>('[data-testid="dob-year"]')
        year.value = '2018'
        year.dispatchEvent(new Event('change'))
        await flushPromises()
        const month = $<HTMLSelectElement>('[data-testid="dob-month"]')
        month.value = '3'
        month.dispatchEvent(new Event('change'))

        expect(shown.value.toString()).toBe('2018-03-01')
    })

    it('offers a hundred years, from the year of `max` back, and every month by name', async () => {
        mountOpen()
        await flushPromises()

        const years = [...$('[data-testid="dob-year"]').querySelectorAll('option')].map(
            (o) => o.textContent,
        )
        const months = [...$('[data-testid="dob-month"]').querySelectorAll('option')].map((o) =>
            o.textContent?.trim(),
        )
        expect([years.length, years[0], years.at(-1)]).toEqual([100, '2026', '1927'])
        expect([months.length, months[0], months[11]]).toEqual([12, 'January', 'December'])
    })
})
