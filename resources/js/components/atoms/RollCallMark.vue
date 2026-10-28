<script setup lang="ts">
import Pill from '@/components/atoms/Pill.vue'

/**
 * A child's roll call on Attendance (DECISIONS 2026-10-28): "Present ✓" in a success Pill, or "Not marked" in quiet
 * words — one piece for the phone's stacked rows and the table.
 */
defineProps<{ present: boolean }>()
</script>

<template>
    <Pill v-if="present" variant="success">Present ✓</Pill>
    <span v-else class="text-text-muted font-semibold">Not marked</span>
</template>
