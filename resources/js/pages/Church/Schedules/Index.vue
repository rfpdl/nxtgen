<script setup lang="ts">
import PageHeader from '@/components/atoms/PageHeader.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import SatelliteSchedules from '@/components/organisms/SatelliteSchedules.vue'
import type { SchedulesPage } from '@/types'
import { Head } from '@inertiajs/vue3'

/**
 * Classes (GLOSSARY "Schedule", DECISIONS 2026-10-26): each satellite this admin looks after, with its classes.
 * A weekly class shows at the door on its day every week; a one-time class on its date only (spec item 14).
 */
defineProps<SchedulesPage>()
</script>

<template>
    <Head title="Classes" />
    <ChurchShell>
        <PageHeader
            title="Classes"
            sub="A weekly class shows at the door on its day every week. A one-time class shows on its date only."
        />
        <SatelliteSchedules
            v-for="satellite in satellites"
            :key="satellite.id"
            :satellite="satellite"
        />
    </ChurchShell>
</template>
