<script setup lang="ts">
import SelectField from '@/components/atoms/SelectField.vue'
import { useEnum } from '@/composables/useEnum'
import type { ChildPortal, ChildStatus, LevelOption } from '@/types'
import { computed } from 'vue'

/**
 * Staff controls inside a child's card on the family page (DECISIONS 2026-10-17, after first look): Level (church
 * admins, plan §3.2 — a correction outside Moving-Up) and status (who may edit the family), side by side on a wide
 * screen. Each list shows the child's current value first (not pickable); picking another sends it at once. A refused Level change
 * shows only on the child it was for — the page passes `levelError` to that child alone. The statuses come from the
 * page's `enums.child_status`.
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

const levelOptions = computed(() => props.levels.map((l) => ({ value: l.level, label: l.name })))
const statusOptions = computed(() =>
    statuses.value.map((s) => ({ value: s.value, label: s.label })),
)

function pickLevel(picked: string | number | null): void {
    if (picked !== null) emit('level', props.child.id, Number(picked))
}

function pickStatus(picked: string | number | null): void {
    if (picked !== null) emit('status', props.child.id, picked as ChildStatus)
}
</script>

<template>
    <div v-if="canSetLevel || canSetStatus" class="grid gap-4 sm:grid-cols-2">
        <SelectField
            v-if="canSetLevel"
            label="Level"
            :placeholder="child.level?.name ?? 'No Level'"
            placeholder-disabled
            :options="levelOptions"
            :error="levelError"
            :data-testid="`level-${child.id}`"
            @update:model-value="pickLevel"
        />
        <SelectField
            v-if="canSetStatus"
            label="Status"
            :placeholder="child.status_label"
            placeholder-disabled
            :options="statusOptions"
            :data-testid="`status-${child.id}`"
            @update:model-value="pickStatus"
        />
    </div>
</template>
