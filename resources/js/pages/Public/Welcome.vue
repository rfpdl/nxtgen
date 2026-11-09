<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import BrandMark from '@/components/atoms/BrandMark.vue'
import Card from '@/components/atoms/Card.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The front door for a visitor (Roy 2026-11-08): empty for now — the logo and a hello, and Sign in on the right of the
 * bar, in sight on a phone too. A
 * family registers at its own satellite's address, from that satellite's QR code or link, so there is no form here.
 */
</script>

<template>
    <Head title="Welcome" />
    <AppShell narrow>
        <template #end>
            <AppButton :href="route('login')" variant="quiet" data-testid="welcome-sign-in"
                >Sign in</AppButton
            >
        </template>
        <Card as="section" class="flex flex-col items-center gap-5 py-8 text-center">
            <BrandMark large />
            <h1 class="text-text text-3xl font-bold">Welcome to CCF NxtGen</h1>
        </Card>
    </AppShell>
</template>
