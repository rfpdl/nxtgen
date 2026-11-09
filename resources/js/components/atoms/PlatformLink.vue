<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import type { SharedProps } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * "Platform" — the platform admin's shortcut to `/platform` (spec item 12, Roy 2026-11-10), beside Switch in the menu;
 * not on the platform's own pages, whose menu already holds them. Quiet in a row; the menu sheet asks for a big
 * `secondary` one.
 */
withDefaults(
    defineProps<{ variant?: 'quiet' | 'secondary'; size?: 'md' | 'lg'; block?: boolean }>(),
    { variant: 'quiet', size: 'md' },
)

const page = usePage<SharedProps>()
const shown = computed(
    () => page.props.auth.is_platform_admin === true && !page.url.startsWith('/platform'),
)
</script>

<template>
    <AppButton
        v-if="shown"
        :href="route('platform.satellites.index')"
        :variant="variant"
        :size="size"
        :block="block"
        data-testid="platform-link"
        >Platform</AppButton
    >
</template>
