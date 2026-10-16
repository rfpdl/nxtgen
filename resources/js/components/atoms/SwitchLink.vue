<script setup lang="ts">
import type { SharedProps } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * "Switch" between My family and Serve (or two churches) — shown only to someone with more than one place to
 * go (plan §4.5: the choice is remembered, and switchable from the menu). Asks the choice page again.
 */
const page = usePage<SharedProps>()
const canSwitch = computed(() => page.props.auth.can_switch === true)
</script>

<template>
    <Link
        v-if="canSwitch"
        :href="route('home', { choose: 1 })"
        class="min-h-touch text-text-muted flex items-center underline-offset-4 hover:underline"
        data-testid="switch-place"
        >Switch</Link
    >
</template>
