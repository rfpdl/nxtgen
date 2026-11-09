<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue'
import type { ShellItem } from '@/types'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The parent's frame (AppShell, the Bold look), phone-first: the amber bar with the logo and "My family";
 * the menu is My family, then Switch (only for a parent who also serves) and Sign out. One narrow
 * column on a wide screen, so the page reads like it does on the phone.
 */
withDefaults(defineProps<{ title?: string }>(), { title: 'My family' })

const items = computed<ShellItem[]>(() => [
    {
        label: 'My family',
        href: route('family.show'),
        testid: 'nav-family',
        current: true,
    },
])
</script>

<template>
    <AppShell :title="title" :items="items" narrow>
        <slot />
    </AppShell>
</template>
