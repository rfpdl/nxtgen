<script setup lang="ts">
import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import { useChurch } from '@/composables/useChurch'
import type { DoorClass } from '@/types'
import { computed } from 'vue'

/**
 * The top of both door tabs (DECISIONS 2026-10-26, 2026-10-28): "CCF DXB · Sat 31 Oct" (the chosen class's satellite,
 * else the church) and today's classes as pressable chips — "1PM" over "13:00–15:00", and the satellite when the
 * viewer's Role covers more than one; or, when none meets today, what to do. Picking a class is the page's to send.
 */
const props = defineProps<{ today: string; classes: DoorClass[]; classId: number | null }>()
const emit = defineEmits<{ pick: [classId: number] }>()

const church = useChurch()
const chosen = computed(() => props.classes.find((c) => c.id === props.classId) ?? null)
const manySatellites = computed(() => new Set(props.classes.map((c) => c.satellite)).size > 1)

const chips = computed(() =>
    props.classes.map((c) => ({
        value: c.id,
        label: c.name,
        sub: [c.times, manySatellites.value ? c.satellite : null].filter(Boolean).join(' · '),
    })),
)

function pick(id: string | number | null): void {
    if (id !== null) {
        emit('pick', Number(id))
    }
}
</script>

<template>
    <PageHeader
        :title="`${chosen ? chosen.satellite : church.name} · ${today}`"
        title-testid="door-heading"
    />
    <ChoiceChips
        v-if="classes.length > 0"
        label="Class"
        :options="chips"
        :model-value="classId"
        data-testid="door-class"
        @update:model-value="pick"
    />
    <EmptyState v-else data-testid="no-class">
        No class meets today. Admins add classes on the Classes page.
    </EmptyState>
</template>
