<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import FlashBanner from '@/components/molecules/FlashBanner.vue'
import { useChurch } from '@/composables/useChurch'
import { useFlashMessages } from '@/composables/useFlashMessages'

const church = useChurch()

const { message, dismiss } = useFlashMessages()
</script>

<template>
    <Head title="Thank you" />
    <FlashBanner :message="message" @dismiss="dismiss" />
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-4 p-6">
        <h1 class="text-3xl font-semibold text-text">Thank you</h1>
        <p class="text-lg text-text">
            {{ church.name }} has your details. If you can't find our email, check your spam folder, or ask a Lifeshaper at church to
            send it again.
        </p>
    </main>
</template>
