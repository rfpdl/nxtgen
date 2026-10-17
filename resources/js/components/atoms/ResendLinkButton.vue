<script setup lang="ts">
import { useChurch } from '@/composables/useChurch'
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * "Send the confirmation link again" for a family whose email is unconfirmed — on the family page and on the
 * families and children lists. The server decides who may (AccessScope::canResendLink) and flashes the answer.
 */
defineProps<{ familyId: number }>()

const church = useChurch()
</script>

<template>
    <Link
        :href="route('church.families.resend', [church.slug, familyId])"
        method="post"
        as="button"
        :preserve-scroll="true"
        class="min-h-touch border-primary text-primary self-start rounded-md border px-4 text-lg font-semibold"
        data-testid="resend"
        >Send the confirmation link again</Link
    >
</template>
