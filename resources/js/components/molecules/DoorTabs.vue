<script setup lang="ts">
import LinkTabs from '@/components/atoms/LinkTabs.vue'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The door's two tabs (DECISIONS 2026-10-28 — roll call): Check in · Who's in, on both pages, both at the Session's
 * own code (spec item 16). The page it is on is marked and pressed in (LinkTabs).
 */
const props = defineProps<{ current: 'check-in' | 'whos-in'; code: string }>()

const tabs = computed(() => [
    { key: 'check-in', label: 'Check in', href: route('session.checkin', props.code) },
    { key: 'whos-in', label: "Who's in", href: route('session.whos-in', props.code) },
])
</script>

<template>
    <LinkTabs
        label="Door"
        :tabs="tabs"
        :current="current"
        testid-prefix="door-tab"
        data-testid="door-tabs"
    />
</template>
