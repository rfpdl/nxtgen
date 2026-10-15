<script setup lang="ts">
import type { ChildPortal } from '@/types'

/** One of the parent's own children on the family page. The nickname leads: it is what the child is called. */
defineProps<{ child: ChildPortal }>()
</script>

<template>
    <article
        class="border-border bg-surface flex flex-col gap-1 rounded-lg border p-4"
        data-testid="family-child"
    >
        <h3 class="text-text text-xl font-semibold">{{ child.nickname }}</h3>
        <p class="text-text text-lg">
            {{ [child.firstName, child.middleName, child.lastName].filter(Boolean).join(' ') }}
        </p>
        <p class="text-text-muted">
            Born {{ child.dateOfBirth }} · {{ child.group ?? 'Group not set yet' }}
        </p>
        <p v-if="child.allergies" class="text-danger font-semibold" data-testid="allergy">
            Allergies: {{ child.allergies }}
        </p>
    </article>
</template>
