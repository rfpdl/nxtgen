<script setup lang="ts">
import type { DateValue } from '@internationalized/date'
import {
    DatePickerCalendar,
    DatePickerCell,
    DatePickerCellTrigger,
    DatePickerContent,
    DatePickerGrid,
    DatePickerGridBody,
    DatePickerGridHead,
    DatePickerGridRow,
    DatePickerHeadCell,
} from 'reka-ui'

/**
 * DateField's calendar, inside its DatePickerRoot: a month and a year select that jump the month `shown`, so a
 * birthday years back is three taps (year, month, day), then the month's days — none after `max`.
 */
const props = defineProps<{ max: DateValue; label: string; testid: string }>()
const shown = defineModel<DateValue>('shown', { required: true })

const MONTHS = Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: new Date(2000, i, 1).toLocaleString('en-GB', { month: 'long' }),
}))
const YEARS = Array.from({ length: 100 }, (_, i) => props.max.year - i)

function jump(part: 'year' | 'month', event: Event): void {
    shown.value = shown.value.set({ [part]: Number((event.target as HTMLSelectElement).value) })
}

const select = 'rounded-control border-border-strong bg-surface text-text border-2 p-2 text-lg'
</script>

<template>
    <DatePickerContent
        :side-offset="8"
        :collision-padding="16"
        align="start"
        class="rounded-card bg-surface border-border-strong z-50 border-2 p-3 shadow-lg"
    >
        <DatePickerCalendar
            v-slot="{ weekDays, grid }"
            :data-testid="`${testid}-calendar`"
            :calendar-label="label"
            class="flex flex-col gap-3"
        >
            <div class="flex gap-2">
                <select
                    :value="shown.month"
                    aria-label="Month"
                    :data-testid="`${testid}-month`"
                    :class="select"
                    class="flex-1"
                    @change="jump('month', $event)"
                >
                    <option v-for="m in MONTHS" :key="m.value" :value="m.value">
                        {{ m.label }}
                    </option>
                </select>
                <select
                    :value="shown.year"
                    aria-label="Year"
                    :data-testid="`${testid}-year`"
                    :class="select"
                    @change="jump('year', $event)"
                >
                    <option v-for="y in YEARS" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <DatePickerGrid v-for="month in grid" :key="month.value.toString()">
                <DatePickerGridHead>
                    <DatePickerGridRow class="grid grid-cols-7">
                        <DatePickerHeadCell
                            v-for="day in weekDays"
                            :key="day"
                            class="text-text-muted text-sm font-semibold"
                            >{{ day }}</DatePickerHeadCell
                        >
                    </DatePickerGridRow>
                </DatePickerGridHead>
                <DatePickerGridBody>
                    <DatePickerGridRow
                        v-for="(week, index) in month.rows"
                        :key="index"
                        class="grid grid-cols-7"
                    >
                        <DatePickerCell v-for="day in week" :key="day.toString()" :date="day">
                            <DatePickerCellTrigger
                                :day="day"
                                :month="month.value"
                                class="text-text hover:bg-primary-soft data-selected:bg-primary data-selected:text-on-primary data-outside-view:text-text-muted data-disabled:text-text-muted flex size-11 items-center justify-center rounded-full text-lg data-disabled:line-through"
                            />
                        </DatePickerCell>
                    </DatePickerGridRow>
                </DatePickerGridBody>
            </DatePickerGrid>
        </DatePickerCalendar>
    </DatePickerContent>
</template>
