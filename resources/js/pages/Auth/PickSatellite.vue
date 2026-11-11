<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import AppShell from '@/components/layout/AppShell.vue'
import type { PickSatellitePage } from '@/types'
import { Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * "Pick your satellite" (spec item 11): someone who serves at several satellites and used none yet picks one; each is
 * one big button to its home there (Admin for an admin, else Serve). The bar remembers the last one after that.
 */
defineProps<PickSatellitePage>()
</script>

<template>
    <Head title="Pick your satellite" />
    <AppShell title="Pick your satellite" narrow>
        <PageHeader
            title="Pick your satellite"
            sub="You serve at more than one. Pick where you are today."
        />
        <nav class="flex flex-col gap-3" aria-label="Satellites">
            <AppButton
                v-for="satellite in satellites"
                :key="satellite.slug"
                :href="route('satellite.home', satellite.slug)"
                variant="secondary"
                size="lg"
                block
                data-testid="pick-satellite"
            >
                {{ satellite.name }}
            </AppButton>
        </nav>
        <SignOutButton class="self-start" />
    </AppShell>
</template>
