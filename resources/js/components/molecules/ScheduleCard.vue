<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import type { Schedule } from '@/types'
import { computed, ref } from 'vue'

/**
 * One class (GLOSSARY "Schedule") on the Classes page: its name, its day (weekly) or date (one-time) and times, with
 * Edit and Retire. Retiring asks once more, saying what it means, before it is sent. A `past` class — a one-time class
 * whose date has gone (Roy 2026-11-10) — has no Edit; retiring it frees its name for next year's.
 */
const props = defineProps<{ schedule: Schedule; past?: boolean }>()

const emit = defineEmits<{ edit: []; retire: [scheduleId: number] }>()

const confirming = ref(false)

const when = computed(() => {
    const {
        weekday_label: day,
        date_label: date,
        starts_at: starts,
        ends_at: ends,
    } = props.schedule
    return `${day ?? date ?? ''} · ${starts}–${ends}`
})

function retire(): void {
    confirming.value = false
    emit('retire', props.schedule.id)
}
</script>

<template>
    <Card
        as="article"
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
        data-testid="schedule"
    >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <h3 class="text-text text-xl font-bold">{{ schedule.name }}</h3>
            <p class="text-text-muted text-lg">{{ when }}</p>
        </div>
        <div v-if="!confirming" class="flex flex-wrap items-center gap-3">
            <AppButton
                v-if="!past"
                variant="secondary"
                data-testid="edit-schedule"
                @click="$emit('edit')"
            >
                Edit
            </AppButton>
            <AppButton variant="link" data-testid="retire-schedule" @click="confirming = true">
                Retire
            </AppButton>
        </div>
        <div v-else class="bg-warning-soft rounded-control flex w-full flex-col gap-3 p-3">
            <p class="text-text font-semibold">
                Retire "{{ schedule.name }}"? It will no longer show at the door.
            </p>
            <div class="flex flex-wrap gap-3">
                <AppButton variant="danger" data-testid="confirm-retire" @click="retire">
                    Yes, retire it
                </AppButton>
                <AppButton
                    variant="secondary"
                    data-testid="keep-schedule"
                    @click="confirming = false"
                >
                    Keep it
                </AppButton>
            </div>
        </div>
    </Card>
</template>
