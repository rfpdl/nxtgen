<script setup lang="ts">
import type { LevelOption } from '@/types'

/**
 * Staff controls under a child on the family page: status (who may edit the family) and Level (church admins,
 * plan §3.2 — a correction outside Moving-Up). A refused Level change shows only on the child it was for — the
 * page passes `levelError` to that child alone.
 */
defineProps<{
    child: { id: number; level: string | null; status: string }
    canSetStatus: boolean
    canSetLevel: boolean
    levels: LevelOption[]
    levelError?: string
}>()

const emit = defineEmits<{
    status: [childId: number, status: string]
    level: [childId: number, level: number]
}>()

const select = 'min-h-touch border-border bg-surface text-text rounded-md border px-3'
</script>

<template>
    <label v-if="canSetLevel" class="text-text flex items-center gap-2">
        Level
        <select
            :class="select"
            :data-testid="`level-${child.id}`"
            @change="emit('level', child.id, Number(($event.target as HTMLSelectElement).value))"
        >
            <option value="" selected disabled>{{ child.level ?? 'No Level' }}</option>
            <option v-for="l in levels" :key="l.level" :value="l.level">{{ l.name }}</option>
        </select>
    </label>
    <p v-if="canSetLevel && levelError" role="alert" class="text-danger font-semibold">
        {{ levelError }}
    </p>
    <label v-if="canSetStatus" class="text-text flex items-center gap-2">
        Status
        <select
            :class="select"
            :data-testid="`status-${child.id}`"
            @change="emit('status', child.id, ($event.target as HTMLSelectElement).value)"
        >
            <option value="" selected disabled>{{ child.status }}</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="graduated">Graduated</option>
        </select>
    </label>
</template>
