<script setup lang="ts">
import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import type { DoorClass } from '@/types'
import { computed } from 'vue'

/**
 * The top of both door tabs (DECISIONS 2026-10-26, 2026-10-28): today, "Sat 31 Oct" — the satellite is in the bar
 * (spec item 13) — and today's classes as pressable chips, "1PM" over "13:00–15:00"; or, when none meets today, what
 * to do. Picking a class is the page's to send.
 */
const props = defineProps<{ today: string; classes: DoorClass[]; classId: number | null }>()
const emit = defineEmits<{ pick: [classId: number] }>()

const chips = computed(() =>
    props.classes.map((c) => ({
        value: c.id,
        label: c.name,
        sub: c.times ?? '',
    })),
)

function pick(id: string | number | null): void {
    if (id !== null) {
        emit('pick', Number(id))
    }
}
</script>

<template>
    <PageHeader :title="today" title-testid="door-heading" />
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
