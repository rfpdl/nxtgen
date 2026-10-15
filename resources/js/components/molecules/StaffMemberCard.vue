<script setup lang="ts">
import type { StaffMember } from '@/types'

/** One person who serves, with each role they hold. Remove appears only where the viewer may remove it. */
defineProps<{ person: StaffMember }>()

defineEmits<{ remove: [roleId: number] }>()
</script>

<template>
    <article
        class="border-border bg-surface flex flex-col gap-2 rounded-lg border p-4"
        data-testid="staff-member"
    >
        <h3 class="text-text text-lg font-semibold">{{ person.name }}</h3>
        <p class="text-text-muted">{{ person.email }}</p>
        <ul class="flex flex-col gap-1">
            <li v-for="role in person.roles" :key="role.id" class="flex items-center gap-3">
                <span class="bg-primary-soft text-primary rounded-md px-2 py-1 font-semibold">{{
                    role.label
                }}</span>
                <button
                    v-if="role.canRemove"
                    type="button"
                    class="min-h-touch text-danger px-2 underline"
                    data-testid="remove-role"
                    @click="$emit('remove', role.id)"
                >
                    Remove
                </button>
            </li>
        </ul>
    </article>
</template>
