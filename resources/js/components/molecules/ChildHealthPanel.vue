<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import type { ChildHealth } from '@/types'
import { computed } from 'vue'

/**
 * A child's health details at the door, shown only when Staff tap the child's name (the family card carries an
 * allergy flag and nothing more). Allergies first and loud; the rest only when given.
 */
const props = defineProps<{ health: ChildHealth }>()
const emit = defineEmits<{ close: [] }>()

const rows = computed(() =>
    [
        ['Sickness or illness', props.health.illnesses],
        ['Dislikes', props.health.dislikes],
        ['Afraid of', props.health.fears],
        ['From the family', props.health.guardian_notes],
    ].filter((row): row is [string, string] => typeof row[1] === 'string' && row[1] !== ''),
)
const nothing = computed(() => !props.health.allergies && rows.value.length === 0)
</script>

<template>
    <Card as="section" class="flex flex-col gap-3" aria-live="polite" data-testid="child-health">
        <h2 class="text-text text-xl font-bold">{{ health.nickname }} — health</h2>
        <p
            v-if="health.allergies"
            class="bg-danger-soft text-danger rounded-control p-3 text-lg font-bold"
            data-testid="health-allergies"
        >
            ⚠ Allergies: {{ health.allergies }}
        </p>
        <dl v-if="rows.length > 0" class="text-text grid gap-x-3 gap-y-1 sm:grid-cols-[auto_1fr]">
            <template v-for="[label, value] in rows" :key="label">
                <dt class="text-text-muted">{{ label }}</dt>
                <dd>{{ value }}</dd>
            </template>
        </dl>
        <p v-if="nothing" class="text-text">No allergies or health details given.</p>
        <AppButton
            variant="secondary"
            class="self-start"
            data-testid="close-health"
            @click="emit('close')"
        >
            Close
        </AppButton>
    </Card>
</template>
