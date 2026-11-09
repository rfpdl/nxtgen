<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue'
import type { ShellItem } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The platform admin's frame (AppShell, spec item 12): the bar and the platform's menu — Satellites, People, Settings.
 */
defineProps<{ title?: string }>()

const page = usePage()

const items = computed<ShellItem[]>(() =>
    [
        {
            label: 'Satellites',
            name: 'platform.satellites.index',
            testid: 'nav-platform-satellites',
        },
        { label: 'People', name: 'platform.people.index', testid: 'nav-platform-people' },
        { label: 'Settings', name: 'platform.settings.show', testid: 'nav-platform-settings' },
    ].map((item) => ({
        label: item.label,
        href: route(item.name),
        testid: item.testid,
        current: page.url === new URL(route(item.name)).pathname,
    })),
)
</script>

<template>
    <AppShell :title="title ?? 'Platform'" :items="items">
        <slot />
    </AppShell>
</template>
