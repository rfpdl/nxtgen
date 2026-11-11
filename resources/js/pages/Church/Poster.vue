<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import { useSatellite } from '@/composables/useSatellite'
import type { PosterPage } from '@/types'
import { Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The QR poster for the foyer (plan §6 R1; Roy 2026-11-06): print it. Plain big words for parents, the registration
 * QR code to scan and its address for anyone whose camera will not — then the satellite's own Links (its Facebook
 * group, its WhatsApp group…), each a QR code with its label. It is the poster of the satellite in the address
 * (DECISIONS 2026-11-08); the buttons are hidden on paper. On paper everything is smaller, so six Links still print on
 * one A4 or Letter page (Astra; e2e prints it to PDF and counts the pages).
 */
defineProps<PosterPage>()

const satellite = useSatellite()

function print(): void {
    window.print()
}
</script>

<template>
    <Head title="QR poster" />
    <main
        class="bg-surface mx-auto flex min-h-dvh max-w-3xl flex-col items-center justify-center gap-6 p-8 text-center print:min-h-0 print:gap-2 print:p-0"
    >
        <div class="flex flex-wrap items-end justify-center gap-4 print:hidden">
            <AppButton size="lg" data-testid="print-poster" @click="print">Print</AppButton>
            <AppButton
                :href="route('serve.families.index', satellite.slug)"
                variant="secondary"
                size="lg"
                >Back</AppButton
            >
        </div>
        <h1 class="text-text text-5xl font-bold print:text-3xl">Register your kids for NxtGen</h1>
        <p class="text-text text-2xl print:text-xl">
            {{ satellite.name }} kids church — it takes about 5 minutes.
        </p>
        <img
            :src="qr"
            alt="QR code for the registration form"
            class="aspect-square w-full max-w-96 print:max-w-48"
            :data-qr-url="qr_url"
            data-testid="poster-qr"
        />
        <p class="text-text text-2xl font-semibold print:text-xl">Scan with your phone camera</p>
        <p class="text-text-muted text-lg break-all print:text-base" data-testid="poster-url">
            {{ register_url }}
        </p>
        <section
            v-if="links.length > 0"
            class="grid w-full grid-cols-2 gap-6 sm:grid-cols-3 print:grid-cols-3 print:gap-4"
            aria-label="Our links"
        >
            <figure
                v-for="link in links"
                :key="link.url"
                class="flex flex-col items-center gap-2"
                data-testid="poster-link"
            >
                <img
                    :src="link.qr"
                    :alt="`QR code for ${link.label}`"
                    class="aspect-square w-full"
                />
                <figcaption class="text-text text-lg font-semibold">{{ link.label }}</figcaption>
            </figure>
        </section>
    </main>
</template>
