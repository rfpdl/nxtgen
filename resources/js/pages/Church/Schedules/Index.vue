<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import SatelliteSchedules from '@/components/organisms/SatelliteSchedules.vue'
import type { SatelliteSchedules as SatelliteSchedulesProp } from '@/types'
import { Head } from '@inertiajs/vue3'

/**
 * Classes (GLOSSARY "Schedule", DECISIONS 2026-10-26): each satellite this admin looks after, with its classes.
 * A class with a day shows at the door only on that day; one with no day shows every day.
 */
defineProps<{ satellites: SatelliteSchedulesProp[] }>()
</script>

<template>
    <Head title="Classes" />
    <ChurchShell>
        <h1 class="text-text text-2xl font-semibold">Classes</h1>
        <p class="text-text-muted">
            A class with a day shows at the door only on that day. A class with no set day shows
            every day.
        </p>
        <SatelliteSchedules
            v-for="satellite in satellites"
            :key="satellite.id"
            :satellite="satellite"
        />
    </ChurchShell>
</template>
