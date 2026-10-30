<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

/**
 * Tabs that are pages (the Bold look): a `nav` named by `label`, each tab a link; the one the page is on is marked
 * (`aria-current`) and pressed in, like a picked chip. Any number: two a row on a phone, all in one row of equal tabs
 * from the sm breakpoint. Each tab's testid is `{testidPrefix}-{key}`. The door's Check in · Who's in, and Cards' steps.
 */
defineProps<{
    label: string
    tabs: { key: string; label: string; href: string }[]
    current: string
    testidPrefix: string
}>()
</script>

<template>
    <nav
        :aria-label="label"
        class="grid grid-cols-2 gap-2 sm:auto-cols-fr sm:grid-flow-col sm:grid-cols-none"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.key"
            :href="tab.href"
            :aria-current="tab.key === current ? 'page' : undefined"
            class="rounded-control flex min-h-14 items-center justify-center border-2 border-b-4 px-3 text-center text-lg font-bold"
            :class="
                tab.key === current
                    ? 'border-primary bg-primary-soft text-primary'
                    : 'border-border-strong bg-surface text-text hover:bg-surface-sunken'
            "
            :data-testid="`${testidPrefix}-${tab.key}`"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
