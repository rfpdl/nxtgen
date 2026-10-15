<script setup lang="ts">
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Nothing here yet" />
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-4 p-6">
        <h1 class="text-text text-3xl font-semibold">Nothing here yet</h1>
        <p class="text-text text-lg">
            You are signed in, but this email is not linked to a family or a volunteer role yet. Ask
            a Lifeshaper at church for the registration link, or for a leader to add you.
        </p>
        <SignOutButton class="self-start" />
    </main>
</template>
