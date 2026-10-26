<script setup lang="ts">
import type { Schedule } from '@/types'
import { computed, ref } from 'vue'

/**
 * One class (GLOSSARY "Schedule") on the Classes page: its name, day and times, with Edit and Retire. Retiring
 * asks once more, saying what it means, before it is sent.
 */
const props = defineProps<{ schedule: Schedule }>()

const emit = defineEmits<{ edit: []; retire: [scheduleId: number] }>()

const confirming = ref(false)

const when = computed(() => {
    const day = props.schedule.weekday_label ?? 'Any day'
    const { starts_at: starts, ends_at: ends } = props.schedule
    if (starts !== null && ends !== null) return `${day} · ${starts}–${ends}`
    if (starts !== null) return `${day} · from ${starts}`
    if (ends !== null) return `${day} · until ${ends}`
    return day
})

function retire(): void {
    confirming.value = false
    emit('retire', props.schedule.id)
}
</script>

<template>
    <article
        class="border-border bg-surface flex flex-col gap-2 rounded-lg border p-4"
        data-testid="schedule"
    >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <h3 class="text-text text-lg font-semibold">{{ schedule.name }}</h3>
            <p class="text-text-muted">{{ when }}</p>
        </div>
        <div v-if="!confirming" class="flex flex-wrap gap-3">
            <button
                type="button"
                class="min-h-touch border-primary text-primary rounded-md border px-4 font-semibold"
                data-testid="edit-schedule"
                @click="$emit('edit')"
            >
                Edit
            </button>
            <button
                type="button"
                class="min-h-touch text-danger px-2 underline"
                data-testid="retire-schedule"
                @click="confirming = true"
            >
                Retire
            </button>
        </div>
        <div v-else class="flex flex-col gap-2">
            <p class="text-text">
                Retire "{{ schedule.name }}"? It will no longer show at the door.
            </p>
            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    class="min-h-touch bg-danger text-on-primary rounded-md px-4 font-semibold"
                    data-testid="confirm-retire"
                    @click="retire"
                >
                    Yes, retire it
                </button>
                <button
                    type="button"
                    class="min-h-touch text-text px-2 underline"
                    data-testid="keep-schedule"
                    @click="confirming = false"
                >
                    Keep it
                </button>
            </div>
        </div>
    </article>
</template>
