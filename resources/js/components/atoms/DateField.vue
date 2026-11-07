<script setup lang="ts">
import DateCalendar from '@/components/atoms/DateCalendar.vue'
import {
    CalendarDate,
    getLocalTimeZone,
    parseDate,
    today,
    type DateValue,
} from '@internationalized/date'
import { CalendarDays } from 'lucide-vue-next'
import { DatePickerField, DatePickerInput, DatePickerRoot, DatePickerTrigger } from 'reka-ui'
import { computed, shallowRef, useAttrs, useId } from 'vue'

/**
 * A labelled date (the Bold look, Roy 2026-11-07): day / month / year to type, and a calendar button (DateCalendar).
 * No day after today. The model is the date as the server takes it, `YYYY-MM-DD`, or '' for none. Same frame as
 * TextField; `data-testid` names the field, and its parts are `<testid>-day|month|year`, `-open`, `-calendar`.
 */
defineOptions({ inheritAttrs: false })

const props = withDefaults(defineProps<{ label: string; error?: string; required?: boolean }>(), {
    error: undefined,
})
const model = defineModel<string>({ default: '' })

const id = useId()
const attrs = useAttrs()
const testid = computed(() => String(attrs['data-testid'] ?? 'date'))

const max = today(getLocalTimeZone())
const date = computed<DateValue | undefined>({
    get: () => (/^\d{4}-\d{2}-\d{2}$/.test(model.value) ? parseDate(model.value) : undefined),
    set: (value) => (model.value = value === undefined ? '' : value.toString()),
})
/** The month the calendar shows: the date's, else a child's age back from today. */
const shown = shallowRef<DateValue>(date.value ?? new CalendarDate(max.year - 6, max.month, 1))
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1.5" :class="attrs.class">
        <span :id="`${id}-label`" class="text-text text-lg font-semibold"
            >{{ label }}<span v-if="required" class="text-danger" aria-hidden="true"> *</span></span
        >
        <DatePickerRoot
            v-model="date"
            v-model:placeholder="shown"
            locale="en-GB"
            granularity="day"
            :max-value="max"
            :required="required"
            prevent-deselect
            close-on-select
        >
            <DatePickerField
                v-slot="{ segments }"
                :data-testid="testid"
                :aria-labelledby="`${id}-label`"
                :aria-describedby="props.error ? `${id}-error` : undefined"
                :aria-invalid="props.error ? 'true' : undefined"
                class="min-h-touch-door rounded-control bg-surface text-text focus-within:border-primary flex w-full items-center border-2 border-b-4 pr-2 pl-4 text-lg"
                :class="props.error ? 'border-danger' : 'border-border-strong'"
            >
                <template v-for="item in segments" :key="item.part">
                    <DatePickerInput
                        :part="item.part"
                        :data-testid="
                            item.part === 'literal' ? undefined : `${testid}-${item.part}`
                        "
                        class="data-placeholder:text-text-muted focus:bg-primary-soft rounded px-0.5 outline-none"
                        >{{ item.value }}</DatePickerInput
                    >
                </template>
                <DatePickerTrigger
                    :data-testid="`${testid}-open`"
                    aria-label="Open the calendar"
                    class="text-primary hover:bg-primary-soft min-h-touch min-w-touch ml-auto flex items-center justify-center rounded-full"
                >
                    <CalendarDays class="size-6" aria-hidden="true" />
                </DatePickerTrigger>
            </DatePickerField>
            <DateCalendar v-model:shown="shown" :max="max" :label="label" :testid="testid" />
        </DatePickerRoot>
        <p
            v-if="error"
            :id="`${id}-error`"
            role="alert"
            class="text-danger text-base font-semibold"
        >
            {{ error }}
        </p>
    </div>
</template>
