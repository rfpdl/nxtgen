<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import type { SharedProps } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * "Switch" between My family and Serve (or two churches) — shown only to someone with more than one place to
 * go (plan §4.5: the choice is remembered, and switchable from the menu). Asks the choice page again. Quiet in a
 * row; the menu sheet asks for a big `secondary` one.
 */
withDefaults(
    defineProps<{ variant?: 'quiet' | 'secondary'; size?: 'md' | 'lg'; block?: boolean }>(),
    { variant: 'quiet', size: 'md' },
)

const page = usePage<SharedProps>()
const canSwitch = computed(() => page.props.auth.can_switch === true)
</script>

<template>
    <AppButton
        v-if="canSwitch"
        :href="route('home', { choose: 1 })"
        :variant="variant"
        :size="size"
        :block="block"
        data-testid="switch-place"
        >Switch</AppButton
    >
</template>
