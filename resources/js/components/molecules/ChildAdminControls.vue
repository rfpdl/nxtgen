<script setup lang="ts">
import { useEnum } from '@/composables/useEnum'
import type { ChildPortal, ChildStatus, LevelOption } from '@/types'
import { ref, watch } from 'vue'

/**
 * Staff controls inside a child's card on the family page (DECISIONS 2026-10-17, after first look): status (who
 * may edit the family) and Level (church admins, plan §3.2 — a correction outside Moving-Up). A refused Level
 * change shows only on the child it was for — the page passes `levelError` to that child alone. The statuses come
 * from the page's `enums.child_status`.
 */
const props = defineProps<{
    child: Pick<ChildPortal, 'id' | 'level' | 'status_label'>
    canSetStatus: boolean
    canSetLevel: boolean
    levels: LevelOption[]
    levelError?: string
}>()

const emit = defineEmits<{
    status: [childId: number, status: ChildStatus]
    level: [childId: number, level: number]
}>()

const { options: statuses } = useEnum('child_status')

/** What the person just picked; blank shows the child's current value as the (disabled) first option. */
const level = ref<number | ''>('')
const status = ref<ChildStatus | ''>('')

watch(level, (picked) => {
    if (picked !== '') emit('level', props.child.id, picked)
})
watch(status, (picked) => {
    if (picked !== '') emit('status', props.child.id, picked)
})

const select = 'min-h-touch border-border bg-surface text-text rounded-md border px-3'
</script>

<template>
    <label v-if="canSetLevel" class="text-text flex items-center gap-2">
        Level
        <select v-model="level" :class="select" :data-testid="`level-${child.id}`">
            <option value="" disabled>{{ child.level ?? 'No Level' }}</option>
            <option v-for="l in levels" :key="l.level" :value="l.level">{{ l.name }}</option>
        </select>
    </label>
    <p v-if="canSetLevel && levelError" role="alert" class="text-danger font-semibold">
        {{ levelError }}
    </p>
    <label v-if="canSetStatus" class="text-text flex items-center gap-2">
        Status
        <select v-model="status" :class="select" :data-testid="`status-${child.id}`">
            <option value="" disabled>{{ child.status_label }}</option>
            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
    </label>
</template>
