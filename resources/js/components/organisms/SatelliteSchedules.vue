<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import ScheduleCard from '@/components/molecules/ScheduleCard.vue'
import ScheduleForm from '@/components/organisms/ScheduleForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { SatelliteSchedules } from '@/types'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * One satellite on the Classes page: its classes (GLOSSARY "Schedule") in the order of the week, one opened for
 * editing at a time in place, and "+ Add a class", which opens the add form until it is saved or cancelled — so a
 * phone is not one long form per satellite (Roy's look, 2026-10-26).
 */
defineProps<{ satellite: SatelliteSchedules }>()

const church = useChurch()
const editing = ref<number | null>(null)
const adding = ref(false)

function retire(scheduleId: number): void {
    router.put(
        route('church.schedules.retire', [church.value.slug, scheduleId]),
        {},
        { preserveScroll: true },
    )
}
</script>

<template>
    <section class="flex flex-col gap-3" :data-testid="`satellite-${satellite.id}`">
        <SectionHeading>{{ satellite.name }}</SectionHeading>
        <EmptyState v-if="satellite.schedules.length === 0"
            >No classes yet. Add the first one below.</EmptyState
        >
        <template v-for="schedule in satellite.schedules" :key="schedule.id">
            <div v-if="editing === schedule.id" data-testid="edit-schedule-form">
                <ScheduleForm
                    :action="route('church.schedules.update', [church.slug, schedule.id])"
                    :schedule="schedule"
                    submit-label="Save"
                    @done="editing = null"
                />
            </div>
            <ScheduleCard
                v-else
                :schedule="schedule"
                @edit="editing = schedule.id"
                @retire="retire"
            />
        </template>
        <div v-if="adding" class="flex flex-col gap-2" data-testid="add-schedule">
            <SectionHeading level="3">Add a class at {{ satellite.name }}</SectionHeading>
            <ScheduleForm
                :action="route('church.schedules.store', [church.slug])"
                :satellite-id="satellite.id"
                submit-label="Add class"
                @done="adding = false"
            />
        </div>
        <AppButton
            v-else
            variant="secondary"
            class="self-start"
            data-testid="open-add-schedule"
            @click="adding = true"
            >+ Add a class</AppButton
        >
    </section>
</template>
