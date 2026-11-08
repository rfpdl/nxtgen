<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import LinkTabs from '@/components/atoms/LinkTabs.vue'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The door's two tabs (DECISIONS 2026-10-28 — roll call): Check in · Who's in, on both pages, each keeping the class
 * picked (`?class=`). The page it is on is marked and pressed in (LinkTabs).
 */
const props = defineProps<{ current: 'check-in' | 'whos-in'; classId: number | null }>()

const satellite = useSatellite()

const tabs = computed(() => {
    const query = props.classId === null ? {} : { class: props.classId }
    return [
        {
            key: 'check-in',
            label: 'Check in',
            href: route('serve.door.show', { satellite: satellite.value.slug, ...query }),
        },
        {
            key: 'whos-in',
            label: "Who's in",
            href: route('serve.door.whos-in', { satellite: satellite.value.slug, ...query }),
        },
    ]
})
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
