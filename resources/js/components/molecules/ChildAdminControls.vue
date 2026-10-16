<script setup lang="ts">
/**
 * Staff controls under a child on the family page: status (who may edit the family) and group (church admins,
 * plan §3.2). A refused group change shows only on the child it was for — the page passes `groupError` to that
 * child alone (it used to appear under every child).
 */
defineProps<{
    child: { id: number; group: string | null; status: string }
    canSetStatus: boolean
    canSetGroup: boolean
    groups: { id: number; name: string }[]
    groupError?: string
}>()

const emit = defineEmits<{
    status: [childId: number, status: string]
    group: [childId: number, groupId: number]
}>()

const select = 'min-h-touch border-border bg-surface text-text rounded-md border px-3'
</script>

<template>
    <label v-if="canSetGroup" class="text-text flex items-center gap-2">
        Group
        <select
            :class="select"
            :data-testid="`group-${child.id}`"
            @change="emit('group', child.id, Number(($event.target as HTMLSelectElement).value))"
        >
            <option value="" selected disabled>{{ child.group ?? 'No group' }}</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
    </label>
    <p v-if="canSetGroup && groupError" role="alert" class="text-danger font-semibold">
        {{ groupError }}
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
