<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import { route } from 'ziggy-js'

/**
 * "Sign out" for every signed-in page — families often share one phone. A POST, so a link preview cannot sign
 * anyone out. Quiet in a row; the menu sheet asks for a big `secondary` one.
 */
withDefaults(
    defineProps<{ variant?: 'quiet' | 'secondary'; size?: 'md' | 'lg'; block?: boolean }>(),
    { variant: 'quiet', size: 'md' },
)
</script>

<template>
    <AppButton
        :href="route('logout')"
        method="post"
        :variant="variant"
        :size="size"
        :block="block"
        data-testid="sign-out"
        >Sign out</AppButton
    >
</template>
