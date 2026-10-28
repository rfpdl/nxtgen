<script setup lang="ts">
import type { ChildHealth } from '@/types'
import { computed } from 'vue'

/**
 * A child's health details at the door, inside their row once it is opened (DECISIONS 2026-10-28; the family card
 * carries an allergy flag and nothing more). Allergies first — loud when there are some, "Allergies: none" when
 * not — then the rest only when given.
 */
const props = defineProps<{ health: ChildHealth }>()

const rows = computed(() =>
    [
        ['Sickness or illness', props.health.illnesses],
        ['Dislikes', props.health.dislikes],
        ['Afraid of', props.health.fears],
        ['From the family', props.health.guardian_notes],
    ].filter((row): row is [string, string] => typeof row[1] === 'string' && row[1] !== ''),
)
</script>

<template>
    <div class="flex flex-col gap-2" aria-live="polite" data-testid="child-health">
        <p
            v-if="health.allergies"
            class="bg-danger-soft text-danger rounded-control p-3 text-lg font-bold"
            data-testid="health-allergies"
        >
            ⚠ Allergies: {{ health.allergies }}
        </p>
        <p v-else class="text-text text-lg font-semibold" data-testid="health-allergies">
            Allergies: none
        </p>
        <dl v-if="rows.length > 0" class="text-text grid gap-x-3 gap-y-1 sm:grid-cols-[auto_1fr]">
            <template v-for="[label, value] in rows" :key="label">
                <dt class="text-text-muted">{{ label }}</dt>
                <dd>{{ value }}</dd>
            </template>
        </dl>
        <p v-else class="text-text">No other health details given.</p>
    </div>
</template>
