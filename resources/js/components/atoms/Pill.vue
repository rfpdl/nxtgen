<script setup lang="ts">
import type { Level } from '@/types'
import { computed } from 'vue'

/**
 * A small rounded label (the Bold look). Give it a `level` (a Level: number 1–4 and name) and it takes that Level's
 * colour from the age-level chart — 1 blue, 2 yellow, 3 red, 4 green — as a soft tint with dark text and a dot of the
 * chart colour, saying the Level's name unless given words of its own (a Squad's name);
 * otherwise a `variant`: `danger` for a warning flag (allergies), a status, or `neutral`. Every pair is ≥7:1.
 * The colour never carries the meaning alone: the words are in the pill.
 */
type Variant = 'neutral' | 'success' | 'warning' | 'info' | 'danger'

const props = withDefaults(defineProps<{ variant?: Variant; level?: Level | null }>(), {
    variant: 'neutral',
    level: null,
})

const VARIANTS: Record<Variant, string> = {
    neutral: 'bg-surface-sunken text-text',
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    info: 'bg-info-soft text-info',
    danger: 'bg-danger-soft text-danger font-bold',
}

const LEVELS: Record<number, { pill: string; dot: string }> = {
    1: { pill: 'bg-level-1-soft text-on-level-1', dot: 'bg-level-1' },
    2: { pill: 'bg-level-2-soft text-on-level-2', dot: 'bg-level-2' },
    3: { pill: 'bg-level-3-soft text-on-level-3', dot: 'bg-level-3' },
    4: { pill: 'bg-level-4-soft text-on-level-4', dot: 'bg-level-4' },
}

const level = computed(() => (props.level === null ? null : (LEVELS[props.level.number] ?? null)))
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-base font-semibold"
        :class="level ? level.pill : VARIANTS[variant]"
    >
        <span
            v-if="level"
            class="size-2.5 shrink-0 rounded-full"
            :class="level.dot"
            aria-hidden="true"
            data-testid="level-dot"
        />
        <slot>{{ props.level?.name }}</slot>
    </span>
</template>
