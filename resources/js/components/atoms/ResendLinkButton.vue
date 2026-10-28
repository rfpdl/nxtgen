<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import { useChurch } from '@/composables/useChurch'
import { route } from 'ziggy-js'

/**
 * "Send the confirmation link again" for a family whose email is unconfirmed — on the family page, the families list
 * and the door search. A secondary AppButton that POSTs, keeping the scroll. The server decides who may
 * (AccessScope::canResendLink) and flashes the answer.
 */
defineProps<{ familyId: number }>()

const church = useChurch()
</script>

<template>
    <AppButton
        variant="secondary"
        :href="route('church.families.resend', [church.slug, familyId])"
        method="post"
        :preserve-scroll="true"
        class="self-start"
        data-testid="resend"
        >Send the confirmation link again</AppButton
    >
</template>
