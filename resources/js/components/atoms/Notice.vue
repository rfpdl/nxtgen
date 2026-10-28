<script setup lang="ts">
/**
 * A short message in the page (the Bold look): `danger` when something did not work (an alert, read out at once),
 * `warning` when staff should check something, `info` for a by-the-way (both read out politely, as a status). Bold
 * text on a soft tint of the colour; the words carry the meaning, never the colour alone.
 */
type Tone = 'danger' | 'warning' | 'info'

withDefaults(defineProps<{ tone?: Tone }>(), { tone: 'info' })

const TONES: Record<Tone, string> = {
    danger: 'bg-danger-soft text-danger',
    warning: 'bg-warning-soft text-warning',
    info: 'bg-info-soft text-info',
}
</script>

<template>
    <p
        class="rounded-control p-3 text-lg font-bold"
        :class="TONES[tone]"
        :role="tone === 'danger' ? 'alert' : 'status'"
    >
        <slot />
    </p>
</template>
