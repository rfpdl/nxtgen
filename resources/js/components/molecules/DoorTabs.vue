<script setup lang="ts">
import { useChurch } from '@/composables/useChurch'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The door's two tabs (DECISIONS 2026-10-28 — roll call): Check in · Who's in, on both pages, each keeping the class
 * picked (`?class=`). The page it is on is marked (`aria-current`) and looks pressed in, like a picked chip.
 */
const props = defineProps<{ current: 'check-in' | 'whos-in'; classId: number | null }>()

const church = useChurch()

const tabs = computed(() => {
    const query = props.classId === null ? {} : { class: props.classId }
    return [
        {
            key: 'check-in',
            label: 'Check in',
            href: route('church.door.show', { church: church.value.slug, ...query }),
        },
        {
            key: 'whos-in',
            label: "Who's in",
            href: route('church.door.whos-in', { church: church.value.slug, ...query }),
        },
    ]
})
</script>

<template>
    <nav aria-label="Door" class="grid grid-cols-2 gap-2" data-testid="door-tabs">
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
            :data-testid="`door-tab-${tab.key}`"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
