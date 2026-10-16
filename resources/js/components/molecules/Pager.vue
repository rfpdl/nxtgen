<script setup lang="ts">
/** "Page 2 of 3" with Previous / Next for the registration lists (50 per page). Hidden when one page holds all. */
defineProps<{ page: number; lastPage: number; total: number }>()

const emit = defineEmits<{ go: [page: number] }>()

const button = 'min-h-touch border-border bg-surface text-text rounded-md border px-4'
</script>

<template>
    <nav v-if="lastPage > 1" class="flex items-center gap-3" aria-label="Pages">
        <button
            v-if="page > 1"
            type="button"
            :class="button"
            data-testid="page-prev"
            @click="emit('go', page - 1)"
        >
            ← Previous
        </button>
        <span class="text-text-muted">Page {{ page }} of {{ lastPage }} · {{ total }} in all</span>
        <button
            v-if="page < lastPage"
            type="button"
            :class="button"
            data-testid="page-next"
            @click="emit('go', page + 1)"
        >
            Next →
        </button>
    </nav>
</template>
