<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The registration poster for the foyer (plan §6 R1): print it. Plain big words for parents, the QR code to scan,
 * and the address for anyone whose camera will not. The buttons are hidden on paper.
 */
defineProps<{ church: { name: string; slug: string }; registerUrl: string; qr: string }>()

function print(): void {
    window.print()
}
</script>

<template>
    <Head title="Registration poster" />
    <main
        class="bg-surface mx-auto flex min-h-dvh max-w-3xl flex-col items-center justify-center gap-6 p-8 text-center"
    >
        <div class="flex gap-4 print:hidden">
            <button
                type="button"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                data-testid="print-poster"
                @click="print"
            >
                Print
            </button>
            <Link
                :href="route('org.families.index', church.slug)"
                class="min-h-touch text-text flex items-center underline"
                >Back</Link
            >
        </div>
        <h1 class="text-text text-5xl font-bold">Register your kids for NxtGen</h1>
        <p class="text-text text-2xl">{{ church.name }} kids church — it takes about 5 minutes.</p>
        <img
            :src="qr"
            alt="QR code for the registration form"
            class="size-96"
            data-testid="poster-qr"
        />
        <p class="text-text text-2xl font-semibold">Scan with your phone camera</p>
        <p class="text-text-muted text-lg break-all" data-testid="poster-url">{{ registerUrl }}</p>
    </main>
</template>
