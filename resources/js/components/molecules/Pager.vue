<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'

/** "Page 2 of 3" with Previous / Next for the registration lists (50 per page). Hidden when one page holds all. */
defineProps<{ page: number; lastPage: number; total: number }>()

const emit = defineEmits<{ go: [page: number] }>()
</script>

<template>
    <nav v-if="lastPage > 1" class="flex flex-wrap items-center gap-3" aria-label="Pages">
        <AppButton
            v-if="page > 1"
            variant="secondary"
            data-testid="page-prev"
            @click="emit('go', page - 1)"
        >
            ← Previous
        </AppButton>
        <span class="text-text-muted">Page {{ page }} of {{ lastPage }} · {{ total }} in all</span>
        <AppButton
            v-if="page < lastPage"
            variant="secondary"
            data-testid="page-next"
            @click="emit('go', page + 1)"
        >
            Next →
        </AppButton>
    </nav>
</template>
