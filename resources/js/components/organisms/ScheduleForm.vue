<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import { useEnum } from '@/composables/useEnum'
import type { Draft, Schedule, ScheduleInput } from '@/types'
import { useForm } from '@inertiajs/vue3'

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

const input = 'min-h-touch border-input rounded-md border px-3'
</script>

<template>
    <form
        class="border-border bg-surface flex flex-col gap-4 rounded-lg border p-4"
        novalidate
        data-testid="schedule-form"
        @submit.prevent="submit"
    >
        <FormField label="Name" hint="What people call it, e.g. 1PM" :error="form.errors.name">
            <input
                v-model="form.name"
                type="text"
                autocomplete="off"
                :class="input"
                data-testid="schedule-name"
            />
        </FormField>
        <FormField label="Day" :error="form.errors.weekday">
            <select v-model="form.weekday" :class="input" data-testid="schedule-weekday">
                <option value="">No set day (shows every day)</option>
                <option v-for="day in weekdays" :key="day.value" :value="day.value">
                    {{ day.label }}
                </option>
            </select>
        </FormField>
        <div class="flex flex-wrap gap-4">
            <FormField label="Starts" :error="form.errors.starts_at">
                <input
                    v-model="form.starts_at"
                    type="time"
                    :class="input"
                    data-testid="schedule-starts-at"
                />
            </FormField>
            <FormField label="Ends" :error="form.errors.ends_at">
                <input
                    v-model="form.ends_at"
                    type="time"
                    :class="input"
                    data-testid="schedule-ends-at"
                />
            </FormField>
        </div>
        <div class="flex flex-wrap gap-3">
            <button
                type="submit"
                :disabled="form.processing"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                data-testid="schedule-submit"
            >
                {{ submitLabel }}
            </button>
            <button
                type="button"
                class="min-h-touch text-text px-2 underline"
                data-testid="schedule-cancel"
                @click="emit('done')"
            >
                Cancel
            </button>
        </div>
    </form>
</template>
