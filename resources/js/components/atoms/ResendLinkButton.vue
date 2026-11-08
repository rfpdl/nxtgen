<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import AppButton from '@/components/atoms/AppButton.vue'
import { areaRoute } from '@/composables/useArea'

/**
 * "Send the confirmation link again" for a family whose email is unconfirmed — on the family page, the families list
 * and the door search. A secondary AppButton that POSTs, keeping the scroll. The server decides who may
 * (AccessScope::canResendLink) and flashes the answer.
 */
defineProps<{ familyId: number }>()

const satellite = useSatellite()
</script>

<template>
    <AppButton
        variant="secondary"
        :href="areaRoute('families.resend', [satellite.slug, familyId])"
        method="post"
        :preserve-scroll="true"
        class="self-start"
        data-testid="resend"
        >Send the confirmation link again</AppButton
    >
</template>
