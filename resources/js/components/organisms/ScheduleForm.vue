<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import { useEnum } from '@/composables/useEnum'
import type { Draft, Schedule, ScheduleInput } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

/**
 * Add or edit a class (GLOSSARY "Schedule"): a name, and a day and start/end time if it has them. Given a
 * `satelliteId` it ADDS a class there (POST); given a saved `schedule` it EDITS that one (PUT, satellite never
 * changes). The days come from the page's `enums.weekday`; the server checks the times (HH:MM, end after start).
 * `done` says it was saved or cancelled, so the page can close it.
 */
const props = defineProps<{
    action: string
    submitLabel: string
    satelliteId?: number
    schedule?: Schedule
}>()

const emit = defineEmits<{ done: [] }>()

const { options: weekdays } = useEnum('weekday')

const form = useForm<Draft<ScheduleInput> & { satellite_id?: number }>({
    ...(props.satelliteId !== undefined ? { satellite_id: props.satelliteId } : {}),
    name: props.schedule?.name ?? '',
    weekday: props.schedule?.weekday ?? '',
    starts_at: props.schedule?.starts_at ?? '',
    ends_at: props.schedule?.ends_at ?? '',
})

function submit(): void {
    if (props.schedule === undefined) {
        form.post(props.action, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                emit('done')
            },
        })
        return
    }
    form.put(props.action, { preserveScroll: true, onSuccess: () => emit('done') })
}

const weekdayOptions = computed(() =>
    weekdays.value.map((d) => ({ value: d.value, label: d.label })),
)

function setWeekday(value: string | number | null): void {
    form.weekday = (value ?? '') as typeof form.weekday
}
</script>

<template>
    <Card>
        <form
            class="flex flex-col gap-4"
            novalidate
            data-testid="schedule-form"
            @submit.prevent="submit"
        >
            <TextField
                v-model="form.name"
                label="Name"
                hint="What people call it, e.g. 1PM"
                autocomplete="off"
                :error="form.errors.name"
                data-testid="schedule-name"
            />
            <SelectField
                label="Day"
                placeholder="No set day (shows every day)"
                :options="weekdayOptions"
                :model-value="form.weekday || null"
                :error="form.errors.weekday"
                data-testid="schedule-weekday"
                @update:model-value="setWeekday"
            />
            <div class="grid grid-cols-2 gap-4">
                <TextField
                    v-model="form.starts_at"
                    type="time"
                    label="Starts"
                    :error="form.errors.starts_at"
                    data-testid="schedule-starts-at"
                />
                <TextField
                    v-model="form.ends_at"
                    type="time"
                    label="Ends"
                    :error="form.errors.ends_at"
                    data-testid="schedule-ends-at"
                />
            </div>
            <div class="flex flex-wrap gap-3">
                <AppButton type="submit" :loading="form.processing" data-testid="schedule-submit">
                    {{ submitLabel }}
                </AppButton>
                <AppButton variant="secondary" data-testid="schedule-cancel" @click="emit('done')">
                    Cancel
                </AppButton>
            </div>
        </form>
    </Card>
</template>
