<script setup lang="ts">
import PageHeader from '@/components/atoms/PageHeader.vue'
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
        <PageHeader
            title="Classes"
            sub="A class with a day shows at the door only on that day. A class with no set day shows every day."
        />
        <SatelliteSchedules
            v-for="satellite in satellites"
            :key="satellite.id"
            :satellite="satellite"
        />
    </ChurchShell>
</template>
