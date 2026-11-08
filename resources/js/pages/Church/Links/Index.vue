<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import AppButton from '@/components/atoms/AppButton.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import SatelliteLinks from '@/components/organisms/SatelliteLinks.vue'
import type { SatelliteLinks as SatelliteLinksProp } from '@/types'
import { Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * Links (GLOSSARY; Roy 2026-11-06): each satellite this admin looks after, with the Links its QR poster shows —
 * its Facebook group, its WhatsApp group, anything parents should scan. The poster itself is in Serve.
 */
defineProps<{ satellites: SatelliteLinksProp[]; max: number }>()

const here = useSatellite()
</script>

<template>
    <Head title="Links" />
    <ChurchShell>
        <PageHeader
            title="Links"
            sub="Each satellite's QR poster shows the registration form, then these links — each as a QR code parents can scan."
        >
            <template #actions>
                <AppButton
                    :href="route('serve.poster', here.slug)"
                    variant="secondary"
                    data-testid="open-poster"
                    >See the poster</AppButton
                >
            </template>
        </PageHeader>
        <SatelliteLinks
            v-for="satellite in satellites"
            :key="satellite.id"
            :satellite="satellite"
            :max="max"
        />
    </ChurchShell>
</template>
