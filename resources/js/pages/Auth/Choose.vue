<script setup lang="ts">
import type { LandingOption } from '@/types'
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps<{ options: LandingOption[] }>()
</script>

<template>
    <Head title="Where to?" />
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-4 p-6">
        <h1 class="text-text text-3xl font-semibold">Where would you like to go?</h1>
        <Link
            v-for="option in options"
            :key="option.url"
            :href="option.url"
            data-testid="landing-option"
            class="min-h-touch border-border bg-surface flex flex-col justify-center rounded-md border px-4 py-3"
        >
            <span class="text-text text-lg font-semibold">{{ option.label }}</span>
            <span class="text-text-muted">{{ option.churchName }}</span>
        </Link>
        <SignOutButton class="self-start" />
    </main>
</template>
